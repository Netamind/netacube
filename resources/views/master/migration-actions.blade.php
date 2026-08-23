{{-- resources/views/master/migration-actions.blade.php --}}
@extends('master.dashboard')
@section('content')
<style>
    .card-header {
        padding: 0.5rem 1.5rem !important;
        background: linear-gradient(to right, #4B5EBD, #576CC0);
        color: #fff;
    }
    .card-header .btn-light {
        height: 28px; width: 36px; padding: 0 10px;
        display: flex; align-items: center; justify-content: center;
        line-height: 1; border-radius: 6px;
    }
    .card-header .btn-light:hover { background-color: #f8f9fa !important; }
    .card-header .btn-light:hover i { color: #0d6efd !important; }
    .card { border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden; }
    .card-body { padding: 1.8rem !important; }

    .info-table {
        width: 100%; background: transparent; border-radius: 10px; overflow: hidden;
        margin-bottom: 0; border-collapse: separate; border-spacing: 0;
    }
    .info-table td { padding: 1rem 1.2rem; vertical-align: middle; }
    .info-table td:first-child {
        background: #e9ecef; font-weight: 600; color: #212529;
        width: 280px; border: none !important;
    }
    .info-table td:last-child {
        background: #f1f3f5; color: #495057; border-bottom: 1px solid #ced4da;
    }
    .info-table tr:last-child td:last-child { border-bottom: none; }
    .info-table .num { font-size: 1.5rem; font-weight: 700; }
    .danger-row td:first-child { background: #fee2e2 !important; color: #991b1b !important; font-weight: 700; }
    .danger-row td:last-child  { background: #f1f3f5 !important; border-bottom: 1px solid #ced4da !important; }

    /* Terminal log */
    #migrationLog {
        background: #0f172a; color: #94a3b8;
        font-family: 'Courier New', monospace; font-size: 0.78rem;
        border-radius: 8px; padding: 12px 14px;
        max-height: 200px; overflow-y: auto;
        text-align: left; line-height: 1.7; margin-top: 4px;
    }
    #migrationLog .log-ok   { color: #4ade80; }
    #migrationLog .log-err  { color: #f87171; }
    #migrationLog .log-warn { color: #fbbf24; }
    #migrationLog .log-heal { color: #a78bfa; }
    #migrationLog .log-info { color: #60a5fa; }

    #migrationProgressBar {
        font-size: 0.75rem; font-weight: 600;
        line-height: 24px; transition: width 0.35s ease; min-width: 2rem;
    }

    /* Skipped/failed summary table */
    #failureSummary { font-size: 0.8rem; }
    #failureSummary th { background: #1e293b; color: #94a3b8; padding: 6px 10px; }
    #failureSummary td { padding: 5px 10px; border-bottom: 1px solid #fee2e2; word-break: break-word; }
    #failureSummary tr:last-child td { border-bottom: none; }
</style>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">

            <?php
                use Illuminate\Support\Facades\DB;
                use Illuminate\Support\Facades\File;

                $intendedDbName  = $tenant->data ?? 'tenant_' . $tenant->id;
                $migrationPath   = database_path('migrations/tenant');

                // File names on disk right now — this is the single source of truth
                // for "what migrations exist". Everything else is compared against it.
                $allFileNames = collect();
                if (is_dir($migrationPath)) {
                    $allFileNames = collect(File::files($migrationPath))
                        ->map(fn($f) => $f->getFilenameWithoutExtension())
                        ->values();
                }
                $totalMigrations = $allFileNames->count();
                $isLocal         = app()->environment('local');

                $dbExists = false; $dbNameToShow = 'N/A'; $migrated = 0; $pending = $totalMigrations;

                try {
                    if ($isLocal) {
                        config(['database.connections.tenant.database' => $intendedDbName]);
                    } else {
                        // ✅ FIX: read the same env-backed password the controller uses,
                        // instead of the old hardcoded 'binto2020'. Keeping these in sync
                        // matters — if they ever diverge, this page can show different
                        // migrated/pending counts than what the actual migration runner sees.
                        $tenantPassword = config('database.tenant_db_password') ?? env('TENANT_DB_PASSWORD');

                        config([
                            'database.connections.tenant.host'     => env('TENANT_DB_HOST', config('database.connections.mysql.host')),
                            'database.connections.tenant.database' => $intendedDbName,
                            'database.connections.tenant.username' => $tenant->db_user,
                            'database.connections.tenant.password' => $tenantPassword,
                        ]);
                    }
                    DB::purge('tenant');
                    DB::connection('tenant')->getPdo();
                    $dbExists     = true;
                    $dbNameToShow = $intendedDbName;

                    // ✅ FIX: "Migrated" used to be a raw COUNT(*) on the migrations table,
                    // which can include rows for migration files that were later renamed
                    // or deleted from disk (or rows inserted by self-healing against a
                    // stale/wrong migration name). That let Migrated exceed Total Migrations,
                    // producing a negative Pending. We now only count rows whose migration
                    // name still matches a file that actually exists on disk right now —
                    // this can never exceed Total Migrations, so Pending can never go negative.
                    if (DB::connection('tenant')->getSchemaBuilder()->hasTable('migrations')) {
                        $ranNames = DB::connection('tenant')->table('migrations')->pluck('migration');
                        $migrated = $allFileNames->intersect($ranNames)->count();
                    } else {
                        $migrated = 0;
                    }
                    $pending = max(0, $totalMigrations - $migrated);
                } catch (\Exception $e) {
                    $dbExists = false; $migrated = 0; $pending = $totalMigrations;
                }
                DB::purge('tenant');
            ?>

            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title mb-0">
                        <i class="ri-database-2-line me-2 fs-22"></i>Migration Actions
                    </h4>
                    <div class="d-flex align-items-center">
                        <a href="#" class="btn btn-light text-primary fs-16 mx-1"
                           data-bs-toggle="modal" data-bs-target="#infoModal" title="Info">
                            <i class="ri-information-line"></i>
                        </a>
                        <a href="{{ route('master.tenant.migrations') }}"
                           class="btn btn-light text-primary fs-16 mx-1" title="Back">
                            <i class="ri-arrow-left-line"></i>
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <table class="info-table">
                        <tr>
                            <td>Tenant</td>
                            <td><strong>{{ $tenant->business_name }}</strong></td>
                        </tr>
                        <tr>
                            <td>Database</td>
                            <td>{{ $dbExists ? $dbNameToShow : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <td>Total Migrations</td>
                            <td><span class="num text-primary">{{ $totalMigrations }}</span></td>
                        </tr>
                        <tr>
                            <td>Pending Migrations</td>
                            <td>
                                <span class="num {{ $pending > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $pending }}
                                </span>
                                @if($dbExists && $pending > 0)
                                    &nbsp;&nbsp;
                                    <button class="btn btn-success btn-sm"
                                            data-bs-toggle="modal" data-bs-target="#runModal">
                                        <i class="ri-play-circle-line"></i> Run
                                    </button>
                                @elseif(!$dbExists)
                                    &nbsp;&nbsp;
                                    <button class="btn btn-outline-secondary btn-sm" disabled>
                                        <i class="ri-play-circle-line"></i> Run
                                    </button>
                                    <small class="text-muted ms-2">(Approve tenant first)</small>
                                @endif
                            </td>
                        </tr>
                        <tr class="danger-row">
                            <td>Danger Zone</td>
                            <td>
                                <button class="btn btn-danger {{ $migrated > 0 ? '' : 'disabled' }}"
                                        data-bs-toggle="modal" data-bs-target="#refreshModal">
                                    <i class="ri-refresh-line"></i> Refresh tables
                                </button>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ══════════════════════ MODALS ══════════════════════ --}}

{{-- Info --}}
<div class="modal fade" id="infoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">About Migrations</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Run Now</strong>: Runs each pending migration one at a time. If a migration
                   fails it is skipped and the rest continue. A full summary is shown at the end.</p>
                <p class="mb-0"><strong>Refresh Tables</strong>: Drops <em>all</em> tables and re-runs
                   every migration. <span class="text-danger fw-bold">All data will be lost.</span></p>
            </div>
        </div>
    </div>
</div>

{{-- ── Run Pending Migrations ── --}}
<div class="modal fade" id="runModal" data-bs-backdrop="static" data-bs-keyboard="false"
     tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 540px; margin: 1.75rem auto;">
        <div class="modal-content">

            {{-- IDLE --}}
            <div class="modal-body text-center pb-4 pt-4" id="runModal-idle">
                <i class="ri-play-circle-line text-success" style="font-size: 70px;"></i>
                <h4 class="mt-3">Run Pending Migrations?</h4>
                <p class="mb-1">
                    You are about to run <strong>{{ $pending }}</strong> pending migration(s) for:
                </p>
                <p class="fw-bold mb-1">{{ $tenant->business_name }}</p>
                <p class="text-muted mb-1">{{ $dbExists ? $dbNameToShow : 'N/A' }}</p>
                <p class="text-muted small mb-4">
                    Migrations that fail will be skipped automatically.<br>
                    A summary of any failures will be shown at the end.
                </p>
                <button class="btn btn-success me-2" id="confirmRun" style="min-width: 140px;">
                    Yes, Run Now
                </button>
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal" style="min-width: 140px;">
                    Cancel
                </button>
            </div>

            {{-- RUNNING --}}
            <div class="modal-body pb-4 pt-3 d-none" id="runModal-progress">
                <h5 class="text-center mb-3 fw-bold" id="progressTitle">Running Migrations…</h5>

                <div class="progress mb-2" style="height: 24px; border-radius: 8px; background: #e2e8f0;">
                    <div id="migrationProgressBar"
                         class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                         role="progressbar" style="width: 0%;">0%</div>
                </div>

                <div class="d-flex justify-content-between text-muted small mb-2">
                    <span id="migrationCounter">0 / 0 migrations</span>
                    <span id="migrationStatus">Fetching pending list…</span>
                </div>

                <div id="migrationLog"></div>

                {{-- DONE (all succeeded) --}}
                <div id="runModal-done" class="text-center mt-3 d-none">
                    <i class="ri-checkbox-circle-line text-success" style="font-size: 48px;"></i>
                    <p class="fw-bold mt-2 mb-0" id="doneMessage">All migrations completed!</p>
                    <button class="btn btn-primary mt-3" onclick="location.reload()">
                        <i class="ri-refresh-line me-1"></i> Reload Page
                    </button>
                </div>

                {{-- DONE WITH SKIPS --}}
                <div id="runModal-partial" class="mt-3 d-none">
                    <div class="text-center mb-3">
                        <i class="ri-error-warning-line text-warning" style="font-size: 48px;"></i>
                        <p class="fw-bold mt-2 mb-1 text-warning" id="partialMessage">
                            Completed with some failures
                        </p>
                    </div>

                    <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px;
                                padding:10px 14px; margin-bottom:12px; font-size:0.82rem; color:#92400e;">
                        The migrations below were skipped due to errors. Everything else ran successfully.
                        You can fix the issues and re-run to apply the skipped ones.
                    </div>

                    <div style="overflow-x:auto; border-radius:8px; overflow:hidden;">
                        <table class="table table-sm mb-0" id="failureSummary">
                            <thead>
                                <tr>
                                    <th style="width:40%">Migration</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody id="failureTableBody"></tbody>
                        </table>
                    </div>

                    <div class="text-center mt-3">
                        <button class="btn btn-primary me-2" onclick="location.reload()">
                            <i class="ri-refresh-line me-1"></i> Reload Page
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- ── Danger: Refresh / Reset ── --}}
<div class="modal fade" id="refreshModal" data-bs-backdrop="static" data-bs-keyboard="false"
     tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 420px; margin: 1.75rem auto;">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">DANGER – PERMANENT DATA LOSS</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center pb-4 pt-4">
                <i class="ri-error-warning-line text-danger" style="font-size: 80px;"></i>
                <h4 class="mt-3 text-danger fw-bold">This will DELETE ALL DATA</h4>
                <p class="mb-1 fw-bold">for tenant:</p>
                <p class="fw-bold mb-2 text-primary fs-5">{{ $tenant->business_name }}</p>
                <p class="text-muted mb-4">{{ $dbExists ? $dbNameToShow : 'N/A' }}</p>
                <p class="text-danger small mb-3">
                    This action <strong>cannot be undone</strong>.<br>
                    All tables, records, users, transactions, etc. will be permanently deleted.
                </p>
                <p class="mb-2">To confirm, type exactly:</p>
                <p class="fw-bold text-danger mb-2">
                    YESDELETEDATAFOR{{ strtoupper(str_replace(' ', '', $tenant->business_name)) }}
                </p>
                <input type="text" class="form-control text-center mb-3"
                       id="confirmation-input" placeholder="Type the text above here" autocomplete="off">
                <div class="d-flex justify-content-center gap-3">
                    <button class="btn btn-danger" id="confirmRefreshFinal" disabled style="min-width: 160px;">
                        Confirm Delete
                    </button>
                    <button class="btn btn-outline-secondary" data-bs-dismiss="modal" style="min-width: 140px;">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {

    const pendingUrl = "{{ route('master.tenant.migrations.pending', $tenant->id) }}";
    const nextUrl    = "{{ route('master.tenant.migrations.next',   $tenant->id) }}";
    const csrfToken  = "{{ csrf_token() }}";

    // ─── Helpers ──────────────────────────────────────────────────────────────

    function logLine(msg, type = 'info') {
        const cls  = { ok:'log-ok', err:'log-err', warn:'log-warn', heal:'log-heal', info:'log-info' }[type] || 'log-info';
        const $log = $('#migrationLog');
        $log.append($('<div>').addClass(cls).text(msg));
        $log.scrollTop($log[0].scrollHeight);
    }

    function setProgress(done, total) {
        const pct = total > 0 ? Math.round((done / total) * 100) : 0;
        $('#migrationProgressBar').css('width', pct + '%').text(pct + '%');
        $('#migrationCounter').text(done + ' / ' + total + ' migrations');
    }

    function setStatus(msg) { $('#migrationStatus').text(msg); }

    function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

    function stopAnimation(color) {
        $('#migrationProgressBar')
            .removeClass('progress-bar-striped progress-bar-animated bg-success bg-warning bg-danger')
            .addClass(color);
    }

    // ─── Main runner ──────────────────────────────────────────────────────────
    //
    // Each migration is a separate POST so PHP execution time is never hit.
    // On error: log it, record it in the failures list, then CONTINUE to next.
    // At the end: if any failures exist show the partial-success summary panel.

    async function runMigrationsSequentially() {
        $('#runModal-idle').addClass('d-none');
        $('#runModal-progress').removeClass('d-none');

        // ── Phase 1: fetch pending list ───────────────────────────────────────
        let pendingList = [], totalAll = 0, alreadyRan = 0;

        try {
            setStatus('Fetching pending list…');
            logLine('Fetching list of pending migrations…', 'info');

            const res  = await fetch(pendingUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();

            if (!res.ok || data.error) throw new Error(data.error || 'HTTP ' + res.status);

            pendingList = data.pending;
            totalAll    = data.total;
            alreadyRan  = data.ran;

            if (pendingList.length === 0) {
                logLine('No pending migrations — already up to date.', 'ok');
                setProgress(totalAll, totalAll);
                setStatus('Up to date');
                stopAnimation('bg-success');
                $('#doneMessage').text('No pending migrations — database is already up to date.');
                $('#runModal-done').removeClass('d-none');
                return;
            }

            logLine('Found ' + pendingList.length + ' pending migration(s). Running…', 'info');
            setProgress(alreadyRan, totalAll);

        } catch (err) {
            logLine('Error: ' + err.message, 'err');
            stopAnimation('bg-danger');
            return;
        }

        // ── Phase 2: run one at a time, skip on failure ───────────────────────
        let doneCount = alreadyRan;
        const failures = []; // { name, reason }

        for (let i = 0; i < pendingList.length; i++) {
            const name    = pendingList[i];
            const stepNum = i + 1;

            setStatus('Running ' + stepNum + ' / ' + pendingList.length + '…');
            logLine('▶ [' + stepNum + '/' + pendingList.length + '] ' + name, 'info');

            try {
                const res = await fetch(nextUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type':     'application/json',
                        'X-CSRF-TOKEN':     csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ migration: name }),
                });

                let data = {};
                try { data = await res.json(); } catch (_) {}

                if (!res.ok || data.error) {
                    // ── SKIPPED (error) ──────────────────────────────────────
                    const reason = data.error || ('HTTP ' + res.status);
                    const detail = data.output || '';
                    logLine('  ✗ SKIPPED — ' + reason, 'err');
                    if (detail) logLine('    ' + detail, 'err');

                    failures.push({ name, reason: reason + (detail ? '\n' + detail : '') });

                    // Still advance the counter so progress bar keeps moving
                    doneCount++;
                    setProgress(doneCount, totalAll);

                } else {
                    // ── SUCCESS (or self-healed) ─────────────────────────────
                    doneCount++;
                    setProgress(doneCount, totalAll);

                    if (data.healed) {
                        logLine('  ✦ AUTO-FIXED — ' + (data.output || 'marked as complete'), 'heal');
                    } else {
                        const summary = (data.output || 'Done')
                            .split('\n').map(l => l.trim()).filter(Boolean).join(' | ');
                        logLine('  ✓ ' + summary, 'ok');
                    }
                }

            } catch (err) {
                // Network-level error — still skip and continue
                logLine('  ✗ SKIPPED — Network error: ' + err.message, 'err');
                failures.push({ name, reason: 'Network error: ' + err.message });
                doneCount++;
                setProgress(doneCount, totalAll);
            }

            await sleep(100);
        }

        // ── Phase 3: show final result ────────────────────────────────────────
        setStatus('Complete');
        logLine('', 'info');

        if (failures.length === 0) {
            logLine('✅  All ' + pendingList.length + ' migration(s) completed successfully.', 'ok');
            stopAnimation('bg-success');
            $('#doneMessage').text('All ' + pendingList.length + ' migration(s) ran successfully.');
            $('#runModal-done').removeClass('d-none');

        } else {
            const ok = pendingList.length - failures.length;
            logLine('⚠  Finished: ' + ok + ' succeeded, ' + failures.length + ' skipped.', 'warn');
            stopAnimation('bg-warning');

            // Populate failure summary table
            const $tbody = $('#failureTableBody').empty();
            failures.forEach(f => {
                const reasonHtml = $('<div>').text(f.reason).html().replace(/\n/g, '<br>');
                $tbody.append(
                    '<tr>' +
                    '<td style="color:#1e293b;font-weight:600">' + $('<div>').text(f.name).html() + '</td>' +
                    '<td style="color:#991b1b">' + reasonHtml + '</td>' +
                    '</tr>'
                );
            });

            $('#partialMessage').text(
                ok + ' migration(s) succeeded, ' + failures.length + ' skipped due to errors.'
            );
            $('#progressTitle').text('Completed with Skips');
            $('#runModal-partial').removeClass('d-none');
        }
    }

    // ─── Confirm Run ──────────────────────────────────────────────────────────
    $('#confirmRun').on('click', function (e) {
        e.preventDefault();
        $(this).prop('disabled', true);
        runMigrationsSequentially();
    });

    // Reset modal state on close
    $('#runModal').on('hidden.bs.modal', function () {
        $('#runModal-idle').removeClass('d-none');
        $('#runModal-progress').addClass('d-none');
        $('#runModal-done').addClass('d-none');
        $('#runModal-partial').addClass('d-none');
        $('#migrationLog').empty();
        $('#failureTableBody').empty();
        $('#migrationProgressBar')
            .css('width', '0%').text('0%')
            .removeClass('bg-danger bg-warning')
            .addClass('progress-bar-striped progress-bar-animated bg-success');
        $('#migrationCounter').text('0 / 0 migrations');
        $('#migrationStatus').text('Fetching pending list…');
        $('#progressTitle').text('Running Migrations…');
        $('#confirmRun').prop('disabled', false);
    });

    // ─── Refresh / Reset ──────────────────────────────────────────────────────
    const expected = "YESDELETEDATAFOR" + "{{ strtoupper(str_replace(' ', '', $tenant->business_name)) }}";

    $('#refreshModal').on('shown.bs.modal', function () {
        $('#confirmation-input').val('').focus();
        $('#confirmRefreshFinal').prop('disabled', true);
    });

    $('#confirmation-input').on('input', function () {
        $('#confirmRefreshFinal').prop('disabled', $(this).val().trim().toUpperCase() !== expected);
    });

    $('#confirmRefreshFinal').on('click', function (e) {
        e.preventDefault();
        const self = $(this);
        self.prop('disabled', true).text('Deleting…');

        $.ajax({
            type: 'POST',
            url:  "{{ route('master.tenant.migrations.reset', $tenant->id) }}",
            data: { _token: csrfToken, confirmation: $('#confirmation-input').val().trim() },
            timeout: 30000, // just a DROP TABLE loop now — should never take long
            complete: function () { self.prop('disabled', false).text('Confirm Delete'); },
            success:  function (data) {
                if (data.success) {
                    toastr.success('All tables dropped. Rebuilding schema…', 'Success');
                    $('#refreshModal').modal('hide');
                    // Reuse the exact same one-migration-per-request loop the
                    // "Run" button uses — this is what actually keeps every
                    // step clear of PHP's execution timeout. The reset
                    // endpoint's job was only ever to clear the tables.
                    $('#runModal').modal('show');
                    runMigrationsSequentially();
                } else {
                    toastr.error(data.error || 'Reset failed', 'Error');
                    $('#refreshModal').modal('hide');
                }
            },
            error: function (xhr, status) {
                const msg = status === 'timeout'  ? 'Request timed out.'
                          : xhr.status === 419    ? 'Session expired — refresh the page.'
                          : 'Unexpected error (' + xhr.status + ').';
                toastr.error(msg, 'Error');
                $('#refreshModal').modal('hide');
            },
        });
    });
});
</script>
@endsection