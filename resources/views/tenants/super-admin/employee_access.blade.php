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
    .access-badge { font-size: 0.85rem; padding: 0.4em 0.7em; margin: 0.25em 0.3em; border-radius: 1rem; }
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
    Employee Access Management
</h4>
<div class="d-flex align-items-center">
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="Info"><i class="ri-information-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download options"><i class="ri-download-line"></i></a>
</div>
<?php 
$maintableTitle = "Employee Access List"; 
$employees = DB::connection('tenant')->table('users')->get(); 
$sectors = DB::connection('tenant')->table('sectors')->pluck('sector', 'id')->toArray();
$accesses = DB::connection('tenant')->table('employee_access')->get()->groupBy('employee_id');
?>
</div>

<div class="card-body">
<table id="maintable" class="table table-sm table-striped row-border order-column w-100">
    <thead style="background-color:#e2e2e9">
    <tr>
        <th>Employee Name</th>
        <th style="text-align:center">Role</th>
        <th style="text-align:center">Access</th>
        <th style="text-align:center">Action</th>
    </tr>
    </thead>
    <tbody id="tbody">
    @foreach($employees as $emp)
        <?php $row = "row".$emp->id ?>
        <tr id="{{ $row }}">
            <td>{{ $emp->name }}</td>
            <td style="text-align:center">{{ $emp->role }}</td>
            <td style="text-align:center">
                <?php $empAccess = $accesses[$emp->id] ?? collect(); ?>
                @if($empAccess->isNotEmpty())
                    @foreach($empAccess as $acc)
                        <?php 
                        $sectorName = DB::connection('tenant')->table('sectors')->where('id', $acc->sector_id)->value('sector') ?? 'Unknown';
                        ?>
                        <span class="badge bg-primary access-badge">
                            {{ $sectorName }}
                            <a href="#" class="deleteDataBtn text-white ms-1"
                               deleteLabel="{{ $sectorName }}"
                               deleteId="{{ $acc->id }}"
                               deleteRow="{{ $row }}">
                                <i class="ri-close-line"></i>
                            </a>
                        </span>
                    @endforeach
                @else
                    <span class="text-muted">No sectors assigned</span>
                @endif
            </td>
            <td style="text-align:center">
                <a href="#" class="addDataBtn text-success"
                   addId="{{ $emp->id }}"
                   addName="{{ $emp->name }}">
                   <i class="ri-add-circle-line" style="font-size:20px;"></i>
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
                <p class="mb-2">Click on respective button to download access data</p>
                <div class="buttons"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Employee Access Management</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Assign and remove sector access permissions for employees.
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="newDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Sector Access</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post" id="newDataForm">
                    @csrf
                    <input type="hidden" name="employee_id" id="new_employee_id">
                    <div class="mb-3">
                        <label class="control-label form-label">Employee</label>
                        <input class="form-control" type="text" id="new_employee_name" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Sector <span class="text-danger">*</span></label>
                        <select class="form-control" name="sector_id" id="sector_id" required>
                            <option value="">-- Select Sector --</option>
                            @foreach($sectors as $id => $sector)
                                <option value="{{ $id }}">{{ $sector }}</option>
                            @endforeach
                        </select>
                    </div>
                    <a href="#" class="btn btn-primary float-end mt-3 mb-2" id="submitDataBtn">Assign</a>
                    <a href="#" class="btn btn-secondary float-end mt-3 mb-2 mx-2" id="cancelDataBtn">Cancel</a>
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
                      <h4>Are you sure you want to remove <span id="singleDisplayDeleteLabel"></span> access?</h4>
                    </div>
                    <div class="form-group">
                        <h5>You won't be able to revert this!</h5>
                    </div>
                    <div class="form-group">
                        <input type="hidden" id="singleDeleteId" name="id">
                        <input type="hidden" id="singleDeleteRow">
                    </div>
                    <div class="form-group">
                        <a href="#" class="btn btn-danger" id="submitSingleDeleteDataBtn" style="margin-top:10px;margin-bottom:10px;margin-right:5px">Yes, Remove it</a>
                        <a href="#" class="btn btn-info" id="keepSingleDataBtn" style="margin-top:10px;margin-bottom:10px;">No, Keep it</a>
                    </div>
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
        lengthMenu: [[50, 100, 250, -1], [50, 100, 250, "All"]],
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

    // Open assign modal
    $('#tbody').on('click', '.addDataBtn', function (e) {
        e.preventDefault();
        $('#new_employee_id').val($(this).attr('addId'));
        $('#new_employee_name').val($(this).attr('addName'));
        $('#sector_id').val('');
        $('#newDataModal').modal('show');
    });

    $('#submitDataBtn').click(function (e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        var formData = $('#newDataForm').serialize();

        $.ajax({
            type: 'POST',
            url: '{{ route("tenant.super.admin.permision.add") }}',
            data: formData,
            timeout: 60000,
            beforeSend: function () { $('#progressBar').show(); },
            complete: function () { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function (data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    var acc = data.access;
                    var badge = `<span class="badge bg-primary access-badge">
                        ${acc.sector}
                        <a href="#" class="deleteDataBtn text-white ms-1"
                           deleteLabel="${acc.sector}"
                           deleteId="${acc.id}"
                           deleteRow="row${acc.employee_id}">
                            <i class="ri-close-line"></i>
                        </a>
                    </span>`;

                    var td = $(`#row${acc.employee_id} td:nth-child(3)`);
                    if (td.find('.text-muted').length) td.empty();
                    td.append(badge);

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
        toastr.info('Access kept.', 'Cancelled');
        $('#singleDeleteDataModal').modal('hide');
    });

    $('#submitSingleDeleteDataBtn').click(function (e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        var row = $('#singleDeleteRow').val();
        var accessId = $('#singleDeleteId').val();

        $.ajax({
            type: 'POST',
            url: '{{ route("tenant.super.admin.permision.remove") }}',
            data: { id: accessId, _token: '{{ csrf_token() }}' },
            timeout: 60000,
            beforeSend: function () { $('#progressBar').show(); },
            complete: function () { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function (data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    $(`#${row} .badge:contains("${$('#singleDisplayDeleteLabel').text()}")`).remove();
                    if ($(`#${row} .badge`).length === 0) {
                        $(`#${row} td:nth-child(3)`).html('<span class="text-muted">No sectors assigned</span>');
                    }
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
});
</script>
@endsection