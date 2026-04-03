<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class MasterAdmin
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
            return redirect()->route('master.login.page')->with($notification);
        }

        // 2. Check user role from master database
        $currentRole = DB::table('users')
                         ->where('id', $user->id)
                         ->value('role');

        if ($currentRole === null) {
            Auth::logout();

            $notification = [
                'message'    => 'Your role to the system is not defined.',
                'alert-type' => 'error'
            ];
            return redirect()->route('master.login.page')->with($notification);
        }

        if ($currentRole !== 'Admin') {
            Auth::logout();

            $notification = [
                'message'    => 'This area is restricted to administrators only.',
                'alert-type' => 'error'
            ];
            return redirect()->route('master.login.page')->with($notification);
        }

        // All checks passed → proceed
        return $next($request);
    }
}