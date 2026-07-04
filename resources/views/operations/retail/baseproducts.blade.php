@extends('operations.retail.dashboard')
@section('content')

@php
    $categories = DB::connection('tenant')->table('categories')->orderBy('category')->get();

    $pref = DB::connection('tenant')
               ->table('user_filters')
               ->where('user_id', Auth::id())
               ->first();

    $savedCategoryId = $pref->category_id ?? null;
    $savedSupplierId = $pref->supplier_id  ?? null;

    $suppliers = collect();
    if ($savedCategoryId) {
        $suppliers = DB::connection('tenant')
                        ->table('suppliers')
                        ->where('status', 'active')
                        ->where('category', $savedCategoryId)
                        ->orderBy('name')
                        ->get(['id', 'name', 'category']);
    }

    // retail_base_products.supplier stores supplier ID (int)
    $products = collect();
    if ($savedCategoryId) {
        if ($savedSupplierId) {
            $products = DB::connection('tenant')
                           ->table('retail_base_products')
                           ->where('supplier', $savedSupplierId)
                           ->orderBy('name')
                           ->get();
        } else {
            $categorySupplierIds = DB::connection('tenant')
                                      ->table('suppliers')
                                      ->where('status', 'active')
                                      ->where('category', $savedCategoryId)
                                      ->pluck('id')
                                      ->toArray();
            if (!empty($categorySupplierIds)) {
                $products = DB::connection('tenant')
                               ->table('retail_base_products')
                               ->whereIn('supplier', $categorySupplierIds)
                               ->orderBy('name')
                               ->get();
            }
        }
    }

    // Resolve supplier names for display
    $supplierNamesMap = collect();
    if ($products->isNotEmpty()) {
        $supplierNamesMap = DB::connection('tenant')
            ->table('suppliers')
            ->whereIn('id', $products->pluck('supplier')->unique()->filter()->values())
            ->pluck('name', 'id');
    }

    $maintableTitle = 'Retail Base Products';
@endphp

<style>
/* ── DataTable export buttons ─────────────────────────────────────── */
.dt-buttons .btn { background:transparent !important; background-image:none !important; box-shadow:none !important; border-color:#5bc0de; color:#5bc0de; }
.dt-buttons .btn:hover { background:#5bc0de !important; color:#fff; }

/* ── Card chrome ─────────────────────────────────────────────────── */
.card-header { padding:0.5rem 1.5rem !important; background:linear-gradient(to right,#4B5EBD,#576CC0); color:#fff; border-radius:10px 10px 0 0 !important; flex-wrap:wrap; gap:8px; }
.card-body   { padding:0 1.5rem 1.5rem 1.5rem !important; }
.card        { border:none; box-shadow:0 4px 8px rgba(0,0,0,0.1); border-radius:10px; }
.card-header h4 { color:#fff; font-weight:600; margin-bottom:0; display:flex; align-items:center; }
.card-header .btn-light { height:28px; padding:0 10px; display:flex; align-items:center; justify-content:center; line-height:1; }
.card-header .btn-light:hover { background-color:#f8f9fa; transition:background-color 0.2s; }

/* ── Select-all checkbox ── */
.header-select-all { width:16px; height:16px; cursor:pointer; accent-color:#4B5EBD; border-radius:3px; margin-right:10px; flex-shrink:0; vertical-align:middle; }

/* ── Filter bar ─────────────────────────────────────────────────── */
.card-filter { background:#eef0f7; border-bottom:1px solid #d6daf0; padding:9px 1.5rem; display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.card-filter label { font-size:12px; font-weight:600; color:#4B5EBD; margin-bottom:0; white-space:nowrap; }
.card-filter select { font-size:12px; height:30px; padding:0 8px; border-radius:6px; border:1px solid #c8d0ed; background:#fff; min-width:160px; max-width:220px; }
.filter-divider { width:1px; height:22px; background:#c8d0ed; margin:0 4px; }

/* ── Bulk trigger ───────────────────────────────────────────────── */
#bulkTriggerBtn { font-size:12px; font-weight:700; margin-left:auto; height:28px; padding:0 12px; display:none; align-items:center; gap:5px; }
#bulkTriggerBtn.visible { display:flex !important; }

/* ── Table alignment ────────────────────────────────────────────── */
#maintable thead th, table.dataTable thead th { text-align:center !important; vertical-align:middle !important; }
#maintable thead th:first-child, table.dataTable thead th:first-child { text-align:left !important; }
#maintable tbody td, table.dataTable tbody td { text-align:center !important; vertical-align:middle !important; }
#maintable tbody td:first-child, table.dataTable tbody td:first-child { text-align:left !important; }

/* ── Prices & badges ────────────────────────────────────────────── */
.price-cell { font-size:12px; font-weight:600; color:#198754; }
.type-badge-product { font-size:10px; font-weight:600; background:#e8f5e9; color:#2d6a4f; border:1px solid #a5d6a7; border-radius:10px; padding:1px 7px; white-space:nowrap; }
.type-badge-service { font-size:10px; font-weight:600; background:#fff3e0; color:#e65100; border:1px solid #ffcc80; border-radius:10px; padding:1px 7px; white-space:nowrap; }

/* ── Modal header helpers ────────────────────────────────────────── */
.mh-blue   { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-green  { background:linear-gradient(135deg,#2d6a4f,#40916c); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-info   { background:linear-gradient(135deg,#0d6efd,#3b82f6); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-orange { background:linear-gradient(135deg,#fd7e14,#e8590c); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-danger { background:linear-gradient(135deg,#c0392b,#e74c3c); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-title  { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close  { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }

/* ── View modal ─────────────────────────────────────────────────── */
.view-grid { display:grid; grid-template-columns:1fr 1fr; gap:6px 14px; }
.view-item label { font-size:10px; font-weight:600; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:2px; }
.view-item .view-val { font-size:13px; color:#1e293b; font-weight:500; }
.view-item.full { grid-column:1/-1; }

/* ── Bulk actions modal ─────────────────────────────────────────── */
.bulk-section { background:#f8f9fa; border-radius:8px; padding:12px 14px; margin-bottom:12px; }
.bulk-section-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.8px; color:#6c757d; margin-bottom:10px; }

/* ── CSV wizard steps (mirrors Branch Products exactly) ─────────── */
.csv-step { display:none; }
.csv-step.active { display:block; }
.csv-step-indicator { display:flex; align-items:center; gap:0; margin-bottom:18px; }
.csi-step { display:flex; align-items:center; gap:6px; font-size:11px; font-weight:600; color:#94a3b8; }
.csi-step.active { color:#4B5EBD; }
.csi-step.done   { color:#059669; }
.csi-num { width:22px; height:22px; border-radius:50%; border:2px solid currentColor; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; flex-shrink:0; }
.csi-line { flex:1; height:1px; background:#dee2e6; margin:0 6px; }
.csv-preview-scroll { max-height:200px; overflow-y:auto; border:1px solid #dee2e6; border-radius:8px; margin-bottom:10px; }
.csv-preview-scroll::-webkit-scrollbar { width:8px; }
.csv-preview-scroll::-webkit-scrollbar-thumb { background:#c8d0ed; border-radius:8px; }
.csv-preview-scroll::-webkit-scrollbar-track { background:#f8f9fb; }
.csv-preview-row { padding:6px 10px; border-bottom:1px solid #f1f5f9; font-size:12px; display:flex; justify-content:space-between; align-items:center; gap:8px; }
.csv-preview-row:last-child { border-bottom:none; }
.csv-preview-row .cpr-name { flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.csv-preview-row .cpr-meta { flex-shrink:0; color:#94a3b8; font-size:11px; }

/* ── Skipped / failed names list ──────────────────────────────────── */
.import-skipped-list { max-height:140px; overflow-y:auto; background:#fff8f8; border:1px solid #fecaca; border-radius:6px; padding:8px 12px; margin-top:8px; text-align:left; }
.import-skipped-list li { font-size:12px; color:#7f1d1d; padding:1px 0; }

/* ── Invalid-on-parse banner (Step 3) ──────────────────────────────── */
.csv-invalid-banner { background:#fff8f8; border:1px solid #fecaca; border-radius:8px; padding:10px 12px; margin-top:10px; font-size:12px; color:#7f1d1d; display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }

/* ── Chunk progress bar (Step 4) ───────────────────────────────────── */
.csv-chunk-progress-track { background:#e9ecef; border-radius:6px; height:8px; overflow:hidden; margin:14px 0 6px; }
.csv-chunk-progress-fill { background:linear-gradient(to right,#4B5EBD,#576CC0); height:100%; width:0%; transition:width .25s ease; }
.csv-chunk-progress-label { font-size:11px; color:#6c757d; text-align:center; }

/* ── Spinner ────────────────────────────────────────────────────── */
@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
</style>

<div class="progress" id="progressBar" role="progressbar" style="height:8px;transform:rotate(180deg);display:none">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

  {{-- ── Card header ── --}}
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
      <input type="checkbox" id="selectAll" class="header-select-all">
      Base Products
    </h4>
    <div class="d-flex align-items-center" style="gap:4px;">
      <a href="#" class="btn btn-light text-success fs-16 mx-1" id="importBtn"       title="Import CSV"><i class="ri-file-excel-2-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="newDataBtn"      title="Add product"><i class="ri-add-circle-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn"         title="Info"><i class="ri-information-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download"><i class="ri-download-line"></i></a>
    </div>
  </div>

  {{-- ── Filter bar ── --}}
  <div class="card-filter">
    <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
          id="filterCategoryForm" style="display:contents;">
      @csrf
      <input type="hidden" name="user_id"     value="{{ Auth::id() }}">
      <input type="hidden" name="supplier_id" value="">
      <label>Category:</label>
      <select name="category_id" id="filterCategory"
              onchange="document.getElementById('filterCategoryForm').submit()">
        <option value="" hidden>— Select Category —</option>
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
          <i class="ri-information-line"></i> No active suppliers for this category.
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
      <select disabled><option>— Select a category first —</option></select>
    @endif

    <a href="#" class="btn btn-warning btn-sm" id="bulkTriggerBtn" style="margin-left:auto;">
      <i class="ri-checkbox-multiple-line me-1"></i><span id="selectedCount">0</span> Selected
    </a>
  </div>

  {{-- ── Table ── --}}
  <div class="card-body">
    <table id="maintable" class="table table-sm table-striped row-border order-column w-100 mt-2">
      <thead style="background-color:#e2e2e9">
        <tr>
          <th>Product Name</th>
          <th>Code</th>
          <th>Unit</th>
          <th>Order Price</th>
          <th>Sell Price</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="tbody">
        @foreach($products as $product)
          @php $row = 'row' . $product->id; $supplierName = $supplierNamesMap[$product->supplier] ?? '—'; @endphp
          <tr id="{{ $row }}">
            <td>
              <input type="checkbox" class="selectRow" value="{{ $product->id }}" data-row-id="{{ $row }}">
              &nbsp;{{ $product->name }}
            </td>
            <td>{{ $product->code ?? '—' }}</td>
            <td>{{ $product->unit }}</td>
            <td>
              @if($product->cost_price !== null)
                <span style="font-size:12px;color:#6c757d">{{ number_format($product->cost_price,2) }}</span>
              @else <span class="text-muted" style="font-size:12px">—</span> @endif
            </td>
            <td>
              @if($product->selling_price !== null)
                <span class="price-cell">{{ number_format($product->selling_price,2) }}</span>
              @else <span class="text-muted" style="font-size:12px">—</span> @endif
            </td>
            <td>
              <a href="#" class="viewDataBtn"
                 data-id="{{ $product->id }}" data-name="{{ $product->name }}"
                 data-description="{{ $product->description }}"
                 data-supplier-id="{{ $product->supplier }}" data-supplier-name="{{ $supplierName }}"
                 data-code="{{ $product->code }}" data-unit="{{ $product->unit }}"
                 data-sell="{{ $product->selling_price }}" data-cost="{{ $product->cost_price }}"
                 data-is-product="{{ $product->is_product }}">
                <i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px"></i>
              </a>
              <a href="#" class="editDataBtn"
                 data-id="{{ $product->id }}" data-row="{{ $row }}"
                 data-name="{{ $product->name }}" data-description="{{ $product->description }}"
                 data-supplier-id="{{ $product->supplier }}"
                 data-code="{{ $product->code }}" data-unit="{{ $product->unit }}"
                 data-sell="{{ $product->selling_price }}" data-cost="{{ $product->cost_price }}"
                 data-is-product="{{ $product->is_product }}">
                <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px"></i>
              </a>
              <a href="#" class="deleteDataBtn"
                 data-label="{{ $product->name }}" data-id="{{ $product->id }}" data-row="{{ $row }}">
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

{{-- ══════════════════════════════════════════════════════════════════
     DOWNLOAD MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Download</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body"><div class="buttons"></div></div>
  </div></div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     INFO MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title"><i class="ri-information-line me-1 text-primary"></i> About Base Products</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <p class="mb-2"><strong>Base Products</strong> are your master catalogue — defined once and assigned to branches.</p>
      <p class="mb-2"><strong>Supplier (Required)</strong> — every product links to a supplier which determines its category.</p>
      <p class="mb-2"><strong>Default Prices</strong> — selling and cost prices here apply to all branches by default. Branches can override individually.</p>
      <p class="mb-0"><strong>Product vs Service</strong> — flagged via the Edit form. New entries default to Product.</p>
    </div>
  </div></div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     VIEW MODAL
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
        <ul class="nav nav-tabs nav-sm mb-3" role="tablist" style="font-size:12px;">
          <li class="nav-item"><button class="nav-link active py-1 px-2" data-bs-toggle="tab" data-bs-target="#vw-t1"><i class="ri-price-tag-3-line me-1"></i>Identity</button></li>
          <li class="nav-item"><button class="nav-link py-1 px-2"        data-bs-toggle="tab" data-bs-target="#vw-t2"><i class="ri-money-dollar-circle-line me-1"></i>Pricing</button></li>
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

{{-- ══════════════════════════════════════════════════════════════════
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
        <form id="newDataForm">
          @csrf
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Product Name <span class="text-danger">*</span></label>
            <input class="form-control" type="text" id="new-name" placeholder="e.g. Cooking Oil 2L" autocomplete="off" />
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Supplier <span class="text-danger">*</span></label>
            <select class="form-select" id="new-supplier">
              <option value="">— Select Supplier —</option>
              @foreach($suppliers as $sup)
                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
              @endforeach
            </select>
            @if($savedCategoryId && $suppliers->isEmpty())
              <div class="form-text text-warning"><i class="ri-information-line"></i> No active suppliers for this category.</div>
            @elseif(!$savedCategoryId)
              <div class="form-text text-muted">Select a category filter to see suppliers.</div>
            @endif
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold" style="font-size:13px">Selling Price <small class="text-muted fw-normal">(MWK)</small></label>
              <input class="form-control" type="number" step="0.01" min="0" id="new-selling-price" placeholder="0.00" />
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold" style="font-size:13px">Order / Cost Price <small class="text-muted fw-normal">(MWK)</small></label>
              <input class="form-control" type="number" step="0.01" min="0" id="new-cost-price" placeholder="0.00" />
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Unit of Measure</label>
            <input class="form-control" type="text" id="new-unit" list="newUnitOptions" placeholder="Each, kg, Litre…" value="Each" autocomplete="off" />
            <datalist id="newUnitOptions">
              <option value="Each"><option value="kg"><option value="g"><option value="Litre"><option value="ml">
              <option value="Box"><option value="Carton"><option value="Pack"><option value="Pair">
              <option value="Dozen"><option value="Bag"><option value="Bottle"><option value="Metre"><option value="Service">
            </datalist>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Code <small class="text-muted fw-normal">(SKU — optional)</small></label>
            <input class="form-control" type="text" id="new-code" placeholder="e.g. OIL-001" autocomplete="off" />
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Description <small class="text-muted fw-normal">(optional)</small></label>
            <textarea class="form-control" id="new-description" rows="2" placeholder="Brief description…"></textarea>
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

{{-- ══════════════════════════════════════════════════════════════════
     IMPORT MODAL — 4-step wizard
     Step 1: Guide  ·  Step 2: Supplier  ·  Step 3: Upload/Validate/Preview  ·  Step 4: Chunked Import
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="importModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
      <div class="modal-header mh-green">
        <h5 class="modal-title mh-title"><i class="ri-file-excel-2-line"></i> Import Base Products from CSV</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:16px 18px !important;">

        <div class="csv-step-indicator">
          <div class="csi-step active" id="bp-csi1"><span class="csi-num">1</span>Guide</div>
          <div class="csi-line"></div>
          <div class="csi-step" id="bp-csi2"><span class="csi-num">2</span>Supplier</div>
          <div class="csi-line"></div>
          <div class="csi-step" id="bp-csi3"><span class="csi-num">3</span>Upload</div>
          <div class="csi-line"></div>
          <div class="csi-step" id="bp-csi4"><span class="csi-num">4</span>Import</div>
        </div>

        {{-- Step 1: Guide --}}
        <div class="csv-step active" id="bpCsvStep1">
          <div style="font-size:13px;color:#374151;margin-bottom:12px;">
            Prepare a CSV file with the following columns:
          </div>
          <div style="background:#f8f9fa;border-radius:8px;padding:12px 14px;margin-bottom:14px;font-family:monospace;font-size:12px;color:#374151;overflow-x:auto;white-space:nowrap;">
            name, code, unit, cost_price, selling_price
          </div>
          <div class="alert" style="background:#eff6ff;border-left:3px solid #4B5EBD;border-radius:0 5px 5px 0;padding:8px 12px;font-size:11px;color:#1e40af;margin-bottom:14px;">
            <i class="ri-information-line me-1"></i>
            All rows will be assigned to the supplier you choose in the next step. Formatted numbers like <code>2,000</code> are accepted. Unit is imported exactly as written in the file. Rows are checked as the file is read — anything invalid (name too long, bad price format, duplicate within the file) is set aside immediately and can be downloaded instead of being sent. Rows that fail to save on the server (e.g. already in the catalogue) are also reported at the end and can be downloaded.
          </div>
          <table style="width:100%;border-collapse:collapse;font-size:11px;margin-bottom:14px;">
            <thead>
              <tr style="background:#eef0f7;">
                <th style="padding:6px 8px;text-align:left;border-bottom:1px solid #dee2e6;">Column</th>
                <th style="padding:6px 8px;text-align:left;border-bottom:1px solid #dee2e6;">Required</th>
                <th style="padding:6px 8px;text-align:left;border-bottom:1px solid #dee2e6;">Notes</th>
              </tr>
            </thead>
            <tbody>
              <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:5px 8px;font-weight:600;">name</td><td style="padding:5px 8px;color:#dc2626;">Yes</td><td style="padding:5px 8px;color:#6c757d;">Product name (max 255 chars)</td></tr>
              <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:5px 8px;font-weight:600;">code</td><td style="padding:5px 8px;color:#6c757d;">No</td><td style="padding:5px 8px;color:#6c757d;">SKU / product code (max 100 chars)</td></tr>
              <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:5px 8px;font-weight:600;">unit</td><td style="padding:5px 8px;color:#6c757d;">No</td><td style="padding:5px 8px;color:#6c757d;">Kept exactly as written (defaults to "Each" only if blank)</td></tr>
              <tr style="border-bottom:1px solid #f1f5f9;"><td style="padding:5px 8px;font-weight:600;">cost_price</td><td style="padding:5px 8px;color:#6c757d;">No</td><td style="padding:5px 8px;color:#6c757d;">2,000 or 2000</td></tr>
              <tr><td style="padding:5px 8px;font-weight:600;">selling_price</td><td style="padding:5px 8px;color:#6c757d;">No</td><td style="padding:5px 8px;color:#6c757d;">2,000 or 2000</td></tr>
            </tbody>
          </table>
          <div class="d-flex justify-content-between align-items-center">
            <a href="#" id="bpCsvDownloadSample" style="font-size:12px;color:#4B5EBD;">
              <i class="ri-download-line me-1"></i>Download sample CSV
            </a>
            <button type="button" class="btn btn-primary btn-sm" onclick="bpCsvGoToStep(2)">
              Next <i class="ri-arrow-right-s-line"></i>
            </button>
          </div>
        </div>

        {{-- Step 2: Supplier --}}
        <div class="csv-step" id="bpCsvStep2">
          <label class="form-label fw-semibold" style="font-size:12px">Supplier <span class="text-danger">*</span></label>
          <select class="form-select form-select-sm mb-3" id="bp-csv-supplier">
            <option value="">Select supplier</option>
            @foreach($suppliers as $sup)
              <option value="{{ $sup->id }}">{{ $sup->name }}</option>
            @endforeach
          </select>
          <div style="font-size:11px;color:#6c757d;margin-bottom:14px;">
            @if($savedCategoryId)
              Only active suppliers in the currently selected category are listed. All imported rows will be assigned to this supplier.
            @else
              Select a category in the page filter bar first to see available suppliers.
            @endif
          </div>
          <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-secondary btn-sm" onclick="bpCsvGoToStep(1)">
              <i class="ri-arrow-left-s-line"></i> Back
            </button>
            <button type="button" class="btn btn-primary btn-sm" onclick="bpCsvStep2Next()">
              Next <i class="ri-arrow-right-s-line"></i>
            </button>
          </div>
        </div>

        {{-- Step 3: Upload — parses + validates fully client-side, caches valid/invalid
             rows separately to localStorage, previews valid rows, offers immediate
             download of invalid rows. --}}
        <div class="csv-step" id="bpCsvStep3">
          <label class="form-label fw-semibold" style="font-size:12px">CSV File <span class="text-danger">*</span></label>
          <input class="form-control form-control-sm mb-2" type="file" id="bp-csv-file" accept=".csv,.txt" />
          <div id="bpCsvParseError" class="alert alert-danger py-2 px-3 mb-2" style="font-size:11px;display:none;"></div>
          <div id="bpCsvFilePreviewWrap" style="display:none;">
            <div style="font-size:11px;color:#6c757d;margin-bottom:6px;" id="bpCsvFilePreviewLabel"></div>
            <div class="csv-preview-scroll" id="bpCsvFilePreviewScroll"></div>
          </div>
          <div id="bpCsvInvalidWrap" class="csv-invalid-banner" style="display:none;">
            <span><i class="ri-error-warning-line me-1"></i><span id="bpCsvInvalidCount">0</span> row(s) look invalid and won't be sent — fix and re-import separately.</span>
            <a href="#" id="bpCsvDownloadInvalidBtn" class="btn btn-sm btn-outline-danger">
              <i class="ri-file-excel-2-line me-1"></i>Download Invalid Rows (.xlsx)
            </a>
          </div>
          <div style="font-size:11px;color:#6c757d;margin:10px 0 14px;">
            The file is fully parsed and validated in this browser before anything is sent — if the modal closes accidentally your parsed rows are kept until you re-open it. Only valid rows are uploaded, in small batches, so large files never time out. Products already in the catalogue (by name) will be skipped server-side — you'll see a count at the end.
          </div>
          <div class="d-flex justify-content-between">
            <button type="button" class="btn btn-secondary btn-sm" onclick="bpCsvGoToStep(2)">
              <i class="ri-arrow-left-s-line"></i> Back
            </button>
            <button type="button" class="btn btn-success btn-sm" id="bpCsvImportBtn" disabled>
              <i class="ri-upload-2-line"></i> Import CSV
            </button>
          </div>
        </div>

        {{-- Step 4: Chunked upload progress + combined result --}}
        <div class="csv-step" id="bpCsvStep4">
          <div id="bpCsvImportProgress" style="font-size:13px;color:#475569;text-align:center;padding:20px 0;"></div>
          <div id="bpCsvChunkProgressWrap" style="display:none;">
            <div class="csv-chunk-progress-track"><div class="csv-chunk-progress-fill" id="bpCsvChunkProgressFill"></div></div>
            <div class="csv-chunk-progress-label" id="bpCsvChunkProgressLabel"></div>
          </div>
          <div class="d-flex justify-content-end mt-2">
            <button type="button" class="btn btn-primary btn-sm" id="bpCsvDoneBtn">
              <i class="ri-check-line"></i> Done
            </button>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     SINGLE DELETE MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="singleDeleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:380px;">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-danger">
        <h5 class="modal-title mh-title"><i class="ri-delete-bin-line"></i> Delete Product</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center py-4">
        <i class="ri-error-warning-line text-danger" style="font-size:60px"></i>
        <h5 class="mt-2 mb-1">Delete <span id="singleDeleteLabel" class="text-danger"></span>?</h5>
        <p style="font-size:12px;color:#6c757d;margin-bottom:0;">
          Cannot be undone. If this product is assigned to a branch, it will be skipped instead.
        </p>
        <input type="hidden" id="singleDeleteId">
        <input type="hidden" id="singleDeleteRow">
      </div>
      <div class="modal-footer justify-content-center gap-2" style="padding:10px 20px 18px;">
        <a href="#" class="btn btn-secondary btn-sm px-4" id="keepSingleBtn">Keep</a>
        <a href="#" class="btn btn-danger btn-sm px-4"    id="submitSingleDeleteBtn">Yes, Delete</a>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     BULK DELETE MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="multipleDeleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:380px;">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-danger">
        <h5 class="modal-title mh-title"><i class="ri-delete-bin-line"></i> Delete Selected</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center py-4">
        <i class="ri-error-warning-line text-danger" style="font-size:60px"></i>
        <h5 class="mt-2 mb-1">Delete <span id="multipleDeleteCount" class="text-danger">0</span> product(s)?</h5>
        <p style="font-size:12px;color:#6c757d;margin-bottom:0;">
          Cannot be undone. Products assigned to a branch will be skipped — you'll see how many.
        </p>
        <input type="hidden" id="multipleDeleteIds">
        <input type="hidden" id="multipleDeleteRows">
      </div>
      <div class="modal-footer justify-content-center gap-2" style="padding:10px 20px 18px;">
        <a href="#" class="btn btn-secondary btn-sm px-4" id="keepMultipleBtn">Keep</a>
        <a href="#" class="btn btn-danger btn-sm px-4"    id="submitMultipleDeleteBtn">Yes, Delete</a>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     BULK ACTIONS MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="bulkActionsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-orange">
        <h5 class="modal-title mh-title">
          <i class="ri-checkbox-multiple-line"></i>
          Bulk Actions — <span id="bulkActionsCount">0</span> selected
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:16px 18px !important;">
        <div class="bulk-section">
          <div class="bulk-section-title"><i class="ri-truck-line me-1"></i>Change Supplier</div>
          <div class="d-flex gap-2 align-items-center">
            <select class="form-select form-select-sm" id="bulkSupplierSelect">
              <option value="">— Select Supplier —</option>
              @foreach($suppliers as $sup)
                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
              @endforeach
            </select>
            <a href="#" class="btn btn-sm btn-warning text-dark" id="applyBulkSupplierBtn" style="white-space:nowrap">
              <i class="ri-check-line me-1"></i>Apply
            </a>
          </div>
        </div>
        <div class="bulk-section">
          <div class="bulk-section-title"><i class="ri-toggle-line me-1"></i>Set Type</div>
          <div class="d-flex gap-2">
            <a href="#" class="btn btn-sm btn-success text-white flex-fill" id="bulkMarkProductBtn">
              <i class="ri-box-3-line me-1"></i>Mark as Product
            </a>
            <a href="#" class="btn btn-sm btn-warning text-dark flex-fill" id="bulkMarkServiceBtn">
              <i class="ri-service-line me-1"></i>Mark as Service
            </a>
          </div>
        </div>
        <div class="d-grid mt-1">
          <a href="#" class="btn btn-danger" id="deleteSelectedBtn">
            <i class="ri-delete-bin-line me-1"></i>Delete Selected
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     EDIT MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-edit-box-line"></i> Update Base Product</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:16px 18px 8px !important;">
        <form id="editDataForm">
          @csrf
          <input type="hidden" id="editId">
          <input type="hidden" id="editRow">
          <div class="mb-2">
            <label class="form-label fw-semibold" style="font-size:13px">Product Name <span class="text-danger">*</span></label>
            <input class="form-control form-control-sm" type="text" id="editName" autocomplete="off" required />
          </div>
          <div class="mb-2">
            <label class="form-label fw-semibold" style="font-size:13px">Supplier <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" id="editSupplier" required>
              <option value="">— Select Supplier —</option>
              @foreach($suppliers as $sup)
                <option value="{{ $sup->id }}">{{ $sup->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="row g-2 mb-2">
            <div class="col-6">
              <label class="form-label fw-semibold" style="font-size:13px">Selling Price <small class="text-muted fw-normal">(MWK)</small></label>
              <input class="form-control form-control-sm" type="number" step="0.01" min="0" id="editSellingPrice" placeholder="0.00" />
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold" style="font-size:13px">Order / Cost Price <small class="text-muted fw-normal">(MWK)</small></label>
              <input class="form-control form-control-sm" type="number" step="0.01" min="0" id="editCostPrice" placeholder="0.00" />
            </div>
          </div>
          <div class="mb-2">
            <label class="form-label fw-semibold" style="font-size:13px">Unit of Measure <span class="text-danger">*</span></label>
            <input class="form-control form-control-sm" type="text" id="editUnit" autocomplete="off" required />
          </div>
          <div class="mb-2">
            <label class="form-label fw-semibold" style="font-size:13px">Code <small class="text-muted fw-normal">(SKU — optional)</small></label>
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
                <input class="form-check-input" type="radio" name="is_product" id="editIsProductYes" value="1">
                <label class="form-check-label" for="editIsProductYes">
                  <span class="type-badge-product"><i class="ri-box-3-line me-1"></i>Product</span>
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="is_product" id="editIsProductNo" value="0">
                <label class="form-check-label" for="editIsProductNo">
                  <span class="type-badge-service"><i class="ri-service-line me-1"></i>Service</span>
                </label>
              </div>
            </div>
          </div>
          <div class="alert border-0 py-2 px-3 mt-2 mb-0"
               style="background:#f0f3ff;border-left:3px solid #4B5EBD;border-radius:0 5px 5px 0;font-size:11px;color:#3a4a9a;">
            <i class="ri-information-line me-1"></i>
            Prices here are <strong>defaults for all branches</strong>. Branches can override individually.
          </div>
        </form>
      </div>
      <div class="modal-footer" style="padding:10px 18px 14px;justify-content:flex-end;gap:8px;">
        <a href="#" class="btn btn-secondary btn-sm" id="cancelEditBtn">Cancel</a>
        <a href="#" class="btn btn-primary btn-sm"   id="submitUpdateBtn">
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

    toastr.options = { closeButton:true, progressBar:true, showMethod:'slideDown', timeOut:5000, allowHtml:true };

    // ── Helpers ───────────────────────────────────────────────────────────
    function handleAjaxError(xhr, status) {
        if (status === 'timeout') { toastr.error('Request timed out.', 'Timeout'); return; }
        if (xhr.status === 422) {
            var e = xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : {};
            var m = ''; $.each(e, function(k,v) { m += v + '\n'; });
            toastr.error(m || (xhr.responseJSON && xhr.responseJSON.error) || 'Validation failed.', 'Error');
        } else if (xhr.status === 500) {
            toastr.error('Server error.', 'Error');
        } else {
            toastr.error('Unspecified error.', 'Error');
        }
    }

    function fmtPrice(val) {
        if (val === null || val === '' || val === undefined) return '—';
        var n = parseFloat(val);
        return isNaN(n) ? '—' : n.toLocaleString('en-US', { minimumFractionDigits:2, maximumFractionDigits:2 });
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
                   data-supplier-id="${p.supplier_id}" data-supplier-name="${d(p.supplier_name)}"
                   data-code="${d(p.code)}" data-unit="${d(p.unit)}"
                   data-sell="${p.selling_price !== null ? p.selling_price : ''}"
                   data-cost="${p.cost_price    !== null ? p.cost_price    : ''}"
                   data-is-product="${p.is_product}">
                   <i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px"></i>
                </a>
                <a href="#" class="editDataBtn"
                   data-id="${p.id}" data-row="${p.row}"
                   data-name="${d(p.name)}" data-description="${d(p.description)}"
                   data-supplier-id="${p.supplier_id}"
                   data-code="${d(p.code)}" data-unit="${d(p.unit)}"
                   data-sell="${p.selling_price !== null ? p.selling_price : ''}"
                   data-cost="${p.cost_price    !== null ? p.cost_price    : ''}"
                   data-is-product="${p.is_product}">
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
        var rows  = $('.selectRow').length;
        var count = $('.selectRow:checked').length;
        $('#selectedCount').text(count);
        if (count > 0) $('#bulkTriggerBtn').addClass('visible');
        else           $('#bulkTriggerBtn').removeClass('visible');
        $('#selectAll').prop('checked', rows > 0 && count === rows);
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

    // ════════════════════════════════════════════════════════════════════════
    //  CSV IMPORT WIZARD
    //  - Parses the whole file client-side (proper quoted-CSV parser).
    //  - VALIDATES every row at parse time and splits them into:
    //      · validRows   → cached to localStorage, these get uploaded
    //      · invalidRows → cached separately, never sent, downloadable
    //        immediately as .xlsx from Step 3
    //  - Uploads validRows to the server in small sequential CHUNKS (not one
    //    big request) so large files never risk a timeout or payload limit.
    //  - Server-side failures (e.g. name already exists) from every chunk
    //    are merged with the client-side invalidRows into ONE combined
    //    "Download All Failed/Invalid Rows" button at the end.
    // ════════════════════════════════════════════════════════════════════════
    var BP_CSV_VALID_LS_KEY   = 'bp_csv_import_valid_rows_v2';
    var BP_CSV_INVALID_LS_KEY = 'bp_csv_import_invalid_rows_v2';
    var BP_CSV_UPLOAD_CHUNK_SIZE = 150; // rows per network request — keeps every POST small

    // Small RFC4180-ish CSV parser: handles quoted fields, embedded commas,
    // escaped quotes ("") and CRLF/CR/LF line endings.
    function bpParseCsv(text) {
        text = text.replace(/^\uFEFF/, ''); // strip BOM
        var rows = [];
        var row = [];
        var field = '';
        var inQuotes = false;
        var i = 0, len = text.length;

        while (i < len) {
            var ch = text[i];

            if (inQuotes) {
                if (ch === '"') {
                    if (text[i + 1] === '"') { field += '"'; i += 2; continue; }
                    inQuotes = false; i++; continue;
                }
                field += ch; i++; continue;
            }

            if (ch === '"') { inQuotes = true; i++; continue; }
            if (ch === ',') { row.push(field); field = ''; i++; continue; }
            if (ch === '\r') { i++; continue; }
            if (ch === '\n') {
                row.push(field); field = '';
                if (row.length > 1 || row[0] !== '') rows.push(row);
                row = []; i++; continue;
            }
            field += ch; i++;
        }
        // last field/row (file may not end with a newline)
        if (field !== '' || row.length) {
            row.push(field);
            if (row.length > 1 || row[0] !== '') rows.push(row);
        }
        return rows;
    }

    // Permissive numeric parse: strips thousands separators/currency chars.
    // Returns { ok, value } — value is '' when the field was blank (allowed),
    // ok is false only when something was typed but isn't a valid number.
    function bpParsePriceField(raw) {
        var s = (raw || '').toString().trim();
        if (s === '') return { ok: true, value: '' };
        var cleaned = s.replace(/[^0-9.\-]/g, '');
        if (cleaned === '' || isNaN(parseFloat(cleaned))) return { ok: false, value: s };
        return { ok: true, value: cleaned };
    }

    /**
     * Parses the CSV text and VALIDATES every row as it goes, splitting the
     * result into validRows (will be uploaded) and invalidRows (will not —
     * offered as an immediate download instead). Nothing invalid is ever
     * sent to the server.
     */
    function bpBuildAndValidateRowsFromCsv(text) {
        var table = bpParseCsv(text);
        if (table.length < 2) return { error: 'CSV is empty or has no data rows.' };

        var header = table[0].map(function(h) { return h.trim().toLowerCase(); });
        var map = { name:null, code:null, unit:null, cost_price:null, selling_price:null };
        header.forEach(function(col, idx) {
            if (['name','product','product_name'].indexOf(col) !== -1) map.name = idx;
            if (['code','sku'].indexOf(col) !== -1)                    map.code = idx;
            if (col === 'unit')                                        map.unit = idx;
            if (['cost_price','cost'].indexOf(col) !== -1)             map.cost_price = idx;
            if (['selling_price','price','sell_price'].indexOf(col) !== -1) map.selling_price = idx;
        });
        if (map.name === null) return { error: 'CSV must contain a "name" column.' };

        var validRows   = [];
        var invalidRows = [];
        var seenNames   = {}; // catches duplicates WITHIN the file itself, before upload

        for (var r = 1; r < table.length; r++) {
            var cols = table[r];
            var name = (cols[map.name] || '').trim();
            if (name === '') continue; // blank row — skip silently, not an error

            var code = map.code !== null ? (cols[map.code] || '').trim() : '';
            // Unit kept exactly as written — only fall back to "Each" if blank.
            var unit = map.unit !== null ? (cols[map.unit] || '').trim() : '';
            if (unit === '') unit = 'Each';

            var costResult = map.cost_price    !== null ? bpParsePriceField(cols[map.cost_price])    : { ok:true, value:'' };
            var sellResult = map.selling_price !== null ? bpParsePriceField(cols[map.selling_price]) : { ok:true, value:'' };

            var row = {
                name: name,
                code: code || null,
                unit: unit,
                cost_price:    costResult.value,
                selling_price: sellResult.value
            };

            var reasons = [];
            if (name.length > 255)               reasons.push('Name exceeds 255 characters.');
            if (code && code.length > 100)        reasons.push('Code exceeds 100 characters.');
            if (unit.length > 50)                 reasons.push('Unit exceeds 50 characters.');
            if (!costResult.ok)                   reasons.push('Cost price is not a valid number.');
            if (!sellResult.ok)                   reasons.push('Selling price is not a valid number.');

            var key = name.toLowerCase();
            if (reasons.length === 0 && seenNames[key]) {
                reasons.push('Duplicate name within this file (first occurrence kept).');
            }

            if (reasons.length > 0) {
                row.error = reasons.join(' ');
                invalidRows.push(row);
                continue;
            }

            seenNames[key] = true;
            validRows.push(row);
        }

        if (!validRows.length && !invalidRows.length) return { error: 'No valid rows found in CSV.' };
        return { validRows: validRows, invalidRows: invalidRows };
    }

    function bpSaveToStorage(key, rows) {
        try { localStorage.setItem(key, JSON.stringify(rows)); } catch (e) { /* storage full/unavailable — proceed in-memory only */ }
    }
    function bpLoadFromStorage(key) {
        try {
            var raw = localStorage.getItem(key);
            return raw ? JSON.parse(raw) : null;
        } catch (e) { return null; }
    }
    function bpClearStorage(key) {
        try { localStorage.removeItem(key); } catch (e) {}
    }

    var bpCsvValidRows   = []; // in-memory mirror of localStorage (valid)
    var bpCsvInvalidRows = []; // in-memory mirror of localStorage (invalid)

    function bpRenderPreview(validRows, invalidRows) {
        var html = validRows.map(function(r) {
            var meta = [r.code || '—', r.unit, r.cost_price || '—', r.selling_price || '—'].join(' · ');
            return '<div class="csv-preview-row">' +
                   '<span class="cpr-name">' + $('<div>').text(r.name).html() + '</span>' +
                   '<span class="cpr-meta">' + $('<div>').text(meta).html() + '</span>' +
                   '</div>';
        }).join('');
        $('#bpCsvFilePreviewLabel').text(validRows.length + ' valid row(s) parsed and cached — scroll to review before importing');
        $('#bpCsvFilePreviewScroll').html(html);
        $('#bpCsvFilePreviewWrap').toggle(validRows.length > 0);
        $('#bpCsvImportBtn').prop('disabled', validRows.length === 0);

        if (invalidRows.length > 0) {
            $('#bpCsvInvalidCount').text(invalidRows.length);
            $('#bpCsvInvalidWrap').show();
        } else {
            $('#bpCsvInvalidWrap').hide();
        }
    }

    function bpCsvReset() {
        bpCsvGoToStep(1);
        $('#bp-csv-supplier').val('');
        $('#bp-csv-file').val('');
        $('#bpCsvFilePreviewWrap').hide();
        $('#bpCsvFilePreviewScroll').html('');
        $('#bpCsvInvalidWrap').hide();
        $('#bpCsvImportProgress').html('');
        $('#bpCsvChunkProgressWrap').hide();
        $('#bpCsvChunkProgressFill').css('width', '0%');
        $('#bpCsvChunkProgressLabel').text('');
        $('#bpCsvParseError').hide().text('');
        $('#bpCsvImportBtn').prop('disabled', true);

        // Re-hydrate from localStorage if a previous parse is still cached,
        // so closing the modal mid-way doesn't lose the work.
        var cachedValid   = bpLoadFromStorage(BP_CSV_VALID_LS_KEY);
        var cachedInvalid = bpLoadFromStorage(BP_CSV_INVALID_LS_KEY);
        if (cachedValid && cachedValid.length) {
            bpCsvValidRows   = cachedValid;
            bpCsvInvalidRows = cachedInvalid || [];
            bpRenderPreview(bpCsvValidRows, bpCsvInvalidRows);
        } else {
            bpCsvValidRows   = [];
            bpCsvInvalidRows = [];
        }
    }

    $('#importBtn').on('click', function(e) { e.preventDefault(); bpCsvReset(); $('#importModal').modal('show'); });
    // Note: intentionally NOT clearing localStorage on modal hide — that's what lets a parse survive an accidental close.

    window.bpCsvGoToStep = function(step) {
        $('.csv-step').removeClass('active');
        $('#bpCsvStep' + step).addClass('active');
        for (var i = 1; i <= 4; i++) {
            var el = document.getElementById('bp-csi' + i);
            el.className = 'csi-step' + (i < step ? ' done' : (i === step ? ' active' : ''));
        }
    };

    window.bpCsvStep2Next = function() {
        if (!$('#bp-csv-supplier').val()) {
            toastr.warning('Select a supplier.', 'Required');
            return;
        }
        bpCsvGoToStep(3);
    };

    $('#bpCsvDownloadSample').on('click', function(e) {
        e.preventDefault();
        var csv = 'name,code,unit,cost_price,selling_price\n' +
                  'Cooking Oil 2L,OIL-001,Each,1500.00,2000.00\n' +
                  'Drinking Water 500ml,WAT-001,Each,350.00,500.00\n' +
                  '"Bread Loaf, 700g",BRD-001,Each,600.00,800.00\n';
        var blob = new Blob([csv], { type:'text/csv' });
        var url  = URL.createObjectURL(blob);
        var a    = document.createElement('a');
        a.href   = url; a.download = 'base_products_sample.csv'; a.click();
        URL.revokeObjectURL(url);
    });

    // Parse + validate on file select → cache valid/invalid separately →
    // render preview + invalid download banner. Nothing is sent yet.
    $('#bp-csv-file').on('change', function() {
        var file = this.files[0];
        $('#bpCsvParseError').hide().text('');
        if (!file) {
            $('#bpCsvFilePreviewWrap').hide();
            $('#bpCsvInvalidWrap').hide();
            $('#bpCsvImportBtn').prop('disabled', true);
            return;
        }

        var reader = new FileReader();
        reader.onload = function(e) {
            var result = bpBuildAndValidateRowsFromCsv(e.target.result);
            if (result.error) {
                $('#bpCsvParseError').text(result.error).show();
                $('#bpCsvFilePreviewWrap').hide();
                $('#bpCsvInvalidWrap').hide();
                $('#bpCsvImportBtn').prop('disabled', true);
                bpClearStorage(BP_CSV_VALID_LS_KEY);
                bpClearStorage(BP_CSV_INVALID_LS_KEY);
                bpCsvValidRows = []; bpCsvInvalidRows = [];
                return;
            }
            bpCsvValidRows   = result.validRows;
            bpCsvInvalidRows = result.invalidRows;
            bpSaveToStorage(BP_CSV_VALID_LS_KEY,   bpCsvValidRows);
            bpSaveToStorage(BP_CSV_INVALID_LS_KEY, bpCsvInvalidRows);
            bpRenderPreview(bpCsvValidRows, bpCsvInvalidRows);

            if (bpCsvInvalidRows.length > 0) {
                toastr.warning(bpCsvInvalidRows.length + ' row(s) were invalid and will not be imported — download them from Step 3 to review.', 'Some rows skipped');
            }
        };
        reader.onerror = function() {
            $('#bpCsvParseError').text('Could not read that file.').show();
        };
        reader.readAsText(file);
    });

    // Loads xlsx.js on demand (only needed when there are rows to export).
    var _bpXlsxLoading = null;
    function bpLoadXlsxLib() {
        if (window.XLSX) return Promise.resolve();
        if (_bpXlsxLoading) return _bpXlsxLoading;
        _bpXlsxLoading = new Promise(function(resolve, reject) {
            var s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
            s.onload = resolve;
            s.onerror = reject;
            document.head.appendChild(s);
        });
        return _bpXlsxLoading;
    }

    function bpDownloadRowsAsExcel(rows, filename, sheetName) {
        bpLoadXlsxLib().then(function() {
            var sheetData = rows.map(function(r) {
                return {
                    name:          r.name,
                    code:          r.code,
                    unit:          r.unit,
                    cost_price:    r.cost_price,
                    selling_price: r.selling_price,
                    error:         r.error || 'Failed to save'
                };
            });
            var ws = XLSX.utils.json_to_sheet(sheetData);
            var wb = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(wb, ws, sheetName || 'Rows');
            XLSX.writeFile(wb, filename);
        }).catch(function() {
            toastr.error('Could not load the Excel export library — check your connection.', 'Error');
        });
    }

    // Step 3: download the rows that failed CLIENT-SIDE validation, before
    // anything was even sent to the server.
    $('#bpCsvDownloadInvalidBtn').on('click', function(e) {
        e.preventDefault();
        var rows = bpLoadFromStorage(BP_CSV_INVALID_LS_KEY) || bpCsvInvalidRows;
        if (!rows.length) { toastr.info('No invalid rows to download.', 'Info'); return; }
        bpDownloadRowsAsExcel(rows, 'base_products_invalid_rows.xlsx', 'Invalid Rows');
    });

    /**
     * Uploads validRows to the server in sequential chunks of
     * BP_CSV_UPLOAD_CHUNK_SIZE rows. Each chunk is its own small POST, so a
     * large file (hundreds/thousands of rows) never risks a single request
     * timing out or exceeding post_max_size — and one chunk failing outright
     * (network blip, server error) doesn't lose everything: chunks already
     * processed stay committed, and we keep going with the rest.
     *
     * Results (created/skipped/failed) are aggregated across all chunks and
     * shown as one combined summary at the end, with a single "download all
     * failed/invalid rows" button covering BOTH server-side failures and the
     * rows that were already filtered out client-side in Step 3.
     */
    function bpUploadRowsChunked(validRows, supplierId, clientBatchId) {
        var chunks = [];
        for (var i = 0; i < validRows.length; i += BP_CSV_UPLOAD_CHUNK_SIZE) {
            chunks.push(validRows.slice(i, i + BP_CSV_UPLOAD_CHUNK_SIZE));
        }

        var totalChunks = chunks.length;
        var aggregate = {
            created: 0,
            skipped: 0,
            skippedNames: [],
            failedRows: [],
            chunkErrors: 0
        };

        $('#bpCsvChunkProgressWrap').show();
        $('#bpCsvChunkProgressLabel').text('Preparing ' + totalChunks + ' batch(es)…');

        function uploadOne(index) {
            if (index >= totalChunks) {
                return Promise.resolve();
            }

            var chunkRows = chunks[index];
            var pct = Math.round((index / totalChunks) * 100);
            $('#bpCsvChunkProgressFill').css('width', pct + '%');
            $('#bpCsvChunkProgressLabel').text(
                'Uploading batch ' + (index + 1) + ' of ' + totalChunks +
                ' — ' + (index * BP_CSV_UPLOAD_CHUNK_SIZE) + '/' + validRows.length + ' rows sent'
            );

            return new Promise(function(resolve) {
                $.ajax({
                    type:        'POST',
                    url:         '{{ route("retail.operations.baseproducts.csv.upload") }}',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        rows:          chunkRows,
                        supplier_id:   supplierId,
                        batch_id:      clientBatchId,
                        chunk_index:   index + 1,
                        total_chunks:  totalChunks,
                        _token:        '{{ csrf_token() }}'
                    }),
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    timeout: 60000, // small chunk → short, generous timeout is still safe
                    success: function(data) {
                        if (data.status === 200) {
                            aggregate.created += (data.created_count || 0);
                            aggregate.skipped += (data.skipped_count || 0);
                            if (data.skipped_names) aggregate.skippedNames = aggregate.skippedNames.concat(data.skipped_names);
                            if (data.failed_rows)   aggregate.failedRows   = aggregate.failedRows.concat(data.failed_rows);
                        } else {
                            aggregate.chunkErrors++;
                            // Mark every row in this chunk as failed so nothing silently vanishes.
                            chunkRows.forEach(function(r) {
                                aggregate.failedRows.push($.extend({}, r, { error: data.error || 'Batch failed to process.' }));
                            });
                        }
                        resolve();
                    },
                    error: function(xhr, status) {
                        aggregate.chunkErrors++;
                        var msg = status === 'timeout' ? 'Request timed out.' : 'Network/server error for this batch.';
                        chunkRows.forEach(function(r) {
                            aggregate.failedRows.push($.extend({}, r, { error: msg }));
                        });
                        resolve(); // keep going with remaining chunks regardless
                    }
                });
            }).then(function() {
                return uploadOne(index + 1);
            });
        }

        return uploadOne(0).then(function() {
            $('#bpCsvChunkProgressFill').css('width', '100%');
            $('#bpCsvChunkProgressLabel').text('All batches sent — ' + totalChunks + ' of ' + totalChunks);
            return aggregate;
        });
    }

    $('#bpCsvImportBtn').on('click', function() {
        var supId     = $('#bp-csv-supplier').val();
        var validRows = bpLoadFromStorage(BP_CSV_VALID_LS_KEY) || bpCsvValidRows;
        if (!supId)          { toastr.warning('Select a supplier first.', 'Required'); bpCsvGoToStep(2); return; }
        if (!validRows.length) { toastr.warning('Choose a CSV file.', 'Required'); bpCsvGoToStep(3); return; }

        var self = $(this); self.prop('disabled', true);
        bpCsvGoToStep(4);
        $('#bpCsvImportProgress').html(
            '<i class="ri-loader-4-line" style="font-size:32px;animation:spin 1s linear infinite;display:inline-block;"></i>' +
            '<div class="mt-2">Importing — please wait…</div>'
        );

        var clientBatchId = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : ('bp_' + Date.now() + '_' + Math.random().toString(36).slice(2));

        $('#progressBar').show();

        bpUploadRowsChunked(validRows, supId, clientBatchId).then(function(aggregate) {
            $('#progressBar').hide();
            self.prop('disabled', false);

            var invalidRows  = bpLoadFromStorage(BP_CSV_INVALID_LS_KEY) || bpCsvInvalidRows || [];
            var allFailedRows = aggregate.failedRows.concat(invalidRows);

            var html = '<i class="ri-checkbox-circle-line text-success" style="font-size:38px;"></i>' +
                       '<div class="mt-2" style="font-weight:600;color:#1e293b;">' +
                       aggregate.created + ' of ' + validRows.length + ' row(s) created.</div>';

            if (aggregate.chunkErrors > 0) {
                html += '<div class="mt-1" style="font-size:12px;color:#dc2626;">' + aggregate.chunkErrors + ' batch(es) hit an error — affected rows are included in the failed download below.</div>';
            }

            if (aggregate.skipped > 0 && aggregate.skippedNames.length) {
                html += '<div class="mt-2" style="font-size:12px;color:#6c757d;text-align:left;">' +
                        '<strong>' + aggregate.skipped + '</strong> row(s) skipped (already in catalogue):</div>' +
                        '<div class="import-skipped-list"><ul class="mb-0 ps-3">';
                aggregate.skippedNames.slice(0, 50).forEach(function(name) {
                    html += '<li>' + $('<div>').text(name).html() + '</li>';
                });
                if (aggregate.skippedNames.length > 50) {
                    html += '<li style="color:#94a3b8;">…and ' + (aggregate.skippedNames.length - 50) + ' more</li>';
                }
                html += '</ul></div>';
            }

            if (allFailedRows.length) {
                html += '<div class="mt-3" style="font-size:12px;color:#7f1d1d;text-align:left;">' +
                        '<strong>' + allFailedRows.length + '</strong> row(s) were not imported (server failures + rows filtered out before upload) — download, fix, and re-import:</div>' +
                        '<div class="mt-2 text-center"><a href="#" id="bpDownloadFailedBtn" class="btn btn-sm btn-outline-danger">' +
                        '<i class="ri-file-excel-2-line me-1"></i>Download All Failed/Invalid Rows (.xlsx)</a></div>';
            }

            $('#bpCsvImportProgress').html(html);
            $('#bpCsvChunkProgressWrap').hide();

            if (allFailedRows.length) {
                $('#bpDownloadFailedBtn').on('click', function(e) {
                    e.preventDefault();
                    bpDownloadRowsAsExcel(allFailedRows, 'base_products_failed_rows.xlsx', 'Failed Rows');
                });
            }

            toastr.success(aggregate.created + ' row(s) imported.', 'Import complete');

            // Only clear the "will be imported" cache once the batch has
            // actually been sent — the invalid-rows cache stays so it can
            // still be downloaded from Step 3 if the modal is reopened,
            // until the next file is chosen.
            bpClearStorage(BP_CSV_VALID_LS_KEY);
        });
    });

    $('#bpCsvDoneBtn').on('click', function() {
        $('#importModal').modal('hide');
        setTimeout(function() { location.reload(); }, 200);
    });

    // ════════════════════════════════════════════════════════════════════════
    //  VIEW
    // ════════════════════════════════════════════════════════════════════════
    var _viewData = {};

    $('#tbody').on('click', '.viewDataBtn', function(e) {
        e.preventDefault();
        var b = $(this);
        _viewData = {
            id:          b.data('id'),
            name:        b.data('name'),
            description: b.data('description'),
            supplierId:  b.data('supplier-id'),
            supplierName:b.data('supplier-name'),
            code:        b.data('code'),
            unit:        b.data('unit'),
            sell:        b.data('sell'),
            cost:        b.data('cost'),
            isProduct:   b.data('is-product'),
            editRow:     b.closest('tr').attr('id')
        };

        function mv(val) {
            return (val === '' || val === null || val === undefined || val === 'null')
                ? '<span style="color:#9ca3af;font-style:italic;">—</span>' : val;
        }

        $('#vw-name').text(_viewData.name);
        $('#vw-code-line').text(_viewData.code ? 'Code: ' + _viewData.code : '');
        $('#vw-badges').html(typeBadge(_viewData.isProduct));
        $('#vw-code').html(mv(_viewData.code));
        $('#vw-unit').html(mv(_viewData.unit));
        $('#vw-supplier').html(mv(_viewData.supplierName));
        $('#vw-type').html(typeBadge(_viewData.isProduct));
        $('#vw-description').html(mv(_viewData.description));
        $('#vw-sell').html(_viewData.sell !== '' && _viewData.sell !== null ? fmtPrice(_viewData.sell) : '<span style="color:#9ca3af;font-style:italic;">—</span>');
        $('#vw-cost').html(_viewData.cost !== '' && _viewData.cost !== null ? fmtPrice(_viewData.cost) : '<span style="color:#9ca3af;font-style:italic;">—</span>');

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
    //  ADD
    // ════════════════════════════════════════════════════════════════════════
    $('#newDataBtn').on('click', function(e) { e.preventDefault(); resetNewModal(); $('#newDataModal').modal('show'); });
    $('#newDataModal').on('hidden.bs.modal', resetNewModal);
    $('#cancelDataBtn').on('click', function(e) { e.preventDefault(); $('#newDataModal').modal('hide'); });

    function resetNewModal() {
        $('#new-name, #new-code, #new-description, #new-selling-price, #new-cost-price').val('');
        $('#new-unit').val('Each');
        $('#new-supplier').val('');
    }

    $('#submitDataBtn').on('click', function(e) {
        e.preventDefault();
        var name = $('#new-name').val().trim();
        if (!name)                { toastr.warning('Product name is required.', 'Required'); $('#new-name').focus();     return; }
        if (!$('#new-supplier').val()) { toastr.warning('Select a supplier.',  'Required'); $('#new-supplier').focus(); return; }

        var self = $(this); self.prop('disabled', true);
        $.ajaxSetup({ headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:    'POST',
            url:     '{{ route("retail.operations.baseproducts.insert") }}',
            timeout: 60000,
            data: {
                name:          name,
                supplier:      $('#new-supplier').val(),
                unit:          $('#new-unit').val() || 'Each',
                code:          $('#new-code').val(),
                description:   $('#new-description').val(),
                selling_price: $('#new-selling-price').val(),
                cost_price:    $('#new-cost-price').val(),
                _token:        '{{ csrf_token() }}'
            },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    table.row.add($(buildRow(data.product))).draw(false);
                    resetNewModal();
                    $('#new-name').focus();
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
    //  EDIT
    // ════════════════════════════════════════════════════════════════════════
    $('#tbody').on('click', '.editDataBtn', function(e) {
        e.preventDefault();
        var b = $(this);
        $('#editId').val(b.data('id'));
        $('#editRow').val(b.data('row'));
        $('#editName').val(b.data('name'));
        $('#editSupplier').val(b.data('supplier-id'));
        $('#editCode').val(b.data('code'));
        $('#editUnit').val(b.data('unit'));
        $('#editDescription').val(b.data('description'));
        $('#editSellingPrice').val(b.data('sell'));
        $('#editCostPrice').val(b.data('cost'));
        var ip = parseInt(b.data('is-product'));
        if (ip === 1) $('#editIsProductYes').prop('checked', true);
        else          $('#editIsProductNo').prop('checked',  true);
        $('#editDataModal').modal('show');
    });

    $('#cancelEditBtn').on('click', function(e) { e.preventDefault(); $('#editDataModal').modal('hide'); });

    $('#submitUpdateBtn').on('click', function(e) {
        e.preventDefault();
        var name     = $('#editName').val().trim();
        var supplier = $('#editSupplier').val();
        if (!name)     { toastr.warning('Product name is required.', 'Required'); $('#editName').focus();     return; }
        if (!supplier) { toastr.warning('Select a supplier.',        'Required'); $('#editSupplier').focus(); return; }

        var self = $(this); self.prop('disabled', true);
        var row  = $('#editRow').val();

        $.ajaxSetup({ headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:    'POST',
            url:     '{{ route("retail.operations.baseproducts.update") }}',
            timeout: 60000,
            data: {
                id:            $('#editId').val(),
                name:          name,
                supplier:      supplier,
                unit:          $('#editUnit').val(),
                code:          $('#editCode').val(),
                description:   $('#editDescription').val(),
                is_product:    $('input[name="is_product"]:checked').val() || '1',
                selling_price: $('#editSellingPrice').val(),
                cost_price:    $('#editCostPrice').val(),
                _token:        '{{ csrf_token() }}'
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
    //  SINGLE DELETE — server skips if product still has stock at a branch
    // ════════════════════════════════════════════════════════════════════════
    $('#tbody').on('click', '.deleteDataBtn', function(e) {
        e.preventDefault();
        var b = $(this);
        $('#singleDeleteLabel').text(b.data('label'));
        $('#singleDeleteRow').val(b.data('row'));
        $('#singleDeleteId').val(b.data('id'));
        $('#singleDeleteModal').modal('show');
    });

    $('#keepSingleBtn').on('click', function(e) { e.preventDefault(); $('#singleDeleteModal').modal('hide'); });

    $('#submitSingleDeleteBtn').on('click', function(e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        var row  = $('#singleDeleteRow').val();
        var id   = $('#singleDeleteId').val();

        $.ajaxSetup({ headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:    'POST',
            url:     '{{ route("retail.operations.baseproducts.delete") }}',
            timeout: 60000,
            data:    { id:id, _token:'{{ csrf_token() }}' },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    if (data.skipped === 1) {
                        toastr.warning(data.success, 'Skipped');
                    } else {
                        toastr.success(data.success, 'Deleted');
                        table.row('#' + row).remove().draw(false);
                        updateSelectedCount();
                    }
                    $('#singleDeleteModal').modal('hide');
                } else {
                    toastr.error(data.error || 'Failed.', 'Error');
                }
            },
            error: handleAjaxError
        });
    });

    // ════════════════════════════════════════════════════════════════════════
    //  BULK DELETE — server skips products still holding stock at a branch
    // ════════════════════════════════════════════════════════════════════════
    $('#deleteSelectedBtn').on('click', function(e) {
        e.preventDefault();
        var ids  = [], rows = [];
        $('.selectRow:checked').each(function() { ids.push($(this).val()); rows.push($(this).data('row-id')); });
        if (!ids.length) { toastr.warning('No products selected.', 'Warning'); return; }
        $('#multipleDeleteCount').text(ids.length);
        $('#multipleDeleteIds').val(ids.join(','));
        $('#multipleDeleteRows').val(rows.join(','));
        $('#bulkActionsModal').modal('hide');
        setTimeout(function() { $('#multipleDeleteModal').modal('show'); }, 250);
    });

    $('#keepMultipleBtn').on('click', function(e) { e.preventDefault(); $('#multipleDeleteModal').modal('hide'); });

    $('#submitMultipleDeleteBtn').on('click', function(e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        var ids  = $('#multipleDeleteIds').val().split(',');
        var rows = $('#multipleDeleteRows').val().split(',');

        $.ajaxSetup({ headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:    'POST',
            url:     '{{ route("retail.operations.baseproducts.bulkdelete") }}',
            timeout: 60000,
            data:    { ids:ids, _token:'{{ csrf_token() }}' },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    if (data.deleted > 0) {
                        if (data.skipped > 0) {
                            toastr.success(data.success, 'Done');
                            setTimeout(function() { location.reload(); }, 1800);
                        } else {
                            rows.forEach(function(r) { table.row('#' + r).remove(); });
                            table.draw(false);
                            updateSelectedCount();
                            toastr.success(data.success, 'Deleted');
                        }
                    } else {
                        toastr.warning(data.success, 'Skipped');
                    }
                    $('#multipleDeleteModal').modal('hide');
                } else {
                    toastr.error(data.error || 'Failed.', 'Error');
                }
            },
            error: handleAjaxError
        });
    });

    // ════════════════════════════════════════════════════════════════════════
    //  BULK TYPE
    // ════════════════════════════════════════════════════════════════════════
    function doBulkStatus(isProduct) {
        var selected = []; $('.selectRow:checked').each(function() { selected.push($(this).val()); });
        if (!selected.length) return;
        $.ajaxSetup({ headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type: 'POST', url: '{{ route("retail.operations.baseproducts.bulkstatus") }}',
            data: { ids:selected, is_product:isProduct, _token:'{{ csrf_token() }}' }, timeout:60000,
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
    $('#bulkMarkProductBtn').on('click', function(e) { e.preventDefault(); doBulkStatus(1); });
    $('#bulkMarkServiceBtn').on('click', function(e) { e.preventDefault(); doBulkStatus(0); });

    // ════════════════════════════════════════════════════════════════════════
    //  BULK SUPPLIER
    // ════════════════════════════════════════════════════════════════════════
    $('#applyBulkSupplierBtn').on('click', function(e) {
        e.preventDefault();
        var supplier = $('#bulkSupplierSelect').val();
        if (!supplier) { toastr.warning('Select a supplier.', 'Required'); return; }
        var selected = []; $('.selectRow:checked').each(function() { selected.push($(this).val()); });
        if (!selected.length) return;
        $.ajaxSetup({ headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type: 'POST', url: '{{ route("retail.operations.baseproducts.bulksupplier") }}',
            data: { ids:selected, supplier:supplier, _token:'{{ csrf_token() }}' }, timeout:60000,
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

    // ════════════════════════════════════════════════════════════════════════
    //  ROW SELECTION
    // ════════════════════════════════════════════════════════════════════════
    $('#selectAll').on('change', function() { $('.selectRow').prop('checked', this.checked); updateSelectedCount(); });
    $('#tbody').on('click', '.selectRow', function() { updateSelectedCount(); });

});
</script>
@endsection