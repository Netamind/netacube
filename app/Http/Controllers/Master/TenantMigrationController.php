<?php
namespace App\Http\Controllers\Master;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class TenantMigrationController extends Controller
{
   public function showTenantMigrationView()
{
    $tenants = Tenant::orderBy('id')->get();
    return view('master.migrations', compact('tenants'));
}

public function showTenantMigrationActionsView($tenantId)
{
    $tenant = Tenant::findOrFail($tenantId);
    return view('master.migration-actions', compact('tenant'));
}


public function showGlobalMigrations()
{
    return view('master.global-migrations');
}

/**
 * Point the 'tenant' connection at the given tenant's database, using the
 * tenant's own DB credentials in production (shared hosting / cPanel DB
 * users are scoped to their own database and the app's default 'mysql'
 * user often cannot see or access other tenants' databases). Returns
 * true if a real connection to the tenant database succeeds.
 */
private function connectAsTenant(Tenant $tenant, string $database): bool
{
    $isLocal = app()->environment('local');

    try {
        if ($isLocal) {
            config(['database.connections.tenant.database' => $database]);
        } else {
            if (empty($tenant->db_user)) {
                Log::warning("Tenant {$tenant->id} has no db_user configured for production connection");
                return false;
            }

            config([
                'database.connections.tenant.host'     => env('TENANT_DB_HOST', config('database.connections.mysql.host')),
                'database.connections.tenant.database' => $database,
                'database.connections.tenant.username' => $tenant->db_user,
                'database.connections.tenant.password' => 'binto2020',
            ]);
        }

        DB::purge('tenant');
        DB::connection('tenant')->getPdo();

        return true;
    } catch (\Exception $e) {
        Log::warning("Failed to connect as tenant {$tenant->id} ({$database}): " . $e->getMessage());
        return false;
    }
}

public function executePendingMigrations($tenantId)
{
    $tenant = Tenant::find($tenantId);

    if (!$tenant) {
        return response()->json([
            'error'  => 'Tenant not found',
            'status' => 409
        ]);
    }

    $database = $tenant->data;

    if (empty($database)) {
        return response()->json([
            'error'  => 'No database name configured for this tenant',
            'status' => 409
        ]);
    }

    // Optional: basic validation of database name format
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $database)) {
        return response()->json([
            'error'  => 'Invalid database name configured for this tenant',
            'status' => 409
        ]);
    }

    if (!$this->connectAsTenant($tenant, $database)) {
        return response()->json([
            'error'  => 'Tenant database does not exist or could not be reached',
            'status' => 409
        ]);
    }

    $exitCode = Artisan::call('migrate', [
        '--database' => 'tenant',
        '--force'    => true,
        '--path'     => 'database/migrations/tenant',
    ]);

    if ($exitCode === 0) {
        return response()->json([
            'success' => 'Migrations executed successfully',
            'status'  => 201
        ]);
    }

    Log::warning("Migration failed for tenant {$tenant->id} ({$database}) - exit code: {$exitCode} - output: " . Artisan::output());

    return response()->json([
        'error'  => 'Migration failed (non-zero exit code)',
        'status' => 409
    ]);
}
/**
 * Danger zone: Drops all tables and re-runs all migrations (deletes all data!)
 */public function resetTenantDatabaseCompletely($tenantId)
{
    $tenant = Tenant::find($tenantId);

    if (!$tenant) {
        return response()->json([
            'error'  => 'Tenant not found',
            'status' => 409
        ]);
    }

    $database = $tenant->data;

    if (empty($database)) {
        return response()->json([
            'error'  => 'No database name configured for this tenant',
            'status' => 409
        ]);
    }

    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $database)) {
        return response()->json([
            'error'  => 'Invalid database name configured for this tenant',
            'status' => 409
        ]);
    }

    if (!$this->connectAsTenant($tenant, $database)) {
        return response()->json([
            'error'  => 'Tenant database does not exist or could not be reached',
            'status' => 409
        ]);
    }

    $exitCode = Artisan::call('migrate:fresh', [
        '--database' => 'tenant',
        '--force'    => true,
        '--path'     => 'database/migrations/tenant',
    ]);

    if ($exitCode === 0) {
        return response()->json([
            'success' => 'Database has been completely dropped and fully re-migrated',
            'status'  => 201
        ]);
    }

    Log::warning("Fresh migration failed for tenant {$tenant->id} ({$database}) - exit code: {$exitCode} - output: " . Artisan::output());

    return response()->json([
        'error'  => 'Fresh migration failed',
        'status' => 409
    ]);
}
public function runPendingForAll(Request $request)
{
    $chunkSize = 50; // adjust based on your server resources (50–200 usually safe)

    $total = Tenant::count();

    if ($total === 0) {
        return response()->json([
            'success'   => true,
            'message'   => 'No tenants found',
            'processed' => 0,
            'skipped'   => 0,
            'failed'    => 0,
            'total'     => 0,
            'status'    => 200
        ]);
    }

    $processed = 0;
    $skipped   = 0;
    $failed    = 0;

    Tenant::query()->chunk($chunkSize, function ($tenants) use (&$processed, &$skipped, &$failed) {
        foreach ($tenants as $tenant) {
            $database = $tenant->data;

            if (empty($database)) {
                $skipped++;
                continue;
            }

            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $database)) {
                $skipped++;
                Log::warning("Invalid database name for tenant {$tenant->id}: {$database}");
                continue;
            }

            if (!$this->connectAsTenant($tenant, $database)) {
                $skipped++;
                continue;
            }

            $exitCode = Artisan::call('migrate', [
                '--database' => 'tenant',
                '--force'    => true,
                '--path'     => 'database/migrations/tenant',
            ]);

            if ($exitCode === 0) {
                $processed++;
            } else {
                $failed++;
                Log::warning("Migration failed for tenant {$tenant->id} ({$database}) - exit code: {$exitCode}");
            }

            // Optional: small delay to reduce DB connection pressure
            // usleep(50000); // 50ms delay – uncomment if needed
        }
    });

    if ($failed > 0) {
        $message = "Completed with {$failed} failure(s), {$skipped} skipped, {$processed} successful";
    } elseif ($skipped > 0) {
        $message = "Completed - {$skipped} tenants skipped (missing/invalid database), {$processed} successful";
    } else {
        $message = "Migrations executed successfully for all {$processed} tenants";
    }


    return response()->json([
        'success'   => $failed === 0,
        'message'   => $message,
        'processed' => $processed,
        'skipped'   => $skipped,
        'failed'    => $failed,
        'total'     => $total,
        'status'    => $failed === 0 ? 200 : 207
    ]);
}
}