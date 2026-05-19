@extends('sales.retail.dashboard')
@section('content')

@php
    use Carbon\Carbon;

    $today       = Carbon::today()->toDateString();
    $displayDate = Carbon::createFromFormat('Y-m-d', $today)->format('d M Y');
    $dateString  = preg_replace('/-/', '', $today);

    $branchId   = Auth::user()->branch;
    $userId     = Auth::id();
    $branchName = DB::connection('tenant')->table('branches')->where('id', $branchId)->value('name');

    /* Closure instead of a global function — avoids "Cannot redeclare" errors
       if this view is ever rendered more than once in the same request. */
    $makeTransSuffix = function (int $n = 6): string {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $rand  = '';
        for ($i = 0; $i < $n; $i++) $rand .= $chars[rand(0, strlen($chars) - 1)];
        return $rand;
    };
    $transId = $dateString . $makeTransSuffix();

    /* All active branch products — loaded once, searched offline via DataTable.
       branch_product_id (rbp.id) is what retail_system_sales.branch_product_id
       points to; product/unit/price are captured as a snapshot at sale time. */
    $products = DB::connection('tenant')
        ->table('retail_branch_products as rbp')
        ->join('retail_base_products as bp', 'bp.id', '=', 'rbp.base_product_id')
        ->where('rbp.branch_id', $branchId)
        ->where('rbp.is_active', 1)
        ->select(
            'rbp.id',
            'rbp.stock_quantity',
            'rbp.selling_price',
            'bp.name',
            'bp.code',
            'bp.unit',
            'bp.selling_price as bp_sell'
        )
        ->orderBy('bp.name')
        ->get()
        ->map(function ($p) {
            $p->effective_price = $p->selling_price ?? $p->bp_sell;
            return $p;
        });

    $paymentMethods = [
        ['id' => 'cash',   'label' => 'Cash',        'icon' => 'ri-money-dollar-box-line'],
        ['id' => 'airtel', 'label' => 'Airtel Money', 'icon' => 'ri-phone-line'],
        ['id' => 'mpamba', 'label' => 'Mpamba',       'icon' => 'ri-phone-line'],
        ['id' => 'bank',   'label' => 'Bank',         'icon' => 'ri-bank-line'],
    ];

    /* Last 20 sold items overall (not just today), read from retail_system_sales. */
    $recentSales = DB::connection('tenant')
        ->table('retail_system_sales')
        ->where('branch', $branchId)
        ->orderByDesc('id')
        ->limit(20)
        ->get();

    $customersToday = DB::connection('tenant')
        ->table('retail_system_sales')
        ->where('branch', $branchId)
        ->where('date', $today)
        ->distinct('transid')
        ->count('transid');

    /* ── Interval auto-progression ───────────────────────────────────────
       All 9 fixed slots, in chronological order, from retail_intervals.
       If no interval has been logged yet today for this branch, the user
       picks their starting slot from a dropdown (defaulting to the first
       slot). Once at least one entry exists today, the next slot is always
       the next one in order after the last logged interval — no picker. */
    $allIntervals = DB::connection('tenant')
        ->table('retail_intervals')
        ->orderBy('sort_order')
        ->get();

    /* retail_interval_sales.user_id -> users.id (per the actual migration
       schema — this table has no plain "user" column). */
    $todaysIntervalSales = DB::connection('tenant')
        ->table('retail_interval_sales as ris')
        ->join('retail_intervals as ri', 'ri.id', '=', 'ris.interval_id')
        ->leftJoin('users as u', 'u.id', '=', 'ris.user_id')
        ->where('ris.branch_id', $branchId)
        ->where('ris.date', $today)
        ->orderBy('ri.sort_order')
        ->select('ris.*', 'ri.slot', 'ri.sort_order', 'u.name as user_name')
        ->get();

    $isFirstIntervalToday = $todaysIntervalSales->isEmpty();

    $nextIntervalId  = null;
    $nextIntervalSlot = null;

    if (!$isFirstIntervalToday) {
        $lastSortOrder = $todaysIntervalSales->last()->sort_order;
        $next = $allIntervals->firstWhere('sort_order', $lastSortOrder + 1);
        if ($next) {
            $nextIntervalId   = $next->id;
            $nextIntervalSlot = $next->slot;
        }
    }

    $intervalTotal = $todaysIntervalSales->sum('sales');

    /* Today's recorded POS sales, broken down by payment method — shown
       under the interval list so totals can be reconciled at a glance. */
    $todaysPaymentSummary = DB::connection('tenant')
        ->table('retail_system_sales')
        ->where('branch', $branchId)
        ->where('date', $today)
        ->select('payment_method', DB::raw('SUM(quantity * price) as total'))
        ->groupBy('payment_method')
        ->pluck('total', 'payment_method');
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
/* ══════════════════════════════════════════════════════════════
   Old-POS design/colors, exactly — silver/gray panels, no card
   header, no gradients, no rounded cards — with the Availability
   view's blue (#4B5EBD) swapped in everywhere old POS used
   bg-primary / #007bff blue.
══════════════════════════════════════════════════════════════ */

/* ── Outer card chrome — matches the Branch Products template ───────── */
.card-header {
  padding: 0.5rem 1.5rem !important;
  background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff;
  border-radius: 10px 10px 0 0 !important;
}
.card-body  { padding: 0 !important; display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0; overflow: hidden; }
.card       { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; overflow:hidden; display: flex; flex-direction: column; }
.card-header h4 { color:#fff; font-weight:600; margin-bottom:0; display:flex; align-items:center; }
.card-header h4 i { margin-right: 0.25rem; }
.card-header .btn-light {
  height:28px; padding:0 10px; position: relative;
  display:flex; align-items:center; justify-content:center; line-height:1;
}
.card-header .btn-light:hover { background-color:#f8f9fa; transition:background-color 0.2s; }

#pos-loader {
    height: 4px;
    flex: 0 0 auto;
    background: linear-gradient(90deg, #4B5EBD, #4B5EBD 50%, transparent 50%);
    background-size: 40px 100%;
    animation: posload 1s linear infinite;
    display: none;
}
@keyframes posload { 0%{background-position:0 0} 100%{background-position:40px 0} }

.pos-badge {
    background: #dc2626; color: #fff;
    font-size: 10px; font-weight: 700;
    min-width: 16px; height: 16px; border-radius: 8px;
    display: none; align-items: center; justify-content: center;
    padding: 0 4px;
    position: absolute; top: -2px; right: -6px;
    border: 1px solid #fff;
}
.pos-badge.show { display: inline-flex; }

/* ── Workspace row — left (search/products) + right (cart) stretch to
       fill all available vertical height together. ──────────────────── */
#pos-workspace-row { flex: 1 1 auto; min-height: 0; }

#pos-left-col {
    background-color: #fff;
    padding: 0;
    display: flex;
    flex-direction: column;
}
#pos-search-row {
    background-color: #4B5EBD;
    padding: 8px;
    flex: 0 0 auto;
}
#pos-search-wrap { position: relative; }
#pos-search-wrap i {
    position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
    color: #4d4d4d; font-size: 16px; pointer-events: none;
}
#pos-search {
    background-color: silver;
    text-transform: uppercase;
    font-weight: bold;
    border: 1px solid silver;
    width: 100%;
    height: 34px;
    border-radius: 4px;
    padding: 0 10px 0 32px;
    outline: none;
}
#pos-search::placeholder { color: #4d4d4d; font-weight: bold; text-transform: none; }

/* No background here — silver only shows up once product rows render,
   since each .prd-row carries its own silver background. */
#pos-product-display { flex: 1 1 auto; overflow-y: auto; }

.prd-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 6px 8px; border-bottom: 1px solid #a6a6a6; border-top: 1px solid #a6a6a6;
    color: black; background-color: #cccccc;
}
.prd-row .prd-link { color: black; text-decoration: none; cursor: pointer; flex: 1; min-width: 0; }
.prd-row.prd-oos .prd-link { opacity: .5; cursor: not-allowed; }
.prd-name { text-transform: uppercase; font-weight: bold; font-size: 14px; font-family: inherit; }
.prd-code { color: #8a8a8a; font-weight: 600; font-size: 13px; font-family: monospace; margin-left: 4px; }
.prd-code .val { color: #c0392b; }
.prd-meta { color: gray; font-family: monospace; font-size: 13px; margin-left: 6px; }
.prd-stock-tag { color: #8a8a8a; font-weight: 600; font-size: 16px; font-family: monospace; margin-left: 6px; }
.prd-stock-tag .val { color: #c0392b; }

.prd-qty-input {
    text-align: center; width: 84px; border-radius: 5px; border: 1px ridge #b3b3b3;
    background: transparent; font-size: 15px; font-weight: bold; color: #1a1a1a; flex-shrink: 0;
    height: 36px; margin-left: 8px;
}
.prd-qty-input:focus { outline: 1px solid #4B5EBD; background: transparent; }

#pos-right-col {
    padding: 0;
    border-left: 1px solid #adadad;
    display: flex;
    flex-direction: column;
}
#pos-cart-bar {
    background-color: #4B5EBD;
    padding: 8px 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex: 0 0 auto;
}
#pos-cart-bar .pos-cart-label {
    border: 2px solid silver; font-weight: bold; color: silver; background: transparent;
    padding: 4px 10px; font-size: 14px; display: inline-flex; align-items: center; gap: 6px;
}
#pos-cart-bar .pos-cart-label i { color: silver; }
#pos-cart-bar .pos-cart-label #cartTotalPill { color: #f2f2f2; font-weight: bold; font-size: 17px; }
#pos-checkout-btn {
    border: 2px solid silver; background: transparent; color: silver; font-weight: bold;
    width: 70px; height: 32px; display: flex; align-items: center; justify-content: center; font-size: 16px;
    cursor: pointer; border-radius: 3px;
}
#pos-checkout-btn:disabled { opacity: .45; cursor: not-allowed; }

/* Cart fills the full remaining vertical height of the right column. */
#pos-cart-table-wrap { background-color: silver; padding: 0; flex: 1 1 auto; overflow-y: auto; }
#pos-cart-table { width: 100%; font-size: 11px; border-collapse: collapse; }
#pos-cart-table thead th {
    color: #3d5c5c; border-bottom: 2px solid #a6a6a6; border-top: 1px solid #a6a6a6;
    padding: 6px 4px; text-align: center; position: sticky; top: 0; background-color: silver;
}
#pos-cart-table thead th:first-child { text-align: left; padding-left: 6px; }
#pos-cart-table tbody td { border-bottom: 1px solid #b3b3b3; padding: 6px 4px; text-align: center; color: black; }
#pos-cart-table tbody td:first-child { text-align: left; padding-left: 6px; }

.pcr-name { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 130px; }
.pcr-qinput {
    width: 36px; height: 22px; text-align: center; border: none; background: transparent;
    font-size: 12px; font-weight: bold; color: #1d4ed8; outline: none;
}
.pcr-qinput:focus { outline: 1px solid #4B5EBD; background: #fff; }
.pcr-remove { color: red; cursor: pointer; font-weight: bold; text-decoration: none; }

#pos-cart-empty { text-align: center; padding: 40px 16px; color: #595959; font-size: 13px; background-color: silver; height: 100%; }

@media (max-width: 900px) {
    .card { height: calc(100vh - 110px) !important; }
    #pos-search-row { padding: 8px 8px 0 8px; }
    #pos-workspace-row { row-gap: 0 !important; }
    #pos-left-col, #pos-right-col { margin: 0 !important; flex: 0 0 auto; }
    #pos-product-display:empty { display: none; }
}

/* ── Modals: bordered/flat instead of rounded-gradient ───────────────── */
.mh-pos {
    background-color: #4B5EBD; padding: 10px 16px !important; border-bottom: none;
}
.mh-pos-title { color: #fff; font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
.mh-close-w { filter: brightness(0) invert(1); opacity: .8; }
.mh-close-w:hover { opacity: 1; }

.checkout-summary { background: #e6e6e6; border: 1px solid #a6a6a6; padding: 10px 14px; margin-bottom: 14px; font-size: 12px; }
.checkout-summary-row { display: flex; justify-content: space-between; padding: 3px 0; border-bottom: 1px solid #cccccc; color: #333; }
.checkout-summary-row:last-child { border-bottom: none; font-weight: 700; font-size: 14px; color: #1e293b; }

.pm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px; }
.pm-card {
    border: 1.5px solid #a6a6a6; padding: 10px 12px;
    cursor: pointer; display: flex; align-items: center; gap: 8px; user-select: none; background: #f2f2f2;
}
.pm-card:hover { border-color: #4B5EBD; }
.pm-card.active { border-color: #4B5EBD; background: #eceefb; }
.pm-card i { font-size: 20px; color: #4B5EBD; }
.pm-card .pm-label { font-size: 12px; font-weight: 600; color: #1e293b; }

.checkout-amount-wrap { margin-bottom: 12px; }
.checkout-amount-wrap label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6c757d; display: block; margin-bottom: 5px; }
.checkout-amount-hint { font-size: 11px; color: #8a8a8a; margin-top: 4px; }
.checkout-amount-input {
    width: 100%; height: 44px; border: 1px solid #a6a6a6; border-radius: 6px; font-size: 18px; font-weight: 700;
    padding: 0 14px; color: #1e293b; outline: none; background: transparent;
}
.checkout-amount-input:focus { border-color: #4B5EBD; }

#checkout-change-row {
    background: #e6f5ea; border: 1px solid #a6a6a6; padding: 8px 12px;
    display: none; justify-content: space-between; align-items: center; margin-bottom: 14px;
}
#checkout-change-row.show { display: flex; }
#checkout-change-row.negative { background: #fbe6e6; }
#checkout-change-label { font-size: 12px; font-weight: 600; color: #065f46; }
#checkout-change-row.negative #checkout-change-label { color: #7f1d1d; }
#checkout-change-value { font-size: 16px; font-weight: 800; color: #16a34a; }
#checkout-change-row.negative #checkout-change-value { color: #dc2626; }

#confirmSaleBtn {
    width: 100%; height: 44px; border: 2px solid #4B5EBD; border-radius: 6px;
    background: #4B5EBD; color: #fff; font-size: 14px; font-weight: 700;
    cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
}
#confirmSaleBtn:disabled { opacity: .5; cursor: not-allowed; }

#pos-product-display::-webkit-scrollbar, #pos-cart-table-wrap::-webkit-scrollbar { width: 5px; }
#pos-product-display::-webkit-scrollbar-thumb, #pos-cart-table-wrap::-webkit-scrollbar-thumb { background: #999; }

/* ── No spinner arrows on any number input, anywhere on this page ───── */
input[type=number]::-webkit-outer-spin-button,
input[type=number]::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}
input[type=number] {
    -moz-appearance: textfield;
}

/* ── Payment summary (under View Interval Sales) ─────────────────────── */
.pay-summary-title {
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
    color: #6c757d; margin: 16px 0 8px;
}
.pay-summary-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 8px 10px; border: 1px solid #e3e3e3; border-radius: 6px; margin-bottom: 6px; background: #fafafa;
}
.pay-summary-row .psr-label { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #1e293b; }
.pay-summary-row .psr-label i { color: #4B5EBD; font-size: 16px; }
.pay-summary-row .psr-value { font-size: 13px; font-weight: 700; color: #1e293b; }

/* ── View Interval modal ──────────────────────────────────────────────── */
.mh-icon-btn { color: #fff; opacity: .85; font-size: 20px; cursor: pointer; display: inline-flex; }
.mh-icon-btn:hover { opacity: 1; }
.mh-iv-title { display: flex; flex-direction: column; line-height: 1.35; }
.mh-iv-branch { color: #fff; font-size: 15px; font-weight: 700; }
.mh-iv-date { color: #fff; font-size: 13px; font-weight: 500; opacity: .85; }
#viewIntervalModal .modal-header.mh-pos { display: flex; align-items: center; justify-content: space-between; }
#viewIntervalModal .modal-header.mh-pos .d-flex { margin-left: auto; gap: 16px !important; }
.iv-slot-icon {
    display: inline-flex; align-items: center; gap: 3px;
    background: #eceefb; color: #4B5EBD; font-weight: 700; font-size: 11px;
    border-radius: 10px; padding: 2px 7px;
}
.iv-total-row {
    display: flex; justify-content: space-between; align-items: center;
    background: #eceefb; border: 1px solid #d7dcf6; border-radius: 6px;
    padding: 8px 12px; margin-top: 10px; font-weight: 700; font-size: 13px; color: #1e293b;
}
.iv-tabs .nav-link {
    color: #6c757d; border: none; border-bottom: 2px solid transparent;
    font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
    padding: 8px 4px; margin-right: 16px; background: transparent;
}
.iv-tabs .nav-link.active { color: #4B5EBD; border-bottom-color: #4B5EBD; background: transparent; }
.iv-tabs .nav-link:hover { color: #4B5EBD; }

/* ── Calculator ───────────────────────────────────────────────────────── */
.calc-screen { background: #1e2233; padding: 16px 18px 14px; }
#calcExpression { color: #8a93b8; font-size: 13px; min-height: 18px; text-align: right; font-family: monospace; }
#calcDisplay { color: #fff; font-size: 42px; font-weight: 700; text-align: right; font-family: monospace; word-break: break-all; line-height: 1.1; margin-top: 4px; }
.calc-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: #2b3050; }
.calc-btn {
    border: none; background: #262b45; color: #fff; font-size: 21px; font-weight: 600;
    padding: 21px 0; cursor: pointer; transition: background .15s;
}
.calc-btn:hover { background: #323955; }
.calc-btn:active { background: #3d4566; }
.calc-fn { background: #3a4060; color: #c9cfe8; font-size: 16px; }
.calc-fn:hover { background: #454c70; }
.calc-op { background: #4B5EBD; color: #fff; }
.calc-op:hover { background: #576CC0; }
.calc-zero { grid-column: span 2; }
.calc-eq { background: #22c55e; color: #fff; }
.calc-eq:hover { background: #16a34a; }

/* ── Pending sales table ─────────────────────────────────────────────── */
#pendingListWrap { max-height: 60vh; overflow: auto; }
#pendingTable { min-width: 540px; }
#pendingTable thead th { position: sticky; top: 0; background: silver; z-index: 1; }
</style>

{{-- ══════════════════════════════════════════════════════════════════════
     OUTER SHELL — matches the Branch Products template exactly:
     progress bar -> content-page > content > container-fluid -> card
══════════════════════════════════════════════════════════════════════ --}}
<div class="progress" id="progressBar" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="height:8px;display:none;margin-bottom:10px;">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card" style="height: calc(100vh - 140px);">

    {{-- ── Card header — date (click = refresh) + every icon-only action
         link, all grouped together on the right ─────────────────────── --}}
    <div class="card-header d-flex justify-content-between align-items-center">
        <a href="{{ url()->current() }}" class="header-title mb-0" style="gap:8px;color:#fff;text-decoration:none;" title="Refresh">
            <i class="ri-refresh-line"></i> {{ $displayDate }}
        </a>
        <div class="d-flex align-items-center" style="gap:4px;">
            <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="posUploadBtn" title="Pending sales — view &amp; upload">
                <i class="ri-cloud-line"></i>
                <span class="pos-badge" id="posPendingBadge"></span>
            </a>
            <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="posCalcBtn" title="Calculator"><i class="ri-calculator-line"></i></a>
            <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="posRecentBtn" title="Recently sold items"><i class="ri-list-check"></i></a>
            <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="posViewIntervalBtn" title="View interval sales"><i class="ri-eye-line"></i></a>
            <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="posIntervalBtn" title="Add interval sales"><i class="ri-add-circle-line"></i></a>
        </div>
    </div>

    {{-- ── Card body — POS workspace ──────────────────────────────────── --}}
    <div class="card-body">

        <div id="pos-loader"></div>

        <div class="row g-0" id="pos-workspace-row">

            {{-- LEFT COLUMN — search + product list (hidden until searched) --}}
            <div class="col-md-5" id="pos-left-col">
                <div id="pos-search-row">
                    <div id="pos-search-wrap">
                        <i class="ri-smartphone-line"></i>
                        <input type="text" id="pos-search" placeholder="Search product name or code" autocomplete="off" onclick="clearPosSearch()">
                    </div>
                </div>

                <div id="pos-product-display">
                    {{-- product rows injected by JS — no placeholder shown when empty --}}
                </div>

                {{-- Hidden payload — entire branch product list, searched client-side only --}}
                <script type="application/json" id="pos-products-json">{!! json_encode($products->map(function($p){ return ['id'=>$p->id,'name'=>$p->name,'code'=>$p->code,'unit'=>$p->unit,'price'=>$p->effective_price,'stock'=>(float)$p->stock_quantity]; })) !!}</script>
            </div>

            {{-- RIGHT COLUMN — cart --}}
            <div class="col-md-7" id="pos-right-col">
                <div id="pos-cart-bar">
                    <div class="pos-cart-label">
                        <i class="ri-shopping-cart-2-line"></i> MWK<span id="cartTotalPill">0</span>
                    </div>
                    <button id="pos-checkout-btn" disabled onclick="openCheckout()"><i class="ri-arrow-right-s-line"></i></button>
                </div>

                <div id="pos-cart-table-wrap">
                    <div id="pos-cart-empty"></div>
                    <table id="pos-cart-table" style="display:none;">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Unit</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Total</th>
                                <th>Actn</th>
                            </tr>
                        </thead>
                        <tbody id="pos-cart-tbody"></tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

</div></div></div>

{{-- ══════════════════════════════════════════════════════════
     CHECKOUT MODAL
══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="checkoutModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title"><i class="ri-shopping-bag-3-line"></i> Confirm Sale</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:16px 18px;">
                <div class="checkout-summary" id="checkoutSummaryWrap"></div>

                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6c757d;margin-bottom:8px;">
                    Payment Method
                </div>
                <div class="pm-grid">
                    @foreach($paymentMethods as $pm)
                    <div class="pm-card {{ $loop->first ? 'active' : '' }}"
                         data-pm="{{ $pm['id'] }}"
                         onclick="selectPaymentMethod('{{ $pm['id'] }}', this)">
                        <i class="{{ $pm['icon'] }}"></i>
                        <span class="pm-label">{{ $pm['label'] }}</span>
                    </div>
                    @endforeach
                </div>

                <div class="checkout-amount-wrap" id="amountPaidWrap">
                    <label>Amount Tendered (MWK)</label>
                    <input type="number" class="checkout-amount-input" id="amountPaidInput"
                           placeholder="Amount tendered" min="0" oninput="calcChange()">
                    <div class="checkout-amount-hint">Leave blank to charge the exact total.</div>
                </div>

                <div id="checkout-change-row">
                    <span id="checkout-change-label">Change</span>
                    <span id="checkout-change-value">MWK 0</span>
                </div>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" id="confirmSaleBtn" onclick="confirmSale()">
                    <i class="ri-check-double-line"></i> Record Sale
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     PENDING DATA MODAL — flat table (Product, Unit, Price, Qty, Total),
     no per-transaction grouping; scrolls both x and y once full.
══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="pendingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title">
                    <i class="ri-archive-line"></i> Pending Sales
                    <span style="font-size:12px;font-weight:400;opacity:.75;">— MWK <span id="pendingTotalLabel">0</span></span>
                </h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:0;">
                <div id="pendingListWrap">
                    <table class="table table-sm mb-0" id="pendingTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Unit</th>
                                <th class="text-center">Price</th>
                                <th class="text-center">Qty</th>
                                <th class="text-center">Total</th>
                            </tr>
                        </thead>
                        <tbody id="pendingTbody">
                            <tr><td colspan="5" style="text-align:center;color:#595959;padding:30px;font-size:13px;">No pending sales.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer" style="padding:10px 16px;">
                <button class="btn btn-primary btn-sm" onclick="posUpload()" id="pendingUploadBtn">
                    <i class="ri-cloud-line me-1"></i> Upload All
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     ADD INTERVAL SALE MODAL
     A single select of all slots, defaulting to whichever slot comes
     next in chronological order — the user can override it, but if
     left alone it just keeps walking forward through the sequence.
══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="intervalModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title"><i class="ri-add-circle-line"></i> Interval Sales</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:16px 18px;">

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12px;">Time Slot</label>
                    <select class="form-select form-select-sm" id="intervalSlotSelect">
                        @foreach($allIntervals as $iv)
                        <option value="{{ $iv->id }}" data-slot="{{ $iv->slot }}"
                            {{ ($isFirstIntervalToday ? $loop->first : $iv->id == $nextIntervalId) ? 'selected' : '' }}>
                            {{ $iv->slot }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:12px;">Sales (MWK)</label>
                    <input type="number" class="form-control form-control-sm" id="intervalSalesInput"
                           placeholder="Enter sales amount" min="0" autocomplete="off">
                </div>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button class="btn btn-primary btn-sm" id="intervalSubmitBtn" onclick="submitIntervalSale()">
                    <i class="ri-check-line me-1"></i> Submit
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     VIEW INTERVAL SALES MODAL — header is branch + date so a screenshot
     still makes sense later. One icon toggles, in place, between the
     interval table and the payment-method breakdown (no tabs).
══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="viewIntervalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <div class="mh-iv-title">
                    <span class="mh-iv-branch">{{ $branchName }}</span>
                    <span class="mh-iv-date">{{ $displayDate }}</span>
                </div>
                <div class="d-flex align-items-center" style="gap:14px;">
                    <a href="#" id="ivToggleBtn" class="mh-icon-btn" title="View payment breakdown"
                       onclick="event.preventDefault();toggleIntervalView();">
                        <i class="ri-bank-card-line" id="ivToggleIcon"></i>
                    </a>
                    <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body" style="padding:16px 18px;">

                {{-- PANE 1: Intervals — Interval (icon), User, Amount, with the
                     grand total as the table's own bottom row (matches the
                     branch reference layout) --}}
                <div id="iv-intervals-pane">
                    <div style="max-height:42vh;overflow-y:auto;">
                        <table class="table table-sm mb-0" style="font-size:12px;">
                            <thead style="position:sticky;top:0;background:#fff;">
                                <tr>
                                    <th style="border-bottom:2px solid #737373;border-top:2px solid #737373;">Interval</th>
                                    <th style="border-bottom:2px solid #737373;border-top:2px solid #737373;">User</th>
                                    <th class="text-end" style="border-bottom:2px solid #737373;border-top:2px solid #737373;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($todaysIntervalSales as $is)
                                <tr>
                                    <td><span class="iv-slot-icon" title="{{ $is->slot }}"><i class="ri-time-line"></i>{{ $is->sort_order }}</span></td>
                                    <td>{{ $is->user_name ?? '—' }}</td>
                                    <td class="text-end">{{ number_format($is->sales, 0) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted">No intervals logged yet today.</td></tr>
                                @endforelse
                                @if($todaysIntervalSales->isNotEmpty())
                                <tr>
                                    <td style="border-bottom:2px solid #737373;border-top:2px solid #737373;"></td>
                                    <td style="border-bottom:2px solid #737373;border-top:2px solid #737373;font-weight:700;">Total</td>
                                    <td class="text-end" style="border-bottom:2px solid #737373;border-top:2px solid #737373;font-weight:700;">MWK {{ number_format($intervalTotal, 0) }}</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- PANE 2: Payment-method breakdown — hidden until toggled --}}
                <div id="iv-payments-pane" style="display:none;">
                    @foreach($paymentMethods as $pm)
                    <div class="pay-summary-row">
                        <span class="psr-label"><i class="{{ $pm['icon'] }}"></i>{{ $pm['label'] }}</span>
                        <span class="psr-value">MWK {{ number_format($todaysPaymentSummary[$pm['id']] ?? 0, 0) }}</span>
                    </div>
                    @endforeach
                    <div class="iv-total-row">
                        <span>Total</span>
                        <span>MWK {{ number_format($todaysPaymentSummary->sum(), 0) }}</span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     RECENTLY SOLD ITEMS MODAL — last 20 items, any date
══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="recentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title"><i class="ri-list-check"></i> Recently Sold Items</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div style="max-height:60vh;overflow:auto;">
                    <table class="table table-sm mb-0" style="font-size:12px;min-width:620px;">
                        <thead style="position:sticky;top:0;background:silver;">
                            <tr>
                                <th>Date</th>
                                <th>Product</th>
                                <th class="text-center">Unit</th>
                                <th class="text-center">Price</th>
                                <th class="text-center">Qty</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSales as $rs)
                            <tr>
                                <td>{{ $rs->date }}</td>
                                <td>{{ $rs->product }}</td>
                                <td class="text-center">{{ $rs->unit }}</td>
                                <td class="text-center">{{ number_format($rs->price, 0) }}</td>
                                <td class="text-center">{{ number_format($rs->quantity, 2) }}</td>
                                <td class="text-center">{{ number_format($rs->quantity * $rs->price, 0) }}</td>
                                <td class="text-center">{{ $rs->payment_method ?? 'Cash' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No sales recorded yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════
     CALCULATOR MODAL
══════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="calculatorModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:360px;">
        <div class="modal-content" style="border:1px solid #a6a6a6;border-radius:14px;overflow:hidden;">
            <div class="modal-header mh-pos" style="border-radius:0;">
                <h5 class="modal-title mh-pos-title"><i class="ri-calculator-line"></i> Calculator</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="calc-screen">
                    <div id="calcExpression"></div>
                    <div id="calcDisplay">0</div>
                </div>
                <div class="calc-grid">
                    <button class="calc-btn calc-fn" onclick="calcClear()">C</button>
                    <button class="calc-btn calc-fn" onclick="calcBackspace()">⌫</button>
                    <button class="calc-btn calc-fn" onclick="calcPercent()">%</button>
                    <button class="calc-btn calc-op" onclick="calcOp('/')">÷</button>

                    <button class="calc-btn" onclick="calcDigit('7')">7</button>
                    <button class="calc-btn" onclick="calcDigit('8')">8</button>
                    <button class="calc-btn" onclick="calcDigit('9')">9</button>
                    <button class="calc-btn calc-op" onclick="calcOp('*')">×</button>

                    <button class="calc-btn" onclick="calcDigit('4')">4</button>
                    <button class="calc-btn" onclick="calcDigit('5')">5</button>
                    <button class="calc-btn" onclick="calcDigit('6')">6</button>
                    <button class="calc-btn calc-op" onclick="calcOp('-')">−</button>

                    <button class="calc-btn" onclick="calcDigit('1')">1</button>
                    <button class="calc-btn" onclick="calcDigit('2')">2</button>
                    <button class="calc-btn" onclick="calcDigit('3')">3</button>
                    <button class="calc-btn calc-op" onclick="calcOp('+')">+</button>

                    <button class="calc-btn calc-zero" onclick="calcDigit('0')">0</button>
                    <button class="calc-btn" onclick="calcDigit('.')">.</button>
                    <button class="calc-btn calc-eq" onclick="calcEquals()">=</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Hidden meta --}}
<input type="hidden" id="posTransId"  value="{{ $transId }}">
<input type="hidden" id="posUserId"   value="{{ $userId }}">
<input type="hidden" id="posUserName" value="{{ Auth::user()->name ?? Auth::id() }}">
<input type="hidden" id="posBranchId" value="{{ $branchId }}">
<input type="hidden" id="posBranchName" value="{{ $branchName }}">
<input type="hidden" id="posDate"     value="{{ $today }}">

@endsection

@section('scripts')
<script>
'use strict';

/* ══════════════════════════════════════════════════════════════
   CONSTANTS
══════════════════════════════════════════════════════════════ */
const POS_CART_KEY  = 'netacube_pos_cart';
const POS_CLOUD_KEY = 'netacube_pos_cloud';

let cart       = [];
let cloudData  = [];
let activePaymentMethod = 'cash';
let allProducts = [];

function escHtml(str) {
    return String(str == null ? '' : str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function fmtNum(n) {
    if (n === null || n === undefined || n === '') return '0';
    return parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}

/* Quantities can be fractional (e.g. kg) — keep up to 2 decimals, with
   thousand separators on the integer part. */
function fmtQty(n) {
    if (n === null || n === undefined || n === '') return '0';
    return parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

/* Clears the search box (and any rendered results) when the user taps
   into it, so they always start a fresh search without deleting text. */
function clearPosSearch() {
    const srch = document.getElementById('pos-search');
    if (srch.value) {
        srch.value = '';
        document.getElementById('pos-product-display').innerHTML = '';
    }
}

$(document).ready(function () {

    try {
        allProducts = JSON.parse(document.getElementById('pos-products-json').textContent || '[]');
    } catch (e) {
        allProducts = [];
    }

    const display = document.getElementById('pos-product-display');

    /* ── Old-POS behavior: typing 2+ chars reveals matching rows directly,
       each with its own inline qty box — no suggestion dropdown, no
       separate qty-picker step. Rows carry their own silver background;
       nothing is shown until there's a query. ──────────────────────────── */
    function filterProducts(q) {
        q = q.toLowerCase();
        return allProducts.filter(function (p) {
            return (p.name || '').toLowerCase().indexOf(q) !== -1
                || (p.code || '').toLowerCase().indexOf(q) !== -1;
        });
    }

    function renderRows(products) {
        if (!products.length) {
            display.innerHTML = '<div style="text-align:center;padding:24px 12px;color:#595959;font-size:13px;background:#cccccc;">No products matched your search.</div>';
            return;
        }

        let html = '';
        products.forEach(function (p) {
            const oos = p.stock <= 0;
            html += `
            <div class="prd-row${oos ? ' prd-oos' : ''}" data-id="${p.id}">
                <a href="#" class="prd-link" onclick="event.preventDefault();${oos ? '' : 'prdRowClick(' + p.id + ')'}">
                    <span class="prd-name">${escHtml(p.name)}</span>
                    <span class="prd-code">${p.code ? '(<span class="val">' + escHtml(p.code) + '</span>)' : ''}</span>
                    <span class="prd-meta">${fmtNum(p.price)}/${escHtml(p.unit)}</span>
                    <span class="prd-stock-tag">[<span class="val">${oos ? '0' : fmtNum(p.stock)}</span>]</span>
                </a>
                <input type="number" class="prd-qty-input" id="qinput_${p.id}"
                       min="0" max="${p.stock}" placeholder="Qty" autocomplete="off"
                       ${oos ? 'disabled' : ''}
                       onchange="prdQtyChange(${p.id})">
            </div>`;
        });
        display.innerHTML = html;
    }

    $('#pos-search').on('keyup', function () {
        const q = $(this).val().trim();
        if (q.length < 2) {
            display.innerHTML = '';
            return;
        }
        renderRows(filterProducts(q));
    });

    loadCart();
    loadCloud();
    renderCart();
    updatePendingBadge();
    document.getElementById('pos-search').focus();
});

/* Find a product's static data by id from the in-memory payload */
function findProduct(id) {
    return allProducts.find(p => p.id === id);
}

/* Clicking the product name/link adds 1 unit (mirrors old POS's .sale-data-1 click-to-add) */
function prdRowClick(id) {
    const p = findProduct(id);
    if (!p) return;
    if (p.stock <= 0) { toastr.warning(p.name + ' is out of stock.'); return; }

    addToCart({ id: p.id, name: p.name, unit: p.unit, price: p.price, stock: p.stock, qty: 1 });

    const qInput = document.getElementById('qinput_' + id);
    if (qInput) qInput.value = '';

    const srch = document.getElementById('pos-search');
    srch.value = '';
    document.getElementById('pos-product-display').innerHTML = '';
    srch.focus();
}

/* Typing a qty directly into a row's inline box and tabbing/blurring away
   adds that exact quantity (mirrors old POS's .sale-data change handler) */
function prdQtyChange(id) {
    const p = findProduct(id);
    if (!p) return;
    const input = document.getElementById('qinput_' + id);
    const qty = parseFloat(input.value);

    if (!qty || qty <= 0) { input.value = ''; return; }
    if (qty > p.stock) {
        toastr.error('Quantity for ' + p.name + ' must be greater than 0 and must be less than or equal to ' + p.stock);
        input.value = '';
        return;
    }

    addToCart({ id: p.id, name: p.name, unit: p.unit, price: p.price, stock: p.stock, qty: qty });
    input.value = '';

    const srch = document.getElementById('pos-search');
    srch.value = '';
    document.getElementById('pos-product-display').innerHTML = '';
    srch.focus();
}

/* ══════════════════════════════════════════════════════════════
   CART
   Each cart line carries everything retail_system_sales needs:
   branch_product_id, product/unit/price snapshot, and stock-before
   so qty_before/qty_sold/qty_after can be written at checkout time.
══════════════════════════════════════════════════════════════ */
function addToCart(item) {
    const existing = cart.find(c => c.id === item.id);
    if (existing) {
        const newQty = existing.qty + item.qty;
        if (newQty > item.stock) {
            toastr.warning(item.name + ' — only ' + item.stock + ' in stock (' + existing.qty + ' already in cart).');
            return;
        }
        existing.qty = newQty;
    } else {
        cart.push({ ...item });
    }
    saveCart();
    renderCart();
    toastr.success(item.name + ' added.', '', { timeOut: 1500 });
}

function removeFromCart(id) {
    cart = cart.filter(c => c.id !== id);
    saveCart(); renderCart();
}

function updateCartQtyInput(id, val) {
    const item = cart.find(c => c.id === id);
    if (!item) return;
    const newQty = parseFloat(val) || 1;
    if (newQty <= 0)         { removeFromCart(id); return; }
    if (newQty > item.stock) { toastr.warning('Only ' + item.stock + ' in stock.'); document.getElementById('cqty_' + id).value = item.qty; return; }
    item.qty = newQty;
    const tEl = document.getElementById('ctot_' + id);
    if (tEl) tEl.textContent = fmtNum(item.qty * item.price);
    saveCart();
    renderCartTotals();
}

function saveCart()  { localStorage.setItem(POS_CART_KEY,  JSON.stringify(cart)); }
function loadCart()  { try { cart  = JSON.parse(localStorage.getItem(POS_CART_KEY)  || '[]'); } catch(e) { cart  = []; } }
function saveCloud() { localStorage.setItem(POS_CLOUD_KEY, JSON.stringify(cloudData)); }
function loadCloud() { try { cloudData = JSON.parse(localStorage.getItem(POS_CLOUD_KEY) || '[]'); } catch(e) { cloudData = []; } }

function renderCart() {
    const table = document.getElementById('pos-cart-table');
    const tbody = document.getElementById('pos-cart-tbody');
    const empty = document.getElementById('pos-cart-empty');

    if (!cart.length) {
        tbody.innerHTML = '';
        table.style.display = 'none';
        empty.style.display = 'block';
        document.getElementById('pos-checkout-btn').disabled = true;
        renderCartTotals();
        return;
    }

    empty.style.display = 'none';
    table.style.display = 'table';

    let html = '';
    cart.forEach(function (item) {
        html += `
        <tr id="crow_${item.id}">
            <td class="pcr-name" title="${escHtml(item.name)}">${escHtml(item.name)}</td>
            <td>${escHtml(item.unit)}</td>
            <td>${fmtNum(item.price)}</td>
            <td>
                <input class="pcr-qinput" id="cqty_${item.id}" type="number"
                       value="${item.qty}" min="1" max="${item.stock}"
                       onchange="updateCartQtyInput(${item.id}, this.value)">
            </td>
            <td id="ctot_${item.id}">${fmtNum(item.qty * item.price)}</td>
            <td><a href="#" class="pcr-remove" onclick="event.preventDefault();removeFromCart(${item.id})">X</a></td>
        </tr>`;
    });
    tbody.innerHTML = html;
    document.getElementById('pos-checkout-btn').disabled = false;
    renderCartTotals();
}

function renderCartTotals() {
    const total = cartTotal();
    document.getElementById('cartTotalPill').textContent = fmtNum(total);
}

function cartTotal() {
    return cart.reduce(function (sum, c) { return sum + c.qty * c.price; }, 0);
}

/* ══════════════════════════════════════════════════════════════
   CHECKOUT
══════════════════════════════════════════════════════════════ */
function openCheckout() {
    if (!cart.length) return;
    let rows = '';
    cart.forEach(function (c) {
        rows += `<div class="checkout-summary-row">
            <span>${escHtml(c.name)} × ${c.qty} ${escHtml(c.unit)}</span>
            <span>MWK ${fmtNum(c.qty * c.price)}</span>
        </div>`;
    });
    rows += `<div class="checkout-summary-row">
        <span>Total</span><span>MWK ${fmtNum(cartTotal())}</span>
    </div>`;
    document.getElementById('checkoutSummaryWrap').innerHTML = rows;
    document.getElementById('amountPaidInput').value = '';
    document.getElementById('checkout-change-row').classList.remove('show', 'negative');
    document.querySelectorAll('.pm-card').forEach(c => c.classList.remove('active'));
    const cashCard = document.querySelector('.pm-card[data-pm="cash"]');
    if (cashCard) cashCard.classList.add('active');
    activePaymentMethod = 'cash';
    document.getElementById('amountPaidWrap').style.display = 'block';
    $('#checkoutModal').modal('show');
    setTimeout(function () { document.getElementById('amountPaidInput').focus(); }, 450);
}

function selectPaymentMethod(pm, el) {
    activePaymentMethod = pm;
    document.querySelectorAll('.pm-card').forEach(c => c.classList.remove('active'));
    if (el) el.classList.add('active');
    const wrap = document.getElementById('amountPaidWrap');
    wrap.style.display = pm === 'cash' ? 'block' : 'none';
    if (pm !== 'cash') document.getElementById('checkout-change-row').classList.remove('show');
}

function calcChange() {
    const total  = cartTotal();
    const paid   = parseFloat(document.getElementById('amountPaidInput').value) || 0;
    const change = paid - total;
    const row    = document.getElementById('checkout-change-row');
    if (paid <= 0) { row.classList.remove('show'); return; }
    row.classList.add('show');
    row.classList.toggle('negative', change < 0);
    document.getElementById('checkout-change-label').textContent = change < 0 ? 'Short by' : 'Change';
    document.getElementById('checkout-change-value').textContent = 'MWK ' + fmtNum(Math.abs(change));
}

/* Best-effort device label — not a reliable fingerprint, just a readable hint
   like "Chrome on Windows" for the audit columns. */
function getDeviceName() {
    const ua = navigator.userAgent;
    let os = 'Unknown OS';
    if (/Windows/i.test(ua))      os = 'Windows';
    else if (/Android/i.test(ua)) os = 'Android';
    else if (/iPhone|iPad/i.test(ua)) os = 'iOS';
    else if (/Mac/i.test(ua))     os = 'macOS';
    else if (/Linux/i.test(ua))   os = 'Linux';

    let browser = 'Browser';
    if (/Edg\//i.test(ua))        browser = 'Edge';
    else if (/Chrome\//i.test(ua)) browser = 'Chrome';
    else if (/Firefox\//i.test(ua)) browser = 'Firefox';
    else if (/Safari\//i.test(ua)) browser = 'Safari';

    return browser + ' on ' + os;
}

/* If the cashier leaves amount-tendered blank (or 0), we assume the
   customer paid the exact total — no need to force a redundant entry. */
function confirmSale() {
    if (!cart.length) return;
    const total = cartTotal();

    let paid;
    if (activePaymentMethod === 'cash') {
        const raw     = document.getElementById('amountPaidInput').value;
        const entered = parseFloat(raw);
        paid = (raw === '' || isNaN(entered) || entered <= 0) ? total : entered;

        if (paid < total) {
            toastr.warning('Amount tendered is less than the total.');
            return;
        }
    } else {
        paid = total;
    }

    const transId    = document.getElementById('posTransId').value;
    const date       = document.getElementById('posDate').value;
    const userId     = document.getElementById('posUserId').value;
    const userName   = document.getElementById('posUserName').value;
    const branchId   = document.getElementById('posBranchId').value;
    const branchName = document.getElementById('posBranchName').value;
    const time       = new Date().toTimeString().slice(0, 8);
    const deviceName = getDeviceName();
    const userAgent  = navigator.userAgent;

    cart.forEach(function (c) {
        const qtyBefore = c.stock;
        const qtySold   = c.qty;
        const qtyAfter  = qtyBefore - qtySold;

        cloudData.push({
            branch_product_id: c.id,
            product:           c.name,
            unit:              c.unit,
            price:             c.price,
            transid:           transId,
            date:              date,
            time:              time,
            user:              userName,
            branch:            branchName,
            quantity:          qtySold,
            rquantity:         0,
            qty_before:        qtyBefore,
            qty_sold:          qtySold,
            qty_after:         qtyAfter,
            payment_method:    activePaymentMethod,
            amount_paid:       paid,
            slot:              '0',
            device_name:       deviceName,
            user_agent:        userAgent,
        });
    });

    saveCloud();
    updatePendingBadge();

    cart = [];
    saveCart();
    renderCart();
    refreshTransId();

    $('#checkoutModal').modal('hide');
    toastr.success('Sale recorded. Upload when online.', 'Done');
    document.getElementById('pos-search').focus();
}

/* ══════════════════════════════════════════════════════════════
   UPLOAD
══════════════════════════════════════════════════════════════ */
function updatePendingBadge() {
    const badge = document.getElementById('posPendingBadge');
    if (cloudData.length > 0) {
        badge.textContent = cloudData.length;
        badge.classList.add('show');
    } else {
        badge.classList.remove('show');
    }
}

/* Flat table — every pending line item shown directly, no grouping by
   transaction id. The wrapper (#pendingListWrap) scrolls on both axes
   once content exceeds the max-height / min-width thresholds set in CSS. */
function renderPendingModal() {
    const tbody = document.getElementById('pendingTbody');
    const total = cloudData.reduce(function (s, c) { return s + (c.quantity * c.price); }, 0);
    document.getElementById('pendingTotalLabel').textContent = fmtNum(total);

    if (!cloudData.length) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#595959;padding:30px;font-size:13px;">No pending sales.</td></tr>';
        return;
    }

    let html = '';
    cloudData.forEach(function (e) {
        html += `<tr>
            <td>${escHtml(e.product)}</td>
            <td class="text-center">${escHtml(e.unit)}</td>
            <td class="text-center">${fmtNum(e.price)}</td>
            <td class="text-center">${fmtQty(e.quantity)}</td>
            <td class="text-center">${fmtNum(e.quantity * e.price)}</td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

async function posUpload() {
    if (!cloudData.length) { toastr.info('Nothing to upload.'); return; }
    posLoaderShow();
    document.getElementById('posUploadBtn').style.pointerEvents = 'none';
    document.getElementById('pendingUploadBtn').disabled = true;

    try {
        const res = await fetch('/upload-sales', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: new URLSearchParams({ data: JSON.stringify(cloudData) }),
        });
        const result = await res.json();
        if (Array.isArray(result) && result.length === 0) {
            cloudData = [];
            saveCloud(); updatePendingBadge();
            toastr.success('All sales uploaded.', 'Success');
            $('#pendingModal').modal('hide');
        } else if (Array.isArray(result)) {
            cloudData = result.flat();
            saveCloud(); updatePendingBadge();
            toastr.warning('Some items could not be uploaded and remain pending.', 'Partial');
        } else {
            toastr.error('Unexpected server response.', 'Error');
        }
    } catch (err) {
        toastr.error('Upload failed — check connection and try again.', 'Error');
    } finally {
        posLoaderHide();
        document.getElementById('posUploadBtn').style.pointerEvents = '';
        document.getElementById('pendingUploadBtn').disabled = false;
    }
}

/* ══════════════════════════════════════════════════════════════
   INTERVAL SALES
   Always reads from #intervalSlotSelect — it defaults to the next
   slot in chronological order, but the user can override it.
   Field name sent here is "user", mapped to user_id by the
   /insert-interval-sales controller — adjust there if it currently
   expects a different key name.
══════════════════════════════════════════════════════════════ */
function submitIntervalSale() {
    const sales = parseFloat(document.getElementById('intervalSalesInput').value);
    if (isNaN(sales) || sales < 0) { toastr.warning('Enter a valid sales amount.'); return; }
    if (cloudData.length > 0) { toastr.warning('Upload pending sales before entering interval sales.'); return; }

    const intervalId = document.getElementById('intervalSlotSelect').value;
    if (!intervalId) { toastr.warning('No interval slot available to log.'); return; }

    posLoaderShow();
    document.getElementById('intervalSubmitBtn').disabled = true;

    $.ajax({
        url:  '/insert-interval-sales',
        type: 'POST',
        data: {
            _token:      $('meta[name="csrf-token"]').attr('content'),
            user_id:     document.getElementById('posUserId').value,
            branch:      document.getElementById('posBranchId').value,
            date:        document.getElementById('posDate').value,
            interval_id: intervalId,
            sales:       sales,
        },
        success: function (res) {
            if (res == 2) {
                toastr.success('Interval sales recorded.');
                $('#intervalModal').modal('hide');
                // Reload so the next-slot logic recalculates server-side.
                setTimeout(function () { window.location.reload(); }, 600);
            } else if (res == 1) {
                toastr.error('This interval has already been entered.');
            } else {
                toastr.error('Sales must be 0 or greater.');
            }
        },
        error:    function () { toastr.error('Could not save. Check your connection.'); },
        complete: function () {
            posLoaderHide();
            document.getElementById('intervalSubmitBtn').disabled = false;
        }
    });
}

/* ══════════════════════════════════════════════════════════════
   CALCULATOR
   Builds a plain expression string and evaluates it with a
   whitelisted character set only (digits, + - * / . ( )) — no
   arbitrary code can reach Function() through this path.
   Display values (both the running expression and the current
   number) are rendered with thousand separators for readability.
══════════════════════════════════════════════════════════════ */
let calcExpr = '';

/* Adds thousand separators to the integer part of a plain numeric
   string while typing — preserves a trailing "." so the user can keep
   typing decimals without it being stripped. */
function calcFormatDisplay(str) {
    if (str === '' || str === '-' || str === undefined) return str || '';
    let neg = false;
    if (str.charAt(0) === '-') { neg = true; str = str.slice(1); }
    const parts = str.split('.');
    let intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    let result = (neg ? '-' : '') + (intPart === '' ? '0' : intPart);
    if (parts.length > 1) result += '.' + parts[1];
    return result;
}

/* Formats every number found inside the full expression (so the small
   running total above the main display also reads with commas). */
function calcFormatExpr(expr) {
    return expr.replace(/\d+\.?\d*/g, function (m) { return calcFormatDisplay(m); });
}

function calcRender() {
    document.getElementById('calcExpression').textContent = calcFormatExpr(calcExpr);
    const lastVal = calcExpr.split(/[+\-*/]/).pop();
    document.getElementById('calcDisplay').textContent = lastVal === '' ? '0' : calcFormatDisplay(lastVal);
}

function calcDigit(d) {
    if (d === '.') {
        const lastVal = calcExpr.split(/[+\-*/]/).pop();
        if (lastVal.includes('.')) return;
    }
    calcExpr += d;
    calcRender();
}

function calcOp(op) {
    if (!calcExpr) return;
    const lastChar = calcExpr.slice(-1);
    if ('+-*/'.includes(lastChar)) {
        calcExpr = calcExpr.slice(0, -1) + op;
    } else {
        calcExpr += op;
    }
    calcRender();
}

function calcClear() {
    calcExpr = '';
    calcRender();
}

function calcBackspace() {
    calcExpr = calcExpr.slice(0, -1);
    calcRender();
}

function calcPercent() {
    const parts = calcExpr.split(/([+\-*/])/);
    const last  = parts.pop();
    if (last) {
        parts.push((parseFloat(last) / 100).toString());
        calcExpr = parts.join('');
        calcRender();
    }
}

function calcEquals() {
    if (!calcExpr) return;
    try {
        const safe   = calcExpr.replace(/[^0-9+\-*/.()]/g, '');
        const result = Function('"use strict";return (' + safe + ')')();
        calcExpr = (Math.round(result * 100000) / 100000).toString();
        calcRender();
    } catch (e) {
        document.getElementById('calcDisplay').textContent = 'Error';
    }
}

/* ══════════════════════════════════════════════════════════════
   UTILITIES
══════════════════════════════════════════════════════════════ */
function posLoaderShow() { document.getElementById('pos-loader').style.display = 'block'; }
function posLoaderHide() { document.getElementById('pos-loader').style.display = 'none'; }

function refreshTransId() {
    const chars  = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const prefix = document.getElementById('posDate').value.replace(/-/g,'');
    let   rand   = '';
    for (let i = 0; i < 6; i++) rand += chars[Math.floor(Math.random() * chars.length)];
    document.getElementById('posTransId').value = prefix + rand;
}

/* ══════════════════════════════════════════════════════════════
   VIEW INTERVAL MODAL — single-pane icon toggle (no tabs)
══════════════════════════════════════════════════════════════ */
function toggleIntervalView() {
    const ivPane = document.getElementById('iv-intervals-pane');
    const pmPane = document.getElementById('iv-payments-pane');
    const icon   = document.getElementById('ivToggleIcon');
    const btn    = document.getElementById('ivToggleBtn');
    const showingIntervals = pmPane.style.display === 'none';

    if (showingIntervals) {
        ivPane.style.display = 'none';
        pmPane.style.display = '';
        icon.className = 'ri-time-line';
        btn.title = 'View intervals';
    } else {
        ivPane.style.display = '';
        pmPane.style.display = 'none';
        icon.className = 'ri-bank-card-line';
        btn.title = 'View payment breakdown';
    }
}

function resetIntervalView() {
    document.getElementById('iv-intervals-pane').style.display = '';
    document.getElementById('iv-payments-pane').style.display = 'none';
    document.getElementById('ivToggleIcon').className = 'ri-bank-card-line';
    document.getElementById('ivToggleBtn').title = 'View payment breakdown';
}

/* ══════════════════════════════════════════════════════════════
   BUTTON BINDINGS
══════════════════════════════════════════════════════════════ */
document.getElementById('posUploadBtn').addEventListener('click',  function (e) { e.preventDefault(); renderPendingModal(); $('#pendingModal').modal('show'); });
document.getElementById('posCalcBtn').addEventListener('click',    function (e) { e.preventDefault(); calcExpr = ''; calcRender(); $('#calculatorModal').modal('show'); });
document.getElementById('posRecentBtn').addEventListener('click',  function (e) { e.preventDefault(); $('#recentModal').modal('show'); });
document.getElementById('posViewIntervalBtn').addEventListener('click', function (e) { e.preventDefault(); resetIntervalView(); $('#viewIntervalModal').modal('show'); });
document.getElementById('posIntervalBtn').addEventListener('click',function (e) { e.preventDefault(); $('#intervalModal').modal('show'); setTimeout(function(){ document.getElementById('intervalSalesInput').focus(); }, 400); });
</script>
@endsection