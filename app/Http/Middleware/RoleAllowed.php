<?php

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\ValidatesTenantSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleAllowed
{
    use ValidatesTenantSession;

    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
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

        if (!in_array($currentUser->role, $allowedRoles, true)) {
            return $this->denyToLogin($request, 'This area is restricted to authorised staff only.');
        }

        return $next($request);
    }
}