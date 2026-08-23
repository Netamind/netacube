@extends('operations.wholesale.dashboard')
@section('content')
@php
    // Controller already supplies: $branches (sector=Wholesale), $selectedBranch (int|null),
    // $branchProducts (collection), $shopValue. We derive display-only bits here.
    $branchObj = $selectedBranch ? $branches->firstWhere('id', $selectedBranch) : null;

    $maintableTitle = 'Wholesale Branch Products — ' . ($branchObj->name ?? 'All');
    $activeCount    = $branchProducts->where('is_active', 1)->count();
    $lowStockCount  = $branchProducts->filter(fn($p) => (float)$p->stock_quantity <= (float)$p->reorder_point && (float)$p->stock_quantity > 0)->count();
    $zeroCount      = $branchProducts->filter(fn($p) => (float)$p->stock_quantity <= 0)->count();
@endphp

<style>
/* ── Reset & Base ─────────────────────────────────────────────────── */
.dt-buttons .btn { background:transparent !important; background-image:none !important; box-shadow:none !important; border-color:#5bc0de; color:#5bc0de; }
.dt-buttons .btn:hover { background:#5bc0de !important; color:#fff; }

/* ── Card chrome ─────────────────────────────────────────────────── */
.card-header { padding:0.5rem 1.5rem !important; background:linear-gradient(to right,#4B5EBD,#576CC0); color:#fff; border-radius:10px 10px 0 0 !important; flex-wrap:wrap; gap:8px; }
.card-body   { padding:0 1.5rem 1.5rem 1.5rem !important; }
.card        { border:none; box-shadow:0 4px 8px rgba(0,0,0,0.1); border-radius:10px; }
.card-header h4 { color:#fff; font-weight:600; margin-bottom:0; display:flex; align-items:center; }
.card-header .btn-light { height:28px; padding:0 10px; display:flex; align-items:center; justify-content:center; line-height:1; }
.card-header .btn-light:hover { background-color:#f8f9fa; transition:background-color 0.2s; }

.header-select-all { width:16px; height:16px; cursor:pointer; accent-color:#4B5EBD; background:#d1d5db; border-radius:3px; margin-right:10px; flex-shrink:0; vertical-align:middle; }
.header-title-block { display:flex; flex-direction:column; line-height:1.25; min-width:0; }
.card-header-actions { display:flex; align-items:center; gap:4px; flex-wrap:wrap; justify-content:flex-end; }

@media (max-width:576px) {
  .card-header { padding:10px 14px !important; }
  .header-title-block { max-width:55vw; }
  #branchSelectHeader { font-size:15px; max-width:100%; }
  .card-header-actions { width:100%; justify-content:flex-start; }
  .card-header .btn-light { height:32px; width:32px; padding:0; font-size:15px; }
}

input[type=number]::-webkit-outer-spin-button, input[type=number]::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
input[type=number] { -moz-appearance:textfield; appearance:textfield; }

#bulkActionsHeaderBtn { position:relative; opacity:.5; pointer-events:none; cursor:not-allowed; transition:opacity .15s; }
#bulkActionsHeaderBtn.enabled { opacity:1; pointer-events:auto; cursor:pointer; }
#bulkActionsHeaderBtn .bah-count { position:absolute; top:-5px; right:-5px; background:#dc2626; color:#fff; border-radius:50%; font-size:10px; font-weight:700; min-width:16px; height:16px; line-height:16px; text-align:center; padding:0 3px; display:none; box-shadow:0 0 0 1.5px #fff; }
#bulkActionsHeaderBtn .bah-count.show { display:block; }

#maintable thead th, table.dataTable thead th { text-align:center !important; vertical-align:middle !important; }
#maintable thead th:first-child, table.dataTable thead th:first-child { text-align:left !important; }
#maintable tbody td, table.dataTable tbody td { text-align:center !important; vertical-align:middle !important; }
#maintable tbody td:first-child, table.dataTable tbody td:first-child { text-align:left !important; }

.stock-ok   { color:#16a34a; font-weight:700; }
.stock-low  { color:#d97706; font-weight:700; }
.stock-zero { color:#dc2626; font-weight:700; }
.price-branch { color:#1d4ed8; font-weight:700; font-size:12px; }
.price-base   { color:#059669; font-weight:600; font-size:12px; }

.no-branch-wrap { padding:48px 20px; text-align:center; color:#94a3b8; }
.no-branch-wrap i { font-size:52px; display:block; margin-bottom:12px; color:#c8d0ed; }
.no-branch-wrap h5 { color:#64748b; font-weight:600; }

.mh-blue   { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-danger { background:linear-gradient(135deg,#c0392b,#e74c3c); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-title  { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close  { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }

#branchSelectHeader { border:none; background:transparent; color:#fff; font-size:18px; font-weight:600; cursor:pointer; padding:0; outline:none; max-width:300px; }
#branchSelectHeader option { color:#1e293b; background:#fff; font-size:14px; }

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

.confirm-icon-wrap { width:56px; height:56px; border-radius:50%; background:#fffbeb; display:flex; align-items:center; justify-content:center; margin:0 auto 14px; }
.confirm-icon-wrap i { font-size:28px; color:#d97706; }

.search-result-list { max-height:420px; overflow-y:auto; border:1px solid #e2e6f0; border-radius:10px; background:#fff; display:none; box-shadow:0 4px 18px rgba(0,0,0,0.10); margin-top:6px; }
.search-result-list::-webkit-scrollbar { width:6px; }
.search-result-list::-webkit-scrollbar-thumb { background:#c8d0ed; border-radius:6px; }
.sri-item { border-bottom:1px solid #f1f5f9; transition:background .1s; background:#fff; }
.sri-item:nth-child(even) { background:#fafafa; }
.sri-item:last-child { border-bottom:none; }
.sri-main { display:flex; align-items:center; gap:10px; padding:10px 14px 6px; }
.sri-name-wrap { flex:1; min-width:0; }
.sri-name  { font-size:13px; font-weight:700; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.sri-code  { font-size:11px; color:#94a3b8; font-weight:400; }
.sri-meta-row { display:flex; align-items:center; gap:10px; padding:0 14px 10px; flex-wrap:wrap; }
.sri-price-tag { font-size:12px; font-weight:600; padding:3px 9px; border-radius:10px; background:#ecfdf5; color:#059669; white-space:nowrap; }
.sri-controls { display:flex; align-items:center; gap:8px; padding:0 14px 12px; flex-wrap:wrap; }
.sri-qty-wrap { display:flex; align-items:center; gap:6px; }
.sri-qty-label { font-size:11px; color:#94a3b8; font-weight:600; white-space:nowrap; }
.sri-qty-input, .sri-price-override-input {
    width:110px; border:1px solid #e2e6f0; border-radius:6px; height:30px;
    font-size:12px; font-weight:600; padding:0 8px; color:#475569;
    background:#fff; outline:none; text-align:center;
}
.sri-add-btn {
    height:30px; padding:0 16px; font-size:12px; font-weight:700;
    border:none; border-radius:6px; cursor:pointer;
    background:#e9ecef; color:#4B5EBD;
    display:flex; align-items:center; gap:4px;
    box-shadow:none; transition:background .15s; white-space:nowrap; flex-shrink:0;
}
.sri-add-btn:hover:not(:disabled) { background:#dee2e6; }
.sri-add-btn:disabled { background:#eef0f2 !important; color:#b0b7c3 !important; cursor:default; }
.sri-already-badge { font-size:10px; background:#f1f3f9; color:#94a3b8; padding:2px 8px; border-radius:10px; font-weight:600; flex-shrink:0; }

.edit-modal-tabs { display:flex; background:#f1f3f9; border-bottom:2px solid #dde1f0; margin:0; list-style:none; padding:0 18px; gap:2px; }
.edit-modal-tab-btn {
    position:relative; display:flex; align-items:center; gap:6px;
    font-size:12px; font-weight:500; color:#94a3b8;
    padding:10px 16px; border:none; background:#f1f3f9; cursor:pointer;
    border-bottom:3px solid transparent; margin-bottom:-2px;
    transition:color .15s; text-decoration:none; white-space:nowrap;
}
.edit-modal-tab-btn:hover:not(.em-active) { color:#4B5EBD; }
.edit-modal-tab-btn.em-active { color:#4B5EBD; font-weight:700; background:#f1f3f9; border-bottom:3px solid #4B5EBD; }
.edit-tab-pane { display:none; }
.edit-tab-pane.em-show { display:block; }

#addProductModal .nav-link { font-size:12px; color:#94a3b8; background:transparent !important; border:none !important; border-bottom:3px solid transparent !important; padding:9px 14px; font-weight:500; transition:color .15s; }
#addProductModal .nav-link.active { color:#4B5EBD !important; font-weight:700; border-bottom-color:#4B5EBD !important; background:transparent !important; }
#addProductModal .nav-link:hover:not(.active) { color:#4B5EBD; }
#addProductModal .nav-tabs { background:#f1f3f9; border-bottom:2px solid #dde1f0 !important; padding:0 4px; gap:2px; }

.edit-ro { background:#f1f3f9 !important; color:#64748b !important; border-color:#e2e6f0 !important; cursor:default !important; font-weight:600; }

.price-source-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.psc { border:1.5px solid #e2e6f0; border-radius:8px; padding:9px 12px; cursor:pointer; transition:border-color .15s, background .15s; user-select:none; background:#f4f5f7; }
.psc:hover:not(.psc-active-base):not(.psc-active-branch) { border-color:#c8d0ed; }
.psc-active-base   { border-color:#059669 !important; background:#f4f5f7; }
.psc-active-branch { border-color:#1d4ed8 !important; background:#f4f5f7; }
.psc-dot { width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:5px; flex-shrink:0; }
.psc-dot-base   { background:#059669; }
.psc-dot-branch { background:#1d4ed8; opacity:.35; }
.psc-active-branch .psc-dot-branch { opacity:1; }
.psc-label { font-size:11px; font-weight:700; display:flex; align-items:center; margin-bottom:3px; }
.psc-label-base   { color:#059669; }
.psc-label-branch { color:#9ca3af; }
.psc-active-branch .psc-label-branch { color:#1d4ed8; }
.psc-val { font-size:14px; font-weight:700; color:#9ca3af; }
.psc-active-base   .psc-val-base   { color:#059669; }
.psc-active-branch .psc-val-branch { color:#1d4ed8; }
.psc-sub { font-size:10px; color:#9ca3af; margin-top:1px; }

.edit-section { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.8px; color:#94a3b8; margin:16px 0 8px; display:flex; align-items:center; gap:6px; }
.edit-section::after { content:''; flex:1; height:1px; background:#e9ecef; }
.edit-reason-label { font-size:11px; color:#6c757d; font-weight:600; margin-bottom:4px; display:flex; align-items:center; gap:4px; }
.edit-reason-opt { font-size:10px; color:#94a3b8; font-weight:400; }

.np-price-source-row { display:flex; align-items:center; gap:8px; background:#f4f5f7; border-radius:8px; padding:8px 12px; margin-bottom:10px; }
.np-ps-btn { flex:1; padding:6px 0; border-radius:6px; border:1.5px solid #e2e6f0; background:#f4f5f7; font-size:12px; font-weight:600; color:#6c757d; cursor:pointer; transition:all .15s; text-align:center; }
.np-ps-btn.np-ps-active-base   { border-color:#059669; color:#059669; background:#f4f5f7; }
.np-ps-btn.np-ps-active-branch { border-color:#1d4ed8; color:#1d4ed8; background:#f4f5f7; }

.bulk-option-card { display:flex; align-items:center; gap:12px; padding:14px 16px; border-radius:10px; border:1.5px solid #e9ecef; cursor:pointer; transition:border-color .15s,background .15s; margin-bottom:10px; }
.bulk-option-card:last-child { margin-bottom:0; }
.bulk-option-card:hover { border-color:#c8d0ed; background:#f8f9ff; }
.bulk-option-card .boc-icon { width:40px; height:40px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:19px; flex-shrink:0; }
.boc-title { font-size:14px; font-weight:700; color:#1e293b; }
.boc-desc  { font-size:12px; color:#6c757d; margin-top:1px; }
.boc-icon-base   { background:#ecfdf5; color:#059669; }
.boc-icon-branch { background:#eff6ff; color:#1d4ed8; }
.boc-icon-delete { background:#fef2f2; color:#dc2626; }
.boc-icon-toggle { background:#f5f3ff; color:#7c3aed; }

.bp-edit-warning { background:#fffbeb; border-left:2px solid #f59e0b; border-radius:0 5px 5px 0; padding:8px 12px; font-size:11px; color:#92400e; margin-bottom:14px; display:flex; align-items:flex-start; gap:6px; }

#addProductModal .form-label, #addProductModal .form-text, #addProductModal label, #addProductModal .edit-section { color:#94a3b8; }
#addProductModal .btn-secondary, #addProductModal .np-ps-btn:not(.np-ps-active-base):not(.np-ps-active-branch) { background:#e9ecef; color:#94a3b8; border-color:#e9ecef; }
</style>

<div class="progress" id="progressBar" role="progressbar" style="height:8px;transform:rotate(180deg);display:none;border-radius:0">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%;border-radius:0"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
      @if($branchObj)
        <input type="checkbox" id="selectAll" class="header-select-all">
      @endif
      <form method="POST" action="{{ route('wholesale.operations.update.filters') }}" id="headerBranchForm" style="margin:0;display:inline;">
        @csrf
        <div class="header-title-block">
          <select name="branch_id" id="branchSelectHeader" onchange="document.getElementById('headerBranchForm').submit()">
            <option value="" hidden>{{ $branchObj ? $branchObj->name : '— Select Warehouse —' }}</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ $selectedBranch == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
          </select>
        </div>
      </form>
    </h4>
    <div class="card-header-actions">
      @if($branchObj)
      <button type="button" class="btn btn-light text-primary fs-16 mx-1" id="bulkActionsHeaderBtn" disabled title="Select rows to enable bulk actions">
        <i class="ri-stack-line"></i>
        <span class="bah-count" id="bulkActionsHeaderCount"></span>
      </button>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="overviewBtn" title="Overview"><i class="ri-dashboard-line"></i></a>
      @endif
      <a href="#" class="btn btn-light text-success fs-16 mx-1" id="addProductBtn" title="Add product" @if(!$branchObj) style="pointer-events:none;opacity:.5" @endif><i class="ri-add-circle-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="About Branch Products"><i class="ri-information-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download"><i class="ri-download-line"></i></a>
    </div>
  </div>

  <div class="card-body">
    @if(!$branchObj)
      <div class="no-branch-wrap">
        <i class="ri-store-line"></i>
        <h5>No Warehouse Selected</h5>
        <p style="font-size:13px;">Select a warehouse from the header above.</p>
      </div>
    @else
    <table id="maintable" class="table table-sm table-striped row-border order-column w-100 mt-3">
      <thead style="background-color:#e2e2e9">
        <tr>
          <th>Product Name</th>
          <th>Code</th>
          <th>Unit</th>
          <th>Supplier</th>
          <th>Stock</th>
          <th>Sell Price</th>
          <th>Batch</th>
          <th>Expiry</th>
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
            $displayPrice = $sellIsBranch ? $bp->selling_price : $bp->base_selling_price;
            $costIsBranch = ($bp->cost_price !== null);
          @endphp
          <tr id="{{ $row }}">
            <td>
              <input type="checkbox" class="selectRow" value="{{ $bp->id }}" data-row-id="{{ $row }}" data-name="{{ $bp->name }}">
              &nbsp;{{ $bp->name }}
            </td>
            <td>{{ $bp->code ?? '—' }}</td>
            <td>{{ $bp->unit }}</td>
            <td>{{ $bp->supplier_name ?? '—' }}</td>
            <td><span class="{{ $stockClass }}">{{ number_format($sq, 2) }}</span></td>
            <td><span class="{{ $sellIsBranch ? 'price-branch' : 'price-base' }}">{{ number_format($displayPrice, 2) }}</span></td>
            <td>{{ $bp->batch_number ?? '—' }}</td>
            <td>{{ $bp->expiry_date  ?? '—' }}</td>
            <td>
              <a href="#" class="viewDataBtn"
                 data-id="{{ $bp->id }}" data-name="{{ $bp->name }}" data-code="{{ $bp->code }}"
                 data-unit="{{ $bp->unit }}" data-pack-unit="{{ $bp->pack_unit }}" data-units-per-pack="{{ $bp->units_per_pack }}"
                 data-supplier="{{ $bp->supplier_name }}" data-barcode="{{ $bp->primary_barcode }}"
                 data-batch="{{ $bp->batch_number }}" data-expiry="{{ $bp->expiry_date }}"
                 data-cost="{{ $bp->cost_price }}" data-sell="{{ $bp->selling_price }}"
                 data-base-cost="{{ $bp->base_cost_price }}" data-base-sell="{{ $bp->base_selling_price }}"
                 data-stock="{{ $bp->stock_quantity }}" data-reorder="{{ $bp->reorder_point }}"
                 data-reorder-qty="{{ $bp->reorder_quantity }}" data-max="{{ $bp->max_stock }}"
                 data-active="{{ $bp->is_active }}" data-track="{{ $bp->track_stock }}" data-neg="{{ $bp->allow_negative_stock }}"
                 data-sell-is-branch="{{ $sellIsBranch ? 1 : 0 }}" data-cost-is-branch="{{ $costIsBranch ? 1 : 0 }}">
                <i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px"></i>
              </a>
              <a href="#" class="editDataBtn"
                 data-id="{{ $bp->id }}" data-row="{{ $row }}" data-name="{{ $bp->name }}" data-code="{{ $bp->code }}"
                 data-unit="{{ $bp->unit }}" data-supplier-id="{{ $bp->supplier_id }}"
                 data-barcode="{{ $bp->primary_barcode }}" data-batch="{{ $bp->batch_number }}" data-expiry="{{ $bp->expiry_date }}"
                 data-cost="{{ $bp->cost_price }}" data-sell="{{ $bp->selling_price }}"
                 data-base-cost="{{ $bp->base_cost_price }}" data-base-sell="{{ $bp->base_selling_price }}"
                 data-stock="{{ $bp->stock_quantity }}" data-reorder="{{ $bp->reorder_point }}"
                 data-reorder-qty="{{ $bp->reorder_quantity }}" data-max="{{ $bp->max_stock }}"
                 data-active="{{ $bp->is_active }}" data-track="{{ $bp->track_stock }}" data-neg="{{ $bp->allow_negative_stock }}"
                 data-sell-is-branch="{{ $sellIsBranch ? 1 : 0 }}"
                 data-base-product-id="{{ $bp->base_product_id }}">
                <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px"></i>
              </a>
              <a href="#" class="deleteDataBtn" data-label="{{ $bp->name }}" data-id="{{ $bp->id }}" data-row="{{ $row }}" data-stock="{{ $bp->stock_quantity }}">
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

{{-- ══ ADD PRODUCT MODAL ══ --}}
<div class="modal fade" id="addProductModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title">
          <i class="ri-add-circle-line"></i> Add Product
          @if($branchObj)<span style="font-size:12px;font-weight:400;opacity:.85">— {{ $branchObj->name }}</span>@endif
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>

      <ul class="nav nav-tabs border-bottom px-2 pt-2" role="tablist" style="flex-wrap:nowrap;">
        <li class="nav-item"><button class="nav-link active px-3 py-1" data-bs-toggle="tab" data-bs-target="#at1" type="button"><i class="ri-search-line me-1"></i>Search</button></li>
        <li class="nav-item"><button class="nav-link px-3 py-1" data-bs-toggle="tab" data-bs-target="#at2" type="button"><i class="ri-add-line me-1"></i>New Product</button></li>
        <li class="nav-item"><button class="nav-link px-3 py-1" data-bs-toggle="tab" data-bs-target="#at3" type="button"><i class="ri-upload-2-line me-1"></i>Import CSV</button></li>
      </ul>

      <div class="modal-body" style="padding:14px 18px 10px !important;">
        <div class="tab-content">

          {{-- ── Tab 1: Search existing catalogue products ── --}}
          <div class="tab-pane fade show active" id="at1" role="tabpanel">
            <div class="mb-1">
              <input type="text" class="form-control" id="baseProductSearch" placeholder="Type product name or code…" autocomplete="off" />
            </div>
            <div id="searchResultList" class="search-result-list"></div>
          </div>

          {{-- ── Tab 2: New Product (base catalogue, then auto-assigned to this branch) ── --}}
          <div class="tab-pane fade" id="at2" role="tabpanel">
            <div class="alert" style="background:#eff6ff;border-left:3px solid #4B5EBD;border-radius:0 5px 5px 0;padding:8px 12px;font-size:11px;color:#1e40af;margin-bottom:12px;">
              <i class="ri-information-line me-1"></i>
              Products added here go into the wholesale base catalogue first, then automatically assigned to this warehouse.
            </div>
            <div class="row g-2 mb-2">
              <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Name <span class="text-danger">*</span></label><input class="form-control form-control-sm" type="text" id="new-name" autocomplete="off" /></div>
              <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Code</label><input class="form-control form-control-sm" type="text" id="new-code" autocomplete="off" /></div>
            </div>
            <div class="row g-2 mb-2">
              <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Unit</label><input class="form-control form-control-sm" type="text" id="new-unit" value="Each" autocomplete="off" /></div>
              <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Min Order Qty</label><input class="form-control form-control-sm" type="text" inputmode="decimal" id="new-moq" value="1" autocomplete="off" /></div>
            </div>
            <div class="row g-2 mb-2">
              <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Pack Unit</label><input class="form-control form-control-sm" type="text" id="new-pack-unit" placeholder="Carton, Box…" autocomplete="off" /></div>
              <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Units / Pack</label><input class="form-control form-control-sm" type="text" inputmode="decimal" id="new-units-per-pack" autocomplete="off" /></div>
            </div>
            <div class="row g-2 mb-2">
              <div class="col-12"><label class="form-label fw-semibold" style="font-size:12px">Supplier</label>
                <select class="form-select form-select-sm" id="new-supplier" autocomplete="off">
                  <option value="">Select supplier</option>
                </select>
              </div>
            </div>
            <div class="row g-2 mb-2">
              <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Quantity (opening stock)</label><input class="form-control form-control-sm" type="text" inputmode="decimal" id="new-stock-qty" placeholder="0" autocomplete="off" /></div>
              <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Reorder Point</label><input class="form-control form-control-sm" type="text" inputmode="decimal" id="new-reorder-point" placeholder="0" autocomplete="off" /></div>
            </div>

            <div class="edit-section"><i class="ri-coin-line"></i>Selling Price</div>
            <div class="np-price-source-row" id="npPriceSourceRow">
              <span style="font-size:11px;color:#6c757d;font-weight:600;white-space:nowrap;">Source:</span>
              <button type="button" class="np-ps-btn np-ps-active-base" id="npBtnBase" onclick="setNpPriceSource('base')">
                <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#059669;margin-right:4px;vertical-align:middle;"></span> Base Catalogue
              </button>
              <button type="button" class="np-ps-btn" id="npBtnBranch" onclick="setNpPriceSource('branch')">
                <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#1d4ed8;margin-right:4px;vertical-align:middle;"></span> Warehouse Override
              </button>
            </div>
            <div id="npBasePriceArea" class="row g-2 mb-2">
              <div class="col-12">
                <label class="form-label fw-semibold" style="font-size:12px">Selling Price <span class="text-danger">*</span></label>
                <input class="form-control form-control-sm" type="text" inputmode="decimal" id="new-selling-price" placeholder="0.00" autocomplete="off" />
              </div>
            </div>
            <div id="npBranchPriceArea" style="display:none;" class="row g-2 mb-2">
              <div class="col-12">
                <div style="background:#eff6ff;border-left:3px solid #1d4ed8;border-radius:0 5px 5px 0;padding:7px 12px;font-size:11px;color:#1e40af;margin-bottom:8px;">
                  <i class="ri-information-line me-1"></i>Enter the selling price. It will be stored as the base catalogue price <strong>and</strong> set as a warehouse override for <strong>{{ $branchObj->name ?? 'this warehouse' }}</strong>.
                </div>
                <label class="form-label fw-semibold" style="font-size:12px">Warehouse Selling Price <span class="text-danger">*</span></label>
                <input class="form-control form-control-sm" type="text" inputmode="decimal" id="new-branch-price" placeholder="0.00" autocomplete="off" />
              </div>
            </div>

            <div class="edit-section"><i class="ri-money-dollar-circle-line"></i>Cost Price <span style="font-size:10px;font-weight:400;text-transform:none;letter-spacing:0;color:#b0b7c3;">(base catalogue)</span></div>
            <div class="row g-2 mb-3">
              <div class="col-12"><input class="form-control form-control-sm" type="text" inputmode="decimal" id="new-cost-price" placeholder="0.00" autocomplete="off" /></div>
            </div>

            <div class="d-flex justify-content-end mt-1">
              <a href="#" class="btn btn-success btn-sm" id="submitAddBtn"><i class="ri-check-line"></i> Save to Catalogue &amp; Warehouse</a>
            </div>
            <div id="addSuccessNotice" class="mt-2" style="font-size:12px;color:#198754;display:none;"><i class="ri-check-double-line me-1"></i><span id="addSuccessText"></span></div>
          </div>

          {{-- ── Tab 3: CSV Import (base catalogue only — add to a warehouse afterwards via Search) ── --}}
          <div class="tab-pane fade" id="at3" role="tabpanel">
            <div style="font-size:13px;color:#374151;margin-bottom:12px;">Prepare a CSV file with these columns:</div>
            <div style="background:#f8f9fa;border-radius:8px;padding:12px 14px;margin-bottom:14px;font-family:monospace;font-size:12px;color:#374151;overflow-x:auto;white-space:nowrap;">name, code, unit, pack_unit, units_per_pack, selling_price, cost_price, min_order_quantity</div>
            <div class="alert" style="background:#eff6ff;border-left:3px solid #4B5EBD;border-radius:0 5px 5px 0;padding:8px 12px;font-size:11px;color:#1e40af;margin-bottom:14px;">
              <i class="ri-information-line me-1"></i>This imports into the base catalogue only. Use the <strong>Search</strong> tab afterwards to stock imported products at this warehouse.
            </div>
            <input class="form-control form-control-sm mb-2" type="file" id="csv-file" accept=".csv,.txt" />
            <div class="d-flex justify-content-end"><button type="button" class="btn btn-success btn-sm" id="csvImportBtn"><i class="ri-upload-2-line"></i> Import CSV</button></div>
            <div id="csvImportProgress" style="font-size:12px;color:#475569;margin-top:10px;"></div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>

{{-- ══ VIEW MODAL ══ --}}
<div class="modal fade" id="viewProductModal" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue"><h5 class="modal-title mh-title"><i class="ri-eye-line"></i> Branch Product Details</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" style="padding:16px 20px !important;">
      <div class="mb-3 pb-2 border-bottom d-flex align-items-start justify-content-between">
        <div><div style="font-size:17px;font-weight:700;color:#1e293b" id="vw-name"></div><div style="font-size:12px;color:#6c757d" id="vw-meta-line"></div></div>
        <div id="vw-badges" class="d-flex gap-2 flex-wrap justify-content-end"></div>
      </div>
      <ul class="nav nav-tabs nav-sm mb-3" role="tablist" style="font-size:12px;">
        <li class="nav-item"><button class="nav-link active py-1 px-2" data-bs-toggle="tab" data-bs-target="#vw-t1"><i class="ri-money-dollar-circle-line me-1"></i>Pricing</button></li>
        <li class="nav-item"><button class="nav-link py-1 px-2" data-bs-toggle="tab" data-bs-target="#vw-t2"><i class="ri-stack-line me-1"></i>Stock</button></li>
        <li class="nav-item"><button class="nav-link py-1 px-2" data-bs-toggle="tab" data-bs-target="#vw-t3"><i class="ri-settings-3-line me-1"></i>Settings</button></li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="vw-t1">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px 14px;">
            <div><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Selling Price (MWK)</label><div id="vw-sell" style="font-size:13px;font-weight:600;"></div></div>
            <div><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Cost Price (MWK)</label><div id="vw-cost" style="font-size:13px;"></div></div>
            <div><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Supplier</label><div id="vw-supplier"></div></div>
          </div>
        </div>
        <div class="tab-pane fade" id="vw-t2">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px 14px;">
            <div><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Stock on Hand</label><div id="vw-stock"></div></div>
            <div><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Reorder Point</label><div id="vw-reorder"></div></div>
            <div><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Reorder Qty</label><div id="vw-reorder-qty"></div></div>
            <div><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Max Stock</label><div id="vw-max"></div></div>
            <div><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Barcode</label><div id="vw-barcode"></div></div>
            <div><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Batch</label><div id="vw-batch"></div></div>
            <div style="grid-column:1/-1;"><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Expiry Date</label><div id="vw-expiry"></div></div>
          </div>
        </div>
        <div class="tab-pane fade" id="vw-t3">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px 14px;">
            <div><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Track Stock</label><div id="vw-track"></div></div>
            <div><label style="font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Allow Negative Stock</label><div id="vw-neg"></div></div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer" style="padding:10px 20px 14px;justify-content:space-between;">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal"><i class="ri-close-line me-1"></i> Close</button>
      <a href="#" class="btn btn-primary btn-sm" id="vwEditBtn"><i class="ri-edit-box-line me-1"></i> Edit</a>
    </div>
  </div></div>
</div>

{{-- ══ EDIT MODAL ══ --}}
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-edit-box-line"></i> <span id="editModalName"></span></h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="edit-modal-tabs" id="editTabBar">
        <button type="button" class="edit-modal-tab-btn em-active" id="emTab1Btn" onclick="switchEditTab(1)"><i class="ri-edit-line" style="font-size:13px;"></i> Edit</button>
        <button type="button" class="edit-modal-tab-btn" id="emTab2Btn" onclick="switchEditTab(2)"><i class="ri-settings-3-line" style="font-size:13px;"></i> Settings</button>
        <button type="button" class="edit-modal-tab-btn" id="emTab3Btn" onclick="switchEditTab(3)"><i class="ri-database-2-line" style="font-size:13px;"></i> Base Info</button>
      </div>

      <div class="modal-body" style="padding:16px 18px 10px !important;">
        <input type="hidden" id="editId">
        <input type="hidden" id="editRow">
        <input type="hidden" id="editBaseProductId">

        {{-- ── TAB 1: EDIT ── --}}
        <div class="edit-tab-pane em-show" id="emTab1">
          <div class="mb-2">
            <label class="form-label fw-semibold" style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;">Product</label>
            <input type="text" class="form-control form-control-sm edit-ro" id="edit-ro-name" readonly tabindex="-1" autocomplete="off" />
          </div>
          <div class="row g-2 mb-2">
            <div class="col-6">
              <label class="form-label fw-semibold" style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;">Unit</label>
              <input type="text" class="form-control form-control-sm edit-ro" id="edit-ro-unit" readonly tabindex="-1" autocomplete="off" />
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold" style="font-size:12px">Quantity</label>
              <input class="form-control form-control-sm" type="text" inputmode="decimal" id="editStockQty" autocomplete="off" />
            </div>
          </div>

          <div class="mb-3">
            <div class="edit-reason-label">Reason for quantity change <span class="edit-reason-opt">(optional)</span></div>
            <textarea class="form-control form-control-sm" id="editStockReason" rows="2" placeholder="e.g. Stock count correction, received delivery…" style="resize:vertical;font-size:12px;" autocomplete="off"></textarea>
          </div>

          <div class="edit-section"><i class="ri-coin-line"></i>Selling Price Source</div>
          <div class="price-source-grid mb-2">
            <div class="psc psc-active-base" id="editPscBase" onclick="setEditPriceSource('base')">
              <div class="psc-label psc-label-base"><span class="psc-dot psc-dot-base"></span>Base catalogue</div>
              <div class="psc-val psc-val-base" id="editPscBaseVal">—</div>
              <div class="psc-sub">Inherited · all warehouses</div>
            </div>
            <div class="psc" id="editPscBranch" onclick="setEditPriceSource('branch')">
              <div class="psc-label psc-label-branch"><span class="psc-dot psc-dot-branch"></span>This warehouse only</div>
              <div class="psc-val psc-val-branch" id="editPscBranchVal">—</div>
              <div class="psc-sub">Override for this warehouse</div>
            </div>
          </div>
          <div id="editBranchPriceFields" style="display:none;">
            <div class="row g-2 mb-1">
              <div class="col-12">
                <label class="form-label fw-semibold" style="font-size:12px">Warehouse Selling Price <span class="text-danger">*</span></label>
                <input class="form-control form-control-sm" type="text" inputmode="decimal" id="editSellPrice" placeholder="0.00" autocomplete="off" />
              </div>
            </div>
            <div class="mb-2">
              <div class="edit-reason-label">Reason for price change <span class="edit-reason-opt">(optional)</span></div>
              <textarea class="form-control form-control-sm" id="editPriceReason" rows="2" placeholder="e.g. Promotional pricing, supplier cost change…" style="resize:vertical;font-size:12px;" autocomplete="off"></textarea>
            </div>
          </div>
        </div>

        {{-- ── TAB 2: SETTINGS ── --}}
        <div class="edit-tab-pane" id="emTab2">
          <div class="edit-section" style="margin-top:0;"><i class="ri-coin-line"></i>Cost Price Override <span style="font-size:10px;font-weight:400;text-transform:none;letter-spacing:0;color:#b0b7c3;">(this warehouse — blank = use base)</span></div>
          <div class="row g-2 mb-2">
            <div class="col-12"><input class="form-control form-control-sm" type="text" inputmode="decimal" id="editCostPrice" placeholder="0.00" autocomplete="off" /></div>
          </div>
          <div class="edit-section"><i class="ri-truck-line"></i>Supplier</div>
          <div class="row g-2 mb-2">
            <div class="col-12">
              <select class="form-select form-select-sm" id="editSupplier" autocomplete="off">
                <option value="">Default (base catalogue supplier)</option>
              </select>
            </div>
          </div>
          <div class="edit-section"><i class="ri-stack-line"></i>Reorder &amp; Limits</div>
          <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Reorder Point</label><input class="form-control form-control-sm" type="text" inputmode="decimal" id="editReorderPoint" autocomplete="off" /></div>
            <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Reorder Qty</label><input class="form-control form-control-sm" type="text" inputmode="decimal" id="editReorderQty" autocomplete="off" /></div>
            <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Max Stock</label><input class="form-control form-control-sm" type="text" inputmode="decimal" id="editMaxStock" autocomplete="off" /></div>
          </div>
          <div class="edit-section"><i class="ri-qr-code-line"></i>Barcode &amp; Batch</div>
          <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Barcode</label><input class="form-control form-control-sm" type="text" id="editBarcode" autocomplete="off" /></div>
            <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Batch Number</label><input class="form-control form-control-sm" type="text" id="editBatch" autocomplete="off" /></div>
            <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Expiry Date</label><input class="form-control form-control-sm" type="date" id="editExpiry" autocomplete="off" /></div>
          </div>
          <div class="edit-section"><i class="ri-toggle-line"></i>Status &amp; Behaviour</div>
          <div class="row g-2">
            <div class="col-6"><div class="form-check"><input class="form-check-input" type="checkbox" id="editTrackStock"><label class="form-check-label" for="editTrackStock" style="font-size:12px">Track stock</label></div></div>
            <div class="col-6"><div class="form-check"><input class="form-check-input" type="checkbox" id="editAllowNeg"><label class="form-check-label" for="editAllowNeg" style="font-size:12px">Allow negative</label></div></div>
            <div class="col-6"><div class="form-check"><input class="form-check-input" type="checkbox" id="editIsActive"><label class="form-check-label" for="editIsActive" style="font-size:12px">Active</label></div></div>
          </div>
        </div>

        {{-- ── TAB 3: BASE INFO (base catalogue — affects every warehouse) ── --}}
        <div class="edit-tab-pane" id="emTab3">
          <div class="bp-edit-warning">
            <i class="ri-alert-line" style="font-size:14px;flex-shrink:0;margin-top:1px;"></i>
            Changes here update the <strong>base catalogue</strong> and affect all warehouses using this product.
          </div>
          <input type="hidden" id="bpEditId">
          <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Name <span class="text-danger">*</span></label><input class="form-control form-control-sm" type="text" id="bpEditName" autocomplete="off" /></div>
            <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Code</label><input class="form-control form-control-sm" type="text" id="bpEditCode" autocomplete="off" /></div>
          </div>
          <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Unit</label><input class="form-control form-control-sm" type="text" id="bpEditUnit" autocomplete="off" /></div>
            <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Base Sell Price <span class="text-danger">*</span></label><input class="form-control form-control-sm" type="text" inputmode="decimal" id="bpEditSellPrice" placeholder="0.00" autocomplete="off" /></div>
          </div>
          <div class="row g-2 mb-2">
            <div class="col-6"><label class="form-label fw-semibold" style="font-size:12px">Base Cost Price</label><input class="form-control form-control-sm" type="text" inputmode="decimal" id="bpEditCostPrice" placeholder="0.00" autocomplete="off" /></div>
            <div class="col-6">
              <label class="form-label fw-semibold" style="font-size:12px">Default Supplier</label>
              <select class="form-select form-select-sm" id="bpEditSupplier" autocomplete="off"><option value="">Select supplier</option></select>
            </div>
          </div>
          <div class="alert border-0 py-2 px-3 mb-0" style="background:#ecfdf5;border-left:2px solid #059669 !important;border-radius:0 5px 5px 0;font-size:11px;color:#065f46;">
            <i class="ri-information-line me-1"></i>These are the catalogue defaults shown in <span style="color:#059669;font-weight:700;">green</span> for warehouses without an override.
          </div>
        </div>
      </div>

      <div class="modal-footer" style="padding:10px 18px 14px;justify-content:flex-end;gap:8px;">
        <a href="#" class="btn btn-secondary btn-sm" id="cancelEditBtn">Cancel</a>
        <a href="#" class="btn btn-primary btn-sm" id="submitEditBtn"><i class="ri-check-line me-1"></i> Update Branch Product</a>
        <a href="#" class="btn btn-success btn-sm" id="submitBaseProductBtn" style="display:none;"><i class="ri-check-line me-1"></i> Update Base Product</a>
      </div>
    </div>
  </div>
</div>

{{-- ══ DELETE MODAL ══ --}}
<div class="modal fade" id="deleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-danger"><h5 class="modal-title mh-title"><i class="ri-delete-bin-line"></i> Remove from Warehouse</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body text-center py-4">
      <i class="ri-error-warning-line text-danger" style="font-size:60px"></i>
      <h5 class="mt-2 mb-1">Remove <span id="deleteLabel" class="text-danger"></span>?</h5>
      <p style="font-size:13px;color:#6c757d;margin-bottom:0;">Removes from <strong>{{ $branchObj->name ?? 'this warehouse' }}</strong> only. Base product is kept.</p>
      <input type="hidden" id="deleteId"><input type="hidden" id="deleteRow"><input type="hidden" id="deleteStock">
    </div>
    <div class="modal-footer justify-content-center gap-2" style="padding:10px 20px 18px;"><a href="#" class="btn btn-secondary btn-sm px-4" id="keepBtn">Keep</a><a href="#" class="btn btn-danger btn-sm px-4" id="submitDeleteBtn">Remove</a></div>
  </div></div>
</div>

{{-- ══ FORCE DELETE CONFIRM (stock > 0) ══ --}}
<div class="modal fade" id="forceDeleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-danger"><h5 class="modal-title mh-title"><i class="ri-error-warning-line"></i> Product Still Has Stock</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body text-center py-4">
      <div class="confirm-icon-wrap"><i class="ri-alert-line"></i></div>
      <p style="font-size:13px;color:#6c757d;max-width:380px;margin:0 auto;">This product still has <strong id="forceDeleteStock"></strong> unit(s) in stock. Removing it will write the remaining stock off as <strong>WriteOff</strong> in the inventory log. Continue?</p>
    </div>
    <div class="modal-footer justify-content-center gap-2" style="padding:10px 20px 18px;"><button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-danger btn-sm px-4" id="forceDeleteSubmitBtn">Yes, Write Off &amp; Remove</button></div>
  </div></div>
</div>

{{-- ══ BULK ACTIONS MODAL ══ --}}
<div class="modal fade" id="bulkActionsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue">
      <h5 class="modal-title mh-title"><i class="ri-stack-line"></i> Bulk Actions <span style="font-size:12px;font-weight:400;opacity:.85" id="bulkActionsModalCountText">— 0 selected</span></h5>
      <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="padding:18px 20px !important;">
      <div class="bulk-option-card" id="boMarkActive"><div class="boc-icon boc-icon-base"><i class="ri-checkbox-circle-line"></i></div><div><div class="boc-title">Mark Active</div><div class="boc-desc">Enable selected products at this warehouse.</div></div></div>
      <div class="bulk-option-card" id="boMarkInactive"><div class="boc-icon boc-icon-delete"><i class="ri-close-circle-line"></i></div><div><div class="boc-title">Mark Inactive</div><div class="boc-desc">Hide selected products from sale here.</div></div></div>
      <div class="bulk-option-card" id="boTrackStockOn"><div class="boc-icon boc-icon-toggle"><i class="ri-toggle-line"></i></div><div><div class="boc-title">Enable Track Stock</div><div class="boc-desc">Sales will decrement stock for selected products.</div></div></div>
      <div class="bulk-option-card" id="boTrackStockOff"><div class="boc-icon boc-icon-toggle"><i class="ri-toggle-line"></i></div><div><div class="boc-title">Disable Track Stock</div><div class="boc-desc">Stop decrementing stock for selected products.</div></div></div>
      <div class="bulk-option-card" id="boBulkDelete"><div class="boc-icon boc-icon-delete"><i class="ri-delete-bin-line"></i></div><div><div class="boc-title">Delete from Warehouse</div><div class="boc-desc">Remove selected products from this warehouse only.</div></div></div>
    </div>
    <div class="modal-footer" style="padding:10px 20px 14px;"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button></div>
  </div></div>
</div>

{{-- ══ BULK DELETE CONFIRM ══ --}}
<div class="modal fade" id="confirmBulkDeleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-danger"><h5 class="modal-title mh-title"><i class="ri-delete-bin-line"></i> Remove from Warehouse</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body text-center py-4">
      <i class="ri-error-warning-line text-danger" style="font-size:60px"></i>
      <h5 class="mt-2 mb-1">Remove <span id="confirmBulkDeleteCount" class="text-danger">0</span> product(s)?</h5>
      <p style="font-size:13px;color:#6c757d;margin-bottom:0;">Removes from this warehouse only. Base products remain in the catalogue.</p>
    </div>
    <div class="modal-footer justify-content-center gap-2" style="padding:10px 20px 18px;"><button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Keep</button><button type="button" class="btn btn-danger btn-sm px-4" id="confirmBulkDeleteSubmitBtn">Remove</button></div>
  </div></div>
</div>

{{-- ══ FORCE BULK DELETE CONFIRM (some rows have stock > 0) ══ --}}
<div class="modal fade" id="forceBulkDeleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-danger"><h5 class="modal-title mh-title"><i class="ri-error-warning-line"></i> Some Products Still Have Stock</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body text-center py-4">
      <div class="confirm-icon-wrap"><i class="ri-alert-line"></i></div>
      <p style="font-size:13px;color:#6c757d;max-width:380px;margin:0 auto;"><strong id="forceBulkDeleteCount"></strong> of the selected product(s) still have stock. Removing them will write the remaining stock off in the inventory log. Continue for all selected?</p>
    </div>
    <div class="modal-footer justify-content-center gap-2" style="padding:10px 20px 18px;"><button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-danger btn-sm px-4" id="forceBulkDeleteSubmitBtn">Yes, Write Off &amp; Remove</button></div>
  </div></div>
</div>

{{-- ══ OVERVIEW MODAL ══ --}}
<div class="modal fade" id="overviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue"><h5 class="modal-title mh-title"><i class="ri-dashboard-line"></i> Warehouse Overview</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
    <div style="display:flex;border-bottom:1.5px solid #dde1f0;background:#f8f9fb;padding:0 18px;">
      <button class="overview-tab-btn active" id="ovTabShopBtn" onclick="switchOverviewTab('shop')"><i class="ri-store-2-line me-1"></i>Stock Value</button>
      <button class="overview-tab-btn" id="ovTabPriceBtn" onclick="switchOverviewTab('price')"><i class="ri-price-tag-3-line me-1"></i>Price Guide</button>
    </div>
    <div class="modal-body" style="padding:18px 20px !important;">
      <div id="ovTabShop">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:18px;">
          <div class="sv-metric"><div class="sv-label">Products</div><div class="sv-value" style="color:#4B5EBD;">{{ $branchProducts->count() }}</div></div>
          <div class="sv-metric"><div class="sv-label">Active</div><div class="sv-value" style="color:#198754;">{{ $activeCount }}</div></div>
          <div class="sv-metric"><div class="sv-label">Low / Zero</div><div class="sv-value" style="color:#d97706;">{{ $lowStockCount + $zeroCount }}</div></div>
        </div>
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
          <tbody>
            <tr style="border-bottom:1px solid #e9ecef;"><td style="padding:8px 0;color:#6c757d;font-weight:600;width:140px;">Warehouse</td><td style="padding:8px 0;font-weight:600;color:#1e293b;">{{ $branchObj->name ?? '—' }}</td></tr>
            <tr style="border-bottom:1px solid #e9ecef;"><td style="padding:8px 0;color:#6c757d;font-weight:600;">Zero stock</td><td style="padding:8px 0;color:#dc2626;font-weight:600;">{{ $zeroCount }}</td></tr>
            <tr style="border-bottom:1px solid #e9ecef;"><td style="padding:8px 0;color:#6c757d;font-weight:600;">Low stock</td><td style="padding:8px 0;color:#d97706;font-weight:600;">{{ $lowStockCount }}</td></tr>
            <tr><td style="padding:12px 0 4px;color:#6c757d;font-weight:600;">Total stock value</td><td style="padding:12px 0 4px;font-size:22px;font-weight:700;color:#4B5EBD;">MWK {{ number_format($shopValue, 0) }}</td></tr>
            <tr><td style="padding:4px 0;color:#6c757d;font-weight:600;">Valuation date</td><td style="padding:4px 0;color:#94a3b8;font-size:12px;">{{ now()->toDateString() }}</td></tr>
          </tbody>
        </table>
      </div>
      <div id="ovTabPrice" style="display:none;">
        <div class="pricing-swatch pricing-swatch-br"><span class="swatch-dot swatch-dot-br"></span><div class="flex-fill"><div class="swatch-label" style="color:#1d4ed8;">Warehouse Override</div><div class="swatch-desc">Price set specifically for this warehouse via Edit.</div></div><div style="text-align:right;font-weight:700;font-size:13px;color:#1d4ed8;">Blue</div></div>
        <div class="pricing-swatch pricing-swatch-bp"><span class="swatch-dot swatch-dot-bp"></span><div class="flex-fill"><div class="swatch-label" style="color:#059669;">Base Catalogue Default</div><div class="swatch-desc">No warehouse override — using the base catalogue price.</div></div><div style="text-align:right;font-weight:700;font-size:13px;color:#059669;">Green</div></div>
        <div style="background:#f8fafc;border-radius:8px;padding:10px 14px;font-size:12px;color:#475569;margin-top:8px;"><i class="ri-lightbulb-line me-1 text-warning"></i>Warehouse prices are set via the <strong>Edit</strong> modal. Adding always defaults to the base price.</div>
      </div>
    </div>
    <div class="modal-footer" style="padding:10px 20px 14px;"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button></div>
  </div></div>
</div>

{{-- ══ DOWNLOAD MODAL ══ --}}
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue"><h5 class="modal-title mh-title"><i class="ri-download-line"></i> Download</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="buttons"></div></div>
  </div></div>
</div>

{{-- ══ INFO MODAL ══ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue"><h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Branch Products</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" style="padding:18px 20px;">
      <p class="mb-2"><strong>Wholesale Branch Products</strong> are base catalogue items assigned to a specific warehouse with their own stock, prices, and reorder levels.</p>
      <hr class="my-3">
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <tbody>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;width:140px;border-bottom:1px solid #f1f5f9">Selling Price</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">Warehouse override shown in <span class="price-branch">blue</span>; base catalogue price shown in <span class="price-base">green</span>.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9">Stock Qty</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9"><span class="stock-zero">Red=zero</span>, <span class="stock-low">amber=low</span>, <span class="stock-ok">green=healthy</span>.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9">Reorder Point</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">Low-stock alert triggers when stock reaches this level.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569">Track Stock</td><td style="padding:8px 12px">When enabled, sales decrement the stock quantity.</td></tr>
        </tbody>
      </table>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button></div>
  </div></div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {

    toastr.options = { closeButton:true, progressBar:true, showMethod:'slideDown', timeOut:5000, allowHtml:true };

    var BRANCH_ID = {{ $branchObj->id ?? 'null' }};

    // ── Number formatting ──────────────────────────────────────────────────
    function purifyFloat(raw) {
        var s = String(raw || '').replace(/[^0-9.\-]/g, '');
        var parts = s.split('.');
        if (parts.length > 2) s = parts[0] + '.' + parts.slice(1).join('');
        return s;
    }
    function bindNumFmt(selector) {
        $(document).on('blur', selector, function () {
            var raw = purifyFloat($(this).val());
            if (raw === '' || raw === '-') return;
            var n = parseFloat(raw);
            if (!isNaN(n)) $(this).val(n.toLocaleString('en-US', { minimumFractionDigits:0, maximumFractionDigits:4 }));
        });
        $(document).on('focus', selector, function () {
            var raw = purifyFloat($(this).val());
            if (raw !== '') $(this).val(raw);
        });
        $(document).on('paste', selector, function (e) {
            e.preventDefault();
            var pasted = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
            $(this).val(purifyFloat(pasted));
        });
    }
    bindNumFmt([
        '#new-selling-price','#new-cost-price','#new-stock-qty','#new-branch-price','#new-reorder-point','#new-units-per-pack','#new-moq',
        '#editSellPrice','#editCostPrice','#editStockQty','#editReorderPoint','#editReorderQty','#editMaxStock',
        '#bpEditSellPrice','#bpEditCostPrice'
    ].join(', '));

    function normaliseNumericInputs(selector) { $(selector).each(function () { $(this).val(purifyFloat($(this).val())); }); }

    function handleAjaxError(xhr, status) {
        if (status === 'timeout') { toastr.error('Request timed out.', 'Timeout'); return; }
        if (xhr.status === 422) {
            var e = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : {};
            var m = ''; $.each(e, function(k,v) { m += v + '\n'; });
            toastr.error(m || (xhr.responseJSON && xhr.responseJSON.error) || 'Validation failed.', 'Error');
        } else if (xhr.status === 500) { toastr.error('Server error.', 'Error'); }
        else { toastr.error('Unspecified error.', 'Error'); }
    }

    function fmtNum(val, dec) {
        dec = dec === undefined ? 2 : dec;
        if (val === null || val === '' || val === undefined) return '—';
        var n = parseFloat(val);
        return isNaN(n) ? '—' : n.toLocaleString('en-US', { minimumFractionDigits:dec, maximumFractionDigits:dec });
    }
    function yn(val) {
        return parseInt(val) === 1
            ? '<span class="badge bg-success" style="font-size:11px">Yes</span>'
            : '<span class="badge bg-secondary" style="font-size:11px">No</span>';
    }
    // Rebuilds one <tr> from a JSON row shaped like the controller's
    // fetchFormattedRow() output — mirrors the server-side loop markup
    // above, so table.row.add()/remove() can update the table in place
    // instead of reloading the page.
    function buildRow(p) {
        var sq  = parseFloat(p.stock_quantity) || 0;
        var rp  = parseFloat(p.reorder_point)  || 0;
        var stockClass    = sq <= 0 ? 'stock-zero' : (sq <= rp ? 'stock-low' : 'stock-ok');
        var sellIsBranch  = (p.selling_price !== null && p.selling_price !== '');
        var displayPrice  = sellIsBranch ? p.selling_price : p.base_selling_price;
        var costIsBranch  = (p.cost_price !== null && p.cost_price !== '');

        function d(v) { return (v === null || v === undefined ? '' : v).toString().replace(/"/g, '&quot;'); }

        return `<tr id="${p.row}">
            <td>
                <input type="checkbox" class="selectRow" value="${p.id}" data-row-id="${p.row}" data-name="${d(p.name)}">
                &nbsp;${p.name}
            </td>
            <td>${p.code || '—'}</td>
            <td>${p.unit}</td>
            <td>${p.supplier_name || '—'}</td>
            <td><span class="${stockClass}">${fmtNum(sq, 2)}</span></td>
            <td><span class="${sellIsBranch ? 'price-branch' : 'price-base'}">${fmtNum(displayPrice, 2)}</span></td>
            <td>${p.batch_number || '—'}</td>
            <td>${p.expiry_date || '—'}</td>
            <td>
                <a href="#" class="viewDataBtn"
                   data-id="${p.id}" data-name="${d(p.name)}" data-code="${d(p.code)}"
                   data-unit="${d(p.unit)}" data-pack-unit="${d(p.pack_unit)}" data-units-per-pack="${d(p.units_per_pack)}"
                   data-supplier="${d(p.supplier_name)}" data-barcode="${d(p.primary_barcode)}"
                   data-batch="${d(p.batch_number)}" data-expiry="${d(p.expiry_date)}"
                   data-cost="${d(p.cost_price)}" data-sell="${d(p.selling_price)}"
                   data-base-cost="${d(p.base_cost_price)}" data-base-sell="${d(p.base_selling_price)}"
                   data-stock="${d(p.stock_quantity)}" data-reorder="${d(p.reorder_point)}"
                   data-reorder-qty="${d(p.reorder_quantity)}" data-max="${d(p.max_stock)}"
                   data-active="${p.is_active}" data-track="${p.track_stock}" data-neg="${p.allow_negative_stock}"
                   data-sell-is-branch="${sellIsBranch ? 1 : 0}" data-cost-is-branch="${costIsBranch ? 1 : 0}">
                    <i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px"></i>
                </a>
                <a href="#" class="editDataBtn"
                   data-id="${p.id}" data-row="${p.row}" data-name="${d(p.name)}" data-code="${d(p.code)}"
                   data-unit="${d(p.unit)}" data-supplier-id="${p.supplier_id || ''}"
                   data-barcode="${d(p.primary_barcode)}" data-batch="${d(p.batch_number)}" data-expiry="${d(p.expiry_date)}"
                   data-cost="${d(p.cost_price)}" data-sell="${d(p.selling_price)}"
                   data-base-cost="${d(p.base_cost_price)}" data-base-sell="${d(p.base_selling_price)}"
                   data-stock="${d(p.stock_quantity)}" data-reorder="${d(p.reorder_point)}"
                   data-reorder-qty="${d(p.reorder_quantity)}" data-max="${d(p.max_stock)}"
                   data-active="${p.is_active}" data-track="${p.track_stock}" data-neg="${p.allow_negative_stock}"
                   data-sell-is-branch="${sellIsBranch ? 1 : 0}"
                   data-base-product-id="${p.base_product_id}">
                    <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px"></i>
                </a>
                <a href="#" class="deleteDataBtn" data-label="${d(p.name)}" data-id="${p.id}" data-row="${p.row}" data-stock="${d(p.stock_quantity)}">
                    <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px"></i>
                </a>
            </td>
        </tr>`;
    }

    // Replaces an existing row (by id) with a freshly built one, or appends
    // it if the row isn't in the table yet.
    function upsertRow(p) {
        var existing = table.row('#' + p.row);
        if (existing.length) existing.remove();
        table.row.add($(buildRow(p)));
    }

    function updateSelectedCount() {
        var total = $('.selectRow').length;
        var count = $('.selectRow:checked').length;
        var badge = $('#bulkActionsHeaderCount');
        if (count > 0) {
            badge.text(count).addClass('show');
            $('#bulkActionsHeaderBtn').addClass('enabled').prop('disabled', false).attr('title', count + ' selected — click for bulk actions');
        } else {
            badge.text('').removeClass('show');
            $('#bulkActionsHeaderBtn').removeClass('enabled').prop('disabled', true).attr('title', 'Select rows to enable bulk actions');
        }
        $('#selectAll').prop('checked', total > 0 && count === total);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  DataTable
    // ════════════════════════════════════════════════════════════════════════
    @if($branchObj)

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
              customize: function(doc) { doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split(''); }
            }
        ]
    });
    table.buttons().container().appendTo($('#buttonsModal .buttons'));

    $('#overviewBtn').on('click', function(e) { e.preventDefault(); $('#overviewModal').modal('show'); });

    // ── Suppliers dropdown — loaded once, reused across New Product / Edit / Base Info ──
    var suppliersLoaded = false;
    function loadSuppliers() {
        if (suppliersLoaded) return;
        $.ajax({ type:'GET', url:'{{ route("wholesale.operations.suppliers.dropdown") }}',
            success:function(data){
                suppliersLoaded = true;
                var opts = '<option value="">Select supplier</option>';
                $.each(data.suppliers || [], function(i, s){ opts += '<option value="' + s.id + '">' + s.name + '</option>'; });
                $('#new-supplier, #bpEditSupplier').html(opts);
                $('#editSupplier').html('<option value="">Default (base catalogue supplier)</option>' + opts.replace('<option value="">Select supplier</option>', ''));
            }
        });
    }

    // ════════════════════════════════════════════════════════════════════════
    //  ADD PRODUCT — Search tab
    // ════════════════════════════════════════════════════════════════════════
    var searchTimer = null;
    $('#addProductBtn').on('click', function(e) {
        e.preventDefault();
        loadSuppliers();
        $('#addProductModal').modal('show');
    });

    $('#baseProductSearch').on('input', function() {
        var q = $(this).val().trim();
        clearTimeout(searchTimer);
        if (q.length < 1) { $('#searchResultList').hide().empty(); return; }
        searchTimer = setTimeout(function() {
            $.ajax({ type:'GET', url:'{{ route("wholesale.operations.branchproducts.search") }}', data:{ branch_id: BRANCH_ID, q: q },
                success:function(data) {
                    var products = data.products || [];
                    if (!products.length) { $('#searchResultList').html('<div class="text-center text-muted py-3" style="font-size:12px;">No matching products.</div>').show(); return; }
                    var html = '';
                    $.each(products, function(i, p) {
                        html += '<div class="sri-item" data-id="' + p.id + '">' +
                            '<div class="sri-main"><div class="sri-name-wrap"><div class="sri-name">' + p.name + ' <span class="sri-code">' + (p.code || '') + '</span></div></div>' +
                            '<span class="sri-price-tag">MWK ' + fmtNum(p.selling_price) + '</span></div>' +
                            '<div class="sri-controls">' +
                            '<div class="sri-qty-wrap"><span class="sri-qty-label">Qty</span><input type="number" min="0" step="any" class="sri-qty-input" id="sri-qty-' + p.id + '" placeholder="0"></div>' +
                            '<button type="button" class="sri-add-btn" data-id="' + p.id + '"><i class="ri-add-line"></i> Add</button>' +
                            '</div></div>';
                    });
                    $('#searchResultList').html(html).show();
                },
                error: handleAjaxError
            });
        }, 300);
    });

    $('#searchResultList').on('click', '.sri-add-btn', function() {
        var id = $(this).data('id');
        var qty = $('#sri-qty-' + id).val() || 0;
        var self = $(this); self.prop('disabled', true);
        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:'POST', url:'{{ route("wholesale.operations.branchproducts.insert") }}', timeout:60000,
            data:{ branch_id: BRANCH_ID, base_product_id: id, stock_quantity: qty, _token:'{{ csrf_token() }}' },
            beforeSend:function(){ $('#progressBar').show(); },
            complete:function(){ $('#progressBar').hide(); },
            success:function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    $('#addProductModal').modal('hide');
                    upsertRow(data.product);
                    table.draw(false);
                    updateSelectedCount();
                    $('#baseProductSearch').val('');
                    $('#searchResultList').hide().empty();
                    self.prop('disabled', false);
                }
                else { toastr.error(data.error || 'Failed.', 'Error'); self.prop('disabled', false); }
            },
            error:function(xhr, status) { handleAjaxError(xhr, status); self.prop('disabled', false); }
        });
    });

    // ════════════════════════════════════════════════════════════════════════
    //  ADD PRODUCT — New Product tab (create in base catalogue, then assign)
    // ════════════════════════════════════════════════════════════════════════
    window.setNpPriceSource = function(src) {
        $('#npBtnBase, #npBtnBranch').removeClass('np-ps-active-base np-ps-active-branch');
        if (src === 'base') { $('#npBtnBase').addClass('np-ps-active-base'); $('#npBasePriceArea').show(); $('#npBranchPriceArea').hide(); }
        else { $('#npBtnBranch').addClass('np-ps-active-branch'); $('#npBasePriceArea').hide(); $('#npBranchPriceArea').show(); }
    };

    $('#submitAddBtn').on('click', function(e) {
        e.preventDefault();
        normaliseNumericInputs('#new-selling-price,#new-cost-price,#new-stock-qty,#new-branch-price,#new-reorder-point,#new-units-per-pack,#new-moq');

        var name = $('#new-name').val().trim();
        if (!name) { toastr.warning('Product name is required.', 'Required'); $('#new-name').focus(); return; }

        var branchSourced = $('#npBtnBranch').hasClass('np-ps-active-branch');
        var sellPrice = branchSourced ? $('#new-branch-price').val() : $('#new-selling-price').val();
        if (!sellPrice || parseFloat(sellPrice) < 0) { toastr.warning('Selling price is required.', 'Required'); return; }

        var self = $(this); self.prop('disabled', true);
        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });

        $.ajax({
            type:'POST', url:'{{ route("wholesale.operations.baseproducts.insert") }}', timeout:60000,
            data:{
                name:name, code:$('#new-code').val(), unit:$('#new-unit').val() || 'Each',
                pack_unit:$('#new-pack-unit').val(), units_per_pack:$('#new-units-per-pack').val(),
                min_order_quantity:$('#new-moq').val() || 1,
                supplier_id:$('#new-supplier').val(), selling_price:sellPrice, cost_price:$('#new-cost-price').val(),
                _token:'{{ csrf_token() }}'
            },
            beforeSend:function(){ $('#progressBar').show(); },
            success:function(data) {
                if (data.status !== 201) { toastr.error(data.error || 'Failed to create base product.', 'Error'); self.prop('disabled', false); $('#progressBar').hide(); return; }
                var baseProductId = (data.product && data.product.id) || data.id;
                $.ajax({
                    type:'POST', url:'{{ route("wholesale.operations.branchproducts.insert") }}', timeout:60000,
                    data:{
                        branch_id: BRANCH_ID, base_product_id: baseProductId,
                        stock_quantity: $('#new-stock-qty').val() || 0, reorder_point: $('#new-reorder-point').val() || 0,
                        selling_price: branchSourced ? sellPrice : '', supplier_id: $('#new-supplier').val(),
                        _token:'{{ csrf_token() }}'
                    },
                    complete:function(){ $('#progressBar').hide(); },
                    success:function(data2) {
                        if (data2.status === 201) {
                            $('#addSuccessText').text(name + ' added to the catalogue and this warehouse.');
                            $('#addSuccessNotice').show();
                            toastr.success('Product created and assigned.', 'Success');
                            $('#addProductModal').modal('hide');
                            upsertRow(data2.product);
                            table.draw(false);
                            updateSelectedCount();
                            self.prop('disabled', false);
                        } else {
                            toastr.warning('Product created in the catalogue, but assigning it to this warehouse failed: ' + (data2.error || ''), 'Partial success');
                            self.prop('disabled', false);
                        }
                    },
                    error:function(xhr, status) { handleAjaxError(xhr, status); self.prop('disabled', false); }
                });
            },
            error:function(xhr, status) { $('#progressBar').hide(); handleAjaxError(xhr, status); self.prop('disabled', false); }
        });
    });

    // ════════════════════════════════════════════════════════════════════════
    //  ADD PRODUCT — Import CSV tab (base catalogue only)
    // ════════════════════════════════════════════════════════════════════════
    $('#csvImportBtn').on('click', function(e) {
        e.preventDefault();
        var file = $('#csv-file')[0].files[0];
        if (!file) { toastr.warning('Choose a CSV file first.', 'Required'); return; }
        var fd = new FormData();
        fd.append('file', file);
        fd.append('_token', '{{ csrf_token() }}');
        var self = $(this); self.prop('disabled', true);
        $('#csvImportProgress').text('Importing…');
        $.ajax({
            type:'POST', url:'{{ route("wholesale.operations.baseproducts.csv.upload") }}', timeout:120000,
            data:fd, processData:false, contentType:false,
            beforeSend:function(){ $('#progressBar').show(); },
            complete:function(){ $('#progressBar').hide(); self.prop('disabled', false); },
            success:function(data) {
                if (data.status === 201) { toastr.success(data.success || 'Import complete.', 'Success'); $('#csvImportProgress').text('Done — use the Search tab to stock these products at this warehouse.'); }
                else { toastr.error(data.error || 'Import failed.', 'Error'); $('#csvImportProgress').text(''); }
            },
            error:function(xhr, status) { handleAjaxError(xhr, status); $('#csvImportProgress').text(''); }
        });
    });

    // ════════════════════════════════════════════════════════════════════════
    //  VIEW MODAL
    // ════════════════════════════════════════════════════════════════════════
    $('#tbody').on('click', '.viewDataBtn', function(e) {
        e.preventDefault();
        var d = $(this).data();
        var sellIsBranch = parseInt(d.sellIsBranch) === 1;
        var costIsBranch = parseInt(d.costIsBranch) === 1;
        $('#vw-name').text(d.name);
        $('#vw-meta-line').text((d.code ? d.code + ' · ' : '') + (d.unit || '') + (d.packUnit ? ' · ' + d.packUnit + (d.unitsPerPack ? ' (' + d.unitsPerPack + '/pack)' : '') : ''));
        $('#vw-badges').html((parseInt(d.active) === 1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>'));
        $('#vw-sell').html('<span class="' + (sellIsBranch ? 'price-branch' : 'price-base') + '">' + fmtNum(sellIsBranch ? d.sell : d.baseSell) + '</span>');
        $('#vw-cost').html('<span class="' + (costIsBranch ? 'price-branch' : 'price-base') + '">' + fmtNum(costIsBranch ? d.cost : d.baseCost) + '</span>');
        $('#vw-supplier').text(d.supplier || '—');
        $('#vw-stock').text(fmtNum(d.stock)); $('#vw-reorder').text(fmtNum(d.reorder));
        $('#vw-reorder-qty').text(fmtNum(d.reorderQty)); $('#vw-max').text(fmtNum(d.max));
        $('#vw-barcode').text(d.barcode || '—'); $('#vw-batch').text(d.batch || '—'); $('#vw-expiry').text(d.expiry || '—');
        $('#vw-track').html(yn(d.track)); $('#vw-neg').html(yn(d.neg));
        $('#vwEditBtn').data('source-id', d.id);
        $('#viewProductModal').modal('show');
    });
    $('#vwEditBtn').on('click', function(e) {
        e.preventDefault();
        $('#viewProductModal').modal('hide');
        var id = $(this).data('source-id');
        setTimeout(function() { $('#tbody .editDataBtn[data-id="' + id + '"]').trigger('click'); }, 250);
    });

    // ════════════════════════════════════════════════════════════════════════
    //  EDIT MODAL
    // ════════════════════════════════════════════════════════════════════════
    window.switchEditTab = function(n) {
        $('.edit-modal-tab-btn').removeClass('em-active'); $('#emTab' + n + 'Btn').addClass('em-active');
        $('.edit-tab-pane').removeClass('em-show'); $('#emTab' + n).addClass('em-show');
        $('#submitEditBtn, #submitBaseProductBtn').hide();
        if (n === 3) { $('#submitBaseProductBtn').show(); } else { $('#submitEditBtn').show(); }
    };
    window.setEditPriceSource = function(src) {
        $('#editPscBase, #editPscBranch').removeClass('psc-active-base psc-active-branch');
        if (src === 'base') { $('#editPscBase').addClass('psc-active-base'); $('#editBranchPriceFields').hide(); }
        else { $('#editPscBranch').addClass('psc-active-branch'); $('#editBranchPriceFields').show(); }
    };

    $('#tbody').on('click', '.editDataBtn', function(e) {
        e.preventDefault();
        loadSuppliers();
        var d = $(this).data();
        var sellIsBranch = parseInt(d.sellIsBranch) === 1;

        $('#editId').val(d.id); $('#editRow').val(d.row); $('#editBaseProductId').val(d.baseProductId);
        $('#editModalName').text(d.name);
        $('#edit-ro-name').val(d.name); $('#edit-ro-unit').val(d.unit);
        $('#editStockQty').val(d.stock); $('#editStockReason').val('');
        $('#editCostPrice').val((d.cost !== undefined && d.cost !== '') ? d.cost : '');
        $('#editSupplier').val(d.supplierId || '');
        $('#editReorderPoint').val(d.reorder); $('#editReorderQty').val(d.reorderQty); $('#editMaxStock').val(d.max);
        $('#editBarcode').val(d.barcode); $('#editBatch').val(d.batch); $('#editExpiry').val(d.expiry);
        $('#editTrackStock').prop('checked', parseInt(d.track) === 1);
        $('#editAllowNeg').prop('checked', parseInt(d.neg) === 1);
        $('#editIsActive').prop('checked', parseInt(d.active) === 1);

        $('#editPscBaseVal').text('MWK ' + fmtNum(d.baseSell));
        $('#editPscBranchVal').text(sellIsBranch ? ('MWK ' + fmtNum(d.sell)) : '—');
        $('#editSellPrice').val(sellIsBranch ? d.sell : '');
        $('#editPriceReason').val('');
        setEditPriceSource(sellIsBranch ? 'branch' : 'base');

        $('#bpEditId').val(d.baseProductId); $('#bpEditName').val(d.name); $('#bpEditCode').val(d.code);
        $('#bpEditUnit').val(d.unit); $('#bpEditSellPrice').val(d.baseSell); $('#bpEditCostPrice').val(d.baseCost);

        switchEditTab(1);
        $('#editDataModal').modal('show');
    });
    $('#cancelEditBtn').on('click', function(e) { e.preventDefault(); $('#editDataModal').modal('hide'); });

    $('#submitEditBtn').on('click', function(e) {
        e.preventDefault();
        normaliseNumericInputs('#editStockQty,#editCostPrice,#editReorderPoint,#editReorderQty,#editMaxStock,#editSellPrice');

        var branchSourced = $('#editPscBranch').hasClass('psc-active-branch');
        if (branchSourced && (!$('#editSellPrice').val() || parseFloat($('#editSellPrice').val()) < 0)) {
            toastr.warning('Enter a warehouse selling price, or switch back to Base catalogue.', 'Required'); return;
        }

        var self = $(this); self.prop('disabled', true);
        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:'POST', url:'{{ route("wholesale.operations.branchproducts.update") }}', timeout:60000,
            data:{
                id: $('#editId').val(),
                stock_quantity: $('#editStockQty').val(), stock_change_reason: $('#editStockReason').val(),
                selling_price: branchSourced ? $('#editSellPrice').val() : '', price_change_reason: $('#editPriceReason').val(),
                cost_price: $('#editCostPrice').val(), supplier_id: $('#editSupplier').val(),
                reorder_point: $('#editReorderPoint').val(), reorder_quantity: $('#editReorderQty').val(), max_stock: $('#editMaxStock').val(),
                primary_barcode: $('#editBarcode').val(), batch_number: $('#editBatch').val(), expiry_date: $('#editExpiry').val(),
                track_stock: $('#editTrackStock').is(':checked') ? 1 : 0,
                allow_negative_stock: $('#editAllowNeg').is(':checked') ? 1 : 0,
                is_active: $('#editIsActive').is(':checked') ? 1 : 0,
                _token:'{{ csrf_token() }}'
            },
            beforeSend:function(){ $('#progressBar').show(); },
            complete:function(){ $('#progressBar').hide(); self.prop('disabled', false); },
            success:function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    $('#editDataModal').modal('hide');
                    upsertRow(data.product);
                    table.draw(false);
                }
                else { toastr.error(data.error || 'Failed.', 'Error'); }
            },
            error:handleAjaxError
        });
    });

    $('#submitBaseProductBtn').on('click', function(e) {
        e.preventDefault();
        normaliseNumericInputs('#bpEditSellPrice,#bpEditCostPrice');
        var name = $('#bpEditName').val().trim();
        if (!name) { toastr.warning('Product name is required.', 'Required'); $('#bpEditName').focus(); return; }
        var sell = $('#bpEditSellPrice').val();
        if (!sell || parseFloat(sell) < 0) { toastr.warning('Selling price is required.', 'Required'); $('#bpEditSellPrice').focus(); return; }
        var self = $(this); self.prop('disabled', true);
        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:'POST', url:'{{ route("wholesale.operations.baseproducts.update") }}', timeout:60000,
            data:{ id:$('#bpEditId').val(), name:name, unit:$('#bpEditUnit').val(), code:$('#bpEditCode').val(), selling_price:sell, cost_price:$('#bpEditCostPrice').val(), _token:'{{ csrf_token() }}' },
            beforeSend:function(){ $('#progressBar').show(); },
            complete:function(){ $('#progressBar').hide(); self.prop('disabled', false); },
            success:function(data) {
                if (data.status === 201) {
                    toastr.success(data.success || 'Base product updated.', 'Success');
                    $('#editDataModal').modal('hide');
                    var branchRowId = $('#editId').val();
                    $.ajax({
                        type:'GET', url:'{{ route("wholesale.operations.branchproducts.row") }}',
                        data:{ id: branchRowId },
                        success:function(rowData) {
                            if (rowData.product) { upsertRow(rowData.product); table.draw(false); }
                        }
                    });
                }
                else { toastr.error(data.error || 'Error.', 'Error'); }
            },
            error:handleAjaxError
        });
    });

    // ════════════════════════════════════════════════════════════════════════
    //  DELETE (single) — with force-write-off retry when stock > 0
    // ════════════════════════════════════════════════════════════════════════
    $('#tbody').on('click', '.deleteDataBtn', function(e) {
        e.preventDefault();
        $('#deleteLabel').text($(this).data('label'));
        $('#deleteRow').val($(this).data('row'));
        $('#deleteId').val($(this).data('id'));
        $('#deleteStock').val($(this).data('stock'));
        $('#deleteModal').modal('show');
    });
    $('#keepBtn').on('click', function(e) { e.preventDefault(); $('#deleteModal').modal('hide'); });

    function doDelete(force) {
        var id = $('#deleteId').val();
        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:'POST', url:'{{ route("wholesale.operations.branchproducts.delete") }}', timeout:60000,
            data:{ id:id, force: force ? 1 : 0, _token:'{{ csrf_token() }}' },
            beforeSend:function(){ $('#progressBar').show(); },
            complete:function(){ $('#progressBar').hide(); },
            success:function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    $('#deleteModal, #forceDeleteModal').modal('hide');
                    var row = $('#deleteRow').val();
                    table.row('#' + row).remove().draw(false);
                    updateSelectedCount();
                }
                else if (data.status === 409 && data.requires_force) {
                    $('#deleteModal').modal('hide');
                    $('#forceDeleteStock').text(fmtNum(data.stock_quantity));
                    setTimeout(function(){ $('#forceDeleteModal').modal('show'); }, 250);
                } else { toastr.error(data.error || 'Failed.', 'Error'); }
            },
            error:handleAjaxError
        });
    }
    $('#submitDeleteBtn').on('click', function(e) { e.preventDefault(); doDelete(false); });
    $('#forceDeleteSubmitBtn').on('click', function(e) { e.preventDefault(); doDelete(true); });

    // ════════════════════════════════════════════════════════════════════════
    //  BULK SELECTION
    // ════════════════════════════════════════════════════════════════════════
    $('#selectAll').on('change', function() { $('.selectRow').prop('checked', this.checked); updateSelectedCount(); });
    $('#tbody').on('click', '.selectRow', function() { updateSelectedCount(); });
    function getSelectedIds() { var ids=[]; $('.selectRow:checked').each(function(){ ids.push($(this).val()); }); return ids; }

    $('#bulkActionsHeaderBtn').on('click', function() {
        if (!$(this).hasClass('enabled')) return;
        $('#bulkActionsModalCountText').text('— ' + $('.selectRow:checked').length + ' selected');
        $('#bulkActionsModal').modal('show');
    });

    function bulkStatus(isActive) {
        var ids = getSelectedIds(); if (!ids.length) { toastr.warning('No products selected.', 'Warning'); return; }
        $('#bulkActionsModal').modal('hide');
        $.ajaxSetup({ headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:'POST', url:'{{ route("wholesale.operations.branchproducts.bulkstatus") }}', timeout:60000,
            data:{ ids:ids, is_active:isActive, _token:'{{ csrf_token() }}' },
            beforeSend:function(){ $('#progressBar').show(); }, complete:function(){ $('#progressBar').hide(); },
            success:function(data){
                if(data.status===201){
                    toastr.success(data.success,'Success');
                    (data.products || []).forEach(function(p){ upsertRow(p); });
                    table.draw(false);
                    updateSelectedCount();
                } else { toastr.error(data.error||'Failed.','Error'); }
            },
            error:handleAjaxError
        });
    }
    function bulkTrackStock(track) {
        var ids = getSelectedIds(); if (!ids.length) { toastr.warning('No products selected.', 'Warning'); return; }
        $('#bulkActionsModal').modal('hide');
        $.ajaxSetup({ headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:'POST', url:'{{ route("wholesale.operations.branchproducts.bulktrackstock") }}', timeout:60000,
            data:{ ids:ids, track_stock:track, _token:'{{ csrf_token() }}' },
            beforeSend:function(){ $('#progressBar').show(); }, complete:function(){ $('#progressBar').hide(); },
            success:function(data){
                if(data.status===201){
                    toastr.success(data.success,'Success');
                    (data.products || []).forEach(function(p){ upsertRow(p); });
                    table.draw(false);
                    updateSelectedCount();
                } else { toastr.error(data.error||'Failed.','Error'); }
            },
            error:handleAjaxError
        });
    }
    $('#boMarkActive').on('click', function() { bulkStatus(1); });
    $('#boMarkInactive').on('click', function() { bulkStatus(0); });
    $('#boTrackStockOn').on('click', function() { bulkTrackStock(1); });
    $('#boTrackStockOff').on('click', function() { bulkTrackStock(0); });

    $('#boBulkDelete').on('click', function() {
        var ids = getSelectedIds(); if (!ids.length) { toastr.warning('No products selected.', 'Warning'); return; }
        $('#bulkActionsModal').modal('hide');
        $('#confirmBulkDeleteCount').text(ids.length);
        setTimeout(function(){ $('#confirmBulkDeleteModal').modal('show'); }, 250);
    });

    function doBulkDelete(force) {
        var ids = getSelectedIds(); if (!ids.length) { $('#confirmBulkDeleteModal, #forceBulkDeleteModal').modal('hide'); return; }
        $.ajaxSetup({ headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:'POST', url:'{{ route("wholesale.operations.branchproducts.bulkdelete") }}', timeout:120000,
            data:{ ids:ids, force: force ? 1 : 0, _token:'{{ csrf_token() }}' },
            beforeSend:function(){ $('#progressBar').show(); }, complete:function(){ $('#progressBar').hide(); },
            success:function(data){
                if (data.status === 201) {
                    toastr.success(data.success,'Success');
                    $('#confirmBulkDeleteModal, #forceBulkDeleteModal').modal('hide');
                    ids.forEach(function(id){ table.row('#row' + id).remove(); });
                    table.draw(false);
                    updateSelectedCount();
                }
                else if (data.status === 409 && data.requires_force) {
                    $('#confirmBulkDeleteModal').modal('hide');
                    $('#forceBulkDeleteCount').text(data.blocked_count);
                    setTimeout(function(){ $('#forceBulkDeleteModal').modal('show'); }, 250);
                } else { toastr.error(data.error||'Failed.','Error'); }
            },
            error:handleAjaxError
        });
    }
    $('#confirmBulkDeleteSubmitBtn').on('click', function(e) { e.preventDefault(); doBulkDelete(false); });
    $('#forceBulkDeleteSubmitBtn').on('click', function(e) { e.preventDefault(); doBulkDelete(true); });

    @endif

    $('#infoBtn').on('click', function(e) { e.preventDefault(); $('#infoModal').modal('show'); });
    $('#tableButtonsBtn').on('click', function(e) { e.preventDefault(); $('#buttonsModal').modal('show'); });
});

function switchOverviewTab(tab) {
    document.getElementById('ovTabShop').style.display  = tab==='shop' ? '' : 'none';
    document.getElementById('ovTabPrice').style.display = tab==='price'? '' : 'none';
    document.getElementById('ovTabShopBtn').className   = 'overview-tab-btn' + (tab==='shop' ? ' active' : '');
    document.getElementById('ovTabPriceBtn').className  = 'overview-tab-btn' + (tab==='price'? ' active' : '');
}
</script>
@endsection