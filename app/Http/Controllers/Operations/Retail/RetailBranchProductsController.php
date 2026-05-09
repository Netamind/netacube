<?php
namespace App\Http\Controllers\Operations\Retail;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use DB;

class RetailBranchProductsController extends Controller
{

    public function showBranchproductsView()
    {
        return view('operations.retail.branchproducts');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function fetchBaseProduct(int $baseProductId)
    {
        return DB::connection('tenant')
            ->table('retail_base_products')
            ->where('id', $baseProductId)
            ->first(['id', 'name', 'code', 'unit', 'supplier', 'selling_price', 'cost_price']);
    }

    private function fetchBaseProductsMap(array $baseProductIds): array
    {
        if (empty($baseProductIds)) return [];
        return DB::connection('tenant')
            ->table('retail_base_products')
            ->whereIn('id', $baseProductIds)
            ->get(['id', 'name', 'code', 'unit', 'supplier', 'selling_price', 'cost_price'])
            ->keyBy('id')
            ->all();
    }

    private function mergeWithBase($branchRow, $base): object
    {
        $merged             = (array) $branchRow;
        $merged['name']     = $base->name          ?? null;
        $merged['code']     = $base->code           ?? null;
        $merged['unit']     = $base->unit           ?? 'Each';
        $merged['supplier'] = $base->supplier       ?? null;
        $merged['bp_sell']  = $base->selling_price  ?? null;
        $merged['bp_cost']  = $base->cost_price     ?? null;
        return (object) $merged;
    }

    private function formatBranchProduct($bp): array
    {
        $bpSell = $bp->bp_sell ?? null;
        $bpCost = $bp->bp_cost ?? null;

        $sellIsBranch = ($bp->selling_price !== null && (string)$bp->selling_price !== (string)$bpSell);
        $costIsBranch = ($bp->cost_price    !== null && (string)$bp->cost_price    !== (string)$bpCost);

        return [
            'id'                   => $bp->id,
            'row'                  => 'row' . $bp->id,
            'branch_id'            => $bp->branch_id,
            'base_product_id'      => $bp->base_product_id,
            'name'                 => $bp->name     ?? null,
            'code'                 => $bp->code      ?? null,
            'unit'                 => $bp->unit      ?? 'Each',
            'supplier'             => $bp->supplier  ?? null,
            'bp_sell'              => $bpSell,
            'bp_cost'              => $bpCost,
            'selling_price'        => $bp->selling_price,
            'cost_price'           => $bp->cost_price           ?? null,
            'sell_is_branch'       => $sellIsBranch,
            'cost_is_branch'       => $costIsBranch,
            'primary_barcode'      => $bp->primary_barcode      ?? null,
            'batch_number'         => $bp->batch_number         ?? null,
            'expiry_date'          => $bp->expiry_date          ?? null,
            'stock_quantity'       => $bp->stock_quantity       ?? 0,
            'reorder_point'        => $bp->reorder_point        ?? 0,
            'reorder_quantity'     => $bp->reorder_quantity     ?? null,
            'max_stock'            => $bp->max_stock            ?? null,
            'track_stock'          => (int) ($bp->track_stock          ?? 1),
            'is_active'            => (int) ($bp->is_active            ?? 1),
            'allow_negative_stock' => (int) ($bp->allow_negative_stock ?? 0),
        ];
    }

    private function fetchBranchProduct(int $id)
    {
        $branchRow = DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('id', $id)
            ->first();
        if (!$branchRow) return null;
        $base = $this->fetchBaseProduct((int) $branchRow->base_product_id);
        return $this->mergeWithBase($branchRow, $base ?? (object) []);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  UA parsers (mirrors RetailAuditLogsController — keep in sync or
    //  extract to a shared trait if the codebase grows)
    // ─────────────────────────────────────────────────────────────────────

    private function parseDeviceType(string $ua): string
    {
        $ua = strtolower($ua);
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad'))  return 'tablet';
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android')
            || str_contains($ua, 'iphone'))                             return 'mobile';
        return 'desktop';
    }

    private function parseBrowser(string $ua): string
    {
        if (str_contains($ua, 'Edg'))                                   return 'Edge';
        if (str_contains($ua, 'OPR') || str_contains($ua, 'Opera'))    return 'Opera';
        if (str_contains($ua, 'Chrome'))                                return 'Chrome';
        if (str_contains($ua, 'Firefox'))                               return 'Firefox';
        if (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) return 'Safari';
        if (str_contains($ua, 'MSIE')   || str_contains($ua, 'Trident')) return 'IE';
        return 'Other';
    }

    private function parseOS(string $ua): string
    {
        if (str_contains($ua, 'Windows NT'))                            return 'Windows';
        if (str_contains($ua, 'Mac OS X'))                              return 'macOS';
        if (str_contains($ua, 'Android'))                               return 'Android';
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad'))  return 'iOS';
        if (str_contains($ua, 'Linux'))                                 return 'Linux';
        return 'Other';
    }

    /**
     * Write one fully-populated row to retail_inventory_logs.
     *
     * @param  int         $baseProductId
     * @param  int         $branchId
     * @param  float       $stockBefore
     * @param  float       $stockAfter
     * @param  string      $operationType   One of the enum values in the migration.
     * @param  string      $reason          Human-readable note stored in action_reason.
     * @param  float|null  $sellingPrice    Snapshot price at the time of the action.
     * @param  float|null  $costPrice       Snapshot cost at the time of the action.
     * @param  string      $sourceType      Originating module, e.g. 'Manual', 'Transfer'.
     * @param  int|null    $sourceId        PK in the originating module's table (nullable).
     */
    private function logStockChange(
        int     $baseProductId,
        int     $branchId,
        float   $stockBefore,
        float   $stockAfter,
        string  $operationType,
        string  $reason,
        ?float  $sellingPrice = null,
        ?float  $costPrice    = null,
        string  $sourceType   = 'Manual',
        ?int    $sourceId     = null
    ): void {
        $change = $stockAfter - $stockBefore;

        // Skip a no-op to avoid polluting the log.
        if (abs($change) < 0.0001) return;

        $request = request();
        $user    = Auth::user();
        $agent   = $request->userAgent() ?? '';

        DB::connection('tenant')
            ->table('retail_inventory_logs')
            ->insert([
                // ── Core references ───────────────────────────────────────
                'product_id'          => $baseProductId,
                'branch_id'           => $branchId,

                // ── Stock movement ────────────────────────────────────────
                'stock_before'        => $stockBefore,
                'stock_after'         => $stockAfter,
                'stock_change'        => $change,

                // ── Price snapshot ────────────────────────────────────────
                // Passed in from the call site so the value is frozen at the
                // moment of the action — immune to future price changes.
                'selling_price'       => $sellingPrice ?? 0,
                'cost_price'          => $costPrice    ?? 0,

                // ── Operation classification ──────────────────────────────
                'operation_type'      => $operationType,

                // ── Source reference ──────────────────────────────────────
                'source_type'         => $sourceType,
                'source_id'           => $sourceId,

                // ── Reason ────────────────────────────────────────────────
                'action_reason'       => $reason,

                // ── User identity snapshot ────────────────────────────────
                'user_id'             => $user->id,
                'user_full_name'      => $user->name   ?? null,
                'user_email'          => $user->email  ?? null,
                'user_role'           => $user->role   ?? null,

                // ── Device & session fingerprint ──────────────────────────
                'user_device_details' => $agent,
                'ip_address'          => $request->ip(),
                'device_type'         => $this->parseDeviceType($agent),
                'browser'             => $this->parseBrowser($agent),
                'operating_system'    => $this->parseOS($agent),
                'session_id'          => session()->getId(),

                // ── Date & time ───────────────────────────────────────────
                'log_date'            => now()->toDateString(),
                'log_time'            => now()->toTimeString(),
            ]);
    }

    // ── Search base products ──────────────────────────────────────────────

    public function searchBaseproducts(Request $request)
    {
        $products = DB::connection('tenant')
            ->table('retail_base_products')
            ->where('is_product', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'unit', 'supplier', 'selling_price', 'cost_price']);

        return response()->json(['products' => $products]);
    }

    // ── Upsert ────────────────────────────────────────────────────────────

    public function upsertBranchproduct(Request $request)
    {
        $request->validate([
            'branch_id'            => 'required|integer|exists:tenant.branches,id',
            'base_product_id'      => 'required|integer|exists:tenant.retail_base_products,id',
            'selling_price'        => 'nullable|numeric|min:0',
            'cost_price'           => 'nullable|numeric|min:0',
            'stock_quantity'       => 'nullable|numeric|min:0',
            'reorder_point'        => 'nullable|numeric|min:0',
            'reorder_quantity'     => 'nullable|numeric|min:0',
            'max_stock'            => 'nullable|numeric|min:0',
            'primary_barcode'      => 'nullable|string|max:100',
            'batch_number'         => 'nullable|string|max:100',
            'expiry_date'          => 'nullable|date',
            'track_stock'          => 'nullable|boolean',
            'allow_negative_stock' => 'nullable|boolean',
            'is_active'            => 'nullable|boolean',
        ]);

        // Resolve the base product once — needed for price fallbacks and snapshots.
        $base = $this->fetchBaseProduct((int) $request->base_product_id);

        // Fall back to base product price when no selling_price is submitted.
        $sellPrice = ($request->selling_price !== null && $request->selling_price !== '')
            ? (float) $request->selling_price
            : (float) ($base->selling_price ?? 0);

        $costPrice = ($request->cost_price !== null && $request->cost_price !== '')
            ? (float) $request->cost_price
            : (float) ($base->cost_price ?? 0);

        $existing = DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('branch_id',       $request->branch_id)
            ->where('base_product_id', $request->base_product_id)
            ->first();

        $newQty = (float) ($request->stock_quantity ?? 0);

        $sharedData = [
            'selling_price'        => $sellPrice,
            'cost_price'           => ($request->cost_price !== null && $request->cost_price !== '') ? $request->cost_price : null,
            'reorder_point'        => $request->reorder_point    ?? 0,
            'reorder_quantity'     => ($request->reorder_quantity !== null && $request->reorder_quantity !== '') ? $request->reorder_quantity : null,
            'max_stock'            => ($request->max_stock        !== null && $request->max_stock        !== '') ? $request->max_stock        : null,
            'primary_barcode'      => $request->primary_barcode  ? trim($request->primary_barcode) : null,
            'batch_number'         => $request->batch_number     ? trim($request->batch_number)    : null,
            'expiry_date'          => $request->expiry_date      ?: null,
            'track_stock'          => (int) ($request->track_stock          ?? 1),
            'allow_negative_stock' => (int) ($request->allow_negative_stock ?? 0),
            'is_active'            => (int) ($request->is_active            ?? 1),
            'updated_at'           => now(),
        ];

        if ($existing) {
            $oldQty    = (float) $existing->stock_quantity;
            $mergedQty = $oldQty + $newQty;

            $sharedData['stock_quantity'] = $mergedQty;

            DB::connection('tenant')
                ->table('retail_branch_products')
                ->where('id', $existing->id)
                ->update($sharedData);

            $branchProductId = $existing->id;

            $this->logStockChange(
                baseProductId: (int) $request->base_product_id,
                branchId:      (int) $request->branch_id,
                stockBefore:   $oldQty,
                stockAfter:    $mergedQty,
                operationType: $newQty >= 0.0001 ? 'StockDelivery' : 'Adjustment',
                reason:        $newQty >= 0.0001
                    ? 'Stock increased via add-to-branch (added ' . $newQty . ' to existing ' . $oldQty . ')'
                    : 'Product re-added to branch (stock unchanged)',
                sellingPrice:  $sellPrice,
                costPrice:     $costPrice,
            );

        } else {
            $insertData = array_merge($sharedData, [
                'branch_id'       => $request->branch_id,
                'base_product_id' => $request->base_product_id,
                'stock_quantity'  => $newQty,
                'created_at'      => now(),
            ]);

            $branchProductId = DB::connection('tenant')
                ->table('retail_branch_products')
                ->insertGetId($insertData);

            $this->logStockChange(
                baseProductId: (int) $request->base_product_id,
                branchId:      (int) $request->branch_id,
                stockBefore:   0,
                stockAfter:    $newQty,
                operationType: 'OpeningStock',
                reason:        'Product added to branch'
                    . ($newQty > 0 ? ' with opening stock of ' . $newQty : ' (zero opening stock)'),
                sellingPrice:  $sellPrice,
                costPrice:     $costPrice,
            );
        }

        if ($branchProductId) {
            $bp = $this->fetchBranchProduct($branchProductId);
            return response()->json([
                'success' => $existing ? 'Branch product updated.' : 'Product added to branch successfully.',
                'status'  => 201,
                'product' => $this->formatBranchProduct($bp),
            ]);
        }

        return response()->json(['error' => 'Failed to save branch product.', 'status' => 500]);
    }

    // ── Update ────────────────────────────────────────────────────────────

    public function updateBranchproduct(Request $request)
    {
        $request->validate([
            'id'                   => 'required|integer|exists:tenant.retail_branch_products,id',
            'selling_price'        => 'nullable|numeric|min:0',
            'cost_price'           => 'nullable|numeric|min:0',
            'stock_quantity'       => 'nullable|numeric',
            'reorder_point'        => 'nullable|numeric|min:0',
            'reorder_quantity'     => 'nullable|numeric|min:0',
            'max_stock'            => 'nullable|numeric|min:0',
            'primary_barcode'      => 'nullable|string|max:100',
            'batch_number'         => 'nullable|string|max:100',
            'expiry_date'          => 'nullable|date',
            'track_stock'          => 'nullable|boolean',
            'allow_negative_stock' => 'nullable|boolean',
            'is_active'            => 'nullable|boolean',
        ]);

        $current = DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('id', $request->id)
            ->first();

        if (!$current) {
            return response()->json(['error' => 'Branch product not found.', 'status' => 404]);
        }

        $base = $this->fetchBaseProduct((int) $current->base_product_id);

        $sellPrice = ($request->selling_price !== null && $request->selling_price !== '')
            ? (float) $request->selling_price
            : (float) ($base->selling_price ?? 0);

        $costPrice = ($request->cost_price !== null && $request->cost_price !== '')
            ? (float) $request->cost_price
            : (float) ($base->cost_price ?? 0);

        $oldQty = (float) $current->stock_quantity;
        $newQty = $request->stock_quantity !== null ? (float) $request->stock_quantity : $oldQty;

        $data = [
            'selling_price'        => $sellPrice,
            'cost_price'           => ($request->cost_price !== null && $request->cost_price !== '') ? $request->cost_price : null,
            'stock_quantity'       => $newQty,
            'reorder_point'        => $request->reorder_point   ?? 0,
            'reorder_quantity'     => ($request->reorder_quantity !== null && $request->reorder_quantity !== '') ? $request->reorder_quantity : null,
            'max_stock'            => ($request->max_stock       !== null && $request->max_stock       !== '') ? $request->max_stock       : null,
            'primary_barcode'      => $request->primary_barcode ? trim($request->primary_barcode) : null,
            'batch_number'         => $request->batch_number    ? trim($request->batch_number)    : null,
            'expiry_date'          => $request->expiry_date     ?: null,
            'track_stock'          => (int) ($request->track_stock          ?? 1),
            'allow_negative_stock' => (int) ($request->allow_negative_stock ?? 0),
            'is_active'            => (int) ($request->is_active            ?? 1),
            'updated_at'           => now(),
        ];

        DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('id', $request->id)
            ->update($data);

        $this->logStockChange(
            baseProductId: (int) $current->base_product_id,
            branchId:      (int) $current->branch_id,
            stockBefore:   $oldQty,
            stockAfter:    $newQty,
            operationType: 'Adjustment',
            reason:        'Manual stock update via branch product edit',
            sellingPrice:  $sellPrice,
            costPrice:     $costPrice,
        );

        $bp = $this->fetchBranchProduct((int) $request->id);
        return response()->json([
            'success' => 'Branch product updated successfully.',
            'status'  => 201,
            'product' => $this->formatBranchProduct($bp),
        ]);
    }

    // ── Delete ────────────────────────────────────────────────────────────

    public function deleteBranchproduct(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:tenant.retail_branch_products,id',
        ]);

        $current = DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('id', $request->id)
            ->first();

        if (!$current) {
            return response()->json(['error' => 'Branch product not found.', 'status' => 404]);
        }

        // Resolve prices for the snapshot before the row is deleted.
        $base = $this->fetchBaseProduct((int) $current->base_product_id);

        $this->logStockChange(
            baseProductId: (int) $current->base_product_id,
            branchId:      (int) $current->branch_id,
            stockBefore:   (float) $current->stock_quantity,
            stockAfter:    0,
            operationType: 'WriteOff',
            reason:        'Product removed from branch (stock zeroed)',
            sellingPrice:  (float) ($current->selling_price ?? $base->selling_price ?? 0),
            costPrice:     (float) ($current->cost_price    ?? $base->cost_price    ?? 0),
        );

        DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('id', $request->id)
            ->delete();

        return response()->json([
            'success' => 'Product removed from branch successfully.',
            'status'  => 201,
        ]);
    }

    // ── Bulk delete ───────────────────────────────────────────────────────

    public function bulkDeleteBranchproducts(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|integer|exists:tenant.retail_branch_products,id',
        ]);

        $rows = DB::connection('tenant')
            ->table('retail_branch_products')
            ->whereIn('id', $request->ids)
            ->get();

        // Fetch all needed base products in one query to avoid N+1.
        $baseMap = $this->fetchBaseProductsMap(
            $rows->pluck('base_product_id')->unique()->toArray()
        );

        foreach ($rows as $row) {
            $base = $baseMap[$row->base_product_id] ?? null;

            $this->logStockChange(
                baseProductId: (int) $row->base_product_id,
                branchId:      (int) $row->branch_id,
                stockBefore:   (float) $row->stock_quantity,
                stockAfter:    0,
                operationType: 'WriteOff',
                reason:        'Product bulk-removed from branch (stock zeroed)',
                sellingPrice:  (float) ($row->selling_price ?? $base->selling_price ?? 0),
                costPrice:     (float) ($row->cost_price    ?? $base->cost_price    ?? 0),
            );
        }

        $deleted = DB::connection('tenant')
            ->table('retail_branch_products')
            ->whereIn('id', $request->ids)
            ->delete();

        if ($deleted > 0) {
            return response()->json([
                'success' => $deleted . ' product' . ($deleted > 1 ? 's' : '') . ' removed from branch successfully.',
                'status'  => 201,
            ]);
        }

        return response()->json(['error' => 'No branch products found.', 'status' => 404]);
    }

    // ── Bulk activate / deactivate ────────────────────────────────────────

    public function bulkStatusBranchproducts(Request $request)
    {
        $request->validate([
            'ids'       => 'required|array',
            'ids.*'     => 'required|integer|exists:tenant.retail_branch_products,id',
            'is_active' => 'required|boolean',
        ]);

        DB::connection('tenant')
            ->table('retail_branch_products')
            ->whereIn('id', $request->ids)
            ->update([
                'is_active'  => (int) $request->is_active,
                'updated_at' => now(),
            ]);

        $branchRows = DB::connection('tenant')
            ->table('retail_branch_products')
            ->whereIn('id', $request->ids)
            ->get();

        $baseMap = $this->fetchBaseProductsMap(
            $branchRows->pluck('base_product_id')->unique()->toArray()
        );

        $formatted = $branchRows->map(function ($row) use ($baseMap) {
            $base   = $baseMap[$row->base_product_id] ?? (object) [];
            $merged = $this->mergeWithBase($row, $base);
            return $this->formatBranchProduct($merged);
        })->values()->toArray();

        $label = $request->is_active ? 'activated' : 'deactivated';
        $count = count($formatted);

        return response()->json([
            'success'  => $count . ' product' . ($count > 1 ? 's' : '') . ' ' . $label . ' successfully.',
            'status'   => 201,
            'products' => $formatted,
        ]);
    }


    public function showShopvaluesOverview()
    {
        return view('operations.retail.shopvalues_overview');
    }

    public function showShopvaluesMovement()
    {
        return view('operations.retail.shopvalues_movement');
    }

    public function getShopvalueMovement(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:tenant.branches,id',
        ]);

        $branchId = (int) $request->branch_id;
        $today    = now()->toDateString();
        $from     = now()->subMonths(3)->toDateString();

        $logs = DB::connection('tenant')
            ->table('retail_inventory_logs as ril')
            ->join('retail_branch_products as rbp', function ($join) use ($branchId) {
                $join->on('rbp.base_product_id', '=', 'ril.product_id')
                     ->where('rbp.branch_id', '=', $branchId);
            })
            ->where('ril.branch_id', $branchId)
            ->whereBetween('ril.log_date', [$from, $today])
            ->select(
                'ril.log_date',
                'ril.stock_change',
                DB::raw('ril.selling_price as unit_price')   // ← snapshot, not rbp
            )
            ->get();

        $byDate = [];
        foreach ($logs as $log) {
            $d      = $log->log_date;
            $change = (float) $log->stock_change;
            $price  = (float) $log->unit_price;
            $val    = $change * $price;

            if (!isset($byDate[$d])) {
                $byDate[$d] = ['added' => 0.0, 'removed' => 0.0];
            }
            if ($change > 0) {
                $byDate[$d]['added'] += $val;
            } else {
                $byDate[$d]['removed'] += abs($val);
            }
        }

        $currentShopValue = (float) DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('branch_id', $branchId)
            ->selectRaw('COALESCE(SUM(selling_price * stock_quantity), 0) as total')
            ->value('total');

        $netSincePeriodStart = 0.0;
        foreach ($byDate as $bkt) {
            $netSincePeriodStart += ($bkt['added'] - $bkt['removed']);
        }

        $periodOpeningValue = max(0, $currentShopValue - $netSincePeriodStart);

        $rows         = [];
        $runningValue = $periodOpeningValue;
        $totalAdded   = 0.0;
        $totalRemoved = 0.0;

        $cursor = new \DateTime($from);
        $end    = new \DateTime($today);

        while ($cursor <= $end) {
            $dateStr = $cursor->format('Y-m-d');
            $dayData = $byDate[$dateStr] ?? null;

            $added   = $dayData ? $dayData['added']  : 0.0;
            $removed = $dayData ? $dayData['removed'] : 0.0;

            $isFirst = ($dateStr === $from);
            $isLast  = ($dateStr === $today);

            if ($added > 0 || $removed > 0 || $isFirst || $isLast) {
                $closingValue = $runningValue + $added - $removed;

                $rows[] = [
                    'date'          => $dateStr,
                    'opening_value' => round($runningValue, 2),
                    'value_added'   => round($added, 2),
                    'value_removed' => round($removed, 2),
                    'closing_value' => round(max(0, $closingValue), 2),
                    'net_change'    => round($closingValue - $runningValue, 2),
                ];

                $totalAdded   += $added;
                $totalRemoved += $removed;
            }

            $runningValue += $added - $removed;
            $cursor->modify('+1 day');
        }

        $totals = [
            'opening_value' => round($periodOpeningValue, 2),
            'value_added'   => round($totalAdded, 2),
            'value_removed' => round($totalRemoved, 2),
            'closing_value' => round(max(0, $currentShopValue), 2),
            'net_change'    => round($currentShopValue - $periodOpeningValue, 2),
        ];

        return response()->json([
            'status' => 200,
            'rows'   => $rows,
            'totals' => $totals,
        ]);
    }

    public function getMovementData(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:tenant.branches,id',
        ]);

        $branchId = (int) $request->branch_id;
        $today    = now()->toDateString();
        $from     = now()->subMonths(3)->toDateString();

        $logs = DB::connection('tenant')
            ->table('retail_inventory_logs as ril')
            ->join('retail_branch_products as rbp', function ($join) use ($branchId) {
                $join->on('rbp.base_product_id', '=', 'ril.product_id')
                     ->where('rbp.branch_id', '=', $branchId);
            })
            ->where('ril.branch_id', $branchId)
            ->whereBetween('ril.log_date', [$from, $today])
            ->select(
                'ril.log_date',
                'ril.stock_change',
                DB::raw('ril.selling_price as unit_price')   // ← snapshot, not rbp
            )
            ->get();

        $byDate = [];
        foreach ($logs as $log) {
            $d      = $log->log_date;
            $change = (float) $log->stock_change;
            $price  = (float) $log->unit_price;
            $val    = $change * $price;

            if (!isset($byDate[$d])) {
                $byDate[$d] = ['added' => 0.0, 'removed' => 0.0];
            }
            if ($change > 0) {
                $byDate[$d]['added'] += $val;
            } else {
                $byDate[$d]['removed'] += abs($val);
            }
        }

        $currentShopValue = (float) DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('branch_id', $branchId)
            ->selectRaw('COALESCE(SUM(selling_price * stock_quantity), 0) as total')
            ->value('total');

        $netSincePeriodStart = 0.0;
        foreach ($byDate as $bkt) {
            $netSincePeriodStart += ($bkt['added'] - $bkt['removed']);
        }
        $periodOpeningValue = max(0.0, $currentShopValue - $netSincePeriodStart);

        $rows         = [];
        $runningValue = $periodOpeningValue;
        $totalAdded   = 0.0;
        $totalRemoved = 0.0;

        $cursor = new \DateTime($from);
        $end    = new \DateTime($today);

        while ($cursor <= $end) {
            $dateStr = $cursor->format('Y-m-d');
            $dayData = $byDate[$dateStr] ?? null;

            $added   = $dayData ? $dayData['added']  : 0.0;
            $removed = $dayData ? $dayData['removed'] : 0.0;

            $isFirst = ($dateStr === $from);
            $isLast  = ($dateStr === $today);

            if ($added > 0 || $removed > 0 || $isFirst || $isLast) {
                $closingValue = max(0.0, $runningValue + $added - $removed);

                $rows[] = [
                    'date'          => $dateStr,
                    'opening_value' => round($runningValue, 2),
                    'value_added'   => round($added, 2),
                    'value_removed' => round($removed, 2),
                    'closing_value' => round($closingValue, 2),
                    'net_change'    => round($closingValue - $runningValue, 2),
                ];

                $totalAdded   += $added;
                $totalRemoved += $removed;
            }

            $runningValue += ($added - $removed);
            $cursor->modify('+1 day');
        }

        $totals = [
            'opening_value' => round($periodOpeningValue, 2),
            'value_added'   => round($totalAdded, 2),
            'value_removed' => round($totalRemoved, 2),
            'closing_value' => round(max(0, $currentShopValue), 2),
            'net_change'    => round($currentShopValue - $periodOpeningValue, 2),
        ];

        return response()->json([
            'status' => 200,
            'rows'   => $rows,
            'totals' => $totals,
        ]);
    }

    public function getAuditLog(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:tenant.branches,id',
            'date'      => 'required|date',
            'type'      => 'required|in:added,removed',
        ]);

        $branchId = (int) $request->branch_id;
        $date     = $request->date;
        $isAdd    = ($request->type === 'added');

        $query = DB::connection('tenant')
            ->table('retail_inventory_logs as ril')
            ->join('retail_base_products as bp', 'bp.id', '=', 'ril.product_id')
            ->leftJoin('users as u', 'u.id', '=', 'ril.user_id')
            ->where('ril.branch_id', $branchId)
            ->where('ril.log_date', $date)
            ->where('ril.stock_change', $isAdd ? '>' : '<', 0)
            ->select(
                'ril.id',
                'ril.stock_before',
                'ril.stock_after',
                'ril.stock_change',
                'ril.selling_price',            // ← snapshot from log row
                'ril.action_reason',
                'ril.log_time',
                'bp.name as product_name',
                'bp.code as product_code',
                DB::raw('ABS(ril.stock_change) * ril.selling_price as value_change'),
                DB::raw("CONCAT(u.first_name, ' ', u.last_name) as user_name")
            )
            ->orderBy('ril.log_time')
            ->get();

        $entries = $query->map(function ($row) {
            return [
                'product_name'  => $row->product_name,
                'product_code'  => $row->product_code,
                'unit_price'    => (float) $row->selling_price,
                'stock_before'  => (float) $row->stock_before,
                'stock_change'  => (float) $row->stock_change,
                'stock_after'   => (float) $row->stock_after,
                'value_change'  => (float) $row->stock_change * (float) $row->selling_price,
                'action_reason' => $row->action_reason,
                'log_time'      => $row->log_time,
                'user_name'     => trim($row->user_name) ?: 'System',
            ];
        })->values()->toArray();

        $totalUnits = array_sum(array_column($entries, 'stock_change'));
        $totalValue = array_sum(array_column($entries, 'value_change'));
        $products   = array_unique(array_column($entries, 'product_name'));

        return response()->json([
            'status'  => 200,
            'entries' => $entries,
            'summary' => [
                'entry_count'   => count($entries),
                'product_count' => count($products),
                'total_units'   => $totalUnits,
                'total_value'   => $totalValue,
            ],
        ]);
    }
}