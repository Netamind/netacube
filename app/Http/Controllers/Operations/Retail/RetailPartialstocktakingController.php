<?php
// FILE: app/Http/Controllers/Operations/Retail/RetailPartialstocktakingController.php

namespace App\Http\Controllers\Operations\Retail;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

/*
 * Partial Stocktaking is Full Stocktaking's opportunistic sibling:
 *
 *   - No session-snapshot seeding for the whole catalog, and no "missing
 *     products" tab — partial counts are opportunistic (a shelf, a
 *     category, a spot-check), not the full catalog. A product's baseline
 *     (expected_at_count) and sales checkpoint (sales_id_at_count) are
 *     frozen directly on ITS OWN row the first time it is counted that
 *     day.
 *
 *   ── "Expected" is FIXED. Full stop. ──────────────────────────────────
 *     expected_at_count is written ONLY in two places: (1) the moment a
 *     product is first counted that day, and (2) an explicit user edit on
 *     the Stocktaking Data tab. Nothing else — no background job, no page
 *     view, no sync, no rectification step — is ever allowed to touch it.
 *     Same rule for sales_id_at_count, the sales checkpoint frozen at the
 *     same moment: it is a permanent snapshot for the day and is never
 *     re-baselined (there is deliberately no "recount" action that would
 *     do that — a correction on the Data tab is always a typo-fix against
 *     the ORIGINAL count, never a new checkpoint).
 *
 *   - Every write (a live count, or an edit on the Stocktaking Data tab)
 *     still pushes the corrected quantity straight to
 *     retail_branch_products.stock_quantity — see pushLiveStock() — so
 *     stock stays live without waiting for rectification. That push nets
 *     off sales that happened after the count purely as an in-memory
 *     calculation (computeSalesSinceCount()); it is never written back
 *     onto the row as a new "expected", and it never changes
 *     expected_at_count or sales_id_at_count.
 *
 *   - expected_final on the row is informational only, and is written in
 *     exactly one place: rectifyRow(), as part of the formal close-off.
 *     That is also where "the actual difference" — expected_at_count
 *     minus everything sold since the frozen checkpoint, each sale
 *     counted exactly once — is settled. Because it's anchored on
 *     sales_id_at_count (never mutated), this is correct even if
 *     expected_at_count itself was edited by a user in between.
 *
 *   - salesSinceCount() gives the auditor the itemised list backing that
 *     number: every sale on a counted product since its frozen checkpoint,
 *     oldest first. It reads the same untouched sales_id_at_count, so it
 *     stays correct no matter when — or how many times — expected was
 *     manually corrected afterwards.
 *
 *   - Rectification (Actions & Info tab) is the formal "close-off"
 *     action: it locks each counted row's status, freezes a
 *     retail_partialstocktaking_summary row (same atomic-INSERT-is-the-
 *     lock pattern as Full Stocktaking), captures the auditor's remarks,
 *     and — critically — makes sure any newly-found product that had no
 *     retail_branch_products row yet gets one created (inheriting the
 *     base price, never freezing today's price as a permanent override),
 *     so branch-specific vs base-catalog settings are never lost.
 *
 *   - "Latest affected product on top": every retail_partialstocktaking
 *     row carries last_activity_line_id, copied from the auto-increment
 *     id of whichever count-line most recently touched it. Because that
 *     id is assigned centrally by MySQL (not by any device's clock),
 *     ordering by it DESC is always correct regardless of which device,
 *     or how many devices, are counting concurrently.
 */
class RetailPartialstocktakingController extends Controller
{
    private const QTY_EPSILON = 0.0001;
    private const RECTIFY_IN_PROGRESS_GRACE_SECONDS = 20;

    /* ════════════════════════════════════════════════════════════════════
       VIEWS
       ════════════════════════════════════════════════════════════════════ */

    public function showCountingView()
    {
        return view('operations.retail.partialstocktaking');
    }

    public function showDataView()
    {
        return view('operations.retail.partialstocktaking-data');
    }

    /**
     * A GET request must never mutate data — simply viewing this page used
     * to silently rewrite expected_final (and push live stock) for every
     * counted row, which is exactly what made "Expected" look like it was
     * changing on its own. That's gone: this view only reads. Expected
     * (expected_at_count) is fixed and comes straight off each row; the
     * only place "sales since count" actually gets settled and written is
     * rectifyRow(). If stock needs a live nudge outside of a count or an
     * edit, that's what the explicit "Refresh Live Figures" button
     * (recomputeAll()) is for.
     */
    public function showActionsAndInfoView()
    {
        $pref = DB::connection('tenant')->table('user_filters')->where('user_id', Auth::id())->first();

        $branchId      = $pref->branch_id ?? null;
        $pstCustomDate = $pref->pst_custom_date ?? null;
        $date          = ! empty($pstCustomDate) ? $pstCustomDate : now()->toDateString();

        if ($branchId) {
            // Keeps a completed summary's totals in sync with any row edits
            // or deletes made since — pure recomputation from fixed
            // expected_at_count values, no writes to the counted rows.
            $this->recomputeSummaryIfRectified((int) $branchId, $date);
        }

        return view('operations.retail.partialstocktaking-actions-and-info');
    }

    public function showHistoryView()
    {
        return view('operations.retail.partialstocktaking-history');
    }

    /* ════════════════════════════════════════════════════════════════════
       SALES-SINCE-COUNT — read-only. Never writes to expected_at_count or
       sales_id_at_count; those two stay exactly as frozen at first count
       (or as later hand-edited by a user), no matter how many times this
       is called. Anchored on sales_id_at_count, the permanent checkpoint,
       so it stays correct even if expected_at_count was edited afterwards.
       ════════════════════════════════════════════════════════════════════ */
    private function computeSalesSinceCount($row): array
    {
        $branchProduct = DB::connection('tenant')->table('retail_branch_products')
            ->where('branch_id', $row->branch_id)
            ->where('base_product_id', $row->base_product_id)
            ->first();

        $qtySoldSinceCount = $branchProduct
            ? (float) DB::connection('tenant')->table('retail_system_sales')
                ->where('branch', (string) $row->branch_id)
                ->where('branch_product_id', $branchProduct->id)
                ->where('id', '>', $row->sales_id_at_count ?? 0)
                ->sum('quantity')
            : 0.0;

        return [
            'branch_product'        => $branchProduct,
            'qty_sold_since_count'  => $qtySoldSinceCount,
            'expected_now'          => max(0, (float) $row->expected_at_count - $qtySoldSinceCount),
        ];
    }

    /**
     * Pushes a corrected quantity straight to retail_branch_products.stock_quantity
     * — the "live" half of Partial Stocktaking. Called after a count merge or a
     * Stocktaking Data edit so stock moves immediately, without waiting for
     * rectification. Deliberately does NOT touch retail_partialstocktaking at
     * all (not expected_at_count, not expected_final) — this only ever moves
     * the branch's live stock figure.
     */
    private function pushLiveStock($row, float $qtySoldSinceCount, $branchProduct = null): void
    {
        $now = now();
        $trueCurrentStock = max(0, (float) $row->found - $qtySoldSinceCount);

        if ($branchProduct) {
            DB::connection('tenant')->table('retail_branch_products')
                ->where('id', $branchProduct->id)
                ->update(['stock_quantity' => $trueCurrentStock, 'updated_at' => $now]);
            return;
        }

        // No retail_branch_products row exists for this branch+product yet
        // (a genuinely new find). Create one now so counted stock is never
        // silently lost — see the price rule note in rectifyRow().
        try {
            DB::connection('tenant')->table('retail_branch_products')->insert([
                'branch_id'       => $row->branch_id,
                'base_product_id' => $row->base_product_id,
                'stock_quantity'  => $trueCurrentStock,
                'selling_price'   => null, // inherits base price — never freeze today's price as a branch override
                'is_active'       => 1,
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ((int) $e->getCode() === 23000) {
                DB::connection('tenant')->table('retail_branch_products')
                    ->where('branch_id', $row->branch_id)
                    ->where('base_product_id', $row->base_product_id)
                    ->update(['stock_quantity' => $trueCurrentStock, 'updated_at' => $now]);
            } else {
                throw $e;
            }
        }
    }

    /**
     * Refreshes live stock for one row against current sales — used after a
     * count merge, a Stocktaking Data edit, and the "Refresh Live Figures"
     * button. Pure convenience wrapper: read the sales-since-count, push it.
     * Never touches expected_at_count, sales_id_at_count, or expected_final.
     */
    private function refreshLiveStock($row): void
    {
        $resolved = $this->computeSalesSinceCount($row);
        $this->pushLiveStock($row, $resolved['qty_sold_since_count'], $resolved['branch_product']);
    }

    /* ════════════════════════════════════════════════════════════════════
       TAB 1 — LIVE COUNTING (cart)
       ════════════════════════════════════════════════════════════════════
       "Live" here means: the moment the cart is submitted, every line is
       written straight through — no lock, no waiting for rectification —
       and stock moves immediately. A product counted again later the same
       day does NOT create a second retail_partialstocktaking row: the
       existing row's `found` is recomputed (the new submission is added
       to the ledger as another line, same additive model as Full
       Stocktaking, so two devices counting the same shelf both count
       safely), and last_activity_line_id is bumped so it resurfaces at
       the top of the Stocktaking Data list.
       ════════════════════════════════════════════════════════════════════ */

    /**
     * Opens (or returns the already-open) counting session for this
     * branch+date+device. The FIRST call each day wins — the session's
     * max_sales_id_at_start is captured then and never moves forward again,
     * even if this is called repeatedly. That's deliberate: the whole point
     * is a checkpoint the device could not have influenced, stamped at (or
     * before) the earliest physical count it will end up covering. Called
     * once when the counting page loads (while the device still has a
     * connection), then cached client-side and reused for every merge that
     * device makes that day, however long it stays offline in between.
     */
    public function startCountingSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id'    => 'required|integer|exists:tenant.branches,id',
            'date'         => 'required|date',
            'device_id'    => 'required|string|max:120',
            'device_label' => 'nullable|string|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $branchId = (int) $request->branch_id;
        $date     = $request->date;
        $deviceId = $request->device_id;
        $now      = now();

        try {
            $sessionId = DB::connection('tenant')->table('retail_partialstocktaking_sessions')->insertGetId([
                'branch_id'             => $branchId,
                'date'                  => $date,
                'device_id'             => $deviceId,
                'device_label'          => $request->device_label,
                'max_sales_id_at_start' => (int) (DB::connection('tenant')->table('retail_system_sales')->max('id') ?? 0),
                'created_at'            => $now,
                'updated_at'            => $now,
            ]);

            return response()->json(['status' => 200, 'session_id' => $sessionId]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ((int) $e->getCode() !== 23000) {
                throw $e;
            }
            // Already opened earlier today by this device — return that one
            // unchanged rather than minting a later (less conservative) one.
            $existing = DB::connection('tenant')->table('retail_partialstocktaking_sessions')
                ->where('branch_id', $branchId)->where('date', $date)->where('device_id', $deviceId)->first();

            return response()->json(['status' => 200, 'session_id' => $existing->id]);
        }
    }

    public function mergeCounts(Request $request)
    {
        if (is_string($request->input('lines'))) {
            $decoded = json_decode($request->input('lines'), true);
            $request->merge(['lines' => is_array($decoded) ? $decoded : []]);
        }

        $validator = Validator::make($request->all(), [
            'password'                => 'required|string',
            'branch_id'               => 'required|integer|exists:tenant.branches,id',
            'date'                    => 'required|date',
            'lines'                   => 'required|array|min:1',
            'lines.*.base_product_id' => 'required|integer',
            'lines.*.quantity'        => 'required|numeric|gt:0',
            'lines.*.client_uuid'     => 'nullable|string|max:60',
            'session_id'              => 'nullable|integer',
            'device_id'               => 'nullable|string|max:120',
            'device_label'            => 'nullable|string|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $tenantUser = DB::connection('tenant')->table('users')->where('id', Auth::id())->first();
        if (! $tenantUser || ! Hash::check($request->password, $tenantUser->password)) {
            return response()->json(['status' => 401, 'message' => 'The password you entered is incorrect.'], 401);
        }

        $branchId = (int) $request->branch_id;
        $date     = $request->date;

        if ($this->isLocked($branchId, $date)) {
            return response()->json(['status' => 409, 'message' => 'This date has already been rectified for this branch.'], 409);
        }

        $now      = now();
        $deviceId = $request->device_id ?: 'unknown-device';
        $merged   = 0;
        $results  = []; // per-line outcome, so the client can show progress + a failed-items report

        // The session's max_sales_id_at_start is a server-minted ceiling —
        // stamped when the device opened its counting session for this
        // branch+date, well before any line in this batch was physically
        // counted. Using it (rather than a client-reported clock) means the
        // counter never gets to choose their own checkpoint. If session_id
        // is missing, doesn't exist, or belongs to a different branch/date,
        // we deliberately do NOT trust it — fall back to the old "checkpoint
        // at merge time" behaviour, which can't be gamed in the counter's
        // favour (it just means offline-gap sales are handled less
        // precisely, not exploitably).
        $sessionCeiling = null;
        if ($request->filled('session_id')) {
            $session = DB::connection('tenant')->table('retail_partialstocktaking_sessions')
                ->where('id', $request->session_id)
                ->where('branch_id', $branchId)
                ->where('date', $date)
                ->first();

            if ($session) {
                $sessionCeiling = (int) $session->max_sales_id_at_start;
            } else {
                Log::warning('Partial stocktaking merge: session_id not found for this branch/date — falling back to merge-time checkpoint.', [
                    'branch_id' => $branchId, 'date' => $date, 'session_id' => $request->session_id,
                ]);
            }
        }

        foreach ($request->lines as $line) {
            $productId  = (int) ($line['base_product_id'] ?? 0);
            $qty        = (float) ($line['quantity'] ?? 0);
            $clientUuid = $line['client_uuid'] ?? ('srv_' . Str::uuid()->toString());
            $lineLabel  = $line['product_name'] ?? ('Product #' . $productId);

            // Any failure below is caught here so ONE bad line (a stale product
            // id, a transient DB hiccup, a constraint violation) never aborts
            // the rest of the cart — it's recorded and merging continues.
            try {
                $branchProduct = DB::connection('tenant')->table('retail_branch_products')
                    ->where('branch_id', $branchId)
                    ->where('base_product_id', $productId)
                    ->first();

                try {
                    $lineId = DB::connection('tenant')->table('retail_partialstocktaking_count_lines')->insertGetId([
                        'date'                 => $date,
                        'branch_id'            => $branchId,
                        'base_product_id'      => $productId,
                        'device_id'            => $deviceId,
                        'device_label'         => $request->device_label,
                        'submitted_by_user_id' => Auth::id(),
                        'quantity'             => $qty,
                        'replaces_line_id'     => null,
                        'client_uuid'          => $clientUuid,
                        'created_at'           => $now,
                        'updated_at'           => $now,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    if ((int) $e->getCode() === 23000) {
                        // duplicate client_uuid — already applied earlier, count it as a success and move on
                        $results[] = ['client_uuid' => $clientUuid, 'base_product_id' => $productId, 'product_name' => $lineLabel, 'status' => 'success'];
                        $merged++;
                        continue;
                    }
                    throw $e;
                }

                $existing = DB::connection('tenant')->table('retail_partialstocktaking')
                    ->where('date', $date)->where('branch_id', $branchId)
                    ->where('base_product_id', $productId)->first();

                if (! $existing) {
                    $baseProduct = DB::connection('tenant')->table('retail_base_products')
                        ->where('id', $productId)->first();

                    // Checkpoint = the highest sales id that already existed when
                    // this device's session opened (or "no ceiling" if we don't
                    // trust the session — see note above). Sales after that id
                    // are what computeSalesSinceCount() will net off going forward,
                    // right through to rectification — this checkpoint never moves.
                    $maxSalesId = $branchProduct
                        ? DB::connection('tenant')->table('retail_system_sales')
                            ->where('branch', (string) $branchId)
                            ->where('branch_product_id', $branchProduct->id)
                            ->when($sessionCeiling !== null, fn ($q) => $q->where('id', '<=', $sessionCeiling))
                            ->max('id')
                        : null;

                    // Sales that happened between the checkpoint and right now —
                    // stock_quantity already reflects them, but the counter never
                    // saw them on the shelf, so add them back to get the true
                    // baseline "as of the checkpoint" rather than "as of this
                    // request".
                    $salesSinceCountToNow = $branchProduct
                        ? DB::connection('tenant')->table('retail_system_sales')
                            ->where('branch', (string) $branchId)
                            ->where('branch_product_id', $branchProduct->id)
                            ->where('id', '>', $maxSalesId ?? 0)
                            ->sum('quantity')
                        : 0;

                    $expectedAtCount = ($branchProduct->stock_quantity ?? 0) + $salesSinceCountToNow;

                    DB::connection('tenant')->table('retail_partialstocktaking')->insert([
                        'date'                   => $date,
                        'branch_id'              => $branchId,
                        'base_product_id'        => $productId,
                        'product_name'           => $baseProduct->name ?? ($line['product_name'] ?? 'Unknown'),
                        'unit'                   => $baseProduct->unit ?? ($line['unit'] ?? 'Each'),
                        'price'                  => $branchProduct->selling_price ?? $baseProduct->selling_price ?? 0,
                        'rate'                   => 1.00,
                        'expected_at_count'      => $expectedAtCount,
                        'sales_id_at_count'      => $maxSalesId,
                        'found'                  => 0,
                        'expected_final'         => $expectedAtCount,
                        'merge_count'            => 0,
                        'source_device_ids'      => json_encode([]),
                        'last_activity_line_id'  => $lineId,
                        'status'                 => 'counted',
                        'counted_by_user_id'     => Auth::id(),
                        'created_at'             => $now,
                        'updated_at'             => $now,
                    ]);

                    $existing = DB::connection('tenant')->table('retail_partialstocktaking')
                        ->where('date', $date)->where('branch_id', $branchId)
                        ->where('base_product_id', $productId)->first();
                }

                $totalFound = max(0, DB::connection('tenant')->table('retail_partialstocktaking_count_lines')
                    ->where('date', $date)->where('branch_id', $branchId)
                    ->where('base_product_id', $productId)->sum('quantity'));

                $lineCount = DB::connection('tenant')->table('retail_partialstocktaking_count_lines')
                    ->where('date', $date)->where('branch_id', $branchId)
                    ->where('base_product_id', $productId)->count();

                $sourceDevices = json_decode($existing->source_device_ids ?? '[]', true) ?: [];
                if (! in_array($deviceId, $sourceDevices, true)) {
                    $sourceDevices[] = $deviceId;
                }

                DB::connection('tenant')->table('retail_partialstocktaking')
                    ->where('id', $existing->id)
                    ->update([
                        'found'                 => $totalFound,
                        'merge_count'           => $lineCount,
                        'source_device_ids'     => json_encode($sourceDevices),
                        'last_activity_line_id' => $lineId, // bump — this row is now the most recently affected
                        'counted_by_user_id'    => Auth::id(),
                        'updated_at'            => $now,
                    ]);

                // Live: push corrected stock immediately. This never touches
                // expected_at_count/sales_id_at_count/expected_final — only
                // retail_branch_products.stock_quantity moves here.
                $freshRow = DB::connection('tenant')->table('retail_partialstocktaking')->find($existing->id);
                $this->refreshLiveStock($freshRow);

                $merged++;
                $results[] = ['client_uuid' => $clientUuid, 'base_product_id' => $productId, 'product_name' => $lineLabel, 'status' => 'success'];
            } catch (\Throwable $e) {
                Log::error('Partial stocktaking merge: line failed', [
                    'branch_id' => $branchId, 'date' => $date, 'base_product_id' => $productId, 'error' => $e->getMessage(),
                ]);
                $results[] = [
                    'client_uuid'     => $clientUuid,
                    'base_product_id' => $productId,
                    'product_name'    => $lineLabel,
                    'quantity'        => $qty,
                    'unit'            => $line['unit'] ?? '',
                    'status'          => 'failed',
                    'error'           => 'Could not save this line — please retry.',
                ];
            }
        }

        $this->recomputeSummaryIfRectified($branchId, $date);

        $failed = count(array_filter($results, fn ($r) => $r['status'] === 'failed'));

        return response()->json([
            'status'  => 200,
            'message' => $failed > 0
                ? "Counted {$merged} product(s), {$failed} failed — live stock updated for the successful ones."
                : "Counted {$merged} product(s) — live stock updated.",
            'merged'  => $merged,
            'failed'  => $failed,
            'results' => $results,
        ]);
    }

    /* ════════════════════════════════════════════════════════════════════
       TAB 2 — STOCKTAKING DATA (editable, offline-queue-then-sync)
       ════════════════════════════════════════════════════════════════════ */

    public function updateDataRow(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'       => 'required|integer|exists:tenant.retail_partialstocktaking,id',
            'expected' => 'required|numeric|gte:0',
            'found'    => 'required|numeric|gte:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $row = DB::connection('tenant')->table('retail_partialstocktaking')->where('id', $request->id)->first();

        if (! $row) {
            return response()->json(['status' => 404, 'error' => 'Row not found.'], 404);
        }

        $this->applyDataRowEdit($row, (float) $request->expected, (float) $request->found);

        return response()->json(['status' => 201, 'success' => 'Data updated successfully.']);
    }

    public function deleteDataRow(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:tenant.retail_partialstocktaking,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $row = DB::connection('tenant')->table('retail_partialstocktaking')->where('id', $request->id)->first();

        if (! $row) {
            return response()->json(['status' => 404, 'error' => 'Row not found.'], 404);
        }

        DB::connection('tenant')->table('retail_partialstocktaking')->where('id', $request->id)->delete();

        $this->recomputeSummaryIfRectified($row->branch_id, $row->date);

        return response()->json(['status' => 201, 'success' => 'Row deleted.']);
    }

    public function syncDataEdits(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ops'               => 'required|array|min:1',
            'ops.*.client_uuid' => 'required|string',
            'ops.*.type'        => 'required|in:update,delete',
            'ops.*.id'          => 'required|integer',
            'ops.*.expected'    => 'required_if:ops.*.type,update|nullable|numeric|gte:0',
            'ops.*.found'       => 'required_if:ops.*.type,update|nullable|numeric|gte:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $applied = $skipped = $failed = [];
        $touchedBranchDates = [];

        foreach ($request->ops as $op) {
            $row = DB::connection('tenant')->table('retail_partialstocktaking')->where('id', $op['id'])->first();

            if (! $row) { $failed[] = $op['client_uuid']; continue; }
            if ($row->last_synced_client_uuid === $op['client_uuid']) { $skipped[] = $op['client_uuid']; continue; }

            if ($op['type'] === 'delete') {
                DB::connection('tenant')->table('retail_partialstocktaking')->where('id', $op['id'])->delete();
                $touchedBranchDates[$row->branch_id . '|' . $row->date] = [$row->branch_id, $row->date];
                $applied[] = $op['client_uuid'];
                continue;
            }

            $this->applyDataRowEdit($row, (float) $op['expected'], (float) $op['found'], $op['client_uuid']);
            $touchedBranchDates[$row->branch_id . '|' . $row->date] = [$row->branch_id, $row->date];
            $applied[] = $op['client_uuid'];
        }

        foreach ($touchedBranchDates as [$branchId, $date]) {
            $this->recomputeSummaryIfRectified($branchId, $date);
        }

        return response()->json([
            'status'  => 200,
            'applied' => $applied,
            'skipped' => $skipped,
            'failed'  => $failed,
            'message' => count($applied) . ' change(s) synced' . (count($failed) ? ', ' . count($failed) . ' failed' : '') . '.',
        ]);
    }

    /**
     * A typo CORRECTION against the ORIGINAL count — nothing about when
     * that count happened has changed, so sales_id_at_count (the sales
     * checkpoint) is never touched here, only ever set once, when the row
     * is first created. expected_at_count only changes because the user
     * explicitly typed a new value into the Expected field — never as a
     * side effect of anything else.
     */
    private function applyDataRowEdit($row, float $newExpected, float $newFound, ?string $clientUuid = null): void
    {
        $now   = now();
        $delta = $newFound - (float) $row->found;
        $lineId = null;

        if (abs($delta) > self::QTY_EPSILON) {
            $uuid = $clientUuid ?? ('correction_' . Str::uuid()->toString());

            try {
                $lineId = DB::connection('tenant')->table('retail_partialstocktaking_count_lines')->insertGetId([
                    'date'                 => $row->date,
                    'branch_id'            => $row->branch_id,
                    'base_product_id'      => $row->base_product_id,
                    'device_id'            => 'partial-data-correction',
                    'device_label'         => 'Manual correction (Stocktaking Data)',
                    'submitted_by_user_id' => Auth::id(),
                    'quantity'             => $delta,
                    'replaces_line_id'     => null,
                    'client_uuid'          => $uuid,
                    'created_at'           => $now,
                    'updated_at'           => $now,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                if ((int) $e->getCode() !== 23000) {
                    throw $e;
                }
            }
        }

        $trueFound = max(0, DB::connection('tenant')->table('retail_partialstocktaking_count_lines')
            ->where('date', $row->date)->where('branch_id', $row->branch_id)
            ->where('base_product_id', $row->base_product_id)->sum('quantity'));

        $update = [
            'found'             => $trueFound,
            'expected_at_count' => $newExpected, // only ever moved by an explicit user edit — baseline lives directly on the row, no separate snapshot table
            'updated_at'        => $now,
        ];

        if ($lineId !== null) {
            $update['last_activity_line_id'] = $lineId; // bump — edited row resurfaces at the top
        }

        if ($clientUuid !== null) {
            $update['last_synced_client_uuid'] = $clientUuid;
        }

        DB::connection('tenant')->table('retail_partialstocktaking')->where('id', $row->id)->update($update);

        // Re-fetch and push live stock — same as counting, and works whether
        // the row is still open or already rectified: editing a rectified
        // row keeps stock correct rather than freezing it stale. This never
        // touches expected_at_count/sales_id_at_count/expected_final.
        $freshRow = DB::connection('tenant')->table('retail_partialstocktaking')->find($row->id);
        $this->refreshLiveStock($freshRow);
    }

    /* ════════════════════════════════════════════════════════════════════
       DEVICE SYNC HEARTBEAT — Stocktaking Data's offline queue + POS.
       The live Counting tab (Tab 1) talks to the server on every
       submission, so it never needs to report here.
       ════════════════════════════════════════════════════════════════════ */

    public function reportDeviceSync(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id'         => 'required|integer|exists:tenant.branches,id',
            'date'              => 'required|date',
            'device_id'         => 'required|string|max:120',
            'device_label'      => 'nullable|string|max:120',
            'device_type'       => 'required|in:partial,pos',
            'pending_ops_count' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        DB::connection('tenant')->table('retail_partialstocktaking_sync_devices')->updateOrInsert(
            [
                'date'        => $request->date,
                'branch_id'   => (int) $request->branch_id,
                'device_id'   => $request->device_id,
                'device_type' => $request->device_type,
            ],
            [
                'device_label'      => $request->device_label,
                'pending_ops_count' => (int) $request->pending_ops_count,
                'last_synced_at'    => now(),
                'updated_at'        => now(),
                'created_at'        => now(),
            ]
        );

        return response()->json(['status' => 200, 'message' => 'Sync status recorded.']);
    }

    public function getSyncStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|integer|exists:tenant.branches,id',
            'date'      => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $devices = DB::connection('tenant')->table('retail_partialstocktaking_sync_devices')
            ->where('branch_id', $request->branch_id)->where('date', $request->date)
            ->orderBy('device_type')->orderBy('device_label')->get();

        $devicesWithPending = $devices->filter(fn ($d) => $d->pending_ops_count > 0)->count();

        return response()->json([
            'status'          => 200,
            'devices'         => $devices,
            'can_rectify'     => $devicesWithPending === 0,
            'pending_devices' => $devicesWithPending,
        ]);
    }

    /* ════════════════════════════════════════════════════════════════════
       TAB 3 — ACTIONS & INFO
       ════════════════════════════════════════════════════════════════════ */

    private function isLocked(int $branchId, string $date): bool
    {
        return DB::connection('tenant')->table('retail_partialstocktaking_summary')
            ->where('branch_id', $branchId)->where('date', $date)
            ->where('status', 'completed')->exists();
    }

    /**
     * Force-refresh live stock for every counted row of a branch+date.
     * Used by the "Refresh live figures" button — useful after a burst of
     * sales, or after a batch of offline edits has just been synced. This
     * only ever moves retail_branch_products.stock_quantity — Expected
     * (expected_at_count) is fixed and is never touched here.
     */
    public function recomputeAll(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|integer|exists:tenant.branches,id',
            'date'      => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $rows = DB::connection('tenant')->table('retail_partialstocktaking')
            ->where('branch_id', $request->branch_id)->where('date', $request->date)->get();

        foreach ($rows as $row) {
            $this->refreshLiveStock($row);
        }

        $this->recomputeSummaryIfRectified((int) $request->branch_id, $request->date);

        return response()->json([
            'status'  => 200,
            'message' => "Refreshed live stock for {$rows->count()} product(s) against sales — Expected values were not changed.",
            'count'   => $rows->count(),
        ]);
    }

    /**
     * Update just the auditor's remarks — no password needed, works any
     * time: before rectification, after it, or in between. If no summary
     * row exists yet (stocktake never rectified), one is created here as a
     * "draft" row — status stays 'pending' and `started_at` is left NULL so
     * submitRectification() can tell it apart from a real in-progress
     * rectification attempt (which always sets started_at) and safely claim
     * it later instead of treating it as a lock held by someone else.
     */
    public function updateRemarks(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|integer|exists:tenant.branches,id',
            'date'      => 'required|date',
            'remarks'   => 'nullable|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $branchId = (int) $request->branch_id;
        $date     = $request->date;
        $now      = now();

        $updated = DB::connection('tenant')->table('retail_partialstocktaking_summary')
            ->where('branch_id', $branchId)->where('date', $date)
            ->update(['remarks' => $request->remarks, 'updated_at' => $now]);

        if (! $updated) {
            // No summary row yet — create a draft one just to hold the remarks.
            // started_at stays NULL: this is NOT a rectification lock.
            try {
                DB::connection('tenant')->table('retail_partialstocktaking_summary')->insert([
                    'date'       => $date,
                    'branch_id'  => $branchId,
                    'status'     => 'pending',
                    'started_at' => null,
                    'remarks'    => $request->remarks,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                if ((int) $e->getCode() !== 23000) {
                    return response()->json(['status' => 500, 'error' => 'Could not save remarks: ' . $e->getMessage()], 500);
                }

                // Lost a race with another request that just created the row
                // (e.g. a concurrent rectify) — just update remarks on it.
                DB::connection('tenant')->table('retail_partialstocktaking_summary')
                    ->where('branch_id', $branchId)->where('date', $date)
                    ->update(['remarks' => $request->remarks, 'updated_at' => $now]);
            }
        }

        return response()->json(['status' => 200, 'success' => 'Remarks saved.']);
    }

    /**
     * Totals are always computed off expected_at_count — the fixed figure —
     * never expected_final. Sales since count cancel out of found-minus-
     * expected identically whether or not they're netted off first, so this
     * is also the mathematically "actual" difference; the difference is
     * that it can never drift just because time passed or a page was
     * reloaded.
     */
    /**
     * Itemised list of every sale recorded against a counted product since
     * ITS OWN frozen sales_id_at_count checkpoint — the evidence behind the
     * "actual difference" settled at rectification. Read-only, and anchored
     * on that checkpoint rather than on expected_at_count, so it stays
     * correct no matter how many times (or when) Expected was hand-edited
     * on the Stocktaking Data tab afterwards. Sorted oldest-first per
     * product — the order the sales actually happened in — using the sale's
     * own auto-increment id (centrally assigned, so it's a safe proxy for
     * "happened before/after" the same way last_activity_line_id is used
     * elsewhere in this module). Only products with at least one sale since
     * their count are returned — nothing to show for the rest.
     */
    public function salesSinceCount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|integer|exists:tenant.branches,id',
            'date'      => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $branchId = (int) $request->branch_id;
        $date     = $request->date;

        $rows = DB::connection('tenant')->table('retail_partialstocktaking')
            ->where('branch_id', $branchId)->where('date', $date)
            ->orderBy('product_name')->get();

        $products = [];
        $grandTotalQty = 0;

        foreach ($rows as $row) {
            $branchProduct = DB::connection('tenant')->table('retail_branch_products')
                ->where('branch_id', $row->branch_id)
                ->where('base_product_id', $row->base_product_id)
                ->first();

            $sales = $branchProduct
                ? DB::connection('tenant')->table('retail_system_sales')
                    ->where('branch', (string) $row->branch_id)
                    ->where('branch_product_id', $branchProduct->id)
                    ->where('id', '>', $row->sales_id_at_count ?? 0)
                    ->orderBy('id') // chronological — the order these sales actually happened in
                    ->get(['id', 'quantity', 'created_at'])
                : collect();

            if ($sales->isEmpty()) {
                continue;
            }

            $qty = (float) $sales->sum('quantity');
            $grandTotalQty += $qty;

            $products[] = [
                'row_id'               => $row->id,
                'product_name'         => $row->product_name,
                'unit'                 => $row->unit,
                'expected_at_count'    => $row->expected_at_count, // fixed — untouched by anything below
                'found'                => $row->found,
                'qty_sold_since_count' => $qty,
                'sales'                => $sales,
            ];
        }

        return response()->json([
            'status'          => 200,
            'branch_id'       => $branchId,
            'date'            => $date,
            'products'        => $products,
            'grand_total_qty' => $grandTotalQty,
        ]);
    }

    private function computeTotals($rows): array
    {
        $noAnomaly = $overage = $shortage = 0;
        $expectedTotal = $foundTotal = $overageTotal = $shortageTotal = 0;

        foreach ($rows as $row) {
            $expected = (float) $row->expected_at_count;
            $expectedTotal += $expected * $row->price;
            $foundTotal    += $row->found * $row->price;

            if (abs($row->found - $expected) < self::QTY_EPSILON) {
                $noAnomaly++;
            } elseif ($row->found > $expected) {
                $overage++;
                $overageTotal += ($row->found - $expected) * $row->price;
            } else {
                $shortage++;
                $shortageTotal += ($expected - $row->found) * $row->price;
            }
        }

        return [
            'products_counted'    => $rows->count(),
            'products_no_anomaly' => $noAnomaly,
            'products_overage'    => $overage,
            'products_shortage'   => $shortage,
            'expected_value'      => $expectedTotal,
            'found_value'         => $foundTotal,
            'overage_value'       => $overageTotal,
            'shortage_value'      => $shortageTotal,
            'difference_value'    => $foundTotal - $expectedTotal,
        ];
    }

    private function recomputeSummaryIfRectified(int $branchId, string $date): void
    {
        $summary = DB::connection('tenant')->table('retail_partialstocktaking_summary')
            ->where('branch_id', $branchId)->where('date', $date)->first();

        if (! $summary || $summary->status !== 'completed') {
            return;
        }

        $rows = DB::connection('tenant')->table('retail_partialstocktaking')
            ->where('branch_id', $branchId)->where('date', $date)->get();

        DB::connection('tenant')->table('retail_partialstocktaking_summary')
            ->where('id', $summary->id)
            ->update($this->computeTotals($rows) + ['updated_at' => now()]);
    }

    /* ════════════════════════════════════════════════════════════════════
       RECTIFICATION — split into start / row / finish, mirroring the Live
       Counting tab's one-line-at-a-time merge pattern (see mergeCounts()
       above). The client claims the lock once via startRectification(),
       then walks the counted rows one at a time through rectifyRow() so a
       single bad row (a stale product id, a transient DB hiccup) never
       aborts the whole batch — progress can be shown live, and any row
       that fails is reported back individually so it can be retried
       (retrying is safe even after finishRectification() has already run,
       since computeSalesSinceCount()/pushLiveStock() are idempotent and
       always safe to re-run).
       finishRectification() then freezes the summary totals regardless of
       whether every row made it to 'rectified' status, exactly as the old
       single-shot version did — a handful of stubborn rows never blocks
       the close-off, they just stay flagged for retry.
       ════════════════════════════════════════════════════════════════════ */

    public function startRectification(Request $request)
    {
        $tag = '[PST-Rectify:start]';

        Log::info("{$tag} ── Request received ──────────────────────────────");
        Log::info("{$tag} User ID  : " . Auth::id());
        Log::info("{$tag} Input    : " . json_encode($request->except('password')));

        // ── STEP 0: VALIDATION ────────────────────────────────────────────
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|integer|exists:tenant.branches,id',
            'date'      => 'required|date',
            'password'  => 'required|string',
            'remarks'   => 'nullable|string|max:5000',
            'force'     => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        // ── STEP 1: PASSWORD CHECK ────────────────────────────────────────
        $tenantUser = DB::connection('tenant')->table('users')->where('id', Auth::id())->first();

        if (! $tenantUser) {
            return response()->json(['status' => 401, 'error' => 'User record not found.'], 401);
        }

        if (! Hash::check($request->password, $tenantUser->password)) {
            return response()->json(['status' => 401, 'error' => 'The password you entered is incorrect.'], 401);
        }

        $branchId = (int) $request->branch_id;
        $date     = $request->date;
        $force    = (bool) $request->boolean('force');
        $now      = now();

        // ── STEP 2: SYNC GATE ─────────────────────────────────────────────
        if (! $force) {
            $pendingDevices = DB::connection('tenant')->table('retail_partialstocktaking_sync_devices')
                ->where('branch_id', $branchId)->where('date', $date)
                ->where('pending_ops_count', '>', 0)
                ->get(['device_id', 'device_label', 'device_type', 'pending_ops_count']);

            if ($pendingDevices->isNotEmpty()) {
                return response()->json([
                    'status'          => 423,
                    'error'           => 'Some devices still have unsynced offline edits.',
                    'pending_devices' => $pendingDevices,
                ], 423);
            }
        }

        // ── STEP 3: CLAIM THE LOCK ────────────────────────────────────────
        try {
            $summaryId = DB::connection('tenant')->table('retail_partialstocktaking_summary')->insertGetId([
                'date'                 => $date,
                'branch_id'            => $branchId,
                'status'               => 'pending',
                'started_at'           => $now,
                'remarks'              => $request->remarks,
                'rectified_by_user_id' => Auth::id(),
                'device_details'       => $request->header('User-Agent'),
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ((int) $e->getCode() !== 23000) {
                Log::error("{$tag} Non-duplicate-key DB error during lock claim: " . $e->getMessage());
                return response()->json(['status' => 500, 'error' => 'Could not start rectification: ' . $e->getMessage()], 500);
            }

            $existing = DB::connection('tenant')->table('retail_partialstocktaking_summary')
                ->where('branch_id', $branchId)->where('date', $date)->first();

            if ($existing->status === 'completed') {
                // Rectification is a one-time, irreversible action — no
                // "re-finalize" path. Remarks can still be changed afterwards,
                // but only through updateRemarks(), never through this endpoint.
                return response()->json([
                    'status' => 409,
                    'error'  => 'This date has already been rectified. Rectification cannot be run again — use Edit Remarks to update the note.',
                ], 409);
            } elseif (is_null($existing->started_at)) {
                // This row was created by updateRemarks() as a remarks-only draft
                // (no rectification has actually been attempted yet) — it isn't a
                // lock held by anyone, so claim it now and turn it into the real
                // rectification-in-progress row.
                $summaryId = $existing->id;
                DB::connection('tenant')->table('retail_partialstocktaking_summary')
                    ->where('id', $summaryId)
                    ->update([
                        'started_at'           => $now,
                        'remarks'              => $request->remarks ?? $existing->remarks,
                        'rectified_by_user_id' => Auth::id(),
                        'device_details'       => $request->header('User-Agent'),
                        'updated_at'           => $now,
                    ]);
            } else {
                $secondsAgo = Carbon::parse($existing->started_at)->diffInSeconds($now);
                if ($secondsAgo < self::RECTIFY_IN_PROGRESS_GRACE_SECONDS) {
                    return response()->json(['status' => 423, 'error' => 'Rectification is already in progress. Please wait a moment and try again.'], 423);
                }
                $summaryId = $existing->id; // stale pending row — resume
            }
        }

        // ── STEP 4: HAND BACK THE ROW LIST FOR THE CLIENT TO WALK ─────────
        // Only rows not already 'rectified' need processing — a resumed
        // stale-pending attempt (see STEP 3 above) may have partially
        // completed last time, and there's no reason to redo good rows.
        $rows = DB::connection('tenant')->table('retail_partialstocktaking')
            ->where('branch_id', $branchId)->where('date', $date)
            ->where('status', '!=', 'rectified')
            ->orderBy('product_name')
            ->get(['id', 'product_name', 'unit', 'price', 'found', 'expected_final', 'expected_at_count']);

        Log::info("{$tag} Lock claimed — summary_id={$summaryId}, {$rows->count()} row(s) to process.");

        return response()->json([
            'status'     => 200,
            'summary_id' => $summaryId,
            'rows'       => $rows,
            'total'      => $rows->count(),
        ]);
    }

    /**
     * Rectify a single counted row. Called once per row by the client so
     * a bad row is reported and skipped rather than aborting the whole
     * close-off. Safe to call again later for a row that previously
     * failed — including after finishRectification() has already flipped
     * the summary to 'completed' — since computeSalesSinceCount() always
     * recomputes fresh against whatever sales have happened since.
     */
    public function rectifyRow(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'summary_id' => 'required|integer|exists:tenant.retail_partialstocktaking_summary,id',
            'branch_id'  => 'required|integer|exists:tenant.branches,id',
            'date'       => 'required|date',
            'row_id'     => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $branchId = (int) $request->branch_id;
        $date     = $request->date;
        $now      = now();

        $row = DB::connection('tenant')->table('retail_partialstocktaking')
            ->where('id', $request->row_id)
            ->where('branch_id', $branchId)
            ->where('date', $date)
            ->first();

        if (! $row) {
            return response()->json([
                'status' => 200,
                'result' => [
                    'id' => (int) $request->row_id, 'product_name' => null,
                    'status' => 'failed', 'error' => 'Row not found — it may have been deleted.',
                ],
            ]);
        }

        try {
            // The ONE place expected_final is written. It's informational —
            // expected_at_count (Expected, and everything the summary/totals
            // are built from) is untouched. This nets off, exactly once,
            // every sale recorded since the row's frozen sales_id_at_count
            // checkpoint — correct even if expected_at_count was hand-edited
            // afterwards, since the checkpoint itself was never moved.
            // pushLiveStock() is the same branch-vs-base-product safeguard as
            // before: creates the retail_branch_products row (price = null,
            // inherits base price) the first time a genuinely new product is
            // closed off, so nothing counted is ever lost and no
            // branch-specific price override is invented that was never
            // deliberately set.
            $resolved = $this->computeSalesSinceCount($row);
            $this->pushLiveStock($row, $resolved['qty_sold_since_count'], $resolved['branch_product']);

            DB::connection('tenant')->table('retail_partialstocktaking')
                ->where('id', $row->id)
                ->update([
                    'expected_final'       => $resolved['expected_now'],
                    'status'               => 'rectified',
                    'rectified_by_user_id' => Auth::id(),
                    'rectified_at'         => $now,
                    'updated_at'           => $now,
                ]);

            return response()->json([
                'status' => 200,
                'result' => ['id' => $row->id, 'product_name' => $row->product_name, 'status' => 'success'],
            ]);
        } catch (\Throwable $e) {
            Log::error('[PST-Rectify:row] Row failed', [
                'branch_id' => $branchId, 'date' => $date, 'row_id' => $row->id, 'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 200,
                'result' => [
                    'id'           => $row->id,
                    'product_name' => $row->product_name,
                    'unit'         => $row->unit,
                    'price'        => $row->price,
                    'found'        => $row->found,
                    'status'       => 'failed',
                    'error'        => 'Could not rectify this row — please retry.',
                ],
            ]);
        }
    }

    /**
     * Freezes the summary totals once the client has walked every row
     * through rectifyRow(). Totals are computed over ALL rows for the
     * date regardless of per-row outcome — exactly as the old single-shot
     * version did — so a handful of stubborn rows never blocks the
     * close-off; they simply stay out of 'rectified' status and can be
     * retried afterwards via rectifyRow().
     */
    public function finishRectification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'summary_id' => 'required|integer|exists:tenant.retail_partialstocktaking_summary,id',
            'branch_id'  => 'required|integer|exists:tenant.branches,id',
            'date'       => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $branchId = (int) $request->branch_id;
        $date     = $request->date;
        $now      = now();

        $finalRows = DB::connection('tenant')->table('retail_partialstocktaking')
            ->where('branch_id', $branchId)->where('date', $date)->get();

        DB::connection('tenant')->table('retail_partialstocktaking_summary')
            ->where('id', $request->summary_id)
            ->update($this->computeTotals($finalRows) + ['status' => 'completed', 'updated_at' => $now]);

        $summaryRow = DB::connection('tenant')->table('retail_partialstocktaking_summary')->find($request->summary_id);

        $notRectified = $finalRows->where('status', '!=', 'rectified')->count();

        Log::info('[PST-Rectify:finish] ── Rectification COMPLETE ─────────', [
            'branch_id' => $branchId, 'date' => $date,
            'total_rows' => $finalRows->count(), 'not_rectified' => $notRectified,
        ]);

        return response()->json([
            'status'        => 201,
            'success'       => $notRectified > 0
                ? "Rectification completed — {$notRectified} row(s) could not be finalized and can be retried from Actions & Info."
                : 'Partial stocktaking rectified successfully.',
            'summary'       => $summaryRow,
            'not_rectified' => $notRectified,
        ], 201);
    }

    /* ════════════════════════════════════════════════════════════════════
       PDF REPORTS
       ════════════════════════════════════════════════════════════════════ */

    /**
     * Single row of tenant company details, shared by both PDF reports —
     * same source table/columns the new deliverynote design reads
     * (company_info: business_name, physical_address, primary_number,
     * email_address). Centralised here so both reports always agree.
     */
    private function getCompanyProfile()
    {
        return DB::connection('tenant')->table('company_info')->first();
    }

    public function downloadReport(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|integer|exists:tenant.branches,id',
            'date'      => 'required|date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $branchId   = (int) $request->branch_id;
        $date       = $request->date;
        $branchName = DB::connection('tenant')->table('branches')->where('id', $branchId)->value('name');

        $summary     = DB::connection('tenant')->table('retail_partialstocktaking_summary')
            ->where('branch_id', $branchId)->where('date', $date)->first();
        $countedRows = DB::connection('tenant')->table('retail_partialstocktaking')
            ->where('branch_id', $branchId)->where('date', $date)->orderBy('product_name')->get();

        $companyProfile = $this->getCompanyProfile();
        $preparedByUser = DB::connection('tenant')->table('users')->where('id', Auth::id())->first();

        // NOTE ON DATA LOGIC: everything below this point is display-only —
        // the totals themselves are still computed the same way inside the
        // Blade view (partialstocktaking-report.blade.php recomputes live
        // from $countedRows so the PDF always matches the screen, whether
        // or not this date has been rectified yet). Nothing here changes
        // that calculation.
        $pdf = Pdf::loadView('operations.retail.partialstocktaking-report', [
            'summary'        => $summary,
            'countedRows'    => $countedRows,
            'branchName'     => $branchName,
            'date'           => $date,
            'displayDate'    => Carbon::parse($date)->format('d F Y'),
            'remarks'        => optional($summary)->remarks,
            'companyName'    => $companyProfile->business_name    ?? 'Netamind Technology',
            'companyAddress' => $companyProfile->physical_address ?? null,
            'companyPhone'   => $companyProfile->primary_number   ?? null,
            'companyEmail'   => $companyProfile->email_address    ?? null,
            'generatedBy'    => $preparedByUser->name ?? (Auth::user()->name ?? 'System'),
        ]);

        return $pdf->download($branchName . ' Partial Stocktaking Report ' . $date . '.pdf');
    }

    public function downloadDeliveryNote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|integer|exists:tenant.branches,id',
            'date'      => 'required|date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $branchId = (int) $request->branch_id;
        $date     = $request->date;
        $branch   = DB::connection('tenant')->table('branches')->where('id', $branchId)->first();

        // DATA LOGIC UNCHANGED: found quantities and totalValue are still
        // computed the exact same way (found * price, summed) — only the
        // surrounding company/branch/prepared-by context below is new,
        // mirroring how the deliverynote design sources that information.
        $countedRows = DB::connection('tenant')->table('retail_partialstocktaking')
            ->where('branch_id', $branchId)->where('date', $date)
            ->orderBy('product_name')
            ->get(['product_name', 'unit', 'price', 'found']);

        $totalValue = $countedRows->sum(fn ($r) => $r->found * $r->price);

        $companyProfile = $this->getCompanyProfile();
        $preparedByUser = DB::connection('tenant')->table('users')->where('id', Auth::id())->first();

        $pdf = Pdf::loadView('operations.retail.partialstocktaking-delivery-report', [
            'countedRows'    => $countedRows,
            'totalValue'     => $totalValue,
            'branch'         => $branch,
            'branchName'     => $branch->name ?? '',
            'date'           => $date,
            'deliveryDate'   => $date,
            'displayDate'    => Carbon::parse($date)->format('d F Y'),
            'companyName'    => $companyProfile->business_name    ?? 'Netamind Technology',
            'companyAddress' => $companyProfile->physical_address ?? null,
            'companyPhone'   => $companyProfile->primary_number   ?? null,
            'companyEmail'   => $companyProfile->email_address    ?? null,
            'preparedByUser' => $preparedByUser,
            'generatedBy'    => $preparedByUser->name ?? (Auth::user()->name ?? 'System'),
        ]);

        return $pdf->download($branchName . ' Partial Stock Delivery Note ' . $date . '.pdf');
    }
}