@extends('tenants.admin.dashboard')
@section('content')
<style>
    .card-header { 
        padding:0.5rem 1.5rem !important; 
        background:linear-gradient(to right,#4B5EBD,#576CC0); 
        color:#fff; 
        border-top-left-radius:10px; 
        border-top-right-radius:10px; 
    }
    .card-header .btn-light { 
        height:28px; 
        padding:0 10px; 
        display:flex; 
        align-items:center; 
        justify-content:center; 
        line-height:1; 
        font-size: 1rem;
    }
    .card-header .btn-light:hover { 
        background-color:#f8f9fa; 
        transition:background-color .2s ease-in-out; 
    }
    .card { 
        border:none; 
        box-shadow:0 4px 8px rgba(0,0,0,.1); 
        border-radius:10px; 
        overflow: hidden;
    }
    .card-header h4 { 
        color:#fff; 
        font-weight:600; 
        margin-bottom:0; 
        display:flex; 
        align-items:center; 
    }
    .card-header h4 i { 
        margin-right:.25rem; 
    }

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
</style>

<div class="progress" id="progressBar" role="progressbar" aria-label="Animated striped"
     aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"
     style="height:8px; transform:rotate(180deg); display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">

            <div class="row mb-3"></div>

            <div class="card">
                <!-- Header -->
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title mb-0">
                         Employee Details
                    </h4>
                    <div class="d-flex align-items-center gap-1">
                        <a href="{{ route('tenant.admin.employees') }}"
                           class="btn btn-light text-primary" title="Back to Employees">
                            <i class="ri-arrow-left-line"></i>
                        </a>
                        <a href="{{ route('tenant.admin.employee.pdf', $user->id) }}" 
                           class="btn btn-light text-primary" title="Download PDF">
                            <i class="ri-download-line"></i>
                        </a>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="tab-header-container">
                    <ul class="nav nav-pills nav-justified mb-0">
                        <li class="nav-item">
                            <a href="#profile" data-bs-toggle="tab" class="nav-link active">
                                <i class="ri-user-settings-line"></i>
                                Personal Info
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#leaves" data-bs-toggle="tab" class="nav-link">
                                <i class="ri-calendar-2-line"></i>
                                Leaves
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#finances" data-bs-toggle="tab" class="nav-link">
                                <i class="ri-money-dollar-circle-line"></i>
                                Finances
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#password" data-bs-toggle="tab" class="nav-link">
                                <i class="ri-lock-password-line"></i>
                                Change Password
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Tab Content -->
                <div class="card-body pt-4">
                    <div class="tab-content">

                        <!-- PROFILE TAB -->
                        <div class="tab-pane show active" id="profile">
                            <form action="#" class="form-horizontal" id="profileDataForm" method="post">
                                @csrf
                                <input type="hidden" name="id" value="{{ $user->id }}">
                                <div class="row">
                                    <!-- LEFT COLUMN -->
                                    <div class="col-md-6">
                                        <div class="row mb-3">
                                            <label for="name" class="col-3 col-form-label">Name</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="name" value="{{ $user->name }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="phone" class="col-3 col-form-label">Phone</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="phone" value="{{ $user->phone }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="email" class="col-3 col-form-label">Email</label>
                                            <div class="col-9">
                                                <input type="email" class="form-control" name="email" value="{{ $user->email }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="dob" class="col-3 col-form-label">Date of Birth</label>
                                            <div class="col-9">
                                                <input type="date" class="form-control" name="dob" value="{{ $user->dob }}">
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <label for="branch" class="col-3 col-form-label">Branch</label>
                                            <div class="col-9">
                                                <select class="form-control" name="branch">
                                                    <option value="{{$user->branch}}">{{ DB::connection('tenant')->table('branches')->where('id', $user->branch)->value('name') }}</option>
                                                    @foreach($branches as $b)
                                                    <option value="{{ $b->id }}">{{ $b->name}}</option>
                                                   @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <label for="idtype" class="col-3 col-form-label">ID Type</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="idtype" value="{{ $user->idtype }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="idnumber" class="col-3 col-form-label">ID Number</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="idnumber" value="{{ $user->idnumber }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="started_on" class="col-3 col-form-label">Started On</label>
                                            <div class="col-9">
                                                <input type="date" class="form-control" name="started_on" value="{{ $user->started_on }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="home_address" class="col-3 col-form-label">Home Address</label>
                                            <div class="col-9">
                                                <textarea name="home_address" class="form-control" rows="2">{{ $user->home_address }}</textarea>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="current_residence" class="col-3 col-form-label">Current Residence</label>
                                            <div class="col-9">
                                                <textarea name="current_residence" class="form-control" rows="2">{{ $user->current_residence }}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- RIGHT COLUMN -->
                                    <div class="col-md-6">
                                        <div class="row mb-3">
                                            <label for="entered_on" class="col-3 col-form-label">Added On</label>
                                            <div class="col-9">
                                                <input type="date" class="form-control" name="entered_on"
                                                       value="{{ $user->entered_on }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="active" class="col-3 col-form-label">Active</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="active"
                                                       value="{{ $user->active ?? 'Yes' }}" readonly>
                                            </div>
                                        </div>

                                        <!-- DEPARTMENT FROM ROLES -->
                                        <div class="row mb-3">
                                            <label for="department" class="col-3 col-form-label">Department</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="department" value="{{ $user->department }}">
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <label for="position" class="col-3 col-form-label">Position</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="position" value="{{ $user->position }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="gross_salary" class="col-3 col-form-label">Gross Salary</label>
                                            <div class="col-9">
                                                <input type="number" class="form-control" name="gross_salary" value="{{ $user->gross_salary }}">
                                            </div>
                                        </div>

                                        <!-- ROLE FROM ROLES -->
                                        <div class="row mb-3">
                                            <label for="role" class="col-3 col-form-label">Role</label>
                                            <div class="col-9">
                                                <select class="form-control" name="role">
                                                    <option value="{{ $user->role}} ">{{ $user->role }}</option>
                                                    @foreach($roles as $r)
                                                        <option value="{{$r->role}}">{{ $r->role }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <label for="nextofkin_name" class="col-3 col-form-label">Next of Kin Name</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="nextofkin_name"
                                                       value="{{ $user->nextofkin_name }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="nextofkin_relationship" class="col-3 col-form-label">Next of Kin Relationship</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="nextofkin_relationship"
                                                       value="{{ $user->nextofkin_relationship }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="nextofkin_physical_address" class="col-3 col-form-label">Next of Kin Address</label>
                                            <div class="col-9">
                                                <textarea name="nextofkin_physical_address"
                                                          class="form-control" rows="2">{{ $user->nextofkin_physical_address }}</textarea>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="nextofkin_contact" class="col-3 col-form-label">Next of Kin Contact</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="nextofkin_contact"
                                                       value="{{ $user->nextofkin_contact }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ACTION BUTTONS -->
                                <div class="justify-content-end row">
                                    <div class="col-12 text-end">
                                        <a href="#" class="btn btn-danger" id="deleteBtn"
                                           data-id="{{ $user->id }}" data-name="{{ $user->name }}" title="Delete Employee">
                                            <i class="ri-delete-bin-line"></i> Delete
                                        </a>
                                        <button type="submit" class="btn btn-primary" id="updateProfileInfoBtn">
                                            <i class="ri-save-line me-1"></i> Update
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="tab-pane" id="leaves">Leaves data not found</div>
                        <div class="tab-pane" id="finances">Finances data not found</div>

                        <!-- CHANGE PASSWORD -->
                        <div class="tab-pane" id="password">
                            <form class="form-horizontal" id="changePasswordForm">
                                @csrf
                                <input type="hidden" name="id" value="{{ $user->id }}">
                                <div class="row mb-3">
                                    <label class="col-3 col-form-label">New password</label>
                                    <div class="col-9">
                                        <input type="password" class="form-control" name="newpassword" placeholder="Enter new password">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-3 col-form-label">Confirm password</label>
                                    <div class="col-9">
                                        <input type="password" class="form-control" name="comfirmpassword" placeholder="Retype new password">
                                    </div>
                                </div>
                                <div class="justify-content-end row">
                                    <div class="col-9 text-end">
                                        <button type="submit" class="btn btn-primary" id="submitChangePasswordBtn">
                                            <i class="ri-check-line me-1"></i> Submit
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- DELETE MODAL --}}
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

@endsection

@section('scripts')
<script>
$(function () {
    toastr.options = { closeButton:true, progressBar:true, timeOut:5000 };

    $('#updateProfileInfoBtn').on('click', function (e) {
        e.preventDefault();
        const $btn = $(this).prop('disabled', true);
        const $form = $('#profileDataForm');

        $.ajax({
            url: '{{ route('tenant.admin.employee.details.update') }}',
            method: 'POST',
            data: $form.serialize(),
            timeout: 60000,
            beforeSend: () => $('#progressBar').show(),
            complete: () => { $('#progressBar').hide(); $btn.prop('disabled', false); },
            success: data => {
                if (data.status === 201) {
                    
                toastr.success(data.success, 'Success');
            
              }
              else if(data.status===203){

                toastr.error(data.error, 'Error');  
                  

              }
                else if (data.status === 422) {
                    let msg = ''; $.each(data.errors, (k,v) => msg += v + '\n');
                    toastr.error(msg, 'Validation');
                } else toastr.error(data.error || 'Update failed', 'Error');
            },
            error: xhr => {
                let msg = 'Error';
                if (xhr.status === 422) {
                    $.each(xhr.responseJSON.errors, (k,v) => msg += v + '\n');
                    toastr.error(msg, 'Validation');
                } else toastr.error('Server error', 'Error');
            }
        });
    });

    $('#submitChangePasswordBtn').on('click', function (e) {
        e.preventDefault();
        const $btn = $(this).prop('disabled', true);
        const $form = $('#changePasswordForm');

        $.ajax({
            url: '{{ route('master.employee.change.password') }}',
            method: 'POST',
            data: $form.serialize(),
            timeout: 60000,
            beforeSend: () => $('#progressBar').show(),
            complete: () => { $('#progressBar').hide(); $btn.prop('disabled', false); },
            success: data => {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    $form[0].reset();
                } else toastr.error(data.error || 'Failed', 'Error');
            },
            error: xhr => {
                if (xhr.status === 422) {
                    let msg = ''; $.each(xhr.responseJSON.errors, (k,v) => msg += v + '\n');
                    toastr.error(msg, 'Validation');
                } else toastr.error('Server error', 'Error');
            }
        });
    });

    const delModal = new bootstrap.Modal('#singleDeleteDataModal');

    $('#deleteBtn').on('click', function (e) {
        e.preventDefault();
        const id = $(this).data('id'), name = $(this).data('name');
        $('#singleDeleteId').val(id);
        $('#singleDisplayDeleteLabel').text(name);
        $('#singleDeleteDataForm').attr('action', '{{ route("tenant.admin.employee.delete") }}');
        delModal.show();
    });

    $('#keepSingleDataBtn').on('click', function (e) {
        e.preventDefault();
        delModal.hide();
    });

    $('#submitSingleDeleteDataBtn').on('click', function (e) {
        e.preventDefault();
        const $form = $('#singleDeleteDataForm');
        const $btn = $(this).prop('disabled', true);

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize() + '&_method=DELETE',
            beforeSend: () => $('#progressBar').show(),
            success: res => {
                if (res.status === 201) {
                    toastr.success(res.success, 'Deleted');
                    delModal.hide();
                    setTimeout(() => location.href = '{{ route("tenant.admin.employees") }}', 800);
                }
            },
            error: xhr => {
                toastr.error(xhr.responseJSON?.message || 'Delete failed', 'Error');
            },
            complete: () => {
                $('#progressBar').hide();
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
@endsection