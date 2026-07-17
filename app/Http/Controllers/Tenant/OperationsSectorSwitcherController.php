<?php
// OperationsSectorSwitcherController.php
//
// Landing point for every Operations-role login (route: tenant.operations.hub.dashboard).
// Decides, in order:
//   1. No allowed sectors at all -> account is misconfigured; bounce back to
//      login with an error rather than show an empty dashboard.
//   2. Does this user's own operations_dashboard_settings.default_landing_sector
//      row name a sector they actually have access to? -> redirect straight there.
//   3. Otherwise -> render the Operations dashboard, where the user picks
//      a sector from the ones they're allowed into (employee_access).
//
// operations_dashboard_settings is per-user (one row per user_id, created on
// that user's first settings submit — see OperationsDashboardSettingsController).
// A user with no row yet simply has no default_landing_sector override, same
// as if the field were explicitly blank.
//
// This is unconditional: unlike the old multi_sector_landing setting, having
// more than one (or exactly one) allowed sector no longer skips the
// dashboard on its own — only an explicit, accessible default_landing_sector
// does.
//
// ASSUMPTION: `employee_access.employee_id` maps to the authenticated user's
// id (i.e. an Operations user's own row in `users`, same as `Auth::id()`
// elsewhere in this app). If employees are a separate table from users,
// swap the lookup below for whatever links a logged-in user to their
// employee_id first.

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\SectorDashboards;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OperationsSectorSwitcherController extends Controller
{
    public function show(Request $request)
    {
        $tenantName = $request->route('tenantName');
        $userId     = Auth::id();

        $allowedSectors = $this->resolveAllowedSectors($userId);

        // No sector access at all — nothing to switch to or land on.
        if ($allowedSectors->isEmpty()) {
            session()->flush();

            return redirect()->route('tenant.login.by.url', ['tenantName' => $tenantName])->with([
                'message'    => 'Your account has no sector access configured. Contact your administrator.',
                'alert-type' => 'error',
            ]);
        }

        // The default_landing_sector auto-redirect is a LOGIN-time convenience
        // only (see TenantCommonController::redirectOperations, which hits this
        // same route right after auth). Once a user is already in the app and
        // deliberately clicks the "Operations" hub link in the sidebar, they
        // want the picker — not to be bounced straight back to a sector.
        // The sidebar link appends ?hub=1 to signal "explicit navigation";
        // its presence skips the auto-redirect below.
        $explicitHubVisit = $request->boolean('hub');

        if (!$explicitHubVisit) {
            $settings = DB::connection('tenant')
                ->table('operations_dashboard_settings')
                ->where('user_id', $userId)
                ->first();

            // Explicit default, if the user actually has access to it. This is
            // the only thing that skips the dashboard, and only on a fresh
            // (non-explicit) hit of this route.
            if (
                $settings &&
                !empty($settings->default_landing_sector) &&
                $allowedSectors->contains($settings->default_landing_sector)
            ) {
                return $this->redirectToSector($settings->default_landing_sector, $tenantName);
            }
        }

        return view('operations.dashboard', [
            'allowedSectors' => $allowedSectors,
            'tenantName'     => $tenantName,
        ]);
    }

    protected function resolveAllowedSectors(?int $userId)
    {
        if (!$userId) {
            return collect();
        }

        return DB::connection('tenant')
            ->table('employee_access')
            ->join('sectors', 'sectors.id', '=', 'employee_access.sector_id')
            ->where('employee_access.employee_id', $userId)
            ->pluck('sectors.sector')
            ->unique()
            ->intersect(array_keys(SectorDashboards::routes()))
            ->sort()
            ->values();
    }

    protected function redirectToSector(string $sector, ?string $tenantName)
    {
        $routeName = SectorDashboards::routes()[$sector] ?? null;

        if (!$routeName) {
            // Route not actually live — fall back to the dashboard rather than 500.
            return redirect()->route('tenant.operations.hub.dashboard', ['tenantName' => $tenantName]);
        }

        return redirect()->route($routeName, ['tenantName' => $tenantName]);
    }
}