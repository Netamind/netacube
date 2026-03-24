@extends('tenants.super-admin.dashboard')

@section('content')
<style>
    .dt-buttons .btn {
        background: transparent !important;
        background-image: none !important;
        box-shadow: none !important;
        border-color: #5bc0de;
        color: #5bc0de;  
    }
    .dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }
    .card-header { padding: 0.5rem 1.5rem !important; background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff; }
    .card-body { padding: 0 1.5rem 1.5rem 1.5rem !important; }
    .card-header .btn-light { height: 28px; padding: 0 10px; display: flex; align-items: center; justify-content: center; line-height: 1; }
    .card-header .btn-light:hover { background-color: #f8f9fa; transition: background-color 0.2s ease-in-out; }
    .card { border: none; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); border-radius: 10px; }
    .card-header h4 { color: #fff; font-weight: 600; margin-bottom: 0; display: flex; align-items: center; }
    .card-header h4 i { margin-right: 0.25rem; }
    table.dataTable.fixedHeader-floating, table.dataTable.fixedHeader-locked { background: #fff !important; border-bottom: none !important; }
    table.dataTable thead th.fixedHeader-floating { background: #e2e2e9 !important; }
</style>

<div class="progress" id="progressBar" role="progressbar" aria-label="Animated striped" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="height: 8px; transform: rotate(180deg);display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
</div>

<div class="content-page">
<div class="content">
<div class="container-fluid">

<div class="row mb-3"></div>

<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
<h4 class="header-title mb-0">
    Category Management
</h4>
<div class="d-flex align-items-center">
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="Info"><i class="ri-information-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download options"><i class="ri-download-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="newDataBtn" title="Add new category"><i class="ri-add-circle-line"></i></a>
</div>
<?php 
$maintableTitle = "Business Categories"; 
$categories = DB::connection('tenant')->table('categories')->get(); 
?>
</div>

<div class="card-body">
<table id="maintable" class="table table-sm table-striped row-border order-column w-100">
    <thead style="background-color:#e2e2e9">
    <tr>
        <th>Category</th>
        <th style="text-align:center">Description</th>
        <th style="text-align:center">Action</th>
    </tr>
    </thead>
    <tbody id="tbody">
    @foreach($categories as $category)
        <?php $row = "row".$category->id ?>
        <tr id="{{ $row }}">
            <td>{{ $category->category }}</td>
            <td style="text-align:center">{{ $category->description ?? '-' }}</td>
            <td style="text-align:center">
                <a href="#" class="editDataBtn"
                   editId="{{ $category->id }}"
                   editRow="{{ $row }}"
                   editCategory="{{ $category->category }}"
                   editDescription="{{ $category->description ?? '' }}">
                   <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px;"></i>
                </a>
                <a href="#" class="deleteDataBtn"
                   deleteLabel="{{ $category->category }}"
                   deleteId="{{ $category->id }}"
                   deleteRow="{{ $row }}">
                   <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px;"></i>
                </a>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
</div>

</div>
</div>
</div>

<!-- Modals -->
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Download</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Click on respective button to download category data</p>
                <div class="buttons"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Category Management</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Manage categories.
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="newDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post" id="newDataForm">
                    @csrf
                    <div class="mb-3">
                        <label class="control-label form-label">Category <span class="text-danger">*</span></label>
                        <input class="form-control" placeholder="Enter category name" type="text" name="category" id="category" autocomplete="off" required/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Description <small class="text-muted">(max 500 characters)</small></label>
                        <textarea class="form-control" placeholder="Enter category description" name="description" id="description" rows="4" maxlength="500" autocomplete="off"></textarea>
                    </div>
                    <a href="#" class="btn btn-primary float-end mt-3 mb-2" id="submitDataBtn">Submit</a>
                    <a href="#" class="btn btn-secondary float-end mt-3 mb-2 mx-2" id="cancelDataBtn">Clear</a>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="singleDeleteDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width:350px; margin:1.75rem auto;">
        <div class="modal-content">
            <div class="modal-body text-center pb-4">
                <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
                <form action="#" method="post" id="singleDeleteDataForm">
                    @csrf
                    <div class="form-group">
                      <h4>Are you sure you want to delete <span id="singleDisplayDeleteLabel"></span>?</h4>
                    </div>
                    <div class="form-group">
                        <h5>You won't be able to revert this!</h5>
                    </div>
                    <div class="form-group">
                        <input type="hidden" id="singleDeleteId" name="id">
                        <input type="hidden" id="singleDeleteRow">
                    </div>
                    <div class="form-group">
                        <a href="#" class="btn btn-danger" id="submitSingleDeleteDataBtn" style="margin-top:10px;margin-bottom:10px;margin-right:5px">Yes, Delete it</a>
                        <a href="#" class="btn btn-info" id="keepSingleDataBtn" style="margin-top:10px;margin-bottom:10px;">No, Keep it</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post" id="editDataForm">
                    @csrf
                    <div class="form-group">
                        <input type="hidden" name="id" id="editId">
                        <input type="hidden" name="editrow" id="editRow">
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Category <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" name="category" id="editCategory" required/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Description <small class="text-muted">(max 500 characters)</small></label>
                        <textarea class="form-control" name="description" id="editDescription" rows="4" maxlength="500"></textarea>
                    </div>
                    <a href="#" class="btn btn-primary float-end mt-3 mb-2" id="submitUpdateDataBtn">Submit</a>
                    <a href="#" class="btn btn-secondary float-end mt-3 mb-2 mx-2" id="cancelEditDataBtn">Clear</a>
                </form>
            </div>
        </div>
    </div>
</div>

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

    var table = $('#maintable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[100, 250, 500, -1], [100, 250, 500, "All"]],
        fixedColumns: { left: 1 },
        scrollX: true,
        buttons: [
            { extend: 'excelHtml5', title: @json($maintableTitle), exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'csvHtml5',  title: @json($maintableTitle), exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'pdfHtml5',  title: @json($maintableTitle), exportOptions: { columns: ':visible:not(:last-child)' },
              customize: function (doc) { doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split(''); }
            }
        ]
    });
    table.buttons().container().appendTo($('#buttonsModal .buttons'));

    // Open add modal
    $('#newDataBtn').click(function (e) {
        e.preventDefault();
        $('#newDataForm')[0].reset();
        $('#newDataModal').modal('show');
    });

    $('#infoBtn').click(function (e) { e.preventDefault(); $('#infoModal').modal('show'); });
    $('#tableButtonsBtn').click(function (e) { e.preventDefault(); $('#buttonsModal').modal('show'); });

    // ADD new category
    $('#submitDataBtn').click(function (e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        var formData = $('#newDataForm').serialize();

        $.ajax({
            type: 'POST',
            url: '{{ route("tenant.super.admin.category.insert") }}',
            data: formData,
            timeout: 60000,
            beforeSend: function () { $('#progressBar').show(); },
            complete: function () { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function (data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    var c = data.category;
                    var newRow = `<tr id="row${c.id}">
                        <td>${c.category}</td>
                        <td style="text-align:center">${c.description || '-'}</td>
                        <td style="text-align:center">
                            <a href="#" class="editDataBtn"
                               editId="${c.id}" editRow="row${c.id}"
                               editCategory="${c.category}"
                               editDescription="${c.description || ''}">
                               <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px;"></i>
                            </a>
                            <a href="#" class="deleteDataBtn"
                               deleteLabel="${c.category}"
                               deleteId="${c.id}" deleteRow="row${c.id}">
                               <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px;"></i>
                            </a>
                        </td>
                    </tr>`;
                    table.row.add($(newRow)).draw(false);
                    $('#newDataModal').modal('hide');
                } else if (data.status === 422) {
                    toastr.error(data.error || 'Validation failed.', 'Error');
                } else {
                    toastr.info('Unspecified error occurred.', 'Notice');
                }
            },
            error: function (xhr, status, error) {
                if (status === 'timeout') {
                    toastr.error('Request timed out. Check connection.', 'Timeout');
                } else if (xhr.status === 0) {
                    toastr.error('Connection failed.', 'Error');
                } else if (xhr.status === 422) {
                    var errorPassage = ''; 
                    $.each(xhr.responseJSON.errors, function (k, v) { errorPassage += v.join('<br>') + '<br>'; });
                    toastr.error(errorPassage, 'Validation Errors');
                } else if (xhr.status === 500) {
                    toastr.error('Server error. Try again later.', 'Error');
                } else {
                    toastr.error('Unknown error occurred.', 'Error');
                }
            }
        });
    });

    $('#cancelDataBtn').click(function (e) { 
        e.preventDefault(); 
        $('#newDataForm')[0].reset(); 
        $('#newDataModal').modal('hide'); 
    });

    // DELETE
    $('#tbody').on('click', '.deleteDataBtn', function () {
        $('#singleDisplayDeleteLabel').html($(this).attr('deleteLabel'));
        $('#singleDeleteRow').val($(this).attr('deleteRow'));
        $('#singleDeleteId').val($(this).attr('deleteId'));
        $('#singleDeleteDataModal').modal('show');
    });

    $('#keepSingleDataBtn').click(function (e) {
        e.preventDefault();
        toastr.info('Category kept.', 'Cancelled');
        $('#singleDeleteDataModal').modal('hide');
    });

    $('#submitSingleDeleteDataBtn').click(function (e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        var row = $('#singleDeleteRow').val();
        var categoryId = $('#singleDeleteId').val();

        $.ajax({
            type: 'POST',
            url: '{{ route("tenant.super.admin.category.delete") }}',
            data: { id: categoryId, _token: '{{ csrf_token() }}' },
            timeout: 60000,
            beforeSend: function () { $('#progressBar').show(); },
            complete: function () { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function (data) {
                if (data.status === 200 || data.status === 201) {
                    toastr.success(data.success || 'Deleted successfully', 'Success');
                    table.row('#' + row).remove().draw(false);
                    $('#singleDeleteDataModal').modal('hide');
                } else {
                    toastr.error(data.error || 'Delete failed.', 'Error');
                }
            },
            error: function (xhr, status, error) {
                if (status === 'timeout') {
                    toastr.error('Request timed out.', 'Timeout');
                } else if (xhr.status === 0) {
                    toastr.error('Connection failed.', 'Error');
                } else if (xhr.status === 422) {
                    var errorPassage = ''; $.each(xhr.responseJSON.errors, function (k, v) { errorPassage += v.join('<br>') + '<br>'; });
                    toastr.error(errorPassage, 'Validation Errors');
                } else if (xhr.status === 500) {
                    toastr.error('Server error.', 'Error');
                } else {
                    toastr.error('Unknown error.', 'Error');
                }
            }
        });
    });

    // EDIT - populate modal
    $('#tbody').on('click', '.editDataBtn', function () {
        $('#editId').val($(this).attr('editId'));
        $('#editRow').val($(this).attr('editRow'));
        $('#editCategory').val($(this).attr('editCategory'));
        $('#editDescription').val($(this).attr('editDescription'));
        $('#editDataModal').modal('show');
    });

    // EDIT submit
    $('#submitUpdateDataBtn').click(function (e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        var formData = $('#editDataForm').serialize();
        var row = $('#editRow').val();

        $.ajax({
            type: 'POST',
            url: '{{ route("tenant.super.admin.category.update") }}',
            data: formData,
            timeout: 60000,
            beforeSend: function () { $('#progressBar').show(); },
            complete: function () { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function (data) {
                if (data.status === 200 || data.status === 201) {
                    toastr.success(data.success, 'Success');
                    var c = data.category;
                    var updatedRow = `<tr id="${row}">
                        <td>${c.category}</td>
                        <td style="text-align:center">${c.description || '-'}</td>
                        <td style="text-align:center">
                            <a href="#" class="editDataBtn"
                               editId="${c.id}" editRow="${row}"
                               editCategory="${c.category}"
                               editDescription="${c.description || ''}">
                               <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px;"></i>
                            </a>
                            <a href="#" class="deleteDataBtn"
                               deleteLabel="${c.category}"
                               deleteId="${c.id}" deleteRow="${row}">
                               <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px;"></i>
                            </a>
                        </td>
                    </tr>`;
                    table.row('#' + row).remove();
                    table.row.add($(updatedRow)).draw(false);
                    $('#editDataModal').modal('hide');
                } else if (data.status === 422) {
                    toastr.error(data.error || 'Validation failed.', 'Error');
                } else {
                    toastr.info('Unspecified error occurred.', 'Notice');
                }
            },
            error: function (xhr, status, error) {
                if (status === 'timeout') {
                    toastr.error('Request timed out.', 'Timeout');
                } else if (xhr.status === 0) {
                    toastr.error('Connection failed.', 'Error');
                } else if (xhr.status === 422) {
                    var errorPassage = ''; $.each(xhr.responseJSON.errors, function (k, v) { errorPassage += v.join('<br>') + '<br>'; });
                    toastr.error(errorPassage, 'Validation Errors');
                } else if (xhr.status === 500) {
                    toastr.error('Server error.', 'Error');
                } else {
                    toastr.error('Unknown error.', 'Error');
                }
            }
        });
    });

    $('#cancelEditDataBtn').click(function (e) { 
        e.preventDefault(); 
        $('#editDataForm')[0].reset(); 
        $('#editDataModal').modal('hide'); 
    });
});
</script>
@endsection