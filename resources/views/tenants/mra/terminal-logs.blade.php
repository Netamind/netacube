@extends('tenants.admin.dashboard')
@section('content')
@php

  $pref = DB::connection('tenant')->table('user_filters')->where('user_id', Auth::id())->first();



  $branches = DB::connection('tenant')->table('branches')->orderBy('name')->get();

  $terminals = collect();

  if ($pref->branch_id) {
      $terminals = DB::connection('tenant')->table('branch_terminals')->where('branch_id', $pref->branch_id)->orderBy('terminal_position')->get();
  }
  $logs = collect();
  if ($pref->terminal_id) {
      $query = DB::connection('tenant')->table('eis_terminal_logs')->where('terminal_id', $pref->terminal_id)->limit(500)->get();
  }
@endphp
<style>
/* ── Card chrome ─────────────────────────────────────────────────────────── */
.card-header { padding: 0.5rem 1.5rem !important; background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff; }
.card-body   { padding: 0 1.5rem 1.5rem 1.5rem !important; }
.card-header .btn-light { height: 28px; padding: 0 10px; display: flex; align-items: center; justify-content: center; line-height: 1; }
.card-header .btn-light:hover { background-color: #f8f9fa; transition: background-color 0.2s ease-in-out; }
.card { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; }
.card-header h4 { color: #fff; font-weight: 600; margin-bottom: 0; display: flex; align-items: center; }
.card-header h4 i { margin-right: 0.25rem; }

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

/* ── Filter bar ──────────────────────────────────────────────────────────── */
.card-filter {
    background: #eef0f7; border-bottom: 1px solid #d6daf0;
    padding: 9px 1.5rem; display: flex; align-items: center;
    gap: 10px; flex-wrap: wrap;
}
.card-filter label { font-size: 12px; font-weight: 700; color: #4B5EBD; margin-bottom: 0; white-space: nowrap; }
.card-filter select { font-size: 12px; height: 30px; padding: 0 8px; border-radius: 6px; border: 1px solid #c8d0ed; background: #fff; }
.card-filter select:focus { outline: none; border-color: #4B5EBD; box-shadow: 0 0 0 2px rgba(75,94,189,0.12); }

/* ── Badges ──────────────────────────────────────────────────────────────── */
.outcome-success { background:#d1fae5;color:#065f46;border-radius:5px;padding:2px 8px;font-size:10px;font-weight:700; }
.outcome-failed  { background:#fee2e2;color:#991b1b;border-radius:5px;padding:2px 8px;font-size:10px;font-weight:700; }
.outcome-error   { background:#fef3c7;color:#92400e;border-radius:5px;padding:2px 8px;font-size:10px;font-weight:700; }
.ep-badge        { display:inline-block;border-radius:5px;padding:2px 7px;font-size:10px;font-weight:700;font-family:monospace;background:#eef0fa;color:#4B5EBD; }
.http-200 { color:#16a34a;font-weight:700; }
.http-4xx { color:#dc2626;font-weight:700; }
.http-5xx { color:#ea580c;font-weight:700; }
.dur-fast { color:#16a34a;font-weight:700; }
.dur-slow { color:#dc2626;font-weight:700; }
.dur-mid  { color:#d97706;font-weight:700; }
.trigger-badge     { font-size:10px;font-weight:600;border-radius:4px;padding:2px 7px; }
.trigger-manual    { background:#dbeafe;color:#1e40af; }
.trigger-scheduled { background:#d1fae5;color:#065f46; }
.trigger-reactive  { background:#fef3c7;color:#92400e; }

/* ── Expand row ──────────────────────────────────────────────────────────── */
.log-detail-row td { background:#f8f9ff;font-size:12px;padding:12px 16px; }
.log-detail-grid   { display:grid;grid-template-columns:1fr 1fr;gap:10px 20px; }
.log-detail-item label { font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;display:block;margin-bottom:3px; }
.log-detail-item .dv  { color:#1e293b;font-weight:600;word-break:break-all; }
.log-detail-item.full { grid-column:1/-1; }

/* ── Modal ───────────────────────────────────────────────────────────────── */
.mh-blue  { background:linear-gradient(135deg,#4B5EBD,#576CC0);padding:14px 18px !important;border-bottom:none; }
.mh-title { color:#fff;font-size:15px;font-weight:600;display:flex;align-items:center;gap:7px; }
.mh-close { filter:brightness(0) invert(1);opacity:.8; }
.mh-close:hover { opacity:1; }
.info-detail-table { width:100%;border-collapse:collapse;font-size:13px;margin-top:8px; }
.info-detail-table td { padding:8px 12px;border-bottom:1px solid #f1f5f9;vertical-align:top; }
.info-detail-table td:first-child { width:130px;font-weight:700;color:#475569;white-space:nowrap; }
.info-detail-table tr:last-child td { border-bottom:none; }

.log-main-row { cursor: pointer; }
</style>

<div class="content-page">
<div class="content">
<div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
      <i class="ri-history-line"></i> EIS Terminal Logs
    </h4>
    <div class="d-flex align-items-center" style="gap:4px;">
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="About Terminal Logs">
        <i class="ri-information-line"></i>
      </a>
      <a href="{{ route('tenant.admin.eis.dashboard') }}" class="btn btn-light text-primary fs-16 mx-1" title="EIS Dashboard">
        <i class="ri-arrow-left-line"></i>
      </a>
    </div>
  </div>

  <div class="card-filter">

    <form method="POST" action="{{ route('tenant.admin.update.filters') }}" style="margin:0;display:contents;">
      @csrf
      <input type="hidden" name="user_id" value="{{ Auth::id() }}">
      <label><i class="ri-store-line me-1"></i> Branch:</label>
      <select name="branch_id" style="min-width:190px;" onchange="this.form.submit()">
        <option value="">— Select a branch —</option>
        @foreach($branches as $b)
          <option value="{{ $b->id }}" {{ $pref->branch_id == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
        @endforeach
      </select>
    </form>

 
    <form method="POST" action="{{ route('tenant.admin.update.filters') }}" style="margin:0;display:contents;">
      @csrf
      <input type="hidden" name="user_id" value="{{ Auth::id() }}">
      <label><i class="ri-cpu-line me-1"></i> Terminal:</label>
      <select name="terminal_id" style="min-width:170px;" onchange="this.form.submit()">
        <option value="">— Select terminal —</option>
        @foreach($terminals as $t)
          <option value="{{ $t->id }}" {{ $pref->terminal_id == $t->id ? 'selected' : '' }}>{{ $t->terminal_label }} (Pos. {{ $t->terminal_position }})</option>
        @endforeach
      </select>
    </form>

    <form method="POST" action="{{ route('tenant.admin.update.filters') }}" style="margin:0;margin-left:auto;">
      @csrf
      <input type="hidden" name="user_id"     value="{{ Auth::id() }}">
      <input type="hidden" name="branch_id"   value="">
      <input type="hidden" name="terminal_id" value="">
      <input type="hidden" name="endpoint"    value="">
      <input type="hidden" name="outcome"     value="">
      <button type="submit" class="btn btn-sm btn-outline-secondary"
              style="font-size:11px;height:28px;display:inline-flex;align-items:center;gap:4px;padding:0 10px">
        <i class="ri-close-line"></i> Clear
      </button>
    </form>

  </div>

  <div class="card-body">
    <div class="tab-content" style="padding-top:1rem;">
      <div class="tab-pane show active">

        <table id="logsTable" class="table table-sm table-striped row-border order-column w-100">
          <thead>
            <tr>
              <th style="text-align:left !important;">Date / Time</th>
              <th style="text-align:left !important;">Endpoint</th>
              <th>Method</th>
              <th>HTTP</th>
              <th>MRA Code</th>
              <th>Outcome</th>
              <th>Duration</th>
              <th>Trigger</th>
              <th style="width:32px;"></th>
            </tr>
          </thead>
          <tbody>
            @foreach($logs as $l)
              @php
                $httpClass = match(true) {
                  $l->http_status === 200 || $l->http_status === 201 => 'http-200',
                  $l->http_status >= 400 && $l->http_status < 500    => 'http-4xx',
                  $l->http_status >= 500                              => 'http-5xx',
                  default                                             => 'text-muted',
                };
                $durClass = match(true) {
                  !$l->duration_ms         => 'text-muted',
                  $l->duration_ms < 500    => 'dur-fast',
                  $l->duration_ms < 1500   => 'dur-mid',
                  default                  => 'dur-slow',
                };
                $triggerClass = match($l->trigger_source ?? '') {
                  'scheduled' => 'trigger-scheduled',
                  'reactive'  => 'trigger-reactive',
                  default     => 'trigger-manual',
                };
                $outcomeClass = match($l->outcome ?? '') {
                  'success' => 'outcome-success',
                  'failed'  => 'outcome-failed',
                  'error'   => 'outcome-error',
                  default   => '',
                };
              @endphp
              <tr class="log-main-row" data-id="{{ $l->id }}">
                <td style="font-size:11px;white-space:nowrap;color:#64748b;text-align:left !important;">
                  {{ \Carbon\Carbon::parse($l->created_at)->format('d M Y, H:i:s') }}
                </td>
                <td style="text-align:left !important;">
                  <span class="ep-badge">{{ $l->endpoint }}</span>
                </td>
                <td>
                  @php $mc = ['GET'=>'#0284c7','POST'=>'#16a34a','PUT'=>'#d97706','DELETE'=>'#dc2626'][$l->http_method] ?? '#64748b'; @endphp
                  <span style="font-size:10px;font-weight:800;color:{{ $mc }}">{{ $l->http_method }}</span>
                </td>
                <td>
                  @if($l->http_status)
                    <span class="{{ $httpClass }}">{{ $l->http_status }}</span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  @if($l->mra_status_code !== null)
                    <span style="font-size:11px;font-weight:600;color:#475569">{{ $l->mra_status_code }}</span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  <span class="{{ $outcomeClass }}">
                    @if($l->outcome === 'success')    <i class="ri-check-line me-1"></i>Success
                    @elseif($l->outcome === 'failed') <i class="ri-close-line me-1"></i>Failed
                    @elseif($l->outcome === 'error')  <i class="ri-alert-line me-1"></i>Error
                    @else {{ $l->outcome }}
                    @endif
                  </span>
                </td>
                <td class="{{ $durClass }}">{{ $l->duration_ms ? $l->duration_ms.'ms' : '—' }}</td>
                <td><span class="trigger-badge {{ $triggerClass }}">{{ $l->trigger_source ?? '—' }}</span></td>
                <td style="text-align:center;padding:0 8px;">
                  <i class="ri-arrow-down-s-line expand-icon"
                     style="font-size:16px;color:#c8d0ed;transition:transform 0.2s;"></i>
                </td>
              </tr>

              <tr class="log-detail-row" id="detail_{{ $l->id }}" style="display:none;">
                <td colspan="9">
                  <div class="log-detail-grid">
                    <div class="log-detail-item full">
                      <label>Outcome Message</label>
                      <div class="dv">{{ $l->outcome_message ?: '—' }}</div>
                    </div>
                    <div class="log-detail-item full">
                      <label>MRA Remark</label>
                      <div class="dv">{{ $l->mra_remark ?: '—' }}</div>
                    </div>
                    <div class="log-detail-item">
                      <label>Branch ID</label>
                      <div class="dv">{{ $l->branch_id ?? '—' }}</div>
                    </div>
                    <div class="log-detail-item">
                      <label>Terminal ID</label>
                      <div class="dv">{{ $l->terminal_id ?? '—' }}</div>
                    </div>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>

      </div>
    </div>
  </div>

</div>
</div></div></div>

{{-- INFO MODAL --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false"
     tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content"
         style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title">
          <i class="ri-information-line"></i> Understanding Terminal Logs
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:18px 20px;">
        <p class="mb-2"><strong>What are terminal logs?</strong><br>
        Every time a terminal communicates with the MRA EIS server — to activate, confirm, sync config, or ping — a log entry is automatically created.</p>
        <hr class="my-3">
        <p class="mb-2 fw-bold">Column explanations:</p>
        <table class="info-detail-table">
          <tbody>
            <tr>
              <td><span class="ep-badge">endpoint</span></td>
              <td>The specific MRA API action: <code>activate</code>, <code>confirm</code>, <code>get_config</code>, <code>ping</code>.</td>
            </tr>
            <tr>
              <td>Method</td>
              <td>HTTP method — POST for most calls, GET for read-only calls like ping.</td>
            </tr>
            <tr>
              <td>HTTP Status</td>
              <td>
                <strong style="color:#16a34a">200/201</strong> = success.
                <strong style="color:#dc2626">4xx</strong> = request problem.
                <strong style="color:#ea580c">5xx</strong> = MRA server error.
              </td>
            </tr>
            <tr>
              <td>MRA Code</td>
              <td>MRA's internal application response code. Refer to MRA EIS developer docs.</td>
            </tr>
            <tr>
              <td>Outcome</td>
              <td>
                <span class="outcome-success me-1"><i class="ri-check-line"></i> Success</span> MRA accepted.<br>
                <span class="outcome-failed me-1"><i class="ri-close-line"></i> Failed</span> MRA rejected.<br>
                <span class="outcome-error me-1"><i class="ri-alert-line"></i> Error</span> Network or system error.
              </td>
            </tr>
            <tr>
              <td>Duration</td>
              <td>
                <strong style="color:#16a34a">Under 500ms</strong> fast.
                <strong style="color:#d97706">500–1500ms</strong> moderate.
                <strong style="color:#dc2626">Over 1500ms</strong> may indicate server load or network issues.
              </td>
            </tr>
            <tr>
              <td>Trigger</td>
              <td>
                <span class="trigger-badge trigger-manual me-1">manual</span> User clicked a button.<br>
                <span class="trigger-badge trigger-scheduled me-1">scheduled</span> Automated background job.<br>
                <span class="trigger-badge trigger-reactive me-1">reactive</span> System-triggered in response to an event.
              </td>
            </tr>
          </tbody>
        </table>
        <hr class="my-3">
        <p class="mb-0">
          <strong><i class="ri-lightbulb-line me-1 text-warning"></i> Tip:</strong>
          Click any row to expand and see the full outcome message and MRA's remark.
        </p>
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
$(function () {

    $('#logsTable').DataTable({
        scrollX:    false,
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
        pageLength: 25,
        order:      [[0, 'desc']],
        dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
        columnDefs: [{ orderable: false, targets: 8 }],
        initComplete: function () { this.api().columns.adjust().draw(false); }
    });

    $('#infoBtn').on('click', function (e) {
        e.preventDefault();
        $('#infoModal').modal('show');
    });

    var openId = null;

    $(document).on('click', '.log-main-row', function () {
        var id  = $(this).data('id');
        var row = $('#detail_' + id);
        var ico = $(this).find('.expand-icon');

        if (openId && openId !== id) {
            $('#detail_' + openId).slideUp(150);
            $('.log-main-row[data-id="' + openId + '"] .expand-icon')
                .css({ transform: 'rotate(0deg)', color: '#c8d0ed' });
        }

        if (row.is(':visible')) {
            row.slideUp(150);
            ico.css({ transform: 'rotate(0deg)', color: '#c8d0ed' });
            openId = null;
        } else {
            row.slideDown(150);
            ico.css({ transform: 'rotate(180deg)', color: '#4B5EBD' });
            openId = id;
        }
    });

});
</script>
@endsection