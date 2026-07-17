<?php
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

class RetailFullstocktakingController extends Controller
{
    private const QTY_EPSILON = 0.0001;
    private const RECTIFY_IN_PROGRESS_GRACE_SECONDS = 20;

    /* ════════════════════════════════════════════════════════════════════
       VIEWS
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
       SESSION SNAPSHOT
       ════════════════════════════════════════════════════════════════════ */

    private function ensureSessionSnapshotSeeded(int $branchId, string $date): void
    {
        $alreadySeeded = DB::connection('tenant')->table('retail_fullstocktaking_session_snapshot')
            ->where('branch_id', $branchId)->where('date', $date)->exists();

        if ($alreadySeeded) {
            return;
        }

        $products = DB::connection('tenant')->table('retail_branch_products')
            ->where('branch_id', $branchId)
            ->get(['id', 'base_product_id', 'stock_quantity']);

        if ($products->isEmpty()) {
            return;
        }

        $now  = now();

        $rows = $products->map(function ($p) use ($branchId, $date, $now) {
            $maxSalesId = DB::connection('tenant')->table('retail_system_sales')
                ->where('branch', (string) $branchId)
                ->where('branch_product_id', $p->id)
                ->max('id');

            return [
                'date'                       => $date,
                'branch_id'                  => $branchId,
                'base_product_id'            => $p->base_product_id,
                'expected_at_session_start'  => $p->stock_quantity,
                'sales_id_at_session_start'  => $maxSalesId,
                'created_at'                 => $now,
                'updated_at'                 => $now,
            ];
        })->toArray();

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::connection('tenant')->table('retail_fullstocktaking_session_snapshot')->insertOrIgnore($chunk);
        }
    }

    public function seedSession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|integer|exists:tenant.branches,id',
            'date'      => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $this->ensureSessionSnapshotSeeded((int) $request->branch_id, $request->date);

        return response()->json(['status' => 200, 'message' => 'Session snapshot ready.']);
    }

    /* ════════════════════════════════════════════════════════════════════
       TAB 1 — COUNTING
       ════════════════════════════════════════════════════════════════════ */

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

        if (DB::connection('tenant')->table('retail_fullstocktaking_summary')
                ->where('branch_id', $branchId)->where('date', $date)->exists()) {
            return response()->json(['status' => 409, 'message' => 'This date has already been rectified for this branch.'], 409);
        }

        $this->ensureSessionSnapshotSeeded($branchId, $date);

        $now      = now();
        $deviceId = $request->device_id ?: 'unknown-device';
        $merged   = 0;
        $results  = []; // per-line outcome, so the client can show progress + a failed-items report

        foreach ($request->lines as $line) {
            $productId  = (int) ($line['base_product_id'] ?? 0);
            $qty        = (float) ($line['quantity'] ?? 0);
            $clientUuid = $line['client_uuid'] ?? ('srv_' . Str::uuid()->toString());
            $lineLabel  = $line['product_name'] ?? ('Product #' . $productId);

            // Any failure below is caught here so ONE bad line (a stale product
            // id, a transient DB hiccup, a constraint violation) never aborts
            // the rest of the batch — it's recorded and merging continues.
            try {
                $branchProduct = DB::connection('tenant')->table('retail_branch_products')
                    ->where('branch_id', $branchId)
                    ->where('base_product_id', $productId)
                    ->first();

                // NOTE: previously this skipped the line entirely when no
                // retail_branch_products row existed yet (`continue`). That blocked
                // counting any product that is in the catalog (retail_base_products) but
                // not yet stocked at this branch — exactly the [NS] "not in system"
                // products the counting view now deliberately surfaces in search. We now
                // allow these through: a missing branch product is treated as having had
                // zero expected stock at this branch, and the branch product record is
                // created for real at rectification time (see rectifyRow).

                try {
                    DB::connection('tenant')->table('retail_fullstocktaking_count_lines')->insert([
                        'date'                  => $date,
                        'branch_id'             => $branchId,
                        'base_product_id'       => $productId,
                        'device_id'             => $deviceId,
                        'device_label'          => $request->device_label,
                        'submitted_by_user_id'  => Auth::id(),
                        'quantity'              => $qty,
                        'replaces_line_id'      => null,
                        'client_uuid'           => $clientUuid,
                        'created_at'            => $now,
                        'updated_at'            => $now,
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

                $existing = DB::connection('tenant')->table('retail_fullstocktaking')
                    ->where('date', $date)->where('branch_id', $branchId)
                    ->where('base_product_id', $productId)->first();

                if (! $existing) {
                    $baseProduct = DB::connection('tenant')->table('retail_base_products')
                        ->where('id', $productId)->first();

                    $snapshot = DB::connection('tenant')->table('retail_fullstocktaking_session_snapshot')
                        ->where('date', $date)->where('branch_id', $branchId)
                        ->where('base_product_id', $productId)->first();

                    $expectedAtCount = $snapshot->expected_at_session_start ?? ($branchProduct->stock_quantity ?? 0);
                    $salesIdAtCount  = $snapshot->sales_id_at_session_start ?? null;

                    if (! $snapshot) {
                        Log::warning('Stocktaking: no session snapshot found for product, falling back to live stock', [
                            'branch_id' => $branchId, 'date' => $date, 'base_product_id' => $productId,
                        ]);
                    }

                    DB::connection('tenant')->table('retail_fullstocktaking')->insert([
                        'date'               => $date,
                        'branch_id'          => $branchId,
                        'base_product_id'    => $productId,
                        'product_name'       => $baseProduct->name ?? ($line['product_name'] ?? 'Unknown'),
                        'unit'               => $baseProduct->unit ?? ($line['unit'] ?? 'Each'),
                        'price'              => $branchProduct->selling_price ?? $baseProduct->selling_price ?? 0,
                        'rate'               => 1.00,
                        'expected_at_count'  => $expectedAtCount,
                        'sales_id_at_count'  => $salesIdAtCount,
                        'found'              => 0,
                        'merge_count'        => 0,
                        'source_device_ids'  => json_encode([]),
                        'status'             => 'counted',
                        'counted_by_user_id' => Auth::id(),
                        'created_at'         => $now,
                        'updated_at'         => $now,
                    ]);

                    $existing = DB::connection('tenant')->table('retail_fullstocktaking')
                        ->where('date', $date)->where('branch_id', $branchId)
                        ->where('base_product_id', $productId)->first();
                }

                $totalFound = DB::connection('tenant')->table('retail_fullstocktaking_count_lines')
                    ->where('date', $date)->where('branch_id', $branchId)
                    ->where('base_product_id', $productId)->sum('quantity');

                $lineCount = DB::connection('tenant')->table('retail_fullstocktaking_count_lines')
                    ->where('date', $date)->where('branch_id', $branchId)
                    ->where('base_product_id', $productId)->count();

                $sourceDevices = json_decode($existing->source_device_ids ?? '[]', true) ?: [];
                if (! in_array($deviceId, $sourceDevices, true)) {
                    $sourceDevices[] = $deviceId;
                }

                DB::connection('tenant')->table('retail_fullstocktaking')
                    ->where('id', $existing->id)
                    ->update([
                        'found'              => max(0, $totalFound),
                        'merge_count'        => $lineCount,
                        'source_device_ids'  => json_encode($sourceDevices),
                        'counted_by_user_id' => Auth::id(),
                        'updated_at'         => $now,
                    ]);

                $merged++;
                $results[] = ['client_uuid' => $clientUuid, 'base_product_id' => $productId, 'product_name' => $lineLabel, 'status' => 'success'];
            } catch (\Throwable $e) {
                Log::error('Full stocktaking merge: line failed', [
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

        $failed = count(array_filter($results, fn ($r) => $r['status'] === 'failed'));

        return response()->json([
            'status'  => 200,
            'message' => $failed > 0
                ? "Merged {$merged} product line(s), {$failed} failed."
                : "Merged {$merged} product line(s) successfully.",
            'merged'  => $merged,
            'failed'  => $failed,
            'results' => $results,
        ]);
    }

    /* ════════════════════════════════════════════════════════════════════
       TAB 2 — MERGED DATA
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

        return response()->json(['status' => 201, 'success' => 'Row deleted.']);
    }

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
            $row = DB::connection('tenant')->table('retail_fullstocktaking')->where('id', $op['id'])->first();

            if (! $row) { $failed[] = $op['client_uuid']; continue; }
            if ($row->last_synced_client_uuid === $op['client_uuid']) { $skipped[] = $op['client_uuid']; continue; }

            if ($op['type'] === 'delete') {
                DB::connection('tenant')->table('retail_fullstocktaking')->where('id', $op['id'])->delete();
                $touchedBranchDates[$row->branch_id . '|' . $row->date] = [$row->branch_id, $row->date];
                $applied[] = $op['client_uuid'];
                continue;
            }

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

    private function applyMergedRowEdit($row, float $newExpected, float $newFound, ?string $clientUuid = null): void
    {
        $now   = now();
        $delta = $newFound - (float) $row->found;

        if (abs($delta) > self::QTY_EPSILON) {
            $uuid = $clientUuid ?? ('correction_' . Str::uuid()->toString());

            try {
                DB::connection('tenant')->table('retail_fullstocktaking_count_lines')->insert([
                    'date'                 => $row->date,
                    'branch_id'            => $row->branch_id,
                    'base_product_id'      => $row->base_product_id,
                    'device_id'            => 'merged-data-correction',
                    'device_label'         => 'Manual correction (Merged Data)',
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

        $trueFound = max(0, DB::connection('tenant')->table('retail_fullstocktaking_count_lines')
            ->where('date', $row->date)->where('branch_id', $row->branch_id)
            ->where('base_product_id', $row->base_product_id)->sum('quantity'));

        $expectedChanged = abs($newExpected - (float) $row->expected_at_count) > self::QTY_EPSILON;

        if ($expectedChanged) {
            DB::connection('tenant')->table('retail_fullstocktaking_session_snapshot')
                ->where('date', $row->date)
                ->where('branch_id', $row->branch_id)
                ->where('base_product_id', $row->base_product_id)
                ->update([
                    'expected_at_session_start' => $newExpected,
                    'updated_at'                => $now,
                ]);
        }

        $update = [
            'expected_at_count' => $newExpected,
            'found'             => $trueFound,
            'updated_at'        => $now,
        ];

        if ($clientUuid !== null) {
            $update['last_synced_client_uuid'] = $clientUuid;
        }

        if ($row->status === 'rectified') {
            $branchProductId = DB::connection('tenant')->table('retail_branch_products')
                ->where('branch_id', $row->branch_id)
                ->where('base_product_id', $row->base_product_id)
                ->value('id');

            $salesSinceCount = $branchProductId
                ? DB::connection('tenant')->table('retail_system_sales')
                    ->where('branch', (string) $row->branch_id)
                    ->where('branch_product_id', $branchProductId)
                    ->where('id', '>', $row->sales_id_at_count ?? 0)
                    ->sum('quantity')
                : 0;

            $update['expected_final'] = max(0, $newExpected - $salesSinceCount);
            $trueCurrentStock = max(0, $trueFound - $salesSinceCount);

            DB::connection('tenant')->table('retail_branch_products')
                ->where('branch_id', $row->branch_id)
                ->where('base_product_id', $row->base_product_id)
                ->update(['stock_quantity' => $trueCurrentStock, 'updated_at' => $now]);
        }

        DB::connection('tenant')->table('retail_fullstocktaking')->where('id', $row->id)->update($update);
    }

    private function recomputeSummaryIfRectified(int $branchId, string $date): void
    {
        $summary = DB::connection('tenant')->table('retail_fullstocktaking_summary')
            ->where('branch_id', $branchId)->where('date', $date)->first();

        if (! $summary || $summary->status !== 'completed') {
            return;
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
       DEVICE SYNC HEARTBEAT
       ════════════════════════════════════════════════════════════════════ */

    public function reportDeviceSync(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id'          => 'required|integer|exists:tenant.branches,id',
            'date'               => 'required|date',
            'device_id'          => 'required|string|max:120',
            'device_label'       => 'nullable|string|max:120',
            'device_type'        => 'required|in:stocktaking,pos',
            'pending_ops_count'  => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        DB::connection('tenant')->table('retail_fullstocktaking_sync_devices')->updateOrInsert(
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

        $devices = DB::connection('tenant')->table('retail_fullstocktaking_sync_devices')
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
       TAB 4 — RECTIFICATION
       ════════════════════════════════════════════════════════════════════ */

    /* ════════════════════════════════════════════════════════════════════
       RECTIFICATION — split into start / row / finish, mirroring the
       Partial Stocktaking controller's one-row-at-a-time pattern (see
       mergeCounts() above for the equivalent on the merge side). The
       client claims the lock once via startRectification(), then walks
       the counted rows one at a time through rectifyRow() so a single bad
       row (a stale product id, a transient DB hiccup) never aborts the
       whole batch — progress can be shown live, and any row that fails is
       reported back individually so it can be retried (retrying is safe
       even after finishRectification() has already run, since the
       per-row logic always recomputes fresh against whatever sales have
       happened since). finishRectification() then removes any still-
       missing products from retail_branch_products and freezes the
       summary totals regardless of whether every row made it to
       'rectified' status, exactly as the old single-shot version did — a
       handful of stubborn rows never blocks the close-off, they just stay
       flagged for retry.
       ════════════════════════════════════════════════════════════════════ */

    public function startRectification(Request $request)
    {
        $tag = '[FST-Rectify:start]';

        Log::info("{$tag} ── Request received ──────────────────────────────");
        Log::info("{$tag} User ID  : " . Auth::id());
        Log::info("{$tag} Input    : " . json_encode($request->except('password')));

        // ── STEP 0: VALIDATION ────────────────────────────────────────────
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|integer|exists:tenant.branches,id',
            'date'      => 'required|date',
            'password'  => 'required|string',
            'force'     => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            Log::warning("{$tag} Validation failed", ['errors' => $validator->errors()->toArray()]);
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

        Log::info("{$tag} branch_id={$branchId} date={$date} force=" . ($force ? 'true' : 'false'));

        // ── STEP 2: SYNC GATE ─────────────────────────────────────────────
        if (! $force) {
            $pendingDevices = DB::connection('tenant')->table('retail_fullstocktaking_sync_devices')
                ->where('branch_id', $branchId)
                ->where('date', $date)
                ->where('pending_ops_count', '>', 0)
                ->get(['device_id', 'device_label', 'device_type', 'pending_ops_count']);

            if ($pendingDevices->isNotEmpty()) {
                Log::warning("{$tag} Sync gate blocked — pending devices", ['devices' => $pendingDevices->toArray()]);
                return response()->json([
                    'status'          => 423,
                    'error'           => 'Some devices still have unsynced offline data.',
                    'pending_devices' => $pendingDevices,
                ], 423);
            }
        } else {
            Log::warning("{$tag} Sync gate BYPASSED via force=true");
        }

        // ── STEP 3: CLAIM THE LOCK ────────────────────────────────────────
        try {
            $summaryId = DB::connection('tenant')->table('retail_fullstocktaking_summary')->insertGetId([
                'date'                 => $date,
                'branch_id'            => $branchId,
                'status'               => 'pending',
                'started_at'           => $now,
                'rectified_by_user_id' => Auth::id(),
                'device_details'       => $request->header('User-Agent'),
                'created_at'           => $now,
                'updated_at'           => $now,
            ]);

            Log::info("{$tag} Lock row inserted — summary ID: {$summaryId}");
        } catch (\Illuminate\Database\QueryException $e) {
            if ((int) $e->getCode() !== 23000) {
                Log::error("{$tag} Non-duplicate-key DB error during lock claim: " . $e->getMessage());
                return response()->json(['status' => 500, 'error' => 'Could not start rectification: ' . $e->getMessage()], 500);
            }

            // Duplicate key — another request already inserted this row
            $existing = DB::connection('tenant')->table('retail_fullstocktaking_summary')
                ->where('branch_id', $branchId)->where('date', $date)->first();

            if ($existing->status === 'completed') {
                // Rectification is a one-time, irreversible action — no
                // "re-finalize" path.
                return response()->json([
                    'status'  => 409,
                    'error'   => 'This date has already been rectified for this branch.',
                    'summary' => $existing,
                ], 409);
            }

            $secondsAgo = Carbon::parse($existing->started_at)->diffInSeconds($now);
            if ($secondsAgo < self::RECTIFY_IN_PROGRESS_GRACE_SECONDS) {
                return response()->json(['status' => 423, 'error' => 'Rectification is already in progress. Please wait a moment and try again.'], 423);
            }

            // Stale pending row — resume
            $summaryId = $existing->id;
            Log::info("{$tag} Resuming stale pending run — summary ID: {$summaryId}");
        }

        // ── STEP 4: HAND BACK THE ROW LIST FOR THE CLIENT TO WALK ─────────
        // Only rows not already 'rectified' need processing — a resumed
        // stale-pending attempt (see STEP 3 above) may have partially
        // completed last time, and there's no reason to redo good rows.
        $rows = DB::connection('tenant')->table('retail_fullstocktaking')
            ->where('branch_id', $branchId)->where('date', $date)
            ->where('status', '!=', 'rectified')
            ->orderBy('product_name')
            ->get(['id', 'base_product_id', 'product_name', 'unit', 'price', 'found', 'expected_at_count', 'sales_id_at_count']);

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
     * the summary to 'completed' — since the netting-off logic always
     * recomputes fresh against whatever sales have happened since.
     */
    public function rectifyRow(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'summary_id' => 'required|integer|exists:tenant.retail_fullstocktaking_summary,id',
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

        $row = DB::connection('tenant')->table('retail_fullstocktaking')
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
            $trueFound = max(0, DB::connection('tenant')->table('retail_fullstocktaking_count_lines')
                ->where('date', $date)->where('branch_id', $branchId)
                ->where('base_product_id', $row->base_product_id)->sum('quantity'));

            $branchProduct = DB::connection('tenant')->table('retail_branch_products')
                ->where('branch_id', $branchId)
                ->where('base_product_id', $row->base_product_id)
                ->first();

            $branchProductId = $branchProduct->id ?? null;

            $salesSinceCount = $branchProductId
                ? DB::connection('tenant')->table('retail_system_sales')
                    ->where('branch', (string) $branchId)
                    ->where('branch_product_id', $branchProductId)
                    ->where('id', '>', $row->sales_id_at_count ?? 0)
                    ->sum('quantity')
                : 0;

            $expectedFinal    = max(0, $row->expected_at_count - $salesSinceCount);
            $trueCurrentStock = max(0, $trueFound - $salesSinceCount);

            DB::connection('tenant')->table('retail_fullstocktaking')
                ->where('id', $row->id)
                ->update([
                    'found'                => $trueFound,
                    'expected_final'       => $expectedFinal,
                    'status'               => 'rectified',
                    'rectified_by_user_id' => Auth::id(),
                    'rectified_at'         => $now,
                    'updated_at'           => $now,
                ]);

            if ($branchProduct) {
                // Existing branch product — update its stock to the rectified figure.
                DB::connection('tenant')->table('retail_branch_products')
                    ->where('id', $branchProduct->id)
                    ->update(['stock_quantity' => $trueCurrentStock, 'updated_at' => $now]);
            } else {
                // Newly-found product — no retail_branch_products row existed for this
                // branch before today's count. Create one now so the product becomes
                // a real, sellable branch product rather than silently losing the
                // counted stock.
                //
                // Price rule (applies before AND after rectification):
                //   1. No branch product row             -> use base price.
                //   2. Branch product row, price is NULL -> use base price.
                //   3. Branch product row, price is set   -> use branch price.
                // A brand-new branch product has never had a deliberate branch
                // override, so selling_price is left NULL here — it "inherits"
                // the base price (via COALESCE elsewhere) rather than freezing
                // today's base price as a permanent branch-specific value.
                try {
                    DB::connection('tenant')->table('retail_branch_products')->insert([
                        'branch_id'       => $branchId,
                        'base_product_id' => $row->base_product_id,
                        'stock_quantity'  => $trueCurrentStock,
                        'selling_price'   => null,
                        'is_active'       => 1,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ]);
                } catch (\Illuminate\Database\QueryException $e) {
                    // Defensive: if a row was created concurrently between our SELECT and
                    // INSERT, fall back to an update instead of failing this row.
                    if ((int) $e->getCode() === 23000) {
                        DB::connection('tenant')->table('retail_branch_products')
                            ->where('branch_id', $branchId)
                            ->where('base_product_id', $row->base_product_id)
                            ->update(['stock_quantity' => $trueCurrentStock, 'updated_at' => $now]);
                    } else {
                        throw $e;
                    }
                }
            }

            return response()->json([
                'status' => 200,
                'result' => ['id' => $row->id, 'product_name' => $row->product_name, 'status' => 'success'],
            ]);
        } catch (\Throwable $e) {
            Log::error('[FST-Rectify:row] Row failed', [
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
     * through rectifyRow(). Also removes any product still sitting in
     * Missing Products for this branch+date from retail_branch_products —
     * it was never counted by any device, so it's treated as not actually
     * in stock. Totals are computed over ALL rows for the date regardless
     * of per-row outcome — exactly as the old single-shot version did —
     * so a handful of stubborn rows never blocks the close-off; they
     * simply stay out of 'rectified' status and can be retried afterwards
     * via rectifyRow().
     */
    public function finishRectification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'summary_id' => 'required|integer|exists:tenant.retail_fullstocktaking_summary,id',
            'branch_id'  => 'required|integer|exists:tenant.branches,id',
            'date'       => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()], 422);
        }

        $branchId = (int) $request->branch_id;
        $date     = $request->date;
        $now      = now();

        // ── REMOVE MISSING PRODUCTS FROM BRANCH PRODUCTS ─────────────────
        $missingBaseProductIds = DB::connection('tenant')->table('retail_fullstocktaking_missing_products')
            ->where('branch_id', $branchId)->where('date', $date)
            ->pluck('base_product_id');

        $branchProductsRemoved = 0;

        if ($missingBaseProductIds->isNotEmpty()) {
            $branchProductsRemoved = DB::connection('tenant')->table('retail_branch_products')
                ->where('branch_id', $branchId)
                ->whereIn('base_product_id', $missingBaseProductIds)
                ->delete();
        }

        Log::info('[FST-Rectify:finish] Missing products removed from retail_branch_products: ' . $branchProductsRemoved);

        // ── COMPUTE SUMMARY TOTALS ────────────────────────────────────────
        $finalRows = DB::connection('tenant')->table('retail_fullstocktaking')
            ->where('branch_id', $branchId)->where('date', $date)->get();

        $productsNoAnomaly = $productsOverage = $productsShortage = 0;
        $expectedTotal = $foundTotal = $overageTotal = $shortageTotal = 0;

        foreach ($finalRows as $row) {
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

        // ── FLIP STATUS TO COMPLETED ──────────────────────────────────────
        DB::connection('tenant')->table('retail_fullstocktaking_summary')
            ->where('id', $request->summary_id)
            ->update([
                'status'                => 'completed',
                'products_counted'      => $finalRows->count(),
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
                'updated_at'            => $now,
            ]);

        $summaryRow = DB::connection('tenant')->table('retail_fullstocktaking_summary')->find($request->summary_id);

        $notRectified = $finalRows->where('status', '!=', 'rectified')->count();

        Log::info('[FST-Rectify:finish] ── Rectification COMPLETE ─────────', [
            'branch_id' => $branchId, 'date' => $date,
            'total_rows' => $finalRows->count(), 'not_rectified' => $notRectified,
        ]);

        return response()->json([
            'status'        => 201,
            'success'       => $notRectified > 0
                ? "Rectification completed — {$notRectified} row(s) could not be finalized and can be retried from Actions & Info."
                : 'Full stocktaking rectified successfully.',
            'summary'       => $summaryRow,
            'not_rectified' => $notRectified,
        ], 201);
    }

    /* ════════════════════════════════════════════════════════════════════
       PDF REPORTS
       ════════════════════════════════════════════════════════════════════ */

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

        $pdf = Pdf::loadView('operations.retail.fullstocktaking-full-report', [
            'summary'     => $summary,
            'countedRows' => $countedRows,
            'missingRows' => $missingRows,
            'branchName'  => $branchName,
            'date'        => $date,
            'displayDate' => Carbon::parse($date)->format('d F Y'),
        ]);

        return $pdf->download($branchName . ' Full Stocktaking Report ' . $date . '.pdf');
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

        $branchId   = (int) $request->branch_id;
        $date       = $request->date;
        $branchName = DB::connection('tenant')->table('branches')->where('id', $branchId)->value('name');

        $countedRows = DB::connection('tenant')->table('retail_fullstocktaking')
            ->where('branch_id', $branchId)->where('date', $date)
            ->orderBy('product_name')
            ->get(['product_name', 'unit', 'price', 'found']);

        $totalValue = $countedRows->sum(fn ($r) => $r->found * $r->price);

        $pdf = Pdf::loadView('operations.retail.fullstocktaking-delivery-report', [
            'countedRows' => $countedRows,
            'totalValue'  => $totalValue,
            'branchName'  => $branchName,
            'date'        => $date,
            'displayDate' => Carbon::parse($date)->format('d F Y'),
        ]);

        return $pdf->download($branchName . ' Stock Delivery Note ' . $date . '.pdf');
    }

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

        return $pdf->download($branchName . ' Merged Data ' . $date . '.pdf');
    }

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

        return $pdf->download($branchName . ' Missing Products ' . $date . '.pdf');
    }
}