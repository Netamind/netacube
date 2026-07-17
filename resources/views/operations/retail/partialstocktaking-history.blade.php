@extends('operations.retail.dashboard')
@section('content')

@php
    use Carbon\Carbon;

    $branches = DB::connection('tenant')->table('branches')
        ->where('sector', 'Retail')
        ->where('status', 'active')
        ->orderBy('name')
        ->get();

    // Branch filter from query string; default to all
    $filterBranchId = request()->query('branch_id');

    $query = DB::connection('tenant')
        ->table('retail_partialstocktaking_summary as s')
        ->join('branches as b', 'b.id', '=', 's.branch_id')
        ->leftJoin('users as u', 'u.id', '=', 's.rectified_by_user_id')
        ->select(
            's.id', 's.date', 's.branch_id', 's.status',
            's.products_counted', 's.products_no_anomaly',
            's.products_overage', 's.products_shortage',
            's.expected_value', 's.found_value', 's.difference_value',
            's.remarks',
            's.started_at', 's.updated_at',
            's.rectified_by_user_id',
            'b.name as branch_name',
            'u.name as rectified_by'
        )
        ->where('s.status', 'completed')
        ->orderByDesc('s.date')
        ->orderByDesc('s.updated_at');

    if ($filterBranchId) {
        $query->where('s.branch_id', $filterBranchId);
    }

    $history = $query->get();
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
/* ── DataTable export buttons ─────────────────────────────────────── */
.dt-buttons .btn { background:transparent !important; background-image:none !important; box-shadow:none !important; border-color:#5bc0de; color:#5bc0de; }
.dt-buttons .btn:hover { background:#5bc0de !important; color:#fff; }

/* ── Card chrome ─────────────────────────────────────────────────── */
.card-header { padding:0.5rem 1.5rem !important; background:linear-gradient(to right,#4B5EBD,#576CC0); color:#fff; border-radius:10px 10px 0 0 !important; flex-wrap:wrap; gap:8px; }
.card-body   { padding:0 1.5rem 1.5rem 1.5rem !important; }
.card        { border:none; box-shadow:0 4px 8px rgba(0,0,0,0.1); border-radius:10px; }
.card-header h4 { color:#fff; font-weight:600; margin-bottom:0; display:flex; align-items:center; }
.card-header .btn-light { height:28px; padding:0 10px; display:flex; align-items:center; justify-content:center; line-height:1; }
.card-header .btn-light:hover { background-color:#f8f9fa; transition:background-color 0.2s; }

/* ── Header: title block + branch select (matches Branch Products pattern) ── */
.header-title-block { display:flex; flex-direction:column; line-height:1.25; min-width:0; }

/* ── Card header action buttons row ───────────────────────────────── */
.card-header-actions { display:flex; align-items:center; gap:4px; flex-wrap:wrap; justify-content:flex-end; }

/* ── Branch select in header ─────────────────────────────────────── */
#histBranchSelect { border:none; background:transparent; color:#fff; font-size:13px; font-weight:600; cursor:pointer; padding:0; outline:none; max-width:300px; margin-top:2px; }
#histBranchSelect option { color:#1e293b; background:#fff; font-size:14px; }

/* ── Mobile ───────────────────────────────────────────────────────── */
@media (max-width: 576px) {
  .card-header { padding:10px 14px !important; flex-wrap:nowrap; }
  .header-title { flex:1 1 auto; min-width:0; }
  .header-title-block { max-width:none; min-width:0; }
  #histBranchSelect { font-size:12px; max-width:100%; }
  .card-header-actions { flex:0 0 auto; width:auto; justify-content:flex-end; }
  .card-header .btn-light { height:32px; width:32px; padding:0; font-size:15px; }
}

/* ── Table alignment ─────────────────────────────────────────────── */
#historyTable thead th, table.dataTable thead th { text-align:center !important; vertical-align:middle !important; }
#historyTable thead th:first-child, table.dataTable thead th:first-child { text-align:left !important; }
#historyTable tbody td, table.dataTable tbody td { text-align:center !important; vertical-align:middle !important; }
#historyTable tbody td:first-child, table.dataTable tbody td:first-child { text-align:left !important; }

/* ── Fixed column style ──────────────────────────────────────────── */
table.dataTable.fixedHeader-floating,
table.dataTable.fixedHeader-locked { background:#fff !important; border-bottom:none !important; }
table.dataTable thead th.fixedHeader-floating { background:#e2e2e9 !important; }

.diff-pos { color:#059669; font-weight:700; }
.diff-neg { color:#dc2626; font-weight:700; }
.diff-zero { color:#64748b; }

.hist-view-btn { font-size:11px; font-weight:600; color:#4B5EBD; text-decoration:none; white-space:nowrap; cursor:pointer; }
.hist-view-btn:hover { text-decoration:underline; }

/* ── Modal header helpers ────────────────────────────────────────── */
.mh-blue  { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-title { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }

/* Quick-stats modal */
.qs-row { display:flex; justify-content:space-between; padding:9px 0; border-bottom:1px solid #f1f5f9; font-size:13px; }
.qs-row:last-child { border-bottom:none; }
.qs-row .lbl { color:#64748b; }
.qs-row .val { font-weight:700; color:#1e293b; }
.qs-row .val.pos { color:#059669; }
.qs-row .val.neg { color:#dc2626; }
.qs-remarks { font-size:12.5px; color:#78350f; background:#fffbeb; border:1px solid #fcd34d; border-radius:6px; padding:8px 10px; margin-top:6px; white-space:pre-wrap; }
.qs-remarks-empty { color:#94a3b8; font-style:italic; }
</style>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

  {{-- ── Card header: title + branch select (same pattern as Branch Products) ── --}}
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
      <a href="{{ route('retail.operations.partialstocktaking') }}" class="btn btn-light text-primary fs-16 mx-1" title="Back to Stocktaking" style="margin-right:8px;">
        <i class="ri-arrow-left-line"></i>
      </a>
      <form method="GET" action="{{ route('retail.operations.partialstocktaking.history') }}" id="histBranchForm" style="margin:0;display:inline;">
        <div class="header-title-block">
          Partial Stocktaking History
          <select name="branch_id" id="histBranchSelect" onchange="document.getElementById('histBranchForm').submit()">
            <option value="">All Branches</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ $filterBranchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
          </select>
        </div>
      </form>
    </h4>

    <div class="card-header-actions">
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download">
        <i class="ri-download-line"></i>
      </a>
    </div>
  </div>

  <div class="card-body">
    <table id="historyTable" class="table table-sm table-striped row-border order-column w-100 mt-3">
      <thead style="background-color:#e2e2e9">
        <tr>
          <th>Branch</th>
          <th>Date</th>
          <th>Counted</th>
          <th>No Anomaly</th>
          <th>Overage</th>
          <th>Shortage</th>
          <th>Expected Value</th>
          <th>Found Value</th>
          <th>Difference</th>
          <th>Rectified By</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($history as $h)
        @php $diff = $h->difference_value; @endphp
        <tr>
          <td>{{ $h->branch_name }}</td>
          <td>{{ Carbon::parse($h->date)->format('d M Y') }}</td>
          <td>{{ $h->products_counted }}</td>
          <td>{{ $h->products_no_anomaly }}</td>
          <td>{{ $h->products_overage }}</td>
          <td>{{ $h->products_shortage }}</td>
          <td>{{ number_format($h->expected_value, 2) }}</td>
          <td>{{ number_format($h->found_value, 2) }}</td>
          <td class="{{ $diff > 0.01 ? 'diff-pos' : ($diff < -0.01 ? 'diff-neg' : 'diff-zero') }}">
              {{ number_format($diff, 2) }}
          </td>
          <td>{{ $h->rectified_by ?? '—' }}</td>
          <td>
              <a href="#" class="hist-view-btn histOpenBtn"
                 data-branch-id="{{ $h->branch_id }}"
                 data-date="{{ $h->date }}">
                  <i class="ri-eye-line me-1"></i>View
              </a>
              &nbsp;
              <a href="#" class="hist-view-btn quickStatsBtn"
                 data-id="{{ $h->id }}"
                 data-branch-id="{{ $h->branch_id }}"
                 data-date="{{ $h->date }}"
                 data-branch="{{ $h->branch_name }}"
                 data-displaydate="{{ Carbon::parse($h->date)->format('d M Y') }}"
                 data-counted="{{ $h->products_counted }}"
                 data-noanomaly="{{ $h->products_no_anomaly }}"
                 data-overage="{{ $h->products_overage }}"
                 data-shortage="{{ $h->products_shortage }}"
                 data-expected="{{ number_format($h->expected_value,2) }}"
                 data-found="{{ number_format($h->found_value,2) }}"
                 data-diff="{{ number_format($diff,2) }}"
                 data-by="{{ $h->rectified_by ?? '—' }}"
                 data-remarks="{{ $h->remarks }}">
                  <i class="ri-bar-chart-2-line me-1"></i>Stats
              </a>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
</div></div></div>

{{-- ══ QUICK STATS MODAL ════════════════════════════════════════════════ --}}
<div class="modal fade" id="quickStatsModal" tabindex="-1">
  <div class="modal-dialog" style="max-width:400px;">
    <div class="modal-content">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-bar-chart-2-line"></i> Session Summary</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:18px 20px;">
        <div class="qs-row"><span class="lbl">Branch</span><span class="val" id="qsBranch"></span></div>
        <div class="qs-row"><span class="lbl">Date</span><span class="val" id="qsDate"></span></div>
        <div class="qs-row"><span class="lbl">Products counted</span><span class="val" id="qsCounted"></span></div>
        <div class="qs-row"><span class="lbl">No anomaly</span><span class="val" id="qsNoAnomaly"></span></div>
        <div class="qs-row"><span class="lbl">Overages</span><span class="val" id="qsOverage"></span></div>
        <div class="qs-row"><span class="lbl">Shortages</span><span class="val" id="qsShortage"></span></div>
        <div class="qs-row"><span class="lbl">Expected value</span><span class="val" id="qsExpected"></span></div>
        <div class="qs-row"><span class="lbl">Found value</span><span class="val" id="qsFound"></span></div>
        <div class="qs-row"><span class="lbl">Difference (FV − EV)</span><span class="val" id="qsDiff"></span></div>
        <div class="qs-row"><span class="lbl">Rectified by</span><span class="val" id="qsBy" style="font-size:12px;"></span></div>
        <div style="margin-top:10px;">
          <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Remarks</div>
          <div class="qs-remarks" id="qsRemarks"></div>
        </div>
      </div>
      <div class="modal-footer" style="padding:10px 18px 14px;">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <a href="#" class="btn btn-primary btn-sm" id="qsViewBtn"><i class="ri-eye-line me-1"></i> View Details</a>
      </div>
    </div>
  </div>
</div>

{{-- ══ DOWNLOAD MODAL (matches Branch Products pattern) ════════════════ --}}
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue">
      <h5 class="modal-title mh-title"><i class="ri-download-line"></i> Download</h5>
      <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <div class="buttons"></div>
    </div>
  </div></div>
</div>

@endsection
@section('scripts')
<script>
'use strict';

function pstHistCsrf() { return document.querySelector('meta[name="csrf-token"]').getAttribute('content'); }

/* History summaries are keyed by branch+date, not by "today's session", so
   opening one has to point Actions & Info / Stocktaking at that branch+date
   first — the same user_filters row every other tab reads from. This posts
   the filter update, then navigates, rather than needing a separate
   query-string-driven details page (Partial Stocktaking has none). */
function pstHistOpen(branchId, date) {
    fetch('{{ route("tenant.admin.update.filters") }}', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': pstHistCsrf(), 'Accept': 'application/json' },
        body:    JSON.stringify({ user_id: '{{ Auth::id() }}', branch_id: branchId, pst_custom_date: date }),
    })
    .then(() => { window.location = '{{ route("retail.operations.partialstocktaking.actions-and-info") }}'; })
    .catch(() => { toastr.error('Could not switch to that branch/date. Try again.', 'Network Error'); });
}

$(document).ready(function () {
    var csrf = $('meta[name="csrf-token"]').attr('content');
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrf } });

    var table = $('#historyTable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        pageLength: 25,
        lengthMenu: [[25,50,100,-1],[25,50,100,'All']],
        order: [[1,'desc']],
        fixedColumns: { leftColumns: 1 },
        scrollX: true,
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: 0,      className: 'text-start'  },
            { targets: -1,     orderable: false         },
        ],
        buttons: [
            { extend: 'excelHtml5', title: 'Partial Stocktaking History', exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'csvHtml5',   title: 'Partial Stocktaking History', exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'pdfHtml5',   title: 'Partial Stocktaking History', exportOptions: { columns: ':visible:not(:last-child)' },
              customize: function(doc) {
                  doc.content[1].table.widths = Array(doc.content[1].table.body[0].length+1).join('*').split('');
                  doc.content[1].table.body.forEach(function(row){ row[0].alignment='left'; for(var j=1;j<row.length;j++) row[j].alignment='center'; });
              }
            }
        ]
    });

    table.buttons().container().appendTo($('#buttonsModal .buttons'));
    $('#tableButtonsBtn').on('click', function(e) { e.preventDefault(); $('#buttonsModal').modal('show'); });

    $(document).on('click', '.histOpenBtn', function (e) {
        e.preventDefault();
        pstHistOpen($(this).data('branch-id'), $(this).data('date'));
    });

    $(document).on('click', '.quickStatsBtn', function (e) {
        e.preventDefault();
        var d = $(this).data();
        $('#qsBranch').text(d.branch);
        $('#qsDate').text(d.displaydate);
        $('#qsCounted').text(d.counted);
        $('#qsNoAnomaly').text(d.noanomaly);
        $('#qsOverage').text(d.overage);
        $('#qsShortage').text(d.shortage);
        $('#qsExpected').text(d.expected);
        $('#qsFound').text(d.found);

        var diff = parseFloat(String(d.diff).replace(/,/g,''));
        $('#qsDiff').text(d.diff).removeClass('pos neg').addClass(diff > 0.01 ? 'pos' : (diff < -0.01 ? 'neg' : ''));
        $('#qsBy').text(d.by);

        var remarksEl = $('#qsRemarks');
        if (d.remarks && String(d.remarks).trim().length) {
            remarksEl.removeClass('qs-remarks-empty').text(d.remarks);
        } else {
            remarksEl.addClass('qs-remarks-empty').text('No remarks were recorded for this stocktake.');
        }

        // Wire "View Details" button inside modal — same branch+date filter-swap as the row's own View link
        $('#qsViewBtn').off('click').on('click', function (e2) {
            e2.preventDefault();
            $('#quickStatsModal').modal('hide');
            pstHistOpen(d['branch-id'], d.date);
        });

        $('#quickStatsModal').modal('show');
    });

    @if(Session::has('message'))
    toastr['{{ Session::get("alert-type","info") }}']('{{ Session::get("message") }}');
    @endif
});
</script>
@endsection