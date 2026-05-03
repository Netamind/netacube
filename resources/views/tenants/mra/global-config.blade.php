@extends('tenants.admin.dashboard')
@section('content')
<style>
/* ── Card chrome ─────────────────────────────────────────────────────────── */
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

/* ── DataTable ──────────────────────────────────────────────────────────── */
.tab-pane { padding-top: 1rem; }
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
table.dataTable tbody td:first-child,
table.dataTable thead th:nth-child(2),
table.dataTable tbody td:nth-child(2) { text-align: left !important; }
table.dataTable tbody td { padding-top: 6px !important; padding-bottom: 6px !important; }
table.dataTable, .dataTables_wrapper { overflow: visible !important; }

/* ── Pills / badges ─────────────────────────────────────────────────────── */
.rate-id-pill {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 36px; height: 28px; border-radius: 7px; padding: 0 10px;
    font-weight: 800; font-size: 12px; font-family: monospace;
    background: #eef0fa; color: #4B5EBD;
}
.levy-id-pill {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 36px; height: 28px; border-radius: 7px; padding: 0 10px;
    font-weight: 800; font-size: 12px; font-family: monospace;
    background: #fef3c7; color: #92400e;
}
.mode-badge { display: inline-block; border-radius: 4px; padding: 2px 7px; font-size: 10px; font-weight: 600; background: #f1f5f9; color: #475569; }

/* ── Modal helpers ──────────────────────────────────────────────────────── */
.mh-blue  { background: linear-gradient(135deg, #4B5EBD, #576CC0); padding: 14px 18px !important; border-bottom: none; }
.mh-teal  { background: linear-gradient(135deg, #0f6e56, #1d9e75); padding: 14px 18px !important; border-bottom: none; }
.mh-title { color: #fff; font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 7px; }
.mh-close { filter: brightness(0) invert(1); opacity: .8; }
.mh-close:hover { opacity: 1; }
.sync-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.sync-detail-item label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #94a3b8; display: block; margin-bottom: 3px; }
.sync-detail-item .dv  { font-weight: 600; color: #1e293b; font-size: 13px; }
.sync-error-box { background: #fef2f2; border: 1px solid #fca5a5; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #991b1b; margin-top: 12px; }

@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
.spin { animation: spin 1s linear infinite; display: inline-block; }
</style>

<div class="progress" id="progressBar" role="progressbar" style="height:8px;transform:rotate(180deg);display:none">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page">
<div class="content">
<div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

  {{-- Card header --}}
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
      <i class="ri-settings-4-line"></i> MRA Global Configuration
    </h4>
    <div class="d-flex align-items-center" style="gap:4px;">
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn"     title="About Global Config"><i class="ri-information-line"></i></a>
      <a href="#" class="btn btn-light text-info    fs-16 mx-1" id="syncInfoBtn" title="Sync Status &amp; Details"><i class="ri-cloud-line"></i></a>
      <a href="#" class="btn btn-light text-success  fs-16 mx-1" id="syncNowBtn"  title="Sync Now"><i class="ri-refresh-line"></i></a>
      <a href="{{ route('tenant.admin.eis.dashboard') }}" class="btn btn-light text-primary fs-16 mx-1" title="Back to EIS Dashboard"><i class="ri-arrow-left-line"></i></a>
    </div>
  </div>

  {{-- Tab nav --}}
  <div class="tab-header-container">
    <ul class="nav nav-pills mb-0">
      <li class="nav-item">
        <a href="#taxRatesTab" data-bs-toggle="tab" class="nav-link active">
          <i class="ri-percent-line"></i> Tax Rates
          <span id="rateTabCount" class="badge bg-primary ms-1" style="font-size:10px;">—</span>
        </a>
      </li>
      <li class="nav-item">
        <a href="#leviesTab" data-bs-toggle="tab" class="nav-link">
          <i class="ri-receipt-line"></i> Activated Levies
          <span id="levyTabCount" class="badge bg-warning text-dark ms-1" style="font-size:10px;">—</span>
        </a>
      </li>
    </ul>
  </div>

  <div class="card-body">
    <div class="tab-content">

      <div class="tab-pane show active" id="taxRatesTab">
        <table id="rateTable" class="table table-sm table-striped row-border order-column w-100">
          <thead style="background-color:#e2e2e9">
            <tr>
              <th style="text-align:left !important;">Rate ID</th>
              <th style="text-align:left !important;">Name</th>
              <th>Rate (%)</th>
              <th>Charge Mode</th>
              <th>Ordinal</th>
              <th>Active</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <div class="tab-pane" id="leviesTab">
        <table id="levyTable" class="table table-sm table-striped row-border order-column w-100">
          <thead style="background-color:#e2e2e9">
            <tr>
              <th style="text-align:left !important;">Levy ID</th>
              <th style="text-align:left !important;">Name</th>
              <th>Rate (%)</th>
              <th>Charge Mode</th>
              <th>Applicable Tax Rate</th>
              <th>Ordinal</th>
              <th>Active</th>
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

{{-- INFO MODAL --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About MRA Global Configuration</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:18px 20px;">
        <p class="mb-3"><strong>What is this page?</strong><br>
        This page shows the master tax configuration that MRA pushes to all certified EIS terminals via the <code>getLatestConfig</code> API. Every receipt your terminals generate must apply exactly these rates and levies.</p>
        <hr class="my-3">
        <p class="mb-2"><strong><i class="ri-percent-line me-1 text-primary"></i> Tax Rate IDs</strong></p>
        <p style="font-size:13px;">MRA assigns each tax type a unique ID returned in <code>globalConfiguration.taxrates</code>. Always run a sync after any MRA gazette notice.</p>
        <div class="alert alert-warning py-2 px-3 mb-3" style="font-size:12px;border-radius:8px;">
          <i class="ri-error-warning-line me-1"></i>
          <strong>Important:</strong> Zero-rated and exempt supplies have different VAT return implications.
        </div>
        <hr class="my-3">
        <p class="mb-2"><strong><i class="ri-receipt-line me-1 text-warning"></i> Activated Levies</strong><br>
        Some taxpayer profiles attract additional levies returned in <code>taxpayerConfiguration.activatedLevies</code>.</p>
        <hr class="my-3">
        <p class="mb-1"><strong><i class="ri-refresh-line me-1 text-success"></i> Sync Now</strong> — Manually pulls the latest configuration from MRA. Also runs automatically every 6 hours.</p>
        <p class="mb-0"><strong><i class="ri-cloud-line me-1 text-info"></i> Sync Details</strong> — Shows last sync time, success/failure status, which terminal was used, and any error messages.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- SYNC STATUS DETAIL MODAL --}}
<div class="modal fade" id="syncDetailModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:520px;">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-teal">
        <h5 class="modal-title mh-title"><i class="ri-cloud-line"></i> MRA Config Sync Status</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:18px 20px;">
        <div id="syncModalBanner" class="mb-4 text-center" style="padding:20px;border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0;">
          <div id="syncModalIcon"   style="font-size:48px;margin-bottom:8px;color:#94a3b8"><i class="ri-cloud-off-line"></i></div>
          <div id="syncModalStatus" style="font-size:16px;font-weight:700;color:#1e293b;">Never synced</div>
          <div id="syncModalSub"    style="font-size:12px;color:#64748b;margin-top:4px;">Activate a terminal or click Sync Now.</div>
        </div>
        <div id="syncModalError" class="sync-error-box" style="display:none;">
          <strong><i class="ri-error-warning-line me-1"></i> Sync Error:</strong>
          <span id="syncModalErrorText"></span>
        </div>
        <div class="sync-detail-grid mt-3">
          <div class="sync-detail-item"><label>Config Version</label>      <div class="dv" id="sd-ver">—</div></div>
          <div class="sync-detail-item"><label>Status</label>              <div class="dv" id="sd-status">—</div></div>
          <div class="sync-detail-item"><label>Last Successful Sync</label><div class="dv" id="sd-synced">—</div></div>
          <div class="sync-detail-item"><label>Last Attempt</label>        <div class="dv" id="sd-attempt">—</div></div>
          <div class="sync-detail-item"><label>Synced Via Terminal</label> <div class="dv" id="sd-terminal">—</div></div>
          <div class="sync-detail-item"><label>Record Updated</label>      <div class="dv" id="sd-updated">—</div></div>
        </div>
      </div>
      <div class="modal-footer" style="gap:8px;">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <a href="#" class="btn btn-success btn-sm" id="syncNowFromModal">
          <i class="ri-refresh-line me-1"></i> Sync Now
        </a>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {
    toastr.options = { closeButton: true, progressBar: true, showMethod: 'slideDown', timeOut: 5000, allowHtml: true };

    var cachedConfig = null;

    var dtOpts = {
        scrollX:    false,
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
        pageLength: 25,
        language: { emptyTable: '<span class="text-muted fst-italic">No data — run a sync to fetch from MRA.</span>' },
        dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
        initComplete: function () { this.api().columns.adjust().draw(false); }
    };

    var ratesDT  = $('#rateTable').DataTable(dtOpts);
    var leviesDT = $('#levyTable').DataTable(dtOpts);

    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        var t = $(e.target).attr('href');
        if (t === '#taxRatesTab') ratesDT.columns.adjust().draw(false);
        if (t === '#leviesTab')   leviesDT.columns.adjust().draw(false);
    });

    function fmtDate(d) {
        if (!d) return '<span class="text-muted fst-italic">—</span>';
        return new Date(d).toLocaleString('en-GB', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
    }
    function activeBadge(val) {
        if (val === undefined || val === null) return '—';
        return val ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>';
    }
    function dash(v) { return (v !== undefined && v !== null && v !== '') ? v : '—'; }

    function fillRates(rates) {
        ratesDT.clear();
        $('#rateTabCount').text(rates ? rates.length : 0);
        if (rates && rates.length) {
            rates.forEach(function (r) {
                ratesDT.row.add([
                    '<span class="rate-id-pill">' + dash(r.id) + '</span>',
                    '<strong>' + dash(r.name) + '</strong>',
                    r.rate !== undefined ? '<strong style="font-size:14px;color:#1e293b">' + r.rate + '%</strong>' : '—',
                    '<span class="mode-badge">' + dash(r.chargeMode) + '</span>',
                    dash(r.ordinal),
                    activeBadge(r.isActive)
                ]);
            });
        }
        ratesDT.draw(false);
    }

    function fillLevies(levies) {
        leviesDT.clear();
        $('#levyTabCount').text(levies ? levies.length : 0);
        if (levies && levies.length) {
            levies.forEach(function (l) {
                leviesDT.row.add([
                    '<span class="levy-id-pill">' + dash(l.id) + '</span>',
                    '<strong>' + dash(l.name) + '</strong>',
                    l.rate !== undefined ? '<strong style="color:#d97706">' + l.rate + '%</strong>' : '—',
                    '<span class="mode-badge">' + dash(l.chargeMode) + '</span>',
                    dash(l.applicableTaxRateId),
                    dash(l.ordinal),
                    activeBadge(l.isActive)
                ]);
            });
        }
        leviesDT.draw(false);
    }

    function renderSyncModal(cfg) {
        var s    = cfg.last_sync_status;
        var sMap = {
            ok:     '<span class="badge bg-success">OK</span>',
            failed: '<span class="badge bg-danger">Failed</span>'
        };
        if (s === 'ok') {
            $('#syncModalBanner').attr('style','padding:20px;border-radius:10px;background:#f0fdf4;border:1px solid #86efac;');
            $('#syncModalIcon').html('<i class="ri-cloud-check-line" style="color:#16a34a"></i>');
            $('#syncModalStatus').text('Synced successfully');
            $('#syncModalSub').text('Tax rates and levies are current.');
            $('#syncModalError').hide();
        } else if (s === 'failed') {
            $('#syncModalBanner').attr('style','padding:20px;border-radius:10px;background:#fef2f2;border:1px solid #fca5a5;');
            $('#syncModalIcon').html('<i class="ri-cloud-off-line" style="color:#dc2626"></i>');
            $('#syncModalStatus').text('Last sync failed');
            $('#syncModalSub').text('See error details below.');
            if (cfg.last_sync_error) { $('#syncModalErrorText').text(cfg.last_sync_error); $('#syncModalError').show(); }
            else { $('#syncModalError').hide(); }
        } else {
            $('#syncModalBanner').attr('style','padding:20px;border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0;');
            $('#syncModalIcon').html('<i class="ri-cloud-off-line" style="color:#94a3b8"></i>');
            $('#syncModalStatus').text('Never synced');
            $('#syncModalSub').text('Activate a terminal or click Sync Now to fetch from MRA.');
            $('#syncModalError').hide();
        }
        $('#sd-ver').text(cfg.mra_version_no || '0');
        $('#sd-status').html(sMap[s] || '<span class="badge bg-secondary">' + (s || 'unknown') + '</span>');
        $('#sd-synced').html(cfg.last_synced_at          ? fmtDate(cfg.last_synced_at)         : '<span class="text-muted fst-italic">Never</span>');
        $('#sd-attempt').html(cfg.last_sync_attempted_at ? fmtDate(cfg.last_sync_attempted_at) : '<span class="text-muted fst-italic">Never</span>');
        $('#sd-terminal').html(cfg.synced_via_terminal_id ? 'Terminal #' + cfg.synced_via_terminal_id : '<span class="text-muted fst-italic">—</span>');
        $('#sd-updated').html(cfg.updated_at             ? fmtDate(cfg.updated_at)             : '—');
    }

    function loadConfig() {
        $.getJSON('{{ route("tenant.admin.eis.global-config.get") }}', function (data) {
            if (!data.success) {
                toastr.error(data.message || 'Could not load configuration.', 'Error');
                fillRates([]);
                fillLevies([]);
                return;
            }
            cachedConfig = data.config;
            fillRates(cachedConfig.tax_rates);
            fillLevies(cachedConfig.activated_levies);
        }).fail(function () {
            toastr.error('Request failed. Please refresh the page.', 'Error');
            fillRates([]);
            fillLevies([]);
        });
    }

    loadConfig();

    $('#infoBtn').on('click', function (e) { e.preventDefault(); $('#infoModal').modal('show'); });

    $('#syncInfoBtn').on('click', function (e) {
        e.preventDefault();
        if (cachedConfig) renderSyncModal(cachedConfig);
        $('#syncDetailModal').modal('show');
    });

    function doSync(btnEl) {
        var $btn = $(btnEl).prop('disabled', true);
        $.ajax({
            type: 'POST',
            url:  '{{ route("tenant.admin.eis.sync-config") }}',
            data: { _token: '{{ csrf_token() }}' },
            beforeSend: function () { $('#progressBar').show(); },
            complete:   function () { $('#progressBar').hide(); $btn.prop('disabled', false); },
            success: function (r) {
                if (r.success) { toastr.success(r.success, 'Synced'); loadConfig(); }
                else           { toastr.error(r.error || 'Sync failed.', 'Error'); }
            },
            error: function (xhr, status) {
                toastr.error(status === 'timeout' ? 'The request timed out.' : 'Request failed.', 'Error');
            }
        });
    }

    $('#syncNowBtn').on('click',       function (e) { e.preventDefault(); doSync(this); });
    $('#syncNowFromModal').on('click', function (e) { e.preventDefault(); $('#syncDetailModal').modal('hide'); doSync($('#syncNowBtn')[0]); });
});
</script>
@endsection