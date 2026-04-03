<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TenantAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // 1. Check if user is logged in
        if (!$user) {
            $notification = [
                'message'    => 'Your session has expired. Please sign in again to continue.',
                'alert-type' => 'error'
            ];
            return redirect()->route('tenant.login.by.url')->with($notification);
        }

        // 2. Verify tenant session belongs to the correct tenant
        $currentTenantCode = $request->route('tenantName') ?? session('tenant_code');

        if (!$currentTenantCode || session('tenant_code') !== $currentTenantCode) {
            Auth::logout();
            session()->flush();

            $notification = [
                'message'    => 'Session belongs to a different tenant. Please login again.',
                'alert-type' => 'error'
            ];
            return redirect()->route('tenant.login.by.url')->with($notification);
        }

        // 3. Check if user is Admin (directly from tenant database)
        $currentRole = DB::connection('tenant')
                         ->table('users')
                         ->where('id', $user->id)
                         ->value('role');

        if ($currentRole === null) {
            Auth::logout();

            $notification = [
                'message'    => 'Session expired. You need to login again.',
                'alert-type' => 'error'
            ];
            return redirect()->route('tenant.login.by.url')->with($notification);
        }

        if ($currentRole !== 'Admin') {
            Auth::logout();

            $notification = [
                'message'    => 'This area is restricted to administrators only.',
                'alert-type' => 'error'
            ];
            return redirect()->route('tenant.login.by.url')->with($notification);
        }

        // All checks passed
        return $next($request);
    }
}