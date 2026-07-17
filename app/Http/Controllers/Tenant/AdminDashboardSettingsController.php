<?php
// AdminDashboardSettingsController.php
//
// Per-user Admin dashboard settings (route names:
// tenant.admin.system.dashboard.settings.admin / .update). Previously this
// was a single tenant-wide admin_dashboard_settings row (id = 1, seeded by
// its migration) — it is now per-user, same shape as
// OperationsDashboardSettingsController: every Admin gets their own
// admin_dashboard_settings row, created lazily the first time they save.
// Until then, gatherSettingsData() hands the form the same defaults the
// migration's column defaults specify, so the page renders sensibly with
// nothing in the DB yet for that user.
//
// TWO LAYOUTS READ THIS DATA: an Admin's own sidebar (tenants/admin/dashboard.blade.php,
// via showSettingsView() below → tenants.admin.dashboard-settings-admin) AND
// an Admin viewing Retail's Settings link (operations/retail/dashboard.blade.php,
// via OperationsDashboardSettingsController::showRetailSettingsView →
// operations.retail.dashboard-settings-admin). Both need the exact same
// admin_dashboard_settings row/defaults/available-sectors — only the view
// (and therefore the surrounding layout/sidebar) differs — so that data
// assembly lives in gatherSettingsData() once, and each show method just
// picks which view to hand it to. updateSettings() doesn't need this split:
// it only ever redirects back to whichever page posted to it, so it's
// layout-agnostic already and is shared as-is by both entry points (see
// OperationsDashboardSettingsController::updateRetailSettings, which calls
// this directly).
//
// MIGRATION NOTE: admin_dashboard_settings needs a user_id column (unique
// per user, same as operations_dashboard_settings / sales_dashboard_settings)
// in place of the old singleton id = 1 row. The seeded singleton row should
// be dropped/migrated away as part of that change.

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\SectorDashboards;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminDashboardSettingsController extends Controller
{
    public function showSettingsView(Request $request)
    {
        return view('tenants.admin.dashboard-settings-admin', $this->gatherSettingsData());
    }

    /**
     * Everything the Admin settings form needs: current (or default) row,
     * whether it's actually been saved, and the sector options list. Used
     * by showSettingsView() above and by
     * OperationsDashboardSettingsController::showRetailSettingsView, which
     * renders a different view (operations.retail.dashboard-settings-admin,
     * under the Retail layout) with this same data.
     */
    public function gatherSettingsData(): array
    {
        $userId = Auth::id();

        $adminSettings = DB::connection('tenant')
            ->table('admin_dashboard_settings')
            ->where('user_id', $userId)
            ->first();

        $hasSavedSettings = (bool) $adminSettings;

        // No row yet (first visit) — hand the view the same defaults the
        // migration's column defaults specify, so checkboxes/selects render
        // correctly instead of blank/unchecked.
        if (!$adminSettings) {
            $adminSettings = self::defaultsObject();
        }

        // Only offer sectors that actually have a live dashboard route.
        $availableSectors = DB::connection('tenant')
            ->table('sectors')
            ->pluck('sector')
            ->intersect(array_keys(SectorDashboards::routes()))
            ->values();

        return compact('adminSettings', 'availableSectors', 'hasSavedSettings');
    }

    public function updateSettings(Request $request)
    {
        $userId = Auth::id();

        $validator = Validator::make($request->all(), [
            'default_landing_sector'             => 'nullable|string|in:' . implode(',', array_keys(SectorDashboards::routes())),
            'allow_sector_switching'              => 'required|boolean',

            'idle_timeout_enabled'                => 'required|boolean',
            'idle_timeout_minutes'                => 'required|integer|min:1|max:1440',
            'session_lifetime_minutes'            => 'required|integer|min:1|max:10080|gt:idle_timeout_minutes',
            'enforce_single_session'              => 'required|boolean',

            'dashboard_refresh_interval_seconds'  => 'required|integer|min:10|max:3600',
            'default_report_range'                => 'required|string|in:today,this_week,this_month,this_year',

            'low_stock_alert_enabled'             => 'required|boolean',
            'low_stock_alert_threshold'           => 'required|integer|min:0',

            'email_notifications_enabled'         => 'required|boolean',
            'notification_email'                  => 'nullable|email|max:255',
        ], [
            'session_lifetime_minutes.gt' => 'Session Lifetime must be greater than Idle Timeout.',
        ]);

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
                ->table('admin_dashboard_settings')
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                DB::connection('tenant')
                    ->table('admin_dashboard_settings')
                    ->where('user_id', $userId)
                    ->update($data);
            } else {
                $data['user_id']    = $userId;
                $data['created_at'] = $data['updated_at'];

                DB::connection('tenant')->table('admin_dashboard_settings')->insert($data);
            }
        });

        return redirect()->back()->with([
            'message'    => 'Admin dashboard settings updated.',
            'alert-type' => 'success',
        ]);
    }

    /**
     * The one place admin_dashboard_settings' defaults are defined. Used
     * here to pre-fill the form for an Admin who hasn't saved yet. Keep in
     * sync with the column defaults in the admin_dashboard_settings
     * migration.
     */
    public static function defaults(): array
    {
        return [
            'default_landing_sector'             => null,
            'allow_sector_switching'              => true,

            'idle_timeout_enabled'                => true,
            'idle_timeout_minutes'                => 60,
            'session_lifetime_minutes'            => 120,
            'enforce_single_session'              => false,

            'dashboard_refresh_interval_seconds'  => 300,
            'default_report_range'                => 'today',

            'low_stock_alert_enabled'             => true,
            'low_stock_alert_threshold'           => 10,

            'email_notifications_enabled'         => true,
            'notification_email'                  => null,
        ];
    }

    public static function defaultsObject(): object
    {
        return (object) self::defaults();
    }
}