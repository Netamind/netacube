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

        if (! $user) {
            $notification = array(
                'message'    => 'Your session has expired. Please sign in again to continue.',
                'alert-type' => 'error'
            );
            return Redirect()->route('master.login.page')->with($notification);
        }

        $currentRole = DB::table('users')->where('id', $user->id)->value('role');

        if ($currentRole === null) {
            Auth::logout();
            $notification = array(
                'message'    => 'Your role to the system is not defined.',
                'alert-type' => 'error'
            );
            return Redirect()->route('master.login.page')->with($notification);
        }

        if ($currentRole !== 'Admin') {
            Auth::logout();
            $notification = array(
                'message'    => 'This area is restricted to administrators only.',
                'alert-type' => 'error'
            );
            return Redirect()->route('master.login.page')->with($notification);
        }

        return $next($request);
    }
}