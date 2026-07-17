<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{{ $filters->branch_name }}</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Theme Config Js -->
<script src="{{ asset('dashboard/assets/js/config.js') }}"></script>

<!-- App css (static, same build as the dashboard) -->
<link href="{{ asset('dashboard/assets/css/app.min.css') }}" rel="stylesheet" type="text/css" id="app-style" />

<!-- Remixicons -->
<link href="{{ asset('dashboard/assets/remixicons/remixicon.css') }}" rel="stylesheet" type="text/css" />

<!-- Datatables css -->
<link href="{{ asset('dashboard/assets/vendor/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ asset('dashboard/assets/vendor/datatables.net-fixedcolumns-bs5/css/fixedColumns4.2.0.css') }}" rel="stylesheet" type="text/css" />

<style>
    /* No sidebar on this public page — reclaim the space the theme
       normally reserves for it, and drop its footer. The theme's JS
       (not loaded here) is what normally sizes content-page's top
       padding to match the fixed navbar's height; without it that
       padding is left over as a white gap, so make the navbar static
       instead of fixed and clear the reserved spacing. */
    .navbar-custom { position: static !important; left: 0 !important; }
    .content-page  { margin-left: 0 !important; padding-top: 0 !important; }

    .card-header {
        padding: 0.5rem 1.5rem !important;
        background: linear-gradient(to right, #4B5EBD, #576CC0); color:#fff;
        border-radius: 10px 10px 0 0 !important;
    }
    .card-header h2 {
        margin:0; font-size:19px; color:#fff; font-weight:600;
        white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .card-header h2 .cat-abbr {
        font-size:13px; color:#c0c0c0; font-weight:500;
    }
    .card-header .sub-date {
        font-size:11px; color:#c0c0c0; margin-top:2px;
    }
    .card-header .btn-light {
        height:28px; padding:0 10px; flex-shrink:0;
        display:flex; align-items:center; justify-content:center; line-height:1;
    }
    .card-header .btn-light:hover { background-color:#f8f9fa; transition:background-color 0.2s; }
    .card-body { padding: 20px 24px; }

    /* ── Shared table — same look/alignment rules as the Branch Products
       DataTable (thead #e2e2e9, centered cells, first column left-aligned,
       first column locked via fixedColumns). Do NOT wrap this table in an
       extra .table-responsive div — DataTables' own scrollX wrapper is
       what FixedColumns measures against; nesting it inside another
       scroll container throws off the frozen column's offset on
       narrow/mobile viewports and makes the header appear to vanish. ── */
    #sharedOrderTable thead th,
    table.dataTable thead th { text-align:center !important; vertical-align:middle !important; }
    #sharedOrderTable thead th:first-child,
    table.dataTable thead th:first-child { text-align:left !important; }
    #sharedOrderTable tbody td,
    table.dataTable tbody td { text-align:center !important; vertical-align:middle !important; }
    #sharedOrderTable tbody td:first-child,
    table.dataTable tbody td:first-child { text-align:left !important; }
    .dataTables_filter { margin-bottom:10px; }
    .dataTables_filter input {
        border:1px solid #d8dae3; border-radius:6px; padding:5px 10px; font-size:13px; margin-left:6px;
    }
    .dataTables_filter input:focus { outline:none; border-color:#4B5EBD; }

    .footnote { text-align:center; color:#94a3b8; font-size:11px; margin:16px 0; }
</style>
</head>
<body>

@php
    // This is a live, ever-refreshing view keyed by branch+category+supplier
    // (or "All Suppliers") — there's no single "order date" anymore, so the
    // date shown here is just "as of" today, same as the PDF download.
    $formattedDate = \Carbon\Carbon::parse($filters->date ?? now())->format('d M Y');

    $categoryAbbr = match($filters->category) {
        'Regular'   => 'RO',
        'Emergency' => 'EO',
        'Rare'      => 'RAO',
        default     => strtoupper(substr($filters->category, 0, 2)),
    };
@endphp

<!-- Begin page -->
<div class="wrapper">
  
<div class="row mb-3"></div>
    
    <!-- ========== Content (no leftside-menu — this is a public, read-only page) ========== -->
    <div class="content-page">
        <div class="content">
            <div class="container-fluid">

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h2>{{ $filters->branch_name }} <span class="cat-abbr">({{ $categoryAbbr }})</span></h2>
                            <div class="sub-date">{{ $formattedDate }}@if(($filters->supplier_label ?? 'All Suppliers') !== 'All Suppliers') &middot; {{ $filters->supplier_label }}@endif</div>
                        </div>
                        <a href="{{ route('retail.orders.shared.download', [
                                'tenantName'   => request()->route('tenantName'),
                                'branchSlug'   => request()->route('branchSlug'),
                                'supplierSlug' => request()->route('supplierSlug'),
                                'token'        => $filters->share_token,
                            ]) }}"
                           class="btn btn-light text-primary fs-16 mx-1" title="Download PDF">
                            <i class="ri-download-line"></i>
                        </a>
                    </div>
                    <div class="card-body">

                        <table id="sharedOrderTable" class="table table-sm table-striped row-border order-column w-100 mt-3">
                            <thead style="background-color:#e2e2e9">
                                <tr><th>Product</th><th>Unit</th><th>Stock@Order</th><th>OrderQty</th></tr>
                            </thead>
                            <tbody>
                                @foreach($lines as $l)
                                <tr>
                                    <td>{{ $l->product_name }}@if($l->is_custom) <span style="color:#7c3aed;font-size:11px;">(custom)</span>@endif</td>
                                    <td>{{ $l->units ?? '—' }}</td>
                                    <td>{{ $l->stock_at_order !== null ? number_format($l->stock_at_order, 0) : '—' }}</td>
                                    <td>{{ $l->quantity }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                    </div>
                </div>

                <div class="footnote">This is a shared, read-only view. It was viewed {{ $filters->share_view_count }} time(s).</div>

            </div>
        </div>
    </div>
    <!-- ========== End Content ========== -->

</div>
<!-- END wrapper -->


<!-- Scripts (static, same build as the dashboard) -->
<script src="{{ asset('library/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('dashboard/assets/vendor/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('dashboard/assets/vendor/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('dashboard/assets/vendor/datatables.net-fixedcolumns-bs5/js/fixedColumns4.2.0.js') }}"></script>
<script>
$(function () {
    var table = $('#sharedOrderTable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
        pageLength: 100,
        fixedColumns: { leftColumns: 1 },
        scrollX: true,
        info: true,
        searching: true,
        ordering: true,
        autoWidth: false,
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: 0,      className: 'text-start'  }
        ],
    });

    // Defensive: FixedColumns caches the frozen column's width/offset at
    // init time. If the viewport changes afterwards (mobile rotation,
    // address-bar collapse/expand), that cache can go stale and the
    // header clone appears to vanish even though the body renders fine.
    // Recomputing both on resize keeps them in sync.
    $(window).on('resize orientationchange', function () {
        table.columns.adjust();
        if (table.fixedColumns) {
            table.fixedColumns().relayout();
        }
    });
});
</script>
</body>
</html>