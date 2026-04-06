@extends('tenants.admin.dashboard')

@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.5/css/lightbox.min.css">

<style>
    /* ──────────────────────────────────────
       Export Buttons
       ────────────────────────────────────── */
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

    /* Card */
    .card-header {
        padding: 0.5rem 1.5rem !important;
        background: linear-gradient(to right, #4B5EBD, #576CC0);
        color: #fff;
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
    }
    .card-body { padding: 0 1.5rem 1.5rem 1.5rem !important; }

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
        transition: background-color .2s ease-in-out;
    }

    .card {
        border: none;
        box-shadow: 0 4px 8px rgba(0,0,0,.1);
        border-radius: 10px;
        overflow: hidden;
    }
    .card-header h4 {
        color: #fff;
        font-weight: 600;
        margin-bottom: 0;
        display: flex;
        align-items: center;
    }
    .card-header h4 i { margin-right: .25rem; }

    /* Fixed header */
    table.dataTable.fixedHeader-floating,
    table.dataTable.fixedHeader-locked {
        background: #fff !important;
        border-bottom: none !important;
    }
    table.dataTable thead th.fixedHeader-floating {
        background: #e2e2e9 !important;
    }

    /* Tabs */
    .tab-header-container {
        background: #f8f9fa;
        border-top: 1px solid #dee2e6;
    }
    .nav-pills .nav-link {
        border-radius: 0 !important;
        padding: 0.75rem 1rem;
        font-weight: 500;
        color: #495057;
        border-bottom: 3px solid transparent;
        transition: all 0.2s ease;
    }
    .nav-pills .nav-link:hover {
        background-color: #e9ecef;
        color: #4B5EBD;
    }
    .nav-pills .nav-link.active {
        background-color: transparent !important;
        color: #4B5EBD !important;
        border-bottom-color: #4B5EBD;
        font-weight: 600;
    }
    .nav-pills .nav-link i {
        font-size: 1.1rem;
        margin-right: 0.35rem;
    }

    .tab-content {
        margin-top: 0 !important;
        padding-top: 0.75rem;
    }

    /* Action icons */
    .action-icon {
        font-size: 17px;
        font-weight: bold;
        margin: 0 4px;
        text-decoration: none;
    }

    /* File icon */
    .file-icon {
        font-size: 28px;
    }
</style>

<div class="progress" id="progressBar"
     role="progressbar" aria-label="Animated striped"
     aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"
     style="height:8px; transform:rotate(180deg); display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated"
         style="width:100%"></div>
</div>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">

            <div class="row mb-3"></div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title mb-0">
                         <i class="ri-building-line"></i> Company Info
                    </h4>
                    <div class="d-flex align-items-center gap-1">
                        <a href="#" class="btn btn-light text-primary fs-16" id="companyInfoBtn" title="Info">
                            <i class="ri-information-line"></i>
                        </a>
                    </div>
                </div>

                <div class="tab-header-container">
                    <ul class="nav nav-pills nav-justified mb-0">
                        <li class="nav-item">
                            <a href="#general" data-bs-toggle="tab" class="nav-link active">
                               <i class="ri-file-list-3-line"></i> General
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#contact" data-bs-toggle="tab" class="nav-link">
                             <i class="ri-contacts-book-line"></i> Contact
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#files" data-bs-toggle="tab" class="nav-link">
                               <i class="ri-folder-2-line"></i> Files
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content">

                        <!-- General Tab -->
                        <div class="tab-pane show active" id="general">
                            <?php $generalData = DB::connection('tenant')->table('company_info')->where('id',1)->first(); ?>
                            <form class="form-horizontal" id="generalDataForm" method="post">
                                @csrf
                                <div class="row mb-3">
                                    <label class="col-3 col-form-label">Business/Company name</label>
                                    <div class="col-9">
                                        <input type="text" class="form-control" name="business_name" value="{{optional($generalData)->business_name}}">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-3 col-form-label">License Number</label>
                                    <div class="col-9">
                                        <input type="text" class="form-control" name="business_license_number" value="{{optional($generalData)->business_license_number}}">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-3 col-form-label">TIN Number</label>
                                    <div class="col-9">
                                        <input type="text" class="form-control" name="tin_number" value="{{optional($generalData)->tin_number}}">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-3 col-form-label">Company Description</label>
                                    <div class="col-9">
                                        <textarea name="business_description" class="form-control">{{optional($generalData)->business_description}}</textarea>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-3 col-form-label">Company Mission</label>
                                    <div class="col-9">
                                        <textarea name="business_mission" class="form-control">{{optional($generalData)->business_mission}}</textarea>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-3 col-form-label">Company Vision</label>
                                    <div class="col-9">
                                        <textarea name="business_vision" class="form-control">{{optional($generalData)->business_vision}}</textarea>
                                    </div>
                                </div>
                                <div class="justify-content-end row">
                                    <div class="col-9 text-end">
                                        <button type="button" class="btn btn-primary" id="updateGeneralDataBtn">Update</button>
                                    </div>
                                </div>
                            </form>   
                        </div>

                        <!-- Contact Tab -->
                        <div class="tab-pane" id="contact">
                            <?php $contactData = DB::connection('tenant')->table('company_info')->where('id',1)->first(); ?>
                            <form class="form-horizontal" id="contactDataForm" method="post">
                                @csrf
                                <div class="row mb-3">
                                    <label class="col-3 col-form-label">Primary phone number</label>
                                    <div class="col-9">
                                        <input type="text" class="form-control" name="primary_number" value="{{optional($contactData)->primary_number}}">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-3 col-form-label">Secondary phone number</label>
                                    <div class="col-9">
                                        <input type="text" class="form-control" name="secondary_number" value="{{optional($contactData)->secondary_number}}">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-3 col-form-label">Email address</label>
                                    <div class="col-9">
                                        <input type="text" class="form-control" name="email_address" value="{{optional($contactData)->email_address}}">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-3 col-form-label">Physical address</label>
                                    <div class="col-9">
                                        <textarea name="physical_address" class="form-control">{{optional($contactData)->physical_address}}</textarea>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-3 col-form-label">Postal address</label>
                                    <div class="col-9">
                                        <textarea name="postal_address" class="form-control">{{optional($contactData)->postal_address}}</textarea>
                                    </div>
                                </div>
                                <div class="justify-content-end row">
                                    <div class="col-9 text-end">
                                        <button type="button" class="btn btn-primary" id="updateContactDataBtn">Update</button>
                                    </div>
                                </div>
                            </form>   
                        </div>

                        <!-- Files Tab -->
                        <div class="tab-pane" id="files">
                            <div class="files-controls d-flex justify-content-between align-items-center mb-3">
                                <div class="add-new-group">
                                    <div class="btn-group me-2">
                                        <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="ri-add-line me-1"></i> Add New
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" id="addDocumentBtn"><i class="ri-file-add-line me-1"></i> Document</a></li>
                                            <li><a class="dropdown-item" href="#" id="addImageBtn"><i class="ri-image-add-line me-1"></i> Image</a></li>
                                        </ul>
                                    </div>

                                    <button type="button" class="btn btn-danger btn-sm" id="bulkDeleteBtn" style="display:none;">
                                        <i class="ri-delete-bin-line"></i> Delete Selected (<span id="selectedCount">0</span>)
                                    </button>
                                </div>

                                <div class="app-search">
                                    <form>
                                        <div class="position-relative">
                                            <input type="text" class="form-control" placeholder="Search files..." id="filesSearch">
                                            <span class="ri-search-line search-icon"></span>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="mt-3">
                                <table class="table table-bordered table-hover" id="filesTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:40px;" class="text-center">
                                                <input type="checkbox" id="selectAllFiles">
                                            </th>
                                            <th>File Name</th>
                                            <th style="width: 150px;">Uploaded Date</th>
                                            <th style="width: 100px;" class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="filesTbody">
                                        @php
                                            $files = DB::connection('tenant')->table('company_files')->orderByDesc('created_at')->get();
                                        @endphp

                                        @if($files->count() == 0)
                                            <tr>
                                                <td colspan="4" class="text-center py-5 text-muted">
                                                    <i class="ri-folder-open-line fs-48 d-block mb-3"></i> No files uploaded yet
                                                </td>
                                            </tr>
                                        @else
                                            @foreach($files as $file)
                                                @php
                                                    $ext = strtolower(pathinfo($file->filename, PATHINFO_EXTENSION));
                                                    $isImage = in_array($ext, ['jpg','jpeg','png','gif']);
                                                @endphp
                                                <tr data-file-id="{{ $file->id }}">
                                                    <td class="text-center">
                                                        <input type="checkbox" class="file-checkbox" value="{{ $file->id }}">
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            @if($isImage)
                                                                <i class="ri-image-line file-icon text-success"></i>
                                                            @else
                                                                <i class="ri-file-text-line file-icon text-primary"></i>
                                                            @endif

                                                            <div>
                                                                <strong class="d-block file-name">{{ $file->name }}</strong>
                                                                <small class="text-muted">{{ $file->filename }} ({{ number_format($file->size / 1024, 1) }} KB)</small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>{{ \Carbon\Carbon::parse($file->created_at)->format('d M Y') }}</td>
                                                    <td class="text-center">
                                                        <div class="dropdown">
                                                            <a href="#" class="text-muted" data-bs-toggle="dropdown">
                                                                <i class="ri-more-2-fill fs-18"></i>
                                                            </a>
                                                            <ul class="dropdown-menu dropdown-menu-end">
                                                                <li>
                                                                    <a class="dropdown-item preview-file" href="#"
                                                                       data-url="{{ asset('files/tenants/company/' . $file->filename) }}"
                                                                       data-ext="{{ $ext }}">
                                                                        <i class="ri-eye-line me-2"></i> View
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item edit-file" href="#"
                                                                    data-id="{{ $file->id }}"
                                                                    data-name="{{ $file->name }}">
                                                                        <i class="ri-pencil-line me-2"></i> Edit Name
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item"
                                                                    href="{{ route('tenant.admin.company.download.file') }}?id={{ $file->id }}">
                                                                        <i class="ri-download-2-line me-2"></i> Download
                                                                    </a>
                                                                </li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <a class="dropdown-item text-danger delete-file" href="#"
                                                                    data-id="{{ $file->id }}"
                                                                    data-name="{{ $file->name }}">
                                                                        <i class="ri-delete-bin-line me-2"></i> Delete
                                                                    </a>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div> <!-- /.tab-content -->
                </div> <!-- /.card-body -->
            </div> <!-- /.card -->

        </div> <!-- /.container-fluid -->
    </div> <!-- /.content -->
</div> <!-- /.content-page -->

<!-- ==================== MODALS ==================== -->

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocumentModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="uploadDocumentForm" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-file-add-line me-2"></i> Add Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">File</label><input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx" required></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Upload</button></div>
            </div>
        </form>
    </div>
</div>

<!-- Upload Image Modal -->
<div class="modal fade" id="uploadImageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-image-add-line me-2"></i> Add Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Name</label><input type="text" id="imageNameInput" class="form-control" required></div>
                <div class="mb-3"><label class="form-label">File</label><input type="file" id="imageFileInput" accept="image/*" class="form-control" required></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-primary" id="startCropBtn">Crop & Upload</button></div>
        </div>
    </div>
</div>

<!-- Crop Modal -->
<div class="modal modal-flex" id="cropModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crop Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="cropper-container"><img id="cropImage" class="img-fluid"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary" id="uploadCroppedBtn">Submit</button></div>
        </div>
    </div>
</div>

<!-- Edit File Name Modal -->
<div class="modal fade" id="editFileModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit file name</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editFileForm">
                    @csrf
                    <input type="hidden" name="id" id="editFileId">
                    <div class="mb-3">
                        <label class="form-label fw-bold">New Name</label>
                        <input type="text" class="form-control" name="name" id="editNameInput" autocomplete="off" required>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line"></i> Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Company Info Modal -->
<div class="modal fade" id="companyInfoModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Company Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Centralized Company Profile Management</strong></p>
                <p class="text-muted fs-14">This section is the single source of truth for company data used across invoices, receipts, and reports.</p>
                <hr>
                <p><strong>General:</strong> Business name, license, TIN, mission/vision.</p>
                <p><strong>Contact:</strong> Phone, email, addresses.</p>
                <p><strong>Files:</strong> Upload and manage documents and images.</p>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">File Preview - <span id="previewFileName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="previewBody">
                <!-- Iframe will be injected dynamically -->
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteFileModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog" style="max-width:350px; margin:1.75rem auto;">
        <div class="modal-content">
            <div class="modal-body text-center pb-4">
                <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
                <form action="#" method="post" id="deleteFileForm">
                    @csrf
                    <div class="form-group">
                        <h4 id="bulkDeleteText" style="display:none;">Delete <span id="bulkCountText"></span> selected files?</h4>
                        <h4 id="singleDeleteText">Are you sure you want to delete <span id="deleteFileName"></span>?</h4>  
                    </div>
                    <div class="form-group">
                        <h5>You won't be able to revert this!</h5>
                    </div>
                    <div class="form-group">
                        <input type="hidden" id="deleteFileId">
                        <input type="hidden" id="deleteBulkIds" name="ids">
                    </div>
                    <div class="form-group">
                        <a href="#" class="btn btn-danger" id="confirmDeleteFileBtn" style="margin-top:10px;margin-bottom:10px;margin-right:5px">Yes, Delete it</a>
                        <a href="#" class="btn btn-info" id="cancelDeleteFileBtn" style="margin-top:10px;margin-bottom:10px;">No, Keep it</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.5/js/lightbox.min.js"></script>

<script>
$(document).ready(function() {
    toastr.options = { closeButton: true, progressBar: true, showMethod: 'slideDown', timeOut: 5000 };

    $("#companyInfoBtn").click(e => { e.preventDefault(); $("#companyInfoModal").modal('show'); });

    // File Selection
    const $selectAll  = $('#selectAllFiles');
    const $checkboxes = $('.file-checkbox');
    const $bulkBtn    = $('#bulkDeleteBtn');
    const $count      = $('#selectedCount');

    function refreshUI() {
        const checked = $checkboxes.filter(':checked').length;
        $count.text(checked);
        $bulkBtn.toggle(checked > 0);
    }

    $selectAll.change(function() {
        $checkboxes.prop('checked', this.checked);
        refreshUI();
    });

    $(document).on('change', '.file-checkbox', function() {
        $selectAll.prop('checked', $checkboxes.length === $checkboxes.filter(':checked').length);
        refreshUI();
    });

    // Bulk Delete
    $bulkBtn.click(function() {
        const ids = $checkboxes.filter(':checked').map(function() { return this.value; }).get();
        $('#deleteBulkIds').val(ids.join(','));
        $('#bulkCountText').text(ids.length);
        $('#singleDeleteText').hide();
        $('#bulkDeleteText').show();
        $('#deleteFileModal').modal('show');
    });

    $(document).on('click', '.delete-file', function(e) {
        e.preventDefault();
        $('#deleteFileId').val($(this).data('id'));
        $('#deleteFileName').text($(this).data('name'));
        $('#deleteBulkIds').val('');
        $('#singleDeleteText').show();
        $('#bulkDeleteText').hide();
        $('#deleteFileModal').modal('show');
    });

    // Confirm Delete
    $('#confirmDeleteFileBtn').click(function(e) {
        e.preventDefault();
        const bulkIds = $('#deleteBulkIds').val();
        const singleId = $('#deleteFileId').val();

        const url = bulkIds 
            ? "{{ route('tenant.admin.company.files.bulk-delete') }}" 
            : "{{ route('tenant.admin.company.delete.file') }}";

        const data = { _token: $('meta[name="csrf-token"]').attr('content') };
        if (bulkIds) data.ids = bulkIds.split(',');
        else data.id = singleId;

        $('#progressBar').show();

        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            success: function(response) {
                toastr.success(response.success || 'Deleted successfully');
                $('#deleteFileModal').modal('hide');
                loadFiles();
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.error || 'Delete failed');
            },
            complete: () => $('#progressBar').hide()
        });
    });

    $('#cancelDeleteFileBtn').click(e => { 
        e.preventDefault(); 
        $('#deleteFileModal').modal('hide'); 
    });

    // Update General & Contact
    $('#updateGeneralDataBtn').click(function(e) {
        e.preventDefault();
        const self = $(this);
        self.prop("disabled", true);

        $.ajax({
            type: "POST",
            url: "{{ route('tenant.admin.company.general.info.update') }}",
            data: $("#generalDataForm").serialize(),
            beforeSend: () => $('#progressBar').show(),
            complete: () => { 
                $('#progressBar').hide(); 
                self.prop("disabled", false); 
            },
            success: function(data) {
                toastr.success(data.success || 'General information updated successfully');
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.error || 'Error updating general information');
            }
        });
    });

    $('#updateContactDataBtn').click(function(e) {
        e.preventDefault();
        const self = $(this);
        self.prop("disabled", true);

        $.ajax({
            type: "POST",
            url: "{{ route('tenant.admin.company.contact.info.update') }}",
            data: $("#contactDataForm").serialize(),
            beforeSend: () => $('#progressBar').show(),
            complete: () => { 
                $('#progressBar').hide(); 
                self.prop("disabled", false); 
            },
            success: function(data) {
                toastr.success(data.success || 'Contact information updated successfully');
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.error || 'Error updating contact information');
            }
        });
    });

    let cropper;

    // Load Files
    function loadFiles() {
        $('#filesTbody').load("{{ route('tenant.admin.company.files.list') }} #filesTbody > *", function() {
            refreshUI();
        });
    }

    // Search
    let searchTimeout;
    $('#filesSearch').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const value = $(this).val().toLowerCase();
            $('#filesTbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });

            if ($('#filesTbody tr:visible').length === 0) {
                if ($('#noResultsRow').length === 0) {
                    $('#filesTbody').append('<tr id="noResultsRow"><td colspan="4" class="text-center py-5 text-muted"><i class="ri-folder-open-line fs-48 d-block mb-3"></i>No files found</td></tr>');
                }
            } else {
                $('#noResultsRow').remove();
            }
        }, 350);
    });

    $('#addDocumentBtn').click(e => { e.preventDefault(); $('#uploadDocumentModal').modal('show'); });
    $('#addImageBtn').click(e => { e.preventDefault(); $('#uploadImageModal').modal('show'); });

    // Upload Document
    $('#uploadDocumentForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button');
        btn.prop('disabled', true).text('Uploading...');

        const fd = new FormData(this);
        fd.append('_token', $('meta[name="csrf-token"]').attr('content'));

        $.ajax({
            url: "{{ route('tenant.admin.company.upload.document') }}",
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            beforeSend: () => $('#progressBar').show(),
            complete: () => {
                $('#progressBar').hide();
                btn.prop('disabled', false).text('Upload');
                $('#uploadDocumentModal').modal('hide');
            },
            success: function(r) {
                toastr.success(r.success || 'Document uploaded successfully');
                loadFiles();
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.error || 'Upload failed');
            }
        });
    });

    // Image Crop & Upload
    $('#startCropBtn').click(() => {
        const file = $('#imageFileInput')[0].files[0];
        const name = $('#imageNameInput').val().trim();
        if (!file || !name) return toastr.error('Name and file are required');

        const reader = new FileReader();
        reader.onload = e => {
            $('#cropImage').attr('src', e.target.result);
            $('#uploadImageModal').modal('hide');
            $('#cropModal').modal('show');
            cropper = new Cropper($('#cropImage')[0], { viewMode: 1 });
        };
        reader.readAsDataURL(file);
    });

    $('#uploadCroppedBtn').click(() => {
        cropper.getCroppedCanvas().toBlob(blob => {
            const fd = new FormData();
            fd.append('name', $('#imageNameInput').val());
            fd.append('file', blob, 'cropped.jpg');
            fd.append('_token', $('meta[name="csrf-token"]').attr('content'));

            $.ajax({
                url: "{{ route('tenant.admin.company.upload.image') }}",
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                beforeSend: () => $('#progressBar').show(),
                complete: () => {
                    $('#progressBar').hide();
                    $('#cropModal').modal('hide');
                    if (cropper) cropper.destroy();
                },
                success: function(r) {
                    toastr.success(r.success || 'Image uploaded successfully');
                    loadFiles();
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.error || 'Upload failed');
                }
            });
        });
    });

    $('#cropModal').on('hidden.bs.modal', () => {
        if (cropper) cropper.destroy();
    });

    // Edit File Name
    $(document).on('click', '.edit-file', function(e) {
        e.preventDefault();
        $('#editFileId').val($(this).data('id'));
        $('#editNameInput').val($(this).data('name'));
        $('#editFileModal').modal('show');
    });

    $('#editFileForm').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: "{{ route('tenant.admin.company.edit.name') }}",
            method: 'POST',
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            beforeSend: () => $('#progressBar').show(),
            success: function(response) {
                toastr.success(response.success || 'File name updated successfully');
                $('#editFileModal').modal('hide');
                loadFiles();
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.error || 'Failed to update name');
            },
            complete: () => $('#progressBar').hide()
        });
    });

    // Preview File (Improved - dynamic iframe)
    $(document).on('click', '.preview-file', function(e) {
        e.preventDefault();
        const url  = $(this).data('url');
        const ext  = $(this).data('ext').toLowerCase();
        const name = $(this).closest('tr').find('.file-name').text().trim();

        $('#previewFileName').text(name);

        const $body = $('#previewBody');
        $body.empty();

        const $iframe = $('<iframe>', {
            id: 'previewIframe',
            style: 'width:100%; height:75vh; border:none; background:#f8f9fa;',
            frameborder: '0'
        });

        $body.append($iframe);

        setTimeout(() => {
            if (['jpg','jpeg','png','gif'].includes(ext)) {
                const html = `
                    <!DOCTYPE html>
                    <html><head><style>
                        body { margin:0; padding:20px; background:#f8f9fa; display:flex; align-items:center; justify-content:center; min-height:100vh; }
                        img { max-width:100%; max-height:90vh; object-fit:contain; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
                    </style></head><body><img src="${url}" alt="${name}"></body></html>
                `;
                $iframe.attr('srcdoc', html);
            } else if (ext === 'pdf') {
                $iframe.attr('src', url);
            } else {
                const viewerUrl = 'https://docs.google.com/gview?url=' + encodeURIComponent(url) + '&embedded=true';
                $iframe.attr('src', viewerUrl);
            }

            $('#previewModal').modal('show');
        }, 100);
    });

    // Cleanup on modal close
    $('#previewModal').on('hidden.bs.modal', function () {
        $('#previewBody').empty();
    });

});
</script>
@endsection