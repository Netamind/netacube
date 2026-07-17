@extends('operations.retail.dashboard')
@section('content')

@php
    // Active types are offered for new/updated expenditures; inactive types
    // are excluded from the select but existing rows still display their name
    // via the join below, so historical records never show blank.
    $expenditureTypes = DB::connection('tenant')
        ->table('retail_expenditure_types')
        ->where('status', 'active')
        ->orderBy('name')
        ->get();

    // categories has no sector column of its own — a category counts as
    // "under Retail" only if some Retail-sector branch is tagged with it.
    // branches.category holds the categories.id value (it's an id column that
    // was just never suffixed "_id"), so join it against categories.id.
    $retailCategories = DB::connection('tenant')
        ->table('categories as c')
        ->join('branches as b', 'b.category', '=', 'c.id')
        ->where('b.sector', 'Retail')
        ->select('c.id', 'c.category')
        ->distinct()
        ->orderBy('c.category')
        ->get();

    $retailBranches = DB::connection('tenant')
        ->table('branches')
        ->where('sector', 'Retail')
        ->where('status', 'active')
        ->orderBy('name')
        ->get(['id', 'name', 'category']);

    $expenditures = DB::connection('tenant')
        ->table('retail_expenditures as e')
        ->join('retail_expenditure_types as t', 't.id', '=', 'e.expenditure_type_id')
        ->leftJoin('categories as c', 'c.id', '=', 'e.category_id')
        ->leftJoin('branches as b', 'b.id', '=', 'e.branch_id')
        ->select('e.*', 't.name as type_name', 'c.category as category_name', 'b.name as branch_name')
        ->orderBy('e.expenditure_date', 'desc')
        ->orderBy('e.id', 'desc')
        ->get();

    $maintableTitle = 'Retail Expenditures';
@endphp

<style>
/* ── Reset & Base ─────────────────────────────────────────────────── */
.dt-buttons .btn { background:transparent !important; background-image:none !important; box-shadow:none !important; border-color:#5bc0de; color:#5bc0de; }
.dt-buttons .btn:hover { background:#5bc0de !important; color:#fff; }

/* ── Card chrome ─────────────────────────────────────────────────── */
.card-header { padding:0.5rem 1.5rem !important; background:linear-gradient(to right,#4B5EBD,#576CC0); color:#fff; border-radius:10px 10px 0 0 !important; flex-wrap:wrap; gap:8px; }
.card-body   { padding:0 1.5rem 1.5rem 1.5rem !important; }
.card        { border:none; box-shadow:0 4px 8px rgba(0,0,0,0.1); border-radius:10px; }
.card-header h4 { color:#fff; font-weight:600; margin-bottom:0; display:flex; align-items:center; }
.card-header .btn-light { height:28px; padding:0 10px; display:flex; align-items:center; justify-content:center; line-height:1; }
.card-header .btn-light:hover { background-color:#f8f9fa; transition:background-color 0.2s; }

/* ── Select all checkbox ────────────────────────────────────────── */
.header-select-all { width:16px; height:16px; cursor:pointer; accent-color:#4B5EBD; background:#d1d5db; border-radius:3px; margin-right:10px; flex-shrink:0; vertical-align:middle; }
.header-title-block { display:flex; flex-direction:column; line-height:1.25; min-width:0; }
.card-header-actions { display:flex; align-items:center; gap:4px; flex-wrap:wrap; justify-content:flex-end; }

@media (max-width:576px) {
  .card-header { padding:10px 14px !important; }
  .header-title-block { max-width:55vw; }
  .card-header-actions { width:100%; justify-content:flex-start; }
  .card-header .btn-light { height:32px; width:32px; padding:0; font-size:15px; }
}

/* ── Remove spinners ─────────────────────────────────────────────── */
input[type=number]::-webkit-outer-spin-button, input[type=number]::-webkit-inner-spin-button { -webkit-appearance:none; margin:0; }
input[type=number] { -moz-appearance:textfield; appearance:textfield; }

/* ── Bulk button ─────────────────────────────────────────────────── */
#bulkActionsHeaderBtn { position:relative; opacity:.5; pointer-events:none; cursor:not-allowed; transition:opacity .15s; }
#bulkActionsHeaderBtn.enabled { opacity:1; pointer-events:auto; cursor:pointer; }
#bulkActionsHeaderBtn .bah-count { position:absolute; top:-5px; right:-5px; background:#dc2626; color:#fff; border-radius:50%; font-size:10px; font-weight:700; min-width:16px; height:16px; line-height:16px; text-align:center; padding:0 3px; display:none; box-shadow:0 0 0 1.5px #fff; }
#bulkActionsHeaderBtn .bah-count.show { display:block; }

/* ── Table alignment ─────────────────────────────────────────────── */
#maintable thead th, table.dataTable thead th { text-align:center !important; vertical-align:middle !important; }
#maintable thead th:first-child, table.dataTable thead th:first-child { text-align:left !important; }
#maintable tbody td, table.dataTable tbody td { text-align:center !important; vertical-align:middle !important; }
#maintable tbody td:first-child, table.dataTable tbody td:first-child { text-align:left !important; }

/* ── Amounts & badges ────────────────────────────────────────────── */
.amount-cell { font-size:12px; font-weight:600; color:#c0392b; }
.scope-badge-all      { font-size:10px; font-weight:600; background:#e7f1ff; color:#1d4ed8; border:1px solid #93c5fd; border-radius:10px; padding:1px 7px; white-space:nowrap; }
.scope-badge-category { font-size:10px; font-weight:600; background:#fff3e0; color:#e65100; border:1px solid #ffcc80; border-radius:10px; padding:1px 7px; white-space:nowrap; }
.scope-badge-branch   { font-size:10px; font-weight:600; background:#e8f5e9; color:#2d6a4f; border:1px solid #a5d6a7; border-radius:10px; padding:1px 7px; white-space:nowrap; }

/* ── Modal headers ───────────────────────────────────────────────── */
.mh-blue   { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-danger { background:linear-gradient(135deg,#c0392b,#e74c3c); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-title  { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close  { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }

/* ── Scope selector ──────────────────────────────────────────────── */
.scope-option { flex:1; }
.scope-option label { display:flex; flex-direction:column; align-items:center; gap:4px; border:1px solid #dee2e6; border-radius:8px; padding:10px 6px; font-size:12px; font-weight:600; color:#495057; cursor:pointer; text-align:center; }
.scope-option input:checked + label { border-color:#4B5EBD; background:#f0f3ff; color:#4B5EBD; }
.scope-option input { position:absolute; opacity:0; pointer-events:none; }

/* ── Section titles inside forms ─────────────────────────────────── */
.edit-section { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.8px; color:#94a3b8; margin:16px 0 8px; display:flex; align-items:center; gap:6px; }
.edit-section::after { content:''; flex:1; height:1px; background:#e9ecef; }

/* ── Bulk action cards ───────────────────────────────────────────── */
.bulk-option-card { display:flex; align-items:center; gap:12px; padding:14px 16px; border-radius:10px; border:1.5px solid #e9ecef; cursor:pointer; transition:border-color .15s,background .15s; margin-bottom:10px; }
.bulk-option-card:last-child { margin-bottom:0; }
.bulk-option-card:hover { border-color:#c8d0ed; background:#f8f9ff; }
.bulk-option-card .boc-icon { width:40px; height:40px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:19px; flex-shrink:0; }
.boc-title { font-size:14px; font-weight:700; color:#1e293b; }
.boc-desc  { font-size:12px; color:#6c757d; margin-top:1px; }
.boc-icon-delete { background:#fef2f2; color:#dc2626; }
</style>

<div class="progress" id="progressBar" role="progressbar" style="height:8px;transform:rotate(180deg);display:none">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
      <input type="checkbox" id="selectAll" class="header-select-all">
      Expenditures
    </h4>
    <div class="card-header-actions">
      <button type="button" class="btn btn-light text-primary fs-16 mx-1" id="bulkActionsHeaderBtn" disabled title="Select rows to enable bulk actions">
        <i class="ri-stack-line"></i>
        <span class="bah-count" id="bulkActionsHeaderCount"></span>
      </button>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="newDataBtn"      title="Record expenditure"><i class="ri-add-circle-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn"         title="About Expenditures"><i class="ri-information-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download"><i class="ri-download-line"></i></a>
    </div>
  </div>

  <div class="card-body">
    <table id="maintable" class="table table-sm table-striped row-border order-column w-100 mt-3">
      <thead style="background-color:#e2e2e9">
        <tr>
          <th>Type</th>
          <th>Scope</th>
          <th>Amount</th>
          <th>Date</th>
          <th>Reference</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="tbody">
        @foreach($expenditures as $exp)
          @php
            $row = 'exrow' . $exp->id;
            $scopeLabel = $exp->scope_type === 'category' ? ('Category: ' . ($exp->category_name ?? '—'))
                        : ($exp->scope_type === 'branch'   ? ('Branch: '   . ($exp->branch_name   ?? '—'))
                        : 'All Retail');
            $scopeBadgeClass = 'scope-badge-' . $exp->scope_type;
          @endphp
          <tr id="{{ $row }}">
            <td>
              <input type="checkbox" class="selectRow" value="{{ $exp->id }}" data-row-id="{{ $row }}">
              &nbsp;{{ $exp->type_name }}
            </td>
            <td><span class="{{ $scopeBadgeClass }}">{{ $scopeLabel }}</span></td>
            <td><span class="amount-cell">{{ number_format($exp->amount, 2) }}</span></td>
            <td>{{ \Carbon\Carbon::parse($exp->expenditure_date)->format('d M Y') }}</td>
            <td>{{ $exp->reference_no ?? '—' }}</td>
            <td>
              <a href="#" class="editDataBtn"
                 data-id="{{ $exp->id }}" data-row="{{ $row }}"
                 data-type-id="{{ $exp->expenditure_type_id }}"
                 data-scope-type="{{ $exp->scope_type }}"
                 data-category-id="{{ $exp->category_id }}"
                 data-branch-id="{{ $exp->branch_id }}"
                 data-amount="{{ $exp->amount }}"
                 data-date="{{ \Carbon\Carbon::parse($exp->expenditure_date)->format('Y-m-d') }}"
                 data-reference="{{ $exp->reference_no }}"
                 data-description="{{ $exp->description }}">
                <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px"></i>
              </a>
              <a href="#" class="deleteDataBtn"
                 data-label="{{ $exp->type_name }} — {{ number_format($exp->amount, 2) }}"
                 data-id="{{ $exp->id }}" data-row="{{ $row }}">
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

{{-- ══ BULK ACTIONS MODAL ══ --}}
<div class="modal fade" id="bulkActionsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue">
      <h5 class="modal-title mh-title"><i class="ri-stack-line"></i> Bulk Actions <span style="font-size:12px;font-weight:400;opacity:.85" id="bulkActionsModalCountText">— 0 selected</span></h5>
      <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="padding:18px 20px !important;">
      <div class="bulk-option-card" id="deleteSelectedBtn">
        <div class="boc-icon boc-icon-delete"><i class="ri-delete-bin-line"></i></div>
        <div><div class="boc-title">Delete Selected</div><div class="boc-desc">Remove the selected expenditures. This cannot be undone.</div></div>
      </div>
    </div>
    <div class="modal-footer" style="padding:10px 20px 14px;"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button></div>
  </div></div>
</div>

{{-- ══ DOWNLOAD MODAL ══ --}}
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue"><h5 class="modal-title mh-title"><i class="ri-download-line"></i> Download</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="buttons"></div></div>
  </div></div>
</div>

{{-- ══ INFO MODAL ══ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue"><h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Expenditures</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" style="padding:18px 20px;">
      <p class="mb-2"><strong>Scope</strong> controls how broadly an expenditure applies:</p>
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <tbody>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;width:140px;border-bottom:1px solid #f1f5f9"><span class="scope-badge-all">All Retail</span></td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">Applies across the whole Retail sector.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9"><span class="scope-badge-category">Category</span></td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">Applies to one specific category under Retail.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569"><span class="scope-badge-branch">Branch</span></td><td style="padding:8px 12px">Applies to one specific branch under Retail.</td></tr>
        </tbody>
      </table>
    </div>
    <div class="modal-footer" style="padding:10px 20px 14px;"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button></div>
  </div></div>
</div>

{{-- ══ ADD MODAL ══ --}}
<div class="modal fade" id="newDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-add-circle-line"></i> Record Expenditure</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:16px 20px 8px !important;">
        <form id="newDataForm">
          @csrf
          <div class="edit-section" style="margin-top:0;"><i class="ri-price-tag-3-line"></i>Type &amp; Scope</div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Expenditure Type <span class="text-danger">*</span></label>
            <select class="form-select" id="new-type">
              <option value="">— Select Type —</option>
              @foreach($expenditureTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold d-block" style="font-size:13px">Scope <span class="text-danger">*</span></label>
            <div class="d-flex gap-2 position-relative">
              <div class="scope-option">
                <input type="radio" name="new_scope" id="new-scope-all" value="all" checked>
                <label for="new-scope-all"><i class="ri-global-line"></i>All Retail</label>
              </div>
              <div class="scope-option">
                <input type="radio" name="new_scope" id="new-scope-category" value="category">
                <label for="new-scope-category"><i class="ri-price-tag-3-line"></i>Category</label>
              </div>
              <div class="scope-option">
                <input type="radio" name="new_scope" id="new-scope-branch" value="branch">
                <label for="new-scope-branch"><i class="ri-store-2-line"></i>Branch</label>
              </div>
            </div>
          </div>

          <div class="mb-3" id="new-category-wrap" style="display:none;">
            <label class="form-label fw-semibold" style="font-size:13px">Category <span class="text-danger">*</span></label>
            <select class="form-select" id="new-category">
              <option value="">— Select Category —</option>
              @foreach($retailCategories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->category }}</option>
              @endforeach
            </select>
            @if($retailCategories->isEmpty())
              <div class="form-text text-warning"><i class="ri-information-line"></i> No categories found under the Retail sector.</div>
            @endif
          </div>

          <div class="mb-3" id="new-branch-wrap" style="display:none;">
            <label class="form-label fw-semibold" style="font-size:13px">Branch <span class="text-danger">*</span></label>
            <select class="form-select" id="new-branch">
              <option value="">— Select Branch —</option>
              @foreach($retailBranches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
              @endforeach
            </select>
            @if($retailBranches->isEmpty())
              <div class="form-text text-warning"><i class="ri-information-line"></i> No active branches found under the Retail sector.</div>
            @endif
          </div>

          <div class="edit-section"><i class="ri-money-dollar-circle-line"></i>Amount &amp; Date</div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label fw-semibold" style="font-size:13px">Amount <small class="text-muted fw-normal">(MWK)</small> <span class="text-danger">*</span></label>
              <input class="form-control" type="number" step="0.01" min="0.01" id="new-amount" placeholder="0.00" />
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold" style="font-size:13px">Date <span class="text-danger">*</span></label>
              <input class="form-control" type="date" id="new-date" />
            </div>
          </div>

          <div class="edit-section"><i class="ri-file-text-line"></i>Details <span style="font-size:10px;font-weight:400;text-transform:none;letter-spacing:0;color:#b0b7c3;">(optional)</span></div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Reference No</label>
            <input class="form-control" type="text" id="new-reference" placeholder="e.g. Receipt / Invoice no." autocomplete="off" />
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Description</label>
            <textarea class="form-control" id="new-description" rows="2" placeholder="Brief description…"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer" style="padding:10px 20px 14px;justify-content:flex-end;gap:8px;">
        <a href="#" class="btn btn-secondary btn-sm" id="cancelDataBtn">Cancel</a>
        <a href="#" class="btn btn-success btn-sm"   id="submitDataBtn"><i class="ri-check-line"></i> Save Expenditure</a>
      </div>
    </div>
  </div>
</div>

{{-- ══ EDIT MODAL ══ --}}
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-edit-box-line"></i> Update Expenditure</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:16px 18px 8px !important;">
        <form id="editDataForm">
          @csrf
          <input type="hidden" id="editId">
          <input type="hidden" id="editRow">

          <div class="edit-section" style="margin-top:0;"><i class="ri-price-tag-3-line"></i>Type &amp; Scope</div>
          <div class="mb-2">
            <label class="form-label fw-semibold" style="font-size:13px">Expenditure Type <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" id="editType">
              <option value="">— Select Type —</option>
              @foreach($expenditureTypes as $type)
                <option value="{{ $type->id }}">{{ $type->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-2">
            <label class="form-label fw-semibold d-block" style="font-size:13px">Scope <span class="text-danger">*</span></label>
            <div class="d-flex gap-2 position-relative">
              <div class="scope-option">
                <input type="radio" name="edit_scope" id="edit-scope-all" value="all">
                <label for="edit-scope-all"><i class="ri-global-line"></i>All Retail</label>
              </div>
              <div class="scope-option">
                <input type="radio" name="edit_scope" id="edit-scope-category" value="category">
                <label for="edit-scope-category"><i class="ri-price-tag-3-line"></i>Category</label>
              </div>
              <div class="scope-option">
                <input type="radio" name="edit_scope" id="edit-scope-branch" value="branch">
                <label for="edit-scope-branch"><i class="ri-store-2-line"></i>Branch</label>
              </div>
            </div>
          </div>

          <div class="mb-2" id="edit-category-wrap" style="display:none;">
            <label class="form-label fw-semibold" style="font-size:13px">Category <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" id="editCategory">
              <option value="">— Select Category —</option>
              @foreach($retailCategories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->category }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-2" id="edit-branch-wrap" style="display:none;">
            <label class="form-label fw-semibold" style="font-size:13px">Branch <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" id="editBranch">
              <option value="">— Select Branch —</option>
              @foreach($retailBranches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="edit-section"><i class="ri-money-dollar-circle-line"></i>Amount &amp; Date</div>
          <div class="row g-2 mb-2">
            <div class="col-6">
              <label class="form-label fw-semibold" style="font-size:13px">Amount <small class="text-muted fw-normal">(MWK)</small> <span class="text-danger">*</span></label>
              <input class="form-control form-control-sm" type="number" step="0.01" min="0.01" id="editAmount" placeholder="0.00" />
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold" style="font-size:13px">Date <span class="text-danger">*</span></label>
              <input class="form-control form-control-sm" type="date" id="editDate" />
            </div>
          </div>

          <div class="edit-section"><i class="ri-file-text-line"></i>Details <span style="font-size:10px;font-weight:400;text-transform:none;letter-spacing:0;color:#b0b7c3;">(optional)</span></div>
          <div class="mb-2">
            <label class="form-label fw-semibold" style="font-size:13px">Reference No</label>
            <input class="form-control form-control-sm" type="text" id="editReference" autocomplete="off" />
          </div>

          <div class="mb-2">
            <label class="form-label fw-semibold" style="font-size:13px">Description</label>
            <textarea class="form-control form-control-sm" id="editDescription" rows="2"></textarea>
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

{{-- ══ SINGLE DELETE MODAL ══ --}}
<div class="modal fade" id="singleDeleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:380px;">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-danger">
        <h5 class="modal-title mh-title"><i class="ri-delete-bin-line"></i> Delete Expenditure</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center py-4">
        <i class="ri-error-warning-line text-danger" style="font-size:60px"></i>
        <h5 class="mt-2 mb-1">Delete <span id="singleDeleteLabel" class="text-danger"></span>?</h5>
        <p style="font-size:13px;color:#6c757d;margin-bottom:0;">This cannot be undone.</p>
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

{{-- ══ BULK DELETE CONFIRM MODAL ══ --}}
<div class="modal fade" id="multipleDeleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:380px;">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-danger">
        <h5 class="modal-title mh-title"><i class="ri-delete-bin-line"></i> Delete Selected</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center py-4">
        <i class="ri-error-warning-line text-danger" style="font-size:60px"></i>
        <h5 class="mt-2 mb-1">Delete <span id="multipleDeleteCount" class="text-danger">0</span> expenditure(s)?</h5>
        <p style="font-size:13px;color:#6c757d;margin-bottom:0;">This cannot be undone.</p>
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

@endsection

@section('scripts')
<script>
$(document).ready(function () {

    toastr.options = { closeButton:true, progressBar:true, showMethod:'slideDown', timeOut:5000, allowHtml:true };

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

    function fmtAmount(val) {
        var n = parseFloat(val);
        return isNaN(n) ? '0.00' : n.toLocaleString('en-US', { minimumFractionDigits:2, maximumFractionDigits:2 });
    }

    function fmtDate(iso) {
        if (!iso) return '—';
        var d = new Date(iso + 'T00:00:00');
        if (isNaN(d.getTime())) return iso;
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        return d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    }

    function scopeBadge(e) {
        if (e.scope_type === 'category') {
            return '<span class="scope-badge-category">Category: ' + (e.category_name || '—') + '</span>';
        }
        if (e.scope_type === 'branch') {
            return '<span class="scope-badge-branch">Branch: ' + (e.branch_name || '—') + '</span>';
        }
        return '<span class="scope-badge-all">All Retail</span>';
    }

    function buildRow(e) {
        function d(v) { return (v || '').toString().replace(/"/g, '&quot;'); }
        return `<tr id="${e.row}">
            <td>
                <input type="checkbox" class="selectRow" value="${e.id}" data-row-id="${e.row}">
                &nbsp;${e.type_name}
            </td>
            <td>${scopeBadge(e)}</td>
            <td><span class="amount-cell">${fmtAmount(e.amount)}</span></td>
            <td>${fmtDate(e.expenditure_date)}</td>
            <td>${e.reference_no || '—'}</td>
            <td>
                <a href="#" class="editDataBtn"
                   data-id="${e.id}" data-row="${e.row}"
                   data-type-id="${e.expenditure_type_id}"
                   data-scope-type="${e.scope_type}"
                   data-category-id="${e.category_id || ''}"
                   data-branch-id="${e.branch_id || ''}"
                   data-amount="${e.amount}"
                   data-date="${e.expenditure_date}"
                   data-reference="${d(e.reference_no)}"
                   data-description="${d(e.description)}">
                   <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px"></i>
                </a>
                <a href="#" class="deleteDataBtn"
                   data-label="${d(e.type_name)} — ${fmtAmount(e.amount)}" data-id="${e.id}" data-row="${e.row}">
                   <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px"></i>
                </a>
            </td>
        </tr>`;
    }

    function updateSelectedCount() {
        var total = $('.selectRow').length;
        var count = $('.selectRow:checked').length;
        var badge = $('#bulkActionsHeaderCount');
        if (count > 0) {
            badge.text(count).addClass('show');
            $('#bulkActionsHeaderBtn').addClass('enabled').prop('disabled', false).attr('title', count + ' selected — click for bulk actions');
        } else {
            badge.text('').removeClass('show');
            $('#bulkActionsHeaderBtn').removeClass('enabled').prop('disabled', true).attr('title', 'Select rows to enable bulk actions');
        }
        $('#selectAll').prop('checked', total > 0 && count === total);
    }

    // ════════════════════════════════════════════════════════════════════════
    //  Scope toggle — new + edit modals
    // ════════════════════════════════════════════════════════════════════════
    function toggleScopeFields(prefix) {
        var scope = $('input[name="' + prefix + '_scope"]:checked').val();
        $('#' + prefix + '-category-wrap').toggle(scope === 'category');
        $('#' + prefix + '-branch-wrap').toggle(scope === 'branch');
    }
    $('input[name="new_scope"]').on('change',  function() { toggleScopeFields('new'); });
    $('input[name="edit_scope"]').on('change', function() { toggleScopeFields('edit'); });

    // ════════════════════════════════════════════════════════════════════════
    //  DataTable
    // ════════════════════════════════════════════════════════════════════════
    var table = $('#maintable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[100,250,500,-1],[100,250,500,'All']],
        fixedColumns: { leftColumns: 1 },
        scrollX: true,
        order: [],
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

    $('#bulkActionsHeaderBtn').on('click', function() {
        if (!$(this).hasClass('enabled')) return;
        $('#bulkActionsModalCountText').text('— ' + $('.selectRow:checked').length + ' selected');
        $('#bulkActionsModal').modal('show');
    });

    // ════════════════════════════════════════════════════════════════════════
    //  ADD
    // ════════════════════════════════════════════════════════════════════════
    $('#newDataBtn').on('click', function(e) {
        e.preventDefault();
        resetNewModal();
        $('#newDataModal').modal('show');
    });
    $('#newDataModal').on('hidden.bs.modal', resetNewModal);
    $('#cancelDataBtn').on('click', function(e) { e.preventDefault(); $('#newDataModal').modal('hide'); });

    function resetNewModal() {
        $('#new-type, #new-category, #new-branch, #new-amount, #new-reference, #new-description').val('');
        $('#new-date').val(new Date().toISOString().slice(0,10));
        $('#new-scope-all').prop('checked', true);
        toggleScopeFields('new');
    }

    $('#submitDataBtn').on('click', function(e) {
        e.preventDefault();

        var typeId = $('#new-type').val();
        var scope  = $('input[name="new_scope"]:checked').val();
        var catId  = $('#new-category').val();
        var branchId = $('#new-branch').val();
        var amount = $('#new-amount').val();
        var date   = $('#new-date').val();

        if (!typeId)  { toastr.warning('Select an expenditure type.', 'Required'); return; }
        if (scope === 'category' && !catId)    { toastr.warning('Select a category.', 'Required'); return; }
        if (scope === 'branch'   && !branchId) { toastr.warning('Select a branch.',   'Required'); return; }
        if (!amount || parseFloat(amount) <= 0) { toastr.warning('Enter a valid amount.', 'Required'); return; }
        if (!date)    { toastr.warning('Select a date.', 'Required'); return; }

        var self = $(this); self.prop('disabled', true);
        $.ajaxSetup({ headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:    'POST',
            url:     '{{ route("retail.operations.expenditures.insert") }}',
            timeout: 60000,
            data: {
                expenditure_type_id: typeId,
                scope_type:          scope,
                category_id:         scope === 'category' ? catId    : '',
                branch_id:           scope === 'branch'   ? branchId : '',
                amount:              amount,
                expenditure_date:    date,
                reference_no:        $('#new-reference').val(),
                description:         $('#new-description').val(),
                _token:              '{{ csrf_token() }}'
            },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    table.row.add($(buildRow(data.expenditure))).draw(false);
                    resetNewModal();
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
        $('#editType').val(b.data('type-id'));
        $('#editAmount').val(b.data('amount'));
        $('#editDate').val(b.data('date'));
        $('#editReference').val(b.data('reference'));
        $('#editDescription').val(b.data('description'));

        var scope = b.data('scope-type') || 'all';
        $('input[name="edit_scope"][value="' + scope + '"]').prop('checked', true);
        $('#editCategory').val(b.data('category-id') || '');
        $('#editBranch').val(b.data('branch-id') || '');
        toggleScopeFields('edit');

        $('#editDataModal').modal('show');
    });

    $('#cancelEditBtn').on('click', function(e) { e.preventDefault(); $('#editDataModal').modal('hide'); });

    $('#submitUpdateBtn').on('click', function(e) {
        e.preventDefault();

        var typeId = $('#editType').val();
        var scope  = $('input[name="edit_scope"]:checked').val();
        var catId  = $('#editCategory').val();
        var branchId = $('#editBranch').val();
        var amount = $('#editAmount').val();
        var date   = $('#editDate').val();

        if (!typeId)  { toastr.warning('Select an expenditure type.', 'Required'); return; }
        if (scope === 'category' && !catId)    { toastr.warning('Select a category.', 'Required'); return; }
        if (scope === 'branch'   && !branchId) { toastr.warning('Select a branch.',   'Required'); return; }
        if (!amount || parseFloat(amount) <= 0) { toastr.warning('Enter a valid amount.', 'Required'); return; }
        if (!date)    { toastr.warning('Select a date.', 'Required'); return; }

        var self = $(this); self.prop('disabled', true);
        var row  = $('#editRow').val();

        $.ajaxSetup({ headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:    'POST',
            url:     '{{ route("retail.operations.expenditures.update") }}',
            timeout: 60000,
            data: {
                id:                  $('#editId').val(),
                expenditure_type_id: typeId,
                scope_type:          scope,
                category_id:         scope === 'category' ? catId    : '',
                branch_id:           scope === 'branch'   ? branchId : '',
                amount:              amount,
                expenditure_date:    date,
                reference_no:        $('#editReference').val(),
                description:         $('#editDescription').val(),
                _token:              '{{ csrf_token() }}'
            },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    table.row('#' + row).remove();
                    table.row.add($(buildRow(data.expenditure))).draw(false);
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
    //  SINGLE DELETE
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
            url:     '{{ route("retail.operations.expenditures.delete") }}',
            timeout: 60000,
            data:    { id:id, _token:'{{ csrf_token() }}' },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Deleted');
                    table.row('#' + row).remove().draw(false);
                    updateSelectedCount();
                    $('#singleDeleteModal').modal('hide');
                } else {
                    toastr.error(data.error || 'Failed.', 'Error');
                }
            },
            error: handleAjaxError
        });
    });

    // ════════════════════════════════════════════════════════════════════════
    //  BULK DELETE
    // ════════════════════════════════════════════════════════════════════════
    $('#deleteSelectedBtn').on('click', function(e) {
        e.preventDefault();
        var ids  = [], rows = [];
        $('.selectRow:checked').each(function() { ids.push($(this).val()); rows.push($(this).data('row-id')); });
        if (!ids.length) { toastr.warning('No expenditures selected.', 'Warning'); return; }
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
            url:     '{{ route("retail.operations.expenditures.bulkdelete") }}',
            timeout: 60000,
            data:    { ids:ids, _token:'{{ csrf_token() }}' },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    rows.forEach(function(r) { table.row('#' + r).remove(); });
                    table.draw(false);
                    updateSelectedCount();
                    toastr.success(data.success, 'Deleted');
                    $('#multipleDeleteModal').modal('hide');
                } else {
                    toastr.error(data.error || 'Failed.', 'Error');
                }
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