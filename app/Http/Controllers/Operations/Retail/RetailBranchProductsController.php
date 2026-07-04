<?php

namespace App\Http\Controllers\Operations\Retail;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use DB;

class RetailBranchProductsController extends Controller
{
    /**
     * How many rows we touch per DB chunk for bulk/CSV operations.
     * Keeps each query small so we never approach PHP/SQL execution
     * timeouts even on large selections or large CSV files.
     */
    private const CHUNK_SIZE = 200;

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
            ->first();
    }

    private function fetchBaseProductsMap(array $baseProductIds): array
    {
        if (empty($baseProductIds)) return [];

        $map = [];
        foreach (array_chunk(array_unique($baseProductIds), self::CHUNK_SIZE) as $idsChunk) {
            $rows = DB::connection('tenant')
                ->table('retail_base_products')
                ->whereIn('id', $idsChunk)
                ->get(['id', 'name', 'code', 'unit', 'supplier', 'selling_price', 'cost_price']);
            foreach ($rows as $row) {
                $map[$row->id] = $row;
            }
        }
        return $map;
    }

    private function mergeWithBase($branchRow, $base): object
    {
        $merged             = (array) $branchRow;
        $merged['name']     = $base->name         ?? null;
        $merged['code']     = $base->code          ?? null;
        $merged['unit']     = $base->unit          ?? 'Each';
        $merged['supplier'] = $base->supplier      ?? null;
        $merged['bp_sell']  = $base->selling_price ?? null;
        $merged['bp_cost']  = $base->cost_price    ?? null;
        return (object) $merged;
    }

    private function formatBranchProduct($bp): array
    {
        $bpSell = $bp->bp_sell ?? null;
        $bpCost = $bp->bp_cost ?? null;

        $sellIsBranch = ($bp->selling_price !== null);
        $costIsBranch = ($bp->cost_price    !== null);

        return [
            'id'                   => $bp->id,
            'row'                  => 'row' . $bp->id,
            'branch_id'            => $bp->branch_id,
            'base_product_id'      => $bp->base_product_id,
            'name'                 => $bp->name    ?? null,
            'code'                 => $bp->code     ?? null,
            'unit'                 => $bp->unit     ?? 'Each',
            'supplier'             => $bp->supplier ?? null,
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

    private function fetchBranchProductsFormatted(array $ids): array
    {
        if (empty($ids)) return [];

        $rowsById = [];
        foreach (array_chunk(array_unique($ids), self::CHUNK_SIZE) as $idsChunk) {
            $rows = DB::connection('tenant')
                ->table('retail_branch_products')
                ->whereIn('id', $idsChunk)
                ->get();
            foreach ($rows as $row) {
                $rowsById[$row->id] = $row;
            }
        }

        $baseMap = $this->fetchBaseProductsMap(
            array_values(array_unique(array_map(fn($r) => $r->base_product_id, $rowsById)))
        );

        $out = [];
        foreach ($ids as $id) {
            if (!isset($rowsById[$id])) continue;
            $row    = $rowsById[$id];
            $base   = $baseMap[$row->base_product_id] ?? (object) [];
            $merged = $this->mergeWithBase($row, $base);
            $out[]  = $this->formatBranchProduct($merged);
        }
        return $out;
    }

    // ── Category helpers ──────────────────────────────────────────────────

    private function getBranchCategory(int $branchId): ?int
    {
        return DB::connection('tenant')->table('branches')->where('id', $branchId)->value('category');
    }

    private function findBaseProductInCategory(string $name, int $categoryId)
    {
        return DB::connection('tenant')
            ->table('retail_base_products as bp')
            ->join('suppliers as s', 's.name', '=', 'bp.supplier')
            ->where('s.category', $categoryId)
            ->whereRaw('LOWER(TRIM(bp.name)) = ?', [strtolower(trim($name))])
            ->select('bp.*')
            ->first();
    }

    private function findBaseProductsInCategoryBulk(array $names, int $categoryId): array
    {
        if (empty($names)) return [];

        $lookup = [];
        foreach (array_chunk(array_unique($names), self::CHUNK_SIZE) as $namesChunk) {
            $loweredChunk = array_map(fn($n) => strtolower(trim($n)), $namesChunk);

            $rows = DB::connection('tenant')
                ->table('retail_base_products as bp')
                ->join('suppliers as s', 's.name', '=', 'bp.supplier')
                ->where('s.category', $categoryId)
                ->whereIn(DB::raw('LOWER(TRIM(bp.name))'), $loweredChunk)
                ->select('bp.*')
                ->get();

            foreach ($rows as $row) {
                $lookup[strtolower(trim($row->name))] = $row;
            }
        }
        return $lookup;
    }

    private function purifyNumber($value): ?float
    {
        if ($value === null) return null;
        // Strip commas/spaces used as thousand separators, keep digits and dot
        $value = preg_replace('/[^0-9.\-]/', '', (string) $value);
        return $value === '' ? null : (float) $value;
    }

    // ── UA parsers ────────────────────────────────────────────────────────

    private function parseDeviceType(string $ua): string
    {
        $ua = strtolower($ua);
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad'))            return 'tablet';
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android')
            || str_contains($ua, 'iphone'))                                        return 'mobile';
        return 'desktop';
    }

    private function parseBrowser(string $ua): string
    {
        if (str_contains($ua, 'Edg'))                                              return 'Edge';
        if (str_contains($ua, 'OPR') || str_contains($ua, 'Opera'))               return 'Opera';
        if (str_contains($ua, 'Chrome'))                                           return 'Chrome';
        if (str_contains($ua, 'Firefox'))                                          return 'Firefox';
        if (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome'))          return 'Safari';
        if (str_contains($ua, 'MSIE')   || str_contains($ua, 'Trident'))          return 'IE';
        return 'Other';
    }

    private function parseOS(string $ua): string
    {
        if (str_contains($ua, 'Windows NT'))                                       return 'Windows';
        if (str_contains($ua, 'Mac OS X'))                                         return 'macOS';
        if (str_contains($ua, 'Android'))                                          return 'Android';
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad'))             return 'iOS';
        if (str_contains($ua, 'Linux'))                                            return 'Linux';
        return 'Other';
    }

    private function logStockChange(
        int    $baseProductId,
        int    $branchId,
        float  $stockBefore,
        float  $stockAfter,
        string $operationType,
        string $reason,
        ?float $sellingPrice = null,
        ?float $costPrice    = null,
        string $sourceType   = 'Manual',
        ?int   $sourceId     = null
    ): void {
        $change = $stockAfter - $stockBefore;
        if (abs($change) < 0.0001) return;

        $request = request();
        $user    = Auth::user();
        $agent   = $request->userAgent() ?? '';

        DB::connection('tenant')->table('retail_inventory_logs')->insert([
            'product_id'          => $baseProductId,
            'branch_id'           => $branchId,
            'stock_before'        => $stockBefore,
            'stock_after'         => $stockAfter,
            'stock_change'        => $change,
            'selling_price'       => $sellingPrice ?? 0,
            'cost_price'          => $costPrice    ?? 0,
            'operation_type'      => $operationType,
            'source_type'         => $sourceType,
            'source_id'           => $sourceId,
            'action_reason'       => $reason,
            'user_id'             => $user->id,
            'user_full_name'      => $user->name  ?? null,
            'user_email'          => $user->email ?? null,
            'user_role'           => $user->role  ?? null,
            'user_device_details' => $agent,
            'ip_address'          => $request->ip(),
            'device_type'         => $this->parseDeviceType($agent),
            'browser'             => $this->parseBrowser($agent),
            'operating_system'    => $this->parseOS($agent),
            'session_id'          => session()->getId(),
            'log_date'            => now()->toDateString(),
            'log_time'            => now()->toTimeString(),
        ]);
    }

    private function logStockChangesBulk(array $entries): void
    {
        if (empty($entries)) return;

        $request = request();
        $user    = Auth::user();
        $agent   = $request->userAgent() ?? '';
        $ip      = $request->ip();
        $deviceType = $this->parseDeviceType($agent);
        $browser    = $this->parseBrowser($agent);
        $os         = $this->parseOS($agent);
        $sessionId  = session()->getId();
        $today      = now()->toDateString();
        $time       = now()->toTimeString();

        $rows = [];
        foreach ($entries as $e) {
            $change = $e['stock_after'] - $e['stock_before'];
            if (abs($change) < 0.0001) continue;

            $rows[] = [
                'product_id'          => $e['base_product_id'],
                'branch_id'           => $e['branch_id'],
                'stock_before'        => $e['stock_before'],
                'stock_after'         => $e['stock_after'],
                'stock_change'        => $change,
                'selling_price'       => $e['selling_price'] ?? 0,
                'cost_price'          => $e['cost_price']    ?? 0,
                'operation_type'      => $e['operation_type'],
                'source_type'         => $e['source_type'] ?? 'Manual',
                'source_id'           => $e['source_id']   ?? null,
                'action_reason'       => $e['reason'],
                'user_id'             => $user->id,
                'user_full_name'      => $user->name  ?? null,
                'user_email'          => $user->email ?? null,
                'user_role'           => $user->role  ?? null,
                'user_device_details' => $agent,
                'ip_address'          => $ip,
                'device_type'         => $deviceType,
                'browser'             => $browser,
                'operating_system'    => $os,
                'session_id'          => $sessionId,
                'log_date'            => $today,
                'log_time'            => $time,
            ];
        }

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $rowsChunk) {
            DB::connection('tenant')->table('retail_inventory_logs')->insert($rowsChunk);
        }
    }

    private function logPriceChange(
        int    $baseProductId,
        int    $branchId,
        float  $oldPrice,
        float  $newPrice,
        string $productName,
        ?string $productCode,
        string $productUnit,
        ?string $reason = null
    ): void {
        if (round($oldPrice, 2) === round($newPrice, 2)) return;

        $branchName = DB::connection('tenant')->table('branches')->where('id', $branchId)->value('name');

        DB::connection('tenant')->table('retail_price_changes')->insert([
            'base_product_id' => $baseProductId,
            'branch_id'       => $branchId,
            'changed_by'      => Auth::id(),
            'product_name'    => $productName,
            'product_code'    => $productCode,
            'product_unit'    => $productUnit,
            'branch_name'     => $branchName,
            'old_price'       => $oldPrice,
            'new_price'       => $newPrice,
            'reason'          => $reason,
            'change_date'     => now()->toDateString(),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }

    private function logPriceChangesBulk(array $entries, int $branchId): void
    {
        if (empty($entries)) return;

        $branchName = DB::connection('tenant')->table('branches')->where('id', $branchId)->value('name');
        $changedBy  = Auth::id();
        $today      = now()->toDateString();
        $now        = now();

        $rows = [];
        foreach ($entries as $e) {
            if (round($e['old_price'], 2) === round($e['new_price'], 2)) continue;

            $rows[] = [
                'base_product_id' => $e['base_product_id'],
                'branch_id'       => $branchId,
                'changed_by'      => $changedBy,
                'product_name'    => $e['product_name'],
                'product_code'    => $e['product_code'] ?? null,
                'product_unit'    => $e['product_unit'] ?? 'Each',
                'branch_name'     => $branchName,
                'old_price'       => $e['old_price'],
                'new_price'       => $e['new_price'],
                'reason'          => $e['reason'] ?? null,
                'change_date'     => $today,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];
        }

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $rowsChunk) {
            DB::connection('tenant')->table('retail_price_changes')->insert($rowsChunk);
        }
    }

    // ── Suppliers filtered by branch category ─────────────────────────────

    public function listSuppliersForBranch(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:tenant.branches,id',
        ]);

        $categoryId = $this->getBranchCategory((int) $request->branch_id);
        if (!$categoryId) {
            return response()->json(['suppliers' => []]);
        }

        $suppliers = DB::connection('tenant')
            ->table('suppliers')
            ->where('status', 'active')
            ->where('category', $categoryId)
            ->orderBy('name')
            ->get(['id', 'name', 'category']);

        return response()->json(['suppliers' => $suppliers]);
    }

    public function listSuppliersForDropdown()
    {
        $suppliers = DB::connection('tenant')
            ->table('suppliers')
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'category']);

        return response()->json(['suppliers' => $suppliers]);
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

    // ── Insert base product ───────────────────────────────────────────────

    public function insertBaseproduct(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'selling_price' => 'required|numeric|min:0',
            'cost_price'    => 'nullable|numeric|min:0',
            'unit'          => 'nullable|string|max:50',
            'code'          => 'nullable|string|max:100',
            'supplier'      => 'nullable|string|max:255',
            'is_product'    => 'nullable|boolean',
        ]);

        $id = DB::connection('tenant')->table('retail_base_products')->insertGetId([
            'name'          => trim($request->name),
            'selling_price' => $request->selling_price,
            'cost_price'    => $request->cost_price ?? null,
            'unit'          => $request->unit ?? 'Each',
            'code'          => $request->code ? trim($request->code) : null,
            'supplier'      => $request->supplier ?? null,
            'is_product'    => (int) ($request->is_product ?? 1),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $product = DB::connection('tenant')->table('retail_base_products')->where('id', $id)->first();

        return response()->json(['status' => 201, 'product' => $product]);
    }

    // ── Update base product ───────────────────────────────────────────────

    public function updateBaseproduct(Request $request)
    {
        $request->validate([
            'id'                => 'required|integer|exists:tenant.retail_base_products,id',
            'name'              => 'required|string|max:255',
            'selling_price'     => 'required|numeric|min:0',
            'cost_price'        => 'nullable|numeric|min:0',
            'unit'              => 'nullable|string|max:50',
            'code'              => 'nullable|string|max:100',
            'supplier'          => 'nullable|string|max:255',
            'branch_product_id' => 'nullable|integer',
        ]);

        DB::connection('tenant')->table('retail_base_products')
            ->where('id', $request->id)
            ->update([
                'name'          => trim($request->name),
                'selling_price' => $request->selling_price,
                'cost_price'    => $request->cost_price ?? null,
                'unit'          => $request->unit ?? 'Each',
                'code'          => $request->code ? trim($request->code) : null,
                'supplier'      => $request->supplier ?? null,
                'updated_at'    => now(),
            ]);

        $branchProduct = null;
        if ($request->branch_product_id) {
            $bp = $this->fetchBranchProduct((int) $request->branch_product_id);
            if ($bp) $branchProduct = $this->formatBranchProduct($bp);
        }

        return response()->json([
            'status'  => 201,
            'success' => 'Base product updated successfully.',
            'product' => $branchProduct,
        ]);
    }

    // ── Upsert ────────────────────────────────────────────────────────────

    public function upsertBranchproduct(Request $request)
    {
        $request->validate([
            'branch_id'            => 'required|integer|exists:tenant.branches,id',
            'base_product_id'      => 'required|integer|exists:tenant.retail_base_products,id',
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

        $base   = $this->fetchBaseProduct((int) $request->base_product_id);
        $newQty = (float) ($request->stock_quantity ?? 0);

        $existing = DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('branch_id',       $request->branch_id)
            ->where('base_product_id', $request->base_product_id)
            ->first();

        if ($existing) {
            $oldQty    = (float) $existing->stock_quantity;
            $mergedQty = $oldQty + $newQty;

            DB::connection('tenant')->table('retail_branch_products')
                ->where('id', $existing->id)
                ->update([
                    'stock_quantity' => $mergedQty,
                    'updated_at'     => now(),
                ]);

            $logSell = (float) ($existing->selling_price ?? $base->selling_price ?? 0);
            $logCost = (float) ($existing->cost_price    ?? $base->cost_price    ?? 0);

            $this->logStockChange(
                baseProductId: (int) $request->base_product_id,
                branchId:      (int) $request->branch_id,
                stockBefore:   $oldQty,
                stockAfter:    $mergedQty,
                operationType: $newQty >= 0.0001 ? 'StockDelivery' : 'Adjustment',
                reason:        $newQty >= 0.0001
                    ? 'Stock increased via add-to-branch (added ' . $newQty . ' to existing ' . $oldQty . ')'
                    : 'Product re-added to branch (stock unchanged)',
                sellingPrice:  $logSell,
                costPrice:     $logCost,
            );

            $bp = $this->fetchBranchProduct($existing->id);
            return response()->json([
                'success' => 'Branch product updated.',
                'status'  => 201,
                'product' => $this->formatBranchProduct($bp),
            ]);
        }

        $branchProductId = DB::connection('tenant')
            ->table('retail_branch_products')
            ->insertGetId([
                'branch_id'            => $request->branch_id,
                'base_product_id'      => $request->base_product_id,
                'selling_price'        => null,
                'cost_price'           => null,
                'stock_quantity'       => $newQty,
                'reorder_point'        => $request->reorder_point ?? 0,
                'reorder_quantity'     => ($request->reorder_quantity !== null && $request->reorder_quantity !== '') ? $request->reorder_quantity : null,
                'max_stock'            => ($request->max_stock !== null && $request->max_stock !== '') ? $request->max_stock : null,
                'primary_barcode'      => $request->primary_barcode ? trim($request->primary_barcode) : null,
                'batch_number'         => $request->batch_number    ? trim($request->batch_number)    : null,
                'expiry_date'          => $request->expiry_date     ?: null,
                'track_stock'          => (int) ($request->track_stock          ?? 1),
                'allow_negative_stock' => (int) ($request->allow_negative_stock ?? 0),
                'is_active'            => (int) ($request->is_active            ?? 1),
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

        $this->logStockChange(
            baseProductId: (int) $request->base_product_id,
            branchId:      (int) $request->branch_id,
            stockBefore:   0,
            stockAfter:    $newQty,
            operationType: 'OpeningStock',
            reason:        'Product added to branch (using base catalogue price)'
                . ($newQty > 0 ? ' with quantity of ' . $newQty : ' (zero quantity)'),
            sellingPrice:  (float) ($base->selling_price ?? 0),
            costPrice:     (float) ($base->cost_price    ?? 0),
        );

        $bp = $this->fetchBranchProduct($branchProductId);
        return response()->json([
            'success' => 'Product added to branch successfully.',
            'status'  => 201,
            'product' => $this->formatBranchProduct($bp),
        ]);
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
            'price_change_reason'  => 'nullable|string|max:255',
            'stock_change_reason'  => 'nullable|string|max:500',
        ]);

        $current = DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('id', $request->id)
            ->first();

        if (!$current) {
            return response()->json(['error' => 'Branch product not found.', 'status' => 404]);
        }

        $base = $this->fetchBaseProduct((int) $current->base_product_id);

        // Selling price for the log snapshot: use the new branch price if set,
        // otherwise fall back to the base catalogue price.
        $sellPrice = ($request->selling_price !== null && $request->selling_price !== '')
            ? (float) $request->selling_price
            : (float) ($base->selling_price ?? 0);

        $costPrice = ($request->cost_price !== null && $request->cost_price !== '')
            ? (float) $request->cost_price
            : (float) ($base->cost_price ?? 0);

        $oldQty       = (float) $current->stock_quantity;
        $newQty       = $request->stock_quantity !== null ? (float) $request->stock_quantity : $oldQty;
        $oldSellPrice = (float) ($current->selling_price ?? $base->selling_price ?? 0);

        // NULL means "use base catalogue" — only store a value when the user
        // explicitly chose the branch-override price source.
        $storeSellPrice = ($request->selling_price !== null && $request->selling_price !== '')
            ? (float) $request->selling_price
            : null;

        $data = [
            'selling_price'        => $storeSellPrice,
            'cost_price'           => ($request->cost_price !== null && $request->cost_price !== '') ? $request->cost_price : null,
            'stock_quantity'       => $newQty,
            'reorder_point'        => $request->reorder_point  ?? 0,
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

        DB::connection('tenant')->table('retail_branch_products')
            ->where('id', $request->id)->update($data);

        // Build the stock-change log reason: use the user-supplied reason when
        // provided, otherwise fall back to a sensible default.
        $stockReason = ($request->stock_change_reason && trim($request->stock_change_reason) !== '')
            ? trim($request->stock_change_reason)
            : 'Manual stock update via branch product edit';

        $this->logStockChange(
            baseProductId: (int) $current->base_product_id,
            branchId:      (int) $current->branch_id,
            stockBefore:   $oldQty,
            stockAfter:    $newQty,
            operationType: 'Adjustment',
            reason:        $stockReason,
            sellingPrice:  $sellPrice,
            costPrice:     $costPrice,
        );

        $this->logPriceChange(
            baseProductId: (int) $current->base_product_id,
            branchId:      (int) $current->branch_id,
            oldPrice:      $oldSellPrice,
            newPrice:      $sellPrice,
            productName:   $base->name ?? '',
            productCode:   $base->code ?? null,
            productUnit:   $base->unit ?? 'Each',
            reason:        $request->price_change_reason ? trim($request->price_change_reason) : $stockReason,
        );

        $bp = $this->fetchBranchProduct((int) $request->id);
        return response()->json([
            'success' => 'Branch product updated successfully.',
            'status'  => 201,
            'product' => $this->formatBranchProduct($bp),
        ]);
    }

    // ── Bulk set branch prices ─────────────────────────────────────────────

    public function bulkSetBranchPrices(Request $request)
    {
        $request->validate([
            'items'         => 'required|array',
            'items.*.id'    => 'required|integer|exists:tenant.retail_branch_products,id',
            'items.*.price' => 'nullable|numeric|min:0',
        ]);

        $priceById = [];
        foreach ($request->items as $item) {
            $priceById[(int) $item['id']] = (isset($item['price']) && $item['price'] !== '')
                ? (float) $item['price']
                : null;
        }

        $allIds     = array_keys($priceById);
        $updatedIds = [];

        foreach (array_chunk($allIds, self::CHUNK_SIZE) as $idsChunk) {
            $currentRows = DB::connection('tenant')
                ->table('retail_branch_products')
                ->whereIn('id', $idsChunk)
                ->get()
                ->keyBy('id');

            if ($currentRows->isEmpty()) continue;

            $baseMap = $this->fetchBaseProductsMap(
                $currentRows->pluck('base_product_id')->unique()->toArray()
            );

            $priceLogEntries = [];

            foreach ($currentRows as $id => $current) {
                $price        = $priceById[$id];
                $base         = $baseMap[$current->base_product_id] ?? null;
                $oldSellPrice = (float) ($current->selling_price ?? $base->selling_price ?? 0);
                $newSellPrice = $price ?? (float) ($base->selling_price ?? 0);

                if ($price === null) {
                    continue;
                }

                $priceLogEntries[] = [
                    'base_product_id' => (int) $current->base_product_id,
                    'product_name'    => $base->name ?? '',
                    'product_code'    => $base->code ?? null,
                    'product_unit'    => $base->unit ?? 'Each',
                    'old_price'       => $oldSellPrice,
                    'new_price'       => $newSellPrice,
                    'reason'          => 'Branch price set via bulk action',
                ];

                DB::connection('tenant')->table('retail_branch_products')
                    ->where('id', $id)
                    ->update(['selling_price' => $price, 'updated_at' => now()]);

                $updatedIds[] = $id;
            }

            $branchIdForLog = $currentRows->first()->branch_id ?? null;
            if ($branchIdForLog && !empty($priceLogEntries)) {
                $this->logPriceChangesBulk($priceLogEntries, (int) $branchIdForLog);
            }
        }

        $updated = $this->fetchBranchProductsFormatted($updatedIds);

        return response()->json([
            'status'   => 201,
            'success'  => count($updated) . ' product price(s) updated.',
            'products' => $updated,
        ]);
    }

    // ── Bulk use base prices ───────────────────────────────────────────────

    public function bulkUseBasePrices(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|integer|exists:tenant.retail_branch_products,id',
        ]);

        $allIds       = array_values(array_unique(array_map('intval', $request->ids)));
        $processedIds = [];

        foreach (array_chunk($allIds, self::CHUNK_SIZE) as $idsChunk) {
            $rows = DB::connection('tenant')
                ->table('retail_branch_products')
                ->whereIn('id', $idsChunk)
                ->get();

            if ($rows->isEmpty()) continue;

            $baseMap = $this->fetchBaseProductsMap(
                $rows->pluck('base_product_id')->unique()->toArray()
            );

            $priceLogEntries = [];
            $branchIdForLog  = null;

            foreach ($rows as $row) {
                $base         = $baseMap[$row->base_product_id] ?? null;
                $oldSellPrice = (float) ($row->selling_price ?? $base->selling_price ?? 0);
                $newSellPrice = (float) ($base->selling_price ?? 0);

                $priceLogEntries[] = [
                    'base_product_id' => (int) $row->base_product_id,
                    'product_name'    => $base->name ?? '',
                    'product_code'    => $base->code ?? null,
                    'product_unit'    => $base->unit ?? 'Each',
                    'old_price'       => $oldSellPrice,
                    'new_price'       => $newSellPrice,
                    'reason'          => 'Reverted to base price via bulk action',
                ];

                $branchIdForLog = $branchIdForLog ?? $row->branch_id;
            }

            DB::connection('tenant')->table('retail_branch_products')
                ->whereIn('id', $idsChunk)
                ->update(['selling_price' => null, 'updated_at' => now()]);

            if ($branchIdForLog) {
                $this->logPriceChangesBulk($priceLogEntries, (int) $branchIdForLog);
            }

            $processedIds = array_merge($processedIds, $idsChunk);
        }

        $formatted = $this->fetchBranchProductsFormatted($processedIds);

        return response()->json([
            'status'   => 201,
            'success'  => count($formatted) . ' product(s) reverted to base prices.',
            'products' => $formatted,
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

        DB::connection('tenant')->table('retail_branch_products')
            ->where('id', $request->id)->delete();

        return response()->json([
            'success' => 'Product removed from branch successfully.',
            'status'  => 201,
        ]);
    }

    // ── Bulk delete ────────────────────────────────────────────────────────

    public function bulkDeleteBranchproducts(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|integer|exists:tenant.retail_branch_products,id',
        ]);

        $allIds        = array_values(array_unique(array_map('intval', $request->ids)));
        $totalDeleted  = 0;

        foreach (array_chunk($allIds, self::CHUNK_SIZE) as $idsChunk) {
            $rows = DB::connection('tenant')
                ->table('retail_branch_products')
                ->whereIn('id', $idsChunk)
                ->get();

            if ($rows->isEmpty()) continue;

            $baseMap = $this->fetchBaseProductsMap(
                $rows->pluck('base_product_id')->unique()->toArray()
            );

            $stockLogEntries = [];
            foreach ($rows as $row) {
                $base = $baseMap[$row->base_product_id] ?? null;
                $stockLogEntries[] = [
                    'base_product_id' => (int) $row->base_product_id,
                    'branch_id'       => (int) $row->branch_id,
                    'stock_before'    => (float) $row->stock_quantity,
                    'stock_after'     => 0,
                    'operation_type'  => 'WriteOff',
                    'reason'          => 'Product bulk-removed from branch (stock zeroed)',
                    'selling_price'   => (float) ($row->selling_price ?? $base->selling_price ?? 0),
                    'cost_price'      => (float) ($row->cost_price    ?? $base->cost_price    ?? 0),
                ];
            }
            $this->logStockChangesBulk($stockLogEntries);

            $deletedInChunk = DB::connection('tenant')
                ->table('retail_branch_products')
                ->whereIn('id', $idsChunk)
                ->delete();

            $totalDeleted += $deletedInChunk;
        }

        if ($totalDeleted > 0) {
            return response()->json([
                'success' => $totalDeleted . ' product' . ($totalDeleted > 1 ? 's' : '') . ' removed from branch successfully.',
                'status'  => 201,
            ]);
        }

        return response()->json(['error' => 'No branch products found.', 'status' => 404]);
    }

    // ── CSV Upload + Import ────────────────────────────────────────────────

    public function uploadBranchproductsCsv(Request $request)
    {
        $request->validate([
            'csv_file'    => 'required|file|mimes:csv,txt|max:5120',
            'branch_id'   => 'required|integer|exists:tenant.branches,id',
            'supplier_id' => 'required|integer|exists:tenant.suppliers,id',
        ]);

        $branchId       = (int) $request->branch_id;
        $branchCategory = $this->getBranchCategory($branchId);
        if (!$branchCategory) {
            return response()->json(['error' => 'This branch has no category configured.', 'status' => 422]);
        }

        $supplier = DB::connection('tenant')->table('suppliers')
            ->where('id', $request->supplier_id)->where('status', 'active')->first();

        if (!$supplier) {
            return response()->json(['error' => 'Selected supplier is invalid or inactive.', 'status' => 422]);
        }

        if ((int) $supplier->category !== (int) $branchCategory) {
            return response()->json([
                'error'  => "\"{$supplier->name}\" does not belong to this branch's category.",
                'status' => 422,
            ]);
        }

        // ── Parse CSV ──────────────────────────────────────────────────────
        $raw = file_get_contents($request->file('csv_file')->getRealPath());
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);

        $lines = array_values(array_filter(explode("\n", $raw), fn($l) => trim($l) !== ''));
        if (count($lines) < 2) {
            return response()->json(['error' => 'CSV is empty or has no data rows.', 'status' => 422]);
        }

        $header = array_map(fn($h) => strtolower(trim($h)), str_getcsv(array_shift($lines)));

        $map = ['name' => null, 'code' => null, 'unit' => null, 'selling_price' => null, 'cost_price' => null, 'quantity' => null];
        foreach ($header as $i => $col) {
            if (in_array($col, ['name', 'product', 'product_name']))            $map['name']          = $i;
            if (in_array($col, ['code', 'sku']))                                $map['code']          = $i;
            if ($col === 'unit')                                                 $map['unit']          = $i;
            if (in_array($col, ['selling_price', 'price', 'sell_price']))       $map['selling_price'] = $i;
            if (in_array($col, ['cost_price', 'cost']))                         $map['cost_price']    = $i;
            if (in_array($col, ['quantity', 'qty', 'stock', 'stock_quantity'])) $map['quantity']      = $i;
        }

        if ($map['name'] === null) {
            return response()->json(['error' => 'CSV must contain a "name" column.', 'status' => 422]);
        }

        $validUnits = ['Each','kg','g','Litre','ml','Box','Carton','Pack','Pair','Dozen','Bag','Bottle','Metre','Service'];

        $clean = [];
        foreach ($lines as $line) {
            $cols = str_getcsv($line);
            $name = trim(strip_tags($cols[$map['name']] ?? ''));
            if ($name === '') continue;

            $unit = $map['unit'] !== null ? trim(strip_tags($cols[$map['unit']] ?? '')) : 'Each';
            $unit = in_array($unit, $validUnits) ? $unit : 'Each';

            $clean[] = [
                'name'          => $name,
                'code'          => $map['code']          !== null ? (trim(strip_tags($cols[$map['code']]          ?? '')) ?: null) : null,
                'unit'          => $unit,
                'selling_price' => $map['selling_price'] !== null ? $this->purifyNumber($cols[$map['selling_price']] ?? null) : null,
                'cost_price'    => $map['cost_price']    !== null ? $this->purifyNumber($cols[$map['cost_price']]    ?? null) : null,
                'quantity'      => $map['quantity']      !== null ? ($this->purifyNumber($cols[$map['quantity']]     ?? null) ?? 0) : 0,
            ];
        }

        if (empty($clean)) {
            return response()->json(['error' => 'No valid rows found in CSV.', 'status' => 422]);
        }

        $result = $this->processCsvRowsChunked($clean, $branchId, $branchCategory, $supplier);

        $processed  = $result['created'] + $result['updated'];
        $total      = count($clean);

        $successMsg = "Import complete — {$processed} of {$total} row(s) saved.";
        if ($result['updated'] > 0) {
            $successMsg .= " ({$result['created']} new, {$result['updated']} stock updated)";
        }

        return response()->json([
            'status'        => 200,
            'success'       => $successMsg,
            'row_count'     => $total,
            'created_count' => $result['created'],
            'updated_count' => $result['updated'],
            'skipped_count' => $result['skipped'],
            'skipped_names' => $result['skipped_names'],
        ]);
    }

    private function processCsvRowsChunked(array $rows, int $branchId, int $branchCategory, $supplier): array
    {
        $created      = 0;
        $updated      = 0;
        $skipped      = 0;
        $skippedNames = [];

        $existingCodes = DB::connection('tenant')
            ->table('retail_base_products')
            ->whereNotNull('code')
            ->pluck('code')
            ->flip()
            ->all();

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $rowsChunk) {
            $names           = array_map(fn($r) => $r['name'], $rowsChunk);
            $baseByLowerName = $this->findBaseProductsInCategoryBulk($names, $branchCategory);

            $newBaseInserts     = [];
            $rowsNeedingNewBase = [];
            foreach ($rowsChunk as $r) {
                $key = strtolower(trim($r['name']));
                if (!isset($baseByLowerName[$key])) {
                    $rowsNeedingNewBase[$key] = $r;
                }
            }

            foreach ($rowsNeedingNewBase as $key => $r) {
                $code = $r['code'];
                if ($code !== null && isset($existingCodes[$code])) {
                    $code = null;
                }
                $newBaseInserts[$key] = [
                    'name'          => $r['name'],
                    'description'   => null,
                    'code'          => $code,
                    'supplier'      => $supplier->name,
                    'unit'          => $r['unit'],
                    'cost_price'    => $r['cost_price'],
                    'selling_price' => $r['selling_price'],
                    'is_product'    => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
                if ($code !== null) {
                    $existingCodes[$code] = true;
                }
            }

            if (!empty($newBaseInserts)) {
                DB::connection('tenant')->table('retail_base_products')->insert(array_values($newBaseInserts));

                $createdRows = DB::connection('tenant')
                    ->table('retail_base_products')
                    ->where('supplier', $supplier->name)
                    ->whereIn(DB::raw('LOWER(TRIM(name))'), array_keys($newBaseInserts))
                    ->get();
                foreach ($createdRows as $cr) {
                    $baseByLowerName[strtolower(trim($cr->name))] = $cr;
                }
            }

            $baseIdsInChunk = [];
            $resolvedRows   = [];
            foreach ($rowsChunk as $r) {
                $key  = strtolower(trim($r['name']));
                $base = $baseByLowerName[$key] ?? null;
                if (!$base) {
                    $skipped++;
                    $skippedNames[] = $r['name'];
                    continue;
                }
                $resolvedRows[]   = ['row' => $r, 'base' => $base];
                $baseIdsInChunk[] = $base->id;
            }
            if (empty($resolvedRows)) continue;

            $existingBranchProducts = DB::connection('tenant')
                ->table('retail_branch_products')
                ->where('branch_id', $branchId)
                ->whereIn('base_product_id', array_unique($baseIdsInChunk))
                ->get()
                ->keyBy('base_product_id');

            $stockLogEntries = [];
            $toInsert        = [];

            foreach ($resolvedRows as $rr) {
                $r    = $rr['row'];
                $base = $rr['base'];
                $qty  = (float) $r['quantity'];

                $existingBp = $existingBranchProducts[$base->id] ?? null;

                if ($existingBp) {
                    $oldQty  = (float) $existingBp->stock_quantity;
                    $newQty  = $oldQty + $qty;
                    $logSell = (float) ($existingBp->selling_price ?? $base->selling_price ?? 0);
                    $logCost = (float) ($existingBp->cost_price    ?? $base->cost_price    ?? 0);

                    DB::connection('tenant')->table('retail_branch_products')
                        ->where('id', $existingBp->id)
                        ->update(['stock_quantity' => $newQty, 'updated_at' => now()]);

                    $stockLogEntries[] = [
                        'base_product_id' => (int) $base->id,
                        'branch_id'       => $branchId,
                        'stock_before'    => $oldQty,
                        'stock_after'     => $newQty,
                        'operation_type'  => 'StockDelivery',
                        'reason'          => "CSV import — added {$qty} to existing branch stock",
                        'selling_price'   => $logSell,
                        'cost_price'      => $logCost,
                    ];
                    $updated++;
                } else {
                    $toInsert[] = [
                        'branch_id'            => $branchId,
                        'base_product_id'      => $base->id,
                        'selling_price'        => null,
                        'cost_price'           => null,
                        'stock_quantity'       => $qty,
                        'reorder_point'        => 0,
                        'track_stock'          => 1,
                        'allow_negative_stock' => 0,
                        'is_active'            => 1,
                        'created_at'           => now(),
                        'updated_at'           => now(),
                        '_log_base_id'         => $base->id,
                        '_log_qty'             => $qty,
                        '_log_sell'            => (float) ($base->selling_price ?? 0),
                        '_log_cost'            => (float) ($base->cost_price    ?? 0),
                    ];
                }
            }

            if (!empty($toInsert)) {
                $insertPayload = array_map(function ($row) {
                    unset($row['_log_base_id'], $row['_log_qty'], $row['_log_sell'], $row['_log_cost']);
                    return $row;
                }, $toInsert);

                DB::connection('tenant')->table('retail_branch_products')->insert($insertPayload);

                foreach ($toInsert as $row) {
                    $stockLogEntries[] = [
                        'base_product_id' => (int) $row['_log_base_id'],
                        'branch_id'       => $branchId,
                        'stock_before'    => 0,
                        'stock_after'     => $row['_log_qty'],
                        'operation_type'  => 'OpeningStock',
                        'reason'          => 'CSV import — product added to branch (using base price)'
                            . ($row['_log_qty'] > 0 ? " with quantity {$row['_log_qty']}" : ''),
                        'selling_price'   => $row['_log_sell'],
                        'cost_price'      => $row['_log_cost'],
                    ];
                    $created++;
                }
            }

            $this->logStockChangesBulk($stockLogEntries);
        }

        return [
            'processed'     => $created + $updated,
            'created'       => $created,
            'updated'       => $updated,
            'skipped'       => $skipped,
            'skipped_names' => $skippedNames,
        ];
    }

    // ── Shop Values ───────────────────────────────────────────────────────

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
            ->select('ril.log_date', 'ril.stock_change', DB::raw('ril.selling_price as unit_price'))
            ->get();

        $byDate = [];
        foreach ($logs as $log) {
            $d = $log->log_date; $change = (float) $log->stock_change; $price = (float) $log->unit_price; $val = $change * $price;
            if (!isset($byDate[$d])) $byDate[$d] = ['added' => 0.0, 'removed' => 0.0];
            if ($change > 0) $byDate[$d]['added'] += $val; else $byDate[$d]['removed'] += abs($val);
        }

        // ── FIX: use branch price if set, otherwise base price. Nothing else. ──
        $currentShopValue = (float) DB::connection('tenant')
            ->table('retail_branch_products as rbp')
            ->join('retail_base_products as bp', 'bp.id', '=', 'rbp.base_product_id')
            ->where('rbp.branch_id', $branchId)
            ->selectRaw('COALESCE(SUM(COALESCE(rbp.selling_price, bp.selling_price) * rbp.stock_quantity), 0) as total')
            ->value('total');

        $netSincePeriodStart = array_sum(array_map(fn($b) => $b['added'] - $b['removed'], $byDate));
        $periodOpeningValue  = max(0, $currentShopValue - $netSincePeriodStart);

        $rows = []; $runningValue = $periodOpeningValue; $totalAdded = 0.0; $totalRemoved = 0.0;
        $cursor = new \DateTime($from); $end = new \DateTime($today);

        while ($cursor <= $end) {
            $dateStr = $cursor->format('Y-m-d');
            $dayData = $byDate[$dateStr] ?? null;
            $added   = $dayData ? $dayData['added']  : 0.0;
            $removed = $dayData ? $dayData['removed'] : 0.0;
            $isFirst = ($dateStr === $from); $isLast = ($dateStr === $today);

            if ($added > 0 || $removed > 0 || $isFirst || $isLast) {
                $closingValue = $runningValue + $added - $removed;
                $rows[]       = ['date' => $dateStr, 'opening_value' => round($runningValue, 2), 'value_added' => round($added, 2), 'value_removed' => round($removed, 2), 'closing_value' => round(max(0, $closingValue), 2), 'net_change' => round($closingValue - $runningValue, 2)];
                $totalAdded  += $added; $totalRemoved += $removed;
            }
            $runningValue += $added - $removed;
            $cursor->modify('+1 day');
        }

        return response()->json(['status' => 200, 'rows' => $rows, 'totals' => ['opening_value' => round($periodOpeningValue, 2), 'value_added' => round($totalAdded, 2), 'value_removed' => round($totalRemoved, 2), 'closing_value' => round(max(0, $currentShopValue), 2), 'net_change' => round($currentShopValue - $periodOpeningValue, 2)]]);
    }

    public function getMovementData(Request $request)
    {
        return $this->getShopvalueMovement($request);
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

        $entries = DB::connection('tenant')
            ->table('retail_inventory_logs as ril')
            ->join('retail_base_products as bp', 'bp.id', '=', 'ril.product_id')
            ->where('ril.branch_id', $branchId)
            ->where('ril.log_date', $date)
            ->where('ril.stock_change', $isAdd ? '>' : '<', 0)
            ->select(
                'ril.stock_before', 'ril.stock_after', 'ril.stock_change',
                'ril.selling_price', 'ril.action_reason', 'ril.log_time',
                'ril.user_full_name',
                'bp.name as product_name', 'bp.code as product_code',
                DB::raw('ABS(ril.stock_change) * ril.selling_price as value_change')
            )
            ->orderBy('ril.log_time')
            ->get()
            ->map(fn($row) => [
                'product_name'  => $row->product_name,
                'product_code'  => $row->product_code,
                'unit_price'    => (float) $row->selling_price,
                'stock_before'  => (float) $row->stock_before,
                'stock_change'  => (float) $row->stock_change,
                'stock_after'   => (float) $row->stock_after,
                'value_change'  => (float) $row->stock_change * (float) $row->selling_price,
                'action_reason' => $row->action_reason,
                'log_time'      => $row->log_time,
                'user_name'     => $row->user_full_name ?: 'System',
            ])->values()->toArray();

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