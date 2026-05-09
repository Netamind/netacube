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
            'user_id'             => $user->id,
            'user_full_name'      => $user->name          ?? null,
            'user_email'          => $user->email         ?? null,
            'user_role'           => $user->role          ?? null,   // adjust to your role field
            'user_device_details' => $agent,
            'ip_address'          => $request->ip(),
            'device_type'         => $this->parseDeviceType($agent),
            'browser'             => $this->parseBrowser($agent),
            'operating_system'    => $this->parseOS($agent),
            'session_id'          => session()->getId(),
        ];
    }

    /**
     * Lightweight User-Agent parsers.
     * If you install jenssegers/agent these can be replaced with Agent::device() etc.
     * These regex parsers handle the vast majority of real-world UA strings.
     */
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
        if (str_contains($ua, 'Edg'))          return 'Edge';
        if (str_contains($ua, 'OPR')
            || str_contains($ua, 'Opera'))     return 'Opera';
        if (str_contains($ua, 'Chrome'))       return 'Chrome';
        if (str_contains($ua, 'Firefox'))      return 'Firefox';
        if (str_contains($ua, 'Safari')
            && !str_contains($ua, 'Chrome'))   return 'Safari';
        if (str_contains($ua, 'MSIE')
            || str_contains($ua, 'Trident'))   return 'IE';
        return 'Other';
    }

    private function parseOS(string $ua): string
    {
        if (str_contains($ua, 'Windows NT'))   return 'Windows';
        if (str_contains($ua, 'Mac OS X'))     return 'macOS';
        if (str_contains($ua, 'Android'))      return 'Android';
        if (str_contains($ua, 'iPhone')
            || str_contains($ua, 'iPad'))      return 'iOS';
        if (str_contains($ua, 'Linux'))        return 'Linux';
        return 'Other';
    }

    /**
     * Fetch a single log row fully joined and return it as a formatted array.
     * Used after writes so the UI receives fresh data without a page reload.
     * Now reads selling_price directly from the log row (snapshot), not from
     * the base product — so value calculations stay historically accurate.
     */
    private function fetchFormattedLog(int $id): ?array
    {
        $log = DB::connection('tenant')
            ->table('retail_inventory_logs as ril')
            ->join('retail_base_products as rbp', 'rbp.id', '=', 'ril.product_id')
            ->join('users as u', 'u.id', '=', 'ril.user_id')
            ->where('ril.id', $id)
            ->select(
                'ril.*',                            // includes selling_price, cost_price,
                                                    // operation_type, reference_*, device fields
                'rbp.name  as product_name',
                'rbp.code  as product_code',
                'rbp.unit  as product_unit',
                'u.name    as user_name'            // live name for display (snapshot also in ril)
            )
            ->first();

        return $log ? $this->formatLog($log) : null;
    }

    /**
     * Normalise a raw DB row into the array shape the front-end expects.
     * is_reversed is now driven by operation_type = 'Reversal' first,
     * with the old string-match kept as a fallback for legacy rows.
     */
    private function formatLog($log): array
    {
        $change     = (float) $log->stock_change;
        $isReversed = ($log->operation_type ?? '') === 'Reversal'
                    || str_contains(strtolower($log->action_reason ?? ''), 'reversed');

        return [
            // ── Identifiers ───────────────────────────────────────────────
            'id'                  => $log->id,
            'row'                 => 'row' . $log->id,
            'product_id'          => $log->product_id,
            'branch_id'           => $log->branch_id,

            // ── Product info (from join) ───────────────────────────────────
            'product_name'        => $log->product_name  ?? null,
            'product_code'        => $log->product_code  ?? null,
            'product_unit'        => $log->product_unit  ?? null,

            // ── Price snapshots (from the log row itself) ─────────────────
            // These are frozen at write time — do NOT pull from rbp here.
            'selling_price'       => $log->selling_price ?? 0,
            'cost_price'          => $log->cost_price    ?? 0,

            // ── Stock movement ────────────────────────────────────────────
            'stock_before'        => $log->stock_before,
            'stock_change'        => $log->stock_change,
            'stock_after'         => $log->stock_after,

            // ── Classification ────────────────────────────────────────────
            'operation_type'      => $log->operation_type  ?? 'Others',
            'action_reason'       => $log->action_reason,
            'reference_type'      => $log->reference_type  ?? null,
            'reference_id'        => $log->reference_id    ?? null,

            // ── User snapshot ─────────────────────────────────────────────
            'user_name'           => $log->user_name        ?? $log->user_full_name ?? null,
            'user_full_name'      => $log->user_full_name   ?? null,
            'user_email'          => $log->user_email       ?? null,
            'user_role'           => $log->user_role        ?? null,

            // ── Device fingerprint ────────────────────────────────────────
            'user_device_details' => $log->user_device_details ?? null,
            'ip_address'          => $log->ip_address          ?? null,
            'device_type'         => $log->device_type         ?? null,
            'browser'             => $log->browser             ?? null,
            'operating_system'    => $log->operating_system    ?? null,
            'session_id'          => $log->session_id          ?? null,

            // ── Timestamps ────────────────────────────────────────────────
            'log_date'            => $log->log_date,
            'log_time'            => $log->log_time,
            'created_at'          => $log->created_at ?? null,

            // ── Computed helpers for the UI ───────────────────────────────
            'is_reversed'         => $isReversed,
            'change_direction'    => $change > 0 ? 'in' : ($change < 0 ? 'out' : 'zero'),

            // Computed value at log time — use the snapshot price, not current
            'row_value'           => round(abs($change) * (float)($log->selling_price ?? 0), 2),
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    //  VIEW
    // ══════════════════════════════════════════════════════════════════════

    public function showAuditLogsView()
    {
        return view('operations.retail.auditlogs');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  AJAX: Dates with logs
    // ══════════════════════════════════════════════════════════════════════

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

    // ══════════════════════════════════════════════════════════════════════
    //  AJAX: Logs for a specific date
    //  Summaries now use the snapshotted selling_price from the log row,
    //  not the current price from retail_base_products.
    // ══════════════════════════════════════════════════════════════════════

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
                'ril.*',                            // selling_price lives here now
                'rbp.name  as product_name',
                'rbp.code  as product_code',
                'rbp.unit  as product_unit',
                'u.name    as user_name'
            )
            ->orderByDesc('ril.log_time')
            ->get();

        $formatted = $logs->map(fn($l) => $this->formatLog($l))->values();

        // Use log-time selling_price snapshot — historically accurate
        $summaryIn = $logs
            ->filter(fn($l) => (float) $l->stock_change > 0)
            ->sum(fn($l) => abs((float) $l->stock_change) * (float) ($l->selling_price ?? 0));

        $summaryOut = $logs
            ->filter(fn($l) => (float) $l->stock_change < 0)
            ->sum(fn($l) => abs((float) $l->stock_change) * (float) ($l->selling_price ?? 0));

        return response()->json([
            'status'      => 200,
            'logs'        => $formatted,
            'summary_in'  => round($summaryIn,  2),
            'summary_out' => round($summaryOut, 2),
            'summary_net' => round($summaryIn - $summaryOut, 2),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  PDF DOWNLOAD
    //  Prices now come from the log row snapshot, not from joined tables.
    // ══════════════════════════════════════════════════════════════════════

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
        $company = DB::connection('tenant')->table('company_info')->first();

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
            ->select('name', 'phone', 'position', 'department', 'email')
            ->first();

        // ── 4. Log rows — join only for product display fields ────────────
        //  selling_price is read from the log row (snapshot), so we do NOT
        //  need retail_branch_products for pricing any more.
        $logsQuery = DB::connection('tenant')
            ->table('retail_inventory_logs as ril')
            ->join('retail_base_products as rbp', 'rbp.id', '=', 'ril.product_id')
            ->where('ril.branch_id', $branchId)
            ->where('ril.log_date',  $request->log_date)
            ->select(
                'ril.*',
                'rbp.name as product_name',
                'rbp.code as product_code',
                'rbp.unit as product_unit'
                // NOTE: selling_price comes from ril.selling_price (snapshot)
                //       not from rbp — do not select rbp.selling_price here
            )
            ->orderByDesc('ril.log_time');

        if ($isPositive) {
            $logsQuery->where('ril.stock_change', '>', 0);
        } elseif ($isNegative) {
            $logsQuery->where('ril.stock_change', '<', 0);
        }

        $logs = $logsQuery->get();

        // ── 5. Value-based summaries using snapshotted price ─────────────
        $summaryIn = $logs
            ->filter(fn($l) => (float) $l->stock_change > 0)
            ->sum(fn($l) => abs((float) $l->stock_change) * (float) ($l->selling_price ?? 0));

        $summaryOut = $logs
            ->filter(fn($l) => (float) $l->stock_change < 0)
            ->sum(fn($l) => abs((float) $l->stock_change) * (float) ($l->selling_price ?? 0));

        $totalQty   = $logs->sum(fn($l) => abs((float) $l->stock_change));
        $totalValue = $direction === 'all'
            ? ($summaryIn + $summaryOut)
            : ($isPositive ? $summaryIn : $summaryOut);

        // ── 6. Accent palette ─────────────────────────────────────────────
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

        // ── 7. Render Blade → HTML ────────────────────────────────────────
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

        // ── 8. DomPDF ─────────────────────────────────────────────────────
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // ── 9. Safe filename ──────────────────────────────────────────────
        $filename = ($branch->name ?? 'Branch') . ' — ' . $dirLabel . ' — ' . $request->log_date . '.pdf';
        $filename = preg_replace('/[^\w\s\-\.]+/u', '', $filename);

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'no-cache, no-store, must-revalidate',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  UPDATE
    //  Prices and operation_type are editable. User snapshot and device
    //  fingerprint are NOT updated — they record who originally created it.
    // ══════════════════════════════════════════════════════════════════════

    public function updateLog(Request $request)
    {
        $request->validate([
            'id'             => 'required|integer|exists:tenant.retail_inventory_logs,id',
            'stock_before'   => 'required|numeric',
            'stock_change'   => 'required|numeric',
            'stock_after'    => 'required|numeric',
            'selling_price'  => 'nullable|numeric|min:0',
            'cost_price'     => 'nullable|numeric|min:0',
            'operation_type' => 'nullable|string|max:50',
            'action_reason'  => 'required|string|max:1000',
            'log_date'       => 'required|date',
            'log_time'       => 'required',
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
                'stock_before'   => $request->stock_before,
                'stock_change'   => $request->stock_change,
                'stock_after'    => $request->stock_after,
                'selling_price'  => $request->filled('selling_price') ? $request->selling_price : $current->selling_price,
                'cost_price'     => $request->filled('cost_price')    ? $request->cost_price    : $current->cost_price,
                'operation_type' => $request->filled('operation_type') ? $request->operation_type : $current->operation_type,
                'action_reason'  => trim($request->action_reason),
                'log_date'       => $request->log_date,
                'log_time'       => $request->log_time,
                // user snapshot + device fields intentionally NOT updated here
                // updated_at is touched automatically by timestamps()
            ]);

        $formatted = $this->fetchFormattedLog((int) $request->id);

        return response()->json([
            'success' => 'Log entry updated successfully.',
            'status'  => 201,
            'log'     => $formatted,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SINGLE REVERSE  (delegates to bulkReverse)
    // ══════════════════════════════════════════════════════════════════════

    public function reverseLog(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:tenant.retail_inventory_logs,id',
        ]);

        $bulkRequest  = new Request(['ids' => [$request->id]]);
        $bulkRequest->setUserResolver($request->getUserResolver());  // preserve auth user
        $response     = $this->bulkReverse($bulkRequest);
        $data         = $response->getData(true);

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

    // ══════════════════════════════════════════════════════════════════════
    //  BULK REVERSE
    //  The compensating entry copies the price snapshot from the original
    //  so that value calculations cancel out correctly.
    // ══════════════════════════════════════════════════════════════════════

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

        $snapshot = $this->userSnapshot($request);
        $newIds   = [];
        $skipped  = 0;
        $now      = now();

        foreach ($originals as $original) {

            // Skip entries that are already reversals or have been reversed
            if (($original->operation_type ?? '') === 'Reversal'
                || str_contains(strtolower($original->action_reason ?? ''), 'reversed')) {
                $skipped++;
                continue;
            }

            $originalChange = (float) $original->stock_change;
            $compensating   = $originalChange * -1;
            $newBefore      = (float) $original->stock_after;
            $newAfter       = $newBefore + $compensating;

            $newIds[] = DB::connection('tenant')
                ->table('retail_inventory_logs')
                ->insertGetId(array_merge($snapshot, [
                    'product_id'      => $original->product_id,
                    'branch_id'       => $original->branch_id,
                    'stock_before'    => $newBefore,
                    'stock_after'     => $newAfter,
                    'stock_change'    => $compensating,

                    // Copy price snapshots from original so the reversal
                    // cancels out the exact same value, not the current price
                    'selling_price'   => $original->selling_price ?? 0,
                    'cost_price'      => $original->cost_price    ?? 0,

                    'operation_type'  => 'Reversal',
                    'reference_type'  => 'InventoryLog',
                    'reference_id'    => $original->id,
                    'action_reason'   => 'Reversal of log #' . $original->id
                                        . '. Original change: '
                                        . ($originalChange >= 0 ? '+' : '') . $originalChange
                                        . '. Original reason: ' . $original->action_reason,
                    'log_date'        => $now->toDateString(),
                    'log_time'        => $now->toTimeString(),
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]));
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
                'rbp.name as product_name',
                'rbp.code as product_code',
                'rbp.unit as product_unit',
                'u.name   as user_name'
            )
            ->get()
            ->map(fn($l) => $this->formatLog($l))
            ->values();

        $reversedCount = count($newIds);
        $message = $reversedCount . ' entr' . ($reversedCount > 1 ? 'ies' : 'y') . ' reversed successfully.';
        if ($skipped > 0) {
            $message .= ' ' . $skipped . ' already-reversed '
                      . ($skipped > 1 ? 'entries were' : 'entry was') . ' skipped.';
        }

        return response()->json([
            'success'        => $message,
            'status'         => 201,
            'logs'           => $newLogs,
            'reversed_count' => $reversedCount,
            'skipped_count'  => $skipped,
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  DELETE
    // ══════════════════════════════════════════════════════════════════════

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

    // ══════════════════════════════════════════════════════════════════════
    //  BULK DELETE
    // ══════════════════════════════════════════════════════════════════════

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