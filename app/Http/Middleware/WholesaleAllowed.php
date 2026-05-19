<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class WholesaleAllowed
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantName = $request->route('tenantName') ?? $request->segment(1) ?? session('tenant_code');

        // 1. Check if user is logged in
        if (!Auth::check()) {
            return redirect()->route('tenant.login.by.url', ['tenantName' => $tenantName])->with([
                'message'    => 'Your session has expired. Please sign in again.',
                'alert-type' => 'error'
            ]);
        }

        // 2. Verify tenant session belongs to the correct tenant
        $currentTenantCode = $request->route('tenantName') ?? session('tenant_code');

        if (!$currentTenantCode || session('tenant_code') !== $currentTenantCode) {
            Auth::logout();
            session()->flush();
            return redirect()->route('tenant.login.by.url', ['tenantName' => $tenantName])->with([
                'message'    => 'Session belongs to a different tenant. Please login again.',
                'alert-type' => 'error'
            ]);
        }

        // 3. Fetch user's role from tenant database
        $currentUser = DB::connection('tenant')
                         ->table('users')
                         ->where('id', Auth::id())
                         ->select('id', 'role')
                         ->first();

        if (!$currentUser) {
            Auth::logout();
            session()->flush();
            return redirect()->route('tenant.login.by.url', ['tenantName' => $tenantName])->with([
                'message'    => 'Session expired. You need to login again.',
                'alert-type' => 'error'
            ]);
        }

        // 4. Admin — full access, no further checks needed
        if ($currentUser->role === 'Admin') {
            return $next($request);
        }

        // 5. Operations — must have Wholesale sector permission
        if ($currentUser->role === 'Operations') {
            $wholesaleSectorId = DB::connection('tenant')
                                    ->table('sectors')
                                    ->where('name', 'Wholesale')
                                    ->value('id');

            $hasAccess = $wholesaleSectorId && DB::connection('tenant')
                            ->table('employee_access')
                            ->where('employee_id', $currentUser->id)
                            ->where('sector_id', $wholesaleSectorId)
                            ->exists();

            if ($hasAccess) {
                return $next($request);
            }

            return redirect()->back()->with([
                'message'    => 'You do not have permission to access the Wholesale section.',
                'alert-type' => 'error'
            ]);
        }

        // 6. Any other role — deny
        Auth::logout();
        session()->flush();
        return redirect()->route('tenant.login.by.url', ['tenantName' => $tenantName])->with([
            'message'    => 'This area is restricted to authorised staff only.',
            'alert-type' => 'error'
        ]);
    }
}