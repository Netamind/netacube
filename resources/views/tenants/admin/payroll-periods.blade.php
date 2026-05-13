@extends('tenants.admin.dashboard')
@section('content')
<style>
    .dt-buttons .btn {
        background: transparent !important;
        background-image: none !important;
        box-shadow: none !important;
        border-color: #5bc0de;
        color: #5bc0de;
    }
    .dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }
    .card-header {
        padding: 0.5rem 1.5rem !important;
        background: linear-gradient(to right, #4B5EBD, #576CC0);
        color: #fff;
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
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
    .modal-section-title {
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #6c757d;
        border-bottom: 1px solid #e9ecef;
        padding-bottom: 6px;
        margin: 18px 0 12px 0;
    }
    .badge-draft      { background-color: #6c757d; color:#fff; padding:4px 10px; border-radius:20px; font-size:11px; }
    .badge-processing { background-color: #0dcaf0; color:#fff; padding:4px 10px; border-radius:20px; font-size:11px; }
    .badge-approved   { background-color: #198754; color:#fff; padding:4px 10px; border-radius:20px; font-size:11px; }
    .badge-paid       { background-color: #4B5EBD; color:#fff; padding:4px 10px; border-radius:20px; font-size:11px; }

    /* Stats modal cards */
    .stats-summary-card {
        border-radius: 10px;
        padding: 14px 18px;
        color: #fff;
        text-align: center;
    }
    .stats-summary-card .s-label { font-size: 11px; opacity: 0.85; text-transform: uppercase; letter-spacing: 0.05em; }
    .stats-summary-card .s-value { font-size: 28px; font-weight: 700; line-height: 1.3; }
    .bg-s1 { background: linear-gradient(135deg,#4B5EBD,#6c7fe0); }
    .bg-s2 { background: linear-gradient(135deg,#198754,#27c87e); }
    .bg-s3 { background: linear-gradient(135deg,#0dcaf0,#0891b2); }
    .bg-s4 { background: linear-gradient(135deg,#fd7e14,#f59e0b); }
</style>

<div class="progress" id="progressBar" role="progressbar"
     style="height:8px; transform:rotate(180deg); display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page">
<div class="content">
<div class="container-fluid">

<div class="row mb-3"></div>

<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
        <i class="ri-calendar-check-line me-1"></i> Payroll Periods
    </h4>
    <div class="d-flex align-items-center">
        <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="statsBtn"         title="Statistics"><i class="ri-bar-chart-2-line"></i></a>
        <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn"          title="Info"><i class="ri-information-line"></i></a>
        <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn"  title="Download"><i class="ri-download-line"></i></a>
        <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="newDataBtn"       title="New Period"><i class="ri-add-line"></i></a>
    </div>
</div>

<div class="card-body">
<table id="maintable" class="table table-sm table-striped row-border order-column w-100">
    <thead style="background-color:#e2e2e9">
    <tr>
        <th>Period Name</th>
        <th style="text-align:center">Period Start</th>
        <th style="text-align:center">Period End</th>
        <th style="text-align:center">Pay Date</th>
        <th style="text-align:center">Employees</th>
        <th style="text-align:center">Total Net Pay</th>
        <th style="text-align:center">Status</th>
        <th style="text-align:center">Action</th>
    </tr>
    </thead>
    <tbody id="tbody">
    @foreach($periods as $period)
        <?php $row = "row".$period->id ?>
        <tr id="{{ $row }}">
            <td><strong>{{ $period->name }}</strong></td>
            <td style="text-align:center">{{ $period->period_start }}</td>
            <td style="text-align:center">{{ $period->period_end }}</td>
            <td style="text-align:center">{{ $period->pay_date }}</td>
            <td style="text-align:center">{{ $period->employee_count ?? 0 }}</td>
            <td style="text-align:center">{{ number_format($period->total_net_pay ?? 0, 2) }}</td>
            <td style="text-align:center">
                <span class="badge-{{ $period->status }}">{{ ucfirst($period->status) }}</span>
            </td>
            <td style="text-align:center">
                @if($period->status === 'draft')
                <a href="#" class="editDataBtn btn btn-light text-info btn-sm"
                   data-id="{{ $period->id }}"
                   data-row="{{ $row }}"
                   data-name="{{ $period->name }}"
                   data-period-start="{{ $period->period_start }}"
                   data-period-end="{{ $period->period_end }}"
                   data-pay-date="{{ $period->pay_date }}"
                   data-status="{{ $period->status }}"
                   data-notes="{{ $period->notes }}"
                   title="Edit">
                    <i class="ri-edit-box-line"></i>
                </a>
                <a href="#" class="generateBtn btn btn-light text-success btn-sm"
                   data-id="{{ $period->id }}"
                   data-name="{{ $period->name }}"
                   title="Generate Wage Bill">
                    <i class="ri-play-circle-line"></i>
                </a>
                <a href="#" class="deleteBtn btn btn-light text-danger btn-sm"
                   data-id="{{ $period->id }}"
                   data-row="{{ $row }}"
                   data-name="{{ $period->name }}"
                   title="Delete">
                    <i class="ri-delete-bin-line"></i>
                </a>
                @endif
                @if($period->status === 'processing')
                <a href="#" class="approveBtn btn btn-light text-warning btn-sm"
                   data-id="{{ $period->id }}"
                   data-name="{{ $period->name }}"
                   title="Approve Period">
                    <i class="ri-checkbox-circle-line"></i>
                </a>
                @endif
                @if($period->status === 'approved')
                <a href="#" class="markPaidBtn btn btn-light text-success btn-sm"
                   data-id="{{ $period->id }}"
                   data-name="{{ $period->name }}"
                   title="Mark as Paid">
                    <i class="ri-money-dollar-circle-line"></i>
                </a>
                @endif
                @if($period->status === 'paid')
                <span class="text-muted" style="font-size:11px;"><i class="ri-check-double-line"></i> Paid</span>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
</div>

</div>
</div>
</div>

{{-- STATISTICS MODAL --}}
<div class="modal fade" id="statsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(to right, #4B5EBD, #576CC0);">
                <h5 class="modal-title text-white"><i class="ri-bar-chart-2-line me-1"></i> Payroll Period Statistics</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="stats-summary-card bg-s1">
                            <div class="s-label">Total Periods</div>
                            <div class="s-value" id="sTotalPeriods">{{ $totalPeriods }}</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stats-summary-card bg-s2">
                            <div class="s-label">Paid Periods</div>
                            <div class="s-value" id="sPaidPeriods">{{ $paidPeriods }}</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stats-summary-card bg-s3">
                            <div class="s-label">Approved / Processing</div>
                            <div class="s-value" id="sApprovedPeriods">{{ $approvedPeriods }}</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="stats-summary-card bg-s4">
                            <div class="s-label">Draft Periods</div>
                            <div class="s-value" id="sDraftPeriods">{{ $draftPeriods }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- DOWNLOAD MODAL --}}
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Download</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Click a button to download payroll periods data.</p>
                <div class="buttons"></div>
            </div>
        </div>
    </div>
</div>

{{-- INFO MODAL --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Payroll Periods</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>A <strong>Payroll Period</strong> defines a single monthly pay run. The process is:</p>
                <ol>
                    <li>Create a period (e.g. <em>June 2026</em>) with start date, end date and pay date.</li>
                    <li>Click <i class="ri-play-circle-line text-success"></i> <strong>Generate</strong> — the system creates one wage bill entry per active employee, automatically pulling their salary, pension, loans and advances.</li>
                    <li>Review the wage bill, adjust any entries if needed.</li>
                    <li>Click <i class="ri-checkbox-circle-line text-warning"></i> <strong>Approve</strong> to lock the period.</li>
                    <li>After paying employees, click <i class="ri-money-dollar-circle-line text-success"></i> <strong>Mark Paid</strong>.</li>
                    <li>Download individual payslips from the Wage Bill view.</li>
                </ol>
                <div class="alert alert-warning mb-0">
                    <i class="ri-error-warning-line me-1"></i>
                    A period can only be deleted while it is in <strong>Draft</strong> status.
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ADD NEW PERIOD MODAL --}}
<div class="modal fade" id="newDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Payroll Period</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post" id="newDataForm">
                    @csrf
                    <div class="modal-section-title">Period Details</div>
                    <div class="row">
                        <div class="form-group col-12 mb-3">
                            <label>Period Name <span style="color:red">*</span></label>
                            <input type="text" class="form-control" name="name" placeholder="e.g. June 2026" autocomplete="off" required>
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label>Period Start <span style="color:red">*</span></label>
                            <input type="date" class="form-control" name="period_start" required>
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label>Period End <span style="color:red">*</span></label>
                            <input type="date" class="form-control" name="period_end" required>
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label>Pay Date <span style="color:red">*</span></label>
                            <input type="date" class="form-control" name="pay_date" required>
                        </div>
                        <div class="form-group col-12 mb-3">
                            <label>Notes</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes..."></textarea>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-2">
                        <a href="#" class="btn btn-secondary" id="cancelDataBtn">Cancel</a>
                        <a href="#" class="btn btn-primary"   id="submitDataBtn"><i class="ri-save-line me-1"></i> Save Period</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- EDIT PERIOD MODAL --}}
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Payroll Period</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="#" method="post" id="editDataForm">
                    @csrf
                    <input type="hidden" name="id"      id="editId">
                    <input type="hidden" name="editrow" id="editRow">
                    <div class="modal-section-title">Period Details</div>
                    <div class="row">
                        <div class="form-group col-12 mb-3">
                            <label>Period Name <span style="color:red">*</span></label>
                            <input type="text" class="form-control" name="name" id="editName" required>
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label>Period Start <span style="color:red">*</span></label>
                            <input type="date" class="form-control" name="period_start" id="editPeriodStart" required>
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label>Period End <span style="color:red">*</span></label>
                            <input type="date" class="form-control" name="period_end" id="editPeriodEnd" required>
                        </div>
                        <div class="form-group col-md-6 mb-3">
                            <label>Pay Date <span style="color:red">*</span></label>
                            <input type="date" class="form-control" name="pay_date" id="editPayDate" required>
                        </div>
                        <div class="form-group col-12 mb-3">
                            <label>Notes</label>
                            <textarea class="form-control" name="notes" id="editNotes" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-2">
                        <a href="#" class="btn btn-secondary" id="cancelEditBtn">Cancel</a>
                        <a href="#" class="btn btn-primary"   id="submitUpdateBtn"><i class="ri-save-line me-1"></i> Update Period</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- GENERATE CONFIRM MODAL --}}
<div class="modal fade" id="generateModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width:400px; margin:1.75rem auto;">
        <div class="modal-content">
            <div class="modal-body text-center pb-4 pt-4">
                <i class="ri-play-circle-line text-success" style="font-size:60px"></i>
                <h4 class="mt-2">Generate Wage Bill</h4>
                <p class="text-muted">
                    This will create one payroll entry per active employee for
                    <strong id="generatePeriodName"></strong>.<br>
                    Salary, pension, loans and advances will be pulled automatically.
                </p>
                <input type="hidden" id="generatePeriodId">
                <div class="d-flex justify-content-center gap-2 mt-3">
                    <a href="#" class="btn btn-secondary" id="cancelGenerateBtn">Cancel</a>
                    <a href="#" class="btn btn-success"   id="confirmGenerateBtn"><i class="ri-play-circle-line me-1"></i> Yes, Generate</a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- APPROVE CONFIRM MODAL --}}
<div class="modal fade" id="approveModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width:400px; margin:1.75rem auto;">
        <div class="modal-content">
            <div class="modal-body text-center pb-4 pt-4">
                <i class="ri-checkbox-circle-line text-warning" style="font-size:60px"></i>
                <h4 class="mt-2">Approve Period</h4>
                <p class="text-muted">
                    Approving <strong id="approvePeriodName"></strong> will lock all
                    wage bill entries. No further edits will be allowed.
                </p>
                <input type="hidden" id="approvePeriodId">
                <div class="d-flex justify-content-center gap-2 mt-3">
                    <a href="#" class="btn btn-secondary" id="cancelApproveBtn">Cancel</a>
                    <a href="#" class="btn btn-warning"   id="confirmApproveBtn"><i class="ri-checkbox-circle-line me-1"></i> Yes, Approve</a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MARK PAID CONFIRM MODAL --}}
<div class="modal fade" id="markPaidModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width:400px; margin:1.75rem auto;">
        <div class="modal-content">
            <div class="modal-body text-center pb-4 pt-4">
                <i class="ri-money-dollar-circle-line text-success" style="font-size:60px"></i>
                <h4 class="mt-2">Mark as Paid</h4>
                <p class="text-muted">
                    Confirm that all employees have been paid for
                    <strong id="markPaidPeriodName"></strong>.
                </p>
                <input type="hidden" id="markPaidPeriodId">
                <div class="d-flex justify-content-center gap-2 mt-3">
                    <a href="#" class="btn btn-secondary" id="cancelMarkPaidBtn">Cancel</a>
                    <a href="#" class="btn btn-success"   id="confirmMarkPaidBtn"><i class="ri-money-dollar-circle-line me-1"></i> Yes, Mark Paid</a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- DELETE CONFIRM MODAL --}}
<div class="modal fade" id="deleteModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width:350px; margin:1.75rem auto;">
        <div class="modal-content">
            <div class="modal-body text-center pb-4 pt-4">
                <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
                <h4 class="mt-2">Are you sure?</h4>
                <h5>Delete <span id="deletePeriodName" class="text-danger"></span>?</h5>
                <p class="text-muted">This will also delete all wage bill entries for this period.</p>
                <input type="hidden" id="deletePeriodId">
                <input type="hidden" id="deletePeriodRow">
                <div class="d-flex justify-content-center gap-2 mt-3">
                    <a href="#" class="btn btn-info"   id="cancelDeleteBtn">No, Keep it</a>
                    <a href="#" class="btn btn-danger" id="confirmDeleteBtn">Yes, Delete</a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {

    toastr.options = { closeButton: true, progressBar: true, showMethod: 'slideDown', timeOut: 5000 };

    var maintableTitle = "Payroll Periods";
    var table = $('#maintable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, "All"]],
        order: [[0, 'desc']],
        buttons: [
            { extend: 'excelHtml5', title: maintableTitle, exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'csvHtml5',   title: maintableTitle, exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'pdfHtml5',   title: maintableTitle, exportOptions: { columns: ':visible:not(:last-child)' },
              customize: function(doc){ doc.content[1].table.widths = Array(doc.content[1].table.body[0].length+1).join('*').split(''); }
            }
        ]
    });
    table.buttons().container().appendTo($('#buttonsModal .buttons'));

    var newModal      = new bootstrap.Modal('#newDataModal');
    var editModal     = new bootstrap.Modal('#editDataModal');
    var generateModal = new bootstrap.Modal('#generateModal');
    var approveModal  = new bootstrap.Modal('#approveModal');
    var markPaidModal = new bootstrap.Modal('#markPaidModal');
    var deleteModal   = new bootstrap.Modal('#deleteModal');

    $('#statsBtn').on('click',           function(e){ e.preventDefault(); $('#statsModal').modal('show'); });
    $('#newDataBtn').on('click',         function(e){ e.preventDefault(); $('#newDataForm')[0].reset(); newModal.show(); });
    $('#infoBtn').on('click',            function(e){ e.preventDefault(); $('#infoModal').modal('show'); });
    $('#tableButtonsBtn').on('click',    function(e){ e.preventDefault(); $('#buttonsModal').modal('show'); });
    $('#cancelDataBtn').on('click',      function(e){ e.preventDefault(); newModal.hide(); });
    $('#cancelEditBtn').on('click',      function(e){ e.preventDefault(); editModal.hide(); });
    $('#cancelGenerateBtn').on('click',  function(e){ e.preventDefault(); generateModal.hide(); });
    $('#cancelApproveBtn').on('click',   function(e){ e.preventDefault(); approveModal.hide(); });
    $('#cancelMarkPaidBtn').on('click',  function(e){ e.preventDefault(); markPaidModal.hide(); });
    $('#cancelDeleteBtn').on('click',    function(e){ e.preventDefault(); deleteModal.hide(); });

    function badgeHtml(status) {
        return '<span class="badge-' + status + '">' + status.charAt(0).toUpperCase() + status.slice(1) + '</span>';
    }

    function buildRowHtml(p, row) {
        var actions = '';

        if (p.status === 'draft') {
            actions += '<a href="#" class="editDataBtn btn btn-light text-info btn-sm"'
                + ' data-id="' + p.id + '" data-row="' + row + '" data-name="' + p.name + '"'
                + ' data-period-start="' + p.period_start + '" data-period-end="' + p.period_end + '"'
                + ' data-pay-date="' + p.pay_date + '" data-status="' + p.status + '" data-notes="' + (p.notes||'') + '"'
                + ' title="Edit"><i class="ri-edit-box-line"></i></a> ';
            actions += '<a href="#" class="generateBtn btn btn-light text-success btn-sm"'
                + ' data-id="' + p.id + '" data-name="' + p.name + '"'
                + ' title="Generate Wage Bill"><i class="ri-play-circle-line"></i></a> ';
            actions += '<a href="#" class="deleteBtn btn btn-light text-danger btn-sm"'
                + ' data-id="' + p.id + '" data-row="' + row + '" data-name="' + p.name + '"'
                + ' title="Delete"><i class="ri-delete-bin-line"></i></a>';
        }
        if (p.status === 'processing') {
            actions += '<a href="#" class="approveBtn btn btn-light text-warning btn-sm"'
                + ' data-id="' + p.id + '" data-name="' + p.name + '"'
                + ' title="Approve"><i class="ri-checkbox-circle-line"></i></a>';
        }
        if (p.status === 'approved') {
            actions += '<a href="#" class="markPaidBtn btn btn-light text-success btn-sm"'
                + ' data-id="' + p.id + '" data-name="' + p.name + '"'
                + ' title="Mark Paid"><i class="ri-money-dollar-circle-line"></i></a>';
        }
        if (p.status === 'paid') {
            actions += '<span class="text-muted" style="font-size:11px;"><i class="ri-check-double-line"></i> Paid</span>';
        }

        return '<tr id="' + row + '">'
            + '<td><strong>' + p.name + '</strong></td>'
            + '<td style="text-align:center">' + p.period_start + '</td>'
            + '<td style="text-align:center">' + p.period_end   + '</td>'
            + '<td style="text-align:center">' + p.pay_date     + '</td>'
            + '<td style="text-align:center">' + (p.employee_count || 0) + '</td>'
            + '<td style="text-align:center">' + parseFloat(p.total_net_pay || 0).toLocaleString('en', {minimumFractionDigits:2}) + '</td>'
            + '<td style="text-align:center">' + badgeHtml(p.status) + '</td>'
            + '<td style="text-align:center">' + actions + '</td>'
            + '</tr>';
    }

    // ── ADD ───────────────────────────────────────────────────────────────────
    $('#submitDataBtn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            type: 'POST', url: '{{ route("tenant.admin.hr.payroll.period.store") }}',
            data: $('#newDataForm').serialize(), timeout: 60000,
            beforeSend: () => $('#progressBar').show(),
            complete:   () => { $('#progressBar').hide(); $btn.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    var row = 'row' + data.period.id;
                    table.row.add($(buildRowHtml(data.period, row))).draw(false);
                    updateSummary(data.summary);
                    newModal.hide();
                } else if (data.status === 422) {
                    var msg = ''; $.each(data.errors, function(k,v){ msg += v + '\n'; }); toastr.error(msg, 'Validation');
                } else { toastr.error(data.error || 'Failed', 'Error'); }
            },
            error: function(xhr) {
                if (xhr.status === 422) { var msg = ''; $.each(xhr.responseJSON.errors, function(k,v){ msg += v + '\n'; }); toastr.error(msg, 'Validation'); }
                else { toastr.error('Server error', 'Error'); }
            }
        });
    });

    // ── EDIT populate ─────────────────────────────────────────────────────────
    $('#tbody').on('click', '.editDataBtn', function() {
        $('#editId').val($(this).data('id'));
        $('#editRow').val($(this).data('row'));
        $('#editName').val($(this).data('name'));
        $('#editPeriodStart').val($(this).data('period-start'));
        $('#editPeriodEnd').val($(this).data('period-end'));
        $('#editPayDate').val($(this).data('pay-date'));
        $('#editNotes').val($(this).data('notes'));
        editModal.show();
    });

    // ── UPDATE ────────────────────────────────────────────────────────────────
    $('#submitUpdateBtn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this).prop('disabled', true);
        var row  = $('#editRow').val();
        $.ajax({
            type: 'POST', url: '{{ route("tenant.admin.hr.payroll.period.update") }}',
            data: $('#editDataForm').serialize(), timeout: 60000,
            beforeSend: () => $('#progressBar').show(),
            complete:   () => { $('#progressBar').hide(); $btn.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    table.row('#' + row).remove();
                    table.row.add($(buildRowHtml(data.period, row))).draw(false);
                    editModal.hide();
                } else if (data.status === 422) {
                    var msg = ''; $.each(data.errors, function(k,v){ msg += v + '\n'; }); toastr.error(msg, 'Validation');
                } else { toastr.error(data.error || 'Failed', 'Error'); }
            },
            error: function(xhr) {
                if (xhr.status === 422) { var msg = ''; $.each(xhr.responseJSON.errors, function(k,v){ msg += v + '\n'; }); toastr.error(msg, 'Validation'); }
                else { toastr.error('Server error', 'Error'); }
            }
        });
    });

    // ── GENERATE ──────────────────────────────────────────────────────────────
    $('#tbody').on('click', '.generateBtn', function() {
        $('#generatePeriodId').val($(this).data('id'));
        $('#generatePeriodName').text($(this).data('name'));
        generateModal.show();
    });
    $('#confirmGenerateBtn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            type: 'POST', url: '{{ route("tenant.admin.hr.payroll.period.generate") }}',
            data: { _token: '{{ csrf_token() }}', id: $('#generatePeriodId').val() }, timeout: 120000,
            beforeSend: () => $('#progressBar').show(),
            complete:   () => { $('#progressBar').hide(); $btn.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Generated');
                    var row = 'row' + data.period.id;
                    table.row('#' + row).remove();
                    table.row.add($(buildRowHtml(data.period, row))).draw(false);
                    updateSummary(data.summary);
                    generateModal.hide();
                } else { toastr.error(data.error || 'Failed', 'Error'); }
            },
            error: () => toastr.error('Server error', 'Error')
        });
    });

    // ── APPROVE ───────────────────────────────────────────────────────────────
    $('#tbody').on('click', '.approveBtn', function() {
        $('#approvePeriodId').val($(this).data('id'));
        $('#approvePeriodName').text($(this).data('name'));
        approveModal.show();
    });
    $('#confirmApproveBtn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            type: 'POST', url: '{{ route("tenant.admin.hr.payroll.period.approve") }}',
            data: { _token: '{{ csrf_token() }}', id: $('#approvePeriodId').val() }, timeout: 60000,
            beforeSend: () => $('#progressBar').show(),
            complete:   () => { $('#progressBar').hide(); $btn.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Approved');
                    var row = 'row' + data.period.id;
                    table.row('#' + row).remove();
                    table.row.add($(buildRowHtml(data.period, row))).draw(false);
                    updateSummary(data.summary);
                    approveModal.hide();
                } else { toastr.error(data.error || 'Failed', 'Error'); }
            },
            error: () => toastr.error('Server error', 'Error')
        });
    });

    // ── MARK PAID ─────────────────────────────────────────────────────────────
    $('#tbody').on('click', '.markPaidBtn', function() {
        $('#markPaidPeriodId').val($(this).data('id'));
        $('#markPaidPeriodName').text($(this).data('name'));
        markPaidModal.show();
    });
    $('#confirmMarkPaidBtn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            type: 'POST', url: '{{ route("tenant.admin.hr.payroll.period.markpaid") }}',
            data: { _token: '{{ csrf_token() }}', id: $('#markPaidPeriodId').val() }, timeout: 60000,
            beforeSend: () => $('#progressBar').show(),
            complete:   () => { $('#progressBar').hide(); $btn.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Paid');
                    var row = 'row' + data.period.id;
                    table.row('#' + row).remove();
                    table.row.add($(buildRowHtml(data.period, row))).draw(false);
                    updateSummary(data.summary);
                    markPaidModal.hide();
                } else { toastr.error(data.error || 'Failed', 'Error'); }
            },
            error: () => toastr.error('Server error', 'Error')
        });
    });

    // ── DELETE ────────────────────────────────────────────────────────────────
    $('#tbody').on('click', '.deleteBtn', function() {
        $('#deletePeriodId').val($(this).data('id'));
        $('#deletePeriodRow').val($(this).data('row'));
        $('#deletePeriodName').text($(this).data('name'));
        deleteModal.show();
    });
    $('#confirmDeleteBtn').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this).prop('disabled', true);
        var row  = $('#deletePeriodRow').val();
        $.ajax({
            type: 'POST', url: '{{ route("tenant.admin.hr.payroll.period.delete") }}',
            data: { _token: '{{ csrf_token() }}', id: $('#deletePeriodId').val(), _method: 'DELETE' }, timeout: 60000,
            beforeSend: () => $('#progressBar').show(),
            complete:   () => { $('#progressBar').hide(); $btn.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Deleted');
                    table.row('#' + row).remove().draw(false);
                    updateSummary(data.summary);
                    deleteModal.hide();
                } else { toastr.error(data.error || 'Failed', 'Error'); }
            },
            error: () => toastr.error('Server error', 'Error')
        });
    });

    // ── Summary update ────────────────────────────────────────────────────────
    function updateSummary(s) {
        if (!s) return;
        $('#sTotalPeriods').text(s.total);
        $('#sPaidPeriods').text(s.paid);
        $('#sApprovedPeriods').text(s.approved);
        $('#sDraftPeriods').text(s.draft);
    }
});
</script>
@endsection