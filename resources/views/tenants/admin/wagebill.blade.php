@extends('tenants.admin.dashboard')
@section('content')
@php
    /* ── User preferences ─────────────────────────────────────────────── */
    $pref = DB::connection('tenant')->table('user_filters')
                ->where('user_id', Auth::id())
                ->first();

    $savedSector   = ($pref->sector               ?? null) ?: null;
    $savedCatId    = ($pref->wagebill_category_id ?? null) ?: null;
    $savedPeriodId = ($pref->wagebill_period_id   ?? null) ?: null;

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
    | BRANCH IDs — root scope for every downstream query
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
    | EMPLOYEE IDs IN THOSE BRANCHES
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
    | CATEGORY LIST — only categories with branches in selected sector
    |--------------------------------------------------------------------------
    */
    $categories = collect();
    if ($savedSector) {
        $catIds = DB::connection('tenant')
            ->table('branches')
            ->where('sector', $savedSector)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
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
    | PERIOD LIST — only periods with entries in the filtered branches
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
            ->orderBy('period_start', 'desc')
            ->get();
    }

    if ($savedPeriodId && $periods->where('id', (int)$savedPeriodId)->isEmpty()) {
        $savedPeriodId = null;
    }

    /*
    |--------------------------------------------------------------------------
    | WAGE BILL ENTRIES — strictly scoped to filteredBranchIds
    |--------------------------------------------------------------------------
    */
    $period  = null;
    $entries = collect();

    if ($savedPeriodId && $filteredBranchIds->isNotEmpty()) {
        $period = DB::connection('tenant')
            ->table('payroll_periods')
            ->where('id', $savedPeriodId)
            ->first();

        if ($period) {
            $entries = DB::connection('tenant')
                ->table('payroll_entries')
                ->join('users',    'users.id',    '=', 'payroll_entries.employee_id')
                ->join('branches', 'branches.id', '=', 'users.branch')
                ->where('payroll_entries.payroll_period_id', $savedPeriodId)
                ->whereIn('users.branch', $filteredBranchIds)
                ->select(
                    'payroll_entries.*',
                    'users.name                as employee_name',
                    'users.phone               as employee_number',
                    'users.bank_name           as bank_name',
                    'users.bank_account_number as bank_account_number',
                    'branches.name             as branch_name'
                )
                ->orderBy('users.name', 'asc')
                ->get();
        }
    }

    /* ── Branch list for the single-branch selector in download modals ── */
    $sectorBranches = collect();
    if ($savedSector) {
        $sectorBranches = DB::connection('tenant')
            ->table('branches')
            ->where('sector', $savedSector)
            ->orderBy('name')
            ->get();
    }
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
.filter-divider { width:1px; height:22px; background:#c8d0ed; margin:0 4px; }

/* ── Period status badges ───────────────────────────────────────────────── */
.badge-draft      { background:#6c757d; color:#fff; padding:2px 9px; border-radius:20px; font-size:11px; }
.badge-processing { background:#0dcaf0; color:#fff; padding:2px 9px; border-radius:20px; font-size:11px; }
.badge-approved   { background:#198754; color:#fff; padding:2px 9px; border-radius:20px; font-size:11px; }
.badge-paid       { background:#4B5EBD; color:#fff; padding:2px 9px; border-radius:20px; font-size:11px; }

/* ── Period meta strip ──────────────────────────────────────────────────── */
.period-meta-strip {
  background:#f8f9fa; border-bottom:1px solid #e9ecef;
  padding:8px 1.5rem; display:flex; flex-wrap:wrap; gap:24px;
  align-items:center; font-size:12px;
}
.period-meta-strip .meta-item { display:flex; flex-direction:column; }
.period-meta-strip .meta-label { font-size:10px; text-transform:uppercase; letter-spacing:.05em; color:#6c757d; font-weight:600; }
.period-meta-strip .meta-value { font-weight:600; color:#212529; }

/* ── Locked banner ──────────────────────────────────────────────────────── */
.period-locked-banner {
  background:#fff3cd; border:1px solid #ffc107; border-radius:6px;
  padding:7px 14px; font-size:12px; margin:10px 1.5rem 0;
  display:flex; align-items:center; gap:7px;
}

/* ── Empty state ────────────────────────────────────────────────────────── */
.empty-state { padding:52px 20px; text-align:center; color:#94a3b8; }
.empty-state i { font-size:52px; display:block; margin-bottom:12px; color:#c8d0ed; }
.empty-state h5 { color:#64748b; font-weight:600; margin-bottom:6px; }
.empty-state p  { font-size:13px; }

/* ── Entry view/edit modal ──────────────────────────────────────────────── */
.modal-section-title {
  font-size:11px; font-weight:600; text-transform:uppercase;
  letter-spacing:.07em; color:#6c757d;
  border-bottom:1px solid #e9ecef; padding-bottom:6px; margin:16px 0 10px;
}
.entry-field-group { background:#f8f9fa; border-radius:6px; padding:9px 12px; }
.entry-field-label { font-size:11px; color:#6c757d; text-transform:uppercase; letter-spacing:.05em; }
.entry-field-value { font-size:14px; font-weight:600; color:#212529; }

/* ── Modal header ───────────────────────────────────────────────────────── */
.mh-blue   { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-orange { background:linear-gradient(135deg,#d97706,#f59e0b); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-title  { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close  { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }

/* ══════════════════════════════════════════════════════════════════════════
   SHARED DOWNLOAD MODAL COMPONENTS
   Used by both the Wage Bill modal and the Statutory Deductions modal
══════════════════════════════════════════════════════════════════════════ */

/* Section card wrapper */
.wdl-section {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
  margin-bottom: 14px;
}
.wdl-section:last-child { margin-bottom: 0; }

/* Section header bar */
.wdl-section-header {
  background: #f1f5fb;
  padding: 10px 16px;
  display: flex;
  align-items: flex-start;
  gap: 10px;
  border-bottom: 1px solid #e2e8f0;
}
.wdl-section-header .wdl-hicon {
  font-size: 17px;
  color: #4B5EBD;
  margin-top: 1px;
  flex-shrink: 0;
}
.wdl-section-header .wdl-hicon-orange { color: #d97706; }
.wdl-section-header .wdl-title  { font-size: 13px; font-weight: 600; color: #1e293b; line-height: 1.3; }
.wdl-section-header .wdl-sub    { font-size: 11px; color: #64748b; margin-top: 2px; line-height: 1.4; }

/* Section body */
.wdl-section-body { padding: 12px 16px; background: #fff; }

/* Format buttons */
.wdl-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 5px 13px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  text-decoration: none;
  border: 1px solid;
  transition: background .15s, color .15s;
  cursor: pointer;
}
.wdl-btn-excel  { border-color:#198754; color:#198754; }
.wdl-btn-excel:hover  { background:#198754; color:#fff; }
.wdl-btn-pdf    { border-color:#dc3545; color:#dc3545; }
.wdl-btn-pdf:hover    { background:#dc3545; color:#fff; }
.wdl-btn-csv    { border-color:#6c757d; color:#6c757d; }
.wdl-btn-csv:hover    { background:#6c757d; color:#fff; }

/* Scope badge shown inside section header */
.wdl-scope-badge {
  display: inline-flex; align-items: center; gap: 4px;
  background: #e8eaf6; color: #4B5EBD; border-radius: 20px;
  font-size: 10px; font-weight: 600; padding: 2px 9px;
  margin-left: 6px; vertical-align: middle;
}
.wdl-scope-badge-orange {
  background: #fef3c7; color: #92400e;
}

/* Divider between groups of sections */
.wdl-group-label {
  font-size: 10px; font-weight: 700; text-transform: uppercase;
  letter-spacing: .07em; color: #94a3b8;
  margin: 18px 0 10px;
  display: flex; align-items: center; gap: 8px;
}
.wdl-group-label::after {
  content: ''; flex: 1; height: 1px; background: #e2e8f0;
}

/* Branch select inside a section body */
.wdl-branch-select {
  font-size: 12px; height: 30px; padding: 0 8px;
  border-radius: 6px; border: 1px solid #c8d0ed;
  background: #fff; min-width: 180px; max-width: 100%;
}

/* No-period notice inside modals */
.wdl-no-period {
  background: #fff8e1; border: 1px solid #ffc107; border-radius: 8px;
  padding: 14px 18px; font-size: 13px; color: #78350f;
  display: flex; align-items: center; gap: 10px;
}
.wdl-no-period i { font-size: 20px; color: #f59e0b; flex-shrink: 0; }
</style>

<div class="progress" id="progressBar" role="progressbar"
     style="height:8px;transform:rotate(180deg);display:none">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

  {{-- ══════════════════════════════════════════════════════════════════════
       CARD HEADER — sector selector + action buttons
  ══════════════════════════════════════════════════════════════════════ --}}
  <div class="card-header d-flex justify-content-between align-items-center">

    {{-- Left: icon + sector dropdown --}}
    <h4 class="header-title mb-0" style="gap:8px;">
      <i class="ri-bill-line" style="flex-shrink:0;"></i>

      @if($sectors->isEmpty())
        <span style="font-size:18px;font-weight:600;opacity:0.75;">— No Sectors Configured —</span>
      @else
        <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
              id="headerSectorForm" style="margin:0;display:inline;">
          @csrf
          {{-- Changing sector clears category + period — prevents cross-sector data leaking --}}
          <input type="hidden" name="user_id"              value="{{ Auth::id() }}">
          <input type="hidden" name="wagebill_category_id" value="">
          <input type="hidden" name="wagebill_period_id"   value="">
          <select name="sector" id="sectorSelectHeader"
                  onchange="document.getElementById('headerSectorForm').submit()">
            @foreach($sectors as $sec)
              <option value="{{ $sec }}" {{ $savedSector === $sec ? 'selected' : '' }}>
                {{ $sec }}
              </option>
            @endforeach
          </select>
        </form>
      @endif
    </h4>

    {{-- Right: action buttons ──────────────────────────────────────────── --}}
    <div class="d-flex align-items-center" style="gap:4px;">

      {{-- Statutory Deductions download (new) --}}
      <a href="#" class="btn btn-light fs-16 mx-1" id="statutoryBtn"
         style="color:#d97706;" title="Statutory Deductions Report">
        <i class="ri-government-line"></i>
      </a>

      {{-- Wage Bill download --}}
      <a href="#" class="btn btn-light text-success fs-16 mx-1" id="wageDownloadBtn"
         title="Download Wage Bill">
        <i class="ri-download-2-line"></i>
      </a>

      {{-- DataTable export --}}
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn"
         title="Export Table">
        <i class="ri-table-line"></i>
      </a>

      {{-- Info --}}
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn"
         title="Info">
        <i class="ri-information-line"></i>
      </a>

    </div>
  </div>

  {{-- ══════════════════════════════════════════════════════════════════════
       FILTER BAR — Category → Period   (sector lives in the header)
  ══════════════════════════════════════════════════════════════════════ --}}
  <div class="card-filter">

    {{-- Category — scoped to current sector --}}
    <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
          id="filterCatForm" style="display:contents;">
      @csrf
      <input type="hidden" name="user_id"              value="{{ Auth::id() }}">
      <input type="hidden" name="sector"               value="{{ $savedSector }}">
      <input type="hidden" name="wagebill_period_id"   value="">
      <label>Category:</label>
      <select name="wagebill_category_id"
              onchange="document.getElementById('filterCatForm').submit()"
              {{ $sectors->isEmpty() ? 'disabled' : '' }}>
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

    {{-- Payroll Period — scoped to sector + category --}}
    <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
          id="filterPeriodForm" style="display:contents;">
      @csrf
      <input type="hidden" name="user_id"              value="{{ Auth::id() }}">
      <input type="hidden" name="sector"               value="{{ $savedSector }}">
      <input type="hidden" name="wagebill_category_id" value="{{ $savedCatId }}">
      <label>Payroll Period:</label>
      <select name="wagebill_period_id"
              onchange="document.getElementById('filterPeriodForm').submit()"
              {{ $sectors->isEmpty() ? 'disabled' : '' }}>
        <option value="">— Select Period —</option>
        @forelse($periods as $per)
          <option value="{{ $per->id }}" {{ (int)$savedPeriodId === $per->id ? 'selected' : '' }}>
            {{ $per->name }}
            ({{ \Carbon\Carbon::parse($per->period_start)->format('d M') }}
             &ndash; {{ \Carbon\Carbon::parse($per->period_end)->format('d M Y') }})
          </option>
        @empty
          <option value="" disabled>No periods found for this selection</option>
        @endforelse
      </select>
    </form>

    {{-- Right side: Clear + back to Periods --}}
    <div class="ms-auto d-flex align-items-center gap-2">
      <form method="POST" action="{{ route('tenant.admin.update.filters') }}" style="display:inline;">
        @csrf
        <input type="hidden" name="user_id"              value="{{ Auth::id() }}">
        <input type="hidden" name="sector"               value="{{ $savedSector }}">
        <input type="hidden" name="wagebill_period_id"   value="">
        <input type="hidden" name="wagebill_category_id" value="">
        <button type="submit" class="btn btn-sm btn-outline-secondary" style="font-size:12px;">
          <i class="ri-refresh-line me-1"></i> Clear Filters
        </button>
      </form>
      <a href="{{ route('tenant.admin.hr.payroll.periods', ['tenantName' => request()->route('tenantName')]) }}"
         class="btn btn-sm btn-outline-secondary" style="font-size:12px;">
        <i class="ri-arrow-left-line me-1"></i> Periods
      </a>
    </div>

  </div>{{-- /.card-filter --}}

  {{-- ── Period meta strip ───────────────────────────────────────────────── --}}
  @if($period)
  <div class="period-meta-strip">
    <div class="meta-item">
      <span class="meta-label">Period</span>
      <span class="meta-value">{{ $period->name }}</span>
    </div>
    <div class="meta-item">
      <span class="meta-label">Start</span>
      <span class="meta-value">{{ \Carbon\Carbon::parse($period->period_start)->format('d M Y') }}</span>
    </div>
    <div class="meta-item">
      <span class="meta-label">End</span>
      <span class="meta-value">{{ \Carbon\Carbon::parse($period->period_end)->format('d M Y') }}</span>
    </div>
    <div class="meta-item">
      <span class="meta-label">Pay Date</span>
      <span class="meta-value">{{ \Carbon\Carbon::parse($period->pay_date)->format('d M Y') }}</span>
    </div>
    <div class="meta-item">
      <span class="meta-label">Status</span>
      <span class="meta-value">
        <span class="badge-{{ $period->status }}">{{ ucfirst($period->status) }}</span>
      </span>
    </div>
    <div class="meta-item">
      <span class="meta-label">Employees</span>
      <span class="meta-value">{{ $entries->count() }}</span>
    </div>
    <div class="meta-item">
      <span class="meta-label">Total Net Pay</span>
      <span class="meta-value text-primary">{{ number_format($entries->sum('net_pay'), 2) }}</span>
    </div>
  </div>

  @if(in_array($period->status, ['approved', 'paid']))
  <div class="period-locked-banner">
    <i class="ri-lock-line text-warning" style="font-size:15px"></i>
    <span>This period is <strong>{{ ucfirst($period->status) }}</strong> — entries are locked and cannot be edited.</span>
  </div>
  @endif
  @endif

  {{-- ══════════════════════════════════════════════════════════════════════
       TABLE / EMPTY STATE
  ══════════════════════════════════════════════════════════════════════ --}}
  <div class="card-body" style="padding-top:0 !important;">

    @if($sectors->isEmpty())
      <div class="empty-state">
        <i class="ri-building-4-line"></i>
        <h5>No Sectors Configured</h5>
        <p>Please assign a <strong>Sector</strong> to your branches before the wage bill can be viewed.</p>
      </div>

    @elseif(!$savedPeriodId)
      <div class="empty-state">
        <i class="ri-calendar-check-line"></i>
        <h5>Select a Payroll Period</h5>
        <p>Choose a payroll period from the filter bar above to load the wage bill.</p>
      </div>

    @elseif($entries->isEmpty())
      <div class="empty-state">
        <i class="ri-file-list-3-line"></i>
        <h5>No Entries Found</h5>
        <p>No wage bill entries exist for the selected period{{ $savedCatId ? ' and category' : '' }}.<br>
           Go to <a href="{{ route('tenant.admin.hr.payroll.periods', ['tenantName' => request()->route('tenantName')]) }}">Payroll Periods</a> and generate the wage bill first.</p>
      </div>

    @else
    <div style="overflow-x:auto;">
    <table id="maintable" class="table table-sm table-striped row-border order-column w-100"
           style="margin-top:12px;">
      <thead style="background-color:#e2e2e9">
        <tr>
          <th style="text-align:left">Employee</th>
          <th style="text-align:center">Branch</th>
          <th style="text-align:center">Bank Name</th>
          <th style="text-align:center">Account No.</th>
          <th style="text-align:center">Basic</th>
          <th style="text-align:center">Housing</th>
          <th style="text-align:center">Transport</th>
          <th style="text-align:center">Other Allow.</th>
          <th style="text-align:center">Overtime</th>
          <th style="text-align:center">Gross Pay</th>
          <th style="text-align:center">PAYE</th>
          <th style="text-align:center">Pension (Ee)</th>
          <th style="text-align:center">Loan</th>
          <th style="text-align:center">Advance</th>
          <th style="text-align:center">Other Ded.</th>
          <th style="text-align:center">Total Ded.</th>
          <th style="text-align:center">Net Pay</th>
          <th style="text-align:center">Action</th>
        </tr>
      </thead>
      <tbody id="tbody">
        @foreach($entries as $entry)
          <?php $row = 'row' . $entry->id; ?>
          <tr id="{{ $row }}">
            <td style="text-align:left"><strong>{{ $entry->employee_name }}</strong></td>
            <td style="text-align:center">{{ $entry->branch_name ?? '—' }}</td>
            <td style="text-align:center">{{ $entry->bank_name ?? '—' }}</td>
            <td style="text-align:center">{{ $entry->bank_account_number ?? '—' }}</td>
            <td style="text-align:center">{{ number_format($entry->basic_salary,        2) }}</td>
            <td style="text-align:center">{{ number_format($entry->housing_allowance,   2) }}</td>
            <td style="text-align:center">{{ number_format($entry->transport_allowance, 2) }}</td>
            <td style="text-align:center">{{ number_format($entry->other_allowances,    2) }}</td>
            <td style="text-align:center">{{ number_format($entry->overtime_amount,     2) }}</td>
            <td style="text-align:center"><strong>{{ number_format($entry->gross_pay,   2) }}</strong></td>
            <td style="text-align:center">{{ number_format($entry->paye,               2) }}</td>
            <td style="text-align:center">{{ number_format($entry->pension_employee,   2) }}</td>
            <td style="text-align:center">{{ number_format($entry->loan_deduction,     2) }}</td>
            <td style="text-align:center">{{ number_format($entry->advance_deduction,  2) }}</td>
            <td style="text-align:center">{{ number_format($entry->other_deductions,   2) }}</td>
            <td style="text-align:center"><strong>{{ number_format($entry->total_deductions, 2) }}</strong></td>
            <td style="text-align:center"><strong class="text-primary">{{ number_format($entry->net_pay, 2) }}</strong></td>
            <td style="text-align:center; white-space:nowrap;">
              <a href="#" class="viewEntryBtn btn btn-light text-primary btn-sm"
                  data-id="{{ $entry->id }}"
                  data-row="{{ $row }}"
                  data-employee-name="{{ $entry->employee_name }}"
                  data-employee-number="{{ $entry->employee_number ?? '' }}"
                  data-branch="{{ $entry->branch_name ?? '' }}"
                  data-basic="{{ $entry->basic_salary }}"
                  data-housing="{{ $entry->housing_allowance }}"
                  data-transport="{{ $entry->transport_allowance }}"
                  data-other-allowances="{{ $entry->other_allowances }}"
                  data-overtime="{{ $entry->overtime_amount }}"
                  data-gross="{{ $entry->gross_pay }}"
                  data-paye="{{ $entry->paye }}"
                  data-pension-employee="{{ $entry->pension_employee }}"
                  data-pension-employer="{{ $entry->pension_employer }}"
                  data-loan="{{ $entry->loan_deduction }}"
                  data-advance="{{ $entry->advance_deduction }}"
                  data-other-deductions="{{ $entry->other_deductions }}"
                  data-total-deductions="{{ $entry->total_deductions }}"
                  data-net-pay="{{ $entry->net_pay }}"
                  data-notes="{{ $entry->notes }}"
                  title="View Details">
                <i class="ri-eye-line"></i>
              </a>
              @if($period && in_array($period->status, ['draft', 'processing']))
              <a href="#" class="editEntryBtn btn btn-light text-info btn-sm"
                  data-id="{{ $entry->id }}"
                  data-row="{{ $row }}"
                  data-employee-name="{{ $entry->employee_name }}"
                  data-basic="{{ $entry->basic_salary }}"
                  data-housing="{{ $entry->housing_allowance }}"
                  data-transport="{{ $entry->transport_allowance }}"
                  data-other-allowances="{{ $entry->other_allowances }}"
                  data-overtime="{{ $entry->overtime_amount }}"
                  data-paye="{{ $entry->paye }}"
                  data-pension-employee="{{ $entry->pension_employee }}"
                  data-pension-employer="{{ $entry->pension_employer }}"
                  data-loan="{{ $entry->loan_deduction }}"
                  data-advance="{{ $entry->advance_deduction }}"
                  data-other-deductions="{{ $entry->other_deductions }}"
                  data-notes="{{ $entry->notes }}"
                  title="Edit Entry">
                <i class="ri-edit-box-line"></i>
              </a>
              @endif
              <a href="{{ route('tenant.admin.hr.payroll.wagebill.payslip', ['tenantName' => request()->route('tenantName')]) }}?entry_id={{ $entry->id }}"
                 class="btn btn-light text-success btn-sm" title="Download Payslip">
                <i class="ri-file-download-line"></i>
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


{{-- ════════════════════════════════════════════════════════════════════════
     MODAL 1 — WAGE BILL DOWNLOAD
     Downloads the full wage bill (all columns) in various scopes/formats.
     Routes to wire up tomorrow:
       - tenant.admin.hr.payroll.wagebill.download.payment-list   (Excel/PDF/CSV)
       - tenant.admin.hr.payroll.wagebill.download.all-sectors    (Excel/PDF/CSV)
       - tenant.admin.hr.payroll.wagebill.download.by-category    (Excel/PDF/CSV)
       - tenant.admin.hr.payroll.wagebill.download.branches-grouped (Excel/PDF/CSV)
       - tenant.admin.hr.payroll.wagebill.download.branches-flat  (Excel/PDF/CSV)
       - tenant.admin.hr.payroll.wagebill.download.single-branch  (Excel/PDF/CSV)
     All routes accept: period_id, sector, category_id (optional), branch_id (single-branch only)
════════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="wageDownloadModal" data-bs-backdrop="static" data-bs-keyboard="false"
     tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title">
          <i class="ri-download-2-line"></i>&nbsp; Wage Bill Downloads
          @if($period)
            <span style="font-weight:400;font-size:12px;opacity:.85;margin-left:6px;">
              &mdash; {{ $period->name }}
            </span>
          @endif
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-4">

        @if(!$period)
          {{-- No period selected —— prompt user ──────────────────────── --}}
          <div class="wdl-no-period">
            <i class="ri-calendar-event-line"></i>
            <div>
              <strong>No payroll period selected.</strong><br>
              Please choose a period from the filter bar before downloading.
            </div>
          </div>
        @else

          {{-- ── GROUP A: Payment List ───────────────────────────────── --}}
          <div class="wdl-group-label">
            <i class="ri-bank-line me-1"></i> Payment List
          </div>

          <div class="wdl-section">
            <div class="wdl-section-header">
              <i class="ri-bank-line wdl-hicon"></i>
              <div>
                <div class="wdl-title">Bank Submission Format</div>
                <div class="wdl-sub">
                  Compact list: Employee Name, Net Pay, Bank Name, Account Number —
                  ready to upload to your bank's bulk payment portal.
                </div>
              </div>
            </div>
            <div class="wdl-section-body">
              <div class="row g-2 align-items-center">
                <div class="col-auto">
                  {{-- TODO: href="route('...payment-list', ['period_id'=>$period->id,'sector'=>$savedSector,'format'=>'excel','tenantName'=>...])" --}}
                  <a href="#" class="wdl-btn wdl-btn-excel"><i class="ri-file-excel-line"></i> Excel</a>
                </div>
                <div class="col-auto">
                  {{-- TODO: href="route('...payment-list', [..., 'format'=>'pdf'])" --}}
                  <a href="#" class="wdl-btn wdl-btn-pdf"><i class="ri-file-pdf-line"></i> PDF</a>
                </div>
                <div class="col-auto">
                  {{-- TODO: href="route('...payment-list', [..., 'format'=>'csv'])" --}}
                  <a href="#" class="wdl-btn wdl-btn-csv"><i class="ri-file-text-line"></i> CSV</a>
                </div>
              </div>
            </div>
          </div>

          {{-- ── GROUP B: Full Wage Bill ─────────────────────────────── --}}
          <div class="wdl-group-label" style="margin-top:20px;">
            <i class="ri-file-list-3-line me-1"></i> Full Wage Bill — All Columns
          </div>

          {{-- Row 1: All Sectors Combined + By Category --}}
          <div class="row g-3 mb-3">

            <div class="col-md-6">
              <div class="wdl-section" style="margin-bottom:0;height:100%;">
                <div class="wdl-section-header">
                  <i class="ri-building-4-line wdl-hicon"></i>
                  <div>
                    <div class="wdl-title">All Sectors — Combined</div>
                    <div class="wdl-sub">Every employee across all sectors in a single list.</div>
                  </div>
                </div>
                <div class="wdl-section-body">
                  <div class="row g-2">
                    <div class="col-auto">
                      {{-- TODO: route('...all-sectors', ['period_id'=>$period->id,'format'=>'excel','tenantName'=>...]) --}}
                      <a href="#" class="wdl-btn wdl-btn-excel"><i class="ri-file-excel-line"></i> Excel</a>
                    </div>
                    <div class="col-auto">
                      <a href="#" class="wdl-btn wdl-btn-pdf"><i class="ri-file-pdf-line"></i> PDF</a>
                    </div>
                    <div class="col-auto">
                      <a href="#" class="wdl-btn wdl-btn-csv"><i class="ri-file-text-line"></i> CSV</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="wdl-section" style="margin-bottom:0;height:100%;">
                <div class="wdl-section-header">
                  <i class="ri-filter-line wdl-hicon"></i>
                  <div>
                    <div class="wdl-title">
                      By Category
                      @if($savedCatId && $categories->where('id',(int)$savedCatId)->isNotEmpty())
                        <span class="wdl-scope-badge">
                          <i class="ri-checkbox-circle-line"></i>
                          {{ $categories->where('id',(int)$savedCatId)->first()->category }}
                        </span>
                      @else
                        <span class="wdl-scope-badge" style="background:#f1f5f9;color:#64748b;">
                          All Categories
                        </span>
                      @endif
                    </div>
                    <div class="wdl-sub">Uses the current Category filter selection.</div>
                  </div>
                </div>
                <div class="wdl-section-body">
                  <div class="row g-2">
                    <div class="col-auto">
                      {{-- TODO: route('...by-category', ['period_id'=>$period->id,'sector'=>$savedSector,'category_id'=>$savedCatId,'format'=>'excel','tenantName'=>...]) --}}
                      <a href="#" class="wdl-btn wdl-btn-excel"><i class="ri-file-excel-line"></i> Excel</a>
                    </div>
                    <div class="col-auto">
                      <a href="#" class="wdl-btn wdl-btn-pdf"><i class="ri-file-pdf-line"></i> PDF</a>
                    </div>
                    <div class="col-auto">
                      <a href="#" class="wdl-btn wdl-btn-csv"><i class="ri-file-text-line"></i> CSV</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>{{-- /.row --}}

          {{-- Row 2: Branches Grouped + Branches Flat --}}
          <div class="row g-3 mb-3">

            <div class="col-md-6">
              <div class="wdl-section" style="margin-bottom:0;height:100%;">
                <div class="wdl-section-header">
                  <i class="ri-store-2-line wdl-hicon"></i>
                  <div>
                    <div class="wdl-title">All Branches — Grouped</div>
                    <div class="wdl-sub">Each branch in its own section / sheet.</div>
                  </div>
                </div>
                <div class="wdl-section-body">
                  <div class="row g-2">
                    <div class="col-auto">
                      {{-- TODO: route('...branches-grouped', ['period_id'=>$period->id,'sector'=>$savedSector,'format'=>'excel','tenantName'=>...]) --}}
                      <a href="#" class="wdl-btn wdl-btn-excel"><i class="ri-file-excel-line"></i> Excel</a>
                    </div>
                    <div class="col-auto">
                      <a href="#" class="wdl-btn wdl-btn-pdf"><i class="ri-file-pdf-line"></i> PDF</a>
                    </div>
                    <div class="col-auto">
                      <a href="#" class="wdl-btn wdl-btn-csv"><i class="ri-file-text-line"></i> CSV</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="wdl-section" style="margin-bottom:0;height:100%;">
                <div class="wdl-section-header">
                  <i class="ri-store-line wdl-hicon"></i>
                  <div>
                    <div class="wdl-title">All Branches — Flat List</div>
                    <div class="wdl-sub">Single continuous list across all branches.</div>
                  </div>
                </div>
                <div class="wdl-section-body">
                  <div class="row g-2">
                    <div class="col-auto">
                      {{-- TODO: route('...branches-flat', ['period_id'=>$period->id,'sector'=>$savedSector,'format'=>'excel','tenantName'=>...]) --}}
                      <a href="#" class="wdl-btn wdl-btn-excel"><i class="ri-file-excel-line"></i> Excel</a>
                    </div>
                    <div class="col-auto">
                      <a href="#" class="wdl-btn wdl-btn-pdf"><i class="ri-file-pdf-line"></i> PDF</a>
                    </div>
                    <div class="col-auto">
                      <a href="#" class="wdl-btn wdl-btn-csv"><i class="ri-file-text-line"></i> CSV</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>{{-- /.row --}}

          {{-- Row 3: Single Branch — full width --}}
          <div class="wdl-section" style="margin-bottom:0;">
            <div class="wdl-section-header">
              <i class="ri-map-pin-2-line wdl-hicon"></i>
              <div>
                <div class="wdl-title">Single Branch</div>
                <div class="wdl-sub">Select a specific branch and download its wage bill only.</div>
              </div>
            </div>
            <div class="wdl-section-body">
              <div class="row g-2 align-items-center">
                <div class="col-md-5 col-12">
                  {{-- TODO: on change, update the href of the download buttons below
                       to append &branch_id=<selected value> --}}
                  <select class="wdl-branch-select" id="wdlBranchSelect">
                    <option value="">— Select a branch —</option>
                    @foreach($sectorBranches as $br)
                      <option value="{{ $br->id }}">{{ $br->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-auto">
                  {{-- TODO: route('...single-branch', ['period_id'=>$period->id,'sector'=>$savedSector,'branch_id'=><selected>,'format'=>'excel','tenantName'=>...]) --}}
                  <a href="#" class="wdl-btn wdl-btn-excel" id="wdlSingleBranchExcel"><i class="ri-file-excel-line"></i> Excel</a>
                </div>
                <div class="col-auto">
                  <a href="#" class="wdl-btn wdl-btn-pdf"   id="wdlSingleBranchPdf"><i class="ri-file-pdf-line"></i> PDF</a>
                </div>
                <div class="col-auto">
                  <a href="#" class="wdl-btn wdl-btn-csv"   id="wdlSingleBranchCsv"><i class="ri-file-text-line"></i> CSV</a>
                </div>
              </div>
            </div>
          </div>

        @endif
      </div>{{-- /.modal-body --}}

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>


{{-- ════════════════════════════════════════════════════════════════════════
     MODAL 2 — STATUTORY DEDUCTIONS REPORT
     Columns: Employee Name | Gross Pay | PAYE | Pension (Ee) | Pension (Er) | Total Statutory
     Totals footer row per report.

     Routes to wire up tomorrow:
       - tenant.admin.hr.payroll.statutory.all-categories   (Excel/PDF)
       - tenant.admin.hr.payroll.statutory.by-category      (Excel/PDF)
       - tenant.admin.hr.payroll.statutory.single-branch    (Excel/PDF)
     All routes accept: period_id, sector, category_id (optional), branch_id (single-branch only)

     Controller method notes (tomorrow):
       - Use the same $filteredBranchIds scoping pattern from the view @php block
       - Query payroll_entries JOIN users JOIN branches
         SELECT users.name, gross_pay, paye, pension_employee, pension_employer,
                (paye + pension_employee) as total_statutory
       - Group/order by users.name ASC
       - For the PDF: use the existing DomPDF setup; create a new Blade view at
         tenants.admin.hr.payroll.statutory-deductions-pdf
         with letterhead, period header, the table, and a totals row
       - For Excel: use Laravel Excel (Maatwebsite) with a simple array export
         or a FromQuery export — add a totals row at the end
       - Pension (Er) is informational only — NOT deducted from employee net pay
         but the accountant needs it for the pension fund submission
════════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="statutoryModal" data-bs-backdrop="static" data-bs-keyboard="false"
     tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header mh-orange">
        <h5 class="modal-title mh-title">
          <i class="ri-government-line"></i>&nbsp; Statutory Deductions Report
          @if($period)
            <span style="font-weight:400;font-size:12px;opacity:.85;margin-left:6px;">
              &mdash; {{ $period->name }}
            </span>
          @endif
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body p-4">

        @if(!$period)
          <div class="wdl-no-period">
            <i class="ri-calendar-event-line"></i>
            <div>
              <strong>No payroll period selected.</strong><br>
              Please choose a period from the filter bar before downloading.
            </div>
          </div>
        @else

          {{-- Intro / column legend ─────────────────────────────────── --}}
          <div class="alert alert-light border" style="font-size:12px;padding:10px 14px;margin-bottom:18px;">
            <div style="font-weight:600;color:#1e293b;margin-bottom:6px;">
              <i class="ri-information-line me-1 text-primary"></i> Report columns
            </div>
            <div class="row g-0" style="line-height:1.8;">
              <div class="col-md-6">
                <span style="color:#64748b;">Employee Name</span> &nbsp;·&nbsp;
                <span style="color:#64748b;">Gross Pay</span> &nbsp;·&nbsp;
                <span style="color:#64748b;">PAYE</span>
              </div>
              <div class="col-md-6">
                <span style="color:#64748b;">Pension (Employee)</span> &nbsp;·&nbsp;
                <span style="color:#64748b;">Pension (Employer)</span> &nbsp;·&nbsp;
                <span style="color:#64748b;font-weight:600;">Total Statutory</span>
              </div>
            </div>
            <div style="margin-top:6px;color:#94a3b8;font-size:11px;">
              Totals row included at the bottom of every report. Pension (Employer) is shown for
              pension fund submission — it is not deducted from employee pay.
            </div>
          </div>

          {{-- ── PAYE REPORT ─────────────────────────────────────────── --}}
          <div class="wdl-group-label">
            <i class="ri-money-dollar-circle-line me-1"></i> PAYE — Tax Authority Submission
          </div>

          {{-- Row 1: All Categories + By Category --}}
          <div class="row g-3 mb-3">

            <div class="col-md-6">
              <div class="wdl-section" style="margin-bottom:0;height:100%;">
                <div class="wdl-section-header">
                  <i class="ri-building-4-line wdl-hicon wdl-hicon-orange"></i>
                  <div>
                    <div class="wdl-title">All Categories — Combined</div>
                    <div class="wdl-sub">PAYE for every employee in the sector.</div>
                  </div>
                </div>
                <div class="wdl-section-body">
                  <div class="row g-2">
                    <div class="col-auto">
                      {{-- TODO: route('...statutory.all-categories', ['period_id'=>$period->id,'sector'=>$savedSector,'type'=>'paye','format'=>'excel','tenantName'=>...]) --}}
                      <a href="#" class="wdl-btn wdl-btn-excel"><i class="ri-file-excel-line"></i> Excel</a>
                    </div>
                    <div class="col-auto">
                      {{-- TODO: same but format=>'pdf' — use DomPDF Blade view --}}
                      <a href="#" class="wdl-btn wdl-btn-pdf"><i class="ri-file-pdf-line"></i> PDF</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="wdl-section" style="margin-bottom:0;height:100%;">
                <div class="wdl-section-header">
                  <i class="ri-filter-line wdl-hicon wdl-hicon-orange"></i>
                  <div>
                    <div class="wdl-title">
                      By Category
                      @if($savedCatId && $categories->where('id',(int)$savedCatId)->isNotEmpty())
                        <span class="wdl-scope-badge wdl-scope-badge-orange">
                          <i class="ri-checkbox-circle-line"></i>
                          {{ $categories->where('id',(int)$savedCatId)->first()->category }}
                        </span>
                      @else
                        <span class="wdl-scope-badge" style="background:#f1f5f9;color:#64748b;">
                          All Categories
                        </span>
                      @endif
                    </div>
                    <div class="wdl-sub">PAYE scoped to the current category filter.</div>
                  </div>
                </div>
                <div class="wdl-section-body">
                  <div class="row g-2">
                    <div class="col-auto">
                      {{-- TODO: route('...statutory.by-category', ['period_id'=>$period->id,'sector'=>$savedSector,'category_id'=>$savedCatId,'type'=>'paye','format'=>'excel','tenantName'=>...]) --}}
                      <a href="#" class="wdl-btn wdl-btn-excel"><i class="ri-file-excel-line"></i> Excel</a>
                    </div>
                    <div class="col-auto">
                      <a href="#" class="wdl-btn wdl-btn-pdf"><i class="ri-file-pdf-line"></i> PDF</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>{{-- /.row --}}

          {{-- ── PENSION REPORT ───────────────────────────────────────── --}}
          <div class="wdl-group-label" style="margin-top:20px;">
            <i class="ri-shield-check-line me-1"></i> Pension — Fund Submission
          </div>

          <div class="row g-3 mb-3">

            <div class="col-md-6">
              <div class="wdl-section" style="margin-bottom:0;height:100%;">
                <div class="wdl-section-header">
                  <i class="ri-building-4-line wdl-hicon wdl-hicon-orange"></i>
                  <div>
                    <div class="wdl-title">All Categories — Combined</div>
                    <div class="wdl-sub">
                      Employee + Employer pension for all staff.
                      Includes combined total per employee.
                    </div>
                  </div>
                </div>
                <div class="wdl-section-body">
                  <div class="row g-2">
                    <div class="col-auto">
                      {{-- TODO: route('...statutory.all-categories', [...,'type'=>'pension','format'=>'excel',...]) --}}
                      <a href="#" class="wdl-btn wdl-btn-excel"><i class="ri-file-excel-line"></i> Excel</a>
                    </div>
                    <div class="col-auto">
                      <a href="#" class="wdl-btn wdl-btn-pdf"><i class="ri-file-pdf-line"></i> PDF</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="wdl-section" style="margin-bottom:0;height:100%;">
                <div class="wdl-section-header">
                  <i class="ri-filter-line wdl-hicon wdl-hicon-orange"></i>
                  <div>
                    <div class="wdl-title">
                      By Category
                      @if($savedCatId && $categories->where('id',(int)$savedCatId)->isNotEmpty())
                        <span class="wdl-scope-badge wdl-scope-badge-orange">
                          <i class="ri-checkbox-circle-line"></i>
                          {{ $categories->where('id',(int)$savedCatId)->first()->category }}
                        </span>
                      @else
                        <span class="wdl-scope-badge" style="background:#f1f5f9;color:#64748b;">
                          All Categories
                        </span>
                      @endif
                    </div>
                    <div class="wdl-sub">Pension scoped to the current category filter.</div>
                  </div>
                </div>
                <div class="wdl-section-body">
                  <div class="row g-2">
                    <div class="col-auto">
                      {{-- TODO: route('...statutory.by-category', [...,'type'=>'pension','format'=>'excel',...]) --}}
                      <a href="#" class="wdl-btn wdl-btn-excel"><i class="ri-file-excel-line"></i> Excel</a>
                    </div>
                    <div class="col-auto">
                      <a href="#" class="wdl-btn wdl-btn-pdf"><i class="ri-file-pdf-line"></i> PDF</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>{{-- /.row --}}

          {{-- ── COMBINED STATUTORY (PAYE + Pension together) ────────── --}}
          <div class="wdl-group-label" style="margin-top:20px;">
            <i class="ri-file-chart-line me-1"></i> Full Statutory Summary — PAYE &amp; Pension Combined
          </div>

          <div class="row g-3">

            <div class="col-md-6">
              <div class="wdl-section" style="margin-bottom:0;height:100%;">
                <div class="wdl-section-header">
                  <i class="ri-building-4-line wdl-hicon wdl-hicon-orange"></i>
                  <div>
                    <div class="wdl-title">All Categories — Combined</div>
                    <div class="wdl-sub">
                      One report: Gross, PAYE, Pension (Ee), Pension (Er), Total Statutory.
                    </div>
                  </div>
                </div>
                <div class="wdl-section-body">
                  <div class="row g-2">
                    <div class="col-auto">
                      {{-- TODO: route('...statutory.all-categories', [...,'type'=>'combined','format'=>'excel',...]) --}}
                      <a href="#" class="wdl-btn wdl-btn-excel"><i class="ri-file-excel-line"></i> Excel</a>
                    </div>
                    <div class="col-auto">
                      <a href="#" class="wdl-btn wdl-btn-pdf"><i class="ri-file-pdf-line"></i> PDF</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <div class="wdl-section" style="margin-bottom:0;height:100%;">
                <div class="wdl-section-header">
                  <i class="ri-filter-line wdl-hicon wdl-hicon-orange"></i>
                  <div>
                    <div class="wdl-title">
                      By Category
                      @if($savedCatId && $categories->where('id',(int)$savedCatId)->isNotEmpty())
                        <span class="wdl-scope-badge wdl-scope-badge-orange">
                          <i class="ri-checkbox-circle-line"></i>
                          {{ $categories->where('id',(int)$savedCatId)->first()->category }}
                        </span>
                      @else
                        <span class="wdl-scope-badge" style="background:#f1f5f9;color:#64748b;">
                          All Categories
                        </span>
                      @endif
                    </div>
                    <div class="wdl-sub">Combined statutory scoped to the current category filter.</div>
                  </div>
                </div>
                <div class="wdl-section-body">
                  <div class="row g-2">
                    <div class="col-auto">
                      {{-- TODO: route('...statutory.by-category', [...,'type'=>'combined','format'=>'excel',...]) --}}
                      <a href="#" class="wdl-btn wdl-btn-excel"><i class="ri-file-excel-line"></i> Excel</a>
                    </div>
                    <div class="col-auto">
                      <a href="#" class="wdl-btn wdl-btn-pdf"><i class="ri-file-pdf-line"></i> PDF</a>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>{{-- /.row --}}

          {{-- Single Branch — full width ──────────────────────────────── --}}
          <div class="wdl-group-label" style="margin-top:20px;">
            <i class="ri-map-pin-2-line me-1"></i> Single Branch
          </div>

          <div class="wdl-section" style="margin-bottom:0;">
            <div class="wdl-section-header">
              <i class="ri-map-pin-2-line wdl-hicon wdl-hicon-orange"></i>
              <div>
                <div class="wdl-title">Statutory Deductions for One Branch</div>
                <div class="wdl-sub">
                  Select a branch, then choose PAYE-only, Pension-only, or the full combined report.
                </div>
              </div>
            </div>
            <div class="wdl-section-body">
              <div class="row g-2 align-items-center flex-wrap">
                {{-- Branch picker --}}
                <div class="col-md-4 col-12">
                  {{-- TODO: JS — on change, update all six download button hrefs below
                       to append &branch_id=<selected value> --}}
                  <select class="wdl-branch-select" id="statBranchSelect">
                    <option value="">— Select a branch —</option>
                    @foreach($sectorBranches as $br)
                      <option value="{{ $br->id }}">{{ $br->name }}</option>
                    @endforeach
                  </select>
                </div>
                {{-- PAYE --}}
                <div class="col-auto">
                  <span style="font-size:10px;font-weight:600;color:#94a3b8;text-transform:uppercase;display:block;margin-bottom:3px;">PAYE</span>
                  <div class="d-flex gap-1">
                    {{-- TODO: route('...statutory.single-branch', ['period_id'=>$period->id,'sector'=>$savedSector,'branch_id'=><sel>,'type'=>'paye','format'=>'excel','tenantName'=>...]) --}}
                    <a href="#" class="wdl-btn wdl-btn-excel" id="statBranchPayeExcel"><i class="ri-file-excel-line"></i> Excel</a>
                    <a href="#" class="wdl-btn wdl-btn-pdf"   id="statBranchPayePdf"><i class="ri-file-pdf-line"></i> PDF</a>
                  </div>
                </div>
                {{-- Pension --}}
                <div class="col-auto">
                  <span style="font-size:10px;font-weight:600;color:#94a3b8;text-transform:uppercase;display:block;margin-bottom:3px;">Pension</span>
                  <div class="d-flex gap-1">
                    {{-- TODO: route('...statutory.single-branch', [...,'type'=>'pension',...]) --}}
                    <a href="#" class="wdl-btn wdl-btn-excel" id="statBranchPensionExcel"><i class="ri-file-excel-line"></i> Excel</a>
                    <a href="#" class="wdl-btn wdl-btn-pdf"   id="statBranchPensionPdf"><i class="ri-file-pdf-line"></i> PDF</a>
                  </div>
                </div>
                {{-- Combined --}}
                <div class="col-auto">
                  <span style="font-size:10px;font-weight:600;color:#94a3b8;text-transform:uppercase;display:block;margin-bottom:3px;">Combined</span>
                  <div class="d-flex gap-1">
                    {{-- TODO: route('...statutory.single-branch', [...,'type'=>'combined',...]) --}}
                    <a href="#" class="wdl-btn wdl-btn-excel" id="statBranchCombinedExcel"><i class="ri-file-excel-line"></i> Excel</a>
                    <a href="#" class="wdl-btn wdl-btn-pdf"   id="statBranchCombinedPdf"><i class="ri-file-pdf-line"></i> PDF</a>
                  </div>
                </div>
              </div>
            </div>
          </div>

        @endif
      </div>{{-- /.modal-body --}}

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>


{{-- ═══════════════════════════════════════════════════════════════════════
     DATATABLE EXPORT MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false"
     tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Export Table</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <p class="mb-2">Export the currently visible table data.</p>
      <div class="buttons"></div>
    </div>
  </div></div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════
     INFO MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false"
     tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header mh-blue">
      <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Wage Bill</h5>
      <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="font-size:13px;">
      <p>The <strong>Wage Bill</strong> shows one payroll entry per employee for the selected payroll period, scoped strictly to the chosen sector.</p>
      <p class="mb-1"><strong>Filters</strong></p>
      <ul class="mb-3" style="padding-left:18px;">
        <li class="mb-1"><strong>Sector</strong> (header) — Primary scope. Changing it clears all other filters.</li>
        <li class="mb-1"><strong>Category</strong> — Narrow to a branch category within the sector.</li>
        <li class="mb-1"><strong>Payroll Period</strong> — Choose which pay run to view.</li>
      </ul>
      <p class="mb-1"><strong>Header buttons</strong></p>
      <ul class="mb-3" style="padding-left:18px;">
        <li class="mb-1"><i class="ri-government-line" style="color:#d97706"></i> <strong>Statutory Deductions</strong> — Download PAYE and pension summaries for the tax authority and pension fund.</li>
        <li class="mb-1"><i class="ri-download-2-line text-success"></i> <strong>Wage Bill Downloads</strong> — Full wage bill in various scopes and formats.</li>
        <li class="mb-1"><i class="ri-table-line text-primary"></i> <strong>Export Table</strong> — Export what's currently visible in the DataTable.</li>
      </ul>
      <p class="mb-1"><strong>Row actions</strong></p>
      <ul class="mb-0" style="padding-left:18px;">
        <li class="mb-1"><i class="ri-eye-line text-primary"></i> <strong>View</strong> — Full breakdown.</li>
        <li class="mb-1"><i class="ri-edit-box-line text-info"></i> <strong>Edit</strong> — Draft / Processing only.</li>
        <li><i class="ri-file-download-line text-success"></i> <strong>Payslip</strong> — PDF payslip.</li>
      </ul>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
    </div>
  </div></div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════
     VIEW ENTRY MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="viewEntryModal" data-bs-backdrop="static" data-bs-keyboard="false"
     tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title">
          <i class="ri-bill-line"></i> Entry Detail &mdash; <span id="viewEmployeeNameTitle"></span>
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">

        <div class="modal-section-title">Employee</div>
        <div class="row g-2 mb-2">
          <div class="col-md-4 col-6"><div class="entry-field-group"><div class="entry-field-label">Name</div><div class="entry-field-value" id="vEmployeeName">—</div></div></div>
          <div class="col-md-4 col-6"><div class="entry-field-group"><div class="entry-field-label">Employee #</div><div class="entry-field-value" id="vEmployeeNumber">—</div></div></div>
          <div class="col-md-4 col-6"><div class="entry-field-group"><div class="entry-field-label">Branch</div><div class="entry-field-value" id="vBranch">—</div></div></div>
        </div>

        <div class="modal-section-title">Earnings</div>
        <div class="row g-2 mb-2">
          <div class="col-md-4 col-6"><div class="entry-field-group"><div class="entry-field-label">Basic Salary</div><div class="entry-field-value" id="vBasic">0.00</div></div></div>
          <div class="col-md-4 col-6"><div class="entry-field-group"><div class="entry-field-label">Housing Allowance</div><div class="entry-field-value" id="vHousing">0.00</div></div></div>
          <div class="col-md-4 col-6"><div class="entry-field-group"><div class="entry-field-label">Transport Allowance</div><div class="entry-field-value" id="vTransport">0.00</div></div></div>
          <div class="col-md-4 col-6"><div class="entry-field-group"><div class="entry-field-label">Other Allowances</div><div class="entry-field-value" id="vOtherAllowances">0.00</div></div></div>
          <div class="col-md-4 col-6"><div class="entry-field-group"><div class="entry-field-label">Overtime</div><div class="entry-field-value" id="vOvertime">0.00</div></div></div>
          <div class="col-md-4 col-6"><div class="entry-field-group" style="background:#e8f5e9;"><div class="entry-field-label">Gross Pay</div><div class="entry-field-value text-success" id="vGross">0.00</div></div></div>
        </div>

        <div class="modal-section-title">Deductions</div>
        <div class="row g-2 mb-2">
          <div class="col-md-4 col-6"><div class="entry-field-group"><div class="entry-field-label">PAYE</div><div class="entry-field-value" id="vPaye">0.00</div></div></div>
          <div class="col-md-4 col-6"><div class="entry-field-group"><div class="entry-field-label">Pension (Employee)</div><div class="entry-field-value" id="vPensionEe">0.00</div></div></div>
          <div class="col-md-4 col-6"><div class="entry-field-group"><div class="entry-field-label">Pension (Employer)</div><div class="entry-field-value" id="vPensionEr">0.00</div></div></div>
          <div class="col-md-4 col-6"><div class="entry-field-group"><div class="entry-field-label">Loan Deduction</div><div class="entry-field-value" id="vLoan">0.00</div></div></div>
          <div class="col-md-4 col-6"><div class="entry-field-group"><div class="entry-field-label">Advance Deduction</div><div class="entry-field-value" id="vAdvance">0.00</div></div></div>
          <div class="col-md-4 col-6"><div class="entry-field-group"><div class="entry-field-label">Other Deductions</div><div class="entry-field-value" id="vOtherDeductions">0.00</div></div></div>
        </div>

        <div class="modal-section-title">Summary</div>
        <div class="row g-2 mb-2">
          <div class="col-md-4 col-6"><div class="entry-field-group" style="background:#e8f5e9;"><div class="entry-field-label">Gross Pay</div><div class="entry-field-value text-success" id="vGross2">0.00</div></div></div>
          <div class="col-md-4 col-6"><div class="entry-field-group" style="background:#fdecea;"><div class="entry-field-label">Total Deductions</div><div class="entry-field-value text-danger" id="vTotalDeductions">0.00</div></div></div>
          <div class="col-md-4 col-6"><div class="entry-field-group" style="background:#e8eaf6;"><div class="entry-field-label">Net Pay</div><div class="entry-field-value text-primary" id="vNetPay">0.00</div></div></div>
        </div>

        <div class="modal-section-title">Notes</div>
        <div class="entry-field-group">
          <div class="entry-field-value" id="vNotes" style="font-size:13px;font-weight:400;">—</div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════
     EDIT ENTRY MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="editEntryModal" data-bs-backdrop="static" data-bs-keyboard="false"
     tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title">
          <i class="ri-edit-box-line"></i> Edit Entry &mdash; <span id="editEmployeeNameLabel"></span>
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form action="#" method="post" id="editEntryForm">
          @csrf
          <input type="hidden" name="id"  id="editEntryId">
          <input type="hidden" name="row" id="editEntryRow">

          <div class="modal-section-title">Earnings</div>
          <div class="row g-3">
            <div class="col-md-4 col-6">
              <label style="font-size:13px">Basic Salary</label>
              <input type="number" step="0.01" min="0" class="form-control calcField" name="basic_salary" id="eBasic">
            </div>
            <div class="col-md-4 col-6">
              <label style="font-size:13px">Housing Allowance</label>
              <input type="number" step="0.01" min="0" class="form-control calcField" name="housing_allowance" id="eHousing">
            </div>
            <div class="col-md-4 col-6">
              <label style="font-size:13px">Transport Allowance</label>
              <input type="number" step="0.01" min="0" class="form-control calcField" name="transport_allowance" id="eTransport">
            </div>
            <div class="col-md-4 col-6">
              <label style="font-size:13px">Other Allowances</label>
              <input type="number" step="0.01" min="0" class="form-control calcField" name="other_allowances" id="eOtherAllowances">
            </div>
            <div class="col-md-4 col-6">
              <label style="font-size:13px">Overtime</label>
              <input type="number" step="0.01" min="0" class="form-control calcField" name="overtime_amount" id="eOvertime">
            </div>
          </div>

          <div class="modal-section-title">Deductions</div>
          <div class="row g-3">
            <div class="col-md-4 col-6">
              <label style="font-size:13px">PAYE</label>
              <input type="number" step="0.01" min="0" class="form-control calcField" name="paye" id="ePaye">
            </div>
            <div class="col-md-4 col-6">
              <label style="font-size:13px">Pension (Employee)</label>
              <input type="number" step="0.01" min="0" class="form-control calcField" name="pension_employee" id="ePensionEe">
            </div>
            <div class="col-md-4 col-6">
              <label style="font-size:13px">Pension (Employer)</label>
              <input type="number" step="0.01" min="0" class="form-control" name="pension_employer" id="ePensionEr">
            </div>
            <div class="col-md-4 col-6">
              <label style="font-size:13px">Loan Deduction</label>
              <input type="number" step="0.01" min="0" class="form-control calcField" name="loan_deduction" id="eLoan">
            </div>
            <div class="col-md-4 col-6">
              <label style="font-size:13px">Advance Deduction</label>
              <input type="number" step="0.01" min="0" class="form-control calcField" name="advance_deduction" id="eAdvance">
            </div>
            <div class="col-md-4 col-6">
              <label style="font-size:13px">Other Deductions</label>
              <input type="number" step="0.01" min="0" class="form-control calcField" name="other_deductions" id="eOtherDeductions">
            </div>
          </div>

          <div class="row g-2 mt-3 mb-3">
            <div class="col-md-4 col-6"><div class="entry-field-group" style="background:#e8f5e9;"><div class="entry-field-label">Gross Pay (computed)</div><div class="entry-field-value text-success" id="previewGross">0.00</div></div></div>
            <div class="col-md-4 col-6"><div class="entry-field-group" style="background:#fdecea;"><div class="entry-field-label">Total Deductions (computed)</div><div class="entry-field-value text-danger" id="previewTotalDed">0.00</div></div></div>
            <div class="col-md-4 col-6"><div class="entry-field-group" style="background:#e8eaf6;"><div class="entry-field-label">Net Pay (computed)</div><div class="entry-field-value text-primary" id="previewNetPay">0.00</div></div></div>
          </div>

          <div class="modal-section-title">Notes</div>
          <div class="mb-3">
            <textarea class="form-control" name="notes" id="eNotes" rows="2" placeholder="Optional notes…"></textarea>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-2">
            <a href="#" class="btn btn-secondary" id="cancelEditEntryBtn">Cancel</a>
            <a href="#" class="btn btn-primary"   id="submitEditEntryBtn">
              <i class="ri-save-line me-1"></i> Save Entry
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {

    toastr.options = { closeButton:true, progressBar:true, showMethod:'slideDown', timeOut:5000 };

    // ── DataTable ─────────────────────────────────────────────────────────
    @if($period && $entries->isNotEmpty())
    var maintableTitle = '{{ "Wage Bill - " . addslashes($period->name) }}';
    var table = $('#maintable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
        order: [[0, 'asc']],
        fixedColumns: { left: 1 },
        scrollX: true,
        columnDefs: [{ targets: [17], orderable: false }],
        buttons: [
            { extend: 'excelHtml5', title: maintableTitle, exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'csvHtml5',   title: maintableTitle, exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'pdfHtml5',   title: maintableTitle, exportOptions: { columns: ':visible:not(:last-child)' },
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

    // ── Modal instances ───────────────────────────────────────────────────
    var viewModal         = new bootstrap.Modal('#viewEntryModal');
    var editEntryModal    = new bootstrap.Modal('#editEntryModal');
    var wageDownloadModal = new bootstrap.Modal('#wageDownloadModal');
    var statutoryModal    = new bootstrap.Modal('#statutoryModal');

    // ── Header button bindings ────────────────────────────────────────────
    $('#infoBtn').on('click',            function(e) { e.preventDefault(); $('#infoModal').modal('show'); });
    $('#tableButtonsBtn').on('click',    function(e) { e.preventDefault(); $('#buttonsModal').modal('show'); });
    $('#wageDownloadBtn').on('click',    function(e) { e.preventDefault(); wageDownloadModal.show(); });
    $('#statutoryBtn').on('click',       function(e) { e.preventDefault(); statutoryModal.show(); });
    $('#cancelEditEntryBtn').on('click', function(e) { e.preventDefault(); editEntryModal.hide(); });

    // ── Number formatter ──────────────────────────────────────────────────
    function fmt(n) {
        return parseFloat(n || 0).toLocaleString('en', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    }

    // ── VIEW entry ────────────────────────────────────────────────────────
    $('#tbody').on('click', '.viewEntryBtn', function() {
        var d = $(this).data();
        $('#viewEmployeeNameTitle').text(d.employeeName);
        $('#vEmployeeName').text(d.employeeName);
        $('#vEmployeeNumber').text(d.employeeNumber || '—');
        $('#vBranch').text(d.branch || '—');
        $('#vBasic').text(fmt(d.basic));
        $('#vHousing').text(fmt(d.housing));
        $('#vTransport').text(fmt(d.transport));
        $('#vOtherAllowances').text(fmt(d.otherAllowances));
        $('#vOvertime').text(fmt(d.overtime));
        $('#vGross').text(fmt(d.gross));
        $('#vGross2').text(fmt(d.gross));
        $('#vPaye').text(fmt(d.paye));
        $('#vPensionEe').text(fmt(d.pensionEmployee));
        $('#vPensionEr').text(fmt(d.pensionEmployer));
        $('#vLoan').text(fmt(d.loan));
        $('#vAdvance').text(fmt(d.advance));
        $('#vOtherDeductions').text(fmt(d.otherDeductions));
        $('#vTotalDeductions').text(fmt(d.totalDeductions));
        $('#vNetPay').text(fmt(d.netPay));
        $('#vNotes').text(d.notes || '—');
        viewModal.show();
    });

    // ── EDIT entry ────────────────────────────────────────────────────────
    $('#tbody').on('click', '.editEntryBtn', function() {
        var d = $(this).data();
        $('#editEntryId').val(d.id);
        $('#editEntryRow').val(d.row);
        $('#editEmployeeNameLabel').text(d.employeeName);
        $('#eBasic').val(d.basic);
        $('#eHousing').val(d.housing);
        $('#eTransport').val(d.transport);
        $('#eOtherAllowances').val(d.otherAllowances);
        $('#eOvertime').val(d.overtime);
        $('#ePaye').val(d.paye);
        $('#ePensionEe').val(d.pensionEmployee);
        $('#ePensionEr').val(d.pensionEmployer);
        $('#eLoan').val(d.loan);
        $('#eAdvance').val(d.advance);
        $('#eOtherDeductions').val(d.otherDeductions);
        $('#eNotes').val(d.notes);
        refreshPreview();
        editEntryModal.show();
    });

    // ── Live preview in edit modal ────────────────────────────────────────
    function refreshPreview() {
        var gross = parseFloat($('#eBasic').val()           || 0)
                  + parseFloat($('#eHousing').val()         || 0)
                  + parseFloat($('#eTransport').val()       || 0)
                  + parseFloat($('#eOtherAllowances').val() || 0)
                  + parseFloat($('#eOvertime').val()        || 0);

        var totalDed = parseFloat($('#ePaye').val()            || 0)
                     + parseFloat($('#ePensionEe').val()       || 0)
                     + parseFloat($('#eLoan').val()            || 0)
                     + parseFloat($('#eAdvance').val()         || 0)
                     + parseFloat($('#eOtherDeductions').val() || 0);

        var net = gross - totalDed;
        $('#previewGross').text(fmt(gross));
        $('#previewTotalDed').text(fmt(totalDed));
        $('#previewNetPay').text(fmt(net < 0 ? 0 : net));
    }

    $('#editEntryForm .calcField').on('input', refreshPreview);

    // ── SAVE entry ────────────────────────────────────────────────────────
    $('#submitEditEntryBtn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this).prop('disabled', true);
        var row  = $('#editEntryRow').val();

        $.ajax({
            type: 'POST',
            url:  '{{ route("tenant.admin.hr.payroll.wagebill.entry.update", ["tenantName" => request()->route("tenantName")]) }}',
            data: $('#editEntryForm').serialize(),
            timeout: 60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); $btn.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Saved');
                    var e    = data.entry;
                    var $row = $('#' + row);
                    // cols: 0=Employee 1=Branch 2=BankName 3=AccountNo 4=Basic 5=Housing
                    //       6=Transport 7=OtherAllow 8=Overtime 9=Gross 10=PAYE
                    //       11=PensionEe 12=Loan 13=Advance 14=OtherDed 15=TotalDed 16=NetPay 17=Action
                    $row.find('td').eq(4).text(fmt(e.basic_salary));
                    $row.find('td').eq(5).text(fmt(e.housing_allowance));
                    $row.find('td').eq(6).text(fmt(e.transport_allowance));
                    $row.find('td').eq(7).text(fmt(e.other_allowances));
                    $row.find('td').eq(8).text(fmt(e.overtime_amount));
                    $row.find('td').eq(9).html('<strong>' + fmt(e.gross_pay) + '</strong>');
                    $row.find('td').eq(10).text(fmt(e.paye));
                    $row.find('td').eq(11).text(fmt(e.pension_employee));
                    $row.find('td').eq(12).text(fmt(e.loan_deduction));
                    $row.find('td').eq(13).text(fmt(e.advance_deduction));
                    $row.find('td').eq(14).text(fmt(e.other_deductions));
                    $row.find('td').eq(15).html('<strong>' + fmt(e.total_deductions) + '</strong>');
                    $row.find('td').eq(16).html('<strong class="text-primary">' + fmt(e.net_pay) + '</strong>');

                    $row.find('.viewEntryBtn, .editEntryBtn')
                        .data('basic',            e.basic_salary)
                        .data('housing',          e.housing_allowance)
                        .data('transport',        e.transport_allowance)
                        .data('other-allowances', e.other_allowances)
                        .data('overtime',         e.overtime_amount)
                        .data('gross',            e.gross_pay)
                        .data('paye',             e.paye)
                        .data('pension-employee', e.pension_employee)
                        .data('pension-employer', e.pension_employer)
                        .data('loan',             e.loan_deduction)
                        .data('advance',          e.advance_deduction)
                        .data('other-deductions', e.other_deductions)
                        .data('total-deductions', e.total_deductions)
                        .data('net-pay',          e.net_pay)
                        .data('notes',            e.notes);

                    editEntryModal.hide();

                } else if (data.status === 422) {
                    var msg = '';
                    $.each(data.errors, function(k, v) { msg += v + '\n'; });
                    toastr.error(msg, 'Validation');
                } else {
                    toastr.error(data.error || 'Failed', 'Error');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var msg = '';
                    $.each(xhr.responseJSON.errors, function(k, v) { msg += v + '\n'; });
                    toastr.error(msg, 'Validation');
                } else {
                    toastr.error('Server error', 'Error');
                }
            }
        });
    });

});
</script>
@endsection
