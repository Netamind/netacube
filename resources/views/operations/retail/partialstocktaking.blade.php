{{-- FILE: resources/views/operations/retail/partialstocktaking.blade.php --}}
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
        ->where('sector', 'Retail')
        ->where('status', 'active')
        ->orderBy('name')
        ->get();

    $branchName = $branchId
        ? (DB::connection('tenant')->table('branches')->where('id', $branchId)->value('name') ?? 'Branch not found')
        : null;

    $isRectified = $branchId && DB::connection('tenant')
        ->table('retail_partialstocktaking_summary')
        ->where('branch_id', $branchId)
        ->where('date', $date)
        ->where('status', 'completed')
        ->exists();

    $products       = collect();
    $alreadyCounted = collect();

    if ($branchId) {
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

        $alreadyCounted = DB::connection('tenant')
            ->table('retail_partialstocktaking')
            ->where('branch_id', $branchId)
            ->where('date', $date)
            ->pluck('found', 'base_product_id');
    }
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
/* ══════════════════════════════════════════════════════════════
   PARTIAL STOCKTAKING — same Silver + Netacube brand gradient
   (#4B5EBD → #576CC0) design language as Full Stocktaking.
══════════════════════════════════════════════════════════════ */

.content-page > .content > .container-fluid { padding-top: 16px; }

.pst-card {
    border: none; box-shadow: none; border-radius: 0; overflow: hidden;
    display: flex; flex-direction: column; background-color: transparent;
    height: calc(100vh - 90px);
}

.pst-card-header {
    padding: 4px 10px !important; background-color: silver; color: #666666;
    display: flex; align-items: center; justify-content: space-between;
    flex: 0 0 auto; gap: 8px;
}

.pst-hdr-left { display: flex; align-items: center; gap: 8px; min-width: 0; }

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

#pstActionsBtn {
    height: 28px; padding: 0 10px; border-radius: 4px;
    border: 1px solid rgba(102,102,102,.35); background: rgba(255,255,255,.35);
    color: #555555; font-size: 12px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 5px; cursor: pointer;
    white-space: nowrap;
}
#pstActionsBtn i { font-size: 14px; }
#pstActionsBtn:hover { background: rgba(255,255,255,.6); color: #333333; }

.pst-hdr-actions { display: flex; align-items: center; gap: 2px; }
.pst-hdr-btn {
    height: 24px; width: 24px; border: none; background: none;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 0; color: #666666; font-size: 16px;
    cursor: pointer; position: relative; padding: 1px; text-decoration: none;
}
.pst-hdr-btn:hover { color: #333333; }

.pst-branch-row {
    background: linear-gradient(to right, #4B5EBD, #576CC0);
    padding: 7px 10px; flex: 0 0 auto; box-sizing: border-box; width: 100%;
    display: flex; align-items: center; justify-content: space-between;
    gap: 10px; flex-wrap: nowrap;
}
.pst-branch-left { display: flex; flex-direction: row; align-items: center; gap: 10px; min-width: 0; flex: 1 1 auto; }

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

.pst-branch-right { display: flex; align-items: center; flex-shrink: 0; }
.pst-page-label { font-size: 12px; font-weight: 600; color: silver; white-space: nowrap; letter-spacing: .2px; }

.pst-card-body {
    flex: 1 1 auto; min-height: 0; display: flex; flex-direction: column;
    padding: 0 !important; overflow: hidden;
}
#pst-workspace-row {
    flex: 1 1 auto; min-height: 0; display: flex; flex-direction: row;
    overflow: hidden; height: 100%; margin: 0 !important;
}

#pst-left-col {
    background-color: transparent; display: flex; flex-direction: column;
    flex: 0 0 41.6667%; max-width: 41.6667%; min-width: 0; min-height: 0;
    border-right: none; overflow: hidden; padding: 0 !important;
}
#pst-search-row {
    background: linear-gradient(to right, #4B5EBD, #576CC0);
    padding: 8px; flex: 0 0 auto; box-sizing: border-box; width: 100%;
}
#pst-search-wrap { position: relative; }
#pst-search-wrap i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #595959; font-size: 16px; pointer-events: none; }
#pst-search {
    background-color: silver; text-transform: uppercase; font-weight: bold;
    border: 1px solid rgba(255,255,255,.35); width: 100%; height: 36px;
    border-radius: 4px; padding: 0 10px 0 32px; outline: none; color: #1a1a1a; box-sizing: border-box;
}
#pst-search::placeholder { color: #595959; font-weight: bold; text-transform: none; }
#pst-search:focus { background-color: #d9d9d9; border-color: rgba(255,255,255,.65); outline: none; box-shadow: none; }

#pst-product-display { flex: 0 0 0px; height: 0; min-height: 0; max-height: 0; overflow: hidden; background: transparent; border: none; }
#pst-product-display.has-results { flex: 1 1 auto; height: auto; min-height: 0; max-height: none; overflow-y: auto; background: transparent; }

.pst-row {
    display: flex; align-items: center; justify-content: space-between; padding: 6px 8px;
    background-color: #cccccc; border-bottom: 1px solid #a8a8a8; border-left: 1px solid #b8b8b8; border-right: 1px solid #b8b8b8;
}
.pst-row:first-child { border-top: 1px solid #a8a8a8; }
.pst-row .pst-link { color: black; text-decoration: none; cursor: pointer; flex: 0 0 66%; max-width: 66%; min-width: 0; overflow: hidden; }
.pst-name { text-transform: uppercase; font-weight: bold; font-size: 14px; }
.pst-meta { color: gray; font-family: monospace; font-size: 13px; margin-left: 6px; }
.pst-stock-tag { color: #8a8a8a; font-weight: 600; font-size: 16px; font-family: monospace; margin-left: 6px; }
.pst-already { color: #1d4ed8; font-weight: 700; font-size: 11px; margin-left: 6px; }
.pst-qty-input {
    text-align: center; flex: 0 0 28%; max-width: 28%; border-radius: 5px; border: 1px ridge #b3b3b3;
    background: transparent; font-size: 15px; font-weight: bold; color: #1a1a1a; height: 36px; margin-left: 8px; margin-right: 6px;
}
.pst-qty-input:focus { outline: 1px solid #0d6efd; background: transparent; }

#pst-right-col {
    flex: 0 0 58.3333%; max-width: 58.3333%; min-width: 0; min-height: 0;
    display: flex; flex-direction: column; overflow: hidden; background: transparent; padding: 0 !important;
}
#pst-cart-bar {
    background: linear-gradient(to right, #4B5EBD, #576CC0); padding: 6px 8px;
    display: flex; align-items: center; justify-content: space-between; flex: 0 0 auto;
}
.pst-cart-label {
    border: 2px solid silver; font-weight: bold; color: silver; background: transparent;
    padding: 3px 8px; font-size: 14px; display: inline-flex; align-items: center; gap: 0; height: 36px; box-sizing: border-box;
}
.pst-cart-label .cart-icon { color: silver; font-size: 16px; }
.pst-cart-label .cart-pipe { color: rgba(255,255,255,.4); margin: 0 7px; font-size: 16px; line-height: 1; }
.pst-cart-label .cart-currency { font-size: 11px; font-weight: 700; color: rgba(255,255,255,.7); letter-spacing: .5px; margin-right: 2px; }
#pstCartTotal { color: #f2f2f2; font-weight: bold; font-size: 17px; }

#pst-submit-btn {
    border: 2px solid silver; background: transparent; color: silver; font-weight: bold;
    height: 36px; width: 90px; padding: 0; display: inline-flex; align-items: center; justify-content: center;
    font-size: 13px; cursor: pointer; border-radius: 4px;
}
#pst-submit-btn:disabled { opacity: .45; cursor: not-allowed; }

#pst-cart-table-wrap { flex: 1 1 auto; min-height: 0; background-color: transparent; overflow-y: auto; overflow-x: auto; padding-bottom: 0; }
#pst-cart-table {
    width: 100%; font-size: 12px; border-collapse: collapse; background-color: transparent;
    border-left: 1px solid #999999; border-right: 1px solid #999999; border-bottom: 1px solid #999999;
}
#pst-cart-table thead th {
    color: #3d5c5c; border-bottom: 2px solid #a6a6a6; border-top: 1px solid #a6a6a6;
    padding: 6px 4px; text-align: center; position: sticky; top: 0; background-color: silver; z-index: 2;
}
#pst-cart-table thead th:first-child { text-align: left; padding-left: 6px; }
#pst-cart-table tbody td { border-bottom: 1px solid #b3b3b3; padding: 6px 4px; text-align: center; color: black; background-color: silver; }
#pst-cart-table tbody td:first-child { text-align: left; padding-left: 6px; }
.pst-cart-remove { color: red; cursor: pointer; font-weight: bold; text-decoration: none; }
.pst-diff-pos { color: #059669; font-weight: 700; }
.pst-diff-neg { color: #dc2626; font-weight: 700; }
.pst-diff-zero { color: #64748b; }

#pst-cart-empty-row td#pst-cart-empty { text-align: center; color: #595959; font-size: 13px; background-color: silver; padding: 22px 8px; border-bottom: none; }
#pst-cart-table.pst-cart-empty { border-left: none; border-right: none; border-bottom: none; }

.pst-placeholder-wrap { padding: 48px 20px; text-align: center; color: #94a3b8; }
.pst-placeholder-wrap i { font-size: 52px; display: block; margin-bottom: 12px; color: #c8d0ed; }
.pst-placeholder-wrap h5 { color: #64748b; font-weight: 600; }

.pst-locked-wrap {
    display: flex; align-items: flex-start; gap: 14px; padding: 28px 24px; background: #f8f9fa;
    border-radius: 0; border: 1px solid #dee2e6; border-top: none;
}
.pst-locked-wrap i { font-size: 32px; color: #16a34a; flex-shrink: 0; margin-top: 2px; }
.pst-locked-wrap .lock-title { font-weight: 700; font-size: 15px; color: #1e293b; margin-bottom: 4px; }
.pst-locked-wrap .lock-body { font-size: 13px; color: #475569; }

.mh-pos { background: linear-gradient(to right, #4B5EBD, #576CC0); padding: 10px 16px !important; border-bottom: none; }
.mh-pos-title { color: #fff; font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
.mh-close-w { filter: brightness(0) invert(1); opacity: .8; }
.mh-close-w:hover { opacity: 1; }

.pst-action-link {
    display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 8px;
    background: #f8f9fa; border: 1px solid #e2e8f0; text-decoration: none; color: #1e293b; transition: background .15s;
}
.pst-action-link:hover { background: #f1f5ff; color: #1e293b; }
.pst-action-link .fal-icon { font-size: 20px; flex-shrink: 0; }
.pst-action-link .fal-title { font-size: 13px; font-weight: 600; }
.pst-action-link .fal-sub { font-size: 11px; color: #64748b; }
.pst-action-link .fal-arrow { margin-left: auto; color: #94a3b8; font-size: 18px; flex-shrink: 0; }

.pst-info-list { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 13px; }
.pst-info-list li { display: flex; gap: 10px; align-items: flex-start; }
.pst-info-list li i { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
.pst-info-list .fil-title { font-size: 13px; font-weight: 700; color: #1e293b; display: block; margin-bottom: 2px; }
.pst-info-list .fil-body { font-size: 12px; color: #475569; line-height: 1.5; }

.date-mode-toggle { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px; }
.dmc { padding: 10px 12px; border-radius: 8px; border: 1px solid #e2e8f0; cursor: pointer; }
.dmc.active-sys { border-color: #4B5EBD; background: #eff3ff; }
.dmc.active-cus { border-color: #d97706; background: #fffbeb; }
.dmc-label { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
.dmc-val { font-size: 13px; font-weight: 600; color: #64748b; }
.dmc.active-sys .dmc-val { color: #4B5EBD; }
.dmc.active-cus .dmc-val { color: #d97706; }

input[type=number]::-webkit-outer-spin-button, input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
input[type=number] { -moz-appearance: textfield; }

#pst-product-display::-webkit-scrollbar, #pst-cart-table-wrap::-webkit-scrollbar { width: 5px; height: 5px; }
#pst-product-display::-webkit-scrollbar-thumb, #pst-cart-table-wrap::-webkit-scrollbar-thumb { background: #999; }

@media (max-width: 768px) {
    .pst-card { border-radius: 0 !important; height: auto !important; margin-top: 8px; margin-left: 8px; margin-right: 8px; }
    .content-page { padding: 0 !important; }
    .content { padding: 0 !important; }
    .content-page > .content > .container-fluid { padding-top: 0 !important; padding-left: 0 !important; padding-right: 0 !important; }
    .pst-branch-row { gap: 8px; }
    #pst-workspace-row { flex-direction: column; overflow: visible; height: auto; flex: 0 0 auto; }
    #pst-left-col { flex: 0 0 auto; width: 100%; max-width: 100%; max-height: 44vh; border-right: none; border-bottom: 1px solid #adadad; overflow: hidden; }
    #pst-product-display.has-results { max-height: calc(44vh - 54px); }
    #pst-right-col { flex: 0 0 auto; width: 100%; max-width: 100%; min-height: 0; overflow: hidden; }
    #pst-cart-table-wrap { flex: 0 0 auto; overflow-y: auto; max-height: 50vh; padding-bottom: 0; }
    #pst-cart-table { border-left: none; border-right: none; border-bottom: none; }
    #pst-search-row { padding: 8px; box-sizing: border-box; width: 100%; }
    #pst-search { width: 100%; box-sizing: border-box; }
    .pst-card-body { overflow: hidden; flex: 0 0 auto; }
    .pst-hdr-btn { width: 26px; height: 26px; font-size: 17px; }
    .pst-row { padding: 9px 8px; }
    .pst-name { font-size: 15px; }
    .pst-qty-input { height: 40px; font-size: 16px; }
    #pstActionsBtn { font-size: 11px; padding: 0 8px; height: 26px; }
    .pst-page-label { font-size: 11px; }
    .modal-dialog { margin: 1.25rem auto !important; max-width: calc(100% - 24px) !important; }
    .modal-content { border-radius: 10px !important; max-height: calc(100vh - 2.5rem); overflow-y: auto; }
    .modal-body { max-height: 70vh; overflow-y: auto; }
}

/* ── MERGE PROGRESS ── */
.pst-merge-progress-wrap { display: none; }
.pst-merge-progress-wrap.active { display: block; }
.pst-merge-bar-track { width: 100%; height: 10px; border-radius: 6px; background: #e5e7eb; overflow: hidden; }
.pst-merge-bar-fill { height: 100%; background: linear-gradient(90deg,#4B5EBD,#6b7fd7); width: 0%; transition: width .18s ease; border-radius: 6px; }
.pst-merge-status-line { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; font-size: 12px; color: #475569; }
.pst-merge-current-item { font-weight: 600; color: #1e293b; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pst-merge-counts { display: flex; gap: 14px; margin-top: 10px; font-size: 12px; }
.pst-merge-counts span { display: flex; align-items: center; gap: 5px; font-weight: 600; }
.pst-merge-ok { color: #059669; }
.pst-merge-bad { color: #dc2626; }
.pst-merge-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
.pst-merge-dot.pst-merge-ok { background: #059669; }
.pst-merge-dot.pst-merge-bad { background: #dc2626; }
.pst-merge-summary { display: none; }
.pst-merge-summary.active { display: block; }
.pst-merge-summary-title { font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 4px; }
.pst-merge-fail-list { max-height: 140px; overflow-y: auto; border: 1px solid #fecaca; background: #fef2f2; border-radius: 6px; padding: 8px 10px; margin-top: 8px; font-size: 11.5px; color: #991b1b; }
.pst-merge-fail-list div { padding: 2px 0; }
</style>

<div class="progress" id="progressBar" role="progressbar" style="height:8px;transform:rotate(180deg);display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page">
<div class="content">
<div class="container-fluid">

<div class="pst-card" id="pstCard">

    <div class="pst-card-header">
        <div class="pst-hdr-left">
            <button type="button" id="pstDateChip" class="{{ $isCustom ? 'custom-mode' : '' }}" title="Change stocktaking date">
                <i class="ri-calendar-line"></i> {{ $displayDate }}
                <span class="pst-mode-tag">{{ $isCustom ? 'Custom' : 'Today' }}</span>
                <i class="ri-pencil-line pst-edit-pencil"></i>
            </button>
            <button type="button" id="pstActionsBtn" onclick="$('#pstNavActionsModal').modal('show')" title="Quick navigation">
                <i class="ri-layout-grid-line"></i> <span class="fab-label">PS Actions</span>
            </button>
        </div>
        <div class="pst-hdr-actions">
            <a href="{{ route('retail.operations.partialstocktaking.history') }}" class="pst-hdr-btn" title="History">
                <i class="ri-history-line"></i>
            </a>
            <button type="button" class="pst-hdr-btn" id="pstInfoBtn" title="About Partial Stocktaking" onclick="$('#pstInfoModal').modal('show')">
                <i class="ri-information-line"></i>
            </button>
        </div>
    </div>

    <div class="pst-branch-row">
        <div class="pst-branch-left">
            <form method="POST" action="{{ route('retail.operations.update.filters') }}" id="pstBranchForm">
                @csrf
                <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                <select name="branch_id" id="pstBranchSelect" onchange="document.getElementById('pstBranchForm').submit()">
                    <option value="" hidden>{{ $branchName ?? '— Select Branch —' }}</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </form>
            @if($isRectified)
                <span class="pst-rectified-tag"><i class="ri-lock-line"></i> Rectified</span>
            @endif
        </div>
        <div class="pst-branch-right">
            <span class="pst-page-label" role="button" title="Refresh" onclick="location.reload()" style="cursor:pointer;">Partialstocktaking</span>
        </div>
    </div>

    <div class="pst-card-body">
        @if(!$branchId)
            <div class="pst-placeholder-wrap">
                <i class="ri-store-2-line"></i>
                <h5>No Branch Selected</h5>
                <p style="font-size:13px;">Select a branch from the bar above to begin counting.</p>
            </div>
        @elseif($isRectified)
            <div class="pst-locked-wrap">
                <i class="ri-lock-line"></i>
                <div>
                    <div class="lock-title">Counting Locked — {{ $branchName }} · {{ $displayDate }}</div>
                    <div class="lock-body">This date has already been rectified. Counting is closed — pick a different date, or edit figures directly from Stocktaking Data.</div>
                </div>
            </div>
        @else
        <div id="pst-workspace-row">

            <div id="pst-left-col">
                <div id="pst-search-row">
                    <div id="pst-search-wrap">
                        <i class="ri-search-line"></i>
                        <input type="text" style="display:none;" aria-hidden="true" tabindex="-1">
                        <input type="password" style="display:none;" aria-hidden="true" tabindex="-1">
                        <input type="text" id="pst-search" placeholder="Search product name or code"
                               autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
                               data-lpignore="true" data-form-type="other" data-1p-ignore="true"
                               aria-autocomplete="none" role="presentation">
                    </div>
                </div>
                <div id="pst-product-display"></div>
                <script type="application/json" id="pst-products-json">{!! json_encode($products->map(fn($p) => [
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

            {{-- RIGHT — live counted cart: Product / Unit / Price / Expected / Found / Action --}}
            <div id="pst-right-col">
                <div id="pst-cart-bar">
                    <div class="pst-cart-label">
                        <span class="cart-icon" style="font-weight:bold;">&Sigma;</span>
                        <span class="cart-pipe">|</span>
                        <span class="cart-currency">MWK</span>
                        <span id="pstCartTotal">0.00</span>
                    </div>
                    <button id="pst-submit-btn" disabled onclick="openSubmitModal()">
                        <i class="ri-arrow-right-s-line" style="font-size:20px;"></i>
                    </button>
                </div>
                <div id="pst-cart-table-wrap">
                    <table id="pst-cart-table" class="pst-cart-empty">
                        <thead>
                            <tr><th>Product</th><th>Unit</th><th>Price</th><th>Expected</th><th>Found</th><th>Diff</th><th>Action</th></tr>
                        </thead>
                        <tbody id="pst-cart-tbody">
                            <tr id="pst-cart-empty-row"><td colspan="7" id="pst-cart-empty">No items counted</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
        @endif
    </div>
</div>

</div>
</div>
</div>


{{-- ══ SUBMIT MODAL — live push, password confirmation ══ --}}
@if($branchId && !$isRectified)
<div class="modal fade" id="pstSubmitModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title"><i class="ri-flashlight-line"></i> Submit Live Count</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal" id="pstSubmitCloseX"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <div id="pstSubmitFormArea">
                    <p style="font-size:13px;color:#475569;">
                        You are about to push <strong id="submitLineCount">0</strong> counted product line(s)
                        live for <strong>{{ $branchName }}</strong> on <strong>{{ $displayDate }}</strong>.
                        Stock updates immediately — there is no separate merge step.
                    </p>
                    <label class="form-label fw-semibold" style="font-size:12px;">Enter your password to confirm</label>
                    <input type="password" class="form-control" id="pstSubmitPassword" placeholder="Password" autocomplete="off">
                    <div class="alert alert-info border-0 mt-3 py-2 px-3" style="font-size:11.5px;border-radius:6px;">
                        <i class="ri-information-line me-1"></i>
                        Recounting a product you already counted today edits its figure — it does not create a duplicate row, and the product moves to the top of Stocktaking Data.
                    </div>
                </div>

                {{-- Progress — shown while lines are being merged one by one --}}
                <div class="pst-merge-progress-wrap" id="pstMergeProgressWrap">
                    <div class="pst-merge-bar-track"><div class="pst-merge-bar-fill" id="pstMergeBarFill"></div></div>
                    <div class="pst-merge-status-line">
                        <span class="pst-merge-current-item" id="pstMergeCurrentItem">Starting…</span>
                        <span id="pstMergeCountLabel">0 / 0</span>
                    </div>
                    <div class="pst-merge-counts">
                        <span class="pst-merge-ok"><i class="pst-merge-dot pst-merge-ok"></i><span id="pstMergeOkCount">0</span> merged</span>
                        <span class="pst-merge-bad"><i class="pst-merge-dot pst-merge-bad"></i><span id="pstMergeBadCount">0</span> failed</span>
                    </div>
                </div>

                {{-- Final summary — shown once the run finishes --}}
                <div class="pst-merge-summary" id="pstMergeSummary">
                    <div class="pst-merge-summary-title" id="pstMergeSummaryTitle">Done</div>
                    <div style="font-size:12.5px;color:#475569;" id="pstMergeSummarySub"></div>
                    <div class="pst-merge-fail-list" id="pstMergeFailList" style="display:none;"></div>
                </div>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;" id="pstSubmitFooter">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="pstSubmitBtn"><i class="ri-check-line"></i> Submit Now</button>
                <button type="button" class="btn btn-outline-danger btn-sm" id="pstDownloadFailedBtn" style="display:none;"><i class="ri-file-excel-2-line"></i> Download Failed Items (Excel)</button>
                <button type="button" class="btn btn-primary btn-sm" id="pstMergeDoneBtn" style="display:none;" data-bs-dismiss="modal">Done</button>
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
                <h5 class="modal-title mh-pos-title"><i class="ri-layout-grid-line"></i> Quick Actions</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:12px 16px;display:flex;flex-direction:column;gap:8px;">
                <a href="{{ route('retail.operations.partialstocktaking.data') }}" class="pst-action-link">
                    <i class="ri-stack-line fal-icon" style="color:#4B5EBD;"></i>
                    <div>
                        <div class="fal-title">Stocktaking Data</div>
                        <div class="fal-sub">View &amp; edit live counted records</div>
                    </div>
                    <i class="ri-arrow-right-s-line fal-arrow"></i>
                </a>
                <a href="{{ route('retail.operations.partialstocktaking.actions-and-info') }}" class="pst-action-link">
                    <i class="ri-flashlight-line fal-icon" style="color:#059669;"></i>
                    <div>
                        <div class="fal-title">Actions &amp; Info</div>
                        <div class="fal-sub">Summary, rectify, remarks and reports</div>
                    </div>
                    <i class="ri-arrow-right-s-line fal-arrow"></i>
                </a>
            </div>
        </div>
    </div>
</div>


{{-- ══ INFO MODAL ══ --}}
<div class="modal fade" id="pstInfoModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:430px;">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title"><i class="ri-information-line"></i> About Partial Stocktaking</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <ul class="pst-info-list">
                    <li>
                        <i class="ri-search-line" style="color:#4B5EBD;"></i>
                        <div><span class="fil-title">Count any product, any time</span>
                        <span class="fil-body">Count only what you need — a shelf, a category, a spot-check. No requirement to cover the whole catalog.</span></div>
                    </li>
                    <li>
                        <i class="ri-alert-line" style="color:#dc2626;"></i>
                        <div><span class="fil-title">Count the WHOLE product before you submit</span>
                        <span class="fil-body">If a product sits in more than one spot, add up every location first and submit one combined total. Submitting the same units twice will double the figure — fix mistakes on <em>Stocktaking Data</em> instead of recounting.</span></div>
                    </li>
                    <li>
                        <i class="ri-flashlight-line" style="color:#1d4ed8;"></i>
                        <div><span class="fil-title">Counting is local until you submit</span>
                        <span class="fil-body">Typing quantities just builds a list on your device — nothing changes in the system yet. Stock only updates when you tap submit and enter your password.</span></div>
                    </li>
                    <li>
                        <i class="ri-refresh-line" style="color:#4B5EBD;"></i>
                        <div><span class="fil-title">Auto-resolves sales</span>
                        <span class="fil-body">Sales made after a count are netted off automatically, so live stock stays accurate even if selling never stopped.</span></div>
                    </li>
                    <li>
                        <i class="ri-error-warning-line" style="color:#b45309;"></i>
                        <div><span class="fil-title">[NS] — Not in System</span>
                        <span class="fil-body">A product tagged <strong>[NS]</strong> has no stock record yet for this branch. Count it anyway — a branch record is created automatically.</span></div>
                    </li>
                    <li>
                        <i class="ri-lock-line" style="color:#16a34a;"></i>
                        <div><span class="fil-title">Rectify (optional close-off)</span>
                        <span class="fil-body">Only the products you've actually counted are included when you rectify — everything else is left untouched. Go to <em>Actions &amp; Info</em> to rectify; counting locks afterward, though edits from Stocktaking Data still push live.</span></div>
                    </li>
                    <li>
                        <i class="ri-calendar-line" style="color:#4B5EBD;"></i>
                        <div><span class="fil-title">Custom date &amp; History</span>
                        <span class="fil-body">Click the date chip to count a past or future date. Use the history icon to browse past rectified stocktakes by branch.</span></div>
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
<div class="modal fade" id="pstDateModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog" style="max-width:400px;">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title"><i class="ri-calendar-event-line"></i> Stocktaking Date</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <div class="date-mode-toggle">
                    <div class="dmc {{ !$isCustom ? 'active-sys' : '' }}" id="pstDmcSystem" onclick="pstSetDateMode('system')">
                        <div class="dmc-label">System date</div>
                        <div class="dmc-val">{{ Carbon::today()->format('d M Y') }}</div>
                    </div>
                    <div class="dmc {{ $isCustom ? 'active-cus' : '' }}" id="pstDmcCustom" onclick="pstSetDateMode('custom')">
                        <div class="dmc-label">Custom date</div>
                        <div class="dmc-val" id="pstDmcCustomVal">{{ $isCustom ? $displayDate : 'Pick a date' }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('retail.operations.update.filters') }}" id="pstDateForm">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ Auth::id() }}">
                    <input type="hidden" name="pst_custom_date" id="pstDateFormValue" value="">
                    <div id="pstCustomDateRow" style="{{ !$isCustom ? 'display:none;' : '' }}">
                        <input type="date" class="form-control" id="pstCustomDateInput" value="{{ $date }}" oninput="pstPreviewDate(this.value)">
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

@endsection
@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
'use strict';

const PST_BRANCH_ID = '{{ $branchId }}';
const PST_DATE      = '{{ $date }}';
const PST_CART_KEY  = 'partialstocktaking_count_cart_'    + PST_BRANCH_ID + '_' + PST_DATE;
const PST_SESSION_KEY = 'partialstocktaking_session_id_' + PST_BRANCH_ID + '_' + PST_DATE;

let pstCart        = [];
let pstAllProducts = [];
let pstSessionId   = null; // server-issued checkpoint for this branch+date+device — see pstEnsureSession()

function pstEsc(s)  { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function pstFmt(n)  { return (n === null || n === undefined || n === '') ? '0' : parseFloat(n).toLocaleString('en-US', { maximumFractionDigits: 2 }); }
function pstFmt2(n) { return (n === null || n === undefined || n === '') ? '0.00' : parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
function pstCsrf()  { return document.querySelector('meta[name="csrf-token"]').getAttribute('content'); }

function pstDeviceId() {
    let id = localStorage.getItem('partialstocktaking_device_id');
    if (!id) { id = 'pstk_' + Math.random().toString(36).slice(2, 10); localStorage.setItem('partialstocktaking_device_id', id); }
    return id;
}
function pstDeviceLabel() { return 'Partial Stocktaking — ' + (navigator.platform || 'Unknown'); }

/**
 * Opens this device's counting session for today, or picks up the one it
 * already opened earlier. Cached in localStorage so it survives page
 * reloads and works even if the device goes offline right after this call —
 * every line counted from here on (however long before it's synced) rides
 * on this same server-issued checkpoint. Safe to call repeatedly; the
 * server only ever honours the first session opened per branch+date+device,
 * so calling this again never moves the checkpoint forward.
 */
async function pstEnsureSession() {
    const cached = localStorage.getItem(PST_SESSION_KEY);
    if (cached) { pstSessionId = parseInt(cached, 10); return pstSessionId; }

    try {
        const resp = await fetch('{{ route("retail.operations.partialstocktaking.session.start") }}', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': pstCsrf(), 'Accept': 'application/json' },
            body:    JSON.stringify({
                branch_id:    PST_BRANCH_ID,
                date:         PST_DATE,
                device_id:    pstDeviceId(),
                device_label: pstDeviceLabel(),
            }),
        });
        const data = await resp.json();
        if (resp.ok && data.session_id) {
            pstSessionId = data.session_id;
            localStorage.setItem(PST_SESSION_KEY, String(pstSessionId));
            return pstSessionId;
        }
    } catch (e) {
        // Offline right now — fine, we'll retry at submit time. Merges
        // without a session_id just fall back to the old (safe, if less
        // precise) merge-time checkpoint server-side.
    }
    return null;
}

$(document).ready(function () {
    @if($branchId && !$isRectified)

    pstEnsureSession(); // fire on load, while we're most likely still online
    try { pstAllProducts = JSON.parse(document.getElementById('pst-products-json').textContent || '[]'); } catch(e) { pstAllProducts = []; }
    loadPstCart();
    renderPstCart();

    const display = document.getElementById('pst-product-display');

    function renderRows(products) {
        if (!products.length) {
            display.innerHTML = '<div style="padding:10px 12px 6px;color:#595959;font-size:12px;text-align:center;">No products matched.</div>';
            display.classList.add('has-results');
            return;
        }
        display.innerHTML = products.map(p => `
            <div class="pst-row" data-id="${p.id}">
                <a href="#" class="pst-link" onclick="event.preventDefault();pstRowClick(${p.id})">
                    <span class="pst-name">${pstEsc(p.name)}</span>
                    <span class="pst-meta">${pstFmt(p.price)}/${pstEsc(p.unit)}</span>
                    ${p.inSystem ? `<span class="pst-stock-tag">[${pstFmt(p.stock)}]</span>` : `<span class="pst-stock-tag">[NS]</span>`}
                    ${p.already !== null ? `<span class="pst-already">counted: ${pstFmt(p.already)}</span>` : ''}
                </a>
                <input type="number" class="pst-qty-input" id="pstq_${p.id}" step="any"
                       autocomplete="off" onchange="pstQtyChange(${p.id})">
            </div>`
        ).join('');
        display.classList.add('has-results');
    }

    function clearPstDisplay() { display.innerHTML = ''; display.classList.remove('has-results'); }
    window.pstClearDisplay = clearPstDisplay;

    $('#pst-search').on('keyup', function () {
        const q = $(this).val().trim().toLowerCase();
        if (q.length < 2) { clearPstDisplay(); return; }
        renderRows(pstFilterProducts(q));
    });

    const searchInput = document.getElementById('pst-search');
    searchInput.value = '';
    searchInput.addEventListener('focus', function () { if (this.value) { this.value = ''; clearPstDisplay(); } });
    searchInput.addEventListener('click', function () { if (this.value) { this.value = ''; clearPstDisplay(); } });
    setTimeout(() => { searchInput.value = ''; }, 50);
    searchInput.focus();

    @endif
});

function pstFindProduct(id) { return pstAllProducts.find(p => p.id === id); }
function pstRowClick(id)    { const input = document.getElementById('pstq_' + id); if (input) input.focus(); }

function pstTokenizeQuery(q) { return (q || '').toLowerCase().split(/\s+/).filter(Boolean); }

// Fuzzy subsequence match: true if every character of `needle` appears
// in `haystack`, in order, but not necessarily next to each other.
// e.g. "pra" matches "paracetamol" (p...r...a...) even though "pra"
// isn't a literal substring of it.
function pstIsSubsequence(needle, haystack) {
    var hi = 0;
    for (var i = 0; i < haystack.length && hi < needle.length; i++) {
        if (haystack[i] === needle[hi]) hi++;
    }
    return hi === needle.length;
}

// Does this token match the product's searchable text? Checked in order:
// (1) literal substring within a single word — fast, covers most typing,
//     e.g. "500" in "500mg" or "ceta" in "paracetamol";
// (2) fuzzy subsequence within a single word — for partial/skipped-letter
//     typing, e.g. "pra" in "paracetamol";
// (3) fuzzy subsequence across the whole run-together text — for queries
//     typed with no space, e.g. "para500" spanning "paracetamol" and
//     "500mg" as separate words.
function pstTokenMatchesWords(token, words, joined) {
    return words.some(function (w) {
        return w.indexOf(token) !== -1 || pstIsSubsequence(token, w);
    }) || pstIsSubsequence(token, joined);
}

// Ranks how good a match is so the closest matches float to the top.
// Higher = better. Per token, the best word-level match wins:
// exact word > word starts with token > literal substring > fuzzy subsequence.
// Then a bonus is added if the whole typed query lines up with the product
// name itself (so "lofnac 100" ranks "Lofnac 100mg" above "Lofnac Plus 100mg"),
// and a tiny penalty for longer names breaks remaining ties toward the more
// specific/shorter match.
function pstTokenScore(token, words, joined) {
    var best = 0;
    for (var i = 0; i < words.length; i++) {
        var w = words[i];
        if (w === token)                { if (100 > best) best = 100; continue; }
        if (w.indexOf(token) === 0)     { if (80  > best) best = 80;  continue; }
        if (w.indexOf(token) !== -1)    { if (50  > best) best = 50;  continue; }
        if (pstIsSubsequence(token, w)) { if (20  > best) best = 20;  continue; }
    }
    if (best === 0 && pstIsSubsequence(token, joined)) best = 5;
    return best;
}

function pstScoreProduct(tokens, name, code) {
    const nameLower = (name || '').toLowerCase();
    const words = (nameLower + ' ' + (code || '').toLowerCase()).split(/\s+/).filter(Boolean);
    const joined = words.join('');
    let score = 0;
    for (let i = 0; i < tokens.length; i++) score += pstTokenScore(tokens[i], words, joined);

    const queryJoined = tokens.join(' ');
    if (nameLower === queryJoined) score += 1000;
    else if (nameLower.indexOf(queryJoined) === 0) score += 400;
    else if (nameLower.indexOf(queryJoined) !== -1) score += 150;

    score -= nameLower.length * 0.01;
    return score;
}

function pstFilterProducts(q) {
    const tokens = pstTokenizeQuery(q);
    if (!tokens.length) return [];
    return pstAllProducts
        .filter(p => {
            const words = ((p.name||'') + ' ' + (p.code||'')).toLowerCase().split(/\s+/).filter(Boolean);
            const joined = words.join('');
            return tokens.every(t => pstTokenMatchesWords(t, words, joined));
        })
        .map(p => ({ p, s: pstScoreProduct(tokens, p.name, p.code) }))
        .sort((a, b) => b.s - a.s)
        .map(x => x.p);
}

function pstQtyChange(id) {
    const p = pstFindProduct(id); if (!p) return;
    const input = document.getElementById('pstq_' + id);
    const qty   = parseFloat(input.value);
    if (isNaN(qty) || qty === 0) { input.value = ''; return; }

    // Live semantics: if this product is already sitting in the cart (counted
    // earlier in this same session, not yet submitted), ADD the newly entered
    // quantity to the quantity already on that line instead of replacing it or
    // adding a second row — e.g. counted 5 earlier, count 3 more now, line
    // becomes 8. The diff shown in the cart is recalculated off that combined
    // quantity.
    const existingIdx = pstCart.findIndex(c => c.id === p.id);
    const expected = p.stock ?? 0;

    if (existingIdx > -1) {
        const line = pstCart.splice(existingIdx, 1)[0];
        line.qty = (parseFloat(line.qty) || 0) + qty;
        pstCart.unshift(line); // bump to top — most recently affected
    } else {
        pstCart.unshift({
            id:          p.id,
            name:        p.name,
            unit:        p.unit,
            price:       p.price,
            expected,
            qty,
            client_uuid: 'pstk_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 9),
        });
    }

    savePstCart();
    renderPstCart();
    input.value = '';
}

function pstRemoveCartLine(clientUuid) {
    pstCart = pstCart.filter(c => c.client_uuid !== clientUuid);
    savePstCart();
    renderPstCart();
}

function savePstCart()  { localStorage.setItem(PST_CART_KEY, JSON.stringify(pstCart)); }
function loadPstCart()  { try { pstCart = JSON.parse(localStorage.getItem(PST_CART_KEY) || '[]'); } catch(e) { pstCart = []; } }
function pstCartValue() { return pstCart.reduce((s, c) => s + (c.qty * (c.price || 0)), 0); }

function renderPstCart() {
    const table   = document.getElementById('pst-cart-table');
    const tbody   = document.getElementById('pst-cart-tbody');
    const btn     = document.getElementById('pst-submit-btn');
    const totalEl = document.getElementById('pstCartTotal');
    if (totalEl) totalEl.textContent = pstFmt2(pstCartValue());

    if (!pstCart.length) {
        tbody.innerHTML = '<tr id="pst-cart-empty-row"><td colspan="7" id="pst-cart-empty">No items counted</td></tr>';
        if (btn) btn.disabled = true;
        if (table) table.classList.add('pst-cart-empty');
        return;
    }

    tbody.innerHTML = pstCart.map(c => {
        const diff = c.qty - (c.expected || 0);
        const cls  = Math.abs(diff) < 0.0001 ? 'pst-diff-zero' : (diff > 0 ? 'pst-diff-pos' : 'pst-diff-neg');
        const diffLabel = Math.abs(diff) < 0.0001 ? '0' : pstFmt(diff); // natural sign — pstFmt already renders negatives with '-'; positives get no prefix
        return `<tr id="pstcrow_${pstEsc(c.client_uuid)}">
            <td>${pstEsc(c.name)}</td>
            <td>${pstEsc(c.unit)}</td>
            <td>${pstFmt(c.price)}</td>
            <td>${pstFmt(c.expected)}</td>
            <td>${pstFmt(c.qty)}</td>
            <td class="${cls}">${diffLabel}</td>
            <td><a href="#" class="pst-cart-remove" onclick="event.preventDefault();pstRemoveCartLine('${c.client_uuid}')">✕</a></td>
        </tr>`;
    }).join('');
    if (btn) btn.disabled = false;
    if (table) table.classList.remove('pst-cart-empty');
}

function pstResetSubmitModalUI() {
    document.getElementById('pstSubmitFormArea').style.display = '';
    document.getElementById('pstMergeProgressWrap').classList.remove('active');
    document.getElementById('pstMergeSummary').classList.remove('active');
    const failList = document.getElementById('pstMergeFailList');
    failList.style.display = 'none';
    failList.innerHTML = '';
    document.getElementById('pstMergeBarFill').style.width = '0%';
    document.getElementById('pstMergeOkCount').textContent = '0';
    document.getElementById('pstMergeBadCount').textContent = '0';
    document.getElementById('pstMergeCountLabel').textContent = '0 / 0';
    document.getElementById('pstMergeCurrentItem').textContent = 'Starting…';
    document.getElementById('pstDownloadFailedBtn').style.display = 'none';
    document.getElementById('pstMergeDoneBtn').style.display = 'none';
    document.getElementById('pstSubmitCloseX').style.display = '';
    const cancelBtn = document.querySelector('#pstSubmitFooter .btn-secondary');
    if (cancelBtn) cancelBtn.style.display = '';
    const submitBtn = document.getElementById('pstSubmitBtn');
    submitBtn.style.display = '';
    submitBtn.disabled = false;
    submitBtn.innerHTML = '<i class="ri-check-line"></i> Submit Now';
}

let pstMergeRunning     = false;
let pstMergeReloadOnHide = false;
let pstLastFailedLines  = [];

function openSubmitModal() {
    if (!pstCart.length) return;
    if (pstMergeRunning) { $('#pstSubmitModal').modal('show'); return; }
    pstResetSubmitModalUI();
    document.getElementById('submitLineCount').textContent = pstCart.length;
    document.getElementById('pstSubmitPassword').value = '';
    $('#pstSubmitModal').modal('show');
}

$('#pstSubmitModal').on('hidden.bs.modal', function () {
    if (pstMergeReloadOnHide) {
        pstMergeReloadOnHide = false;
        location.reload();
    }
});

document.getElementById('pstSubmitBtn')?.addEventListener('click', async function () {
    if (pstMergeRunning) return;
    const password = document.getElementById('pstSubmitPassword').value;
    if (!password) { toastr.warning('Please enter your password.'); return; }

    // Last chance to grab a session if the device was offline on page load —
    // if this still fails (still offline, or truly never connected until
    // now), mergeCounts() falls back to its own safe default server-side.
    if (!pstSessionId) { await pstEnsureSession(); }

    const lines = pstCart.map(c => ({
        base_product_id: c.id,
        quantity:        c.qty,
        product_name:    c.name,
        unit:            c.unit,
        client_uuid:     c.client_uuid,
    }));

    pstMergeRunning    = true;
    pstLastFailedLines = [];

    // Switch the modal into progress mode — one line is merged at a time so
    // a bad line never blocks the rest, and the bar reflects real progress.
    document.getElementById('pstSubmitFormArea').style.display = 'none';
    document.getElementById('pstMergeProgressWrap').classList.add('active');
    document.getElementById('pstSubmitCloseX').style.display = 'none';
    const cancelBtn = document.querySelector('#pstSubmitFooter .btn-secondary');
    if (cancelBtn) cancelBtn.style.display = 'none';
    const submitBtn = this;
    submitBtn.style.display = 'none';

    const total = lines.length;
    let done = 0, ok = 0, bad = 0;
    const succeededUuids = new Set();
    let aborted = false;
    let abortReason = '';

    for (const line of lines) {
        document.getElementById('pstMergeCurrentItem').textContent = line.product_name || 'Product';
        document.getElementById('pstMergeCountLabel').textContent = `${done} / ${total}`;

        const cartLine = pstCart.find(c => c.client_uuid === line.client_uuid) || {};

        try {
            const resp = await fetch('{{ route("retail.operations.partialstocktaking.merge") }}', {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': pstCsrf(), 'Accept': 'application/json' },
                body:    JSON.stringify({
                    password:     password,
                    branch_id:    PST_BRANCH_ID,
                    date:         PST_DATE,
                    device_id:    pstDeviceId(),
                    device_label: pstDeviceLabel(),
                    session_id:   pstSessionId,
                    lines:        [line],
                }),
            });
            const d = await resp.json();

            if (resp.status === 401) {
                aborted = true; abortReason = d.message || 'Incorrect password.'; break;
            }
            if (resp.status === 409) {
                aborted = true; abortReason = d.message || 'This date has already been rectified.'; break;
            }

            const lineResult = resp.status === 200 && Array.isArray(d.results) ? d.results[0] : null;
            if (lineResult && lineResult.status === 'success') {
                ok++; succeededUuids.add(line.client_uuid);
            } else {
                bad++;
                pstLastFailedLines.push({
                    name:  line.product_name,
                    unit:  line.unit || '',
                    price: cartLine.price || 0,
                    qty:   line.quantity,
                    error: (lineResult && lineResult.error) || d.message || 'Failed to merge this item.',
                });
            }
        } catch (e) {
            bad++;
            pstLastFailedLines.push({
                name: line.product_name, unit: line.unit || '', price: cartLine.price || 0,
                qty: line.quantity, error: 'Network error — could not reach the server.',
            });
        }

        done++;
        document.getElementById('pstMergeOkCount').textContent  = ok;
        document.getElementById('pstMergeBadCount').textContent = bad;
        document.getElementById('pstMergeBarFill').style.width  = Math.round((done / total) * 100) + '%';
        document.getElementById('pstMergeCountLabel').textContent = `${done} / ${total}`;
    }

    pstMergeRunning = false;

    // Drop only the lines that actually merged — failures (and anything never
    // attempted because of an abort) stay in the cart so nothing is lost.
    pstCart = pstCart.filter(c => !succeededUuids.has(c.client_uuid));
    savePstCart();
    renderPstCart();

    document.getElementById('pstMergeProgressWrap').classList.remove('active');
    document.getElementById('pstMergeSummary').classList.add('active');

    if (aborted) {
        const notAttempted = total - done;
        document.getElementById('pstMergeSummaryTitle').textContent = ok > 0 ? 'Stopped early' : 'Could not submit';
        document.getElementById('pstMergeSummarySub').textContent =
            `${abortReason} ${ok} product(s) were merged before this happened${notAttempted > 0 ? `; ${notAttempted} were not attempted and are still in your cart.` : '.'}`;
        toastr.error(abortReason, 'Merge Stopped');
    } else if (bad === 0) {
        document.getElementById('pstMergeSummaryTitle').textContent = 'All done';
        document.getElementById('pstMergeSummarySub').textContent = `${ok} product(s) merged successfully — live stock updated.`;
        toastr.success(`${ok} product(s) merged successfully.`, 'Live Stock Updated');
    } else {
        document.getElementById('pstMergeSummaryTitle').textContent = 'Finished with some failures';
        document.getElementById('pstMergeSummarySub').textContent = `${ok} succeeded, ${bad} failed. The failed items are still in your cart — retry, or download them below.`;
        toastr.warning(`${ok} merged, ${bad} failed.`, 'Merge Complete');
    }

    if (pstLastFailedLines.length) {
        const failList = document.getElementById('pstMergeFailList');
        failList.style.display = 'block';
        failList.innerHTML = pstLastFailedLines.map(f => `<div>${pstEsc(f.name)} — ${pstEsc(f.error)}</div>`).join('');
        document.getElementById('pstDownloadFailedBtn').style.display = '';
    }

    document.getElementById('pstMergeDoneBtn').style.display = '';
    pstMergeReloadOnHide = ok > 0;
});

document.getElementById('pstDownloadFailedBtn')?.addEventListener('click', function () {
    if (!pstLastFailedLines.length) { toastr.error('Nothing to export.'); return; }
    if (typeof XLSX === 'undefined') { toastr.error('Excel export library did not load — check your connection and try again.'); return; }

    const rows = pstLastFailedLines.map(f => ({
        Product:  f.name,
        Unit:     f.unit,
        Price:    f.price,
        Quantity: f.qty,
        Error:    f.error,
    }));
    const ws = XLSX.utils.json_to_sheet(rows);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Failed Items');
    XLSX.writeFile(wb, `partial-stocktaking-failed_${PST_BRANCH_ID}_${PST_DATE}.xlsx`);
});

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
</script>
@endsection