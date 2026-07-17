<?php
// OperationsDashboardSettingsController.php
//
// Per-user Operations dashboard settings. Two independent entry points now
// share this controller:
//
//   1. tenant.operations.dashboard.settings / .update — the Operations
//      landing hub's own Settings link (role:Operations group). Unchanged
//      from before: showSettingsView() / updateSettings() below, rendering
//      operations.dashboard-settings (extends operations/dashboard.blade.php).
//
//   2. retail.operations.dashboard.settings / .update — the Settings link
//      inside operations/retail/dashboard.blade.php (sector:Retail group:
//      Admin always, or Operations with a Retail row). Handled by
//      showRetailSettingsView() / updateRetailSettings() below, rendering
//      operations.retail.dashboard-settings (extends
//      operations/retail/dashboard.blade.php). This is the one that has to
//      deal with Admin viewers — role:Operations letting Admin through
//      doesn't apply here, sector:Retail does its own Admin-always check —
//      so these two delegate straight to AdminDashboardSettingsController
//      when the viewer is Admin, same table/view Admin's own sidebar uses.
//
// operations_dashboard_settings is per-user — every Operations user gets
// their own row, created lazily the first time they save. Until then, both
// "show" methods hand the form the same defaults the migration's column
// defaults specify, so the page renders sensibly with nothing in the DB yet.

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\SectorDashboards;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OperationsDashboardSettingsController extends Controller
{
    // ── Operations landing hub (unchanged, role:Operations only) ──

    public function showSettingsView(Request $request)
    {
        $tenantName = $request->route('tenantName');
        $userId     = Auth::id();

        [$operationsSettings, $hasSavedSettings] = $this->loadOperationsSettings($userId);
        [$allowedSectors, $availableSectors]     = $this->loadSectorLists($userId);

        return view('operations.dashboard-settings', compact(
            'operationsSettings', 'availableSectors', 'allowedSectors', 'tenantName', 'hasSavedSettings'
        ));
    }

    public function updateSettings(Request $request)
    {
        return $this->persistSettings($request);
    }

    // ── Retail sidebar (sector:Retail — Admin always, or Operations with a Retail row) ──

    public function showRetailSettingsView(Request $request)
    {
        // Admin viewing this via the Retail layout — same admin_dashboard_settings
        // data as their own sidebar's settings page, but rendered under
        // operations.retail.dashboard (not tenants.admin.dashboard) so the
        // page stays inside the Retail chrome instead of jumping the user
        // into the Admin theme. tenant.admin.dashboard and
        // tenant.operations.dashboard (the hub) stay fully independent of
        // this — this only affects what's shown under Retail.
        if (Auth::user()->role === 'Admin') {
            $adminData = app(AdminDashboardSettingsController::class)->gatherSettingsData();

            return view('operations.retail.dashboard-settings-admin', $adminData);
        }

        $tenantName = $request->route('tenantName');
        $userId     = Auth::id();

        [$operationsSettings, $hasSavedSettings] = $this->loadOperationsSettings($userId);
        [$allowedSectors, $availableSectors]     = $this->loadSectorLists($userId);

        return view('operations.retail.dashboard-settings', compact(
            'operationsSettings', 'availableSectors', 'allowedSectors', 'tenantName', 'hasSavedSettings'
        ));
    }

    public function updateRetailSettings(Request $request)
    {
        // Defensive — the Retail settings *form* only ever posts here for
        // an Operations user (Admin is routed to the Admin data/view by
        // showRetailSettingsView above, whose form posts to Admin's own
        // update route, tenant.admin.system.dashboard.settings.admin.update).
        // updateSettings() itself only ever redirects back to whichever page
        // posted to it, so it's layout-agnostic — safe to call directly here
        // without needing a Retail-specific version of it.
        if (Auth::user()->role === 'Admin') {
            return app(AdminDashboardSettingsController::class)->updateSettings($request);
        }

        return $this->persistSettings($request);
    }

    // ── Shared helpers ──

    /**
     * Fetch (or default) the current user's operations_dashboard_settings
     * row. Returns [$operationsSettings, $hasSavedSettings].
     */
    private function loadOperationsSettings(int $userId): array
    {
        $operationsSettings = DB::connection('tenant')
            ->table('operations_dashboard_settings')
            ->where('user_id', $userId)
            ->first();

        $hasSavedSettings = (bool) $operationsSettings;

        // No row yet (first visit) — hand the view the same defaults the
        // migration's column defaults specify, so checkboxes/selects render
        // correctly instead of blank/unchecked.
        if (!$operationsSettings) {
            $operationsSettings = self::defaultsObject();
        }

        return [$operationsSettings, $hasSavedSettings];
    }

    /**
     * $allowedSectors — used by the hub layout's dynamic "Sectors" menu
     * (same query as OperationsSectorSwitcherController::resolveAllowedSectors()).
     * $availableSectors — sectors with a live dashboard route, offered as
     * "Default Landing Sector" options on both settings pages.
     */
    private function loadSectorLists(int $userId): array
    {
        $allowedSectors = DB::connection('tenant')
            ->table('employee_access')
            ->join('sectors', 'sectors.id', '=', 'employee_access.sector_id')
            ->where('employee_access.employee_id', $userId)
            ->pluck('sectors.sector')
            ->unique()
            ->intersect(array_keys(SectorDashboards::routes()))
            ->sort()
            ->values();

        $availableSectors = DB::connection('tenant')
            ->table('sectors')
            ->pluck('sector')
            ->intersect(array_keys(SectorDashboards::routes()))
            ->values();

        return [$allowedSectors, $availableSectors];
    }

    /**
     * Validate + save operations_dashboard_settings for the current user.
     * Shared by both updateSettings() (hub) and updateRetailSettings()
     * (Retail) — identical validation/persistence either way, they only
     * differ in which route/view got the user here.
     */
    private function persistSettings(Request $request)
    {
        $userId = Auth::id();

        $validator = Validator::make($request->all(), [
            'default_landing_sector'             => 'nullable|string|in:' . implode(',', array_keys(SectorDashboards::routes())),
            'allow_sector_switching'              => 'required|boolean',

            'idle_timeout_enabled'                => 'required|boolean',
            // numeric (not integer) + step 0.1 in the view lets these be
            // fractional minutes (e.g. 0.5 = 30s) for fast testing.
            'idle_timeout_minutes'                => 'required|numeric|min:0.1|max:1440',
            'session_lifetime_minutes'            => 'required|numeric|min:0.1|max:10080',
        ]);

        // Session Lifetime is a hard cap on top of Idle Timeout — if it's
        // ever <= Idle Timeout, idle timeout always fires first and the
        // session-lifetime setting becomes meaningless. Cross-field checks
        // like this can't be expressed as a single Validator rule string.
        $validator->after(function ($validator) use ($request) {
            $idleMinutes    = (float) $request->input('idle_timeout_minutes');
            $sessionMinutes = (float) $request->input('session_lifetime_minutes');

            if ($sessionMinutes <= $idleMinutes) {
                $validator->errors()->add(
                    'session_lifetime_minutes',
                    'Session Lifetime must be greater than Idle Timeout.'
                );
            }
        });

        if ($validator->fails()) {
            // Deliberately no withInput() here — on reload the fields should
            // show the last *saved* value, not the rejected one. The error
            // message (surfaced via toastr and inline on the field) is
            // enough to tell the user what went wrong and to re-enter it.
            return redirect()->back()->with([
                'message'    => implode(', ', $validator->errors()->all()),
                'alert-type' => 'error',
            ]);
        }

        $data = $validator->validated();
        $data['updated_at'] = now();

        DB::connection('tenant')->transaction(function () use ($userId, $data) {
            $existing = DB::connection('tenant')
                ->table('operations_dashboard_settings')
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                DB::connection('tenant')
                    ->table('operations_dashboard_settings')
                    ->where('user_id', $userId)
                    ->update($data);
            } else {
                $data['user_id']    = $userId;
                $data['created_at'] = $data['updated_at'];

                DB::connection('tenant')->table('operations_dashboard_settings')->insert($data);
            }
        });

        // Whatever the session-lifetime clock was counting from (see
        // operations/dashboard.blade.php's $__sessionStartedAt), restart it
        // here. Without this, lowering session_lifetime_minutes below the
        // time already elapsed since the stored start would make the
        // countdown come back already negative — the modal would never get
        // a chance to count down, it'd just bounce straight to reload.
        session()->put('operations_session_started_at', time());

        return redirect()->back()->with([
            'message'    => 'Dashboard settings updated.',
            'alert-type' => 'success',
        ]);
    }

    /**
     * The one place operations_dashboard_settings' defaults are defined.
     * Used here to pre-fill the form for a user who hasn't saved yet, and
     * by EnforceIdleTimeout middleware so that same user is actually
     * protected by these values, not just shown a checkbox that says so.
     * Keep in sync with the column defaults in the
     * operations_dashboard_settings migration.
     */
    public static function defaults(): array
    {
        return [
            'idle_timeout_enabled'               => true,
            'idle_timeout_minutes'               => 30,
            'session_lifetime_minutes'           => 90,
            'enforce_single_session'             => true,
            'default_landing_sector'             => null,
            'allow_sector_switching'             => false,
            'restrict_to_assigned_branch'        => false,
            'dashboard_refresh_interval_seconds' => 300,
            'default_report_range'               => 'today',
            'low_stock_alert_enabled'            => true,
            'low_stock_alert_threshold'          => 10,
        ];
    }

    public static function defaultsObject(): object
    {
        return (object) self::defaults();
    }
}