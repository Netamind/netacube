@extends('tenants.admin.dashboard')
@section('content')
@php
    $employees = DB::connection('tenant')->table('users')->where('active', 'Yes')->orderBy('name')->get();

    $allowances = DB::connection('tenant')
        ->table('employee_allowances')
        ->join('users', 'users.id', '=', 'employee_allowances.employee_id')
        ->select(
            'employee_allowances.*',
            'users.name        as employee_name',
            'users.phone       as employee_number',
            'users.position    as position',
            'users.department  as department'
        )
        ->orderBy('users.name')
        ->get();

    $totalWithAllowances  = $allowances->count();
    $totalWithRecurring   = $allowances->filter(fn($a) =>
        $a->housing_allowance + $a->transport_allowance +
        $a->medical_allowance + $a->meal_allowance +
        $a->other_recurring_allowance > 0
    )->count();
    $totalWithVariable    = $allowances->filter(fn($a) =>
        $a->acting_allowance + $a->commissions +
        $a->other_variable_allowance > 0
    )->count();
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
}
.card-body { padding: 0 1.5rem 1.5rem 1.5rem !important; }
.card-header .btn-light {
  height: 28px; padding: 0 10px;
  display: flex; align-items: center; justify-content: center; line-height: 1;
}
.card-header .btn-light:hover { background-color: #f8f9fa; transition: background-color 0.2s ease-in-out; }
.card { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; }
.card-header h4 { color: #fff; font-weight: 600; margin-bottom: 0; display: flex; align-items: center; }
.card-header h4 i { margin-right: 0.25rem; }
table.dataTable.fixedHeader-floating,
table.dataTable.fixedHeader-locked { background: #fff !important; border-bottom: none !important; }
table.dataTable thead th.fixedHeader-floating { background: #e2e2e9 !important; }
.badge-recurring { background:#4B5EBD; color:#fff; padding:2px 9px; border-radius:20px; font-size:11px; }
.badge-variable  { background:#f59e0b; color:#212529; padding:2px 9px; border-radius:20px; font-size:11px; }
.badge-none      { background:#e9ecef; color:#6c757d; padding:2px 9px; border-radius:20px; font-size:11px; }
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
.stats-card { border-radius:10px; padding:14px 18px; color:#fff; text-align:center; }
.stats-card .sc-label { font-size:11px; opacity:.85; text-transform:uppercase; letter-spacing:.05em; }
.stats-card .sc-value { font-size:26px; font-weight:700; line-height:1.3; }
.bg-sc1 { background:linear-gradient(135deg,#4B5EBD,#6c7fe0); }
.bg-sc2 { background:linear-gradient(135deg,#198754,#27c87e); }
.bg-sc3 { background:linear-gradient(135deg,#f59e0b,#fbbf24); }
.bg-sc3 .sc-label, .bg-sc3 .sc-value { color:#212529 !important; }
.bg-sc4 { background:linear-gradient(135deg,#6c757d,#94a3b8); }
.bg-sc5 { background:linear-gradient(135deg,#dc3545,#f87171); }
.tier-tag {
  display:inline-block; font-size:10px; font-weight:700;
  text-transform:uppercase; letter-spacing:.08em;
  padding:2px 8px; border-radius:4px; margin-bottom:6px;
}
.tier-tag.recurring { background:#eef0fc; color:#4B5EBD; }
.tier-tag.variable  { background:#fef3c7; color:#92400e; }
/* All non-first columns center-aligned */
#maintable td.num { text-align:center; font-variant-numeric: tabular-nums; }
#maintable th.num { text-align:center; }
</style>

<div class="progress" id="progressBar" role="progressbar"
     style="height:8px; transform:rotate(180deg); display:none">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
      <i class="ri-money-dollar-circle-line me-1"></i> Employee Allowances
    </h4>
    <div class="d-flex align-items-center">
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="statsBtn"        title="Statistics"><i class="ri-bar-chart-2-line"></i></a>
      <a href="{{ route('tenant.admin.hr.allowances.history', ['tenantName' => request()->route('tenantName')]) }}" class="btn btn-light text-primary fs-16 mx-1" title="Allowance History"><i class="ri-history-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn"         title="Info"><i class="ri-information-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Export Table"><i class="ri-table-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="newDataBtn"      title="Add Allowance Package"><i class="ri-add-circle-line"></i></a>
    </div>
    <?php $maintableTitle = "Employee Allowances"; ?>
  </div>

  <div class="card-body">
    <table id="maintable" class="table table-sm table-striped row-border order-column w-100">
      <thead style="background-color:#e2e2e9">
        <tr>
          <th>Employee</th>
          <th style="text-align:center">Position</th>
          {{-- Recurring --}}
          <th class="num">Housing</th>
          <th class="num">Transport</th>
          <th class="num">Medical</th>
          <th class="num">Meal</th>
          <th class="num">Other Rec.</th>
          {{-- Variable --}}
          <th class="num">Acting</th>
          <th class="num">Commission</th>
          <th class="num">Other Var.</th>
          {{-- Meta --}}
          <th style="text-align:center">Effective From</th>
          <th style="text-align:center">Action</th>
        </tr>
      </thead>
      <tbody id="tbody">
        @foreach($allowances as $a)
          <?php $row = 'row' . $a->id; ?>
          <tr id="{{ $row }}">
            <td><strong>{{ $a->employee_name }}</strong></td>
            <td style="text-align:center">{{ $a->position ?? '—' }}</td>
            {{-- Recurring --}}
            <td class="num">{{ number_format($a->housing_allowance,   2) }}</td>
            <td class="num">{{ number_format($a->transport_allowance, 2) }}</td>
            <td class="num">{{ number_format($a->medical_allowance,   2) }}</td>
            <td class="num">{{ number_format($a->meal_allowance,      2) }}</td>
            <td class="num">{{ number_format($a->other_recurring_allowance, 2) }}</td>
            {{-- Variable --}}
            <td class="num">{{ number_format($a->acting_allowance,        2) }}</td>
            <td class="num">{{ number_format($a->commissions,             2) }}</td>
            <td class="num">{{ number_format($a->other_variable_allowance,2) }}</td>
            {{-- Meta --}}
            <td style="text-align:center">{{ \Carbon\Carbon::parse($a->effective_from)->format('d M Y') }}</td>
            <td style="text-align:center; white-space:nowrap;">
              <a href="#" class="viewBtn"
                 data-id="{{ $a->id }}"
                 data-employee-name="{{ $a->employee_name }}"
                 data-position="{{ $a->position ?? '' }}"
                 data-department="{{ $a->department ?? '' }}"
                 data-housing="{{ $a->housing_allowance }}"
                 data-transport="{{ $a->transport_allowance }}"
                 data-medical="{{ $a->medical_allowance }}"
                 data-meal="{{ $a->meal_allowance }}"
                 data-other-recurring="{{ $a->other_recurring_allowance }}"
                 data-other-recurring-label="{{ $a->other_recurring_allowance_label ?? '' }}"
                 data-acting="{{ $a->acting_allowance }}"
                 data-commissions="{{ $a->commissions }}"
                 data-other-variable="{{ $a->other_variable_allowance }}"
                 data-other-variable-label="{{ $a->other_variable_allowance_label ?? '' }}"
                 data-variable-reset="{{ $a->variable_reset_on_generate ? '1' : '0' }}"
                 data-effective-from="{{ \Carbon\Carbon::parse($a->effective_from)->format('d M Y') }}"
                 data-notes="{{ $a->notes ?? '' }}">
                <i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px;"></i>
              </a>
              <a href="#" class="editBtn"
                 data-id="{{ $a->id }}"
                 data-row="{{ $row }}"
                 data-employee-id="{{ $a->employee_id }}"
                 data-employee-name="{{ $a->employee_name }}"
                 data-housing="{{ $a->housing_allowance }}"
                 data-transport="{{ $a->transport_allowance }}"
                 data-medical="{{ $a->medical_allowance }}"
                 data-meal="{{ $a->meal_allowance }}"
                 data-other-recurring="{{ $a->other_recurring_allowance }}"
                 data-other-recurring-label="{{ $a->other_recurring_allowance_label ?? '' }}"
                 data-acting="{{ $a->acting_allowance }}"
                 data-commissions="{{ $a->commissions }}"
                 data-other-variable="{{ $a->other_variable_allowance }}"
                 data-other-variable-label="{{ $a->other_variable_allowance_label ?? '' }}"
                 data-variable-reset="{{ $a->variable_reset_on_generate ? '1' : '0' }}"
                 data-effective-from="{{ $a->effective_from }}"
                 data-notes="{{ $a->notes ?? '' }}">
                <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px;"></i>
              </a>
              <a href="#" class="deleteBtn"
                 data-id="{{ $a->id }}"
                 data-row="{{ $row }}"
                 data-name="{{ $a->employee_name }}">
                <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px;"></i>
              </a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>

</div>
</div></div></div>


{{-- STATISTICS MODAL --}}
<div class="modal fade" id="statsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-bar-chart-2-line"></i>&nbsp; Allowances Statistics</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="row g-3 mb-4">
          <div class="col-md col-6">
            <div class="stats-card bg-sc1"><div class="sc-label">Total Packages</div><div class="sc-value">{{ $totalWithAllowances }}</div></div>
          </div>
          <div class="col-md col-6">
            <div class="stats-card bg-sc2"><div class="sc-label">With Recurring</div><div class="sc-value">{{ $totalWithRecurring }}</div></div>
          </div>
          <div class="col-md col-6">
            <div class="stats-card bg-sc3"><div class="sc-label">With Variable</div><div class="sc-value">{{ $totalWithVariable }}</div></div>
          </div>
          <div class="col-md col-6">
            <div class="stats-card bg-sc5"><div class="sc-label">Not Configured</div><div class="sc-value">{{ $employees->count() - $totalWithAllowances }}</div></div>
          </div>
        </div>
        @if($allowances->isNotEmpty())
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:#6c757d;border-bottom:1px solid #e9ecef;padding-bottom:6px;margin-bottom:10px;">
          Allowance Totals by Type
        </div>
        <div style="overflow-x:auto;">
          <table class="table table-sm table-striped" style="font-size:12px;">
            <thead><tr>
              <th>Allowance Type</th>
              <th class="text-center">Tier</th>
              <th class="text-center">Employees with Amount</th>
              <th class="text-center">Total Monthly</th>
            </tr></thead>
            <tbody>
              @foreach([
                ['Housing',           'recurring', 'housing_allowance'],
                ['Transport',         'recurring', 'transport_allowance'],
                ['Medical',           'recurring', 'medical_allowance'],
                ['Meal',              'recurring', 'meal_allowance'],
                ['Other (Recurring)', 'recurring', 'other_recurring_allowance'],
                ['Acting',            'variable',  'acting_allowance'],
                ['Commissions',       'variable',  'commissions'],
                ['Other (Variable)',  'variable',  'other_variable_allowance'],
              ] as [$label, $tier, $col])
              <tr>
                <td><strong>{{ $label }}</strong></td>
                <td class="text-center"><span class="badge-{{ $tier }}">{{ ucfirst($tier) }}</span></td>
                <td class="text-center">{{ $allowances->where($col, '>', 0)->count() }}</td>
                <td class="text-center">{{ number_format($allowances->sum($col), 2) }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @endif
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>



{{-- INFO MODAL --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Employee Allowances</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="font-size:13px;">
      <p>The <strong>Allowances</strong> module manages each employee's standing allowance package. One record per employee. Values here are automatically read when a payroll period is generated.</p>
      <p class="mb-1"><strong>Recurring Tier</strong></p>
      <ul class="mb-3" style="padding-left:18px;">
        <li class="mb-1">Set once and included in every payroll run automatically.</li>
        <li>Covers: Housing, Transport, Medical, Meal, and one Other (with a custom label).</li>
      </ul>
      <p class="mb-1"><strong>Variable Tier</strong></p>
      <ul class="mb-3" style="padding-left:18px;">
        <li class="mb-1">Set before each run for one-off or irregular payments.</li>
        <li class="mb-1">Covers: Acting Allowance, Commissions, and one Other (with a custom label).</li>
        <li><strong>Auto-reset:</strong> when <em>Reset after generate</em> is enabled, variable amounts are zeroed automatically after each payroll run.</li>
      </ul>
      <p class="mb-1"><strong>Gross Pay</strong></p>
      <ul class="mb-0" style="padding-left:18px;">
        <li>Basic Salary + All Allowances + Overtime = Gross Pay on the wage bill.</li>
      </ul>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
    </div>
  </div></div>
</div>


{{-- EXPORT MODAL --}}
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Download</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <p class="mb-2">Click a button to download allowance data.</p>
      <div class="buttons"></div>
    </div>
  </div></div>
</div>


{{-- VIEW MODAL --}}
<div class="modal fade" id="viewModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title">
          <i class="ri-money-dollar-circle-line"></i>&nbsp; Allowance Package &mdash; <span id="vTitle"></span>
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="modal-section-title" style="margin-top:0;">Employee</div>
        <div class="row g-2 mb-2">
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Name</div><div class="pf-value" id="vName">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Position</div><div class="pf-value" id="vPosition">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Department</div><div class="pf-value" id="vDepartment">—</div></div></div>
        </div>
        <div class="modal-section-title"><span class="tier-tag recurring">Recurring Tier</span></div>
        <div class="row g-2 mb-2">
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Housing</div><div class="pf-value" id="vHousing">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Transport</div><div class="pf-value" id="vTransport">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Medical</div><div class="pf-value" id="vMedical">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Meal</div><div class="pf-value" id="vMeal">—</div></div></div>
          <div class="col-md-4 col-6">
            <div class="pf-group">
              <div class="pf-label" id="vOtherRecurringLabel">Other (Recurring)</div>
              <div class="pf-value" id="vOtherRecurring">—</div>
            </div>
          </div>
        </div>
        <div class="modal-section-title"><span class="tier-tag variable">Variable Tier</span></div>
        <div class="row g-2 mb-2">
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Acting Allowance</div><div class="pf-value" id="vActing">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Commissions</div><div class="pf-value" id="vCommissions">—</div></div></div>
          <div class="col-md-4 col-6">
            <div class="pf-group">
              <div class="pf-label" id="vOtherVariableLabel">Other (Variable)</div>
              <div class="pf-value" id="vOtherVariable">—</div>
            </div>
          </div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Reset After Generate</div><div class="pf-value" id="vVariableReset">—</div></div></div>
        </div>
        <div class="modal-section-title">Package Info</div>
        <div class="row g-2">
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Effective From</div><div class="pf-value" id="vEffectiveFrom">—</div></div></div>
        </div>
        <div id="vNotesRow" style="display:none;">
          <div class="modal-section-title">Notes</div>
          <div class="pf-group"><div class="pf-value" id="vNotes" style="font-size:13px;font-weight:400;"></div></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


{{-- ADD MODAL --}}
<div class="modal fade" id="newDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Allowance Package</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form action="#" method="post" id="newDataForm">
          @csrf
          <div class="modal-section-title" style="margin-top:0;">Employee</div>
          <div class="row">
            <div class="form-group col-12 mb-3">
              <label style="font-size:13px;">Employee <span class="text-danger">*</span></label>
              <select class="form-select select2" name="employee_id" id="newEmployeeId" required>
                <option value="">— Select Employee —</option>
                @foreach($employees as $emp)
                  <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="modal-section-title"><span class="tier-tag recurring">Recurring Tier</span> — paid every month</div>
          <div class="row">
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Housing Allowance</label>
              <input type="number" step="0.01" min="0" class="form-control" name="housing_allowance" value="0">
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Transport Allowance</label>
              <input type="number" step="0.01" min="0" class="form-control" name="transport_allowance" value="0">
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Medical Allowance</label>
              <input type="number" step="0.01" min="0" class="form-control" name="medical_allowance" value="0">
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Meal Allowance</label>
              <input type="number" step="0.01" min="0" class="form-control" name="meal_allowance" value="0">
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Other (Recurring) Amount</label>
              <input type="number" step="0.01" min="0" class="form-control" name="other_recurring_allowance" value="0">
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Other (Recurring) Label</label>
              <input type="text" class="form-control" name="other_recurring_allowance_label" placeholder="e.g. Hardship Allowance">
            </div>
          </div>
          <div class="modal-section-title"><span class="tier-tag variable">Variable Tier</span> — set per run</div>
          <div class="row">
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Acting Allowance</label>
              <input type="number" step="0.01" min="0" class="form-control" name="acting_allowance" value="0">
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Commissions</label>
              <input type="number" step="0.01" min="0" class="form-control" name="commissions" value="0">
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Other (Variable) Amount</label>
              <input type="number" step="0.01" min="0" class="form-control" name="other_variable_allowance" value="0">
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Other (Variable) Label</label>
              <input type="text" class="form-control" name="other_variable_allowance_label" placeholder="e.g. Project Bonus">
            </div>
            <div class="form-group col-12 mb-3">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="variable_reset_on_generate" id="newVariableReset" value="1" checked>
                <label class="form-check-label" for="newVariableReset" style="font-size:13px;">
                  Reset variable amounts to 0 after each payroll generate
                </label>
              </div>
            </div>
          </div>
          <div class="modal-section-title">Package Info</div>
          <div class="row">
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Effective From <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="effective_from" required>
            </div>
            <div class="form-group col-12 mb-3">
              <label style="font-size:13px;">Notes</label>
              <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes…"></textarea>
            </div>
          </div>
          <a href="#" class="btn btn-primary float-end mt-3 mb-2" id="submitDataBtn">Submit</a>
          <a href="#" class="btn btn-secondary float-end mt-3 mb-2 mx-2" id="cancelDataBtn">Cancel</a>
        </form>
      </div>
    </div>
  </div>
</div>


{{-- EDIT MODAL --}}
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Allowances — <span id="editTitle"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form action="#" method="post" id="editDataForm">
          @csrf
          <input type="hidden" name="id"  id="editId">
          <input type="hidden" name="row" id="editRow">
          <div class="modal-section-title" style="margin-top:0;"><span class="tier-tag recurring">Recurring Tier</span> — paid every month</div>
          <div class="row">
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Housing Allowance</label>
              <input type="number" step="0.01" min="0" class="form-control" name="housing_allowance" id="editHousing">
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Transport Allowance</label>
              <input type="number" step="0.01" min="0" class="form-control" name="transport_allowance" id="editTransport">
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Medical Allowance</label>
              <input type="number" step="0.01" min="0" class="form-control" name="medical_allowance" id="editMedical">
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Meal Allowance</label>
              <input type="number" step="0.01" min="0" class="form-control" name="meal_allowance" id="editMeal">
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Other (Recurring) Amount</label>
              <input type="number" step="0.01" min="0" class="form-control" name="other_recurring_allowance" id="editOtherRecurring">
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Other (Recurring) Label</label>
              <input type="text" class="form-control" name="other_recurring_allowance_label" id="editOtherRecurringLabel">
            </div>
          </div>
          <div class="modal-section-title"><span class="tier-tag variable">Variable Tier</span> — set per run</div>
          <div class="row">
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Acting Allowance</label>
              <input type="number" step="0.01" min="0" class="form-control" name="acting_allowance" id="editActing">
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Commissions</label>
              <input type="number" step="0.01" min="0" class="form-control" name="commissions" id="editCommissions">
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Other (Variable) Amount</label>
              <input type="number" step="0.01" min="0" class="form-control" name="other_variable_allowance" id="editOtherVariable">
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Other (Variable) Label</label>
              <input type="text" class="form-control" name="other_variable_allowance_label" id="editOtherVariableLabel">
            </div>
            <div class="form-group col-12 mb-3">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="variable_reset_on_generate" id="editVariableReset" value="1">
                <label class="form-check-label" for="editVariableReset" style="font-size:13px;">
                  Reset variable amounts to 0 after each payroll generate
                </label>
              </div>
            </div>
          </div>
          <div class="modal-section-title">Package Info</div>
          <div class="row">
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Effective From <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="effective_from" id="editEffectiveFrom" required>
            </div>
            <div class="form-group col-12 mb-3">
              <label style="font-size:13px;">Notes</label>
              <textarea class="form-control" name="notes" id="editNotes" rows="2"></textarea>
            </div>
          </div>
          <a href="#" class="btn btn-primary float-end mt-3 mb-2" id="submitUpdateDataBtn">Submit</a>
          <a href="#" class="btn btn-secondary float-end mt-3 mb-2 mx-2" id="cancelEditDataBtn">Cancel</a>
        </form>
      </div>
    </div>
  </div>
</div>


{{-- DELETE MODAL --}}
<div class="modal fade" id="singleDeleteDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:350px; margin:1.75rem auto;">
    <div class="modal-content">
      <div class="modal-body text-center pb-4">
        <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
        <form action="#" method="post" id="singleDeleteDataForm">
          @csrf
          <h4>Are you sure you want to delete <span id="singleDisplayDeleteLabel" class="text-danger"></span>'s allowances?</h4>
          <h5>You won't be able to revert this!</h5>
          <input type="hidden" id="singleDeleteId"  name="id">
          <input type="hidden" id="singleDeleteRow">
          <a href="#" class="btn btn-danger"  id="submitSingleDeleteDataBtn" style="margin-top:10px;margin-bottom:10px;margin-right:5px">Yes, Delete it</a>
          <a href="#" class="btn btn-info"    id="keepSingleDataBtn"         style="margin-top:10px;margin-bottom:10px;">No, Keep it</a>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {

    toastr.options = { closeButton:true, progressBar:true, showMethod:'slideDown', timeOut:5000, allowHtml:true };

    var maintableTitle = @json($maintableTitle);

    var table = $('#maintable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
        fixedColumns: { left: 1 },
        scrollX: true,
        order: [[0, 'asc']],
        columnDefs: [{ targets: [11], orderable: false }],
        buttons: [
            { extend: 'excelHtml5', title: maintableTitle, exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'csvHtml5',   title: maintableTitle, exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'pdfHtml5',   title: maintableTitle, exportOptions: { columns: ':visible:not(:last-child)' },
              orientation: 'landscape', pageSize: 'A4',
              customize: function(doc) {
                  doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
              }
            }
        ]
    });
    table.buttons().container().appendTo($('#buttonsModal .buttons'));

    // ── Toolbar ───────────────────────────────────────────────────────────
    $('#statsBtn').click(function(e)         { e.preventDefault(); $('#statsModal').modal('show'); });
    $('#infoBtn').click(function(e)          { e.preventDefault(); $('#infoModal').modal('show'); });
    $('#tableButtonsBtn').click(function(e)  { e.preventDefault(); $('#buttonsModal').modal('show'); });
    $('#newDataBtn').click(function(e)       { e.preventDefault(); $('#newDataForm')[0].reset(); $('#newVariableReset').prop('checked', true); $('#newDataModal').modal('show'); });
    $('#cancelDataBtn').click(function(e)    { e.preventDefault(); $('#newDataForm')[0].reset(); $('#newDataModal').modal('hide'); });
    $('#cancelEditDataBtn').click(function(e){ e.preventDefault(); $('#editDataForm')[0].reset(); $('#editDataModal').modal('hide'); });
    $('#keepSingleDataBtn').click(function(e){ e.preventDefault(); toastr.info("Record kept.", "OK"); $('#singleDeleteDataModal').modal('hide'); });

    // ── Helpers ───────────────────────────────────────────────────────────
    function fmt(val) {
        return parseFloat(val || 0).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
    }

    // ── Build row HTML ─────────────────────────────────────────────────────
    function buildRow(a, row) {
        return '<tr id="' + row + '">'
            + '<td><strong>' + a.employee_name + '</strong></td>'
            + '<td style="text-align:center">' + (a.position || '—') + '</td>'
            // Recurring
            + '<td class="num">' + fmt(a.housing_allowance)          + '</td>'
            + '<td class="num">' + fmt(a.transport_allowance)        + '</td>'
            + '<td class="num">' + fmt(a.medical_allowance)          + '</td>'
            + '<td class="num">' + fmt(a.meal_allowance)             + '</td>'
            + '<td class="num">' + fmt(a.other_recurring_allowance)  + '</td>'
            // Variable
            + '<td class="num">' + fmt(a.acting_allowance)           + '</td>'
            + '<td class="num">' + fmt(a.commissions)                + '</td>'
            + '<td class="num">' + fmt(a.other_variable_allowance)   + '</td>'
            // Meta
            + '<td style="text-align:center">' + a.effective_from_fmt + '</td>'
            + '<td style="text-align:center; white-space:nowrap;">'
            +   '<a href="#" class="viewBtn"'
            +     ' data-id="' + a.id + '"'
            +     ' data-employee-name="' + a.employee_name + '"'
            +     ' data-position="' + (a.position||'') + '"'
            +     ' data-department="' + (a.department||'') + '"'
            +     ' data-housing="' + a.housing_allowance + '"'
            +     ' data-transport="' + a.transport_allowance + '"'
            +     ' data-medical="' + a.medical_allowance + '"'
            +     ' data-meal="' + a.meal_allowance + '"'
            +     ' data-other-recurring="' + a.other_recurring_allowance + '"'
            +     ' data-other-recurring-label="' + (a.other_recurring_allowance_label||'') + '"'
            +     ' data-acting="' + a.acting_allowance + '"'
            +     ' data-commissions="' + a.commissions + '"'
            +     ' data-other-variable="' + a.other_variable_allowance + '"'
            +     ' data-other-variable-label="' + (a.other_variable_allowance_label||'') + '"'
            +     ' data-variable-reset="' + (a.variable_reset_on_generate ? '1' : '0') + '"'
            +     ' data-effective-from="' + a.effective_from_fmt + '"'
            +     ' data-notes="' + (a.notes||'') + '">'
            +     '<i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px;"></i></a> '
            +   '<a href="#" class="editBtn"'
            +     ' data-id="' + a.id + '" data-row="' + row + '"'
            +     ' data-employee-id="' + a.employee_id + '"'
            +     ' data-employee-name="' + a.employee_name + '"'
            +     ' data-housing="' + a.housing_allowance + '"'
            +     ' data-transport="' + a.transport_allowance + '"'
            +     ' data-medical="' + a.medical_allowance + '"'
            +     ' data-meal="' + a.meal_allowance + '"'
            +     ' data-other-recurring="' + a.other_recurring_allowance + '"'
            +     ' data-other-recurring-label="' + (a.other_recurring_allowance_label||'') + '"'
            +     ' data-acting="' + a.acting_allowance + '"'
            +     ' data-commissions="' + a.commissions + '"'
            +     ' data-other-variable="' + a.other_variable_allowance + '"'
            +     ' data-other-variable-label="' + (a.other_variable_allowance_label||'') + '"'
            +     ' data-variable-reset="' + (a.variable_reset_on_generate ? '1' : '0') + '"'
            +     ' data-effective-from="' + a.effective_from + '"'
            +     ' data-notes="' + (a.notes||'') + '">'
            +     '<i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px;"></i></a> '
            +   '<a href="#" class="deleteBtn"'
            +     ' data-id="' + a.id + '" data-row="' + row + '"'
            +     ' data-name="' + a.employee_name + '">'
            +     '<i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px;"></i></a>'
            + '</td></tr>';
    }

    // ── VIEW ──────────────────────────────────────────────────────────────
    $('#tbody').on('click', '.viewBtn', function(e) {
        e.preventDefault();
        var d = $(this).data();
        $('#vTitle').text(d.employeeName);
        $('#vName').text(d.employeeName);
        $('#vPosition').text(d.position || '—');
        $('#vDepartment').text(d.department || '—');
        $('#vHousing').text(fmt(d.housing));
        $('#vTransport').text(fmt(d.transport));
        $('#vMedical').text(fmt(d.medical));
        $('#vMeal').text(fmt(d.meal));
        $('#vOtherRecurring').text(fmt(d.otherRecurring));
        $('#vOtherRecurringLabel').text(d.otherRecurringLabel || 'Other (Recurring)');
        $('#vActing').text(fmt(d.acting));
        $('#vCommissions').text(fmt(d.commissions));
        $('#vOtherVariable').text(fmt(d.otherVariable));
        $('#vOtherVariableLabel').text(d.otherVariableLabel || 'Other (Variable)');
        $('#vVariableReset').text(d.variableReset == '1' ? 'Yes' : 'No');
        $('#vEffectiveFrom').text(d.effectiveFrom);
        if (d.notes) { $('#vNotes').text(d.notes); $('#vNotesRow').show(); }
        else          { $('#vNotesRow').hide(); }
        $('#viewModal').modal('show');
    });

    // ── EDIT — populate ───────────────────────────────────────────────────
    $('#tbody').on('click', '.editBtn', function(e) {
        e.preventDefault();
        var d = $(this).data();
        $('#editId').val(d.id);
        $('#editRow').val(d.row);
        $('#editTitle').text(d.employeeName);
        $('#editHousing').val(d.housing);
        $('#editTransport').val(d.transport);
        $('#editMedical').val(d.medical);
        $('#editMeal').val(d.meal);
        $('#editOtherRecurring').val(d.otherRecurring);
        $('#editOtherRecurringLabel').val(d.otherRecurringLabel);
        $('#editActing').val(d.acting);
        $('#editCommissions').val(d.commissions);
        $('#editOtherVariable').val(d.otherVariable);
        $('#editOtherVariableLabel').val(d.otherVariableLabel);
        $('#editVariableReset').prop('checked', d.variableReset == '1');
        $('#editEffectiveFrom').val(d.effectiveFrom);
        $('#editNotes').val(d.notes);
        $('#editDataModal').modal('show');
    });

    // ── DELETE — open ─────────────────────────────────────────────────────
    $('#tbody').on('click', '.deleteBtn', function(e) {
        e.preventDefault();
        var d = $(this).data();
        $('#singleDeleteId').val(d.id);
        $('#singleDeleteRow').val(d.row);
        $('#singleDisplayDeleteLabel').text(d.name);
        $('#singleDeleteDataModal').modal('show');
    });

    // ── ADD — submit ──────────────────────────────────────────────────────
    $('#submitDataBtn').click(function(e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        $.ajax({
            type: 'POST',
            url:  '{{ route("tenant.admin.hr.allowances.store", ["tenantName" => request()->route("tenantName")]) }}',
            data: $('#newDataForm').serialize(),
            timeout: 60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    var row = 'row' + data.allowance.id;
                    table.row.add($(buildRow(data.allowance, row))).draw(false);
                    $('#newDataModal').modal('hide');
                    $('#newDataForm')[0].reset();
                } else if (data.status === 422) {
                    var msg = ''; $.each(data.errors, function(k, v) { msg += v + '\n'; });
                    toastr.error(msg, 'Validation');
                } else { toastr.error(data.error || 'Failed.', 'Error'); }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var msg = ''; $.each(xhr.responseJSON.errors, function(k, v) { msg += v + '\n'; });
                    toastr.error(msg, 'Validation');
                } else { toastr.error('Server error.', 'Error'); }
            }
        });
    });

    // ── EDIT — submit ─────────────────────────────────────────────────────
    $('#submitUpdateDataBtn').click(function(e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        var row  = $('#editRow').val();
        $.ajax({
            type: 'POST',
            url:  '{{ route("tenant.admin.hr.allowances.update", ["tenantName" => request()->route("tenantName")]) }}',
            data: $('#editDataForm').serialize(),
            timeout: 60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    table.row('#' + row).remove();
                    table.row.add($(buildRow(data.allowance, row))).draw(false);
                    $('#editDataModal').modal('hide');
                } else if (data.status === 422) {
                    var msg = ''; $.each(data.errors, function(k, v) { msg += v + '\n'; });
                    toastr.error(msg, 'Validation');
                } else { toastr.error(data.error || 'Failed.', 'Error'); }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var msg = ''; $.each(xhr.responseJSON.errors, function(k, v) { msg += v + '\n'; });
                    toastr.error(msg, 'Validation');
                } else { toastr.error('Server error.', 'Error'); }
            }
        });
    });

    // ── DELETE — confirm ──────────────────────────────────────────────────
    $('#submitSingleDeleteDataBtn').click(function(e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        var row  = $('#singleDeleteRow').val();
        $.ajax({
            type: 'POST',
            url:  '{{ route("tenant.admin.hr.allowances.delete", ["tenantName" => request()->route("tenantName")]) }}',
            data: { _token: '{{ csrf_token() }}', id: $('#singleDeleteId').val() },
            timeout: 60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Deleted');
                    table.row('#' + row).remove().draw(false);
                    $('#singleDeleteDataModal').modal('hide');
                } else { toastr.error(data.error || 'Failed.', 'Error'); }
            },
            error: function() { toastr.error('Server error.', 'Error'); }
        });
    });

});
</script>
@endsection