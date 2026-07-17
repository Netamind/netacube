<?php

namespace App\Http\Controllers\Sales\Retail;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Carbon\Carbon;
use DB;
use Auth;

class RetailOrdersController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════
    // VIEWS
    // ══════════════════════════════════════════════════════════════════════
    //
    // Each category still has two dedicated routes/views: an Action Area
    // (product table you order against) and a History (everything ordered
    // so far). There is no more "batch" underneath either of them — a
    // product ordered for a branch+category is just one row in
    // retail_orders that keeps getting updated. Supplier is the only thing
    // that scopes what a page/download/share shows.

    public function showRegularOrdersView()
    {
        return view('sales.retail.orders-regular');
    }

    public function showEmergencyOrdersView()
    {
        return view('sales.retail.orders-emergency');
    }

    public function showRareOrdersView()
    {
        return view('sales.retail.orders-rare');
    }

    public function showRegularHistoryView()
    {
        return view('sales.retail.orders-regular-history');
    }

    public function showEmergencyHistoryView()
    {
        return view('sales.retail.orders-emergency-history');
    }

    public function showRareHistoryView()
    {
        return view('sales.retail.orders-rare-history');
    }

    // ══════════════════════════════════════════════════════════════════════
    // SUPPLIER SCOPING
    // Only suppliers whose sector is Retail AND whose category matches the
    // user's branch category are allowed to appear/order against.
    // ══════════════════════════════════════════════════════════════════════

    private function resolveBranchId(): ?int
    {
        $user = Auth::user();
        $pref = DB::connection('tenant')->table('user_filters')->where('user_id', $user->id)->first();

        return ($pref && $pref->branch_id) ? $pref->branch_id : $user->branch;
    }

    private function getBranchCategory(int $branchId): ?int
    {
        return DB::connection('tenant')->table('branches')->where('id', $branchId)->value('category');
    }

    private function eligibleSuppliersQuery($branch)
    {
        return DB::connection('tenant')
            ->table('suppliers')
            ->where('status', 'active')
            ->where('sector', 'Retail')
            ->where('category', $branch->category)
            ->orderBy('name');
    }

    private function eligibleSupplierIds(int $branchId): array
    {
        $branchCategory = $this->getBranchCategory($branchId);
        if (!$branchCategory) {
            return [];
        }

        return DB::connection('tenant')
            ->table('suppliers')
            ->where('status', 'active')
            ->where('sector', 'Retail')
            ->where('category', $branchCategory)
            ->pluck('id')
            ->all();
    }

    /**
     * Powers the header branch selector on all six order pages.
     */
    public function getBranches()
    {
        $branches = DB::connection('tenant')->table('branches')->orderBy('name')->get(['id', 'name']);
        $branchId = $this->resolveBranchId();
        $branch   = $branchId ? DB::connection('tenant')->table('branches')->find($branchId) : null;

        return response()->json([
            'branches' => $branches,
            'branchId' => $branchId,
            'branch'   => $branch,
        ]);
    }

    /**
     * Suppliers eligible for the ordering supplier picker — used for the
     * Regular/Emergency filter bar AND the Rare "supplier (optional)"
     * dropdown, so the same rule applies everywhere on this page.
     */
    public function getSuppliers()
    {
        $branchId = $this->resolveBranchId();
        if (!$branchId) {
            return response()->json(['branch' => null, 'suppliers' => []]);
        }

        $branch = DB::connection('tenant')->table('branches')->find($branchId);
        if (!$branch) {
            return response()->json(['branch' => null, 'suppliers' => []]);
        }

        $suppliers = $this->eligibleSuppliersQuery($branch)->get(['id', 'name']);

        return response()->json([
            'branch'    => ['id' => $branch->id, 'name' => $branch->name],
            'suppliers' => $suppliers,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // THE CURRENT ORDER STATE — no more batches. For a catalog product
    // (Regular/Emergency), retail_orders has AT MOST ONE row per
    // branch+category+product_id: saving a quantity always upserts that
    // one row, which is why the ordering page's qty box can simply prefill
    // from it and why history only ever shows "the latest state" for that
    // product rather than a log of past orders. Rare items have no
    // product_id to key on, so every quick-add is its own permanent row.
    // ══════════════════════════════════════════════════════════════════════

    private function findExistingLine(int $branchId, string $category, int $productId)
    {
        return DB::connection('tenant')->table('retail_orders')
            ->where('branch_id', $branchId)
            ->where('category', $category)
            ->where('product_id', $productId)
            ->first();
    }

    private function currentOrderSummary(int $branchId, string $category): array
    {
        $lines = DB::connection('tenant')->table('retail_orders')
            ->where('branch_id', $branchId)->where('category', $category)->get();

        return [
            'line_count'   => $lines->count(),
            'all_synced'   => $lines->isNotEmpty() && $lines->every(fn ($l) => !$l->synced_offline),
            'has_unsynced' => $lines->contains(fn ($l) => (bool) $l->synced_offline),
        ];
    }

    /**
     * Shared write path for a single order line — used both by the live
     * auto-save endpoint (saveOrderLine, called while online) and by the
     * offline sync endpoint (syncOfflineOrders, called once connectivity
     * returns). Upserts by (branch_id, category, product_id) for catalog
     * products; Rare's custom items have no product_id, so each call
     * inserts a new line and relies on client_uuid alone for offline-sync
     * de-duplication.
     */
    private function applyLineSave(array $line, int $branchId, int $userId, Request $request, bool $syncedOffline): ?array
    {
        $category = $line['category'] ?? 'Regular';
        if (!in_array($category, ['Regular', 'Emergency', 'Rare'], true)) {
            return null;
        }

        $date       = $line['date'] ?? now()->toDateString();
        $productId  = (!empty($line['product_id'])) ? (int) $line['product_id'] : null;
        $qtyRaw     = trim((string) ($line['quantity'] ?? ''));
        $clientUuid = $line['client_uuid'] ?? null;

        // Idempotency for offline-queued saves: if this exact line already made it in, skip it.
        if ($clientUuid) {
            $existingByUuid = DB::connection('tenant')->table('retail_orders')->where('client_uuid', $clientUuid)->first();
            if ($existingByUuid) {
                return array_merge($this->currentOrderSummary($branchId, $category), ['line_id' => $existingByUuid->id]);
            }
        }

        // Blank quantity on a catalog product means "remove this line" rather than save a zero.
        if ($qtyRaw === '' && $productId) {
            DB::connection('tenant')->table('retail_orders')
                ->where('branch_id', $branchId)->where('category', $category)
                ->where('product_id', $productId)->delete();

            return $this->currentOrderSummary($branchId, $category);
        }

        if ($qtyRaw === '') {
            return null;
        }

        $price  = (float) ($line['price'] ?? 0);
        $isRare = $category === 'Rare';

        $values = [
            'date'           => $date,
            'branch_id'      => $branchId,
            'category'       => $category,
            'supplier_id'    => $line['supplier_id'] ?? null,
            'product_id'     => $isRare ? null : $productId,
            'is_custom'      => $isRare,
            'product_name'   => trim($line['product_name'] ?? ''),
            'units'          => $line['units'] ?? null,
            'quantity'       => $qtyRaw,
            'price'          => $price,
            'stock_at_order' => $isRare ? null : ($line['stock_quantity'] ?? null),
            'ordered_by'     => $userId,
            'client_uuid'    => $clientUuid,
            'device_name'    => $request->input('device_name'),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'synced_offline' => $syncedOffline,
            'updated_at'     => now(),
        ];

        $lineId = null;

        if (!$isRare && $productId) {
            $existing = $this->findExistingLine($branchId, $category, $productId);

            if ($existing) {
                // Deliberately NOT touching `status` here — re-saving a
                // quantity shouldn't silently revert a line an admin
                // already marked "received"/"ordered" back to pending.
                DB::connection('tenant')->table('retail_orders')->where('id', $existing->id)->update($values);
                $lineId = $existing->id;
            } else {
                $values['status']     = 'pending';
                $values['created_at'] = now();
                $lineId = DB::connection('tenant')->table('retail_orders')->insertGetId($values);
            }
        } else {
            $values['status']     = 'pending';
            $values['created_at'] = now();
            $lineId = DB::connection('tenant')->table('retail_orders')->insertGetId($values);
        }

        return array_merge($this->currentOrderSummary($branchId, $category), ['line_id' => $lineId]);
    }

    /**
     * Called on blur/Enter from the product table's quantity input
     * (Regular/Emergency) and directly from the Rare "Add" modal — Rare
     * has no offline queue, so this is its only write path.
     */
    public function saveOrderLine(Request $request)
    {
        $request->validate([
            'category'       => 'required|in:Regular,Emergency,Rare',
            'product_id'     => 'nullable|integer',
            'product_name'   => 'required|string|max:255',
            'units'          => 'nullable|string|max:50',
            'quantity'       => 'nullable|string|max:60',
            'price'          => 'nullable|numeric|min:0', // Rare no longer collects a price — defaults to 0 in applyLineSave
            'stock_quantity' => 'nullable|numeric',
            'supplier_id'    => 'nullable|integer',
        ]);

        $user     = Auth::user();
        // Was `$user->branch` — ignored the saved branch-filter override
        // (user_filters.branch_id) that every other read on this page
        // respects. When a user's filter pointed at a different branch
        // than their assigned one, a line saved here landed on the wrong
        // branch_id and simply never showed up in "Items on This Order" /
        // History after "uploading" — looked like the upload silently
        // failed even though it succeeded, just against the wrong branch.
        $branchId = $this->resolveBranchId();
        if (!$branchId) {
            return response()->json(['status' => 'error', 'message' => 'Your account has no branch assigned.'], 422);
        }

        $result = $this->applyLineSave(
            array_merge($request->all(), ['date' => now()->toDateString()]),
            $branchId, $user->id, $request, false
        );

        return response()->json(array_merge(['status' => 'success'], $result ?? [
            'line_count' => 0, 'all_synced' => false, 'has_unsynced' => false,
        ]));
    }

    /**
     * Powers the "Current Order" tab of the Download modal on both the
     * ordering pages AND the history pages — every line currently sitting
     * in retail_orders for this branch+category, plus a per-supplier
     * breakdown so a (possibly multi-supplier) set can be downloaded or
     * shared one supplier at a time instead of only as one flat PDF.
     */
    public function getCurrentOrderLines(Request $request)
    {
        $request->validate(['category' => 'required|in:Regular,Emergency,Rare']);

        $branchId = $this->resolveBranchId();
        if (!$branchId) {
            return response()->json(['lines' => [], 'suppliers' => [], 'summary' => ['line_count' => 0]]);
        }

        $lines = DB::connection('tenant')->table('retail_orders as l')
            ->leftJoin('users as u', 'u.id', '=', 'l.ordered_by')
            ->where('l.branch_id', $branchId)
            ->where('l.category', $request->category)
            ->orderByDesc('l.updated_at')
            ->orderByDesc('l.id')
            ->select('l.*', 'u.name as ordered_by_name')
            ->get();

        return response()->json([
            'lines'     => $lines,
            'suppliers' => $this->supplierBreakdown($lines),
            'summary'   => $this->currentOrderSummary($branchId, $request->category),
        ]);
    }

    /**
     * Distinct suppliers represented in a set of order lines, each with
     * its own line count — powers the supplier picker in the
     * Download/Share tab.
     */
    private function supplierBreakdown($lines): array
    {
        $supplierIds = $lines->pluck('supplier_id')->filter()->unique()->values();
        if ($supplierIds->isEmpty()) {
            return [];
        }

        $suppliers = DB::connection('tenant')->table('suppliers')
            ->whereIn('id', $supplierIds)->orderBy('name')->get(['id', 'name']);

        return $suppliers->map(function ($s) use ($lines) {
            $supplierLines = $lines->where('supplier_id', $s->id);
            return [
                'id'         => $s->id,
                'name'       => $s->name,
                'line_count' => $supplierLines->count(),
            ];
        })->values()->all();
    }

    /**
     * "Clear" button — wipes every current line for this branch+category
     * (a fresh start), same "give up entirely" action the old clearOpenBatch
     * offered, just without a batch id.
     */
    public function clearCurrentOrder(Request $request)
    {
        $request->validate(['category' => 'required|in:Regular,Emergency,Rare']);

        $branchId = $this->resolveBranchId();
        if (!$branchId) {
            return response()->json(['status' => 'error', 'message' => 'No branch assigned.'], 422);
        }

        DB::connection('tenant')->table('retail_orders')
            ->where('branch_id', $branchId)->where('category', $request->category)->delete();

        return response()->json(['status' => 'success', 'line_count' => 0]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // CREATE — OFFLINE SYNC
    // ══════════════════════════════════════════════════════════════════════

    public function syncOfflineOrders(Request $request)
    {
        $request->validate(['data' => 'required|string']);

        $queued = json_decode($request->data, true);
        if (!is_array($queued) || empty($queued)) {
            return response()->json([]);
        }

        $user     = Auth::user();
        // Same fix as saveOrderLine — must respect the saved branch-filter
        // override, not just the user's assigned branch, or lines upload
        // "successfully" but land on a branch nothing on screen is
        // querying, so they never appear.
        $branchId = $this->resolveBranchId();
        $failed   = [];

        if (!$branchId) {
            return response()->json($queued);
        }

        foreach ($queued as $line) {
            try {
                if (empty($line['client_uuid'])) {
                    $line['_error'] = 'Missing client_uuid.';
                    $failed[] = $line;
                    continue;
                }
                $this->applyLineSave($line, $branchId, $user->id, $request, true);
            } catch (\Throwable $e) {
                \Log::error('syncOfflineOrders line failed: ' . $e->getMessage(), ['line' => $line]);
                // Surfaced to the client so a failed upload is diagnosable
                // from the browser instead of only appearing in server
                // logs — the old version returned the line with no
                // indication of what went wrong.
                $line['_error'] = $e->getMessage();
                $failed[] = $line;
            }
        }

        return response()->json($failed);
    }

    // ══════════════════════════════════════════════════════════════════════
    // READ — HISTORY
    // The category is always locked to the current page's category (the
    // tab the user is on). Newest first, filtered only by supplier — no
    // more batch grouping, no date/status filters.
    // ══════════════════════════════════════════════════════════════════════

    public function indexOrders(Request $request)
    {
        $request->validate(['category' => 'required|in:Regular,Emergency,Rare']);

        $branchId = $this->resolveBranchId();

        $query = DB::connection('tenant')
            ->table('retail_orders as l')
            ->leftJoin('users as u', 'u.id', '=', 'l.ordered_by')
            // Live stock — this is "Current Qty" in the history view. It is
            // NOT l.stock_at_order (that's the "Qty@Order" snapshot column)
            // — it's whatever retail_branch_products says right now.
            ->leftJoin('retail_branch_products as rbp', function ($j) {
                $j->on('rbp.base_product_id', '=', 'l.product_id')->on('rbp.branch_id', '=', 'l.branch_id');
            })
            ->where('l.category', $request->category)
            ->when($branchId, fn ($q) => $q->where('l.branch_id', $branchId))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('l.supplier_id', $request->supplier_id))
            ->orderByDesc('l.updated_at')
            ->orderByDesc('l.id')
            ->select(
                'l.*',
                'u.name as ordered_by_name',
                DB::raw('rbp.stock_quantity as current_qty')
            );

        return response()->json(['lines' => $query->get()]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // UPDATE
    // ══════════════════════════════════════════════════════════════════════

    public function updateOrderLineStatus(Request $request)
    {
        $request->validate([
            'id'     => 'required|integer|exists:tenant.retail_orders,id',
            'status' => 'required|in:pending,ordered,received,cancelled',
        ]);

        DB::connection('tenant')->table('retail_orders')
            ->where('id', $request->id)
            ->update(['status' => $request->status, 'updated_at' => now()]);

        return response()->json(['status' => 'success', 'message' => 'Status updated.']);
    }

    public function updateOrderLine(Request $request)
    {
        $request->validate([
            'id'           => 'required|integer|exists:tenant.retail_orders,id',
            'product_name' => 'required|string|max:255',
            'units'        => 'nullable|string|max:50',
            'quantity'     => 'required|string|max:60',
            'price'        => 'nullable|numeric|min:0', // Rare has no price field — defaults to 0
            'supplier_id'  => 'nullable|integer',
        ]);

        $qtyRaw = trim($request->quantity);
        $price  = (float) ($request->price ?? 0);

        $updated = DB::connection('tenant')->table('retail_orders')
            ->where('id', $request->id)
            ->update([
                'product_name' => trim($request->product_name),
                'units'        => $request->units,
                'quantity'     => $qtyRaw,
                'price'        => $price,
                'supplier_id'  => $request->supplier_id,
                'updated_at'   => now(),
            ]);

        if (!$updated) {
            return response()->json(['status' => 'error', 'message' => 'Order line not found.'], 404);
        }

        return response()->json(['status' => 'success', 'message' => 'Order line updated.']);
    }

    // ══════════════════════════════════════════════════════════════════════
    // DELETE
    // ══════════════════════════════════════════════════════════════════════

    public function deleteOrderLine(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        $deleted = DB::connection('tenant')->table('retail_orders')->where('id', $request->id)->delete();

        if (!$deleted) {
            return response()->json(['status' => 'error', 'message' => 'Order line not found.'], 404);
        }

        return response()->json(['status' => 'success', 'message' => 'Order line deleted.']);
    }

    public function bulkDeleteOrderLines(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'required|integer']);

        $deleted = DB::connection('tenant')->table('retail_orders')->whereIn('id', $request->ids)->delete();

        if ($deleted > 0) {
            return response()->json(['status' => 'success', 'message' => 'Selected lines deleted.']);
        }

        return response()->json(['status' => 'error', 'message' => 'No lines found.'], 404);
    }

    /**
     * Delete-all for a History page's blue-bar delete button. Scoped to the
     * current branch (server-resolved, same rule as everywhere else) +
     * category + whatever supplier selection is active on screen —
     * supplier_id omitted/empty means "All Suppliers", matching the same
     * convention as the filter dropdown and the download/share picker.
     */
    public function deleteOrdersByScope(Request $request)
    {
        $request->validate([
            'category'    => 'required|in:Regular,Emergency,Rare',
            'supplier_id' => 'nullable|integer',
        ]);

        $branchId = $this->resolveBranchId();
        if (!$branchId) {
            return response()->json(['status' => 'error', 'message' => 'No branch assigned.'], 422);
        }

        $query = DB::connection('tenant')->table('retail_orders')
            ->where('branch_id', $branchId)
            ->where('category', $request->category);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $deleted = $query->delete();

        return response()->json(['status' => 'success', 'deleted' => $deleted]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // SUPPLIER-SCOPED FILTER (shared by download + share + shared view)
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Resolves a "?supplier=<id>|all" request param down to a normalized
     * (nullable int $supplierId, string $label) pair, used identically by
     * the download route and the share-link route so both are unambiguous
     * about which supplier's lines they contain.
     */
    private function resolveSupplierParam($supplierParam): array
    {
        if (!$supplierParam || $supplierParam === 'all') {
            return [null, 'All Suppliers'];
        }

        $supplierId = (int) $supplierParam;
        $name = DB::connection('tenant')->table('suppliers')->where('id', $supplierId)->value('name');

        return [$supplierId, $name ?? 'Supplier'];
    }

    private function currentOrderLinesFor(int $branchId, string $category, ?int $supplierId)
    {
        return DB::connection('tenant')->table('retail_orders as l')
            ->leftJoin('retail_branch_products as rbp', function ($j) {
                $j->on('rbp.base_product_id', '=', 'l.product_id')->on('rbp.branch_id', '=', 'l.branch_id');
            })
            ->where('l.branch_id', $branchId)
            ->where('l.category', $category)
            ->when($supplierId, fn ($q) => $q->where('l.supplier_id', $supplierId))
            ->orderByDesc('l.updated_at')
            ->orderByDesc('l.id')
            ->select('l.*', DB::raw('rbp.stock_quantity as current_qty'))
            ->get();
    }

    // ══════════════════════════════════════════════════════════════════════
    // DOWNLOAD — PDF
    // Scoped to branch+category+supplier(or all) — no order id, since
    // there's no longer a single "order" entity to download.
    // ══════════════════════════════════════════════════════════════════════

    /**
     * "Branch Name-Regular-AcmeSupplies-Order-17-Jul-2026.pdf" (or
     * "-All-Suppliers-" for an unscoped download) — spaces collapsed to
     * single hyphens, everything else left readable.
     */
    private function buildOrderPdfFilename(string $branchName, string $category, string $supplierLabel): string
    {
        $branch   = preg_replace('/\s+/', '-', trim($branchName));
        $supplier = preg_replace('/\s+/', '-', trim($supplierLabel));
        $date     = now()->format('d-M-Y');

        return "{$branch}-{$category}-{$supplier}-Order-{$date}.pdf";
    }

    public function downloadOrderPdf(Request $request)
    {
        $request->validate(['category' => 'required|in:Regular,Emergency,Rare']);

        $branchId = $this->resolveBranchId();
        abort_if(!$branchId, 422, 'No branch assigned.');

        $branch = DB::connection('tenant')->table('branches')->find($branchId);
        abort_if(!$branch, 404, 'Branch not found.');

        [$supplierId, $supplierLabel] = $this->resolveSupplierParam($request->query('supplier'));
        $lines = $this->currentOrderLinesFor($branchId, $request->category, $supplierId);

        $filters = (object) [
            'branch_name'    => $branch->name,
            'category'       => $request->category,
            'supplier_label' => $supplierLabel,
            'date'           => now()->toDateString(),
        ];

        $pdf = Pdf::loadView('sales.retail.orders-pdf', ['filters' => $filters, 'lines' => $lines]);

        return $pdf->download($this->buildOrderPdfFilename($branch->name, $request->category, $supplierLabel));
    }

    // ══════════════════════════════════════════════════════════════════════
    // LINKS — stable per branch+category+supplier. No auth required to
    // view; the token alone is what's looked up (branch/supplier names in
    // the URL are cosmetic slugs — see retail_order_links migration).
    // ══════════════════════════════════════════════════════════════════════

    private function makeLinkToken(string $branchName, string $category): string
    {
        $branchSlug   = Str::slug($branchName);
        $categorySlug = strtolower($category);
        $base  = "{$branchSlug}-{$categorySlug}-" . Str::lower(Str::random(6));
        $token = $base;
        $n     = 1;

        while (DB::connection('tenant')->table('retail_order_links')->where('share_token', $token)->exists()) {
            $n++;
            $token = "{$base}-{$n}";
        }

        return $token;
    }

    private function findOrCreateOrderLink(int $branchId, string $category, ?int $supplierId, string $branchName)
    {
        $query = DB::connection('tenant')->table('retail_order_links')
            ->where('branch_id', $branchId)->where('category', $category);

        $query = $supplierId ? $query->where('supplier_id', $supplierId) : $query->whereNull('supplier_id');
        $link  = $query->first();

        if ($link) {
            return $link;
        }

        $id = DB::connection('tenant')->table('retail_order_links')->insertGetId([
            'share_token'  => $this->makeLinkToken($branchName, $category),
            'branch_id'    => $branchId,
            'category'     => $category,
            'supplier_id'  => $supplierId,
            'share_enabled' => true,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return DB::connection('tenant')->table('retail_order_links')->where('id', $id)->first();
    }

    public function getOrderLink(Request $request)
    {
        $request->validate([
            'category'    => 'required|in:Regular,Emergency,Rare',
            'supplier_id' => 'nullable|string', // a numeric supplier id, or "all"
        ]);

        $branchId = $this->resolveBranchId();
        abort_if(!$branchId, 422, 'No branch assigned.');

        $branch = DB::connection('tenant')->table('branches')->find($branchId);
        abort_if(!$branch, 404, 'Branch not found.');

        [$supplierId, $supplierLabel] = $this->resolveSupplierParam($request->supplier_id);

        $link = $this->findOrCreateOrderLink($branchId, $request->category, $supplierId, $branch->name);

        if (!$link->share_enabled) {
            DB::connection('tenant')->table('retail_order_links')->where('id', $link->id)
                ->update(['share_enabled' => true, 'updated_at' => now()]);
        }

        $tenantName   = $request->route('tenantName');
        $branchSlug   = Str::slug($branch->name);
        $supplierSlug = $supplierId ? Str::slug($supplierLabel) : 'all-suppliers';

        $url = route('retail.orders.shared.view', [
            'tenantName'   => $tenantName,
            'branchSlug'   => $branchSlug,
            'supplierSlug' => $supplierSlug,
            'token'        => $link->share_token,
        ]);

        return response()->json(['status' => 'success', 'url' => $url, 'id' => $link->id]);
    }

    public function revokeOrderLink(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        DB::connection('tenant')->table('retail_order_links')
            ->where('id', $request->id)
            ->update(['share_enabled' => false, 'updated_at' => now()]);

        return response()->json(['status' => 'success', 'message' => 'Share link disabled.']);
    }

    public function showSharedOrder(Request $request, $tenantName, $branchSlug, $supplierSlug, $token)
    {
        $link = DB::connection('tenant')->table('retail_order_links')
            ->where('share_token', $token)->where('share_enabled', true)->first();

        abort_if(!$link, 404, 'This order link is invalid or has been disabled.');

        $branch = DB::connection('tenant')->table('branches')->find($link->branch_id);
        abort_if(!$branch, 404, 'Branch not found.');

        $supplierLabel = 'All Suppliers';
        if ($link->supplier_id) {
            $supplierLabel = DB::connection('tenant')->table('suppliers')->where('id', $link->supplier_id)->value('name') ?? 'Supplier';
        }

        $lines = $this->currentOrderLinesFor($link->branch_id, $link->category, $link->supplier_id);

        DB::connection('tenant')->table('retail_order_links')->where('id', $link->id)->update([
            'share_last_viewed_at' => now(),
            'share_view_count'     => $link->share_view_count + 1,
        ]);

        $filters = (object) [
            'branch_name'       => $branch->name,
            'category'          => $link->category,
            'supplier_label'    => $supplierLabel,
            'date'              => now()->toDateString(),
            'share_token'       => $token,
            'share_view_count'  => $link->share_view_count + 1,
        ];

        return view('sales.retail.orders-shared', ['filters' => $filters, 'lines' => $lines]);
    }

    public function downloadSharedOrderPdf(Request $request, $tenantName, $branchSlug, $supplierSlug, $token)
    {
        $link = DB::connection('tenant')->table('retail_order_links')
            ->where('share_token', $token)->where('share_enabled', true)->first();

        abort_if(!$link, 404, 'This order link is invalid or has been disabled.');

        $branch = DB::connection('tenant')->table('branches')->find($link->branch_id);
        abort_if(!$branch, 404, 'Branch not found.');

        $supplierLabel = 'All Suppliers';
        if ($link->supplier_id) {
            $supplierLabel = DB::connection('tenant')->table('suppliers')->where('id', $link->supplier_id)->value('name') ?? 'Supplier';
        }

        $lines = $this->currentOrderLinesFor($link->branch_id, $link->category, $link->supplier_id);

        $filters = (object) [
            'branch_name'    => $branch->name,
            'category'       => $link->category,
            'supplier_label' => $supplierLabel,
            'date'           => now()->toDateString(),
        ];

        $pdf = Pdf::loadView('sales.retail.orders-pdf', ['filters' => $filters, 'lines' => $lines]);

        return $pdf->download($this->buildOrderPdfFilename($branch->name, $link->category, $supplierLabel));
    }
}