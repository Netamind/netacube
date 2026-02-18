<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class TenantSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            $notification = array(
                'message'    => 'Your session has expired. Please sign in again to continue.',
                'alert-type' => 'error'
            );
            return Redirect()->route('tenant.login.by.url')->with($notification);
        }

        // Directly query current role from tenant database
        $currentRole = DB::connection('tenant')->table('users')->where('id', $user->id)->value('role');

        if ($currentRole === null) {
            Auth::logout();
            $notification = array(
                'message'    => 'Sesion expired you need to login.',
                'alert-type' => 'error'
            );
            return Redirect()->route('tenant.login.by.url')->with($notification);
        }

        if ($currentRole !== 'SuperAdmin') {
            Auth::logout();
            $notification = array(
                'message'    => 'This area is restricted to administrators only.',
                'alert-type' => 'error'
            );
            return Redirect()->route('tenant.login.by.url')->with($notification);
        }

        return $next($request);
    }
}