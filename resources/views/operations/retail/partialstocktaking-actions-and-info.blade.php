@extends('operations.retail.dashboard')
@section('content')

@php
    use Carbon\Carbon;

    $pref = DB::connection('tenant')->table('user_filters')->where('user_id', Auth::id())->first();

    $branchId      = $pref->branch_id      ?? null;
    $pstCustomDate = $pref->pst_custom_date ?? null;
    $isCustom      = ! empty($pstCustomDate);
    $date          = $isCustom ? $pstCustomDate : Carbon::today()->toDateString();
    $displayDate   = Carbon::parse($date)->format('d M Y');

    $branches = DB::connection('tenant')->table('branches')
        ->where('sector', 'Retail')->where('status', 'active')->orderBy('name')->get();

    $branchName = $branchId
        ? (DB::connection('tenant')->table('branches')->where('id', $branchId)->value('name') ?? 'Branch not found')
        : null;

    $summary = $branchId
        ? DB::connection('tenant')->table('retail_partialstocktaking_summary')
            ->where('branch_id', $branchId)->where('date', $date)->first()
        : null;

    $isRectified = $summary && $summary->status === 'completed';

    $totalCount = $zeroCount = $positiveCount = $negativeCount = 0;
    $zeroPercentage = $positivePercentage = $negativePercentage = 0;
    $expectedValue = $foundValue = $positiveValue = $negativeValue = $differenceValue = 0;

    if ($branchId) {
        $counted = DB::connection('tenant')->table('retail_partialstocktaking')
            ->where('branch_id', $branchId)->where('date', $date)->get();

        // Expected is always expected_at_count — fixed, only ever moved by an
        // explicit edit on the Stocktaking Data tab. expected_final is an
        // informational figure written once at rectification and is never
        // used here, so these totals can never drift just from a page reload.
        $totalCount    = $counted->count();
        $zeroCount     = $counted->filter(fn ($r) => abs($r->found - $r->expected_at_count) < 0.0001)->count();
        $positiveCount = $counted->filter(fn ($r) => $r->found > $r->expected_at_count + 0.0001)->count();
        $negativeCount = $counted->filter(fn ($r) => $r->found < $r->expected_at_count - 0.0001)->count();

        $expectedValue = $counted->sum(fn ($r) => $r->expected_at_count * $r->price);
        $foundValue    = $counted->sum(fn ($r) => $r->found * $r->price);
        $positiveValue = $counted->filter(fn ($r) => $r->found > $r->expected_at_count)
            ->sum(fn ($r) => ($r->found - $r->expected_at_count) * $r->price);
        $negativeValue = $counted->filter(fn ($r) => $r->found < $r->expected_at_count)
            ->sum(fn ($r) => ($r->expected_at_count - $r->found) * $r->price);

        $totalCountSafe     = max($totalCount, 1);
        $zeroPercentage     = round(($zeroCount / $totalCountSafe) * 100, 2);
        $positivePercentage = round(($positiveCount / $totalCountSafe) * 100, 2);
        $negativePercentage = round(($negativeCount / $totalCountSafe) * 100, 2);
        $differenceValue    = $foundValue - $expectedValue;
    }
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
/* ══════════════════════════════════════════════════════════════
   PARTIAL STOCKTAKING — ACTIONS & INFO (mirrors Full Stocktaking's
   Actions & Info layout: silver header, blue branch bar, two-column
   card body, shared modal styling)
══════════════════════════════════════════════════════════════ */
.content-page > .content > .container-fluid { padding-top: 16px; }

/* ── Outer card ── */
.pst-card {
    border: none; box-shadow: none; border-radius: 0; overflow: hidden;
    display: flex; flex-direction: column; background-color: transparent;
    height: auto;
}

/* ── Silver header bar ── */
.pst-card-header {
    padding: 4px 10px !important;
    background-color: silver;
    color: #666666;
    display: flex; align-items: center; justify-content: space-between;
    flex: 0 0 auto; gap: 8px;
}

#pstDateChip {
    height: 28px; padding: 0 8px; border-radius: 4px;
    background: none; color: #666666; border: none;
    font-weight: bold; font-size: 14px;
    display: inline-flex; align-items: center; gap: 6px; cursor: pointer;
    white-space: nowrap;
}
#pstDateChip:hover { color: #333333; }
#pstDateChip .pst-mode-tag {
    font-size: 9px; font-weight: 700; letter-spacing: .3px;
    padding: 2px 6px; border-radius: 8px;
    background: rgba(255,255,255,.35); color: #555555; text-transform: uppercase;
}
#pstDateChip.custom-mode .pst-mode-tag { background: #fcd34d; color: #7c4a03; }
#pstDateChip .pst-edit-pencil { font-size: 11px; opacity: .65; }

.pst-hdr-left { display: flex; align-items: center; gap: 8px; min-width: 0; }

#pstActionsBtn {
    height: 28px; padding: 0 10px; border-radius: 4px;
    border: 1px solid rgba(102,102,102,.35); background: rgba(255,255,255,.35);
    color: #555555; font-size: 12px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 5px; cursor: pointer;
    white-space: nowrap; text-decoration: none; flex-shrink: 0;
}
#pstActionsBtn i { font-size: 14px; }
#pstActionsBtn:hover { background: rgba(255,255,255,.6); color: #333333; }

.pst-hdr-actions { display: flex; align-items: center; gap: 2px; flex-shrink: 0; }
.pst-hdr-btn {
    height: 24px; width: 24px; border: none; background: none;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 0; color: #666666; font-size: 16px;
    cursor: pointer; position: relative; padding: 1px; text-decoration: none;
}
.pst-hdr-btn:hover { color: #333333; }
.pst-hdr-divider { width: 1px; height: 16px; background: #8a8a8a; margin: 0 6px; opacity: .6; }

/* ── Blue branch bar ── */
.pst-branch-row {
    background: linear-gradient(to right, #4B5EBD, #576CC0);
    padding: 7px 10px; flex: 0 0 auto;
    display: flex; align-items: center; justify-content: space-between;
    gap: 10px; flex-wrap: nowrap;
}
.pst-branch-left {
    display: flex; flex-direction: row; align-items: center;
    gap: 10px; min-width: 0; flex: 1 1 auto;
}
#pstBranchForm { margin: 0; display: inline-flex; align-items: center; min-width: 0; }
#pstBranchSelect {
    border: none; background: transparent; color: silver;
    font-size: 16px; font-weight: 600; cursor: pointer;
    padding: 0 0 0 2px; outline: none; max-width: 280px;
}
#pstBranchSelect option { color: #1e293b; background: #fff; font-size: 14px; }

.pst-rectified-tag {
    font-size: 10px; font-weight: 700; background: #d1fae5; color: #065f46;
    border-radius: 5px; padding: 3px 8px; display: inline-flex; align-items: center; gap: 4px; flex-shrink: 0;
}

.pst-branch-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.pst-page-label {
    font-size: 12px; font-weight: 600; color: silver;
    white-space: nowrap; letter-spacing: .2px;
}

/* ── Card body ── */
.pst-card-body {
    flex: 0 1 auto; min-height: 0; display: flex;
    flex-direction: column; padding: 0 !important; overflow: hidden;
    background-color: #fff;
    border-radius: 0 0 10px 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

/* ── Placeholder ── */
.pst-placeholder-wrap { padding: 48px 20px; text-align: center; color: #94a3b8; }
.pst-placeholder-wrap i { font-size: 52px; display: block; margin-bottom: 12px; color: #c8d0ed; }
.pst-placeholder-wrap h5 { color: #64748b; font-weight: 600; }

/* ── Two-column content layout ── */
.ai-content-row {
    display: flex; gap: 0; align-items: stretch;
    padding: 0;
}
.ai-left-col {
    flex: 1 1 60%; padding: 20px 24px 24px 20px;
    border-right: 1px solid #e8eaf2;
    overflow-y: auto;
    display: flex; flex-direction: column; gap: 14px;
}
.ai-left-col .ai-card.stats-card {
    flex: 0 0 auto; display: flex; flex-direction: column;
}
.ai-right-col {
    flex: 0 0 38%; padding: 20px 20px 24px 20px;
    display: flex; flex-direction: column; gap: 14px;
    overflow-y: auto;
}

/* ── Stats table ── */
.stats-table { width: 100%; font-size: 13px; border-collapse: collapse; }
.stats-table th {
    color: #94a3b8; font-size: 10px; text-transform: uppercase;
    letter-spacing: .5px; font-weight: 700; padding: 8px 10px;
    border-bottom: 2px solid #e2e8f0; text-align: left;
}
.stats-table th.c, .stats-table td.c { text-align: center; }
.stats-table td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
.stats-table tr.total-row td { font-weight: 800; background: #f4f6ff; border-top: 2px solid #c5caec; }
.stats-icon { color: #4B5EBD; margin-right: 6px; }

/* ── Cards (shared) ── */
.ai-card {
    background: #f8f9fc; border: 1px solid #e4e7f5;
    border-radius: 10px; padding: 16px;
}
.ai-card-title {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .6px; color: #94a3b8; margin-bottom: 12px;
    display: flex; align-items: center; gap: 6px;
}

/* Action / download buttons */
.ai-dl-btn {
    display: flex; align-items: center; gap: 10px; padding: 10px 12px;
    border-radius: 8px; border: 1px solid #d8ddf0; background: #fff;
    cursor: pointer; text-decoration: none; transition: border-color 0.15s, background 0.15s;
    width: 100%; text-align: left;
}
.ai-dl-btn:hover { border-color: #4B5EBD; background: #eff3ff; }
.ai-dl-btn:disabled, .ai-dl-btn.disabled { opacity: .5; cursor: not-allowed; }
.ai-dl-btn:disabled:hover, .ai-dl-btn.disabled:hover { border-color: #d8ddf0; background: #fff; }
.ai-dl-btn i { font-size: 20px; color: #4B5EBD; flex-shrink: 0; }
.ai-dl-btn .dl-label { font-size: 12.5px; font-weight: 600; color: #1e293b; display: block; line-height: 1.2; }
.ai-dl-btn .dl-sub   { font-size: 11px; color: #64748b; display: block; margin-top: 2px; }
.ai-card .ai-dl-btn + .ai-dl-btn { margin-top: 10px; }

/* Sync gate card */
.ai-sync-card {
    background: #fffbeb; border: 1.5px solid #fcd34d;
    border-radius: 10px; padding: 16px;
}
.ai-sync-card.all-clear { background: #f0fdf4; border-color: #86efac; }
.ai-sync-title {
    font-size: 12px; font-weight: 700; display: flex; align-items: center;
    gap: 6px; margin-bottom: 10px; color: #92400e;
}
.ai-sync-card.all-clear .ai-sync-title { color: #166534; }

.device-list { list-style: none; padding: 0; margin: 0 0 12px; }
.device-list li {
    display: flex; align-items: center; justify-content: space-between;
    padding: 5px 0; border-bottom: 1px solid rgba(0,0,0,0.06); font-size: 12px;
}
.device-list li:last-child { border-bottom: none; }
.device-pill { font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 8px; }
.device-pill.synced  { background: #dcfce7; color: #166534; }
.device-pill.pending { background: #fee2e2; color: #991b1b; }
.device-pill.stk     { background: #dbeafe; color: #1e40af; }
.device-pill.pos     { background: #ede9fe; color: #5b21b6; }

#syncCheckBtn {
    width: 100%; padding: 7px 14px; border-radius: 7px; font-size: 12.5px; font-weight: 600;
    background: rgba(0,0,0,0.06); border: 1.5px solid rgba(0,0,0,0.12); color: #374151;
    cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;
    transition: background 0.15s;
}
#syncCheckBtn:hover { background: rgba(0,0,0,0.1); }
#syncCheckBtn.checking { opacity: .6; pointer-events: none; }

/* Rectify card */
.ai-rect-card {
    background: #f0fdf4; border: 1.5px solid #86efac;
    border-radius: 10px; padding: 16px;
}
.ai-rect-card.locked { background: #f8fafc; border-color: #cbd5e1; }
.ai-rect-title {
    font-size: 12px; font-weight: 700; color: #166534;
    display: flex; align-items: center; gap: 6px; margin-bottom: 8px;
}
.ai-rect-card.locked .ai-rect-title { color: #475569; }
.ai-rect-body { font-size: 12px; color: #14532d; line-height: 1.55; margin-bottom: 12px; }
.ai-rect-card.locked .ai-rect-body { color: #64748b; }

/* Rectify progress — mirrors the Live Counting tab's merge-progress UI
   (see pst-merge-* in partialstocktaking.blade.php) so both one-line-at-
   a-time flows look and feel the same. */
.rf-progress-wrap { display: none; }
.rf-progress-wrap.active { display: block; }
.rf-bar-track { width: 100%; height: 10px; border-radius: 6px; background: #e5e7eb; overflow: hidden; }
.rf-bar-fill { height: 100%; background: linear-gradient(90deg,#4B5EBD,#6b7fd7); width: 0%; transition: width .18s ease; border-radius: 6px; }
.rf-status-line { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; font-size: 12px; color: #475569; }
.rf-current-item { font-weight: 600; color: #1e293b; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rf-counts { display: flex; gap: 14px; margin-top: 10px; font-size: 12px; }
.rf-counts span { display: flex; align-items: center; gap: 5px; font-weight: 600; }
.rf-ok { color: #059669; } .rf-bad { color: #dc2626; }
.rf-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
.rf-dot.rf-ok { background: #059669; } .rf-dot.rf-bad { background: #dc2626; }
.rf-summary { display: none; }
.rf-summary.active { display: block; }
.rf-summary-title { font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
.rf-fail-list { max-height: 140px; overflow-y: auto; border: 1px solid #fecaca; background: #fef2f2; border-radius: 6px; padding: 8px 10px; margin-top: 8px; font-size: 11.5px; color: #991b1b; }
.rf-fail-list div { padding: 2px 0; }

/* Remarks card + modal */
#pstRemarksModalBox { width: 100%; min-height: 110px; border: 1px solid #d8ddf0; border-radius: 8px; padding: 10px 12px; font-size: 13px; color: #1a1a1a; resize: vertical; background: #fff; }
#pstRemarksModalBox:focus { outline: 1px solid #4B5EBD; }
.ai-remarks-preview { font-size: 12px; color: #475569; line-height: 1.5; white-space: pre-wrap; word-break: break-word; }
.ai-remarks-preview.empty { color: #94a3b8; font-style: italic; }

/* ── Modal headers (shared) ── */
.mh-pos {
    background: linear-gradient(to right, #4B5EBD, #576CC0);
    padding: 10px 16px !important; border-bottom: none;
}
.mh-pos-title { color: #fff; font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
.mh-blue   { background: linear-gradient(135deg,#4B5EBD,#576CC0); padding: 12px 18px !important; border-bottom: none; }
.mh-danger { background: linear-gradient(135deg,#dc2626,#ef4444); padding: 12px 18px !important; border-bottom: none; }
.mh-amber  { background: linear-gradient(135deg,#d97706,#f59e0b); padding: 12px 18px !important; border-bottom: none; }
.mh-title  { color: #fff; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.mh-close-w { filter: brightness(0) invert(1); opacity: .8; }
.mh-close-w:hover { opacity: 1; }

/* ── Nav actions modal links ── */
.pst-action-link {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 14px; border-radius: 8px;
    background: #f8f9fa; border: 1px solid #e2e8f0;
    text-decoration: none; color: #1e293b; transition: background .15s;
}
.pst-action-link:hover { background: #f1f5ff; color: #1e293b; }
.pst-action-link .fal-icon  { font-size: 20px; flex-shrink: 0; }
.pst-action-link .fal-title { font-size: 13px; font-weight: 600; }
.pst-action-link .fal-sub   { font-size: 11px; color: #64748b; }
.pst-action-link .fal-arrow { margin-left: auto; color: #94a3b8; font-size: 18px; flex-shrink: 0; }

/* ── Date modal ── */
.date-mode-toggle { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px; }
.dmc { padding: 10px 12px; border-radius: 8px; border: 1px solid #e2e8f0; cursor: pointer; }
.dmc.active-sys { border-color: #4B5EBD; background: #eff3ff; }
.dmc.active-cus { border-color: #d97706; background: #fffbeb; }
.dmc-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
.dmc-val   { font-size: 13px; font-weight: 600; color: #64748b; }
.dmc.active-sys .dmc-val { color: #4B5EBD; }
.dmc.active-cus .dmc-val { color: #d97706; }

/* ── Mobile ── */
@media (max-width: 768px) {
    .pst-card { height: auto !important; margin: 8px; }
    .content-page { padding: 0 !important; }
    .content { padding: 0 !important; }
    .content-page > .content > .container-fluid { padding-top: 0 !important; padding-left: 0 !important; padding-right: 0 !important; }
    .ai-content-row { flex-direction: column; }
    .ai-left-col { border-right: none; border-bottom: 1px solid #e8eaf2; padding-right: 20px; }
    .ai-right-col { flex: unset; }
    .modal-dialog { margin: 1.25rem auto !important; max-width: calc(100% - 24px) !important; }
    .modal-content { border-radius: 10px !important; }
    #pstActionsBtn { font-size: 11px; padding: 0 8px; height: 26px; }
    .pst-card-header { gap: 4px; }
    .pst-hdr-actions { gap: 0; }
    .pst-hdr-btn { width: 24px; height: 24px; font-size: 15px; padding: 0; }
    .pst-hdr-divider { margin: 0 3px; }
    .pst-branch-row { gap: 6px; }
    .pst-page-label { font-size: 11px; }
}
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>

<div class="content-page"><div class="content"><div class="container-fluid">

<div class="pst-card">

    {{-- ── Silver header bar ── --}}
    <div class="pst-card-header">
        <div class="pst-hdr-left">
            <button type="button" id="pstDateChip"
                    class="{{ $isCustom ? 'custom-mode' : '' }}"
                    title="Change stocktaking date">
                <i class="ri-calendar-line"></i> {{ $displayDate }}
                <span class="pst-mode-tag">{{ $isCustom ? 'Custom' : 'Today' }}</span>
                <i class="ri-pencil-line pst-edit-pencil"></i>
            </button>

            <button type="button" id="pstActionsBtn"
                    onclick="$('#pstNavActionsModal').modal('show')"
                    title="Quick navigation">
                <i class="ri-layout-grid-line"></i> <span>PS Actions</span>
            </button>
        </div>

        <div class="pst-hdr-actions">
            @if($branchId)
                <button type="button" class="pst-hdr-btn" id="pstStatsBtn" title="View summary stats">
                    <i class="ri-bar-chart-2-line"></i>
                </button>
                <span class="pst-hdr-divider"></span>
            @endif
            <button type="button" class="pst-hdr-btn" title="About Actions &amp; Info"
                    onclick="$('#pstInfoModal').modal('show')">
                <i class="ri-information-line"></i>
            </button>
        </div>
    </div>

    {{-- ── Blue branch bar ── --}}
    <div class="pst-branch-row">
        <div class="pst-branch-left">
            <form method="POST" action="{{ route('tenant.admin.update.filters') }}" id="pstBranchForm">
                @csrf
                <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                <select name="branch_id" id="pstBranchSelect"
                        onchange="document.getElementById('pstBranchForm').submit()">
                    <option value="" hidden>{{ $branchName ?? '— Select Branch —' }}</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </form>
            @if($isRectified)
                <span class="pst-rectified-tag"><i class="ri-lock-line"></i> Rectified</span>
            @endif
        </div>

        <div class="pst-branch-right">
            <span class="pst-page-label">Actions &amp; Info</span>
        </div>
    </div>

    {{-- ── Body ── --}}
    <div class="pst-card-body">
        @if(!$branchId)
            <div class="pst-placeholder-wrap">
                <i class="ri-store-2-line"></i>
                <h5>No Branch Selected</h5>
                <p style="font-size:13px;">Select a branch from the bar above to view actions and info.</p>
            </div>
        @else
        <div class="ai-content-row">

            {{-- Left: stats table --}}
            <div class="ai-left-col">
                <div class="ai-card stats-card">
                    <div class="ai-card-title"><i class="ri-bar-chart-grouped-line"></i> Stocktaking Summary</div>
                    <div class="stats-table-wrap">
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th><i class="ri-information-line stats-icon"></i>Description</th>
                                <th class="c">Value</th>
                                <th class="c">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>Products counted</td><td class="c">{{ $totalCount }}</td><td class="c">100.00</td></tr>
                            <tr><td>Products with no anomalies</td><td class="c">{{ $zeroCount }}</td><td class="c">{{ $zeroPercentage }}</td></tr>
                            <tr><td>Products with overages</td><td class="c">{{ $positiveCount }}</td><td class="c">{{ $positivePercentage }}</td></tr>
                            <tr><td>Overage value</td><td class="c">{{ number_format($positiveValue, 2) }}</td><td class="c">—</td></tr>
                            <tr><td>Products with shortages</td><td class="c">{{ $negativeCount }}</td><td class="c">{{ $negativePercentage }}</td></tr>
                            <tr><td>Shortage value</td><td class="c">{{ number_format($negativeValue, 2) }}</td><td class="c">—</td></tr>
                            <tr><td>Expected value (EV)</td><td class="c">{{ number_format($expectedValue, 2) }}</td><td class="c">—</td></tr>
                            <tr><td>Found value (FV)</td><td class="c">{{ number_format($foundValue, 2) }}</td><td class="c">—</td></tr>
                            <tr class="total-row"><td>Difference (FV − EV)</td><td class="c">{{ number_format($differenceValue, 2) }}</td><td class="c">—</td></tr>
                        </tbody>
                    </table>
                    </div>
                </div>

                <div class="ai-card">
                    <div class="ai-card-title"><i class="ri-file-text-line"></i> Auditor Remarks</div>
                    <div class="ai-remarks-preview {{ empty($summary->remarks ?? null) ? 'empty' : '' }}" id="pstRemarksPreview">{{ $summary->remarks ?? 'No remarks yet.' }}</div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="pstEditRemarksBtn">
                        <i class="ri-edit-line"></i> Edit Remarks
                    </button>
                </div>

                <div class="ai-card">
                    <div class="ai-card-title"><i class="ri-shopping-cart-2-line"></i> Sales Since Count</div>
                    <div style="font-size:12px;color:#64748b;margin-bottom:10px;">
                        Every sale recorded against a counted product since ITS OWN count — anchored on the frozen checkpoint, so this stays correct even if Expected was hand-edited afterwards on Stocktaking Data.
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="pstSalesSinceBtn">
                        <i class="ri-list-check-2"></i> View Sales Since Count
                    </button>
                </div>

            </div>

            {{-- Right: download + sync + rectify --}}
            <div class="ai-right-col">

                <div class="ai-card">
                    <div class="ai-card-title"><i class="ri-download-2-line"></i> Download Report</div>
                    <button type="button" class="ai-dl-btn {{ $totalCount === 0 ? 'disabled' : '' }}" id="downloadReportBtn" {{ $totalCount === 0 ? 'disabled' : '' }}>
                        <i class="ri-file-pdf-2-line"></i>
                        <span>
                            <span class="dl-label">Stocktaking Report</span>
                            <span class="dl-sub">Full summary with remarks</span>
                        </span>
                    </button>
                </div>

                <div class="ai-sync-card" id="syncGateCard">
                    <div class="ai-sync-title"><i class="ri-wifi-line"></i> Device Sync Status</div>
                    <ul class="device-list" id="deviceList">
                        <li style="color:#94a3b8;font-size:12px;font-style:italic;">
                            <span>Click below to check sync status</span>
                        </li>
                    </ul>
                    <button id="syncCheckBtn" onclick="checkSyncStatus()">
                        <i class="ri-refresh-line"></i> Check Sync Status
                    </button>
                </div>

                @if($isRectified)
                    <div class="ai-rect-card locked" id="rectifyCard">
                        <div class="ai-rect-title"><i class="ri-shield-check-line" style="color:#16a34a;"></i> Already Rectified</div>
                        <div class="ai-rect-body">
                            Rectification for <strong>{{ $branchName }}</strong> on <strong>{{ $displayDate }}</strong> is complete and can't be run again. Unlike Full Stocktaking, this record isn't fully locked — Stocktaking Data edits and sales still keep pushing to live stock — but the rectification step itself is done. Need to change the note? Use the Edit Remarks button under the Summary panel.
                        </div>
                    </div>
                @else
                    <div class="ai-rect-card" id="rectifyCard">
                        <div class="ai-rect-title">
                            <i class="ri-lock-line"></i> Rectification
                        </div>
                        <div class="ai-rect-body">
                            Freezes a summary record for <strong>{{ $branchName }}</strong> on <strong>{{ $displayDate }}</strong> and locks the counting tab for this date. Expected stays exactly as entered — sales made after each product's count are netted off separately as part of this step, and the itemised list is always available above under Sales Since Count.
                        </div>
                        <a href="#" class="btn btn-success btn-sm w-100" id="rectifyBtn">
                            <i class="ri-arrow-right-line me-1"></i> Proceed to Rectification
                        </a>
                    </div>
                @endif

            </div>
        </div>
        @endif
    </div>

</div>{{-- /.pst-card --}}
</div></div></div>

{{-- ══ EDIT REMARKS MODAL ══ --}}
@if($branchId)
<div class="modal fade" id="pstRemarksModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title"><i class="ri-file-text-line"></i> Auditor Remarks</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <p style="font-size:12px;color:#64748b;margin-bottom:10px;">
                    Free text for the closing auditor — prints on the report. Can be added or changed any time, whether or not this date has been rectified yet.
                </p>
                <textarea id="pstRemarksModalBox" placeholder="Notes for the report — variance explanations, follow-up actions, anything the auditor wants recorded...">{{ $summary->remarks ?? '' }}</textarea>
                <div id="pstRemarksError" style="display:none;margin-top:10px;" class="alert alert-danger border-0 py-2 px-3"></div>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="pstRemarksSaveBtn"><i class="ri-save-line"></i> Save Remarks</button>
            </div>
        </div>
    </div>
</div>
@endif


{{-- ══ SALES SINCE COUNT MODAL ══ --}}
@if($branchId)
<div class="modal fade" id="pstSalesSinceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title"><i class="ri-shopping-cart-2-line"></i> Sales Since Count</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;max-height:65vh;overflow-y:auto;">
                <p style="font-size:12px;color:#64748b;margin-bottom:12px;">
                    Every sale recorded on a counted product since its own frozen checkpoint, oldest first. This is keyed off the checkpoint captured the moment each product was first counted — never off Expected — so it's correct regardless of any manual edit made afterwards.
                </p>
                <div id="pstSalesSinceLoading" style="text-align:center;padding:30px 0;color:#94a3b8;">
                    <i class="ri-loader-4-line" style="font-size:26px;animation:spin 1s linear infinite;"></i>
                    <div style="font-size:12px;margin-top:6px;">Loading...</div>
                </div>
                <div id="pstSalesSinceEmpty" style="display:none;text-align:center;padding:30px 0;color:#94a3b8;">
                    <i class="ri-checkbox-circle-line" style="font-size:32px;color:#c8d0ed;"></i>
                    <div style="font-size:13px;margin-top:8px;">No sales recorded since any product was counted.</div>
                </div>
                <div id="pstSalesSinceError" style="display:none;" class="alert alert-danger border-0 py-2 px-3" style="font-size:12px;"></div>
                <div id="pstSalesSinceContent" style="display:none;">
                    <table class="table table-sm" style="font-size:12.5px;">
                        <thead>
                            <tr style="background:#e2e2e9;">
                                <th>Product</th>
                                <th class="text-center">Expected (fixed)</th>
                                <th class="text-center">Found</th>
                                <th class="text-center">Sold Since Count</th>
                                <th>Sale Detail (oldest first)</th>
                            </tr>
                        </thead>
                        <tbody id="pstSalesSinceBody"></tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="3" class="text-end"><strong>Total sold since count</strong></td>
                                <td class="text-center" colspan="2"><strong id="pstSalesSinceGrandTotal">0</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endif


{{-- ══ NAV ACTIONS MODAL ══ --}}
<div class="modal fade" id="pstNavActionsModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:360px;">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title">
                    <i class="ri-layout-grid-line"></i> Quick Actions
                </h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:12px 16px;display:flex;flex-direction:column;gap:8px;">
                <a href="{{ route('retail.operations.partialstocktaking') }}" class="pst-action-link">
                    <i class="ri-scales-3-line fal-icon" style="color:#4B5EBD;"></i>
                    <div><div class="fal-title">Stocktaking</div><div class="fal-sub">Count products live for this branch and date</div></div>
                    <i class="ri-arrow-right-s-line fal-arrow"></i>
                </a>
                <a href="{{ route('retail.operations.partialstocktaking.data') }}" class="pst-action-link">
                    <i class="ri-stack-line fal-icon" style="color:#4B5EBD;"></i>
                    <div><div class="fal-title">Stocktaking Data</div><div class="fal-sub">View &amp; edit live counted records</div></div>
                    <i class="ri-arrow-right-s-line fal-arrow"></i>
                </a>
            </div>
        </div>
    </div>
</div>


{{-- ══ STATS MODAL ══ --}}
@if($branchId)
<div class="modal fade" id="pstStatsModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:400px;">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title">
                    <i class="ri-bar-chart-2-line"></i> Stocktaking Summary
                </h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                @php
                    $statRows = [
                        ['Branch',                  $branchName],
                        ['Date',                    $displayDate],
                        ['Products counted',        $totalCount],
                        ['No anomalies',            $zeroCount . ' (' . $zeroPercentage . '%)'],
                        ['Overages',                $positiveCount . ' — MWK ' . number_format($positiveValue, 2)],
                        ['Shortages',               $negativeCount . ' — MWK ' . number_format($negativeValue, 2)],
                        ['Expected value (EV)',     'MWK ' . number_format($expectedValue, 2)],
                        ['Found value (FV)',        'MWK ' . number_format($foundValue, 2)],
                        ['Difference (FV − EV)',    'MWK ' . number_format($differenceValue, 2)],
                    ];
                @endphp
                @foreach($statRows as $r)
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:13px;">
                    <span style="color:#64748b;">{{ $r[0] }}</span>
                    <span style="font-weight:700;color:#1e293b;">{{ $r[1] }}</span>
                </div>
                @endforeach
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endif


{{-- ══ RECTIFY MODAL ══ --}}
@if($branchId)
<div class="modal fade" id="rectifyModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-danger">
                <h5 class="modal-title mh-title">
                    <i class="ri-lock-line"></i> Confirm Rectification
                </h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <div id="rectifyFormArea">
                    <p style="font-size:13px;color:#475569;margin-bottom:12px;">
                        This freezes a summary for <strong>{{ $branchName }}</strong> on <strong>{{ $displayDate }}</strong>,
                        locks counting for this date, and finalizes stock for every counted product — including creating
                        branch product records for anything newly found, so nothing counted is ever lost. Rows are
                        processed one at a time, so a single bad row never blocks the rest. Expected is never touched by
                        this step; sales made after each product's count are netted off separately, once, as part of
                        finalizing that row — see Sales Since Count for the itemised list. This action is
                        <strong>one-time and can't be re-run</strong> — remarks can still be edited afterwards from the
                        Auditor Remarks card.
                    </p>
                    <div id="rectifyPendingWarning" class="alert alert-warning border-0 py-2 px-3 mb-3" style="font-size:11.5px;border-radius:6px;display:none;">
                        <i class="ri-error-warning-line me-1"></i>
                        <span id="rectifyPendingText"></span>
                        <label class="d-flex align-items-center gap-2 mt-2" style="font-size:11.5px;">
                            <input type="checkbox" id="rectifyForceCheck"> Rectify anyway (force)
                        </label>
                    </div>
                    <label class="form-label fw-semibold" style="font-size:12px;">Enter your password to confirm</label>
                    <input type="password" class="form-control" id="rectifyPassword" placeholder="Password" autocomplete="off">
                    <div id="rectifyError" style="display:none;margin-top:10px;" class="alert alert-danger border-0 py-2 px-3"></div>
                </div>

                {{-- Progress — rows are rectified one at a time --}}
                <div class="rf-progress-wrap" id="rfProgressWrap">
                    <div class="rf-bar-track"><div class="rf-bar-fill" id="rfBarFill"></div></div>
                    <div class="rf-status-line">
                        <span class="rf-current-item" id="rfCurrentItem">Starting…</span>
                        <span id="rfCountLabel">0 / 0</span>
                    </div>
                    <div class="rf-counts">
                        <span class="rf-ok"><i class="rf-dot rf-ok"></i><span id="rfOkCount">0</span> rectified</span>
                        <span class="rf-bad"><i class="rf-dot rf-bad"></i><span id="rfBadCount">0</span> failed</span>
                    </div>
                </div>

                {{-- Final summary --}}
                <div class="rf-summary" id="rfSummary">
                    <div class="rf-summary-title" id="rfSummaryTitle">Done</div>
                    <div style="font-size:12.5px;color:#475569;" id="rfSummarySub"></div>
                    <div class="rf-fail-list" id="rfFailList" style="display:none;"></div>
                </div>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;" id="rectifyFooter">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-sm" id="rectifyConfirmBtn"><i class="ri-check-line"></i> Rectify</button>
                <button type="button" class="btn btn-outline-danger btn-sm" id="rfDownloadFailedBtn" style="display:none;"><i class="ri-file-excel-2-line"></i> Download Failed Rows</button>
                <button type="button" class="btn btn-outline-warning btn-sm" id="rfRetryFailedBtn" style="display:none;"><i class="ri-refresh-line"></i> Retry Failed</button>
                <button type="button" class="btn btn-primary btn-sm" id="rfDoneBtn" style="display:none;" data-bs-dismiss="modal">Done</button>
            </div>
        </div>
    </div>
</div>
@endif


{{-- ══ INFO MODAL ══ --}}
<div class="modal fade" id="pstInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title">
                    <i class="ri-information-line"></i> About Actions &amp; Info
                </h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;font-size:13px;line-height:1.6;">
                <ul style="padding-left:18px;margin:0;display:flex;flex-direction:column;gap:10px;">
                    <li><strong>Expected never changes on its own</strong> — the only way it moves is an explicit edit on Stocktaking Data. Viewing this page, syncing, or refreshing live stock never touches it.</li>
                    <li>Sales that happen after a product is counted are netted off exactly once, only at <strong>Rectify</strong> — see the Sales Since Count card for the itemised, oldest-first list, which stays correct even if Expected was hand-edited afterwards.</li>
                    <li><strong>Rectify</strong> freezes a summary record and locks the counting tab for this date — it's a one-time action and can't be re-run. Unlike Full Stocktaking, edits from Stocktaking Data still keep pushing to live stock afterwards.</li>
                    <li><strong>Remarks</strong> are free text for the closing auditor — they print under a dedicated section on the report. Use the <strong>Edit Remarks</strong> button to add or change them any time, independent of rectification.</li>
                    <li>Rectification refuses to run while any device still has unsynced offline edits queued, unless you tick <em>Rectify anyway (force)</em>.</li>
                    <li>Branch-specific vs base-catalog price settings are preserved: a newly found product inherits the base price rather than freezing today's price as a permanent branch override.</li>
                </ul>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


{{-- ══ DATE MODAL ══ --}}
<div class="modal fade" id="pstDateModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog" style="max-width:400px;">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title">
                    <i class="ri-calendar-event-line"></i> Stocktaking Date
                </h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <div class="date-mode-toggle">
                    <div class="dmc {{ !$isCustom ? 'active-sys' : '' }}" id="pstDmcSystem"
                         onclick="pstSetDateMode('system')">
                        <div class="dmc-label">System date</div>
                        <div class="dmc-val">{{ Carbon::today()->format('d M Y') }}</div>
                    </div>
                    <div class="dmc {{ $isCustom ? 'active-cus' : '' }}" id="pstDmcCustom"
                         onclick="pstSetDateMode('custom')">
                        <div class="dmc-label">Custom date</div>
                        <div class="dmc-val" id="pstDmcCustomVal">
                            {{ $isCustom ? $displayDate : 'Pick a date' }}
                        </div>
                    </div>
                </div>
                <form method="POST" action="{{ route('tenant.admin.update.filters') }}" id="pstDateForm">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                    <input type="hidden" name="pst_custom_date" id="pstDateFormValue" value="">
                    <div id="pstCustomDateRow" style="{{ !$isCustom ? 'display:none;' : '' }}">
                        <input type="date" class="form-control" id="pstCustomDateInput"
                               value="{{ $date }}" oninput="pstPreviewDate(this.value)">
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="ri-check-line"></i> Apply
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
'use strict';

const AI_BRANCH_ID = '{{ $branchId }}';
const AI_DATE       = '{{ $date }}';

function aiCsrf() { return document.querySelector('meta[name="csrf-token"]').getAttribute('content'); }

// ── STATS BUTTON ──────────────────────────────────────────────────────
document.getElementById('pstStatsBtn')?.addEventListener('click', function () {
    $('#pstStatsModal').modal('show');
});

// ── SYNC STATUS CHECK ──────────────────────────────────────────────────
function checkSyncStatus() {
    if (!AI_BRANCH_ID) return;

    const btn = document.getElementById('syncCheckBtn');
    if (btn) { btn.classList.add('checking'); btn.innerHTML = '<i class="ri-loader-4-line"></i> Checking...'; }

    fetch(`{{ route('retail.operations.partialstocktaking.sync-status') }}?branch_id=${AI_BRANCH_ID}&date=${AI_DATE}`)
        .then(r => r.json())
        .then(data => {
            if (btn) { btn.classList.remove('checking'); btn.innerHTML = '<i class="ri-refresh-line"></i> Re-check Sync Status'; }

            renderDeviceList(data.devices || []);

            const card = document.getElementById('syncGateCard');
            if (data.can_rectify) {
                if (card) {
                    card.classList.add('all-clear');
                    card.querySelector('.ai-sync-title').innerHTML = '<i class="ri-shield-check-line"></i> All Devices Synced';
                }
            } else {
                if (card) {
                    card.classList.remove('all-clear');
                    card.querySelector('.ai-sync-title').innerHTML = '<i class="ri-wifi-line"></i> Device Sync Status';
                }
            }
        })
        .catch(() => {
            if (btn) { btn.classList.remove('checking'); btn.innerHTML = '<i class="ri-refresh-line"></i> Check Sync Status'; }
            toastr.error('Could not reach the server. Please retry.', 'Network Error');
        });
}

function renderDeviceList(devices) {
    const list = document.getElementById('deviceList');
    if (!list) return;
    if (!devices.length) {
        list.innerHTML = '<li style="color:#94a3b8;font-size:12px;font-style:italic;"><span>No devices have reported in yet.</span></li>';
        return;
    }
    list.innerHTML = devices.map(function (d) {
        const synced = d.pending_ops_count === 0;
        return '<li>' +
            '<span><span class="device-pill ' + (d.device_type === 'pos' ? 'pos' : 'stk') + '">' + d.device_type.toUpperCase() + '</span>' +
            '<span style="margin-left:6px;font-weight:600;">' + (d.device_label || d.device_id) + '</span></span>' +
            '<span class="device-pill ' + (synced ? 'synced' : 'pending') + '">' + (synced ? 'Synced' : d.pending_ops_count + ' pending') + '</span>' +
        '</li>';
    }).join('');
}

// ── RECTIFY — start / row / finish, one row at a time (same shape as the
//    Live Counting tab's merge flow) ─────────────────────────────────────
function rfResetModalUI() {
    document.getElementById('rectifyFormArea').style.display = '';
    document.getElementById('rfProgressWrap').classList.remove('active');
    document.getElementById('rfSummary').classList.remove('active');
    const failList = document.getElementById('rfFailList');
    failList.style.display = 'none';
    failList.innerHTML = '';
    document.getElementById('rfBarFill').style.width = '0%';
    document.getElementById('rfOkCount').textContent = '0';
    document.getElementById('rfBadCount').textContent = '0';
    document.getElementById('rfCountLabel').textContent = '0 / 0';
    document.getElementById('rfCurrentItem').textContent = 'Starting…';
    document.getElementById('rfDownloadFailedBtn').style.display = 'none';
    document.getElementById('rfRetryFailedBtn').style.display = 'none';
    document.getElementById('rfDoneBtn').style.display = 'none';
    const cancelBtn = document.querySelector('#rectifyFooter .btn-secondary');
    if (cancelBtn) cancelBtn.style.display = '';
    const confirmBtn = document.getElementById('rectifyConfirmBtn');
    confirmBtn.style.display = '';
    confirmBtn.disabled = false;
    confirmBtn.innerHTML = '<i class="ri-check-line"></i> Rectify';
}

let rfRunning        = false;
let rfReloadOnHide   = false;
let rfSummaryId      = null;
let rfLastFailedRows = []; // [{id, product_name, unit, price, found, error}]

document.getElementById('rectifyBtn')?.addEventListener('click', function (e) {
    e.preventDefault();
    if (rfRunning) { $('#rectifyModal').modal('show'); return; }
    rfResetModalUI();
    document.getElementById('rectifyPassword').value = '';
    document.getElementById('rectifyForceCheck').checked = false;
    document.getElementById('rectifyPendingWarning').style.display = 'none';
    document.getElementById('rectifyError').style.display = 'none';

    fetch(`{{ route('retail.operations.partialstocktaking.sync-status') }}?branch_id=${AI_BRANCH_ID}&date=${AI_DATE}`)
        .then(r => r.json())
        .then(d => {
            if (!d.can_rectify) {
                document.getElementById('rectifyPendingText').textContent =
                    `${d.pending_devices} device(s) still have unsynced offline edits.`;
                document.getElementById('rectifyPendingWarning').style.display = 'block';
            }
        })
        .catch(() => {});

    $('#rectifyModal').modal('show');
});

$('#rectifyModal').on('hidden.bs.modal', function () {
    if (rfReloadOnHide) {
        rfReloadOnHide = false;
        location.reload();
    }
});

async function rfProcessRows(rows) {
    const total = rows.length;
    let done = 0, ok = 0, bad = 0;
    const stillFailed = [];

    for (const row of rows) {
        document.getElementById('rfCurrentItem').textContent = row.product_name || 'Product';
        document.getElementById('rfCountLabel').textContent = `${done} / ${total}`;

        try {
            const resp = await fetch('{{ route("retail.operations.partialstocktaking.rectify.row") }}', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': aiCsrf(), 'Accept': 'application/json' },
                body:    JSON.stringify({
                    summary_id: rfSummaryId,
                    branch_id:  AI_BRANCH_ID,
                    date:       AI_DATE,
                    row_id:     row.id,
                }),
            });
            const d = await resp.json();
            const result = d.result || {};

            if (result.status === 'success') {
                ok++;
            } else {
                bad++;
                stillFailed.push({
                    id: row.id, product_name: result.product_name || row.product_name,
                    unit: result.unit || row.unit || '', price: result.price ?? row.price ?? 0,
                    found: result.found ?? row.found ?? 0,
                    error: result.error || 'Could not rectify this row.',
                });
            }
        } catch (e) {
            bad++;
            stillFailed.push({
                id: row.id, product_name: row.product_name, unit: row.unit || '',
                price: row.price || 0, found: row.found || 0,
                error: 'Network error — could not reach the server.',
            });
        }

        done++;
        document.getElementById('rfOkCount').textContent = ok;
        document.getElementById('rfBadCount').textContent = bad;
        document.getElementById('rfBarFill').style.width = Math.round((done / total) * 100) + '%';
        document.getElementById('rfCountLabel').textContent = `${done} / ${total}`;
    }

    return { ok, bad, stillFailed };
}

function rfRenderFailList() {
    const failList = document.getElementById('rfFailList');
    if (rfLastFailedRows.length) {
        failList.style.display = 'block';
        failList.innerHTML = rfLastFailedRows.map(f => `<div>${$('<div>').text(f.product_name || 'Product').html()} — ${$('<div>').text(f.error).html()}</div>`).join('');
        document.getElementById('rfDownloadFailedBtn').style.display = '';
        document.getElementById('rfRetryFailedBtn').style.display = '';
    } else {
        failList.style.display = 'none';
        failList.innerHTML = '';
        document.getElementById('rfDownloadFailedBtn').style.display = 'none';
        document.getElementById('rfRetryFailedBtn').style.display = 'none';
    }
}

async function rfFinish() {
    const resp = await fetch('{{ route("retail.operations.partialstocktaking.rectify.finish") }}', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': aiCsrf(), 'Accept': 'application/json' },
        body:    JSON.stringify({ summary_id: rfSummaryId, branch_id: AI_BRANCH_ID, date: AI_DATE }),
    });
    return resp.json();
}

document.getElementById('rectifyConfirmBtn')?.addEventListener('click', async function () {
    if (rfRunning) return;
    const password = document.getElementById('rectifyPassword').value;
    const errorBox  = document.getElementById('rectifyError');
    if (!password) {
        errorBox.textContent = 'Please enter your password.';
        errorBox.style.display = 'block';
        return;
    }
    errorBox.style.display = 'none';

    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="ri-loader-4-line"></i> Starting...';

    let startResp;
    try {
        startResp = await fetch('{{ route("retail.operations.partialstocktaking.rectify.start") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': aiCsrf(), 'Accept': 'application/json' },
            body: JSON.stringify({
                branch_id: AI_BRANCH_ID,
                date:      AI_DATE,
                password:  password,
                force:     document.getElementById('rectifyForceCheck').checked,
            }),
        });
    } catch (e) {
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-check-line"></i> Rectify';
        toastr.error('Could not reach the server.', 'Network Error');
        return;
    }

    const sd = await startResp.json();

    if (startResp.status !== 200) {
        btn.disabled = false;
        btn.innerHTML = '<i class="ri-check-line"></i> Rectify';
        if (startResp.status === 401) {
            errorBox.textContent = sd.error || 'The password you entered is incorrect.';
        } else if (startResp.status === 409) {
            errorBox.textContent = sd.error || 'This date has already been rectified.';
            setTimeout(() => location.reload(), 1200);
        } else if (startResp.status === 423) {
            errorBox.textContent = sd.error || 'Locked / pending sync.';
        } else {
            errorBox.textContent = sd.error || 'Rectification failed.';
        }
        errorBox.style.display = 'block';
        return;
    }

    rfRunning   = true;
    rfSummaryId = sd.summary_id;

    // Switch into progress mode.
    document.getElementById('rectifyFormArea').style.display = 'none';
    document.getElementById('rfProgressWrap').classList.add('active');
    const cancelBtn = document.querySelector('#rectifyFooter .btn-secondary');
    if (cancelBtn) cancelBtn.style.display = 'none';
    btn.style.display = 'none';

    const { ok, bad, stillFailed } = await rfProcessRows(sd.rows || []);
    rfLastFailedRows = stillFailed;

    const finishData = await rfFinish();

    rfRunning = false;
    document.getElementById('rfProgressWrap').classList.remove('active');
    document.getElementById('rfSummary').classList.add('active');

    if (bad === 0) {
        document.getElementById('rfSummaryTitle').textContent = 'All done';
        document.getElementById('rfSummarySub').textContent = ok > 0
            ? `${ok} product(s) rectified — live stock updated.`
            : 'Nothing left to rectify — summary finalized.';
        toastr.success(finishData.success || 'Rectified successfully.', 'Rectified');
    } else {
        document.getElementById('rfSummaryTitle').textContent = 'Finished with some failures';
        document.getElementById('rfSummarySub').textContent =
            `${ok} succeeded, ${bad} failed. The summary has still been finalized — retry the failed rows below, or download them.`;
        toastr.warning(`${ok} rectified, ${bad} failed.`, 'Rectification Complete');
    }

    rfRenderFailList();
    document.getElementById('rfDoneBtn').style.display = '';
    rfReloadOnHide = true; // reload either way — summary status/stats changed
});

document.getElementById('rfRetryFailedBtn')?.addEventListener('click', async function () {
    if (!rfLastFailedRows.length || rfRunning) return;
    rfRunning = true;
    this.disabled = true;

    document.getElementById('rfSummary').classList.remove('active');
    document.getElementById('rfProgressWrap').classList.add('active');
    document.getElementById('rfBarFill').style.width = '0%';
    document.getElementById('rfOkCount').textContent = '0';
    document.getElementById('rfBadCount').textContent = '0';

    const rowsToRetry = rfLastFailedRows.map(f => ({ id: f.id, product_name: f.product_name, unit: f.unit, price: f.price, found: f.found }));
    const { ok, bad, stillFailed } = await rfProcessRows(rowsToRetry);
    rfLastFailedRows = stillFailed;

    const finishData = await rfFinish();

    rfRunning = false;
    this.disabled = false;
    document.getElementById('rfProgressWrap').classList.remove('active');
    document.getElementById('rfSummary').classList.add('active');

    if (bad === 0) {
        document.getElementById('rfSummaryTitle').textContent = 'All done';
        document.getElementById('rfSummarySub').textContent = `${ok} more product(s) rectified — everything is now finalized.`;
        toastr.success(finishData.success || 'Rectified successfully.', 'Rectified');
    } else {
        document.getElementById('rfSummaryTitle').textContent = 'Still some failures';
        document.getElementById('rfSummarySub').textContent = `${ok} succeeded, ${bad} still failing. Retry again, or download the list.`;
        toastr.warning(`${ok} rectified, ${bad} still failed.`, 'Retry Complete');
    }

    rfRenderFailList();
});

document.getElementById('rfDownloadFailedBtn')?.addEventListener('click', function () {
    if (!rfLastFailedRows.length) { toastr.error('Nothing to export.'); return; }
    if (typeof XLSX === 'undefined') { toastr.error('Excel export library did not load — check your connection and try again.'); return; }

    const rows = rfLastFailedRows.map(f => ({
        Product: f.product_name,
        Unit:    f.unit,
        Price:   f.price,
        Found:   f.found,
        Error:   f.error,
    }));
    const ws = XLSX.utils.json_to_sheet(rows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Failed Rows');
    XLSX.writeFile(wb, `partial-stocktaking-rectify-failed_${AI_BRANCH_ID}_${AI_DATE}.xlsx`);
});

// ── EDIT REMARKS ─────────────────────────────────────────────────────────
document.getElementById('pstEditRemarksBtn')?.addEventListener('click', function () {
    document.getElementById('pstRemarksError').style.display = 'none';
    $('#pstRemarksModal').modal('show');
});

document.getElementById('pstRemarksSaveBtn')?.addEventListener('click', function () {
    const btn = this;
    const errorBox = document.getElementById('pstRemarksError');
    errorBox.style.display = 'none';
    btn.disabled = true;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="ri-loader-4-line"></i> Saving...';

    const value = document.getElementById('pstRemarksModalBox').value;

    fetch('{{ route("retail.operations.partialstocktaking.remarks") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': aiCsrf(), 'Accept': 'application/json' },
        body: JSON.stringify({ branch_id: AI_BRANCH_ID, date: AI_DATE, remarks: value }),
    })
    .then(r => r.json().then(d => ({ status: r.status, d })))
    .then(({ status, d }) => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        if (status === 200) {
            const preview = document.getElementById('pstRemarksPreview');
            preview.textContent = value.trim() ? value : 'No remarks yet.';
            preview.classList.toggle('empty', !value.trim());
            $('#pstRemarksModal').modal('hide');
            toastr.success('Remarks saved.', 'Saved');
        } else {
            errorBox.textContent = d.error || 'Could not save remarks.';
            errorBox.style.display = 'block';
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        errorBox.textContent = 'Could not reach the server.';
        errorBox.style.display = 'block';
    });
});

// ── DOWNLOADS ─────────────────────────────────────────────────────────────
document.getElementById('downloadReportBtn')?.addEventListener('click', function () {
    if (this.disabled) return;
    const f = document.createElement('form');
    f.method = 'POST'; f.action = '{{ route("retail.operations.partialstocktaking.report") }}';
    f.innerHTML = `@csrf <input type="hidden" name="branch_id" value="${AI_BRANCH_ID}"><input type="hidden" name="date" value="${AI_DATE}">`;
    document.body.appendChild(f); f.submit();
});

// ── DATE MODAL ────────────────────────────────────────────────────────────
function pstSetDateMode(mode) {
    document.getElementById('pstDmcSystem').classList.toggle('active-sys', mode === 'system');
    document.getElementById('pstDmcCustom').classList.toggle('active-cus', mode === 'custom');
    document.getElementById('pstCustomDateRow').style.display = mode === 'custom' ? '' : 'none';
    document.getElementById('pstDateFormValue').value = mode === 'system' ? '' : document.getElementById('pstCustomDateInput').value;
}
function pstPreviewDate(val) {
    if (!val) return;
    document.getElementById('pstDateFormValue').value = val;
    const d  = new Date(val + 'T00:00:00');
    const mo = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    document.getElementById('pstDmcCustomVal').textContent = d.getDate() + ' ' + mo[d.getMonth()] + ' ' + d.getFullYear();
}
document.getElementById('pstDateChip')?.addEventListener('click', () => {
    document.getElementById('pstDateFormValue').value = '{{ $isCustom ? $date : "" }}';
    $('#pstDateModal').modal('show');
});

@if(Session::has('message'))
toastr['{{ Session::get("alert-type","info") }}']('{{ Session::get("message") }}');
@endif

/* ══ SALES SINCE COUNT ══
   Read-only viewer for salesSinceCount() — every sale recorded against a
   counted product since ITS OWN frozen sales_id_at_count checkpoint,
   oldest first. Fetched fresh every time the modal opens so it always
   reflects the latest sales, but it never writes anything back. */
function pstSSEsc(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function pstSSFmt(n) {
    return (n === null || n === undefined || n === '') ? '0' : parseFloat(n).toLocaleString('en-US', { maximumFractionDigits: 2 });
}
function pstSSFmtDate(iso) {
    if (!iso) return '';
    const d = new Date(iso.replace(' ', 'T'));
    if (isNaN(d.getTime())) return iso;
    const mo = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return d.getDate() + ' ' + mo[d.getMonth()] + ', ' + d.toTimeString().slice(0, 5);
}

document.getElementById('pstSalesSinceBtn')?.addEventListener('click', function () {
    $('#pstSalesSinceModal').modal('show');

    const loading = document.getElementById('pstSalesSinceLoading');
    const empty   = document.getElementById('pstSalesSinceEmpty');
    const errBox  = document.getElementById('pstSalesSinceError');
    const content = document.getElementById('pstSalesSinceContent');
    const body    = document.getElementById('pstSalesSinceBody');

    loading.style.display = 'block';
    empty.style.display   = 'none';
    errBox.style.display  = 'none';
    content.style.display = 'none';
    body.innerHTML = '';

    fetch(`{{ route('retail.operations.partialstocktaking.sales-since-count') }}?branch_id=${AI_BRANCH_ID}&date=${AI_DATE}`)
        .then(res => res.json().then(data => ({ status: res.status, data })))
        .then(({ status, data }) => {
            loading.style.display = 'none';

            if (status !== 200) {
                errBox.textContent = (data && data.message) || 'Could not load sales since count.';
                errBox.style.display = 'block';
                return;
            }

            if (!data.products || !data.products.length) {
                empty.style.display = 'block';
                return;
            }

            body.innerHTML = data.products.map(p => {
                const saleLines = p.sales.map(s =>
                    `${pstSSFmtDate(s.created_at)} — ${pstSSFmt(s.quantity)}`
                ).join('<br>');

                return `<tr>
                    <td>${pstSSEsc(p.product_name)} <span style="color:#94a3b8;">(${pstSSEsc(p.unit)})</span></td>
                    <td class="text-center">${pstSSFmt(p.expected_at_count)}</td>
                    <td class="text-center">${pstSSFmt(p.found)}</td>
                    <td class="text-center"><strong>${pstSSFmt(p.qty_sold_since_count)}</strong></td>
                    <td style="font-size:11.5px;color:#475569;">${saleLines}</td>
                </tr>`;
            }).join('');

            document.getElementById('pstSalesSinceGrandTotal').textContent = pstSSFmt(data.grand_total_qty);
            content.style.display = 'block';
        })
        .catch(() => {
            loading.style.display = 'none';
            errBox.textContent = 'Could not reach the server.';
            errBox.style.display = 'block';
        });
});
</script>
@endsection