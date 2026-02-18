@extends('tenants.admin.dashboard')
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
   <i class="ri-money-dollar-circle-line"></i> Currency Management
</h4>
<div class="d-flex align-items-center">
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="Info"><i class="ri-information-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download options"><i class="ri-download-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="newDataBtn" title="Add new currency"><i class="ri-add-circle-line"></i></a>
</div>
<?php $maintableTitle = "Currency List"; $currencies = DB::table('currency')->get(); ?>
</div>

<div class="card-body">
<table id="maintable" class="table table-sm table-striped row-border order-column w-100">
    <thead style="background-color:#e2e2e9">
    <tr>
        <th>Currency Name</th>
        <th style="text-align:center">Code</th>
        <th style="text-align:center">Action</th>
    </tr>
    </thead>
    <tbody id="tbody">
    @foreach($currencies as $currency)
        <?php $row = "row".$currency->id ?>
        <tr id="{{ $row }}">
            <td>{{ $currency->name }}</td>
            <td style="text-align:center">{{ $currency->code }}</td>
            <td style="text-align:center">
                <a href="#" class="editDataBtn"
                   editId="{{ $currency->id }}"
                   editRow="{{ $row }}"
                   editName="{{ $currency->name }}"
                   editCode="{{ $currency->code }}">
                   <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px;color:blue2"></i>
                </a>
                <a href="#" class="deleteDataBtn"
                   deleteLabel="{{ $currency->name }} ({{ $currency->code }})"
                   deleteId="{{ $currency->id }}"
                   deleteRow="{{ $row }}">
                   <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px;color:red2"></i>
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
<section>
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Download</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Click on respective button to download currency data</p>
                <div class="buttons"></div>
            </div>
        </div>
    </div>
</div>
</section>

<section>
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Currency Management</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Manage currencies used in the system by adding, editing, or deleting them.
            </div>
        </div>
    </div>
</div>
</section>

<section>
<div class="modal fade" id="newDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Currency</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post" id="newDataForm">
                    @csrf
                    <div class="mb-3">
                        <label class="control-label form-label">Currency Name </label>
                        <input class="form-control" placeholder="Enter currency name" type="text" name="name" id="name" autocomplete="off" required/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Currency Code </label>
                        <input class="form-control" placeholder="e.g. USD" type="text" name="code" id="code" maxlength="3" autocomplete="off" required/>
                    </div>
                    <a href="#" class="btn btn-primary float-end mt-3 mb-2" id="submitDataBtn">Submit</a>
                    <a href="#" class="btn btn-secondary float-end mt-3 mb-2 mx-2" id="cancelDataBtn">Clear</a>
                </form>
            </div>
        </div>
    </div>
</div>
</section>

<section>
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
</section>

<section>
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Currency</h5>
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
                        <label class="control-label form-label">Currency Name </label>
                        <input class="form-control" type="text" name="name" id="editName" required/>
                    </div>
                    <div class="mb-3">
                        <label class="control-label form-label">Currency Code </label>
                        <input class="form-control" type="text" name="code" id="editCode" maxlength="3" required/>
                    </div>
                    <a href="#" class="btn btn-primary float-end mt-3 mb-2" id="submitUpdateDataBtn">Submit</a>
                    <a href="#" class="btn btn-secondary float-end mt-3 mb-2 mx-2" id="cancelEditDataBtn">Clear</a>
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

    function initDataTable() {
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

        $('#newDataBtn').click(function (e) { e.preventDefault(); $('#newDataForm')[0].reset(); $('#newDataModal').modal('show'); });
        $('#infoBtn').click(function (e) { e.preventDefault(); $('#infoModal').modal('show'); });
        $('#tableButtonsBtn').click(function (e) { e.preventDefault(); $('#buttonsModal').modal('show'); });

        // ADD
        $('#submitDataBtn').click(function (e) {
            e.preventDefault();
            var self = $(this); self.prop('disabled', true);
            var formData = $('#newDataForm').serialize();

            $.ajax({
                type: 'POST',
                url: '{{ route("master.currency.insert") }}',
                data: formData + '&_token={{ csrf_token() }}',
                timeout: 60000,
                beforeSend: function () { $('#progressBar').show(); },
                complete: function () { $('#progressBar').hide(); self.prop('disabled', false); },
                success: function (data) {
                    if (data.status === 201) {
                        toastr.success(data.success, 'Success', { timeOut: 5000, progressBar: true });
                        var c = data.currency;
                        var newRow = `<tr id="row${c.id}"><td>${c.name}</td><td style="text-align:center">${c.code}</td><td style="text-align:center"><a href="#" class="editDataBtn" editId="${c.id}" editRow="row${c.id}" editName="${c.name}" editCode="${c.code}"><i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px;color:blue2"></i></a> <a href="#" class="deleteDataBtn" deleteLabel="${c.name} (${c.code})" deleteId="${c.id}" deleteRow="row${c.id}"><i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px;color:red2"></i></a></td></tr>`;
                        table.row.add($(newRow)).draw(false);
                        $('#newDataModal').modal('hide');
                    } else if (data.status === 422) {
                        toastr.error(data.error || 'Validation failed.', 'Error', { timeOut: 5000, progressBar: true });
                    } else {
                        toastr.info('Unspecified error occurred.', 'Error', { timeOut: 5000, progressBar: true });
                    }
                },
                error: function (xhr, status, error) {
                    if (status === 'timeout') {
                        toastr.error('The request timed out. Please check your internet connection and try again.', 'Timeout Error', { timeOut: 5000, progressBar: true });
                    } else if (xhr.status === 0) {
                        toastr.error('Unable to connect. Please check your internet connection and try again.', 'Connection Error', { timeOut: 5000, progressBar: true });
                    } else if (xhr.status === 422) {
                        var errorPassage = ''; $.each(xhr.responseJSON.errors, function (k, v) { errorPassage += v + '\n'; });
                        toastr.error(errorPassage, 'Validation Errors', { timeOut: 5000, progressBar: true });
                    } else if (xhr.status === 500) {
                        toastr.error('Server error occurred. Please refresh the page and try again.', 'Server Error', { timeOut: 5000, progressBar: true });
                    } else {
                        toastr.error('Unspecified error occurred. Try again later.', 'Unspecified Error', { timeOut: 5000, progressBar: true });
                    }
                }
            });
        });

        $('#cancelDataBtn').click(function (e) { e.preventDefault(); $('#newDataForm')[0].reset(); $('#newDataModal').modal('hide'); });

        // DELETE
        $('#tbody').on('click', '.deleteDataBtn', function () {
            $('#singleDisplayDeleteLabel').html($(this).attr('deleteLabel'));
            $('#singleDeleteRow').val($(this).attr('deleteRow'));
            $('#singleDeleteId').val($(this).attr('deleteId'));
            $('#singleDeleteDataModal').modal('show');
        });

        $('#keepSingleDataBtn').click(function (e) {
            e.preventDefault();
            toastr.info('Your currency is safe', 'Great!', { timeOut: 5000, progressBar: true });
            $('#singleDeleteDataModal').modal('hide');
        });

        $('#submitSingleDeleteDataBtn').click(function (e) {
            e.preventDefault();
            var self = $(this); self.prop('disabled', true);
            var row = $('#singleDeleteRow').val();
            var currencyId = $('#singleDeleteId').val();

            $.ajax({
                type: 'POST',
                url: '{{ route("master.currency.delete") }}',
                data: { id: currencyId, _token: '{{ csrf_token() }}' },
                timeout: 60000,
                beforeSend: function () { $('#progressBar').show(); },
                complete: function () { $('#progressBar').hide(); self.prop('disabled', false); },
                success: function (data) {
                    if (data.status === 201) {
                        toastr.success(data.success, 'Success', { timeOut: 5000, progressBar: true });
                        table.row('#' + row).remove().draw(false);
                        $('#singleDeleteDataModal').modal('hide');
                    } else if (data.status === 422) {
                        toastr.error(data.error || 'Validation failed.', 'Error', { timeOut: 5000, progressBar: true });
                    } else {
                        toastr.info('Unspecified error occurred.', 'Error', { timeOut: 5000, progressBar: true });
                    }
                },
                error: function (xhr, status, error) {
                    if (status === 'timeout') {
                        toastr.error('The request timed out. Please check your internet connection and try again.', 'Timeout Error', { timeOut: 5000, progressBar: true });
                    } else if (xhr.status === 0) {
                        toastr.error('Unable to connect. Please check your internet connection and try again.', 'Connection Error', { timeOut: 5000, progressBar: true });
                    } else if (xhr.status === 422) {
                        var errorPassage = ''; $.each(xhr.responseJSON.errors, function (k, v) { errorPassage += v + '\n'; });
                        toastr.error(errorPassage, 'Validation Errors', { timeOut: 5000, progressBar: true });
                    } else if (xhr.status === 500) {
                        toastr.error('Server error occurred. Please refresh the page and try again.', 'Server Error', { timeOut: 5000, progressBar: true });
                    } else {
                        toastr.error('Unspecified error occurred. Try again later.', 'Unspecified Error', { timeOut: 5000, progressBar: true });
                    }
                }
            });
        });

        // EDIT
        $('#tbody').on('click', '.editDataBtn', function () {
            $('#editId').val($(this).attr('editId'));
            $('#editRow').val($(this).attr('editRow'));
            $('#editName').val($(this).attr('editName'));
            $('#editCode').val($(this).attr('editCode'));
            $('#editDataModal').modal('show');
        });

        $('#submitUpdateDataBtn').click(function (e) {
            e.preventDefault();
            var self = $(this); self.prop('disabled', true);
            var formData = $('#editDataForm').serialize();
            var row = $('#editRow').val();

            $.ajax({
                type: 'POST',
                url: '{{ route("master.currency.update") }}',
                data: formData + '&_token={{ csrf_token() }}',
                timeout: 60000,
                beforeSend: function () { $('#progressBar').show(); },
                complete: function () { $('#progressBar').hide(); self.prop('disabled', false); },
                success: function (data) {
                    if (data.status === 201) {
                        toastr.success(data.success, 'Success', { timeOut: 5000, progressBar: true });
                        var c = data.currency;
                        var updatedRow = `<tr id="${row}"><td>${c.name}</td><td style="text-align:center">${c.code}</td><td style="text-align:center"><a href="#" class="editDataBtn" editId="${c.id}" editRow="${row}" editName="${c.name}" editCode="${c.code}"><i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px;color:blue2"></i></a> <a href="#" class="deleteDataBtn" deleteLabel="${c.name} (${c.code})" deleteId="${c.id}" deleteRow="${row}"><i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px;color:red2"></i></a></td></tr>`;
                        table.row('#' + row).remove(); table.row.add($(updatedRow)).draw(false);
                        $('#editDataModal').modal('hide');
                    } else if (data.status === 422) {
                        toastr.error(data.error || 'Validation failed.', 'Error', { timeOut: 5000, progressBar: true });
                    } else {
                        toastr.info('Unspecified error occurred.', 'Error', { timeOut: 5000, progressBar: true });
                    }
                },
                error: function (xhr, status, error) {
                    if (status === 'timeout') {
                        toastr.error('The request timed out. Please check your internet connection and try again.', 'Timeout Error', { timeOut: 5000, progressBar: true });
                    } else if (xhr.status === 0) {
                        toastr.error('Unable to connect. Please check your internet connection and try again.', 'Connection Error', { timeOut: 5000, progressBar: true });
                    } else if (xhr.status === 422) {
                        var errorPassage = ''; $.each(xhr.responseJSON.errors, function (k, v) { errorPassage += v + '\n'; });
                        toastr.error(errorPassage, 'Validation Errors', { timeOut: 5000, progressBar: true });
                    } else if (xhr.status === 500) {
                        toastr.error('Server error occurred. Please refresh the page and try again.', 'Server Error', { timeOut: 5000, progressBar: true });
                    } else {
                        toastr.error('Unspecified error occurred. Try again later.', 'Unspecified Error', { timeOut: 5000, progressBar: true });
                    }
                }
            });
        });

        $('#cancelEditDataBtn').click(function (e) { e.preventDefault(); $('#editDataForm')[0].reset(); $('#editDataModal').modal('hide'); });
    }

    initDataTable();
});
</script>
@endsection