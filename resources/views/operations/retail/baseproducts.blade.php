@extends('operations.retail.dashboard')
@section('content')
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

/* ── Filter bar ─────────────────────────────────────────────────────────── */
.card-filter {
  background: #eef0f7; border-bottom: 1px solid #d6daf0;
  padding: 9px 1.5rem; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
}
.card-filter label { font-size:12px; font-weight:600; color:#4B5EBD; margin-bottom:0; white-space:nowrap; }
.card-filter select {
  font-size:12px; height:30px; padding:0 8px; border-radius:6px;
  border:1px solid #c8d0ed; background:#fff; min-width:160px; max-width:220px;
}
.filter-divider { width:1px; height:22px; background:#c8d0ed; margin:0 4px; }
.filter-badge { font-size:11px; background:#4B5EBD; color:#fff; border-radius:10px; padding:1px 8px; }

/* ── Bulk trigger ───────────────────────────────────────────────────────── */
#bulkTriggerBtn {
  font-size:12px; font-weight:700; margin-left:auto; height:28px; padding:0 12px;
  display:none; align-items:center; gap:5px;
}
#bulkTriggerBtn.visible { display:flex !important; }

/* ── Table alignment ────────────────────────────────────────────────────── */
#maintable thead th,
table.dataTable thead th { text-align:center !important; vertical-align:middle !important; }
#maintable thead th:first-child,
table.dataTable thead th:first-child { text-align:left !important; }
#maintable tbody td,
table.dataTable tbody td { text-align:center !important; vertical-align:middle !important; }
#maintable tbody td:first-child,
table.dataTable tbody td:first-child { text-align:left !important; }

/* ── Prices ─────────────────────────────────────────────────────────────── */
.price-cell { font-size:12px; font-weight:600; color:#198754; }

/* ── Type badge ─────────────────────────────────────────────────────────── */
.type-badge-product { font-size:10px; font-weight:600; background:#e8f5e9; color:#2d6a4f; border:1px solid #a5d6a7; border-radius:10px; padding:1px 7px; white-space:nowrap; }
.type-badge-service { font-size:10px; font-weight:600; background:#fff3e0; color:#e65100; border:1px solid #ffcc80; border-radius:10px; padding:1px 7px; white-space:nowrap; }

/* ── Modal selects fix ──────────────────────────────────────────────────── */
.modal-body .form-select,
.modal-body .form-control { width:100% !important; }

/* ── Modal header helpers ───────────────────────────────────────────────── */
.mh-blue   { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-green  { background:linear-gradient(135deg,#2d6a4f,#40916c); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-info   { background:linear-gradient(135deg,#0d6efd,#3b82f6); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-orange { background:linear-gradient(135deg,#fd7e14,#e8590c); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-title  { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close  { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }

/* ── View modal ─────────────────────────────────────────────────────────── */
.view-grid { display:grid; grid-template-columns:1fr 1fr; gap:6px 14px; }
.view-item label { font-size:10px; font-weight:600; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:2px; }
.view-item .view-val { font-size:13px; color:#1e293b; font-weight:500; }
.view-item .view-val.muted { color:#9ca3af; font-style:italic; }
.view-item.full { grid-column:1/-1; }

/* ── Import modal ───────────────────────────────────────────────────────── */
.excel-preview-wrap { border:2px solid #b7d5c4; border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.07); }
.excel-header-bar { background:#217346; color:#fff; font-size:11px; font-weight:700; padding:5px 10px; letter-spacing:0.5px; display:flex; align-items:center; gap:6px; }
.excel-preview-table { width:100%; border-collapse:collapse; font-size:12px; }
.excel-row-num { background:#f0f0f0; color:#888; font-size:10px; font-weight:600; text-align:center; padding:4px 6px; border-right:1px solid #d0d0d0; border-bottom:1px solid #d0d0d0; min-width:28px; }
.excel-preview-table thead th { background:#217346; color:#fff; text-align:center; padding:6px 10px; border-right:1px solid #1a5c38; font-weight:600; font-size:11px; white-space:nowrap; }
.excel-preview-table thead th.col-name { text-align:left; }
.excel-preview-table tbody td { text-align:center; padding:5px 10px; border-bottom:1px solid #e8e8e8; border-right:1px solid #e8e8e8; background:#fff; }
.excel-preview-table tbody td.col-name { text-align:left; }
.excel-preview-table tbody tr:nth-child(even) td { background:#f8fff8; }
.excel-preview-table tbody tr:hover td { background:#e8f5e9; }
.excel-col-code { font-family:monospace; background:#f1f8e9; padding:1px 5px; border-radius:3px; color:#2d6a4f; font-size:11px; }
.excel-req-star { color:#e74c3c; margin-left:2px; font-size:13px; }
.excel-sample-val { color:#555; }
.excel-sample-muted { color:#aaa; font-style:italic; font-size:11px; }

.drop-zone { border:2px dashed #40916c; border-radius:12px; padding:26px 20px; text-align:center; cursor:pointer; transition:all .2s; background:#f0faf5; position:relative; }
.drop-zone:hover,.drop-zone.drag-over { background:#d8f3e6; border-color:#1b4332; }
.drop-zone input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
.drop-zone i { font-size:36px; color:#40916c; display:block; margin-bottom:6px; }
#importPreviewTable { font-size:12px; }
#importPreviewTable th { background:#e8f5e9; position:sticky; top:0; text-align:center; }
#importPreviewTable th:first-child { text-align:left; }
#importPreviewTable td { text-align:center; }
#importPreviewTable td:first-child { text-align:left; }
.import-progress-bar { height:6px; border-radius:3px; background:#e9ecef; overflow:hidden; }
.import-progress-bar .bar { height:100%; width:0; background:linear-gradient(to right,#40916c,#52b788); transition:width .3s ease; border-radius:3px; }

/* ── Bulk actions modal ─────────────────────────────────────────────────── */
.bulk-section { background:#f8f9fa; border-radius:8px; padding:12px 14px; margin-bottom:12px; }
.bulk-section-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#6c757d; margin-bottom:10px; }

/* ── Edit modal read-only fields ────────────────────────────────────────── */
.edit-readonly-field {
  background:#f8f9fa !important; color:#495057 !important;
  border-color:#dee2e6 !important; cursor:default !important;
}

/* ── Spinner ────────────────────────────────────────────────────────────── */
@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
</style>

<div class="progress" id="progressBar" role="progressbar" style="height:8px;transform:rotate(180deg);display:none">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<?php
    $maintableTitle = "Retail Base Products";

    $categories = DB::connection('tenant')
                    ->table('categories')
                    ->orderBy('category')
                    ->get();

    $pref = DB::connection('tenant')
               ->table('user_filters')
               ->where('user_id', Auth::id())
               ->first();

    $savedCategoryId = $pref->category_id ?? null;
    $savedSupplierId = $pref->supplier_id  ?? null;

    $savedCategoryName = null;
    if ($savedCategoryId) {
        $savedCategoryName = DB::connection('tenant')
                               ->table('categories')
                               ->where('id', $savedCategoryId)
                               ->value('category');
    }

    $allRetailSuppliers = DB::connection('tenant')
                             ->table('suppliers')
                             ->where('sector', 'retail')
                             ->orderBy('name')
                             ->get();

    $suppliers = collect();
    if ($savedCategoryId) {
        $suppliers = DB::connection('tenant')
                        ->table('suppliers')
                        ->where('sector', 'retail')
                        ->where('category', $savedCategoryId)
                        ->orderBy('name')
                        ->get();
    }

    $products = collect();

    if ($savedCategoryId) {
        if ($savedSupplierId) {
            $supplierName = DB::connection('tenant')
                               ->table('suppliers')
                               ->where('id', $savedSupplierId)
                               ->where('sector', 'retail')
                               ->where('category', $savedCategoryId)
                               ->value('name');

            if ($supplierName) {
                $products = DB::connection('tenant')
                               ->table('retail_base_products')
                               ->where('supplier', $supplierName)
                               ->orderBy('name')
                               ->get();
            }
        } else {
            $categorySupplierNames = DB::connection('tenant')
                                        ->table('suppliers')
                                        ->where('sector', 'retail')
                                        ->where('category', $savedCategoryId)
                                        ->pluck('name')
                                        ->toArray();

            if (!empty($categorySupplierNames)) {
                $products = DB::connection('tenant')
                               ->table('retail_base_products')
                               ->whereIn('supplier', $categorySupplierNames)
                               ->orderBy('name')
                               ->get();
            }
        }
    }
?>

<div class="card">

{{-- ── Card header ──────────────────────────────────────────────────────── --}}
<div class="card-header d-flex justify-content-between align-items-center">
  <h4 class="header-title mb-0">Base Products</h4>
  <div class="d-flex align-items-center" style="gap:4px;">
    <a href="#" class="btn btn-light text-success fs-16 mx-1" id="importBtn"       title="Import from CSV"><i class="ri-file-excel-2-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="newDataBtn"      title="Add new product"><i class="ri-add-circle-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn"         title="Info"><i class="ri-information-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download"><i class="ri-download-line"></i></a>
  </div>
</div>

{{-- ── Filter bar ───────────────────────────────────────────────────────── --}}
<div class="card-filter">

  <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
        id="filterCategoryForm" style="display:contents;">
    @csrf
    <input type="hidden" name="user_id"     value="{{ Auth::id() }}">
    <input type="hidden" name="supplier_id" value="">
    <label>Category:</label>
    <select name="category_id" id="filterCategory"
            onchange="document.getElementById('filterCategoryForm').submit()">
      <option value="" hidden>—Select Category—</option>
      @foreach($categories as $cat)
        <option value="{{ $cat->id }}" {{ $savedCategoryId == $cat->id ? 'selected' : '' }}>
          {{ $cat->category }}
        </option>
      @endforeach
    </select>
  </form>

  <div class="filter-divider"></div>

  @if($savedCategoryId)
    @if($suppliers->isEmpty())
      <div style="background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:5px 12px;font-size:12px;color:#7d5a00;display:flex;align-items:center;gap:6px;">
        <i class="ri-information-line"></i> No retail suppliers for this category.
      </div>
    @else
      <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
            id="filterSupplierForm" style="display:contents;">
        @csrf
        <input type="hidden" name="user_id"     value="{{ Auth::id() }}">
        <input type="hidden" name="category_id" value="{{ $savedCategoryId }}">
        <label>Supplier:</label>
        <select name="supplier_id" id="filterSupplier"
                onchange="document.getElementById('filterSupplierForm').submit()">
          <option value="">All Suppliers</option>
          @foreach($suppliers as $sup)
            <option value="{{ $sup->id }}" {{ $savedSupplierId == $sup->id ? 'selected' : '' }}>
              {{ $sup->name }}
            </option>
          @endforeach
        </select>
      </form>
    @endif
  @else
    <label>Supplier:</label>
    <select disabled title="Select a category first">
      <option>— Select a category first —</option>
    </select>
  @endif

  <a href="#" class="btn btn-warning btn-sm" id="bulkTriggerBtn" title="Bulk Actions" style="margin-left:auto;">
    <i class="ri-checkbox-multiple-line me-1"></i><span id="selectedCount">0</span> Selected
  </a>
</div>

{{-- ── Table ────────────────────────────────────────────────────────────── --}}
<div class="card-body">
<table id="maintable" class="table table-sm table-striped row-border order-column w-100">
  <thead style="background-color:#e2e2e9">
    <tr>
      <th><input type="checkbox" id="selectAll">&nbsp;&nbsp;Product Name</th>
      <th>Code</th>
      <th>Unit</th>
      <th>Order Price</th>
      <th>Sell Price</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody id="tbody">
    @foreach($products as $product)
      <?php $row = "row".$product->id ?>
      <tr id="{{ $row }}">
        <td>
          <input type="checkbox" class="selectRow"
                 value="{{ $product->id }}" data-row-id="{{ $row }}">
          &nbsp;{{ $product->name }}
        </td>
        <td>{{ $product->code ?? '—' }}</td>
        <td>{{ $product->unit }}</td>
        <td>
          @if($product->cost_price !== null)
            <span style="font-size:12px;color:#6c757d">{{ number_format($product->cost_price,2) }}</span>
          @else
            <span class="text-muted" style="font-size:12px">—</span>
          @endif
        </td>
        <td>
          @if($product->selling_price !== null)
            <span class="price-cell">{{ number_format($product->selling_price,2) }}</span>
          @else
            <span class="text-muted" style="font-size:12px">—</span>
          @endif
        </td>
        <td>
          <a href="#" class="viewDataBtn"
             data-id="{{ $product->id }}"
             data-name="{{ $product->name }}"
             data-description="{{ $product->description }}"
             data-supplier="{{ $product->supplier }}"
             data-code="{{ $product->code }}"
             data-unit="{{ $product->unit }}"
             data-sell="{{ $product->selling_price }}"
             data-cost="{{ $product->cost_price }}"
             data-is-product="{{ $product->is_product }}">
            <i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px"></i>
          </a>
          <a href="#" class="editDataBtn"
             editId="{{ $product->id }}"
             editRow="{{ $row }}"
             editName="{{ $product->name }}"
             editDescription="{{ $product->description }}"
             editSupplier="{{ $product->supplier }}"
             editCode="{{ $product->code }}"
             editUnit="{{ $product->unit }}"
             editSellingPrice="{{ $product->selling_price }}"
             editCostPrice="{{ $product->cost_price }}"
             editIsProduct="{{ $product->is_product }}">
            <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px"></i>
          </a>
          <a href="#" class="deleteDataBtn"
             deleteLabel="{{ $product->name }}"
             deleteId="{{ $product->id }}"
             deleteRow="{{ $row }}">
            <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px"></i>
          </a>
        </td>
      </tr>
    @endforeach
  </tbody>
</table>
</div>
</div>
</div></div></div>

{{-- ══════════════════════════════════════════════════════════════════════
     DOWNLOAD MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Download</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <p class="mb-2">Click a button to download base products data</p>
      <div class="buttons"></div>
    </div>
  </div></div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     INFO MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title"><i class="ri-information-line me-1 text-primary"></i> About Base Products</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <p class="mb-2"><strong>What is a Base Product?</strong><br>
      Base products are your <strong>master catalogue</strong> — the permanent list of everything you sell or offer. Each entry is defined once here and can then be assigned to one or more branches.</p>
      <p class="mb-2"><strong>Supplier (Required)</strong><br>
      Every product must be linked to a supplier. The supplier field drives the category classification — select the correct retail supplier when adding or editing a product.</p>
      <p class="mb-2"><strong>Default Prices</strong><br>
      The selling price and order/cost price set here apply to all branches by default. Individual branches can override these with their own prices if needed.</p>
      <p class="mb-0"><strong>Product vs Service</strong><br>
      Each entry is flagged as either a <strong>Product</strong> (a physical item) or a <strong>Service</strong> (a non-physical offering). This flag can be set or changed via the <strong>Edit</strong> form — new entries default to <em>Product</em>.</p>
    </div>
  </div></div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     VIEW PRODUCT MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="viewProductModal" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-info">
        <h5 class="modal-title mh-title"><i class="ri-eye-line"></i> Product Details</h5>
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
        <ul class="nav nav-tabs nav-sm mb-3" id="viewModalTabs" role="tablist" style="font-size:12px;">
          <li class="nav-item"><button class="nav-link active py-1 px-2" data-bs-toggle="tab" data-bs-target="#vw-t1"><i class="ri-price-tag-3-line me-1"></i>Identity</button></li>
          <li class="nav-item"><button class="nav-link py-1 px-2" data-bs-toggle="tab" data-bs-target="#vw-t2"><i class="ri-money-dollar-circle-line me-1"></i>Pricing</button></li>
        </ul>
        <div class="tab-content">
          <div class="tab-pane fade show active" id="vw-t1">
            <div class="view-grid">
              <div class="view-item"><label>Code</label><div class="view-val" id="vw-code"></div></div>
              <div class="view-item"><label>Unit of Measure</label><div class="view-val" id="vw-unit"></div></div>
              <div class="view-item"><label>Supplier</label><div class="view-val" id="vw-supplier"></div></div>
              <div class="view-item"><label>Type</label><div class="view-val" id="vw-type"></div></div>
              <div class="view-item full"><label>Description</label><div class="view-val" id="vw-description"></div></div>
            </div>
          </div>
          <div class="tab-pane fade" id="vw-t2">
            <div class="view-grid">
              <div class="view-item"><label>Default Selling Price (MWK)</label><div class="view-val price-cell" id="vw-sell"></div></div>
              <div class="view-item"><label>Default Order / Cost Price (MWK)</label><div class="view-val" id="vw-cost"></div></div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="padding:10px 20px 14px;justify-content:space-between;">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <a href="#" class="btn btn-info btn-sm text-white" id="vwEditBtn">
          <i class="ri-edit-box-line me-1"></i> Edit
        </a>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     ADD MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="newDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-add-circle-line"></i> Add Base Product</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:16px 20px 8px !important;">
        <form action="#" method="post" id="newDataForm">
          @csrf
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Product Name <span class="text-danger">*</span></label>
            <input class="form-control" type="text" name="name" id="new-name"
                   placeholder="e.g. Cooking Oil 2L" autocomplete="off" required />
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Supplier <span class="text-danger">*</span></label>
            <select class="form-select" name="supplier" id="new-supplier" required>
              <option value="">— Select Supplier —</option>
              @foreach($suppliers as $sup)
                <option value="{{ $sup->name }}">{{ $sup->name }}</option>
              @endforeach
            </select>
            @if($savedCategoryId && $suppliers->isEmpty())
              <div class="form-text text-warning"><i class="ri-information-line"></i> No retail suppliers for the selected category.</div>
            @elseif(!$savedCategoryId)
              <div class="form-text text-muted">Select a category filter to see suppliers.</div>
            @endif
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold" style="font-size:13px">Selling Price <small class="text-muted fw-normal">(MWK)</small></label>
              <input class="form-control" type="number" step="0.01" min="0"
                     name="selling_price" id="new-selling-price" placeholder="0.00" />
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold" style="font-size:13px">Order / Cost Price <small class="text-muted fw-normal">(MWK)</small></label>
              <input class="form-control" type="number" step="0.01" min="0"
                     name="cost_price" id="new-cost-price" placeholder="0.00" />
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Unit of Measure</label>
            <input class="form-control" type="text" name="unit" id="new-unit"
                   list="newUnitOptions" placeholder="e.g. Each, kg, Litre…" value="Each" autocomplete="off" />
            <datalist id="newUnitOptions">
              <option value="Each"><option value="kg"><option value="g">
              <option value="Litre"><option value="ml"><option value="Box">
              <option value="Carton"><option value="Pack"><option value="Pair">
              <option value="Dozen"><option value="Bag"><option value="Bottle">
              <option value="Metre"><option value="Service">
            </datalist>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Code <small class="text-muted fw-normal">(optional SKU)</small></label>
            <input class="form-control" type="text" name="code" id="new-code"
                   placeholder="e.g. OIL-001" autocomplete="off" />
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Description <small class="text-muted fw-normal">(optional)</small></label>
            <textarea class="form-control" name="description" id="new-description" rows="2"
                      placeholder="Brief description…"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer" style="padding:10px 20px 14px;justify-content:flex-end;gap:8px;">
        <a href="#" class="btn btn-secondary" id="cancelDataBtn"><i class="ri-close-line"></i> Cancel</a>
        <a href="#" class="btn btn-success"   id="submitDataBtn"><i class="ri-check-line"></i> Save Product</a>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     EXCEL SAMPLE MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="excelSampleModal" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-green">
        <h5 class="modal-title mh-title"><i class="ri-table-line"></i> CSV Column Guide</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:16px 20px !important;">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <strong style="font-size:13px"><i class="ri-table-line me-1 text-success"></i> Required CSV Structure</strong>
            <span class="ms-2 text-muted" style="font-size:12px">6 columns</span>
          </div>
          <a href="#" id="downloadTemplateBtn" class="btn btn-sm btn-success" style="font-size:12px">
            <i class="ri-download-2-line"></i> Download Template
          </a>
        </div>
        <div class="excel-preview-wrap mb-3">
          <div class="excel-header-bar"><i class="ri-file-excel-2-line"></i> base_products_template.csv</div>
          <div style="background:#f0faf5;border-bottom:1px solid #b7d5c4;padding:5px 10px;font-size:11px;color:#2d6a4f;display:flex;align-items:center;gap:5px;">
            <i class="ri-information-line"></i>
            <span>Row 1 is the <strong>header row</strong> — keep it exactly as shown. Your products start from row 2.</span>
          </div>
          <div class="table-responsive">
            <table class="excel-preview-table">
              <thead>
                <tr>
                  <th class="excel-row-num" style="background:#1a5c38;color:#aaa;">#</th>
                  <th class="col-name">name <span class="excel-req-star">*</span></th>
                  <th>supplier <span class="excel-req-star">*</span></th>
                  <th>code</th><th>unit</th>
                  <th>cost_price</th><th>selling_price</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="excel-row-num" style="background:#e8f5e9;color:#2d6a4f;font-weight:600;">1</td>
                  <td class="col-name" style="color:#2d6a4f;font-weight:600;">← header row</td>
                  <td style="color:#2d6a4f;">headers</td>
                  <td style="color:#2d6a4f;">headers</td><td style="color:#2d6a4f;">headers</td>
                  <td style="color:#2d6a4f;">headers</td><td style="color:#2d6a4f;">headers</td>
                </tr>
                <tr>
                  <td class="excel-row-num">2</td>
                  <td class="col-name excel-sample-val">Cooking Oil 2L</td>
                  <td>Unilever</td>
                  <td><span class="excel-col-code">OIL-001</span></td>
                  <td>Each</td><td>1,500.00</td><td>2,000.00</td>
                </tr>
                <tr>
                  <td class="excel-row-num">3</td>
                  <td class="col-name excel-sample-val">Drinking Water 500ml</td>
                  <td>Carlsberg</td>
                  <td><span class="excel-col-code">WAT-001</span></td>
                  <td>Each</td><td>350.00</td><td>500.00</td>
                </tr>
                <tr style="background:#fafafa;">
                  <td class="excel-row-num" style="color:#ccc;">4</td>
                  <td class="col-name excel-sample-muted">your product here…</td>
                  <td class="excel-sample-muted">required</td>
                  <td class="excel-sample-muted">optional</td>
                  <td class="excel-sample-muted">Each / kg / Litre…</td>
                  <td class="excel-sample-muted">numeric, no commas</td>
                  <td class="excel-sample-muted">numeric, no commas</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="alert alert-success border-0 py-2 px-3 mb-0" style="font-size:12px;border-radius:8px;background:#d8f3e6;">
          <strong><i class="ri-check-double-line me-1"></i>Auto-applied to all rows:</strong>
          is_product = true (Product). Supplier column is <strong>required</strong> — rows without a supplier will be skipped.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <a href="#" id="downloadTemplateBtnFooter" class="btn btn-success btn-sm">
          <i class="ri-download-2-line me-1"></i> Download Template
        </a>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     IMPORT MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="importModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
      <div class="modal-header mh-green">
        <h5 class="modal-title mh-title"><i class="ri-file-excel-2-line"></i> Import Products from CSV</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal" id="importModalCloseBtn"></button>
      </div>
      <div class="modal-body" style="padding:16px !important;">
        <div id="importSetupFields">
          <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span style="font-size:12px;font-weight:600;color:#1e293b;">
                <i class="ri-table-line me-1 text-success"></i>
                Prepare your CSV file:
              </span>
              <a href="#" id="viewSampleBtn" class="btn btn-sm btn-outline-success" style="font-size:12px;white-space:nowrap;">
                <i class="ri-table-line me-1"></i> View Full Guide
              </a>
            </div>
            <div class="excel-preview-wrap mb-2">
              <div class="excel-header-bar"><i class="ri-file-excel-2-line"></i> base_products_template.csv</div>
              <div style="background:#f0faf5;border-bottom:1px solid #b7d5c4;padding:5px 10px;font-size:11px;color:#2d6a4f;display:flex;align-items:center;gap:5px;">
                <i class="ri-information-line"></i>
                <strong>Row 1 is the header row</strong> — keep exactly as shown. Products start from row 2.
              </div>
              <div class="table-responsive">
                <table class="excel-preview-table">
                  <thead>
                    <tr>
                      <th class="excel-row-num" style="background:#1a5c38;color:#aaa;">#</th>
                      <th class="col-name">name <span class="excel-req-star">*</span></th>
                      <th>supplier <span class="excel-req-star">*</span></th>
                      <th>code</th><th>unit</th>
                      <th>cost_price</th><th>selling_price</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td class="excel-row-num" style="background:#e8f5e9;color:#2d6a4f;font-weight:600;">1</td>
                      <td class="col-name" style="color:#2d6a4f;font-weight:600;">← header row</td>
                      <td style="color:#2d6a4f;">headers</td>
                      <td style="color:#2d6a4f;">headers</td><td style="color:#2d6a4f;">headers</td>
                      <td style="color:#2d6a4f;">headers</td><td style="color:#2d6a4f;">headers</td>
                    </tr>
                    <tr>
                      <td class="excel-row-num">2</td>
                      <td class="col-name excel-sample-val">Cooking Oil 2L</td>
                      <td>Unilever</td>
                      <td><span class="excel-col-code">OIL-001</span></td>
                      <td>Each</td><td>1500.00</td><td>2000.00</td>
                    </tr>
                    <tr style="background:#fafafa;">
                      <td class="excel-row-num" style="color:#ccc;">3</td>
                      <td class="col-name excel-sample-muted">your product here…</td>
                      <td class="excel-sample-muted">required</td>
                      <td class="excel-sample-muted">optional</td>
                      <td class="excel-sample-muted">Each / kg / Litre…</td>
                      <td class="excel-sample-muted">numeric, no commas</td>
                      <td class="excel-sample-muted">numeric, no commas</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
            <div class="d-flex justify-content-end mb-2">
              <a href="#" id="downloadTemplateInline" class="btn btn-sm btn-success" style="font-size:12px">
                <i class="ri-download-2-line me-1"></i> Download Template
              </a>
            </div>
          </div>

          <div id="importStepSetup">
            <div class="drop-zone" id="dropZone">
              <input type="file" id="csvFileInput" accept=".csv,text/csv">
              <i class="ri-file-excel-2-line"></i>
              <p class="mb-1 fw-semibold" style="font-size:13px">Drop your CSV file here</p>
              <p class="text-muted mb-0" style="font-size:12px">or click to browse — CSV files only</p>
            </div>
            <div id="csvFileName" class="mt-2 text-muted" style="font-size:12px;display:none;">
              <i class="ri-file-line text-success"></i> <span id="csvFileNameText"></span>
            </div>
          </div>

          <div id="importStepPreview" style="display:none;">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div>
                <strong style="font-size:13px">Preview</strong>
                <span class="ms-2 text-muted" style="font-size:12px">First 5 of <strong id="importTotalCount">0</strong> rows</span>
              </div>
              <a href="#" id="resetCsvBtn" class="btn btn-sm btn-outline-secondary" style="font-size:12px">
                <i class="ri-refresh-line"></i> Change file
              </a>
            </div>
            <div class="table-responsive" style="max-height:180px;overflow-y:auto;border:1px solid #dee2e6;border-radius:6px;">
              <table class="table table-sm table-bordered mb-0" id="importPreviewTable">
                <thead id="importPreviewHead"></thead>
                <tbody id="importPreviewBody"></tbody>
              </table>
            </div>
            <div class="alert alert-success border-0 mt-3 py-2 px-3 mb-0 d-flex align-items-center gap-2"
                 style="font-size:13px;border-radius:8px;">
              <i class="ri-check-double-line fs-5 text-success"></i>
              <span>Ready to import <strong><span id="importConfirmCount">0</span> products</strong>.</span>
            </div>
          </div>
        </div>

        <div id="importStepProgress" style="display:none;">
          <div class="text-center mb-3">
            <i class="ri-loader-4-line text-success" style="font-size:40px;animation:spin 1s linear infinite"></i>
            <p class="mt-2 mb-0 fw-semibold" style="font-size:14px">Importing products…</p>
            <p class="text-muted" style="font-size:12px" id="importProgressText">0 of 0 done</p>
          </div>
          <div class="import-progress-bar mb-2"><div class="bar" id="importBarFill"></div></div>
          <div id="importLog" style="max-height:140px;overflow-y:auto;font-size:11px;background:#f8f9fa;border-radius:6px;padding:8px;border:1px solid #dee2e6;"></div>
        </div>

        <div id="importStepDone" style="display:none;">
          <div class="text-center py-3">
            <i class="ri-checkbox-circle-line text-success" style="font-size:52px"></i>
            <h5 class="mt-2">Import Complete!</h5>
            <p class="text-muted mb-0" style="font-size:13px" id="importDoneSummary"></p>
            <p class="text-muted" style="font-size:12px">Page will reload shortly.</p>
          </div>
        </div>
      </div>

      <div class="modal-footer" style="justify-content:space-between;padding:10px 18px 14px;">
        <div></div>
        <div class="d-flex gap-2">
          <a href="#" class="btn btn-secondary btn-sm" id="cancelImportBtn" style="font-size:12px">
            <i class="ri-close-line"></i> Cancel
          </a>
          <a href="#" class="btn btn-success btn-sm" id="submitImportBtn" style="font-size:12px;display:none;" disabled>
            <i class="ri-upload-2-line"></i> Start Import
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     SINGLE DELETE MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="singleDeleteDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:350px;margin:1.75rem auto;">
    <div class="modal-content">
      <div class="modal-body text-center pb-4">
        <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
        <form action="#" method="post" id="singleDeleteDataForm">
          @csrf
          <h4 class="mt-2">Delete <span id="singleDisplayDeleteLabel" class="text-danger"></span>?</h4>
          <h5>This cannot be undone.</h5>
          <input type="hidden" id="singleDeleteId"  name="id">
          <input type="hidden" id="singleDeleteRow">
          <a href="#" class="btn btn-danger me-2 mt-3" id="submitSingleDeleteDataBtn">Yes, Delete it</a>
          <a href="#" class="btn btn-info mt-3"        id="keepSingleDataBtn">No, Keep it</a>
        </form>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     BULK DELETE MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="multipleDeleteDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:350px;margin:1.75rem auto;">
    <div class="modal-content">
      <div class="modal-body text-center pb-4">
        <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
        <form action="#" method="post" id="multipleDeleteDataForm">
          @csrf
          <h4 class="mt-2">Delete <span id="multipleDisplayDeleteLabel"></span>?</h4>
          <h5>This cannot be undone.</h5>
          <input type="hidden" id="multipleDeleteIds"  name="ids[]">
          <input type="hidden" id="multipleDeleteRows">
          <a href="#" class="btn btn-danger me-2 mt-3" id="submitMultipleDeleteDataBtn">Yes, Delete them</a>
          <a href="#" class="btn btn-info mt-3"         id="keepMultipleDataBtn">No, Keep them</a>
        </form>
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
      <div class="modal-header mh-orange">
        <h5 class="modal-title mh-title">
          <i class="ri-checkbox-multiple-line"></i>
          Bulk Actions — <span id="bulkActionsCount">0</span> item(s) selected
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:16px 18px !important;">
        <div class="bulk-section">
          <div class="bulk-section-title"><i class="ri-truck-line me-1"></i> Change Supplier</div>
          <div class="d-flex gap-2 align-items-center">
            <select class="form-select form-select-sm" id="bulkSupplierSelect">
              <option value="">— Select Supplier —</option>
              @foreach($suppliers as $sup)
                <option value="{{ $sup->name }}">{{ $sup->name }}</option>
              @endforeach
            </select>
            <a href="#" class="btn btn-sm btn-warning text-dark" id="applyBulkSupplierBtn" style="white-space:nowrap">
              <i class="ri-check-line me-1"></i> Apply
            </a>
          </div>
        </div>
        <div class="bulk-section">
          <div class="bulk-section-title"><i class="ri-toggle-line me-1"></i> Set Type</div>
          <div class="d-flex gap-2">
            <a href="#" class="btn btn-sm btn-success text-white flex-fill" id="bulkMarkProductBtn">
              <i class="ri-box-3-line me-1"></i> Mark as Product
            </a>
            <a href="#" class="btn btn-sm btn-warning text-dark flex-fill" id="bulkMarkServiceBtn">
              <i class="ri-service-line me-1"></i> Mark as Service
            </a>
          </div>
        </div>
        <div class="d-grid mt-1">
          <a href="#" class="btn btn-danger" id="deleteSelectedBtn">
            <i class="ri-delete-bin-line me-1"></i> Delete Selected
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════
     EDIT MODAL  ── Prices on Tab 1, identity (read-only) on Tab 2
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-edit-box-line"></i> Update Base Product</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>

      <ul class="nav nav-tabs border-bottom px-2 pt-2" role="tablist" style="font-size:12px;flex-wrap:nowrap;">
        <li class="nav-item">
          <button class="nav-link active px-3 py-1" data-bs-toggle="tab" data-bs-target="#et1" type="button">
            <i class="ri-money-dollar-circle-line me-1"></i>Prices
          </button>
        </li>
        <li class="nav-item">
          <button class="nav-link px-3 py-1" data-bs-toggle="tab" data-bs-target="#et2" type="button">
            <i class="ri-information-line me-1"></i>Details
          </button>
        </li>
      </ul>

      <div class="modal-body" style="padding:14px 18px 8px !important;">
        <form action="#" method="post" id="editDataForm">
          @csrf
          <input type="hidden" name="id"      id="editId">
          <input type="hidden" name="editrow" id="editRow">
          {{-- Hidden fields carry non-price values through so they're always submitted --}}
          <input type="hidden" name="name"        id="editNameHidden">
          <input type="hidden" name="supplier"    id="editSupplierHidden">
          <input type="hidden" name="code"        id="editCodeHidden">
          <input type="hidden" name="unit"        id="editUnitHidden">
          <input type="hidden" name="description" id="editDescriptionHidden">
          <input type="hidden" name="is_product"  id="editIsProductHidden">

          <div class="tab-content">

            {{-- ── Tab 1: Prices (editable) ─────────────────────────────── --}}
            <div class="tab-pane fade show active" id="et1" role="tabpanel">

              {{-- Read-only identity summary at top --}}
              <div class="row g-2 mb-3 mt-1">
                <div class="col-8">
                  <label class="form-label fw-semibold" style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;">Product</label>
                  <input type="text" class="form-control form-control-sm edit-readonly-field" id="et1-name-display" readonly tabindex="-1" />
                </div>
                <div class="col-4">
                  <label class="form-label fw-semibold" style="font-size:11px;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;">Unit</label>
                  <input type="text" class="form-control form-control-sm edit-readonly-field" id="et1-unit-display" readonly tabindex="-1" />
                </div>
              </div>

              <hr class="my-2">

              <div class="row g-2 mb-2">
                <div class="col-6">
                  <label class="form-label fw-semibold" style="font-size:13px">Selling Price <span class="text-danger">*</span> <small class="text-muted fw-normal">(MWK)</small></label>
                  <input class="form-control form-control-sm" type="number" step="0.01" min="0"
                         name="selling_price" id="editSellingPrice" placeholder="0.00" />
                </div>
                <div class="col-6">
                  <label class="form-label fw-semibold" style="font-size:13px">Order / Cost Price <small class="text-muted fw-normal">(MWK)</small></label>
                  <input class="form-control form-control-sm" type="number" step="0.01" min="0"
                         name="cost_price" id="editCostPrice" placeholder="0.00" />
                </div>
              </div>

              <div class="alert border-0 py-2 px-3 mt-2"
                   style="background:#f0f3ff;border-left:3px solid #4B5EBD !important;border-radius:0 5px 5px 0;font-size:11px;color:#3a4a9a;">
                <i class="ri-information-line me-1"></i>
                These are the <strong>default prices</strong> for all branches. Individual branches can override them with their own prices.
              </div>
            </div>

            {{-- ── Tab 2: Details (read-only display + editable fields) ───── --}}
            <div class="tab-pane fade" id="et2" role="tabpanel">
              <div class="mb-2 mt-1">
                <label class="form-label fw-semibold" style="font-size:13px">Product Name <span class="text-danger">*</span></label>
                <input class="form-control form-control-sm" type="text" id="editName" autocomplete="off"
                       placeholder="Product name" required />
              </div>
              <div class="mb-2">
                <label class="form-label fw-semibold" style="font-size:13px">Supplier <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm" id="editSupplier" required>
                  <option value="">— Select Supplier —</option>
                  @foreach($suppliers as $sup)
                    <option value="{{ $sup->name }}">{{ $sup->name }}</option>
                  @endforeach
                </select>
                <div class="form-text" id="editSupplierHint">Suppliers shown for the current category filter.</div>
              </div>
              <div class="mb-2">
                <label class="form-label fw-semibold" style="font-size:13px">Unit of Measure <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm" id="editUnit" required>
                  <option value="Each">Each</option><option value="kg">Kilogram (kg)</option>
                  <option value="g">Gram (g)</option><option value="Litre">Litre</option>
                  <option value="ml">Millilitre (ml)</option><option value="Box">Box</option>
                  <option value="Carton">Carton</option><option value="Pack">Pack</option>
                  <option value="Pair">Pair</option><option value="Dozen">Dozen</option>
                  <option value="Bag">Bag</option><option value="Bottle">Bottle</option>
                  <option value="Metre">Metre</option><option value="Service">Service (N/A)</option>
                </select>
              </div>
              <div class="mb-2">
                <label class="form-label fw-semibold" style="font-size:13px">Code <small class="text-muted fw-normal">(SKU)</small></label>
                <input class="form-control form-control-sm" type="text" id="editCode" autocomplete="off" />
              </div>
              <div class="mb-2">
                <label class="form-label fw-semibold" style="font-size:13px">Description</label>
                <textarea class="form-control form-control-sm" id="editDescription" rows="2"></textarea>
              </div>
              <div class="mb-2">
                <label class="form-label fw-semibold d-block" style="font-size:13px">Type</label>
                <div class="d-flex gap-3">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" id="editIsProductYes" value="1">
                    <label class="form-check-label" for="editIsProductYes">
                      <span class="type-badge-product"><i class="ri-box-3-line me-1"></i>Product</span>
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" id="editIsProductNo" value="0">
                    <label class="form-check-label" for="editIsProductNo">
                      <span class="type-badge-service"><i class="ri-service-line me-1"></i>Service</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </form>
      </div>

      <div class="modal-footer" style="padding:10px 18px 14px;justify-content:flex-end;gap:8px;">
        <a href="#" class="btn btn-secondary btn-sm" id="cancelEditDataBtn">Cancel</a>
        <a href="#" class="btn btn-primary btn-sm"   id="submitUpdateDataBtn">
          <i class="ri-check-line me-1"></i> Update
        </a>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {

    toastr.options = {
        closeButton: true, progressBar: true,
        showMethod: 'slideDown', timeOut: 5000, allowHtml: true
    };

    function handleAjaxError(xhr, status) {
        if (status === 'timeout')    { toastr.error('The request timed out.', 'Timeout Error'); }
        else if (xhr.status === 0)   { toastr.error('Unable to connect.', 'Connection Error'); }
        else if (xhr.status === 422) {
            var errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : {};
            var msg = ''; $.each(errors, function (k, v) { msg += v + '\n'; });
            toastr.error(msg || 'Validation failed.', 'Validation Errors');
        } else if (xhr.status === 500) { toastr.error('Server error.', 'Server Error'); }
        else { toastr.error('Unspecified error.', 'Error'); }
    }

    function fmtPrice(val) {
        if (val === null || val === '' || val === undefined) return '—';
        var n = parseFloat(val);
        return isNaN(n) ? '—' : n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function typeBadge(isProduct) {
        return parseInt(isProduct) === 1
            ? '<span class="type-badge-product"><i class="ri-box-3-line me-1"></i>Product</span>'
            : '<span class="type-badge-service"><i class="ri-service-line me-1"></i>Service</span>';
    }

    function buildRow(p) {
        var spCell = (p.selling_price !== null && p.selling_price !== '')
            ? '<span class="price-cell">' + fmtPrice(p.selling_price) + '</span>'
            : '<span class="text-muted" style="font-size:12px">—</span>';
        var cpCell = (p.cost_price !== null && p.cost_price !== '')
            ? '<span style="font-size:12px;color:#6c757d">' + fmtPrice(p.cost_price) + '</span>'
            : '<span class="text-muted" style="font-size:12px">—</span>';
        function d(v) { return (v || '').toString().replace(/"/g, '&quot;'); }

        return `<tr id="${p.row}">
            <td>
                <input type="checkbox" class="selectRow" value="${p.id}" data-row-id="${p.row}">
                &nbsp;${p.name}
            </td>
            <td>${p.code || '—'}</td>
            <td>${p.unit}</td>
            <td>${cpCell}</td>
            <td>${spCell}</td>
            <td>
                <a href="#" class="viewDataBtn"
                   data-id="${p.id}" data-name="${d(p.name)}" data-description="${d(p.description)}"
                   data-supplier="${d(p.supplier)}" data-code="${d(p.code)}"
                   data-unit="${d(p.unit)}"
                   data-sell="${p.selling_price !== null ? p.selling_price : ''}"
                   data-cost="${p.cost_price    !== null ? p.cost_price    : ''}"
                   data-is-product="${p.is_product}">
                   <i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px"></i>
                </a>
                <a href="#" class="editDataBtn"
                   editId="${p.id}" editRow="${p.row}" editName="${d(p.name)}"
                   editDescription="${d(p.description)}" editSupplier="${d(p.supplier)}"
                   editCode="${d(p.code)}" editUnit="${d(p.unit)}"
                   editSellingPrice="${p.selling_price !== null ? p.selling_price : ''}"
                   editCostPrice="${p.cost_price    !== null ? p.cost_price    : ''}"
                   editIsProduct="${p.is_product}">
                   <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px"></i>
                </a>
                <a href="#" class="deleteDataBtn"
                   deleteLabel="${d(p.name)}" deleteId="${p.id}" deleteRow="${p.row}">
                   <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px"></i>
                </a>
            </td>
        </tr>`;
    }

    function updateSelectedCount() {
        var count = $('.selectRow:checked').length;
        $('#selectedCount').text(count);
        if (count > 0) $('#bulkTriggerBtn').addClass('visible');
        else           $('#bulkTriggerBtn').removeClass('visible');
    }

    // ════════════════════════════════════════════════════════════════════════
    //  IMPORT
    // ════════════════════════════════════════════════════════════════════════
    var parsedCsvRows = [];
    var IMPORT_KEY = 'bp_import_queue';

    function resetImportModal() {
        parsedCsvRows = [];
        $('#csvFileInput').val('');
        $('#csvFileName').hide();
        $('#importStepSetup').show();
        $('#importStepPreview').hide();
        $('#importStepProgress').hide();
        $('#importStepDone').hide();
        $('#importSetupFields').show();
        $('#importPreviewHead, #importPreviewBody, #importLog').empty();
        $('#importBarFill').css('width', '0');
        $('#cancelImportBtn').prop('disabled', false).html('<i class="ri-close-line"></i> Cancel');
        $('#submitImportBtn').show().prop('disabled', true).html('<i class="ri-upload-2-line"></i> Start Import');
    }

    $('#importBtn').on('click', function(e) {
        e.preventDefault(); resetImportModal(); $('#importModal').modal('show');
    });
    $('#cancelImportBtn').on('click', function(e) {
        e.preventDefault(); localStorage.removeItem(IMPORT_KEY); resetImportModal(); $('#importModal').modal('hide');
    });
    $('#importModal').on('hidden.bs.modal', function() {
        localStorage.removeItem(IMPORT_KEY); resetImportModal();
    });
    $('#importModalCloseBtn').on('click', function() {
        localStorage.removeItem(IMPORT_KEY); resetImportModal();
    });
    $('#viewSampleBtn').on('click', function(e) {
        e.preventDefault(); $('#importModal').modal('hide'); $('#excelSampleModal').modal('show');
    });

    function downloadTemplate() {
        var header = 'name,supplier,code,unit,cost_price,selling_price';
        var row1   = 'Cooking Oil 2L,Unilever,OIL-001,Each,1500.00,2000.00';
        var row2   = 'Drinking Water 500ml,Carlsberg,WAT-001,Each,350.00,500.00';
        var row3   = 'Bread Loaf 700g,Lyons,BRD-001,Each,600.00,800.00';
        var blob   = new Blob([header+'\n'+row1+'\n'+row2+'\n'+row3], {type:'text/csv;charset=utf-8;'});
        var url    = URL.createObjectURL(blob);
        var a      = document.createElement('a'); a.href=url; a.download='base_products_template.csv'; a.click();
        URL.revokeObjectURL(url);
    }
    $('#downloadTemplateBtn, #downloadTemplateBtnFooter, #downloadTemplateInline').on('click', function(e) {
        e.preventDefault(); downloadTemplate();
    });

    var dz = document.getElementById('dropZone');
    if (dz) {
        dz.addEventListener('dragover',  function(e) { e.preventDefault(); dz.classList.add('drag-over'); });
        dz.addEventListener('dragleave', function()  { dz.classList.remove('drag-over'); });
        dz.addEventListener('drop', function(e) {
            e.preventDefault(); dz.classList.remove('drag-over');
            var file = e.dataTransfer.files[0]; if (file) processCSVFile(file);
        });
    }
    $('#csvFileInput').on('change', function() {
        if (this.files && this.files[0]) processCSVFile(this.files[0]);
    });

    function processCSVFile(file) {
        if (!file.name.match(/\.csv$/i)) { toastr.error('Please select a valid CSV file.', 'Invalid File'); return; }
        $('#csvFileNameText').text(file.name); $('#csvFileName').show();
        var reader = new FileReader();
        reader.onload = function(e) { parseCSV(e.target.result); };
        reader.readAsText(file, 'UTF-8');
    }

    function parseCSV(text) {
        var lines = text.split(/\r?\n/).filter(function(l) { return l.trim() !== ''; });
        if (lines.length < 2) { toastr.error('CSV has no data rows.', 'Empty File'); return; }
        var headers = lines[0].split(',').map(function(h) { return h.trim().replace(/^"|"$/g,''); });
        parsedCsvRows = [];
        for (var i = 1; i < lines.length; i++) {
            var cols = splitCSVLine(lines[i]);
            if (!cols.length) continue;
            var row = {};
            for (var j = 0; j < headers.length; j++) {
                row[headers[j]] = (cols[j] !== undefined) ? cols[j].trim().replace(/^"|"$/g,'') : '';
            }
            if (!row['name'] || row['name'].trim() === '') continue;
            parsedCsvRows.push(row);
        }
        if (!parsedCsvRows.length) { toastr.error('No valid data rows found.', 'Empty Data'); return; }
        try { localStorage.setItem(IMPORT_KEY, JSON.stringify(parsedCsvRows)); } catch(ex) {}
        showImportPreview(headers, parsedCsvRows);
        $('#importStepSetup').hide(); $('#importStepPreview').show();
        $('#importTotalCount').text(parsedCsvRows.length);
        $('#importConfirmCount').text(parsedCsvRows.length);
        $('#submitImportBtn').prop('disabled', false);
    }

    function splitCSVLine(line) {
        var result=[], current='', inQ=false;
        for (var i=0; i<line.length; i++) {
            var ch = line[i];
            if (ch==='"') { inQ=!inQ; }
            else if (ch===','&&!inQ) { result.push(current); current=''; }
            else { current+=ch; }
        }
        result.push(current); return result;
    }

    function showImportPreview(headers, rows) {
        var displayMap = {
            'name':'Product Name','supplier':'Supplier','code':'Code',
            'unit':'Unit','cost_price':'Order Price','selling_price':'Selling Price'
        };
        var displayOrder = ['name','supplier','code','unit','cost_price','selling_price'];
        var shown = displayOrder.filter(function(k) { return headers.indexOf(k) >= 0; });
        if (!shown.length) shown = headers.slice(0,6);

        var thead = '<tr>' + shown.map(function(k, idx) {
            return '<th' + (idx===0?'':' style="text-align:center"') + '>' + (displayMap[k]||k) + '</th>';
        }).join('') + '</tr>';
        $('#importPreviewHead').html(thead);

        var tbody = '';
        var limit = Math.min(5, rows.length);
        for (var i=0; i<limit; i++) {
            var r = rows[i];
            tbody += '<tr>' + shown.map(function(k, idx) {
                var v = r[k] || '—';
                if ((k==='cost_price'||k==='selling_price') && r[k] && !isNaN(parseFloat(r[k]))) {
                    v = parseFloat(r[k]).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
                }
                return '<td' + (idx===0?'':' style="text-align:center"') + '>' + v + '</td>';
            }).join('') + '</tr>';
        }
        $('#importPreviewBody').html(tbody);
    }

    $('#resetCsvBtn').on('click', function(e) {
        e.preventDefault();
        parsedCsvRows = [];
        $('#csvFileInput').val(''); $('#csvFileName').hide();
        $('#importStepPreview').hide(); $('#importStepSetup').show();
        localStorage.removeItem(IMPORT_KEY);
        $('#submitImportBtn').prop('disabled', true);
    });

    $('#submitImportBtn').on('click', function(e) {
        e.preventDefault();
        var queue = [];
        try { var s = localStorage.getItem(IMPORT_KEY); queue = s ? JSON.parse(s) : parsedCsvRows; }
        catch(ex) { queue = parsedCsvRows; }
        if (!queue || !queue.length) { toastr.error('No data to import.', 'Error'); return; }
        runImport(queue);
    });

    function runImport(queue) {
        $('#importSetupFields').hide();
        $('#importStepPreview').hide(); $('#importStepProgress').show();
        $('#submitImportBtn').prop('disabled', true);
        $('#cancelImportBtn').prop('disabled', true);

        var total=queue.length, done=0, succeeded=0, failed=0;
        $('#importProgressText').text('0 of '+total+' done');

        function importNext(index) {
            if (index >= queue.length) {
                localStorage.removeItem(IMPORT_KEY);
                $('#importStepProgress').hide(); $('#importStepDone').show();
                $('#importDoneSummary').text(succeeded+' imported, '+failed+' skipped/failed.');
                $('#cancelImportBtn').prop('disabled',false).html('<i class="ri-close-line"></i> Close');
                setTimeout(function() { location.reload(); }, 3000);
                return;
            }
            var row = queue[index];
            $.ajax({
                type:'POST', url:'{{ route("retail.operations.baseproducts.import.row") }}',
                data:{
                    name:          row.name          || '',
                    supplier:      row.supplier      || '',
                    code:          row.code          || '',
                    unit:          row.unit          || 'Each',
                    cost_price:    row.cost_price    || '',
                    selling_price: row.selling_price || '',
                    description:   row.description   || '',
                    is_product:    1,
                    _token: '{{ csrf_token() }}'
                },
                timeout:30000,
                success: function(data) {
                    done++;
                    if (data.status===201) {
                        succeeded++;
                        appendLog('<span class="text-success">✓</span> Row '+(index+1)+': '+row.name+' imported.');
                        if (data.product && window._dt) { window._dt.row.add($(buildRow(data.product))); }
                    } else {
                        failed++;
                        appendLog('<span class="text-danger">✗</span> Row '+(index+1)+': '+(data.error||'Failed')+' ('+row.name+')');
                    }
                },
                error: function() {
                    done++; failed++;
                    appendLog('<span class="text-danger">✗</span> Row '+(index+1)+': Network error ('+row.name+')');
                },
                complete: function() {
                    var pct = Math.round((done/total)*100);
                    $('#importBarFill').css('width',pct+'%');
                    $('#importProgressText').text(done+' of '+total+' done');
                    importNext(index+1);
                }
            });
        }
        importNext(0);
    }

    function appendLog(msg) {
        var el = $('#importLog'); el.append('<div>'+msg+'</div>'); el.scrollTop(el[0].scrollHeight);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  DataTable
    // ════════════════════════════════════════════════════════════════════════
    var table = $('#maintable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[100,250,500,-1],[100,250,500,'All']],
        fixedColumns: { leftColumns: 1 },
        scrollX: true,
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: 0,      className: 'text-start'  }
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

    $('#infoBtn').on('click',         function(e) { e.preventDefault(); $('#infoModal').modal('show'); });
    $('#tableButtonsBtn').on('click', function(e) { e.preventDefault(); $('#buttonsModal').modal('show'); });
    $('#bulkTriggerBtn').on('click',  function(e) {
        e.preventDefault();
        $('#bulkActionsCount').text($('.selectRow:checked').length);
        $('#bulkActionsModal').modal('show');
    });

    // ── VIEW ──────────────────────────────────────────────────────────────
    var _viewData = {};
    $('#tbody').on('click', '.viewDataBtn', function(e) {
        e.preventDefault();
        var b = $(this);
        _viewData = {
            id:        b.data('id'),
            name:      b.data('name'),
            description: b.data('description'),
            supplier:  b.data('supplier'),
            code:      b.data('code'),
            unit:      b.data('unit'),
            sell:      b.data('sell'),
            cost:      b.data('cost'),
            isProduct: b.data('is-product'),
            editRow:   b.closest('tr').attr('id')
        };

        function v(val) {
            return (val===''||val===null||val===undefined||val==='null')
                ? '<span class="muted">—</span>' : val;
        }
        $('#vw-name').text(_viewData.name);
        $('#vw-code-line').text(_viewData.code ? 'Code: '+_viewData.code : '');
        $('#vw-badges').html(typeBadge(_viewData.isProduct));
        $('#vw-code').html(v(_viewData.code));
        $('#vw-unit').html(v(_viewData.unit));
        $('#vw-supplier').html(v(_viewData.supplier));
        $('#vw-type').html(typeBadge(_viewData.isProduct));
        $('#vw-description').html(v(_viewData.description));
        $('#vw-sell').html(_viewData.sell!==''&&_viewData.sell!==null ? fmtPrice(_viewData.sell) : '<span class="muted">—</span>');
        $('#vw-cost').html(_viewData.cost!==''&&_viewData.cost!==null ? fmtPrice(_viewData.cost) : '<span class="muted">—</span>');
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

    // ── ADD  ── supplier does NOT reset after successful save ─────────────
    $('#newDataBtn').on('click', function(e) {
        e.preventDefault(); resetNewModal(); $('#newDataModal').modal('show');
    });
    $('#newDataModal').on('hidden.bs.modal', resetNewModal);

    $('#submitDataBtn').on('click', function(e) {
        e.preventDefault();
        if (!$('#new-name').val().trim()) {
            toastr.warning('Product name is required.','Required'); $('#new-name').focus(); return;
        }
        if (!$('#new-supplier').val()) {
            toastr.warning('Please select a supplier.','Required'); $('#new-supplier').focus(); return;
        }
        var self=$(this); self.prop('disabled',true);
        $.ajaxSetup({ headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:'POST', url:'{{ route("retail.operations.baseproducts.insert") }}',
            data:$('#newDataForm').serializeArray(), timeout:60000,
            beforeSend:function() { $('#progressBar').show(); },
            complete:  function() { $('#progressBar').hide(); self.prop('disabled',false); },
            success: function(data) {
                if (data.status===201) {
                    toastr.success(data.success,'Success');
                    table.row.add($(buildRow(data.product))).draw(false);

                    // ── Reset only name, prices, code, description, unit
                    // ── Supplier is intentionally KEPT so user can add more
                    //    products for the same supplier without reselecting.
                    $('#new-name').val('').focus();
                    $('#new-selling-price').val('');
                    $('#new-cost-price').val('');
                    $('#new-code').val('');
                    $('#new-description').val('');
                    $('#new-unit').val('Each');
                    // $('#new-supplier') is NOT reset

                } else if (data.status===422) {
                    toastr.error(data.error||'Validation failed.','Error');
                } else { toastr.info('Unspecified error.','Error'); }
            },
            error: handleAjaxError
        });
    });
    $('#cancelDataBtn').on('click', function(e) {
        e.preventDefault(); resetNewModal(); $('#newDataModal').modal('hide');
    });

    // Full reset (only on Cancel / modal close)
    function resetNewModal() {
        $('#newDataForm')[0].reset();
        $('#new-unit').val('Each');
    }

    // ── SINGLE DELETE ─────────────────────────────────────────────────────
    $('#tbody').on('click', '.deleteDataBtn', function() {
        $('#singleDisplayDeleteLabel').text($(this).attr('deleteLabel'));
        $('#singleDeleteRow').val($(this).attr('deleteRow'));
        $('#singleDeleteId').val($(this).attr('deleteId'));
        $('#singleDeleteDataModal').modal('show');
    });
    $('#keepSingleDataBtn').on('click', function(e) {
        e.preventDefault(); toastr.info('Your data is safe','Great!'); $('#singleDeleteDataModal').modal('hide');
    });
    $('#submitSingleDeleteDataBtn').on('click', function(e) {
        e.preventDefault();
        var self=$(this); self.prop('disabled',true);
        var row=$('#singleDeleteRow').val(), id=$('#singleDeleteId').val();
        $.ajaxSetup({ headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:'POST', url:'{{ route("retail.operations.baseproducts.delete") }}',
            data:{id:id,_token:'{{ csrf_token() }}'}, timeout:60000,
            beforeSend:function() { $('#progressBar').show(); },
            complete:  function() { $('#progressBar').hide(); self.prop('disabled',false); },
            success: function(data) {
                if (data.status===201) {
                    toastr.success(data.success,'Success');
                    $('#singleDeleteDataModal').modal('hide');
                    table.row('#'+row).remove().draw(false);
                    updateSelectedCount();
                } else if (data.status===422) {
                    toastr.error(data.error||'Validation failed.','Error');
                } else { toastr.info('Unspecified error.','Error'); }
            },
            error: handleAjaxError
        });
    });

    // ── EDIT ── prices on Tab 1, sync hidden fields before submit ─────────
    $('#tbody').on('click', '.editDataBtn', function() {
        var b=$(this);
        var nm   = b.attr('editName');
        var unit = b.attr('editUnit');

        // Populate Tab 1 read-only displays
        $('#et1-name-display').val(nm);
        $('#et1-unit-display').val(unit);

        // Populate Tab 2 editable fields
        $('#editId').val(b.attr('editId'));
        $('#editRow').val(b.attr('editRow'));
        $('#editName').val(nm);
        $('#editSupplier').val(b.attr('editSupplier'));
        $('#editCode').val(b.attr('editCode'));
        $('#editUnit').val(unit);
        $('#editDescription').val(b.attr('editDescription'));
        $('#editSellingPrice').val(b.attr('editSellingPrice'));
        $('#editCostPrice').val(b.attr('editCostPrice'));

        var ip = parseInt(b.attr('editIsProduct'));
        if (ip === 1) { $('#editIsProductYes').prop('checked', true); }
        else          { $('#editIsProductNo').prop('checked', true);  }

        // Start on the prices tab
        $('button[data-bs-target="#et1"]').tab('show');
        $('#editDataModal').modal('show');
    });

    $('#submitUpdateDataBtn').on('click', function(e) {
        e.preventDefault();

        // Sync hidden fields from Tab 2 visible fields before serialising
        $('#editNameHidden').val($('#editName').val());
        $('#editSupplierHidden').val($('#editSupplier').val());
        $('#editCodeHidden').val($('#editCode').val());
        $('#editUnitHidden').val($('#editUnit').val());
        $('#editDescriptionHidden').val($('#editDescription').val());
        $('#editIsProductHidden').val($('input[name="is_product"]:checked').length
            ? $('input[name="is_product"]:checked').val() : '1');

        if (!$('#editName').val().trim()) {
            toastr.warning('Product name is required.','Required');
            $('button[data-bs-target="#et2"]').tab('show');
            $('#editName').focus(); return;
        }
        if (!$('#editSupplier').val()) {
            toastr.warning('Please select a supplier.','Required');
            $('button[data-bs-target="#et2"]').tab('show');
            $('#editSupplier').focus(); return;
        }

        var self=$(this); self.prop('disabled',true);
        var row=$('#editRow').val();

        // Build data from form + hidden fields
        var formData = {
            id:            $('#editId').val(),
            editrow:       row,
            name:          $('#editNameHidden').val(),
            supplier:      $('#editSupplierHidden').val(),
            code:          $('#editCodeHidden').val(),
            unit:          $('#editUnitHidden').val(),
            description:   $('#editDescriptionHidden').val(),
            is_product:    $('#editIsProductHidden').val(),
            selling_price: $('#editSellingPrice').val(),
            cost_price:    $('#editCostPrice').val(),
            _token:        '{{ csrf_token() }}'
        };

        $.ajaxSetup({ headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:'POST', url:'{{ route("retail.operations.baseproducts.update") }}',
            data: formData, timeout:60000,
            beforeSend:function() { $('#progressBar').show(); },
            complete:  function() { $('#progressBar').hide(); self.prop('disabled',false); },
            success: function(data) {
                if (data.status===201) {
                    toastr.success(data.success,'Success');
                    $('#editDataModal').modal('hide');
                    table.row('#'+row).remove();
                    table.row.add($(buildRow(data.product))).draw(false);
                    updateSelectedCount();
                } else if (data.status===422) {
                    toastr.error(data.error||'Validation failed.','Error');
                } else { toastr.info('Unspecified error.','Error'); }
            },
            error: handleAjaxError
        });
    });
    $('#cancelEditDataBtn').on('click', function(e) {
        e.preventDefault(); $('#editDataForm')[0].reset(); $('#editDataModal').modal('hide');
    });

    // ── BULK DELETE ───────────────────────────────────────────────────────
    $('#deleteSelectedBtn').on('click', function(e) {
        e.preventDefault();
        var selected=[], selectedRows=[];
        $('.selectRow:checked').each(function() {
            selected.push($(this).val()); selectedRows.push($(this).data('row-id'));
        });
        if (!selected.length) { toastr.warning('No products selected.','Warning'); return; }
        var c=selected.length;
        $('#multipleDisplayDeleteLabel').html('the selected <strong>'+c+' product'+(c>1?'s':'')+'</strong>');
        $('#multipleDeleteIds').val(selected.join(','));
        $('#multipleDeleteRows').val(selectedRows.join(','));
        $('#bulkActionsModal').modal('hide');
        setTimeout(function() { $('#multipleDeleteDataModal').modal('show'); }, 300);
    });
    $('#keepMultipleDataBtn').on('click', function(e) {
        e.preventDefault(); toastr.info('Your data is safe','Great!'); $('#multipleDeleteDataModal').modal('hide');
    });
    $('#submitMultipleDeleteDataBtn').on('click', function(e) {
        e.preventDefault();
        var self=$(this); self.prop('disabled',true);
        var ids=$('#multipleDeleteIds').val().split(',');
        var rows=$('#multipleDeleteRows').val().split(',');
        $.ajaxSetup({ headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:'POST', url:'{{ route("retail.operations.baseproducts.bulkdelete") }}',
            data:{ids:ids,_token:'{{ csrf_token() }}'}, timeout:60000,
            beforeSend:function() { $('#progressBar').show(); },
            complete:  function() { $('#progressBar').hide(); self.prop('disabled',false); },
            success: function(data) {
                if (data.status===201) {
                    toastr.success(data.success,'Success');
                    for (var i=0;i<rows.length;i++) { table.row('#'+rows[i]).remove(); }
                    table.draw(false); updateSelectedCount();
                    $('#multipleDeleteDataModal').modal('hide');
                } else if (data.status===422) {
                    toastr.error(data.error||'Validation failed.','Error');
                } else { toastr.info('Unspecified error.','Error'); }
            },
            error: handleAjaxError
        });
    });

    // ── BULK TYPE ─────────────────────────────────────────────────────────
    function doBulkStatus(isProduct) {
        var selected=[]; $('.selectRow:checked').each(function() { selected.push($(this).val()); });
        if (!selected.length) return;
        $.ajaxSetup({ headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:'POST', url:'{{ route("retail.operations.baseproducts.bulkstatus") }}',
            data:{ids:selected, is_product:isProduct, _token:'{{ csrf_token() }}'}, timeout:60000,
            beforeSend:function() { $('#progressBar').show(); },
            complete:  function() { $('#progressBar').hide(); },
            success: function(data) {
                if (data.status===201) {
                    toastr.success(data.success,'Success');
                    $.each(data.products,function(i,p) { table.row('#'+p.row).remove(); table.row.add($(buildRow(p))); });
                    table.draw(false); updateSelectedCount(); $('#bulkActionsModal').modal('hide');
                } else { toastr.error(data.error||'Failed.','Error'); }
            },
            error: handleAjaxError
        });
    }
    $('#bulkMarkProductBtn').on('click', function(e) { e.preventDefault(); doBulkStatus(1); });
    $('#bulkMarkServiceBtn').on('click', function(e) { e.preventDefault(); doBulkStatus(0); });

    // ── BULK SUPPLIER ─────────────────────────────────────────────────────
    $('#applyBulkSupplierBtn').on('click', function(e) {
        e.preventDefault();
        var supplier = $('#bulkSupplierSelect').val();
        if (!supplier) { toastr.warning('Select a supplier.','Required'); return; }
        var selected=[]; $('.selectRow:checked').each(function() { selected.push($(this).val()); });
        if (!selected.length) return;
        $.ajaxSetup({ headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:'POST', url:'{{ route("retail.operations.baseproducts.bulksupplier") }}',
            data:{ids:selected, supplier:supplier, _token:'{{ csrf_token() }}'}, timeout:60000,
            beforeSend:function() { $('#progressBar').show(); },
            complete:  function() { $('#progressBar').hide(); },
            success: function(data) {
                if (data.status===201) {
                    toastr.success(data.success,'Success');
                    $.each(data.products,function(i,p) { table.row('#'+p.row).remove(); table.row.add($(buildRow(p))); });
                    table.draw(false); updateSelectedCount(); $('#bulkActionsModal').modal('hide');
                } else { toastr.error(data.error||'Failed.','Error'); }
            },
            error: handleAjaxError
        });
    });

    // ── SELECT ALL ────────────────────────────────────────────────────────
    $('#selectAll').on('click', function() {
        $('.selectRow').prop('checked', this.checked); updateSelectedCount();
    });
    $('#tbody').on('click', '.selectRow', function() { updateSelectedCount(); });

});
</script>
@endsection