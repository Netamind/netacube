@extends('sales.retail.dashboard')
@section('content')

@php
    $branchProducts = collect();
    $selectedBranch = null;
    $shopValue      = 0;
    $branchCategory = null;

    $branchId = Auth::user()->branch;

    if ($branchId) {
        $selectedBranch = DB::connection('tenant')->table('branches')->find($branchId);

        if ($selectedBranch) {
            $branchCategory = DB::connection('tenant')
                ->table('categories')
                ->where('id', $selectedBranch->category)
                ->first();
        }

        $branchProducts = DB::connection('tenant')
            ->table('retail_branch_products as rbp')
            ->join('retail_base_products as bp', 'bp.id', '=', 'rbp.base_product_id')
            ->where('rbp.branch_id', $branchId)
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
      {{ $selectedBranch->name ?? 'No Branch Assigned' }}
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
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="About Branch Products">
        <i class="ri-information-line"></i>
      </a>
     <!-- <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download">
        <i class="ri-download-line"></i>
      </a>-->
    </div>
  </div>

  {{-- ── Table / Empty state ─────────────────────────────────────────────── --}}
  <div class="card-body">

    @if(!$selectedBranch)
      <div class="no-branch-wrap">
        <i class="ri-store-line"></i>
        <h5>No Branch Assigned</h5>
        <p style="font-size:13px;">Your user account is not currently assigned to a branch. Contact an administrator to get a branch assigned.</p>
      </div>
    @else

    <table id="maintable"
           class="table table-sm table-striped row-border order-column w-100 mt-3">
      <thead style="background-color:#e2e2e9">
        <tr>
          <th>Product Name</th>
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
            <td>{{ $bp->name }}</td>
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
      Branch products are base catalogue items <em>assigned to a specific branch</em>. Each branch can have its own selling price, stock quantity, reorder points, and barcode. This view is read-only.</p>
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
     VIEW PRODUCT MODAL (read-only)
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
      <div class="modal-footer" style="padding:10px 20px 14px;">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {

    toastr.options = { closeButton:true, progressBar:true, showMethod:'slideDown', timeOut:5000, allowHtml:true };

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

    // ════════════════════════════════════════════════════════════════════════
    //  DataTable (read-only)
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
    //  VIEW (read-only)
    // ════════════════════════════════════════════════════════════════════════
    $('#tbody').on('click', '.viewDataBtn', function(e) {
        e.preventDefault();
        var b = $(this);
        var _viewData = {
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
            bpCost:       b.data('bp-cost')
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

    @endif

    $('#infoBtn').on('click',         function(e) { e.preventDefault(); $('#infoModal').modal('show'); });
    $('#tableButtonsBtn').on('click', function(e) { e.preventDefault(); $('#buttonsModal').modal('show'); });

});
</script>
@endsection