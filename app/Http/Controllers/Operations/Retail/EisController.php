<?php

namespace App\Http\Controllers\Operations\Retail;

use App\Http\Controllers\Controller;
use App\Services\EisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EisController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    //  VIEWS          → resources/views/tenants/mra/
    //  JSON routes    → /operations/retail/eis/json/*
    //  MRA actions    → POST /operations/retail/eis/*
    // ─────────────────────────────────────────────────────────────────────────

    private EisService $eis;

    public function __construct(EisService $eis)
    {
        $this->eis = $eis;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  SHARED HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    /** Branches list injected into every view that has a branch selector. */
    private function branches(): \Illuminate\Support\Collection
    {
        return DB::connection('tenant')
            ->table('branches')
            ->orderBy('name')
            ->get(['id', 'name']);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  VIEWS  (resources/views/tenants/mra/)
    // ─────────────────────────────────────────────────────────────────────────

    public function showEisDashboardView()
    {
        return view('tenants.mra.dashboard', [
            'branches' => $this->branches(),
        ]);
    }

    public function showGlobalConfigView()
    {
        return view('tenants.mra.global-config');
    }

    public function showTerminalsView()
    {
        return view('tenants.mra.terminals', [
            'branches' => $this->branches(),
        ]);
    }

    public function showTerminalLogsView()
    {
        return view('tenants.mra.terminal-logs', [
            'branches' => $this->branches(),
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  TERMINALS — GET LIST
    // ─────────────────────────────────────────────────────────────────────────

    public function getTerminals(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:tenant.branches,id',
        ]);

        $terminals = DB::connection('tenant')
            ->table('branch_terminals')
            ->where('branch_id', $request->branch_id)
            ->orderBy('terminal_position')
            ->orderBy('id')
            ->get();

        $branch = DB::connection('tenant')
            ->table('branches')
            ->where('id', $request->branch_id)
            ->first();

        return response()->json([
            'success'   => true,
            'terminals' => $terminals->map(fn($t) => $this->formatTerminal($t)),
            'branch'    => $this->formatBranchEis($branch),
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  TERMINALS — INSERT
    // ─────────────────────────────────────────────────────────────────────────

    public function insertTerminal(Request $request)
    {
        $request->validate([
            'branch_id'         => 'required|integer|exists:tenant.branches,id',
            'terminal_label'    => 'required|string|max:100',
            'terminal_position' => 'required|integer|min:1|max:9999',
        ]);

        $labelExists = DB::connection('tenant')
            ->table('branch_terminals')
            ->where('branch_id',      $request->branch_id)
            ->where('terminal_label', trim($request->terminal_label))
            ->exists();

        if ($labelExists) {
            return response()->json(['error' => 'A terminal with this label already exists for this branch.'], 422);
        }

        $posExists = DB::connection('tenant')
            ->table('branch_terminals')
            ->where('branch_id',         $request->branch_id)
            ->where('terminal_position', $request->terminal_position)
            ->exists();

        if ($posExists) {
            return response()->json([
                'error' => 'Terminal position ' . $request->terminal_position . ' is already taken on this branch.',
            ], 422);
        }

        $id = DB::connection('tenant')->table('branch_terminals')->insertGetId([
            'branch_id'                     => $request->branch_id,
            'terminal_label'                => trim($request->terminal_label),
            'terminal_position'             => (int) $request->terminal_position,
            'terminal_activation_code'      => null,
            'mra_terminal_id'               => null,
            'mra_jwt_token'                 => null,
            'mra_secret_key'                => null,
            'mra_terminal_config_version'   => 0,
            'daily_transaction_count'       => 0,
            'transaction_count_date'        => null,
            'activation_status'             => 'pending',
            'activated_at'                  => null,
            'offline_max_hours'             => 0,
            'offline_max_cumulative_amount' => 0,
            'status'                        => 'active',
            'created_at'                    => now(),
            'updated_at'                    => now(),
        ]);

        $terminal = DB::connection('tenant')->table('branch_terminals')->where('id', $id)->first();

        return response()->json([
            'success'  => 'Terminal created successfully.',
            'terminal' => $this->formatTerminal($terminal),
        ], 201);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  TERMINALS — UPDATE
    //
    //  Label + position are locked once activated.
    //  Status (active / inactive) can always be changed.
    // ─────────────────────────────────────────────────────────────────────────

    public function updateTerminal(Request $request)
    {
        $request->validate([
            'id'                => 'required|integer|exists:tenant.branch_terminals,id',
            'terminal_label'    => 'required|string|max:100',
            'terminal_position' => 'required|integer|min:1|max:9999',
            'status'            => 'required|in:active,inactive',
        ]);

        $terminal = DB::connection('tenant')
            ->table('branch_terminals')->where('id', $request->id)->first();

        if (!$terminal) {
            return response()->json(['error' => 'Terminal not found.'], 404);
        }

        if ($terminal->activation_status === 'activated') {
            // Only status toggle allowed on activated terminals
            DB::connection('tenant')->table('branch_terminals')->where('id', $request->id)->update([
                'status'     => $request->status,
                'updated_at' => now(),
            ]);
            $updated = DB::connection('tenant')->table('branch_terminals')->where('id', $request->id)->first();
            return response()->json([
                'success'  => 'Terminal status updated.',
                'terminal' => $this->formatTerminal($updated),
            ]);
        }

        // Check label uniqueness (excluding self)
        $labelExists = DB::connection('tenant')
            ->table('branch_terminals')
            ->where('branch_id',      $terminal->branch_id)
            ->where('terminal_label', trim($request->terminal_label))
            ->where('id', '!=', $request->id)
            ->exists();

        if ($labelExists) {
            return response()->json(['error' => 'A terminal with this label already exists for this branch.'], 422);
        }

        // Check position uniqueness (excluding self)
        $posExists = DB::connection('tenant')
            ->table('branch_terminals')
            ->where('branch_id',         $terminal->branch_id)
            ->where('terminal_position', $request->terminal_position)
            ->where('id', '!=', $request->id)
            ->exists();

        if ($posExists) {
            return response()->json([
                'error' => 'Terminal position ' . $request->terminal_position . ' is already taken on this branch.',
            ], 422);
        }

        DB::connection('tenant')->table('branch_terminals')->where('id', $request->id)->update([
            'terminal_label'    => trim($request->terminal_label),
            'terminal_position' => (int) $request->terminal_position,
            'status'            => $request->status,
            'updated_at'        => now(),
        ]);

        $updated = DB::connection('tenant')->table('branch_terminals')->where('id', $request->id)->first();

        return response()->json([
            'success'  => 'Terminal updated successfully.',
            'terminal' => $this->formatTerminal($updated),
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  TERMINALS — DEACTIVATE (soft)
    // ─────────────────────────────────────────────────────────────────────────

    public function deactivateTerminal(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:tenant.branch_terminals,id',
        ]);

        DB::connection('tenant')->table('branch_terminals')->where('id', $request->id)->update([
            'activation_status' => 'deactivated',
            'status'            => 'inactive',
            'updated_at'        => now(),
        ]);

        $updated = DB::connection('tenant')->table('branch_terminals')->where('id', $request->id)->first();

        return response()->json([
            'success'  => 'Terminal has been deactivated.',
            'terminal' => $this->formatTerminal($updated),
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  TERMINALS — DELETE (hard, only pending / failed)
    // ─────────────────────────────────────────────────────────────────────────

    public function deleteTerminal(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:tenant.branch_terminals,id',
        ]);

        $terminal = DB::connection('tenant')
            ->table('branch_terminals')->where('id', $request->id)->first();

        if (!$terminal) {
            return response()->json(['error' => 'Terminal not found.'], 404);
        }

        if (!in_array($terminal->activation_status, ['pending', 'failed'])) {
            return response()->json([
                'error' => 'Only pending or failed terminals can be deleted.',
            ], 422);
        }

        DB::connection('tenant')->table('branch_terminals')->where('id', $request->id)->delete();

        return response()->json(['success' => 'Terminal deleted successfully.']);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  MRA — ACTIVATE TERMINAL
    // ─────────────────────────────────────────────────────────────────────────

    public function activateTerminal(Request $request)
    {
        $request->validate([
            'terminal_id' => 'required|integer|exists:tenant.branch_terminals,id',
            'tac'         => ['required', 'string', 'regex:/^[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}$/'],
        ], [
            'tac.regex' => 'The activation code must follow the format: XXXX-XXXX-XXXX-XXXX.',
        ]);

        $terminal = DB::connection('tenant')
            ->table('branch_terminals')->where('id', $request->terminal_id)->first();

        if (!$terminal) {
            return response()->json(['error' => 'Terminal not found.'], 404);
        }

        $branch = DB::connection('tenant')
            ->table('branches')->where('id', $terminal->branch_id)->first();

        if (!$branch || !$branch->eis_enabled) {
            return response()->json(['error' => 'EIS is not enabled for this branch.'], 422);
        }

        if (empty($branch->mra_site_id)) {
            return response()->json(['error' => 'No MRA Site ID set on this branch.'], 422);
        }

        $result = $this->eis->activateTerminal((int) $request->terminal_id, strtoupper(trim($request->tac)));

        if (!$result['success']) {
            return response()->json(['error' => $result['message']], 422);
        }

        return response()->json([
            'success'  => $result['message'],
            'terminal' => $result['data'] ? $this->formatTerminal($result['data']) : null,
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  MRA — CONFIRM ACTIVATION
    // ─────────────────────────────────────────────────────────────────────────

    public function confirmActivation(Request $request)
    {
        $request->validate([
            'terminal_id' => 'required|integer|exists:tenant.branch_terminals,id',
        ]);

        $result = $this->eis->confirmActivation((int) $request->terminal_id);

        if (!$result['success']) {
            return response()->json(['error' => $result['message']], 422);
        }

        return response()->json([
            'success'  => $result['message'],
            'terminal' => $result['data'] ? $this->formatTerminal($result['data']) : null,
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  MRA — SYNC GLOBAL CONFIG (manual trigger)
    // ─────────────────────────────────────────────────────────────────────────

    public function syncGlobalConfig(Request $request)
    {
        $request->validate([
            'branch_id'   => 'nullable|integer|exists:tenant.branches,id',
            'terminal_id' => 'nullable|integer|exists:tenant.branch_terminals,id',
        ]);

        // Resolve which terminal to use for the sync call
        $terminalId = null;

        if ($request->filled('terminal_id')) {
            $terminalId = (int) $request->terminal_id;
        } elseif ($request->filled('branch_id')) {
            $terminalId = DB::connection('tenant')
                ->table('branch_terminals')
                ->where('branch_id',        $request->branch_id)
                ->where('activation_status', 'activated')
                ->where('status',            'active')
                ->value('id');
        } else {
            $terminalId = DB::connection('tenant')
                ->table('branch_terminals')
                ->where('activation_status', 'activated')
                ->where('status',            'active')
                ->value('id');
        }

        if (!$terminalId) {
            return response()->json([
                'error' => 'No activated terminal found. Activate at least one terminal before syncing.',
            ], 422);
        }

        $result = $this->eis->getLatestConfig($terminalId, 'manual');

        if (!$result['success']) {
            return response()->json(['error' => $result['message']], 422);
        }

        $configRow = DB::connection('tenant')->table('eis_global_config')->where('id', 1)->first();

        return response()->json([
            'success' => $result['message'],
            'config'  => $this->formatGlobalConfig($configRow),
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  MRA — PING
    // ─────────────────────────────────────────────────────────────────────────

    public function ping(Request $request)
    {
        $request->validate([
            'terminal_id' => 'nullable|integer|exists:tenant.branch_terminals,id',
        ]);

        $terminalId = $request->filled('terminal_id')
            ? (int) $request->terminal_id
            : DB::connection('tenant')
                ->table('branch_terminals')
                ->where('activation_status', 'activated')
                ->where('status',            'active')
                ->value('id');

        if (!$terminalId) {
            return response()->json([
                'success'     => false,
                'message'     => 'No activated terminal available for ping.',
                'reachable'   => false,
                'duration_ms' => null,
            ], 422);
        }

        $result = $this->eis->ping($terminalId);

        return response()->json([
            'success'     => $result['success'],
            'message'     => $result['message'],
            'reachable'   => $result['reachable'],
            'duration_ms' => $result['duration_ms'],
        ], $result['success'] ? 200 : 503);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  READ-ONLY — GLOBAL CONFIG JSON
    // ─────────────────────────────────────────────────────────────────────────

    public function getGlobalConfig()
    {
        $configRow = DB::connection('tenant')->table('eis_global_config')->where('id', 1)->first();

        if (!$configRow) {
            return response()->json([
                'success' => false,
                'message' => 'Global config record not found. Run migrations.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'config'  => $this->formatGlobalConfig($configRow),
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  READ-ONLY — TERMINAL LOGS (paginated)
    // ─────────────────────────────────────────────────────────────────────────

    public function getTerminalLogs(Request $request)
    {
        $request->validate([
            'terminal_id' => 'required|integer|exists:tenant.branch_terminals,id',
            'endpoint'    => 'nullable|string|max:60',
            'outcome'     => 'nullable|in:success,failed,error',
            'page'        => 'nullable|integer|min:1',
        ]);

        $query = DB::connection('tenant')
            ->table('eis_terminal_logs')
            ->where('terminal_id', $request->terminal_id)
            ->orderBy('created_at', 'desc');

        if ($request->filled('endpoint')) {
            $query->where('endpoint', $request->endpoint);
        }
        if ($request->filled('outcome')) {
            $query->where('outcome', $request->outcome);
        }

        $perPage = 50;
        $page    = (int) ($request->page ?? 1);
        $total   = $query->count();
        $logs    = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

        return response()->json([
            'success'    => true,
            'logs'       => $logs->map(fn($l) => $this->formatLog($l)),
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => max(1, (int) ceil($total / $perPage)),
            ],
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  READ-ONLY — BRANCH EIS STATUS SUMMARY
    // ─────────────────────────────────────────────────────────────────────────

    public function getBranchEisStatus(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:tenant.branches,id',
        ]);

        $branch = DB::connection('tenant')
            ->table('branches')->where('id', $request->branch_id)->first();

        $terminals = DB::connection('tenant')
            ->table('branch_terminals')->where('branch_id', $request->branch_id)->get();

        $config = DB::connection('tenant')->table('eis_global_config')->where('id', 1)->first();

        $statusCounts = $terminals->groupBy('activation_status')->map->count();

        return response()->json([
            'success' => true,
            'summary' => [
                'eis_enabled'         => (bool) $branch->eis_enabled,
                'mra_site_id'         => $branch->mra_site_id,
                'is_vat_registered'   => (bool) $branch->is_vat_registered,
                'tin_number'          => $branch->tin_number,
                'mra_tax_office'      => $branch->mra_tax_office_name ?? null,
                'total_terminals'     => $terminals->count(),
                'activated_terminals' => $statusCounts['activated'] ?? 0,
                'pending_terminals'   => $statusCounts['pending']   ?? 0,
                'failed_terminals'    => $statusCounts['failed']    ?? 0,
                'config_sync_status'  => $config->last_sync_status  ?? 'never',
                'config_last_synced'  => $config->last_synced_at    ?? null,
                'config_version'      => $config->mra_version_no    ?? 0,
            ],
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  BRANCH EIS SETTINGS — UPDATE
    // ─────────────────────────────────────────────────────────────────────────

    public function updateBranchEisSettings(Request $request)
    {
        $request->validate([
            'branch_id'         => 'required|integer|exists:tenant.branches,id',
            'eis_enabled'       => 'required|boolean',
            'mra_site_id'       => 'nullable|string|max:100',
            'tin_number'        => 'nullable|string|max:50',
            'is_vat_registered' => 'nullable|boolean',
        ]);

        DB::connection('tenant')->table('branches')->where('id', $request->branch_id)->update([
            'eis_enabled'       => (bool) $request->eis_enabled,
            'mra_site_id'       => $request->mra_site_id ? trim($request->mra_site_id) : null,
            'tin_number'        => $request->tin_number  ? trim($request->tin_number)  : null,
            'is_vat_registered' => (bool) ($request->is_vat_registered ?? false),
            'updated_at'        => now(),
        ]);

        $branch = DB::connection('tenant')->table('branches')->where('id', $request->branch_id)->first();

        return response()->json([
            'success' => 'Branch EIS settings updated.',
            'branch'  => $this->formatBranchEis($branch),
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  PRIVATE FORMATTERS
    // ─────────────────────────────────────────────────────────────────────────

    private function formatTerminal($t): array
    {
        return [
            'id'                            => $t->id,
            'branch_id'                     => $t->branch_id,
            'terminal_label'                => $t->terminal_label,
            'terminal_position'             => $t->terminal_position,
            'mra_terminal_id'               => $t->mra_terminal_id,
            'has_credentials'               => !empty($t->mra_jwt_token),   // never expose token itself
            'mra_terminal_config_version'   => $t->mra_terminal_config_version,
            'activation_status'             => $t->activation_status,
            'activated_at'                  => $t->activated_at,
            'offline_max_hours'             => $t->offline_max_hours,
            'offline_max_cumulative_amount' => $t->offline_max_cumulative_amount,
            'daily_transaction_count'       => $t->daily_transaction_count,
            'transaction_count_date'        => $t->transaction_count_date,
            'status'                        => $t->status,
            'created_at'                    => $t->created_at,
            'updated_at'                    => $t->updated_at,
        ];
    }

    private function formatBranchEis($b): array
    {
        if (!$b) return [];
        return [
            'id'                          => $b->id,
            'name'                        => $b->name,
            'eis_enabled'                 => (bool) ($b->eis_enabled ?? false),
            'mra_site_id'                 => $b->mra_site_id         ?? null,
            'tin_number'                  => $b->tin_number           ?? null,
            'is_vat_registered'           => (bool) ($b->is_vat_registered ?? false),
            'mra_tax_office_code'         => $b->mra_tax_office_code  ?? null,
            'mra_tax_office_name'         => $b->mra_tax_office_name  ?? null,
            'activated_tax_rate_ids'      => isset($b->activated_tax_rate_ids)
                                                ? json_decode($b->activated_tax_rate_ids, true)
                                                : [],
            'mra_global_config_version'   => $b->mra_global_config_version   ?? 0,
            'mra_taxpayer_config_version' => $b->mra_taxpayer_config_version  ?? 0,
        ];
    }

    private function formatGlobalConfig($c): array
    {
        if (!$c) return [];
        return [
            'id'                     => $c->id,
            'mra_version_no'         => $c->mra_version_no,
            'tax_rates'              => $c->tax_rates        ? json_decode($c->tax_rates, true)        : [],
            'activated_levies'       => $c->activated_levies ? json_decode($c->activated_levies, true) : [],
            'synced_via_terminal_id' => $c->synced_via_terminal_id,
            'last_synced_at'         => $c->last_synced_at,
            'last_sync_attempted_at' => $c->last_sync_attempted_at,
            'last_sync_status'       => $c->last_sync_status,
            'last_sync_error'        => $c->last_sync_error,
            'updated_at'             => $c->updated_at,
        ];
    }

    private function formatLog($l): array
    {
        return [
            'id'              => $l->id,
            'terminal_id'     => $l->terminal_id,
            'branch_id'       => $l->branch_id,
            'endpoint'        => $l->endpoint,
            'http_method'     => $l->http_method,
            'http_status'     => $l->http_status,
            'mra_status_code' => $l->mra_status_code,
            'mra_remark'      => $l->mra_remark,
            'outcome'         => $l->outcome,
            'outcome_message' => $l->outcome_message,
            'duration_ms'     => $l->duration_ms,
            'trigger_source'  => $l->trigger_source,
            'created_at'      => $l->created_at,
        ];
    }
}