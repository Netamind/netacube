@extends('tenants.admin.dashboard')
@section('content')
@php
    $brackets = DB::connection('tenant')
        ->table('paye_brackets')
        ->orderBy('effective_from', 'desc')
        ->orderBy('income_from',    'asc')
        ->get();

    $activeBrackets  = $brackets->whereNull('effective_to');
    $retiredBrackets = $brackets->whereNotNull('effective_to');
@endphp

<style>
.dt-buttons .btn {
  background: transparent !important;
  background-image: none !important;
  box-shadow: none !important;
  border-color: #5bc0de;
  color: #5bc0de;
}
.dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }
.card-header { padding: 0.5rem 1.5rem !important; background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff; }
.card-body { padding: 0 1.5rem 1.5rem 1.5rem !important; }
.card-header .btn-light { height: 28px; padding: 0 10px; display: flex; align-items: center; justify-content: center; line-height: 1; }
.card-header .btn-light:hover { background-color: #f8f9fa; transition: background-color 0.2s ease-in-out; }
.card { border: none; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); border-radius: 10px; }
.card-header h4 { color: #fff; font-weight: 600; margin-bottom: 0; display: flex; align-items: center; }
.card-header h4 i { margin-right: 0.25rem; }
table.dataTable.fixedHeader-floating, table.dataTable.fixedHeader-locked { background: #fff !important; border-bottom: none !important; }
table.dataTable thead th.fixedHeader-floating { background: #e2e2e9 !important; }

/* Fixed first column overrides */
table.dataTable thead th.dtfc-fixed-left {
  background-color: #e2e2e9 !important;
}
table.dataTable tbody td.dtfc-fixed-left {
  background-color: #fff !important;
}
table.dataTable.table-striped tbody tr:nth-child(odd) td.dtfc-fixed-left {
  background-color: rgba(0,0,0,.05) !important;
}

/* Modal header */
.mh-blue  { background: linear-gradient(135deg, #4B5EBD, #576CC0); padding: 14px 18px !important; border-bottom: none; border-radius: 8px 8px 0 0; }
.mh-title { color: #fff; font-size: 15px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.mh-close { filter: brightness(0) invert(1); opacity: .8; }
.mh-close:hover { opacity: 1; }

/* Badges */
.badge-active  { background: #198754; color: #fff; padding: 2px 9px; border-radius: 20px; font-size: 11px; }
.badge-retired { background: #6c757d; color: #fff; padding: 2px 9px; border-radius: 20px; font-size: 11px; }
.badge-free    { background: #0dcaf0; color: #fff; padding: 2px 9px; border-radius: 20px; font-size: 11px; }

.info-alert {
  background: #fff8e1;
  border: 1px solid #ffe082;
  border-left: 4px solid #f0a500;
  border-radius: 6px;
  padding: 10px 14px;
  font-size: 12px;
  color: #5d4037;
  margin-bottom: 16px;
}

.modal-section-title {
  font-size: 11px; font-weight: 600; text-transform: uppercase;
  letter-spacing: .07em; color: #6c757d;
  border-bottom: 1px solid #e9ecef; padding-bottom: 6px; margin: 16px 0 10px;
}
</style>

<div class="progress" id="progressBar" role="progressbar" aria-label="Animated striped" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="height:8px; transform:rotate(180deg); display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page">
<div class="content">
<div class="container-fluid">

<div class="row mb-3"></div>

{{-- ── ACTIVE BRACKETS CARD ── --}}
<div class="card mb-4">
<div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
        <i class="ri-percent-line"></i> PAYE Brackets — Active
    </h4>
    <div class="d-flex align-items-center">
        <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="infoBtn"    title="How brackets work"><i class="ri-information-line"></i></a>
        <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="testBtn"    title="Test PAYE calculation"><i class="ri-calculator-line"></i></a>
        <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="newDataBtn" title="Add Bracket"><i class="ri-add-circle-line"></i></a>
    </div>
    <?php $activetableTitle = "PAYE Brackets — Active"; ?>
</div>

<div class="card-body">
<table id="activetable" class="table table-sm table-striped row-border order-column w-100">
    <thead style="background-color:#e2e2e9">
    <tr>
        <th>Description</th>
        <th style="text-align:center">Income From (MWK)</th>
        <th style="text-align:center">Income To (MWK)</th>
        <th style="text-align:center">Rate</th>
        <th style="text-align:center">Effective From</th>
        <th style="text-align:center">Status</th>
        <th style="text-align:center">Action</th>
    </tr>
    </thead>
    <tbody id="activetbody">
    @foreach($activeBrackets->sortBy('income_from') as $b)
        @php $row = 'row' . $b->id; @endphp
        <tr id="{{ $row }}">
            <td>{{ $b->description ?? '—' }}</td>
            <td style="text-align:center">{{ number_format($b->income_from, 2) }}</td>
            <td style="text-align:center">{!! $b->income_to !== null ? number_format($b->income_to, 2) : '<span class="text-muted fst-italic">No ceiling</span>' !!}</td>
            <td style="text-align:center">
                @if($b->rate == 0)
                    <span class="badge-free">0% — Free</span>
                @else
                    <strong>{{ number_format($b->rate, 2) }}%</strong>
                @endif
            </td>
            <td style="text-align:center">{{ \Carbon\Carbon::parse($b->effective_from)->format('d M Y') }}</td>
            <td style="text-align:center"><span class="badge-active">Active</span></td>
            <td style="text-align:center; white-space:nowrap;">
                <a href="#" class="editBtn"
                   data-id="{{ $b->id }}"
                   data-row="{{ $row }}"
                   data-income-from="{{ $b->income_from }}"
                   data-income-to="{{ $b->income_to ?? '' }}"
                   data-rate="{{ $b->rate }}"
                   data-effective-from="{{ $b->effective_from }}"
                   data-effective-to="{{ $b->effective_to ?? '' }}"
                   data-description="{{ $b->description ?? '' }}">
                    <i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px;"></i>
                </a>
                <a href="#" class="retireBtn"
                   data-id="{{ $b->id }}"
                   data-row="{{ $row }}"
                   data-description="{{ $b->description ?? 'this bracket' }}">
                    <i class="ri-archive-line text-warning" style="font-weight:bold;font-size:17px;" title="Retire bracket"></i>
                </a>
                <a href="#" class="deleteBtn"
                   data-id="{{ $b->id }}"
                   data-row="{{ $row }}"
                   data-description="{{ $b->description ?? 'this bracket' }}">
                    <i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px;"></i>
                </a>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
</div>

{{-- ── RETIRED BRACKETS CARD ── --}}
@if($retiredBrackets->count())
<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
        <i class="ri-archive-line"></i> PAYE Brackets — Retired (Historical)
    </h4>
    <?php $retiredtableTitle = "PAYE Brackets — Retired"; ?>
</div>
<div class="card-body">
<table id="retiredtable" class="table table-sm table-striped row-border order-column w-100">
    <thead style="background-color:#e2e2e9">
    <tr>
        <th>Description</th>
        <th style="text-align:center">Income From (MWK)</th>
        <th style="text-align:center">Income To (MWK)</th>
        <th style="text-align:center">Rate</th>
        <th style="text-align:center">Effective From</th>
        <th style="text-align:center">Effective To</th>
        <th style="text-align:center">Status</th>
    </tr>
    </thead>
    <tbody id="retiredbody">
    @foreach($retiredBrackets->sortByDesc('effective_to') as $b)
        <tr>
            <td>{{ $b->description ?? '—' }}</td>
            <td style="text-align:center">{{ number_format($b->income_from, 2) }}</td>
            <td style="text-align:center">{{ $b->income_to !== null ? number_format($b->income_to, 2) : '—' }}</td>
            <td style="text-align:center"><strong>{{ number_format($b->rate, 2) }}%</strong></td>
            <td style="text-align:center">{{ \Carbon\Carbon::parse($b->effective_from)->format('d M Y') }}</td>
            <td style="text-align:center">{{ \Carbon\Carbon::parse($b->effective_to)->format('d M Y') }}</td>
            <td style="text-align:center"><span class="badge-retired">Retired</span></td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
</div>
@endif

</div>
</div>
</div>


{{-- ══ INFO MODAL ══ --}}
<div class="modal fade" id="infoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">How PAYE Brackets Work</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" style="font-size:13px;">
            <p class="mb-2">PAYE is calculated <strong>progressively</strong> — each band only taxes the portion of income that falls within it.</p>
            <p class="mb-2"><strong>Example</strong> — employee earns MWK 450,000/month:</p>
            <table class="table table-sm table-bordered mb-3" style="font-size:12px;">
                <thead class="table-light"><tr><th>Band</th><th>Range</th><th>Rate</th><th>Tax</th></tr></thead>
                <tbody>
                    <tr><td>1</td><td>0 – 100,000</td><td>0%</td><td>MWK 0</td></tr>
                    <tr><td>2</td><td>100,001 – 450,000 (350,000 taxable)</td><td>25%</td><td>MWK 87,500</td></tr>
                    <tr class="table-warning"><td colspan="3"><strong>Total PAYE</strong></td><td><strong>MWK 87,500</strong></td></tr>
                </tbody>
            </table>
            <p class="mb-1"><strong>When MRA changes rates (new financial year):</strong></p>
            <ol style="padding-left:18px;" class="mb-0">
                <li class="mb-1">Click the <i class="ri-archive-line text-warning"></i> retire icon on each current bracket and set its end date.</li>
                <li class="mb-1">Add the new brackets with the new <em>Effective From</em> date.</li>
                <li>Re-generate any payroll periods that fall under the new rates.</li>
            </ol>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        </div>
    </div></div>
</div>


{{-- ══ TEST / CALCULATOR MODAL ══ --}}
<div class="modal fade" id="testModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header mh-blue">
            <h5 class="modal-title mh-title"><i class="ri-calculator-line"></i>&nbsp; Test PAYE Calculation</h5>
            <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
            <p style="font-size:13px;" class="mb-3">Enter a gross salary to see how the active brackets compute PAYE.</p>
            <div class="row g-2 mb-3">
                <div class="col-8">
                    <input type="number" class="form-control" id="testGross" placeholder="e.g. 450000" min="0" step="0.01">
                </div>
                <div class="col-4">
                    <a href="#" class="btn btn-primary w-100" id="runTestBtn">Calculate</a>
                </div>
            </div>
            <div id="testResult" style="display:none;">
                <div class="modal-section-title" style="margin-top:0;">Breakdown</div>
                <table class="table table-sm table-bordered" style="font-size:12px;">
                    <thead class="table-light">
                        <tr><th>Band</th><th>Range (MWK)</th><th>Rate</th><th>Taxable Amount</th><th>Tax</th></tr>
                    </thead>
                    <tbody id="testBreakdownBody"></tbody>
                    <tfoot>
                        <tr class="table-primary">
                            <td colspan="4"><strong>Total PAYE</strong></td>
                            <td><strong id="testTotalPaye"></strong></td>
                        </tr>
                        <tr class="table-light">
                            <td colspan="4">Net (Gross − PAYE)</td>
                            <td id="testNet"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
        </div>
    </div></div>
</div>


{{-- ══ ADD MODAL ══ --}}
<div class="modal fade" id="newDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header mh-blue">
            <h5 class="modal-title mh-title"><i class="ri-add-circle-line"></i>&nbsp; Add PAYE Bracket</h5>
            <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="info-alert">
                <i class="ri-information-line me-1"></i>
                Leave <strong>Income To</strong> blank for the top band (no ceiling). Only one active top band is allowed.
            </div>
            <form id="newDataForm">
                @csrf
                <div class="row">
                    <div class="form-group col-md-6 mb-3">
                        <label style="font-size:13px;">Income From (MWK) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" name="income_from" required>
                    </div>
                    <div class="form-group col-md-6 mb-3">
                        <label style="font-size:13px;">Income To (MWK) <span class="text-muted" style="font-size:11px;">(blank = no ceiling)</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" name="income_to">
                    </div>
                    <div class="form-group col-md-6 mb-3">
                        <label style="font-size:13px;">Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control" name="rate" placeholder="e.g. 25">
                    </div>
                    <div class="form-group col-md-6 mb-3">
                        <label style="font-size:13px;">Description</label>
                        <input type="text" class="form-control" name="description" placeholder="e.g. 25% band">
                    </div>
                    <div class="form-group col-md-6 mb-3">
                        <label style="font-size:13px;">Effective From <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="effective_from" required>
                    </div>
                    <div class="form-group col-md-6 mb-3">
                        <label style="font-size:13px;">Effective To <span class="text-muted" style="font-size:11px;">(blank = active)</span></label>
                        <input type="date" class="form-control" name="effective_to">
                    </div>
                </div>
                <a href="#" class="btn btn-primary float-end mt-2 mb-2" id="submitDataBtn">Save Bracket</a>
                <a href="#" class="btn btn-secondary float-end mt-2 mb-2 mx-2" id="cancelDataBtn">Cancel</a>
            </form>
        </div>
    </div></div>
</div>


{{-- ══ EDIT MODAL ══ --}}
<div class="modal fade" id="editDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header mh-blue">
            <h5 class="modal-title mh-title"><i class="ri-edit-box-line"></i>&nbsp; Edit PAYE Bracket</h5>
            <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <form id="editDataForm">
                @csrf
                <input type="hidden" name="id"  id="editId">
                <input type="hidden" name="row" id="editRow">
                <div class="row">
                    <div class="form-group col-md-6 mb-3">
                        <label style="font-size:13px;">Income From (MWK) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" name="income_from" id="editIncomeFrom" required>
                    </div>
                    <div class="form-group col-md-6 mb-3">
                        <label style="font-size:13px;">Income To (MWK) <span class="text-muted" style="font-size:11px;">(blank = no ceiling)</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" name="income_to" id="editIncomeTo">
                    </div>
                    <div class="form-group col-md-6 mb-3">
                        <label style="font-size:13px;">Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control" name="rate" id="editRate" required>
                    </div>
                    <div class="form-group col-md-6 mb-3">
                        <label style="font-size:13px;">Description</label>
                        <input type="text" class="form-control" name="description" id="editDescription">
                    </div>
                    <div class="form-group col-md-6 mb-3">
                        <label style="font-size:13px;">Effective From <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="effective_from" id="editEffectiveFrom" required>
                    </div>
                    <div class="form-group col-md-6 mb-3">
                        <label style="font-size:13px;">Effective To <span class="text-muted" style="font-size:11px;">(blank = active)</span></label>
                        <input type="date" class="form-control" name="effective_to" id="editEffectiveTo">
                    </div>
                </div>
                <a href="#" class="btn btn-primary float-end mt-2 mb-2" id="submitEditBtn">Save Changes</a>
                <a href="#" class="btn btn-secondary float-end mt-2 mb-2 mx-2" id="cancelEditBtn">Cancel</a>
            </form>
        </div>
    </div></div>
</div>


{{-- ══ RETIRE MODAL ══ --}}
<div class="modal fade" id="retireModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width:380px;"><div class="modal-content">
        <div class="modal-header mh-blue">
            <h5 class="modal-title mh-title"><i class="ri-archive-line"></i>&nbsp; Retire Bracket</h5>
            <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
            <p style="font-size:13px;">Set the last effective date for <strong><span id="retireDescription"></span></strong>. The bracket will be moved to the historical table.</p>
            <form id="retireForm">
                @csrf
                <input type="hidden" name="id"  id="retireId">
                <input type="hidden" name="row" id="retireRow">
                <div class="form-group mb-3">
                    <label style="font-size:13px;">Effective To (last date active) <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="effective_to" id="retireEffectiveTo" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <a href="#" class="btn btn-warning btn-sm" id="submitRetireBtn">Retire Bracket</a>
            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        </div>
    </div></div>
</div>


{{-- ══ DELETE CONFIRM MODAL ══ --}}
<div class="modal fade" id="singleDeleteDataModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" style="max-width:350px; margin:1.75rem auto;">
        <div class="modal-content">
            <div class="modal-body text-center pb-4">
                <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
                <h4>Delete <span id="singleDisplayDeleteLabel" class="text-danger"></span>?</h4>
                <h5>This cannot be undone.</h5>
                <input type="hidden" id="singleDeleteId">
                <input type="hidden" id="singleDeleteRow">
                <a href="#" class="btn btn-danger"  id="submitSingleDeleteDataBtn" style="margin-top:10px;margin-bottom:10px;margin-right:5px;">Yes, Delete it</a>
                <a href="#" class="btn btn-info"    id="keepSingleDataBtn"         style="margin-top:10px;margin-bottom:10px;">No, Keep it</a>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {

    toastr.options = { closeButton: true, progressBar: true, showMethod: 'slideDown', timeOut: 5000, allowHtml: true };

    // ── Active brackets DataTable ─────────────────────────────────────────
    var table = $('#activetable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
        fixedColumns: { left: 1 },
        scrollX: true,
        order: [[1, 'asc']],
        columnDefs: [{ targets: [6], orderable: false }],
        buttons: [
            { extend: 'excelHtml5', title: @json($activetableTitle), exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'csvHtml5',   title: @json($activetableTitle), exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'pdfHtml5',   title: @json($activetableTitle), exportOptions: { columns: ':visible:not(:last-child)' },
              customize: function(doc) { doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split(''); }
            }
        ]
    });

    // ── Retired brackets DataTable ────────────────────────────────────────
    if ($('#retiredtable').length) {
        $('#retiredtable').DataTable({
            dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
            fixedColumns: { left: 1 },
            scrollX: true,
            order: [[4, 'desc'], [1, 'asc']],
            buttons: [
                { extend: 'excelHtml5', title: @json($retiredtableTitle ?? 'PAYE Brackets — Retired'), exportOptions: { columns: ':visible' } },
                { extend: 'csvHtml5',   title: @json($retiredtableTitle ?? 'PAYE Brackets — Retired'), exportOptions: { columns: ':visible' } }
            ]
        });
    }

    // ── Bracket data from PHP for the JS calculator ───────────────────────
    var activeBrackets = @json($activeBrackets->sortBy('income_from')->values());

    // ── Toolbar ───────────────────────────────────────────────────────────
    $('#infoBtn').click(function(e)    { e.preventDefault(); $('#infoModal').modal('show'); });
    $('#testBtn').click(function(e)    { e.preventDefault(); $('#testResult').hide(); $('#testGross').val(''); $('#testModal').modal('show'); });
    $('#newDataBtn').click(function(e) { e.preventDefault(); $('#newDataForm')[0].reset(); $('#newDataModal').modal('show'); });

    $('#cancelDataBtn').click(function(e)  { e.preventDefault(); $('#newDataModal').modal('hide'); });
    $('#cancelEditBtn').click(function(e)  { e.preventDefault(); $('#editDataModal').modal('hide'); });
    $('#keepSingleDataBtn').click(function(e) {
        e.preventDefault();
        toastr.info('Bracket is safe.', 'OK');
        $('#singleDeleteDataModal').modal('hide');
    });

    // ── Helper: format number ─────────────────────────────────────────────
    function fmt(n) {
        return parseFloat(n || 0).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // ── Build active table row HTML ───────────────────────────────────────
    function buildRow(b, row) {
        var incomeTo = (b.income_to !== null && b.income_to !== '')
            ? fmt(b.income_to)
            : '<span class="text-muted fst-italic">No ceiling</span>';

        var rateBadge = parseFloat(b.rate) === 0
            ? '<span class="badge-free">0% — Free</span>'
            : '<strong>' + parseFloat(b.rate).toFixed(2) + '%</strong>';

        return '<tr id="' + row + '">'
            + '<td>' + (b.description || '—') + '</td>'
            + '<td style="text-align:center">' + fmt(b.income_from) + '</td>'
            + '<td style="text-align:center">' + incomeTo + '</td>'
            + '<td style="text-align:center">' + rateBadge + '</td>'
            + '<td style="text-align:center">' + (b.effective_from || '—') + '</td>'
            + '<td style="text-align:center"><span class="badge-active">Active</span></td>'
            + '<td style="text-align:center; white-space:nowrap;">'
            +   '<a href="#" class="editBtn"'
            +     ' data-id="' + b.id + '" data-row="' + row + '"'
            +     ' data-income-from="' + b.income_from + '"'
            +     ' data-income-to="'   + (b.income_to  || '') + '"'
            +     ' data-rate="'         + b.rate + '"'
            +     ' data-effective-from="' + (b.effective_from || '') + '"'
            +     ' data-effective-to="'   + (b.effective_to   || '') + '"'
            +     ' data-description="'    + (b.description    || '') + '">'
            +     '<i class="ri-edit-box-line text-info" style="font-weight:bold;font-size:17px;"></i></a> '
            +   '<a href="#" class="retireBtn"'
            +     ' data-id="' + b.id + '" data-row="' + row + '"'
            +     ' data-description="' + (b.description || 'this bracket') + '">'
            +     '<i class="ri-archive-line text-warning" style="font-weight:bold;font-size:17px;"></i></a> '
            +   '<a href="#" class="deleteBtn"'
            +     ' data-id="' + b.id + '" data-row="' + row + '"'
            +     ' data-description="' + (b.description || 'this bracket') + '">'
            +     '<i class="ri-delete-bin-line text-danger" style="font-weight:bold;font-size:17px;"></i></a>'
            + '</td></tr>';
    }

    // ── EDIT — open ───────────────────────────────────────────────────────
    $('#activetbody').on('click', '.editBtn', function(e) {
        e.preventDefault();
        var d = $(this).data();
        $('#editId').val(d.id);
        $('#editRow').val(d.row);
        $('#editIncomeFrom').val(d.incomeFrom);
        $('#editIncomeTo').val(d.incomeTo || '');
        $('#editRate').val(d.rate);
        $('#editEffectiveFrom').val(d.effectiveFrom);
        $('#editEffectiveTo').val(d.effectiveTo || '');
        $('#editDescription').val(d.description || '');
        $('#editDataModal').modal('show');
    });

    // ── RETIRE — open ─────────────────────────────────────────────────────
    $('#activetbody').on('click', '.retireBtn', function(e) {
        e.preventDefault();
        var d = $(this).data();
        $('#retireId').val(d.id);
        $('#retireRow').val(d.row);
        $('#retireDescription').text(d.description);
        $('#retireEffectiveTo').val('');
        $('#retireModal').modal('show');
    });

    // ── DELETE — open ─────────────────────────────────────────────────────
    $('#activetbody').on('click', '.deleteBtn', function(e) {
        e.preventDefault();
        var d = $(this).data();
        $('#singleDeleteId').val(d.id);
        $('#singleDeleteRow').val(d.row);
        $('#singleDisplayDeleteLabel').text(d.description);
        $('#singleDeleteDataModal').modal('show');
    });

    // ── ADD — submit ──────────────────────────────────────────────────────
    $('#submitDataBtn').click(function(e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        $.ajax({
            type: 'POST',
            url:  '{{ route("tenant.admin.hr.paye.brackets.store", ["tenantName" => request()->route("tenantName")]) }}',
            data: $('#newDataForm').serialize(),
            timeout: 30000,
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    var row = 'row' + data.bracket.id;
                    table.row.add($(buildRow(data.bracket, row))).draw(false);
                    $('#newDataModal').modal('hide');
                    $('#newDataForm')[0].reset();
                    activeBrackets.push(data.bracket);
                    activeBrackets.sort(function(a, b) { return a.income_from - b.income_from; });
                } else if (data.status === 422) {
                    var msg = ''; $.each(data.errors, function(k, v) { msg += v + '<br>'; });
                    toastr.error(msg, 'Validation');
                } else { toastr.error(data.error || 'Failed.', 'Error'); }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var msg = ''; $.each(xhr.responseJSON.errors, function(k, v) { msg += v + '<br>'; });
                    toastr.error(msg, 'Validation');
                } else { toastr.error('Server error.', 'Error'); }
            }
        });
    });

    // ── EDIT — submit ─────────────────────────────────────────────────────
    $('#submitEditBtn').click(function(e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        var row  = $('#editRow').val();
        $.ajax({
            type: 'POST',
            url:  '{{ route("tenant.admin.hr.paye.brackets.update", ["tenantName" => request()->route("tenantName")]) }}',
            data: $('#editDataForm').serialize(),
            timeout: 30000,
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Success');
                    table.row('#' + row).remove();
                    table.row.add($(buildRow(data.bracket, row))).draw(false);
                    $('#editDataModal').modal('hide');
                } else if (data.status === 422) {
                    var msg = ''; $.each(data.errors, function(k, v) { msg += v + '<br>'; });
                    toastr.error(msg, 'Validation');
                } else { toastr.error(data.error || 'Failed.', 'Error'); }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var msg = ''; $.each(xhr.responseJSON.errors, function(k, v) { msg += v + '<br>'; });
                    toastr.error(msg, 'Validation');
                } else { toastr.error('Server error.', 'Error'); }
            }
        });
    });

    // ── RETIRE — submit ───────────────────────────────────────────────────
    $('#submitRetireBtn').click(function(e) {
        e.preventDefault();
        if (!$('#retireEffectiveTo').val()) {
            toastr.warning('Please set the effective to date.', 'Required');
            return;
        }
        var self = $(this); self.prop('disabled', true);
        var row  = $('#retireRow').val();
        $.ajax({
            type: 'POST',
            url:  '{{ route("tenant.admin.hr.paye.brackets.retire", ["tenantName" => request()->route("tenantName")]) }}',
            data: $('#retireForm').serialize(),
            timeout: 30000,
            beforeSend: function() { $('#progressBar').show(); },
            complete:   function() { $('#progressBar').hide(); self.prop('disabled', false); },
            success: function(data) {
                if (data.status === 201) {
                    toastr.success(data.success, 'Retired');
                    table.row('#' + row).remove().draw(false);
                    $('#retireModal').modal('hide');
                    toastr.info('Reload the page to see the bracket in the historical table.', 'Note');
                } else { toastr.error(data.error || 'Failed.', 'Error'); }
            },
            error: function() { toastr.error('Server error.', 'Error'); }
        });
    });

    // ── DELETE — submit ───────────────────────────────────────────────────
    $('#submitSingleDeleteDataBtn').click(function(e) {
        e.preventDefault();
        var self = $(this); self.prop('disabled', true);
        var row  = $('#singleDeleteRow').val();
        $.ajax({
            type: 'POST',
            url:  '{{ route("tenant.admin.hr.paye.brackets.delete", ["tenantName" => request()->route("tenantName")]) }}',
            data: { _token: '{{ csrf_token() }}', id: $('#singleDeleteId').val() },
            timeout: 30000,
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

    // ── CALCULATOR ────────────────────────────────────────────────────────
    $('#runTestBtn').click(function(e) {
        e.preventDefault();
        var gross = parseFloat($('#testGross').val()) || 0;
        if (gross <= 0) { toastr.warning('Enter a gross salary greater than zero.', 'Required'); return; }

        var tbody     = $('#testBreakdownBody').empty();
        var totalTax  = 0;
        var remaining = gross;

        activeBrackets.forEach(function(b, i) {
            if (remaining <= 0) return;

            var bandWidth = (b.income_to !== null && b.income_to !== '')
                ? (parseFloat(b.income_to) - parseFloat(b.income_from))
                : remaining;

            var taxable = Math.min(remaining, bandWidth);
            var tax     = taxable * (parseFloat(b.rate) / 100);
            totalTax   += tax;
            remaining  -= taxable;

            var incomeTo = (b.income_to !== null && b.income_to !== '')
                ? fmt(b.income_to) : 'No ceiling';

            tbody.append(
                '<tr>'
                + '<td>' + (i + 1) + '</td>'
                + '<td>' + fmt(b.income_from) + ' – ' + incomeTo + '</td>'
                + '<td>' + parseFloat(b.rate).toFixed(2) + '%</td>'
                + '<td>' + fmt(taxable) + '</td>'
                + '<td>' + fmt(tax) + '</td>'
                + '</tr>'
            );
        });

        $('#testTotalPaye').text('MWK ' + fmt(totalTax));
        $('#testNet').text('MWK ' + fmt(gross - totalTax));
        $('#testResult').show();
    });

    $('#testGross').on('keydown', function(e) {
        if (e.key === 'Enter') $('#runTestBtn').click();
    });

});
</script>
@endsection