<?php

namespace App\Http\Controllers\Operations\Retail;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;

class BaseproductsController extends Controller
{
    public function showBaseproductsView()
    {
        return view('operations.retail.baseproducts');
    }

    private function formatProduct($product): array
    {
        return [
            'id'            => $product->id,
            'row'           => 'row' . $product->id,
            'name'          => $product->name,
            'description'   => $product->description,
            'code'          => $product->code,
            'supplier'      => $product->supplier,
            'unit'          => $product->unit,
            'cost_price'    => $product->cost_price,
            'selling_price' => $product->selling_price,
            'is_product'    => (int) $product->is_product,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  INSERT
    // ─────────────────────────────────────────────────────────────────────────

    public function insertBaseproduct(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255|unique:tenant.retail_base_products,name',
            'description'   => 'nullable|string|max:2000',
            'code'          => 'nullable|string|max:100|unique:tenant.retail_base_products,code',
            'supplier'      => 'required|string|max:255',
            'unit'          => 'required|string|max:50',
            'cost_price'    => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
        ], [
            'name.unique'     => 'A product with this name already exists in the base catalogue.',
            'code.unique'     => 'This code (SKU) is already used by another product.',
            'supplier.required' => 'A supplier is required.',
        ]);

        $data = [
            'name'          => trim($request->name),
            'description'   => $request->description ? trim($request->description) : null,
            'code'          => $request->code         ? trim($request->code)        : null,
            'supplier'      => trim($request->supplier),
            'unit'          => trim($request->unit ?? 'Each'),
            'cost_price'    => ($request->cost_price    !== null && $request->cost_price    !== '') ? $request->cost_price    : null,
            'selling_price' => ($request->selling_price !== null && $request->selling_price !== '') ? $request->selling_price : null,
            'is_product'    => 1,
            'created_at'    => now(),
            'updated_at'    => now(),
        ];

        $insertId = DB::connection('tenant')->table('retail_base_products')->insertGetId($data);

        if ($insertId) {
            $product = DB::connection('tenant')
                ->table('retail_base_products')
                ->where('id', $insertId)
                ->first();

            return response()->json([
                'success' => 'Product created successfully.',
                'status'  => 201,
                'product' => $this->formatProduct($product),
            ]);
        }

        return response()->json(['error' => 'Failed to create product.', 'status' => 500]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  UPDATE
    // ─────────────────────────────────────────────────────────────────────────

    public function updateBaseproduct(Request $request)
    {
        $request->validate([
            'id'            => 'required|integer|exists:tenant.retail_base_products,id',
            'name'          => 'required|string|max:255|unique:tenant.retail_base_products,name,' . $request->id,
            'description'   => 'nullable|string|max:2000',
            'code'          => 'nullable|string|max:100|unique:tenant.retail_base_products,code,' . $request->id,
            'supplier'      => 'required|string|max:255',
            'unit'          => 'required|string|max:50',
            'cost_price'    => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'is_product'    => 'nullable|boolean',
        ], [
            'name.unique'       => 'A product with this name already exists in the base catalogue.',
            'code.unique'       => 'This code (SKU) is already used by another product.',
            'supplier.required' => 'A supplier is required.',
        ]);

        $data = [
            'name'          => trim($request->name),
            'description'   => $request->description ? trim($request->description) : null,
            'code'          => $request->code         ? trim($request->code)        : null,
            'supplier'      => trim($request->supplier),
            'unit'          => trim($request->unit ?? 'Each'),
            'cost_price'    => ($request->cost_price    !== null && $request->cost_price    !== '') ? $request->cost_price    : null,
            'selling_price' => ($request->selling_price !== null && $request->selling_price !== '') ? $request->selling_price : null,
            'is_product'    => (int) ($request->is_product ?? 1),
            'updated_at'    => now(),
        ];

        $updated = DB::connection('tenant')
            ->table('retail_base_products')
            ->where('id', $request->id)
            ->update($data);

        if ($updated !== false) {
            $product = DB::connection('tenant')
                ->table('retail_base_products')
                ->where('id', $request->id)
                ->first();

            return response()->json([
                'success' => 'Product updated successfully.',
                'status'  => 201,
                'product' => $this->formatProduct($product),
            ]);
        }

        return response()->json(['error' => 'Product not found or no changes made.', 'status' => 409]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  SINGLE DELETE
    // ─────────────────────────────────────────────────────────────────────────

    public function deleteBaseproduct(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:tenant.retail_base_products,id',
        ]);

        $inUse = DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('base_product_id', $request->id)
            ->exists();

        if ($inUse) {
            return response()->json([
                'error'  => 'This product is assigned to one or more branches. Remove it from all branches first.',
                'status' => 422,
            ]);
        }

        $deleted = DB::connection('tenant')
            ->table('retail_base_products')
            ->where('id', $request->id)
            ->delete();

        if ($deleted) {
            return response()->json(['success' => 'Product deleted successfully.', 'status' => 201]);
        }

        return response()->json(['error' => 'Product not found.', 'status' => 404]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  BULK DELETE
    // ─────────────────────────────────────────────────────────────────────────

    public function bulkDeleteBaseproducts(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|integer|exists:tenant.retail_base_products,id',
        ]);

        $inUseIds = DB::connection('tenant')
            ->table('retail_branch_products')
            ->whereIn('base_product_id', $request->ids)
            ->pluck('base_product_id')
            ->toArray();

        $safeIds = array_diff($request->ids, $inUseIds);

        if (empty($safeIds)) {
            return response()->json([
                'error'  => 'All selected products are assigned to branches and cannot be deleted.',
                'status' => 422,
            ]);
        }

        $deleted = DB::connection('tenant')
            ->table('retail_base_products')
            ->whereIn('id', $safeIds)
            ->delete();

        if ($deleted > 0) {
            $skipped = count($inUseIds);
            $message = 'Selected products deleted successfully.';
            if ($skipped > 0) {
                $message .= ' ' . $skipped . ' product' . ($skipped > 1 ? 's were' : ' was') .
                            ' skipped (assigned to branches).';
            }
            return response()->json(['success' => $message, 'status' => 201]);
        }

        return response()->json(['error' => 'No products found or permission denied.', 'status' => 404]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  BULK STATUS  (is_product)
    // ─────────────────────────────────────────────────────────────────────────

    public function bulkStatusBaseproducts(Request $request)
    {
        $request->validate([
            'ids'        => 'required|array',
            'ids.*'      => 'required|integer|exists:tenant.retail_base_products,id',
            'is_product' => 'required|boolean',
        ]);

        DB::connection('tenant')
            ->table('retail_base_products')
            ->whereIn('id', $request->ids)
            ->update([
                'is_product' => (int) $request->is_product,
                'updated_at' => now(),
            ]);

        $products  = DB::connection('tenant')->table('retail_base_products')->whereIn('id', $request->ids)->get();
        $label     = $request->is_product ? 'marked as Product' : 'marked as Service';
        $count     = $products->count();
        $formatted = $products->map(fn($p) => $this->formatProduct($p))->values()->toArray();

        return response()->json([
            'success'  => $count . ' item' . ($count > 1 ? 's' : '') . ' ' . $label . ' successfully.',
            'status'   => 201,
            'products' => $formatted,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  BULK SUPPLIER
    // ─────────────────────────────────────────────────────────────────────────

    public function bulkSupplierBaseproducts(Request $request)
    {
        $request->validate([
            'ids'      => 'required|array',
            'ids.*'    => 'required|integer|exists:tenant.retail_base_products,id',
            'supplier' => 'required|string|max:255',
        ]);

        $supplier = trim($request->supplier);

        DB::connection('tenant')
            ->table('retail_base_products')
            ->whereIn('id', $request->ids)
            ->update([
                'supplier'   => $supplier,
                'updated_at' => now(),
            ]);

        $products  = DB::connection('tenant')->table('retail_base_products')->whereIn('id', $request->ids)->get();
        $count     = $products->count();
        $formatted = $products->map(fn($p) => $this->formatProduct($p))->values()->toArray();

        return response()->json([
            'success'  => 'Supplier updated for ' . $count . ' product' . ($count > 1 ? 's' : '') . '.',
            'status'   => 201,
            'products' => $formatted,
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
//  IMPORT ROW  (supplier comes from user_filters, not CSV)
// ─────────────────────────────────────────────────────────────────────────

public function importBaseproductRow(Request $request)
{
    // ── 1. Resolve supplier from the importing user's filter preference ──
    $userId  = auth()->id();
    $pref    = DB::connection('tenant')
                    ->table('user_filters')
                    ->where('user_id', $userId)
                    ->first();

    $supplierId = $pref->supplier_id ?? null;

    if (!$supplierId) {
        return response()->json([
            'error'  => 'No supplier selected. Please select a supplier in your filters before importing.',
            'status' => 422,
            'abort'  => true,   // signal the front-end to halt the whole batch
        ]);
    }

    $supplier = DB::connection('tenant')
                    ->table('suppliers')
                    ->where('id', $supplierId)
                    ->where('status', 'active')
                    ->first();

    if (!$supplier) {
        return response()->json([
            'error'  => 'The selected supplier no longer exists or is inactive. Please choose another.',
            'status' => 422,
            'abort'  => true,
        ]);
    }

    // ── 2. Basic row validation ──────────────────────────────────────────
    if (empty($request->name) || trim($request->name) === '') {
        return response()->json(['error' => 'Name is blank — row skipped.', 'status' => 409]);
    }

    $nameExists = DB::connection('tenant')
        ->table('retail_base_products')
        ->whereRaw('LOWER(name) = ?', [strtolower(trim($request->name))])
        ->exists();

    if ($nameExists) {
        return response()->json([
            'error'  => 'Product name "' . trim($request->name) . '" already exists — row skipped.',
            'status' => 409,
        ]);
    }

    if (!empty($request->code)) {
        $exists = DB::connection('tenant')
            ->table('retail_base_products')
            ->where('code', trim($request->code))
            ->exists();

        if ($exists) {
            return response()->json([
                'error'  => 'Code "' . trim($request->code) . '" already exists — row skipped.',
                'status' => 409,
            ]);
        }
    }

    $validUnits = ['Each','kg','g','Litre','ml','Box','Carton','Pack','Pair','Dozen','Bag','Bottle','Metre','Service'];
    $unit       = trim($request->unit ?? 'Each');
    if (!in_array($unit, $validUnits)) {
        $unit = 'Each';
    }

    // ── 3. Insert — supplier is always the one from user_filters ────────
    $data = [
        'name'          => trim($request->name),
        'description'   => $request->description ? trim($request->description) : null,
        'code'          => $request->code         ? trim($request->code)        : null,
        'supplier'      => $supplier->name,    // store display name in the catalogue
        'supplier_id'   => $supplier->id,      // store FK if your schema has it
        'unit'          => $unit,
        'cost_price'    => is_numeric($request->cost_price)    ? (float)$request->cost_price    : null,
        'selling_price' => is_numeric($request->selling_price) ? (float)$request->selling_price : null,
        'is_product'    => 1,
        'created_at'    => now(),
        'updated_at'    => now(),
    ];

    // Drop supplier_id key if your table doesn't have that column
    if (!DB::connection('tenant')->getSchemaBuilder()->hasColumn('retail_base_products', 'supplier_id')) {
        unset($data['supplier_id']);
    }

    $insertId = DB::connection('tenant')->table('retail_base_products')->insertGetId($data);

    if ($insertId) {
        $product = DB::connection('tenant')
            ->table('retail_base_products')
            ->where('id', $insertId)
            ->first();

        return response()->json([
            'success' => 'Row imported.',
            'status'  => 201,
            'product' => $this->formatProduct($product),
        ]);
    }

    return response()->json(['error' => 'Database insert failed.', 'status' => 500]);
}

}