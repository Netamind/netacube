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

    .custom-invoice-section {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 1.25rem;
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
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tenant Actions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php $status = optional($tenantData)->status; ?>

                    @if($status == 'Pending')
                        <a href="#" class="btn btn-primary btn-sm mb-2 me-2" id="approveBtn">
                            <i class="ri-checkbox-circle-line"></i> Approve tenant
                        </a>
                    @else 
                        <input type="text" class="form-control btn-sm mb-2 me-2" value="Tenant already approved" disabled>
                    @endif

                    <a href="#" class="btn btn-warning form-control btn-sm mb-2 me-2">
                        @if(optional($tenantData)->put_on_hold == 'Yes')
                            <i class="ri-lock-unlock-line"></i> Remove on hold
                        @else
                            <i class="ri-lock-2-line"></i> Put on hold
                        @endif
                    </a>

                    <a href="#" class="btn btn-dark form-control btn-sm mb-2 me-2">
                        <i class="ri-calendar-2-line"></i> Change payment dates
                    </a>

                    <a href="#" class="btn btn-secondary form-control btn-sm mb-2 me-2">
                        <i class="ri-exchange-dollar-line"></i> Change subscription plan
                    </a>

                    <a href="#" class="btn btn-success form-control btn-sm mb-2 me-2 send-invoice-trigger" data-type="system">
                        <i class="ri-file-pdf-2-line"></i> Send system invoice
                    </a>

                    <a href="#" class="btn btn-info form-control btn-sm mb-2 me-2 send-invoice-trigger" data-type="custom">
                        <i class="ri-file-pdf-2-line"></i> Send custom invoice
                    </a>
                
                    <a href="#" class="btn btn-danger form-control btn-sm mb-2 me-2">
                        <i class="ri-delete-bin-2-line"></i> Delete tenant
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Approve Modal -->
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

<!-- Send Invoice Modal (Supports both System and Custom) -->
<section>
    <div class="modal fade" id="sendInvoiceModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="ri-file-pdf-2-line"></i> Send Invoice
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="sendInvoiceForm">
                    @csrf
                    <input type="hidden" name="tenant_id" value="{{ request('id') }}">
                    <input type="hidden" name="is_custom" id="is_custom" value="0">

                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Tenant Name</label>
                                <input type="text" class="form-control" disabled 
                                       value="{{ optional($tenantData)->full_name }} ({{ optional($tenantData)->business_name }})">
                            </div>
                        </div>

                        <!-- System Invoice Section -->
                        <div id="systemSection" class="border p-3 rounded bg-light mb-3">
                            <div class="fw-bold text-success mb-2">Subscription Plan</div>
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

                        <!-- Custom Invoice Fields -->
                        <div id="customSection" class="custom-invoice-section mb-3" style="display: none;">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label">Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control" name="description" id="description" rows="4"
                                              placeholder="Enter invoice description (e.g., One-time setup fee, Website maintenance, etc.)" required></textarea>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Amount <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="currencySymbol">USD</span>
                                        <input type="number" step="0.01" min="0.01" class="form-control" 
                                               name="amount" id="amount" placeholder="0.00" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Currency <span class="text-danger">*</span></label>
                                    <select class="form-select" name="currency" id="currency" required>
                                        <option value="">-- Select Currency --</option>
                                        @foreach(DB::table('currency')->orderBy('name')->get() as $curr)
                                            <option value="{{ $curr->code }}">{{ $curr->name }} ({{ $curr->code }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 mt-3">
                                    <label class="form-label">Due Date</label>
                                    <input type="date" class="form-control" name="due_date" 
                                           value="{{ now()->addDays(14)->format('Y-m-d') }}">
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method (common) -->
                        <div class="mb-3">
                            <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select" name="payment_method" required>
                                <option value="">-- Select Payment Method --</option>
                                @foreach(DB::table('payment_methods')->orderBy('method_type')->get() as $method)
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

    // Approve Tenant
    $('#approveBtn').click(e => {
        e.preventDefault();
        $('#approveModal').modal('show');
        $('#actionsModal').modal('hide');
    });

    // Open Send Invoice Modal - Distinguish System vs Custom
    $('.send-invoice-trigger').on('click', function(e) {
        e.preventDefault();
        $('#actionsModal').modal('hide');

        const type = $(this).data('type'); // 'system' or 'custom'

        $('#is_custom').val(type === 'custom' ? 1 : 0);
        $('#modalTitle').html(type === 'custom' 
            ? '<i class="ri-file-pdf-2-line"></i> Send Custom Invoice' 
            : '<i class="ri-file-pdf-2-line"></i> Send System Invoice'
        );

        if (type === 'custom') {
            $('#systemSection').hide();
            $('#customSection').show();
        } else {
            $('#systemSection').show();
            $('#customSection').hide();
        }

        setTimeout(() => $('#sendInvoiceModal').modal('show'), 350);
    });

    // ==================== UPDATE TENANT DETAILS ====================
    $('#updateDataBtn').click(function(e) {
        var self = $(this);
        self.prop("disabled", true);
        var form = document.getElementById("tenantForm");
        e.preventDefault();

        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        $.ajax({
            type: "POST",
            url: "{{ route('master.tenant.details.update') }}",
            data: $(form).serialize(),
            timeout: 60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete: function() { $('#progressBar').hide(); self.prop("disabled", false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success', { timeOut: 5000, progressBar: true });
                } else if (data.status === 409) {
                    toastr.info(data.error, 'No Changes', { timeOut: 5000, progressBar: true });
                }
            },
            error: function(xhr, status) {
                if (status === 'timeout') {
                    toastr.error('Request timed out.', 'Timeout');
                } else if (xhr.status === 422) {
                    let msg = '';
                    $.each(xhr.responseJSON.errors || {}, (k, v) => msg += v + '<br>');
                    toastr.error(msg, 'Validation Error');
                } else {
                    toastr.error('An error occurred.', 'Error');
                }
            }
        });
    });

    // ==================== APPROVE TENANT ====================
    $('#submitApproveBtn').click(function(e) {
        var self = $(this);
        self.prop("disabled", true);
        var form = document.getElementById("approveTenantForm");
        e.preventDefault();

        $.ajaxSetup({
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
        });

        $.ajax({
            type: "POST",
            url: "{{ route('master.tenant.approve') }}",
            data: $(form).serialize(),
            timeout: 60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete: function() { $('#progressBar').hide(); self.prop("disabled", false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    $('#approveModal').modal('hide');
                    setTimeout(() => location.reload(), 1500);
                } else if (data.status === 203) {
                    toastr.info(data.errors[0], 'Already Approved');
                    $('#approveModal').modal('hide');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    let msg = '';
                    $.each(xhr.responseJSON.errors || {}, (k, v) => msg += v + '<br>');
                    toastr.error(msg, 'Validation Error');
                } else {
                    toastr.error('An error occurred.', 'Error');
                }
            }
        });
    });

    // ==================== SEND INVOICE (System or Custom) ====================
    $('#sendInvoiceBtn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        $btn.prop('disabled', true);

        var isCustom = $('#is_custom').val() == "1";
        var url = isCustom 
            ? '{{ route("master.tenant.send.custom.invoice") }}' 
            : '{{ route("master.tenant.send.invoice") }}';

        var formData = $('#sendInvoiceForm').serialize();

        $.ajax({
            type: 'POST',
            url: url,
            data: formData,
            timeout: 60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete: function() { 
                $('#progressBar').hide(); 
                $btn.prop('disabled', false); 
            },
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
                    errorMessage = xhr.responseJSON.error || Object.values(xhr.responseJSON.errors || {}).flat().join('<br>');
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