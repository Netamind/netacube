@extends('operations.retail.dashboard')
@section('content')

@php
    $expenditureTypes = DB::connection('tenant')
        ->table('retail_expenditure_types')
        ->orderBy('name')
        ->get();

    $maintableTitle = 'Retail Expenditure Types';
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

/* ── Status badges ──────────────────────────────────────────────── */
.status-badge-active   { font-size:10px; font-weight:600; background:#e8f5e9; color:#2d6a4f; border:1px solid #a5d6a7; border-radius:10px; padding:1px 7px; white-space:nowrap; }
.status-badge-inactive { font-size:10px; font-weight:600; background:#f1f3f5; color:#6c757d; border:1px solid #ced4da; border-radius:10px; padding:1px 7px; white-space:nowrap; }

/* ── Modal headers ───────────────────────────────────────────────── */
.mh-blue   { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-danger { background:linear-gradient(135deg,#c0392b,#e74c3c); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-title  { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close  { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }

/* ── Bulk action cards ───────────────────────────────────────────── */
.bulk-option-card { display:flex; align-items:center; gap:12px; padding:14px 16px; border-radius:10px; border:1.5px solid #e9ecef; cursor:pointer; transition:border-color .15s,background .15s; margin-bottom:10px; }
.bulk-option-card:last-child { margin-bottom:0; }
.bulk-option-card:hover { border-color:#c8d0ed; background:#f8f9ff; }
.bulk-option-card .boc-icon { width:40px; height:40px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:19px; flex-shrink:0; }
.boc-title { font-size:14px; font-weight:700; color:#1e293b; }
.boc-desc  { font-size:12px; color:#6c757d; margin-top:1px; }
.boc-icon-active   { background:#ecfdf5; color:#059669; }
.boc-icon-inactive { background:#fff7ed; color:#c2410c; }
.boc-icon-delete   { background:#fef2f2; color:#dc2626; }
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
      Expenditure Types
    </h4>
    <div class="card-header-actions">
      <button type="button" class="btn btn-light text-primary fs-16 mx-1" id="bulkActionsHeaderBtn" disabled title="Select rows to enable bulk actions">
        <i class="ri-stack-line"></i>
        <span class="bah-count" id="bulkActionsHeaderCount"></span>
      </button>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="newDataBtn"      title="Add type"><i class="ri-add-circle-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn"         title="About Expenditure Types"><i class="ri-information-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download"><i class="ri-download-line"></i></a>
    </div>
  </div>

  <div class="card-body">
    <table id="maintable" class="table table-sm table-striped row-border order-column w-100 mt-3">
      <thead style="background-color:#e2e2e9">
        <tr>
          <th>Name</th>
          <th>Description</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody id="tbody">
        @foreach($expenditureTypes as $type)
          @php $row = 'etrow' . $type->id; @endphp
          <tr id="{{ $row }}">
            <td>
              <input type="checkbox" class="selectRow" value="{{ $type->id }}" data-row-id="{{ $row }}">
              &nbsp;{{ $type->name }}
            </td>
            <td>{{ $type->description ?? '—' }}</td>
            <td>
              @if($type->status === 'active')
                <span class="status-badge-active"><i class="ri-checkbox-circle-line me-1"></i>Active</span>
              @else
                <span class="status-badge-inactive"><i class="ri-close-circle-line me-1"></i>Inactive</span>
              @endif
            </td>
            <td>
              <a href="#" class="editDataBtn"
                 data-id="{{ $type->id }}" data-row="{{ $row }}"
                 data-name="{{ $type->name }}" data-description="{{ $type->description }}"
                 data-status="{{ $type->status }}">
                <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px"></i>
              </a>
              <a href="#" class="deleteDataBtn"
                 data-label="{{ $type->name }}" data-id="{{ $type->id }}" data-row="{{ $row }}">
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
      <div class="bulk-option-card" id="bulkMarkActiveBtn">
        <div class="boc-icon boc-icon-active"><i class="ri-checkbox-circle-line"></i></div>
        <div><div class="boc-title">Mark Active</div><div class="boc-desc">Make the selected types selectable for new expenditures.</div></div>
      </div>
      <div class="bulk-option-card" id="bulkMarkInactiveBtn">
        <div class="boc-icon boc-icon-inactive"><i class="ri-close-circle-line"></i></div>
        <div><div class="boc-title">Mark Inactive</div><div class="boc-desc">Hide from new entries while keeping historical records intact.</div></div>
      </div>
      <div class="bulk-option-card" id="deleteSelectedBtn">
        <div class="boc-icon boc-icon-delete"><i class="ri-delete-bin-line"></i></div>
        <div><div class="boc-title">Delete Selected</div><div class="boc-desc">Types still used by an expenditure will be skipped automatically.</div></div>
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
    <div class="modal-header mh-blue"><h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Expenditure Types</h5><button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body" style="padding:18px 20px;">
      <table style="width:100%;border-collapse:collapse;font-size:13px;">
        <tbody>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569;width:140px;border-bottom:1px solid #f1f5f9">Expenditure Types</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9">Categories of spend (e.g. Rent, Utilities, Repairs) used when recording an expenditure.</td></tr>
          <tr><td style="padding:8px 12px;font-weight:700;color:#475569">Status</td><td style="padding:8px 12px">Inactive types stay in the list for historical records but should no longer be selected for new expenditures.</td></tr>
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
        <h5 class="modal-title mh-title"><i class="ri-add-circle-line"></i> Add Expenditure Type</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:16px 20px 8px !important;">
        <form id="newDataForm">
          @csrf
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Name <span class="text-danger">*</span></label>
            <input class="form-control" type="text" id="new-name" placeholder="e.g. Rent" autocomplete="off" />
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold" style="font-size:13px">Description <small class="text-muted fw-normal">(optional)</small></label>
            <textarea class="form-control" id="new-description" rows="2" placeholder="Brief description…"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer" style="padding:10px 20px 14px;justify-content:flex-end;gap:8px;">
        <a href="#" class="btn btn-secondary btn-sm" id="cancelDataBtn">Cancel</a>
        <a href="#" class="btn btn-success btn-sm"   id="submitDataBtn"><i class="ri-check-line"></i> Save Type</a>
      </div>
    </div>
  </div>
</div>

{{-- ══ EDIT MODAL ══ --}}
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-edit-box-line"></i> Update Expenditure Type</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:16px 18px 8px !important;">
        <form id="editDataForm">
          @csrf
          <input type="hidden" id="editId">
          <input type="hidden" id="editRow">
          <div class="mb-2">
            <label class="form-label fw-semibold" style="font-size:13px">Name <span class="text-danger">*</span></label>
            <input class="form-control form-control-sm" type="text" id="editName" autocomplete="off" required />
          </div>
          <div class="mb-2">
            <label class="form-label fw-semibold" style="font-size:13px">Description</label>
            <textarea class="form-control form-control-sm" id="editDescription" rows="2"></textarea>
          </div>
          <div class="mb-2">
            <label class="form-label fw-semibold d-block" style="font-size:13px">Status</label>
            <div class="d-flex gap-3">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="edit_status" id="editStatusActive" value="active">
                <label class="form-check-label" for="editStatusActive">
                  <span class="status-badge-active"><i class="ri-checkbox-circle-line me-1"></i>Active</span>
                </label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="edit_status" id="editStatusInactive" value="inactive">
                <label class="form-check-label" for="editStatusInactive">
                  <span class="status-badge-inactive"><i class="ri-close-circle-line me-1"></i>Inactive</span>
                </label>
              </div>
            </div>
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
        <h5 class="modal-title mh-title"><i class="ri-delete-bin-line"></i> Delete Type</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center py-4">
        <i class="ri-error-warning-line text-danger" style="font-size:60px"></i>
        <h5 class="mt-2 mb-1">Delete <span id="singleDeleteLabel" class="text-danger"></span>?</h5>
        <p style="font-size:13px;color:#6c757d;margin-bottom:0;">
          Cannot be undone. If this type is still used by any expenditure, it will be skipped instead.
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
        <h5 class="mt-2 mb-1">Delete <span id="multipleDeleteCount" class="text-danger">0</span> type(s)?</h5>
        <p style="font-size:13px;color:#6c757d;margin-bottom:0;">
          Cannot be undone. Types still used by an expenditure will be skipped — you'll see how many.
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

    function statusBadge(status) {
        return status === 'active'
            ? '<span class="status-badge-active"><i class="ri-checkbox-circle-line me-1"></i>Active</span>'
            : '<span class="status-badge-inactive"><i class="ri-close-circle-line me-1"></i>Inactive</span>';
    }

    function buildRow(t) {
        function d(v) { return (v || '').toString().replace(/"/g, '&quot;'); }
        return `<tr id="${t.row}">
            <td>
                <input type="checkbox" class="selectRow" value="${t.id}" data-row-id="${t.row}">
                &nbsp;${t.name}
            </td>
            <td>${t.description || '—'}</td>
            <td>${statusBadge(t.status)}</td>
            <td>
                <a href="#" class="editDataBtn"
                   data-id="${t.id}" data-row="${t.row}"
                   data-name="${d(t.name)}" data-description="${d(t.description)}"
                   data-status="${t.status}">
                   <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px"></i>
                </a>
                <a href="#" class="deleteDataBtn"
                   data-label="${d(t.name)}" data-id="${t.id}" data-row="${t.row}">
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

    $('#bulkActionsHeaderBtn').on('click', function() {
        if (!$(this).hasClass('enabled')) return;
        $('#bulkActionsModalCountText').text('— ' + $('.selectRow:checked').length + ' selected');
        $('#bulkActionsModal').modal('show');
    });

    // ════════════════════════════════════════════════════════════════════════
    //  ADD
    // ════════════════════════════════════════════════════════════════════════
    $('#newDataBtn').on('click', function(e) { e.preventDefault(); resetNewModal(); $('#newDataModal').modal('show'); });
    $('#newDataModal').on('hidden.bs.modal', resetNewModal);
    $('#cancelDataBtn').on('click', function(e) { e.preventDefault(); $('#newDataModal').modal('hide'); });

    function resetNewModal() {
        $('#new-name, #new-description').val('');
    }

    $('#submitDataBtn').on('click', function(e) {
        e.preventDefault();
        var name = $('#new-name').val().trim();
        if (!name) { toastr.warning('Name is required.', 'Required'); $('#new-name').focus(); return; }

        var self = $(this); self.prop('disabled', true);
        $.ajaxSetup({ headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:    'POST',
            url:     '{{ route("retail.operations.expendituretypes.insert") }}',
            timeout: 60000,
            data: {
                name:        name,
                description: $('#new-description').val(),
                _token:      '{{ csrf_token() }}'
            },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    table.row.add($(buildRow(data.type))).draw(false);
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
        $('#editDescription').val(b.data('description'));
        if (b.data('status') === 'active') $('#editStatusActive').prop('checked', true);
        else                                $('#editStatusInactive').prop('checked', true);
        $('#editDataModal').modal('show');
    });

    $('#cancelEditBtn').on('click', function(e) { e.preventDefault(); $('#editDataModal').modal('hide'); });

    $('#submitUpdateBtn').on('click', function(e) {
        e.preventDefault();
        var name = $('#editName').val().trim();
        if (!name) { toastr.warning('Name is required.', 'Required'); $('#editName').focus(); return; }

        var self = $(this); self.prop('disabled', true);
        var row  = $('#editRow').val();

        $.ajaxSetup({ headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type:    'POST',
            url:     '{{ route("retail.operations.expendituretypes.update") }}',
            timeout: 60000,
            data: {
                id:          $('#editId').val(),
                name:        name,
                description: $('#editDescription').val(),
                status:      $('input[name="edit_status"]:checked').val() || 'active',
                _token:      '{{ csrf_token() }}'
            },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    table.row('#' + row).remove();
                    table.row.add($(buildRow(data.type))).draw(false);
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
            url:     '{{ route("retail.operations.expendituretypes.delete") }}',
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
    //  BULK DELETE
    // ════════════════════════════════════════════════════════════════════════
    $('#deleteSelectedBtn').on('click', function(e) {
        e.preventDefault();
        var ids  = [], rows = [];
        $('.selectRow:checked').each(function() { ids.push($(this).val()); rows.push($(this).data('row-id')); });
        if (!ids.length) { toastr.warning('No types selected.', 'Warning'); return; }
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
            url:     '{{ route("retail.operations.expendituretypes.bulkdelete") }}',
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
    //  BULK STATUS
    // ════════════════════════════════════════════════════════════════════════
    function doBulkStatus(status) {
        var selected = []; $('.selectRow:checked').each(function() { selected.push($(this).val()); });
        if (!selected.length) { toastr.warning('No types selected.', 'Warning'); return; }
        $.ajaxSetup({ headers:{'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')} });
        $.ajax({
            type: 'POST', url: '{{ route("retail.operations.expendituretypes.bulkstatus") }}',
            data: { ids:selected, status:status, _token:'{{ csrf_token() }}' }, timeout:60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    $.each(data.types, function(i,t) { table.row('#'+t.row).remove(); table.row.add($(buildRow(t))); });
                    table.draw(false); updateSelectedCount(); $('#bulkActionsModal').modal('hide');
                } else { toastr.error(data.error || 'Failed.', 'Error'); }
            },
            error: handleAjaxError
        });
    }
    $('#bulkMarkActiveBtn').on('click',   function(e) { e.preventDefault(); doBulkStatus('active'); });
    $('#bulkMarkInactiveBtn').on('click', function(e) { e.preventDefault(); doBulkStatus('inactive'); });

    // ════════════════════════════════════════════════════════════════════════
    //  ROW SELECTION
    // ════════════════════════════════════════════════════════════════════════
    $('#selectAll').on('change', function() { $('.selectRow').prop('checked', this.checked); updateSelectedCount(); });
    $('#tbody').on('click', '.selectRow', function() { updateSelectedCount(); });

});
</script>
@endsection