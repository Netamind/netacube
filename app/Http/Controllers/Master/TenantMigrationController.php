<?php
namespace App\Http\Controllers\Master;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant;
use Illuminate\Support\Facades\Schema;

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

    private function connectAsTenant(Tenant $tenant, string $database): bool
    {
        $isLocal = app()->environment('local');

        $tenantPassword = null;

        if (!$isLocal) {
            if (empty($tenant->db_user)) {
                Log::warning("Tenant {$tenant->id} has no db_user configured");
                return false;
            }

         
            $tenantPassword = config('database.tenant_db_password') ?? env('TENANT_DB_PASSWORD');

            if (empty($tenantPassword)) {
                Log::error('TENANT_DB_PASSWORD is not set or not readable (check .env and whether config is cached — run `php artisan config:clear`)');
                return false;
            }
        }

        try {
            // Only purge once every required value is confirmed present, so we
            // never leave the connection torn down with stale config behind it.
            DB::purge('tenant');

            if ($isLocal) {
                config(['database.connections.tenant.database' => $database]);
            } else {
                config([
                    'database.connections.tenant.host'     => env('TENANT_DB_HOST', config('database.connections.mysql.host')),
                    'database.connections.tenant.database' => $database,
                    'database.connections.tenant.username' => $tenant->db_user,
                    'database.connections.tenant.password' => $tenantPassword,
                ]);
            }

            DB::connection('tenant')->getPdo();
            return true;
        } catch (\Exception $e) {
            Log::warning("Failed to connect as tenant {$tenant->id} ({$database}): " . $e->getMessage());
            // ✅ Always purge on failure too, so nothing downstream can silently
            // pick up a half-configured or stale connection.
            DB::purge('tenant');
            return false;
        }
    }

    /**
     * Returns pending migration names for the tenant.
     */
    public function getPendingMigrationsList($tenantId)
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        $database = $tenant->data;

        if (empty($database) || !preg_match('/^[a-zA-Z0-9_-]+$/', $database)) {
            return response()->json(['error' => 'Invalid database config'], 409);
        }

        if (!$this->connectAsTenant($tenant, $database)) {
            return response()->json(['error' => 'Cannot connect to tenant database'], 409);
        }

        $migrationPath = database_path('migrations/tenant');
        $allFiles      = collect(File::files($migrationPath))
            ->map(fn($f) => $f->getFilenameWithoutExtension())
            ->sort()
            ->values();

        $ran = collect();
        if (Schema::connection('tenant')->hasTable('migrations')) {
            $ran = DB::connection('tenant')->table('migrations')->pluck('migration');
        }

        $pending = $allFiles->diff($ran)->values();

        DB::purge('tenant');

        return response()->json([
            'pending' => $pending,
            'total'   => $allFiles->count(),
            'ran'     => $ran->count(),
        ]);
    }

    /**
     * Runs ONE pending migration with --step.
     *
     * Self-healing: if the error is "table already exists" we check whether the
     * table is actually there. If it is, the migration succeeded physically but
     * the migrations record was never written (e.g. a previous crash). We insert
     * the record ourselves and return success so the loop continues cleanly.
     *
     * ✅ FIX: the reconnect inside this block now has its return value checked.
     * If we can't genuinely reconnect to THIS tenant's database, we abort
     * self-healing entirely instead of letting Schema::hasTable() run against
     * whatever connection happens to be lying around.
     */
    public function runNextMigration($tenantId)
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        $database = $tenant->data;

        if (empty($database) || !preg_match('/^[a-zA-Z0-9_-]+$/', $database)) {
            return response()->json(['error' => 'Invalid database config'], 409);
        }

        if (!$this->connectAsTenant($tenant, $database)) {
            return response()->json(['error' => 'Cannot connect to tenant database'], 409);
        }

        try {
            $exitCode = Artisan::call('migrate', [
                '--database' => 'tenant',
                '--force'    => true,
                '--path'     => 'database/migrations/tenant',
                '--step'     => true,
            ]);

            $output = trim(Artisan::output());
            DB::purge('tenant');

            if ($exitCode === 0) {
                return response()->json(['success' => true, 'output' => $output]);
            }

            Log::warning("Migration step failed for tenant {$tenant->id}: {$output}");
            return response()->json(['error' => 'Migration step failed', 'output' => $output], 500);

        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            Log::error("Migration exception for tenant {$tenant->id}: {$errorMsg}");

            // ── Self-healing: "table already exists" ─────────────────────────
            if ($this->isTableAlreadyExistsError($e)) {
                $tableName     = $this->extractTableNameFromError($errorMsg);
                $migrationName = $this->detectCurrentMigrationName($tenant, $database);

                if ($tableName && $migrationName) {
                    try {
                        // ✅ FIX: bail out of self-healing if we can't actually
                        // reconnect to THIS tenant — don't trust a stale connection.
                        if (!$this->connectAsTenant($tenant, $database)) {
                            Log::error("Self-heal aborted for tenant {$tenant->id}: could not reconnect");
                        } else {
                            $tableExists = Schema::connection('tenant')->hasTable($tableName);

                            if ($tableExists) {
                                $alreadyRecorded = DB::connection('tenant')
                                    ->table('migrations')
                                    ->where('migration', $migrationName)
                                    ->exists();

                                if (!$alreadyRecorded) {
                                    $maxBatch = DB::connection('tenant')
                                        ->table('migrations')
                                        ->max('batch') ?? 0;

                                    DB::connection('tenant')->table('migrations')->insert([
                                        'migration' => $migrationName,
                                        'batch'     => $maxBatch + 1,
                                    ]);
                                }

                                DB::purge('tenant');

                                return response()->json([
                                    'success' => true,
                                    'healed'  => true,
                                    'output'  => "Table '{$tableName}' already existed — migration marked as complete.",
                                ]);
                            }
                        }
                    } catch (\Throwable $healEx) {
                        Log::error("Self-heal failed for tenant {$tenant->id}: " . $healEx->getMessage());
                    }
                }
            }
            // ─────────────────────────────────────────────────────────────────

            DB::purge('tenant');

            return response()->json([
                'error'  => $errorMsg,
                'output' => basename($e->getFile()) . ':' . $e->getLine(),
            ], 500);
        }
    }

    /**
     * Returns true if the exception is a "table already exists" (ER 1050).
     */
    private function isTableAlreadyExistsError(\Throwable $e): bool
    {
        return str_contains($e->getMessage(), '1050') ||
               str_contains(strtolower($e->getMessage()), 'already exists');
    }

    /**
     * Extracts the table name from a MySQL 1050 error message.
     * e.g. "Table 'retail_price_changes' already exists"
     */
    private function extractTableNameFromError(string $message): ?string
    {
        if (preg_match("/Table '([^']+)' already exists/i", $message, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Finds which migration is next in the pending list for this tenant,
     * so we know which record to insert into the migrations table.
     *
     * ✅ FIX: return value of connectAsTenant() is now checked — previously
     * this ran Schema/DB calls even if the reconnect silently failed.
     */
    private function detectCurrentMigrationName(Tenant $tenant, string $database): ?string
    {
        try {
            if (!$this->connectAsTenant($tenant, $database)) {
                return null;
            }

            $migrationPath = database_path('migrations/tenant');
            $allFiles      = collect(File::files($migrationPath))
                ->map(fn($f) => $f->getFilenameWithoutExtension())
                ->sort()
                ->values();

            $ran = collect();
            if (Schema::connection('tenant')->hasTable('migrations')) {
                $ran = DB::connection('tenant')->table('migrations')->pluck('migration');
            }

            return $allFiles->diff($ran)->values()->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function executePendingMigrations($tenantId)
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) return response()->json(['error' => 'Tenant not found', 'status' => 409]);

        $database = $tenant->data;
        if (empty($database)) return response()->json(['error' => 'No database name configured', 'status' => 409]);
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $database)) return response()->json(['error' => 'Invalid database name', 'status' => 409]);
        if (!$this->connectAsTenant($tenant, $database)) return response()->json(['error' => 'Cannot connect to tenant database', 'status' => 409]);

        $exitCode = Artisan::call('migrate', [
            '--database' => 'tenant',
            '--force'    => true,
            '--path'     => 'database/migrations/tenant',
        ]);

        if ($exitCode === 0) return response()->json(['success' => 'Migrations executed successfully', 'status' => 201]);

        Log::warning("Migration failed for tenant {$tenant->id}: " . Artisan::output());
        return response()->json(['error' => 'Migration failed', 'status' => 409]);
    }

    public function resetTenantDatabaseCompletely($tenantId)
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) return response()->json(['error' => 'Tenant not found', 'status' => 409]);

        $database = $tenant->data;
        if (empty($database)) return response()->json(['error' => 'No database name configured', 'status' => 409]);
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $database)) return response()->json(['error' => 'Invalid database name', 'status' => 409]);
        if (!$this->connectAsTenant($tenant, $database)) return response()->json(['error' => 'Cannot connect to tenant database', 'status' => 409]);

        $exitCode = Artisan::call('migrate:fresh', [
            '--database' => 'tenant',
            '--force'    => true,
            '--path'     => 'database/migrations/tenant',
        ]);

        if ($exitCode === 0) return response()->json(['success' => 'Database reset and fully re-migrated', 'status' => 201]);

        Log::warning("Fresh migration failed for tenant {$tenant->id}: " . Artisan::output());
        return response()->json(['error' => 'Fresh migration failed', 'status' => 409]);
    }

    public function runPendingForAll(Request $request)
    {
        $total = Tenant::count();
        if ($total === 0) {
            return response()->json(['success' => true, 'message' => 'No tenants found',
                'processed' => 0, 'skipped' => 0, 'failed' => 0, 'total' => 0, 'status' => 200]);
        }

        $processed = $skipped = $failed = 0;

        Tenant::query()->chunk(50, function ($tenants) use (&$processed, &$skipped, &$failed) {
            foreach ($tenants as $tenant) {
                $database = $tenant->data;
                if (empty($database) || !preg_match('/^[a-zA-Z0-9_-]+$/', $database)) { $skipped++; continue; }
                if (!$this->connectAsTenant($tenant, $database)) { $skipped++; continue; }

                $exitCode = Artisan::call('migrate', [
                    '--database' => 'tenant', '--force' => true, '--path' => 'database/migrations/tenant',
                ]);

                $exitCode === 0 ? $processed++ : $failed++;
            }
        });

        $message = $failed > 0
            ? "Completed with {$failed} failure(s), {$skipped} skipped, {$processed} successful"
            : ($skipped > 0 ? "{$skipped} skipped, {$processed} successful" : "All {$processed} tenants migrated");

        return response()->json(['success' => $failed === 0, 'message' => $message,
            'processed' => $processed, 'skipped' => $skipped, 'failed' => $failed,
            'total' => $total, 'status' => $failed === 0 ? 200 : 207]);
    }

    /**
     * Returns all tenants with their pending migration counts.
     * Used by the global runner to build the work queue.
     */
    public function getGlobalPendingList()
    {
        $migrationPath = database_path('migrations/tenant');
        $allFiles      = collect(File::files($migrationPath))
            ->map(fn($f) => $f->getFilenameWithoutExtension())
            ->sort()
            ->values();

        $totalFiles = $allFiles->count();
        $tenants    = Tenant::orderBy('id')->get();
        $result     = [];

        foreach ($tenants as $tenant) {
            $database = $tenant->data;

            if (empty($database) || !preg_match('/^[a-zA-Z0-9_-]+$/', $database)) {
                $result[] = [
                    'id'          => $tenant->id,
                    'name'        => $tenant->business_name,
                    'database'    => $database ?? 'N/A',
                    'pending'     => [],
                    'skipped'     => true,
                    'skip_reason' => 'Invalid or missing database name',
                ];
                continue;
            }

            if (!$this->connectAsTenant($tenant, $database)) {
                $result[] = [
                    'id'          => $tenant->id,
                    'name'        => $tenant->business_name,
                    'database'    => $database,
                    'pending'     => [],
                    'skipped'     => true,
                    'skip_reason' => 'Cannot connect to database',
                ];
                DB::purge('tenant');
                continue;
            }

            $ran = collect();
            try {
                if (Schema::connection('tenant')->hasTable('migrations')) {
                    $ran = DB::connection('tenant')->table('migrations')->pluck('migration');
                }
            } catch (\Throwable $e) {
                // migrations table may not exist yet — treat as zero ran
            }

            $pending = $allFiles->diff($ran)->values();

            $result[] = [
                'id'          => $tenant->id,
                'name'        => $tenant->business_name,
                'database'    => $database,
                'pending'     => $pending,
                'skipped'     => false,
                'skip_reason' => null,
            ];

            DB::purge('tenant');
        }

        $totalPending = collect($result)->sum(fn($t) => count($t['pending']));

        return response()->json([
            'tenants'       => $result,
            'total_files'   => $totalFiles,
            'total_pending' => $totalPending,
        ]);
    }

    /**
     * Runs ONE pending migration for a specific tenant (global runner).
     * Identical self-healing logic as runNextMigration, same fix applied.
     */
    public function runNextMigrationForTenant($tenantId)
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        $database = $tenant->data;

        if (empty($database) || !preg_match('/^[a-zA-Z0-9_-]+$/', $database)) {
            return response()->json(['error' => 'Invalid database config'], 409);
        }

        if (!$this->connectAsTenant($tenant, $database)) {
            return response()->json(['error' => 'Cannot connect to tenant database'], 409);
        }

        try {
            $exitCode = Artisan::call('migrate', [
                '--database' => 'tenant',
                '--force'    => true,
                '--path'     => 'database/migrations/tenant',
                '--step'     => true,
            ]);

            $output = trim(Artisan::output());
            DB::purge('tenant');

            if ($exitCode === 0) {
                return response()->json(['success' => true, 'output' => $output]);
            }

            return response()->json(['error' => 'Migration step failed', 'output' => $output], 500);

        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            Log::error("Global migration exception tenant {$tenant->id}: {$errorMsg}");

            if ($this->isTableAlreadyExistsError($e)) {
                $tableName     = $this->extractTableNameFromError($errorMsg);
                $migrationName = $this->detectCurrentMigrationName($tenant, $database);

                if ($tableName && $migrationName) {
                    try {
                        // ✅ FIX: same return-value check as runNextMigration().
                        if (!$this->connectAsTenant($tenant, $database)) {
                            Log::error("Global self-heal aborted for tenant {$tenant->id}: could not reconnect");
                        } else {
                            if (Schema::connection('tenant')->hasTable($tableName)) {
                                $alreadyRecorded = DB::connection('tenant')
                                    ->table('migrations')
                                    ->where('migration', $migrationName)
                                    ->exists();

                                if (!$alreadyRecorded) {
                                    $maxBatch = DB::connection('tenant')->table('migrations')->max('batch') ?? 0;
                                    DB::connection('tenant')->table('migrations')->insert([
                                        'migration' => $migrationName,
                                        'batch'     => $maxBatch + 1,
                                    ]);
                                }

                                DB::purge('tenant');
                                return response()->json([
                                    'success' => true,
                                    'healed'  => true,
                                    'output'  => "Table '{$tableName}' already existed — marked as complete.",
                                ]);
                            }
                        }
                    } catch (\Throwable $healEx) {
                        Log::error("Global self-heal failed tenant {$tenant->id}: " . $healEx->getMessage());
                    }
                }
            }

            DB::purge('tenant');
            return response()->json([
                'error'  => $errorMsg,
                'output' => basename($e->getFile()) . ':' . $e->getLine(),
            ], 500);
        }
    }
}