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

    /* ── Live stats ──────────────────────────────────────────────────── */
    $totalCount = $zeroCount = $positiveCount = $negativeCount = 0;
    $zeroPercentage = $positivePercentage = $negativePercentage = 0;
    $expectedValue = $foundValue = $positiveValue = $negativeValue = 0;
    $differenceValue = $missingCount = $missingValue = $missingPercentage = $fullDifference = 0;

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
                ->select(
                    'rbp.base_product_id', 'bp.name as product_name', 'bp.unit',
                    DB::raw('COALESCE(rbp.selling_price, bp.selling_price) as price'),
                    'rbp.stock_quantity as quantity',
                    'rbp.batch_number', 'rbp.expiry_date'
                )
                ->get();

            if ($missingToSeed->isNotEmpty()) {
                $now  = now();
                $rows = $missingToSeed->map(fn ($m) => [
                    'date'            => $date,
                    'branch_id'       => $branchId,
                    'base_product_id' => $m->base_product_id,
                    'product_name'    => $m->product_name,
                    'unit'            => $m->unit,
                    'price'           => $m->price ?? 0,
                    'quantity'        => $m->quantity ?? 0,
                    'rate'            => 1.00,
                    'batch_number'    => $m->batch_number,
                    'expiry_date'     => $m->expiry_date,
                    'product_status'  => 'Active',
                    'created_at'      => $now,
                    'updated_at'      => $now,
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
    }
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
/* ── Shared chrome ────────────────────────────────────────────────────── */
.card-header { padding: 0.5rem 1.5rem !important; background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff; border-top-left-radius: 10px; border-top-right-radius: 10px; }
.card-body { padding: 0 !important; }
.card { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; overflow: hidden; }
.card-header h4 { color: #fff; font-weight: 600; margin-bottom: 0; display: flex; align-items: center; }

.card-header .btn-light {
    height: 28px;
    padding: 0 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    font-size: 16px;
}
.card-header .btn-light:hover { background-color: #f8f9fa; transition: background-color 0.2s; }

.tab-header-container { background: #cccccc; border-bottom: 1px solid #b3b3b3; }
.nav-pills .nav-link { border-radius: 0 !important; padding: .65rem 1rem; font-weight: 500; color: #495057; border-bottom: none; transition: all .2s; font-size: 12.5px; }
.nav-pills .nav-link:hover { background: #b8b8b8; color: #4B5EBD; }
.nav-pills .nav-link.active { background: transparent !important; color: #4B5EBD !important; border-bottom: none; font-weight: 600; }
.nav-pills .nav-link i { font-size: 1rem; margin-right: .3rem; }

.fst-action-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #9098a8;
    padding: 8px 14px;
    border-bottom: 1px solid #7a8090;
    gap: 10px;
    flex-wrap: wrap;
}
.fst-left { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; flex-wrap: wrap; }
.fst-right { display: flex; align-items: center; gap: 4px; flex-shrink: 0; }

#fstBranchSelect {
    border: 1.5px solid rgba(255,255,255,0.35);
    background: #9098a8;
    border-radius: 7px;
    padding: 5px 10px;
    font-size: 12.5px;
    font-weight: 600;
    color: #dde0e8;
    max-width: 220px;
    height: 32px;
}

.fst-date-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: rgba(255,255,255,0.12);
    border: 1.5px solid rgba(255,255,255,0.3);
    border-radius: 20px;
    padding: 5px 12px;
    font-size: 12px;
    font-weight: 600;
    color: #dde0e8;
    white-space: nowrap;
    height: 32px;
}
.fst-date-chip .mode-badge { font-size: 9px; padding: 1px 5px; border-radius: 8px; background: rgba(255,255,255,0.2); font-weight: 700; color: #dde0e8; margin-left: 4px; }
.rectified-tag { font-size: 9px; font-weight: 700; background: #d1fae5; color: #065f46; border-radius: 5px; padding: 1px 6px; margin-left: 4px; }

.fst-icon-btn {
    width: 32px;
    height: 32px;
    border-radius: 7px;
    background: rgba(255,255,255,0.12);
    border: 1.5px solid rgba(255,255,255,0.3);
    color: #dde0e8;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 15px;
    text-decoration: none;
}
.fst-icon-btn:hover { background: rgba(255,255,255,0.22); }

/* ── Two-column content layout ───────────────────────────────────────── */
.ai-content-row {
    display: flex;
    gap: 22px;
    align-items: stretch;
    min-height: 320px;
    padding: 4px 0;
}
.ai-left-col {
    flex: 1 1 60%;
    padding: 18px 22px 18px 18px;
    border-right: 1px solid #e8eaf2;
}
.ai-right-col {
    flex: 0 0 38%;
    padding: 18px 18px 18px 4px;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* ── Stats table ─────────────────────────────────────────────────────── */
.stats-table { width: 100%; font-size: 13px; border-collapse: collapse; }
.stats-table th { color: #94a3b8; font-size: 10px; text-transform: uppercase; letter-spacing: .5px; font-weight: 700; padding: 8px 10px; border-bottom: 2px solid #e2e8f0; text-align: left; }
.stats-table th.c, .stats-table td.c { text-align: center; }
.stats-table td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
.stats-table tr.total-row td { font-weight: 800; background: #f4f6ff; border-top: 2px solid #c5caec; }
.stats-icon { color: #4B5EBD; margin-right: 6px; }

/* ── Sales-netting protection badge ──────────────────────────────────── */
.ai-protection-banner {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    background: #ecfdf5;
    border: 1.5px solid #a7f3d0;
    border-radius: 9px;
    padding: 12px 14px;
    margin-bottom: 18px;
}
.ai-protection-banner .ai-pb-icon {
    width: 30px; height: 30px; border-radius: 50%;
    background: #16a34a; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; flex-shrink: 0;
}
.ai-protection-banner .ai-pb-title {
    font-size: 12.5px; font-weight: 700; color: #065f46; margin-bottom: 3px;
    display: flex; align-items: center; gap: 6px;
}
.ai-protection-banner .ai-pb-body { font-size: 11.5px; color: #0f5132; line-height: 1.5; }
.ai-protection-banner .ai-pb-badge {
    display: inline-flex; align-items: center; gap: 3px;
    font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px;
    background: #16a34a; color: #fff; border-radius: 10px; padding: 2px 7px; margin-left: 6px;
    vertical-align: middle;
}

/* ── Right column: download card ─────────────────────────────────────── */
.ai-card {
    background: #f8f9fc;
    border: 1px solid #e4e7f5;
    border-radius: 10px;
    padding: 16px;
}
.ai-card-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: #94a3b8;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.ai-dl-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    border: 1px solid #d8ddf0;
    background: #fff;
    cursor: pointer;
    text-decoration: none;
    margin-bottom: 10px;
    transition: border-color 0.15s, background 0.15s;
}
.ai-dl-btn:last-child { margin-bottom: 0; }
.ai-dl-btn:hover { border-color: #4B5EBD; background: #eff3ff; }
.ai-dl-btn i { font-size: 20px; color: #4B5EBD; flex-shrink: 0; }
.ai-dl-btn .dl-text { flex: 1; }
.ai-dl-btn .dl-label { font-size: 12.5px; font-weight: 600; color: #1e293b; display: block; line-height: 1.2; }
.ai-dl-btn .dl-sub   { font-size: 11px; color: #64748b; display: block; margin-top: 2px; }

/* ── Right column: rectification card ───────────────────────────────── */
.ai-rect-card {
    background: #fef2f2;
    border: 1.5px solid #fca5a5;
    border-radius: 10px;
    padding: 16px;
    margin-top: 4px;
}
.ai-rect-card.locked {
    background: #f3f4f6;
    border-color: #d1d5db;
}
.ai-rect-title {
    font-size: 12px;
    font-weight: 700;
    color: #b91c1c;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 8px;
}
.ai-rect-card.locked .ai-rect-title { color: #374151; }
.ai-rect-body {
    font-size: 12px;
    color: #7f1d1d;
    line-height: 1.55;
    margin-bottom: 12px;
}
.ai-rect-card.locked .ai-rect-body { color: #4b5563; margin-bottom: 0; }

/* ── Modal headers ──────────────────────────────────────────────────── */
.mh-blue   { background: linear-gradient(135deg,#4B5EBD,#576CC0); padding: 12px 18px !important; border-bottom: none; }
.mh-danger { background: linear-gradient(135deg,#dc2626,#ef4444); padding: 12px 18px !important; border-bottom: none; }
.mh-title  { color: #fff; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
.mh-close  { filter: brightness(0) invert(1); opacity: .8; } .mh-close:hover { opacity: 1; }
.info-list li { margin-bottom: 8px; }

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
            <div style="padding:40px;text-align:center;color:#94a3b8;"><i class="ri-store-2-line" style="font-size:40px;display:block;margin-bottom:10px;"></i>Select a branch above.</div>
        @else
        <div class="ai-content-row">

            {{-- ── LEFT: Stats ─────────────────────────────────────────── --}}
            <div class="ai-left-col">

                <div class="ai-protection-banner">
                    <div class="ai-pb-icon"><i class="ri-shield-check-line"></i></div>
                    <div>
                        <div class="ai-pb-title">
                            Sales made after counting are automatically handled
                            <span class="ai-pb-badge"><i class="ri-checkbox-circle-fill"></i> Active</span>
                        </div>
                        <div class="ai-pb-body">
                            Every product is timestamped with a sales-sequence marker the moment it's counted. At rectification, any sale that happened <strong>after</strong> that point is automatically subtracted from the expected figure — so staff can keep selling on the floor while stocktaking is in progress, and the final numbers above already account for it. No manual adjustment is needed.
                        </div>
                    </div>
                </div>

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
                        <tr><td>Difference (FV - EV)</td><td class="c">{{ number_format($differenceValue, 2) }}</td><td class="c">—</td></tr>
                        <tr><td>Missing items</td><td class="c">{{ $missingCount }}</td><td class="c">{{ $missingPercentage }}</td></tr>
                        <tr><td>Missing value</td><td class="c">{{ number_format($missingValue, 2) }}</td><td class="c">—</td></tr>
                        <tr class="total-row"><td>Full difference (FV - (EV + MV))</td><td class="c">{{ number_format($fullDifference, 2) }}</td><td class="c">—</td></tr>
                    </tbody>
                </table>
            </div>

            {{-- ── RIGHT: Downloads + Rectification ───────────────────── --}}
            <div class="ai-right-col">

                <div class="ai-card">
                    <div class="ai-card-title"><i class="ri-download-2-line"></i> Download Report</div>
                    <form action="{{ route('retail.operations.fullstocktaking.report.full') }}" method="POST" target="_blank">
                        @csrf
                        <input type="hidden" name="branch_id" value="{{ $branchId }}">
                        <input type="hidden" name="date" value="{{ $date }}">
                        <button type="submit" class="ai-dl-btn w-100">
                            <i class="ri-file-chart-line"></i>
                            <span class="dl-text">
                                <span class="dl-label">Full Report</span>
                                <span class="dl-sub">All data — counts, expected, differences, missing</span>
                            </span>
                        </button>
                    </form>
                    <form action="{{ route('retail.operations.fullstocktaking.report.delivery') }}" method="POST" target="_blank">
                        @csrf
                        <input type="hidden" name="branch_id" value="{{ $branchId }}">
                        <input type="hidden" name="date" value="{{ $date }}">
                        <button type="submit" class="ai-dl-btn w-100">
                            <i class="ri-file-list-3-line"></i>
                            <span class="dl-text">
                                <span class="dl-label">Stock Delivery Note</span>
                                <span class="dl-sub">Product · Unit · Price · Qty — suitable as a delivery note</span>
                            </span>
                        </button>
                    </form>
                </div>

                @if($isRectified)
                <div class="ai-rect-card locked">
                    <div class="ai-rect-title"><i class="ri-shield-check-line" style="color:#16a34a;"></i> Already Rectified</div>
                    <div class="ai-rect-body">This date has already been rectified for {{ $branchName }}. Figures above and in History reflect the final, sales-netted result. Corrections can still be made via the Merged Data tab's offline sync.</div>
                </div>
                @else
                <div class="ai-rect-card">
                    <div class="ai-rect-title"><i class="ri-alert-line"></i> Rectification</div>
                    <div class="ai-rect-body">Replaces live stock quantities at <strong>{{ $branchName }}</strong> with counted figures (already adjusted for sales made after each count), and writes a permanent history record. <strong>Rectifying locks everything for this branch and date</strong> — counting, merging, and missing-product entry all close immediately. <strong>This action is irreversible.</strong></div>
                    <a href="#" class="btn btn-danger btn-sm w-100" id="rectifyOpenBtn"><i class="ri-arrow-right-line me-1"></i> Proceed with Rectification</a>
                </div>
                @endif

            </div>
        </div>
        @endif
    </div>
</div>
</div></div></div>

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
                    <strong>This action is permanent and cannot be undone.</strong> Live stock at <strong>{{ $branchName }}</strong> will be overwritten with the counted figures for <strong>{{ $displayDate }}</strong>, and counting/merging/missing-products entry for this branch and date will lock immediately.
                </div>
                <p style="font-size:13px;color:#475569;margin-bottom:14px;">Sales that occurred after each product was counted have already been netted out automatically using sale sequence numbers (not timestamps), so the figures you see are final and account for floor sales made during counting, regardless of clock differences across POS devices. A permanent history record will be written.</p>
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
                    <li style="margin-bottom:8px;"><strong>Sales made after a product is counted are handled automatically.</strong> Each product is stamped with a sales-sequence marker at the moment it's counted; at rectification, sales that happened after that marker are subtracted from the expected figure. Selling continues uninterrupted on the floor during stocktaking.</li>
                    <li style="margin-bottom:8px;">The stats table shows a <strong>live preview</strong> using the expected stock snapshotted at count time. Final figures — already netted for post-count sales — are written when you run rectification.</li>
                    <li style="margin-bottom:8px;">Once rectified, the figures above reflect the sales-netted result and stay current with any offline edits synced via Merged Data.</li>
                    <li style="margin-bottom:8px;"><strong>Full Report</strong> includes all counted lines, expected values, differences, and missing products.</li>
                    <li style="margin-bottom:8px;"><strong>Stock Delivery Note</strong> is a simplified product/unit/price/qty list, useful as a receiving note when restocking a branch.</li>
                    <li style="margin-bottom:8px;">Rectification is <strong>irreversible and locks everything</strong> for this branch and date — counting, merging, and missing-product entry all close immediately.</li>
                </ul>
                <div class="alert alert-warning border-0" style="font-size:12px;border-radius:6px;">
                    <i class="ri-alert-line me-1"></i> Even after rectifying, corrections via Merged Data's offline sync are still applied — they automatically re-run the sales-netting math so figures stay consistent.
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script>
document.getElementById('rectifyOpenBtn')?.addEventListener('click', function (e) {
    e.preventDefault();
    document.getElementById('rectifyPassword').value = '';
    $('#rectifyModal').modal('show');
});

document.getElementById('rectifySubmitBtn')?.addEventListener('click', function () {
    var password = document.getElementById('rectifyPassword').value;
    if (!password) { toastr.warning('Please enter your password.'); return; }
    var btn = this; btn.disabled = true;
    fetch('{{ route("retail.operations.fullstocktaking.rectify") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: new URLSearchParams({ branch_id: '{{ $branchId }}', date: '{{ $date }}', password }),
    })
    .then(r => r.json().then(d => ({ status: r.status, d })))
    .then(({ status, d }) => {
        btn.disabled = false;
        if (status === 201) { toastr.success(d.success, 'Rectified'); $('#rectifyModal').modal('hide'); setTimeout(() => location.reload(), 1200); }
        else if (status === 401) { toastr.error(d.error, 'Incorrect Password'); }
        else if (status === 409) { toastr.error(d.error, 'Already Done'); }
        else { toastr.error(d.error || 'Rectification failed.', 'Error'); }
    })
    .catch(() => { btn.disabled = false; toastr.error('Could not reach the server.', 'Network Error'); });
});

document.getElementById('fstInfoBtn')?.addEventListener('click', function (e) { e.preventDefault(); $('#fstInfoModal').modal('show'); });

@if(Session::has('message'))
toastr['{{ Session::get("alert-type","info") }}']('{{ Session::get("message") }}');
@endif
</script>
@endsection