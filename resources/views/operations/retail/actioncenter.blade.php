@extends('operations.retail.dashboard')
@section('content')
@php
    use Carbon\Carbon;

    $pref       = DB::connection('tenant')->table('user_filters')->where('user_id', Auth::id())->first();
    $categories = DB::connection('tenant')->table('categories')->orderBy('category')->get();

    $selectedCategory = null;
    $branches         = collect();

    $customDate  = $pref->dnote_custom_date ?? null;
    $isCustom    = !empty($customDate);
    $date        = $isCustom ? $customDate : Carbon::today()->toDateString();
    $displayDate = Carbon::parse($date)->format('d M Y');

    $productId = $pref->action_product_id ?? null;
    $product   = null;

    if ($selectedCategory = ($pref && $pref->category_id
        ? DB::connection('tenant')->table('categories')->where('id', $pref->category_id)->first()
        : null)) {

        $branches = DB::connection('tenant')
            ->table('branches')
            ->where('sector',   'Retail')
            ->where('category', (string) $selectedCategory->id)
            ->where('status',   'active')
            ->orderBy('name')
            ->get();
    }

    $baseProducts = DB::connection('tenant')
        ->table('retail_base_products')
        ->where('is_product', 1)
        ->orderBy('name')
        ->get(['id', 'name', 'unit', 'selling_price', 'cost_price', 'code', 'supplier']);

    $suppliers = DB::connection('tenant')
        ->table('retail_base_products')
        ->whereNotNull('supplier')->where('supplier', '!=', '')
        ->distinct()->orderBy('supplier')->pluck('supplier');

    if ($productId) {
        $product = DB::connection('tenant')
            ->table('retail_base_products')
            ->where('id', $productId)
            ->first(['id', 'name', 'unit', 'selling_price', 'cost_price', 'code']);
    }

    /* ── Branch-specific prices for the selected product ─────────────── */
    $branchPriceMap = [];
    if ($product) {
        $branchPrices = DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('base_product_id', $product->id)
            ->whereIn('branch_id', $branches->pluck('id'))
            ->whereNotNull('selling_price')
            ->get(['branch_id', 'selling_price']);

        foreach ($branchPrices as $bp) {
            if ((float) $bp->selling_price !== (float) $product->selling_price) {
                $branchPriceMap[$bp->branch_id] = (float) $bp->selling_price;
            }
        }
    }

    $branchSummary      = collect();
    $grandTotalCost     = 0;
    $grandTotalValue    = 0;

    if ($selectedCategory) {
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
#progressBar { height: 3px; display: none; transform: rotate(180deg); }

.card      { border: none; box-shadow: 0 2px 12px rgba(0,0,0,0.08); border-radius: 12px; }
.card-body { padding: 0 !important; }

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
.ch-left {
    display: flex; align-items: center; gap: 8px;
    flex: 1; min-width: 0; overflow: hidden;
}
.ch-right { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }

#categorySelectHeader {
    border: none; background: transparent; color: #fff;
    font-size: 15px; font-weight: 600; cursor: pointer;
    padding: 0; outline: none;
    flex: 0 1 auto;
    min-width: 0;
    max-width: 200px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
#categorySelectHeader option { color: #1e293b; background: #fff; font-size: 13px; }

.ch-sep {
    width: 1px; height: 20px;
    background: rgba(255,255,255,0.25);
    flex-shrink: 0;
}

.ch-date-chip {
    display: inline-flex; align-items: center; gap: 4px;
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.25);
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

.search-bar-row {
    display: flex; align-items: stretch;
    background: #eef0f8;
    border-bottom: 1px solid #dde1f0;
    padding: 10px 14px;
    gap: 12px;
}
.sbr-col {
    flex: 0 0 calc(33.333% - 8px); max-width: calc(33.333% - 8px);
    display: flex; align-items: center;
}

@media (max-width: 767px) {
    .search-bar-row  { flex-wrap: wrap; gap: 10px; padding: 10px 12px; }
    .sbr-col         { flex: 0 0 100%; max-width: 100%; }
    #categorySelectHeader { max-width: 130px; }
}

.search-input-wrap { position: relative; width: 100%; }
.search-input-inner {
    display: flex; align-items: center;
    background: #fff; border: 1.5px solid #c5caec;
    border-radius: 9px; padding: 0 11px; height: 40px;
    gap: 7px;
}
.search-icon-left { color: #94a3b8; font-size: 15px; flex-shrink: 0; }
#productSearch {
    flex: 1; border: none; outline: none;
    font-size: 12px; color: #1e293b; background: transparent; padding: 0;
}
#productSearch::placeholder { color: #b0baca; }

#productDropdown {
    display: none; position: absolute;
    top: calc(100% + 4px); left: 0; right: 0;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
    box-shadow: 0 10px 28px rgba(0,0,0,0.13); z-index: 2000;
    max-height: 300px; overflow-y: auto;
}
#productDropdown.open { display: block; }
#productDropdown::-webkit-scrollbar { width: 4px; }
#productDropdown::-webkit-scrollbar-thumb { background: #c5caec; border-radius: 4px; }
.pd-item {
    padding: 8px 13px; font-size: 12px; cursor: pointer;
    border-bottom: 1px solid #f1f5f9; transition: background .1s;
    display: flex; align-items: center; gap: 10px;
}
.pd-item:last-child { border-bottom: none; }
.pd-item:hover { background: #f0f2fa; }
.pd-item-icon {
    width: 28px; height: 28px; border-radius: 7px;
    background: #eff3ff; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; color: #4B5EBD; font-size: 13px;
}
.pd-item-body { flex: 1; min-width: 0; }
.pd-item .pd-name { font-weight: 600; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.pd-item .pd-meta { font-size: 10px; color: #94a3b8; margin-top: 1px; }
.pd-item-price { font-size: 11px; font-weight: 600; color: #059669; white-space: nowrap; }
.pd-empty { padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; font-style: italic; }

.sbr-product-pill {
    display: flex; align-items: center; gap: 8px;
    background: #f1f3f9; border: 1.5px solid #c5caec;
    border-radius: 9px; padding: 0 10px; height: 40px;
    width: 100%; min-width: 0;
    cursor: pointer; transition: border-color .15s;
}
.sbr-product-pill:hover { border-color: #4B5EBD; }
.sbr-product-pill .pill-icon {
    width: 24px; height: 24px; border-radius: 5px;
    background: #eff3ff; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; color: #4B5EBD; font-size: 12px;
}
.sbr-product-pill .pill-info { flex: 1; min-width: 0; }
.sbr-product-pill .pill-name {
    font-size: 11px; font-weight: 700; color: #2d3a8c;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;
}
.sbr-product-pill .pill-meta { display: flex; align-items: center; gap: 4px; margin-top: 1px; }
.sbr-product-pill .pill-badge {
    font-size: 8px; font-weight: 600; padding: 0px 5px; border-radius: 3px;
    white-space: nowrap; line-height: 1.5;
}
.sbr-product-pill .pill-badge-code  { background: #e2e8f0; color: #64748b; }
.sbr-product-pill .pill-badge-unit  { background: #dbeafe; color: #1d4ed8; }
.sbr-product-pill .pill-badge-price { background: #d1fae5; color: #065f46; }
.sbr-product-pill .pill-edit {
    width: 20px; height: 20px; border-radius: 4px;
    background: #eff3ff; border: 1px solid #c5caec;
    display: flex; align-items: center; justify-content: center;
    color: #4B5EBD; font-size: 11px; flex-shrink: 0;
}
.sbr-product-empty {
    display: flex; align-items: center; gap: 7px;
    color: #b0baca; font-size: 12px; font-style: italic;
    height: 40px;
}
.sbr-product-empty i { font-size: 15px; }

.sbr-counter {
    display: flex; align-items: center; gap: 0;
    width: 100%;
    background: #f1f3f9; border: 1.5px solid #c5caec;
    border-radius: 9px; overflow: hidden; height: 40px;
}
.sbr-cr-seg {
    flex: 1; display: flex; flex-direction: column;
    justify-content: center; align-items: center;
    padding: 0 6px;
    border-right: 1px solid #dde1f0;
    height: 100%;
}
.sbr-cr-seg:last-child { border-right: none; }
.sbr-cr-label { font-size: 7px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .7px; line-height: 1; }
.sbr-cr-val { font-size: 13px; font-weight: 800; color: #1e293b; line-height: 1.2; font-variant-numeric: tabular-nums; }
.sbr-cr-val.accent { color: #4B5EBD; }
.sbr-cr-unit-input {
    width: 56px; height: 26px;
    border: 1.5px solid #c5caec; border-radius: 5px;
    text-align: center; font-size: 13px; font-weight: 700; color: #1e293b;
    background: #fff; outline: none; padding: 0;
    -moz-appearance: textfield;
}
.sbr-cr-unit-input::-webkit-outer-spin-button,
.sbr-cr-unit-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }

.branch-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 14px;
    padding: 20px 20px 36px;
    background: #fff;
}
@media (max-width: 767px) {
    .branch-grid { grid-template-columns: 1fr 1fr; gap: 10px; padding: 12px 12px 28px; }
}
@media (max-width: 420px) {
    .branch-grid { grid-template-columns: 1fr; }
}

.branch-card {
    background: #fff;
    border: 1.5px solid #e4e7f5;
    border-radius: 10px;
    overflow: hidden;
}

.bc-header { background: #f0f2fa; border-bottom: 1.5px solid #dde1f0; }
.bc-name-row {
    display: flex; align-items: center; gap: 6px;
    padding: 8px 10px 4px;
}
.bc-name {
    font-size: 11px; font-weight: 700; color: #2d3a8c;
    flex: 1; min-width: 0;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

.bc-price-badge {
    display: inline-flex; align-items: center; gap: 3px;
    background: #fef3c7; border: 1px solid #fcd34d;
    border-radius: 4px; padding: 1px 6px;
    font-size: 9px; font-weight: 700; color: #92400e;
    white-space: nowrap; flex-shrink: 0;
    line-height: 1.6;
}
.bc-price-badge i { font-size: 8px; }

.bc-saved-check {
    display: none;
    width: 15px; height: 15px; border-radius: 50%;
    background: #10b981;
    align-items: center; justify-content: center;
    flex-shrink: 0;
}
.bc-saved-check i { font-size: 8px; color: #fff; }
.bc-saved-check.show { display: flex; }

@keyframes checkPulse {
    0%   { transform: scale(1);   opacity: 1; }
    40%  { transform: scale(1.5); opacity: .7; }
    100% { transform: scale(1);   opacity: 1; }
}
.bc-saved-check.pulse { animation: checkPulse .35s ease; }

.bc-stats-row { display: flex; align-items: center; gap: 0; padding: 0 10px 8px; }
.bc-stat-piece { display: flex; align-items: baseline; gap: 2px; }
.bc-stat-piece + .bc-stat-piece::before { content: '·'; color: #c5caec; font-size: 9px; margin: 0 5px; }
.bc-stat-label { font-size: 8px; font-weight: 600; color: #b0baca; text-transform: uppercase; letter-spacing: .4px; }
.bc-stat-val { font-size: 12px; font-weight: 700; font-variant-numeric: tabular-nums; line-height: 1; }
.bc-stat-val.v-stock     { color: #a0aec0; }
.bc-stat-val.v-delivered { color: #93a3d4; }
.bc-stat-val.v-order     { color: #cbd5e1; }

.bc-input-row { background: #fff; }
.bc-input {
    display: block;
    width: 100%;
    text-align: center;
    font-size: 20px;
    font-weight: normal !important;
    font-family: inherit !important;
    border: none;
    border-top: 1px solid #e4e7f5;
    border-radius: 0;
    padding: 8px 8px;
    outline: none;
    color: #1e293b;
    background: #fff;
    font-variant-numeric: tabular-nums;
    -moz-appearance: textfield;
    transition: none;
}
input.bc-input,
input[type="number"].bc-input { font-weight: normal !important; font-family: inherit !important; }
.bc-input::-webkit-outer-spin-button,
.bc-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.bc-input::placeholder { color: #e8eaf3; font-size: 16px; font-weight: 400; }

.no-category-wrap { padding: 60px 16px; text-align: center; }
.no-category-wrap i { font-size: 48px; color: #dde1f0; display: block; margin-bottom: 14px; }
.no-category-wrap p { color: #94a3b8; font-size: 13px; }
.no-product-placeholder { padding: 50px 16px; text-align: center; color: #94a3b8; grid-column: 1 / -1; }
.no-product-placeholder i { font-size: 40px; color: #dde1f0; display: block; margin-bottom: 10px; }
.no-product-placeholder p { font-size: 12px; margin: 0; }

.mh-blue   { background: linear-gradient(135deg,#4B5EBD,#576CC0); padding: 12px 18px !important; border-bottom: none; border-radius: 8px 8px 0 0; }
.mh-green  { background: linear-gradient(135deg,#059669,#10b981); padding: 12px 18px !important; border-bottom: none; border-radius: 8px 8px 0 0; }
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

#actionsModal .modal-dialog { margin: auto 0 0; max-width: 100%; }
#actionsModal .modal-content { border-radius: 16px 16px 0 0; }
#actionsModal .mh-blue { border-radius: 16px 16px 0 0 !important; }
@media (min-width: 480px) {
    #actionsModal .modal-dialog { margin: auto auto 0; max-width: 400px; }
}

.action-sheet-btn {
    width: 100%; display: flex; align-items: center; gap: 12px;
    background: #f8f9fa; border: 1px solid #e4e7f5; border-radius: 9px;
    padding: 11px 14px; font-size: 13px; font-weight: 500; color: #1e293b;
    cursor: pointer; text-align: left; transition: background .15s;
}
.action-sheet-btn:hover { background: #eef0f8; }
.action-sheet-btn.as-danger { background: #fff5f5; border-color: #fecaca; color: #b91c1c; }
.action-sheet-btn.as-danger:hover { background: #fee2e2; }
.action-sheet-btn i { font-size: 18px; flex-shrink: 0; }

.sum-totals-strip {
    display: flex; align-items: stretch;
    background: #f4f6ff;
    border-bottom: 1.5px solid #e4e7f5;
    padding: 0;
}
.sum-strip-seg {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 12px 10px;
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
    border-bottom: 1.5px solid #e2e8f0;
    text-align: left;
}
.sum-th-c {
    font-size: 9px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: #94a3b8;
    padding: 9px 16px; background: #f8f9fa;
    border-bottom: 1.5px solid #e2e8f0;
    text-align: center;
}
.sum-tr { border-bottom: 1px solid #f1f5f9; transition: background .1s; }
.sum-tr:last-child { border-bottom: none; }
.sum-tr:hover { background: #f8f9ff; }
.sum-td-name {
    padding: 9px 16px; color: #1e293b; font-weight: 600;
    font-size: 12px; display: flex; align-items: center; gap: 8px;
}
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
.sum-tfoot-label {
    padding: 10px 16px; font-size: 12px; font-weight: 700; color: #2d3a8c;
}
.sum-tfoot-num {
    padding: 10px 16px; font-size: 12px; font-weight: 700; color: #1e293b;
    font-variant-numeric: tabular-nums;
}
.sum-tfoot-num.accent { color: #3b4fa0; }

.sum-empty { padding: 48px 20px; text-align: center; }
.sum-empty i { font-size: 36px; color: #dde1f0; display: block; margin-bottom: 10px; }
.sum-empty p { font-size: 13px; color: #94a3b8; margin: 0; }

.sum-footer-info-icon {
    font-size: 15px; color: #a0aec0; cursor: default; transition: color .15s;
}
.sum-footer-info-wrap:hover .sum-footer-info-icon { color: #4B5EBD; }
.sum-footer-tooltip {
    display: none; position: absolute;
    bottom: calc(100% + 8px); right: 0;
    width: 220px; background: #1e293b; color: #e2e8f0;
    font-size: 11px; line-height: 1.5; padding: 8px 10px;
    border-radius: 7px; box-shadow: 0 4px 16px rgba(0,0,0,0.18);
    z-index: 9999; pointer-events: none; white-space: normal;
}
.sum-footer-tooltip::after {
    content: ''; position: absolute;
    top: 100%; right: 6px;
    border: 5px solid transparent; border-top-color: #1e293b;
}
.sum-footer-info-wrap:hover .sum-footer-tooltip { display: block; }

.ep-section-label {
    display: block;
    font-size: 9px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .8px; color: #94a3b8;
    padding-bottom: 6px; margin-bottom: 10px;
    border-bottom: 1px solid #e9ecef;
}
.ep-section-label + * { margin-top: 0; }

#ep-branch-overrides-wrap {
    margin-top: 14px;
    padding-top: 0;
}
.ep-branch-override-row {
    display: flex; align-items: center; gap: 8px;
    background: #f8f9ff; border: 1px solid #e4e7f5; border-radius: 7px;
    padding: 6px 10px;
}
.ep-branch-override-row .ep-bo-name {
    flex: 1; font-size: 12px; font-weight: 600; color: #2d3a8c;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.ep-branch-override-row .ep-bo-input {
    width: 110px; text-align: right;
    font-size: 12px; font-weight: 700; color: #1d4ed8;
    -moz-appearance: textfield;
    transition: none !important;
}
.ep-branch-override-row .ep-bo-input::-webkit-outer-spin-button,
.ep-branch-override-row .ep-bo-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.ep-branch-override-row .ep-bo-input:hover { border-color: #c5caec !important; box-shadow: none !important; }

.ep-branch-overrides-loading,
.ep-branch-overrides-empty {
    font-size: 12px; color: #94a3b8; font-style: italic; padding: 6px 2px;
}

input[type=number] { -moz-appearance: textfield; }
input[type=number]::-webkit-outer-spin-button,
input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
</style>

<form method="POST" action="{{ route('tenant.admin.update.filters') }}"
      id="selectProductForm" style="display:none;">
    @csrf
    <input type="hidden" name="user_id"           value="{{ Auth::id() }}">
    <input type="hidden" name="action_product_id" id="selectProductFormId" value="">
</form>

<div class="progress" id="progressBar" role="progressbar">
    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

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
            <a href="#" class="ch-btn" id="catSummaryBtn" title="View distribution summary for {{ $displayDate }}">
                <i class="ri-bar-chart-grouped-line"></i>
            </a>
            <a href="#" class="ch-btn" id="actionsBtn" title="Actions">
                <i class="ri-settings-3-line"></i>
            </a>
            @endif
            <a href="#" class="ch-btn" id="infoBtn" title="About Action Centre">
                <i class="ri-information-line"></i>
            </a>
        </div>
    </div>
</div>

<div class="tab-header-container">
    <ul class="nav nav-pills mb-0">
        <li class="nav-item">
            <a href="{{ route('retail.operations.actioncenter') }}" class="nav-link active">
                <i class="ri-send-plane-line"></i> Actioncentre
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('retail.operations.deliverynotes') }}" class="nav-link">
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
        <p>Select a category from the header to get started.</p>
    </div>
@else

<div class="search-bar-row">

    <div class="sbr-col">
        <div class="search-input-wrap">
            <div class="search-input-inner">
                <i class="ri-search-2-line search-icon-left"></i>
                <input type="text" id="productSearch"
                       placeholder="Search product by name or code…"
                       autocomplete="off">
            </div>
            <div id="productDropdown"></div>
        </div>
    </div>

    <div class="sbr-col" id="sbrProductCol">
        @if($product)
        <div class="sbr-product-pill" id="pcr-edit-icon" title="Edit product">
            <div class="pill-icon"><i class="ri-box-3-line"></i></div>
            <div class="pill-info">
                <span class="pill-name" id="pcrName">{{ $product->name }}</span>
                <div class="pill-meta" id="pcrMetaRow">
                    @if($product->code)
                    <span class="pill-badge pill-badge-code" id="pcrCode">{{ $product->code }}</span>
                    @endif
                    <span class="pill-badge pill-badge-unit" id="pcrUnit">{{ $product->unit }}</span>
                    <span class="pill-badge pill-badge-price" id="pcrPrice">MWK {{ number_format((float)$product->selling_price, 2) }}</span>
                </div>
            </div>
            <div class="pill-edit"><i class="ri-edit-box-line"></i></div>
        </div>
        @else
        <div class="sbr-product-empty" id="sbrProductEmpty">
            <i class="ri-box-3-line"></i>
            <span>No product selected</span>
        </div>
        @endif
    </div>

    <div class="sbr-col">
        <div class="sbr-counter">
            <div class="sbr-cr-seg">
                <span class="sbr-cr-label">Distribution</span>
                <span class="sbr-cr-val" id="distTotalVal">0</span>
            </div>
            <div class="sbr-cr-seg">
                <span class="sbr-cr-label">Unit of Issue</span>
                <input type="number" inputmode="decimal" class="sbr-cr-unit-input" id="dividerInput" value="1" placeholder="1">
            </div>
            <div class="sbr-cr-seg">
                <span class="sbr-cr-label">Qty Distributed</span>
                <span class="sbr-cr-val accent" id="distResultVal">0</span>
            </div>
        </div>
    </div>

</div>

<div id="branchGridWrap">
@if($product)
    <div class="branch-grid" id="branchGrid">
        @foreach($branches as $branch)
            @php
                $bStock = DB::connection('tenant')
                    ->table('retail_branch_products')
                    ->where('branch_id', $branch->id)
                    ->where('base_product_id', $product->id)
                    ->value('stock_quantity') ?? 0;

                $bSdnote = DB::connection('tenant')
                    ->table('retail_deliverynotes')
                    ->where('delivery_date', $date)
                    ->where('branch_id', $branch->id)
                    ->where('base_product_id', $product->id)
                    ->where('submitted', true)
                    ->value('quantity') ?? 0;

                $bPending = DB::connection('tenant')
                    ->table('retail_deliverynotes')
                    ->where('delivery_date', $date)
                    ->where('branch_id', $branch->id)
                    ->where('base_product_id', $product->id)
                    ->where('submitted', false)
                    ->value('quantity');

                $branchSpecificPrice = $branchPriceMap[$branch->id] ?? null;
            @endphp
            <div class="branch-card"
                 data-branch-id="{{ $branch->id }}"
                 data-product-id="{{ $product->id }}">

                <div class="bc-header">
                    <div class="bc-name-row">
                        <span class="bc-name">{{ $branch->name }}</span>

                        @if($branchSpecificPrice !== null)
                        <span class="bc-price-badge" title="Branch-specific selling price (overrides base price of MWK {{ number_format((float)$product->selling_price, 2) }})">
                            <i class="ri-price-tag-3-line"></i>
                            MWK {{ number_format($branchSpecificPrice, 2) }}
                        </span>
                        @endif

                        <span class="bc-saved-check {{ $bPending !== null ? 'show' : '' }}" id="check-{{ $branch->id }}" title="Saved">
                            <i class="ri-check-line"></i>
                        </span>
                    </div>
                    <div class="bc-stats-row">
                        <div class="bc-stat-piece">
                            <span class="bc-stat-label">Stock</span>
                            <span class="bc-stat-val v-stock">{{ number_format((float)$bStock, 0) }}</span>
                        </div>
                        <div class="bc-stat-piece">
                            <span class="bc-stat-label">Delivered</span>
                            <span class="bc-stat-val v-delivered">{{ number_format((float)$bSdnote, 0) }}</span>
                        </div>
                        <div class="bc-stat-piece">
                            <span class="bc-stat-label">Order</span>
                            <span class="bc-stat-val v-order">0</span>
                        </div>
                    </div>
                </div>

                <div class="bc-input-row" id="ir-{{ $branch->id }}">
                    <input type="number"
                           inputmode="decimal"
                           step="0.01"
                           class="bc-input"
                           placeholder=""
                           value="{{ $bPending !== null ? rtrim(rtrim(number_format((float)$bPending, 2, '.', ''), '0'), '.') : '' }}"
                           data-branch-id="{{ $branch->id }}"
                           data-product-id="{{ $product->id }}"
                           data-branch-name="{{ $branch->name }}"
                           data-selling-price="{{ $branchSpecificPrice ?? $product->selling_price }}">
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="branch-grid" id="branchGrid">
        <div class="no-product-placeholder">
            <i class="ri-search-line d-block mx-auto"></i>
            <p>Search and select a product above to distribute to branches.</p>
        </div>
    </div>
@endif
</div>

@endif

</div>
</div></div></div>


{{-- ══ CATEGORY DISTRIBUTION SUMMARY MODAL ════════════════════════════ --}}
<div class="modal fade" id="catSummaryModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:520px;">
        <div class="modal-content">
            <div class="modal-header mh-blue" style="display:flex;align-items:center;justify-content:space-between;">
                <h5 class="modal-title mh-title">Distribution Summary</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal" style="margin:0;"></button>
            </div>
            <div class="modal-body" style="padding:0;">
                @if($selectedCategory)
                @if($branchSummary->isNotEmpty())
                <div class="sum-totals-strip">
                    <div class="sum-strip-seg">
                        <span class="sum-strip-label">Date</span>
                        <span class="sum-strip-val" style="font-size:13px;font-weight:700;color:#3b4fa0;">
                            {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
                        </span>
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
                @else
                <div class="sum-empty">
                    <i class="ri-inbox-2-line"></i>
                    <p>No delivery notes found for <strong>{{ $selectedCategory->category }}</strong> on {{ $displayDate }}.</p>
                </div>
                @endif
                @endif
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;background:#f8f9ff;border-top:1px solid #e8eaf5;display:flex;align-items:center;">
                <span style="font-size:10px;color:#94a3b8;flex:1;">{{ $selectedCategory->category ?? '' }}</span>
                <span style="position:relative;display:inline-flex;align-items:center;margin-right:10px;" class="sum-footer-info-wrap">
                    <i class="ri-information-line sum-footer-info-icon"></i>
                    <span class="sum-footer-tooltip">
                        All delivery notes for {{ $selectedCategory->category ?? '' }} on {{ $displayDate }}.
                        Includes both submitted and pending notes.
                    </span>
                </span>
            </div>
        </div>
    </div>
</div>


{{-- ══ ACTIONS BOTTOM SHEET ════════════════════════════════════════════ --}}
<div class="modal fade" id="actionsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header mh-blue" style="border-radius:16px 16px 0 0 !important;">
                <h5 class="modal-title mh-title"><i class="ri-settings-3-line"></i> Actions</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:14px 16px 20px;">
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <button class="action-sheet-btn" id="asSubmitAllBtn">
                        <i class="ri-send-plane-fill" style="color:#4B5EBD;"></i>
                        <div>
                            <div style="font-weight:600;">Submit all pending notes</div>
                            <div style="font-size:11px;color:#94a3b8;margin-top:1px;">All products · {{ $displayDate }}</div>
                        </div>
                    </button>
                    <button class="action-sheet-btn" id="asSubmitBtn">
                        <i class="ri-corner-up-right-line" style="color:#059669;"></i>
                        <div>
                            <div style="font-weight:600;">Submit current product</div>
                            <div style="font-size:11px;color:#94a3b8;margin-top:1px;">Marks pending notes as submitted</div>
                        </div>
                    </button>
                    <button class="action-sheet-btn" id="asCancelBtn">
                        <i class="ri-close-line" style="color:#d97706;"></i>
                        <div>
                            <div style="font-weight:600;">Cancel pending notes</div>
                            <div style="font-size:11px;color:#94a3b8;margin-top:1px;">Deletes unsubmitted notes for current product</div>
                        </div>
                    </button>
                    <button class="action-sheet-btn" id="asAddProductBtn">
                        <i class="ri-add-circle-line" style="color:#4B5EBD;"></i>
                        <div>
                            <div style="font-weight:600;">Add new product</div>
                            <div style="font-size:11px;color:#94a3b8;margin-top:1px;">Add to the base catalogue</div>
                        </div>
                    </button>
                    @if($product)
                    <button class="action-sheet-btn as-danger" id="asDeleteProductBtn">
                        <i class="ri-delete-bin-5-line"></i>
                        <div>
                            <div style="font-weight:600;">Delete selected product</div>
                            <div style="font-size:11px;color:#fca5a5;margin-top:1px;">{{ $product->name }}</div>
                        </div>
                    </button>
                    @endif
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
                    <input type="hidden" name="user_id" value="{{ Auth::id() }}">
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


{{-- ══ ADD PRODUCT MODAL ═══════════════════════════════════════════════ --}}
<div class="modal fade" id="addProductModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header mh-green">
                <h5 class="modal-title mh-title"><i class="ri-add-circle-line"></i> Add New Product to Catalogue</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px 10px;">
                <div class="alert alert-info border-0 py-2 px-3 mb-3" style="font-size:12px;border-radius:6px;">
                    <i class="ri-information-line me-1"></i>
                    Creates a new base product. Once saved you can distribute it and set branch-specific prices.
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold" style="font-size:13px;">Product Name <span class="text-danger">*</span></label>
                    <input class="form-control form-control-sm" type="text" id="ap-name" placeholder="e.g. Bread Loaf 700g" autocomplete="off">
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold" style="font-size:13px;">Supplier <span class="text-danger">*</span></label>
                    <select class="form-select form-select-sm" id="ap-supplier">
                        <option value="">— Select Supplier —</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup }}">{{ $sup }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-4">
                        <label class="form-label fw-semibold" style="font-size:12px;">Selling Price <span class="text-danger">*</span></label>
                        <input class="form-control form-control-sm" type="number" inputmode="decimal" min="0" id="ap-sell" placeholder="0.00">
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold" style="font-size:12px;">Cost Price</label>
                        <input class="form-control form-control-sm" type="number" inputmode="decimal" min="0" id="ap-cost" placeholder="0.00">
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold" style="font-size:12px;">Unit</label>
                        <input class="form-control form-control-sm" type="text" id="ap-unit" value="Each">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold" style="font-size:12px;">Code <small class="text-muted">(optional)</small></label>
                    <input class="form-control form-control-sm" type="text" id="ap-code" placeholder="e.g. BRD-001" autocomplete="off">
                </div>
            </div>
            <div class="modal-footer" style="padding:10px 20px 14px;gap:8px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-sm" id="saveNewProductBtn">
                    <i class="ri-check-line"></i> Save Product
                </button>
            </div>
        </div>
    </div>
</div>


{{-- ══ EDIT BASE PRODUCT MODAL ─────────────────────────────────────── --}}
<div class="modal fade" id="editProductModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title">
                    <i class="ri-edit-box-line"></i>
                    Edit Product — <span id="epModalName" style="font-weight:400;opacity:.85;"></span>
                </h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:16px 20px 10px;">
                <div class="alert border-0 py-2 px-3 mb-3"
                     style="background:#fff8e1;border-left:3px solid #f59e0b;border-radius:0 5px 5px 0;font-size:12px;color:#92400e;">
                    <i class="ri-alert-line me-1"></i>
                    Changes here update the <strong>base catalogue</strong> price for all branches.
                </div>
                <input type="hidden" id="ep-id">
                <span class="ep-section-label">Base Details</span>
                <div class="mb-2">
                    <label class="form-label fw-semibold" style="font-size:13px;">Product Name <span class="text-danger">*</span></label>
                    <input class="form-control form-control-sm" type="text" id="ep-name" autocomplete="off">
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold" style="font-size:12px;">Code</label>
                    <input class="form-control form-control-sm" type="text" id="ep-code" autocomplete="off">
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label fw-semibold" style="font-size:12px;">Selling Price <span class="text-danger">*</span></label>
                        <input class="form-control form-control-sm" type="number" inputmode="decimal" id="ep-sell">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold" style="font-size:12px;">Cost Price</label>
                        <input class="form-control form-control-sm" type="number" inputmode="decimal" id="ep-cost">
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold" style="font-size:12px;">Unit</label>
                    <input class="form-control form-control-sm" type="text" id="ep-unit">
                </div>
                <div id="ep-branch-overrides-wrap" style="display:none;">
                    <span class="ep-section-label">Branch Prices</span>
                    <div style="background:#eff3ff;border-left:3px solid #4B5EBD;border-radius:0 5px 5px 0;padding:7px 11px;font-size:11px;color:#3b4fa0;margin-bottom:8px;">
                        Branches with a price differing from the base default above.
                    </div>
                    <div id="ep-branch-overrides-list" style="display:flex;flex-direction:column;gap:6px;"></div>
                </div>
            </div>
            <div class="modal-footer" style="padding:10px 20px 14px;gap:8px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="saveEditProductBtn">
                    <i class="ri-check-line"></i> Update Product
                </button>
            </div>
        </div>
    </div>
</div>


{{-- ══ DELETE PRODUCT MODAL ════════════════════════════════════════════ --}}
<div class="modal fade" id="deleteProductModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header mh-danger">
                <h5 class="modal-title mh-title"><i class="ri-delete-bin-5-line"></i> Delete Product</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 22px;">
                <div style="display:flex;align-items:flex-start;gap:14px;">
                    <div style="width:42px;height:42px;border-radius:50%;background:#fef2f2;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="ri-delete-bin-5-line" style="font-size:20px;color:#dc2626;"></i>
                    </div>
                    <div>
                        <p style="font-size:13px;font-weight:600;color:#1e293b;margin:0 0 6px;">Permanently delete this product?</p>
                        <p style="font-size:12px;color:#64748b;margin:0;">
                            <strong id="deleteProductName"></strong> will be removed from the base catalogue,
                            all branch product records, and all delivery notes.
                        </p>
                    </div>
                </div>
                <div style="background:#fef2f2;border-left:3px solid #dc2626;border-radius:0 5px 5px 0;padding:8px 12px;font-size:11px;color:#7f1d1d;margin-top:14px;">
                    <i class="ri-alert-line me-1"></i> This action is irreversible. All history for this product will be deleted.
                </div>
            </div>
            <div class="modal-footer" style="padding:10px 20px 14px;gap:8px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="deleteProductConfirmBtn">
                    <i class="ri-delete-bin-5-line"></i> Yes, Delete Product
                </button>
            </div>
        </div>
    </div>
</div>


{{-- ══ INFO MODAL ══════════════════════════════════════════════════════ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Action Centre</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px;">
                <table style="width:100%;font-size:13px;border-collapse:collapse;">
                    <tbody>
                        @foreach([
                            ['Select product',  'Search for a base product in the search bar and click it — the page reloads with the product displayed in the counter bar and branch cards below.'],
                            ['Enter quantity',  'Type a quantity per branch. Saved automatically as a pending delivery note. A green check appears next to the branch name after each save.'],
                            ['Branch price badge', 'An amber badge next to the branch name shows the branch-specific selling price when it differs from the base product price.'],
                            ['Distribution / Unit of Issue', 'Counter bar shows total units entered. Set unit of issue (e.g. loaves per crate) to see the quantity distributed.'],
                            ['Summary button',  'Shows the total pending distribution value across all branches and products for the selected category and date.'],
                            ['Submit',          'Marks pending delivery notes as submitted and adds quantities to branch stock.'],
                            ['Submit All',      'Submits ALL pending notes for the selected date across all products (via the Actions menu).'],
                            ['Cancel',          'Deletes all pending (unsubmitted) notes for the current product on the selected date.'],
                            ['Date — Today',    'Uses the current system date, auto-updates each day.'],
                            ['Date — Custom',   'Use for historical or future deliveries. Stored in your filter preferences.'],
                            ['Delete Product',  'Permanently removes the product from the base catalogue, all branch records, and delivery notes.'],
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


{{-- ══ SUBMIT CONFIRMATION MODAL ═══════════════════════════════════════ --}}
<div class="modal fade" id="submitConfirmModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-corner-up-right-line"></i> Submit Delivery Notes</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 22px;">
                <div style="display:flex;align-items:flex-start;gap:14px;">
                    <div style="width:42px;height:42px;border-radius:50%;background:#eff3ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="ri-corner-up-right-line" style="font-size:20px;color:#4B5EBD;"></i>
                    </div>
                    <div>
                        <p style="font-size:13px;font-weight:600;color:#1e293b;margin:0 0 6px;">Submit pending delivery notes?</p>
                        <p style="font-size:12px;color:#64748b;margin:0;">
                            All pending notes for <strong id="submitConfirmProduct"></strong>
                            on <strong id="submitConfirmDate"></strong> will be marked submitted
                            and branch stock will be updated.
                        </p>
                    </div>
                </div>
                <div style="background:#eff3ff;border-left:3px solid #4B5EBD;border-radius:0 5px 5px 0;padding:8px 12px;font-size:11px;color:#3b4fa0;margin-top:14px;">
                    <i class="ri-information-line me-1"></i> This action cannot be undone.
                </div>
            </div>
            <div class="modal-footer" style="padding:10px 20px 14px;gap:8px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="submitConfirmBtn">
                    <i class="ri-corner-up-right-line"></i> Yes, Submit
                </button>
            </div>
        </div>
    </div>
</div>


{{-- ══ CANCEL CONFIRMATION MODAL ═══════════════════════════════════════ --}}
<div class="modal fade" id="cancelConfirmModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header mh-amber">
                <h5 class="modal-title mh-title"><i class="ri-delete-bin-2-line"></i> Cancel Pending Notes</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 22px;">
                <div style="display:flex;align-items:flex-start;gap:14px;">
                    <div style="width:42px;height:42px;border-radius:50%;background:#fff8e1;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="ri-delete-bin-2-line" style="font-size:20px;color:#d97706;"></i>
                    </div>
                    <div>
                        <p style="font-size:13px;font-weight:600;color:#1e293b;margin:0 0 6px;">Cancel all pending notes?</p>
                        <p style="font-size:12px;color:#64748b;margin:0;">
                            All unsubmitted delivery notes for <strong id="cancelConfirmProduct"></strong>
                            on <strong id="cancelConfirmDate"></strong> will be permanently deleted.
                        </p>
                    </div>
                </div>
                <div style="background:#fff8e1;border-left:3px solid #f59e0b;border-radius:0 5px 5px 0;padding:8px 12px;font-size:11px;color:#92400e;margin-top:14px;">
                    <i class="ri-alert-line me-1"></i> This cannot be undone. Stock will not be affected.
                </div>
            </div>
            <div class="modal-footer" style="padding:10px 20px 14px;gap:8px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">No, Keep them</button>
                <button type="button" class="btn btn-warning btn-sm text-white" id="cancelConfirmBtn">
                    <i class="ri-delete-bin-2-line"></i> Yes, Cancel Notes
                </button>
            </div>
        </div>
    </div>
</div>


{{-- ══ SUBMIT ALL CONFIRMATION MODAL ═══════════════════════════════════ --}}
<div class="modal fade" id="submitAllModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:420px;">
        <div class="modal-content">
            <div class="modal-header mh-blue">
                <h5 class="modal-title mh-title"><i class="ri-send-plane-fill"></i> Submit All Pending Notes</h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 22px;">
                <div style="display:flex;align-items:flex-start;gap:14px;">
                    <div style="width:42px;height:42px;border-radius:50%;background:#eff3ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="ri-send-plane-fill" style="font-size:20px;color:#4B5EBD;"></i>
                    </div>
                    <div>
                        <p style="font-size:13px;font-weight:600;color:#1e293b;margin:0 0 6px;">Submit all pending delivery notes?</p>
                        <p style="font-size:12px;color:#64748b;margin:0;">
                            All unsubmitted notes for <strong id="submitAllDateLabel"></strong>
                            across every product will be marked submitted and branch stock updated.
                        </p>
                    </div>
                </div>
                <div style="background:#fff8e1;border-left:3px solid #f59e0b;border-radius:0 5px 5px 0;padding:8px 12px;font-size:11px;color:#92400e;margin-top:14px;">
                    <i class="ri-alert-line me-1"></i> This action cannot be undone. Notes with zero quantity will be skipped.
                </div>
            </div>
            <div class="modal-footer" style="padding:10px 20px 14px;gap:8px;">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="submitAllConfirmBtn">
                    <i class="ri-send-plane-fill"></i> Yes, Submit All
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
@section('scripts')
<script>
$(document).ready(function () {

    /* ── CSRF ─────────────────────────────────────────────────────────── */
    var csrfToken = $('meta[name="csrf-token"]').attr('content')
                 || $('input[name="_token"]').first().val();
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });

    /* ── Toastr ──────────────────────────────────────────────────────── */
    toastr.options = { timeOut: 5000, progressBar: true, positionClass: 'toast-top-end', closeButton: true };

    /* ── Helpers ─────────────────────────────────────────────────────── */
    function showProgress() { $('#progressBar').show(); }
    function hideProgress() { $('#progressBar').hide(); }

    function fmtPrice(n) {
        return parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function fmt(n, d) {
        d = d === undefined ? 2 : d;
        if (n === null || n === undefined || n === '') return '—';
        return parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d });
    }

    function handleAjaxError(xhr) {
        var status = xhr.status;
        var json   = null;
        try { json = xhr.responseJSON || JSON.parse(xhr.responseText); } catch (e) {}
        if (status === 419) { toastr.error('Your session has expired. The page will refresh in a moment.', 'Session Expired'); setTimeout(function () { location.reload(); }, 2000); return; }
        if (status === 0)   { toastr.error('No response from server. Check your connection.', 'Connection Error'); return; }
        if (status === 404) { toastr.error((json && (json.message || json.error)) || 'The requested endpoint was not found.', 'Not Found (404)'); return; }
        if (status === 405) { toastr.error('HTTP method not allowed for this route.', 'Method Not Allowed (405)'); return; }
        if (status === 422) { var msgs = Object.values((json && json.errors) || {}).flat().join('\n'); toastr.error(msgs || (json && json.message) || 'Validation failed.', 'Validation Error'); return; }
        if (status === 500) { toastr.error((json && (json.message || json.error)) || 'An internal server error occurred.', 'Server Error (500)'); return; }
        toastr.error((json && (json.message || json.error)) || ('Unexpected error (HTTP ' + status + ').'), 'Error');
    }

    /* ── Product map ─────────────────────────────────────────────────── */
    var productMap = {};
    @foreach($baseProducts as $bp)
    productMap['{{ $bp->id }}'] = {
        id:       '{{ $bp->id }}',
        name:     @json($bp->name),
        unit:     @json($bp->unit),
        price:    {{ (float)($bp->selling_price ?? 0) }},
        cost:     {{ (float)($bp->cost_price ?? 0) }},
        code:     @json($bp->code ?? ''),
        supplier: @json($bp->supplier ?? ''),
    };
    @endforeach

    var activeProductId = '{{ $product->id ?? "" }}';
    var activeDate      = '{{ $date }}';
    var allProducts     = Object.values(productMap);

    /* ── Distribution counter ────────────────────────────────────────── */
    function recalcDistribution() {
        var total = 0;
        $('.bc-input').each(function () {
            var v = parseFloat($(this).val());
            if (!isNaN(v) && v > 0) total += v;
        });
        var divider = parseFloat($('#dividerInput').val());
        if (isNaN(divider) || divider <= 0) divider = 1;
        var result = total / divider;
        $('#distTotalVal').text(total  % 1 === 0 ? total  : total.toFixed(3));
        $('#distResultVal').text(result % 1 === 0 ? result : result.toFixed(3));
    }

    $(document).on('input change', '.bc-input', recalcDistribution);
    $('#dividerInput').on('input', recalcDistribution)
                     .on('blur',  function () { var v = parseFloat($(this).val()); if (isNaN(v) || v <= 0) $(this).val(''); recalcDistribution(); });
    recalcDistribution();

    /* ── Category distribution summary modal ─────────────────────────── */
    $('#catSummaryBtn').on('click', function (e) {
        e.preventDefault();
        $('#catSummaryModal').modal('show');
    });

    /* ── Product search dropdown ─────────────────────────────────────── */
    var $dropdown = $('#productDropdown');

    function renderDropdown(list) {
        if (!list.length) { $dropdown.html('<div class="pd-empty"><i class="ri-search-line"></i> No products found</div>'); return; }
        var html = '';
        list.slice(0, 40).forEach(function (p) {
            html += '<div class="pd-item" data-id="' + p.id + '">' +
                '<div class="pd-item-icon"><i class="ri-box-3-line"></i></div>' +
                '<div class="pd-item-body">' +
                '<div class="pd-name">' + $('<span>').text(p.name).html() + '</div>' +
                '<div class="pd-meta">' + (p.unit || 'Each') + (p.code ? ' · ' + p.code : '') + '</div>' +
                '</div>' +
                '<div class="pd-item-price">MWK ' + fmt(p.price) + '</div>' +
                '</div>';
        });
        $dropdown.html(html);
    }

    $('#productSearch').on('focus', function () { renderDropdown(allProducts.slice(0, 20)); $dropdown.addClass('open'); });
    $('#productSearch').on('input', function () {
        var val = $(this).val().toLowerCase().trim();
        if (!val) { renderDropdown(allProducts.slice(0, 20)); $dropdown.addClass('open'); return; }
        var filtered = allProducts.filter(function (p) {
            return p.name.toLowerCase().includes(val) || (p.code && p.code.toLowerCase().includes(val));
        });
        renderDropdown(filtered);
        $dropdown.addClass('open');
    });
    $(document).on('click', function (e) { if (!$(e.target).closest('.search-input-wrap').length) $dropdown.removeClass('open'); });

    /* ── Selecting a product ─────────────────────────────────────────── */
    $dropdown.on('click', '.pd-item', function () {
        var id = String($(this).data('id'));
        if (!productMap[id]) return;
        $dropdown.removeClass('open');
        showProgress();
        $('#selectProductFormId').val(id);
        $('#selectProductForm').submit();
    });

    /* ── Keyboard shortcut ⌘K / Ctrl+K ──────────────────────────────── */
    $(document).on('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') { e.preventDefault(); $('#productSearch').val('').focus(); }
        if (e.key === 'Escape') $dropdown.removeClass('open');
    });

    /* ── Edit product via product pill ──────────────────────────────── */
    function renderBranchOverrides(overrides) {
        var $list = $('#ep-branch-overrides-list');
        var $wrap = $('#ep-branch-overrides-wrap');
        $list.empty();

        if (!overrides || !overrides.length) {
            $wrap.hide();
            return;
        }

        overrides.forEach(function (o) {
            // Store branch_id and branch_name as data attrs so the success
            // handler can update the matching card badge without a reload.
            var row = $(
                '<div class="ep-branch-override-row"' +
                    ' data-branch-product-id="' + o.id + '"' +
                    ' data-branch-id="' + o.branch_id + '"' +
                    ' data-branch-name="' + $('<span>').text(o.branch_name).html() + '">' +
                    '<span class="ep-bo-name">' + $('<span>').text(o.branch_name).html() + '</span>' +
                    '<span style="font-size:11px;color:#94a3b8;">MWK</span>' +
                    '<input type="number" inputmode="decimal" min="0" ' +
                           'class="form-control form-control-sm ep-bo-input" ' +
                           'value="' + o.selling_price + '">' +
                '</div>'
            );
            $list.append(row);
        });

        $wrap.show();
    }

    function loadBranchOverrides(baseProductId) {
        var $wrap = $('#ep-branch-overrides-wrap');
        var $list = $('#ep-branch-overrides-list');
        $list.html('<div class="ep-branch-overrides-loading"><i class="ri-loader-4-line"></i> Loading branch prices…</div>');
        $wrap.show();

        $.ajax({
            type: 'GET',
            url: '{{ route("retail.operations.baseproducts.branchoverrides") }}',
            data: { base_product_id: baseProductId },
            success: function (data) {
                if (data.overrides && data.overrides.length) {
                    renderBranchOverrides(data.overrides);
                } else {
                    $wrap.hide();
                    $list.empty();
                }
            },
            error: function () {
                $list.html('<div class="ep-branch-overrides-empty"><i class="ri-error-warning-line"></i> Could not load branch prices.</div>');
            },
        });
    }

    function openEditModal() {
        if (!activeProductId) return;
        var p = productMap[activeProductId];
        if (!p) return;
        $('#ep-id').val(p.id);
        $('#epModalName').text(p.name);
        $('#ep-name').val(p.name);
        $('#ep-sell').val(p.price);
        $('#ep-cost').val(p.cost || '');
        $('#ep-unit').val(p.unit);
        $('#ep-code').val(p.code);
        $('#ep-branch-overrides-wrap').hide();
        $('#ep-branch-overrides-list').empty();
        loadBranchOverrides(p.id);
        $('#editProductModal').modal('show');
    }
    $('#pcr-edit-icon').on('click', function () { openEditModal(); });

    /* ── Save edit product — no page reload ──────────────────────────── */
    $('#saveEditProductBtn').on('click', function () {
        var name = $('#ep-name').val().trim();
        var sell = $('#ep-sell').val();
        if (!name) { toastr.warning('Product name is required.'); $('#ep-name').focus(); return; }
        if (sell === '' || parseFloat(sell) < 0) { toastr.warning('Selling price is required.'); $('#ep-sell').focus(); return; }

        var productId = $('#ep-id').val();
        var p         = productMap[productId];

        var payload = {
            id:            productId,
            name:          name,
            selling_price: sell,
            cost_price:    $('#ep-cost').val(),
            unit:          $('#ep-unit').val(),
            code:          $('#ep-code').val(),
            supplier:      p ? p.supplier : '',
        };

        // Collect branch override rows
        $('#ep-branch-overrides-list .ep-branch-override-row').each(function (i) {
            var bpId  = $(this).data('branch-product-id');
            var price = $(this).find('.ep-bo-input').val();
            if (bpId && price !== '' && price !== null) {
                payload['branch_overrides[' + i + '][id]']            = bpId;
                payload['branch_overrides[' + i + '][selling_price]'] = price;
            }
        });

        // Snapshot override rows BEFORE the AJAX call so we can update
        // badges in the success handler (modal is hidden by then).
        var overrideSnapshot = [];
        $('#ep-branch-overrides-list .ep-branch-override-row').each(function () {
            overrideSnapshot.push({
                branchId:  $(this).data('branch-id'),
                price:     parseFloat($(this).find('.ep-bo-input').val()),
            });
        });

        var $btn = $(this).prop('disabled', true);
        showProgress();

        $.ajax({
            type:     'POST',
            url:      '{{ route("retail.operations.baseproducts.update") }}',
            data:     payload,
            complete: function () {
                hideProgress();
                $btn.prop('disabled', false);
            },
            success: function (data) {
                if (data.status !== 201) {
                    toastr.error(data.error || 'Failed to update product.', 'Error');
                    return;
                }

                // ── Close modal first so toastr renders on top ────────
                $('#editProductModal').modal('hide');

                var newSell = parseFloat(sell);
                var newCost = parseFloat($('#ep-cost').val()) || 0;
                var newUnit = $('#ep-unit').val().trim() || (p ? p.unit : 'Each');
                var newCode = $('#ep-code').val().trim();

                // ── 1. Update productMap in memory ────────────────────
                if (productMap[productId]) {
                    productMap[productId].name  = name;
                    productMap[productId].price = newSell;
                    productMap[productId].cost  = newCost;
                    productMap[productId].unit  = newUnit;
                    productMap[productId].code  = newCode;
                }

                // ── 2. Update the product pill ────────────────────────
                if (productId === activeProductId) {
                    $('#pcrName').text(name);
                    $('#pcrUnit').text(newUnit);
                    $('#pcrPrice').text('MWK ' + fmtPrice(newSell));
                    if (newCode) {
                        $('#pcrCode').text(newCode).show();
                    } else {
                        $('#pcrCode').hide();
                    }
                    $('#epModalName').text(name);
                }

                // ── 3. Update branch card price badges ────────────────
                overrideSnapshot.forEach(function (ov) {
                    if (!ov.branchId || isNaN(ov.price)) return;

                    var $card  = $('.branch-card[data-branch-id="' + ov.branchId + '"]');
                    if (!$card.length) return;

                    var $badge = $card.find('.bc-price-badge');

                    if (Math.abs(ov.price - newSell) > 0.001) {
                        // Override price still differs from base — show/update badge
                        var badgeHtml = '<i class="ri-price-tag-3-line"></i> MWK ' + fmtPrice(ov.price);
                        var badgeTitle = 'Branch-specific selling price (overrides base price of MWK ' + fmtPrice(newSell) + ')';

                        if ($badge.length) {
                            $badge.attr('title', badgeTitle).html(badgeHtml);
                        } else {
                            $card.find('.bc-saved-check').before(
                                $('<span class="bc-price-badge"></span>')
                                    .attr('title', badgeTitle)
                                    .html(badgeHtml)
                            );
                        }
                    } else {
                        // Override now equals base — remove badge
                        $badge.remove();
                    }
                });

                // ── 4. Notify ─────────────────────────────────────────
                toastr.success(data.success || 'Product updated successfully.', 'Saved');
            },
            error: handleAjaxError,
        });
    });

    /* ── Actions modal ───────────────────────────────────────────────── */
    $('#actionsBtn').on('click', function (e) { e.preventDefault(); $('#actionsModal').modal('show'); });

    /* ── Action sheet → Delete product ──────────────────────────────── */
    $('#asDeleteProductBtn').on('click', function () {
        $('#actionsModal').modal('hide');
        setTimeout(function () {
            if (!activeProductId) return;
            var p = productMap[activeProductId];
            $('#deleteProductName').text(p ? p.name : 'this product');
            $('#deleteProductModal').modal('show');
        }, 300);
    });
    $('#deleteProductConfirmBtn').on('click', function () {
        $('#deleteProductModal').modal('hide');
        var $btn = $(this).prop('disabled', true);
        showProgress();
        $.ajax({
            type: 'POST', url: '{{ route("retail.operations.actioncenter.product.delete") }}',
            data: { base_product_id: activeProductId },
            complete: function () { hideProgress(); $btn.prop('disabled', false); },
            success: function (data) {
                if (data.success) { toastr.success(data.success); showProgress(); $('#selectProductFormId').val(''); $('#selectProductForm').submit(); }
                else { toastr.error(data.error || 'Failed to delete product.'); }
            },
            error: handleAjaxError,
        });
    });

    /* ── Action sheet → Add new product ─────────────────────────────── */
    $('#asAddProductBtn').on('click', function () {
        $('#actionsModal').modal('hide');
        setTimeout(function () {
            $('#ap-name, #ap-code').val(''); $('#ap-sell, #ap-cost').val('');
            $('#ap-unit').val('Each'); $('#ap-supplier').val('');
            $('#addProductModal').modal('show');
            setTimeout(function () { $('#ap-name').focus(); }, 400);
        }, 300);
    });
    $('#saveNewProductBtn').on('click', function () {
        var name = $('#ap-name').val().trim(), supplier = $('#ap-supplier').val(), sell = $('#ap-sell').val();
        if (!name)     { toastr.warning('Product name is required.'); $('#ap-name').focus(); return; }
        if (!supplier) { toastr.warning('Please select a supplier.');  $('#ap-supplier').focus(); return; }
        if (!sell || parseFloat(sell) < 0) { toastr.warning('Selling price is required.'); $('#ap-sell').focus(); return; }
        var self = $(this).prop('disabled', true);
        showProgress();
        $.ajax({
            type: 'POST', url: '{{ route("retail.operations.baseproducts.insert") }}',
            data: { name: name, supplier: supplier, selling_price: sell, cost_price: $('#ap-cost').val(), unit: $('#ap-unit').val() || 'Each', code: $('#ap-code').val(), is_product: 1 },
            complete: function () { hideProgress(); self.prop('disabled', false); },
            success: function (data) {
                if (data.status === 201) {
                    toastr.success('"' + name + '" added to catalogue.');
                    $('#addProductModal').modal('hide');
                    showProgress();
                    $('#selectProductFormId').val(String(data.product.id));
                    $('#selectProductForm').submit();
                } else { toastr.error(data.error || 'Failed to save product.'); }
            },
            error: handleAjaxError,
        });
    });

    /* ── Route constants ─────────────────────────────────────────────── */
    var routes = {
        saveDnote: '{{ route("retail.operations.actioncenter.dnote.save") }}',
        submit:    '{{ route("retail.operations.actioncenter.dnote.submit") }}',
        submitAll: '{{ route("retail.operations.actioncenter.dnote.submit-all") }}',
        cancel:    '{{ route("retail.operations.actioncenter.dnote.cancel") }}',
    };

    /* ── Auto-save branch inputs ─────────────────────────────────────── */
    var saveTimer = {};
    $(document).on('input', '.bc-input', function () {
        var $input    = $(this);
        var branchId  = $input.data('branch-id');
        var productId = $input.data('product-id');
        var quantity  = $input.val();
        var $check    = $('#check-' + branchId);

        clearTimeout(saveTimer[branchId]);

        if (quantity === '' || isNaN(parseFloat(quantity))) return;

        saveTimer[branchId] = setTimeout(function () {
            $.ajax({
                type: 'POST', url: routes.saveDnote,
                data: { branch_id: branchId, base_product_id: productId, quantity: quantity, delivery_date: activeDate },
                success: function (res) {
                    if (res.status === 200 || res.status === 201) {
                        $check.removeClass('pulse');
                        void $check[0].offsetWidth;
                        $check.addClass('show pulse');
                    } else {
                        toastr.error('Failed to save for ' + $input.data('branch-name') + '.', 'Save Error');
                    }
                },
                error: function (xhr) { handleAjaxError(xhr); },
            });
        }, 600);
    });

    /* ── Action sheet → Submit (single product) ──────────────────────── */
    $('#asSubmitBtn').on('click', function () {
        $('#actionsModal').modal('hide');
        setTimeout(function () {
            if (!activeProductId) { toastr.warning('Please select a product first.'); return; }
            var p = productMap[activeProductId];
            $('#submitConfirmProduct').text(p ? p.name : '');
            $('#submitConfirmDate').text(activeDate);
            $('#submitConfirmModal').modal('show');
        }, 300);
    });
    $('#submitConfirmBtn').on('click', function () {
        $('#submitConfirmModal').modal('hide');
        showProgress();
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            type: 'POST', url: routes.submit,
            data: { base_product_id: activeProductId, delivery_date: activeDate },
            complete: function () { hideProgress(); $btn.prop('disabled', false); },
            success: function (data) {
                if (data.success) { toastr.success(data.success); setTimeout(function () { location.reload(); }, 800); }
                if (data.info) toastr.info(data.info);
            },
            error: handleAjaxError,
        });
    });

    /* ── Action sheet → Submit All ───────────────────────────────────── */
    $('#asSubmitAllBtn').on('click', function () {
        $('#actionsModal').modal('hide');
        setTimeout(function () { $('#submitAllDateLabel').text(activeDate); $('#submitAllModal').modal('show'); }, 300);
    });
    $('#submitAllConfirmBtn').on('click', function () {
        $('#submitAllModal').modal('hide');
        showProgress();
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            type: 'POST', url: routes.submitAll,
            data: { delivery_date: activeDate },
            complete: function () { hideProgress(); $btn.prop('disabled', false); },
            success: function (data) {
                if (data.success) { toastr.success(data.success); setTimeout(function () { location.reload(); }, 800); }
                if (data.info) toastr.info(data.info);
            },
            error: handleAjaxError,
        });
    });

    /* ── Action sheet → Cancel (single product) ──────────────────────── */
    $('#asCancelBtn').on('click', function () {
        $('#actionsModal').modal('hide');
        setTimeout(function () {
            if (!activeProductId) { toastr.warning('Please select a product first.'); return; }
            var p = productMap[activeProductId];
            $('#cancelConfirmProduct').text(p ? p.name : '');
            $('#cancelConfirmDate').text(activeDate);
            $('#cancelConfirmModal').modal('show');
        }, 300);
    });
    $('#cancelConfirmBtn').on('click', function () {
        $('#cancelConfirmModal').modal('hide');
        showProgress();
        var $btn = $(this).prop('disabled', true);
        $.ajax({
            type: 'POST', url: routes.cancel,
            data: { base_product_id: activeProductId, delivery_date: activeDate },
            complete: function () { hideProgress(); $btn.prop('disabled', false); },
            success: function (data) {
                if (data.success) {
                    toastr.success(data.success);
                    $('.bc-input').val('');
                    $('.bc-saved-check').removeClass('show');
                    recalcDistribution();
                }
                if (data.info) toastr.info(data.info);
            },
            error: handleAjaxError,
        });
    });

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
        var d = new Date(val + 'T00:00:00');
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
});
</script>
@endsection

