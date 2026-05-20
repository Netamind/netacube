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

    $makeTransSuffix = function (int $n = 6): string {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $rand  = '';
        for ($i = 0; $i < $n; $i++) $rand .= $chars[rand(0, strlen($chars) - 1)];
        return $rand;
    };
    $transId = $dateString . $makeTransSuffix();

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
        ['id' => 'cash',   'label' => 'Cash',         'icon' => 'ri-money-dollar-box-line'],
        ['id' => 'airtel', 'label' => 'Airtel Money',  'icon' => 'ri-phone-line'],
        ['id' => 'mpamba', 'label' => 'Mpamba',        'icon' => 'ri-phone-line'],
        ['id' => 'bank',   'label' => 'Bank',          'icon' => 'ri-bank-line'],
    ];

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

    // ── All fixed slots in order ──────────────────────────────────────────
    $allIntervals = DB::connection('tenant')
        ->table('retail_intervals')
        ->orderBy('sort_order')
        ->get();

    // ── What has been entered today (ordered by slot sequence) ────────────
    $todaysIntervalSales = DB::connection('tenant')
        ->table('retail_interval_sales as ris')
        ->join('retail_intervals as ri', 'ri.id', '=', 'ris.interval_id')
        ->leftJoin('users as u', 'u.id', '=', 'ris.user_id')
        ->where('ris.branch_id', $branchId)
        ->where('ris.date', $today)
        ->orderBy('ri.sort_order')
        ->select('ris.*', 'ri.slot', 'ri.sort_order', 'u.name as user_name')
        ->get();

    // ── Remaining (not yet entered) intervals ─────────────────────────────
    $enteredIntervalIds = $todaysIntervalSales->pluck('interval_id')->toArray();
    $remainingIntervals = $allIntervals->whereNotIn('id', $enteredIntervalIds)->values();

    $isFirstIntervalToday = $todaysIntervalSales->isEmpty();
    $nextInterval         = $remainingIntervals->first();

    $nextIntervalId   = $nextInterval?->id;
    $nextIntervalSlot = $nextInterval?->slot;

    $intervalTotal = $todaysIntervalSales->sum('sales');

    $todaysPaymentSummary = DB::connection('tenant')
        ->table('retail_system_sales')
        ->where('branch', (string) (int) $branchId)
        ->where('date', $today)
        ->select('payment_method', DB::raw('SUM(quantity * price) as total'))
        ->groupBy('payment_method')
        ->pluck('total', 'payment_method');

    // ── Physical cash today ───────────────────────────────────────────────
    $physicalCashToday = (float) DB::connection('tenant')
        ->table('retail_physical_cash')
        ->where('branch_id', $branchId)
        ->where('date', $today)
        ->value('amount') ?? 0;
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
/* ══════════════════════════════════════════════════════════════
   POS — silver/gray panels, #4B5EBD accent
══════════════════════════════════════════════════════════════ */
.card-header {
  padding: 0.5rem 1.5rem !important;
  background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff;
  border-radius: 10px 10px 0 0 !important;
}
.card-body  { padding: 0 !important; display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0; overflow: hidden; }
.card       { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; overflow:hidden; display: flex; flex-direction: column; }
.card-header h4 { color:#fff; font-weight:600; margin-bottom:0; display:flex; align-items:center; }
.card-header .btn-light {
  height:28px; padding:0 10px; position: relative;
  display:flex; align-items:center; justify-content:center; line-height:1;
}
.card-header .btn-light:hover { background-color:#f8f9fa; transition:background-color 0.2s; }

/* ── Date pill button in the card header ── */
#posDateBtn {
    height: 30px; padding: 0 14px; border-radius: 6px;
    background-color: #e8e8e8; color: #1e293b; border: none;
    font-weight: 600; font-size: 13px;
    display: inline-flex; align-items: center; gap: 6px;
    text-decoration: none; cursor: pointer;
}
#posDateBtn i { color: #1e293b; }
#posDateBtn:hover { background-color: #d8d8d8; }

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

#pos-workspace-row { flex: 1 1 auto; min-height: 0; }

#pos-left-col { background-color: #fff; padding: 0; display: flex; flex-direction: column; }

/* ── Search bar — blue background matching cart header, silver input ── */
#pos-search-row { background-color: #4B5EBD; padding: 8px; flex: 0 0 auto; }

#pos-search-wrap { position: relative; }
#pos-search-wrap i {
    position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
    color: #595959; font-size: 16px; pointer-events: none;
}
#pos-search {
    background-color: silver;
    text-transform: uppercase; font-weight: bold;
    border: 1px solid rgba(255,255,255,0.35);
    width: 100%; height: 34px;
    border-radius: 4px; padding: 0 10px 0 32px; outline: none;
    color: #1a1a1a;
}
#pos-search::placeholder { color: #595959; font-weight: bold; text-transform: none; }
#pos-search:focus { background-color: #d9d9d9; border-color: rgba(255,255,255,0.65); }

#pos-product-display { flex: 1 1 auto; overflow-y: auto; }

.prd-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 6px 8px 6px 8px; border-bottom: 1px solid #a6a6a6; border-top: 1px solid #a6a6a6;
    color: black; background-color: #cccccc;
}
.prd-row .prd-link {
    color: black; text-decoration: none; cursor: pointer;
    flex: 0 0 70%; max-width: 70%; min-width: 0; overflow: hidden;
}
.prd-row.prd-oos .prd-link { opacity: .5; cursor: not-allowed; }
.prd-name { text-transform: uppercase; font-weight: bold; font-size: 14px; font-family: inherit; }
.prd-code { color: #8a8a8a; font-weight: 600; font-size: 13px; font-family: monospace; margin-left: 4px; }
.prd-code .val { color: #c0392b; }
.prd-meta { color: gray; font-family: monospace; font-size: 13px; margin-left: 6px; }
.prd-stock-tag { color: #8a8a8a; font-weight: 600; font-size: 16px; font-family: monospace; margin-left: 6px; }
.prd-stock-tag .val { color: #c0392b; }

/* ── Qty input: capped at 30% with breathing room on the right ── */
.prd-qty-input {
    text-align: center; flex: 0 0 28%; max-width: 28%; box-sizing: border-box;
    border-radius: 5px; border: 1px ridge #b3b3b3;
    background: transparent; font-size: 15px; font-weight: bold; color: #1a1a1a; flex-shrink: 0;
    height: 36px; margin-left: 8px; margin-right: 6px;
}
.prd-qty-input:focus { outline: 1px solid #4B5EBD; background: transparent; }

#pos-right-col { padding: 0; border-left: 1px solid #adadad; display: flex; flex-direction: column; }
#pos-cart-bar {
    background-color: #4B5EBD; padding: 8px 10px;
    display: flex; align-items: center; justify-content: space-between; flex: 0 0 auto;
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

/* ── Modals ───────────────────────────────────────────────────────────── */
.mh-pos { background-color: #4B5EBD; padding: 10px 16px !important; border-bottom: none; }
.mh-pos-title { color: #fff; font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
.mh-close-w { filter: brightness(0) invert(1); opacity: .8; }
.mh-close-w:hover { opacity: 1; }

/* White modal header variant */
.mh-white {
    background-color: #fff; padding: 10px 16px !important;
    border-bottom: 1px solid #e3e3e3;
    display: flex; align-items: center; justify-content: space-between; width: 100%;
}
.mh-white .mh-iv-branch { color: #1e293b; }
.mh-white .mh-iv-date   { color: #6c757d; }
.mh-white .mh-icon-btn  { color: #4B5EBD; }

.checkout-summary { background: #e6e6e6; border: 1px solid #a6a6a6; padding: 10px 14px; margin-bottom: 14px; font-size: 12px; }
.checkout-summary-row { display: flex; justify-content: space-between; padding: 3px 0; border-bottom: 1px solid #cccccc; color: #333; }
.checkout-summary-row:last-child { border-bottom: none; font-weight: 700; font-size: 14px; color: #1e293b; }

.pm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px; }
.pm-card { border: 1.5px solid #a6a6a6; padding: 10px 12px; cursor: pointer; display: flex; align-items: center; gap: 8px; user-select: none; background: #f2f2f2; }
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

/* ── Interval modal ─────────────────────────────────────────────────── */
.mh-icon-btn { color: #fff; opacity: .85; font-size: 20px; cursor: pointer; display: inline-flex; }
.mh-icon-btn:hover { opacity: 1; }
.mh-iv-title { display: flex; flex-direction: column; line-height: 1.35; }
.mh-iv-branch { color: #fff; font-size: 15px; font-weight: 700; }
.mh-iv-date { color: #fff; font-size: 13px; font-weight: 500; opacity: .85; }

.iv-slot-text { color: #1e293b; font-weight: 600; font-size: 13px; }

/* ── Sales amount in interval table: black, bold, clickable — no underline/link color ── */
.iv-amount-link {
    color: #1e293b; font-weight: 700; text-decoration: none; cursor: pointer;
}
.iv-amount-link:hover { color: #000; }

#ivGrandTotal { font-weight: 800 !important; color: #1e293b; }

.iv-total-row {
    display: flex; justify-content: space-between; align-items: center;
    background: #eceefb; border: 1px solid #d7dcf6; border-radius: 6px;
    padding: 8px 12px; margin-top: 10px; font-weight: 700; font-size: 13px; color: #1e293b;
}
.pay-summary-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6c757d; margin: 16px 0 8px; }
.pay-summary-row { display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; border: 1px solid #e3e3e3; border-radius: 6px; margin-bottom: 6px; background: #fafafa; }
.pay-summary-row .psr-label { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #1e293b; }
.pay-summary-row .psr-label i { color: #4B5EBD; font-size: 16px; }
.pay-summary-row .psr-value { font-size: 13px; font-weight: 700; color: #1e293b; }

/* ── Edit interval / action rows ──────────────────────────────────── */
.iv-action-btn { font-size:11px; border:none; background:transparent; padding:2px 6px; cursor:pointer; border-radius:3px; }
.iv-edit-btn   { color:#4B5EBD; }
.iv-edit-btn:hover { background:#eceefb; }
.iv-del-btn    { color:#dc2626; }
.iv-del-btn:hover { background:#fee2e2; }

/* ── Calculator ────────────────────────────────────────────────────── */
.calc-screen { background: #1e2233; padding: 16px 18px 14px; }
#calcExpression { color: #8a93b8; font-size: 13px; min-height: 18px; text-align: right; font-family: monospace; }
#calcDisplay { color: #fff; font-size: 42px; font-weight: 700; text-align: right; font-family: monospace; word-break: break-all; line-height: 1.1; margin-top: 4px; }
.calc-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: #2b3050; }
.calc-btn { border: none; background: #262b45; color: #fff; font-size: 21px; font-weight: 600; padding: 21px 0; cursor: pointer; transition: background .15s; }
.calc-btn:hover { background: #323955; }
.calc-fn { background: #3a4060; color: #c9cfe8; font-size: 16px; }
.calc-fn:hover { background: #454c70; }
.calc-op { background: #4B5EBD; color: #fff; }
.calc-op:hover { background: #576CC0; }
.calc-zero { grid-column: span 2; }
.calc-eq { background: #22c55e; color: #fff; }
.calc-eq:hover { background: #16a34a; }

/* ── Pending ────────────────────────────────────────────────────────── */
#pendingListWrap { max-height: 60vh; overflow: auto; }
#pendingTable { min-width: 540px; }
#pendingTable thead th { position: sticky; top: 0; background: silver; z-index: 1; }

/* ── Scrollbars ─────────────────────────────────────────────────────── */
#pos-product-display::-webkit-scrollbar, #pos-cart-table-wrap::-webkit-scrollbar { width: 5px; }
#pos-product-display::-webkit-scrollbar-thumb, #pos-cart-table-wrap::-webkit-scrollbar-thumb { background: #999; }

/* Remove spinner arrows on number inputs */
input[type=number]::-webkit-outer-spin-button,
input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
input[type=number] { -moz-appearance: textfield; }

/* ── Edit interval modal — white header style fields ─────── */
.edit-iv-field {
    width: 100%; border: 1px solid #dee2e6; border-radius: 6px;
    padding: 8px 12px; font-size: 14px; color: #1e293b; outline: none;
}
.edit-iv-field:focus { border-color: #4B5EBD; box-shadow: 0 0 0 2px rgba(75,94,189,.15); }
.edit-iv-field:disabled { background: #f8f9fa; color: #6c757d; cursor: default; }
.edit-iv-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6c757d; display: block; margin-bottom: 5px; }

/* ── Delete confirm modal ─────────────────────────────────── */
#ivDeleteConfirmModal .modal-body { text-align: center; padding-bottom: 28px; }

@media (max-width: 900px) {
    .card { height: calc(100vh - 110px) !important; }
    #pos-workspace-row { flex-direction: column; }
    #pos-left-col { flex: 0 1 auto; }
    #pos-product-display { flex: 0 1 auto; max-height: 45vh; }
    #pos-right-col { flex: 1 1 auto; min-height: 0; }
}
</style>

{{-- ══ Progress bar ════════════════════════════════════════════════════════ --}}
<div class="progress" id="progressBar" role="progressbar"
     style="height:8px;transform:rotate(180deg);display:none">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card" style="height: calc(100vh - 140px);">

    {{-- ── Card header ────────────────────────────────────────────────────── --}}
    <div class="card-header d-flex justify-content-between align-items-center">
        <button type="button" id="posDateBtn" title="Refresh" onclick="window.location.href='{{ url()->current() }}'">
            <i class="ri-refresh-line"></i> {{ $displayDate }}
        </button>
        <div class="d-flex align-items-center" style="gap:4px;">
            <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="posUploadBtn" title="Pending sales — view &amp; upload">
                <i class="ri-cloud-line"></i>
                <span class="pos-badge" id="posPendingBadge"></span>
            </a>
            <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="posCalcBtn"         title="Calculator"><i class="ri-calculator-line"></i></a>
            <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="posRecentBtn"       title="Recently sold items"><i class="ri-list-check"></i></a>
            <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="posViewIntervalBtn" title="View interval sales"><i class="ri-eye-line"></i></a>
            <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="posIntervalBtn"     title="Add interval sales"><i class="ri-add-circle-line"></i></a>
        </div>
    </div>

    {{-- ── Card body ──────────────────────────────────────────────────────── --}}
    <div class="card-body">
        <div class="row g-0" id="pos-workspace-row">

            {{-- LEFT — search + products --}}
            <div class="col-md-5" id="pos-left-col">
                <div id="pos-search-row">
                    <div id="pos-search-wrap">
                        <i class="ri-smartphone-line"></i>
                        <input type="text" id="pos-search" placeholder="Search product name or code" autocomplete="off" onclick="clearPosSearch()">
                    </div>
                </div>
                <div id="pos-product-display"></div>
                <script type="application/json" id="pos-products-json">{!! json_encode($products->map(function($p){
                    return ['id'=>$p->id,'name'=>$p->name,'code'=>$p->code,'unit'=>$p->unit,'price'=>$p->effective_price,'stock'=>(float)$p->stock_quantity];
                })) !!}</script>
            </div>

            {{-- RIGHT — cart --}}
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
                                <th>Item</th><th>Unit</th><th>Price</th><th>Qty</th><th>Total</th><th>Actn</th>
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

{{-- ══ CHECKOUT MODAL ═════════════════════════════════════════════════════ --}}
<div class="modal fade" id="checkoutModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title"><i class="ri-shopping-bag-3-line"></i> Confirm Sale</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:16px 18px;">
                <div class="checkout-summary" id="checkoutSummaryWrap"></div>
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6c757d;margin-bottom:8px;">Payment Method</div>
                <div class="pm-grid">
                    @foreach($paymentMethods as $pm)
                    <div class="pm-card {{ $loop->first ? 'active' : '' }}" data-pm="{{ $pm['id'] }}" onclick="selectPaymentMethod('{{ $pm['id'] }}', this)">
                        <i class="{{ $pm['icon'] }}"></i>
                        <span class="pm-label">{{ $pm['label'] }}</span>
                    </div>
                    @endforeach
                </div>
                <div class="checkout-amount-wrap" id="amountPaidWrap">
                    <label>Amount Tendered (MWK)</label>
                    <input type="number" class="checkout-amount-input" id="amountPaidInput" placeholder="Amount tendered" min="0" oninput="calcChange()">
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

{{-- ══ PENDING MODAL ══════════════════════════════════════════════════════ --}}
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

{{-- ══ ADD INTERVAL MODAL ════════════════════════════════════════════════ --}}
<div class="modal fade" id="intervalModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title"><i class="ri-add-circle-line"></i> Interval Sales</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:16px 18px;" id="intervalModalBody">
                @if($remainingIntervals->isEmpty())
                    <div id="ivAllDoneMsg" style="text-align:center;padding:20px;color:#595959;font-size:13px;">
                        <i class="ri-check-double-line" style="color:#16a34a;font-size:24px;display:block;margin-bottom:8px;"></i>
                        All intervals have been entered for today.
                    </div>
                @else
                    <div class="mb-3" id="ivSlotSelectWrap">
                        <label class="form-label fw-semibold" style="font-size:12px;">Time Slot</label>
                        <select class="form-select form-select-sm" id="intervalSlotSelect">
                            @foreach($remainingIntervals as $iv)
                            <option value="{{ $iv->id }}" data-slot="{{ $iv->slot }}" {{ $loop->first ? 'selected' : '' }}>
                                {{ $iv->slot }}
                            </option>
                            @endforeach
                        </select>
                        <div id="ivNextHint" style="font-size:11px;color:#8a8a8a;margin-top:4px;">
                            Next in sequence: <strong id="ivNextSlotLabel">{{ $nextIntervalSlot ?? 'None remaining' }}</strong>
                        </div>
                    </div>
                    <div class="mb-3" id="ivSalesInputWrap">
                        <label class="form-label fw-semibold" style="font-size:12px;">Sales (MWK)</label>
                        <input type="number" class="form-control form-control-sm" id="intervalSalesInput"
                               placeholder="Enter sales amount" min="0" autocomplete="off">
                    </div>
                @endif
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;" id="intervalModalFooter"
                 @if($remainingIntervals->isEmpty()) style="display:none;" @endif>
                @if($remainingIntervals->isNotEmpty())
                <button class="btn btn-primary btn-sm" id="intervalSubmitBtn" onclick="submitIntervalSale()">
                    <i class="ri-check-line me-1"></i> Submit
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ══ EDIT INTERVAL MODAL ═══════════════════════════════════════════════ --}}
<div class="modal fade" id="editIntervalModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-white">
                <div class="mh-iv-title">
                    <span class="mh-iv-branch" style="color:#1e293b;font-size:15px;font-weight:700;">{{ $branchName }}</span>
                    <span class="mh-iv-date"   style="color:#6c757d;font-size:13px;" id="editIvDateLabel">{{ $displayDate }}</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px 20px 8px;">
                <input type="hidden" id="editIvId">
                <input type="hidden" id="editIvOldSales">
                <div class="mb-4">
                    <label class="edit-iv-label">Time Slot</label>
                    <input type="text" class="edit-iv-field" id="editIvSlot" disabled>
                </div>
                <div class="mb-2">
                    <label class="edit-iv-label">Sales (MWK)</label>
                    <input type="number" class="edit-iv-field" id="editIvSales" min="0"
                           placeholder="0" autocomplete="off"
                           style="font-size:22px;font-weight:700;height:52px;">
                </div>
            </div>
            <div class="modal-footer" style="padding:12px 20px 16px;display:flex;justify-content:space-between;align-items:center;">
                <button type="button" id="editIvDeleteBtn"
                        style="height:38px;padding:0 18px;border-radius:6px;font-size:13px;font-weight:700;
                               background:#fee2e2;color:#dc2626;border:none;
                               display:flex;align-items:center;gap:6px;cursor:pointer;"
                        onclick="triggerIvDelete()">
                    <i class="ri-delete-bin-line"></i> Delete
                </button>
                <button type="button" id="editIvSubmitBtn"
                        style="height:38px;padding:0 22px;border-radius:6px;font-size:13px;font-weight:700;
                               background:#4B5EBD;color:#fff;border:none;
                               display:flex;align-items:center;gap:6px;cursor:pointer;"
                        onclick="submitEditInterval()">
                    <i class="ri-check-line"></i> Update
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ══ DELETE CONFIRMATION MODAL ══════════════════════════════════════════ --}}
<div class="modal fade" id="ivDeleteConfirmModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog" style="max-width:350px;margin:1.75rem auto;">
        <div class="modal-content">
            <div class="modal-body text-center pb-4">
                <i class="ri-error-warning-line text-danger" style="font-size:70px"></i>
                <h4 class="mt-2">Delete <span id="ivDeleteSlotLabel" class="text-danger"></span>?</h4>
                <h5>This cannot be undone.</h5>
                <a href="#" class="btn btn-danger me-2 mt-3" id="ivDeleteConfirmBtn">Yes, Delete it</a>
                <a href="#" class="btn btn-info mt-3"        id="ivDeleteKeepBtn">No, Keep it</a>
            </div>
        </div>
    </div>
</div>

{{-- ══ VIEW INTERVAL MODAL ═══════════════════════════════════════════════ --}}
<div class="modal fade" id="viewIntervalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-white">
                <div class="mh-iv-title">
                    <span class="mh-iv-branch">{{ $branchName }}</span>
                    <span class="mh-iv-date">{{ $displayDate }}</span>
                </div>
                <div class="d-flex align-items-center" style="gap:14px;margin-left:auto;">
                    <a href="#" id="ivToggleBtn" class="mh-icon-btn" title="View payment breakdown"
                       onclick="event.preventDefault();toggleIntervalView();">
                        <i class="ri-bank-card-line" id="ivToggleIcon" style="color:#4B5EBD;"></i>
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body" style="padding:16px 18px;">

                {{-- PANE 1 — Interval table --}}
                <div id="iv-intervals-pane">
                    <div style="max-height:42vh;overflow-y:auto;">
                        <table class="table table-sm mb-0" style="font-size:13px;" id="ivIntervalTable">
                            <thead style="position:sticky;top:0;background:#fff;">
                                <tr>
                                    <th style="font-size:13px;font-weight:700;border-bottom:2px solid #737373;border-top:2px solid #737373;">User</th>
                                    <th style="font-size:13px;font-weight:700;border-bottom:2px solid #737373;border-top:2px solid #737373;">Interval</th>
                                    <th class="text-end" style="font-size:13px;font-weight:700;border-bottom:2px solid #737373;border-top:2px solid #737373;">Sales</th>
                                </tr>
                            </thead>
                            <tbody id="ivIntervalTbody">
                                @forelse($todaysIntervalSales as $is)
                                <tr id="ivrow_{{ $is->id }}" data-interval-id="{{ $is->interval_id }}" data-slot="{{ $is->slot }}">
                                    <td style="font-size:13px;">{{ $is->user_name ?? '—' }}</td>
                                    <td class="iv-slot-text" style="font-size:13px;">{{ $is->slot }}</td>
                                    <td class="text-end">
                                        <a href="#" class="iv-amount-link"
                                            onclick="event.preventDefault();openEditIntervalFromView({{ $is->id }}, '{{ addslashes($is->slot) }}', {{ $is->sales }})">
                                            {{ number_format($is->sales, 0) }}
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr id="ivEmptyRow"><td colspan="3" class="text-center text-muted">No intervals logged yet today.</td></tr>
                                @endforelse
                                @if($todaysIntervalSales->isNotEmpty())
                                <tr id="ivTotalRow">
                                    <td style="border-top:2px solid #737373;border-bottom:2px solid #737373;"></td>
                                    <td style="border-top:2px solid #737373;border-bottom:2px solid #737373;font-weight:700;font-size:13px;">Grand Total</td>
                                    <td class="text-end" style="border-top:2px solid #737373;border-bottom:2px solid #737373;font-weight:800;font-size:13px;" id="ivGrandTotal">
                                        MWK {{ number_format($intervalTotal, 0) }}
                                    </td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- PANE 2 — Payment breakdown --}}
                <div id="iv-payments-pane" style="display:none;">
                    <div id="ivPaymentRowsWrap">
                        @foreach($paymentMethods as $pm)
                        <div class="pay-summary-row" data-pm="{{ $pm['id'] }}">
                            <span class="psr-label"><i class="{{ $pm['icon'] }}"></i>{{ $pm['label'] }}</span>
                            <span class="psr-value">MWK {{ number_format($todaysPaymentSummary[$pm['id']] ?? 0, 0) }}</span>
                        </div>
                        @endforeach
                    </div>
                    <div class="iv-total-row">
                        <span>Total</span>
                        <span id="ivPaymentGrandTotal">MWK {{ number_format($todaysPaymentSummary->sum(), 0) }}</span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- ══ RECENTLY SOLD MODAL ═══════════════════════════════════════════════ --}}
<div class="modal fade" id="recentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title"><i class="ri-list-check"></i> Recently Sold Items</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div style="max-height:60vh;overflow:auto;">
                    <table class="table table-sm mb-0" style="font-size:12px;min-width:560px;">
                        <thead style="position:sticky;top:0;background:silver;">
                            <tr>
                                <th>Product</th><th class="text-center">Unit</th>
                                <th class="text-center">Price</th><th class="text-center">Qty</th>
                                <th class="text-center">Total</th><th class="text-center">Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentSales as $rs)
                            <tr>
                                <td>{{ $rs->product }}</td>
                                <td class="text-center">{{ $rs->unit }}</td>
                                <td class="text-center">{{ number_format($rs->price, 0) }}</td>
                                <td class="text-center">{{ number_format($rs->quantity, 2) }}</td>
                                <td class="text-center">{{ number_format($rs->quantity * $rs->price, 0) }}</td>
                                <td class="text-center">{{ $rs->payment_method ?? 'cash' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">No sales recorded yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══ CALCULATOR MODAL ══════════════════════════════════════════════════ --}}
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
<input type="hidden" id="posTransId"   value="{{ $transId }}">
<input type="hidden" id="posUserId"    value="{{ $userId }}">
<input type="hidden" id="posUserName"  value="{{ Auth::user()->name ?? Auth::id() }}">
<input type="hidden" id="posBranchId"  value="{{ $branchId }}">
<input type="hidden" id="posBranchName" value="{{ $branchName }}">
<input type="hidden" id="posDate"      value="{{ $today }}">

@endsection

@section('scripts')
<script>
'use strict';

/* ══════════════════════════════════════════════════════════════
   CONSTANTS & STATE
══════════════════════════════════════════════════════════════ */
const POS_CART_KEY  = 'netacube_pos_cart';
const POS_CLOUD_KEY = 'netacube_pos_cloud';

let cart      = [];
let cloudData = [];
let activePaymentMethod = 'cash';
let allProducts = [];

// Current interval being edited/deleted
let currentIvId    = null;
let currentIvSlot  = null;
let currentIvSales = null;

/* ── Helpers ──────────────────────────────────────────────── */
function escHtml(str) {
    return String(str == null ? '' : str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function fmtNum(n) {
    if (n === null || n === undefined || n === '') return '0';
    return parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
}
function fmtQty(n) {
    if (n === null || n === undefined || n === '') return '0';
    return parseFloat(n).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}
function getDeviceName() {
    const ua = navigator.userAgent;
    let os = 'Unknown OS';
    if (/Windows/i.test(ua))          os = 'Windows';
    else if (/Android/i.test(ua))     os = 'Android';
    else if (/iPhone|iPad/i.test(ua)) os = 'iOS';
    else if (/Mac/i.test(ua))         os = 'macOS';
    else if (/Linux/i.test(ua))       os = 'Linux';
    let browser = 'Browser';
    if (/Edg\//i.test(ua))            browser = 'Edge';
    else if (/Chrome\//i.test(ua))    browser = 'Chrome';
    else if (/Firefox\//i.test(ua))   browser = 'Firefox';
    else if (/Safari\//i.test(ua))    browser = 'Safari';
    return browser + ' on ' + os;
}
function clearPosSearch() {
    const srch = document.getElementById('pos-search');
    if (srch.value) { srch.value = ''; document.getElementById('pos-product-display').innerHTML = ''; }
}
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

/* ══════════════════════════════════════════════════════════════
   INIT
══════════════════════════════════════════════════════════════ */
$(document).ready(function () {
    try { allProducts = JSON.parse(document.getElementById('pos-products-json').textContent || '[]'); }
    catch (e) { allProducts = []; }

    const display = document.getElementById('pos-product-display');

    function filterProducts(q) {
        q = q.toLowerCase();
        return allProducts.filter(p =>
            (p.name || '').toLowerCase().includes(q) ||
            (p.code || '').toLowerCase().includes(q)
        );
    }

    function renderRows(products) {
        if (!products.length) {
            display.innerHTML = '<div style="text-align:center;padding:24px 12px;color:#595959;font-size:13px;background:#cccccc;">No products matched your search.</div>';
            return;
        }
        let html = '';
        products.forEach(p => {
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
                       min="0" max="${p.stock}" autocomplete="off"
                       ${oos ? 'disabled' : ''}
                       onchange="prdQtyChange(${p.id})">
            </div>`;
        });
        display.innerHTML = html;
    }

    $('#pos-search').on('keyup', function () {
        const q = $(this).val().trim();
        if (q.length < 2) { display.innerHTML = ''; return; }
        renderRows(filterProducts(q));
    });

    loadCart(); loadCloud(); renderCart(); updatePendingBadge();
    document.getElementById('pos-search').focus();

    // ── Delete confirm modal bindings ────────────────────────────────────
    document.getElementById('ivDeleteKeepBtn').addEventListener('click', function(e) {
        e.preventDefault();
        $('#ivDeleteConfirmModal').modal('hide');
    });
    document.getElementById('ivDeleteConfirmBtn').addEventListener('click', function(e) {
        e.preventDefault();
        $('#ivDeleteConfirmModal').modal('hide');
        setTimeout(() => executeDeleteInterval(), 200);
    });
});

/* ══════════════════════════════════════════════════════════════
   PRODUCT ACTIONS
══════════════════════════════════════════════════════════════ */
function findProduct(id) { return allProducts.find(p => p.id === id); }

function prdRowClick(id) {
    const p = findProduct(id);
    if (!p) return;
    if (p.stock <= 0) { toastr.warning(p.name + ' is out of stock.'); return; }
    addToCart({ id: p.id, name: p.name, unit: p.unit, price: p.price, stock: p.stock, qty: 1 });
    const qInput = document.getElementById('qinput_' + id);
    if (qInput) qInput.value = '';
    clearSearch();
}

function prdQtyChange(id) {
    const p = findProduct(id);
    if (!p) return;
    const input = document.getElementById('qinput_' + id);
    const qty   = parseFloat(input.value);
    if (!qty || qty <= 0) { input.value = ''; return; }
    if (qty > p.stock) {
        toastr.error('Quantity for ' + p.name + ' must be ≤ ' + p.stock);
        input.value = ''; return;
    }
    addToCart({ id: p.id, name: p.name, unit: p.unit, price: p.price, stock: p.stock, qty });
    input.value = '';
    clearSearch();
}

function clearSearch() {
    const srch = document.getElementById('pos-search');
    srch.value = '';
    document.getElementById('pos-product-display').innerHTML = '';
    srch.focus();
}

/* ══════════════════════════════════════════════════════════════
   CART
   — No success toast on add
   — Clear duplicate error message with edit hint
══════════════════════════════════════════════════════════════ */
function addToCart(item) {
    const existing = cart.find(c => c.id === item.id);
    if (existing) {
        // Item already in cart — show helpful message, do nothing else
        toastr.error(
            '<strong>' + escHtml(item.name) + '</strong> is already in the cart.<br>Edit the quantity in the cart if needed.',
            'Already Added',
            { timeOut: 3000, escapeHtml: false }
        );
        return;
    }
    cart.push({ ...item });
    saveCart(); renderCart();
    // No success toast — the cart updating is visual feedback enough
}

function removeFromCart(id) {
    cart = cart.filter(c => c.id !== id);
    saveCart(); renderCart();
}

function updateCartQtyInput(id, val) {
    const item = cart.find(c => c.id === id);
    if (!item) return;
    const newQty = parseFloat(val) || 1;
    if (newQty <= 0) { removeFromCart(id); return; }
    if (newQty > item.stock) {
        toastr.warning('Only ' + item.stock + ' in stock.');
        document.getElementById('cqty_' + id).value = item.qty; return;
    }
    item.qty = newQty;
    const tEl = document.getElementById('ctot_' + id);
    if (tEl) tEl.textContent = fmtNum(item.qty * item.price);
    saveCart(); renderCartTotals();
}

function saveCart()  { localStorage.setItem(POS_CART_KEY,  JSON.stringify(cart)); }
function loadCart()  { try { cart  = JSON.parse(localStorage.getItem(POS_CART_KEY)  || '[]'); } catch(e) { cart  = []; } }
function saveCloud() { localStorage.setItem(POS_CLOUD_KEY, JSON.stringify(cloudData)); }
function loadCloud() { try { cloudData = JSON.parse(localStorage.getItem(POS_CLOUD_KEY) || '[]'); } catch(e) { cloudData = []; } }

function cartTotal() { return cart.reduce((s, c) => s + c.qty * c.price, 0); }

function renderCartTotals() {
    document.getElementById('cartTotalPill').textContent = fmtNum(cartTotal());
}

function renderCart() {
    const table = document.getElementById('pos-cart-table');
    const tbody = document.getElementById('pos-cart-tbody');
    const empty = document.getElementById('pos-cart-empty');

    if (!cart.length) {
        tbody.innerHTML = '';
        table.style.display = 'none';
        empty.style.display = 'block';
        document.getElementById('pos-checkout-btn').disabled = true;
        renderCartTotals(); return;
    }

    empty.style.display = 'none';
    table.style.display = 'table';

    let html = '';
    cart.forEach(item => {
        html += `
        <tr id="crow_${item.id}">
            <td class="pcr-name" title="${escHtml(item.name)}">${escHtml(item.name)}</td>
            <td>${escHtml(item.unit)}</td>
            <td>${fmtNum(item.price)}</td>
            <td><input class="pcr-qinput" id="cqty_${item.id}" type="number"
                   value="${item.qty}" min="1" max="${item.stock}"
                   onchange="updateCartQtyInput(${item.id}, this.value)"></td>
            <td id="ctot_${item.id}">${fmtNum(item.qty * item.price)}</td>
            <td><a href="#" class="pcr-remove" onclick="event.preventDefault();removeFromCart(${item.id})">X</a></td>
        </tr>`;
    });
    tbody.innerHTML = html;
    document.getElementById('pos-checkout-btn').disabled = false;
    renderCartTotals();
}

/* ══════════════════════════════════════════════════════════════
   CHECKOUT
══════════════════════════════════════════════════════════════ */
function openCheckout() {
    if (!cart.length) return;
    let rows = '';
    cart.forEach(c => {
        rows += `<div class="checkout-summary-row">
            <span>${escHtml(c.name)} × ${fmtQty(c.qty)} ${escHtml(c.unit)}</span>
            <span>MWK ${fmtNum(c.qty * c.price)}</span>
        </div>`;
    });
    rows += `<div class="checkout-summary-row"><span>Total</span><span>MWK ${fmtNum(cartTotal())}</span></div>`;
    document.getElementById('checkoutSummaryWrap').innerHTML = rows;
    document.getElementById('amountPaidInput').value = '';
    document.getElementById('checkout-change-row').classList.remove('show', 'negative');
    document.querySelectorAll('.pm-card').forEach(c => c.classList.remove('active'));
    const cashCard = document.querySelector('.pm-card[data-pm="cash"]');
    if (cashCard) cashCard.classList.add('active');
    activePaymentMethod = 'cash';
    document.getElementById('amountPaidWrap').style.display = 'block';
    $('#checkoutModal').modal('show');
    setTimeout(() => document.getElementById('amountPaidInput').focus(), 450);
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

function confirmSale() {
    if (!cart.length) return;
    const total = cartTotal();

    let paid;
    if (activePaymentMethod === 'cash') {
        const raw     = document.getElementById('amountPaidInput').value;
        const entered = parseFloat(raw);
        paid = (raw === '' || isNaN(entered) || entered <= 0) ? total : entered;
        if (paid < total) { toastr.warning('Amount tendered is less than the total.'); return; }
    } else {
        paid = total;
    }

    const transId    = document.getElementById('posTransId').value;
    const date       = document.getElementById('posDate').value;
    const userName   = document.getElementById('posUserName').value;
    const branchId   = document.getElementById('posBranchId').value;
    const branchName = document.getElementById('posBranchName').value;
    const time       = new Date().toTimeString().slice(0, 8);
    const deviceName = getDeviceName();
    const userAgent  = navigator.userAgent;

    cart.forEach(c => {
        cloudData.push({
            branch_product_id: c.id,
            product:           c.name,
            unit:              c.unit,
            price:             c.price,
            transid:           transId,
            date,
            time,
            user:              userName,
            branch:            branchId,
            quantity:          c.qty,
            qty_before:        c.stock,
            qty_sold:          c.qty,
            qty_after:         Math.max(0, c.stock - c.qty),
            payment_method:    activePaymentMethod,
            amount_paid:       paid,
            slot:              '0',
            device_name:       deviceName,
            user_agent:        userAgent,
        });
    });

    saveCloud(); updatePendingBadge();
    cart = []; saveCart(); renderCart(); refreshTransId();
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

function renderPendingModal() {
    const tbody = document.getElementById('pendingTbody');
    const total = cloudData.reduce((s, c) => s + (c.quantity * c.price), 0);
    document.getElementById('pendingTotalLabel').textContent = fmtNum(total);

    if (!cloudData.length) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#595959;padding:30px;font-size:13px;">No pending sales.</td></tr>';
        return;
    }
    let html = '';
    cloudData.forEach(e => {
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
    document.getElementById('pendingUploadBtn').disabled = true;

    try {
        const res = await fetch('/{{ request()->route("tenantName") }}/sales/retail/upload-sales', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body: new URLSearchParams({ data: JSON.stringify(cloudData) }),
        });
        const failed = await res.json();

        if (Array.isArray(failed) && failed.length === 0) {
            cloudData = [];
            saveCloud(); updatePendingBadge();
            toastr.success('All sales uploaded successfully.', 'Done');
            $('#pendingModal').modal('hide');
            // POS sales were just uploaded — payment totals may have changed.
            refreshPaymentSummaryPane();
        } else if (Array.isArray(failed)) {
            cloudData = failed;
            saveCloud(); updatePendingBadge();
            toastr.warning(failed.length + ' item(s) could not be uploaded and remain pending.', 'Partial');
            refreshPaymentSummaryPane();
        } else {
            toastr.error('Unexpected server response.', 'Error');
        }
    } catch (err) {
        toastr.error('Upload failed — check your connection and try again.', 'Error');
    } finally {
        posLoaderHide();
        document.getElementById('pendingUploadBtn').disabled = false;
    }
}

/* ══════════════════════════════════════════════════════════════
   PAYMENT SUMMARY PANE — live refresh, no page reload
   Re-fetches today's per-payment-method totals for this branch and
   re-renders the rows + grand total inside #iv-payments-pane.
   Safe to call any time (insert/edit/delete interval, after upload);
   it only reflects retail_system_sales and never touches interval data.
══════════════════════════════════════════════════════════════ */
function refreshPaymentSummaryPane() {
    const branch = document.getElementById('posBranchId').value;
    const date   = document.getElementById('posDate').value;

    // IMPORTANT: branch+date are the same on every call within a session,
    // so a plain GET fetch can get served from the browser's HTTP cache
    // instead of hitting the server — which looks exactly like "stale
    // until I hard-reload". cache:'no-store' plus a _ts cache-buster
    // param guarantees every call is a real network round trip.
    const url = '/{{ request()->route("tenantName") }}/sales/retail/payment-summary?'
        + new URLSearchParams({ branch, date, _ts: Date.now() });

    fetch(url, { cache: 'no-store', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(res => {
            if (!res.ok) {
                console.error('refreshPaymentSummaryPane: HTTP ' + res.status + ' from payment-summary endpoint. Check that the route is registered.');
                return null;
            }
            return res.json();
        })
        .then(json => {
            if (!json) return;
            if (json.status !== 'success') {
                console.error('refreshPaymentSummaryPane: unexpected response', json);
                return;
            }
            const byMethod = json.data.by_method || {};
            const total    = json.data.total || 0;

            const wrap = document.getElementById('ivPaymentRowsWrap');
            if (wrap) {
                wrap.querySelectorAll('.pay-summary-row').forEach(row => {
                    const pmId = row.dataset.pm;
                    const valueEl = row.querySelector('.psr-value');
                    if (valueEl) valueEl.textContent = 'MWK ' + fmtNum(byMethod[pmId] || 0);
                });
            }
            const grandEl = document.getElementById('ivPaymentGrandTotal');
            if (grandEl) grandEl.textContent = 'MWK ' + fmtNum(total);
        })
        .catch(err => {
            console.error('refreshPaymentSummaryPane: network/parse error', err);
        });
}

/* ══════════════════════════════════════════════════════════════
   INTERVAL SALES — DOM helpers (no page reload)
══════════════════════════════════════════════════════════════ */

/**
 * Recalculate the grand total row from all .iv-amount-link cells.
 * Shows/hides the empty-state row as needed.
 */
function recalcIntervalGrandTotal() {
    const tbody = document.getElementById('ivIntervalTbody');
    if (!tbody) return;

    let grand = 0;
    tbody.querySelectorAll('.iv-amount-link').forEach(a => {
        grand += parseFloat(a.textContent.replace(/,/g, '')) || 0;
    });

    const dataRows = tbody.querySelectorAll('tr[id^="ivrow_"]');

    if (dataRows.length === 0) {
        // Remove total row, show empty state
        const totalRow = document.getElementById('ivTotalRow');
        if (totalRow) totalRow.remove();
        if (!document.getElementById('ivEmptyRow')) {
            const tr = document.createElement('tr');
            tr.id = 'ivEmptyRow';
            tr.innerHTML = '<td colspan="3" class="text-center text-muted">No intervals logged yet today.</td>';
            tbody.appendChild(tr);
        }
        return;
    }

    // Update or create total row
    let totalRow = document.getElementById('ivTotalRow');
    if (!totalRow) {
        totalRow = document.createElement('tr');
        totalRow.id = 'ivTotalRow';
        totalRow.innerHTML = `
            <td style="border-top:2px solid #737373;border-bottom:2px solid #737373;"></td>
            <td style="border-top:2px solid #737373;border-bottom:2px solid #737373;font-weight:700;font-size:13px;">Grand Total</td>
            <td class="text-end" style="border-top:2px solid #737373;border-bottom:2px solid #737373;font-weight:800;font-size:13px;" id="ivGrandTotal"></td>`;
        tbody.appendChild(totalRow);
    }
    const grandCell = document.getElementById('ivGrandTotal');
    if (grandCell) grandCell.textContent = 'MWK ' + fmtNum(grand);
}

/**
 * Append a new interval row to the view table after a successful insert.
 * Uses the real DB id returned by the server so edit links work immediately.
 */
function addIntervalRowToView(dbId, slot, sales, userName) {
    const tbody = document.getElementById('ivIntervalTbody');
    if (!tbody) return;

    // Remove empty-state row
    const emptyRow = document.getElementById('ivEmptyRow');
    if (emptyRow) emptyRow.remove();

    // Remove total row — we'll re-append it after the new row
    const oldTotal = document.getElementById('ivTotalRow');
    if (oldTotal) oldTotal.remove();

    // Build onclick safely — escape the slot string for JS
    const safeSlot = slot.replace(/\\/g, '\\\\').replace(/'/g, "\\'");

    const tr = document.createElement('tr');
    tr.id = 'ivrow_' + dbId;
    tr.dataset.slot = slot;
    // interval_id gets stashed by removeSlotFromDropdown() right after this call
    tr.dataset.intervalId = '';
    tr.innerHTML = `
        <td style="font-size:13px;">${escHtml(userName)}</td>
        <td class="iv-slot-text" style="font-size:13px;">${escHtml(slot)}</td>
        <td class="text-end">
            <a href="#" class="iv-amount-link"
               onclick="event.preventDefault();openEditIntervalFromView(${dbId},'${safeSlot}',${parseFloat(sales)})">
               ${fmtNum(sales)}
            </a>
        </td>`;
    tbody.appendChild(tr);

    recalcIntervalGrandTotal();
}

/**
 * Remove the used slot from the Add Interval dropdown.
 * Also updates the "Next in sequence" hint.
 * Stashes the interval_id on the newly-added view row so delete can restore it.
 */
function removeSlotFromDropdown(intervalId, newRowDbId) {
    const select = document.getElementById('intervalSlotSelect');
    if (!select) return;

    // Stash interval_id on the DOM row before removing the option
    const viewRow = document.getElementById('ivrow_' + newRowDbId);
    if (viewRow) viewRow.dataset.intervalId = intervalId;

    const opt = select.querySelector('option[value="' + intervalId + '"]');
    if (opt) opt.remove();

    if (select.options.length === 0) {
        // All intervals done — replace modal body with completion message
        const body = document.getElementById('intervalModalBody');
        if (body) {
            body.innerHTML = `<div id="ivAllDoneMsg" style="text-align:center;padding:20px;color:#595959;font-size:13px;">
                <i class="ri-check-double-line" style="color:#16a34a;font-size:24px;display:block;margin-bottom:8px;"></i>
                All intervals have been entered for today.
            </div>`;
        }
        const footer = document.getElementById('intervalModalFooter');
        if (footer) footer.style.display = 'none';
        return;
    }

    // Update next hint
    const firstOpt = select.options[0];
    const nextLabel = document.getElementById('ivNextSlotLabel');
    if (nextLabel && firstOpt) nextLabel.textContent = firstOpt.dataset.slot || firstOpt.text;
}

/**
 * Add a slot option back to the dropdown after a delete.
 */
function addSlotBackToDropdown(intervalId, slotLabel) {
    if (!intervalId) return;

    const body = document.getElementById('intervalModalBody');
    const footer = document.getElementById('intervalModalFooter');

    // If the "all done" message is showing, rebuild the dropdown form
    const allDoneMsg = document.getElementById('ivAllDoneMsg');
    if (allDoneMsg) {
        body.innerHTML = `
            <div class="mb-3" id="ivSlotSelectWrap">
                <label class="form-label fw-semibold" style="font-size:12px;">Time Slot</label>
                <select class="form-select form-select-sm" id="intervalSlotSelect"></select>
                <div id="ivNextHint" style="font-size:11px;color:#8a8a8a;margin-top:4px;">
                    Next in sequence: <strong id="ivNextSlotLabel"></strong>
                </div>
            </div>
            <div class="mb-3" id="ivSalesInputWrap">
                <label class="form-label fw-semibold" style="font-size:12px;">Sales (MWK)</label>
                <input type="number" class="form-control form-control-sm" id="intervalSalesInput"
                       placeholder="Enter sales amount" min="0" autocomplete="off">
            </div>`;
        if (footer) {
            footer.style.display = '';
            footer.innerHTML = `<button class="btn btn-primary btn-sm" id="intervalSubmitBtn" onclick="submitIntervalSale()">
                <i class="ri-check-line me-1"></i> Submit
            </button>`;
        }
    }

    const select = document.getElementById('intervalSlotSelect');
    if (!select) return;

    // Avoid duplicates
    if (select.querySelector('option[value="' + intervalId + '"]')) return;

    const opt = document.createElement('option');
    opt.value = intervalId;
    opt.dataset.slot = slotLabel;
    opt.textContent = slotLabel;
    select.appendChild(opt);

    const nextLabel = document.getElementById('ivNextSlotLabel');
    if (nextLabel) nextLabel.textContent = select.options[0].dataset.slot || select.options[0].text;
}

/* ══════════════════════════════════════════════════════════════
   INTERVAL SALES — Insert
   No page reload. Updates view table, dropdown, grand total, and
   the per-payment-method breakdown pane (kept fresh, even though
   interval sales don't change it directly).
   Uses standard HTTP status codes + a JSON { status, message, data } body
   returned by the controller (no more magic numbers 1/2/3).
══════════════════════════════════════════════════════════════ */
function submitIntervalSale() {
    const salesInput = document.getElementById('intervalSalesInput');
    const sales = parseFloat(salesInput ? salesInput.value : '');
    if (isNaN(sales) || sales < 0) { toastr.warning('Enter a valid sales amount (0 or more).'); return; }

    if (cloudData.length > 0) {
        toastr.warning('Upload pending POS sales before entering interval sales.');
        return;
    }

    const select = document.getElementById('intervalSlotSelect');
    const intervalId = select ? select.value : '';
    const selectedOpt = select ? select.selectedOptions[0] : null;
    const slotLabel   = selectedOpt ? (selectedOpt.dataset.slot || selectedOpt.text) : '';

    if (!intervalId) { toastr.warning('No interval slot available.'); return; }

    const btn = document.getElementById('intervalSubmitBtn');
    btn.disabled = true;
    posLoaderShow();

    fetch('/{{ request()->route("tenantName") }}/sales/retail/insert-interval-sale', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: new URLSearchParams({
            _token:      csrfToken(),
            user_id:     document.getElementById('posUserId').value,
            branch:      document.getElementById('posBranchId').value,
            date:        document.getElementById('posDate').value,
            interval_id: intervalId,
            sales:       sales,
        }),
    })
    .then(res => res.json().then(data => ({ status: res.status, data })))
    .then(({ status, data }) => {
        posLoaderHide();
        btn.disabled = false;

        if (status === 422) {
            const msg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Validation error.');
            toastr.error(msg, 'Validation');
            return;
        }
        if (status === 409) {
            toastr.error(data.message || 'This interval has already been entered for today.');
            return;
        }
        if (status === 404) {
            toastr.error(data.message || 'Interval not found.');
            return;
        }
        if (status === 201 && data.status === 'success') {
            const userName = document.getElementById('posUserName').value;
            addIntervalRowToView(data.data.id, data.data.slot, data.data.sales, userName);
            removeSlotFromDropdown(intervalId, data.data.id);
            refreshPaymentSummaryPane();

            if (salesInput) salesInput.value = '';
            $('#intervalModal').modal('hide');

            toastr.success('Interval recorded successfully.', 'Done');
            return;
        }

        // Genuine server error
        toastr.error(data.message || ('Server error (' + status + ').'), 'Error');
    })
    .catch(() => {
        posLoaderHide();
        btn.disabled = false;
        toastr.error('Could not reach the server — check your connection.', 'Network Error');
    });
}

/* ══════════════════════════════════════════════════════════════
   INTERVAL SALES — Open edit modal
══════════════════════════════════════════════════════════════ */
function openEditIntervalFromView(id, slot, sales) {
    $('#viewIntervalModal').modal('hide');
    setTimeout(() => openEditInterval(id, slot, sales), 300);
}

function openEditInterval(id, slot, sales) {
    currentIvId    = id;
    currentIvSlot  = slot;
    currentIvSales = sales;

    document.getElementById('editIvId').value       = id;
    document.getElementById('editIvSlot').value     = slot;
    document.getElementById('editIvSales').value    = sales;
    document.getElementById('editIvOldSales').value = sales;
    $('#editIntervalModal').modal('show');
    setTimeout(() => document.getElementById('editIvSales').focus(), 400);
}

/* ══════════════════════════════════════════════════════════════
   INTERVAL SALES — Edit (submit)
   No page reload. Updates the amount in the view table row.
══════════════════════════════════════════════════════════════ */
function submitEditInterval() {
    const newSales = parseFloat(document.getElementById('editIvSales').value);
    if (isNaN(newSales) || newSales < 0) { toastr.warning('Sales must be 0 or greater.'); return; }

    const btn = document.getElementById('editIvSubmitBtn');
    btn.disabled = true;
    posLoaderShow();

    const ivId    = document.getElementById('editIvId').value;
    const oldSales = parseFloat(document.getElementById('editIvOldSales').value) || 0;

    fetch('/{{ request()->route("tenantName") }}/sales/retail/edit-interval-sale', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: new URLSearchParams({
            _token:   csrfToken(),
            id:       ivId,
            sales:    newSales,
            oldsales: oldSales,
            branch:   document.getElementById('posBranchId').value,
            date:     document.getElementById('posDate').value,
        }),
    })
    .then(res => res.json().then(data => ({ status: res.status, data })))
    .then(({ status, data }) => {
        posLoaderHide();
        btn.disabled = false;

        if (status === 200 && data.status === 'success') {
            // Update the amount link in the view table row
            const row = document.getElementById('ivrow_' + ivId);
            if (row) {
                const link = row.querySelector('.iv-amount-link');
                if (link) {
                    link.textContent = fmtNum(data.data.sales);
                    const safeSlot = currentIvSlot.replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                    link.setAttribute('onclick',
                        `event.preventDefault();openEditIntervalFromView(${ivId},'${safeSlot}',${data.data.sales})`);
                }
            }
            // Update state & recalc total
            document.getElementById('editIvOldSales').value = data.data.sales;
            currentIvSales = data.data.sales;
            recalcIntervalGrandTotal();
            refreshPaymentSummaryPane();

            toastr.success('Interval updated successfully.', 'Updated');
            $('#editIntervalModal').modal('hide');
        } else {
            toastr.error(data.message || 'No change detected or record not found.');
        }
    })
    .catch(() => {
        posLoaderHide();
        btn.disabled = false;
        toastr.error('Could not update. Check your connection.');
    });
}

/* ══════════════════════════════════════════════════════════════
   INTERVAL SALES — Delete trigger (show confirmation)
══════════════════════════════════════════════════════════════ */
function triggerIvDelete() {
    document.getElementById('ivDeleteSlotLabel').textContent = currentIvSlot;
    $('#editIntervalModal').modal('hide');
    setTimeout(() => $('#ivDeleteConfirmModal').modal('show'), 300);
}

/* ══════════════════════════════════════════════════════════════
   INTERVAL SALES — Delete execute
   No page reload. Removes the row from view table, recalcs total,
   and adds the slot back to the Add Interval dropdown.
══════════════════════════════════════════════════════════════ */
function executeDeleteInterval() {
    posLoaderShow();

    const ivId = currentIvId;

    // Grab the interval_id and slot from the view row before we delete it
    const viewRow = document.getElementById('ivrow_' + ivId);
    const intervalId = viewRow ? viewRow.dataset.intervalId : null;
    const slotLabel  = viewRow ? (viewRow.dataset.slot || currentIvSlot) : currentIvSlot;

    fetch('/{{ request()->route("tenantName") }}/sales/retail/delete-interval-sale', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: new URLSearchParams({
            _token: csrfToken(),
            id:     ivId,
            branch: document.getElementById('posBranchId').value,
            date:   document.getElementById('posDate').value,
        }),
    })
    .then(res => res.json().then(data => ({ status: res.status, data })))
    .then(({ status, data }) => {
        posLoaderHide();
        if (status === 200 && data.status === 'success') {
            if (viewRow) viewRow.remove();
            recalcIntervalGrandTotal();
            addSlotBackToDropdown(intervalId, slotLabel);
            refreshPaymentSummaryPane();
            toastr.success('Interval deleted successfully.', 'Deleted');
        } else {
            toastr.error(data.message || 'Could not delete — record not found.');
        }
    })
    .catch(() => {
        posLoaderHide();
        toastr.error('Could not delete. Check your connection.');
    });
}

/* ══════════════════════════════════════════════════════════════
   VIEW INTERVAL MODAL — icon toggle (intervals ↔ payments)
══════════════════════════════════════════════════════════════ */
function toggleIntervalView() {
    const ivPane  = document.getElementById('iv-intervals-pane');
    const pmPane  = document.getElementById('iv-payments-pane');
    const icon    = document.getElementById('ivToggleIcon');
    const btn     = document.getElementById('ivToggleBtn');
    const showInt = pmPane.style.display === 'none';
    if (showInt) {
        ivPane.style.display = 'none'; pmPane.style.display = '';
        icon.className = 'ri-time-line'; btn.title = 'View intervals';
        // Refresh on open so the payment pane is never stale, even if
        // something changed in another tab/device.
        refreshPaymentSummaryPane();
    } else {
        ivPane.style.display = ''; pmPane.style.display = 'none';
        icon.className = 'ri-bank-card-line'; btn.title = 'View payment breakdown';
    }
}

function resetIntervalView() {
    document.getElementById('iv-intervals-pane').style.display = '';
    document.getElementById('iv-payments-pane').style.display  = 'none';
    document.getElementById('ivToggleIcon').className = 'ri-bank-card-line';
    document.getElementById('ivToggleBtn').title      = 'View payment breakdown';
}

/* ══════════════════════════════════════════════════════════════
   CALCULATOR
══════════════════════════════════════════════════════════════ */
let calcExpr = '';

function calcFormatDisplay(str) {
    if (!str || str === '-') return str || '';
    let neg = str.charAt(0) === '-'; if (neg) str = str.slice(1);
    const parts = str.split('.');
    let intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    let result = (neg ? '-' : '') + (intPart || '0');
    if (parts.length > 1) result += '.' + parts[1];
    return result;
}
function calcFormatExpr(expr) { return expr.replace(/\d+\.?\d*/g, m => calcFormatDisplay(m)); }
function calcRender() {
    document.getElementById('calcExpression').textContent = calcFormatExpr(calcExpr);
    const lastVal = calcExpr.split(/[+\-*/]/).pop();
    document.getElementById('calcDisplay').textContent = lastVal === '' ? '0' : calcFormatDisplay(lastVal);
}
function calcDigit(d) {
    if (d === '.') { const lv = calcExpr.split(/[+\-*/]/).pop(); if (lv.includes('.')) return; }
    calcExpr += d; calcRender();
}
function calcOp(op) {
    if (!calcExpr) return;
    const lc = calcExpr.slice(-1);
    calcExpr = '+-*/'.includes(lc) ? calcExpr.slice(0,-1) + op : calcExpr + op;
    calcRender();
}
function calcClear()     { calcExpr = ''; calcRender(); }
function calcBackspace() { calcExpr = calcExpr.slice(0,-1); calcRender(); }
function calcPercent() {
    const parts = calcExpr.split(/([+\-*/])/);
    const last  = parts.pop();
    if (last) { parts.push((parseFloat(last) / 100).toString()); calcExpr = parts.join(''); calcRender(); }
}
function calcEquals() {
    if (!calcExpr) return;
    try {
        const safe   = calcExpr.replace(/[^0-9+\-*/.()]/g, '');
        const result = Function('"use strict";return (' + safe + ')')();
        calcExpr = (Math.round(result * 100000) / 100000).toString();
        calcRender();
    } catch (e) { document.getElementById('calcDisplay').textContent = 'Error'; }
}

/* ══════════════════════════════════════════════════════════════
   UTILITIES
══════════════════════════════════════════════════════════════ */
function posLoaderShow() { $('#progressBar').show(); }
function posLoaderHide() { $('#progressBar').hide(); }

function refreshTransId() {
    const chars  = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const prefix = document.getElementById('posDate').value.replace(/-/g,'');
    let rand = '';
    for (let i = 0; i < 6; i++) rand += chars[Math.floor(Math.random() * chars.length)];
    document.getElementById('posTransId').value = prefix + rand;
}

/* ══════════════════════════════════════════════════════════════
   BUTTON BINDINGS
══════════════════════════════════════════════════════════════ */
document.getElementById('posUploadBtn').addEventListener('click', function (e) {
    e.preventDefault(); renderPendingModal(); $('#pendingModal').modal('show');
});
document.getElementById('posCalcBtn').addEventListener('click', function (e) {
    e.preventDefault(); calcExpr = ''; calcRender(); $('#calculatorModal').modal('show');
});
document.getElementById('posRecentBtn').addEventListener('click', function (e) {
    e.preventDefault(); $('#recentModal').modal('show');
});
document.getElementById('posViewIntervalBtn').addEventListener('click', function (e) {
    e.preventDefault(); resetIntervalView(); $('#viewIntervalModal').modal('show');
});
document.getElementById('posIntervalBtn').addEventListener('click', function (e) {
    e.preventDefault(); $('#intervalModal').modal('show');
    setTimeout(() => {
        const inp = document.getElementById('intervalSalesInput');
        if (inp) inp.focus();
    }, 400);
});
</script>
@endsection