<?php
// Destination: app/Http/Controllers/Operations/Wholesale/WholesaleBaseProductsController.php

namespace App\Http\Controllers\Operations\Wholesale;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;

class WholesaleBaseProductsController extends Controller
{
    /**
     * How many rows we touch per DB chunk for the CSV import — same safety
     * net idea as the retail Base Products import: even if the client-side
     * batch size grows later, queries here stay bounded.
     */
    private const CHUNK_SIZE = 200;

    public function showBaseproductsView()
    {
        $tenant = DB::connection('tenant');
        $userId = auth()->id();

        // Load the user's saved category/supplier filters.
        $pref = $tenant->table('user_filters')
            ->where('user_id', $userId)
            ->first();

        $savedCategoryId = $pref->category_id ?? null;
        $savedSupplierId = $pref->supplier_id ?? null;

        // Only categories that currently have active Wholesale suppliers.
        $categoryIds = $tenant->table('suppliers')
            ->where('status', 'active')
            ->where('sector', 'Wholesale')
            ->whereNotNull('category')
            ->pluck('category')
            ->unique()
            ->values();

        $categories = $tenant->table('categories')
            ->whereIn('id', $categoryIds)
            ->orderBy('category')
            ->get();

        // Suppliers are dependent on the selected category.
        $suppliers = collect();

        if ($savedCategoryId) {
            $suppliers = $tenant->table('suppliers')
                ->where('status', 'active')
                ->where('sector', 'Wholesale')
                ->where('category', $savedCategoryId)
                ->orderBy('name')
                ->get(['id', 'name', 'category']);

            // Prevent a stale supplier filter from hiding all products.
            if ($savedSupplierId && !$suppliers->contains('id', (int) $savedSupplierId)) {
                $savedSupplierId = null;
            }
        } else {
            $savedSupplierId = null;
        }

        // Filter products by the selected category and, optionally, supplier.
        $products = $tenant->table('wholesale_base_products as p')
            ->leftJoin('suppliers as s', 's.id', '=', 'p.supplier_id')
            ->where('s.status', 'active')
            ->where('s.sector', 'Wholesale');

        if ($savedCategoryId) {
            $products->where('s.category', $savedCategoryId);

            if ($savedSupplierId) {
                $products->where('p.supplier_id', (int) $savedSupplierId);
            }
        } else {
            // No category selected = no filtered product list, matching Retail.
            $products->whereRaw('1 = 0');
        }

        $products = $products
            ->orderBy('p.name')
            ->select('p.*', 's.name as supplier_name')
            ->get();

        $supplierNamesMap = $products->isNotEmpty()
            ? $tenant->table('suppliers')
                ->whereIn('id', $products->pluck('supplier_id')->unique()->filter()->values())
                ->pluck('name', 'id')
            : collect();

        return view('operations.wholesale.baseproducts', [
            'categories'      => $categories,
            'suppliers'       => $suppliers,
            'products'        => $products,
            'savedCategoryId' => $savedCategoryId,
            'savedSupplierId' => $savedSupplierId,
            'supplierNamesMap'=> $supplierNamesMap,
        ]);
    }

    /**
     * Save Wholesale Base Products filters.
     *
     * The Base Products page posts category_id first, then supplier_id.
     * Only the submitted filter fields are updated so other user_filters
     * values are preserved.
     */
    public function updateUserFilters(Request $request)
    {
        $request->validate([
            'category_id' => 'nullable|integer',
            'supplier_id' => 'nullable|integer',
        ]);

        $tenant = DB::connection('tenant');
        $userId = auth()->id();

        $existing = $tenant->table('user_filters')
            ->where('user_id', $userId)
            ->first();

        $data = [
            'updated_at' => now(),
        ];

        if ($request->has('category_id')) {
            $categoryId = $request->input('category_id');

            if ($categoryId === null || $categoryId === '') {
                $data['category_id'] = null;
                $data['supplier_id'] = null;
            } else {
                $categoryId = (int) $categoryId;

                $categoryExists = $tenant->table('categories')
                    ->where('id', $categoryId)
                    ->exists();

                if (!$categoryExists) {
                    return redirect()->route('wholesale.operations.baseproducts')->with('error', 'Selected category does not exist.');
                }

                $hasWholesaleSupplier = $tenant->table('suppliers')
                    ->where('status', 'active')
                    ->where('sector', 'Wholesale')
                    ->where('category', $categoryId)
                    ->exists();

                if (!$hasWholesaleSupplier) {
                    return redirect()->route('wholesale.operations.baseproducts')->with('error', 'No active Wholesale suppliers exist for the selected category.');
                }

                $oldCategoryId = $existing->category_id ?? null;
                $data['category_id'] = $categoryId;

                // Category changed: clear the old supplier.
                if ((int) $oldCategoryId !== $categoryId) {
                    $data['supplier_id'] = null;
                }
            }
        }

        if ($request->has('supplier_id')) {
            $supplierId = $request->input('supplier_id');

            if ($supplierId === null || $supplierId === '') {
                $data['supplier_id'] = null;
            } else {
                $supplierId = (int) $supplierId;
                $categoryId = array_key_exists('category_id', $data)
                    ? $data['category_id']
                    : ($existing->category_id ?? null);

                if (!$categoryId) {
                    return redirect()->route('wholesale.operations.baseproducts')->with('error', 'Please select a category first.');
                }

                $supplierExists = $tenant->table('suppliers')
                    ->where('id', $supplierId)
                    ->where('status', 'active')
                    ->where('sector', 'Wholesale')
                    ->where('category', $categoryId)
                    ->exists();

                if (!$supplierExists) {
                    return redirect()->route('wholesale.operations.baseproducts')->with('error', 'Selected supplier is not valid for the selected Wholesale category.');
                }

                $data['supplier_id'] = $supplierId;
            }
        }

        if ($existing) {
            $tenant->table('user_filters')
                ->where('user_id', $userId)
                ->update($data);
        } else {
            $data['user_id'] = $userId;
            $data['category_id'] = $data['category_id'] ?? null;
            $data['supplier_id'] = $data['supplier_id'] ?? null;
            $data['created_at'] = now();

            $tenant->table('user_filters')->insert($data);
        }

        return redirect()->route('wholesale.operations.baseproducts');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function formatProduct($product): array
    {
        return [
            'id'                => $product->id,
            'row'               => 'row' . $product->id,
            'name'              => $product->name,
            'description'       => $product->description,
            'code'              => $product->code,
            'supplier_id'       => $product->supplier_id,
            'supplier_name'     => $product->supplier_name ?? $this->fetchSupplierName($product->supplier_id),
            'unit'              => $product->unit,
            'pack_unit'         => $product->pack_unit,
            'units_per_pack'    => $product->units_per_pack,
            'cost_price'        => $product->cost_price,
            'selling_price'     => $product->selling_price,
            'min_order_quantity'=> $product->min_order_quantity,
            'is_product'        => (int) $product->is_product,
            'is_active'         => (int) $product->is_active,
        ];
    }

    private function fetchSupplierName(?int $supplierId): ?string
    {
        if (!$supplierId) return null;
        return DB::connection('tenant')->table('suppliers')->where('id', $supplierId)->value('name');
    }

    private function formatProductWithLookup($product, array $supplierNamesMap): array
    {
        $product = (object) (array) $product;
        $product->supplier_name = $supplierNamesMap[$product->supplier_id] ?? null;
        return $this->formatProduct($product);
    }

    private function fetchSupplierNamesMap(array $supplierIds): array
    {
        $supplierIds = array_values(array_unique(array_filter($supplierIds)));
        if (empty($supplierIds)) return [];

        return DB::connection('tenant')
            ->table('suppliers')
            ->whereIn('id', $supplierIds)
            ->pluck('name', 'id')
            ->all();
    }

    private function purifyNumber($value): ?float
    {
        if ($value === null) return null;
        $value = preg_replace('/[^0-9.\-]/', '', (string) $value);
        return $value === '' ? null : (float) $value;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  INSERT
    // ─────────────────────────────────────────────────────────────────────────

    public function insertBaseproduct(Request $request)
    {
        $request->validate([
            'name'                => 'required|string|max:255|unique:tenant.wholesale_base_products,name',
            'description'         => 'nullable|string|max:2000',
            'code'                => 'nullable|string|max:100|unique:tenant.wholesale_base_products,code',
            'supplier_id'         => 'required|integer|exists:tenant.suppliers,id',
            'unit'                => 'required|string|max:50',
            'pack_unit'           => 'nullable|string|max:50',
            'units_per_pack'      => 'nullable|numeric|min:0',
            'cost_price'          => 'nullable|numeric|min:0',
            'selling_price'       => 'nullable|numeric|min:0',
            'min_order_quantity'  => 'nullable|numeric|min:0',
        ], [
            'name.unique'        => 'A product with this name already exists in the base catalogue.',
            'code.unique'        => 'This code (SKU) is already used by another product.',
            'supplier_id.required' => 'A supplier is required.',
            'supplier_id.exists'   => 'Selected supplier was not found.',
        ]);

        $data = [
            'name'               => trim($request->name),
            'description'        => $request->description ? trim($request->description) : null,
            'code'               => $request->code         ? trim($request->code)        : null,
            'supplier_id'        => (int) $request->supplier_id,
            'unit'               => trim($request->unit ?? 'Each'),
            'pack_unit'          => $request->pack_unit ? trim($request->pack_unit) : null,
            'units_per_pack'     => $this->purifyNumber($request->units_per_pack),
            'cost_price'         => $this->purifyNumber($request->cost_price),
            'selling_price'      => $this->purifyNumber($request->selling_price),
            'min_order_quantity' => $request->min_order_quantity !== null && $request->min_order_quantity !== ''
                                        ? $this->purifyNumber($request->min_order_quantity)
                                        : 1,
            'is_product'         => 1,
            'is_active'          => 1,
            'created_at'         => now(),
            'updated_at'         => now(),
        ];

        $insertId = DB::connection('tenant')->table('wholesale_base_products')->insertGetId($data);

        if ($insertId) {
            $product = DB::connection('tenant')
                ->table('wholesale_base_products')
                ->where('id', $insertId)
                ->first();

            return response()->json([
                'success' => 'Product created successfully.',
                'status'  => 201,
                'product' => $this->formatProduct((object) array_merge((array) $product, [
                    'supplier_name' => $this->fetchSupplierName((int) $request->supplier_id),
                ])),
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
            'id' => 'required|integer|exists:tenant.wholesale_base_products,id',
        ]);

        // Snapshot before validating the rest — used to detect a price
        // change after the update and to skip re-checking name uniqueness
        // when the name isn't actually changing.
        $existingProduct = DB::connection('tenant')
            ->table('wholesale_base_products')
            ->where('id', $request->id)
            ->first();

        if (! $existingProduct) {
            return response()->json(['error' => 'Product not found.', 'status' => 404]);
        }

        $nameChanging = trim(strtolower((string) $request->name)) !== trim(strtolower($existingProduct->name));

        $request->validate([
            'name'                 => $nameChanging
                ? 'required|string|max:255|unique:tenant.wholesale_base_products,name,' . $request->id
                : 'required|string|max:255',
            'description'          => 'nullable|string|max:2000',
            'code'                 => 'nullable|string|max:100|unique:tenant.wholesale_base_products,code,' . $request->id,
            'supplier_id'          => 'required|integer|exists:tenant.suppliers,id',
            'unit'                 => 'required|string|max:50',
            'pack_unit'            => 'nullable|string|max:50',
            'units_per_pack'       => 'nullable|numeric|min:0',
            'cost_price'           => 'nullable|numeric|min:0',
            'selling_price'        => 'nullable|numeric|min:0',
            'min_order_quantity'   => 'nullable|numeric|min:0',
            'is_product'           => 'nullable|boolean',
            'is_active'            => 'nullable|boolean',
            'price_change_reason'  => 'nullable|string|max:255',
        ], [
            'name.unique' => 'A product with this name already exists in the base catalogue.',
            'code.unique' => 'This code (SKU) is already used by another product.',
        ]);

        $userId = auth()->id();
        $reason = $request->price_change_reason ? trim($request->price_change_reason) : null;
        $today  = now()->toDateString();

        $data = [
            'name'               => trim($request->name),
            'description'        => $request->description ? trim($request->description) : null,
            'code'               => $request->code         ? trim($request->code)        : null,
            'supplier_id'        => (int) $request->supplier_id,
            'unit'               => trim($request->unit ?? 'Each'),
            'pack_unit'          => $request->pack_unit ? trim($request->pack_unit) : null,
            'units_per_pack'     => $this->purifyNumber($request->units_per_pack),
            'cost_price'         => $this->purifyNumber($request->cost_price),
            'selling_price'      => $this->purifyNumber($request->selling_price),
            'min_order_quantity' => $request->min_order_quantity !== null && $request->min_order_quantity !== ''
                                        ? $this->purifyNumber($request->min_order_quantity)
                                        : 1,
            'is_product'         => (int) ($request->is_product ?? 1),
            'is_active'          => (int) ($request->is_active  ?? 1),
            'updated_at'         => now(),
        ];

        $updated = DB::connection('tenant')
            ->table('wholesale_base_products')
            ->where('id', $request->id)
            ->update($data);

        $priceChangeRows = [];

        // ── Log a base catalogue price change, if the selling price actually moved ──
        if ($existingProduct->selling_price !== null
            && $data['selling_price'] !== null
            && round((float) $existingProduct->selling_price, 2) !== round((float) $data['selling_price'], 2)) {

            $priceChangeRows[] = [
                'base_product_id' => $request->id,
                'branch_id'       => null,
                'changed_by'      => $userId,
                'product_name'    => $data['name'],
                'product_code'    => $data['code'],
                'product_unit'    => $data['unit'],
                'branch_name'     => null,
                'old_price'       => $existingProduct->selling_price,
                'new_price'       => $data['selling_price'],
                'reason'          => $reason,
                'change_date'     => $today,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];
        }

        // ── Handle branch price overrides ─────────────────────────────────────
        $branchOverrides  = $request->input('branch_overrides', []);
        $overridesUpdated = 0;

        if (!empty($branchOverrides) && is_array($branchOverrides)) {
            foreach ($branchOverrides as $override) {
                $bpId  = isset($override['id'])            ? (int)   $override['id']            : null;
                $price = isset($override['selling_price']) ? (float) $override['selling_price'] : null;

                if (!$bpId || $price === null) continue;

                $branchProduct = DB::connection('tenant')
                    ->table('wholesale_branch_products as wbp')
                    ->join('branches as b', 'b.id', '=', 'wbp.branch_id')
                    ->where('wbp.id', $bpId)
                    ->select('wbp.id', 'wbp.branch_id', 'wbp.selling_price as old_price', 'b.name as branch_name')
                    ->first();

                $affected = DB::connection('tenant')
                    ->table('wholesale_branch_products')
                    ->where('id', $bpId)
                    ->update([
                        'selling_price' => $price,
                        'updated_at'    => now(),
                    ]);

                if ($affected) {
                    $overridesUpdated++;

                    if ($branchProduct
                        && $branchProduct->old_price !== null
                        && round((float) $branchProduct->old_price, 2) !== round($price, 2)) {

                        $priceChangeRows[] = [
                            'base_product_id' => $request->id,
                            'branch_id'       => $branchProduct->branch_id,
                            'changed_by'      => $userId,
                            'product_name'    => $data['name'],
                            'product_code'    => $data['code'],
                            'product_unit'    => $data['unit'],
                            'branch_name'     => $branchProduct->branch_name,
                            'old_price'       => $branchProduct->old_price,
                            'new_price'       => $price,
                            'reason'          => $reason,
                            'change_date'     => $today,
                            'created_at'      => now(),
                            'updated_at'      => now(),
                        ];
                    }
                }
            }
        }

        if (!empty($priceChangeRows)) {
            DB::connection('tenant')->table('wholesale_price_changes')->insert($priceChangeRows);
        }

        if ($updated !== false) {
            $product = DB::connection('tenant')
                ->table('wholesale_base_products')
                ->where('id', $request->id)
                ->first();

            $message = 'Product updated successfully.';
            if ($overridesUpdated > 0) {
                $message .= ' ' . $overridesUpdated . ' branch price' . ($overridesUpdated > 1 ? 's' : '') . ' updated.';
            }
            if (!empty($priceChangeRows)) {
                $message .= ' ' . count($priceChangeRows) . ' price change' . (count($priceChangeRows) > 1 ? 's' : '') . ' logged.';
            }

            return response()->json([
                'success'           => $message,
                'status'            => 201,
                'product'           => $this->formatProduct((object) array_merge((array) $product, [
                    'supplier_name' => $this->fetchSupplierName((int) $request->supplier_id),
                ])),
                'overrides_updated' => $overridesUpdated,
            ]);
        }

        return response()->json(['error' => 'Product not found or no changes made.', 'status' => 409]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  SINGLE DELETE — blocked only if a branch product with non-zero stock
    //  exists. Everything else (zero-stock branch rows, inventory log
    //  history) is cleaned up automatically so the FK never gets in the way.
    // ─────────────────────────────────────────────────────────────────────────

    public function deleteBaseproduct(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:tenant.wholesale_base_products,id',
        ]);

        $hasStock = DB::connection('tenant')
            ->table('wholesale_branch_products')
            ->where('base_product_id', $request->id)
            ->where('stock_quantity', '>', 0)
            ->exists();

        if ($hasStock) {
            return response()->json([
                'success' => 'Product skipped — it still has stock at one or more branches.',
                'status'  => 201,
                'skipped' => 1,
                'deleted' => 0,
            ]);
        }

        $deleted = DB::connection('tenant')->transaction(function () use ($request) {
            // wholesale_inventory_logs.product_id is ON DELETE RESTRICT — must
            // be cleared manually. wholesale_branch_products cascades on its
            // own, but is dropped explicitly here too for a predictable,
            // single-pass cleanup.
            DB::connection('tenant')->table('wholesale_inventory_logs')->where('product_id', $request->id)->delete();
            DB::connection('tenant')->table('wholesale_branch_products')->where('base_product_id', $request->id)->delete();

            return DB::connection('tenant')->table('wholesale_base_products')->where('id', $request->id)->delete();
        });

        if ($deleted) {
            return response()->json([
                'success' => 'Product deleted successfully.',
                'status'  => 201,
                'skipped' => 0,
                'deleted' => 1,
            ]);
        }

        return response()->json(['error' => 'Product not found.', 'status' => 404]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  BULK DELETE — same rule: only skip ids that have non-zero stock at a
    //  branch. Everything else gets its dependent rows cleared, then deleted.
    // ─────────────────────────────────────────────────────────────────────────

    public function bulkDeleteBaseproducts(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|integer|exists:tenant.wholesale_base_products,id',
        ]);

        $allIds = array_values(array_unique(array_map('intval', $request->ids)));

        $stockedIds = DB::connection('tenant')
            ->table('wholesale_branch_products')
            ->whereIn('base_product_id', $allIds)
            ->where('stock_quantity', '>', 0)
            ->pluck('base_product_id')
            ->unique()
            ->toArray();

        $safeIds = array_values(array_diff($allIds, $stockedIds));
        $skipped = count($stockedIds);

        $deleted = 0;
        if (!empty($safeIds)) {
            $deleted = DB::connection('tenant')->transaction(function () use ($safeIds) {
                DB::connection('tenant')->table('wholesale_inventory_logs')->whereIn('product_id', $safeIds)->delete();
                DB::connection('tenant')->table('wholesale_branch_products')->whereIn('base_product_id', $safeIds)->delete();

                return DB::connection('tenant')->table('wholesale_base_products')->whereIn('id', $safeIds)->delete();
            });
        }

        if ($deleted === 0 && $skipped === 0) {
            return response()->json(['error' => 'No products found.', 'status' => 404]);
        }

        $message = $deleted . ' product' . ($deleted !== 1 ? 's' : '') . ' deleted.';
        if ($skipped > 0) {
            $message .= ' ' . $skipped . ' product' . ($skipped !== 1 ? 's were' : ' was') .
                        ' skipped because ' . ($skipped !== 1 ? 'they still have' : 'it still has') .
                        ' stock at one or more branches.';
        }

        return response()->json([
            'success' => $message,
            'status'  => 201,
            'deleted' => $deleted,
            'skipped' => $skipped,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  BULK STATUS  (is_product — Product vs Service)
    // ─────────────────────────────────────────────────────────────────────────

    public function bulkStatusBaseproducts(Request $request)
    {
        $request->validate([
            'ids'        => 'required|array',
            'ids.*'      => 'required|integer|exists:tenant.wholesale_base_products,id',
            'is_product' => 'required|boolean',
        ]);

        DB::connection('tenant')
            ->table('wholesale_base_products')
            ->whereIn('id', $request->ids)
            ->update([
                'is_product' => (int) $request->is_product,
                'updated_at' => now(),
            ]);

        return $this->respondWithFormattedProducts($request->ids, $request->is_product ? 'marked as Product' : 'marked as Service');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  BULK ACTIVE / INACTIVE
    // ─────────────────────────────────────────────────────────────────────────

    public function bulkActiveBaseproducts(Request $request)
    {
        $request->validate([
            'ids'       => 'required|array',
            'ids.*'     => 'required|integer|exists:tenant.wholesale_base_products,id',
            'is_active' => 'required|boolean',
        ]);

        DB::connection('tenant')
            ->table('wholesale_base_products')
            ->whereIn('id', $request->ids)
            ->update([
                'is_active'  => (int) $request->is_active,
                'updated_at' => now(),
            ]);

        return $this->respondWithFormattedProducts($request->ids, $request->is_active ? 'marked Active' : 'marked Inactive');
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  BULK SUPPLIER
    // ─────────────────────────────────────────────────────────────────────────

    public function bulkSupplierBaseproducts(Request $request)
    {
        $request->validate([
            'ids'         => 'required|array',
            'ids.*'       => 'required|integer|exists:tenant.wholesale_base_products,id',
            'supplier_id' => 'required|integer|exists:tenant.suppliers,id',
        ]);

        DB::connection('tenant')
            ->table('wholesale_base_products')
            ->whereIn('id', $request->ids)
            ->update([
                'supplier_id' => (int) $request->supplier_id,
                'updated_at'  => now(),
            ]);

        return $this->respondWithFormattedProducts($request->ids, null, 'Supplier updated for');
    }

    private function respondWithFormattedProducts(array $ids, ?string $label, string $verb = null): \Illuminate\Http\JsonResponse
    {
        $products = DB::connection('tenant')->table('wholesale_base_products')->whereIn('id', $ids)->get();
        $count    = $products->count();

        $supplierNamesMap = $this->fetchSupplierNamesMap($products->pluck('supplier_id')->toArray());
        $formatted = $products->map(fn($p) => $this->formatProductWithLookup($p, $supplierNamesMap))->values()->toArray();

        $message = $verb
            ? $verb . ' ' . $count . ' product' . ($count > 1 ? 's' : '') . '.'
            : $count . ' item' . ($count > 1 ? 's' : '') . ' ' . $label . ' successfully.';

        return response()->json([
            'success'  => $message,
            'status'   => 201,
            'products' => $formatted,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  CSV IMPORT
    //
    //  The client parses the CSV file itself and sends already-validated
    //  rows here in bounded chunks (self::CHUNK_SIZE per request), tagged
    //  with a batch_id/chunk_index/total_chunks so requests are traceable
    //  to one import run — same contract as the retail Base Products import.
    // ─────────────────────────────────────────────────────────────────────────

    public function uploadBaseproductsCsv(Request $request)
    {
        $request->validate([
            'rows'                 => 'required|array|min:1|max:' . self::CHUNK_SIZE,
            'rows.*.name'          => 'required|string|max:255',
            'rows.*.code'          => 'nullable|string|max:100',
            'rows.*.unit'          => 'nullable|string|max:50',
            'rows.*.cost_price'    => 'nullable',
            'rows.*.selling_price' => 'nullable',
            'supplier_id'          => 'required|integer|exists:tenant.suppliers,id',
            'batch_id'             => 'nullable|string|max:64',
            'chunk_index'          => 'nullable|integer|min:1',
            'total_chunks'         => 'nullable|integer|min:1',
        ]);

        $supplier = DB::connection('tenant')->table('suppliers')
            ->where('id', $request->supplier_id)
            ->where('status', 'active')
            ->first();

        if (!$supplier) {
            return response()->json(['error' => 'Selected supplier is invalid or inactive.', 'status' => 422]);
        }

        $clean = [];
        foreach ($request->input('rows', []) as $r) {
            $name = trim(strip_tags((string) ($r['name'] ?? '')));
            if ($name === '') continue; // silently drop blank rows

            $unit = trim(strip_tags((string) ($r['unit'] ?? '')));
            if ($unit === '') $unit = 'Each';

            $code = trim(strip_tags((string) ($r['code'] ?? '')));

            $clean[] = [
                'name'          => $name,
                'code'          => $code !== '' ? $code : null,
                'unit'          => $unit,
                'cost_price'    => $this->purifyNumber($r['cost_price']    ?? null),
                'selling_price' => $this->purifyNumber($r['selling_price'] ?? null),
            ];
        }

        if (empty($clean)) {
            return response()->json(['error' => 'No valid rows found in this batch.', 'status' => 422]);
        }

        @set_time_limit(120);

        $result = $this->processCsvRowsChunked($clean, $supplier);

        return response()->json([
            'status'        => 200,
            'success'       => "Batch complete — {$result['created']} of " . count($clean) . ' row(s) created.',
            'batch_id'      => $request->input('batch_id'),
            'chunk_index'   => $request->input('chunk_index'),
            'total_chunks'  => $request->input('total_chunks'),
            'row_count'     => count($clean),
            'created_count' => $result['created'],
            'skipped_count' => $result['skipped'],
            'skipped_names' => $result['skipped_names'],
            'failed_count'  => $result['failed_count'],
            'failed_rows'   => $result['failed_rows'],
        ]);
    }

    private function processCsvRowsChunked(array $rows, $supplier): array
    {
        $created      = 0;
        $skipped      = 0;
        $skippedNames = [];
        $failedRows   = [];

        $seenNames = [];
        $seenCodes = [];

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $rowsChunk) {

            $chunkNamesLower = array_map(fn($r) => strtolower(trim($r['name'])), $rowsChunk);
            $existingNamesInDb = DB::connection('tenant')
                ->table('wholesale_base_products')
                ->whereIn(DB::raw('LOWER(name)'), $chunkNamesLower)
                ->pluck('name')
                ->map(fn($n) => strtolower(trim($n)))
                ->flip()
                ->all();

            $chunkCodes = array_values(array_filter(array_map(fn($r) => $r['code'], $rowsChunk)));
            $existingCodesInDb = [];
            if (!empty($chunkCodes)) {
                $existingCodesInDb = DB::connection('tenant')
                    ->table('wholesale_base_products')
                    ->whereIn('code', $chunkCodes)
                    ->pluck('code')
                    ->flip()
                    ->all();
            }

            $toInsert = [];

            foreach ($rowsChunk as $r) {
                $key = strtolower(trim($r['name']));

                if (isset($existingNamesInDb[$key]) || isset($seenNames[$key])) {
                    $skipped++;
                    $skippedNames[] = $r['name'];
                    continue;
                }

                $code = $r['code'];
                if ($code !== null && (isset($existingCodesInDb[$code]) || isset($seenCodes[$code]))) {
                    $code = null; // drop the conflicting code, keep the row
                }

                $toInsert[] = [
                    'name'               => $r['name'],
                    'description'        => null,
                    'code'               => $code,
                    'supplier_id'        => $supplier->id,
                    'unit'               => $r['unit'],
                    'cost_price'         => $r['cost_price'],
                    'selling_price'      => $r['selling_price'],
                    'min_order_quantity' => 1,
                    'is_product'         => 1,
                    'is_active'          => 1,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];

                $seenNames[$key] = true;
                if ($code !== null) $seenCodes[$code] = true;
            }

            if (empty($toInsert)) continue;

            try {
                DB::connection('tenant')->table('wholesale_base_products')->insert($toInsert);
                $created += count($toInsert);
            } catch (\Throwable $e) {
                foreach ($toInsert as $row) {
                    try {
                        DB::connection('tenant')->table('wholesale_base_products')->insert($row);
                        $created++;
                    } catch (\Throwable $rowError) {
                        $failedRows[] = [
                            'name'          => $row['name'],
                            'code'          => $row['code'],
                            'unit'          => $row['unit'],
                            'cost_price'    => $row['cost_price'],
                            'selling_price' => $row['selling_price'],
                            'error'         => 'Could not be saved (duplicate or invalid data).',
                        ];
                    }
                }
            }
        }

        return [
            'created'       => $created,
            'skipped'       => $skipped,
            'skipped_names' => $skippedNames,
            'failed_count'  => count($failedRows),
            'failed_rows'   => $failedRows,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  BRANCH PRICE OVERRIDES — for Edit Product modal
    // ─────────────────────────────────────────────────────────────────────────

    public function getBranchOverrides(Request $request)
    {
        $request->validate([
            'base_product_id' => 'required|integer|exists:tenant.wholesale_base_products,id',
        ]);

        $base = DB::connection('tenant')
            ->table('wholesale_base_products')
            ->where('id', $request->base_product_id)
            ->first(['selling_price']);

        $baseSell = (float) ($base->selling_price ?? 0);

        $overrides = DB::connection('tenant')
            ->table('wholesale_branch_products as wbp')
            ->join('branches as b', 'b.id', '=', 'wbp.branch_id')
            ->where('wbp.base_product_id', $request->base_product_id)
            ->whereNotNull('wbp.selling_price')
            ->select('wbp.id', 'b.name as branch_name', 'wbp.selling_price')
            ->orderBy('b.name')
            ->get()
            ->filter(function ($row) use ($baseSell) {
                return abs((float) $row->selling_price - $baseSell) > 0.0001;
            })
            ->values();

        return response()->json(['overrides' => $overrides]);
    }
}