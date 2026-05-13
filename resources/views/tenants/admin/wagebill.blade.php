@extends('tenants.admin.dashboard')
@section('content')
@php
    /* ── User preferences ─────────────────────────────────────────────── */
    $pref = DB::connection('tenant')->table('user_filters')
                ->where('user_id', Auth::id())
                ->first();

    $savedSector   = $pref->sector          ?? null;
    $savedCatId    = $pref->category_id     ?? null;
    $savedPeriodId = $pref->wagebill_period_id ?? null;

    /* ── Sectors ──────────────────────────────────────────────────────── */
    $sectors = DB::connection('tenant')->table('sectors')->orderBy('sector')->get();

    /* ── Categories for the saved sector ─────────────────────────────── */
    $categories = collect();
    if ($savedSector) {
        $categories = DB::connection('tenant')
            ->table('categories')
            ->orderBy('category')
            ->get();
    }

    /* ── Payroll periods ──────────────────────────────────────────────── */
    $periods = collect();
    if ($savedSector) {
        $periods = DB::connection('tenant')
            ->table('payroll_periods')
            ->orderBy('period_start', 'desc')
            ->get();
    }

    /* ── Wage bill entries ────────────────────────────────────────────── */
    $period  = null;
    $entries = collect();

    if ($savedPeriodId) {
        $period = DB::connection('tenant')
            ->table('payroll_periods')
            ->where('id', $savedPeriodId)
            ->first();

        if ($period) {
            $entriesQuery = DB::connection('tenant')
                ->table('payroll_entries')
                ->join('users', 'users.id', '=', 'payroll_entries.employee_id')
                ->where('payroll_entries.payroll_period_id', $savedPeriodId);

            if ($savedCatId) {
                $branchIds = DB::connection('tenant')
                    ->table('branches')
                    ->where('category', $savedCatId)
                    ->pluck('id')
                    ->toArray();

                if (!empty($branchIds)) {
                    $entriesQuery->whereIn('users.branch', $branchIds);
                }
            }

            $entries = $entriesQuery
                ->select(
                    'payroll_entries.*',
                    'users.name            as employee_name',
                    'users.phone           as employee_number',
                    'users.bank_name       as bank_name',
                    'users.bank_account_number as bank_account_number'
                )
                ->orderBy('users.name', 'asc')
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

/* ── Filter bar ─────────────────────────────────────────────────────────── */
.card-filter {
  background: #eef0f7; border-bottom: 1px solid #d6daf0;
  padding: 9px 1.5rem; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
}
.card-filter label {
  font-size:12px; font-weight:600; color:#4B5EBD; margin-bottom:0; white-space:nowrap;
}
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

/* ── No selection / empty state ─────────────────────────────────────────── */
.empty-state {
  padding:52px 20px; text-align:center; color:#94a3b8;
}
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
.mh-blue  { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-title { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }

/* ── Fixed column DataTable overrides ──────────────────────────────────── */
table.dataTable.fixedHeader-floating,
table.dataTable.fixedHeader-locked { background: #fff !important; border-bottom: none !important; }
table.dataTable thead th.fixedHeader-floating { background: #e2e2e9 !important; }

/* ── Wage download modal section headers ───────────────────────────────── */
.wdl-section-label {
  font-size:11px; font-weight:600; text-transform:uppercase;
  letter-spacing:.05em; color:#4B5EBD; margin-bottom:8px;
}
</style>

<div class="progress" id="progressBar" role="progressbar"
     style="height:8px;transform:rotate(180deg);display:none">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

  {{-- ── Card header ─────────────────────────────────────────────────────── --}}
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
      <i class="ri-bill-line me-1"></i> Wage Bill
      @if($period) &mdash; {{ $period->name }} @endif
    </h4>
    <div class="d-flex align-items-center" style="gap:4px;">
      <a href="#" class="btn btn-light text-primary fs-16 mx-1"  id="infoBtn"         title="Info"><i class="ri-information-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1"  id="tableButtonsBtn" title="DataTable Export"><i class="ri-table-line"></i></a>
      <a href="#" class="btn btn-light text-success fs-16 mx-1"  id="wageDownloadBtn" title="Download Wage Bill"><i class="ri-download-2-line"></i></a>
    </div>
  </div>

  {{-- ── Filter bar: Sector → Category → Period ─────────────────────────── --}}
  <div class="card-filter">

    {{-- Sector --}}
    <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
          id="filterSectorForm" style="display:contents;">
      @csrf
      <input type="hidden" name="user_id"            value="{{ Auth::id() }}">
      <input type="hidden" name="category_id"        value="">
      <input type="hidden" name="wagebill_period_id" value="">
      <label>Sector:</label>
      <select name="sector" id="filterSector"
              onchange="document.getElementById('filterSectorForm').submit()">
        <option value="" hidden>— Select Sector —</option>
        @foreach($sectors as $sec)
          <option value="{{ $sec->sector }}"
            {{ $savedSector === $sec->sector ? 'selected' : '' }}>
            {{ $sec->sector }}
          </option>
        @endforeach
      </select>
    </form>

    <div class="filter-divider"></div>

    {{-- Category --}}
    @if($savedSector)
      <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
            id="filterCatForm" style="display:contents;">
        @csrf
        <input type="hidden" name="user_id"            value="{{ Auth::id() }}">
        <input type="hidden" name="sector"             value="{{ $savedSector }}">
        <input type="hidden" name="wagebill_period_id" value="">
        <label>Category:</label>
        <select name="category_id" id="filterCat"
                onchange="document.getElementById('filterCatForm').submit()">
          <option value="">All Categories</option>
          @foreach($categories as $cat)
            <option value="{{ $cat->id }}"
              {{ $savedCatId == $cat->id ? 'selected' : '' }}>
              {{ $cat->category }}
            </option>
          @endforeach
        </select>
      </form>
    @else
      <label>Category:</label>
      <select disabled title="Select a sector first">
        <option>— Select sector first —</option>
      </select>
    @endif

    <div class="filter-divider"></div>

    {{-- Payroll Period --}}
    @if($savedSector)
      <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
            id="filterPeriodForm" style="display:contents;">
        @csrf
        <input type="hidden" name="user_id"     value="{{ Auth::id() }}">
        <input type="hidden" name="sector"      value="{{ $savedSector }}">
        <input type="hidden" name="category_id" value="{{ $savedCatId }}">
        <label>Payroll Period:</label>
        <select name="wagebill_period_id" id="filterPeriod"
                onchange="document.getElementById('filterPeriodForm').submit()">
          <option value="">— Select Period —</option>
          @foreach($periods as $per)
            <option value="{{ $per->id }}"
              {{ $savedPeriodId == $per->id ? 'selected' : '' }}>
              {{ $per->name }}
              ({{ \Carbon\Carbon::parse($per->period_start)->format('d M') }}
               – {{ \Carbon\Carbon::parse($per->period_end)->format('d M Y') }})
            </option>
          @endforeach
        </select>
      </form>
    @else
      <label>Payroll Period:</label>
      <select disabled title="Select a sector first">
        <option>— Select sector first —</option>
      </select>
    @endif

    {{-- Back to Periods --}}
    <div class="ms-auto">
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
      <span class="meta-value">{{ $period->period_start }}</span>
    </div>
    <div class="meta-item">
      <span class="meta-label">End</span>
      <span class="meta-value">{{ $period->period_end }}</span>
    </div>
    <div class="meta-item">
      <span class="meta-label">Pay Date</span>
      <span class="meta-value">{{ $period->pay_date }}</span>
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

  @if(in_array($period->status, ['approved', 'paid']))
  <div class="period-locked-banner">
    <i class="ri-lock-line text-warning" style="font-size:15px"></i>
    <span>This period is <strong>{{ ucfirst($period->status) }}</strong> — entries are locked and cannot be edited.</span>
  </div>
  @endif
  @endif

  {{-- ── Table / empty state ─────────────────────────────────────────────── --}}
  <div class="card-body" style="padding-top:0 !important;">

    @if(!$savedSector)
      <div class="empty-state">
        <i class="ri-filter-3-line"></i>
        <h5>Select a Sector to begin</h5>
        <p>Use the filter bar above to choose a Sector, then optionally a Category and Payroll Period.</p>
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
              {{-- View --}}
              <a href="#" class="viewEntryBtn btn btn-light text-primary btn-sm"
                  data-id="{{ $entry->id }}"
                  data-row="{{ $row }}"
                  data-employee-name="{{ $entry->employee_name }}"
                  data-employee-number="{{ $entry->employee_number ?? '' }}"
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
              {{-- Edit: draft/processing only --}}
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
              {{-- Payslip PDF --}}
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


{{-- ═══════════════════════════════════════════════════════════════════════
     WAGE BILL DOWNLOAD MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="wageDownloadModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title">
          <i class="ri-download-2-line"></i>&nbsp; Download Wage Bill
          @if($period)
            &mdash; <span style="font-weight:400;font-size:13px;opacity:.9;">{{ $period->name }}</span>
          @endif
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">

        {{-- ── Payment list (bank submission format) ── --}}
        <div class="wdl-section-label" style="margin-top:0;">
          <i class="ri-bank-line me-1"></i> Payment List — Bank Submission Format
        </div>
        <p style="font-size:12px;color:#6c757d;margin-bottom:10px;">
          Compact list showing employee name, net pay, bank name and account number — suitable for uploading to your bank's bulk payment portal.
        </p>
        <div class="d-flex flex-wrap gap-2 mb-4 ps-1">
          <a href="#" class="btn btn-outline-primary btn-sm">
            <i class="ri-file-excel-line me-1"></i> Payment List — Excel
          </a>
          <a href="#" class="btn btn-outline-danger btn-sm">
            <i class="ri-file-pdf-line me-1"></i> Payment List — PDF
          </a>
          <a href="#" class="btn btn-outline-secondary btn-sm">
            <i class="ri-file-text-line me-1"></i> Payment List — CSV
          </a>
        </div>

        {{-- ── Full wage bill ── --}}
        <div class="wdl-section-label">
          <i class="ri-file-list-3-line me-1"></i> Full Wage Bill — All Columns
        </div>
        <p style="font-size:12px;color:#6c757d;margin-bottom:14px;">
          Complete detail including all earnings, deductions, gross pay and net pay.
        </p>

        {{-- All sectors combined --}}
        <div class="border rounded p-3 mb-3">
          <div style="font-size:12px;font-weight:600;color:#212529;margin-bottom:8px;">
            <i class="ri-building-4-line me-1 text-primary"></i> All Sectors — Combined
          </div>
          <div class="d-flex flex-wrap gap-2 ps-1">
            <a href="#" class="btn btn-outline-primary btn-sm"><i class="ri-file-excel-line me-1"></i> Excel</a>
            <a href="#" class="btn btn-outline-danger  btn-sm"><i class="ri-file-pdf-line   me-1"></i> PDF</a>
            <a href="#" class="btn btn-outline-secondary btn-sm"><i class="ri-file-text-line me-1"></i> CSV</a>
          </div>
        </div>

        {{-- By category --}}
        <div class="border rounded p-3 mb-3">
          <div style="font-size:12px;font-weight:600;color:#212529;margin-bottom:8px;">
            <i class="ri-filter-line me-1 text-primary"></i> By Category
            @if($savedCatId)
              <span class="badge bg-light text-primary border ms-1" style="font-size:11px;font-weight:500;">
                {{ $categories->where('id', $savedCatId)->first()->category ?? 'Selected' }}
              </span>
            @else
              <span class="text-muted" style="font-weight:400;font-size:11px;">(uses current category filter)</span>
            @endif
          </div>
          <div class="d-flex flex-wrap gap-2 ps-1">
            <a href="#" class="btn btn-outline-primary btn-sm"><i class="ri-file-excel-line me-1"></i> Excel</a>
            <a href="#" class="btn btn-outline-danger  btn-sm"><i class="ri-file-pdf-line   me-1"></i> PDF</a>
            <a href="#" class="btn btn-outline-secondary btn-sm"><i class="ri-file-text-line me-1"></i> CSV</a>
          </div>
        </div>

        {{-- All branches grouped --}}
        <div class="border rounded p-3 mb-3">
          <div style="font-size:12px;font-weight:600;color:#212529;margin-bottom:2px;">
            <i class="ri-store-2-line me-1 text-primary"></i> All Branches — Grouped by Branch
          </div>
          <div style="font-size:11px;color:#6c757d;margin-bottom:8px;">Each branch on a separate section / sheet.</div>
          <div class="d-flex flex-wrap gap-2 ps-1">
            <a href="#" class="btn btn-outline-primary btn-sm"><i class="ri-file-excel-line me-1"></i> Excel</a>
            <a href="#" class="btn btn-outline-danger  btn-sm"><i class="ri-file-pdf-line   me-1"></i> PDF</a>
            <a href="#" class="btn btn-outline-secondary btn-sm"><i class="ri-file-text-line me-1"></i> CSV</a>
          </div>
        </div>

        {{-- All branches not grouped --}}
        <div class="border rounded p-3 mb-3">
          <div style="font-size:12px;font-weight:600;color:#212529;margin-bottom:2px;">
            <i class="ri-store-line me-1 text-primary"></i> All Branches — Not Grouped
          </div>
          <div style="font-size:11px;color:#6c757d;margin-bottom:8px;">Single flat list of all employees across all branches.</div>
          <div class="d-flex flex-wrap gap-2 ps-1">
            <a href="#" class="btn btn-outline-primary btn-sm"><i class="ri-file-excel-line me-1"></i> Excel</a>
            <a href="#" class="btn btn-outline-danger  btn-sm"><i class="ri-file-pdf-line   me-1"></i> PDF</a>
            <a href="#" class="btn btn-outline-secondary btn-sm"><i class="ri-file-text-line me-1"></i> CSV</a>
          </div>
        </div>

        {{-- Single branch --}}
        <div class="border rounded p-3 mb-1">
          <div style="font-size:12px;font-weight:600;color:#212529;margin-bottom:2px;">
            <i class="ri-map-pin-2-line me-1 text-primary"></i> Single Branch
          </div>
          <div style="font-size:11px;color:#6c757d;margin-bottom:8px;">Select a specific branch to download its wage bill only.</div>
          <div class="d-flex flex-wrap gap-2 ps-1">
            <a href="#" class="btn btn-outline-primary btn-sm"><i class="ri-file-excel-line me-1"></i> Excel</a>
            <a href="#" class="btn btn-outline-danger  btn-sm"><i class="ri-file-pdf-line   me-1"></i> PDF</a>
            <a href="#" class="btn btn-outline-secondary btn-sm"><i class="ri-file-text-line me-1"></i> CSV</a>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════
     DATATABLE EXPORT MODAL
══════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
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
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header mh-blue">
      <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Wage Bill</h5>
      <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="font-size:13px;">
      <p>The <strong>Wage Bill</strong> shows one payroll entry per active employee for the selected payroll period.</p>

      <p class="mb-1"><strong>How to use the filters</strong></p>
      <ol class="mb-3" style="padding-left:18px;">
        <li class="mb-1"><strong>Sector</strong> — Select the business sector (e.g. Retail, Wholesale). This unlocks the Category and Period selectors.</li>
        <li class="mb-1"><strong>Category</strong> — Optionally narrow down to a specific branch category. Selecting <em>All Categories</em> shows all employees in the sector.</li>
        <li class="mb-1"><strong>Payroll Period</strong> — Choose which pay run to view. Periods are listed most recent first.</li>
      </ol>

      <p class="mb-1"><strong>Table columns</strong></p>
      <table style="width:100%;border-collapse:collapse;font-size:12px;" class="mb-3">
        <tbody>
          <tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 8px;font-weight:600;color:#475569;width:130px">Bank Name</td><td style="padding:5px 8px;">Employee's registered bank for salary payment.</td></tr>
          <tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 8px;font-weight:600;color:#475569">Account No.</td><td style="padding:5px 8px;">Bank account number for salary transfer.</td></tr>
          <tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 8px;font-weight:600;color:#475569">Gross Pay</td><td style="padding:5px 8px;">Basic + all allowances + overtime.</td></tr>
          <tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 8px;font-weight:600;color:#475569">PAYE</td><td style="padding:5px 8px;">Tax computed using the Malawi PAYE bracket table.</td></tr>
          <tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 8px;font-weight:600;color:#475569">Pension (Ee)</td><td style="padding:5px 8px;">Employee pension contribution deducted from gross pay.</td></tr>
          <tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 8px;font-weight:600;color:#475569">Loan</td><td style="padding:5px 8px;">Active loan instalment deduction.</td></tr>
          <tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 8px;font-weight:600;color:#475569">Advance</td><td style="padding:5px 8px;">Salary advance recovery deduction.</td></tr>
          <tr style="border-bottom:1px solid #f1f5f9"><td style="padding:5px 8px;font-weight:600;color:#475569">Total Ded.</td><td style="padding:5px 8px;">Sum of all deductions.</td></tr>
          <tr><td style="padding:5px 8px;font-weight:600;color:#4B5EBD">Net Pay</td><td style="padding:5px 8px;">Amount the employee receives (Gross − Total Deductions).</td></tr>
        </tbody>
      </table>

      <p class="mb-1"><strong>Row actions</strong></p>
      <ul class="mb-0" style="padding-left:18px;">
        <li class="mb-1"><i class="ri-eye-line text-primary"></i> <strong>View</strong> — Shows the full breakdown for any employee.</li>
        <li class="mb-1"><i class="ri-edit-box-line text-info"></i> <strong>Edit</strong> — Adjust earnings or deductions. Only available while the period is <strong>Draft</strong> or <strong>Processing</strong>.</li>
        <li><i class="ri-file-download-line text-success"></i> <strong>Payslip</strong> — Downloads a PDF payslip for the employee.</li>
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
<div class="modal fade" id="viewEntryModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
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
          <div class="col-md-6">
            <div class="entry-field-group">
              <div class="entry-field-label">Name</div>
              <div class="entry-field-value" id="vEmployeeName">—</div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="entry-field-group">
              <div class="entry-field-label">Employee #</div>
              <div class="entry-field-value" id="vEmployeeNumber">—</div>
            </div>
          </div>
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
<div class="modal fade" id="editEntryModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
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
          <div class="row">
            <div class="form-group col-md-4 col-6 mb-3">
              <label style="font-size:13px">Basic Salary</label>
              <input type="number" step="0.01" min="0" class="form-control calcField" name="basic_salary" id="eBasic">
            </div>
            <div class="form-group col-md-4 col-6 mb-3">
              <label style="font-size:13px">Housing Allowance</label>
              <input type="number" step="0.01" min="0" class="form-control calcField" name="housing_allowance" id="eHousing">
            </div>
            <div class="form-group col-md-4 col-6 mb-3">
              <label style="font-size:13px">Transport Allowance</label>
              <input type="number" step="0.01" min="0" class="form-control calcField" name="transport_allowance" id="eTransport">
            </div>
            <div class="form-group col-md-4 col-6 mb-3">
              <label style="font-size:13px">Other Allowances</label>
              <input type="number" step="0.01" min="0" class="form-control calcField" name="other_allowances" id="eOtherAllowances">
            </div>
            <div class="form-group col-md-4 col-6 mb-3">
              <label style="font-size:13px">Overtime</label>
              <input type="number" step="0.01" min="0" class="form-control calcField" name="overtime_amount" id="eOvertime">
            </div>
          </div>

          <div class="modal-section-title">Deductions</div>
          <div class="row">
            <div class="form-group col-md-4 col-6 mb-3">
              <label style="font-size:13px">PAYE</label>
              <input type="number" step="0.01" min="0" class="form-control calcField" name="paye" id="ePaye">
            </div>
            <div class="form-group col-md-4 col-6 mb-3">
              <label style="font-size:13px">Pension (Employee)</label>
              <input type="number" step="0.01" min="0" class="form-control calcField" name="pension_employee" id="ePensionEe">
            </div>
            <div class="form-group col-md-4 col-6 mb-3">
              <label style="font-size:13px">Pension (Employer)</label>
              <input type="number" step="0.01" min="0" class="form-control" name="pension_employer" id="ePensionEr">
            </div>
            <div class="form-group col-md-4 col-6 mb-3">
              <label style="font-size:13px">Loan Deduction</label>
              <input type="number" step="0.01" min="0" class="form-control calcField" name="loan_deduction" id="eLoan">
            </div>
            <div class="form-group col-md-4 col-6 mb-3">
              <label style="font-size:13px">Advance Deduction</label>
              <input type="number" step="0.01" min="0" class="form-control calcField" name="advance_deduction" id="eAdvance">
            </div>
            <div class="form-group col-md-4 col-6 mb-3">
              <label style="font-size:13px">Other Deductions</label>
              <input type="number" step="0.01" min="0" class="form-control calcField" name="other_deductions" id="eOtherDeductions">
            </div>
          </div>

          {{-- Live computed preview --}}
          <div class="row g-2 mt-1 mb-3">
            <div class="col-md-4">
              <div class="entry-field-group" style="background:#e8f5e9;">
                <div class="entry-field-label">Gross Pay (computed)</div>
                <div class="entry-field-value text-success" id="previewGross">0.00</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="entry-field-group" style="background:#fdecea;">
                <div class="entry-field-label">Total Deductions (computed)</div>
                <div class="entry-field-value text-danger" id="previewTotalDed">0.00</div>
              </div>
            </div>
            <div class="col-md-4">
              <div class="entry-field-group" style="background:#e8eaf6;">
                <div class="entry-field-label">Net Pay (computed)</div>
                <div class="entry-field-value text-primary" id="previewNetPay">0.00</div>
              </div>
            </div>
          </div>

          <div class="modal-section-title">Notes</div>
          <div class="form-group mb-3">
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

    // ── DataTable (only when entries exist) ───────────────────────────────
    @if($period && $entries->isNotEmpty())

    var maintableTitle = '{{ "Wage Bill - " . addslashes($period->name) }}';
    var table = $('#maintable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
        order: [[0, 'asc']],
        fixedColumns: { left: 1 },
        scrollX: true,
        columnDefs: [
            { targets: [16], orderable: false }
        ],
        buttons: [
            { extend: 'excelHtml5', title: maintableTitle,
              exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'csvHtml5',   title: maintableTitle,
              exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'pdfHtml5',   title: maintableTitle,
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

    // ── Modals ────────────────────────────────────────────────────────────
    var viewModal         = new bootstrap.Modal('#viewEntryModal');
    var editEntryModal    = new bootstrap.Modal('#editEntryModal');
    var wageDownloadModal = new bootstrap.Modal('#wageDownloadModal');

    $('#infoBtn').on('click',            function(e) { e.preventDefault(); $('#infoModal').modal('show'); });
    $('#tableButtonsBtn').on('click',    function(e) { e.preventDefault(); $('#buttonsModal').modal('show'); });
    $('#wageDownloadBtn').on('click',    function(e) { e.preventDefault(); wageDownloadModal.show(); });
    $('#cancelEditEntryBtn').on('click', function(e) { e.preventDefault(); editEntryModal.hide(); });

    // ── Number formatter ──────────────────────────────────────────────────
    function fmt(n) {
        return parseFloat(n || 0).toLocaleString('en', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // ── VIEW entry ────────────────────────────────────────────────────────
    $('#tbody').on('click', '.viewEntryBtn', function() {
        var d = $(this).data();
        $('#viewEmployeeNameTitle').text(d.employeeName);
        $('#vEmployeeName').text(d.employeeName);
        $('#vEmployeeNumber').text(d.employeeNumber || '—');
        $('#vBasic').text(fmt(d.basic));
        $('#vHousing').text(fmt(d.housing));
        $('#vTransport').text(fmt(d.transport));
        $('#vOtherAllowances').text(fmt(d.otherAllowances));
        $('#vOvertime').text(fmt(d.overtime));
        $('#vGross').text(fmt(d.gross));
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

    // ── EDIT entry — populate modal ───────────────────────────────────────
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

    // ── Live preview ──────────────────────────────────────────────────────
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

                    var e = data.entry;
                    var $row = $('#' + row);
                    // columns: Employee(0) BankName(1) AccountNo(2) Basic(3) Housing(4) Transport(5) OtherAllow(6) Overtime(7) Gross(8) PAYE(9) PensionEe(10) Loan(11) Advance(12) OtherDed(13) TotalDed(14) NetPay(15) Action(16)
                    $row.find('td').eq(3).text(fmt(e.basic_salary));
                    $row.find('td').eq(4).text(fmt(e.housing_allowance));
                    $row.find('td').eq(5).text(fmt(e.transport_allowance));
                    $row.find('td').eq(6).text(fmt(e.other_allowances));
                    $row.find('td').eq(7).text(fmt(e.overtime_amount));
                    $row.find('td').eq(8).html('<strong>' + fmt(e.gross_pay) + '</strong>');
                    $row.find('td').eq(9).text(fmt(e.paye));
                    $row.find('td').eq(10).text(fmt(e.pension_employee));
                    $row.find('td').eq(11).text(fmt(e.loan_deduction));
                    $row.find('td').eq(12).text(fmt(e.advance_deduction));
                    $row.find('td').eq(13).text(fmt(e.other_deductions));
                    $row.find('td').eq(14).html('<strong>' + fmt(e.total_deductions) + '</strong>');
                    $row.find('td').eq(15).html('<strong class="text-primary">' + fmt(e.net_pay) + '</strong>');

                    // Refresh data- attributes on action buttons
                    $row.find('.viewEntryBtn')
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

                    $row.find('.editEntryBtn')
                        .data('basic',            e.basic_salary)
                        .data('housing',          e.housing_allowance)
                        .data('transport',        e.transport_allowance)
                        .data('other-allowances', e.other_allowances)
                        .data('overtime',         e.overtime_amount)
                        .data('paye',             e.paye)
                        .data('pension-employee', e.pension_employee)
                        .data('pension-employer', e.pension_employer)
                        .data('loan',             e.loan_deduction)
                        .data('advance',          e.advance_deduction)
                        .data('other-deductions', e.other_deductions)
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