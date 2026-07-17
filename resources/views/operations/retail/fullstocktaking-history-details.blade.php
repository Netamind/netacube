@extends('operations.retail.dashboard')
@section('content')

@php
    use Carbon\Carbon;

    $branchId = request()->query('branch_id');
    $date     = request()->query('date');

    if (! $branchId || ! $date) {
        abort(404, 'Branch and date are required.');
    }

    $displayDate = Carbon::parse($date)->format('d M Y');

    $branches = DB::connection('tenant')->table('branches')
        ->where('sector', 'Retail')
        ->where('status', 'active')
        ->orderBy('name')
        ->get();

    $branchName = DB::connection('tenant')->table('branches')
        ->where('id', $branchId)
        ->value('name') ?? 'Unknown Branch';

    // Pull the summary record (must exist — this is a history page)
    $summary = DB::connection('tenant')->table('retail_fullstocktaking_summary as s')
        ->leftJoin('users as u', 'u.id', '=', 's.rectified_by_user_id')
        ->select('s.*', 'u.name as rectified_by_name')
        ->where('s.branch_id', $branchId)
        ->where('s.date', $date)
        ->where('s.status', 'completed')
        ->first();

    if (! $summary) {
        abort(404, 'No completed stocktaking record found for this branch and date.');
    }

    // Pull the detail rows from the live tables for product-level info
    $counted = DB::connection('tenant')->table('retail_fullstocktaking')
        ->where('branch_id', $branchId)
        ->where('date', $date)
        ->orderBy('product_name')
        ->get();

    // Derive counted base_product_ids so we can exclude them from missing.
    // Missing = products that were NEVER counted for this branch+date.
    // The seeding guard ($alreadySeeded) is one-time-only, so counting
    // that happened after seeding can leave duplicates in the missing table.
    // This whereNotIn makes the history view self-correcting regardless.
    $countedBaseIds = $counted->pluck('base_product_id')->filter()->values()->toArray();

    $missing = DB::connection('tenant')->table('retail_fullstocktaking_missing_products')
        ->where('branch_id', $branchId)
        ->where('date', $date)
        ->when(! empty($countedBaseIds), fn ($q) => $q->whereNotIn('base_product_id', $countedBaseIds))
        ->orderBy('product_name')
        ->get();

    // Derive stats from summary snapshot (authoritative post-rectification figures)
    $totalCount         = $summary->products_counted;
    $zeroCount          = $summary->products_no_anomaly;
    $positiveCount      = $summary->products_overage;
    $negativeCount      = $summary->products_shortage;
    $expectedValue      = $summary->expected_value;
    $foundValue         = $summary->found_value;
    $differenceValue    = $summary->difference_value;
    $missingCount       = $summary->missing_count;
    $missingValue       = $summary->missing_value;
    $fullDifference     = $summary->full_difference_value;

    $totalCountSafe     = max($totalCount, 1);
    $totalAll           = max($totalCount + $missingCount, 1);
    $zeroPercentage     = round(($zeroCount     / $totalCountSafe) * 100, 2);
    $positivePercentage = round(($positiveCount  / $totalCountSafe) * 100, 2);
    $negativePercentage = round(($negativeCount  / $totalCountSafe) * 100, 2);
    $missingPercentage  = round(($missingCount   / $totalAll)       * 100, 2);

    // Overage / shortage values — re-derive from live rows if needed
    $positiveValue = $counted->filter(fn ($r) => $r->found > $r->expected_at_count + 0.0001)
        ->sum(fn ($r) => ($r->found - $r->expected_at_count) * $r->price);
    $negativeValue = $counted->filter(fn ($r) => $r->found < $r->expected_at_count - 0.0001)
        ->sum(fn ($r) => ($r->expected_at_count - $r->found) * $r->price);

    $rectifiedBy   = $summary->rectified_by_name ?? '—';
    $rectifiedAt   = $summary->updated_at ? Carbon::parse($summary->updated_at)->format('d M Y, H:i') : '—';
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
/* ═══════════════════════════════════════════════════════════════
   HISTORY DETAILS — mirrors Actions & Info layout exactly
═══════════════════════════════════════════════════════════════ */

.content-page > .content > .container-fluid { padding-top: 16px; }

/* ── Outer card ── */
.fst-card {
    border: none; box-shadow: none; border-radius: 0; overflow: hidden;
    display: flex; flex-direction: column; background-color: transparent;
    height: auto;
}

/* ── Silver header bar ── */
.fst-card-header {
    padding: 4px 10px !important;
    background-color: silver;
    color: #666666;
    display: flex; align-items: center; justify-content: space-between;
    flex: 0 0 auto; gap: 8px;
}

.fst-hdr-left  { display: flex; align-items: center; gap: 8px; min-width: 0; }
.fst-hdr-actions { display: flex; align-items: center; gap: 2px; flex-shrink: 0; }

.fst-hdr-btn {
    height: 24px; width: 24px; border: none; background: none;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 0; color: #666666; font-size: 16px;
    cursor: pointer; position: relative; padding: 1px; text-decoration: none;
}
.fst-hdr-btn:hover { color: #333333; }
.fst-hdr-divider { width: 1px; height: 16px; background: #8a8a8a; margin: 0 6px; opacity: .6; }

/* Back chip */
.fst-back-chip {
    height: 28px; padding: 0 10px; border-radius: 4px;
    border: 1px solid rgba(102,102,102,.35); background: rgba(255,255,255,.35);
    color: #555555; font-size: 12px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 5px;
    cursor: pointer; white-space: nowrap; text-decoration: none; flex-shrink: 0;
}
.fst-back-chip:hover { background: rgba(255,255,255,.6); color: #333333; }
.fst-back-chip i { font-size: 14px; }

/* Date chip (read-only in history) */
.fst-date-chip-ro {
    height: 28px; padding: 0 10px; border-radius: 4px;
    background: rgba(255,255,255,.25); border: 1px solid rgba(102,102,102,.2);
    color: #555555; font-size: 13px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 6px;
    white-space: nowrap; pointer-events: none; user-select: none;
}
.fst-date-chip-ro .fst-mode-tag {
    font-size: 9px; font-weight: 700; letter-spacing: .3px;
    padding: 2px 6px; border-radius: 8px;
    background: rgba(0,0,0,.1); color: #555555; text-transform: uppercase;
}

/* ── Blue branch bar ── */
.fst-branch-row {
    background: linear-gradient(to right, #4B5EBD, #576CC0);
    padding: 7px 10px; flex: 0 0 auto;
    display: flex; align-items: center; justify-content: space-between;
    gap: 10px; flex-wrap: nowrap;
}
.fst-branch-left {
    display: flex; flex-direction: row; align-items: center;
    gap: 10px; min-width: 0; flex: 1 1 auto;
}
.fst-branch-name {
    font-size: 16px; font-weight: 600; color: silver;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 320px;
}
.fst-rectified-tag {
    font-size: 10px; font-weight: 700; background: #d1fae5; color: #065f46;
    border-radius: 5px; padding: 3px 8px; display: inline-flex; align-items: center;
    gap: 4px; flex-shrink: 0;
}
.fst-branch-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.fst-page-label {
    font-size: 12px; font-weight: 600; color: silver;
    white-space: nowrap; letter-spacing: .2px;
}
.fst-bar-icon-btn {
    height: 30px; width: 30px; border: none; background: none;
    display: inline-flex; align-items: center; justify-content: center;
    color: silver; font-size: 17px; cursor: pointer; flex-shrink: 0;
    border-radius: 4px; padding: 0; text-decoration: none;
}
.fst-bar-icon-btn:hover { background: rgba(255,255,255,.12); }

/* ── Card body ── */
.fst-card-body {
    flex: 0 1 auto; min-height: 0; display: flex;
    flex-direction: column; padding: 0 !important; overflow: hidden;
    background-color: #fff;
    border-radius: 0 0 10px 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

/* ── Two-column content layout ── */
.ai-content-row {
    display: flex; gap: 0; align-items: stretch; padding: 0;
}
.ai-left-col {
    flex: 1 1 60%; padding: 20px 24px 24px 20px;
    border-right: 1px solid #e8eaf2;
    overflow-y: auto;
    display: flex; flex-direction: column;
}
.ai-right-col {
    flex: 0 0 38%; padding: 20px 20px 24px 20px;
    display: flex; flex-direction: column; gap: 14px;
    overflow-y: auto;
}

/* ── Right-column cards ── */
.ai-card {
    background: #f8f9fc; border: 1px solid #e4e7f5;
    border-radius: 10px; padding: 16px;
}
.ai-card-title {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .6px; color: #94a3b8; margin-bottom: 12px;
    display: flex; align-items: center; gap: 6px;
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

/* ── Download buttons ── */
.ai-dl-btn {
    display: flex; align-items: center; gap: 10px; padding: 10px 12px;
    border-radius: 8px; border: 1px solid #d8ddf0; background: #fff;
    cursor: pointer; text-decoration: none; transition: border-color 0.15s, background 0.15s;
    width: 100%; text-align: left;
}
.ai-dl-btn:hover { border-color: #4B5EBD; background: #eff3ff; }
.ai-dl-btn i { font-size: 20px; color: #4B5EBD; flex-shrink: 0; }
.ai-dl-btn .dl-label { font-size: 12.5px; font-weight: 600; color: #1e293b; display: block; line-height: 1.2; }
.ai-dl-btn .dl-sub   { font-size: 11px; color: #64748b; display: block; margin-top: 2px; }
.ai-card form + form { margin-top: 10px; }

/* ── Locked / rectified cards ── */
.ai-rect-card-locked {
    background: #f3f4f6; border: 1.5px solid #d1d5db;
    border-radius: 10px; padding: 16px;
}
.ai-rect-card-locked .ai-rect-title {
    font-size: 12px; font-weight: 700; color: #374151;
    display: flex; align-items: center; gap: 6px; margin-bottom: 8px;
}
.ai-rect-card-locked .ai-rect-body {
    font-size: 12px; color: #4b5563; line-height: 1.55;
}

/* Sync card — disabled/locked state */
.ai-sync-card-locked {
    background: #f0fdf4; border: 1.5px solid #86efac;
    border-radius: 10px; padding: 16px;
}
.ai-sync-card-locked .ai-sync-title {
    font-size: 12px; font-weight: 700; color: #166534;
    display: flex; align-items: center; gap: 6px; margin-bottom: 8px;
}
.ai-sync-card-locked .ai-sync-body {
    font-size: 12px; color: #14532d; line-height: 1.55;
}

/* Rectify button — permanently disabled */
.btn-rectify-disabled {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    width: 100%; padding: 7px 14px; border-radius: 7px;
    font-size: 12.5px; font-weight: 600;
    background: #f3f4f6; border: 1.5px solid #d1d5db; color: #9ca3af;
    cursor: not-allowed; pointer-events: none; opacity: .7;
}

/* ── Rectification meta row ── */
.rect-meta-row {
    display: flex; justify-content: space-between;
    padding: 7px 0; border-bottom: 1px solid #f1f5f9; font-size: 12px;
}
.rect-meta-row:last-child { border-bottom: none; }
.rect-meta-row .lbl { color: #64748b; }
.rect-meta-row .val { font-weight: 700; color: #1e293b; }

/* ── Product detail tabs ── */
.det-tab-bar {
    display: flex; gap: 0; border-bottom: 2px solid #e2e8f0;
    margin-bottom: 0; padding: 14px 20px 0 20px; background: #fafbff;
}
.det-tab {
    padding: 8px 18px; font-size: 12.5px; font-weight: 600; color: #64748b;
    border-bottom: 2px solid transparent; margin-bottom: -2px;
    cursor: pointer; transition: color .15s, border-color .15s;
    white-space: nowrap;
}
.det-tab:hover { color: #4B5EBD; }
.det-tab.active { color: #4B5EBD; border-bottom-color: #4B5EBD; }

.det-tab-content { display: none; }
.det-tab-content.active { display: block; }

/* ── Counted products table ── */
.cprod-table { width: 100%; font-size: 12.5px; border-collapse: collapse; }
.cprod-table th {
    color: #94a3b8; font-size: 10px; text-transform: uppercase;
    letter-spacing: .5px; font-weight: 700; padding: 8px 10px;
    border-bottom: 2px solid #e2e8f0; text-align: left; white-space: nowrap;
}
.cprod-table th.c, .cprod-table td.c { text-align: center; }
.cprod-table td { padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
.cprod-table tbody tr:hover td { background: #f8f9fc; }
.cprod-table .diff-pos { color: #059669; font-weight: 700; }
.cprod-table .diff-neg { color: #dc2626; font-weight: 700; }
.cprod-table .diff-zero { color: #64748b; }
.cprod-table .badge-pos  { font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 8px; background: #dcfce7; color: #166534; }
.cprod-table .badge-neg  { font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 8px; background: #fee2e2; color: #991b1b; }
.cprod-table .badge-zero { font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 8px; background: #f1f5f9; color: #64748b; }

/* ── DataTable alignment (Counted / Missing tables) ── */
#countedTable thead th, #missingTable thead th,
table.dataTable thead th { text-align: center !important; vertical-align: middle !important; }
#countedTable thead th:first-child, #missingTable thead th:first-child,
table.dataTable thead th:first-child { text-align: left !important; }
#countedTable tbody td, #missingTable tbody td,
table.dataTable tbody td { text-align: center !important; vertical-align: middle !important; }
#countedTable tbody td:first-child, #missingTable tbody td:first-child,
table.dataTable tbody td:first-child { text-align: left !important; }

/* ── Fixed column background (keeps sticky first column opaque while scrolling) ── */
table.dataTable.fixedColumns .DTFC_LeftBodyLiner,
table.dataTable.fixedColumns .DTFC_LeftHeadWrapper { background: #fff; }

.missing-note {
    margin: 0; padding: 10px 14px; font-size: 12px; color: #92400e;
    background: #fffbeb; border-bottom: 1px solid #fde68a;
    display: flex; align-items: center; gap: 6px;
}

/* ── Modal headers ── */
.mh-pos       { background: linear-gradient(to right, #4B5EBD, #576CC0); padding: 10px 16px !important; border-bottom: none; }
.mh-pos-title { color: #fff; font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
.mh-blue      { background: linear-gradient(135deg,#4B5EBD,#576CC0); padding: 12px 18px !important; border-bottom: none; }
.mh-title     { color: #fff; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.mh-close-w   { filter: brightness(0) invert(1); opacity: .8; }
.mh-close-w:hover { opacity: 1; }

/* ── Mobile ── */
@media (max-width: 768px) {
    .fst-card { height: auto !important; margin: 8px; }
    .content-page { padding: 0 !important; }
    .content { padding: 0 !important; }
    .content-page > .content > .container-fluid { padding-top: 0 !important; padding-left: 0 !important; padding-right: 0 !important; }
    .ai-content-row { flex-direction: column; }
    .ai-left-col { border-right: none; border-bottom: 1px solid #e8eaf2; padding-right: 20px; }
    .ai-right-col { flex: unset; }
    .det-tab { padding: 8px 12px; font-size: 12px; }
    .modal-dialog { margin: 1.25rem auto !important; max-width: calc(100% - 24px) !important; }
    .modal-content { border-radius: 10px !important; }
}
</style>

<div class="content-page"><div class="content"><div class="container-fluid">

<div class="fst-card">

    {{-- ── Silver header bar ── --}}
    <div class="fst-card-header">
        <div class="fst-hdr-left">
            <a href="{{ route('retail.operations.fullstocktaking.history') }}" class="fst-back-chip" title="Back to History">
                <i class="ri-arrow-left-line"></i> History
            </a>
            <div class="fst-date-chip-ro">
                <i class="ri-calendar-check-line"></i> {{ $displayDate }}
            </div>
        </div>
        <div class="fst-hdr-actions">
            <button type="button" class="fst-hdr-btn" id="hdStatsBtn" title="View summary stats">
                <i class="ri-bar-chart-2-line"></i>
            </button>
            <span class="fst-hdr-divider"></span>
            <button type="button" class="fst-hdr-btn" title="About this view"
                    onclick="$('#hdInfoModal').modal('show')">
                <i class="ri-information-line"></i>
            </button>
        </div>
    </div>

    {{-- ── Blue branch bar ── --}}
    <div class="fst-branch-row">
        <div class="fst-branch-left">
            <span class="fst-branch-name">{{ $branchName }}</span>
        </div>
        <div class="fst-branch-right">
            <span class="fst-page-label">Stocktaking History Details</span>
        </div>
    </div>

    {{-- ── Product detail tabs ── --}}
    <div class="det-tab-bar" id="hdTabBar">
        <div class="det-tab active" data-tab="summary">
            <i class="ri-bar-chart-grouped-line" style="margin-right:4px;"></i>Summary
        </div>
        <div class="det-tab" data-tab="counted">
            <i class="ri-checkbox-circle-line" style="margin-right:4px;"></i>
            Counted <span style="font-size:10px;color:#94a3b8;">({{ $counted->count() }})</span>
        </div>
        <div class="det-tab" data-tab="missing">
            <i class="ri-error-warning-line" style="margin-right:4px;"></i>
            Missing <span style="font-size:10px;color:#94a3b8;">({{ $missing->count() }})</span>
        </div>
    </div>

    {{-- ── Body ── --}}
    <div class="fst-card-body">

        {{-- ─── TAB: Summary ─── --}}
        <div class="det-tab-content active" id="tab-summary">
            <div class="ai-content-row">

                {{-- Left: stats --}}
                <div class="ai-left-col">
                    <div class="ai-card" style="flex:1;">
                        <div class="ai-card-title"><i class="ri-bar-chart-grouped-line"></i> Stocktaking Summary</div>
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
                                <tr><td>Difference (FV − EV)</td><td class="c">{{ number_format($differenceValue, 2) }}</td><td class="c">—</td></tr>
                                <tr><td>Missing items</td><td class="c">{{ $missingCount }}</td><td class="c">{{ $missingPercentage }}</td></tr>
                                <tr><td>Missing value</td><td class="c">{{ number_format($missingValue, 2) }}</td><td class="c">—</td></tr>
                                <tr class="total-row"><td>Full difference (FV − (EV + MV))</td><td class="c">{{ number_format($fullDifference, 2) }}</td><td class="c">—</td></tr>
                            </tbody>
                        </table>
                    </div>{{-- /.ai-card --}}
                </div>

                {{-- Right: downloads + sync (locked) + rectify (locked) --}}
                <div class="ai-right-col">

                    {{-- Downloads --}}
                    <div class="ai-card">
                        <div class="ai-card-title"><i class="ri-download-2-line"></i> Download Report</div>
                        <form action="{{ route('retail.operations.fullstocktaking.report.full') }}" method="POST">
                            @csrf
                            <input type="hidden" name="branch_id" value="{{ $branchId }}">
                            <input type="hidden" name="date" value="{{ $date }}">
                            <button type="submit" class="ai-dl-btn">
                                <i class="ri-file-chart-line"></i>
                                <span>
                                    <span class="dl-label">Full Report</span>
                                    <span class="dl-sub">All data — counts, expected, differences, missing</span>
                                </span>
                            </button>
                        </form>
                        <form action="{{ route('retail.operations.fullstocktaking.report.delivery') }}" method="POST">
                            @csrf
                            <input type="hidden" name="branch_id" value="{{ $branchId }}">
                            <input type="hidden" name="date" value="{{ $date }}">
                            <button type="submit" class="ai-dl-btn">
                                <i class="ri-file-list-3-line"></i>
                                <span>
                                    <span class="dl-label">Stock Delivery Note</span>
                                    <span class="dl-sub">Product · Unit · Price · Qty</span>
                                </span>
                            </button>
                        </form>
                    </div>

                    {{-- Rectification meta --}}
                    <div class="ai-card">
                        <div class="ai-card-title"><i class="ri-shield-check-line"></i> Rectification Record</div>
                        <div class="rect-meta-row">
                            <span class="lbl">Rectified by</span>
                            <span class="val">{{ $rectifiedBy }}</span>
                        </div>
                        <div class="rect-meta-row">
                            <span class="lbl">Rectified on</span>
                            <span class="val">{{ $rectifiedAt }}</span>
                        </div>
                        <div class="rect-meta-row">
                            <span class="lbl">Branch</span>
                            <span class="val">{{ $branchName }}</span>
                        </div>
                        <div class="rect-meta-row">
                            <span class="lbl">Date</span>
                            <span class="val">{{ $displayDate }}</span>
                        </div>
                    </div>

                    {{-- Sync gate — locked --}}
                    <div class="ai-sync-card-locked">
                        <div class="ai-sync-title">
                            <i class="ri-shield-check-line"></i> Device Sync — Locked
                        </div>
                        <div class="ai-sync-body">
                            This stocktaking session has been rectified and is permanently locked. Device sync checking is no longer applicable.
                        </div>
                    </div>

                    {{-- Rectify button — permanently disabled --}}
                    <div class="ai-rect-card-locked">
                        <div class="ai-rect-title">
                            <i class="ri-lock-fill" style="color:#16a34a;"></i> Already Rectified
                        </div>
                        <div class="ai-rect-body" style="margin-bottom:12px;">
                            This date has been permanently rectified for <strong>{{ $branchName }}</strong>. Live stock was overwritten with the final counted figures on <strong>{{ $rectifiedAt }}</strong>. This record is read-only.
                        </div>
                        <div class="btn-rectify-disabled">
                            <i class="ri-lock-line"></i> Rectification Locked
                        </div>
                    </div>

                </div>
            </div>
        </div>

        {{-- ─── TAB: Counted Products ─── --}}
        <div class="det-tab-content" id="tab-counted">
            <div style="padding:16px 20px 20px 20px;">
                @if($counted->isEmpty())
                    <div style="padding:40px;text-align:center;color:#94a3b8;">
                        <i class="ri-inbox-line" style="font-size:40px;display:block;margin-bottom:10px;"></i>
                        No counted products found for this session.
                    </div>
                @else
                    <table class="cprod-table table table-sm w-100" id="countedTable">
                        <thead style="background:#e2e2e9;">
                            <tr>
                                <th>Product</th>
                                <th>Unit</th>
                                <th class="c">Expected</th>
                                <th class="c">Found</th>
                                <th class="c">Difference</th>
                                <th class="c">Status</th>
                                <th class="c">Price</th>
                                <th class="c">Diff Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($counted as $i => $row)
                            @php
                                $diff      = $row->found - $row->expected_at_count;
                                $diffValue = $diff * $row->price;
                                $status    = abs($diff) < 0.0001 ? 'none' : ($diff > 0 ? 'over' : 'short');
                            @endphp
                            <tr>
                                <td style="font-weight:600;">{{ $row->product_name }}</td>
                                <td style="color:#64748b;font-size:12px;">{{ $row->unit }}</td>
                                <td class="c">{{ number_format($row->expected_at_count, 2) }}</td>
                                <td class="c">{{ number_format($row->found, 2) }}</td>
                                <td class="c {{ $status === 'over' ? 'diff-pos' : ($status === 'short' ? 'diff-neg' : 'diff-zero') }}">
                                    {{ $status === 'none' ? '—' : ($diff > 0 ? '+' : '') . number_format($diff, 2) }}
                                </td>
                                <td class="c">
                                    @if($status === 'none')
                                        <span class="badge-zero">OK</span>
                                    @elseif($status === 'over')
                                        <span class="badge-pos">Overage</span>
                                    @else
                                        <span class="badge-neg">Shortage</span>
                                    @endif
                                </td>
                                <td class="c" style="color:#64748b;">{{ number_format($row->price, 2) }}</td>
                                <td class="c {{ $status === 'over' ? 'diff-pos' : ($status === 'short' ? 'diff-neg' : 'diff-zero') }}">
                                    {{ $status === 'none' ? '—' : ($diffValue > 0 ? '+' : '') . number_format($diffValue, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- ─── TAB: Missing Products ─── --}}
        <div class="det-tab-content" id="tab-missing">
            @if($missing->isNotEmpty())
            <p class="missing-note">
                <i class="ri-error-warning-line"></i>
                These products were in branch stock but were not counted during this stocktaking session.
            </p>
            @endif
            <div style="padding:16px 20px 20px 20px;">
                @if($missing->isEmpty())
                    <div style="padding:40px;text-align:center;color:#94a3b8;">
                        <i class="ri-checkbox-circle-line" style="font-size:40px;display:block;margin-bottom:10px;color:#86efac;"></i>
                        No missing products — all branch stock was counted.
                    </div>
                @else
                    <table class="cprod-table table table-sm w-100" id="missingTable">
                        <thead style="background:#e2e2e9;">
                            <tr>
                                <th>Product</th>
                                <th>Unit</th>
                                <th class="c">Stock Qty</th>
                                <th class="c">Price</th>
                                <th class="c">Value</th>
                                <th>Batch No.</th>
                                <th>Expiry</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($missing as $i => $m)
                            <tr>
                                <td style="font-weight:600;">{{ $m->product_name }}</td>
                                <td style="color:#64748b;font-size:12px;">{{ $m->unit }}</td>
                                <td class="c">{{ number_format($m->quantity, 2) }}</td>
                                <td class="c" style="color:#64748b;">{{ number_format($m->price, 2) }}</td>
                                <td class="c" style="font-weight:700;">{{ number_format($m->quantity * $m->price, 2) }}</td>
                                <td style="color:#64748b;font-size:12px;">{{ $m->batch_number ?? '—' }}</td>
                                <td style="color:#64748b;font-size:12px;">
                                    {{ $m->expiry_date ? Carbon::parse($m->expiry_date)->format('d M Y') : '—' }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr style="background:#f4f6ff;border-top:2px solid #c5caec;">
                                <td colspan="4" style="font-weight:800;font-size:12.5px;padding:8px 10px;">Total missing value</td>
                                <td class="c" style="font-weight:800;font-size:13px;color:#dc2626;padding:8px 10px;">
                                    {{ number_format($missing->sum(fn($m) => $m->quantity * $m->price), 2) }}
                                </td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                @endif
            </div>
        </div>

    </div>{{-- /.fst-card-body --}}
</div>{{-- /.fst-card --}}

</div></div></div>


{{-- ══ STATS MODAL ══ --}}
<div class="modal fade" id="hdStatsModal" tabindex="-1">
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
                        ['Overages',                $positiveCount . ' — ' . number_format($positiveValue, 2)],
                        ['Shortages',               $negativeCount . ' — ' . number_format($negativeValue, 2)],
                        ['Expected value (EV)',     number_format($expectedValue, 2)],
                        ['Found value (FV)',        number_format($foundValue, 2)],
                        ['Difference (FV − EV)',    number_format($differenceValue, 2)],
                        ['Missing items',           $missingCount . ' (' . $missingPercentage . '%)'],
                        ['Missing value (MV)',      number_format($missingValue, 2)],
                        ['Full difference',         number_format($fullDifference, 2)],
                        ['Rectified by',            $rectifiedBy],
                        ['Rectified on',            $rectifiedAt],
                    ];
                @endphp
                @foreach($statRows as $r)
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:13px;">
                    <span style="color:#64748b;">{{ $r[0] }}</span>
                    <span style="font-weight:700;color:#1e293b;text-align:right;max-width:55%;">{{ $r[1] }}</span>
                </div>
                @endforeach
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


{{-- ══ INFO MODAL ══ --}}
<div class="modal fade" id="hdInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title">
                    <i class="ri-information-line"></i> About This View
                </h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;font-size:13px;line-height:1.6;">
                <ul style="padding-left:18px;margin:0;display:flex;flex-direction:column;gap:10px;">
                    <li>This is a <strong>read-only historical record</strong> of a completed and rectified stocktaking session.</li>
                    <li>The <strong>Summary</strong> tab shows figures from the permanent snapshot written at rectification time — these are the final, authoritative values.</li>
                    <li>The <strong>Counted</strong> tab lists every product that was physically counted, with the expected quantity at count time, the found quantity, and the resulting difference.</li>
                    <li>The <strong>Missing</strong> tab lists products that were in branch stock but were not counted during this session.</li>
                    <li><strong>Device sync and rectification are permanently disabled</strong> for historical records — all actions were completed when this session was rectified.</li>
                    <li>You can still <strong>download reports</strong> for this session at any time using the buttons in the Summary tab.</li>
                </ul>
                <div class="alert alert-info border-0 mt-3 py-2 px-3" style="font-size:12px;border-radius:6px;">
                    <i class="ri-information-line me-1"></i> If corrections were applied after rectification via the Merged Data offline sync, those are reflected in the live stock but not in this historical snapshot.
                </div>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script>
'use strict';

// ── COUNTED / MISSING DATATABLES ───────────────────────────────────────
// Both tables live inside tabs that start hidden (display:none), so each
// is initialized once and then relaid out (columns.adjust + fixedColumns
// relayout) the first time its tab is actually shown — initializing a
// DataTable on a hidden element gives it a zero-width first render.
var hdCountedTable = null;
var hdMissingTable = null;
var hdCountedReady = false;
var hdMissingReady = false;

function hdBuildTable(id) {
    var $t = $('#' + id);
    if (!$t.length) return null;
    return $t.DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        pageLength: 100,
        lengthMenu: [[50, 100, 250, -1], [50, 100, 250, 'All']],
        fixedColumns: { leftColumns: 1 },
        scrollX: true,
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: 0,      className: 'text-start'  }
        ]
    });
}

$(function () {
    if ($('#countedTable tbody tr').length) hdCountedTable = hdBuildTable('countedTable');
    if ($('#missingTable tbody tr').length) hdMissingTable = hdBuildTable('missingTable');
});

// ── TAB SWITCHING ─────────────────────────────────────────────────────
document.querySelectorAll('#hdTabBar .det-tab').forEach(function (tab) {
    tab.addEventListener('click', function () {
        var target = this.dataset.tab;

        // Update tab active state
        document.querySelectorAll('#hdTabBar .det-tab').forEach(function (t) {
            t.classList.toggle('active', t.dataset.tab === target);
        });

        // Show/hide content panels
        document.querySelectorAll('.det-tab-content').forEach(function (panel) {
            panel.classList.toggle('active', panel.id === 'tab-' + target);
        });

        // Relayout the relevant DataTable now that its container is visible
        if (target === 'counted' && hdCountedTable && !hdCountedReady) {
            hdCountedTable.columns.adjust();
            if (hdCountedTable.fixedColumns) hdCountedTable.fixedColumns().relayout();
            hdCountedReady = true;
        }
        if (target === 'missing' && hdMissingTable && !hdMissingReady) {
            hdMissingTable.columns.adjust();
            if (hdMissingTable.fixedColumns) hdMissingTable.fixedColumns().relayout();
            hdMissingReady = true;
        }
    });
});

// ── STATS MODAL ───────────────────────────────────────────────────────
document.getElementById('hdStatsBtn')?.addEventListener('click', function () {
    $('#hdStatsModal').modal('show');
});

// ── SESSION FLASH ─────────────────────────────────────────────────────
@if(Session::has('message'))
toastr['{{ Session::get("alert-type","info") }}']('{{ Session::get("message") }}');
@endif
</script>
@endsection