@extends('operations.retail.dashboard')
@section('content')

@push('head')
<meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

<style>
/* ── Progress bar ─────────────────────────────────────────────────────── */
#progressBar { height: 3px; display: none; transform: rotate(180deg); }

/* ── Card chrome ──────────────────────────────────────────────────────── */
.card      { border: none; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border-radius: 12px; }
.card-body { padding: 0 !important; }

/* ── Card header ──────────────────────────────────────────────────────── */
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

.ch-date-chip {
    display: inline-flex; align-items: center; gap: 4px;
    background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25);
    border-radius: 20px; padding: 5px 10px;
    font-size: 11px; font-weight: 500; color: #fff;
    white-space: nowrap; flex-shrink: 0;
}

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

/* ── Tabs ─────────────────────────────────────────────────────────────── */
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
.nav-pills .nav-link i { font-size: .95rem; margin-right: .3rem; }

/* ── Stats strip ──────────────────────────────────────────────────────── */
.det-stats-strip {
    display: flex; align-items: stretch;
    background: #f4f6ff; border-bottom: 1.5px solid #e4e7f5;
    flex-wrap: wrap;
}
.det-stat {
    flex: 1; min-width: 100px;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 12px 10px;
}
.det-stat.accent { background: #eff3ff; }
.det-stat-label {
    font-size: 8px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .7px; color: #94a3b8; margin-bottom: 3px;
}
.det-stat.accent .det-stat-label { color: #6478c0; }
.det-stat-val {
    font-size: 15px; font-weight: 800; color: #1e293b;
    font-variant-numeric: tabular-nums; line-height: 1;
}
.det-stat.accent .det-stat-val { color: #3b4fa0; }
.det-stat-divider { width: 1px; background: #dde1f0; margin: 10px 0; flex-shrink: 0; }

/* ── Selection bar ────────────────────────────────────────────────────── */
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
.dab-submit  { color: #4B5EBD; border-color: #c5caec; background: #eff3ff; }
.dab-submit:hover  { background: #4B5EBD; color: #fff; border-color: #4B5EBD; }
.dab-unsubmit { color: #d97706; border-color: #fde68a; background: #fffbeb; }
.dab-unsubmit:hover { background: #d97706; color: #fff; border-color: #d97706; }
.dab-delete  { color: #dc2626; border-color: #fecaca; background: #fef2f2; }
.dab-delete:hover  { background: #dc2626; color: #fff; border-color: #dc2626; }
.dab-pdf     { color: #dc2626; border-color: #fecaca; background: #fef2f2; }
.dab-pdf:hover     { background: #dc2626; color: #fff; border-color: #dc2626; }
.det-bar-sep { width: 1px; height: 20px; background: #e2e8f0; flex-shrink: 0; }

/* ── Table wrapper ────────────────────────────────────────────────────── */
.det-table-wrap { padding: 20px 20px 36px; background: #fff; position: relative; }

/* ── Loading overlay ──────────────────────────────────────────────────── */
#detLoadingOverlay {
    display: none; position: absolute; inset: 0;
    background: rgba(255,255,255,0.72); z-index: 10;
    align-items: center; justify-content: center;
    border-radius: 0 0 10px 10px;
}
#detLoadingOverlay .spinner-border { color: #4B5EBD; }

/* ── Row checkbox ─────────────────────────────────────────────────────── */
.det-row-check { width: 16px; height: 16px; cursor: pointer; accent-color: #4B5EBD; }

/* ── Status badge ─────────────────────────────────────────────────────── */
.status-badge {
    display: inline-flex; align-items: center; gap: 3px;
    font-size: 10px; font-weight: 600; padding: 2px 8px;
    border-radius: 5px; border: 1px solid; white-space: nowrap;
}
.status-submitted { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }
.status-pending   { background: #fef9c3; color: #854d0e; border-color: #fde68a; }

/* ── Inline action buttons ────────────────────────────────────────────── */
.det-act-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 26px; height: 26px; border-radius: 5px; border: 1px solid;
    cursor: pointer; transition: all .15s; background: none; font-size: 13px;
    text-decoration: none;
}
.det-act-unsubmit { background: #fffbeb; border-color: #fde68a; color: #d97706; }
.det-act-unsubmit:hover { background: #d97706; color: #fff; border-color: #d97706; }
.det-act-delete   { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
.det-act-delete:hover   { background: #dc2626; color: #fff; border-color: #dc2626; }
.det-act-submit   { background: #eff3ff; border-color: #c5caec; color: #4B5EBD; }
.det-act-submit:hover   { background: #4B5EBD; color: #fff; border-color: #4B5EBD; }
.det-act-btn.disabled { opacity: .35; pointer-events: none; cursor: default; }

/* ── Modals ───────────────────────────────────────────────────────────── */
.mh-blue   { background: linear-gradient(135deg,#4B5EBD,#576CC0); padding: 12px 18px !important; border-bottom: none; border-radius: 8px 8px 0 0; }
.mh-amber  { background: linear-gradient(135deg,#d97706,#f59e0b); padding: 12px 18px !important; border-bottom: none; border-radius: 8px 8px 0 0; }
.mh-danger { background: linear-gradient(135deg,#dc2626,#ef4444); padding: 12px 18px !important; border-bottom: none; border-radius: 8px 8px 0 0; }
.mh-title  { color: #fff; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.mh-close  { filter: brightness(0) invert(1); opacity: .8; }
.mh-close:hover { opacity: 1; }
.modal-content { border: none; border-radius: 10px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.18); }

/* ── DT buttons ───────────────────────────────────────────────────────── */
.dt-buttons .btn {
    background: transparent !important; background-image: none !important;
    box-shadow: none !important; border-color: #5bc0de; color: #5bc0de;
}
.dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }

@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

@media (max-width: 767px) {
    .det-table-wrap { padding: 12px 12px 28px; }
    .det-stats-strip { flex-wrap: wrap; }
}
</style>

<div class="progress" id="progressBar" role="progressbar">
    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

{{-- ══ Card header ══════════════════════════════════════════════════════ --}}
<div class="card-header">
    <div class="ch-inner">
        <div class="ch-left">
            <a href="{{ route('retail.operations.deliverynotes') }}" class="ch-btn back" title="Back to Delivery Notes">
                <i class="ri-arrow-left-line"></i>
            </a>
            <div class="ch-sep"></div>
            <div style="min-width:0;">
                <div class="ch-title">{{ $branch->name }}</div>
                <div class="ch-subtitle">Delivery Note Details</div>
            </div>
            <div class="ch-sep"></div>
            <div class="ch-date-chip">
                <i class="ri-calendar-line" style="font-size:11px;"></i>
                <span>{{ $displayDate }}</span>
            </div>
        </div>
        <div class="ch-right">
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

{{-- ══ Tabs ═══════════════════════════════════════════════════════════════ --}}
<div class="tab-header-container">
    <ul class="nav nav-pills mb-0">
        <li class="nav-item">
            <a href="{{ route('retail.operations.actioncenter') }}" class="nav-link">
                <i class="ri-send-plane-line"></i> Action Centre
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('retail.operations.deliverynotes') }}" class="nav-link">
                <i class="ri-file-list-3-line"></i> Delivery Notes
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('retail.operations.pricechanges') }}" class="nav-link">
                <i class="ri-price-tag-3-line"></i> Price Changes
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('retail.operations.deliverynote.details') }}" class="nav-link active">
                <i class="ri-file-text-line"></i> Note Details
            </a>
        </li>
    </ul>
</div>

{{-- ══ Stats strip ══════════════════════════════════════════════════════ --}}
<div class="det-stats-strip" id="statsStrip">
    <div class="det-stat">
        <span class="det-stat-label">Selected</span>
        <span class="det-stat-val" id="statSelected">0</span>
    </div>
    <div class="det-stat-divider"></div>
    <div class="det-stat">
        <span class="det-stat-label">Total Lines</span>
        <span class="det-stat-val" id="statTotalLines">—</span>
    </div>
    <div class="det-stat-divider"></div>
    <div class="det-stat">
        <span class="det-stat-label">Total Qty</span>
        <span class="det-stat-val" id="statTotalQty">—</span>
    </div>
    <div class="det-stat-divider"></div>
    <div class="det-stat">
        <span class="det-stat-label">Total Cost</span>
        <span class="det-stat-val" id="statTotalCost">—</span>
    </div>
    <div class="det-stat-divider"></div>
    <div class="det-stat">
        <span class="det-stat-label">Total Value</span>
        <span class="det-stat-val" id="statTotalValue">—</span>
    </div>
    <div class="det-stat-divider"></div>
    <div class="det-stat">
        <span class="det-stat-label">Submitted</span>
        <span class="det-stat-val" id="statSubmitted">—</span>
    </div>
    <div class="det-stat-divider"></div>
    <div class="det-stat accent">
        <span class="det-stat-label">Pending</span>
        <span class="det-stat-val" id="statPending">—</span>
    </div>
</div>

{{-- ══ Selection / action bar ═══════════════════════════════════════════ --}}
<div class="det-sel-bar" id="detSelBar">
    <span class="det-sel-count none" id="detSelCount">
        <i class="ri-checkbox-blank-line" style="font-size:13px;"></i> 0 selected
    </span>
    <div class="det-bar-sep"></div>
    <button type="button" class="det-action-bar-btn dab-submit"   id="barSubmitBtn"   disabled><i class="ri-corner-up-right-line"></i> Submit</button>
    <button type="button" class="det-action-bar-btn dab-unsubmit" id="barUnsubmitBtn" disabled><i class="ri-arrow-go-back-line"></i>   Unsubmit</button>
    <button type="button" class="det-action-bar-btn dab-delete"   id="barDeleteBtn"   disabled><i class="ri-delete-bin-5-line"></i>    Delete</button>
    <div class="det-bar-sep"></div>
    <button type="button" class="det-action-bar-btn dab-pdf" id="barPdfBtn">
        <i class="ri-file-pdf-2-line"></i> PDF
    </button>
</div>

{{-- ══ Table ══════════════════════════════════════════════════════════════ --}}
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
                    &nbsp;Product
                </th>
                <th style="text-align:center;">Code</th>
                <th style="text-align:center;">Unit</th>
                <th style="text-align:center;">Qty</th>
                <th style="text-align:center;">Cost Price</th>
                <th style="text-align:center;">Selling Price</th>
                <th style="text-align:center;">Cost Value</th>
                <th style="text-align:center;">Sell Value</th>
                <th style="text-align:center;">Status</th>
                <th style="text-align:center;">Submitted By</th>
                <th style="text-align:center;">Submitted At</th>
                <th style="text-align:center;">Actions</th>
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

</div>{{-- .card --}}
</div></div></div>


{{-- ══ CONFIRM MODAL (reusable) ════════════════════════════════════════ --}}
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


{{-- ══ INFO MODAL ══════════════════════════════════════════════════════ --}}
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
                            ['Line items',    'Each row represents one product line in the delivery note for this branch on the selected date.'],
                            ['Checkboxes',    'Select one or more lines to perform bulk Submit, Unsubmit, or Delete via the action bar above the table.'],
                            ['Submit',        'Marks selected pending lines as submitted and increments branch stock accordingly.'],
                            ['Unsubmit',      'Reverses submission on selected lines — sets back to pending and decrements stock.'],
                            ['Delete',        'Permanently deletes the selected note lines. Stock is NOT reversed for submitted lines.'],
                            ['PDF (action bar)','Downloads a PDF of all delivery notes for this branch on this date. Does not require a selection.'],
                            ['Row actions',   'Each row has inline submit (if pending) and delete buttons for single-line operations.'],
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
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });
    toastr.options = { timeOut: 5000, progressBar: true, positionClass: 'toast-top-end', closeButton: true };

    /* ── Page state ───────────────────────────────────────────────────── */
    var branchId   = {{ $branch->id }};
    var branchName = '{{ addslashes($branch->name) }}';
    var activeDate = '{{ $deliveryDate }}';
    var pdfUrl     = '{{ route("retail.operations.deliverynotes.branch.export-pdf") }}?branch_id={{ $branch->id }}&date={{ $deliveryDate }}';
    var dtTable    = null;
    var pendingAction = null;
    var pendingSingleId = null;  /* for single-row actions */

    /* ── Helpers ─────────────────────────────────────────────────────── */
    function showProgress() { $('#progressBar').show(); }
    function hideProgress() { $('#progressBar').hide(); }

    function fmt(n, d) {
        d = (d === undefined) ? 2 : d;
        if (n === null || n === undefined || n === '') return '—';
        return parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d });
    }

    function handleAjaxError(xhr) {
        var json = null;
        try { json = xhr.responseJSON || JSON.parse(xhr.responseText); } catch(e) {}
        if (xhr.status === 419) { toastr.error('Session expired. Refreshing…'); setTimeout(function(){ location.reload(); }, 2000); return; }
        toastr.error((json && (json.message || json.error)) || 'Unexpected error (HTTP ' + xhr.status + ').', 'Error');
    }

    /* ── Selection helpers ────────────────────────────────────────────── */
    function getSelectedNoteIds() {
        var ids = [];
        $('.det-row-check:checked').not('#selectAllDet').each(function () { ids.push($(this).val()); });
        return ids;
    }

    function updateSelectionUI() {
        var count = $('.det-row-check:checked').not('#selectAllDet').length;
        $('#statSelected').text(count);
        if (count > 0) {
            $('#detSelCount').removeClass('none').html('<i class="ri-checkbox-multiple-line" style="font-size:13px;"></i> ' + count + ' selected');
            $('#barSubmitBtn, #barUnsubmitBtn, #barDeleteBtn').prop('disabled', false);
        } else {
            $('#detSelCount').addClass('none').html('<i class="ri-checkbox-blank-line" style="font-size:13px;"></i> 0 selected');
            $('#barSubmitBtn, #barUnsubmitBtn, #barDeleteBtn').prop('disabled', true);
            $('#selectAllDet').prop('checked', false);
        }
    }

    /* ── Select all ───────────────────────────────────────────────────── */
    $(document).on('click', '#selectAllDet', function () {
        $('.det-row-check').not('#selectAllDet').prop('checked', this.checked);
        updateSelectionUI();
    });
    $(document).on('change', '.det-row-check', function () {
        if (!this.checked) $('#selectAllDet').prop('checked', false);
        updateSelectionUI();
    });

    /* ── Load table ───────────────────────────────────────────────────── */
    function loadTable() {
        showProgress();
        $('#detLoadingOverlay').css('display', 'flex');
        $('#selectAllDet').prop('checked', false);
        updateSelectionUI();

        $.ajax({
            type: 'GET',
            url:  '{{ route("retail.operations.deliverynotes.branch.lines") }}',
            data: { branch_id: branchId, delivery_date: activeDate },
            complete: function () { hideProgress(); $('#detLoadingOverlay').hide(); },
            success: function (data) {
                if (data.status !== 200) { toastr.error('Failed to load data.'); return; }

                var lines = data.lines;

                /* Update stats strip */
                $('#statTotalLines').text(lines.length || '—');
                var totalQty   = 0, totalCost  = 0, totalValue = 0;
                var submitted  = 0, pending    = 0;
                lines.forEach(function (l) {
                    totalQty   += parseFloat(l.quantity   || 0);
                    totalCost  += parseFloat(l.cost_value  || 0);
                    totalValue += parseFloat(l.sell_value  || 0);
                    if (l.submitted) submitted++; else pending++;
                });
                $('#statTotalQty').text(fmt(totalQty, 0));
                $('#statTotalCost').text(fmt(totalCost));
                $('#statTotalValue').text(fmt(totalValue));
                $('#statSubmitted').text(submitted || '—');
                $('#statPending').text(pending || '—');

                /* Build rows */
                var html = '';
                if (!lines.length) {
                    html = '<tr><td colspan="12" style="text-align:center;padding:48px 16px;color:#94a3b8;font-size:13px;">'
                         + '<i class="ri-inbox-2-line" style="font-size:36px;display:block;margin-bottom:10px;color:#dde1f0;"></i>'
                         + 'No delivery note lines found for this branch on ' + activeDate + '.</td></tr>';
                } else {
                    lines.forEach(function (l) {
                        var statusBadge = l.submitted
                            ? '<span class="status-badge status-submitted"><i class="ri-check-line"></i> Submitted</span>'
                            : '<span class="status-badge status-pending"><i class="ri-time-line"></i> Pending</span>';

                        var submitRowBtn = !l.submitted
                            ? '<a href="#" class="det-act-btn det-act-submit det-single-submit" data-note-id="' + l.id + '" data-product="' + l.product_name + '" title="Submit this line"><i class="ri-corner-up-right-line"></i></a>'
                            : '<a href="#" class="det-act-btn det-act-unsubmit det-single-unsubmit" data-note-id="' + l.id + '" data-product="' + l.product_name + '" title="Unsubmit this line"><i class="ri-arrow-go-back-line"></i></a>';

                        var deleteRowBtn = '<a href="#" class="det-act-btn det-act-delete det-single-delete" data-note-id="' + l.id + '" data-product="' + l.product_name + '" title="Delete this line"><i class="ri-delete-bin-5-line"></i></a>';

                        html += '<tr>'
                            + '<td>'
                            +   '<input type="checkbox" class="det-row-check" value="' + l.id + '" style="margin-right:8px;vertical-align:middle;">'
                            +   '<strong>' + l.product_name + '</strong>'
                            + '</td>'
                            + '<td style="text-align:center;font-family:monospace;font-size:11px;color:#64748b;">' + (l.product_code || '—') + '</td>'
                            + '<td style="text-align:center;">' + (l.product_unit || '—') + '</td>'
                            + '<td style="text-align:center;font-weight:700;">' + fmt(l.quantity, 0) + '</td>'
                            + '<td style="text-align:center;">' + fmt(l.cost_price) + '</td>'
                            + '<td style="text-align:center;">' + fmt(l.selling_price) + '</td>'
                            + '<td style="text-align:center;color:#475569;">' + fmt(l.cost_value) + '</td>'
                            + '<td style="text-align:center;color:#059669;font-weight:600;">' + fmt(l.sell_value) + '</td>'
                            + '<td style="text-align:center;">' + statusBadge + '</td>'
                            + '<td style="text-align:center;font-size:11px;color:#64748b;">' + (l.submitted_by_name || '—') + '</td>'
                            + '<td style="text-align:center;font-size:11px;color:#64748b;">' + (l.submitted_at || '—') + '</td>'
                            + '<td style="text-align:center;">'
                            +   '<div style="display:inline-flex;gap:4px;align-items:center;">'
                            +     submitRowBtn
                            +     deleteRowBtn
                            +   '</div>'
                            + '</td>'
                            + '</tr>';
                    });
                }

                $('#detTableBody').html(html);

                /* Re-init DataTable */
                if ($.fn.DataTable.isDataTable('#detTable')) { $('#detTable').DataTable().destroy(); }
                dtTable = $('#detTable').DataTable({
                    dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
                    lengthChange: true,
                    lengthMenu:   [[25, 50, 100, -1], [25, 50, 100, 'All']],
                    pageLength:   50,
                    scrollX:      true,
                    order:        [[0, 'asc']],
                    columnDefs:   [
                        { orderable: false, targets: [11] },
                        { className: 'text-center', targets: [1,2,3,4,5,6,7,8,9,10,11] },
                    ],
                    language: {
                        search: '',
                        searchPlaceholder: 'Search products…',
                        emptyTable: 'No delivery note lines found.',
                    },
                });

                updateSelectionUI();
            },
            error: handleAjaxError,
        });
    }

    /* ── Confirm modal helper ─────────────────────────────────────────── */
    function openConfirm(config, onExecute) {
        $('#detConfirmHeader').attr('class', 'modal-header ' + config.headerClass);
        $('#detConfirmTitle').html(config.title);
        $('#detConfirmIconWrap').css('background', config.wrapBg);
        $('#detConfirmIcon').attr('class', config.iconClass).css('color', config.iconColor);
        $('#detConfirmHeading').text(config.heading);
        $('#detConfirmBody').html(config.body);
        $('#detConfirmNote')
            .attr('style', 'border-radius:0 5px 5px 0;padding:8px 12px;font-size:11px;margin-top:14px;border-left:3px solid;' + config.noteStyle)
            .html(config.noteText);
        $('#detConfirmExecuteBtn').attr('class', 'btn btn-sm ' + config.btnClass).html(config.btnText)
            .off('click').on('click', function () {
                $('#detConfirmModal').modal('hide');
                onExecute();
            });
        $('#detConfirmModal').modal('show');
    }

    /* ── Bulk submit (action bar) ─────────────────────────────────────── */
    $('#barSubmitBtn').on('click', function () {
        var ids = getSelectedNoteIds();
        if (!ids.length) return;
        openConfirm({
            headerClass: 'mh-blue', iconClass: 'ri-corner-up-right-line', iconColor: '#4B5EBD', wrapBg: '#eff3ff',
            title: '<i class="ri-corner-up-right-line"></i> Submit Lines',
            heading: 'Submit ' + ids.length + ' selected line' + (ids.length > 1 ? 's' : '') + '?',
            body: 'Selected pending lines for <strong>' + branchName + '</strong> on <strong>' + activeDate + '</strong> will be submitted and branch stock updated.',
            noteStyle: 'background:#eff3ff;color:#3b4fa0;border-color:#4B5EBD;',
            noteText: '<i class="ri-information-line me-1"></i> Stock will be incremented. This cannot be undone.',
            btnClass: 'btn-primary', btnText: '<i class="ri-corner-up-right-line me-1"></i> Yes, Submit',
        }, function () {
            executeBulkLineAction('submit', ids);
        });
    });

    /* ── Bulk unsubmit ────────────────────────────────────────────────── */
    $('#barUnsubmitBtn').on('click', function () {
        var ids = getSelectedNoteIds();
        if (!ids.length) return;
        openConfirm({
            headerClass: 'mh-amber', iconClass: 'ri-arrow-go-back-line', iconColor: '#d97706', wrapBg: '#fff8e1',
            title: '<i class="ri-arrow-go-back-line"></i> Unsubmit Lines',
            heading: 'Unsubmit ' + ids.length + ' selected line' + (ids.length > 1 ? 's' : '') + '?',
            body: 'Selected submitted lines for <strong>' + branchName + '</strong> on <strong>' + activeDate + '</strong> will revert to pending and branch stock will be decremented.',
            noteStyle: 'background:#fff8e1;color:#92400e;border-color:#f59e0b;',
            noteText: '<i class="ri-alert-line me-1"></i> Stock will be reversed. Use with caution.',
            btnClass: 'btn-warning text-white', btnText: '<i class="ri-arrow-go-back-line me-1"></i> Yes, Unsubmit',
        }, function () {
            executeBulkLineAction('unsubmit', ids);
        });
    });

    /* ── Bulk delete ──────────────────────────────────────────────────── */
    $('#barDeleteBtn').on('click', function () {
        var ids = getSelectedNoteIds();
        if (!ids.length) return;
        openConfirm({
            headerClass: 'mh-danger', iconClass: 'ri-delete-bin-5-line', iconColor: '#dc2626', wrapBg: '#fef2f2',
            title: '<i class="ri-delete-bin-5-line"></i> Delete Lines',
            heading: 'Delete ' + ids.length + ' selected line' + (ids.length > 1 ? 's' : '') + '?',
            body: 'These delivery note lines for <strong>' + branchName + '</strong> on <strong>' + activeDate + '</strong> will be permanently deleted.',
            noteStyle: 'background:#fef2f2;color:#7f1d1d;border-color:#dc2626;',
            noteText: '<i class="ri-alert-line me-1"></i> Irreversible. Stock is NOT reversed for submitted lines.',
            btnClass: 'btn-danger', btnText: '<i class="ri-delete-bin-5-line me-1"></i> Yes, Delete',
        }, function () {
            executeBulkLineAction('delete', ids);
        });
    });

    /* ── Execute bulk line action ─────────────────────────────────────── */
    function executeBulkLineAction(action, ids) {
        var urlMap = {
            submit:   '{{ route("retail.operations.deliverynotes.lines.bulk.submit") }}',
            unsubmit: '{{ route("retail.operations.deliverynotes.lines.bulk.unsubmit") }}',
            delete:   '{{ route("retail.operations.deliverynotes.lines.bulk.delete") }}',
        };
        var url = urlMap[action];
        if (!url) return;

        showProgress();
        var postData = { branch_id: branchId, delivery_date: activeDate };
        ids.forEach(function (id, idx) { postData['note_ids[' + idx + ']'] = id; });

        $.ajax({
            type: 'POST', url: url, data: postData,
            complete: hideProgress,
            success: function (data) {
                if (data.success) { toastr.success(data.success); loadTable(); }
                if (data.info)    { toastr.info(data.info); }
            },
            error: handleAjaxError,
        });
    }

    /* ── Single row: submit ───────────────────────────────────────────── */
    $(document).on('click', '.det-single-submit', function (e) {
        e.preventDefault();
        var noteId  = $(this).data('note-id');
        var product = $(this).data('product');
        openConfirm({
            headerClass: 'mh-blue', iconClass: 'ri-corner-up-right-line', iconColor: '#4B5EBD', wrapBg: '#eff3ff',
            title: '<i class="ri-corner-up-right-line"></i> Submit Line',
            heading: 'Submit this delivery note line?',
            body: '<strong>' + product + '</strong> will be submitted and branch stock updated.',
            noteStyle: 'background:#eff3ff;color:#3b4fa0;border-color:#4B5EBD;',
            noteText: '<i class="ri-information-line me-1"></i> Stock will be incremented. This cannot be undone.',
            btnClass: 'btn-primary', btnText: '<i class="ri-corner-up-right-line me-1"></i> Yes, Submit',
        }, function () {
            executeSingleLineAction('submit', noteId);
        });
    });

    /* ── Single row: unsubmit ─────────────────────────────────────────── */
    $(document).on('click', '.det-single-unsubmit', function (e) {
        e.preventDefault();
        var noteId  = $(this).data('note-id');
        var product = $(this).data('product');
        openConfirm({
            headerClass: 'mh-amber', iconClass: 'ri-arrow-go-back-line', iconColor: '#d97706', wrapBg: '#fff8e1',
            title: '<i class="ri-arrow-go-back-line"></i> Unsubmit Line',
            heading: 'Unsubmit this delivery note line?',
            body: '<strong>' + product + '</strong> will revert to pending and branch stock will be decremented.',
            noteStyle: 'background:#fff8e1;color:#92400e;border-color:#f59e0b;',
            noteText: '<i class="ri-alert-line me-1"></i> Stock will be reversed.',
            btnClass: 'btn-warning text-white', btnText: '<i class="ri-arrow-go-back-line me-1"></i> Yes, Unsubmit',
        }, function () {
            executeSingleLineAction('unsubmit', noteId);
        });
    });

    /* ── Single row: delete ───────────────────────────────────────────── */
    $(document).on('click', '.det-single-delete', function (e) {
        e.preventDefault();
        var noteId  = $(this).data('note-id');
        var product = $(this).data('product');
        openConfirm({
            headerClass: 'mh-danger', iconClass: 'ri-delete-bin-5-line', iconColor: '#dc2626', wrapBg: '#fef2f2',
            title: '<i class="ri-delete-bin-5-line"></i> Delete Line',
            heading: 'Delete this delivery note line?',
            body: '<strong>' + product + '</strong> will be permanently deleted.',
            noteStyle: 'background:#fef2f2;color:#7f1d1d;border-color:#dc2626;',
            noteText: '<i class="ri-alert-line me-1"></i> Irreversible. Stock is NOT reversed.',
            btnClass: 'btn-danger', btnText: '<i class="ri-delete-bin-5-line me-1"></i> Yes, Delete',
        }, function () {
            executeSingleLineAction('delete', noteId);
        });
    });

    /* ── Execute single line action ───────────────────────────────────── */
    function executeSingleLineAction(action, noteId) {
        var urlMap = {
            submit:   '{{ route("retail.operations.deliverynotes.line.submit") }}',
            unsubmit: '{{ route("retail.operations.deliverynotes.line.unsubmit") }}',
            delete:   '{{ route("retail.operations.deliverynotes.line.delete") }}',
        };
        showProgress();
        $.ajax({
            type: 'POST', url: urlMap[action],
            data: { note_id: noteId, branch_id: branchId, delivery_date: activeDate },
            complete: hideProgress,
            success: function (data) {
                if (data.success) { toastr.success(data.success); loadTable(); }
                if (data.info)    { toastr.info(data.info); }
            },
            error: handleAjaxError,
        });
    }

    /* ── PDF (header button + action bar) ────────────────────────────── */
    function downloadPdf() { window.location.href = pdfUrl; }
    $('#pdfBtn').on('click', function (e) { e.preventDefault(); downloadPdf(); });
    $('#barPdfBtn').on('click', function () { downloadPdf(); });

    /* ── Refresh ──────────────────────────────────────────────────────── */
    $('#refreshBtn').on('click', function (e) { e.preventDefault(); loadTable(); });

    /* ── Info ─────────────────────────────────────────────────────────── */
    $('#infoBtn').on('click', function (e) { e.preventDefault(); $('#detInfoModal').modal('show'); });

    /* ── Flash messages ───────────────────────────────────────────────── */
    @if(Session::has('message'))
        toastr['{{ Session::get("alert-type","info") }}']('{{ Session::get("message") }}');
    @endif

    /* ── Initial load ─────────────────────────────────────────────────── */
    loadTable();
});
</script>
@endsection