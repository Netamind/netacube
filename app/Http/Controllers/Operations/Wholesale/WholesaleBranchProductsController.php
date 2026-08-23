<?php
// Destination: app/Http/Controllers/Operations/Wholesale/WholesaleBranchProductsController.php
//
// Per-warehouse/branch stock for the wholesale catalogue. Mirrors the
// conventions established in WholesaleBaseProductsController (purifyNumber(),
// DB::connection('tenant'), formatted response payloads) and the retail
// branchproducts design (branch selector persisted via user_filters,
// select-all + bulk actions, search-existing-product add flow).
//
// AUDIT-TRAIL CONTRACT (audit log VIEWER intentionally not built yet):
//   - Every stock_quantity change (insert into a branch, manual stock edit,
//     delete) writes a row to wholesale_inventory_logs (stock_before/after,
//     operation_type, user + device snapshot).
//   - Every selling_price change (branch-level override) writes a row to
//     wholesale_price_changes (old_price/new_price, branch_id set, product
//     snapshot columns) — same table WholesaleBaseProductsController writes
//     to for base-catalogue price edits, so one log covers both.
// Both tables are written from day one so nothing is lost once the Audit
// Logs screen is eventually built — it will just read what's already here.

namespace App\Http\Controllers\Operations\Wholesale;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;

class WholesaleBranchProductsController extends Controller
{
    /** Ensure the supplied branch belongs to the wholesale sector. */
    private function wholesaleBranch(int $branchId)
    {
        return DB::connection('tenant')
            ->table('branches')
            ->where('id', $branchId)
            ->where('sector', 'Wholesale')
            ->first();
    }

    /** Ensure a supplier belongs to the wholesale catalogue and is active. */
    private function wholesaleSupplier(?int $supplierId)
    {
        if (!$supplierId) return null;

        return DB::connection('tenant')
            ->table('suppliers')
            ->where('id', $supplierId)
            ->where('sector', 'Wholesale')
            ->where('status', 'active')
            ->first();
    }

    // ─────────────────────────────────────────────────────────────────────
    //  VIEW
    // ─────────────────────────────────────────────────────────────────────

    public function showBranchproductsView(Request $request)
    {
        $branches = DB::connection('tenant')->table('branches')->where('sector', 'Wholesale')->orderBy('name')->get();

        $pref = DB::connection('tenant')
            ->table('user_filters')
            ->where('user_id', auth()->id())
            ->first();

        $selectedBranch = $request->query('branch_id') ?? ($pref->branch_id ?? null);
        $selectedBranch = $selectedBranch ? (int) $selectedBranch : null;
        if ($selectedBranch && !$this->wholesaleBranch($selectedBranch)) {
            $selectedBranch = null;
        }

        $branchProducts = collect();
        $shopValue = 0;

        if ($selectedBranch) {
            $branchProducts = DB::connection('tenant')
                ->table('wholesale_branch_products as wbp')
                ->join('wholesale_base_products as p', 'p.id', '=', 'wbp.base_product_id')
                ->leftJoin('suppliers as s', 's.id', '=', 'wbp.supplier_id')
                ->where('wbp.branch_id', $selectedBranch)
                ->orderBy('p.name')
                ->select(
                    'wbp.*',
                    'p.name', 'p.code', 'p.unit', 'p.pack_unit', 'p.units_per_pack',
                    'p.selling_price as base_selling_price', 'p.cost_price as base_cost_price',
                    's.name as supplier_name'
                )
                ->get();

            $shopValue = $branchProducts->sum(function ($row) {
                return (float) $row->stock_quantity * (float) ($row->selling_price ?? $row->base_selling_price ?? 0);
            });
        }

        return view('operations.wholesale.branchproducts', [
            'branches'       => $branches,
            'selectedBranch' => $selectedBranch,
            'branchProducts' => $branchProducts,
            'shopValue'      => $shopValue,
        ]);
    }

    // ── User filter persistence (which branch is selected) ────────────────

    public function updateUserFilters(Request $request)
    {
        $request->validate(['branch_id' => 'nullable|integer|exists:tenant.branches,id']);

        if ($request->filled('branch_id') && !$this->wholesaleBranch((int) $request->branch_id)) {
            return redirect()->back()->withErrors(['branch_id' => 'Please select a wholesale warehouse.']);
        }

        DB::connection('tenant')->table('user_filters')->updateOrInsert(
            ['user_id' => auth()->id()],
            ['branch_id' => $request->branch_id, 'updated_at' => now(), 'created_at' => now()]
        );

        return redirect()->back();
    }

    // ── Suppliers dropdown (for stock-level supplier override) ────────────

    public function listSuppliersForDropdown()
    {
        $suppliers = DB::connection('tenant')
            ->table('suppliers')
            ->where('status', 'active')
            ->where('sector', 'Wholesale')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json(['suppliers' => $suppliers]);
    }

    // ── Search base catalogue products not yet stocked at this branch ─────

    public function searchBaseproducts(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:tenant.branches,id',
            'q'         => 'nullable|string|max:255',
        ]);

        if (!$this->wholesaleBranch((int) $request->branch_id)) {
            return response()->json(['error' => 'Selected branch is not a wholesale warehouse.', 'status' => 422], 422);
        }

        $existingIds = DB::connection('tenant')
            ->table('wholesale_branch_products')
            ->where('branch_id', $request->branch_id)
            ->pluck('base_product_id');

        $query = DB::connection('tenant')
            ->table('wholesale_base_products')
            ->where('is_active', 1)
            ->whereNotIn('id', $existingIds);

        if ($request->filled('q')) {
            $term = trim($request->q);
            $query->where(function ($w) use ($term) {
                $w->where('name', 'like', "%{$term}%")
                  ->orWhere('code', 'like', "%{$term}%");
            });
        }

        $results = $query->orderBy('name')->limit(25)
            ->get(['id', 'name', 'code', 'unit', 'cost_price', 'selling_price']);

        return response()->json(['products' => $results]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  ADD PRODUCT TO BRANCH  (insert into wholesale_branch_products)
    // ─────────────────────────────────────────────────────────────────────

    public function insertBranchproduct(Request $request)
    {
        $request->validate([
            'branch_id'        => 'required|integer|exists:tenant.branches,id',
            'base_product_id'  => 'required|integer|exists:tenant.wholesale_base_products,id',
            'supplier_id'      => 'nullable|integer|exists:tenant.suppliers,id',
            'primary_barcode'  => 'nullable|string|max:100',
            'batch_number'     => 'nullable|string|max:100',
            'expiry_date'      => 'nullable|date',
            'cost_price'       => 'nullable|numeric|min:0',
            'selling_price'    => 'nullable|numeric|min:0',
            'stock_quantity'   => 'nullable|numeric|min:0',
            'reorder_point'    => 'nullable|numeric|min:0',
            'reorder_quantity' => 'nullable|numeric|min:0',
            'max_stock'        => 'nullable|numeric|min:0',
            'track_stock'      => 'nullable|boolean',
        ]);

        if (!$this->wholesaleBranch((int) $request->branch_id)) {
            return response()->json(['error' => 'Selected branch is not a wholesale warehouse.', 'status' => 422], 422);
        }
        if ($request->filled('supplier_id') && !$this->wholesaleSupplier((int) $request->supplier_id)) {
            return response()->json(['error' => 'Selected supplier is not an active wholesale supplier.', 'status' => 422], 422);
        }

        $already = DB::connection('tenant')
            ->table('wholesale_branch_products')
            ->where('branch_id', $request->branch_id)
            ->where('base_product_id', $request->base_product_id)
            ->exists();

        if ($already) {
            return response()->json(['error' => 'This product is already stocked at this branch. Edit it instead.', 'status' => 409]);
        }

        $stockQty = $this->purifyNumber($request->stock_quantity) ?? 0;

        $data = [
            'branch_id'        => (int) $request->branch_id,
            'base_product_id'  => (int) $request->base_product_id,
            'supplier_id'      => $request->supplier_id ? (int) $request->supplier_id : null,
            'primary_barcode'  => $request->primary_barcode ? trim($request->primary_barcode) : null,
            'batch_number'     => $request->batch_number ? trim($request->batch_number) : null,
            'expiry_date'      => $request->expiry_date ?: null,
            'cost_price'       => $this->purifyNumber($request->cost_price),
            'selling_price'    => $this->purifyNumber($request->selling_price),
            'stock_quantity'   => $stockQty,
            'reorder_point'    => $this->purifyNumber($request->reorder_point) ?? 0,
            'reorder_quantity' => $this->purifyNumber($request->reorder_quantity),
            'max_stock'        => $this->purifyNumber($request->max_stock),
            'track_stock'      => (int) ($request->track_stock ?? 1),
            'is_active'        => 1,
            'allow_negative_stock' => 0,
            'created_at'       => now(),
            'updated_at'       => now(),
        ];

        $insertId = DB::connection('tenant')->table('wholesale_branch_products')->insertGetId($data);

        if (!$insertId) {
            return response()->json(['error' => 'Failed to add product to branch.', 'status' => 500]);
        }

        // Opening stock movement — logged even at zero so the branch has a
        // day-one anchor row in the inventory trail.
        $this->logInventoryMovement([
            'product_id'    => $data['base_product_id'],
            'branch_id'     => $data['branch_id'],
            'batch_number'  => $data['batch_number'],
            'expiry_date'   => $data['expiry_date'],
            'stock_before'  => 0,
            'stock_after'   => $stockQty,
            'stock_change'  => $stockQty,
            'selling_price' => $data['selling_price'] ?? 0,
            'cost_price'    => $data['cost_price'] ?? 0,
            'operation_type'=> 'OpeningStock',
            'source_type'   => 'BranchProductsCRUD',
            'source_id'     => $insertId,
            'action_reason' => 'Product added to branch',
        ]);

        $row = $this->fetchFormattedRow($insertId);

        return response()->json([
            'success' => 'Product added to branch successfully.',
            'status'  => 201,
            'product' => $row,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  UPDATE  — edits a branch's stock row; logs price + stock movement
    // ─────────────────────────────────────────────────────────────────────

    public function updateBranchproduct(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:tenant.wholesale_branch_products,id',
        ]);

        $existing = DB::connection('tenant')->table('wholesale_branch_products')->where('id', $request->id)->first();
        if (!$existing) {
            return response()->json(['error' => 'Branch product not found.', 'status' => 404]);
        }
        if (!$this->wholesaleBranch((int) $existing->branch_id)) {
            return response()->json(['error' => 'This branch product does not belong to a wholesale warehouse.', 'status' => 422], 422);
        }

        $request->validate([
            'supplier_id'          => 'nullable|integer|exists:tenant.suppliers,id',
            'primary_barcode'      => 'nullable|string|max:100',
            'batch_number'         => 'nullable|string|max:100',
            'expiry_date'          => 'nullable|date',
            'cost_price'           => 'nullable|numeric|min:0',
            'selling_price'        => 'nullable|numeric|min:0',
            'stock_quantity'       => 'nullable|numeric|min:0',
            'reorder_point'        => 'nullable|numeric|min:0',
            'reorder_quantity'     => 'nullable|numeric|min:0',
            'max_stock'            => 'nullable|numeric|min:0',
            'track_stock'          => 'nullable|boolean',
            'is_active'            => 'nullable|boolean',
            'allow_negative_stock' => 'nullable|boolean',
            'stock_change_reason'  => 'nullable|string|max:255',
            'price_change_reason'  => 'nullable|string|max:255',
        ]);

        if ($request->has('supplier_id') && $request->supplier_id && !$this->wholesaleSupplier((int) $request->supplier_id)) {
            return response()->json(['error' => 'Selected supplier is not an active wholesale supplier.', 'status' => 422], 422);
        }

        $newSellingPrice = $request->has('selling_price') ? $this->purifyNumber($request->selling_price) : $existing->selling_price;
        $newStockQty     = $request->has('stock_quantity') ? ($this->purifyNumber($request->stock_quantity) ?? 0) : $existing->stock_quantity;

        $data = [
            'supplier_id'          => $request->has('supplier_id') ? ($request->supplier_id ? (int) $request->supplier_id : null) : $existing->supplier_id,
            'primary_barcode'      => $request->has('primary_barcode') ? ($request->primary_barcode ? trim($request->primary_barcode) : null) : $existing->primary_barcode,
            'batch_number'         => $request->has('batch_number') ? ($request->batch_number ? trim($request->batch_number) : null) : $existing->batch_number,
            'expiry_date'          => $request->has('expiry_date') ? ($request->expiry_date ?: null) : $existing->expiry_date,
            'cost_price'           => $request->has('cost_price') ? $this->purifyNumber($request->cost_price) : $existing->cost_price,
            'selling_price'        => $newSellingPrice,
            'stock_quantity'       => $newStockQty,
            'reorder_point'        => $request->has('reorder_point') ? ($this->purifyNumber($request->reorder_point) ?? 0) : $existing->reorder_point,
            'reorder_quantity'     => $request->has('reorder_quantity') ? $this->purifyNumber($request->reorder_quantity) : $existing->reorder_quantity,
            'max_stock'            => $request->has('max_stock') ? $this->purifyNumber($request->max_stock) : $existing->max_stock,
            'track_stock'          => $request->has('track_stock') ? (int) $request->track_stock : $existing->track_stock,
            'is_active'            => $request->has('is_active') ? (int) $request->is_active : $existing->is_active,
            'allow_negative_stock' => $request->has('allow_negative_stock') ? (int) $request->allow_negative_stock : $existing->allow_negative_stock,
            'updated_at'           => now(),
        ];

        $updated = DB::connection('tenant')->table('wholesale_branch_products')->where('id', $request->id)->update($data);

        // ── Price change log — compare effective prices so both transitions
        //    (base -> warehouse override and override -> base) are audited.
        $baseForPrice = DB::connection('tenant')
            ->table('wholesale_base_products')
            ->where('id', $existing->base_product_id)
            ->first(['selling_price']);
        $oldEffectivePrice = $existing->selling_price !== null
            ? (float) $existing->selling_price
            : (float) ($baseForPrice->selling_price ?? 0);
        $newEffectivePrice = $data['selling_price'] !== null
            ? (float) $data['selling_price']
            : (float) ($baseForPrice->selling_price ?? 0);

        if (round($oldEffectivePrice, 2) !== round($newEffectivePrice, 2)) {
            $this->logBranchPriceChange(
                $existing->base_product_id,
                $existing->branch_id,
                $oldEffectivePrice,
                $newEffectivePrice,
                $request->price_change_reason ?: 'Branch product price updated'
            );
        }

        // ── Stock movement log — only if the quantity actually moved ─────
        if (round((float) $existing->stock_quantity, 4) !== round((float) $data['stock_quantity'], 4)) {
            $this->logInventoryMovement([
                'product_id'    => $existing->base_product_id,
                'branch_id'     => $existing->branch_id,
                'batch_number'  => $data['batch_number'],
                'expiry_date'   => $data['expiry_date'],
                'stock_before'  => $existing->stock_quantity,
                'stock_after'   => $data['stock_quantity'],
                'stock_change'  => $data['stock_quantity'] - $existing->stock_quantity,
                'selling_price' => $data['selling_price'] ?? 0,
                'cost_price'    => $data['cost_price'] ?? 0,
                'operation_type'=> 'Adjustment',
                'source_type'   => 'BranchProductsCRUD',
                'source_id'     => $existing->id,
                'action_reason' => $request->stock_change_reason ?: 'Manual stock edit',
            ]);
        }

        if ($updated !== false) {
            return response()->json([
                'success' => 'Branch product updated successfully.',
                'status'  => 201,
                'product' => $this->fetchFormattedRow($request->id),
            ]);
        }

        return response()->json(['error' => 'No changes were made.', 'status' => 409]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  DELETE — single + bulk. Blocked if stock > 0 unless force=true;
    //  a forced delete logs the stock going to zero first so nothing is
    //  silently lost from the inventory trail.
    // ─────────────────────────────────────────────────────────────────────

    public function deleteBranchproduct(Request $request)
    {
        $request->validate([
            'id'    => 'required|integer|exists:tenant.wholesale_branch_products,id',
            'force' => 'nullable|boolean',
        ]);

        $row = DB::connection('tenant')->table('wholesale_branch_products')->where('id', $request->id)->first();
        if (!$row) {
            return response()->json(['error' => 'Branch product not found.', 'status' => 404]);
        }
        if (!$this->wholesaleBranch((int) $row->branch_id)) {
            return response()->json(['error' => 'This branch product does not belong to a wholesale warehouse.', 'status' => 422], 422);
        }

        if ((float) $row->stock_quantity > 0 && !$request->boolean('force')) {
            return response()->json([
                'error'          => 'This product still has stock at this branch.',
                'status'         => 409,
                'requires_force' => true,
                'stock_quantity' => $row->stock_quantity,
            ]);
        }

        if ((float) $row->stock_quantity > 0) {
            $this->logInventoryMovement([
                'product_id'    => $row->base_product_id,
                'branch_id'     => $row->branch_id,
                'batch_number'  => $row->batch_number,
                'expiry_date'   => $row->expiry_date,
                'stock_before'  => $row->stock_quantity,
                'stock_after'   => 0,
                'stock_change'  => -$row->stock_quantity,
                'selling_price' => $row->selling_price ?? 0,
                'cost_price'    => $row->cost_price ?? 0,
                'operation_type'=> 'WriteOff',
                'source_type'   => 'BranchProductsCRUD',
                'source_id'     => $row->id,
                'action_reason' => 'Stock cleared prior to removing product from branch',
            ]);
        }

        DB::connection('tenant')->table('wholesale_branch_products')->where('id', $request->id)->delete();

        return response()->json(['success' => 'Product removed from branch.', 'status' => 201]);
    }

    public function bulkDeleteBranchproducts(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'required|integer|exists:tenant.wholesale_branch_products,id',
            'force' => 'nullable|boolean',
        ]);

        $rows = DB::connection('tenant')->table('wholesale_branch_products')->whereIn('id', $request->ids)->get();
        if ($rows->isEmpty()) {
            return response()->json(['error' => 'No branch products found.', 'status' => 404], 404);
        }

        $invalidRows = $rows->first(fn ($r) => !$this->wholesaleBranch((int) $r->branch_id));
        if ($invalidRows) {
            return response()->json(['error' => 'One or more selected products do not belong to a wholesale warehouse.', 'status' => 422], 422);
        }

        $force   = $request->boolean('force');
        $blocked = $rows->filter(fn ($r) => (float) $r->stock_quantity > 0 && !$force);

        if ($blocked->isNotEmpty() && !$force) {
            return response()->json([
                'error'          => $blocked->count() . ' of the selected product(s) still have stock at this branch.',
                'status'         => 409,
                'requires_force' => true,
                'blocked_count'  => $blocked->count(),
            ]);
        }

        foreach ($rows as $row) {
            if ((float) $row->stock_quantity > 0) {
                $this->logInventoryMovement([
                    'product_id'    => $row->base_product_id,
                    'branch_id'     => $row->branch_id,
                    'batch_number'  => $row->batch_number,
                    'expiry_date'   => $row->expiry_date,
                    'stock_before'  => $row->stock_quantity,
                    'stock_after'   => 0,
                    'stock_change'  => -$row->stock_quantity,
                    'selling_price' => $row->selling_price ?? 0,
                    'cost_price'    => $row->cost_price ?? 0,
                    'operation_type'=> 'WriteOff',
                    'source_type'   => 'BranchProductsCRUD',
                    'source_id'     => $row->id,
                    'action_reason' => 'Stock cleared during bulk removal from branch',
                ]);
            }
        }

        $deleted = DB::connection('tenant')->table('wholesale_branch_products')->whereIn('id', $rows->pluck('id'))->delete();

        return response()->json([
            'success' => $deleted . ' product' . ($deleted !== 1 ? 's' : '') . ' removed from branch.',
            'status'  => 201,
            'deleted' => $deleted,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  BULK STATUS / TRACK-STOCK TOGGLES
    // ─────────────────────────────────────────────────────────────────────

    public function bulkStatusBranchproducts(Request $request)
    {
        $request->validate([
            'ids'       => 'required|array',
            'ids.*'     => 'required|integer|exists:tenant.wholesale_branch_products,id',
            'is_active' => 'required|boolean',
        ]);

        $rows = DB::connection('tenant')->table('wholesale_branch_products')->whereIn('id', $request->ids)->get(['id', 'branch_id']);
        if ($rows->isEmpty()) {
            return response()->json(['error' => 'No branch products found.', 'status' => 404], 404);
        }
        if ($rows->contains(fn ($r) => !$this->wholesaleBranch((int) $r->branch_id))) {
            return response()->json(['error' => 'One or more selected products do not belong to a wholesale warehouse.', 'status' => 422], 422);
        }

        DB::connection('tenant')->table('wholesale_branch_products')->whereIn('id', $rows->pluck('id'))
            ->update(['is_active' => (int) $request->is_active, 'updated_at' => now()]);

        return $this->respondWithFormattedRows($request->ids, $request->is_active ? 'marked Active' : 'marked Inactive');
    }

    public function bulkTrackStockBranchproducts(Request $request)
    {
        $request->validate([
            'ids'         => 'required|array',
            'ids.*'       => 'required|integer|exists:tenant.wholesale_branch_products,id',
            'track_stock' => 'required|boolean',
        ]);

        $rows = DB::connection('tenant')->table('wholesale_branch_products')->whereIn('id', $request->ids)->get(['id', 'branch_id']);
        if ($rows->isEmpty()) {
            return response()->json(['error' => 'No branch products found.', 'status' => 404], 404);
        }
        if ($rows->contains(fn ($r) => !$this->wholesaleBranch((int) $r->branch_id))) {
            return response()->json(['error' => 'One or more selected products do not belong to a wholesale warehouse.', 'status' => 422], 422);
        }

        DB::connection('tenant')->table('wholesale_branch_products')->whereIn('id', $rows->pluck('id'))
            ->update(['track_stock' => (int) $request->track_stock, 'updated_at' => now()]);

        return $this->respondWithFormattedRows($request->ids, $request->track_stock ? 'set to track stock' : 'set to not track stock');
    }

    // ─────────────────────────────────────────────────────────────────────
    //  HELPERS
    // ─────────────────────────────────────────────────────────────────────

    private function purifyNumber($value): ?float
    {
        if ($value === null || $value === '') return null;
        $value = preg_replace('/[^0-9.\-]/', '', (string) $value);
        return $value === '' ? null : (float) $value;
    }

    private function fetchFormattedRow(int $id): array
    {
        $row = DB::connection('tenant')
            ->table('wholesale_branch_products as wbp')
            ->join('wholesale_base_products as p', 'p.id', '=', 'wbp.base_product_id')
            ->leftJoin('suppliers as s', 's.id', '=', 'wbp.supplier_id')
            ->where('wbp.id', $id)
            ->select(
                'wbp.*',
                'p.name', 'p.code', 'p.unit', 'p.pack_unit', 'p.units_per_pack',
                'p.selling_price as base_selling_price', 'p.cost_price as base_cost_price',
                's.name as supplier_name'
            )
            ->first();

        return [
            'id'                => $row->id,
            'row'               => 'row' . $row->id,
            'branch_id'         => $row->branch_id,
            'base_product_id'   => $row->base_product_id,
            'name'              => $row->name,
            'code'              => $row->code,
            'unit'              => $row->unit,
            'pack_unit'         => $row->pack_unit,
            'units_per_pack'    => $row->units_per_pack,
            'supplier_id'       => $row->supplier_id,
            'supplier_name'     => $row->supplier_name,
            'primary_barcode'   => $row->primary_barcode,
            'batch_number'      => $row->batch_number,
            'expiry_date'       => $row->expiry_date,
            'cost_price'        => $row->cost_price,
            'selling_price'     => $row->selling_price,
            'base_cost_price'   => $row->base_cost_price,
            'base_selling_price'=> $row->base_selling_price,
            'stock_quantity'    => $row->stock_quantity,
            'reorder_point'     => $row->reorder_point,
            'reorder_quantity'  => $row->reorder_quantity,
            'max_stock'         => $row->max_stock,
            'track_stock'       => (int) $row->track_stock,
            'is_active'         => (int) $row->is_active,
            'allow_negative_stock' => (int) $row->allow_negative_stock,
        ];
    }

    /**
     * Fetch one branch product row in the same shape as the insert/update
     * responses. Used by the front-end to refresh a row after an action
     * (e.g. editing the base product) whose own response doesn't carry the
     * branch-specific fields, so a full page reload can be avoided.
     */
    public function getRow(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:tenant.wholesale_branch_products,id',
        ]);

        $row = DB::connection('tenant')->table('wholesale_branch_products')->where('id', $request->id)->first(['branch_id']);
        if (!$row || !$this->wholesaleBranch((int) $row->branch_id)) {
            return response()->json(['error' => 'Branch product not found.', 'status' => 404], 404);
        }

        return response()->json(['product' => $this->fetchFormattedRow((int) $request->id)]);
    }

    private function respondWithFormattedRows(array $ids, string $verb): \Illuminate\Http\JsonResponse
    {
        $rows = collect($ids)->map(fn ($id) => $this->fetchFormattedRow((int) $id))->values();

        return response()->json([
            'success'  => $rows->count() . ' product' . ($rows->count() > 1 ? 's' : '') . ' ' . $verb . '.',
            'status'   => 201,
            'products' => $rows,
        ]);
    }

    private function parseDeviceType(string $ua): string
    {
        $ua = strtolower($ua);
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad')) return 'tablet';
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) return 'mobile';
        return 'desktop';
    }

    private function parseBrowser(string $ua): string
    {
        if (str_contains($ua, 'Edg')) return 'Edge';
        if (str_contains($ua, 'OPR') || str_contains($ua, 'Opera')) return 'Opera';
        if (str_contains($ua, 'Chrome')) return 'Chrome';
        if (str_contains($ua, 'Firefox')) return 'Firefox';
        if (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) return 'Safari';
        if (str_contains($ua, 'MSIE') || str_contains($ua, 'Trident')) return 'IE';
        return 'Other';
    }

    private function parseOS(string $ua): string
    {
        if (str_contains($ua, 'Windows NT')) return 'Windows';
        if (str_contains($ua, 'Mac OS X')) return 'macOS';
        if (str_contains($ua, 'Android')) return 'Android';
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) return 'iOS';
        if (str_contains($ua, 'Linux')) return 'Linux';
        return 'Other';
    }

    /**
     * Writes one row to wholesale_inventory_logs. Called on every stock
     * change so the movement trail is complete before the Audit Logs
     * viewer exists to read it.
     */
    private function logInventoryMovement(array $movement): void
    {
        $user = auth()->user();
        $agent = request()->userAgent() ?? '';

        DB::connection('tenant')->table('wholesale_inventory_logs')->insert(array_merge([
            'user_id'           => auth()->id(),
            'user_full_name'    => $user->name ?? null,
            'user_email'        => $user->email ?? null,
            'user_role'         => $user->role ?? null,
            'user_device_details' => $agent,
            'ip_address'        => request()->ip(),
            'device_type'       => $this->parseDeviceType($agent),
            'browser'           => $this->parseBrowser($agent),
            'operating_system'  => $this->parseOS($agent),
            'session_id'        => request()->session()->getId(),
            'log_date'          => now()->toDateString(),
            'log_time'          => now()->toTimeString(),
            'created_at'        => now(),
            'updated_at'        => now(),
        ], $movement));
    }

    /**
     * Writes one row to wholesale_price_changes for a branch-level price
     * override. base-catalogue price changes are logged the same way from
     * WholesaleBaseProductsController::updateBaseproduct — same table.
     */
    private function logBranchPriceChange(int $baseProductId, int $branchId, $oldPrice, $newPrice, ?string $reason): void
    {
        $product = DB::connection('tenant')->table('wholesale_base_products')->where('id', $baseProductId)->first(['name', 'code', 'unit']);
        $branch  = DB::connection('tenant')->table('branches')->where('id', $branchId)->first(['name']);

        DB::connection('tenant')->table('wholesale_price_changes')->insert([
            'base_product_id' => $baseProductId,
            'branch_id'       => $branchId,
            'changed_by'      => auth()->id(),
            'product_name'    => $product->name ?? '',
            'product_code'    => $product->code ?? null,
            'product_unit'    => $product->unit ?? 'Each',
            'branch_name'     => $branch->name ?? null,
            'old_price'       => $oldPrice,
            'new_price'       => $newPrice,
            'reason'          => $reason ? trim($reason) : null,
            'change_date'     => now()->toDateString(),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }
}