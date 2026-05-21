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

        // Build tenant connection config.
        // On shared hosting (e.g. iFastNet/cPanel), each tenant database is
        // typically owned by its own DB user with grants scoped only to that
        // database. The app's main 'mysql' connection user often cannot see
        // other tenants' databases via SHOW DATABASES / information_schema,
        // even though the database genuinely exists (visible in phpMyAdmin).
        // So instead of asking the main connection "can you see this db",
        // we directly attempt to connect AS the tenant using its own
        // credentials (stored on approval) - this is also the actual
        // connection the app needs to succeed for the request to work.
        $isLocal = app()->environment('local');

        if ($isLocal) {
            config(['database.connections.tenant.database' => $tenantDatabase]);
        } else {
            if (empty($tenant->db_user)) {
                Log::error('Tenant db_user not configured for non-local environment', [
                    'tenantName' => $tenantName,
                    'database' => $tenantDatabase,
                ]);
                return response()->view('tenants.errors.not-found', [
                    'response' => "Database not found for client '{$tenantName}' contact support for assistance ",
                ], 404)->throwResponse();
            }

            // NOTE: matches the hardcoded password used in
            // MasterApproveTenantController when the cPanel DB user was created.
            // TODO: replace with a securely stored per-tenant password
            // (see Option B discussed) once this is migrated off a shared constant.
            config([
                'database.connections.tenant.host'     => env('TENANT_DB_HOST', config('database.connections.mysql.host')),
                'database.connections.tenant.database' => $tenantDatabase,
                'database.connections.tenant.username' => $tenant->db_user,
                'database.connections.tenant.password' => 'binto2020',
            ]);
        }

        DB::purge('tenant');

        try {
            DB::connection('tenant')->getPdo(); // Real connection attempt = real existence + access check
        } catch (\Exception $e) {
            Log::error('Tenant database connection failed', [
                'tenantName' => $tenantName,
                'database' => $tenantDatabase,
                'error' => $e->getMessage(),
            ]);
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

        // Inject tenantName automatically into all route() calls
        URL::defaults(['tenantName' => $tenantName]);
        return $next($request);
    }
}