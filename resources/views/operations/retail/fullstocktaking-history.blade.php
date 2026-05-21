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
        ->table('retail_fullstocktaking_summary as s')
        ->join('branches as b', 'b.id', '=', 's.branch_id')
        ->leftJoin('users as u', 'u.id', '=', 's.rectified_by_user_id')
        ->select(
            's.id', 's.date', 's.branch_id', 's.status',
            's.products_counted', 's.products_no_anomaly',
            's.products_overage', 's.products_shortage',
            's.expected_value', 's.found_value', 's.difference_value',
            's.missing_count', 's.missing_value', 's.full_difference_value',
            's.started_at', 's.updated_at',
            's.rectified_by_user_id',
            'b.name as branch_name',
            DB::raw("CONCAT(u.first_name, ' ', u.last_name) as rectified_by")
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
.card-header { padding: 0.5rem 1.5rem !important; background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff; border-top-left-radius: 10px; border-top-right-radius: 10px; }
.card-body { padding: 0 !important; }
.card { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden; }
.card-header h4 { color: #fff; font-weight: 600; margin-bottom: 0; display: flex; align-items: center; gap: 6px; }
.card-header .btn-light { height: 28px; padding: 0 10px; display: flex; align-items: center; justify-content: center; line-height: 1; font-size: 16px; }
.card-header .btn-light:hover { background-color: #f8f9fa; }

.hist-action-bar { display: flex; align-items: center; gap: 10px; background: #9098a8; padding: 8px 14px; border-bottom: 1px solid #7a8090; flex-wrap: wrap; }

#histBranchSelect { border: 1.5px solid rgba(255,255,255,0.35); background: #9098a8; border-radius: 7px; padding: 5px 10px; font-size: 12.5px; font-weight: 600; color: #dde0e8; max-width: 220px; height: 32px; }

.dt-buttons .btn { background: transparent !important; background-image: none !important; box-shadow: none !important; border-color: #5bc0de; color: #5bc0de; }
.dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }
.table-wrapper { overflow-x: auto; padding: 0 16px 20px; }
table.dataTable tbody td { padding-top: 6px !important; padding-bottom: 6px !important; }

#historyTable thead th { text-align: center !important; vertical-align: middle !important; }
#historyTable thead th:first-child { text-align: left !important; }
#historyTable tbody td { text-align: center !important; vertical-align: middle !important; }
#historyTable tbody td:first-child { text-align: left !important; }

.diff-pos { color: #059669; font-weight: 700; }
.diff-neg { color: #dc2626; font-weight: 700; }
.diff-zero { color: #64748b; }

.status-badge { font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 8px; }
.status-completed { background: #d1fae5; color: #065f46; }

.hist-view-btn { font-size: 11px; font-weight: 600; color: #4B5EBD; text-decoration: none; white-space: nowrap; }
.hist-view-btn:hover { text-decoration: underline; }

.mh-blue  { background: linear-gradient(135deg,#4B5EBD,#576CC0); padding: 12px 18px !important; border-bottom: none; }
.mh-title { color: #fff; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.mh-close { filter: brightness(0) invert(1); opacity: .8; }
.mh-close:hover { opacity: 1; }

/* Quick-stats modal */
.qs-row { display: flex; justify-content: space-between; padding: 9px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
.qs-row:last-child { border-bottom: none; }
.qs-row .lbl { color: #64748b; }
.qs-row .val { font-weight: 700; color: #1e293b; }
.qs-row .val.pos { color: #059669; }
.qs-row .val.neg { color: #dc2626; }
</style>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4><i class="ri-history-line me-1"></i> Stocktaking History</h4>
        <div class="d-flex align-items-center" style="gap:4px;">
            <a href="{{ route('retail.operations.fullstocktaking') }}" class="btn btn-light text-primary" title="Back to Stocktaking">
                <i class="ri-arrow-left-line"></i>
            </a>
        </div>
    </div>

    <div class="hist-action-bar">
        <form method="GET" action="{{ route('retail.operations.fullstocktaking.history') }}" style="margin:0;display:flex;align-items:center;gap:8px;">
            <select name="branch_id" id="histBranchSelect" onchange="this.form.submit()">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ $filterBranchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        </form>
        <span style="font-size:12px;color:#dde0e8;font-weight:600;">
            {{ $history->count() }} session{{ $history->count() === 1 ? '' : 's' }} found
        </span>
    </div>

    <div class="card-body">
        <div class="table-wrapper" style="margin-top:14px;">
            <table id="historyTable" class="table table-sm table-striped row-border order-column w-100">
                <thead style="background-color:#e2e2e9">
                    <tr>
                        <th>Branch</th>
                        <th>Date</th>
                        <th>Counted</th>
                        <th>No Anomaly</th>
                        <th>Overage</th>
                        <th>Shortage</th>
                        <th>Missing</th>
                        <th>Expected Value</th>
                        <th>Found Value</th>
                        <th>Difference</th>
                        <th>Full Difference</th>
                        <th>Rectified By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($history as $h)
                    @php $diff = $h->difference_value; $fullDiff = $h->full_difference_value; @endphp
                    <tr>
                        <td>{{ $h->branch_name }}</td>
                        <td>{{ Carbon::parse($h->date)->format('d M Y') }}</td>
                        <td>{{ $h->products_counted }}</td>
                        <td>{{ $h->products_no_anomaly }}</td>
                        <td>{{ $h->products_overage }}</td>
                        <td>{{ $h->products_shortage }}</td>
                        <td>{{ $h->missing_count }}</td>
                        <td>{{ number_format($h->expected_value, 2) }}</td>
                        <td>{{ number_format($h->found_value, 2) }}</td>
                        <td class="{{ $diff > 0.01 ? 'diff-pos' : ($diff < -0.01 ? 'diff-neg' : 'diff-zero') }}">
                            {{ number_format($diff, 2) }}
                        </td>
                        <td class="{{ $fullDiff > 0.01 ? 'diff-pos' : ($fullDiff < -0.01 ? 'diff-neg' : 'diff-zero') }}">
                            {{ number_format($fullDiff, 2) }}
                        </td>
                        <td>{{ $h->rectified_by ?? '—' }}</td>
                        <td>
                            <a href="{{ route('retail.operations.fullstocktaking.history.details') }}?branch_id={{ $h->branch_id }}&date={{ $h->date }}"
                               class="hist-view-btn">
                                <i class="ri-eye-line me-1"></i>View
                            </a>
                            &nbsp;
                            <a href="#" class="hist-view-btn quickStatsBtn"
                               data-id="{{ $h->id }}"
                               data-branch="{{ $h->branch_name }}"
                               data-date="{{ Carbon::parse($h->date)->format('d M Y') }}"
                               data-counted="{{ $h->products_counted }}"
                               data-noanomaly="{{ $h->products_no_anomaly }}"
                               data-overage="{{ $h->products_overage }}"
                               data-shortage="{{ $h->products_shortage }}"
                               data-missing="{{ $h->missing_count }}"
                               data-expected="{{ number_format($h->expected_value,2) }}"
                               data-found="{{ number_format($h->found_value,2) }}"
                               data-diff="{{ number_format($diff,2) }}"
                               data-fulldiff="{{ number_format($fullDiff,2) }}"
                               data-by="{{ $h->rectified_by ?? '—' }}">
                                <i class="ri-bar-chart-2-line me-1"></i>Stats
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
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
                <div class="qs-row"><span class="lbl">Missing</span><span class="val" id="qsMissing"></span></div>
                <div class="qs-row"><span class="lbl">Expected value</span><span class="val" id="qsExpected"></span></div>
                <div class="qs-row"><span class="lbl">Found value</span><span class="val" id="qsFound"></span></div>
                <div class="qs-row"><span class="lbl">Difference (FV − EV)</span><span class="val" id="qsDiff"></span></div>
                <div class="qs-row"><span class="lbl">Full difference</span><span class="val" id="qsFullDiff"></span></div>
                <div class="qs-row"><span class="lbl">Rectified by</span><span class="val" id="qsBy" style="font-size:12px;"></span></div>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                <a href="#" class="btn btn-primary btn-sm" id="qsViewBtn"><i class="ri-eye-line me-1"></i> View Details</a>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script>
$(document).ready(function () {
    var csrf = $('meta[name="csrf-token"]').attr('content');
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrf } });

    $('#historyTable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6 text-end"B>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
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
            { extend: 'excelHtml5', text: 'Excel', title: 'Stocktaking History', exportOptions: { columns: ':not(:last-child)' } },
            { extend: 'csvHtml5',   text: 'CSV',   title: 'Stocktaking History', exportOptions: { columns: ':not(:last-child)' } },
            { extend: 'pdfHtml5',   text: 'PDF',   title: 'Stocktaking History', exportOptions: { columns: ':not(:last-child)' } },
        ],
    });

    $(document).on('click', '.quickStatsBtn', function (e) {
        e.preventDefault();
        var d = $(this).data();
        $('#qsBranch').text(d.branch);
        $('#qsDate').text(d.date);
        $('#qsCounted').text(d.counted);
        $('#qsNoAnomaly').text(d.noanomaly);
        $('#qsOverage').text(d.overage);
        $('#qsShortage').text(d.shortage);
        $('#qsMissing').text(d.missing);
        $('#qsExpected').text(d.expected);
        $('#qsFound').text(d.found);

        var diff     = parseFloat(String(d.diff).replace(/,/g,''));
        var fullDiff = parseFloat(String(d.fulldiff).replace(/,/g,''));

        $('#qsDiff').text(d.diff).removeClass('pos neg').addClass(diff > 0.01 ? 'pos' : (diff < -0.01 ? 'neg' : ''));
        $('#qsFullDiff').text(d.fulldiff).removeClass('pos neg').addClass(fullDiff > 0.01 ? 'pos' : (fullDiff < -0.01 ? 'neg' : ''));
        $('#qsBy').text(d.by);

        // Wire "View Details" button inside modal
        var branchId  = $(this).closest('tr').find('a.hist-view-btn').first().attr('href');
        $('#qsViewBtn').attr('href', '{{ route("retail.operations.fullstocktaking.history.details") }}?branch_id=' + encodeURIComponent('') + '&date=');
        // Re-derive from the view link
        var viewHref = $(this).siblings('a.hist-view-btn').first().attr('href');
        if (viewHref) { $('#qsViewBtn').attr('href', viewHref); }

        $('#quickStatsModal').modal('show');
    });

    @if(Session::has('message'))
    toastr['{{ Session::get("alert-type","info") }}']('{{ Session::get("message") }}');
    @endif
});
</script>
@endsection