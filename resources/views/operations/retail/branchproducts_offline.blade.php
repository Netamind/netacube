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
            ->select('rbp.*', 'bp.name', 'bp.code', 'bp.unit',
                     'bp.selling_price as bp_sell', 'bp.cost_price as bp_cost')
            ->get();

        foreach ($branchProducts as $bp) {
            $sellIsBranch = ($bp->selling_price !== null);
            $unitPrice    = $sellIsBranch ? (float)$bp->selling_price : (float)$bp->bp_sell;
            $shopValue   += $unitPrice * (float)$bp->stock_quantity;
        }
    }

    $activeCount   = $branchProducts->where('is_active', 1)->count();
    $lowStockCount = $branchProducts->filter(fn($p) => (float)$p->stock_quantity <= (float)$p->reorder_point && (float)$p->stock_quantity > 0)->count();
    $zeroCount     = $branchProducts->filter(fn($p) => (float)$p->stock_quantity <= 0)->count();
@endphp

<style>
.card-header { padding:0.5rem 1.5rem !important; background:linear-gradient(to right,#4B5EBD,#576CC0); color:#fff; border-radius:10px 10px 0 0 !important; flex-wrap:wrap; gap:8px; }
.card-body   { padding:0 1.5rem 1.5rem 1.5rem !important; }
.card        { border:none; box-shadow:0 4px 8px rgba(0,0,0,0.1); border-radius:10px; }
.card-header h4 { color:#fff; font-weight:600; margin-bottom:0; display:flex; align-items:center; }
.card-header .btn-light { height:28px; padding:0 10px; display:flex; align-items:center; justify-content:center; line-height:1; position:relative; }
.card-header .btn-light:hover { background-color:#f8f9fa; transition:background-color 0.2s; }
.card-header-actions { display:flex; align-items:center; gap:4px; flex-wrap:wrap; justify-content:flex-end; }
#branchSelectHeader { border:none; background:transparent; color:#fff; font-size:18px; font-weight:600; cursor:pointer; padding:0; outline:none; max-width:300px; }
#branchSelectHeader option { color:#1e293b; background:#fff; font-size:14px; }

@media (max-width:576px) {
  .card-header { padding:10px 14px !important; }
  #branchSelectHeader { font-size:15px; max-width:100%; }
  .card-header-actions { width:100%; justify-content:flex-start; }
  .card-header .btn-light { height:32px; width:32px; padding:0; font-size:15px; }
}

/* ── Table alignment (identical to Branch Products) ─────────────── */
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

/* ── Shop value modal metrics ─────────────────────────────────────── */
.sv-metric { background:#eef0f7; border-radius:8px; padding:10px 12px; text-align:center; }
.sv-metric .sv-label { font-size:11px; color:#6c757d; margin-bottom:4px; }
.sv-metric .sv-value { font-size:20px; font-weight:600; }

/* ── Cloud icon + badge ─────────────────────────────────────────── */
#cloudBtn .cloud-count { position:absolute; top:-5px; right:-5px; background:#dc2626; color:#fff; border-radius:50%; font-size:10px; font-weight:700; min-width:16px; height:16px; line-height:16px; text-align:center; padding:0 3px; display:none; box-shadow:0 0 0 1.5px #fff; }
#cloudBtn .cloud-count.show { display:block; }
#cloudBtn.has-queue i { color:#f59e0b !important; }

/* ── Queued row state ───────────────────────────────────────────── */
tr.row-queued { background:#fffbeb !important; }
.queued-badge { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; background:#f59e0b; color:#fff; border-radius:10px; padding:2px 7px; margin-left:6px; vertical-align:middle; }
.queued-badge.del { background:#dc2626; }

/* ── Cloud queue modal ──────────────────────────────────────────── */
.cq-item { display:flex; align-items:center; gap:10px; padding:10px 14px; border:1px solid #eef0f7; border-radius:8px; margin-bottom:8px; background:#fff; }
.cq-item:last-child { margin-bottom:0; }
.cq-icon { width:34px; height:34px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
.cq-icon-edit { background:#eff6ff; color:#1d4ed8; }
.cq-icon-del  { background:#fef2f2; color:#dc2626; }
.cq-body { flex:1; min-width:0; }
.cq-name { font-size:13px; font-weight:700; color:#1e293b; }
.cq-detail { font-size:11px; color:#6c757d; margin-top:1px; }
.cq-value { font-size:13px; font-weight:700; white-space:nowrap; }
.cq-value.pos { color:#16a34a; }
.cq-value.neg { color:#dc2626; }
.cq-remove { color:#94a3b8; cursor:pointer; font-size:16px; margin-left:6px; flex-shrink:0; }
.cq-remove:hover { color:#dc2626; }
.cq-empty { padding:40px 20px; text-align:center; color:#94a3b8; }
.cq-total-bar { background:#eef0f7; border-radius:8px; padding:12px 16px; display:flex; justify-content:space-between; align-items:center; margin-top:12px; }
.cq-total-label { font-size:12px; color:#6c757d; font-weight:600; }
.cq-total-value { font-size:20px; font-weight:700; }

/* ── Edit modal price cards ──────────────────────────────────────── */
.price-source-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.psc { border:1.5px solid #e2e6f0; border-radius:8px; padding:9px 12px; cursor:pointer; transition:border-color .15s; user-select:none; background:#f4f5f7; }
.psc-active-base   { border-color:#059669 !important; }
.psc-active-branch { border-color:#1d4ed8 !important; }
.psc-dot { width:8px; height:8px; border-radius:50%; display:inline-block; margin-right:5px; }
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
.edit-section { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.8px; color:#94a3b8; margin:16px 0 8px; display:flex; align-items:center; gap:6px; }
.edit-section::after { content:''; flex:1; height:1px; background:#e9ecef; }
</style>

<div class="progress" id="progressBar" role="progressbar" style="height:8px;display:none">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
      <form method="POST" action="{{ route('retail.operations.update.filters') }}" id="headerBranchForm" style="margin:0;display:inline;">
        @csrf
        <input type="hidden" name="user_id" value="{{ Auth::id() }}">
        <select name="branch_id" id="branchSelectHeader" onchange="document.getElementById('headerBranchForm').submit()">
          <option value="" hidden>{{ $selectedBranch ? $selectedBranch->name : '— Select Branch —' }}</option>
          @foreach($branches as $b)
            <option value="{{ $b->id }}" {{ ($pref && $pref->branch_id == $b->id) ? 'selected' : '' }}>{{ $b->name }}</option>
          @endforeach
        </select>
      </h4>
    </form>
    <div class="card-header-actions">
      @if($selectedBranch)
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="cloudBtn" title="Offline queue">
        <i class="ri-cloud-line"></i>
        <span class="cloud-count" id="cloudCount">0</span>
      </a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="shopValueBtn" title="Shop Value"><i class="ri-store-2-line"></i></a>
      @endif
    </div>
  </div>

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
          <th>Product Name</th>
          <th>Code</th>
          <th>Unit</th>
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
            $displayPrice = $sellIsBranch ? $bp->selling_price : $bp->bp_sell;
          @endphp
          <tr id="{{ $row }}" data-id="{{ $bp->id }}">
            <td data-field="name">&nbsp;{{ $bp->name }}</td>
            <td data-field="code">{{ $bp->code ?? '—' }}</td>
            <td data-field="unit">{{ $bp->unit }}</td>
            <td data-field="stock"><span class="qty-cell {{ $stockClass }}">{{ number_format($sq, 2) }}</span></td>
            <td data-field="price">
              <span class="price-cell {{ $sellIsBranch ? 'price-branch' : 'price-base' }}">
                {{ number_format($displayPrice, 2) }}
              </span>
            </td>
            <td data-field="batch">{{ $bp->batch_number ?? '—' }}</td>
            <td data-field="expiry">{{ $bp->expiry_date ?? '—' }}</td>
            <td>
              <a href="#" class="editDataBtn"
                 data-id="{{ $bp->id }}" data-row="{{ $row }}"
                 data-name="{{ $bp->name }}" data-code="{{ $bp->code }}" data-unit="{{ $bp->unit }}"
                 data-sell="{{ $bp->selling_price }}" data-stock="{{ $bp->stock_quantity }}"
                 data-sell-is-branch="{{ $sellIsBranch ? 1 : 0 }}"
                 data-bp-sell="{{ $bp->bp_sell }}">
                <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px"></i>
              </a>
              <a href="#" class="deleteDataBtn"
                 data-id="{{ $bp->id }}" data-row="{{ $row }}" data-label="{{ $bp->name }}"
                 data-unit="{{ $bp->unit }}"
                 data-sell="{{ $bp->selling_price }}" data-stock="{{ $bp->stock_quantity }}"
                 data-sell-is-branch="{{ $sellIsBranch ? 1 : 0 }}" data-bp-sell="{{ $bp->bp_sell }}">
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

{{-- ══ SHOP VALUE MODAL ══ --}}
<div class="modal fade" id="shopValueModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue"><h5 class="modal-title mh-title"><i class="ri-store-2-line"></i> Shop Value</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" style="padding:18px 20px !important;">
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:18px;">
        <div class="sv-metric"><div class="sv-label">Products</div><div class="sv-value" style="color:#4B5EBD;">{{ $branchProducts->count() }}</div></div>
        <div class="sv-metric"><div class="sv-label">Active</div><div class="sv-value" style="color:#198754;">{{ $activeCount }}</div></div>
        <div class="sv-metric"><div class="sv-label">Low / Zero</div><div class="sv-value" style="color:#d97706;">{{ $lowStockCount + $zeroCount }}</div></div>
      </div>
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <tbody>
          <tr style="border-bottom:1px solid #e9ecef;"><td style="padding:8px 0;color:#6c757d;font-weight:600;width:140px;">Branch</td><td style="padding:8px 0;font-weight:600;color:#1e293b;">{{ $selectedBranch->name ?? '—' }}</td></tr>
          <tr style="border-bottom:1px solid #e9ecef;"><td style="padding:8px 0;color:#6c757d;font-weight:600;">Category</td><td style="padding:8px 0;color:#1e293b;">{{ $branchCategory->category ?? '—' }}</td></tr>
          <tr style="border-bottom:1px solid #e9ecef;"><td style="padding:8px 0;color:#6c757d;font-weight:600;">Zero stock</td><td style="padding:8px 0;color:#dc2626;font-weight:600;">{{ $zeroCount }}</td></tr>
          <tr style="border-bottom:1px solid #e9ecef;"><td style="padding:8px 0;color:#6c757d;font-weight:600;">Low stock</td><td style="padding:8px 0;color:#d97706;font-weight:600;">{{ $lowStockCount }}</td></tr>
          <tr><td style="padding:12px 0 4px;color:#6c757d;font-weight:600;">Total shop value</td><td style="padding:12px 0 4px;font-size:22px;font-weight:700;color:#4B5EBD;">MWK {{ number_format($shopValue, 0) }}</td></tr>
          <tr><td style="padding:4px 0;color:#6c757d;font-weight:600;">Valuation date</td><td style="padding:4px 0;color:#94a3b8;font-size:12px;">{{ now()->toDateString() }}</td></tr>
          <tr><td style="padding:8px 0 0;color:#94a3b8;font-size:11px;" colspan="2"><i class="ri-information-line me-1"></i>Value shown does not include any changes still sitting in your offline queue.</td></tr>
        </tbody>
      </table>
    </div>
    <div class="modal-footer" style="padding:10px 20px 14px;"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button></div>
  </div></div>
</div>

{{-- ══ EDIT MODAL (queues to offline storage) ══ --}}
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-edit-box-line"></i> <span id="editModalName"></span></h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:16px 18px 10px !important;">
        <input type="hidden" id="editId"><input type="hidden" id="editRow">

        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:12px">Quantity</label>
          <input class="form-control form-control-sm" type="text" inputmode="decimal" id="editStockQty" autocomplete="off" />
        </div>

        <div class="edit-section"><i class="ri-coin-line"></i>Selling Price Source</div>
        <div class="price-source-grid mb-2">
          <div class="psc psc-active-base" id="editPscBase" onclick="setEditPriceSource('base')">
            <div class="psc-label psc-label-base"><span class="psc-dot psc-dot-base"></span>Base catalogue</div>
            <div class="psc-val psc-val-base" id="editPscBaseVal">—</div>
          </div>
          <div class="psc" id="editPscBranch" onclick="setEditPriceSource('branch')">
            <div class="psc-label psc-label-branch"><span class="psc-dot psc-dot-branch"></span>This branch only</div>
            <div class="psc-val psc-val-branch" id="editPscBranchVal">—</div>
          </div>
        </div>
        <div id="editBranchPriceFields" style="display:none;">
          <label class="form-label fw-semibold" style="font-size:12px">Branch Selling Price <span class="text-danger">*</span></label>
          <input class="form-control form-control-sm" type="text" inputmode="decimal" id="editSellPrice" placeholder="0.00" autocomplete="off" />
        </div>
      </div>
      <div class="modal-footer" style="padding:10px 18px 14px;justify-content:flex-end;gap:8px;">
        <a href="#" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</a>
        <a href="#" class="btn btn-primary btn-sm" id="queueEditBtn"><i class="ri-cloud-line me-1"></i> Queue Change</a>
      </div>
    </div>
  </div>
</div>

{{-- ══ DELETE MODAL (queues to offline storage) ══ --}}
<div class="modal fade" id="deleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-danger"><h5 class="modal-title mh-title"><i class="ri-delete-bin-line"></i> Remove from Branch</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body text-center py-4">
      <i class="ri-error-warning-line text-danger" style="font-size:60px"></i>
      <h5 class="mt-2 mb-1">Remove <span id="deleteLabel" class="text-danger"></span>?</h5>
      <p style="font-size:13px;color:#6c757d;margin-bottom:0;">This will be queued and only removed once you upload.</p>
      <input type="hidden" id="deleteId"><input type="hidden" id="deleteRow">
    </div>
    <div class="modal-footer justify-content-center gap-2" style="padding:10px 20px 18px;">
      <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Keep</button>
      <button type="button" class="btn btn-danger btn-sm px-4" id="queueDeleteBtn"><i class="ri-cloud-line me-1"></i> Queue Removal</button>
    </div>
  </div></div>
</div>

{{-- ══ CLOUD QUEUE MODAL ══ --}}
<div class="modal fade" id="cloudModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue"><h5 class="modal-title mh-title"><i class="ri-cloud-line"></i> Offline Queue — <span id="cqCount">0</span></h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" style="padding:16px 18px !important;">
      <div id="cqList"></div>
      <div class="cq-total-bar">
        <span class="cq-total-label">Total Value Impact</span>
        <span class="cq-total-value" id="cqTotal">MWK 0.00</span>
      </div>
    </div>
    <div class="modal-footer" style="padding:10px 18px 14px;justify-content:space-between;">
      <button type="button" class="btn btn-outline-success btn-sm" id="exportQueueBtn"><i class="ri-file-excel-2-line me-1"></i> Export Excel</button>
      <button type="button" class="btn btn-success btn-sm" id="uploadQueueBtn"><i class="ri-upload-2-line me-1"></i> Upload All</button>
    </div>
  </div></div>
</div>

@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
$(document).ready(function () {

    toastr.options = { closeButton:true, progressBar:true, showMethod:'slideDown', timeOut:5000, allowHtml:true };

    var BRANCH_ID  = {{ $selectedBranch->id ?? 'null' }};
    var QUEUE_KEY  = 'netacube_bp_queue_' + BRANCH_ID;

    function purifyFloat(raw) {
        var s = String(raw || '').replace(/[^0-9.\-]/g, '');
        var parts = s.split('.');
        if (parts.length > 2) s = parts[0] + '.' + parts.slice(1).join('');
        return s;
    }

    function fmtMoney(n) { return 'MWK ' + parseFloat(n || 0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}); }
    function fmtNum(n)   { return parseFloat(n || 0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}); }

    // ── Queue storage helpers ──────────────────────────────────────────
    function getQueue() {
        try { return JSON.parse(localStorage.getItem(QUEUE_KEY)) || []; }
        catch (e) { return []; }
    }
    function saveQueue(q) { localStorage.setItem(QUEUE_KEY, JSON.stringify(q)); }

    function valueImpact(item) {
        if (item.type === 'delete') return -(item.old_qty * item.old_price);
        return (item.new_qty * item.new_price) - (item.old_qty * item.old_price);
    }

    function refreshCloudBadge() {
        var q = getQueue();
        var badge = $('#cloudCount');
        if (q.length > 0) {
            badge.text(q.length).addClass('show');
            $('#cloudBtn').addClass('has-queue');
        } else {
            badge.text('').removeClass('show');
            $('#cloudBtn').removeClass('has-queue');
        }
    }

    // type: 'edit' | 'delete'. qty: for 'edit', the queued (new) quantity to display in the badge.
    function markRowQueued(id, type, qty) {
        var row = $('tr[data-id="' + id + '"]');
        row.addClass('row-queued');
        row.find('.queued-badge').remove();
        var badgeCls = type === 'delete' ? 'queued-badge del' : 'queued-badge';
        var label    = type === 'delete' ? 'Queued: Delete' : 'Queued: Edit (' + fmtNum(qty) + ')';
        row.find('td[data-field="name"]').append('<span class="' + badgeCls + '">' + label + '</span>');
    }

    // Restore visual "queued" state on page load
    (function restoreQueuedRows() {
        getQueue().forEach(function(item) { markRowQueued(item.id, item.type, item.new_qty); });
    })();
    refreshCloudBadge();

    @if($selectedBranch)

    $('#maintable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[100, 250, 500, -1], [100, 250, 500, 'All']],
        fixedColumns: { leftColumns: 1 },
        scrollX: true,
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: 0,      className: 'text-start'  }
        ]
    });

    $('#shopValueBtn').on('click', function(e) { e.preventDefault(); $('#shopValueModal').modal('show'); });

    // ── Edit modal ──────────────────────────────────────────────────────
    window._editPriceSource = 'base';

    window.setEditPriceSource = function(src) {
        window._editPriceSource = src;
        if (src === 'base') {
            $('#editPscBase').addClass('psc-active-base').removeClass('psc-active-branch');
            $('#editPscBranch').removeClass('psc-active-base psc-active-branch');
            $('#editBranchPriceFields').hide();
        } else {
            $('#editPscBranch').addClass('psc-active-branch').removeClass('psc-active-base');
            $('#editPscBase').removeClass('psc-active-base psc-active-branch');
            $('#editBranchPriceFields').show();
            setTimeout(function() { $('#editSellPrice').focus(); }, 50);
        }
    };

    $('#tbody').on('click', '.editDataBtn', function(e) {
        e.preventDefault();
        var b = $(this);
        var sellIsBr = parseInt(b.data('sell-is-branch')) === 1;
        var bpSell   = b.data('bp-sell') || 0;
        var brSell   = sellIsBr ? (b.data('sell') || 0) : '';

        $('#editId').val(b.data('id'));
        $('#editRow').val(b.data('row'));
        $('#editModalName').text(b.data('name'));
        $('#editStockQty').val(parseFloat(b.data('stock') || 0).toFixed(2));
        $('#editPscBaseVal').text(fmtMoney(bpSell));
        $('#editPscBranchVal').text(sellIsBr ? fmtMoney(brSell) : '—');
        $('#editSellPrice').val(sellIsBr ? parseFloat(brSell).toFixed(2) : '');
        setEditPriceSource(sellIsBr ? 'branch' : 'base');

        $('#editDataModal').data('meta', {
            name: b.data('name'), code: b.data('code') || '', unit: b.data('unit') || 'Each',
            bpSell: parseFloat(bpSell), oldSell: sellIsBr ? parseFloat(brSell) : parseFloat(bpSell),
            oldQty: parseFloat(b.data('stock') || 0)
        });
        $('#editDataModal').modal('show');
    });

    $('#queueEditBtn').on('click', function() {
        var meta = $('#editDataModal').data('meta');
        var id   = parseInt($('#editId').val());
        var row  = $('#editRow').val();
        var newQty = parseFloat(purifyFloat($('#editStockQty').val())) || 0;

        var useBranch = (window._editPriceSource === 'branch');
        var newPrice;
        if (useBranch) {
            var sellVal = purifyFloat($('#editSellPrice').val());
            if (!sellVal || parseFloat(sellVal) < 0) { toastr.warning('Branch selling price is required.', 'Required'); $('#editSellPrice').focus(); return; }
            newPrice = parseFloat(sellVal);
        } else {
            newPrice = meta.bpSell;
        }

        var q = getQueue().filter(function(item) { return item.id !== id; }); // replace any existing queued entry for this product
        q.push({
            type: 'edit', id: id, row: row,
            name: meta.name, code: meta.code, unit: meta.unit,
            old_qty: meta.oldQty, new_qty: newQty,
            old_price: meta.oldSell, new_price: newPrice,
            selling_price: useBranch ? newPrice : null, // null = revert to base
            queued_at: new Date().toISOString()
        });
        saveQueue(q);
        markRowQueued(id, 'edit', newQty);
        refreshCloudBadge();
        $('#editDataModal').modal('hide');
    });

    // ── Delete modal ────────────────────────────────────────────────────
    $('#tbody').on('click', '.deleteDataBtn', function(e) {
        e.preventDefault();
        var b = $(this);
        $('#deleteLabel').text(b.data('label'));
        $('#deleteId').val(b.data('id'));
        $('#deleteRow').val(b.data('row'));
        var sellIsBr = parseInt(b.data('sell-is-branch')) === 1;
        $('#deleteModal').data('meta', {
            name: b.data('label'),
            unit: b.data('unit') || '',
            oldQty: parseFloat(b.data('stock') || 0),
            oldPrice: sellIsBr ? parseFloat(b.data('sell') || 0) : parseFloat(b.data('bp-sell') || 0)
        });
        $('#deleteModal').modal('show');
    });

    $('#queueDeleteBtn').on('click', function() {
        var meta = $('#deleteModal').data('meta');
        var id   = parseInt($('#deleteId').val());
        var row  = $('#deleteRow').val();

        var q = getQueue().filter(function(item) { return item.id !== id; });
        q.push({
            type: 'delete', id: id, row: row,
            name: meta.name, unit: meta.unit, old_qty: meta.oldQty, old_price: meta.oldPrice,
            new_qty: 0, new_price: meta.oldPrice,
            queued_at: new Date().toISOString()
        });
        saveQueue(q);
        markRowQueued(id, 'delete');
        refreshCloudBadge();
        $('#deleteModal').modal('hide');
    });

    // ── Cloud queue modal ───────────────────────────────────────────────
    function renderCloudModal() {
        var q = getQueue();
        $('#cqCount').text(q.length);

        if (!q.length) {
            $('#cqList').html('<div class="cq-empty"><i class="ri-cloud-off-line" style="font-size:36px;display:block;margin-bottom:8px;"></i>No pending changes.</div>');
            $('#cqTotal').text(fmtMoney(0));
            return;
        }

        var html = '', total = 0;
        q.forEach(function(item, idx) {
            var vi = valueImpact(item);
            total += vi;
            var viClass = vi >= 0 ? 'pos' : 'neg';
            var viSign  = vi >= 0 ? '+' : '';

            if (item.type === 'delete') {
                html += `<div class="cq-item">
                    <div class="cq-icon cq-icon-del"><i class="ri-delete-bin-line"></i></div>
                    <div class="cq-body">
                        <div class="cq-name">${item.name}</div>
                        <div class="cq-detail">Remove from branch · was ${fmtNum(item.old_qty)} @ ${fmtMoney(item.old_price)}</div>
                    </div>
                    <div class="cq-value ${viClass}">${viSign}${fmtMoney(vi)}</div>
                    <i class="ri-close-circle-line cq-remove" onclick="removeQueueItem(${idx})"></i>
                </div>`;
            } else {
                var qtyChanged   = item.old_qty !== item.new_qty;
                var priceChanged = item.old_price !== item.new_price;
                var detail = [];
                if (qtyChanged)   detail.push('Qty ' + fmtNum(item.old_qty) + ' → ' + fmtNum(item.new_qty));
                if (priceChanged) detail.push('Price ' + fmtMoney(item.old_price) + ' → ' + fmtMoney(item.new_price));
                if (!detail.length) detail.push('No numeric change');

                html += `<div class="cq-item">
                    <div class="cq-icon cq-icon-edit"><i class="ri-edit-box-line"></i></div>
                    <div class="cq-body">
                        <div class="cq-name">${item.name}</div>
                        <div class="cq-detail">${detail.join(' · ')}</div>
                    </div>
                    <div class="cq-value ${viClass}">${viSign}${fmtMoney(vi)}</div>
                    <i class="ri-close-circle-line cq-remove" onclick="removeQueueItem(${idx})"></i>
                </div>`;
            }
        });

        $('#cqList').html(html);
        $('#cqTotal').text(fmtMoney(total)).removeClass('pos neg').addClass(total >= 0 ? 'pos' : 'neg');
    }

    function exportQueueToExcel() {
        var q = getQueue();
        if (!q.length) { toastr.warning('Nothing queued to export.', 'Empty queue'); return; }

        var rows = q.map(function(item) {
            return {
                'Product Name': item.name,
                'Unit': item.unit || '',
                'Price': item.type === 'delete' ? item.old_price : item.new_price,
                'Qty Before': item.old_qty,
                'Qty After': item.new_qty
            };
        });

        var ws = XLSX.utils.json_to_sheet(rows);
        ws['!cols'] = [{ wch: 28 }, { wch: 10 }, { wch: 12 }, { wch: 12 }, { wch: 12 }];

        var wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Offline Queue');

        var branchName = '{{ $selectedBranch->name ?? "branch" }}'.replace(/[^a-z0-9]+/gi, '_');
        var fname = 'offline_queue_' + branchName + '_' + new Date().toISOString().slice(0, 10) + '.xlsx';
        XLSX.writeFile(wb, fname);
    }

    $('#exportQueueBtn').on('click', exportQueueToExcel);

    window.removeQueueItem = function(idx) {
        var q = getQueue();
        var removed = q.splice(idx, 1)[0];
        saveQueue(q);
        if (removed) {
            var row = $('tr[data-id="' + removed.id + '"]');
            row.removeClass('row-queued');
            row.find('.queued-badge').remove();
        }
        refreshCloudBadge();
        renderCloudModal();
    };

    $('#cloudBtn').on('click', function(e) { e.preventDefault(); renderCloudModal(); $('#cloudModal').modal('show'); });

    $('#uploadQueueBtn').on('click', function() {
        var q = getQueue();
        if (!q.length) { toastr.warning('Nothing queued to upload.', 'Empty queue'); return; }

        var payload = q.map(function(item) {
            return {
                type: item.type,
                id: item.id,
                selling_price: item.type === 'edit' ? item.selling_price : null,
                stock_quantity: item.type === 'edit' ? item.new_qty : null
            };
        });

        var self = $(this); self.prop('disabled', true);
        $.ajaxSetup({ headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type: 'POST', url: '{{ route("retail.operations.branchproducts.offline.sync") }}', timeout: 120000,
            data: { branch_id: BRANCH_ID, items: payload, _token: '{{ csrf_token() }}' },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Synced');
                    if (data.errors && data.errors.length) {
                        data.errors.forEach(function(e) { toastr.error(e, 'Sync issue'); });
                    }
                    localStorage.removeItem(QUEUE_KEY);
                    $('#cloudModal').modal('hide');
                    setTimeout(function() { location.reload(); }, 400);
                } else {
                    toastr.error(data.error || 'Sync failed.', 'Error');
                }
            },
            error: function(xhr) {
                toastr.error('Upload failed — check your connection and try again.', 'Error');
            }
        });
    });

    @endif
});
</script>
@endsection