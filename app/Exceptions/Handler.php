<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

   public function render($request, Throwable $e)
{
    if ($e instanceof TokenMismatchException) {
        // Get tenantName from the URL segment since session may be dead
        $tenantName = $request->segment(1);

        if ($tenantName) {
            return redirect()
                ->route('tenant.login.by.url', ['tenantName' => $tenantName])
                ->with([
                    'message'    => 'Your session expired. Please login again.',
                    'alert-type' => 'warning'
                ]);
        }

        // Fallback if no tenantName in URL
        return redirect('/')
            ->with([
                'message'    => 'Your session expired. Please login again.',
                'alert-type' => 'warning'
            ]);
    }

    return parent::render($request, $e);
}
}