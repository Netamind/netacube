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
                'bp.internal_code',
                'bp.unit_of_measure',
                'bp.mra_tax_rate_id',
                'bp.category',
                'bp.brand',
                'bp.default_selling_price as bp_sell',
                'bp.default_cost_price    as bp_cost',
                'bp.is_product',
                'bp.is_vat_exempt_by_nature'
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
            ->where('is_active', 1)
            ->get();
    }

    $suppliers  = DB::connection('tenant')->table('retail_base_products')
                    ->whereNotNull('supplier')->where('supplier', '!=', '')
                    ->distinct()->orderBy('supplier')->pluck('supplier');

    $maintableTitle = 'Branch Products — ' . ($selectedBranch->name ?? 'All');
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

/* ── Header action buttons ───────────────────────────────────────────────── */
.hdr-btn {
  height: 30px; padding: 0 10px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 6px; border: none; cursor: pointer;
  font-size: 15px; transition: opacity .15s, transform .1s;
  text-decoration: none;
}
.hdr-btn:hover { opacity: .85; transform: translateY(-1px); }

/* Shop Value — amber/gold */
.hdr-btn-shopvalue {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: #fff !important;
  box-shadow: 0 2px 8px rgba(245,158,11,0.4);
}
/* Add product — green */
.hdr-btn-add {
  background: linear-gradient(135deg, #10b981, #059669);
  color: #fff !important;
  box-shadow: 0 2px 8px rgba(16,185,129,0.4);
}
/* Pricing info — soft teal */
.hdr-btn-pricing {
  background: linear-gradient(135deg, #0ea5e9, #0284c7);
  color: #fff !important;
  box-shadow: 0 2px 8px rgba(14,165,233,0.4);
}
/* Info — indigo */
.hdr-btn-info {
  background: rgba(255,255,255,0.18);
  color: #fff !important;
  border: 1px solid rgba(255,255,255,0.35);
}
/* Download — light */
.hdr-btn-download {
  background: rgba(255,255,255,0.18);
  color: #fff !important;
  border: 1px solid rgba(255,255,255,0.35);
}

/* ── Bulk bar ────────────────────────────────────────────────────────────── */
#bulkBar {
  background: #eef0f7;
  border-bottom: 1px solid #d6daf0;
  padding: 7px 1.5rem;
  display: none;
  align-items: center;
  justify-content: space-between;
}
#bulkBar.visible { display: flex !important; }

/* Bulk select button — shown only when rows selected */
#bulkTriggerBtn {
  font-size:12px; font-weight:700;
  height:30px; padding:0 14px;
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
.tax-badge  { font-size:11px; padding:2px 8px; border-radius:10px; font-weight:600; letter-spacing:0.5px; }
.price-cell { font-size:12px; font-weight:600; }
.stock-ok   { color: #16a34a; font-weight: 700; }
.stock-low  { color: #d97706; font-weight: 700; }
.stock-zero { color: #dc2626; font-weight: 700; }

/* ── Price source colors ────────────────────────────────────────────────── */
/* Branch-specific override — blue */
.price-branch {
  color: #1d4ed8;
  font-weight: 700;
}
/* Inherited from base product — teal/green */
.price-base {
  color: #059669;
  font-weight: 600;
}

/* ── Price source pill — used only in modals/guides, not in table ───────── */
.price-src {
  display:inline-block; font-size:9px; font-weight:700; letter-spacing:.5px;
  padding:1px 5px; border-radius:4px; vertical-align:middle; margin-left:3px;
  line-height:14px;
}
.price-src-bp { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
.price-src-br { background:#dbeafe; color:#1e40af; border:1px solid #93c5fd; }

/* ── No branch selected banner ──────────────────────────────────────────── */
.no-branch-wrap { padding: 48px 20px; text-align: center; color: #94a3b8; }
.no-branch-wrap i { font-size: 52px; display: block; margin-bottom: 12px; color: #c8d0ed; }
.no-branch-wrap h5 { color: #64748b; font-weight: 600; }

/* ── Modal header helpers ───────────────────────────────────────────────── */
.mh-blue   { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-green  { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-info   { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-orange { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
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

/* ── Add product modal search ───────────────────────────────────────────── */
.search-result-list {
  max-height: 220px; overflow-y: auto;
  border: 1px solid #dee2e6; border-radius: 6px; background: #fff;
  display: none;
}
.search-result-item {
  padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f1f5f9;
  display: flex; align-items: center; justify-content: space-between;
  transition: background .1s;
}
.search-result-item:last-child { border-bottom: none; }
.search-result-item:hover { background: #eef0fa; }
.search-result-item .sr-name  { font-weight: 600; font-size: 13px; color: #1e293b; }
.search-result-item .sr-meta  { font-size: 11px; color: #94a3b8; }
.search-result-item .sr-price { font-size: 12px; font-weight: 700; color: #198754; }

/* ── Inline add rows ─────────────────────────────────────────────────────── */
.inline-add-row {
  display: flex; align-items: center; gap: 8px;
  padding: 7px 12px; border-bottom: 1px solid #f1f5f9; background: #fff;
}
.inline-add-row .ia-name  { flex:1; font-weight:600; font-size:13px; color:#1e293b; }
.inline-add-row .ia-meta  { font-size:11px; color:#94a3b8; }
.inline-add-row .ia-price { font-size:12px; font-weight:700; color:#198754; white-space:nowrap; }
.inline-add-row input.qty-input {
  width:80px; text-align:center; border:1px solid #8c8c8c;
  border-radius:5px; height:30px; font-size:13px;
}
.inline-add-row .btn-more-details {
  font-size:11px; white-space:nowrap; height:30px; padding:0 10px;
  display:flex; align-items:center; gap:4px;
}
.inline-add-row .btn-remove-pending {
  font-size:13px; color:#dc2626; cursor:pointer; padding:0 4px; flex-shrink:0;
}
.more-details-panel {
  background:#f8f9ff; border-top:1px solid #e8ecff;
  padding:10px 14px; display:none;
}

/* ── Bulk section ────────────────────────────────────────────────────────── */
.bulk-section { background:#f8f9fa; border-radius:8px; padding:12px 14px; margin-bottom:12px; }
.bulk-section-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#6c757d; margin-bottom:10px; }

/* ── Pricing explanation modal swatches ─────────────────────────────────── */
.pricing-swatch {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 8px 14px; border-radius: 8px; margin-bottom: 8px; width: 100%;
}
.pricing-swatch-br { background: #eff6ff; border: 1px solid #bfdbfe; }
.pricing-swatch-bp { background: #ecfdf5; border: 1px solid #a7f3d0; }
.pricing-swatch .swatch-dot {
  width: 12px; height: 12px; border-radius: 50%; flex-shrink:0;
}
.swatch-dot-br { background: #1d4ed8; }
.swatch-dot-bp { background: #059669; }
.pricing-swatch .swatch-label { font-size: 13px; font-weight: 600; }
.pricing-swatch .swatch-desc  { font-size: 12px; color: #64748b; margin-top:1px; }
.price-demo-br { color:#1d4ed8; font-weight:700; font-size:13px; }
.price-demo-bp { color:#059669; font-weight:600; font-size:13px; }

/* ── Spinner ─────────────────────────────────────────────────────────────── */
@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
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

    <div class="d-flex align-items-center" style="gap:6px;">
      @if($selectedBranch)
      {{-- Shop Value — gold/amber with store chart icon --}}
      <a href="#" class="hdr-btn hdr-btn-shopvalue" id="shopValueBtn" title="View shop value">
        <i class="ri-funds-line"></i>
      </a>
      @endif

      {{-- Pricing colour guide --}}
      <a href="#" class="hdr-btn hdr-btn-pricing" id="pricingInfoBtn" title="Price colour guide">
        <i class="ri-price-tag-3-line"></i>
      </a>

      <a href="#" class="hdr-btn hdr-btn-add" id="addProductBtn"
         title="Add product to branch" @if(!$selectedBranch) style="pointer-events:none;opacity:.5" @endif>
        <i class="ri-add-circle-line"></i>
      </a>
      <a href="#" class="hdr-btn hdr-btn-info" id="infoBtn" title="About Branch Products">
        <i class="ri-information-line"></i>
      </a>
      <a href="#" class="hdr-btn hdr-btn-download" id="tableButtonsBtn" title="Download">
        <i class="ri-download-line"></i>
      </a>
    </div>
  </div>

  {{-- ── Bulk bar — always rendered when branch selected, hidden until selection ── --}}
  @if($selectedBranch)
  <div id="bulkBar">
    {{-- Left: nothing visible until rows selected (bulk trigger shown via JS) --}}
    <div id="bulkLeft">
      <button type="button" id="bulkTriggerBtn" style="display:none;" title="Open bulk actions">
        <i class="ri-checkbox-multiple-line"></i>
        <span id="selectedCount">0</span>&nbsp;Selected
      </button>
    </div>
    {{-- Right: spacer --}}
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
          <th>Cost Price</th>
          <th>Sell Price</th>
          <th>Stock</th>
          <th>VAT</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="tbody">
        @foreach($branchProducts as $bp)
          @php
            $row    = 'row' . $bp->id;
            $taxId  = $bp->mra_tax_rate_id_override ?: $bp->mra_tax_rate_id;
            $sq     = (float)$bp->stock_quantity;
            $rp     = (float)$bp->reorder_point;
            $stockClass = $sq <= 0 ? 'stock-zero' : ($sq <= $rp ? 'stock-low' : 'stock-ok');
            $hasBranchSell = ($bp->selling_price !== null && $bp->selling_price != $bp->bp_sell);
            $hasBranchCost = ($bp->cost_price !== null && $bp->cost_price != $bp->bp_cost);
          @endphp
          <tr id="{{ $row }}">
            <td>
              <input type="checkbox" class="selectRow" value="{{ $bp->id }}" data-row-id="{{ $row }}">
              &nbsp;{{ $bp->name }}
              @if($bp->brand)<small class="text-muted">· {{ $bp->brand }}</small>@endif
            </td>
            <td>{{ $bp->internal_code ?? '—' }}</td>
            <td>{{ $bp->unit_of_measure }}</td>
            <td>
              @if($bp->cost_price !== null)
                <span class="{{ $hasBranchCost ? 'price-branch' : 'price-base' }}" style="font-size:12px">
                  {{ number_format($bp->cost_price, 2) }}
                </span>
                <span class="price-src {{ $hasBranchCost ? 'price-src-br' : 'price-src-bp' }}">{{ $hasBranchCost ? 'BR' : 'BP' }}</span>
              @else<span class="text-muted" style="font-size:12px">—</span>@endif
            </td>
            <td>
              <span class="{{ $hasBranchSell ? 'price-branch' : 'price-base' }}" style="font-size:12px">
                {{ number_format($bp->selling_price, 2) }}
              </span>
              <span class="price-src {{ $hasBranchSell ? 'price-src-br' : 'price-src-bp' }}">{{ $hasBranchSell ? 'BR' : 'BP' }}</span>
            </td>
            <td><span class="{{ $stockClass }}">{{ number_format($sq, 0) }}</span></td>
            <td>
              @if($taxId)
                <span class="badge tax-badge
                  @if($taxId==='A') bg-danger
                  @elseif($taxId==='B') bg-warning text-dark
                  @elseif($taxId==='C') bg-info text-dark
                  @elseif($taxId==='E') bg-secondary
                  @elseif($taxId==='TL') bg-warning text-dark
                  @else bg-info @endif">{{ $taxId }}</span>
              @else<span class="text-muted" style="font-size:12px">—</span>@endif
            </td>
            <td>
              @if($bp->is_active)
                <span class="badge bg-success" style="font-size:11px">Active</span>
              @else
                <span class="badge bg-danger" style="font-size:11px">Inactive</span>
              @endif
            </td>
            <td>
              <a href="#" class="viewDataBtn"
                 data-id="{{ $bp->id }}" data-name="{{ $bp->name }}"
                 data-code="{{ $bp->internal_code }}" data-unit="{{ $bp->unit_of_measure }}"
                 data-brand="{{ $bp->brand }}" data-barcode="{{ $bp->primary_barcode }}"
                 data-batch="{{ $bp->batch_number }}" data-expiry="{{ $bp->expiry_date }}"
                 data-cost="{{ $bp->cost_price }}" data-sell="{{ $bp->selling_price }}"
                 data-wholesale="{{ $bp->wholesale_price }}" data-stock="{{ $bp->stock_quantity }}"
                 data-reorder="{{ $bp->reorder_point }}" data-reorder-qty="{{ $bp->reorder_quantity }}"
                 data-max="{{ $bp->max_stock }}" data-tax="{{ $taxId }}"
                 data-tax-override="{{ $bp->mra_tax_rate_id_override }}"
                 data-active="{{ $bp->is_active }}" data-track="{{ $bp->track_stock }}"
                 data-neg="{{ $bp->allow_negative_stock }}" data-pinned="{{ $bp->is_pinned_on_pos }}"
                 data-branch-sell="{{ $hasBranchSell ? 1 : 0 }}"
                 data-branch-cost="{{ $hasBranchCost ? 1 : 0 }}"
                 data-bp-sell="{{ $bp->bp_sell }}" data-bp-cost="{{ $bp->bp_cost }}">
                <i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px"></i>
              </a>
              <a href="#" class="editDataBtn"
                 data-id="{{ $bp->id }}" data-row="{{ $row }}" data-name="{{ $bp->name }}"
                 data-unit="{{ $bp->unit_of_measure }}"
                 data-barcode="{{ $bp->primary_barcode }}" data-batch="{{ $bp->batch_number }}"
                 data-expiry="{{ $bp->expiry_date }}" data-cost="{{ $bp->cost_price }}"
                 data-sell="{{ $bp->selling_price }}" data-wholesale="{{ $bp->wholesale_price }}"
                 data-stock="{{ $bp->stock_quantity }}" data-reorder="{{ $bp->reorder_point }}"
                 data-reorder-qty="{{ $bp->reorder_quantity }}" data-max="{{ $bp->max_stock }}"
                 data-tax-override="{{ $bp->mra_tax_rate_id_override }}"
                 data-active="{{ $bp->is_active }}" data-track="{{ $bp->track_stock }}"
                 data-neg="{{ $bp->allow_negative_stock }}" data-pinned="{{ $bp->is_pinned_on_pos }}"
                 data-sort="{{ $bp->pos_sort_order }}">
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
      <div class="modal-header" style="background:linear-gradient(135deg,#f59e0b,#d97706);padding:14px 18px !important;border-bottom:none;border-radius:8px 8px 0 0;">
        <h5 class="modal-title mh-title"><i class="ri-funds-line"></i> Branch Details &amp; Shop Value</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:18px 20px !important;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
          <tbody>
            <tr>
              <td style="padding:7px 0;color:#6c757d;font-weight:600;width:120px;">Branch</td>
              <td style="padding:7px 0;font-weight:700;color:#1e293b;">{{ $selectedBranch->name ?? '—' }}</td>
            </tr>
            <tr>
              <td style="padding:7px 0;color:#6c757d;font-weight:600;">Category</td>
              <td style="padding:7px 0;font-weight:700;color:#1e293b;">{{ $branchCategory->category ?? '—' }}</td>
            </tr>
            <tr>
              <td style="padding:7px 0;color:#6c757d;font-weight:600;">Products</td>
              <td style="padding:7px 0;font-weight:700;color:#1e293b;">{{ $branchProducts->count() }}</td>
            </tr>
            <tr style="border-top:1px solid #e9ecef;">
              <td style="padding:10px 0 4px;color:#6c757d;font-weight:600;">Shop Value</td>
              <td style="padding:10px 0 4px;font-size:20px;font-weight:800;color:#d97706;">
                MWK {{ number_format($shopValue, 0) }}
              </td>
            </tr>
            <tr>
              <td style="padding:4px 0;color:#6c757d;font-weight:600;">Date</td>
              <td style="padding:4px 0;color:#64748b;">{{ now()->toDateString() }}</td>
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
          Each product's selling and cost price can come from two sources. The colour and tag on each price cell tells you at a glance which source applies.
        </p>

        {{-- Branch override swatch --}}
        <div class="pricing-swatch pricing-swatch-br">
          <span class="swatch-dot swatch-dot-br"></span>
          <div class="flex-fill">
            <div class="swatch-label" style="color:#1d4ed8;">
              <span class="price-src price-src-br me-1">BR</span> Branch Override
            </div>
            <div class="swatch-desc">This price was explicitly set for <strong>this branch</strong>. It overrides whatever the base catalogue says.</div>
          </div>
          <div style="text-align:right;flex-shrink:0;">
            <div class="price-demo-br">1,250.00</div>
            <div style="font-size:10px;color:#93c5fd;">Blue</div>
          </div>
        </div>

        {{-- Base product swatch --}}
        <div class="pricing-swatch pricing-swatch-bp">
          <span class="swatch-dot swatch-dot-bp"></span>
          <div class="flex-fill">
            <div class="swatch-label" style="color:#059669;">
              <span class="price-src price-src-bp me-1">BP</span> Base Product Default
            </div>
            <div class="swatch-desc">No branch price has been set. The system is using the default price from the <strong>base catalogue</strong>.</div>
          </div>
          <div style="text-align:right;flex-shrink:0;">
            <div class="price-demo-bp">950.00</div>
            <div style="font-size:10px;color:#6ee7b7;">Green</div>
          </div>
        </div>

        <hr style="margin:16px 0 12px;">

        <div style="background:#f8fafc;border-radius:8px;padding:12px 14px;font-size:12px;color:#475569;">
          <strong><i class="ri-lightbulb-line me-1 text-warning"></i>Tip:</strong>
          To make a price branch-specific, open the <strong>Edit</strong> modal and save a selling or cost price. From that point it will show in <span style="color:#1d4ed8;font-weight:700;">blue</span> with the <span class="price-src price-src-br">BR</span> tag.
          Clearing a branch price (leaving it blank on edit) reverts to the base default, shown in <span style="color:#059669;font-weight:700;">green</span> with the <span class="price-src price-src-bp">BP</span> tag.
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
      Branch products are your master catalogue products <em>assigned to a specific branch</em>. Each branch can have its own selling price, stock quantity, reorder points, and barcode.</p>

      <hr class="my-3">
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <tbody>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;width:140px;border-bottom:1px solid #f1f5f9">Selling Price</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">The price this branch charges customers.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9">Cost Price</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">What this branch paid the supplier.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9">Stock Qty</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9"><span style="color:#dc2626;font-weight:600">Red = zero</span>, <span style="color:#d97706;font-weight:600">amber = at/below reorder point</span>, <span style="color:#16a34a;font-weight:600">green = healthy</span>.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9">Reorder Point</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">When stock falls to or below this level a low-stock alert is triggered.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9">VAT Override</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">Inherited from the base product. Override only if this branch has a special MRA classification.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569">Track Stock</td><td style="padding:8px 12px">When enabled, the POS decrements stock on each sale.</td></tr>
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
  <div class="modal-dialog modal-lg">
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
            <i class="ri-search-line me-1"></i>Search Base Products
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

          <div class="tab-pane fade show active" id="at1" role="tabpanel">

            <div class="mb-2">
              <label class="form-label fw-semibold" style="font-size:13px">
                <i class="ri-search-line me-1 text-success"></i>Search Products
              </label>
              <input type="text" class="form-control" id="baseProductSearch"
                     placeholder="Type product name or code…" autocomplete="off" />
              <div class="form-text">Search then set quantity. Press <strong>Save to Branch</strong> to add selected items — the modal stays open for more.</div>
            </div>

            <div id="searchResultList" class="search-result-list"></div>

            <div id="pendingItemsWrap" style="display:none; margin-top:8px;">
              <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#4B5EBD;margin-bottom:6px;">
                <i class="ri-list-check-2 me-1"></i> Products to add
              </div>
              <div id="pendingItemsList"></div>
            </div>

          </div>

          <div class="tab-pane fade" id="at2" role="tabpanel">
            <div class="alert alert-info border-0 py-2 px-3 mb-3" style="font-size:12px;border-radius:6px;">
              <i class="ri-information-line me-1"></i>
              This product will be added to the <strong>base catalogue</strong> and immediately assigned to
              <strong>{{ $selectedBranch->name ?? 'this branch' }}</strong>.
              Category <strong>{{ $branchCategory->category ?? 'from branch' }}</strong> is applied automatically.
            </div>

            <div class="mb-2">
              <label class="form-label fw-semibold" style="font-size:13px">Product Name <span class="text-danger">*</span></label>
              <input class="form-control form-control-sm" type="text" id="new-name"
                     placeholder="e.g. Cooking Oil 2L" autocomplete="off" />
            </div>
            <div class="row g-2 mb-2">
              <div class="col-4">
                <label class="form-label fw-semibold" style="font-size:12px">Selling Price (MWK) <span class="text-danger">*</span></label>
                <input class="form-control form-control-sm" type="number" step="0.01" min="0" id="new-selling-price" placeholder="0.00" />
              </div>
              <div class="col-4">
                <label class="form-label fw-semibold" style="font-size:12px">Cost Price (MWK)</label>
                <input class="form-control form-control-sm" type="number" step="0.01" min="0" id="new-cost-price" placeholder="0.00" />
              </div>
              <div class="col-4">
                <label class="form-label fw-semibold" style="font-size:12px">Opening Stock</label>
                <input class="form-control form-control-sm" type="number" step="0.001" min="0" id="new-stock-qty" placeholder="0" value="0" />
              </div>
            </div>
            <div class="row g-2 mb-2">
              <div class="col-6">
                <label class="form-label fw-semibold" style="font-size:12px">Unit of Measure</label>
                <input class="form-control form-control-sm" type="text" id="new-unit-of-measure"
                       list="newUnitOptions" placeholder="Each, kg, Litre…" value="Each" autocomplete="off" />
                <datalist id="newUnitOptions">
                  <option value="Each"><option value="kg"><option value="g">
                  <option value="Litre"><option value="ml"><option value="Box">
                  <option value="Carton"><option value="Pack"><option value="Pair">
                  <option value="Dozen"><option value="Bag"><option value="Bottle">
                  <option value="Metre"><option value="Service">
                </datalist>
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold" style="font-size:12px">Internal Code</label>
                <input class="form-control form-control-sm" type="text" id="new-internal-code"
                       placeholder="e.g. OIL-001" autocomplete="off" />
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
            <div class="mb-2">
              <label class="form-label fw-semibold" style="font-size:12px">VAT Type <span class="text-danger">*</span></label>
              <select class="form-select form-select-sm" id="new-tax-rate">
                <option value="A" selected>A — Standard VAT 17.5% (most products)</option>
                <option value="B">B — Reduced VAT rate</option>
                <option value="C">C — Zero-rated (0%)</option>
                <option value="E">E — VAT Exempt (basic foods, medicine)</option>
                <option value="TL">TL — Tourism Levy 1%</option>
              </select>
            </div>
          </div>

        </div>
      </div>

      <div class="modal-footer" style="padding:10px 18px 14px;justify-content:space-between;gap:8px;">
        <div id="addSuccessNotice" style="font-size:12px;color:#198754;display:none;">
          <i class="ri-check-double-line me-1"></i><span id="addSuccessText"></span>
        </div>
        <div class="d-flex gap-2 ms-auto">
          <a href="#" class="btn btn-secondary btn-sm" id="cancelAddBtn">
            <i class="ri-close-line"></i> Close
          </a>
          <a href="#" class="btn btn-success btn-sm" id="submitAddBtn">
            <i class="ri-check-line"></i> Save to Branch
          </a>
        </div>
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
            <div style="font-size:12px;color:#6c757d" id="vw-code-line"></div>
          </div>
          <div id="vw-badges" class="d-flex gap-2 flex-wrap justify-content-end"></div>
        </div>

        <div id="vw-price-notice" class="mb-3" style="background:#f0f3ff;border-left:3px solid #4B5EBD;border-radius:0 5px 5px 0;padding:7px 12px;font-size:11px;color:#3a4a9a;display:none;">
          <i class="ri-information-line me-1"></i>
          <span id="vw-price-notice-text"></span>
        </div>

        <ul class="nav nav-tabs nav-sm mb-3" role="tablist" style="font-size:12px;">
          <li class="nav-item"><button class="nav-link active py-1 px-2" data-bs-toggle="tab" data-bs-target="#vw-t1"><i class="ri-money-dollar-circle-line me-1"></i>Pricing</button></li>
          <li class="nav-item"><button class="nav-link py-1 px-2" data-bs-toggle="tab" data-bs-target="#vw-t2"><i class="ri-stack-line me-1"></i>Stock</button></li>
          <li class="nav-item"><button class="nav-link py-1 px-2" data-bs-toggle="tab" data-bs-target="#vw-t3"><i class="ri-settings-3-line me-1"></i>Settings</button></li>
        </ul>
        <div class="tab-content">
          <div class="tab-pane fade show active" id="vw-t1">
            <div class="view-grid">
              <div class="view-item">
                <label>Selling Price (MWK) <span id="vw-sell-src-badge"></span></label>
                <div class="view-val price-cell" id="vw-sell"></div>
              </div>
              <div class="view-item">
                <label>Cost Price (MWK) <span id="vw-cost-src-badge"></span></label>
                <div class="view-val" id="vw-cost"></div>
              </div>
              <div class="view-item"><label>Wholesale Price (MWK)</label><div class="view-val" id="vw-wholesale"></div></div>
              <div class="view-item"><label>VAT / Tax Rate</label><div class="view-val" id="vw-tax"></div></div>
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
              <div class="view-item"><label>Pinned on POS</label><div class="view-val" id="vw-pinned"></div></div>
              <div class="view-item"><label>VAT Override</label><div class="view-val" id="vw-tax-override"></div></div>
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
     EDIT MODAL — Tab 1: Product info + Qty + Prices | Tab 2: Stock & Batch | Tab 3: VAT & Settings
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-edit-box-line"></i> Edit Branch Product — <span id="editModalName"></span></h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <ul class="nav nav-tabs border-bottom px-2 pt-2" role="tablist" style="font-size:12px;flex-wrap:nowrap;">
        <li class="nav-item"><button class="nav-link active px-2 py-1" data-bs-toggle="tab" data-bs-target="#et1" type="button"><i class="ri-layout-top-line me-1"></i>Product &amp; Pricing</button></li>
        <li class="nav-item"><button class="nav-link px-2 py-1"        data-bs-toggle="tab" data-bs-target="#et2" type="button"><i class="ri-stack-line me-1"></i>Stock &amp; Batch</button></li>
        <li class="nav-item"><button class="nav-link px-2 py-1"        data-bs-toggle="tab" data-bs-target="#et3" type="button"><i class="ri-settings-3-line me-1"></i>VAT &amp; Settings</button></li>
      </ul>
      <div class="modal-body" style="padding:14px 18px 8px !important;">
        <form id="editDataForm">
          @csrf
          <input type="hidden" id="editId">
          <input type="hidden" id="editRow">
          <div class="tab-content">

            {{-- ── Tab 1: Product info, quantity, prices ─────────────────── --}}
            <div class="tab-pane fade show active" id="et1" role="tabpanel">

              {{-- Product name + unit (read-only context) --}}
              <div class="row g-2 mb-3 mt-1">
                <div class="col-8">
                  <label class="form-label fw-semibold" style="font-size:12px;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;">Product</label>
                  <div style="font-size:15px;font-weight:700;color:#1e293b;padding:6px 0 2px;" id="editProductNameDisplay"></div>
                </div>
                <div class="col-4">
                  <label class="form-label fw-semibold" style="font-size:12px;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;">Unit</label>
                  <div style="font-size:14px;font-weight:600;color:#475569;padding:6px 0 2px;" id="editUnitDisplay"></div>
                </div>
              </div>

              <hr class="my-2">

              {{-- Stock quantity --}}
              <div class="row g-2 mb-3">
                <div class="col-4">
                  <label class="form-label fw-semibold" style="font-size:13px">Stock Quantity</label>
                  <input class="form-control form-control-sm" type="number" step="0.001" id="editStockQtyTab1" />
                </div>
                <div class="col-4">
                  <label class="form-label fw-semibold" style="font-size:13px">Reorder Point</label>
                  <input class="form-control form-control-sm" type="number" step="0.001" min="0" id="editReorderPointTab1" />
                </div>
                <div class="col-4">
                  <label class="form-label fw-semibold" style="font-size:13px">Max Stock</label>
                  <input class="form-control form-control-sm" type="number" step="0.001" min="0" id="editMaxStockTab1" />
                </div>
              </div>

              <hr class="my-2">

              {{-- Prices --}}
              <div class="row g-2 mb-2">
                <div class="col-4">
                  <label class="form-label fw-semibold" style="font-size:13px">Selling Price <span class="text-danger">*</span> <small class="text-muted">(MWK)</small></label>
                  <input class="form-control form-control-sm" type="number" step="0.01" min="0" id="editSellPrice" required />
                </div>
                <div class="col-4">
                  <label class="form-label fw-semibold" style="font-size:13px">Cost Price <small class="text-muted">(MWK)</small></label>
                  <input class="form-control form-control-sm" type="number" step="0.01" min="0" id="editCostPrice" />
                </div>
                <div class="col-4">
                  <label class="form-label fw-semibold" style="font-size:13px">Wholesale Price <small class="text-muted">(MWK)</small></label>
                  <input class="form-control form-control-sm" type="number" step="0.01" min="0" id="editWholesalePrice" />
                </div>
              </div>
              <div class="alert border-0 py-2 px-3 mt-1" style="background:#f0f3ff;border-left:3px solid #4B5EBD !important;border-radius:0 5px 5px 0;font-size:11px;color:#3a4a9a;">
                <i class="ri-information-line me-1"></i>
                Prices saved here become <strong>branch-specific overrides</strong> <span class="price-src price-src-br">BR</span> shown in <span style="color:#1d4ed8;font-weight:700">blue</span>,
                taking precedence over base product defaults <span class="price-src price-src-bp">BP</span> shown in <span style="color:#059669;font-weight:700">green</span>.
              </div>
            </div>

            {{-- ── Tab 2: Stock & Batch ──────────────────────────────────── --}}
            <div class="tab-pane fade" id="et2" role="tabpanel">
              <div class="row g-2 mb-2 mt-1">
                <div class="col-3"><label class="form-label fw-semibold" style="font-size:12px">Stock Qty</label><input class="form-control form-control-sm" type="number" step="0.001" id="editStockQty" /></div>
                <div class="col-3"><label class="form-label fw-semibold" style="font-size:12px">Reorder Point</label><input class="form-control form-control-sm" type="number" step="0.001" min="0" id="editReorderPoint" /></div>
                <div class="col-3"><label class="form-label fw-semibold" style="font-size:12px">Reorder Qty</label><input class="form-control form-control-sm" type="number" step="0.001" min="0" id="editReorderQty" /></div>
                <div class="col-3"><label class="form-label fw-semibold" style="font-size:12px">Max Stock</label><input class="form-control form-control-sm" type="number" step="0.001" min="0" id="editMaxStock" /></div>
              </div>
              <div class="row g-2">
                <div class="col-4"><label class="form-label fw-semibold" style="font-size:12px">Primary Barcode</label><input class="form-control form-control-sm" type="text" id="editBarcode" autocomplete="off" /></div>
                <div class="col-4"><label class="form-label fw-semibold" style="font-size:12px">Batch / Lot Number</label><input class="form-control form-control-sm" type="text" id="editBatch" autocomplete="off" /></div>
                <div class="col-4"><label class="form-label fw-semibold" style="font-size:12px">Expiry Date</label><input class="form-control form-control-sm" type="date" id="editExpiry" /></div>
              </div>
            </div>

            {{-- ── Tab 3: VAT & Settings ─────────────────────────────────── --}}
            <div class="tab-pane fade" id="et3" role="tabpanel">
              <div class="mb-3 mt-1">
                <label class="form-label fw-semibold" style="font-size:13px">VAT Override <small class="text-muted fw-normal">(leave blank to inherit from base product)</small></label>
                <select class="form-select form-select-sm" id="editTaxOverride">
                  <option value="">Inherit from base product</option>
                  <option value="A">A — Standard VAT 17.5%</option>
                  <option value="B">B — Reduced VAT rate</option>
                  <option value="C">C — Zero-rated (0%)</option>
                  <option value="E">E — VAT Exempt</option>
                  <option value="TL">TL — Tourism Levy 1%</option>
                </select>
              </div>
              <div class="row g-2">
                <div class="col-6">
                  <div class="form-check"><input class="form-check-input" type="checkbox" id="editTrackStock"><label class="form-check-label" for="editTrackStock" style="font-size:12px">Track stock</label></div>
                  <div class="form-check mt-1"><input class="form-check-input" type="checkbox" id="editAllowNeg"><label class="form-check-label" for="editAllowNeg" style="font-size:12px">Allow negative stock</label></div>
                </div>
                <div class="col-6">
                  <div class="form-check"><input class="form-check-input" type="checkbox" id="editIsActive"><label class="form-check-label" for="editIsActive" style="font-size:12px">Active at this branch</label></div>
                  <div class="form-check mt-1"><input class="form-check-input" type="checkbox" id="editPinned"><label class="form-check-label" for="editPinned" style="font-size:12px">Pinned on POS grid</label></div>
                </div>
              </div>
              <div class="mt-2">
                <label class="form-label fw-semibold" style="font-size:12px">POS Sort Order</label>
                <input class="form-control form-control-sm" type="number" id="editSortOrder" value="0" style="width:100px;" />
              </div>
            </div>

          </div>
        </form>
      </div>
      <div class="modal-footer" style="padding:10px 18px 14px;justify-content:flex-end;gap:8px;">
        <a href="#" class="btn btn-secondary btn-sm" id="cancelEditBtn">Cancel</a>
        <a href="#" class="btn btn-primary btn-sm" id="submitEditBtn">
          <i class="ri-check-line me-1"></i> Update
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
        <div class="bulk-section">
          <div class="bulk-section-title"><i class="ri-percent-line me-1"></i> Change VAT Override</div>
          <div class="d-flex gap-2">
            <select class="form-select form-select-sm" id="bulkTaxSelect">
              <option value="">— Select VAT Type —</option>
              <option value="A">A — Standard VAT 17.5%</option>
              <option value="E">E — VAT Exempt</option>
              <option value="TL">TL — Tourism Levy 1%</option>
            </select>
            <a href="#" class="btn btn-sm btn-primary" id="applyBulkTaxBtn" style="white-space:nowrap"><i class="ri-check-line me-1"></i> Apply</a>
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

    function taxBadgeClass(t) {
        if (t==='A') return 'bg-danger';
        if (t==='B') return 'bg-warning text-dark';
        if (t==='C') return 'bg-info text-dark';
        if (t==='E') return 'bg-secondary';
        if (t==='TL') return 'bg-warning text-dark';
        return 'bg-info';
    }

    var TAX_LABELS = { 'A':'A — Standard VAT 17.5%','B':'B — Reduced VAT','C':'C — Zero-rated (0%)','E':'E — VAT Exempt','TL':'TL — Tourism Levy 1%' };

    function yn(val) {
        return parseInt(val)===1
            ? '<span class="badge bg-success" style="font-size:11px">Yes</span>'
            : '<span class="badge bg-secondary" style="font-size:11px">No</span>';
    }

    // ── Price source pill HTML ────────────────────────────────────────────
    function priceSrcPill(isBranch) {
        return isBranch
            ? '<span class="price-src price-src-br" title="Branch-specific price">BR</span>'
            : '<span class="price-src price-src-bp" title="Inherited from base product">BP</span>';
    }

    // ── Build row HTML (uses colour classes for prices) ───────────────────
    function buildRow(p) {
        var sq = parseFloat(p.stock_quantity || 0);
        var rp = parseFloat(p.reorder_point || 0);
        var sc = sq <= 0 ? 'stock-zero' : (sq <= rp ? 'stock-low' : 'stock-ok');
        var taxId = p.mra_tax_rate_id_override || p.mra_tax_rate_id || '';
        var tc    = taxBadgeClass(taxId);
        var taxBadge = taxId ? '<span class="badge tax-badge '+tc+'">'+taxId+'</span>' : '<span class="text-muted" style="font-size:12px">—</span>';
        var d = function(v){ return (v||'').toString().replace(/"/g,'&quot;'); };

        var hasBranchSell = p.has_branch_sell == 1 || p.has_branch_sell === true;
        var hasBranchCost = p.has_branch_cost == 1 || p.has_branch_cost === true;

        var sellColorClass = hasBranchSell ? 'price-branch' : 'price-base';
        var costColorClass = hasBranchCost ? 'price-branch' : 'price-base';

        return `<tr id="${p.row}">
            <td><input type="checkbox" class="selectRow" value="${p.id}" data-row-id="${p.row}">
                &nbsp;${p.name}${p.brand ? ' <small class="text-muted">· '+p.brand+'</small>' : ''}
            </td>
            <td>${p.internal_code || '—'}</td>
            <td>${p.unit_of_measure || '—'}</td>
            <td>
                ${p.cost_price !== null && p.cost_price !== ''
                    ? `<span class="${costColorClass}" style="font-size:12px">${fmtNum(p.cost_price)}</span> ${priceSrcPill(hasBranchCost)}`
                    : '<span class="text-muted" style="font-size:12px">—</span>'}
            </td>
            <td>
                <span class="${sellColorClass}" style="font-size:12px">${fmtNum(p.selling_price)}</span>
                ${priceSrcPill(hasBranchSell)}
            </td>
            <td><span class="${sc}">${fmtNum(sq,0)}</span></td>
            <td>${taxBadge}</td>
            <td>${parseInt(p.is_active)===1 ? '<span class="badge bg-success" style="font-size:11px">Active</span>' : '<span class="badge bg-danger" style="font-size:11px">Inactive</span>'}</td>
            <td>
                <a href="#" class="viewDataBtn"
                   data-id="${p.id}" data-name="${d(p.name)}" data-code="${d(p.internal_code)}"
                   data-unit="${d(p.unit_of_measure)}" data-brand="${d(p.brand)}"
                   data-barcode="${d(p.primary_barcode)}" data-batch="${d(p.batch_number)}"
                   data-expiry="${d(p.expiry_date)}"
                   data-cost="${p.cost_price!==null?p.cost_price:''}"
                   data-sell="${p.selling_price!==null?p.selling_price:''}"
                   data-wholesale="${p.wholesale_price!==null?p.wholesale_price:''}"
                   data-stock="${p.stock_quantity}" data-reorder="${p.reorder_point}"
                   data-reorder-qty="${p.reorder_quantity!==null?p.reorder_quantity:''}"
                   data-max="${p.max_stock!==null?p.max_stock:''}"
                   data-tax="${d(taxId)}" data-tax-override="${d(p.mra_tax_rate_id_override)}"
                   data-active="${p.is_active}" data-track="${p.track_stock}"
                   data-neg="${p.allow_negative_stock}" data-pinned="${p.is_pinned_on_pos}"
                   data-branch-sell="${hasBranchSell?1:0}" data-branch-cost="${hasBranchCost?1:0}"
                   data-bp-sell="${p.bp_sell||''}" data-bp-cost="${p.bp_cost||''}">
                   <i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px"></i>
                </a>
                <a href="#" class="editDataBtn"
                   data-id="${p.id}" data-row="${p.row}" data-name="${d(p.name)}"
                   data-unit="${d(p.unit_of_measure)}"
                   data-sell="${p.selling_price!==null?p.selling_price:''}"
                   data-cost="${p.cost_price!==null?p.cost_price:''}"
                   data-wholesale="${p.wholesale_price!==null?p.wholesale_price:''}"
                   data-stock="${p.stock_quantity}" data-reorder="${p.reorder_point}"
                   data-reorder-qty="${p.reorder_quantity!==null?p.reorder_quantity:''}"
                   data-max="${p.max_stock!==null?p.max_stock:''}"
                   data-barcode="${d(p.primary_barcode)}" data-batch="${d(p.batch_number)}"
                   data-expiry="${d(p.expiry_date)}"
                   data-tax-override="${d(p.mra_tax_rate_id_override)}"
                   data-active="${p.is_active}" data-track="${p.track_stock}"
                   data-neg="${p.allow_negative_stock}" data-pinned="${p.is_pinned_on_pos}"
                   data-sort="${p.pos_sort_order}">
                   <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px"></i>
                </a>
                <a href="#" class="deleteDataBtn"
                   data-label="${d(p.name)}" data-id="${p.id}" data-row="${p.row}">
                   <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px"></i>
                </a>
            </td>
        </tr>`;
    }

    // ── Selected count & bulk bar ─────────────────────────────────────────
    function updateSelectedCount() {
        var count = $('.selectRow:checked').length;
        $('#selectedCount').text(count);
        if (count > 0) {
            $('#bulkTriggerBtn').show();
            $('#bulkBar').addClass('visible');
        } else {
            $('#bulkTriggerBtn').hide();
            $('#bulkBar').removeClass('visible');
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    //  DataTable (only when branch selected)
    // ════════════════════════════════════════════════════════════════════════
    @if($selectedBranch)

    var table = $('#maintable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[100,250,500,-1],[100,250,500,'All']],
        fixedColumns: { leftColumns: 1 },
        scrollX: true,
        columnDefs: [
            { targets:'_all', className:'text-center' },
            { targets:0,      className:'text-start'  }
        ],
        buttons: [
            { extend:'excelHtml5', title:@json($maintableTitle), exportOptions:{ columns:':visible:not(:last-child)' } },
            { extend:'csvHtml5',   title:@json($maintableTitle), exportOptions:{ columns:':visible:not(:last-child)' } },
            {
                extend:'pdfHtml5', title:@json($maintableTitle),
                exportOptions:{ columns:':visible:not(:last-child)' },
                customize: function(doc) {
                    doc.content[1].table.widths = Array(doc.content[1].table.body[0].length+1).join('*').split('');
                }
            }
        ]
    });
    window._dt = table;
    table.buttons().container().appendTo($('#buttonsModal .buttons'));

    // ════════════════════════════════════════════════════════════════════════
    //  SHOP VALUE BUTTON
    // ════════════════════════════════════════════════════════════════════════
    $('#shopValueBtn').on('click', function(e) { e.preventDefault(); $('#shopValueModal').modal('show'); });

    // ════════════════════════════════════════════════════════════════════════
    //  PRICING INFO BUTTON
    // ════════════════════════════════════════════════════════════════════════
    $('#pricingInfoBtn').on('click', function(e) { e.preventDefault(); $('#pricingInfoModal').modal('show'); });

    // ════════════════════════════════════════════════════════════════════════
    //  ADD PRODUCT
    // ════════════════════════════════════════════════════════════════════════
    var allBaseProducts = [];
    var pendingItems    = {};

    function loadBaseProducts() {
        if (allBaseProducts.length) return;
        $.ajax({
            type:'GET', url:'{{ route("retail.operations.baseproducts.search") }}',
            data:{ branch_id: {{ $selectedBranch->id ?? 0 }} },
            success: function(data) { allBaseProducts = data.products || []; }
        });
    }

    $('#addProductBtn').on('click', function(e) {
        e.preventDefault();
        resetAddModal();
        loadBaseProducts();
        $('#addProductModal').modal('show');
        setTimeout(function() { $('#baseProductSearch').focus(); }, 400);
    });

    $('#baseProductSearch').on('input', function() {
        var q = $(this).val().trim().toLowerCase();
        if (!q) { $('#searchResultList').hide(); return; }
        var results = allBaseProducts.filter(function(p) {
            return p.name.toLowerCase().indexOf(q) >= 0
                || (p.internal_code && p.internal_code.toLowerCase().indexOf(q) >= 0);
        }).slice(0, 20);
        renderSearchResults(results, q);
    });

    function renderSearchResults(results, q) {
        var list = $('#searchResultList');
        if (!results.length) {
            list.html('<div style="padding:12px;text-align:center;color:#94a3b8;font-size:12px;"><i class="ri-search-line"></i> No products found</div>').show();
            return;
        }
        var html = '';
        results.forEach(function(p) {
            var re     = new RegExp('('+q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')+')', 'gi');
            var nameHl = p.name.replace(re, '<strong>$1</strong>');
            var price  = p.default_selling_price
                ? 'MWK '+parseFloat(p.default_selling_price).toLocaleString('en-US',{minimumFractionDigits:2}) : '';
            var meta   = [p.internal_code, p.unit_of_measure].filter(Boolean).join(' · ');
            var already = pendingItems[p.id] ? 'style="opacity:.5;pointer-events:none;"' : '';
            html += `<div class="search-result-item" ${already} data-id="${p.id}"
                         data-name="${(p.name||'').replace(/"/g,'&quot;')}"
                         data-code="${(p.internal_code||'').replace(/"/g,'&quot;')}"
                         data-unit="${(p.unit_of_measure||'').replace(/"/g,'&quot;')}"
                         data-sell="${p.default_selling_price||''}"
                         data-cost="${p.default_cost_price||''}">
                <div>
                    <div class="sr-name">${nameHl}</div>
                    <div class="sr-meta">${meta}</div>
                </div>
                <div class="sr-price">${price}</div>
            </div>`;
        });
        list.html(html).show();
    }

    $(document).on('click', '.search-result-item', function() {
        var id   = $(this).data('id');
        if (pendingItems[id]) return;

        var name = $(this).data('name');
        var code = $(this).data('code');
        var unit = $(this).data('unit');
        var sell = $(this).data('sell');
        var cost = $(this).data('cost');

        pendingItems[id] = { id:id, name:name, code:code, unit:unit, sell:sell, cost:cost };

        var rowId = 'prow_'+id;
        var meta  = [code, unit].filter(Boolean).join(' · ');
        var priceDisplay = sell ? 'MWK '+parseFloat(sell).toLocaleString('en-US',{minimumFractionDigits:2}) : '';

        var html = `<div class="inline-add-row" id="${rowId}">
            <div class="flex-fill">
                <div class="ia-name">${name}</div>
                <div class="ia-meta">${meta}</div>
            </div>
            <div class="ia-price">${priceDisplay}</div>
            <input type="number" class="qty-input" id="qty_${id}"
                   placeholder="Qty" step="0.001" min="0" value="0" />
            <a href="#" class="btn btn-sm btn-outline-primary btn-more-details" data-pid="${id}">
                <i class="ri-settings-3-line"></i> Details
            </a>
            <span class="btn-remove-pending" data-pid="${id}" title="Remove">&times;</span>
        </div>
        <div class="more-details-panel" id="mdp_${id}">
            <div class="row g-2">
                <div class="col-4">
                    <label style="font-size:11px;font-weight:600">Branch Sell Price (MWK)</label>
                    <input type="number" class="form-control form-control-sm" step="0.01" min="0"
                           id="md_sell_${id}" value="${sell ? parseFloat(sell).toFixed(2) : ''}" placeholder="0.00" />
                </div>
                <div class="col-4">
                    <label style="font-size:11px;font-weight:600">Branch Cost Price (MWK)</label>
                    <input type="number" class="form-control form-control-sm" step="0.01" min="0"
                           id="md_cost_${id}" value="${cost ? parseFloat(cost).toFixed(2) : ''}" placeholder="0.00" />
                </div>
                <div class="col-4">
                    <label style="font-size:11px;font-weight:600">Reorder Point</label>
                    <input type="number" class="form-control form-control-sm" step="0.001" min="0"
                           id="md_reorder_${id}" value="0" placeholder="0" />
                </div>
                <div class="col-6">
                    <label style="font-size:11px;font-weight:600">Primary Barcode</label>
                    <input type="text" class="form-control form-control-sm" id="md_barcode_${id}" autocomplete="off" />
                </div>
                <div class="col-6">
                    <label style="font-size:11px;font-weight:600">Expiry Date</label>
                    <input type="date" class="form-control form-control-sm" id="md_expiry_${id}" />
                </div>
                <div class="col-12 d-flex gap-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="md_track_${id}" checked>
                        <label class="form-check-label" for="md_track_${id}" style="font-size:11px">Track stock</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="md_active_${id}" checked>
                        <label class="form-check-label" for="md_active_${id}" style="font-size:11px">Active at branch</label>
                    </div>
                </div>
            </div>
        </div>`;

        $('#pendingItemsList').append(html);
        $('#pendingItemsWrap').show();
        $('#baseProductSearch').val('');
        $('#searchResultList').hide();
        $('#addSuccessNotice').hide();
        setTimeout(function() { $('#qty_'+id).focus(); }, 100);
    });

    $(document).on('click', '.btn-remove-pending', function(e) {
        e.preventDefault();
        var pid = $(this).data('pid');
        delete pendingItems[pid];
        $('#prow_'+pid).remove();
        $('#mdp_'+pid).remove();
        if (Object.keys(pendingItems).length === 0) $('#pendingItemsWrap').hide();
    });

    $(document).on('click', '.btn-more-details', function(e) {
        e.preventDefault();
        var pid = $(this).data('pid');
        var panel = $('#mdp_'+pid);
        panel.is(':visible') ? panel.slideUp(150) : panel.slideDown(150);
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#baseProductSearch, #searchResultList').length) {
            $('#searchResultList').hide();
        }
    });

    function resetAddModal() {
        pendingItems    = {};
        allBaseProducts = [];
        $('#baseProductSearch').val('');
        $('#searchResultList').hide().empty();
        $('#pendingItemsList').empty();
        $('#pendingItemsWrap').hide();
        $('#addSuccessNotice').hide();
        $('#new-name, #new-selling-price, #new-cost-price, #new-internal-code').val('');
        $('#new-stock-qty').val('0');
        $('#new-unit-of-measure').val('Each');
        $('#new-supplier').val('');
        $('#new-tax-rate').val('A');
    }

    $('#cancelAddBtn').on('click', function(e) {
        e.preventDefault();
        resetAddModal();
        $('#addProductModal').modal('hide');
    });

    $('#addProductModal').on('hidden.bs.modal', resetAddModal);

    $('#submitAddBtn').on('click', function(e) {
        e.preventDefault();

        var activeTab = $('#addProductModal .nav-link.active').attr('data-bs-target');

        if (activeTab === '#at1') {
            var ids = Object.keys(pendingItems);
            if (!ids.length) {
                toastr.warning('Please search and select at least one product.', 'Required');
                return;
            }

            for (var i = 0; i < ids.length; i++) {
                var pid = ids[i];
                var sellInput = $('#md_sell_'+pid).val();
                var sellPrice = sellInput && parseFloat(sellInput) >= 0 ? sellInput : pendingItems[pid].sell;
                if (!sellPrice || parseFloat(sellPrice) < 0) {
                    toastr.warning('Please enter a selling price for "'+pendingItems[pid].name+'".', 'Required');
                    $('#mdp_'+pid).slideDown(150); $('#md_sell_'+pid).focus();
                    return;
                }
            }

            var self = $(this); self.prop('disabled', true);
            $.ajaxSetup({ headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')} });

            var total = ids.length, done = 0, succeeded = 0;

            function addNext(index) {
                if (index >= ids.length) {
                    self.prop('disabled', false);
                    $('#progressBar').hide();
                    if (succeeded > 0) {
                        toastr.success(succeeded + ' product' + (succeeded>1?'s':'') + ' added to branch.', 'Success');
                        $('#addSuccessText').text(succeeded + ' added successfully.');
                        $('#addSuccessNotice').show();
                    }
                    pendingItems    = {};
                    allBaseProducts = [];
                    $('#pendingItemsList').empty();
                    $('#pendingItemsWrap').hide();
                    $('#baseProductSearch').val('');
                    return;
                }
                var pid  = ids[index];
                var item = pendingItems[pid];
                var sellInput = $('#md_sell_'+pid).val();
                var sellPrice = sellInput && parseFloat(sellInput) >= 0 ? sellInput : item.sell;

                $.ajax({
                    type:'POST', url:'{{ route("retail.operations.branchproducts.upsert") }}',
                    data:{
                        branch_id:            {{ $selectedBranch->id ?? 0 }},
                        base_product_id:      pid,
                        selling_price:        sellPrice,
                        cost_price:           $('#md_cost_'+pid).val(),
                        stock_quantity:       parseFloat($('#qty_'+pid).val()) || 0,
                        reorder_point:        $('#md_reorder_'+pid).val() || 0,
                        primary_barcode:      $('#md_barcode_'+pid).val(),
                        expiry_date:          $('#md_expiry_'+pid).val(),
                        track_stock:          $('#md_track_'+pid).prop('checked') ? 1 : 0,
                        is_active:            $('#md_active_'+pid).prop('checked') ? 1 : 0,
                        allow_negative_stock: 0,
                        is_pinned_on_pos:     0,
                        _token: '{{ csrf_token() }}'
                    },
                    timeout:60000,
                    beforeSend: function() { if (index === 0) $('#progressBar').show(); },
                    success: function(data) {
                        done++;
                        if (data.status === 201) {
                            succeeded++;
                            if (window._dt) {
                                if (table.row('#'+data.product.row).length) { table.row('#'+data.product.row).remove(); }
                                table.row.add($(buildRow(data.product))).draw(false);
                            }
                        } else if (data.status === 422) {
                            toastr.error((data.error || 'Failed') + ' (' + item.name + ')', 'Error');
                        }
                    },
                    error: function() {
                        done++;
                        toastr.error('Network error for "' + item.name + '"', 'Error');
                    },
                    complete: function() { addNext(index + 1); }
                });
            }

            addNext(0);

        } else {
            var name = $('#new-name').val().trim();
            if (!name) { toastr.warning('Product name is required.', 'Required'); $('#new-name').focus(); return; }
            var sell = $('#new-selling-price').val();
            if (!sell || parseFloat(sell) < 0) { toastr.warning('Selling price is required.', 'Required'); $('#new-selling-price').focus(); return; }

            var self = $(this); self.prop('disabled', true);
            $.ajaxSetup({ headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')} });

            $.ajax({
                type:'POST', url:'{{ route("retail.operations.baseproducts.insert") }}',
                data:{
                    name:                    name,
                    default_selling_price:   sell,
                    default_cost_price:      $('#new-cost-price').val(),
                    unit_of_measure:         $('#new-unit-of-measure').val() || 'Each',
                    internal_code:           $('#new-internal-code').val(),
                    supplier:                $('#new-supplier').val(),
                    mra_tax_rate_id:         $('#new-tax-rate').val(),
                    category:                @json($branchCategory->category ?? ''),
                    is_product:              1,
                    is_vat_exempt_by_nature: 0,
                    is_active:               1,
                    _token: '{{ csrf_token() }}'
                },
                timeout:60000,
                beforeSend: function() { $('#progressBar').show(); },
                success: function(bpData) {
                    if (bpData.status === 201) {
                        $.ajax({
                            type:'POST', url:'{{ route("retail.operations.branchproducts.upsert") }}',
                            data:{
                                branch_id:            {{ $selectedBranch->id ?? 0 }},
                                base_product_id:      bpData.product.id,
                                selling_price:        sell,
                                cost_price:           $('#new-cost-price').val(),
                                stock_quantity:       $('#new-stock-qty').val() || 0,
                                reorder_point:        0,
                                track_stock:          1,
                                allow_negative_stock: 0,
                                is_active:            1,
                                is_pinned_on_pos:     0,
                                _token: '{{ csrf_token() }}'
                            },
                            timeout:60000,
                            complete: function() { $('#progressBar').hide(); self.prop('disabled', false); },
                            success: function(data) {
                                if (data.status === 201) {
                                    toastr.success('Product created and added to branch.', 'Success');
                                    if (window._dt) {
                                        if (table.row('#'+data.product.row).length) { table.row('#'+data.product.row).remove(); }
                                        table.row.add($(buildRow(data.product))).draw(false);
                                    }
                                    allBaseProducts = [];
                                    $('#new-name, #new-selling-price, #new-cost-price, #new-internal-code').val('');
                                    $('#new-stock-qty').val('0');
                                    $('#new-unit-of-measure').val('Each');
                                    $('#new-supplier').val('');
                                    $('#new-tax-rate').val('A');
                                    $('#addSuccessText').text('Product "'+name+'" added.');
                                    $('#addSuccessNotice').show();
                                    $('#new-name').focus();
                                } else {
                                    toastr.error(data.error || 'Failed to assign to branch.', 'Error');
                                }
                            },
                            error: function() { $('#progressBar').hide(); self.prop('disabled',false); handleAjaxError.apply(this, arguments); }
                        });
                    } else {
                        $('#progressBar').hide(); self.prop('disabled', false);
                        toastr.error(bpData.error || 'Failed to create product.', 'Error');
                    }
                },
                error: function() { $('#progressBar').hide(); self.prop('disabled',false); handleAjaxError.apply(this, arguments); }
            });
        }
    });

    // ════════════════════════════════════════════════════════════════════════
    //  VIEW
    // ════════════════════════════════════════════════════════════════════════
    var _viewData = {};

    $('#tbody').on('click', '.viewDataBtn', function(e) {
        e.preventDefault();
        var b = $(this);
        _viewData = {
            id:b.data('id'), name:b.data('name'), code:b.data('code'),
            unit:b.data('unit'), brand:b.data('brand'),
            barcode:b.data('barcode'), batch:b.data('batch'), expiry:b.data('expiry'),
            cost:b.data('cost'), sell:b.data('sell'), wholesale:b.data('wholesale'),
            stock:b.data('stock'), reorder:b.data('reorder'),
            reorderQty:b.data('reorder-qty'), max:b.data('max'),
            tax:b.data('tax'), taxOverride:b.data('tax-override'),
            active:b.data('active'), track:b.data('track'),
            neg:b.data('neg'), pinned:b.data('pinned'),
            branchSell:b.data('branch-sell'), branchCost:b.data('branch-cost'),
            bpSell:b.data('bp-sell'), bpCost:b.data('bp-cost'),
            editRow:b.closest('tr').attr('id')
        };
        function mv(val) { return (val===''||val===null||val===undefined) ? '<span class="text-muted fst-italic">—</span>' : val; }

        $('#vw-name').text(_viewData.name);
        $('#vw-code-line').text(_viewData.code ? 'Code: '+_viewData.code : '');

        var tc = taxBadgeClass(_viewData.tax);
        var badges = '';
        if (_viewData.tax) badges += '<span class="badge tax-badge '+tc+'">'+_viewData.tax+'</span>';
        badges += parseInt(_viewData.active)===1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
        if (parseInt(_viewData.pinned)===1) badges += '<span class="badge bg-warning text-dark">POS Pinned</span>';
        $('#vw-badges').html(badges);

        $('#vw-sell-src-badge').html(priceSrcPill(parseInt(_viewData.branchSell)===1));
        $('#vw-cost-src-badge').html(priceSrcPill(parseInt(_viewData.branchCost)===1));

        var noticeParts = [];
        if (parseInt(_viewData.branchSell)===1) {
            noticeParts.push('Selling price is a branch-specific override.');
        } else {
            noticeParts.push('Selling price is inherited from the base product' + (_viewData.bpSell ? ' (default: MWK '+parseFloat(_viewData.bpSell).toLocaleString('en-US',{minimumFractionDigits:2})+')' : '') + '.');
        }
        if (parseInt(_viewData.branchCost)===1) {
            noticeParts.push('Cost price is a branch-specific override.');
        } else {
            noticeParts.push('Cost price is inherited from the base product.');
        }
        $('#vw-price-notice-text').html(noticeParts.join(' '));
        $('#vw-price-notice').show();

        $('#vw-sell').text(fmtNum(_viewData.sell));
        $('#vw-cost').text(fmtNum(_viewData.cost));
        $('#vw-wholesale').html(mv(fmtNum(_viewData.wholesale)));
        $('#vw-tax').html(_viewData.tax ? '<span class="badge tax-badge '+tc+'">'+_viewData.tax+'</span> '+(TAX_LABELS[_viewData.tax]||_viewData.tax) : '<span class="text-muted fst-italic">—</span>');

        $('#vw-stock').html('<span class="fw-bold" style="font-size:15px">'+fmtNum(_viewData.stock,0)+'</span>');
        $('#vw-reorder').text(fmtNum(_viewData.reorder,0));
        $('#vw-reorder-qty').html(mv(fmtNum(_viewData.reorderQty,0)));
        $('#vw-max').html(mv(fmtNum(_viewData.max,0)));
        $('#vw-barcode').html(mv(_viewData.barcode));
        $('#vw-batch').html(mv(_viewData.batch));
        $('#vw-expiry').html(mv(_viewData.expiry));

        $('#vw-track').html(yn(_viewData.track));
        $('#vw-neg').html(yn(_viewData.neg));
        $('#vw-pinned').html(yn(_viewData.pinned));
        $('#vw-tax-override').html(_viewData.taxOverride
            ? '<span class="badge tax-badge '+taxBadgeClass(_viewData.taxOverride)+'">'+_viewData.taxOverride+'</span>'
            : '<span class="text-muted fst-italic">Inherit from base</span>');

        $('#viewProductModal').modal('show');
    });

    $('#vwEditBtn').on('click', function(e) {
        e.preventDefault();
        $('#viewProductModal').modal('hide');
        setTimeout(function() {
            var $btn = $('#'+_viewData.editRow).find('.editDataBtn');
            if ($btn.length) $btn.trigger('click');
        }, 350);
    });

    // ════════════════════════════════════════════════════════════════════════
    //  EDIT — populate all tabs including Tab 1 product/qty/prices
    // ════════════════════════════════════════════════════════════════════════
    $('#tbody').on('click', '.editDataBtn', function(e) {
        e.preventDefault();
        var b = $(this);
        var productName = b.data('name');
        var unitName    = b.data('unit') || '—';

        $('#editId').val(b.data('id'));
        $('#editRow').val(b.data('row'));
        $('#editModalName').text(productName);

        // Tab 1 — product context + qty + prices
        $('#editProductNameDisplay').text(productName);
        $('#editUnitDisplay').text(unitName);
        $('#editSellPrice').val(b.data('sell'));
        $('#editCostPrice').val(b.data('cost'));
        $('#editWholesalePrice').val(b.data('wholesale'));
        // Stock fields mirrored on tab 1
        $('#editStockQtyTab1').val(b.data('stock'));
        $('#editReorderPointTab1').val(b.data('reorder'));
        $('#editMaxStockTab1').val(b.data('max'));

        // Tab 2 — stock & batch (full set)
        $('#editStockQty').val(b.data('stock'));
        $('#editReorderPoint').val(b.data('reorder'));
        $('#editReorderQty').val(b.data('reorder-qty'));
        $('#editMaxStock').val(b.data('max'));
        $('#editBarcode').val(b.data('barcode'));
        $('#editBatch').val(b.data('batch'));
        $('#editExpiry').val(b.data('expiry'));

        // Tab 3 — VAT & settings
        $('#editTaxOverride').val(b.data('tax-override') || '');
        $('#editTrackStock').prop('checked', parseInt(b.data('track'))===1);
        $('#editAllowNeg').prop('checked',   parseInt(b.data('neg'))===1);
        $('#editIsActive').prop('checked',   parseInt(b.data('active'))===1);
        $('#editPinned').prop('checked',     parseInt(b.data('pinned'))===1);
        $('#editSortOrder').val(b.data('sort') || 0);

        $('button[data-bs-target="#et1"]').tab('show');
        $('#editDataModal').modal('show');
    });

    // Keep Tab1 qty fields in sync with Tab2 (two-way mirror for stock/reorder/max)
    $('#editStockQtyTab1, #editReorderPointTab1, #editMaxStockTab1').on('input', function() {
        var which = $(this).attr('id');
        if (which === 'editStockQtyTab1')    $('#editStockQty').val($(this).val());
        if (which === 'editReorderPointTab1') $('#editReorderPoint').val($(this).val());
        if (which === 'editMaxStockTab1')    $('#editMaxStock').val($(this).val());
    });
    $('#editStockQty, #editReorderPoint, #editMaxStock').on('input', function() {
        var which = $(this).attr('id');
        if (which === 'editStockQty')    $('#editStockQtyTab1').val($(this).val());
        if (which === 'editReorderPoint') $('#editReorderPointTab1').val($(this).val());
        if (which === 'editMaxStock')    $('#editMaxStockTab1').val($(this).val());
    });

    $('#cancelEditBtn').on('click', function(e) { e.preventDefault(); $('#editDataForm')[0].reset(); $('#editDataModal').modal('hide'); });

    $('#submitEditBtn').on('click', function(e) {
        e.preventDefault();
        var sell = $('#editSellPrice').val();
        if (!sell || parseFloat(sell) < 0) {
            toastr.warning('Selling price is required.', 'Required');
            $('button[data-bs-target="#et1"]').tab('show'); $('#editSellPrice').focus(); return;
        }
        var self = $(this); self.prop('disabled', true);
        var row  = $('#editRow').val();
        $.ajaxSetup({ headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:'POST', url:'{{ route("retail.operations.branchproducts.update") }}',
            data:{
                id:                       $('#editId').val(),
                selling_price:            sell,
                cost_price:               $('#editCostPrice').val(),
                wholesale_price:          $('#editWholesalePrice').val(),
                stock_quantity:           $('#editStockQty').val(),
                reorder_point:            $('#editReorderPoint').val(),
                reorder_quantity:         $('#editReorderQty').val(),
                max_stock:                $('#editMaxStock').val(),
                primary_barcode:          $('#editBarcode').val(),
                batch_number:             $('#editBatch').val(),
                expiry_date:              $('#editExpiry').val(),
                mra_tax_rate_id_override: $('#editTaxOverride').val(),
                track_stock:              $('#editTrackStock').prop('checked') ? 1 : 0,
                allow_negative_stock:     $('#editAllowNeg').prop('checked')   ? 1 : 0,
                is_active:                $('#editIsActive').prop('checked')   ? 1 : 0,
                is_pinned_on_pos:         $('#editPinned').prop('checked')     ? 1 : 0,
                pos_sort_order:           $('#editSortOrder').val() || 0,
                _token: '{{ csrf_token() }}'
            },
            timeout:60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    table.row('#'+row).remove();
                    table.row.add($(buildRow(data.product))).draw(false);
                    updateSelectedCount();
                    $('#editDataModal').modal('hide');
                } else if (data.status === 422) {
                    toastr.error(data.error || 'Validation failed.', 'Error');
                } else { toastr.info('Unspecified error.', 'Error'); }
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

    $('#keepBtn').on('click', function(e) { e.preventDefault(); toastr.info('Your data is safe','Great!'); $('#deleteModal').modal('hide'); });

    $('#submitDeleteBtn').on('click', function(e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        var row = $('#deleteRow').val(), id = $('#deleteId').val();
        $.ajaxSetup({ headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:'POST', url:'{{ route("retail.operations.branchproducts.delete") }}',
            data:{ id:id, _token:'{{ csrf_token() }}' },
            timeout:60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    table.row('#'+row).remove().draw(false);
                    updateSelectedCount();
                    $('#deleteModal').modal('hide');
                } else if (data.status === 422) {
                    toastr.error(data.error || 'Validation failed.', 'Error');
                } else { toastr.info('Unspecified error.', 'Error'); }
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

    function getSelectedIds()  { var ids=[]; $('.selectRow:checked').each(function() { ids.push($(this).val()); }); return ids; }
    function getSelectedRows() { var rows=[]; $('.selectRow:checked').each(function() { rows.push($(this).data('row-id')); }); return rows; }

    function doBulkStatus(isActive) {
        var ids = getSelectedIds(), rows = getSelectedRows();
        if (!ids.length) return;
        $.ajaxSetup({ headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:'POST', url:'{{ route("retail.operations.branchproducts.bulkstatus") }}',
            data:{ ids:ids, is_active:isActive, _token:'{{ csrf_token() }}' },
            timeout:60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    $.each(data.products, function(i,p) { table.row('#'+p.row).remove(); table.row.add($(buildRow(p))); });
                    table.draw(false); updateSelectedCount(); $('#bulkActionsModal').modal('hide');
                } else { toastr.error(data.error || 'Failed.', 'Error'); }
            },
            error: handleAjaxError
        });
    }
    $('#bulkActivateBtn').on('click',   function(e) { e.preventDefault(); doBulkStatus(1); });
    $('#bulkDeactivateBtn').on('click', function(e) { e.preventDefault(); doBulkStatus(0); });

    $('#applyBulkTaxBtn').on('click', function(e) {
        e.preventDefault();
        var tax = $('#bulkTaxSelect').val();
        var ids = getSelectedIds(); if (!ids.length) return;
        $.ajaxSetup({ headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:'POST', url:'{{ route("retail.operations.branchproducts.bulktax") }}',
            data:{ ids:ids, mra_tax_rate_id_override:tax, _token:'{{ csrf_token() }}' },
            timeout:60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    $.each(data.products, function(i,p) { table.row('#'+p.row).remove(); table.row.add($(buildRow(p))); });
                    table.draw(false); updateSelectedCount(); $('#bulkActionsModal').modal('hide');
                } else { toastr.error(data.error || 'Failed.', 'Error'); }
            },
            error: handleAjaxError
        });
    });

    $('#bulkDeleteBtn').on('click', function(e) {
        e.preventDefault();
        var ids = getSelectedIds(), rows = getSelectedRows();
        if (!ids.length) { toastr.warning('No products selected.', 'Warning'); return; }
        if (!confirm('Remove '+ids.length+' product(s) from this branch? This cannot be undone.')) return;
        $('#bulkActionsModal').modal('hide');
        $.ajaxSetup({ headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:'POST', url:'{{ route("retail.operations.branchproducts.bulkdelete") }}',
            data:{ ids:ids, _token:'{{ csrf_token() }}' },
            timeout:60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    rows.forEach(function(r) { table.row('#'+r).remove(); });
                    table.draw(false); updateSelectedCount();
                } else { toastr.error(data.error || 'Failed.', 'Error'); }
            },
            error: handleAjaxError
        });
    });

    @endif

    // ── Info / Download modals ────────────────────────────────────────────
    $('#infoBtn').on('click',         function(e) { e.preventDefault(); $('#infoModal').modal('show'); });
    $('#tableButtonsBtn').on('click', function(e) { e.preventDefault(); $('#buttonsModal').modal('show'); });

});
</script>
@endsection