@extends('operations.retail.dashboard')
@section('content')
<style>
/* ── DataTable export buttons ──────────────────────────────────────────── */
.dt-buttons .btn {
  background: transparent !important; background-image: none !important;
  box-shadow: none !important; border-color: #5bc0de; color: #5bc0de;
}
.dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }

/* ── Card chrome ───────────────────────────────────────────────────────── */
.card-header {
  padding: 0.5rem 1.5rem !important;
  background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff;
}
.card-body  { padding: 0 1.5rem 1.5rem 1.5rem !important; }
.card       { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; }
.card-header h4 { color:#fff; font-weight:600; margin-bottom:0; display:flex; align-items:center; }
.card-header h4 i { margin-right: 0.25rem; }

/* ── Standard header icon buttons ─────────────────────────────────────── */
.card-header .btn-light {
  height: 28px; padding: 0 10px;
  display: flex; align-items: center; justify-content: center; line-height: 1;
}
.card-header .btn-light:hover { background-color: #f8f9fa; transition: background-color 0.2s; }

/* ── Filter bar ────────────────────────────────────────────────────────── */
#filterBar {
  background: #f8f9ff;
  border: 1px solid #dde3f8;
  border-radius: 10px;
  padding: 10px 16px;
  margin-bottom: 14px;
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}
#filterBar label { font-size: 12px; font-weight: 600; color: #4B5EBD; margin-bottom: 0; white-space: nowrap; }
#filterBar select, #filterBar .filter-select {
  font-size: 12px; height: 30px; padding: 0 8px; border-radius: 6px;
  border: 1px solid #c8d0ed; background: #fff; min-width: 160px; max-width: 220px;
}
#filterBar .filter-divider { width: 1px; height: 22px; background: #c8d0ed; margin: 0 4px; }
.filter-badge { font-size: 11px; background: #4B5EBD; color: #fff; border-radius: 10px; padding: 1px 8px; }

/* ── Bulk-action toolbar ───────────────────────────────────────────────── */
#bulkActionsBar {
  display: none; align-items: center; gap: 4px;
  background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px;
  padding: 3px 10px; margin-right: 6px;
}
#bulkActionsBar .bulk-count {
  font-size: 12px; font-weight: 700; color: #856404;
  margin-right: 6px; white-space: nowrap;
}
.btn-bulk {
  height: 26px; padding: 0 9px; font-size: 12px;
  display: inline-flex; align-items: center; gap: 4px; border-radius: 6px;
}

/* ── Table alignment ───────────────────────────────────────────────────── */
#maintable thead th { text-align: center !important; vertical-align: middle; }
#maintable thead th:first-child { text-align: left !important; }
#maintable tbody td { text-align: center !important; vertical-align: middle; }
#maintable tbody td:first-child { text-align: left !important; }

/* ── Badges ────────────────────────────────────────────────────────────── */
.tax-badge  { font-size:11px; padding:2px 8px; border-radius:10px; font-weight:600; letter-spacing:0.5px; }
.price-cell { font-size:12px; font-weight:600; color:#198754; }

/* ── Form section dividers ─────────────────────────────────────────────── */
.section-label {
  font-size:11px; font-weight:700; text-transform:uppercase;
  letter-spacing:0.8px; color:#6c757d;
  border-bottom:1px solid #e9ecef;
  padding-bottom:4px; margin-bottom:12px; margin-top:8px;
}

/* ── Fixed first column background ────────────────────────────────────── */
table.dataTable tbody tr.odd  td.dtfc-fixed-left { background-color:#f2f2f2 !important; }
table.dataTable tbody tr.even td.dtfc-fixed-left { background-color:#fff    !important; }
table.dataTable tbody tr:hover td.dtfc-fixed-left { background-color:#e8f4fd !important; }

/* ════════════════════════════════════════════════════════════════════════
   ADD MODAL — TABBED DESIGN
════════════════════════════════════════════════════════════════════════ */
#newDataModal .modal-header {
  background: linear-gradient(135deg, #4B5EBD 0%, #576CC0 100%);
  padding: 0 !important; border-bottom: none;
  border-radius: 8px 8px 0 0; flex-direction: column; align-items: stretch;
}
#newDataModal .modal-header-top {
  display: flex; align-items: center; justify-content: space-between;
  padding: 12px 18px 8px 18px;
}
#newDataModal .modal-header-top .modal-title {
  color: #fff; font-size: 15px; font-weight: 600;
  display: flex; align-items: center; gap: 6px;
}
#newDataModal .modal-header-top .btn-close { filter: brightness(0) invert(1); opacity: 0.8; }
#newDataModal .modal-header-top .btn-close:hover { opacity: 1; }
#newDataModal .modal-tabs { display: flex; padding: 0 18px; gap: 2px; }
#newDataModal .modal-tabs .nav-tab-item {
  flex: 1; text-align: center; padding: 8px 4px 10px;
  font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.6);
  cursor: pointer; border-bottom: 3px solid transparent;
  transition: all 0.2s ease; user-select: none;
  display: flex; align-items: center; justify-content: center;
  gap: 5px; white-space: nowrap;
}
#newDataModal .modal-tabs .nav-tab-item:hover { color: rgba(255,255,255,0.9); }
#newDataModal .modal-tabs .nav-tab-item.active { color: #fff; border-bottom-color: #fff; }
#newDataModal .modal-tabs .nav-tab-item .tab-num {
  display: inline-flex; align-items: center; justify-content: center;
  width: 18px; height: 18px; border-radius: 50%; font-size: 10px; font-weight: 700;
  background: rgba(255,255,255,0.2); color: rgba(255,255,255,0.7); flex-shrink: 0;
}
#newDataModal .modal-tabs .nav-tab-item.active .tab-num { background: rgba(255,255,255,0.9); color: #4B5EBD; }
#newDataModal .modal-tabs .nav-tab-item.done .tab-num { background: rgba(255,255,255,0.4); color: #fff; }
#newDataModal .modal-body { padding: 20px 20px 8px 20px !important; min-height: 320px; }
#newDataModal .tab-pane { display: none; animation: fadeTabIn 0.2s ease; }
#newDataModal .tab-pane.active { display: block; }
@keyframes fadeTabIn {
  from { opacity: 0; transform: translateY(4px); }
  to   { opacity: 1; transform: translateY(0); }
}
#newDataModal .req { color: #dc3545; }
.mra-default-badge {
  font-size: 10px; padding: 1px 6px; border-radius: 8px;
  background: #e8f5e9; color: #2e7d32; font-weight: 600;
  border: 1px solid #a5d6a7; margin-left: 6px; vertical-align: middle;
}
#newDataModal .input-group-text {
  font-size: 11px; color: #6c757d; background: #f8f9fa; min-width: 46px; justify-content: center;
}
#newDataModal .modal-footer {
  padding: 10px 20px 14px; border-top: 1px solid #f0f0f0;
  display: flex; align-items: center; justify-content: space-between; gap: 8px;
}
#newDataModal .modal-footer .footer-left  { display: flex; gap: 6px; }
#newDataModal .modal-footer .footer-right { display: flex; gap: 6px; }
.tab-dots { display: flex; align-items: center; gap: 5px; }
.tab-dots span {
  display: inline-block; width: 6px; height: 6px; border-radius: 50%;
  background: #dee2e6; transition: all 0.25s ease;
}
.tab-dots span.active { background: #4B5EBD; width: 18px; border-radius: 3px; }
.btn-tab-nav { font-size: 12px; padding: 5px 14px; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px; }

/* ── Import modal ──────────────────────────────────────────────────────── */
#importModal .modal-header {
  background: linear-gradient(135deg, #2d6a4f 0%, #40916c 100%);
  padding: 0 !important; border-bottom: none; border-radius: 8px 8px 0 0;
  flex-direction: column; align-items: stretch;
}
#importModal .modal-header-top {
  display: flex; align-items: center; justify-content: space-between; padding: 12px 18px 8px 18px;
}
#importModal .modal-header-top .modal-title { color: #fff; font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
#importModal .modal-header-top .btn-close { filter: brightness(0) invert(1); opacity: 0.8; }
#importModal .modal-tabs { display: flex; padding: 0 18px; gap: 2px; }
#importModal .modal-tabs .nav-tab-item {
  flex: 1; text-align: center; padding: 8px 4px 10px;
  font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.6);
  cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.2s ease;
  user-select: none; display: flex; align-items: center; justify-content: center; gap: 5px;
}
#importModal .modal-tabs .nav-tab-item:hover { color: rgba(255,255,255,0.9); }
#importModal .modal-tabs .nav-tab-item.active { color: #fff; border-bottom-color: #fff; }
#importModal .modal-tabs .nav-tab-item .tab-num {
  display: inline-flex; align-items: center; justify-content: center;
  width: 18px; height: 18px; border-radius: 50%; font-size: 10px; font-weight: 700;
  background: rgba(255,255,255,0.2); color: rgba(255,255,255,0.7); flex-shrink: 0;
}
#importModal .modal-tabs .nav-tab-item.active .tab-num { background: rgba(255,255,255,0.9); color: #2d6a4f; }
.csv-col-table th { background: #e8f5e9; font-size: 12px; }
.csv-col-table td { font-size: 12px; vertical-align: middle; }
.csv-col-table code { background: #f1f8e9; padding: 1px 5px; border-radius: 4px; color: #2d6a4f; font-size: 11px; }
.drop-zone {
  border: 2px dashed #40916c; border-radius: 12px; padding: 32px 20px;
  text-align: center; cursor: pointer; transition: all 0.2s;
  background: #f0faf5; position: relative;
}
.drop-zone:hover, .drop-zone.drag-over { background: #d8f3e6; border-color: #1b4332; }
.drop-zone input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
.drop-zone i { font-size: 40px; color: #40916c; display: block; margin-bottom: 8px; }
#importPreviewTable { font-size: 12px; }
#importPreviewTable th { background: #e8f5e9; position: sticky; top: 0; }
.import-progress-bar { height: 6px; border-radius: 3px; background: #e9ecef; overflow: hidden; }
.import-progress-bar .bar { height: 100%; width: 0; background: linear-gradient(to right, #40916c, #52b788); transition: width 0.3s ease; border-radius: 3px; }
</style>

<div class="progress" id="progressBar" role="progressbar"
     style="height:8px;transform:rotate(180deg);display:none">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

{{-- ── Card header ──────────────────────────────────────────────────────── --}}
<div class="card-header d-flex justify-content-between align-items-center">
  <h4 class="header-title mb-0">
    <i class="ri-store-2-line"></i> Base Products
  </h4>

  <div class="d-flex align-items-center flex-wrap" style="gap:4px;">
    <div id="bulkActionsBar">
      <span class="bulk-count"><span id="selectedCount">0</span> selected</span>
      <a href="#" class="btn btn-bulk btn-warning text-dark" id="changeSupplierBtn" title="Change Supplier">
        <i class="ri-truck-line"></i> Supplier
      </a>
      <a href="#" class="btn btn-bulk btn-info text-white" id="bulkActivateBtn" title="Set Active">
        <i class="ri-checkbox-circle-line"></i> Activate
      </a>
      <a href="#" class="btn btn-bulk btn-secondary text-white" id="bulkDeactivateBtn" title="Set Inactive">
        <i class="ri-close-circle-line"></i> Deactivate
      </a>
      <a href="#" class="btn btn-bulk btn-danger" id="deleteSelectedBtn" title="Delete selected">
        <i class="ri-delete-bin-line"></i> Delete
      </a>
    </div>

    <a href="#" class="btn btn-light text-success fs-16 mx-1" id="importBtn"       title="Import from CSV"><i class="ri-file-excel-2-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="newDataBtn"      title="Add new product"><i class="ri-add-circle-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn"         title="Info"><i class="ri-information-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download"><i class="ri-download-line"></i></a>
  </div>

  <?php
    $maintableTitle = "Retail Base Products";
    $categories = DB::connection('tenant')->table('categories')->orderBy('category')->get();
    $suppliers   = DB::connection('tenant')->table('retail_base_products')
                     ->whereNotNull('supplier')->where('supplier','!=','')
                     ->distinct()->orderBy('supplier')->pluck('supplier');
    $products    = DB::connection('tenant')->table('retail_base_products')->get();

  ?>
</div>

{{-- ── Filter bar ────────────────────────────────────────────────────────── --}}
<div class="card-body" style="padding-bottom:0 !important">
  <div id="filterBar">
    <i class="ri-filter-3-line text-primary"></i>
    <label>Category:</label>
    <select id="filterCategory" class="filter-select">
      <option value="">All Categories</option>
      @foreach($categories as $cat)
        <option value="{{ $cat->id }}">{{ $cat->category }}</option>
      @endforeach
    </select>
    <div class="filter-divider"></div>
    <label>Supplier:</label>
    <select id="filterSupplier" class="filter-select">
      <option value="">All Suppliers</option>
      @foreach($suppliers as $sup)
        <option value="{{ $sup }}">{{ $sup }}</option>
      @endforeach
    </select>
    <a href="#" class="btn btn-sm btn-outline-secondary ms-1" id="clearFilterBtn" style="font-size:11px;height:28px;display:inline-flex;align-items:center;gap:4px">
      <i class="ri-close-line"></i> Clear
    </a>
    <span id="filterInfo" class="ms-2" style="font-size:12px;color:#6c757d;display:none">
      Showing <span id="filterInfoCount" class="filter-badge">0</span> products
    </span>
  </div>
</div>

{{-- ── Table ────────────────────────────────────────────────────────────── --}}
<div class="card-body">
<table id="maintable" class="table table-sm table-striped row-border order-column w-100">
  <thead style="background-color:#e2e2e9">
    <tr>
      <th><input type="checkbox" id="selectAll">&nbsp;&nbsp;Product Name</th>
      <th>Internal Code</th>
      <th>Unit</th>
      <th>Cost Price</th>
      <th>Sell Price</th>
      <th>Supplier</th>
      <th>Brand</th>
      <th>Category</th>
      <th>Tax Rate</th>
      <th>Type</th>
      <th>Status</th>
      <th>Action</th>
    </tr>
  </thead>
  <tbody id="tbody">
    @foreach($products as $product)
      <?php $row = "row".$product->id ?>
      <tr id="{{ $row }}" data-category-id="{{ $product->category }}" data-supplier="{{ $product->supplier }}">
        <td>
          <input type="checkbox" class="selectRow" value="{{ $product->id }}" data-row-id="{{ $row }}">
          &nbsp;{{ $product->name }}
        </td>
        <td>{{ $product->internal_code ?? '—' }}</td>
        <td>{{ $product->unit_of_measure }}</td>
        <td>
          @if($product->default_cost_price !== null)
            <span style="font-size:12px;color:#6c757d">{{ number_format($product->default_cost_price, 2) }}</span>
          @else <span class="text-muted" style="font-size:12px">—</span>
          @endif
        </td>
        <td>
          @if($product->default_selling_price !== null)
            <span class="price-cell">{{ number_format($product->default_selling_price, 2) }}</span>
          @else <span class="text-muted" style="font-size:12px">—</span>
          @endif
        </td>
        <td>{{ $product->supplier ?? '—' }}</td>
        <td>{{ $product->brand ?? '—' }}</td>
        <td>{{ $product->category_name ?? '—' }}</td>
        <td>
          @if($product->mra_tax_rate_id)
            <span class="badge tax-badge
              @if($product->mra_tax_rate_id==='A') bg-danger
              @elseif($product->mra_tax_rate_id==='E') bg-secondary
              @elseif($product->mra_tax_rate_id==='TL') bg-warning text-dark
              @else bg-info @endif">{{ $product->mra_tax_rate_id }}</span>
          @else <span class="text-muted" style="font-size:12px">—</span>
          @endif
        </td>
        <td>
          <span class="badge {{ $product->is_product ? 'bg-primary' : 'bg-warning text-dark' }}">
            {{ $product->is_product ? 'Product' : 'Service' }}
          </span>
        </td>
        <td>
          <span class="badge {{ $product->is_active ? 'bg-success' : 'bg-danger' }}">
            {{ $product->is_active ? 'Active' : 'Inactive' }}
          </span>
        </td>
        <td>
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
             editIsProduct="{{ $product->is_product }}"
             editDefaultSellingPrice="{{ $product->default_selling_price }}"
             editDefaultCostPrice="{{ $product->default_cost_price }}"
             editMraProductCode="{{ $product->mra_product_code }}"
             editMraTaxRateId="{{ $product->mra_tax_rate_id }}"
             editIsVatExemptByNature="{{ $product->is_vat_exempt_by_nature }}"
             editCategoryId="{{ $product->category}}"
             editSubcategory="{{ $product->subcategory }}"
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
<section>
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
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     INFO MODAL
══════════════════════════════════════════════════════════════════════ --}}
<section>
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Base Products</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <p class="mb-1">Base products are the <strong>permanent catalogue</strong> — identity fields that never change regardless of where a product is sold.</p>
      <p class="mb-1"><strong>Default prices</strong> apply to all branches unless a branch product record overrides them.</p>
      <p class="mb-1">MRA EIS Tax Rate IDs:</p>
      <ul class="mt-1 mb-0" style="font-size:13px">
        <li><code>A</code> — VAT-A (Standard 16.5%)</li>
        <li><code>E</code> — Exempt (zero-rated)</li>
        <li><code>TL</code> — Tourism Levy (1%)</li>
      </ul>
    </div>
  </div></div>
</div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     ADD MODAL  ·  Tabbed
══════════════════════════════════════════════════════════════════════ --}}
<section>
<div class="modal fade" id="newDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">

      <div class="modal-header">
        <div class="modal-header-top">
          <h5 class="modal-title"><i class="ri-add-circle-line"></i> Add Base Product</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" id="newDataModalCloseBtn"></button>
        </div>
        <div class="modal-tabs" id="newModalTabStrip">
          <div class="nav-tab-item active" data-tab="newTab1">
            <span class="tab-num">1</span><i class="ri-price-tag-3-line"></i> Essentials
          </div>
          <div class="nav-tab-item" data-tab="newTab2">
            <span class="tab-num">2</span><i class="ri-file-list-3-line"></i> Details
          </div>
          <div class="nav-tab-item" data-tab="newTab3">
            <span class="tab-num">3</span><i class="ri-government-line"></i> MRA / Tax
          </div>
        </div>
      </div>

      <div class="modal-body">
        <form action="#" method="post" id="newDataForm">
          @csrf

          {{-- TAB 1 — ESSENTIALS --}}
          <div class="tab-pane active" id="newTab1">
            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:13px">
                Product / Service Name <span class="req">*</span>
              </label>
              <input class="form-control" type="text" name="name" id="new-name"
                     placeholder="e.g. Cooking Oil 2L" autocomplete="off" required />
              <div class="form-text">The permanent name shown on invoices and POS.</div>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-7">
                <label class="form-label fw-semibold" style="font-size:13px">Unit of Measure <span class="req">*</span></label>
                <select class="form-select" name="unit_of_measure" id="new-unit-of-measure" required>
                  <option value="Each" selected>Each (default)</option>
                  <option value="kg">Kilogram (kg)</option>
                  <option value="g">Gram (g)</option>
                  <option value="Litre">Litre</option>
                  <option value="ml">Millilitre (ml)</option>
                  <option value="Box">Box</option>
                  <option value="Carton">Carton</option>
                  <option value="Pack">Pack</option>
                  <option value="Pair">Pair</option>
                  <option value="Dozen">Dozen</option>
                  <option value="Bag">Bag</option>
                  <option value="Bottle">Bottle</option>
                  <option value="Metre">Metre</option>
                  <option value="Service">Service (N/A)</option>
                </select>
              </div>
              <div class="col-5">
                <label class="form-label fw-semibold" style="font-size:13px">Type</label>
                <div class="d-flex gap-3 mt-1" style="padding-top:3px">
                  <div class="form-check mb-0">
                    <input class="form-check-input" type="radio" name="is_product" id="new-is-product-yes" value="1" checked>
                    <label class="form-check-label" for="new-is-product-yes" style="font-size:13px">Product</label>
                  </div>
                  <div class="form-check mb-0">
                    <input class="form-check-input" type="radio" name="is_product" id="new-is-product-no" value="0">
                    <label class="form-check-label" for="new-is-product-no" style="font-size:13px">Service</label>
                  </div>
                </div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:13px">Supplier</label>
              <select class="form-select" name="supplier" id="new-supplier">
                <option value="">— Select Supplier —</option>
                @foreach($suppliers as $sup)
                  <option value="{{ $sup }}">{{ $sup }}</option>
                @endforeach
              </select>
            </div>

            <div class="row g-2 mb-1">
              <div class="col-6">
                <label class="form-label fw-semibold" style="font-size:13px">Selling Price</label>
                <div class="input-group">
                  <span class="input-group-text">MWK</span>
                  <input class="form-control" type="number" step="0.01" min="0"
                         name="default_selling_price" id="new-selling-price" placeholder="0.00" />
                </div>
                <div class="form-text">Leave blank if not yet finalised.</div>
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold" style="font-size:13px">Cost / Order Price</label>
                <div class="input-group">
                  <span class="input-group-text">MWK</span>
                  <input class="form-control" type="number" step="0.01" min="0"
                         name="default_cost_price" id="new-cost-price" placeholder="0.00" />
                </div>
                <div class="form-text">Never sent to MRA.</div>
              </div>
            </div>
          </div>{{-- /newTab1 --}}

          {{-- TAB 2 — DETAILS --}}
          <div class="tab-pane" id="newTab2">
            <div class="row g-2 mb-3">
              <div class="col-5">
                <label class="form-label fw-semibold" style="font-size:13px">Internal Code</label>
                <input class="form-control" type="text" name="internal_code" id="new-internal-code"
                       placeholder="e.g. OIL-001" autocomplete="off" />
                <div class="form-text">Unique import match key.</div>
              </div>
              <div class="col-4">
                <label class="form-label fw-semibold" style="font-size:13px">Brand</label>
                <input class="form-control" type="text" name="brand" id="new-brand"
                       placeholder="e.g. Soya" autocomplete="off" />
              </div>
              <div class="col-3">
                <label class="form-label fw-semibold" style="font-size:13px">Origin</label>
                <input class="form-control" type="text" name="country_of_origin" id="new-country-of-origin"
                       placeholder="MW" maxlength="2" autocomplete="off" style="text-transform:uppercase" />
                <div class="form-text">ISO-2</div>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:13px">Description</label>
              <textarea class="form-control" name="description" id="new-description" rows="2"
                        placeholder="Short product description (optional)" style="font-size:13px;resize:none"></textarea>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label fw-semibold" style="font-size:13px">Manufacturer</label>
                <input class="form-control" type="text" name="manufacturer" id="new-manufacturer"
                       placeholder="e.g. Illovo Sugar" autocomplete="off" />
              </div>
              <div class="col-3">
                <label class="form-label fw-semibold" style="font-size:13px">Weight (kg)</label>
                <input class="form-control" type="number" step="0.0001" min="0"
                       name="weight_kg" id="new-weight-kg" placeholder="0.0000" />
              </div>
              <div class="col-3">
                <label class="form-label fw-semibold" style="font-size:13px">Volume (L)</label>
                <input class="form-control" type="number" step="0.0001" min="0"
                       name="volume_litres" id="new-volume-litres" placeholder="0.0000" />
              </div>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label fw-semibold" style="font-size:13px">Category</label>
                <select class="form-select" name="category_id" id="new-category-id">
                  <option value="">— Select Category —</option>
                  @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->category }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-6">
                <label class="form-label fw-semibold" style="font-size:13px">Subcategory</label>
                <input class="form-control" type="text" name="subcategory" id="new-subcategory"
                       placeholder="e.g. Cooking Oils" autocomplete="off" />
              </div>
            </div>

            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_active" id="new-is-active" value="1" checked>
              <label class="form-check-label" for="new-is-active" style="font-size:13px">
                <strong>Active</strong> — can be assigned to branches
              </label>
            </div>
          </div>{{-- /newTab2 --}}

          {{-- TAB 3 — MRA / TAX --}}
          <div class="tab-pane" id="newTab3">
            <div class="alert alert-light border d-flex align-items-start gap-2 py-2 px-3 mb-3"
                 style="font-size:12px;border-radius:8px;background:#f8f9ff !important;border-color:#c5cdf5 !important;">
              <i class="ri-information-line text-primary mt-1" style="flex-shrink:0"></i>
              <div>
                These fields map directly to MRA EIS invoice fields.
                <strong>Tax Rate</strong> defaults to <code>A</code> (standard VAT 16.5%) — change only if needed.
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:13px">
                MRA Tax Rate ID <span class="req">*</span>
                <span class="mra-default-badge">Default: A</span>
              </label>
              <select class="form-select" name="mra_tax_rate_id" id="new-mra-tax-rate-id" required>
                <option value="A" selected>A — VAT-A (Standard 16.5%)</option>
                <option value="E">E — Exempt (zero-rated)</option>
                <option value="TL">TL — Tourism Levy (1%)</option>
              </select>
              <div class="form-text">→ sent as <code>invoiceLineItems[].taxRateId</code></div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold" style="font-size:13px">MRA Product Code</label>
              <input class="form-control" type="text" name="mra_product_code" id="new-mra-product-code"
                     placeholder="e.g. 50201700" autocomplete="off" />
              <div class="form-text">UN/SPSC code → <code>invoiceLineItems[].productCode</code></div>
            </div>

            <div class="card border-0 mb-1" style="background:#fff8e1;border-radius:8px;">
              <div class="card-body py-2 px-3">
                <div class="form-check mb-0">
                  <input class="form-check-input" type="checkbox" name="is_vat_exempt_by_nature"
                         id="new-is-vat-exempt" value="1">
                  <label class="form-check-label" for="new-is-vat-exempt" style="font-size:13px">
                    <strong>VAT Exempt by Nature</strong>
                    <small class="text-muted d-block mt-1">
                      Tick for foodstuffs, medicine, agricultural inputs, etc. —
                      always exempt regardless of branch. Use rate <code>E</code> when ticked.
                    </small>
                  </label>
                </div>
              </div>
            </div>
          </div>{{-- /newTab3 --}}

        </form>
      </div>

      <div class="modal-footer">
        <div class="footer-left">
          <div class="tab-dots align-self-center me-2" id="newTabDots">
            <span class="active" data-dot="1"></span>
            <span data-dot="2"></span>
            <span data-dot="3"></span>
          </div>
          <a href="#" class="btn btn-outline-secondary btn-tab-nav d-none" id="newTabBackBtn">
            <i class="ri-arrow-left-s-line"></i> Back
          </a>
        </div>
        <div class="footer-right">
          <a href="#" class="btn btn-secondary btn-tab-nav" id="cancelDataBtn">
            <i class="ri-close-line"></i> Cancel
          </a>
          <a href="#" class="btn btn-primary btn-tab-nav" id="newTabNextBtn">
            Next <i class="ri-arrow-right-s-line"></i>
          </a>
          <a href="#" class="btn btn-success btn-tab-nav d-none" id="submitDataBtn">
            <i class="ri-check-line"></i> Save Product
          </a>
        </div>
      </div>

    </div>
  </div>
</div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     IMPORT MODAL
══════════════════════════════════════════════════════════════════════ --}}
<section>
<div class="modal fade" id="importModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.2);">

      <div class="modal-header">
        <div class="modal-header-top">
          <h5 class="modal-title"><i class="ri-file-excel-2-line"></i> Import Products from CSV</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" id="importModalCloseBtn"></button>
        </div>
        <div class="modal-tabs" id="importModalTabStrip">
          <div class="nav-tab-item active" data-itab="iTab1">
            <span class="tab-num">1</span><i class="ri-question-line"></i> How to Prepare
          </div>
          <div class="nav-tab-item" data-itab="iTab2">
            <span class="tab-num">2</span><i class="ri-upload-2-line"></i> Upload &amp; Import
          </div>
        </div>
      </div>

      <div class="modal-body" style="padding:20px !important;min-height:360px">

        {{-- iTab 1 — How to Prepare --}}
        <div class="tab-pane active" id="iTab1">
          <div class="alert alert-info border-0 py-2 px-3 mb-3" style="font-size:12px;border-radius:8px;">
            <i class="ri-information-line me-1"></i>
            Download the template, fill in your data, and save as <strong>CSV (UTF-8)</strong>.
            The first row must be the header row exactly as shown below.
          </div>

          <div class="d-flex justify-content-between align-items-center mb-2">
            <strong style="font-size:13px"><i class="ri-table-line me-1 text-success"></i> Required CSV Columns</strong>
            <a href="#" id="downloadTemplateBtn" class="btn btn-sm btn-outline-success" style="font-size:12px">
              <i class="ri-download-2-line"></i> Download Template
            </a>
          </div>

          <div class="table-responsive" style="max-height:340px;overflow-y:auto;">
            <table class="table table-sm table-bordered csv-col-table mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Column Header</th>
                  <th>Required?</th>
                  <th>Default</th>
                  <th>Description / Example</th>
                </tr>
              </thead>
              <tbody>
                <tr><td>1</td><td><code>name</code></td><td><span class="badge bg-danger">Yes</span></td><td>—</td><td>Product name (e.g. <em>Cooking Oil 2L</em>)</td></tr>
                <tr><td>2</td><td><code>internal_code</code></td><td><span class="badge bg-secondary">No</span></td><td>blank</td><td>Unique internal code (e.g. <em>OIL-001</em>)</td></tr>
                <tr><td>3</td><td><code>unit_of_measure</code></td><td><span class="badge bg-secondary">No</span></td><td>Each</td><td>Each / kg / g / Litre / ml / Box / Carton / Pack / Pair / Dozen / Bag / Bottle / Metre / Service</td></tr>
                <tr><td>4</td><td><code>default_cost_price</code></td><td><span class="badge bg-secondary">No</span></td><td>blank</td><td>Numeric, e.g. <em>1500.00</em></td></tr>
                <tr><td>5</td><td><code>default_selling_price</code></td><td><span class="badge bg-secondary">No</span></td><td>blank</td><td>Numeric, e.g. <em>2000.00</em></td></tr>
                <tr><td>6</td><td><code>supplier</code></td><td><span class="badge bg-secondary">No</span></td><td>blank</td><td>Supplier name (must match existing supplier or leave blank)</td></tr>
                <tr><td>7</td><td><code>brand</code></td><td><span class="badge bg-secondary">No</span></td><td>blank</td><td>Brand name, e.g. <em>Soya</em></td></tr>
                <tr><td>8</td><td><code>category</code></td><td><span class="badge bg-secondary">No</span></td><td>blank</td><td>Exact category name from your categories list</td></tr>
                <tr><td>9</td><td><code>subcategory</code></td><td><span class="badge bg-secondary">No</span></td><td>blank</td><td>e.g. <em>Cooking Oils</em></td></tr>
                <tr><td>10</td><td><code>description</code></td><td><span class="badge bg-secondary">No</span></td><td>blank</td><td>Short description</td></tr>
                <tr><td>11</td><td><code>manufacturer</code></td><td><span class="badge bg-secondary">No</span></td><td>blank</td><td>e.g. <em>Illovo Sugar</em></td></tr>
                <tr><td>12</td><td><code>country_of_origin</code></td><td><span class="badge bg-secondary">No</span></td><td>blank</td><td>2-letter ISO code, e.g. <em>MW</em></td></tr>
                <tr><td>13</td><td><code>weight_kg</code></td><td><span class="badge bg-secondary">No</span></td><td>blank</td><td>Numeric, e.g. <em>2.0000</em></td></tr>
                <tr><td>14</td><td><code>volume_litres</code></td><td><span class="badge bg-secondary">No</span></td><td>blank</td><td>Numeric, e.g. <em>2.0000</em></td></tr>
                <tr><td>15</td><td><code>mra_tax_rate_id</code></td><td><span class="badge bg-secondary">No</span></td><td>A</td><td>A / E / TL</td></tr>
                <tr><td>16</td><td><code>mra_product_code</code></td><td><span class="badge bg-secondary">No</span></td><td>blank</td><td>UN/SPSC code, e.g. <em>50201700</em></td></tr>
                <tr><td>17</td><td><code>is_product</code></td><td><span class="badge bg-secondary">No</span></td><td>1</td><td>1 = Product, 0 = Service</td></tr>
                <tr><td>18</td><td><code>is_vat_exempt_by_nature</code></td><td><span class="badge bg-secondary">No</span></td><td>0</td><td>1 = VAT exempt, 0 = Not exempt</td></tr>
                <tr><td>19</td><td><code>is_active</code></td><td><span class="badge bg-secondary">No</span></td><td>1</td><td>1 = Active, 0 = Inactive</td></tr>
              </tbody>
            </table>
          </div>

          <div class="alert alert-warning border-0 mt-3 py-2 px-3 mb-0" style="font-size:12px;border-radius:8px;">
            <i class="ri-error-warning-line me-1"></i>
            <strong>Important:</strong> Do not change the header row. Rows with a blank or duplicate <code>name</code> will be skipped.
            If <code>internal_code</code> already exists in the database, that row will be skipped.
          </div>
        </div>{{-- /iTab1 --}}

        {{-- iTab 2 — Upload & Import --}}
        <div class="tab-pane" id="iTab2">

          {{-- Step A: Upload --}}
          <div id="importStepUpload">
            <div class="drop-zone" id="dropZone">
              <input type="file" id="csvFileInput" accept=".csv,text/csv">
              <i class="ri-file-excel-2-line"></i>
              <p class="mb-1 fw-semibold" style="font-size:14px">Drop your CSV file here</p>
              <p class="text-muted mb-0" style="font-size:12px">or click to browse — CSV files only</p>
            </div>
            <div id="csvFileName" class="mt-2 text-muted" style="font-size:12px;display:none">
              <i class="ri-file-line text-success"></i> <span id="csvFileNameText"></span>
            </div>
          </div>

          {{-- Step B: Preview --}}
          <div id="importStepPreview" style="display:none">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div>
                <strong style="font-size:13px">Preview</strong>
                <span class="ms-2 text-muted" style="font-size:12px">Showing first 5 rows of <strong id="importTotalCount">0</strong> data rows</span>
              </div>
              <a href="#" id="resetCsvBtn" class="btn btn-sm btn-outline-secondary" style="font-size:12px">
                <i class="ri-refresh-line"></i> Choose different file
              </a>
            </div>
            <div class="table-responsive" style="max-height:200px;overflow-y:auto;border:1px solid #dee2e6;border-radius:6px;">
              <table class="table table-sm table-bordered mb-0" id="importPreviewTable">
                <thead id="importPreviewHead"></thead>
                <tbody id="importPreviewBody"></tbody>
              </table>
            </div>
            <div class="alert alert-success border-0 mt-3 py-2 px-3 mb-0 d-flex align-items-center gap-2"
                 style="font-size:13px;border-radius:8px;" id="importConfirmBanner">
              <i class="ri-question-line fs-5 text-success"></i>
              <span>Are you sure you want to import <strong><span id="importConfirmCount">0</span> items</strong>?</span>
            </div>
          </div>

          {{-- Step C: Importing progress --}}
          <div id="importStepProgress" style="display:none">
            <div class="text-center mb-3">
              <i class="ri-loader-4-line text-success" style="font-size:40px;animation:spin 1s linear infinite"></i>
              <p class="mt-2 mb-0 fw-semibold" style="font-size:14px">Importing products…</p>
              <p class="text-muted" style="font-size:12px" id="importProgressText">0 of 0 done</p>
            </div>
            <div class="import-progress-bar mb-2">
              <div class="bar" id="importBarFill"></div>
            </div>
            <div id="importLog" style="max-height:160px;overflow-y:auto;font-size:11px;background:#f8f9fa;border-radius:6px;padding:8px;border:1px solid #dee2e6;"></div>
          </div>

          {{-- Step D: Done --}}
          <div id="importStepDone" style="display:none">
            <div class="text-center py-3">
              <i class="ri-checkbox-circle-line text-success" style="font-size:52px"></i>
              <h5 class="mt-2">Import Complete!</h5>
              <p class="text-muted mb-1" style="font-size:13px" id="importDoneSummary"></p>
              <p class="text-muted" style="font-size:12px">The page will reload shortly to reflect all changes.</p>
            </div>
          </div>

        </div>{{-- /iTab2 --}}
      </div>

      <div class="modal-footer" style="justify-content:space-between;padding:10px 20px 14px;">
        <div>
          <a href="#" class="btn btn-sm btn-outline-secondary" id="importTabBackBtn" style="display:none;font-size:12px">
            <i class="ri-arrow-left-s-line"></i> Back
          </a>
        </div>
        <div class="d-flex gap-2">
          <a href="#" class="btn btn-secondary btn-sm" id="cancelImportBtn" style="font-size:12px">
            <i class="ri-close-line"></i> Cancel
          </a>
          <a href="#" class="btn btn-primary btn-sm" id="importNextBtn" style="font-size:12px">
            Next <i class="ri-arrow-right-s-line"></i>
          </a>
          <a href="#" class="btn btn-success btn-sm d-none" id="submitImportBtn" style="font-size:12px">
            <i class="ri-upload-2-line"></i> Start Import
          </a>
        </div>
      </div>

    </div>
  </div>
</div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     SINGLE DELETE MODAL
══════════════════════════════════════════════════════════════════════ --}}
<section>
<div class="modal fade" id="singleDeleteDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:350px;margin:1.75rem auto;">
    <div class="modal-content">
      <div class="modal-body text-center pb-4">
        <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
        <form action="#" method="post" id="singleDeleteDataForm">
          @csrf
          <h4 class="mt-2">Are you sure you want to delete<br><span id="singleDisplayDeleteLabel" class="text-danger"></span>?</h4>
          <h5>You won't be able to revert this!</h5>
          <input type="hidden" id="singleDeleteId" name="id">
          <input type="hidden" id="singleDeleteRow">
          <a href="#" class="btn btn-danger me-2 mt-3" id="submitSingleDeleteDataBtn">Yes, Delete it</a>
          <a href="#" class="btn btn-info mt-3"         id="keepSingleDataBtn">No, Keep it</a>
        </form>
      </div>
    </div>
  </div>
</div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     BULK DELETE MODAL
══════════════════════════════════════════════════════════════════════ --}}
<section>
<div class="modal fade" id="multipleDeleteDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:350px;margin:1.75rem auto;">
    <div class="modal-content">
      <div class="modal-body text-center pb-4">
        <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
        <form action="#" method="post" id="multipleDeleteDataForm">
          @csrf
          <h4 class="mt-2">Are you sure you want to delete <span id="multipleDisplayDeleteLabel"></span>?</h4>
          <h5>You won't be able to revert this!</h5>
          <input type="hidden" id="multipleDeleteIds" name="ids[]">
          <input type="hidden" id="multipleDeleteRows">
          <a href="#" class="btn btn-danger me-2 mt-3" id="submitMultipleDeleteDataBtn">Yes, Delete them</a>
          <a href="#" class="btn btn-info mt-3"         id="keepMultipleDataBtn">No, Keep them</a>
        </form>
      </div>
    </div>
  </div>
</div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     CHANGE SUPPLIER MODAL (bulk)
══════════════════════════════════════════════════════════════════════ --}}
<section>
<div class="modal fade" id="changeSupplierModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:420px;margin:1.75rem auto;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-truck-line me-1"></i> Change Supplier</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-3" style="font-size:13px">
          Applies to <strong><span id="supplierChangeCount">0</span> selected product(s)</strong>.
        </p>
        <form action="#" id="changeSupplierForm">
          @csrf
          <input type="hidden" id="supplierChangeIds">
          <input type="hidden" id="supplierChangeRows">
          <div class="mb-3">
            <label class="form-label">New Supplier <span class="text-danger">*</span></label>
            <select class="form-select" id="newSupplierValue" name="supplier">
              <option value="">— Select Supplier —</option>
              @foreach($suppliers as $sup)
                <option value="{{ $sup }}">{{ $sup }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-1">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="clearSupplierCheck">
              <label class="form-check-label text-danger" for="clearSupplierCheck">
                Clear supplier field instead (set to blank)
              </label>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <a href="#" class="btn btn-secondary" id="cancelChangeSupplierBtn">Cancel</a>
        <a href="#" class="btn btn-warning text-dark" id="submitChangeSupplierBtn">
          <i class="ri-truck-line me-1"></i> Apply to Selected
        </a>
      </div>
    </div>
  </div>
</div>
</section>

{{-- ══════════════════════════════════════════════════════════════════════
     EDIT MODAL
══════════════════════════════════════════════════════════════════════ --}}
<section>
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title"><i class="ri-edit-box-line me-1"></i> Update Base Product</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <form action="#" method="post" id="editDataForm">
        @csrf
        <input type="hidden" name="id"      id="editId">
        <input type="hidden" name="editrow" id="editRow">

        <div class="section-label">Core Identity</div>
        <div class="row">
          <div class="col-md-8 mb-3">
            <label class="form-label">Product Name <span class="text-danger">*</span></label>
            <input class="form-control" type="text" name="name" id="editName" autocomplete="off" required />
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Internal Code</label>
            <input class="form-control" type="text" name="internal_code" id="editInternalCode" autocomplete="off" />
            <div class="form-text">Unique — import match key</div>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea class="form-control" name="description" id="editDescription" rows="2"></textarea>
        </div>
        <div class="row">
          <div class="col-md-3 mb-3">
            <label class="form-label">Brand</label>
            <input class="form-control" type="text" name="brand" id="editBrand" autocomplete="off" />
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Supplier</label>
            <select class="form-select" name="supplier" id="editSupplier">
              <option value="">— Select Supplier —</option>
              @foreach($suppliers as $sup)
                <option value="{{ $sup }}">{{ $sup }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Manufacturer</label>
            <input class="form-control" type="text" name="manufacturer" id="editManufacturer" autocomplete="off" />
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Country of Origin</label>
            <input class="form-control" type="text" name="country_of_origin" id="editCountryOfOrigin" maxlength="2" autocomplete="off" />
            <div class="form-text">2-letter ISO code</div>
          </div>
        </div>

        <div class="section-label">Default Pricing <small class="text-muted fw-normal">(MWK — branches can override)</small></div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Default Selling Price</label>
            <div class="input-group">
              <span class="input-group-text" style="font-size:12px;color:#6c757d">MWK</span>
              <input class="form-control" type="number" step="0.01" min="0"
                     name="default_selling_price" id="editSellingPrice" placeholder="0.00" />
            </div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Default Cost Price</label>
            <div class="input-group">
              <span class="input-group-text" style="font-size:12px;color:#6c757d">MWK</span>
              <input class="form-control" type="number" step="0.01" min="0"
                     name="default_cost_price" id="editCostPrice" placeholder="0.00" />
            </div>
          </div>
        </div>

        <div class="section-label">Physical / Measurement</div>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Unit of Measure <span class="text-danger">*</span></label>
            <select class="form-select" name="unit_of_measure" id="editUnitOfMeasure" required>
              <option value="Each">Each</option>
              <option value="kg">Kilogram (kg)</option>
              <option value="g">Gram (g)</option>
              <option value="Litre">Litre</option>
              <option value="ml">Millilitre (ml)</option>
              <option value="Box">Box</option>
              <option value="Carton">Carton</option>
              <option value="Pack">Pack</option>
              <option value="Pair">Pair</option>
              <option value="Dozen">Dozen</option>
              <option value="Bag">Bag</option>
              <option value="Bottle">Bottle</option>
              <option value="Metre">Metre</option>
              <option value="Service">Service (N/A)</option>
            </select>
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Weight (kg)</label>
            <input class="form-control" type="number" step="0.0001" min="0" name="weight_kg" id="editWeightKg" />
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Volume (litres)</label>
            <input class="form-control" type="number" step="0.0001" min="0" name="volume_litres" id="editVolumeLitres" />
          </div>
        </div>
        <div class="mb-3">
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="is_product" id="edit-is-product-yes" value="1">
            <label class="form-check-label" for="edit-is-product-yes">Product (stock tracked)</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="is_product" id="edit-is-product-no" value="0">
            <label class="form-check-label" for="edit-is-product-no">Service (no stock)</label>
          </div>
        </div>

        <div class="section-label">MRA EIS Classification</div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">MRA Product Code</label>
            <input class="form-control" type="text" name="mra_product_code" id="editMraProductCode" autocomplete="off" />
            <div class="form-text">UN/SPSC → <code>invoiceLineItems[].productCode</code></div>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">MRA Tax Rate ID <span class="text-danger">*</span></label>
            <select class="form-select" name="mra_tax_rate_id" id="editMraTaxRateId" required>
              <option value="">— Select —</option>
              <option value="A">A — VAT-A (Standard 16.5%)</option>
              <option value="E">E — Exempt (zero-rated)</option>
              <option value="TL">TL — Tourism Levy (1%)</option>
            </select>
          </div>
        </div>
        <div class="mb-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_vat_exempt_by_nature" id="editIsVatExempt" value="1">
            <label class="form-check-label" for="editIsVatExempt">
              VAT Exempt by Nature
              <small class="text-muted">(always exempt — use rate <code>E</code>)</small>
            </label>
          </div>
        </div>

        <div class="section-label">Internal Categorisation</div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Category</label>
            <select class="form-select" name="category_id" id="editCategoryId">
              <option value="">— Select Category —</option>
              @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->category }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Subcategory</label>
            <input class="form-control" type="text" name="subcategory" id="editSubcategory" autocomplete="off" />
          </div>
        </div>
        <div class="mb-2">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" id="editIsActive" value="1">
            <label class="form-check-label" for="editIsActive">Active (can be assigned to branches)</label>
          </div>
        </div>

        <hr class="mt-3 mb-2">
        <a href="#" class="btn btn-primary float-end mt-2 mb-2" id="submitUpdateDataBtn">Submit</a>
        <a href="#" class="btn btn-secondary float-end mt-2 mb-2 mx-2" id="cancelEditDataBtn">Clear</a>
      </form>
    </div>
  </div></div>
</div>
</section>

{{-- Spinner keyframe --}}
<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

@endsection

@section('scripts')
<script>
$(document).ready(function () {

    toastr.options = {
        closeButton: true, progressBar: true,
        showMethod: 'slideDown', timeOut: 5000, allowHtml: true
    };

    // ── Categories map (id → name) ────────────────────────────────────────
    var categoriesMap = {!! json_encode($categories->pluck('category','id')) !!};

    // ── Ajax error handler ────────────────────────────────────────────────
    function handleAjaxError(xhr, status) {
        if (status === 'timeout') {
            toastr.error('The request timed out. Please check your connection and try again.', 'Timeout Error');
        } else if (xhr.status === 0) {
            toastr.error('Unable to connect. Please check your connection and try again.', 'Connection Error');
        } else if (xhr.status === 422) {
            var errors = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : {};
            var msg = '';
            $.each(errors, function (k, v) { msg += v + '\n'; });
            toastr.error(msg || 'Validation failed.', 'Validation Errors');
        } else if (xhr.status === 500) {
            toastr.error('Server error occurred. Please refresh and try again.', 'Server Error');
        } else {
            toastr.error('Unspecified error occurred. Try again later.', 'Unspecified Error');
        }
    }

    function fmtPrice(val) {
        if (val === null || val === '' || val === undefined) return '—';
        var n = parseFloat(val);
        return isNaN(n) ? '—' : n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // ── Build <tr> HTML ───────────────────────────────────────────────────
    function buildRow(p) {
        var taxClass = p.mra_tax_rate_id === 'A'  ? 'bg-danger'
                     : p.mra_tax_rate_id === 'E'  ? 'bg-secondary'
                     : p.mra_tax_rate_id === 'TL' ? 'bg-warning text-dark'
                     : p.mra_tax_rate_id           ? 'bg-info' : '';
        var taxBadge    = p.mra_tax_rate_id
            ? '<span class="badge tax-badge ' + taxClass + '">' + p.mra_tax_rate_id + '</span>'
            : '<span class="text-muted" style="font-size:12px">—</span>';
        var typeBadge   = p.is_product == 1
            ? '<span class="badge bg-primary">Product</span>'
            : '<span class="badge bg-warning text-dark">Service</span>';
        var statusBadge = p.is_active == 1
            ? '<span class="badge bg-success">Active</span>'
            : '<span class="badge bg-danger">Inactive</span>';
        var spCell = (p.default_selling_price !== null && p.default_selling_price !== '')
            ? '<span class="price-cell">' + fmtPrice(p.default_selling_price) + '</span>'
            : '<span class="text-muted" style="font-size:12px">—</span>';
        var cpCell = (p.default_cost_price !== null && p.default_cost_price !== '')
            ? '<span style="font-size:12px;color:#6c757d">' + fmtPrice(p.default_cost_price) + '</span>'
            : '<span class="text-muted" style="font-size:12px">—</span>';
        var catName = (p.category_id && categoriesMap[p.category_id]) ? categoriesMap[p.category_id] : '—';

        return `
            <tr id="${p.row}" data-category-id="${p.category_id||''}" data-supplier="${p.supplier||''}">
                <td><input type="checkbox" class="selectRow" value="${p.id}" data-row-id="${p.row}">&nbsp;${p.name}</td>
                <td>${p.internal_code || '—'}</td>
                <td>${p.unit_of_measure}</td>
                <td>${cpCell}</td>
                <td>${spCell}</td>
                <td>${p.supplier || '—'}</td>
                <td>${p.brand || '—'}</td>
                <td>${catName}</td>
                <td>${taxBadge}</td>
                <td>${typeBadge}</td>
                <td>${statusBadge}</td>
                <td>
                    <a href="#" class="editDataBtn"
                       editId="${p.id}" editRow="${p.row}" editName="${p.name}"
                       editDescription="${p.description||''}" editBrand="${p.brand||''}"
                       editSupplier="${p.supplier||''}" editManufacturer="${p.manufacturer||''}"
                       editCountryOfOrigin="${p.country_of_origin||''}" editInternalCode="${p.internal_code||''}"
                       editUnitOfMeasure="${p.unit_of_measure}"
                       editWeightKg="${p.weight_kg!==null?p.weight_kg:''}"
                       editVolumeLitres="${p.volume_litres!==null?p.volume_litres:''}"
                       editIsProduct="${p.is_product}"
                       editDefaultSellingPrice="${p.default_selling_price!==null?p.default_selling_price:''}"
                       editDefaultCostPrice="${p.default_cost_price!==null?p.default_cost_price:''}"
                       editMraProductCode="${p.mra_product_code||''}" editMraTaxRateId="${p.mra_tax_rate_id||''}"
                       editIsVatExemptByNature="${p.is_vat_exempt_by_nature}"
                       editCategoryId="${p.category_id||''}" editSubcategory="${p.subcategory||''}"
                       editIsActive="${p.is_active}">
                       <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px"></i>
                    </a>
                    <a href="#" class="deleteDataBtn"
                       deleteLabel="${p.name}" deleteId="${p.id}" deleteRow="${p.row}">
                       <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px"></i>
                    </a>
                </td>
            </tr>`;
    }

    // ── Checkbox helpers ──────────────────────────────────────────────────
    function collectNewFormData() {
        var fd = $('#newDataForm').serializeArray().filter(function (i) {
            return i.name !== 'is_vat_exempt_by_nature' && i.name !== 'is_active';
        });
        fd.push({ name: 'is_vat_exempt_by_nature', value: $('#new-is-vat-exempt').prop('checked') ? 1 : 0 });
        fd.push({ name: 'is_active',               value: $('#new-is-active').prop('checked')     ? 1 : 0 });
        return fd;
    }
    function collectEditFormData() {
        var fd = $('#editDataForm').serializeArray().filter(function (i) {
            return i.name !== 'is_vat_exempt_by_nature' && i.name !== 'is_active';
        });
        fd.push({ name: 'is_vat_exempt_by_nature', value: $('#editIsVatExempt').prop('checked') ? 1 : 0 });
        fd.push({ name: 'is_active',               value: $('#editIsActive').prop('checked')    ? 1 : 0 });
        return fd;
    }

    // ── Bulk toolbar ──────────────────────────────────────────────────────
    function updateSelectedCount() {
        var count = $('.selectRow:checked').length;
        $('#selectedCount').text(count);
        count > 0 ? $('#bulkActionsBar').css('display','flex') : $('#bulkActionsBar').hide();
    }

    // ════════════════════════════════════════════════════════════════════════
    //  FILTER ENGINE
    // ════════════════════════════════════════════════════════════════════════
    function applyFilters() {
        var catVal = $('#filterCategory').val();
        var supVal = $('#filterSupplier').val();
        var visible = 0;

        $('#tbody tr').each(function () {
            var rowCat = $(this).data('category-id') ? String($(this).data('category-id')) : '';
            var rowSup = $(this).data('supplier')    ? String($(this).data('supplier'))    : '';
            var show   = true;
            if (catVal && rowCat !== catVal) show = false;
            if (supVal && rowSup !== supVal) show = false;
            if (show) { $(this).show(); visible++; } else { $(this).hide(); }
        });

        if (catVal || supVal) {
            $('#filterInfo').show();
            $('#filterInfoCount').text(visible);
        } else {
            $('#filterInfo').hide();
        }

        if (window._dt) window._dt.draw(false);
    }

    $('#filterCategory, #filterSupplier').on('change', applyFilters);
    $('#clearFilterBtn').on('click', function (e) {
        e.preventDefault();
        $('#filterCategory, #filterSupplier').val('');
        applyFilters();
    });

    // ════════════════════════════════════════════════════════════════════════
    //  ADD MODAL TAB ENGINE
    // ════════════════════════════════════════════════════════════════════════
    var newCurrentTab = 1, newTotalTabs = 3;

    function newTabGoto(n) {
        newCurrentTab = n;
        $('#newDataModal .tab-pane').removeClass('active');
        $('#newTab' + n).addClass('active');
        $('#newModalTabStrip .nav-tab-item').each(function () {
            var t = parseInt($(this).data('tab').replace('newTab', ''));
            $(this).removeClass('active done');
            if (t === n) $(this).addClass('active');
            if (t < n)  $(this).addClass('done');
        });
        $('#newTabDots span').each(function () {
            $(this).removeClass('active');
            if (parseInt($(this).data('dot')) === n) $(this).addClass('active');
        });
        n > 1 ? $('#newTabBackBtn').removeClass('d-none') : $('#newTabBackBtn').addClass('d-none');
        if (n === newTotalTabs) {
            $('#newTabNextBtn').addClass('d-none'); $('#submitDataBtn').removeClass('d-none');
        } else {
            $('#newTabNextBtn').removeClass('d-none'); $('#submitDataBtn').addClass('d-none');
        }
    }

    function resetNewModal() {
        $('#newDataForm')[0].reset();
        $('#new-unit-of-measure').val('Each');
        $('#new-is-product-yes').prop('checked', true);
        $('#new-is-active').prop('checked', true);
        $('#new-is-vat-exempt').prop('checked', false);
        $('#new-mra-tax-rate-id').val('A');
        newTabGoto(1);
    }

    $('#newModalTabStrip').on('click', '.nav-tab-item', function () {
        var target = parseInt($(this).data('tab').replace('newTab', ''));
        if (target <= newCurrentTab) newTabGoto(target);
    });
    $('#newTabNextBtn').on('click', function (e) {
        e.preventDefault();
        if (newCurrentTab === 1 && !$('#new-name').val().trim()) {
            toastr.warning('Product name is required before continuing.', 'Required');
            $('#new-name').focus(); return;
        }
        if (newCurrentTab < newTotalTabs) newTabGoto(newCurrentTab + 1);
    });
    $('#newTabBackBtn').on('click', function (e) {
        e.preventDefault();
        if (newCurrentTab > 1) newTabGoto(newCurrentTab - 1);
    });

    // ════════════════════════════════════════════════════════════════════════
    //  IMPORT MODAL ENGINE
    // ════════════════════════════════════════════════════════════════════════
    var importCurrentTab = 1;
    var parsedCsvRows    = [];
    var IMPORT_STORAGE_KEY = 'bp_import_queue';

    function importTabGoto(n) {
        importCurrentTab = n;
        $('#importModal .tab-pane').removeClass('active');
        $('#iTab' + n).addClass('active');
        $('#importModalTabStrip .nav-tab-item').each(function () {
            var t = parseInt($(this).data('itab').replace('iTab', ''));
            $(this).removeClass('active');
            if (t === n) $(this).addClass('active');
        });
        n > 1 ? $('#importTabBackBtn').show() : $('#importTabBackBtn').hide();
        if (n === 2 && parsedCsvRows.length > 0) {
            $('#importNextBtn').addClass('d-none');
            $('#submitImportBtn').removeClass('d-none');
        } else {
            $('#importNextBtn').removeClass('d-none');
            $('#submitImportBtn').addClass('d-none');
        }
    }

    function resetImportModal() {
        importCurrentTab = 1; parsedCsvRows = [];
        $('#csvFileInput').val(''); $('#csvFileName').hide();
        $('#importStepUpload').show(); $('#importStepPreview').hide();
        $('#importStepProgress').hide(); $('#importStepDone').hide();
        $('#importPreviewHead').empty(); $('#importPreviewBody').empty();
        $('#importLog').empty(); $('#importBarFill').css('width','0');
        $('#importNextBtn').removeClass('d-none'); $('#submitImportBtn').addClass('d-none');
        importTabGoto(1);
    }

    $('#importModalTabStrip').on('click', '.nav-tab-item', function () {
        var t = parseInt($(this).data('itab').replace('iTab', ''));
        if (t <= importCurrentTab) importTabGoto(t);
    });

    $('#importBtn').on('click', function (e) {
        e.preventDefault(); resetImportModal(); $('#importModal').modal('show');
    });
    $('#cancelImportBtn').on('click', function (e) {
        e.preventDefault(); localStorage.removeItem(IMPORT_STORAGE_KEY); resetImportModal(); $('#importModal').modal('hide');
    });
    $('#importModal').on('hidden.bs.modal', function () { localStorage.removeItem(IMPORT_STORAGE_KEY); resetImportModal(); });

    $('#importTabBackBtn').on('click', function (e) {
        e.preventDefault(); importTabGoto(1);
    });

    $('#importNextBtn').on('click', function (e) {
        e.preventDefault();
        if (importCurrentTab === 1) { importTabGoto(2); return; }
    });

    // CSV template download
    $('#downloadTemplateBtn').on('click', function (e) {
        e.preventDefault();
        var header = 'name,internal_code,unit_of_measure,default_cost_price,default_selling_price,supplier,brand,category,subcategory,description,manufacturer,country_of_origin,weight_kg,volume_litres,mra_tax_rate_id,mra_product_code,is_product,is_vat_exempt_by_nature,is_active';
        var example = 'Cooking Oil 2L,OIL-001,Each,1500.00,2000.00,Rab Processors Ltd,Soya,Food & Beverages,Cooking Oils,Pure soya cooking oil,Rab Processors,MW,2.0000,2.0000,A,50201700,1,0,1';
        var blob = new Blob([header + '\n' + example], { type: 'text/csv;charset=utf-8;' });
        var url  = URL.createObjectURL(blob);
        var a    = document.createElement('a'); a.href = url; a.download = 'base_products_template.csv'; a.click();
        URL.revokeObjectURL(url);
    });

    // Drag & drop
    var dz = document.getElementById('dropZone');
    dz.addEventListener('dragover',  function(e) { e.preventDefault(); dz.classList.add('drag-over'); });
    dz.addEventListener('dragleave', function()  { dz.classList.remove('drag-over'); });
    dz.addEventListener('drop', function(e) {
        e.preventDefault(); dz.classList.remove('drag-over');
        var file = e.dataTransfer.files[0];
        if (file) processCSVFile(file);
    });

    $('#csvFileInput').on('change', function () {
        if (this.files && this.files[0]) processCSVFile(this.files[0]);
    });

    function processCSVFile(file) {
        if (!file.name.match(/\.csv$/i)) { toastr.error('Please select a valid CSV file.', 'Invalid File'); return; }
        $('#csvFileNameText').text(file.name); $('#csvFileName').show();
        var reader = new FileReader();
        reader.onload = function (e) {
            var text = e.target.result;
            parseCSV(text);
        };
        reader.readAsText(file, 'UTF-8');
    }

    function parseCSV(text) {
        var lines = text.split(/\r?\n/).filter(function(l) { return l.trim() !== ''; });
        if (lines.length < 2) { toastr.error('CSV has no data rows.', 'Empty File'); return; }

        var headers = lines[0].split(',').map(function(h) { return h.trim().replace(/^"|"$/g,''); });
        parsedCsvRows = [];

        for (var i = 1; i < lines.length; i++) {
            var cols = splitCSVLine(lines[i]);
            if (cols.length === 0) continue;
            var row = {};
            for (var j = 0; j < headers.length; j++) {
                row[headers[j]] = (cols[j] !== undefined) ? cols[j].trim().replace(/^"|"$/g,'') : '';
            }
            if (!row['name'] || row['name'].trim() === '') continue;
            parsedCsvRows.push(row);
        }

        if (parsedCsvRows.length === 0) { toastr.error('No valid data rows found in CSV.', 'Empty Data'); return; }

        // Save to localStorage
        try { localStorage.setItem(IMPORT_STORAGE_KEY, JSON.stringify(parsedCsvRows)); } catch(ex) { toastr.warning('LocalStorage full; will import directly.','Warning'); }

        showImportPreview(headers, parsedCsvRows);
        importTabGoto(2);
        $('#importStepUpload').hide(); $('#importStepPreview').show();
        $('#importNextBtn').addClass('d-none'); $('#submitImportBtn').removeClass('d-none');
        $('#importTotalCount').text(parsedCsvRows.length);
        $('#importConfirmCount').text(parsedCsvRows.length);
    }

    function splitCSVLine(line) {
        var result = [], current = '', inQuotes = false;
        for (var i = 0; i < line.length; i++) {
            var ch = line[i];
            if (ch === '"') { inQuotes = !inQuotes; }
            else if (ch === ',' && !inQuotes) { result.push(current); current = ''; }
            else { current += ch; }
        }
        result.push(current);
        return result;
    }

    function showImportPreview(headers, rows) {
        var previewHeaders = ['name','internal_code','unit_of_measure','default_cost_price','default_selling_price','supplier','category'];
        var thead = '<tr>' + previewHeaders.map(function(h){ return '<th>'+h+'</th>'; }).join('') + '</tr>';
        $('#importPreviewHead').html(thead);
        var tbody = '';
        var limit = Math.min(5, rows.length);
        for (var i = 0; i < limit; i++) {
            var r = rows[i];
            tbody += '<tr>' + previewHeaders.map(function(h){ return '<td>'+(r[h]||'—')+'</td>'; }).join('') + '</tr>';
        }
        $('#importPreviewBody').html(tbody);
    }

    $('#resetCsvBtn').on('click', function(e) {
        e.preventDefault();
        parsedCsvRows = [];
        $('#csvFileInput').val(''); $('#csvFileName').hide();
        $('#importStepPreview').hide(); $('#importStepUpload').show();
        $('#importNextBtn').removeClass('d-none'); $('#submitImportBtn').addClass('d-none');
        localStorage.removeItem(IMPORT_STORAGE_KEY);
    });

    // Start import
    $('#submitImportBtn').on('click', function(e) {
        e.preventDefault();
        var queue = [];
        try { var stored = localStorage.getItem(IMPORT_STORAGE_KEY); queue = stored ? JSON.parse(stored) : parsedCsvRows; }
        catch(ex) { queue = parsedCsvRows; }
        if (!queue || queue.length === 0) { toastr.error('No data to import.','Error'); return; }
        runImport(queue);
    });

    function runImport(queue) {
        $('#importStepPreview').hide();
        $('#importStepProgress').show();
        $('#submitImportBtn').addClass('d-none');
        $('#importTabBackBtn').hide();
        $('#cancelImportBtn').prop('disabled', true);

        var total = queue.length, done = 0, succeeded = 0, failed = 0;
        $('#importProgressText').text('0 of ' + total + ' done');

        function importNext(index) {
            if (index >= queue.length) {
                // All done
                localStorage.removeItem(IMPORT_STORAGE_KEY);
                $('#importStepProgress').hide();
                $('#importStepDone').show();
                $('#importDoneSummary').text(succeeded + ' imported successfully, ' + failed + ' skipped/failed.');
                $('#cancelImportBtn').prop('disabled', false).text('Close');
                setTimeout(function() { location.reload(); }, 3000);
                return;
            }

            var row = queue[index];
            var payload = {
                name:                    row.name || '',
                internal_code:           row.internal_code           || '',
                unit_of_measure:         row.unit_of_measure         || 'Each',
                default_cost_price:      row.default_cost_price      || '',
                default_selling_price:   row.default_selling_price   || '',
                supplier:                row.supplier                || '',
                brand:                   row.brand                   || '',
                category_name:           row.category                || '',
                subcategory:             row.subcategory             || '',
                description:             row.description             || '',
                manufacturer:            row.manufacturer            || '',
                country_of_origin:       row.country_of_origin       || '',
                weight_kg:               row.weight_kg               || '',
                volume_litres:           row.volume_litres           || '',
                mra_tax_rate_id:         row.mra_tax_rate_id         || 'A',
                mra_product_code:        row.mra_product_code        || '',
                is_product:              (row.is_product !== undefined && row.is_product !== '') ? row.is_product : 1,
                is_vat_exempt_by_nature: row.is_vat_exempt_by_nature || 0,
                is_active:               (row.is_active !== undefined && row.is_active !== '') ? row.is_active : 1,
                _token: '{{ csrf_token() }}'
            };

            $.ajax({
                type: 'POST', url: '{{ route("retail.operations.baseproducts.import.row") }}',
                data: payload, timeout: 30000,
                success: function(data) {
                    done++;
                    if (data.status === 201) {
                        succeeded++;
                        appendLog('<span class="text-success">✓</span> Row ' + (index+1) + ': ' + row.name + ' imported.');
                        if (data.product) {
                            window._dt && window._dt.row.add($(buildRow(data.product)));
                        }
                    } else {
                        failed++;
                        appendLog('<span class="text-danger">✗</span> Row ' + (index+1) + ': ' + (data.error || 'Failed') + ' (' + row.name + ')');
                    }
                },
                error: function() {
                    done++; failed++;
                    appendLog('<span class="text-danger">✗</span> Row ' + (index+1) + ': Network error (' + row.name + ')');
                },
                complete: function() {
                    var pct = Math.round((done / total) * 100);
                    $('#importBarFill').css('width', pct + '%');
                    $('#importProgressText').text(done + ' of ' + total + ' done');
                    importNext(index + 1);
                }
            });
        }

        importNext(0);
    }

    function appendLog(msg) {
        var el = $('#importLog');
        el.append('<div>' + msg + '</div>');
        el.scrollTop(el[0].scrollHeight);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  DataTable
    // ════════════════════════════════════════════════════════════════════════
    function initDataTable() {
        var table = $('#maintable').DataTable({
            dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
            lengthChange: true,
            lengthMenu: [[100, 250, 500, -1], [100, 250, 500, 'All']],
            fixedColumns: { leftColumns: 1 },
            scrollX: true,
            buttons: [
                { extend: 'excelHtml5', title: @json($maintableTitle), exportOptions: { columns: ':visible:not(:last-child)' } },
                { extend: 'csvHtml5',   title: @json($maintableTitle), exportOptions: { columns: ':visible:not(:last-child)' } },
                {
                    extend: 'pdfHtml5', title: @json($maintableTitle),
                    exportOptions: { columns: ':visible:not(:last-child)' },
                    customize: function (doc) {
                        doc.content[1].table.widths = Array(doc.content[1].table.body[0].length+1).join('*').split('');
                    }
                }
            ]
        });

        window._dt = table;
        table.buttons().container().appendTo($('#buttonsModal .buttons'));

        // ── Open modals ────────────────────────────────────────────────────
        $('#newDataBtn').on('click', function (e) {
            e.preventDefault(); resetNewModal(); $('#newDataModal').modal('show');
        });
        $('#newDataModal').on('hidden.bs.modal', function () { resetNewModal(); });
        $('#infoBtn').on('click',         function (e) { e.preventDefault(); $('#infoModal').modal('show'); });
        $('#tableButtonsBtn').on('click', function (e) { e.preventDefault(); $('#buttonsModal').modal('show'); });

        // ══════════════════════════════════════════════════════════════════
        //  ADD
        // ══════════════════════════════════════════════════════════════════
        $('#submitDataBtn').on('click', function (e) {
            e.preventDefault();
            var self = $(this); self.prop('disabled', true);
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
            $.ajax({
                type: 'POST', url: '{{ route("retail.operations.baseproducts.insert") }}',
                data: collectNewFormData(), timeout: 60000,
                beforeSend: function () { $('#progressBar').show(); },
                complete:   function () { $('#progressBar').hide(); self.prop('disabled', false); },
                success: function (data) {
                    if (data.status === 201) {
                        toastr.success(data.success, 'Success');
                        table.row.add($(buildRow(data.product))).draw(false);
                        $('#newDataModal').modal('hide');
                    } else if (data.status === 422) {
                        toastr.error(data.error || 'Validation failed.', 'Error');
                    } else {
                        toastr.info('Unspecified error occurred.', 'Error');
                    }
                },
                error: handleAjaxError
            });
        });

        $('#cancelDataBtn').on('click', function (e) {
            e.preventDefault(); resetNewModal(); $('#newDataModal').modal('hide');
        });

        // ══════════════════════════════════════════════════════════════════
        //  SINGLE DELETE
        // ══════════════════════════════════════════════════════════════════
        $('#tbody').on('click', '.deleteDataBtn', function () {
            $('#singleDisplayDeleteLabel').text($(this).attr('deleteLabel'));
            $('#singleDeleteRow').val($(this).attr('deleteRow'));
            $('#singleDeleteId').val($(this).attr('deleteId'));
            $('#singleDeleteDataModal').modal('show');
        });
        $('#keepSingleDataBtn').on('click', function (e) {
            e.preventDefault(); toastr.info('Your data is safe', 'Great!'); $('#singleDeleteDataModal').modal('hide');
        });
        $('#submitSingleDeleteDataBtn').on('click', function (e) {
            e.preventDefault();
            var self = $(this); self.prop('disabled', true);
            var row  = $('#singleDeleteRow').val();
            var id   = $('#singleDeleteId').val();
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
            $.ajax({
                type: 'POST', url: '{{ route("retail.operations.baseproducts.delete") }}',
                data: { id: id, _token: '{{ csrf_token() }}' }, timeout: 60000,
                beforeSend: function () { $('#progressBar').show(); },
                complete:   function () { $('#progressBar').hide(); self.prop('disabled', false); },
                success: function (data) {
                    if (data.status === 201) {
                        toastr.success(data.success, 'Success');
                        $('#singleDeleteDataModal').modal('hide');
                        table.row('#' + row).remove().draw(false);
                        updateSelectedCount();
                    } else if (data.status === 422) {
                        toastr.error(data.error || 'Validation failed.', 'Error');
                    } else {
                        toastr.info('Unspecified error occurred.', 'Error');
                    }
                },
                error: handleAjaxError
            });
        });

        // ══════════════════════════════════════════════════════════════════
        //  EDIT
        // ══════════════════════════════════════════════════════════════════
        $('#tbody').on('click', '.editDataBtn', function () {
            var b = $(this);
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
            $('#editMraProductCode').val(b.attr('editMraProductCode'));
            $('#editMraTaxRateId').val(b.attr('editMraTaxRateId'));
            $('#editCategoryId').val(b.attr('editCategoryId'));
            $('#editSubcategory').val(b.attr('editSubcategory'));
            $('input[name="is_product"][value="' + b.attr('editIsProduct') + '"]').prop('checked', true);
            $('#editIsVatExempt').prop('checked', b.attr('editIsVatExemptByNature') == 1);
            $('#editIsActive').prop('checked',    b.attr('editIsActive') == 1);
            $('#editDataModal').modal('show');
        });

        $('#submitUpdateDataBtn').on('click', function (e) {
            e.preventDefault();
            var self = $(this); self.prop('disabled', true);
            var row  = $('#editRow').val();
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
            $.ajax({
                type: 'POST', url: '{{ route("retail.operations.baseproducts.update") }}',
                data: collectEditFormData(), timeout: 60000,
                beforeSend: function () { $('#progressBar').show(); },
                complete:   function () { $('#progressBar').hide(); self.prop('disabled', false); },
                success: function (data) {
                    if (data.status === 201) {
                        toastr.success(data.success, 'Success');
                        $('#editDataModal').modal('hide');
                        table.row('#' + row).remove();
                        table.row.add($(buildRow(data.product))).draw(false);
                        updateSelectedCount();
                    } else if (data.status === 422) {
                        toastr.error(data.error || 'Validation failed.', 'Error');
                    } else {
                        toastr.info('Unspecified error occurred.', 'Error');
                    }
                },
                error: handleAjaxError
            });
        });
        $('#cancelEditDataBtn').on('click', function (e) {
            e.preventDefault(); $('#editDataForm')[0].reset(); $('#editDataModal').modal('hide');
        });

        // ══════════════════════════════════════════════════════════════════
        //  BULK DELETE
        // ══════════════════════════════════════════════════════════════════
        $('#deleteSelectedBtn').on('click', function (e) {
            e.preventDefault();
            var selected = [], selectedRows = [];
            $('.selectRow:checked').each(function () {
                selected.push($(this).val()); selectedRows.push($(this).data('row-id'));
            });
            if (!selected.length) { toastr.warning('No products selected.', 'Warning'); return; }
            var c = selected.length;
            $('#multipleDisplayDeleteLabel').html('the selected <strong>' + c + ' product' + (c > 1 ? 's' : '') + '</strong>');
            $('#multipleDeleteIds').val(selected.join(','));
            $('#multipleDeleteRows').val(selectedRows.join(','));
            $('#multipleDeleteDataModal').modal('show');
        });
        $('#keepMultipleDataBtn').on('click', function (e) {
            e.preventDefault(); toastr.info('Your data is safe', 'Great!'); $('#multipleDeleteDataModal').modal('hide');
        });
        $('#submitMultipleDeleteDataBtn').on('click', function (e) {
            e.preventDefault();
            var self = $(this); self.prop('disabled', true);
            var ids  = $('#multipleDeleteIds').val().split(',');
            var rows = $('#multipleDeleteRows').val().split(',');
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
            $.ajax({
                type: 'POST', url: '{{ route("retail.operations.baseproducts.bulkdelete") }}',
                data: { ids: ids, _token: '{{ csrf_token() }}' }, timeout: 60000,
                beforeSend: function () { $('#progressBar').show(); },
                complete:   function () { $('#progressBar').hide(); self.prop('disabled', false); },
                success: function (data) {
                    if (data.status === 201) {
                        toastr.success(data.success, 'Success');
                        for (var i = 0; i < rows.length; i++) { table.row('#' + rows[i]).remove(); }
                        table.draw(false); updateSelectedCount();
                        $('#multipleDeleteDataModal').modal('hide');
                    } else if (data.status === 422) {
                        toastr.error(data.error || 'Validation failed.', 'Error');
                    } else {
                        toastr.info('Unspecified error occurred.', 'Error');
                    }
                },
                error: handleAjaxError
            });
        });

        // ══════════════════════════════════════════════════════════════════
        //  BULK ACTIVATE / DEACTIVATE — no page reload
        // ══════════════════════════════════════════════════════════════════
        function doBulkStatus(isActive) {
            var selected = [];
            $('.selectRow:checked').each(function () { selected.push($(this).val()); });
            if (!selected.length) return;
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
            $.ajax({
                type: 'POST', url: '{{ route("retail.operations.baseproducts.bulkstatus") }}',
                data: { ids: selected, is_active: isActive, _token: '{{ csrf_token() }}' }, timeout: 60000,
                beforeSend: function () { $('#progressBar').show(); },
                complete:   function () { $('#progressBar').hide(); },
                success: function (data) {
                    if (data.status === 201) {
                        toastr.success(data.success, 'Success');
                        $.each(data.products, function (i, p) {
                            table.row('#' + p.row).remove();
                            table.row.add($(buildRow(p)));
                        });
                        table.draw(false); updateSelectedCount();
                    } else {
                        toastr.error(data.error || 'Failed.', 'Error');
                    }
                },
                error: handleAjaxError
            });
        }
        $('#bulkActivateBtn').on('click',   function (e) { e.preventDefault(); doBulkStatus(1); });
        $('#bulkDeactivateBtn').on('click', function (e) { e.preventDefault(); doBulkStatus(0); });

        // ══════════════════════════════════════════════════════════════════
        //  CHANGE SUPPLIER (bulk)
        // ══════════════════════════════════════════════════════════════════
        $('#changeSupplierBtn').on('click', function (e) {
            e.preventDefault();
            var selected = [], selectedRows = [];
            $('.selectRow:checked').each(function () {
                selected.push($(this).val()); selectedRows.push($(this).data('row-id'));
            });
            if (!selected.length) return;
            $('#supplierChangeCount').text(selected.length);
            $('#supplierChangeIds').val(selected.join(','));
            $('#supplierChangeRows').val(selectedRows.join(','));
            $('#newSupplierValue').val('').prop('disabled', false);
            $('#clearSupplierCheck').prop('checked', false);
            $('#changeSupplierModal').modal('show');
        });
        $('#clearSupplierCheck').on('change', function () {
            $('#newSupplierValue').prop('disabled', $(this).prop('checked'));
            if ($(this).prop('checked')) $('#newSupplierValue').val('');
        });
        $('#cancelChangeSupplierBtn').on('click', function (e) {
            e.preventDefault(); $('#changeSupplierModal').modal('hide');
        });
        $('#submitChangeSupplierBtn').on('click', function (e) {
            e.preventDefault();
            var clearing  = $('#clearSupplierCheck').prop('checked');
            var supplier  = clearing ? '' : $('#newSupplierValue').val().trim();
            if (!clearing && supplier === '') {
                toastr.warning('Select a supplier or tick "Clear supplier".', 'Required'); return;
            }
            var self = $(this); self.prop('disabled', true);
            var ids  = $('#supplierChangeIds').val().split(',');
            var rows = $('#supplierChangeRows').val().split(',');
            $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
            $.ajax({
                type: 'POST', url: '{{ route("retail.operations.baseproducts.bulksupplier") }}',
                data: { ids: ids, supplier: supplier, _token: '{{ csrf_token() }}' }, timeout: 60000,
                beforeSend: function () { $('#progressBar').show(); },
                complete:   function () { $('#progressBar').hide(); self.prop('disabled', false); },
                success: function (data) {
                    if (data.status === 201) {
                        toastr.success(data.success, 'Success');
                        $.each(data.products, function (i, p) {
                            table.row('#' + p.row).remove();
                            table.row.add($(buildRow(p)));
                        });
                        table.draw(false); updateSelectedCount();
                        $('#changeSupplierModal').modal('hide');
                    } else {
                        toastr.error(data.error || 'Failed.', 'Error');
                    }
                },
                error: handleAjaxError
            });
        });

        // ── Select All ─────────────────────────────────────────────────────
        $('#selectAll').on('click', function () {
            $('.selectRow').prop('checked', this.checked);
            updateSelectedCount();
        });
        $('#tbody').on('click', '.selectRow', function () { updateSelectedCount(); });

    } // end initDataTable

    initDataTable();
});
</script>
@endsection