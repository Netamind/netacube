<?php

namespace App\Http\Controllers\Operations\Retail;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;

class BaseproductsController extends Controller
{
    /**
     * How many rows we touch per DB chunk for the CSV import.
     * The client already sends small batches (BP_CSV_UPLOAD_CHUNK_SIZE
     * rows per request), but we chunk again server-side as a safety net —
     * if the client-side chunk size is ever raised, queries here still
     * stay small and won't approach PHP/SQL execution timeouts.
     */
    private const CHUNK_SIZE = 200;

    public function showBaseproductsView()
    {
        return view('operations.retail.baseproducts');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /**
     * retail_base_products.supplier stores the supplier's ID (int) only —
     * never a name. This formats a product row for the client, resolving
     * the supplier name via the optionally-preloaded $supplierName.
     */
    private function formatProduct($product, ?string $supplierName = null): array
    {
        return [
            'id'            => $product->id,
            'row'           => 'row' . $product->id,
            'name'          => $product->name,
            'description'   => $product->description,
            'code'          => $product->code,
            'supplier_id'   => $product->supplier,
            'supplier_name' => $supplierName,
            'unit'          => $product->unit,
            'cost_price'    => $product->cost_price,
            'selling_price' => $product->selling_price,
            'is_product'    => (int) $product->is_product,
        ];
    }

    private function fetchSupplierName(?int $supplierId): ?string
    {
        if (!$supplierId) return null;
        return DB::connection('tenant')->table('suppliers')->where('id', $supplierId)->value('name');
    }

    /**
     * Bulk-fetch supplier names for a batch of products at once (1 query
     * instead of N), keyed by supplier id.
     */
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

    private function formatProductWithLookup($product, array $supplierNamesMap): array
    {
        $supplierName = $supplierNamesMap[$product->supplier] ?? null;
        return $this->formatProduct($product, $supplierName);
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
            'name'          => 'required|string|max:255|unique:tenant.retail_base_products,name',
            'description'   => 'nullable|string|max:2000',
            'code'          => 'nullable|string|max:100|unique:tenant.retail_base_products,code',
            'supplier'      => 'required|integer|exists:tenant.suppliers,id',
            'unit'          => 'required|string|max:50',
            'cost_price'    => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
        ], [
            'name.unique'        => 'A product with this name already exists in the base catalogue.',
            'code.unique'        => 'This code (SKU) is already used by another product.',
            'supplier.required'  => 'A supplier is required.',
            'supplier.exists'    => 'Selected supplier was not found.',
        ]);

        $data = [
            'name'          => trim($request->name),
            'description'   => $request->description ? trim($request->description) : null,
            'code'          => $request->code         ? trim($request->code)        : null,
            'supplier'      => (int) $request->supplier, // supplier ID only — never a name
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
                'product' => $this->formatProduct($product, $this->fetchSupplierName((int) $request->supplier)),
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
            'id'                   => 'required|integer|exists:tenant.retail_base_products,id',
            'name'                 => 'required|string|max:255|unique:tenant.retail_base_products,name,' . $request->id,
            'description'          => 'nullable|string|max:2000',
            'code'                 => 'nullable|string|max:100|unique:tenant.retail_base_products,code,' . $request->id,
            'supplier'             => 'required|integer|exists:tenant.suppliers,id',
            'unit'                 => 'required|string|max:50',
            'cost_price'           => 'nullable|numeric|min:0',
            'selling_price'        => 'nullable|numeric|min:0',
            'is_product'           => 'nullable|boolean',
            'price_change_reason'  => 'nullable|string|max:255',
        ], [
            'name.unique'       => 'A product with this name already exists in the base catalogue.',
            'code.unique'       => 'This code (SKU) is already used by another product.',
            'supplier.required' => 'A supplier is required.',
            'supplier.exists'   => 'Selected supplier was not found.',
        ]);

        $userId = auth()->id();
        $reason = $request->price_change_reason ? trim($request->price_change_reason) : null;
        $today  = now()->toDateString();

        // Snapshot the existing product so we can detect a base price change after the update runs.
        $existingProduct = DB::connection('tenant')
            ->table('retail_base_products')
            ->where('id', $request->id)
            ->first();

        $data = [
            'name'          => trim($request->name),
            'description'   => $request->description ? trim($request->description) : null,
            'code'          => $request->code         ? trim($request->code)        : null,
            'supplier'      => (int) $request->supplier, // supplier ID only — never a name
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

        $priceChangeRows = [];

        // ── Log a base catalogue price change, if the selling price actually moved ──
        if ($existingProduct
            && $existingProduct->selling_price !== null
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

                // Snapshot the branch row (and its branch name) before overwriting it.
                $branchProduct = DB::connection('tenant')
                    ->table('retail_branch_products as rbp')
                    ->join('branches as b', 'b.id', '=', 'rbp.branch_id')
                    ->where('rbp.id', $bpId)
                    ->select('rbp.id', 'rbp.branch_id', 'rbp.selling_price as old_price', 'b.name as branch_name')
                    ->first();

                $affected = DB::connection('tenant')
                    ->table('retail_branch_products')
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
            DB::connection('tenant')->table('retail_price_changes')->insert($priceChangeRows);
        }

        if ($updated !== false) {
            $product = DB::connection('tenant')
                ->table('retail_base_products')
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
                'product'           => $this->formatProduct($product, $this->fetchSupplierName((int) $request->supplier)),
                'overrides_updated' => $overridesUpdated,
            ]);
        }

        return response()->json(['error' => 'Product not found or no changes made.', 'status' => 409]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  SINGLE DELETE — only blocked if a branch product with non-zero stock
    //  exists. Everything else (zero-stock branch rows, inventory log
    //  history) is cleaned up automatically so the FK never gets in the way.
    // ─────────────────────────────────────────────────────────────────────────

    public function deleteBaseproduct(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:tenant.retail_base_products,id',
        ]);

        $hasStock = DB::connection('tenant')
            ->table('retail_branch_products')
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
            // retail_inventory_logs.product_id is ON DELETE NO ACTION — must be
            // cleared manually. retail_branch_products is ON DELETE CASCADE so
            // it's removed automatically, but we drop it explicitly here too
            // for a predictable, single-pass cleanup.
            DB::connection('tenant')->table('retail_inventory_logs')->where('product_id', $request->id)->delete();
            DB::connection('tenant')->table('retail_branch_products')->where('base_product_id', $request->id)->delete();

            return DB::connection('tenant')->table('retail_base_products')->where('id', $request->id)->delete();
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
            'ids.*' => 'required|integer|exists:tenant.retail_base_products,id',
        ]);

        $allIds = array_values(array_unique(array_map('intval', $request->ids)));

        $stockedIds = DB::connection('tenant')
            ->table('retail_branch_products')
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
                DB::connection('tenant')->table('retail_inventory_logs')->whereIn('product_id', $safeIds)->delete();
                DB::connection('tenant')->table('retail_branch_products')->whereIn('base_product_id', $safeIds)->delete();

                return DB::connection('tenant')->table('retail_base_products')->whereIn('id', $safeIds)->delete();
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

        $products = DB::connection('tenant')->table('retail_base_products')->whereIn('id', $request->ids)->get();
        $label    = $request->is_product ? 'marked as Product' : 'marked as Service';
        $count    = $products->count();

        $supplierNamesMap = $this->fetchSupplierNamesMap($products->pluck('supplier')->toArray());
        $formatted = $products->map(fn($p) => $this->formatProductWithLookup($p, $supplierNamesMap))->values()->toArray();

        return response()->json([
            'success'  => $count . ' item' . ($count > 1 ? 's' : '') . ' ' . $label . ' successfully.',
            'status'   => 201,
            'products' => $formatted,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  BULK SUPPLIER — takes a supplier ID, not a name
    // ─────────────────────────────────────────────────────────────────────────

    public function bulkSupplierBaseproducts(Request $request)
    {
        $request->validate([
            'ids'      => 'required|array',
            'ids.*'    => 'required|integer|exists:tenant.retail_base_products,id',
            'supplier' => 'required|integer|exists:tenant.suppliers,id',
        ]);

        $supplierId = (int) $request->supplier;

        DB::connection('tenant')
            ->table('retail_base_products')
            ->whereIn('id', $request->ids)
            ->update([
                'supplier'   => $supplierId, // supplier ID only — never a name
                'updated_at' => now(),
            ]);

        $products = DB::connection('tenant')->table('retail_base_products')->whereIn('id', $request->ids)->get();
        $count    = $products->count();

        $supplierNamesMap = $this->fetchSupplierNamesMap($products->pluck('supplier')->toArray());
        $formatted = $products->map(fn($p) => $this->formatProductWithLookup($p, $supplierNamesMap))->values()->toArray();

        return response()->json([
            'success'  => 'Supplier updated for ' . $count . ' product' . ($count > 1 ? 's' : '') . '.',
            'status'   => 201,
            'products' => $formatted,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  CSV IMPORT
    //
    //  The client (baseproducts.blade.php) now:
    //    1. Parses the whole CSV file itself with a proper quoted-CSV parser.
    //    2. Validates every row at parse time and splits them into
    //       validRows / invalidRows, caching both to localStorage so an
    //       accidental modal close doesn't lose the parse.
    //    3. Uploads ONLY validRows to this endpoint, in small sequential
    //       chunks (BP_CSV_UPLOAD_CHUNK_SIZE rows per request), tagging
    //       each request with a client-generated batch_id plus
    //       chunk_index/total_chunks so requests are traceable to one
    //       import run and one chunk position within it.
    //
    //  This endpoint therefore never sees a raw file and never parses CSV
    //  text — it just needs to safely persist whatever JSON rows arrive,
    //  chunk-safe, without ever aborting the whole request because one row
    //  is bad.
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Step 2 of the wizard: list suppliers for the dropdown, filtered to
     * the category currently selected in the page's filter bar.
     */
    public function listSuppliersForImport(Request $request)
    {
        $request->validate([
            'category_id' => 'required|integer|exists:tenant.categories,id',
        ]);

        $suppliers = DB::connection('tenant')
            ->table('suppliers')
            ->where('status', 'active')
            ->where('category', $request->category_id)
            ->orderBy('name')
            ->get(['id', 'name', 'category']);

        return response()->json(['suppliers' => $suppliers]);
    }

    /**
     * Receives ONE chunk of already-parsed, already-validated rows from the
     * client and persists it. Called repeatedly (once per chunk) by the
     * browser's bpUploadRowsChunked() for a single CSV import run.
     */
    public function uploadBaseproductsCsv(Request $request)
    {
        $request->validate([
            'rows'                => 'required|array|min:1|max:' . self::CHUNK_SIZE,
            'rows.*.name'         => 'required|string|max:255',
            'rows.*.code'         => 'nullable|string|max:100',
            'rows.*.unit'         => 'nullable|string|max:50',
            'rows.*.cost_price'   => 'nullable',
            'rows.*.selling_price'=> 'nullable',
            'supplier_id'         => 'required|integer|exists:tenant.suppliers,id',
            'batch_id'            => 'nullable|string|max:64',
            'chunk_index'         => 'nullable|integer|min:1',
            'total_chunks'        => 'nullable|integer|min:1',
        ]);

        $supplier = DB::connection('tenant')->table('suppliers')
            ->where('id', $request->supplier_id)
            ->where('status', 'active')
            ->first();

        if (!$supplier) {
            return response()->json(['error' => 'Selected supplier is invalid or inactive.', 'status' => 422]);
        }

        // Normalize the rows the client already parsed & validated.
        // Deliberately tolerant here — a malformed value should be caught
        // and skipped per-row later, not blow up the whole chunk.
        $clean = [];
        foreach ($request->input('rows', []) as $r) {
            $name = trim(strip_tags((string) ($r['name'] ?? '')));
            if ($name === '') continue; // silently drop blank rows

            // Unit is kept EXACTLY as the user provided it — no whitelist,
            // no forcing. Only fall back to "Each" if it was left blank.
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

        // Each chunk is already small (client caps it), but extend the
        // execution window slightly as a safety valve on slow hosts.
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

    /**
     * Inserts rows in bounded sub-chunks (self::CHUNK_SIZE). For each
     * sub-chunk:
     *  - duplicate name/code checks only query the DB for the names/codes
     *    IN THAT SUB-CHUNK (bounded whereIn), never the whole catalogue —
     *    so memory use and query size stay flat regardless of how big the
     *    products table gets.
     *  - the sub-chunk is inserted in one bulk insert() for speed. If that
     *    throws for any reason, we fall back to inserting row-by-row inside
     *    a try/catch, so one bad row is caught and recorded in
     *    $failedRows while every other row in the sub-chunk still saves —
     *    nothing aborts the batch.
     */
    private function processCsvRowsChunked(array $rows, $supplier): array
    {
        $created      = 0;
        $skipped      = 0;
        $skippedNames = [];
        $failedRows   = [];

        // Tracks names/codes already staged or inserted during THIS request,
        // so duplicates within the same chunk are caught even before hitting the DB.
        $seenNames = [];
        $seenCodes = [];

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $rowsChunk) {

            $chunkNamesLower = array_map(fn($r) => strtolower(trim($r['name'])), $rowsChunk);
            $existingNamesInDb = DB::connection('tenant')
                ->table('retail_base_products')
                ->whereIn(DB::raw('LOWER(name)'), $chunkNamesLower)
                ->pluck('name')
                ->map(fn($n) => strtolower(trim($n)))
                ->flip()
                ->all();

            $chunkCodes = array_values(array_filter(array_map(fn($r) => $r['code'], $rowsChunk)));
            $existingCodesInDb = [];
            if (!empty($chunkCodes)) {
                $existingCodesInDb = DB::connection('tenant')
                    ->table('retail_base_products')
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
                    'name'          => $r['name'],
                    'description'   => null,
                    'code'          => $code,
                    'supplier'      => $supplier->id, // supplier ID only — never a name
                    'unit'          => $r['unit'],
                    'cost_price'    => $r['cost_price'],
                    'selling_price' => $r['selling_price'],
                    'is_product'    => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];

                $seenNames[$key] = true;
                if ($code !== null) $seenCodes[$code] = true;
            }

            if (empty($toInsert)) continue;

            try {
                DB::connection('tenant')->table('retail_base_products')->insert($toInsert);
                $created += count($toInsert);
            } catch (\Throwable $e) {
                // Something in this sub-chunk failed a DB-level constraint
                // (or similar). Retry row-by-row so we can isolate exactly
                // which row(s) failed instead of losing the whole sub-chunk.
                foreach ($toInsert as $row) {
                    try {
                        DB::connection('tenant')->table('retail_base_products')->insert($row);
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
            'base_product_id' => 'required|integer|exists:tenant.retail_base_products,id',
        ]);

        $base = DB::connection('tenant')
            ->table('retail_base_products')
            ->where('id', $request->base_product_id)
            ->first(['selling_price']);

        $baseSell = (float) ($base->selling_price ?? 0);

        $overrides = DB::connection('tenant')
            ->table('retail_branch_products as rbp')
            ->join('branches as b', 'b.id', '=', 'rbp.branch_id')
            ->where('rbp.base_product_id', $request->base_product_id)
            ->whereNotNull('rbp.selling_price')
            ->select('rbp.id', 'b.name as branch_name', 'rbp.selling_price')
            ->orderBy('b.name')
            ->get()
            ->filter(function ($row) use ($baseSell) {
                return abs((float) $row->selling_price - $baseSell) > 0.0001;
            })
            ->values();

        return response()->json(['overrides' => $overrides]);
    }
}