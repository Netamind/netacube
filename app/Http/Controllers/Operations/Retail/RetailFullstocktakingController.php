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

        foreach ($request->lines as $line) {
            $productId   = (int) $line['base_product_id'];
            $qty         = (float) $line['quantity'];
            $clientUuid  = $line['client_uuid'] ?? ('srv_' . Str::uuid()->toString());

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
            // created for real at rectification time (see submitRectification).

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
        }

        return response()->json(['status' => 200, 'message' => "Merged {$merged} product line(s) successfully.", 'merged' => $merged]);
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

    public function submitRectification(Request $request)
    {
        $tag = '[FST-Rectify]'; // prefix every log line so they're easy to grep

        Log::info("{$tag} ── Request received ──────────────────────────────");
        Log::info("{$tag} User ID  : " . Auth::id());
        Log::info("{$tag} IP       : " . $request->ip());
        Log::info("{$tag} Method   : " . $request->method());
        Log::info("{$tag} URL      : " . $request->fullUrl());
        Log::info("{$tag} Accept   : " . $request->header('Accept'));
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

        Log::info("{$tag} Validation passed");

        // ── STEP 1: PASSWORD CHECK ────────────────────────────────────────
        $tenantUser = DB::connection('tenant')->table('users')->where('id', Auth::id())->first();

        if (! $tenantUser) {
            Log::error("{$tag} Tenant user record not found for Auth ID: " . Auth::id());
            return response()->json(['status' => 401, 'error' => 'User record not found.'], 401);
        }

        if (! Hash::check($request->password, $tenantUser->password)) {
            Log::warning("{$tag} Password mismatch for user ID: " . Auth::id());
            return response()->json(['status' => 401, 'error' => 'The password you entered is incorrect.'], 401);
        }

        Log::info("{$tag} Password verified");

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

            Log::info("{$tag} Sync gate passed — no pending devices");
        } else {
            Log::warning("{$tag} Sync gate BYPASSED via force=true");
        }

        // ── STEP 3: CLAIM THE LOCK ────────────────────────────────────────
        Log::info("{$tag} Attempting to insert summary lock row");

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
            Log::warning("{$tag} Summary INSERT exception — code={$e->getCode()} msg={$e->getMessage()}");

            if ((int) $e->getCode() !== 23000) {
                Log::error("{$tag} Non-duplicate-key DB error during lock claim", [
                    'code'    => $e->getCode(),
                    'message' => $e->getMessage(),
                ]);
                return response()->json(['status' => 500, 'error' => 'Could not start rectification: ' . $e->getMessage()], 500);
            }

            // Duplicate key — another request already inserted this row
            $existing = DB::connection('tenant')->table('retail_fullstocktaking_summary')
                ->where('branch_id', $branchId)->where('date', $date)->first();

            Log::info("{$tag} Existing summary row found", [
                'id'         => $existing->id,
                'status'     => $existing->status,
                'started_at' => $existing->started_at,
            ]);

            if ($existing->status === 'completed') {
                Log::info("{$tag} Already completed — returning 409");
                return response()->json([
                    'status'  => 409,
                    'error'   => 'This date has already been rectified for this branch.',
                    'summary' => $existing,
                ], 409);
            }

            $secondsAgo = Carbon::parse($existing->started_at)->diffInSeconds($now);
            Log::info("{$tag} Pending row is {$secondsAgo}s old (grace=" . self::RECTIFY_IN_PROGRESS_GRACE_SECONDS . "s)");

            if ($secondsAgo < self::RECTIFY_IN_PROGRESS_GRACE_SECONDS) {
                Log::warning("{$tag} Another rectification is in progress — returning 423");
                return response()->json(['status' => 423, 'error' => 'Rectification is already in progress. Please wait a moment and try again.'], 423);
            }

            // Stale pending row — resume
            $summaryId = $existing->id;
            Log::info("{$tag} Resuming stale pending run — summary ID: {$summaryId}");
        }

        // ── STEP 4: WRITE EACH PRODUCT ROW ───────────────────────────────
        $countedRows = DB::connection('tenant')->table('retail_fullstocktaking')
            ->where('branch_id', $branchId)->where('date', $date)->get();

        Log::info("{$tag} Products to process: " . $countedRows->count());

        $stockUpdateFailures = [];
        $branchProductsInserted = 0;

        foreach ($countedRows as $row) {
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
                // today's base price as a permanent branch-specific value. If we
                // copied the base price in, a later change to the base price
                // would stop reaching this branch, which breaks the rule above.
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
                    $branchProductsInserted++;
                } catch (\Illuminate\Database\QueryException $e) {
                    // Defensive: if a row was created concurrently between our SELECT and
                    // INSERT, fall back to an update instead of failing the whole run.
                    if ((int) $e->getCode() === 23000) {
                        DB::connection('tenant')->table('retail_branch_products')
                            ->where('branch_id', $branchId)
                            ->where('base_product_id', $row->base_product_id)
                            ->update(['stock_quantity' => $trueCurrentStock, 'updated_at' => $now]);
                    } else {
                        Log::error("{$tag} Failed to insert new branch product for base_product_id={$row->base_product_id}: " . $e->getMessage());
                        $stockUpdateFailures[] = $row->base_product_id;
                    }
                }
            }
        }

        Log::info("{$tag} Product rows processed. New branch products created: {$branchProductsInserted}. Stock update failures: " . count($stockUpdateFailures));

        // ── STEP 4b: REMOVE MISSING PRODUCTS FROM BRANCH PRODUCTS ────────
        // Anything still sitting in the missing-products table for this branch+date
        // was never counted by any device, so it is treated as not actually in stock
        // at this branch. Its retail_branch_products row is removed entirely.
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

        Log::info("{$tag} Missing products removed from retail_branch_products: {$branchProductsRemoved}");

        // ── STEP 5: COMPUTE SUMMARY TOTALS ───────────────────────────────
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

        Log::info("{$tag} Summary totals computed", [
            'products_counted' => $finalRows->count(),
            'expected_total'   => $expectedTotal,
            'found_total'      => $foundTotal,
            'difference'       => $differenceValue,
            'missing_count'    => $missingCount,
        ]);

        // ── STEP 6: FLIP STATUS TO COMPLETED ─────────────────────────────
        Log::info("{$tag} Flipping summary row {$summaryId} to completed");

        DB::connection('tenant')->table('retail_fullstocktaking_summary')
            ->where('id', $summaryId)
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
                'updated_at'            => now(),
            ]);

        $summaryRow = DB::connection('tenant')->table('retail_fullstocktaking_summary')->find($summaryId);

        Log::info("{$tag} ── Rectification COMPLETE — returning 201 ─────────");

        return response()->json([
            'status'  => 201,
            'success' => 'Full stocktaking rectified successfully.',
            'summary' => $summaryRow,
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