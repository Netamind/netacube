@extends('operations.retail.dashboard')
@section('content')

@php
    use Carbon\Carbon;

    $branches = DB::connection('tenant')->table('branches')->where('status','active')->orderBy('name')->get();
    $pref     = DB::connection('tenant')->table('user_filters')->where('user_id', Auth::id())->first();

    $selectedBranch = null;
    if ($pref && $pref->branch_id) {
        $selectedBranch = DB::connection('tenant')->table('branches')->find($pref->branch_id);
    }

    $today       = Carbon::today()->toDateString();
    $displayDate = Carbon::today()->format('d F Y');
    $branchName  = $selectedBranch->name ?? '— Select Branch —';

    $sales      = collect();
    $transCount = 0;
    $sysTotal   = 0;

    if ($selectedBranch) {
        $sales = DB::connection('tenant')
            ->table('retail_system_sales as s')
            ->join('retail_branch_products as rbp', 'rbp.id', '=', 's.branch_product_id')
            ->join('retail_base_products as bp',    'bp.id',  '=', 'rbp.base_product_id')
            ->leftJoin('users as u', 'u.id', '=', DB::raw("CAST(s.user AS UNSIGNED)"))
            ->where('rbp.branch_id', $selectedBranch->id)
            ->where('s.date', $today)
            ->orderBy('s.id', 'asc')
            ->select(
                's.id', 's.transid', 's.date', 's.time', 's.product', 's.unit',
                's.price', 's.quantity', 's.rquantity', 's.qty_before', 's.qty_sold',
                's.qty_after', 's.payment_method', 's.amount_paid', 's.slot', 's.user',
                's.branch_product_id',
                'u.name as user_name'
            )
            ->get();

        $transCount = DB::connection('tenant')
            ->table('retail_system_sales as s')
            ->join('retail_branch_products as rbp', 'rbp.id', '=', 's.branch_product_id')
            ->where('rbp.branch_id', $selectedBranch->id)
            ->where('s.date', $today)
            ->distinct('s.transid')
            ->count('s.transid');

        $sysTotal = $sales->sum(fn($r) => (float)$r->quantity * (float)$r->price);
    }

    $intervalSales = collect();
    $intervalTotal = 0;
    if ($selectedBranch) {
        $intervalSales = DB::connection('tenant')
            ->table('retail_interval_sales as ris')
            ->join('retail_intervals as ri', 'ri.id', '=', 'ris.interval_id')
            ->leftJoin('users as u', 'u.id', '=', 'ris.user_id')
            ->where('ris.branch_id', $selectedBranch->id)
            ->where('ris.date', $today)
            ->orderBy('ri.sort_order')
            ->select('ris.id', 'ri.slot', 'ris.sales', 'ris.interval_id', 'u.name as user_name')
            ->get();

        $intervalTotal = $intervalSales->sum('sales');
    }

    $detailsRows  = collect();
    $physicalCash = 0;
    $grandManual  = 0;
    if ($selectedBranch) {
        $sysPerSlot = DB::connection('tenant')
            ->table('retail_system_sales as s')
            ->join('retail_branch_products as rbp', 'rbp.id', '=', 's.branch_product_id')
            ->where('rbp.branch_id', $selectedBranch->id)
            ->where('s.date', $today)
            ->selectRaw('s.slot, SUM(s.quantity * s.price) as sys_sales')
            ->groupBy('s.slot')
            ->pluck('sys_sales', 'slot');

        $detailsRows = $intervalSales->map(function ($row) use ($sysPerSlot) {
            $sys = (float)($sysPerSlot[$row->slot] ?? 0);
            $man = (float)$row->sales;
            return (object)[
                'slot'      => $row->slot,
                'user_name' => $row->user_name,
                'sys_sales'    => $sys,
                'manual_sales' => $man,
                'diff'         => $man - $sys,
            ];
        });

        $grandManual  = $intervalSales->sum('sales');
        $physicalCash = (float)(DB::connection('tenant')
            ->table('retail_physical_cash')
            ->where('branch_id', $selectedBranch->id)
            ->where('date', $today)
            ->value('amount') ?? 0);
    }

    // Payment summary by method for Details modal
    $paymentSummaryByMethod = collect();
    $paymentMethodsForDetails = [
        ['id' => 'cash',   'label' => 'Cash',         'icon' => 'ri-money-dollar-box-line'],
        ['id' => 'airtel', 'label' => 'Airtel Money',  'icon' => 'ri-phone-line'],
        ['id' => 'mpamba', 'label' => 'Mpamba',        'icon' => 'ri-phone-line'],
        ['id' => 'bank',   'label' => 'Bank',          'icon' => 'ri-bank-line'],
    ];
    if ($selectedBranch) {
        $paymentSummaryByMethod = DB::connection('tenant')
            ->table('retail_system_sales as s')
            ->join('retail_branch_products as rbp', 'rbp.id', '=', 's.branch_product_id')
            ->where('rbp.branch_id', $selectedBranch->id)
            ->where('s.date', $today)
            ->select('s.payment_method', DB::raw('SUM(s.quantity * s.price) as total'))
            ->groupBy('s.payment_method')
            ->pluck('total', 'payment_method');
    }

    $tableTitle = $branchName . ' System Sales [' . $displayDate . ']';
@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<style>
/* ── DataTable export buttons ──────────────────────────────────────── */
.dt-buttons .btn { background:transparent !important; background-image:none !important; box-shadow:none !important; border-color:#5bc0de; color:#5bc0de; }
.dt-buttons .btn:hover { background:#5bc0de !important; color:#fff; }

/* ── Card chrome ────────────────────────────────────────────────────── */
.card-header { padding:0.5rem 1.5rem !important; background:linear-gradient(to right,#4B5EBD,#576CC0); color:#fff; border-radius:10px 10px 0 0 !important; flex-wrap:wrap; gap:8px; }
.card-body   { padding:0 1.5rem 1.5rem 1.5rem !important; }
.card        { border:none; box-shadow:0 4px 8px rgba(0,0,0,0.1); border-radius:10px; }
.card-header h4 { color:#fff; font-weight:600; margin-bottom:0; display:flex; align-items:center; }
.card-header .btn-light { height:28px; padding:0 10px; display:flex; align-items:center; justify-content:center; line-height:1; }
.card-header .btn-light:hover { background-color:#f8f9fa; transition:background-color 0.2s; }

/* ── Header: select-all + branch/date block ───────────────────────── */
.header-select-all {
    width:16px; height:16px; cursor:pointer;
    accent-color:#4B5EBD; background:#d1d5db; border-radius:3px;
    margin-right:10px; flex-shrink:0; vertical-align:middle;
}
.header-title-block { display:flex; flex-direction:column; line-height:1.25; min-width:0; }
.header-date-line { font-size:12px; font-weight:400; opacity:.85; margin-top:2px; display:flex; align-items:center; gap:4px; white-space:nowrap; }

/* ── Branch select in header ────────────────────────────────────────── */
#branchSelectHeader { border:none; background:transparent; color:#fff; font-size:18px; font-weight:600; cursor:pointer; padding:0; outline:none; max-width:320px; }
#branchSelectHeader option { color:#1e293b; background:#fff; font-size:14px; }

/* ── Bulk actions badge button ──────────────────────────────────────── */
#bulkActionsHeaderBtn { position:relative; opacity:.45; pointer-events:none; cursor:not-allowed; transition:opacity .15s; }
#bulkActionsHeaderBtn.enabled { opacity:1; pointer-events:auto; cursor:pointer; }
#bulkActionsHeaderBtn .bah-count {
    position:absolute; top:-5px; right:-5px;
    background:#dc2626; color:#fff; border-radius:50%; font-size:10px; font-weight:700;
    min-width:16px; height:16px; line-height:16px; text-align:center; padding:0 3px;
    display:none; box-shadow:0 0 0 1.5px #fff;
}
#bulkActionsHeaderBtn .bah-count.show { display:block; }

/* ── Card header action buttons row ───────────────────────────────── */
.card-header-actions { display:flex; align-items:center; gap:4px; flex-wrap:wrap; justify-content:flex-end; }

/* ── Mobile ───────────────────────────────────────────────────────── */
@media (max-width: 576px) {
  .card-header { padding:10px 14px !important; }
  .header-title-block { max-width:55vw; }
  #branchSelectHeader { font-size:15px; max-width:100%; }
  .card-header-actions { width:100%; justify-content:flex-start; }
  .card-header .btn-light { height:32px; width:32px; padding:0; font-size:15px; }
}

/* ── Table alignment ────────────────────────────────────────────────── */
#maintable thead th, table.dataTable thead th { text-align:center !important; vertical-align:middle !important; }
#maintable thead th:first-child, table.dataTable thead th:first-child { text-align:left !important; }
#maintable tbody td, table.dataTable tbody td { text-align:center !important; vertical-align:middle !important; }
#maintable tbody td:first-child, table.dataTable tbody td:first-child { text-align:left !important; }

/* ── Fixed column style ─────────────────────────────────────────────── */
table.dataTable.fixedHeader-floating,
table.dataTable.fixedHeader-locked { background:#fff !important; border-bottom:none !important; }
table.dataTable thead th.fixedHeader-floating { background:#e2e2e9 !important; }

/* ── Row states ─────────────────────────────────────────────────────── */
.pay-cash   { color:#16a34a; font-weight:700; font-size:11px; }
.pay-airtel { color:#7c3aed; font-weight:700; font-size:11px; }
.pay-mpamba { color:#0369a1; font-weight:700; font-size:11px; }
.pay-bank   { color:#d97706; font-weight:700; font-size:11px; }

/* ── No branch placeholder ──────────────────────────────────────────── */
.no-branch-wrap { padding:48px 20px; text-align:center; color:#94a3b8; }
.no-branch-wrap i { font-size:52px; display:block; margin-bottom:12px; color:#c8d0ed; }
.no-branch-wrap h5 { color:#64748b; font-weight:600; }

/* ── Modal header helpers ───────────────────────────────────────────── */
.mh-blue   { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none !important; border-radius:8px 8px 0 0 !important; }
.mh-danger { background:linear-gradient(135deg,#c0392b,#e74c3c); padding:14px 18px !important; border-bottom:none !important; border-radius:8px 8px 0 0 !important; }
.mh-teal   { background:linear-gradient(135deg,#0ea5e9,#0284c7); padding:14px 18px !important; border-bottom:none !important; border-radius:8px 8px 0 0 !important; }
.mh-title  { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close  { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }

/* ── White modal header ─────────────────────────────────────────────── */
.mh-white { background-color:#fff; padding:14px 18px !important; border-bottom:1px solid #e9ecef; display:flex; align-items:center; justify-content:space-between; }
.mh-white-branch { font-size:15px; font-weight:700; color:#1e293b; }
.mh-white-date   { font-size:12px; color:#6c757d; margin-top:2px; }

/* ── Bulk actions modal option cards ────────────────────────────────── */
.bulk-option-card { display:flex; align-items:center; gap:12px; padding:14px 16px; border-radius:10px; border:1.5px solid #e9ecef; cursor:pointer; transition:border-color .15s,background .15s; margin-bottom:10px; }
.bulk-option-card:last-child { margin-bottom:0; }
.bulk-option-card:hover { border-color:#c8d0ed; background:#f8f9ff; }
.bulk-option-card .boc-icon { width:40px; height:40px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:19px; flex-shrink:0; }
.bulk-option-card .boc-title { font-size:14px; font-weight:700; color:#1e293b; }
.bulk-option-card .boc-desc  { font-size:12px; color:#6c757d; margin-top:1px; }
.boc-icon-reverse  { background:#fef2f2; color:#dc2626; }
.boc-icon-calendar { background:#eff6ff; color:#1d4ed8; }

/* ── Plain interval/details table — no backgrounds, just border lines ─
   First column left-aligned, all other columns center-aligned.        */
.plain-tbl { width:100%; font-size:13px; border-collapse:collapse; }
.plain-tbl thead th {
    font-size:13px; font-weight:700;
    border-top:2px solid #737373; border-bottom:2px solid #737373;
    padding:8px 10px; background:#fff;
    position:sticky; top:0; z-index:1;
    text-align:center;
}
.plain-tbl thead th:first-child { text-align:left; }
.plain-tbl tbody td { padding:8px 10px; border-bottom:1px solid #f1f5f9; vertical-align:middle; text-align:center; }
.plain-tbl tbody td:first-child { text-align:left; }
.plain-tbl .grand-row td { font-weight:700; font-size:13px; border-top:2px solid #737373; border-bottom:2px solid #737373; }
.plain-tbl tr:last-child td { border-bottom:none; }
.big-total { font-weight:700; }
.diff-pos  { color:#16a34a; font-weight:700; }
.diff-neg  { color:#dc2626; font-weight:700; }
.diff-zero { color:#94a3b8; }
.iv-amount-link { color:#1e293b; font-weight:700; text-decoration:none; cursor:pointer; }
.iv-amount-link:hover { color:#4B5EBD; }

/* ── Details modal toggle icon button ──────────────────────────────── */
.mh-icon-btn-dark { color:#4B5EBD; opacity:.85; font-size:20px; cursor:pointer; display:inline-flex; }
.mh-icon-btn-dark:hover { opacity:1; }

/* ── Payment summary rows (Details modal) ────────────────────────────
   Reuses exact same layout as POS pay-summary-row.                   */
.pay-summary-row { display:flex; align-items:center; justify-content:space-between; padding:8px 10px; border:1px solid #e3e3e3; border-radius:6px; margin-bottom:6px; background:#fafafa; }
.pay-summary-row .psr-label { display:flex; align-items:center; gap:8px; font-size:13px; font-weight:600; color:#1e293b; }
.pay-summary-row .psr-label i { color:#4B5EBD; font-size:16px; }
.pay-summary-row .psr-value { font-size:13px; font-weight:700; color:#1e293b; }
.pay-total-row { display:flex; justify-content:space-between; align-items:center; background:#e6e6e6; border:1px solid silver; border-radius:6px; padding:8px 12px; margin-top:10px; font-weight:700; font-size:13px; color:#1e293b; }

/* ── Edit interval field ────────────────────────────────────────────── */
.edit-iv-field { width:100%; border:1px solid #dee2e6; border-radius:6px; padding:8px 12px; font-size:14px; color:#1e293b; outline:none; }
.edit-iv-field:focus { border-color:#4B5EBD; box-shadow:0 0 0 2px rgba(75,94,189,.15); }
.edit-iv-field:disabled { background:#f8f9fa; color:#6c757d; cursor:default; }

/* ── Password eye toggle ────────────────────────────────────────────── */
.pass-wrap { position:relative; }
.pass-wrap .pass-eye { position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; color:#94a3b8; }
.pass-wrap .pass-eye:hover { color:#4B5EBD; }

/* ── Edit sale view grid ────────────────────────────────────────────── */
.view-grid { display:grid; grid-template-columns:1fr 1fr; gap:6px 14px; }
.view-item label { font-size:10px; font-weight:600; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:2px; }
.view-item .view-val { font-size:13px; color:#1e293b; font-weight:500; }
.view-item.full { grid-column:1/-1; }

@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
</style>

<div class="progress" id="progressBar" role="progressbar" style="height:8px;transform:rotate(180deg);display:none">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

  {{-- ── Card Header ─────────────────────────────────────────────────── --}}
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
      @if($selectedBranch)
        <input type="checkbox" id="selectAll" class="header-select-all">
      @endif
      <form method="POST" action="{{ route('retail.operations.update.filters') }}"
            id="headerBranchForm" style="margin:0;display:inline;">
        @csrf
        <input type="hidden" name="user_id" value="{{ Auth::id() }}">
        <div class="header-title-block">
          <select name="branch_id" id="branchSelectHeader"
                  onchange="document.getElementById('headerBranchForm').submit()">
            <option value="" hidden>{{ $branchName }}</option>
            @foreach($branches as $b)
              <option value="{{ $b->id }}" {{ ($pref && $pref->branch_id == $b->id) ? 'selected' : '' }}>
                {{ $b->name }}
              </option>
            @endforeach
          </select>
          @if($selectedBranch)
          <span class="header-date-line">
            <i class="ri-calendar-line"></i> {{ $displayDate }}
          </span>
          @endif
        </div>
      </form>
    </h4>

    <div class="card-header-actions">
      @if($selectedBranch)
      <button type="button" class="btn btn-light text-danger fs-16 mx-1"
              id="bulkActionsHeaderBtn" title="Bulk actions — select rows first">
        <i class="ri-stack-line"></i>
        <span class="bah-count" id="bulkBadge">0</span>
      </button>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="detailsBtn"
         title="System vs cash breakdown">
        <i class="ri-bar-chart-2-line"></i>
      </a>
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="intervalsBtn"
         title="Interval sales">
        <i class="ri-time-line"></i>
      </a>
      @endif
      <a href="#" class="btn btn-light text-primary fs-16 mx-1" id="tableButtonsBtn" title="Export / Download">
        <i class="ri-download-line"></i>
      </a>
    </div>
  </div>

  {{-- ── Card Body ──────────────────────────────────────────────────── --}}
  <div class="card-body">
    @if(!$selectedBranch)
      <div class="no-branch-wrap">
        <i class="ri-store-line"></i>
        <h5>No Branch Selected</h5>
        <p style="font-size:13px;">Select a branch from the header above to view today's sales.</p>
      </div>
    @else
    <table id="maintable" class="table table-sm table-striped row-border order-column w-100 mt-3">
      <thead style="background-color:#e2e2e9">
        <tr>
          <th>Product</th>
          <th>Unit</th>
          <th>Qty</th>
          <th>Price</th>
          <th>Total</th>
          <th>Payment</th>
          <th>Trans ID [{{ $transCount }}]</th>
          <th>Slot</th>
          <th>Time</th>
          <th>User</th>
        </tr>
      </thead>
      <tbody id="tbody">
        @foreach($sales as $d)
          @php
            $qty        = (float)$d->quantity;
            $rqty       = (float)$d->rquantity;
            // Fully reversed (nothing left) — skip row entirely
            if ($rqty > 0 && $rqty >= $qty) continue;
            // Partially reversed — show remaining qty as the effective quantity
            $displayQty = ($rqty > 0) ? round($qty - $rqty, 2) : $qty;
            $total      = $displayQty * (float)$d->price;
            $payClass   = match(strtolower($d->payment_method ?? 'cash')) {
                'airtel' => 'pay-airtel',
                'mpamba' => 'pay-mpamba',
                'bank'   => 'pay-bank',
                default  => 'pay-cash',
            };
            $rowId    = 'row' . $d->id;
            $userName = $d->user_name ?? $d->user;
          @endphp
          <tr id="{{ $rowId }}">
            <td>
              <input type="checkbox" class="selectRow" value="{{ $d->id }}" data-row-id="{{ $rowId }}">
              &nbsp;<a href="#" class="editSaleBtn" style="color:#1e293b;font-weight:600;"
                 data-id="{{ $d->id }}"
                 data-row="{{ $rowId }}"
                 data-product="{{ $d->product }}"
                 data-unit="{{ $d->unit }}"
                 data-price="{{ $d->price }}"
                 data-qty="{{ $displayQty }}"
                 data-transid="{{ $d->transid }}"
                 data-qty-before="{{ $d->qty_before }}"
                 data-qty-after="{{ $d->qty_after }}">{{ $d->product }}</a>
            </td>
            <td>{{ $d->unit }}</td>
            <td>{{ number_format($displayQty, 2) }}</td>
            <td>{{ number_format((float)$d->price, 2) }}</td>
            <td style="font-weight:700;">{{ number_format($total, 2) }}</td>
            <td><span class="{{ $payClass }}">{{ strtoupper($d->payment_method ?? 'CASH') }}</span></td>
            <td style="font-size:11px;">{{ $d->transid }}</td>
            <td style="font-size:11px;">{{ $d->slot ?? '—' }}</td>
            <td style="font-size:11px;">{{ $d->time }}</td>
            <td style="font-size:11px;">{{ $userName }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
    @endif
  </div>
</div>
</div></div></div>

{{-- ═══ MODALS ════════════════════════════════════════════════════════════ --}}

@if($selectedBranch)

{{-- ── Bulk Actions ────────────────────────────────────────────────────── --}}
<div class="modal fade" id="bulkActionsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <div class="mh-title-block">
          <div style="font-size:15px;font-weight:700;color:#fff;">Bulk Actions
            <span id="bulkModalCount" style="font-size:12px;font-weight:400;opacity:.8;margin-left:4px;"></span>
          </div>
        </div>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:18px 20px;">
        <div class="bulk-option-card" id="boBulkReverse">
          <div class="boc-icon boc-icon-reverse"><i class="ri-arrow-go-back-line"></i></div>
          <div>
            <div class="boc-title">Reverse Selected</div>
            <div class="boc-desc">Mark selected sales as reversed and restore stock quantities</div>
          </div>
        </div>
        <div class="bulk-option-card" id="boBulkChangeDate">
          <div class="boc-icon boc-icon-calendar"><i class="ri-calendar-line"></i></div>
          <div>
            <div class="boc-title">Change Date</div>
            <div class="boc-desc">Move selected sales to a different date</div>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="padding:10px 20px 14px;">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- ── Reverse ─────────────────────────────────────────────────────────── --}}
<div class="modal fade" id="reverseModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-danger">
        <div class="mh-title-block">
          <div style="font-size:15px;font-weight:700;color:#fff;">Reverse Selected Sales</div>
        </div>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:20px;">
        <p style="font-size:13px;color:#64748b;margin-bottom:14px;">
          You are about to reverse <strong id="reverseCount">0</strong> sale(s). Stock will be restored.
        </p>
        <label style="font-size:12px;font-weight:600;color:#374151;">Password</label>
        <div class="pass-wrap mt-1">
          <input type="password" id="reversePassword" class="form-control" placeholder="Enter your password" autocomplete="new-password">
          <i class="ri-eye-off-line pass-eye" id="toggleReversePass"></i>
        </div>
      </div>
      <div class="modal-footer" style="padding:12px 18px;">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-danger btn-sm" id="reverseSubmitBtn">
          <i class="ri-arrow-go-back-line"></i> Confirm Reverse
        </button>
      </div>
    </div>
  </div>
</div>

{{-- ── Change Date ─────────────────────────────────────────────────────── --}}
<div class="modal fade" id="changeDateModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-teal">
        <div class="mh-title-block">
          <div style="font-size:15px;font-weight:700;color:#fff;">Change Date</div>
        </div>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:20px;">
        <p style="font-size:13px;color:#64748b;margin-bottom:14px;">
          Moving <strong id="changeDateCount">0</strong> sale(s) to a new date.
        </p>
        <div class="mb-3">
          <label style="font-size:12px;font-weight:600;color:#374151;">New Date</label>
          <input type="date" id="changeDateValue" class="form-control mt-1" value="{{ $today }}">
        </div>
        <label style="font-size:12px;font-weight:600;color:#374151;">Password</label>
        <div class="pass-wrap mt-1">
          <input type="password" id="changeDatePassword" class="form-control" placeholder="Enter your password" autocomplete="new-password">
          <i class="ri-eye-off-line pass-eye" id="toggleChangeDatePass"></i>
        </div>
      </div>
      <div class="modal-footer" style="padding:12px 18px;">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary btn-sm" id="changeDateSubmitBtn">
          <i class="ri-calendar-check-line"></i> Change Date
        </button>
      </div>
    </div>
  </div>
</div>

{{-- ── Edit Sale ────────────────────────────────────────────────────────── --}}
<div class="modal fade" id="editSaleModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <div class="mh-title-block">
          <div style="font-size:15px;font-weight:700;color:#fff;">{{ $selectedBranch->name }}</div>
          <div style="font-size:12px;font-weight:400;color:rgba(255,255,255,.8);margin-top:2px;">{{ $displayDate }}</div>
        </div>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:20px;">
        <input type="hidden" id="editSaleId">
        <input type="hidden" id="editSaleRowId">
        <input type="hidden" id="editSaleOriginalQty">
        <div class="view-grid mb-3">
          <div class="view-item full">
            <label>Product</label>
            <div class="view-val" id="editSaleProduct" style="font-weight:700;font-size:15px;"></div>
          </div>
          <div class="view-item">
            <label>Unit</label>
            <div class="view-val" id="editSaleUnit"></div>
          </div>
          <div class="view-item">
            <label>Trans ID</label>
            <div class="view-val" id="editSaleTransid" style="font-size:11px;"></div>
          </div>
          <div class="view-item">
            <label>Stock Before Sale</label>
            <div class="view-val" id="editSaleQtyBefore" style="color:#6c757d;"></div>
          </div>
          <div class="view-item">
            <label>Stock After Sale</label>
            <div class="view-val" id="editSaleQtyAfter" style="color:#6c757d;"></div>
          </div>
        </div>
        <div style="border-left:3px solid #f59e0b;padding:8px 12px;font-size:12px;color:#92400e;margin-bottom:14px;border:1px solid #fde68a;border-left-width:3px;border-radius:0 6px 6px 0;">
          <i class="ri-alert-line me-1"></i>
          Quantity can be <strong>reduced or set to 0</strong> (full reversal). The difference is restored to stock.
        </div>
        <div class="mb-3">
          <label style="font-size:12px;font-weight:600;color:#374151;">Price</label>
          <input type="number" step="0.01" min="0" id="editSalePrice" class="form-control mt-1" placeholder="0.00">
        </div>
        <div>
          <label style="font-size:12px;font-weight:600;color:#374151;">
            Quantity <span id="editSaleQtyHint" style="font-size:11px;color:#94a3b8;font-weight:400;"></span>
          </label>
          <input type="number" step="0.01" min="0" id="editSaleQty" class="form-control mt-1" placeholder="0.00">
        </div>
      </div>
      <div class="modal-footer" style="padding:12px 18px;">
        <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-primary btn-sm" id="editSaleSubmitBtn">
          <i class="ri-save-line"></i> Save Changes
        </button>
      </div>
    </div>
  </div>
</div>

{{-- ── Details (System vs Cash + Payment breakdown) ─────────────────────
     Header has a toggle icon (bank-card ↔ bar-chart) to switch between
     the two panes, matching the same pattern as POS viewIntervalModal. --}}
<div class="modal fade" id="detailsModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="mh-white modal-header">
        <div>
          <div class="mh-white-branch">{{ $selectedBranch->name }}</div>
          <div class="mh-white-date">{{ $displayDate }}</div>
        </div>
        <div class="d-flex align-items-center" style="gap:14px;margin-left:auto;">
          <a href="#" id="detailsToggleBtn" class="mh-icon-btn-dark" title="View payment breakdown"
             onclick="event.preventDefault();toggleDetailsView();">
            <i class="ri-bank-card-line" id="detailsToggleIcon"></i>
          </a>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
      </div>
      <div class="modal-body" style="padding:18px 20px;">

        {{-- Pane 1: System vs Cash table --}}
        <div id="details-intervals-pane">
          @if($detailsRows->isEmpty())
            <p style="text-align:center;color:#94a3b8;padding:20px 0;">No interval sales recorded for today.</p>
          @else
          <div style="overflow-x:auto;max-height:55vh;overflow-y:auto;">
            <table class="plain-tbl">
              <thead>
                <tr>
                  <th>User</th>
                  <th>Interval</th>
                  <th>System (MWK)</th>
                  <th>Cash (MWK)</th>
                  <th>Diff (MWK)</th>
                </tr>
              </thead>
              <tbody>
                @foreach($detailsRows as $dr)
                  @php $diffClass = $dr->diff > 0 ? 'diff-pos' : ($dr->diff < 0 ? 'diff-neg' : 'diff-zero'); @endphp
                  <tr>
                    <td style="font-size:11px;">{{ $dr->user_name ?? '—' }}</td>
                    <td style="font-weight:600;">{{ $dr->slot }}</td>
                    <td class="big-total">{{ number_format($dr->sys_sales, 2) }}</td>
                    <td class="big-total">{{ number_format($dr->manual_sales, 2) }}</td>
                    <td class="{{ $diffClass }} big-total">{{ number_format($dr->diff, 2) }}</td>
                  </tr>
                @endforeach
                @php
                  $grandDiff      = $grandManual - $sysTotal;
                  $grandDiffClass = $grandDiff > 0 ? 'diff-pos' : ($grandDiff < 0 ? 'diff-neg' : 'diff-zero');
                @endphp
                <tr class="grand-row">
                  <td colspan="2">Grand Total</td>
                  <td class="big-total">{{ number_format($sysTotal, 2) }}</td>
                  <td class="big-total">{{ number_format($grandManual, 2) }}</td>
                  <td class="{{ $grandDiffClass }} big-total">{{ number_format($grandDiff, 2) }}</td>
                </tr>
                <tr>
                  <td colspan="4" style="font-size:12px;color:#64748b;">
                    <i class="ri-money-dollar-circle-line me-1"></i>Physical Cash Counted
                  </td>
                  <td class="big-total" style="color:#1e293b;">{{ number_format($physicalCash, 2) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          @endif
        </div>

        {{-- Pane 2: Payment breakdown --}}
        <div id="details-payments-pane" style="display:none;">
          @foreach($paymentMethodsForDetails as $pm)
          <div class="pay-summary-row" data-pm="{{ $pm['id'] }}">
            <span class="psr-label"><i class="{{ $pm['icon'] }}"></i>{{ $pm['label'] }}</span>
            <span class="psr-value">MWK {{ number_format($paymentSummaryByMethod[$pm['id']] ?? 0, 0) }}</span>
          </div>
          @endforeach
          <div class="pay-total-row">
            <span>Total</span>
            <span>MWK {{ number_format($paymentSummaryByMethod->sum(), 0) }}</span>
          </div>
        </div>

      </div>
      <div class="modal-footer" style="padding:10px 20px 14px;">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- ── Intervals ────────────────────────────────────────────────────────── --}}
<div class="modal fade" id="intervalsModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="mh-white modal-header">
        <div>
          <div class="mh-white-branch">{{ $selectedBranch->name }}</div>
          <div class="mh-white-date">{{ $displayDate }}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:18px 20px;">
        <div style="max-height:55vh;overflow-y:auto;">
          <table class="plain-tbl">
            <thead>
              <tr>
                <th>User</th>
                <th>Interval</th>
                <th>Sales (MWK)</th>
              </tr>
            </thead>
            <tbody id="ivTbody">
              @forelse($intervalSales as $is)
                <tr id="ivrow_{{ $is->id }}" data-sales="{{ $is->sales }}">
                  <td style="font-size:12px;">{{ $is->user_name ?? '—' }}</td>
                  <td style="font-weight:600;">{{ $is->slot }}</td>
                  <td>
                    <a href="#" class="iv-amount-link openEditIvBtn"
                       data-id="{{ $is->id }}" data-slot="{{ $is->slot }}" data-sales="{{ $is->sales }}">
                      {{ number_format((float)$is->sales, 0) }}
                    </a>
                  </td>
                </tr>
              @empty
                <tr id="ivEmptyRow">
                  <td colspan="3" style="text-align:center;color:#94a3b8;padding:24px;border:none;">No interval sales recorded for today.</td>
                </tr>
              @endforelse
              @if($intervalSales->isNotEmpty())
              <tr class="grand-row" id="ivGrandRow">
                <td colspan="2">Grand Total</td>
                <td id="ivGrandTotal">MWK {{ number_format($intervalTotal, 0) }}</td>
              </tr>
              @endif
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer" style="padding:10px 20px 14px;">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- ── Edit Interval ────────────────────────────────────────────────────── --}}
<div class="modal fade" id="editIvModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="mh-white modal-header">
        <div>
          <div class="mh-white-branch">{{ $selectedBranch->name }}</div>
          <div class="mh-white-date">{{ $displayDate }}</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:20px;">
        <input type="hidden" id="editIvId">
        <div class="mb-4">
          <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6c757d;display:block;margin-bottom:5px;">Time Slot</label>
          <input type="text" class="edit-iv-field" id="editIvSlot" disabled>
        </div>
        <div>
          <label style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#6c757d;display:block;margin-bottom:5px;">Sales (MWK)</label>
          <input type="number" class="edit-iv-field" id="editIvSales" min="0" placeholder="0" autocomplete="off" style="font-size:22px;font-weight:700;height:52px;">
        </div>
      </div>
      <div class="modal-footer" style="padding:12px 18px;display:flex;justify-content:space-between;align-items:center;border-top:1px solid #f1f5f9;">
        <button type="button" id="editIvDeleteBtn" class="btn btn-sm" style="background:#fee2e2;color:#dc2626;border:none;font-weight:700;display:flex;align-items:center;gap:6px;">
          <i class="ri-delete-bin-line"></i> Delete
        </button>
        <div style="display:flex;gap:8px;">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="button" id="editIvSubmitBtn" class="btn btn-primary btn-sm" style="display:flex;align-items:center;gap:6px;">
            <i class="ri-check-line"></i> Update
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ── Delete Interval Confirm ──────────────────────────────────────────── --}}
<div class="modal fade" id="ivDeleteConfirmModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog" style="max-width:350px;margin:1.75rem auto;">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
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

@endif

{{-- ── Export / Download ────────────────────────────────────────────────── --}}
<div class="modal fade" id="buttonsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header mh-blue">
        <div class="mh-title-block">
          <div style="font-size:15px;font-weight:700;color:#fff;">Export / Download</div>
        </div>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:18px 20px;">
        <div class="buttons"></div>
      </div>
    </div>
  </div>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js"></script>
@endsection

@section('scripts')
<script>
$(document).ready(function () {

    toastr.options = { closeButton:true, progressBar:true, showMethod:'slideDown', timeOut:5000, allowHtml:true };

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    var BRANCH_SELECTED = {{ $selectedBranch ? 'true' : 'false' }};

    var ROUTES = {
        updateSale:     '{{ route("retail.operations.sales.update") }}',
        reverse:        '{{ route("retail.operations.sales.reverse") }}',
        changeDate:     '{{ route("retail.operations.sales.change-date") }}',
        updateInterval: '{{ route("retail.operations.sales.interval.update") }}',
        deleteInterval: '{{ route("retail.operations.sales.interval.delete") }}',
    };

    var tableTitle = @json($tableTitle);

    /* ── Helpers ──────────────────────────────────────────────────── */
    function fmt(n, dp) {
        dp = (dp !== undefined) ? dp : 2;
        return parseFloat(n || 0).toLocaleString('en-US', { minimumFractionDigits:dp, maximumFractionDigits:dp });
    }
    function showProgress(v) { $('#progressBar').toggle(v); }

    function handleAjaxError(xhr, status) {
        if (status === 'timeout')    { toastr.error('Request timed out.', 'Timeout'); }
        else if (xhr.status === 0)   { toastr.error('Unable to connect.', 'Connection Error'); }
        else if (xhr.status === 419) { toastr.error('Your session expired. Please refresh the page and try again.', 'Session Expired'); }
        else if (xhr.status === 422) {
            var msg = ''; if (xhr.responseJSON && xhr.responseJSON.errors) { $.each(xhr.responseJSON.errors, function(k,v){ msg+=v+'\n'; }); }
            toastr.error(msg || 'Validation failed.', 'Error');
        } else if (xhr.status === 500) { toastr.error('Server error.', 'Error'); }
        else { toastr.error('Unexpected error.', 'Error'); }
    }

    /* ── DataTable ───────────────────────────────────────────────── */
    var table = $('#maintable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthChange: true,
        lengthMenu: [[100, 250, 500, -1], [100, 250, 500, 'All']],
        fixedColumns: { left: 1 },
        scrollX: true,
        order: [],
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: 0,      className: 'text-start'  }
        ],
        buttons: [
            { extend: 'excelHtml5', title: tableTitle, exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'csvHtml5',   title: tableTitle, exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'pdfHtml5',   title: tableTitle, exportOptions: { columns: ':visible:not(:last-child)' },
              customize: function(doc) {
                  doc.content[1].table.widths = Array(doc.content[1].table.body[0].length+1).join('*').split('');
                  doc.content[1].table.body.forEach(function(row){ row[0].alignment='left'; for(var j=1;j<row.length;j++) row[j].alignment='center'; });
              }
            }
        ]
    });

    table.buttons().container().appendTo($('#buttonsModal .buttons'));

    /* ── Header buttons ─────────────────────────────────────────── */
    $('#tableButtonsBtn').click(function(e) { e.preventDefault(); $('#buttonsModal').modal('show'); });

    if (!BRANCH_SELECTED) return;

    $('#detailsBtn').click(function(e)   { e.preventDefault(); resetDetailsView(); $('#detailsModal').modal('show'); });
    $('#intervalsBtn').click(function(e) { e.preventDefault(); $('#intervalsModal').modal('show'); });

    /* ── Details modal: toggle between System/Cash pane and Payment pane -- */
    window.toggleDetailsView = function() {
        var ivPane = document.getElementById('details-intervals-pane');
        var pmPane = document.getElementById('details-payments-pane');
        var icon   = document.getElementById('detailsToggleIcon');
        var btn    = document.getElementById('detailsToggleBtn');
        var showPayments = (pmPane.style.display === 'none');
        if (showPayments) {
            ivPane.style.display = 'none';
            pmPane.style.display = '';
            icon.className = 'ri-bar-chart-2-line';
            btn.title = 'View system vs cash';
        } else {
            ivPane.style.display = '';
            pmPane.style.display = 'none';
            icon.className = 'ri-bank-card-line';
            btn.title = 'View payment breakdown';
        }
    };

    function resetDetailsView() {
        document.getElementById('details-intervals-pane').style.display = '';
        document.getElementById('details-payments-pane').style.display  = 'none';
        document.getElementById('detailsToggleIcon').className = 'ri-bank-card-line';
        document.getElementById('detailsToggleBtn').title      = 'View payment breakdown';
    }

    /* ── Selection ──────────────────────────────────────────────── */
    function getSelectedIds() {
        var ids = [];
        $('.selectRow:checked').each(function(){ ids.push(parseInt($(this).val())); });
        return ids;
    }

    function updateSelection() {
        var n   = getSelectedIds().length;
        var tot = $('.selectRow').length;
        $('#bulkBadge').text(n).toggleClass('show', n > 0);
        n > 0 ? $('#bulkActionsHeaderBtn').addClass('enabled') : $('#bulkActionsHeaderBtn').removeClass('enabled');
        var allChecked = (n > 0 && n === tot);
        $('#selectAll').prop('checked', allChecked);
    }

    $('#selectAll').on('change', function() {
        var c = $(this).is(':checked');
        $('.selectRow').prop('checked', c);
        updateSelection();
    });

    $('#tbody').on('change', '.selectRow', function() { updateSelection(); });

    /* ── Bulk actions ───────────────────────────────────────────── */
    $('#bulkActionsHeaderBtn').on('click', function() {
        var ids = getSelectedIds();
        if (!ids.length) return;
        $('#bulkModalCount').text('(' + ids.length + ' selected)');
        $('#bulkActionsModal').modal('show');
    });

    $('#boBulkReverse').on('click', function() {
        var ids = getSelectedIds();
        if (ids.length > 50) { toastr.error('Max 50 items at once.'); return; }
        $('#reverseCount').text(ids.length);
        $('#reversePassword').val('');
        $('#bulkActionsModal').modal('hide');
        setTimeout(function(){ $('#reverseModal').modal('show'); }, 260);
    });

    $('#reverseSubmitBtn').on('click', function() {
        var ids  = getSelectedIds();
        var pass = $('#reversePassword').val().trim();
        if (!pass) { toastr.warning('Enter your password.'); return; }
        var self = $(this); self.prop('disabled', true).html('<i class="ri-loader-4-line"></i> Processing…');
        $.ajax({
            type: 'POST', url: ROUTES.reverse,
            data: JSON.stringify({ ids:ids, password:pass }), contentType: 'application/json',
            beforeSend: function(){ showProgress(true); },
            complete:   function(){ showProgress(false); self.prop('disabled',false).html('<i class="ri-arrow-go-back-line"></i> Confirm Reverse'); },
            success: function(res) {
                if (res.status === 201) {
                    toastr.success('Sales reversed successfully.');
                    $('#reverseModal').modal('hide');
                    $.each(res.reversed, function(i, id){
                        table.row('#row'+id).remove();
                    });
                    table.draw(false);
                    updateSelection();
                } else { toastr.error(res.error || 'Failed.'); }
            },
            error: handleAjaxError
        });
    });

    $('#boBulkChangeDate').on('click', function() {
        var ids = getSelectedIds();
        if (ids.length > 50) { toastr.error('Max 50 items at once.'); return; }
        $('#changeDateCount').text(ids.length);
        $('#changeDatePassword').val('');
        $('#bulkActionsModal').modal('hide');
        setTimeout(function(){ $('#changeDateModal').modal('show'); }, 260);
    });

    $('#changeDateSubmitBtn').on('click', function() {
        var ids  = getSelectedIds();
        var date = $('#changeDateValue').val();
        var pass = $('#changeDatePassword').val().trim();
        if (!date) { toastr.warning('Select a date.'); return; }
        if (!pass) { toastr.warning('Enter your password.'); return; }
        var self = $(this); self.prop('disabled', true).html('<i class="ri-loader-4-line"></i> Processing…');
        $.ajax({
            type: 'POST', url: ROUTES.changeDate,
            data: JSON.stringify({ ids:ids, date:date, password:pass }), contentType: 'application/json',
            beforeSend: function(){ showProgress(true); },
            complete:   function(){ showProgress(false); self.prop('disabled',false).html('<i class="ri-calendar-check-line"></i> Change Date'); },
            success: function(res) {
                if (res.status === 201) {
                    toastr.success('Date changed.');
                    $('#changeDateModal').modal('hide');
                    $.each(res.changed, function(i, id){ table.row('#row'+id).remove(); });
                    table.draw(false); updateSelection();
                } else { toastr.error(res.error || 'Failed.'); }
            },
            error: handleAjaxError
        });
    });

    /* ── Edit Sale ───────────────────────────────────────────────── */
    $('#tbody').on('click', '.editSaleBtn', function(e) {
        e.preventDefault();
        var $el = $(this), origQty = parseFloat($el.data('qty'));
        $('#editSaleId').val($el.data('id'));
        $('#editSaleRowId').val($el.data('row'));
        $('#editSaleOriginalQty').val(origQty);
        $('#editSaleProduct').text($el.data('product'));
        $('#editSaleUnit').text($el.data('unit'));
        $('#editSaleTransid').text($el.data('transid'));
        $('#editSalePrice').val(parseFloat($el.data('price')).toFixed(2));
        $('#editSaleQty').val(origQty.toFixed(2)).attr('max', origQty);
        $('#editSaleQtyBefore').text(fmt($el.data('qty-before'), 2));
        $('#editSaleQtyAfter').text(fmt($el.data('qty-after'), 2));
        $('#editSaleQtyHint').text('(max: ' + origQty.toFixed(2) + ', min: 0)');
        $('#editSaleModal').modal('show');
    });

    $('#editSaleSubmitBtn').on('click', function() {
        var id      = $('#editSaleId').val();
        var price   = parseFloat($('#editSalePrice').val());
        var qty     = parseFloat($('#editSaleQty').val());
        var origQty = parseFloat($('#editSaleOriginalQty').val());

        if (!id || isNaN(price) || price < 0)    { toastr.warning('Enter a valid price.'); return; }
        if (isNaN(qty) || qty < 0)               { toastr.warning('Quantity must be 0 or more.'); return; }
        if (qty > origQty) {
            toastr.error('Quantity cannot exceed the original: ' + origQty.toFixed(2));
            return;
        }

        qty   = Math.round(qty   * 100) / 100;
        price = Math.round(price * 100) / 100;

        var self = $(this); self.prop('disabled', true).html('<i class="ri-loader-4-line"></i> Saving…');
        $.ajax({
            type: 'POST', url: ROUTES.updateSale,
            data: JSON.stringify({ id:id, price:price, quantity:qty }), contentType: 'application/json',
            beforeSend: function(){ showProgress(true); },
            complete:   function(){ showProgress(false); self.prop('disabled',false).html('<i class="ri-save-line"></i> Save Changes'); },
            success: function(res) {
                if (res.status === 201) {
                    toastr.success('Sale updated.');
                    $('#editSaleModal').modal('hide');
                    var d = res.row;

                    // Active qty = quantity - rquantity (mirrors the Blade rendering logic)
                    var activeQty = Math.round((parseFloat(d.quantity) - parseFloat(d.rquantity || 0)) * 100) / 100;
                    var newPrice  = parseFloat(d.price);
                    var newTotal  = activeQty * newPrice;

                    var row = $('#' + $('#editSaleRowId').val());

                    // Column indices: 0=Product 1=Unit 2=Qty 3=Price 4=Total 5=Payment 6=TransID 7=Slot 8=Time 9=User
                    row.find('td').eq(2).text(fmt(activeQty, 2));
                    row.find('td').eq(3).text(fmt(newPrice, 2));
                    row.find('td').eq(4).text(fmt(newTotal, 2));

                    // Sync data attributes so re-opening the modal is accurate without a refresh
                    row.find('.editSaleBtn')
                        .data('price',     newPrice)
                        .data('qty',       activeQty)
                        .data('qty-after', d.qty_after);

                    // Update the hidden original qty so successive edits in the same session are bounded correctly
                    $('#editSaleOriginalQty').val(activeQty);

                } else { toastr.error(res.error || 'Failed to save.'); }
            },
            error: handleAjaxError
        });
    });

    /* ── Interval edit ───────────────────────────────────────────── */
    var _editIvId = null, _editIvSlot = null;

    $('#ivTbody').on('click', '.openEditIvBtn', function(e) {
        e.preventDefault();
        _editIvId   = $(this).data('id');
        _editIvSlot = $(this).data('slot');
        $('#editIvId').val(_editIvId);
        $('#editIvSlot').val(_editIvSlot);
        $('#editIvSales').val($(this).data('sales'));
        $('#intervalsModal').modal('hide');
        setTimeout(function(){ $('#editIvModal').modal('show'); }, 260);
    });

    $('#editIvSubmitBtn').on('click', function() {
        var newSales = parseFloat($('#editIvSales').val());
        if (isNaN(newSales) || newSales < 0) { toastr.warning('Enter a valid sales amount.'); return; }
        var self = $(this); self.prop('disabled', true);
        $.ajax({
            type: 'POST', url: ROUTES.updateInterval,
            data: JSON.stringify({ id:_editIvId, sales:newSales }), contentType: 'application/json',
            beforeSend: function(){ showProgress(true); },
            complete:   function(){ showProgress(false); self.prop('disabled',false); },
            success: function(res) {
                if (res.status === 201) {
                    toastr.success('Interval updated.');
                    $('#ivrow_'+_editIvId).find('.iv-amount-link')
                        .text(parseFloat(newSales).toLocaleString('en-US',{minimumFractionDigits:0,maximumFractionDigits:0}))
                        .data('sales', newSales);
                    recalcIvGrandTotal();
                    $('#editIvModal').modal('hide');
                    setTimeout(function(){ $('#intervalsModal').modal('show'); }, 260);
                } else { toastr.error(res.error || 'Failed.'); }
            },
            error: handleAjaxError
        });
    });

    $('#editIvDeleteBtn').on('click', function() {
        $('#ivDeleteSlotLabel').text(_editIvSlot);
        $('#editIvModal').modal('hide');
        setTimeout(function(){ $('#ivDeleteConfirmModal').modal('show'); }, 260);
    });

    $('#ivDeleteKeepBtn').on('click', function(e) {
        e.preventDefault();
        $('#ivDeleteConfirmModal').modal('hide');
        setTimeout(function(){ $('#editIvModal').modal('show'); }, 260);
    });

    $('#ivDeleteConfirmBtn').on('click', function(e) {
        e.preventDefault();
        var self = $(this); self.addClass('disabled');
        $.ajax({
            type: 'POST', url: ROUTES.deleteInterval,
            data: JSON.stringify({ id:_editIvId }), contentType: 'application/json',
            beforeSend: function(){ showProgress(true); },
            complete:   function(){ showProgress(false); self.removeClass('disabled'); },
            success: function(res) {
                if (res.status === 201) {
                    toastr.success('Interval deleted.');
                    $('#ivrow_'+_editIvId).remove();
                    recalcIvGrandTotal();
                    $('#ivDeleteConfirmModal').modal('hide');
                    setTimeout(function(){ $('#intervalsModal').modal('show'); }, 260);
                } else { toastr.error(res.error || 'Failed.'); }
            },
            error: handleAjaxError
        });
    });

    function recalcIvGrandTotal() {
        var total = 0;
        $('#ivTbody .iv-amount-link').each(function(){ total += parseFloat($(this).data('sales')) || 0; });
        var remaining = $('#ivTbody tr[id^="ivrow_"]').length;
        if (remaining === 0) {
            $('#ivGrandRow').remove();
            if (!$('#ivEmptyRow').length) {
                $('#ivTbody').append('<tr id="ivEmptyRow"><td colspan="3" style="text-align:center;color:#94a3b8;padding:24px;border:none;">No interval sales recorded for today.</td></tr>');
            }
        } else {
            $('#ivEmptyRow').remove();
            if (!$('#ivGrandRow').length) {
                $('#ivTbody').append('<tr class="grand-row" id="ivGrandRow"><td colspan="2">Grand Total</td><td id="ivGrandTotal"></td></tr>');
            }
            $('#ivGrandTotal').text('MWK ' + total.toLocaleString('en-US',{minimumFractionDigits:0,maximumFractionDigits:0}));
        }
    }

    /* ── Password toggles ───────────────────────────────────────── */
    function bindPassToggle(toggleId, inputId) {
        $('#'+toggleId).on('click', function() {
            var $inp = $('#'+inputId);
            $inp.attr('type', $inp.attr('type') === 'password' ? 'text' : 'password');
            $(this).toggleClass('ri-eye-off-line ri-eye-line');
        });
    }
    bindPassToggle('toggleReversePass',    'reversePassword');
    bindPassToggle('toggleChangeDatePass', 'changeDatePassword');

    /* ── Session flash ──────────────────────────────────────────── */
    @if(Session::has('message'))
        var _t = "{{ Session::get('alert-type','info') }}";
        var _m = "{{ Session::get('message') }}";
        if      (_t==='success') toastr.success(_m);
        else if (_t==='error')   toastr.error(_m);
        else if (_t==='warning') toastr.warning(_m);
        else                     toastr.info(_m);
    @endif

});
</script>
@endsection