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

    /**
     * Write one row to retail_inventory_logs whenever stock changes.
     */
    private function logStockChange(
        int    $baseProductId,
        int    $branchId,
        float  $stockBefore,
        float  $stockAfter,
        string $reason
    ): void {
        $change = $stockAfter - $stockBefore;

        if (abs($change) < 0.0001) return;

        $request = request();

        DB::connection('tenant')
            ->table('retail_inventory_logs')
            ->insert([
                'product_id'          => $baseProductId,
                'branch_id'           => $branchId,
                'stock_before'        => $stockBefore,
                'stock_after'         => $stockAfter,
                'stock_change'        => $change,
                'action_reason'       => $reason,
                'user_id'             => Auth::id(),
                'user_device_details' => $request->userAgent(),
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

        // Fall back to base product price when no selling_price is submitted
        $sellPrice = $request->selling_price;
        if ($sellPrice === null || $sellPrice === '') {
            $base      = $this->fetchBaseProduct((int) $request->base_product_id);
            $sellPrice = $base->selling_price ?? 0;
        }

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
            $mergedQty = $oldQty + $newQty;   // accumulate submitted qty on top of existing stock

            $sharedData['stock_quantity'] = $mergedQty;

            DB::connection('tenant')
                ->table('retail_branch_products')
                ->where('id', $existing->id)
                ->update($sharedData);

            $branchProductId = $existing->id;

            $this->logStockChange(
                (int) $request->base_product_id,
                (int) $request->branch_id,
                $oldQty,
                $mergedQty,
                $newQty >= 0.0001
                    ? 'Stock increased via add-to-branch (added ' . $newQty . ' to existing ' . $oldQty . ')'
                    : 'Product re-added to branch (stock unchanged)'
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
                (int) $request->base_product_id,
                (int) $request->branch_id,
                0,
                $newQty,
                'Product added to branch' . ($newQty > 0 ? ' with opening stock of ' . $newQty : ' (zero opening stock)')
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

        $sellPrice = $request->selling_price;
        if ($sellPrice === null || $sellPrice === '') {
            $base      = $this->fetchBaseProduct((int) $current->base_product_id);
            $sellPrice = $base->selling_price ?? 0;
        }

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
            (int) $current->base_product_id,
            (int) $current->branch_id,
            $oldQty,
            $newQty,
            'Manual stock update via branch product edit'
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

        $this->logStockChange(
            (int) $current->base_product_id,
            (int) $current->branch_id,
            (float) $current->stock_quantity,
            0,
            'Product removed from branch (stock zeroed)'
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

        foreach ($rows as $row) {
            $this->logStockChange(
                (int) $row->base_product_id,
                (int) $row->branch_id,
                (float) $row->stock_quantity,
                0,
                'Product bulk-removed from branch (stock zeroed)'
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
}