<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RetailAllowed
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

        // 3. Fetch user's role from tenant database
        $user = DB::connection('tenant')
                    ->table('users')
                    ->where('id', $user->id)
                    ->select('role')
                    ->first();

        if (!$user) {
            Auth::logout();

            $notification = [
                'message'    => 'Session expired. You need to login again.',
                'alert-type' => 'error'
            ];
            return redirect()->route('tenant.login.by.url')->with($notification);
        }

        // 4. Admin — full access, no further checks needed
        if ($user->role === 'Admin') {
            return $next($request);
        }

        // 5. Operations — must have Retail sector permission
        if ($user->role === 'Operations') {
            $retailSectorId = DB::connection('tenant')
                                ->table('sectors')
                                ->where('name', 'Retail')
                                ->value('id');

            $hasAccess = $retailSectorId && DB::connection('tenant')
                            ->table('employee_access')
                            ->where('employee_id', Auth::id())
                            ->where('sector_id', $retailSectorId)
                            ->exists();

            if ($hasAccess) {
                return $next($request);
            }

            $notification = [
                'message'    => 'You do not have permission to access the Retail section.',
                'alert-type' => 'error'
            ];
            return redirect()->back()->with($notification);
        }

        // 6. Any other role — deny
        Auth::logout();

        $notification = [
            'message'    => 'This area is restricted to authorised staff only.',
            'alert-type' => 'error'
        ];
        return redirect()->route('tenant.login.by.url')->with($notification);
    }
}