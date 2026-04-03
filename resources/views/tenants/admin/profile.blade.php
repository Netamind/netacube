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

            <?php 
                $user = DB::table('users')->where('id', Auth::user()->id)->first();
            ?>

            <div class="card">
                <!-- Header -->
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title mb-0">
                        My Profile
                    </h4>
                    <div class="d-flex align-items-center gap-1">
                        <a href="#" 
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
                                Details
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
                                <input type="hidden" name="id" value="{{ Auth::user()->id }}">
                                <div class="row">
                                    <!-- LEFT COLUMN -->
                                    <div class="col-md-6">
                                        <div class="row mb-3">
                                            <label for="name" class="col-3 col-form-label">Name</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="name" value="{{ optional($user)->name }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="phone" class="col-3 col-form-label">Phone</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="phone" value="{{ optional($user)->phone }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="email" class="col-3 col-form-label">Email</label>
                                            <div class="col-9">
                                                <input type="email" class="form-control" name="email" value="{{ optional($user)->email }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="dob" class="col-3 col-form-label">Date of Birth</label>
                                            <div class="col-9">
                                                <input type="date" class="form-control" name="dob" value="{{ optional($user)->dob }}">
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <label for="branch" class="col-3 col-form-label">Branch</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="branch" value="{{ optional($user)->branch }}" readonly>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <label for="idtype" class="col-3 col-form-label">ID Type</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="idtype" value="{{ optional($user)->idtype }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="idnumber" class="col-3 col-form-label">ID Number</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="idnumber" value="{{ optional($user)->idnumber }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="started_on" class="col-3 col-form-label">Started On</label>
                                            <div class="col-9">
                                                <input type="date" class="form-control" name="started_on" value="{{ optional($user)->started_on }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="home_address" class="col-3 col-form-label">Home Address</label>
                                            <div class="col-9">
                                                <textarea name="home_address" class="form-control" rows="2">{{ optional($user)->home_address }}</textarea>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="current_residence" class="col-3 col-form-label">Current Residence</label>
                                            <div class="col-9">
                                                <textarea name="current_residence" class="form-control" rows="2">{{ optional($user)->current_residence }}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- RIGHT COLUMN -->
                                    <div class="col-md-6">
                                        <div class="row mb-3">
                                            <label for="entered_on" class="col-3 col-form-label">Entered On</label>
                                            <div class="col-9">
                                                <input type="date" class="form-control" name="entered_on"
                                                       value="{{ optional($user)->entered_on }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="active" class="col-3 col-form-label">Active</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="active"
                                                       value="{{ optional($user)->active ?? 'Yes' }}" readonly>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <label for="department" class="col-3 col-form-label">Department</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="department" value="{{ optional($user)->department }}" readonly>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <label for="position" class="col-3 col-form-label">Position</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="position" value="{{ optional($user)->position }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="gross_salary" class="col-3 col-form-label">Gross Salary</label>
                                            <div class="col-9">
                                                <input type="number" class="form-control" name="gross_salary" value="{{ optional($user)->gross_salary }}" readonly>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <label for="role" class="col-3 col-form-label">Role</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="role" value="{{ optional($user)->role }}" readonly>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <label for="nextofkin_name" class="col-3 col-form-label">Next of Kin Name</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="nextofkin_name"
                                                       value="{{ optional($user)->nextofkin_name }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="nextofkin_relationship" class="col-3 col-form-label">Next of Kin Relationship</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="nextofkin_relationship"
                                                       value="{{ optional($user)->nextofkin_relationship }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="nextofkin_physical_address" class="col-3 col-form-label">Next of Kin Address</label>
                                            <div class="col-9">
                                                <textarea name="nextofkin_physical_address"
                                                          class="form-control" rows="2">{{ optional($user)->nextofkin_physical_address }}</textarea>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label for="nextofkin_contact" class="col-3 col-form-label">Next of Kin Contact</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="nextofkin_contact"
                                                       value="{{ optional($user)->nextofkin_contact }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- ACTION BUTTON -->
                                <div class="justify-content-end row">
                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn btn-primary" id="updateProfileInfoBtn">
                                            <i class="ri-save-line me-1"></i> Update
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="tab-pane" id="leaves">
                            Leaves data not found
                        </div>

                        <div class="tab-pane" id="finances">
                            Finances data not found
                        </div>

                        <!-- CHANGE PASSWORD TAB -->
                        <div class="tab-pane" id="password">
                            <form class="form-horizontal" id="changePasswordForm">
                                @csrf
                                <div class="row mb-3">
                                    <label class="col-3 col-form-label">Current password</label>
                                    <div class="col-9">
                                        <input type="password" class="form-control" name="currentpassword" placeholder="Enter current password">
                                    </div>
                                </div>
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

@endsection

@section('scripts')
<script>
$(document).ready(function() {

    toastr.options = { closeButton:true, progressBar:true, timeOut:5000 };

    $('#updateProfileInfoBtn').click(function(e) {
        e.preventDefault();
        var self = $(this);
        self.prop("disabled", true);
        var form = document.getElementById("profileDataForm");

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $.ajax({
            type: "POST",
            url: "{{ route('master.update.profile.info') }}",
            data: $(form).serialize(),
            timeout: 60000,
            beforeSend: function() {
                $('#progressBar').show();
            },
            complete: function() {
                $('#progressBar').hide();
                self.prop("disabled", false);
            },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                } else if(data.status === 404) {
                    toastr.error(data.error, 'Error');
                } else if (data.status === 422) {
                    let errorPassage = '';
                    $.each(data.errors, function(key, value) {
                        errorPassage += value + '\n';
                    });
                    toastr.error(errorPassage, 'Validation Errors');
                } else {
                    toastr.error('Unspecified error occurred', 'Error');
                }
            },
            error: function(xhr, status, error) {
                if (status === 'timeout') {
                    toastr.error('The request timed out. Please check your internet connection.', 'Timeout Error');
                } else if (xhr.status === 0) {
                    toastr.error('Network error. Please check your internet connection.', 'Connection Error');
                } else if (xhr.status === 422) {
                    var errorPassage = '';
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        errorPassage += value + '\n';
                    });
                    toastr.error(errorPassage, 'Validation Errors');
                } else if (xhr.status === 500) {
                    toastr.error('Server error occurred. Please try again later.', 'Server Error');
                } else {
                    toastr.error('An unexpected error occurred.', 'Error');
                }
            }
        });
    });

    $('#submitChangePasswordBtn').click(function(e) {
        e.preventDefault();
        var self = $(this);
        self.prop("disabled", true);
        var form = document.getElementById("changePasswordForm");

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $.ajax({
            type: "POST",
            url: "{{ route('master.profile.change.password') }}",
            data: $(form).serialize(),
            timeout: 60000,
            beforeSend: function() {
                $('#progressBar').show();
            },
            complete: function() {
                $('#progressBar').hide();
                self.prop("disabled", false);
            },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    form.reset();
                } else if (data.status === 422) {
                    toastr.error(data.error, 'Error');
                } else {
                    toastr.info('Operation completed', 'Info');
                    form.reset();
                }
            },
            error: function(xhr, status, error) {
                if (xhr.status === 0 && xhr.readyState === 0) {
                    toastr.error('Timeout - check your internet connection', 'Timeout Error');
                } else if (xhr.status === 422) {
                    var errorPassage = '';
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        errorPassage += value + '\n';
                    });
                    toastr.error(errorPassage, 'Validation Errors');
                } else if (xhr.status === 500) {
                    toastr.error('Internal server error occurred', 'Server Error');
                } else {
                    toastr.error('An unexpected error occurred', 'Error');
                }
            }
        });
    });

});
</script>
@endsection