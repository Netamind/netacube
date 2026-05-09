<?php

namespace App\Http\Controllers\Operations\Retail;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Dompdf\Dompdf;
use Dompdf\Options;
use DB;

class RetailAuditLogsController extends Controller
{
    // ── View ─────────────────────────────────────────────────────────────

    public function showAuditLogsView()
    {
        return view('operations.retail.auditlogs');
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function fetchFormattedLog(int $id): ?array
    {
        $log = DB::connection('tenant')
            ->table('retail_inventory_logs as ril')
            ->join('retail_base_products as rbp', 'rbp.id', '=', 'ril.product_id')
            ->join('users as u', 'u.id', '=', 'ril.user_id')
            ->where('ril.id', $id)
            ->select(
                'ril.*',
                'rbp.name          as product_name',
                'rbp.code          as product_code',
                'rbp.unit          as product_unit',
                'rbp.selling_price as product_sell_price',
                'u.name            as user_name'
            )
            ->first();

        return $log ? $this->formatLog($log) : null;
    }

    private function formatLog($log): array
    {
        $change     = (float) $log->stock_change;
        $isReversed = str_contains(strtolower($log->action_reason ?? ''), 'reversed');

        return [
            'id'                  => $log->id,
            'row'                 => 'row' . $log->id,
            'product_id'          => $log->product_id,
            'branch_id'           => $log->branch_id,
            'product_name'        => $log->product_name        ?? null,
            'product_code'        => $log->product_code        ?? null,
            'product_unit'        => $log->product_unit        ?? null,
            'product_sell_price'  => $log->product_sell_price  ?? null,
            'user_name'           => $log->user_name           ?? null,
            'user_device_details' => $log->user_device_details ?? null,
            'stock_before'        => $log->stock_before,
            'stock_change'        => $log->stock_change,
            'stock_after'         => $log->stock_after,
            'action_reason'       => $log->action_reason,
            'log_date'            => $log->log_date,
            'log_time'            => $log->log_time,
            'is_reversed'         => $isReversed,
            'change_direction'    => $change > 0 ? 'in' : ($change < 0 ? 'out' : 'zero'),
        ];
    }

    // ── AJAX: Dates with logs ─────────────────────────────────────────────

    public function getDatesWithLogs(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:tenant.branches,id',
        ]);

        $dates = DB::connection('tenant')
            ->table('retail_inventory_logs')
            ->where('branch_id', $request->branch_id)
            ->where('log_date', '>=', now()->subMonths(3)->toDateString())
            ->distinct()
            ->orderByDesc('log_date')
            ->pluck('log_date');

        return response()->json(['status' => 200, 'dates' => $dates]);
    }

    // ── AJAX: Logs for a specific date ────────────────────────────────────
    // Summaries are VALUE-based: qty × selling_price per entry.

    public function getLogsByDate(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:tenant.branches,id',
            'log_date'  => 'required|date',
        ]);

        $logs = DB::connection('tenant')
            ->table('retail_inventory_logs as ril')
            ->join('retail_base_products as rbp', 'rbp.id', '=', 'ril.product_id')
            ->join('users as u', 'u.id', '=', 'ril.user_id')
            ->where('ril.branch_id', $request->branch_id)
            ->where('ril.log_date',  $request->log_date)
            ->select(
                'ril.*',
                'rbp.name          as product_name',
                'rbp.code          as product_code',
                'rbp.unit          as product_unit',
                'rbp.selling_price as product_sell_price',
                'u.name            as user_name'
            )
            ->orderByDesc('ril.log_time')
            ->get();

        $formatted = $logs->map(fn($l) => $this->formatLog($l))->values();

        // Value-based: abs(qty) × price
        $summaryIn = $logs
            ->filter(fn($l) => (float) $l->stock_change > 0)
            ->sum(fn($l) => abs((float) $l->stock_change) * (float) ($l->product_sell_price ?? 0));

        $summaryOut = $logs
            ->filter(fn($l) => (float) $l->stock_change < 0)
            ->sum(fn($l) => abs((float) $l->stock_change) * (float) ($l->product_sell_price ?? 0));

        return response()->json([
            'status'      => 200,
            'logs'        => $formatted,
            'summary_in'  => round($summaryIn,  2),
            'summary_out' => round($summaryOut, 2),
            'summary_net' => round($summaryIn - $summaryOut, 2),
        ]);
    }

 


// ══════════════════════════════════════════════════════════════════════════
//  Tables used:
//    company_info             → company header / page footer
//    branches                 → branch details section
//    users                    → prepared-by (Auth user)
//    retail_inventory_logs    → raw log rows
//    retail_base_products     → product name, code, unit, selling_price
//    retail_branch_products   → batch_number, expiry_date, branch selling_price
// ══════════════════════════════════════════════════════════════════════════

    public function downloadPdf(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:tenant.branches,id',
            'log_date'  => 'required|date',
            'direction' => 'required|in:positive,negative,all',
        ]);

        $direction  = $request->direction;
        $isPositive = $direction === 'positive';
        $isNegative = $direction === 'negative';
        $branchId   = (int) $request->branch_id;

        // ── 1. Company info ───────────────────────────────────────────────
        $company = DB::connection('tenant')
            ->table('company_info')
            ->first();

        // ── 2. Branch ─────────────────────────────────────────────────────
        $branch = DB::connection('tenant')
            ->table('branches')
            ->where('id', $branchId)
            ->first();

        if (! $branch) {
            abort(404, 'Branch not found.');
        }

        // ── 3. Auth user (Prepared By) ────────────────────────────────────
        $preparedByUser = DB::connection('tenant')
            ->table('users')
            ->where('id', Auth::id())
            ->select('name', 'position', 'department', 'email')
            ->first();

        // ── 4. Raw log rows (no joins) ────────────────────────────────────
        $logsQuery = DB::connection('tenant')
            ->table('retail_inventory_logs')
            ->where('branch_id', $branchId)
            ->where('log_date',  $request->log_date)
            ->orderByDesc('log_time');

        if ($isPositive) {
            $logsQuery->where('stock_change', '>', 0);
        } elseif ($isNegative) {
            $logsQuery->where('stock_change', '<', 0);
        }

        $rawLogs = $logsQuery->get();

        // ── 5. Collect unique product IDs from the logs ───────────────────
        $productIds = $rawLogs->pluck('product_id')->unique()->values()->all();

        // ── 6. Base products (keyed by id) ────────────────────────────────
        $baseProducts = collect();
        if (! empty($productIds)) {
            $baseProducts = DB::connection('tenant')
                ->table('retail_base_products')
                ->whereIn('id', $productIds)
                ->select('id', 'name', 'code', 'unit', 'selling_price')
                ->get()
                ->keyBy('id');
        }

        // ── 7. Branch products (keyed by base_product_id) ─────────────────
        //    One record per product per branch — no join needed.
        $branchProducts = collect();
        if (! empty($productIds)) {
            $branchProducts = DB::connection('tenant')
                ->table('retail_branch_products')
                ->where('branch_id', $branchId)
                ->whereIn('base_product_id', $productIds)
                ->select('base_product_id', 'selling_price', 'batch_number', 'expiry_date')
                ->get()
                ->keyBy('base_product_id');
        }

        // ── 8. Stitch everything onto each log row in PHP ─────────────────
        $logs = $rawLogs->map(function ($log) use ($baseProducts, $branchProducts) {
            $base   = $baseProducts->get($log->product_id);
            $branch = $branchProducts->get($log->product_id);

            // Prefer branch-level selling price; fall back to base product price
            $log->product_name       = $base->name          ?? '—';
            $log->product_code       = $base->code          ?? '—';
            $log->product_unit       = $base->unit          ?? '—';
            $log->product_sell_price = $branch->selling_price
                                        ?? $base->selling_price
                                        ?? 0;
            $log->batch_number       = $branch->batch_number ?? '—';
            $log->expiry_date        = $branch->expiry_date  ?? null;

            return $log;
        });

        // ── 9. Value-based summaries ──────────────────────────────────────
        $summaryIn = $logs
            ->filter(fn($l) => (float) $l->stock_change > 0)
            ->sum(fn($l) => abs((float) $l->stock_change) * (float) ($l->product_sell_price ?? 0));

        $summaryOut = $logs
            ->filter(fn($l) => (float) $l->stock_change < 0)
            ->sum(fn($l) => abs((float) $l->stock_change) * (float) ($l->product_sell_price ?? 0));

        $totalQty   = $logs->sum(fn($l) => abs((float) $l->stock_change));
        $totalValue = ($direction === 'all')
            ? ($summaryIn + $summaryOut)
            : ($isPositive ? $summaryIn : $summaryOut);

        // ── 10. Accent palette ────────────────────────────────────────────
        if ($isPositive) {
            $accentHdr = '#059669';
            $dirLabel  = 'Added Items';
        } elseif ($isNegative) {
            $accentHdr = '#dc2626';
            $dirLabel  = 'Subtracted Items';
        } else {
            $accentHdr = '#4B5EBD';
            $dirLabel  = 'All Movements';
        }

        // ── 11. Render Blade view → HTML ──────────────────────────────────
        $html = view('operations.retail.auditlogspdf', [
            'company'        => $company,
            'branch'         => $branch,
            'preparedByUser' => $preparedByUser,
            'logs'           => $logs,
            'direction'      => $direction,
            'summaryIn'      => round($summaryIn,  2),
            'summaryOut'     => round($summaryOut, 2),
            'totalQty'       => round($totalQty,   2),
            'totalValue'     => round($totalValue,  2),
            'accentHdr'      => $accentHdr,
            'dirLabel'       => $dirLabel,
            'formattedDate'  => \Carbon\Carbon::parse($request->log_date)->format('d F Y'),
            'generatedAt'    => now()->format('d M Y, H:i'),
            'generatedBy'    => $preparedByUser->name ?? (Auth::user()->name ?? 'System'),
        ])->render();

        // ── 12. DomPDF ────────────────────────────────────────────────────
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // ── 13. Safe filename ─────────────────────────────────────────────
        $filename = ($branch->name ?? 'Branch') . ' — ' . $dirLabel . ' — ' . $request->log_date . '.pdf';
        $filename = preg_replace('/[^\w\s\-\.]+/u', '', $filename);

        // ── 14. Attachment response (background download, user stays on page)
        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ]);
    }











    // ── Update ────────────────────────────────────────────────────────────

    public function updateLog(Request $request)
    {
        $request->validate([
            'id'            => 'required|integer|exists:tenant.retail_inventory_logs,id',
            'stock_before'  => 'required|numeric',
            'stock_change'  => 'required|numeric',
            'stock_after'   => 'required|numeric',
            'action_reason' => 'required|string|max:1000',
            'log_date'      => 'required|date',
            'log_time'      => 'required',
        ]);

        $current = DB::connection('tenant')
            ->table('retail_inventory_logs')
            ->where('id', $request->id)
            ->first();

        if (! $current) {
            return response()->json(['error' => 'Log entry not found.', 'status' => 404]);
        }

        DB::connection('tenant')
            ->table('retail_inventory_logs')
            ->where('id', $request->id)
            ->update([
                'stock_before'  => $request->stock_before,
                'stock_change'  => $request->stock_change,
                'stock_after'   => $request->stock_after,
                'action_reason' => trim($request->action_reason),
                'log_date'      => $request->log_date,
                'log_time'      => $request->log_time,
            ]);

        $formatted = $this->fetchFormattedLog((int) $request->id);

        return response()->json([
            'success' => 'Log entry updated successfully.',
            'status'  => 201,
            'log'     => $formatted,
        ]);
    }

    // ── Single Reverse ────────────────────────────────────────────────────

    public function reverseLog(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:tenant.retail_inventory_logs,id',
        ]);

        $bulkRequest = new Request(['ids' => [$request->id]]);
        $response    = $this->bulkReverse($bulkRequest);
        $data        = $response->getData(true);

        if ($data['status'] === 201 && ! empty($data['logs'])) {
            return response()->json([
                'success' => 'Reversal entry created successfully.',
                'status'  => 201,
                'log'     => $data['logs'][0],
                'logs'    => $data['logs'],
            ]);
        }

        return $response;
    }

    // ── Bulk Reverse ──────────────────────────────────────────────────────

    public function bulkReverse(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:tenant.retail_inventory_logs,id',
        ]);

        $originals = DB::connection('tenant')
            ->table('retail_inventory_logs')
            ->whereIn('id', $request->ids)
            ->get();

        if ($originals->isEmpty()) {
            return response()->json(['error' => 'No log entries found.', 'status' => 404]);
        }

        $newIds  = [];
        $skipped = 0;
        $now     = now();

        foreach ($originals as $original) {
            if (str_contains(strtolower($original->action_reason ?? ''), 'reversed')) {
                $skipped++;
                continue;
            }

            $originalChange = (float) $original->stock_change;
            $compensating   = $originalChange * -1;
            $newBefore      = (float) $original->stock_after;
            $newAfter       = $newBefore + $compensating;

            $newIds[] = DB::connection('tenant')
                ->table('retail_inventory_logs')
                ->insertGetId([
                    'product_id'          => $original->product_id,
                    'branch_id'           => $original->branch_id,
                    'stock_before'        => $newBefore,
                    'stock_after'         => $newAfter,
                    'stock_change'        => $compensating,
                    'action_reason'       => 'REVERSED — Compensating entry for log #' . $original->id
                                            . '. Original change: ' . ($originalChange >= 0 ? '+' : '') . $originalChange
                                            . '. Reason: ' . $original->action_reason,
                    'user_id'             => Auth::id(),
                    'user_device_details' => $request->userAgent(),
                    'log_date'            => $now->toDateString(),
                    'log_time'            => $now->toTimeString(),
                ]);
        }

        if (empty($newIds)) {
            return response()->json([
                'error'  => 'All selected entries have already been reversed.',
                'status' => 422,
            ]);
        }

        $newLogs = DB::connection('tenant')
            ->table('retail_inventory_logs as ril')
            ->join('retail_base_products as rbp', 'rbp.id', '=', 'ril.product_id')
            ->join('users as u', 'u.id', '=', 'ril.user_id')
            ->whereIn('ril.id', $newIds)
            ->select(
                'ril.*',
                'rbp.name          as product_name',
                'rbp.code          as product_code',
                'rbp.unit          as product_unit',
                'rbp.selling_price as product_sell_price',
                'u.name            as user_name'
            )
            ->get()
            ->map(fn($l) => $this->formatLog($l))
            ->values();

        $reversedCount = count($newIds);
        $message = $reversedCount . ' entr' . ($reversedCount > 1 ? 'ies' : 'y') . ' reversed successfully.';
        if ($skipped > 0) {
            $message .= ' ' . $skipped . ' already-reversed ' . ($skipped > 1 ? 'entries were' : 'entry was') . ' skipped.';
        }

        return response()->json([
            'success'        => $message,
            'status'         => 201,
            'logs'           => $newLogs,
            'reversed_count' => $reversedCount,
            'skipped_count'  => $skipped,
        ]);
    }

    // ── Delete ────────────────────────────────────────────────────────────

    public function deleteLog(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:tenant.retail_inventory_logs,id',
        ]);

        $deleted = DB::connection('tenant')
            ->table('retail_inventory_logs')
            ->where('id', $request->id)
            ->delete();

        if ($deleted) {
            return response()->json([
                'success' => 'Log entry deleted successfully.',
                'status'  => 201,
            ]);
        }

        return response()->json(['error' => 'Log entry not found.', 'status' => 404]);
    }

    // ── Bulk delete ───────────────────────────────────────────────────────

    public function bulkDeleteLogs(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:tenant.retail_inventory_logs,id',
        ]);

        $deleted = DB::connection('tenant')
            ->table('retail_inventory_logs')
            ->whereIn('id', $request->ids)
            ->delete();

        if ($deleted > 0) {
            return response()->json([
                'success' => $deleted . ' log entr' . ($deleted > 1 ? 'ies' : 'y') . ' deleted successfully.',
                'status'  => 201,
            ]);
        }

        return response()->json(['error' => 'No log entries found.', 'status' => 404]);
    }
}