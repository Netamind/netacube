<?php
namespace App\Http\Controllers\Operations\Retail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;

class RetailBranchProductsController extends Controller
{

    public function showBranchproductsView()
    {
        return view('operations.retail.branchproducts');
    }

    private function formatBranchProduct($bp): array
    {
        $effectiveSell = $bp->selling_price ?? $bp->bp_sell ?? null;
        $effectiveCost = $bp->cost_price    ?? $bp->bp_cost ?? null;

        return [
            'id'                      => $bp->id,
            'row'                     => 'row' . $bp->id,
            'branch_id'               => $bp->branch_id,
            'base_product_id'         => $bp->base_product_id,
            'name'                    => $bp->name,
            'internal_code'           => $bp->internal_code,
            'unit_of_measure'         => $bp->unit_of_measure,
            'brand'                   => $bp->brand         ?? null,
            'category'                => $bp->category      ?? null,
            'bp_sell'                 => $bp->bp_sell ?? null,
            'bp_cost'                 => $bp->bp_cost ?? null,
            'selling_price'           => $bp->selling_price    ?? null,
            'cost_price'              => $bp->cost_price       ?? null,
            'effective_sell'          => $effectiveSell,
            'effective_cost'          => $effectiveCost,
            'sell_is_branch'          => ($bp->selling_price !== null),
            'cost_is_branch'          => ($bp->cost_price    !== null),
            'wholesale_price'         => $bp->wholesale_price    ?? null,
            'currency'                => $bp->currency           ?? 'MWK',
            'primary_barcode'         => $bp->primary_barcode    ?? null,
            'batch_number'            => $bp->batch_number       ?? null,
            'expiry_date'             => $bp->expiry_date        ?? null,
            'stock_quantity'          => $bp->stock_quantity     ?? 0,
            'reorder_point'           => $bp->reorder_point      ?? 0,
            'reorder_quantity'        => $bp->reorder_quantity   ?? null,
            'max_stock'               => $bp->max_stock          ?? null,
            'track_stock'             => (int) ($bp->track_stock ?? 1),
            'is_active'               => (int) ($bp->is_active   ?? 1),
            'allow_negative_stock'    => (int) ($bp->allow_negative_stock ?? 0),
            'is_pinned_on_pos'        => (int) ($bp->is_pinned_on_pos    ?? 0),
            'pos_sort_order'          => $bp->pos_sort_order     ?? 0,
        ];
    }

    private function fetchBranchProduct(int $id)
    {
        return DB::connection('tenant')
            ->table('retail_branch_products as rbp')
            ->join('retail_base_products as bp', 'bp.id', '=', 'rbp.base_product_id')
            ->where('rbp.id', $id)
            ->select(
                'rbp.*',
                'bp.name',
                'bp.internal_code',
                'bp.unit_of_measure',
                'bp.category',
                'bp.brand',
                'bp.default_selling_price as bp_sell',
                'bp.default_cost_price    as bp_cost'
            )
            ->first();
    }

    public function searchBaseproducts(Request $request)
    {
        $products = DB::connection('tenant')
            ->table('retail_base_products')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'internal_code',
                'unit_of_measure',
                'category',
                'default_selling_price',
                'default_cost_price',
            ]);

        return response()->json(['products' => $products]);
    }

    public function upsertBranchproduct(Request $request)
    {
        $request->validate([
            'branch_id'            => 'required|integer|exists:tenant.branches,id',
            'base_product_id'      => 'required|integer|exists:tenant.retail_base_products,id',
            'selling_price'        => 'nullable|numeric|min:0',
            'cost_price'           => 'nullable|numeric|min:0',
            'wholesale_price'      => 'nullable|numeric|min:0',
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
            'is_pinned_on_pos'     => 'nullable|boolean',
        ]);

        $sellPrice = ($request->selling_price !== null && $request->selling_price !== '') ? $request->selling_price : null;
        $costPrice = ($request->cost_price    !== null && $request->cost_price    !== '') ? $request->cost_price    : null;

        $existing = DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('branch_id',       $request->branch_id)
            ->where('base_product_id', $request->base_product_id)
            ->first();

        $sharedData = [
            'selling_price'        => $sellPrice,
            'cost_price'           => $costPrice,
            'wholesale_price'      => ($request->wholesale_price !== null && $request->wholesale_price !== '') ? $request->wholesale_price : null,
            'reorder_point'        => $request->reorder_point  ?? 0,
            'reorder_quantity'     => ($request->reorder_quantity !== null && $request->reorder_quantity !== '') ? $request->reorder_quantity : null,
            'max_stock'            => ($request->max_stock !== null && $request->max_stock !== '') ? $request->max_stock : null,
            'primary_barcode'      => $request->primary_barcode ? trim($request->primary_barcode) : null,
            'batch_number'         => $request->batch_number    ? trim($request->batch_number)    : null,
            'expiry_date'          => $request->expiry_date     ? $request->expiry_date            : null,
            'track_stock'          => (int) ($request->track_stock         ?? 1),
            'allow_negative_stock' => (int) ($request->allow_negative_stock ?? 0),
            'is_active'            => (int) ($request->is_active            ?? 1),
            'is_pinned_on_pos'     => (int) ($request->is_pinned_on_pos     ?? 0),
            'updated_at'           => now(),
        ];

        if ($existing) {
            DB::connection('tenant')
                ->table('retail_branch_products')
                ->where('id', $existing->id)
                ->update($sharedData);

            $branchProductId = $existing->id;
        } else {
            $insertData = array_merge($sharedData, [
                'branch_id'       => $request->branch_id,
                'base_product_id' => $request->base_product_id,
                'stock_quantity'  => $request->stock_quantity ?? 0,
                'created_at'      => now(),
            ]);

            $branchProductId = DB::connection('tenant')
                ->table('retail_branch_products')
                ->insertGetId($insertData);
        }

        if ($branchProductId) {
            $bp = $this->fetchBranchProduct($branchProductId);

            return response()->json([
                'success' => $existing
                    ? 'Branch product updated successfully.'
                    : 'Product added to branch successfully.',
                'status'  => 201,
                'product' => $this->formatBranchProduct($bp),
            ]);
        }

        return response()->json(['error' => 'Failed to save branch product.', 'status' => 500]);
    }

    public function updateBranchproduct(Request $request)
    {
        $request->validate([
            'id'                   => 'required|integer|exists:tenant.retail_branch_products,id',
            'selling_price'        => 'nullable|numeric|min:0',
            'cost_price'           => 'nullable|numeric|min:0',
            'wholesale_price'      => 'nullable|numeric|min:0',
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
            'is_pinned_on_pos'     => 'nullable|boolean',
            'pos_sort_order'       => 'nullable|integer|min:0',
        ]);

        $sellPrice = ($request->selling_price !== null && $request->selling_price !== '') ? $request->selling_price : null;
        $costPrice = ($request->cost_price    !== null && $request->cost_price    !== '') ? $request->cost_price    : null;

        $data = [
            'selling_price'        => $sellPrice,
            'cost_price'           => $costPrice,
            'wholesale_price'      => ($request->wholesale_price !== null && $request->wholesale_price !== '') ? $request->wholesale_price : null,
            'stock_quantity'       => $request->stock_quantity  ?? 0,
            'reorder_point'        => $request->reorder_point   ?? 0,
            'reorder_quantity'     => ($request->reorder_quantity !== null && $request->reorder_quantity !== '') ? $request->reorder_quantity : null,
            'max_stock'            => ($request->max_stock !== null && $request->max_stock !== '') ? $request->max_stock : null,
            'primary_barcode'      => $request->primary_barcode ? trim($request->primary_barcode) : null,
            'batch_number'         => $request->batch_number    ? trim($request->batch_number)    : null,
            'expiry_date'          => $request->expiry_date     ? $request->expiry_date            : null,
            'track_stock'          => (int) ($request->track_stock          ?? 1),
            'allow_negative_stock' => (int) ($request->allow_negative_stock  ?? 0),
            'is_active'            => (int) ($request->is_active             ?? 1),
            'is_pinned_on_pos'     => (int) ($request->is_pinned_on_pos      ?? 0),
            'pos_sort_order'       => (int) ($request->pos_sort_order        ?? 0),
            'updated_at'           => now(),
        ];

        $updated = DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('id', $request->id)
            ->update($data);

        if ($updated !== false) {
            $bp = $this->fetchBranchProduct((int) $request->id);

            return response()->json([
                'success' => 'Branch product updated successfully.',
                'status'  => 201,
                'product' => $this->formatBranchProduct($bp),
            ]);
        }

        return response()->json(['error' => 'Branch product not found or no changes made.', 'status' => 409]);
    }

    public function deleteBranchproduct(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:tenant.retail_branch_products,id',
        ]);

        $deleted = DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('id', $request->id)
            ->delete();

        if ($deleted) {
            return response()->json([
                'success' => 'Product removed from branch successfully.',
                'status'  => 201,
            ]);
        }

        return response()->json(['error' => 'Branch product not found.', 'status' => 404]);
    }

    public function bulkDeleteBranchproducts(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|integer|exists:tenant.retail_branch_products,id',
        ]);

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

        $rows = DB::connection('tenant')
            ->table('retail_branch_products as rbp')
            ->join('retail_base_products as bp', 'bp.id', '=', 'rbp.base_product_id')
            ->whereIn('rbp.id', $request->ids)
            ->select(
                'rbp.*',
                'bp.name', 'bp.internal_code', 'bp.unit_of_measure',
                'bp.category', 'bp.brand',
                'bp.default_selling_price as bp_sell',
                'bp.default_cost_price    as bp_cost'
            )
            ->get();

        $label     = $request->is_active ? 'activated' : 'deactivated';
        $count     = $rows->count();
        $formatted = $rows->map(fn($bp) => $this->formatBranchProduct($bp))->values()->toArray();

        return response()->json([
            'success'  => $count . ' product' . ($count > 1 ? 's' : '') . ' ' . $label . ' successfully.',
            'status'   => 201,
            'products' => $formatted,
        ]);
    }
}

