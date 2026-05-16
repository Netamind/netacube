@extends('operations.retail.dashboard')
@section('content')

<style>
/* ── Progress bar ────────────────────────────────────────────────────────── */
#progressBar { height: 3px; display: none; transform: rotate(180deg); }

/* ── DataTable export buttons — baseproducts style ──────────────────────── */
.dt-buttons .btn {
    background: transparent !important; background-image: none !important;
    box-shadow: none !important; border-color: #5bc0de; color: #5bc0de;
}
.dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }

/* ── Card chrome ─────────────────────────────────────────────────────────── */
.card      { border: none; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border-radius: 12px; }
.card-body { padding: 0 !important; }

.card-header {
    padding: 0 !important;
    background: #4B5EBD;
    border-radius: 12px 12px 0 0 !important;
    border: none;
}
.ch-inner {
    display: flex; align-items: center;
    padding: 0 14px; height: 48px; gap: 8px;
    flex-wrap: nowrap;
}
.ch-left  { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; overflow: hidden; }
.ch-right { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }

.ch-title {
    color: #fff; font-size: 15px; font-weight: 600;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.ch-subtitle {
    color: rgba(255,255,255,0.7); font-size: 11px; font-weight: 500;
    white-space: nowrap;
}
.ch-sep { width: 1px; height: 20px; background: rgba(255,255,255,0.25); flex-shrink: 0; }

.ch-btn {
    width: 30px; height: 30px; border-radius: 7px;
    background: #fff; border: 1px solid rgba(255,255,255,0.6);
    color: #4B5EBD; display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 14px; transition: background .15s, box-shadow .15s;
    text-decoration: none; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}
.ch-btn:hover { background: #f0f2ff; color: #3a4ca0; box-shadow: 0 2px 6px rgba(0,0,0,0.15); }
.ch-btn.back  { background: rgba(255,255,255,0.2); border-color: rgba(255,255,255,0.4); color: #fff; }
.ch-btn.back:hover { background: rgba(255,255,255,0.35); color: #fff; }

/* ── Tab nav ─────────────────────────────────────────────────────────────── */
.tab-header-container { background: #f8f9fa; border-bottom: 1px solid #dee2e6; overflow-x: auto; }
.nav-pills { flex-wrap: nowrap; }
.nav-pills .nav-link {
    border-radius: 0 !important; padding: .5rem 1rem;
    font-weight: 500; font-size: 12px; color: #6c757d;
    border-bottom: 3px solid transparent; transition: all .2s; white-space: nowrap;
}
.nav-pills .nav-link:hover  { background: #e9ecef; color: #4B5EBD; }
.nav-pills .nav-link.active {
    background: transparent !important; color: #4B5EBD !important;
    border-bottom-color: #4B5EBD; font-weight: 600;
}

/* ── Selection / action bar ──────────────────────────────────────────────── */
.det-sel-bar {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 20px; background: #f8f9fc;
    border-bottom: 1px solid #eaecf5;
    flex-wrap: wrap;
}
.det-sel-count {
    font-size: 12px; font-weight: 600; color: #4B5EBD;
    background: #eff3ff; border: 1px solid #c5caec;
    border-radius: 20px; padding: 3px 10px;
    display: inline-flex; align-items: center; gap: 4px;
}
.det-sel-count.none { color: #94a3b8; background: #f1f5f9; border-color: #e2e8f0; }
.det-action-bar-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 6px; border: 1px solid;
    font-size: 12px; font-weight: 600; cursor: pointer;
    transition: all .15s; white-space: nowrap; line-height: 1;
    background: none;
}
.det-action-bar-btn:disabled { opacity: .4; pointer-events: none; }
.dab-submit      { color: #4B5EBD; border-color: #c5caec; background: #eff3ff; }
.dab-submit:hover      { background: #4B5EBD; color: #fff; border-color: #4B5EBD; }
.dab-submit-all  { color: #059669; border-color: #6ee7b7; background: #ecfdf5; }
.dab-submit-all:hover  { background: #059669; color: #fff; border-color: #059669; }
.dab-unsubmit { color: #d97706; border-color: #fde68a; background: #fffbeb; }
.dab-unsubmit:hover { background: #d97706; color: #fff; border-color: #d97706; }
.dab-delete  { color: #dc2626; border-color: #fecaca; background: #fef2f2; }
.dab-delete:hover  { background: #dc2626; color: #fff; border-color: #dc2626; }
.det-bar-sep { width: 1px; height: 20px; background: #e2e8f0; flex-shrink: 0; }

.det-bar-right { margin-left: auto; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.det-info-badge {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11px; font-weight: 600; padding: 3px 9px;
    border-radius: 20px; border: 1px solid; white-space: nowrap; line-height: 1.3;
}
.det-info-badge .badge-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; opacity: .75; margin-right: 1px; }
.dib-cost      { background: #f0f4ff; color: #3b4fa0; border-color: #c5caec; }
.dib-value     { background: #f0fdf4; color: #15803d; border-color: #bbf7d0; }
.dib-submitted { background: #dcfce7; color: #15803d; border-color: #86efac; }
.dib-pending   { background: #fef9c3; color: #854d0e; border-color: #fde68a; }

/* ── Table wrap — matches baseproducts card-body ──────────────────────────── */
.det-table-wrap { padding: 0 1.5rem 1.5rem 1.5rem !important; background: #fff; position: relative; }

#detLoadingOverlay {
    display: none; position: absolute; inset: 0;
    background: rgba(255,255,255,0.72); z-index: 10;
    align-items: center; justify-content: center;
    border-radius: 0 0 10px 10px;
}
#detLoadingOverlay .spinner-border { color: #4B5EBD; }

/* ── Table alignment — mirrors baseproducts exactly ──────────────────────── */
#detTable thead th,
table.dataTable thead th { text-align: center !important; vertical-align: middle !important; }
#detTable thead th:first-child,
table.dataTable thead th:first-child { text-align: left !important; }
#detTable tbody td,
table.dataTable tbody td { text-align: center !important; vertical-align: middle !important; }
#detTable tbody td:first-child,
table.dataTable tbody td:first-child { text-align: left !important; }

.det-row-check { width: 16px; height: 16px; cursor: pointer; accent-color: #4B5EBD; }

/* ── Status badges ───────────────────────────────────────────────────────── */
.status-badge {
    display: inline-flex; align-items: center; gap: 3px;
    font-size: 10px; font-weight: 600; padding: 2px 8px;
    border-radius: 5px; border: 1px solid; white-space: nowrap;
}
.status-submitted { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }
.status-pending   { background: #fef9c3; color: #854d0e; border-color: #fde68a; }

/* ── Row action buttons — same icon-link style as baseproducts ───────────── */
.det-act-btn {
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; transition: all .15s; background: none;
    font-size: 17px; text-decoration: none; font-weight: bold;
}
.det-act-submit   { color: #4B5EBD; }
.det-act-submit:hover   { color: #2d3a8c; }
.det-act-unsubmit { color: #d97706; }
.det-act-unsubmit:hover { color: #92400e; }
.det-act-delete   { color: #dc2626; }
.det-act-delete:hover   { color: #7f1d1d; }
.det-act-edit     { color: #0d6efd; }
.det-act-edit:hover     { color: #0a4eb3; }

/* ── Price cell ──────────────────────────────────────────────────────────── */
.price-cell { font-size: 12px; font-weight: 600; color: #198754; }

/* ── Modal headers ───────────────────────────────────────────────────────── */
.mh-blue   { background: linear-gradient(135deg,#4B5EBD,#576CC0); padding: 12px 18px !important; border-bottom: none; border-radius: 8px 8px 0 0; }
.mh-green  { background: linear-gradient(135deg,#059669,#10b981); padding: 12px 18px !important; border-bottom: none; border-radius: 8px 8px 0 0; }
.mh-amber  { background: linear-gradient(135deg,#d97706,#f59e0b); padding: 12px 18px !important; border-bottom: none; border-radius: 8px 8px 0 0; }
.mh-danger { background: linear-gradient(135deg,#dc2626,#ef4444); padding: 12px 18px !important; border-bottom: none; border-radius: 8px 8px 0 0; }
.mh-title  { color: #fff; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.mh-close  { filter: brightness(0) invert(1); opacity: .8; }
.mh-close:hover { opacity: 1; }
.modal-content { border: none; border-radius: 10px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.18); }

/* ── Edit modal fields ───────────────────────────────────────────────────── */
.edit-field-group { margin-bottom: 14px; }
.edit-field-group label {
    display: block; font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .4px; color: #64748b; margin-bottom: 5px;
}
.edit-field-group .form-control {
    font-size: 13px; border-radius: 7px; border: 1px solid #dde1f0;
    padding: 7px 11px; transition: border-color .15s, box-shadow .15s;
}
.edit-field-group .form-control:focus {
    border-color: #4B5EBD; box-shadow: 0 0 0 3px rgba(75,94,189,.12); outline: none;
}
.edit-field-group .form-control:disabled { background: #f8f9fc; color: #94a3b8; cursor: default; }
.edit-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.edit-product-banner {
    background: #f4f6ff; border: 1px solid #e4e7f5; border-radius: 8px;
    padding: 10px 14px; margin-bottom: 16px;
    display: flex; align-items: center; gap: 10px;
}
.edit-product-banner i { color: #4B5EBD; font-size: 18px; flex-shrink: 0; }
.edit-product-name { font-size: 13px; font-weight: 700; color: #1e293b; }
.edit-product-meta { font-size: 11px; color: #64748b; margin-top: 1px; }

@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

@media (max-width: 767px) {
    .det-table-wrap { padding: 0 12px 28px !important; }
    .det-bar-right  { margin-left: 0; width: 100%; }
    .edit-grid      { grid-template-columns: 1fr; }
}
</style>

<div class="progress" id="progressBar" role="progressbar">
    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

{{-- ── Card header ──────────────────────────────────────────────────────── --}}
<div class="card-header">
    <div class="ch-inner">
        <div class="ch-left">
            <a href="{{ route('retail.operations.deliverynotes') }}" class="ch-btn back" title="Back to Delivery Notes">
                <i class="ri-arrow-left-line"></i>
            </a>
            <div class="ch-sep"></div>
            <div style="min-width:0;">
                <div class="ch-title">{{ $branch->name }}</div>
                <div class="ch-subtitle">Delivery Note [ {{ $displayDate }} ]</div>
            </div>
        </div>
        <div class="ch-right">
            <a href="#" class="ch-btn" id="downloadBtn" title="Download table">
                <i class="ri-download-line"></i>
            </a>
            <a href="#" class="ch-btn" id="pdfBtn" title="Download PDF for this branch">
                <i class="ri-file-pdf-2-line"></i>
            </a>
            <a href="#" class="ch-btn" id="refreshBtn" title="Refresh">
                <i class="ri-refresh-line"></i>
            </a>
            <a href="#" class="ch-btn" id="infoBtn" title="Help">
                <i class="ri-information-line"></i>
            </a>
        </div>
    </div>
</div>

{{-- ── Tab nav (kept for visual consistency) ───────────────────────────── --}}
<div class="tab-header-container">
    <ul class="nav nav-pills mb-0"></ul>
</div>

{{-- ── Selection / action bar ──────────────────────────────────────────── --}}
<div class="det-sel-bar" id="detSelBar">
    <span class="det-sel-count none" id="detSelCount">
        <i class="ri-checkbox-blank-line" style="font-size:13px;"></i> 0 selected
    </span>
    <div class="det-bar-sep"></div>
    <button type="button" class="det-action-bar-btn dab-submit"     id="barSubmitBtn"    disabled><i class="ri-corner-up-right-line"></i> Submit</button>
    <button type="button" class="det-action-bar-btn dab-unsubmit"   id="barUnsubmitBtn"  disabled><i class="ri-arrow-go-back-line"></i>   Unsubmit</button>
    <button type="button" class="det-action-bar-btn dab-delete"     id="barDeleteBtn"    disabled><i class="ri-delete-bin-5-line"></i>    Delete</button>
    <div class="det-bar-sep"></div>
    <button type="button" class="det-action-bar-btn dab-submit-all" id="barSubmitAllBtn" disabled>
        <i class="ri-send-plane-line"></i> Submit All
    </button>

    <div class="det-bar-right">
        <span class="det-info-badge dib-cost">
            <span class="badge-label">Cost</span>
            <span id="badgeTotalCost">—</span>
        </span>
        <span class="det-info-badge dib-value">
            <span class="badge-label">Value</span>
            <span id="badgeTotalValue">—</span>
        </span>
        <div class="det-bar-sep"></div>
        <span class="det-info-badge dib-submitted">
            <i class="ri-check-line" style="font-size:11px;"></i>
            <span class="badge-label">Submitted</span>
            <span id="badgeSubmitted">—</span>
        </span>
        <span class="det-info-badge dib-pending">
            <i class="ri-time-line" style="font-size:11px;"></i>
            <span class="badge-label">Pending</span>
            <span id="badgePending">—</span>
        </span>
    </div>
</div>

{{-- ── Table ────────────────────────────────────────────────────────────── --}}
<div class="det-table-wrap">
    <div id="detLoadingOverlay">
        <div class="spinner-border" role="status" style="width:2.5rem;height:2.5rem;">
            <span class="visually-hidden">Loading…</span>
        </div>
    </div>

    <table id="detTable" class="table table-sm table-striped row-border order-column w-100">
        <thead style="background-color:#e2e2e9;">
            <tr>
                <th>
                    <input type="checkbox" id="selectAllDet" class="det-row-check" title="Select all">
                    &nbsp;&nbsp;Product
                </th>
                <th>Code</th>
                <th>Unit</th>
                <th>Qty</th>
                <th>Cost Price</th>
                <th>Selling Price</th>
                <th>Cost Value</th>
                <th>Sell Value</th>
                <th>Status</th>
                <th>Submitted By</th>
                <th>Submitted At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody id="detTableBody">
            <tr>
                <td colspan="12" style="text-align:center;padding:40px 16px;color:#94a3b8;font-size:13px;">
                    <i class="ri-loader-4-line" style="font-size:24px;display:block;margin-bottom:8px;animation:spin 1s linear infinite;"></i>
                    Loading delivery note lines…
                </td>
            </tr>
        </tbody>
    </table>
</div>

</div>
</div></div></div>


{{-- ══ DOWNLOAD MODAL ─────────────────────────────────────────────────────── --}}
<div class="modal fade" id="downloadModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header mh-blue">
            <h5 class="modal-title mh-title"><i class="ri-download-line"></i> Download Table</h5>
            <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <p class="mb-2" style="font-size:13px;">Click a button to export the delivery note lines table.</p>
            <div class="buttons"></div>
        </div>
    </div></div>
</div>


{{-- ══ CONFIRM MODAL ──────────────────────────────────────────────────────── --}}
<div class="modal fade" id="detConfirmModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header" id="detConfirmHeader" style="padding:12px 18px !important;border-bottom:none;border-radius:8px 8px 0 0;">
                <h5 class="modal-title mh-title" id="detConfirmTitle"></h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 22px;">
                <div style="display:flex;align-items:flex-start;gap:14px;">
                    <div style="width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;" id="detConfirmIconWrap">
                        <i id="detConfirmIcon" style="font-size:20px;"></i>
                    </div>
                    <div>
                        <p style="font-size:13px;font-weight:600;color:#1e293b;margin:0 0 6px;" id="detConfirmHeading"></p>
                        <p style="font-size:12px;color:#64748b;margin:0;" id="detConfirmBody"></p>
                    </div>
                </div>
                <div style="border-radius:0 5px 5px 0;padding:8px 12px;font-size:11px;margin-top:14px;border-left:3px solid;" id="detConfirmNote"></div>
            </div>
            <div class="modal-footer" style="padding:10px 20px 14px;gap:8px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm" id="detConfirmExecuteBtn"></button>
            </div>
        </div>
    </div>
</div>


{{-- ══ EDIT LINE MODAL ────────────────────────────────────────────────────── --}}
<div class="modal fade" id="editLineModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:480px;">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-edit-box-line"></i> Update Delivery Note Line</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 22px;">

                <div class="edit-product-banner">
                    <i class="ri-box-3-line"></i>
                    <div>
                        <div class="edit-product-name" id="editProductName">—</div>
                        <div class="edit-product-meta" id="editProductMeta">—</div>
                    </div>
                </div>

                <input type="hidden" id="editNoteId">
                <input type="hidden" id="editRowId">

                <div class="edit-grid">
                    <div class="edit-field-group">
                        <label>Quantity</label>
                        <input type="number" class="form-control" id="editQty" min="0" step="0.01" placeholder="0">
                    </div>
                    <div class="edit-field-group">
                        <label>Unit</label>
                        <input type="text" class="form-control" id="editUnit" disabled>
                    </div>
                    <div class="edit-field-group">
                        <label>Cost Price</label>
                        <input type="number" class="form-control" id="editCostPrice" min="0" step="0.01" placeholder="0.00">
                    </div>
                    <div class="edit-field-group">
                        <label>Selling Price</label>
                        <input type="number" class="form-control" id="editSellingPrice" min="0" step="0.01" placeholder="0.00">
                    </div>
                </div>

                <div style="background:#f4f6ff;border:1px solid #e4e7f5;border-radius:8px;padding:10px 14px;margin-top:4px;">
                    <div style="font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#94a3b8;margin-bottom:6px;">Live Preview</div>
                    <div style="display:flex;gap:16px;flex-wrap:wrap;">
                        <div>
                            <div style="font-size:9px;color:#94a3b8;font-weight:600;text-transform:uppercase;">Cost Value</div>
                            <div style="font-size:14px;font-weight:800;color:#3b4fa0;" id="editPreviewCost">—</div>
                        </div>
                        <div>
                            <div style="font-size:9px;color:#94a3b8;font-weight:600;text-transform:uppercase;">Sell Value</div>
                            <div style="font-size:14px;font-weight:800;color:#059669;" id="editPreviewSell">—</div>
                        </div>
                    </div>
                </div>

            </div>
            <div class="modal-footer" style="padding:10px 20px 14px;gap:8px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="editSaveBtn" class="btn btn-primary btn-sm">
                    <i class="ri-check-line me-1"></i> Update
                </button>
            </div>
        </div>
    </div>
</div>


{{-- ══ INFO MODAL ─────────────────────────────────────────────────────────── --}}
<div class="modal fade" id="detInfoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Note Details</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <table style="width:100%;font-size:13px;border-collapse:collapse;">
                    <tbody>
                        @foreach([
                            ['Line items',     'Each row represents one product line in the delivery note for this branch on the selected date.'],
                            ['Checkboxes',     'Select one or more lines to bulk Submit, Unsubmit, or Delete via the action bar.'],
                            ['Summary badges', 'The action bar shows live totals — Cost, Value, Submitted count, and Pending count.'],
                            ['Submit All',     'Submits every pending line at once — active only when at least one pending line exists.'],
                            ['Submit',         'Marks selected pending lines as submitted and increments branch stock.'],
                            ['Unsubmit',       'Reverses submission — sets lines back to pending and decrements stock.'],
                            ['Delete',         'Permanently deletes selected lines. Stock is NOT reversed for submitted lines.'],
                            ['Edit (✎)',       'Opens an edit modal to adjust quantity, cost price, and selling price for that line.'],
                            ['PDF',            'Downloads a PDF of all delivery notes for this branch on this date.'],
                            ['Download',       'Exports the table to Excel, CSV, or PDF.'],
                        ] as [$k,$v])
                        <tr>
                            <td style="padding:7px 12px;font-weight:700;color:#475569;width:160px;border-bottom:1px solid #f1f5f9;white-space:nowrap;">{{ $k }}</td>
                            <td style="padding:7px 12px;border-bottom:1px solid #f1f5f9;">{{ $v }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function () {

    /* ── CSRF ─────────────────────────────────────────────────────────── */
    var _token = '{{ csrf_token() }}';
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': _token } });

    toastr.options = {
        timeOut: 5000, progressBar: true,
        positionClass: 'toast-top-end', closeButton: true
    };

    /* ── Page constants ───────────────────────────────────────────────── */
    var branchId   = {{ $branch->id }};
    var branchName = '{{ addslashes($branch->name) }}';
    var activeDate = '{{ $deliveryDate }}';
    var pdfUrl     = '{{ route("retail.operations.deliverynotes.branch.export-pdf") }}?branch_id={{ $branch->id }}&date={{ $deliveryDate }}';

    var dtTable  = null;
    var hasPending = false;

    /* ── Utilities ────────────────────────────────────────────────────── */
    function fmt(n, d) {
        d = (d === undefined) ? 2 : d;
        if (n === null || n === undefined || n === '') return '—';
        var v = parseFloat(n);
        return isNaN(v) ? '—' : v.toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d });
    }

    function esc(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function showProgress() { $('#progressBar').show(); }
    function hideProgress() { $('#progressBar').hide(); }

    function handleAjaxError(xhr) {
        var json = null;
        try { json = xhr.responseJSON || JSON.parse(xhr.responseText); } catch(e) {}
        if (xhr.status === 419) { toastr.error('Session expired. The page will refresh.'); setTimeout(function(){ location.reload(); }, 2000); return; }
        if (xhr.status === 422 && json && json.errors) { toastr.error(Object.values(json.errors).flat().join(' '), 'Validation error'); return; }
        toastr.error((json && (json.message || json.error)) || 'Unexpected error (HTTP ' + xhr.status + ').', 'Error');
    }

    /* ══════════════════════════════════════════════════════════════════
       BUILD ROW HTML — icon links matching baseproducts action column
    ══════════════════════════════════════════════════════════════════ */
    function buildRow(l) {
        var rowId   = 'row' + l.id;
        var costVal = parseFloat(l.quantity || 0) * parseFloat(l.cost_price    || 0);
        var sellVal = parseFloat(l.quantity || 0) * parseFloat(l.selling_price || 0);

        var statusBadge = l.submitted
            ? '<span class="status-badge status-submitted"><i class="ri-check-line"></i> Submitted</span>'
            : '<span class="status-badge status-pending"><i class="ri-time-line"></i> Pending</span>';

        /* Submit / Unsubmit toggle — icon link style like baseproducts */
        var toggleBtn = !l.submitted
            ? '<a href="#" class="det-act-btn det-act-submit det-single-submit"'
              + ' data-note-id="' + l.id + '" data-row-id="' + rowId + '"'
              + ' data-product="' + esc(l.product_name) + '"'
              + ' title="Submit"><i class="ri-corner-up-right-line"></i></a>'
            : '<a href="#" class="det-act-btn det-act-unsubmit det-single-unsubmit"'
              + ' data-note-id="' + l.id + '" data-row-id="' + rowId + '"'
              + ' data-product="' + esc(l.product_name) + '"'
              + ' title="Unsubmit"><i class="ri-arrow-go-back-line"></i></a>';

        var editBtn = '<a href="#" class="det-act-btn det-act-edit det-single-edit"'
            + ' data-note-id="'  + l.id             + '"'
            + ' data-row-id="'   + rowId            + '"'
            + ' data-product="'  + esc(l.product_name)  + '"'
            + ' data-code="'     + esc(l.product_code)  + '"'
            + ' data-unit="'     + esc(l.product_unit)  + '"'
            + ' data-qty="'      + l.quantity        + '"'
            + ' data-cost="'     + l.cost_price      + '"'
            + ' data-sell="'     + l.selling_price   + '"'
            + ' title="Edit"><i class="ri-edit-box-line"></i></a>';

        var deleteBtn = '<a href="#" class="det-act-btn det-act-delete det-single-delete"'
            + ' data-note-id="' + l.id + '" data-row-id="' + rowId + '"'
            + ' data-product="' + esc(l.product_name) + '"'
            + ' title="Delete"><i class="ri-delete-bin-line"></i></a>';

        return '<tr id="' + rowId + '">'
            + '<td>'
            +   '<input type="checkbox" class="det-row-check" value="' + l.id + '" style="margin-right:8px;vertical-align:middle;">'
            +   '<strong>' + esc(l.product_name) + '</strong>'
            + '</td>'
            + '<td>' + (l.product_code || '—') + '</td>'
            + '<td>' + (l.product_unit || '—') + '</td>'
            + '<td style="font-weight:700;">' + fmt(l.quantity, 0) + '</td>'
            + '<td>' + fmt(l.cost_price) + '</td>'
            + '<td><span class="price-cell">' + fmt(l.selling_price) + '</span></td>'
            + '<td style="color:#475569;">' + fmt(costVal) + '</td>'
            + '<td style="color:#059669;font-weight:600;">' + fmt(sellVal) + '</td>'
            + '<td>' + statusBadge + '</td>'
            + '<td style="font-size:11px;color:#64748b;">' + esc(l.submitted_by_name || '—') + '</td>'
            + '<td style="font-size:11px;color:#64748b;">' + (l.submitted_at || '—') + '</td>'
            + '<td>'
            +   toggleBtn + ' ' + editBtn + ' ' + deleteBtn
            + '</td>'
            + '</tr>';
    }

    /* ══════════════════════════════════════════════════════════════════
       SELECTION UI
    ══════════════════════════════════════════════════════════════════ */
    function getSelectedNoteIds() {
        var ids = [];
        $('.det-row-check:checked').not('#selectAllDet').each(function () { ids.push($(this).val()); });
        return ids;
    }

    function updateSelectionUI() {
        var count = $('.det-row-check:checked').not('#selectAllDet').length;
        if (count > 0) {
            $('#detSelCount').removeClass('none')
                .html('<i class="ri-checkbox-multiple-line" style="font-size:13px;"></i> ' + count + ' selected');
            $('#barSubmitBtn, #barUnsubmitBtn, #barDeleteBtn').prop('disabled', false);
        } else {
            $('#detSelCount').addClass('none')
                .html('<i class="ri-checkbox-blank-line" style="font-size:13px;"></i> 0 selected');
            $('#barSubmitBtn, #barUnsubmitBtn, #barDeleteBtn').prop('disabled', true);
            $('#selectAllDet').prop('checked', false);
        }
        $('#barSubmitAllBtn').prop('disabled', !hasPending);
    }

    $(document).on('click', '#selectAllDet', function () {
        $('.det-row-check').not('#selectAllDet').prop('checked', this.checked);
        updateSelectionUI();
    });
    $(document).on('change', '.det-row-check', function () {
        if (!this.checked) $('#selectAllDet').prop('checked', false);
        updateSelectionUI();
    });

    /* ══════════════════════════════════════════════════════════════════
       RECOMPUTE SUMMARY BADGES FROM DOM
    ══════════════════════════════════════════════════════════════════ */
    function recomputeBadges() {
        var totalCost = 0, totalValue = 0, submitted = 0, pending = 0;
        $('#detTableBody tr').each(function () {
            var $tds = $(this).find('td');
            if ($tds.length < 9) return;
            var qty  = parseFloat($tds.eq(3).text().replace(/,/g,'')) || 0;
            var cost = parseFloat($tds.eq(4).text().replace(/,/g,'')) || 0;
            var sell = parseFloat($tds.eq(5).text().replace(/,/g,'')) || 0;
            totalCost  += qty * cost;
            totalValue += qty * sell;
            if ($tds.eq(8).find('.status-submitted').length) submitted++;
            else pending++;
        });
        hasPending = pending > 0;
        $('#badgeTotalCost').text(fmt(totalCost));
        $('#badgeTotalValue').text(fmt(totalValue));
        $('#badgeSubmitted').text(submitted);
        $('#badgePending').text(pending);
        $('#barSubmitAllBtn').prop('disabled', !hasPending);
    }

    /* ══════════════════════════════════════════════════════════════════
       LOAD TABLE (full reload)
    ══════════════════════════════════════════════════════════════════ */
    function loadTable() {
        showProgress();
        $('#detLoadingOverlay').css('display','flex');
        $('#selectAllDet').prop('checked', false);
        hasPending = false;
        updateSelectionUI();

        $.ajax({
            type: 'GET',
            url:  '{{ route("retail.operations.deliverynotes.branch.lines") }}',
            data: { branch_id: branchId, delivery_date: activeDate },
            complete: function () { hideProgress(); $('#detLoadingOverlay').hide(); },
            success: function (data) {
                if (data.status !== 200) { toastr.error('Failed to load data.'); return; }

                var lines = data.lines;
                var totalCost = 0, totalValue = 0, sub = 0, pend = 0;
                lines.forEach(function (l) {
                    totalCost  += parseFloat(l.quantity||0) * parseFloat(l.cost_price||0);
                    totalValue += parseFloat(l.quantity||0) * parseFloat(l.selling_price||0);
                    if (l.submitted) sub++; else pend++;
                });
                hasPending = pend > 0;
                $('#badgeTotalCost').text(fmt(totalCost));
                $('#badgeTotalValue').text(fmt(totalValue));
                $('#badgeSubmitted').text(sub);
                $('#badgePending').text(pend);

                /* Destroy old DataTable before touching DOM */
                if (dtTable && $.fn.DataTable.isDataTable('#detTable')) {
                    dtTable.destroy();
                    dtTable = null;
                }

                var html = '';
                if (!lines.length) {
                    html = '<tr><td colspan="12" style="text-align:center;padding:48px 16px;color:#94a3b8;font-size:13px;">'
                         + '<i class="ri-inbox-2-line" style="font-size:36px;display:block;margin-bottom:10px;color:#dde1f0;"></i>'
                         + 'No delivery note lines found for this branch on ' + activeDate + '.'
                         + '</td></tr>';
                } else {
                    lines.forEach(function (l) { html += buildRow(l); });
                }
                $('#detTableBody').html(html);

                /* Initialise DataTable — fixedColumns + same dom as baseproducts */
                dtTable = $('#detTable').DataTable({
                    dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>Brt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
                    lengthChange: true,
                    lengthMenu:   [[25,50,100,-1],[25,50,100,'All']],
                    pageLength:   50,
                    fixedColumns: { leftColumns: 1 },
                    scrollX:      true,
                    order:        [[0,'asc']],
                    columnDefs: [
                        { targets: '_all', className: 'text-center' },
                        { targets: 0,      className: 'text-start'  },
                        { orderable: false, targets: [11] },
                    ],
                    buttons: [
                        { extend:'excelHtml5', title: branchName+' – Delivery Notes – '+activeDate, exportOptions:{ columns:':visible:not(:last-child)' } },
                        { extend:'csvHtml5',   title: branchName+' – Delivery Notes – '+activeDate, exportOptions:{ columns:':visible:not(:last-child)' } },
                        { extend:'pdfHtml5',   title: branchName+' – Delivery Notes – '+activeDate,
                          exportOptions:{ columns:':visible:not(:last-child)' },
                          customize: function(doc) {
                            doc.content[1].table.widths = Array(doc.content[1].table.body[0].length+1).join('*').split('');
                          }
                        },
                    ],
                    language: { search:'', searchPlaceholder:'Search products…', emptyTable:'No delivery note lines found.' },
                });

                $('#downloadModal .buttons').empty();
                dtTable.buttons().container().appendTo($('#downloadModal .buttons'));
                updateSelectionUI();
            },
            error: handleAjaxError,
        });
    }

    /* ══════════════════════════════════════════════════════════════════
       UPDATE ONE ROW (baseproducts pattern: remove → add → draw)
    ══════════════════════════════════════════════════════════════════ */
    function updateRowInTable(line) {
        var rowId = 'row' + line.id;
        if (!dtTable) { loadTable(); return; }
        var dtRow = dtTable.row('#' + rowId);
        if (dtRow.length) { dtRow.remove(); }
        dtTable.row.add($(buildRow(line))).draw(false);
        recomputeBadges();
        updateSelectionUI();
    }

    /* ══════════════════════════════════════════════════════════════════
       CONFIRM MODAL HELPER
    ══════════════════════════════════════════════════════════════════ */
    function openConfirm(cfg, onExecute) {
        $('#detConfirmHeader').attr('class','modal-header ' + cfg.headerClass);
        $('#detConfirmTitle').html(cfg.title);
        $('#detConfirmIconWrap').css('background', cfg.wrapBg);
        $('#detConfirmIcon').attr('class', cfg.iconClass).css('color', cfg.iconColor);
        $('#detConfirmHeading').text(cfg.heading);
        $('#detConfirmBody').html(cfg.body);
        $('#detConfirmNote')
            .attr('style','border-radius:0 5px 5px 0;padding:8px 12px;font-size:11px;margin-top:14px;border-left:3px solid;' + cfg.noteStyle)
            .html(cfg.noteText);
        $('#detConfirmExecuteBtn')
            .attr('class','btn btn-sm ' + cfg.btnClass)
            .html(cfg.btnText)
            .off('click').on('click', function () {
                $('#detConfirmModal').modal('hide');
                onExecute();
            });
        $('#detConfirmModal').modal('show');
    }

    /* ══════════════════════════════════════════════════════════════════
       SUBMIT ALL
    ══════════════════════════════════════════════════════════════════ */
    $('#barSubmitAllBtn').on('click', function () {
        if (!hasPending) return;
        openConfirm({
            headerClass:'mh-green', iconClass:'ri-send-plane-line', iconColor:'#059669', wrapBg:'#ecfdf5',
            title:     '<i class="ri-send-plane-line"></i> Submit All Pending Lines',
            heading:   'Submit all pending lines for ' + branchName + '?',
            body:      'Every pending line for <strong>' + branchName + '</strong> on <strong>' + activeDate + '</strong> will be submitted and stock updated.',
            noteStyle: 'background:#ecfdf5;color:#065f46;border-color:#059669;',
            noteText:  '<i class="ri-information-line me-1"></i> Stock will be incremented for all pending lines.',
            btnClass:  'btn-success', btnText: '<i class="ri-send-plane-line me-1"></i> Yes, Submit All',
        }, function () {
            showProgress();
            $.ajax({
                type: 'POST',
                url:  '{{ route("retail.operations.deliverynotes.branch.submit-pending") }}',
                data: { branch_id: branchId, delivery_date: activeDate, _token: _token },
                complete: hideProgress,
                success: function (data) {
                    if (data.success) { toastr.success(data.success); loadTable(); }
                    else if (data.info) { toastr.info(data.info); }
                },
                error: handleAjaxError,
            });
        });
    });

    /* ══════════════════════════════════════════════════════════════════
       BULK ACTIONS
    ══════════════════════════════════════════════════════════════════ */
    $('#barSubmitBtn').on('click', function () {
        var ids = getSelectedNoteIds(); if (!ids.length) return;
        openConfirm({
            headerClass:'mh-blue', iconClass:'ri-corner-up-right-line', iconColor:'#4B5EBD', wrapBg:'#eff3ff',
            title:     '<i class="ri-corner-up-right-line"></i> Submit Lines',
            heading:   'Submit ' + ids.length + ' selected line' + (ids.length>1?'s':'') + '?',
            body:      'Selected pending lines for <strong>' + branchName + '</strong> on <strong>' + activeDate + '</strong> will be submitted and stock updated.',
            noteStyle: 'background:#eff3ff;color:#3b4fa0;border-color:#4B5EBD;',
            noteText:  '<i class="ri-information-line me-1"></i> Stock will be incremented.',
            btnClass:  'btn-primary', btnText: '<i class="ri-corner-up-right-line me-1"></i> Yes, Submit',
        }, function () { executeBulk('submit', ids); });
    });

    $('#barUnsubmitBtn').on('click', function () {
        var ids = getSelectedNoteIds(); if (!ids.length) return;
        openConfirm({
            headerClass:'mh-amber', iconClass:'ri-arrow-go-back-line', iconColor:'#d97706', wrapBg:'#fff8e1',
            title:     '<i class="ri-arrow-go-back-line"></i> Unsubmit Lines',
            heading:   'Unsubmit ' + ids.length + ' selected line' + (ids.length>1?'s':'') + '?',
            body:      'Selected submitted lines for <strong>' + branchName + '</strong> will revert to pending and stock will be decremented.',
            noteStyle: 'background:#fff8e1;color:#92400e;border-color:#f59e0b;',
            noteText:  '<i class="ri-alert-line me-1"></i> Stock will be reversed.',
            btnClass:  'btn-warning text-white', btnText: '<i class="ri-arrow-go-back-line me-1"></i> Yes, Unsubmit',
        }, function () { executeBulk('unsubmit', ids); });
    });

    $('#barDeleteBtn').on('click', function () {
        var ids = getSelectedNoteIds(); if (!ids.length) return;
        openConfirm({
            headerClass:'mh-danger', iconClass:'ri-delete-bin-5-line', iconColor:'#dc2626', wrapBg:'#fef2f2',
            title:     '<i class="ri-delete-bin-5-line"></i> Delete Lines',
            heading:   'Delete ' + ids.length + ' selected line' + (ids.length>1?'s':'') + '?',
            body:      'These delivery note lines will be permanently deleted.',
            noteStyle: 'background:#fef2f2;color:#7f1d1d;border-color:#dc2626;',
            noteText:  '<i class="ri-alert-line me-1"></i> Irreversible. Stock is NOT reversed for submitted lines.',
            btnClass:  'btn-danger', btnText: '<i class="ri-delete-bin-5-line me-1"></i> Yes, Delete',
        }, function () { executeBulk('delete', ids); });
    });

    function executeBulk(action, ids) {
        var urlMap = {
            submit:   '{{ route("retail.operations.deliverynotes.lines.bulk.submit") }}',
            unsubmit: '{{ route("retail.operations.deliverynotes.lines.bulk.unsubmit") }}',
            delete:   '{{ route("retail.operations.deliverynotes.lines.bulk.delete") }}',
        };
        showProgress();
        var postData = { branch_id: branchId, delivery_date: activeDate, _token: _token };
        $.each(ids, function (i, id) { postData['note_ids['+i+']'] = id; });

        $.ajax({
            type: 'POST', url: urlMap[action], data: postData,
            complete: hideProgress,
            success: function (data) {
                if (data.success) { toastr.success(data.success); loadTable(); }
                else if (data.info) { toastr.info(data.info); }
            },
            error: handleAjaxError,
        });
    }

    /* ══════════════════════════════════════════════════════════════════
       SINGLE ROW: SUBMIT
    ══════════════════════════════════════════════════════════════════ */
    $(document).on('click', '.det-single-submit', function (e) {
        e.preventDefault();
        var noteId  = $(this).data('note-id');
        var product = $(this).data('product');
        openConfirm({
            headerClass:'mh-blue', iconClass:'ri-corner-up-right-line', iconColor:'#4B5EBD', wrapBg:'#eff3ff',
            title:     '<i class="ri-corner-up-right-line"></i> Submit Line',
            heading:   'Submit this delivery note line?',
            body:      '<strong>' + esc(product) + '</strong> will be submitted and branch stock updated.',
            noteStyle: 'background:#eff3ff;color:#3b4fa0;border-color:#4B5EBD;',
            noteText:  '<i class="ri-information-line me-1"></i> Stock will be incremented.',
            btnClass:  'btn-primary', btnText: '<i class="ri-corner-up-right-line me-1"></i> Yes, Submit',
        }, function () { executeSingle('submit', noteId); });
    });

    /* ══════════════════════════════════════════════════════════════════
       SINGLE ROW: UNSUBMIT
    ══════════════════════════════════════════════════════════════════ */
    $(document).on('click', '.det-single-unsubmit', function (e) {
        e.preventDefault();
        var noteId  = $(this).data('note-id');
        var product = $(this).data('product');
        openConfirm({
            headerClass:'mh-amber', iconClass:'ri-arrow-go-back-line', iconColor:'#d97706', wrapBg:'#fff8e1',
            title:     '<i class="ri-arrow-go-back-line"></i> Unsubmit Line',
            heading:   'Unsubmit this delivery note line?',
            body:      '<strong>' + esc(product) + '</strong> will revert to pending and branch stock will be decremented.',
            noteStyle: 'background:#fff8e1;color:#92400e;border-color:#f59e0b;',
            noteText:  '<i class="ri-alert-line me-1"></i> Stock will be reversed.',
            btnClass:  'btn-warning text-white', btnText: '<i class="ri-arrow-go-back-line me-1"></i> Yes, Unsubmit',
        }, function () { executeSingle('unsubmit', noteId); });
    });

    /* ══════════════════════════════════════════════════════════════════
       SINGLE ROW: DELETE (remove row directly — no loadTable needed)
    ══════════════════════════════════════════════════════════════════ */
    $(document).on('click', '.det-single-delete', function (e) {
        e.preventDefault();
        var noteId  = $(this).data('note-id');
        var rowId   = $(this).data('row-id');
        var product = $(this).data('product');
        openConfirm({
            headerClass:'mh-danger', iconClass:'ri-delete-bin-5-line', iconColor:'#dc2626', wrapBg:'#fef2f2',
            title:     '<i class="ri-delete-bin-5-line"></i> Delete Line',
            heading:   'Delete this delivery note line?',
            body:      '<strong>' + esc(product) + '</strong> will be permanently deleted.',
            noteStyle: 'background:#fef2f2;color:#7f1d1d;border-color:#dc2626;',
            noteText:  '<i class="ri-alert-line me-1"></i> Irreversible. Stock is NOT reversed.',
            btnClass:  'btn-danger', btnText: '<i class="ri-delete-bin-5-line me-1"></i> Yes, Delete',
        }, function () {
            showProgress();
            $.ajax({
                type: 'POST',
                url:  '{{ route("retail.operations.deliverynotes.line.delete") }}',
                data: { _token: _token, note_id: noteId, branch_id: branchId },
                complete: hideProgress,
                success: function (data) {
                    if (data.success) {
                        toastr.success(data.success);
                        dtTable.row('#' + rowId).remove().draw(false);
                        recomputeBadges();
                        updateSelectionUI();
                    } else if (data.info) {
                        toastr.info(data.info);
                    }
                },
                error: handleAjaxError,
            });
        });
    });

    function executeSingle(action, noteId) {
        var urlMap = {
            submit:   '{{ route("retail.operations.deliverynotes.line.submit") }}',
            unsubmit: '{{ route("retail.operations.deliverynotes.line.unsubmit") }}',
        };
        showProgress();
        $.ajax({
            type: 'POST', url: urlMap[action],
            data: { _token: _token, note_id: noteId, branch_id: branchId, delivery_date: activeDate },
            complete: hideProgress,
            success: function (data) {
                if (data.success) { toastr.success(data.success); loadTable(); }
                else if (data.info) { toastr.info(data.info); }
            },
            error: handleAjaxError,
        });
    }

    /* ══════════════════════════════════════════════════════════════════
       EDIT LINE — open modal
    ══════════════════════════════════════════════════════════════════ */
    $(document).on('click', '.det-single-edit', function (e) {
        e.preventDefault();
        var $a = $(this);
        $('#editNoteId').val($a.data('note-id'));
        $('#editRowId').val($a.data('row-id'));
        $('#editProductName').text($a.data('product'));
        $('#editProductMeta').text('Code: ' + ($a.data('code') || '—') + '  ·  Unit: ' + ($a.data('unit') || '—'));
        $('#editUnit').val($a.data('unit') || '—');
        $('#editQty').val($a.data('qty'));
        $('#editCostPrice').val($a.data('cost'));
        $('#editSellingPrice').val($a.data('sell'));
        updateEditPreview();
        $('#editLineModal').modal('show');
    });

    function updateEditPreview() {
        var qty  = parseFloat($('#editQty').val())          || 0;
        var cost = parseFloat($('#editCostPrice').val())    || 0;
        var sell = parseFloat($('#editSellingPrice').val()) || 0;
        $('#editPreviewCost').text(fmt(qty * cost));
        $('#editPreviewSell').text(fmt(qty * sell));
    }
    $(document).on('input', '#editQty, #editCostPrice, #editSellingPrice', updateEditPreview);

    /* ══════════════════════════════════════════════════════════════════
       EDIT LINE — save (baseproducts remove+add pattern)
    ══════════════════════════════════════════════════════════════════ */
    $('#editSaveBtn').on('click', function () {
        var noteId = $.trim($('#editNoteId').val());
        var rowId  = $.trim($('#editRowId').val());
        var qty    = $('#editQty').val();
        var cost   = $('#editCostPrice').val();
        var sell   = $('#editSellingPrice').val();

        if (!noteId) { toastr.error('Missing note ID. Please close and reopen the edit modal.'); return; }
        if (qty   === '' || isNaN(parseFloat(qty))  || parseFloat(qty)  < 0) { toastr.warning('Please enter a valid quantity (0 or more).'); $('#editQty').focus(); return; }
        if (cost  === '' || isNaN(parseFloat(cost)) || parseFloat(cost) < 0) { toastr.warning('Please enter a valid cost price (0 or more).'); $('#editCostPrice').focus(); return; }
        if (sell  === '' || isNaN(parseFloat(sell)) || parseFloat(sell) < 0) { toastr.warning('Please enter a valid selling price (0 or more).'); $('#editSellingPrice').focus(); return; }

        var $btn = $(this).prop('disabled', true)
            .html('<i class="ri-loader-4-line me-1" style="animation:spin .8s linear infinite;display:inline-block;"></i> Saving…');

        showProgress();

        $.ajax({
            type: 'POST',
            url:  '{{ route("retail.operations.deliverynotes.line.update") }}',
            data: {
                _token:        _token,
                note_id:       noteId,
                branch_id:     branchId,
                quantity:      parseFloat(qty),
                cost_price:    parseFloat(cost),
                selling_price: parseFloat(sell),
            },
            complete: function () {
                hideProgress();
                $btn.prop('disabled', false).html('<i class="ri-check-line me-1"></i> Update');
            },
            success: function (data) {
                if (data.status === 201 && data.success) {
                    toastr.success(data.success);
                    $('#editLineModal').modal('hide');
                    if (data.line) {
                        updateRowInTable(data.line);
                    } else {
                        loadTable();
                    }
                } else if (data.info) {
                    toastr.info(data.info);
                } else {
                    toastr.error('Unexpected response from server.');
                }
            },
            error: handleAjaxError,
        });
    });

    /* ── Header buttons ───────────────────────────────────────────────── */
    $('#pdfBtn').on('click',      function (e) { e.preventDefault(); window.location.href = pdfUrl; });
    $('#downloadBtn').on('click', function (e) { e.preventDefault(); $('#downloadModal').modal('show'); });
    $('#refreshBtn').on('click',  function (e) { e.preventDefault(); loadTable(); });
    $('#infoBtn').on('click',     function (e) { e.preventDefault(); $('#detInfoModal').modal('show'); });

    /* ── Flash messages ───────────────────────────────────────────────── */
    @if(Session::has('message'))
        toastr['{{ Session::get("alert-type","info") }}']('{{ Session::get("message") }}');
    @endif

    /* ── Initial load ─────────────────────────────────────────────────── */
    loadTable();
});
</script>
@endsection