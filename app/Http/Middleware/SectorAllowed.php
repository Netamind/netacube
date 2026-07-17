<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\ValidatesTenantSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;


class SectorAllowed
{
    use ValidatesTenantSession;

    public function handle(Request $request, Closure $next, string ...$allowedSectors): Response
    {
        if (!$this->hasSessionIdentity()) {
            return $this->denyToLogin($request, 'Your session has expired. Please sign in again.');
        }

        if ($this->tenantMismatch($request)) {
            return $this->denyToLogin($request, 'Session mismatch. Please login again.');
        }

        $currentUser = $this->resolveTenantUser($request);

        if (!$currentUser) {
            return $this->denyToLogin($request, 'Session expired. Please login again.');
        }

        // Admin — full access, no further checks needed.
        if ($currentUser->role === 'Admin') {
            return $next($request);
        }

        // Operations — must have access to at least one of the allowed sectors.
        if ($currentUser->role === 'Operations') {
            $sectorIds = DB::connection('tenant')
                ->table('sectors')
                ->whereIn('sector', $allowedSectors) // <-- correct column name
                ->pluck('id');

            $hasAccess = $sectorIds->isNotEmpty() && DB::connection('tenant')
                ->table('employee_access')
                ->where('employee_id', $currentUser->id)
                ->whereIn('sector_id', $sectorIds)
                ->exists();

            if ($hasAccess) {
                return $next($request);
            }

            return redirect()->back()->with([
                'message'    => 'You do not have permission to access this section.',
                'alert-type' => 'error',
            ]);
        }

        // Any other role — deny and force re-login, matching prior behaviour.
        return $this->denyToLogin($request, 'This area is restricted to authorised staff only.');
    }
}