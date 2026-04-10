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

<div class="progress" id="progressBar" role="progressbar" aria-label="Animated striped" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="height: 8px; transform: rotate(180deg); display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
</div>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">

            <div class="row mb-3"></div>

            <div class="card">

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
                        $approvedBy = optional($tenantData)->approved_by
                            ? DB::table('users')->where('id', optional($tenantData)->approved_by)->value('name')
                            : null;
                        $allPlans = DB::table('subscription_plans')->orderBy('plan_name')->get();
                    ?>

                    {{-- UPDATE FORM --}}
                    <form action="#" method="POST" id="tenantForm">
                        @csrf
                        <input type="hidden" name="id" value="{{ request('id') }}">

                        <div class="row">

                            <!-- LEFT COLUMN -->
                            <div class="col-md-6">

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Full Name</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" name="full_name" id="full_name"
                                               value="{{ optional($tenantData)->full_name }}" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Email</label>
                                    <div class="col-8">
                                        <input type="email" class="form-control" name="email" id="email"
                                               value="{{ optional($tenantData)->email }}" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Phone Number</label>
                                    <div class="col-8">
                                        <input type="tel" class="form-control" name="phone_number" id="phone_number"
                                               value="{{ optional($tenantData)->phone_number }}" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Physical Address</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" name="physical_address" id="physical_address"
                                               value="{{ optional($tenantData)->physical_address }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Postal Address</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" name="postal_address" id="postal_address"
                                               value="{{ optional($tenantData)->postal_address }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Status</label>
                                    <div class="col-8">
                                        <select class="form-select" name="status" disabled>
                                            <option value="{{ optional($tenantData)->status }}" selected>
                                                {{ ucfirst(optional($tenantData)->status) }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Put On Hold</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" id="putOnHoldDisplay" disabled
                                               value="{{ optional($tenantData)->put_on_hold == 'Yes' ? 'Yes' : 'No' }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Approved At</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" disabled
                                               value="{{ optional($tenantData)->approved_at ? \Carbon\Carbon::parse(optional($tenantData)->approved_at)->format('d M Y, H:i') : '—' }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Approved By</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" disabled
                                               value="{{ $approvedBy ?? '—' }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Created At</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" disabled
                                               value="{{ optional($tenantData)->created_at ? \Carbon\Carbon::parse(optional($tenantData)->created_at)->format('d M Y, H:i') : '—' }}">
                                    </div>
                                </div>

                            </div>

                            <!-- RIGHT COLUMN -->
                            <div class="col-md-6">

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Business Name</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" name="business_name" id="business_name"
                                               value="{{ optional($tenantData)->business_name }}" required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Client Code</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" disabled
                                               value="{{ optional($tenantData)->client_url }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Login URL</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" disabled
                                               value="{{ optional($tenantData)->client_url ? url('/') . '/' . optional($tenantData)->client_url : '—' }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Database</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" disabled
                                               value="{{ optional($tenantData)->data }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Subscription Plan</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" id="subscriptionPlanDisplay" disabled
                                               value="{{ optional($plan)->plan_name }} {{ optional($plan)->plan_period }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Payment Amount</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" disabled
                                               value="{{ optional($plan)->plan_amount }} {{ optional($plan)->plan_currency }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Payment Method</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" disabled
                                               value="{{ optional($tenantData)->payment_method ?? '—' }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Last Payment Date</label>
                                    <div class="col-8">
                                        <input type="date" class="form-control" id="lastPaymentDisplay" disabled
                                               value="{{ optional($tenantData)->last_payment_date }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Next Payment Date</label>
                                    <div class="col-8">
                                        <input type="date" class="form-control" id="nextPaymentDisplay" disabled
                                               value="{{ optional($tenantData)->next_payment_date }}">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-4 col-form-label">Last Updated</label>
                                    <div class="col-8">
                                        <input type="text" class="form-control" disabled
                                               value="{{ optional($tenantData)->updated_at ? \Carbon\Carbon::parse(optional($tenantData)->updated_at)->format('d M Y, H:i') : '—' }}">
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="justify-content-end row mt-3">
                            <div class="col-9 text-end">
                                <button type="button" class="btn btn-danger me-2" id="deleteDataBtn">
                                    <i class="ri-delete-bin-2-line"></i> Delete Tenant
                                </button>
                                <button type="button" class="btn btn-primary" id="updateDataBtn">Update Details</button>
                            </div>
                        </div>
                    </form>

                    {{-- Hidden delete form — @csrf + id, serialized exactly like update --}}
                    <form id="deleteTenantForm" style="display:none;">
                        @csrf
                        <input type="hidden" name="id" value="{{ optional($tenantData)->id }}">
                    </form>

                    {{-- Hidden hold form --}}
                    <form id="holdTenantForm" style="display:none;">
                        @csrf
                        <input type="hidden" name="id" value="{{ optional($tenantData)->id }}">
                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- ====================== MODALS ====================== -->

<!-- Actions Modal -->
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
                    <a href="#" class="btn btn-primary form-control btn-sm mb-2 me-2" id="approveBtn">
                        <i class="ri-checkbox-circle-line"></i> Approve tenant
                    </a>
                @else
                    <input type="text" class="form-control btn-sm mb-2 me-2" value="Tenant already approved" disabled>
                @endif

                <a href="#" class="btn form-control btn-sm mb-2 me-2 {{ optional($tenantData)->put_on_hold == 'Yes' ? 'btn-success' : 'btn-warning' }}" id="holdBtn">
                    @if(optional($tenantData)->put_on_hold == 'Yes')
                        <i class="ri-lock-unlock-line"></i> Remove on hold
                    @else
                        <i class="ri-lock-2-line"></i> Put on hold
                    @endif
                </a>

                <a href="#" class="btn btn-dark form-control btn-sm mb-2 me-2" id="paymentDatesBtn">
                    <i class="ri-calendar-2-line"></i> Change payment dates
                </a>

                <a href="#" class="btn btn-secondary form-control btn-sm mb-2 me-2" id="subscriptionPlanBtn">
                    <i class="ri-exchange-dollar-line"></i> Change subscription plan
                </a>

                <a href="#" class="btn btn-success form-control btn-sm mb-2 me-2 send-invoice-trigger" data-type="system">
                    <i class="ri-file-pdf-2-line"></i> Send system invoice
                </a>

                <a href="#" class="btn btn-info form-control btn-sm mb-2 me-2 send-invoice-trigger" data-type="custom">
                    <i class="ri-file-pdf-2-line"></i> Send custom invoice
                </a>

                <a href="#" class="btn btn-danger form-control btn-sm mb-2 me-2" id="deleteBtnFromActions">
                    <i class="ri-delete-bin-2-line"></i> Delete tenant
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
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
                    <input type="hidden" name="user_id" value="{{ auth()->id() }}">

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

<!-- Put On Hold / Remove On Hold Modal -->
<div class="modal fade" id="holdModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm" style="max-width:380px; margin:1.75rem auto;">
        <div class="modal-content">
            <div class="modal-header {{ optional($tenantData)->put_on_hold == 'Yes' ? 'bg-success' : 'bg-warning' }} text-white">
                <h5 class="modal-title" id="holdModalTitle">
                    @if(optional($tenantData)->put_on_hold == 'Yes')
                        <i class="ri-lock-unlock-line me-1"></i> Remove On Hold
                    @else
                        <i class="ri-lock-2-line me-1"></i> Put On Hold
                    @endif
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center pb-2">
                <p class="mb-1">
                    @if(optional($tenantData)->put_on_hold == 'Yes')
                        Remove hold from <strong>{{ optional($tenantData)->full_name }}</strong>?
                        <br><small class="text-muted">Tenant will regain access.</small>
                    @else
                        Put <strong>{{ optional($tenantData)->full_name }}</strong> on hold?
                        <br><small class="text-muted">Tenant access will be restricted.</small>
                    @endif
                </p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn {{ optional($tenantData)->put_on_hold == 'Yes' ? 'btn-success' : 'btn-warning' }}" id="submitHoldBtn">
                    @if(optional($tenantData)->put_on_hold == 'Yes')
                        <i class="ri-lock-unlock-line"></i> Yes, Remove Hold
                    @else
                        <i class="ri-lock-2-line"></i> Yes, Put On Hold
                    @endif
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Change Payment Dates Modal -->
<div class="modal fade" id="paymentDatesModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-calendar-2-line me-1"></i> Change Payment Dates</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="paymentDatesForm">
                    @csrf
                    <input type="hidden" name="id" value="{{ optional($tenantData)->id }}">

                    <div class="mb-3">
                        <label class="form-label">Tenant</label>
                        <input type="text" class="form-control" disabled
                               value="{{ optional($tenantData)->full_name }} ({{ optional($tenantData)->business_name }})">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Payment Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="last_payment_date" id="inputLastPaymentDate"
                               value="{{ optional($tenantData)->last_payment_date }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Next Payment Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="next_payment_date" id="inputNextPaymentDate"
                               value="{{ optional($tenantData)->next_payment_date }}" required>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-dark" id="submitPaymentDatesBtn">
                            <i class="ri-save-line"></i> Save Dates
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Change Subscription Plan Modal -->
<div class="modal fade" id="subscriptionPlanModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ri-exchange-dollar-line me-1"></i> Change Subscription Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="subscriptionPlanForm">
                    @csrf
                    <input type="hidden" name="id" value="{{ optional($tenantData)->id }}">

                    <div class="mb-3">
                        <label class="form-label">Tenant</label>
                        <input type="text" class="form-control" disabled
                               value="{{ optional($tenantData)->full_name }} ({{ optional($tenantData)->business_name }})">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Plan</label>
                        <input type="text" class="form-control" disabled
                               value="{{ optional($plan)->plan_name }} {{ optional($plan)->plan_period }} — {{ optional($plan)->plan_amount }} {{ optional($plan)->plan_currency }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Plan <span class="text-danger">*</span></label>
                        <select class="form-select" name="subscription_plan" id="selectNewPlan" required>
                            <option value="">-- Select New Plan --</option>
                            @foreach($allPlans as $p)
                                <option value="{{ $p->id }}" {{ optional($tenantData)->subscription_plan == $p->id ? 'selected' : '' }}>
                                    {{ $p->plan_name }} {{ $p->plan_period }}
                                    — {{ $p->plan_amount }} {{ $p->plan_currency }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-secondary" id="submitSubscriptionPlanBtn">
                            <i class="ri-save-line"></i> Save Plan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Send Invoice Modal -->
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

                    <div id="customSection" class="custom-invoice-section mb-3" style="display: none;">
                        <div class="row">
                            <div class="col-12 mb-3">
                                <label class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="description" id="description" rows="4"
                                          placeholder="Enter invoice description" required></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">USD</span>
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

<!-- Delete Tenant Modal -->
<div class="modal fade" id="deleteTenantModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-danger">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="ri-error-warning-line me-1"></i> Delete Tenant — Irreversible Action
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger border-danger mb-3">
                    <strong><i class="ri-alert-line me-1"></i> Warning:</strong>
                    This action is <strong>permanent and cannot be undone</strong>. Deleting this tenant will remove
                    all associated data, database, and access. Proceed only if you are absolutely certain.
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted">Tenant being deleted</label>
                    <input type="text" class="form-control bg-light" disabled
                           value="{{ optional($tenantData)->full_name }} — {{ optional($tenantData)->business_name }}">
                </div>
                <div class="mb-1">
                    <label class="form-label">
                        Type <strong class="text-danger">{{ optional($tenantData)->full_name }}</strong> to confirm deletion
                    </label>
                    <input type="text" class="form-control border-danger" id="deleteConfirmInput"
                           placeholder="Type tenant name here..." autocomplete="off">
                    <div class="form-text text-danger mt-1" id="deleteMatchHint" style="display:none;">
                        <i class="ri-close-circle-line"></i> Name does not match. Please type it exactly.
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" disabled>
                    <i class="ri-delete-bin-2-line"></i> Yes, Delete Tenant
                </button>
            </div>
        </div>
    </div>
</div>

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

    // ==================== ACTIONS MODAL ====================
    $('#actionsBtn').click(e => {
        e.preventDefault();
        $('#actionsModal').modal('show');
    });

    // ==================== APPROVE ====================
    $('#approveBtn').click(e => {
        e.preventDefault();
        $('#actionsModal').modal('hide');
        setTimeout(() => $('#approveModal').modal('show'), 300);
    });

    $('#submitApproveBtn').click(function(e) {
        e.preventDefault();
        var self = $(this);
        self.prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: '{{ route("master.tenant.approve") }}',
            data: $('#approveTenantForm').serialize(),
            timeout: 60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete: function() { $('#progressBar').hide(); self.prop('disabled', false); },
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

    // ==================== PUT ON HOLD / REMOVE HOLD ====================
    $('#holdBtn').click(function(e) {
        e.preventDefault();
        $('#actionsModal').modal('hide');
        setTimeout(() => $('#holdModal').modal('show'), 300);
    });

    $('#submitHoldBtn').click(function(e) {
        e.preventDefault();
        var self = $(this);
        self.prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: '{{ route("master.tenant.hold") }}',
            data: $('#holdTenantForm').serialize(),
            timeout: 60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete: function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    $('#holdModal').modal('hide');
                    // Update the display field on the page without reload
                    $('#putOnHoldDisplay').val(data.put_on_hold === 'Yes' ? 'Yes' : 'No');
                    setTimeout(() => location.reload(), 1500);
                } else if (data.status === 409) {
                    toastr.info(data.error, 'No Changes');
                    $('#holdModal').modal('hide');
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

    // ==================== CHANGE PAYMENT DATES ====================
    $('#paymentDatesBtn').click(function(e) {
        e.preventDefault();
        $('#actionsModal').modal('hide');
        setTimeout(() => $('#paymentDatesModal').modal('show'), 300);
    });

    $('#submitPaymentDatesBtn').click(function(e) {
        e.preventDefault();
        var self = $(this);
        self.prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: '{{ route("master.tenant.payment.dates") }}',
            data: $('#paymentDatesForm').serialize(),
            timeout: 60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete: function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    $('#paymentDatesModal').modal('hide');
                    // Update display fields on the page
                    $('#lastPaymentDisplay').val($('#inputLastPaymentDate').val());
                    $('#nextPaymentDisplay').val($('#inputNextPaymentDate').val());
                } else if (data.status === 409) {
                    toastr.info(data.error, 'No Changes');
                    $('#paymentDatesModal').modal('hide');
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

    // ==================== CHANGE SUBSCRIPTION PLAN ====================
    $('#subscriptionPlanBtn').click(function(e) {
        e.preventDefault();
        $('#actionsModal').modal('hide');
        setTimeout(() => $('#subscriptionPlanModal').modal('show'), 300);
    });

    $('#submitSubscriptionPlanBtn').click(function(e) {
        e.preventDefault();
        var self = $(this);
        self.prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: '{{ route("master.tenant.subscription.plan") }}',
            data: $('#subscriptionPlanForm').serialize(),
            timeout: 60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete: function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    $('#subscriptionPlanModal').modal('hide');
                    // Update the display field with the selected plan text
                    var selectedText = $('#selectNewPlan option:selected').text().trim();
                    $('#subscriptionPlanDisplay').val(selectedText);
                } else if (data.status === 409) {
                    toastr.info(data.error, 'No Changes');
                    $('#subscriptionPlanModal').modal('hide');
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

    // ==================== SEND INVOICE ====================
    $('.send-invoice-trigger').on('click', function(e) {
        e.preventDefault();
        $('#actionsModal').modal('hide');

        const type = $(this).data('type');
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

    $('#sendInvoiceBtn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        $btn.prop('disabled', true);

        var isCustom = $('#is_custom').val() == "1";
        var url = isCustom
            ? '{{ route("master.tenant.send.custom.invoice") }}'
            : '{{ route("master.tenant.send.invoice") }}';

        $.ajax({
            type: 'POST',
            url: url,
            data: $('#sendInvoiceForm').serialize(),
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

    // ==================== UPDATE TENANT DETAILS ====================
    $('#updateDataBtn').click(function(e) {
        e.preventDefault();
        var self = $(this);
        self.prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: '{{ route("master.tenant.details.update") }}',
            data: $('#tenantForm').serialize(),
            timeout: 60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete: function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                } else if (data.status === 409) {
                    toastr.info(data.error, 'No Changes');
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

    // ==================== DELETE TENANT ====================
    const expectedName = "{{ optional($tenantData)->full_name }}";

    // Both delete buttons (main form + actions modal) open the same modal
    $('#deleteDataBtn, #deleteBtnFromActions').on('click', function(e) {
        e.preventDefault();
        $('#actionsModal').modal('hide');
        $('#deleteConfirmInput').val('');
        $('#confirmDeleteBtn').prop('disabled', true);
        $('#deleteMatchHint').hide();
        setTimeout(() => $('#deleteTenantModal').modal('show'), 300);
    });

    $('#deleteConfirmInput').on('input', function() {
        const typed = $(this).val();
        const matches = typed === expectedName;
        $('#confirmDeleteBtn').prop('disabled', !matches);
        $('#deleteMatchHint').toggle(typed.length > 0 && !matches);
    });

    $('#confirmDeleteBtn').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: '{{ route("master.tenant.delete") }}',
            data: $('#deleteTenantForm').serialize(),
            timeout: 60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete: function() { $('#progressBar').hide(); $btn.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success('Tenant deleted successfully.', 'Deleted', { timeOut: 4000 });
                    $('#deleteTenantModal').modal('hide');
                    setTimeout(() => window.location.href = '{{ route("master.tenants") }}', 1500);
                } else if (data.status === 409) {
                    toastr.error(data.error || 'Could not delete tenant.', 'Error');
                }
            },
            error: function(xhr) {
                var errorMessage = 'An error occurred while deleting the tenant.';
                if (xhr.status === 422 && xhr.responseJSON) {
                    errorMessage = Object.values(xhr.responseJSON.errors || {}).flat().join('<br>');
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMessage = xhr.responseJSON.errors[0];
                }
                toastr.error(errorMessage, 'Error', { timeOut: 8000, escapeHtml: false });
            }
        });
    });

});
</script>
@endsection