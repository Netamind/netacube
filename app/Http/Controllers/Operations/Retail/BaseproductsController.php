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
    //  VAT TYPE CODES (mra_tax_rate_id)
    //
    //  Malawi uses an Electronic Invoicing System (EIS) where every sale must
    //  be reported to MRA in real time. Each product on a receipt must carry a
    //  VAT type code that tells MRA how to tax it.
    //
    //  These are the valid VAT type codes:
    //
    //    A  — Standard VAT (17.5%)
    //         Used for most retail products and general services.
    //         This is the default for the majority of goods sold in shops.
    //
    //    B  — Reduced VAT rate
    //         A lower rate for specific goods approved by MRA.
    //         Rarely used — check with your accountant if applicable.
    //
    //    C  — Zero-rated (0% VAT)
    //         VAT registered but charged at zero percent.
    //         Mainly used for exports. Uncommon in local retail.
    //
    //    E  — VAT Exempt by nature
    //         These goods are completely outside the VAT system.
    //         Examples: basic unprocessed foods (maize, rice, vegetables),
    //         medicines, agricultural seeds and fertilisers.
    //         No VAT line appears on the receipt for these items.
    //
    //    TL — Tourism Levy (1%)
    //         Only applies to hotels, lodges, restaurants and other
    //         tourism/hospitality businesses. Not used in general retail.
    //
    //  IMPORTANT: If you are unsure which code to use, consult your accountant.
    //  Using the wrong code can affect your VAT returns.
    // ─────────────────────────────────────────────────────────────────────────

    private const VALID_TAX_RATE_IDS = ['A', 'B', 'C', 'E', 'TL'];


    // ─────────────────────────────────────────────────────────────────────────
    //  PRIVATE HELPER — shape the product array for every JSON response.
    //
    //  NOTE: The retail_base_products table stores category as a plain string
    //  column (not a foreign key). There is no category_id on this table.
    //  Subcategory is stored in the database but not exposed through this
    //  interface — it can be managed directly in the database if needed.
    // ─────────────────────────────────────────────────────────────────────────

    private function formatProduct($product): array
    {
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
            // category is a plain string — no join needed
            'category'                => $product->category,
            'subcategory'             => $product->subcategory,
            'is_active'               => (int) $product->is_active,
        ];
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  INSERT
    //
    //  Creates a new base product in the master catalogue.
    //  category is stored as a plain text string (e.g. "Beverages").
    //
    //  About mra_tax_rate_id:
    //  This is the VAT type that will appear on MRA electronic receipts for
    //  this product. It is required — every product must have a VAT type.
    //  Default is "A" (Standard VAT 17.5%) which applies to most retail goods.
    //
    //  About mra_product_code:
    //  This is an optional code that MRA assigns to your product category.
    //  You register your products on the MRA EIS portal (eis-portal.mra.mw)
    //  to obtain this code. You can leave it blank and fill it in later.
    //
    //  About is_product:
    //  true  = a physical item you keep in stock (e.g. a bag of rice)
    //  false = a service you provide (e.g. delivery fee, consultation)
    // ─────────────────────────────────────────────────────────────────────────

    public function insertBaseproduct(Request $request)
    {
        $validTaxIds = implode(',', self::VALID_TAX_RATE_IDS);

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
            'mra_tax_rate_id'         => 'required|string|in:' . $validTaxIds,
            'is_vat_exempt_by_nature' => 'nullable|boolean',
            'category'                => 'nullable|string|max:255',
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
            'mra_tax_rate_id'         => strtoupper(trim($request->mra_tax_rate_id)),
            'is_vat_exempt_by_nature' => (int) ($request->is_vat_exempt_by_nature ?? 0),
            'category'                => $request->category           ? trim($request->category)                      : null,
            'subcategory'             => $request->subcategory        ? trim($request->subcategory)                   : null,
            'is_active'               => (int) ($request->is_active ?? 1),
            'created_at'              => now(),
            'updated_at'              => now(),
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
        $validTaxIds = implode(',', self::VALID_TAX_RATE_IDS);

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
            'mra_tax_rate_id'         => 'required|string|in:' . $validTaxIds,
            'is_vat_exempt_by_nature' => 'nullable|boolean',
            'category'                => 'nullable|string|max:255',
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
            'mra_tax_rate_id'         => strtoupper(trim($request->mra_tax_rate_id)),
            'is_vat_exempt_by_nature' => (int) ($request->is_vat_exempt_by_nature ?? 0),
            'category'                => $request->category           ? trim($request->category)                      : null,
            // subcategory: preserved from existing value — not editable via the UI form
            'is_active'               => (int) ($request->is_active ?? 1),
            'updated_at'              => now(),
        ];

        $updated = DB::connection('tenant')->table('retail_base_products')->where('id', $request->id)->update($data);

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
    //
    //  A product cannot be deleted if it is already assigned to a branch.
    //  Remove it from all branches first, then delete it here.
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
    //
    //  Products assigned to branches are skipped (not deleted).
    //  The response tells you how many were deleted vs skipped.
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
    //  BULK STATUS (activate / deactivate)
    //
    //  Inactive products are hidden from branch product listings.
    //  This does not affect existing branch stock records — only visibility.
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
            ->table('retail_base_products')
            ->whereIn('id', $request->ids)
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
    //  BULK VAT TYPE
    //
    //  Changes the VAT type code on multiple products at once.
    //  The VAT type controls how MRA taxes each item on electronic receipts.
    //  Use with care — changing the VAT type affects all future receipts
    //  for these products.
    // ─────────────────────────────────────────────────────────────────────────

    public function bulkTaxBaseproducts(Request $request)
    {
        $validTaxIds = implode(',', self::VALID_TAX_RATE_IDS);

        $request->validate([
            'ids'             => 'required|array',
            'ids.*'           => 'required|integer|exists:tenant.retail_base_products,id',
            'mra_tax_rate_id' => 'required|string|in:' . $validTaxIds,
        ]);

        DB::connection('tenant')
            ->table('retail_base_products')
            ->whereIn('id', $request->ids)
            ->update([
                'mra_tax_rate_id' => strtoupper(trim($request->mra_tax_rate_id)),
                'updated_at'      => now(),
            ]);

        $products = DB::connection('tenant')
            ->table('retail_base_products')
            ->whereIn('id', $request->ids)
            ->get();

        $count     = $products->count();
        $formatted = $products->map(fn($p) => $this->formatProduct($p))->values()->toArray();

        return response()->json([
            'success'  => 'VAT type updated to "' . strtoupper($request->mra_tax_rate_id) . '" for ' . $count . ' product' . ($count > 1 ? 's' : '') . '.',
            'status'   => 201,
            'products' => $formatted,
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  BULK TYPE (product / service)
    // ─────────────────────────────────────────────────────────────────────────

    public function bulkTypeBaseproducts(Request $request)
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
                'is_product'  => (int) $request->is_product,
                'updated_at'  => now(),
            ]);

        $products = DB::connection('tenant')
            ->table('retail_base_products')
            ->whereIn('id', $request->ids)
            ->get();

        $count     = $products->count();
        $label     = $request->is_product ? 'Product' : 'Service';
        $formatted = $products->map(fn($p) => $this->formatProduct($p))->values()->toArray();

        return response()->json([
            'success'  => $count . ' product' . ($count > 1 ? 's' : '') . ' set to "' . $label . '" successfully.',
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
            ->table('retail_base_products')
            ->whereIn('id', $request->ids)
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
    //  This endpoint receives one product row at a time from the browser.
    //  The JS loops through every row in the uploaded CSV and sends them here
    //  one by one, showing a progress bar as it goes.
    //
    //  Each row is skipped (soft-fail, not a crash) if:
    //    • The product name is blank
    //    • The internal_code already exists in the database (duplicate guard)
    //
    //  VAT type defaults to "A" (Standard VAT 17.5%) if not supplied in the
    //  CSV. This is correct for most retail products. Users can change
    //  individual products after import.
    //
    //  Category and Supplier come from the top filter bar in the UI — they
    //  are passed in the payload from JS, not entered inside the import modal.
    // ─────────────────────────────────────────────────────────────────────────

    public function importBaseproductRow(Request $request)
    {
        // Skip rows with no name — don't crash, just report and move on
        if (empty($request->name) || trim($request->name) === '') {
            return response()->json(['error' => 'Name is blank — row skipped.', 'status' => 409]);
        }

        // Skip rows where the internal code already exists (no duplicates)
        if (!empty($request->internal_code)) {
            $exists = DB::connection('tenant')
                ->table('retail_base_products')
                ->where('internal_code', trim($request->internal_code))
                ->exists();
            if ($exists) {
                return response()->json([
                    'error'  => 'Code "' . trim($request->internal_code) . '" already exists — row skipped.',
                    'status' => 409,
                ]);
            }
        }

        // VAT type: default to "A" (Standard VAT) if not supplied or unrecognised
        $taxRate = strtoupper(trim($request->mra_tax_rate_id ?? 'A'));
        if (!in_array($taxRate, self::VALID_TAX_RATE_IDS)) {
            $taxRate = 'A';
        }

        // Unit of measure: default to "Each" if not supplied or unrecognised
        $validUnits = ['Each','kg','g','Litre','ml','Box','Carton','Pack','Pair','Dozen','Bag','Bottle','Metre','Service'];
        $unit = trim($request->unit_of_measure ?? 'Each');
        if (!in_array($unit, $validUnits)) {
            $unit = 'Each';
        }

        $data = [
            'name'                    => trim($request->name),
            'description'             => $request->description       ? trim($request->description)                        : null,
            'brand'                   => $request->brand              ? trim($request->brand)                              : null,
            'supplier'                => $request->supplier           ? trim($request->supplier)                           : null,
            'manufacturer'            => $request->manufacturer       ? trim($request->manufacturer)                       : null,
            'country_of_origin'       => $request->country_of_origin  ? strtoupper(substr(trim($request->country_of_origin), 0, 2)) : null,
            'internal_code'           => $request->internal_code      ? trim($request->internal_code)                      : null,
            'unit_of_measure'         => $unit,
            'weight_kg'               => is_numeric($request->weight_kg)    ? (float) $request->weight_kg    : null,
            'volume_litres'           => is_numeric($request->volume_litres) ? (float) $request->volume_litres : null,
            'is_product'              => in_array($request->is_product, ['0', 0, false], true) ? 0 : 1,
            'default_selling_price'   => is_numeric($request->default_selling_price) ? (float) $request->default_selling_price : null,
            'default_cost_price'      => is_numeric($request->default_cost_price)    ? (float) $request->default_cost_price    : null,
            'mra_product_code'        => $request->mra_product_code   ? trim($request->mra_product_code)                   : null,
            'mra_tax_rate_id'         => $taxRate,
            'is_vat_exempt_by_nature' => in_array($request->is_vat_exempt_by_nature, ['1', 1, true], true) ? 1 : 0,
            // category is a plain string — passed from the filter bar selection in the UI
            'category'                => $request->category           ? trim($request->category)                           : null,
            'subcategory'             => $request->subcategory        ? trim($request->subcategory)                        : null,
            'is_active'               => in_array($request->is_active, ['0', 0, false], true) ? 0 : 1,
            'created_at'              => now(),
            'updated_at'              => now(),
        ];

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