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
    .card-header { padding: 0.5rem 1.5rem !important; background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff; border-top-left-radius: 10px; border-top-right-radius: 10px; }
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
    Employee Management
</h4>
<div class="d-flex align-items-center">
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="Info"><i class="ri-information-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Download options"><i class="ri-download-line"></i></a>
    <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="newDataBtn" title="Add new employee"><i class="ri-user-add-line"></i></a>
</div>
<?php
    $maintableTitle = "Employees Management";
    $employees = DB::connection('tenant')->table('users')->get();
    $branches = DB::connection('tenant')->table('branches')->get();
    $roles = DB::connection('tenant')->table('roles')->get();
?>
</div>

<div class="card-body">
<table id="maintable" class="table table-sm table-striped row-border order-column w-100">
    <thead style="background-color:#e2e2e9">
    <tr>
        <th>Name</th>
        <th style="text-align:center">Phone</th>
        <th style="text-align:center">Email</th>
        <th style="text-align:center">Role</th>
        <th style="text-align:center">Branch</th>
        <th style="text-align:center">Department</th>
        <th style="text-align:center">Position</th>
        <th style="text-align:center">Action</th>
    </tr>
    </thead>
    <tbody id="tbody">
    @foreach($employees as $emp)
        <?php $row = "row".$emp->id ?>
        <tr id="{{ $row }}">
            <td>{{ $emp->name }}</td>
            <td style="text-align:center">{{ $emp->phone }}</td>
            <td style="text-align:center">{{ $emp->email }}</td>
            <td style="text-align:center">{{ $emp->role ?? '—' }}</td>
            <td style="text-align:center">      
            {{ DB::connection('tenant')->table('branches')->where('id',$emp->branch)->value('name') }}
            </td>
            <td style="text-align:center">{{ $emp->department ?? '—' }}</td>
            <td style="text-align:center">{{ $emp->position ?? '—' }}</td>
            <td style="text-align:center">
                <a href="{{ route('tenant.admin.employee.details') }}?id={{ $emp->id }}" class="btn btn-light text-primary btn-sm" title="View Details">
                    <i class="ri-eye-line"></i>
                </a>
                <a href="#" class="editDataBtn btn btn-light text-info btn-sm"
                   editId="{{ $emp->id }}"
                   editRow="{{ $row }}"
                   editName="{{ $emp->name }}"
                   editPhone="{{ $emp->phone }}"
                   editEmail="{{ $emp->email }}"
                   editRole="{{ $emp->role ?? '' }}"
                   editBranch="{{ $emp->branch ?? '' }}"
                   editDepartment="{{ $emp->department ?? '' }}"
                   editPosition="{{ $emp->position ?? '' }}"
                   editGrossSalary="{{ $emp->gross_salary ?? '' }}"
                   editDob="{{ $emp->dob ?? '' }}"
                   editStartedOn="{{ $emp->started_on ?? '' }}"
                   editIdType="{{ $emp->idtype ?? '' }}"
                   editIdNumber="{{ $emp->idnumber ?? '' }}"
                   editHomeAddress="{{ $emp->home_address ?? '' }}"
                   editCurrentResidence="{{ $emp->current_residence ?? '' }}"
                   editNextofkinName="{{ $emp->nextofkin_name ?? '' }}"
                   editNextofkinRelationship="{{ $emp->nextofkin_relationship ?? '' }}"
                   editNextofkinPhysicalAddress="{{ $emp->nextofkin_physical_address ?? '' }}"
                   editNextofkinContact="{{ $emp->nextofkin_contact ?? '' }}">
                   <i class="ri-edit-box-line"></i>
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

<!-- Download Modal -->
<section>
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Download</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Click on respective button to download employees data</p>
                <div class="buttons"></div>
            </div>
        </div>
    </div>
</section>

<!-- Info Modal -->
<section>
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Employee Management</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Click on the info icon above to read more about employee management.</p>
                <p>Use this section to add new employees, view their details, and export data in various formats (Excel, CSV, PDF, Print).</p>
                <p>To add a new employee, click the <strong>Add</strong> button and fill in all required fields marked with <span style="color:red">*</span>.</p>
                <p>Make sure you understand roles before assigning to employees <a  href="{{ route('tenant.admin.roles') }}" >Click here to view roles</a></p>
            </div>
        </div>
    </div>
</section>

<!-- Add New Employee Modal -->
<section>
<div class="modal fade" id="newDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post" id="newDataForm">
                    @csrf
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="name">Full name (<span style="color:red">*</span>)</label>
                            <input type="text" class="form-control" id="name" name="name" autocomplete="off" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="phone">Phone (<span style="color:red">*</span>)</label>
                            <input type="text" class="form-control" id="phone" name="phone" autocomplete="off" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="email">Email (<span style="color:red">*</span>)</label>
                            <input type="email" class="form-control" id="email" name="email" autocomplete="off" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="role">Role (<span style="color:red">*</span>):</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="">-- Select Role --</option>
                                @foreach($roles as $r)
                                    <option value="{{ $r->role }}">{{ $r->role }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="branch">Branch (<span style="color:red">*</span>):</label>
                            <select class="form-control" id="branch" name="branch" required>
                                <option value="">-- Select Branch --</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="idtype">Department</label>
                            <input type="text" class="form-control" id="department" name="department" autocomplete="off">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="dob">Date of Birth:</label>
                            <input type="date" class="form-control" id="dob" name="dob" autocomplete="off">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="idtype">ID Type:</label>
                            <input type="text" class="form-control" id="idtype" name="idtype" autocomplete="off">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="idnumber">ID Number:</label>
                            <input type="text" class="form-control" id="idnumber" name="idnumber" autocomplete="off">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="started_on">Started work On:</label>
                            <input type="date" class="form-control" id="started_on" name="started_on" autocomplete="off">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="home_address">Home Address:</label>
                            <textarea class="form-control" id="home_address" name="home_address" autocomplete="off"></textarea>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="current_residence">Current Residence:</label>
                            <textarea class="form-control" id="current_residence" name="current_residence" autocomplete="off"></textarea>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="position">Position:</label>
                            <input type="text" class="form-control" id="position" name="position" autocomplete="off">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="gross_salary">Gross Salary:</label>
                            <input type="text" class="form-control" id="gross_salary" name="gross_salary" autocomplete="off">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="nextofkin_name">Next of Kin Name:</label>
                            <input type="text" class="form-control" id="nextofkin_name" name="nextofkin_name" autocomplete="off">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="nextofkin_relationship">Next of Kin Relationship:</label>
                            <input type="text" class="form-control" id="nextofkin_relationship" name="nextofkin_relationship" autocomplete="off">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="nextofkin_physical_address">Next of Kin Physical Address:</label>
                            <input type="text" class="form-control" id="nextofkin_physical_address" name="nextofkin_physical_address" autocomplete="off">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="nextofkin_contact">Next of Kin Contact:</label>
                            <input type="text" class="form-control" id="nextofkin_contact" name="nextofkin_contact" autocomplete="off">
                        </div>
                    </div>
                    <a href="#" class="btn btn-primary float-end mt-3 mb-2" id="submitDataBtn">Submit</a>
                    <a href="#" class="btn btn-secondary float-end mt-3 mb-2 mx-2" id="cancelDataBtn">Clear</a>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Edit Modal -->
<section>
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post" id="editDataForm">
                    @csrf
                    <input type="hidden" name="id" id="editId">
                    <input type="hidden" name="editrow" id="editRow">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="editName">Full name</label>
                            <input type="text" class="form-control" id="editName" name="name" autocomplete="off" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editPhone">Phone </label>
                            <input type="text" class="form-control" id="editPhone" name="phone" autocomplete="off" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editEmail">Email</label>
                            <input type="email" class="form-control" id="editEmail" name="email" autocomplete="off" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editRole">Role</label>
                            <select class="form-control" id="editRole" name="role" required>
                                @foreach($roles as $r)
                                    <option value="{{ $r->role }}"> {{ $r->role}} </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editBranch">Branch</label>
                            <select class="form-control" id="editBranch" name="branch" required>
                                @foreach($branches as $b)
                                       <option value="{{ $b->id }}"> {{ $b->name}} </option>
                                @endforeach
                            </select>
                        </div>
            
                         <div class="form-group col-md-6">
                            <label for="editDob">Department</label>
                            <input type="text" class="form-control" id="editDepartment" name="department" autocomplete="off">
                        </div>

                        <div class="form-group col-md-6">
                            <label for="editDob">Date of Birth:</label>
                            <input type="date" class="form-control" id="editDob" name="dob" autocomplete="off">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editIdType">ID Type:</label>
                            <input type="text" class="form-control" id="editIdType" name="idtype" autocomplete="off">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editIdNumber">ID Number:</label>
                            <input type="text" class="form-control" id="editIdNumber" name="idnumber" autocomplete="off">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editStartedOn">Started work On:</label>
                            <input type="date" class="form-control" id="editStartedOn" name="started_on" autocomplete="off">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editHomeAddress">Home Address:</label>
                            <textarea class="form-control" id="editHomeAddress" name="home_address" autocomplete="off"></textarea>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editCurrentResidence">Current Residence:</label>
                            <textarea class="form-control" id="editCurrentResidence" name="current_residence" autocomplete="off"></textarea>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editPosition">Position:</label>
                            <input type="text" class="form-control" id="editPosition" name="position" autocomplete="off">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editGrossSalary">Gross Salary:</label>
                            <input type="text" class="form-control" id="editGrossSalary" name="gross_salary" autocomplete="off">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editNextofkinName">Next of Kin Name:</label>
                            <input type="text" class="form-control" id="editNextofkinName" name="nextofkin_name" autocomplete="off">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editNextofkinRelationship">Next of Kin Relationship:</label>
                            <input type="text" class="form-control" id="editNextofkinRelationship" name="nextofkin_relationship" autocomplete="off">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editNextofkinPhysicalAddress">Next of Kin Physical Address:</label>
                            <input type="text" class="form-control" id="editNextofkinPhysicalAddress" name="nextofkin_physical_address" autocomplete="off">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="editNextofkinContact">Next of Kin Contact:</label>
                            <input type="text" class="form-control" id="editNextofkinContact" name="nextofkin_contact" autocomplete="off">
                        </div>
                    </div>
                    <a href="#" class="btn btn-primary float-end mt-3 mb-2" id="submitUpdateDataBtn">Submit</a>
                    <a href="#" class="btn btn-secondary float-end mt-3 mb-2 mx-2" id="cancelEditDataBtn">Clear</a>
                </form>
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
                url: '{{ route("tenant.admin.employee.insert") }}',
                data: formData + '&_token={{ csrf_token() }}',
                timeout: 60000,
                beforeSend: function () { $('#progressBar').show(); },
                complete: function () { $('#progressBar').hide(); self.prop('disabled', false); },
                success: function (data) {
                    if (data.status === 201) {
                        toastr.success(data.success, 'Success');
                        var e = data.employee;
                        var newRow = `<tr id="row${e.id}"><td>${e.name}</td><td style="text-align:center">${e.phone}</td><td style="text-align:center">${e.email}</td><td style="text-align:center">${e.role || '—'}</td><td style="text-align:center">${e.branch || '—'}</td><td style="text-align:center">${e.department || '—'}</td><td style="text-align:center">${e.position || '—'}</td><td style="text-align:center"><a href="{{ route('master.employee.details') }}?id=${e.id}" class="btn btn-light text-primary btn-sm"><i class="ri-eye-line"></i></a> <a href="#" class="editDataBtn btn btn-light text-info btn-sm" editId="${e.id}" editRow="row${e.id}" editName="${e.name}" editPhone="${e.phone}" editEmail="${e.email}" editRole="${e.role || ''}" editBranch="${e.branch || ''}" editDepartment="${e.department || ''}" editPosition="${e.position || ''}" editGrossSalary="${e.gross_salary || ''}" editDob="${e.dob || ''}" editStartedOn="${e.started_on || ''}" editIdType="${e.idtype || ''}" editIdNumber="${e.idnumber || ''}" editHomeAddress="${e.home_address || ''}" editCurrentResidence="${e.current_residence || ''}" editNextofkinName="${e.nextofkin_name || ''}" editNextofkinRelationship="${e.nextofkin_relationship || ''}" editNextofkinPhysicalAddress="${e.nextofkin_physical_address || ''}" editNextofkinContact="${e.nextofkin_contact || ''}"><i class="ri-edit-box-line"></i></a></td></tr>`;
                        table.row.add($(newRow)).draw(false);
                        $('#newDataModal').modal('hide');
                    } else if (data.status === 422) {
                        var errorPassage = ''; $.each(data.errors, function (k, v) { errorPassage += v + '\n'; });
                        toastr.error(errorPassage, 'Validation Errors');
                    } else {
                        toastr.info('Unspecified error occurred.', 'Error');
                    }
                },
                error: function (xhr, status, error) {
                    if (status === 'timeout') {
                        toastr.error('The request timed out. Please check your internet connection and try again.', 'Timeout Error');
                    } else if (xhr.status === 0) {
                        toastr.error('Unable to connect. Please check your internet connection and try again.', 'Connection Error');
                    } else if (xhr.status === 422) {
                        var errorPassage = ''; $.each(xhr.responseJSON.errors, function (k, v) { errorPassage += v + '\n'; });
                        toastr.error(errorPassage, 'Validation Errors');
                    } else if (xhr.status === 500) {
                        toastr.error('Server error occurred. Please refresh the page and try again.', 'Server Error');
                    } else {
                        toastr.error('Unspecified error occurred. Try again later.', 'Unspecified Error');
                    }
                }
            });
        });

        $('#cancelDataBtn').click(function (e) { e.preventDefault(); $('#newDataForm')[0].reset(); $('#newDataModal').modal('hide'); });

        // EDIT
        $('#tbody').on('click', '.editDataBtn', function () {
            $('#editId').val($(this).attr('editId'));
            $('#editRow').val($(this).attr('editRow'));
            $('#editName').val($(this).attr('editName'));
            $('#editPhone').val($(this).attr('editPhone'));
            $('#editEmail').val($(this).attr('editEmail'));
            $('#editRole').val($(this).attr('editRole'));
            $('#editBranch').val($(this).attr('editBranch'));
            $('#editDepartment').val($(this).attr('editDepartment'));
            $('#editPosition').val($(this).attr('editPosition'));
            $('#editGrossSalary').val($(this).attr('editGrossSalary'));
            $('#editDob').val($(this).attr('editDob'));
            $('#editStartedOn').val($(this).attr('editStartedOn'));
            $('#editIdType').val($(this).attr('editIdType'));
            $('#editIdNumber').val($(this).attr('editIdNumber'));
            $('#editHomeAddress').val($(this).attr('editHomeAddress'));
            $('#editCurrentResidence').val($(this).attr('editCurrentResidence'));
            $('#editNextofkinName').val($(this).attr('editNextofkinName'));
            $('#editNextofkinRelationship').val($(this).attr('editNextofkinRelationship'));
            $('#editNextofkinPhysicalAddress').val($(this).attr('editNextofkinPhysicalAddress'));
            $('#editNextofkinContact').val($(this).attr('editNextofkinContact'));
            $('#editDataModal').modal('show');
        });

        $('#submitUpdateDataBtn').click(function (e) {
            e.preventDefault();
            var self = $(this); self.prop('disabled', true);
            var formData = $('#editDataForm').serialize();
            var row = $('#editRow').val();

            $.ajax({
                type: 'POST',
                url: '{{ route("tenant.admin.employee.update") }}',
                data: formData + '&_token={{ csrf_token() }}',
                timeout: 60000,
                beforeSend: function () { $('#progressBar').show(); },
                complete: function () { $('#progressBar').hide(); self.prop('disabled', false); },
                success: function (data) {
                    if (data.status === 201) {
                        toastr.success(data.success, 'Success');
                        var e = data.employee;
                        var updatedRow = `<tr id="${row}"><td>${e.name}</td><td style="text-align:center">${e.phone}</td><td style="text-align:center">${e.email}</td><td style="text-align:center">${e.role || '—'}</td><td style="text-align:center">${e.branch || '—'}</td><td style="text-align:center">${e.department || '—'}</td><td style="text-align:center">${e.position || '—'}</td><td style="text-align:center"><a href="{{ route('master.employee.details') }}?id=${e.id}" class="btn btn-light text-primary btn-sm"><i class="ri-eye-line"></i></a> <a href="#" class="editDataBtn btn btn-light text-info btn-sm" editId="${e.id}" editRow="${row}" editName="${e.name}" editPhone="${e.phone}" editEmail="${e.email}" editRole="${e.role || ''}" editBranch="${e.branch || ''}" editDepartment="${e.department || ''}" editPosition="${e.position || ''}" editGrossSalary="${e.gross_salary || ''}" editDob="${e.dob || ''}" editStartedOn="${e.started_on || ''}" editIdType="${e.idtype || ''}" editIdNumber="${e.idnumber || ''}" editHomeAddress="${e.home_address || ''}" editCurrentResidence="${e.current_residence || ''}" editNextofkinName="${e.nextofkin_name || ''}" editNextofkinRelationship="${e.nextofkin_relationship || ''}" editNextofkinPhysicalAddress="${e.nextofkin_physical_address || ''}" editNextofkinContact="${e.nextofkin_contact || ''}"><i class="ri-edit-box-line"></i></a></td></tr>`;
                        table.row('#' + row).remove(); table.row.add($(updatedRow)).draw(false);
                        $('#editDataModal').modal('hide');
                    } else if (data.status === 422) {
                        var errorPassage = ''; $.each(data.errors, function (k, v) { errorPassage += v + '\n'; });
                        toastr.error(errorPassage, 'Validation Errors');
                    } else {
                        toastr.info('Unspecified error occurred.', 'Error');
                    }
                },
                error: function (xhr, status, error) {
                    if (status === 'timeout') {
                        toastr.error('The request timed out. Please check your internet connection and try again.', 'Timeout Error');
                    } else if (xhr.status === 0) {
                        toastr.error('Unable to connect. Please check your internet connection and try again.', 'Connection Error');
                    } else if (xhr.status === 422) {
                        var errorPassage = ''; $.each(xhr.responseJSON.errors, function (k, v) { errorPassage += v + '\n'; });
                        toastr.error(errorPassage, 'Validation Errors');
                    } else if (xhr.status === 500) {
                        toastr.error('Server error occurred. Please refresh the page and try again.', 'Server Error');
                    } else {
                        toastr.error('Unspecified error occurred. Try again later.', 'Unspecified Error');
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