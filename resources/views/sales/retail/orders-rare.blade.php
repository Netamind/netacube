@extends('sales.retail.dashboard')
@section('content')

@php
    use Carbon\Carbon;

    $CATEGORY = 'Rare';

    $userId = Auth::id();
    $pref   = DB::connection('tenant')->table('user_filters')->where('user_id', $userId)->first();

    // Saved branch filter always wins over the user's assigned branch —
    // same rule used everywhere else in the retail module. Branch is no
    // longer editable from this page's header.
    $branchId = ($pref && $pref->branch_id) ? $pref->branch_id : Auth::user()->branch;
    $branch   = $branchId ? DB::connection('tenant')->table('branches')->find($branchId) : null;

    // Suppliers eligible for this branch: sector = Retail AND supplier's
    // category matches the branch's own category (Rare has no catalog, so
    // this only feeds the optional supplier dropdown on the quick-add form
    // and the header supplier selector).
    $suppliers = collect();
    if ($branch && $branch->category) {
        $suppliers = DB::connection('tenant')->table('suppliers')
            ->where('status', 'active')
            ->where('sector', 'Retail')
            ->where('category', $branch->category)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    // Shares the same pref column as the History page's supplier filter
    // (supplier_id) — this is the field name the update-filters route
    // actually recognizes.
    $savedSupplierId = $pref->supplier_id ?? null;

    // No more batches. Every Rare quick-add is its own permanent row in
    // retail_orders (no product_id to upsert against), so "Items on This
    // Order" below is just every Rare row for this branch, newest first —
    // there's no "today only" or "pending only" scoping anymore. Anything
    // typed but not yet uploaded lives only in the browser's local queue
    // until Upload is pressed.
    $openLines      = collect();
    $currentSummary = ['line_count' => 0];

    if ($branchId) {
        $openLines = DB::connection('tenant')->table('retail_orders')
            ->where('branch_id', $branchId)
            ->where('category', $CATEGORY)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->get();

        $currentSummary = [
            'line_count' => $openLines->count(),
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

/* ── Tab bar (Rare / History) ── */
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
.badge-Rare      { background:#f5f3ff; color:#7c3aed; }
.share-link-box { display:flex; gap:8px; }
.share-link-box input { flex:1; }

/* ── Table alignment — first column left, everything else center.
   Applies to the main product table AND every table inside a modal. ── */
#maintable thead th, table.dataTable thead th, .dl-table thead th { text-align:center !important; vertical-align:middle !important; }
#maintable thead th:first-child, table.dataTable thead th:first-child, .dl-table thead th:first-child { text-align:left !important; }
#maintable tbody td, table.dataTable tbody td, .dl-table tbody td { text-align:center !important; vertical-align:middle !important; }
#maintable tbody td:first-child, table.dataTable tbody td:first-child, .dl-table tbody td:first-child { text-align:left !important; }

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
        {{-- Resend the branch — this route overwrites the whole
             user_filters row with whatever fields are posted, so
             omitting branch_id here silently reset it to null and
             broke the supplier filter after the first change. --}}
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
      <a href="#" class="btn btn-light text-dark fs-16 mx-1" id="addRareProductBtn" title="Add product">
        <i class="ri-add-line"></i>
      </a>
      <a href="#" class="btn btn-light text-dark fs-16 mx-1" id="downloadBtn" title="Pending orders / Download">
        <i class="ri-download-2-line"></i>
      </a>
    </div>
  </div>

      <div class="tab-header-container">
    <ul class="nav nav-pills px-2 pt-1">
      <li class="nav-item">
        <a class="nav-link active" href="{{ route('retail.orders.rare') }}"><i class="ri-quill-pen-line"></i> Rare</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="{{ route('retail.orders.rare.history') }}"><i class="ri-history-line"></i> History</a>
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

      {{-- ═══════════════════ RARE — free-typed product, no catalog. Adding
           an item is now done through the "Add product" button in the blue
           header bar, which opens #addRareModal below. ═══════════════════ --}}
      <h6 class="fw-bold mb-2 mt-3">Items on This Order</h6>

      <table id="maintable" class="table table-sm table-striped row-border order-column w-100 mt-3">
        <thead style="background-color:#e2e2e9">
          <tr>
            <th>Product Name</th>
            <th>Unit</th>
            <th>Qty</th>
            <th>Remove</th>
          </tr>
        </thead>
        <tbody id="tbody">
          @foreach($openLines as $line)
            <tr id="rare-line-{{ $line->id }}">
              <td>{{ $line->product_name }}</td>
              <td>{{ $line->units ?? '—' }}</td>
              <td>{{ $line->quantity }}</td>
              <td><button class="btn btn-sm btn-light text-danger removeRareBtn" data-id="{{ $line->id }}"><i class="ri-delete-bin-line"></i></button></td>
            </tr>
          @endforeach
        </tbody>
      </table>

    @endif

  </div>
</div>

</div></div></div>

{{-- ═══════════════════════════════ ADD PRODUCT MODAL ═══════════════════════════════ --}}
<div class="modal fade" id="addRareModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-add-line"></i> Add Rare Order Item</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label" style="font-size:12px;">Product Name</label>
          <input type="text" id="rareName" class="form-control" placeholder="e.g. Imported olive tapenade">
        </div>
        <div class="row g-2">
          <div class="col-6">
            <label class="form-label" style="font-size:12px;">Unit</label>
            <input type="text" id="rareUnit" class="form-control" placeholder="e.g. pcs, kg">
          </div>
          <div class="col-6">
            <label class="form-label" style="font-size:12px;">Qty to Order</label>
            <input type="text" id="rareQty" class="form-control" placeholder="e.g. 20" maxlength="60">
          </div>
          <div class="col-12">
            <label class="form-label" style="font-size:12px;">Supplier (optional)</label>
            <select id="rareSupplier" class="form-select">
              <option value="">—</option>
              @foreach($suppliers as $s)
                <option value="{{ $s->id }}" {{ (string) $savedSupplierId === (string) $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="rareAddBtn"><i class="ri-add-line me-1"></i> Add to Queue</button>
      </div>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════ DOWNLOAD MODAL ═══════════════════════════════ --}}
<div class="modal fade" id="downloadModal" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-download-2-line"></i> Rare Orders</h5>
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
                  <tr><th>Item</th><th>Unit</th><th>Qty@Order</th><th>OrderQty</th><th>Date</th><th>Ordered By</th></tr>
                </thead>
                <tbody id="dlPendingBody"><tr><td colspan="6" class="text-center text-muted py-3">Loading…</td></tr></tbody>
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
        <p class="mb-2" style="font-size:13px;">Anyone with this link can view the order — <strong>no login required</strong>. Share it with your supplier.</p>
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
const CATEGORY     = 'Rare';
const QTY_MAX_LEN  = 60;

const ROUTES = {
    lineSave: '{{ route("retail.orders.line.save") }}',
    current: '{{ route("retail.orders.current") }}',
    lineDelete: '{{ route("retail.orders.line.delete") }}',
    download: '{{ url("/" . request()->route("tenantName") . "/sales/retail/orders/download") }}',
    linkGet: '{{ route("retail.orders.link.get") }}',
    linkRevoke: '{{ route("retail.orders.link.revoke") }}',
};

$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
toastr.options = { closeButton:true, progressBar:true, showMethod:'slideDown', timeOut:4000 };

// ─────────────────────────────────────────────────────────────────────────
// HELPERS
// ─────────────────────────────────────────────────────────────────────────
function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

// ─────────────────────────────────────────────────────────────────────────
// DOWNLOAD (in-app views) — separate from the public share-link download,
// which is a plain same-origin anchor and already works fine on its own
// page. From inside the app the PDF link lives inside a Bootstrap modal
// and target="_blank" silently fails to open a new tab on installed/PWA
// contexts and some in-app browsers, so the click never triggers anything.
// Fetching the file as a blob and driving the download from a temporary
// anchor sidesteps that entirely.
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
// ADD-PRODUCT MODAL — opened from the "Add product" button in the blue
// header bar. Rare has no offline queue: each Add inserts straight into
// the database via ROUTES.lineSave (the same endpoint Regular/Emergency
// use), and the returned line_id is used to render the new row without a
// page reload.
// ─────────────────────────────────────────────────────────────────────────
$('#addRareProductBtn').on('click', function (e) {
    e.preventDefault();
    $('#addRareModal').modal('show');
    setTimeout(() => $('#rareName').trigger('focus'), 200);
});

$('#rareAddBtn').on('click', function () {
    const name  = $('#rareName').val().trim();
    const unit  = $('#rareUnit').val().trim();
    const qty   = $('#rareQty').val().trim();
    const supplierId = $('#rareSupplier').val() || null;

    if (!name) { toastr.warning('Product name is required.'); return; }
    if (!qty || qty.length > QTY_MAX_LEN) { toastr.warning('Enter a quantity (up to ' + QTY_MAX_LEN + ' characters).'); return; }

    const $btn = $(this).prop('disabled', true);
    $.post(ROUTES.lineSave, {
        category: CATEGORY, product_id: null, product_name: name, units: unit,
        price: 0, quantity: qty, supplier_id: supplierId,
    }).done(function (res) {
        if (rareTable && res.line_id) {
            rareTable.row.add($(`<tr id="rare-line-${res.line_id}">
                <td>${escapeHtml(name)}</td>
                <td>${escapeHtml(unit || '—')}</td>
                <td>${escapeHtml(qty)}</td>
                <td><button class="btn btn-sm btn-light text-danger removeRareBtn" data-id="${res.line_id}"><i class="ri-delete-bin-line"></i></button></td>
            </tr>`)).draw(false);
        }

        $('#rareName, #rareUnit, #rareQty').val('');
        $('#rareSupplier').val('');
        $('#addRareModal').modal('hide');
        toastr.success('Item added to the order.');
    }).fail(function () {
        toastr.error('Could not save this item — check your connection and try again.');
    }).always(function () {
        $btn.prop('disabled', false);
    });
});

$(document).on('click', '.removeRareBtn', function () {
    const id = $(this).data('id');
    $.post(ROUTES.lineDelete, { id }).done(function () {
        if (rareTable) { rareTable.row('#rare-line-' + id).remove().draw(false); }
        toastr.success('Removed.');
    });
});

// ─────────────────────────────────────────────────────────────────────────
// DOWNLOAD MODAL
// ─────────────────────────────────────────────────────────────────────────
async function loadPendingTab() {
    if ($.fn.DataTable.isDataTable('#dlPendingTable')) { $('#dlPendingTable').DataTable().destroy(); }
    $('#dlPendingBody').html('<tr><td colspan="6" class="text-center text-muted py-3">Loading…</td></tr>');
    try {
        const res = await $.get(ROUTES.current, { category: CATEGORY });
        const lines = res.lines || [];
        if (!lines.length) {
            $('#dlPendingBody').html('<tr><td colspan="6" class="text-center text-muted py-3">No items on this order.</td></tr>');
            return;
        }
        let rows = '';
        lines.forEach(l => {
            rows += `<tr>
                <td>${escapeHtml(l.product_name)} <span style="color:#7c3aed;font-size:10px;">(custom)</span></td>
                <td>${escapeHtml(l.units || '—')}</td>
                <td>—</td>
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
        $('#dlPendingBody').html('<tr><td colspan="6" class="text-center text-danger py-3">Could not load pending orders.</td></tr>');
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
// DATATABLE (Items on This Order) — same fixed-first-column DataTable
// treatment as Regular/Emergency's #maintable.
// ─────────────────────────────────────────────────────────────────────────
let rareTable = null;
if ($('#maintable').length) {
    rareTable = $('#maintable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[100,250,500,-1],[100,250,500,'All']],
        fixedColumns: { leftColumns: 1 },
        scrollX: true,
        order: [],
        language: { emptyTable: 'No items added yet.' },
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: 0,      className: 'text-start'  },
            { targets: 3,      orderable: false }
        ],
    });
}

});
</script>
@endsection