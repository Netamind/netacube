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

    $missingProducts = collect();
    $missingValue    = 0;

    if ($branchId) {
        // ── 1. Which base_product_ids have already been counted for this branch+date? ──
        // This must be computed BEFORE the seed check, and used to purge the missing
        // table unconditionally — seeding is one-time, but counting/merging happens
        // continuously afterward, so a product counted *after* the initial seed must
        // still disappear from "missing" the moment it's counted.
        $countedIds = DB::connection('tenant')->table('retail_fullstocktaking')
            ->where('branch_id', $branchId)->where('date', $date)
            ->pluck('base_product_id');

        // ── 2. ALWAYS purge any missing-product row whose product has since been counted. ──
        // This is the fix: previously this cleanup only existed in an unused partial and
        // was never run on this page, so once a product was seeded as "missing" it stayed
        // missing forever even after being counted and merged.
        if ($countedIds->isNotEmpty()) {
            DB::connection('tenant')->table('retail_fullstocktaking_missing_products')
                ->where('branch_id', $branchId)
                ->where('date', $date)
                ->whereIn('base_product_id', $countedIds)
                ->delete();
        }

        // ── 3. One-time seed: insert rows for products with NO missing-table row yet. ──
        // Changed from a branch+date-level "has any row at all" check to an
        // explicit "skip ids that already have a missing-table row" check, so that
        // re-seeding can safely run on every page load without duplicating rows,
        // and newly-uncounted products (e.g. added to the branch after the first
        // seed) still get picked up.
        $alreadyMissingIds = DB::connection('tenant')->table('retail_fullstocktaking_missing_products')
            ->where('branch_id', $branchId)->where('date', $date)
            ->pluck('base_product_id');

        $excludeIds = $countedIds->merge($alreadyMissingIds)->unique();

        $missingToSeed = DB::connection('tenant')
            ->table('retail_branch_products as rbp')
            ->join('retail_base_products as bp', 'bp.id', '=', 'rbp.base_product_id')
            ->where('rbp.branch_id', $branchId)
            ->whereNotIn('rbp.base_product_id', $excludeIds)
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

        // ── 4. Read the final, reconciled list. ──
        $missingProducts = DB::connection('tenant')->table('retail_fullstocktaking_missing_products')
            ->where('branch_id', $branchId)
            ->where('date', $date)
            ->orderBy('product_name')
            ->get();

        $missingValue = $missingProducts->sum(fn ($m) => $m->quantity * $m->price);
    }

    $title = ($branchName ?? 'Branch') . ' Missing Products ' . $displayDate;
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
/* ══════════════════════════════════════════════════════════════
   MISSING PRODUCTS — matches new FST layout exactly
══════════════════════════════════════════════════════════════ */

.content-page > .content > .container-fluid { padding-top: 16px; }

/* ── Outer card — content-driven height, no longer pinned to viewport ── */
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

/* ── Left cluster of silver bar: date chip + FS Actions ── */
.fst-hdr-left { display: flex; align-items: center; gap: 8px; min-width: 0; }

/* ── FS Actions — silver-bar styled pill, sits right of the date chip ── */
#fstActionsBtn {
    height: 28px; padding: 0 10px; border-radius: 4px;
    border: 1px solid rgba(102,102,102,.35); background: rgba(255,255,255,.35);
    color: #555555; font-size: 12px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 5px; cursor: pointer;
    white-space: nowrap; text-decoration: none; flex-shrink: 0;
}
#fstActionsBtn i { font-size: 14px; }
#fstActionsBtn:hover { background: rgba(255,255,255,.6); color: #333333; }

/* ── Right cluster of silver bar: summary / history / download / info ── */
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

/* ── Locked branch select — shown as-is, but not interactive ── */
#fstBranchSelect:disabled {
    opacity: 1;
    color: silver;
    -webkit-text-fill-color: silver;
    cursor: not-allowed;
}

.fst-rectified-tag {
    font-size: 10px; font-weight: 700; background: #d1fae5; color: #065f46;
    border-radius: 5px; padding: 3px 8px; display: inline-flex; align-items: center; gap: 4px; flex-shrink: 0;
}

/* ── Right side of blue bar: page name + sync ── */
.fst-branch-right {
    display: flex; align-items: center; gap: 8px; flex-shrink: 0;
}
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
.fst-pending-badge {
    position: absolute; top: -5px; right: -5px;
    background: #dc2626; color: #fff; font-size: 9px; font-weight: 700;
    min-width: 16px; height: 16px; border-radius: 8px;
    display: none; align-items: center; justify-content: center;
    padding: 0 4px; border: 1px solid #fff;
}
.fst-pending-badge.show { display: flex; }

/* ── Card body — white card flush with the blue bar, content-driven height ── */
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

/* ── Table area — wraps its content; scrolls only past a sane max height ── */
.fst-table-wrap {
    flex: 0 1 auto; min-height: 0;
    overflow-x: auto;
    padding: 0 1.5rem 1.5rem 1.5rem;
}
.fst-table-wrap table.dataTable { margin-top: 0 !important; }

/* ── DataTable export buttons ── */
.dt-buttons .btn {
    background: transparent !important; background-image: none !important;
    box-shadow: none !important; border-color: #5bc0de; color: #5bc0de;
}
.dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }

#missingProductsTable thead th,
table.dataTable thead th { text-align: center !important; vertical-align: middle !important; }
#missingProductsTable thead th:first-child,
table.dataTable thead th:first-child { text-align: left !important; }
#missingProductsTable tbody td,
table.dataTable tbody td { text-align: center !important; vertical-align: middle !important; }
#missingProductsTable tbody td:first-child,
table.dataTable tbody td:first-child { text-align: left !important; }

.mp-qty-input {
    width: 90px; text-align: center; border: 1px solid #c5caec;
    border-radius: 5px; padding: 4px 6px; font-weight: 700; color: #1d4ed8;
}
.mp-qty-input:focus { outline: 2px solid #4B5EBD; border-color: #4B5EBD; }
.mp-dirty { background: #fffbeb !important; border-color: #f59e0b !important; }

.action-icon { cursor: pointer; padding: 4px 6px; border-radius: 6px; }
.action-icon:hover { background: #f0f0f0; }

/* ── Modal headers ── */
.mh-pos {
    background: linear-gradient(to right, #4B5EBD, #576CC0);
    padding: 10px 16px !important; border-bottom: none;
}
.mh-pos-title { color: #fff; font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
.mh-amber { background: linear-gradient(135deg,#d97706,#f59e0b); padding: 12px 18px !important; border-bottom: none; }
.mh-title { color: #fff; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 6px; }
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
.fst-action-link .fal-icon { font-size: 20px; flex-shrink: 0; }
.fst-action-link .fal-title { font-size: 13px; font-weight: 600; }
.fst-action-link .fal-sub   { font-size: 11px; color: #64748b; }
.fst-action-link .fal-arrow { margin-left: auto; color: #94a3b8; font-size: 18px; flex-shrink: 0; }

/* ── Stats rows ── */
.mp-stat-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 13px; }
.mp-stat-row:last-child { border-bottom: none; }
.mp-stat-row .lbl { color: #64748b; }
.mp-stat-row .val { font-weight: 700; color: #1e293b; }

/* ── Date modal ── */
.date-mode-toggle { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px; }
.dmc { padding: 10px 12px; border-radius: 8px; border: 1px solid #e2e8f0; cursor: pointer; }
.dmc.active-sys { border-color: #4B5EBD; background: #eff3ff; }
.dmc.active-cus { border-color: #d97706; background: #fffbeb; }
.dmc-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
.dmc-val { font-size: 13px; font-weight: 600; color: #64748b; }
.dmc.active-sys .dmc-val { color: #4B5EBD; }
.dmc.active-cus .dmc-val { color: #d97706; }

/* ── Spinner removal ── */
input[type=number]::-webkit-outer-spin-button,
input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
input[type=number] { -moz-appearance: textfield; }

/* ── MOBILE ── */
@media (max-width: 768px) {
    .fst-card { height: auto !important; margin: 8px; }
    .content-page { padding: 0 !important; }
    .content { padding: 0 !important; }
    .content-page > .content > .container-fluid { padding-top: 0 !important; padding-left: 0 !important; padding-right: 0 !important; }
    .fst-table-wrap { padding: 0 10px 12px; }
    .modal-dialog { margin: 1.25rem auto !important; max-width: calc(100% - 24px) !important; }
    .modal-content { border-radius: 10px !important; }

    /* FS Actions keeps its text label on mobile too */
    #fstActionsBtn { font-size: 11px; padding: 0 8px; height: 26px; }

    /* tighten up the extra header icons so everything fits */
    .fst-card-header { gap: 4px; }
    .fst-hdr-actions { gap: 0; }
    .fst-hdr-btn { width: 24px; height: 24px; font-size: 15px; padding: 0; }
    .fst-hdr-divider { margin: 0 3px; }

    .fst-branch-row { gap: 6px; }
    .fst-page-label { font-size: 11px; }
    .fst-bar-icon-btn { height: 26px; width: 26px; font-size: 15px; }
}
</style>

{{-- Progress bar --}}
<div class="progress" id="progressBar" role="progressbar"
     style="height:8px;transform:rotate(180deg);display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">

<div class="fst-card">

    {{-- ── Silver header: date chip + FS Actions (left) + summary / history / download / info (right) ── --}}
    <div class="fst-card-header">
        <div class="fst-hdr-left">
            <button type="button" id="fstDateChip"
                    class="{{ $isCustom ? 'custom-mode' : '' }}"
                    title="Change stocktaking date">
                <i class="ri-calendar-line"></i> {{ $displayDate }}
                <span class="fst-mode-tag">{{ $isCustom ? 'Custom' : 'Today' }}</span>
                <i class="ri-pencil-line fst-edit-pencil"></i>
            </button>

            <button type="button" id="fstActionsBtn"
                    onclick="$('#fstNavActionsModal').modal('show')"
                    title="Quick navigation">
                <i class="ri-layout-grid-line"></i> <span class="fab-label">FS Actions</span>
            </button>
        </div>

        <div class="fst-hdr-actions">
            @if($branchId)
                <button type="button" class="fst-hdr-btn" id="mpStatsBtn" title="Missing Products Summary">
                    <i class="ri-bar-chart-2-line"></i>
                </button>
                <span class="fst-hdr-divider"></span>
            @endif

            @if($branchId)
                <span class="fst-hdr-divider"></span>
                <button type="button" class="fst-hdr-btn" id="mpDownloadBtn" title="Download">
                    <i class="ri-download-line"></i>
                </button>
            @endif

            <span class="fst-hdr-divider"></span>
            <button type="button" class="fst-hdr-btn" title="About Missing Products"
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
                <select name="branch_id" id="fstBranchSelect" disabled
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
            <span class="fst-page-label">Missing Products</span>
            <a href="#" class="fst-bar-icon-btn" id="mpSyncBtn" title="Sync offline changes">
                <i class="ri-upload-cloud-2-line"></i>
                <span class="fst-pending-badge" id="mpPendingBadge"></span>
            </a>
        </div>
    </div>

    {{-- ── Body ── --}}
    <div class="fst-card-body">
        @if(!$branchId)
            <div class="fst-placeholder-wrap">
                <i class="ri-store-2-line"></i>
                <h5>No Branch Selected</h5>
                <p style="font-size:13px;">Select a branch from the bar above to view missing products.</p>
            </div>
        @elseif($missingProducts->isEmpty())
            <div class="fst-placeholder-wrap">
                <i class="ri-checkbox-circle-line" style="color:#16a34a;"></i>
                <h5 style="color:#16a34a;">All Products Accounted For</h5>
                <p style="font-size:13px;">No missing products for {{ $branchName }} on {{ $displayDate }}.</p>
            </div>
        @else
        <div class="fst-table-wrap">
            <table id="missingProductsTable" class="table table-sm table-striped row-border order-column w-100">
                <thead style="background-color:#e2e2e9">
                    <tr>
                        <th>Product</th>
                        <th>Unit</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="mpTbody">
                    @foreach($missingProducts as $m)
                    <tr id="mprow{{ $m->id }}" data-id="{{ $m->id }}">
                        <td>{{ $m->product_name }}</td>
                        <td>{{ $m->unit }}</td>
                        <td>{{ number_format($m->price, 2) }}</td>
                        <td data-order="{{ number_format($m->quantity, 2, '.', '') }}">
                            <input type="number" class="mp-qty-input"
                                   id="mpqty{{ $m->id }}"
                                   value="{{ number_format($m->quantity, 2, '.', '') }}"
                                   data-original="{{ number_format($m->quantity, 2, '.', '') }}">
                        </td>
                        <td>
                            <i class="ri-delete-bin-line action-icon text-danger mpDeleteBtn"
                               data-id="{{ $m->id }}"
                               data-product="{{ $m->product_name }}"></i>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
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
<div class="modal fade" id="mpStatsModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:380px;">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title">
                    <i class="ri-bar-chart-2-line"></i> Missing Products Summary
                </h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <div class="mp-stat-row"><span class="lbl">Branch</span><span class="val">{{ $branchName }}</span></div>
                <div class="mp-stat-row"><span class="lbl">Date</span><span class="val">{{ $displayDate }}</span></div>
                <div class="mp-stat-row"><span class="lbl">Missing products</span><span class="val">{{ $missingProducts->count() }}</span></div>
                <div class="mp-stat-row"><span class="lbl">Missing value</span><span class="val">MWK {{ number_format($missingValue, 2) }}</span></div>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


{{-- ══ DOWNLOAD MODAL ══ --}}
<div class="modal fade" id="mpDownloadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title">
                    <i class="ri-download-line"></i> Download Missing Products
                </h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <p style="font-size:13px;color:#475569;margin-bottom:14px;">
                    Download missing-product data or generate a PDF report.
                </p>
                <div class="dt-buttons mb-3" id="missingProductsButtons"></div>
                <hr style="margin:14px 0;">
                <form action="{{ route('retail.operations.fullstocktaking.report.missing-products') }}"
                      method="POST" target="_blank">
                    @csrf
                    <input type="hidden" name="branch_id" value="{{ $branchId }}">
                    <input type="hidden" name="date" value="{{ $date }}">
                    <button type="submit"
                            style="display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;border:1px solid #d8ddf0;background:#fff;cursor:pointer;width:100%;text-align:left;">
                        <i class="ri-file-pdf-2-line" style="font-size:20px;color:#4B5EBD;flex-shrink:0;"></i>
                        <span style="flex:1;">
                            <span style="font-size:12.5px;font-weight:600;color:#1e293b;display:block;line-height:1.2;">Missing Products PDF Report</span>
                            <span style="font-size:11px;color:#64748b;display:block;margin-top:2px;">All missing products for this branch and date</span>
                        </span>
                    </button>
                </form>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


{{-- ══ DELETE CONFIRM MODAL ══ --}}
<div class="modal fade" id="mpDeleteModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:380px;margin:1.75rem auto;">
        <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
            <div class="modal-body text-center pb-4">
                <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
                <h4 class="mt-2">Remove <span id="mpDeleteProductLabel" class="text-danger"></span>?</h4>
                <h5 style="font-size:13px;color:#64748b;font-weight:400;">
                    This deletes the missing-product entry only — it does not affect live branch stock.
                </h5>
                <a href="#" class="btn btn-danger me-2 mt-3" id="mpDeleteConfirmBtn">Yes, Remove</a>
                <a href="#" class="btn btn-info mt-3" data-bs-dismiss="modal">Cancel</a>
            </div>
        </div>
    </div>
</div>


{{-- ══ SYNC CONFIRM MODAL ══ --}}
<div class="modal fade" id="mpSyncModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-amber">
                <h5 class="modal-title mh-title">
                    <i class="ri-upload-cloud-2-line"></i> Sync Offline Changes
                </h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;font-size:13px;">
                <p>You have <strong id="mpSyncCount">0</strong> unsynced change(s) on this device.
                   Sync now to upload them and reload with the latest data?</p>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Not Yet</button>
                <button type="button" class="btn btn-primary btn-sm" id="mpSyncConfirmBtn">
                    <i class="ri-upload-cloud-2-line"></i> Sync Now
                </button>
            </div>
        </div>
    </div>
</div>
@endif


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
                    <i class="ri-information-line"></i> About Missing Products
                </h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;font-size:13px;line-height:1.6;">
                <ul style="padding-left:18px;margin:0;display:flex;flex-direction:column;gap:10px;">
                    <li>Products at this branch that were <strong>never counted</strong> during this stocktake appear here automatically, seeded from current branch stock the first time this tab is opened for the date.</li>
                    <li>Edits and deletes here work <strong>offline</strong> — change a quantity, it's queued on this device, then tap the cloud icon to sync.</li>
                    <li>Removing an entry here only deletes the missing-product record — it does not affect live branch stock.</li>
                    <li>As soon as a product is counted and merged, it is automatically removed from this list — you never need to remove it manually.</li>
                    <li>Tap the chart icon in the top bar to view the current missing count and its value at present quantities.</li>
                </ul>
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

const MP_BRANCH_ID = '{{ $branchId }}';
const MP_DATE      = '{{ $date }}';
const MP_QUEUE_KEY = 'fullstocktaking_missing_products_queue_' + MP_BRANCH_ID + '_' + MP_DATE;

let mpQueue = [];

function mpUuid() { return 'mp_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 9); }
function mpCsrf() { return document.querySelector('meta[name="csrf-token"]').getAttribute('content'); }
function loadMpQueue()  { try { mpQueue = JSON.parse(localStorage.getItem(MP_QUEUE_KEY) || '[]'); } catch (e) { mpQueue = []; } }
function saveMpQueue()  { localStorage.setItem(MP_QUEUE_KEY, JSON.stringify(mpQueue)); updateMpBadge(); }

function updateMpBadge() {
    const badge = document.getElementById('mpPendingBadge');
    const btn   = document.getElementById('mpSyncBtn');
    if (!badge || !btn) return;
    if (mpQueue.length > 0) { badge.textContent = mpQueue.length; badge.classList.add('show'); btn.classList.add('has-pending'); }
    else { badge.classList.remove('show'); btn.classList.remove('has-pending'); }
}

function mpQueueUpdate(rowId, quantity) {
    const idx = mpQueue.findIndex(op => op.type === 'update' && op.id === rowId);
    const op  = { client_uuid: mpUuid(), type: 'update', id: rowId, quantity };
    if (idx >= 0) { mpQueue[idx] = op; } else { mpQueue.push(op); }
    saveMpQueue();
}
function mpQueueDelete(rowId) {
    mpQueue = mpQueue.filter(op => op.id !== rowId);
    mpQueue.push({ client_uuid: mpUuid(), type: 'delete', id: rowId });
    saveMpQueue();
}

function showProgress(v) { document.getElementById('progressBar').style.display = v ? '' : 'none'; }

$(document).ready(function () {
    loadMpQueue(); updateMpBadge();

    @if($branchId && $missingProducts->isNotEmpty())

    var table = $('#missingProductsTable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        pageLength: 100,
        lengthMenu: [[50, 100, 250, -1], [50, 100, 250, 'All']],
        fixedColumns: { leftColumns: 1 },
        scrollX: true,
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: 0,      className: 'text-start'  }
        ],
        buttons: [
            { extend: 'excelHtml5', title: @json($title), text: 'Excel', exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'csvHtml5',   title: @json($title), text: 'CSV',   exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'pdfHtml5',   title: @json($title), text: 'PDF',   exportOptions: { columns: ':visible:not(:last-child)' } },
        ]
    });
    table.buttons().container().appendTo('#missingProductsButtons');
    window._mpTable = table;

    // Replay pending queue onto the table visually
    mpQueue.forEach(function (op) {
        if (op.type === 'update') {
            var $inp = $('#mpqty' + op.id);
            if ($inp.length) {
                var qtyVal = parseFloat(op.quantity);
                $inp.val(qtyVal.toFixed(2)).addClass('mp-dirty');
                $inp.closest('td').attr('data-order', qtyVal);
                table.cell($inp.closest('td')).invalidate('dom');
            }
        }
        if (op.type === 'delete') {
            var row = document.getElementById('mprow' + op.id);
            if (row) row.style.opacity = '0.4';
        }
    });

    // Qty change → queue update
    $(document).on('change', '.mp-qty-input', function () {
        var $input = $(this);
        var rowId  = parseInt($input.closest('tr').data('id'), 10);
        var qty    = parseFloat($input.val());
        if (isNaN(qty) || qty < 0) {
            toastr.warning('Quantity must be 0 or greater.');
            $input.val($input.data('original'));
            return;
        }
        $input.val(qty.toFixed(2)).addClass('mp-dirty');
        $input.closest('td').attr('data-order', qty);
        table.cell($input.closest('td')).invalidate('dom').draw(false);
        mpQueueUpdate(rowId, qty);
        toastr.info('Quantity queued offline. Sync to apply.', 'Queued');
    });

    // Delete button
    var deleteRowId = null;
    $(document).on('click', '.mpDeleteBtn', function () {
        deleteRowId = parseInt($(this).data('id'), 10);
        $('#mpDeleteProductLabel').text($(this).data('product'));
        $('#mpDeleteModal').modal('show');
    });

    $('#mpDeleteConfirmBtn').on('click', function (e) {
        e.preventDefault();
        $('#mpDeleteModal').modal('hide');
        $('#mprow' + deleteRowId).fadeOut(200, function () { $(this).remove(); });
        mpQueueDelete(deleteRowId);
        toastr.info('Removal queued offline. Sync to apply.', 'Queued');
    });

    // Stats button
    document.getElementById('mpStatsBtn')?.addEventListener('click', function (e) {
        e.preventDefault(); $('#mpStatsModal').modal('show');
    });

    // Download button
    document.getElementById('mpDownloadBtn')?.addEventListener('click', function (e) {
        e.preventDefault(); $('#mpDownloadModal').modal('show');
    });

    @endif

    // Sync button
    document.getElementById('mpSyncBtn')?.addEventListener('click', function (e) {
        e.preventDefault();
        if (!mpQueue.length) { toastr.info('Nothing to sync — no offline changes pending.'); return; }
        document.getElementById('mpSyncCount').textContent = mpQueue.length;
        $('#mpSyncModal').modal('show');
    });

    document.getElementById('mpSyncConfirmBtn')?.addEventListener('click', function () {
        var btn = this; btn.disabled = true;
        showProgress(true);
        fetch('{{ route("retail.operations.fullstocktaking.missing-products.sync") }}', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': mpCsrf() },
            body:    JSON.stringify({ ops: mpQueue }),
        })
        .then(res => res.json().then(data => ({ status: res.status, data })))
        .then(({ status, data }) => {
            btn.disabled = false; showProgress(false);
            if (status === 200) {
                toastr.success(data.message, 'Synced');
                mpQueue = []; saveMpQueue();
                $('#mpSyncModal').modal('hide');
                setTimeout(() => location.reload(), 700);
            } else {
                toastr.error(data.message || 'Sync failed.', 'Error');
            }
        })
        .catch(() => {
            btn.disabled = false; showProgress(false);
            toastr.error('Could not reach the server. Your changes remain queued offline.', 'Network Error');
        });
    });

    @if(Session::has('message'))
    toastr['{{ Session::get("alert-type","info") }}']('{{ Session::get("message") }}');
    @endif
});

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
    const d  = new Date(val + 'T00:00:00');
    const mo = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    document.getElementById('fstDmcCustomVal').textContent = d.getDate() + ' ' + mo[d.getMonth()] + ' ' + d.getFullYear();
}
document.getElementById('fstDateChip')?.addEventListener('click', () => {
    document.getElementById('fstDateFormValue').value = '{{ $isCustom ? $date : "" }}';
    $('#fstDateModal').modal('show');
});
</script>
@endsection