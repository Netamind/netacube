@extends('sales.retail.dashboard')
@section('content')

@php
    // This is a user-facing page, not admin — there is no branch selector.
    // The branch is always the one the logged-in user is assigned to (or
    // the saved filter override, same rule used everywhere else).
    $userId   = Auth::id();
    $pref     = DB::connection('tenant')->table('user_filters')->where('user_id', $userId)->first();
    $branchId = ($pref && $pref->branch_id) ? $pref->branch_id : Auth::user()->branch;
    $branch   = $branchId ? DB::connection('tenant')->table('branches')->find($branchId) : null;

    $CATEGORY = 'Regular';

    // Saved supplier filter for this history table.
    $savedSupplierId = $pref->supplier_id ?? null;

    // Suppliers for the filter bar — same eligibility rule as the ordering
    // page (sector = Retail, category matches this branch's category).
    $suppliers = collect();
    if ($branch && $branch->category) {
        $suppliers = DB::connection('tenant')->table('suppliers')
            ->where('status', 'active')
            ->where('sector', 'Retail')
            ->where('category', $branch->category)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    // No more batches — every ordered product is just a row here. The only
    // demarcator is supplier (null/omitted from the filter = "All
    // Suppliers"), so this lists every line for this branch+category,
    // newest-updated first, optionally narrowed to one supplier. "Current
    // Qty" is the *live* stock_quantity from retail_branch_products, NOT
    // the stock_at_order snapshot ("Qty@Order") — those are two different
    // columns shown side by side below.
    $lines = collect();
    if ($branchId) {
        $lines = DB::connection('tenant')
            ->table('retail_orders as l')
            ->leftJoin('users as u', 'u.id', '=', 'l.ordered_by')
            ->leftJoin('retail_branch_products as rbp', function ($j) {
                $j->on('rbp.base_product_id', '=', 'l.product_id')->on('rbp.branch_id', '=', 'l.branch_id');
            })
            ->where('l.branch_id', $branchId)
            ->where('l.category', $CATEGORY)
            ->when($savedSupplierId, fn ($q) => $q->where('l.supplier_id', $savedSupplierId))
            ->orderByDesc('l.updated_at')
            ->orderByDesc('l.id')
            ->select('l.*', 'u.name as ordered_by_name', DB::raw('rbp.stock_quantity as current_qty'))
            ->get();
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

.no-branch-wrap { padding:48px 20px; text-align:center; color:#94a3b8; }
.no-branch-wrap i { font-size:52px; display:block; margin-bottom:12px; color:#c8d0ed; }
.no-branch-wrap h5 { color:#64748b; font-weight:600; }

.mh-blue   { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-title  { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close  { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }

.order-badge { font-size:11px; font-weight:700; padding:3px 8px; border-radius:20px; }
.status-pending   { background:#eef2ff; color:#4338ca; }
.status-ordered   { background:#fff7ed; color:#c2410c; }
.status-received  { background:#ecfdf5; color:#059669; }
.status-cancelled { background:#fef2f2; color:#dc2626; }
.share-link-box { display:flex; gap:8px; }
.share-link-box input { flex:1; }

/* ── Supplier select (header bar) — same plain-text + caret treatment
   as the Orders page, no icon, no title. ── */
.supplier-select-wrap { display:inline-flex; align-items:center; gap:4px; min-width:0; }
#filterSupplier {
  border:none; background:transparent; color:#fff; font-size:18px; font-weight:600;
  cursor:pointer; padding:0; outline:none; max-width:280px;
  appearance:none; -webkit-appearance:none; -moz-appearance:none;
}
#filterSupplier option { color:#1e293b; background:#fff; font-size:14px; }
#filterSupplier:disabled { cursor:not-allowed; opacity:.7; }
.supplier-select-caret { color:#fff; font-size:18px; line-height:1; pointer-events:none; }

/* ── Table alignment ── */
#historytable thead th, table.dataTable thead th, .dl-table thead th { text-align:center !important; vertical-align:middle !important; }
#historytable thead th:first-child, table.dataTable thead th:first-child, .dl-table thead th:first-child { text-align:left !important; }
#historytable tbody td, table.dataTable tbody td, .dl-table tbody td { text-align:center !important; vertical-align:middle !important; }
#historytable tbody td:first-child, table.dataTable tbody td:first-child, .dl-table tbody td:first-child { text-align:left !important; }

.row-actions .btn { padding:2px 6px; font-size:13px; }
.row-status-select { font-size:11px; padding:2px 4px; border-radius:6px; }

.vo-share-locked { text-align:center; padding:28px 16px; color:#94a3b8; }
.vo-share-locked i { font-size:36px; display:block; margin-bottom:8px; color:#fdba74; }
</style>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <form method="POST" action="{{ route('retail.sales.update.filters') }}" id="filterSupplierForm" style="margin:0;display:inline;">
      @csrf
      <input type="hidden" name="user_id" value="{{ Auth::id() }}">
      {{-- Resend the branch — this route overwrites the whole
           user_filters row with whatever fields are posted, so
           omitting branch_id here would silently reset it to null. --}}
      <input type="hidden" name="branch_id" value="{{ $branchId }}">
      <div class="supplier-select-wrap">
        <select name="supplier_id" id="filterSupplier" onchange="document.getElementById('filterSupplierForm').submit()" {{ $suppliers->isEmpty() ? 'disabled' : '' }}>
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
    <div class="d-flex align-items-center" style="gap:4px;">
      <a href="#" class="btn btn-light text-dark fs-16 mx-1" id="downloadBtn" title="Download / Share">
        <i class="ri-download-2-line"></i>
      </a>
      <a href="#" class="btn btn-light text-danger fs-16 mx-1" id="deleteAllBtn" title="Delete all orders for this selection">
        <i class="ri-delete-bin-line"></i>
      </a>
      <a href="{{ route('retail.orders.regular') }}" class="btn btn-light text-primary fs-16 mx-1" title="New order">
        <i class="ri-add-line"></i>
      </a>
    </div>
  </div>

      <div class="tab-header-container">
    <ul class="nav nav-pills px-2 pt-1">
      <li class="nav-item">
        <a class="nav-link" href="{{ route('retail.orders.regular') }}"><i class="ri-repeat-line"></i> Regular</a>
      </li>
      <li class="nav-item">
        <a class="nav-link active" href="{{ route('retail.orders.regular.history') }}"><i class="ri-history-line"></i> History</a>
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

      <div id="historyTableWrap">
        <table id="historytable" class="table table-sm table-striped row-border order-column w-100 mt-2">
          <thead style="background-color:#e2e2e9">
            <tr>
              <th>Product Name</th>
              <th>Unit</th>
              <th>Qty@Order</th>
              <th>OrderQty</th>
              <th>Date</th>
              <th>Current Qty</th>
              <th>Ordered By</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="historyBody">
            @forelse($lines as $l)
              <tr data-id="{{ $l->id }}">
                <td>{{ $l->product_name }}@if($l->is_custom) <span style="color:#7c3aed;font-size:11px;">(custom)</span>@endif</td>
                <td>{{ $l->units ?? '—' }}</td>
                <td>{{ $l->stock_at_order !== null ? number_format($l->stock_at_order, 0) : '—' }}</td>
                <td style="font-weight:600;">{{ $l->quantity }}</td>
                <td>{{ \Carbon\Carbon::parse($l->date)->format('d M Y') }}</td>
                <td>{{ isset($l->current_qty) && $l->current_qty !== null ? number_format($l->current_qty, 0) : 'NA' }}</td>
                <td>{{ $l->ordered_by_name }}</td>
                <td class="row-actions">
                  <button class="btn btn-light text-primary editLineBtn" data-id="{{ $l->id }}"
                          data-name="{{ $l->product_name }}" data-units="{{ $l->units }}"
                          data-quantity="{{ $l->quantity }}" data-price="{{ $l->price }}"
                          data-supplier="{{ $l->supplier_id }}" title="Edit"><i class="ri-pencil-line"></i></button>
                  <button class="btn btn-light text-danger deleteLineBtn" data-id="{{ $l->id }}" title="Delete"><i class="ri-delete-bin-line"></i></button>
                </td>
              </tr>
            @empty
              <tr><td colspan="8" class="text-center text-muted py-3">No items ordered yet for this filter.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

    @endif

  </div>
</div>

</div></div></div>

{{-- ═══════════════════════════════ EDIT LINE MODAL ═══════════════════════════════ --}}
<div class="modal fade" id="editLineModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-pencil-line"></i> Edit Order Line</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="elId">
        <div class="mb-2">
          <label class="form-label" style="font-size:12px;">Product Name</label>
          <input type="text" id="elName" class="form-control">
        </div>
        <div class="row g-2">
          <div class="col-6">
            <label class="form-label" style="font-size:12px;">Unit</label>
            <input type="text" id="elUnit" class="form-control">
          </div>
          <div class="col-6">
            <label class="form-label" style="font-size:12px;">Qty</label>
            <input type="text" id="elQty" class="form-control" maxlength="60">
          </div>
          <div class="col-6">
            <label class="form-label" style="font-size:12px;">Price</label>
            <input type="number" id="elPrice" class="form-control" min="0" step="0.01">
          </div>
          <div class="col-6">
            <label class="form-label" style="font-size:12px;">Supplier</label>
            <select id="elSupplier" class="form-select">
              <option value="">—</option>
              @foreach($suppliers as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="elSaveBtn">Save Changes</button>
      </div>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════ DOWNLOAD / SHARE MODAL ═══════════════════════════════ --}}
<div class="modal fade" id="downloadModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-download-2-line"></i> Download / Share</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="dlShareWrap"></div>
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

{{-- ═══════════════════════════════ DELETE CONFIRM MODAL ═══════════════════════════════ --}}
<div class="modal fade" id="deleteOrderModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:350px;margin:1.75rem auto;">
    <div class="modal-content">
      <div class="modal-body text-center pb-4">
        <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
        <h4 class="mt-2">Are you sure you want to delete <span id="deleteOrderLabel">this item</span>?</h4>
        <h5>You won't be able to revert this!</h5>
        <a href="#" class="btn btn-danger" id="confirmDeleteOrderBtn" style="margin-top:10px;margin-bottom:10px;margin-right:5px">Yes, Delete it</a>
        <a href="#" class="btn btn-info"   id="cancelDeleteOrderBtn" style="margin-top:10px;margin-bottom:10px;">No, Keep it</a>
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
const CATEGORY = 'Regular';
let dt = null;

const ROUTES = {
    current: '{{ route("retail.orders.current") }}',
    lineUpdate: '{{ route("retail.orders.line.update") }}',
    lineDelete: '{{ route("retail.orders.line.delete") }}',
    deleteScope: '{{ route("retail.orders.delete.scope") }}',
    download: '{{ url("/" . request()->route("tenantName") . "/sales/retail/orders/download") }}',
    linkGet: '{{ route("retail.orders.link.get") }}',
    linkRevoke: '{{ route("retail.orders.link.revoke") }}',
};

$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });
toastr.options = { closeButton:true, progressBar:true, showMethod:'slideDown', timeOut:4000 };

// ─────────────────────────────────────────────────────────────────────────
// DOWNLOAD (in-app views) — fetch as a blob and drive the download from a
// temporary anchor; target="_blank" inside a modal silently fails on
// installed/PWA contexts and some in-app browsers.
// ─────────────────────────────────────────────────────────────────────────
function downloadOrderPdfFromViews(url, fallbackName) {
    fetch(url, { credentials: 'same-origin' })
        .then(res => {
            if (!res.ok) throw new Error('Download failed');
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
        .catch(() => {
            toastr.error('Could not download the PDF. Please try again.');
        });
}

function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

// ─────────────────────────────────────────────────────────────────────────
// DATATABLE — rows are already rendered server-side (see @php above), so
// this just wires up sorting/paging/search on what's in the DOM.
// ─────────────────────────────────────────────────────────────────────────
if ($('#historytable').length) {
    dt = $('#historytable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[25,50,100,-1],[25,50,100,'All']],
        order: [],
        fixedColumns: { leftColumns: 1 },
        scrollX: true,
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: 0,      className: 'text-start'  },
            { targets: 7,      orderable: false }
        ],
    });
}

// ─────────────────────────────────────────────────────────────────────────
// DELETE CONFIRMATION MODAL
// ─────────────────────────────────────────────────────────────────────────
let pendingDeleteAction = null;

function openDeleteConfirm(label, action) {
    $('#deleteOrderLabel').text(label);
    pendingDeleteAction = action;
    $('#deleteOrderModal').modal('show');
}

$('#confirmDeleteOrderBtn').on('click', function (e) {
    e.preventDefault();
    if (typeof pendingDeleteAction === 'function') pendingDeleteAction();
});

$('#cancelDeleteOrderBtn').on('click', function (e) {
    e.preventDefault();
    pendingDeleteAction = null;
    $('#deleteOrderModal').modal('hide');
});

// ─────────────────────────────────────────────────────────────────────────
// DELETE ALL — blue-bar button, scoped to the branch+category+whatever
// supplier is currently selected in the filter dropdown (empty = All
// Suppliers), same convention used by Download/Share and the ordering
// page's supplier filter.
// ─────────────────────────────────────────────────────────────────────────
$('#deleteAllBtn').on('click', function (e) {
    e.preventDefault();
    const supplierId = $('#filterSupplier').val() || null;
    const supplierLabel = supplierId ? $('#filterSupplier option:selected').text() : 'All Suppliers';

    openDeleteConfirm(CATEGORY + ' orders for ' + supplierLabel, function () {
        $.post(ROUTES.deleteScope, { category: CATEGORY, supplier_id: supplierId }).done(function () {
            toastr.success('Orders deleted.');
            $('#deleteOrderModal').modal('hide');
            if (dt) {
                dt.clear().draw(false);
            } else {
                $('#historyBody').html('<tr><td colspan="8" class="text-center text-muted py-3">No items ordered yet for this filter.</td></tr>');
            }
        }).fail(function () {
            toastr.error('Could not delete these orders.');
            $('#deleteOrderModal').modal('hide');
        });
    });
});

// ─────────────────────────────────────────────────────────────────────────
// ROW DELETE
// ─────────────────────────────────────────────────────────────────────────
$(document).on('click', '.deleteLineBtn', function () {
    const id = $(this).data('id');
    openDeleteConfirm('this item', function () {
        $.post(ROUTES.lineDelete, { id }).done(function () {
            toastr.success('Item deleted.');
            $('#deleteOrderModal').modal('hide');
            if (dt) { dt.row($('#historytable tbody tr[data-id="' + id + '"]')).remove().draw(false); }
        }).fail(function () {
            toastr.error('Could not delete this item.');
            $('#deleteOrderModal').modal('hide');
        });
    });
});

// ─────────────────────────────────────────────────────────────────────────
// EDIT LINE
// ─────────────────────────────────────────────────────────────────────────
$(document).on('click', '.editLineBtn', function () {
    const $b = $(this);
    $('#elId').val($b.data('id'));
    $('#elName').val($b.data('name'));
    $('#elUnit').val($b.data('units'));
    $('#elQty').val($b.data('quantity'));
    $('#elPrice').val($b.data('price'));
    $('#elSupplier').val($b.data('supplier') || '');
    $('#editLineModal').modal('show');
});

$('#elSaveBtn').on('click', function () {
    const id = $('#elId').val();
    const payload = {
        id: id,
        product_name: $('#elName').val().trim(),
        units: $('#elUnit').val().trim(),
        quantity: $('#elQty').val().trim(),
        price: $('#elPrice').val(),
        supplier_id: $('#elSupplier').val() || null,
    };
    if (!payload.product_name) { toastr.warning('Product name is required.'); return; }
    if (!payload.quantity)     { toastr.warning('Quantity is required.'); return; }

    $.post(ROUTES.lineUpdate, payload).done(function () {
        toastr.success('Order line updated.');
        $('#editLineModal').modal('hide');

        // Update the row in place — only Product Name, Unit, and OrderQty
        // are shown in this table, so patch just those cells instead of
        // reloading the page.
        if (dt) {
            const $tr = $('#historytable tbody tr[data-id="' + id + '"]');
            const nameCell = escapeHtml(payload.product_name) +
                ($tr.find('td:first span').length ? ' <span style="color:#7c3aed;font-size:11px;">(custom)</span>' : '');
            dt.cell($tr, 0).data(nameCell);
            dt.cell($tr, 1).data(escapeHtml(payload.units || '—'));
            dt.cell($tr, 3).data(escapeHtml(payload.quantity));
            dt.draw(false);

            // Keep the row's edit button data attributes in sync so a
            // second edit (without a reload in between) opens the modal
            // pre-filled with the latest values.
            $tr.find('.editLineBtn')
                .data('name', payload.product_name)
                .data('units', payload.units)
                .data('quantity', payload.quantity)
                .data('price', payload.price)
                .data('supplier', payload.supplier_id);
        }
    }).fail(function () {
        toastr.error('Could not update this order line.');
    });
});

// ─────────────────────────────────────────────────────────────────────────
// DOWNLOAD / SHARE — scoped to branch+category+supplier, no order id.
// ─────────────────────────────────────────────────────────────────────────
async function loadDownloadShareTab() {
    $('#dlShareWrap').html('<div class="text-center text-muted py-3">Loading…</div>');
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

$('#downloadBtn').on('click', function (e) { e.preventDefault(); $('#downloadModal').modal('show'); loadDownloadShareTab(); });

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

});
</script>
@endsection