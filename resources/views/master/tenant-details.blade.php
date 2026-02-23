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
        border-radius: 10px 10px 0 0;
    }
    .card {
        border: none;
        box-shadow: 0 4px 8px rgba(0,0,0,.1);
        border-radius: 10px;
    }
    .card-header h4 {
        color: #fff;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
    }
    .card-header h4 i { margin-right: .25rem; }
    .card-body { padding: 1.5rem !important; }
    .form-label {
        font-weight: 500;
        color: #333;
        margin-bottom: 0;
    }
    .form-control:disabled,
    .form-control[readonly] {
        background-color: #f8f9fa;
        color: #6c757d;
        opacity: 1;
    }
    .row.mb-3 { margin-bottom: 1rem !important; }
    .btn-sm i { font-size: 0.875rem; }

    /* Back & Action Buttons */
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
</style>

<div class="progress" id="progressBar" role="progressbar" aria-label="Animated striped"
     aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"
     style="height:8px;transform:rotate(180deg);display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">

            <div class="row mb-3"></div>

            <div class="card">

                <!-- HEADER -->
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title mb-0">
                        <i class="ri-team-line"></i> Tenant Details & Actions
                    </h4>
                    <div class="d-flex align-items-center">
                        <a href="{{ route('master.tenants') }}" class="btn btn-light text-primary fs-16 mx-1" title="Back to Tenants">
                            <i class="ri-arrow-left-line"></i>
                        </a>
                        <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="actionsBtn" title="Actions">
                            <i class="ri-settings-2-line"></i>
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <?php
                        $tenantData = DB::table('tenants')->where('id', request('id'))->first();
                        $plan = DB::table('subscription_plans')
                            ->where('id', optional($tenantData)->subscription_plan)
                            ->first();
                    ?>

                    <form action="#" method="POST" id="tenantForm">
                        @csrf
                        <input type="hidden" name="id" value="{{ request('id') }}">

                        <div class="row">
                            <!-- Left Column -->
                            <div class="col-md-6">
                                <div class="row mb-3">
                                    <label for="full_name" class="col-3 col-form-label">Full Name</label>
                                    <div class="col-9">
                                        <input type="text" class="form-control" name="full_name" id="full_name"
                                               value="{{ optional($tenantData)->full_name }}" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="email" class="col-3 col-form-label">Email</label>
                                    <div class="col-9">
                                        <input type="email" class="form-control" name="email" id="email"
                                               value="{{ optional($tenantData)->email }}" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="phone_number" class="col-3 col-form-label">Phone Number</label>
                                    <div class="col-9">
                                        <input type="tel" class="form-control" name="phone_number" id="phone_number"
                                               value="{{ optional($tenantData)->phone_number }}" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="physical_address" class="col-3 col-form-label">Physical Address</label>
                                    <div class="col-9">
                                        <input type="text" class="form-control" name="physical_address" id="physical_address"
                                               value="{{ optional($tenantData)->physical_address }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="postal_address" class="col-3 col-form-label">Postal Address</label>
                                    <div class="col-9">
                                        <input type="text" class="form-control" name="postal_address" id="postal_address"
                                               value="{{ optional($tenantData)->postal_address }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="status" class="col-3 col-form-label">Status</label>
                                    <div class="col-9">
                                        <select class="form-select" name="status" disabled>
                                            <option value="{{ optional($tenantData)->status }}" selected>
                                                {{ ucfirst(optional($tenantData)->status) }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="put_on_hold" class="col-3 col-form-label">Put On Hold</label>
                                    <div class="col-9">
                                        <input type="text" class="form-control" disabled
                                               value="{{ optional($tenantData)->put_on_hold == 'Yes' ? 'Yes' : 'No' }}">
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="col-md-6">
                                <div class="row mb-3">
                                    <label for="business_name" class="col-3 col-form-label">Business Name</label>
                                    <div class="col-9">
                                        <input type="text" class="form-control" name="business_name" id="business_name"
                                               value="{{ optional($tenantData)->business_name }}" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="client_url" class="col-3 col-form-label">Client URL</label>
                                    <div class="col-9">
                                        <input type="url" class="form-control" disabled
                                               value="{{ optional($tenantData)->client_url }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="database_name" class="col-3 col-form-label">Database</label>
                                    <div class="col-9">
                                        <input type="text" class="form-control" disabled
                                               value="{{ optional($tenantData)->data }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="subscription_plan" class="col-3 col-form-label">Subscription Plan</label>
                                    <div class="col-9">
                                        <input type="text" class="form-control" disabled
                                               value="{{ optional($plan)->plan_name }} {{ optional($plan)->plan_period }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="payment_amount" class="col-3 col-form-label">Payment Amount</label>
                                    <div class="col-9">
                                        <input type="text" class="form-control" disabled
                                               value="{{ optional($plan)->plan_amount }} {{optional($plan)->plan_currency}}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="last_payment_date" class="col-3 col-form-label">Last Payment Date</label>
                                    <div class="col-9">
                                        <input type="date" class="form-control" disabled
                                               value="{{ optional($tenantData)->last_payment_date }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label for="next_payment_date" class="col-3 col-form-label">Next Payment Date</label>
                                    <div class="col-9">
                                        <input type="date" class="form-control" disabled
                                               value="{{ optional($tenantData)->next_payment_date }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="justify-content-end row mt-3">
                            <div class="col-9 text-end">
                                <button type="submit" class="btn btn-primary" id="updateDataBtn">Update Details</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ====================== MODALS ====================== -->

<!-- Actions Modal -->
<section>
    <div class="modal fade" id="actionsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tenant Actions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php $status = optional($tenantData)->status; ?>

                    @if($status == 'Pending')
                        <a href="#" class="btn btn-primary btn-sm mb-2 me-2" id="approveBtn">
                            <i class="ri-checkbox-circle-line"></i> Approve Tenant
                        </a>
                    @endif

                    <a href="#" class="btn btn-warning btn-sm mb-2 me-2">
                        @if(optional($tenantData)->put_on_hold == 'Yes')
                            <i class="ri-lock-unlock-line"></i> Remove Hold
                        @else
                            <i class="ri-lock-2-line"></i> Put on Hold
                        @endif
                    </a>

                    <a href="#" class="btn btn-dark btn-sm mb-2 me-2">
                        <i class="ri-calendar-2-line"></i> Change Payment Dates
                    </a>

                    <a href="#" class="btn btn-secondary btn-sm mb-2 me-2">
                        <i class="ri-exchange-dollar-line"></i> Change Plan
                    </a>

                    <a href="#" class="btn btn-success btn-sm mb-2 me-2">
                        <i class="ri-notification-2-line"></i> Send Reminder
                    </a>

                    <a href="#" class="btn btn-info btn-sm mb-2 me-2 send-invoice-trigger">
                        <i class="ri-file-pdf-2-line"></i> Send Invoice
                    </a>

                    <a href="{{route('master.tenant.send.invoice')}}" class="btn btn-light btn-sm mb-2 me-2">
                        <i class="ri-global-line"></i> Change URL
                    </a>

                    <a href="#" class="btn btn-danger btn-sm mb-2 me-2">
                        <i class="ri-delete-bin-2-line"></i> Delete Tenant
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Single Approve Modal (used for both local & remote) -->
<section>
    <div class="modal fade" id="approveModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Approve Tenant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="approveTenantForm">
                        @csrf
                        <input type="hidden" name="id" value="{{ optional($tenantData)->id }}">
                        <input type="hidden" name="approved_by" value="1">

                        <div class="mb-3">
                            <label class="form-label">Tenant Name</label>
                            <input type="text" class="form-control" disabled value="{{ optional($tenantData)->full_name }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Business Name</label>
                            <input type="text" class="form-control" disabled value="{{ optional($tenantData)->business_name }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Client URL Prefix</label>
                            <small class="text-muted d-block mb-2">
                                e.g., <code>netacube.net/clientprefix</code> enter <code>clientprefix</code>
                            </small>
                            <input type="text" class="form-control text-lowercase" name="client_url" required
                                   placeholder="e.g., netamind" maxlength="50">
                        </div>

                        <div class="text-end">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="submitApproveBtn">
                                <i class="ri-checkbox-circle-line"></i> Approve
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Send Invoice Modal -->
<section>
    <div class="modal fade" id="sendInvoiceModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-file-pdf-2-line"></i> Send Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="sendInvoiceForm">
                    @csrf
                    <input type="hidden" name="tenant_id" value="{{ request('id') }}">

                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Tenant Name</label>
                                <input type="text" class="form-control" disabled 
                                       value="{{ optional($tenantData)->full_name }} ({{ optional($tenantData)->business_name }})">
                            </div>
                        </div>

                        <div class="border p-3 rounded bg-light mb-3">
                            <div class="fw-bold text-success mb-2">
                            Subscription Plan
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-2">
                                    <label class="form-label">Plan</label>
                                    <input type="text" class="form-control" disabled 
                                           value="{{ optional($plan)->plan_name }} {{ optional($plan)->plan_period_name ?? optional($plan)->plan_period }}">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Amount</label>
                                    <input type="text" class="form-control" disabled 
                                           value="{{ optional($plan)->plan_amount }} {{ strtoupper(optional($plan)->plan_currency) }}">
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method Field -->
                        <div class="mb-3">
                            <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select" name="payment_method" required>
                                <option value="">-- Select Payment Method --</option>
                                @foreach(\DB::table('payment_methods')->orderBy('method_type')->get() as $method)
                                    <option value="{{ $method->id }}">
                                        {{ $method->method_type }}
                                        @if($method->method_type === 'Bank')
                                            - {{ $method->bank_name }} ({{ $method->account_number }})
                                        @elseif($method->method_type === 'Mobile')
                                            - {{ $method->mobile_operator }} ({{ $method->mobile_number }})
                                        @elseif($method->method_type === 'Paypal')
                                            - {{ $method->paypal_email }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="sendInvoiceBtn">
                            <i class="ri-send-plane-line"></i> Send Invoice
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

@endsection 

@section('scripts')

<script>
$(document).ready(function() {
    toastr.options = {
        closeButton: true,
        progressBar: true,
        showMethod: 'slideDown',
        timeOut: 5000,
        allowHtml: true
    };

    $('#actionsBtn').click(e => {
        e.preventDefault();
        $('#actionsModal').modal('show');
    });

    // ── Single Approve Button Handler ───────────────────────────────
    $('#approveBtn').click(e => {
        e.preventDefault();
        $('#approveModal').modal('show');
        $('#actionsModal').modal('hide');
    });

    // Open Send Invoice Modal
    $('.send-invoice-trigger').on('click', function(e) {
        e.preventDefault();
        $('#actionsModal').modal('hide');
        setTimeout(() => $('#sendInvoiceModal').modal('show'), 350);
    });

    // ==================== UPDATE TENANT DETAILS ====================
    $('#updateDataBtn').click(function(e) {
        var self = $(this);
        $(this).prop("disabled", true);
        var form = document.getElementById("tenantForm");
        e.preventDefault();

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $.ajax({
            type: "POST",
            url: "{{ route('master.tenant.details.update') }}",
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
                    toastr.success(data.success, 'Success', { timeOut: 5000, progressBar: true });
                } else if (data.status === 409) {
                    toastr.info(data.error, 'No Changes', { timeOut: 5000, progressBar: true });
                } else {
                    toastr.info('Unexpected response.', 'Info', { timeOut: 5000, progressBar: true });
                }
            },
            error: function(xhr, status, error) {
                // ... (same error handling as before) ...
                if (status === 'timeout') {
                    toastr.error('The request timed out. Please check your internet connection and try again.', 'Timeout Error', { timeOut: 5000, progressBar: true });
                } else if (xhr.status === 0) {
                    toastr.error('Unable to connect. Please check your internet connection and try again.', 'Connection Error', { timeOut: 5000, progressBar: true });
                } else if (xhr.status === 422) {
                    var errorPassage = '';
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        errorPassage += value + '\n';
                    });
                    toastr.error(errorPassage, 'Validation Errors', { timeOut: 5000, progressBar: true });
                } else if (xhr.status === 404) {
                    toastr.error(xhr.responseJSON?.error || 'Tenant not found.', 'Not Found', { timeOut: 5000, progressBar: true });
                } else if (xhr.status === 500) {
                    toastr.error('Server error occurred. Please refresh the page and try again.', 'Server Error', { timeOut: 5000, progressBar: true });
                } else {
                    toastr.error('Unspecified error occurred. Try again later.', 'Error', { timeOut: 5000, progressBar: true });
                }
            }
        });
    });

    // ==================== APPROVE TENANT (single handler) ====================
    $('#submitApproveBtn').click(function(e) {
        var self = $(this);
        self.prop("disabled", true);
        var form = document.getElementById("approveTenantForm");
        e.preventDefault();

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $.ajax({
            type: "POST",
            url: "{{ route('master.tenant.approve') }}",   // ← your new unified route
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
                    toastr.success(data.success, 'Success', {
                        timeOut: 5000,
                        progressBar: true
                    });
                    $('#approveModal').modal('hide');
                    setTimeout(() => location.reload(), 1500);
                } else if (data.status === 203) {
                    toastr.info(data.errors[0], 'Already Approved', {
                        timeOut: 5000,
                        progressBar: true
                    });
                    $('#approveModal').modal('hide');
                } else {
                    toastr.info('Unexpected response.', 'Info', {
                        timeOut: 5000,
                        progressBar: true
                    });
                }
            },
            error: function(xhr, status, error) {
                // Reuse the same detailed error handling as before
                if (status === 'timeout') {
                    toastr.error('The request timed out. Please check your internet connection and try again.', 'Timeout Error', { timeOut: 5000, progressBar: true });
                } else if (xhr.status === 0) {
                    toastr.error('Unable to connect. Please check your internet connection and try again.', 'Connection Error', { timeOut: 5000, progressBar: true });
                } else if (xhr.status === 422) {
                    var errorPassage = '';
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        errorPassage += value + '\n';
                    });
                    toastr.error(errorPassage, 'Validation Errors', { timeOut: 5000, progressBar: true });
                } else if (xhr.status === 404) {
                    toastr.error(xhr.responseJSON?.errors?.[0] || 'Tenant not found.', 'Not Found', { timeOut: 5000, progressBar: true });
                } 

                else if (xhr.status === 500) {
                    let errorMsg = 'Server error occurred. Please try again.';
                    
                    // Try to get the real message from Laravel's JSON response
                    if (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.length > 0) {
                        errorMsg = xhr.responseJSON.errors[0];   // ← this will show "Unresolved application environment..."
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    
                    toastr.error(errorMsg, 'Error', {
                        timeOut: 8000,
                        progressBar: true,
                        closeButton: true
                    });
                   }
                
                else {
                    toastr.error('Unspecified error occurred. Try again later.', 'Error', { timeOut: 5000, progressBar: true });
                }
            }
        });
    });

    // Send Invoice handler remains unchanged
    $('#sendInvoiceBtn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        $btn.prop('disabled', true);

        var formData = $('#sendInvoiceForm').serialize();
        formData += '&useSubscriptionPlan=1';

        $.ajax({
            type: 'POST',
            url: '{{ route("master.tenant.send.invoice") }}',
            data: formData,
            timeout: 60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete: function() { $('#progressBar').hide(); $btn.prop('disabled', false); },
            success: function(data) {
                var msg = data.success || 'Invoice created and sent successfully!';
                if (data.invoice_number) {
                    msg += '<br><small><strong>Invoice #:</strong> ' + data.invoice_number + '</small>';
                }
                toastr.success(msg, 'Success', { timeOut: 10000, escapeHtml: false });
                $('#sendInvoiceModal').modal('hide');
                $('#sendInvoiceForm')[0].reset();
            },
            error: function(xhr) {
                var errorMessage = 'An unexpected error occurred.';
                if (xhr.status === 422 && xhr.responseJSON) {
                    if (xhr.responseJSON.error) {
                        errorMessage = xhr.responseJSON.error;
                    } else if (xhr.responseJSON.errors) {
                        errorMessage = '';
                        $.each(xhr.responseJSON.errors, function(field, msgs) {
                            errorMessage += (Array.isArray(msgs) ? msgs.join('<br>') : msgs) + '<br>';
                        });
                    }
                } else if (xhr.status === 404) {
                    errorMessage = xhr.responseJSON?.error || 'Tenant not found.';
                } else if (xhr.status === 500) {
                    errorMessage = 'Server error. Please try again later.';
                }
                toastr.error(errorMessage, 'Error', { timeOut: 12000, escapeHtml: false });
            }
        });
    });

});
</script>

@endsection