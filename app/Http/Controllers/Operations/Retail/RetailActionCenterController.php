<?php

namespace App\Http\Controllers\Operations\Retail;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;

class RetailActionCenterController extends Controller
{
    // ══════════════════════════════════════════════════════════════════════
    //  HELPERS
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Build the user snapshot array from the currently authenticated user.
     * Called at write time so the snapshot is frozen at the moment of action.
     */
    private function userSnapshot(Request $request): array
    {
        $user  = Auth::user();
        $agent = $request->userAgent() ?? '';

        return [
            'added_by'            => $user->id,
            'user_full_name'      => $user->name   ?? null,
            'user_email'          => $user->email  ?? null,
            'user_role'           => $user->role   ?? null,
            'user_device_details' => $agent,
            'ip_address'          => $request->ip(),
            'device_type'         => $this->parseDeviceType($agent),
            'browser'             => $this->parseBrowser($agent),
            'operating_system'    => $this->parseOS($agent),
            'session_id'          => session()->getId(),
        ];
    }

    private function parseDeviceType(string $ua): string
    {
        $ua = strtolower($ua);
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad'))  return 'tablet';
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android')
            || str_contains($ua, 'iphone'))                             return 'mobile';
        return 'desktop';
    }

    private function parseBrowser(string $ua): string
    {
        if (str_contains($ua, 'Edg'))                                   return 'Edge';
        if (str_contains($ua, 'OPR') || str_contains($ua, 'Opera'))    return 'Opera';
        if (str_contains($ua, 'Chrome'))                                return 'Chrome';
        if (str_contains($ua, 'Firefox'))                               return 'Firefox';
        if (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) return 'Safari';
        if (str_contains($ua, 'MSIE')   || str_contains($ua, 'Trident')) return 'IE';
        return 'Other';
    }

    private function parseOS(string $ua): string
    {
        if (str_contains($ua, 'Windows NT'))                            return 'Windows';
        if (str_contains($ua, 'Mac OS X'))                              return 'macOS';
        if (str_contains($ua, 'Android'))                               return 'Android';
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad'))  return 'iOS';
        if (str_contains($ua, 'Linux'))                                 return 'Linux';
        return 'Other';
    }

    /**
     * Fetch a single delivery note fully joined and return it as a formatted array.
     * Used after writes so the UI receives fresh data without a page reload.
     */
    private function fetchFormattedNote(int $id): ?array
    {
        $note = DB::connection('tenant')
            ->table('retail_deliverynotes as rdn')
            ->join('retail_base_products as rbp', 'rbp.id', '=', 'rdn.base_product_id')
            ->join('branches as b',               'b.id',   '=', 'rdn.branch_id')
            ->join('users as u',                  'u.id',   '=', 'rdn.added_by')
            ->leftJoin('users as su', 'su.id', '=', 'rdn.submitted_by')
            ->where('rdn.id', $id)
            ->select(
                'rdn.*',
                'rbp.name         as product_name',
                'rbp.code         as product_code',
                'rbp.unit         as product_unit',
                'b.name           as branch_name',
                'u.name           as added_by_name',
                'su.name          as submitted_by_name'
            )
            ->first();

        return $note ? $this->formatNote($note) : null;
    }

    /**
     * Normalise a raw DB row into the array shape the front-end expects.
     */
    private function formatNote($note): array
    {
        return [
            // ── Identifiers ───────────────────────────────────────────────
            'id'                => $note->id,
            'row'               => 'row' . $note->id,
            'branch_id'         => $note->branch_id,
            'base_product_id'   => $note->base_product_id,

            // ── Branch & product info (from joins) ────────────────────────
            'branch_name'       => $note->branch_name       ?? null,
            'product_name'      => $note->product_name      ?? null,
            'product_code'      => $note->product_code      ?? null,
            'product_unit'      => $note->product_unit      ?? null,

            // ── Price snapshots (frozen at entry time) ────────────────────
            'selling_price'     => $note->selling_price     ?? 0,
            'cost_price'        => $note->cost_price        ?? 0,

            // ── Delivery details ──────────────────────────────────────────
            'delivery_date'     => $note->delivery_date,
            'quantity'          => $note->quantity,

            // ── Submission state ──────────────────────────────────────────
            'submitted'         => (bool) $note->submitted,
            'submitted_by'      => $note->submitted_by      ?? null,
            'submitted_by_name' => $note->submitted_by_name ?? null,
            'submitted_at'      => $note->submitted_at      ?? null,

            // ── Who added it ──────────────────────────────────────────────
            'added_by'          => $note->added_by,
            'added_by_name'     => $note->added_by_name     ?? null,

            // ── Error / discrepancy ───────────────────────────────────────
            'error_quantity'    => $note->error_quantity    ?? null,
            'error_notes'       => $note->error_notes       ?? null,
            'error_status'      => $note->error_status      ?? null,

            // ── Notes ─────────────────────────────────────────────────────
            'notes'             => $note->notes             ?? null,

            // ── Timestamps ───────────────────────────────────────────────
            'created_at'        => $note->created_at        ?? null,
            'updated_at'        => $note->updated_at        ?? null,

            // ── Computed helpers for the UI ───────────────────────────────
            'row_value'         => round((float) $note->quantity * (float) ($note->selling_price ?? 0), 2),
        ];
    }

    /**
     * Resolve the base product snapshot fields — price, unit, name, code.
     * These are frozen into the delivery note at creation time.
     */
    private function fetchBaseProduct(int $baseProductId): ?object
    {
        return DB::connection('tenant')
            ->table('retail_base_products')
            ->where('id', $baseProductId)
            ->first(['id', 'name', 'code', 'unit', 'selling_price', 'cost_price']);
    }

    /**
     * Write a stock movement to retail_inventory_logs when a delivery note
     * is submitted (stock actually added to branch).
     */
    private function logStockChange(
        int     $baseProductId,
        int     $branchId,
        float   $stockBefore,
        float   $stockAfter,
        string  $operationType,
        string  $reason,
        ?float  $sellingPrice = null,
        ?float  $costPrice    = null,
        string  $sourceType   = 'DeliveryNote',
        ?int    $sourceId     = null
    ): void {
        $change = $stockAfter - $stockBefore;

        // Skip no-ops to avoid polluting the log.
        if (abs($change) < 0.0001) return;

        $request = request();
        $user    = Auth::user();
        $agent   = $request->userAgent() ?? '';

        DB::connection('tenant')
            ->table('retail_inventory_logs')
            ->insert([
                'product_id'          => $baseProductId,
                'branch_id'           => $branchId,
                'stock_before'        => $stockBefore,
                'stock_after'         => $stockAfter,
                'stock_change'        => $change,
                'selling_price'       => $sellingPrice ?? 0,
                'cost_price'          => $costPrice    ?? 0,
                'operation_type'      => $operationType,
                'source_type'         => $sourceType,
                'source_id'           => $sourceId,
                'action_reason'       => $reason,
                'user_id'             => $user->id,
                'user_full_name'      => $user->name  ?? null,
                'user_email'          => $user->email ?? null,
                'user_role'           => $user->role  ?? null,
                'user_device_details' => $agent,
                'ip_address'          => $request->ip(),
                'device_type'         => $this->parseDeviceType($agent),
                'browser'             => $this->parseBrowser($agent),
                'operating_system'    => $this->parseOS($agent),
                'session_id'          => session()->getId(),
                'log_date'            => now()->toDateString(),
                'log_time'            => now()->toTimeString(),
            ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  VIEW
    // ══════════════════════════════════════════════════════════════════════

    /**
     * GET  /retail/operations/action-center
     * Render the main Action Centre view.
     */
    public function showActionCenterView()
    {
        return view('operations.retail.actioncenter');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  AJAX: SAVE DELIVERY NOTE  (on-change auto-save)
    // ══════════════════════════════════════════════════════════════════════

    /**
     * POST  /retail/operations/action-center/save-dnote
     *
     * Upserts one pending delivery note for a branch + product + date.
     * If quantity is 0 or blank the note is deleted (cleared).
     * Does NOT add to branch stock — that only happens on Submit.
     */
    public function saveDeliveryNote(Request $request)
    {
        $request->validate([
            'branch_id'       => 'required|integer|exists:tenant.branches,id',
            'base_product_id' => 'required|integer|exists:tenant.retail_base_products,id',
            'quantity'        => 'required|numeric|min:0',
            'delivery_date'   => 'required|date',
        ]);

        $branchId      = (int)    $request->branch_id;
        $baseProductId = (int)    $request->base_product_id;
        $quantity      = (float)  $request->quantity;
        $date          = $request->delivery_date;

        // If quantity is zero, remove any pending note for this slot.
        if ($quantity <= 0) {
            DB::connection('tenant')
                ->table('retail_deliverynotes')
                ->where('branch_id',       $branchId)
                ->where('base_product_id', $baseProductId)
                ->where('delivery_date',   $date)
                ->where('submitted',       false)
                ->delete();

            return response()->json(['status' => 200, 'info' => 'Delivery note cleared.']);
        }

        $base = $this->fetchBaseProduct($baseProductId);

        $existing = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('branch_id',       $branchId)
            ->where('base_product_id', $baseProductId)
            ->where('delivery_date',   $date)
            ->where('submitted',       false)
            ->first();

        $snapshot = $this->userSnapshot($request);
        $now      = now();

        if ($existing) {
            DB::connection('tenant')
                ->table('retail_deliverynotes')
                ->where('id', $existing->id)
                ->update([
                    'quantity'      => $quantity,
                    'selling_price' => $base->selling_price ?? 0,
                    'cost_price'    => $base->cost_price    ?? 0,
                    'updated_at'    => $now,
                ]);

            $noteId = $existing->id;

        } else {
            $noteId = DB::connection('tenant')
                ->table('retail_deliverynotes')
                ->insertGetId(array_merge($snapshot, [
                    'branch_id'       => $branchId,
                    'base_product_id' => $baseProductId,
                    'product_name'    => $base->name ?? null,
                    'product_code'    => $base->code ?? null,
                    'product_unit'    => $base->unit ?? 'Each',
                    'selling_price'   => $base->selling_price ?? 0,
                    'cost_price'      => $base->cost_price    ?? 0,
                    'delivery_date'   => $date,
                    'quantity'        => $quantity,
                    'submitted'       => false,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]));
        }

        $formatted = $this->fetchFormattedNote($noteId);

        return response()->json([
            'status'  => $existing ? 200 : 201,
            'success' => 'Delivery note saved.',
            'note'    => $formatted,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  AJAX: SUBMIT — single product
    // ══════════════════════════════════════════════════════════════════════

    /**
     * POST  /retail/operations/action-center/submit
     *
     * Marks all pending delivery notes for the given product + date as
     * submitted and adds their quantities to the respective branch stock.
     * Writes an inventory log entry for each branch affected.
     */
    public function submitDeliveryNotes(Request $request)
    {
        $request->validate([
            'base_product_id' => 'required|integer|exists:tenant.retail_base_products,id',
            'delivery_date'   => 'required|date',
        ]);

        $baseProductId = (int) $request->base_product_id;
        $date          = $request->delivery_date;
        $now           = now();
        $userId        = Auth::id();

        $pending = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('base_product_id', $baseProductId)
            ->where('delivery_date',   $date)
            ->where('submitted',       false)
            ->get();

        if ($pending->isEmpty()) {
            return response()->json([
                'status' => 200,
                'info'   => 'No pending delivery notes to process.',
            ]);
        }

        $processed = 0;

        foreach ($pending as $note) {
            $branchId = (int) $note->branch_id;
            $quantity = (float) $note->quantity;

            // ── Add to branch stock ───────────────────────────────────────
            $branchProduct = DB::connection('tenant')
                ->table('retail_branch_products')
                ->where('branch_id',       $branchId)
                ->where('base_product_id', $baseProductId)
                ->first();

            $stockBefore = $branchProduct ? (float) $branchProduct->stock_quantity : 0.0;
            $stockAfter  = $stockBefore + $quantity;

            if ($branchProduct) {
                DB::connection('tenant')
                    ->table('retail_branch_products')
                    ->where('id', $branchProduct->id)
                    ->update([
                        'stock_quantity' => $stockAfter,
                        'updated_at'     => $now,
                    ]);
            }
            // If the branch product row doesn't exist yet we skip the stock
            // update but still mark the note submitted — branch must be set up first.

            // ── Mark note as submitted ────────────────────────────────────
            DB::connection('tenant')
                ->table('retail_deliverynotes')
                ->where('id', $note->id)
                ->update([
                    'submitted'    => true,
                    'submitted_by' => $userId,
                    'submitted_at' => $now,
                    'updated_at'   => $now,
                ]);

            // ── Write inventory log ───────────────────────────────────────
            if ($branchProduct) {
                $this->logStockChange(
                    baseProductId: $baseProductId,
                    branchId:      $branchId,
                    stockBefore:   $stockBefore,
                    stockAfter:    $stockAfter,
                    operationType: 'StockDelivery',
                    reason:        'Delivery note #' . $note->id . ' submitted for ' . $date,
                    sellingPrice:  (float) ($note->selling_price ?? 0),
                    costPrice:     (float) ($note->cost_price    ?? 0),
                    sourceType:    'DeliveryNote',
                    sourceId:      $note->id,
                );
            }

            $processed++;
        }

        return response()->json([
            'status'  => 200,
            'success' => $processed . ' delivery note' . ($processed > 1 ? 's' : '') . ' processed successfully.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  AJAX: SUBMIT ALL — all products for the date
    // ══════════════════════════════════════════════════════════════════════

    /**
     * POST  /retail/operations/action-center/submit-all
     *
     * Same logic as submitDeliveryNotes() but processes every pending note
     * across all products for the selected delivery date.
     */
    public function submitAllDeliveryNotes(Request $request)
    {
        $request->validate([
            'delivery_date' => 'required|date',
        ]);

        $date   = $request->delivery_date;
        $now    = now();
        $userId = Auth::id();

        $pending = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('delivery_date', $date)
            ->where('submitted',     false)
            ->get();

        if ($pending->isEmpty()) {
            return response()->json([
                'status' => 200,
                'info'   => 'No pending delivery notes to process for ' . Carbon::parse($date)->format('d F Y') . '.',
            ]);
        }

        $processed = 0;
        $skipped   = 0;

        foreach ($pending as $note) {
            $branchId      = (int) $note->branch_id;
            $baseProductId = (int) $note->base_product_id;
            $quantity      = (float) $note->quantity;

            $branchProduct = DB::connection('tenant')
                ->table('retail_branch_products')
                ->where('branch_id',       $branchId)
                ->where('base_product_id', $baseProductId)
                ->first();

            if (!$branchProduct) {
                // Product not set up at this branch — mark submitted but log skipped.
                DB::connection('tenant')
                    ->table('retail_deliverynotes')
                    ->where('id', $note->id)
                    ->update([
                        'submitted'    => true,
                        'submitted_by' => $userId,
                        'submitted_at' => $now,
                        'notes'        => 'Stock not updated: product not found at branch.',
                        'updated_at'   => $now,
                    ]);
                $skipped++;
                continue;
            }

            $stockBefore = (float) $branchProduct->stock_quantity;
            $stockAfter  = $stockBefore + $quantity;

            DB::connection('tenant')
                ->table('retail_branch_products')
                ->where('id', $branchProduct->id)
                ->update([
                    'stock_quantity' => $stockAfter,
                    'updated_at'     => $now,
                ]);

            DB::connection('tenant')
                ->table('retail_deliverynotes')
                ->where('id', $note->id)
                ->update([
                    'submitted'    => true,
                    'submitted_by' => $userId,
                    'submitted_at' => $now,
                    'updated_at'   => $now,
                ]);

            $this->logStockChange(
                baseProductId: $baseProductId,
                branchId:      $branchId,
                stockBefore:   $stockBefore,
                stockAfter:    $stockAfter,
                operationType: 'StockDelivery',
                reason:        'Delivery note #' . $note->id . ' submitted (bulk) for ' . $date,
                sellingPrice:  (float) ($note->selling_price ?? 0),
                costPrice:     (float) ($note->cost_price    ?? 0),
                sourceType:    'DeliveryNote',
                sourceId:      $note->id,
            );

            $processed++;
        }

        $message = $processed . ' delivery note' . ($processed > 1 ? 's' : '') . ' processed successfully.';
        if ($skipped > 0) {
            $message .= ' ' . $skipped . ' note' . ($skipped > 1 ? 's' : '') . ' skipped (product not set up at branch).';
        }

        return response()->json([
            'status'  => 200,
            'success' => $message,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  AJAX: CANCEL — delete pending notes for a product + date
    // ══════════════════════════════════════════════════════════════════════

    /**
     * POST  /retail/operations/action-center/cancel
     *
     * Deletes all unsubmitted delivery notes for the given product + date.
     * Already-submitted notes are left untouched.
     */
    public function cancelDeliveryNotes(Request $request)
    {
        $request->validate([
            'base_product_id' => 'required|integer|exists:tenant.retail_base_products,id',
            'delivery_date'   => 'required|date',
        ]);

        $baseProductId = (int) $request->base_product_id;
        $date          = $request->delivery_date;

        $deleted = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('base_product_id', $baseProductId)
            ->where('delivery_date',   $date)
            ->where('submitted',       false)
            ->delete();

        if ($deleted === 0) {
            return response()->json([
                'status' => 200,
                'info'   => 'No pending delivery notes found to cancel.',
            ]);
        }

        return response()->json([
            'status'  => 200,
            'success' => $deleted . ' pending delivery note' . ($deleted > 1 ? 's' : '') . ' cancelled.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  AJAX: GET DELIVERY NOTES — for the delivery notes tab / listing
    // ══════════════════════════════════════════════════════════════════════

    /**
     * GET  /retail/operations/action-center/notes
     *
     * Returns delivery notes for a given date (and optional branch / product
     * filters), joined with product and branch display info.
     */
    public function getDeliveryNotes(Request $request)
    {
        $request->validate([
            'delivery_date'   => 'required|date',
            'branch_id'       => 'nullable|integer|exists:tenant.branches,id',
            'base_product_id' => 'nullable|integer|exists:tenant.retail_base_products,id',
            'submitted'       => 'nullable|boolean',
        ]);

        $query = DB::connection('tenant')
            ->table('retail_deliverynotes as rdn')
            ->join('retail_base_products as rbp', 'rbp.id', '=', 'rdn.base_product_id')
            ->join('branches as b',               'b.id',   '=', 'rdn.branch_id')
            ->join('users as u',                  'u.id',   '=', 'rdn.added_by')
            ->leftJoin('users as su', 'su.id', '=', 'rdn.submitted_by')
            ->where('rdn.delivery_date', $request->delivery_date)
            ->select(
                'rdn.*',
                'rbp.name  as product_name',
                'rbp.code  as product_code',
                'rbp.unit  as product_unit',
                'b.name    as branch_name',
                'u.name    as added_by_name',
                'su.name   as submitted_by_name'
            )
            ->orderBy('b.name')
            ->orderBy('rbp.name');

        if ($request->filled('branch_id')) {
            $query->where('rdn.branch_id', $request->branch_id);
        }

        if ($request->filled('base_product_id')) {
            $query->where('rdn.base_product_id', $request->base_product_id);
        }

        if ($request->filled('submitted')) {
            $query->where('rdn.submitted', (bool) $request->submitted);
        }

        $notes = $query->get();

        $formatted = $notes->map(fn($n) => $this->formatNote($n))->values();

        $totalQty   = $notes->sum(fn($n) => (float) $n->quantity);
        $totalValue = $notes->sum(fn($n) => (float) $n->quantity * (float) ($n->selling_price ?? 0));

        return response()->json([
            'status'      => 200,
            'notes'       => $formatted,
            'total_qty'   => round($totalQty,   3),
            'total_value' => round($totalValue, 2),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  AJAX: UPDATE DELIVERY NOTE
    // ══════════════════════════════════════════════════════════════════════

    /**
     * POST  /retail/operations/action-center/update-note
     *
     * Edits the quantity or notes on an unsubmitted delivery note.
     * Submitted notes are locked — return 422 if someone tries.
     */
    public function updateDeliveryNote(Request $request)
    {
        $request->validate([
            'id'       => 'required|integer|exists:tenant.retail_deliverynotes,id',
            'quantity' => 'required|numeric|min:0',
            'notes'    => 'nullable|string|max:500',
        ]);

        $note = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('id', $request->id)
            ->first();

        if (!$note) {
            return response()->json(['error' => 'Delivery note not found.', 'status' => 404]);
        }

        if ($note->submitted) {
            return response()->json([
                'error'  => 'This delivery note has already been submitted and cannot be edited.',
                'status' => 422,
            ]);
        }

        $base = $this->fetchBaseProduct((int) $note->base_product_id);

        DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('id', $request->id)
            ->update([
                'quantity'      => (float) $request->quantity,
                'selling_price' => $base->selling_price ?? $note->selling_price,
                'cost_price'    => $base->cost_price    ?? $note->cost_price,
                'notes'         => $request->notes ?? $note->notes,
                'updated_at'    => now(),
            ]);

        $formatted = $this->fetchFormattedNote((int) $request->id);

        return response()->json([
            'status'  => 201,
            'success' => 'Delivery note updated successfully.',
            'note'    => $formatted,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  AJAX: DELETE DELIVERY NOTE
    // ══════════════════════════════════════════════════════════════════════

    /**
     * POST  /retail/operations/action-center/delete-note
     *
     * Permanently deletes a delivery note (submitted or not).
     * Use with caution — submitted notes have already affected stock.
     */
    public function deleteDeliveryNote(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:tenant.retail_deliverynotes,id',
        ]);

        $deleted = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('id', $request->id)
            ->delete();

        if ($deleted) {
            return response()->json([
                'status'  => 201,
                'success' => 'Delivery note deleted successfully.',
            ]);
        }

        return response()->json(['error' => 'Delivery note not found.', 'status' => 404]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  AJAX: BULK DELETE DELIVERY NOTES
    // ══════════════════════════════════════════════════════════════════════

    /**
     * POST  /retail/operations/action-center/bulk-delete-notes
     */
    public function bulkDeleteDeliveryNotes(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:tenant.retail_deliverynotes,id',
        ]);

        $deleted = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->whereIn('id', $request->ids)
            ->delete();

        if ($deleted > 0) {
            return response()->json([
                'status'  => 201,
                'success' => $deleted . ' delivery note' . ($deleted > 1 ? 's' : '') . ' deleted successfully.',
            ]);
        }

        return response()->json(['error' => 'No delivery notes found.', 'status' => 404]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  AJAX: DATES WITH DELIVERY NOTES
    // ══════════════════════════════════════════════════════════════════════

    /**
     * GET  /retail/operations/action-center/dates
     *
     * Returns distinct delivery dates that have notes (last 3 months).
     * Optional category_id filter — useful for the date picker dropdown.
     */
    public function getDatesWithNotes(Request $request)
    {
        $request->validate([
            'branch_id' => 'nullable|integer|exists:tenant.branches,id',
        ]);

        $query = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('delivery_date', '>=', now()->subMonths(3)->toDateString())
            ->distinct()
            ->orderByDesc('delivery_date');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $dates = $query->pluck('delivery_date');

        return response()->json(['status' => 200, 'dates' => $dates]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  AJAX: SEARCH BASE PRODUCTS
    // ══════════════════════════════════════════════════════════════════════

    /**
     * GET  /retail/operations/action-center/search-products
     *
     * Returns all active base products for the product search dropdown.
     */
    public function searchBaseProducts(Request $request)
    {
        $products = DB::connection('tenant')
            ->table('retail_base_products')
            ->where('is_product', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'unit', 'selling_price', 'cost_price']);

        return response()->json(['status' => 200, 'products' => $products]);
    }
}