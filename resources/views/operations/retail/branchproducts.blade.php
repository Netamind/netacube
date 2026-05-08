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
            ->select(
                'rbp.*',
                'bp.name',
                'bp.code',
                'bp.unit',
                'bp.supplier',
                'bp.selling_price as bp_sell',
                'bp.cost_price    as bp_cost'
            )
            ->get();

        foreach ($branchProducts as $bp) {
            $shopValue += (float)$bp->selling_price * (float)$bp->stock_quantity;
        }
    }

    $baseProducts = collect();
    if ($selectedBranch) {
        $alreadyIn = $branchProducts->pluck('base_product_id')->toArray();
        $baseProducts = DB::connection('tenant')
            ->table('retail_base_products')
            ->whereNotIn('id', $alreadyIn)
            ->where('is_product', 1)
            ->get();
    }

    $suppliers = DB::connection('tenant')->table('retail_base_products')
                    ->whereNotNull('supplier')->where('supplier', '!=', '')
                    ->distinct()->orderBy('supplier')->pluck('supplier');

    $maintableTitle = 'Branch Products — ' . ($selectedBranch->name ?? 'All');

    $activeCount   = $branchProducts->where('is_active', 1)->count();
    $lowStockCount = $branchProducts->filter(fn($p) => (float)$p->stock_quantity <= (float)$p->reorder_point && (float)$p->stock_quantity > 0)->count();
    $zeroCount     = $branchProducts->filter(fn($p) => (float)$p->stock_quantity <= 0)->count();
@endphp

<style>
/* ── DataTable export buttons ───────────────────────────────────────────── */
.dt-buttons .btn {
  background: transparent !important; background-image: none !important;
  box-shadow: none !important; border-color: #5bc0de; color: #5bc0de;
}
.dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }

/* ── Card chrome ────────────────────────────────────────────────────────── */
.card-header {
  padding: 0.5rem 1.5rem !important;
  background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff;
  border-radius: 10px 10px 0 0 !important;
}
.card-body  { padding: 0 1.5rem 1.5rem 1.5rem !important; }
.card       { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; }
.card-header h4 { color:#fff; font-weight:600; margin-bottom:0; display:flex; align-items:center; }
.card-header h4 i { margin-right: 0.25rem; }
.card-header .btn-light {
  height:28px; padding:0 10px;
  display:flex; align-items:center; justify-content:center; line-height:1;
}
.card-header .btn-light:hover { background-color:#f8f9fa; transition:background-color 0.2s; }

/* ── Bulk bar ────────────────────────────────────────────────────────────── */
#bulkBar {
  background: #eef0f7; border-bottom: 1px solid #d6daf0;
  padding: 7px 1.5rem; display: none;
  align-items: center; justify-content: space-between;
}
#bulkBar.visible { display: flex !important; }

#bulkTriggerBtn {
  font-size:12px; font-weight:700; height:30px; padding:0 14px;
  display:flex; align-items:center; gap:6px;
  background: linear-gradient(to right,#4B5EBD,#576CC0);
  border: none; color:#fff; border-radius:6px;
  box-shadow: 0 2px 6px rgba(75,94,189,0.35);
  cursor:pointer; transition: opacity .15s;
}
#bulkTriggerBtn:hover { opacity:.88; }

/* ── Table alignment ────────────────────────────────────────────────────── */
#maintable thead th,
table.dataTable thead th { text-align:center !important; vertical-align:middle !important; }
#maintable thead th:first-child,
table.dataTable thead th:first-child { text-align:left !important; }
#maintable tbody td,
table.dataTable tbody td { text-align:center !important; vertical-align:middle !important; }
#maintable tbody td:first-child,
table.dataTable tbody td:first-child { text-align:left !important; }

/* ── Badges & prices ────────────────────────────────────────────────────── */
.price-cell { font-size:12px; font-weight:600; }
.stock-ok   { color: #16a34a; font-weight: 700; }
.stock-low  { color: #d97706; font-weight: 700; }
.stock-zero { color: #dc2626; font-weight: 700; }

/* ── Price source colors ─────────────────────────────────────────────────── */
.price-branch { color: #1d4ed8; font-weight: 700; }
.price-base   { color: #059669; font-weight: 600; }

/* ── No branch selected banner ──────────────────────────────────────────── */
.no-branch-wrap { padding: 48px 20px; text-align: center; color: #94a3b8; }
.no-branch-wrap i { font-size: 52px; display: block; margin-bottom: 12px; color: #c8d0ed; }
.no-branch-wrap h5 { color: #64748b; font-weight: 600; }

/* ── Modal header helpers ───────────────────────────────────────────────── */
.mh-blue   { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-green  { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-danger { background:linear-gradient(135deg,#c0392b,#e74c3c); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-teal   { background:linear-gradient(135deg,#0ea5e9,#0284c7); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-title  { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close  { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }

/* ── View modal ─────────────────────────────────────────────────────────── */
.view-grid { display:grid; grid-template-columns:1fr 1fr; gap:6px 14px; }
.view-item label { font-size:10px; font-weight:600; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:2px; }
.view-item .view-val { font-size:13px; color:#1e293b; font-weight:500; }
.view-item .view-val.muted { color:#9ca3af; font-style:italic; }
.view-item.full { grid-column:1/-1; }

/* ── Branch select in header ────────────────────────────────────────────── */
#branchSelectHeader {
  border: none; background: transparent; color: #fff;
  font-size: 18px; font-weight: 600; cursor: pointer;
  padding: 0; outline: none; max-width: 300px;
}
#branchSelectHeader option { color: #1e293b; background: #fff; font-size: 14px; }

/* ── Add product modal: search result list ──────────────────────────────── */
.search-result-list {
  max-height: 380px; overflow-y: auto;
  border: 1px solid #dee2e6; border-radius: 8px; background: #fff;
  display: none; box-shadow: 0 4px 16px rgba(0,0,0,0.10);
}
.search-result-item { border-bottom: 1px solid #f1f5f9; transition: background .12s; }
.search-result-item:last-child { border-bottom: none; }
.sri-row { display:flex; align-items:center; gap:8px; padding:6px 10px; transition:background .12s; }
.search-result-item:hover .sri-row { background: #eef0fa; }
.sri-name { flex:1; font-weight:600; font-size:13px; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.sri-name .sri-code { font-weight:400; color:#64748b; }
.sri-meta { font-size:11px; color:#64748b; white-space:nowrap; flex-shrink:0; background:#f1f5f9; padding:2px 7px; border-radius:10px; font-weight:500; }
.sri-qty-input {
  width:72px; text-align:center; border:1.5px solid #c8d0ed; border-radius:6px;
  height:30px; font-size:13px; font-weight:600; padding:0 6px; flex-shrink:0;
  color:#1e293b; background:#f8f9ff; transition:border-color .15s,box-shadow .15s; outline:none;
}
.sri-qty-input:focus { border-color:#4B5EBD; box-shadow:0 0 0 3px rgba(75,94,189,0.12); background:#fff; }
.sri-qty-input:disabled { background:#f0f0f0; color:#aaa; }
.sri-add-btn {
  height:30px; padding:0 14px; font-size:12px; font-weight:700; letter-spacing:.3px;
  border:none; border-radius:6px; cursor:pointer; flex-shrink:0;
  background:linear-gradient(135deg,#4B5EBD,#576CC0); color:#fff;
  display:flex; align-items:center; gap:4px;
  box-shadow:0 2px 6px rgba(75,94,189,0.28); transition:opacity .15s,box-shadow .15s;
}
.sri-add-btn:hover:not(:disabled) { opacity:.88; box-shadow:0 4px 10px rgba(75,94,189,0.35); }
.sri-add-btn:disabled { background:#e2e8f0 !important; color:#94a3b8 !important; box-shadow:none; cursor:default; }
.sri-added-msg { font-size:11px; font-weight:700; color:#16a34a; white-space:nowrap; display:none; flex-shrink:0; }
.sri-added-msg i { margin-right:2px; }

/* ── Bulk section ────────────────────────────────────────────────────────── */
.bulk-section { background:#f8f9fa; border-radius:8px; padding:12px 14px; margin-bottom:12px; }
.bulk-section-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#6c757d; margin-bottom:10px; }

/* ── Pricing explanation modal swatches ─────────────────────────────────── */
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

/* ── Shop value metric cards ─────────────────────────────────────────────── */
.sv-metric { background:#eef0f7; border-radius:8px; padding:10px 12px; text-align:center; }
.sv-metric .sv-label { font-size:11px; color:#6c757d; margin-bottom:4px; }
.sv-metric .sv-value { font-size:20px; font-weight:600; }

/* ── Spinner ─────────────────────────────────────────────────────────────── */
@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }

/* ══════════════════════════════════════════════════════════════════════════
   EDIT MODAL — improved tab navigation
══════════════════════════════════════════════════════════════════════════ */
#editModalTabs {
  display: flex;
  gap: 0;
  background: #f1f3f9;
  padding: 10px 18px 0;
  border-bottom: 1.5px solid #dde1f0;
  margin: 0;
  list-style: none;
}

#editModalTabs .nav-item { margin: 0; }

#editModalTabs .nav-link {
  position: relative;
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 500;
  color: #94a3b8;
  padding: 7px 16px 9px;
  border: none;
  background: none;
  border-radius: 6px 6px 0 0;
  border-bottom: 2px solid transparent;
  margin-bottom: -1.5px;
  cursor: pointer;
  transition: color .15s, background .15s;
  text-decoration: none;
}

#editModalTabs .nav-link i {
  font-size: 14px;
  opacity: .6;
  transition: opacity .15s;
}

#editModalTabs .nav-link:hover:not(.active) {
  color: #4B5EBD;
  background: rgba(75,94,189,0.06);
}

#editModalTabs .nav-link.active {
  color: #4B5EBD;
  background: #fff;
  border-bottom: 2px solid #4B5EBD;
  font-weight: 600;
}

#editModalTabs .nav-link.active i { opacity: 1; }

.edit-tab-badge {
  font-size: 10px;
  font-weight: 500;
  padding: 1px 7px;
  border-radius: 20px;
  background: #e8eaf6;
  color: #6c757d;
  line-height: 1.6;
  border: 0.5px solid #d0d4ee;
  transition: background .15s, color .15s, border-color .15s;
}

#editModalTabs .nav-link.active .edit-tab-badge {
  background: #eff3ff;
  color: #4B5EBD;
  border-color: #c7d0f5;
}

/* ── Edit modal read-only fields ────────────────────────────────────────── */
.edit-readonly-field {
  background:#f8f9fa !important; color:#6c757d !important;
  border-color:#dee2e6 !important; cursor:default !important;
}

/* ── Subtle link to other tab ───────────────────────────────────────────── */
.edit-tab-crosslink {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  color: #4B5EBD;
  text-decoration: none;
  margin-bottom: 14px;
}
.edit-tab-crosslink:hover { text-decoration: underline; color: #3d4fa0; }

/* ── Price source card toggle ───────────────────────────────────────────── */
.price-source-toggle {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
  margin-bottom: 10px;
}

.price-source-card {
  border: 0.5px solid #dee2e6;
  border-radius: 8px;
  padding: 10px 12px;
  cursor: pointer;
  transition: border-color .15s, background .15s;
  user-select: none;
}
.price-source-card:hover { border-color: #adb5bd; }

.price-source-card.active-base {
  border: 1.5px solid #059669;
  background: #f0fdf4;
}
.price-source-card.active-branch {
  border: 1.5px solid #1d4ed8;
  background: #eff6ff;
}

.psc-label {
  font-size: 12px;
  font-weight: 600;
  color: #374151;
  display: flex;
  align-items: center;
  gap: 5px;
  margin-bottom: 3px;
}
.psc-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  display: inline-block;
}
.psc-value {
  font-size: 15px;
  font-weight: 600;
  color: #9ca3af;
}
.price-source-card.active-base   .psc-value { color: #059669; }
.price-source-card.active-branch .psc-value { color: #1d4ed8; }

.psc-desc {
  font-size: 11px;
  color: #9ca3af;
  margin-top: 2px;
}

/* ── Contextual price hint strip ────────────────────────────────────────── */
.price-context-hint {
  font-size: 11px;
  padding: 7px 10px;
  border-radius: 6px;
  margin-bottom: 12px;
  display: flex;
  align-items: flex-start;
  gap: 6px;
}
.pch-base   { background: #f0fdf4; color: #065f46; }
.pch-branch { background: #eff6ff; color: #1e40af; }

/* ── Edit section titles ────────────────────────────────────────────────── */
.edit-section-title {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .7px;
  color: #6c757d;
  margin-bottom: 8px;
  margin-top: 4px;
  display: flex;
  align-items: center;
  gap: 5px;
}

/* ── Base product tab warning ────────────────────────────────────────────── */
.bp-edit-warning {
  background: #fffbeb;
  border-left: 2px solid #f59e0b;
  border-radius: 0 5px 5px 0;
  padding: 8px 12px;
  font-size: 11px;
  color: #92400e;
  margin-bottom: 14px;
  display: flex;
  align-items: flex-start;
  gap: 6px;
}
</style>

<div class="progress" id="progressBar" role="progressbar"
     style="height:8px;transform:rotate(180deg);display:none">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

  {{-- ── Card header ─────────────────────────────────────────────────────── --}}
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0" style="gap:8px;">
      <i class="ri-store-2-line me-1"></i>
      <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
            id="headerBranchForm" style="margin:0;display:inline;">
        @csrf
        <input type="hidden" name="user_id" value="{{ Auth::id() }}">
        <select name="branch_id" id="branchSelectHeader"
                onchange="document.getElementById('headerBranchForm').submit()">
          <option value="" hidden>{{ $selectedBranch ? $selectedBranch->name : '— Select Branch —' }}</option>
          @foreach($branches as $b)
            <option value="{{ $b->id }}"
              {{ ($pref && $pref->branch_id == $b->id) ? 'selected' : '' }}>
              {{ $b->name }}
            </option>
          @endforeach
        </select>
      </form>
    </h4>

    <div class="d-flex align-items-center" style="gap:4px;">
      @if($selectedBranch)
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="shopValueBtn" title="View shop value">
        <i class="ri-funds-line"></i>
      </a>
      @endif
      <a href="#" class="btn btn-light text-info fs-16 mx-1" id="pricingInfoBtn" title="Price colour guide">
        <i class="ri-price-tag-3-line"></i>
      </a>
      <a href="#" class="btn btn-light text-success fs-16 mx-1" id="addProductBtn"
         title="Add product to branch" @if(!$selectedBranch) style="pointer-events:none;opacity:.5" @endif>
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

  {{-- ── Bulk bar ─────────────────────────────────────────────────────────── --}}
  @if($selectedBranch)
  <div id="bulkBar">
    <div id="bulkLeft">
      <button type="button" id="bulkTriggerBtn" style="display:none;" title="Open bulk actions">
        <i class="ri-checkbox-multiple-line"></i>
        <span id="selectedCount">0</span>&nbsp;Selected
      </button>
    </div>
    <div id="bulkRight"></div>
  </div>
  @endif

  {{-- ── Table / Empty state ─────────────────────────────────────────────── --}}
  <div class="card-body">

    @if(!$selectedBranch)
      <div class="no-branch-wrap">
        <i class="ri-store-line"></i>
        <h5>No Branch Selected</h5>
        <p style="font-size:13px;">Select a branch from the header above to view and manage its products.</p>
      </div>
    @else

    <table id="maintable"
           class="table table-sm table-striped row-border order-column w-100 mt-3">
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
            $row    = 'row' . $bp->id;
            $sq     = (float)$bp->stock_quantity;
            $rp     = (float)$bp->reorder_point;
            $stockClass = $sq <= 0 ? 'stock-zero' : ($sq <= $rp ? 'stock-low' : 'stock-ok');
            $sellIsBranch = ($bp->selling_price !== null && (string)$bp->selling_price !== (string)$bp->bp_sell);
            $costIsBranch = ($bp->cost_price    !== null && (string)$bp->cost_price    !== (string)$bp->bp_cost);
          @endphp
          <tr id="{{ $row }}">
            <td>
              <input type="checkbox" class="selectRow" value="{{ $bp->id }}" data-row-id="{{ $row }}">
              &nbsp;{{ $bp->name }}
            </td>
            <td>{{ $bp->code ?? '—' }}</td>
            <td>{{ $bp->unit }}</td>
            <td><span class="{{ $stockClass }}">{{ number_format($sq, 0) }}</span></td>
            <td>
              <span class="{{ $sellIsBranch ? 'price-branch' : 'price-base' }}" style="font-size:12px">
                {{ number_format($bp->selling_price, 2) }}
              </span>
            </td>
            <td>{{ $bp->batch_number ?? '—' }}</td>
            <td>{{ $bp->expiry_date ?? '—' }}</td>
            <td>
              <a href="#" class="viewDataBtn"
                 data-id="{{ $bp->id }}"
                 data-name="{{ $bp->name }}"
                 data-code="{{ $bp->code }}"
                 data-unit="{{ $bp->unit }}"
                 data-supplier="{{ $bp->supplier }}"
                 data-barcode="{{ $bp->primary_barcode }}"
                 data-batch="{{ $bp->batch_number }}"
                 data-expiry="{{ $bp->expiry_date }}"
                 data-cost="{{ $bp->cost_price }}"
                 data-sell="{{ $bp->selling_price }}"
                 data-stock="{{ $bp->stock_quantity }}"
                 data-reorder="{{ $bp->reorder_point }}"
                 data-reorder-qty="{{ $bp->reorder_quantity }}"
                 data-max="{{ $bp->max_stock }}"
                 data-active="{{ $bp->is_active }}"
                 data-track="{{ $bp->track_stock }}"
                 data-neg="{{ $bp->allow_negative_stock }}"
                 data-sell-is-branch="{{ $sellIsBranch ? 1 : 0 }}"
                 data-cost-is-branch="{{ $costIsBranch ? 1 : 0 }}"
                 data-bp-sell="{{ $bp->bp_sell }}"
                 data-bp-cost="{{ $bp->bp_cost }}">
                <i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px"></i>
              </a>
              <a href="#" class="editDataBtn"
                 data-id="{{ $bp->id }}"
                 data-row="{{ $row }}"
                 data-name="{{ $bp->name }}"
                 data-code="{{ $bp->code }}"
                 data-unit="{{ $bp->unit }}"
                 data-supplier="{{ $bp->supplier }}"
                 data-barcode="{{ $bp->primary_barcode }}"
                 data-batch="{{ $bp->batch_number }}"
                 data-expiry="{{ $bp->expiry_date }}"
                 data-cost="{{ $bp->cost_price }}"
                 data-sell="{{ $bp->selling_price }}"
                 data-stock="{{ $bp->stock_quantity }}"
                 data-reorder="{{ $bp->reorder_point }}"
                 data-reorder-qty="{{ $bp->reorder_quantity }}"
                 data-max="{{ $bp->max_stock }}"
                 data-active="{{ $bp->is_active }}"
                 data-track="{{ $bp->track_stock }}"
                 data-neg="{{ $bp->allow_negative_stock }}"
                 data-sell-is-branch="{{ $sellIsBranch ? 1 : 0 }}"
                 data-cost-is-branch="{{ $costIsBranch ? 1 : 0 }}"
                 data-bp-sell="{{ $bp->bp_sell }}"
                 data-bp-cost="{{ $bp->bp_cost }}"
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

{{-- ══════════════════════════════════════════════════════════════════════
     SHOP VALUE MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="shopValueModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-store-2-line"></i> Branch Overview</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:18px 20px !important;">
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
            <div class="sv-label">Low / Zero stock</div>
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
              <td style="padding:8px 0;color:#6c757d;font-weight:600;">Zero stock items</td>
              <td style="padding:8px 0;color:#dc2626;font-weight:600;">{{ $zeroCount }}</td>
            </tr>
            <tr style="border-bottom:1px solid #e9ecef;">
              <td style="padding:8px 0;color:#6c757d;font-weight:600;">Low stock items</td>
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
      <div class="modal-footer" style="padding:10px 20px 14px;">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     PRICING COLOUR GUIDE MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="pricingInfoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-teal">
        <h5 class="modal-title mh-title"><i class="ri-price-tag-3-line"></i> Price Colour Guide</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:20px 22px !important;">
        <p style="font-size:13px;color:#475569;margin-bottom:16px;">
          Each product's selling price can come from two sources. The colour tells you which applies.
        </p>
        <div class="pricing-swatch pricing-swatch-br">
          <span class="swatch-dot swatch-dot-br"></span>
          <div class="flex-fill">
            <div class="swatch-label" style="color:#1d4ed8;">Branch Override</div>
            <div class="swatch-desc">This price was explicitly set for <strong>this branch</strong>, overriding the base catalogue.</div>
          </div>
          <div style="text-align:right;flex-shrink:0;">
            <div class="price-demo-br">1,250.00</div>
            <div style="font-size:10px;color:#93c5fd;">Blue</div>
          </div>
        </div>
        <div class="pricing-swatch pricing-swatch-bp">
          <span class="swatch-dot swatch-dot-bp"></span>
          <div class="flex-fill">
            <div class="swatch-label" style="color:#059669;">Base Product Default</div>
            <div class="swatch-desc">No branch price set. Using the default from the <strong>base catalogue</strong>.</div>
          </div>
          <div style="text-align:right;flex-shrink:0;">
            <div class="price-demo-bp">950.00</div>
            <div style="font-size:10px;color:#6ee7b7;">Green</div>
          </div>
        </div>
        <hr style="margin:16px 0 12px;">
        <div style="background:#f8fafc;border-radius:8px;padding:12px 14px;font-size:12px;color:#475569;">
          <strong><i class="ri-lightbulb-line me-1 text-warning"></i>Tip:</strong>
          To set a branch-specific price, open <strong>Edit</strong> and choose <em>This branch only</em> then save.
          It will show in <span style="color:#1d4ed8;font-weight:700">blue</span>;
          prices using the base default show in <span style="color:#059669;font-weight:700">green</span>.
        </div>
      </div>
      <div class="modal-footer" style="padding:10px 20px 14px;">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     DOWNLOAD MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue">
      <h5 class="modal-title mh-title"><i class="ri-download-line"></i> Download Branch Products</h5>
      <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <p class="mb-2" style="font-size:13px;">Click a button to download branch product data.</p>
      <div class="buttons"></div>
    </div>
  </div></div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     INFO MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content"
       style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue">
      <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Branch Products</h5>
      <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="padding:18px 20px;">
      <p class="mb-2"><strong>What are Branch Products?</strong><br>
      Branch products are base catalogue items <em>assigned to a specific branch</em>. Each branch can have its own selling price, stock quantity, reorder points, and barcode.</p>
      <hr class="my-3">
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <tbody>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;width:140px;border-bottom:1px solid #f1f5f9">Selling Price</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">The price this branch charges customers. Can differ from the base product default.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9">Cost Price</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">What this branch paid the supplier.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9">Stock Qty</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9"><span style="color:#dc2626;font-weight:600">Red = zero</span>, <span style="color:#d97706;font-weight:600">amber = at/below reorder point</span>, <span style="color:#16a34a;font-weight:600">green = healthy</span>.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9">Reorder Point</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">When stock falls to or below this level a low-stock alert is triggered.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569">Track Stock</td><td style="padding:8px 12px">When enabled, sales decrement the stock quantity.</td></tr>
        </tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
    </div>
  </div></div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     ADD PRODUCT MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="addProductModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">

      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title">
          <i class="ri-add-circle-line"></i> Add Product to Branch
          @if($selectedBranch)
            <span style="font-size:12px;font-weight:400;opacity:.85">— {{ $selectedBranch->name }}</span>
          @endif
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>

      <ul class="nav nav-tabs border-bottom px-2 pt-2" role="tablist" style="font-size:12px;flex-wrap:nowrap;">
        <li class="nav-item">
          <button class="nav-link active px-3 py-1" data-bs-toggle="tab" data-bs-target="#at1" type="button">
            <i class="ri-search-line me-1"></i>Search Products
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link px-3 py-1" data-bs-toggle="tab" data-bs-target="#at2" type="button">
            <i class="ri-add-line me-1"></i>New Product
          </button>
        </li>
      </ul>

      <div class="modal-body" style="padding:14px 18px 10px !important;">
        <div class="tab-content">

          {{-- ── Tab 1: Search existing base products ─────────────────── --}}
          <div class="tab-pane fade show active" id="at1" role="tabpanel">
            <div class="mb-2">
              <label class="form-label fw-semibold" style="font-size:13px">
                <i class="ri-search-line me-1 text-success"></i>Search Products
              </label>
              <input type="text" class="form-control" id="baseProductSearch"
                     placeholder="Type product name or code…" autocomplete="off" />
              <div class="form-text" style="font-size:11px;">
                <i class="ri-keyboard-line me-1"></i>
                Type to search · Tab to qty · Enter to add — no mouse needed.
              </div>
            </div>
            <div id="searchResultList" class="search-result-list"></div>
          </div>

          {{-- ── Tab 2: Create new base product + assign to branch ────── --}}
          <div class="tab-pane fade" id="at2" role="tabpanel">
            <div class="alert alert-info border-0 py-2 px-3 mb-3" style="font-size:12px;border-radius:6px;">
              <i class="ri-information-line me-1"></i>
              Creates a new product in the <strong>base catalogue</strong> and assigns it to
              <strong>{{ $selectedBranch->name ?? 'this branch' }}</strong>.
            </div>
            <div class="mb-2">
              <label class="form-label fw-semibold" style="font-size:13px">
                Product Name <span class="text-danger">*</span>
              </label>
              <input class="form-control form-control-sm" type="text" id="new-name"
                     placeholder="e.g. Cooking Oil 2L" autocomplete="off" />
            </div>
            <div class="row g-2 mb-2">
              <div class="col-6">
                <label class="form-label fw-semibold" style="font-size:12px">Product Code</label>
                <input class="form-control form-control-sm" type="text" id="new-code"
                       placeholder="e.g. OIL-001" autocomplete="off" />
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold" style="font-size:12px">Unit of Measure</label>
                <input class="form-control form-control-sm" type="text" id="new-unit"
                       placeholder="Each, kg, Litre…" value="Each" autocomplete="off" />
              </div>
            </div>
            <div class="mb-2">
              <label class="form-label fw-semibold" style="font-size:12px">Supplier</label>
              <select class="form-select form-select-sm" id="new-supplier">
                <option value="">— Select Supplier —</option>
                @foreach($suppliers as $sup)
                  <option value="{{ $sup }}">{{ $sup }}</option>
                @endforeach
              </select>
            </div>
            <div class="row g-2 mb-2">
              <div class="col-4">
                <label class="form-label fw-semibold" style="font-size:12px">
                  Selling Price (MWK) <span class="text-danger">*</span>
                  <span class="badge bg-success ms-1" style="font-size:9px;font-weight:600;">Catalogue default</span>
                </label>
                <input class="form-control form-control-sm" type="number"
                       step="0.01" min="0" id="new-selling-price" placeholder="0.00" />
              </div>
              <div class="col-4">
                <label class="form-label fw-semibold" style="font-size:12px">
                  Cost Price (MWK)
                  <span class="badge bg-success ms-1" style="font-size:9px;font-weight:600;">Catalogue default</span>
                </label>
                <input class="form-control form-control-sm" type="number"
                       step="0.01" min="0" id="new-cost-price" placeholder="0.00" />
              </div>
              <div class="col-4">
                <label class="form-label fw-semibold" style="font-size:12px">Opening Stock</label>
                <input class="form-control form-control-sm" type="number"
                       step="0.001" min="0" id="new-stock-qty" placeholder="0" value="0" />
              </div>
            </div>
            <div class="d-flex justify-content-end mt-3 gap-2">
              <a href="#" class="btn btn-secondary btn-sm" id="cancelAddBtn">
                <i class="ri-close-line"></i> Close
              </a>
              <a href="#" class="btn btn-success btn-sm" id="submitAddBtn">
                <i class="ri-check-line"></i> Save to Catalogue &amp; Branch
              </a>
            </div>
            <div id="addSuccessNotice" class="mt-2" style="font-size:12px;color:#198754;display:none;">
              <i class="ri-check-double-line me-1"></i><span id="addSuccessText"></span>
            </div>
          </div>

        </div>
      </div>

      <div class="modal-footer" style="padding:10px 18px 14px;">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
          <i class="ri-close-line"></i> Close
        </button>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     VIEW PRODUCT MODAL
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
          <i class="ri-information-line me-1"></i>
          <span id="vw-price-notice-text"></span>
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
              <div class="view-item"><label>Reorder Quantity</label><div class="view-val" id="vw-reorder-qty"></div></div>
              <div class="view-item"><label>Max Stock</label><div class="view-val" id="vw-max"></div></div>
              <div class="view-item"><label>Primary Barcode</label><div class="view-val" id="vw-barcode"></div></div>
              <div class="view-item"><label>Batch / Lot Number</label><div class="view-val" id="vw-batch"></div></div>
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
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <a href="#" class="btn btn-primary btn-sm" id="vwEditBtn">
          <i class="ri-edit-box-line me-1"></i> Edit
        </a>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     EDIT MODAL — Tab 1: Branch Product  |  Tab 2: Base Product Info
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">

      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title">
          <i class="ri-edit-box-line"></i> Edit — <span id="editModalName"></span>
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>

      {{-- ── Improved tab navigation ─────────────────────────────────────── --}}
      <ul class="nav" id="editModalTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="tab-branch-lnk"
                  data-bs-toggle="tab" data-bs-target="#tab-branch"
                  type="button" role="tab">
            <i class="ri-store-2-line"></i>
            Branch product
            <span class="edit-tab-badge" id="editTabBranchBadge">{{ $selectedBranch->name ?? 'Branch' }}</span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-base-lnk"
                  data-bs-toggle="tab" data-bs-target="#tab-base"
                  type="button" role="tab">
            <i class="ri-database-line"></i>
            Base product info
            <span class="edit-tab-badge">Catalogue</span>
          </button>
        </li>
      </ul>

      <div class="modal-body" style="padding:14px 18px 10px !important;">
        <div class="tab-content">

          {{-- ════ TAB 1 — Branch-level fields ════ --}}
          <div class="tab-pane fade show active" id="tab-branch" role="tabpanel">
            <form id="editDataForm">
              @csrf
              <input type="hidden" id="editId">
              <input type="hidden" id="editRow">
              <input type="hidden" id="editBaseProductId">

              {{-- Read-only product identity --}}
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

              {{-- Subtle cross-link to base tab --}}
              <a href="#" class="edit-tab-crosslink mb-2" id="goToBaseTabLink">
                <i class="ri-arrow-right-s-line"></i>
                Edit name, unit or code in the Base product info tab
              </a>

              <hr style="border:none;border-top:0.5px solid #e9ecef;margin:10px 0 12px;">

              {{-- ── Selling Price ───────────────────────────────────────── --}}
              <div class="edit-section-title"><i class="ri-coin-line me-1"></i>Selling Price</div>

              <div class="price-source-toggle">

                {{-- Card A: Base catalogue --}}
                <div class="price-source-card active-base" id="priceSourceBase" onclick="setPriceSource('base')">
                  <div class="psc-label">
                    <span class="psc-dot" id="dotBase" style="background:#059669;"></span>
                    Base catalogue
                  </div>
                  <div class="psc-value" id="pscBaseVal">—</div>
                  <div class="psc-desc">Inherited · all branches</div>
                </div>

                {{-- Card B: Branch override --}}
                <div class="price-source-card" id="priceSourceBranch" onclick="setPriceSource('branch')">
                  <div class="psc-label">
                    <span class="psc-dot" id="dotBranch" style="background:#1d4ed8;opacity:.3;"></span>
                    This branch only
                  </div>
                  <div class="psc-value" id="pscBranchVal" style="color:#9ca3af;">—</div>
                  <div class="psc-desc">Override for this branch</div>
                </div>

              </div>

              {{-- Contextual hint — updates when toggle changes --}}
              <div class="price-context-hint pch-base" id="priceContextHint">
                <i class="ri-information-line" style="font-size:13px;flex-shrink:0;margin-top:1px;"></i>
                <span id="priceContextHintText"></span>
              </div>

              {{-- Branch price inputs — shown only when branch override selected --}}
              <div id="branchPriceFields" style="display:none;">
                <div class="row g-2 mb-2">
                  <div class="col-6">
                    <label class="form-label fw-semibold" style="font-size:12px">Selling Price <span class="text-danger">*</span> <small class="text-muted">(MWK)</small></label>
                    <input class="form-control form-control-sm" type="number" step="0.01" min="0" id="editSellPrice" placeholder="0.00" />
                  </div>
                  <div class="col-6">
                    <label class="form-label fw-semibold" style="font-size:12px">Cost Price <small class="text-muted">(MWK)</small></label>
                    <input class="form-control form-control-sm" type="number" step="0.01" min="0" id="editCostPrice" placeholder="0.00" />
                  </div>
                </div>
              </div>

              <hr style="border:none;border-top:0.5px solid #e9ecef;margin:10px 0 12px;">

              {{-- Stock --}}
              <div class="edit-section-title"><i class="ri-stack-line me-1"></i>Stock</div>
              <div class="row g-2 mb-2">
                <div class="col-3">
                  <label class="form-label fw-semibold" style="font-size:12px">Stock Qty</label>
                  <input class="form-control form-control-sm" type="number" step="0.001" id="editStockQty" />
                </div>
                <div class="col-3">
                  <label class="form-label fw-semibold" style="font-size:12px">Reorder Point</label>
                  <input class="form-control form-control-sm" type="number" step="0.001" min="0" id="editReorderPoint" />
                </div>
                <div class="col-3">
                  <label class="form-label fw-semibold" style="font-size:12px">Reorder Qty</label>
                  <input class="form-control form-control-sm" type="number" step="0.001" min="0" id="editReorderQty" />
                </div>
                <div class="col-3">
                  <label class="form-label fw-semibold" style="font-size:12px">Max Stock</label>
                  <input class="form-control form-control-sm" type="number" step="0.001" min="0" id="editMaxStock" />
                </div>
              </div>

              <hr style="border:none;border-top:0.5px solid #e9ecef;margin:10px 0 12px;">

              {{-- Barcode & Batch --}}
              <div class="edit-section-title"><i class="ri-qr-code-line me-1"></i>Barcode &amp; Batch</div>
              <div class="row g-2 mb-2">
                <div class="col-4">
                  <label class="form-label fw-semibold" style="font-size:12px">Primary Barcode</label>
                  <input class="form-control form-control-sm" type="text" id="editBarcode" autocomplete="off" />
                </div>
                <div class="col-4">
                  <label class="form-label fw-semibold" style="font-size:12px">Batch / Lot Number</label>
                  <input class="form-control form-control-sm" type="text" id="editBatch" autocomplete="off" />
                </div>
                <div class="col-4">
                  <label class="form-label fw-semibold" style="font-size:12px">Expiry Date</label>
                  <input class="form-control form-control-sm" type="date" id="editExpiry" />
                </div>
              </div>

              <hr style="border:none;border-top:0.5px solid #e9ecef;margin:10px 0 12px;">

              {{-- Settings --}}
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
                    <label class="form-check-label" for="editAllowNeg" style="font-size:12px">Allow negative stock</label>
                  </div>
                </div>
                <div class="col-4">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="editIsActive">
                    <label class="form-check-label" for="editIsActive" style="font-size:12px">Active at this branch</label>
                  </div>
                </div>
              </div>
            </form>
          </div>{{-- end tab-branch --}}

          {{-- ════ TAB 2 — Base product catalogue fields ════ --}}
          <div class="tab-pane fade" id="tab-base" role="tabpanel">

            <div class="bp-edit-warning">
              <i class="ri-alert-line" style="font-size:14px;flex-shrink:0;margin-top:1px;"></i>
              Changes here update the <strong>base catalogue</strong> and will affect
              <strong>all branches</strong> that use this product.
              Branch-specific overrides (prices, stock) remain managed in the Branch product tab.
            </div>

            <form id="editBaseProductForm">
              @csrf
              <input type="hidden" id="bpEditId">

              <div class="mb-2">
                <label class="form-label fw-semibold" style="font-size:12px">
                  Product Name <span class="text-danger">*</span>
                </label>
                <input class="form-control form-control-sm" type="text" id="bpEditName"
                       placeholder="e.g. Cooking Oil 2L" autocomplete="off" />
              </div>

              <div class="row g-2 mb-2">
                <div class="col-6">
                  <label class="form-label fw-semibold" style="font-size:12px">Product Code</label>
                  <input class="form-control form-control-sm" type="text" id="bpEditCode"
                         placeholder="e.g. OIL-001" autocomplete="off" />
                </div>
                <div class="col-6">
                  <label class="form-label fw-semibold" style="font-size:12px">Unit of Measure</label>
                  <input class="form-control form-control-sm" type="text" id="bpEditUnit"
                         placeholder="Each, kg, Litre…" autocomplete="off" />
                </div>
              </div>

              <div class="mb-2">
                <label class="form-label fw-semibold" style="font-size:12px">Supplier</label>
                <select class="form-select form-select-sm" id="bpEditSupplier">
                  <option value="">— Select Supplier —</option>
                  @foreach($suppliers as $sup)
                    <option value="{{ $sup }}">{{ $sup }}</option>
                  @endforeach
                </select>
              </div>

              <div class="row g-2 mb-2">
                <div class="col-6">
                  <label class="form-label fw-semibold" style="font-size:12px">
                    Selling Price (MWK) <span class="text-danger">*</span>
                    <span class="badge bg-success ms-1" style="font-size:9px;">Catalogue default</span>
                  </label>
                  <input class="form-control form-control-sm" type="number" step="0.01" min="0"
                         id="bpEditSellPrice" placeholder="0.00" />
                </div>
                <div class="col-6">
                  <label class="form-label fw-semibold" style="font-size:12px">
                    Cost Price (MWK)
                    <span class="badge bg-success ms-1" style="font-size:9px;">Catalogue default</span>
                  </label>
                  <input class="form-control form-control-sm" type="number" step="0.01" min="0"
                         id="bpEditCostPrice" placeholder="0.00" />
                </div>
              </div>

              <div class="alert border-0 py-2 px-3 mb-0"
                   style="background:#ecfdf5;border-left:2px solid #059669;border-radius:0 5px 5px 0;font-size:11px;color:#065f46;">
                <i class="ri-information-line me-1"></i>
                These are the <strong>catalogue defaults</strong> shown in
                <span style="color:#059669;font-weight:700;">green</span> for branches without a price override.
              </div>
            </form>

          </div>{{-- end tab-base --}}

        </div>{{-- end tab-content --}}
      </div>{{-- end modal-body --}}

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

{{-- ══════════════════════════════════════════════════════════════════════
     SINGLE DELETE MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="deleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-danger">
        <h5 class="modal-title mh-title"><i class="ri-delete-bin-line"></i> Remove Product from Branch</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center py-4">
        <i class="ri-error-warning-line text-danger" style="font-size:60px"></i>
        <h5 class="mt-2 mb-1">Remove <span id="deleteLabel" class="text-danger"></span>?</h5>
        <p style="font-size:13px;color:#6c757d;margin-bottom:0;">
          This removes it from <strong>{{ $selectedBranch->name ?? 'this branch' }}</strong> only.<br>
          The base product remains in the catalogue.
        </p>
        <input type="hidden" id="deleteId">
        <input type="hidden" id="deleteRow">
      </div>
      <div class="modal-footer justify-content-center gap-2" style="padding:10px 20px 18px;">
        <a href="#" class="btn btn-secondary btn-sm px-4" id="keepBtn">No, Keep it</a>
        <a href="#" class="btn btn-danger btn-sm px-4" id="submitDeleteBtn">Yes, Remove</a>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     BULK ACTIONS MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="bulkActionsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title">
          <i class="ri-checkbox-multiple-line"></i>
          Bulk Actions — <span id="bulkActionsCount">0</span> product(s) selected
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:16px 18px !important;">
        <div class="bulk-section">
          <div class="bulk-section-title"><i class="ri-toggle-line me-1"></i> Status</div>
          <div class="d-flex gap-2">
            <a href="#" class="btn btn-sm btn-success flex-fill" id="bulkActivateBtn"><i class="ri-checkbox-circle-line me-1"></i> Activate All</a>
            <a href="#" class="btn btn-sm btn-secondary flex-fill" id="bulkDeactivateBtn"><i class="ri-close-circle-line me-1"></i> Deactivate All</a>
          </div>
        </div>
        <div class="d-grid mt-1">
          <a href="#" class="btn btn-danger" id="bulkDeleteBtn">
            <i class="ri-delete-bin-line me-1"></i> Remove Selected from Branch
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {

    toastr.options = { closeButton:true, progressBar:true, showMethod:'slideDown', timeOut:5000, allowHtml:true };

    // ── Helpers ───────────────────────────────────────────────────────────
    function handleAjaxError(xhr, status) {
        if (status === 'timeout') { toastr.error('The request timed out.', 'Timeout'); }
        else if (xhr.status === 422) {
            var errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : {};
            var msg = ''; $.each(errors, function(k,v) { msg += v + '\n'; });
            toastr.error(msg || 'Validation failed.', 'Validation Errors');
        } else if (xhr.status === 500) { toastr.error('Server error.', 'Server Error'); }
        else { toastr.error('Unspecified error.', 'Error'); }
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

        return `<tr id="${p.row}">
            <td>
                <input type="checkbox" class="selectRow" value="${p.id}" data-row-id="${p.row}">
                &nbsp;${p.name || ''}
            </td>
            <td>${p.code || '—'}</td>
            <td>${p.unit || '—'}</td>
            <td><span class="${sc}">${fmtNum(sq, 0)}</span></td>
            <td><span class="${sellClass}" style="font-size:12px">${fmtNum(p.selling_price)}</span></td>
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
        $('#selectedCount').text(count);
        if (count > 0) { $('#bulkTriggerBtn').show(); $('#bulkBar').addClass('visible'); }
        else           { $('#bulkTriggerBtn').hide(); $('#bulkBar').removeClass('visible'); }
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
            {
                extend: 'pdfHtml5', title: @json($maintableTitle),
                exportOptions: { columns: ':visible:not(:last-child)' },
                customize: function(doc) {
                    doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
                }
            }
        ]
    });
    window._dt = table;
    table.buttons().container().appendTo($('#buttonsModal .buttons'));

    $('#shopValueBtn').on('click',   function(e) { e.preventDefault(); $('#shopValueModal').modal('show'); });
    $('#pricingInfoBtn').on('click', function(e) { e.preventDefault(); $('#pricingInfoModal').modal('show'); });

    // ════════════════════════════════════════════════════════════════════════
    //  ADD PRODUCT — search tab
    // ════════════════════════════════════════════════════════════════════════
    var allBaseProducts = [];

    function loadBaseProducts() {
        if (allBaseProducts.length) return;
        $.ajax({
            type: 'GET',
            url:  '{{ route("retail.operations.baseproducts.search") }}',
            data: { branch_id: {{ $selectedBranch->id ?? 0 }} },
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
            var badge = [p.unit, priceDisp].filter(Boolean).join(' / ');
            var addedText  = _addedMap[p.id] || '';
            var wasAdded   = addedText !== '';
            var btnDisabled = wasAdded ? 'disabled' : '';
            var msgDisplay  = wasAdded ? 'flex' : 'none';

            html += `
            <div class="search-result-item" data-id="${p.id}">
                <div class="sri-row">
                    <div class="sri-name" title="${p.name}">${nameHl}${codeStr}</div>
                    ${badge ? `<span class="sri-meta">${badge}</span>` : ''}
                    <input type="number" class="sri-qty-input" id="sri_qty_${p.id}"
                           placeholder="Qty" step="0.001" min="0" value="0" ${btnDisabled}
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
            toastr.warning('Enter a valid quantity (0 or more).', 'Required');
            $('#sri_qty_' + pid).focus();
            return;
        }
        var btn      = $('#sri_btn_' + pid);
        var qtyInput = $('#sri_qty_' + pid);
        btn.prop('disabled', true);
        qtyInput.prop('disabled', true);

        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:    'POST',
            url:     '{{ route("retail.operations.branchproducts.upsert") }}',
            timeout: 60000,
            data: {
                branch_id:            {{ $selectedBranch->id ?? 0 }},
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
                        if (table.row('#' + data.product.row).length) {
                            table.row('#' + data.product.row).remove();
                        }
                        table.row.add($(buildRow(data.product))).draw(false);
                    }
                    var msg = qty > 0 ? qty + ' added' : '0 added';
                    _addedMap[pid] = msg;
                    $('#sri_msg_text_' + pid).text(msg);
                    $('#sri_msg_' + pid).show();
                } else if (data.status === 422) {
                    btn.prop('disabled', false);
                    qtyInput.prop('disabled', false);
                    toastr.error(data.error || 'Validation failed.', 'Error');
                } else {
                    btn.prop('disabled', false);
                    qtyInput.prop('disabled', false);
                    toastr.info('Unspecified error.', 'Error');
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

    // ── New product (Tab 2) save ──────────────────────────────────────────
    $('#cancelAddBtn').on('click', function(e) {
        e.preventDefault();
        softResetAddModal();
        $('#addProductModal').modal('hide');
    });

    $('#submitAddBtn').on('click', function(e) {
        e.preventDefault();
        var name = $('#new-name').val().trim();
        if (!name) { toastr.warning('Product name is required.', 'Required'); $('#new-name').focus(); return; }
        var sell = $('#new-selling-price').val();
        if (!sell || parseFloat(sell) < 0) { toastr.warning('Selling price is required.', 'Required'); $('#new-selling-price').focus(); return; }

        var self = $(this); self.prop('disabled', true);
        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });

        $.ajax({
            type:    'POST',
            url:     '{{ route("retail.operations.baseproducts.insert") }}',
            timeout: 60000,
            data: {
                name:          name,
                selling_price: sell,
                cost_price:    $('#new-cost-price').val(),
                unit:          $('#new-unit').val() || 'Each',
                code:          $('#new-code').val(),
                supplier:      $('#new-supplier').val(),
                is_product:    1,
                _token:        '{{ csrf_token() }}'
            },
            beforeSend: function() { $('#progressBar').show(); },
            success: function(bpData) {
                if (bpData.status === 201) {
                    $.ajax({
                        type:    'POST',
                        url:     '{{ route("retail.operations.branchproducts.upsert") }}',
                        timeout: 60000,
                        data: {
                            branch_id:            {{ $selectedBranch->id ?? 0 }},
                            base_product_id:      bpData.product.id,
                            stock_quantity:       $('#new-stock-qty').val() || 0,
                            reorder_point:        0,
                            track_stock:          1,
                            allow_negative_stock: 0,
                            is_active:            1,
                            _token:               '{{ csrf_token() }}'
                        },
                        complete: function() { $('#progressBar').hide(); self.prop('disabled', false); },
                        success: function(data) {
                            if (data.status === 201) {
                                toastr.success('Product created in catalogue and added to branch.', 'Success');
                                if (window._dt) {
                                    if (table.row('#' + data.product.row).length) {
                                        table.row('#' + data.product.row).remove();
                                    }
                                    table.row.add($(buildRow(data.product))).draw(false);
                                }
                                allBaseProducts = [];
                                loadBaseProducts();
                                $('#new-name, #new-selling-price, #new-cost-price, #new-code').val('');
                                $('#new-stock-qty').val('0');
                                $('#new-unit').val('Each');
                                $('#addSuccessText').text('"' + name + '" added. Use Edit to set a branch-specific price.');
                                $('#addSuccessNotice').show();
                                $('#new-name').focus();
                            } else {
                                toastr.error(data.error || 'Failed to assign to branch.', 'Error');
                            }
                        },
                        error: function() {
                            $('#progressBar').hide();
                            self.prop('disabled', false);
                            handleAjaxError.apply(this, arguments);
                        }
                    });
                } else {
                    $('#progressBar').hide();
                    self.prop('disabled', false);
                    toastr.error(bpData.error || 'Failed to create product.', 'Error');
                }
            },
            error: function() {
                $('#progressBar').hide();
                self.prop('disabled', false);
                handleAjaxError.apply(this, arguments);
            }
        });
    });

    // ════════════════════════════════════════════════════════════════════════
    //  VIEW
    // ════════════════════════════════════════════════════════════════════════
    var _viewData = {};

    $('#tbody').on('click', '.viewDataBtn', function(e) {
        e.preventDefault();
        var b = $(this);
        _viewData = {
            id:           b.data('id'),
            name:         b.data('name'),
            code:         b.data('code'),
            unit:         b.data('unit'),
            supplier:     b.data('supplier'),
            barcode:      b.data('barcode'),
            batch:        b.data('batch'),
            expiry:       b.data('expiry'),
            cost:         b.data('cost'),
            sell:         b.data('sell'),
            stock:        b.data('stock'),
            reorder:      b.data('reorder'),
            reorderQty:   b.data('reorder-qty'),
            max:          b.data('max'),
            active:       b.data('active'),
            track:        b.data('track'),
            neg:          b.data('neg'),
            sellIsBranch: b.data('sell-is-branch'),
            costIsBranch: b.data('cost-is-branch'),
            bpSell:       b.data('bp-sell'),
            bpCost:       b.data('bp-cost'),
            editRow:      b.closest('tr').attr('id')
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

        var badges = parseInt(_viewData.active) === 1
            ? '<span class="badge bg-success">Active</span>'
            : '<span class="badge bg-danger">Inactive</span>';
        $('#vw-badges').html(badges);

        var noticeParts = [];
        if (parseInt(_viewData.sellIsBranch) === 1) {
            noticeParts.push('Selling price is a <strong>branch-specific override</strong> (shown in blue).');
        } else {
            noticeParts.push('Selling price uses the base product default'
                + (_viewData.bpSell ? ' (MWK ' + parseFloat(_viewData.bpSell).toLocaleString('en-US', {minimumFractionDigits: 2}) + ')' : '')
                + ' (shown in green).');
        }
        if (parseInt(_viewData.costIsBranch) === 1) {
            noticeParts.push('Cost price is a <strong>branch-specific override</strong>.');
        } else {
            noticeParts.push('Cost price uses the base product default.');
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
    //  PRICE SOURCE TOGGLE — card-based, with live value display
    // ════════════════════════════════════════════════════════════════════════
    window._currentPriceSource = 'base';
    window._bpSellStored = '';   // catalogue default price
    window._branchSellStored = ''; // current branch override if any

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
            hintText.innerHTML = 'Using the base catalogue price of <strong>' + bpFmt + '</strong>. To set a different price for this branch, select <em>This branch only</em>.';
        } else {
            cardBranch.className = 'price-source-card active-branch';
            cardBase.className   = 'price-source-card';
            dotBranch.style.opacity = '1';
            dotBase.style.opacity   = '.3';
            fields.style.display    = 'block';
            hint.className = 'price-context-hint pch-branch';
            hintText.innerHTML = 'Enter a price below — overrides the catalogue for <strong>this branch only</strong>. Shown in <span style="color:#1d4ed8;font-weight:700;">blue</span> in the product list.';
            // Seed the input if we had a previously stored branch price
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

    // Live update the "This branch only" card value as user types
    $(document).on('input', '#editSellPrice', function() {
        if (window._currentPriceSource === 'branch') {
            var v = parseFloat($(this).val());
            var branchVal = document.getElementById('pscBranchVal');
            branchVal.style.color = '#1d4ed8';
            branchVal.textContent = isNaN(v) ? '— enter below'
                : 'MWK ' + v.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
        }
    });

    // ════════════════════════════════════════════════════════════════════════
    //  EDIT MODAL — tab switching (swap footer save buttons)
    // ════════════════════════════════════════════════════════════════════════
    $('#tab-branch-lnk').on('shown.bs.tab', function() {
        $('#submitBaseProductBtn').hide();
        $('#submitEditBtn').show();
    });
    $('#tab-base-lnk').on('shown.bs.tab', function() {
        $('#submitEditBtn').hide();
        $('#submitBaseProductBtn').show();
    });

    // Cross-link: click the arrow link → switch to base tab
    $('#goToBaseTabLink').on('click', function(e) {
        e.preventDefault();
        $('#tab-base-lnk').tab('show');
    });

    // ════════════════════════════════════════════════════════════════════════
    //  EDIT — open modal and populate both tabs
    // ════════════════════════════════════════════════════════════════════════
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

        // Store base price globally so toggle can reference it
        window._bpSellStored     = bpSell;
        window._branchSellStored = sellIsBr ? (b.data('sell') || '') : '';

        // Hidden IDs
        $('#editId').val(b.data('id'));
        $('#editRow').val(b.data('row'));
        $('#editBaseProductId').val(bpId);
        $('#bpEditId').val(bpId);

        $('#editModalName').text(nm);

        // ── Tab 1: branch product ──────────────────────────────────────────
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

        // Populate the base catalogue price card value
        var bpFmt = bpSell
            ? 'MWK ' + parseFloat(bpSell).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2})
            : '—';
        document.getElementById('pscBaseVal').textContent = bpFmt;

        // Set price toggle state
        if (sellIsBr) {
            setPriceSource('branch');
            $('#editSellPrice').val(b.data('sell'));
            $('#editCostPrice').val(b.data('cost'));
        } else {
            setPriceSource('base');
            $('#editSellPrice').val('');
            $('#editCostPrice').val('');
        }

        // ── Tab 2: base product catalogue ─────────────────────────────────
        $('#bpEditName').val(nm);
        $('#bpEditUnit').val(unit !== '—' ? unit : '');
        $('#bpEditCode').val(code !== '—' ? code : '');
        $('#bpEditSupplier').val(supplier);
        $('#bpEditSellPrice').val(bpSell);
        $('#bpEditCostPrice').val(bpCost);

        // Reset to first tab and show correct footer button
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

    // ════════════════════════════════════════════════════════════════════════
    //  SUBMIT — Branch product update (Tab 1)
    // ════════════════════════════════════════════════════════════════════════
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
            type:    'POST',
            url:     '{{ route("retail.operations.branchproducts.update") }}',
            timeout: 60000,
            data: {
                id:                   $('#editId').val(),
                selling_price:        sell,
                cost_price:           cost,
                stock_quantity:       $('#editStockQty').val(),
                reorder_point:        $('#editReorderPoint').val(),
                reorder_quantity:     $('#editReorderQty').val(),
                max_stock:            $('#editMaxStock').val(),
                primary_barcode:      $('#editBarcode').val(),
                batch_number:         $('#editBatch').val(),
                expiry_date:          $('#editExpiry').val(),
                track_stock:          $('#editTrackStock').prop('checked') ? 1 : 0,
                allow_negative_stock: $('#editAllowNeg').prop('checked')   ? 1 : 0,
                is_active:            $('#editIsActive').prop('checked')   ? 1 : 0,
                _token:               '{{ csrf_token() }}'
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

    // ════════════════════════════════════════════════════════════════════════
    //  SUBMIT — Base product catalogue update (Tab 2)
    // ════════════════════════════════════════════════════════════════════════
    $('#submitBaseProductBtn').on('click', function(e) {
        e.preventDefault();

        var name = $('#bpEditName').val().trim();
        if (!name) { toastr.warning('Product name is required.', 'Required'); $('#bpEditName').focus(); return; }
        var sell = $('#bpEditSellPrice').val();
        if (!sell || parseFloat(sell) < 0) { toastr.warning('Selling price is required.', 'Required'); $('#bpEditSellPrice').focus(); return; }

        var self = $(this); self.prop('disabled', true);

        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:    'POST',
            url:     '{{ route("retail.operations.baseproducts.update") }}',
            timeout: 60000,
            data: {
                id:            $('#bpEditId').val(),
                name:          name,
                unit:          $('#bpEditUnit').val(),
                code:          $('#bpEditCode').val(),
                supplier:      $('#bpEditSupplier').val(),
                selling_price: sell,
                cost_price:    $('#bpEditCostPrice').val(),
                _token:        '{{ csrf_token() }}'
            },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success || 'Base product updated successfully.', 'Success');

                    // Mirror changes back to the read-only fields in Tab 1
                    $('#edit-ro-name').val(name);
                    $('#edit-ro-unit').val($('#bpEditUnit').val());
                    $('#edit-ro-code').val($('#bpEditCode').val());
                    $('#editModalName').text(name);

                    // Update the base price card with new catalogue value
                    window._bpSellStored = sell;
                    var newFmt = 'MWK ' + parseFloat(sell).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
                    document.getElementById('pscBaseVal').textContent = newFmt;

                    // Refresh DataTable row if controller returns updated product data
                    if (data.product) {
                        var row = $('#editRow').val();
                        if (table.row('#' + row).length) {
                            table.row('#' + row).remove();
                            table.row.add($(buildRow(data.product))).draw(false);
                        }
                    }

                    // Bust the search cache
                    allBaseProducts = [];
                    loadBaseProducts();

                    // Switch back to the branch product tab
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
    //  DELETE
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
        toastr.info('Your data is safe', 'Great!');
        $('#deleteModal').modal('hide');
    });

    $('#submitDeleteBtn').on('click', function(e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        var row  = $('#deleteRow').val();
        var id   = $('#deleteId').val();
        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:    'POST',
            url:     '{{ route("retail.operations.branchproducts.delete") }}',
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
    //  BULK ACTIONS
    // ════════════════════════════════════════════════════════════════════════
    $('#selectAll').on('click', function() { $('.selectRow').prop('checked', this.checked); updateSelectedCount(); });
    $('#tbody').on('click', '.selectRow', function() { updateSelectedCount(); });

    $('#bulkTriggerBtn').on('click', function(e) {
        e.preventDefault();
        $('#bulkActionsCount').text($('.selectRow:checked').length);
        $('#bulkActionsModal').modal('show');
    });

    function getSelectedIds()  { var ids  = []; $('.selectRow:checked').each(function() { ids.push($(this).val()); });           return ids;  }
    function getSelectedRows() { var rows = []; $('.selectRow:checked').each(function() { rows.push($(this).data('row-id')); }); return rows; }

    function doBulkStatus(isActive) {
        var ids = getSelectedIds();
        if (!ids.length) return;
        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:    'POST',
            url:     '{{ route("retail.operations.branchproducts.bulkstatus") }}',
            timeout: 60000,
            data: { ids: ids, is_active: isActive, _token: '{{ csrf_token() }}' },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    $.each(data.products, function(i, p) {
                        table.row('#' + p.row).remove();
                        table.row.add($(buildRow(p)));
                    });
                    table.draw(false);
                    updateSelectedCount();
                    $('#bulkActionsModal').modal('hide');
                } else {
                    toastr.error(data.error || 'Failed.', 'Error');
                }
            },
            error: handleAjaxError
        });
    }
    $('#bulkActivateBtn').on('click',   function(e) { e.preventDefault(); doBulkStatus(1); });
    $('#bulkDeactivateBtn').on('click', function(e) { e.preventDefault(); doBulkStatus(0); });

    $('#bulkDeleteBtn').on('click', function(e) {
        e.preventDefault();
        var ids  = getSelectedIds();
        var rows = getSelectedRows();
        if (!ids.length) { toastr.warning('No products selected.', 'Warning'); return; }
        if (!confirm('Remove ' + ids.length + ' product(s) from this branch? This cannot be undone.')) return;
        $('#bulkActionsModal').modal('hide');
        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:    'POST',
            url:     '{{ route("retail.operations.branchproducts.bulkdelete") }}',
            timeout: 60000,
            data: { ids: ids, _token: '{{ csrf_token() }}' },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    rows.forEach(function(r) { table.row('#' + r).remove(); });
                    table.draw(false);
                    updateSelectedCount();
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
</script>
@endsection
