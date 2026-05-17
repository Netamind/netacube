@extends('operations.retail.dashboard')
@section('content')
@php
    use Carbon\Carbon;

    $pref       = DB::connection('tenant')->table('user_filters')->where('user_id', Auth::id())->first();
    $categories = DB::connection('tenant')->table('categories')->orderBy('category')->get();

    $selectedCategory = null;

    $customDate  = $pref->dnote_custom_date ?? null;
    $isCustom    = !empty($customDate);
    $date        = $isCustom ? $customDate : Carbon::today()->toDateString();
    $displayDate = Carbon::parse($date)->format('d M Y');

    // All price changes for the selected date (no product filter)
    $priceChanges = collect();
    if ($selectedCategory = ($pref && $pref->category_id
        ? DB::connection('tenant')->table('categories')->where('id', $pref->category_id)->first()
        : null)) {

        $priceChanges = DB::connection('tenant')
            ->table('retail_price_changes as rpc')
            ->leftJoin('users as u', 'u.id', '=', 'rpc.changed_by')
            ->where('rpc.change_date', $date)
            ->select(
                'rpc.id',
                'rpc.branch_id',
                'rpc.product_name',
                'rpc.product_code',
                'rpc.product_unit',
                'rpc.branch_name',
                'rpc.old_price',
                'rpc.new_price',
                'rpc.reason',
                'rpc.created_at',
                'u.name as changed_by_name'
            )
            ->orderByDesc('rpc.created_at')
            ->get();
    }

    $baseCount        = $priceChanges->whereNull('branch_id')->count();
    $branchCount      = $priceChanges->whereNotNull('branch_id')->count();
    $distinctBranches = $priceChanges->whereNotNull('branch_id')->pluck('branch_name')->filter()->unique()->count();

    $maintableTitle = 'Price Changes — ' . $displayDate;
@endphp

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

<style>
#progressBar { height: 3px; display: none; transform: rotate(180deg); }

.card      { border: none; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border-radius: 12px; }
.card-header {
    padding: 0 !important; background: #4B5EBD;
    border-radius: 12px 12px 0 0 !important; border: none;
}
.ch-inner {
    display: flex; align-items: center;
    padding: 0 14px; height: 48px; gap: 8px; flex-wrap: nowrap;
}
.ch-left  { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; overflow: hidden; }
.ch-right { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }

#categorySelectHeader {
    border: none; background: transparent; color: #fff;
    font-size: 15px; font-weight: 600; cursor: pointer;
    padding: 0; outline: none; flex: 0 1 auto;
    min-width: 0; max-width: 200px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
#categorySelectHeader option { color: #1e293b; background: #fff; font-size: 13px; }

.ch-sep { width: 1px; height: 20px; background: rgba(255,255,255,0.25); flex-shrink: 0; }

.ch-date-chip {
    display: inline-flex; align-items: center; gap: 4px;
    background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25);
    border-radius: 20px; padding: 5px 10px;
    font-size: 11px; font-weight: 500; color: #fff;
    white-space: nowrap; cursor: default; user-select: none; flex-shrink: 0;
}
.ch-date-chip .mode-badge {
    font-size: 9px; padding: 1px 5px; border-radius: 8px;
    background: rgba(255,255,255,0.2); color: #fff;
    font-weight: 600; letter-spacing: .3px;
}
.ch-date-chip.custom-mode { background: rgba(245,158,11,0.30); border-color: rgba(245,158,11,0.6); }
.ch-date-chip.custom-mode .mode-badge { background: rgba(245,158,11,0.5); }

.ch-btn {
    width: 30px; height: 30px; border-radius: 7px;
    background: #fff; border: 1px solid rgba(255,255,255,0.6);
    color: #4B5EBD; display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 14px; transition: background .15s, box-shadow .15s;
    text-decoration: none; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}
.ch-btn:hover { background: #f0f2ff; color: #3a4ca0; box-shadow: 0 2px 6px rgba(0,0,0,0.15); }

.tab-header-container { background: #f8f9fa; border-bottom: 1px solid #dee2e6; overflow-x: auto; }
.nav-pills { flex-wrap: nowrap; }
.nav-pills .nav-link {
    border-radius: 0 !important; padding: .5rem 1rem;
    font-weight: 500; font-size: 12px; color: #6c757d;
    border-bottom: 3px solid transparent; transition: all .2s; white-space: nowrap;
}
.nav-pills .nav-link:hover  { background: #e9ecef; color: #4B5EBD; }
.nav-pills .nav-link.active {
    background: transparent !important; color: #4B5EBD !important;
    border-bottom-color: #4B5EBD; font-weight: 600;
}
.nav-pills .nav-link i { font-size: .95rem; margin-right: .3rem; }

/* ── Stat chips row ─────────────────────────────────────────────────── */
.stat-chips-row {
    display: flex; align-items: center; gap: 8px;
    background: #eef0f8; border-bottom: 1px solid #dde1f0;
    padding: 8px 14px;
}
.stat-chip {
    display: inline-flex; align-items: center; gap: 6px;
    background: #fff; border: 1.5px solid #c5caec;
    border-radius: 8px; padding: 5px 12px; white-space: nowrap;
}
.stat-chip-label {
    font-size: 10px; font-weight: 700; color: #94a3b8;
    text-transform: uppercase; letter-spacing: .6px;
}
.stat-chip-val {
    font-size: 14px; font-weight: 800; font-variant-numeric: tabular-nums;
}
.stat-chip-val.base-clr    { color: #4B5EBD; }
.stat-chip-val.branch-clr  { color: #d97706; }
.stat-chip-val.distinct-clr { color: #059669; }

/* ── DataTable export buttons — same as delivery note details view ──── */
.dt-buttons .btn {
    background: transparent !important; background-image: none !important;
    box-shadow: none !important; border-color: #5bc0de; color: #5bc0de;
}
.dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }

/* ── Table wrap — identical to det-table-wrap in delivery note details ── */
.pc-table-wrap { padding: 0 1.5rem 1.5rem 1.5rem !important; background: #fff; position: relative; }

/* ── Table alignment — mirrors delivery note details exactly ─────────── */
#maintable thead th,
table.dataTable thead th { text-align: center !important; vertical-align: middle !important; }
#maintable thead th:first-child,
table.dataTable thead th:first-child { text-align: left !important; }
#maintable tbody td,
table.dataTable tbody td { text-align: center !important; vertical-align: middle !important; }
#maintable tbody td:first-child,
table.dataTable tbody td:first-child { text-align: left !important; }

/* ── Price / delta colours ──────────────────────────────────────────── */
.pc-product-block { display: flex; flex-direction: column; gap: 2px; }
.pc-product-name  { font-size: 12px; font-weight: 700; color: #1e293b; white-space: nowrap; }
.pc-product-meta  { display: flex; align-items: center; gap: 4px; }
.pc-badge { font-size: 8px; font-weight: 600; padding: 1px 5px; border-radius: 3px; white-space: nowrap; line-height: 1.5; }
.pc-badge-code { background: #e2e8f0; color: #64748b; }
.pc-badge-unit { background: #dbeafe; color: #1d4ed8; }

.pc-price-block { display: flex; align-items: baseline; gap: 5px; white-space: nowrap; }
.pc-old         { font-size: 11px; color: #94a3b8; text-decoration: line-through; }
.pc-arrow       { font-size: 11px; color: #c5caec; }
.pc-new-up      { font-size: 13px; font-weight: 700; color: #dc2626; }
.pc-new-down    { font-size: 13px; font-weight: 700; color: #059669; }
.pc-new-same    { font-size: 13px; font-weight: 700; color: #64748b; }
.pc-delta-up    { font-size: 10px; font-weight: 700; color: #dc2626; }
.pc-delta-down  { font-size: 10px; font-weight: 700; color: #059669; }
.pc-delta-same  { font-size: 10px; color: #94a3b8; }

/* ── DataTable layout fixes — same as delivery note details view ─────── */
#maintable tbody td.dataTables_empty,
table.dataTable tbody td.dataTables_empty { text-align: center !important; }
.dataTables_wrapper .row > [class*="col-"] { padding-top: 8px; padding-bottom: 8px; }
.dataTables_wrapper .dataTables_length,
.dataTables_wrapper .dataTables_filter { padding-left: 14px !important; padding-right: 14px !important; }
.dataTables_wrapper .dataTables_info { padding-top: 10px !important; padding-bottom: 10px !important; padding-left: 14px !important; }
.dataTables_wrapper .dataTables_paginate { padding-top: 6px !important; padding-bottom: 10px !important; padding-right: 14px !important; }

/* ── Empty / no-category state ──────────────────────────────────────── */
.no-category-wrap { padding: 60px 16px; text-align: center; }
.no-category-wrap i { font-size: 48px; color: #dde1f0; display: block; margin-bottom: 14px; }
.no-category-wrap p { color: #94a3b8; font-size: 13px; }

/* ── Modals ─────────────────────────────────────────────────────────── */
.mh-blue   { background: linear-gradient(135deg,#4B5EBD,#576CC0); padding: 12px 18px !important; border-bottom: none; border-radius: 8px 8px 0 0; }
.mh-title  { color: #fff; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.mh-close  { filter: brightness(0) invert(1); opacity: .8; }
.mh-close:hover { opacity: 1; }
.modal-content { border: none; border-radius: 10px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.18); }
</style>

<div class="progress" id="progressBar" role="progressbar">
    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

{{-- ── Header ── --}}
<div class="card-header">
    <div class="ch-inner">
        <div class="ch-left">
            <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
                  id="headerCategoryForm" style="margin:0;display:contents;">
                @csrf
                <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                <select name="category_id" id="categorySelectHeader"
                        onchange="document.getElementById('headerCategoryForm').submit()">
                    <option value="" hidden>{{ $selectedCategory ? $selectedCategory->category : '— Select Category —' }}</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ ($pref && $pref->category_id == $cat->id) ? 'selected' : '' }}>
                            {{ $cat->category }}
                        </option>
                    @endforeach
                </select>
            </form>

            <div class="ch-sep"></div>

            <div class="ch-date-chip {{ $isCustom ? 'custom-mode' : '' }}"
                 title="Showing price changes recorded on this date">
                <i class="ri-calendar-line" style="font-size:11px;"></i>
                <span>{{ $displayDate }}</span>
                <span class="mode-badge">{{ $isCustom ? 'Custom' : 'Today' }}</span>
            </div>
        </div>

        <div class="ch-right">
            <a href="#" class="ch-btn" id="tableButtonsBtn" title="Download">
                <i class="ri-download-line"></i>
            </a>
            <a href="#" class="ch-btn" id="infoBtn" title="About Price Changes">
                <i class="ri-information-line"></i>
            </a>
        </div>
    </div>
</div>

{{-- ── Tabs ── --}}
<div class="tab-header-container">
    <ul class="nav nav-pills mb-0">
        <li class="nav-item">
            <a href="{{ route('retail.operations.actioncenter') }}" class="nav-link">
                <i class="ri-send-plane-line"></i> Actioncentre
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('retail.operations.deliverynotes') }}" class="nav-link">
                <i class="ri-file-list-3-line"></i> Deliverynotes
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('retail.operations.pricechanges') }}" class="nav-link active">
                <i class="ri-price-tag-3-line"></i> Pricechanges
            </a>
        </li>
    </ul>
</div>

@if(!$selectedCategory)
    <div class="no-category-wrap">
        <i class="ri-store-2-line d-block mx-auto"></i>
        <p>Select a category from the header to get started.</p>
    </div>
@else

{{-- ── Stat chips ── --}}
<div class="stat-chips-row">
    <div class="stat-chip">
        <span class="stat-chip-label">Base price changes</span>
        <span class="stat-chip-val base-clr">{{ $baseCount }}</span>
    </div>
    <div class="stat-chip">
        <span class="stat-chip-label">Branch price changes</span>
        <span class="stat-chip-val branch-clr">{{ $branchCount }}</span>
    </div>
    <div class="stat-chip">
        <span class="stat-chip-label">Branches affected</span>
        <span class="stat-chip-val distinct-clr">{{ $distinctBranches }}</span>
    </div>
</div>

{{-- ── Table — identical structure/options to delivery note details view ── --}}
<div class="pc-table-wrap">
    <table id="maintable" class="table table-sm table-striped row-border order-column w-100">
        <thead style="background-color:#e2e2e9;">
            <tr>
                <th>Product</th>
                <th>Code</th>
                <th>Unit</th>
                <th>Scope</th>
                <th>Old Price</th>
                <th>New Price</th>
            </tr>
        </thead>
        <tbody id="tbody">
            @foreach($priceChanges as $pc)
            @php
                $delta      = (float)$pc->new_price - (float)$pc->old_price;
                $newClass   = $delta > 0 ? 'pc-new-up'   : ($delta < 0 ? 'pc-new-down'   : 'pc-new-same');
            @endphp
            <tr>
                {{-- Product --}}
                <td>
                    <div class="pc-product-block">
                        <span class="pc-product-name">{{ $pc->product_name }}</span>
                    </div>
                </td>

                {{-- Code --}}
                <td>{{ $pc->product_code ?? '—' }}</td>

                {{-- Unit --}}
                <td>{{ $pc->product_unit }}</td>

                {{-- Scope --}}
                <td>
                    @if(is_null($pc->branch_id))
                        Base
                    @else
                        {{ $pc->branch_name }}
                    @endif
                </td>

                {{-- Old price --}}
                <td>
                    {{ number_format((float)$pc->old_price, 2) }}
                </td>

                {{-- New price --}}
                <td>
                    <span class="{{ $newClass }}" style="white-space:nowrap;">
                        {{ number_format((float)$pc->new_price, 2) }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endif {{-- selectedCategory --}}

</div>
</div></div></div>

{{-- ── Download modal ── --}}
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header mh-blue">
            <h5 class="modal-title mh-title"><i class="ri-download-line"></i> Download Price Changes</h5>
            <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <p class="mb-2" style="font-size:13px;">Click a button to download price change data.</p>
            <div class="buttons"></div>
        </div>
    </div></div>
</div>

{{-- ── Info modal ── --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Price Changes</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <table style="width:100%;font-size:13px;border-collapse:collapse;">
                    <tbody>
                        @foreach([
                            ['Product snapshot',      'The product name, code, and unit are saved at the time the change is logged. The record stays accurate even if the product is later renamed or deleted.'],
                            ['Base price change',     'Updates the selling price on the base product catalogue. All branches without their own override inherit this price.'],
                            ['Branch override',       'Updates the selling price for a single branch only. The base price and other branches are unaffected.'],
                            ['Effective immediately', 'All price changes take effect as soon as they are saved — there is no pending or scheduled state.'],
                            ['Base price changes',    'Counter — total catalogue-level price updates recorded on the selected date.'],
                            ['Branch price changes',  'Counter — total branch-specific price updates recorded on the selected date.'],
                            ['Branches affected',     'Counter — distinct number of branches that had at least one price change on the selected date.'],
                            ['Date filter',           'Use the date selector in the header to browse price changes from other dates.'],
                        ] as [$k, $v])
                        <tr>
                            <td style="padding:7px 12px;font-weight:700;color:#475569;width:180px;border-bottom:1px solid #f1f5f9;white-space:nowrap;">{{ $k }}</td>
                            <td style="padding:7px 12px;border-bottom:1px solid #f1f5f9;">{{ $v }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
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

    toastr.options = { timeOut: 5000, progressBar: true, positionClass: 'toast-top-end', closeButton: true };

    @if($selectedCategory)
    var table = $('#maintable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>Brt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
        pageLength: 50,
        fixedColumns: { leftColumns: 1 },
        scrollX: true,
        order: [[0, 'asc']],
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: 0,      className: 'text-start'  }
        ],
        buttons: [
            { extend: 'excelHtml5', title: @json($maintableTitle), exportOptions: { columns: ':visible' } },
            { extend: 'csvHtml5',   title: @json($maintableTitle), exportOptions: { columns: ':visible' } },
            {
                extend: 'pdfHtml5', title: @json($maintableTitle),
                exportOptions: { columns: ':visible' },
                customize: function(doc) {
                    doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
                }
            }
        ],
        language: { search: '', searchPlaceholder: 'Search price changes…', emptyTable: 'No price changes found.' },
    });

    table.buttons().container().appendTo($('#buttonsModal .buttons'));

    $('#tableButtonsBtn').on('click', function(e) { e.preventDefault(); $('#buttonsModal').modal('show'); });
    @endif

    $('#infoBtn').on('click', function(e) { e.preventDefault(); $('#infoModal').modal('show'); });

    @if(Session::has('message'))
        toastr['{{ Session::get("alert-type","info") }}']('{{ Session::get("message") }}');
    @endif
});
</script>
@endsection