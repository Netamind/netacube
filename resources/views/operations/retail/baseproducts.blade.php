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
  background: #eef0f7;
  border-bottom: 1px solid #d6daf0;
  padding: 9px 1.5rem;
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
.card-filter label { font-size:12px; font-weight:600; color:#4B5EBD; margin-bottom:0; white-space:nowrap; }
.card-filter select {
  font-size:12px; height:30px; padding:0 8px; border-radius:6px;
  border:1px solid #c8d0ed; background:#fff; min-width:160px; max-width:220px;
}
.filter-divider { width:1px; height:22px; background:#c8d0ed; margin:0 4px; }
.filter-badge { font-size:11px; background:#4B5EBD; color:#fff; border-radius:10px; padding:1px 8px; }

/* ── Bulk trigger — in filter bar, pushed to far right ──────────────────── */
#bulkTriggerBtn {
  font-size:12px; font-weight:700;
  margin-left:auto;
  height:28px; padding:0 12px;
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
.view-section-title {
  font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:1px;
  color:#4B5EBD; border-bottom:2px solid #e8ecff; padding-bottom:5px; margin-bottom:10px;
}

/* ── Import modal ───────────────────────────────────────────────────────── */
.excel-preview-wrap {
  border:2px solid #b7d5c4; border-radius:8px; overflow:hidden;
  box-shadow:0 2px 8px rgba(0,0,0,0.07);
}
.excel-header-bar {
  background:#217346; color:#fff; font-size:11px; font-weight:700;
  padding:5px 10px; letter-spacing:0.5px; display:flex; align-items:center; gap:6px;
}
.excel-preview-table { width:100%; border-collapse:collapse; font-size:12px; }
.excel-row-num { background:#f0f0f0; color:#888; font-size:10px; font-weight:600;
  text-align:center; padding:4px 6px; border-right:1px solid #d0d0d0;
  border-bottom:1px solid #d0d0d0; min-width:28px; }
.excel-preview-table thead th {
  background:#217346; color:#fff; text-align:center; padding:6px 10px;
  border-right:1px solid #1a5c38; font-weight:600; font-size:11px; white-space:nowrap;
}
.excel-preview-table thead th.col-name { text-align:left; }
.excel-preview-table tbody td {
  text-align:center; padding:5px 10px; border-bottom:1px solid #e8e8e8;
  border-right:1px solid #e8e8e8; background:#fff;
}
.excel-preview-table tbody td.col-name { text-align:left; }
.excel-preview-table tbody tr:nth-child(even) td { background:#f8fff8; }
.excel-preview-table tbody tr:hover td { background:#e8f5e9; }
.excel-col-code { font-family:monospace; background:#f1f8e9; padding:1px 5px;
  border-radius:3px; color:#2d6a4f; font-size:11px; }
.excel-req-star { color:#e74c3c; margin-left:2px; font-size:13px; }
.excel-sample-val { color:#555; }
.excel-sample-muted { color:#aaa; font-style:italic; font-size:11px; }

.drop-zone {
  border:2px dashed #40916c; border-radius:12px; padding:26px 20px;
  text-align:center; cursor:pointer; transition:all .2s;
  background:#f0faf5; position:relative;
}
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

/* ── Import gate banner ───────────────────────────────────────────────────*/
.import-gate-banner {
  background:#fff3cd; border:1px solid #ffc107; border-radius:8px;
  padding:10px 14px; font-size:12px; color:#856404;
  display:flex; align-items:flex-start; gap:8px; margin-bottom:14px;
}
.import-gate-banner i { font-size:16px; flex-shrink:0; margin-top:1px; }
.import-ready-banner {
  background:#d1e7dd; border:1px solid #a3cfbb; border-radius:8px;
  padding:10px 14px; font-size:12px; color:#0a3622;
  display:flex; align-items:center; gap:8px; margin-bottom:14px;
}
.import-ready-banner i { font-size:16px; flex-shrink:0; }

/* ── CSV column guide card ────────────────────────────────────────────────*/
.csv-guide-card {
  background:#f8f9ff; border:1px solid #d6daf0; border-radius:8px;
  padding:12px 14px; margin-bottom:14px;
}
.csv-guide-card .csv-guide-title {
  font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.7px;
  color:#4B5EBD; margin-bottom:8px; display:flex; align-items:center; gap:5px;
}

/* ── Bulk actions modal ─────────────────────────────────────────────────── */
.bulk-section { background:#f8f9fa; border-radius:8px; padding:12px 14px; margin-bottom:12px; }
.bulk-section-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#6c757d; margin-bottom:10px; }

/* ── Spinner ────────────────────────────────────────────────────────────── */
@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }

/* ── Section label ──────────────────────────────────────────────────────── */
.section-label {
  font-size:11px; font-weight:700; text-transform:uppercase;
  letter-spacing:0.8px; color:#6c757d;
  border-bottom:1px solid #e9ecef;
  padding-bottom:4px; margin-bottom:12px; margin-top:8px;
}

/* ── Branch select in filter bar ────────────────────────────────────────── */
#filterBranch { font-size:12px; height:30px; padding:0 8px; border-radius:6px;
  border:1px solid #c8d0ed; background:#fff; min-width:160px; max-width:220px; }
</style>

<div class="progress" id="progressBar" role="progressbar"
     style="height:8px;transform:rotate(180deg);display:none">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<?php
  $maintableTitle = "Retail Base Products";
  $categories = DB::connection('tenant')->table('categories')->orderBy('category')->get();
  $branches   = DB::connection('tenant')->table('branches')->orderBy('name')->get();

  // Fetch suppliers with their associated categories for filtering
  $supplierRows = DB::connection('tenant')->table('retail_base_products')
                    ->whereNotNull('supplier')->where('supplier','!=','')
                    ->select('supplier', 'category')
                    ->distinct()
                    ->orderBy('supplier')
                    ->get();

  // Group: supplier => array of categories
  $supplierCategoryMap = [];
  foreach ($supplierRows as $row) {
      if (!isset($supplierCategoryMap[$row->supplier])) {
          $supplierCategoryMap[$row->supplier] = [];
      }
      if ($row->category && !in_array($row->category, $supplierCategoryMap[$row->supplier])) {
          $supplierCategoryMap[$row->supplier][] = $row->category;
      }
  }
  ksort($supplierCategoryMap);

  // Plain list of unique suppliers for other dropdowns
  $suppliers = array_keys($supplierCategoryMap);

  $products = DB::connection('tenant')->table('retail_base_products')
                ->select('retail_base_products.*')
                ->get();

  // Read saved filters from user_filters
  $pref            = DB::connection('tenant')->table('user_filters')->where('user_id', Auth::id())->first();
  $savedBranchId   = $pref->branch_id   ?? null;
  $savedCategoryId = null;
  $savedCategoryName = null;
  if ($pref && $pref->category_id) {
      $savedCat = DB::connection('tenant')->table('categories')->find($pref->category_id);
      $savedCategoryId   = $savedCat->id       ?? null;
      $savedCategoryName = $savedCat->category ?? null;
  }
  $savedBranch = $savedBranchId ? DB::connection('tenant')->table('branches')->find($savedBranchId) : null;
?>

<div class="card">

{{-- ── Card header ──────────────────────────────────────────────────────── --}}
<div class="card-header d-flex justify-content-between align-items-center">
  <h4 class="header-title mb-0">
     Baseproducts
  </h4>
  <div class="d-flex align-items-center" style="gap:4px;">
    <a href="#" class="btn btn-light text-success fs-16 mx-1" id="importBtn"       title="Import from CSV"><i class="ri-file-excel-2-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="newDataBtn"      title="Add new product"><i class="ri-add-circle-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn"         title="Info"><i class="ri-information-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download"><i class="ri-download-line"></i></a>
  </div>
</div>

{{-- ── Filter bar ───────────────────────────────────────────────────────── --}}
<div class="card-filter">

  {{-- Category selector — saves via user_filters --}}
  <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
        id="filterCategoryForm" style="display:contents;">
    @csrf
    <input type="hidden" name="user_id" value="{{ Auth::id() }}">
    <label>Category:</label>
    <select name="category_id" id="filterCategory"
            onchange="document.getElementById('filterCategoryForm').submit()">
      <option value="">All Categories</option>
      @foreach($categories as $cat)
        <option value="{{ $cat->id }}" {{ $savedCategoryId == $cat->id ? 'selected' : '' }}>
          {{ $cat->category }}
        </option>
      @endforeach
    </select>
  </form>

  <div class="filter-divider"></div>

  <label>Supplier:</label>
  <select id="filterSupplier">
    <option value="">All Suppliers</option>
    @foreach($suppliers as $sup)
      <option value="{{ $sup }}">{{ $sup }}</option>
    @endforeach
  </select>

  <span id="filterInfo" class="ms-2" style="font-size:12px;color:#6c757d;display:none">
    Showing <span id="filterInfoCount" class="filter-badge">0</span> products
  </span>

  {{-- Bulk trigger — far right of filter bar --}}
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
      <tr id="{{ $row }}" data-category="{{ $product->category }}" data-supplier="{{ $product->supplier }}">
        <td>
          <input type="checkbox" class="selectRow" value="{{ $product->id }}" data-row-id="{{ $row }}">
          &nbsp;{{ $product->name }}
        </td>
        <td>{{ $product->internal_code ?? '—' }}</td>
        <td>{{ $product->unit_of_measure }}</td>
        <td>
          @if($product->default_cost_price !== null)
            <span style="font-size:12px;color:#6c757d">{{ number_format($product->default_cost_price,2) }}</span>
          @else<span class="text-muted" style="font-size:12px">—</span>
          @endif
        </td>
        <td>
          @if($product->default_selling_price !== null)
            <span class="price-cell">{{ number_format($product->default_selling_price,2) }}</span>
          @else<span class="text-muted" style="font-size:12px">—</span>
          @endif
        </td>
        <td>
          <a href="#" class="viewDataBtn"
             data-id="{{ $product->id }}"
             data-name="{{ $product->name }}"
             data-description="{{ $product->description }}"
             data-brand="{{ $product->brand }}"
             data-supplier="{{ $product->supplier }}"
             data-manufacturer="{{ $product->manufacturer }}"
             data-origin="{{ $product->country_of_origin }}"
             data-code="{{ $product->internal_code }}"
             data-unit="{{ $product->unit_of_measure }}"
             data-weight="{{ $product->weight_kg }}"
             data-volume="{{ $product->volume_litres }}"
             data-sell="{{ $product->default_selling_price }}"
             data-cost="{{ $product->default_cost_price }}"
             data-cat="{{ $product->category ?? '' }}"
             data-active="{{ $product->is_active }}">
            <i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px"></i>
          </a>
          <a href="#" class="editDataBtn"
             editId="{{ $product->id }}"
             editRow="{{ $row }}"
             editName="{{ $product->name }}"
             editDescription="{{ $product->description }}"
             editBrand="{{ $product->brand }}"
             editSupplier="{{ $product->supplier }}"
             editManufacturer="{{ $product->manufacturer }}"
             editCountryOfOrigin="{{ $product->country_of_origin }}"
             editInternalCode="{{ $product->internal_code }}"
             editUnitOfMeasure="{{ $product->unit_of_measure }}"
             editWeightKg="{{ $product->weight_kg }}"
             editVolumeLitres="{{ $product->volume_litres }}"
             editDefaultSellingPrice="{{ $product->default_selling_price }}"
             editDefaultCostPrice="{{ $product->default_cost_price }}"
             editCategory="{{ $product->category }}"
             editIsActive="{{ $product->is_active }}">
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
      Base products are your <strong>master catalogue</strong> — the permanent list of everything you sell. Each product is defined once here and can then be assigned to one or more branches.</p>
      <p class="mb-2"><strong>Default Prices</strong><br>
      The selling price and order price set here apply to all branches by default. Individual branches can override these with their own prices if needed.</p>
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
              <div class="view-item"><label>Internal Code</label><div class="view-val" id="vw-internal-code"></div></div>
              <div class="view-item"><label>Unit of Measure</label><div class="view-val" id="vw-unit"></div></div>
              <div class="view-item"><label>Brand</label><div class="view-val" id="vw-brand"></div></div>
              <div class="view-item"><label>Manufacturer</label><div class="view-val" id="vw-manufacturer"></div></div>
              <div class="view-item"><label>Supplier</label><div class="view-val" id="vw-supplier"></div></div>
              <div class="view-item"><label>Country of Origin</label><div class="view-val" id="vw-origin"></div></div>
              <div class="view-item"><label>Category</label><div class="view-val" id="vw-category"></div></div>
              <div class="view-item full"><label>Description</label><div class="view-val" id="vw-description"></div></div>
            </div>
          </div>
          <div class="tab-pane fade" id="vw-t2">
            <div class="view-grid">
              <div class="view-item"><label>Default Selling Price (MWK)</label><div class="view-val price-cell" id="vw-sell"></div></div>
              <div class="view-item"><label>Default Order / Cost Price (MWK)</label><div class="view-val" id="vw-cost"></div></div>
              <div class="view-item"><label>Weight (kg)</label><div class="view-val" id="vw-weight"></div></div>
              <div class="view-item"><label>Volume (litres)</label><div class="view-val" id="vw-volume"></div></div>
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
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal" id="newDataModalCloseBtn"></button>
      </div>
      <div id="newProductContextBanner" class="d-none" style="background:#f0faf5;border-bottom:1px solid #b7d5c4;padding:7px 20px;display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        <span style="font-size:11px;font-weight:600;color:#2d6a4f;"><i class="ri-information-line me-1"></i>Category from filter:</span>
        <span id="newCtxCatBadge" style="display:none;background:#e8f5e9;border:1px solid #a5d6a7;color:#2d6a4f;border-radius:6px;padding:2px 8px;font-size:12px;font-weight:600;"><i class="ri-folder-line me-1"></i><span></span></span>
      </div>
      <div class="modal-body" style="padding:16px 20px 8px !important;">
        <form action="#" method="post" id="newDataForm">
          @csrf
          <input type="hidden" name="category" id="new-category">
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Product / Service Name <span class="text-danger">*</span></label>
            <input class="form-control" type="text" name="name" id="new-name"
                   placeholder="e.g. Cooking Oil 2L" autocomplete="off" required />
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold" style="font-size:13px">Selling Price <small class="text-muted fw-normal">(MWK)</small></label>
              <input class="form-control" type="number" step="0.01" min="0"
                     name="default_selling_price" id="new-selling-price" placeholder="0.00" />
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold" style="font-size:13px">Order / Cost Price <small class="text-muted fw-normal">(MWK)</small></label>
              <input class="form-control" type="number" step="0.01" min="0"
                     name="default_cost_price" id="new-cost-price" placeholder="0.00" />
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Unit of Measure</label>
            <input class="form-control" type="text" name="unit_of_measure" id="new-unit-of-measure"
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
            <label class="form-label fw-semibold" style="font-size:13px">Internal Code <small class="text-muted fw-normal">(optional)</small></label>
            <input class="form-control" type="text" name="internal_code" id="new-internal-code"
                   placeholder="e.g. OIL-001" autocomplete="off" />
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Supplier</label>
            <select class="form-select" name="supplier" id="new-supplier">
              <option value="">— Select Supplier —</option>
              @foreach($supplierCategoryMap as $sup => $cats)
                <option value="{{ $sup }}" data-categories="{{ implode(',', $cats) }}">
                  {{ $sup }}
                </option>
              @endforeach
            </select>
            <div id="new-supplier-hint" class="form-text" style="display:none;color:#2d6a4f;">
              <i class="ri-filter-line"></i> Showing suppliers linked to the selected category.
            </div>
          </div>
          <div class="form-check mb-1">
            <input class="form-check-input" type="checkbox" name="is_active_check" id="new-is-active" checked>
            <label class="form-check-label" for="new-is-active">Active (visible to branches)</label>
          </div>
          <input type="hidden" name="is_active" id="new-is-active-hidden" value="1">
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
            <span class="ms-2 text-muted" style="font-size:12px">5 columns</span>
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
                  <th>internal_code</th>
                  <th>unit_of_measure</th>
                  <th>default_cost_price</th>
                  <th>default_selling_price</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="excel-row-num" style="background:#e8f5e9;color:#2d6a4f;font-weight:600;">1</td>
                  <td class="col-name" style="color:#2d6a4f;font-weight:600;">← header row</td>
                  <td style="color:#2d6a4f;">headers</td>
                  <td style="color:#2d6a4f;">headers</td>
                  <td style="color:#2d6a4f;">headers</td>
                  <td style="color:#2d6a4f;">headers</td>
                </tr>
                <tr>
                  <td class="excel-row-num">2</td>
                  <td class="col-name excel-sample-val">Cooking Oil 2L</td>
                  <td><span class="excel-col-code">OIL-001</span></td>
                  <td>Each</td><td>1,500.00</td><td>2,000.00</td>
                </tr>
                <tr>
                  <td class="excel-row-num">3</td>
                  <td class="col-name excel-sample-val">Drinking Water 500ml</td>
                  <td><span class="excel-col-code">WAT-001</span></td>
                  <td>Each</td><td>350.00</td><td>500.00</td>
                </tr>
                <tr style="background:#fafafa;">
                  <td class="excel-row-num" style="color:#ccc;">4</td>
                  <td class="col-name excel-sample-muted">your product here…</td>
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
          Status = Active. Category &amp; Supplier are taken from the page filters.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        <a href="#" id="downloadTemplateBtnFooter" class="btn btn-success btn-sm"><i class="ri-download-2-line me-1"></i> Download Template</a>
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

        {{-- Gate / Ready banner --}}
        <div id="importGateBanner" class="import-gate-banner" style="display:none;">
          <i class="ri-alert-line"></i>
          <div>
            <strong>Import disabled.</strong> Please select both a <strong>category</strong> and a
            <strong>supplier</strong> using the filters at the top of the page before importing.
            All imported products will be assigned to those selections.
          </div>
        </div>
        <div id="importReadyBanner" class="import-ready-banner" style="display:none;">
          <i class="ri-checkbox-circle-line text-success"></i>
          <div>
            Importing into category <strong id="importReadyCat"></strong>
            with supplier <strong id="importReadySup"></strong>.
            <span class="text-muted ms-1" style="font-size:11px;">Close modal to change filters.</span>
          </div>
        </div>

        <div id="importSetupFields">
          <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <span style="font-size:12px;font-weight:600;color:#1e293b;">
                <i class="ri-table-line me-1 text-success"></i>Prepare a CSV file that looks like this:
              </span>
              <a href="#" id="viewSampleBtn" class="btn btn-sm btn-outline-success" style="font-size:12px;white-space:nowrap;">
                <i class="ri-table-line me-1"></i> View Full Guide
              </a>
            </div>

            {{-- Inline sample table --}}
            <div class="excel-preview-wrap mb-3">
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
                      <th>internal_code</th>
                      <th>unit_of_measure</th>
                      <th>default_cost_price</th>
                      <th>default_selling_price</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td class="excel-row-num" style="background:#e8f5e9;color:#2d6a4f;font-weight:600;">1</td>
                      <td class="col-name" style="color:#2d6a4f;font-weight:600;">← header row</td>
                      <td style="color:#2d6a4f;">headers</td>
                      <td style="color:#2d6a4f;">headers</td>
                      <td style="color:#2d6a4f;">headers</td>
                      <td style="color:#2d6a4f;">headers</td>
                    </tr>
                    <tr>
                      <td class="excel-row-num">2</td>
                      <td class="col-name excel-sample-val">Cooking Oil 2L</td>
                      <td><span class="excel-col-code">OIL-001</span></td>
                      <td>Each</td><td>1500.00</td><td>2000.00</td>
                    </tr>
                    <tr>
                      <td class="excel-row-num">3</td>
                      <td class="col-name excel-sample-val">Drinking Water 500ml</td>
                      <td><span class="excel-col-code">WAT-001</span></td>
                      <td>Each</td><td>350.00</td><td>500.00</td>
                    </tr>
                    <tr style="background:#fafafa;">
                      <td class="excel-row-num" style="color:#ccc;">4</td>
                      <td class="col-name excel-sample-muted">your product here…</td>
                      <td class="excel-sample-muted">optional</td>
                      <td class="excel-sample-muted">Each / kg / Litre…</td>
                      <td class="excel-sample-muted">numeric, no commas</td>
                      <td class="excel-sample-muted">numeric, no commas</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <div class="d-flex justify-content-end mb-3">
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
            <div class="table-responsive" style="max-height:200px;overflow-y:auto;border:1px solid #dee2e6;border-radius:6px;">
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
          <a href="#" class="btn btn-secondary btn-sm" id="cancelImportBtn" style="font-size:12px"><i class="ri-close-line"></i> Cancel</a>
          <a href="#" class="btn btn-success btn-sm" id="submitImportBtn" style="font-size:12px"><i class="ri-upload-2-line"></i> Start Import</a>
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
          <input type="hidden" id="singleDeleteId" name="id">
          <input type="hidden" id="singleDeleteRow">
          <a href="#" class="btn btn-danger me-2 mt-3"  id="submitSingleDeleteDataBtn">Yes, Delete it</a>
          <a href="#" class="btn btn-info mt-3"          id="keepSingleDataBtn">No, Keep it</a>
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
          <a href="#" class="btn btn-danger me-2 mt-3"  id="submitMultipleDeleteDataBtn">Yes, Delete them</a>
          <a href="#" class="btn btn-info mt-3"          id="keepMultipleDataBtn">No, Keep them</a>
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
          Bulk Actions — <span id="bulkActionsCount">0</span> product(s) selected
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:16px 18px !important;">
        <div class="bulk-section">
          <div class="bulk-section-title"><i class="ri-truck-line me-1"></i> Change Supplier</div>
          <div class="d-flex gap-2 align-items-center mb-2">
            <select class="form-select form-select-sm" id="bulkSupplierSelect">
              <option value="">— Select Supplier —</option>
              @foreach($suppliers as $sup)
                <option value="{{ $sup }}">{{ $sup }}</option>
              @endforeach
            </select>
            <a href="#" class="btn btn-sm btn-warning text-dark" id="applyBulkSupplierBtn" style="white-space:nowrap">
              <i class="ri-check-line me-1"></i> Apply
            </a>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="bulkClearSupplierCheck">
            <label class="form-check-label text-danger" style="font-size:12px" for="bulkClearSupplierCheck">
              Clear supplier field instead (set to blank)
            </label>
          </div>
        </div>
        <div class="bulk-section">
          <div class="bulk-section-title"><i class="ri-toggle-line me-1"></i> Status</div>
          <div class="d-flex gap-2">
            <a href="#" class="btn btn-sm btn-info text-white flex-fill" id="bulkActivateBtn">
              <i class="ri-checkbox-circle-line me-1"></i> Activate All
            </a>
            <a href="#" class="btn btn-sm btn-secondary text-white flex-fill" id="bulkDeactivateBtn">
              <i class="ri-close-circle-line me-1"></i> Deactivate All
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
     EDIT MODAL — 3 tabs
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-edit-box-line"></i> Update Base Product</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <ul class="nav nav-tabs border-bottom px-2 pt-2" id="editModalTabs" role="tablist" style="font-size:12px;flex-wrap:nowrap;">
        <li class="nav-item"><button class="nav-link active px-2 py-1" data-bs-toggle="tab" data-bs-target="#etab1" type="button"><i class="ri-price-tag-3-line me-1"></i>Core Info</button></li>
        <li class="nav-item"><button class="nav-link px-2 py-1"        data-bs-toggle="tab" data-bs-target="#etab2" type="button"><i class="ri-file-list-line me-1"></i>More Details</button></li>
        <li class="nav-item"><button class="nav-link px-2 py-1"        data-bs-toggle="tab" data-bs-target="#etab3" type="button"><i class="ri-folder-line me-1"></i>Category</button></li>
      </ul>
      <div class="modal-body" style="padding:14px 18px 8px !important;">
        <form action="#" method="post" id="editDataForm">
          @csrf
          <input type="hidden" name="id"      id="editId">
          <input type="hidden" name="editrow" id="editRow">
          <div class="tab-content">
            <div class="tab-pane fade show active" id="etab1" role="tabpanel">
              <div class="mb-2">
                <label class="form-label fw-semibold" style="font-size:13px">Product Name <span class="text-danger">*</span></label>
                <input class="form-control form-control-sm" type="text" name="name" id="editName" autocomplete="off" required />
              </div>
              <div class="row g-2 mb-2">
                <div class="col-6">
                  <label class="form-label fw-semibold" style="font-size:13px">Selling Price <small class="text-muted fw-normal">(MWK)</small></label>
                  <input class="form-control form-control-sm" type="number" step="0.01" min="0" name="default_selling_price" id="editSellingPrice" placeholder="0.00" />
                </div>
                <div class="col-6">
                  <label class="form-label fw-semibold" style="font-size:13px">Order / Cost Price <small class="text-muted fw-normal">(MWK)</small></label>
                  <input class="form-control form-control-sm" type="number" step="0.01" min="0" name="default_cost_price" id="editCostPrice" placeholder="0.00" />
                </div>
              </div>
              <div class="mb-2">
                <label class="form-label fw-semibold" style="font-size:13px">Unit of Measure <span class="text-danger">*</span></label>
                <select class="form-select form-select-sm" name="unit_of_measure" id="editUnitOfMeasure" required>
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
                <label class="form-label fw-semibold" style="font-size:13px">Internal Code</label>
                <input class="form-control form-control-sm" type="text" name="internal_code" id="editInternalCode" autocomplete="off" />
                <div class="form-text">Your unique SKU / import match key</div>
              </div>
            </div>
            <div class="tab-pane fade" id="etab2" role="tabpanel">
              <div class="mb-2">
                <label class="form-label fw-semibold" style="font-size:13px">Description</label>
                <textarea class="form-control form-control-sm" name="description" id="editDescription" rows="2"></textarea>
              </div>
              <div class="row g-2 mb-2">
                <div class="col-6">
                  <label class="form-label fw-semibold" style="font-size:13px">Brand</label>
                  <input class="form-control form-control-sm" type="text" name="brand" id="editBrand" autocomplete="off" />
                </div>
                <div class="col-6">
                  <label class="form-label fw-semibold" style="font-size:13px">Manufacturer</label>
                  <input class="form-control form-control-sm" type="text" name="manufacturer" id="editManufacturer" autocomplete="off" />
                </div>
              </div>
              <div class="row g-2 mb-2">
                <div class="col-6">
                  <label class="form-label fw-semibold" style="font-size:13px">Supplier</label>
                  <select class="form-select form-select-sm" name="supplier" id="editSupplier">
                    <option value="">— Select Supplier —</option>
                    @foreach($suppliers as $sup)
                      <option value="{{ $sup }}">{{ $sup }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-6">
                  <label class="form-label fw-semibold" style="font-size:13px">Country of Origin</label>
                  <input class="form-control form-control-sm" type="text" name="country_of_origin" id="editCountryOfOrigin" maxlength="2" autocomplete="off" placeholder="e.g. MW" />
                </div>
              </div>
              <div class="row g-2">
                <div class="col-6">
                  <label class="form-label fw-semibold" style="font-size:13px">Weight (kg)</label>
                  <input class="form-control form-control-sm" type="number" step="0.0001" min="0" name="weight_kg" id="editWeightKg" />
                </div>
                <div class="col-6">
                  <label class="form-label fw-semibold" style="font-size:13px">Volume (litres)</label>
                  <input class="form-control form-control-sm" type="number" step="0.0001" min="0" name="volume_litres" id="editVolumeLitres" />
                </div>
              </div>
            </div>
            <div class="tab-pane fade" id="etab3" role="tabpanel">
              <div class="mb-3">
                <label class="form-label fw-semibold" style="font-size:13px">Category</label>
                <select class="form-select form-select-sm" name="category" id="editCategory">
                  <option value="">— Select Category —</option>
                  @foreach($categories as $cat)
                    <option value="{{ $cat->category }}">{{ $cat->category }}</option>
                  @endforeach
                </select>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive" value="1">
                <label class="form-check-label" for="editIsActive">Active (visible to branches)</label>
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
        if (status === 'timeout') { toastr.error('The request timed out.', 'Timeout Error'); }
        else if (xhr.status === 0) { toastr.error('Unable to connect.', 'Connection Error'); }
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

    function buildRow(p) {
        var spCell = (p.default_selling_price !== null && p.default_selling_price !== '')
            ? '<span class="price-cell">' + fmtPrice(p.default_selling_price) + '</span>'
            : '<span class="text-muted" style="font-size:12px">—</span>';
        var cpCell = (p.default_cost_price !== null && p.default_cost_price !== '')
            ? '<span style="font-size:12px;color:#6c757d">' + fmtPrice(p.default_cost_price) + '</span>'
            : '<span class="text-muted" style="font-size:12px">—</span>';
        var d = function(v){ return (v || '').toString().replace(/"/g, '&quot;'); };

        return `<tr id="${p.row}" data-category="${d(p.category)}" data-supplier="${d(p.supplier)}">
            <td>
                <input type="checkbox" class="selectRow" value="${p.id}" data-row-id="${p.row}">
                &nbsp;${p.name}
            </td>
            <td>${p.internal_code || '—'}</td>
            <td>${p.unit_of_measure}</td>
            <td>${cpCell}</td>
            <td>${spCell}</td>
            <td>
                <a href="#" class="viewDataBtn"
                   data-id="${p.id}" data-name="${d(p.name)}" data-description="${d(p.description)}"
                   data-brand="${d(p.brand)}" data-supplier="${d(p.supplier)}" data-manufacturer="${d(p.manufacturer)}"
                   data-origin="${d(p.country_of_origin)}" data-code="${d(p.internal_code)}"
                   data-unit="${d(p.unit_of_measure)}"
                   data-weight="${p.weight_kg !== null ? p.weight_kg : ''}"
                   data-volume="${p.volume_litres !== null ? p.volume_litres : ''}"
                   data-sell="${p.default_selling_price !== null ? p.default_selling_price : ''}"
                   data-cost="${p.default_cost_price !== null ? p.default_cost_price : ''}"
                   data-cat="${d(p.category)}"
                   data-active="${p.is_active}">
                   <i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px"></i>
                </a>
                <a href="#" class="editDataBtn"
                   editId="${p.id}" editRow="${p.row}" editName="${d(p.name)}"
                   editDescription="${d(p.description)}" editBrand="${d(p.brand)}"
                   editSupplier="${d(p.supplier)}" editManufacturer="${d(p.manufacturer)}"
                   editCountryOfOrigin="${d(p.country_of_origin)}" editInternalCode="${d(p.internal_code)}"
                   editUnitOfMeasure="${d(p.unit_of_measure)}"
                   editWeightKg="${p.weight_kg !== null ? p.weight_kg : ''}"
                   editVolumeLitres="${p.volume_litres !== null ? p.volume_litres : ''}"
                   editDefaultSellingPrice="${p.default_selling_price !== null ? p.default_selling_price : ''}"
                   editDefaultCostPrice="${p.default_cost_price !== null ? p.default_cost_price : ''}"
                   editCategory="${d(p.category)}"
                   editIsActive="${p.is_active}">
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
        else $('#bulkTriggerBtn').removeClass('visible');
    }

    function collectNewFormData() {
        var fd = $('#newDataForm').serializeArray().filter(function(i) {
            return i.name !== 'is_active_check';
        });
        $('#new-is-active-hidden').val($('#new-is-active').prop('checked') ? 1 : 0);
        return fd;
    }
    function collectEditFormData() {
        var fd = $('#editDataForm').serializeArray().filter(function(i) {
            return i.name !== 'is_active';
        });
        fd.push({ name: 'is_active', value: $('#editIsActive').prop('checked') ? 1 : 0 });
        return fd;
    }

    // ════════════════════════════════════════════════════════════════════════
    //  CLIENT-SIDE SUPPLIER FILTER
    // ════════════════════════════════════════════════════════════════════════
    function applySupplierFilter() {
        var supVal = $('#filterSupplier').val();
        var visible = 0;
        $('#tbody tr').each(function () {
            var rowSup = $(this).data('supplier') ? String($(this).data('supplier')) : '';
            var show = !supVal || rowSup === supVal;
            if (show) { $(this).show(); visible++; } else { $(this).hide(); }
        });
        if (supVal) {
            $('#filterInfo').show(); $('#filterInfoCount').text(visible);
        } else {
            $('#filterInfo').hide();
        }
        if (window._dt) window._dt.draw(false);
    }
    $('#filterSupplier').on('change', applySupplierFilter);
    $('#clearFilterBtn').on('click', function(e) {
        e.preventDefault();
        $('#filterSupplier').val('');
        applySupplierFilter();
        window.location.href = window.location.pathname + '?clear_filters=1';
    });

    // ════════════════════════════════════════════════════════════════════════
    //  IMPORT ENGINE
    // ════════════════════════════════════════════════════════════════════════
    var parsedCsvRows = [];
    var IMPORT_KEY    = 'bp_import_queue';

    function resetImportModal() {
        parsedCsvRows = [];
        $('#csvFileInput').val(''); $('#csvFileName').hide();
        $('#importStepSetup').show(); $('#importStepPreview').hide();
        $('#importStepProgress').hide(); $('#importStepDone').hide();
        $('#importPreviewHead, #importPreviewBody, #importLog').empty();
        $('#importBarFill').css('width','0');
        $('#importSetupFields').show();
        $('#submitImportBtn').prop('disabled', false);
        $('#submitImportBtn').html('<i class="ri-upload-2-line"></i> Start Import');
        $('#cancelImportBtn').prop('disabled', false).html('<i class="ri-close-line"></i> Cancel');
    }

    // Checks whether category AND supplier are both selected, then shows
    // the appropriate banner and enables/disables the submit button.
    function refreshImportCtxDisplay() {
        var catText = @json($savedCategoryName ?? '');
        var supVal  = $('#filterSupplier').val();
        var ready   = catText && supVal;

        if (ready) {
            $('#importGateBanner').hide();
            $('#importReadyCat').text(catText);
            $('#importReadySup').text(supVal);
            $('#importReadyBanner').show();
            $('#submitImportBtn').prop('disabled', false);
        } else {
            $('#importReadyBanner').hide();
            $('#importGateBanner').show();
            $('#submitImportBtn').prop('disabled', true);
        }
    }

    $('#importBtn').on('click', function(e) {
        e.preventDefault();
        resetImportModal();
        refreshImportCtxDisplay();
        $('#importModal').modal('show');
    });
    $('#cancelImportBtn').on('click', function(e) {
        e.preventDefault();
        localStorage.removeItem(IMPORT_KEY);
        resetImportModal();
        $('#importModal').modal('hide');
    });
    $('#importModal').on('hidden.bs.modal', function() {
        localStorage.removeItem(IMPORT_KEY);
        resetImportModal();
    });
    $('#importModalCloseBtn').on('click', function() {
        localStorage.removeItem(IMPORT_KEY);
        resetImportModal();
    });

    $('#viewSampleBtn').on('click', function(e) { 

        e.preventDefault();
        $('#importModal').modal('hide');
        $('#excelSampleModal').modal('show'); 
    });

    function downloadTemplate() {
        var header = 'name,internal_code,unit_of_measure,default_cost_price,default_selling_price';
        var row1   = 'Cooking Oil 2L,OIL-001,Each,1500.00,2000.00';
        var row2   = 'Drinking Water 500ml,WAT-001,Each,350.00,500.00';
        var row3   = 'Bread Loaf 700g,BRD-001,Each,600.00,800.00';
        var blob   = new Blob([header+'\n'+row1+'\n'+row2+'\n'+row3], {type:'text/csv;charset=utf-8;'});
        var url    = URL.createObjectURL(blob);
        var a      = document.createElement('a'); a.href=url; a.download='base_products_template.csv'; a.click();
        URL.revokeObjectURL(url);
    }
    $('#downloadTemplateBtn, #downloadTemplateBtnFooter, #downloadTemplateInline').on('click', function(e) {
        e.preventDefault(); downloadTemplate();
    });

    var dz = document.getElementById('dropZone');
    dz.addEventListener('dragover',  function(e) { e.preventDefault(); dz.classList.add('drag-over'); });
    dz.addEventListener('dragleave', function()  { dz.classList.remove('drag-over'); });
    dz.addEventListener('drop', function(e) {
        e.preventDefault(); dz.classList.remove('drag-over');
        var file = e.dataTransfer.files[0]; if (file) processCSVFile(file);
    });
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
            'name':'Product Name','internal_code':'Code',
            'unit_of_measure':'Unit','default_cost_price':'Order Price',
            'default_selling_price':'Selling Price'
        };
        var displayOrder = ['name','internal_code','unit_of_measure','default_cost_price','default_selling_price'];
        var shown = displayOrder.filter(function(k) { return headers.indexOf(k) >= 0; });
        if (!shown.length) shown = headers.slice(0,5);

        var thead = '<tr>' + shown.map(function(k, idx) {
            var cls = (idx === 0) ? '' : ' style="text-align:center"';
            return '<th' + cls + '>' + (displayMap[k] || k) + '</th>';
        }).join('') + '</tr>';
        $('#importPreviewHead').html(thead);

        var tbody = '';
        var limit = Math.min(5, rows.length);
        for (var i=0; i<limit; i++) {
            var r = rows[i];
            tbody += '<tr>' + shown.map(function(k, idx) {
                var v = r[k] || '—';
                if ((k==='default_cost_price'||k==='default_selling_price') && r[k] && !isNaN(parseFloat(r[k]))) {
                    v = parseFloat(r[k]).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
                }
                var cls = (idx === 0) ? '' : ' style="text-align:center"';
                return '<td' + cls + '>' + v + '</td>';
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
    });

    $('#submitImportBtn').on('click', function(e) {
        e.preventDefault();

        // Hard gate: category AND supplier must be selected
        var catText = @json($savedCategoryName ?? '');
        var supVal  = $('#filterSupplier').val();
        if (!catText || !supVal) {
            toastr.warning(
                'Please select a <strong>category</strong> and a <strong>supplier</strong> in the page filters before importing.',
                'Required'
            );
            return;
        }

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

        var importCat = @json($savedCategoryName ?? '');
        var importSup = $('#filterSupplier').val() || '';

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
            var payload = {
                name:                  row.name || '',
                internal_code:         row.internal_code         || '',
                unit_of_measure:       row.unit_of_measure       || 'Each',
                default_cost_price:    row.default_cost_price    || '',
                default_selling_price: row.default_selling_price || '',
                supplier:              importSup || row.supplier  || '',
                brand:                 row.brand                 || '',
                category:              importCat || row.category  || '',
                subcategory:           row.subcategory            || '',
                description:           row.description            || '',
                manufacturer:          row.manufacturer           || '',
                country_of_origin:     row.country_of_origin      || '',
                weight_kg:             row.weight_kg              || '',
                volume_litres:         row.volume_litres          || '',
                is_active:             1,
                _token: '{{ csrf_token() }}'
            };
            $.ajax({
                type:'POST', url:'{{ route("retail.operations.baseproducts.import.row") }}',
                data:payload, timeout:30000,
                success: function(data) {
                    done++;
                    if (data.status===201) {
                        succeeded++;
                        appendLog('<span class="text-success">✓</span> Row '+(index+1)+': '+row.name+' imported.');
                        if (data.product && window._dt) {
                            window._dt.row.add($(buildRow(data.product)));
                        }
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
    //  DataTable init
    // ════════════════════════════════════════════════════════════════════════
    function initDataTable() {
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

        // ── VIEW ──────────────────────────────────────────────────────────
        var _viewData = {};
        $('#tbody').on('click', '.viewDataBtn', function(e) {
            e.preventDefault();
            var b = $(this);
            _viewData = {
                id:b.data('id'), name:b.data('name'), description:b.data('description'),
                brand:b.data('brand'), supplier:b.data('supplier'), manufacturer:b.data('manufacturer'),
                origin:b.data('origin'), code:b.data('code'), unit:b.data('unit'),
                weight:b.data('weight'), volume:b.data('volume'),
                sell:b.data('sell'), cost:b.data('cost'),
                cat:b.data('cat'), active:b.data('active'),
                editRow:b.closest('tr').attr('id')
            };
            function v(val) { return (val===''||val===null||val===undefined) ? '<span class="muted">—</span>' : val; }
            $('#vw-name').text(_viewData.name);
            $('#vw-code-line').text(_viewData.code ? 'Code: '+_viewData.code : '');
            var badges = '';
            badges += _viewData.active==1 ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>';
            $('#vw-badges').html(badges);
            $('#vw-internal-code').html(v(_viewData.code));
            $('#vw-unit').html(v(_viewData.unit));
            $('#vw-brand').html(v(_viewData.brand));
            $('#vw-manufacturer').html(v(_viewData.manufacturer));
            $('#vw-supplier').html(v(_viewData.supplier));
            $('#vw-origin').html(v(_viewData.origin));
            $('#vw-category').html(v(_viewData.cat));
            $('#vw-description').html(v(_viewData.description));
            $('#vw-sell').html(_viewData.sell!==''&&_viewData.sell!==null ? fmtPrice(_viewData.sell) : '<span class="muted">—</span>');
            $('#vw-cost').html(_viewData.cost!==''&&_viewData.cost!==null ? fmtPrice(_viewData.cost) : '<span class="muted">—</span>');
            $('#vw-weight').html(_viewData.weight!=='' ? _viewData.weight+' kg' : '<span class="muted">—</span>');
            $('#vw-volume').html(_viewData.volume!=='' ? _viewData.volume+' L'  : '<span class="muted">—</span>');
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

        // ── ADD ───────────────────────────────────────────────────────────
        function filterNewModalSuppliers(catName) {
            var anyVisible = false;
            $('#new-supplier option').each(function() {
                if (!$(this).val()) return; // always keep the blank placeholder
                if (!catName) {
                    // No category filter active — show all
                    $(this).show();
                    anyVisible = true;
                    return;
                }
                var cats = ($(this).data('categories') || '').toString().split(',').map(function(c){ return c.trim(); });
                // Show supplier if: it has no category data (new supplier), or it belongs to this category
                if (cats.length === 0 || cats[0] === '' || cats.indexOf(catName) >= 0) {
                    $(this).show();
                    anyVisible = true;
                } else {
                    $(this).hide();
                }
            });
            $('#new-supplier-hint').toggle(!!catName && anyVisible);
        }

        function prefillNewModalFromFilter() {
            var catName = @json($savedCategoryName ?? '');
            $('#new-category').val(catName || '');
            if (catName) {
                $('#newCtxCatBadge').find('span').text(catName);
                $('#newCtxCatBadge').show();
                $('#newProductContextBanner').removeClass('d-none').css('display','flex');
            } else {
                $('#newProductContextBanner').addClass('d-none');
            }
            // Filter suppliers by category
            filterNewModalSuppliers(catName);
            // Pre-select supplier from filter bar if it is visible
            var supVal = $('#filterSupplier').val();
            if (supVal) {
                var $opt = $('#new-supplier option[value="'+supVal+'"]');
                if ($opt.length && $opt.css('display') !== 'none') {
                    $('#new-supplier').val(supVal);
                }
            }
        }

        $('#newDataBtn').on('click', function(e) {
            e.preventDefault();
            resetNewModal();
            prefillNewModalFromFilter();
            $('#newDataModal').modal('show');
        });
        $('#newDataModal').on('hidden.bs.modal', function() { resetNewModal(); });

        $('#submitDataBtn').on('click', function(e) {
            e.preventDefault();
            if (!$('#new-name').val().trim()) {
                toastr.warning('Product name is required.','Required'); $('#new-name').focus(); return;
            }
            var self=$(this); self.prop('disabled',true);
            $('#new-is-active-hidden').val($('#new-is-active').prop('checked') ? 1 : 0);
            $.ajaxSetup({ headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')} });
            $.ajax({
                type:'POST', url:'{{ route("retail.operations.baseproducts.insert") }}',
                data:collectNewFormData(), timeout:60000,
                beforeSend:function() { $('#progressBar').show(); },
                complete:  function() { $('#progressBar').hide(); self.prop('disabled',false); },
                success: function(data) {
                    if (data.status===201) {
                        toastr.success(data.success,'Success');
                        table.row.add($(buildRow(data.product))).draw(false);
                        $('#newDataModal').modal('hide');
                    } else if (data.status===422) {
                        toastr.error(data.error||'Validation failed.','Error');
                    } else {
                        toastr.info('Unspecified error.','Error');
                    }
                },
                error: handleAjaxError
            });
        });
        $('#cancelDataBtn').on('click', function(e) { e.preventDefault(); resetNewModal(); $('#newDataModal').modal('hide'); });

        // ── SINGLE DELETE ─────────────────────────────────────────────────
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

        // ── EDIT ──────────────────────────────────────────────────────────
        $('#tbody').on('click', '.editDataBtn', function() {
            var b=$(this);
            $('#editRow').val(b.attr('editRow'));
            $('#editId').val(b.attr('editId'));
            $('#editName').val(b.attr('editName'));
            $('#editDescription').val(b.attr('editDescription'));
            $('#editBrand').val(b.attr('editBrand'));
            $('#editSupplier').val(b.attr('editSupplier'));
            $('#editManufacturer').val(b.attr('editManufacturer'));
            $('#editCountryOfOrigin').val(b.attr('editCountryOfOrigin'));
            $('#editInternalCode').val(b.attr('editInternalCode'));
            $('#editUnitOfMeasure').val(b.attr('editUnitOfMeasure'));
            $('#editWeightKg').val(b.attr('editWeightKg'));
            $('#editVolumeLitres').val(b.attr('editVolumeLitres'));
            $('#editSellingPrice').val(b.attr('editDefaultSellingPrice'));
            $('#editCostPrice').val(b.attr('editDefaultCostPrice'));
            $('#editCategory').val(b.attr('editCategory'));
            $('#editIsActive').prop('checked', b.attr('editIsActive')==1);
            $('#editModalTabs button[data-bs-target="#etab1"]').tab('show');
            $('#editDataModal').modal('show');
        });
        $('#submitUpdateDataBtn').on('click', function(e) {
            e.preventDefault();
            var self=$(this); self.prop('disabled',true);
            var row=$('#editRow').val();
            $.ajaxSetup({ headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')} });
            $.ajax({
                type:'POST', url:'{{ route("retail.operations.baseproducts.update") }}',
                data:collectEditFormData(), timeout:60000,
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

        // ── BULK DELETE ───────────────────────────────────────────────────
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

        // ── BULK ACTIVATE / DEACTIVATE ────────────────────────────────────
        function doBulkStatus(isActive) {
            var selected=[]; $('.selectRow:checked').each(function() { selected.push($(this).val()); });
            if (!selected.length) return;
            $.ajaxSetup({ headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')} });
            $.ajax({
                type:'POST', url:'{{ route("retail.operations.baseproducts.bulkstatus") }}',
                data:{ids:selected,is_active:isActive,_token:'{{ csrf_token() }}'}, timeout:60000,
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
        $('#bulkActivateBtn').on('click',   function(e) { e.preventDefault(); doBulkStatus(1); });
        $('#bulkDeactivateBtn').on('click', function(e) { e.preventDefault(); doBulkStatus(0); });

        // ── BULK SUPPLIER ─────────────────────────────────────────────────
        $('#bulkClearSupplierCheck').on('change', function() {
            $('#bulkSupplierSelect').prop('disabled', $(this).prop('checked'));
            if ($(this).prop('checked')) $('#bulkSupplierSelect').val('');
        });
        $('#applyBulkSupplierBtn').on('click', function(e) {
            e.preventDefault();
            var clearing = $('#bulkClearSupplierCheck').prop('checked');
            var supplier = clearing ? '' : $('#bulkSupplierSelect').val().trim();
            if (!clearing && supplier==='') { toastr.warning('Select a supplier or tick "Clear".','Required'); return; }
            var selected=[]; $('.selectRow:checked').each(function() { selected.push($(this).val()); });
            if (!selected.length) return;
            $.ajaxSetup({ headers:{'X-CSRF-TOKEN':$('meta[name="csrf-token"]').attr('content')} });
            $.ajax({
                type:'POST', url:'{{ route("retail.operations.baseproducts.bulksupplier") }}',
                data:{ids:selected,supplier:supplier,_token:'{{ csrf_token() }}'}, timeout:60000,
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

        // ── SELECT ALL ────────────────────────────────────────────────────
        $('#selectAll').on('click', function() {
            $('.selectRow').prop('checked', this.checked); updateSelectedCount();
        });
        $('#tbody').on('click', '.selectRow', function() { updateSelectedCount(); });

    } // end initDataTable

    function resetNewModal() {
        $('#newDataForm')[0].reset();
        $('#new-unit-of-measure').val('Each');
        $('#new-is-active').prop('checked', true);
        $('#new-is-active-hidden').val(1);
        $('#new-category').val('');
        $('#newProductContextBanner').addClass('d-none');
        $('#new-supplier-hint').hide();
        // Restore all supplier options
        $('#new-supplier option').show();
    }

    initDataTable();
});
</script>
@endsection