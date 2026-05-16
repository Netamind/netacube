@extends('tenants.admin.dashboard')
@section('content')
@php
    $pref = DB::connection('tenant')->table('user_filters')
                ->where('user_id', Auth::id())
                ->first();

    $savedSector     = ($pref->sector              ?? null) ?: null;
    $savedCatId      = ($pref->payslip_category_id ?? null) ?: null;
    $savedPeriodId   = ($pref->payslip_period_id   ?? null) ?: null;
    $savedEmployeeId = ($pref->payslip_employee_id ?? null) ?: null;

    /*
    |--------------------------------------------------------------------------
    | SECTOR LIST
    |--------------------------------------------------------------------------
    */
    $sectors = DB::connection('tenant')
        ->table('sectors')
        ->orderBy('sector')
        ->pluck('sector');

    if (!$savedSector && $sectors->isNotEmpty()) {
        $savedSector = $sectors->first();
    }

    /*
    |--------------------------------------------------------------------------
    | CATEGORY LIST
    |--------------------------------------------------------------------------
    */
    $categories = collect();
    if ($savedSector) {
        $catIds = DB::connection('tenant')
            ->table('branches')
            ->join('users',           'users.branch',               '=', 'branches.id')
            ->join('payroll_entries', 'payroll_entries.employee_id', '=', 'users.id')
            ->join('payroll_periods', 'payroll_periods.id',          '=', 'payroll_entries.payroll_period_id')
            ->whereIn('payroll_periods.status', ['approved', 'paid'])
            ->where('branches.sector', $savedSector)
            ->whereNotNull('branches.category')
            ->distinct()
            ->pluck('branches.category')
            ->map(fn($c) => (int) $c)
            ->unique()
            ->values();

        $categories = DB::connection('tenant')
            ->table('categories')
            ->whereIn('id', $catIds)
            ->orderBy('category')
            ->get();
    }

    if ($savedCatId && $categories->where('id', (int)$savedCatId)->isEmpty()) {
        $savedCatId = null;
    }

    /*
    |--------------------------------------------------------------------------
    | BRANCH IDs
    |--------------------------------------------------------------------------
    */
    $filteredBranchIds = collect();
    if ($savedSector) {
        $branchQuery = DB::connection('tenant')
            ->table('branches')
            ->where('sector', $savedSector);
        if ($savedCatId) {
            $branchQuery->where('category', (string) $savedCatId);
        }
        $filteredBranchIds = $branchQuery->pluck('id');
    }

    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE IDs
    |--------------------------------------------------------------------------
    */
    $filteredEmployeeIds = collect();
    if ($filteredBranchIds->isNotEmpty()) {
        $filteredEmployeeIds = DB::connection('tenant')
            ->table('users')
            ->whereIn('branch', $filteredBranchIds)
            ->pluck('id');
    }

    /*
    |--------------------------------------------------------------------------
    | PERIOD LIST — approved/paid only, ALWAYS required
    |--------------------------------------------------------------------------
    */
    $periods = collect();
    if ($filteredEmployeeIds->isNotEmpty()) {
        $periodIds = DB::connection('tenant')
            ->table('payroll_entries')
            ->whereIn('employee_id', $filteredEmployeeIds)
            ->distinct()
            ->pluck('payroll_period_id');

        $periods = DB::connection('tenant')
            ->table('payroll_periods')
            ->whereIn('id', $periodIds)
            ->whereIn('status', ['approved', 'paid'])
            ->orderBy('period_start', 'desc')
            ->get();
    }

    if (!$savedPeriodId && $periods->isNotEmpty()) {
        $savedPeriodId = $periods->first()->id;
    }

    if ($savedPeriodId && $periods->where('id', (int)$savedPeriodId)->isEmpty()) {
        $savedPeriodId = null;
    }

    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE SELECT LIST
    |--------------------------------------------------------------------------
    */
    $employees = collect();
    if ($filteredEmployeeIds->isNotEmpty() && $savedPeriodId) {
        $empIdsInPeriod = DB::connection('tenant')
            ->table('payroll_entries')
            ->where('payroll_period_id', (int)$savedPeriodId)
            ->whereIn('employee_id', $filteredEmployeeIds)
            ->pluck('employee_id');

        $employees = DB::connection('tenant')
            ->table('users')
            ->whereIn('id', $empIdsInPeriod)
            ->orderBy('name')
            ->get();
    }

    if ($savedEmployeeId && $employees->where('id', (int)$savedEmployeeId)->isEmpty()) {
        $savedEmployeeId = null;
    }

    /*
    |--------------------------------------------------------------------------
    | SECTOR HAS PAYSLIPS?
    |--------------------------------------------------------------------------
    */
    $currentSectorHasPayslips = $savedSector && DB::connection('tenant')
        ->table('branches')
        ->join('users',           'users.branch',               '=', 'branches.id')
        ->join('payroll_entries', 'payroll_entries.employee_id', '=', 'users.id')
        ->join('payroll_periods', 'payroll_periods.id',          '=', 'payroll_entries.payroll_period_id')
        ->whereIn('payroll_periods.status', ['approved', 'paid'])
        ->where('branches.sector', $savedSector)
        ->exists();

    /*
    |--------------------------------------------------------------------------
    | SELECTED PERIOD OBJECT
    |--------------------------------------------------------------------------
    */
    $selectedPeriod = null;
    if ($savedPeriodId) {
        $selectedPeriod = $periods->where('id', (int)$savedPeriodId)->first();
    }

    /*
    |--------------------------------------------------------------------------
    | MAIN PAYSLIP QUERY — period is MANDATORY
    |--------------------------------------------------------------------------
    */
    $payslips = collect();
    if ($filteredBranchIds->isNotEmpty() && $currentSectorHasPayslips && $savedPeriodId) {
        $query = DB::connection('tenant')
            ->table('payroll_entries')
            ->join('payroll_periods', 'payroll_periods.id', '=', 'payroll_entries.payroll_period_id')
            ->join('users',           'users.id',           '=', 'payroll_entries.employee_id')
            ->join('branches',        'branches.id',        '=', 'users.branch')
            ->leftJoin('categories',  'categories.id',      '=', 'branches.category')
            ->whereIn('payroll_periods.status', ['approved', 'paid'])
            ->whereIn('users.branch', $filteredBranchIds)
            ->where('payroll_entries.payroll_period_id', (int)$savedPeriodId)
            ->select(
                'payroll_entries.id',
                'payroll_entries.payroll_period_id',
                'payroll_entries.employee_id',
                'payroll_entries.basic_salary',
                'payroll_entries.housing_allowance',
                'payroll_entries.transport_allowance',
                'payroll_entries.medical_allowance',
                'payroll_entries.meal_allowance',
                'payroll_entries.other_recurring_allowance',
                'payroll_entries.other_recurring_allowance_label',
                'payroll_entries.acting_allowance',
                'payroll_entries.commissions',
                'payroll_entries.other_variable_allowance',
                'payroll_entries.other_variable_allowance_label',
                'payroll_entries.overtime_amount',
                'payroll_entries.gross_pay',
                'payroll_entries.on_pension',
                'payroll_entries.paye',
                'payroll_entries.pension_employee',
                'payroll_entries.pension_employer',
                'payroll_entries.loan_deduction',
                'payroll_entries.advance_deduction',
                'payroll_entries.other_deductions',
                'payroll_entries.total_deductions',
                'payroll_entries.net_pay',
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
                'branches.sector             as branch_sector',
                'categories.id               as category_id',
                'categories.category         as category_name'
            );

        if ($savedEmployeeId) {
            $query->where('payroll_entries.employee_id', (int)$savedEmployeeId);
        }

        $payslips = $query->orderBy('users.name', 'asc')->get();
    }

    $totalNetPay     = $payslips->sum('net_pay');
    $totalGrossPay   = $payslips->sum('gross_pay');
    $totalDeductions = $payslips->sum('total_deductions');
    $totalBasic      = $payslips->sum('basic_salary');
    $totalAllowances = $totalGrossPay - $totalBasic;
@endphp

<style>
/* ── DataTable export buttons ───────────────────────────────────────────── */
.dt-buttons .btn {
  background: transparent !important; background-image: none !important;
  box-shadow: none !important; border-color: #5bc0de; color: #5bc0de;
}
.dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }

/* ── Card chrome ────────────────────────────────────────────────────────── */
.card-header {
  padding: 0.5rem 1.5rem !important;
  background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff;
  border-radius: 10px 10px 0 0 !important;
}
.card-body  { padding: 0 1.5rem 1.5rem 1.5rem !important; }
.card       { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; }
.card-header h4 { color:#fff; font-weight:600; margin-bottom:0; display:flex; align-items:center; }
.card-header h4 i { margin-right: 0.25rem; }
.card-header .btn-light {
  height:28px; padding:0 10px;
  display:flex; align-items:center; justify-content:center; line-height:1;
}
.card-header .btn-light:hover { background-color:#f8f9fa; transition:background-color 0.2s; }

/* ── Sector select in header ────────────────────────────────────────────── */
#sectorSelectHeader {
  border: none; background: transparent; color: #fff;
  font-size: 18px; font-weight: 600; cursor: pointer;
  padding: 0; outline: none; max-width: 360px;
}
#sectorSelectHeader option { color: #1e293b; background: #fff; font-size: 14px; }
#sectorSelectHeader:disabled { opacity: 0.75; cursor: default; }

/* ── Filter bar ─────────────────────────────────────────────────────────── */
.card-filter {
  background: #eef0f7; border-bottom: 1px solid #d6daf0;
  padding: 9px 1.5rem; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
}
.card-filter label { font-size:12px; font-weight:600; color:#4B5EBD; margin-bottom:0; white-space:nowrap; }
.card-filter select {
  font-size:12px; height:30px; padding:0 8px; border-radius:6px;
  border:1px solid #c8d0ed; background:#fff; min-width:140px; max-width:220px;
}
.card-filter select.required-select {
  border-color: #4B5EBD;
  font-weight: 600;
}
.filter-divider { width:1px; height:22px; background:#c8d0ed; margin:0 4px; }
@media (max-width: 767px) {
  .card-filter { flex-direction:column; align-items:stretch; padding:10px 1rem; gap:8px; }
  .card-filter select { max-width:100%; min-width:0; width:100%; }
  .filter-divider { display:none; }
}

/* ── Period status badges ───────────────────────────────────────────────── */
.badge-draft      { background:#6c757d; color:#fff; padding:2px 9px; border-radius:20px; font-size:11px; }
.badge-processing { background:#0dcaf0; color:#fff; padding:2px 9px; border-radius:20px; font-size:11px; }
.badge-approved   { background:#198754; color:#fff; padding:2px 9px; border-radius:20px; font-size:11px; }
.badge-paid       { background:#4B5EBD; color:#fff; padding:2px 9px; border-radius:20px; font-size:11px; }

/* ── Period meta strip ──────────────────────────────────────────────────── */
.period-meta-strip {
  background:#f8f9fa; border-bottom:1px solid #e9ecef;
  padding:10px 1.5rem; display:flex; flex-wrap:wrap;
  align-items:center; gap:0; font-size:12px;
}
.meta-item  { display:flex; flex-direction:column; padding:2px 24px 2px 0; }
.meta-item:last-child { padding-right:0; }
.meta-label { font-size:10px; text-transform:uppercase; letter-spacing:.05em; color:#6c757d; font-weight:600; white-space:nowrap; }
.meta-value { font-weight:600; color:#212529; white-space:nowrap; }
@media (max-width: 767px) {
  .period-meta-strip {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 0; padding: 0;
  }
  .meta-item {
    padding: 8px 12px; border-bottom: 1px solid #e9ecef;
    border-right: 1px solid #e9ecef;
  }
  .meta-item:nth-child(even) { border-right: none; }
  .meta-item:nth-last-child(-n+2) { border-bottom: none; }
}

/* ── Stats strip ────────────────────────────────────────────────────────── */
.stats-strip {
  display: flex; align-items: stretch; flex-wrap: wrap;
  border-bottom: 1px solid #d6daf0; background: #f8f9fc;
}
.stats-strip-item {
  flex: 1; min-width: 140px; padding: 10px 16px;
  border-right: 1px solid #d6daf0; border-bottom: 0;
  display: flex; flex-direction: column; justify-content: center; gap: 3px;
}
.stats-strip-item:last-child { border-right: none; }
.stats-strip-item .s-label {
  font-size: 11px; font-weight: 700; text-transform: uppercase;
  letter-spacing: .06em; color: #94a3b8; display: block;
}
.stats-strip-item .s-value {
  font-size: 16px; font-weight: 700; color: #1e293b;
  display: block; white-space: nowrap;
}
.stats-strip-action {
  display: flex; align-items: center; justify-content: center;
  padding: 10px 16px; border-right: 1px solid #d6daf0; flex-shrink: 0;
}
#bulkActionBtn {
  position: relative; height: 34px; padding: 0 14px;
  display: flex; align-items: center; gap: 6px;
  font-size: 12px; font-weight: 600; border-radius: 6px;
  border: 1px solid #c8d0ed; background: #fff; color: #94a3b8;
  cursor: default; transition: all .2s; white-space: nowrap;
}
#bulkActionBtn.active {
  border-color: #4B5EBD; background: #eef0f7; color: #4B5EBD; cursor: pointer;
}
#bulkActionBtn.active:hover { background: #4B5EBD; color: #fff; }
#bulkActionBtn .bulk-badge {
  position: absolute; top: -8px; right: -8px;
  background: #dc3545; color: #fff; font-size: 10px;
  padding: 1px 5px; border-radius: 50%; display: none; line-height: 1.6;
}
#bulkActionBtn.active .bulk-badge { display: block; }
@media (max-width: 767px) {
  .stats-strip { flex-direction: column; }
  .stats-strip-action {
    justify-content: flex-start; padding: 10px 16px;
    border-right: none; border-bottom: 1px solid #d6daf0;
  }
  .stats-strip-item {
    min-width: 0; flex: none; width: 100%;
    border-right: none; border-bottom: 1px solid #d6daf0;
    flex-direction: row; justify-content: space-between; align-items: center;
    padding: 9px 16px;
  }
  .stats-strip-item:last-child { border-bottom: none; }
  .stats-strip-item .s-label { font-size: 12px; letter-spacing: 0; }
  .stats-strip-item .s-value { font-size: 14px; }
}

/* ── Empty state ────────────────────────────────────────────────────────── */
.empty-state { padding:52px 20px; text-align:center; color:#94a3b8; }
.empty-state i { font-size:52px; display:block; margin-bottom:12px; color:#c8d0ed; }
.empty-state h5 { color:#64748b; font-weight:600; margin-bottom:6px; }
.empty-state p  { font-size:13px; }

/* ── Modal helpers ──────────────────────────────────────────────────────── */
.mh-blue  { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-title { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }
.modal-section-title {
  font-size:11px; font-weight:600; text-transform:uppercase;
  letter-spacing:.07em; color:#6c757d;
  border-bottom:1px solid #e9ecef; padding-bottom:6px; margin:16px 0 10px;
}
.pf-group { background:#f8f9fa; border-radius:6px; padding:9px 12px; }
.pf-label { font-size:11px; color:#6c757d; text-transform:uppercase; letter-spacing:.05em; }
.pf-value { font-size:14px; font-weight:600; color:#212529; }

/* ── Allowances breakdown chip in view modal ────────────────────────────── */
.allow-breakdown {
  background:#f1f5fb; border:1px solid #d6daf0; border-radius:8px;
  padding:10px 14px; font-size:12px; margin-bottom:8px;
}
.allow-breakdown .allow-row {
  display:flex; justify-content:space-between; align-items:center;
  padding:3px 0; border-bottom:1px dashed #e2e8f0;
}
.allow-breakdown .allow-row:last-child { border-bottom:none; }
.allow-breakdown .allow-label { color:#475569; }
.allow-breakdown .allow-value { font-weight:600; color:#1e293b; }
.allow-breakdown .allow-zero  { color:#c8d0e8; font-weight:400; }
.allow-breakdown .allow-total-row {
  display:flex; justify-content:space-between; align-items:center;
  padding:5px 0 0; margin-top:4px; font-weight:700;
  border-top:2px solid #c8d0ed;
}
.allow-breakdown .allow-total-label { color:#4B5EBD; font-size:13px; }
.allow-breakdown .allow-total-value { color:#4B5EBD; font-size:13px; }

/* ── Bulk action modal ──────────────────────────────────────────────────── */
.bulk-action-card {
  border:1px solid #e9ecef; border-radius:10px; padding:20px;
  text-align:center; cursor:pointer; transition:all .2s;
}
.bulk-action-card:hover { border-color:#4B5EBD; background:#f0f2fb; }
.bulk-action-card i { font-size:36px; display:block; margin-bottom:8px; }
.bulk-action-card .ba-label { font-size:13px; font-weight:600; color:#1e293b; }
.bulk-action-card .ba-sub   { font-size:11px; color:#94a3b8; margin-top:3px; }
</style>

<div class="progress" id="progressBar" role="progressbar"
     style="height:8px;transform:rotate(180deg);display:none">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

  {{-- ── Card header ──────────────────────────────────────────────────────── --}}
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0" style="gap:8px;">
      <i class="ri-file-paper-2-line" style="flex-shrink:0;"></i>

      @if($sectors->isEmpty())
        <span style="font-size:18px; font-weight:600; opacity:0.75;">— No Sectors Configured —</span>
      @else
        <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
              id="headerSectorForm" style="margin:0;display:inline;">
          @csrf
          <input type="hidden" name="user_id"             value="{{ Auth::id() }}">
          <input type="hidden" name="payslip_category_id" value="">
          <input type="hidden" name="payslip_period_id"   value="">
          <input type="hidden" name="payslip_employee_id" value="">
          <select name="sector" id="sectorSelectHeader"
                  onchange="document.getElementById('headerSectorForm').submit()">
            @foreach($sectors as $sec)
              <option value="{{ $sec }}" {{ $savedSector === $sec ? 'selected' : '' }}>{{ $sec }}</option>
            @endforeach
          </select>
        </form>
      @endif
    </h4>
    <div class="d-flex align-items-center" style="gap:4px;">
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn"         title="Info"><i class="ri-information-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Export Table"><i class="ri-table-line"></i></a>
    </div>
  </div>

  {{-- ── Filter bar ───────────────────────────────────────────────────────── --}}
  <div class="card-filter">

    {{-- Category --}}
    <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
          id="filterCatForm" style="display:contents;">
      @csrf
      <input type="hidden" name="user_id"             value="{{ Auth::id() }}">
      <input type="hidden" name="sector"              value="{{ $savedSector }}">
      <input type="hidden" name="payslip_period_id"   value="">
      <input type="hidden" name="payslip_employee_id" value="">
      <label>Category:</label>
      <select name="payslip_category_id" onchange="document.getElementById('filterCatForm').submit()">
        <option value="">All Categories</option>
        @forelse($categories as $cat)
          <option value="{{ $cat->id }}" {{ (int)$savedCatId === $cat->id ? 'selected' : '' }}>
            {{ $cat->category }}
          </option>
        @empty
          <option value="" disabled>No categories for this sector</option>
        @endforelse
      </select>
    </form>

    <div class="filter-divider"></div>

    {{-- Period — REQUIRED, no "All Periods" option --}}
    <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
          id="filterPeriodForm" style="display:contents;">
      @csrf
      <input type="hidden" name="user_id"              value="{{ Auth::id() }}">
      <input type="hidden" name="sector"               value="{{ $savedSector }}">
      <input type="hidden" name="payslip_category_id"  value="{{ $savedCatId }}">
      <input type="hidden" name="payslip_employee_id"  value="">
      <label>
        Period: <span style="color:#dc3545;font-size:10px;margin-left:3px;" title="Required">&#9679;</span>
      </label>
      <select name="payslip_period_id" class="required-select"
              onchange="document.getElementById('filterPeriodForm').submit()">
        @if($periods->isEmpty())
          <option value="">— No periods available —</option>
        @else
          @if(!$savedPeriodId)
            <option value="" disabled selected>— Select a Period —</option>
          @endif
          @foreach($periods as $per)
            <option value="{{ $per->id }}" {{ (int)$savedPeriodId === $per->id ? 'selected' : '' }}>
              {{ $per->name }}
              ({{ \Carbon\Carbon::parse($per->period_start)->format('d M') }}
               &ndash; {{ \Carbon\Carbon::parse($per->period_end)->format('d M Y') }})
              — {{ ucfirst($per->status) }}
            </option>
          @endforeach
        @endif
      </select>
    </form>

    <div class="filter-divider"></div>

    {{-- Employee --}}
    <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
          id="filterEmployeeForm" style="display:contents;">
      @csrf
      <input type="hidden" name="user_id"             value="{{ Auth::id() }}">
      <input type="hidden" name="sector"              value="{{ $savedSector }}">
      <input type="hidden" name="payslip_period_id"   value="{{ $savedPeriodId }}">
      <input type="hidden" name="payslip_category_id" value="{{ $savedCatId }}">
      <label>Employee:</label>
      <select name="payslip_employee_id"
              onchange="document.getElementById('filterEmployeeForm').submit()"
              {{ !$savedPeriodId ? 'disabled' : '' }}>
        <option value="">All Employees</option>
        @foreach($employees as $emp)
          <option value="{{ $emp->id }}" {{ (int)$savedEmployeeId === $emp->id ? 'selected' : '' }}>
            {{ $emp->name }}
          </option>
        @endforeach
      </select>
    </form>

    <div class="ms-auto">
      <form method="POST" action="{{ route('tenant.admin.update.filters') }}" style="display:inline;">
        @csrf
        <input type="hidden" name="user_id"             value="{{ Auth::id() }}">
        <input type="hidden" name="sector"              value="{{ $savedSector }}">
        <input type="hidden" name="payslip_period_id"   value="">
        <input type="hidden" name="payslip_category_id" value="">
        <input type="hidden" name="payslip_employee_id" value="">
        <button type="submit" class="btn btn-sm btn-outline-secondary" style="font-size:12px;">
          <i class="ri-refresh-line me-1"></i> Clear Filters
        </button>
      </form>
    </div>
  </div>

  {{-- ── Period meta strip — contextual info only, no financial totals ──── --}}
  @if($selectedPeriod)
  <div class="period-meta-strip">
    <div class="meta-item">
      <span class="meta-label">Period</span>
      <span class="meta-value">{{ $selectedPeriod->name }}</span>
    </div>
    <div class="meta-item">
      <span class="meta-label">Start</span>
      <span class="meta-value">{{ \Carbon\Carbon::parse($selectedPeriod->period_start)->format('d M Y') }}</span>
    </div>
    <div class="meta-item">
      <span class="meta-label">End</span>
      <span class="meta-value">{{ \Carbon\Carbon::parse($selectedPeriod->period_end)->format('d M Y') }}</span>
    </div>
    <div class="meta-item">
      <span class="meta-label">Pay Date</span>
      <span class="meta-value">{{ \Carbon\Carbon::parse($selectedPeriod->pay_date)->format('d M Y') }}</span>
    </div>
    <div class="meta-item">
      <span class="meta-label">Status</span>
      <span class="meta-value">
        <span class="badge-{{ $selectedPeriod->status }}">{{ ucfirst($selectedPeriod->status) }}</span>
      </span>
    </div>
    <div class="meta-item">
      <span class="meta-label">Employees</span>
      <span class="meta-value">{{ $payslips->count() }}</span>
    </div>
  </div>
  @endif

  {{-- ── Stats strip — bulk action + all five financial totals ─────────── --}}
  <div class="stats-strip">
    <div class="stats-strip-action">
      <button id="bulkActionBtn" disabled title="Select rows to enable bulk actions">
        <i class="ri-checkbox-multiple-line"></i> Bulk Actions
        <span class="bulk-badge" id="bulkBadge">0</span>
      </button>
    </div>
    <div class="stats-strip-item">
      <span class="s-label">Basic Pay</span>
      <span class="s-value">{{ number_format($totalBasic, 2) }}</span>
    </div>
    <div class="stats-strip-item">
      <span class="s-label">Total Allowances</span>
      <span class="s-value">{{ number_format($totalAllowances, 2) }}</span>
    </div>
    <div class="stats-strip-item">
      <span class="s-label">Gross Pay</span>
      <span class="s-value" style="color:#198754;">{{ number_format($totalGrossPay, 2) }}</span>
    </div>
    <div class="stats-strip-item">
      <span class="s-label">Total Deductions</span>
      <span class="s-value" style="color:#dc3545;">{{ number_format($totalDeductions, 2) }}</span>
    </div>
    <div class="stats-strip-item">
      <span class="s-label">Net Pay</span>
      <span class="s-value" style="color:#4B5EBD;">{{ number_format($totalNetPay, 2) }}</span>
    </div>
  </div>

  {{-- ── Table / empty state ─────────────────────────────────────────────── --}}
  <div class="card-body">

    @if($sectors->isEmpty())
      <div class="empty-state">
        <i class="ri-building-4-line"></i>
        <h5>No Sectors Configured</h5>
        <p>Please assign a <strong>Sector</strong> to your branches before payslips can be viewed here.</p>
      </div>

    @elseif(!$currentSectorHasPayslips)
      <div class="empty-state">
        <i class="ri-calendar-check-line"></i>
        <h5>No Payslips for This Sector</h5>
        <p>
          The <strong>{{ $savedSector }}</strong> sector has no <strong>Approved</strong> or <strong>Paid</strong> payroll periods yet.<br><br>
          <a href="{{ route('tenant.admin.hr.payroll.periods', ['tenantName' => request()->route('tenantName')]) }}">
            Go to Payroll Periods &rarr;
          </a>
        </p>
      </div>

    @elseif(!$savedPeriodId)
      <div class="empty-state">
        <i class="ri-calendar-line"></i>
        <h5>Select a Payroll Period</h5>
        <p>Choose a period from the filter bar above. Payslips are always viewed one period at a time.</p>
      </div>

    @elseif($payslips->isEmpty())
      <div class="empty-state">
        <i class="ri-file-list-3-line"></i>
        <h5>No Payslips Match Your Filters</h5>
        <p>
          No entries found for the selected period{{ $savedCatId ? ' and category' : '' }}{{ $savedEmployeeId ? ' and employee' : '' }}.<br>
          Try adjusting or <strong>clearing the filters</strong> above.
        </p>
      </div>

    @else

    <table id="maintable" class="table table-sm table-striped row-border order-column w-100" style="margin-top:12px;">
      <thead style="background-color:#e2e2e9">
        <tr>
          <th><input type="checkbox" id="selectAll">&nbsp;&nbsp;Employee</th>
          <th style="text-align:center">Position</th>
          <th style="text-align:center">Branch</th>
          <th style="text-align:center">Basic Pay</th>
          <th style="text-align:center">Allowances</th>
          <th style="text-align:center">Gross Pay</th>
          <th style="text-align:center">Deductions</th>
          <th style="text-align:center">Net Pay</th>
          <th style="text-align:center">Status</th>
          <th style="text-align:center">Action</th>
        </tr>
      </thead>
      <tbody id="tbody">
        @foreach($payslips as $slip)
          @php
            $slipAllowances = $slip->gross_pay - $slip->basic_salary;
          @endphp
          <tr id="row{{ $slip->id }}">
            <td>
              <input type="checkbox" class="row-check" value="{{ $slip->id }}"
                     data-id="{{ $slip->id }}"
                     data-name="{{ $slip->employee_name }}"
                     data-email="{{ $slip->employee_email }}"
                     data-period="{{ $slip->period_name }}">&nbsp;<strong>{{ $slip->employee_name }}</strong>
            </td>
            <td style="text-align:center">{{ $slip->position ?? '—' }}</td>
            <td style="text-align:center">{{ $slip->branch_name ?? '—' }}</td>
            <td style="text-align:center">{{ number_format($slip->basic_salary, 2) }}</td>
            <td style="text-align:center">
              @if($slipAllowances != 0)
                <strong>{{ number_format($slipAllowances, 2) }}</strong>
              @else
                {{ number_format($slipAllowances, 2) }}
              @endif
            </td>
            <td style="text-align:center"><strong>{{ number_format($slip->gross_pay, 2) }}</strong></td>
            <td style="text-align:center"><span style="color:#dc3545;">{{ number_format($slip->total_deductions, 2) }}</span></td>
            <td style="text-align:center"><strong class="text-primary">{{ number_format($slip->net_pay, 2) }}</strong></td>
            <td style="text-align:center"><span class="badge-{{ $slip->period_status }}">{{ ucfirst($slip->period_status) }}</span></td>
            <td style="text-align:center; white-space:nowrap;">
              {{-- VIEW --}}
              <a href="#" class="viewSlipBtn"
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
                 data-basic="{{ $slip->basic_salary }}"
                 data-housing="{{ $slip->housing_allowance }}"
                 data-transport="{{ $slip->transport_allowance }}"
                 data-medical="{{ $slip->medical_allowance }}"
                 data-meal="{{ $slip->meal_allowance }}"
                 data-other-recurring="{{ $slip->other_recurring_allowance }}"
                 data-other-recurring-label="{{ $slip->other_recurring_allowance_label ?? '' }}"
                 data-acting="{{ $slip->acting_allowance }}"
                 data-commissions="{{ $slip->commissions }}"
                 data-other-variable="{{ $slip->other_variable_allowance }}"
                 data-other-variable-label="{{ $slip->other_variable_allowance_label ?? '' }}"
                 data-overtime="{{ $slip->overtime_amount }}"
                 data-gross="{{ $slip->gross_pay }}"
                 data-on-pension="{{ $slip->on_pension }}"
                 data-paye="{{ $slip->paye }}"
                 data-pension-ee="{{ $slip->pension_employee }}"
                 data-pension-er="{{ $slip->pension_employer }}"
                 data-loan="{{ $slip->loan_deduction }}"
                 data-advance="{{ $slip->advance_deduction }}"
                 data-other-ded="{{ $slip->other_deductions }}"
                 data-deductions="{{ $slip->total_deductions }}"
                 data-net="{{ $slip->net_pay }}"
                 data-notes="{{ $slip->entry_notes ?? '' }}"
                 title="View Details">
                <i class="ri-eye-line text-primary" style="font-size:17px;"></i>
              </a>
              {{-- DOWNLOAD --}}
              <a href="{{ route('tenant.admin.hr.payroll.wagebill.payslip', ['tenantName' => request()->route('tenantName')]) }}?entry_id={{ $slip->id }}"
                 title="Download PDF">
                <i class="ri-file-download-line text-success" style="font-size:17px;"></i>
              </a>
              {{-- EMAIL --}}
              <a href="#" class="emailSlipBtn"
                 data-id="{{ $slip->id }}"
                 data-name="{{ $slip->employee_name }}"
                 data-email="{{ $slip->employee_email ?? '' }}"
                 data-period="{{ $slip->period_name }}"
                 title="Email Payslip">
                <i class="ri-mail-send-line text-info" style="font-size:17px;"></i>
              </a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>

    @endif
  </div>
</div>

</div></div></div>


{{-- ════════════════════════════════════════════════════════════════════════
     MODAL — INFO
════════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header mh-blue">
      <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Payslips</h5>
      <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="font-size:13px;">
      <p>The <strong>Payslips</strong> page shows payslips for <strong>Approved</strong> and <strong>Paid</strong> periods. A period must always be selected — payslips cannot be viewed across all periods at once.</p>
      <p class="mb-1"><strong>Filters</strong></p>
      <ul class="mb-3" style="padding-left:18px;">
        <li class="mb-1"><strong>Sector</strong> (header) — Primary scope. Changing it clears all other filters.</li>
        <li class="mb-1"><strong>Category</strong> — Narrow to a category within the sector.</li>
        <li class="mb-1"><strong>Period &#9679;</strong> — Required. Defaults to the most recent period. Changing it resets the employee filter.</li>
        <li class="mb-1"><strong>Employee</strong> — View a single employee's payslip for the selected period.</li>
      </ul>
      <p class="mb-1"><strong>Row actions</strong></p>
      <ul class="mb-0" style="padding-left:18px;">
        <li class="mb-1"><i class="ri-eye-line text-primary"></i> <strong>View</strong> — Full earnings and deductions breakdown.</li>
        <li class="mb-1"><i class="ri-file-download-line text-success"></i> <strong>Download</strong> — PDF payslip.</li>
        <li><i class="ri-mail-send-line text-info"></i> <strong>Email</strong> — Sends PDF to the employee's email address.</li>
      </ul>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
    </div>
  </div></div>
</div>

{{-- ════════════════════════════════════════════════════════════════════════
     MODAL — EXPORT
════════════════════════════════════════════════════════════════════════ --}}
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

{{-- ════════════════════════════════════════════════════════════════════════
     MODAL — VIEW PAYSLIP DETAIL
════════════════════════════════════════════════════════════════════════ --}}
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

        {{-- Employee --}}
        <div class="modal-section-title">Employee</div>
        <div class="row g-2 mb-2">
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Name</div><div class="pf-value" id="vName">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Employee #</div><div class="pf-value" id="vNumber">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Email</div><div class="pf-value" id="vEmail" style="font-size:12px;">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Position</div><div class="pf-value" id="vPosition">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Department</div><div class="pf-value" id="vDepartment">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Branch / Category</div><div class="pf-value" id="vBranchCat">—</div></div></div>
        </div>

        {{-- Period --}}
        <div class="modal-section-title">Period</div>
        <div class="row g-2 mb-2">
          <div class="col-md-3 col-6"><div class="pf-group"><div class="pf-label">Period</div><div class="pf-value" id="vPeriod">—</div></div></div>
          <div class="col-md-3 col-6"><div class="pf-group"><div class="pf-label">Dates</div><div class="pf-value" id="vDates" style="font-size:12px;">—</div></div></div>
          <div class="col-md-3 col-6"><div class="pf-group"><div class="pf-label">Pay Date</div><div class="pf-value" id="vPayDate">—</div></div></div>
          <div class="col-md-3 col-6"><div class="pf-group"><div class="pf-label">Status</div><div class="pf-value" id="vStatus">—</div></div></div>
        </div>

        {{-- Earnings --}}
        <div class="modal-section-title">Earnings</div>

        <div class="row g-2 mb-2">
          <div class="col-12">
            <div class="pf-group" style="background:#f0f4ff;">
              <div class="pf-label">Basic Pay</div>
              <div class="pf-value" id="vBasic">0.00</div>
            </div>
          </div>
        </div>

        <div class="allow-breakdown">
          <div class="allow-row">
            <span class="allow-label">Housing Allowance</span>
            <span class="allow-value" id="vHousing">0.00</span>
          </div>
          <div class="allow-row">
            <span class="allow-label">Transport Allowance</span>
            <span class="allow-value" id="vTransport">0.00</span>
          </div>
          <div class="allow-row">
            <span class="allow-label">Medical Allowance</span>
            <span class="allow-value" id="vMedical">0.00</span>
          </div>
          <div class="allow-row">
            <span class="allow-label">Meal Allowance</span>
            <span class="allow-value" id="vMeal">0.00</span>
          </div>
          <div class="allow-row">
            <span class="allow-label">Other Recurring &mdash; <em id="vOtherRecurringLabel" style="color:#4B5EBD;font-style:normal;"></em></span>
            <span class="allow-value" id="vOtherRecurring">0.00</span>
          </div>
          <div class="allow-row">
            <span class="allow-label">Acting Allowance</span>
            <span class="allow-value" id="vActing">0.00</span>
          </div>
          <div class="allow-row">
            <span class="allow-label">Commissions</span>
            <span class="allow-value" id="vCommissions">0.00</span>
          </div>
          <div class="allow-row">
            <span class="allow-label">Other Variable &mdash; <em id="vOtherVariableLabel" style="color:#4B5EBD;font-style:normal;"></em></span>
            <span class="allow-value" id="vOtherVariable">0.00</span>
          </div>
          <div class="allow-row">
            <span class="allow-label">Overtime</span>
            <span class="allow-value" id="vOvertime">0.00</span>
          </div>
          <div class="allow-total-row">
            <span class="allow-total-label">Total Allowances</span>
            <span class="allow-total-value" id="vAllowancesTotal">0.00</span>
          </div>
        </div>

        <div class="row g-2 mb-2">
          <div class="col-12">
            <div class="pf-group" style="background:#e8f5e9;">
              <div class="pf-label">Gross Pay (Basic + Allowances)</div>
              <div class="pf-value text-success" id="vGross">0.00</div>
            </div>
          </div>
        </div>

        {{-- Deductions --}}
        <div class="modal-section-title">Deductions</div>
        <div class="row g-2 mb-2">
          <div class="col-md-3 col-6"><div class="pf-group"><div class="pf-label">PAYE</div><div class="pf-value" id="vPaye">—</div></div></div>
          <div class="col-md-3 col-6"><div class="pf-group"><div class="pf-label">Pension (Employee)</div><div class="pf-value" id="vPensionEe">—</div></div></div>
          <div class="col-md-3 col-6"><div class="pf-group"><div class="pf-label">Pension (Employer)</div><div class="pf-value" id="vPensionEr">—</div></div></div>
          <div class="col-md-3 col-6"><div class="pf-group"><div class="pf-label">Loan</div><div class="pf-value" id="vLoan">—</div></div></div>
          <div class="col-md-3 col-6"><div class="pf-group"><div class="pf-label">Advance</div><div class="pf-value" id="vAdvance">—</div></div></div>
          <div class="col-md-3 col-6"><div class="pf-group"><div class="pf-label">Other Deductions</div><div class="pf-value" id="vOtherDed">—</div></div></div>
        </div>

        {{-- Summary --}}
        <div class="modal-section-title">Summary</div>
        <div class="row g-2 mb-2">
          <div class="col-md-4 col-6"><div class="pf-group" style="background:#e8f5e9;"><div class="pf-label">Gross Pay</div><div class="pf-value text-success" id="vGross2">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group" style="background:#fdecea;"><div class="pf-label">Total Deductions</div><div class="pf-value text-danger" id="vDeductions">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group" style="background:#e8eaf6;"><div class="pf-label">Net Pay</div><div class="pf-value text-primary" id="vNet">—</div></div></div>
        </div>

        {{-- Notes --}}
        <div id="vNotesRow" style="display:none;">
          <div class="modal-section-title">Notes</div>
          <div class="pf-group"><div class="pf-value" id="vNotes" style="font-size:13px;font-weight:400;"></div></div>
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

{{-- ════════════════════════════════════════════════════════════════════════
     MODAL — EMAIL PAYSLIP
════════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="emailSlipModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:430px;">
    <div class="modal-content">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-mail-send-line"></i>&nbsp; Email Payslip</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <p style="font-size:13px;" class="mb-3">
          Send payslip for <strong id="emailName"></strong>
          (<span id="emailPeriod" class="text-primary fw-bold"></span>) to their registered email.
        </p>
        <div class="pf-group mb-3">
          <div class="pf-label">Recipient</div>
          <div class="pf-value" id="emailAddress" style="font-size:13px;">—</div>
        </div>
        <div class="alert alert-warning py-2" id="noEmailAlert" style="display:none;font-size:12px;">
          <i class="ri-error-warning-line me-1"></i>
          This employee has no email address on file.
        </div>
        <div class="form-group">
          <label style="font-size:13px;font-weight:600;">Additional Note <small class="text-muted fw-normal">(optional)</small></label>
          <textarea class="form-control" id="emailNote" rows="2" placeholder="Add a short note…" style="font-size:13px;"></textarea>
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

{{-- ════════════════════════════════════════════════════════════════════════
     MODAL — BULK ACTIONS
════════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="bulkActionsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:480px;">
    <div class="modal-content">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title">
          <i class="ri-checkbox-multiple-line"></i>&nbsp; Bulk Actions &mdash; <span id="bulkModalCount">0</span> selected
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="row g-3">
          <div class="col-6">
            <div class="bulk-action-card" id="bulkDownloadCard">
              <i class="ri-file-download-line text-success"></i>
              <div class="ba-label">Download PDFs</div>
              <div class="ba-sub">Opens each payslip in a new tab</div>
            </div>
          </div>
          <div class="col-6">
            <div class="bulk-action-card" id="bulkEmailCard">
              <i class="ri-mail-send-line text-info"></i>
              <div class="ba-label">Email Payslips</div>
              <div class="ba-sub">Sends each employee their PDF</div>
            </div>
          </div>
        </div>
        <div id="bulkEmailNoteWrap" style="display:none;margin-top:18px;">
          <div class="modal-section-title" style="margin-top:0;">Email Note</div>
          <div class="alert alert-info py-2 mb-2" style="font-size:12px;">
            <i class="ri-information-line me-1"></i> Employees with no email on file will be skipped.
          </div>
          <textarea class="form-control" id="bulkEmailNote" rows="2" placeholder="Optional note…" style="font-size:13px;"></textarea>
        </div>
        <div id="bulkDownloadNoteWrap" style="display:none;margin-top:18px;">
          <div class="alert alert-warning py-2 mb-0" style="font-size:12px;">
            <i class="ri-error-warning-line me-1"></i> Your browser may block multiple popups. Allow popups if prompted.
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" id="confirmBulkActionBtn" style="display:none;">
          <i class="ri-check-line me-1"></i> Confirm
        </button>
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
    var emailUrl       = '{{ route("tenant.admin.hr.payroll.payslips.email",   ["tenantName" => request()->route("tenantName")]) }}';
    var bulkEmailUrl   = '{{ route("tenant.admin.hr.payroll.payslips.bulkemail",["tenantName" => request()->route("tenantName")]) }}';
    var csrfToken      = '{{ csrf_token() }}';

    @if($payslips->isNotEmpty())
    var table = $('#maintable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
        fixedColumns: { left: 1 },
        scrollX: true,
        columnDefs: [{ targets: [9], orderable: false }],
        buttons: [
            { extend: 'excelHtml5', title: 'Payslips', exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'csvHtml5',   title: 'Payslips', exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'pdfHtml5',   title: 'Payslips', exportOptions: { columns: ':visible:not(:last-child)' },
              orientation: 'landscape', pageSize: 'A3',
              customize: function(doc) {
                  doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
              }
            }
        ]
    });
    table.buttons().container().appendTo($('#buttonsModal .buttons'));
    @endif

    var viewSlipModal    = new bootstrap.Modal('#viewSlipModal');
    var emailSlipModal   = new bootstrap.Modal('#emailSlipModal');
    var bulkActionsModal = new bootstrap.Modal('#bulkActionsModal');

    $('#infoBtn').on('click',         function(e) { e.preventDefault(); $('#infoModal').modal('show'); });
    $('#tableButtonsBtn').on('click', function(e) { e.preventDefault(); $('#buttonsModal').modal('show'); });

    // ── Number formatter ───────────────────────────────────────────────────
    function fmt(n) {
        return parseFloat(n || 0).toLocaleString('en', { minimumFractionDigits:2, maximumFractionDigits:2 });
    }

    // ── VIEW payslip detail ────────────────────────────────────────────────
    $('#tbody').on('click', '.viewSlipBtn', function(e) {
        e.preventDefault();
        var d = $(this).data();

        $('#vSlipTitle').text(d.employeeName + ' — ' + d.period);

        $('#vName').text(d.employeeName);
        $('#vNumber').text(d.employeeNumber || '—');
        $('#vEmail').text(d.employeeEmail   || '—');
        $('#vPosition').text(d.position     || '—');
        $('#vDepartment').text(d.department || '—');
        $('#vBranchCat').text((d.branch || '—') + (d.category ? ' / ' + d.category : ''));

        $('#vPeriod').text(d.period);
        $('#vDates').text(d.periodStart + ' – ' + d.periodEnd);
        $('#vPayDate').text(d.payDate);
        $('#vStatus').html('<span class="badge-' + d.periodStatus + '">' + d.periodStatus.charAt(0).toUpperCase() + d.periodStatus.slice(1) + '</span>');

        $('#vBasic').text(fmt(d.basic));

        $('#vHousing').text(fmt(d.housing));
        $('#vTransport').text(fmt(d.transport));
        $('#vMedical').text(fmt(d.medical));
        $('#vMeal').text(fmt(d.meal));
        $('#vOtherRecurringLabel').text(d.otherRecurringLabel || 'Other Recurring');
        $('#vOtherRecurring').text(fmt(d.otherRecurring));
        $('#vActing').text(fmt(d.acting));
        $('#vCommissions').text(fmt(d.commissions));
        $('#vOtherVariableLabel').text(d.otherVariableLabel || 'Other Variable');
        $('#vOtherVariable').text(fmt(d.otherVariable));
        $('#vOvertime').text(fmt(d.overtime));

        $('#vHousing, #vTransport, #vMedical, #vMeal, #vOtherRecurring, #vActing, #vCommissions, #vOtherVariable, #vOvertime').each(function() {
            var val = parseFloat($(this).text().replace(/,/g,''));
            $(this).toggleClass('allow-zero', val === 0);
        });

        var allowTotal = parseFloat(d.housing       || 0)
                       + parseFloat(d.transport     || 0)
                       + parseFloat(d.medical       || 0)
                       + parseFloat(d.meal          || 0)
                       + parseFloat(d.otherRecurring|| 0)
                       + parseFloat(d.acting        || 0)
                       + parseFloat(d.commissions   || 0)
                       + parseFloat(d.otherVariable || 0)
                       + parseFloat(d.overtime      || 0);
        $('#vAllowancesTotal').text(fmt(allowTotal));

        $('#vGross').text(fmt(d.gross));
        $('#vGross2').text(fmt(d.gross));

        $('#vPaye').text(fmt(d.paye));
        $('#vPensionEe').text(fmt(d.pensionEe));
        $('#vPensionEr').text(fmt(d.pensionEr));
        $('#vLoan').text(fmt(d.loan));
        $('#vAdvance').text(fmt(d.advance));
        $('#vOtherDed').text(fmt(d.otherDed));
        $('#vDeductions').text(fmt(d.deductions));
        $('#vNet').text(fmt(d.net));

        if (d.notes) { $('#vNotes').text(d.notes); $('#vNotesRow').show(); }
        else         { $('#vNotesRow').hide(); }

        $('#vDownloadBtn').attr('href', payslipBaseUrl + '?entry_id=' + d.id);
        $('#vEmailBtn').off('click').on('click', function(e) {
            e.preventDefault();
            viewSlipModal.hide();
            setTimeout(function() { openEmailModal(d.id, d.employeeName, d.employeeEmail, d.period); }, 400);
        });

        viewSlipModal.show();
    });

    // ── Email modal ────────────────────────────────────────────────────────
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
                if (data.status === 200) { toastr.success(data.success, 'Sent'); emailSlipModal.hide(); }
                else { toastr.error(data.error || 'Failed to send.', 'Error'); }
            },
            error: function() { toastr.error('Server error.', 'Error'); }
        });
    });

    // ── Bulk selection ─────────────────────────────────────────────────────
    function getChecked() { return $('.row-check:checked'); }

    function updateBulkState() {
        var count = getChecked().length;
        $('#bulkBadge').text(count);
        if (count > 0) { $('#bulkActionBtn').prop('disabled', false).addClass('active'); }
        else           { $('#bulkActionBtn').prop('disabled', true).removeClass('active'); }
    }

    $('#selectAll').on('click', function() {
        $('.row-check').prop('checked', this.checked);
        updateBulkState();
    });

    $('#tbody').on('click', '.row-check', function() {
        var total = $('.row-check').length, checked = getChecked().length;
        $('#selectAll').prop('indeterminate', checked > 0 && checked < total).prop('checked', checked === total);
        updateBulkState();
    });

    // ── Bulk actions modal ─────────────────────────────────────────────────
    $('#bulkActionBtn').on('click', function() {
        var checked = getChecked();
        if (!checked.length) return;
        $('#bulkModalCount').text(checked.length);
        $('#bulkEmailNoteWrap, #bulkDownloadNoteWrap').hide();
        $('#confirmBulkActionBtn').hide();
        $('#bulkDownloadCard, #bulkEmailCard').css({ 'border-color':'#e9ecef', 'background':'' });
        $('#bulkEmailNote').val('');
        $('#confirmBulkActionBtn').data('action', '');
        bulkActionsModal.show();
    });

    $('#bulkDownloadCard').on('click', function() {
        $('#bulkDownloadCard').css({ 'border-color':'#198754', 'background':'#f0faf3' });
        $('#bulkEmailCard').css({ 'border-color':'#e9ecef', 'background':'' });
        $('#bulkEmailNoteWrap').hide(); $('#bulkDownloadNoteWrap').show();
        $('#confirmBulkActionBtn').data('action', 'download').show().removeClass('btn-info').addClass('btn-success').html('<i class="ri-file-download-line me-1"></i> Download All');
    });

    $('#bulkEmailCard').on('click', function() {
        $('#bulkEmailCard').css({ 'border-color':'#0dcaf0', 'background':'#f0fbfc' });
        $('#bulkDownloadCard').css({ 'border-color':'#e9ecef', 'background':'' });
        $('#bulkDownloadNoteWrap').hide(); $('#bulkEmailNoteWrap').show();
        $('#confirmBulkActionBtn').data('action', 'email').show().removeClass('btn-success').addClass('btn-info').html('<i class="ri-mail-send-line me-1"></i> Send All');
    });

    $('#confirmBulkActionBtn').on('click', function() {
        var action = $(this).data('action');
        if (action === 'download') {
            bulkActionsModal.hide();
            getChecked().each(function(i) {
                var entryId = $(this).data('id');
                setTimeout(function() { window.open(payslipBaseUrl + '?entry_id=' + entryId, '_blank'); }, i * 350);
            });
            $('.row-check, #selectAll').prop('checked', false).prop('indeterminate', false);
            updateBulkState();
        } else if (action === 'email') {
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
                        bulkActionsModal.hide();
                        $('.row-check, #selectAll').prop('checked', false).prop('indeterminate', false);
                        updateBulkState();
                    } else { toastr.error(data.error || 'Some emails failed.', 'Error'); }
                },
                error: function() { toastr.error('Server error.', 'Error'); }
            });
        }
    });

});
</script>
@endsection