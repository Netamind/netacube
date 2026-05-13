@extends('tenants.admin.dashboard')
@section('content')
@php
    $pref = DB::connection('tenant')->table('user_filters')
                ->where('user_id', Auth::id())
                ->first();

    $savedPeriodId   = $pref->payslip_period_id   ?? null;
    $savedCatId      = $pref->payslip_category_id ?? null;
    $savedEmployeeId = $pref->payslip_employee_id ?? null;

    /* Periods that have entries (approved or paid only) */
    $periods = DB::connection('tenant')
        ->table('payroll_periods')
        ->whereIn('status', ['approved', 'paid'])
        ->orderBy('period_start', 'desc')
        ->get();

    /* All categories */
    $categories = DB::connection('tenant')
        ->table('categories')
        ->orderBy('category')
        ->get();

    /* All active employees */
    $employees = DB::connection('tenant')
        ->table('users')
        ->where('active', 'Yes')
        ->orderBy('name')
        ->get();

    /* ── Build payslip list ───────────────────────────────────── */
    $query = DB::connection('tenant')
        ->table('payroll_entries')
        ->join('payroll_periods', 'payroll_periods.id', '=', 'payroll_entries.payroll_period_id')
        ->join('users',           'users.id',           '=', 'payroll_entries.employee_id')
        ->leftJoin('branches',    'branches.id',        '=', 'users.branch')
        ->leftJoin('categories',  'categories.id',      '=', 'branches.category')
        ->whereIn('payroll_periods.status', ['approved', 'paid'])
        ->select(
            'payroll_entries.id',
            'payroll_entries.payroll_period_id',
            'payroll_entries.employee_id',
            'payroll_entries.gross_pay',
            'payroll_entries.total_deductions',
            'payroll_entries.net_pay',
            'payroll_entries.on_pension',
            'payroll_entries.paye',
            'payroll_entries.pension_employee',
            'payroll_entries.loan_deduction',
            'payroll_entries.advance_deduction',
            'payroll_entries.other_deductions',
            'payroll_entries.notes as entry_notes',
            'payroll_periods.name        as period_name',
            'payroll_periods.period_start',
            'payroll_periods.period_end',
            'payroll_periods.pay_date',
            'payroll_periods.status      as period_status',
            'users.name                  as employee_name',
            'users.phone                 as employee_number',
            'users.email                 as employee_email',
            'users.position              as position',
            'users.department            as department',
            'branches.name               as branch_name',
            'categories.id               as category_id',
            'categories.category         as category_name'
        );

    if ($savedPeriodId)   $query->where('payroll_entries.payroll_period_id', $savedPeriodId);
    if ($savedCatId)      $query->where('categories.id', $savedCatId);
    if ($savedEmployeeId) $query->where('payroll_entries.employee_id', $savedEmployeeId);

    $payslips = $query->orderBy('payroll_periods.period_start', 'desc')
                      ->orderBy('users.name', 'asc')
                      ->get();

    $totalPayslips   = $payslips->count();
    $totalNetPay     = $payslips->sum('net_pay');
    $totalGrossPay   = $payslips->sum('gross_pay');
    $totalDeductions = $payslips->sum('total_deductions');
@endphp

<style>
.dt-buttons .btn {
    background: transparent !important; background-image: none !important;
    box-shadow: none !important; border-color: #5bc0de; color: #5bc0de;
}
.dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }

.card-header {
    padding: 0.5rem 1.5rem !important;
    background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff;
    border-radius: 10px 10px 0 0 !important;
}
.card-body  { padding: 0 1.5rem 1.5rem 1.5rem !important; }
.card       { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; }
.card-header h4 { color:#fff; font-weight:600; margin-bottom:0; display:flex; align-items:center; }
.card-header .btn-light {
    height:28px; padding:0 10px;
    display:flex; align-items:center; justify-content:center; line-height:1;
}
.card-header .btn-light:hover { background-color:#f8f9fa; transition:background-color 0.2s; }

/* Filter bar */
.card-filter {
    background: #eef0f7; border-bottom: 1px solid #d6daf0;
    padding: 9px 1.5rem; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
}
.card-filter label { font-size:12px; font-weight:600; color:#4B5EBD; margin-bottom:0; white-space:nowrap; }
.card-filter select {
    font-size:12px; height:30px; padding:0 8px; border-radius:6px;
    border:1px solid #c8d0ed; background:#fff; min-width:140px; max-width:220px;
}
.filter-divider { width:1px; height:22px; background:#c8d0ed; margin:0 4px; }

/* Badges */
.badge-draft      { background:#6c757d; color:#fff; padding:2px 9px; border-radius:20px; font-size:11px; }
.badge-processing { background:#0dcaf0; color:#fff; padding:2px 9px; border-radius:20px; font-size:11px; }
.badge-approved   { background:#198754; color:#fff; padding:2px 9px; border-radius:20px; font-size:11px; }
.badge-paid       { background:#4B5EBD; color:#fff; padding:2px 9px; border-radius:20px; font-size:11px; }

/* Stats strip */
.stats-strip { display:flex; flex-wrap:wrap; gap:0; border-bottom:1px solid #d6daf0; }
.stats-strip-item {
    flex:1; min-width:140px; padding:12px 20px;
    border-right:1px solid #d6daf0; background:#f8f9fc;
}
.stats-strip-item:last-child { border-right:none; }
.stats-strip-item .s-label {
    font-size:10px; font-weight:700; text-transform:uppercase;
    letter-spacing:.06em; color:#94a3b8; display:block; margin-bottom:3px;
}
.stats-strip-item .s-value { font-size:18px; font-weight:700; color:#1e293b; display:block; }

/* Modal header */
.mh-blue  { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-title { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }

/* Section title */
.modal-section-title {
    font-size:11px; font-weight:600; text-transform:uppercase;
    letter-spacing:.07em; color:#6c757d;
    border-bottom:1px solid #e9ecef; padding-bottom:6px; margin:16px 0 10px;
}

/* Field groups */
.pf-group { background:#f8f9fa; border-radius:6px; padding:9px 12px; }
.pf-label { font-size:11px; color:#6c757d; text-transform:uppercase; letter-spacing:.05em; }
.pf-value { font-size:14px; font-weight:600; color:#212529; }

/* Bulk bar */
#bulkBar {
    background:#fff3cd; border:1px solid #ffc107; border-radius:6px;
    padding:8px 14px; margin:10px 1.5rem 0;
    display:none; align-items:center; gap:10px; flex-wrap:wrap;
}
#bulkBar .bulk-count { font-size:13px; font-weight:600; color:#856404; }

/* Empty state */
.empty-state { padding:52px 20px; text-align:center; color:#94a3b8; }
.empty-state i { font-size:52px; display:block; margin-bottom:12px; color:#c8d0ed; }
.empty-state h5 { color:#64748b; font-weight:600; margin-bottom:6px; }
.empty-state p  { font-size:13px; }

/* Stats modal cards */
.stats-card {
    border-radius:10px; padding:14px 18px; color:#fff; text-align:center;
}
.stats-card .sc-label { font-size:11px; opacity:.85; text-transform:uppercase; letter-spacing:.05em; }
.stats-card .sc-value { font-size:26px; font-weight:700; line-height:1.3; }
.bg-sc1 { background:linear-gradient(135deg,#4B5EBD,#6c7fe0); }
.bg-sc2 { background:linear-gradient(135deg,#0dcaf0,#0891b2); }
.bg-sc3 { background:linear-gradient(135deg,#dc3545,#f87171); }
.bg-sc4 { background:linear-gradient(135deg,#198754,#27c87e); }
</style>

<div class="progress" id="progressBar" role="progressbar"
     style="height:8px;transform:rotate(180deg);display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

    {{-- Card header --}}
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="header-title mb-0">
            <i class="ri-file-paper-2-line me-1"></i> Payslips
        </h4>
        <div class="d-flex align-items-center" style="gap:4px;">
            <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="statsBtn"        title="Statistics"><i class="ri-bar-chart-2-line"></i></a>
            <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn"         title="Info"><i class="ri-information-line"></i></a>
            <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Export Table"><i class="ri-table-line"></i></a>
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="card-filter">

        {{-- Period --}}
        <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
              id="filterPeriodForm" style="display:contents;">
            @csrf
            <input type="hidden" name="user_id"              value="{{ Auth::id() }}">
            <input type="hidden" name="payslip_category_id"  value="{{ $savedCatId }}">
            <input type="hidden" name="payslip_employee_id"  value="{{ $savedEmployeeId }}">
            <label>Period:</label>
            <select name="payslip_period_id"
                    onchange="document.getElementById('filterPeriodForm').submit()">
                <option value="">All Periods</option>
                @foreach($periods as $per)
                    <option value="{{ $per->id }}" {{ $savedPeriodId == $per->id ? 'selected' : '' }}>
                        {{ $per->name }}
                        ({{ \Carbon\Carbon::parse($per->period_start)->format('d M') }}
                         &ndash; {{ \Carbon\Carbon::parse($per->period_end)->format('d M Y') }})
                    </option>
                @endforeach
            </select>
        </form>

        <div class="filter-divider"></div>

        {{-- Category --}}
        <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
              id="filterCatForm" style="display:contents;">
            @csrf
            <input type="hidden" name="user_id"             value="{{ Auth::id() }}">
            <input type="hidden" name="payslip_period_id"   value="{{ $savedPeriodId }}">
            <input type="hidden" name="payslip_employee_id" value="{{ $savedEmployeeId }}">
            <label>Category:</label>
            <select name="payslip_category_id"
                    onchange="document.getElementById('filterCatForm').submit()">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ $savedCatId == $cat->id ? 'selected' : '' }}>
                        {{ $cat->category }}
                    </option>
                @endforeach
            </select>
        </form>

        <div class="filter-divider"></div>

        {{-- Employee --}}
        <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
              id="filterEmployeeForm" style="display:contents;">
            @csrf
            <input type="hidden" name="user_id"             value="{{ Auth::id() }}">
            <input type="hidden" name="payslip_period_id"   value="{{ $savedPeriodId }}">
            <input type="hidden" name="payslip_category_id" value="{{ $savedCatId }}">
            <label>Employee:</label>
            <select name="payslip_employee_id"
                    onchange="document.getElementById('filterEmployeeForm').submit()">
                <option value="">All Employees</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ $savedEmployeeId == $emp->id ? 'selected' : '' }}>
                        {{ $emp->name }}
                    </option>
                @endforeach
            </select>
        </form>

        {{-- Clear filters --}}
        <div class="ms-auto">
            <form method="POST" action="{{ route('tenant.admin.update.filters') }}" style="display:inline;">
                @csrf
                <input type="hidden" name="user_id"             value="{{ Auth::id() }}">
                <input type="hidden" name="payslip_period_id"   value="">
                <input type="hidden" name="payslip_category_id" value="">
                <input type="hidden" name="payslip_employee_id" value="">
                <button type="submit" class="btn btn-sm btn-outline-secondary" style="font-size:12px;">
                    <i class="ri-refresh-line me-1"></i> Clear Filters
                </button>
            </form>
        </div>

    </div>{{-- /.card-filter --}}

    {{-- Stats strip --}}
    <div class="stats-strip">
        <div class="stats-strip-item">
            <span class="s-label">Payslips Shown</span>
            <span class="s-value" style="color:#4B5EBD;">{{ number_format($totalPayslips) }}</span>
        </div>
        <div class="stats-strip-item">
            <span class="s-label">Total Gross Pay</span>
            <span class="s-value">{{ number_format($totalGrossPay, 2) }}</span>
        </div>
        <div class="stats-strip-item">
            <span class="s-label">Total Deductions</span>
            <span class="s-value" style="color:#dc3545;">{{ number_format($totalDeductions, 2) }}</span>
        </div>
        <div class="stats-strip-item">
            <span class="s-label">Total Net Pay</span>
            <span class="s-value" style="color:#198754;">{{ number_format($totalNetPay, 2) }}</span>
        </div>
    </div>

    {{-- Bulk action bar --}}
    <div id="bulkBar">
        <span class="bulk-count"><span id="selectedCount">0</span> selected</span>
        <a href="#" class="btn btn-sm btn-outline-success" id="bulkDownloadBtn">
            <i class="ri-file-download-line me-1"></i> Download Selected
        </a>
        <a href="#" class="btn btn-sm btn-outline-info" id="bulkEmailBtn">
            <i class="ri-mail-send-line me-1"></i> Email Selected
        </a>
        <a href="#" class="btn btn-sm btn-outline-secondary ms-auto" id="clearSelectionBtn">
            <i class="ri-close-line me-1"></i> Clear
        </a>
    </div>

    {{-- Table / empty state --}}
    <div class="card-body" style="padding-top:0 !important;">

        @if($payslips->isEmpty())
            <div class="empty-state">
                <i class="ri-file-paper-2-line"></i>
                <h5>No Payslips Found</h5>
                <p>
                    No payslips exist for the selected filters.<br>
                    Payslips are available for <strong>Approved</strong> or <strong>Paid</strong> periods only.<br>
                    Go to
                    <a href="{{ route('tenant.admin.hr.payroll.periods', ['tenantName' => request()->route('tenantName')]) }}">Payroll Periods</a>
                    to generate and approve a period first.
                </p>
            </div>
        @else
        <div style="overflow-x:auto;">
        <table id="maintable" class="table table-sm table-striped row-border order-column w-100"
               style="margin-top:12px;">
            <thead style="background-color:#e2e2e9">
                <tr>
                    <th style="width:30px; text-align:center;">
                        <input type="checkbox" id="selectAll" title="Select all">
                    </th>
                    <th>Employee</th>
                    <th style="text-align:center">Position</th>
                    <th style="text-align:center">Branch</th>
                    <th style="text-align:center">Period</th>
                    <th style="text-align:center">Pay Date</th>
                    <th style="text-align:center">Gross Pay</th>
                    <th style="text-align:center">Deductions</th>
                    <th style="text-align:center">Net Pay</th>
                    <th style="text-align:center">Status</th>
                    <th style="text-align:center">Action</th>
                </tr>
            </thead>
            <tbody id="tbody">
                @foreach($payslips as $slip)
                    <tr id="row{{ $slip->id }}">
                        <td style="text-align:center; vertical-align:middle;">
                            <input type="checkbox" class="row-check"
                                   data-id="{{ $slip->id }}"
                                   data-name="{{ $slip->employee_name }}"
                                   data-email="{{ $slip->employee_email }}"
                                   data-period="{{ $slip->period_name }}">
                        </td>
                        <td>
                            <strong>{{ $slip->employee_name }}</strong>
                            @if($slip->department)
                                <br><small class="text-muted">{{ $slip->department }}</small>
                            @endif
                        </td>
                        <td style="text-align:center">{{ $slip->position ?? '—' }}</td>
                        <td style="text-align:center">{{ $slip->branch_name ?? '—' }}</td>
                        <td style="text-align:center">
                            <strong>{{ $slip->period_name }}</strong><br>
                            <small class="text-muted">
                                {{ \Carbon\Carbon::parse($slip->period_start)->format('d M') }}
                                &ndash;
                                {{ \Carbon\Carbon::parse($slip->period_end)->format('d M Y') }}
                            </small>
                        </td>
                        <td style="text-align:center">
                            {{ \Carbon\Carbon::parse($slip->pay_date)->format('d M Y') }}
                        </td>
                        <td style="text-align:center">{{ number_format($slip->gross_pay, 2) }}</td>
                        <td style="text-align:center; color:#dc3545;">{{ number_format($slip->total_deductions, 2) }}</td>
                        <td style="text-align:center">
                            <strong class="text-primary">{{ number_format($slip->net_pay, 2) }}</strong>
                        </td>
                        <td style="text-align:center">
                            <span class="badge-{{ $slip->period_status }}">
                                {{ ucfirst($slip->period_status) }}
                            </span>
                        </td>
                        <td style="text-align:center; white-space:nowrap;">

                            {{-- View --}}
                            <a href="#" class="viewSlipBtn btn btn-light text-primary btn-sm"
                               data-id="{{ $slip->id }}"
                               data-employee-name="{{ $slip->employee_name }}"
                               data-employee-number="{{ $slip->employee_number ?? '' }}"
                               data-employee-email="{{ $slip->employee_email ?? '' }}"
                               data-position="{{ $slip->position ?? '' }}"
                               data-department="{{ $slip->department ?? '' }}"
                               data-branch="{{ $slip->branch_name ?? '' }}"
                               data-category="{{ $slip->category_name ?? '' }}"
                               data-period="{{ $slip->period_name }}"
                               data-period-start="{{ \Carbon\Carbon::parse($slip->period_start)->format('d M Y') }}"
                               data-period-end="{{ \Carbon\Carbon::parse($slip->period_end)->format('d M Y') }}"
                               data-pay-date="{{ \Carbon\Carbon::parse($slip->pay_date)->format('d M Y') }}"
                               data-period-status="{{ $slip->period_status }}"
                               data-gross="{{ $slip->gross_pay }}"
                               data-deductions="{{ $slip->total_deductions }}"
                               data-net="{{ $slip->net_pay }}"
                               data-on-pension="{{ $slip->on_pension }}"
                               data-paye="{{ $slip->paye }}"
                               data-pension-ee="{{ $slip->pension_employee }}"
                               data-loan="{{ $slip->loan_deduction }}"
                               data-advance="{{ $slip->advance_deduction }}"
                               data-other-ded="{{ $slip->other_deductions }}"
                               data-notes="{{ $slip->entry_notes ?? '' }}"
                               title="View Details">
                                <i class="ri-eye-line"></i>
                            </a>

                            {{-- Download --}}
                            <a href="{{ route('tenant.admin.hr.payroll.wagebill.payslip', ['tenantName' => request()->route('tenantName')]) }}?entry_id={{ $slip->id }}"
                               class="btn btn-light text-success btn-sm"
                               title="Download PDF Payslip">
                                <i class="ri-file-download-line"></i>
                            </a>

                            {{-- Email --}}
                            <a href="#" class="emailSlipBtn btn btn-light text-info btn-sm"
                               data-id="{{ $slip->id }}"
                               data-name="{{ $slip->employee_name }}"
                               data-email="{{ $slip->employee_email ?? '' }}"
                               data-period="{{ $slip->period_name }}"
                               title="Email Payslip">
                                <i class="ri-mail-send-line"></i>
                            </a>

                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @endif

    </div>{{-- /.card-body --}}
</div>{{-- /.card --}}

</div></div></div>


{{-- ══════════════════════════════════════════════════════════════════
     STATISTICS MODAL
══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="statsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title">
                    <i class="ri-bar-chart-2-line"></i>&nbsp; Payslip Statistics
                </h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="statsModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Loading…</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     INFO MODAL
══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header mh-blue">
            <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Payslips</h5>
            <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" style="font-size:13px;">
            <p>The <strong>Payslips</strong> page is a centralised view of all employee payslips across every <strong>Approved</strong> and <strong>Paid</strong> payroll period.</p>
            <p class="mb-1"><strong>Filters</strong></p>
            <ul class="mb-3" style="padding-left:18px;">
                <li class="mb-1"><strong>Period</strong> — Narrow down to a specific payroll run.</li>
                <li class="mb-1"><strong>Category</strong> — Show only employees whose branch belongs to a particular category.</li>
                <li class="mb-1"><strong>Employee</strong> — View a single employee's full payslip history.</li>
            </ul>
            <p class="mb-1"><strong>Row actions</strong></p>
            <ul class="mb-3" style="padding-left:18px;">
                <li class="mb-1"><i class="ri-eye-line text-primary"></i> <strong>View</strong> — Full breakdown summary without downloading.</li>
                <li class="mb-1"><i class="ri-file-download-line text-success"></i> <strong>Download</strong> — PDF payslip for the employee.</li>
                <li><i class="ri-mail-send-line text-info"></i> <strong>Email</strong> — Sends the payslip PDF to the employee's registered email.</li>
            </ul>
            <p class="mb-1"><strong>Bulk actions</strong></p>
            <p>Tick multiple rows then use the yellow bar to download or email all selected payslips at once.</p>
            <div class="alert alert-warning mt-3 mb-0">
                <i class="ri-error-warning-line me-1"></i>
                Only <strong>Approved</strong> and <strong>Paid</strong> periods appear here.
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        </div>
    </div></div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     DATATABLE EXPORT MODAL
══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Export Table</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <p class="mb-2">Export the currently visible payslip list.</p>
            <div class="buttons"></div>
        </div>
    </div></div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     VIEW PAYSLIP DETAIL MODAL
══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="viewSlipModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title">
                    <i class="ri-file-text-line"></i>&nbsp; Payslip &mdash; <span id="vSlipTitle"></span>
                </h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">

                <div class="modal-section-title">Employee</div>
                <div class="row g-2 mb-2">
                    <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Name</div><div class="pf-value" id="vName">—</div></div></div>
                    <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Employee #</div><div class="pf-value" id="vNumber">—</div></div></div>
                    <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Email</div><div class="pf-value" id="vEmail" style="font-size:12px;">—</div></div></div>
                    <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Position</div><div class="pf-value" id="vPosition">—</div></div></div>
                    <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Department</div><div class="pf-value" id="vDepartment">—</div></div></div>
                    <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Branch / Category</div><div class="pf-value" id="vBranchCat">—</div></div></div>
                </div>

                <div class="modal-section-title">Period</div>
                <div class="row g-2 mb-2">
                    <div class="col-md-3 col-6"><div class="pf-group"><div class="pf-label">Period</div><div class="pf-value" id="vPeriod">—</div></div></div>
                    <div class="col-md-3 col-6"><div class="pf-group"><div class="pf-label">Dates</div><div class="pf-value" id="vDates" style="font-size:12px;">—</div></div></div>
                    <div class="col-md-3 col-6"><div class="pf-group"><div class="pf-label">Pay Date</div><div class="pf-value" id="vPayDate">—</div></div></div>
                    <div class="col-md-3 col-6"><div class="pf-group"><div class="pf-label">Status</div><div class="pf-value" id="vStatus">—</div></div></div>
                </div>

                <div class="modal-section-title">Deductions Breakdown</div>
                <div class="row g-2 mb-2">
                    <div class="col-md-3 col-6"><div class="pf-group"><div class="pf-label">PAYE</div><div class="pf-value" id="vPaye">—</div></div></div>
                    <div class="col-md-3 col-6"><div class="pf-group"><div class="pf-label">Pension (Ee)</div><div class="pf-value" id="vPensionEe">—</div></div></div>
                    <div class="col-md-3 col-6"><div class="pf-group"><div class="pf-label">Loan</div><div class="pf-value" id="vLoan">—</div></div></div>
                    <div class="col-md-3 col-6"><div class="pf-group"><div class="pf-label">Advance</div><div class="pf-value" id="vAdvance">—</div></div></div>
                </div>

                <div class="modal-section-title">Summary</div>
                <div class="row g-2 mb-2">
                    <div class="col-md-4 col-6">
                        <div class="pf-group" style="background:#e8f5e9;">
                            <div class="pf-label">Gross Pay</div>
                            <div class="pf-value text-success" id="vGross">—</div>
                        </div>
                    </div>
                    <div class="col-md-4 col-6">
                        <div class="pf-group" style="background:#fdecea;">
                            <div class="pf-label">Total Deductions</div>
                            <div class="pf-value text-danger" id="vDeductions">—</div>
                        </div>
                    </div>
                    <div class="col-md-4 col-6">
                        <div class="pf-group" style="background:#e8eaf6;">
                            <div class="pf-label">Net Pay</div>
                            <div class="pf-value text-primary" id="vNet">—</div>
                        </div>
                    </div>
                </div>

                <div id="vNotesRow" style="display:none;">
                    <div class="modal-section-title">Notes</div>
                    <div class="pf-group">
                        <div class="pf-value" id="vNotes" style="font-size:13px;font-weight:400;"></div>
                    </div>
                </div>

            </div>
            <div class="modal-footer d-flex justify-content-between">
                <div>
                    <a href="#" class="btn btn-success btn-sm" id="vDownloadBtn">
                        <i class="ri-file-download-line me-1"></i> Download PDF
                    </a>
                    <a href="#" class="btn btn-info btn-sm ms-1 text-white" id="vEmailBtn">
                        <i class="ri-mail-send-line me-1"></i> Email Payslip
                    </a>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     EMAIL PAYSLIP MODAL
══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="emailSlipModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width:430px;">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title">
                    <i class="ri-mail-send-line"></i>&nbsp; Email Payslip
                </h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p style="font-size:13px;" class="mb-3">
                    Send payslip for <strong id="emailName"></strong>
                    (<span id="emailPeriod" class="text-primary fw-bold"></span>)
                    to their registered email.
                </p>
                <div class="pf-group mb-3">
                    <div class="pf-label">Recipient</div>
                    <div class="pf-value" id="emailAddress" style="font-size:13px;">—</div>
                </div>
                <div class="alert alert-warning py-2" id="noEmailAlert" style="display:none;font-size:12px;">
                    <i class="ri-error-warning-line me-1"></i>
                    This employee has no email address on file. Please update their profile first.
                </div>
                <div class="form-group">
                    <label style="font-size:13px;font-weight:600;">
                        Additional Note <small class="text-muted fw-normal">(optional)</small>
                    </label>
                    <textarea class="form-control" id="emailNote" rows="2"
                              placeholder="Add a short note to include in the email…"
                              style="font-size:13px;"></textarea>
                </div>
                <input type="hidden" id="emailEntryId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <a href="#" class="btn btn-info btn-sm text-white" id="confirmEmailBtn">
                    <i class="ri-mail-send-line me-1"></i> Send Email
                </a>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     BULK DOWNLOAD CONFIRM MODAL
══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="bulkDownloadModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width:400px; margin:1.75rem auto;">
        <div class="modal-content">
            <div class="modal-body text-center pb-4 pt-4">
                <i class="ri-file-download-line text-success" style="font-size:60px"></i>
                <h4 class="mt-2">Download Payslips</h4>
                <p class="text-muted">
                    Download <strong id="bulkDlCount">0</strong> payslip(s) as individual PDF files.<br>
                    Each will open in a new tab.
                </p>
                <div class="d-flex justify-content-center gap-2 mt-3">
                    <a href="#" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</a>
                    <a href="#" class="btn btn-success" id="confirmBulkDownloadBtn">
                        <i class="ri-file-download-line me-1"></i> Download All
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════
     BULK EMAIL CONFIRM MODAL
══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="bulkEmailModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width:420px; margin:1.75rem auto;">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-mail-send-line"></i>&nbsp; Bulk Email Payslips</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p style="font-size:13px;" class="mb-3">
                    Send payslips to <strong id="bulkEmailCount">0</strong> selected employee(s).
                    Each employee will receive their own payslip PDF.
                    Employees with no email on file will be skipped.
                </p>
                <div class="form-group">
                    <label style="font-size:13px;font-weight:600;">
                        Additional Note <small class="text-muted fw-normal">(optional, sent to all)</small>
                    </label>
                    <textarea class="form-control" id="bulkEmailNote" rows="2"
                              placeholder="Add a short note…"
                              style="font-size:13px;"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <a href="#" class="btn btn-info btn-sm text-white" id="confirmBulkEmailBtn">
                    <i class="ri-mail-send-line me-1"></i> Send All
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {

    toastr.options = { closeButton:true, progressBar:true, showMethod:'slideDown', timeOut:5000 };

    var payslipBaseUrl = '{{ route("tenant.admin.hr.payroll.wagebill.payslip", ["tenantName" => request()->route("tenantName")]) }}';
    var statsUrl       = '{{ route("tenant.admin.hr.payroll.payslips.stats",   ["tenantName" => request()->route("tenantName")]) }}';
    var emailUrl       = '{{ route("tenant.admin.hr.payroll.payslips.email",   ["tenantName" => request()->route("tenantName")]) }}';
    var bulkEmailUrl   = '{{ route("tenant.admin.hr.payroll.payslips.bulkemail",["tenantName" => request()->route("tenantName")]) }}';
    var csrfToken      = '{{ csrf_token() }}';

    // ── DataTable ─────────────────────────────────────────────────────────
    @if($payslips->isNotEmpty())
    var table = $('#maintable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
        order: [[4, 'desc'], [1, 'asc']],
        columnDefs: [{ targets: [0, 10], orderable: false }],
        buttons: [
            { extend: 'excelHtml5', title: 'Payslips',
              exportOptions: { columns: ':visible:not(:first-child):not(:last-child)' } },
            { extend: 'csvHtml5',   title: 'Payslips',
              exportOptions: { columns: ':visible:not(:first-child):not(:last-child)' } },
            { extend: 'pdfHtml5',   title: 'Payslips',
              exportOptions: { columns: ':visible:not(:first-child):not(:last-child)' },
              orientation: 'landscape', pageSize: 'A3',
              customize: function(doc) {
                  doc.content[1].table.widths =
                      Array(doc.content[1].table.body[0].length + 1).join('*').split('');
              }
            }
        ]
    });
    table.buttons().container().appendTo($('#buttonsModal .buttons'));
    @endif

    // ── Bootstrap modals ──────────────────────────────────────────────────
    var viewSlipModal     = new bootstrap.Modal('#viewSlipModal');
    var emailSlipModal    = new bootstrap.Modal('#emailSlipModal');
    var bulkDownloadModal = new bootstrap.Modal('#bulkDownloadModal');
    var bulkEmailModal    = new bootstrap.Modal('#bulkEmailModal');

    // ── Toolbar buttons ───────────────────────────────────────────────────
    $('#infoBtn').on('click',         function(e) { e.preventDefault(); $('#infoModal').modal('show'); });
    $('#tableButtonsBtn').on('click', function(e) { e.preventDefault(); $('#buttonsModal').modal('show'); });

    // ── Statistics (loaded on demand) ─────────────────────────────────────
    $('#statsBtn').on('click', function(e) {
        e.preventDefault();
        $('#statsModalBody').html(
            '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div>' +
            '<p class="mt-2 text-muted">Loading…</p></div>'
        );
        $('#statsModal').modal('show');
        $.get(statsUrl, function(data) {
            if (data.status !== 200) { $('#statsModalBody').html('<p class="text-danger text-center">Failed.</p>'); return; }
            var html = '<div class="row g-3">';
            $.each(data.stats, function(i, s) {
                html += '<div class="col-md-3 col-6"><div class="stats-card ' + s.css + '">'
                      + '<div class="sc-label">' + s.label + '</div>'
                      + '<div class="sc-value">' + s.value + '</div>'
                      + '</div></div>';
            });
            html += '</div>';
            if (data.period_breakdown && data.period_breakdown.length) {
                html += '<div class="modal-section-title mt-4" style="font-size:11px;font-weight:600;text-transform:uppercase;'
                      + 'letter-spacing:.07em;color:#6c757d;border-bottom:1px solid #e9ecef;padding-bottom:6px;">By Period</div>';
                html += '<div style="overflow-x:auto;"><table class="table table-sm table-striped" style="font-size:12px;margin-top:8px;">'
                      + '<thead><tr><th>Period</th><th class="text-center">Employees</th>'
                      + '<th class="text-center">Gross Pay</th><th class="text-center">Net Pay</th>'
                      + '<th class="text-center">Status</th></tr></thead><tbody>';
                $.each(data.period_breakdown, function(i, p) {
                    html += '<tr><td><strong>' + p.name + '</strong></td>'
                          + '<td class="text-center">' + p.count + '</td>'
                          + '<td class="text-center">' + parseFloat(p.gross_pay).toLocaleString('en',{minimumFractionDigits:2}) + '</td>'
                          + '<td class="text-center"><strong class="text-primary">' + parseFloat(p.net_pay).toLocaleString('en',{minimumFractionDigits:2}) + '</strong></td>'
                          + '<td class="text-center"><span class="badge-' + p.status + '">' + p.status.charAt(0).toUpperCase() + p.status.slice(1) + '</span></td>'
                          + '</tr>';
                });
                html += '</tbody></table></div>';
            }
            $('#statsModalBody').html(html);
        }).fail(function() { $('#statsModalBody').html('<p class="text-danger text-center">Failed to load statistics.</p>'); });
    });

    // ── Number formatter ──────────────────────────────────────────────────
    function fmt(n) {
        return parseFloat(n || 0).toLocaleString('en', { minimumFractionDigits:2, maximumFractionDigits:2 });
    }

    // ── VIEW payslip detail ───────────────────────────────────────────────
    $('#tbody').on('click', '.viewSlipBtn', function() {
        var d = $(this).data();
        $('#vSlipTitle').text(d.employeeName + ' — ' + d.period);
        $('#vName').text(d.employeeName);
        $('#vNumber').text(d.employeeNumber || '—');
        $('#vEmail').text(d.employeeEmail || '—');
        $('#vPosition').text(d.position || '—');
        $('#vDepartment').text(d.department || '—');
        $('#vBranchCat').text((d.branch || '—') + (d.category ? ' / ' + d.category : ''));
        $('#vPeriod').text(d.period);
        $('#vDates').text(d.periodStart + ' – ' + d.periodEnd);
        $('#vPayDate').text(d.payDate);
        $('#vStatus').html('<span class="badge-' + d.periodStatus + '">' + d.periodStatus.charAt(0).toUpperCase() + d.periodStatus.slice(1) + '</span>');
        $('#vPaye').text(fmt(d.paye));
        $('#vPensionEe').text(fmt(d.pensionEe));
        $('#vLoan').text(fmt(d.loan));
        $('#vAdvance').text(fmt(d.advance));
        $('#vGross').text(fmt(d.gross));
        $('#vDeductions').text(fmt(d.deductions));
        $('#vNet').text(fmt(d.net));

        if (d.notes) { $('#vNotes').text(d.notes); $('#vNotesRow').show(); }
        else          { $('#vNotesRow').hide(); }

        $('#vDownloadBtn').attr('href', payslipBaseUrl + '?entry_id=' + d.id);
        $('#vEmailBtn').off('click').on('click', function(e) {
            e.preventDefault();
            viewSlipModal.hide();
            setTimeout(function() {
                openEmailModal(d.id, d.employeeName, d.employeeEmail, d.period);
            }, 400);
        });
        viewSlipModal.show();
    });

    // ── EMAIL — open modal ────────────────────────────────────────────────
    function openEmailModal(entryId, name, email, period) {
        $('#emailEntryId').val(entryId);
        $('#emailName').text(name);
        $('#emailPeriod').text(period);
        $('#emailNote').val('');
        if (email) {
            $('#emailAddress').text(email);
            $('#noEmailAlert').hide();
            $('#confirmEmailBtn').removeClass('disabled');
        } else {
            $('#emailAddress').text('No email on file');
            $('#noEmailAlert').show();
            $('#confirmEmailBtn').addClass('disabled');
        }
        emailSlipModal.show();
    }

    $('#tbody').on('click', '.emailSlipBtn', function(e) {
        e.preventDefault();
        var d = $(this).data();
        openEmailModal(d.id, d.name, d.email, d.period);
    });

    // ── EMAIL — send ──────────────────────────────────────────────────────
    $('#confirmEmailBtn').on('click', function(e) {
        e.preventDefault();
        if ($(this).hasClass('disabled')) return;
        var $btn = $(this).prop('disabled', true).html('<i class="ri-loader-4-line me-1"></i> Sending…');
        $.ajax({
            url: emailUrl, type: 'POST',
            data: { _token: csrfToken, entry_id: $('#emailEntryId').val(), note: $('#emailNote').val() },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); $btn.prop('disabled', false).html('<i class="ri-mail-send-line me-1"></i> Send Email'); },
            success: function(data) {
                if (data.status === 200) {
                    toastr.success(data.success, 'Sent');
                    emailSlipModal.hide();
                } else {
                    toastr.error(data.error || 'Failed to send.', 'Error');
                }
            },
            error: function() { toastr.error('Server error.', 'Error'); }
        });
    });

    // ── CHECKBOX / BULK SELECTION ─────────────────────────────────────────
    function getChecked() { return $('.row-check:checked'); }

    function updateBulkBar() {
        var count = getChecked().length;
        if (count > 0) {
            $('#selectedCount').text(count);
            $('#bulkDlCount').text(count);
            $('#bulkEmailCount').text(count);
            $('#bulkBar').css('display', 'flex');
        } else {
            $('#bulkBar').hide();
        }
    }

    $('#selectAll').on('change', function() {
        $('.row-check').prop('checked', this.checked);
        updateBulkBar();
    });

    $('#tbody').on('change', '.row-check', function() {
        var total   = $('.row-check').length;
        var checked = getChecked().length;
        $('#selectAll').prop('indeterminate', checked > 0 && checked < total);
        $('#selectAll').prop('checked', checked === total);
        updateBulkBar();
    });

    $('#clearSelectionBtn').on('click', function(e) {
        e.preventDefault();
        $('.row-check, #selectAll').prop('checked', false).prop('indeterminate', false);
        updateBulkBar();
    });

    // ── BULK DOWNLOAD ─────────────────────────────────────────────────────
    $('#bulkDownloadBtn').on('click', function(e) {
        e.preventDefault();
        bulkDownloadModal.show();
    });

    $('#confirmBulkDownloadBtn').on('click', function(e) {
        e.preventDefault();
        bulkDownloadModal.hide();
        getChecked().each(function(i) {
            var entryId = $(this).data('id');
            setTimeout(function() {
                window.open(payslipBaseUrl + '?entry_id=' + entryId, '_blank');
            }, i * 350);
        });
        $('.row-check, #selectAll').prop('checked', false).prop('indeterminate', false);
        updateBulkBar();
    });

    // ── BULK EMAIL ────────────────────────────────────────────────────────
    $('#bulkEmailBtn').on('click', function(e) {
        e.preventDefault();
        bulkEmailModal.show();
    });

    $('#confirmBulkEmailBtn').on('click', function(e) {
        e.preventDefault();
        var ids = [];
        getChecked().each(function() { ids.push($(this).data('id')); });
        if (!ids.length) return;

        var $btn = $(this).prop('disabled', true).html('<i class="ri-loader-4-line me-1"></i> Sending…');
        $.ajax({
            url: bulkEmailUrl, type: 'POST',
            data: { _token: csrfToken, entry_ids: ids, note: $('#bulkEmailNote').val() },
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); $btn.prop('disabled', false).html('<i class="ri-mail-send-line me-1"></i> Send All'); },
            success: function(data) {
                if (data.status === 200) {
                    toastr.success(data.success, 'Done');
                    bulkEmailModal.hide();
                    $('.row-check, #selectAll').prop('checked', false).prop('indeterminate', false);
                    updateBulkBar();
                } else {
                    toastr.error(data.error || 'Some emails failed.', 'Error');
                }
            },
            error: function() { toastr.error('Server error.', 'Error'); }
        });
    });

});
</script>
@endsection