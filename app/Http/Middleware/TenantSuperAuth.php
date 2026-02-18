<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantSuperAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Not logged in → redirect to login
        if (! $user) {
            $notification = array(
                'message'    => 'Please login first',
                'alert-type' => 'error'
            );
            return Redirect()->route('tenant.login.by.url')->with($notification);
        }

        // Get current tenant from route or session
        $currentTenantCode = $request->route('tenantName') ?? session('tenant_code');

        // Compare with the tenant code stored during login
        if (! $currentTenantCode || session('tenant_code') !== $currentTenantCode) {
            Auth::logout();
            session()->flush();

            $notification = array(
                'message'    => 'Session belongs to different tenant. Please login again.',
                'alert-type' => 'error'
            );
            return Redirect()->route('tenant.login.by.url')->with($notification);
        }

        return $next($request);
    }
}