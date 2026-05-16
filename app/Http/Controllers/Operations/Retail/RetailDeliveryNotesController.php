<?php

namespace App\Http\Controllers\Operations\Retail;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;
use Dompdf\Dompdf;
use Dompdf\Options;

class RetailDeliveryNotesController extends Controller
{
    

    public function showDeliverynotesView()
    {
        return view('operations.retail.deliverynotes');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  FETCH BRANCH DELIVERY NOTE SUMMARY BY DATE  (AJAX)
    // ══════════════════════════════════════════════════════════════════════

    public function fetchBranchDeliveryNoteSummaryByDate(Request $request)
    {
        $request->validate([
            'delivery_date' => 'required|date',
            'category_id'   => 'nullable|integer',
        ]);

        $date       = $request->delivery_date;
        $categoryId = $request->category_id;

        $branchQuery = DB::connection('tenant')
            ->table('branches')
            ->where('sector', 'Retail')
            ->where('status', 'active')
            ->orderBy('name');

        if ($categoryId) {
            $branchQuery->where('category', (string) $categoryId);
        }

        $branches  = $branchQuery->get();
        $branchIds = $branches->pluck('id');
        $branchMap = $branches->keyBy('id');

        $allNotesForDate = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->whereIn('branch_id', $branchIds)
            ->where('delivery_date', $date)
            ->get();

        $branchRows = $allNotesForDate
            ->groupBy('branch_id')
            ->map(function ($notesForBranch, $branchId) use ($branchMap) {
                $branch         = $branchMap[$branchId] ?? null;
                $submittedNotes = $notesForBranch->where('submitted', true);
                $pendingNotes   = $notesForBranch->where('submitted', false);

                $totalCostValue      = $notesForBranch->sum(fn($n) => (float) $n->quantity * (float) ($n->cost_price    ?? 0));
                $totalSellingValue   = $notesForBranch->sum(fn($n) => (float) $n->quantity * (float) ($n->selling_price  ?? 0));
                $submittedSellValue  = $submittedNotes->sum(fn($n) => (float) $n->quantity * (float) ($n->selling_price  ?? 0));
                $pendingSellValue    = $pendingNotes->sum(fn($n)   => (float) $n->quantity * (float) ($n->selling_price  ?? 0));

                return [
                    'branch_id'               => $branchId,
                    'branch_name'             => $branch->name ?? 'Unknown',
                    'total_product_lines'     => $notesForBranch->count(),
                    'total_cost_value'        => round($totalCostValue,      2),
                    'total_selling_value'     => round($totalSellingValue,   2),
                    'submitted_note_count'    => $submittedNotes->count(),
                    'submitted_selling_value' => round($submittedSellValue,  2),
                    'pending_note_count'      => $pendingNotes->count(),
                    'pending_selling_value'   => round($pendingSellValue,    2),
                    'has_pending_notes'       => $pendingNotes->isNotEmpty(),
                ];
            })
            ->values();

        $grandTotalCost      = $branchRows->sum('total_cost_value');
        $grandTotalValue     = $branchRows->sum('total_selling_value');
        $grandSubmittedValue = $branchRows->sum('submitted_selling_value');
        $grandPendingValue   = $branchRows->sum('pending_selling_value');

        return response()->json([
            'status'                => 200,
            'rows'                  => $branchRows,
            'grand_total_cost'      => round($grandTotalCost,      2),
            'grand_total_value'     => round($grandTotalValue,      2),
            'grand_submitted_value' => round($grandSubmittedValue,  2),
            'grand_pending_value'   => round($grandPendingValue,    2),
            'delivery_date'         => $date,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SUBMIT ALL PENDING NOTES FOR BRANCH
    // ══════════════════════════════════════════════════════════════════════

    public function submitAllPendingNotesForBranch(Request $request)
    {
        $request->validate([
            'branch_id'     => 'required|integer|exists:tenant.branches,id',
            'delivery_date' => 'required|date',
        ]);

        $branchId    = (int) $request->branch_id;
        $date        = $request->delivery_date;
        $now         = now();
        $submitterId = Auth::id();

        $pendingNotes = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('branch_id',     $branchId)
            ->where('delivery_date', $date)
            ->where('submitted',     false)
            ->get();

        if ($pendingNotes->isEmpty()) {
            return response()->json([
                'status' => 200,
                'info'   => 'No pending delivery notes found for this branch on ' . Carbon::parse($date)->format('d M Y') . '.',
            ]);
        }

        $notesSubmittedCount = 0;

        DB::connection('tenant')->transaction(function () use (
            $pendingNotes, $branchId, $submitterId, $now, &$notesSubmittedCount
        ) {
            foreach ($pendingNotes as $note) {
                $baseProductId = (int)   $note->base_product_id;
                $deliveredQty  = (float) $note->quantity;

                if ($deliveredQty <= 0) continue;

                DB::connection('tenant')
                    ->table('retail_deliverynotes')
                    ->where('id', $note->id)
                    ->update([
                        'submitted'    => true,
                        'submitted_by' => $submitterId,
                        'submitted_at' => $now,
                        'updated_at'   => $now,
                    ]);

                $existingBranchProduct = DB::connection('tenant')
                    ->table('retail_branch_products')
                    ->where('branch_id',       $branchId)
                    ->where('base_product_id', $baseProductId)
                    ->first();

                if ($existingBranchProduct) {
                    DB::connection('tenant')
                        ->table('retail_branch_products')
                        ->where('id', $existingBranchProduct->id)
                        ->update([
                            'stock_quantity' => (float) $existingBranchProduct->stock_quantity + $deliveredQty,
                            'updated_at'     => $now,
                        ]);
                } else {
                    DB::connection('tenant')
                        ->table('retail_branch_products')
                        ->insert([
                            'branch_id'       => $branchId,
                            'base_product_id' => $baseProductId,
                            'selling_price'   => $note->selling_price,
                            'cost_price'      => $note->cost_price,
                            'stock_quantity'  => $deliveredQty,
                            'created_at'      => $now,
                            'updated_at'      => $now,
                        ]);
                }

                $notesSubmittedCount++;
            }
        });

        if ($notesSubmittedCount === 0) {
            return response()->json([
                'status' => 200,
                'info'   => 'All pending notes had zero quantity and were skipped.',
            ]);
        }

        return response()->json([
            'status'  => 200,
            'success' => $notesSubmittedCount . ' delivery note' . ($notesSubmittedCount > 1 ? 's' : '') .
                         ' submitted successfully and branch stock updated.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  EXPORT BRANCH DELIVERY NOTES TO PDF
    //  Mirrors the AuditLogsController PDF pattern exactly — DomPDF,
    //  Blade view render, landscape A4.
    // ══════════════════════════════════════════════════════════════════════
           public function exportBranchDeliveryNotesToPdf(Request $request)
{
    $request->validate([
        'branch_id' => 'required|integer|exists:tenant.branches,id',
        'date'      => 'required|date',
    ]);
 
    $branchId     = (int) $request->branch_id;
    $deliveryDate = $request->date;
 
    // ── 1. Branch ─────────────────────────────────────────────────────
    $branch = DB::connection('tenant')
        ->table('branches')
        ->where('id', $branchId)
        ->first();
 
    if (! $branch) {
        abort(404, 'Branch not found.');
    }
 
    // ── 2. Company profile  ★ change 'company_info' → 'company' if needed ──
    $companyProfile = DB::connection('tenant')->table('company_info')->first();
 
    // ── 3. Prepared-by user ───────────────────────────────────────────
    $preparedByUser = DB::connection('tenant')
        ->table('users')
        ->where('id', Auth::id())
        ->select('name', 'phone', 'position', 'department', 'email')
        ->first();
 
    // ── 4. Delivery notes ─────────────────────────────────────────────
    $deliveryNotes = DB::connection('tenant')
        ->table('retail_deliverynotes as rdn')
        ->join('retail_base_products as rbp', 'rbp.id', '=', 'rdn.base_product_id')
        ->leftJoin('users as submitter', 'submitter.id', '=', 'rdn.submitted_by')
        ->where('rdn.branch_id',     $branchId)
        ->where('rdn.delivery_date', $deliveryDate)
        ->select(
            'rdn.id',
            'rdn.quantity',
            'rdn.selling_price',
            'rdn.cost_price',
            'rdn.submitted',
            'rdn.submitted_at',
            'rbp.name as product_name',
            'rbp.code as product_code',
            'rbp.unit as product_unit',
            'submitter.name as submitted_by_name'
        )
        ->orderBy('rbp.name')
        ->get();
 
    // ── 5. Totals ─────────────────────────────────────────────────────
    $grandTotalCost  = $deliveryNotes->sum(fn($n) => (float) $n->quantity * (float) ($n->cost_price    ?? 0));
    $grandTotalValue = $deliveryNotes->sum(fn($n) => (float) $n->quantity * (float) ($n->selling_price ?? 0));
    $submittedCount  = $deliveryNotes->where('submitted', true)->count();
    $pendingCount    = $deliveryNotes->where('submitted', false)->count();
    $totalQty        = $deliveryNotes->sum(fn($n) => (float) $n->quantity);
 
    // ── 6. Render Blade → HTML ────────────────────────────────────────
    $html = view('operations.retail.deliverynotespdf', [
        'branch'          => $branch,
        'companyProfile'  => $companyProfile,
        'preparedByUser'  => $preparedByUser,
        'deliveryNotes'   => $deliveryNotes,
        'deliveryDate'    => $deliveryDate,
        'displayDate'     => \Carbon\Carbon::parse($deliveryDate)->format('d M Y'),
        'formattedDate'   => \Carbon\Carbon::parse($deliveryDate)->format('d F Y'),
        'grandTotalCost'  => round($grandTotalCost,  2),
        'grandTotalValue' => round($grandTotalValue, 2),
        'submittedCount'  => $submittedCount,
        'pendingCount'    => $pendingCount,
        'totalQty'        => round($totalQty, 2),
        'generatedAt'     => now()->format('d M Y, H:i'),
        'generatedBy'     => $preparedByUser->name ?? (Auth::user()->name ?? 'System'),
    ])->render();
 
    // ── 7. DomPDF  — mirrors RetailAuditLogsController::downloadPdf() ─
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled',      false);
    $options->set('defaultFont',          'DejaVu Sans');
 
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
 
    // ── 8. Safe filename ──────────────────────────────────────────────
    $filename = 'DeliveryNotes_'
              . preg_replace('/[^\w\-]+/', '_', $branch->name)
              . '_' . $deliveryDate . '.pdf';
 
    return response($dompdf->output(), 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        'Cache-Control'       => 'no-cache, no-store, must-revalidate',
        'Pragma'              => 'no-cache',
        'Expires'             => '0',
    ]);
}
 
// ──────────────────────────────────────────────────────────────────────
//  EDIT VIEW  — GET /retail/operations/delivery-notes/edit-view
//               ?branch_id=4&date=2026-05-12
// ──────────────────────────────────────────────────────────────────────
public function showBranchDeliveryNoteEditView(Request $request)
{
    $request->validate([
        'branch_id' => 'required|integer|exists:tenant.branches,id',
        'date'      => 'required|date',
    ]);
 
    $branchId     = (int) $request->branch_id;
    $deliveryDate = $request->date;
 
    $branch = DB::connection('tenant')
        ->table('branches')
        ->where('id', $branchId)
        ->first(['id', 'name']);
 
    if (! $branch) {
        abort(404, 'Branch not found.');
    }
 
    $deliveryNotesWithDetails = DB::connection('tenant')
        ->table('retail_deliverynotes as rdn')
        ->join('retail_base_products as rbp', 'rbp.id', '=', 'rdn.base_product_id')
        ->leftJoin('users as creator',   'creator.id',   '=', 'rdn.added_by')
        ->leftJoin('users as submitter', 'submitter.id', '=', 'rdn.submitted_by')
        ->where('rdn.branch_id',     $branchId)
        ->where('rdn.delivery_date', $deliveryDate)
        ->select(
            'rdn.*',
            'rbp.name as product_name',
            'rbp.code as product_code',
            'rbp.unit as product_unit',
            'creator.name   as created_by_name',
            'submitter.name as submitted_by_name'
        )
        ->orderBy('rbp.name')
        ->get();
 
    return view('operations.retail.deliverynotes-edit', [
        'branch'                   => $branch,
        'deliveryNotesWithDetails' => $deliveryNotesWithDetails,
        'deliveryDate'             => $deliveryDate,
        'displayDate'              => \Carbon\Carbon::parse($deliveryDate)->format('d M Y'),
    ]);
}


    // ══════════════════════════════════════════════════════════════════════
    //  BULK SUBMIT SELECTED
    // ══════════════════════════════════════════════════════════════════════

    public function bulkSubmitSelectedDeliveryNotes(Request $request)
    {
        $request->validate([
            'branch_ids'    => 'required|array|min:1',
            'branch_ids.*'  => 'required|integer|exists:tenant.branches,id',
            'delivery_date' => 'required|date',
        ]);

        $branchIds   = array_map('intval', $request->branch_ids);
        $date        = $request->delivery_date;
        $now         = now();
        $submitterId = Auth::id();

        $pendingNotes = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->whereIn('branch_id',   $branchIds)
            ->where('delivery_date', $date)
            ->where('submitted',     false)
            ->get();

        if ($pendingNotes->isEmpty()) {
            return response()->json([
                'status' => 200,
                'info'   => 'No pending delivery notes found for the selected branches on ' . Carbon::parse($date)->format('d M Y') . '.',
            ]);
        }

        $notesSubmittedCount = 0;
        $branchesAffected    = [];

        DB::connection('tenant')->transaction(function () use (
            $pendingNotes, $submitterId, $now, &$notesSubmittedCount, &$branchesAffected
        ) {
            foreach ($pendingNotes as $note) {
                $branchId      = (int)   $note->branch_id;
                $baseProductId = (int)   $note->base_product_id;
                $deliveredQty  = (float) $note->quantity;

                if ($deliveredQty <= 0) continue;

                DB::connection('tenant')
                    ->table('retail_deliverynotes')
                    ->where('id', $note->id)
                    ->update([
                        'submitted'    => true,
                        'submitted_by' => $submitterId,
                        'submitted_at' => $now,
                        'updated_at'   => $now,
                    ]);

                $existingBranchProduct = DB::connection('tenant')
                    ->table('retail_branch_products')
                    ->where('branch_id',       $branchId)
                    ->where('base_product_id', $baseProductId)
                    ->first();

                if ($existingBranchProduct) {
                    DB::connection('tenant')
                        ->table('retail_branch_products')
                        ->where('id', $existingBranchProduct->id)
                        ->update([
                            'stock_quantity' => (float) $existingBranchProduct->stock_quantity + $deliveredQty,
                            'updated_at'     => $now,
                        ]);
                } else {
                    DB::connection('tenant')
                        ->table('retail_branch_products')
                        ->insert([
                            'branch_id'       => $branchId,
                            'base_product_id' => $baseProductId,
                            'selling_price'   => $note->selling_price,
                            'cost_price'      => $note->cost_price,
                            'stock_quantity'  => $deliveredQty,
                            'created_at'      => $now,
                            'updated_at'      => $now,
                        ]);
                }

                $branchesAffected[$branchId] = true;
                $notesSubmittedCount++;
            }
        });

        if ($notesSubmittedCount === 0) {
            return response()->json([
                'status' => 200,
                'info'   => 'All selected notes had zero quantity and were skipped.',
            ]);
        }

        $branchCount = count($branchesAffected);

        return response()->json([
            'status'  => 200,
            'success' => $notesSubmittedCount . ' delivery note' . ($notesSubmittedCount > 1 ? 's' : '') .
                         ' submitted across ' . $branchCount . ' branch' . ($branchCount > 1 ? 'es' : '') .
                         '. Branch stock updated.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  BULK UNSUBMIT SELECTED
    // ══════════════════════════════════════════════════════════════════════

    public function bulkUnsubmitSelectedDeliveryNotes(Request $request)
    {
        $request->validate([
            'branch_ids'    => 'required|array|min:1',
            'branch_ids.*'  => 'required|integer|exists:tenant.branches,id',
            'delivery_date' => 'required|date',
        ]);

        $branchIds = array_map('intval', $request->branch_ids);
        $date      = $request->delivery_date;
        $now       = now();

        $submittedNotes = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->whereIn('branch_id',   $branchIds)
            ->where('delivery_date', $date)
            ->where('submitted',     true)
            ->get();

        if ($submittedNotes->isEmpty()) {
            return response()->json([
                'status' => 200,
                'info'   => 'No submitted delivery notes found for the selected branches on ' . Carbon::parse($date)->format('d M Y') . '.',
            ]);
        }

        $notesReversedCount = 0;
        $branchesAffected   = [];

        DB::connection('tenant')->transaction(function () use (
            $submittedNotes, $now, &$notesReversedCount, &$branchesAffected
        ) {
            foreach ($submittedNotes as $note) {
                $branchId      = (int)   $note->branch_id;
                $baseProductId = (int)   $note->base_product_id;
                $deliveredQty  = (float) $note->quantity;

                DB::connection('tenant')
                    ->table('retail_deliverynotes')
                    ->where('id', $note->id)
                    ->update([
                        'submitted'    => false,
                        'submitted_by' => null,
                        'submitted_at' => null,
                        'updated_at'   => $now,
                    ]);

                if ($deliveredQty > 0) {
                    $existingBranchProduct = DB::connection('tenant')
                        ->table('retail_branch_products')
                        ->where('branch_id',       $branchId)
                        ->where('base_product_id', $baseProductId)
                        ->first();

                    if ($existingBranchProduct) {
                        $reversedQty = max(0, (float) $existingBranchProduct->stock_quantity - $deliveredQty);
                        DB::connection('tenant')
                            ->table('retail_branch_products')
                            ->where('id', $existingBranchProduct->id)
                            ->update([
                                'stock_quantity' => $reversedQty,
                                'updated_at'     => $now,
                            ]);
                    }
                }

                $branchesAffected[$branchId] = true;
                $notesReversedCount++;
            }
        });

        if ($notesReversedCount === 0) {
            return response()->json(['status' => 200, 'info' => 'No notes were reversed.']);
        }

        $branchCount = count($branchesAffected);

        return response()->json([
            'status'  => 200,
            'success' => $notesReversedCount . ' delivery note' . ($notesReversedCount > 1 ? 's' : '') .
                         ' unsubmitted across ' . $branchCount . ' branch' . ($branchCount > 1 ? 'es' : '') .
                         '. Stock has been reversed.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  BULK DELETE SELECTED
    // ══════════════════════════════════════════════════════════════════════

    public function bulkDeleteSelectedDeliveryNotes(Request $request)
    {
        $request->validate([
            'branch_ids'    => 'required|array|min:1',
            'branch_ids.*'  => 'required|integer|exists:tenant.branches,id',
            'delivery_date' => 'required|date',
        ]);

        $branchIds = array_map('intval', $request->branch_ids);
        $date      = $request->delivery_date;

        $notesToDelete = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->whereIn('branch_id',   $branchIds)
            ->where('delivery_date', $date)
            ->get();

        if ($notesToDelete->isEmpty()) {
            return response()->json([
                'status' => 200,
                'info'   => 'No delivery notes found for the selected branches on ' . Carbon::parse($date)->format('d M Y') . '.',
            ]);
        }

        $noteIds      = $notesToDelete->pluck('id')->toArray();
        $deletedCount = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->whereIn('id', $noteIds)
            ->delete();

        return response()->json([
            'status'  => 200,
            'success' => $deletedCount . ' delivery note' . ($deletedCount > 1 ? 's' : '') .
                         ' permanently deleted for ' . count($branchIds) . ' branch' . (count($branchIds) > 1 ? 'es' : '') . '.',
        ]);
    }





    
// ══════════════════════════════════════════════════════════════════════════════
//  NEW ROUTE: retail.operations.deliverynotes.branch.details
//  (GET) shows the deliverynote-details blade for a specific branch+date
// ══════════════════════════════════════════════════════════════════════════════
public function showBranchDeliveryNoteDetailsView(Request $request)
{
    $request->validate([
        'branch_id' => 'required|integer|exists:tenant.branches,id',
        'date'      => 'required|date',
    ]);

    $branch = DB::connection('tenant')
        ->table('branches')
        ->where('id', (int) $request->branch_id)
        ->first(['id', 'name']);

    if (! $branch) {
        abort(404, 'Branch not found.');
    }

    return view('operations.retail.deliverynote-details', [
        'branch'       => $branch,
        'deliveryDate' => $request->date,
        'displayDate'  => \Carbon\Carbon::parse($request->date)->format('d M Y'),
    ]);
}


// ══════════════════════════════════════════════════════════════════════════════
//  FETCH DELIVERY NOTE LINES FOR A BRANCH ON A DATE  (AJAX — GET)
//  Route: retail.operations.deliverynotes.branch.lines
// ══════════════════════════════════════════════════════════════════════════════
public function fetchBranchDeliveryNoteLines(Request $request)
{
    $request->validate([
        'branch_id'     => 'required|integer|exists:tenant.branches,id',
        'delivery_date' => 'required|date',
    ]);

    $branchId = (int) $request->branch_id;
    $date     = $request->delivery_date;

    $lines = DB::connection('tenant')
        ->table('retail_deliverynotes as rdn')
        ->join('retail_base_products as rbp', 'rbp.id', '=', 'rdn.base_product_id')
        ->leftJoin('users as submitter', 'submitter.id', '=', 'rdn.submitted_by')
        ->where('rdn.branch_id',     $branchId)
        ->where('rdn.delivery_date', $date)
        ->select(
            'rdn.id',
            'rdn.quantity',
            'rdn.cost_price',
            'rdn.selling_price',
            'rdn.submitted',
            'rdn.submitted_at',
            'rbp.name as product_name',
            'rbp.code as product_code',
            'rbp.unit as product_unit',
            'submitter.name as submitted_by_name'
        )
        ->orderBy('rbp.name')
        ->get()
        ->map(function ($n) {
            $qty  = (float) $n->quantity;
            $cost = (float) ($n->cost_price    ?? 0);
            $sell = (float) ($n->selling_price ?? 0);
            return [
                'id'               => $n->id,
                'product_name'     => $n->product_name,
                'product_code'     => $n->product_code,
                'product_unit'     => $n->product_unit,
                'quantity'         => $qty,
                'cost_price'       => $cost,
                'selling_price'    => $sell,
                'cost_value'       => round($qty * $cost, 2),
                'sell_value'       => round($qty * $sell, 2),
                'submitted'        => (bool) $n->submitted,
                'submitted_by_name'=> $n->submitted_by_name,
                'submitted_at'     => $n->submitted_at
                    ? \Carbon\Carbon::parse($n->submitted_at)->format('d M Y H:i')
                    : null,
            ];
        });

    return response()->json([
        'status' => 200,
        'lines'  => $lines->values(),
    ]);
}


// ══════════════════════════════════════════════════════════════════════════════
//  SINGLE LINE: SUBMIT  (POST)
//  Route: retail.operations.deliverynotes.line.submit
// ══════════════════════════════════════════════════════════════════════════════
public function submitSingleDeliveryNoteLine(Request $request)
{
    $request->validate([
        'note_id'       => 'required|integer|exists:tenant.retail_deliverynotes,id',
        'branch_id'     => 'required|integer|exists:tenant.branches,id',
        'delivery_date' => 'required|date',
    ]);

    $noteId      = (int) $request->note_id;
    $branchId    = (int) $request->branch_id;
    $now         = now();
    $submitterId = Auth::id();

    $note = DB::connection('tenant')
        ->table('retail_deliverynotes')
        ->where('id',        $noteId)
        ->where('branch_id', $branchId)
        ->where('submitted', false)
        ->first();

    if (! $note) {
        return response()->json(['status' => 200, 'info' => 'This line is already submitted or does not exist.']);
    }

    $deliveredQty  = (float) $note->quantity;
    $baseProductId = (int)   $note->base_product_id;

    if ($deliveredQty <= 0) {
        return response()->json(['status' => 200, 'info' => 'Cannot submit a line with zero quantity.']);
    }

    DB::connection('tenant')->transaction(function () use (
        $note, $noteId, $branchId, $baseProductId, $deliveredQty, $submitterId, $now
    ) {
        DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('id', $noteId)
            ->update([
                'submitted'    => true,
                'submitted_by' => $submitterId,
                'submitted_at' => $now,
                'updated_at'   => $now,
            ]);

        $existing = DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('branch_id',       $branchId)
            ->where('base_product_id', $baseProductId)
            ->first();

        if ($existing) {
            DB::connection('tenant')
                ->table('retail_branch_products')
                ->where('id', $existing->id)
                ->update([
                    'stock_quantity' => (float) $existing->stock_quantity + $deliveredQty,
                    'updated_at'     => $now,
                ]);
        } else {
            DB::connection('tenant')
                ->table('retail_branch_products')
                ->insert([
                    'branch_id'       => $branchId,
                    'base_product_id' => $baseProductId,
                    'selling_price'   => $note->selling_price,
                    'cost_price'      => $note->cost_price,
                    'stock_quantity'  => $deliveredQty,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
        }
    });

    return response()->json(['status' => 200, 'success' => 'Delivery note line submitted and stock updated.']);
}


// ══════════════════════════════════════════════════════════════════════════════
//  SINGLE LINE: UNSUBMIT  (POST)
//  Route: retail.operations.deliverynotes.line.unsubmit
// ══════════════════════════════════════════════════════════════════════════════
public function unsubmitSingleDeliveryNoteLine(Request $request)
{
    $request->validate([
        'note_id'       => 'required|integer|exists:tenant.retail_deliverynotes,id',
        'branch_id'     => 'required|integer|exists:tenant.branches,id',
        'delivery_date' => 'required|date',
    ]);

    $noteId   = (int) $request->note_id;
    $branchId = (int) $request->branch_id;
    $now      = now();

    $note = DB::connection('tenant')
        ->table('retail_deliverynotes')
        ->where('id',        $noteId)
        ->where('branch_id', $branchId)
        ->where('submitted', true)
        ->first();

    if (! $note) {
        return response()->json(['status' => 200, 'info' => 'This line is already pending or does not exist.']);
    }

    $deliveredQty  = (float) $note->quantity;
    $baseProductId = (int)   $note->base_product_id;

    DB::connection('tenant')->transaction(function () use (
        $noteId, $branchId, $baseProductId, $deliveredQty, $now
    ) {
        DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('id', $noteId)
            ->update([
                'submitted'    => false,
                'submitted_by' => null,
                'submitted_at' => null,
                'updated_at'   => $now,
            ]);

        if ($deliveredQty > 0) {
            $existing = DB::connection('tenant')
                ->table('retail_branch_products')
                ->where('branch_id',       $branchId)
                ->where('base_product_id', $baseProductId)
                ->first();

            if ($existing) {
                DB::connection('tenant')
                    ->table('retail_branch_products')
                    ->where('id', $existing->id)
                    ->update([
                        'stock_quantity' => max(0, (float) $existing->stock_quantity - $deliveredQty),
                        'updated_at'     => $now,
                    ]);
            }
        }
    });

    return response()->json(['status' => 200, 'success' => 'Delivery note line unsubmitted and stock reversed.']);
}


// ══════════════════════════════════════════════════════════════════════════════
//  SINGLE LINE: DELETE  (POST)
//  Route: retail.operations.deliverynotes.line.delete
// ══════════════════════════════════════════════════════════════════════════════
public function deleteSingleDeliveryNoteLine(Request $request)
{
    $request->validate([
        'note_id'   => 'required|integer|exists:tenant.retail_deliverynotes,id',
        'branch_id' => 'required|integer|exists:tenant.branches,id',
    ]);

    $noteId   = (int) $request->note_id;
    $branchId = (int) $request->branch_id;

    $deleted = DB::connection('tenant')
        ->table('retail_deliverynotes')
        ->where('id',        $noteId)
        ->where('branch_id', $branchId)
        ->delete();

    if (! $deleted) {
        return response()->json(['status' => 200, 'info' => 'Line not found or already deleted.']);
    }

    return response()->json(['status' => 200, 'success' => 'Delivery note line deleted.']);
}


// ══════════════════════════════════════════════════════════════════════════════
//  BULK LINES: SUBMIT  (POST)
//  Route: retail.operations.deliverynotes.lines.bulk.submit
// ══════════════════════════════════════════════════════════════════════════════
public function bulkSubmitDeliveryNoteLines(Request $request)
{
    $request->validate([
        'note_ids'      => 'required|array|min:1',
        'note_ids.*'    => 'required|integer|exists:tenant.retail_deliverynotes,id',
        'branch_id'     => 'required|integer|exists:tenant.branches,id',
        'delivery_date' => 'required|date',
    ]);

    $noteIds     = array_map('intval', $request->note_ids);
    $branchId    = (int) $request->branch_id;
    $now         = now();
    $submitterId = Auth::id();

    $notes = DB::connection('tenant')
        ->table('retail_deliverynotes')
        ->whereIn('id',      $noteIds)
        ->where('branch_id', $branchId)
        ->where('submitted', false)
        ->get();

    if ($notes->isEmpty()) {
        return response()->json(['status' => 200, 'info' => 'None of the selected lines are pending.']);
    }

    $count = 0;

    DB::connection('tenant')->transaction(function () use ($notes, $branchId, $submitterId, $now, &$count) {
        foreach ($notes as $note) {
            $qty           = (float) $note->quantity;
            $baseProductId = (int)   $note->base_product_id;
            if ($qty <= 0) continue;

            DB::connection('tenant')
                ->table('retail_deliverynotes')
                ->where('id', $note->id)
                ->update(['submitted' => true, 'submitted_by' => $submitterId, 'submitted_at' => $now, 'updated_at' => $now]);

            $existing = DB::connection('tenant')
                ->table('retail_branch_products')
                ->where('branch_id', $branchId)->where('base_product_id', $baseProductId)->first();

            if ($existing) {
                DB::connection('tenant')->table('retail_branch_products')->where('id', $existing->id)
                    ->update(['stock_quantity' => (float) $existing->stock_quantity + $qty, 'updated_at' => $now]);
            } else {
                DB::connection('tenant')->table('retail_branch_products')->insert([
                    'branch_id' => $branchId, 'base_product_id' => $baseProductId,
                    'selling_price' => $note->selling_price, 'cost_price' => $note->cost_price,
                    'stock_quantity' => $qty, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            $count++;
        }
    });

    if ($count === 0) {
        return response()->json(['status' => 200, 'info' => 'All selected lines had zero quantity and were skipped.']);
    }

    return response()->json(['status' => 200, 'success' => $count . ' line' . ($count > 1 ? 's' : '') . ' submitted and stock updated.']);
}


// ══════════════════════════════════════════════════════════════════════════════
//  BULK LINES: UNSUBMIT  (POST)
//  Route: retail.operations.deliverynotes.lines.bulk.unsubmit
// ══════════════════════════════════════════════════════════════════════════════
public function bulkUnsubmitDeliveryNoteLines(Request $request)
{
    $request->validate([
        'note_ids'      => 'required|array|min:1',
        'note_ids.*'    => 'required|integer|exists:tenant.retail_deliverynotes,id',
        'branch_id'     => 'required|integer|exists:tenant.branches,id',
        'delivery_date' => 'required|date',
    ]);

    $noteIds  = array_map('intval', $request->note_ids);
    $branchId = (int) $request->branch_id;
    $now      = now();

    $notes = DB::connection('tenant')
        ->table('retail_deliverynotes')
        ->whereIn('id',      $noteIds)
        ->where('branch_id', $branchId)
        ->where('submitted', true)
        ->get();

    if ($notes->isEmpty()) {
        return response()->json(['status' => 200, 'info' => 'None of the selected lines are submitted.']);
    }

    $count = 0;

    DB::connection('tenant')->transaction(function () use ($notes, $branchId, $now, &$count) {
        foreach ($notes as $note) {
            $qty           = (float) $note->quantity;
            $baseProductId = (int)   $note->base_product_id;

            DB::connection('tenant')
                ->table('retail_deliverynotes')
                ->where('id', $note->id)
                ->update(['submitted' => false, 'submitted_by' => null, 'submitted_at' => null, 'updated_at' => $now]);

            if ($qty > 0) {
                $existing = DB::connection('tenant')
                    ->table('retail_branch_products')
                    ->where('branch_id', $branchId)->where('base_product_id', $baseProductId)->first();
                if ($existing) {
                    DB::connection('tenant')->table('retail_branch_products')->where('id', $existing->id)
                        ->update(['stock_quantity' => max(0, (float) $existing->stock_quantity - $qty), 'updated_at' => $now]);
                }
            }
            $count++;
        }
    });

    return response()->json(['status' => 200, 'success' => $count . ' line' . ($count > 1 ? 's' : '') . ' unsubmitted and stock reversed.']);
}


// ══════════════════════════════════════════════════════════════════════════════
//  BULK LINES: DELETE  (POST)
//  Route: retail.operations.deliverynotes.lines.bulk.delete
// ══════════════════════════════════════════════════════════════════════════════
public function bulkDeleteDeliveryNoteLines(Request $request)
{
    $request->validate([
        'note_ids'   => 'required|array|min:1',
        'note_ids.*' => 'required|integer|exists:tenant.retail_deliverynotes,id',
        'branch_id'  => 'required|integer|exists:tenant.branches,id',
    ]);

    $noteIds  = array_map('intval', $request->note_ids);
    $branchId = (int) $request->branch_id;

    $deleted = DB::connection('tenant')
        ->table('retail_deliverynotes')
        ->whereIn('id',      $noteIds)
        ->where('branch_id', $branchId)
        ->delete();

    if (! $deleted) {
        return response()->json(['status' => 200, 'info' => 'No lines found to delete.']);
    }

    return response()->json(['status' => 200, 'success' => $deleted . ' line' . ($deleted > 1 ? 's' : '') . ' permanently deleted.']);
}


public function updateSingleDeliveryNoteLine(Request $request)
{
    $request->validate([
        'note_id'       => 'required|integer|exists:tenant.retail_deliverynotes,id',
        'branch_id'     => 'required|integer|exists:tenant.branches,id',
        'quantity'      => 'required|numeric|min:0',
        'cost_price'    => 'required|numeric|min:0',
        'selling_price' => 'required|numeric|min:0',
    ]);

    $noteId       = (int)   $request->note_id;
    $branchId     = (int)   $request->branch_id;
    $newQty       = (float) $request->quantity;
    $newCostPrice = (float) $request->cost_price;
    $newSellPrice = (float) $request->selling_price;
    $now          = now();

    // Fetch the existing note — must belong to the given branch
    $note = DB::connection('tenant')
        ->table('retail_deliverynotes')
        ->where('id',        $noteId)
        ->where('branch_id', $branchId)
        ->first();

    if (! $note) {
        return response()->json([
            'status' => 404,
            'error'  => 'Delivery note line not found.',
        ], 404);
    }

    $oldQty        = (float) $note->quantity;
    $baseProductId = (int)   $note->base_product_id;
    $wasSubmitted  = (bool)  $note->submitted;

    DB::connection('tenant')->transaction(function () use (
        $noteId, $branchId, $baseProductId,
        $oldQty, $newQty,
        $newCostPrice, $newSellPrice,
        $wasSubmitted, $now
    ) {
        // 1. Update the note line itself
        DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('id', $noteId)
            ->update([
                'quantity'      => $newQty,
                'cost_price'    => $newCostPrice,
                'selling_price' => $newSellPrice,
                'updated_at'    => $now,
            ]);

        // 2. If already submitted → adjust branch stock by the qty difference
        if ($wasSubmitted) {
            $qtyDelta = $newQty - $oldQty;

            $existing = DB::connection('tenant')
                ->table('retail_branch_products')
                ->where('branch_id',       $branchId)
                ->where('base_product_id', $baseProductId)
                ->first();

            if ($existing) {
                $adjustedStock = max(0, (float) $existing->stock_quantity + $qtyDelta);

                DB::connection('tenant')
                    ->table('retail_branch_products')
                    ->where('id', $existing->id)
                    ->update([
                        'stock_quantity' => $adjustedStock,
                        'selling_price'  => $newSellPrice,
                        'cost_price'     => $newCostPrice,
                        'updated_at'     => $now,
                    ]);
            } elseif ($newQty > 0) {
                DB::connection('tenant')
                    ->table('retail_branch_products')
                    ->insert([
                        'branch_id'       => $branchId,
                        'base_product_id' => $baseProductId,
                        'selling_price'   => $newSellPrice,
                        'cost_price'      => $newCostPrice,
                        'stock_quantity'  => $newQty,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ]);
            }
        }
    });

    // Re-fetch the updated note with product and submitter details
    // so the JS can rebuild the row without a separate loadTable() call.
    $updated = DB::connection('tenant')
        ->table('retail_deliverynotes as rdn')
        ->join('retail_base_products as rbp', 'rbp.id', '=', 'rdn.base_product_id')
        ->leftJoin('users as submitter', 'submitter.id', '=', 'rdn.submitted_by')
        ->where('rdn.id', $noteId)
        ->select(
            'rdn.id',
            'rdn.quantity',
            'rdn.cost_price',
            'rdn.selling_price',
            'rdn.submitted',
            'rdn.submitted_at',
            'rbp.name  as product_name',
            'rbp.code  as product_code',
            'rbp.unit  as product_unit',
            'submitter.name as submitted_by_name'
        )
        ->first();

    return response()->json([
        'status'  => 201,
        'success' => 'Delivery note line updated successfully.'
                   . ($wasSubmitted ? ' Branch stock adjusted.' : ''),
        'line'    => [
            'id'               => $updated->id,
            'product_name'     => $updated->product_name,
            'product_code'     => $updated->product_code,
            'product_unit'     => $updated->product_unit,
            'quantity'         => (float) $updated->quantity,
            'cost_price'       => (float) $updated->cost_price,
            'selling_price'    => (float) $updated->selling_price,
            'submitted'        => (bool)  $updated->submitted,
            'submitted_by_name'=> $updated->submitted_by_name,
            'submitted_at'     => $updated->submitted_at
                ? \Carbon\Carbon::parse($updated->submitted_at)->format('d M Y H:i')
                : null,
        ],
    ]);
}

// ══════════════════════════════════════════════════════════════════════════════
//  UPDATE — from the details view
//  Route: retail.operations.deliverynotes.line.update
// ══════════════════════════════════════════════════════════════════════════════
public function updateDeliverynoteFromDetailsView(Request $request)
{
    $request->validate([
        'note_id'       => 'required|integer|exists:tenant.retail_deliverynotes,id',
        'branch_id'     => 'required|integer|exists:tenant.branches,id',
        'quantity'      => 'required|numeric|min:0',
        'cost_price'    => 'required|numeric|min:0',
        'selling_price' => 'required|numeric|min:0',
    ]);

    $noteId       = (int)   $request->note_id;
    $branchId     = (int)   $request->branch_id;
    $newQty       = (float) $request->quantity;
    $newCostPrice = (float) $request->cost_price;
    $newSellPrice = (float) $request->selling_price;
    $now          = now();

    $note = DB::connection('tenant')
        ->table('retail_deliverynotes')
        ->where('id',        $noteId)
        ->where('branch_id', $branchId)
        ->first();

    if (! $note) {
        return response()->json(['status' => 404, 'error' => 'Delivery note line not found.'], 404);
    }

    $oldQty        = (float) $note->quantity;
    $baseProductId = (int)   $note->base_product_id;
    $wasSubmitted  = (bool)  $note->submitted;

    DB::connection('tenant')->transaction(function () use (
        $noteId, $branchId, $baseProductId,
        $oldQty, $newQty, $newCostPrice, $newSellPrice,
        $wasSubmitted, $now
    ) {
        DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('id', $noteId)
            ->update([
                'quantity'      => $newQty,
                'cost_price'    => $newCostPrice,
                'selling_price' => $newSellPrice,
                'updated_at'    => $now,
            ]);

        if ($wasSubmitted) {
            $qtyDelta = $newQty - $oldQty;
            $existing = DB::connection('tenant')
                ->table('retail_branch_products')
                ->where('branch_id',       $branchId)
                ->where('base_product_id', $baseProductId)
                ->first();

            if ($existing) {
                DB::connection('tenant')
                    ->table('retail_branch_products')
                    ->where('id', $existing->id)
                    ->update([
                        'stock_quantity' => max(0, (float) $existing->stock_quantity + $qtyDelta),
                        'selling_price'  => $newSellPrice,
                        'cost_price'     => $newCostPrice,
                        'updated_at'     => $now,
                    ]);
            } elseif ($newQty > 0) {
                DB::connection('tenant')
                    ->table('retail_branch_products')
                    ->insert([
                        'branch_id'       => $branchId,
                        'base_product_id' => $baseProductId,
                        'selling_price'   => $newSellPrice,
                        'cost_price'      => $newCostPrice,
                        'stock_quantity'  => $newQty,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ]);
            }
        }
    });

    $updated = DB::connection('tenant')
        ->table('retail_deliverynotes as rdn')
        ->join('retail_base_products as rbp', 'rbp.id', '=', 'rdn.base_product_id')
        ->leftJoin('users as submitter', 'submitter.id', '=', 'rdn.submitted_by')
        ->where('rdn.id', $noteId)
        ->select(
            'rdn.id', 'rdn.quantity', 'rdn.cost_price', 'rdn.selling_price',
            'rdn.submitted', 'rdn.submitted_at',
            'rbp.name as product_name', 'rbp.code as product_code', 'rbp.unit as product_unit',
            'submitter.name as submitted_by_name'
        )
        ->first();

    return response()->json([
        'status'  => 201,
        'success' => 'Line updated successfully.' . ($wasSubmitted ? ' Branch stock adjusted.' : ''),
        'line'    => [
            'id'                => $updated->id,
            'product_name'      => $updated->product_name,
            'product_code'      => $updated->product_code,
            'product_unit'      => $updated->product_unit,
            'quantity'          => (float) $updated->quantity,
            'cost_price'        => (float) $updated->cost_price,
            'selling_price'     => (float) $updated->selling_price,
            'submitted'         => (bool)  $updated->submitted,
            'submitted_by_name' => $updated->submitted_by_name,
            'submitted_at'      => $updated->submitted_at
                ? \Carbon\Carbon::parse($updated->submitted_at)->format('d M Y H:i')
                : null,
        ],
    ]);
}

// ══════════════════════════════════════════════════════════════════════════════
//  SUBMIT SINGLE — from the details view
//  Route: retail.operations.deliverynotes.line.submit
// ══════════════════════════════════════════════════════════════════════════════
public function submitDeliverynoteFromDetailsView(Request $request)
{
    $request->validate([
        'note_id'       => 'required|integer|exists:tenant.retail_deliverynotes,id',
        'branch_id'     => 'required|integer|exists:tenant.branches,id',
        'delivery_date' => 'required|date',
    ]);

    $noteId      = (int) $request->note_id;
    $branchId    = (int) $request->branch_id;
    $now         = now();
    $submitterId = Auth::id();

    $note = DB::connection('tenant')
        ->table('retail_deliverynotes')
        ->where('id',        $noteId)
        ->where('branch_id', $branchId)
        ->where('submitted', false)
        ->first();

    if (! $note) {
        return response()->json(['status' => 200, 'info' => 'This line is already submitted or does not exist.']);
    }

    $deliveredQty  = (float) $note->quantity;
    $baseProductId = (int)   $note->base_product_id;

    if ($deliveredQty <= 0) {
        return response()->json(['status' => 200, 'info' => 'Cannot submit a line with zero quantity.']);
    }

    DB::connection('tenant')->transaction(function () use (
        $note, $noteId, $branchId, $baseProductId, $deliveredQty, $submitterId, $now
    ) {
        DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('id', $noteId)
            ->update([
                'submitted'    => true,
                'submitted_by' => $submitterId,
                'submitted_at' => $now,
                'updated_at'   => $now,
            ]);

        $existing = DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('branch_id',       $branchId)
            ->where('base_product_id', $baseProductId)
            ->first();

        if ($existing) {
            DB::connection('tenant')
                ->table('retail_branch_products')
                ->where('id', $existing->id)
                ->update([
                    'stock_quantity' => (float) $existing->stock_quantity + $deliveredQty,
                    'updated_at'     => $now,
                ]);
        } else {
            DB::connection('tenant')
                ->table('retail_branch_products')
                ->insert([
                    'branch_id'       => $branchId,
                    'base_product_id' => $baseProductId,
                    'selling_price'   => $note->selling_price,
                    'cost_price'      => $note->cost_price,
                    'stock_quantity'  => $deliveredQty,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
        }
    });

    return response()->json(['status' => 200, 'success' => 'Line submitted and stock updated.']);
}

// ══════════════════════════════════════════════════════════════════════════════
//  UNSUBMIT SINGLE — from the details view
//  Route: retail.operations.deliverynotes.line.unsubmit
// ══════════════════════════════════════════════════════════════════════════════
public function unsubmitDeliverynoteFromDetailsView(Request $request)
{
    $request->validate([
        'note_id'       => 'required|integer|exists:tenant.retail_deliverynotes,id',
        'branch_id'     => 'required|integer|exists:tenant.branches,id',
        'delivery_date' => 'required|date',
    ]);

    $noteId   = (int) $request->note_id;
    $branchId = (int) $request->branch_id;
    $now      = now();

    $note = DB::connection('tenant')
        ->table('retail_deliverynotes')
        ->where('id',        $noteId)
        ->where('branch_id', $branchId)
        ->where('submitted', true)
        ->first();

    if (! $note) {
        return response()->json(['status' => 200, 'info' => 'This line is already pending or does not exist.']);
    }

    $deliveredQty  = (float) $note->quantity;
    $baseProductId = (int)   $note->base_product_id;

    DB::connection('tenant')->transaction(function () use (
        $noteId, $branchId, $baseProductId, $deliveredQty, $now
    ) {
        DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('id', $noteId)
            ->update([
                'submitted'    => false,
                'submitted_by' => null,
                'submitted_at' => null,
                'updated_at'   => $now,
            ]);

        if ($deliveredQty > 0) {
            $existing = DB::connection('tenant')
                ->table('retail_branch_products')
                ->where('branch_id',       $branchId)
                ->where('base_product_id', $baseProductId)
                ->first();

            if ($existing) {
                DB::connection('tenant')
                    ->table('retail_branch_products')
                    ->where('id', $existing->id)
                    ->update([
                        'stock_quantity' => max(0, (float) $existing->stock_quantity - $deliveredQty),
                        'updated_at'     => $now,
                    ]);
            }
        }
    });

    return response()->json(['status' => 200, 'success' => 'Line unsubmitted and stock reversed.']);
}

// ══════════════════════════════════════════════════════════════════════════════
//  DELETE SINGLE — from the details view
//  Route: retail.operations.deliverynotes.line.delete
// ══════════════════════════════════════════════════════════════════════════════
public function deleteDeliverynoteFromDetailsView(Request $request)
{
    $request->validate([
        'note_id'   => 'required|integer|exists:tenant.retail_deliverynotes,id',
        'branch_id' => 'required|integer|exists:tenant.branches,id',
    ]);

    $noteId   = (int) $request->note_id;
    $branchId = (int) $request->branch_id;

    $deleted = DB::connection('tenant')
        ->table('retail_deliverynotes')
        ->where('id',        $noteId)
        ->where('branch_id', $branchId)
        ->delete();

    if (! $deleted) {
        return response()->json(['status' => 200, 'info' => 'Line not found or already deleted.']);
    }

    return response()->json(['status' => 200, 'success' => 'Delivery note line deleted.']);
}

// ══════════════════════════════════════════════════════════════════════════════
//  BULK SUBMIT — from the details view
//  Route: retail.operations.deliverynotes.lines.bulk.submit
// ══════════════════════════════════════════════════════════════════════════════
public function bulkSubmitDeliverynoteLinesFromDetailsView(Request $request)
{
    $request->validate([
        'note_ids'      => 'required|array|min:1',
        'note_ids.*'    => 'required|integer|exists:tenant.retail_deliverynotes,id',
        'branch_id'     => 'required|integer|exists:tenant.branches,id',
        'delivery_date' => 'required|date',
    ]);

    $noteIds     = array_map('intval', $request->note_ids);
    $branchId    = (int) $request->branch_id;
    $now         = now();
    $submitterId = Auth::id();

    $notes = DB::connection('tenant')
        ->table('retail_deliverynotes')
        ->whereIn('id',      $noteIds)
        ->where('branch_id', $branchId)
        ->where('submitted', false)
        ->get();

    if ($notes->isEmpty()) {
        return response()->json(['status' => 200, 'info' => 'None of the selected lines are pending.']);
    }

    $count = 0;

    DB::connection('tenant')->transaction(function () use ($notes, $branchId, $submitterId, $now, &$count) {
        foreach ($notes as $note) {
            $qty           = (float) $note->quantity;
            $baseProductId = (int)   $note->base_product_id;
            if ($qty <= 0) continue;

            DB::connection('tenant')->table('retail_deliverynotes')->where('id', $note->id)->update([
                'submitted' => true, 'submitted_by' => $submitterId,
                'submitted_at' => $now, 'updated_at' => $now,
            ]);

            $existing = DB::connection('tenant')->table('retail_branch_products')
                ->where('branch_id', $branchId)->where('base_product_id', $baseProductId)->first();

            if ($existing) {
                DB::connection('tenant')->table('retail_branch_products')->where('id', $existing->id)
                    ->update(['stock_quantity' => (float) $existing->stock_quantity + $qty, 'updated_at' => $now]);
            } else {
                DB::connection('tenant')->table('retail_branch_products')->insert([
                    'branch_id' => $branchId, 'base_product_id' => $baseProductId,
                    'selling_price' => $note->selling_price, 'cost_price' => $note->cost_price,
                    'stock_quantity' => $qty, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            $count++;
        }
    });

    if ($count === 0) {
        return response()->json(['status' => 200, 'info' => 'All selected lines had zero quantity and were skipped.']);
    }

    return response()->json([
        'status'  => 200,
        'success' => $count . ' line' . ($count > 1 ? 's' : '') . ' submitted and stock updated.',
    ]);
}

// ══════════════════════════════════════════════════════════════════════════════
//  BULK UNSUBMIT — from the details view
//  Route: retail.operations.deliverynotes.lines.bulk.unsubmit
// ══════════════════════════════════════════════════════════════════════════════
public function bulkUnsubmitDeliverynoteLinesFromDetailsView(Request $request)
{
    $request->validate([
        'note_ids'      => 'required|array|min:1',
        'note_ids.*'    => 'required|integer|exists:tenant.retail_deliverynotes,id',
        'branch_id'     => 'required|integer|exists:tenant.branches,id',
        'delivery_date' => 'required|date',
    ]);

    $noteIds  = array_map('intval', $request->note_ids);
    $branchId = (int) $request->branch_id;
    $now      = now();

    $notes = DB::connection('tenant')
        ->table('retail_deliverynotes')
        ->whereIn('id',      $noteIds)
        ->where('branch_id', $branchId)
        ->where('submitted', true)
        ->get();

    if ($notes->isEmpty()) {
        return response()->json(['status' => 200, 'info' => 'None of the selected lines are submitted.']);
    }

    $count = 0;

    DB::connection('tenant')->transaction(function () use ($notes, $branchId, $now, &$count) {
        foreach ($notes as $note) {
            $qty           = (float) $note->quantity;
            $baseProductId = (int)   $note->base_product_id;

            DB::connection('tenant')->table('retail_deliverynotes')->where('id', $note->id)->update([
                'submitted' => false, 'submitted_by' => null,
                'submitted_at' => null, 'updated_at' => $now,
            ]);

            if ($qty > 0) {
                $existing = DB::connection('tenant')->table('retail_branch_products')
                    ->where('branch_id', $branchId)->where('base_product_id', $baseProductId)->first();
                if ($existing) {
                    DB::connection('tenant')->table('retail_branch_products')->where('id', $existing->id)
                        ->update(['stock_quantity' => max(0, (float) $existing->stock_quantity - $qty), 'updated_at' => $now]);
                }
            }
            $count++;
        }
    });

    return response()->json([
        'status'  => 200,
        'success' => $count . ' line' . ($count > 1 ? 's' : '') . ' unsubmitted and stock reversed.',
    ]);
}

// ══════════════════════════════════════════════════════════════════════════════
//  BULK DELETE — from the details view
//  Route: retail.operations.deliverynotes.lines.bulk.delete
// ══════════════════════════════════════════════════════════════════════════════
public function bulkDeleteDeliverynoteLinesFromDetailsView(Request $request)
{
    $request->validate([
        'note_ids'   => 'required|array|min:1',
        'note_ids.*' => 'required|integer|exists:tenant.retail_deliverynotes,id',
        'branch_id'  => 'required|integer|exists:tenant.branches,id',
    ]);

    $noteIds  = array_map('intval', $request->note_ids);
    $branchId = (int) $request->branch_id;

    $deleted = DB::connection('tenant')
        ->table('retail_deliverynotes')
        ->whereIn('id',      $noteIds)
        ->where('branch_id', $branchId)
        ->delete();

    if (! $deleted) {
        return response()->json(['status' => 200, 'info' => 'No lines found to delete.']);
    }

    return response()->json([
        'status'  => 200,
        'success' => $deleted . ' line' . ($deleted > 1 ? 's' : '') . ' permanently deleted.',
    ]);
}






}