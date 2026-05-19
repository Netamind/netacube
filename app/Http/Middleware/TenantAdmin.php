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
        $tenantName = $request->route('tenantName') ?? $request->segment(1) ?? session('tenant_code');

        if (!Auth::check()) {
            return redirect()->route('tenant.login.by.url', ['tenantName' => $tenantName])->with([
                'message'    => 'Your session has expired. Please sign in again.',
                'alert-type' => 'error'
            ]);
        }

        $currentTenantCode = $request->route('tenantName') ?? session('tenant_code');

        if (!$currentTenantCode || session('tenant_code') !== $currentTenantCode) {
            Auth::logout();
            session()->flush();
            return redirect()->route('tenant.login.by.url', ['tenantName' => $tenantName])->with([
                'message'    => 'Session mismatch. Please login again.',
                'alert-type' => 'error'
            ]);
        }

        $currentRole = DB::connection('tenant')
                         ->table('users')
                         ->where('id', Auth::id())
                         ->value('role');

        if ($currentRole === null) {
            Auth::logout();
            session()->flush();
            return redirect()->route('tenant.login.by.url', ['tenantName' => $tenantName])->with([
                'message'    => 'Session expired. Please login again.',
                'alert-type' => 'error'
            ]);
        }

        if ($currentRole !== 'Admin' && $currentRole !== 'Operations') {
            Auth::logout();
            session()->flush();
            return redirect()->route('tenant.login.by.url', ['tenantName' => $tenantName])->with([
                'message'    => 'This area is restricted to administrators only.',
                'alert-type' => 'error'
            ]);
        }

        return $next($request);
    }
}