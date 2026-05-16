<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SalesAllowed
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        Log::info('SalesAllowed [1] user check', [
            'user_id'    => $user?->id,
            'user_email' => $user?->email,
            'logged_in'  => (bool) $user,
        ]);

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

        Log::info('SalesAllowed [2] tenant check', [
            'route_tenantName'   => $request->route('tenantName'),
            'session_tenant_code'=> session('tenant_code'),
            'session_tenant_db'  => session('tenant_database'),
            'currentTenantCode'  => $currentTenantCode,
            'codes_match'        => session('tenant_code') === $currentTenantCode,
        ]);

        if (!$currentTenantCode || session('tenant_code') !== $currentTenantCode) {
            Auth::logout();
            session()->flush();

            $notification = [
                'message'    => 'Session belongs to a different tenant. Please login again.',
                'alert-type' => 'error'
            ];
            return redirect()->route('tenant.login.by.url')->with($notification);
        }

        // 3. Check if user is Sales (directly from tenant database)
        $tenantDbConfig = config('database.connections.tenant.database');

        Log::info('SalesAllowed [3] before DB query', [
            'tenant_db_config' => $tenantDbConfig,
            'querying_user_id' => $user->id,
        ]);

        $currentRole = DB::connection('tenant')
                         ->table('users')
                         ->where('id', $user->id)
                         ->value('role');

        Log::info('SalesAllowed [4] role result', [
            'role_returned' => $currentRole,
            'is_null'       => $currentRole === null,
            'is_sales'      => $currentRole === 'Sales',
        ]);

        if ($currentRole === null) {
            Auth::logout();

            $notification = [
                'message'    => 'Session expired. You need to login again.',
                'alert-type' => 'error'
            ];
            return redirect()->route('tenant.login.by.url')->with($notification);
        }

        if ($currentRole !== 'Sales') {
            Log::warning('SalesAllowed [5] role mismatch — denying access', [
                'user_id'      => $user->id,
                'user_email'   => $user->email,
                'role_in_db'   => $currentRole,
                'expected_role'=> 'Sales',
                'tenant_db'    => $tenantDbConfig,
            ]);

            Auth::logout();

            $notification = [
                'message'    => 'This area is restricted to Sales staff only.',
                'alert-type' => 'error'
            ];
            return redirect()->route('tenant.login.by.url')->with($notification);
        }

        Log::info('SalesAllowed [6] access granted', [
            'user_id'   => $user->id,
            'user_email'=> $user->email,
            'role'      => $currentRole,
        ]);

        // All checks passed
        return $next($request);
    }
}