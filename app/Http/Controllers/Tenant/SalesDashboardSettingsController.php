<?php
// app/Http/Controllers/Tenant/SalesDashboardSettingsController.php
//
// Sales counterpart to OperationsDashboardSettingsController — same shape
// (defaultsObject / showSettingsView / updateSettings), same per-user table
// pattern, so Concerns\ResolvesDashboardRoleSettings's 'Sales' defaults
// callable and EnforceIdleTimeout / EnforceSessionLifetime work against it
// exactly the way they already work against Operations.
//
// default_landing_sector is deliberately NOT accepted in updateSettings():
// the settings form renders that field disabled (Sales users are tied to a
// branch, not a sector — see the migration's comment), so a normal form
// submission never actually posts it. Once branch-based landing exists,
// wire it up in both places together.

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SalesDashboardSettingsController extends Controller
{
    /**
     * Used by:
     *  - showSettingsView() below, for a user with no saved row yet
     *  - Concerns\ResolvesDashboardRoleSettings, so EnforceIdleTimeout /
     *    EnforceSessionLifetime enforce these same values against a
     *    brand-new Sales user rather than skipping enforcement entirely.
     */
    public static function defaultsObject(): object
    {
        return (object) [
            'default_landing_sector'   => null,
            'idle_timeout_enabled'     => true,
            'idle_timeout_minutes'     => 30,
            'session_lifetime_minutes' => 90,
            'enforce_single_session'   => true,
        ];
    }

    public function showSettingsView(Request $request, string $tenantName)
    {
        $settings = DB::connection('tenant')
            ->table('sales_dashboard_settings')
            ->where('user_id', Auth::id())
            ->first();

        // Sales users are tied to a branch, not a sector (see the migration's
        // comment on default_landing_sector) — same lookup RetailSalesController
        // already uses (Auth::user()->branch -> branches.id) so the settings
        // page can show which branch/sector actually govern this account
        // instead of leaving the disabled "Default Landing Sector" field
        // looking unexplained.
        $myBranch = DB::connection('tenant')
            ->table('branches')
            ->find(Auth::user()->branch);

        return view('sales.retail.dashboard-settings', [
            'tenantName'       => $tenantName,
            'hasSavedSettings' => (bool) $settings,
            'salesSettings'    => $settings ?: static::defaultsObject(),
            'myBranch'         => $myBranch,
        ]);
    }

    public function updateSettings(Request $request, string $tenantName)
    {
        $validator = Validator::make($request->all(), [
            'idle_timeout_enabled'     => ['nullable', 'boolean'],
            'idle_timeout_minutes'     => ['required', 'numeric', 'min:0.1', 'max:1440'],
            'session_lifetime_minutes' => ['required', 'numeric', 'min:0.1', 'max:10080', 'gt:idle_timeout_minutes'],
        ], [
            'session_lifetime_minutes.gt' => 'Session Lifetime must be greater than Idle Timeout.',
        ]);

        if ($validator->fails()) {
            // Deliberately no withInput() here — on reload the fields should
            // show the last *saved* value, not the rejected one. withErrors()
            // alone is enough for the inline @error feedback to still work.
            return back()->withErrors($validator);
        }

        $userId = Auth::id();

        $payload = [
            'idle_timeout_enabled'     => $request->boolean('idle_timeout_enabled'),
            'idle_timeout_minutes'     => $request->input('idle_timeout_minutes'),
            'session_lifetime_minutes' => $request->input('session_lifetime_minutes'),
            'updated_at'               => now(),
        ];

        $existing = DB::connection('tenant')
            ->table('sales_dashboard_settings')
            ->where('user_id', $userId)
            ->exists();

        if ($existing) {
            DB::connection('tenant')
                ->table('sales_dashboard_settings')
                ->where('user_id', $userId)
                ->update($payload);
        } else {
            DB::connection('tenant')->table('sales_dashboard_settings')->insert(array_merge($payload, [
                'user_id'                => $userId,
                'default_landing_sector' => null,
                'enforce_single_session' => true,
                'created_at'             => now(),
            ]));
        }

     
        $request->session()->put('operations_session_started_at', time());

        return redirect()
            ->route('retail.sales.dashboard.settings', ['tenantName' => $tenantName])
            ->with(['message' => 'Settings saved.', 'alert-type' => 'success']);
    }
}