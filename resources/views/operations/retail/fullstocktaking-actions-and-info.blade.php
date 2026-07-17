@extends('operations.retail.dashboard')
@section('content')

@php
    use Carbon\Carbon;

    $pref = DB::connection('tenant')->table('user_filters')->where('user_id', Auth::id())->first();

    $branchId      = $pref->branch_id      ?? null;
    $fstCustomDate = $pref->fst_custom_date ?? null;
    $isCustom      = ! empty($fstCustomDate);
    $date          = $isCustom ? $fstCustomDate : Carbon::today()->toDateString();
    $displayDate   = Carbon::parse($date)->format('d M Y');

    $branches = DB::connection('tenant')->table('branches')
        ->where('sector', 'Retail')
        ->where('status', 'active')
        ->orderBy('name')
        ->get();

    $branchName = $branchId
        ? (DB::connection('tenant')->table('branches')->where('id', $branchId)->value('name') ?? 'Branch not found')
        : null;

    $isRectified = $branchId && DB::connection('tenant')
        ->table('retail_fullstocktaking_summary')
        ->where('branch_id', $branchId)
        ->where('date', $date)
        ->exists();

    $totalCount = $zeroCount = $positiveCount = $negativeCount = 0;
    $zeroPercentage = $positivePercentage = $negativePercentage = 0;
    $expectedValue = $foundValue = $positiveValue = $negativeValue = 0;
    $differenceValue = $missingCount = $missingValue = $missingPercentage = $fullDifference = 0;
    $salesAfterCounting = collect();

    if ($branchId) {
        $counted = DB::connection('tenant')->table('retail_fullstocktaking')
            ->where('branch_id', $branchId)->where('date', $date)->get();

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

        // ── 1. Which base_product_ids have already been counted for this branch+date? ──
        $countedIds = $counted->pluck('base_product_id');

        // ── 2. ALWAYS purge any missing-table row whose product has since been counted. ──
        if ($countedIds->isNotEmpty()) {
            DB::connection('tenant')->table('retail_fullstocktaking_missing_products')
                ->where('branch_id', $branchId)
                ->where('date', $date)
                ->whereIn('base_product_id', $countedIds)
                ->delete();
        }

        // ── 3. One-time seed for products that still have no missing-table row. ──
        $alreadyMissingIds = DB::connection('tenant')->table('retail_fullstocktaking_missing_products')
            ->where('branch_id', $branchId)->where('date', $date)
            ->pluck('base_product_id');

        $excludeIds = $countedIds->merge($alreadyMissingIds)->unique();

        $missingToSeed = DB::connection('tenant')
            ->table('retail_branch_products as rbp')
            ->join('retail_base_products as bp', 'bp.id', '=', 'rbp.base_product_id')
            ->where('rbp.branch_id', $branchId)
            ->whereNotIn('rbp.base_product_id', $excludeIds)
            ->select('rbp.base_product_id','bp.name as product_name','bp.unit',
                DB::raw('COALESCE(rbp.selling_price, bp.selling_price) as price'),
                'rbp.stock_quantity as quantity','rbp.batch_number','rbp.expiry_date')
            ->get();

        if ($missingToSeed->isNotEmpty()) {
            $now = now();
            $rows = $missingToSeed->map(fn ($m) => [
                'date' => $date,'branch_id' => $branchId,'base_product_id' => $m->base_product_id,
                'product_name' => $m->product_name,'unit' => $m->unit,'price' => $m->price ?? 0,
                'quantity' => $m->quantity ?? 0,'rate' => 1.00,'batch_number' => $m->batch_number,
                'expiry_date' => $m->expiry_date,'product_status' => 'Active',
                'created_at' => $now,'updated_at' => $now,
            ])->toArray();
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::connection('tenant')->table('retail_fullstocktaking_missing_products')->insertOrIgnore($chunk);
            }
        }

        // ── 4. Read the final, reconciled list. ──
        $missing      = DB::connection('tenant')->table('retail_fullstocktaking_missing_products')
            ->where('branch_id', $branchId)->where('date', $date)->get();
        $missingCount = $missing->count();
        $missingValue = $missing->sum(fn ($m) => $m->quantity * $m->price);

        $totalAll       = max($totalCount + $missingCount, 1);
        $totalCountSafe = max($totalCount, 1);
        $zeroPercentage     = round(($zeroCount / $totalCountSafe) * 100, 2);
        $positivePercentage = round(($positiveCount / $totalCountSafe) * 100, 2);
        $negativePercentage = round(($negativeCount / $totalCountSafe) * 100, 2);
        $missingPercentage  = round(($missingCount / $totalAll) * 100, 2);

        $differenceValue = $foundValue - $expectedValue;
        $fullDifference  = $differenceValue - $missingValue;

        foreach ($counted as $row) {
            $branchProductId = DB::connection('tenant')->table('retail_branch_products')
                ->where('branch_id', $branchId)->where('base_product_id', $row->base_product_id)->value('id');

            $qtySold = $branchProductId
                ? DB::connection('tenant')->table('retail_system_sales')
                    ->where('branch', (string) $branchId)
                    ->where('branch_product_id', $branchProductId)
                    ->where('id', '>', $row->sales_id_at_count ?? 0)
                    ->sum('quantity')
                : 0;

            if ($qtySold > 0.0001) {
                $salesAfterCounting->push([
                    'product' => $row->product_name,'unit' => $row->unit,
                    'qty_sold' => $qtySold,'expected_before' => $row->expected_at_count,
                    'expected_after' => max(0, $row->expected_at_count - $qtySold),
                ]);
            }
        }
        $salesAfterCounting = $salesAfterCounting->sortByDesc('qty_sold')->values();
    }

    $rectifyStartUrl  = route('retail.operations.fullstocktaking.rectify.start');
    $rectifyRowUrl    = route('retail.operations.fullstocktaking.rectify.row');
    $rectifyFinishUrl = route('retail.operations.fullstocktaking.rectify.finish');
    $syncStatusUrl    = route('retail.operations.fullstocktaking.sync-status');
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
/* ══════════════════════════════════════════════════════════════
   ACTIONS & INFO — mirrors Missing Products layout exactly
══════════════════════════════════════════════════════════════ */

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

#fstDateChip {
    height: 28px; padding: 0 8px; border-radius: 4px;
    background: none; color: #666666; border: none;
    font-weight: bold; font-size: 14px;
    display: inline-flex; align-items: center; gap: 6px; cursor: pointer;
    white-space: nowrap;
}
#fstDateChip:hover { color: #333333; }
#fstDateChip .fst-mode-tag {
    font-size: 9px; font-weight: 700; letter-spacing: .3px;
    padding: 2px 6px; border-radius: 8px;
    background: rgba(255,255,255,.35); color: #555555; text-transform: uppercase;
}
#fstDateChip.custom-mode .fst-mode-tag { background: #fcd34d; color: #7c4a03; }
#fstDateChip .fst-edit-pencil { font-size: 11px; opacity: .65; }
#fstDateChip.fst-locked { cursor: not-allowed; opacity: .75; }
#fstDateChip.fst-locked:hover { color: #666666; }

.fst-hdr-left { display: flex; align-items: center; gap: 8px; min-width: 0; }

#fstActionsBtn {
    height: 28px; padding: 0 10px; border-radius: 4px;
    border: 1px solid rgba(102,102,102,.35); background: rgba(255,255,255,.35);
    color: #555555; font-size: 12px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 5px; cursor: pointer;
    white-space: nowrap; text-decoration: none; flex-shrink: 0;
}
#fstActionsBtn i { font-size: 14px; }
#fstActionsBtn:hover { background: rgba(255,255,255,.6); color: #333333; }

.fst-hdr-actions { display: flex; align-items: center; gap: 2px; flex-shrink: 0; }
.fst-hdr-btn {
    height: 24px; width: 24px; border: none; background: none;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 0; color: #666666; font-size: 16px;
    cursor: pointer; position: relative; padding: 1px; text-decoration: none;
}
.fst-hdr-btn:hover { color: #333333; }
.fst-hdr-divider { width: 1px; height: 16px; background: #8a8a8a; margin: 0 6px; opacity: .6; }

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
#fstBranchForm { margin: 0; display: inline-flex; align-items: center; min-width: 0; }
#fstBranchSelect {
    border: none; background: transparent; color: silver;
    font-size: 16px; font-weight: 600; cursor: pointer;
    padding: 0 0 0 2px; outline: none; max-width: 280px;
}
#fstBranchSelect option { color: #1e293b; background: #fff; font-size: 14px; }
#fstBranchSelect:disabled { opacity: 1; color: silver; -webkit-text-fill-color: silver; cursor: not-allowed; }

.fst-rectified-tag {
    font-size: 10px; font-weight: 700; background: #d1fae5; color: #065f46;
    border-radius: 5px; padding: 3px 8px; display: inline-flex; align-items: center; gap: 4px; flex-shrink: 0;
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
    border-radius: 4px; padding: 0; position: relative; text-decoration: none;
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

/* ── Placeholder ── */
.fst-placeholder-wrap { padding: 48px 20px; text-align: center; color: #94a3b8; }
.fst-placeholder-wrap i { font-size: 52px; display: block; margin-bottom: 12px; color: #c8d0ed; }
.fst-placeholder-wrap h5 { color: #64748b; font-weight: 600; }

/* ── Two-column content layout ── */
.ai-content-row {
    display: flex; gap: 0; align-items: stretch;
    padding: 0;
}
.ai-left-col {
    flex: 1 1 60%; padding: 20px 24px 24px 20px;
    border-right: 1px solid #e8eaf2;
    overflow-y: auto;
    display: flex; flex-direction: column;
}
.ai-left-col .ai-card.stats-card {
    flex: 1 1 auto; display: flex; flex-direction: column;
}
.ai-left-col .ai-card.stats-card .stats-table-wrap {
    flex: 1 1 auto;
}
.ai-right-col {
    flex: 0 0 38%; padding: 20px 20px 24px 20px;
    display: flex; flex-direction: column; gap: 14px;
    overflow-y: auto;
}

/* ── Sales-after-counting link ── */
.ai-affected-line { display: flex; justify-content: flex-end; margin-bottom: 10px; }
.ai-affected-line a {
    font-size: 11px; font-weight: 700; color: #4B5EBD;
    text-decoration: underline; display: inline-flex; align-items: center; gap: 4px;
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

/* Download buttons */
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
    background: #fef2f2; border: 1.5px solid #fca5a5;
    border-radius: 10px; padding: 16px;
}
.ai-rect-card.locked       { background: #f3f4f6; border-color: #d1d5db; }
.ai-rect-card.sync-blocked { background: #fef9ec; border-color: #fcd34d; }
.ai-rect-title {
    font-size: 12px; font-weight: 700; color: #b91c1c;
    display: flex; align-items: center; gap: 6px; margin-bottom: 8px;
}
.ai-rect-card.locked .ai-rect-title       { color: #374151; }
.ai-rect-card.sync-blocked .ai-rect-title { color: #92400e; }
.ai-rect-body { font-size: 12px; color: #7f1d1d; line-height: 1.55; margin-bottom: 12px; }
.ai-rect-card.locked .ai-rect-body       { color: #4b5563; margin-bottom: 0; }
.ai-rect-card.sync-blocked .ai-rect-body { color: #78350f; }

/* Rectify progress — mirrors the merge-progress UI in
   fullstocktaking.blade.php (see fst-merge-* there) so both one-row-at-
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

/* Device history card (rectified state) */
.ai-devhist-card {
    background: #eff6ff; border: 1.5px solid #93c5fd;
    border-radius: 10px; padding: 16px;
}
.ai-devhist-title {
    font-size: 12px; font-weight: 700; color: #1e40af;
    display: flex; align-items: center; gap: 6px; margin-bottom: 10px;
}
.ai-devhist-body { font-size: 12px; color: #1e3a5f; line-height: 1.55; margin-bottom: 10px; }
.ai-devhist-note { font-size: 11px; color: #475569; font-style: italic; }

/* ── Modal headers ── */
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
.fst-action-link {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 14px; border-radius: 8px;
    background: #f8f9fa; border: 1px solid #e2e8f0;
    text-decoration: none; color: #1e293b; transition: background .15s;
}
.fst-action-link:hover { background: #f1f5ff; color: #1e293b; }
.fst-action-link .fal-icon  { font-size: 20px; flex-shrink: 0; }
.fst-action-link .fal-title { font-size: 13px; font-weight: 600; }
.fst-action-link .fal-sub   { font-size: 11px; color: #64748b; }
.fst-action-link .fal-arrow { margin-left: auto; color: #94a3b8; font-size: 18px; flex-shrink: 0; }

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
    .fst-card { height: auto !important; margin: 8px; }
    .content-page { padding: 0 !important; }
    .content { padding: 0 !important; }
    .content-page > .content > .container-fluid { padding-top: 0 !important; padding-left: 0 !important; padding-right: 0 !important; }
    .ai-content-row { flex-direction: column; }
    .ai-left-col { border-right: none; border-bottom: 1px solid #e8eaf2; padding-right: 20px; }
    .ai-right-col { flex: unset; }
    .modal-dialog { margin: 1.25rem auto !important; max-width: calc(100% - 24px) !important; }
    .modal-content { border-radius: 10px !important; }
    #fstActionsBtn { font-size: 11px; padding: 0 8px; height: 26px; }
    .fst-card-header { gap: 4px; }
    .fst-hdr-actions { gap: 0; }
    .fst-hdr-btn { width: 24px; height: 24px; font-size: 15px; padding: 0; }
    .fst-hdr-divider { margin: 0 3px; }
    .fst-branch-row { gap: 6px; }
    .fst-page-label { font-size: 11px; }
    .fst-bar-icon-btn { height: 26px; width: 26px; font-size: 15px; }
}
</style>

<div class="content-page"><div class="content"><div class="container-fluid">

<div class="fst-card">

    {{-- ── Silver header bar ── --}}
    <div class="fst-card-header">
        <div class="fst-hdr-left">
            @if($isRectified)
                <button type="button" id="fstDateChip" class="fst-locked" disabled
                        title="Date is locked — this stocktaking has been rectified">
                    <i class="ri-calendar-line"></i> {{ $displayDate }}
                    <i class="ri-lock-line fst-edit-pencil"></i>
                </button>
            @else
                <button type="button" id="fstDateChip"
                        class="{{ $isCustom ? 'custom-mode' : '' }}"
                        title="Change stocktaking date">
                    <i class="ri-calendar-line"></i> {{ $displayDate }}
                    <span class="fst-mode-tag">{{ $isCustom ? 'Custom' : 'Today' }}</span>
                    <i class="ri-pencil-line fst-edit-pencil"></i>
                </button>
            @endif

            <button type="button" id="fstActionsBtn"
                    onclick="$('#fstNavActionsModal').modal('show')"
                    title="Quick navigation">
                <i class="ri-layout-grid-line"></i> <span>FS Actions</span>
            </button>
        </div>

        <div class="fst-hdr-actions">
            @if($branchId)
                <button type="button" class="fst-hdr-btn" id="aiStatsBtn" title="View summary stats">
                    <i class="ri-bar-chart-2-line"></i>
                </button>
                <span class="fst-hdr-divider"></span>
            @endif
            <button type="button" class="fst-hdr-btn" title="About Actions &amp; Info"
                    onclick="$('#fstInfoModal').modal('show')">
                <i class="ri-information-line"></i>
            </button>
        </div>
    </div>

    {{-- ── Blue branch bar ── --}}
    <div class="fst-branch-row">
        <div class="fst-branch-left">
            <form method="POST" action="{{ route('tenant.admin.update.filters') }}" id="fstBranchForm">
                @csrf
                <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                <select name="branch_id" id="fstBranchSelect"
                        @if($isRectified) disabled title="Branch is locked — this stocktaking has been rectified" @endif
                        onchange="document.getElementById('fstBranchForm').submit()">
                    <option value="" hidden>{{ $branchName ?? '— Select Branch —' }}</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </form>
            @if($isRectified)
                <span class="fst-rectified-tag"><i class="ri-lock-line"></i> Rectified</span>
            @endif
        </div>

        <div class="fst-branch-right">
            <span class="fst-page-label">Actions &amp; Info</span>
        </div>
    </div>

    {{-- ── Body ── --}}
    <div class="fst-card-body">
        @if(!$branchId)
            <div class="fst-placeholder-wrap">
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
                    @if($salesAfterCounting->isNotEmpty())
                    <div class="ai-affected-line">
                        <a href="#" onclick="event.preventDefault();$('#salesAfterModal').modal('show');">
                            <i class="ri-shopping-cart-2-line"></i>
                            View {{ $salesAfterCounting->count() }} affected product{{ $salesAfterCounting->count() === 1 ? '' : 's' }}
                        </a>
                    </div>
                    @endif
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
                            <tr><td>Difference (FV − EV)</td><td class="c">{{ number_format($differenceValue, 2) }}</td><td class="c">—</td></tr>
                            <tr><td>Missing items</td><td class="c">{{ $missingCount }}</td><td class="c">{{ $missingPercentage }}</td></tr>
                            <tr><td>Missing value</td><td class="c">{{ number_format($missingValue, 2) }}</td><td class="c">—</td></tr>
                            <tr class="total-row"><td>Full difference (FV − (EV + MV))</td><td class="c">{{ number_format($fullDifference, 2) }}</td><td class="c">—</td></tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            {{-- Right: download + sync + rectify --}}
            <div class="ai-right-col">

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

                @if($isRectified)

                {{-- Locked summary card (no "corrections still possible" line) --}}
                <div class="ai-rect-card locked">
                    <div class="ai-rect-title"><i class="ri-shield-check-line" style="color:#16a34a;"></i> Already Rectified</div>
                    <div class="ai-rect-body">There is nothing further to do for {{ $branchName }} on {{ $displayDate }}. Figures above and in History reflect the final, sales-netted result for this date. To do stocktaking again for this branch, change the date on the Stocktaking page.</div>
                </div>

                {{-- Device history card --}}
                <div class="ai-devhist-card" id="devHistoryCard">
                    <div class="ai-devhist-title"><i class="ri-device-line"></i> Devices Used in This Stocktaking</div>
                    <div class="ai-devhist-body" id="devHistoryList">Loading device history…</div>
                    <div class="ai-devhist-note">Rectification only completed after every device above reported a fully synced state — this is why the figures here are final.</div>
                </div>

                @else
                <div class="ai-sync-card" id="syncGateCard">
                    <div class="ai-sync-title"><i class="ri-wifi-line"></i> Device Sync Status</div>
                    <div id="syncDevicePanel">
                        <p style="font-size:12px;color:#92400e;margin-bottom:10px;">
                            Check that all counting and POS devices for <strong>{{ $branchName }}</strong> have fully synced before rectifying.
                        </p>
                        <ul class="device-list" id="deviceList">
                            <li style="color:#94a3b8;font-size:12px;font-style:italic;">
                                <span>Click below to check sync status</span>
                            </li>
                        </ul>
                        <button id="syncCheckBtn" onclick="checkSyncStatus()">
                            <i class="ri-refresh-line"></i> Check Sync Status
                        </button>
                    </div>
                </div>

                <div class="ai-rect-card sync-blocked" id="rectifyCard">
                    <div class="ai-rect-title"><i class="ri-alert-line"></i> Rectification</div>
                    <div class="ai-rect-body">
                        Replaces live stock quantities at <strong>{{ $branchName }}</strong> with counted figures (already adjusted for sales made after each count), and writes a permanent history record.
                        <strong>Rectifying locks everything for this branch and date</strong> — counting, merging, and missing-product entry all close immediately.
                        <strong>This action is irreversible.</strong>
                    </div>
                    <a href="#" class="btn btn-danger btn-sm w-100" id="rectifyOpenBtn" style="pointer-events:none;opacity:.45;">
                        <i class="ri-lock-line me-1"></i> Check Device Sync First
                    </a>
                </div>
                @endif

            </div>
        </div>
        @endif
    </div>

</div>{{-- /.fst-card --}}
</div></div></div>


{{-- ══ NAV ACTIONS MODAL ══ --}}
<div class="modal fade" id="fstNavActionsModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:360px;">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title">
                    <i class="ri-layout-grid-line"></i> Quick Actions
                </h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:12px 16px;display:flex;flex-direction:column;gap:8px;">
                <a href="{{ route('retail.operations.fullstocktaking') }}" class="fst-action-link">
                    <i class="ri-scales-3-line fal-icon" style="color:#4B5EBD;"></i>
                    <div><div class="fal-title">Stocktaking</div><div class="fal-sub">Count products for this branch and date</div></div>
                    <i class="ri-arrow-right-s-line fal-arrow"></i>
                </a>
                <a href="{{ route('retail.operations.fullstocktaking.merged-data') }}" class="fst-action-link">
                    <i class="ri-stack-line fal-icon" style="color:#4B5EBD;"></i>
                    <div><div class="fal-title">Merged Data</div><div class="fal-sub">View all merged stocktake records</div></div>
                    <i class="ri-arrow-right-s-line fal-arrow"></i>
                </a>
                <a href="{{ route('retail.operations.fullstocktaking.missing-products') }}" class="fst-action-link">
                    <i class="ri-error-warning-line fal-icon" style="color:#d97706;"></i>
                    <div><div class="fal-title">Missing Products</div><div class="fal-sub">Products not yet counted on this date</div></div>
                    <i class="ri-arrow-right-s-line fal-arrow"></i>
                </a>
                <a href="{{ route('retail.operations.fullstocktaking.actions-and-info') }}" class="fst-action-link">
                    <i class="ri-flashlight-line fal-icon" style="color:#059669;"></i>
                    <div><div class="fal-title">Actions &amp; Info</div><div class="fal-sub">Rectify, export and manage stocktake</div></div>
                    <i class="ri-arrow-right-s-line fal-arrow"></i>
                </a>
            </div>
        </div>
    </div>
</div>


{{-- ══ STATS MODAL ══ --}}
@if($branchId)
<div class="modal fade" id="aiStatsModal" tabindex="-1">
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
                        ['Missing items',           $missingCount . ' (' . $missingPercentage . '%)'],
                        ['Missing value (MV)',      'MWK ' . number_format($missingValue, 2)],
                        ['Full difference',         'MWK ' . number_format($fullDifference, 2)],
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


{{-- ══ SALES-AFTER-COUNTING MODAL ══ --}}
<div class="modal fade" id="salesAfterModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-shopping-cart-2-line"></i> Sales Recorded After Counting</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <p style="font-size:12.5px;color:#64748b;margin-bottom:14px;">
                    These products were sold <strong>after</strong> they were counted today. Rectification will automatically subtract the quantity sold from the expected figure for each — this is a preview of that adjustment.
                </p>
                <table class="stats-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="c">Qty Sold After Count</th>
                            <th class="c">Expected (before)</th>
                            <th class="c">Expected (after)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($salesAfterCounting as $s)
                        <tr>
                            <td>{{ $s['product'] }} <span style="color:#94a3b8;font-size:11px;">({{ $s['unit'] }})</span></td>
                            <td class="c">{{ number_format($s['qty_sold'], 2) }}</td>
                            <td class="c">{{ number_format($s['expected_before'], 2) }}</td>
                            <td class="c" style="font-weight:700;color:#4B5EBD;">{{ number_format($s['expected_after'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


{{-- ══ RECTIFICATION CONFIRM MODAL ══ --}}
<div class="modal fade" id="rectifyModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-danger">
                <h5 class="modal-title mh-title"><i class="ri-alert-line"></i> Confirm Rectification</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <div id="rectifyFormArea">
                    <div class="alert alert-danger border-0 py-2 px-3 mb-3" style="font-size:12px;border-radius:6px;">
                        <i class="ri-error-warning-line me-1"></i>
                        <strong>This action is permanent and cannot be undone.</strong> Live stock at <strong>{{ $branchName }}</strong> will be overwritten with counted figures for <strong>{{ $displayDate }}</strong>.
                    </div>
                    <p style="font-size:13px;color:#475569;margin-bottom:14px;">Any sales made after a product was counted have already been deducted automatically. Rows are processed one at a time, so a single bad row never blocks the rest.</p>
                    <div class="alert alert-success border-0 py-2 px-3 mb-3" id="syncConfirmBanner" style="font-size:12px;border-radius:6px;display:none;">
                        <i class="ri-shield-check-line me-1"></i> All devices are synced — safe to proceed.
                    </div>
                    <label class="form-label fw-semibold" style="font-size:12px;">Enter your password to confirm</label>
                    <input type="password" class="form-control" id="rectifyPassword" placeholder="Password" autocomplete="off">
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
                <button type="button" class="btn btn-danger btn-sm" id="rectifySubmitBtn">
                    <i class="ri-check-line"></i> Rectify Now
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm" id="rfDownloadFailedBtn" style="display:none;"><i class="ri-file-excel-2-line"></i> Download Failed Rows</button>
                <button type="button" class="btn btn-outline-warning btn-sm" id="rfRetryFailedBtn" style="display:none;"><i class="ri-refresh-line"></i> Retry Failed</button>
                <button type="button" class="btn btn-primary btn-sm" id="rfDoneBtn" style="display:none;" data-bs-dismiss="modal">Done</button>
            </div>
        </div>
    </div>
</div>


{{-- ══ PENDING DEVICES MODAL ══ --}}
<div class="modal fade" id="pendingDevicesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-amber">
                <h5 class="modal-title mh-title"><i class="ri-wifi-off-line"></i> Devices Still Pending</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;font-size:13px;">
                <p>The following devices still have unsynced work for <strong>{{ $branchName }}</strong> on <strong>{{ $displayDate }}</strong>.</p>
                <ul id="pendingDevicesList" style="font-size:12.5px;padding-left:18px;"></ul>
                <div class="alert alert-warning border-0 mt-3 py-2 px-3" style="font-size:11.5px;border-radius:6px;">
                    <i class="ri-alert-line me-1"></i> POS devices: the operator needs to sync any offline sales before rectification can proceed.
                </div>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">OK, I'll wait</button>
                <button type="button" class="btn btn-warning btn-sm"
                        onclick="$('#pendingDevicesModal').modal('hide');checkSyncStatus();">
                    <i class="ri-refresh-line me-1"></i> Re-check
                </button>
            </div>
        </div>
    </div>
</div>


{{-- ══ DATE MODAL ══ --}}
<div class="modal fade" id="fstDateModal" data-bs-backdrop="static" tabindex="-1">
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
                    <div class="dmc {{ !$isCustom ? 'active-sys' : '' }}" id="fstDmcSystem"
                         onclick="fstSetDateMode('system')">
                        <div class="dmc-label">System date</div>
                        <div class="dmc-val">{{ Carbon::today()->format('d M Y') }}</div>
                    </div>
                    <div class="dmc {{ $isCustom ? 'active-cus' : '' }}" id="fstDmcCustom"
                         onclick="fstSetDateMode('custom')">
                        <div class="dmc-label">Custom date</div>
                        <div class="dmc-val" id="fstDmcCustomVal">
                            {{ $isCustom ? $displayDate : 'Pick a date' }}
                        </div>
                    </div>
                </div>
                <form method="POST" action="{{ route('tenant.admin.update.filters') }}" id="fstDateForm">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                    <input type="hidden" name="fst_custom_date" id="fstDateFormValue" value="">
                    <div id="fstCustomDateRow" style="{{ !$isCustom ? 'display:none;' : '' }}">
                        <input type="date" class="form-control" id="fstCustomDateInput"
                               value="{{ $date }}" oninput="fstPreviewDate(this.value)">
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


{{-- ══ INFO MODAL ══ --}}
<div class="modal fade" id="fstInfoModal" tabindex="-1">
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
                    <li><strong>Sales made after counting are handled automatically.</strong></li>
                    <li>The stats table shows a <strong>live preview</strong> using the expected stock snapshotted at count time.</li>
                    <li><strong>Sync gate:</strong> The Rectify button only unlocks once all devices show zero pending operations.</li>
                    <li><strong>POS devices</strong> report their offline queue when the POS operator syncs.</li>
                    <li>Rectification is <strong>irreversible and locks everything</strong> for this branch and date.</li>
                    <li>Once rectified, the branch and date are locked here — switch the date to start a new stocktaking.</li>
                </ul>
                <div class="alert alert-warning border-0 mt-3 py-2 px-3" style="font-size:12px;border-radius:6px;">
                    <i class="ri-alert-line me-1"></i> If a device never shows up in the sync panel, it has never reported in — use "Check Sync Status" periodically.
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

var AI_RECTIFY_START_URL  = '{{ $rectifyStartUrl }}';
var AI_RECTIFY_ROW_URL    = '{{ $rectifyRowUrl }}';
var AI_RECTIFY_FINISH_URL = '{{ $rectifyFinishUrl }}';
var AI_SYNC_URL           = '{{ $syncStatusUrl }}';
var AI_BRANCH_ID    = '{{ $branchId }}';
var AI_DATE         = '{{ $date }}';
var AI_IS_RECTIFIED = {{ $isRectified ? 'true' : 'false' }};
var syncAllClear    = false;

function aiCsrf() {
    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

// ── STATS BUTTON ──────────────────────────────────────────────────────
document.getElementById('aiStatsBtn')?.addEventListener('click', function () {
    $('#aiStatsModal').modal('show');
});

// ── SYNC STATUS CHECK (also reused read-only for rectified device history) ──
function checkSyncStatus() {
    if (!AI_BRANCH_ID) return;

    var btn = document.getElementById('syncCheckBtn');
    if (btn) { btn.classList.add('checking'); btn.innerHTML = '<i class="ri-loader-4-line"></i> Checking...'; }

    fetch(AI_SYNC_URL + '?branch_id=' + AI_BRANCH_ID + '&date=' + AI_DATE, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': aiCsrf() }
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        if (btn) { btn.classList.remove('checking'); btn.innerHTML = '<i class="ri-refresh-line"></i> Re-check Sync Status'; }

        renderDeviceList(data.devices || []);

        if (data.can_rectify) {
            syncAllClear = true;
            var card = document.getElementById('syncGateCard');
            if (card) {
                card.classList.add('all-clear');
                card.querySelector('.ai-sync-title').innerHTML = '<i class="ri-shield-check-line"></i> All Devices Synced';
            }
            var rectCard = document.getElementById('rectifyCard');
            if (rectCard) { rectCard.classList.remove('sync-blocked'); }
            var rectBtn = document.getElementById('rectifyOpenBtn');
            if (rectBtn) {
                rectBtn.style.pointerEvents = '';
                rectBtn.style.opacity = '';
                rectBtn.innerHTML = '<i class="ri-arrow-right-line me-1"></i> Proceed with Rectification';
            }
        } else {
            syncAllClear = false;
            var pendingList = document.getElementById('pendingDevicesList');
            if (pendingList) {
                var pending = (data.devices || []).filter(function (d) { return d.pending_ops_count > 0; });
                pendingList.innerHTML = pending.map(function (d) {
                    return '<li><strong>' + (d.device_label || d.device_id) + '</strong> (' + d.device_type + ') — ' + d.pending_ops_count + ' pending op(s)</li>';
                }).join('');
            }
            $('#pendingDevicesModal').modal('show');
        }
    })
    .catch(function () {
        if (btn) { btn.classList.remove('checking'); btn.innerHTML = '<i class="ri-refresh-line"></i> Check Sync Status'; }
        toastr.error('Could not reach the server. Please retry.', 'Network Error');
    });
}

function renderDeviceList(devices) {
    var list = document.getElementById('deviceList');
    if (!list) return;
    if (!devices.length) {
        list.innerHTML = '<li style="color:#94a3b8;font-size:12px;font-style:italic;"><span>No devices have reported in yet.</span></li>';
        return;
    }
    list.innerHTML = devices.map(function (d) {
        var synced = d.pending_ops_count === 0;
        return '<li>' +
            '<span><span class="device-pill ' + (d.device_type === 'pos' ? 'pos' : 'stk') + '">' + d.device_type.toUpperCase() + '</span>' +
            '<span style="margin-left:6px;font-weight:600;">' + (d.device_label || d.device_id) + '</span></span>' +
            '<span class="device-pill ' + (synced ? 'synced' : 'pending') + '">' + (synced ? 'Synced' : d.pending_ops_count + ' pending') + '</span>' +
        '</li>';
    }).join('');
}

// ── DEVICE HISTORY (rectified state — read-only, no gate logic) ────────
function loadDeviceHistory() {
    if (!AI_BRANCH_ID) return;

    var listEl = document.getElementById('devHistoryList');

    fetch(AI_SYNC_URL + '?branch_id=' + AI_BRANCH_ID + '&date=' + AI_DATE, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': aiCsrf() }
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        var devices = data.devices || [];

        if (!listEl) return;
        if (!devices.length) {
            listEl.innerHTML = 'No device records were found for this branch and date.';
            return;
        }

        listEl.innerHTML = devices.length + ' device' + (devices.length === 1 ? '' : 's') +
            (devices.length === 1 ? ' was' : ' were') +
            ' involved in this stocktaking, ' + (devices.length === 1 ? 'which was' : 'all of which were') +
            ' fully synced at the time of rectification.';
    })
    .catch(function () {
        if (listEl) listEl.innerHTML = 'Device history could not be loaded right now.';
    });
}

if (AI_IS_RECTIFIED) {
    loadDeviceHistory();
}

// ── OPEN RECTIFY MODAL ────────────────────────────────────────────────
function rfResetModal() {
    document.getElementById('rectifyPassword').value = '';
    document.getElementById('rectifyFormArea').style.display = '';
    document.getElementById('rfProgressWrap').classList.remove('active');
    document.getElementById('rfSummary').style.display = 'none';
    document.getElementById('rfBarFill').style.width = '0%';
    document.getElementById('rfCurrentItem').textContent = 'Starting…';
    document.getElementById('rfCountLabel').textContent = '0 / 0';
    document.getElementById('rfOkCount').textContent = '0';
    document.getElementById('rfBadCount').textContent = '0';
    document.getElementById('rfFailList').style.display = 'none';
    document.getElementById('rfFailList').innerHTML = '';
    document.getElementById('rectifySubmitBtn').style.display = '';
    document.getElementById('rectifySubmitBtn').disabled = false;
    document.getElementById('rectifySubmitBtn').innerHTML = '<i class="ri-check-line"></i> Rectify Now';
    document.querySelector('#rectifyModal .btn-secondary[data-bs-dismiss="modal"]').style.display = '';
    document.getElementById('rfDownloadFailedBtn').style.display = 'none';
    document.getElementById('rfRetryFailedBtn').style.display = 'none';
    document.getElementById('rfDoneBtn').style.display = 'none';
    rfState = { summaryId: null, okCount: 0, badCount: 0, failedRows: [] };
}

var rfState = { summaryId: null, okCount: 0, badCount: 0, failedRows: [] };

var rectifyOpenBtn = document.getElementById('rectifyOpenBtn');
if (rectifyOpenBtn) {
    rectifyOpenBtn.addEventListener('click', function (e) {
        e.preventDefault();
        if (!syncAllClear) {
            toastr.warning('Check sync status first.', 'Sync Required');
            return;
        }
        rfResetModal();
        var banner = document.getElementById('syncConfirmBanner');
        if (banner) banner.style.display = '';
        $('#rectifyModal').modal('show');
    });
}

// ── PROGRESS UI HELPERS ─────────────────────────────────────────────
function rfUpdateProgress(done, total, currentName) {
    var pct = total > 0 ? Math.round((done / total) * 100) : 0;
    document.getElementById('rfBarFill').style.width = pct + '%';
    document.getElementById('rfCountLabel').textContent = done + ' / ' + total;
    document.getElementById('rfCurrentItem').textContent = currentName
        ? ('Rectifying: ' + currentName)
        : 'Finishing up…';
    document.getElementById('rfOkCount').textContent = rfState.okCount;
    document.getElementById('rfBadCount').textContent = rfState.badCount;
}

function rfRenderFailList() {
    var listEl = document.getElementById('rfFailList');
    if (!rfState.failedRows.length) {
        listEl.style.display = 'none';
        listEl.innerHTML = '';
        return;
    }
    listEl.style.display = '';
    listEl.innerHTML = rfState.failedRows.map(function (r) {
        return '<div class="rf-fail-row"><strong>' + (r.product_name || ('Row #' + r.id)) + '</strong> — ' + (r.error || 'Failed') + '</div>';
    }).join('');
}

// ── ROW-BY-ROW WALK ─────────────────────────────────────────────────
// Rows are sent to rectifyRow one at a time (not in parallel) so the
// progress bar reflects real progress and a bad row never blocks the rest.
async function rfProcessRows(rows, branchId, date) {
    var total = rows.length;
    rfUpdateProgress(0, total, rows[0] ? rows[0].product_name : null);

    for (var i = 0; i < rows.length; i++) {
        var row = rows[i];
        rfUpdateProgress(i, total, row.product_name);

        try {
            var res = await fetch(AI_RECTIFY_ROW_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': aiCsrf(),
                    'Accept': 'application/json',
                },
                body: new URLSearchParams({
                    summary_id: rfState.summaryId,
                    branch_id: branchId,
                    date: date,
                    row_id: row.id,
                }).toString(),
            });
            var d = await res.json();
            var result = d.result || {};

            if (result.status === 'success') {
                rfState.okCount++;
            } else {
                rfState.badCount++;
                rfState.failedRows.push(result);
            }
        } catch (e) {
            console.error('[Rectify] Row failed to reach server', row.id, e);
            rfState.badCount++;
            rfState.failedRows.push({ id: row.id, product_name: row.product_name, error: 'Network error — could not reach the server.' });
        }

        rfUpdateProgress(i + 1, total, i + 1 < rows.length ? rows[i + 1].product_name : null);
    }
}

async function rfFinish(branchId, date) {
    var res = await fetch(AI_RECTIFY_FINISH_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': aiCsrf(),
            'Accept': 'application/json',
        },
        body: new URLSearchParams({
            summary_id: rfState.summaryId,
            branch_id: branchId,
            date: date,
        }).toString(),
    });
    return res.json();
}

function rfShowSummary(finishData) {
    document.getElementById('rfProgressWrap').classList.remove('active');
    document.getElementById('rectifySubmitBtn').style.display = 'none';
    document.querySelector('#rectifyModal .btn-secondary[data-bs-dismiss="modal"]').style.display = 'none';

    var summaryEl = document.getElementById('rfSummary');
    summaryEl.style.display = '';

    var hasFailures = rfState.failedRows.length > 0;
    document.getElementById('rfSummaryTitle').textContent = hasFailures
        ? 'Rectification finished with issues'
        : 'Rectification complete';
    document.getElementById('rfSummarySub').textContent =
        rfState.okCount + ' rectified, ' + rfState.badCount + ' failed.' +
        (finishData && finishData.success ? (' ' + finishData.success) : '');

    rfRenderFailList();

    if (hasFailures) {
        toastr.warning(rfState.badCount + ' row(s) could not be rectified. Retry or download the list.', 'Rectified with issues');
        document.getElementById('rfDownloadFailedBtn').style.display = '';
        document.getElementById('rfRetryFailedBtn').style.display = '';
    } else {
        toastr.success('Full stocktaking rectified successfully.', 'Rectified!');
    }
    document.getElementById('rfDoneBtn').style.display = '';
}

// ── SUBMIT RECTIFICATION ──────────────────────────────────────────────
var rectifySubmitBtn = document.getElementById('rectifySubmitBtn');
if (rectifySubmitBtn) {
    rectifySubmitBtn.addEventListener('click', async function () {
        var btn      = this;
        var password = document.getElementById('rectifyPassword').value.trim();

        if (!password) {
            toastr.error('Please enter your password.', 'Password Required');
            return;
        }

        btn.disabled = true;
        var originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="ri-loader-4-line"></i> Starting...';

        try {
            var res = await fetch(AI_RECTIFY_START_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': aiCsrf(),
                    'Accept': 'application/json',
                },
                body: new URLSearchParams({ branch_id: AI_BRANCH_ID, date: AI_DATE, password: password }).toString(),
            });
            var status = res.status;
            var d = {};
            try { d = await res.json(); } catch (e) { /* non-JSON response */ }

            if (status !== 200) {
                btn.disabled = false;
                btn.innerHTML = originalHtml;

                if (status === 401) {
                    toastr.error(d.error || 'The password you entered is incorrect.', 'Incorrect Password');
                } else if (status === 409) {
                    $('#rectifyModal').modal('hide');
                    toastr.info('This date has already been rectified.', 'Already Done');
                    setTimeout(function () { window.location.reload(); }, 1800);
                } else if (status === 423) {
                    $('#rectifyModal').modal('hide');
                    toastr.warning('A device reported pending work after the sync check. Re-check sync and try again.', 'Sync Changed');
                    syncAllClear = false;
                    var rb = document.getElementById('rectifyOpenBtn');
                    if (rb) { rb.style.pointerEvents = 'none'; rb.style.opacity = '.45'; rb.innerHTML = '<i class="ri-lock-line me-1"></i> Check Device Sync First'; }
                    var gc = document.getElementById('syncGateCard');
                    if (gc) { gc.classList.remove('all-clear'); gc.querySelector('.ai-sync-title').innerHTML = '<i class="ri-wifi-line"></i> Device Sync Status'; }
                } else if (status === 422) {
                    var msg422 = 'Validation error.';
                    if (d.errors) { msg422 = Object.values(d.errors).map(function (e) { return Array.isArray(e) ? e[0] : e; }).join(' '); }
                    toastr.error(msg422, 'Validation Error');
                } else {
                    var msg = (d && (d.error || d.message)) || ('Unexpected server response (HTTP ' + status + ').');
                    toastr.error(msg, 'Error');
                    console.error('[Rectify] Unhandled status ' + status + ':', d);
                }
                return;
            }

            // ── Start succeeded — walk the rows with the progress bar ──
            rfState.summaryId = d.summary_id;
            document.getElementById('rectifyFormArea').style.display = 'none';
            document.getElementById('rfProgressWrap').classList.add('active');

            if (!d.rows.length) {
                rfUpdateProgress(0, 0, null);
            } else {
                await rfProcessRows(d.rows, AI_BRANCH_ID, AI_DATE);
            }

            rfUpdateProgress(d.total, d.total, null);
            var finishData = await rfFinish(AI_BRANCH_ID, AI_DATE);
            rfShowSummary(finishData);

        } catch (e) {
            console.error('[Rectify] Network error', e);
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            toastr.error('Network error — could not reach the server.', 'Network Error');
        }
    });
}

// ── RETRY FAILED ROWS ───────────────────────────────────────────────
var rfRetryFailedBtn = document.getElementById('rfRetryFailedBtn');
if (rfRetryFailedBtn) {
    rfRetryFailedBtn.addEventListener('click', async function () {
        var btn = this;
        var rowsToRetry = rfState.failedRows.map(function (r) { return { id: r.id, product_name: r.product_name }; });
        if (!rowsToRetry.length) return;

        btn.disabled = true;
        btn.innerHTML = '<i class="ri-loader-4-line"></i> Retrying...';

        rfState.failedRows = [];
        rfState.badCount = 0;

        document.getElementById('rfSummary').style.display = 'none';
        document.getElementById('rfProgressWrap').classList.add('active');

        await rfProcessRows(rowsToRetry, AI_BRANCH_ID, AI_DATE);
        var finishData = await rfFinish(AI_BRANCH_ID, AI_DATE);

        btn.disabled = false;
        btn.innerHTML = '<i class="ri-refresh-line"></i> Retry Failed';
        rfShowSummary(finishData);
    });
}

// ── DOWNLOAD FAILED ROWS ────────────────────────────────────────────
var rfDownloadFailedBtn = document.getElementById('rfDownloadFailedBtn');
if (rfDownloadFailedBtn) {
    rfDownloadFailedBtn.addEventListener('click', function () {
        if (!rfState.failedRows.length) return;
        var lines = ['Row ID,Product,Error'];
        rfState.failedRows.forEach(function (r) {
            var product = '"' + String(r.product_name || '').replace(/"/g, '""') + '"';
            var error   = '"' + String(r.error || '').replace(/"/g, '""') + '"';
            lines.push(r.id + ',' + product + ',' + error);
        });
        var blob = new Blob([lines.join('\n')], { type: 'text/csv' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'fst-rectify-failed-rows-' + AI_DATE + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    });
}

// ── DONE ─────────────────────────────────────────────────────────────
var rfDoneBtn = document.getElementById('rfDoneBtn');
if (rfDoneBtn) {
    rfDoneBtn.addEventListener('click', function () {
        window.location.reload();
    });
}


/* ── Date modal helpers ── */
function fstSetDateMode(mode) {
    document.getElementById('fstDmcSystem').classList.toggle('active-sys', mode === 'system');
    document.getElementById('fstDmcSystem').classList.toggle('active-cus', false);
    document.getElementById('fstDmcCustom').classList.toggle('active-cus', mode === 'custom');
    document.getElementById('fstDmcCustom').classList.toggle('active-sys', false);
    document.getElementById('fstCustomDateRow').style.display = mode === 'custom' ? '' : 'none';
    document.getElementById('fstDateFormValue').value = mode === 'system' ? '' : document.getElementById('fstCustomDateInput').value;
}
function fstPreviewDate(val) {
    if (!val) return;
    document.getElementById('fstDateFormValue').value = val;
    var d  = new Date(val + 'T00:00:00');
    var mo = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    document.getElementById('fstDmcCustomVal').textContent = d.getDate() + ' ' + mo[d.getMonth()] + ' ' + d.getFullYear();
}
// Date chip is only clickable when not rectified (button is disabled in markup when rectified)
document.getElementById('fstDateChip')?.addEventListener('click', function () {
    if (AI_IS_RECTIFIED) return;
    document.getElementById('fstDateFormValue').value = '{{ $isCustom ? $date : "" }}';
    $('#fstDateModal').modal('show');
});

/* ── Session flash ── */
@if(Session::has('message'))
toastr['{{ Session::get("alert-type","info") }}']('{{ Session::get("message") }}');
@endif
</script>
@endsection