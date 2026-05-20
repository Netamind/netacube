<?php

namespace App\Http\Controllers\Operations\Retail;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * RetailFullstocktakingController
 * ════════════════════════════════════════════════════════════════════════
 *
 * VIEW METHODS RETURN BARE VIEWS — NO DATA.
 * ──────────────────────────────────────────
 *   Every show...View() method below is a one-liner: `return view(...)`.
 *   All data (branch selection, date resolution, products, stats, the
 *   "is this date rectified?" check, etc.) is fetched INSIDE each Blade
 *   file via @php blocks — exactly the pattern used by the Action Centre
 *   view (operations.retail.actioncenter). This keeps the controller thin
 *   and keeps each view self-contained / independently refreshable.
 *
 *   Action endpoints (merge, update, delete, sync, rectify, report) are
 *   NOT views — they return JSON or a PDF stream — so they keep their
 *   normal server-side logic below, unchanged in spirit from before.
 *
 * SELECTION STRATEGY (no dedicated selection table):
 * ──────────────────────────────────────────────────
 *   Branch  → user_filters.branch_id
 *   Date    → user_filters.fst_custom_date (NULL = today)
 *   Both resolved independently inside each view's @php block.
 *
 * SALES-NETTING (no timestamp comparison) — UNCHANGED:
 * ───────────────────────────────────────────────────────
 *   At merge time we record MAX(retail_system_sales.id) for this
 *   branch+product as `sales_id_at_count`. At rectification:
 *
 *     sales_since     = SUM(quantity) WHERE id > sales_id_at_count
 *     expected_final  = MAX(0, expected_at_count - sales_since)
 *
 *   This is immune to clock skew and backdated sales entries, and it is
 *   what allows selling to continue uninterrupted while counting and
 *   merging happen on other devices. This protection is automatic and
 *   requires no manual step — it always runs as part of rectification,
 *   and is also re-run automatically on any post-rectify Merged Data
 *   sync (see applyMergedRowEdit()).
 *
 * POST-RECTIFICATION EDITS (Merged Data offline sync):
 * ───────────────────────────────────────────────────────
 *   The Merged Data tab now queues edits offline and syncs them in a
 *   batch (syncMergedData), mirroring the Missing Products pattern.
 *   A queued edit is applied EVEN IF the date has already been
 *   rectified — per product decision, rectification does not lock out
 *   corrections. When that happens we:
 *     1. Update expected_at_count / found on the row.
 *     2. If the row was already rectified, RECOMPUTE expected_final
 *        using the same sales-netting formula (so it stays consistent
 *        with rows rectified normally).
 *     3. Recompute and overwrite the retail_fullstocktaking_summary
 *        row for that date+branch, so History never shows stale totals
 *        after a post-rectify correction.
 *
 * PDF REPORTS (Barryvdh\DomPDF) — NEW:
 * ───────────────────────────────────────────────────────
 *   Three dedicated PDF templates, each fed by its own controller
 *   method, all reusing the shared buildBranchAndDate() / data-pull
 *   helpers below so the figures always match what's on screen:
 *     - downloadFullReport()            → full counted-line breakdown
 *     - downloadDeliveryNote()          → simplified delivery-note view
 *     - downloadMergedDataReport()      → Merged Data tab, as a PDF
 *     - downloadMissingProductsReport() → Missing Products tab, as a PDF
 */
class RetailFullstocktakingController extends Controller
{
    private const QTY_EPSILON = 0.0001;

    /* ════════════════════════════════════════════════════════════════════
       VIEWS — bare, no data. Each Blade file fetches its own data.
       ════════════════════════════════════════════════════════════════════ */

    public function showCountingView()
    {
        return view('operations.retail.fullstocktaking');
    }

    public function showMergedDataView()
    {
        return view('operations.retail.fullstocktaking-merged-data');
    }

    public function showMissingProductsView()
    {
        return view('operations.retail.fullstocktaking-missing-products');
    }

    public function showActionsAndInfoView()
    {
        return view('operations.retail.fullstocktaking-actions-and-info');
    }

    public function showHistoryView()
    {
        return view('operations.retail.fullstocktaking-history');
    }

    public function showHistoryDetailsView()
    {
        return view('operations.retail.fullstocktaking-history-details');
    }

    /* ════════════════════════════════════════════════════════════════════
       TAB 1 — COUNTING — merge offline counted lines
       ════════════════════════════════════════════════════════════════════ */

    /**
     * Merge offline counted lines.
     *
     * SALES SNAPSHOT:
     * For the first merge of a product we record:
     *   sales_id_at_count = MAX(id) FROM retail_system_sales
     *                       WHERE branch = branch_id
     *                         AND productid = base_product_id
     *
     * NULL means no sales row existed yet for this product at this branch,
     * which means at rectify time we sum ALL sales (id > 0 is always true,
     * but we handle NULL specially to mean "sum everything").
     *
     * Selling keeps working uninterrupted: this endpoint never touches
     * retail_branch_products.stock_quantity, so POS sales are unaffected
     * by merges happening concurrently on other devices.
     */
    public function mergeCounts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password'                => 'required|string',
            'branch_id'               => 'required|integer|exists:tenant.branches,id',
            'date'                    => 'required|date',
            'lines'                   => 'required|array|min:1',
            'lines.*.base_product_id' => 'required|integer',
            'lines.*.quantity'        => 'required|numeric|gt:0',
            'device_id'               => 'nullable|string|max:120',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        if (! Hash::check($request->password, Auth::user()->password)) {
            return response()->json(['status' => 401, 'message' => 'The password you entered is incorrect.'], 401);
        }

        $branchId = (int) $request->branch_id;
        $date     = $request->date;

        if (DB::connection('tenant')->table('retail_fullstocktaking_summary')
                ->where('branch_id', $branchId)->where('date', $date)->exists()) {
            return response()->json(['status' => 409, 'message' => 'This date has already been rectified for this branch.'], 409);
        }

        $now      = now();
        $deviceId = $request->device_id ?: 'unknown-device';
        $merged   = 0;

        try {
            DB::connection('tenant')->transaction(function () use ($request, $branchId, $date, $now, $deviceId, &$merged) {
                foreach ($request->lines as $line) {
                    $productId = (int) $line['base_product_id'];
                    $qty       = (float) $line['quantity'];

                    $branchProduct = DB::connection('tenant')->table('retail_branch_products')
                        ->where('branch_id', $branchId)
                        ->where('base_product_id', $productId)
                        ->first();

                    if (! $branchProduct) {
                        continue;
                    }

                    $baseProduct = DB::connection('tenant')->table('retail_base_products')
                        ->where('id', $productId)->first();

                    $existing = DB::connection('tenant')->table('retail_fullstocktaking')
                        ->where('date', $date)
                        ->where('branch_id', $branchId)
                        ->where('base_product_id', $productId)
                        ->first();

                    if ($existing) {
                        // Subsequent merge: accumulate found only.
                        // sales_id_at_count stays from the first merge —
                        // that is the correct snapshot moment.
                        $sourceDevices = json_decode($existing->source_device_ids ?? '[]', true) ?: [];
                        if (! in_array($deviceId, $sourceDevices, true)) {
                            $sourceDevices[] = $deviceId;
                        }

                        DB::connection('tenant')->table('retail_fullstocktaking')
                            ->where('id', $existing->id)
                            ->update([
                                'found'              => $existing->found + $qty,
                                'merge_count'         => $existing->merge_count + 1,
                                'source_device_ids'   => json_encode($sourceDevices),
                                'counted_by_user_id'  => Auth::id(),
                                'updated_at'          => $now,
                            ]);
                    } else {
                        // First merge of this product: snapshot stock + sales sequence.
                        $salesIdAtCount = DB::connection('tenant')->table('retail_system_sales')
                            ->where('branch', (string) $branchId)
                            ->where('productid', $productId)
                            ->max('id'); // NULL if no sales yet — handled at rectify time

                        DB::connection('tenant')->table('retail_fullstocktaking')->insert([
                            'date'               => $date,
                            'branch_id'          => $branchId,
                            'base_product_id'    => $productId,
                            'product_name'       => $baseProduct->name ?? ($line['product_name'] ?? 'Unknown'),
                            'unit'               => $baseProduct->unit ?? ($line['unit'] ?? 'Each'),
                            'price'              => $branchProduct->selling_price ?? $baseProduct->selling_price ?? 0,
                            'rate'               => 1.00,
                            'expected_at_count'  => $branchProduct->stock_quantity,
                            'sales_id_at_count'  => $salesIdAtCount, // integer sequence snapshot
                            'found'              => $qty,
                            'merge_count'        => 1,
                            'source_device_ids'  => json_encode([$deviceId]),
                            'status'             => 'counted',
                            'counted_by_user_id' => Auth::id(),
                            'created_at'         => $now,
                            'updated_at'         => $now,
                        ]);
                    }

                    $merged++;
                }
            });
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'Merge failed: ' . $e->getMessage()], 500);
        }

        return response()->json(['status' => 200, 'message' => "Merged {$merged} product line(s) successfully.", 'merged' => $merged]);
    }

    /* ════════════════════════════════════════════════════════════════════
       TAB 2 — MERGED DATA
       Single-row edit/delete kept for compatibility, PLUS the new
       offline-batch sync endpoint (syncMergedData) used by the rebuilt
       offline-first UI.
       ════════════════════════════════════════════════════════════════════ */

    public function updateMergedRow(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'       => 'required|integer|exists:tenant.retail_fullstocktaking,id',
            'expected' => 'required|numeric|gte:0',
            'found'    => 'required|numeric|gte:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $row = DB::connection('tenant')->table('retail_fullstocktaking')->where('id', $request->id)->first();

        if (! $row) {
            return response()->json(['status' => 404, 'error' => 'Row not found.'], 404);
        }

        $this->applyMergedRowEdit($row, (float) $request->expected, (float) $request->found);

        return response()->json(['status' => 201, 'success' => 'Data updated successfully.']);
    }

    public function deleteMergedRow(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:tenant.retail_fullstocktaking,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $row = DB::connection('tenant')->table('retail_fullstocktaking')->where('id', $request->id)->first();

        if (! $row) {
            return response()->json(['status' => 404, 'error' => 'Row not found.'], 404);
        }

        DB::connection('tenant')->table('retail_fullstocktaking')->where('id', $request->id)->delete();
        $this->recomputeSummaryIfRectified($row->branch_id, $row->date);

        return response()->json(['status' => 201, 'success' => 'Row deleted. It will reappear under Missing Products next time that tab refreshes.']);
    }

    /**
     * Offline-batch sync for the Merged Data tab — same op shape as
     * syncMissingProducts (client_uuid, type, id, fields), separate route.
     *
     * Edits and deletes are applied REGARDLESS of rectification status.
     * If a row is already rectified, an update recomputes expected_final
     * via the same sales-netting formula used at rectification, and the
     * branch+date summary row is recomputed so History stays consistent.
     */
    public function syncMergedData(Request $request)
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
            $row = DB::connection('tenant')->table('retail_fullstocktaking')
                ->where('id', $op['id'])->first();

            if (! $row) { $failed[] = $op['client_uuid']; continue; }

            if ($row->last_synced_client_uuid === $op['client_uuid']) { $skipped[] = $op['client_uuid']; continue; }

            if ($op['type'] === 'delete') {
                DB::connection('tenant')->table('retail_fullstocktaking')->where('id', $op['id'])->delete();
                $touchedBranchDates[$row->branch_id . '|' . $row->date] = [$row->branch_id, $row->date];
                $applied[] = $op['client_uuid'];
                continue;
            }

            // update — applied even if $row->status === 'rectified'
            $this->applyMergedRowEdit($row, (float) $op['expected'], (float) $op['found'], $op['client_uuid']);
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
     * Apply an expected/found edit to a single retail_fullstocktaking row.
     * If the row is already rectified, recompute expected_final using the
     * same sales-netting formula as submitRectification(), so a
     * post-rectify correction stays mathematically consistent — sales
     * made after the count continue to be accounted for automatically,
     * even on a correction made after rectification.
     */
    private function applyMergedRowEdit($row, float $expected, float $found, ?string $clientUuid = null): void
    {
        $update = [
            'expected_at_count' => $expected,
            'found'             => $found,
            'updated_at'        => now(),
        ];

        if ($clientUuid !== null) {
            $update['last_synced_client_uuid'] = $clientUuid;
        }

        if ($row->status === 'rectified') {
            $salesSinceCount = DB::connection('tenant')
                ->table('retail_system_sales')
                ->where('branch', (string) $row->branch_id)
                ->where('productid', $row->base_product_id)
                ->where('id', '>', $row->sales_id_at_count ?? 0)
                ->sum('quantity');

            $update['expected_final'] = max(0, $expected - $salesSinceCount);

            // Found now wins again as the corrected physical count.
            DB::connection('tenant')->table('retail_branch_products')
                ->where('branch_id', $row->branch_id)
                ->where('base_product_id', $row->base_product_id)
                ->update(['stock_quantity' => $found, 'updated_at' => now()]);
        }

        DB::connection('tenant')->table('retail_fullstocktaking')->where('id', $row->id)->update($update);
    }

    /**
     * Recompute retail_fullstocktaking_summary for a date+branch if (and
     * only if) a summary row already exists for it — i.e. it was already
     * rectified. Keeps History totals honest after a post-rectify sync.
     */
    private function recomputeSummaryIfRectified(int $branchId, string $date): void
    {
        $summary = DB::connection('tenant')->table('retail_fullstocktaking_summary')
            ->where('branch_id', $branchId)->where('date', $date)->first();

        if (! $summary) {
            return; // not rectified yet — nothing to keep in sync
        }

        $countedRows = DB::connection('tenant')->table('retail_fullstocktaking')
            ->where('branch_id', $branchId)->where('date', $date)->get();

        $productsNoAnomaly = $productsOverage = $productsShortage = 0;
        $expectedTotal = $foundTotal = $overageTotal = $shortageTotal = 0;

        foreach ($countedRows as $row) {
            $expectedFinal = $row->expected_final ?? $row->expected_at_count;

            $expectedTotal += $expectedFinal * $row->price;
            $foundTotal    += $row->found * $row->price;

            if (abs($row->found - $expectedFinal) < self::QTY_EPSILON) {
                $productsNoAnomaly++;
            } elseif ($row->found > $expectedFinal) {
                $productsOverage++;
                $overageTotal += ($row->found - $expectedFinal) * $row->price;
            } else {
                $productsShortage++;
                $shortageTotal += ($expectedFinal - $row->found) * $row->price;
            }
        }

        $missingRows  = DB::connection('tenant')->table('retail_fullstocktaking_missing_products')
            ->where('branch_id', $branchId)->where('date', $date)->get();
        $missingCount = $missingRows->count();
        $missingValue = $missingRows->sum(fn ($m) => $m->quantity * $m->price);

        $differenceValue     = $foundTotal - $expectedTotal;
        $fullDifferenceValue = $differenceValue - $missingValue;

        DB::connection('tenant')->table('retail_fullstocktaking_summary')
            ->where('id', $summary->id)
            ->update([
                'products_counted'      => $countedRows->count(),
                'products_no_anomaly'   => $productsNoAnomaly,
                'products_overage'      => $productsOverage,
                'products_shortage'     => $productsShortage,
                'expected_value'        => $expectedTotal,
                'found_value'           => $foundTotal,
                'overage_value'         => $overageTotal,
                'shortage_value'        => $shortageTotal,
                'difference_value'      => $differenceValue,
                'missing_count'         => $missingCount,
                'missing_value'         => $missingValue,
                'full_difference_value' => $fullDifferenceValue,
                'updated_at'            => now(),
            ]);
    }

    /* ════════════════════════════════════════════════════════════════════
       TAB 3 — MISSING PRODUCTS
       ════════════════════════════════════════════════════════════════════ */

    public function syncMissingProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ops'               => 'required|array|min:1',
            'ops.*.client_uuid' => 'required|string',
            'ops.*.type'        => 'required|in:update,delete',
            'ops.*.id'          => 'required|integer',
            'ops.*.quantity'    => 'required_if:ops.*.type,update|nullable|numeric|gte:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $applied = $skipped = $failed = [];

        foreach ($request->ops as $op) {
            $row = DB::connection('tenant')->table('retail_fullstocktaking_missing_products')
                ->where('id', $op['id'])->first();

            if (! $row) { $failed[] = $op['client_uuid']; continue; }

            if ($row->client_uuid === $op['client_uuid']) { $skipped[] = $op['client_uuid']; continue; }

            if ($op['type'] === 'delete') {
                DB::connection('tenant')->table('retail_fullstocktaking_missing_products')
                    ->where('id', $op['id'])->delete();
                $applied[] = $op['client_uuid'];
                continue;
            }

            DB::connection('tenant')->table('retail_fullstocktaking_missing_products')
                ->where('id', $op['id'])
                ->update([
                    'quantity'               => $op['quantity'],
                    'client_uuid'            => $op['client_uuid'],
                    'last_edited_by_user_id' => Auth::id(),
                    'last_synced_at'         => now(),
                    'updated_at'             => now(),
                ]);
            $applied[] = $op['client_uuid'];
        }

        return response()->json([
            'status'  => 200,
            'applied' => $applied,
            'skipped' => $skipped,
            'failed'  => $failed,
            'message' => count($applied) . ' change(s) synced' . (count($failed) ? ', ' . count($failed) . ' failed' : '') . '.',
        ]);
    }

    /* ════════════════════════════════════════════════════════════════════
       TAB 4 — ACTIONS AND INFO — RECTIFICATION
       ─────────────────────────────────────────────────────────────────────
       For each counted row:
         sales_since     = SUM(quantity) WHERE id > sales_id_at_count
                           (NULL sales_id_at_count → sum ALL sales)
         expected_final  = MAX(0, expected_at_count - sales_since)
       Then physical count (found) replaces live stock.

       Rectification LOCKS EVERYTHING for this branch+date: counting,
       merging, and missing-product seeding/entry all stop immediately
       once a retail_fullstocktaking_summary row exists (the existence
       check at the top of submitRectification() and mergeCounts() is
       what enforces this). The only way to change figures afterward is
       the explicit post-rectify Merged Data sync path, which re-runs
       the sales-netting formula automatically.
       ════════════════════════════════════════════════════════════════════ */

    public function submitRectification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|integer|exists:tenant.branches,id',
            'date'      => 'required|date',
            'password'  => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        if (! Hash::check($request->password, Auth::user()->password)) {
            return response()->json(['status' => 401, 'error' => 'The password you entered is incorrect.'], 401);
        }

        $branchId = (int) $request->branch_id;
        $date     = $request->date;

        if (DB::connection('tenant')->table('retail_fullstocktaking_summary')
                ->where('branch_id', $branchId)->where('date', $date)->exists()) {
            return response()->json(['status' => 409, 'error' => 'This date has already been rectified for this branch.'], 409);
        }

        $rectificationMoment = now();

        try {
            $summary = DB::connection('tenant')->transaction(function () use ($branchId, $date, $rectificationMoment) {

                $countedRows = DB::connection('tenant')->table('retail_fullstocktaking')
                    ->where('branch_id', $branchId)->where('date', $date)->get();

                $productsNoAnomaly = $productsOverage = $productsShortage = 0;
                $expectedTotal = $foundTotal = $overageTotal = $shortageTotal = 0;

                foreach ($countedRows as $row) {
                    // ── Integer-sequence-based sales netting ──────────────
                    // If sales_id_at_count is NULL no sales existed when
                    // this product was counted → treat all sales as post-count.
                    // This is what lets selling continue uninterrupted on
                    // the floor while stocktaking is in progress: sales made
                    // after a product's count are detected by id, not time,
                    // and are ALWAYS subtracted here automatically — there is
                    // no separate manual step required to "take care of" them.
                    $salesSinceCount = DB::connection('tenant')
                        ->table('retail_system_sales')
                        ->where('branch', (string) $branchId)
                        ->where('productid', $row->base_product_id)
                        ->where('id', '>', $row->sales_id_at_count ?? 0)
                        ->sum('quantity');

                    $expectedFinal = max(0, $row->expected_at_count - $salesSinceCount);

                    DB::connection('tenant')->table('retail_fullstocktaking')
                        ->where('id', $row->id)
                        ->update([
                            'expected_final'       => $expectedFinal,
                            'status'               => 'rectified',
                            'rectified_by_user_id' => Auth::id(),
                            'rectified_at'         => $rectificationMoment,
                            'updated_at'           => $rectificationMoment,
                        ]);

                    $expectedTotal += $expectedFinal * $row->price;
                    $foundTotal    += $row->found * $row->price;

                    if (abs($row->found - $expectedFinal) < self::QTY_EPSILON) {
                        $productsNoAnomaly++;
                    } elseif ($row->found > $expectedFinal) {
                        $productsOverage++;
                        $overageTotal += ($row->found - $expectedFinal) * $row->price;
                    } else {
                        $productsShortage++;
                        $shortageTotal += ($expectedFinal - $row->found) * $row->price;
                    }

                    // Physical count wins — update live stock.
                    DB::connection('tenant')->table('retail_branch_products')
                        ->where('branch_id', $branchId)
                        ->where('base_product_id', $row->base_product_id)
                        ->update(['stock_quantity' => $row->found, 'updated_at' => $rectificationMoment]);
                }

                $missingRows  = DB::connection('tenant')->table('retail_fullstocktaking_missing_products')
                    ->where('branch_id', $branchId)->where('date', $date)->get();
                $missingCount = $missingRows->count();
                $missingValue = $missingRows->sum(fn ($m) => $m->quantity * $m->price);

                $differenceValue     = $foundTotal - $expectedTotal;
                $fullDifferenceValue = $differenceValue - $missingValue;

                $summaryId = DB::connection('tenant')->table('retail_fullstocktaking_summary')->insertGetId([
                    'date'                  => $date,
                    'branch_id'             => $branchId,
                    'products_counted'      => $countedRows->count(),
                    'products_no_anomaly'   => $productsNoAnomaly,
                    'products_overage'      => $productsOverage,
                    'products_shortage'     => $productsShortage,
                    'expected_value'        => $expectedTotal,
                    'found_value'           => $foundTotal,
                    'overage_value'         => $overageTotal,
                    'shortage_value'        => $shortageTotal,
                    'difference_value'      => $differenceValue,
                    'missing_count'         => $missingCount,
                    'missing_value'         => $missingValue,
                    'full_difference_value' => $fullDifferenceValue,
                    'rectified_by_user_id'  => Auth::id(),
                    'device_details'        => request()->header('User-Agent'),
                    'created_at'            => $rectificationMoment,
                    'updated_at'            => $rectificationMoment,
                ]);

                return DB::connection('tenant')->table('retail_fullstocktaking_summary')->find($summaryId);
            });
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'error' => 'Rectification failed: ' . $e->getMessage()], 500);
        }

        return response()->json(['status' => 201, 'success' => 'Full stocktaking rectification completed successfully.', 'summary' => $summary]);
    }

    /* ════════════════════════════════════════════════════════════════════
       PDF REPORTS — Barryvdh\DomPDF
       ─────────────────────────────────────────────────────────────────────
       Each method validates branch_id + date, pulls exactly the data its
       Blade PDF template needs, and streams the PDF inline (opens in a
       new tab via target="_blank" on the calling form).
       ════════════════════════════════════════════════════════════════════ */

    /**
     * Full Report — every counted line, expected/found/diff, plus the
     * missing-products section, plus the live (or rectified) summary
     * stats. Mirrors what's shown in Actions & Info.
     */
    public function downloadFullReport(Request $request)
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

        $summary     = DB::connection('tenant')->table('retail_fullstocktaking_summary')
            ->where('branch_id', $branchId)->where('date', $date)->first();
        $countedRows = DB::connection('tenant')->table('retail_fullstocktaking')
            ->where('branch_id', $branchId)->where('date', $date)->orderBy('product_name')->get();
        $missingRows = DB::connection('tenant')->table('retail_fullstocktaking_missing_products')
            ->where('branch_id', $branchId)->where('date', $date)->orderBy('product_name')->get();

        $pdf = Pdf::loadView('operations.retail.pdf.fullstocktaking-full-report', [
            'summary'     => $summary,
            'countedRows' => $countedRows,
            'missingRows' => $missingRows,
            'branchName'  => $branchName,
            'date'        => $date,
            'displayDate' => Carbon::parse($date)->format('d F Y'),
        ]);

        return $pdf->stream($branchName . ' Full Stocktaking Report ' . $date . '.pdf');
    }

    /**
     * Stock Delivery Note — simplified product / unit / price / qty list,
     * suitable for use as a receiving note when restocking a branch.
     */
    public function downloadDeliveryNote(Request $request)
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

        $countedRows = DB::connection('tenant')->table('retail_fullstocktaking')
            ->where('branch_id', $branchId)->where('date', $date)
            ->orderBy('product_name')
            ->get(['product_name', 'unit', 'price', 'found']);

        $totalValue = $countedRows->sum(fn ($r) => $r->found * $r->price);

        $pdf = Pdf::loadView('operations.retail.pdf.fullstocktaking-delivery-note', [
            'countedRows' => $countedRows,
            'totalValue'  => $totalValue,
            'branchName'  => $branchName,
            'date'        => $date,
            'displayDate' => Carbon::parse($date)->format('d F Y'),
        ]);

        return $pdf->stream($branchName . ' Stock Delivery Note ' . $date . '.pdf');
    }

    /**
     * Merged Data PDF — the full Merged Data tab (product, unit, price,
     * expected, found, difference, merge count), exactly as on screen.
     */
    public function downloadMergedDataReport(Request $request)
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

        $rows = DB::connection('tenant')->table('retail_fullstocktaking')
            ->where('branch_id', $branchId)->where('date', $date)
            ->orderBy('product_name')->get();

        $expectedValue = $rows->sum(fn ($r) => $r->expected_at_count * $r->price);
        $foundValue    = $rows->sum(fn ($r) => $r->found * $r->price);

        $pdf = Pdf::loadView('operations.retail.pdf.fullstocktaking-merged-data', [
            'rows'          => $rows,
            'expectedValue' => $expectedValue,
            'foundValue'    => $foundValue,
            'branchName'    => $branchName,
            'date'          => $date,
            'displayDate'   => Carbon::parse($date)->format('d F Y'),
        ]);

        return $pdf->stream($branchName . ' Merged Data ' . $date . '.pdf');
    }

    /**
     * Missing Products PDF — every product never counted for this
     * branch+date, with its current quantity and value.
     */
    public function downloadMissingProductsReport(Request $request)
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

        $rows = DB::connection('tenant')->table('retail_fullstocktaking_missing_products')
            ->where('branch_id', $branchId)->where('date', $date)
            ->orderBy('product_name')->get();

        $missingValue = $rows->sum(fn ($m) => $m->quantity * $m->price);

        $pdf = Pdf::loadView('operations.retail.pdf.fullstocktaking-missing-products', [
            'rows'         => $rows,
            'missingValue' => $missingValue,
            'branchName'   => $branchName,
            'date'         => $date,
            'displayDate'  => Carbon::parse($date)->format('d F Y'),
        ]);

        return $pdf->stream($branchName . ' Missing Products ' . $date . '.pdf');
    }
}