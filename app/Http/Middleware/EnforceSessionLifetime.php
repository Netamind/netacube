<?php
// app/Http/Middleware/EnforceSessionLifetime.php
//
// Real server-side enforcement of <role>_dashboard_settings.session_lifetime_minutes
// — the hard cap on total session age, independent of activity. This is the
// missing counterpart to EnforceIdleTimeout: that one resets on every
// request (time since LAST request); this one does NOT reset on requests
// (time since login), so it needs its own stored starting point.
//
// Generic across the three enforced roles (Admin / Operations / Sales) —
// see Concerns\ResolvesDashboardRoleSettings for the per-role table/scope/
// defaults config. Any other role passes through unenforced.
//
// SESSION_STARTED_AT_KEY stays 'operations_session_started_at' (not renamed
// to something role-neutral) on purpose: a session only ever belongs to one
// role at a time, and the existing reset points already write that literal
// key —
//   - once, on the first request after login (below, if not already set)
//   - reset by OperationsDashboardSettingsController::updateSettings()
//     whenever settings are saved
//   - reset by the dashboard layout's "Stay Signed In" button via
//     ?resetSessionClock=1 (operations/dashboard.blade.php)
// Renaming it would silently break those without touching this file. If
// Admin or Sales later get their own "Stay Signed In" control or a settings
// save that should reset the clock, wire them to this same key rather than
// inventing a per-role one.
//
// Register this alongside EnforceIdleTimeout on the same tenant route
// group/middleware stack (e.g. in app/Http/Kernel.php's $middlewareGroups,
// or wherever EnforceIdleTimeout is currently attached) so it actually runs
// on every request for that role. Until it's registered for a given role,
// that role's session-timeout modal (if any) is display-only.

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\ResolvesDashboardRoleSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnforceSessionLifetime
{
    use ResolvesDashboardRoleSettings;

    protected const SESSION_STARTED_AT_KEY = 'operations_session_started_at';

    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $role   = $request->session()->get('auth_user_role');
        $config = $this->roleConfig($role);

        // Not one of Admin/Operations/Sales — nothing to enforce here.
        if (!$config) {
            return $next($request);
        }

        // Explicit reset (Stay Signed In) or first request after login.
        if ($request->boolean('resetSessionClock') || !$request->session()->has(self::SESSION_STARTED_AT_KEY)) {
            $request->session()->put(self::SESSION_STARTED_AT_KEY, time());

            return $next($request);
        }

        $settings = $this->resolveRoleSettings($config, Auth::id());

        if (!$settings) {
            return $next($request);
        }

        $sessionLifetimeSeconds = (float) $settings->session_lifetime_minutes * 60;

        if ($sessionLifetimeSeconds <= 0) {
            return $next($request);
        }

        $startedAt      = (int) $request->session()->get(self::SESSION_STARTED_AT_KEY);
        $elapsedSeconds = time() - $startedAt;

        if ($elapsedSeconds >= $sessionLifetimeSeconds) {
            $tenantName = $request->route('tenantName');

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('tenant.login.by.url', ['tenantName' => $tenantName])->with([
                'message'    => 'Your session has reached its time limit. Please log in again.',
                'alert-type' => 'error',
            ]);
        }

        return $next($request);
    }
}