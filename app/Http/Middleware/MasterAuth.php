<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class MasterAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (! $user) {
            $notification = array(
                'message'    => 'Please login first',
                'alert-type' => 'error'
            );
            return Redirect()->route('master.login.page')->with($notification);
        }
        return $next($request);
    }
}