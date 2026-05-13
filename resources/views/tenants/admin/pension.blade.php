@extends('tenants.admin.dashboard')
@section('content')
@php
    $employees = DB::connection('tenant')->table('users')->where('active', 'Yes')->orderBy('name')->get();

    $pension = DB::connection('tenant')
        ->table('employee_pension')
        ->join('users', 'users.id', '=', 'employee_pension.employee_id')
        ->select(
            'employee_pension.*',
            'users.name        as employee_name',
            'users.phone       as employee_number',
            'users.position    as position',
            'users.department  as department'
        )
        ->orderBy('users.name')
        ->get();

    $totalActive    = $pension->where('status', 'active')->count();
    $totalSuspended = $pension->where('status', 'suspended')->count();
    $totalExited    = $pension->where('status', 'exited')->count();
@endphp

<style>
/* ── DataTable export buttons ───────────────────────────────────────────── */
.dt-buttons .btn {
  background: transparent !important;
  background-image: none !important;
  box-shadow: none !important;
  border-color: #5bc0de;
  color: #5bc0de;
}
.dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }

/* ── Card chrome ────────────────────────────────────────────────────────── */
.card-header {
  padding: 0.5rem 1.5rem !important;
  background: linear-gradient(to right, #4B5EBD, #576CC0);
  color: #fff;
}
.card-body { padding: 0 1.5rem 1.5rem 1.5rem !important; }
.card-header .btn-light {
  height: 28px; padding: 0 10px;
  display: flex; align-items: center;
  justify-content: center; line-height: 1;
}
.card-header .btn-light:hover { background-color: #f8f9fa; transition: background-color 0.2s ease-in-out; }
.card { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; }
.card-header h4 { color: #fff; font-weight: 600; margin-bottom: 0; display: flex; align-items: center; }
.card-header h4 i { margin-right: 0.25rem; }

/* ── Fixed column DataTable overrides ──────────────────────────────────── */
table.dataTable.fixedHeader-floating,
table.dataTable.fixedHeader-locked { background: #fff !important; border-bottom: none !important; }
table.dataTable thead th.fixedHeader-floating { background: #e2e2e9 !important; }

/* ── Status badges ──────────────────────────────────────────────────────── */
.badge-active    { background:#198754; color:#fff; padding:2px 9px; border-radius:20px; font-size:11px; }
.badge-suspended { background:#ffc107; color:#212529; padding:2px 9px; border-radius:20px; font-size:11px; }
.badge-exited    { background:#6c757d; color:#fff; padding:2px 9px; border-radius:20px; font-size:11px; }

/* ── Modal header helpers ───────────────────────────────────────────────── */
.mh-blue  { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-title { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }

/* ── Section titles ─────────────────────────────────────────────────────── */
.modal-section-title {
  font-size:11px; font-weight:600; text-transform:uppercase;
  letter-spacing:.07em; color:#6c757d;
  border-bottom:1px solid #e9ecef; padding-bottom:6px; margin:16px 0 10px;
}

/* ── Field groups (view modal) ──────────────────────────────────────────── */
.pf-group { background:#f8f9fa; border-radius:6px; padding:9px 12px; }
.pf-label { font-size:11px; color:#6c757d; text-transform:uppercase; letter-spacing:.05em; }
.pf-value { font-size:14px; font-weight:600; color:#212529; }

/* ── Stats summary cards (stats modal) ─────────────────────────────────── */
.stats-card { border-radius:10px; padding:14px 18px; color:#fff; text-align:center; }
.stats-card .sc-label { font-size:11px; opacity:.85; text-transform:uppercase; letter-spacing:.05em; }
.stats-card .sc-value { font-size:26px; font-weight:700; line-height:1.3; }
.bg-sc1 { background:linear-gradient(135deg,#4B5EBD,#6c7fe0); }
.bg-sc2 { background:linear-gradient(135deg,#198754,#27c87e); }
.bg-sc3 { background:linear-gradient(135deg,#ffc107,#f59e0b); }
.bg-sc3 .sc-label, .bg-sc3 .sc-value { color:#212529 !important; }
.bg-sc4 { background:linear-gradient(135deg,#6c757d,#94a3b8); }
.bg-sc5 { background:linear-gradient(135deg,#dc3545,#f87171); }
</style>

<div class="progress" id="progressBar" role="progressbar"
     style="height:8px; transform:rotate(180deg); display:none">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

  {{-- ── Card header ─────────────────────────────────────────────────────── --}}
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
      <i class="ri-shield-check-line me-1"></i> Pension Enrollment
    </h4>
    <div class="d-flex align-items-center">
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="statsBtn"        title="Statistics"><i class="ri-bar-chart-2-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn"         title="Info"><i class="ri-information-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Export Table"><i class="ri-table-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="newDataBtn"      title="Enroll Employee"><i class="ri-add-circle-line"></i></a>
    </div>
    <?php $maintableTitle = "Pension Enrollment"; ?>
  </div>

  {{-- ── Table ────────────────────────────────────────────────────────────── --}}
  <div class="card-body">
    <table id="maintable" class="table table-sm table-striped row-border order-column w-100">
      <thead style="background-color:#e2e2e9">
        <tr>
          <th>Employee</th>
          <th style="text-align:center">Position</th>
          <th style="text-align:center">Pension Fund</th>
          <th style="text-align:center">Member No.</th>
          <th style="text-align:center">Ee Rate %</th>
          <th style="text-align:center">Er Rate %</th>
          <th style="text-align:center">Enrolled On</th>
          <th style="text-align:center">Status</th>
          <th style="text-align:center">Action</th>
        </tr>
      </thead>
      <tbody id="tbody">
        @foreach($pension as $p)
          <?php $row = 'row' . $p->id; ?>
          <tr id="{{ $row }}">
            <td><strong>{{ $p->employee_name }}</strong></td>
            <td style="text-align:center">{{ $p->position ?? '—' }}</td>
            <td style="text-align:center">{{ $p->pension_fund_name ?? '—' }}</td>
            <td style="text-align:center">{{ $p->pension_member_number ?? '—' }}</td>
            <td style="text-align:center">{{ $p->employee_rate }}%</td>
            <td style="text-align:center">{{ $p->employer_rate }}%</td>
            <td style="text-align:center">{{ \Carbon\Carbon::parse($p->enrolled_on)->format('d M Y') }}</td>
            <td style="text-align:center"><span class="badge-{{ $p->status }}">{{ ucfirst($p->status) }}</span></td>
            <td style="text-align:center; white-space:nowrap;">
              <a href="#" class="viewBtn"
                 data-id="{{ $p->id }}"
                 data-employee-name="{{ $p->employee_name }}"
                 data-position="{{ $p->position ?? '' }}"
                 data-department="{{ $p->department ?? '' }}"
                 data-pension-fund="{{ $p->pension_fund_name ?? '' }}"
                 data-member-number="{{ $p->pension_member_number ?? '' }}"
                 data-employee-rate="{{ $p->employee_rate }}"
                 data-employer-rate="{{ $p->employer_rate }}"
                 data-enrolled-on="{{ \Carbon\Carbon::parse($p->enrolled_on)->format('d M Y') }}"
                 data-status="{{ $p->status }}"
                 data-notes="{{ $p->notes ?? '' }}">
                <i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px;"></i>
              </a>
              <a href="#" class="editBtn"
                 data-id="{{ $p->id }}"
                 data-row="{{ $row }}"
                 data-employee-id="{{ $p->employee_id }}"
                 data-employee-name="{{ $p->employee_name }}"
                 data-pension-fund="{{ $p->pension_fund_name ?? '' }}"
                 data-member-number="{{ $p->pension_member_number ?? '' }}"
                 data-employee-rate="{{ $p->employee_rate }}"
                 data-employer-rate="{{ $p->employer_rate }}"
                 data-enrolled-on="{{ $p->enrolled_on }}"
                 data-status="{{ $p->status }}"
                 data-notes="{{ $p->notes ?? '' }}">
                <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px;"></i>
              </a>
              <a href="#" class="deleteBtn"
                 data-id="{{ $p->id }}"
                 data-row="{{ $row }}"
                 data-name="{{ $p->employee_name }}">
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


{{-- ══════════════════════════════════════════════════════════════════
     STATISTICS MODAL
══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="statsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-bar-chart-2-line"></i>&nbsp; Pension Statistics</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="row g-3 mb-4">
          <div class="col-md col-6">
            <div class="stats-card bg-sc1">
              <div class="sc-label">Total Enrolled</div>
              <div class="sc-value">{{ $pension->count() }}</div>
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
              <div class="sc-label">Suspended</div>
              <div class="sc-value">{{ $totalSuspended }}</div>
            </div>
          </div>
          <div class="col-md col-6">
            <div class="stats-card bg-sc4">
              <div class="sc-label">Exited</div>
              <div class="sc-value">{{ $totalExited }}</div>
            </div>
          </div>
          <div class="col-md col-6">
            <div class="stats-card bg-sc5">
              <div class="sc-label">Not Enrolled</div>
              <div class="sc-value">{{ $employees->count() - $pension->count() }}</div>
            </div>
          </div>
        </div>

        @if($pension->isNotEmpty())
        <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.07em;color:#6c757d;border-bottom:1px solid #e9ecef;padding-bottom:6px;margin-bottom:10px;">
          Active Members by Pension Fund
        </div>
        <div style="overflow-x:auto;">
          <table class="table table-sm table-striped" style="font-size:12px;">
            <thead>
              <tr>
                <th>Pension Fund</th>
                <th class="text-center">Members</th>
                <th class="text-center">Avg Ee Rate</th>
                <th class="text-center">Avg Er Rate</th>
              </tr>
            </thead>
            <tbody>
              @foreach($pension->where('status','active')->groupBy('pension_fund_name') as $fund => $members)
              <tr>
                <td><strong>{{ $fund ?: '(No fund name)' }}</strong></td>
                <td class="text-center">{{ $members->count() }}</td>
                <td class="text-center">{{ number_format($members->avg('employee_rate'), 2) }}%</td>
                <td class="text-center">{{ number_format($members->avg('employer_rate'), 2) }}%</td>
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


{{-- ══════════════════════════════════════════════════════════════════
     INFO MODAL
══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Pension Enrollment</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="font-size:13px;">
      <p>The <strong>Pension</strong> module manages employee pension fund enrollments. Each employee can have one pension record.</p>
      <p class="mb-1"><strong>How it works</strong></p>
      <ol class="mb-3" style="padding-left:18px;">
        <li class="mb-1">Enroll an active employee and set their fund name, member number, and contribution rates.</li>
        <li class="mb-1">When a payroll period is <strong>Generated</strong>, the system automatically computes pension deductions based on the employee rate and basic salary.</li>
        <li class="mb-1">Employer contributions are stored informally and are not deducted from net pay.</li>
      </ol>
      <p class="mb-1"><strong>Rates</strong></p>
      <ul class="mb-3" style="padding-left:18px;">
        <li class="mb-1"><strong>Employee Rate (Ee)</strong> — % deducted from the employee's gross pay each month.</li>
        <li><strong>Employer Rate (Er)</strong> — % contributed by the employer (informational only).</li>
      </ul>
      <p class="mb-1"><strong>Statuses</strong></p>
      <ul class="mb-0" style="padding-left:18px;">
        <li class="mb-1"><strong>Active</strong> — Deductions applied each payroll run.</li>
        <li class="mb-1"><strong>Suspended</strong> — Record kept but deductions paused.</li>
        <li><strong>Exited</strong> — Employee has left the scheme.</li>
      </ul>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
    </div>
  </div></div>
</div>


{{-- ══════════════════════════════════════════════════════════════════
     EXPORT MODAL
══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Download</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <p class="mb-2">Click a button to download pension enrollment data.</p>
      <div class="buttons"></div>
    </div>
  </div></div>
</div>


{{-- ══════════════════════════════════════════════════════════════════
     VIEW MODAL
══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="viewModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title">
          <i class="ri-shield-check-line"></i>&nbsp; Pension Record &mdash; <span id="vTitle"></span>
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
        <div class="modal-section-title">Pension Details</div>
        <div class="row g-2 mb-2">
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Pension Fund</div><div class="pf-value" id="vFund">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Member Number</div><div class="pf-value" id="vMemberNo">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Enrolled On</div><div class="pf-value" id="vEnrolledOn">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Employee Rate</div><div class="pf-value" id="vEeRate">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Employer Rate</div><div class="pf-value" id="vErRate">—</div></div></div>
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


{{-- ══════════════════════════════════════════════════════════════════
     ADD MODAL
══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="newDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Enroll Employee in Pension</h5>
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
          <div class="modal-section-title">Pension Details</div>
          <div class="row">
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Pension Fund Name</label>
              <input type="text" class="form-control" name="pension_fund_name" placeholder="e.g. NICO Life">
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Member Number</label>
              <input type="text" class="form-control" name="pension_member_number" placeholder="e.g. PF-00123">
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Employee Rate (%) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="0" max="100" class="form-control" name="employee_rate" value="5.00" required>
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Employer Rate (%) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="0" max="100" class="form-control" name="employer_rate" value="10.00" required>
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Enrolled On <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="enrolled_on" required>
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Status</label>
              <select class="form-select" name="status">
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
                <option value="exited">Exited</option>
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


{{-- ══════════════════════════════════════════════════════════════════
     EDIT MODAL
══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Pension — <span id="editTitle"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form action="#" method="post" id="editDataForm">
          @csrf
          <input type="hidden" name="id"  id="editId">
          <input type="hidden" name="row" id="editRow">
          <div class="modal-section-title" style="margin-top:0;">Pension Details</div>
          <div class="row">
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Pension Fund Name</label>
              <input type="text" class="form-control" name="pension_fund_name" id="editFund">
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Member Number</label>
              <input type="text" class="form-control" name="pension_member_number" id="editMemberNo">
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Employee Rate (%) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="0" max="100" class="form-control" name="employee_rate" id="editEeRate" required>
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Employer Rate (%) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="0" max="100" class="form-control" name="employer_rate" id="editErRate" required>
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Enrolled On <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="enrolled_on" id="editEnrolledOn" required>
            </div>
            <div class="form-group col-6 mb-3">
              <label style="font-size:13px;">Status</label>
              <select class="form-select" name="status" id="editStatus">
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
                <option value="exited">Exited</option>
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


{{-- ══════════════════════════════════════════════════════════════════
     DELETE CONFIRM MODAL
══════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="singleDeleteDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:350px; margin:1.75rem auto;">
    <div class="modal-content">
      <div class="modal-body text-center pb-4">
        <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
        <form action="#" method="post" id="singleDeleteDataForm">
          @csrf
          <h4>Are you sure you want to delete <span id="singleDisplayDeleteLabel" class="text-danger"></span>?</h4>
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

    toastr.options = {
        closeButton: true,
        progressBar: true,
        showMethod: 'slideDown',
        timeOut: 5000,
        allowHtml: true
    };

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
              customize: function(doc) {
                  doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
              }
            }
        ]
    });
    table.buttons().container().appendTo($('#buttonsModal .buttons'));

    // ── Toolbar ───────────────────────────────────────────────────────────
    $('#statsBtn').click(function(e)        { e.preventDefault(); $('#statsModal').modal('show'); });
    $('#infoBtn').click(function(e)         { e.preventDefault(); $('#infoModal').modal('show'); });
    $('#tableButtonsBtn').click(function(e) { e.preventDefault(); $('#buttonsModal').modal('show'); });
    $('#newDataBtn').click(function(e)      { e.preventDefault(); $('#newDataForm')[0].reset(); $('#newDataModal').modal('show'); });
    $('#cancelDataBtn').click(function(e)   { e.preventDefault(); $('#newDataForm')[0].reset(); $('#newDataModal').modal('hide'); });
    $('#cancelEditDataBtn').click(function(e){ e.preventDefault(); $('#editDataForm')[0].reset(); $('#editDataModal').modal('hide'); });
    $('#keepSingleDataBtn').click(function(e){ e.preventDefault(); toastr.info("Your record is safe", "Great!"); $('#singleDeleteDataModal').modal('hide'); });

    // ── Badge HTML helper ─────────────────────────────────────────────────
    function badgeHtml(status) {
        return '<span class="badge-' + status + '">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span>';
    }

    // ── Build row HTML ────────────────────────────────────────────────────
    function buildRow(p, row) {
        var enrolledFmt = p.enrolled_on_fmt || p.enrolled_on;
        return '<tr id="' + row + '">'
            + '<td><strong>' + p.employee_name + '</strong></td>'
            + '<td style="text-align:center">' + (p.position || '—') + '</td>'
            + '<td style="text-align:center">' + (p.pension_fund_name || '—') + '</td>'
            + '<td style="text-align:center">' + (p.pension_member_number || '—') + '</td>'
            + '<td style="text-align:center">' + parseFloat(p.employee_rate).toFixed(2) + '%</td>'
            + '<td style="text-align:center">' + parseFloat(p.employer_rate).toFixed(2) + '%</td>'
            + '<td style="text-align:center">' + enrolledFmt + '</td>'
            + '<td style="text-align:center">' + badgeHtml(p.status) + '</td>'
            + '<td style="text-align:center; white-space:nowrap;">'
            +   '<a href="#" class="viewBtn"'
            +     ' data-id="' + p.id + '"'
            +     ' data-employee-name="' + p.employee_name + '"'
            +     ' data-position="' + (p.position||'') + '"'
            +     ' data-department="' + (p.department||'') + '"'
            +     ' data-pension-fund="' + (p.pension_fund_name||'') + '"'
            +     ' data-member-number="' + (p.pension_member_number||'') + '"'
            +     ' data-employee-rate="' + p.employee_rate + '"'
            +     ' data-employer-rate="' + p.employer_rate + '"'
            +     ' data-enrolled-on="' + enrolledFmt + '"'
            +     ' data-status="' + p.status + '"'
            +     ' data-notes="' + (p.notes||'') + '">'
            +     '<i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px;"></i></a> '
            +   '<a href="#" class="editBtn"'
            +     ' data-id="' + p.id + '" data-row="' + row + '"'
            +     ' data-employee-id="' + p.employee_id + '"'
            +     ' data-employee-name="' + p.employee_name + '"'
            +     ' data-pension-fund="' + (p.pension_fund_name||'') + '"'
            +     ' data-member-number="' + (p.pension_member_number||'') + '"'
            +     ' data-employee-rate="' + p.employee_rate + '"'
            +     ' data-employer-rate="' + p.employer_rate + '"'
            +     ' data-enrolled-on="' + p.enrolled_on + '"'
            +     ' data-status="' + p.status + '"'
            +     ' data-notes="' + (p.notes||'') + '">'
            +     '<i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px;"></i></a> '
            +   '<a href="#" class="deleteBtn"'
            +     ' data-id="' + p.id + '" data-row="' + row + '"'
            +     ' data-name="' + p.employee_name + '">'
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
        $('#vFund').text(d.pensionFund || '—');
        $('#vMemberNo').text(d.memberNumber || '—');
        $('#vEnrolledOn').text(d.enrolledOn);
        $('#vEeRate').text(parseFloat(d.employeeRate).toFixed(2) + '%');
        $('#vErRate').text(parseFloat(d.employerRate).toFixed(2) + '%');
        $('#vStatus').html(badgeHtml(d.status));
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
        $('#editFund').val(d.pensionFund);
        $('#editMemberNo').val(d.memberNumber);
        $('#editEeRate').val(d.employeeRate);
        $('#editErRate').val(d.employerRate);
        $('#editEnrolledOn').val(d.enrolledOn);
        $('#editStatus').val(d.status);
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
            url:  '{{ route("tenant.admin.hr.pension.store", ["tenantName" => request()->route("tenantName")]) }}',
            data: $('#newDataForm').serialize(),
            timeout: 60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    var row = 'row' + data.pension.id;
                    table.row.add($(buildRow(data.pension, row))).draw(false);
                    $('#newDataModal').modal('hide');
                } else if (data.status === 422) {
                    var msg = ''; $.each(data.errors, function(k, v) { msg += v + '\n'; });
                    toastr.error(msg, 'Validation');
                } else {
                    toastr.error(data.error || 'Failed.', 'Error');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var msg = ''; $.each(xhr.responseJSON.errors, function(k, v) { msg += v + '\n'; });
                    toastr.error(msg, 'Validation');
                } else {
                    toastr.error('Server error.', 'Error');
                }
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
            url:  '{{ route("tenant.admin.hr.pension.update", ["tenantName" => request()->route("tenantName")]) }}',
            data: $('#editDataForm').serialize(),
            timeout: 60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    table.row('#' + row).remove();
                    table.row.add($(buildRow(data.pension, row))).draw(false);
                    $('#editDataModal').modal('hide');
                } else if (data.status === 422) {
                    var msg = ''; $.each(data.errors, function(k, v) { msg += v + '\n'; });
                    toastr.error(msg, 'Validation');
                } else {
                    toastr.error(data.error || 'Failed.', 'Error');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var msg = ''; $.each(xhr.responseJSON.errors, function(k, v) { msg += v + '\n'; });
                    toastr.error(msg, 'Validation');
                } else {
                    toastr.error('Server error.', 'Error');
                }
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
            url:  '{{ route("tenant.admin.hr.pension.delete", ["tenantName" => request()->route("tenantName")]) }}',
            data: { _token: '{{ csrf_token() }}', id: $('#singleDeleteId').val() },
            timeout: 60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Deleted');
                    table.row('#' + row).remove().draw(false);
                    $('#singleDeleteDataModal').modal('hide');
                } else {
                    toastr.error(data.error || 'Failed.', 'Error');
                }
            },
            error: function() { toastr.error('Server error.', 'Error'); }
        });
    });

});
</script>
@endsection