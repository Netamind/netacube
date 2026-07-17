<?php
// app/Http/Middleware/Concerns/ResolvesDashboardRoleSettings.php
//
// Shared by EnforceIdleTimeout and EnforceSessionLifetime so both middleware
// look up "this role's dashboard settings row (or its defaults)" the exact
// same way, for the exact same three roles. Add a role here once and both
// middleware pick it up — no other role is enforced by either middleware.
//
// Roles fall into two shapes:
//   'tenant' scope — one row per tenant, looked up by id = 1 (Admin: every
//                    tenant has a single shared admin_dashboard_settings row).
//                    No defaults fallback: if the row is missing, enforcement
//                    is skipped for the request, same as before this refactor.
//   'user'   scope — one row per user, looked up by user_id (Operations,
//                    Sales). If a user hasn't saved settings yet, we fall
//                    back to that role's defaultsObject() so a brand-new
//                    user is still genuinely protected rather than silently
//                    unenforced — this is the same fallback Operations
//                    already used, now shared by every 'user' scoped role.
//
// Sales: sales_dashboard_settings / SalesDashboardSettingsController don't
// exist yet. The 'defaults' entry below is wired up in advance so nothing
// else needs to change here once they're created — resolveSettings() checks
// class_exists()/method_exists() before calling it, so until then a Sales
// user with no row simply falls through unenforced (identical to today).
//
// Sales note (product decision, not implemented here): Sales settings won't
// expose "Default Landing Sector" the way Operations does, since Sales users
// are tied to a branch rather than a sector — but per current plan the field
// stays on the Sales settings form, just disabled, rather than being removed.
// That's a SalesDashboardSettingsController/view concern, not this trait's.

namespace App\Http\Middleware\Concerns;

use App\Http\Controllers\Tenant\OperationsDashboardSettingsController;
use App\Http\Controllers\Tenant\SalesDashboardSettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait ResolvesDashboardRoleSettings
{
    /**
     * @var array<string, array{table: string, scope: 'tenant'|'user', defaults?: array{0: class-string, 1: string}}>
     */
    protected static array $roleConfig = [
        'Admin' => [
            'table' => 'admin_dashboard_settings',
            'scope' => 'tenant',
        ],
        'Operations' => [
            'table'    => 'operations_dashboard_settings',
            'scope'    => 'user',
            'defaults' => [OperationsDashboardSettingsController::class, 'defaultsObject'],
        ],
        'Sales' => [
            'table'    => 'sales_dashboard_settings',
            'scope'    => 'user',
            'defaults' => [SalesDashboardSettingsController::class, 'defaultsObject'],
        ],
    ];

    /**
     * Config for the given role, or null if this role isn't one of the
     * three enforced roles (Admin/Operations/Sales) — every other role
     * passes through both middleware untouched.
     */
    protected function roleConfig(?string $role): ?array
    {
        if (!$role) {
            return null;
        }

        return static::$roleConfig[$role] ?? null;
    }

    /**
     * Resolve the settings row for $config, using $userId for 'user' scope.
     * Returns null when there's nothing to enforce against (missing tenant
     * row, or missing user row with no usable defaults yet).
     */
    protected function resolveRoleSettings(array $config, ?int $userId)
    {
        $query = DB::connection('tenant')->table($config['table']);

        if ($config['scope'] === 'tenant') {
            return $query->where('id', 1)->first() ?: null;
        }

        $settings = $query->where('user_id', $userId)->first();

        if ($settings) {
            return $settings;
        }

        $defaults = $config['defaults'] ?? null;

        if (is_array($defaults)
            && isset($defaults[0], $defaults[1])
            && class_exists($defaults[0])
            && method_exists($defaults[0], $defaults[1])) {
            return call_user_func($defaults);
        }

        return null;
    }
}