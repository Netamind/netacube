@extends('tenants.admin.dashboard')
@section('content')
@php
    $employees = DB::connection('tenant')->table('users')->where('active', 'Yes')->orderBy('name')->get();

    $advances = DB::connection('tenant')
        ->table('employee_advances')
        ->join('users', 'users.id', '=', 'employee_advances.employee_id')
        ->select(
            'employee_advances.*',
            'users.name       as employee_name',
            'users.phone      as employee_number',
            'users.position   as position',
            'users.department as department'
        )
        ->orderBy('users.name')
        ->get();

    $totalActive    = $advances->where('status', 'active')->count();
    $totalRecovered = $advances->where('status', 'recovered')->count();
    $totalCancelled = $advances->where('status', 'cancelled')->count();
    $totalOutstanding = $advances->where('status', 'active')->sum('balance_remaining');
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

table.dataTable.fixedHeader-floating,
table.dataTable.fixedHeader-locked { background: #fff !important; border-bottom: none !important; }
table.dataTable thead th.fixedHeader-floating { background: #e2e2e9 !important; }

.badge-active    { background: #198754; color: #fff; padding: 2px 9px; border-radius: 20px; font-size: 11px; }
.badge-recovered { background: #4B5EBD; color: #fff; padding: 2px 9px; border-radius: 20px; font-size: 11px; }
.badge-cancelled { background: #6c757d; color: #fff; padding: 2px 9px; border-radius: 20px; font-size: 11px; }

.mh-blue  { background: linear-gradient(135deg, #4B5EBD, #576CC0); padding: 14px 18px !important; border-bottom: none; border-radius: 8px 8px 0 0; }
.mh-title { color: #fff; font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.mh-close { filter: brightness(0) invert(1); opacity: .8; }
.mh-close:hover { opacity: 1; }

.modal-section-title {
  font-size: 11px; font-weight: 600; text-transform: uppercase;
  letter-spacing: .07em; color: #6c757d;
  border-bottom: 1px solid #e9ecef; padding-bottom: 6px; margin: 16px 0 10px;
}

.pf-group { background: #f8f9fa; border-radius: 6px; padding: 9px 12px; }
.pf-label { font-size: 11px; color: #6c757d; text-transform: uppercase; letter-spacing: .05em; }
.pf-value { font-size: 14px; font-weight: 600; color: #212529; }

.stats-card { border-radius: 10px; padding: 14px 18px; color: #fff; text-align: center; }
.stats-card .sc-label { font-size: 11px; opacity: .85; text-transform: uppercase; letter-spacing: .05em; }
.stats-card .sc-value { font-size: 26px; font-weight: 700; line-height: 1.3; }
.bg-sc1 { background: linear-gradient(135deg, #4B5EBD, #6c7fe0); }
.bg-sc2 { background: linear-gradient(135deg, #198754, #27c87e); }
.bg-sc3 { background: linear-gradient(135deg, #0dcaf0, #0891b2); }
.bg-sc4 { background: linear-gradient(135deg, #6c757d, #94a3b8); }
.bg-sc5 { background: linear-gradient(135deg, #dc3545, #f87171); }

.adv-progress { height: 6px; border-radius: 3px; background: #e9ecef; margin-top: 4px; }
.adv-progress-bar { height: 100%; border-radius: 3px; background: linear-gradient(to right, #0dcaf0, #0891b2); transition: width .4s; }
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
      <i class="ri-hand-coin-line me-1"></i> Salary Advances
    </h4>
    <div class="d-flex align-items-center">
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="statsBtn"        title="Statistics"><i class="ri-bar-chart-2-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn"         title="Info"><i class="ri-information-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Export Table"><i class="ri-table-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="newDataBtn"      title="Add Advance"><i class="ri-add-circle-line"></i></a>
    </div>
    <?php $maintableTitle = "Salary Advances"; ?>
  </div>

  <div class="card-body">
    <table id="maintable" class="table table-sm table-striped row-border order-column w-100">
      <thead style="background-color:#e2e2e9">
        <tr>
          <th>Employee</th>
          <th style="text-align:center">Position</th>
          <th style="text-align:center">Advance Amount</th>
          <th style="text-align:center">Monthly Ded.</th>
          <th style="text-align:center">Balance</th>
          <th style="text-align:center">Recovered</th>
          <th style="text-align:center">Advance Date</th>
          <th style="text-align:center">Status</th>
          <th style="text-align:center">Action</th>
        </tr>
      </thead>
      <tbody id="tbody">
        @foreach($advances as $a)
          <?php
            $row = 'row' . $a->id;
            $recovered = $a->advance_amount - $a->balance_remaining;
            $pct       = $a->advance_amount > 0 ? round(($recovered / $a->advance_amount) * 100) : 0;
          ?>
          <tr id="{{ $row }}">
            <td><strong>{{ $a->employee_name }}</strong></td>
            <td style="text-align:center">{{ $a->position ?? '—' }}</td>
            <td style="text-align:center">{{ number_format($a->advance_amount, 2) }}</td>
            <td style="text-align:center">{{ number_format($a->monthly_deduction, 2) }}</td>
            <td style="text-align:center">
              {{ number_format($a->balance_remaining, 2) }}
              <div class="adv-progress"><div class="adv-progress-bar" style="width:{{ $pct }}%"></div></div>
            </td>
            <td style="text-align:center">{{ $pct }}%</td>
            <td style="text-align:center">{{ \Carbon\Carbon::parse($a->advance_date)->format('d M Y') }}</td>
            <td style="text-align:center"><span class="badge-{{ $a->status }}">{{ ucfirst($a->status) }}</span></td>
            <td style="text-align:center; white-space:nowrap;">
              <a href="#" class="viewBtn"
                 data-id="{{ $a->id }}"
                 data-employee-name="{{ $a->employee_name }}"
                 data-position="{{ $a->position ?? '' }}"
                 data-department="{{ $a->department ?? '' }}"
                 data-advance-amount="{{ $a->advance_amount }}"
                 data-balance-remaining="{{ $a->balance_remaining }}"
                 data-monthly-deduction="{{ $a->monthly_deduction }}"
                 data-advance-date="{{ \Carbon\Carbon::parse($a->advance_date)->format('d M Y') }}"
                 data-approved-by="{{ $a->approved_by ?? '' }}"
                 data-status="{{ $a->status }}"
                 data-notes="{{ $a->notes ?? '' }}">
                <i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px;"></i>
              </a>
              <a href="#" class="editBtn"
                 data-id="{{ $a->id }}"
                 data-row="{{ $row }}"
                 data-employee-id="{{ $a->employee_id }}"
                 data-employee-name="{{ $a->employee_name }}"
                 data-advance-amount="{{ $a->advance_amount }}"
                 data-balance-remaining="{{ $a->balance_remaining }}"
                 data-monthly-deduction="{{ $a->monthly_deduction }}"
                 data-advance-date="{{ $a->advance_date }}"
                 data-approved-by="{{ $a->approved_by ?? '' }}"
                 data-status="{{ $a->status }}"
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


{{-- ══ STATISTICS MODAL ══ --}}
<div class="modal fade" id="statsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-bar-chart-2-line"></i>&nbsp; Advance Statistics</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="row g-3 mb-4">
          <div class="col-md col-6">
            <div class="stats-card bg-sc1">
              <div class="sc-label">Total Advances</div>
              <div class="sc-value">{{ $advances->count() }}</div>
            </div>
          </div>
          <div class="col-md col-6">
            <div class="stats-card bg-sc2">
              <div class="sc-label">Active</div>
              <div class="sc-value">{{ $totalActive }}</div>
            </div>
          </div>
          <div class="col-md col-6">
            <div class="stats-card bg-sc3">
              <div class="sc-label">Recovered</div>
              <div class="sc-value">{{ $totalRecovered }}</div>
            </div>
          </div>
          <div class="col-md col-6">
            <div class="stats-card bg-sc4">
              <div class="sc-label">Cancelled</div>
              <div class="sc-value">{{ $totalCancelled }}</div>
            </div>
          </div>
          <div class="col-md col-6">
            <div class="stats-card bg-sc5">
              <div class="sc-label">Outstanding Balance</div>
              <div class="sc-value" style="font-size:18px;">{{ number_format($totalOutstanding, 2) }}</div>
            </div>
          </div>
        </div>

        @if($advances->where('status','active')->isNotEmpty())
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:#6c757d;border-bottom:1px solid #e9ecef;padding-bottom:6px;margin-bottom:10px;">
          Active Advances
        </div>
        <div style="overflow-x:auto;">
          <table class="table table-sm table-striped" style="font-size:12px;">
            <thead>
              <tr>
                <th>Employee</th>
                <th class="text-center">Advance Amount</th>
                <th class="text-center">Balance</th>
                <th class="text-center">Monthly Ded.</th>
                <th class="text-center">Recovered %</th>
              </tr>
            </thead>
            <tbody>
              @foreach($advances->where('status','active') as $a)
              <?php $pct = $a->advance_amount > 0 ? round((($a->advance_amount - $a->balance_remaining) / $a->advance_amount) * 100) : 0; ?>
              <tr>
                <td><strong>{{ $a->employee_name }}</strong></td>
                <td class="text-center">{{ number_format($a->advance_amount, 2) }}</td>
                <td class="text-center">{{ number_format($a->balance_remaining, 2) }}</td>
                <td class="text-center">{{ number_format($a->monthly_deduction, 2) }}</td>
                <td class="text-center">{{ $pct }}%</td>
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


{{-- ══ INFO MODAL ══ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Salary Advances</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="font-size:13px;">
      <p>The <strong>Advances</strong> module tracks salary advances taken before payday and their monthly recovery deductions.</p>
      <p class="mb-1"><strong>How it works</strong></p>
      <ol class="mb-3" style="padding-left:18px;">
        <li class="mb-1">Record an advance for an employee with the total amount and a monthly recovery amount.</li>
        <li class="mb-1">During payroll generation, the active advance deduction is included automatically.</li>
        <li class="mb-1">When the period is <strong>Paid</strong>, the balance is reduced by the deduction.</li>
        <li class="mb-1">When balance reaches zero the advance is automatically marked <strong>Recovered</strong>.</li>
      </ol>
      <p class="mb-1"><strong>Statuses</strong></p>
      <ul class="mb-0" style="padding-left:18px;">
        <li class="mb-1"><strong>Active</strong> — Recovery deductions applied each payroll run.</li>
        <li class="mb-1"><strong>Recovered</strong> — Fully recovered; kept for history.</li>
        <li><strong>Cancelled</strong> — Advance cancelled; no further deductions.</li>
      </ul>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
    </div>
  </div></div>
</div>


{{-- ══ EXPORT MODAL ══ --}}
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Download</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <p class="mb-2">Click a button to download advance data.</p>
      <div class="buttons"></div>
    </div>
  </div></div>
</div>


{{-- ══ VIEW MODAL ══ --}}
<div class="modal fade" id="viewModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title">
          <i class="ri-hand-coin-line"></i>&nbsp; Advance Record &mdash; <span id="vTitle"></span>
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
        <div class="modal-section-title">Advance Details</div>
        <div class="row g-2 mb-2">
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Advance Amount</div><div class="pf-value" id="vAdvanceAmount">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Balance Remaining</div><div class="pf-value" id="vBalance">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Monthly Deduction</div><div class="pf-value" id="vMonthlyDed">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Advance Date</div><div class="pf-value" id="vAdvanceDate">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Approved By</div><div class="pf-value" id="vApprovedBy">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Status</div><div class="pf-value" id="vStatus">—</div></div></div>
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


{{-- ══ ADD MODAL ══ --}}
<div class="modal fade" id="newDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Salary Advance</h5>
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
          <div class="modal-section-title">Advance Details</div>
          <div class="row">
            <div class="form-group col-md-6 mb-3">
              <label style="font-size:13px;">Advance Amount <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="0" class="form-control" name="advance_amount" required>
            </div>
            <div class="form-group col-md-6 mb-3">
              <label style="font-size:13px;">Monthly Deduction <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="0" class="form-control" name="monthly_deduction" required>
            </div>
            <div class="form-group col-md-6 mb-3">
              <label style="font-size:13px;">Advance Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="advance_date" required>
            </div>
            <div class="form-group col-md-6 mb-3">
              <label style="font-size:13px;">Approved By</label>
              <input type="text" class="form-control" name="approved_by" placeholder="e.g. HR Manager">
            </div>
            <div class="form-group col-md-6 mb-3">
              <label style="font-size:13px;">Status</label>
              <select class="form-select" name="status">
                <option value="active">Active</option>
                <option value="recovered">Recovered</option>
                <option value="cancelled">Cancelled</option>
              </select>
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


{{-- ══ EDIT MODAL ══ --}}
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Advance — <span id="editTitle"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form action="#" method="post" id="editDataForm">
          @csrf
          <input type="hidden" name="id"  id="editId">
          <input type="hidden" name="row" id="editRow">
          <div class="modal-section-title" style="margin-top:0;">Advance Details</div>
          <div class="row">
            <div class="form-group col-md-6 mb-3">
              <label style="font-size:13px;">Advance Amount <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="0" class="form-control" name="advance_amount" id="editAdvanceAmount" required>
            </div>
            <div class="form-group col-md-6 mb-3">
              <label style="font-size:13px;">Balance Remaining <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="0" class="form-control" name="balance_remaining" id="editBalance" required>
            </div>
            <div class="form-group col-md-6 mb-3">
              <label style="font-size:13px;">Monthly Deduction <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="0" class="form-control" name="monthly_deduction" id="editMonthlyDed" required>
            </div>
            <div class="form-group col-md-6 mb-3">
              <label style="font-size:13px;">Advance Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="advance_date" id="editAdvanceDate" required>
            </div>
            <div class="form-group col-md-6 mb-3">
              <label style="font-size:13px;">Approved By</label>
              <input type="text" class="form-control" name="approved_by" id="editApprovedBy">
            </div>
            <div class="form-group col-md-6 mb-3">
              <label style="font-size:13px;">Status</label>
              <select class="form-select" name="status" id="editStatus">
                <option value="active">Active</option>
                <option value="recovered">Recovered</option>
                <option value="cancelled">Cancelled</option>
              </select>
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


{{-- ══ DELETE CONFIRM MODAL ══ --}}
<div class="modal fade" id="singleDeleteDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:350px; margin:1.75rem auto;">
    <div class="modal-content">
      <div class="modal-body text-center pb-4">
        <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
        <form action="#" method="post" id="singleDeleteDataForm">
          @csrf
          <h4>Delete advance for <span id="singleDisplayDeleteLabel" class="text-danger"></span>?</h4>
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

    toastr.options = { closeButton: true, progressBar: true, showMethod: 'slideDown', timeOut: 5000, allowHtml: true };

    var maintableTitle = @json($maintableTitle);

    var table = $('#maintable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
        fixedColumns: { left: 1 },
        scrollX: true,
        order: [[0, 'asc']],
        columnDefs: [{ targets: [8], orderable: false }],
        buttons: [
            { extend: 'excelHtml5', title: maintableTitle, exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'csvHtml5',   title: maintableTitle, exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'pdfHtml5',   title: maintableTitle, exportOptions: { columns: ':visible:not(:last-child)' },
              orientation: 'landscape', pageSize: 'A4',
              customize: function(doc) { doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split(''); }
            }
        ]
    });
    table.buttons().container().appendTo($('#buttonsModal .buttons'));

    $('#statsBtn').click(function(e)        { e.preventDefault(); $('#statsModal').modal('show'); });
    $('#infoBtn').click(function(e)         { e.preventDefault(); $('#infoModal').modal('show'); });
    $('#tableButtonsBtn').click(function(e) { e.preventDefault(); $('#buttonsModal').modal('show'); });
    $('#newDataBtn').click(function(e)      { e.preventDefault(); $('#newDataForm')[0].reset(); $('#newDataModal').modal('show'); });
    $('#cancelDataBtn').click(function(e)   { e.preventDefault(); $('#newDataForm')[0].reset(); $('#newDataModal').modal('hide'); });
    $('#cancelEditDataBtn').click(function(e){ e.preventDefault(); $('#editDataForm')[0].reset(); $('#editDataModal').modal('hide'); });
    $('#keepSingleDataBtn').click(function(e){ e.preventDefault(); toastr.info("Record is safe", "OK"); $('#singleDeleteDataModal').modal('hide'); });

    function fmt(n) { return parseFloat(n || 0).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function badgeHtml(status) { return '<span class="badge-' + status + '">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span>'; }

    function buildRow(a, row) {
        var recovered = parseFloat(a.advance_amount) - parseFloat(a.balance_remaining);
        var pct = a.advance_amount > 0 ? Math.round((recovered / a.advance_amount) * 100) : 0;
        var dateFmt = a.advance_date_fmt || a.advance_date;
        return '<tr id="' + row + '">'
            + '<td><strong>' + a.employee_name + '</strong></td>'
            + '<td style="text-align:center">' + (a.position || '—') + '</td>'
            + '<td style="text-align:center">' + fmt(a.advance_amount) + '</td>'
            + '<td style="text-align:center">' + fmt(a.monthly_deduction) + '</td>'
            + '<td style="text-align:center">' + fmt(a.balance_remaining)
            +   '<div class="adv-progress"><div class="adv-progress-bar" style="width:' + pct + '%"></div></div></td>'
            + '<td style="text-align:center">' + pct + '%</td>'
            + '<td style="text-align:center">' + dateFmt + '</td>'
            + '<td style="text-align:center">' + badgeHtml(a.status) + '</td>'
            + '<td style="text-align:center; white-space:nowrap;">'
            +   '<a href="#" class="viewBtn"'
            +     ' data-id="' + a.id + '"'
            +     ' data-employee-name="' + a.employee_name + '"'
            +     ' data-position="' + (a.position||'') + '"'
            +     ' data-department="' + (a.department||'') + '"'
            +     ' data-advance-amount="' + a.advance_amount + '"'
            +     ' data-balance-remaining="' + a.balance_remaining + '"'
            +     ' data-monthly-deduction="' + a.monthly_deduction + '"'
            +     ' data-advance-date="' + dateFmt + '"'
            +     ' data-approved-by="' + (a.approved_by||'') + '"'
            +     ' data-status="' + a.status + '"'
            +     ' data-notes="' + (a.notes||'') + '">'
            +     '<i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px;"></i></a> '
            +   '<a href="#" class="editBtn"'
            +     ' data-id="' + a.id + '" data-row="' + row + '"'
            +     ' data-employee-id="' + a.employee_id + '"'
            +     ' data-employee-name="' + a.employee_name + '"'
            +     ' data-advance-amount="' + a.advance_amount + '"'
            +     ' data-balance-remaining="' + a.balance_remaining + '"'
            +     ' data-monthly-deduction="' + a.monthly_deduction + '"'
            +     ' data-advance-date="' + a.advance_date + '"'
            +     ' data-approved-by="' + (a.approved_by||'') + '"'
            +     ' data-status="' + a.status + '"'
            +     ' data-notes="' + (a.notes||'') + '">'
            +     '<i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px;"></i></a> '
            +   '<a href="#" class="deleteBtn"'
            +     ' data-id="' + a.id + '" data-row="' + row + '"'
            +     ' data-name="' + a.employee_name + '">'
            +     '<i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px;"></i></a>'
            + '</td></tr>';
    }

    $('#tbody').on('click', '.viewBtn', function(e) {
        e.preventDefault();
        var d = $(this).data();
        $('#vTitle').text(d.employeeName);
        $('#vName').text(d.employeeName);
        $('#vPosition').text(d.position || '—');
        $('#vDepartment').text(d.department || '—');
        $('#vAdvanceAmount').text(fmt(d.advanceAmount));
        $('#vBalance').text(fmt(d.balanceRemaining));
        $('#vMonthlyDed').text(fmt(d.monthlyDeduction));
        $('#vAdvanceDate').text(d.advanceDate);
        $('#vApprovedBy').text(d.approvedBy || '—');
        $('#vStatus').html(badgeHtml(d.status));
        if (d.notes) { $('#vNotes').text(d.notes); $('#vNotesRow').show(); }
        else          { $('#vNotesRow').hide(); }
        $('#viewModal').modal('show');
    });

    $('#tbody').on('click', '.editBtn', function(e) {
        e.preventDefault();
        var d = $(this).data();
        $('#editId').val(d.id);
        $('#editRow').val(d.row);
        $('#editTitle').text(d.employeeName);
        $('#editAdvanceAmount').val(d.advanceAmount);
        $('#editBalance').val(d.balanceRemaining);
        $('#editMonthlyDed').val(d.monthlyDeduction);
        $('#editAdvanceDate').val(d.advanceDate);
        $('#editApprovedBy').val(d.approvedBy);
        $('#editStatus').val(d.status);
        $('#editNotes').val(d.notes);
        $('#editDataModal').modal('show');
    });

    $('#tbody').on('click', '.deleteBtn', function(e) {
        e.preventDefault();
        var d = $(this).data();
        $('#singleDeleteId').val(d.id);
        $('#singleDeleteRow').val(d.row);
        $('#singleDisplayDeleteLabel').text(d.name);
        $('#singleDeleteDataModal').modal('show');
    });

    $('#submitDataBtn').click(function(e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        $.ajax({
            type: 'POST',
            url:  '{{ route("tenant.admin.hr.advances.store", ["tenantName" => request()->route("tenantName")]) }}',
            data: $('#newDataForm').serialize(),
            timeout: 60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    var row = 'row' + data.advance.id;
                    table.row.add($(buildRow(data.advance, row))).draw(false);
                    $('#newDataModal').modal('hide');
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

    $('#submitUpdateDataBtn').click(function(e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        var row  = $('#editRow').val();
        $.ajax({
            type: 'POST',
            url:  '{{ route("tenant.admin.hr.advances.update", ["tenantName" => request()->route("tenantName")]) }}',
            data: $('#editDataForm').serialize(),
            timeout: 60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    table.row('#' + row).remove();
                    table.row.add($(buildRow(data.advance, row))).draw(false);
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

    $('#submitSingleDeleteDataBtn').click(function(e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        var row  = $('#singleDeleteRow').val();
        $.ajax({
            type: 'POST',
            url:  '{{ route("tenant.admin.hr.advances.delete", ["tenantName" => request()->route("tenantName")]) }}',
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