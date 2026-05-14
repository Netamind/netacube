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
    .paye-toggle-wrap {
        display: flex;
        align-items: center;
        height: calc(1.5em + 0.75rem + 2px);
        padding-top: calc(0.375rem + 1px);
    }
    .paye-toggle-wrap .form-check-input { margin-top: 0; }
    .paye-toggle-wrap .form-check-label { margin-left: 0.5rem; font-weight: 500; }
</style>

<div class="progress" id="progressBar" role="progressbar" aria-label="Animated striped"
     aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"
     style="height:8px; transform:rotate(180deg); display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

{{--
    Hidden base form — carries ALL fields every tab needs so the backend
    validation for required fields never fails on a partial tab submit.
    on_paye is a checkbox so we carry it as a plain hidden 0/1 here;
    the tab form's value wins (it comes second in mergedData()).
--}}
<form id="baseDataForm" style="display:none">
    @csrf
    <input type="hidden" name="id"                          value="{{ $user->id }}">
    <input type="hidden" name="name"                        value="{{ $user->name }}">
    <input type="hidden" name="phone"                       value="{{ $user->phone }}">
    <input type="hidden" name="email"                       value="{{ $user->email }}">
    <input type="hidden" name="role"                        value="{{ $user->role }}">
    <input type="hidden" name="branch"                      value="{{ $user->branch }}">
    <input type="hidden" name="dob"                         value="{{ $user->dob }}">
    <input type="hidden" name="idtype"                      value="{{ $user->idtype }}">
    <input type="hidden" name="idnumber"                    value="{{ $user->idnumber }}">
    <input type="hidden" name="home_address"                value="{{ $user->home_address }}">
    <input type="hidden" name="current_residence"           value="{{ $user->current_residence }}">
    <input type="hidden" name="department"                  value="{{ $user->department }}">
    <input type="hidden" name="position"                    value="{{ $user->position }}">
    <input type="hidden" name="gross_salary"                value="{{ $user->gross_salary }}">
    <input type="hidden" name="started_on"                  value="{{ $user->started_on }}">
    <input type="hidden" name="employment_type"             value="{{ $user->employment_type ?? 'Full-time' }}">
    <input type="hidden" name="contract_end_date"           value="{{ $user->contract_end_date }}">
    <input type="hidden" name="on_paye"                     value="{{ $user->on_paye ? '1' : '0' }}">
    <input type="hidden" name="nextofkin_name"              value="{{ $user->nextofkin_name }}">
    <input type="hidden" name="nextofkin_relationship"      value="{{ $user->nextofkin_relationship }}">
    <input type="hidden" name="nextofkin_physical_address"  value="{{ $user->nextofkin_physical_address }}">
    <input type="hidden" name="nextofkin_contact"           value="{{ $user->nextofkin_contact }}">
    <input type="hidden" name="bank_name"                   value="{{ $user->bank_name }}">
    <input type="hidden" name="bank_account_name"           value="{{ $user->bank_account_name }}">
    <input type="hidden" name="bank_account_number"         value="{{ $user->bank_account_number }}">
    <input type="hidden" name="bank_branch"                 value="{{ $user->bank_branch }}">
    <input type="hidden" name="bank_account_type"           value="{{ $user->bank_account_type ?? 'Savings' }}">
</form>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">

            <div class="row mb-3"></div>

            <div class="card">

                {{-- Header --}}
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title mb-0">Employee Details</h4>
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
                                <input type="hidden" name="id" value="{{ $user->id }}">

                                {{-- Personal --}}
                                <div class="section-divider">Personal Information</div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Name</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="name" value="{{ $user->name }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Phone</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="phone" value="{{ $user->phone }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Email</label>
                                            <div class="col-9">
                                                <input type="email" class="form-control" name="email" value="{{ $user->email }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Date of Birth</label>
                                            <div class="col-9">
                                                <input type="date" class="form-control" name="dob" value="{{ $user->dob }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">ID Type</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="idtype" value="{{ $user->idtype }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">ID Number</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="idnumber" value="{{ $user->idnumber }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Home Address</label>
                                            <div class="col-9">
                                                <textarea name="home_address" class="form-control" rows="2">{{ $user->home_address }}</textarea>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Current Residence</label>
                                            <div class="col-9">
                                                <textarea name="current_residence" class="form-control" rows="2">{{ $user->current_residence }}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Added On</label>
                                            <div class="col-9">
                                                <input type="date" class="form-control" name="entered_on" value="{{ $user->entered_on }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Active</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="active" value="{{ $user->active ?? 'Yes' }}" readonly>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Branch</label>
                                            <div class="col-9">
                                                <select class="form-control" name="branch">
                                                    <option value="{{ $user->branch }}">
                                                        {{ DB::connection('tenant')->table('branches')->where('id', $user->branch)->value('name') }}
                                                    </option>
                                                    @foreach($branches as $b)
                                                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Role</label>
                                            <div class="col-9">
                                                <select class="form-control" name="role">
                                                    <option value="{{ $user->role }}">{{ $user->role }}</option>
                                                    @foreach($roles as $r)
                                                        <option value="{{ $r->role }}">{{ $r->role }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Department</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="department" value="{{ $user->department }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Position</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="position" value="{{ $user->position }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Started On</label>
                                            <div class="col-9">
                                                <input type="date" class="form-control" name="started_on" value="{{ $user->started_on }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Employment Type</label>
                                            <div class="col-9">
                                                <select class="form-control" name="employment_type" id="employment_type">
                                                    <option value="Full-time"  {{ $user->employment_type === 'Full-time'  ? 'selected' : '' }}>Full-time</option>
                                                    <option value="Part-time"  {{ $user->employment_type === 'Part-time'  ? 'selected' : '' }}>Part-time</option>
                                                    <option value="Contract"   {{ $user->employment_type === 'Contract'   ? 'selected' : '' }}>Contract</option>
                                                    <option value="Casual"     {{ $user->employment_type === 'Casual'     ? 'selected' : '' }}>Casual</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mb-3" id="contract_end_date_row">
                                            <label class="col-3 col-form-label">Contract End Date</label>
                                            <div class="col-9">
                                                <input type="date" class="form-control" name="contract_end_date" value="{{ $user->contract_end_date }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Gross Salary</label>
                                            <div class="col-9">
                                                <input type="number" class="form-control" name="gross_salary" value="{{ $user->gross_salary }}">
                                            </div>
                                        </div>

                                        {{-- ── ON PAYE ── --}}
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">PAYE Deduction</label>
                                            <div class="col-9">
                                                <div class="form-check form-switch paye-toggle-wrap mt-1">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                           name="on_paye" id="on_paye" value="1"
                                                           {{ $user->on_paye ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="on_paye">Subject to PAYE</label>
                                                </div>
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
                                                <input type="text" class="form-control" name="nextofkin_name" value="{{ $user->nextofkin_name }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Relationship</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="nextofkin_relationship" value="{{ $user->nextofkin_relationship }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Physical Address</label>
                                            <div class="col-9">
                                                <textarea name="nextofkin_physical_address" class="form-control" rows="2">{{ $user->nextofkin_physical_address }}</textarea>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-3 col-form-label">Contact</label>
                                            <div class="col-9">
                                                <input type="text" class="form-control" name="nextofkin_contact" value="{{ $user->nextofkin_contact }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Action buttons --}}
                                <div class="row justify-content-end">
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

                        {{-- ══════════════════════════════════════════════
                             TAB 2 — FINANCES & BANKING
                        ══════════════════════════════════════════════ --}}
                        <div class="tab-pane" id="finances">
                            <form action="#" class="form-horizontal" id="financesDataForm" method="post">
                                @csrf
                                <input type="hidden" name="id" value="{{ $user->id }}">

                                {{-- Banking --}}
                                <div class="section-divider">Banking Details</div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="row mb-3">
                                            <label class="col-4 col-form-label">Bank Name</label>
                                            <div class="col-8">
                                                <input type="text" class="form-control" name="bank_name" value="{{ $user->bank_name }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-4 col-form-label">Account Name</label>
                                            <div class="col-8">
                                                <input type="text" class="form-control" name="bank_account_name" value="{{ $user->bank_account_name }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-4 col-form-label">Account Number</label>
                                            <div class="col-8">
                                                <input type="text" class="form-control" name="bank_account_number" value="{{ $user->bank_account_number }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row mb-3">
                                            <label class="col-4 col-form-label">Bank Branch</label>
                                            <div class="col-8">
                                                <input type="text" class="form-control" name="bank_branch" value="{{ $user->bank_branch }}">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <label class="col-4 col-form-label">Account Type</label>
                                            <div class="col-8">
                                                <select class="form-control" name="bank_account_type">
                                                    <option value="Savings" {{ $user->bank_account_type === 'Savings' ? 'selected' : '' }}>Savings</option>
                                                    <option value="Current" {{ $user->bank_account_type === 'Current' ? 'selected' : '' }}>Current</option>
                                                    <option value="Cheque"  {{ $user->bank_account_type === 'Cheque'  ? 'selected' : '' }}>Cheque</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Action buttons --}}
                                <div class="row justify-content-end">
                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn btn-primary" id="updateFinancesBtn">
                                            <i class="ri-save-line me-1"></i> Update
                                        </button>
                                    </div>
                                </div>
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
                    </div>
                    <div class="form-group">
                        <a href="#" class="btn btn-danger" id="submitSingleDeleteDataBtn" style="margin-top:10px;margin-bottom:10px;margin-right:5px">Yes, Delete it</a>
                        <a href="#" class="btn btn-info"   id="keepSingleDataBtn"          style="margin-top:10px;margin-bottom:10px;">No, Keep it</a>
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
    toastr.options = { closeButton: true, progressBar: true, timeOut: 5000 };

    // ── Contract end date toggle ──────────────────────────────────────────────
    function toggleContractRow() {
        $('#contract_end_date_row').toggle($('#employment_type').val() === 'Contract');
    }
    toggleContractRow();
    $('#employment_type').on('change', toggleContractRow);

    /**
     * Merge baseDataForm (all required fields) with the tab-specific form.
     * The tab form values come second so they WIN over the base values.
     * Checkboxes are NOT serialised when unchecked, so we handle on_paye
     * explicitly: if the tab form contains an on_paye field we use it;
     * otherwise we strip on_paye from the base and send 0.
     *
     * For Tab 1 (profileDataForm) the checkbox is present in the form itself
     * so we call it directly. For Tab 2 (financesDataForm) we use mergedData.
     */
    function mergedData(tabFormSelector) {
        // Start with base data minus any on_paye entry —
        // the base carries a hidden 0/1 but the tab form checkbox is the truth.
        var base = $('#baseDataForm').serialize()
                       .split('&')
                       .filter(function (p) { return p.indexOf('on_paye') === -1; })
                       .join('&');
        return base + '&' + $(tabFormSelector).serialize();
    }

    // Keep baseDataForm in sync when the user edits Tab 1 fields,
    // so subsequent tab saves carry the latest personal values.
    $('#profileDataForm').on('input change', 'input, select, textarea', function () {
        var name = $(this).attr('name');
        if (!name) return;
        // For the checkbox we store 0/1 in the hidden field
        if (name === 'on_paye') {
            $('#baseDataForm input[name="on_paye"]').val($(this).is(':checked') ? '1' : '0');
        } else {
            $('#baseDataForm input[name="' + name + '"]').val($(this).val());
        }
    });

    // ── TAB 1 — update personal info ─────────────────────────────────────────
    $('#updateProfileInfoBtn').on('click', function (e) {
        e.preventDefault();
        const $btn = $(this).prop('disabled', true);

        $.ajax({
            url:    '{{ route("tenant.admin.employee.details.update") }}',
            method: 'POST',
            data:   $('#profileDataForm').serialize(),
            timeout: 60000,
            beforeSend: () => $('#progressBar').show(),
            complete:   () => { $('#progressBar').hide(); $btn.prop('disabled', false); },
            success: data => {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                } else if (data.status === 203) {
                    toastr.error(data.error, 'Error');
                } else if (data.status === 422) {
                    let msg = ''; $.each(data.errors, (k, v) => msg += v + '\n');
                    toastr.error(msg, 'Validation');
                } else {
                    toastr.error(data.error || 'Update failed', 'Error');
                }
            },
            error: xhr => {
                if (xhr.status === 422) {
                    let msg = ''; $.each(xhr.responseJSON.errors, (k, v) => msg += v + '\n');
                    toastr.error(msg, 'Validation');
                } else {
                    toastr.error('Server error', 'Error');
                }
            }
        });
    });

    // ── TAB 2 — update finances & banking ────────────────────────────────────
    $('#updateFinancesBtn').on('click', function (e) {
        e.preventDefault();
        const $btn = $(this).prop('disabled', true);

        $.ajax({
            url:    '{{ route("tenant.admin.employee.details.update") }}',
            method: 'POST',
            data:   mergedData('#financesDataForm'),
            timeout: 60000,
            beforeSend: () => $('#progressBar').show(),
            complete:   () => { $('#progressBar').hide(); $btn.prop('disabled', false); },
            success: data => {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                } else if (data.status === 203) {
                    toastr.error(data.error, 'Error');
                } else if (data.status === 422) {
                    let msg = ''; $.each(data.errors, (k, v) => msg += v + '\n');
                    toastr.error(msg, 'Validation');
                } else {
                    toastr.error(data.error || 'Update failed', 'Error');
                }
            },
            error: xhr => {
                if (xhr.status === 422) {
                    let msg = ''; $.each(xhr.responseJSON.errors, (k, v) => msg += v + '\n');
                    toastr.error(msg, 'Validation');
                } else {
                    toastr.error('Server error', 'Error');
                }
            }
        });
    });

    // ── TAB 4 — change password ───────────────────────────────────────────────
    $('#submitChangePasswordBtn').on('click', function (e) {
        e.preventDefault();
        const $btn = $(this).prop('disabled', true);

        $.ajax({
            url:    '{{ route("master.employee.change.password") }}',
            method: 'POST',
            data:   $('#changePasswordForm').serialize(),
            timeout: 60000,
            beforeSend: () => $('#progressBar').show(),
            complete:   () => { $('#progressBar').hide(); $btn.prop('disabled', false); },
            success: data => {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    $('#changePasswordForm')[0].reset();
                } else {
                    toastr.error(data.error || 'Failed', 'Error');
                }
            },
            error: xhr => {
                if (xhr.status === 422) {
                    let msg = ''; $.each(xhr.responseJSON.errors, (k, v) => msg += v + '\n');
                    toastr.error(msg, 'Validation');
                } else {
                    toastr.error('Server error', 'Error');
                }
            }
        });
    });

    // ── Delete ────────────────────────────────────────────────────────────────
    const delModal = new bootstrap.Modal('#singleDeleteDataModal');

    $('#deleteBtn').on('click', function (e) {
        e.preventDefault();
        $('#singleDeleteId').val($(this).data('id'));
        $('#singleDisplayDeleteLabel').text($(this).data('name'));
        $('#singleDeleteDataForm').attr('action', '{{ route("tenant.admin.employee.delete") }}');
        delModal.show();
    });

    $('#keepSingleDataBtn').on('click', function (e) {
        e.preventDefault();
        delModal.hide();
    });

    $('#submitSingleDeleteDataBtn').on('click', function (e) {
        e.preventDefault();
        const $btn = $(this).prop('disabled', true);

        $.ajax({
            url:    $('#singleDeleteDataForm').attr('action'),
            method: 'POST',
            data:   $('#singleDeleteDataForm').serialize() + '&_method=DELETE',
            beforeSend: () => $('#progressBar').show(),
            complete:   () => { $('#progressBar').hide(); $btn.prop('disabled', false); },
            success: res => {
                if (res.status === 201) {
                    toastr.success(res.success, 'Deleted');
                    delModal.hide();
                    setTimeout(() => location.href = '{{ route("tenant.admin.employees") }}', 800);
                }
            },
            error: xhr => {
                toastr.error(xhr.responseJSON?.message || 'Delete failed', 'Error');
            }
        });
    });
});
</script>
@endsection