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
        : 'Branch not selected';

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

        $alreadySeeded = DB::connection('tenant')->table('retail_fullstocktaking_missing_products')
            ->where('branch_id', $branchId)->where('date', $date)->exists();

        if (! $alreadySeeded) {
            $countedIds = $counted->pluck('base_product_id');
            $missingToSeed = DB::connection('tenant')
                ->table('retail_branch_products as rbp')
                ->join('retail_base_products as bp', 'bp.id', '=', 'rbp.base_product_id')
                ->where('rbp.branch_id', $branchId)
                ->whereNotIn('rbp.base_product_id', $countedIds)
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
        }

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
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
.card-header { padding: 0.5rem 1.5rem !important; background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff; border-top-left-radius: 10px; border-top-right-radius: 10px; }
.card-body { padding: 0 !important; }
.card { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden; }
.card-header h4 { color: #fff; font-weight: 600; margin-bottom: 0; display: flex; align-items: center; }
.card-header .btn-light { height: 28px; padding: 0 10px; display: flex; align-items: center; justify-content: center; line-height: 1; font-size: 16px; }
.card-header .btn-light:hover { background-color: #f8f9fa; transition: background-color 0.2s; }

.tab-header-container { background: #cccccc; border-bottom: 1px solid #b3b3b3; }
.nav-pills .nav-link { border-radius: 0 !important; padding: .65rem 1rem; font-weight: 500; color: #495057; border-bottom: none; transition: all .2s; font-size: 12.5px; }
.nav-pills .nav-link:hover { background: #b8b8b8; color: #4B5EBD; }
.nav-pills .nav-link.active { background: transparent !important; color: #4B5EBD !important; border-bottom: none; font-weight: 600; }
.nav-pills .nav-link i { font-size: 1rem; margin-right: .3rem; }

.fst-action-bar { display: flex; align-items: center; justify-content: space-between; background: #9098a8; padding: 8px 14px; border-bottom: 1px solid #7a8090; gap: 10px; flex-wrap: wrap; }
.fst-left { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; flex-wrap: wrap; }
.fst-right { display: flex; align-items: center; gap: 4px; flex-shrink: 0; }
#fstBranchSelect { border: 1.5px solid rgba(255,255,255,0.35); background: #9098a8; border-radius: 7px; padding: 5px 10px; font-size: 12.5px; font-weight: 600; color: #dde0e8; max-width: 220px; height: 32px; }
.fst-date-chip { display: inline-flex; align-items: center; gap: 5px; background: rgba(255,255,255,0.12); border: 1.5px solid rgba(255,255,255,0.3); border-radius: 20px; padding: 5px 12px; font-size: 12px; font-weight: 600; color: #dde0e8; white-space: nowrap; height: 32px; }
.fst-date-chip .mode-badge { font-size: 9px; padding: 1px 5px; border-radius: 8px; background: rgba(255,255,255,0.2); font-weight: 700; color: #dde0e8; margin-left: 4px; }
.rectified-tag { font-size: 9px; font-weight: 700; background: #d1fae5; color: #065f46; border-radius: 5px; padding: 1px 6px; margin-left: 4px; }
.fst-icon-btn { width: 32px; height: 32px; border-radius: 7px; background: rgba(255,255,255,0.12); border: 1.5px solid rgba(255,255,255,0.3); color: #dde0e8; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 15px; text-decoration: none; }
.fst-icon-btn:hover { background: rgba(255,255,255,0.22); }

/* ── Two-column content layout ───────────────────────────────────────── */
.ai-content-row { display: flex; gap: 22px; align-items: stretch; min-height: 320px; padding: 4px 0; }
.ai-left-col { flex: 1 1 60%; padding: 18px 22px 18px 18px; border-right: 1px solid #e8eaf2; }
.ai-right-col { flex: 0 0 38%; padding: 18px 18px 18px 4px; display: flex; flex-direction: column; gap: 16px; }

.ai-affected-line { display: flex; justify-content: flex-end; margin-bottom: 10px; }
.ai-affected-line a { font-size: 11px; font-weight: 700; color: #4B5EBD; text-decoration: underline; display: inline-flex; align-items: center; gap: 4px; }

.stats-table { width: 100%; font-size: 13px; border-collapse: collapse; }
.stats-table th { color: #94a3b8; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; font-weight: 700; padding: 8px 10px; border-bottom: 2px solid #e2e8f0; text-align: left; }
.stats-table th.c, .stats-table td.c { text-align: center; }
.stats-table td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
.stats-table tr.total-row td { font-weight: 800; background: #f4f6ff; border-top: 2px solid #c5caec; }
.stats-icon { color: #4B5EBD; margin-right: 6px; }

/* ── Right column cards ─────────────────────────────────────────────── */
.ai-card { background: #f8f9fc; border: 1px solid #e4e7f5; border-radius: 10px; padding: 16px; }
.ai-card-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #94a3b8; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
.ai-dl-btn { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px; border: 1px solid #d8ddf0; background: #fff; cursor: pointer; text-decoration: none; transition: border-color 0.15s, background 0.15s; width: 100%; text-align: left; }
.ai-dl-btn:hover { border-color: #4B5EBD; background: #eff3ff; }
.ai-dl-btn i { font-size: 20px; color: #4B5EBD; flex-shrink: 0; }
.ai-dl-btn .dl-label { font-size: 12.5px; font-weight: 600; color: #1e293b; display: block; line-height: 1.2; }
.ai-dl-btn .dl-sub   { font-size: 11px; color: #64748b; display: block; margin-top: 2px; }
.ai-card form + form { margin-top: 10px; }

/* ── Sync gate card ─────────────────────────────────────────────────── */
.ai-sync-card { background: #fffbeb; border: 1.5px solid #fcd34d; border-radius: 10px; padding: 16px; }
.ai-sync-card.all-clear { background: #f0fdf4; border-color: #86efac; }
.ai-sync-title { font-size: 12px; font-weight: 700; display: flex; align-items: center; gap: 6px; margin-bottom: 10px; color: #92400e; }
.ai-sync-card.all-clear .ai-sync-title { color: #166534; }

.device-list { list-style: none; padding: 0; margin: 0 0 12px; }
.device-list li { display: flex; align-items: center; justify-content: space-between; padding: 5px 0; border-bottom: 1px solid rgba(0,0,0,0.06); font-size: 12px; }
.device-list li:last-child { border-bottom: none; }
.device-pill { font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 8px; }
.device-pill.synced   { background: #dcfce7; color: #166534; }
.device-pill.pending  { background: #fee2e2; color: #991b1b; }
.device-pill.stk      { background: #dbeafe; color: #1e40af; }
.device-pill.pos      { background: #ede9fe; color: #5b21b6; }
.device-type-label    { font-size: 10px; color: #94a3b8; margin-left: 4px; }

/* ── Check sync button ───────────────────────────────────────────────── */
#syncCheckBtn {
    width: 100%; padding: 7px 14px; border-radius: 7px; font-size: 12.5px; font-weight: 600;
    background: rgba(0,0,0,0.06); border: 1.5px solid rgba(0,0,0,0.12); color: #374151;
    cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px;
    transition: background 0.15s;
}
#syncCheckBtn:hover { background: rgba(0,0,0,0.1); }
#syncCheckBtn.checking { opacity: .6; pointer-events: none; }

/* ── Rectification card ─────────────────────────────────────────────── */
.ai-rect-card { background: #fef2f2; border: 1.5px solid #fca5a5; border-radius: 10px; padding: 16px; }
.ai-rect-card.locked { background: #f3f4f6; border-color: #d1d5db; }
.ai-rect-card.sync-blocked { background: #fef9ec; border-color: #fcd34d; }
.ai-rect-title { font-size: 12px; font-weight: 700; color: #b91c1c; display: flex; align-items: center; gap: 6px; margin-bottom: 8px; }
.ai-rect-card.locked .ai-rect-title { color: #374151; }
.ai-rect-card.sync-blocked .ai-rect-title { color: #92400e; }
.ai-rect-body { font-size: 12px; color: #7f1d1d; line-height: 1.55; margin-bottom: 12px; }
.ai-rect-card.locked .ai-rect-body { color: #4b5563; margin-bottom: 0; }
.ai-rect-card.sync-blocked .ai-rect-body { color: #78350f; }

/* ── Modal headers ──────────────────────────────────────────────────── */
.mh-blue   { background: linear-gradient(135deg,#4B5EBD,#576CC0); padding: 12px 18px !important; border-bottom: none; }
.mh-danger { background: linear-gradient(135deg,#dc2626,#ef4444); padding: 12px 18px !important; border-bottom: none; }
.mh-title  { color: #fff; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.mh-close  { filter: brightness(0) invert(1); opacity: .8; }
.mh-close:hover { opacity: 1; }

@media (max-width: 768px) {
    .ai-content-row { flex-direction: column; gap: 0; }
    .ai-left-col { border-right: none; border-bottom: 1px solid #e8eaf2; padding-right: 18px; }
    .ai-right-col { flex: unset; padding-left: 18px; }
}
</style>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>
<div class="card">

    <div class="card-header d-flex justify-content-between align-items-center">
        <h4><i class="ri-scales-3-line me-1"></i> Full Stocktaking</h4>
        <div class="d-flex align-items-center" style="gap:4px;">
            <a href="{{ route('retail.operations.fullstocktaking.history') }}" class="btn btn-light text-primary" title="History">
                <i class="ri-history-line"></i>
            </a>
            <a href="#" class="btn btn-light text-primary" id="fstInfoBtn" title="About this section">
                <i class="ri-information-line"></i>
            </a>
        </div>
    </div>

    <div class="fst-action-bar">
        <div class="fst-left">
            <form method="POST" action="{{ route('tenant.admin.update.filters') }}" id="fstBranchForm" style="margin:0;">
                @csrf
                <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                <select name="branch_id" id="fstBranchSelect" onchange="document.getElementById('fstBranchForm').submit()">
                    <option value="" hidden>{{ $branchId ? '' : '— Select Branch —' }}</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </form>
            <div class="fst-date-chip">
                <i class="ri-calendar-line" style="font-size:11px;"></i> {{ $displayDate }}
                <span class="mode-badge">{{ $isCustom ? 'Custom' : 'Today' }}</span>
            </div>
            @if($isRectified)<span class="rectified-tag"><i class="ri-lock-line"></i> Rectified</span>@endif
        </div>
        @if($branchId && !$isRectified)
        <div class="fst-right">
            <a href="#" class="fst-icon-btn" id="fstInfoBtn2" title="About this section"><i class="ri-information-line"></i></a>
        </div>
        @endif
    </div>

    <div class="tab-header-container">
        <ul class="nav nav-pills nav-justified mb-0">
            <li class="nav-item"><a href="{{ route('retail.operations.fullstocktaking') }}" class="nav-link"><i class="ri-scales-3-line"></i> Stocktaking</a></li>
            <li class="nav-item"><a href="{{ route('retail.operations.fullstocktaking.merged-data') }}" class="nav-link"><i class="ri-stack-line"></i> Merged Data</a></li>
            <li class="nav-item"><a href="{{ route('retail.operations.fullstocktaking.missing-products') }}" class="nav-link"><i class="ri-error-warning-line"></i> Missing Products</a></li>
            <li class="nav-item"><a href="{{ route('retail.operations.fullstocktaking.actions-and-info') }}" class="nav-link active"><i class="ri-flashlight-line"></i> Actions &amp; Info</a></li>
        </ul>
    </div>

    <div class="card-body">
        @if(!$branchId)
            <div style="padding:40px;text-align:center;color:#94a3b8;">
                <i class="ri-store-2-line" style="font-size:40px;display:block;margin-bottom:10px;"></i>Select a branch above.
            </div>
        @else
        <div class="ai-content-row">

            {{-- ── LEFT: Stats ─────────────────────────────────────────── --}}
            <div class="ai-left-col">

                @if($salesAfterCounting->isNotEmpty())
                <div class="ai-affected-line">
                    <a href="#" onclick="event.preventDefault();$('#salesAfterModal').modal('show');">
                        <i class="ri-shopping-cart-2-line"></i>
                        View {{ $salesAfterCounting->count() }} affected product{{ $salesAfterCounting->count() === 1 ? '' : 's' }}
                    </a>
                </div>
                @endif

                <table class="stats-table">
                    <thead>
                        <tr>
                            <th><i class="ri-information-line stats-icon"></i>Description</th>
                            <th class="c">Value</th><th class="c">%</th>
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
                        <tr><td>Difference (FV - EV)</td><td class="c">{{ number_format($differenceValue, 2) }}</td><td class="c">—</td></tr>
                        <tr><td>Missing items</td><td class="c">{{ $missingCount }}</td><td class="c">{{ $missingPercentage }}</td></tr>
                        <tr><td>Missing value</td><td class="c">{{ number_format($missingValue, 2) }}</td><td class="c">—</td></tr>
                        <tr class="total-row"><td>Full difference (FV - (EV + MV))</td><td class="c">{{ number_format($fullDifference, 2) }}</td><td class="c">—</td></tr>
                    </tbody>
                </table>
            </div>

            {{-- ── RIGHT: Sync Gate + Downloads + Rectification ────────── --}}
            <div class="ai-right-col">

                {{-- Downloads --}}
                <div class="ai-card">
                    <div class="ai-card-title"><i class="ri-download-2-line"></i> Download Report</div>
                    <form action="{{ route('retail.operations.fullstocktaking.report.full') }}" method="POST" target="_blank">
                        @csrf
                        <input type="hidden" name="branch_id" value="{{ $branchId }}">
                        <input type="hidden" name="date" value="{{ $date }}">
                        <button type="submit" class="ai-dl-btn">
                            <i class="ri-file-chart-line"></i>
                            <span><span class="dl-label">Full Report</span><span class="dl-sub">All data — counts, expected, differences, missing</span></span>
                        </button>
                    </form>
                    <form action="{{ route('retail.operations.fullstocktaking.report.delivery') }}" method="POST" target="_blank">
                        @csrf
                        <input type="hidden" name="branch_id" value="{{ $branchId }}">
                        <input type="hidden" name="date" value="{{ $date }}">
                        <button type="submit" class="ai-dl-btn">
                            <i class="ri-file-list-3-line"></i>
                            <span><span class="dl-label">Stock Delivery Note</span><span class="dl-sub">Product · Unit · Price · Qty</span></span>
                        </button>
                    </form>
                </div>

                @if($isRectified)
                {{-- Already rectified --}}
                <div class="ai-rect-card locked">
                    <div class="ai-rect-title"><i class="ri-shield-check-line" style="color:#16a34a;"></i> Already Rectified</div>
                    <div class="ai-rect-body">This date has already been rectified for {{ $branchName }}. Figures above and in History reflect the final, sales-netted result. Corrections can still be made via the Merged Data tab's offline sync.</div>
                </div>
                @else
                {{-- Sync Gate card --}}
                <div class="ai-sync-card" id="syncGateCard">
                    <div class="ai-sync-title"><i class="ri-wifi-line"></i> Device Sync Status</div>

                    <div id="syncDevicePanel">
                        <p style="font-size:12px;color:#92400e;margin-bottom:10px;">
                            Check that all counting and POS devices for <strong>{{ $branchName }}</strong> have fully synced before rectifying. The Rectify button only unlocks when every device shows zero pending operations.
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

                {{-- Rectification card --}}
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
</div>
</div></div></div>

{{-- ══ SALES-AFTER-COUNTING MODAL ══════════════════════════════════════ --}}
<div class="modal fade" id="salesAfterModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-shopping-cart-2-line"></i> Sales Recorded After Counting</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <p style="font-size:12.5px;color:#64748b;margin-bottom:14px;">
                    These products were sold <strong>after</strong> they were counted today. Rectification will automatically subtract the quantity sold from the expected figure for each — this is a preview of that adjustment.
                </p>
                <table class="stats-table">
                    <thead><tr><th>Product</th><th class="c">Qty Sold After Count</th><th class="c">Expected (before)</th><th class="c">Expected (after)</th></tr></thead>
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
        </div>
    </div>
</div>

{{-- ══ RECTIFICATION CONFIRM MODAL ════════════════════════════════════ --}}
<div class="modal fade" id="rectifyModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header mh-danger">
                <h5 class="modal-title mh-title"><i class="ri-alert-line"></i> Confirm Rectification</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <div class="alert alert-danger border-0 py-2 px-3 mb-3" style="font-size:12px;border-radius:6px;">
                    <i class="ri-error-warning-line me-1"></i>
                    <strong>This action is permanent and cannot be undone.</strong> Live stock at <strong>{{ $branchName }}</strong> will be overwritten with counted figures for <strong>{{ $displayDate }}</strong>, and counting/merging/missing-products entry for this branch and date will lock immediately.
                </div>
                <p style="font-size:13px;color:#475569;margin-bottom:14px;">Any sales made after a product was counted have already been deducted automatically, so the figures you see are final.</p>
                <div class="alert alert-success border-0 py-2 px-3 mb-3" id="syncConfirmBanner" style="font-size:12px;border-radius:6px;display:none;">
                    <i class="ri-shield-check-line me-1"></i> All devices are synced — safe to proceed.
                </div>
                <label class="form-label fw-semibold" style="font-size:12px;">Enter your password to confirm</label>
                <input type="password" class="form-control" id="rectifyPassword" placeholder="Password" autocomplete="off">
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="rectifySubmitBtn"><i class="ri-check-line"></i> Rectify Now</button>
            </div>
        </div>
    </div>
</div>

{{-- ══ PENDING DEVICES MODAL — shown if sync check finds pending ═══════ --}}
<div class="modal fade" id="pendingDevicesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#d97706,#f59e0b);padding:12px 18px !important;border-bottom:none;">
                <h5 class="modal-title mh-title"><i class="ri-wifi-off-line"></i> Devices Still Pending</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;font-size:13px;">
                <p>The following devices still have unsynced work for <strong>{{ $branchName }}</strong> on <strong>{{ $displayDate }}</strong>. Ask those devices to sync before rectifying.</p>
                <ul id="pendingDevicesList" style="font-size:12.5px;padding-left:18px;"></ul>
                <div class="alert alert-warning border-0 mt-3 py-2 px-3" style="font-size:11.5px;border-radius:6px;">
                    <i class="ri-alert-line me-1"></i> POS devices: the operator needs to sync any offline sales before rectification can proceed.
                </div>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">OK, I'll wait</button>
                <button type="button" class="btn btn-warning btn-sm" id="syncCheckAgainBtn" onclick="$('#pendingDevicesModal').modal('hide');checkSyncStatus();">
                    <i class="ri-refresh-line me-1"></i> Re-check
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══ INFO MODAL ═══════════════════════════════════════════════════════ --}}
<div class="modal fade" id="fstInfoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Actions &amp; Info</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;font-size:13px;line-height:1.6;">
                <ul class="mb-3" style="padding-left:18px;">
                    <li style="margin-bottom:8px;"><strong>Sales made after counting are handled automatically.</strong> If a product gets sold after it's already been counted, the system adjusts for it — you don't need to recount it.</li>
                    <li style="margin-bottom:8px;">The stats table shows a <strong>live preview</strong> using the expected stock snapshotted at count time. Final adjusted figures are written when you run rectification.</li>
                    <li style="margin-bottom:8px;"><strong>Sync gate:</strong> Click "Check Sync Status" to see every known device (stocktaking and POS) for this branch and date. The Rectify button only unlocks once all devices show zero pending operations.</li>
                    <li style="margin-bottom:8px;"><strong>POS devices</strong> report their offline queue when the POS operator syncs on that device. Ask any POS operators to sync before you rectify.</li>
                    <li style="margin-bottom:8px;">Rectification is <strong>irreversible and locks everything</strong> for this branch and date — counting, merging, and missing-product entry all close immediately.</li>
                    <li style="margin-bottom:8px;">Even after rectifying, corrections via Merged Data's offline sync are still applied and stay mathematically consistent.</li>
                </ul>
                <div class="alert alert-warning border-0" style="font-size:12px;border-radius:6px;">
                    <i class="ri-alert-line me-1"></i> If a device never shows up in the sync panel, it has never reported in — either it hasn't merged yet, or it's offline. Use "Check Sync Status" periodically until all expected devices appear.
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script>
'use strict';

const AI_BRANCH_ID = '{{ $branchId }}';
const AI_DATE      = '{{ $date }}';
let syncAllClear   = false;

function aiCsrf() { return document.querySelector('meta[name="csrf-token"]').getAttribute('content'); }

// ── SYNC STATUS CHECK ─────────────────────────────────────────────────
function checkSyncStatus() {
    if (!AI_BRANCH_ID) return;

    const btn = document.getElementById('syncCheckBtn');
    if (btn) { btn.classList.add('checking'); btn.innerHTML = '<i class="ri-loader-4-line"></i> Checking...'; }

    fetch('{{ route("retail.operations.fullstocktaking.sync-status") }}?branch_id=' + AI_BRANCH_ID + '&date=' + AI_DATE, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': aiCsrf() },
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (btn) { btn.classList.remove('checking'); btn.innerHTML = '<i class="ri-refresh-line"></i> Re-check Sync Status'; }

        renderDeviceList(data.devices || []);

        if (data.can_rectify) {
            syncAllClear = true;
            var card = document.getElementById('syncGateCard');
            if (card) { card.classList.add('all-clear'); card.querySelector('.ai-sync-title').innerHTML = '<i class="ri-shield-check-line"></i> All Devices Synced'; }

            var rectCard = document.getElementById('rectifyCard');
            if (rectCard) { rectCard.classList.remove('sync-blocked'); rectCard.classList.add('ai-rect-card'); }

            var rectBtn = document.getElementById('rectifyOpenBtn');
            if (rectBtn) { rectBtn.style.pointerEvents = ''; rectBtn.style.opacity = ''; rectBtn.innerHTML = '<i class="ri-arrow-right-line me-1"></i> Proceed with Rectification'; }

        } else {
            syncAllClear = false;
            var pendingList = document.getElementById('pendingDevicesList');
            if (pendingList) {
                var pending = (data.devices || []).filter(function(d) { return d.pending_ops_count > 0; });
                pendingList.innerHTML = pending.map(function(d) {
                    return '<li><strong>' + (d.device_label || d.device_id) + '</strong> (' + d.device_type + ') — ' + d.pending_ops_count + ' pending op(s)</li>';
                }).join('');
            }
            $('#pendingDevicesModal').modal('show');
        }
    })
    .catch(function() {
        if (btn) { btn.classList.remove('checking'); btn.innerHTML = '<i class="ri-refresh-line"></i> Check Sync Status'; }
        toastr.error('Could not reach the server. Please retry.', 'Network Error');
    });
}

function renderDeviceList(devices) {
    var list = document.getElementById('deviceList');
    if (!list) return;
    if (!devices.length) {
        list.innerHTML = '<li style="color:#94a3b8;font-size:12px;font-style:italic;"><span>No devices have reported in yet for this session.</span></li>';
        return;
    }
    list.innerHTML = devices.map(function(d) {
        var isSynced = d.pending_ops_count === 0;
        return '<li>' +
            '<span>' +
                '<span class="device-pill ' + (d.device_type === 'pos' ? 'pos' : 'stk') + '">' + d.device_type.toUpperCase() + '</span>' +
                '<span style="margin-left:6px;font-weight:600;">' + (d.device_label || d.device_id) + '</span>' +
            '</span>' +
            '<span class="device-pill ' + (isSynced ? 'synced' : 'pending') + '">' +
                (isSynced ? 'Synced' : d.pending_ops_count + ' pending') +
            '</span>' +
        '</li>';
    }).join('');
}

// ── OPEN RECTIFY MODAL ────────────────────────────────────────────────
document.getElementById('rectifyOpenBtn')?.addEventListener('click', function(e) {
    e.preventDefault();
    if (!syncAllClear) {
        toastr.warning('Check sync status first — rectification requires all devices to be fully synced.', 'Sync Required');
        return;
    }
    document.getElementById('rectifyPassword').value = '';
    var banner = document.getElementById('syncConfirmBanner');
    if (banner) banner.style.display = '';
    $('#rectifyModal').modal('show');
});

// ── SUBMIT RECTIFICATION ──────────────────────────────────────────────
document.getElementById('rectifySubmitBtn')?.addEventListener('click', function() {
    var btn = this;
    if (btn.disabled) return;

    var password = document.getElementById('rectifyPassword').value;
    if (!password) {
        toastr.warning('Please enter your password.', 'Password Required');
        return;
    }

    btn.disabled = true;
    var originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="ri-loader-4-line"></i> Rectifying...';

    fetch('{{ route("retail.operations.fullstocktaking.rectify") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': aiCsrf()
        },
        body: new URLSearchParams({ branch_id: AI_BRANCH_ID, date: AI_DATE, password: password }),
    })
    .then(function(r) {
        var httpStatus = r.status;
        return r.json()
            .catch(function() { return {}; })
            .then(function(d) { return { httpStatus: httpStatus, d: d }; });
    })
    .then(function(result) {
        var httpStatus = result.httpStatus;
        var d = result.d;

        console.log('[Rectify] status=' + httpStatus, d);

        if (httpStatus === 201) {
            // ── SUCCESS ───────────────────────────────────────────────
            $('#rectifyModal').modal('hide');
            toastr.success('Stocktaking rectified successfully. Reloading...', 'Rectified!');
            setTimeout(function() { location.reload(); }, 1800);
            return;
        }

        // All non-201 paths re-enable the button
        btn.disabled = false;
        btn.innerHTML = originalHtml;

        if (httpStatus === 401) {
            toastr.error(d.error || 'The password you entered is incorrect.', 'Incorrect Password');

        } else if (httpStatus === 409) {
            // Already rectified — treat as success since the goal is achieved
            $('#rectifyModal').modal('hide');
            toastr.info(d.error || 'This date has already been rectified.', 'Already Rectified');
            setTimeout(function() { location.reload(); }, 1800);

        } else if (httpStatus === 423) {
            // Sync changed between check and submit — reset gate
            $('#rectifyModal').modal('hide');
            toastr.warning('A device reported pending work after the sync check. Please re-check and try again.', 'Sync Changed');
            syncAllClear = false;
            var rectBtn = document.getElementById('rectifyOpenBtn');
            if (rectBtn) {
                rectBtn.style.pointerEvents = 'none';
                rectBtn.style.opacity = '.45';
                rectBtn.innerHTML = '<i class="ri-lock-line me-1"></i> Check Device Sync First';
            }
            var gateCard = document.getElementById('syncGateCard');
            if (gateCard) {
                gateCard.classList.remove('all-clear');
                var title = gateCard.querySelector('.ai-sync-title');
                if (title) title.innerHTML = '<i class="ri-wifi-line"></i> Device Sync Status';
            }

        } else if (httpStatus === 422) {
            toastr.error('Validation error — please check all fields.', 'Validation Error');

        } else {
            // 500 or anything unexpected — surface the real message
            var msg = (d && (d.error || d.message)) || ('Rectification failed (HTTP ' + httpStatus + ').');
            toastr.error(msg, 'Error');
        }
    })
    .catch(function(err) {
        console.error('[Rectify] network error:', err);
        btn.disabled = false;
        btn.innerHTML = originalHtml;
        toastr.error('Network error — could not reach the server. Please check your connection and try again.', 'Network Error');
    });
});

// ── INFO MODAL TRIGGERS ───────────────────────────────────────────────
document.getElementById('fstInfoBtn')?.addEventListener('click',  function(e) { e.preventDefault(); $('#fstInfoModal').modal('show'); });
document.getElementById('fstInfoBtn2')?.addEventListener('click', function(e) { e.preventDefault(); $('#fstInfoModal').modal('show'); });

// ── SESSION FLASH MESSAGES ────────────────────────────────────────────
@if(Session::has('message'))
toastr['{{ Session::get("alert-type","info") }}']('{{ Session::get("message") }}');
@endif
</script>
@endsection