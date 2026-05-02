<?php

namespace App\Http\Controllers\Operations\Retail;

use App\Http\Controllers\Controller;
use App\Services\EisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class EisController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    //  This controller handles everything EIS-related at the operations level:
    //
    //  VIEWS
    //    showEisDashboardView()      — main EIS overview for a branch
    //    showGlobalConfigView()      — read-only global config (tax rates, levies)
    //    showTerminalsView()         — terminal list for a branch
    //    showTerminalLogsView()      — audit log for a terminal
    //
    //  TERMINALS (CRUD)
    //    getTerminals()              — JSON list of all terminals for a branch
    //    insertTerminal()            — create a new terminal record (before activation)
    //    updateTerminal()            — edit label / position (only on non-activated terminals)
    //    deactivateTerminal()        — soft-deactivate (status → inactive)
    //    deleteTerminal()            — hard delete (only if never activated)
    //
    //  MRA EIS ACTIONS
    //    activateTerminal()          — send TAC to MRA, store credentials
    //    confirmActivation()         — send x-signature to MRA, mark as activated
    //    syncGlobalConfig()          — manually pull latest config from MRA
    //    ping()                      — check if MRA API is reachable
    //
    //  READ-ONLY DATA
    //    getGlobalConfig()           — return current eis_global_config row as JSON
    //    getTerminalLogs()           — paginated log for one terminal
    //    getBranchEisStatus()        — summary of all terminals + config for a branch
    // ─────────────────────────────────────────────────────────────────────────

    private EisService $eis;

    public function __construct(EisService $eis)
    {
        $this->eis = $eis;
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  VIEWS
    // ─────────────────────────────────────────────────────────────────────────

    public function showEisDashboardView()
    {
        return view('operations.retail.eis.dashboard');
    }

    public function showGlobalConfigView()
    {
        return view('operations.retail.eis.global-config');
    }

    public function showTerminalsView()
    {
        return view('operations.retail.eis.terminals');
    }

    public function showTerminalLogsView()
    {
        return view('operations.retail.eis.terminal-logs');
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  TERMINALS — GET LIST
    //
    //  Returns all terminals for a given branch_id, with branch EIS config
    //  merged in for display.
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

        $formatted = $terminals->map(fn($t) => $this->formatTerminal($t));

        return response()->json([
            'success'   => true,
            'status'    => 200,
            'terminals' => $formatted,
            'branch'    => $this->formatBranchEis($branch),
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  TERMINALS — INSERT
    //
    //  Creates a new terminal record in the pending state.
    //  The operator fills in the label, position, and optionally the TAC here.
    //  Activation is a separate action.
    //
    //  terminal_position must be unique per branch (used in invoice number gen).
    // ─────────────────────────────────────────────────────────────────────────

    public function insertTerminal(Request $request)
    {
        $request->validate([
            'branch_id'        => 'required|integer|exists:tenant.branches,id',
            'terminal_label'   => 'required|string|max:100',
            'terminal_position'=> 'required|integer|min:1|max:9999',
        ]);

        // Unique label per branch
        $labelExists = DB::connection('tenant')
            ->table('branch_terminals')
            ->where('branch_id',       $request->branch_id)
            ->where('terminal_label',  trim($request->terminal_label))
            ->exists();

        if ($labelExists) {
            return response()->json([
                'error'  => 'A terminal with this label already exists for this branch.',
                'status' => 422,
            ], 422);
        }

        // Unique position per branch
        $posExists = DB::connection('tenant')
            ->table('branch_terminals')
            ->where('branch_id',          $request->branch_id)
            ->where('terminal_position',   $request->terminal_position)
            ->exists();

        if ($posExists) {
            return response()->json([
                'error'  => 'Terminal position ' . $request->terminal_position . ' is already taken on this branch.',
                'status' => 422,
            ], 422);
        }

        $id = DB::connection('tenant')->table('branch_terminals')->insertGetId([
            'branch_id'                    => $request->branch_id,
            'terminal_label'               => trim($request->terminal_label),
            'terminal_position'            => (int) $request->terminal_position,
            'terminal_activation_code'     => null,
            'mra_terminal_id'              => null,
            'mra_jwt_token'                => null,
            'mra_secret_key'               => null,
            'mra_terminal_config_version'  => 0,
            'daily_transaction_count'      => 0,
            'transaction_count_date'       => null,
            'activation_status'            => 'pending',
            'activated_at'                 => null,
            'offline_max_hours'            => 0,
            'offline_max_cumulative_amount'=> 0,
            'status'                       => 'active',
            'created_at'                   => now(),
            'updated_at'                   => now(),
        ]);

        $terminal = DB::connection('tenant')->table('branch_terminals')->where('id', $id)->first();

        return response()->json([
            'success'  => 'Terminal created successfully.',
            'status'   => 201,
            'terminal' => $this->formatTerminal($terminal),
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  TERMINALS — UPDATE
    //
    //  Only the label and position can be changed, and only while the terminal
    //  has NOT been activated (pending or failed).
    //
    //  Once activated, the label is locked — MRA records it under that identity.
    //  The status (active/inactive) can always be changed regardless.
    // ─────────────────────────────────────────────────────────────────────────

    public function updateTerminal(Request $request)
    {
        $request->validate([
            'id'               => 'required|integer|exists:tenant.branch_terminals,id',
            'terminal_label'   => 'required|string|max:100',
            'terminal_position'=> 'required|integer|min:1|max:9999',
            'status'           => 'required|in:active,inactive',
        ]);

        $terminal = DB::connection('tenant')
            ->table('branch_terminals')
            ->where('id', $request->id)
            ->first();

        if (!$terminal) {
            return response()->json(['error' => 'Terminal not found.', 'status' => 404], 404);
        }

        // Label and position are locked once activated
        if ($terminal->activation_status === 'activated') {
            // Only allow status toggle on activated terminals
            DB::connection('tenant')->table('branch_terminals')->where('id', $request->id)->update([
                'status'     => $request->status,
                'updated_at' => now(),
            ]);
            $updated = DB::connection('tenant')->table('branch_terminals')->where('id', $request->id)->first();
            return response()->json([
                'success'  => 'Terminal status updated.',
                'status'   => 200,
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
            return response()->json([
                'error'  => 'A terminal with this label already exists for this branch.',
                'status' => 422,
            ], 422);
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
                'error'  => 'Terminal position ' . $request->terminal_position . ' is already taken on this branch.',
                'status' => 422,
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
            'status'   => 200,
            'terminal' => $this->formatTerminal($updated),
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  TERMINALS — DEACTIVATE (soft)
    //
    //  Sets activation_status = 'deactivated' and status = 'inactive'.
    //  The terminal can no longer be used for sales but its records are kept
    //  for audit. Cannot be reversed here — contact MRA for reactivation.
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
            'status'   => 200,
            'terminal' => $this->formatTerminal($updated),
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  TERMINALS — DELETE (hard)
    //
    //  Only allowed if the terminal was never activated (pending or failed).
    //  Activated or deactivated terminals cannot be deleted — they must remain
    //  for MRA audit trail purposes.
    // ─────────────────────────────────────────────────────────────────────────

    public function deleteTerminal(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:tenant.branch_terminals,id',
        ]);

        $terminal = DB::connection('tenant')
            ->table('branch_terminals')
            ->where('id', $request->id)
            ->first();

        if (!$terminal) {
            return response()->json(['error' => 'Terminal not found.', 'status' => 404], 404);
        }

        if (!in_array($terminal->activation_status, ['pending', 'failed'])) {
            return response()->json([
                'error'  => 'Only pending or failed terminals can be deleted. Activated terminals must be deactivated first.',
                'status' => 422,
            ], 422);
        }

        DB::connection('tenant')->table('branch_terminals')->where('id', $request->id)->delete();

        return response()->json([
            'success' => 'Terminal deleted successfully.',
            'status'  => 200,
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  MRA ACTION — ACTIVATE TERMINAL
    //
    //  Validates the TAC format, then calls EisService::activateTerminal().
    //  The TAC is stored on the terminal record by the service.
    //
    //  TAC format: xxxx-xxxx-xxxx-xxxx  (16 alphanumeric chars + 3 hyphens)
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
            ->table('branch_terminals')
            ->where('id', $request->terminal_id)
            ->first();

        if (!$terminal) {
            return response()->json(['error' => 'Terminal not found.', 'status' => 404], 404);
        }

        // Check the branch has EIS enabled and a site ID
        $branch = DB::connection('tenant')
            ->table('branches')
            ->where('id', $terminal->branch_id)
            ->first();

        if (!$branch || !$branch->eis_enabled) {
            return response()->json([
                'error'  => 'EIS is not enabled for this branch. Enable EIS on the branch first.',
                'status' => 422,
            ], 422);
        }

        if (empty($branch->mra_site_id)) {
            return response()->json([
                'error'  => 'This branch does not have an MRA Site ID. Add the Site ID on the branch settings first.',
                'status' => 422,
            ], 422);
        }

        $result = $this->eis->activateTerminal((int) $request->terminal_id, strtoupper(trim($request->tac)));

        if (!$result['success']) {
            return response()->json([
                'error'  => $result['message'],
                'status' => 422,
            ], 422);
        }

        return response()->json([
            'success'  => $result['message'],
            'status'   => 200,
            'terminal' => $result['data'] ? $this->formatTerminal($result['data']) : null,
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  MRA ACTION — CONFIRM ACTIVATION
    //
    //  Must be called right after activateTerminal() succeeds.
    //  Sends the x-signature to MRA and flips activation_status → 'activated'.
    //  If this step fails, the TAC has already been consumed at MRA's side
    //  and a new TAC must be obtained from the MRA portal.
    // ─────────────────────────────────────────────────────────────────────────

    public function confirmActivation(Request $request)
    {
        $request->validate([
            'terminal_id' => 'required|integer|exists:tenant.branch_terminals,id',
        ]);

        $result = $this->eis->confirmActivation((int) $request->terminal_id);

        if (!$result['success']) {
            return response()->json([
                'error'  => $result['message'],
                'status' => 422,
            ], 422);
        }

        return response()->json([
            'success'  => $result['message'],
            'status'   => 200,
            'terminal' => $result['data'] ? $this->formatTerminal($result['data']) : null,
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  MRA ACTION — SYNC GLOBAL CONFIG (manual)
    //
    //  Operator can click "Sync now" to pull the latest config from MRA.
    //  Requires an activated terminal to provide the JWT token.
    //  If the branch has multiple activated terminals, uses the first one found.
    //
    //  The branch_id is optional — if not provided, we find any activated
    //  terminal across any branch in this tenant.
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
            // Use any activated terminal across the tenant
            $terminalId = DB::connection('tenant')
                ->table('branch_terminals')
                ->where('activation_status', 'activated')
                ->where('status',            'active')
                ->value('id');
        }

        if (!$terminalId) {
            return response()->json([
                'error'  => 'No activated terminal found. Activate at least one terminal before syncing configuration.',
                'status' => 422,
            ], 422);
        }

        $result = $this->eis->getLatestConfig($terminalId, 'manual');

        if (!$result['success']) {
            return response()->json([
                'error'  => $result['message'],
                'status' => 422,
            ], 422);
        }

        $configRow = DB::connection('tenant')->table('eis_global_config')->where('id', 1)->first();

        return response()->json([
            'success' => $result['message'],
            'status'  => 200,
            'config'  => $this->formatGlobalConfig($configRow),
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  MRA ACTION — PING
    //
    //  Checks MRA EIS API connectivity using any activated terminal.
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
                'status'      => 422,
            ], 422);
        }

        $result = $this->eis->ping($terminalId);

        return response()->json([
            'success'     => $result['success'],
            'message'     => $result['message'],
            'reachable'   => $result['reachable'],
            'duration_ms' => $result['duration_ms'],
            'status'      => $result['success'] ? 200 : 503,
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  READ-ONLY DATA — GLOBAL CONFIG
    //
    //  Returns the current eis_global_config row as JSON.
    //  Used to populate the read-only global config view.
    // ─────────────────────────────────────────────────────────────────────────

    public function getGlobalConfig()
    {
        $configRow = DB::connection('tenant')->table('eis_global_config')->where('id', 1)->first();

        if (!$configRow) {
            return response()->json([
                'success' => false,
                'message' => 'Global config record not found. Run a migration.',
                'config'  => null,
                'status'  => 500,
            ]);
        }

        return response()->json([
            'success' => true,
            'status'  => 200,
            'config'  => $this->formatGlobalConfig($configRow),
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  READ-ONLY DATA — TERMINAL LOGS (paginated)
    //
    //  Returns the audit log for one terminal, newest first, 50 rows per page.
    //  Accepts optional endpoint filter (e.g. ?endpoint=activate).
    // ─────────────────────────────────────────────────────────────────────────

    public function getTerminalLogs(Request $request)
    {
        $request->validate([
            'terminal_id' => 'required|integer|exists:tenant.branch_terminals,id',
            'endpoint'    => 'nullable|string|max:60',
            'page'        => 'nullable|integer|min:1',
        ]);

        $query = DB::connection('tenant')
            ->table('eis_terminal_logs')
            ->where('terminal_id', $request->terminal_id)
            ->orderBy('created_at', 'desc');

        if ($request->filled('endpoint')) {
            $query->where('endpoint', $request->endpoint);
        }

        $perPage = 50;
        $page    = (int) ($request->page ?? 1);
        $total   = $query->count();
        $logs    = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

        return response()->json([
            'success'    => true,
            'status'     => 200,
            'logs'       => $logs->map(fn($l) => $this->formatLog($l)),
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => (int) ceil($total / $perPage),
            ],
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  READ-ONLY DATA — BRANCH EIS STATUS SUMMARY
    //
    //  Returns a summary for the EIS dashboard card: how many terminals,
    //  how many activated, global config sync status, etc.
    // ─────────────────────────────────────────────────────────────────────────

    public function getBranchEisStatus(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|integer|exists:tenant.branches,id',
        ]);

        $branch = DB::connection('tenant')
            ->table('branches')
            ->where('id', $request->branch_id)
            ->first();

        $terminals = DB::connection('tenant')
            ->table('branch_terminals')
            ->where('branch_id', $request->branch_id)
            ->get();

        $config = DB::connection('tenant')->table('eis_global_config')->where('id', 1)->first();

        $statusCounts = $terminals->groupBy('activation_status')->map->count();

        return response()->json([
            'success' => true,
            'status'  => 200,
            'summary' => [
                'eis_enabled'          => (bool) $branch->eis_enabled,
                'mra_site_id'          => $branch->mra_site_id,
                'is_vat_registered'    => (bool) $branch->is_vat_registered,
                'tin_number'           => $branch->tin_number,
                'mra_tax_office'       => $branch->mra_tax_office_name,
                'total_terminals'      => $terminals->count(),
                'activated_terminals'  => $statusCounts['activated'] ?? 0,
                'pending_terminals'    => $statusCounts['pending']   ?? 0,
                'failed_terminals'     => $statusCounts['failed']    ?? 0,
                'config_sync_status'   => $config->last_sync_status    ?? 'never',
                'config_last_synced'   => $config->last_synced_at      ?? null,
                'config_version'       => $config->mra_version_no      ?? 0,
            ],
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  BRANCH EIS SETTINGS — UPDATE
    //
    //  Updates the EIS-specific fields on a branch record.
    //  These are the fields needed before terminal activation can proceed:
    //    • eis_enabled
    //    • mra_site_id     (obtained from MRA EIS Portal)
    //    • tin_number      (if different from company TIN)
    //    • is_vat_registered
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
            'mra_site_id'       => $request->mra_site_id       ? trim($request->mra_site_id)  : null,
            'tin_number'        => $request->tin_number         ? trim($request->tin_number)   : null,
            'is_vat_registered' => (bool) ($request->is_vat_registered ?? false),
            'updated_at'        => now(),
        ]);

        $branch = DB::connection('tenant')->table('branches')->where('id', $request->branch_id)->first();

        return response()->json([
            'success' => 'Branch EIS settings updated.',
            'status'  => 200,
            'branch'  => $this->formatBranchEis($branch),
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  PRIVATE FORMATTERS
    //  Shape the raw DB rows into clean JSON shapes for the frontend.
    // ─────────────────────────────────────────────────────────────────────────

    private function formatTerminal($t): array
    {
        return [
            'id'                            => $t->id,
            'branch_id'                     => $t->branch_id,
            'terminal_label'                => $t->terminal_label,
            'terminal_position'             => $t->terminal_position,
            'mra_terminal_id'               => $t->mra_terminal_id,
            // Never expose JWT token or secret key to the frontend
            'has_credentials'               => !empty($t->mra_jwt_token),
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
            'eis_enabled'                 => (bool) $b->eis_enabled,
            'mra_site_id'                 => $b->mra_site_id,
            'tin_number'                  => $b->tin_number,
            'is_vat_registered'           => (bool) $b->is_vat_registered,
            'mra_tax_office_code'         => $b->mra_tax_office_code,
            'mra_tax_office_name'         => $b->mra_tax_office_name,
            'activated_tax_rate_ids'      => $b->activated_tax_rate_ids ? json_decode($b->activated_tax_rate_ids, true) : [],
            'mra_global_config_version'   => $b->mra_global_config_version,
            'mra_taxpayer_config_version' => $b->mra_taxpayer_config_version,
        ];
    }

    private function formatGlobalConfig($c): array
    {
        if (!$c) return [];
        return [
            'id'                       => $c->id,
            'mra_version_no'           => $c->mra_version_no,
            'tax_rates'                => $c->tax_rates          ? json_decode($c->tax_rates, true)         : [],
            'activated_levies'         => $c->activated_levies   ? json_decode($c->activated_levies, true)  : [],
            'synced_via_terminal_id'   => $c->synced_via_terminal_id,
            'last_synced_at'           => $c->last_synced_at,
            'last_sync_attempted_at'   => $c->last_sync_attempted_at,
            'last_sync_status'         => $c->last_sync_status,
            'last_sync_error'          => $c->last_sync_error,
            'updated_at'               => $c->updated_at,
        ];
    }

    private function formatLog($l): array
    {
        return [
            'id'               => $l->id,
            'terminal_id'      => $l->terminal_id,
            'branch_id'        => $l->branch_id,
            'endpoint'         => $l->endpoint,
            'http_method'      => $l->http_method,
            'http_status'      => $l->http_status,
            'mra_status_code'  => $l->mra_status_code,
            'mra_remark'       => $l->mra_remark,
            'outcome'          => $l->outcome,
            'outcome_message'  => $l->outcome_message,
            'duration_ms'      => $l->duration_ms,
            'trigger_source'   => $l->trigger_source,
            'created_at'       => $l->created_at,
        ];
    }
}