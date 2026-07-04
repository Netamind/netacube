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

    $products       = collect();
    $alreadyCounted = collect();

    if ($branchId && ! $isRectified) {
        $products = DB::connection('tenant')
            ->table('retail_base_products as bp')
            ->leftJoin('retail_branch_products as rbp', function ($join) use ($branchId) {
                $join->on('rbp.base_product_id', '=', 'bp.id')
                     ->where('rbp.branch_id', '=', $branchId);
            })
            ->where('bp.is_product', 1)
            ->where(function ($q) {
                $q->whereNull('rbp.id')->orWhere('rbp.is_active', 1);
            })
            ->select(
                'bp.id as id',
                'bp.name as product',
                'bp.code',
                'bp.unit',
                'rbp.stock_quantity',
                'rbp.id as branch_product_id',
                DB::raw('COALESCE(rbp.selling_price, bp.selling_price) as selling_price')
            )
            ->orderBy('bp.name')
            ->get();
    }

    if ($branchId) {
        $alreadyCounted = DB::connection('tenant')
            ->table('retail_fullstocktaking')
            ->where('branch_id', $branchId)
            ->where('date', $date)
            ->pluck('found', 'base_product_id');

        $countedIds = $alreadyCounted->keys();

        if ($countedIds->isNotEmpty()) {
            DB::connection('tenant')
                ->table('retail_fullstocktaking_missing_products')
                ->where('branch_id', $branchId)
                ->where('date', $date)
                ->whereIn('base_product_id', $countedIds)
                ->delete();
        }

        $alreadyMissingIds = DB::connection('tenant')
            ->table('retail_fullstocktaking_missing_products')
            ->where('branch_id', $branchId)
            ->where('date', $date)
            ->pluck('base_product_id');

        $excludeIds = $countedIds->merge($alreadyMissingIds)->unique();

        $missingToSeed = DB::connection('tenant')
            ->table('retail_branch_products as rbp')
            ->join('retail_base_products as bp', 'bp.id', '=', 'rbp.base_product_id')
            ->where('rbp.branch_id', $branchId)
            ->whereNotIn('rbp.base_product_id', $excludeIds)
            ->select(
                'rbp.base_product_id',
                'bp.name as product_name',
                'bp.unit',
                DB::raw('COALESCE(rbp.selling_price, bp.selling_price) as price'),
                'rbp.stock_quantity as quantity',
                'rbp.batch_number',
                'rbp.expiry_date'
            )
            ->get();

        if ($missingToSeed->isNotEmpty()) {
            $now  = now();
            $rows = $missingToSeed->map(fn ($m) => [
                'date'           => $date,
                'branch_id'      => $branchId,
                'base_product_id'=> $m->base_product_id,
                'product_name'   => $m->product_name,
                'unit'           => $m->unit,
                'price'          => $m->price ?? 0,
                'quantity'       => $m->quantity ?? 0,
                'rate'           => 1.00,
                'batch_number'   => $m->batch_number,
                'expiry_date'    => $m->expiry_date,
                'product_status' => 'Active',
                'created_at'     => $now,
                'updated_at'     => $now,
            ])->toArray();

            foreach (array_chunk($rows, 200) as $chunk) {
                DB::connection('tenant')
                    ->table('retail_fullstocktaking_missing_products')
                    ->insertOrIgnore($chunk);
            }
        }
    }
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
/* ══════════════════════════════════════════════════════════════
   FULL STOCKTAKING — Silver + Netacube brand gradient (#4B5EBD → #576CC0)
══════════════════════════════════════════════════════════════ */

.content-page > .content > .container-fluid {
    padding-top: 16px;
}

/* ══ Stocktaking Card ═══════════════════════════════════════════ */
.fst-card {
    border: none;
    box-shadow: none;
    border-radius: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    background-color: transparent;
    height: calc(100vh - 90px);
}

/* ── Silver header bar ── */
.fst-card-header {
    padding: 4px 10px !important;
    background-color: silver;
    color: #666666;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex: 0 0 auto;
    gap: 8px;
}

/* ── Left cluster: date chip + FS Actions ── */
.fst-hdr-left { display: flex; align-items: center; gap: 8px; min-width: 0; }

/* ── Date chip — click opens date modal ── */
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
    background: rgba(255,255,255,.35); color: #555555;
    text-transform: uppercase;
}
#fstDateChip.custom-mode .fst-mode-tag { background: #fcd34d; color: #7c4a03; }
#fstDateChip .fst-edit-pencil { font-size: 11px; opacity: .65; }

/* ── FS Actions — silver-bar styled pill, sits right of the date chip ── */
#fstActionsBtn {
    height: 28px; padding: 0 10px; border-radius: 4px;
    border: 1px solid rgba(102,102,102,.35); background: rgba(255,255,255,.35);
    color: #555555; font-size: 12px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 5px; cursor: pointer;
    white-space: nowrap;
}
#fstActionsBtn i { font-size: 14px; }
#fstActionsBtn:hover { background: rgba(255,255,255,.6); color: #333333; }

/* ── Header icon buttons (History / Info / Refresh) ── */
.fst-hdr-actions { display: flex; align-items: center; gap: 2px; }
.fst-hdr-btn {
    height: 24px; width: 24px; border: none; background: none;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 0; color: #666666; font-size: 16px;
    cursor: pointer; position: relative; padding: 1px;
    text-decoration: none;
}
.fst-hdr-btn:hover { color: #333333; }
.fst-hdr-divider { width: 1px; height: 16px; background: #8a8a8a; margin: 0 6px; opacity: .6; }

/* ══ Blue branch bar ══ */
.fst-branch-row {
    background: linear-gradient(to right, #4B5EBD, #576CC0);
    padding: 7px 10px;
    flex: 0 0 auto;
    box-sizing: border-box;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    flex-wrap: nowrap;
}

.fst-branch-left {
    display: flex;
    flex-direction: row;
    align-items: center;
    gap: 10px;
    min-width: 0;
    flex: 1 1 auto;
}

.fst-section-label {
    font-size: 10px; font-weight: 600; color: rgba(255,255,255,.4);
    letter-spacing: .5px; text-transform: uppercase;
    white-space: nowrap; line-height: 1.2;
    padding-left: 2px;
}

#fstBranchForm { margin: 0; display: inline-flex; align-items: center; min-width: 0; }

#fstBranchSelect {
    border: none; background: transparent; color: silver;
    font-size: 16px; font-weight: 600; cursor: pointer;
    padding: 0 0 0 2px; outline: none; max-width: 280px;
}
#fstBranchSelect option { color: #1e293b; background: #fff; font-size: 14px; }

.fst-rectified-tag {
    font-size: 10px; font-weight: 700; background: #d1fae5; color: #065f46;
    border-radius: 5px; padding: 3px 8px; display: inline-flex; align-items: center; gap: 4px;
    flex-shrink: 0;
}

/* ── "Fullstocktaking" label — far right of the blue bar ── */
.fst-branch-right { display: flex; align-items: center; flex-shrink: 0; }
.fst-page-label {
    font-size: 12px; font-weight: 600; color: silver;
    white-space: nowrap; letter-spacing: .2px;
}

/* ══ Workspace ══ */
.fst-card-body {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
    padding: 0 !important;
    overflow: hidden;
}
#fst-workspace-row {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: row;
    overflow: hidden;
    height: 100%;
    margin: 0 !important;
}

/* ── Left col ── */
#fst-left-col {
    background-color: transparent;
    display: flex;
    flex-direction: column;
    flex: 0 0 41.6667%;
    max-width: 41.6667%;
    min-width: 0;
    min-height: 0;
    border-right: none;
    overflow: hidden;
    padding: 0 !important;
}

#fst-search-row {
    background: linear-gradient(to right, #4B5EBD, #576CC0);
    padding: 8px;
    flex: 0 0 auto;
    box-sizing: border-box;
    width: 100%;
}
#fst-search-wrap { position: relative; }
#fst-search-wrap i {
    position: absolute; left: 10px; top: 50%;
    transform: translateY(-50%);
    color: #595959; font-size: 16px; pointer-events: none;
}
#fst-search {
    background-color: silver;
    text-transform: uppercase; font-weight: bold;
    border: 1px solid rgba(255,255,255,.35);
    width: 100%; height: 36px;
    border-radius: 4px; padding: 0 10px 0 32px;
    outline: none;
    color: #1a1a1a;
    box-sizing: border-box;
}
#fst-search::placeholder { color: #595959; font-weight: bold; text-transform: none; }
#fst-search:focus { background-color: #d9d9d9; border-color: rgba(255,255,255,.65); outline: none; box-shadow: none; }

#fst-product-display {
    flex: 0 0 0px;
    height: 0;
    min-height: 0;
    max-height: 0;
    overflow: hidden;
    background: transparent;
    border: none;
}
#fst-product-display.has-results {
    flex: 1 1 auto;
    height: auto;
    min-height: 0;
    max-height: none;
    overflow-y: auto;
    background: transparent;
}

.fst-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 6px 8px;
    background-color: #cccccc;
    border-bottom: 1px solid #a8a8a8;
    border-left: 1px solid #b8b8b8;
    border-right: 1px solid #b8b8b8;
}
.fst-row:first-child { border-top: 1px solid #a8a8a8; }
.fst-row .fst-link {
    color: black; text-decoration: none; cursor: pointer;
    flex: 0 0 66%; max-width: 66%; min-width: 0; overflow: hidden;
}
.fst-name { text-transform: uppercase; font-weight: bold; font-size: 14px; }
.fst-meta { color: gray; font-family: monospace; font-size: 13px; margin-left: 6px; }
.fst-stock-tag { color: #8a8a8a; font-weight: 600; font-size: 16px; font-family: monospace; margin-left: 6px; }
.fst-already { color: #1d4ed8; font-weight: 700; font-size: 11px; margin-left: 6px; }
.fst-qty-input {
    text-align: center; flex: 0 0 28%; max-width: 28%;
    border-radius: 5px; border: 1px ridge #b3b3b3;
    background: transparent; font-size: 15px; font-weight: bold; color: #1a1a1a;
    height: 36px; margin-left: 8px; margin-right: 6px;
}
.fst-qty-input:focus { outline: 1px solid #0d6efd; background: transparent; }

/* ── Right col ── */
#fst-right-col {
    flex: 0 0 58.3333%;
    max-width: 58.3333%;
    min-width: 0;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: transparent;
    padding: 0 !important;
}
#fst-cart-bar {
    background: linear-gradient(to right, #4B5EBD, #576CC0);
    padding: 6px 8px;
    display: flex; align-items: center; justify-content: space-between;
    flex: 0 0 auto;
}
.fst-cart-label {
    border: 2px solid silver; font-weight: bold; color: silver; background: transparent;
    padding: 3px 8px; font-size: 14px; display: inline-flex; align-items: center; gap: 0;
    height: 36px; box-sizing: border-box;
}
.fst-cart-label .cart-icon { color: silver; font-size: 16px; }
.fst-cart-label .cart-pipe { color: rgba(255,255,255,.4); margin: 0 7px; font-size: 16px; line-height: 1; }
.fst-cart-label .cart-currency { font-size: 11px; font-weight: 700; color: rgba(255,255,255,.7); letter-spacing: .5px; margin-right: 2px; }
#fstCartTotal { color: #f2f2f2; font-weight: bold; font-size: 17px; }

#fst-merge-btn {
    border: 2px solid silver; background: transparent; color: silver; font-weight: bold;
    height: 36px; width: 90px; padding: 0;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 13px; cursor: pointer; border-radius: 4px;
}
#fst-merge-btn:disabled { opacity: .45; cursor: not-allowed; }

#fst-cart-table-wrap {
    flex: 1 1 auto;
    min-height: 0;
    background-color: transparent;
    overflow-y: auto;
    overflow-x: auto;
    padding-bottom: 0;
}
#fst-cart-table {
    width: 100%; font-size: 12px; border-collapse: collapse;
    background-color: transparent;
    border-left: 1px solid #999999;
    border-right: 1px solid #999999;
    border-bottom: 1px solid #999999;
}
#fst-cart-table thead th {
    color: #3d5c5c; border-bottom: 2px solid #a6a6a6; border-top: 1px solid #a6a6a6;
    padding: 6px 4px; text-align: center;
    position: sticky; top: 0; background-color: silver; z-index: 2;
}
#fst-cart-table thead th:first-child { text-align: left; padding-left: 6px; }
#fst-cart-table tbody td {
    border-bottom: 1px solid #b3b3b3; padding: 6px 4px;
    text-align: center; color: black; background-color: silver;
}
#fst-cart-table tbody td:first-child { text-align: left; padding-left: 6px; }
.fst-cart-remove { color: red; cursor: pointer; font-weight: bold; text-decoration: none; }

#fst-cart-empty-row td#fst-cart-empty {
    text-align: center; color: #595959; font-size: 13px;
    background-color: silver;
    padding: 22px 8px;
    border-bottom: none;
}
#fst-cart-table.fst-cart-empty {
    border-left: none; border-right: none; border-bottom: none;
}

/* ══ Placeholders ══ */
.fst-placeholder-wrap { padding: 48px 20px; text-align: center; color: #94a3b8; }
.fst-placeholder-wrap i { font-size: 52px; display: block; margin-bottom: 12px; color: #c8d0ed; }
.fst-placeholder-wrap h5 { color: #64748b; font-weight: 600; }

.fst-locked-wrap {
    display: flex; align-items: flex-start; gap: 14px;
    padding: 28px 24px; background: #f8f9fa;
    border-radius: 0;
    border: 1px solid #dee2e6;
    border-top: none;
}
.fst-locked-wrap i { font-size: 32px; color: #16a34a; flex-shrink: 0; margin-top: 2px; }
.fst-locked-wrap .lock-title { font-weight: 700; font-size: 15px; color: #1e293b; margin-bottom: 4px; }
.fst-locked-wrap .lock-body  { font-size: 13px; color: #475569; }

/* ══ Modal headers ══ */
.mh-pos {
    background: linear-gradient(to right, #4B5EBD, #576CC0);
    padding: 10px 16px !important; border-bottom: none;
}
.mh-pos-title { color: #fff; font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
.mh-close-w { filter: brightness(0) invert(1); opacity: .8; }
.mh-close-w:hover { opacity: 1; }

/* ── Nav actions modal links ── */
.fst-action-link {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 14px; border-radius: 8px;
    background: #f8f9fa; border: 1px solid #e2e8f0;
    text-decoration: none; color: #1e293b;
    transition: background .15s;
}
.fst-action-link:hover { background: #f1f5ff; color: #1e293b; }
.fst-action-link .fal-icon { font-size: 20px; flex-shrink: 0; }
.fst-action-link .fal-title { font-size: 13px; font-weight: 600; }
.fst-action-link .fal-sub   { font-size: 11px; color: #64748b; }
.fst-action-link .fal-arrow { margin-left: auto; color: #94a3b8; font-size: 18px; flex-shrink: 0; }

/* ── Info modal list ── */
.fst-info-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 13px; }
.fst-info-list li { display: flex; gap: 10px; align-items: flex-start; }
.fst-info-list li i { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
.fst-info-list .fil-title { font-size: 13px; font-weight: 700; color: #1e293b; display: block; margin-bottom: 2px; }
.fst-info-list .fil-body  { font-size: 12px; color: #475569; line-height: 1.5; }

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

/* ── Scrollbars ── */
#fst-product-display::-webkit-scrollbar,
#fst-cart-table-wrap::-webkit-scrollbar { width: 5px; height: 5px; }
#fst-product-display::-webkit-scrollbar-thumb,
#fst-cart-table-wrap::-webkit-scrollbar-thumb { background: #999; }

/* ══ MOBILE ══ */
@media (max-width: 768px) {
    .fst-card {
        border-radius: 0 !important;
        height: auto !important;
        margin-top: 8px;
        margin-left: 8px;
        margin-right: 8px;
    }

    .content-page { padding: 0 !important; }
    .content      { padding: 0 !important; }

    .content-page > .content > .container-fluid {
        padding-top: 0 !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    .fst-branch-row { gap: 8px; }

    #fst-workspace-row {
        flex-direction: column;
        overflow: visible;
        height: auto;
        flex: 0 0 auto;
    }

    #fst-left-col {
        flex: 0 0 auto;
        width: 100%;
        max-width: 100%;
        max-height: 44vh;
        border-right: none;
        border-bottom: 1px solid #adadad;
        overflow: hidden;
    }

    #fst-product-display.has-results { max-height: calc(44vh - 54px); }

    #fst-right-col {
        flex: 0 0 auto;
        width: 100%;
        max-width: 100%;
        min-height: 0;
        overflow: hidden;
    }

    #fst-cart-table-wrap {
        flex: 0 0 auto;
        overflow-y: auto;
        max-height: 50vh;
        padding-bottom: 0;
    }

    #fst-cart-table { border-left: none; border-right: none; border-bottom: none; }

    #fst-search-row { padding: 8px; box-sizing: border-box; width: 100%; }
    #fst-search     { width: 100%; box-sizing: border-box; }

    .fst-card-body { overflow: hidden; flex: 0 0 auto; }

    .fst-hdr-btn { width: 26px; height: 26px; font-size: 17px; }

    .fst-row { padding: 9px 8px; }
    .fst-name { font-size: 15px; }
    .fst-qty-input { height: 40px; }

    /* FS Actions stays visible with icon + text on mobile */
    #fstActionsBtn { font-size: 11px; padding: 0 8px; height: 26px; }

    .fst-page-label { font-size: 11px; }

    .modal-dialog { margin: 1.25rem auto !important; max-width: calc(100% - 24px) !important; }
    .modal-content { border-radius: 10px !important; max-height: calc(100vh - 2.5rem); overflow-y: auto; }
    .modal-body { max-height: 70vh; overflow-y: auto; }

    .fst-qty-input { font-size: 16px; }
}
</style>

{{-- ══ Progress bar ══ --}}
<div class="progress" id="progressBar" role="progressbar"
     style="height:8px;transform:rotate(180deg);display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

{{-- ══ Page wrapper ══ --}}
<div class="content-page">
<div class="content">
<div class="container-fluid">

<div class="fst-card" id="fstCard">

    {{-- ── Silver header: date chip + FS Actions (left) + history / info / refresh (right) ── --}}
    <div class="fst-card-header">

        <div class="fst-hdr-left">
            {{-- Date chip — click opens date modal --}}
            <button type="button" id="fstDateChip"
                    class="{{ $isCustom ? 'custom-mode' : '' }}"
                    title="Change stocktaking date">
                <i class="ri-calendar-line"></i> {{ $displayDate }}
                <span class="fst-mode-tag">{{ $isCustom ? 'Custom' : 'Today' }}</span>
                <i class="ri-pencil-line fst-edit-pencil"></i>
            </button>

            {{-- FS Actions — right after the date chip --}}
            <button type="button" id="fstActionsBtn"
                    onclick="$('#fstNavActionsModal').modal('show')"
                    title="Quick navigation">
                <i class="ri-layout-grid-line"></i> <span class="fab-label">FS Actions</span>
            </button>
        </div>

        {{-- Right-side icons: History | Info | Refresh --}}
        <div class="fst-hdr-actions">
            <a href="{{ route('retail.operations.fullstocktaking.history') }}"
               class="fst-hdr-btn" title="History">
                <i class="ri-history-line"></i>
            </a>
            <span class="fst-hdr-divider"></span>
            <button type="button" class="fst-hdr-btn" id="fstInfoBtn"
                    title="About Full Stocktaking"
                    onclick="$('#fstInfoModal').modal('show')">
                <i class="ri-information-line"></i>
            </button>
            <span class="fst-hdr-divider"></span>
            <button type="button" class="fst-hdr-btn" title="Refresh" onclick="location.reload()">
                <i class="ri-refresh-line"></i>
            </button>
        </div>
    </div>

    {{-- ── Blue branch bar: branch select (left) + Fullstocktaking label (far right) ── --}}
    <div class="fst-branch-row">
        <div class="fst-branch-left">
            <form method="POST" action="{{ route('tenant.admin.update.filters') }}" id="fstBranchForm">
                @csrf
                <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                <select name="branch_id" id="fstBranchSelect"
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
            <span class="fst-page-label">Fullstocktaking</span>
        </div>
    </div>

    {{-- ── Body ── --}}
    <div class="fst-card-body">
        @if(!$branchId)
            <div class="fst-placeholder-wrap">
                <i class="ri-store-2-line"></i>
                <h5>No Branch Selected</h5>
                <p style="font-size:13px;">Select a branch from the bar above to begin counting.</p>
            </div>
        @elseif($isRectified)
            <div class="fst-locked-wrap">
                <i class="ri-lock-line"></i>
                <div>
                    <div class="lock-title">Counting Locked — {{ $branchName }} · {{ $displayDate }}</div>
                    <div class="lock-body">This date has already been rectified. Counting is closed — pick a different date or view this stocktake in History.</div>
                </div>
            </div>
        @else
        <div id="fst-workspace-row">

            {{-- LEFT — product search & list --}}
            <div id="fst-left-col">
                <div id="fst-search-row">
                    <div id="fst-search-wrap">
                        <i class="ri-search-line"></i>
                        <input type="text" style="display:none;" aria-hidden="true" tabindex="-1">
                        <input type="password" style="display:none;" aria-hidden="true" tabindex="-1">
                        <input type="text" id="fst-search"
                               placeholder="Search product name or code"
                               autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                               data-lpignore="true" data-form-type="other" data-1p-ignore="true"
                               aria-autocomplete="none" role="presentation">
                    </div>
                </div>
                <div id="fst-product-display"></div>
                <script type="application/json" id="fst-products-json">{!! json_encode($products->map(fn($p) => [
                    'id'        => $p->id,
                    'name'      => $p->product,
                    'code'      => $p->code,
                    'unit'      => $p->unit,
                    'price'     => $p->selling_price,
                    'stock'     => is_null($p->branch_product_id) ? null : (float) $p->stock_quantity,
                    'inSystem'  => ! is_null($p->branch_product_id),
                    'already'   => $alreadyCounted[$p->id] ?? null,
                ])) !!}</script>
            </div>

            {{-- RIGHT — counted cart --}}
            <div id="fst-right-col">
                <div id="fst-cart-bar">
                    <div class="fst-cart-label">
                        <span class="cart-icon" style="font-weight:bold;">&Sigma;</span>
                        <span class="cart-pipe">|</span>
                        <span class="cart-currency">MWK</span>
                        <span id="fstCartTotal">0.00</span>
                    </div>
                    <button id="fst-merge-btn" disabled onclick="openMergeModal()">
                        <i class="ri-arrow-right-s-line" style="font-size:20px;"></i>
                    </button>
                </div>
                <div id="fst-cart-table-wrap">
                    <table id="fst-cart-table" class="fst-cart-empty">
                        <thead>
                            <tr><th>Item</th><th>Unit</th><th>Qty</th><th>Del</th></tr>
                        </thead>
                        <tbody id="fst-cart-tbody">
                            <tr id="fst-cart-empty-row">
                                <td colspan="4" id="fst-cart-empty">No items counted</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>{{-- /#fst-workspace-row --}}
        @endif
    </div>{{-- /.fst-card-body --}}
</div>{{-- /.fst-card --}}

</div>{{-- /.container-fluid --}}
</div>{{-- /.content --}}
</div>{{-- /.content-page --}}


{{-- ══ MERGE MODAL ══ --}}
@if($branchId && !$isRectified)
<div class="modal fade" id="fstMergeModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title">
                    <i class="ri-upload-cloud-2-line"></i> Merge Counted Data
                </h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <p style="font-size:13px;color:#475569;">
                    You are about to merge <strong id="mergeLineCount">0</strong> counted product line(s)
                    into the stocktake for <strong>{{ $branchName }}</strong> on <strong>{{ $displayDate }}</strong>.
                </p>
                <label class="form-label fw-semibold" style="font-size:12px;">Enter your password to confirm</label>
                <input type="password" class="form-control" id="fstMergePassword"
                       placeholder="Password" autocomplete="off">
                <div class="alert alert-info border-0 mt-3 py-2 px-3"
                     style="font-size:11.5px;border-radius:6px;">
                    <i class="ri-information-line me-1"></i>
                    Counting can keep happening on other devices while sales continue — the system reconciles them safely at rectification time.
                </div>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="fstMergeSubmitBtn">
                    <i class="ri-check-line"></i> Merge Now
                </button>
            </div>
        </div>
    </div>
</div>
@endif


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
                <a href="{{ route('retail.operations.fullstocktaking.merged-data') }}"
                   class="fst-action-link">
                    <i class="ri-stack-line fal-icon" style="color:#4B5EBD;"></i>
                    <div>
                        <div class="fal-title">Merged Data</div>
                        <div class="fal-sub">View all merged stocktake records</div>
                    </div>
                    <i class="ri-arrow-right-s-line fal-arrow"></i>
                </a>
                <a href="{{ route('retail.operations.fullstocktaking.missing-products') }}"
                   class="fst-action-link">
                    <i class="ri-error-warning-line fal-icon" style="color:#d97706;"></i>
                    <div>
                        <div class="fal-title">Missing Products</div>
                        <div class="fal-sub">Products not yet counted on this date</div>
                    </div>
                    <i class="ri-arrow-right-s-line fal-arrow"></i>
                </a>
                <a href="{{ route('retail.operations.fullstocktaking.actions-and-info') }}"
                   class="fst-action-link">
                    <i class="ri-flashlight-line fal-icon" style="color:#059669;"></i>
                    <div>
                        <div class="fal-title">Actions &amp; Info</div>
                        <div class="fal-sub">Rectify, export and manage stocktake</div>
                    </div>
                    <i class="ri-arrow-right-s-line fal-arrow"></i>
                </a>
            </div>
        </div>
    </div>
</div>


{{-- ══ INFO MODAL ══ --}}
<div class="modal fade" id="fstInfoModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:430px;">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title">
                    <i class="ri-information-line"></i> About Full Stocktaking
                </h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <ul class="fst-info-list">
                    <li>
                        <i class="ri-search-line" style="color:#4B5EBD;"></i>
                        <div>
                            <span class="fil-title">Count products</span>
                            <span class="fil-body">Search for a product by name or code, type the quantity found on the shelf, and it is added to your count cart instantly. You can count the same product more than once — each entry is kept as its own line and all of them are added together when merged.</span>
                        </div>
                    </li>
                    <li>
                        <i class="ri-error-warning-line" style="color:#b45309;"></i>
                        <div>
                            <span class="fil-title">[NS] — Not in System</span>
                            <span class="fil-body">Search results include the full product catalog, not just products already assigned to this branch. A product tagged <strong>[NS]</strong> exists in the catalog but has no stock record yet for this branch — instead of a quantity, you'll see [NS] in place of the stock figure. You can still count it: doing so creates the branch product record automatically when the stocktake is rectified.</span>
                        </div>
                    </li>
                    <li>
                        <i class="ri-save-line" style="color:#4B5EBD;"></i>
                        <div>
                            <span class="fil-title">Saved offline automatically</span>
                            <span class="fil-body">Counts are stored on this device as you go. You can close the browser and return later — nothing is lost until you clear the cart manually.</span>
                        </div>
                    </li>
                    <li>
                        <i class="ri-upload-cloud-2-line" style="color:#4B5EBD;"></i>
                        <div>
                            <span class="fil-title">Merge</span>
                            <span class="fil-body">Press <em>Merge</em> to push your counts to the server. Multiple devices and staff can merge independently and at different times — the system accumulates all submissions safely.</span>
                        </div>
                    </li>
                    <li>
                        <i class="ri-lock-line" style="color:#16a34a;"></i>
                        <div>
                            <span class="fil-title">Rectify</span>
                            <span class="fil-body">Once all devices have merged, go to <em>Actions &amp; Info</em> to rectify. Rectification locks the date and adjusts stock levels to match the physical count. This can only be done once per branch per date.</span>
                        </div>
                    </li>
                    <li>
                        <i class="ri-error-warning-line" style="color:#d97706;"></i>
                        <div>
                            <span class="fil-title">Missing products</span>
                            <span class="fil-body">Any product that was not counted by any device appears in <em>Missing Products</em>. As soon as a product is counted and merged, it disappears from that list automatically. These are flagged and can be reviewed or excluded before rectification.</span>
                        </div>
                    </li>
                    <li>
                        <i class="ri-stack-line" style="color:#4B5EBD;"></i>
                        <div>
                            <span class="fil-title">Merged data</span>
                            <span class="fil-body">See a consolidated view of everything merged so far across all devices, including quantities, timestamps, and which device submitted each line.</span>
                        </div>
                    </li>
                    <li>
                        <i class="ri-calendar-line" style="color:#4B5EBD;"></i>
                        <div>
                            <span class="fil-title">Custom date</span>
                            <span class="fil-body">By default the stocktake targets today. To count for a past or future date, click the date chip in the top-left of the header — it will reload the page for that date's session.</span>
                        </div>
                    </li>
                    <li>
                        <i class="ri-history-line" style="color:#64748b;"></i>
                        <div>
                            <span class="fil-title">History</span>
                            <span class="fil-body">Browse all rectified stocktakes for this branch. You can view the final counted quantities and the variance against system stock at the time of rectification.</span>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
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
                        <div class="dmc-val" id="fstDmcCustomVal">{{ $isCustom ? $displayDate : 'Pick a date' }}</div>
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

@endsection
@section('scripts')
<script>
'use strict';

const FST_BRANCH_ID = '{{ $branchId }}';
const FST_DATE      = '{{ $date }}';
const FST_CART_KEY  = 'fullstocktaking_count_cart_' + FST_BRANCH_ID + '_' + FST_DATE;

let fstCart        = [];
let fstAllProducts = [];

function fstEsc(s)  { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function fstFmt(n)  { return (n === null || n === undefined || n === '') ? '0' : parseFloat(n).toLocaleString('en-US', { maximumFractionDigits: 2 }); }
function fstFmt2(n) { return (n === null || n === undefined || n === '') ? '0.00' : parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
function fstCsrf()  { return document.querySelector('meta[name="csrf-token"]').getAttribute('content'); }

function fstDeviceId() {
    let id = localStorage.getItem('fullstocktaking_device_id');
    if (!id) { id = 'stk_' + Math.random().toString(36).slice(2, 10); localStorage.setItem('fullstocktaking_device_id', id); }
    return id;
}
function fstDeviceLabel() {
    return 'Stocktaking — ' + (navigator.platform || 'Unknown');
}

function fstReportDeviceSync(pendingOpsCount) {
    if (!FST_BRANCH_ID) return;
    const payload = new FormData();
    payload.append('_token',            fstCsrf());
    payload.append('branch_id',         FST_BRANCH_ID);
    payload.append('date',              FST_DATE);
    payload.append('device_id',         fstDeviceId());
    payload.append('device_label',      fstDeviceLabel());
    payload.append('device_type',       'stocktaking');
    payload.append('pending_ops_count', pendingOpsCount);

    if (navigator.sendBeacon) {
        navigator.sendBeacon('{{ route("retail.operations.fullstocktaking.device-sync") }}', payload);
    } else {
        fetch('{{ route("retail.operations.fullstocktaking.device-sync") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': fstCsrf() },
            body: JSON.stringify({
                branch_id: FST_BRANCH_ID, date: FST_DATE,
                device_id: fstDeviceId(), device_label: fstDeviceLabel(),
                device_type: 'stocktaking', pending_ops_count: pendingOpsCount,
            }),
        }).catch(() => {});
    }
}

$(document).ready(function () {
    @if($branchId && !$isRectified)

    fetch('{{ route("retail.operations.fullstocktaking.seed-session") }}', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': fstCsrf() },
        body:    JSON.stringify({ branch_id: FST_BRANCH_ID, date: FST_DATE }),
    }).catch(() => {});

    try { fstAllProducts = JSON.parse(document.getElementById('fst-products-json').textContent || '[]'); } catch(e) { fstAllProducts = []; }
    loadFstCart();
    renderFstCart();

    fstReportDeviceSync(fstCart.length);

    const display = document.getElementById('fst-product-display');

    function renderRows(products) {
        if (!products.length) {
            display.innerHTML = '<div style="padding:10px 12px 6px;color:#595959;font-size:12px;text-align:center;">No products matched.</div>';
            display.classList.add('has-results');
            return;
        }
        display.innerHTML = products.map(p => `
            <div class="fst-row" data-id="${p.id}">
                <a href="#" class="fst-link" onclick="event.preventDefault();fstRowClick(${p.id})">
                    <span class="fst-name">${fstEsc(p.name)}</span>
                    <span class="fst-meta">${fstFmt(p.price)}/${fstEsc(p.unit)}</span>
                    ${p.inSystem
                        ? `<span class="fst-stock-tag">[${fstFmt(p.stock)}]</span>`
                        : `<span class="fst-stock-tag">[NS]</span>`}
                    ${p.already !== null ? `<span class="fst-already">counted: ${fstFmt(p.already)}</span>` : ''}
                </a>
                <input type="number" class="fst-qty-input" id="fstq_${p.id}" min="0" step="any"
                       autocomplete="off" onchange="fstQtyChange(${p.id})">
            </div>`
        ).join('');
        display.classList.add('has-results');
    }

    function clearFstDisplay() {
        display.innerHTML = '';
        display.classList.remove('has-results');
    }

    // Exposed so fstQtyChange() and the search input's own listeners can share
    // the exact same "collapse the results" behaviour.
    window.fstClearDisplay = clearFstDisplay;

    $('#fst-search').on('keyup', function () {
        const q = $(this).val().trim().toLowerCase();
        if (q.length < 2) { clearFstDisplay(); return; }

        // If what's typed is an exact match for a product's code, treat the
        // search as "resolved" and collapse the list — otherwise keep it open.
        const exactCodeMatch = fstAllProducts.some(p => p.code && p.code.toLowerCase() === q);
        if (exactCodeMatch) { clearFstDisplay(); return; }

        renderRows(fstAllProducts.filter(p =>
            p.name.toLowerCase().includes(q) ||
            (p.code && p.code.toLowerCase().includes(q))
        ));
    });

    const searchInput = document.getElementById('fst-search');
    searchInput.value = '';
    searchInput.addEventListener('focus', function () { if (this.value) { this.value = ''; clearFstDisplay(); } });
    searchInput.addEventListener('click', function () { if (this.value) { this.value = ''; clearFstDisplay(); } });
    setTimeout(() => { searchInput.value = ''; }, 50);
    searchInput.focus();

    @endif
});

function fstFindProduct(id) { return fstAllProducts.find(p => p.id === id); }
function fstRowClick(id)    { const input = document.getElementById('fstq_' + id); if (input) input.focus(); }

function fstQtyChange(id) {
    const p = fstFindProduct(id); if (!p) return;
    const input = document.getElementById('fstq_' + id);
    const qty   = parseFloat(input.value);
    if (!qty || qty <= 0) { input.value = ''; return; }

    // Duplicates allowed: the same product can be counted several times.
    // Each count is kept as its own cart row (its own client_uuid) instead
    // of being merged into an existing row for that product id. The server
    // already sums `quantity` across all count_lines per base_product_id,
    // so sending multiple lines for the same product is exactly what the
    // controller expects when multiple people/counts recount an item —
    // this only changes how the local cart is built, not any backend logic.
    // unshift (not push) so the most recently counted item shows at the
    // top of the cart list rather than the bottom.
    fstCart.unshift({
        id:          p.id,
        name:        p.name,
        unit:        p.unit,
        price:       p.price,
        qty,
        client_uuid: 'stk_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 9),
    });

    saveFstCart();
    renderFstCart();
    fstReportDeviceSync(fstCart.length);

    // Only clear this row's own quantity box. The search input and the
    // results list are left exactly as they are, so multiple products can
    // be counted one after another from the same search without the list
    // disappearing. The list only collapses when the user clicks/focuses
    // the search input again, or types a full product code (see the
    // keyup handler and the focus/click listeners above).
    input.value = '';
}

function fstRemoveCartLine(clientUuid) {
    // Keyed by client_uuid (not product id) so removing one duplicate row
    // doesn't remove every row counted for that same product.
    fstCart = fstCart.filter(c => c.client_uuid !== clientUuid);
    saveFstCart();
    renderFstCart();
    fstReportDeviceSync(fstCart.length);
}

function saveFstCart()  { localStorage.setItem(FST_CART_KEY, JSON.stringify(fstCart)); }
function loadFstCart()  { try { fstCart = JSON.parse(localStorage.getItem(FST_CART_KEY) || '[]'); } catch(e) { fstCart = []; } }
function fstCartValue() { return fstCart.reduce((s, c) => s + (c.qty * (c.price || 0)), 0); }

function renderFstCart() {
    const table   = document.getElementById('fst-cart-table');
    const tbody   = document.getElementById('fst-cart-tbody');
    const btn     = document.getElementById('fst-merge-btn');
    const totalEl = document.getElementById('fstCartTotal');
    if (totalEl) totalEl.textContent = fstFmt2(fstCartValue());

    if (!fstCart.length) {
        tbody.innerHTML = '<tr id="fst-cart-empty-row"><td colspan="4" id="fst-cart-empty">No items counted</td></tr>';
        if (btn) btn.disabled = true;
        if (table) table.classList.add('fst-cart-empty');
        return;
    }

    tbody.innerHTML = fstCart.map(c =>
        `<tr id="fstcrow_${fstEsc(c.client_uuid)}">
            <td>${fstEsc(c.name)}</td>
            <td>${fstEsc(c.unit)}</td>
            <td>${fstFmt(c.qty)}</td>
            <td><a href="#" class="fst-cart-remove" onclick="event.preventDefault();fstRemoveCartLine('${c.client_uuid}')">✕</a></td>
        </tr>`
    ).join('');
    if (btn) btn.disabled = false;
    if (table) table.classList.remove('fst-cart-empty');
}

function openMergeModal() {
    if (!fstCart.length) return;
    document.getElementById('mergeLineCount').textContent = fstCart.length;
    document.getElementById('fstMergePassword').value = '';
    $('#fstMergeModal').modal('show');
}

document.getElementById('fstMergeSubmitBtn')?.addEventListener('click', function () {
    const password = document.getElementById('fstMergePassword').value;
    if (!password) { toastr.warning('Please enter your password.'); return; }

    const lines = fstCart.map(c => ({
        base_product_id: c.id,
        quantity:        c.qty,
        product_name:    c.name,
        unit:            c.unit,
        client_uuid:     c.client_uuid,
    }));

    const btn = this;
    btn.disabled = true;
    const originalHtml = btn.innerHTML;
    btn.innerHTML = '<i class="ri-loader-4-line"></i> Merging...';

    fetch('{{ route("retail.operations.fullstocktaking.merge") }}', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': fstCsrf(), 'Accept': 'application/json' },
        body:    JSON.stringify({
            password:     password,
            branch_id:    FST_BRANCH_ID,
            date:         FST_DATE,
            device_id:    fstDeviceId(),
            device_label: fstDeviceLabel(),
            lines:        lines,
        }),
    })
    .then(r => r.json().then(d => ({ status: r.status, d })))
    .then(({ status, d }) => {
        btn.disabled  = false;
        btn.innerHTML = originalHtml;
        if (status === 200) {
            toastr.success(d.message, 'Merged');
            fstCart = [];
            saveFstCart();
            renderFstCart();
            fstReportDeviceSync(0);
            $('#fstMergeModal').modal('hide');
            setTimeout(() => location.reload(), 800);
        } else if (status === 401) {
            toastr.error(d.message, 'Incorrect Password');
        } else if (status === 409) {
            toastr.error(d.message, 'Locked');
        } else {
            toastr.error(d.message || 'Merge failed.', 'Error');
        }
    })
    .catch(() => {
        btn.disabled  = false;
        btn.innerHTML = originalHtml;
        toastr.error('Could not reach the server. Counts remain saved offline.', 'Network Error');
    });
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

@if(Session::has('message'))
toastr['{{ Session::get("alert-type","info") }}']('{{ Session::get("message") }}');
@endif
</script>
@endsection