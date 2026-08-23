<?php
namespace App\Http\Controllers\Master;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Tenant;
use Illuminate\Support\Facades\Schema;

class TenantMigrationController extends Controller
{
    /**
     * Per-request time budget for batch migration endpoints, in seconds.
     * Kept comfortably under PHP's max_execution_time (60s) so we always
     * return a clean JSON response instead of getting killed mid-CREATE TABLE.
     */
    private const BATCH_BUDGET_SECONDS = 45;

    /**
     * How long a per-tenant migration lock is held before it auto-expires,
     * in seconds. Must stay above the extended time limit (see
     * extendTimeLimit()) so a genuinely slow-but-not-stuck step can never
     * outlive its own lock and get its lock stolen by another request —
     * that would recreate the exact overlapping-request problem this lock
     * exists to prevent.
     */
    private const LOCK_TTL_SECONDS = 180;

    /**
     * How long a request will wait to acquire a tenant's migration lock
     * before giving up and returning 409. Short on purpose — if another
     * request is already migrating this tenant, we want to fail fast and
     * let the frontend retry, not queue up behind it.
     */
    private const LOCK_WAIT_SECONDS = 5;

    /**
     * Server-side cap (seconds) on how long MySQL will let a single
     * statement wait on a metadata/row lock before erroring out. Without
     * this, a CREATE TABLE blocked behind a stale/orphaned connection
     * (e.g. one left dangling by a previous PHP max_execution_time kill)
     * can hang silently until PHP's own 60s timeout kills the script with
     * an uncatchable fatal error — no exception, no diagnostics, just a
     * bare HTTP 500. Bounding it here turns that into a normal, catchable
     * PDOException well before PHP's hard limit, so runOneMigrationStep()
     * can actually see it, log it, and let self-heal do its job.
     */
    private const LOCK_WAIT_TIMEOUT_SECONDS = 10;

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

            // Bound how long MySQL will let a statement wait on a lock.
            // Without this, a hung CREATE TABLE/INSERT rides PHP's blunt
            // max_execution_time to a silent, uncatchable death instead of
            // surfacing as a normal exception we can log and self-heal from.
            // Non-fatal if the host restricts SET SESSION — we still tried.
            try {
                DB::connection('tenant')->statement(
                    'SET SESSION lock_wait_timeout = ' . self::LOCK_WAIT_TIMEOUT_SECONDS
                );
                DB::connection('tenant')->statement(
                    'SET SESSION innodb_lock_wait_timeout = ' . self::LOCK_WAIT_TIMEOUT_SECONDS
                );
            } catch (\Throwable $e) {
                Log::warning("Could not set lock_wait_timeout for tenant {$tenant->id}: " . $e->getMessage());
            }

            return true;
        } catch (\Exception $e) {
            Log::warning("Failed to connect as tenant {$tenant->id} ({$database}): " . $e->getMessage());
            DB::purge('tenant');
            return false;
        }
    }

    /**
     * Acquires a short-lived, per-tenant lock so only one request at a time
     * can run migration steps against a given tenant's database. Returns
     * the lock on success, or null if another request already holds it —
     * callers should return a 409 in that case rather than proceeding.
     */
    /**
     * Extends THIS request's execution time limit beyond PHP's default 60s,
     * without touching php.ini globally. Needed because a step can be
     * genuinely slow rather than stuck — most commonly the very first
     * CREATE TABLE against a brand-new database on Windows dev machines,
     * where real-time antivirus scanning intercepts new InnoDB file
     * creation and can add tens of seconds to just the first table or two.
     * That's disk/OS-level, not a MySQL lock, so lock_wait_timeout doesn't
     * help — the only fix here is giving the slow-but-not-stuck step
     * enough runway to actually finish and get recorded normally, instead
     * of dying mid-CREATE-TABLE with an uncatchable fatal error.
     *
     * If this keeps happening, the real fix is excluding your MySQL data
     * directory from real-time antivirus scanning — this is a mitigation,
     * not a replacement for that.
     */
    private function extendTimeLimit(int $seconds = 150): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit($seconds);
        }
    }

    private function acquireTenantLock(int $tenantId)
    {
        $lock = Cache::lock("tenant-migrate-lock:{$tenantId}", self::LOCK_TTL_SECONDS);

        try {
            // block() throws LockTimeoutException (not a bool) if the lock
            // isn't free within LOCK_WAIT_SECONDS.
            $lock->block(self::LOCK_WAIT_SECONDS);
            return $lock;
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            return null;
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

        $allFiles = $this->getTenantMigrationFiles();

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
     * Runs ONE pending migration with --step. Self-heals "table already
     * exists" by checking whether the table is actually there and, if so,
     * recording the migration as complete instead of failing.
     */
    public function runNextMigration($tenantId)
    {
        return $this->runSingleStepEndpoint($tenantId, "Migration step failed for tenant");
    }

    /**
     * Identical to runNextMigration — kept as a separate route for the
     * global runner UI, same underlying logic.
     */
    public function runNextMigrationForTenant($tenantId)
    {
        return $this->runSingleStepEndpoint($tenantId, "Global migration exception tenant");
    }

    private function runSingleStepEndpoint($tenantId, string $logPrefix)
    {
        $this->extendTimeLimit();

        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return response()->json(['error' => 'Tenant not found'], 404);
        }

        $database = $tenant->data;

        if (empty($database) || !preg_match('/^[a-zA-Z0-9_-]+$/', $database)) {
            return response()->json(['error' => 'Invalid database config'], 409);
        }

        $lock = $this->acquireTenantLock((int) $tenantId);
        if (!$lock) {
            return response()->json([
                'error' => 'Another migration step is already running for this tenant. Please wait a moment and try again.',
            ], 409);
        }

        try {
            if (!$this->connectAsTenant($tenant, $database)) {
                return response()->json(['error' => 'Cannot connect to tenant database'], 409);
            }

            $step = $this->runOneMigrationStep($tenant, $database, $logPrefix);

            if ($step['status'] === 'failed') {
                return response()->json(['error' => $step['error'], 'output' => $step['output'] ?? null], 500);
            }

            return response()->json([
                'success' => true,
                'healed'  => $step['status'] === 'healed',
                'done'    => $step['status'] === 'done',
                'output'  => $step['output'] ?? null,
            ]);
        } finally {
            $lock->release();
        }
    }

    /**
     * Runs migrations for one tenant in a loop, bounded by a time budget so
     * we always return well before PHP's max_execution_time kills the
     * request. Returns as soon as either everything is migrated or the
     * budget runs out — callers check `done` and re-invoke if not finished.
     */
    private function runMigrationBatch(Tenant $tenant, string $database, int $budgetSeconds): array
    {
        $startTime = microtime(true);
        $processed = 0;
        $healed    = 0;

        while (microtime(true) - $startTime < $budgetSeconds) {
            if (!$this->connectAsTenant($tenant, $database)) {
                return [
                    'success' => false, 'error' => 'Cannot connect to tenant database',
                    'processed' => $processed, 'healed' => $healed, 'done' => false,
                ];
            }

            $step = $this->runOneMigrationStep($tenant, $database, 'Batch migration exception for tenant');

            if ($step['status'] === 'failed') {
                return [
                    'success' => false, 'error' => $step['error'], 'output' => $step['output'] ?? null,
                    'processed' => $processed, 'healed' => $healed, 'done' => false,
                ];
            }

            if ($step['status'] === 'done') {
                return ['success' => true, 'processed' => $processed, 'healed' => $healed, 'done' => true];
            }

            if ($step['status'] === 'healed') {
                $healed++;
            } else {
                $processed++;
            }
        }

        // Budget exhausted, more may still be pending.
        return ['success' => true, 'processed' => $processed, 'healed' => $healed, 'done' => false];
    }

    /**
     * Runs exactly one `migrate --step` call and classifies the outcome:
     * 'done' (nothing left to migrate), 'stepped' (one migration ran),
     * 'healed' (a duplicate table was detected and recorded), or 'failed'.
     *
     * On failure, the response now always carries the FULL raw error text
     * (not a generic "Migration step failed") plus how long the step ran
     * before failing, plus a SHOW PROCESSLIST / InnoDB lock dump — so a
     * 60s hang shows up as elapsed≈60 with the connection that was blocking
     * it, instead of surfacing as a bare "HTTP 500" with nothing to paste.
     */
    private function runOneMigrationStep(Tenant $tenant, string $database, string $logPrefix): array
    {
        $startedAt = microtime(true);

        try {
            $exitCode = Artisan::call('migrate', [
                '--database' => 'tenant',
                '--force'    => true,
                '--path'     => 'database/migrations/tenant',
                '--step'     => true,
            ]);

            $elapsed = round(microtime(true) - $startedAt, 2);
            $output  = trim(Artisan::output());
            DB::purge('tenant');

            if ($exitCode !== 0) {
                $diagnostics = $this->captureLockDiagnostics($tenant, $database);
                $fullOutput  = "{$output}\n\n[step took {$elapsed}s before failing]\n\n{$diagnostics}";

                Log::error("{$logPrefix} {$tenant->id}: {$fullOutput}");

                return [
                    'status' => 'failed',
                    'error'  => $output !== '' ? $output : "Migration step failed after {$elapsed}s (no output captured)",
                    'output' => $fullOutput,
                ];
            }

            if ($output === '' || str_contains(strtolower($output), 'nothing to migrate')) {
                return ['status' => 'done'];
            }

            return ['status' => 'stepped', 'output' => $output];

        } catch (\Throwable $e) {
            $elapsed  = round(microtime(true) - $startedAt, 2);
            $errorMsg = $e->getMessage();
            Log::error("{$logPrefix} {$tenant->id} (after {$elapsed}s): {$errorMsg}");

            if ($this->isTableAlreadyExistsError($e)) {
                // One reconnect here covers the whole self-heal — detectCurrentMigrationName()
                // and healDuplicateTable() reuse this same connection instead of each
                // opening their own, which used to mean 3 separate round trips to a
                // remote DB host (slow on shared hosting) for a single self-heal.
                if ($this->connectAsTenant($tenant, $database) && $this->healDuplicateTable($tenant, $errorMsg)) {
                    return ['status' => 'healed'];
                }
            }

            $diagnostics = $this->captureLockDiagnostics($tenant, $database);
            DB::purge('tenant');

            return [
                'status' => 'failed',
                'error'  => $errorMsg,
                'output' => "{$errorMsg}\n\n[failed after {$elapsed}s, at "
                    . basename($e->getFile()) . ':' . $e->getLine() . "]\n\n{$diagnostics}",
            ];
        }
    }

    /**
     * Dumps SHOW PROCESSLIST (filtered to this tenant's DB) and the InnoDB
     * lock-wait section of SHOW ENGINE INNODB STATUS. Called right when a
     * step fails, so a copy-pasteable snapshot of whatever was blocking the
     * connection ends up in both the log and the JSON response — no need
     * to manually run these by hand while trying to catch the hang live.
     */
    private function captureLockDiagnostics(Tenant $tenant, string $database): string
    {
        $lines = ["── Lock diagnostics for tenant {$tenant->id} ({$database}) ──"];

        try {
            if (!$this->connectAsTenant($tenant, $database)) {
                $lines[] = '(could not reconnect to capture diagnostics)';
                return implode("\n", $lines);
            }

            $processes = DB::connection('tenant')->select('SHOW FULL PROCESSLIST');
            $relevant  = array_filter($processes, fn($p) => ($p->db ?? null) === $database);

            if (empty($relevant)) {
                $lines[] = 'SHOW PROCESSLIST: no connections currently open against this database.';
            } else {
                $lines[] = 'SHOW PROCESSLIST (this database):';
                foreach ($relevant as $p) {
                    $lines[] = sprintf(
                        '  id=%s user=%s host=%s command=%s time=%ss state="%s" info="%s"',
                        $p->Id ?? $p->id ?? '?',
                        $p->User ?? $p->user ?? '?',
                        $p->Host ?? $p->host ?? '?',
                        $p->Command ?? $p->command ?? '?',
                        $p->Time ?? $p->time ?? '?',
                        $p->State ?? $p->state ?? '',
                        mb_strimwidth((string) ($p->Info ?? $p->info ?? ''), 0, 200, '…')
                    );
                }
            }

            try {
                $status = DB::connection('tenant')->select('SHOW ENGINE INNODB STATUS');
                $text   = $status[0]->Status ?? '';
                if (preg_match('/-*\s*TRANSACTIONS\s*-*.*?(?=-{2,}\s*[A-Z ]+-{2,})/s', $text, $m)) {
                    $lines[] = 'SHOW ENGINE INNODB STATUS (TRANSACTIONS section, truncated):';
                    $lines[] = mb_strimwidth(trim($m[0]), 0, 2000, '… [truncated]');
                }
            } catch (\Throwable $innoEx) {
                $lines[] = '(SHOW ENGINE INNODB STATUS unavailable: ' . $innoEx->getMessage() . ')';
            }

            DB::purge('tenant');
        } catch (\Throwable $diagEx) {
            $lines[] = '(diagnostics capture failed: ' . $diagEx->getMessage() . ')';
        }

        return implode("\n", $lines);
    }

    /**
     * Self-healing: if a migration failed because its table already exists,
     * check whether the table is genuinely there. If so, the CREATE TABLE
     * physically succeeded on a previous (likely timed-out) run but the
     * migrations record was never written — write it now so the batch can
     * continue past it instead of looping on the same table forever.
     */
    private function healDuplicateTable(Tenant $tenant, string $errorMsg): bool
    {
        $tableName     = $this->extractTableNameFromError($errorMsg);
        $migrationName = $this->detectCurrentMigrationName();

        if (!$tableName || !$migrationName) {
            return false;
        }

        try {
            if (!Schema::connection('tenant')->hasTable($tableName)) {
                return false;
            }

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
            return true;
        } catch (\Throwable $healEx) {
            Log::error("Self-heal failed for tenant {$tenant->id}: " . $healEx->getMessage());
            DB::purge('tenant');
            return false;
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
     * so we know which record to insert into the migrations table. Assumes
     * the 'tenant' connection is already live (caller connects once before
     * kicking off self-heal) — no reconnect here.
     */
    private function detectCurrentMigrationName(): ?string
    {
        try {
            $allFiles = $this->getTenantMigrationFiles();

            $ran = collect();
            if (Schema::connection('tenant')->hasTable('migrations')) {
                $ran = DB::connection('tenant')->table('migrations')->pluck('migration');
            }

            return $allFiles->diff($ran)->values()->first();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function getTenantMigrationFiles()
    {
        $migrationPath = database_path('migrations/tenant');
        return collect(File::files($migrationPath))
            ->map(fn($f) => $f->getFilenameWithoutExtension())
            ->sort()
            ->values();
    }

    /**
     * Runs all pending migrations for one tenant, budgeted so it always
     * returns before PHP's execution timeout. If not everything got
     * migrated in this call, `done` is false — call again to continue
     * (progress is durable via the migrations table, nothing is lost
     * between calls).
     */
    public function executePendingMigrations($tenantId)
    {
        $this->extendTimeLimit();

        $tenant = Tenant::find($tenantId);
        if (!$tenant) return response()->json(['error' => 'Tenant not found', 'status' => 409]);

        $database = $tenant->data;
        if (empty($database)) return response()->json(['error' => 'No database name configured', 'status' => 409]);
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $database)) return response()->json(['error' => 'Invalid database name', 'status' => 409]);

        $lock = $this->acquireTenantLock((int) $tenantId);
        if (!$lock) {
            return response()->json([
                'error'  => 'Another migration step is already running for this tenant. Please wait a moment and try again.',
                'status' => 409,
            ]);
        }

        try {
            if (!$this->connectAsTenant($tenant, $database)) return response()->json(['error' => 'Cannot connect to tenant database', 'status' => 409]);

            $result = $this->runMigrationBatch($tenant, $database, self::BATCH_BUDGET_SECONDS);

            if (!$result['success']) {
                Log::warning("Migration failed for tenant {$tenant->id}: " . ($result['output'] ?? $result['error']));
                return response()->json([
                    'error'     => $result['error'] ?? 'Migration failed',
                    'output'    => $result['output'] ?? null,
                    'processed' => $result['processed'],
                    'healed'    => $result['healed'],
                    'status'    => 409,
                ]);
            }

            return response()->json([
                'success'   => true,
                'done'      => $result['done'],
                'processed' => $result['processed'],
                'healed'    => $result['healed'],
                'message'   => $result['done']
                    ? 'Migrations executed successfully'
                    : "Processed {$result['processed']} migration(s) this batch — call again to continue",
                'status'    => $result['done'] ? 201 : 202,
            ]);
        } finally {
            $lock->release();
        }
    }

    /**
     * Drops every table in the tenant's database and stops there — a plain
     * DROP TABLE loop is fast even for dozens of tables, so this always
     * completes well inside the request timeout. Re-migrating from empty
     * is deliberately NOT done here: the frontend reuses the same
     * one-migration-per-request loop it already uses for the normal "Run"
     * button (runNextMigration), which is what actually keeps every step
     * clear of PHP's execution limit. Duplicating a batch loop here would
     * just recreate the exact failure mode this was built to avoid.
     */
    public function resetTenantDatabaseCompletely($tenantId)
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) return response()->json(['error' => 'Tenant not found', 'status' => 409]);

        $database = $tenant->data;
        if (empty($database)) return response()->json(['error' => 'No database name configured', 'status' => 409]);
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $database)) return response()->json(['error' => 'Invalid database name', 'status' => 409]);

        $lock = $this->acquireTenantLock((int) $tenantId);
        if (!$lock) {
            return response()->json([
                'error'  => 'A migration step is already running for this tenant. Please wait a moment and try again.',
                'status' => 409,
            ]);
        }

        try {
            if (!$this->connectAsTenant($tenant, $database)) return response()->json(['error' => 'Cannot connect to tenant database', 'status' => 409]);

            try {
                DB::connection('tenant')->statement('SET FOREIGN_KEY_CHECKS=0');
                $tables = DB::connection('tenant')->select('SHOW TABLES');
                $key    = "Tables_in_{$database}";

                foreach ($tables as $table) {
                    $tableName = $table->$key;
                    DB::connection('tenant')->statement("DROP TABLE IF EXISTS `{$tableName}`");
                }

                DB::connection('tenant')->statement('SET FOREIGN_KEY_CHECKS=1');
            } catch (\Throwable $e) {
                DB::purge('tenant');
                Log::error("Failed to drop tables for tenant {$tenant->id}: " . $e->getMessage());
                return response()->json(['error' => 'Failed to clear database', 'status' => 409]);
            }

            DB::purge('tenant');

            return response()->json([
                'success' => true,
                'message' => 'All tables dropped — re-run pending migrations to rebuild the schema.',
                'status'  => 200,
            ]);
        } finally {
            $lock->release();
        }
    }

    /**
     * Runs pending migrations across all tenants, budgeted overall so the
     * request always returns before the execution timeout. Tenants not
     * fully migrated within the budget are reported in `incomplete` —
     * call again to pick up where it left off.
     */
    public function runPendingForAll(Request $request)
    {
        $this->extendTimeLimit();

        $total = Tenant::count();
        if ($total === 0) {
            return response()->json([
                'success' => true, 'message' => 'No tenants found', 'done' => true,
                'processed' => 0, 'skipped' => 0, 'failed' => 0, 'incomplete' => [], 'total' => 0, 'status' => 200,
            ]);
        }

        $startTime = microtime(true);
        $processed = $skipped = $failed = 0;
        $incomplete = [];

        foreach (Tenant::orderBy('id')->cursor() as $tenant) {
            $elapsed = microtime(true) - $startTime;

            if ($elapsed >= self::BATCH_BUDGET_SECONDS) {
                $incomplete[] = $tenant->id;
                continue;
            }

            $database = $tenant->data;
            if (empty($database) || !preg_match('/^[a-zA-Z0-9_-]+$/', $database)) { $skipped++; continue; }

            // Non-blocking: if a single-step click or another global run is
            // already migrating this tenant, defer it instead of queuing
            // behind the same DB lock (which is what produced the
            // timeout → "already exists" pairs seen in the logs).
            $lock = Cache::lock("tenant-migrate-lock:{$tenant->id}", self::LOCK_TTL_SECONDS);
            if (!$lock->get()) {
                $incomplete[] = $tenant->id;
                continue;
            }

            try {
                if (!$this->connectAsTenant($tenant, $database)) { $skipped++; continue; }

                $remaining    = self::BATCH_BUDGET_SECONDS - $elapsed;
                $tenantBudget = (int) max(5, min(15, $remaining));

                $result = $this->runMigrationBatch($tenant, $database, $tenantBudget);

                if (!$result['success']) {
                    $failed++;
                    continue;
                }

                if ($result['done']) {
                    $processed++;
                } else {
                    $incomplete[] = $tenant->id;
                }
            } finally {
                $lock->release();
            }
        }

        $done = empty($incomplete);

        $message = match (true) {
            $failed > 0 => "Completed with {$failed} failure(s), {$skipped} skipped, {$processed} fully migrated"
                . (!$done ? ', ' . count($incomplete) . ' still pending' : ''),
            !$done => "{$processed} fully migrated, " . count($incomplete) . ' still pending — call again to continue',
            $skipped > 0 => "{$skipped} skipped, {$processed} fully migrated",
            default => "All {$processed} tenants migrated",
        };

        return response()->json([
            'success'    => $failed === 0,
            'message'    => $message,
            'processed'  => $processed,
            'skipped'    => $skipped,
            'failed'     => $failed,
            'incomplete' => $incomplete,
            'done'       => $done,
            'total'      => $total,
            'status'     => $failed === 0 ? ($done ? 200 : 202) : 207,
        ]);
    }

    /**
     * Returns all tenants with their pending migration counts.
     * Used by the global runner to build the work queue.
     */
    public function getGlobalPendingList()
    {
        $allFiles   = $this->getTenantMigrationFiles();
        $totalFiles = $allFiles->count();
        $tenants    = Tenant::orderBy('id')->get();
        $result     = [];

        foreach ($tenants as $tenant) {
            $database = $tenant->data;

            if (empty($database) || !preg_match('/^[a-zA-Z0-9_-]+$/', $database)) {
                $result[] = [
                    'id' => $tenant->id, 'name' => $tenant->business_name, 'database' => $database ?? 'N/A',
                    'pending' => [], 'skipped' => true, 'skip_reason' => 'Invalid or missing database name',
                ];
                continue;
            }

            if (!$this->connectAsTenant($tenant, $database)) {
                $result[] = [
                    'id' => $tenant->id, 'name' => $tenant->business_name, 'database' => $database,
                    'pending' => [], 'skipped' => true, 'skip_reason' => 'Cannot connect to database',
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
                'id' => $tenant->id, 'name' => $tenant->business_name, 'database' => $database,
                'pending' => $pending, 'skipped' => false, 'skip_reason' => null,
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
}