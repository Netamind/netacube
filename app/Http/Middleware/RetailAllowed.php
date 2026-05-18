<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RetailAllowed
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantName = $request->route('tenantName') ?? $request->segment(1) ?? session('tenant_code');

        if (!session('auth_user_email')) {
            return redirect()->route('tenant.login.by.url', ['tenantName' => $tenantName])->with([
                'message'    => 'Your session has expired. Please sign in again.',
                'alert-type' => 'error'
            ]);
        }

        $currentTenantCode = $request->route('tenantName') ?? session('tenant_code');

        if (!$currentTenantCode || session('tenant_code') !== $currentTenantCode) {
            session()->flush();
            return redirect()->route('tenant.login.by.url', ['tenantName' => $tenantName])->with([
                'message'    => 'Session mismatch. Please login again.',
                'alert-type' => 'error'
            ]);
        }

        $currentUser = DB::connection('tenant')
                         ->table('users')
                         ->where('email', session('auth_user_email'))
                         ->select('id', 'role')
                         ->first();

        if (!$currentUser) {
            session()->flush();
            return redirect()->route('tenant.login.by.url', ['tenantName' => $tenantName])->with([
                'message'    => 'Session expired. Please login again.',
                'alert-type' => 'error'
            ]);
        }

        // Admin — full access, no further checks needed
        if ($currentUser->role === 'Admin') {
            return $next($request);
        }

        // Operations — must have Retail sector permission
        if ($currentUser->role === 'Operations') {
            $retailSectorId = DB::connection('tenant')
                                ->table('sectors')
                                ->where('name', 'Retail')
                                ->value('id');

            $hasAccess = $retailSectorId && DB::connection('tenant')
                            ->table('employee_access')
                            ->where('employee_id', $currentUser->id)
                            ->where('sector_id', $retailSectorId)
                            ->exists();

            if ($hasAccess) {
                return $next($request);
            }

            return redirect()->back()->with([
                'message'    => 'You do not have permission to access the Retail section.',
                'alert-type' => 'error'
            ]);
        }

        // Any other role — deny
        session()->flush();
        return redirect()->route('tenant.login.by.url', ['tenantName' => $tenantName])->with([
            'message'    => 'This area is restricted to authorised staff only.',
            'alert-type' => 'error'
        ]);
    }
}