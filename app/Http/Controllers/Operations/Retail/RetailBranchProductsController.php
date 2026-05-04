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

    // ─────────────────────────────────────────────────────────────────────────
    //  PRIVATE HELPER — shape a branch product row for every JSON response.
    //
    //  Price resolution (null = use base product price):
    //    selling_price  = null → use bp.default_selling_price
    //    cost_price     = null → use bp.default_cost_price
    //
    //  We expose BOTH the raw branch price (may be null) AND the resolved
    //  effective price, plus a flag so the UI knows which source is active.
    // ─────────────────────────────────────────────────────────────────────────

    private function formatBranchProduct($bp): array
    {
        // Resolve effective prices: null branch price → fall back to base product
        $effectiveSell = $bp->selling_price ?? $bp->bp_sell ?? null;
        $effectiveCost = $bp->cost_price    ?? $bp->bp_cost ?? null;

        return [
            'id'                      => $bp->id,
            'row'                     => 'row' . $bp->id,
            'branch_id'               => $bp->branch_id,
            'base_product_id'         => $bp->base_product_id,
            // ── Base product fields ──
            'name'                    => $bp->name,
            'internal_code'           => $bp->internal_code,
            'unit_of_measure'         => $bp->unit_of_measure,
            'brand'                   => $bp->brand         ?? null,
            'category'                => $bp->category      ?? null,
            'is_product'              => (int) ($bp->is_product ?? 1),
            'is_vat_exempt_by_nature' => (int) ($bp->is_vat_exempt_by_nature ?? 0),
            'mra_tax_rate_id'         => $bp->mra_tax_rate_id ?? null,
            // ── Base product default prices (for display / fallback) ──
            'bp_sell'                 => $bp->bp_sell ?? null,
            'bp_cost'                 => $bp->bp_cost ?? null,
            // ── Branch-specific raw prices (may be null → uses base price) ──
            'selling_price'           => $bp->selling_price    ?? null,
            'cost_price'              => $bp->cost_price       ?? null,
            // ── Effective (resolved) prices used for display ──
            'effective_sell'          => $effectiveSell,
            'effective_cost'          => $effectiveCost,
            // ── Price source flags: true = branch has its own price ──
            'sell_is_branch'          => ($bp->selling_price !== null),
            'cost_is_branch'          => ($bp->cost_price    !== null),
            // ── Other branch fields ──
            'wholesale_price'         => $bp->wholesale_price    ?? null,
            'currency'                => $bp->currency           ?? 'MWK',
            'mra_tax_rate_id_override'=> $bp->mra_tax_rate_id_override ?? null,
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

    // ─────────────────────────────────────────────────────────────────────────
    //  PRIVATE HELPER — fetch a single branch product with base product joined
    // ─────────────────────────────────────────────────────────────────────────

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
                'bp.mra_tax_rate_id',
                'bp.category',
                'bp.brand',
                'bp.is_product',
                'bp.is_vat_exempt_by_nature',
                'bp.default_selling_price as bp_sell',
                'bp.default_cost_price    as bp_cost'
            )
            ->first();
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  SEARCH BASE PRODUCTS
    // ─────────────────────────────────────────────────────────────────────────

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
                'mra_tax_rate_id',
            ]);

        return response()->json(['products' => $products]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  UPSERT — add or update a branch product
    //
    //  selling_price and cost_price are NULLABLE.
    //  null = inherit from base product (bp.default_selling_price / bp.default_cost_price)
    //  When the user sends an empty string or "null", we store NULL in the DB.
    //
    //  On INSERT: stock_quantity taken from form (opening stock).
    //  On UPDATE: stock_quantity is NOT changed here (use Edit modal).
    // ─────────────────────────────────────────────────────────────────────────

    public function upsertBranchproduct(Request $request)
    {
        $request->validate([
            'branch_id'               => 'required|integer|exists:tenant.branches,id',
            'base_product_id'         => 'required|integer|exists:tenant.retail_base_products,id',
            'selling_price'           => 'nullable|numeric|min:0',
            'cost_price'              => 'nullable|numeric|min:0',
            'wholesale_price'         => 'nullable|numeric|min:0',
            'stock_quantity'          => 'nullable|numeric|min:0',
            'reorder_point'           => 'nullable|numeric|min:0',
            'reorder_quantity'        => 'nullable|numeric|min:0',
            'max_stock'               => 'nullable|numeric|min:0',
            'primary_barcode'         => 'nullable|string|max:100',
            'batch_number'            => 'nullable|string|max:100',
            'expiry_date'             => 'nullable|date',
            'mra_tax_rate_id_override'=> 'nullable|string|in:A,B,C,E,TL',
            'track_stock'             => 'nullable|boolean',
            'allow_negative_stock'    => 'nullable|boolean',
            'is_active'               => 'nullable|boolean',
            'is_pinned_on_pos'        => 'nullable|boolean',
        ]);

        // Null out empty price strings — NULL means "use base product price"
        $sellPrice = ($request->selling_price !== null && $request->selling_price !== '') ? $request->selling_price : null;
        $costPrice = ($request->cost_price    !== null && $request->cost_price    !== '') ? $request->cost_price    : null;

        $existing = DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('branch_id',       $request->branch_id)
            ->where('base_product_id', $request->base_product_id)
            ->first();

        $sharedData = [
            'selling_price'            => $sellPrice,
            'cost_price'               => $costPrice,
            'wholesale_price'          => ($request->wholesale_price !== null && $request->wholesale_price !== '') ? $request->wholesale_price : null,
            'reorder_point'            => $request->reorder_point  ?? 0,
            'reorder_quantity'         => ($request->reorder_quantity !== null && $request->reorder_quantity !== '') ? $request->reorder_quantity : null,
            'max_stock'                => ($request->max_stock !== null && $request->max_stock !== '') ? $request->max_stock : null,
            'primary_barcode'          => $request->primary_barcode ? trim($request->primary_barcode) : null,
            'batch_number'             => $request->batch_number    ? trim($request->batch_number)    : null,
            'expiry_date'              => $request->expiry_date     ? $request->expiry_date            : null,
            'mra_tax_rate_id_override' => ($request->mra_tax_rate_id_override !== null && $request->mra_tax_rate_id_override !== '')
                                            ? strtoupper(trim($request->mra_tax_rate_id_override))
                                            : null,
            'track_stock'              => (int) ($request->track_stock         ?? 1),
            'allow_negative_stock'     => (int) ($request->allow_negative_stock ?? 0),
            'is_active'                => (int) ($request->is_active            ?? 1),
            'is_pinned_on_pos'         => (int) ($request->is_pinned_on_pos     ?? 0),
            'updated_at'               => now(),
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

    // ─────────────────────────────────────────────────────────────────────────
    //  UPDATE — edit an existing branch product
    //
    //  selling_price and cost_price can be cleared to NULL (= use base price).
    //  Sending an empty string clears the branch override.
    // ─────────────────────────────────────────────────────────────────────────

    public function updateBranchproduct(Request $request)
    {
        $request->validate([
            'id'                      => 'required|integer|exists:tenant.retail_branch_products,id',
            'selling_price'           => 'nullable|numeric|min:0',
            'cost_price'              => 'nullable|numeric|min:0',
            'wholesale_price'         => 'nullable|numeric|min:0',
            'stock_quantity'          => 'nullable|numeric',
            'reorder_point'           => 'nullable|numeric|min:0',
            'reorder_quantity'        => 'nullable|numeric|min:0',
            'max_stock'               => 'nullable|numeric|min:0',
            'primary_barcode'         => 'nullable|string|max:100',
            'batch_number'            => 'nullable|string|max:100',
            'expiry_date'             => 'nullable|date',
            'mra_tax_rate_id_override'=> 'nullable|string|in:A,B,C,E,TL',
            'track_stock'             => 'nullable|boolean',
            'allow_negative_stock'    => 'nullable|boolean',
            'is_active'               => 'nullable|boolean',
            'is_pinned_on_pos'        => 'nullable|boolean',
            'pos_sort_order'          => 'nullable|integer|min:0',
        ]);

        // Null out empty price strings — NULL means "use base product price"
        $sellPrice = ($request->selling_price !== null && $request->selling_price !== '') ? $request->selling_price : null;
        $costPrice = ($request->cost_price    !== null && $request->cost_price    !== '') ? $request->cost_price    : null;

        $data = [
            'selling_price'            => $sellPrice,
            'cost_price'               => $costPrice,
            'wholesale_price'          => ($request->wholesale_price !== null && $request->wholesale_price !== '') ? $request->wholesale_price : null,
            'stock_quantity'           => $request->stock_quantity  ?? 0,
            'reorder_point'            => $request->reorder_point   ?? 0,
            'reorder_quantity'         => ($request->reorder_quantity !== null && $request->reorder_quantity !== '') ? $request->reorder_quantity : null,
            'max_stock'                => ($request->max_stock !== null && $request->max_stock !== '') ? $request->max_stock : null,
            'primary_barcode'          => $request->primary_barcode ? trim($request->primary_barcode) : null,
            'batch_number'             => $request->batch_number    ? trim($request->batch_number)    : null,
            'expiry_date'              => $request->expiry_date     ? $request->expiry_date            : null,
            'mra_tax_rate_id_override' => ($request->mra_tax_rate_id_override !== null && $request->mra_tax_rate_id_override !== '')
                                            ? strtoupper(trim($request->mra_tax_rate_id_override))
                                            : null,
            'track_stock'              => (int) ($request->track_stock          ?? 1),
            'allow_negative_stock'     => (int) ($request->allow_negative_stock  ?? 0),
            'is_active'                => (int) ($request->is_active             ?? 1),
            'is_pinned_on_pos'         => (int) ($request->is_pinned_on_pos      ?? 0),
            'pos_sort_order'           => (int) ($request->pos_sort_order        ?? 0),
            'updated_at'               => now(),
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

    // ─────────────────────────────────────────────────────────────────────────
    //  SINGLE DELETE
    // ─────────────────────────────────────────────────────────────────────────

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

    // ─────────────────────────────────────────────────────────────────────────
    //  BULK DELETE
    // ─────────────────────────────────────────────────────────────────────────

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

    // ─────────────────────────────────────────────────────────────────────────
    //  BULK STATUS
    // ─────────────────────────────────────────────────────────────────────────

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
                'bp.mra_tax_rate_id', 'bp.category', 'bp.brand',
                'bp.is_product', 'bp.is_vat_exempt_by_nature',
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

    // ─────────────────────────────────────────────────────────────────────────
    //  BULK VAT OVERRIDE
    // ─────────────────────────────────────────────────────────────────────────

    public function bulkTaxBranchproducts(Request $request)
    {
        $request->validate([
            'ids'                     => 'required|array',
            'ids.*'                   => 'required|integer|exists:tenant.retail_branch_products,id',
            'mra_tax_rate_id_override'=> 'nullable|string|in:A,B,C,E,TL',
        ]);

        $override = ($request->mra_tax_rate_id_override !== null && $request->mra_tax_rate_id_override !== '')
            ? strtoupper(trim($request->mra_tax_rate_id_override))
            : null;

        DB::connection('tenant')
            ->table('retail_branch_products')
            ->whereIn('id', $request->ids)
            ->update([
                'mra_tax_rate_id_override' => $override,
                'updated_at'               => now(),
            ]);

        $rows = DB::connection('tenant')
            ->table('retail_branch_products as rbp')
            ->join('retail_base_products as bp', 'bp.id', '=', 'rbp.base_product_id')
            ->whereIn('rbp.id', $request->ids)
            ->select(
                'rbp.*',
                'bp.name', 'bp.internal_code', 'bp.unit_of_measure',
                'bp.mra_tax_rate_id', 'bp.category', 'bp.brand',
                'bp.is_product', 'bp.is_vat_exempt_by_nature',
                'bp.default_selling_price as bp_sell',
                'bp.default_cost_price    as bp_cost'
            )
            ->get();

        $count     = $rows->count();
        $formatted = $rows->map(fn($bp) => $this->formatBranchProduct($bp))->values()->toArray();

        $msg = $override
            ? 'VAT override set to "' . $override . '" for ' . $count . ' product' . ($count > 1 ? 's' : '') . '.'
            : 'VAT override cleared for ' . $count . ' product' . ($count > 1 ? 's' : '') . ' (now inheriting from base product).';

        return response()->json([
            'success'  => $msg,
            'status'   => 201,
            'products' => $formatted,
        ]);
    }
}