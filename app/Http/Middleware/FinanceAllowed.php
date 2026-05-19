<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class FinanceAllowed
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

        $currentUser = DB::connection('tenant')
                         ->table('users')
                         ->where('id', Auth::id())
                         ->select('id', 'role')
                         ->first();

        if (!$currentUser) {
            Auth::logout();
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

        // Operations — must have Finance sector permission
        if ($currentUser->role === 'Operations') {
            $financeSectorId = DB::connection('tenant')
                                ->table('sectors')
                                ->where('name', 'Finance')
                                ->value('id');

            $hasAccess = $financeSectorId && DB::connection('tenant')
                            ->table('employee_access')
                            ->where('employee_id', $currentUser->id)
                            ->where('sector_id', $financeSectorId)
                            ->exists();

            if ($hasAccess) {
                return $next($request);
            }

            return redirect()->back()->with([
                'message'    => 'You do not have permission to access the Finance section.',
                'alert-type' => 'error'
            ]);
        }

        // Any other role — deny
        Auth::logout();
        session()->flush();
        return redirect()->route('tenant.login.by.url', ['tenantName' => $tenantName])->with([
            'message'    => 'This area is restricted to authorised staff only.',
            'alert-type' => 'error'
        ]);
    }
}