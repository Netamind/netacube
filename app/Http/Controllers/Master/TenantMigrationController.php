<?php
namespace App\Http\Controllers\Master;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
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

    $exists = DB::selectOne(
        'SELECT 1 FROM information_schema.schemata WHERE schema_name = ?',
        [$database]
    );

    if (!$exists) {
        return response()->json([
            'error'  => 'Tenant database does not exist',
            'status' => 409
        ]);
    }

    config(['database.connections.tenant.database' => $database]);
    DB::purge('tenant');

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

    $exists = DB::selectOne(
        'SELECT 1 FROM information_schema.schemata WHERE schema_name = ?',
        [$database]
    );

    if (!$exists) {
        return response()->json([
            'error'  => 'Tenant database does not exist',
            'status' => 409
        ]);
    }

    config(['database.connections.tenant.database' => $database]);
    DB::purge('tenant');

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

            config(['database.connections.tenant.database' => $database]);
            DB::purge('tenant');
            DB::connection('tenant')->reconnect();

            $exists = DB::selectOne(
                'SELECT 1 FROM information_schema.schemata WHERE schema_name = ? LIMIT 1',
                [$database]
            );

            if (!$exists) {
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

    $message = match (true) {
        $failed > 0   => "Completed with {$failed} failure(s), {$skipped} skipped, {$processed} successful",
        $skipped > 0  => "Completed – {$skipped} tenants skipped (missing/invalid database), {$processed} successful",
        default       => "Migrations executed successfully for all {$processed} tenants"
    };

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

