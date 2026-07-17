@extends('sales.retail.dashboard')
@section('content')

@php
    use Carbon\Carbon;

    // Category for this page — drives every query below.
    $CATEGORY = 'Regular';

    $userId = Auth::id();
    $pref   = DB::connection('tenant')->table('user_filters')->where('user_id', $userId)->first();

    // Saved branch filter always wins over the user's assigned branch —
    // same rule used everywhere else in the retail module.
    $branchId = ($pref && $pref->branch_id) ? $pref->branch_id : Auth::user()->branch;
    $branch   = $branchId ? DB::connection('tenant')->table('branches')->find($branchId) : null;

    // Suppliers eligible for this branch: sector = Retail AND supplier's
    // category matches the branch's own category.
    $suppliers = collect();
    $eligibleSupplierIds = [];

    if ($branch && $branch->category) {
        $suppliers = DB::connection('tenant')->table('suppliers')
            ->where('status', 'active')
            ->where('sector', 'Retail')
            ->where('category', $branch->category)
            ->orderBy('name')
            ->get(['id', 'name']);

        $eligibleSupplierIds = $suppliers->pluck('id')->all();
    }

    // Order-page supplier filter — selected from the blue header bar.
    $savedSupplierId = $pref->supplier_id ?? null;

    $products = collect();

    if (!empty($eligibleSupplierIds)) {
        $query = DB::connection('tenant')
            ->table('retail_base_products as bp')
            ->leftJoin('retail_branch_products as rbp', function ($j) use ($branchId) {
                $j->on('rbp.base_product_id', '=', 'bp.id')->where('rbp.branch_id', '=', $branchId);
            })
            ->where('bp.is_product', 1);

        if ($savedSupplierId && in_array((int) $savedSupplierId, $eligibleSupplierIds, true)) {
            $query->where('bp.supplier', (int) $savedSupplierId);
        } else {
            $query->whereIn('bp.supplier', $eligibleSupplierIds);
        }

        $products = $query->select(
            'bp.id', 'bp.name', 'bp.code', 'bp.unit', 'bp.supplier',
            DB::raw('COALESCE(rbp.stock_quantity, 0) as stock_quantity'),
            DB::raw('COALESCE(rbp.selling_price, bp.selling_price) as selling_price'),
            'rbp.reorder_point'
        )->orderBy('bp.name')->get();
    }

    // No more batches — retail_orders has at most ONE row per
    // branch+category+product_id, and this IS that row. A product ordered
    // last week still prefills here today, because it's literally the
    // same database row that also shows up in History; there's no
    // "pending vs closed" distinction gating this anymore.
    $openLinesMap     = [];
    $currentSummary   = ['line_count' => 0];

    if ($branchId) {
        $currentLines = DB::connection('tenant')->table('retail_orders')
            ->where('branch_id', $branchId)
            ->where('category', $CATEGORY)
            ->get();

        $openLinesMap = $currentLines->keyBy('product_id')->all();

        $currentSummary = [
            'line_count' => $currentLines->count(),
        ];
    }
@endphp


<style>
.card-header {
  padding: 0.5rem 1.5rem !important;
  background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff;
  border-radius: 10px 10px 0 0 !important; flex-wrap: wrap; gap: 8px;
}
.card-body  { padding: 0 1.5rem 1.5rem 1.5rem !important; }
.card       { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; }
.card-header h4 { color:#fff; font-weight:600; margin-bottom:0; display:flex; align-items:center; }
.card-header h4 i { margin-right: 0.25rem; }
.card-header .btn-light {
  height:28px; padding:0 10px; position:relative;
  display:flex; align-items:center; justify-content:center; line-height:1;
}
.card-header .btn-light:hover { background-color:#f8f9fa; transition:background-color 0.2s; }
.card-header .btn-light.has-queue i { color:#f59e0b !important; }
.hdr-badge {
  position:absolute; top:-6px; right:-6px; background:#dc2626; color:#fff;
  font-size:10px; font-weight:700; border-radius:50%; min-width:16px; height:16px;
  display:flex; align-items:center; justify-content:center; padding:0 3px;
}

.dt-buttons .btn {
  background: transparent !important; background-image: none !important;
  box-shadow: none !important; border-color: #5bc0de; color: #5bc0de;
}
.dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }

/* ── Tab bar (Regular / History) ── */
.tab-header-container { background:#f8f9fa; border-bottom:1px solid #dee2e6; overflow-x:auto; }
.nav-pills { flex-wrap:nowrap; }
.nav-pills .nav-link {
  border-radius:0 !important; padding:.5rem 1rem;
  font-weight:500; font-size:12px; color:#6c757d;
  border-bottom:3px solid transparent; transition:all .2s; white-space:nowrap;
}
.nav-pills .nav-link:hover  { background:#e9ecef; color:#4B5EBD; }
.nav-pills .nav-link.active {
  background:transparent !important; color:#4B5EBD !important;
  border-bottom-color:#4B5EBD; font-weight:600;
}
.nav-pills .nav-link i { font-size:.95rem; margin-right:.3rem; }

.stock-ok   { color:#16a34a; font-weight:700; }
.stock-low  { color:#d97706; font-weight:700; }
.stock-zero { color:#dc2626; font-weight:700; }

.no-branch-wrap { padding:48px 20px; text-align:center; color:#94a3b8; }
.no-branch-wrap i { font-size:52px; display:block; margin-bottom:12px; color:#c8d0ed; }
.no-branch-wrap h5 { color:#64748b; font-weight:600; }

.no-supplier-wrap { padding:32px 20px; text-align:center; color:#94a3b8; }
.no-supplier-wrap i { font-size:40px; display:block; margin-bottom:10px; color:#c8d0ed; }

/* ── Supplier select (header) — plain text look, no icon, with a
   down-angle caret indicating it's a dropdown. ── */
.supplier-select-wrap { display:inline-flex; align-items:center; gap:4px; min-width:0; }
#supplierSelectHeader {
  border:none; background:transparent; color:#fff; font-size:18px; font-weight:600;
  cursor:pointer; padding:0; outline:none; max-width:280px;
  appearance:none; -webkit-appearance:none; -moz-appearance:none;
}
#supplierSelectHeader option { color:#1e293b; background:#fff; font-size:14px; }
#supplierSelectHeader:disabled { cursor:not-allowed; opacity:.7; }
.supplier-select-caret { color:#fff; font-size:18px; line-height:1; pointer-events:none; }

.mh-blue   { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-title  { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close  { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }

.order-badge { font-size:11px; font-weight:700; padding:3px 8px; border-radius:20px; }
.badge-Regular   { background:#ecfdf5; color:#059669; }
.share-link-box { display:flex; gap:8px; }
.share-link-box input { flex:1; }

/* ── Table alignment — first column right, everything else center.
   Applies to the main product table AND every table inside a modal. ── */
#maintable thead th, table.dataTable thead th, .dl-table thead th { text-align:center !important; vertical-align:middle !important; }
#maintable thead th:first-child, table.dataTable thead th:first-child, .dl-table thead th:first-child { text-align:left !important; }
#maintable tbody td, table.dataTable tbody td, .dl-table tbody td { text-align:center !important; vertical-align:middle !important; }
#maintable tbody td:first-child, table.dataTable tbody td:first-child, .dl-table tbody td:first-child { text-align:left !important; }

/* Quantity input states */
#maintable tbody td:last-child { text-align:center !important; }
.qtyInput { width:160px; max-width:100%; text-align:center; margin:0 auto; }
.qtyInput.has-qty   { border-color:#4B5EBD; background:#eef0ff; font-weight:600; } /* synced — already uploaded to the server */
.qtyInput.queued-local { border-color:#f59e0b !important; background:#fff7ed !important; font-weight:600; } /* queued locally — not yet synced */
.qtyInput.qty-bad   { border-color:#dc2626 !important; }

/* ── Cloud (offline queue) modal ── */
.cq-empty { text-align:center; padding:28px 16px; color:#94a3b8; }
.cq-empty i { font-size:36px; display:block; margin-bottom:8px; color:#c8d0ed; }

/* Fixed-height inner scroll area, matching branchproducts .sbp-table-wrap —
   the dialog itself never scrolls; only this wrapper does. */
.cq-table-wrap { max-height:420px; overflow-y:auto; border:1px solid #e2e6f0; border-radius:8px; }

/* ── Download modal ── */
.vo-tabs .nav-link { font-size:13px; font-weight:600; color:#64748b; }
.vo-tabs .nav-link.active { color:#4B5EBD; }
.vo-share-locked { text-align:center; padding:28px 16px; color:#94a3b8; }
.vo-share-locked i { font-size:36px; display:block; margin-bottom:8px; color:#fdba74; }
</style>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
      <form method="POST" action="{{ route('retail.sales.update.filters') }}" id="headerSupplierForm" style="margin:0;display:inline;">
        @csrf
        <input type="hidden" name="user_id" value="{{ Auth::id() }}">
        <input type="hidden" name="branch_id" value="{{ $branchId }}">
        <div class="supplier-select-wrap">
          <select name="supplier_id" id="supplierSelectHeader" onchange="document.getElementById('headerSupplierForm').submit()" {{ $suppliers->isEmpty() ? 'disabled' : '' }}>
            @if($suppliers->isEmpty())
              <option value="">No Suppliers</option>
            @else
              <option value="" {{ !$savedSupplierId ? 'selected' : '' }}>All Suppliers</option>
              @foreach($suppliers as $s)
                <option value="{{ $s->id }}" {{ (string) $savedSupplierId === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
              @endforeach
            @endif
          </select>
          <i class="ri-arrow-down-s-line supplier-select-caret"></i>
        </div>
      </form>
    </h4>
    <div class="d-flex align-items-center" style="gap:4px;">
      <a href="#" class="btn btn-light text-dark fs-16 mx-1" id="cloudBtn" title="Items waiting to upload">
        <i class="ri-cloud-line"></i>
        <span class="hdr-badge d-none" id="pendingBadge">0</span>
      </a>
      <a href="#" class="btn btn-light text-dark fs-16 mx-1" id="downloadBtn" title="Current order / Download">
        <i class="ri-download-2-line"></i>
      </a>
    </div>
  </div>

      <div class="tab-header-container">
    <ul class="nav nav-pills px-2 pt-1">
      <li class="nav-item">
        <a class="nav-link active" href="{{ route('retail.orders.regular') }}"><i class="ri-repeat-line"></i> Regular</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="{{ route('retail.orders.regular.history') }}"><i class="ri-history-line"></i> History</a>
      </li>
    </ul>
  </div>

  <div class="card-body">

    @if(!$branchId)
      <div class="no-branch-wrap">
        <i class="ri-store-line"></i>
        <h5>No Branch Assigned</h5>
        <p style="font-size:13px;">Your user account is not currently assigned to a branch. Contact an administrator to get a branch assigned.</p>
      </div>
    @else

      @if($suppliers->isEmpty())
        <div class="no-supplier-wrap">
          <i class="ri-truck-line"></i>
          <p style="font-size:13px;">No suppliers are configured for this branch's category yet. Ask an administrator to add a Retail supplier for this category before placing an order.</p>
        </div>
      @else

        <table id="maintable" class="table table-sm table-striped row-border order-column w-100 mt-3">
          <thead style="background-color:#e2e2e9">
            <tr>
              <th>Product Name</th>
              <th>Unit</th>
              <th>Price</th>
              <th>Stock</th>
              <th>Qty to Order</th>
            </tr>
          </thead>
          <tbody id="tbody">
            @foreach($products as $product)
              @php
                $existingLine = $openLinesMap[$product->id] ?? null;
                $stock = (float) $product->stock_quantity;
                $rp    = (float) ($product->reorder_point ?? 0);
                $stockCls = $stock <= 0 ? 'stock-zero' : ($stock <= $rp ? 'stock-low' : 'stock-ok');
              @endphp
              <tr id="prod-row-{{ $product->id }}">
                <td>{{ $product->name }}</td>
                <td>{{ $product->unit ?? '—' }}</td>
                <td>{{ number_format($product->selling_price ?? 0, 2) }}</td>
                <td><span class="{{ $stockCls }}">{{ number_format($stock, 0) }}</span></td>
                <td>
                  <input type="text"
                         class="form-control form-control-sm qtyInput {{ $existingLine ? 'has-qty' : '' }}"
                         maxlength="60"
                         data-product-id="{{ $product->id }}"
                         data-product-name="{{ $product->name }}"
                         data-unit="{{ $product->unit }}"
                         data-price="{{ (float) ($product->selling_price ?? 0) }}"
                         data-stock="{{ $stock }}"
                         data-supplier="{{ $product->supplier }}"
                         value="{{ $existingLine->quantity ?? '' }}">
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>

      @endif

    @endif

  </div>
</div>

</div></div></div>

{{-- ═══════════════════════════════ CLOUD / OFFLINE QUEUE MODAL ═══════════════════════════════ --}}
<div class="modal fade" id="cloudModal" data-bs-backdrop="static" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-cloud-line"></i> Waiting to Upload — <span id="cqCount">0</span></h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:16px 18px !important;">
        <div class="table-responsive cq-table-wrap">
          <table class="table table-sm dl-table">
            <thead style="background-color:#e2e2e9;">
              <tr><th>Product</th><th>Unit</th><th>Qty</th><th>Remove</th></tr>
            </thead>
            <tbody id="cqBody"><tr><td colspan="4" class="text-center text-muted py-3">Nothing queued yet.</td></tr></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer" style="padding:10px 18px 14px;justify-content:space-between;">
        <button type="button" class="btn btn-light text-danger btn-sm" id="cqClearBtn"><i class="ri-delete-bin-line me-1"></i> Clear</button>
        <button type="button" class="btn btn-success btn-sm" id="cqUploadBtn"><i class="ri-upload-2-line me-1"></i> Upload</button>
      </div>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════ DOWNLOAD MODAL ═══════════════════════════════ --}}
<div class="modal fade" id="downloadModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-download-2-line"></i> Regular Orders</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <ul class="nav nav-tabs vo-tabs px-3 pt-2" role="tablist">
          <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#dl-pending" type="button"><i class="ri-list-check-2"></i> Current Order</button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#dl-share" type="button"><i class="ri-download-2-line"></i> Download / Share</button>
          </li>
        </ul>
        <div class="tab-content p-3">
          <div class="tab-pane fade show active" id="dl-pending">
            <div class="table-responsive">
              <table id="dlPendingTable" class="table table-sm dl-table w-100">
                <thead style="background-color:#e2e2e9;">
                  <tr><th>Item</th><th>Unit</th><th>Price</th><th>Qty@Order</th><th>OrderQty</th><th>Date</th><th>Ordered By</th></tr>
                </thead>
                <tbody id="dlPendingBody"><tr><td colspan="7" class="text-center text-muted py-3">Loading…</td></tr></tbody>
              </table>
            </div>
          </div>
          <div class="tab-pane fade" id="dl-share">
            <div id="dlShareWrap"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════ SHARE LINK MODAL ═══════════════════════════════ --}}
<div class="modal fade" id="shareModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-share-line"></i> Share Order</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2" style="font-size:13px;">Anyone with this link can view the order — <strong>no login required</strong>. This link always shows the latest data — share it with your supplier.</p>
        <div class="share-link-box">
          <input type="text" id="shareLinkInput" class="form-control" readonly>
          <button class="btn btn-primary" id="shareCopyBtn"><i class="ri-file-copy-line"></i></button>
        </div>
        <button class="btn btn-link text-danger mt-2 p-0" id="shareRevokeBtn" style="font-size:12px;">Disable this link</button>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
$(function () {

// ─────────────────────────────────────────────────────────────────────────
// CONFIG / STATE
// ─────────────────────────────────────────────────────────────────────────
const TENANT       = '{{ request()->route("tenantName") }}';
const CATEGORY     = 'Regular';
const QTY_MAX_LEN  = 60;
const TODAY        = '{{ \Carbon\Carbon::today()->toDateString() }}';

const ROUTES = {
    sync: '{{ route("retail.orders.sync") }}',
    current: '{{ route("retail.orders.current") }}',
    download: '{{ url("/" . request()->route("tenantName") . "/sales/retail/orders/download") }}',
    linkGet: '{{ route("retail.orders.link.get") }}',
    linkRevoke: '{{ route("retail.orders.link.revoke") }}',
};
const QUEUE_KEY = 'retail_orders_queue_' + TENANT + '_' + CATEGORY;

$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
toastr.options = { closeButton:true, progressBar:true, showMethod:'slideDown', timeOut:4000 };

// ─────────────────────────────────────────────────────────────────────────
// DOWNLOAD (in-app views) — separate from the public share-link download,
// which is a plain same-origin anchor and already works fine on its own
// page. From inside the app the PDF link lives inside a Bootstrap modal
// and is rendered with target="_blank"; on installed/PWA contexts (and
// some in-app browsers) that silently fails to open a new tab, so the
// click never triggers anything. Fetching the file as a blob and driving
// the download from a temporary anchor sidesteps that entirely.
// ─────────────────────────────────────────────────────────────────────────
function downloadOrderPdfFromViews(url, fallbackName) {
    fetch(url, { credentials: 'same-origin' })
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status} ${res.statusText} — ${url}`);
            const disposition = res.headers.get('Content-Disposition') || '';
            const match = disposition.match(/filename="?([^"]+)"?/);
            const filename = match ? match[1] : fallbackName;
            return res.blob().then(blob => ({ blob, filename }));
        })
        .then(({ blob, filename }) => {
            const blobUrl = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = blobUrl;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(blobUrl);
        })
        .catch((err) => {
            console.error('PDF download failed:', err);
            toastr.error('Could not download the PDF. Please try again.');
        });
}


// ─────────────────────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────────────────────
function uuid() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
        const r = Math.random()*16|0, v = c === 'x' ? r : (r&0x3|0x8);
        return v.toString(16);
    });
}
function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

// ─────────────────────────────────────────────────────────────────────────
// LOCAL QUEUE — typing a quantity NEVER hits the database. It only updates
// a local queue (localStorage) and the cloud icon's badge count. Nothing
// reaches the server until the person opens the cloud modal and presses
// Upload.
// ─────────────────────────────────────────────────────────────────────────
function getQueue() {
    try { return JSON.parse(localStorage.getItem(QUEUE_KEY) || '[]'); }
    catch (e) { return []; }
}
function setQueue(arr) {
    localStorage.setItem(QUEUE_KEY, JSON.stringify(arr));
    refreshQueueBadge();
}
function refreshQueueBadge() {
    const n = getQueue().length;
    const $b = $('#pendingBadge');
    if (n > 0) { $b.text(n).removeClass('d-none'); $('#cloudBtn').addClass('has-queue'); }
    else { $b.addClass('d-none'); $('#cloudBtn').removeClass('has-queue'); }
}

// Adds/updates/removes a queued line for a catalog product, keyed by
// product_id so re-typing the same product's quantity replaces the queued
// entry instead of duplicating it. Blank quantity removes it.
function upsertQueueLine(payload) {
    let q = getQueue().filter(l => String(l.product_id) !== String(payload.product_id));
    if (String(payload.quantity || '').trim() !== '') {
        payload.client_uuid = uuid();
        q.push(payload);
    }
    setQueue(q);
}

function restoreQueueIntoInputs() {
    getQueue().forEach(l => {
        if (!l.product_id) return;
        const $inp = $('.qtyInput[data-product-id="' + l.product_id + '"]');
        if ($inp.length) $inp.val(l.quantity).removeClass('has-qty').addClass('queued-local');
    });
}

// ─────────────────────────────────────────────────────────────────────────
// QTY INPUT — local queue only, no autosave to the database.
// ─────────────────────────────────────────────────────────────────────────
$(document).on('blur', '.qtyInput', function () {
    const $input = $(this);
    const qty = $input.val().trim();
    if (qty.length > QTY_MAX_LEN) {
        $input.addClass('qty-bad');
        toastr.warning('Quantity is too long (max ' + QTY_MAX_LEN + ' characters).');
        return;
    }
    $input.removeClass('qty-bad');

    const productId = $input.data('product-id');
    const payload = {
        category: CATEGORY,
        product_id: productId,
        product_name: $input.data('product-name'),
        units: $input.data('unit'),
        price: $input.data('price'),
        stock_quantity: $input.data('stock'),
        supplier_id: $input.data('supplier') || null,
        quantity: qty,
        date: TODAY,
    };

    if (qty.length > 0) { $input.removeClass('has-qty').addClass('queued-local'); }
    else { $input.removeClass('has-qty queued-local'); }
    upsertQueueLine(payload);
});
$(document).on('keydown', '.qtyInput', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); $(this).trigger('blur'); }
});

// ─────────────────────────────────────────────────────────────────────────
// CLOUD MODAL — view queued items, Clear (discard) or Upload (sync to DB).
// ─────────────────────────────────────────────────────────────────────────
function renderCloudModal() {
    const q = getQueue();
    $('#cqCount').text(q.length);
    if (!q.length) {
        $('#cqBody').html('<tr><td colspan="4" class="text-center text-muted py-3">Nothing queued yet.</td></tr>');
        return;
    }
    let rows = '';
    q.forEach(l => {
        rows += `<tr>
            <td>${escapeHtml(l.product_name)}</td>
            <td>${escapeHtml(l.units || '—')}</td>
            <td>${escapeHtml(l.quantity)}</td>
            <td><button class="btn btn-sm btn-light text-danger cqRemoveBtn" data-uuid="${l.client_uuid}" data-product-id="${l.product_id}"><i class="ri-close-line"></i></button></td>
        </tr>`;
    });
    $('#cqBody').html(rows);
}

$('#cloudBtn').on('click', function (e) { e.preventDefault(); renderCloudModal(); $('#cloudModal').modal('show'); });

$(document).on('click', '.cqRemoveBtn', function () {
    const uid = $(this).data('uuid');
    const pid = $(this).data('product-id');
    setQueue(getQueue().filter(l => l.client_uuid !== uid));
    const $inp = $('.qtyInput[data-product-id="' + pid + '"]');
    if ($inp.length) $inp.val('').removeClass('has-qty queued-local');
    renderCloudModal();
});

$('#cqClearBtn').on('click', function () {
    if (!getQueue().length) { toastr.info('Nothing queued.'); return; }
    if (!confirm('Discard everything waiting to upload? These lines were never saved to the server.')) return;
    setQueue([]);
    $('.qtyInput').val('').removeClass('has-qty queued-local');
    renderCloudModal();
    toastr.success('Queue cleared.');
});

$('#cqUploadBtn').on('click', async function () {
    const q = getQueue();
    if (!q.length) { toastr.info('Nothing to upload.'); return; }
    const $btn = $(this).prop('disabled', true);
    try {
        const res = await $.ajax({
            url: ROUTES.sync, method: 'POST',
            data: { data: JSON.stringify(q), device_name: navigator.platform || 'web' },
        });
        setQueue(res || []);
        if (!res || res.length === 0) {
            toastr.success('All items uploaded.');
            $('#cloudModal').modal('hide');
            location.reload();
        } else {
            // Surface WHY, not just how many — each failed line now carries
            // an _error from the server (see syncOfflineOrders), instead of
            // silently showing "0 uploaded" with nothing to go on.
            console.error('Upload: lines still pending', res);
            const firstReason = res[0] && res[0]._error ? res[0]._error : null;
            toastr.warning(
                (q.length - res.length) + ' uploaded, ' + res.length + ' still pending.' +
                (firstReason ? ' First error: ' + firstReason : '')
            );
            renderCloudModal();
        }
    } catch (e) {
        console.error('Upload request failed:', e);
        toastr.error('Upload failed — check your connection and try again.');
    } finally {
        $btn.prop('disabled', false);
    }
});

// ─────────────────────────────────────────────────────────────────────────
// DOWNLOAD MODAL — Tab 1: the full current order (every product ever
// ordered for this branch+category, latest-updated first). Tab 2:
// Download PDF / Share link, scoped by supplier.
// ─────────────────────────────────────────────────────────────────────────
async function loadPendingTab() {
    if ($.fn.DataTable.isDataTable('#dlPendingTable')) { $('#dlPendingTable').DataTable().destroy(); }
    $('#dlPendingBody').html('<tr><td colspan="7" class="text-center text-muted py-3">Loading…</td></tr>');
    try {
        const res = await $.get(ROUTES.current, { category: CATEGORY });
        const lines = res.lines || [];
        if (!lines.length) {
            $('#dlPendingBody').html('<tr><td colspan="7" class="text-center text-muted py-3">No items on this order.</td></tr>');
            return;
        }
        let rows = '';
        lines.forEach(l => {
            rows += `<tr>
                <td>${escapeHtml(l.product_name)}${l.is_custom ? ' <span style="color:#7c3aed;font-size:10px;">(custom)</span>' : ''}</td>
                <td>${escapeHtml(l.units || '—')}</td>
                <td>${parseFloat(l.price).toFixed(2)}</td>
                <td>${l.stock_at_order !== null ? parseFloat(l.stock_at_order).toFixed(0) : '—'}</td>
                <td>${escapeHtml(l.quantity)}</td>
                <td>${escapeHtml(l.date)}</td>
                <td>${escapeHtml(l.ordered_by_name)}</td>
            </tr>`;
        });
        $('#dlPendingBody').html(rows);
        $('#dlPendingTable').DataTable({
            scrollX: true, fixedColumns: { leftColumns: 1 },
            paging: false, info: false, searching: true, ordering: true,
        });
    } catch (e) {
        $('#dlPendingBody').html('<tr><td colspan="7" class="text-center text-danger py-3">Could not load the current order.</td></tr>');
    }
}

// Lines can span several suppliers at once. This tab lets the person pick
// one supplier's lines, or "All Suppliers" combined, to download/share —
// no order id involved, since the download/share are just a live
// branch+category+supplier query now.
async function loadDownloadShareTab() {
    $('#dlShareWrap').html('');
    let suppliers = [], summary = { line_count: 0 };
    try {
        const res = await $.get(ROUTES.current, { category: CATEGORY });
        suppliers = res.suppliers || [];
        summary = res.summary || summary;
    } catch (e) { /* none yet */ }

    if (!summary.line_count) {
        $('#dlShareWrap').html('<div class="vo-share-locked"><i class="ri-inbox-line"></i>No order to download or share yet.</div>');
        return;
    }

    let supplierPicker = '';
    if (suppliers.length > 1) {
        let options = '<option value="all">All Suppliers</option>';
        suppliers.forEach(s => {
            options += `<option value="${s.id}">${escapeHtml(s.name)} (${s.line_count})</option>`;
        });
        supplierPicker = `
            <div class="text-start">
                <label style="font-size:12px;font-weight:600;margin-bottom:2px;display:block;">Supplier</label>
                <select id="dlSupplierSelect" class="form-select form-select-sm">${options}</select>
            </div>`;
    }

    $('#dlShareWrap').html(`
        <div class="d-flex flex-column gap-2" style="max-width:320px;margin:0 auto;">
            ${supplierPicker}
            <button class="btn btn-primary" id="dlDownloadBtn"><i class="ri-download-line"></i> Download PDF</button>
            <button class="btn btn-outline-primary" id="dlShareBtn"><i class="ri-share-line"></i> Get Share Link</button>
        </div>
    `);
}

function dlSelectedSupplier() {
    const $sel = $('#dlSupplierSelect');
    return $sel.length ? $sel.val() : 'all';
}

$('#downloadBtn').on('click', function (e) { e.preventDefault(); $('#downloadModal').modal('show'); loadPendingTab(); loadDownloadShareTab(); });

$(document).on('click', '#dlDownloadBtn', function () {
    const supplier = dlSelectedSupplier();
    const url = ROUTES.download + '?category=' + CATEGORY + (supplier && supplier !== 'all' ? '&supplier=' + supplier : '');
    downloadOrderPdfFromViews(url, `${CATEGORY.toLowerCase()}-order.pdf`);
});

$(document).on('click', '#dlShareBtn', function () {
    const supplier = dlSelectedSupplier();
    $.post(ROUTES.linkGet, { category: CATEGORY, supplier_id: supplier }).done(function (res) {
        $('#shareLinkInput').val(res.url).data('id', res.id);
        $('#downloadModal').modal('hide');
        $('#shareModal').modal('show');
    });
});
$('#shareCopyBtn').on('click', function () {
    const input = document.getElementById('shareLinkInput');
    input.select();
    document.execCommand('copy');
    toastr.success('Link copied to clipboard.');
});
$('#shareRevokeBtn').on('click', function () {
    const id = $('#shareLinkInput').data('id');
    if (!confirm('Disable this share link? The current link will stop working.')) return;
    $.post(ROUTES.linkRevoke, { id }).done(function () {
        toastr.success('Share link disabled.');
        $('#shareModal').modal('hide');
    });
});

// ─────────────────────────────────────────────────────────────────────────
// DATATABLE (product table)
// ─────────────────────────────────────────────────────────────────────────
if ($('#maintable').length) {
    $('#maintable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[100,250,500,-1],[100,250,500,'All']],
        fixedColumns: { leftColumns: 1 },
        scrollX: true,
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: 0,      className: 'text-start'  },
            { targets: 4,      orderable: false }
        ],
    });
}

// ─────────────────────────────────────────────────────────────────────────
// INIT
// ─────────────────────────────────────────────────────────────────────────
restoreQueueIntoInputs();
refreshQueueBadge();

});
</script>
@endsection