@extends('sales.retail.dashboard')
@section('content')

@php
    use Carbon\Carbon;

    $today       = Carbon::today()->toDateString();
    $displayDate = Carbon::createFromFormat('Y-m-d', $today)->format('d M Y');
    $dateString  = preg_replace('/-/', '', $today);

    $branchId   = Auth::user()->branch;
    $userId     = Auth::id();
    $branchRow  = DB::connection('tenant')->table('branches')->where('id', $branchId)->first();
    $branchName = $branchRow->name ?? '';
    $branchAddress = trim(collect([$branchRow->address ?? null, $branchRow->city ?? null])
        ->filter()
        ->implode(', '));
    $branchPhone = $branchRow->phone ?? '';

    // ── Branch-level admin settings (set from the tenant admin side) ──
    $branchSalesSettings = DB::connection('tenant')
        ->table('branch_sales_settings')
        ->where('branch_id', $branchId)
        ->first();

    $adminAutoUploadEnabled   = (bool) ($branchSalesSettings->auto_upload_cloud_sales ?? false);
    $adminAutoUploadInterval  = $branchSalesSettings->auto_upload_cloud_sales_interval_minutes ?? null;
    $adminAllowClearCloud     = (bool) ($branchSalesSettings->allow_to_clear_cloud_sales ?? false);
    $adminAutoRefreshEnabled  = (bool) ($branchSalesSettings->auto_refresh_page ?? false);
    $adminAutoRefreshInterval = $branchSalesSettings->auto_refresh_interval_minutes ?? 240;

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
        ['id' => 'cash',   'label' => 'Cash',        'icon' => 'ri-money-dollar-box-line'],
        ['id' => 'airtel', 'label' => 'Airtel Money', 'icon' => 'ri-phone-line'],
        ['id' => 'mpamba', 'label' => 'Mpamba',       'icon' => 'ri-phone-line'],
        ['id' => 'bank',   'label' => 'Bank',         'icon' => 'ri-bank-line'],
    ];

    $recentSales = DB::connection('tenant')
        ->table('retail_system_sales')
        ->where('branch', $branchId)
        ->where('date', $today)
        ->orderByDesc('id')
        ->limit(30)
        ->get();

    $allIntervals = DB::connection('tenant')
        ->table('retail_intervals')
        ->orderBy('sort_order')
        ->get();

    $todaysIntervalSales = DB::connection('tenant')
        ->table('retail_interval_sales as ris')
        ->join('retail_intervals as ri', 'ri.id', '=', 'ris.interval_id')
        ->leftJoin('users as u', 'u.id', '=', 'ris.user_id')
        ->where('ris.branch_id', $branchId)
        ->where('ris.date', $today)
        ->orderBy('ri.sort_order')
        ->select('ris.*', 'ri.slot', 'ri.sort_order', 'u.name as user_name')
        ->get();

    $enteredIntervalIds = $todaysIntervalSales->pluck('interval_id')->toArray();
    $remainingIntervals = $allIntervals->whereNotIn('id', $enteredIntervalIds)->values();

    $nextInterval     = $remainingIntervals->first();
    $nextIntervalSlot = $nextInterval?->slot;

    $intervalTotal = $todaysIntervalSales->sum('sales');

    $todaysPaymentSummary = DB::connection('tenant')
        ->table('retail_system_sales')
        ->where('branch', (string) (int) $branchId)
        ->where('date', $today)
        ->select('payment_method', DB::raw('SUM(quantity * price) as total'))
        ->groupBy('payment_method')
        ->pluck('total', 'payment_method');

    $physicalCashToday = (float) (DB::connection('tenant')
        ->table('retail_physical_cash')
        ->where('branch_id', $branchId)
        ->where('date', $today)
        ->value('amount') ?? 0);
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
/* ══════════════════════════════════════════════════════════════
   POS — Silver + Netacube brand gradient (#4B5EBD → #576CC0)
══════════════════════════════════════════════════════════════ */

/* ══ Single-tab lock overlay ══════════════════════════════════ */
#posTabLockOverlay {
    position: fixed; inset: 0; z-index: 99999;
    background: silver;
    display: none;
    align-items: center; justify-content: center;
    padding: 20px;
}
#posTabLockBox {
    background: #fff; border: 2px solid #0d6efd; border-radius: 10px;
    max-width: 380px; width: 100%; padding: 28px 24px; text-align: center;
    box-shadow: 0 4px 14px rgba(0,0,0,.2);
}
#posTabLockBox i { font-size: 46px; color: #0d6efd; }
#posTabLockBox h4 { margin: 10px 0 6px; color: #333333; font-weight: 700; }
#posTabLockBox p { color: #595959; font-size: 13px; margin-bottom: 16px; }
#posTabLockUseHereBtn {
    background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff; border: 2px solid silver; border-radius: 4px;
    height: 40px; padding: 0 18px; font-weight: bold; font-size: 14px; cursor: pointer;
}
#posTabLockUseHereBtn:hover { background: linear-gradient(to right, #44539f, #4d5fab); }
.posTabLockHint { margin: 10px 0 0; font-size: 11px; color: #8a8a8a; }

/* ══ Superseded-tab overlay ══════════════════════════════════ */
#posSupersededOverlay {
    position: fixed; inset: 0; z-index: 99997;
    background: silver;
    display: none;
    align-items: center; justify-content: center;
    padding: 20px;
}
#posSupersededBox {
    background: #fff; border: 2px solid #6c757d; border-radius: 10px;
    max-width: 380px; width: 100%; padding: 28px 24px; text-align: center;
    box-shadow: 0 4px 14px rgba(0,0,0,.2);
}
#posSupersededBox i { font-size: 46px; color: #6c757d; }
#posSupersededBox h4 { margin: 10px 0 6px; color: #333333; font-weight: 700; }
#posSupersededBox p { color: #595959; font-size: 13px; margin-bottom: 0; }

/* ══ Top margin — card must not touch the navbar ══════════════ */
.content-page > .content > .container-fluid {
    padding-top: 16px;
}

/* ══ POS Card ═════════════════════════════════════════════════ */
.pos-card {
    border: none;
    box-shadow: none;
    border-radius: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    background-color: transparent;
}

/* — Header bar — */
.pos-card-header {
    padding: 4px 10px !important;
    background-color: silver;
    color: #666666;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex: 0 0 auto;
}
.pos-card-body {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
    padding: 0 !important;
    overflow: hidden;
}

/* — Date/refresh — */
#posDateBtn {
    height: 28px; padding: 0 4px; border-radius: 0;
    background: none; color: #666666; border: none;
    font-weight: bold; font-size: 14px;
    display: inline-flex; align-items: center; gap: 6px; cursor: pointer;
}
#posDateBtn:hover { color: #333333; }

/* — Header icon buttons — */
.pos-hdr-btn {
    height: 24px; width: 24px; border: none; background: none;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 0; color: #666666; font-size: 16px;
    cursor: pointer; position: relative; padding: 1px;
}
.pos-hdr-btn:hover { color: #333333; }

/* — Pending badge — */
.pos-badge {
    background: #dc2626; color: #fff;
    font-size: 10px; font-weight: 700;
    min-width: 16px; height: 16px; border-radius: 8px;
    display: none; align-items: center; justify-content: center;
    padding: 0 4px;
    position: absolute; top: -3px; right: -5px;
    border: 1px solid #fff;
}
.pos-badge.show { display: inline-flex; }

/* ══ Workspace row ══════════════════════════════════════════════ */
#pos-workspace-row {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: row;
    overflow: hidden;
    height: 100%;
}

/* — Product column — */
#pos-left-col {
    background-color: transparent;
    display: flex;
    flex-direction: column;
    flex: 0 0 41.6667%;
    max-width: 41.6667%;
    min-width: 0;
    min-height: 0;
    /* FIX 1: border-right removed entirely — no vertical line next to search results */
    border-right: none;
    overflow: hidden;
}
/* FIX 1: .has-products no longer adds a border-right — rule removed */

#pos-search-row {
    background: linear-gradient(to right, #4B5EBD, #576CC0);
    padding: 8px;
    flex: 0 0 auto;
    box-sizing: border-box;
    width: 100%;
}
#pos-search-wrap { position: relative; }
#pos-search-wrap i {
    position: absolute; left: 10px; top: 50%;
    transform: translateY(-50%);
    color: #595959; font-size: 16px; pointer-events: none;
}
#pos-search {
    background-color: silver;
    text-transform: uppercase; font-weight: bold;
    border: 1px solid rgba(255,255,255,.35);
    width: 100%; height: 36px;
    border-radius: 4px; padding: 0 10px 0 32px;
    outline: none;
    color: #1a1a1a;
    box-sizing: border-box;
}
#pos-search::placeholder { color: #595959; font-weight: bold; text-transform: none; }
#pos-search:focus { background-color: #d9d9d9; border-color: rgba(255,255,255,.65); outline: none; box-shadow: none; }

/* ── Product display area ─────────────────────────────────────
   Empty: zero height, fully invisible — no background, no border.
   Has-results: grows to fill remaining column space and scrolls.
─────────────────────────────────────────────────────────────── */
#pos-product-display {
    flex: 0 0 0px;
    height: 0;
    min-height: 0;
    max-height: 0;
    overflow: hidden;
    background: transparent;
    border: none;
}
#pos-product-display.has-results {
    flex: 1 1 auto;
    height: auto;
    min-height: 0;
    max-height: none;
    overflow-y: auto;
    background: transparent;
}

/* ── Product rows ──────────────────────────────────────────────*/
.prd-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 6px 8px;
    background-color: #cccccc;
    border-bottom: 1px solid #a8a8a8;
    border-left: 1px solid #b8b8b8;
    border-right: 1px solid #b8b8b8;
}
.prd-row:first-child { border-top: 1px solid #a8a8a8; }
.prd-row .prd-link {
    color: black; text-decoration: none; cursor: pointer;
    flex: 0 0 70%; max-width: 70%; min-width: 0; overflow: hidden;
}
.prd-row.prd-oos .prd-link { opacity: .5; cursor: not-allowed; }
.prd-name { text-transform: uppercase; font-weight: bold; font-size: 14px; }
.prd-code { color: #8a8a8a; font-weight: 600; font-size: 13px; font-family: monospace; margin-left: 4px; }
.prd-code .val { color: #c0392b; }
.prd-meta { color: gray; font-family: monospace; font-size: 13px; margin-left: 6px; }
.prd-stock-tag { color: #8a8a8a; font-weight: 600; font-size: 16px; font-family: monospace; margin-left: 6px; }
.prd-stock-tag .val { color: #c0392b; }
.prd-qty-input {
    text-align: center; flex: 0 0 28%; max-width: 28%;
    border-radius: 5px; border: 1px ridge #b3b3b3;
    background: transparent; font-size: 15px; font-weight: bold; color: #1a1a1a;
    height: 36px; margin-left: 8px; margin-right: 6px;
}
.prd-qty-input:focus { outline: 1px solid #0d6efd; background: transparent; }

/* — Cart column — */
#pos-right-col {
    flex: 0 0 58.3333%;
    max-width: 58.3333%;
    min-width: 0;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    /* no background — transparent so no phantom shape */
    background: transparent;
}
#pos-cart-bar {
    background: linear-gradient(to right, #4B5EBD, #576CC0);
    padding: 6px 8px;
    display: flex; align-items: center; justify-content: space-between;
    flex: 0 0 auto;
}
.pos-cart-label {
    border: 2px solid silver; font-weight: bold; color: silver; background: transparent;
    padding: 3px 8px; font-size: 14px; display: inline-flex; align-items: center; gap: 0;
}
.pos-cart-label .cart-icon { color: silver; font-size: 16px; }
.pos-cart-label .cart-pipe { color: rgba(255,255,255,.4); margin: 0 7px; font-size: 16px; line-height: 1; }
.pos-cart-label .cart-currency { font-size: 11px; font-weight: 700; color: rgba(255,255,255,.7); letter-spacing: .5px; margin-right: 2px; }
#cartTotalPill { color: #f2f2f2; font-weight: bold; font-size: 17px; }

#pos-checkout-btn {
    border: 2px solid silver; background: transparent; color: silver; font-weight: bold;
    width: 80px; height: 32px; display: flex; align-items: center; justify-content: center;
    font-size: 16px; cursor: pointer; border-radius: 3px;
}
#pos-checkout-btn:disabled { opacity: .45; cursor: not-allowed; }

/* ── Cart table wrap ─────────────────────────────────────────── */
#pos-cart-table-wrap {
    flex: 1 1 auto;
    min-height: 0;
    /* transparent — the silver comes only from the table rows themselves */
    background-color: transparent;
    overflow-y: auto;
    overflow-x: auto;
    padding-bottom: 0;
}
#pos-cart-table {
    width: 100%; font-size: 11px; border-collapse: collapse;
    background-color: transparent;
    border-left: 1px solid #999999;
    border-right: 1px solid #999999;
    border-bottom: 1px solid #999999;
}
/* When cart is empty the table borders would draw a visible rectangle —
   hide them so the area is truly blank */
#pos-cart-table.pos-cart-empty {
    border-left: none;
    border-right: none;
    border-bottom: none;
}
#pos-cart-table thead th {
    color: #3d5c5c; border-bottom: 2px solid #a6a6a6; border-top: 1px solid #a6a6a6;
    padding: 6px 4px; text-align: center;
    position: sticky; top: 0; background-color: silver; z-index: 2;
}
#pos-cart-table thead th:first-child { text-align: left; padding-left: 6px; }
#pos-cart-table thead th.pcr-qty-col,
#pos-cart-table tbody td.pcr-qty-col { min-width: 64px; }
#pos-cart-table tbody td {
    border-bottom: 1px solid #b3b3b3; padding: 6px 4px;
    text-align: center; color: black; background-color: silver;
}
#pos-cart-table tbody td:first-child { text-align: left; padding-left: 6px; }
/* FIX 3: full product name — wraps onto a second line only once the
   Item cell's full available width is used; no truncation/ellipsis. */
.pcr-name {
    display: block;
    white-space: normal;
    word-break: break-word;
    overflow-wrap: break-word;
}
.pcr-qinput {
    width: 56px; height: 24px; text-align: center; border: none; background: transparent;
    font-size: 12px; font-weight: bold; color: #1d4ed8; outline: none;
}
.pcr-qinput:focus { outline: 1px solid #0d6efd; background: transparent; }
.pcr-remove { color: red; cursor: pointer; font-weight: bold; text-decoration: none; }

/* Empty row — transparent, no border, merges into the page background */
#pos-cart-empty-row td#pos-cart-empty {
    text-align: center; color: #595959; font-size: 13px;
    background-color: silver;
    padding: 22px 8px;
    border-bottom: none;
}

/* ══ Modals — blue header ══════════════════════════════════════ */
.mh-pos {
    background: linear-gradient(to right, #4B5EBD, #576CC0);
    padding: 10px 16px !important; border-bottom: none;
}
.mh-pos-title { color: #fff; font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
.mh-close-w { filter: brightness(0) invert(1); opacity: .8; }
.mh-close-w:hover { opacity: 1; }

/* ══ Silver modal header (touches the blue bar directly below, no gap) ══ */
.mh-pos-silver {
    background-color: silver;
    padding: 10px 16px !important; border-bottom: none;
}
.mh-pos-title-silver { color: #333333; font-size: 15px; font-weight: 700; display: flex; align-items: center; gap: 6px; }

.mh-white {
    background-color: #fff; padding: 10px 16px !important;
    border-bottom: 1px solid #e3e3e3;
    display: flex; align-items: center; justify-content: space-between; width: 100%;
}
.mh-white .mh-iv-branch { color: #1e293b; }
.mh-white .mh-iv-date   { color: #6c757d; }
.mh-white .mh-icon-btn  { color: #0d6efd; }

/* ══ CHECKOUT ═══════════════════════════════════════════════════ */
.checkout-total-row {
    display: flex; justify-content: space-between; align-items: center;
    background: #e6e6e6; border: 1px solid #a6a6a6;
    padding: 10px 14px; margin-bottom: 14px;
}
.checkout-total-label { font-size: 13px; font-weight: 600; color: #333; }
.checkout-total-value { font-size: 20px; font-weight: 800; color: #1e293b; }

.pm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px; }
.pm-card { border: 1.5px solid #a6a6a6; padding: 10px 12px; cursor: pointer; display: flex; align-items: center; gap: 8px; user-select: none; background: #f2f2f2; }
.pm-card:hover { border-color: #4B5EBD; }
.pm-card.active { border-color: #4B5EBD; background: #e6e6e6; }
.pm-card i { font-size: 20px; color: #4B5EBD; }
.pm-card .pm-label { font-size: 12px; font-weight: 600; color: #1e293b; }
.checkout-amount-wrap { margin-bottom: 12px; }
.checkout-amount-wrap label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6c757d; display: block; margin-bottom: 5px; }
.checkout-amount-hint { font-size: 11px; color: #8a8a8a; margin-top: 4px; }
.checkout-amount-input { width: 100%; height: 44px; border: 1px solid #a6a6a6; border-radius: 6px; font-size: 18px; font-weight: 700; padding: 0 14px; color: #1e293b; outline: none; background: transparent; }
.checkout-amount-input:focus { border-color: #4B5EBD; }
#checkout-change-row { background: #e6f5ea; border: 1px solid #a6a6a6; padding: 8px 12px; display: none; justify-content: space-between; align-items: center; margin-bottom: 14px; }
#checkout-change-row.show { display: flex; }
#checkout-change-row.negative { background: #fbe6e6; }
#checkout-change-label { font-size: 12px; font-weight: 600; color: #065f46; }
#checkout-change-row.negative #checkout-change-label { color: #7f1d1d; }
#checkout-change-value { font-size: 16px; font-weight: 800; color: #16a34a; }
#checkout-change-row.negative #checkout-change-value { color: #dc2626; }
#confirmSaleBtn {
    width: 100%; height: 44px; border: 2px solid #4B5EBD; border-radius: 6px;
    background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff; font-size: 14px; font-weight: 700;
    cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
}
#confirmSaleBtn:disabled { opacity: .5; cursor: not-allowed; }

/* — Interval modals — */
.mh-icon-btn { color: #fff; opacity: .85; font-size: 20px; cursor: pointer; display: inline-flex; }
.mh-icon-btn:hover { opacity: 1; }
.mh-iv-title { display: flex; flex-direction: column; line-height: 1.35; }
.mh-iv-branch { color: #fff; font-size: 15px; font-weight: 700; }
.mh-iv-date { color: rgba(255,255,255,.85); font-size: 13px; font-weight: 500; }
.iv-slot-text { color: #1e293b; font-weight: 600; font-size: 13px; }
.iv-amount-link { color: #1e293b; font-weight: 700; text-decoration: none; cursor: pointer; }
.iv-amount-link:hover { color: #000; }
#ivGrandTotal { font-weight: 800 !important; color: #1e293b; }
.iv-total-row {
    display: flex; justify-content: space-between; align-items: center;
    background: #e6e6e6; border: 1px solid silver; border-radius: 6px;
    padding: 8px 12px; margin-top: 10px; font-weight: 700; font-size: 13px; color: #1e293b;
}
.pay-summary-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6c757d; margin: 16px 0 8px; }
.pay-summary-row { display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; border: 1px solid #e3e3e3; border-radius: 6px; margin-bottom: 6px; background: #fafafa; }
.pay-summary-row .psr-label { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #1e293b; }
.pay-summary-row .psr-label i { color: #0d6efd; font-size: 16px; }
.pay-summary-row .psr-value { font-size: 13px; font-weight: 700; color: #1e293b; }
.edit-iv-field { width: 100%; border: 1px solid #dee2e6; border-radius: 6px; padding: 8px 12px; font-size: 14px; color: #1e293b; outline: none; }
.edit-iv-field:focus { border-color: #0d6efd; box-shadow: 0 0 0 2px rgba(13,110,253,.15); }
.edit-iv-field:disabled { background: #f8f9fa; color: #6c757d; cursor: default; }
.edit-iv-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #6c757d; display: block; margin-bottom: 5px; }
#ivDeleteConfirmModal .modal-body { text-align: center; padding-bottom: 28px; }

/* — Pending modal — */
#pendingListWrap { max-height: 60vh; overflow: auto; }
#pendingTable { min-width: 400px; }
#pendingTable thead th { position: sticky; top: 0; background: silver; z-index: 1; }
#pendingModal .modal-footer { display: flex; justify-content: space-between; align-items: center; }
#clearCloudDataBtn {
    background: #fff; color: #dc2626; border: 1.5px solid #dc2626; border-radius: 4px;
    height: 32px; padding: 0 12px; font-size: 12px; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
}
#clearCloudDataBtn:hover { background: #fee2e2; }

/* — Sales Data modal tabs (Cloud Data / Recently Sold) — */
.sd-tabs { display: flex; background: silver; border-bottom: 1px solid #8a8a8a; }
.sd-tab {
    flex: 1 1 0; border: none; background: transparent; color: #595959; font-weight: 700;
    font-size: 13px; padding: 10px 8px; cursor: pointer; display: flex; align-items: center;
    justify-content: center; gap: 6px; border-bottom: 3px solid transparent;
}
.sd-tab.active { color: #1e2a5e; border-bottom-color: #1e2a5e; }
.sd-pane-subbar {
    padding: 8px 12px; background: #f2f2f2; border-bottom: 1px solid #d9d9d9;
    font-size: 12px; color: #595959; display: flex; align-items: center;
    justify-content: space-between; gap: 10px; flex-wrap: wrap;
}
.sd-pane-subbar-note { font-size: 11px; color: #8a8a8a; font-style: italic; }
#sdRecentListWrap { max-height: 60vh; overflow-x: auto; overflow-y: auto; }
#sdFailedListWrap { max-height: 60vh; overflow: auto; }
#sdFailedTable { min-width: 480px; }
#sdFailedTable thead th { position: sticky; top: 0; background: silver; z-index: 1; }
#sdFailedTable td.sd-failed-reason { font-size: 11px; color: #7f1d1d; }
.sd-tab-badge {
    background: #dc2626; color: #fff; font-size: 10px; font-weight: 700;
    min-width: 16px; height: 16px; border-radius: 8px;
    display: inline-flex; align-items: center; justify-content: center;
    padding: 0 4px; margin-left: 2px;
}
.sd-failed-del-btn {
    background: none; border: none; color: #dc2626; cursor: pointer;
    font-size: 15px; padding: 2px 6px;
}
.sd-failed-del-btn:hover { color: #7f1d1d; }
#clearFailedDataBtn {
    background: #fff; color: #dc2626; border: 1.5px solid #dc2626; border-radius: 4px;
    height: 32px; padding: 0 12px; font-size: 12px; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
}
#clearFailedDataBtn:hover { background: #fee2e2; }
#downloadFailedExcelBtn {
    background: #fff; color: #1e7a34; border: 1.5px solid #1e7a34; border-radius: 4px;
    height: 32px; padding: 0 12px; font-size: 12px; font-weight: 700; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
}
#downloadFailedExcelBtn:hover { background: #e8f6ec; }
#downloadFailedExcelBtn:disabled,
#uploadFailedSalesBtn:disabled,
#clearFailedDataBtn:disabled { opacity: .55; cursor: not-allowed; }

/* — Clear Cloud Data warning / captcha modal — */
#clearCloudWarnModal .modal-body { text-align: center; padding: 24px 22px 8px; }
#clearCloudWarnModal i.warn-icon { font-size: 56px; color: #dc2626; }
#clearCloudWarnModal h5 { color: #7f1d1d; font-weight: 800; margin: 10px 0 6px; }
#clearCloudWarnModal p { font-size: 13px; color: #595959; }
#clearCloudWarnModal .ccw-backup-note { font-size: 12px; color: #1e293b; background: #f2f2f2; border: 1px solid #ddd; border-radius: 6px; padding: 8px 10px; margin: 10px 0 16px; text-align: left; }
#ccwCaptchaQuestion { font-size: 20px; font-weight: 800; color: #1e293b; margin: 6px 0 10px; }
#ccwCaptchaInput { width: 120px; height: 42px; text-align: center; font-size: 18px; font-weight: 700; border: 1.5px solid #a6a6a6; border-radius: 6px; outline: none; margin-bottom: 6px; }
#ccwCaptchaInput:focus { border-color: #dc2626; }
#ccwCaptchaError { color: #dc2626; font-size: 12px; min-height: 16px; margin-bottom: 4px; }

/* ══ CALCULATOR ════════════════════════════════════════════════ */
.calc-screen {
    background: #1e2550;
    padding: 16px 18px 14px;
    border-bottom: 2px solid silver;
}
#calcExpression { color: #7a8bbf; font-size: 13px; min-height: 18px; text-align: right; font-family: monospace; }
#calcDisplay { color: #f0f4ff; font-size: 42px; font-weight: 700; text-align: right; font-family: monospace; word-break: break-all; line-height: 1.1; margin-top: 4px; }
.calc-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 1px; background: silver; }
.calc-btn { border: none; background: #2d3b6e; color: #e8ecff; font-size: 21px; font-weight: 600; padding: 21px 0; cursor: pointer; transition: background .12s; }
.calc-btn:hover { background: #3a4a80; }
.calc-fn { background: silver; color: #333333; font-size: 16px; }
.calc-fn:hover { background: #c2c2c2; }
.calc-op { background: linear-gradient(to bottom, #4B5EBD, #3d4fa0); color: #fff; }
.calc-op:hover { background: linear-gradient(to bottom, #576CC0, #4B5EBD); }
.calc-zero { grid-column: span 2; }
.calc-eq { background: linear-gradient(to bottom, #576CC0, #4B5EBD); color: #fff; border: 1px solid silver; }
.calc-eq:hover { background: linear-gradient(to bottom, #4B5EBD, #3d4fa0); }

/* ══ SETTINGS ══════════════════════════════════════════════════ */
.pos-setting-row {
    display: flex; align-items: center; justify-content: space-between; gap: 14px;
    padding: 12px 0; border-bottom: 1px solid #eee;
}
.pos-setting-row:last-child { border-bottom: none; }
.pos-setting-title { font-size: 13px; font-weight: 700; color: #1e293b; }
.pos-setting-desc { font-size: 11.5px; color: #6c757d; margin-top: 3px; line-height: 1.4; }
.pos-switch { position: relative; display: inline-block; width: 44px; height: 24px; flex: 0 0 auto; }
.pos-switch input { opacity: 0; width: 0; height: 0; }
.pos-switch-slider {
    position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
    background-color: #c7c7c7; transition: .2s; border-radius: 24px;
}
.pos-switch-slider:before {
    position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px;
    background-color: #fff; transition: .2s; border-radius: 50%;
}
.pos-switch input:checked + .pos-switch-slider { background: linear-gradient(to right, #4B5EBD, #576CC0); }
.pos-switch input:checked + .pos-switch-slider:before { transform: translateX(20px); }
.pos-setting-row.pos-setting-disabled { opacity: .8; }
.pos-switch-disabled { cursor: not-allowed; }
.pos-switch-disabled .pos-switch-slider { cursor: not-allowed; }
.pos-setting-admin-note {
    display: inline-block; font-size: 10.5px; font-weight: 700; color: #92600a;
    background: #fff7e0; border: 1px solid #f5dd9c; border-radius: 10px;
    padding: 2px 8px; margin-top: 6px;
}
.pos-setting-admin-note-active {
    display: inline-flex; align-items: center; gap: 4px; font-size: 10.5px; font-weight: 700; color: #14532d;
    background: #e7f8ec; border: 1px solid #b7e4c7; border-radius: 10px;
    padding: 2px 8px; margin-top: 6px;
}
.pos-autorefresh-countdown {
    font-size: 12.5px; font-weight: 700; color: #344ba0; background: #eef0fb;
    border: 1px solid #d7dbf5; border-radius: 6px; padding: 8px 10px; text-align: center;
}

/* ══ BYPASS POS ════════════════════════════════════════════════ */
#bypass-pos-search-row { background: linear-gradient(to right, #4B5EBD, #576CC0); padding: 8px; }
#bypass-pos-search-wrap { position: relative; }
#bypass-pos-search-wrap i {
    position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
    color: #595959; font-size: 16px; pointer-events: none;
}
#bypass-pos-search {
    background-color: silver; text-transform: uppercase; font-weight: bold;
    border: 1px solid rgba(255,255,255,.35); width: 100%; height: 36px;
    border-radius: 4px; padding: 0 10px 0 32px; outline: none; color: #1a1a1a; box-sizing: border-box;
}
#bypass-pos-search::placeholder { color: #595959; font-weight: bold; text-transform: none; }
#bypass-pos-search:focus { background-color: #d9d9d9; border-color: rgba(255,255,255,.65); outline: none; box-shadow: none; }
#bypass-pos-product-display { max-height: 0; overflow: hidden; background: transparent; transition: max-height .1s; }
#bypass-pos-product-display.has-results { max-height: 34vh; overflow-y: auto; }
#bypass-pos-cart-bar {
    background: linear-gradient(to right, #4B5EBD, #576CC0); padding: 6px 8px;
    display: flex; align-items: center; justify-content: space-between;
}
#bypass-pos-cart-table-wrap { max-height: 24vh; overflow-y: auto; }
#bypass-pos-cart-table { width: 100%; font-size: 11px; border-collapse: collapse; background-color: transparent; }
#bypass-pos-cart-table.pos-cart-empty { border: none; }
#bypass-pos-cart-table thead th {
    color: #3d5c5c; border-bottom: 2px solid #a6a6a6; border-top: 1px solid #a6a6a6;
    padding: 6px 4px; text-align: center; position: sticky; top: 0; background-color: silver; z-index: 2;
}
#bypass-pos-cart-table thead th:first-child { text-align: left; padding-left: 6px; }
#bypass-pos-cart-table thead th.pcr-qty-col,
#bypass-pos-cart-table tbody td.pcr-qty-col { min-width: 64px; }
#bypass-pos-cart-table tbody td {
    border-bottom: 1px solid #b3b3b3; padding: 6px 4px; text-align: center; color: black; background-color: silver;
}
#bypass-pos-cart-table tbody td:first-child { text-align: left; padding-left: 6px; }
#bypass-pos-cart-empty-row td#bypass-pos-cart-empty {
    text-align: center; color: #595959; font-size: 13px; background-color: silver; padding: 18px 8px; border-bottom: none;
}
#bypass-checkout-change-row {
    background: #e6f5ea; border: 1px solid #a6a6a6; padding: 8px 12px;
    display: none; justify-content: space-between; align-items: center;
}
#bypass-checkout-change-row.show { display: flex; }
#bypass-checkout-change-row.negative { background: #fbe6e6; }
#bypass-checkout-change-label { font-size: 12px; font-weight: 600; color: #065f46; }
#bypass-checkout-change-row.negative #bypass-checkout-change-label { color: #7f1d1d; }
#bypass-checkout-change-value { font-size: 16px; font-weight: 800; color: #16a34a; }
#bypass-checkout-change-row.negative #bypass-checkout-change-value { color: #dc2626; }
#bypassConfirmSaleBtn {
    width: 100%; height: 44px; border: 2px solid #4B5EBD; border-radius: 6px;
    background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff; font-size: 14px; font-weight: 700;
    cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
}
#bypassConfirmSaleBtn:disabled { opacity: .5; cursor: not-allowed; }
#bypassClearAllBtn {
    border: 2px solid silver; background: transparent; color: silver; font-weight: bold;
    height: 32px; padding: 0 12px; display: flex; align-items: center; gap: 6px;
    font-size: 13px; cursor: pointer; border-radius: 3px;
}
#bypassClearAllBtn:hover:not(:disabled) { color: #fff; border-color: #fff; }
#bypassClearAllBtn:disabled { opacity: .45; cursor: not-allowed; }
.bypass-section-label {
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
    color: #6c757d; margin: 12px 18px 8px;
}

/* — Scrollbars — */
#pos-product-display::-webkit-scrollbar,
#pos-cart-table-wrap::-webkit-scrollbar { width: 5px; height: 5px; }
#pos-product-display::-webkit-scrollbar-thumb,
#pos-cart-table-wrap::-webkit-scrollbar-thumb { background: #999; }

/* — Spinner arrows removal — */
input[type=number]::-webkit-outer-spin-button,
input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
input[type=number] { -moz-appearance: textfield; }

/* ══════════════════════════════════════════════════════════════
   RECEIPT PRINTING (DESKTOP ONLY) — pure addition, no impact on
   the normal screen layout.

   The receipt is built and printed inside its OWN iframe document
   (see printReceipt()/buildReceiptDoc() in scripts below), never in
   the main page. That means we don't need any @media print /
   visibility-hiding hack on the rest of the app any more — the main
   window is never asked to print anything.

   The frame stays permanently invisible/off-canvas — it never slides
   into view. Modern browsers can print() a hidden iframe without it
   ever being shown, which avoids a second "receipt-looking" panel
   appearing alongside the OS print dialog. The OS print dialog itself
   (size, position, centering) is controlled entirely by the browser —
   no page CSS/JS can resize or move that; it's blocked by every
   browser for security reasons. */
#receiptPrintFrame {
    position: fixed;
    top: 0;
    left: -9999px;
    width: 1px;
    height: 1px;
    border: 0;
    opacity: 0;
    pointer-events: none;
}

/* NOTE: the .rcpt-* typography rules and the @page/print rule used to
   live here, but now that the receipt is printed from inside its own
   iframe document (a separate DOM this stylesheet can't reach), they've
   moved to RECEIPT_CSS in the print-receipt script block below — that's
   the only place they're used, so that's the single source of truth. */


/* ══ MOBILE LAYOUT (≤ 768px) ══════════════════════════════════ */
@media (max-width: 768px) {
    /* On mobile content-page/content are zeroed, so add margin to card itself */
    .pos-card {
        border-radius: 0 !important;
        height: auto !important;
        /* FIX 2: symmetric horizontal margin on mobile so left matches right */
        margin-top: 8px;
        margin-left: 8px;
        margin-right: 8px;
    }

    .content-page { padding: 0 !important; }
    .content      { padding: 0 !important; }

    /* Override the desktop padding-top since content-page is zeroed on mobile */
    .content-page > .content > .container-fluid {
        padding-top: 0 !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    #pos-workspace-row {
        flex-direction: column;
        overflow: visible;
        height: auto;
        flex: 0 0 auto;
    }

    #pos-left-col {
        flex: 0 0 auto;
        width: 100%;
        max-width: 100%;
        max-height: 44vh;
        border-right: none;
        border-bottom: none;
        align-self: auto;
        overflow: hidden;
    }
    #pos-left-col.has-products { border-bottom: 1px solid #adadad; }

    #pos-product-display.has-results { max-height: calc(44vh - 54px); }

    #pos-right-col {
        flex: 0 0 auto;
        width: 100%;
        max-width: 100%;
        min-height: 0;
        /* Clip so nothing bleeds below the last cart row */
        overflow: hidden;
    }

    #pos-cart-table-wrap {
        flex: 0 0 auto;
        overflow-y: auto;
        max-height: 50vh;
        /* Remove padding-bottom that was creating a white gap on mobile */
        padding-bottom: 0;
    }

    #pos-cart-table {
        border-left: none;
        border-right: none;
        border-bottom: none;
    }

    #pos-search-row { padding: 8px; box-sizing: border-box; width: 100%; }
    #pos-search     { width: 100%; box-sizing: border-box; }

    .pos-card-body { overflow: hidden; flex: 0 0 auto; }

    #posUploadBtn, #posIntervalBtn,
    #posCalcBtn, #posViewIntervalBtn,
    #posBypassBtn, #posSettingsBtn { display: inline-flex !important; }
    .pos-hdr-btn { width: 26px; height: 26px; font-size: 17px; }

    .prd-row { padding: 9px 8px; }
    .prd-name { font-size: 15px; }
    .prd-qty-input { height: 40px; }

    .modal-dialog { margin: 1.25rem auto !important; max-width: calc(100% - 24px) !important; }
    .modal-content { border-radius: 10px !important; }

    #pos-cart-table { font-size: 10px; }
    /* FIX 3 (mobile): no max-width override here anymore — name keeps using
       the full Item cell width before wrapping, same as desktop. */
    .pcr-qinput, .prd-qty-input { font-size: 16px; }
}
</style>

{{-- ══ Progress bar ══════════════════════════════════════════════════════ --}}
<div class="progress" id="progressBar" role="progressbar"
     style="height:8px;transform:rotate(180deg);display:none">
    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

{{-- ══ Single-tab lock overlay ═════════════════════════════════════════════ --}}
<div id="posTabLockOverlay">
    <div id="posTabLockBox">
        <i class="ri-error-warning-line"></i>
        <h4>POS is already open</h4>
        <p>This POS page is already open in another tab or window on this device.
           To avoid losing or duplicating sales data, only one tab can be active at a time.</p>
        <button type="button" id="posTabLockUseHereBtn" onclick="posTabLock.takeOver()">
            Use POS in this tab instead
        </button>
        <p class="posTabLockHint">Only do this if the other tab is no longer in use.</p>
    </div>
</div>

{{-- ══ Superseded-tab overlay ══════════════════════════════════════════════ --}}
<div id="posSupersededOverlay">
    <div id="posSupersededBox">
        <i class="ri-arrow-right-circle-line"></i>
        <h4>POS moved to another tab</h4>
        <p>The POS is now active in a different tab on this device.
           This tab is no longer in use — you can close it safely.</p>
    </div>
</div>

{{-- ══ Standard dashboard page wrapper ════════════════════════════════════ --}}
<div class="content-page">
<div class="content">
<div class="container-fluid">

<div class="pos-card" id="posCard">

    {{-- ── Header ─────────────────────────────────────────────────────── --}}
    <div class="pos-card-header">
        <div class="d-flex align-items-center" style="gap:0;">
            <button type="button" class="pos-hdr-btn" id="posSettingsBtn" title="Settings">
                
            <i class="ri-settings-3-line"></i>
        
        </button>
           
           
           
            <button type="button" id="posDateBtn" title="Refresh page"
                    onclick="window.location.href='{{ url()->current() }}'">
                {{ $displayDate }}
            </button>
        </div>
        <div class="d-flex align-items-center" style="gap:2px;">
            <button type="button" class="pos-hdr-btn" id="posUploadBtn" title="Sales data — cloud &amp; recent items">
                <i class="ri-cloud-line"></i>
                <span class="pos-badge" id="posPendingBadge"></span>
            </button>
            <button type="button" class="pos-hdr-btn" id="posCalcBtn"         title="Calculator"><i class="ri-calculator-line"></i></button>
            <button type="button" class="pos-hdr-btn" id="posBypassBtn"       title="Serve another customer (Bypass POS)"><i class="ri-user-add-line"></i></button>
            <button type="button" class="pos-hdr-btn" id="posViewIntervalBtn" title="View interval sales"><i class="ri-eye-line"></i></button>
            <button type="button" class="pos-hdr-btn" id="posIntervalBtn"     title="Add interval sales"><i class="ri-add-circle-line"></i></button>
        </div>
    </div>

    {{-- ── Body ───────────────────────────────────────────────────────── --}}
    <div class="pos-card-body">
        <div id="pos-workspace-row">

            {{-- LEFT — product search & list --}}
            <div id="pos-left-col">
                <div id="pos-search-row">
                    <div id="pos-search-wrap">
                        <i class="ri-smartphone-line"></i>
                        <input type="text" id="pos-search"
                               placeholder="Search product name or code"
                               autocomplete="off"
                               onclick="clearPosSearch()">
                    </div>
                </div>
                <div id="pos-product-display"></div>
                <script type="application/json" id="pos-products-json">{!! json_encode($products->map(function($p){
                    return ['id'=>$p->id,'name'=>$p->name,'code'=>$p->code,'unit'=>$p->unit,
                            'price'=>(float)$p->effective_price,'stock'=>(float)$p->stock_quantity];
                })) !!}</script>
            </div>

            {{-- RIGHT — shopping cart --}}
            <div id="pos-right-col">
                <div id="pos-cart-bar">
                    <div class="pos-cart-label">
                        <i class="ri-receipt-line cart-icon"></i>
                        <span class="cart-pipe">|</span>
                        <span class="cart-currency">MWK</span>
                        <span id="cartTotalPill">0.00</span>
                    </div>
                    <button id="pos-checkout-btn" disabled onclick="openCheckout()">
                        <i class="ri-arrow-right-s-line"></i>
                    </button>
                </div>
                <div id="pos-cart-table-wrap">
                    <table id="pos-cart-table" class="pos-cart-empty">
                        <thead>
                            <tr>
                                <th>Item</th><th>Unit</th><th>Price</th><th class="pcr-qty-col">Qty</th><th>Total</th><th>Del</th>
                            </tr>
                        </thead>
                        <tbody id="pos-cart-tbody">
                            <tr id="pos-cart-empty-row">
                                <td colspan="6" id="pos-cart-empty">No items in cart</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>{{-- /#pos-workspace-row --}}
    </div>{{-- /.pos-card-body --}}
</div>{{-- /.pos-card --}}

</div>{{-- /.container-fluid --}}
</div>{{-- /.content --}}
</div>{{-- /.content-page --}}

{{-- Hidden POS meta --}}
<input type="hidden" id="posTransId"    value="{{ $transId }}">
<input type="hidden" id="posUserId"     value="{{ $userId }}">
<input type="hidden" id="posUserName"   value="{{ Auth::user()->name ?? Auth::id() }}">
<input type="hidden" id="posBranchId"   value="{{ $branchId }}">
<input type="hidden" id="posBranchName" value="{{ $branchName }}">
<input type="hidden" id="posBranchAddress" value="{{ $branchAddress }}">
<input type="hidden" id="posBranchPhone" value="{{ $branchPhone }}">
<input type="hidden" id="posDate"       value="{{ $today }}">
<input type="hidden" id="posDisplayDate" value="{{ $displayDate }}">

{{-- ══ RECEIPT PRINT FRAME (DESKTOP ONLY) ═══════════════════════════════════ --}}
<iframe id="receiptPrintFrame" title="Receipt" aria-hidden="true"></iframe>

{{-- ══ CHECKOUT MODAL ════════════════════════════════════════════════════ --}}
<div class="modal fade" id="checkoutModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog" style="max-width:420px;">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title"><i class="ri-shopping-bag-3-line"></i> Confirm Sale</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:16px 18px;">
                <div class="checkout-total-row">
                    <span class="checkout-total-label">Cart Total</span>
                    <span class="checkout-total-value">MWK <span id="checkoutTotalDisplay">0</span></span>
                </div>
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6c757d;margin-bottom:8px;">Payment Method</div>
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

{{-- ══ SALES DATA MODAL — Cloud Data + Recently Sold (tabbed) ═════════════ --}}
<div class="modal fade" id="pendingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title"><i class="ri-cloud-line"></i> Sales Data</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="sd-tabs">
                <button type="button" class="sd-tab active" id="sdCloudTabBtn" onclick="switchSalesDataTab('cloud')">
                    <i class="ri-archive-line"></i> Cloud Data
                </button>
                <button type="button" class="sd-tab" id="sdFailedTabBtn" onclick="switchSalesDataTab('failed')">
                    <i class="ri-error-warning-line"></i> Failed
                    <span class="sd-tab-badge" id="sdFailedTabBadge" style="display:none;"></span>
                </button>
                <button type="button" class="sd-tab" id="sdLegacyFailedTabBtn" onclick="switchSalesDataTab('legacy')">
                    <i class="ri-history-line"></i> Legacy Failed
                    <span class="sd-tab-badge" id="sdLegacyFailedTabBadge" style="display:none;"></span>
                </button>
                <button type="button" class="sd-tab" id="sdRecentTabBtn" onclick="switchSalesDataTab('recent')">
                    <i class="ri-list-check"></i> Recently Sold
                </button>
            </div>
            <div class="modal-body" style="padding:0;">

                {{-- Cloud Data pane --}}
                <div id="sdCloudPane">
                    <div class="sd-pane-subbar">
                        <span>Total: MWK <b id="pendingTotalLabel">0</b></span>
                    </div>
                    <div id="pendingListWrap">
                        <table class="table table-sm mb-0" id="pendingTable">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Unit</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-center">Price</th>
                                    <th>Trans ID</th>
                                    <th class="text-center">Time</th>
                                </tr>
                            </thead>
                            <tbody id="pendingTbody">
                                <tr>
                                    <td colspan="6" style="text-align:center;color:#595959;padding:30px;font-size:13px;">
                                        No pending sales.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Failed Sales pane — sales the server explicitly could not
                     accept. Kept in localStorage as the FULL original sale row
                     (product/unit/price/qty/date/time/transid/branch_product_id/
                     user/branch/payment_method/etc, plus reason + failed_at),
                     newest first, until the cashier deletes them or a retry
                     upload succeeds. Keeping every original field — not just
                     the ones shown in this table — is what lets "Upload Failed
                     Sales" retry without any stray/incomplete records. --}}
                <div id="sdFailedPane" style="display:none;">
                    <div class="sd-pane-subbar">
                        <span>Not uploaded: <b id="sdFailedCountLabel">0</b></span>
                        <span class="sd-pane-subbar-note">Not retried automatically — download, retry, or delete</span>
                    </div>
                    <div id="sdFailedListWrap">
                        <table class="table table-sm mb-0" id="sdFailedTable">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Unit</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-center">Price</th>
                                    <th class="text-center">Date</th>
                                    <th>Reason</th>
                                    <th class="text-center">Del</th>
                                </tr>
                            </thead>
                            <tbody id="sdFailedTbody">
                                <tr>
                                    <td colspan="7" style="text-align:center;color:#595959;padding:30px;font-size:13px;">
                                        No failed sales.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Legacy Failed Sales pane — historical failed-sales records recovered from the old date-specific storage. --}}
                <div id="sdLegacyFailedPane" style="display:none;">
                    <div class="sd-pane-subbar">
                        <span>Recovered legacy: <b id="sdLegacyFailedCountLabel">0</b></span>
                        <span class="sd-pane-subbar-note">Historical records from the previous storage format</span>
                    </div>
                    <div id="sdLegacyFailedListWrap">
                        <table class="table table-sm mb-0" id="sdLegacyFailedTable">
                            <thead>
                                <tr>
                                    <th>Product</th><th class="text-center">Unit</th><th class="text-center">Qty</th>
                                    <th class="text-center">Price</th><th>Trans ID</th><th class="text-center">Date</th>
                                    <th>Reason</th><th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="sdLegacyFailedTbody">
                                <tr><td colspan="8" style="text-align:center;color:#595959;padding:30px;font-size:13px;">No legacy failed sales.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Recently Sold pane --}}
                <div id="sdRecentPane" style="display:none;">
                    <div class="sd-pane-subbar">
                        <span>Last synced: <b id="sdLastSyncLabel">—</b></span>
                        <span class="sd-pane-subbar-note">Showing the last 30 items sold today</span>
                    </div>
                    <div id="sdRecentListWrap">
                        <table class="table table-sm mb-0" style="font-size:12px;min-width:500px;">
                            <thead style="position:sticky;top:0;background:silver;">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Unit</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-center">Price</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Payment</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentSales as $rs)
                                <tr>
                                    <td>{{ $rs->product }}</td>
                                    <td class="text-center">{{ $rs->unit }}</td>
                                    <td class="text-center">{{ number_format($rs->quantity, 2) }}</td>
                                    <td class="text-center">{{ number_format($rs->price, 0) }}</td>
                                    <td class="text-center">{{ number_format($rs->quantity * $rs->price, 0) }}</td>
                                    <td class="text-center">{{ $rs->payment_method ?? 'cash' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center py-4 text-muted">No sales recorded yet today.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
            <div class="modal-footer" id="sdCloudFooter">
                {{-- Shown only when the branch admin has enabled clearing of cloud data. --}}
                @if($adminAllowClearCloud)
                <button class="btn btn-outline-secondary btn-sm" id="clearCloudDataBtn" onclick="openClearCloudWarning()">
                    <i class="ri-delete-bin-line"></i> Clear Cloud Data
                </button>
                @endif
                <button class="btn btn-primary btn-sm" onclick="posUpload()" id="pendingUploadBtn" style="margin-left:auto;">
                    <i class="ri-cloud-line me-1"></i> Upload All
                </button>
            </div>
            <div class="modal-footer" id="sdFailedFooter" style="display:none;">
                <button class="btn btn-outline-secondary btn-sm" id="downloadFailedExcelBtn" onclick="downloadFailedSalesExcel()">
                    <i class="ri-file-excel-2-line"></i> Download Excel
                </button>
                <button class="btn btn-primary btn-sm" id="uploadFailedSalesBtn" onclick="uploadFailedSales()">
                    <i class="ri-upload-cloud-2-line me-1"></i> Upload Failed Sales
                </button>
                <button class="btn btn-outline-secondary btn-sm" id="downloadLegacyFailedExcelBtn" onclick="downloadLegacyFailedSalesExcel()" style="display:none;">
                    <i class="ri-file-excel-2-line"></i> Download Legacy Excel
                </button>
                <button class="btn btn-primary btn-sm" id="uploadLegacyFailedSalesBtn" onclick="uploadLegacyFailedSales()" style="display:none;">
                    <i class="ri-upload-cloud-2-line me-1"></i> Retry Legacy Upload
                </button>
                <button class="btn btn-outline-secondary btn-sm" id="clearLegacyFailedDataBtn" onclick="clearAllLegacyFailedSales()" style="display:none;">
                    <i class="ri-delete-bin-line"></i> Clear Legacy
                </button>
                <button class="btn btn-outline-secondary btn-sm" id="clearFailedDataBtn" onclick="clearAllFailedSales()" style="margin-left:auto;">
                    <i class="ri-delete-bin-line"></i> Clear All Failed
                </button>
            </div>
        </div>
    </div>
</div>


{{-- ══ CLEAR CLOUD DATA — SERIOUS WARNING + MATH CAPTCHA MODAL ═══════════ --}}
<div class="modal fade" id="clearCloudWarnModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog" style="max-width:380px;">
        <div class="modal-content">
            <div class="modal-body">
                <i class="ri-error-warning-line warn-icon"></i>
                <h5>This will permanently erase pending sales</h5>
                <p>You are about to clear all <b>pending (not yet uploaded)</b> sales stored on
                   this device. This cannot be undone. Only do this if you are certain these
                   sales are no longer needed, or have already been recovered another way.</p>
                <div class="ccw-backup-note">
                    <i class="ri-file-excel-2-line"></i>
                    A backup CSV file of everything being cleared will be downloaded
                    automatically before anything is deleted.
                </div>
                <p style="margin-bottom:4px;">To confirm you want to proceed, solve this:</p>
                <div id="ccwCaptchaQuestion"></div>
                <input type="number" id="ccwCaptchaInput" placeholder="?" autocomplete="off">
                <div id="ccwCaptchaError"></div>
            </div>
            <div class="modal-footer" style="justify-content:center;gap:10px;">
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-sm" id="ccwConfirmBtn" onclick="confirmClearCloudData()">
                    <i class="ri-delete-bin-line"></i> Yes, Clear It
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
                <div class="mh-iv-title">
                    <span class="mh-iv-branch">{{ $branchName }}</span>
                    <span class="mh-iv-date">{{ $displayDate }}</span>
                </div>
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
                    <span class="mh-iv-date" style="color:#6c757d;font-size:13px;" id="editIvDateLabel">{{ $displayDate }}</span>
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
                               background:linear-gradient(to right,#4B5EBD,#576CC0);color:#fff;border:none;
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
                <a href="#" class="btn btn-info mt-3" id="ivDeleteKeepBtn">No, Keep it</a>
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
                        <i class="ri-bank-card-line" id="ivToggleIcon" style="color:#0d6efd;"></i>
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body" style="padding:16px 18px;">
                <div id="iv-intervals-pane">
                    <div>
                        <table class="table table-sm mb-0" style="font-size:13px;" id="ivIntervalTable">
                            <thead style="position:sticky;top:0;background:#fff;">
                                <tr>
                                    <th style="font-size:13px;font-weight:700;border-top:2px solid #737373;border-bottom:2px solid #737373;">User</th>
                                    <th style="font-size:13px;font-weight:700;border-top:2px solid #737373;border-bottom:2px solid #737373;">Interval</th>
                                    <th class="text-end" style="font-size:13px;font-weight:700;border-top:2px solid #737373;border-bottom:2px solid #737373;">Sales</th>
                                </tr>
                            </thead>
                            <tbody id="ivIntervalTbody">
                                @forelse($todaysIntervalSales as $is)
                                <tr id="ivrow_{{ $is->id }}" data-interval-id="{{ $is->interval_id }}" data-slot="{{ $is->slot }}">
                                    <td style="font-size:13px;">{{ $is->user_name ?? '—' }}</td>
                                    <td class="iv-slot-text" style="font-size:13px;">{{ $is->slot }}</td>
                                    <td class="text-end">
                                        <a href="#" class="iv-amount-link"
                                           onclick="event.preventDefault();openEditIntervalFromView({{ $is->id }},'{{ addslashes($is->slot) }}',{{ $is->sales }})">
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

{{-- ══ CALCULATOR MODAL ══════════════════════════════════════════════════ --}}
<div class="modal fade" id="calculatorModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:360px;">
        <div class="modal-content" style="border:2px solid #4B5EBD;border-radius:14px;overflow:hidden;">
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

{{-- ══ SETTINGS MODAL ════════════════════════════════════════════════════ --}}
<div class="modal fade" id="settingsModal" tabindex="-1">
    <div class="modal-dialog" style="max-width:380px;">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos">
                <h5 class="modal-title mh-pos-title"><i class="ri-settings-3-line"></i> POS Settings</h5>
                <button type="button" class="btn-close mh-close-w" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:6px 20px 18px;">
                <div class="pos-setting-row">
                    <div class="pos-setting-text">
                        <div class="pos-setting-title">Auto-hide search results</div>
                        <div class="pos-setting-desc">
                            When you tap a product or enter a quantity, automatically hide the
                            search results list so you can search for the next item.
                        </div>
                    </div>
                    <label class="pos-switch">
                        <input type="checkbox" id="settingAutoHideSearch" onchange="onToggleAutoHideSearch(this.checked)">
                        <span class="pos-switch-slider"></span>
                    </label>
                </div>

                <div class="pos-setting-row @if(!$adminAutoUploadEnabled) pos-setting-disabled @endif">
                    <div class="pos-setting-text">
                        <div class="pos-setting-title">Auto Upload Cloud Sales</div>
                        <div class="pos-setting-desc">
                            Automatically upload pending cloud sales in the background as they come in.
                        </div>
                        @if($adminAutoUploadEnabled)
                            <div class="pos-setting-admin-note-active">
                                <i class="ri-checkbox-circle-fill"></i>
                                Enabled by admin — uploads every {{ $adminAutoUploadInterval ?? '—' }} minute(s).
                            </div>
                        @else
                            <div class="pos-setting-admin-note">Not enabled — can be set by admin.</div>
                        @endif
                    </div>
                    <label class="pos-switch pos-switch-disabled">
                        <input type="checkbox" disabled {{ $adminAutoUploadEnabled ? 'checked' : '' }}>
                        <span class="pos-switch-slider"></span>
                    </label>
                </div>

                <div class="pos-setting-row @if(!$adminAllowClearCloud) pos-setting-disabled @endif">
                    <div class="pos-setting-text">
                        <div class="pos-setting-title">Clear Cloud Data</div>
                        <div class="pos-setting-desc">
                            Allow clearing pending (not yet uploaded) cloud sales from this device.
                        </div>
                        @if($adminAllowClearCloud)
                            <div class="pos-setting-admin-note-active">
                                <i class="ri-checkbox-circle-fill"></i>
                                Enabled by admin — the "Clear Cloud Data" button is available in Sales Data.
                            </div>
                        @else
                            <div class="pos-setting-admin-note">Not enabled — can be set by admin.</div>
                        @endif
                    </div>
                    <label class="pos-switch pos-switch-disabled">
                        <input type="checkbox" disabled {{ $adminAllowClearCloud ? 'checked' : '' }}>
                        <span class="pos-switch-slider"></span>
                    </label>
                </div>

                <div class="pos-setting-row" style="flex-direction:column;align-items:stretch;gap:8px;">
                    <div class="pos-setting-text">
                        <div class="pos-setting-title">Auto-Refresh</div>
                        @if($adminAutoRefreshEnabled)
                            <div class="pos-setting-desc">
                                The system automatically refreshes this page every {{ $adminAutoRefreshInterval }} minute(s), once the
                                cart is empty, to keep things running smoothly.
                            </div>
                            <div class="pos-setting-admin-note-active">
                                <i class="ri-checkbox-circle-fill"></i>
                                Set by admin — every {{ $adminAutoRefreshInterval }} minute(s).
                            </div>
                        @else
                            <div class="pos-setting-desc">
                                Auto-refresh is currently disabled by admin.
                            </div>
                            <div class="pos-setting-admin-note">Not enabled — can be set by admin.</div>
                        @endif
                    </div>
                    @if($adminAutoRefreshEnabled)
                    <div class="pos-autorefresh-countdown">
                        Page will refresh in <span id="autoRefreshCountdown">--:--:--</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ══ BYPASS POS MODAL ══════════════════════════════════════════════════
     Lets a cashier serve a second customer without disturbing the main
     cart. Its own search/cart/checkout, but confirmed sales are pushed
     into the SAME cloudData/POS_PENDING_KEY as the main cart, so pending
     uploads are unified regardless of which cart recorded the sale. ═══ --}}
<div class="modal fade" id="bypassPosModal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg" style="max-width:640px;">
        <div class="modal-content" style="border:1px solid #a6a6a6;">
            <div class="modal-header mh-pos-silver">
                <h5 class="modal-title mh-pos-title-silver"><i class="ri-user-add-line"></i> Bypass POS — Serve Another Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:0;">
                <div id="bypass-pos-search-row">
                    <div id="bypass-pos-search-wrap">
                        <i class="ri-smartphone-line"></i>
                        <input type="text" id="bypass-pos-search"
                               placeholder="Search product name or code"
                               autocomplete="off"
                               onclick="clearBypassSearch()">
                    </div>
                </div>
                <div id="bypass-pos-product-display"></div>

                <div id="bypass-pos-cart-bar">
                    <button type="button" id="bypassClearAllBtn" onclick="bypassClearCart()" disabled>
                        <i class="ri-delete-bin-line"></i> Clear All
                    </button>
                    <div class="pos-cart-label">
                        <i class="ri-receipt-line cart-icon"></i>
                        <span class="cart-pipe">|</span>
                        <span class="cart-currency">MWK</span>
                        <span id="bypassCartTotalPill">0.00</span>
                    </div>
                </div>
                <div id="bypass-pos-cart-table-wrap">
                    <table id="bypass-pos-cart-table" class="pos-cart-empty">
                        <thead>
                            <tr>
                                <th>Item</th><th>Unit</th><th>Price</th><th class="pcr-qty-col">Qty</th><th>Total</th><th>Del</th>
                            </tr>
                        </thead>
                        <tbody id="bypass-pos-cart-tbody">
                            <tr id="bypass-pos-cart-empty-row">
                                <td colspan="6" id="bypass-pos-cart-empty">No items in cart</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bypass-section-label">Payment Method</div>
                <div class="pm-grid" id="bypassPmGrid" style="margin:0 18px 12px;">
                    @foreach($paymentMethods as $pm)
                    <div class="pm-card {{ $loop->first ? 'active' : '' }}"
                         data-pm="{{ $pm['id'] }}"
                         onclick="bypassSelectPaymentMethod('{{ $pm['id'] }}', this)">
                        <i class="{{ $pm['icon'] }}"></i>
                        <span class="pm-label">{{ $pm['label'] }}</span>
                    </div>
                    @endforeach
                </div>
                <div class="checkout-amount-wrap" id="bypassAmountPaidWrap" style="margin:0 18px 8px;">
                    <label>Amount Tendered (MWK)</label>
                    <input type="number" class="checkout-amount-input" id="bypassAmountPaidInput"
                           placeholder="Amount tendered" min="0" oninput="bypassCalcChange()">
                </div>
                <div id="bypass-checkout-change-row" style="margin:0 18px 14px;">
                    <span id="bypass-checkout-change-label">Change</span>
                    <span id="bypass-checkout-change-value">MWK 0</span>
                </div>
            </div>
            <div class="modal-footer" style="padding:10px 18px 14px;">
                <button type="button" id="bypassConfirmSaleBtn" onclick="bypassConfirmSale()" disabled>
                    <i class="ri-check-double-line"></i> Record Sale
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
'use strict';

/* ══════════════════════════════════════════════════════════════
   STORAGE KEYS
══════════════════════════════════════════════════════════════ */
const _BRANCH = document.getElementById('posBranchId').value;
const _DATE   = document.getElementById('posDate').value;
const PAGE_LOADED_AT = new Date();

/* NOTE ON NAMING — these are the ONLY storage entries this POS ever
   writes for cart/sale data, and every one of them is branch-scoped
   but date-INDEPENDENT on purpose: a cashier who adds items today and
   comes back tomorrow (without having synced or cleared) must still
   find that same data waiting for them. Nothing here is keyed by date,
   and nothing here is ever deleted just because the calendar changed. */
const POS_CART_KEY        = 'dpos_mainPosCart_b'          + _BRANCH; // one "mainPosCart" entry
const POS_BYPASS_CART_KEY = 'dpos_bypassPosCart_b'        + _BRANCH; // one "bypassPosCart" entry
const POS_PENDING_KEY     = 'dpos_pendingSales_b'         + _BRANCH; // one "pendingSales" entry
const POS_FAILED_KEY      = 'dpos_failedToUploadSales_b'  + _BRANCH; // one "failedToUploadSales" entry
const POS_SETTINGS_KEY    = 'dpos_settings_v1';
const POS_TXN_KEY         = 'dpos_saleCommit_b' + _BRANCH;
const POS_LEGACY_FAILED_KEY = 'dpos_recoveredLegacyFailed_b' + _BRANCH;

/* Desktop storage is intentionally self-contained. It does not import
   mobile npos_* keys, because doing so can duplicate or consume mobile sales. */

/* Desktop and Mobile POS intentionally NEVER migrate/read each other's
   current storage namespace. Older shared npos_* keys are left untouched so
   they remain recoverable by the POS version that created them. */

function migrateLegacyFailedStorage() {
    try {
        const oldFailedPrefix = 'npos_failed_b' + _BRANCH + '_d';
        const legacyKeys = [];
        const recovered = [];
        for (let i = localStorage.length - 1; i >= 0; i--) {
            const k = localStorage.key(i);
            if (!k || k.indexOf(oldFailedPrefix) !== 0) continue;
            legacyKeys.push(k);
            try {
                const raw = JSON.parse(localStorage.getItem(k) || '[]');
                const rows = Array.isArray(raw) ? raw : (raw && Array.isArray(raw.items) ? raw.items : []);
                rows.forEach(r => recovered.push(Object.assign({}, r, { legacy_recovered:true, legacy_source_key:k })));
            } catch(e) {}
        }
        if (!recovered.length) return;
        let existing=[];
        try { existing=JSON.parse(localStorage.getItem(POS_LEGACY_FAILED_KEY)||'[]'); } catch(e) {}
        if (!Array.isArray(existing)) existing=[];
        const keyOf=r=>(r.transid||'')+'|'+(r.branch_product_id||'')+'|'+(r.date||'')+'|'+(r.time||'')+'|'+(r.product||'');
        const map=new Map();
        existing.concat(recovered).forEach(r=>map.set(keyOf(r),r));
        storageWriteVerified(POS_LEGACY_FAILED_KEY, Array.from(map.values()));
        // Do not remove the old keys here: historical records are evidence until the
        // cashier has reviewed/exported them. A separate cleanup action handles them.
    } catch(e) { console.error('migrateLegacyFailedStorage failed',e); }
}

/* ── Admin-controlled settings (from branch_sales_settings) ── */
const ADMIN_AUTO_UPLOAD_ENABLED       = @json($adminAutoUploadEnabled);
const ADMIN_AUTO_UPLOAD_INTERVAL_MIN  = {{ (int) ($adminAutoUploadInterval ?? 0) }};
const ADMIN_ALLOW_CLEAR_CLOUD         = @json($adminAllowClearCloud);
const ADMIN_AUTO_REFRESH_ENABLED      = @json($adminAutoRefreshEnabled);
const ADMIN_AUTO_REFRESH_INTERVAL_MIN = {{ (int) $adminAutoRefreshInterval }};

/* ══════════════════════════════════════════════════════════════
   SINGLE-TAB LOCK
══════════════════════════════════════════════════════════════ */
const POS_LOCK_KEY    = 'dpos_lock_b' + _BRANCH + '_v1';
const POS_LOCK_TTL_MS  = 5000;
const POS_LOCK_BEAT_MS = 2000;

const posTabLock = {
    tabId: 'tab_' + Date.now() + '_' + Math.random().toString(36).slice(2),
    heartbeatTimer: null,
    isLocked: false,
    channel: null,

    initChannel() {
        if (typeof BroadcastChannel === 'undefined') return false;
        try {
            this.channel = new BroadcastChannel('netacube_desktop_pos_lock_b' + _BRANCH);
            this.channel.onmessage = (e) => this.handleChannelMessage(e.data);
            return true;
        } catch(err) { return false; }
    },

    handleChannelMessage(data) {
        if (!data || !data.type) return;
        switch (data.type) {
            case 'pos:ping':
                if (!this.isLocked && this.heartbeatTimer) {
                    this.channel.postMessage({ type: 'pos:active', tabId: this.tabId });
                }
                break;
            case 'pos:active':
                if (data.tabId !== this.tabId && !this.isLocked) {
                    this.showOverlay();
                }
                break;
            case 'pos:supersede':
                if (data.tabId !== this.tabId) {
                    this.forceCloseSelf();
                }
                break;
        }
    },

    broadcast(type) {
        if (this.channel) {
            try { this.channel.postMessage({ type, tabId: this.tabId }); } catch(e) {}
        }
    },

    readLock() {
        try { return JSON.parse(localStorage.getItem(POS_LOCK_KEY) || 'null'); }
        catch(e) { return null; }
    },
    writeLock() {
        try {
            localStorage.setItem(POS_LOCK_KEY, JSON.stringify({ tabId: this.tabId, ts: Date.now() }));
        } catch(e) {}
    },
    isHeldByOther() {
        const cur = this.readLock();
        return !!cur && cur.tabId !== this.tabId && (Date.now() - cur.ts) < POS_LOCK_TTL_MS;
    },

    init() {
        const hasChannel = this.initChannel();

        if (hasChannel) {
            this.broadcast('pos:ping');
            return new Promise((resolve) => {
                let resolved = false;
                const timer = setTimeout(() => {
                    if (!resolved) {
                        resolved = true;
                        if (!this.isLocked) {
                            this.claim();
                            resolve(true);
                        } else {
                            resolve(false);
                        }
                    }
                }, 400);
                const poll = setInterval(() => {
                    if (this.isLocked && !resolved) {
                        resolved = true;
                        clearInterval(poll);
                        clearTimeout(timer);
                        resolve(false);
                    }
                }, 50);
            });
        } else {
            if (this.isHeldByOther()) {
                this.showOverlay();
                this.waitTimer = setInterval(() => {
                    if (!this.isHeldByOther()) {
                        clearInterval(this.waitTimer);
                        this.hideOverlay();
                        this.claim();
                        if (typeof initPosApp === 'function') initPosApp();
                    }
                }, 1500);
                return Promise.resolve(false);
            }
            this.claim();
            return Promise.resolve(true);
        }
    },

    claim() {
        this.writeLock();
        this.heartbeatTimer = setInterval(() => this.writeLock(), POS_LOCK_BEAT_MS);

        window.addEventListener('storage', (e) => {
            if (e.key !== POS_LOCK_KEY) return;
            if (this.isHeldByOther()) this.forceCloseSelf();
        });

        window.addEventListener('beforeunload', () => {
            const cur = this.readLock();
            if (cur && cur.tabId === this.tabId) {
                try { localStorage.removeItem(POS_LOCK_KEY); } catch(e) {}
            }
            if (this.channel) { try { this.channel.close(); } catch(e) {} }
        });
    },

    async takeOver() {
        clearInterval(this.heartbeatTimer);
        this.broadcast('pos:supersede');
        await new Promise(r => setTimeout(r, 150));
        this.hideOverlay();
        this.claim();
        if (typeof initPosApp === 'function') initPosApp();
        else window.location.reload();
    },

    forceCloseSelf() {
        if (this.isLocked) return;
        this.isLocked = true;
        clearInterval(this.heartbeatTimer);
        const card = document.getElementById('posCard');
        if (card) card.style.display = 'none';
        try { window.close(); } catch(e) {}
        setTimeout(() => {
            if (document.visibilityState === 'hidden') return;
            const overlay = document.getElementById('posSupersededOverlay');
            if (overlay) overlay.style.display = 'flex';
        }, 150);
    },

    showOverlay() {
        this.isLocked = true;
        const ov = document.getElementById('posTabLockOverlay');
        if (ov) ov.style.display = 'flex';
        const app = document.getElementById('posCard');
        if (app) app.style.display = 'none';
    },
    hideOverlay() {
        this.isLocked = false;
        const ov = document.getElementById('posTabLockOverlay');
        if (ov) ov.style.display = 'none';
        const app = document.getElementById('posCard');
        if (app) app.style.display = '';
    },
};

/* ══════════════════════════════════════════════════════════════
   AUTO-REFRESH (45 minutes, cart empty + no modal open)
══════════════════════════════════════════════════════════════ */
const POS_AUTO_REFRESH_MS = 45 * 60 * 1000;

function scheduleAutoRefresh() {
    setInterval(() => {
        const cartEmpty = !cart || cart.length === 0;
        const modalOpen = document.querySelector('.modal.show') !== null;
        if (cartEmpty && !modalOpen && !posTabLock.isLocked) {
            window.location.reload();
        }
    }, POS_AUTO_REFRESH_MS);
}

/* ══════════════════════════════════════════════════════════════
   AUTO-UPLOAD CLOUD SALES — admin-controlled. Runs in the background
   at the configured interval: shows the pending-sales modal first so
   the cashier can see what's about to go up, waits 20s, then uploads
   and closes the modal once done.
══════════════════════════════════════════════════════════════ */
const AUTO_UPLOAD_MODAL_DELAY_MS = 20000;

function scheduleAutoCloudUpload() {
    if (!ADMIN_AUTO_UPLOAD_ENABLED || !ADMIN_AUTO_UPLOAD_INTERVAL_MIN) return;
    setInterval(() => {
        if (!cloudData.length) return;
        // Don't interrupt the cashier if some other modal is already open
        // (mid-sale, bypass POS, settings, etc.) — try again next cycle.
        if (document.querySelector('.modal.show')) return;

        switchSalesDataTab('cloud');
        renderPendingModal();
        updateLastSyncLabel();
        $('#pendingModal').modal('show');

        setTimeout(() => {
            posUpload(true).finally(() => {
                $('#pendingModal').modal('hide');
            });
        }, AUTO_UPLOAD_MODAL_DELAY_MS);
    }, ADMIN_AUTO_UPLOAD_INTERVAL_MIN * 60 * 1000);
}

/* ══════════════════════════════════════════════════════════════
   DESKTOP HEIGHT — fill remaining viewport below the card's top.
══════════════════════════════════════════════════════════════ */
function setPosCardHeight() {
    if (window.innerWidth <= 768) {
        const card = document.getElementById('posCard');
        if (card) card.style.height = '';
        return;
    }
    const card = document.getElementById('posCard');
    if (!card) return;
    const viewportH = window.visualViewport ? window.visualViewport.height : window.innerHeight;
    const rect      = card.getBoundingClientRect();
    const bottomPad = 20;
    const exact     = Math.max(300, Math.floor(viewportH - rect.top - bottomPad));
    card.style.height = exact + 'px';
}

function initPosCardHeightWatcher() {
    setPosCardHeight();
    window.addEventListener('resize', setPosCardHeight);
    window.addEventListener('orientationchange', () => setTimeout(setPosCardHeight, 200));
    window.addEventListener('pageshow', setPosCardHeight);
    if (window.visualViewport) {
        window.visualViewport.addEventListener('resize', setPosCardHeight);
    }
    window.addEventListener('load', setPosCardHeight);
    [100, 250, 500, 1000, 2000].forEach(ms => setTimeout(setPosCardHeight, ms));
}

/* ══════════════════════════════════════════════════════════════
   STATE
══════════════════════════════════════════════════════════════ */
let cart      = [];
let cloudData = [];
let allProducts = [];
let activePaymentMethod = 'cash';

let bypassCart = [];
let bypassActivePaymentMethod = 'cash';
let bypassTransId = '';
let posUploadInProgress = false;

let posSettings = { autoHideSearchOnAdd: true };

/* ══════════════════════════════════════════════════════════════
   AUTO-REFRESH — driven by the branch admin setting. Only ever
   fires once both carts (main + bypass) are empty, so no
   in-progress sale is ever lost. The settings modal shows a live
   countdown when enabled.
══════════════════════════════════════════════════════════════ */
const AUTO_REFRESH_INTERVAL_MS = ADMIN_AUTO_REFRESH_ENABLED
    ? ADMIN_AUTO_REFRESH_INTERVAL_MIN * 60 * 1000
    : null;
const AUTO_REFRESH_AT = AUTO_REFRESH_INTERVAL_MS
    ? (PAGE_LOADED_AT.getTime() + AUTO_REFRESH_INTERVAL_MS)
    : null;

function formatCountdown(ms) {
    if (ms <= 0) return '00:00:00';
    const totalSeconds = Math.floor(ms / 1000);
    const hh = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
    const mm = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
    const ss = String(totalSeconds % 60).padStart(2, '0');
    return `${hh}:${mm}:${ss}`;
}

function tickAutoRefresh() {
    const label = document.getElementById('autoRefreshCountdown');
    if (!AUTO_REFRESH_AT) return; // admin has auto-refresh disabled

    const remaining  = AUTO_REFRESH_AT - Date.now();
    const cartsEmpty = !cart.length && !bypassCart.length;

    if (label) {
        if (remaining > 0) {
            label.textContent = formatCountdown(remaining);
        } else {
            label.textContent = cartsEmpty ? 'Refreshing…' : 'Waiting for cart to be empty…';
        }
    }

    if (remaining <= 0 && cartsEmpty) {
        window.location.reload();
    }
}
if (AUTO_REFRESH_AT) {
    setInterval(tickAutoRefresh, 1000);
    tickAutoRefresh();
}

let currentIvId    = null;
let currentIvSlot  = null;
let currentIvSales = null;

let ccwExpectedAnswer = null;

/* Desktop-only receipt state. */
let lastSaleSnapshot = null;

/* ══════════════════════════════════════════════════════════════
   HELPERS
══════════════════════════════════════════════════════════════ */
function escHtml(str) {
    return String(str == null ? '' : str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function fmtMoney(n) {
    if (n == null || n === '') return '0.00';
    return parseFloat(n).toLocaleString('en-US', { minimumFractionDigits:2, maximumFractionDigits:2 });
}
function fmtNum(n) {
    if (n == null || n === '') return '0';
    return parseFloat(n).toLocaleString('en-US', { minimumFractionDigits:0, maximumFractionDigits:0 });
}
function fmtQty(n) {
    if (n == null || n === '') return '0.00';
    return parseFloat(n).toLocaleString('en-US', { minimumFractionDigits:2, maximumFractionDigits:2 });
}
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}
function getDeviceName() {
    const ua = navigator.userAgent;
    let os = 'Unknown';
    if (/Windows/i.test(ua))          os = 'Windows';
    else if (/Android/i.test(ua))     os = 'Android';
    else if (/iPhone|iPad/i.test(ua)) os = 'iOS';
    else if (/Mac/i.test(ua))         os = 'macOS';
    else if (/Linux/i.test(ua))       os = 'Linux';
    let br = 'Browser';
    if (/Edg\//i.test(ua))            br = 'Edge';
    else if (/Chrome\//i.test(ua))    br = 'Chrome';
    else if (/Firefox\//i.test(ua))   br = 'Firefox';
    else if (/Safari\//i.test(ua))    br = 'Safari';
    return br + ' on ' + os;
}
function posLoaderShow() { document.getElementById('progressBar').style.display = ''; }
function posLoaderHide() { document.getElementById('progressBar').style.display = 'none'; }

function clearPosSearch() {
    const s = document.getElementById('pos-search');
    if (s.value) {
        s.value = '';
        const display = document.getElementById('pos-product-display');
        display.innerHTML = '';
        display.classList.remove('has-results');
        const leftCol = document.getElementById('pos-left-col');
        if (leftCol) leftCol.classList.remove('has-products');
    }
}
function clearSearch() {
    const s = document.getElementById('pos-search');
    s.value = '';
    const display = document.getElementById('pos-product-display');
    display.innerHTML = '';
    display.classList.remove('has-results');
    const leftCol = document.getElementById('pos-left-col');
    if (leftCol) leftCol.classList.remove('has-products');
    s.focus();
}
/* Called after a product is added to the MAIN cart. Whether the search
   results actually get hidden depends on the user's "Auto-hide search
   results" setting — either way the search box is refocused so the
   next item can be typed straight away. */
function afterAddToCart() {
    if (posSettings.autoHideSearchOnAdd) {
        clearSearch();
    } else {
        document.getElementById('pos-search').focus();
    }
}
function clearBypassSearch() {
    const s = document.getElementById('bypass-pos-search');
    if (s && s.value) {
        s.value = '';
        const display = document.getElementById('bypass-pos-product-display');
        display.innerHTML = '';
        display.classList.remove('has-results');
    }
}
/* Same idea as afterAddToCart() but for the Bypass POS cart. */
function bypassAfterAddToCart() {
    if (posSettings.autoHideSearchOnAdd) {
        const s = document.getElementById('bypass-pos-search');
        s.value = '';
        const display = document.getElementById('bypass-pos-product-display');
        display.innerHTML = '';
        display.classList.remove('has-results');
    }
    document.getElementById('bypass-pos-search').focus();
}

/* ══════════════════════════════════════════════════════════════
   CSV EXPORT
══════════════════════════════════════════════════════════════ */
function exportRowsToCsv(rows, filenamePrefix) {
    if (!Array.isArray(rows) || !rows.length) return;
    const cols   = ['transid', 'product', 'unit', 'price', 'time'];
    const labels = ['Trans ID', 'Product', 'Unit', 'Price', 'Time'];
    const escCsv = v => {
        const s = v == null ? '' : String(v);
        return /[",\n]/.test(s) ? '"' + s.replace(/"/g,'""') + '"' : s;
    };
    let csv = labels.join(',') + '\n';
    rows.forEach(r => { csv += cols.map(c => escCsv(r[c])).join(',') + '\n'; });
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    const stamp = new Date().toISOString().replace(/[:.]/g,'-');
    a.href = url;
    a.download = filenamePrefix + '_' + stamp + '.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(() => URL.revokeObjectURL(url), 4000);
}

/* ══════════════════════════════════════════════════════════════
   STORAGE
══════════════════════════════════════════════════════════════ */
function storageWriteVerified(key, value) {
    try {
        const serialized = JSON.stringify(value);
        localStorage.setItem(key, serialized);
        return localStorage.getItem(key) === serialized;
    } catch(e) {
        console.error('LocalStorage write failed for', key, e);
        return false;
    }
}
function storageRemoveVerified(key) {
    try {
        localStorage.removeItem(key);
        return localStorage.getItem(key) === null;
    } catch(e) {
        console.error('LocalStorage remove failed for', key, e);
        return false;
    }
}
function saveCart() {
    return storageWriteVerified(POS_CART_KEY, { items: cart });
}
function loadCart() {
    let raw = null;
    try { raw = JSON.parse(localStorage.getItem(POS_CART_KEY) || 'null'); }
    catch(e) { raw = null; }
    if (raw && Array.isArray(raw.items)) {
        cart = raw.items;
    } else if (Array.isArray(raw)) {
        cart = raw;
    } else {
        cart = [];
    }
    if (!Array.isArray(cart)) cart = [];
}
function saveBypassCart() {
    return storageWriteVerified(POS_BYPASS_CART_KEY, { items: bypassCart });
}
function loadBypassCart() {
    let raw = null;
    try { raw = JSON.parse(localStorage.getItem(POS_BYPASS_CART_KEY) || 'null'); }
    catch(e) { raw = null; }
    if (raw && Array.isArray(raw.items)) {
        bypassCart = raw.items;
    } else if (Array.isArray(raw)) {
        bypassCart = raw;
    } else {
        bypassCart = [];
    }
    if (!Array.isArray(bypassCart)) bypassCart = [];
}
function savePendingSales() {
    return storageWriteVerified(POS_PENDING_KEY, cloudData);
}
function loadPendingSales() {
    try { cloudData = JSON.parse(localStorage.getItem(POS_PENDING_KEY) || '[]'); }
    catch(e) { cloudData = []; }
    if (!Array.isArray(cloudData)) cloudData = [];
    // pendingSales is one date-independent queue. A sale made yesterday
    // and never uploaded is exactly as valid as one made a minute ago —
    // it stays here, visible and uploadable, until it succeeds or the
    // server explicitly rejects it (see posUpload()). We never drop rows
    // here just because the calendar date has rolled over.
}
/* Appends one row to failedToUploadSales — the single, date-independent
   record of sales that were skipped because the server could not accept
   them. Newest entries are kept at the top so the cashier sees the most
   recent problem first; every entry stays until the cashier deletes it.
   IMPORTANT: the FULL original row is kept (branch_product_id, transid,
   date, time, product, unit, price, quantity, user, branch, payment_method,
   amount_paid, slot, device_name, user_agent — whatever the row had), not
   just the handful of fields shown in the Failed table. Dropping any field
   here would turn a retryable failed sale into a stray record that
   "Upload Failed Sales" and "Download Excel" could never fully recover. */
function appendToFailedLog(row, reason) {
    const list = loadFailedSales();
    const originalRow = Object.assign({}, row);
    delete originalRow._reason;
    list.unshift(Object.assign(originalRow, {
        reason: reason || 'upload_failed',
        failed_at: new Date().toISOString(),
    }));
    return saveFailedSales(list);
}
function loadFailedSales() {
    let list = [];
    try { list = JSON.parse(localStorage.getItem(POS_FAILED_KEY) || '[]'); } catch(e) {}
    return Array.isArray(list) ? list : [];
}
function saveFailedSales(list) {
    const input = Array.isArray(list) ? list : [];
    const seen = new Set();
    const unique = [];
    input.forEach(row => {
        const key = saleRowKey(row);
        if (seen.has(key)) return;
        seen.add(key);
        unique.push(row);
    });
    return storageWriteVerified(POS_FAILED_KEY, unique);
}
function loadSettings() {
    try {
        const raw = JSON.parse(localStorage.getItem(POS_SETTINGS_KEY) || 'null');
        if (raw && typeof raw === 'object') posSettings = Object.assign({}, posSettings, raw);
    } catch(e) { /* keep defaults */ }
}
function saveSettings() {
    try { localStorage.setItem(POS_SETTINGS_KEY, JSON.stringify(posSettings)); }
    catch(e) { console.error('saveSettings failed', e); }
}
function onToggleAutoHideSearch(checked) {
    posSettings.autoHideSearchOnAdd = !!checked;
    saveSettings();
    toastr.info(checked
        ? 'Search results will hide automatically after adding a product.'
        : 'Search results will stay open after adding a product.');
}

/* ══════════════════════════════════════════════════════════════
   SALE COMMIT JOURNAL — protects the small window between recording a
   sale and clearing its cart. The journal is branch-scoped and POS-scoped.
   If the browser closes, storage fills, or a refresh happens mid-commit,
   the next load finishes the commit instead of losing the sale.
══════════════════════════════════════════════════════════════ */
function saleRowKey(r) {
    return [r.transid || '', r.branch_product_id || '', r.date || '', r.time || ''].join('|');
}
function beginSaleJournal(type, snapshot) {
    return storageWriteVerified(POS_TXN_KEY, {
        version: 1,
        type,
        stage: 'prepared',
        snapshot: snapshot.map(r => Object.assign({}, r)),
        created_at: new Date().toISOString(),
    });
}
function updateSaleJournal(stage, snapshot, type) {
    return storageWriteVerified(POS_TXN_KEY, {
        version: 1, type, stage,
        snapshot: snapshot.map(r => Object.assign({}, r)),
        created_at: new Date().toISOString(),
    });
}
function finishSaleJournal() {
    return storageRemoveVerified(POS_TXN_KEY);
}
function recoverSaleJournal() {
    let journal = null;
    try { journal = JSON.parse(localStorage.getItem(POS_TXN_KEY) || 'null'); } catch(e) {}
    if (!journal || !Array.isArray(journal.snapshot) || !journal.snapshot.length) return;

    const snapshot = journal.snapshot;
    const keys = new Set(snapshot.map(saleRowKey));
    let pending = [];
    try { pending = JSON.parse(localStorage.getItem(POS_PENDING_KEY) || '[]'); } catch(e) {}
    if (!Array.isArray(pending)) pending = [];

    const pendingKeys = new Set(pending.map(saleRowKey));
    const missing = snapshot.filter(r => !pendingKeys.has(saleRowKey(r)));
    if (missing.length) {
        pending = pending.concat(missing);
        if (!storageWriteVerified(POS_PENDING_KEY, pending)) {
            console.error('Sale recovery could not persist pending sale journal.');
            return;
        }
    }

    const removeSold = (items) => {
        const remaining = Array.isArray(items) ? items.map(i => Object.assign({}, i)) : [];
        snapshot.forEach(row => {
            const id = String(row.branch_product_id || '');
            let qty = Number(row.quantity || row.qty_sold || 0);
            for (let i = remaining.length - 1; i >= 0 && qty > 0; i--) {
                if (String(remaining[i].id) !== id) continue;
                const have = Number(remaining[i].qty || 0);
                const take = Math.min(have, qty);
                remaining[i].qty = have - take;
                qty -= take;
                if (remaining[i].qty <= 0) remaining.splice(i, 1);
            }
        });
        return remaining;
    };

    const type = journal.type === 'bypass' ? 'bypass' : 'main';
    if (type === 'bypass') {
        let raw = null; try { raw = JSON.parse(localStorage.getItem(POS_BYPASS_CART_KEY) || 'null'); } catch(e) {}
        const items = raw && Array.isArray(raw.items) ? raw.items : (Array.isArray(raw) ? raw : []);
        bypassCart = removeSold(items);
        if (!storageWriteVerified(POS_BYPASS_CART_KEY, { items: bypassCart })) return;
    } else {
        let raw = null; try { raw = JSON.parse(localStorage.getItem(POS_CART_KEY) || 'null'); } catch(e) {}
        const items = raw && Array.isArray(raw.items) ? raw.items : (Array.isArray(raw) ? raw : []);
        cart = removeSold(items);
        if (!storageWriteVerified(POS_CART_KEY, { items: cart })) return;
    }
    storageRemoveVerified(POS_TXN_KEY);
}

/* ══════════════════════════════════════════════════════════════
   CLEAR CLOUD DATA
══════════════════════════════════════════════════════════════ */
function openClearCloudWarning() {
    if (!ADMIN_ALLOW_CLEAR_CLOUD) { toastr.warning('Clearing cloud data is not enabled for this branch.'); return; }
    if (!cloudData.length) { toastr.info('There is no pending data to clear.'); return; }
    ccwGenerateCaptcha();
    document.getElementById('ccwCaptchaInput').value = '';
    document.getElementById('ccwCaptchaError').textContent = '';
    $('#pendingModal').modal('hide');
    setTimeout(() => $('#clearCloudWarnModal').modal('show'), 250);
}

function ccwGenerateCaptcha() {
    const a = Math.floor(Math.random() * 18) + 2;
    const b = Math.floor(Math.random() * 18) + 2;
    const useAdd = Math.random() < 0.5;
    ccwExpectedAnswer = useAdd ? (a + b) : Math.max(a, b) - Math.min(a, b);
    const opSign = useAdd ? '+' : '−';
    const left = useAdd ? a : Math.max(a, b);
    const right = useAdd ? b : Math.min(a, b);
    document.getElementById('ccwCaptchaQuestion').textContent = left + ' ' + opSign + ' ' + right + ' = ?';
}

function confirmClearCloudData() {
    const entered = parseFloat(document.getElementById('ccwCaptchaInput').value);
    const errEl = document.getElementById('ccwCaptchaError');
    if (isNaN(entered) || entered !== ccwExpectedAnswer) {
        errEl.textContent = 'Incorrect answer — try the new sum below.';
        ccwGenerateCaptcha();
        document.getElementById('ccwCaptchaInput').value = '';
        document.getElementById('ccwCaptchaInput').focus();
        return;
    }
    const backupRows = cloudData.map(r => ({ ...r }));
    exportRowsToCsv(backupRows, 'pending_sales_backup_branch' + _BRANCH);
    if (!storageWriteVerified(POS_PENDING_KEY, [])) {
        toastr.error('The backup was created, but the pending queue could not be safely cleared. The sales were retained.', 'Storage Warning');
        return;
    }
    cloudData = [];
    updatePendingBadge();
    renderPendingModal();
    $('#clearCloudWarnModal').modal('hide');
    toastr.success('Pending sales cleared. A backup CSV was downloaded.', 'Cleared');
}

/* ══════════════════════════════════════════════════════════════
   INIT
══════════════════════════════════════════════════════════════ */
let _posAppInitialised = false;

function initPosApp() {
    if (_posAppInitialised) return;
    _posAppInitialised = true;

    initPosCardHeightWatcher();
    loadSettings();
    recoverSaleJournal();

    try {
        allProducts = JSON.parse(document.getElementById('pos-products-json').textContent || '[]');
    } catch(e) { allProducts = []; }

    const display = document.getElementById('pos-product-display');
    const leftCol = document.getElementById('pos-left-col');

    function renderRows(products) {
        display.innerHTML = buildProductRowsHtml(products, 'main');
        display.classList.add('has-results');
        leftCol.classList.add('has-products');
    }

    function clearProductDisplay() {
        display.innerHTML = '';
        display.classList.remove('has-results');
        leftCol.classList.remove('has-products');
    }

    $('#pos-search').on('keyup', function () {
        const q = $(this).val().trim();
        if (q.length < 2) { clearProductDisplay(); return; }
        renderRows(filterProductList(q));
    });

    const bypassDisplay = document.getElementById('bypass-pos-product-display');
    $('#bypass-pos-search').on('keyup', function () {
        const q = $(this).val().trim();
        if (q.length < 2) {
            bypassDisplay.innerHTML = '';
            bypassDisplay.classList.remove('has-results');
            return;
        }
        bypassDisplay.innerHTML = buildProductRowsHtml(filterProductList(q), 'bypass');
        bypassDisplay.classList.add('has-results');
    });

        migrateLegacyFailedStorage();
        loadCart(); loadPendingSales(); renderCart(); updatePendingBadge();
    updateFailedBadge();
    updateLegacyFailedBadge();
    document.getElementById('pos-search').focus();

    document.getElementById('ivDeleteKeepBtn').addEventListener('click', function(e) {
        e.preventDefault(); $('#ivDeleteConfirmModal').modal('hide');
    });
    document.getElementById('ivDeleteConfirmBtn').addEventListener('click', function(e) {
        e.preventDefault();
        $('#ivDeleteConfirmModal').modal('hide');
        setTimeout(() => executeDeleteInterval(), 200);
    });

    document.getElementById('posUploadBtn').addEventListener('click', function(e) {
        e.preventDefault();
        switchSalesDataTab('cloud');
        renderPendingModal();
        updateLastSyncLabel();
        $('#pendingModal').modal('show');
    });
    document.getElementById('posCalcBtn').addEventListener('click', function(e) {
        e.preventDefault(); calcExpr=''; calcRender(); $('#calculatorModal').modal('show');
    });
    document.getElementById('posViewIntervalBtn').addEventListener('click', function(e) {
        e.preventDefault(); resetIntervalView(); $('#viewIntervalModal').modal('show');
    });
    document.getElementById('posIntervalBtn').addEventListener('click', function(e) {
        e.preventDefault(); $('#intervalModal').modal('show');
        setTimeout(() => { const i=document.getElementById('intervalSalesInput'); if(i) i.focus(); }, 400);
    });
    document.getElementById('posSettingsBtn').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('settingAutoHideSearch').checked = !!posSettings.autoHideSearchOnAdd;
        $('#settingsModal').modal('show');
    });
    document.getElementById('posBypassBtn').addEventListener('click', function(e) {
        e.preventDefault();
        openBypassPos();
    });

    scheduleAutoRefresh();
    scheduleAutoCloudUpload();
}

/* Entry point: tab lock → app init */
$(document).ready(async function () {
    const canProceed = await posTabLock.init();
    if (canProceed) initPosApp();
});

/* ══════════════════════════════════════════════════════════════
   PRODUCT ACTIONS
══════════════════════════════════════════════════════════════ */
function findProduct(id) { return allProducts.find(p => p.id === id); }

/* Smart/token search — same behaviour as DataTables' default "smart"
   filtering: the query is split on whitespace and EVERY token must be
   found somewhere in the searchable text (name + code), not necessarily
   adjacent or in order. This is what lets "para 500" match a product
   named "Paracetamol Tabs 500mg". */
function tokenizeQuery(q) {
    return (q || '').toLowerCase().split(/\s+/).filter(Boolean);
}

// Fuzzy subsequence match: true if every character of `needle` appears
// in `haystack`, in order, but not necessarily next to each other.
// e.g. "pra" matches "paracetamol" (p...r...a...) even though "pra"
// isn't a literal substring of it.
function isSubsequence(needle, haystack) {
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
function tokenMatchesWords(token, words, joined) {
    return words.some(function (w) {
        return w.indexOf(token) !== -1 || isSubsequence(token, w);
    }) || isSubsequence(token, joined);
}

// Ranks how good a match is so the closest matches float to the top.
// Higher = better. Per token, the best word-level match wins:
// exact word > word starts with token > literal substring > fuzzy subsequence.
// Then a bonus is added if the whole typed query lines up with the product
// name itself (so "lofnac 100" ranks "Lofnac 100mg" above "Lofnac Plus 100mg"),
// and a tiny penalty for longer names breaks remaining ties toward the more
// specific/shorter match.
function tokenScore(token, words, joined) {
    var best = 0;
    for (var i = 0; i < words.length; i++) {
        var w = words[i];
        if (w === token)             { if (100 > best) best = 100; continue; }
        if (w.indexOf(token) === 0)  { if (80  > best) best = 80;  continue; }
        if (w.indexOf(token) !== -1) { if (50  > best) best = 50;  continue; }
        if (isSubsequence(token, w)) { if (20  > best) best = 20;  continue; }
    }
    if (best === 0 && isSubsequence(token, joined)) best = 5;
    return best;
}

function scoreProduct(tokens, name, code) {
    const nameLower = (name || '').toLowerCase();
    const words = (nameLower + ' ' + (code || '').toLowerCase()).split(/\s+/).filter(Boolean);
    const joined = words.join('');
    let score = 0;
    for (let i = 0; i < tokens.length; i++) score += tokenScore(tokens[i], words, joined);

    const queryJoined = tokens.join(' ');
    if (nameLower === queryJoined) score += 1000;
    else if (nameLower.indexOf(queryJoined) === 0) score += 400;
    else if (nameLower.indexOf(queryJoined) !== -1) score += 150;

    score -= nameLower.length * 0.01;
    return score;
}

function filterProductList(q) {
    const tokens = tokenizeQuery(q);
    if (!tokens.length) return [];
    return allProducts
        .filter(p => {
            const words = ((p.name||'') + ' ' + (p.code||'')).toLowerCase().split(/\s+/).filter(Boolean);
            const joined = words.join('');
            return tokens.every(t => tokenMatchesWords(t, words, joined));
        })
        .map(p => ({ p, s: scoreProduct(tokens, p.name, p.code) }))
        .sort((a, b) => b.s - a.s)
        .map(x => x.p);
}

/* How much of a product is actually available for a given cart, once
   quantities already reserved in the OTHER cart (main vs bypass) are
   accounted for — stops the same last unit being sold twice at once. */
function qtyInCart(list, id) {
    const item = list.find(c => c.id === id);
    return item ? item.qty : 0;
}
function availableStock(id, forCart) {
    const p = findProduct(id);
    if (!p) return 0;
    let reserved = 0;
    if (forCart !== 'main')   reserved += qtyInCart(cart, id);
    if (forCart !== 'bypass') reserved += qtyInCart(bypassCart, id);
    return Math.max(0, p.stock - reserved);
}

/* Shared row-builder for the product search results — used by both the
   main POS and the Bypass POS so their markup and behaviour stay identical. */
function buildProductRowsHtml(products, forCart) {
    if (!products.length) {
        return '<div style="padding:10px 12px 6px;color:#595959;font-size:12px;text-align:center;">No products matched.</div>';
    }
    const clickFn      = forCart === 'bypass' ? 'bypassPrdRowClick'   : 'prdRowClick';
    const changeFn      = forCart === 'bypass' ? 'bypassPrdQtyChange' : 'prdQtyChange';
    const qtyIdPrefix    = forCart === 'bypass' ? 'bqinput_' : 'qinput_';
    let html = '';
    products.forEach(p => {
        const avail = availableStock(p.id, forCart);
        const oos = avail <= 0;
        html += `
        <div class="prd-row${oos?' prd-oos':''}" data-id="${p.id}">
            <a href="#" class="prd-link" onclick="event.preventDefault();${oos?'':clickFn+'('+p.id+')'}">
                <span class="prd-name">${escHtml(p.name)}</span>
                <span class="prd-code">${p.code?'(<span class="val">'+escHtml(p.code)+'</span>)':''}</span>
                <span class="prd-meta">${fmtNum(p.price)}/${escHtml(p.unit)}</span>
                <span class="prd-stock-tag">[<span class="val">${oos?'0':fmtQty(avail)}</span>]</span>
            </a>
            <input type="number" class="prd-qty-input" id="${qtyIdPrefix}${p.id}"
                   min="0" max="${avail}" step="0.01" autocomplete="off"
                   ${oos?'disabled':''}
                   onchange="${changeFn}(${p.id})">
        </div>`;
    });
    return html;
}

function prdRowClick(id) {
    const p = findProduct(id);
    if (!p) return;
    const avail = availableStock(id, 'main');
    if (avail <= 0) { toastr.warning(p.name + ' is out of stock or fully reserved in the bypass cart.'); return; }
    addToCart({ id:p.id, name:p.name, unit:p.unit, price:p.price, stock:p.stock, qty:1 });
    const q = document.getElementById('qinput_' + id);
    if (q) q.value = '';
    afterAddToCart();
}

function prdQtyChange(id) {
    const p = findProduct(id);
    if (!p) return;
    const input = document.getElementById('qinput_' + id);
    const qty   = parseFloat(input.value);
    if (!qty || qty <= 0) { input.value=''; return; }
    const avail = availableStock(id, 'main');
    if (qty > avail) {
        toastr.error('Quantity for ' + p.name + ' must be ≤ ' + fmtQty(avail));
        input.value=''; return;
    }
    addToCart({ id:p.id, name:p.name, unit:p.unit, price:p.price, stock:p.stock, qty });
    input.value='';
    afterAddToCart();
}

/* ══════════════════════════════════════════════════════════════
   CART
══════════════════════════════════════════════════════════════ */
function addToCart(item) {
    const existing = cart.find(c => c.id === item.id);
    if (existing) {
        toastr.error(
            '<strong>' + escHtml(item.name) + '</strong> is already in the cart.<br>Edit the quantity in the cart if needed.',
            'Already Added', { timeOut:3000, escapeHtml:false }
        );
        return;
    }
    cart.push({ ...item });
    saveCart(); renderCart();
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
    const maxAllowed = Math.max(0, item.stock - qtyInCart(bypassCart, id));
    if (newQty > maxAllowed) {
        toastr.warning('Only ' + fmtQty(maxAllowed) + ' available (some is reserved in the bypass cart).');
        document.getElementById('cqty_' + id).value = fmtQty(item.qty);
        return;
    }
    item.qty = newQty;
    const tEl = document.getElementById('ctot_' + id);
    if (tEl) tEl.textContent = fmtNum(item.qty * item.price);
    saveCart(); renderCartTotals();
}

function cartTotal() { return cart.reduce((s,c) => s + c.qty * c.price, 0); }

function renderCartTotals() {
    document.getElementById('cartTotalPill').textContent = fmtMoney(cartTotal());
}

function renderCart() {
    const tbody = document.getElementById('pos-cart-tbody');
    const table = document.getElementById('pos-cart-table');

    if (!cart.length) {
        tbody.innerHTML = '<tr id="pos-cart-empty-row"><td colspan="6" id="pos-cart-empty">No items in cart</td></tr>';
        document.getElementById('pos-checkout-btn').disabled = true;
        /* Hide the table's outer borders so the empty-state area is truly blank */
        table.classList.add('pos-cart-empty');
        renderCartTotals();
        return;
    }

    let html = '';
    cart.forEach(item => {
        html += `
        <tr id="crow_${item.id}">
            <td><span class="pcr-name" title="${escHtml(item.name)}">${escHtml(item.name)}</span></td>
            <td>${escHtml(item.unit)}</td>
            <td>${fmtNum(item.price)}</td>
            <td class="pcr-qty-col"><input class="pcr-qinput" id="cqty_${item.id}" type="number"
                   value="${item.qty.toFixed(2)}" min="0.01" max="${item.stock}" step="0.01"
                   onchange="updateCartQtyInput(${item.id}, this.value)"></td>
            <td id="ctot_${item.id}">${fmtNum(item.qty * item.price)}</td>
            <td><a href="#" class="pcr-remove" onclick="event.preventDefault();removeFromCart(${item.id})">✕</a></td>
        </tr>`;
    });
    tbody.innerHTML = html;
    document.getElementById('pos-checkout-btn').disabled = false;
    /* Restore table borders now that we have real rows */
    table.classList.remove('pos-cart-empty');
    renderCartTotals();
}

/* ══════════════════════════════════════════════════════════════
   CHECKOUT
══════════════════════════════════════════════════════════════ */
function openCheckout() {
    if (!cart.length) return;

    document.getElementById('checkoutTotalDisplay').textContent = fmtNum(cartTotal());

    document.querySelectorAll('.pm-card').forEach(c => c.classList.remove('active'));
    const cashCard = document.querySelector('.pm-card[data-pm="cash"]');
    if (cashCard) cashCard.classList.add('active');
    activePaymentMethod = 'cash';

    document.getElementById('amountPaidInput').value = '';
    document.getElementById('amountPaidWrap').style.display = 'block';
    document.getElementById('checkout-change-row').classList.remove('show','negative');

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

function refreshTransId() {
    const chars  = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const prefix = document.getElementById('posDate').value.replace(/-/g,'');
    let rand = '';
    for (let i=0; i<6; i++) rand += chars[Math.floor(Math.random()*chars.length)];
    document.getElementById('posTransId').value = prefix + rand;
}

function confirmSale() {
    if (!cart.length) return;
    const total = cartTotal();
    let paid;

    if (activePaymentMethod === 'cash') {
        const raw     = document.getElementById('amountPaidInput').value;
        const entered = parseFloat(raw);
        paid = (raw==='' || isNaN(entered) || entered<=0) ? total : entered;
        if (paid < total) { toastr.warning('Amount tendered is less than the total.'); return; }
    } else {
        paid = total;
    }

    const transId    = document.getElementById('posTransId').value;
    const date       = document.getElementById('posDate').value;
    const userName   = document.getElementById('posUserName').value;
    const branchId   = document.getElementById('posBranchId').value;
    const time       = new Date().toTimeString().slice(0,8);
    const deviceName = getDeviceName();
    const userAgent  = navigator.userAgent;

    const snapshot = cart.map(c => ({
        branch_product_id: c.id,
        product:           c.name,
        unit:              c.unit,
        price:              c.price,
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
    }));

    const originalCart = cart.map(c => Object.assign({}, c));
    if (!beginSaleJournal('main', snapshot)) {
        toastr.error('Sale could not be safely started. Nothing was cleared.', 'Storage Error');
        return;
    }
    cloudData = cloudData.concat(snapshot);
    if (!savePendingSales()) {
        cloudData = cloudData.slice(0, -snapshot.length);
        toastr.error('Sale was NOT completed because local storage could not save it. Nothing was cleared.', 'Storage Error');
        return;
    }
    updateSaleJournal('pending_saved', snapshot, 'main');

    lastSaleSnapshot = {
        transId,
        date,
        time,
        cashier:        userName,
        branchName:     document.getElementById('posBranchName').value,
        branchAddress:  document.getElementById('posBranchAddress').value,
        branchPhone:    document.getElementById('posBranchPhone').value,
        paymentMethod:  activePaymentMethod,
        total,
        paid,
        change:         Math.max(0, paid - total),
        items: cart.map(c => ({ name: c.name, unit: c.unit, price: c.price, qty: c.qty })),
    };

    cart = [];
    if (!saveCart()) {
        cart = originalCart;
        toastr.error('Sale was saved safely, but the cart could not be cleared. Refreshing will recover it safely.', 'Storage Warning');
        return;
    }
    finishSaleJournal();
    updatePendingBadge();
    renderCart(); refreshTransId();
    $('#checkoutModal').modal('hide');
    toastr.success('Sale recorded locally. Upload when online.', 'Done');
    document.getElementById('pos-search').focus();

    setTimeout(() => printReceipt(lastSaleSnapshot), 300);
}

/* ══════════════════════════════════════════════════════════════
   BYPASS POS — serve a second customer without touching the main
   cart. Confirmed sales are pushed into the SAME `cloudData` array
   (and therefore the same POS_PENDING_KEY) as the main cart, so
   pending-upload totals stay unified no matter which cart rang up
   the sale.
══════════════════════════════════════════════════════════════ */
function generateBypassTransId() {
    const chars  = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const prefix = _DATE.replace(/-/g,'');
    let rand = '';
    for (let i=0; i<6; i++) rand += chars[Math.floor(Math.random()*chars.length)];
    bypassTransId = prefix + rand;
}

function bypassPrdRowClick(id) {
    const p = findProduct(id);
    if (!p) return;
    const avail = availableStock(id, 'bypass');
    if (avail <= 0) { toastr.warning(p.name + ' is out of stock or fully reserved in the main cart.'); return; }
    bypassAddToCart({ id:p.id, name:p.name, unit:p.unit, price:p.price, stock:p.stock, qty:1 });
    const q = document.getElementById('bqinput_' + id);
    if (q) q.value = '';
    bypassAfterAddToCart();
}

function bypassPrdQtyChange(id) {
    const p = findProduct(id);
    if (!p) return;
    const input = document.getElementById('bqinput_' + id);
    const qty   = parseFloat(input.value);
    if (!qty || qty <= 0) { input.value=''; return; }
    const avail = availableStock(id, 'bypass');
    if (qty > avail) {
        toastr.error('Quantity for ' + p.name + ' must be ≤ ' + fmtQty(avail));
        input.value=''; return;
    }
    bypassAddToCart({ id:p.id, name:p.name, unit:p.unit, price:p.price, stock:p.stock, qty });
    input.value='';
    bypassAfterAddToCart();
}

function bypassAddToCart(item) {
    const existing = bypassCart.find(c => c.id === item.id);
    if (existing) {
        toastr.error(
            '<strong>' + escHtml(item.name) + '</strong> is already in the bypass cart.<br>Edit the quantity in the cart if needed.',
            'Already Added', { timeOut:3000, escapeHtml:false }
        );
        return;
    }
    bypassCart.push({ ...item });
    saveBypassCart(); bypassRenderCart();
}

function bypassRemoveFromCart(id) {
    bypassCart = bypassCart.filter(c => c.id !== id);
    saveBypassCart(); bypassRenderCart();
}

function bypassUpdateCartQtyInput(id, val) {
    const item = bypassCart.find(c => c.id === id);
    if (!item) return;
    const newQty = parseFloat(val) || 1;
    if (newQty <= 0) { bypassRemoveFromCart(id); return; }
    const maxAllowed = Math.max(0, item.stock - qtyInCart(cart, id));
    if (newQty > maxAllowed) {
        toastr.warning('Only ' + fmtQty(maxAllowed) + ' available (some is reserved in the main cart).');
        document.getElementById('bcqty_' + id).value = fmtQty(item.qty);
        return;
    }
    item.qty = newQty;
    const tEl = document.getElementById('bctot_' + id);
    if (tEl) tEl.textContent = fmtNum(item.qty * item.price);
    saveBypassCart(); bypassRenderCartTotals();
}

function bypassCartTotal() { return bypassCart.reduce((s,c) => s + c.qty * c.price, 0); }

function bypassRenderCartTotals() {
    document.getElementById('bypassCartTotalPill').textContent = fmtMoney(bypassCartTotal());
    document.getElementById('bypassConfirmSaleBtn').disabled = !bypassCart.length;
    document.getElementById('bypassClearAllBtn').disabled = !bypassCart.length;
}

function bypassClearCart() {
    if (!bypassCart.length) return;
    bypassCart = [];
    saveBypassCart();
    bypassRenderCart();
    document.getElementById('bypassAmountPaidInput').value = '';
    document.getElementById('bypass-checkout-change-row').classList.remove('show', 'negative');
}

function bypassRenderCart() {
    const tbody = document.getElementById('bypass-pos-cart-tbody');
    const table = document.getElementById('bypass-pos-cart-table');

    if (!bypassCart.length) {
        tbody.innerHTML = '<tr id="bypass-pos-cart-empty-row"><td colspan="6" id="bypass-pos-cart-empty">No items in cart</td></tr>';
        table.classList.add('pos-cart-empty');
        bypassRenderCartTotals();
        return;
    }

    let html = '';
    bypassCart.forEach(item => {
        html += `
        <tr id="brow_${item.id}">
            <td><span class="pcr-name" title="${escHtml(item.name)}">${escHtml(item.name)}</span></td>
            <td>${escHtml(item.unit)}</td>
            <td>${fmtNum(item.price)}</td>
            <td class="pcr-qty-col"><input class="pcr-qinput" id="bcqty_${item.id}" type="number"
                   value="${item.qty.toFixed(2)}" min="0.01" max="${item.stock}" step="0.01"
                   onchange="bypassUpdateCartQtyInput(${item.id}, this.value)"></td>
            <td id="bctot_${item.id}">${fmtNum(item.qty * item.price)}</td>
            <td><a href="#" class="pcr-remove" onclick="event.preventDefault();bypassRemoveFromCart(${item.id})">✕</a></td>
        </tr>`;
    });
    tbody.innerHTML = html;
    table.classList.remove('pos-cart-empty');
    bypassRenderCartTotals();
}

function bypassSelectPaymentMethod(pm, el) {
    bypassActivePaymentMethod = pm;
    document.querySelectorAll('#bypassPmGrid .pm-card').forEach(c => c.classList.remove('active'));
    if (el) el.classList.add('active');
    const wrap = document.getElementById('bypassAmountPaidWrap');
    wrap.style.display = pm === 'cash' ? 'block' : 'none';
    if (pm !== 'cash') document.getElementById('bypass-checkout-change-row').classList.remove('show');
}

function bypassCalcChange() {
    const total  = bypassCartTotal();
    const paid   = parseFloat(document.getElementById('bypassAmountPaidInput').value) || 0;
    const change = paid - total;
    const row    = document.getElementById('bypass-checkout-change-row');
    if (paid <= 0) { row.classList.remove('show'); return; }
    row.classList.add('show');
    row.classList.toggle('negative', change < 0);
    document.getElementById('bypass-checkout-change-label').textContent = change < 0 ? 'Short by' : 'Change';
    document.getElementById('bypass-checkout-change-value').textContent = 'MWK ' + fmtNum(Math.abs(change));
}

function bypassConfirmSale() {
    if (!bypassCart.length) return;
    const total = bypassCartTotal();
    let paid;

    if (bypassActivePaymentMethod === 'cash') {
        const raw     = document.getElementById('bypassAmountPaidInput').value;
        const entered = parseFloat(raw);
        paid = (raw==='' || isNaN(entered) || entered<=0) ? total : entered;
        if (paid < total) { toastr.warning('Amount tendered is less than the total.'); return; }
    } else {
        paid = total;
    }

    const date       = _DATE;
    const userName   = document.getElementById('posUserName').value;
    const branchId   = _BRANCH;
    const time       = new Date().toTimeString().slice(0,8);
    const deviceName = getDeviceName();
    const userAgent  = navigator.userAgent;

    const snapshot = bypassCart.map(c => ({
        branch_product_id: c.id,
        product:           c.name,
        unit:              c.unit,
        price:              c.price,
        transid:           bypassTransId,
        date,
        time,
        user:              userName,
        branch:            branchId,
        quantity:          c.qty,
        qty_before:        c.stock,
        qty_sold:          c.qty,
        qty_after:         Math.max(0, c.stock - c.qty),
        payment_method:    bypassActivePaymentMethod,
        amount_paid:       paid,
        slot:              '0',
        device_name:       deviceName,
        user_agent:        userAgent,
        bypass:            1,
    }));

    /* Same cloudData array / same POS_PENDING_KEY as the main cart —
       pending sales from either cart show up together in one list. */
    const originalBypassCart = bypassCart.map(c => Object.assign({}, c));
    if (!beginSaleJournal('bypass', snapshot)) {
        toastr.error('Sale could not be safely started. Nothing was cleared.', 'Storage Error');
        return;
    }
    cloudData = cloudData.concat(snapshot);
    if (!savePendingSales()) {
        cloudData = cloudData.slice(0, -snapshot.length);
        toastr.error('Sale was NOT completed because local storage could not save it. Nothing was cleared.', 'Storage Error');
        return;
    }
    updateSaleJournal('pending_saved', snapshot, 'bypass');

    lastSaleSnapshot = {
        transId: bypassTransId,
        date,
        time,
        cashier:        userName,
        branchName:     document.getElementById('posBranchName').value,
        branchAddress:  document.getElementById('posBranchAddress').value,
        branchPhone:    document.getElementById('posBranchPhone').value,
        paymentMethod:  bypassActivePaymentMethod,
        total,
        paid,
        change:         Math.max(0, paid - total),
        items: bypassCart.map(c => ({ name: c.name, unit: c.unit, price: c.price, qty: c.qty })),
    };

    bypassCart = [];
    if (!saveBypassCart()) {
        bypassCart = originalBypassCart;
        toastr.error('Sale was saved safely, but the bypass cart could not be cleared. Refreshing will recover it safely.', 'Storage Warning');
        return;
    }
    finishSaleJournal();
    updatePendingBadge();
    bypassRenderCart(); generateBypassTransId();
    toastr.success('Bypass sale recorded locally. Upload when online.', 'Done');
    document.getElementById('bypass-pos-search').focus();

    setTimeout(() => printReceipt(lastSaleSnapshot), 300);
}

function openBypassPos() {
    generateBypassTransId();
    loadBypassCart();
    bypassRenderCart();

    document.getElementById('bypassAmountPaidInput').value = '';
    document.getElementById('bypass-checkout-change-row').classList.remove('show','negative');
    document.querySelectorAll('#bypassPmGrid .pm-card').forEach(c => c.classList.remove('active'));
    const cashCard = document.querySelector('#bypassPmGrid .pm-card[data-pm="cash"]');
    if (cashCard) cashCard.classList.add('active');
    bypassActivePaymentMethod = 'cash';
    document.getElementById('bypassAmountPaidWrap').style.display = 'block';

    clearBypassSearch();
    $('#bypassPosModal').modal('show');
    setTimeout(() => document.getElementById('bypass-pos-search').focus(), 400);
}

/* ══════════════════════════════════════════════════════════════
   RECEIPT PRINTING (DESKTOP ONLY)
══════════════════════════════════════════════════════════════════════ */

const RECEIPT_CSS = `
    @page { size: 80mm auto; margin: 0; }
    * { box-sizing: border-box; }
    body { margin: 0; padding: 4mm 3mm; color: #000; line-height: 1.45; }
    .rcpt-center    { text-align: center; }
    .rcpt-store     { font-family: 'Playfair Display', Georgia, serif; font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
    .rcpt-address   { font-family: 'DM Sans', Arial, sans-serif; font-size: 10.5px; font-weight: 600; color: #000; margin-top: 2px; }
    .rcpt-phone     { font-family: 'DM Sans', Arial, sans-serif; font-size: 10.5px; font-weight: 600; color: #000; }
    .rcpt-rule      { border-top: 1px dashed #000; margin: 6px 0; }
    .rcpt-rule-dbl  { border-top: 1.5px solid #000; margin: 6px 0; }
    .rcpt-meta-row  { display: flex; justify-content: space-between; font-family: 'DM Sans', Arial, sans-serif; font-size: 10.5px; font-weight: 700; color: #000; margin: 1px 0; }
    .rcpt-item-block  { margin-bottom: 5px; }
    .rcpt-item-name   { font-family: 'DM Sans', Arial, sans-serif; font-size: 11.5px; font-weight: 700; color: #000; }
    .rcpt-item-meta   { display: flex; justify-content: space-between; font-family: 'DM Mono', 'Courier New', monospace; font-size: 11px; font-weight: 700; color: #000; }
    .rcpt-total-row { display: flex; justify-content: space-between; font-family: 'DM Sans', Arial, sans-serif; font-weight: 800; font-size: 15px; margin: 3px 0; }
    .rcpt-pay-row   { display: flex; justify-content: space-between; font-family: 'DM Sans', Arial, sans-serif; font-size: 11px; font-weight: 700; color: #000; margin: 2px 0; }
    .rcpt-operator  { font-family: 'DM Sans', Arial, sans-serif; font-size: 10.5px; font-weight: 700; color: #000; margin: 3px 0 1px; }
    .rcpt-foot      { text-align: center; font-family: 'DM Sans', Arial, sans-serif; font-size: 10px; margin-top: 10px; color: #333; }
`;

function buildReceiptHTML(sale) {
    if (!sale) return '';

    /* Amount paid always has a value: for cash it's what was tendered,
       for every other method (and if nothing was entered) it's the cart
       total — so change is simply 0 in that case. */
    const paid   = (sale.paid != null && sale.paid !== '') ? sale.paid : sale.total;
    const change = Math.max(0, paid - sale.total);

    const itemsHtml = sale.items.map(it => `
        <div class="rcpt-item-block">
            <div class="rcpt-item-name">${escHtml(it.name)}</div>
            <div class="rcpt-item-meta">
                <span>${fmtQty(it.qty)} ${escHtml(it.unit)} x ${fmtNum(it.price)}</span>
                <span>${fmtNum(it.qty * it.price)}</span>
            </div>
        </div>
    `).join('');

    return `
        <div class="rcpt-center">
            <div class="rcpt-store">${escHtml(sale.branchName || '')}</div>
            ${sale.branchAddress ? `<div class="rcpt-address">${escHtml(sale.branchAddress)}</div>` : ''}
            ${sale.branchPhone ? `<div class="rcpt-phone">Tel: ${escHtml(sale.branchPhone)}</div>` : ''}
        </div>
        <div class="rcpt-rule"></div>
        <div class="rcpt-meta-row"><span>Trans ID</span><span>${escHtml(sale.transId)}</span></div>
        <div class="rcpt-rule"></div>
        ${itemsHtml}
        <div class="rcpt-rule-dbl"></div>
        <div class="rcpt-total-row"><span>TOTAL</span><span>MWK ${fmtNum(sale.total)}</span></div>
        <div class="rcpt-pay-row"><span>Amount Paid</span><span>MWK ${fmtNum(paid)}</span></div>
        <div class="rcpt-pay-row"><span>Change</span><span>MWK ${fmtNum(change)}</span></div>
        <div class="rcpt-rule"></div>
        <div class="rcpt-operator">Operator Name: ${escHtml(sale.cashier)}</div>
        <div class="rcpt-meta-row"><span>DATE ${escHtml(sale.date)}</span><span>TIME ${escHtml(sale.time)}</span></div>
        <div class="rcpt-rule"></div>
        <div class="rcpt-foot">Thank you for shopping with us!</div>
    `;
}

/* Wraps buildReceiptHTML() in a full standalone document (own <html>,
   own RECEIPT_CSS) so it can be written into #receiptPrintFrame's own
   iframe window — a completely separate DOM/stylesheet from the main
   page. This is what lets us print ONLY the receipt: the iframe's own
   print() only ever sees this document, never the POS UI behind it. */
function buildReceiptDoc(sale) {
    return `<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Receipt</title>
<style>${RECEIPT_CSS}</style>
</head>
<body>
${buildReceiptHTML(sale)}
</body>
</html>`;
}

/* Writes the receipt document into #receiptPrintFrame's own iframe
   window and prints THAT window — not the main one. There is no way
   for any web page to skin, resize, reposition, or auto-confirm the
   browser's native print dialog (that's an OS-level control
   Chrome/Edge/Firefox all deliberately keep out of reach of page
   JS/CSS for security reasons), so the only way to avoid the cashier
   clicking "Print" twice — once on a custom modal, once on the real
   dialog — is to cut the custom modal out and go straight to the one
   dialog that actually matters. */
function printReceipt(sale) {
    if (!sale || !sale.items || !sale.items.length) return;
    const frame = document.getElementById('receiptPrintFrame');
    if (!frame) return;

    const frameWin = frame.contentWindow;
    const frameDoc = frameWin.document;

    frameDoc.open();
    frameDoc.write(buildReceiptDoc(sale));
    frameDoc.close();

    function cleanup() {
        frameWin.removeEventListener('afterprint', cleanup);
    }

    function doPrint() {
        frameWin.addEventListener('afterprint', cleanup);
        frameWin.focus();
        frameWin.print();

        /* Fallback cleanup in case the browser doesn't fire afterprint
           (some older/embedded browsers). */
        setTimeout(cleanup, 5000);
    }

    /* Give the iframe a tick to lay out the freshly-written document
       before invoking print — printing immediately after write() can
       race the layout in some browsers and produce a blank page. */
    setTimeout(doPrint, 50);
}

/* ══════════════════════════════════════════════════════════════
   UPLOAD
══════════════════════════════════════════════════════════════ */
function updatePendingBadge() {
    const n     = cloudData.length;
    const badge  = document.getElementById('posPendingBadge');
    if (n > 0) {
        badge.textContent  = n; badge.classList.add('show');
    } else {
        badge.classList.remove('show');
    }
}

function renderPendingModal() {
    const tbody = document.getElementById('pendingTbody');
    const total = cloudData.reduce((s,c) => s + (c.quantity * c.price), 0);
    document.getElementById('pendingTotalLabel').textContent = fmtNum(total);

    if (!cloudData.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:#595959;padding:30px;font-size:13px;">No pending sales.</td></tr>';
        return;
    }
    let html = '';
    cloudData.forEach(e => {
        html += `<tr>
            <td>${escHtml(e.product)}</td>
            <td class="text-center">${escHtml(e.unit)}</td>
            <td class="text-center">${fmtQty(e.quantity)}</td>
            <td class="text-center">${fmtNum(e.price)}</td>
            <td style="font-family:monospace;font-size:11px;">${escHtml(e.transid||'')}</td>
            <td class="text-center" style="font-family:monospace;">${escHtml(e.time||'')}</td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

/* ══════════════════════════════════════════════════════════════
   FAILED SALES — view / delete-one / clear-all
   Reads straight from failedToUploadSales on every render so the
   list is always in sync with what's actually in localStorage.
══════════════════════════════════════════════════════════════ */
function updateFailedBadge() {
    const n = loadFailedSales().length;
    const badge = document.getElementById('sdFailedTabBadge');
    if (n > 0) { badge.textContent = n; badge.style.display = 'inline-flex'; }
    else { badge.style.display = 'none'; }
}

function renderFailedModal() {
    const list  = loadFailedSales();
    const tbody = document.getElementById('sdFailedTbody');
    document.getElementById('sdFailedCountLabel').textContent = list.length;
    updateFailedBadge();

    if (!list.length) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:#595959;padding:30px;font-size:13px;">No failed sales.</td></tr>';
        return;
    }
    let html = '';
    list.forEach((e, idx) => {
        html += `<tr>
            <td>${escHtml(e.product)}</td>
            <td class="text-center">${escHtml(e.unit)}</td>
            <td class="text-center">${fmtQty(e.quantity)}</td>
            <td class="text-center">${fmtNum(e.price)}</td>
            <td class="text-center">${escHtml(e.date || '')}</td>
            <td class="sd-failed-reason">${escHtml(e.reason || 'upload_failed')}</td>
            <td class="text-center"><button type="button" class="sd-failed-del-btn" title="Delete" onclick="deleteFailedSale(${idx})"><i class="ri-close-line"></i></button></td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

function deleteFailedSale(index) {
    const list = loadFailedSales();
    if (index < 0 || index >= list.length) return;
    const removed = list.splice(index, 1)[0];
    if (!saveFailedSales(list)) {
        toastr.error('The failed sale could not be safely removed and was retained.', 'Storage Warning');
        return;
    }
    renderFailedModal();
}

function clearAllFailedSales() {
    const list = loadFailedSales();
    if (!list.length) { toastr.info('There is nothing to clear.'); return; }
    if (!confirm(
        'Delete all ' + list.length + ' failed sale record(s)? This cannot be undone.\n\n' +
        'An Excel copy will be downloaded automatically first, so this data is never lost.'
    )) return;
    // Always export before wiping — this is what guarantees a cleared failed
    // sale never becomes lost/stray data; it lives on in the downloaded file.
    downloadFailedSalesExcel(list);
    if (!saveFailedSales([])) {
        toastr.error('The Excel backup was created, but the local Failed Sales list could not be cleared. The records were retained.', 'Storage Warning');
        return;
    }
    renderFailedModal();
    toastr.success('Failed sales list cleared. An Excel backup was downloaded first.', 'Cleared');
}

/* Exports the failed-sales list (every original field — not just the ones
   shown in the table) to a static-named, timestamped .xls file the cashier
   can open in Excel. Pure client-side (Blob download), so it also works
   offline — which matters, since failed sales are exactly the kind of data
   that shows up when connectivity is unreliable. */
function loadLegacyFailedSales() {
    let list=[];
    try { list=JSON.parse(localStorage.getItem(POS_LEGACY_FAILED_KEY)||'[]'); } catch(e) {}
    return Array.isArray(list) ? list : [];
}
function saveLegacyFailedSales(list) {
    return storageWriteVerified(POS_LEGACY_FAILED_KEY, Array.isArray(list)?list:[]);
}
function updateLegacyFailedBadge() {
    const n=loadLegacyFailedSales().length, badge=document.getElementById('sdLegacyFailedTabBadge');
    if (!badge) return; badge.textContent=n; badge.style.display=n?'inline-flex':'none';
}
function renderLegacyFailedModal() {
    const list=loadLegacyFailedSales(), tbody=document.getElementById('sdLegacyFailedTbody');
    if (!tbody) return; document.getElementById('sdLegacyFailedCountLabel').textContent=list.length; updateLegacyFailedBadge();
    const df=document.getElementById('downloadLegacyFailedExcelBtn'), up=document.getElementById('uploadLegacyFailedSalesBtn'), cl=document.getElementById('clearLegacyFailedDataBtn');
    [df,up,cl].forEach(x=>{if(x)x.style.display=list.length?'':'none';});
    if(!list.length){tbody.innerHTML='<tr><td colspan="8" style="text-align:center;color:#595959;padding:30px;font-size:13px;">No legacy failed sales.</td></tr>';return;}
    tbody.innerHTML=list.map((e,i)=>`<tr>
      <td>${escHtml(e.product||'')}</td><td class="text-center">${escHtml(e.unit||'')}</td><td class="text-center">${fmtQty(e.quantity)}</td>
      <td class="text-center">${fmtNum(e.price)}</td><td style="font-family:monospace;font-size:11px;">${escHtml(e.transid||'')}</td>
      <td class="text-center">${escHtml(e.date||'')}</td><td class="sd-failed-reason">${escHtml(e.reason||'legacy_failed')}</td>
      <td class="text-center"><button type="button" class="btn btn-sm btn-outline-primary" onclick="retryOneLegacyFailedSale(${i})" title="Retry this sale"><i class="ri-upload-cloud-2-line"></i></button> <button type="button" class="sd-failed-del-btn" onclick="deleteLegacyFailedSale(${i})" title="Delete"><i class="ri-close-line"></i></button></td>
    </tr>`).join('');
}
function deleteLegacyFailedSale(index){ const list=loadLegacyFailedSales(); if(index<0||index>=list.length)return; list.splice(index,1); if(!saveLegacyFailedSales(list)){toastr.error('Legacy record was retained because storage could not be verified.','Storage Warning');return;} renderLegacyFailedModal(); }
function clearAllLegacyFailedSales(){ const list=loadLegacyFailedSales(); if(!list.length){toastr.info('There are no legacy failed sales.');return;} if(!confirm('Clear all '+list.length+' legacy failed sale record(s)? An Excel backup will be downloaded first.'))return; downloadLegacyFailedSalesExcel(list); if(!saveLegacyFailedSales([])){toastr.error('Backup created, but records were retained because local storage could not be verified.','Storage Warning');return;} renderLegacyFailedModal(); toastr.success('Legacy failed sales cleared after Excel backup.','Cleared'); }
function downloadLegacyFailedSalesExcel(list){ list=list||loadLegacyFailedSales(); if(!list.length){toastr.info('There is nothing to download.');return;} const cols=[['Trans ID',r=>r.transid||''],['Product',r=>r.product||''],['Unit',r=>r.unit||''],['Qty',r=>r.quantity??''],['Price',r=>r.price??''],['Date',r=>r.date||''],['Time',r=>r.time||''],['Branch Product ID',r=>r.branch_product_id||''],['Reason',r=>r.reason||'legacy_failed'],['Legacy Source',r=>r.legacy_source_key||'']]; const esc=v=>String(v??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); const html='<table><thead><tr>'+cols.map(c=>'<th>'+esc(c[0])+'</th>').join('')+'</tr></thead><tbody>'+list.map(r=>'<tr>'+cols.map(c=>'<td>'+esc(c[1](r))+'</td>').join('')+'</tr>').join('')+'</tbody></table>'; const blob=new Blob([html],{type:'application/vnd.ms-excel;charset=utf-8;'}); const url=URL.createObjectURL(blob),a=document.createElement('a'); a.href=url; a.download='legacy_failed_sales_'+new Date().toISOString().replace(/[:.]/g,'-')+'.xls'; document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),1000); toastr.success('Downloaded '+list.length+' legacy record(s) to Excel.','Exported'); }
async function retryOneLegacyFailedSale(index){ const list=loadLegacyFailedSales(); if(!list[index])return; await uploadLegacyFailedSales([list[index]]); }
async function uploadLegacyFailedSales(selected){ const list=selected||loadLegacyFailedSales(); if(!list.length){toastr.info('There are no legacy failed sales to retry.');return;} const btn=document.getElementById('uploadLegacyFailedSalesBtn'); if(btn)btn.disabled=true; posLoaderShow(); try { const clean=list.map(r=>{const x=Object.assign({},r);delete x.reason;delete x.failed_at;delete x.legacy_recovered;delete x.legacy_source_key;return x;}); const res=await fetch('/{{ request()->route("tenantName") }}/sales/retail/upload-sales',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-TOKEN':csrfToken(),'Cache-Control':'no-store'},body:new URLSearchParams({data:JSON.stringify(clean)})}); if(!res.ok){toastr.error('Server returned HTTP '+res.status+'. Legacy records were kept.','Upload Error');return;} const still=await res.json(); if(!Array.isArray(still)){toastr.error('Unexpected server response. Legacy records were kept.','Upload Error');return;} const keyOf=r=>(r.transid||'')+'|'+(r.branch_product_id||'')+'|'+(r.date||'')+'|'+(r.time||'')+'|'+(r.product||''); const failedKeys=new Set(still.map(keyOf)); const current=loadLegacyFailedSales(); const selectedKeys=new Set(list.map(keyOf)); const remaining=current.filter(r=>!selectedKeys.has(keyOf(r))||failedKeys.has(keyOf(r))).map(r=>failedKeys.has(keyOf(r))?Object.assign({},r,{reason:still.find(x=>keyOf(x)===keyOf(r))?._reason||r.reason,failed_at:new Date().toISOString()}):r); if(!saveLegacyFailedSales(remaining)){toastr.error('Upload completed, but local legacy storage could not be verified; records were retained.','Storage Warning');return;} renderLegacyFailedModal(); toastr.success((list.length-still.length)+' legacy record(s) uploaded; '+still.length+' remain.','Legacy Upload'); }catch(e){toastr.error('Network error — legacy records were kept.','Offline');}finally{posLoaderHide();if(btn)btn.disabled=false;} }

function downloadFailedSalesExcel(list) {
    list = list || loadFailedSales();
    if (!list.length) { toastr.info('There is nothing to download.'); return; }

    const cols = [
        ['Trans ID',       r => r.transid || ''],
        ['Product',        r => r.product || ''],
        ['Unit',           r => r.unit || ''],
        ['Quantity',       r => (r.quantity != null ? r.quantity : '')],
        ['Price',          r => (r.price != null ? r.price : '')],
        ['Date',           r => r.date || ''],
        ['Time',           r => r.time || ''],
        ['Payment Method', r => r.payment_method || ''],
        ['User',           r => r.user || ''],
        ['Branch',         r => r.branch || ''],
        ['Reason',         r => r.reason || ''],
        ['Failed At',      r => r.failed_at || ''],
    ];

    let tableHtml = '<tr>' + cols.map(c => '<th>' + escHtml(c[0]) + '</th>').join('') + '</tr>';
    list.forEach(row => {
        tableHtml += '<tr>' + cols.map(c => '<td>' + escHtml(String(c[1](row))) + '</td>').join('') + '</tr>';
    });

    const html = '<html><head><meta charset="UTF-8"></head><body>' +
        '<table border="1">' + tableHtml + '</table></body></html>';

    const blob = new Blob(['\ufeff' + html], { type: 'application/vnd.ms-excel;charset=UTF-8' });

    // Static, deterministic filename pattern (branch + date/time) — no
    // random suffixes — so files are predictable and easy to find later.
    const branchSlug = String(_BRANCH || 'branch').replace(/[^A-Za-z0-9_-]/g, '');
    const stamp = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
    const filename = 'failed-sales-' + branchSlug + '-' + stamp + '.xls';

    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    setTimeout(() => URL.revokeObjectURL(url), 1000);

    toastr.success('Downloaded ' + list.length + ' failed sale record(s) to Excel.', 'Exported');
}

/* Retries uploading every row currently in failedToUploadSales through the
   same /sales/retail/upload-sales endpoint posUpload() uses. Because
   appendToFailedLog() now keeps the complete original row, this resend is
   identical in shape to a normal upload — nothing partial, nothing stray.
   Rows the server accepts are removed from the failed list; rows it still
   rejects stay, with their reason refreshed from the server's response. */
async function uploadFailedSales() {
    const list = loadFailedSales();
    if (!list.length) { toastr.info('There are no failed sales to upload.'); return; }

    posLoaderShow();
    const btn = document.getElementById('uploadFailedSalesBtn');
    if (btn) btn.disabled = true;

    // Strip only the local bookkeeping fields — everything the server
    // needs (branch_product_id, transid, date, time, etc) travels as-is.
    const toUpload = list.map(row => {
        const clean = Object.assign({}, row);
        delete clean.reason;
        delete clean.failed_at;
        return clean;
    });

    try {
        const res = await fetch('/{{ request()->route("tenantName") }}/sales/retail/upload-sales', {
            method: 'POST',
            headers: {
                'Content-Type':  'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN':  csrfToken(),
                'Cache-Control': 'no-store',
            },
            body: new URLSearchParams({ data: JSON.stringify(toUpload) }),
        });

        if (!res.ok) {
            toastr.error('Server returned HTTP ' + res.status + '. Failed sales kept — please retry.', 'Upload Error');
            return;
        }

        let stillFailed;
        try {
            stillFailed = await res.json();
        } catch (parseErr) {
            toastr.error('Server response was not valid JSON. Failed sales kept — please retry.', 'Parse Error');
            return;
        }

        if (!Array.isArray(stillFailed)) {
            toastr.error('Unexpected server response shape. Failed sales kept — please retry.', 'Error');
            return;
        }

        const keyOf = r => (r.transid || '') + '|' + (r.branch_product_id || '') + '|' + (r.date || '') + '|' + (r.time || '');

        if (stillFailed.length === 0) {
            saveFailedSales([]);
            renderFailedModal();
            toastr.success('All ' + toUpload.length + ' previously failed sale(s) uploaded successfully.', 'Done');
            refreshPaymentSummaryPane();
        } else {
            // Same success/failure matching posUpload() uses. Rows the
            // server accepted this time are dropped from the failed list;
            // rows still rejected are kept with an updated reason so
            // nothing here ever silently disappears.
            const stillFailedByKey = new Map(stillFailed.map(r => [keyOf(r), r]));
            const remaining = list
                .filter(r => stillFailedByKey.has(keyOf(r)))
                .map(r => {
                    const match = stillFailedByKey.get(keyOf(r));
                    return Object.assign({}, r, {
                        reason:    (match && match._reason) || r.reason,
                        failed_at: new Date().toISOString(),
                    });
                });
            saveFailedSales(remaining);
            renderFailedModal();
            const successCount = toUpload.length - stillFailed.length;
            toastr.warning(
                successCount + ' uploaded. ' + stillFailed.length + ' still could not be uploaded and ' +
                (stillFailed.length === 1 ? 'remains' : 'remain') + ' in Failed Sales.',
                'Partial Upload'
            );
            refreshPaymentSummaryPane();
        }
    } catch (networkErr) {
        toastr.error('Network error — no failed sales were changed. Check your connection and retry.', 'Offline');
    } finally {
        posLoaderHide();
        if (btn) btn.disabled = false;
    }
}

/* ══════════════════════════════════════════════════════════════
   SALES DATA MODAL — tab switching + last-sync label
══════════════════════════════════════════════════════════════ */
function formatLastSync(d) {
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const day   = String(d.getDate()).padStart(2, '0');
    const month = months[d.getMonth()];
    const year  = d.getFullYear();
    const hh    = String(d.getHours()).padStart(2, '0');
    const mm    = String(d.getMinutes()).padStart(2, '0');
    const ss    = String(d.getSeconds()).padStart(2, '0');
    return `${day} ${month} ${year}, ${hh}:${mm}:${ss}`;
}

function updateLastSyncLabel() {
    const el = document.getElementById('sdLastSyncLabel');
    if (el) el.textContent = formatLastSync(PAGE_LOADED_AT);
}

function setLegacyFooter(show) {
    const hasLegacy = loadLegacyFailedSales().length > 0;
    ['downloadLegacyFailedExcelBtn','uploadLegacyFailedSalesBtn','clearLegacyFailedDataBtn'].forEach(id=>{const e=document.getElementById(id);if(e)e.style.display=(show && hasLegacy)?'':'none';});
    ['downloadFailedExcelBtn','uploadFailedSalesBtn','clearFailedDataBtn'].forEach(id=>{const e=document.getElementById(id);if(e)e.style.display=show?'none':'';});
}

function switchSalesDataTab(tab) {
    const cloudTabBtn  = document.getElementById('sdCloudTabBtn');
    const recentTabBtn = document.getElementById('sdRecentTabBtn');
    const failedTabBtn = document.getElementById('sdFailedTabBtn');
    const legacyTabBtn = document.getElementById('sdLegacyFailedTabBtn');
    const cloudPane    = document.getElementById('sdCloudPane');
    const recentPane   = document.getElementById('sdRecentPane');
    const failedPane   = document.getElementById('sdFailedPane');
    const legacyPane   = document.getElementById('sdLegacyFailedPane');
    const cloudFooter  = document.getElementById('sdCloudFooter');
    const failedFooter = document.getElementById('sdFailedFooter');

    cloudTabBtn.classList.toggle('active', tab === 'cloud');
    recentTabBtn.classList.toggle('active', tab === 'recent');
    failedTabBtn.classList.toggle('active', tab === 'failed');
    legacyTabBtn.classList.toggle('active', tab === 'legacy');

    cloudPane.style.display  = tab === 'cloud'  ? '' : 'none';
    recentPane.style.display = tab === 'recent' ? '' : 'none';
    failedPane.style.display = tab === 'failed' ? '' : 'none';
    legacyPane.style.display = tab === 'legacy' ? '' : 'none';

    cloudFooter.style.display  = tab === 'cloud'  ? '' : 'none';
    failedFooter.style.display = (tab === 'failed' || tab === 'legacy') ? '' : 'none';

    if (tab === 'recent') updateLastSyncLabel();
    if (tab === 'failed') { renderFailedModal(); setLegacyFooter(false); }
    if (tab === 'legacy') { renderLegacyFailedModal(); setLegacyFooter(true); }
    if (tab === 'cloud' || tab === 'recent') setLegacyFooter(false);
}

async function posUpload(isAuto = false) {
    if (posUploadInProgress) return;
    if (!cloudData.length) { toastr.info('Nothing to upload.'); return; }

    posUploadInProgress = true;
    posLoaderShow();
    document.getElementById('pendingUploadBtn').disabled = true;

    const toUpload = [...cloudData];

    try {
        const res = await fetch('/{{ request()->route("tenantName") }}/sales/retail/upload-sales', {
            method: 'POST',
            headers: {
                'Content-Type':  'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN':  csrfToken(),
                'Cache-Control': 'no-store',
            },
            body: new URLSearchParams({ data: JSON.stringify(toUpload) }),
        });

        if (!res.ok) {
            toastr.error('Server returned HTTP ' + res.status + '. All rows kept — please retry.', 'Upload Error');
            return;
        }

        let failed;
        try {
            failed = await res.json();
        } catch(parseErr) {
            toastr.error('Server response was not valid JSON. All rows kept — please retry.', 'Parse Error');
            return;
        }

        if (!Array.isArray(failed)) {
            toastr.error('Unexpected server response shape. All rows kept — please retry.', 'Error');
            return;
        }

        if (failed.length === 0) {
            if (!storageWriteVerified(POS_PENDING_KEY, [])) {
                cloudData = toUpload;
                toastr.error('Server accepted the sales, but this device could not safely clear its local queue. The sales were retained for recovery.', 'Storage Warning');
                return;
            }
            cloudData = [];
            updatePendingBadge();
            toastr.success('All ' + toUpload.length + ' sales uploaded successfully.', 'Done');
            if (!isAuto) { $('#pendingModal').modal('hide'); }
            refreshPaymentSummaryPane();
        } else {
            // The server has told us, definitively, which specific rows it
            // could not accept (bad/missing product reference, etc). Those
            // are not retryable — retrying the same bad row forever just
            // re-fails it and re-logs it. So: remove every row the server
            // responded about (success AND failure) from pendingSales, and
            // silently move the rejected ones into failedToUploadSales for
            // the cashier to review and delete at their own pace.
            const failedKeys = new Set(failed.map(r => r.transid + '|' + r.branch_product_id + '|' + r.date + '|' + r.time));
            const existingFailed = loadFailedSales();
            const failedRows = failed.map(row => {
                const originalRow = Object.assign({}, row);
                delete originalRow._reason;
                return Object.assign(originalRow, {
                    reason: row._reason || 'server_rejected',
                    failed_at: new Date().toISOString(),
                });
            });
            if (!storageWriteVerified(POS_FAILED_KEY, failedRows.concat(existingFailed))) {
                toastr.error('The server rejected some sales, but this device could not safely save the rejected records. ALL pending sales were retained for retry.', 'Storage Error');
                return;
            }
            const nextPending = cloudData.filter(r =>
                !failedKeys.has(r.transid + '|' + r.branch_product_id + '|' + r.date + '|' + r.time)
            );
            if (!storageWriteVerified(POS_PENDING_KEY, nextPending)) {
                toastr.error('Some sales were accepted/rejected by the server, but the local queue could not be safely updated. The original pending queue was retained.', 'Storage Error');
                return;
            }
            cloudData = nextPending; updatePendingBadge();
            renderFailedModal(); updateFailedBadge();
            const successCount = toUpload.length - failed.length;
            toastr.warning(
                successCount + ' uploaded. ' + failed.length + ' could not be uploaded and ' +
                (failed.length === 1 ? 'was' : 'were') + ' moved to Failed Sales.',
                'Partial Upload'
            );
            refreshPaymentSummaryPane();
        }
    } catch (networkErr) {
        toastr.error('Network error — no rows removed. Check your connection and retry.', 'Offline');
    } finally {
        posLoaderHide();
        document.getElementById('pendingUploadBtn').disabled = false;
        posUploadInProgress = false;
    }
}

/* ══════════════════════════════════════════════════════════════
   PAYMENT SUMMARY — live refresh
══════════════════════════════════════════════════════════════ */
function refreshPaymentSummaryPane() {
    const branch = document.getElementById('posBranchId').value;
    const date   = document.getElementById('posDate').value;
    /* Route only accepts POST — must match that here (was GET, which
       triggered a 405 MethodNotAllowedHttpException on every call,
       including the one fired from posUpload() after a sales upload). */
    const url = '/{{ request()->route("tenantName") }}/sales/retail/payment-summary';
    fetch(url, {
        method: 'POST',
        cache: 'no-store',
        headers: {
            'Content-Type':     'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN':     csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: new URLSearchParams({ _token: csrfToken(), branch, date, _ts: Date.now() }),
    })
        .then(res => res.json().then(json => ({ ok: res.ok, status: res.status, json })))
        .then(({ ok, status, json }) => {
            if (!ok || !json || json.status !== 'success') {
                console.error('refreshPaymentSummaryPane: bad response', status, json);
                toastr.error('Payment totals could not be refreshed (server said: ' +
                    (json && json.message ? json.message : ('HTTP ' + status)) + ').', 'Payment Summary');
                return;
            }
            const byMethod = json.data.by_method || {};
            const total    = json.data.total || 0;
            const wrap = document.getElementById('ivPaymentRowsWrap');
            if (wrap) {
                wrap.querySelectorAll('.pay-summary-row').forEach(row => {
                    const pmId    = row.dataset.pm;
                    const valueEl = row.querySelector('.psr-value');
                    if (valueEl) valueEl.textContent = 'MWK ' + fmtNum(byMethod[pmId] || 0);
                });
            }
            const grandEl = document.getElementById('ivPaymentGrandTotal');
            if (grandEl) grandEl.textContent = 'MWK ' + fmtNum(total);
        })
        .catch(err => {
            console.error('refreshPaymentSummaryPane:', err);
            toastr.error('Could not reach the server to refresh payment totals.', 'Payment Summary');
        });
}

/* ══════════════════════════════════════════════════════════════
   INTERVAL SALES — DOM helpers
══════════════════════════════════════════════════════════════ */
function recalcIntervalGrandTotal() {
    const tbody = document.getElementById('ivIntervalTbody');
    if (!tbody) return;
    let grand = 0;
    tbody.querySelectorAll('.iv-amount-link').forEach(a => {
        grand += parseFloat(a.textContent.replace(/,/g,'')) || 0;
    });
    const dataRows = tbody.querySelectorAll('tr[id^="ivrow_"]');
    if (dataRows.length === 0) {
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

function addIntervalRowToView(dbId, slot, sales, userName) {
    const tbody = document.getElementById('ivIntervalTbody');
    if (!tbody) return;
    const emptyRow = document.getElementById('ivEmptyRow');
    if (emptyRow) emptyRow.remove();
    const oldTotal = document.getElementById('ivTotalRow');
    if (oldTotal) oldTotal.remove();
    const safeSlot = slot.replace(/\\/g,'\\\\').replace(/'/g,"\\'");
    const tr = document.createElement('tr');
    tr.id = 'ivrow_' + dbId;
    tr.dataset.slot = slot;
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

function removeSlotFromDropdown(intervalId, newRowDbId) {
    const select = document.getElementById('intervalSlotSelect');
    if (!select) return;
    const viewRow = document.getElementById('ivrow_' + newRowDbId);
    if (viewRow) viewRow.dataset.intervalId = intervalId;
    const opt = select.querySelector('option[value="' + intervalId + '"]');
    if (opt) opt.remove();
    if (select.options.length === 0) {
        const body   = document.getElementById('intervalModalBody');
        const footer = document.getElementById('intervalModalFooter');
        if (body) {
            body.innerHTML = `<div id="ivAllDoneMsg" style="text-align:center;padding:20px;color:#595959;font-size:13px;">
                <i class="ri-check-double-line" style="color:#16a34a;font-size:24px;display:block;margin-bottom:8px;"></i>
                All intervals have been entered for today.
            </div>`;
        }
        if (footer) footer.style.display = 'none';
        return;
    }
    const firstOpt = select.options[0];
    const nextLabel = document.getElementById('ivNextSlotLabel');
    if (nextLabel && firstOpt) nextLabel.textContent = firstOpt.dataset.slot || firstOpt.text;
}

function addSlotBackToDropdown(intervalId, slotLabel) {
    if (!intervalId) return;
    const body   = document.getElementById('intervalModalBody');
    const footer = document.getElementById('intervalModalFooter');
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
══════════════════════════════════════════════════════════════ */
function submitIntervalSale() {
    const salesInput = document.getElementById('intervalSalesInput');
    const sales = parseFloat(salesInput ? salesInput.value : '');
    if (isNaN(sales) || sales < 0) { toastr.warning('Enter a valid sales amount (0 or more).'); return; }
    if (cloudData.length > 0) {
        toastr.warning('Upload pending POS sales before entering interval sales.');
        return;
    }
    const select     = document.getElementById('intervalSlotSelect');
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
            'Content-Type':    'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN':    csrfToken(),
            'X-Requested-With':'XMLHttpRequest',
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
            toastr.error(msg, 'Validation'); return;
        }
        if (status === 409) { toastr.error(data.message || 'Already entered for today.'); return; }
        if (status === 404) { toastr.error(data.message || 'Interval not found.'); return; }
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
        toastr.error(data.message || ('Server error (' + status + ').'), 'Error');
    })
    .catch(() => {
        posLoaderHide(); btn.disabled = false;
        toastr.error('Could not reach the server — check your connection.', 'Network Error');
    });
}

/* ══════════════════════════════════════════════════════════════
   INTERVAL SALES — Edit
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

function submitEditInterval() {
    const newSales = parseFloat(document.getElementById('editIvSales').value);
    if (isNaN(newSales) || newSales < 0) { toastr.warning('Sales must be 0 or greater.'); return; }
    const btn     = document.getElementById('editIvSubmitBtn');
    btn.disabled  = true;
    posLoaderShow();
    const ivId    = document.getElementById('editIvId').value;
    const oldSales= parseFloat(document.getElementById('editIvOldSales').value) || 0;
    fetch('/{{ request()->route("tenantName") }}/sales/retail/edit-interval-sale', {
        method: 'POST',
        headers: {
            'Content-Type':    'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN':    csrfToken(),
            'X-Requested-With':'XMLHttpRequest',
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
        posLoaderHide(); btn.disabled = false;
        if (status === 200 && data.status === 'success') {
            const row = document.getElementById('ivrow_' + ivId);
            if (row) {
                const link = row.querySelector('.iv-amount-link');
                if (link) {
                    link.textContent = fmtNum(data.data.sales);
                    const safeSlot = currentIvSlot.replace(/\\/g,'\\\\').replace(/'/g,"\\'");
                    link.setAttribute('onclick',
                        `event.preventDefault();openEditIntervalFromView(${ivId},'${safeSlot}',${data.data.sales})`);
                }
            }
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
        posLoaderHide(); btn.disabled = false;
        toastr.error('Could not update. Check your connection.');
    });
}

/* ══════════════════════════════════════════════════════════════
   INTERVAL SALES — Delete
══════════════════════════════════════════════════════════════ */
function triggerIvDelete() {
    document.getElementById('ivDeleteSlotLabel').textContent = currentIvSlot;
    $('#editIntervalModal').modal('hide');
    setTimeout(() => $('#ivDeleteConfirmModal').modal('show'), 300);
}

function executeDeleteInterval() {
    posLoaderShow();
    const ivId    = currentIvId;
    const viewRow = document.getElementById('ivrow_' + ivId);
    const intervalId = viewRow ? viewRow.dataset.intervalId : null;
    const slotLabel  = viewRow ? (viewRow.dataset.slot || currentIvSlot) : currentIvSlot;
    fetch('/{{ request()->route("tenantName") }}/sales/retail/delete-interval-sale', {
        method: 'POST',
        headers: {
            'Content-Type':    'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN':    csrfToken(),
            'X-Requested-With':'XMLHttpRequest',
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
    .catch(() => { posLoaderHide(); toastr.error('Could not delete. Check your connection.'); });
}

/* ══════════════════════════════════════════════════════════════
   VIEW INTERVAL — toggle intervals ↔ payments
══════════════════════════════════════════════════════════════ */
function toggleIntervalView() {
    const ivPane = document.getElementById('iv-intervals-pane');
    const pmPane = document.getElementById('iv-payments-pane');
    const icon   = document.getElementById('ivToggleIcon');
    const btn    = document.getElementById('ivToggleBtn');
    const showInt = pmPane.style.display === 'none';
    if (showInt) {
        ivPane.style.display='none'; pmPane.style.display='';
        icon.className='ri-time-line'; btn.title='View intervals';
        refreshPaymentSummaryPane();
    } else {
        ivPane.style.display=''; pmPane.style.display='none';
        icon.className='ri-bank-card-line'; btn.title='View payment breakdown';
    }
}

function resetIntervalView() {
    document.getElementById('iv-intervals-pane').style.display='';
    document.getElementById('iv-payments-pane').style.display='none';
    document.getElementById('ivToggleIcon').className='ri-bank-card-line';
    document.getElementById('ivToggleBtn').title='View payment breakdown';
}

/* ══════════════════════════════════════════════════════════════
   CALCULATOR
══════════════════════════════════════════════════════════════ */
let calcExpr = '';
function calcFormatDisplay(str) {
    if (!str || str==='-') return str||'';
    let neg=str.charAt(0)==='-'; if(neg) str=str.slice(1);
    const parts=str.split('.');
    let intPart=parts[0].replace(/\B(?=(\d{3})+(?!\d))/g,',');
    let result=(neg?'-':'')+(intPart||'0');
    if(parts.length>1) result+='.'+parts[1];
    return result;
}
function calcFormatExpr(expr) { return expr.replace(/\d+\.?\d*/g,m=>calcFormatDisplay(m)); }
function calcRender() {
    document.getElementById('calcExpression').textContent=calcFormatExpr(calcExpr);
    const lastVal=calcExpr.split(/[+\-*/]/).pop();
    document.getElementById('calcDisplay').textContent=lastVal===''?'0':calcFormatDisplay(lastVal);
}
function calcDigit(d) {
    if(d==='.'){ const lv=calcExpr.split(/[+\-*/]/).pop(); if(lv.includes('.')) return; }
    calcExpr+=d; calcRender();
}
function calcOp(op) {
    if(!calcExpr) return;
    const lc=calcExpr.slice(-1);
    calcExpr='+-*/'.includes(lc)?calcExpr.slice(0,-1)+op:calcExpr+op;
    calcRender();
}
function calcClear()     { calcExpr=''; calcRender(); }
function calcBackspace() { calcExpr=calcExpr.slice(0,-1); calcRender(); }
function calcPercent() {
    const parts=calcExpr.split(/([+\-*/])/);
    const last=parts.pop();
    if(last){ parts.push((parseFloat(last)/100).toString()); calcExpr=parts.join(''); calcRender(); }
}
function calcEquals() {
    if(!calcExpr) return;
    try {
        const safe=calcExpr.replace(/[^0-9+\-*/.()]/g,'');
        const result=Function('"use strict";return ('+safe+')')();
        calcExpr=(Math.round(result*100000)/100000).toString();
        calcRender();
    } catch(e) { document.getElementById('calcDisplay').textContent='Error'; }
}
</script>
@endsection]o