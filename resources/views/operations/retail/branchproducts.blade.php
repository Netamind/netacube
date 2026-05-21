@extends('operations.retail.dashboard')
@section('content')

@php
    $branches = DB::connection('tenant')->table('branches')->orderBy('name')->get();
    $pref     = DB::connection('tenant')->table('user_filters')->where('user_id', Auth::id())->first();

    $branchProducts = collect();
    $selectedBranch = null;
    $shopValue      = 0;
    $branchCategory = null;

    if ($pref && $pref->branch_id) {
        $selectedBranch = DB::connection('tenant')->table('branches')->find($pref->branch_id);

        if ($selectedBranch) {
            $branchCategory = DB::connection('tenant')
                ->table('categories')
                ->where('id', $selectedBranch->category)
                ->first();
        }

        $branchProducts = DB::connection('tenant')
            ->table('retail_branch_products as rbp')
            ->join('retail_base_products as bp', 'bp.id', '=', 'rbp.base_product_id')
            ->where('rbp.branch_id', $pref->branch_id)
            ->select('rbp.*', 'bp.name', 'bp.code', 'bp.unit', 'bp.supplier',
                     'bp.selling_price as bp_sell', 'bp.cost_price as bp_cost')
            ->get();

        foreach ($branchProducts as $bp) {
            $shopValue += (float)$bp->selling_price * (float)$bp->stock_quantity;
        }
    }

    $baseProducts = collect();
    if ($selectedBranch) {
        $alreadyIn    = $branchProducts->pluck('base_product_id')->toArray();
        $baseProducts = DB::connection('tenant')
            ->table('retail_base_products')
            ->whereNotIn('id', $alreadyIn)
            ->where('is_product', 1)
            ->get();
    }

    // Suppliers scoped to branch category for dropdowns
    $supplierRows = collect();
    if ($selectedBranch && $selectedBranch->category) {
        $supplierRows = DB::connection('tenant')->table('suppliers')
            ->where('status', 'active')
            ->where('category', $selectedBranch->category)
            ->orderBy('name')
            ->get(['id', 'name', 'category']);
    }

    $maintableTitle  = 'Branch Products — ' . ($selectedBranch->name ?? 'All');
    $activeCount     = $branchProducts->where('is_active', 1)->count();
    $lowStockCount   = $branchProducts->filter(fn($p) => (float)$p->stock_quantity <= (float)$p->reorder_point && (float)$p->stock_quantity > 0)->count();
    $zeroCount       = $branchProducts->filter(fn($p) => (float)$p->stock_quantity <= 0)->count();
@endphp

<style>
/* ── DataTable export buttons ─────────────────────────────────────── */
.dt-buttons .btn { background:transparent !important; background-image:none !important; box-shadow:none !important; border-color:#5bc0de; color:#5bc0de; }
.dt-buttons .btn:hover { background:#5bc0de !important; color:#fff; }

/* ── Card chrome ─────────────────────────────────────────────────── */
.card-header { padding:0.5rem 1.5rem !important; background:linear-gradient(to right,#4B5EBD,#576CC0); color:#fff; border-radius:10px 10px 0 0 !important; }
.card-body   { padding:0 1.5rem 1.5rem 1.5rem !important; }
.card        { border:none; box-shadow:0 4px 8px rgba(0,0,0,0.1); border-radius:10px; }
.card-header h4 { color:#fff; font-weight:600; margin-bottom:0; display:flex; align-items:center; }
.card-header .btn-light { height:28px; padding:0 10px; display:flex; align-items:center; justify-content:center; line-height:1; }
.card-header .btn-light:hover { background-color:#f8f9fa; transition:background-color 0.2s; }

/* ── Remove number input spinners everywhere (Chrome/Edge/Safari + Firefox) ── */
input[type=number]::-webkit-outer-spin-button,
input[type=number]::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
input[type=number] { -moz-appearance:textfield; appearance:textfield; }

/* ── Bulk Actions header button — icon-only, matches other header buttons ── */
#bulkActionsHeaderBtn {
  position:relative;
  opacity:.5; pointer-events:none; cursor:not-allowed;
  transition:opacity .15s;
}
#bulkActionsHeaderBtn.enabled { opacity:1; pointer-events:auto; cursor:pointer; }
#bulkActionsHeaderBtn .bah-count {
  position:absolute; top:-5px; right:-5px;
  background:#dc2626; color:#fff; border-radius:50%; font-size:10px; font-weight:700;
  min-width:16px; height:16px; line-height:16px; text-align:center; padding:0 3px;
  display:none; box-shadow:0 0 0 1.5px #fff;
}
#bulkActionsHeaderBtn .bah-count.show { display:block; }

/* ── Bulk actions modal option cards ─────────────────────────────── */
.bulk-option-card {
  display:flex; align-items:center; gap:12px; padding:14px 16px; border-radius:10px;
  border:1.5px solid #e9ecef; cursor:pointer; transition:border-color .15s,background .15s;
  margin-bottom:10px;
}
.bulk-option-card:last-child { margin-bottom:0; }
.bulk-option-card:hover { border-color:#c8d0ed; background:#f8f9ff; }
.bulk-option-card .boc-icon {
  width:40px; height:40px; border-radius:9px; display:flex; align-items:center; justify-content:center;
  font-size:19px; flex-shrink:0;
}
.bulk-option-card .boc-title { font-size:14px; font-weight:700; color:#1e293b; }
.bulk-option-card .boc-desc  { font-size:12px; color:#6c757d; margin-top:1px; }
.boc-icon-base   { background:#ecfdf5; color:#059669; }
.boc-icon-branch { background:#eff6ff; color:#1d4ed8; }
.boc-icon-delete { background:#fef2f2; color:#dc2626; }

/* ── Table alignment ─────────────────────────────────────────────── */
#maintable thead th, table.dataTable thead th { text-align:center !important; vertical-align:middle !important; }
#maintable thead th:first-child, table.dataTable thead th:first-child { text-align:left !important; }
#maintable tbody td, table.dataTable tbody td { text-align:center !important; vertical-align:middle !important; }
#maintable tbody td:first-child, table.dataTable tbody td:first-child { text-align:left !important; }

/* ── Badges & prices ─────────────────────────────────────────────── */
.price-cell  { font-size:12px; font-weight:600; }
.stock-ok    { color:#16a34a; font-weight:700; }
.stock-low   { color:#d97706; font-weight:700; }
.stock-zero  { color:#dc2626; font-weight:700; }
.price-branch { color:#1d4ed8; font-weight:700; }
.price-base   { color:#059669; font-weight:600; }

/* ── No branch selected ──────────────────────────────────────────── */
.no-branch-wrap { padding:48px 20px; text-align:center; color:#94a3b8; }
.no-branch-wrap i { font-size:52px; display:block; margin-bottom:12px; color:#c8d0ed; }
.no-branch-wrap h5 { color:#64748b; font-weight:600; }

/* ── Modal header helpers ────────────────────────────────────────── */
.mh-blue   { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-danger { background:linear-gradient(135deg,#c0392b,#e74c3c); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-teal   { background:linear-gradient(135deg,#0ea5e9,#0284c7); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-title  { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close  { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }

/* ── Branch select in header ─────────────────────────────────────── */
#branchSelectHeader { border:none; background:transparent; color:#fff; font-size:18px; font-weight:600; cursor:pointer; padding:0; outline:none; max-width:300px; }
#branchSelectHeader option { color:#1e293b; background:#fff; font-size:14px; }

/* ── Search result list ──────────────────────────────────────────── */
.search-result-list { max-height:380px; overflow-y:auto; border:1px solid #dee2e6; border-radius:8px; background:#fff; display:none; box-shadow:0 4px 16px rgba(0,0,0,0.10); }
.search-result-item { border-bottom:1px solid #f1f5f9; }
.search-result-item:last-child { border-bottom:none; }
.sri-row { display:flex; align-items:center; gap:8px; padding:6px 10px; transition:background .12s; }
.search-result-item:hover .sri-row { background:#eef0fa; }
.sri-name { flex:1; font-weight:600; font-size:13px; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.sri-name .sri-code { font-weight:400; color:#64748b; }
.sri-meta { font-size:11px; color:#64748b; white-space:nowrap; flex-shrink:0; background:#f1f5f9; padding:2px 7px; border-radius:10px; font-weight:500; }
.sri-qty-input { width:72px; text-align:center; border:1.5px solid #c8d0ed; border-radius:6px; height:30px; font-size:13px; font-weight:600; padding:0 6px; flex-shrink:0; color:#1e293b; background:#f8f9ff; outline:none; }
.sri-qty-input:focus { border-color:#4B5EBD; box-shadow:0 0 0 3px rgba(75,94,189,0.12); background:#fff; }
.sri-qty-input:disabled { background:#f0f0f0; color:#aaa; }
.sri-add-btn { height:30px; padding:0 14px; font-size:12px; font-weight:700; border:none; border-radius:6px; cursor:pointer; flex-shrink:0; background:linear-gradient(135deg,#4B5EBD,#576CC0); color:#fff; display:flex; align-items:center; gap:4px; box-shadow:0 2px 6px rgba(75,94,189,0.28); transition:opacity .15s; }
.sri-add-btn:hover:not(:disabled) { opacity:.88; }
.sri-add-btn:disabled { background:#e2e8f0 !important; color:#94a3b8 !important; box-shadow:none; cursor:default; }
.sri-added-msg { font-size:11px; font-weight:700; color:#16a34a; white-space:nowrap; display:none; flex-shrink:0; }

/* ── View modal ──────────────────────────────────────────────────── */
.view-grid { display:grid; grid-template-columns:1fr 1fr; gap:6px 14px; }
.view-item label { font-size:10px; font-weight:600; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:2px; }
.view-item .view-val { font-size:13px; color:#1e293b; font-weight:500; }
.view-item.full { grid-column:1/-1; }

/* ── Edit modal tabs ─────────────────────────────────────────────── */
#editModalTabs { display:flex; gap:0; background:#f1f3f9; padding:10px 18px 0; border-bottom:1.5px solid #dde1f0; margin:0; list-style:none; }
#editModalTabs .nav-link { position:relative; display:flex; align-items:center; gap:6px; font-size:12px; font-weight:500; color:#94a3b8; padding:7px 16px 9px; border:none; background:none; border-radius:6px 6px 0 0; border-bottom:2px solid transparent; margin-bottom:-1.5px; cursor:pointer; transition:color .15s,background .15s; text-decoration:none; }
#editModalTabs .nav-link:hover:not(.active) { color:#4B5EBD; background:rgba(75,94,189,0.06); }
#editModalTabs .nav-link.active { color:#4B5EBD; background:#fff; border-bottom:2px solid #4B5EBD; font-weight:600; }
.edit-readonly-field { background:#f8f9fa !important; color:#6c757d !important; border-color:#dee2e6 !important; cursor:default !important; }
.edit-tab-crosslink { display:inline-flex; align-items:center; gap:4px; font-size:11px; color:#4B5EBD; text-decoration:none; margin-bottom:14px; }
.edit-tab-crosslink:hover { text-decoration:underline; }

/* ── Price source cards ──────────────────────────────────────────── */
.price-source-toggle { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:10px; }
.price-source-card { border:0.5px solid #dee2e6; border-radius:8px; padding:10px 12px; cursor:pointer; transition:border-color .15s,background .15s; user-select:none; }
.price-source-card:hover { border-color:#adb5bd; }
.price-source-card.active-base   { border:1.5px solid #059669; background:#f0fdf4; }
.price-source-card.active-branch { border:1.5px solid #1d4ed8; background:#eff6ff; }
.psc-label { font-size:12px; font-weight:600; color:#374151; display:flex; align-items:center; gap:5px; margin-bottom:3px; }
.psc-dot   { width:7px; height:7px; border-radius:50%; display:inline-block; }
.psc-value { font-size:15px; font-weight:600; color:#9ca3af; }
.price-source-card.active-base   .psc-value { color:#059669; }
.price-source-card.active-branch .psc-value { color:#1d4ed8; }
.psc-desc  { font-size:11px; color:#9ca3af; margin-top:2px; }
.price-context-hint { font-size:11px; padding:7px 10px; border-radius:6px; margin-bottom:12px; display:flex; align-items:flex-start; gap:6px; }
.pch-base   { background:#f0fdf4; color:#065f46; }
.pch-branch { background:#eff6ff; color:#1e40af; }
.edit-section-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.7px; color:#6c757d; margin-bottom:8px; margin-top:4px; display:flex; align-items:center; gap:5px; }
.bp-edit-warning { background:#fffbeb; border-left:2px solid #f59e0b; border-radius:0 5px 5px 0; padding:8px 12px; font-size:11px; color:#92400e; margin-bottom:14px; display:flex; align-items:flex-start; gap:6px; }

/* ── Combined overview modal tabs ────────────────────────────────── */
.overview-tab-btn { flex:1; padding:8px 0; font-size:12px; font-weight:600; border:none; background:transparent; color:#94a3b8; border-bottom:2px solid transparent; cursor:pointer; transition:color .15s; }
.overview-tab-btn.active { color:#4B5EBD; border-bottom-color:#4B5EBD; }
.sv-metric { background:#eef0f7; border-radius:8px; padding:10px 12px; text-align:center; }
.sv-metric .sv-label { font-size:11px; color:#6c757d; margin-bottom:4px; }
.sv-metric .sv-value { font-size:20px; font-weight:600; }
.pricing-swatch { display:inline-flex; align-items:center; gap:8px; padding:8px 14px; border-radius:8px; margin-bottom:8px; width:100%; }
.pricing-swatch-br { background:#eff6ff; border:1px solid #bfdbfe; }
.pricing-swatch-bp { background:#ecfdf5; border:1px solid #a7f3d0; }
.pricing-swatch .swatch-dot { width:12px; height:12px; border-radius:50%; flex-shrink:0; }
.swatch-dot-br { background:#1d4ed8; }
.swatch-dot-bp { background:#059669; }
.pricing-swatch .swatch-label { font-size:13px; font-weight:600; }
.pricing-swatch .swatch-desc  { font-size:12px; color:#64748b; margin-top:1px; }
.price-demo-br { color:#1d4ed8; font-weight:700; font-size:13px; }
.price-demo-bp { color:#059669; font-weight:600; font-size:13px; }

/* ── CSV wizard steps ────────────────────────────────────────────── */
.csv-step { display:none; }
.csv-step.active { display:block; }
.csv-step-indicator { display:flex; align-items:center; gap:0; margin-bottom:18px; }
.csi-step { display:flex; align-items:center; gap:6px; font-size:11px; font-weight:600; color:#94a3b8; }
.csi-step.active { color:#4B5EBD; }
.csi-step.done   { color:#059669; }
.csi-num { width:22px; height:22px; border-radius:50%; border:2px solid currentColor; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; flex-shrink:0; }
.csi-line { flex:1; height:1px; background:#dee2e6; margin:0 6px; }
.csv-preview-row { padding:6px 10px; border-bottom:1px solid #f1f5f9; font-size:12px; display:flex; justify-content:space-between; align-items:center; gap:8px; }
.csv-preview-row:last-child { border-bottom:none; }
.csv-preview-row .cpr-name { flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.csv-preview-scroll { max-height:280px; overflow-y:auto; border:1px solid #dee2e6; border-radius:8px; margin-bottom:10px; }
.csv-preview-scroll::-webkit-scrollbar { width:8px; }
.csv-preview-scroll::-webkit-scrollbar-thumb { background:#c8d0ed; border-radius:8px; }
.csv-preview-scroll::-webkit-scrollbar-track { background:#f8f9fb; }

/* ── Set branch prices modal — professional table layout ─────────── */
.sbp-table-wrap {
  max-height:440px; overflow-y:auto; overflow-x:auto; border:1px solid #e2e6f0; border-radius:8px;
}
.sbp-table-wrap::-webkit-scrollbar { width:9px; height:9px; }
.sbp-table-wrap::-webkit-scrollbar-thumb { background:#c8d0ed; border-radius:8px; }
.sbp-table-wrap::-webkit-scrollbar-track { background:#f8f9fb; }
table.sbp-table { width:100%; min-width:420px; border-collapse:collapse; font-size:13px; }
table.sbp-table thead th {
  position:sticky; top:0; background:#eef0f7; color:#4B5EBD; font-size:11px; font-weight:700;
  text-transform:uppercase; letter-spacing:.4px; padding:9px 14px; text-align:left; border-bottom:1.5px solid #dde1f0; z-index:1;
}
table.sbp-table thead th.sbp-th-center { text-align:center; }
table.sbp-table tbody td { padding:8px 14px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
table.sbp-table tbody tr:last-child td { border-bottom:none; }
table.sbp-table tbody tr:hover { background:#fafbff; }
.sbp-col-name { text-align:left; }
.sbp-prod-name { font-size:13px; font-weight:600; color:#1e293b; }
.sbp-prod-meta { font-size:11px; color:#6c757d; margin-top:1px; }
.sbp-base-val  { font-size:11px; font-weight:700; color:#059669; }
.sbp-col-input { text-align:center; }
.sbp-input {
  width:140px; max-width:100%; border:1.5px solid #c8d0ed; border-radius:6px; height:32px;
  font-size:13px; font-weight:600; padding:0 8px; color:#1d4ed8; background:#f8f9ff; outline:none;
  text-align:center;
}
.sbp-input:focus { border-color:#1d4ed8; box-shadow:0 0 0 3px rgba(29,78,216,0.10); background:#fff; }
.sbp-input::placeholder { color:#aab3d6; font-weight:500; }
.sbp-input[data-autofilled="1"] { background:#eff6ff; border-color:#93c5fd; }
.sbp-toolbar { display:flex; justify-content:flex-end; gap:8px; margin-bottom:12px; }
.sbp-tool-btn {
  display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:600;
  border:1.5px solid #dde1f0; background:#fff; color:#4B5EBD; border-radius:6px; padding:6px 12px; cursor:pointer;
  transition:background .15s,border-color .15s;
}
.sbp-tool-btn:hover { background:#f0f3ff; border-color:#c8d0ed; }
.sbp-tool-btn.sbp-tool-clear { color:#dc2626; }
.sbp-tool-btn.sbp-tool-clear:hover { background:#fef2f2; border-color:#fecaca; }

/* ── Confirmation modal (use base prices) ─────────────────────────── */
.confirm-icon-wrap { width:56px; height:56px; border-radius:50%; background:#fffbeb; display:flex; align-items:center; justify-content:center; margin:0 auto 14px; }
.confirm-icon-wrap i { font-size:28px; color:#d97706; }

/* ── Spinner ─────────────────────────────────────────────────────── */
@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
</style>

<div class="progress" id="progressBar" role="progressbar"
     style="height:8px;transform:rotate(180deg);display:none">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

  {{-- ── Card header ──────────────────────────────────────────────────── --}}
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
      <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
            id="headerBranchForm" style="margin:0;display:inline;">
        @csrf
        <input type="hidden" name="user_id" value="{{ Auth::id() }}">
        <select name="branch_id" id="branchSelectHeader"
                onchange="document.getElementById('headerBranchForm').submit()">
          <option value="" hidden>{{ $selectedBranch ? $selectedBranch->name : '— Select Branch —' }}</option>
          @foreach($branches as $b)
            <option value="{{ $b->id }}" {{ ($pref && $pref->branch_id == $b->id) ? 'selected' : '' }}>
              {{ $b->name }}
            </option>
          @endforeach
        </select>
      </form>
    </h4>

    <div class="d-flex align-items-center" style="gap:4px;">
      @if($selectedBranch)
      <button type="button" class="btn btn-light text-primary fs-16 mx-1" id="bulkActionsHeaderBtn" disabled title="Select rows to enable bulk actions">
        <i class="ri-stack-line"></i>
        <span class="bah-count" id="bulkActionsHeaderCount"></span>
      </button>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="overviewBtn" title="Overview">
        <i class="ri-dashboard-line"></i>
      </a>
      @endif
      <a href="#" class="btn btn-light text-success fs-16 mx-1" id="addProductBtn"
         title="Add product" @if(!$selectedBranch) style="pointer-events:none;opacity:.5" @endif>
        <i class="ri-add-circle-line"></i>
      </a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="About Branch Products">
        <i class="ri-information-line"></i>
      </a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download">
        <i class="ri-download-line"></i>
      </a>
    </div>
  </div>

  {{-- ── Table / Empty state ──────────────────────────────────────────── --}}
  <div class="card-body">
    @if(!$selectedBranch)
      <div class="no-branch-wrap">
        <i class="ri-store-line"></i>
        <h5>No Branch Selected</h5>
        <p style="font-size:13px;">Select a branch from the header above.</p>
      </div>
    @else
    <table id="maintable" class="table table-sm table-striped row-border order-column w-100 mt-3">
      <thead style="background-color:#e2e2e9">
        <tr>
          <th><input type="checkbox" id="selectAll">&nbsp;&nbsp;Product Name</th>
          <th>Code</th>
          <th>Unit</th>
          <th>Stock</th>
          <th>Sell Price</th>
          <th>Batch Number</th>
          <th>Expiry Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="tbody">
        @foreach($branchProducts as $bp)
          @php
            $row          = 'row' . $bp->id;
            $sq           = (float)$bp->stock_quantity;
            $rp           = (float)$bp->reorder_point;
            $stockClass   = $sq <= 0 ? 'stock-zero' : ($sq <= $rp ? 'stock-low' : 'stock-ok');
          $sellIsBranch = ($bp->selling_price !== null);
          $costIsBranch = ($bp->cost_price    !== null);
          @endphp
          <tr id="{{ $row }}">
            <td>
              <input type="checkbox" class="selectRow" value="{{ $bp->id }}"
                     data-row-id="{{ $row }}"
                     data-name="{{ $bp->name }}"
                     data-unit="{{ $bp->unit }}"
                     data-stock="{{ $bp->stock_quantity }}"
                     data-bp-sell="{{ $bp->bp_sell }}"
                     data-sell="{{ $bp->selling_price }}"
                     data-sell-is-branch="{{ $sellIsBranch ? 1 : 0 }}">
              &nbsp;{{ $bp->name }}
            </td>
            <td>{{ $bp->code ?? '—' }}</td>
            <td>{{ $bp->unit }}</td>
            <td><span class="{{ $stockClass }}">{{ number_format($sq, 0) }}</span></td>
            <td>
              <span class="{{ $sellIsBranch ? 'price-branch' : 'price-base' }}" style="font-size:12px">
                {{ number_format($bp->selling_price ?? $bp->bp_sell, 2) }}
              </span>
            </td>
            <td>{{ $bp->batch_number ?? '—' }}</td>
            <td>{{ $bp->expiry_date  ?? '—' }}</td>
            <td>
              <a href="#" class="viewDataBtn"
                 data-id="{{ $bp->id }}" data-name="{{ $bp->name }}" data-code="{{ $bp->code }}"
                 data-unit="{{ $bp->unit }}" data-supplier="{{ $bp->supplier }}"
                 data-barcode="{{ $bp->primary_barcode }}" data-batch="{{ $bp->batch_number }}"
                 data-expiry="{{ $bp->expiry_date }}" data-cost="{{ $bp->cost_price }}"
                 data-sell="{{ $bp->selling_price }}" data-stock="{{ $bp->stock_quantity }}"
                 data-reorder="{{ $bp->reorder_point }}" data-reorder-qty="{{ $bp->reorder_quantity }}"
                 data-max="{{ $bp->max_stock }}" data-active="{{ $bp->is_active }}"
                 data-track="{{ $bp->track_stock }}" data-neg="{{ $bp->allow_negative_stock }}"
                 data-sell-is-branch="{{ $sellIsBranch ? 1 : 0 }}"
                 data-cost-is-branch="{{ $costIsBranch ? 1 : 0 }}"
                 data-bp-sell="{{ $bp->bp_sell }}" data-bp-cost="{{ $bp->bp_cost }}">
                <i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px"></i>
              </a>
              <a href="#" class="editDataBtn"
                 data-id="{{ $bp->id }}" data-row="{{ $row }}"
                 data-name="{{ $bp->name }}" data-code="{{ $bp->code }}"
                 data-unit="{{ $bp->unit }}" data-supplier="{{ $bp->supplier }}"
                 data-barcode="{{ $bp->primary_barcode }}" data-batch="{{ $bp->batch_number }}"
                 data-expiry="{{ $bp->expiry_date }}" data-cost="{{ $bp->cost_price }}"
                 data-sell="{{ $bp->selling_price }}" data-stock="{{ $bp->stock_quantity }}"
                 data-reorder="{{ $bp->reorder_point }}" data-reorder-qty="{{ $bp->reorder_quantity }}"
                 data-max="{{ $bp->max_stock }}" data-active="{{ $bp->is_active }}"
                 data-track="{{ $bp->track_stock }}" data-neg="{{ $bp->allow_negative_stock }}"
                 data-sell-is-branch="{{ $sellIsBranch ? 1 : 0 }}"
                 data-cost-is-branch="{{ $costIsBranch ? 1 : 0 }}"
                 data-bp-sell="{{ $bp->bp_sell }}" data-bp-cost="{{ $bp->bp_cost }}"
                 data-base-product-id="{{ $bp->base_product_id }}">
                <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px"></i>
              </a>
              <a href="#" class="deleteDataBtn"
                 data-label="{{ $bp->name }}" data-id="{{ $bp->id }}" data-row="{{ $row }}">
                <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px"></i>
              </a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
    @endif
  </div>
</div>
</div></div></div>

{{-- ══════════════════════════════════════════════════════════════════
     BULK ACTIONS MODAL (entry point — 3 options)
══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="bulkActionsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title">
          <i class="ri-stack-line"></i> Bulk Actions
          <span style="font-size:12px;font-weight:400;opacity:.85" id="bulkActionsModalCountText">— 0 selected</span>
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:18px 20px !important;">

        <div class="bulk-option-card" id="boUseBasePrices">
          <div class="boc-icon boc-icon-base"><i class="ri-arrow-go-back-line"></i></div>
          <div>
            <div class="boc-title">Use Base Prices</div>
            <div class="boc-desc">Clear branch price overrides — revert selected products to the catalogue price.</div>
          </div>
        </div>

        <div class="bulk-option-card" id="boSetBranchPrices">
          <div class="boc-icon boc-icon-branch"><i class="ri-price-tag-3-line"></i></div>
          <div>
            <div class="boc-title">Set Branch Prices</div>
            <div class="boc-desc">Enter a price override for this branch on each selected product.</div>
          </div>
        </div>

        <div class="bulk-option-card" id="boBulkDelete">
          <div class="boc-icon boc-icon-delete"><i class="ri-delete-bin-line"></i></div>
          <div>
            <div class="boc-title">Delete from Branch</div>
            <div class="boc-desc">Remove the selected products from this branch only.</div>
          </div>
        </div>

      </div>
      <div class="modal-footer" style="padding:10px 20px 14px;">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     USE BASE PRICES — CONFIRMATION MODAL
══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="confirmUseBaseModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-danger">
        <h5 class="modal-title mh-title"><i class="ri-error-warning-line"></i> Confirm Action</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center py-4">
        <div class="confirm-icon-wrap"><i class="ri-question-line"></i></div>
        <h5 class="mb-2">Are you sure?</h5>
        <p style="font-size:13px;color:#6c757d;max-width:380px;margin:0 auto;">
          All current branch prices for the <strong id="confirmUseBaseCount">0</strong> selected product(s) will be cleared,
          and pricing will fall back to the base catalogue price.
        </p>
      </div>
      <div class="modal-footer justify-content-center gap-2" style="padding:10px 20px 18px;">
        <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger btn-sm px-4" id="confirmUseBaseSubmitBtn">
          <i class="ri-arrow-go-back-line me-1"></i> Yes, Use Base Prices
        </button>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     BULK DELETE — CONFIRMATION MODAL
══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="confirmBulkDeleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-danger">
        <h5 class="modal-title mh-title"><i class="ri-delete-bin-line"></i> Remove from Branch</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center py-4">
        <i class="ri-error-warning-line text-danger" style="font-size:60px"></i>
        <h5 class="mt-2 mb-1">Remove <span id="confirmBulkDeleteCount" class="text-danger">0</span> product(s)?</h5>
        <p style="font-size:13px;color:#6c757d;margin-bottom:0;">
          Removes from this branch only. Base products are kept in the catalogue.
        </p>
      </div>
      <div class="modal-footer justify-content-center gap-2" style="padding:10px 20px 18px;">
        <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Keep</button>
        <button type="button" class="btn btn-danger btn-sm px-4" id="confirmBulkDeleteSubmitBtn">Remove</button>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     OVERVIEW MODAL (Shop Value + Pricing Guide combined)
══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="overviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-dashboard-line"></i> Branch Overview</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      {{-- Tab strip --}}
      <div style="display:flex;border-bottom:1.5px solid #dde1f0;background:#f8f9fb;padding:0 18px;">
        <button class="overview-tab-btn active" id="ovTabShopBtn" onclick="switchOverviewTab('shop')">
          <i class="ri-store-2-line me-1"></i>Shop Value
        </button>
        <button class="overview-tab-btn" id="ovTabPriceBtn" onclick="switchOverviewTab('price')">
          <i class="ri-price-tag-3-line me-1"></i>Price Guide
        </button>
      </div>
      <div class="modal-body" style="padding:18px 20px !important;">

        {{-- Shop Value tab --}}
        <div id="ovTabShop">
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:18px;">
            <div class="sv-metric">
              <div class="sv-label">Products</div>
              <div class="sv-value" style="color:#4B5EBD;">{{ $branchProducts->count() }}</div>
            </div>
            <div class="sv-metric">
              <div class="sv-label">Active</div>
              <div class="sv-value" style="color:#198754;">{{ $activeCount }}</div>
            </div>
            <div class="sv-metric">
              <div class="sv-label">Low / Zero</div>
              <div class="sv-value" style="color:#d97706;">{{ $lowStockCount + $zeroCount }}</div>
            </div>
          </div>
          <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <tbody>
              <tr style="border-bottom:1px solid #e9ecef;">
                <td style="padding:8px 0;color:#6c757d;font-weight:600;width:140px;">Branch</td>
                <td style="padding:8px 0;font-weight:600;color:#1e293b;">{{ $selectedBranch->name ?? '—' }}</td>
              </tr>
              <tr style="border-bottom:1px solid #e9ecef;">
                <td style="padding:8px 0;color:#6c757d;font-weight:600;">Category</td>
                <td style="padding:8px 0;color:#1e293b;">{{ $branchCategory->category ?? '—' }}</td>
              </tr>
              <tr style="border-bottom:1px solid #e9ecef;">
                <td style="padding:8px 0;color:#6c757d;font-weight:600;">Zero stock</td>
                <td style="padding:8px 0;color:#dc2626;font-weight:600;">{{ $zeroCount }}</td>
              </tr>
              <tr style="border-bottom:1px solid #e9ecef;">
                <td style="padding:8px 0;color:#6c757d;font-weight:600;">Low stock</td>
                <td style="padding:8px 0;color:#d97706;font-weight:600;">{{ $lowStockCount }}</td>
              </tr>
              <tr>
                <td style="padding:12px 0 4px;color:#6c757d;font-weight:600;">Total shop value</td>
                <td style="padding:12px 0 4px;font-size:22px;font-weight:700;color:#4B5EBD;">
                  MWK {{ number_format($shopValue, 0) }}
                </td>
              </tr>
              <tr>
                <td style="padding:4px 0;color:#6c757d;font-weight:600;">Valuation date</td>
                <td style="padding:4px 0;color:#94a3b8;font-size:12px;">{{ now()->toDateString() }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        {{-- Price Guide tab --}}
        <div id="ovTabPrice" style="display:none;">
          <div class="pricing-swatch pricing-swatch-br">
            <span class="swatch-dot swatch-dot-br"></span>
            <div class="flex-fill">
              <div class="swatch-label" style="color:#1d4ed8;">Branch Override</div>
              <div class="swatch-desc">Price set specifically for this branch.</div>
            </div>
            <div style="text-align:right;flex-shrink:0;">
              <div class="price-demo-br">1,250.00</div>
              <div style="font-size:10px;color:#93c5fd;">Blue</div>
            </div>
          </div>
          <div class="pricing-swatch pricing-swatch-bp">
            <span class="swatch-dot swatch-dot-bp"></span>
            <div class="flex-fill">
              <div class="swatch-label" style="color:#059669;">Base Catalogue Default</div>
              <div class="swatch-desc">No branch override — using the base catalogue price.</div>
            </div>
            <div style="text-align:right;flex-shrink:0;">
              <div class="price-demo-bp">950.00</div>
              <div style="font-size:10px;color:#6ee7b7;">Green</div>
            </div>
          </div>
          <div style="background:#f8fafc;border-radius:8px;padding:10px 14px;font-size:12px;color:#475569;margin-top:8px;">
            <i class="ri-lightbulb-line me-1 text-warning"></i>
            Open <strong>Edit</strong> and choose <em>This branch only</em> to set a branch price.
          </div>
        </div>

      </div>
      <div class="modal-footer" style="padding:10px 20px 14px;">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     DOWNLOAD MODAL
══════════════════════════════════════════════════════════════════════ --}}
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

{{-- ══════════════════════════════════════════════════════════════════
     INFO MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue">
      <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Branch Products</h5>
      <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="padding:18px 20px;">
      <p class="mb-2"><strong>Branch Products</strong> are base catalogue items assigned to a specific branch with their own stock, prices, and reorder levels.</p>
      <hr class="my-3">
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <tbody>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;width:140px;border-bottom:1px solid #f1f5f9">Selling Price</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">Branch-specific price. Falls back to catalogue default if not set.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9">Stock Qty</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9"><span style="color:#dc2626;font-weight:600">Red=zero</span>, <span style="color:#d97706;font-weight:600">amber=low</span>, <span style="color:#16a34a;font-weight:600">green=healthy</span>.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9">Reorder Point</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">Low-stock alert triggers when stock reaches this level.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569">Track Stock</td><td style="padding:8px 12px">When enabled, sales decrement the stock quantity.</td></tr>
        </tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
    </div>
  </div></div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     ADD PRODUCT MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="addProductModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog ">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">

      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title">
          <i class="ri-add-circle-line"></i> Add Product
          @if($selectedBranch)
            <span style="font-size:12px;font-weight:400;opacity:.85">— {{ $selectedBranch->name }}</span>
          @endif
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>

      <ul class="nav nav-tabs border-bottom px-2 pt-2" role="tablist" style="font-size:12px;flex-wrap:nowrap;">
        <li class="nav-item">
          <button class="nav-link active px-3 py-1" data-bs-toggle="tab" data-bs-target="#at1" type="button">
            <i class="ri-search-line me-1"></i>Search
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link px-3 py-1" data-bs-toggle="tab" data-bs-target="#at2" type="button">
            <i class="ri-add-line me-1"></i>New Product
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link px-3 py-1" data-bs-toggle="tab" data-bs-target="#at3" type="button">
            <i class="ri-upload-2-line me-1"></i>Import CSV
          </button>
        </li>
      </ul>

      <div class="modal-body" style="padding:14px 18px 10px !important;">
        <div class="tab-content">

          {{-- ── Tab 1: Search ─────────────────────────────────────── --}}
          <div class="tab-pane fade show active" id="at1" role="tabpanel">
            <div class="mb-2">
              <input type="text" class="form-control" id="baseProductSearch"
                     placeholder="Type product name or code…" autocomplete="off" />
              <div class="form-text" style="font-size:11px;">Tab to qty · Enter to add</div>
            </div>
            <div id="searchResultList" class="search-result-list"></div>
          </div>

          {{-- ── Tab 2: New Product ────────────────────────────────── --}}
          <div class="tab-pane fade" id="at2" role="tabpanel">
            <div class="row g-2 mb-2">
              <div class="col-7">
                <label class="form-label fw-semibold" style="font-size:12px">Name <span class="text-danger">*</span></label>
                <input class="form-control form-control-sm" type="text" id="new-name" autocomplete="off" />
              </div>
              <div class="col-5">
                <label class="form-label fw-semibold" style="font-size:12px">Code</label>
                <input class="form-control form-control-sm" type="text" id="new-code" autocomplete="off" />
              </div>
            </div>
            <div class="row g-2 mb-2">
              <div class="col-4">
                <label class="form-label fw-semibold" style="font-size:12px">Unit</label>
                <input class="form-control form-control-sm" type="text" id="new-unit" value="Each" autocomplete="off" />
              </div>
              <div class="col-4">
                <label class="form-label fw-semibold" style="font-size:12px">Sell Price <span class="text-danger">*</span></label>
                <input class="form-control form-control-sm" type="number" id="new-selling-price" placeholder="0.00" />
              </div>
              <div class="col-4">
                <label class="form-label fw-semibold" style="font-size:12px">Cost Price</label>
                <input class="form-control form-control-sm" type="number" id="new-cost-price" placeholder="0.00" />
              </div>
            </div>
            <div class="row g-2 mb-2">
              <div class="col-6">
                <label class="form-label fw-semibold" style="font-size:12px">Quantity</label>
                <input class="form-control form-control-sm" type="number" id="new-stock-qty" value="0" />
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold" style="font-size:12px">Supplier</label>
                <select class="form-select form-select-sm" id="new-supplier">
                  <option value="">Select supplier</option>
                  @foreach($supplierRows as $sup)
                    <option value="{{ $sup->name }}" data-id="{{ $sup->id }}">{{ $sup->name }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="d-flex justify-content-end mt-3">
              <a href="#" class="btn btn-success btn-sm" id="submitAddBtn">
                <i class="ri-check-line"></i> Save to Catalogue &amp; Branch
              </a>
            </div>
            <div id="addSuccessNotice" class="mt-2" style="font-size:12px;color:#198754;display:none;">
              <i class="ri-check-double-line me-1"></i><span id="addSuccessText"></span>
            </div>
          </div>

          {{-- ── Tab 3: CSV Import (wizard) ───────────────────────── --}}
          <div class="tab-pane fade" id="at3" role="tabpanel">

            {{-- Step indicator --}}
            <div class="csv-step-indicator">
              <div class="csi-step active" id="csi1"><span class="csi-num">1</span>Guide</div>
              <div class="csi-line"></div>
              <div class="csi-step" id="csi2"><span class="csi-num">2</span>Supplier</div>
              <div class="csi-line"></div>
              <div class="csi-step" id="csi3"><span class="csi-num">3</span>Upload</div>
              <div class="csi-line"></div>
              <div class="csi-step" id="csi4"><span class="csi-num">4</span>Import</div>
            </div>

            {{-- Step 1: Guide + sample --}}
            <div class="csv-step active" id="csvStep1">
              <div style="font-size:13px;color:#374151;margin-bottom:12px;">
                Prepare a CSV file with the following columns:
              </div>
              <div style="background:#f8f9fa;border-radius:8px;padding:12px 14px;margin-bottom:14px;font-family:monospace;font-size:12px;color:#374151;overflow-x:auto;white-space:nowrap;">
                name, code, unit, selling_price, cost_price, quantity
              </div>
              <table style="width:100%;border-collapse:collapse;font-size:11px;margin-bottom:14px;">
                <thead>
                  <tr style="background:#eef0f7;">
                    <th style="padding:6px 8px;text-align:left;border-bottom:1px solid #dee2e6;">Column</th>
                    <th style="padding:6px 8px;text-align:left;border-bottom:1px solid #dee2e6;">Required</th>
                    <th style="padding:6px 8px;text-align:left;border-bottom:1px solid #dee2e6;">Notes</th>
                  </tr>
                </thead>
                <tbody>
                  <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:5px 8px;font-weight:600;">name</td><td style="padding:5px 8px;color:#dc2626;">Yes</td><td style="padding:5px 8px;color:#6c757d;">Product name (matched to catalogue)</td></tr>
                  <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:5px 8px;font-weight:600;">code</td><td style="padding:5px 8px;color:#6c757d;">No</td><td style="padding:5px 8px;color:#6c757d;">SKU / product code</td></tr>
                  <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:5px 8px;font-weight:600;">unit</td><td style="padding:5px 8px;color:#6c757d;">No</td><td style="padding:5px 8px;color:#6c757d;">Each, kg, g, Litre, Box…</td></tr>
                  <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:5px 8px;font-weight:600;">selling_price</td><td style="padding:5px 8px;color:#6c757d;">No</td><td style="padding:5px 8px;color:#6c757d;">MWK, numeric</td></tr>
                  <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:5px 8px;font-weight:600;">cost_price</td><td style="padding:5px 8px;color:#6c757d;">No</td><td style="padding:5px 8px;color:#6c757d;">MWK, numeric</td></tr>
                  <tr><td style="padding:5px 8px;font-weight:600;">quantity</td><td style="padding:5px 8px;color:#6c757d;">No</td><td style="padding:5px 8px;color:#6c757d;">Stock to add (defaults to 0)</td></tr>
                </tbody>
              </table>
              <div class="d-flex justify-content-between align-items-center">
                <a href="#" id="csvDownloadSample" style="font-size:12px;color:#4B5EBD;">
                  <i class="ri-download-line me-1"></i>Download sample CSV
                </a>
                <button type="button" class="btn btn-primary btn-sm" onclick="csvGoToStep(2)">
                  Next <i class="ri-arrow-right-s-line"></i>
                </button>
              </div>
            </div>

            {{-- Step 2: Supplier --}}
            <div class="csv-step" id="csvStep2">
              <label class="form-label fw-semibold" style="font-size:12px">Supplier <span class="text-danger">*</span></label>
              <select class="form-select form-select-sm mb-3" id="csv-supplier">
                <option value="">Select supplier</option>
                @foreach($supplierRows as $sup)
                  <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                @endforeach
              </select>
              <div style="font-size:11px;color:#6c757d;margin-bottom:14px;">
                Only suppliers in this branch's category are listed. New products without a catalogue match will be created under this supplier.
              </div>
              <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-sm" onclick="csvGoToStep(1)">
                  <i class="ri-arrow-left-s-line"></i> Back
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="csvStep2NextBtn" onclick="csvStep2Next()">
                  Next <i class="ri-arrow-right-s-line"></i>
                </button>
              </div>
            </div>

            {{-- Step 3: Upload --}}
            <div class="csv-step" id="csvStep3">
              <label class="form-label fw-semibold" style="font-size:12px">CSV File <span class="text-danger">*</span></label>
              <input class="form-control form-control-sm mb-2" type="file" id="csv-file" accept=".csv,.txt" />
              <div id="csvFilePreviewWrap" style="display:none;">
                <div style="font-size:11px;color:#6c757d;margin-bottom:6px;" id="csvFilePreviewLabel"></div>
                <div class="csv-preview-scroll" id="csvFilePreviewScroll" style="max-height:200px;"></div>
              </div>
              <div style="font-size:11px;color:#6c757d;margin:10px 0 14px;">
                The whole file is imported in one step — there's no separate validation pass. Rows are matched to
                existing catalogue products automatically; unmatched names are created as new products.
              </div>
              <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-secondary btn-sm" onclick="csvGoToStep(2)">
                  <i class="ri-arrow-left-s-line"></i> Back
                </button>
                <button type="button" class="btn btn-success btn-sm" id="csvImportBtn">
                  <i class="ri-upload-2-line"></i> Import CSV
                </button>
              </div>
            </div>

            {{-- Step 4: Result --}}
            <div class="csv-step" id="csvStep4">
              <div id="csvImportProgress" style="font-size:13px;color:#475569;margin-bottom:14px;text-align:center;padding:20px 0;"></div>
              <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-primary btn-sm" id="csvDoneBtn">
                  <i class="ri-check-line"></i> Done
                </button>
              </div>
            </div>

          </div>{{-- end at3 --}}

        </div>
      </div>

      
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     VIEW MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="viewProductModal" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-eye-line"></i> Branch Product Details</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:16px 20px !important;">
        <div class="mb-3 pb-2 border-bottom d-flex align-items-start justify-content-between">
          <div>
            <div style="font-size:17px;font-weight:700;color:#1e293b" id="vw-name"></div>
            <div style="font-size:12px;color:#6c757d" id="vw-meta-line"></div>
          </div>
          <div id="vw-badges" class="d-flex gap-2 flex-wrap justify-content-end"></div>
        </div>
        <div id="vw-price-notice" class="mb-3"
             style="background:#f0f3ff;border-left:3px solid #4B5EBD;border-radius:0 5px 5px 0;padding:7px 12px;font-size:11px;color:#3a4a9a;display:none;">
          <i class="ri-information-line me-1"></i><span id="vw-price-notice-text"></span>
        </div>
        <ul class="nav nav-tabs nav-sm mb-3" role="tablist" style="font-size:12px;">
          <li class="nav-item"><button class="nav-link active py-1 px-2" data-bs-toggle="tab" data-bs-target="#vw-t1"><i class="ri-money-dollar-circle-line me-1"></i>Pricing</button></li>
          <li class="nav-item"><button class="nav-link py-1 px-2"        data-bs-toggle="tab" data-bs-target="#vw-t2"><i class="ri-stack-line me-1"></i>Stock</button></li>
          <li class="nav-item"><button class="nav-link py-1 px-2"        data-bs-toggle="tab" data-bs-target="#vw-t3"><i class="ri-settings-3-line me-1"></i>Settings</button></li>
        </ul>
        <div class="tab-content">
          <div class="tab-pane fade show active" id="vw-t1">
            <div class="view-grid">
              <div class="view-item"><label>Selling Price (MWK)</label><div class="view-val price-cell" id="vw-sell"></div></div>
              <div class="view-item"><label>Cost Price (MWK)</label><div class="view-val" id="vw-cost"></div></div>
            </div>
          </div>
          <div class="tab-pane fade" id="vw-t2">
            <div class="view-grid">
              <div class="view-item"><label>Stock on Hand</label><div class="view-val" id="vw-stock"></div></div>
              <div class="view-item"><label>Reorder Point</label><div class="view-val" id="vw-reorder"></div></div>
              <div class="view-item"><label>Reorder Qty</label><div class="view-val" id="vw-reorder-qty"></div></div>
              <div class="view-item"><label>Max Stock</label><div class="view-val" id="vw-max"></div></div>
              <div class="view-item"><label>Barcode</label><div class="view-val" id="vw-barcode"></div></div>
              <div class="view-item"><label>Batch Number</label><div class="view-val" id="vw-batch"></div></div>
              <div class="view-item full"><label>Expiry Date</label><div class="view-val" id="vw-expiry"></div></div>
            </div>
          </div>
          <div class="tab-pane fade" id="vw-t3">
            <div class="view-grid">
              <div class="view-item"><label>Track Stock</label><div class="view-val" id="vw-track"></div></div>
              <div class="view-item"><label>Allow Negative Stock</label><div class="view-val" id="vw-neg"></div></div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="padding:10px 20px 14px;justify-content:space-between;">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
          <i class="ri-close-line me-1"></i> Close
        </button>
        <a href="#" class="btn btn-primary btn-sm" id="vwEditBtn">
          <i class="ri-edit-box-line me-1"></i> Edit
        </a>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     EDIT MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-edit-box-line"></i> Edit — <span id="editModalName"></span></h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>

      <ul class="nav" id="editModalTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="tab-branch-lnk" data-bs-toggle="tab" data-bs-target="#tab-branch" type="button" role="tab">Branch Info</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-base-lnk" data-bs-toggle="tab" data-bs-target="#tab-base" type="button" role="tab">Base Info</button>
        </li>
      </ul>

      <div class="modal-body" style="padding:14px 18px 10px !important;">
        <div class="tab-content">

          {{-- TAB 1: Branch --}}
          <div class="tab-pane fade show active" id="tab-branch" role="tabpanel">
            <form id="editDataForm">
              @csrf
              <input type="hidden" id="editId">
              <input type="hidden" id="editRow">
              <input type="hidden" id="editBaseProductId">

              <div class="row g-2 mb-1">
                <div class="col-6">
                  <label class="form-label fw-semibold" style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;">Product</label>
                  <input type="text" class="form-control form-control-sm edit-readonly-field" id="edit-ro-name" readonly tabindex="-1" />
                </div>
                <div class="col-3">
                  <label class="form-label fw-semibold" style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;">Unit</label>
                  <input type="text" class="form-control form-control-sm edit-readonly-field" id="edit-ro-unit" readonly tabindex="-1" />
                </div>
                <div class="col-3">
                  <label class="form-label fw-semibold" style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;">Code</label>
                  <input type="text" class="form-control form-control-sm edit-readonly-field" id="edit-ro-code" readonly tabindex="-1" />
                </div>
              </div>
              <a href="#" class="edit-tab-crosslink mb-2" id="goToBaseTabLink">
                <i class="ri-arrow-right-s-line"></i> Edit name, unit or code in Base Info tab
              </a>
              <hr style="border:none;border-top:0.5px solid #e9ecef;margin:10px 0 12px;">

              <div class="edit-section-title"><i class="ri-coin-line me-1"></i>Selling Price</div>
              <div class="price-source-toggle">
                <div class="price-source-card active-base" id="priceSourceBase" onclick="setPriceSource('base')">
                  <div class="psc-label"><span class="psc-dot" id="dotBase" style="background:#059669;"></span>Base catalogue</div>
                  <div class="psc-value" id="pscBaseVal">—</div>
                  <div class="psc-desc">Inherited · all branches</div>
                </div>
                <div class="price-source-card" id="priceSourceBranch" onclick="setPriceSource('branch')">
                  <div class="psc-label"><span class="psc-dot" id="dotBranch" style="background:#1d4ed8;opacity:.3;"></span>This branch only</div>
                  <div class="psc-value" id="pscBranchVal" style="color:#9ca3af;">—</div>
                  <div class="psc-desc">Override for this branch</div>
                </div>
              </div>
              <div class="price-context-hint pch-base" id="priceContextHint">
                <i class="ri-information-line" style="font-size:13px;flex-shrink:0;margin-top:1px;"></i>
                <span id="priceContextHintText"></span>
              </div>
              <div id="branchPriceFields" style="display:none;">
                <div class="row g-2 mb-2">
                  <div class="col-6">
                    <label class="form-label fw-semibold" style="font-size:12px">Selling Price <span class="text-danger">*</span></label>
                    <input class="form-control form-control-sm" type="number" id="editSellPrice" placeholder="0.00" />
                  </div>
                  <div class="col-6">
                    <label class="form-label fw-semibold" style="font-size:12px">Cost Price</label>
                    <input class="form-control form-control-sm" type="number" id="editCostPrice" placeholder="0.00" />
                  </div>
                </div>
              </div>

              <hr style="border:none;border-top:0.5px solid #e9ecef;margin:10px 0 12px;">
              <div class="edit-section-title"><i class="ri-stack-line me-1"></i>Stock</div>
              <div class="row g-2 mb-2">
                <div class="col-3">
                  <label class="form-label fw-semibold" style="font-size:12px">Qty</label>
                  <input class="form-control form-control-sm" type="number" id="editStockQty" />
                </div>
                <div class="col-3">
                  <label class="form-label fw-semibold" style="font-size:12px">Reorder Point</label>
                  <input class="form-control form-control-sm" type="number" min="0" id="editReorderPoint" />
                </div>
                <div class="col-3">
                  <label class="form-label fw-semibold" style="font-size:12px">Reorder Qty</label>
                  <input class="form-control form-control-sm" type="number" min="0" id="editReorderQty" />
                </div>
                <div class="col-3">
                  <label class="form-label fw-semibold" style="font-size:12px">Max Stock</label>
                  <input class="form-control form-control-sm" type="number" min="0" id="editMaxStock" />
                </div>
              </div>

              <hr style="border:none;border-top:0.5px solid #e9ecef;margin:10px 0 12px;">
              <div class="edit-section-title"><i class="ri-qr-code-line me-1"></i>Barcode &amp; Batch</div>
              <div class="row g-2 mb-2">
                <div class="col-4">
                  <label class="form-label fw-semibold" style="font-size:12px">Barcode</label>
                  <input class="form-control form-control-sm" type="text" id="editBarcode" autocomplete="off" />
                </div>
                <div class="col-4">
                  <label class="form-label fw-semibold" style="font-size:12px">Batch</label>
                  <input class="form-control form-control-sm" type="text" id="editBatch" autocomplete="off" />
                </div>
                <div class="col-4">
                  <label class="form-label fw-semibold" style="font-size:12px">Expiry</label>
                  <input class="form-control form-control-sm" type="date" id="editExpiry" />
                </div>
              </div>

              <hr style="border:none;border-top:0.5px solid #e9ecef;margin:10px 0 12px;">
              <div class="edit-section-title"><i class="ri-settings-3-line me-1"></i>Settings</div>
              <div class="row g-2">
                <div class="col-4">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="editTrackStock">
                    <label class="form-check-label" for="editTrackStock" style="font-size:12px">Track stock</label>
                  </div>
                </div>
                <div class="col-4">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="editAllowNeg">
                    <label class="form-check-label" for="editAllowNeg" style="font-size:12px">Allow negative</label>
                  </div>
                </div>
                <div class="col-4">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="editIsActive">
                    <label class="form-check-label" for="editIsActive" style="font-size:12px">Active</label>
                  </div>
                </div>
              </div>
            </form>
          </div>

          {{-- TAB 2: Base --}}
          <div class="tab-pane fade" id="tab-base" role="tabpanel">
            <div class="bp-edit-warning">
              <i class="ri-alert-line" style="font-size:14px;flex-shrink:0;margin-top:1px;"></i>
              Changes here update the base catalogue and affect all branches using this product.
            </div>
            <form id="editBaseProductForm">
              @csrf
              <input type="hidden" id="bpEditId">
              <div class="row g-2 mb-2">
                <div class="col-7">
                  <label class="form-label fw-semibold" style="font-size:12px">Name <span class="text-danger">*</span></label>
                  <input class="form-control form-control-sm" type="text" id="bpEditName" autocomplete="off" />
                </div>
                <div class="col-5">
                  <label class="form-label fw-semibold" style="font-size:12px">Code</label>
                  <input class="form-control form-control-sm" type="text" id="bpEditCode" autocomplete="off" />
                </div>
              </div>
              <div class="row g-2 mb-2">
                <div class="col-4">
                  <label class="form-label fw-semibold" style="font-size:12px">Unit</label>
                  <input class="form-control form-control-sm" type="text" id="bpEditUnit" autocomplete="off" />
                </div>
                <div class="col-4">
                  <label class="form-label fw-semibold" style="font-size:12px">Sell Price <span class="text-danger">*</span></label>
                  <input class="form-control form-control-sm" type="number" id="bpEditSellPrice" placeholder="0.00" />
                </div>
                <div class="col-4">
                  <label class="form-label fw-semibold" style="font-size:12px">Cost Price</label>
                  <input class="form-control form-control-sm" type="number" id="bpEditCostPrice" placeholder="0.00" />
                </div>
              </div>
              <div class="row g-2 mb-2">
                <div class="col-12">
                  <label class="form-label fw-semibold" style="font-size:12px">Supplier</label>
                  <select class="form-select form-select-sm" id="bpEditSupplier">
                    <option value="">Select supplier</option>
                    @foreach($supplierRows as $sup)
                      <option value="{{ $sup->name }}">{{ $sup->name }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="alert border-0 py-2 px-3 mb-0"
                   style="background:#ecfdf5;border-left:2px solid #059669;border-radius:0 5px 5px 0;font-size:11px;color:#065f46;">
                <i class="ri-information-line me-1"></i>
                These are the catalogue defaults shown in <span style="color:#059669;font-weight:700;">green</span> for branches without a price override.
              </div>
            </form>
          </div>

        </div>
      </div>

      <div class="modal-footer" style="padding:10px 18px 14px;justify-content:flex-end;gap:8px;">
        <a href="#" class="btn btn-secondary btn-sm" id="cancelEditBtn">Cancel</a>
        <a href="#" class="btn btn-primary btn-sm" id="submitEditBtn">
          <i class="ri-check-line me-1"></i> Update Branch Product
        </a>
        <a href="#" class="btn btn-success btn-sm" id="submitBaseProductBtn" style="display:none;">
          <i class="ri-check-line me-1"></i> Update Base Product
        </a>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     DELETE MODAL (single row)
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="deleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-danger">
        <h5 class="modal-title mh-title"><i class="ri-delete-bin-line"></i> Remove from Branch</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center py-4">
        <i class="ri-error-warning-line text-danger" style="font-size:60px"></i>
        <h5 class="mt-2 mb-1">Remove <span id="deleteLabel" class="text-danger"></span>?</h5>
        <p style="font-size:13px;color:#6c757d;margin-bottom:0;">
          Removes from <strong>{{ $selectedBranch->name ?? 'this branch' }}</strong> only. Base product is kept.
        </p>
        <input type="hidden" id="deleteId">
        <input type="hidden" id="deleteRow">
      </div>
      <div class="modal-footer justify-content-center gap-2" style="padding:10px 20px 18px;">
        <a href="#" class="btn btn-secondary btn-sm px-4" id="keepBtn">Keep</a>
        <a href="#" class="btn btn-danger btn-sm px-4" id="submitDeleteBtn">Remove</a>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     SET BRANCH PRICES MODAL — professional table layout
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="setBranchPricesModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title">
          <i class="ri-price-tag-3-line"></i> Set Branch Prices — <span id="sbpCount">0</span> product(s)
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:16px 18px !important;">
        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:8px;margin-bottom:10px;">
          <div style="font-size:11px;color:#6c757d;">
            Leave an input blank to keep its current price unchanged.
          </div>
          <div class="sbp-toolbar" style="margin-bottom:0;">
            <button type="button" class="sbp-tool-btn" id="sbpFillBaseBtn">
              <i class="ri-arrow-go-back-line"></i> Use Base Prices
            </button>
            <button type="button" class="sbp-tool-btn sbp-tool-clear" id="sbpClearAllBtn">
              <i class="ri-close-line"></i> Clear All
            </button>
          </div>
        </div>

        <div class="sbp-table-wrap">
          <table class="sbp-table">
            <thead>
              <tr>
                <th class="sbp-col-name">Product</th>
                <th class="sbp-th-center">Branch Price (MWK)</th>
              </tr>
            </thead>
            <tbody id="sbpProductList"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer" style="padding:10px 18px 14px;gap:8px;">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" id="sbpSubmitBtn">
          <i class="ri-check-line me-1"></i> Save Branch Prices
        </button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {

    toastr.options = { closeButton:true, progressBar:true, showMethod:'slideDown', timeOut:5000, allowHtml:true };

    var BRANCH_ID = {{ $selectedBranch->id ?? 'null' }};

    // ── Helpers ───────────────────────────────────────────────────────────
    function handleAjaxError(xhr, status) {
        if (status === 'timeout') { toastr.error('Request timed out.', 'Timeout'); return; }
        if (xhr.status === 422) {
            var e = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : {};
            var m = ''; $.each(e, function(k,v) { m += v + '\n'; });
            toastr.error(m || (xhr.responseJSON && xhr.responseJSON.error) || 'Validation failed.', 'Error');
        } else if (xhr.status === 500) {
            toastr.error('Server error.', 'Error');
        } else {
            toastr.error('Unspecified error.', 'Error');
        }
    }

    function fmtNum(val, dec) {
        dec = dec === undefined ? 2 : dec;
        if (val === null || val === '' || val === undefined) return '—';
        var n = parseFloat(val);
        return isNaN(n) ? '—' : n.toLocaleString('en-US', {minimumFractionDigits:dec, maximumFractionDigits:dec});
    }

    function yn(val) {
        return parseInt(val) === 1
            ? '<span class="badge bg-success" style="font-size:11px">Yes</span>'
            : '<span class="badge bg-secondary" style="font-size:11px">No</span>';
    }

    function buildRow(p) {
        var sq = parseFloat(p.stock_quantity || 0);
        var rp = parseFloat(p.reorder_point  || 0);
        var sc = sq <= 0 ? 'stock-zero' : (sq <= rp ? 'stock-low' : 'stock-ok');
        var d  = function(v){ return (v || '').toString().replace(/"/g, '&quot;'); };
        var sellClass = p.sell_is_branch ? 'price-branch' : 'price-base';
        var displayPrice = p.selling_price !== null ? p.selling_price : p.bp_sell;

        return `<tr id="${p.row}">
            <td>
                <input type="checkbox" class="selectRow" value="${p.id}" data-row-id="${p.row}"
                       data-name="${d(p.name)}" data-unit="${d(p.unit)}"
                       data-stock="${p.stock_quantity}" data-bp-sell="${p.bp_sell || ''}"
                       data-sell="${p.selling_price !== null ? p.selling_price : ''}">
                &nbsp;${p.name || ''}
            </td>
            <td>${p.code || '—'}</td>
            <td>${p.unit || '—'}</td>
            <td><span class="${sc}">${fmtNum(sq, 0)}</span></td>
            <td><span class="${sellClass}" style="font-size:12px">${fmtNum(displayPrice)}</span></td>
            <td>${p.batch_number || '—'}</td>
            <td>${p.expiry_date  || '—'}</td>
            <td>
                <a href="#" class="viewDataBtn"
                   data-id="${p.id}" data-name="${d(p.name)}" data-code="${d(p.code)}"
                   data-unit="${d(p.unit)}" data-supplier="${d(p.supplier)}"
                   data-barcode="${d(p.primary_barcode)}" data-batch="${d(p.batch_number)}"
                   data-expiry="${d(p.expiry_date)}"
                   data-cost="${p.cost_price !== null ? p.cost_price : ''}"
                   data-sell="${p.selling_price !== null ? p.selling_price : ''}"
                   data-stock="${p.stock_quantity}" data-reorder="${p.reorder_point}"
                   data-reorder-qty="${p.reorder_quantity !== null ? p.reorder_quantity : ''}"
                   data-max="${p.max_stock !== null ? p.max_stock : ''}"
                   data-active="${p.is_active}" data-track="${p.track_stock}" data-neg="${p.allow_negative_stock}"
                   data-sell-is-branch="${p.sell_is_branch ? 1 : 0}"
                   data-cost-is-branch="${p.cost_is_branch ? 1 : 0}"
                   data-bp-sell="${p.bp_sell || ''}" data-bp-cost="${p.bp_cost || ''}">
                   <i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px"></i>
                </a>
                <a href="#" class="editDataBtn"
                   data-id="${p.id}" data-row="${p.row}" data-name="${d(p.name)}"
                   data-unit="${d(p.unit)}" data-code="${d(p.code)}" data-supplier="${d(p.supplier)}"
                   data-sell="${p.selling_price !== null ? p.selling_price : ''}"
                   data-cost="${p.cost_price !== null ? p.cost_price : ''}"
                   data-stock="${p.stock_quantity}" data-reorder="${p.reorder_point}"
                   data-reorder-qty="${p.reorder_quantity !== null ? p.reorder_quantity : ''}"
                   data-max="${p.max_stock !== null ? p.max_stock : ''}"
                   data-barcode="${d(p.primary_barcode)}" data-batch="${d(p.batch_number)}"
                   data-expiry="${d(p.expiry_date)}" data-active="${p.is_active}"
                   data-track="${p.track_stock}" data-neg="${p.allow_negative_stock}"
                   data-sell-is-branch="${p.sell_is_branch ? 1 : 0}"
                   data-cost-is-branch="${p.cost_is_branch ? 1 : 0}"
                   data-bp-sell="${p.bp_sell || ''}" data-bp-cost="${p.bp_cost || ''}"
                   data-base-product-id="${p.base_product_id || ''}">
                   <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px"></i>
                </a>
                <a href="#" class="deleteDataBtn"
                   data-label="${d(p.name)}" data-id="${p.id}" data-row="${p.row}">
                   <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px"></i>
                </a>
            </td>
        </tr>`;
    }

    function updateSelectedCount() {
        var count = $('.selectRow:checked').length;
        var badge = $('#bulkActionsHeaderCount');
        if (count > 0) {
            badge.text(count).addClass('show');
            $('#bulkActionsHeaderBtn').addClass('enabled').prop('disabled', false)
                .attr('title', count + ' selected — click for bulk actions');
        } else {
            badge.text('').removeClass('show');
            $('#bulkActionsHeaderBtn').removeClass('enabled').prop('disabled', true)
                .attr('title', 'Select rows to enable bulk actions');
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    //  DataTable
    // ════════════════════════════════════════════════════════════════════════
    @if($selectedBranch)

    var table = $('#maintable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[100, 250, 500, -1], [100, 250, 500, 'All']],
        fixedColumns: { leftColumns: 1 },
        scrollX: true,
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: 0,      className: 'text-start'  }
        ],
        buttons: [
            { extend: 'excelHtml5', title: @json($maintableTitle), exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'csvHtml5',   title: @json($maintableTitle), exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'pdfHtml5',   title: @json($maintableTitle), exportOptions: { columns: ':visible:not(:last-child)' },
              customize: function(doc) {
                doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
              }
            }
        ]
    });
    window._dt = table;
    table.buttons().container().appendTo($('#buttonsModal .buttons'));

    // ── Overview modal ────────────────────────────────────────────────────
    $('#overviewBtn').on('click', function(e) { e.preventDefault(); $('#overviewModal').modal('show'); });

    // ════════════════════════════════════════════════════════════════════════
    //  ADD PRODUCT — search tab
    // ════════════════════════════════════════════════════════════════════════
    var allBaseProducts = [];

    function loadBaseProducts() {
        if (allBaseProducts.length) return;
        $.ajax({
            type: 'GET',
            url:  '{{ route("retail.operations.baseproducts.search") }}',
            data: { branch_id: BRANCH_ID },
            success: function(data) { allBaseProducts = data.products || []; }
        });
    }

    function softResetAddModal() {
        $('#baseProductSearch').val('');
        $('#searchResultList').hide();
        $('#addSuccessNotice').hide();
        $('#new-name, #new-selling-price, #new-cost-price, #new-code').val('');
        $('#new-stock-qty').val('0');
        $('#new-unit').val('Each');
        $('#new-supplier').val('');
        $('#csv-supplier').val('');
        $('#csv-file').val('');
        $('#csvFilePreviewWrap').hide();
        $('#csvFilePreviewScroll').html('');
        $('#csvImportProgress').html('');
        csvGoToStep(1);
    }

    $('#addProductBtn').on('click', function(e) {
        e.preventDefault();
        softResetAddModal();
        loadBaseProducts();
        $('#addProductModal').modal('show');
        setTimeout(function() { $('#baseProductSearch').focus(); }, 400);
    });

    $('#addProductModal').on('hidden.bs.modal', softResetAddModal);

    $('#baseProductSearch').on('input', function() {
        var q = $(this).val().trim().toLowerCase();
        if (!q) { $('#searchResultList').hide(); return; }
        var results = allBaseProducts.filter(function(p) {
            return p.name.toLowerCase().indexOf(q) >= 0
                || (p.code && p.code.toLowerCase().indexOf(q) >= 0);
        }).slice(0, 30);
        renderSearchResults(results, q);
    });

    function renderSearchResults(results, q) {
        var list = $('#searchResultList');
        if (!results.length) {
            list.html('<div style="padding:14px;text-align:center;color:#94a3b8;font-size:12px;"><i class="ri-search-line me-1"></i>No products found</div>').show();
            return;
        }
        var html = '';
        results.forEach(function(p) {
            var re     = new RegExp('(' + q.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
            var nameHl = p.name.replace(re, '<strong>$1</strong>');
            var codeStr = p.code ? ' <span class="sri-code">(' + p.code + ')</span>' : '';
            var priceDisp = p.selling_price
                ? parseFloat(p.selling_price).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})
                : '—';
            var badge     = [p.unit, priceDisp].filter(Boolean).join(' / ');
            var wasAdded  = !!_addedMap[p.id];
            var btnDisabled = wasAdded ? 'disabled' : '';
            var addedText   = _addedMap[p.id] || '';
            var msgDisplay  = wasAdded ? 'flex' : 'none';

            html += `
            <div class="search-result-item" data-id="${p.id}">
                <div class="sri-row">
                    <div class="sri-name" title="${p.name}">${nameHl}${codeStr}</div>
                    ${badge ? `<span class="sri-meta">${badge}</span>` : ''}
                    <input type="number" class="sri-qty-input" id="sri_qty_${p.id}"
                           placeholder="Qty" min="0" value="0" ${btnDisabled}
                           onkeydown="if(event.key==='Enter'){event.preventDefault();addProductFromSearch(${p.id});}" />
                    <button type="button" class="sri-add-btn" id="sri_btn_${p.id}"
                            onclick="addProductFromSearch(${p.id})" ${btnDisabled}>
                        <i class="ri-add-line"></i> Add
                    </button>
                    <span class="sri-added-msg" id="sri_msg_${p.id}" style="display:${msgDisplay};">
                        <i class="ri-check-double-line"></i>
                        <span id="sri_msg_text_${p.id}">${addedText}</span>
                    </span>
                </div>
            </div>`;
        });
        list.html(html).show();
        setTimeout(function() { list.find('.sri-qty-input:not(:disabled)').first().focus(); }, 50);
    }

    var _addedMap = {};

    window.addProductFromSearch = function(pid) {
        var qty = parseFloat($('#sri_qty_' + pid).val());
        if (isNaN(qty) || qty < 0) {
            toastr.warning('Enter a valid quantity.', 'Required');
            $('#sri_qty_' + pid).focus();
            return;
        }
        var btn      = $('#sri_btn_' + pid);
        var qtyInput = $('#sri_qty_' + pid);
        btn.prop('disabled', true);
        qtyInput.prop('disabled', true);

        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type: 'POST',
            url:  '{{ route("retail.operations.branchproducts.upsert") }}',
            timeout: 60000,
            data: {
                branch_id:            BRANCH_ID,
                base_product_id:      pid,
                stock_quantity:       qty,
                track_stock:          1,
                is_active:            1,
                allow_negative_stock: 0,
                _token:               '{{ csrf_token() }}'
            },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.product.name + ' added to branch.', 'Success');
                    if (window._dt) {
                        if (table.row('#' + data.product.row).length) table.row('#' + data.product.row).remove();
                        table.row.add($(buildRow(data.product))).draw(false);
                    }
                    var msg = qty > 0 ? qty + ' added' : '0 added';
                    _addedMap[pid] = msg;
                    $('#sri_msg_text_' + pid).text(msg);
                    $('#sri_msg_' + pid).show();
                } else {
                    btn.prop('disabled', false);
                    qtyInput.prop('disabled', false);
                    toastr.error(data.error || 'Error.', 'Error');
                }
            },
            error: function() {
                btn.prop('disabled', false);
                qtyInput.prop('disabled', false);
                handleAjaxError.apply(this, arguments);
            }
        });
    };

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#baseProductSearch, #searchResultList').length) {
            $('#searchResultList').hide();
        }
    });

    // ── New product save ──────────────────────────────────────────────────
    $('#submitAddBtn').on('click', function(e) {
        e.preventDefault();
        var name = $('#new-name').val().trim();
        if (!name) { toastr.warning('Product name is required.', 'Required'); $('#new-name').focus(); return; }
        var sell = $('#new-selling-price').val();
        if (!sell || parseFloat(sell) < 0) { toastr.warning('Selling price is required.', 'Required'); $('#new-selling-price').focus(); return; }

        var self = $(this); self.prop('disabled', true);
        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });

        $.ajax({
            type: 'POST',
            url:  '{{ route("retail.operations.baseproducts.insert") }}',
            timeout: 60000,
            data: {
                name: name, selling_price: sell,
                cost_price: $('#new-cost-price').val(),
                unit: $('#new-unit').val() || 'Each',
                code: $('#new-code').val(),
                supplier: $('#new-supplier').val(),
                is_product: 1, _token: '{{ csrf_token() }}'
            },
            beforeSend: function() { $('#progressBar').show(); },
            success: function(bpData) {
                if (bpData.status === 201) {
                    $.ajax({
                        type: 'POST',
                        url:  '{{ route("retail.operations.branchproducts.upsert") }}',
                        timeout: 60000,
                        data: {
                            branch_id: BRANCH_ID,
                            base_product_id: bpData.product.id,
                            stock_quantity: $('#new-stock-qty').val() || 0,
                            // Branch price = same as entered selling price (branch override takes priority)
                            selling_price: sell,
                            cost_price: $('#new-cost-price').val() || null,
                            reorder_point: 0, track_stock: 1,
                            allow_negative_stock: 0, is_active: 1,
                            _token: '{{ csrf_token() }}'
                        },
                        complete: function() { $('#progressBar').hide(); self.prop('disabled', false); },
                        success: function(data) {
                            if (data.status === 201) {
                                toastr.success('Product created and added to branch.', 'Success');
                                if (window._dt) {
                                    if (table.row('#' + data.product.row).length) table.row('#' + data.product.row).remove();
                                    table.row.add($(buildRow(data.product))).draw(false);
                                }
                                allBaseProducts = [];
                                loadBaseProducts();
                                $('#new-name, #new-selling-price, #new-cost-price, #new-code').val('');
                                $('#new-stock-qty').val('0');
                                $('#new-unit').val('Each');
                                $('#addSuccessText').text('"' + name + '" added successfully.');
                                $('#addSuccessNotice').show();
                                $('#new-name').focus();
                            } else {
                                toastr.error(data.error || 'Failed to assign to branch.', 'Error');
                            }
                        },
                        error: function() { $('#progressBar').hide(); self.prop('disabled', false); handleAjaxError.apply(this, arguments); }
                    });
                } else {
                    $('#progressBar').hide(); self.prop('disabled', false);
                    toastr.error(bpData.error || 'Failed to create product.', 'Error');
                }
            },
            error: function() { $('#progressBar').hide(); self.prop('disabled', false); handleAjaxError.apply(this, arguments); }
        });
    });

    // ════════════════════════════════════════════════════════════════════════
    //  CSV WIZARD — single-step server-side import (chunked on the backend)
    // ════════════════════════════════════════════════════════════════════════
    window.csvGoToStep = function(step) {
        $('.csv-step').removeClass('active');
        $('#csvStep' + step).addClass('active');
        for (var i = 1; i <= 4; i++) {
            var el = document.getElementById('csi' + i);
            el.className = 'csi-step' + (i < step ? ' done' : (i === step ? ' active' : ''));
        }
    };

    window.csvStep2Next = function() {
        if (!$('#csv-supplier').val()) {
            toastr.warning('Select a supplier.', 'Required');
            return;
        }
        csvGoToStep(3);
    };

    $('#csvDownloadSample').on('click', function(e) {
        e.preventDefault();
        var csv = 'name,code,unit,selling_price,cost_price,quantity\nSample Product,SKU001,Each,1500.00,1000.00,50\n';
        var blob = new Blob([csv], {type: 'text/csv'});
        var url  = URL.createObjectURL(blob);
        var a    = document.createElement('a');
        a.href   = url; a.download = 'branch_products_sample.csv'; a.click();
        URL.revokeObjectURL(url);
    });

    // Lightweight client-side preview of the chosen file (first N lines)
    // purely for visual confirmation — no validation, no matching logic.
    $('#csv-file').on('change', function() {
        var file = this.files[0];
        if (!file) { $('#csvFilePreviewWrap').hide(); return; }

        var reader = new FileReader();
        reader.onload = function(e) {
            var text  = e.target.result;
            var lines = text.split(/\r\n|\r|\n/).filter(function(l) { return l.trim() !== ''; });
            var dataLineCount = Math.max(0, lines.length - 1);

            var previewLines = lines.slice(0, 50);
            var html = previewLines.map(function(line, idx) {
                var cls = idx === 0 ? 'font-weight:700;color:#4B5EBD;' : 'color:#374151;';
                return '<div class="csv-preview-row"><span class="cpr-name" style="' + cls + '">' +
                       $('<div>').text(line).html() + '</span></div>';
            }).join('');

            $('#csvFilePreviewLabel').text(dataLineCount + ' row(s) detected — showing first ' + (previewLines.length - 1) + ' below');
            $('#csvFilePreviewScroll').html(html);
            $('#csvFilePreviewWrap').show();
        };
        reader.readAsText(file);
    });

    $('#csvImportBtn').on('click', function() {
        var supId = $('#csv-supplier').val();
        var file  = $('#csv-file')[0].files[0];
        if (!supId) { toastr.warning('Select a supplier first.', 'Required'); csvGoToStep(2); return; }
        if (!file)  { toastr.warning('Choose a CSV file.', 'Required'); return; }

        var fd = new FormData();
        fd.append('csv_file', file);
        fd.append('branch_id', BRANCH_ID);
        fd.append('supplier_id', supId);
        fd.append('_token', '{{ csrf_token() }}');

        var self = $(this); self.prop('disabled', true);
        csvGoToStep(4);
        $('#csvImportProgress').html('<i class="ri-loader-4-line" style="font-size:22px;animation:spin 1s linear infinite;display:inline-block;"></i><div class="mt-2">Importing — this can take a moment for large files…</div>');

        $.ajax({
            type: 'POST',
            url:  '{{ route("retail.operations.branchproducts.csv.upload") }}',
            data: fd, processData: false, contentType: false, timeout: 180000,
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 200) {
                    $('#csvImportProgress').html(
                        '<i class="ri-checkbox-circle-line text-success" style="font-size:34px;"></i>' +
                        '<div class="mt-2" style="font-weight:600;color:#1e293b;">' + data.success + '</div>'
                    );
                    toastr.success(data.success, 'Import complete');
                } else {
                    $('#csvImportProgress').html(
                        '<i class="ri-error-warning-line text-danger" style="font-size:34px;"></i>' +
                        '<div class="mt-2">' + (data.error || 'Import failed.') + '</div>'
                    );
                    toastr.error(data.error || 'Import failed.', 'Error');
                }
            },
            error: function(xhr, status) {
                $('#csvImportProgress').html(
                    '<i class="ri-error-warning-line text-danger" style="font-size:34px;"></i>' +
                    '<div class="mt-2">Import failed — please try again.</div>'
                );
                handleAjaxError(xhr, status);
            }
        });
    });

    $('#csvDoneBtn').on('click', function() {
        $('#addProductModal').modal('hide');
        setTimeout(function() { location.reload(); }, 200);
    });

    // ════════════════════════════════════════════════════════════════════════
    //  VIEW
    // ════════════════════════════════════════════════════════════════════════
    var _viewData = {};

    $('#tbody').on('click', '.viewDataBtn', function(e) {
        e.preventDefault();
        var b = $(this);
        _viewData = {
            id: b.data('id'), name: b.data('name'), code: b.data('code'),
            unit: b.data('unit'), supplier: b.data('supplier'),
            barcode: b.data('barcode'), batch: b.data('batch'), expiry: b.data('expiry'),
            cost: b.data('cost'), sell: b.data('sell'),
            stock: b.data('stock'), reorder: b.data('reorder'),
            reorderQty: b.data('reorder-qty'), max: b.data('max'),
            active: b.data('active'), track: b.data('track'), neg: b.data('neg'),
            sellIsBranch: b.data('sell-is-branch'),
            costIsBranch: b.data('cost-is-branch'),
            bpSell: b.data('bp-sell'), bpCost: b.data('bp-cost'),
            editRow: b.closest('tr').attr('id')
        };

        function mv(val) {
            return (val === '' || val === null || val === undefined)
                ? '<span class="text-muted fst-italic">—</span>' : val;
        }

        $('#vw-name').text(_viewData.name);
        $('#vw-meta-line').text(
            [_viewData.code ? 'Code: ' + _viewData.code : '', _viewData.unit, _viewData.supplier]
            .filter(Boolean).join(' · ')
        );
        $('#vw-badges').html(parseInt(_viewData.active) === 1
            ? '<span class="badge bg-success">Active</span>'
            : '<span class="badge bg-danger">Inactive</span>');

        var noticeParts = [];
        if (parseInt(_viewData.sellIsBranch) === 1) {
            noticeParts.push('Selling price is a <strong>branch override</strong> (blue).');
        } else {
            noticeParts.push('Using base catalogue price' + (_viewData.bpSell ? ' (MWK ' + parseFloat(_viewData.bpSell).toLocaleString('en-US',{minimumFractionDigits:2}) + ')' : '') + ' (green).');
        }
        $('#vw-price-notice-text').html(noticeParts.join(' '));
        $('#vw-price-notice').show();

        var sellClass = parseInt(_viewData.sellIsBranch) === 1 ? 'price-branch' : 'price-base';
        $('#vw-sell').html('<span class="' + sellClass + '">' + fmtNum(_viewData.sell) + '</span>');
        $('#vw-cost').text(fmtNum(_viewData.cost));

        var sq = parseFloat(_viewData.stock);
        var rp = parseFloat(_viewData.reorder || 0);
        var sc = sq <= 0 ? 'stock-zero' : (sq <= rp ? 'stock-low' : 'stock-ok');
        $('#vw-stock').html('<span class="fw-bold ' + sc + '" style="font-size:15px">' + fmtNum(sq, 0) + '</span>');
        $('#vw-reorder').text(fmtNum(_viewData.reorder, 0));
        $('#vw-reorder-qty').html(mv(fmtNum(_viewData.reorderQty, 0)));
        $('#vw-max').html(mv(fmtNum(_viewData.max, 0)));
        $('#vw-barcode').html(mv(_viewData.barcode));
        $('#vw-batch').html(mv(_viewData.batch));
        $('#vw-expiry').html(mv(_viewData.expiry));
        $('#vw-track').html(yn(_viewData.track));
        $('#vw-neg').html(yn(_viewData.neg));

        $('button[data-bs-target="#vw-t1"]').tab('show');
        $('#viewProductModal').modal('show');
    });

    $('#vwEditBtn').on('click', function(e) {
        e.preventDefault();
        $('#viewProductModal').modal('hide');
        setTimeout(function() {
            var $btn = $('#' + _viewData.editRow).find('.editDataBtn');
            if ($btn.length) $btn.trigger('click');
        }, 350);
    });

    // ════════════════════════════════════════════════════════════════════════
    //  PRICE SOURCE TOGGLE
    // ════════════════════════════════════════════════════════════════════════
    window._currentPriceSource = 'base';
    window._bpSellStored       = '';
    window._branchSellStored   = '';

    window.setPriceSource = function(source) {
        window._currentPriceSource = source;
        var cardBase   = document.getElementById('priceSourceBase');
        var cardBranch = document.getElementById('priceSourceBranch');
        var dotBase    = document.getElementById('dotBase');
        var dotBranch  = document.getElementById('dotBranch');
        var hint       = document.getElementById('priceContextHint');
        var hintText   = document.getElementById('priceContextHintText');
        var fields     = document.getElementById('branchPriceFields');
        var branchVal  = document.getElementById('pscBranchVal');

        if (source === 'base') {
            cardBase.className   = 'price-source-card active-base';
            cardBranch.className = 'price-source-card';
            dotBase.style.opacity   = '1';
            dotBranch.style.opacity = '.3';
            branchVal.style.color   = '#9ca3af';
            fields.style.display    = 'none';
            hint.className = 'price-context-hint pch-base';
            var bpFmt = window._bpSellStored
                ? 'MWK ' + parseFloat(window._bpSellStored).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})
                : '—';
            hintText.innerHTML = 'Using the base catalogue price of <strong>' + bpFmt + '</strong>.';
        } else {
            cardBranch.className = 'price-source-card active-branch';
            cardBase.className   = 'price-source-card';
            dotBranch.style.opacity = '1';
            dotBase.style.opacity   = '.3';
            fields.style.display    = 'block';
            hint.className = 'price-context-hint pch-branch';
            hintText.innerHTML = 'Enter a price below — overrides the catalogue for <strong>this branch only</strong>.';
            if (window._branchSellStored) {
                $('#editSellPrice').val(window._branchSellStored);
                branchVal.style.color = '#1d4ed8';
                branchVal.textContent = 'MWK ' + parseFloat(window._branchSellStored).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
            } else {
                branchVal.style.color = '#9ca3af';
                branchVal.textContent = '— enter below';
            }
        }
    };

    $(document).on('input', '#editSellPrice', function() {
        if (window._currentPriceSource === 'branch') {
            var v = parseFloat($(this).val());
            var bv = document.getElementById('pscBranchVal');
            bv.style.color  = '#1d4ed8';
            bv.textContent  = isNaN(v) ? '— enter below'
                : 'MWK ' + v.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
        }
    });

    // ════════════════════════════════════════════════════════════════════════
    //  EDIT MODAL — tab switching
    // ════════════════════════════════════════════════════════════════════════
    $('#tab-branch-lnk').on('shown.bs.tab', function() { $('#submitBaseProductBtn').hide(); $('#submitEditBtn').show(); });
    $('#tab-base-lnk').on('shown.bs.tab',   function() { $('#submitEditBtn').hide();        $('#submitBaseProductBtn').show(); });
    $('#goToBaseTabLink').on('click',        function(e) { e.preventDefault(); $('#tab-base-lnk').tab('show'); });

    // ── EDIT — open modal ─────────────────────────────────────────────────
    $('#tbody').on('click', '.editDataBtn', function(e) {
        e.preventDefault();
        var b        = $(this);
        var nm       = b.data('name');
        var unit     = b.data('unit') || '—';
        var code     = b.data('code') || '—';
        var supplier = b.data('supplier') || '';
        var sellIsBr = parseInt(b.data('sell-is-branch')) === 1;
        var bpId     = b.data('base-product-id') || '';
        var bpSell   = b.data('bp-sell') || '';
        var bpCost   = b.data('bp-cost') || '';

        window._bpSellStored     = bpSell;
        window._branchSellStored = sellIsBr ? (b.data('sell') || '') : '';

        $('#editId').val(b.data('id'));
        $('#editRow').val(b.data('row'));
        $('#editBaseProductId').val(bpId);
        $('#bpEditId').val(bpId);
        $('#editModalName').text(nm);
        $('#edit-ro-name').val(nm);
        $('#edit-ro-unit').val(unit);
        $('#edit-ro-code').val(code);

        $('#editStockQty').val(b.data('stock'));
        $('#editReorderPoint').val(b.data('reorder'));
        $('#editReorderQty').val(b.data('reorder-qty'));
        $('#editMaxStock').val(b.data('max'));
        $('#editBarcode').val(b.data('barcode'));
        $('#editBatch').val(b.data('batch'));
        $('#editExpiry').val(b.data('expiry'));
        $('#editTrackStock').prop('checked', parseInt(b.data('track'))  === 1);
        $('#editAllowNeg').prop('checked',   parseInt(b.data('neg'))    === 1);
        $('#editIsActive').prop('checked',   parseInt(b.data('active')) === 1);

        var bpFmt = bpSell
            ? 'MWK ' + parseFloat(bpSell).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})
            : '—';
        document.getElementById('pscBaseVal').textContent = bpFmt;

        if (sellIsBr) {
            setPriceSource('branch');
            $('#editSellPrice').val(b.data('sell'));
            $('#editCostPrice').val(b.data('cost'));
        } else {
            setPriceSource('base');
            $('#editSellPrice').val('');
            $('#editCostPrice').val('');
        }

        $('#bpEditName').val(nm);
        $('#bpEditUnit').val(unit !== '—' ? unit : '');
        $('#bpEditCode').val(code !== '—' ? code : '');
        $('#bpEditSupplier').val(supplier);
        $('#bpEditSellPrice').val(bpSell);
        $('#bpEditCostPrice').val(bpCost);

        $('#tab-branch-lnk').tab('show');
        $('#submitBaseProductBtn').hide();
        $('#submitEditBtn').show();
        $('#editDataModal').modal('show');
    });

    $('#cancelEditBtn').on('click', function(e) {
        e.preventDefault();
        $('#editDataForm')[0].reset();
        $('#editDataModal').modal('hide');
    });

    // ── SUBMIT — Branch product update ────────────────────────────────────
    $('#submitEditBtn').on('click', function(e) {
        e.preventDefault();
        var useBranch = (window._currentPriceSource === 'branch');
        var sell = useBranch ? $('#editSellPrice').val() : null;
        var cost = useBranch ? $('#editCostPrice').val() : null;

        if (useBranch && (!sell || parseFloat(sell) < 0)) {
            toastr.warning('Selling price is required when using branch price.', 'Required');
            $('#editSellPrice').focus();
            return;
        }

        var self = $(this); self.prop('disabled', true);
        var row  = $('#editRow').val();

        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type: 'POST',
            url:  '{{ route("retail.operations.branchproducts.update") }}',
            timeout: 60000,
            data: {
                id: $('#editId').val(), selling_price: sell, cost_price: cost,
                stock_quantity: $('#editStockQty').val(),
                reorder_point: $('#editReorderPoint').val(),
                reorder_quantity: $('#editReorderQty').val(),
                max_stock: $('#editMaxStock').val(),
                primary_barcode: $('#editBarcode').val(),
                batch_number: $('#editBatch').val(),
                expiry_date: $('#editExpiry').val(),
                track_stock: $('#editTrackStock').prop('checked') ? 1 : 0,
                allow_negative_stock: $('#editAllowNeg').prop('checked') ? 1 : 0,
                is_active: $('#editIsActive').prop('checked') ? 1 : 0,
                _token: '{{ csrf_token() }}'
            },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    table.row('#' + row).remove();
                    table.row.add($(buildRow(data.product))).draw(false);
                    updateSelectedCount();
                    $('#editDataModal').modal('hide');
                } else if (data.status === 422) {
                    toastr.error(data.error || 'Validation failed.', 'Error');
                } else {
                    toastr.info('Unspecified error.', 'Error');
                }
            },
            error: handleAjaxError
        });
    });

    // ── SUBMIT — Base product update ──────────────────────────────────────
    $('#submitBaseProductBtn').on('click', function(e) {
        e.preventDefault();
        var name = $('#bpEditName').val().trim();
        if (!name) { toastr.warning('Product name is required.', 'Required'); $('#bpEditName').focus(); return; }
        var sell = $('#bpEditSellPrice').val();
        if (!sell || parseFloat(sell) < 0) { toastr.warning('Selling price is required.', 'Required'); $('#bpEditSellPrice').focus(); return; }

        var self = $(this); self.prop('disabled', true);
        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type: 'POST',
            url:  '{{ route("retail.operations.baseproducts.update") }}',
            timeout: 60000,
            data: {
                id: $('#bpEditId').val(), name: name,
                unit: $('#bpEditUnit').val(), code: $('#bpEditCode').val(),
                supplier: $('#bpEditSupplier').val(),
                selling_price: sell, cost_price: $('#bpEditCostPrice').val(),
                branch_product_id: $('#editId').val(),
                _token: '{{ csrf_token() }}'
            },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success || 'Base product updated.', 'Success');
                    $('#edit-ro-name').val(name);
                    $('#edit-ro-unit').val($('#bpEditUnit').val());
                    $('#edit-ro-code').val($('#bpEditCode').val());
                    $('#editModalName').text(name);
                    window._bpSellStored = sell;
                    document.getElementById('pscBaseVal').textContent = 'MWK ' + parseFloat(sell).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
                    if (data.product) {
                        var row = $('#editRow').val();
                        if (table.row('#' + row).length) {
                            table.row('#' + row).remove();
                            table.row.add($(buildRow(data.product))).draw(false);
                        }
                    }
                    allBaseProducts = [];
                    loadBaseProducts();
                    $('#tab-branch-lnk').tab('show');
                } else if (data.status === 422) {
                    toastr.error(data.error || 'Validation failed.', 'Error');
                } else {
                    toastr.info('Unspecified error.', 'Error');
                }
            },
            error: handleAjaxError
        });
    });

    // ════════════════════════════════════════════════════════════════════════
    //  SINGLE-ROW DELETE
    // ════════════════════════════════════════════════════════════════════════
    $('#tbody').on('click', '.deleteDataBtn', function(e) {
        e.preventDefault();
        $('#deleteLabel').text($(this).data('label'));
        $('#deleteRow').val($(this).data('row'));
        $('#deleteId').val($(this).data('id'));
        $('#deleteModal').modal('show');
    });

    $('#keepBtn').on('click', function(e) {
        e.preventDefault();
        $('#deleteModal').modal('hide');
    });

    $('#submitDeleteBtn').on('click', function(e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        var row  = $('#deleteRow').val();
        var id   = $('#deleteId').val();
        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type: 'POST',
            url:  '{{ route("retail.operations.branchproducts.delete") }}',
            timeout: 60000,
            data: { id: id, _token: '{{ csrf_token() }}' },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    table.row('#' + row).remove().draw(false);
                    updateSelectedCount();
                    $('#deleteModal').modal('hide');
                } else {
                    toastr.error(data.error || 'Failed.', 'Error');
                }
            },
            error: handleAjaxError
        });
    });

    // ════════════════════════════════════════════════════════════════════════
    //  ROW SELECTION  →  Bulk Actions header button
    // ════════════════════════════════════════════════════════════════════════
    $('#selectAll').on('click', function() { $('.selectRow').prop('checked', this.checked); updateSelectedCount(); });
    $('#tbody').on('click', '.selectRow', function() { updateSelectedCount(); });

    function getSelectedIds()  { var ids  = []; $('.selectRow:checked').each(function() { ids.push($(this).val()); }); return ids; }
    function getSelectedRows() { var rows = []; $('.selectRow:checked').each(function() { rows.push($(this).data('row-id')); }); return rows; }

    $('#bulkActionsHeaderBtn').on('click', function() {
        if (!$(this).hasClass('enabled')) return;
        var count = $('.selectRow:checked').length;
        $('#bulkActionsModalCountText').text('— ' + count + ' selected');
        $('#bulkActionsModal').modal('show');
    });

    // ── Option: Use Base Prices → confirmation modal ───────────────────────
    $('#boUseBasePrices').on('click', function() {
        var ids = getSelectedIds();
        if (!ids.length) { toastr.warning('No products selected.', 'Warning'); return; }
        $('#bulkActionsModal').modal('hide');
        $('#confirmUseBaseCount').text(ids.length);
        setTimeout(function() { $('#confirmUseBaseModal').modal('show'); }, 250);
    });

    $('#confirmUseBaseSubmitBtn').on('click', function() {
        var ids = getSelectedIds();
        if (!ids.length) { $('#confirmUseBaseModal').modal('hide'); return; }

        var self = $(this); self.prop('disabled', true);
        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type: 'POST',
            url:  '{{ route("retail.operations.branchproducts.bulk.usebaseprices") }}',
            timeout: 120000,
            data: { ids: ids, _token: '{{ csrf_token() }}' },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    $.each(data.products, function(i, p) {
                        table.row('#' + p.row).remove();
                        table.row.add($(buildRow(p)));
                    });
                    table.draw(false);
                    updateSelectedCount();
                    $('#confirmUseBaseModal').modal('hide');
                } else {
                    toastr.error(data.error || 'Failed.', 'Error');
                }
            },
            error: handleAjaxError
        });
    });

// ── Option: Set Branch Prices → table modal ─────────────────────────────
 $('#boSetBranchPrices').on('click', function() {
    var ids = getSelectedIds();
    if (!ids.length) { toastr.warning('No products selected.', 'Warning'); return; }
    $('#bulkActionsModal').modal('hide');

    var html = '';
    $('.selectRow:checked').each(function() {
        var cb              = $(this);
        var id              = cb.val();
        var name            = cb.data('name');
        var unit            = cb.data('unit');
        var bpSell          = cb.data('bp-sell');
        var sellNow         = cb.data('sell');
        var sellIsBranch    = cb.data('sell-is-branch');

        var bpFmt = (bpSell !== '' && bpSell !== null && bpSell !== undefined)
            ? parseFloat(bpSell).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})
            : null;

        var metaLine = unit || '';
        if (bpFmt) {
            metaLine += (metaLine ? ' &middot; ' : '') + 'Base: <span class="sbp-base-val">' + bpFmt + '</span>';
        }

        // Only pre-fill if this product has a REAL branch price override
        var hasBranchPrice = (sellIsBranch == 1 && sellNow !== '' && sellNow !== null && sellNow !== undefined);
        var prefillVal     = hasBranchPrice ? parseFloat(sellNow).toFixed(2) : '';

        html += `
        <tr data-id="${id}" data-bp-sell="${bpSell || ''}" data-has-branch-price="${hasBranchPrice ? '1' : '0'}">
          <td class="sbp-col-name">
            <div class="sbp-prod-name">${name}</div>
            <div class="sbp-prod-meta">${metaLine}</div>
          </td>
          <td class="sbp-col-input">
            <input type="number" class="sbp-input" id="sbp_price_${id}" placeholder="0.00" min="0"
                   value="${prefillVal}" data-autofilled="0">
          </td>
        </tr>`;
    });

    $('#sbpProductList').html(html);
    $('#sbpCount').text(ids.length);

    setTimeout(function() {
        $('#setBranchPricesModal').modal('show');
        setTimeout(function() { $('#sbpProductList .sbp-input').first().focus(); }, 350);
    }, 250);
});



    // Fill every input with that row's base price. Marking data-autofilled="1"
    // guarantees these values are picked up by the submit handler below exactly
    // the same as manually typed ones — this is what makes "Use Base Prices"
    // actually persist to the branch product instead of only changing the
    // visible input without saving.
    $('#sbpFillBaseBtn').on('click', function() {
        var filledCount = 0;
        $('#sbpProductList tr').each(function() {
            var bpSell = $(this).data('bp-sell');
            var id     = $(this).data('id');
            if (bpSell !== '' && bpSell !== null && bpSell !== undefined) {
                $('#sbp_price_' + id)
                    .val(parseFloat(bpSell).toFixed(2))
                    .attr('data-autofilled', '1');
                filledCount++;
            }
        });
        if (filledCount > 0) {
            toastr.info(filledCount + ' input(s) filled with base prices — click "Save Branch Prices" to apply.', 'Filled');
        } else {
            toastr.warning('None of the selected products have a base price to fill from.', 'Nothing to fill');
        }
    });

    // Clear every input
    $('#sbpClearAllBtn').on('click', function() {
        $('#sbpProductList .sbp-input').val('').attr('data-autofilled', '0');
    });

$('#sbpSubmitBtn').on('click', function() {
    var items = [];

    $('#sbpProductList tr').each(function() {
        var id         = $(this).data('id');
        var input      = $('#sbp_price_' + id);
        var val        = input.val();
        var autofilled = input.attr('data-autofilled') === '1';
        var parsed     = parseFloat(val);
        var hasValue   = (val !== '' && !isNaN(parsed));

        if (!hasValue && !autofilled) return;

        var price;
        if (hasValue) {
            price = parsed;
        } else if (autofilled) {
            var bpSell = $(this).data('bp-sell');
            price = (bpSell !== '' && bpSell !== null && bpSell !== undefined)
                ? parseFloat(bpSell)
                : null;
        }

        if (price === null || isNaN(price)) return;

        items.push({ id: parseInt(id), price: price });
    });

    if (!items.length) {
        toastr.warning('No prices to save — fill at least one input.', 'Nothing to save');
        return;
    }

    var self = $(this); self.prop('disabled', true);
    $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
    $.ajax({
        type: 'POST',
        url:  '{{ route("retail.operations.branchproducts.bulk.setbranchprices") }}',
        timeout: 120000,
        contentType: 'application/json',
        data: JSON.stringify({ items: items, _token: '{{ csrf_token() }}' }),
        beforeSend: function() { $('#progressBar').show(); },
        complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
        success: function(data) {
            if (data.status === 201) {
                toastr.success(data.success, 'Success');
                $.each(data.products, function(i, p) {
                    table.row('#' + p.row).remove();
                    table.row.add($(buildRow(p)));
                });
                table.draw(false);
                updateSelectedCount();
                $('#setBranchPricesModal').modal('hide');
            } else {
                toastr.error(data.error || 'Failed.', 'Error');
            }
        },
        error: handleAjaxError
    });
});

    

    // ── Option: Bulk delete → confirmation modal ────────────────────────────
    $('#boBulkDelete').on('click', function() {
        var ids = getSelectedIds();
        if (!ids.length) { toastr.warning('No products selected.', 'Warning'); return; }
        $('#bulkActionsModal').modal('hide');
        $('#confirmBulkDeleteCount').text(ids.length);
        setTimeout(function() { $('#confirmBulkDeleteModal').modal('show'); }, 250);
    });

    $('#confirmBulkDeleteSubmitBtn').on('click', function() {
        var ids  = getSelectedIds();
        var rows = getSelectedRows();
        if (!ids.length) { $('#confirmBulkDeleteModal').modal('hide'); return; }

        var self = $(this); self.prop('disabled', true);
        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type: 'POST',
            url:  '{{ route("retail.operations.branchproducts.bulkdelete") }}',
            timeout: 120000,
            data: { ids: ids, _token: '{{ csrf_token() }}' },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    rows.forEach(function(r) { table.row('#' + r).remove(); });
                    table.draw(false);
                    updateSelectedCount();
                    $('#confirmBulkDeleteModal').modal('hide');
                } else {
                    toastr.error(data.error || 'Failed.', 'Error');
                }
            },
            error: handleAjaxError
        });
    });

    @endif

    $('#infoBtn').on('click',         function(e) { e.preventDefault(); $('#infoModal').modal('show'); });
    $('#tableButtonsBtn').on('click', function(e) { e.preventDefault(); $('#buttonsModal').modal('show'); });

});

// ── Overview tab switcher (outside doc.ready since called from onclick) ──
function switchOverviewTab(tab) {
    if (tab === 'shop') {
        document.getElementById('ovTabShop').style.display  = '';
        document.getElementById('ovTabPrice').style.display = 'none';
        document.getElementById('ovTabShopBtn').className   = 'overview-tab-btn active';
        document.getElementById('ovTabPriceBtn').className  = 'overview-tab-btn';
    } else {
        document.getElementById('ovTabShop').style.display  = 'none';
        document.getElementById('ovTabPrice').style.display = '';
        document.getElementById('ovTabShopBtn').className   = 'overview-tab-btn';
        document.getElementById('ovTabPriceBtn').className  = 'overview-tab-btn active';
    }
}
</script>
@endsection