@extends('tenants.admin.dashboard')
@section('content')
@php
    $employees = DB::connection('tenant')->table('users')->where('active', 'Yes')->orderBy('name')->get();
    $company   = DB::connection('tenant')->table('company_info')->where('id', 1)->first();

    $letters = DB::connection('tenant')
        ->table('offer_letters')
        ->join('users', 'users.id', '=', 'offer_letters.employee_id')
        ->select(
            'offer_letters.*',
            'users.name         as employee_name',
            'users.phone        as employee_number',
            'users.position     as current_position',
            'users.department   as department',
            'users.gross_salary as current_salary'
        )
        ->orderBy('offer_letters.issue_date', 'desc')
        ->get();

    // Map employee id → current salary so the modal can show it as a hint
    $employeeSalaries = DB::connection('tenant')
        ->table('users')
        ->where('active', 'Yes')
        ->pluck('gross_salary', 'id');

    $totalOffer        = $letters->where('letter_type', 'Offer')->count();
    $totalConfirmation = $letters->where('letter_type', 'Confirmation')->count();
    $totalPromotion    = $letters->where('letter_type', 'Promotion')->count();
    $totalTermination  = $letters->where('letter_type', 'Termination')->count();
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

.badge-Offer        { background: #4B5EBD; color: #fff; padding: 2px 9px; border-radius: 20px; font-size: 11px; }
.badge-Confirmation { background: #198754; color: #fff; padding: 2px 9px; border-radius: 20px; font-size: 11px; }
.badge-Promotion    { background: #0dcaf0; color: #fff; padding: 2px 9px; border-radius: 20px; font-size: 11px; }
.badge-Termination  { background: #dc3545; color: #fff; padding: 2px 9px; border-radius: 20px; font-size: 11px; }

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
.bg-sc4 { background: linear-gradient(135deg, #ffc107, #f59e0b); }
.bg-sc5 { background: linear-gradient(135deg, #dc3545, #f87171); }

.letter-type-grid { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
.letter-type-card {
  flex: 1; min-width: 100px; border: 2px solid #e9ecef;
  border-radius: 8px; padding: 10px; text-align: center;
  cursor: pointer; transition: all .2s;
}
.letter-type-card:hover { border-color: #4B5EBD; background: #f0f2fb; }
.letter-type-card.selected { border-color: #4B5EBD; background: #e8eaf6; }
.letter-type-card i { font-size: 22px; display: block; margin-bottom: 4px; }
.letter-type-card span { font-size: 12px; font-weight: 600; color: #212529; }

/* Salary reference hint box */
.salary-ref-box {
  background: #f0f4ff;
  border: 1px solid #c8d0ed;
  border-radius: 6px;
  padding: 8px 12px;
  font-size: 12px;
  color: #4B5EBD;
  margin-bottom: 8px;
  display: none;
}
.salary-ref-box strong { font-size: 14px; }
.salary-ref-note { font-size: 11px; color: #6c757d; margin-top: 2px; }
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
      <i class="ri-file-text-line me-1"></i> Offer Letters
    </h4>
    <div class="d-flex align-items-center">
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="statsBtn"        title="Statistics"><i class="ri-bar-chart-2-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn"         title="Info"><i class="ri-information-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Export Table"><i class="ri-table-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="newDataBtn"      title="Generate Letter"><i class="ri-add-circle-line"></i></a>
    </div>
    <?php $maintableTitle = "Offer Letters"; ?>
  </div>

  <div class="card-body">
    <table id="maintable" class="table table-sm table-striped row-border order-column w-100">
      <thead style="background-color:#e2e2e9">
        <tr>
          <th>Employee</th>
          <th style="text-align:center">Letter Type</th>
          <th style="text-align:center">Position Offered</th>
          <th style="text-align:center">Offered Salary</th>
          <th style="text-align:center">Issue Date</th>
          <th style="text-align:center">Start Date</th>
          <th style="text-align:center">Generated By</th>
          <th style="text-align:center">Action</th>
        </tr>
      </thead>
      <tbody id="tbody">
        @foreach($letters as $l)
          @php $row = 'row' . $l->id; @endphp
          <tr id="{{ $row }}">
            <td><strong>{{ $l->employee_name }}</strong></td>
            <td style="text-align:center"><span class="badge-{{ $l->letter_type }}">{{ $l->letter_type }}</span></td>
            <td style="text-align:center">{{ $l->offered_position ?? '—' }}</td>
            <td style="text-align:center">{{ number_format($l->offered_salary, 2) }}</td>
            <td style="text-align:center">{{ \Carbon\Carbon::parse($l->issue_date)->format('d M Y') }}</td>
            <td style="text-align:center">{{ $l->start_date ? \Carbon\Carbon::parse($l->start_date)->format('d M Y') : '—' }}</td>
            <td style="text-align:center">{{ $l->generated_by ?? '—' }}</td>
            <td style="text-align:center; white-space:nowrap;">
              {{-- VIEW --}}
              <a href="#" class="viewBtn"
                 data-id="{{ $l->id }}"
                 data-employee-name="{{ $l->employee_name }}"
                 data-current-position="{{ $l->current_position ?? '' }}"
                 data-department="{{ $l->department ?? '' }}"
                 data-letter-type="{{ $l->letter_type }}"
                 data-offered-position="{{ $l->offered_position ?? '' }}"
                 data-offered-department="{{ $l->offered_department ?? '' }}"
                 data-offered-salary="{{ $l->offered_salary }}"
                 data-issue-date="{{ \Carbon\Carbon::parse($l->issue_date)->format('d M Y') }}"
                 data-start-date="{{ $l->start_date ? \Carbon\Carbon::parse($l->start_date)->format('d M Y') : '' }}"
                 data-generated-by="{{ $l->generated_by ?? '' }}"
                 data-custom-message="{{ $l->custom_message ?? '' }}"
                 data-notes="{{ $l->notes ?? '' }}">
                <i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px;"></i>
              </a>
              {{-- DOWNLOAD --}}
              <a href="{{ route('tenant.admin.hr.offer.letters.download', ['tenantName' => request()->route('tenantName')]) }}?id={{ $l->id }}"
                 title="Download PDF">
                <i class="ri-file-download-line text-success" style="font-weight:bold;font-size:17px;"></i>
              </a>
              {{-- DELETE --}}
              <a href="#" class="deleteBtn"
                 data-id="{{ $l->id }}"
                 data-row="{{ $row }}"
                 data-name="{{ $l->employee_name }}">
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
        <h5 class="modal-title mh-title"><i class="ri-bar-chart-2-line"></i>&nbsp; Letter Statistics</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">
        <div class="row g-3 mb-2">
          <div class="col-md col-6">
            <div class="stats-card bg-sc1">
              <div class="sc-label">Total Letters</div>
              <div class="sc-value">{{ $letters->count() }}</div>
            </div>
          </div>
          <div class="col-md col-6">
            <div class="stats-card bg-sc2">
              <div class="sc-label">Offer</div>
              <div class="sc-value">{{ $totalOffer }}</div>
            </div>
          </div>
          <div class="col-md col-6">
            <div class="stats-card bg-sc3">
              <div class="sc-label">Confirmation</div>
              <div class="sc-value">{{ $totalConfirmation }}</div>
            </div>
          </div>
          <div class="col-md col-6">
            <div class="stats-card bg-sc4">
              <div class="sc-label">Promotion</div>
              <div class="sc-value">{{ $totalPromotion }}</div>
            </div>
          </div>
          <div class="col-md col-6">
            <div class="stats-card bg-sc5">
              <div class="sc-label">Termination</div>
              <div class="sc-value">{{ $totalTermination }}</div>
            </div>
          </div>
        </div>
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
      <h5 class="modal-title">Offer Letters — How it works</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="font-size:13px;">
      <p class="mb-3">Generate and track formal HR letters. Each letter stores its own salary snapshot so historical letters never change.</p>
      <p class="mb-1"><strong>Letter types</strong></p>
      <ul class="mb-3" style="padding-left:18px;">
        <li class="mb-1"><strong>Offer</strong> — Initial employment offer. Updates employee's active salary.</li>
        <li class="mb-1"><strong>Confirmation</strong> — Confirms permanent employment after probation. Salary for reference only, does not update employee profile.</li>
        <li class="mb-1"><strong>Promotion</strong> — Documents a change in role or salary. Updates employee's active salary.</li>
        <li><strong>Termination</strong> — Formal notice of termination. Salary for reference only.</li>
      </ul>
      <p class="mb-1"><strong>Editing policy</strong></p>
      <ul class="mb-3" style="padding-left:18px;">
        <li class="mb-1">Letters <strong>cannot be edited</strong> after generation to protect salary history integrity.</li>
        <li>If a mistake was made, delete the letter and generate a new one.</li>
      </ul>
      <p class="mb-1"><strong>Salary behaviour</strong></p>
      <ul class="mb-0" style="padding-left:18px;">
        <li class="mb-1">The employee's current salary is shown as a <em>reference hint</em> when you open the form.</li>
        <li class="mb-1">You must enter the salary explicitly — it is never filled in automatically.</li>
        <li class="mb-1">For <strong>Offer</strong> and <strong>Promotion</strong> letters, saving the letter also updates the employee's salary in their profile.</li>
        <li>Old letters always retain their original salary — re-downloading them always shows the correct historical figure.</li>
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
      <h5 class="modal-title">Download Table</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <p class="mb-2">Export the letter log.</p>
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
          <i class="ri-file-text-line"></i>&nbsp; <span id="vTitle"></span>
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4">

        <div class="modal-section-title" style="margin-top:0;">Employee</div>
        <div class="row g-2 mb-2">
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Name</div><div class="pf-value" id="vName">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Current Position</div><div class="pf-value" id="vCurrentPosition">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Department</div><div class="pf-value" id="vDepartment">—</div></div></div>
        </div>

        <div class="modal-section-title">Letter Details</div>
        <div class="row g-2 mb-2">
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Letter Type</div><div class="pf-value" id="vLetterType">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Offered Position</div><div class="pf-value" id="vOfferedPosition">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Offered Department</div><div class="pf-value" id="vOfferedDept">—</div></div></div>
          <div class="col-md-4 col-6">
            <div class="pf-group" style="background:#e8eaf6;">
              <div class="pf-label">Offered Salary</div>
              <div class="pf-value" id="vOfferedSalary" style="color:#4B5EBD;">—</div>
            </div>
          </div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Issue Date</div><div class="pf-value" id="vIssueDate">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Start Date</div><div class="pf-value" id="vStartDate">—</div></div></div>
          <div class="col-md-4 col-6"><div class="pf-group"><div class="pf-label">Generated By</div><div class="pf-value" id="vGeneratedBy">—</div></div></div>
        </div>

        <div id="vCustomMsgRow" style="display:none;">
          <div class="modal-section-title">Custom Message</div>
          <div class="pf-group"><div class="pf-value" id="vCustomMsg" style="font-size:13px;font-weight:400;white-space:pre-wrap;"></div></div>
        </div>

        <div id="vNotesRow" style="display:none;">
          <div class="modal-section-title">Notes</div>
          <div class="pf-group"><div class="pf-value" id="vNotes" style="font-size:13px;font-weight:400;"></div></div>
        </div>

      </div>
      <div class="modal-footer d-flex justify-content-between">
        <a href="#" class="btn btn-success btn-sm" id="vDownloadBtn">
          <i class="ri-file-download-line me-1"></i> Download PDF
        </a>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


{{-- ══ ADD MODAL ══ --}}
<div class="modal fade" id="newDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-add-circle-line"></i>&nbsp; Generate Letter</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form action="#" method="post" id="newDataForm">
          @csrf

          <div class="modal-section-title" style="margin-top:0;">Employee</div>
          <div class="row">
            <div class="form-group col-12 mb-3">
              <label style="font-size:13px;">Employee <span class="text-danger">*</span></label>
              <select class="form-select select2" name="employee_id" id="newEmployeeSelect" required>
                <option value="">— Select Employee —</option>
                @foreach($employees as $emp)
                  <option value="{{ $emp->id }}"
                          data-salary="{{ $employeeSalaries[$emp->id] ?? 0 }}">
                    {{ $emp->name }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="modal-section-title">Letter Type <span class="text-danger">*</span></div>
          <div class="letter-type-grid" id="letterTypeGrid">
            <div class="letter-type-card" data-type="Offer">
              <i class="ri-file-add-line text-primary"></i>
              <span>Offer</span>
            </div>
            <div class="letter-type-card" data-type="Confirmation">
              <i class="ri-checkbox-circle-line text-success"></i>
              <span>Confirmation</span>
            </div>
            <div class="letter-type-card" data-type="Promotion">
              <i class="ri-arrow-up-circle-line text-info"></i>
              <span>Promotion</span>
            </div>
            <div class="letter-type-card" data-type="Termination">
              <i class="ri-close-circle-line text-danger"></i>
              <span>Termination</span>
            </div>
          </div>
          <input type="hidden" name="letter_type" id="newLetterType">

          <div class="modal-section-title">Letter Details</div>

          {{-- Salary reference box: shown after employee is selected --}}
          <div class="salary-ref-box" id="newSalaryRefBox">
            <div>Current salary on record: <strong id="newSalaryRefValue"></strong></div>
            <div class="salary-ref-note">Copy this value into the Offered Salary field below if the salary is unchanged.</div>
          </div>

          <div class="row">
            <div class="form-group col-md-6 mb-3">
              <label style="font-size:13px;">Issue Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control" name="issue_date" required>
            </div>
            <div class="form-group col-md-6 mb-3">
              <label style="font-size:13px;">Start Date</label>
              <input type="date" class="form-control" name="start_date">
            </div>
            <div class="form-group col-md-6 mb-3">
              <label style="font-size:13px;">Offered Position</label>
              <input type="text" class="form-control" name="offered_position" placeholder="e.g. Senior Cashier">
            </div>
            <div class="form-group col-md-6 mb-3">
              <label style="font-size:13px;">Offered Department</label>
              <input type="text" class="form-control" name="offered_department" placeholder="e.g. Operations">
            </div>
            <div class="form-group col-md-6 mb-3">
              <label style="font-size:13px;">
                Offered Salary <span class="text-danger">*</span>
              </label>
              <input type="number" step="0.01" min="0" class="form-control" name="offered_salary"
                     id="newOfferedSalary" placeholder="Enter salary amount" required>
            </div>
            <div class="form-group col-12 mb-3">
              <label style="font-size:13px;">Custom Message
                <span class="text-muted" style="font-size:11px;">(optional — printed on the letter)</span>
              </label>
              <textarea class="form-control" name="custom_message" rows="3"
                placeholder="e.g. We look forward to welcoming you to the team."></textarea>
            </div>
            <div class="form-group col-12 mb-3">
              <label style="font-size:13px;">Notes <span class="text-muted" style="font-size:11px;">(internal only — not printed)</span></label>
              <textarea class="form-control" name="notes" rows="2" placeholder="Optional internal notes…"></textarea>
            </div>
          </div>

          <div class="alert alert-info py-2 px-3" style="font-size:12px;" id="newSalaryNote" style="display:none;">
            <i class="ri-information-line me-1"></i>
            For <strong>Offer</strong> and <strong>Promotion</strong> letters, saving will also update the employee's salary in their profile to match the offered salary.
          </div>

          <a href="#" class="btn btn-primary float-end mt-3 mb-2" id="submitDataBtn">Generate &amp; Save</a>
          <a href="#" class="btn btn-secondary float-end mt-3 mb-2 mx-2" id="cancelDataBtn">Cancel</a>
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
          <h4>Delete letter for <span id="singleDisplayDeleteLabel" class="text-danger"></span>?</h4>
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

    var maintableTitle  = @json($maintableTitle);
    var downloadBaseUrl = '{{ route("tenant.admin.hr.offer.letters.download", ["tenantName" => request()->route("tenantName")]) }}';

    // ── DataTable ─────────────────────────────────────────────────────────
    var table = $('#maintable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
        fixedColumns: { left: 1 },
        scrollX: true,
        order: [[4, 'desc']],
        columnDefs: [{ targets: [7], orderable: false }],
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

    // ── Toolbar buttons ───────────────────────────────────────────────────
    $('#statsBtn').click(function(e)        { e.preventDefault(); $('#statsModal').modal('show'); });
    $('#infoBtn').click(function(e)         { e.preventDefault(); $('#infoModal').modal('show'); });
    $('#tableButtonsBtn').click(function(e) { e.preventDefault(); $('#buttonsModal').modal('show'); });

    $('#newDataBtn').click(function(e) {
        e.preventDefault();
        $('#newDataForm')[0].reset();
        $('#newLetterType').val('');
        $('.letter-type-card').removeClass('selected');
        $('#newSalaryRefBox').hide();
        $('#newSalaryNote').hide();
        $('#newDataModal').modal('show');
    });

    $('#cancelDataBtn').click(function(e)     { e.preventDefault(); $('#newDataModal').modal('hide'); });
    $('#keepSingleDataBtn').click(function(e) { e.preventDefault(); toastr.info('Record is safe.', 'OK'); $('#singleDeleteDataModal').modal('hide'); });

    // ── Letter type card selection (add modal) ────────────────────────────
    $('#letterTypeGrid').on('click', '.letter-type-card', function() {
        $('.letter-type-card').removeClass('selected');
        $(this).addClass('selected');
        var type = $(this).data('type');
        $('#newLetterType').val(type);
        if (type === 'Offer' || type === 'Promotion') {
            $('#newSalaryNote').show();
        } else {
            $('#newSalaryNote').hide();
        }
    });

    // ── Employee selection → show current salary as reference hint ────────
    $('#newEmployeeSelect').on('change', function() {
        var salary = parseFloat($(this).find(':selected').data('salary')) || 0;
        if (salary > 0) {
            $('#newSalaryRefValue').text(salary.toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
            $('#newSalaryRefBox').show();
        } else {
            $('#newSalaryRefBox').hide();
        }
        $('#newOfferedSalary').val('');
    });

    // ── Helpers ───────────────────────────────────────────────────────────
    function fmt(n) {
        return parseFloat(n || 0).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function badgeHtml(type) {
        return '<span class="badge-' + type + '">' + type + '</span>';
    }

    function buildRow(l, row) {
        var issueFmt = l.issue_date_fmt  || l.issue_date  || '—';
        var startFmt = l.start_date_fmt  || l.start_date  || '—';
        return '<tr id="' + row + '">'
            + '<td><strong>' + l.employee_name + '</strong></td>'
            + '<td style="text-align:center">' + badgeHtml(l.letter_type) + '</td>'
            + '<td style="text-align:center">' + (l.offered_position || '—') + '</td>'
            + '<td style="text-align:center">' + fmt(l.offered_salary) + '</td>'
            + '<td style="text-align:center">' + issueFmt + '</td>'
            + '<td style="text-align:center">' + startFmt + '</td>'
            + '<td style="text-align:center">' + (l.generated_by || '—') + '</td>'
            + '<td style="text-align:center; white-space:nowrap;">'
            +   '<a href="#" class="viewBtn"'
            +     ' data-id="'                 + l.id + '"'
            +     ' data-employee-name="'      + l.employee_name + '"'
            +     ' data-current-position="'   + (l.current_position   || '') + '"'
            +     ' data-department="'         + (l.department         || '') + '"'
            +     ' data-letter-type="'        + l.letter_type + '"'
            +     ' data-offered-position="'   + (l.offered_position   || '') + '"'
            +     ' data-offered-department="' + (l.offered_department || '') + '"'
            +     ' data-offered-salary="'     + l.offered_salary + '"'
            +     ' data-issue-date="'         + issueFmt + '"'
            +     ' data-start-date="'         + startFmt + '"'
            +     ' data-generated-by="'       + (l.generated_by       || '') + '"'
            +     ' data-custom-message="'     + (l.custom_message     || '') + '"'
            +     ' data-notes="'              + (l.notes              || '') + '">'
            +     '<i class="ri-eye-line text-primary" style="font-weight:bold;font-size:17px;"></i></a> '
            +   '<a href="' + downloadBaseUrl + '?id=' + l.id + '" title="Download PDF">'
            +     '<i class="ri-file-download-line text-success" style="font-weight:bold;font-size:17px;"></i></a> '
            +   '<a href="#" class="deleteBtn"'
            +     ' data-id="' + l.id + '" data-row="' + row + '"'
            +     ' data-name="' + l.employee_name + '">'
            +     '<i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px;"></i></a>'
            + '</td></tr>';
    }

    // ── VIEW ──────────────────────────────────────────────────────────────
    $('#tbody').on('click', '.viewBtn', function(e) {
        e.preventDefault();
        var d = $(this).data();
        $('#vTitle').text(d.letterType + ' Letter — ' + d.employeeName);
        $('#vName').text(d.employeeName);
        $('#vCurrentPosition').text(d.currentPosition || '—');
        $('#vDepartment').text(d.department || '—');
        $('#vLetterType').html(badgeHtml(d.letterType));
        $('#vOfferedPosition').text(d.offeredPosition || '—');
        $('#vOfferedDept').text(d.offeredDepartment || '—');
        $('#vOfferedSalary').text(fmt(d.offeredSalary));
        $('#vIssueDate').text(d.issueDate);
        $('#vStartDate').text(d.startDate || '—');
        $('#vGeneratedBy').text(d.generatedBy || '—');
        $('#vDownloadBtn').attr('href', downloadBaseUrl + '?id=' + d.id);

        if (d.customMessage) {
            $('#vCustomMsg').text(d.customMessage);
            $('#vCustomMsgRow').show();
        } else { $('#vCustomMsgRow').hide(); }

        if (d.notes) {
            $('#vNotes').text(d.notes);
            $('#vNotesRow').show();
        } else { $('#vNotesRow').hide(); }

        $('#viewModal').modal('show');
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
        if (!$('#newLetterType').val()) {
            toastr.warning('Please select a letter type.', 'Required');
            return;
        }
        if (!$('#newOfferedSalary').val() || parseFloat($('#newOfferedSalary').val()) <= 0) {
            toastr.warning('Offered Salary is required and must be greater than zero.', 'Required');
            return;
        }
        var self = $(this); self.prop('disabled', true);
        $.ajax({
            type: 'POST',
            url:  '{{ route("tenant.admin.hr.offer.letters.store", ["tenantName" => request()->route("tenantName")]) }}',
            data: $('#newDataForm').serialize(),
            timeout: 60000,
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    var row = 'row' + data.letter.id;
                    table.row.add($(buildRow(data.letter, row))).draw(false);
                    $('#newDataModal').modal('hide');
                    $('#newDataForm')[0].reset();
                    $('.letter-type-card').removeClass('selected');
                    $('#newSalaryRefBox').hide();
                    $('#newSalaryNote').hide();
                } else if (data.status === 422) {
                    var msg = '';
                    $.each(data.errors, function(k, v) { msg += v + '<br>'; });
                    toastr.error(msg, 'Validation');
                } else { toastr.error(data.error || 'Failed.', 'Error'); }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var msg = '';
                    $.each(xhr.responseJSON.errors, function(k, v) { msg += v + '<br>'; });
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
            url:  '{{ route("tenant.admin.hr.offer.letters.delete", ["tenantName" => request()->route("tenantName")]) }}',
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