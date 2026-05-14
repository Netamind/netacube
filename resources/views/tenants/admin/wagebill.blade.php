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

    /* ── SECTOR LIST ──────────────────────────────────────────────────── */
    $sectors = DB::connection('tenant')
        ->table('sectors')
        ->orderBy('sector')
        ->pluck('sector');

    if (!$savedSector && $sectors->isNotEmpty()) {
        $savedSector = $sectors->first();
    }

    /* ── BRANCH IDs ───────────────────────────────────────────────────── */
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

    /* ── EMPLOYEE IDs ─────────────────────────────────────────────────── */
    $filteredEmployeeIds = collect();
    if ($filteredBranchIds->isNotEmpty()) {
        $filteredEmployeeIds = DB::connection('tenant')
            ->table('users')
            ->whereIn('branch', $filteredBranchIds)
            ->pluck('id');
    }

    /* ── CATEGORY LIST ────────────────────────────────────────────────── */
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

    /* ── PERIOD LIST ──────────────────────────────────────────────────── */
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

    /* ── WAGE BILL ENTRIES ────────────────────────────────────────────── */
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
                ->orderBy('branches.name', 'asc')
                ->orderBy('users.name',    'asc')
                ->get();
        }
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
  border-top-left-radius: 10px; border-top-right-radius: 10px;
}
.card-body  { padding: 0 1.5rem 1.5rem 1.5rem !important; }
.card       { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; }
.card-header h4 { color:#fff; font-weight:600; margin-bottom:0; display:flex; align-items:center; }
.card-header h4 i { margin-right: 0.25rem; }
.card-header .btn-light {
  height:28px; padding:0 10px;
  display:flex; align-items:center; justify-content:center; line-height:1;
}
.card-header .btn-light:hover { background-color:#f8f9fa; transition:background-color 0.2s ease-in-out; }

/* ── Fixed-header floating row (mirrors employees view) ─────────────────── */
table.dataTable.fixedHeader-floating,
table.dataTable.fixedHeader-locked  { background: #fff !important; border-bottom: none !important; }
table.dataTable thead th.fixedHeader-floating { background: #e2e2e9 !important; }

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
@media (max-width: 767px) {
  .card-filter { flex-direction:column; align-items:stretch; padding:10px 1rem; gap:8px; }
  .card-filter select { max-width:100%; min-width:0; width:100%; }
  .filter-divider { display:none; }
  .card-filter .ms-auto { margin-left:0 !important; flex-direction:row; flex-wrap:wrap; gap:6px; }
  .card-filter .ms-auto .btn { flex:1; text-align:center; }
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
.meta-group { display:flex; align-items:center; flex-wrap:wrap; gap:0; }
.meta-item  { display:flex; flex-direction:column; padding:2px 20px 2px 0; }
.meta-item:last-child { padding-right:0; }
.meta-label { font-size:10px; text-transform:uppercase; letter-spacing:.05em; color:#6c757d; font-weight:600; white-space:nowrap; }
.meta-value { font-weight:600; color:#212529; white-space:nowrap; }
.meta-divider { width:2px; height:36px; background:#e2e6f0; margin:0 20px; flex-shrink:0; }
/* Finance summary sub-groups */
.meta-fin-group { display:flex; flex-direction:column; padding:0 20px; }
.meta-fin-group + .meta-fin-group { border-left:1px dashed #d6daf0; }
.meta-fin-group:last-child { padding-right:0; }
.meta-fin-group-label { font-size:9px; text-transform:uppercase; letter-spacing:.07em; color:#9ca3af; font-weight:700; margin-bottom:4px; }
.meta-fin-items { display:flex; gap:20px; }
.meta-fin-item  { display:flex; flex-direction:column; }
.meta-fin-item .fin-label { font-size:10px; color:#6c757d; font-weight:600; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
.meta-fin-item .fin-value { font-size:13px; font-weight:700; white-space:nowrap; }

/* ── Period meta strip — MOBILE ─────────────────────────────────────────── */
@media (max-width: 767px) {
  .period-meta-strip {
    flex-direction: column;
    align-items: stretch;
    padding: 12px 1rem;
    gap: 0;
  }
  /* Period identity: 3-up grid */
  .meta-group {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0;
    width: 100%;
  }
  .meta-item {
    padding: 6px 10px;
    border-bottom: 1px solid #e9ecef;
    border-right: 1px solid #e9ecef;
  }
  .meta-item:nth-child(3n) { border-right: none; }
  .meta-item:last-child    { border-bottom: none; }
  /* Vertical divider becomes horizontal rule */
  .meta-divider {
    width: 100%; height: 2px;
    background: #e2e6f0;
    margin: 10px 0;
  }
  /* Finance groups stack vertically, each full-width */
  .meta-fin-group {
    padding: 8px 0;
    border-left: none !important;
    border-top: 1px dashed #d6daf0;
    width: 100%;
  }
  .meta-fin-group:first-of-type { border-top: none; }
  .meta-fin-group-label {
    margin-bottom: 6px;
    font-size: 10px;
  }
  /* Finance items: wrap into grid */
  .meta-fin-items {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 6px 10px;
    flex-wrap: wrap;
  }
  .meta-fin-item .fin-label { font-size: 10px; }
  .meta-fin-item .fin-value { font-size: 13px; }
}

/* ── Locked banner ──────────────────────────────────────────────────────── */
.period-locked-banner {
  background:#fff3cd; border:1px solid #ffc107; border-radius:6px;
  padding:7px 14px; font-size:12px; margin:10px 1.5rem 0;
  display:flex; align-items:center; gap:7px;
}
@media (max-width: 767px) {
  .period-locked-banner { margin:8px 1rem 0; font-size:11px; }
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

/* ── Allowances breakdown chip in view modal ────────────────────────────── */
.allow-breakdown {
  background:#f1f5fb; border:1px solid #d6daf0; border-radius:8px;
  padding:10px 14px; font-size:12px;
}
.allow-breakdown .allow-row {
  display:flex; justify-content:space-between; align-items:center;
  padding:3px 0; border-bottom:1px dashed #e2e8f0;
}
.allow-breakdown .allow-row:last-child { border-bottom:none; }
.allow-breakdown .allow-label { color:#475569; }
.allow-breakdown .allow-value { font-weight:600; color:#1e293b; }
.allow-breakdown .allow-total-row {
  display:flex; justify-content:space-between; align-items:center;
  padding:5px 0 0; margin-top:4px; font-weight:700;
  border-top:2px solid #c8d0ed;
}
.allow-breakdown .allow-total-label { color:#4B5EBD; font-size:13px; }
.allow-breakdown .allow-total-value { color:#4B5EBD; font-size:13px; }

/* ── Modal headers ──────────────────────────────────────────────────────── */
.mh-blue   { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-orange { background:linear-gradient(135deg,#d97706,#f59e0b); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-title  { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close  { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }

/* ── Download modal components ──────────────────────────────────────────── */
.wdl-section {
  border: 1px solid #e2e8f0; border-radius: 10px;
  overflow: hidden; margin-bottom: 14px;
}
.wdl-section:last-child { margin-bottom: 0; }
.wdl-section-header {
  background: #f1f5fb; padding: 10px 16px;
  display: flex; align-items: flex-start; gap: 10px;
  border-bottom: 1px solid #e2e8f0;
}
.wdl-section-header .wdl-hicon        { font-size:17px; color:#4B5EBD; margin-top:1px; flex-shrink:0; }
.wdl-section-header .wdl-hicon-orange { color:#d97706; }
.wdl-section-header .wdl-title { font-size:13px; font-weight:600; color:#1e293b; line-height:1.3; }
.wdl-section-header .wdl-sub   { font-size:11px; color:#64748b; margin-top:2px; line-height:1.4; }
.wdl-section-body { padding:12px 16px; background:#fff; }
.wdl-btn {
  display:inline-flex; align-items:center; gap:5px;
  padding:5px 13px; border-radius:6px; font-size:12px; font-weight:500;
  text-decoration:none; border:1px solid;
  transition:background .15s, color .15s; cursor:pointer;
}
.wdl-btn-excel { border-color:#198754; color:#198754; }
.wdl-btn-excel:hover { background:#198754; color:#fff; }
.wdl-btn-pdf   { border-color:#dc3545; color:#dc3545; }
.wdl-btn-pdf:hover   { background:#dc3545; color:#fff; }
.wdl-no-period {
  background:#fff8e1; border:1px solid #ffc107; border-radius:8px;
  padding:14px 18px; font-size:13px; color:#78350f;
  display:flex; align-items:center; gap:10px;
}
.wdl-no-period i { font-size:20px; color:#f59e0b; flex-shrink:0; }
.wdl-scope-bar {
  background:#f1f5fb; border:1px solid #d6daf0; border-radius:8px;
  padding:9px 14px; font-size:12px; color:#374151;
  display:flex; align-items:center; gap:6px; margin-bottom:16px;
}
.wdl-scope-bar i { color:#4B5EBD; font-size:14px; flex-shrink:0; }
.wdl-scope-bar strong { color:#1e293b; }
</style>

<div class="progress" id="progressBar" role="progressbar"
     style="height:8px;transform:rotate(180deg);display:none">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

  {{-- ══ CARD HEADER ══ --}}
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0" style="gap:8px;">
      <i class="ri-bill-line" style="flex-shrink:0;"></i>
      @if($sectors->isEmpty())
        <span style="font-size:18px;font-weight:600;opacity:0.75;">— No Sectors Configured —</span>
      @else
        <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
              id="headerSectorForm" style="margin:0;display:inline;">
          @csrf
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
    <div class="d-flex align-items-center" style="gap:4px;">
      <a href="#" class="btn btn-light fs-16 mx-1" id="statutoryBtn"
         style="color:#d97706;" title="Statutory Deductions">
        <i class="ri-government-line"></i>
      </a>
      <a href="#" class="btn btn-light text-success fs-16 mx-1" id="wageDownloadBtn"
         title="Download Wage Bill">
        <i class="ri-download-2-line"></i>
      </a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn"
         title="Export Table">
        <i class="ri-table-line"></i>
      </a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn"
         title="Info">
        <i class="ri-information-line"></i>
      </a>
    </div>
  </div>

  {{-- ══ FILTER BAR ══ --}}
  <div class="card-filter">
    <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
          id="filterCatForm" style="display:contents;">
      @csrf
      <input type="hidden" name="user_id"            value="{{ Auth::id() }}">
      <input type="hidden" name="sector"             value="{{ $savedSector }}">
      <input type="hidden" name="wagebill_period_id" value="">
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

  {{-- ══ PERIOD META STRIP ══ --}}
  @if($period)
  @php
    $totalBasic      = $entries->sum('basic_salary');
    $totalAllowances = $entries->sum('gross_pay') - $totalBasic;
    $totalGross      = $entries->sum('gross_pay');
    $totalPaye       = $entries->sum('paye');
    $totalPensionEe  = $entries->sum('pension_employee');
    $totalLoan       = $entries->sum('loan_deduction');
    $totalAdvance    = $entries->sum('advance_deduction');
    $totalOtherDed   = $entries->sum('other_deductions');
    $totalDeductions = $entries->sum('total_deductions');
    $totalNet        = $entries->sum('net_pay');
  @endphp
  <div class="period-meta-strip">

    {{-- ── Period identity ── --}}
    <div class="meta-group">
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
    </div>

    <div class="meta-divider"></div>

    {{-- ── Earnings ── --}}
    <div class="meta-fin-group">
      <div class="meta-fin-group-label">Earnings</div>
      <div class="meta-fin-items">
        <div class="meta-fin-item">
          <span class="fin-label">Basic Pay</span>
          <span class="fin-value">{{ number_format($totalBasic, 2) }}</span>
        </div>
        <div class="meta-fin-item">
          <span class="fin-label">Allowances</span>
          <span class="fin-value">{{ number_format($totalAllowances, 2) }}</span>
        </div>
        <div class="meta-fin-item">
          <span class="fin-label">Gross Pay</span>
          <span class="fin-value" style="color:#198754;">{{ number_format($totalGross, 2) }}</span>
        </div>
      </div>
    </div>

    {{-- ── Deductions ── --}}
    <div class="meta-fin-group">
      <div class="meta-fin-group-label">Deductions</div>
      <div class="meta-fin-items">
        <div class="meta-fin-item">
          <span class="fin-label">PAYE</span>
          <span class="fin-value">{{ number_format($totalPaye, 2) }}</span>
        </div>
        <div class="meta-fin-item">
          <span class="fin-label">Pension (Ee)</span>
          <span class="fin-value">{{ number_format($totalPensionEe, 2) }}</span>
        </div>
        <div class="meta-fin-item">
          <span class="fin-label">Loans</span>
          <span class="fin-value">{{ number_format($totalLoan, 2) }}</span>
        </div>
        <div class="meta-fin-item">
          <span class="fin-label">Advances</span>
          <span class="fin-value">{{ number_format($totalAdvance, 2) }}</span>
        </div>
        <div class="meta-fin-item">
          <span class="fin-label">Other</span>
          <span class="fin-value">{{ number_format($totalOtherDed, 2) }}</span>
        </div>
        <div class="meta-fin-item">
          <span class="fin-label">Total Ded.</span>
          <span class="fin-value" style="color:#dc3545;">{{ number_format($totalDeductions, 2) }}</span>
        </div>
      </div>
    </div>

    {{-- ── Net Pay ── --}}
    <div class="meta-fin-group">
      <div class="meta-fin-group-label">Take-Home</div>
      <div class="meta-fin-items">
        <div class="meta-fin-item">
          <span class="fin-label">Net Pay</span>
          <span class="fin-value" style="color:#4B5EBD;font-size:15px;">{{ number_format($totalNet, 2) }}</span>
        </div>
      </div>
    </div>

  </div>

  @if(in_array($period->status, ['approved', 'paid']))
  <div class="period-locked-banner">
    <i class="ri-lock-line text-warning" style="font-size:15px"></i>
    <span>This period is <strong>{{ ucfirst($period->status) }}</strong> — entries are locked and cannot be edited.</span>
  </div>
  @endif
  @endif

  {{-- ══ TABLE / EMPTY STATE ══ --}}
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
    {{--
        Column index map (0-based):
         0  Employee
         1  Branch
         2  Bank Name
         3  Account No.
         4  Basic Pay
         5  Allowances
         6  Gross Pay
         7  PAYE
         8  Pension (Ee)
         9  Loan
        10  Advance
        11  Other Ded.
        12  Total Ded.
        13  Net Pay
        14  Action  ← not orderable, not exported
    --}}
    <table id="maintable" class="table table-sm table-striped row-border order-column w-100"
           style="margin-top:12px;">
      <thead style="background-color:#e2e2e9">
        <tr>
          <th style="text-align:left">Employee</th>
          <th style="text-align:center">Branch</th>
          <th style="text-align:center">Bank Name</th>
          <th style="text-align:center">Account No.</th>
          <th style="text-align:center">Basic Pay</th>
          <th style="text-align:center">Allowances</th>
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
          <?php
            $row = 'row' . $entry->id;

            /* Allowances total = gross minus basic */
            $allowancesTotal = $entry->gross_pay - $entry->basic_salary;
          ?>
          <tr id="{{ $row }}">
            <td style="text-align:left"><strong>{{ $entry->employee_name }}</strong></td>
            <td style="text-align:center">{{ $entry->branch_name ?? '—' }}</td>
            <td style="text-align:center">{{ $entry->bank_name ?? '—' }}</td>
            <td style="text-align:center">{{ $entry->bank_account_number ?? '—' }}</td>

            {{-- Basic Pay --}}
            <td style="text-align:center">
              {{ number_format($entry->basic_salary, 2) }}
            </td>

            {{-- Allowances (bold when non-zero — detail in view modal) --}}
            <td style="text-align:center">
              @if($allowancesTotal != 0)
                <strong>{{ number_format($allowancesTotal, 2) }}</strong>
              @else
                {{ number_format($allowancesTotal, 2) }}
              @endif
            </td>

            {{-- Gross Pay --}}
            <td style="text-align:center">
              <strong>{{ number_format($entry->gross_pay, 2) }}</strong>
            </td>

            <td style="text-align:center">{{ number_format($entry->paye,              2) }}</td>
            <td style="text-align:center">{{ number_format($entry->pension_employee,  2) }}</td>
            <td style="text-align:center">{{ number_format($entry->loan_deduction,    2) }}</td>
            <td style="text-align:center">{{ number_format($entry->advance_deduction, 2) }}</td>
            <td style="text-align:center">{{ number_format($entry->other_deductions,  2) }}</td>
            <td style="text-align:center"><strong>{{ number_format($entry->total_deductions, 2) }}</strong></td>
            <td style="text-align:center"><strong class="text-primary">{{ number_format($entry->net_pay, 2) }}</strong></td>

            <td style="text-align:center; white-space:nowrap;">

              {{-- VIEW BUTTON --}}
              <a href="#" class="viewEntryBtn btn btn-light text-primary btn-sm"
                  data-id="{{ $entry->id }}"
                  data-row="{{ $row }}"
                  data-employee-name="{{ $entry->employee_name }}"
                  data-employee-number="{{ $entry->employee_number ?? '' }}"
                  data-branch="{{ $entry->branch_name ?? '' }}"
                  data-basic="{{ $entry->basic_salary }}"
                  data-housing="{{ $entry->housing_allowance }}"
                  data-transport="{{ $entry->transport_allowance }}"
                  data-medical="{{ $entry->medical_allowance }}"
                  data-meal="{{ $entry->meal_allowance }}"
                  data-other-recurring="{{ $entry->other_recurring_allowance }}"
                  data-other-recurring-label="{{ $entry->other_recurring_allowance_label ?? '' }}"
                  data-acting="{{ $entry->acting_allowance }}"
                  data-commissions="{{ $entry->commissions }}"
                  data-other-variable="{{ $entry->other_variable_allowance }}"
                  data-other-variable-label="{{ $entry->other_variable_allowance_label ?? '' }}"
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

              {{-- EDIT BUTTON (draft/processing only) --}}
              @if($period && in_array($period->status, ['draft', 'processing']))
              <a href="#" class="editEntryBtn btn btn-light text-info btn-sm"
                  data-id="{{ $entry->id }}"
                  data-row="{{ $row }}"
                  data-employee-name="{{ $entry->employee_name }}"
                  data-basic="{{ $entry->basic_salary }}"
                  data-housing="{{ $entry->housing_allowance }}"
                  data-transport="{{ $entry->transport_allowance }}"
                  data-medical="{{ $entry->medical_allowance }}"
                  data-meal="{{ $entry->meal_allowance }}"
                  data-other-recurring="{{ $entry->other_recurring_allowance }}"
                  data-acting="{{ $entry->acting_allowance }}"
                  data-commissions="{{ $entry->commissions }}"
                  data-other-variable="{{ $entry->other_variable_allowance }}"
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

              {{-- PAYSLIP DOWNLOAD --}}
              <a href="{{ route('tenant.admin.hr.payroll.wagebill.payslip', ['tenantName' => request()->route('tenantName')]) }}?entry_id={{ $entry->id }}"
                 class="btn btn-light text-success btn-sm" title="Download Payslip">
                <i class="ri-file-download-line"></i>
              </a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
    @endif

  </div>{{-- /.card-body --}}
</div>{{-- /.card --}}

</div></div></div>


{{-- ════════════════════════════════════════════════════════════════════════
     MODAL — WAGE BILL DOWNLOAD
════════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="wageDownloadModal" data-bs-backdrop="static" data-bs-keyboard="false"
     tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
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
          <div class="wdl-no-period">
            <i class="ri-calendar-event-line"></i>
            <div>
              <strong>No payroll period selected.</strong><br>
              Please choose a period from the filter bar before downloading.
            </div>
          </div>
        @else
          <div class="wdl-scope-bar">
            <i class="ri-filter-line"></i>
            <span>
              <strong>{{ $savedSector }}</strong>
              @if($savedCatId && $categories->where('id',(int)$savedCatId)->isNotEmpty())
                &nbsp;/&nbsp; <strong>{{ $categories->where('id',(int)$savedCatId)->first()->category }}</strong>
              @else
                &nbsp;/ All Categories
              @endif
              &nbsp;&mdash;&nbsp; {{ $period->name }}
            </span>
          </div>
          <div class="wdl-section">
            <div class="wdl-section-header">
              <i class="ri-file-list-3-line wdl-hicon"></i>
              <div>
                <div class="wdl-title">Full Wage Bill</div>
                <div class="wdl-sub">All columns: branch, basic salary, gross pay, deductions, net pay.</div>
              </div>
            </div>
            <div class="wdl-section-body">
              <div class="d-flex gap-2">
                <a href="#" class="wdl-btn wdl-btn-excel"><i class="ri-file-excel-line"></i> Excel</a>
                <a href="#" class="wdl-btn wdl-btn-pdf"><i class="ri-file-pdf-line"></i> PDF</a>
              </div>
            </div>
          </div>
          <div class="wdl-section">
            <div class="wdl-section-header">
              <i class="ri-bank-line wdl-hicon"></i>
              <div>
                <div class="wdl-title">Bank Payment List</div>
                <div class="wdl-sub">Compact format: Branch, Employee Name, Bank Name, Account No., Net Pay.</div>
              </div>
            </div>
            <div class="wdl-section-body">
              <div class="d-flex gap-2">
                <a href="#" class="wdl-btn wdl-btn-excel"><i class="ri-file-excel-line"></i> Excel</a>
                <a href="#" class="wdl-btn wdl-btn-pdf"><i class="ri-file-pdf-line"></i> PDF</a>
              </div>
            </div>
          </div>
        @endif
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


{{-- ════════════════════════════════════════════════════════════════════════
     MODAL — STATUTORY DEDUCTIONS
════════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="statutoryModal" data-bs-backdrop="static" data-bs-keyboard="false"
     tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header mh-orange">
        <h5 class="modal-title mh-title">
          <i class="ri-government-line"></i>&nbsp; Statutory Deductions
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
          <div class="wdl-scope-bar">
            <i class="ri-filter-line"></i>
            <span>
              <strong>{{ $savedSector }}</strong>
              @if($savedCatId && $categories->where('id',(int)$savedCatId)->isNotEmpty())
                &nbsp;/&nbsp; <strong>{{ $categories->where('id',(int)$savedCatId)->first()->category }}</strong>
              @else
                &nbsp;/ All Categories
              @endif
              &nbsp;&mdash;&nbsp; {{ $period->name }}
            </span>
          </div>
          <div class="wdl-section">
            <div class="wdl-section-header">
              <i class="ri-money-dollar-circle-line wdl-hicon wdl-hicon-orange"></i>
              <div>
                <div class="wdl-title">PAYE — Tax Authority Submission</div>
                <div class="wdl-sub">Columns: Branch &nbsp;·&nbsp; Employee Name &nbsp;·&nbsp; Gross Pay &nbsp;·&nbsp; PAYE. Totals row at the bottom.</div>
              </div>
            </div>
            <div class="wdl-section-body">
              <div class="d-flex gap-2">
                <a href="#" class="wdl-btn wdl-btn-excel"><i class="ri-file-excel-line"></i> Excel</a>
                <a href="#" class="wdl-btn wdl-btn-pdf"><i class="ri-file-pdf-line"></i> PDF</a>
              </div>
            </div>
          </div>
          <div class="wdl-section">
            <div class="wdl-section-header">
              <i class="ri-shield-check-line wdl-hicon wdl-hicon-orange"></i>
              <div>
                <div class="wdl-title">Pension — Fund Submission</div>
                <div class="wdl-sub">Columns: Branch &nbsp;·&nbsp; Employee Name &nbsp;·&nbsp; Gross Pay &nbsp;·&nbsp; Pension (Ee) &nbsp;·&nbsp; Pension (Er) &nbsp;·&nbsp; Total Pension. Totals row at the bottom.</div>
              </div>
            </div>
            <div class="wdl-section-body">
              <div class="d-flex gap-2">
                <a href="#" class="wdl-btn wdl-btn-excel"><i class="ri-file-excel-line"></i> Excel</a>
                <a href="#" class="wdl-btn wdl-btn-pdf"><i class="ri-file-pdf-line"></i> PDF</a>
              </div>
            </div>
          </div>
        @endif
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


{{-- ════════════════════════════════════════════════════════════════════════
     MODAL — DATATABLE EXPORT
════════════════════════════════════════════════════════════════════════ --}}
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


{{-- ════════════════════════════════════════════════════════════════════════
     MODAL — INFO
════════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false"
     tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header mh-blue">
      <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Wage Bill</h5>
      <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="font-size:13px;">
      <p>The <strong>Wage Bill</strong> shows one payroll entry per employee for the selected payroll period, scoped strictly to the chosen sector and category.</p>
      <p class="mb-1"><strong>Allowances Column</strong></p>
      <p class="mb-3">The <em>Allowances</em> column is the sum of all non-basic earnings: Housing + Transport + Medical + Meal + Other Recurring + Acting + Commissions + Other Variable + Overtime. Click <i class="ri-eye-line text-primary"></i> to see the full per-line breakdown.</p>
      <p class="mb-1"><strong>Gross Pay</strong></p>
      <p class="mb-3">Gross Pay = Basic Pay + Allowances.</p>
      <p class="mb-1"><strong>Filters</strong></p>
      <ul class="mb-3" style="padding-left:18px;">
        <li class="mb-1"><strong>Sector</strong> (header) — Primary scope. Changing it clears all other filters.</li>
        <li class="mb-1"><strong>Category</strong> — Narrow to a branch category within the sector.</li>
        <li class="mb-1"><strong>Payroll Period</strong> — Choose which pay run to view.</li>
      </ul>
      <p class="mb-1"><strong>Row actions</strong></p>
      <ul class="mb-0" style="padding-left:18px;">
        <li class="mb-1"><i class="ri-eye-line text-primary"></i> <strong>View</strong> — Full earnings breakdown including every allowance line item.</li>
        <li class="mb-1"><i class="ri-edit-box-line text-info"></i> <strong>Edit</strong> — Draft / Processing periods only. Adjust any earnings or deduction field.</li>
        <li><i class="ri-file-download-line text-success"></i> <strong>Payslip</strong> — PDF payslip for this employee.</li>
      </ul>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
    </div>
  </div></div>
</div>


{{-- ════════════════════════════════════════════════════════════════════════
     MODAL — VIEW ENTRY  (full earnings snapshot)
════════════════════════════════════════════════════════════════════════ --}}
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
          <div class="col-md-4 col-6"><div class="entry-field-group"><div class="entry-field-label">Phone</div><div class="entry-field-value" id="vEmployeeNumber">—</div></div></div>
          <div class="col-md-4 col-6"><div class="entry-field-group"><div class="entry-field-label">Branch</div><div class="entry-field-value" id="vBranch">—</div></div></div>
        </div>

        {{-- ── Earnings: Basic + Allowances breakdown + Gross ── --}}
        <div class="modal-section-title">Earnings</div>

        {{-- Basic Pay row --}}
        <div class="row g-2 mb-2">
          <div class="col-12">
            <div class="entry-field-group" style="background:#f0f4ff;">
              <div class="entry-field-label">Basic Pay</div>
              <div class="entry-field-value" id="vBasicModal">0.00</div>
            </div>
          </div>
        </div>

        {{-- Allowances breakdown --}}
        <div class="allow-breakdown mb-2">
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
            <span class="allow-label">Other Recurring &mdash; <em id="vOtherRecurringLabel" style="font-style:normal;color:#4B5EBD;"></em></span>
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
            <span class="allow-label">Other Variable &mdash; <em id="vOtherVariableLabel" style="font-style:normal;color:#4B5EBD;"></em></span>
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

        {{-- Gross Pay summary row --}}
        <div class="row g-2 mb-2">
          <div class="col-12">
            <div class="entry-field-group" style="background:#e8f5e9;">
              <div class="entry-field-label">Gross Pay (Basic + Allowances)</div>
              <div class="entry-field-value text-success" id="vGross">0.00</div>
            </div>
          </div>
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


{{-- ════════════════════════════════════════════════════════════════════════
     MODAL — EDIT ENTRY  (individual allowance fields)
════════════════════════════════════════════════════════════════════════ --}}
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
        <div class="alert alert-info d-flex align-items-start gap-2 py-2 px-3 mb-3" style="font-size:12px;">
          <i class="ri-information-line" style="font-size:15px;flex-shrink:0;margin-top:1px;"></i>
          <span>Adjust any earnings or deduction field. Gross Pay and Net Pay recompute live as you type.</span>
        </div>

        <form action="#" method="post" id="editEntryForm">
          @csrf
          <input type="hidden" name="id"  id="editEntryId">
          <input type="hidden" name="row" id="editEntryRow">

          <div class="modal-section-title">Basic Pay</div>
          <div class="row g-3 mb-1">
            <div class="col-md-4 col-6">
              <label style="font-size:12px">Basic Salary</label>
              <input type="number" step="0.01" min="0" class="form-control form-control-sm calcField"
                     name="basic_salary" id="eBasic">
            </div>
          </div>

          <div class="modal-section-title">Allowances — Recurring</div>
          <div class="row g-3 mb-1">
            <div class="col-md-3 col-6">
              <label style="font-size:12px">Housing Allowance</label>
              <input type="number" step="0.01" min="0" class="form-control form-control-sm calcField"
                     name="housing_allowance" id="eHousing">
            </div>
            <div class="col-md-3 col-6">
              <label style="font-size:12px">Transport Allowance</label>
              <input type="number" step="0.01" min="0" class="form-control form-control-sm calcField"
                     name="transport_allowance" id="eTransport">
            </div>
            <div class="col-md-3 col-6">
              <label style="font-size:12px">Medical Allowance</label>
              <input type="number" step="0.01" min="0" class="form-control form-control-sm calcField"
                     name="medical_allowance" id="eMedical">
            </div>
            <div class="col-md-3 col-6">
              <label style="font-size:12px">Meal Allowance</label>
              <input type="number" step="0.01" min="0" class="form-control form-control-sm calcField"
                     name="meal_allowance" id="eMeal">
            </div>
            <div class="col-md-3 col-6">
              <label style="font-size:12px">Other Recurring</label>
              <input type="number" step="0.01" min="0" class="form-control form-control-sm calcField"
                     name="other_recurring_allowance" id="eOtherRecurring">
            </div>
          </div>

          <div class="modal-section-title">Allowances — Variable</div>
          <div class="row g-3 mb-1">
            <div class="col-md-3 col-6">
              <label style="font-size:12px">Acting Allowance</label>
              <input type="number" step="0.01" min="0" class="form-control form-control-sm calcField"
                     name="acting_allowance" id="eActing">
            </div>
            <div class="col-md-3 col-6">
              <label style="font-size:12px">Commissions</label>
              <input type="number" step="0.01" min="0" class="form-control form-control-sm calcField"
                     name="commissions" id="eCommissions">
            </div>
            <div class="col-md-3 col-6">
              <label style="font-size:12px">Other Variable</label>
              <input type="number" step="0.01" min="0" class="form-control form-control-sm calcField"
                     name="other_variable_allowance" id="eOtherVariable">
            </div>
            <div class="col-md-3 col-6">
              <label style="font-size:12px">Overtime</label>
              <input type="number" step="0.01" min="0" class="form-control form-control-sm calcField"
                     name="overtime_amount" id="eOvertime">
            </div>
          </div>

          <div class="modal-section-title">Deductions</div>
          <div class="row g-3">
            <div class="col-md-3 col-6">
              <label style="font-size:12px">PAYE</label>
              <input type="number" step="0.01" min="0" class="form-control form-control-sm calcField"
                     name="paye" id="ePaye">
            </div>
            <div class="col-md-3 col-6">
              <label style="font-size:12px">Pension (Employee)</label>
              <input type="number" step="0.01" min="0" class="form-control form-control-sm calcField"
                     name="pension_employee" id="ePensionEe">
            </div>
            <div class="col-md-3 col-6">
              <label style="font-size:12px">Pension (Employer)</label>
              <input type="number" step="0.01" min="0" class="form-control form-control-sm"
                     name="pension_employer" id="ePensionEr">
              <small class="text-muted" style="font-size:10px;">Not deducted from net pay</small>
            </div>
            <div class="col-md-3 col-6">
              <label style="font-size:12px">Loan Deduction</label>
              <input type="number" step="0.01" min="0" class="form-control form-control-sm calcField"
                     name="loan_deduction" id="eLoan">
            </div>
            <div class="col-md-3 col-6">
              <label style="font-size:12px">Advance Deduction</label>
              <input type="number" step="0.01" min="0" class="form-control form-control-sm calcField"
                     name="advance_deduction" id="eAdvance">
            </div>
            <div class="col-md-3 col-6">
              <label style="font-size:12px">Other Deductions</label>
              <input type="number" step="0.01" min="0" class="form-control form-control-sm calcField"
                     name="other_deductions" id="eOtherDeductions">
            </div>
          </div>

          {{-- Live preview ──────────────────────────────────────────────── --}}
          <div class="row g-2 mt-3 mb-3">
            <div class="col-md-3 col-6">
              <div class="entry-field-group" style="background:#f0f4ff;">
                <div class="entry-field-label">Basic Pay</div>
                <div class="entry-field-value" id="previewBasic">0.00</div>
              </div>
            </div>
            <div class="col-md-3 col-6">
              <div class="entry-field-group" style="background:#eef2fb;">
                <div class="entry-field-label">Allowances</div>
                <div class="entry-field-value" style="color:#4B5EBD;" id="previewAllowances">0.00</div>
              </div>
            </div>
            <div class="col-md-3 col-6">
              <div class="entry-field-group" style="background:#e8f5e9;">
                <div class="entry-field-label">Gross Pay</div>
                <div class="entry-field-value text-success" id="previewGross">0.00</div>
              </div>
            </div>
            <div class="col-md-3 col-6">
              <div class="entry-field-group" style="background:#fdecea;">
                <div class="entry-field-label">Total Deductions</div>
                <div class="entry-field-value text-danger" id="previewTotalDed">0.00</div>
              </div>
            </div>
            <div class="col-md-4 col-6">
              <div class="entry-field-group" style="background:#e8eaf6;">
                <div class="entry-field-label">Net Pay</div>
                <div class="entry-field-value text-primary" id="previewNetPay">0.00</div>
              </div>
            </div>
          </div>

          <div class="modal-section-title">Notes</div>
          <div class="mb-3">
            <textarea class="form-control" name="notes" id="eNotes" rows="2"
                      placeholder="Optional notes…"></textarea>
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

    // ── DataTable — mirrors employees view exactly ────────────────────────
    @if($period && $entries->isNotEmpty())
    var maintableTitle = '{{ "Wage Bill - " . addslashes($period->name) }}';
    var table = $('#maintable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[100, 250, 500, -1], [100, 250, 500, 'All']],
        fixedColumns: { left: 1 },
        scrollX: true,
        order: [[1, 'asc'], [0, 'asc']],
        columnDefs: [{ targets: [14], orderable: false }],
        buttons: [
            { extend: 'excelHtml5', title: maintableTitle, exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'csvHtml5',   title: maintableTitle, exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'pdfHtml5',  title: maintableTitle,
              exportOptions: { columns: ':visible:not(:last-child)' },
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
    $('#infoBtn').on('click',            function(e){ e.preventDefault(); $('#infoModal').modal('show'); });
    $('#tableButtonsBtn').on('click',    function(e){ e.preventDefault(); $('#buttonsModal').modal('show'); });
    $('#wageDownloadBtn').on('click',    function(e){ e.preventDefault(); wageDownloadModal.show(); });
    $('#statutoryBtn').on('click',       function(e){ e.preventDefault(); statutoryModal.show(); });
    $('#cancelEditEntryBtn').on('click', function(e){ e.preventDefault(); editEntryModal.hide(); });

    // ── Number formatter ──────────────────────────────────────────────────
    function fmt(n) {
        return parseFloat(n || 0).toLocaleString('en', {
            minimumFractionDigits: 2, maximumFractionDigits: 2
        });
    }

    // ── VIEW entry ────────────────────────────────────────────────────────
    $('#tbody').on('click', '.viewEntryBtn', function() {
        var d = $(this).data();

        // Employee
        $('#viewEmployeeNameTitle').text(d.employeeName);
        $('#vEmployeeName').text(d.employeeName);
        $('#vEmployeeNumber').text(d.employeeNumber || '—');
        $('#vBranch').text(d.branch || '—');

        // Basic Pay
        $('#vBasicModal').text(fmt(d.basic));

        // Allowances breakdown
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

        // Allowances total
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

        // Gross
        $('#vGross').text(fmt(d.gross));
        $('#vGross2').text(fmt(d.gross));

        // Deductions
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

        // Basic
        $('#eBasic').val(d.basic);

        // Recurring allowances
        $('#eHousing').val(d.housing);
        $('#eTransport').val(d.transport);
        $('#eMedical').val(d.medical);
        $('#eMeal').val(d.meal);
        $('#eOtherRecurring').val(d.otherRecurring);

        // Variable allowances
        $('#eActing').val(d.acting);
        $('#eCommissions').val(d.commissions);
        $('#eOtherVariable').val(d.otherVariable);
        $('#eOvertime').val(d.overtime);

        // Deductions
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

    // ── Live preview ──────────────────────────────────────────────────────
    function refreshPreview() {
        var basic = parseFloat($('#eBasic').val() || 0);

        var allowances = parseFloat($('#eHousing').val()        || 0)
                       + parseFloat($('#eTransport').val()      || 0)
                       + parseFloat($('#eMedical').val()        || 0)
                       + parseFloat($('#eMeal').val()           || 0)
                       + parseFloat($('#eOtherRecurring').val() || 0)
                       + parseFloat($('#eActing').val()         || 0)
                       + parseFloat($('#eCommissions').val()    || 0)
                       + parseFloat($('#eOtherVariable').val()  || 0)
                       + parseFloat($('#eOvertime').val()       || 0);

        var gross    = basic + allowances;

        var totalDed = parseFloat($('#ePaye').val()            || 0)
                     + parseFloat($('#ePensionEe').val()       || 0)
                     + parseFloat($('#eLoan').val()            || 0)
                     + parseFloat($('#eAdvance').val()         || 0)
                     + parseFloat($('#eOtherDeductions').val() || 0);

        var net = gross - totalDed;
        $('#previewBasic').text(fmt(basic));
        $('#previewAllowances').text(fmt(allowances));
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
                    if (data.warning) toastr.warning(data.warning, 'Note');

                    var e    = data.entry;
                    var $row = $('#' + row);

                    var allowancesUpdated = parseFloat(e.gross_pay) - parseFloat(e.basic_salary);

                    // Col index map:
                    // 0=Employee 1=Branch 2=BankName 3=AccountNo
                    // 4=BasicPay 5=Allowances 6=GrossPay
                    // 7=PAYE 8=PensionEe 9=Loan 10=Advance 11=OtherDed 12=TotalDed 13=NetPay 14=Action
                    $row.find('td').eq(4).text(fmt(e.basic_salary));
                    $row.find('td').eq(5).html(allowancesUpdated != 0 ? '<strong>' + fmt(allowancesUpdated) + '</strong>' : fmt(allowancesUpdated));
                    $row.find('td').eq(6).html('<strong>' + fmt(e.gross_pay) + '</strong>');
                    $row.find('td').eq(7).text(fmt(e.paye));
                    $row.find('td').eq(8).text(fmt(e.pension_employee));
                    $row.find('td').eq(9).text(fmt(e.loan_deduction));
                    $row.find('td').eq(10).text(fmt(e.advance_deduction));
                    $row.find('td').eq(11).text(fmt(e.other_deductions));
                    $row.find('td').eq(12).html('<strong>' + fmt(e.total_deductions) + '</strong>');
                    $row.find('td').eq(13).html('<strong class="text-primary">' + fmt(e.net_pay) + '</strong>');

                    // Refresh all data-* on both buttons
                    $row.find('.viewEntryBtn, .editEntryBtn')
                        .data('basic',                  e.basic_salary)
                        .data('housing',                e.housing_allowance)
                        .data('transport',              e.transport_allowance)
                        .data('medical',                e.medical_allowance)
                        .data('meal',                   e.meal_allowance)
                        .data('other-recurring',        e.other_recurring_allowance)
                        .data('other-recurring-label',  e.other_recurring_allowance_label)
                        .data('acting',                 e.acting_allowance)
                        .data('commissions',            e.commissions)
                        .data('other-variable',         e.other_variable_allowance)
                        .data('other-variable-label',   e.other_variable_allowance_label)
                        .data('overtime',               e.overtime_amount)
                        .data('gross',                  e.gross_pay)
                        .data('paye',                   e.paye)
                        .data('pension-employee',       e.pension_employee)
                        .data('pension-employer',       e.pension_employer)
                        .data('loan',                   e.loan_deduction)
                        .data('advance',                e.advance_deduction)
                        .data('other-deductions',       e.other_deductions)
                        .data('total-deductions',       e.total_deductions)
                        .data('net-pay',                e.net_pay)
                        .data('notes',                  e.notes);

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