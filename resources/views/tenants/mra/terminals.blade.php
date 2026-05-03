@extends('tenants.admin.dashboard')
@section('content')
<style>
/* ── Card chrome ─────────────────────────────────────────────────────────── */
.card-header { padding: 0.5rem 1.5rem !important; background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff; }
.card-body   { padding: 0 1.5rem 1.5rem 1.5rem !important; }
.card-header .btn-light { height: 28px; padding: 0 10px; display: flex; align-items: center; justify-content: center; line-height: 1; }
.card-header .btn-light:hover { background-color: #f8f9fa; transition: background-color 0.2s ease-in-out; }
.card { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; }
.card-header h4 { color: #fff; font-weight: 600; margin-bottom: 0; display: flex; align-items: center; }
.card-header h4 i { margin-right: 0.25rem; }

.btn-add-terminal {
    height: 28px; padding: 0 10px; display: inline-flex; align-items: center;
    justify-content: center; line-height: 1;
    background: #fff; color: #4B5EBD; border: none; border-radius: 6px;
    font-size: 16px; cursor: pointer;
    transition: background 0.15s, color 0.15s;
}
.btn-add-terminal:hover { background: #f0f2ff; color: #3a4aa0; }

table.dataTable thead th {
    background-color: #e2e2e9 !important;
    font-weight: 600; font-size: 13px; color: #343a40; white-space: nowrap;
}
table.dataTable thead th,
table.dataTable tbody td {
    text-align: center !important;
    vertical-align: middle !important;
    font-size: 13px;
}
table.dataTable thead th:first-child,
table.dataTable tbody td:first-child,
table.dataTable thead th:nth-child(2),
table.dataTable tbody td:nth-child(2) { text-align: left !important; }
table.dataTable tbody td { padding-top: 6px !important; padding-bottom: 6px !important; }
table.dataTable, .dataTables_wrapper { overflow: visible !important; }

.t-label { font-weight: 700; color: #1e293b; font-size: 13px; }
.t-pos   { font-size: 10px; color: #94a3b8; margin-top: 1px; }
.t-mra-id { font-size: 11px; font-family: monospace; color: #64748b; }

.act-badge { display: inline-flex; align-items: center; gap: 4px; border-radius: 6px; padding: 3px 9px; font-size: 10px; font-weight: 700; white-space: nowrap; }
.act-badge.activated   { background: #d1fae5; color: #065f46; }
.act-badge.pending     { background: #fef3c7; color: #92400e; }
.act-badge.failed      { background: #fee2e2; color: #991b1b; }
.act-badge.deactivated { background: #f1f5f9; color: #64748b; }

.cred-yes { background: #d1fae5; color: #065f46; border-radius: 5px; padding: 2px 7px; font-size: 10px; font-weight: 700; }
.cred-no  { background: #f1f5f9; color: #94a3b8; border-radius: 5px; padding: 2px 7px; font-size: 10px; font-weight: 700; }

.confirm-chip { display: inline-flex; align-items: center; gap: 4px; background: #fef3c7; border: 1px solid #fde68a; border-radius: 6px; padding: 2px 8px; font-size: 10px; color: #92400e; font-weight: 600; cursor: pointer; }
.confirm-chip:hover { background: #fde68a; text-decoration: none; }

@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
.spin { animation: spin 1s linear infinite; display: inline-block; }

.mh-blue   { background: linear-gradient(135deg, #4B5EBD, #576CC0); padding: 14px 18px !important; border-bottom: none; }
.mh-green  { background: linear-gradient(135deg, #2d6a4f, #40916c); padding: 14px 18px !important; border-bottom: none; }
.mh-danger { background: linear-gradient(135deg, #dc2626, #ef4444); padding: 14px 18px !important; border-bottom: none; }
.mh-gold   { background: linear-gradient(135deg, #92400e, #d97706); padding: 14px 18px !important; border-bottom: none; }
.mh-title  { color: #fff; font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 7px; }
.mh-close  { filter: brightness(0) invert(1); opacity: .8; }
.mh-close:hover { opacity: 1; }

.tac-input { font-family: monospace; font-size: 20px; font-weight: 700; letter-spacing: 4px; text-align: center; text-transform: uppercase; }
.tac-hint  { font-size: 11px; color: #94a3b8; text-align: center; margin-top: 4px; }

.steps-modal-grid { display: flex; flex-direction: column; gap: 14px; }
.step-modal-item { display: flex; align-items: flex-start; gap: 14px; padding: 12px 14px; background: #f8f9ff; border: 1px solid #dde2f0; border-radius: 10px; }
.step-modal-num { width: 28px; height: 28px; border-radius: 50%; background: #4B5EBD; color: #fff; font-size: 12px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: 1px; }
.step-modal-body .step-modal-title { font-weight: 700; color: #1e293b; font-size: 13px; margin-bottom: 3px; }
.step-modal-body p { font-size: 12px; color: #64748b; margin: 0; }
</style>

<div class="progress" id="progressBar" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"
     style="height:8px;transform:rotate(180deg);display:none">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page">
<div class="content">
<div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
      <i class="ri-cpu-line"></i> EIS Terminals
    </h4>
    <div class="d-flex align-items-center" style="gap:4px;">
      <button class="btn-add-terminal me-1" id="addTerminalBtn" title="Add a new terminal">
        <i class="ri-add-circle-line"></i>
      </button>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="howItWorksBtn" title="How Terminal Activation Works"><i class="ri-question-line"></i></a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn" title="About Terminals"><i class="ri-information-line"></i></a>
      <a href="{{ route('tenant.admin.eis.dashboard') }}" class="btn btn-light text-primary fs-16 mx-1" title="EIS Dashboard"><i class="ri-arrow-left-line"></i></a>
    </div>
  </div>

  <div class="card-body">
    <div class="tab-content" style="padding-top:1rem;">
      <div class="tab-pane show active">
        <table id="terminalsTable" class="table table-sm table-striped row-border order-column w-100">
          <thead style="background-color:#e2e2e9">
            <tr>
              <th style="text-align:left !important;">Terminal</th>
              <th style="text-align:left !important;">Branch</th>
              <th>MRA Terminal ID</th>
              <th>Activation</th>
              <th>Credentials</th>
              <th>Config Ver.</th>
              <th>Activated On</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="terminalsTbody"></tbody>
        </table>
      </div>
    </div>
  </div>

</div>
</div>
</div>
</div>

{{-- HOW IT WORKS MODAL --}}
<div class="modal fade" id="howItWorksModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-question-line"></i> How Terminal Activation Works</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:18px 20px;">
        <p class="mb-3" style="font-size:13px;color:#475569;">Every terminal must complete a 4-step activation process with MRA before it can issue valid fiscal receipts.</p>
        <div class="steps-modal-grid">
          <div class="step-modal-item">
            <div class="step-modal-num">1</div>
            <div class="step-modal-body">
              <div class="step-modal-title">Add Terminal</div>
              <p>Click the <strong>+</strong> button in the top bar. Give it a label (e.g. <em>Till-01</em>) and a unique position number per branch.</p>
            </div>
          </div>
          <div class="step-modal-item">
            <div class="step-modal-num">2</div>
            <div class="step-modal-body">
              <div class="step-modal-title">Activate Using a TAC</div>
              <p>Log in to the <a href="https://eis-portal.mra.mw" target="_blank">MRA EIS Portal</a>, copy the Terminal Activation Code (TAC) in the format <code>XXXX-XXXX-XXXX-XXXX</code>, click <em>Activate</em> on the row, paste it, and submit.</p>
            </div>
          </div>
          <div class="step-modal-item">
            <div class="step-modal-num">3</div>
            <div class="step-modal-body">
              <div class="step-modal-title">Confirm the Activation</div>
              <p>Immediately after activation, click the yellow <em>Confirm now</em> chip. This sends MRA a cryptographic x-signature to finalise onboarding.</p>
              <div class="alert alert-danger border-0 py-2 px-3 mt-2 mb-0" style="font-size:12px;border-radius:7px;">
                <i class="ri-error-warning-line me-1"></i>
                <strong>Do not skip this step.</strong> The TAC is consumed the moment activation is sent.
              </div>
            </div>
          </div>
          <div class="step-modal-item">
            <div class="step-modal-num">4</div>
            <div class="step-modal-body">
              <div class="step-modal-title">Ready</div>
              <p>The terminal status changes to <span class="act-badge activated" style="display:inline-flex">Activated</span>. It can now sign and submit fiscal receipts to MRA.</p>
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

{{-- INFO MODAL --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About EIS Terminals</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:18px 20px;">
        <p class="mb-2"><strong>What is a Terminal?</strong><br>
        Each POS till or cashier point at a branch must be registered with MRA as a separate terminal before it can issue valid fiscal receipts.</p>
        <hr class="my-3">
        <div class="d-flex flex-column gap-2" style="font-size:13px;">
          <div class="d-flex align-items-start gap-2">
            <span class="act-badge activated mt-1">Activated</span>
            <div>Fully registered and live — can issue MRA-signed receipts.</div>
          </div>
          <div class="d-flex align-items-start gap-2">
            <span class="act-badge pending mt-1">Pending</span>
            <div>Activation sent but confirmation not yet completed. Look for the yellow <em>Confirm now</em> chip.</div>
          </div>
          <div class="d-flex align-items-start gap-2">
            <span class="act-badge failed mt-1">Failed</span>
            <div>Activation failed — TAC may have been wrong, already used, or expired. Delete and add a new terminal with a fresh TAC.</div>
          </div>
          <div class="d-flex align-items-start gap-2">
            <span class="act-badge deactivated mt-1">Deactivated</span>
            <div>Manually deactivated. Contact MRA to reactivate.</div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- ADD / EDIT TERMINAL MODAL --}}
<div class="modal fade" id="terminalFormModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:440px;">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
      <div class="modal-header mh-blue">
        <h5 class="modal-title mh-title" id="formModalTitle"><i class="ri-add-circle-line"></i> Add Terminal</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:18px 20px;">
        <input type="hidden" id="fmId">
        <input type="hidden" id="fmBranchId">
        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:13px">Branch <span class="text-danger">*</span></label>
          <select class="form-select" id="fmBranchSelect">
            <option value="">— Select a branch —</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}">{{ $b->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:13px">Terminal Label <span class="text-danger">*</span></label>
          <input class="form-control" type="text" id="fmLabel" placeholder="e.g. Till-01, Counter-A" autocomplete="off">
          <div class="form-text">A human-readable name for this till or POS device.</div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:13px">Terminal Position <span class="text-danger">*</span></label>
          <input class="form-control" type="number" min="1" max="9999" id="fmPosition" placeholder="e.g. 1">
          <div class="form-text">Unique sequential number per branch — used in the MRA invoice number on every receipt.</div>
        </div>
        <div id="fmStatusRow" class="mb-2" style="display:none;">
          <label class="form-label fw-semibold" style="font-size:13px">Status</label>
          <select class="form-select" id="fmStatus">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
          <div class="form-text">Only active terminals can be used for sales.</div>
        </div>
      </div>
      <div class="modal-footer" style="justify-content:flex-end;gap:8px;padding:10px 20px 14px;">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary btn-sm" id="saveTerminalBtn">
          <i class="ri-check-line me-1"></i> Save
        </button>
      </div>
    </div>
  </div>
</div>

{{-- ACTIVATE TERMINAL MODAL --}}
<div class="modal fade" id="activateModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:440px;">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
      <div class="modal-header mh-green">
        <h5 class="modal-title mh-title"><i class="ri-key-line"></i> Activate Terminal</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:18px 20px;">
        <p class="mb-3 pb-2 border-bottom" style="font-size:13px;color:#64748b;">
          Activating: <strong style="color:#1e293b" id="activateTerminalName"></strong>
        </p>
        <div class="alert alert-info border-0 py-2 px-3 mb-3" style="font-size:12px;border-radius:8px;">
          <i class="ri-information-line me-1"></i>
          Get the TAC from the <a href="https://eis-portal.mra.mw" target="_blank">MRA EIS Portal</a>
          under your branch's terminal list. Format: <code>XXXX-XXXX-XXXX-XXXX</code>.
        </div>
        <input type="hidden" id="activateTerminalId">
        <div class="mb-3">
          <label class="form-label fw-semibold" style="font-size:13px">Terminal Activation Code (TAC)</label>
          <input class="form-control tac-input" type="text" id="activateTac"
                 placeholder="XXXX-XXXX-XXXX-XXXX" maxlength="19" autocomplete="off">
          <div class="tac-hint">16 alphanumeric characters in 4 groups of 4, separated by hyphens</div>
        </div>
        <div class="alert alert-warning border-0 py-2 px-3 mb-0" style="font-size:12px;border-radius:8px;">
          <i class="ri-error-warning-line me-1"></i>
          <strong>After activation, immediately click Confirm on the terminal row.</strong>
          The TAC is consumed once sent to MRA.
        </div>
      </div>
      <div class="modal-footer" style="justify-content:flex-end;gap:8px;padding:10px 20px 14px;">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success btn-sm" id="doActivateBtn">
          <i class="ri-key-line me-1"></i> Send Activation Code to MRA
        </button>
      </div>
    </div>
  </div>
</div>

{{-- CONFIRM ACTIVATION MODAL --}}
<div class="modal fade" id="confirmActivationModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:420px;">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.2);">
      <div class="modal-header mh-gold">
        <h5 class="modal-title mh-title"><i class="ri-shield-check-line"></i> Confirm Activation</h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:18px 20px;">
        <p class="mb-3 pb-2 border-bottom" style="font-size:13px;color:#64748b;">
          Confirming: <strong style="color:#1e293b" id="confirmTerminalName"></strong>
        </p>
        <div class="alert alert-warning border-0 py-2 px-3 mb-3" style="font-size:12px;border-radius:8px;">
          <i class="ri-error-warning-line me-1"></i>
          <strong>This is the second and final step.</strong> MRA requires a cryptographic confirmation (x-signature) to complete activation.
        </div>
        <div class="alert alert-danger border-0 py-2 px-3 mb-0" style="font-size:12px;border-radius:8px;">
          <i class="ri-error-warning-line me-1"></i>
          If this fails, the TAC is already consumed. You will need a <strong>new TAC</strong> from the MRA EIS Portal.
        </div>
        <input type="hidden" id="confirmTerminalId">
      </div>
      <div class="modal-footer" style="justify-content:flex-end;gap:8px;padding:10px 20px 14px;">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-warning btn-sm text-white" id="doConfirmBtn">
          <i class="ri-shield-check-line me-1"></i> Confirm &amp; Finalize Activation
        </button>
      </div>
    </div>
  </div>
</div>

{{-- DEACTIVATE MODAL --}}
<div class="modal fade" id="deactivateModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:380px;">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;">
      <div class="modal-body text-center pb-4 pt-4">
        <i class="ri-error-warning-line text-danger" style="font-size:60px"></i>
        <h4 class="mt-2">Deactivate <span id="deactivateTerminalName" class="text-danger"></span>?</h4>
        <p class="text-muted" style="font-size:13px;">This terminal will be marked inactive and can no longer issue receipts. To reactivate, contact MRA.</p>
        <p class="text-muted" style="font-size:12px;">Audit records and logs are preserved.</p>
        <input type="hidden" id="deactivateTerminalId">
        <div class="d-flex gap-2 justify-content-center mt-3">
          <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-danger btn-sm" id="doDeactivateBtn">Yes, Deactivate</button>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- DELETE MODAL --}}
<div class="modal fade" id="deleteTerminalModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" style="max-width:360px;">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;">
      <div class="modal-body text-center pb-4 pt-4">
        <i class="ri-delete-bin-line text-danger" style="font-size:60px"></i>
        <h4 class="mt-2">Delete <span id="deleteTerminalName" class="text-danger"></span>?</h4>
        <p class="text-muted" style="font-size:13px;">Only pending or failed terminals can be deleted. This cannot be undone.</p>
        <input type="hidden" id="deleteTerminalId">
        <div class="d-flex gap-2 justify-content-center mt-3">
          <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">No, Keep it</button>
          <button class="btn btn-danger btn-sm" id="doDeleteBtn">Yes, Delete</button>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')
<script>
$(function () {
    toastr.options = { closeButton: true, progressBar: true, timeOut: 5000 };

    var branches     = @json($branches);
    var allTerminals = {};

    function fmtDate(d) {
        if (!d) return '—';
        return new Date(d).toLocaleString('en-GB', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
    }

    function actBadge(status) {
        var map = {
            activated:   '<span class="act-badge activated"><i class="ri-checkbox-circle-line"></i> Activated</span>',
            pending:     '<span class="act-badge pending"><i class="ri-time-line"></i> Pending</span>',
            failed:      '<span class="act-badge failed"><i class="ri-close-circle-line"></i> Failed</span>',
            deactivated: '<span class="act-badge deactivated"><i class="ri-forbid-line"></i> Deactivated</span>',
        };
        return map[status] || '<span class="act-badge deactivated">' + status + '</span>';
    }

    var dtOpts = {
        scrollX:    false,
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
        pageLength: 25,
        dom: '<"row"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6"p>>',
        initComplete: function () { this.api().columns.adjust().draw(false); }
    };

    var terminalsDT = $('#terminalsTable').DataTable(dtOpts);

    function loadAllTerminals() {
        terminalsDT.clear().draw(false);
        if (!branches.length) return;

        var pending = branches.length;

        branches.forEach(function (b) {
            $.getJSON('{{ route("tenant.admin.eis.terminals.get") }}', { branch_id: b.id }, function (data) {
                pending--;

                if (data.success && data.terminals.length) {
                    allTerminals[b.id] = data.terminals;

                    data.terminals.forEach(function (t) {
                        var confirmChip = '';
                        if (t.activation_status === 'pending' && t.has_credentials) {
                            confirmChip = ' <a href="#" class="confirm-chip confirmActivationBtn" data-id="' + t.id + '" data-label="' + t.terminal_label + '"><i class="ri-shield-check-line"></i> Confirm now</a>';
                        }

                        var terminalCell = '<div class="t-label">' + t.terminal_label + '</div>'
                            + '<div class="t-pos"><i class="ri-cpu-line me-1"></i>Position ' + t.terminal_position + '</div>';

                        var mraId = t.mra_terminal_id
                            ? '<span class="t-mra-id">' + t.mra_terminal_id.substring(0, 20) + '…</span>'
                            : '<span style="color:#c8d0ed;font-size:11px;font-style:italic;">Not assigned</span>';

                        var activationCell = actBadge(t.activation_status) + confirmChip;

                        var credCell = t.has_credentials
                            ? '<span class="cred-yes"><i class="ri-shield-check-line me-1"></i>Stored</span>'
                            : '<span class="cred-no">None</span>';

                        var actionBtns = '';
                        if (t.activation_status === 'pending' || t.activation_status === 'failed') {
                            actionBtns += '<button class="btn btn-xs btn-success activateBtn me-1" data-id="' + t.id + '" data-label="' + t.terminal_label + '" style="font-size:11px;padding:2px 8px"><i class="ri-key-line me-1"></i>Activate</button>';
                            actionBtns += '<button class="btn btn-xs btn-outline-info editTerminalBtn me-1" data-id="' + t.id + '" data-branch-id="' + b.id + '" style="font-size:11px;padding:2px 8px"><i class="ri-edit-box-line"></i></button>';
                            actionBtns += '<button class="btn btn-xs btn-outline-danger deleteTerminalBtn" data-id="' + t.id + '" data-label="' + t.terminal_label + '" style="font-size:11px;padding:2px 8px"><i class="ri-delete-bin-line"></i></button>';
                        } else if (t.activation_status === 'activated') {
                            actionBtns += '<button class="btn btn-xs btn-outline-secondary editTerminalBtn me-1" data-id="' + t.id + '" data-branch-id="' + b.id + '" style="font-size:11px;padding:2px 8px"><i class="ri-toggle-line me-1"></i>Toggle</button>';
                            actionBtns += '<button class="btn btn-xs btn-outline-danger deactivateBtn me-1" data-id="' + t.id + '" data-label="' + t.terminal_label + '" style="font-size:11px;padding:2px 8px"><i class="ri-forbid-line"></i></button>';
                            actionBtns += '<a href="{{ route("tenant.admin.eis.terminal-logs") }}?terminal_id=' + t.id + '" class="btn btn-xs btn-outline-primary" style="font-size:11px;padding:2px 8px"><i class="ri-history-line"></i></a>';
                        } else if (t.activation_status === 'deactivated') {
                            actionBtns += '<a href="{{ route("tenant.admin.eis.terminal-logs") }}?terminal_id=' + t.id + '" class="btn btn-xs btn-outline-secondary" style="font-size:11px;padding:2px 8px"><i class="ri-history-line me-1"></i>Logs</a>';
                        }

                        terminalsDT.row.add([
                            terminalCell,
                            '<strong style="font-size:12px;color:#475569;">' + b.name + '</strong>',
                            mraId,
                            activationCell,
                            credCell,
                            t.mra_terminal_config_version || '—',
                            t.activated_at ? fmtDate(t.activated_at) : '—',
                            actionBtns
                        ]);
                    });
                }

                if (!pending) terminalsDT.draw(false);

            }).fail(function () {
                pending--;
                if (!pending) terminalsDT.draw(false);
            });
        });
    }

    loadAllTerminals();

    $('#infoBtn').on('click',       function (e) { e.preventDefault(); $('#infoModal').modal('show'); });
    $('#howItWorksBtn').on('click', function (e) { e.preventDefault(); $('#howItWorksModal').modal('show'); });

    function openAddModal(branchId) {
        $('#fmId').val('');
        $('#fmBranchSelect').val(branchId || '').prop('disabled', false);
        $('#fmBranchId').val(branchId || '');
        $('#fmLabel').val('').prop('disabled', false);
        $('#fmPosition').val('').prop('disabled', false);
        $('#fmStatus').val('active');
        $('#formModalTitle').html('<i class="ri-add-circle-line me-1"></i> Add Terminal');
        $('#fmStatusRow').hide();
        $('#terminalFormModal').modal('show');
    }

    $('#addTerminalBtn').on('click', function () { openAddModal(null); });

    $(document).on('click', '.editTerminalBtn', function () {
        var id       = $(this).data('id');
        var branchId = $(this).data('branch-id');
        var terminals = allTerminals[branchId] || [];
        var t = terminals.find(function (x) { return x.id == id; });
        if (!t) return;
        $('#fmId').val(t.id);
        $('#fmBranchId').val(branchId);
        $('#fmBranchSelect').val(branchId).prop('disabled', true);
        $('#fmLabel').val(t.terminal_label).prop('disabled', t.activation_status === 'activated');
        $('#fmPosition').val(t.terminal_position).prop('disabled', t.activation_status === 'activated');
        $('#fmStatus').val(t.status);
        $('#formModalTitle').html('<i class="ri-edit-box-line me-1"></i> Edit Terminal');
        $('#fmStatusRow').show();
        $('#terminalFormModal').modal('show');
    });

    $('#saveTerminalBtn').on('click', function () {
        var id       = $('#fmId').val();
        var branchId = $('#fmBranchSelect').val() || $('#fmBranchId').val();
        var isInsert = !id;
        var self     = $(this).prop('disabled', true);
        var url      = isInsert
            ? '{{ route("tenant.admin.eis.terminals.insert") }}'
            : '{{ route("tenant.admin.eis.terminals.update") }}';
        var data = {
            _token: '{{ csrf_token() }}', branch_id: branchId,
            terminal_label: $('#fmLabel').val().trim(),
            terminal_position: $('#fmPosition').val(),
            status: $('#fmStatus').val() || 'active',
        };
        if (id) data.id = id;

        $.ajax({
            type: 'POST', url: url, data: data, timeout: 30000,
            beforeSend: function () { $('#progressBar').show(); },
            complete:   function () { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function (r) {
                if (r.success) {
                    toastr.success(r.success, 'Success');
                    $('#terminalFormModal').modal('hide');
                    allTerminals = {};
                    loadAllTerminals();
                } else { toastr.error(r.error || 'Failed.', 'Error'); }
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.error) || 'Request failed.', 'Error');
            }
        });
    });

    $(document).on('click', '.activateBtn', function () {
        $('#activateTerminalId').val($(this).data('id'));
        $('#activateTerminalName').text($(this).data('label'));
        $('#activateTac').val('');
        $('#activateModal').modal('show');
    });

    $('#activateTac').on('input', function () {
        var raw = $(this).val().replace(/[^A-Za-z0-9]/g, '').substring(0, 16).toUpperCase();
        $(this).val((raw.match(/.{1,4}/g) || []).join('-'));
    });

    $('#doActivateBtn').on('click', function () {
        var tac = $('#activateTac').val().trim();
        if (!/^[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}-[A-Za-z0-9]{4}$/.test(tac)) {
            toastr.warning('TAC must be in XXXX-XXXX-XXXX-XXXX format.', 'Invalid TAC'); return;
        }
        var self = $(this).prop('disabled', true).html('<i class="ri-loader-4-line spin me-1"></i> Sending to MRA…');
        $.ajax({
            type: 'POST', url: '{{ route("tenant.admin.eis.terminals.activate") }}',
            data: { _token: '{{ csrf_token() }}', terminal_id: $('#activateTerminalId').val(), tac: tac },
            timeout: 40000,
            beforeSend: function () { $('#progressBar').show(); },
            complete:   function () { $('#progressBar').hide(); self.prop('disabled', false).html('<i class="ri-key-line me-1"></i> Send Activation Code to MRA'); },
            success: function (r) {
                if (r.success) {
                    toastr.success(r.success, 'Activation Sent');
                    $('#activateModal').modal('hide');
                    allTerminals = {};
                    loadAllTerminals();
                    setTimeout(function () {
                        toastr.warning('Remember to click <strong>Confirm now</strong> on the terminal row to complete activation!', 'Action Required', { timeOut: 8000 });
                    }, 800);
                } else { toastr.error(r.error || 'Activation failed.', 'Error'); }
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.error) || 'Request failed.', 'Error');
            }
        });
    });

    $(document).on('click', '.confirmActivationBtn', function (e) {
        e.preventDefault();
        $('#confirmTerminalId').val($(this).data('id'));
        $('#confirmTerminalName').text($(this).data('label'));
        $('#confirmActivationModal').modal('show');
    });

    $('#doConfirmBtn').on('click', function () {
        var self = $(this).prop('disabled', true).html('<i class="ri-loader-4-line spin me-1"></i> Confirming…');
        $.ajax({
            type: 'POST', url: '{{ route("tenant.admin.eis.terminals.confirm") }}',
            data: { _token: '{{ csrf_token() }}', terminal_id: $('#confirmTerminalId').val() },
            timeout: 40000,
            beforeSend: function () { $('#progressBar').show(); },
            complete:   function () { $('#progressBar').hide(); self.prop('disabled', false).html('<i class="ri-shield-check-line me-1"></i> Confirm &amp; Finalize Activation'); },
            success: function (r) {
                if (r.success) {
                    toastr.success(r.success, 'Activation Confirmed!');
                    $('#confirmActivationModal').modal('hide');
                    allTerminals = {};
                    loadAllTerminals();
                } else { toastr.error(r.error || 'Confirmation failed.', 'Error'); }
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.error) || 'Request failed.', 'Error');
            }
        });
    });

    $(document).on('click', '.deactivateBtn', function () {
        $('#deactivateTerminalId').val($(this).data('id'));
        $('#deactivateTerminalName').text($(this).data('label'));
        $('#deactivateModal').modal('show');
    });

    $('#doDeactivateBtn').on('click', function () {
        var self = $(this).prop('disabled', true);
        $.ajax({
            type: 'POST', url: '{{ route("tenant.admin.eis.terminals.deactivate") }}',
            data: { _token: '{{ csrf_token() }}', id: $('#deactivateTerminalId').val() },
            timeout: 30000,
            beforeSend: function () { $('#progressBar').show(); },
            complete:   function () { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function (r) {
                if (r.success) {
                    toastr.success(r.success, 'Deactivated');
                    $('#deactivateModal').modal('hide');
                    allTerminals = {};
                    loadAllTerminals();
                } else { toastr.error(r.error || 'Failed.', 'Error'); }
            },
            error: function () { toastr.error('Request failed.', 'Error'); }
        });
    });

    $(document).on('click', '.deleteTerminalBtn', function () {
        $('#deleteTerminalId').val($(this).data('id'));
        $('#deleteTerminalName').text($(this).data('label'));
        $('#deleteTerminalModal').modal('show');
    });

    $('#doDeleteBtn').on('click', function () {
        var self = $(this).prop('disabled', true);
        $.ajax({
            type: 'POST', url: '{{ route("tenant.admin.eis.terminals.delete") }}',
            data: { _token: '{{ csrf_token() }}', id: $('#deleteTerminalId').val() },
            timeout: 30000,
            beforeSend: function () { $('#progressBar').show(); },
            complete:   function () { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function (r) {
                if (r.success) {
                    toastr.success(r.success, 'Deleted');
                    $('#deleteTerminalModal').modal('hide');
                    allTerminals = {};
                    loadAllTerminals();
                } else { toastr.error(r.error || 'Failed.', 'Error'); }
            },
            error: function () { toastr.error('Request failed.', 'Error'); }
        });
    });
});
</script>
@endsection