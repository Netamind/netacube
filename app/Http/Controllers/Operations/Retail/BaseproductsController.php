<?php

namespace App\Http\Controllers\Operations\Retail;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;

class BaseproductsController extends Controller
{

    // ─────────────────────────────────────────────────────────────────────────
    //  SHOW VIEW
    // ─────────────────────────────────────────────────────────────────────────

    public function showBaseproductsView()
    {
        return view('operations.retail.baseproducts');
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  PRIVATE HELPER — shape the product array for every JSON response.
    //  Joins category name from categories table.
    // ─────────────────────────────────────────────────────────────────────────

    private function formatProduct($product): array
    {
        // $product may already have category_name from a join, or we can look it up
        $categoryName = null;
        if (isset($product->category_name)) {
            $categoryName = $product->category_name;
        } elseif (!empty($product->category_id)) {
            $cat = DB::connection('tenant')->table('categories')->where('id', $product->category_id)->first();
            $categoryName = $cat ? $cat->category : null;
        }

        return [
            'id'                      => $product->id,
            'row'                     => 'row' . $product->id,
            'name'                    => $product->name,
            'description'             => $product->description,
            'brand'                   => $product->brand,
            'supplier'                => $product->supplier,
            'manufacturer'            => $product->manufacturer,
            'country_of_origin'       => $product->country_of_origin,
            'internal_code'           => $product->internal_code,
            'unit_of_measure'         => $product->unit_of_measure,
            'weight_kg'               => $product->weight_kg,
            'volume_litres'           => $product->volume_litres,
            'is_product'              => (int) $product->is_product,
            'default_selling_price'   => $product->default_selling_price,
            'default_cost_price'      => $product->default_cost_price,
            'mra_product_code'        => $product->mra_product_code,
            'mra_tax_rate_id'         => $product->mra_tax_rate_id,
            'is_vat_exempt_by_nature' => (int) $product->is_vat_exempt_by_nature,
            'category_id'             => $product->category_id,
            'category_name'           => $categoryName,
            'subcategory'             => $product->subcategory,
            'is_active'               => (int) $product->is_active,
        ];
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  INSERT
    // ─────────────────────────────────────────────────────────────────────────

    public function insertBaseproduct(Request $request)
    {
        $request->validate([
            'name'                    => 'required|string|max:255',
            'description'             => 'nullable|string|max:2000',
            'brand'                   => 'nullable|string|max:255',
            'supplier'                => 'nullable|string|max:255',
            'manufacturer'            => 'nullable|string|max:255',
            'country_of_origin'       => 'nullable|string|size:2',
            'internal_code'           => 'nullable|string|max:100|unique:tenant.retail_base_products,internal_code',
            'unit_of_measure'         => 'required|string|max:50',
            'weight_kg'               => 'nullable|numeric|min:0',
            'volume_litres'           => 'nullable|numeric|min:0',
            'is_product'              => 'required|boolean',
            'default_selling_price'   => 'nullable|numeric|min:0',
            'default_cost_price'      => 'nullable|numeric|min:0',
            'mra_product_code'        => 'nullable|string|max:50',
            'mra_tax_rate_id'         => 'required|string|in:A,E,TL',
            'is_vat_exempt_by_nature' => 'nullable|boolean',
            'category_id'             => 'nullable|integer|exists:tenant.categories,id',
            'subcategory'             => 'nullable|string|max:255',
            'is_active'               => 'nullable|boolean',
        ]);

        $data = [
            'name'                    => trim($request->name),
            'description'             => $request->description       ? trim($request->description)                   : null,
            'brand'                   => $request->brand              ? trim($request->brand)                         : null,
            'supplier'                => $request->supplier           ? trim($request->supplier)                      : null,
            'manufacturer'            => $request->manufacturer       ? trim($request->manufacturer)                  : null,
            'country_of_origin'       => $request->country_of_origin  ? strtoupper(trim($request->country_of_origin)) : null,
            'internal_code'           => $request->internal_code      ? trim($request->internal_code)                 : null,
            'unit_of_measure'         => trim($request->unit_of_measure),
            'weight_kg'               => $request->weight_kg          !== '' ? $request->weight_kg          : null,
            'volume_litres'           => $request->volume_litres      !== '' ? $request->volume_litres      : null,
            'is_product'              => (int) $request->is_product,
            'default_selling_price'   => ($request->default_selling_price !== null && $request->default_selling_price !== '') ? $request->default_selling_price : null,
            'default_cost_price'      => ($request->default_cost_price  !== null && $request->default_cost_price  !== '') ? $request->default_cost_price      : null,
            'mra_product_code'        => $request->mra_product_code   ? trim($request->mra_product_code)              : null,
            'mra_tax_rate_id'         => trim($request->mra_tax_rate_id),
            'is_vat_exempt_by_nature' => (int) ($request->is_vat_exempt_by_nature ?? 0),
            'category_id'             => $request->category_id        ? (int) $request->category_id                  : null,
            'subcategory'             => $request->subcategory        ? trim($request->subcategory)                   : null,
            'is_active'               => (int) ($request->is_active ?? 1),
            'created_at'              => now(),
            'updated_at'              => now(),
        ];

        $insertId = DB::connection('tenant')->table('retail_base_products')->insertGetId($data);

        if ($insertId) {
            $product = DB::connection('tenant')
                ->table('retail_base_products as p')
                ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
                ->select('p.*', 'c.category as category_name')
                ->where('p.id', $insertId)
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
            'id'                      => 'required|integer|exists:tenant.retail_base_products,id',
            'name'                    => 'required|string|max:255',
            'description'             => 'nullable|string|max:2000',
            'brand'                   => 'nullable|string|max:255',
            'supplier'                => 'nullable|string|max:255',
            'manufacturer'            => 'nullable|string|max:255',
            'country_of_origin'       => 'nullable|string|size:2',
            'internal_code'           => 'nullable|string|max:100|unique:tenant.retail_base_products,internal_code,' . $request->id,
            'unit_of_measure'         => 'required|string|max:50',
            'weight_kg'               => 'nullable|numeric|min:0',
            'volume_litres'           => 'nullable|numeric|min:0',
            'is_product'              => 'required|boolean',
            'default_selling_price'   => 'nullable|numeric|min:0',
            'default_cost_price'      => 'nullable|numeric|min:0',
            'mra_product_code'        => 'nullable|string|max:50',
            'mra_tax_rate_id'         => 'required|string|in:A,E,TL',
            'is_vat_exempt_by_nature' => 'nullable|boolean',
            'category_id'             => 'nullable|integer|exists:tenant.categories,id',
            'subcategory'             => 'nullable|string|max:255',
            'is_active'               => 'nullable|boolean',
        ]);

        $data = [
            'name'                    => trim($request->name),
            'description'             => $request->description       ? trim($request->description)                   : null,
            'brand'                   => $request->brand              ? trim($request->brand)                         : null,
            'supplier'                => $request->supplier           ? trim($request->supplier)                      : null,
            'manufacturer'            => $request->manufacturer       ? trim($request->manufacturer)                  : null,
            'country_of_origin'       => $request->country_of_origin  ? strtoupper(trim($request->country_of_origin)) : null,
            'internal_code'           => $request->internal_code      ? trim($request->internal_code)                 : null,
            'unit_of_measure'         => trim($request->unit_of_measure),
            'weight_kg'               => ($request->weight_kg !== null && $request->weight_kg !== '')    ? $request->weight_kg    : null,
            'volume_litres'           => ($request->volume_litres !== null && $request->volume_litres !== '') ? $request->volume_litres : null,
            'is_product'              => (int) $request->is_product,
            'default_selling_price'   => ($request->default_selling_price !== null && $request->default_selling_price !== '') ? $request->default_selling_price : null,
            'default_cost_price'      => ($request->default_cost_price  !== null && $request->default_cost_price  !== '') ? $request->default_cost_price      : null,
            'mra_product_code'        => $request->mra_product_code   ? trim($request->mra_product_code)              : null,
            'mra_tax_rate_id'         => trim($request->mra_tax_rate_id),
            'is_vat_exempt_by_nature' => (int) ($request->is_vat_exempt_by_nature ?? 0),
            'category_id'             => $request->category_id        ? (int) $request->category_id                  : null,
            'subcategory'             => $request->subcategory        ? trim($request->subcategory)                   : null,
            'is_active'               => (int) ($request->is_active ?? 1),
            'updated_at'              => now(),
        ];

        $updated = DB::connection('tenant')->table('retail_base_products')->where('id', $request->id)->update($data);

        if ($updated !== false) {
            $product = DB::connection('tenant')
                ->table('retail_base_products as p')
                ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
                ->select('p.*', 'c.category as category_name')
                ->where('p.id', $request->id)
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

        $deleted = DB::connection('tenant')->table('retail_base_products')->where('id', $request->id)->delete();

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

        $deleted = DB::connection('tenant')->table('retail_base_products')->whereIn('id', $safeIds)->delete();

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
    //  BULK STATUS (activate / deactivate)  — no page reload
    // ─────────────────────────────────────────────────────────────────────────

    public function bulkStatusBaseproducts(Request $request)
    {
        $request->validate([
            'ids'       => 'required|array',
            'ids.*'     => 'required|integer|exists:tenant.retail_base_products,id',
            'is_active' => 'required|boolean',
        ]);

        DB::connection('tenant')
            ->table('retail_base_products')
            ->whereIn('id', $request->ids)
            ->update([
                'is_active'  => (int) $request->is_active,
                'updated_at' => now(),
            ]);

        $products = DB::connection('tenant')
            ->table('retail_base_products as p')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->select('p.*', 'c.category as category_name')
            ->whereIn('p.id', $request->ids)
            ->get();

        $label     = $request->is_active ? 'activated' : 'deactivated';
        $count     = $products->count();
        $formatted = $products->map(fn($p) => $this->formatProduct($p))->values()->toArray();

        return response()->json([
            'success'  => $count . ' product' . ($count > 1 ? 's' : '') . ' ' . $label . ' successfully.',
            'status'   => 201,
            'products' => $formatted,
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  BULK CHANGE SUPPLIER
    // ─────────────────────────────────────────────────────────────────────────

    public function bulkSupplierBaseproducts(Request $request)
    {
        $request->validate([
            'ids'      => 'required|array',
            'ids.*'    => 'required|integer|exists:tenant.retail_base_products,id',
            'supplier' => 'nullable|string|max:255',
        ]);

        $supplier = ($request->filled('supplier')) ? trim($request->supplier) : null;

        DB::connection('tenant')
            ->table('retail_base_products')
            ->whereIn('id', $request->ids)
            ->update([
                'supplier'   => $supplier,
                'updated_at' => now(),
            ]);

        $products = DB::connection('tenant')
            ->table('retail_base_products as p')
            ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
            ->select('p.*', 'c.category as category_name')
            ->whereIn('p.id', $request->ids)
            ->get();

        $count     = $products->count();
        $formatted = $products->map(fn($p) => $this->formatProduct($p))->values()->toArray();

        return response()->json([
            'success'  => 'Supplier updated for ' . $count . ' product' . ($count > 1 ? 's' : '') . '.',
            'status'   => 201,
            'products' => $formatted,
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  IMPORT — single row (called sequentially from JS for each CSV row)
    //
    //  Accepts category_name (string from CSV) and resolves to category_id.
    //  Skips rows where:
    //    • name is blank
    //    • internal_code already exists (duplicate guard)
    // ─────────────────────────────────────────────────────────────────────────

    public function importBaseproductRow(Request $request)
    {
        // Basic validation — soft-fail (return status 409) rather than hard-abort
        // so the JS loop can continue to the next row.
        if (empty($request->name) || trim($request->name) === '') {
            return response()->json(['error' => 'Name is blank — row skipped.', 'status' => 409]);
        }

        // Duplicate internal_code guard
        if (!empty($request->internal_code)) {
            $exists = DB::connection('tenant')
                ->table('retail_base_products')
                ->where('internal_code', trim($request->internal_code))
                ->exists();
            if ($exists) {
                return response()->json([
                    'error'  => 'Internal code "' . trim($request->internal_code) . '" already exists — row skipped.',
                    'status' => 409,
                ]);
            }
        }

        // Resolve category name → id
        $categoryId = null;
        if (!empty($request->category_name)) {
            $cat = DB::connection('tenant')
                ->table('categories')
                ->whereRaw('LOWER(category) = ?', [strtolower(trim($request->category_name))])
                ->first();
            $categoryId = $cat ? $cat->id : null;
        }

        // Normalise mra_tax_rate_id
        $taxRate = strtoupper(trim($request->mra_tax_rate_id ?? 'A'));
        if (!in_array($taxRate, ['A', 'E', 'TL'])) $taxRate = 'A';

        // Normalise unit_of_measure
        $validUnits = ['Each','kg','g','Litre','ml','Box','Carton','Pack','Pair','Dozen','Bag','Bottle','Metre','Service'];
        $unit = trim($request->unit_of_measure ?? 'Each');
        if (!in_array($unit, $validUnits)) $unit = 'Each';

        $data = [
            'name'                    => trim($request->name),
            'description'             => $request->description       ? trim($request->description)                   : null,
            'brand'                   => $request->brand              ? trim($request->brand)                         : null,
            'supplier'                => $request->supplier           ? trim($request->supplier)                      : null,
            'manufacturer'            => $request->manufacturer       ? trim($request->manufacturer)                  : null,
            'country_of_origin'       => $request->country_of_origin  ? strtoupper(substr(trim($request->country_of_origin), 0, 2)) : null,
            'internal_code'           => $request->internal_code      ? trim($request->internal_code)                 : null,
            'unit_of_measure'         => $unit,
            'weight_kg'               => is_numeric($request->weight_kg)    ? (float) $request->weight_kg    : null,
            'volume_litres'           => is_numeric($request->volume_litres) ? (float) $request->volume_litres : null,
            'is_product'              => in_array($request->is_product, ['0', 0, false], true) ? 0 : 1,
            'default_selling_price'   => is_numeric($request->default_selling_price) ? (float) $request->default_selling_price : null,
            'default_cost_price'      => is_numeric($request->default_cost_price)    ? (float) $request->default_cost_price    : null,
            'mra_product_code'        => $request->mra_product_code   ? trim($request->mra_product_code)              : null,
            'mra_tax_rate_id'         => $taxRate,
            'is_vat_exempt_by_nature' => in_array($request->is_vat_exempt_by_nature, ['1', 1, true], true) ? 1 : 0,
            'category_id'             => $categoryId,
            'subcategory'             => $request->subcategory        ? trim($request->subcategory)                   : null,
            'is_active'               => in_array($request->is_active, ['0', 0, false], true) ? 0 : 1,
            'created_at'              => now(),
            'updated_at'              => now(),
        ];

        $insertId = DB::connection('tenant')->table('retail_base_products')->insertGetId($data);

        if ($insertId) {
            $product = DB::connection('tenant')
                ->table('retail_base_products as p')
                ->leftJoin('categories as c', 'p.category_id', '=', 'c.id')
                ->select('p.*', 'c.category as category_name')
                ->where('p.id', $insertId)
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