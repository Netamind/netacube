@extends('tenants.admin.dashboard')
@section('content')
<style>
/* ── Card chrome ────────────────────────────────────────────────────────── */
.card-header { padding: 0.5rem 1.5rem !important; background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff; }
.card-body   { padding: 0 1.5rem 1.5rem 1.5rem !important; }
.card-header .btn-light { height: 28px; padding: 0 10px; display: flex; align-items: center; justify-content: center; line-height: 1; }
.card-header .btn-light:hover { background-color: #f8f9fa; transition: background-color 0.2s ease-in-out; }
.card { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; }
.card-header h4 { color: #fff; font-weight: 600; margin-bottom: 0; display: flex; align-items: center; }
.card-header h4 i { margin-right: 0.25rem; }

/* ── Tab header ─────────────────────────────────────────────────────────── */
.tab-header-container { background: #f8f9fa; border-top: 1px solid #dee2e6; }
.nav-pills .nav-link {
    border-radius: 0 !important; padding: .75rem 1rem;
    font-weight: 500; color: #495057;
    border-bottom: 3px solid transparent; transition: all .2s;
}
.nav-pills .nav-link:hover  { background: #e9ecef; color: #4B5EBD; }
.nav-pills .nav-link.active { background: transparent !important; color: #4B5EBD !important; border-bottom-color: #4B5EBD; font-weight: 600; }
.nav-pills .nav-link i      { font-size: 1.1rem; margin-right: .35rem; }
.tab-pane { padding-top: 1rem; }

/* ── Stat cards — 6 per row, 2 rows = 12 total ──────────────────────────── */
.eis-stat-row {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 10px;
    margin: 0 0 18px;
}
.eis-stat { background: #fff; border-radius: 10px; padding: 12px 14px; border: 1px solid #e8ecf4; box-shadow: 0 2px 6px rgba(0,0,0,0.05); transition: transform 0.15s, box-shadow 0.15s; }
.eis-stat:hover { transform: translateY(-2px); box-shadow: 0 6px 14px rgba(0,0,0,0.08); }
.eis-stat .stat-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 15px; margin-bottom: 7px; }
.eis-stat .stat-val  { font-size: 22px; font-weight: 800; color: #1e293b; line-height: 1; }
.eis-stat .stat-lbl  { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.6px; margin-top: 3px; }

/* ── Section sub-card ───────────────────────────────────────────────────── */
.eis-section { background: #fff; border-radius: 10px; border: 1px solid #e2e8f0; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 18px; overflow: hidden; }
.eis-section-head {
    background: linear-gradient(to right, #f8f9ff, #eef0fa);
    border-bottom: 1px solid #dde2f0;
    padding: 9px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 42px;
}
.eis-section-head h6 { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #4B5EBD; margin: 0; display: flex; align-items: center; gap: 6px; }

/* ── Equal-height flex section cards ────────────────────────────────────── */
.eis-section.flex-col { display: flex; flex-direction: column; }
.eis-section.flex-col .action-grid.flex-fill,
.eis-section.flex-col .cfg-table-wrap.flex-fill { flex: 1; }

/* ── Action tiles — 3 columns × 2 rows = 6 tiles ────────────────────────── */
.action-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    padding: 12px;
}
.action-tile {
    border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 14px;
    display: flex; flex-direction: column; gap: 6px;
    text-decoration: none; background: #fafbff;
    transition: border-color 0.15s, box-shadow 0.15s, transform 0.15s;
    cursor: pointer;
}
.action-tile:hover { border-color: #4B5EBD; box-shadow: 0 4px 16px rgba(75,94,189,0.12); transform: translateY(-2px); text-decoration: none; }
.action-tile-icon  { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; }
.action-tile-title { font-size: 12px; font-weight: 700; color: #1e293b; }
.action-tile-desc  { font-size: 11px; color: #64748b; line-height: 1.5; }

/* ── Config table ───────────────────────────────────────────────────────── */
.cfg-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.cfg-table tbody tr { border-bottom: 1px solid #f1f5f9; }
.cfg-table tbody tr:last-child { border-bottom: none; }
.cfg-table tbody tr:hover td { background: #fafbff; }
.cfg-table td { padding: 9px 16px; vertical-align: middle; }
.cfg-table td:first-child { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; width: 44%; }
.cfg-table td:last-child  { font-weight: 600; color: #1e293b; }

/* ── Activity list ──────────────────────────────────────────────────────── */
.act-list { list-style: none; margin: 0; padding: 0; }
.act-list li { display: flex; align-items: flex-start; gap: 10px; padding: 9px 16px; border-bottom: 1px solid #f1f5f9; font-size: 12px; }
.act-list li:last-child { border-bottom: none; }
.act-icon { width: 26px; height: 26px; border-radius: 6px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 12px; }
.act-icon.success { background: #d1fae5; color: #059669; }
.act-icon.error   { background: #fee2e2; color: #dc2626; }
.act-icon.info    { background: #dbeafe; color: #2563eb; }
.act-time { font-size: 10px; color: #94a3b8; margin-top: 1px; }

/* ── Branch table ───────────────────────────────────────────────────────── */
table.dataTable thead th {
    background-color: #e2e2e9 !important;
    font-weight: 600; font-size: 13px; color: #343a40; white-space: nowrap;
}
table.dataTable thead th,
table.dataTable tbody td {
    text-align: center !important;
    vertical-align: middle !important;
    font-size: 13px;
}
table.dataTable thead th:first-child,
table.dataTable tbody td:first-child { text-align: left !important; }
table.dataTable tbody td { padding-top: 6px !important; padding-bottom: 6px !important; }
table.dataTable, .dataTables_wrapper { overflow: visible !important; }

/* ── Branch name cell ───────────────────────────────────────────────────── */
.branch-name { font-weight: 700; color: #1e293b; font-size: 12px; }
.branch-site { font-size: 10px; color: #94a3b8; font-family: monospace; margin-top: 1px; }

/* ── Badges ─────────────────────────────────────────────────────────────── */
.eis-on  { background: #d1fae5; color: #065f46; border-radius: 5px; padding: 2px 8px; font-size: 11px; font-weight: 700; display: inline-block; }
.eis-off { background: #fee2e2; color: #991b1b; border-radius: 5px; padding: 2px 8px; font-size: 11px; font-weight: 700; display: inline-block; }

/* ── Modal header helpers ───────────────────────────────────────────────── */
.mh-blue  { background: linear-gradient(135deg, #4B5EBD, #576CC0); padding: 14px 18px !important; border-bottom: none; }
.mh-title { color: #fff; font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 7px; }
.mh-close { filter: brightness(0) invert(1); opacity: .8; }
.mh-close:hover { opacity: 1; }

/* ── Spinner ────────────────────────────────────────────────────────────── */
@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
.spin { animation: spin 1s linear infinite; display: inline-block; }
</style>

<div class="progress" id="progressBar" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"
     style="height:8px;transform:rotate(180deg);display:none">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page">
<div class="content">
<div class="container-fluid">

<div class="row mb-3"></div>

<div class="card">

  {{-- ── Card header ─────────────────────────────────────────────────── --}}
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
      <i class="ri-receipt-tax-line"></i> Electronic Invoicing System
    </h4>
    <div class="d-flex align-items-center" style="gap:6px;">
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn"       title="About EIS"><i class="ri-information-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="syncConfigBtn" title="Sync Config"><i class="ri-refresh-line"></i></a>
      <a href="{{ route('tenant.admin.eis.global-config') }}" class="btn btn-light text-primary fs-16 mx-1" title="Global Config"><i class="ri-settings-4-line"></i></a>
    </div>
  </div>

  {{-- ── Tab nav ─────────────────────────────────────────────────────── --}}
  <div class="tab-header-container">
    <ul class="nav nav-pills mb-0">
      <li class="nav-item">
        <a href="#overviewTab" data-bs-toggle="tab" class="nav-link active">
          <i class="ri-dashboard-line"></i> Overview
        </a>
      </li>
      <li class="nav-item">
        <a href="#branchTab" data-bs-toggle="tab" class="nav-link">
          <i class="ri-store-line"></i> Branch Overview
          <span id="branchTabCount" class="badge bg-primary ms-1" style="font-size:10px;">{{ $branches->count() }}</span>
        </a>
      </li>
    </ul>
  </div>

  {{-- ── Body ────────────────────────────────────────────────────────── --}}
  <div class="card-body">
    <div class="tab-content">

      {{-- ════════════════════════════════════════════════════════════════
           TAB 1 — OVERVIEW
      ════════════════════════════════════════════════════════════════════ --}}
      <div class="tab-pane show active" id="overviewTab">

        {{-- ── Stat cards ─────────────────────────────────────────────── --}}
        <div class="col-12">
          <div class="eis-stat-row">

            {{-- Row 1 --}}
            <div class="eis-stat">
              <div class="stat-icon" style="background:#e0f2fe;color:#0284c7"><i class="ri-git-branch-line"></i></div>
              <div class="stat-val">{{ $branches->count() }}</div>
              <div class="stat-lbl">Branches</div>
            </div>
            <div class="eis-stat">
              <div class="stat-icon" style="background:#ede9fe;color:#7c3aed"><i class="ri-cpu-line"></i></div>
              <div class="stat-val" id="stat-total">—</div>
              <div class="stat-lbl">Total Terminals</div>
            </div>
            <div class="eis-stat">
              <div class="stat-icon" style="background:#d1fae5;color:#059669"><i class="ri-checkbox-circle-line"></i></div>
              <div class="stat-val" id="stat-activated" style="color:#059669">—</div>
              <div class="stat-lbl">Activated</div>
            </div>
            <div class="eis-stat">
              <div class="stat-icon" style="background:#fef3c7;color:#d97706"><i class="ri-time-line"></i></div>
              <div class="stat-val" id="stat-pending" style="color:#d97706">—</div>
              <div class="stat-lbl">Pending</div>
            </div>
            <div class="eis-stat">
              <div class="stat-icon" style="background:#fee2e2;color:#dc2626"><i class="ri-close-circle-line"></i></div>
              <div class="stat-val" id="stat-failed" style="color:#dc2626">—</div>
              <div class="stat-lbl">Failed</div>
            </div>
            <div class="eis-stat">
              <div class="stat-icon" style="background:#f0fdf4;color:#16a34a"><i class="ri-git-branch-line"></i></div>
              <div class="stat-val" id="stat-ver" style="color:#0284c7">—</div>
              <div class="stat-lbl">Config Version</div>
            </div>

            {{-- Row 2 --}}
            <div class="eis-stat">
              <div class="stat-icon" style="background:#f0fdf4;color:#16a34a"><i class="ri-cloud-line"></i></div>
              <div class="stat-val" id="stat-sync" style="font-size:13px;font-weight:700">—</div>
              <div class="stat-lbl">Last Sync</div>
            </div>
            <div class="eis-stat">
              <div class="stat-icon" style="background:#fdf4ff;color:#9333ea"><i class="ri-receipt-line"></i></div>
              <div class="stat-val" id="stat-invoices">—</div>
              <div class="stat-lbl">Invoices Today</div>
            </div>
            <div class="eis-stat">
              <div class="stat-icon" style="background:#fff7ed;color:#ea580c"><i class="ri-error-warning-line"></i></div>
              <div class="stat-val" id="stat-errors">—</div>
              <div class="stat-lbl">API Errors</div>
            </div>
            <div class="eis-stat">
              <div class="stat-icon" style="background:#ecfdf5;color:#10b981"><i class="ri-shield-check-line"></i></div>
              <div class="stat-val" id="stat-compliant">—</div>
              <div class="stat-lbl">Compliant</div>
            </div>
            <div class="eis-stat">
              <div class="stat-icon" style="background:#eff6ff;color:#3b82f6"><i class="ri-signal-wifi-line"></i></div>
              <div class="stat-val" id="stat-online">—</div>
              <div class="stat-lbl">Online Now</div>
            </div>
            <div class="eis-stat">
              <div class="stat-icon" style="background:#fafafa;color:#6b7280"><i class="ri-calendar-check-line"></i></div>
              <div class="stat-val" id="stat-lastact" style="font-size:12px;font-weight:700">—</div>
              <div class="stat-lbl">Last Activity</div>
            </div>

          </div>
        </div>

        {{-- ── Bottom row: Quick Actions + MRA Config Sync ─────────────── --}}
        <div class="row g-3">

          <div class="col-7">
            <div class="eis-section flex-col h-100">
              <div class="eis-section-head">
                <h6><i class="ri-flashlight-line"></i> Quick Actions</h6>
              </div>
              <div class="action-grid flex-fill">
                <a href="{{ route('tenant.admin.eis.terminals') }}" class="action-tile">
                  <div class="action-tile-icon" style="background:#ede9fe;color:#7c3aed"><i class="ri-cpu-line"></i></div>
                  <div class="action-tile-title">Manage Terminals</div>
                  <div class="action-tile-desc">Add, activate, and monitor POS terminals per branch</div>
                </a>
                <a href="{{ route('tenant.admin.eis.global-config') }}" class="action-tile">
                  <div class="action-tile-icon" style="background:#e0f2fe;color:#0284c7"><i class="ri-settings-4-line"></i></div>
                  <div class="action-tile-title">Global Config</div>
                  <div class="action-tile-desc">View MRA tax rates, levies and sync status</div>
                </a>
                <a href="{{ route('tenant.admin.eis.terminal-logs') }}" class="action-tile">
                  <div class="action-tile-icon" style="background:#fef3c7;color:#d97706"><i class="ri-history-line"></i></div>
                  <div class="action-tile-title">Terminal Logs</div>
                  <div class="action-tile-desc">View all MRA API call logs per terminal</div>
                </a>
                <a href="#" class="action-tile" id="syncTile">
                  <div class="action-tile-icon" style="background:#f0fdf4;color:#16a34a"><i class="ri-refresh-line"></i></div>
                  <div class="action-tile-title">Sync Config</div>
                  <div class="action-tile-desc">Pull latest tax rates and levies from MRA now</div>
                </a>
                <a href="{{ route('tenant.admin.eis.global-config') }}" class="action-tile">
                  <div class="action-tile-icon" style="background:#fee2e2;color:#dc2626"><i class="ri-percent-line"></i></div>
                  <div class="action-tile-title">Tax Rates</div>
                  <div class="action-tile-desc">View current MRA VAT codes and levy rates</div>
                </a>
                <a href="#branchTab" data-bs-toggle="tab" class="action-tile">
                  <div class="action-tile-icon" style="background:#fff7ed;color:#ea580c"><i class="ri-store-line"></i></div>
                  <div class="action-tile-title">Branch Overview</div>
                  <div class="action-tile-desc">Status summary for all registered branches</div>
                </a>
              </div>
            </div>
          </div>

          <div class="col-5">
            <div class="eis-section flex-col h-100">
              <div class="eis-section-head">
                <h6><i class="ri-cloud-line"></i> MRA Config Sync</h6>
                <a href="{{ route('tenant.admin.eis.global-config') }}"
                   class="btn btn-sm btn-outline-primary"
                   style="font-size:11px;height:24px;display:inline-flex;align-items:center;gap:3px;padding:0 9px">
                  <i class="ri-eye-line"></i> Full Config
                </a>
              </div>
              <div class="cfg-table-wrap flex-fill">
                <table class="cfg-table">
                  <tbody>
                    <tr><td>Last Synced</td>    <td id="cfg-synced">—</td></tr>
                    <tr><td>Sync Status</td>    <td id="cfg-status">—</td></tr>
                    <tr><td>Config Version</td> <td id="cfg-mra-ver">—</td></tr>
                    <tr><td>MRA Environment</td><td id="cfg-env">—</td></tr>
                    <tr><td>Tax Codes Loaded</td><td id="cfg-taxcodes">—</td></tr>
                    <tr><td>Levies Loaded</td>  <td id="cfg-levies">—</td></tr>
                    <tr><td>Next Auto-Sync</td> <td id="cfg-next">Every 6 hours</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

        </div>
      </div>

      {{-- ════════════════════════════════════════════════════════════════
           TAB 2 — BRANCH OVERVIEW
      ════════════════════════════════════════════════════════════════════ --}}
      <div class="tab-pane" id="branchTab">
        <table id="branchTable" class="table table-sm table-striped row-border order-column w-100">
          <thead style="background-color:#e2e2e9">
            <tr>
              <th style="text-align:left !important;">Branch</th>
              <th>EIS</th>
              <th>Terminals</th>
              <th>Activated</th>
              <th>Config Ver.</th>
              <th>Sync</th>
              <th></th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

    </div>
  </div>
</div>

</div>
</div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     INFO MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About the Electronic Invoicing System (EIS)</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:18px 20px;">
        <p class="mb-2"><strong>What is EIS?</strong><br>
        The Electronic Invoicing System (EIS) is a mandatory programme by the Malawi Revenue Authority (MRA) for all VAT-registered businesses. It requires every sale to be recorded on an MRA-certified POS terminal that signs the receipt and submits it to MRA in real time over the internet.</p>
        <p class="mb-1"><strong>Why does this matter to your business?</strong><br>
        If you issue a receipt that has not gone through EIS, it is not a valid fiscal receipt under MRA rules. Every till at every branch must be registered and activated with MRA before it can be used for sales.</p>
        <hr class="my-3">
        <p class="mb-2"><strong>How the setup process works — step by step:</strong></p>
        <ol style="font-size:13px;padding-left:20px;line-height:1.9;">
          <li><strong>Register your branch with MRA</strong> — Each physical location must be registered on the MRA EIS Portal. MRA will assign it a <strong>Site ID</strong>.</li>
          <li><strong>Add a terminal in this system</strong> — For each POS till at the branch, create a terminal record here.</li>
          <li><strong>Activate using a TAC</strong> — Log in to the MRA EIS Portal, copy the Terminal Activation Code (TAC), enter it here and click Activate.</li>
          <li><strong>Confirm the activation</strong> — Immediately after activating, click Confirm on the terminal card. <em>The TAC is consumed at this point.</em></li>
          <li><strong>Start issuing receipts</strong> — The terminal is now live.</li>
        </ol>
        <hr class="my-3">
        <p class="mb-2"><strong>Tax Rates &amp; Levies</strong><br>
        MRA controls all tax rates centrally. Always run a sync after any MRA gazette update.</p>
        <hr class="my-3">
        <p class="mb-1"><strong><i class="ri-refresh-line me-1 text-primary"></i> Sync Config</strong> — Manually pulls the latest tax rates and levies from MRA. Also runs automatically every 6 hours.</p>
        <p class="mb-0"><strong><i class="ri-cpu-line me-1 text-primary"></i> Manage Terminals</strong> — Add, activate, and monitor POS terminals per branch.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {
    toastr.options = { closeButton: true, progressBar: true, showMethod: 'slideDown', timeOut: 5000, allowHtml: true };

    function fmtDate(d) {
        if (!d) return '<span class="text-muted fst-italic">—</span>';
        return new Date(d).toLocaleString('en-GB', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
    }
    function syncBadge(s) {
        if (s === 'ok')     return '<span class="badge bg-success">OK</span>';
        if (s === 'failed') return '<span class="badge bg-danger">Failed</span>';
        return '<span class="badge bg-secondary">Never</span>';
    }

    var dtOpts = {
        scrollX:    false,
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
        pageLength: 25,
        language: { emptyTable: '<span class="text-muted fst-italic">No branch data available yet.</span>' },
        dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
        initComplete: function () { this.api().columns.adjust().draw(false); }
    };

    var branchDT = $('#branchTable').DataTable(dtOpts);

    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        if ($(e.target).attr('href') === '#branchTab') {
            branchDT.columns.adjust().draw(false);
        }
    });

    function loadGlobalSummary() {
        $.getJSON('{{ route("tenant.admin.eis.global-config.get") }}', function(data) {
            if (!data.success) return;
            var cfg = data.config;
            $('#stat-ver').text(cfg.mra_version_no || '0');
            $('#stat-sync').html(syncBadge(cfg.last_sync_status));
            $('#cfg-synced').html(fmtDate(cfg.last_synced_at));
            $('#cfg-status').html(syncBadge(cfg.last_sync_status));
            $('#cfg-mra-ver').text(cfg.mra_version_no || '0');
            $('#cfg-env').text(cfg.mra_environment || '—');
            $('#cfg-taxcodes').text(cfg.tax_codes_count !== undefined ? cfg.tax_codes_count : '—');
            $('#cfg-levies').text(cfg.levies_count !== undefined ? cfg.levies_count : '—');
        });
    }

    function loadBranchOverview() {
        var branches        = @json($branches);
        if (!branches.length) return;

        var terminalsBase   = "{{ route('tenant.admin.eis.terminals') }}";
        var branchStatusUrl = "{{ route('tenant.admin.eis.branch-status') }}";

        var totalTerminals = 0, totalActivated = 0, totalPending = 0, totalFailed = 0;
        var pending = branches.length;

        branches.forEach(function(b) {
            $.getJSON(branchStatusUrl, { branch_id: b.id }, function(data) {
                pending--;
                var s = data.success ? data.summary : {};
                totalTerminals += (s.total_terminals || 0);
                totalActivated += (s.activated_terminals || 0);
                totalPending   += (s.pending_terminals || 0);
                totalFailed    += (s.failed_terminals || 0);

                if (!pending) {
                    $('#stat-total').text(totalTerminals);
                    $('#stat-activated').text(totalActivated);
                    $('#stat-pending').text(totalPending);
                    $('#stat-failed').text(totalFailed);
                    $('#stat-compliant').text(totalActivated);
                    $('#stat-online').text(totalActivated);
                }

                var nameCell = '<div class="branch-name">' + b.name + '</div>'
                    + '<div class="branch-site">'
                    + (s.mra_site_id ? s.mra_site_id : '<span style="color:#c8d0ed">No Site ID</span>')
                    + '</div>';

                var terminalsLink = '<a href="' + terminalsBase + '?branch_id=' + b.id + '"'
                    + ' class="btn btn-sm btn-outline-primary"'
                    + ' style="font-size:10px;height:22px;padding:0 8px;display:inline-flex;align-items:center;gap:2px">'
                    + '<i class="ri-cpu-line"></i> Terminals</a>';

                branchDT.row.add([
                    nameCell,
                    s.eis_enabled ? '<span class="eis-on">On</span>' : '<span class="eis-off">Off</span>',
                    '<strong style="color:#1e293b">' + (s.total_terminals || 0) + '</strong>',
                    '<strong style="color:#059669">' + (s.activated_terminals || 0) + '</strong>',
                    '<span style="font-size:11px;color:#64748b">' + (s.config_version || '—') + '</span>',
                    syncBadge(s.config_sync_status),
                    terminalsLink
                ]).draw(false);

            }).fail(function() {
                pending--;
                branchDT.row.add([
                    '<div class="branch-name text-danger"><i class="ri-error-warning-line me-1"></i>' + b.name + '</div>',
                    '—', '—', '—', '—', '—', '—'
                ]).draw(false);
                if (!pending) {
                    $('#stat-total').text(totalTerminals);
                    $('#stat-activated').text(totalActivated);
                    $('#stat-pending').text(totalPending);
                    $('#stat-failed').text(totalFailed);
                }
            });
        });
    }

    function loadActivity() {
        @if($branches->count())
        var terminalsGetUrl = "{{ route('tenant.admin.eis.terminals.get') }}";
        var terminalLogsUrl = "{{ route('tenant.admin.eis.terminal-logs.get') }}";
        var firstBranchId   = {{ $branches->first()->id }};

        $.getJSON(terminalsGetUrl, { branch_id: firstBranchId }, function(d) {
            if (!d.success || !d.terminals.length) {
                $('#activityBody').html('<ul class="act-list"><li><div class="act-icon info"><i class="ri-information-line"></i></div><div style="color:#64748b;font-size:12px;">No activity yet.</div></li></ul>');
                return;
            }
            var t = d.terminals[0];
            if (t.last_used_at) {
                $('#stat-lastact').html(new Date(t.last_used_at).toLocaleString('en-GB',{day:'2-digit',month:'short',hour:'2-digit',minute:'2-digit'}));
            }
            $.getJSON(terminalLogsUrl, { terminal_id: t.id, page: 1 }, function(ld) {
                if (!ld.success || !ld.logs.length) {
                    $('#activityBody').html('<ul class="act-list"><li><div class="act-icon info"><i class="ri-information-line"></i></div><div style="color:#64748b;font-size:12px;">No activity recorded yet.</div></li></ul>');
                    return;
                }
                var errCount = ld.logs.filter(function(l){ return l.outcome === 'error'; }).length;
                $('#stat-errors').text(errCount);
                $('#stat-invoices').text(ld.logs.length);

                var html = '<ul class="act-list">';
                ld.logs.slice(0, 6).forEach(function(l) {
                    var cls  = l.outcome === 'success' ? 'success' : l.outcome === 'error' ? 'error' : 'info';
                    var icon = l.outcome === 'success' ? 'ri-check-line' : l.outcome === 'error' ? 'ri-close-line' : 'ri-information-line';
                    html += '<li>'
                        + '<div class="act-icon ' + cls + '"><i class="' + icon + '"></i></div>'
                        + '<div>'
                        + '<div style="font-weight:600;color:#1e293b">' + l.endpoint + ' <span class="badge bg-light text-dark border" style="font-size:10px">' + l.http_method + '</span></div>'
                        + '<div style="color:#64748b">' + (l.outcome_message || l.mra_remark || '—') + '</div>'
                        + '<div class="act-time">' + new Date(l.created_at).toLocaleString('en-GB', {day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit'}) + '</div>'
                        + '</div>'
                        + '</li>';
                });
                html += '</ul>';
                $('#activityBody').html(html);
            }).fail(function() {
                $('#activityBody').html('<ul class="act-list"><li><div class="act-icon info"><i class="ri-information-line"></i></div><div style="color:#64748b;font-size:12px;">Could not load activity.</div></li></ul>');
            });
        }).fail(function() {
            $('#activityBody').html('<ul class="act-list"><li><div class="act-icon info"><i class="ri-information-line"></i></div><div style="color:#64748b;font-size:12px;">Could not load activity.</div></li></ul>');
        });
        @else
        $('#activityBody').html('<ul class="act-list"><li><div class="act-icon info"><i class="ri-information-line"></i></div><div style="color:#64748b;font-size:12px;">No branches configured.</div></li></ul>');
        @endif
    }

    loadGlobalSummary();
    loadBranchOverview();
    loadActivity();

    $('#infoBtn').on('click', function(e) { e.preventDefault(); $('#infoModal').modal('show'); });

    function doSync() {
        $.ajax({
            type: 'POST',
            url: '{{ route("tenant.admin.eis.sync-config") }}',
            data: { _token: '{{ csrf_token() }}' },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); },
            success: function(r) {
                if (r.success) { toastr.success(r.success, 'Synced'); loadGlobalSummary(); }
                else           { toastr.error(r.error || 'Sync failed.', 'Error'); }
            },
            error: function() { toastr.error('Sync request failed.', 'Error'); }
        });
    }
    $('#syncConfigBtn, #syncTile').on('click', function(e) { e.preventDefault(); doSync(); });
});
</script>
@endsection