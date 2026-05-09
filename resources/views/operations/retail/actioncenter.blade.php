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
        ->get(['id', 'name', 'unit', 'selling_price', 'cost_price', 'code']);

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
@endphp

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
    border-radius: 12px 12px 0 0 !important;
    border: none;
}
.ch-inner {
    display: flex; align-items: center;
    padding: 0 14px; height: 48px; gap: 8px;
}
.ch-left  { display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; }
.ch-right { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }

#categorySelectHeader {
    border: none; background: transparent; color: #fff;
    font-size: 15px; font-weight: 600; cursor: pointer;
    padding: 0; outline: none; max-width: 240px;
}
#categorySelectHeader option { color: #1e293b; background: #fff; font-size: 13px; }

.ch-date-chip {
    display: inline-flex; align-items: center; gap: 4px;
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.25);
    border-radius: 20px; padding: 3px 10px;
    font-size: 11px; font-weight: 500; color: #fff;
    white-space: nowrap; cursor: pointer; transition: background .15s;
    user-select: none; margin-left: 4px;
}
.ch-date-chip:hover { background: rgba(255,255,255,0.25); }
.ch-date-chip .mode-badge {
    font-size: 9px; padding: 1px 5px; border-radius: 8px;
    background: rgba(255,255,255,0.2); color: #fff;
    font-weight: 600; letter-spacing: .3px;
}
.ch-date-chip.custom-mode { background: rgba(245,158,11,0.30); border-color: rgba(245,158,11,0.6); }
.ch-date-chip.custom-mode .mode-badge { background: rgba(245,158,11,0.5); }

.ch-btn {
    width: 30px; height: 30px; border-radius: 7px;
    background: #fff; border: 1px solid rgba(255,255,255,0.6);
    color: #4B5EBD; display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 14px; transition: background .15s, box-shadow .15s;
    text-decoration: none; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}
.ch-btn:hover { background: #f0f2ff; color: #3a4ca0; box-shadow: 0 2px 6px rgba(0,0,0,0.15); }
.ch-btn.ch-btn-danger { color: #dc2626; }
.ch-btn.ch-btn-danger:hover { background: #fef2f2; color: #b91c1c; }

/* ── Tabs ─────────────────────────────────────────────────────────────── */
.tab-header-container { background: #f8f9fa; border-bottom: 1px solid #dee2e6; }
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

/* ── Search bar row ───────────────────────────────────────────────────── */
.search-bar-row {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 16px; background: #f0f2fa;
    border-bottom: 1px solid #dde1f0;
    position: relative;
}

/* Search input — col-7 equivalent (~58%) */
.search-input-wrap {
    position: relative; flex: 0 0 58.333%; max-width: 58.333%;
}
.search-input-inner {
    display: flex; align-items: center;
    background: #fff; border: 1.5px solid #c5caec;
    border-radius: 9px; padding: 0 11px; height: 40px;
    transition: border-color .15s, box-shadow .15s; gap: 7px;
}
.search-input-inner:focus-within {
    border-color: #4B5EBD; box-shadow: 0 0 0 3px rgba(75,94,189,0.12);
}
.search-icon-left { color: #94a3b8; font-size: 15px; flex-shrink: 0; }
#productSearch {
    flex: 1; border: none; outline: none;
    font-size: 12px; color: #1e293b; background: transparent; padding: 0;
}
#productSearch::placeholder { color: #b0baca; }

/* Dropdown — matches search bar width */
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

/* Spacer + action buttons */
.sbr-spacer { flex: 1; }
.sbr-btn {
    height: 40px; padding: 0 18px; font-size: 12px; font-weight: 600;
    border-radius: 9px; display: inline-flex; align-items: center; gap: 6px;
    cursor: pointer; border: none; transition: all .15s; white-space: nowrap; flex-shrink: 0;
}
.sbr-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,0.12); }
.sbr-btn:active { transform: none; }
.sbr-submit { background: #4B5EBD; color: #fff; box-shadow: 0 2px 6px rgba(75,94,189,0.35); }
.sbr-submit:hover { background: #3a4ca0; }
.sbr-cancel { background: #dc2626; color: #fff; box-shadow: 0 2px 6px rgba(220,38,38,0.30); }
.sbr-cancel:hover { background: #b91c1c; }

/* ── Product + Counter row ─────────────────────────────────────────────── */
.product-counter-row {
    display: none;
    border-bottom: 1px solid #dee2e6;
    min-height: 60px;
    background: #f8f9fa;
    align-items: stretch;
}
.product-counter-row.visible { display: flex; }

/* Left panel: product pill button */
.pcr-product {
    display: flex; align-items: center;
    padding: 10px 18px;
    background: #f8f9fa;
    border-right: 1px solid #dee2e6;
    flex-shrink: 0;
    min-width: 0;
}
.pcr-product-btn {
    display: inline-flex; align-items: center; gap: 9px;
    background: #e8e9ed; border: 1.5px solid #cdd0dc;
    border-radius: 8px; padding: 7px 14px 7px 9px;
    cursor: pointer; transition: background .15s, border-color .15s;
    max-width: 420px; min-width: 0;
}
.pcr-product-btn:hover { background: #dde0ea; border-color: #b8bcd0; }
.pcr-edit-icon-btn {
    width: 26px; height: 26px; border-radius: 6px;
    background: #fff; border: 1px solid #cdd0dc;
    display: flex; align-items: center; justify-content: center;
    color: #4B5EBD; font-size: 13px; flex-shrink: 0;
}
/* Product name + meta stacked */
.pcr-info { flex: 1; min-width: 0; }
.pcr-name {
    font-size: 13px; font-weight: 700; color: #2d3a8c;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    display: block;
}
.pcr-meta-row {
    display: flex; align-items: center; gap: 6px; margin-top: 2px; flex-wrap: nowrap;
}
.pcr-badge {
    font-size: 9px; font-weight: 600; padding: 1px 6px; border-radius: 4px;
    white-space: nowrap; line-height: 1.6;
}
.pcr-badge-code  { background: #e2e8f0; color: #64748b; }
.pcr-badge-unit  { background: #dbeafe; color: #1d4ed8; }
.pcr-badge-price { background: #d1fae5; color: #065f46; }

/* Right panel: counter — pushed to far right */
.pcr-counter {
    display: flex; align-items: stretch;
    margin-left: auto;
    border-left: 1px solid #dee2e6;
}
.cr-seg {
    display: flex; flex-direction: column; justify-content: center;
    padding: 10px 20px; border-right: 1px solid #dee2e6;
    gap: 3px; min-width: 130px;
    background: #f8f9fa;
}
.cr-seg:last-child { border-right: none; }
.cr-label {
    font-size: 8px; font-weight: 700; color: #94a3b8;
    text-transform: uppercase; letter-spacing: .8px;
}
.cr-val {
    font-size: 22px; font-weight: 800; color: #1e293b;
    line-height: 1; font-variant-numeric: tabular-nums;
}
.cr-val.accent { color: #4B5EBD; }
.cr-unit-lbl { font-size: 10px; color: #94a3b8; }

/* Unit of Issue input — centered, no spinners, no bg change on focus */
.cr-unit-input-wrap {
    display: flex; align-items: center; justify-content: center;
}
.cr-unit-input {
    width: 90px; height: 38px;
    border: 1.5px solid #c5caec; border-radius: 8px;
    text-align: center; font-size: 18px; font-weight: 700; color: #1e293b;
    background: #fff; outline: none; padding: 0;
    -moz-appearance: textfield;
    transition: border-color .15s;
}
.cr-unit-input::-webkit-outer-spin-button,
.cr-unit-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.cr-unit-input:focus { background: #fff; border-color: #4B5EBD; box-shadow: 0 0 0 3px rgba(75,94,189,0.10); }

/* ── Branch grid ──────────────────────────────────────────────────────── */
.branch-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 14px; padding: 20px;
    background: #fff;
}

.branch-card {
    background: #fff;
    border: 1.5px solid #e4e7f5;
    border-radius: 10px;
    overflow: hidden;
    transition: box-shadow .2s, border-color .2s;
    margin-bottom: 8px;
}
.branch-card:hover { box-shadow: 0 4px 16px rgba(75,94,189,.10); border-color: #c5caec; }
.branch-card.has-value { border-color: #a5b0e6; box-shadow: 0 2px 8px rgba(75,94,189,.12); }

/* Name + stats share one bg */
.bc-header {
    background: #f0f2fa;
    border-bottom: 1.5px solid #dde1f0;
}
.bc-name-row {
    padding: 8px 13px 4px;
    display: flex; align-items: center; gap: 6px;
}
.bc-name { font-size: 11px; font-weight: 700; color: #2d3a8c; flex: 1; }

/* Saved check icon — hidden by default, shown via JS */
.bc-saved-check {
    display: none;
    width: 18px; height: 18px; border-radius: 50%;
    background: #10b981;
    align-items: center; justify-content: center;
    flex-shrink: 0;
}
.bc-saved-check i { font-size: 10px; color: #fff; }
.bc-saved-check.show { display: flex; }

.bc-stats-row {
    display: flex; align-items: center;
    padding: 4px 13px 8px; gap: 0;
}
.bc-stat-piece { display: flex; align-items: baseline; gap: 4px; }
.bc-stat-piece + .bc-stat-piece::before { content: '|'; color: #c5caec; font-size: 11px; margin: 0 8px; }
.bc-stat-label { font-size: 9px; font-weight: 600; color: #b0baca; text-transform: uppercase; letter-spacing: .5px; }
.bc-stat-val { font-size: 12px; font-weight: 700; font-variant-numeric: tabular-nums; line-height: 1; }
.bc-stat-val.v-stock     { color: #a0aec0; }
.bc-stat-val.v-delivered { color: #93a3d4; }
.bc-stat-val.v-order     { color: #cbd5e1; }

/* Input row — border left, right, bottom only; no bg change on hover/focus */
.bc-input-row {
    display: flex; align-items: center;
    position: relative;
}
.bc-input {
    flex: 1; width: 100%; text-align: center;
    font-size: 26px; font-weight: 800;
    border-top: none;
    border-left: 1.5px solid #e4e7f5;
    border-right: 1.5px solid #e4e7f5;
    border-bottom: 1.5px solid #e4e7f5;
    border-radius: 0 0 8px 8px;
    padding: 10px 8px; outline: none;
    color: #1e293b; background: #fff;
    font-variant-numeric: tabular-nums;
    -moz-appearance: textfield;
    transition: border-color .15s;
}
.bc-input::-webkit-outer-spin-button,
.bc-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
.bc-input:focus { background: #fff; }
.bc-input:hover { background: #fff; }
.bc-input::placeholder { color: #dde1f0; }

/* Save state dot */
.bc-input-row .save-dot {
    position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
    width: 6px; height: 6px; border-radius: 50%;
    transition: background .2s, opacity .2s; opacity: 0; pointer-events: none;
}
.bc-input-row.state-saving .save-dot { background: #f59e0b; opacity: 1; animation: dot-pulse .8s infinite; }
.bc-input-row.state-saved  .save-dot { background: #10b981; opacity: 1; }
.bc-input-row.state-error  .save-dot { background: #f87171; opacity: 1; }

@keyframes dot-pulse {
    0%, 100% { opacity: 1; }
    50%       { opacity: .3; }
}

/* ── Placeholders ─────────────────────────────────────────────────────── */
.no-category-wrap { padding: 60px 16px; text-align: center; }
.no-category-wrap i { font-size: 48px; color: #dde1f0; display: block; margin-bottom: 14px; }
.no-category-wrap p { color: #94a3b8; font-size: 13px; }
.no-product-placeholder { padding: 50px 16px; text-align: center; color: #94a3b8; grid-column: 1 / -1; }
.no-product-placeholder i { font-size: 40px; color: #dde1f0; display: block; margin-bottom: 10px; }
.no-product-placeholder p { font-size: 12px; margin: 0; }

/* ── Modals ───────────────────────────────────────────────────────────── */
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
</style>

{{-- Hidden form: saves selected product_id then reloads --}}
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

{{-- ══ Card header ══════════════════════════════════════════════════════ --}}
<div class="card-header">
    <div class="ch-inner">
        <div class="ch-left">
            <i class="ri-store-2-line" style="color:#fff;font-size:17px;flex-shrink:0;"></i>

            <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
                  id="headerCategoryForm" style="margin:0;display:inline;">
                @csrf
                <input type="hidden" name="user_id" value="{{ Auth::id() }}">
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

            @if($selectedCategory)
            <div class="ch-date-chip {{ $isCustom ? 'custom-mode' : '' }}" id="dateChip" title="Change delivery date">
                <i class="ri-calendar-line" style="font-size:11px;"></i>
                <span id="dateChipText">{{ $displayDate }}</span>
                <span class="mode-badge" id="dateChipBadge">{{ $isCustom ? 'Custom' : 'Today' }}</span>
            </div>
            @endif
        </div>

        @if($selectedCategory)
        <div class="ch-right">
            <a href="#" class="ch-btn" id="submitAllBtn" title="Submit all pending notes">
                <i class="ri-send-plane-fill"></i>
            </a>
            <a href="#" class="ch-btn" id="addProductBtn" title="Add new product">
                <i class="ri-add-circle-line"></i>
            </a>
            <a href="#" class="ch-btn" id="dateModalBtn" title="Change delivery date">
                <i class="ri-calendar-event-line"></i>
            </a>
            @if($product)
            <a href="#" class="ch-btn ch-btn-danger" id="deleteProductBtn" title="Delete selected product">
                <i class="ri-delete-bin-5-line"></i>
            </a>
            @endif
            <a href="#" class="ch-btn" id="infoBtn" title="About Action Centre">
                <i class="ri-information-line"></i>
            </a>
        </div>
        @else
        <div class="ch-right">
            <a href="#" class="ch-btn" id="infoBtn" title="About Action Centre">
                <i class="ri-information-line"></i>
            </a>
        </div>
        @endif
    </div>
</div>

{{-- ══ Tabs ═══════════════════════════════════════════════════════════════ --}}
<div class="tab-header-container">
    <ul class="nav nav-pills mb-0">
        <li class="nav-item">
            <a href="{{ route('retail.operations.actioncenter') }}" class="nav-link active">
                <i class="ri-send-plane-line"></i> Action Centre
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('retail.operations.deliverynotes') }}" class="nav-link">
                <i class="ri-file-list-3-line"></i> Delivery Notes
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('retail.operations.pricechanges') }}" class="nav-link">
                <i class="ri-price-tag-3-line"></i> Price Changes
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

{{-- ══ Row 1: Search bar ══════════════════════════════════════════════════ --}}
<div class="search-bar-row">
    {{-- Search input — col-7 width --}}
    <div class="search-input-wrap">
        <div class="search-input-inner">
            <i class="ri-search-2-line search-icon-left"></i>
            <input type="text" id="productSearch"
                   placeholder="Search product by name or code…"
                   autocomplete="off">
        </div>
        <div id="productDropdown"></div>
    </div>

    <div class="sbr-spacer"></div>

    <button class="sbr-btn sbr-submit" id="submitBtn">
        <i class="ri-corner-up-right-line"></i> Submit
    </button>
    <button class="sbr-btn sbr-cancel" id="cancelBtn">
        <i class="ri-delete-bin-2-line"></i> Cancel
    </button>
</div>

{{-- ══ Row 2: Product pill (left) + Counter segments (far right) ══════════ --}}
<div class="product-counter-row {{ $product ? 'visible' : '' }}" id="productCounterRow">

    {{-- Left: product pill button with name + meta --}}
    <div class="pcr-product">
        <div class="pcr-product-btn" id="pcr-edit-icon" title="Edit product">
            <div class="pcr-edit-icon-btn">
                <i class="ri-edit-box-line"></i>
            </div>
            <div class="pcr-info">
                <span class="pcr-name" id="pcrName">{{ $product ? $product->name : '' }}</span>
                <div class="pcr-meta-row" id="pcrMetaRow">
                    @if($product)
                        @if($product->code)
                        <span class="pcr-badge pcr-badge-code" id="pcrCode">{{ $product->code }}</span>
                        @endif
                        <span class="pcr-badge pcr-badge-unit" id="pcrUnit">{{ $product->unit }}</span>
                        <span class="pcr-badge pcr-badge-price" id="pcrPrice">MWK {{ number_format((float)$product->selling_price, 2) }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Right: counter segments — pushed to far right --}}
    <div class="pcr-counter">

        {{-- Distribution --}}
        <div class="cr-seg">
            <span class="cr-label">Distribution</span>
            <div style="display:flex;align-items:baseline;gap:4px;">
                <span class="cr-val" id="distTotalVal">0</span>
                <span class="cr-unit-lbl" id="distTotalUnit">{{ $product ? $product->unit : '' }}</span>
            </div>
        </div>

        {{-- Unit of Issue --}}
        <div class="cr-seg">
            <span class="cr-label">Unit of Issue</span>
            <div class="cr-unit-input-wrap">
                <input type="number" class="cr-unit-input" id="dividerInput" value="1" min="1">
            </div>
        </div>

        {{-- Quantity Distributed --}}
        <div class="cr-seg">
            <span class="cr-label">Quantity Distributed</span>
            <div style="display:flex;align-items:baseline;gap:4px;">
                <span class="cr-val accent" id="distResultVal">0</span>
                <span class="cr-unit-lbl" id="distResultUnit">{{ $product ? $product->unit : '' }}</span>
            </div>
        </div>

    </div>
</div>

{{-- ══ Branch grid ══════════════════════════════════════════════════════ --}}
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
            @endphp
            <div class="branch-card {{ $bPending !== null ? 'has-value' : '' }}"
                 data-branch-id="{{ $branch->id }}"
                 data-product-id="{{ $product->id }}">

                <div class="bc-header">
                    <div class="bc-name-row">
                        <span class="bc-name">{{ $branch->name }}</span>
                        {{-- Check icon: hidden by default, shown via JS after save, cleared on reload --}}
                        <span class="bc-saved-check" id="check-{{ $branch->id }}" title="Saved">
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
                           class="bc-input"
                           placeholder=""
                           value="{{ $bPending !== null ? $bPending : '' }}"
                           data-branch-id="{{ $branch->id }}"
                           data-product-id="{{ $product->id }}"
                           data-branch-name="{{ $branch->name }}">
                    <span class="save-dot"></span>
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

@endif {{-- end selectedCategory --}}

</div>{{-- .card --}}
</div></div></div>


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
                        <input class="form-control form-control-sm" type="number" step="0.01" min="0" id="ap-sell" placeholder="0.00">
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold" style="font-size:12px;">Cost Price</label>
                        <input class="form-control form-control-sm" type="number" step="0.01" min="0" id="ap-cost" placeholder="0.00">
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
                <h5 class="modal-title mh-title"><i class="ri-edit-box-line"></i> Edit Product — <span id="epModalName" style="font-weight:400;opacity:.85;"></span></h5>
                <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:18px 20px 10px;">
                <div class="alert border-0 py-2 px-3 mb-3" style="background:#fff8e1;border-left:3px solid #f59e0b;border-radius:0 5px 5px 0;font-size:12px;color:#92400e;">
                    <i class="ri-alert-line me-1"></i>
                    Changes here update the <strong>base catalogue</strong> price for all branches.
                </div>
                <input type="hidden" id="ep-id">
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
                        <input class="form-control form-control-sm" type="number" step="0.01" min="0" id="ep-sell">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold" style="font-size:12px;">Cost Price</label>
                        <input class="form-control form-control-sm" type="number" step="0.01" min="0" id="ep-cost">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label fw-semibold" style="font-size:12px;">Unit</label>
                    <input class="form-control form-control-sm" type="text" id="ep-unit">
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
                            ['Distribution / Unit of Issue', 'Counter bar shows total units entered. Set unit of issue (e.g. loaves per crate) to see the quantity distributed.'],
                            ['Submit',          'Marks pending delivery notes as submitted and adds quantities to branch stock.'],
                            ['Submit All',      'Submits ALL pending notes for the selected date across all products (header icon).'],
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

    /* ── Toastr ─────────────────────────────────────────────────────────── */
    toastr.options = { timeOut: 5000, progressBar: true, positionClass: 'toast-top-end', closeButton: true };
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    /* ── Helpers ─────────────────────────────────────────────────────────── */
    function showProgress() { $('#progressBar').show(); }
    function hideProgress() { $('#progressBar').hide(); }
    function fmt(n, d) {
        d = d === undefined ? 2 : d;
        if (n === null || n === undefined || n === '') return '—';
        return parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d });
    }
    function handleAjaxError(xhr) {
        if (xhr.status === 0 && xhr.readyState === 0) {
            toastr.error('Request timed out — check your connection.', 'Timeout');
        } else if (xhr.status === 422) {
            var msgs = '';
            $.each(xhr.responseJSON && xhr.responseJSON.errors ? xhr.responseJSON.errors : {}, function (k, v) { msgs += v + '\n'; });
            toastr.error(msgs || 'Validation failed.', 'Validation Error');
        } else if (xhr.status === 500) {
            toastr.error(xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'Internal server error.', 'Server Error');
        } else {
            toastr.error('An unspecified error occurred.', 'Error');
        }
    }

    /* ── Product map ────────────────────────────────────────────────────── */
    var productMap = {};
    @foreach($baseProducts as $bp)
    productMap['{{ $bp->id }}'] = {
        id:    '{{ $bp->id }}',
        name:  @json($bp->name),
        unit:  @json($bp->unit),
        price: {{ (float)($bp->selling_price ?? 0) }},
        cost:  {{ (float)($bp->cost_price ?? 0) }},
        code:  @json($bp->code ?? ''),
    };
    @endforeach

    var activeProductId = '{{ $product->id ?? "" }}';
    var activeDate      = '{{ $date }}';
    var allProducts     = Object.values(productMap);

    /* ── Distribution counter ─────────────────────────────────────────────── */
    function recalcDistribution() {
        var total = 0;
        $('.bc-input').each(function () {
            var v = parseFloat($(this).val());
            if (!isNaN(v) && v > 0) total += v;
        });
        var divider = parseFloat($('#dividerInput').val()) || 1;
        if (divider < 1) divider = 1;
        var result = total / divider;

        $('#distTotalVal').text(total % 1 === 0 ? total : total.toFixed(3));
        $('#distResultVal').text(result % 1 === 0 ? result : result.toFixed(3));
    }

    $(document).on('input change', '.bc-input', recalcDistribution);

    $('#dividerInput').on('input', function () {
        var v = parseInt($(this).val());
        if (isNaN(v) || v < 1) $(this).val(1);
        recalcDistribution();
    });

    recalcDistribution();

    /* ── Product search dropdown ──────────────────────────────────────────── */
    var $dropdown = $('#productDropdown');

    function renderDropdown(list) {
        if (!list.length) {
            $dropdown.html('<div class="pd-empty"><i class="ri-search-line"></i> No products found</div>');
            return;
        }
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

    $('#productSearch').on('focus', function () {
        renderDropdown(allProducts.slice(0, 20));
        $dropdown.addClass('open');
    });

    $('#productSearch').on('input', function () {
        var val = $(this).val().toLowerCase().trim();
        if (!val) { renderDropdown(allProducts.slice(0, 20)); $dropdown.addClass('open'); return; }
        var filtered = allProducts.filter(function (p) {
            return p.name.toLowerCase().includes(val) || (p.code && p.code.toLowerCase().includes(val));
        });
        renderDropdown(filtered);
        $dropdown.addClass('open');
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.search-input-wrap').length) $dropdown.removeClass('open');
    });

    /* ── Selecting a product ────────────────────────────────────────────── */
    $dropdown.on('click', '.pd-item', function () {
        var id = String($(this).data('id'));
        if (!productMap[id]) return;
        $dropdown.removeClass('open');
        showProgress();
        $('#selectProductFormId').val(id);
        $('#selectProductForm').submit();
    });

    /* ── Keyboard shortcut ⌘K / Ctrl+K ──────────────────────────────────── */
    $(document).on('keydown', function (e) {
        if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
            e.preventDefault();
            $('#productSearch').val('').focus();
        }
        if (e.key === 'Escape') {
            $dropdown.removeClass('open');
        }
    });

    /* ── Edit product via counter row pill ──────────────────────────────── */
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
        $('#editProductModal').modal('show');
    }

    $('#pcr-edit-icon').on('click', function () { openEditModal(); });

    $('#saveEditProductBtn').on('click', function () {
        var name = $('#ep-name').val().trim();
        var sell = $('#ep-sell').val();
        if (!name) { toastr.warning('Product name is required.'); $('#ep-name').focus(); return; }
        if (!sell || parseFloat(sell) < 0) { toastr.warning('Selling price is required.'); $('#ep-sell').focus(); return; }
        var self = $(this).prop('disabled', true);
        showProgress();
        $.ajax({
            type: 'POST',
            url:  '{{ route("retail.operations.baseproducts.update") }}',
            data: { id: $('#ep-id').val(), name: name, selling_price: sell, cost_price: $('#ep-cost').val(), unit: $('#ep-unit').val(), code: $('#ep-code').val() },
            complete: function () { hideProgress(); self.prop('disabled', false); },
            success: function (data) {
                if (data.status === 201) {
                    toastr.success('Product updated.');
                    $('#editProductModal').modal('hide');
                    setTimeout(function () { location.reload(); }, 800);
                } else { toastr.error(data.error || 'Failed to update.'); }
            },
            error: handleAjaxError,
        });
    });

    /* ── Delete product ────────────────────────────────────────────────── */
    $('#deleteProductBtn').on('click', function (e) {
        e.preventDefault();
        if (!activeProductId) return;
        var p = productMap[activeProductId];
        $('#deleteProductName').text(p ? p.name : 'this product');
        $('#deleteProductModal').modal('show');
    });

    $('#deleteProductConfirmBtn').on('click', function () {
        $('#deleteProductModal').modal('hide');
        var $btn = $(this).prop('disabled', true);
        showProgress();
        $.ajax({
            type: 'POST',
            url:  '{{ route("retail.operations.actioncenter.product.delete") }}',
            data: { base_product_id: activeProductId },
            complete: function () { hideProgress(); $btn.prop('disabled', false); },
            success: function (data) {
                if (data.success) {
                    toastr.success(data.success);
                    showProgress();
                    $('#selectProductFormId').val('');
                    $('#selectProductForm').submit();
                } else { toastr.error(data.error || 'Failed to delete product.'); }
            },
            error: handleAjaxError,
        });
    });

    /* ── Add new product ─────────────────────────────────────────────────── */
    $('#addProductBtn').on('click', function (e) {
        e.preventDefault();
        $('#ap-name, #ap-code').val('');
        $('#ap-sell, #ap-cost').val('');
        $('#ap-unit').val('Each');
        $('#ap-supplier').val('');
        $('#addProductModal').modal('show');
        setTimeout(function () { $('#ap-name').focus(); }, 400);
    });

    $('#saveNewProductBtn').on('click', function () {
        var name     = $('#ap-name').val().trim();
        var supplier = $('#ap-supplier').val();
        var sell     = $('#ap-sell').val();
        if (!name)     { toastr.warning('Product name is required.'); $('#ap-name').focus(); return; }
        if (!supplier) { toastr.warning('Please select a supplier.');  $('#ap-supplier').focus(); return; }
        if (!sell || parseFloat(sell) < 0) { toastr.warning('Selling price is required.'); $('#ap-sell').focus(); return; }
        var self = $(this).prop('disabled', true);
        showProgress();
        $.ajax({
            type: 'POST',
            url:  '{{ route("retail.operations.baseproducts.insert") }}',
            data: { name: name, supplier: supplier, selling_price: sell, cost_price: $('#ap-cost').val(), unit: $('#ap-unit').val() || 'Each', code: $('#ap-code').val(), is_product: 1 },
            complete: function () { hideProgress(); self.prop('disabled', false); },
            success: function (data) {
                if (data.status === 201) {
                    toastr.success('"' + name + '" added to catalogue.');
                    var np = data.product;
                    $('#addProductModal').modal('hide');
                    showProgress();
                    $('#selectProductFormId').val(String(np.id));
                    $('#selectProductForm').submit();
                } else { toastr.error(data.error || 'Failed to save product.'); }
            },
            error: handleAjaxError,
        });
    });

    /* ── Route constants ─────────────────────────────────────────────────── */
    var routes = {
        saveDnote: '{{ route("retail.operations.actioncenter.save.dnote") }}',
        submit:    '{{ route("retail.operations.actioncenter.submit") }}',
        submitAll: '{{ route("retail.operations.actioncenter.submitall") }}',
        cancel:    '{{ route("retail.operations.actioncenter.cancel") }}',
    };

    /* ── Auto-save branch inputs ──────────────────────────────────────────── */
    var saveTimer = {};
    $(document).on('input', '.bc-input', function () {
        var $input    = $(this);
        var branchId  = $input.data('branch-id');
        var productId = $input.data('product-id');
        var quantity  = $input.val();
        var $row      = $('#ir-' + branchId);
        var $check    = $('#check-' + branchId);

        $input.closest('.branch-card').toggleClass('has-value', parseFloat(quantity) > 0);
        clearTimeout(saveTimer[branchId]);

        $row.removeClass('state-saved state-error').addClass('state-saving');
        $check.removeClass('show'); // hide check while typing

        if (quantity === '' || isNaN(parseFloat(quantity))) {
            $row.removeClass('state-saving state-saved state-error');
            return;
        }

        saveTimer[branchId] = setTimeout(function () {
            $.ajax({
                type: 'POST',
                url:  routes.saveDnote,
                data: { branch_id: branchId, base_product_id: productId, quantity: quantity, delivery_date: activeDate },
                success: function (res) {
                    $row.removeClass('state-saving state-error');
                    if (res.status === 200 || res.status === 201) {
                        $row.addClass('state-saved');
                        // Show green check icon next to branch name
                        $check.addClass('show');
                        setTimeout(function () { $row.removeClass('state-saved'); }, 2000);
                    } else {
                        $row.addClass('state-error');
                        toastr.error('Failed to save delivery note.');
                    }
                },
                error: function () {
                    $row.removeClass('state-saving').addClass('state-error');
                    toastr.error('Error saving delivery note.');
                },
            });
        }, 600);
    });

    /* ── Submit (single product) ─────────────────────────────────────────── */
    $('#submitBtn').on('click', function () {
        if (!activeProductId) { toastr.warning('Please select a product first.'); return; }
        var p = productMap[activeProductId];
        $('#submitConfirmProduct').text(p ? p.name : '');
        $('#submitConfirmDate').text(activeDate);
        $('#submitConfirmModal').modal('show');
    });

    $('#submitConfirmBtn').on('click', function () {
        $('#submitConfirmModal').modal('hide');
        var $btn = $('#submitBtn').prop('disabled', true);
        showProgress();
        $.ajax({
            type: 'POST',
            url:  routes.submit,
            data: { base_product_id: activeProductId, delivery_date: activeDate },
            complete: function () { hideProgress(); $btn.prop('disabled', false); },
            success: function (data) {
                if (data.success) { toastr.success(data.success); setTimeout(function () { location.reload(); }, 800); }
                if (data.info) toastr.info(data.info);
            },
            error: handleAjaxError,
        });
    });

    /* ── Submit All ───────────────────────────────────────────────────────── */
    $('#submitAllBtn').on('click', function (e) {
        e.preventDefault();
        $('#submitAllDateLabel').text(activeDate);
        $('#submitAllModal').modal('show');
    });

    $('#submitAllConfirmBtn').on('click', function () {
        $('#submitAllModal').modal('hide');
        var $headerBtn = $('#submitAllBtn').css('opacity', '.5').css('pointer-events', 'none');
        showProgress();
        $.ajax({
            type: 'POST',
            url:  routes.submitAll,
            data: { delivery_date: activeDate },
            complete: function () { hideProgress(); $headerBtn.css('opacity', '').css('pointer-events', ''); },
            success: function (data) {
                if (data.success) { toastr.success(data.success); setTimeout(function () { location.reload(); }, 800); }
                if (data.info) toastr.info(data.info);
            },
            error: handleAjaxError,
        });
    });

    /* ── Cancel (single product) ─────────────────────────────────────────── */
    $('#cancelBtn').on('click', function () {
        if (!activeProductId) { toastr.warning('Please select a product first.'); return; }
        var p = productMap[activeProductId];
        $('#cancelConfirmProduct').text(p ? p.name : '');
        $('#cancelConfirmDate').text(activeDate);
        $('#cancelConfirmModal').modal('show');
    });

    $('#cancelConfirmBtn').on('click', function () {
        $('#cancelConfirmModal').modal('hide');
        var $btn = $('#cancelBtn').prop('disabled', true);
        showProgress();
        $.ajax({
            type: 'POST',
            url:  routes.cancel,
            data: { base_product_id: activeProductId, delivery_date: activeDate },
            complete: function () { hideProgress(); $btn.prop('disabled', false); },
            success: function (data) {
                if (data.success) {
                    toastr.success(data.success);
                    $('.bc-input').val('');
                    $('.branch-card').removeClass('has-value');
                    $('.bc-input-row').removeClass('state-saving state-saved state-error');
                    $('.bc-saved-check').removeClass('show'); // clear all check icons
                    recalcDistribution();
                }
                if (data.info) toastr.info(data.info);
            },
            error: handleAjaxError,
        });
    });

    /* ── Info modal ───────────────────────────────────────────────────────── */
    $('#infoBtn').on('click', function (e) {
        e.preventDefault();
        $('#infoModal').modal('show');
    });

    /* ── Date modal ──────────────────────────────────────────────────────── */
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

    $('#dateModalBtn, #dateChip').on('click', function (e) {
        e.preventDefault();
        setDateMode(currentDateMode);
        $('#dateModal').modal('show');
    });

    $('#customDateInput').on('input', function () {
        $('#dateFormValue').val($(this).val());
        previewCustomDate($(this).val());
    });

    /* ── Flash messages ───────────────────────────────────────────────────── */
    @if(Session::has('message'))
        toastr['{{ Session::get("alert-type","info") }}']('{{ Session::get("message") }}');
    @endif
});
</script>
@endsection