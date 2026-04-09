@extends('master.dashboard')
@section('content')
<style>
    .dt-buttons .btn {
        background: transparent !important;
        background-image: none !important;
        box-shadow: none !important;
        border: 1px solid #5bc0de;
        color: #5bc0de;
        font-size: 13px;
        padding: 6px 12px;
        border-radius: 6px;
        margin: 0 4px;
    }
    .dt-buttons .btn:hover {
        background: #5bc0de !important;
        color: #fff !important;
    }

    .card-header {
        padding: 0.5rem 1.5rem !important;
        background: linear-gradient(to right, #4B5EBD, #576CC0);
        color: #fff;
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
    }
    .card-body {
        padding: 0 1.5rem 1.5rem 1.5rem !important;
    }
    .card-header .btn-light {
        height: 28px;
        padding: 0 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }
    .card {
        border: none;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        border-radius: 10px;
        overflow: hidden;
    }
    .card-header h4 {
        color: #fff;
        font-weight: 600;
        margin-bottom: 0;
        display: flex;
        align-items: center;
    }
    .card-header h4 i {
        margin-right: 0.25rem;
    }

    .tab-header-container {
        background: #f8f9fa;
        border-top: 1px solid #dee2e6;
    }
    .nav-pills .nav-link {
        border-radius: 0 !important;
        padding: .75rem 1rem;
        font-weight: 500;
        color: #495057;
        border-bottom: 3px solid transparent;
        transition: all .2s;
    }
    .nav-pills .nav-link:hover {
        background: #e9ecef;
        color: #4B5EBD;
    }
    .nav-pills .nav-link.active {
        background: transparent !important;
        color: #4B5EBD !important;
        border-bottom-color: #4B5EBD;
        font-weight: 600;
    }
    .nav-pills .nav-link i {
        font-size: 1.1rem;
        margin-right: .35rem;
    }

    .top-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 1rem 0 0.5rem 0;
    }
    .selected-total {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #f0f9ff;
        padding: 8px 16px;
        border: 1px solid #0ea5e9;
        border-radius: 12px;
        height: 44px;
        font-size: .875rem;
        min-width: 260px;
        box-shadow: 0 2px 6px rgba(14,165,233,.15);
    }
    .selected-total .amount {
        font-weight: 700;
        color: #1e40af;
        font-size: 1rem;
        min-width: 110px;
        text-align: right;
    }

    /* ACTION DROPDOWN */
    .action-cell {
        position: relative;
        text-align: center;
        vertical-align: middle;
    }
    .action-icon {
        cursor: pointer;
        padding: 8px;
        border-radius: 8px;
        transition: background 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .action-icon:hover {
        background: #f0f0f0;
    }

    .action-dropdown {
        position: fixed !important;
        min-width: 240px;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 10px;
        box-shadow: 0 12px 35px rgba(0,0,0,.22);
        z-index: 1050;
        padding: 10px 0;
        font-size: 13.5px;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.25s ease, visibility 0.25s ease;
        overflow: visible !important;
    }
    .action-dropdown.show {
        opacity: 1;
        visibility: visible;
    }

    .action-dropdown::before {
        content: '';
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        border: 9px solid transparent;
        border-bottom-color: #ddd;
        top: -18px;
    }
    .action-dropdown::after {
        content: '';
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        border: 8px solid transparent;
        border-bottom-color: #fff;
        top: -16px;
    }
    .action-dropdown.flip::before {
        border-top-color: #ddd;
        border-bottom-color: transparent;
        top: auto;
        bottom: -18px;
    }
    .action-dropdown.flip::after {
        border-top-color: #fff;
        border-bottom-color: transparent;
        top: auto;
        bottom: -16px;
    }

    .action-dropdown a {
        display: block;
        width: 100%;
        padding: 12px 20px;
        text-align: left;
        background: none;
        color: #333;
        text-decoration: none;
        transition: all .2s;
    }
    .action-dropdown a:hover {
        background-color: #f8f9fa;
        color: #4B5EBD;
    }
    .action-dropdown i {
        width: 22px;
        margin-right: 12px;
        font-size: 15px;
    }

    table.dataTable,
    .dataTables_wrapper,
    .table-responsive {
        overflow: visible !important;
    }

    .select2-container { width: 400px !important; }
    .select2-container--default .select2-selection--single { height: 38px !important; border: 1px solid #ced4da !important; border-radius: 6px !important; background: #fff !important; }
    .select2-container .select2-selection--single .select2-selection__rendered { line-height: 36px !important; padding-left: 12px; padding-right: 35px; color: #495057; font-size: .875rem; }
    .select2-container--default.select2-container--open .select2-selection--single { border-color: #4B5EBD !important; box-shadow: 0 0 0 3px rgba(75,94,189,.15) !important; }
    .select2-dropdown { border: 1px solid #4B5EBD !important; border-radius: 6px !important; }

    .download-section { margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #eee; }
    .download-section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
    .download-section h6 { color: #4B5EBD; font-weight: 600; margin-bottom: 0.75rem; }

    /* Reduce row padding */
    table.dataTable tbody td {
        padding-top: 6px !important;
        padding-bottom: 6px !important;
    }

    /* Force full width for all tables */
    .tab-pane .dataTables_wrapper,
    .tab-pane table.dataTable {
        width: 100% !important;
    }

    /* Text Align Center for all columns except first */
    table.dataTable thead th:not(:first-child),
    table.dataTable tbody td:not(:first-child) {
        text-align: center !important;
    }

    /* Status badges */
    .badge-status-pending   { background:#fef3c7; color:#92400e; border:1px solid #fcd34d; border-radius:6px; padding:3px 10px; font-size:12px; font-weight:600; }
    .badge-status-paid      { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; border-radius:6px; padding:3px 10px; font-size:12px; font-weight:600; }
    .badge-status-overdue   { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; border-radius:6px; padding:3px 10px; font-size:12px; font-weight:600; }
    .badge-status-cancelled { background:#f3f4f6; color:#6b7280; border:1px solid #d1d5db; border-radius:6px; padding:3px 10px; font-size:12px; font-weight:600; }

    /* Plan pill */
    .plan-pill { background:#ede9fe; color:#5b21b6; border-radius:20px; padding:2px 10px; font-size:11.5px; font-weight:600; white-space:nowrap; }

    /* Payment method pill */
    .method-pill { background:#e0f2fe; color:#0369a1; border-radius:20px; padding:2px 10px; font-size:11.5px; font-weight:600; white-space:nowrap; }

    /* Description truncate */
    .desc-cell { max-width:160px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:inline-block; vertical-align:middle; }
</style>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            <div class="row mb-3"></div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="header-title mb-0">
                        <i class="ri-file-list-3-line"></i> Invoice Management
                    </h4>
                    <div class="d-flex align-items-center gap-1">
                        <a href="#" class="btn btn-light text-primary fs-16" id="infoBtn" title="Info">
                            <i class="ri-information-line"></i>
                        </a>
                        <a href="#" class="btn btn-light text-primary fs-16" id="downloadModalBtn" title="Download Options">
                            <i class="ri-download-line"></i>
                        </a>
                    </div>
                </div>

                <div class="tab-header-container">
                    <ul class="nav nav-pills nav-justified mb-0">
                        <li class="nav-item"><a href="#running" data-bs-toggle="tab" class="nav-link active"><i class="ri-play-circle-line"></i> Running</a></li>
                        <li class="nav-item"><a href="#paid" data-bs-toggle="tab" class="nav-link"><i class="ri-check-double-line"></i> Paid</a></li>
                        <li class="nav-item"><a href="#overdue" data-bs-toggle="tab" class="nav-link"><i class="ri-alarm-warning-line"></i> Overdue</a></li>
                        <li class="nav-item"><a href="#cancelled" data-bs-toggle="tab" class="nav-link"><i class="ri-close-circle-line"></i> Cancelled</a></li>
                    </ul>
                </div>

                @php
                    use Illuminate\Support\Facades\DB;
                    use Carbon\Carbon;
                    $today = Carbon::today();

                    $tenantsWithInvoices = DB::table('tenant_invoices')
                        ->join('tenants', 'tenant_invoices.tenant_id', '=', 'tenants.id')
                        ->select('tenants.id', 'tenants.full_name', 'tenants.business_name', 'tenants.email', 'tenants.subscription_plan')
                        ->distinct()
                        ->get()
                        ->keyBy('id');

                    $allInvoices = DB::table('tenant_invoices')->get();

                    $running   = collect();
                    $paid      = collect();
                    $overdue   = collect();
                    $cancelled = collect();

                    foreach ($allInvoices as $inv) {
                        $tenant = $tenantsWithInvoices->get($inv->tenant_id);
                        if (!$tenant) continue;

                        $plan = $tenant->subscription_plan ? DB::table('subscription_plans')->find($tenant->subscription_plan) : null;
                        $inv->plan_days = $plan ? $plan->plan_days : null;

                        $display = $tenant->full_name . ' (' . ($tenant->business_name ?? 'Personal') . ')';
                        $inv->tenant_display      = $display;
                        $inv->tenant_id_filter    = $tenant->id;
                        $inv->tenant_email        = $tenant->email ?? '';
                        $inv->tenant_full_name    = $tenant->full_name;
                        $inv->tenant_business_name = $tenant->business_name ?? 'Personal';

                        // Decode JSON fields
                        $inv->plan_data           = is_string($inv->plan) ? json_decode($inv->plan, true) : (array)$inv->plan;
                        $inv->payment_method_data = is_string($inv->payment_method) ? json_decode($inv->payment_method, true) : (array)($inv->payment_method ?? []);

                        $due = $inv->due_date ? Carbon::parse($inv->due_date) : null;

                        if ($inv->status === 'Paid') {
                            $paid->push($inv);
                        } elseif ($inv->status === 'Cancelled') {
                            $cancelled->push($inv);
                        } elseif ($inv->status === 'Overdue') {
                            $overdue->push($inv);
                        } elseif ($inv->status === 'Pending') {
                            if ($due && $due->lt($today)) {
                                $overdue->push($inv);
                            } else {
                                $running->push($inv);
                            }
                        }
                    }

                    // Helper closures (closures avoid "Cannot redeclare" on Blade cache refresh)
                    $statusBadge = function(string $status): string {
                        $map = [
                            'Pending'   => 'pending',
                            'Paid'      => 'paid',
                            'Overdue'   => 'overdue',
                            'Cancelled' => 'cancelled',
                        ];
                        $cls = $map[$status] ?? 'pending';
                        return "<span class='badge-status-{$cls}'>{$status}</span>";
                    };

                    $planPill = function(array $plan): string {
                        $name   = htmlspecialchars($plan['plan_name']        ?? '—');
                        $period = htmlspecialchars($plan['plan_period_name'] ?? '');
                        return "<span class='plan-pill' title='{$name}'>{$name}" . ($period ? " · {$period}" : '') . "</span>";
                    };

                    $methodPill = function(array $pm): string {
                        $type = htmlspecialchars($pm['method_type'] ?? '');
                        if (!$type) return '<span class="text-muted small">—</span>';
                        $acct  = htmlspecialchars($pm['account_number'] ?? '');
                        $label = $type . ($acct ? ' ···' . substr($acct, -4) : '');
                        return "<span class='method-pill'>{$label}</span>";
                    };
                @endphp

                <div class="card-body">
                    <div class="tab-content">

                        {{-- ============================================================
                             RUNNING TAB  (status = Pending, due_date >= today)
                             ============================================================ --}}
                        <div class="tab-pane show active" id="running">
                            <div class="top-controls">
                                <div>
                                    <form id="tenantSelectionFormRunning" method="GET" action="#">
                                        <input type="hidden" name="tab" value="running">
                                        <select class="client-filter" id="clientFilterRunning" name="tenant_id">
                                            <option value="all">All Tenants (Select All)</option>
                                            <option value="">All Tenants</option>
                                            @foreach($tenantsWithInvoices as $t)
                                                <option value="{{ $t->id }}" {{ request('tenant_id') == $t->id && request('tab') === 'running' ? 'selected' : '' }}>
                                                    {{ htmlspecialchars($t->full_name) }} ({{ htmlspecialchars($t->business_name ?? 'Personal') }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </div>
                                <div class="selected-total" id="selectedTotalRunning">
                                    <i class="ri-wallet-line text-primary fs-18"></i>
                                    Selected Total: <span class="amount">MWK 0.00</span>
                                </div>
                            </div>
                            <table id="runningTable" class="table table-sm table-striped row-border order-column w-100">
                                <thead style="background-color:#e2e2e9">
                                    <tr>
                                        <th style="width:220px; text-align:left !important;"><input type="checkbox" id="selectAllRunning">&nbsp;&nbsp;Tenant</th>
                                        <th>Invoice #</th>
                                        <th>Amount</th>
                                        <th>Currency</th>
                                        <th>Description</th>
                                        <th>Plan</th>
                                        <th>Payment Method</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($running as $inv)
                                        <tr id="invoiceRow{{ $inv->id }}"
                                            data-invoice-id="{{ $inv->id }}"
                                            data-tenant-name="{{ $inv->tenant_full_name }}"
                                            data-business-name="{{ $inv->tenant_business_name }}"
                                            data-tenant-email="{{ $inv->tenant_email }}"
                                            data-amount="{{ $inv->amount }}"
                                            data-plan-days="{{ $inv->plan_days ?? 0 }}">
                                            <td style="text-align:left !important;">
                                                <input type="checkbox" class="invoice-checkbox-running" data-amount="{{ $inv->amount }}">
                                                &nbsp;&nbsp;{{ $inv->tenant_display }}
                                            </td>
                                            <td>{{ $inv->invoice_number }}</td>
                                            <td>{{ number_format($inv->amount, 2) }}</td>
                                            <td>{{ $inv->currency }}</td>
                                            <td><span class="desc-cell" title="{{ $inv->description }}">{{ $inv->description ?? '—' }}</span></td>
                                            <td>{!! $planPill($inv->plan_data) !!}</td>
                                            <td>{!! $methodPill($inv->payment_method_data) !!}</td>
                                            <td>{{ $inv->due_date ? Carbon::parse($inv->due_date)->format('d M Y') : 'N/A' }}</td>
                                            <td>{!! $statusBadge($inv->status) !!}</td>
                                            <td class="action-cell">
                                                <i class="ri-more-2-fill action-icon action-toggle"></i>
                                                <div class="action-dropdown">
                                                    <a href="#" class="view-pdf"><i class="ri-eye-line text-primary"></i> View PDF</a>
                                                    <a href="#" class="download-pdf"><i class="ri-download-2-line text-success"></i> Download PDF</a>
                                                    <a href="#" class="send-email"><i class="ri-mail-send-line text-info"></i> Send Email</a>
                                                    <a href="#" class="mark-paid"><i class="ri-checkbox-circle-line text-success"></i> Mark as Paid</a>
                                                    <a href="#" class="cancel-invoice"><i class="ri-close-circle-line text-danger"></i> Cancel</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- ============================================================
                             PAID TAB  (status = Paid)
                             ============================================================ --}}
                        <div class="tab-pane" id="paid">
                            <div class="top-controls">
                                <div>
                                    <form id="tenantSelectionFormPaid" method="GET" action="#">
                                        <input type="hidden" name="tab" value="paid">
                                        <select class="client-filter" id="clientFilterPaid" name="tenant_id">
                                            <option value="all">All Tenants (Select All)</option>
                                            <option value="">All Tenants</option>
                                            @foreach($tenantsWithInvoices as $t)
                                                <option value="{{ $t->id }}" {{ request('tenant_id') == $t->id && request('tab') === 'paid' ? 'selected' : '' }}>
                                                    {{ htmlspecialchars($t->full_name) }} ({{ htmlspecialchars($t->business_name ?? 'Personal') }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </div>
                                <div class="selected-total" id="selectedTotalPaid">
                                    <i class="ri-wallet-line text-primary fs-18"></i>
                                    Selected Total: <span class="amount">MWK 0.00</span>
                                </div>
                            </div>
                            <table id="paidTable" class="table table-sm table-striped row-border order-column w-100">
                                <thead style="background-color:#e2e2e9">
                                    <tr>
                                        <th style="width:220px; text-align:left !important;"><input type="checkbox" id="selectAllPaid">&nbsp;&nbsp;Tenant</th>
                                        <th>Invoice #</th>
                                        <th>Amount</th>
                                        <th>Currency</th>
                                        <th>Description</th>
                                        <th>Plan</th>
                                        <th>Payment Method</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($paid as $inv)
                                        <tr id="invoiceRow{{ $inv->id }}"
                                            data-invoice-id="{{ $inv->id }}"
                                            data-tenant-name="{{ $inv->tenant_full_name }}"
                                            data-business-name="{{ $inv->tenant_business_name }}"
                                            data-tenant-email="{{ $inv->tenant_email }}"
                                            data-amount="{{ $inv->amount }}"
                                            data-plan-days="{{ $inv->plan_days ?? 0 }}">
                                            <td style="text-align:left !important;">
                                                <input type="checkbox" class="invoice-checkbox-paid" data-amount="{{ $inv->amount }}">
                                                &nbsp;&nbsp;{{ $inv->tenant_display }}
                                            </td>
                                            <td>{{ $inv->invoice_number }}</td>
                                            <td>{{ number_format($inv->amount, 2) }}</td>
                                            <td>{{ $inv->currency }}</td>
                                            <td><span class="desc-cell" title="{{ $inv->description }}">{{ $inv->description ?? '—' }}</span></td>
                                            <td>{!! $planPill($inv->plan_data) !!}</td>
                                            <td>{!! $methodPill($inv->payment_method_data) !!}</td>
                                            <td>{{ $inv->due_date ? Carbon::parse($inv->due_date)->format('d M Y') : 'N/A' }}</td>
                                            <td>{!! $statusBadge($inv->status) !!}</td>
                                            <td class="action-cell">
                                                <i class="ri-more-2-fill action-icon action-toggle"></i>
                                                <div class="action-dropdown">
                                                    <a href="#" class="view-pdf"><i class="ri-eye-line text-primary"></i> View PDF</a>
                                                    <a href="#" class="download-pdf"><i class="ri-download-2-line text-success"></i> Download PDF</a>
                                                    <a href="#" class="send-email"><i class="ri-mail-send-line text-info"></i> Send Email</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- ============================================================
                             OVERDUE TAB  (status = Overdue  OR  status = Pending with past due_date)
                             ============================================================ --}}
                        <div class="tab-pane" id="overdue">
                            <div class="top-controls">
                                <div>
                                    <form id="tenantSelectionFormOverdue" method="GET" action="#">
                                        <input type="hidden" name="tab" value="overdue">
                                        <select class="client-filter" id="clientFilterOverdue" name="tenant_id">
                                            <option value="all">All Tenants (Select All)</option>
                                            <option value="">All Tenants</option>
                                            @foreach($tenantsWithInvoices as $t)
                                                <option value="{{ $t->id }}" {{ request('tenant_id') == $t->id && request('tab') === 'overdue' ? 'selected' : '' }}>
                                                    {{ htmlspecialchars($t->full_name) }} ({{ htmlspecialchars($t->business_name ?? 'Personal') }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </div>
                                <div class="selected-total" id="selectedTotalOverdue">
                                    <i class="ri-wallet-line text-primary fs-18"></i>
                                    Selected Total: <span class="amount">MWK 0.00</span>
                                </div>
                            </div>
                            <table id="overdueTable" class="table table-sm table-striped row-border order-column w-100">
                                <thead style="background-color:#e2e2e9">
                                    <tr>
                                        <th style="width:220px; text-align:left !important;"><input type="checkbox" id="selectAllOverdue">&nbsp;&nbsp;Tenant</th>
                                        <th>Invoice #</th>
                                        <th>Amount</th>
                                        <th>Currency</th>
                                        <th>Description</th>
                                        <th>Plan</th>
                                        <th>Payment Method</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($overdue as $inv)
                                        <tr id="invoiceRow{{ $inv->id }}"
                                            data-invoice-id="{{ $inv->id }}"
                                            data-tenant-name="{{ $inv->tenant_full_name }}"
                                            data-business-name="{{ $inv->tenant_business_name }}"
                                            data-tenant-email="{{ $inv->tenant_email }}"
                                            data-amount="{{ $inv->amount }}"
                                            data-plan-days="{{ $inv->plan_days ?? 0 }}">
                                            <td style="text-align:left !important;">
                                                <input type="checkbox" class="invoice-checkbox-overdue" data-amount="{{ $inv->amount }}">
                                                &nbsp;&nbsp;{{ $inv->tenant_display }}
                                            </td>
                                            <td>{{ $inv->invoice_number }}</td>
                                            <td>{{ number_format($inv->amount, 2) }}</td>
                                            <td>{{ $inv->currency }}</td>
                                            <td><span class="desc-cell" title="{{ $inv->description }}">{{ $inv->description ?? '—' }}</span></td>
                                            <td>{!! $planPill($inv->plan_data) !!}</td>
                                            <td>{!! $methodPill($inv->payment_method_data) !!}</td>
                                            <td>{{ $inv->due_date ? Carbon::parse($inv->due_date)->format('d M Y') : 'N/A' }}</td>
                                            <td>{!! $statusBadge($inv->status) !!}</td>
                                            <td class="action-cell">
                                                <i class="ri-more-2-fill action-icon action-toggle"></i>
                                                <div class="action-dropdown">
                                                    <a href="#" class="view-pdf"><i class="ri-eye-line text-primary"></i> View PDF</a>
                                                    <a href="#" class="download-pdf"><i class="ri-download-2-line text-success"></i> Download PDF</a>
                                                    <a href="#" class="send-email"><i class="ri-mail-send-line text-info"></i> Send Email</a>
                                                    <a href="#" class="mark-paid"><i class="ri-checkbox-circle-line text-success"></i> Mark as Paid</a>
                                                    <a href="#" class="cancel-invoice"><i class="ri-close-circle-line text-danger"></i> Cancel</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- ============================================================
                             CANCELLED TAB  (status = Cancelled)
                             ============================================================ --}}
                        <div class="tab-pane" id="cancelled">
                            <div class="top-controls">
                                <div>
                                    <form id="tenantSelectionFormCancelled" method="GET" action="#">
                                        <input type="hidden" name="tab" value="cancelled">
                                        <select class="client-filter" id="clientFilterCancelled" name="tenant_id">
                                            <option value="all">All Tenants (Select All)</option>
                                            <option value="">All Tenants</option>
                                            @foreach($tenantsWithInvoices as $t)
                                                <option value="{{ $t->id }}" {{ request('tenant_id') == $t->id && request('tab') === 'cancelled' ? 'selected' : '' }}>
                                                    {{ htmlspecialchars($t->full_name) }} ({{ htmlspecialchars($t->business_name ?? 'Personal') }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </div>
                                <div class="selected-total" id="selectedTotalCancelled">
                                    <i class="ri-wallet-line text-primary fs-18"></i>
                                    Selected Total: <span class="amount">MWK 0.00</span>
                                </div>
                            </div>
                            <table id="cancelledTable" class="table table-sm table-striped row-border order-column w-100">
                                <thead style="background-color:#e2e2e9">
                                    <tr>
                                        <th style="width:220px; text-align:left !important;"><input type="checkbox" id="selectAllCancelled">&nbsp;&nbsp;Tenant</th>
                                        <th>Invoice #</th>
                                        <th>Amount</th>
                                        <th>Currency</th>
                                        <th>Description</th>
                                        <th>Plan</th>
                                        <th>Payment Method</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cancelled as $inv)
                                        <tr id="invoiceRow{{ $inv->id }}"
                                            data-invoice-id="{{ $inv->id }}"
                                            data-tenant-name="{{ $inv->tenant_full_name }}"
                                            data-business-name="{{ $inv->tenant_business_name }}"
                                            data-tenant-email="{{ $inv->tenant_email }}"
                                            data-amount="{{ $inv->amount }}"
                                            data-plan-days="{{ $inv->plan_days ?? 0 }}">
                                            <td style="text-align:left !important;">
                                                <input type="checkbox" class="invoice-checkbox-cancelled" data-amount="{{ $inv->amount }}">
                                                &nbsp;&nbsp;{{ $inv->tenant_display }}
                                            </td>
                                            <td>{{ $inv->invoice_number }}</td>
                                            <td>{{ number_format($inv->amount, 2) }}</td>
                                            <td>{{ $inv->currency }}</td>
                                            <td><span class="desc-cell" title="{{ $inv->description }}">{{ $inv->description ?? '—' }}</span></td>
                                            <td>{!! $planPill($inv->plan_data) !!}</td>
                                            <td>{!! $methodPill($inv->payment_method_data) !!}</td>
                                            <td>{{ $inv->due_date ? Carbon::parse($inv->due_date)->format('d M Y') : 'N/A' }}</td>
                                            <td>{!! $statusBadge($inv->status) !!}</td>
                                            <td class="action-cell">
                                                <i class="ri-more-2-fill action-icon action-toggle"></i>
                                                <div class="action-dropdown">
                                                    <a href="#" class="view-pdf"><i class="ri-eye-line text-primary"></i> View PDF</a>
                                                    <a href="#" class="download-pdf"><i class="ri-download-2-line text-success"></i> Download PDF</a>
                                                    <a href="#" class="send-email"><i class="ri-mail-send-line text-info"></i> Send Email</a>
                                                </div>
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
    </div>
</div>

<!-- Download Modal -->
<div class="modal fade" id="downloadModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Download Invoices</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Choose a format to download invoices from each tab:</p>
                <div class="download-section">
                    <h6>Running Invoices</h6>
                    <div class="dt-buttons" id="runningButtons"></div>
                </div>
                <div class="download-section">
                    <h6>Paid Invoices</h6>
                    <div class="dt-buttons" id="paidButtons"></div>
                </div>
                <div class="download-section">
                    <h6>Overdue Invoices</h6>
                    <div class="dt-buttons" id="overdueButtons"></div>
                </div>
                <div class="download-section">
                    <h6>Cancelled Invoices</h6>
                    <div class="dt-buttons" id="cancelledButtons"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PDF Viewer Modal -->
<div class="modal fade" id="pdfModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Invoice PDF</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="pdfFrame" style="width:100%; height:70vh;" frameborder="0"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Mark as Paid Modal -->
<div class="modal fade" id="markPaidModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mark Invoice as Paid</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="markPaidForm">
                    @csrf
                    <div class="mb-3"><label class="form-label">Client Name</label><input type="text" class="form-control" id="paidClientName" readonly></div>
                    <div class="mb-3"><label class="form-label">Business Name</label><input type="text" class="form-control" id="paidBusinessName" readonly></div>
                    <div class="mb-3"><label class="form-label">Invoice Number</label><input type="text" class="form-control" id="paidInvoiceNumber" readonly></div>
                    <div class="mb-3">
                        <label class="form-label">Days to Next Payment</label>
                        <input type="number" min="1" class="form-control" id="paidDays" name="days" required>
                        <div id="paidDaysMessage" class="small mt-1 text-muted"></div>
                    </div>
                    <button type="submit" class="btn btn-primary float-end">Mark as Paid</button>
                    <button type="button" class="btn btn-secondary float-end me-2" data-bs-dismiss="modal">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Cancel Invoice Modal -->
<div class="modal fade" id="cancelInvoiceModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="cancelInvoiceForm">
                    @csrf
                    <p>Are you sure you want to cancel invoice <span id="cancelInvoiceNumber"></span>?</p>
                    <button type="submit" class="btn btn-danger float-end">Yes, Cancel</button>
                    <button type="button" class="btn btn-secondary float-end me-2" data-bs-dismiss="modal">No</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Send Email Modal -->
<div class="modal fade" id="sendEmailModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Invoice via Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="sendEmailForm">
                    @csrf
                    <div class="mb-3"><label class="form-label">Client Name</label><input type="text" class="form-control" id="emailClientName" readonly></div>
                    <div class="mb-3"><label class="form-label">Business Name</label><input type="text" class="form-control" id="emailBusinessName" readonly></div>
                    <div class="mb-3"><label class="form-label">Email Address</label><input type="email" class="form-control" id="emailRecipient" readonly></div>
                    <div class="mb-3"><label class="form-label">Invoice Number</label><input type="text" class="form-control" id="emailInvoiceNumber" readonly></div>
                    <div class="alert alert-info small">The invoice PDF will be generated and attached automatically.</div>
                    <button type="submit" class="btn btn-primary float-end">Send Email Now</button>
                    <button type="button" class="btn btn-secondary float-end me-2" data-bs-dismiss="modal">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Info Modal -->
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Invoice Management</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Manage tenant invoices: view, download, mark as paid, send reminders, or cancel.
                <hr>
                <ul class="mb-0 small">
                    <li><strong>Running</strong> — Pending invoices whose due date has not yet passed.</li>
                    <li><strong>Paid</strong> — Invoices marked as paid.</li>
                    <li><strong>Overdue</strong> — Pending invoices whose due date has passed, plus any explicitly set to Overdue.</li>
                    <li><strong>Cancelled</strong> — Invoices that have been cancelled.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function () {
    toastr.options = { closeButton: true, progressBar: true, showMethod: 'slideDown', timeOut: 5000 };

    const commonConfig = {
        scrollX: true,
        fixedHeader: true,
        fixedColumns: { left: 1 },
        lengthMenu: [[100, 250, 500, -1], [100, 250, 500, "All"]],
        pageLength: 100,
        dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
        initComplete: function () {
            this.api().columns.adjust().draw(false);
        }
    };

    const runningTable   = $('#runningTable').DataTable(commonConfig);
    const paidTable      = $('#paidTable').DataTable(commonConfig);
    const overdueTable   = $('#overdueTable').DataTable(commonConfig);
    const cancelledTable = $('#cancelledTable').DataTable(commonConfig);

    // ── Export buttons ────────────────────────────────────────────────────────
    new $.fn.dataTable.Buttons(runningTable, { buttons: [
        { extend: 'excelHtml5', title: 'Running Invoices',   text: 'Excel' },
        { extend: 'csvHtml5',   title: 'Running Invoices',   text: 'CSV'   },
        { extend: 'pdfHtml5',   title: 'Running Invoices',   text: 'PDF'   }
    ]}).container().appendTo('#runningButtons');

    new $.fn.dataTable.Buttons(paidTable, { buttons: [
        { extend: 'excelHtml5', title: 'Paid Invoices',      text: 'Excel' },
        { extend: 'csvHtml5',   title: 'Paid Invoices',      text: 'CSV'   },
        { extend: 'pdfHtml5',   title: 'Paid Invoices',      text: 'PDF'   }
    ]}).container().appendTo('#paidButtons');

    new $.fn.dataTable.Buttons(overdueTable, { buttons: [
        { extend: 'excelHtml5', title: 'Overdue Invoices',   text: 'Excel' },
        { extend: 'csvHtml5',   title: 'Overdue Invoices',   text: 'CSV'   },
        { extend: 'pdfHtml5',   title: 'Overdue Invoices',   text: 'PDF'   }
    ]}).container().appendTo('#overdueButtons');

    new $.fn.dataTable.Buttons(cancelledTable, { buttons: [
        { extend: 'excelHtml5', title: 'Cancelled Invoices', text: 'Excel' },
        { extend: 'csvHtml5',   title: 'Cancelled Invoices', text: 'CSV'   },
        { extend: 'pdfHtml5',   title: 'Cancelled Invoices', text: 'PDF'   }
    ]}).container().appendTo('#cancelledButtons');

    // ── Download modal trigger ────────────────────────────────────────────────
    $('#downloadModalBtn').on('click', function(e) {
        e.preventDefault();
        $('#downloadModal').modal('show');
    });

    // ── Tab switch: adjust columns + recalc total ─────────────────────────────
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr("href").substring(1);
        const tableMap = { running: runningTable, paid: paidTable, overdue: overdueTable, cancelled: cancelledTable };
        if (tableMap[target]) tableMap[target].columns.adjust().draw(false);
        updateSelectedTotal(target);
    });

    // ── Action dropdown positioning ───────────────────────────────────────────
    $(document).on('click', '.action-toggle', function(e) {
        e.stopPropagation();
        const $icon     = $(this);
        const $dropdown = $icon.siblings('.action-dropdown');
        $('.action-dropdown').not($dropdown).removeClass('show flip');
        if ($dropdown.hasClass('show')) { $dropdown.removeClass('show flip'); return; }
        $dropdown.addClass('show');
        const iconRect      = this.getBoundingClientRect();
        const dropdownWidth = $dropdown.outerWidth(true);
        const dropdownHeight = $dropdown.outerHeight(true) || 280;
        const iconCenterX   = iconRect.left + iconRect.width / 2;
        const spaceBelow    = window.innerHeight - iconRect.bottom;
        const spaceAbove    = iconRect.top;
        let top, bottom;
        if (spaceBelow >= dropdownHeight + 20) {
            top = iconRect.bottom + 10 + window.scrollY;
            $dropdown.removeClass('flip');
        } else if (spaceAbove >= dropdownHeight + 20) {
            bottom = (window.innerHeight - iconRect.top) + 10 + window.scrollY;
            $dropdown.addClass('flip');
        } else {
            top = iconRect.bottom + 10 + window.scrollY;
            $dropdown.removeClass('flip');
        }
        $dropdown.css({ left: iconCenterX - dropdownWidth / 2, top: top || 'auto', bottom: bottom || 'auto' });
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.action-cell').length) $('.action-dropdown').removeClass('show flip');
    });

    $(window).on('scroll resize', function() { $('.action-dropdown').removeClass('show flip'); });

    // ── Selected-total calculator ─────────────────────────────────────────────
    function updateSelectedTotal(tab) {
        let total = 0;
        $(`#${tab}Table tbody input[type="checkbox"]:checked`).each(function() {
            total += parseFloat($(this).data('amount')) || 0;
        });
        $(`#selectedTotal${tab.charAt(0).toUpperCase() + tab.slice(1)} .amount`).text(
            'MWK ' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        );
    }

    $(document).on('change', 'input[type="checkbox"][class^="invoice-checkbox"]', function() {
        const classes = $(this).attr('class').split(/\s+/);
        const tab = classes.find(c => c.startsWith('invoice-checkbox-')).replace('invoice-checkbox-', '');
        updateSelectedTotal(tab);
    });

    $('[id^="selectAll"]').on('change', function() {
        const tab     = $(this).attr('id').replace('selectAll', '').toLowerCase();
        const checked = this.checked;
        $(`#${tab}Table tbody input[type="checkbox"]`).prop('checked', checked);
        updateSelectedTotal(tab);
    });

    // ── Tenant filter (Select2) — submit form on change ───────────────────────
    $('.client-filter').select2({ placeholder: "All Tenants", width: '100%' });

    // Map each select to its form
    const filterFormMap = {
        clientFilterRunning:   'tenantSelectionFormRunning',
        clientFilterPaid:      'tenantSelectionFormPaid',
        clientFilterOverdue:   'tenantSelectionFormOverdue',
        clientFilterCancelled: 'tenantSelectionFormCancelled',
    };

    $.each(filterFormMap, function(selectId, formId) {
        $('#' + selectId).on('change', function() {
            const val = $(this).val();
            // "all" means select-all (no server filter, just check all boxes)
            if (val === 'all') {
                const tab = selectId.replace('clientFilter', '').toLowerCase();
                $(`#${tab}Table tbody input[type="checkbox"]`).prop('checked', true);
                updateSelectedTotal(tab);
                return;
            }
            // Any other value: submit the form to reload with server-side filter
            $('#' + formId).submit();
        });
    });

    // ── View PDF ──────────────────────────────────────────────────────────────
    $(document).on('click', '.view-pdf', function() {
        const id  = $(this).closest('tr').data('invoice-id');
        const url = `{{ route('master.tenant.invoices.pdf', '') }}/${id}`;
        $.ajax({
            url, method: 'GET', xhrFields: { responseType: 'blob' },
            success: function(blob, status, xhr) {
                const contentType = xhr.getResponseHeader('content-type');
                if (contentType && contentType.includes('application/pdf')) {
                    $('#pdfFrame').attr('src', URL.createObjectURL(blob));
                    $('#pdfModal').modal('show');
                } else {
                    blob.text().then(text => {
                        try { toastr.error(JSON.parse(text).message || 'Could not load preview.'); }
                        catch(e) { toastr.error('Unexpected error while loading preview.'); }
                    });
                }
            },
            error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Failed to load preview.')
        });
    });

    // ── Download PDF ──────────────────────────────────────────────────────────
    $(document).on('click', '.download-pdf', function(e) {
        e.preventDefault();
        const id  = $(this).closest('tr').data('invoice-id');
        const url = `{{ route('master.tenant.invoices.download', '') }}/${id}`;
        $.ajax({
            url, method: 'GET', xhrFields: { responseType: 'blob' },
            success: function(blob, status, xhr) {
                const contentType = xhr.getResponseHeader('content-type');
                if (contentType && contentType.includes('application/pdf')) {
                    const link = document.createElement('a');
                    link.href  = URL.createObjectURL(blob);
                    link.download = `invoice_${id}.pdf`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(link.href);
                } else {
                    blob.text().then(text => {
                        try { toastr.error(JSON.parse(text).message || 'Could not download invoice.'); }
                        catch(e) { toastr.error('Unexpected error during download.'); }
                    });
                }
            },
            error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Failed to download invoice.')
        });
    });

    // ── Mark as Paid ──────────────────────────────────────────────────────────
    $(document).on('click', '.mark-paid', function() {
        const row        = $(this).closest('tr');
        const invoiceId  = row.data('invoice-id');
        const currentTab = $('div.tab-pane.active').attr('id');
        const currentTable = currentTab === 'running' ? runningTable : (currentTab === 'overdue' ? overdueTable : null);

        $('#paidClientName').val(row.data('tenant-name'));
        $('#paidBusinessName').val(row.data('business-name'));
        $('#paidInvoiceNumber').val(row.find('td:eq(1)').text().trim());

        const planDays = row.data('plan-days');
        if (planDays > 0) {
            $('#paidDays').val(planDays);
            $('#paidDaysMessage').text('Based on subscription plan: ' + planDays + ' days. You can adjust if needed.')
                .removeClass('text-warning').addClass('text-info');
        } else {
            $('#paidDays').val('');
            $('#paidDaysMessage').text('No subscription plan found. Please enter custom number of days.')
                .removeClass('text-info').addClass('text-warning');
        }
        $('#markPaidForm').data('url', `{{ route('master.tenant.invoices.pay', '') }}/${invoiceId}`);
        $('#markPaidForm').data('row', row);
        $('#markPaidForm').data('table', currentTable);
        $('#markPaidModal').modal('show');
    });

    $('#markPaidForm').on('submit', function(e) {
        e.preventDefault();
        const btn   = $(this).find('button[type="submit"]');
        btn.prop('disabled', true);
        const row   = $(this).data('row');
        const table = $(this).data('table');
        $.ajax({
            type: 'POST', url: $(this).data('url'),
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: (data) => {
                if (data.success) {
                    toastr.success(data.success, 'Success');
                    if (table) table.row(row).remove().draw(false);
                    $('#markPaidModal').modal('hide');
                }
            },
            error: (xhr) => toastr.error(xhr.responseJSON?.error || 'Failed to mark as paid.')
        }).always(() => btn.prop('disabled', false));
    });

    // ── Cancel Invoice ────────────────────────────────────────────────────────
    $(document).on('click', '.cancel-invoice', function() {
        const row        = $(this).closest('tr');
        const invoiceId  = row.data('invoice-id');
        const currentTab = $('div.tab-pane.active').attr('id');
        const currentTable = currentTab === 'running' ? runningTable : (currentTab === 'overdue' ? overdueTable : null);

        $('#cancelInvoiceNumber').text(row.find('td:eq(1)').text().trim());
        $('#cancelInvoiceForm').data('url', `{{ route('master.tenant.invoices.cancel', '') }}/${invoiceId}`);
        $('#cancelInvoiceForm').data('row', row);
        $('#cancelInvoiceForm').data('table', currentTable);
        $('#cancelInvoiceModal').modal('show');
    });

    $('#cancelInvoiceForm').on('submit', function(e) {
        e.preventDefault();
        const btn   = $(this).find('button[type="submit"]');
        btn.prop('disabled', true);
        const row   = $(this).data('row');
        const table = $(this).data('table');
        $.ajax({
            type: 'POST', url: $(this).data('url'),
            data: { _token: '{{ csrf_token() }}' },
            success: (data) => {
                if (data.success) {
                    toastr.success(data.success, 'Cancelled');
                    if (table) table.row(row).remove().draw(false);
                    $('#cancelInvoiceModal').modal('hide');
                }
            },
            error: (xhr) => toastr.error(xhr.responseJSON?.error || 'Failed to cancel.')
        }).always(() => btn.prop('disabled', false));
    });

    // ── Send Email ────────────────────────────────────────────────────────────
    $(document).on('click', '.send-email', function() {
        const row = $(this).closest('tr');
        $('#emailClientName').val(row.data('tenant-name'));
        $('#emailBusinessName').val(row.data('business-name') || 'Personal');
        $('#emailRecipient').val(row.data('tenant-email') || '');
        $('#emailInvoiceNumber').val(row.find('td:eq(1)').text().trim());
        $('#sendEmailForm').data('url', `{{ route('master.tenant.invoices.send', '') }}/${row.data('invoice-id')}`);
        $('#sendEmailModal').modal('show');
    });

    $('#sendEmailForm').on('submit', function(e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true);
        $.ajax({
            type: 'POST', url: $(this).data('url'),
            data: { _token: '{{ csrf_token() }}' },
            success: (data) => { toastr.success(data.success || 'Email sent!', 'Success'); $('#sendEmailModal').modal('hide'); },
            error: (xhr) => toastr.error(xhr.responseJSON?.error || 'Failed to send email.')
        }).always(() => btn.prop('disabled', false));
    });

    // ── Info button ───────────────────────────────────────────────────────────
    $('#infoBtn').on('click', (e) => { e.preventDefault(); $('#infoModal').modal('show'); });
});
</script>
@endsection