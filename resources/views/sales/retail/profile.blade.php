@extends('sales.retail.dashboard')
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
    .section-divider {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #6c757d;
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 6px;
        margin: 0 0 16px 0;
    }
    #contract_end_date_row { display: none; }
</style>

<div class="progress" id="progressBar" role="progressbar" aria-label="Animated striped"
     aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"
     style="height:8px; transform:rotate(180deg); display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<?php
    $user = DB::connection('tenant')->table('users')->where('id', Auth::user()->id)->first();
?>

{{--
    Hidden base form — carries all required fields so every tab's
    AJAX call can merge it and pass backend validation.
--}}
<form id="baseDataForm" style="display:none">
    @csrf
    <input type="hidden" name="id"                         value="{{ optional($user)->id }}">
    <input type="hidden" name="name"                       value="{{ optional($user)->name }}">
    <input type="hidden" name="phone"                      value="{{ optional($user)->phone }}">
    <input type="hidden" name="email"                      value="{{ optional($user)->email }}">
    <input type="hidden" name="role"                       value="{{ optional($user)->role }}">
    <input type="hidden" name="branch"                     value="{{ optional($user)->branch }}">
    <input type="hidden" name="dob"                        value="{{ optional($user)->dob }}">
    <input type="hidden" name="idtype"                     value="{{ optional($user)->idtype }}">
    <input type="hidden" name="idnumber"                   value="{{ optional($user)->idnumber }}">
    <input type="hidden" name="home_address"               value="{{ optional($user)->home_address }}">
    <input type="hidden" name="current_residence"          value="{{ optional($user)->current_residence }}">
    <input type="hidden" name="department"                 value="{{ optional($user)->department }}">
    <input type="hidden" name="position"                   value="{{ optional($user)->position }}">
    <input type="hidden" name="gross_salary"               value="{{ optional($user)->gross_salary }}">
    <input type="hidden" name="started_on"                 value="{{ optional($user)->started_on }}">
    <input type="hidden" name="employment_type"            value="{{ optional($user)->employment_type ?? 'Full-time' }}">
    <input type="hidden" name="contract_end_date"          value="{{ optional($user)->contract_end_date }}">
    <input type="hidden" name="nextofkin_name"             value="{{ optional($user)->nextofkin_name }}">
    <input type="hidden" name="nextofkin_relationship"     value="{{ optional($user)->nextofkin_relationship }}">
    <input type="hidden" name="nextofkin_physical_address" value="{{ optional($user)->nextofkin_physical_address }}">
    <input type="hidden" name="nextofkin_contact"          value="{{ optional($user)->nextofkin_contact }}">
    <input type="hidden" name="bank_name"                  value="{{ optional($user)->bank_name }}">
    <input type="hidden" name="bank_account_name"          value="{{ optional($user)->bank_account_name }}">
    <input type="hidden" name="bank_account_number"        value="{{ optional($user)->bank_account_number }}">
    <input type="hidden" name="bank_branch"                value="{{ optional($user)->bank_branch }}">
    <input type="hidden" name="bank_account_type"          value="{{ optional($user)->bank_account_type ?? 'Savings' }}">
</form>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">

            <div class="row mb-3"></div>

            <div class="card">

                {{-- Header --}}
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title mb-0">My Profile</h4>
                    <div class="d-flex align-items-center gap-1">
                        <a href="#" class="btn btn-light text-primary" id="profileInfoBtn" title="About this page">
                            <i class="ri-information-line"></i>
                        </a>
                    </div>
                </div>

                {{-- Tabs --}}
                <div class="tab-header-container">
                    <ul class="nav nav-pills nav-justified mb-0">
                        <li class="nav-item">
                            <a href="#profile" data-bs-toggle="tab" class="nav-link active">
                                <i class="ri-user-settings-line"></i> Personal Info
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#finances" data-bs-toggle="tab" class="nav-link">
                                <i class="ri-money-dollar-circle-line"></i> Finances &amp; Banking
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#leaves" data-bs-toggle="tab" class="nav-link">
                                <i class="ri-calendar-2-line"></i> Leaves
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#password" data-bs-toggle="tab" class="nav-link">
                                <i class="ri-lock-password-line"></i> Change Password
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- Tab Content --}}
                <div class="card-body pt-4">
                    <div class="tab-content">

                        {{-- ══════════════════════════════════════════════
                             TAB 1 — PERSONAL INFO
                        ══════════════════════════════════════════════ --}}
                        <div class="tab-pane show active" id="profile">
                            <form action="#" class="form-horizontal" id="profileDataForm" method="post">
                                @csrf
                                <input type="hidden" name="id" value="{{ Auth::user()->id }}">

                                {{-- Personal --}}
                                <div class="section-divider">Personal Information</div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Name</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="name" value="{{ optional($user)->name }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Phone</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="phone" value="{{ optional($user)->phone }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Email</label>
                                            <div class="col-9">
                                                <input type="email" class="form-control" name="email" value="{{ optional($user)->email }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Date of Birth</label>
                                            <div class="col-9">
                                                <input type="date" class="form-control" name="dob" value="{{ optional($user)->dob }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">ID Type</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="idtype" value="{{ optional($user)->idtype }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">ID Number</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="idnumber" value="{{ optional($user)->idnumber }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Home Address</label>
                                            <div class="col-9">
                                                <textarea name="home_address" class="form-control" rows="2">{{ optional($user)->home_address }}</textarea>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Current Residence</label>
                                            <div class="col-9">
                                                <textarea name="current_residence" class="form-control" rows="2">{{ optional($user)->current_residence }}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Added On</label>
                                            <div class="col-9">
                                                <input type="date" class="form-control" name="entered_on" value="{{ optional($user)->entered_on }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Active</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="active" value="{{ optional($user)->active ?? 'Yes' }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Branch</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="branch" value="{{ optional($user)->branch }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Role</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="role" value="{{ optional($user)->role }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Department</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="department" value="{{ optional($user)->department }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Position</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="position" value="{{ optional($user)->position }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Started On</label>
                                            <div class="col-9">
                                                <input type="date" class="form-control" name="started_on" value="{{ optional($user)->started_on }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Employment Type</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="employment_type" value="{{ optional($user)->employment_type ?? 'Full-time' }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3" id="contract_end_date_row">
                                            <label class="col-3 col-form-label">Contract End Date</label>
                                            <div class="col-9">
                                                <input type="date" class="form-control" name="contract_end_date" value="{{ optional($user)->contract_end_date }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Gross Salary</label>
                                            <div class="col-9">
                                                <input type="number" class="form-control" name="gross_salary" value="{{ optional($user)->gross_salary }}" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Next of Kin --}}
                                <div class="section-divider mt-2">Next of Kin</div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Name</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="nextofkin_name" value="{{ optional($user)->nextofkin_name }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Relationship</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="nextofkin_relationship" value="{{ optional($user)->nextofkin_relationship }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Physical Address</label>
                                            <div class="col-9">
                                                <textarea name="nextofkin_physical_address" class="form-control" rows="2">{{ optional($user)->nextofkin_physical_address }}</textarea>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Contact</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="nextofkin_contact" value="{{ optional($user)->nextofkin_contact }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Action --}}
                                <div class="row justify-content-end">
                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn btn-primary" id="updateProfileInfoBtn">
                                            <i class="ri-save-line me-1"></i> Update
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        {{-- ══════════════════════════════════════════════
                             TAB 2 — FINANCES & BANKING
                        ══════════════════════════════════════════════ --}}
                        <div class="tab-pane" id="finances">
                            <form action="#" class="form-horizontal" id="financesDataForm" method="post">
                                @csrf
                                <input type="hidden" name="id" value="{{ Auth::user()->id }}">

                                {{-- Banking --}}
                                <div class="section-divider">Banking Details</div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row mb-3">
                                            <label class="col-4 col-form-label">Bank Name</label>
                                            <div class="col-8">
                                                <input type="text" class="form-control" name="bank_name" value="{{ optional($user)->bank_name }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-4 col-form-label">Account Name</label>
                                            <div class="col-8">
                                                <input type="text" class="form-control" name="bank_account_name" value="{{ optional($user)->bank_account_name }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-4 col-form-label">Account Number</label>
                                            <div class="col-8">
                                                <input type="text" class="form-control" name="bank_account_number" value="{{ optional($user)->bank_account_number }}" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row mb-3">
                                            <label class="col-4 col-form-label">Bank Branch</label>
                                            <div class="col-8">
                                                <input type="text" class="form-control" name="bank_branch" value="{{ optional($user)->bank_branch }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-4 col-form-label">Account Type</label>
                                            <div class="col-8">
                                                <input type="text" class="form-control" name="bank_account_type" value="{{ optional($user)->bank_account_type ?? 'Savings' }}" readonly>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- No update button — banking is managed by admin only --}}
                                <p class="text-muted small mt-2">
                                    <i class="ri-information-line me-1"></i>
                                    Banking details can only be updated by an administrator.
                                </p>
                            </form>
                        </div>

                        {{-- ══════════════════════════════════════════════
                             TAB 3 — LEAVES
                        ══════════════════════════════════════════════ --}}
                        <div class="tab-pane" id="leaves">
                            <p class="text-muted">Leaves data not found.</p>
                        </div>

                        {{-- ══════════════════════════════════════════════
                             TAB 4 — CHANGE PASSWORD
                        ══════════════════════════════════════════════ --}}
                        <div class="tab-pane" id="password">
                            <form class="form-horizontal" id="changePasswordForm">
                                @csrf
                                <input type="hidden" name="id" value="{{ Auth::user()->id }}">
                                <div class="row mb-3">
                                    <label class="col-3 col-form-label">Current Password</label>
                                    <div class="col-9">
                                        <input type="password" class="form-control" name="currentpassword" placeholder="Enter current password">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-3 col-form-label">New Password</label>
                                    <div class="col-9">
                                        <input type="password" class="form-control" name="newpassword" placeholder="Enter new password">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-3 col-form-label">Confirm Password</label>
                                    <div class="col-9">
                                        <input type="password" class="form-control" name="comfirmpassword" placeholder="Retype new password">
                                    </div>
                                </div>
                                <div class="row justify-content-end">
                                    <div class="col-9 text-end">
                                        <button type="submit" class="btn btn-primary" id="submitChangePasswordBtn">
                                            <i class="ri-check-line me-1"></i> Submit
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                    </div>{{-- /.tab-content --}}
                </div>{{-- /.card-body --}}
            </div>{{-- /.card --}}
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     ABOUT THIS PAGE — modal
══════════════════════════════════════════════ --}}
<div class="modal fade" id="profileInfoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="profileInfoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileInfoModalLabel">
                    <i class="ri-information-line me-1"></i> About My Profile
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>
                    This page shows your personal, employment, and banking details on file.
                    You can update your name, contact details, address, and next of kin
                    information yourself from the <strong>Personal Info</strong> tab, and
                    change your password from the <strong>Change Password</strong> tab.
                </p>
                <p class="mb-0">
                    Employment fields (role, department, position, salary, dates) and banking
                    details are read-only here and can only be changed by an administrator.
                </p>
                <p class="mb-0 mt-2">
                    <i class="ri-customer-service-2-line me-1"></i>
                    If you need a copy of your profile as a PDF, or need to update a field
                    you can't edit yourself, please contact your administrator.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(function () {

    toastr.options = { closeButton: true, progressBar: true, timeOut: 5000 };

    // ── Info icon — open the About modal ──────────────────────────────────────
    $('#profileInfoBtn').click(function (e) {
        e.preventDefault();
        $('#profileInfoModal').modal('show');
    });

    // ── Show contract end date row if employment type is Contract ─────────────
    (function () {
        var type = '{{ optional($user)->employment_type }}';
        if (type === 'Contract') {
            $('#contract_end_date_row').show();
        }
    })();

    // ── Keep baseDataForm in sync as the user edits Tab 1 fields ─────────────
    $('#profileDataForm').on('input change', 'input, select, textarea', function () {
        var name = $(this).attr('name');
        if (name) {
            $('#baseDataForm input[name="' + name + '"]').val($(this).val());
        }
    });

    // ── TAB 1 — update personal info & next of kin ───────────────────────────
    $('#updateProfileInfoBtn').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this).prop('disabled', true);

        $.ajax({
            type:    'POST',
            url:     '{{ route("tenant.sales.retail.update.profile.info") }}',
            data:    $('#profileDataForm').serialize(),
            timeout: 60000,
            beforeSend: function () { $('#progressBar').show(); },
            complete:   function () { $('#progressBar').hide(); $btn.prop('disabled', false); },
            success: function (data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                } else if (data.status === 404) {
                    toastr.error(data.error, 'Error');
                } else if (data.status === 422) {
                    var msg = '';
                    $.each(data.errors, function (k, v) { msg += v + '\n'; });
                    toastr.error(msg, 'Validation Errors');
                } else {
                    toastr.error('Unspecified error occurred.', 'Error');
                }
            },
            error: function (xhr, status) {
                if (status === 'timeout') {
                    toastr.error('Request timed out. Please check your connection.', 'Timeout');
                } else if (xhr.status === 0) {
                    toastr.error('Network error. Please check your connection.', 'Connection Error');
                } else if (xhr.status === 422) {
                    var msg = '';
                    $.each(xhr.responseJSON.errors, function (k, v) { msg += v + '\n'; });
                    toastr.error(msg, 'Validation Errors');
                } else if (xhr.status === 500) {
                    toastr.error('Server error. Please try again later.', 'Server Error');
                } else {
                    toastr.error('An unexpected error occurred.', 'Error');
                }
            }
        });
    });

    // ── TAB 4 — change password ───────────────────────────────────────────────
    $('#submitChangePasswordBtn').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this).prop('disabled', true);

        $.ajax({
            type:    'POST',
            url:     '{{ route("tenant.sales.retail.profile.change.password") }}',
            data:    $('#changePasswordForm').serialize(),
            timeout: 60000,
            beforeSend: function () { $('#progressBar').show(); },
            complete:   function () { $('#progressBar').hide(); $btn.prop('disabled', false); },
            success: function (data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    $('#changePasswordForm')[0].reset();
                } else if (data.status === 422) {
                    toastr.error(data.error, 'Error');
                } else {
                    toastr.info('Operation completed.', 'Info');
                    $('#changePasswordForm')[0].reset();
                }
            },
            error: function (xhr, status) {
                if (xhr.status === 0 && xhr.readyState === 0) {
                    toastr.error('Timeout — check your connection.', 'Timeout');
                } else if (xhr.status === 422) {
                    var msg = '';
                    $.each(xhr.responseJSON.errors, function (k, v) { msg += v + '\n'; });
                    toastr.error(msg, 'Validation Errors');
                } else if (xhr.status === 500) {
                    toastr.error('Internal server error occurred.', 'Server Error');
                } else {
                    toastr.error('An unexpected error occurred.', 'Error');
                }
            }
        });
    });

});
</script>
@endsection