<?php
// EnforceIdleTimeout.php
//
// Generic across the three enforced roles (Admin / Operations / Sales) —
// see Concerns\ResolvesDashboardRoleSettings for the per-role table/scope/
// defaults config. Any other role (or no role yet) passes through
// unenforced, same as before.

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\ResolvesDashboardRoleSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnforceIdleTimeout
{
    use ResolvesDashboardRoleSettings;

    public function handle(Request $request, Closure $next): Response
    {
        $role = $request->session()->get('auth_user_role');

        // No session identity yet — let RoleAllowed/SectorAllowed handle that.
        if (!$role) {
            return $next($request);
        }

        $config = $this->roleConfig($role);

        // Not one of Admin/Operations/Sales — nothing to enforce here.
        if (!$config) {
            return $next($request);
        }

        $userId = $request->session()->get('auth_user_id');

        // 'tenant' scope (Admin): userId is unused by resolveRoleSettings.
        // 'user' scope (Operations, Sales): a user with no saved row yet
        // still needs to be genuinely protected, so resolveRoleSettings
        // falls back to that role's defaults — not "skip enforcement",
        // which would silently leave brand-new users with no idle timeout
        // / single-session lock at all.
        $settings = $this->resolveRoleSettings($config, $userId);

        if (!$settings) {
            return $next($request);
        }

        // Apply the configured absolute session lifetime for this role.
        // Read before StartSession queues the response cookie so it takes
        // effect for this request's cookie expiry.
        if ($settings->session_lifetime_minutes) {
            config(['session.lifetime' => $settings->session_lifetime_minutes]);
        }

        $tenantCode = $request->route('tenantName');

        // ── Single-session enforcement ──────────────────────────────────────
        if ($settings->enforce_single_session) {
            $sessionToken = $request->session()->get('auth_session_token');

            $storedToken = DB::connection('tenant')
                ->table('user_session_tokens')
                ->where('user_id', $userId)
                ->value('session_token');

            if ($sessionToken && $storedToken && $sessionToken !== $storedToken) {
                $request->session()->flush();
                $request->session()->invalidate();

                return redirect()->route('tenant.login.by.url', ['tenantName' => $tenantCode])->with([
                    'message'    => 'This account was signed in elsewhere. You have been logged out.',
                    'alert-type' => 'error',
                ]);
            }
        }

        // ── Idle timeout ─────────────────────────────────────────────────────
        if ($settings->idle_timeout_enabled) {
            $lastActivity   = $request->session()->get('last_activity_at');
            $timeoutSeconds = $settings->idle_timeout_minutes * 60;

            if ($lastActivity && (time() - $lastActivity) > $timeoutSeconds) {
                $request->session()->flush();
                $request->session()->invalidate();

                return redirect()->route('tenant.login.by.url', ['tenantName' => $tenantCode])->with([
                    'message'    => 'You were logged out due to inactivity.',
                    'alert-type' => 'error',
                ]);
            }

            $request->session()->put('last_activity_at', time());
        }

        return $next($request);
    }
}