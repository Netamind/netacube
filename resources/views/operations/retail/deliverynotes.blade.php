@extends('operations.retail.dashboard')
@section('content')

@php
    use Carbon\Carbon;

    $pref       = DB::connection('tenant')->table('user_filters')->where('user_id', Auth::id())->first();
    $categories = DB::connection('tenant')->table('categories')->orderBy('category')->get();

    $selectedCategory = null;
    $customDate       = $pref->dnote_custom_date ?? null;
    $isCustom         = !empty($customDate);
    $date             = $isCustom ? $customDate : Carbon::today()->toDateString();
    $displayDate      = Carbon::parse($date)->format('d M Y');

    if ($pref && $pref->category_id) {
        $selectedCategory = DB::connection('tenant')
            ->table('categories')
            ->where('id', $pref->category_id)
            ->first();
    }

    /* ── Category summary ─────────────────────────────────────────────── */
    $branches        = collect();
    $branchSummary   = collect();
    $grandTotalCost  = 0;
    $grandTotalValue = 0;

    if ($selectedCategory) {
        $branches = DB::connection('tenant')
            ->table('branches')
            ->where('sector',   'Retail')
            ->where('category', (string) $selectedCategory->id)
            ->where('status',   'active')
            ->orderBy('name')
            ->get();

        $categoryBranchIds = $branches->pluck('id');

        $branchSummary = DB::connection('tenant')
            ->table('retail_deliverynotes as rdn')
            ->join('branches as b', 'b.id', '=', 'rdn.branch_id')
            ->whereIn('rdn.branch_id', $categoryBranchIds)
            ->where('rdn.delivery_date', $date)
            ->select(
                'rdn.branch_id',
                'b.name as branch_name',
                DB::raw('SUM(rdn.quantity * rdn.cost_price) as total_cost'),
                DB::raw('SUM(rdn.quantity * rdn.selling_price) as total_value')
            )
            ->groupBy('rdn.branch_id', 'b.name')
            ->orderBy('b.name')
            ->get();

        $grandTotalCost  = $branchSummary->sum('total_cost');
        $grandTotalValue = $branchSummary->sum('total_value');
    }
@endphp

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
    border-radius: 0 !important;
    border: none;
}
.ch-inner {
    display: flex; align-items: center;
    padding: 0 14px; height: 48px; gap: 8px;
    flex-wrap: nowrap;
}
.ch-left  { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; overflow: hidden; }
.ch-right { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }

#categorySelectHeader {
    border: none; background: transparent; color: #fff;
    font-size: 15px; font-weight: 600; cursor: pointer;
    padding: 0; outline: none; flex: 0 1 auto;
    min-width: 0; max-width: 200px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
#categorySelectHeader option { color: #1e293b; background: #fff; font-size: 13px; }

.ch-sep { width: 1px; height: 20px; background: rgba(255,255,255,0.25); flex-shrink: 0; }

.ch-date-chip {
    display: inline-flex; align-items: center; gap: 4px;
    background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25);
    border-radius: 20px; padding: 5px 10px;
    font-size: 11px; font-weight: 500; color: #fff;
    white-space: nowrap; cursor: pointer; transition: background .15s;
    user-select: none; flex-shrink: 0;
}
.ch-date-chip:hover { background: rgba(255,255,255,0.28); }
.ch-date-chip .mode-badge {
    font-size: 9px; padding: 1px 5px; border-radius: 8px;
    background: rgba(255,255,255,0.2); color: #fff;
    font-weight: 600; letter-spacing: .3px;
}
.ch-date-chip.custom-mode { background: rgba(245,158,11,0.30); border-color: rgba(245,158,11,0.6); }
.ch-date-chip.custom-mode .mode-badge { background: rgba(245,158,11,0.5); }
.ch-date-chip .chip-edit-icon { font-size: 10px; opacity: .75; margin-left: 2px; }
.ch-date-chip:hover .chip-edit-icon { opacity: 1; }
.ch-date-chip.no-category { opacity: .6; cursor: default; pointer-events: none; }

.ch-btn {
    width: 30px; height: 30px; border-radius: 7px;
    background: #fff; border: 1px solid rgba(255,255,255,0.6);
    color: #4B5EBD; display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 14px; transition: background .15s, box-shadow .15s;
    text-decoration: none; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}
.ch-btn:hover { background: #f0f2ff; color: #3a4ca0; box-shadow: 0 2px 6px rgba(0,0,0,0.15); }

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

/* ── Summary strip ────────────────────────────────────────────────────── */
.dn-summary-strip {
    display: flex; align-items: stretch;
    background: #f4f6ff; border-bottom: 1.5px solid #e4e7f5;
}
.dn-strip-seg {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: center; padding: 12px 10px;
}
.dn-strip-seg.accent { background: #eff3ff; }
.dn-strip-label {
    font-size: 8px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .7px; color: #94a3b8; margin-bottom: 3px;
}
.dn-strip-seg.accent .dn-strip-label { color: #6478c0; }
.dn-strip-val {
    font-size: 15px; font-weight: 800; color: #1e293b;
    font-variant-numeric: tabular-nums; line-height: 1;
}
.dn-strip-seg.accent .dn-strip-val { color: #3b4fa0; }
.dn-strip-divider { width: 1px; background: #dde1f0; margin: 10px 0; flex-shrink: 0; }

/* ── Table wrapper ────────────────────────────────────────────────────── */
.dn-table-wrap { padding: 20px 20px 36px; background: #fff; position: relative; }

/* ── DataTable buttons ────────────────────────────────────────────────── */
.dt-buttons .btn {
    background: transparent !important;
    background-image: none !important;
    box-shadow: none !important;
    border-color: #5bc0de;
    color: #5bc0de;
}
.dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }

/* ── Submission badge ─────────────────────────────────────────────────── */
.dn-submit-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 90px; height: 24px; border-radius: 20px;
    font-size: 11px; font-weight: 700; padding: 0 10px;
    border: 1px solid; cursor: pointer; white-space: nowrap;
    transition: background .15s, color .15s;
}
.dn-submit-badge.is-pending  { background: #eff3ff; color: #4B5EBD; border-color: #c5caec; }
.dn-submit-badge.is-pending:hover { background: #4B5EBD; color: #fff; }
.dn-submit-badge.is-complete { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }
.dn-submit-badge.is-disabled { cursor: default; pointer-events: none; opacity: .9; }

/* ── Error pill ───────────────────────────────────────────────────────── */
.dn-err-count {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 28px; height: 22px; border-radius: 5px;
    font-size: 12px; font-weight: 700;
    font-variant-numeric: tabular-nums; padding: 0 7px;
    border: 1px solid; cursor: pointer; transition: background .15s, color .15s, border-color .15s;
}
.dn-err-count.has-errors {
    background: #fee2e2; color: #b91c1c; border-color: #fecaca;
}
.dn-err-count.has-errors:hover { background: #fecaca; }
.dn-err-count.no-errors {
    background: #f1f5f9; color: #94a3b8; border-color: #e2e8f0;
}
.dn-err-count.no-errors:hover { background: #e2e8f0; color: #64748b; }

/* ── Stats modal — 2x2 grid reusing .dn-strip-label / .dn-strip-val ───── */
.stats-modal-grid {
    display: flex; flex-wrap: wrap; gap: 12px; padding: 18px;
}
.stats-modal-cell {
    flex: 1 1 calc(50% - 6px); min-width: 140px;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    background: #f4f6ff; border: 1px solid #e4e7f5; border-radius: 10px; padding: 16px 10px;
}
.stats-modal-cell.accent { background: #eff3ff; border-color: #dbe2fb; }
.stats-modal-cell .dn-strip-label { margin-bottom: 6px; }
.stats-modal-cell .dn-strip-val { font-size: 18px; }

/* ── Insights modal tabs (Totals / Distribution / Download) ───────────── */
.ins-modal-tabs {
    display: flex; flex-wrap: nowrap; margin: 0; padding: 0 10px;
    background: #fff; border-bottom: 1px solid #e5e7eb;
}
.ins-modal-tabs .nav-link {
    display: flex; align-items: center; gap: 5px;
    border: none; border-bottom: 3px solid transparent; border-radius: 0 !important;
    padding: 10px 14px; font-size: 12.5px; font-weight: 600; color: #6c757d;
    background: transparent; transition: color .15s, border-color .15s;
}
.ins-modal-tabs .nav-link i { font-size: 14px; }
.ins-modal-tabs .nav-link:hover { color: #4B5EBD; }
.ins-modal-tabs .nav-link.active { color: #4B5EBD !important; border-bottom-color: #4B5EBD; background: transparent; }

#insightsTabsContent { max-height: 65vh; overflow-y: auto; }
#insTabDownload { padding: 18px; }

.ins-dist-footnote {
    display: flex; align-items: flex-start; gap: 6px;
    font-size: 10.5px; color: #94a3b8; line-height: 1.5;
    padding: 10px 18px 14px; border-top: 1px solid #e8eaf5; background: #f8f9ff;
}
.ins-dist-footnote i { font-size: 12px; margin-top: 1px; flex-shrink: 0; }

/* ── No-category placeholder ──────────────────────────────────────────── */
.no-category-wrap { padding: 60px 16px; text-align: center; }
.no-category-wrap i { font-size: 48px; color: #dde1f0; display: block; margin-bottom: 14px; }
.no-category-wrap p { color: #94a3b8; font-size: 13px; }

/* ── Modals ───────────────────────────────────────────────────────────── */
.mh-blue   { background: linear-gradient(135deg,#4B5EBD,#576CC0); padding: 12px 18px !important; border-bottom: none; border-radius: 8px 8px 0 0; }
.mh-amber  { background: linear-gradient(135deg,#d97706,#f59e0b); padding: 12px 18px !important; border-bottom: none; border-radius: 8px 8px 0 0; }
.mh-danger { background: linear-gradient(135deg,#dc2626,#ef4444); padding: 12px 18px !important; border-bottom: none; border-radius: 8px 8px 0 0; }
.mh-title  { color: #fff; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.mh-close  { filter: brightness(0) invert(1); opacity: .8; }
.mh-close:hover { opacity: 1; }
.modal-content { border: none; border-radius: 10px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.18); }

.date-mode-toggle { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px; }
.dmc { padding: 10px 12px; border-radius: 8px; border: 1px solid #e2e8f0; cursor: pointer; transition: all .15s; }
.dmc:hover { border-color: #a0aec0; }
.dmc.active-sys { border-color: #4B5EBD; background: #eff3ff; }
.dmc.active-cus { border-color: #d97706; background: #fffbeb; }
.dmc-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 2px; }
.dmc.active-sys .dmc-label { color: #3b4fa0; }
.dmc.active-cus .dmc-label { color: #92400e; }
.dmc-val  { font-size: 13px; font-weight: 600; color: #64748b; }
.dmc.active-sys .dmc-val { color: #4B5EBD; }
.dmc.active-cus .dmc-val { color: #d97706; }
.dmc-desc { font-size: 10px; color: #94a3b8; margin-top: 2px; }

/* ── Bulk action buttons ──────────────────────────────────────────────── */
.bulk-action-btn {
    width: 100%; display: flex; align-items: center; gap: 12px;
    background: #f8f9fa; border: 1px solid #e4e7f5; border-radius: 9px;
    padding: 11px 14px; font-size: 13px; font-weight: 500; color: #1e293b;
    cursor: pointer; text-align: left; transition: background .15s; margin-bottom: 8px;
}
.bulk-action-btn:hover { background: #eef0f8; }
.bulk-action-btn.bab-submit  { border-color: #c5caec; }
.bulk-action-btn.bab-submit:hover  { background: #eff3ff; }
.bulk-action-btn.bab-unsubmit { border-color: #fde68a; }
.bulk-action-btn.bab-unsubmit:hover { background: #fffbeb; }
.bulk-action-btn.bab-delete  { background: #fff5f5; border-color: #fecaca; color: #b91c1c; }
.bulk-action-btn.bab-delete:hover  { background: #fee2e2; }
.bulk-action-btn i { font-size: 18px; flex-shrink: 0; }
.bulk-action-btn .bab-title { font-weight: 600; font-size: 13px; }
.bulk-action-btn .bab-desc  { font-size: 11px; color: #94a3b8; margin-top: 1px; }
.bab-delete .bab-desc { color: #fca5a5; }

/* ── Action buttons ───────────────────────────────────────────────────── */
.dn-action-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border-radius: 6px;
    border: 1px solid; cursor: pointer; transition: all .15s;
    text-decoration: none; flex-shrink: 0;
}
.dn-action-btn.btn-submit  { background: #eff3ff; border-color: #c5caec; color: #4B5EBD; }
.dn-action-btn.btn-submit:hover  { background: #4B5EBD; color: #fff; border-color: #4B5EBD; }
.dn-action-btn.btn-pdf     { background: #fef2f2; border-color: #fecaca; color: #dc2626; }
.dn-action-btn.btn-pdf:hover     { background: #dc2626; color: #fff; border-color: #dc2626; }
.dn-action-btn.btn-details { background: #f0f9ff; border-color: #bae6fd; color: #0369a1; }
.dn-action-btn.btn-details:hover { background: #0369a1; color: #fff; border-color: #0369a1; }
.dn-action-btn.btn-disabled { background: #f8f9fa; border-color: #e2e8f0; color: #cbd5e1; cursor: default; pointer-events: none; }

/* ── Summary modal ────────────────────────────────────────────────────── */
.sum-totals-strip {
    display: flex; align-items: stretch;
    background: #f4f6ff; border-bottom: 1.5px solid #e4e7f5;
}
.sum-strip-seg {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: center; padding: 12px 10px;
}
.sum-strip-seg.accent { background: #eff3ff; }
.sum-strip-label {
    font-size: 8px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .7px; color: #94a3b8; margin-bottom: 3px;
}
.sum-strip-seg.accent .sum-strip-label { color: #6478c0; }
.sum-strip-val {
    font-size: 15px; font-weight: 800; color: #1e293b;
    font-variant-numeric: tabular-nums; line-height: 1;
}
.sum-strip-seg.accent .sum-strip-val { color: #3b4fa0; }
.sum-strip-divider { width: 1px; background: #dde1f0; margin: 10px 0; flex-shrink: 0; }
.sum-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.sum-th-name {
    font-size: 9px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: #94a3b8;
    padding: 9px 16px; background: #f8f9fa;
    border-bottom: 1.5px solid #e2e8f0; text-align: left;
}
.sum-th-c {
    font-size: 9px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: #94a3b8;
    padding: 9px 16px; background: #f8f9fa;
    border-bottom: 1.5px solid #e2e8f0; text-align: center;
}
.sum-tr { border-bottom: 1px solid #f1f5f9; transition: background .1s; }
.sum-tr:last-child { border-bottom: none; }
.sum-tr:hover { background: #f8f9ff; }
.sum-td-name { padding: 9px 16px; color: #1e293b; font-weight: 600; font-size: 12px; display: flex; align-items: center; gap: 8px; }
.sum-td-c { padding: 9px 16px; text-align: center; font-variant-numeric: tabular-nums; }
.sum-row-num {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 20px; height: 20px; border-radius: 5px;
    background: #eef0fa; color: #7080c4;
    font-size: 9px; font-weight: 700; flex-shrink: 0;
}
.sum-cost   { color: #475569; font-weight: 500; }
.sum-amount { color: #059669; font-weight: 700; }
.sum-tfoot-tr { background: #f0f2fa; border-top: 2px solid #dde1f0; }
.sum-tfoot-label { padding: 10px 16px; font-size: 12px; font-weight: 700; color: #2d3a8c; }
.sum-tfoot-num { padding: 10px 16px; font-size: 12px; font-weight: 700; color: #1e293b; font-variant-numeric: tabular-nums; }
.sum-tfoot-num.accent { color: #3b4fa0; }
.sum-empty { padding: 48px 20px; text-align: center; }
.sum-empty i { font-size: 36px; color: #dde1f0; display: block; margin-bottom: 10px; }
.sum-empty p { font-size: 13px; color: #94a3b8; margin: 0; }
.sum-footer-info-icon { font-size: 15px; color: #a0aec0; cursor: default; transition: color .15s; }
.sum-footer-info-wrap:hover .sum-footer-info-icon { color: #4B5EBD; }
.sum-footer-tooltip {
    display: none; position: absolute; bottom: calc(100% + 8px); right: 0;
    width: 220px; background: #1e293b; color: #e2e8f0; font-size: 11px;
    line-height: 1.5; padding: 8px 10px; border-radius: 7px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.18); z-index: 9999;
    pointer-events: none; white-space: normal;
}
.sum-footer-tooltip::after {
    content: ''; position: absolute; top: 100%; right: 6px;
    border: 5px solid transparent; border-top-color: #1e293b;
}
.sum-footer-info-wrap:hover .sum-footer-tooltip { display: block; }

/* ── Errors table modal ───────────────────────────────────────────────── */
.err-tbl { width: 100%; border-collapse: collapse; font-size: 12px; }
.err-tbl thead th {
    background: #e2e2e9; color: #475569;
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; padding: 9px 12px;
    border-bottom: 2px solid #d0d3e0;
    white-space: nowrap;
}
.err-tbl thead th:first-child { text-align: left; }
.err-tbl thead th:not(:first-child) { text-align: center; }
.err-tbl tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .1s; }
.err-tbl tbody tr:last-child { border-bottom: none; }
.err-tbl tbody tr:hover td { background: #f8f9ff; }
.err-tbl tbody td { padding: 9px 12px; vertical-align: middle; color: #1e293b; }
.err-tbl tbody td:first-child { text-align: left; font-weight: 600; }
.err-tbl tbody td:not(:first-child) { text-align: center; }
.err-diff-pos { color: #16a34a; font-weight: 700; }
.err-diff-neg { color: #dc2626; font-weight: 700; }
.err-status-badge {
    display: inline-flex; align-items: center; gap: 3px;
    font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: 5px; border: 1px solid;
}
.err-status-pending  { background: #fef9c3; color: #854d0e; border-color: #fde68a; }
.err-status-approved { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }
.err-status-rejected { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }
.err-act-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 26px; height: 26px; border-radius: 5px; border: 1px solid;
    cursor: pointer; transition: all .15s; background: none; font-size: 13px;
}
.err-act-approve { border-color: #bbf7d0; color: #16a34a; }
.err-act-approve:hover { background: #16a34a; color: #fff; border-color: #16a34a; }
.err-act-reject  { border-color: #fecaca; color: #dc2626; }
.err-act-reject:hover  { background: #dc2626; color: #fff; border-color: #dc2626; }
.err-act-btn:disabled, .err-act-btn[disabled] { opacity: .35; cursor: default; pointer-events: none; }

@media (max-width: 767px) {
    .dn-table-wrap { padding: 12px 12px 28px; }
    .dn-summary-strip { flex-wrap: wrap; }
    #categorySelectHeader { max-width: 130px; }
}

@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
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
            <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
                  id="headerCategoryForm" style="margin:0;display:contents;">
                @csrf
                <input type="hidden" name="user_id"           value="{{ Auth::id() }}">
                <input type="hidden" name="action_product_id" value="">
                <select name="category_id" id="categorySelectHeader"
                        onchange="document.getElementById('headerCategoryForm').submit()">
                    <option value="" hidden>{{ $selectedCategory ? $selectedCategory->category : '— Select Category —' }}</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ ($pref && $pref->category_id == $cat->id) ? 'selected' : '' }}>
                            {{ $cat->category }}
                        </option>
                    @endforeach
                </select>
            </form>

            <div class="ch-sep"></div>

            <div class="ch-date-chip {{ $isCustom ? 'custom-mode' : '' }} {{ !$selectedCategory ? 'no-category' : '' }}"
                 id="dateChip"
                 title="{{ $selectedCategory ? 'Change delivery date' : 'Select a category first' }}">
                <i class="ri-calendar-line" style="font-size:11px;"></i>
                <span id="dateChipText">{{ $displayDate }}</span>
                <span class="mode-badge" id="dateChipBadge">{{ $isCustom ? 'Custom' : 'Today' }}</span>
                <i class="ri-pencil-line chip-edit-icon"></i>
            </div>
        </div>

        <div class="ch-right">
            @if($selectedCategory)
            <a href="#" class="ch-btn" id="globalActionsBtn" title="Global actions for {{ $displayDate }}">
                <i class="ri-settings-3-line"></i>
            </a>
            <a href="#" class="ch-btn" id="insightsBtn" title="View totals, distribution &amp; downloads for {{ $displayDate }}">
                <i class="ri-bar-chart-2-line"></i>
            </a>
            @endif
            <a href="#" class="ch-btn" id="infoBtn" title="About Delivery Notes">
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
                <i class="ri-send-plane-line"></i> Actioncentre
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('retail.operations.deliverynotes') }}" class="nav-link active">
                <i class="ri-file-list-3-line"></i> Deliverynotes
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('retail.operations.pricechanges') }}" class="nav-link">
                <i class="ri-price-tag-3-line"></i> Pricechanges
            </a>
        </li>
    </ul>
</div>

@if(!$selectedCategory)
    <div class="no-category-wrap">
        <i class="ri-store-2-line d-block mx-auto"></i>
        <p>Select a category from the header to view delivery notes.</p>
    </div>
@else

{{-- ══ Table ══════════════════════════════════════════════════════════════ --}}
<div class="dn-table-wrap">
    <table id="dnTable" class="table table-sm table-striped row-border order-column w-100">
        <thead style="background-color:#e2e2e9">
            <tr>
                <th>&nbsp;Branch Name</th>
                <th style="text-align:center">Cost (MWK)</th>
                <th style="text-align:center">Value (MWK)</th>
                <th style="text-align:center">Submission</th>
                <th style="text-align:center">Errors</th>
                <th style="text-align:center">Actions</th>
            </tr>
        </thead>
        <tbody id="dnTableBody"></tbody>
    </table>
</div>

@endif {{-- end selectedCategory --}}

</div>{{-- .card --}}
</div></div></div>


{{-- ══ INSIGHTS MODAL (Totals / Distribution / Download) ══════════════ --}}
<div class="modal fade" id="statsModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:560px;">
        <div class="modal-content">
            <div class="modal-header mh-blue" style="display:flex;align-items:center;justify-content:space-between;">
                <h5 class="modal-title mh-title"><i class="ri-bar-chart-2-line"></i> Insights — {{ $displayDate }}</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal" style="margin:0;"></button>
            </div>

            <ul class="nav nav-tabs ins-modal-tabs" id="insightsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="insTabTotals-tab" data-bs-toggle="tab" data-bs-target="#insTabTotals" type="button" role="tab" aria-controls="insTabTotals" aria-selected="true">
                        <i class="ri-bar-chart-2-line"></i> Totals
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="insTabDist-tab" data-bs-toggle="tab" data-bs-target="#insTabDist" type="button" role="tab" aria-controls="insTabDist" aria-selected="false">
                        <i class="ri-bar-chart-grouped-line"></i> Distribution
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="insTabDownload-tab" data-bs-toggle="tab" data-bs-target="#insTabDownload" type="button" role="tab" aria-controls="insTabDownload" aria-selected="false">
                        <i class="ri-download-line"></i> Download
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="insightsTabsContent">

                {{-- ── Tab 1: Totals ── --}}
                <div class="tab-pane fade show active" id="insTabTotals" role="tabpanel" aria-labelledby="insTabTotals-tab">
                    <div class="stats-modal-grid">
                        <div class="stats-modal-cell">
                            <span class="dn-strip-label">Total Cost</span>
                            <span class="dn-strip-val" id="stripCost">—</span>
                        </div>
                        <div class="stats-modal-cell">
                            <span class="dn-strip-label">Total Value</span>
                            <span class="dn-strip-val" id="stripValue">—</span>
                        </div>
                        <div class="stats-modal-cell">
                            <span class="dn-strip-label">Submitted</span>
                            <span class="dn-strip-val" id="stripSubmitted">—</span>
                        </div>
                        <div class="stats-modal-cell accent">
                            <span class="dn-strip-label">Pending</span>
                            <span class="dn-strip-val" id="stripPending">—</span>
                        </div>
                    </div>
                </div>

                {{-- ── Tab 2: Distribution Summary ── --}}
                <div class="tab-pane fade" id="insTabDist" role="tabpanel" aria-labelledby="insTabDist-tab">
                    @if($selectedCategory)
                    @if($branchSummary->isNotEmpty())
                    <div class="sum-totals-strip">
                        <div class="sum-strip-seg">
                            <span class="sum-strip-label">Date</span>
                            <span class="sum-strip-val" style="font-size:13px;font-weight:700;color:#3b4fa0;">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</span>
                        </div>
                        <div class="sum-strip-divider"></div>
                        <div class="sum-strip-seg">
                            <span class="sum-strip-label">Total Cost</span>
                            <span class="sum-strip-val">{{ number_format($grandTotalCost, 2) }}</span>
                        </div>
                        <div class="sum-strip-divider"></div>
                        <div class="sum-strip-seg accent">
                            <span class="sum-strip-label">Total Amount</span>
                            <span class="sum-strip-val">{{ number_format($grandTotalValue, 2) }}</span>
                        </div>
                    </div>
                    <div style="padding:0 0 4px;">
                        <table class="sum-table">
                            <thead>
                                <tr>
                                    <th class="sum-th-name">#&nbsp;&nbsp;Branch Name</th>
                                    <th class="sum-th-c">Total Cost (MWK)</th>
                                    <th class="sum-th-c">Amount (MWK)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($branchSummary as $i => $row)
                                <tr class="sum-tr">
                                    <td class="sum-td-name">
                                        <span class="sum-row-num">{{ $i + 1 }}</span>
                                        {{ $row->branch_name }}
                                    </td>
                                    <td class="sum-td-c sum-cost">{{ number_format($row->total_cost, 2) }}</td>
                                    <td class="sum-td-c sum-amount">{{ number_format($row->total_value, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="sum-tfoot-tr">
                                    <td class="sum-tfoot-label">Grand Total</td>
                                    <td class="sum-td-c sum-tfoot-num">{{ number_format($grandTotalCost, 2) }}</td>
                                    <td class="sum-td-c sum-tfoot-num accent">{{ number_format($grandTotalValue, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="ins-dist-footnote">
                        <i class="ri-information-line"></i>
                        All delivery notes for {{ $selectedCategory->category ?? '' }} on {{ $displayDate }}. Includes both submitted and pending notes.
                    </div>
                    @else
                    <div class="sum-empty">
                        <i class="ri-inbox-2-line"></i>
                        <p>No delivery notes found for <strong>{{ $selectedCategory->category }}</strong> on {{ $displayDate }}.</p>
                    </div>
                    @endif
                    @endif
                </div>

                {{-- ── Tab 3: Download ── --}}
                <div class="tab-pane fade" id="insTabDownload" role="tabpanel" aria-labelledby="insTabDownload-tab">
                    <p class="mb-2" style="font-size:13px;">Click a button to export the delivery notes table.</p>
                    <div class="buttons"></div>
                </div>

            </div>
        </div>
    </div>
</div>


{{-- ══ DATE MODAL ══════════════════════════════════════════════════════ --}}
<div class="modal fade" id="dateModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog" style="max-width:400px;">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-calendar-event-line"></i> Delivery Date</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <div class="date-mode-toggle">
                    <div class="dmc {{ !$isCustom ? 'active-sys' : '' }}" id="dmcSystem" onclick="setDateMode('system')">
                        <div class="dmc-label">System date</div>
                        <div class="dmc-val">{{ Carbon::today()->format('d M Y') }}</div>
                        <div class="dmc-desc">Today, auto-updates daily</div>
                    </div>
                    <div class="dmc {{ $isCustom ? 'active-cus' : '' }}" id="dmcCustom" onclick="setDateMode('custom')">
                        <div class="dmc-label">Custom date</div>
                        <div class="dmc-val" id="dmcCustomVal">{{ $isCustom ? Carbon::parse($date)->format('d M Y') : 'Pick a date' }}</div>
                        <div class="dmc-desc">Past or future deliveries</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('tenant.admin.update.filters') }}" id="dateForm">
                    @csrf
                    <input type="hidden" name="user_id"          value="{{ Auth::id() }}">
                    <input type="hidden" name="dnote_custom_date" id="dateFormValue" value="">
                    <div id="customDateRow" style="{{ $isCustom ? '' : 'display:none;' }}">
                        <label class="form-label fw-semibold" style="font-size:13px;">Select date</label>
                        <input type="date" class="form-control" id="customDateInput"
                               value="{{ $isCustom ? $date : Carbon::today()->toDateString() }}"
                               oninput="previewCustomDate(this.value)">
                    </div>
                    <div id="systemDateNotice" class="{{ $isCustom ? 'd-none' : '' }} mt-2"
                         style="background:#eff3ff;border-left:3px solid #4B5EBD;border-radius:0 5px 5px 0;padding:8px 12px;font-size:12px;color:#3b4fa0;">
                        <i class="ri-information-line me-1"></i>
                        Using today's date <strong>{{ Carbon::today()->format('d M Y') }}</strong>.
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="ri-check-line"></i> Apply</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


{{-- ══ SUBMIT SINGLE BRANCH MODAL ══════════════════════════════════════ --}}
<div class="modal fade" id="submitBranchModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-send-plane-2-line"></i> Submit Pending Notes</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 22px;">
                <div style="display:flex;align-items:flex-start;gap:14px;">
                    <div style="width:42px;height:42px;border-radius:50%;background:#eff3ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="ri-send-plane-2-line" style="font-size:20px;color:#4B5EBD;"></i>
                    </div>
                    <div>
                        <p style="font-size:13px;font-weight:600;color:#1e293b;margin:0 0 6px;">Submit pending delivery notes?</p>
                        <p style="font-size:12px;color:#64748b;margin:0;">
                            All pending notes for <strong id="submitBranchName"></strong>
                            on <strong id="submitBranchDate"></strong> will be marked submitted and branch stock updated.
                        </p>
                    </div>
                </div>
                <div style="background:#eff3ff;border-left:3px solid #4B5EBD;border-radius:0 5px 5px 0;padding:8px 12px;font-size:11px;color:#3b4fa0;margin-top:14px;">
                    <i class="ri-information-line me-1"></i> This action cannot be undone.
                </div>
            </div>
            <div class="modal-footer" style="padding:10px 20px 14px;gap:8px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="submitBranchConfirmBtn">
                    <i class="ri-send-plane-2-line"></i> Yes, Submit
                </button>
            </div>
        </div>
    </div>
</div>


{{-- ══ GLOBAL ACTIONS MODAL (was BULK ACTIONS — now scoped to whole category+date) ═══ --}}
<div class="modal fade" id="bulkActionsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog" style="max-width:440px;">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title">
                    <i class="ri-settings-3-line"></i>
                    Global Actions
                </h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:16px 18px 20px;">
                <div style="background:#f4f6ff;border:1px solid #e4e7f5;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:12px;color:#475569;">
                    <i class="ri-calendar-line me-1 text-primary"></i>
                    Operating on delivery notes for <strong id="bulkDateLabel">—</strong>
                </div>

                <button class="bulk-action-btn bab-submit" id="bulkSubmitBtn">
                    <i class="ri-send-plane-2-line" style="color:#4B5EBD;"></i>
                    <div>
                        <div class="bab-title">Submit pending notes</div>
                        <div class="bab-desc">Marks all unsubmitted notes as submitted · updates branch stock</div>
                    </div>
                </button>

                <button class="bulk-action-btn bab-unsubmit" id="bulkUnsubmitBtn">
                    <i class="ri-arrow-go-back-line" style="color:#d97706;"></i>
                    <div>
                        <div class="bab-title">Unsubmit submitted notes</div>
                        <div class="bab-desc">Reverses submission · stock is decremented accordingly</div>
                    </div>
                </button>

                <button class="bulk-action-btn bab-delete" id="bulkDeleteBtn">
                    <i class="ri-delete-bin-5-line"></i>
                    <div>
                        <div class="bab-title">Delete all notes</div>
                        <div class="bab-desc">Permanently removes all notes for every branch · cannot be undone</div>
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>


{{-- ══ BULK CONFIRM MODAL ═══════════════════════════════════════════════ --}}
<div class="modal fade" id="bulkConfirmModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header" id="bulkConfirmHeader" style="padding:12px 18px !important;border-bottom:none;border-radius:8px 8px 0 0;">
                <h5 class="modal-title mh-title" id="bulkConfirmTitle"></h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 22px;">
                <div style="display:flex;align-items:flex-start;gap:14px;">
                    <div style="width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;" id="bulkConfirmIconWrap">
                        <i id="bulkConfirmIcon" style="font-size:20px;"></i>
                    </div>
                    <div>
                        <p style="font-size:13px;font-weight:600;color:#1e293b;margin:0 0 6px;" id="bulkConfirmHeading"></p>
                        <p style="font-size:12px;color:#64748b;margin:0;" id="bulkConfirmBody"></p>
                    </div>
                </div>
                <div style="border-radius:0 5px 5px 0;padding:8px 12px;font-size:11px;margin-top:14px;border-left:3px solid;" id="bulkConfirmNote"></div>
            </div>
            <div class="modal-footer" style="padding:10px 20px 14px;gap:8px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm" id="bulkConfirmExecuteBtn"></button>
            </div>
        </div>
    </div>
</div>


{{-- ══ ERRORS MODAL ════════════════════════════════════════════════════ --}}
<div class="modal fade" id="errorsModal" tabindex="-1">
    <div class="modal-dialog modal-lg" style="max-width:680px;">
        <div class="modal-content">
            <div class="modal-header mh-blue" style="display:flex;align-items:center;justify-content:space-between;">
                <h5 class="modal-title mh-title">
                    <i class="ri-error-warning-line"></i>
                    Discrepancies — <span id="errModalBranchName"></span>
                </h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal" style="margin:0;"></button>
            </div>
            <div class="modal-body" style="padding:0;overflow-x:auto;">
                <table class="err-tbl" id="errTable">
                    <thead style="background-color:#e2e2e9;">
                        <tr>
                            <th>Product Name</th>
                            <th style="text-align:center;">Unit</th>
                            <th style="text-align:center;">Price (MWK)</th>
                            <th style="text-align:center;">Qty</th>
                            <th style="text-align:center;">Error</th>
                            <th style="text-align:center;">Status</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="errTableBody">
                        <tr><td colspan="7" style="text-align:center;padding:32px;color:#94a3b8;font-size:13px;">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;gap:8px;justify-content:space-between;align-items:center;">
                <span style="font-size:11px;color:#94a3b8;" id="errModalDate"></span>
                <div style="display:flex;gap:8px;">
                    <button type="button" class="btn btn-success btn-sm" id="approveAllErrorsBtn">
                        <i class="ri-check-double-line me-1"></i> Approve All
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


{{-- ══ INFO MODAL ══════════════════════════════════════════════════════ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Delivery Notes</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <table style="width:100%;font-size:13px;border-collapse:collapse;">
                    <tbody>
                        @foreach([
                            ['Table view',        'Shows a per-branch summary of all delivery notes for the selected date and category.'],
                            ['Submission badge',  'Shows how many lines are submitted, e.g. "20 of 30 Submitted". Click it to submit that branch\'s pending notes — a confirmation prompt appears first. It is disabled once every line is submitted.'],
                            ['Errors column',     'Shows the number of discrepancies detected for that branch. Click the badge (even at 0) to review, approve, or reject each discrepancy.'],
                            ['Global actions',    'Click the settings icon in the header to submit, unsubmit, or delete delivery notes for every branch in this category and date at once.'],
                            ['Download PDF',      'Downloads a PDF of all delivery notes for the branch on the selected date.'],
                            ['Note Details',      'Opens the full delivery note details for the branch — view, edit, unsubmit, or delete individual lines.'],
                            ['Distribution Summary', 'Shows the total cost and selling value across all branches for the selected category and date.'],
                        ] as [$k,$v])
                        <tr>
                            <td style="padding:7px 12px;font-weight:700;color:#475569;width:180px;border-bottom:1px solid #f1f5f9;white-space:nowrap;">{{ $k }}</td>
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

    toastr.options = { timeOut: 5000, progressBar: true, positionClass: 'toast-top-end', closeButton: true };

    /* ── State ────────────────────────────────────────────────────────── */
    var activeDate        = '{{ $date }}';
    var activeCategoryId  = '{{ $pref->category_id ?? "" }}';
    var categoryName      = @json($selectedCategory->category ?? '');
    var pendingBranchId   = null;
    var pendingBulkAction = null;
    var dtTable           = null;

    /* ── Errors data ──────────────────────────────────────────────────────
       ▼ CHANGED: emptied out — errors will be wired up to a real endpoint
       later. Every branch now shows "0" and the badge stays fully
       clickable; the modal will simply render its empty state. ── */
    var dummyErrors = {};

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

    /* ── Initialise DataTable once on page load with empty tbody ──────────
       ▼ FIX: wrapped in try/catch. If DataTable init throws (e.g. the
       Buttons extension failing to load), the exception was synchronous
       and killed the REST of this $(document).ready() callback — which is
       exactly where the #globalActionsBtn, #bulkSubmitBtn/#bulkUnsubmitBtn
       /#bulkDeleteBtn, and .dn-submit-badge click handlers are registered
       further down. That is why clicking the gear icon or the submission
       badge did nothing at all and never even reached toastr — the
       handlers were simply never bound. Catching the error here guarantees
       every handler below always binds, and surfaces the real problem via
       toastr/console instead of failing silently. ── */
    @if($selectedCategory)
    try {
        dtTable = $('#dnTable').DataTable({
            dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>Brt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
            lengthChange: true,
            lengthMenu:   [[25, 50, 100, -1], [25, 50, 100, 'All']],
            pageLength:   25,
            fixedColumns: { left: 1 },
            scrollX:      true,
            order:        [[0, 'asc']],
            columnDefs:   [
                { orderable: false, targets: [5] },
                { className: 'text-center', targets: [1, 2, 3, 4, 5] },
            ],
            buttons: [
                { extend: 'excelHtml5', title: 'Delivery Notes - ' + activeDate, exportOptions: { columns: ':visible:not(:last-child)' } },
                { extend: 'csvHtml5',   title: 'Delivery Notes - ' + activeDate, exportOptions: { columns: ':visible:not(:last-child)' } },
                { extend: 'pdfHtml5',   title: 'Delivery Notes - ' + activeDate, exportOptions: { columns: ':visible:not(:last-child)' },
                  customize: function (doc) { doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split(''); }
                },
            ],
            language: {
                search: '',
                searchPlaceholder: 'Search branches…',
                emptyTable:  'No delivery notes found for this date.',
                zeroRecords: 'No branches match your search.',
            },
        });

        /* Move DataTable buttons into download modal once on init */
        dtTable.buttons().container().appendTo($('#insTabDownload .buttons'));
    } catch (initErr) {
        console.error('dnTable DataTable init failed:', initErr);
        toastr.error('Table failed to initialise properly. Some export/download options may be unavailable.', 'Warning');
    }
    @endif

    /* ── Load table data ──────────────────────────────────────────────── */
    function loadTable() {
        if (!activeCategoryId || !dtTable) return;

        showProgress();

        $.ajax({
            type: 'GET',
            url:  '{{ route("retail.operations.deliverynotes.branch-summary") }}',
            data: { delivery_date: activeDate, category_id: activeCategoryId },
            complete: function () { hideProgress(); },
            success: function (data) {
                if (data.status !== 200) { toastr.error('Failed to load data.'); return; }

                var rows = data.rows;

                /* Summary strip */
                $('#stripCost').text(fmt(data.grand_total_cost));
                $('#stripValue').text(fmt(data.grand_total_value));
                $('#stripSubmitted').text(fmt(data.grand_submitted_value));
                $('#stripPending').text(fmt(data.grand_pending_value));

                /* ── Base URLs ── */
                var pdfBase     = '{{ route("retail.operations.deliverynotes.branch.export-pdf") }}';
                var detailsBase = '{{ route("retail.operations.deliverynotes.branch.details") }}';

                dtTable.clear();

                if (rows.length) {
                    rows.forEach(function (r) {
                        var branchErrors = dummyErrors[String(r.branch_id)] || [];
                        var errCount     = branchErrors.length; // always 0 until errors are implemented

                        var errBadge = '<span class="dn-err-count dn-err-pill ' + (errCount > 0 ? 'has-errors' : 'no-errors') + '" '
                            + 'data-branch-id="' + r.branch_id + '" data-branch-name="' + r.branch_name + '" '
                            + 'title="Click to review discrepancies">' + errCount + '</span>';

                        var total     = r.total_product_lines;
                        var submitted = r.submitted_note_count;
                        var pending   = r.pending_note_count;
                        var complete  = (pending === 0);

                        var submitBadge = '<span class="dn-submit-badge ' + (complete ? 'is-complete is-disabled' : 'is-pending') + '" '
                            + 'data-branch-id="' + r.branch_id + '" data-branch-name="' + r.branch_name + '" '
                            + (complete ? 'title="All notes submitted"' : 'title="Click to submit pending notes"')
                            + '>' + submitted + ' of ' + total + ' Submitted</span>';

                        var pdfUrl     = pdfBase     + '?branch_id=' + r.branch_id + '&date=' + activeDate;
                        var detailsUrl = detailsBase + '?branch_id=' + r.branch_id + '&date=' + activeDate;

                        var pdfBtn     = '<a href="' + pdfUrl + '" class="dn-action-btn btn-pdf dn-pdf-btn" title="Download PDF" data-url="' + pdfUrl + '"><i class="ri-file-pdf-2-line"></i></a>';
                        var detailsBtn = '<a href="' + detailsUrl + '" class="dn-action-btn btn-details" title="View delivery note details"><i class="ri-eye-line"></i></a>';

                        var rowHtml = '<tr>'
                            + '<td><strong>' + r.branch_name + '</strong></td>'
                            + '<td style="text-align:center">' + fmt(r.total_cost_value) + '</td>'
                            + '<td style="text-align:center">' + fmt(r.total_selling_value) + '</td>'
                            + '<td style="text-align:center">' + submitBadge + '</td>'
                            + '<td style="text-align:center">' + errBadge + '</td>'
                            + '<td style="text-align:center"><div style="display:flex;align-items:center;justify-content:center;gap:5px;">' + pdfBtn + detailsBtn + '</div></td>'
                            + '</tr>';

                        dtTable.row.add($(rowHtml));
                    });
                }

                dtTable.draw();
            },
            error: handleAjaxError,
        });
    }

    /* ── PDF download — same tab ──────────────────────────────────────── */
    $(document).on('click', '.dn-pdf-btn', function (e) {
        e.preventDefault();
        window.location.href = $(this).data('url');
    });

    /* ── Errors modal ─────────────────────────────────────────────────── */
    function renderErrTable(branchId) {
        var errors = dummyErrors[branchId] || [];
        if (!errors.length) {
            return '<tr><td colspan="7" style="text-align:center;padding:36px;color:#94a3b8;font-size:13px;">'
                 + '<i class="ri-checkbox-circle-line" style="font-size:30px;display:block;margin-bottom:8px;color:#86efac;"></i>'
                 + 'No discrepancies found.</td></tr>';
        }
        var html = '';
        errors.forEach(function (err) {
            var diffClass   = err.diff.charAt(0) === '+' ? 'err-diff-pos' : 'err-diff-neg';
            var statusClass = err.status === 'Approved' ? 'err-status-approved'
                            : err.status === 'Rejected' ? 'err-status-rejected'
                            : 'err-status-pending';
            var actDisabled = (err.status !== 'Pending') ? ' disabled' : '';
            html += '<tr id="errRow-' + err.id + '">'
                + '<td>' + err.product + '</td>'
                + '<td style="text-align:center;">' + err.unit + '</td>'
                + '<td style="text-align:center;">' + parseFloat(err.price).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) + '</td>'
                + '<td style="text-align:center;">' + err.qty + '</td>'
                + '<td style="text-align:center;"><span class="' + diffClass + '">' + err.diff + '</span></td>'
                + '<td style="text-align:center;"><span class="err-status-badge ' + statusClass + '">' + err.status + '</span></td>'
                + '<td style="text-align:center;">'
                +   '<div style="display:inline-flex;gap:5px;align-items:center;">'
                +     '<button class="err-act-btn err-act-approve" data-err-id="' + err.id + '" data-branch-id="' + branchId + '" title="Approve"' + actDisabled + '><i class="ri-check-line"></i></button>'
                +     '<button class="err-act-btn err-act-reject"  data-err-id="' + err.id + '" data-branch-id="' + branchId + '" title="Reject"'  + actDisabled + '><i class="ri-close-line"></i></button>'
                +   '</div>'
                + '</td>'
                + '</tr>';
        });
        return html;
    }

    $(document).on('click', '.dn-err-pill', function (e) {
        e.preventDefault();
        var branchId   = String($(this).data('branch-id'));
        var branchName = $(this).data('branch-name');
        $('#errModalBranchName').text(branchName);
        $('#errModalDate').text('Date: ' + activeDate);
        $('#errTableBody').html(renderErrTable(branchId));
        $('#errorsModal').modal('show');
    });

    $(document).on('click', '.err-act-approve', function () {
        var errId    = $(this).data('err-id');
        var branchId = String($(this).data('branch-id'));
        if (dummyErrors[branchId]) {
            var err = dummyErrors[branchId].find(function(e) { return e.id === errId; });
            if (err) err.status = 'Approved';
        }
        $('#errTableBody').html(renderErrTable(branchId));
        toastr.success('Approved.', '');
        loadTable();
    });

    $(document).on('click', '.err-act-reject', function () {
        var errId    = $(this).data('err-id');
        var branchId = String($(this).data('branch-id'));
        if (dummyErrors[branchId]) {
            var err = dummyErrors[branchId].find(function(e) { return e.id === errId; });
            if (err) err.status = 'Rejected';
        }
        $('#errTableBody').html(renderErrTable(branchId));
        toastr.warning('Rejected.', '');
        loadTable();
    });

    $('#approveAllErrorsBtn').on('click', function () {
        var branchId = String($('#errTableBody .err-act-approve').first().data('branch-id'));
        if (!branchId || !dummyErrors[branchId]) return;
        dummyErrors[branchId].forEach(function(e) { if (e.status === 'Pending') e.status = 'Approved'; });
        $('#errTableBody').html(renderErrTable(branchId));
        toastr.success('All discrepancies approved.', 'Done');
        loadTable();
    });

    /* ── Global actions (settings icon in header) ─────────────────────── */
    $('#globalActionsBtn').on('click', function (e) {
        e.preventDefault();
        $('#bulkDateLabel').text(activeDate);
        $('#bulkActionsModal').modal('show');
    });

    function openBulkConfirm(action) {
        $('#bulkActionsModal').modal('hide');
        pendingBulkAction = action;

        var configs = {
            submit: {
                headerClass: 'mh-blue',
                iconClass: 'ri-send-plane-2-line', iconColor: '#4B5EBD', wrapBg: '#eff3ff',
                title:   '<i class="ri-send-plane-2-line"></i> Submit Pending Notes',
                heading: 'Submit all pending notes for ' + categoryName + '?',
                body:    'All unsubmitted delivery notes for every branch in <strong>' + categoryName + '</strong> on <strong>' + activeDate + '</strong> will be marked submitted and branch stock updated.',
                noteStyle: 'background:#eff3ff;color:#3b4fa0;border-color:#4B5EBD;',
                noteText:  '<i class="ri-information-line me-1"></i> Stock will be incremented. This cannot be undone.',
                btnClass: 'btn-primary', btnText: '<i class="ri-send-plane-2-line me-1"></i> Yes, Submit',
            },
            unsubmit: {
                headerClass: 'mh-amber',
                iconClass: 'ri-arrow-go-back-line', iconColor: '#d97706', wrapBg: '#fff8e1',
                title:   '<i class="ri-arrow-go-back-line"></i> Unsubmit Notes',
                heading: 'Unsubmit all submitted notes for ' + categoryName + '?',
                body:    'Submitted delivery notes for every branch in <strong>' + categoryName + '</strong> on <strong>' + activeDate + '</strong> will be reverted to pending and branch stock will be decremented.',
                noteStyle: 'background:#fff8e1;color:#92400e;border-color:#f59e0b;',
                noteText:  '<i class="ri-alert-line me-1"></i> Stock will be reversed. Use with caution.',
                btnClass: 'btn-warning text-white', btnText: '<i class="ri-arrow-go-back-line me-1"></i> Yes, Unsubmit',
            },
            delete: {
                headerClass: 'mh-danger',
                iconClass: 'ri-delete-bin-5-line', iconColor: '#dc2626', wrapBg: '#fef2f2',
                title:   '<i class="ri-delete-bin-5-line"></i> Delete Notes',
                heading: 'Delete all notes for ' + categoryName + '?',
                body:    'All delivery notes (submitted and pending) for every branch in <strong>' + categoryName + '</strong> on <strong>' + activeDate + '</strong> will be permanently deleted.',
                noteStyle: 'background:#fef2f2;color:#7f1d1d;border-color:#dc2626;',
                noteText:  '<i class="ri-alert-line me-1"></i> This is irreversible. Stock is NOT reversed for submitted notes.',
                btnClass: 'btn-danger', btnText: '<i class="ri-delete-bin-5-line me-1"></i> Yes, Delete',
            },
        };

        var c = configs[action];
        $('#bulkConfirmHeader').attr('class', 'modal-header ' + c.headerClass);
        $('#bulkConfirmTitle').html(c.title);
        $('#bulkConfirmIconWrap').css('background', c.wrapBg);
        $('#bulkConfirmIcon').attr('class', c.iconClass).css('color', c.iconColor);
        $('#bulkConfirmHeading').text(c.heading);
        $('#bulkConfirmBody').html(c.body);
        $('#bulkConfirmNote').attr('style', 'border-radius:0 5px 5px 0;padding:8px 12px;font-size:11px;margin-top:14px;border-left:3px solid;' + c.noteStyle).html(c.noteText);
        $('#bulkConfirmExecuteBtn').attr('class', 'btn btn-sm ' + c.btnClass).html(c.btnText);

        setTimeout(function () { $('#bulkConfirmModal').modal('show'); }, 300);
    }

    $('#bulkSubmitBtn').on('click',   function () { openBulkConfirm('submit');   });
    $('#bulkUnsubmitBtn').on('click', function () { openBulkConfirm('unsubmit'); });
    $('#bulkDeleteBtn').on('click',   function () { openBulkConfirm('delete');   });

    function executeGlobalBulkAction() {
        var urlMap = {
            submit:   '{{ route("retail.operations.deliverynotes.global.submit") }}',
            unsubmit: '{{ route("retail.operations.deliverynotes.global.unsubmit") }}',
            delete:   '{{ route("retail.operations.deliverynotes.global.delete") }}',
        };

        var url = urlMap[pendingBulkAction];
        if (!url) return;

        $('#bulkConfirmModal').modal('hide');
        showProgress();

        $.ajax({
            type: 'POST', url: url,
            data: { delivery_date: activeDate, category_id: activeCategoryId, _token: _token },
            complete: function () { hideProgress(); },
            success: function (data) {
                if (data.success) { toastr.success(data.success); loadTable(); }
                if (data.info)    { toastr.info(data.info); }
            },
            error: handleAjaxError,
        });
    }

    $('#bulkConfirmExecuteBtn').off('click').on('click', executeGlobalBulkAction);

    /* ── Submit single branch — triggered by clicking the submission badge ── */
    $(document).on('click', '.dn-submit-badge:not(.is-disabled)', function (e) {
        e.preventDefault();
        pendingBranchId = $(this).data('branch-id');
        $('#submitBranchName').text($(this).data('branch-name'));
        $('#submitBranchDate').text(activeDate);
        $('#submitBranchModal').modal('show');
    });

    $('#submitBranchConfirmBtn').off('click').on('click', function () {
        $('#submitBranchModal').modal('hide');
        showProgress();
        $.ajax({
            type: 'POST',
            url:  '{{ route("retail.operations.deliverynotes.branch.submit-pending") }}',
            data: { branch_id: pendingBranchId, delivery_date: activeDate, _token: _token },
            complete: function () { hideProgress(); },
            success: function (data) {
                if (data.success) { toastr.success(data.success); loadTable(); }
                if (data.info)    { toastr.info(data.info); }
            },
            error: handleAjaxError,
        });
    });

    /* ── Insights modal (Totals / Distribution / Download tabs) ────────── */
    $('#insightsBtn').on('click', function (e) { e.preventDefault(); $('#statsModal').modal('show'); });

    /* ── Info modal ───────────────────────────────────────────────────── */
    $('#infoBtn').on('click', function (e) { e.preventDefault(); $('#infoModal').modal('show'); });

    /* ── Date modal ───────────────────────────────────────────────────── */
    var currentDateMode = '{{ $isCustom ? "custom" : "system" }}';

    window.setDateMode = function (mode) {
        currentDateMode = mode;
        $('#dmcSystem').toggleClass('active-sys', mode === 'system').toggleClass('active-cus', false);
        $('#dmcCustom').toggleClass('active-cus', mode === 'custom').toggleClass('active-sys', false);
        $('#customDateRow').toggle(mode === 'custom');
        $('#systemDateNotice').toggleClass('d-none', mode === 'custom');
        $('#dateFormValue').val(mode === 'system' ? '' : $('#customDateInput').val());
    };

    window.previewCustomDate = function (val) {
        if (!val) return;
        var d  = new Date(val + 'T00:00:00');
        var mo = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $('#dmcCustomVal').text(d.getDate() + ' ' + mo[d.getMonth()] + ' ' + d.getFullYear());
        $('#dateFormValue').val(val);
    };

    $('#dateChip').on('click', function (e) {
        e.preventDefault();
        if ($(this).hasClass('no-category')) return;
        setDateMode(currentDateMode);
        $('#dateModal').modal('show');
    });
    $('#customDateInput').on('input', function () {
        $('#dateFormValue').val($(this).val());
        previewCustomDate($(this).val());
    });

    /* ── Flash messages ───────────────────────────────────────────────── */
    @if(Session::has('message'))
        toastr['{{ Session::get("alert-type","info") }}']('{{ Session::get("message") }}');
    @endif

    /* ── Initial load ─────────────────────────────────────────────────── */
    @if($selectedCategory)
    loadTable();
    @endif
});
</script>
@endsection