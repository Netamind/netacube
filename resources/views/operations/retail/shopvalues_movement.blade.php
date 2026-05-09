@extends('operations.retail.dashboard')
@section('content')

@php
    $pref       = DB::connection('tenant')->table('user_filters')->where('user_id', Auth::id())->first();
    $categories = DB::connection('tenant')->table('categories')->orderBy('category')->get();

    $selectedCategory = null;
    $branches         = collect();

    if ($pref && $pref->category_id) {
        $selectedCategory = DB::connection('tenant')
            ->table('categories')
            ->where('id', $pref->category_id)
            ->first();

        if ($selectedCategory) {
            $branches = DB::connection('tenant')
                ->table('branches')
                ->where('sector',   'Retail')
                ->where('category', (string) $selectedCategory->id)
                ->where('status',   'active')
                ->orderBy('name')
                ->get();
        }
    }

    $savedBranchId       = $pref->branch_id ?? null;
    $preSelectedBranchId = $savedBranchId ?: request()->query('branch_id');

    // ── Pre-compute movement rows (server-rendered, newest date first) ────
    // Range: exactly 90 days ending TODAY, always calendar-anchored to now().
    // Days with no transactions are included (greyed out in the table).
    $movementRows   = collect();
    $movementTotals = [];
    $today          = now()->startOfDay();
    $rangeEnd       = $today->toDateString();                       // today
    $rangeStart     = $today->copy()->subDays(89)->toDateString();  // day 1 of range (90 days incl. today)

    if ($preSelectedBranchId) {

        // ── Step 1: today's live shop value — the only reliable anchor ──────
        // closing[today] = SUM(selling_price x stock_quantity) right now
        $currentShopValue = (float) DB::connection('tenant')
            ->table('retail_branch_products as rbp')
            ->join('retail_base_products as bp', 'bp.id', '=', 'rbp.base_product_id')
            ->where('rbp.branch_id', $preSelectedBranchId)
            ->sum(DB::raw('CAST(rbp.selling_price AS DECIMAL(15,2)) * CAST(rbp.stock_quantity AS DECIMAL(12,3))'));

        // ── Step 2: all in-range logs with the product's current selling price
        $allLogs = DB::connection('tenant')
            ->table('retail_inventory_logs as ril')
            ->join('retail_branch_products as rbp',
                fn($j) => $j->on('rbp.base_product_id', '=', 'ril.product_id')
                             ->on('rbp.branch_id', '=', 'ril.branch_id'))
            ->where('ril.branch_id', $preSelectedBranchId)
            ->whereBetween('ril.log_date', [$rangeStart, $rangeEnd])
            ->select('ril.log_date', 'ril.stock_change', 'rbp.selling_price')
            ->get();

        // ── Step 3: guard against any logs after today (future-dated entries)
        $futureValue = (float) DB::connection('tenant')
            ->table('retail_inventory_logs as ril')
            ->join('retail_branch_products as rbp',
                fn($j) => $j->on('rbp.base_product_id', '=', 'ril.product_id')
                             ->on('rbp.branch_id', '=', 'ril.branch_id'))
            ->where('ril.branch_id', $preSelectedBranchId)
            ->where('ril.log_date', '>', $rangeEnd)
            ->sum(DB::raw('CAST(ril.stock_change AS DECIMAL(15,4)) * CAST(rbp.selling_price AS DECIMAL(15,2))'));

        // End-of-today closing = live value minus any future-dated movements
        $closingToday = $currentShopValue - $futureValue;

        // ── Step 4: group logs by date string ────────────────────────────────
        $logsByDate = $allLogs->groupBy('log_date');

        // ── Step 5: build 90 dates newest → oldest ───────────────────────────
        // Newest-first means our backward walk and our output order match —
        // row[0] = today, row[1] = yesterday, ... row[89] = day 90
        $allDatesNewestFirst = [];
        for ($i = 0; $i < 90; $i++) {
            $allDatesNewestFirst[] = $today->copy()->subDays($i)->toDateString();
        }

        // ── Step 6: backward walk to assign closing value per date ──────────
        // Rule:  closing[today]  = $closingToday  (anchor)
        //        opening[D]      = closing[D] - net_change[D]
        //        closing[D-1]    = opening[D]          (same thing)
        // Walking newest→oldest: peel off each day's net change to get
        // the previous day's closing.
        $closingByDate = [];
        $running = $closingToday;

        foreach ($allDatesNewestFirst as $date) {
            $closingByDate[$date] = $running;
            $net = $logsByDate->get($date, collect())
                              ->sum(fn($l) => (float)$l->stock_change * (float)$l->selling_price);
            $running -= $net;   // step further back in time
        }
        // $running now holds the opening value of the oldest day (day 90)

        // ── Step 7: emit rows newest → oldest (direct DOM order) ─────────────
        foreach ($allDatesNewestFirst as $date) {
            $dayLogs   = $logsByDate->get($date, collect());
            $closing   = $closingByDate[$date];
            $netChange = $dayLogs->sum(fn($l) => (float)$l->stock_change * (float)$l->selling_price);
            $opening   = $closing - $netChange;

            $added   = $dayLogs->filter(fn($l) => (float)$l->stock_change > 0)
                               ->sum(fn($l) =>  (float)$l->stock_change  * (float)$l->selling_price);
            $removed = $dayLogs->filter(fn($l) => (float)$l->stock_change < 0)
                               ->sum(fn($l) => abs((float)$l->stock_change) * (float)$l->selling_price);

            $movementRows->push((object)[
                'date'          => $date,
                'opening_value' => round($opening,  2),
                'value_added'   => round($added,    2),
                'value_removed' => round($removed,  2),
                'closing_value' => round($closing,  2),
                'net_change'    => round($netChange, 2),
                'has_activity'  => $dayLogs->isNotEmpty(),
            ]);
        }

        // ── Totals for tfoot ─────────────────────────────────────────────────
        // Opening of the period  = opening of the LAST row (oldest day)
        // Closing of the period  = closing of the FIRST row (today)
        $movementTotals = [
            'opening_value'  => round($movementRows->last()->opening_value  ?? 0, 2),
            'value_added'    => round($movementRows->sum('value_added'),      2),
            'value_removed'  => round($movementRows->sum('value_removed'),    2),
            'closing_value'  => round($movementRows->first()->closing_value  ?? 0, 2),
            'net_change'     => round($movementRows->sum('net_change'),       2),
        ];
    }
@endphp

<style>
/* ── DataTable export buttons ───────────────────────────────────────────── */
.dt-buttons .btn {
  background: transparent !important; background-image: none !important;
  box-shadow: none !important; border-color: #5bc0de; color: #5bc0de;
}
.dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }

/* ── Card chrome ────────────────────────────────────────────────────────── */
.card-header {
  padding: 0.5rem 1.5rem !important;
  background: linear-gradient(to right, #4B5EBD, #576CC0); color: #fff;
  border-radius: 10px 10px 0 0 !important;
}
.card-body  { padding: 0 1.5rem 1.5rem 1.5rem !important; }
.card       { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; }
.card-header h4 { color:#fff; font-weight:600; margin-bottom:0; display:flex; align-items:center; }
.card-header h4 i { margin-right: 0.25rem; }
.card-header .btn-light {
  height:28px; padding:0 10px;
  display:flex; align-items:center; justify-content:center; line-height:1;
}

/* ── Category select in header ──────────────────────────────────────────── */
#categorySelectHeader {
  border: none; background: transparent; color: #fff;
  font-size: 18px; font-weight: 600; cursor: pointer;
  padding: 0; outline: none; max-width: 340px;
}
#categorySelectHeader option { color: #1e293b; background: #fff; font-size: 14px; }

/* ── Tab navigation ─────────────────────────────────────────────────────── */
.tab-header-container { background: #f8f9fa; border-bottom: 1px solid #dee2e6; }
.tab-header-container .nav { justify-content: flex-start !important; }
.nav-pills .nav-link {
  border-radius: 0 !important; padding: .6rem 1.1rem;
  font-weight: 500; font-size: 13px; color: #495057;
  border-bottom: 3px solid transparent; transition: all .2s; white-space: nowrap;
}
.nav-pills .nav-link:hover { background: #e9ecef; color: #4B5EBD; }
.nav-pills .nav-link.active {
  background: transparent !important; color: #4B5EBD !important;
  border-bottom-color: #4B5EBD; font-weight: 600;
}
.nav-pills .nav-link i { font-size: 1rem; margin-right: .3rem; }

/* ── Controls bar ───────────────────────────────────────────────────────── */
.mv-controls-bar { display: flex; align-items: center; padding: 14px 0 12px; }

/* ── Branch select ──────────────────────────────────────────────────────── */
#movementBranchSelect {
  font-size: 13px; font-weight: 600; color: #1e293b;
  border: 1px solid #d0d4ee; border-radius: 6px;
  padding: 6px 12px; background: #fff; outline: none;
  min-width: 230px; cursor: pointer; transition: border-color .15s;
}
#movementBranchSelect:focus { border-color: #4B5EBD; box-shadow: 0 0 0 3px rgba(75,94,189,0.1); }

/* ── Date range badge ────────────────────────────────────────────────────── */
.mv-date-badge {
  margin-left: auto;
  display: inline-flex; align-items: center; gap: 6px;
  background: #f1f5f9; border: 1px solid #e2e8f0;
  border-radius: 20px; padding: 6px 14px;
  font-size: 11px; font-weight: 600; color: #475569; white-space: nowrap;
}
.mv-date-badge i { color: #4B5EBD; font-size: 13px; }

/* ── Movement table value cells ─────────────────────────────────────────── */
.mv-open  { color:#475569; font-weight:600; }
.mv-close { color:#4B5EBD; font-weight:700; }
.mv-add-link, .mv-rem-link {
  display:inline-flex; align-items:center; gap:5px;
  border-radius:6px; padding:3px 10px; font-size:12px; font-weight:700;
  cursor:pointer; text-decoration:none; transition:opacity .15s, box-shadow .15s;
}
.mv-add-link { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.mv-add-link:hover { opacity:.85; box-shadow:0 2px 6px rgba(21,128,61,.2); color:#15803d; }
.mv-rem-link { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
.mv-rem-link:hover { opacity:.85; box-shadow:0 2px 6px rgba(185,28,28,.2); color:#b91c1c; }
.mv-zero    { color:#94a3b8; font-size:12px; }
.mv-sale-ph {
  background:#f1f5f9; color:#94a3b8; border-radius:6px; padding:3px 10px;
  font-size:11px; font-weight:600; font-style:italic; border:1px solid #e2e8f0;
}
.mv-net-pos  { color:#059669; font-weight:700; }
.mv-net-neg  { color:#dc2626; font-weight:700; }
.mv-net-zero { color:#94a3b8; }

/* ── No-activity row (quiet) ────────────────────────────────────────────── */
.mv-no-activity td { color:#b0b8c8 !important; }
.mv-no-activity td:first-child { color:#94a3b8 !important; font-style:italic; }

/* ── tfoot matches thead — beats DataTables scrollX + striping ──────────── */
#movementTable tfoot tr td,
table.dataTable tfoot tr td,
.dataTables_scrollFoot table tfoot tr td,
.dataTables_scrollFoot table tbody tr td {
  background-color: #e2e2e9 !important;
  font-weight: 700; text-align: center;
}
#movementTable tfoot tr td:first-child,
.dataTables_scrollFoot table tfoot tr td:first-child { text-align: left !important; }

/* ── Table column alignment ─────────────────────────────────────────────── */
#movementTable thead th { text-align: center !important; vertical-align: middle !important; }
#movementTable thead th:first-child,
.dataTables_scrollHead table thead th:first-child,
.dataTables_scrollHeadInner table thead th:first-child { text-align: left !important; }
#movementTable tbody td { text-align: center !important; vertical-align: middle !important; }
#movementTable tbody td:first-child { text-align: left !important; }

/* ── Audit modal ────────────────────────────────────────────────────────── */
.mh-blue  { background:linear-gradient(135deg,#4B5EBD,#576CC0); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-green { background:linear-gradient(135deg,#059669,#10b981); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-red   { background:linear-gradient(135deg,#b91c1c,#dc2626); padding:14px 18px !important; border-bottom:none; border-radius:8px 8px 0 0; }
.mh-title { color:#fff; font-size:15px; font-weight:600; display:flex; align-items:center; gap:6px; }
.mh-close { filter:brightness(0) invert(1); opacity:.8; }
.mh-close:hover { opacity:1; }
.audit-add-badge { background:#dcfce7; color:#15803d; border-radius:5px; padding:2px 8px; font-size:11px; font-weight:700; }
.audit-rem-badge { background:#fee2e2; color:#b91c1c; border-radius:5px; padding:2px 8px; font-size:11px; font-weight:700; }

#auditLogTable thead th { text-align: center !important; vertical-align: middle !important; }
#auditLogTable thead th:first-child { text-align: left !important; }
#auditLogTable tbody td { text-align: center !important; vertical-align: middle !important; }
#auditLogTable tbody td:first-child { text-align: left !important; }

/* ── Download modal ─────────────────────────────────────────────────────── */
.download-section { margin-bottom:1.5rem; padding-bottom:1rem; border-bottom:1px solid #eee; }
.download-section:last-child { border-bottom:none; margin-bottom:0; padding-bottom:0; }
.download-section h6 { color:#4B5EBD; font-weight:600; margin-bottom:.75rem; }
</style>

<div class="progress" id="progressBar" role="progressbar"
     style="height:8px;transform:rotate(180deg);display:none;">
  <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:100%"></div>
</div>

<div class="content-page"><div class="content"><div class="container-fluid">
<div class="row mb-3"></div>

<div class="card">

  {{-- ── Card header ─────────────────────────────────────────────────────── --}}
  <div class="card-header d-flex justify-content-between align-items-center">
    <h4 class="header-title mb-0">
      <i class="ri-line-chart-line"></i>&nbsp;
      <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
            id="headerCategoryForm" style="margin:0;display:inline;">
        @csrf
        <input type="hidden" name="user_id" value="{{ Auth::id() }}">
        <select name="category_id" id="categorySelectHeader"
                onchange="document.getElementById('headerCategoryForm').submit()">
          <option value="" hidden>{{ $selectedCategory ? $selectedCategory->category : '— Select Category —' }}</option>
          @foreach($categories as $cat)
            <option value="{{ $cat->id }}"
              {{ ($pref && $pref->category_id == $cat->id) ? 'selected' : '' }}>
              {{ $cat->category }}
            </option>
          @endforeach
        </select>
      </form>
    </h4>
    <div class="d-flex align-items-center" style="gap:4px;">
      <a href="#" class="btn btn-light text-primary fs-16" id="downloadModalBtn" title="Download">
        <i class="ri-download-line"></i>
      </a>
      <a href="#" class="btn btn-light text-primary fs-16" id="infoBtn" title="About Movement">
        <i class="ri-information-line"></i>
      </a>
    </div>
  </div>

  {{-- ── Tabs ────────────────────────────────────────────────────────────── --}}
  <div class="tab-header-container">
    <ul class="nav nav-pills mb-0">
      <li class="nav-item">
        <a href="{{ route('retail.operations.shopvalues.overview') }}" class="nav-link">
          <i class="ri-store-2-line"></i> Branch Overview
        </a>
      </li>
      <li class="nav-item">
        <a href="{{ route('retail.operations.shopvalues.movement') }}" class="nav-link active">
          <i class="ri-line-chart-line"></i> Stock Value Movement
        </a>
      </li>
    </ul>
  </div>

  {{-- ── Card body ───────────────────────────────────────────────────────── --}}
  <div class="card-body">

    {{-- Branch picker + date range --}}
    <div class="mv-controls-bar">
      <form method="POST" action="{{ route('tenant.admin.update.filters') }}"
            id="headerBranchForm" style="margin:0;display:inline;">
        @csrf
        <input type="hidden" name="user_id" value="{{ Auth::id() }}">
        <select name="branch_id" id="movementBranchSelect"
                onchange="document.getElementById('headerBranchForm').submit()">
          <option value="">— Select a branch —</option>
          @foreach($branches as $b)
            <option value="{{ $b->id }}"
                    {{ $preSelectedBranchId == $b->id ? 'selected' : '' }}>
              {{ $b->name }}
            </option>
          @endforeach
        </select>
      </form>

      <span class="mv-date-badge">
        <i class="ri-calendar-line"></i>
        {{ now()->subDays(89)->format('d M Y') }} &rarr; {{ now()->format('d M Y') }}
        <span style="opacity:.6;font-weight:400;">&nbsp;(90 days)</span>
      </span>
    </div>

    {{-- Movement table --}}
    <table id="movementTable"
           class="table table-sm table-striped row-border w-100 mt-2">
      <thead style="background-color:#e2e2e9">
        <tr>
          <th>Date</th>
          <th>Opening Value (MWK)</th>
          <th>Value Added</th>
          <th>Sales</th>
          <th>Value Removed</th>
          <th>Closing Value (MWK)</th>
          <th>Net Change</th>
        </tr>
      </thead>
      <tbody id="mvTableBody">
        @foreach($movementRows as $row)
          @php
            $net       = $row->net_change;
            $netClass  = $net > 0 ? 'mv-net-pos' : ($net < 0 ? 'mv-net-neg' : 'mv-net-zero');
            $netPrefix = $net > 0 ? '+' : ($net < 0 ? '−' : '');
            $netAbs    = abs($net);
          @endphp
          <tr class="{{ !$row->has_activity ? 'mv-no-activity' : '' }}">
            {{-- Store ISO date as data-order so DataTable sorts correctly --}}
            <td data-order="{{ $row->date }}">
              <strong>{{ \Carbon\Carbon::parse($row->date)->format('d M Y') }}</strong>
            </td>
            <td><span class="mv-open">{{ number_format($row->opening_value, 0) }}</span></td>
            <td>
              @if($row->value_added > 0)
                <a href="#" class="mv-add-link audit-trigger"
                   data-date="{{ $row->date }}" data-type="added"
                   data-branch="{{ $preSelectedBranchId }}">
                  <i class="ri-add-circle-line"></i>+{{ number_format($row->value_added, 0) }}
                </a>
              @else
                <span class="mv-zero">—</span>
              @endif
            </td>
            <td><span class="mv-sale-ph">0</span></td>
            <td>
              @if($row->value_removed > 0)
                <a href="#" class="mv-rem-link audit-trigger"
                   data-date="{{ $row->date }}" data-type="removed"
                   data-branch="{{ $preSelectedBranchId }}">
                  <i class="ri-subtract-line"></i>−{{ number_format($row->value_removed, 0) }}
                </a>
              @else
                <span class="mv-zero">—</span>
              @endif
            </td>
            <td><span class="mv-close">{{ number_format($row->closing_value, 0) }}</span></td>
            <td>
              @if($net != 0)
                <span class="{{ $netClass }}">{{ $netPrefix }}{{ number_format($netAbs, 0) }}</span>
              @else
                <span class="mv-net-zero">0</span>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <td style="text-align:left !important;"><i class="ri-bar-chart-2-line me-1"></i>Totals — 90 days</td>
          <td>
            @if(isset($movementTotals['opening_value']))
              <span class="mv-open">{{ number_format($movementTotals['opening_value'], 0) }}</span>
            @else —
            @endif
          </td>
          <td>
            @if(isset($movementTotals['value_added']) && $movementTotals['value_added'] > 0)
              <span class="mv-net-pos">+{{ number_format($movementTotals['value_added'], 0) }}</span>
            @else <span class="mv-net-zero">0</span>
            @endif
          </td>
          <td><span class="mv-sale-ph">0</span></td>
          <td>
            @if(isset($movementTotals['value_removed']) && $movementTotals['value_removed'] > 0)
              <span class="mv-net-neg">−{{ number_format($movementTotals['value_removed'], 0) }}</span>
            @else <span class="mv-net-zero">0</span>
            @endif
          </td>
          <td>
            @if(isset($movementTotals['closing_value']))
              <span class="mv-close">{{ number_format($movementTotals['closing_value'], 0) }}</span>
            @else —
            @endif
          </td>
          <td>
            @if(isset($movementTotals['net_change']))
              @php $tn = $movementTotals['net_change']; @endphp
              @if($tn > 0)
                <span class="mv-net-pos">+{{ number_format($tn, 0) }}</span>
              @elseif($tn < 0)
                <span class="mv-net-neg">−{{ number_format(abs($tn), 0) }}</span>
              @else
                <span class="mv-net-zero">0</span>
              @endif
            @else —
            @endif
          </td>
        </tr>
      </tfoot>
    </table>

    @if($preSelectedBranchId)
    <p style="font-size:11px;color:#94a3b8;margin-top:6px;margin-bottom:0;">
      <i class="ri-information-line me-1"></i>
      All 90 days are shown. Greyed rows have no stock activity.
      Click <span style="color:#15803d;font-weight:700;">Added</span> or
      <span style="color:#b91c1c;font-weight:700;">Removed</span> values to view the audit log for that day.
    </p>
    @endif

  </div>{{-- card-body --}}
</div>{{-- card --}}
</div></div></div>

{{-- ══ Audit Log Modal ══════════════════════════════════════════════════════ --}}
<div class="modal fade" id="auditLogModal" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
      <div class="modal-header" id="auditModalHeader">
        <h5 class="modal-title mh-title" id="auditModalTitle">
          <i id="auditModalIcon" class="ri-file-list-3-line"></i>
          <span id="auditModalHeading">Audit Log</span>
        </h5>
        <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="padding:0 !important;">
        <div id="auditSummaryStrip"
             style="display:grid;grid-template-columns:repeat(4,1fr);gap:0;border-bottom:1px solid #e4e7f0;">
        </div>
        <div style="padding:14px 18px 6px;">
          <div id="auditSpinner" style="text-align:center;padding:30px;display:none;">
            <div class="mv-spinner" style="width:28px;height:28px;border:3px solid #e0e4f4;border-top-color:#4B5EBD;border-radius:50%;animation:spin .7s linear infinite;margin:0 auto 8px;"></div>
            <p style="font-size:12px;color:#94a3b8;">Loading audit entries…</p>
          </div>
          <table id="auditLogTable"
                 class="table table-sm table-striped row-border order-column w-100">
            <thead style="background-color:#e2e2e9">
              <tr>
                <th>Product</th>
                <th>Unit Price</th>
                <th>Stock Before</th>
                <th>Stock Change</th>
                <th>Stock After</th>
                <th>Value Change</th>
                <th>Reason</th>
                <th>Time</th>
                <th>User</th>
              </tr>
            </thead>
            <tbody id="auditTableBody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer" style="padding:10px 18px 14px;">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- ══ Download Modal ════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="downloadModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue">
      <h5 class="modal-title mh-title"><i class="ri-download-line"></i> Download Movement Data</h5>
      <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <div class="download-section">
        <h6>Stock Value Movement</h6>
        <div class="dt-buttons" id="mvButtons"></div>
      </div>
    </div>
  </div></div>
</div>

{{-- ══ Info Modal ════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="infoModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
  <div class="modal-dialog modal-lg"><div class="modal-content" style="border:none;border-radius:10px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.18);">
    <div class="modal-header mh-blue">
      <h5 class="modal-title mh-title"><i class="ri-information-line"></i> About Stock Value Movement</h5>
      <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body" style="padding:18px 20px;">
      <p>Day-by-day breakdown of stock value for the selected branch over the past 90 days. Every date in the range is shown — dates with no transactions are greyed out.</p>
      <hr class="my-3">
      <table style="width:100%;font-size:13px;border-collapse:collapse;">
        <tr><td style="padding:8px 12px;font-weight:700;color:#475569;width:160px;border-bottom:1px solid #f1f5f9;">Calculation method</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9;">Today's live shop value (selling price × current stock) anchors the table. Each day's values are derived by working backwards: closing[D] = closing[D+1] − net_change[D+1], opening[D] = closing[D] − net_change[D].</td></tr>
        <tr><td style="padding:8px 12px;font-weight:700;color:#475569;border-bottom:1px solid #f1f5f9;">Opening Value</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9;">Shop value at the start of the day before any stock movements.</td></tr>
        <tr><td style="padding:8px 12px;font-weight:700;color:#059669;border-bottom:1px solid #f1f5f9;">Value Added</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9;">Positive stock changes (deliveries, manual increases). Click to view the audit log.</td></tr>
        <tr><td style="padding:8px 12px;font-weight:700;color:#94a3b8;border-bottom:1px solid #f1f5f9;">Sales</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9;">Shows 0 — sales module not yet integrated.</td></tr>
        <tr><td style="padding:8px 12px;font-weight:700;color:#dc2626;border-bottom:1px solid #f1f5f9;">Value Removed</td><td style="padding:8px 12px;border-bottom:1px solid #f1f5f9;">Negative stock changes (write-offs, manual decreases). Click to view the audit log.</td></tr>
        <tr><td style="padding:8px 12px;font-weight:700;color:#4B5EBD;">Closing Value</td><td style="padding:8px 12px;">Opening + Added − Removed. Today's row closing = current live shop value.</td></tr>
      </table>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
    </div>
  </div></div>
</div>

@endsection
@section('scripts')
<style>
@keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
</style>
<script>
$(document).ready(function () {

    toastr.options = { closeButton:true, progressBar:true, showMethod:'slideDown', timeOut:5000 };

    var auditTable         = null;
    var _currentBranchName = $('#movementBranchSelect option:selected').text().trim();

    function fmt(val, dec) {
        dec = dec === undefined ? 0 : dec;
        var n = parseFloat(val);
        return isNaN(n) ? '—' : n.toLocaleString('en-US', {minimumFractionDigits:dec, maximumFractionDigits:dec});
    }

    // ── DataTable ────────────────────────────────────────────────────────
    // Rows arrive newest-first from the server. We use data-order="YYYY-MM-DD"
    // on the date cell so DataTable can sort correctly by ISO date if the user
    // clicks the header. Default order: column 0 descending = newest on top.
    var mvTable = $('#movementTable').DataTable({
        dom: '<"row mt-2 mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
        lengthMenu: [[31, 62, 92, -1],[31, 62, 92, 'All']],
        order: [[0, 'desc']],          // newest date on top
        fixedColumns: { leftColumns: 1 },
        scrollX: true,
        columnDefs: [
            { targets: '_all', className: 'text-center' },
            { targets: 0,      className: 'text-start dt-head-left', type: 'string' },  // ← fix: added dt-head-left
            { targets: [2, 4], orderable: false }
        ],
        buttons: [
            { extend:'excelHtml5', title:'Stock Value Movement — ' + _currentBranchName,
              exportOptions:{ columns:':not(:nth-child(3))' } },
            { extend:'csvHtml5',   title:'Stock Value Movement — ' + _currentBranchName,
              exportOptions:{ columns:':not(:nth-child(3))' } },
            { extend:'pdfHtml5',   title:'Stock Value Movement — ' + _currentBranchName,
              exportOptions:{ columns:':not(:nth-child(3))' },
              customize: function(doc){ doc.content[1].table.widths =
                Array(doc.content[1].table.body[0].length+1).join('*').split(''); } }
        ]
    });
    mvTable.buttons().container().appendTo('#mvButtons');

    // ── Audit log ─────────────────────────────────────────────────────────
    $(document).on('click', '.audit-trigger', function(e) {
        e.preventDefault();
        openAuditModal($(this).data('branch'), $(this).data('date'), $(this).data('type'));
    });

    function openAuditModal(branchId, date, type) {
        var isAdd = (type === 'added');
        document.getElementById('auditModalHeader').className = 'modal-header ' + (isAdd ? 'mh-green' : 'mh-red');
        $('#auditModalIcon').attr('class', isAdd ? 'ri-add-circle-line' : 'ri-subtract-line');
        $('#auditModalHeading').text((isAdd ? 'Value Added — ' : 'Value Removed — ') + date + ' · ' + _currentBranchName);

        $('#auditSummaryStrip').html('');
        $('#auditTableBody').empty();
        if (auditTable) { auditTable.destroy(); auditTable = null; }
        $('#auditSpinner').show();
        $('#auditLogModal').modal('show');

        $.ajax({
            type: 'GET',
            url:  '{{ route("retail.operations.shopvalues.audit") }}',
            data: { branch_id: branchId, date: date, type: type },
            timeout: 60000,
            success: function(data) {
                if (data.status !== 200) {
                    toastr.error(data.error || 'Failed to load audit log.', 'Error');
                    $('#auditLogModal').modal('hide');
                    return;
                }
                renderAuditLog(data.entries || [], data.summary || {}, isAdd);
            },
            error: function() {
                toastr.error('Could not load audit log.', 'Error');
                $('#auditLogModal').modal('hide');
            },
            complete: function() { $('#auditSpinner').hide(); }
        });
    }

    function renderAuditLog(entries, summary, isAdd) {
        var accent = isAdd ? '#059669' : '#dc2626';
        $('#auditSummaryStrip').html(
            summaryCell('Products',           summary.product_count || 0,                                          '#4B5EBD')
          + summaryCell('Log Entries',         summary.entry_count   || 0,                                          '#64748b')
          + summaryCell('Total Units Changed', (isAdd?'+':'−') + fmt(Math.abs(summary.total_units||0), 2),          accent)
          + summaryCell('Total Value '+(isAdd?'Added':'Removed'), 'MWK '+fmt(Math.abs(summary.total_value||0), 0),  accent)
        );

        var html = '';
        entries.forEach(function(e) {
            var chg    = parseFloat(e.stock_change);
            var valChg = parseFloat(e.value_change || 0);
            var badge  = chg > 0
                ? '<span class="audit-add-badge">+' + fmt(chg, 3) + '</span>'
                : '<span class="audit-rem-badge">'  + fmt(chg, 3) + '</span>';
            var valCell = valChg > 0
                ? '<span style="color:#059669;font-weight:700;">+MWK ' + fmt(valChg, 0) + '</span>'
                : '<span style="color:#dc2626;font-weight:700;">−MWK ' + fmt(Math.abs(valChg), 0) + '</span>';

            html += '<tr>'
                + '<td><strong>' + (e.product_name||'—') + '</strong>'
                + (e.product_code ? '<br><small style="color:#94a3b8;">' + e.product_code + '</small>' : '')
                + '</td>'
                + '<td>MWK ' + fmt(e.unit_price, 2) + '</td>'
                + '<td>' + fmt(e.stock_before, 3) + '</td>'
                + '<td>' + badge + '</td>'
                + '<td>' + fmt(e.stock_after, 3) + '</td>'
                + '<td>' + valCell + '</td>'
                + '<td style="max-width:160px;white-space:normal;font-size:11px;color:#475569;">' + (e.action_reason||'—') + '</td>'
                + '<td>' + (e.log_time||'—') + '</td>'
                + '<td>' + (e.user_name||'—') + '</td>'
                + '</tr>';
        });

        $('#auditTableBody').html(html);

        auditTable = $('#auditLogTable').DataTable({
            dom: '<"row mb-2"<"col-md-6"l><"col-md-6"f>>rt<"row"<"col-md-6"i><"col-md-6 text-end"p>>',
            lengthMenu: [[25, 50, -1],[25, 50,'All']],
            order: [[7,'asc']],
            fixedColumns: { leftColumns: 1 },
            scrollX: true,
            columnDefs: [
                { targets: '_all', className: 'text-center' },
                { targets: 0,      className: 'text-start dt-head-left' }
            ]
        });
    }

    function summaryCell(label, value, color) {
        return '<div style="padding:12px 16px;border-right:1px solid #e4e7f0;">'
            + '<div style="font-size:10px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">' + label + '</div>'
            + '<div style="font-size:17px;font-weight:700;color:' + color + ';">' + value + '</div>'
            + '</div>';
    }

    $('#downloadModalBtn').on('click', function(e) { e.preventDefault(); $('#downloadModal').modal('show'); });
    $('#infoBtn').on('click',          function(e) { e.preventDefault(); $('#infoModal').modal('show'); });
});
</script>
@endsection