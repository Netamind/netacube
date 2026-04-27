@extends('master.dashboard')
@section('content')
<style>
.dt-buttons .btn {
  background: transparent !important;
  background-image: none !important;
  box-shadow: none !important;
  border-color: #5bc0de;
  color: #5bc0de;
}

.dt-buttons .btn:hover {
  background: #5bc0de !important;
  color: #fff;
}

.card-header {
  padding: 0.5rem 1.5rem !important;
  background: linear-gradient(to right, #4B5EBD, #576CC0);
  color: #fff;
}

.card-body {
  padding: 0 1.5rem 1.5rem 1.5rem !important;
}

.card-header .btn-light {
  height: 28px;
  padding: 0 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}

.card-header .btn-light:hover {
  background-color: #f8f9fa;
  transition: background-color 0.2s ease-in-out;
}

.card {
  border: none;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  border-radius: 10px;
}

.card-header h4 {
  color: #fff;
  font-weight: 600;
  margin-bottom: 0;
  display: flex;
  align-items: center;
}

.card-header h4 i {
  margin-right: 0.25rem;
}

/* ── Template card grid ── */
.templates-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 1.25rem;
  padding: 1.5rem 0 0.5rem;
}

.template-card {
  border: 1.5px solid #e3e6f0;
  border-radius: 10px;
  overflow: hidden;
  background: #fff;
  box-shadow: 0 2px 8px rgba(75,94,189,0.07);
  transition: box-shadow 0.2s, transform 0.2s, border-color 0.2s;
  display: flex;
  flex-direction: column;
}

.template-card:hover {
  box-shadow: 0 6px 20px rgba(75,94,189,0.16);
  transform: translateY(-3px);
  border-color: #4B5EBD;
}

/* Thumbnail / preview area */
.template-thumbnail {
  position: relative;
  height: 160px;
  background: #f0f2fa;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  border-bottom: 1px solid #e3e6f0;
  cursor: pointer;
}

.template-thumbnail .tmpl-icon {
  font-size: 3rem;
  color: #c5cadf;
  transition: color 0.2s;
}

.template-card:hover .template-thumbnail .tmpl-icon {
  color: #4B5EBD;
}

.template-thumbnail .tmpl-view-label {
  font-size: 10.5px;
  color: #b0b8d1;
  margin-top: 6px;
  font-family: monospace;
  background: #e8eaf4;
  padding: 2px 8px;
  border-radius: 20px;
}

.template-thumbnail .thumb-overlay {
  position: absolute;
  inset: 0;
  background: rgba(75,94,189,0);
  transition: background 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.template-card:hover .thumb-overlay {
  background: rgba(75,94,189,0.08);
}

.template-thumbnail .thumb-overlay .eye-icon {
  font-size: 2rem;
  color: #4B5EBD;
  opacity: 0;
  transition: opacity 0.2s;
}

.template-card:hover .thumb-overlay .eye-icon {
  opacity: 1;
}

/* Card info */
.template-info {
  padding: 0.65rem 0.9rem 0.5rem;
  flex: 1;
}

.template-info .tmpl-name {
  font-weight: 600;
  font-size: 13.5px;
  color: #2d3a6e;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 3px;
}

.template-info .tmpl-desc {
  font-size: 11.5px;
  color: #8d95b0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Card action buttons */
.template-actions {
  display: flex;
  border-top: 1px solid #e3e6f0;
}

.template-actions a {
  flex: 1;
  text-align: center;
  padding: 7px 0;
  font-size: 12.5px;
  color: #6c757d;
  text-decoration: none;
  transition: background 0.15s, color 0.15s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 3px;
}

.template-actions a:not(:last-child) {
  border-right: 1px solid #e3e6f0;
}

.template-actions a.btn-tmpl-preview:hover  { background: #eef0fb; color: #4B5EBD; }
.template-actions a.btn-tmpl-edit:hover     { background: #e8f7fd; color: #17a2b8; }
.template-actions a.btn-tmpl-delete:hover   { background: #fdeaea; color: #dc3545; }

/* Empty state */
.empty-templates {
  padding: 3.5rem 1rem;
  text-align: center;
  color: #b0b8d1;
}

.empty-templates i {
  font-size: 3.5rem;
  margin-bottom: 1rem;
  display: block;
}

.empty-templates p {
  margin-bottom: 1rem;
  font-size: 15px;
}

/* Preview modal */
#previewModal .modal-dialog { max-width: 880px; }

#previewModal .modal-header {
  background: linear-gradient(to right, #4B5EBD, #576CC0);
  color: #fff;
  padding: 0.5rem 1rem;
}

#previewModal .modal-header .btn-close { filter: invert(1); }
#previewModal .modal-title { font-size: 15px; font-weight: 600; }

#previewModalBody {
  padding: 0;
  height: 72vh;
  overflow: hidden;
}

#previewModalBody iframe {
  width: 100%;
  height: 100%;
  border: none;
}
</style>

<div class="progress" id="progressBar" role="progressbar" aria-label="Animated striped" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="height: 8px; transform: rotate(180deg); display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
</div>

<div class="content-page">
<div class="content">
<div class="container-fluid">

<div class="row mb-3"></div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
      <i class="ri-file-list-3-line"></i> Invoice Templates
    </h4>
    <div class="d-flex align-items-center">
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="newTemplateBtn" title="Add new template">
        <i class="ri-add-circle-line"></i>
      </a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="Info">
        <i class="ri-information-line"></i>
      </a>
    </div>
    <?php
      $templates = DB::table('invoice_templates')->get();
    ?>
  </div>

  <div class="card-body">
    @if($templates->isEmpty())
      <div class="empty-templates" id="emptyState">
        <i class="ri-file-list-3-line"></i>
        <p>No invoice templates yet.<br>Click <strong>+</strong> to add your first template.</p>
        <a href="#" class="btn btn-primary btn-sm" id="newTemplateBtnEmpty">
          <i class="ri-add-circle-line me-1"></i> Add Template
        </a>
      </div>
    @else
      <div class="templates-grid" id="templatesGrid">
        @foreach($templates as $tmpl)
          <div class="template-card" id="tmplcard-{{ $tmpl->id }}">
            <div class="template-thumbnail previewTemplateBtn"
                 data-id="{{ $tmpl->id }}"
                 data-name="{{ $tmpl->name }}"
                 title="Click to preview">
              <i class="ri-file-text-line tmpl-icon"></i>
              <span class="tmpl-view-label">{{ $tmpl->view_name }}.blade.php</span>
              <div class="thumb-overlay">
                <i class="ri-eye-line eye-icon"></i>
              </div>
            </div>
            <div class="template-info">
              <div class="tmpl-name">{{ $tmpl->name }}</div>
              <div class="tmpl-desc">{{ $tmpl->description ?: '—' }}</div>
            </div>
            <div class="template-actions">
              <a href="#"
                 class="btn-tmpl-preview previewTemplateBtn"
                 data-id="{{ $tmpl->id }}"
                 data-name="{{ $tmpl->name }}"
                 title="Preview">
                <i class="ri-eye-line"></i> Preview
              </a>
              <a href="#"
                 class="btn-tmpl-edit editTemplateBtn"
                 data-id="{{ $tmpl->id }}"
                 data-name="{{ $tmpl->name }}"
                 data-view="{{ $tmpl->view_name }}"
                 data-description="{{ $tmpl->description }}"
                 title="Edit">
                <i class="ri-edit-box-line"></i> Edit
              </a>
              <a href="#"
                 class="btn-tmpl-delete deleteTemplateBtn"
                 data-id="{{ $tmpl->id }}"
                 data-name="{{ $tmpl->name }}"
                 title="Delete">
                <i class="ri-delete-bin-line"></i> Delete
              </a>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>
</div>

</div>
</div>
</div>

{{-- ── Info Modal ── --}}
<section>
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Invoice Templates</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Manage your invoice templates. Each template maps to a Blade view stored under
        <code>resources/views/templates/invoice/</code>.
        Use <strong>+</strong> to register a new template, <strong>Preview</strong> to inspect it,
        <strong>Edit</strong> to update its details, and <strong>Delete</strong> to remove it.
      </div>
    </div>
  </div>
</div>
</section>

{{-- ── Add New Template Modal ── --}}
<section>
<div class="modal fade" id="newTemplateModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-add-circle-line me-1"></i> Add New Template</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="newTemplateForm" action="#" method="post">
          @csrf
          <div class="mb-3">
            <label class="form-label control-label">Template Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="new-name" name="name"
                   placeholder="e.g. Modern Blue" required autocomplete="off">
          </div>
          <div class="mb-3">
            <label class="form-label control-label">Blade View Name <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text text-muted" style="font-size:12px">templates/invoice/</span>
              <input type="text" class="form-control" id="new-view" name="view_name"
                     placeholder="modern_blue" required autocomplete="off">
              <span class="input-group-text text-muted" style="font-size:12px">.blade.php</span>
            </div>
            <div class="form-text">The Blade file must already exist at the path above.</div>
          </div>
          <div class="mb-3">
            <label class="form-label control-label">Description</label>
            <textarea class="form-control" id="new-description" name="description" rows="2"
                      placeholder="Brief description of this template..."></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer border-0 pt-0">
        <a href="#" class="btn btn-secondary" id="cancelNewTemplateBtn">Cancel</a>
        <a href="#" class="btn btn-primary" id="submitNewTemplateBtn">
          <i class="ri-save-line me-1"></i> Save
        </a>
      </div>
    </div>
  </div>
</div>
</section>

{{-- ── Edit Template Modal ── --}}
<section>
<div class="modal fade" id="editTemplateModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-edit-box-line me-1"></i> Update Template</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="editTemplateForm" action="#" method="post">
          @csrf
          <input type="hidden" id="edit-id" name="id">
          <div class="mb-3">
            <label class="form-label control-label">Template Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="edit-name" name="name" required autocomplete="off">
          </div>
          <div class="mb-3">
            <label class="form-label control-label">Blade View Name <span class="text-danger">*</span></label>
            <div class="input-group">
              <span class="input-group-text text-muted" style="font-size:12px">templates/invoice/</span>
              <input type="text" class="form-control" id="edit-view" name="view_name" required autocomplete="off">
              <span class="input-group-text text-muted" style="font-size:12px">.blade.php</span>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label control-label">Description</label>
            <textarea class="form-control" id="edit-description" name="description" rows="2"></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer border-0 pt-0">
        <a href="#" class="btn btn-secondary" id="cancelEditTemplateBtn">Cancel</a>
        <a href="#" class="btn btn-primary" id="submitEditTemplateBtn">
          <i class="ri-save-line me-1"></i> Update
        </a>
      </div>
    </div>
  </div>
</div>
</section>

{{-- ── Preview Modal ── --}}
<section>
<div class="modal fade" id="previewModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="ri-eye-line me-1"></i> <span id="previewModalTitle">Preview</span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="previewModalBody">
        <iframe id="previewIframe" src="" title="Invoice Preview"></iframe>
      </div>
    </div>
  </div>
</div>
</section>

{{-- ── Delete Confirm Modal ── --}}
<section>
<div class="modal fade" id="deleteTemplateModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:350px; margin:1.75rem auto;">
    <div class="modal-content">
      <div class="modal-body text-center pb-4">
        <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
        <form id="deleteTemplateForm" action="#" method="post">
          @csrf
          <div class="form-group">
            <h4 class="mt-1">Are you sure you want to delete <span id="deleteTmplLabel"></span>?</h4>
          </div>
          <div class="form-group">
            <h5>You won't be able to revert this!</h5>
          </div>
          <input type="hidden" id="deleteTmplId" name="id">
          <input type="hidden" id="deleteTmplCard">
          <div class="form-group">
            <a href="#" class="btn btn-danger" id="confirmDeleteTmplBtn" style="margin-top:10px;margin-bottom:10px;margin-right:5px">
              Yes, Delete it
            </a>
            <a href="#" class="btn btn-info" id="cancelDeleteTmplBtn" style="margin-top:10px;margin-bottom:10px;">
              No, Keep it
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
</section>

@endsection

@section('scripts')
<script>
$(document).ready(function () {

    toastr.options = {
        closeButton: true,
        progressBar: true,
        showMethod: 'slideDown',
        timeOut: 5000,
        allowHtml: true
    };

    // ── Info ──────────────────────────────────────────────────
    $('#infoBtn').click(function (e) {
        e.preventDefault();
        $('#infoModal').modal('show');
    });

    // ── Open Add Modal ────────────────────────────────────────
    $(document).on('click', '#newTemplateBtn, #newTemplateBtnEmpty', function (e) {
        e.preventDefault();
        $('#newTemplateForm')[0].reset();
        $('#newTemplateModal').modal('show');
    });

    // Auto-slug view name from template name
    $('#new-name').on('input', function () {
        var slug = $(this).val()
            .toLowerCase()
            .replace(/[^a-z0-9\s_]/g, '')
            .trim()
            .replace(/\s+/g, '_');
        $('#new-view').val(slug);
    });

    $('#cancelNewTemplateBtn').click(function (e) {
        e.preventDefault();
        $('#newTemplateModal').modal('hide');
    });

    // ── Submit: Add New Template ──────────────────────────────
    $('#submitNewTemplateBtn').click(function (e) {
        e.preventDefault();
        var self = $(this);

        var name        = $.trim($('#new-name').val());
        var view_name   = $.trim($('#new-view').val());
        var description = $.trim($('#new-description').val());

        if (!name || !view_name) {
            toastr.error('Template name and blade view name are required.', 'Validation Error');
            return;
        }

        self.prop('disabled', true);

        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        $.ajax({
            type: 'POST',
            url: '{{ route("master.invoice.template.insert") }}',
            data: { name: name, view_name: view_name, description: description },
            timeout: 60000,
            beforeSend: function () { $('#progressBar').show(); },
            complete:   function () { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function (data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    $('#newTemplateModal').modal('hide');
                    if ($('#templatesGrid').length === 0) {
                        $('#emptyState').replaceWith('<div class="templates-grid" id="templatesGrid"></div>');
                    }
                    $('#templatesGrid').prepend(buildCard(data.template));
                } else if (data.status === 422) {
                    toastr.error(data.error || 'Validation failed.', 'Error');
                } else {
                    toastr.info('Unspecified error occurred.', 'Error');
                }
            },
            error: handleAjaxError
        });
    });

    // ── Open Edit Modal ───────────────────────────────────────
    $(document).on('click', '.editTemplateBtn', function (e) {
        e.preventDefault();
        $('#edit-id').val($(this).data('id'));
        $('#edit-name').val($(this).data('name'));
        $('#edit-view').val($(this).data('view'));
        $('#edit-description').val($(this).data('description') || '');
        $('#editTemplateModal').modal('show');
    });

    $('#cancelEditTemplateBtn').click(function (e) {
        e.preventDefault();
        $('#editTemplateModal').modal('hide');
    });

    // ── Submit: Update Template ───────────────────────────────
    $('#submitEditTemplateBtn').click(function (e) {
        e.preventDefault();
        var self = $(this);

        var id          = $('#edit-id').val();
        var name        = $.trim($('#edit-name').val());
        var view_name   = $.trim($('#edit-view').val());
        var description = $.trim($('#edit-description').val());

        if (!name || !view_name) {
            toastr.error('Template name and blade view name are required.', 'Validation Error');
            return;
        }

        self.prop('disabled', true);

        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        $.ajax({
            type: 'POST',
            url: '{{ route("master.invoice.template.update") }}',
            data: { id: id, name: name, view_name: view_name, description: description },
            timeout: 60000,
            beforeSend: function () { $('#progressBar').show(); },
            complete:   function () { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function (data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    $('#editTemplateModal').modal('hide');
                    $('#tmplcard-' + data.template.id).replaceWith(buildCard(data.template));
                } else if (data.status === 422) {
                    toastr.error(data.error || 'Validation failed.', 'Error');
                } else {
                    toastr.info('Unspecified error occurred.', 'Error');
                }
            },
            error: handleAjaxError
        });
    });

    // ── Preview ───────────────────────────────────────────────
    $(document).on('click', '.previewTemplateBtn', function (e) {
        e.preventDefault();
        var id   = $(this).data('id');
        var name = $(this).data('name');
        var url  = '{{ route("master.invoice.template.preview") }}?id=' + id;
        $('#previewModalTitle').text(name);
        $('#previewIframe').attr('src', url);
        $('#previewModal').modal('show');
    });

    $('#previewModal').on('hidden.bs.modal', function () {
        $('#previewIframe').attr('src', '');
    });

    // ── Open Delete Confirm ───────────────────────────────────
    $(document).on('click', '.deleteTemplateBtn', function (e) {
        e.preventDefault();
        var id   = $(this).data('id');
        var name = $(this).data('name');
        $('#deleteTmplId').val(id);
        $('#deleteTmplCard').val('tmplcard-' + id);
        $('#deleteTmplLabel').html('<strong>' + escHtml(name) + '</strong>');
        $('#deleteTemplateModal').modal('show');
    });

    $('#cancelDeleteTmplBtn').click(function (e) {
        e.preventDefault();
        toastr.info('Your data is safe', 'Great!');
        $('#deleteTemplateModal').modal('hide');
    });

    // ── Submit: Delete ────────────────────────────────────────
    $('#confirmDeleteTmplBtn').click(function (e) {
        e.preventDefault();
        var self = $(this);
        self.prop('disabled', true);

        var id   = $('#deleteTmplId').val();
        var card = $('#deleteTmplCard').val();

        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

        $.ajax({
            type: 'POST',
            url: '{{ route("master.invoice.template.delete") }}',
            data: { id: id },
            timeout: 60000,
            beforeSend: function () { $('#progressBar').show(); },
            complete:   function () { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function (data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Deleted');
                    $('#deleteTemplateModal').modal('hide');
                    $('#' + card).fadeOut(300, function () {
                        $(this).remove();
                        if ($('.template-card').length === 0) {
                            $('#templatesGrid').replaceWith(`
                              <div class="empty-templates" id="emptyState">
                                <i class="ri-file-list-3-line"></i>
                                <p>No invoice templates yet.<br>Click <strong>+</strong> to add your first template.</p>
                                <a href="#" class="btn btn-primary btn-sm" id="newTemplateBtnEmpty">
                                  <i class="ri-add-circle-line me-1"></i> Add Template
                                </a>
                              </div>`);
                        }
                    });
                } else if (data.status === 422) {
                    toastr.error(data.error || 'Validation failed.', 'Error');
                } else {
                    toastr.info('Unspecified error occurred.', 'Error');
                }
            },
            error: handleAjaxError
        });
    });

    // ── Helpers ───────────────────────────────────────────────
    function buildCard(t) {
        var previewUrl = '{{ route("master.invoice.template.preview") }}?id=' + t.id;
        return `
          <div class="template-card" id="tmplcard-${t.id}">
            <div class="template-thumbnail previewTemplateBtn"
                 data-id="${t.id}"
                 data-name="${escHtml(t.name)}"
                 title="Click to preview">
              <i class="ri-file-text-line tmpl-icon"></i>
              <span class="tmpl-view-label">${escHtml(t.view_name)}.blade.php</span>
              <div class="thumb-overlay">
                <i class="ri-eye-line eye-icon"></i>
              </div>
            </div>
            <div class="template-info">
              <div class="tmpl-name">${escHtml(t.name)}</div>
              <div class="tmpl-desc">${escHtml(t.description || '—')}</div>
            </div>
            <div class="template-actions">
              <a href="#"
                 class="btn-tmpl-preview previewTemplateBtn"
                 data-id="${t.id}"
                 data-name="${escHtml(t.name)}"
                 title="Preview">
                <i class="ri-eye-line"></i> Preview
              </a>
              <a href="#"
                 class="btn-tmpl-edit editTemplateBtn"
                 data-id="${t.id}"
                 data-name="${escHtml(t.name)}"
                 data-view="${escHtml(t.view_name)}"
                 data-description="${escHtml(t.description || '')}"
                 title="Edit">
                <i class="ri-edit-box-line"></i> Edit
              </a>
              <a href="#"
                 class="btn-tmpl-delete deleteTemplateBtn"
                 data-id="${t.id}"
                 data-name="${escHtml(t.name)}"
                 title="Delete">
                <i class="ri-delete-bin-line"></i> Delete
              </a>
            </div>
          </div>`;
    }

    function escHtml(str) {
        return $('<div>').text(str || '').html();
    }

    function handleAjaxError(xhr, status) {
        if (status === 'timeout') {
            toastr.error('The request timed out. Please check your internet connection and try again.', 'Timeout Error');
        } else if (xhr.status === 0) {
            toastr.error('Unable to connect. Please check your internet connection and try again.', 'Connection Error');
        } else if (xhr.status === 422) {
            var errorPassage = '';
            var errors = xhr.responseJSON ? (xhr.responseJSON.errors || {}) : {};
            $.each(errors, function (key, value) { errorPassage += value + '<br>'; });
            toastr.error(errorPassage || 'Validation failed.', 'Validation Errors');
        } else if (xhr.status === 500) {
            toastr.error('Server error occurred. Please refresh the page and try again.', 'Server Error');
        } else {
            toastr.error('Unspecified error occurred. Try again later.', 'Unspecified Error');
        }
    }

});
</script>
@endsection