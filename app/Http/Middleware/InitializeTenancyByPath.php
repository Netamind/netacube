<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use App\Models\Tenant;

class InitializeTenancyByPath
{
    public function handle(Request $request, Closure $next)
    {
        $tenantName = $request->segment(1);


        if (!$tenantName) {
            return $next($request);
        }

        if (!preg_match('/^[a-zA-Z0-9-]+$/', $tenantName)) {
            return response()->view('tenants.errors.not-found', [
                'response' => "Invalid url  '{$tenantName}' make sure you type client name correctly ",
            ], 404)->throwResponse();
        }

        // Lookup tenant
        $tenant = Tenant::where('client_url', $tenantName)->first();

        

        if (!$tenant) {
            return response()->view('tenants.errors.not-found', [
                'response' => "url not found try again and ensure you type everything correctly or contact system administrator",
            ], 404)->throwResponse();
        }

        $tenantDatabase = $tenant->data;

        if (!$tenantDatabase) {
            Log::error('Tenant database not configured', [
                'tenantName' => $tenantName,
                'data' => $tenant->data,
            ]);
            return response()->view('tenants.errors.not-found', [
                'message' => "Tenant '{$tenantName}' database not configured",
            ], 500)->throwResponse();
        }

        // Check if database exists
        $escapedDbName = DB::connection()->getPdo()->quote($tenantDatabase);
        $dbExists = DB::connection()->select("SHOW DATABASES LIKE $escapedDbName");

        if (empty($dbExists)) {
            return response()->view('tenants.errors.not-found', [
             'response' => "Database not found for client '{$tenantName}' contact support for assistance ",
            ], 404)->throwResponse();
        }

        // Check tenant status
        if ($tenant->status === 'Pending') {
            return response()->view('tenants.errors.pending-tenant', [
            'response' => "Client '{$tenantName}' Not configured to use the system contact support for assistance",
            ], 404)->throwResponse();
        }

        if ($tenant->status !== 'Approved') {
            return response()->view('tenants.errors.invalid-status', [
            'response' => "Invalid status for client '{$tenantName}' contact support for assistance",
            ], 404)->throwResponse(); 
        }

        if ($tenant->put_on_hold === 'Yes') {
            return response()->view('tenants.errors.suspended-tenant', [
            'response' => "Client '{$tenantName}' is suspended contact support for assistance",
            ], 404)->throwResponse();
        }

        // Set tenant database connection
        try {
            config(['database.connections.tenant.database' => $tenantDatabase]);
            DB::purge('tenant');
            DB::connection('tenant')->getPdo(); // Test connection
        } catch (\Exception $e) {
            return response()->view('tenants.errors.database-error', [
                'message' => "Failed to connect to  '{$tenantName}' database contact support for assistance",
            ], 500)->throwResponse();
        }

        // 🔑 Inject tenantName automatically into all route() calls
        URL::defaults(['tenantName' => $tenantName]);

        return $next($request);
    }
}
