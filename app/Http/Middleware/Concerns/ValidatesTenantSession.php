<?php

namespace App\Http\Middleware\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


trait ValidatesTenantSession
{

    protected function resolveTenantUser(Request $request): ?object
    {
        $userId = session('auth_user_id');
        $email  = session('auth_user_email');

        if (!$userId || !$email) {
            return null;
        }

        return DB::connection('tenant')
            ->table('users')
            ->where('id', $userId)
            ->where('email', $email)
            ->select('id', 'role')
            ->first();
    }

    protected function hasSessionIdentity(): bool
    {
        return (bool) session('auth_user_id') && (bool) session('auth_user_email');
    }

    protected function tenantMismatch(Request $request): bool
    {
        $currentTenantCode = $request->route('tenantName') ?? session('tenant_code');

        return !$currentTenantCode || session('tenant_code') !== $currentTenantCode;
    }

    protected function resolvedTenantName(Request $request): ?string
    {
        return $request->route('tenantName') ?? $request->segment(1) ?? session('tenant_code');
    }

    protected function denyToLogin(Request $request, string $message)
    {
        session()->flush();

        return redirect()->route('tenant.login.by.url', [
            'tenantName' => $this->resolvedTenantName($request),
        ])->with([
            'message'    => $message,
            'alert-type' => 'error',
        ]);
    }
}