{{-- resources/views/master/global-migrations.blade.php --}}
@extends('master.dashboard')

@section('content')
<style>
    .card-header {
        padding: 0.5rem 1.5rem !important;
        background: linear-gradient(to right, #4B5EBD, #576CC0);
        color: #fff;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .card-header > * { flex-shrink: 0; }
    .card-body { padding: 0 1.5rem 1.5rem 1.5rem !important; }
    .card-header .btn-light {
        height: 28px; padding: 0 10px;
        display: flex; align-items: center; justify-content: center; line-height: 1;
    }
    .card-header .btn-light:hover { background-color: #f8f9fa; transition: background-color .2s; }
    .card { border: none; box-shadow: 0 4px 8px rgba(0,0,0,.1); border-radius: 10px; }
    .card-header h4 { color: #fff; font-weight: 600; margin-bottom: 0; display: flex; align-items: center; }
    .action-btn {
        min-width: 220px; font-size: 0.95rem;
        display: flex; align-items: center; justify-content: center;
        gap: 6px; padding: 0.5rem 1rem;
    }

    /* ── Terminal log ── */
    #globalMigrationLog {
        background: #0f172a; color: #94a3b8;
        font-family: 'Courier New', monospace; font-size: 0.76rem;
        border-radius: 8px; padding: 12px 14px;
        max-height: 260px; overflow-y: auto;
        text-align: left; line-height: 1.7; margin-top: 4px;
    }
    #globalMigrationLog .log-ok     { color: #4ade80; }
    #globalMigrationLog .log-err    { color: #f87171; }
    #globalMigrationLog .log-warn   { color: #fbbf24; }
    #globalMigrationLog .log-heal   { color: #a78bfa; }
    #globalMigrationLog .log-info   { color: #60a5fa; }
    #globalMigrationLog .log-tenant { color: #38bdf8; font-weight: bold; }

    /* ── Progress bars ── */
    #globalProgressBar, #tenantProgressBar {
        font-size: 0.72rem; font-weight: 600;
        line-height: 20px; transition: width 0.3s ease; min-width: 2rem;
    }

    /* ── Failure summary ── */
    #globalFailureSummary th { background: #1e293b; color: #94a3b8; padding: 6px 10px; font-size: 0.78rem; }
    #globalFailureSummary td { padding: 5px 10px; border-bottom: 1px solid #fee2e2;
                               font-size: 0.78rem; word-break: break-word; }
    #globalFailureSummary tr:last-child td { border-bottom: none; }

    /* ── Tenant progress list ── */
    #tenantProgressList { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }
    #tenantProgressList.expanded { max-height: 400px; overflow-y: auto; }
    .tenant-row { display: flex; align-items: center; gap: 10px;
                  padding: 4px 0; border-bottom: 1px solid #f1f5f9; font-size: 0.8rem; }
    .tenant-row:last-child { border-bottom: none; }
    .tenant-badge { font-size: 0.7rem; padding: 2px 7px; border-radius: 20px; white-space: nowrap; }
    .tenant-name  { flex: 1; font-weight: 500; color: #334155; overflow: hidden;
                    text-overflow: ellipsis; white-space: nowrap; }
</style>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="row mb-3"></div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title mb-0">
                        <i class="ri-database-2-line me-2"></i>Global Migration &amp; Schema Manager
                    </h4>
                    <div class="d-flex align-items-center">
                        <a href="#" class="btn btn-light text-primary fs-16 mx-1"
                           data-bs-toggle="modal" data-bs-target="#infoModal" title="Info">
                            <i class="ri-information-line"></i>
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <table id="globalActionsTable"
                           class="table table-sm table-striped row-border order-column w-100">
                        <thead style="background-color:#e2e2e9">
                            <tr>
                                <th>Action</th>
                                <th>Description</th>
                                <th style="text-align:center; width:260px;">Execute</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Run Pending Migrations</strong></td>
                                <td>
                                    Executes all pending Laravel migrations on
                                    <strong>every tenant database</strong>, one migration at a time
                                    with live progress. Failures are skipped and reported.
                                </td>
                                <td style="text-align:center">
                                    <button class="btn btn-success action-btn"
                                            data-bs-toggle="modal" data-bs-target="#runModal">
                                        <i class="ri-play-fill"></i> Run for All Tenants
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ══════════════════════ MODALS ══════════════════════ --}}

{{-- Info --}}
<div class="modal fade" id="infoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Global Migration &amp; Schema Manager</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>This tool runs pending migrations across <strong>all tenant databases</strong> at once.</p>
                <ul class="mb-3">
                    <li>Each migration is a separate request — PHP timeouts are never hit.</li>
                    <li>Migrations that fail are skipped; the rest continue.</li>
                    <li>A full summary of failures is shown at the end.</li>
                    <li>Tables that already exist are auto-healed and marked as complete.</li>
                    <li>Use carefully in production — monitor logs after execution.</li>
                </ul>
                <div class="text-end">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Run Modal ── --}}
<div class="modal fade" id="runModal" data-bs-backdrop="static" data-bs-keyboard="false"
     tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 580px; margin: 1.75rem auto;">
        <div class="modal-content">

            {{-- IDLE --}}
            <div class="modal-body text-center pb-4 pt-4" id="globalRunModal-idle">
                <i class="ri-play-circle-line text-success" style="font-size: 70px;"></i>
                <h4 class="mt-3">Run Pending Migrations for All Tenants?</h4>
                <p class="mb-1">
                    Pending migrations will be applied to <strong>every tenant database</strong>.
                </p>
                <p class="text-muted small mb-4">
                    Each migration runs as a separate request — no timeouts.<br>
                    Failures are skipped automatically and shown in a summary.
                </p>
                <button class="btn btn-success me-2" id="confirmGlobalRun" style="min-width: 140px;">
                    Yes, Run Now
                </button>
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal" style="min-width: 140px;">
                    Cancel
                </button>
            </div>

            {{-- RUNNING --}}
            <div class="modal-body pb-4 pt-3 d-none" id="globalRunModal-progress">

                <h5 class="text-center fw-bold mb-3" id="globalProgressTitle">
                    Running Migrations for All Tenants…
                </h5>

                {{-- Overall progress --}}
                <div class="mb-1">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span>Overall progress</span>
                        <span id="globalMigrationCounter">0 / 0 migrations</span>
                    </div>
                    <div class="progress mb-1" style="height: 22px; border-radius: 8px; background:#e2e8f0;">
                        <div id="globalProgressBar"
                             class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                             role="progressbar" style="width:0%">0%</div>
                    </div>
                </div>

                {{-- Current tenant progress --}}
                <div class="mb-2">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span id="currentTenantLabel">Tenant: —</span>
                        <span id="tenantMigrationCounter">0 / 0</span>
                    </div>
                    <div class="progress" style="height: 14px; border-radius: 6px; background:#e2e8f0;">
                        <div id="tenantProgressBar"
                             class="progress-bar bg-info"
                             role="progressbar" style="width:0%">0%</div>
                    </div>
                </div>

                {{-- Status line --}}
                <div class="d-flex justify-content-between text-muted small mb-2">
                    <span id="globalTenantCounter">Tenant 0 / 0</span>
                    <span id="globalMigrationStatus">Fetching tenant list…</span>
                </div>

                {{-- Terminal log --}}
                <div id="globalMigrationLog"></div>

                {{-- Toggle tenant list --}}
                <div class="mt-2 mb-1">
                    <button class="btn btn-outline-secondary btn-sm w-100" id="toggleTenantList">
                        <i class="ri-list-check me-1"></i> Show tenant progress list
                    </button>
                </div>
                <div id="tenantProgressList"></div>

                {{-- DONE --}}
                <div id="globalRunModal-done" class="text-center mt-3 d-none">
                    <i class="ri-checkbox-circle-line text-success" style="font-size: 48px;"></i>
                    <p class="fw-bold mt-2 mb-0" id="globalDoneMessage">All migrations completed!</p>
                    <button class="btn btn-primary mt-3" onclick="location.reload()">
                        <i class="ri-refresh-line me-1"></i> Reload Page
                    </button>
                </div>

                {{-- DONE WITH SKIPS --}}
                <div id="globalRunModal-partial" class="mt-3 d-none">
                    <div class="text-center mb-2">
                        <i class="ri-error-warning-line text-warning" style="font-size: 48px;"></i>
                        <p class="fw-bold mt-2 mb-1 text-warning" id="globalPartialMessage">
                            Completed with some failures
                        </p>
                    </div>
                    <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:8px;
                                padding:8px 12px; margin-bottom:10px; font-size:0.8rem; color:#92400e;">
                        The migrations below were skipped. Fix the issues and re-run to apply them.
                    </div>
                    <div style="overflow-x:auto; border-radius:8px; overflow:hidden; max-height:200px; overflow-y:auto;">
                        <table class="table table-sm mb-0" id="globalFailureSummary">
                            <thead>
                                <tr>
                                    <th style="width:25%">Tenant</th>
                                    <th style="width:35%">Migration</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody id="globalFailureTableBody"></tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <button class="btn btn-primary" onclick="location.reload()">
                            <i class="ri-refresh-line me-1"></i> Reload Page
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {

    $('#globalActionsTable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        scrollX: true,
        paging: true,
        searching: true,
        info: true,
    });

    const pendingListUrl = "{{ route('master.global.migrations.pending-list') }}";
    const nextUrl        = "{{ route('master.global.migrations.next', '__ID__') }}";
    const csrfToken      = "{{ csrf_token() }}";

    // ─── Helpers ──────────────────────────────────────────────────────────────

    function logLine(msg, type = 'info') {
        const cls  = { ok:'log-ok', err:'log-err', warn:'log-warn', heal:'log-heal',
                       info:'log-info', tenant:'log-tenant' }[type] || 'log-info';
        const $log = $('#globalMigrationLog');
        $log.append($('<div>').addClass(cls).text(msg));
        $log.scrollTop($log[0].scrollHeight);
    }

    function setGlobalProgress(done, total) {
        const pct = total > 0 ? Math.round((done / total) * 100) : 0;
        $('#globalProgressBar').css('width', pct + '%').text(pct + '%');
        $('#globalMigrationCounter').text(done + ' / ' + total + ' migrations');
    }

    function setTenantProgress(done, total, tenantName) {
        const pct = total > 0 ? Math.round((done / total) * 100) : 0;
        $('#tenantProgressBar').css('width', pct + '%').text(pct + '%');
        $('#tenantMigrationCounter').text(done + ' / ' + total);
        $('#currentTenantLabel').text('Tenant: ' + tenantName);
    }

    function resetTenantProgress() {
        $('#tenantProgressBar').css('width', '0%').text('0%');
        $('#tenantMigrationCounter').text('0 / 0');
    }

    function setStatus(msg) { $('#globalMigrationStatus').text(msg); }

    function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

    function stopAnimation(barId, color) {
        $('#' + barId)
            .removeClass('progress-bar-striped progress-bar-animated bg-success bg-warning bg-danger bg-info')
            .addClass(color);
    }

    // ── Tenant progress sidebar list ──────────────────────────────────────────
    const tenantStatuses = {}; // id -> { name, total, done, failed }

    function buildTenantList(tenants) {
        const $list = $('#tenantProgressList').empty();
        tenants.forEach(t => {
            if (t.skipped) return;
            tenantStatuses[t.id] = { name: t.name, total: t.pending.length, done: 0, failed: 0 };
            $list.append(
                $('<div class="tenant-row">').attr('id', 'trow-' + t.id).append(
                    $('<span class="tenant-name">').text(t.name),
                    $('<span class="tenant-badge badge bg-secondary" id="tbadge-' + t.id + '">').text('Pending')
                )
            );
        });
    }

    function updateTenantBadge(tenantId, state) {
        const map = {
            running:  ['bg-primary',  'Running…'],
            done:     ['bg-success',  'Done'],
            partial:  ['bg-warning',  'Partial'],
            skipped:  ['bg-secondary','Skipped'],
            nowork:   ['bg-light text-dark', 'Up to date'],
        };
        const [cls, label] = map[state] || ['bg-secondary', state];
        $('#tbadge-' + tenantId)
            .removeClass('bg-primary bg-success bg-warning bg-secondary bg-light text-dark')
            .addClass(cls)
            .text(label);
    }

    $('#toggleTenantList').on('click', function () {
        const $list = $('#tenantProgressList');
        const open  = $list.hasClass('expanded');
        $list.toggleClass('expanded', !open);
        $(this).html(
            open
            ? '<i class="ri-list-check me-1"></i> Show tenant progress list'
            : '<i class="ri-list-check me-1"></i> Hide tenant progress list'
        );
    });

    // ─── Main global runner ───────────────────────────────────────────────────
    //
    // Phase 1 — GET /pending-list  → array of tenants each with their pending[]
    // Phase 2 — for each tenant, loop POST /next/{tenantId} until that tenant's
    //           pending list is exhausted. One migration per request.

    async function runGlobalMigrationsSequentially() {

        $('#globalRunModal-idle').addClass('d-none');
        $('#globalRunModal-progress').removeClass('d-none');

        // ── Phase 1: fetch full pending list ──────────────────────────────────
        let tenants       = [];
        let totalPending  = 0;

        try {
            setStatus('Fetching tenant list…');
            logLine('Fetching pending migrations for all tenants…', 'info');

            const res  = await fetch(pendingListUrl, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();

            if (!res.ok || data.error) throw new Error(data.error || 'HTTP ' + res.status);

            tenants      = data.tenants;
            totalPending = data.total_pending;

            const skippedCount  = tenants.filter(t => t.skipped).length;
            const activeCount   = tenants.filter(t => !t.skipped).length;
            const upToDateCount = tenants.filter(t => !t.skipped && t.pending.length === 0).length;

            logLine(
                'Found ' + tenants.length + ' tenant(s): ' +
                activeCount + ' reachable, ' +
                skippedCount + ' skipped, ' +
                upToDateCount + ' already up to date, ' +
                totalPending + ' migration(s) to run.',
                'info'
            );

            // Log skipped tenants immediately
            tenants.filter(t => t.skipped).forEach(t => {
                logLine('  ⊘ SKIPPED tenant "' + t.name + '": ' + t.skip_reason, 'warn');
            });

            if (totalPending === 0) {
                setGlobalProgress(0, 0);
                setStatus('All up to date');
                stopAnimation('globalProgressBar', 'bg-success');
                logLine('✅  All tenant databases are already up to date.', 'ok');
                $('#globalDoneMessage').text('All tenant databases are already up to date.');
                $('#globalRunModal-done').removeClass('d-none');
                return;
            }

            buildTenantList(tenants);
            setGlobalProgress(0, totalPending);
            $('#globalTenantCounter').text('Tenant 0 / ' + tenants.filter(t => !t.skipped && t.pending.length > 0).length);

        } catch (err) {
            logLine('Error fetching tenant list: ' + err.message, 'err');
            stopAnimation('globalProgressBar', 'bg-danger');
            return;
        }

        // ── Phase 2: iterate tenants, then their pending migrations ───────────
        const failures   = []; // { tenant, migration, reason }
        let globalDone   = 0;
        let tenantIndex  = 0;
        const activeTenants = tenants.filter(t => !t.skipped && t.pending.length > 0);

        for (const tenant of activeTenants) {
            tenantIndex++;
            $('#globalTenantCounter').text('Tenant ' + tenantIndex + ' / ' + activeTenants.length);

            updateTenantBadge(tenant.id, 'running');
            resetTenantProgress();
            setTenantProgress(0, tenant.pending.length, tenant.name);

            logLine('', 'info');
            logLine('━━ [' + tenantIndex + '/' + activeTenants.length + '] ' + tenant.name + ' (' + tenant.database + ') — ' + tenant.pending.length + ' pending', 'tenant');

            let tenantDone    = 0;
            let tenantFailed  = 0;
            const url         = nextUrl.replace('__ID__', tenant.id);

            for (let i = 0; i < tenant.pending.length; i++) {
                const migName = tenant.pending[i];
                const stepNum = i + 1;

                setStatus(tenant.name + ' — ' + stepNum + '/' + tenant.pending.length);
                logLine('  ▶ [' + stepNum + '/' + tenant.pending.length + '] ' + migName, 'info');

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type':     'application/json',
                            'X-CSRF-TOKEN':     csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ migration: migName }),
                    });

                    let data = {};
                    try { data = await res.json(); } catch (_) {}

                    if (!res.ok || data.error) {
                        const reason = data.error || ('HTTP ' + res.status);
                        const detail = data.output || '';
                        logLine('    ✗ SKIPPED — ' + reason, 'err');
                        if (detail) logLine('      ' + detail, 'err');
                        failures.push({ tenant: tenant.name, migration: migName,
                                        reason: reason + (detail ? '\n' + detail : '') });
                        tenantFailed++;
                    } else {
                        if (data.healed) {
                            logLine('    ✦ AUTO-FIXED — ' + (data.output || 'marked as complete'), 'heal');
                        } else {
                            const summary = (data.output || 'Done')
                                .split('\n').map(l => l.trim()).filter(Boolean).join(' | ');
                            logLine('    ✓ ' + summary, 'ok');
                        }
                        tenantDone++;
                    }

                } catch (err) {
                    logLine('    ✗ SKIPPED — Network error: ' + err.message, 'err');
                    failures.push({ tenant: tenant.name, migration: migName,
                                    reason: 'Network error: ' + err.message });
                    tenantFailed++;
                }

                globalDone++;
                setGlobalProgress(globalDone, totalPending);
                setTenantProgress(tenantDone + tenantFailed, tenant.pending.length, tenant.name);

                await sleep(80);
            }

            // Tenant done — update badge
            updateTenantBadge(tenant.id, tenantFailed > 0 && tenantDone === 0 ? 'partial'
                                        : tenantFailed > 0                    ? 'partial'
                                        : 'done');

            logLine(
                '  → ' + tenant.name + ' complete: ' + tenantDone + ' succeeded' +
                (tenantFailed > 0 ? ', ' + tenantFailed + ' skipped' : '') + '.',
                tenantFailed > 0 ? 'warn' : 'ok'
            );
        }

        // ── Phase 3: final summary ────────────────────────────────────────────
        setStatus('Complete');
        logLine('', 'info');

        if (failures.length === 0) {
            stopAnimation('globalProgressBar', 'bg-success');
            logLine('✅  All migrations completed across all tenants.', 'ok');
            $('#globalDoneMessage').text(
                'All ' + totalPending + ' migration(s) ran successfully across ' + activeTenants.length + ' tenant(s).'
            );
            $('#globalRunModal-done').removeClass('d-none');

        } else {
            stopAnimation('globalProgressBar', 'bg-warning');
            const okCount = totalPending - failures.length;
            logLine('⚠  Finished: ' + okCount + ' succeeded, ' + failures.length + ' skipped.', 'warn');

            const $tbody = $('#globalFailureTableBody').empty();
            failures.forEach(f => {
                const reasonHtml = $('<div>').text(f.reason).html().replace(/\n/g, '<br>');
                $tbody.append(
                    '<tr>' +
                    '<td style="font-weight:600;color:#1e293b">' + $('<div>').text(f.tenant).html()    + '</td>' +
                    '<td style="color:#475569">'                 + $('<div>').text(f.migration).html() + '</td>' +
                    '<td style="color:#991b1b">'                 + reasonHtml                          + '</td>' +
                    '</tr>'
                );
            });

            $('#globalPartialMessage').text(
                okCount + ' migration(s) succeeded, ' + failures.length + ' skipped across all tenants.'
            );
            $('#globalProgressTitle').text('Completed with Skips');
            $('#globalRunModal-partial').removeClass('d-none');
        }
    }

    // ─── Confirm button ───────────────────────────────────────────────────────
    $('#confirmGlobalRun').on('click', function (e) {
        e.preventDefault();
        $(this).prop('disabled', true);
        runGlobalMigrationsSequentially();
    });

    // Reset modal on close
    $('#runModal').on('hidden.bs.modal', function () {
        $('#globalRunModal-idle').removeClass('d-none');
        $('#globalRunModal-progress').addClass('d-none');
        $('#globalRunModal-done').addClass('d-none');
        $('#globalRunModal-partial').addClass('d-none');
        $('#globalMigrationLog').empty();
        $('#globalFailureTableBody').empty();
        $('#tenantProgressList').empty().removeClass('expanded');
        $('#toggleTenantList').html('<i class="ri-list-check me-1"></i> Show tenant progress list');
        $('#globalProgressBar')
            .css('width','0%').text('0%')
            .removeClass('bg-danger bg-warning')
            .addClass('progress-bar-striped progress-bar-animated bg-success');
        $('#tenantProgressBar').css('width','0%').text('0%').addClass('bg-info');
        $('#globalMigrationCounter').text('0 / 0 migrations');
        $('#globalTenantCounter').text('Tenant 0 / 0');
        $('#globalMigrationStatus').text('Fetching tenant list…');
        $('#globalProgressTitle').text('Running Migrations for All Tenants…');
        $('#currentTenantLabel').text('Tenant: —');
        $('#tenantMigrationCounter').text('0 / 0');
        $('#confirmGlobalRun').prop('disabled', false);
    });

});
</script>
@endsection