<?php
use Carbon\Carbon;

/*
 |--------------------------------------------------------------------------
 | RETAIL OPERATIONS — DEFAULT DASHBOARD CONTENT
 |--------------------------------------------------------------------------
 | Rendered directly via @yield('content', View::make('operations.retail.default'))
 | — plain content fragment, no @extends / @section.
 |
 | OLD -> NEW schema mapping:
 |   retailsales          -> retail_system_sales      (branch is VARCHAR)
 |   intervalsales        -> retail_interval_sales + retail_intervals
 |   retailmanualsales    -> retail_physical_cash
 |   retaildeliverynotes  -> retail_deliverynotes (submitted = true only)
 |   retailproducthistory -> retail_inventory_logs (stock_change < 0 = subtracted,
 |                                                    stock_change > 0 = added)
 |
 | NOTE ON EXPENDITURE:
 |   The expenditure module does not exist yet. Every $expenditure* /
 |   $expCategories* value below is intentionally 0 — not an estimate —
 |   because there is no real data to show. Once the module ships,
 |   replace the block marked "EXPENDITURE — MODULE NOT YET IMPLEMENTED"
 |   with real DB::connection('tenant') queries; nothing else on the
 |   page needs to change.
 |
 |   The ONE exception is the Sales vs Expenditure graph below: it uses
 |   a DUMMY expenditure series (clearly marked) purely so the chart has
 |   something to show for demos / tutorial recordings. Swap that block
 |   out for real data at the same time as the rest of the module.
 |
 | NOTE ON THE GRAPH'S "VALUE ADDED" SERIES:
 |   Value Added can legitimately mean two different things in this app:
 |     - Submitted Delivery Notes (the "official" figure, shown in the
 |       Deliverynotes card up top)
 |     - Direct inventory-log gains (stock_change > 0 rows, shown in the
 |       Inventory Movement -> Added tab)
 |   Rather than silently picking one, the graph defaults to Deliverynotes
 |   and exposes a small dropdown so anyone who specifically wants the
 |   direct inventory figure can switch to it — no reload, just swaps the
 |   series data client-side.
 |
 | NOTE ON "NO DATA" IN THE GRAPH:
 |   A zero value for a given month naturally renders as a zero-height
 |   bar — i.e. just a gap at that spot — so nothing special is needed
 |   there. The only edge case handled explicitly is when an entire
 |   series (or the whole chart) has nothing at all across all 4
 |   months, in which case a plain empty-state message is shown instead
 |   of a blank axis (e.g. before the Expenditure module ships and the
 |   dummy-ratio block below is removed).
 |--------------------------------------------------------------------------
 */

$today     = Carbon::today()->toDateString();
$yesterday = Carbon::today()->subDays(1)->toDateString();

$todayLabel     = Carbon::today()->format('l, d F Y');
$yesterdayLabel = Carbon::today()->subDays(1)->format('l, d F Y');

$branches = DB::connection('tenant')
    ->table('branches')
    ->where('sector', 'Retail')
    ->where('status', 'active')
    ->orderBy('name')
    ->get();

$branchIds   = $branches->pluck('id');
$branchCount = $branches->count();

// ── Sales ──────────────────────────────────────────────────────────────
// retail_system_sales.branch is VARCHAR — every comparison casts to string.
$todaysSales = (float) DB::connection('tenant')
    ->table('retail_system_sales')->where('date', $today)
    ->sum(DB::raw('quantity * price'));

$yesterdaysSales = (float) DB::connection('tenant')
    ->table('retail_system_sales')->where('date', $yesterday)
    ->sum(DB::raw('quantity * price'));

$thisMonthSales = (float) DB::connection('tenant')
    ->table('retail_system_sales')
    ->whereRaw('MONTH(STR_TO_DATE(date, "%Y-%m-%d")) = ?', [Carbon::today()->month])
    ->whereRaw('YEAR(STR_TO_DATE(date, "%Y-%m-%d")) = ?',  [Carbon::today()->year])
    ->sum(DB::raw('quantity * price'));

$lastMonthCursor = Carbon::today()->subDays(Carbon::today()->day + 1);
$lastMonthSales = (float) DB::connection('tenant')
    ->table('retail_system_sales')
    ->whereRaw('MONTH(STR_TO_DATE(date, "%Y-%m-%d")) = ?', [$lastMonthCursor->month])
    ->whereRaw('YEAR(STR_TO_DATE(date, "%Y-%m-%d")) = ?',  [$lastMonthCursor->year])
    ->sum(DB::raw('quantity * price'));

$twoMonthsAgoCursor = (clone $lastMonthCursor)->subDays($lastMonthCursor->day + 1);
$lastMonthMinusOneSales = (float) DB::connection('tenant')
    ->table('retail_system_sales')
    ->whereRaw('MONTH(STR_TO_DATE(date, "%Y-%m-%d")) = ?', [$twoMonthsAgoCursor->month])
    ->whereRaw('YEAR(STR_TO_DATE(date, "%Y-%m-%d")) = ?',  [$twoMonthsAgoCursor->year])
    ->sum(DB::raw('quantity * price'));

$thisMonthName         = Carbon::today()->format('F');
$lastMonthName         = $lastMonthCursor->format('F');
$lastMonthMinusOneName = $twoMonthsAgoCursor->format('F');

// ── Value Added — Submitted Delivery Notes only ──────────────────────────
$thisMonthDnotes = (float) DB::connection('tenant')
    ->table('retail_deliverynotes')
    ->whereIn('branch_id', $branchIds)->where('submitted', true)
    ->whereMonth('delivery_date', Carbon::today()->month)
    ->whereYear('delivery_date', Carbon::today()->year)
    ->sum(DB::raw('quantity * selling_price'));

$thisDayDnotes = (float) DB::connection('tenant')
    ->table('retail_deliverynotes')
    ->whereIn('branch_id', $branchIds)->where('submitted', true)
    ->where('delivery_date', $today)
    ->sum(DB::raw('quantity * selling_price'));

$yesterdayDnotes = (float) DB::connection('tenant')
    ->table('retail_deliverynotes')
    ->whereIn('branch_id', $branchIds)->where('submitted', true)
    ->where('delivery_date', $yesterday)
    ->sum(DB::raw('quantity * selling_price'));

$lastMonthDnotes = (float) DB::connection('tenant')
    ->table('retail_deliverynotes')
    ->whereIn('branch_id', $branchIds)->where('submitted', true)
    ->whereMonth('delivery_date', $lastMonthCursor->month)
    ->whereYear('delivery_date', $lastMonthCursor->year)
    ->sum(DB::raw('quantity * selling_price'));

$lastMonthMinusOneDnotes = (float) DB::connection('tenant')
    ->table('retail_deliverynotes')
    ->whereIn('branch_id', $branchIds)->where('submitted', true)
    ->whereMonth('delivery_date', $twoMonthsAgoCursor->month)
    ->whereYear('delivery_date', $twoMonthsAgoCursor->year)
    ->sum(DB::raw('quantity * selling_price'));

// ── Value Subtracted (direct) — retail_inventory_logs, negative stock_change ──
$lossToday = (float) DB::connection('tenant')
    ->table('retail_inventory_logs')
    ->whereIn('branch_id', $branchIds)->where('log_date', $today)->where('stock_change', '<', 0)
    ->selectRaw('COALESCE(SUM(ABS(stock_change) * selling_price), 0) as total')
    ->value('total');

$lossYesterday = (float) DB::connection('tenant')
    ->table('retail_inventory_logs')
    ->whereIn('branch_id', $branchIds)->where('log_date', $yesterday)->where('stock_change', '<', 0)
    ->selectRaw('COALESCE(SUM(ABS(stock_change) * selling_price), 0) as total')
    ->value('total');

$thisMonthLoss = (float) DB::connection('tenant')
    ->table('retail_inventory_logs')
    ->whereIn('branch_id', $branchIds)->where('stock_change', '<', 0)
    ->whereMonth('log_date', Carbon::today()->month)
    ->whereYear('log_date', Carbon::today()->year)
    ->selectRaw('COALESCE(SUM(ABS(stock_change) * selling_price), 0) as total')
    ->value('total');

$lastMonthLoss = (float) DB::connection('tenant')
    ->table('retail_inventory_logs')
    ->whereIn('branch_id', $branchIds)->where('stock_change', '<', 0)
    ->whereMonth('log_date', $lastMonthCursor->month)
    ->whereYear('log_date', $lastMonthCursor->year)
    ->selectRaw('COALESCE(SUM(ABS(stock_change) * selling_price), 0) as total')
    ->value('total');

$lastMonthMinusOneLoss = (float) DB::connection('tenant')
    ->table('retail_inventory_logs')
    ->whereIn('branch_id', $branchIds)->where('stock_change', '<', 0)
    ->whereMonth('log_date', $twoMonthsAgoCursor->month)
    ->whereYear('log_date', $twoMonthsAgoCursor->year)
    ->selectRaw('COALESCE(SUM(ABS(stock_change) * selling_price), 0) as total')
    ->value('total');

// ── Value Added (direct) — same table, positive stock_change rows ──
// This is deliberately NOT the delivery-notes figure above. It's the
// inventory-log counterpart to Value Subtracted: stock that was added
// directly against retail_inventory_logs (e.g. corrections, restocks
// logged straight to inventory) rather than through a delivery note.
$gainToday = (float) DB::connection('tenant')
    ->table('retail_inventory_logs')
    ->whereIn('branch_id', $branchIds)->where('log_date', $today)->where('stock_change', '>', 0)
    ->selectRaw('COALESCE(SUM(stock_change * selling_price), 0) as total')
    ->value('total');

$thisMonthGain = (float) DB::connection('tenant')
    ->table('retail_inventory_logs')
    ->whereIn('branch_id', $branchIds)->where('stock_change', '>', 0)
    ->whereMonth('log_date', Carbon::today()->month)
    ->whereYear('log_date', Carbon::today()->year)
    ->selectRaw('COALESCE(SUM(stock_change * selling_price), 0) as total')
    ->value('total');

$lastMonthGain = (float) DB::connection('tenant')
    ->table('retail_inventory_logs')
    ->whereIn('branch_id', $branchIds)->where('stock_change', '>', 0)
    ->whereMonth('log_date', $lastMonthCursor->month)
    ->whereYear('log_date', $lastMonthCursor->year)
    ->selectRaw('COALESCE(SUM(stock_change * selling_price), 0) as total')
    ->value('total');

$lastMonthMinusOneGain = (float) DB::connection('tenant')
    ->table('retail_inventory_logs')
    ->whereIn('branch_id', $branchIds)->where('stock_change', '>', 0)
    ->whereMonth('log_date', $twoMonthsAgoCursor->month)
    ->whereYear('log_date', $twoMonthsAgoCursor->year)
    ->selectRaw('COALESCE(SUM(stock_change * selling_price), 0) as total')
    ->value('total');

// ── Per-branch numbers, pre-fetched once (avoids N+1 in table + modals) ──
$branchStats = [];
foreach ($branches as $branch) {
    $branchKey = (string) (int) $branch->id;

    $sysToday = (float) DB::connection('tenant')
        ->table('retail_system_sales')->where('branch', $branchKey)->where('date', $today)
        ->sum(DB::raw('quantity * price'));

    $cashToday = (float) (DB::connection('tenant')
        ->table('retail_physical_cash')->where('branch_id', $branch->id)->where('date', $today)
        ->value('amount') ?? 0);

    $sysYesterday = (float) DB::connection('tenant')
        ->table('retail_system_sales')->where('branch', $branchKey)->where('date', $yesterday)
        ->sum(DB::raw('quantity * price'));

    $cashYesterday = (float) (DB::connection('tenant')
        ->table('retail_physical_cash')->where('branch_id', $branch->id)->where('date', $yesterday)
        ->value('amount') ?? 0);

    $intervalsToday = DB::connection('tenant')
        ->table('retail_interval_sales as ris')
        ->join('retail_intervals as ri', 'ri.id', '=', 'ris.interval_id')
        ->leftJoin('users as u', 'u.id', '=', 'ris.user_id')
        ->where('ris.branch_id', $branch->id)->where('ris.date', $today)
        ->orderBy('ri.sort_order')
        ->select('ris.*', 'ri.slot', 'u.name as user_name')
        ->get()
        ->map(function ($iv) use ($branchKey, $today) {
            $sys = (float) DB::connection('tenant')
                ->table('retail_system_sales')
                ->where('branch', $branchKey)->where('date', $today)->where('slot', $iv->slot)
                ->sum(DB::raw('quantity * price'));
            return [
                'slot' => $iv->slot, 'user' => $iv->user_name ?? '—',
                'sys' => $sys, 'cash' => (float) $iv->sales, 'diff' => (float) $iv->sales - $sys,
            ];
        });

    $intervalsYesterday = DB::connection('tenant')
        ->table('retail_interval_sales as ris')
        ->join('retail_intervals as ri', 'ri.id', '=', 'ris.interval_id')
        ->leftJoin('users as u', 'u.id', '=', 'ris.user_id')
        ->where('ris.branch_id', $branch->id)->where('ris.date', $yesterday)
        ->orderBy('ri.sort_order')
        ->select('ris.*', 'ri.slot', 'u.name as user_name')
        ->get()
        ->map(function ($iv) use ($branchKey, $yesterday) {
            $sys = (float) DB::connection('tenant')
                ->table('retail_system_sales')
                ->where('branch', $branchKey)->where('date', $yesterday)->where('slot', $iv->slot)
                ->sum(DB::raw('quantity * price'));
            return [
                'slot' => $iv->slot, 'user' => $iv->user_name ?? '—',
                'sys' => $sys, 'cash' => (float) $iv->sales, 'diff' => (float) $iv->sales - $sys,
            ];
        });

    $branchStats[$branch->id] = [
        'sys_today' => $sysToday, 'cash_today' => $cashToday, 'diff_today' => $cashToday - $sysToday,
        'sys_yesterday' => $sysYesterday, 'cash_yesterday' => $cashYesterday, 'diff_yesterday' => $cashYesterday - $sysYesterday,
        'intervals_today' => $intervalsToday, 'intervals_yesterday' => $intervalsYesterday,
    ];
}

// ── Sales / Value Added / Direct Gain — last 4 months, computed together ──
// (single loop so we don't re-derive month cursors three separate times)
$threeMonthLabels       = [];
$threeMonthSalesSeries  = [];
$threeMonthDnotesSeries = []; // Value Added — Submitted Delivery Notes (default graph source)
$threeMonthGainSeries   = []; // Value Added — Direct (Inventory Movement / stock_change > 0)

for ($i = 3; $i >= 0; $i--) {
    $cursor = Carbon::today()->subMonthsNoOverflow($i)->startOfMonth();

    $monthSales = (float) DB::connection('tenant')
        ->table('retail_system_sales')
        ->whereRaw('MONTH(STR_TO_DATE(date, "%Y-%m-%d")) = ?', [$cursor->month])
        ->whereRaw('YEAR(STR_TO_DATE(date, "%Y-%m-%d")) = ?',  [$cursor->year])
        ->sum(DB::raw('quantity * price'));

    $monthDnotes = (float) DB::connection('tenant')
        ->table('retail_deliverynotes')
        ->whereIn('branch_id', $branchIds)->where('submitted', true)
        ->whereMonth('delivery_date', $cursor->month)
        ->whereYear('delivery_date', $cursor->year)
        ->sum(DB::raw('quantity * selling_price'));

    $monthGain = (float) DB::connection('tenant')
        ->table('retail_inventory_logs')
        ->whereIn('branch_id', $branchIds)->where('stock_change', '>', 0)
        ->whereMonth('log_date', $cursor->month)
        ->whereYear('log_date', $cursor->year)
        ->selectRaw('COALESCE(SUM(stock_change * selling_price), 0) as total')
        ->value('total');

    $threeMonthLabels[]       = $cursor->format('M Y');
    $threeMonthSalesSeries[]  = round($monthSales, 2);
    $threeMonthDnotesSeries[] = round($monthDnotes, 2);
    $threeMonthGainSeries[]   = round((float) $monthGain, 2);
}

// ═══════════════════ EXPENDITURE — MODULE NOT YET IMPLEMENTED ═══════════
// No real data exists yet, so every figure below is 0 rather than an
// estimate. Replace with real DB::connection('tenant') queries once the
// expenditure module ships — the Blade markup will not need to change.
$expenditureToday     = 0.0;
$expenditureThisMonth = 0.0;
$expenditureLastMonth = 0.0;

$expCategoryLabels = [
    'Salaries & Wages',
    'Rent & Utilities',
    'Transport & Fuel',
    'Maintenance',
    'Marketing',
    'Miscellaneous',
];
$expCategoriesToday     = array_fill_keys($expCategoryLabels, 0);
$expCategoriesThisMonth = array_fill_keys($expCategoryLabels, 0);
$expCategoriesLastMonth = array_fill_keys($expCategoryLabels, 0);

// ── DUMMY expenditure series for the 3-month graph ───────────────────────
// The expenditure module has no real data yet. This series exists ONLY so
// the Sales vs Expenditure chart has something to render for demos /
// tutorial recordings. Delete this block and query real data once the
// expenditure module ships — nothing else in the chart needs to change.
// (If you delete this block without replacing it, $threeMonthExpSeries
// becomes all zeros and the chart will correctly render Expenditure as a
// flat line instead of bars — see the "NO DATA" note above.)
$dummyExpenditureRatios = [0.58, 0.66, 0.61];
$threeMonthExpSeries    = [];
foreach ($threeMonthSalesSeries as $idx => $monthSalesValue) {
    $ratio                  = $dummyExpenditureRatios[$idx % count($dummyExpenditureRatios)];
    $threeMonthExpSeries[]  = round($monthSalesValue * $ratio, 2);
}
// ══════════════════════════════════════════════════════════════════════
?>
<style>
/* ══════════════════════════════════════════════════════════════════
   RETAIL OPERATIONS — DEFAULT DASHBOARD
   House palette (#4B5EBD / #576CC0), but headers are light title
   strips (not gradient bars): plain title left, tabs/legend right,
   all cards sharing one fixed header height. A thin brand-colored top
   edge on each card gives a quiet accent + at-a-glance color coding
   (green = Deliverynotes, red = Inventory Movement / Deducted,
   blue = Inventory Movement / Added — kept distinct from Deliverynotes
   green even though both represent "value added").
   Modals still use the house mh-blue gradient header for consistency
   with the rest of the app.
══════════════════════════════════════════════════════════════════ */

*, *::before, *::after { box-sizing: border-box; }

.rod-page-wrap { padding-top: 0; max-width: 100%; overflow-x: hidden; }

/* ── DataTable export buttons — house convention (unused here, kept for parity) ── */
.dt-buttons .btn { background: transparent !important; background-image: none !important; box-shadow: none !important; border-color: #5bc0de; color: #5bc0de; }
.dt-buttons .btn:hover { background: #5bc0de !important; color: #fff; }

/* ── Card chrome ─────────────────────────────────────────────────── */
.rod-card {
  border: none; border-top: 4px solid #4B5EBD;
  box-shadow: 0 2px 12px rgba(0,0,0,0.08); border-radius: 12px;
  background: #fff; height: 100%; display: flex; flex-direction: column;
  overflow: hidden; max-width: 100%; transition: box-shadow .2s ease;
}
.rod-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.10); }
.rod-card.accent-green { border-top-color: #059669; }
.rod-card.accent-red   { border-top-color: #dc2626; }
.rod-card.accent-amber { border-top-color: #d97706; }
.rod-card.accent-teal  { border-top-color: #0ea5e9; }

/* ── Tab strip — light title bar shared by every card, tabs optional ── */
.rod-tab-strip { background: #eef1f6; border-bottom: 1px solid #e3e6ee; overflow-x: auto; }
.rod-tab-strip .nav-pills { flex-wrap: nowrap; }
.rod-tab-strip .nav-link {
  border-radius: 0 !important; padding: .5rem 1rem; font-weight: 500; font-size: 12px; color: #6c757d;
  border-bottom: 3px solid transparent; transition: all .2s; white-space: nowrap; cursor: pointer;
}
.rod-tab-strip .nav-link:hover  { background: #e4e8f1; color: #4B5EBD; }
.rod-tab-strip .nav-link.active { background: transparent !important; color: #4B5EBD !important; border-bottom-color: #4B5EBD; font-weight: 600; }

/* Every card header is the same fixed height, title left / tabs or
   legend right, vertically centered regardless of content. On mobile
   this stays a single-row flex layout that wraps ONLY when the title
   + tabs genuinely can't fit side by side — see media query below —
   so short titles (e.g. "Sales") keep the tabs alongside them instead
   of always dropping to a new line. ────────── */
.rod-tab-strip.has-title {
  display: flex; align-items: center; justify-content: space-between;
  height: 48px; padding: 0 18px; gap: 12px; flex-wrap: nowrap; overflow-x: visible;
}
.rod-tab-strip.has-title .rod-plain-title { display: flex; align-items: center; flex-shrink: 0; }
.rod-tab-strip.has-title .rod-plain-title h4 { margin: 0; font-size: 14.5px; font-weight: 700; color: #1e293b; letter-spacing: .1px; }
.rod-tab-strip.has-title .nav-pills { flex-wrap: nowrap; overflow-x: auto; height: 100%; align-items: center; }
.rod-tab-strip.has-title .nav-pills .nav-item,
.rod-tab-strip.has-title .nav-pills .nav-link { display: flex; align-items: center; height: 100%; }

.rod-card-subtitle { font-size: 10.5px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; margin-top: 1px; }

.rod-card-body { padding: 18px 20px !important; flex: 1; display: flex; flex-direction: column; min-width: 0; }

/* Every tab pane in a rod-card fills the available height and uses
   flex-column, so any child's margin-top:auto sticks to the bottom —
   applies to Inventory Movement, Expenditure, and Sales panes alike. */
.rod-card-body .tab-content { flex: 1; display: flex; flex-direction: column; min-height: 0; }
.rod-card-body .tab-pane { flex: 1; display: none; flex-direction: column; min-height: 0; }
.rod-card-body .tab-pane.show.active { display: flex; }

/* ── Sales table — flat inside the card, no nested-card box. Height is
   fully natural (grows/shrinks with branch count) — no min-height, no
   forced scroll; the card itself just gets taller. Header/footer are
   flat (no background fill) to match the quieter house convention. ── */
table.rod-table { width: 100%; max-width: 100%; border-collapse: collapse; font-size: 13px; table-layout: fixed; }
table.rod-table thead th {
  color: #8a8d98; font-size: 11px; font-weight: 700;
  text-transform: uppercase; letter-spacing: .4px; padding: 11px 8px; text-align: left;
  border-bottom: 1px solid #e6e6e9;
}
table.rod-table thead th.center { text-align: center; }
table.rod-table tbody td { padding: 12px 8px; border-bottom: 1px solid #f5f5f6; vertical-align: middle; overflow: hidden; text-overflow: ellipsis; }
table.rod-table tbody tr:last-child td { border-bottom: none; }
table.rod-table tbody tr:hover { background: #fafbff; }
table.rod-table td.center { text-align: center; }
table.rod-table .col-sales { width: 100px; }

.rod-table-footer {
  display: flex; align-items: center; border-top: 1px solid #e6e6e9;
  padding: 12px 4px 2px; margin-top: 4px;
}
.rod-table-footer .rtf-label { flex: 1; font-size: 12.5px; color: #8a8d98; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; text-align: left; }
.rod-table-footer .rtf-value {
  flex: 0 0 100px; min-width: 100px; text-align: center; font-size: 16px; font-weight: 800; color: #4B5EBD;
  display: flex; align-items: center; justify-content: center;
}

.rod-link-btn { background: none; border: none; padding: 0; margin: 0; cursor: pointer; font: inherit; text-decoration: none; }
.rod-branch-name { font-size: 13px; font-weight: 600; color: #1e293b; }
.rod-branch-name:hover { color: #4B5EBD; }
.rod-branch-idx { font-size: 11px; color: #b3b5bd; font-weight: 600; margin-right: 8px; }

.rod-sales-amount { font-size: 13px; font-weight: 700; color: #4B5EBD; text-align: center; display: inline-block; width: 100%; }
.rod-sales-amount:hover { color: #2d3a8c; text-decoration: underline; }
.rod-sales-amount.zero { color: #ccc; cursor: default; }
.rod-sales-amount.zero:hover { color: #ccc; text-decoration: none; }

.rod-diff-tag { font-size: 10.5px; font-weight: 700; margin-left: 6px; }
.rod-diff-tag.pos  { color: #059669; }
.rod-diff-tag.neg  { color: #dc2626; }
.rod-diff-tag.zero { color: #b3b5bd; }

/* Previous-3-months strip — soft brand-blue chips, matching det-info-badge / dib-cost */
.rod-month-strip { display: flex; gap: 8px; margin-top: 14px; flex-wrap: wrap; }
.rod-chip {
  flex: 1; min-width: 120px; display: flex; flex-direction: column; align-items: flex-start; gap: 2px;
  padding: 10px 14px; border-radius: 8px; border: 1px solid #c5caec; background: #eff3ff;
}
.rod-chip .rms-label { font-size: 10.5px; text-transform: uppercase; letter-spacing: .4px; color: #4B5EBD; font-weight: 700; opacity: .85; }
.rod-chip .rms-value { font-size: 13.5px; font-weight: 800; color: #1e293b; }

/* ── Hero + 3-card month grid — shared across Deliverynotes / Inventory Movement tabs ── */
.rod-hero { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 14px 10px; }
.rod-hero .rh-currency { display: block; font-size: 13px; font-weight: 700; color: #94a3b8; letter-spacing: .6px; margin-bottom: 4px; text-transform: uppercase; }
.rod-hero .rh-amount { margin: 0; font-weight: 800; color: #059669; font-size: 30px; letter-spacing: -.5px; word-break: break-word; }
.rod-hero.is-negative .rh-amount { color: #dc2626; }
.rod-hero .rh-desc { margin: 8px 0 0; color: #9a9da6; font-size: 12px; font-weight: 600; }

/* Inventory "Added" — blue variant, kept distinct from Deliverynotes green */
.rod-hero.is-neutral .rh-amount { color: #2563eb; }

.rod-chip-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; margin-top: auto; }
.rod-chip-grid .rod-chip { flex-direction: column; align-items: flex-start; gap: 2px; width: 100%; min-width: 0; padding: 10px 12px; white-space: normal; }
.rod-chip-grid .rms-label { font-size: 10.5px; letter-spacing: .2px; order: 1; white-space: normal; }
.rod-chip-grid .rms-value { font-size: 13px; font-weight: 800; order: 2; white-space: normal; word-break: break-word; }

/* Deliverynotes — green tint chip variant */
.rod-chip-grid.is-added .rod-chip { background: #ecfdf5; border-color: #bbf7d0; }
.rod-chip-grid.is-added .rms-label { color: #059669; }

/* Inventory Movement "Added" — blue tint chip variant (distinct from Deliverynotes green) */
.rod-chip-grid.is-added-alt .rod-chip { background: #eff6ff; border-color: #bfdbfe; }
.rod-chip-grid.is-added-alt .rms-label { color: #2563eb; }

/* Inventory Movement "Deducted" — red tint chip variant */
.rod-chip-grid.is-deducted .rod-chip { background: #fef2f2; border-color: #fecaca; }
.rod-chip-grid.is-deducted .rms-label { color: #dc2626; }

/* ── Expenditure card — tabbed, flat content ───────────────────────── */
.exp-hero { text-align: center; padding: 16px 10px 18px; border-bottom: 1px solid #f0f0f2; margin-bottom: 16px; }
.exp-hero .exp-currency { display: block; font-size: 12px; font-weight: 700; color: #94a3b8; letter-spacing: .5px; margin-bottom: 4px; text-transform: uppercase; }
.exp-hero .exp-amount { margin: 0; font-weight: 800; color: #111; font-size: 27px; letter-spacing: -.4px; }
.exp-hero .exp-label { margin: 8px 0 0; color: #9a9da6; font-size: 11.5px; font-weight: 600; }

.exp-cat-list { list-style: none; margin: 0; padding: 0; flex: 1; }
.exp-cat-list li { display: flex; align-items: center; justify-content: space-between; padding: 10px 2px; border-top: 1px solid #f5f5f6; font-size: 13px; gap: 8px; }
.exp-cat-list li:first-child { border-top: none; }
.exp-cat-list .ecl-left { display: flex; align-items: center; gap: 10px; min-width: 0; }
.exp-cat-list .ecl-dot { width: 8px; height: 8px; border-radius: 50%; background: #c3c6d1; flex-shrink: 0; }
.exp-cat-list .ecl-label { color: #444; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.exp-cat-list .ecl-value { font-weight: 700; color: #111; flex-shrink: 0; }

/* ── Graph card ──────────────────────────────────────────────────── */
.rod-graph-legend { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
.rod-graph-legend span { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #5b5f6b; white-space: nowrap; }
.rod-graph-legend i.dot { width: 9px; height: 9px; border-radius: 2px; display: inline-block; flex-shrink: 0; }
/* Blends into the header strip instead of standing out as a white pill —
   same background as .rod-tab-strip so it reads as part of the header,
   not a separate control floating on top of it. */
.rod-va-source {
  font-size: 12px; font-weight: 600; color: #4B5EBD; border: 1px solid #c9cee0;
  border-radius: 6px; padding: 3px 8px; background: #eef1f6; cursor: pointer; flex-shrink: 0;
}
.rod-va-source:hover { background: #e4e8f1; }
.rod-va-source:focus { outline: none; border-color: #4B5EBD; }
#rodSalesExpChart { max-width: 100%; overflow: hidden; }
.rod-chart-empty {
  display: flex; align-items: center; justify-content: center;
  height: 340px; color: #9a9da6; font-size: 13px; font-weight: 600;
}

/* ── Modals — mh-blue gradient header, house convention ─────────────── */
.modal-content { border: none; border-radius: 10px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.18); }
.mh-blue { background: linear-gradient(135deg,#4B5EBD,#576CC0); padding: 12px 18px !important; border-bottom: none; border-radius: 8px 8px 0 0; display: flex; align-items: center; justify-content: space-between; }
.mh-title-block { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
.mh-branch { margin: 0; font-size: 15px; font-weight: 700; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mh-date { margin: 0; font-size: 11.5px; color: rgba(255,255,255,0.75); font-weight: 500; }
.mh-close { filter: brightness(0) invert(1); opacity: .8; }
.mh-close:hover { opacity: 1; }

/* No shaded backgrounds in tables — separation comes from borders only */
table.rod-iv-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
table.rod-iv-table thead th { color: #8a8d98; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; padding: 8px 10px; text-align: center; border-bottom: 1px solid #e6e6e9; }
table.rod-iv-table thead th:first-child { text-align: left; }
table.rod-iv-table tbody td { padding: 8px 10px; text-align: center; border-bottom: 1px solid #f1f1f1; }
table.rod-iv-table tbody td:first-child { text-align: left; font-weight: 600; color: #333; }
table.rod-iv-table tfoot td { font-weight: 800; text-align: center; padding: 10px; border-top: 1px solid #e6e6e9; }
table.rod-iv-table tfoot td:first-child { text-align: left; }
.recon-diff-pos { color: #059669; font-weight: 700; }
.recon-diff-neg { color: #dc2626; font-weight: 700; }
.recon-empty-row { text-align: center; color: #aaa; font-size: 12px; padding: 18px 0 !important; }

/* ══ MOBILE ══ */
@media (max-width: 768px) {
  .rod-card-body { padding: 14px !important; }
  table.rod-table, table.rod-iv-table { font-size: 12px; }
  table.rod-table .col-sales { width: 80px; }
  .rod-month-strip .rod-chip { min-width: 100%; }
  .rod-chip-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px; }
  .rod-chip-grid .rod-chip { padding: 8px 8px; }
  .rod-chip-grid .rms-value { font-size: 11.5px; }
  .rod-chip-grid .rms-label { font-size: 9.5px; }
  .rod-hero .rh-amount { font-size: 24px; }

  /* Card headers (Sales, Deliverynotes, Inventory Movement, Expenditure,
     graph): still a single row by default — title left, tabs/legend
     right — and only wraps to a second row when the two genuinely
     don't fit at the same time. Previously the title was forced to
     width:100%, which pushed every header's tabs onto their own line
     even when there was plenty of room (e.g. "Sales" + 2 short tabs).
     Now the title only takes the space its text needs, and the tabs
     claim the rest, right-aligned, shrinking/scrolling before they
     wrap as a group. */
  .rod-tab-strip.has-title {
    height: auto;
    min-height: 48px;
    flex-wrap: wrap;
    align-items: center;
    padding: 8px 14px;
    gap: 6px 10px;
  }
  .rod-tab-strip.has-title .rod-plain-title {
    flex-shrink: 0;
    width: auto;
    max-width: 100%;
  }
  .rod-tab-strip.has-title .nav-pills {
    flex: 1 1 auto;
    width: auto;
    min-width: 0;
    height: auto;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    justify-content: flex-end;
  }
  .rod-tab-strip.has-title .nav-link { padding: .4rem .7rem; font-size: 11.5px; }

  /* Graph header specifically: legend + Value Added dropdown share the
     same "shrink/scroll before wrapping" behavior as the tabs above —
     they only drop below the title when they truly can't fit beside it. */
  .rod-graph-legend {
    flex: 1 1 auto;
    width: auto;
    min-width: 0;
    justify-content: flex-end;
    gap: 8px 12px;
  }
  .rod-graph-legend span { font-size: 11px; }
  .rod-va-source { flex-shrink: 0; }

  .modal-dialog { margin: 1rem auto !important; max-width: calc(100% - 24px) !important; }
  .modal-content { border-radius: 10px !important; max-height: calc(100vh - 2rem); overflow-y: auto; }
  .modal-body { max-height: 70vh; overflow-y: auto; }
  table.rod-iv-table { font-size: 11px; }
  table.rod-iv-table thead th, table.rod-iv-table tbody td { padding: 6px 4px; }

  .rod-chart-empty { height: 260px; font-size: 12px; text-align: center; padding: 0 16px; }
  .exp-cat-list li { font-size: 12px; padding: 8px 2px; }
  .exp-hero .exp-amount { font-size: 23px; }
}
/* Only when the header truly can't fit title + controls on one row at
   all (very narrow phones) do we drop to a fully stacked layout. */
@media (max-width: 420px) {
  .rod-tab-strip.has-title .rod-plain-title { width: 100%; }
  .rod-tab-strip.has-title .nav-pills,
  .rod-tab-strip.has-title .rod-graph-legend { width: 100%; justify-content: flex-start; }
  .rod-chip-grid { grid-template-columns: 1fr; }
  .rod-chip-grid .rod-chip { width: 100%; }
}
</style>

<div class="content-page"><div class="content"><div class="container-fluid rod-page-wrap">

<div class="row mb-3"></div>

  {{-- ════════════════ ROW 1 — Sales (col-7) + Deliverynotes (col-5) ════════════════ --}}
  <div class="row g-3">

    <div class="col-12 col-xl-7">
      <div class="card rod-card">
        <div class="rod-tab-strip has-title">
          <div class="rod-plain-title">
            <h4>Sales</h4>
          </div>
          <ul class="nav nav-pills mb-0" role="tablist">
            <li class="nav-item" role="presentation">
              <a class="nav-link active" data-bs-toggle="pill" href="#rodToday" role="tab">Today</a>
            </li>
            <li class="nav-item" role="presentation">
              <a class="nav-link" data-bs-toggle="pill" href="#rodYesterday" role="tab">Yesterday</a>
            </li>
          </ul>
        </div>
        <div class="card-body rod-card-body">
          <div class="tab-content">

            {{-- ── TODAY ── --}}
            <div class="tab-pane fade show active" id="rodToday" role="tabpanel">
              <table class="rod-table">
                <thead><tr><th>Branch</th><th class="center col-sales">Sales (MWK)</th></tr></thead>
                <tbody>
                  @forelse($branches as $i => $branch)
                    <tr>
                      <td>
                        <span class="rod-branch-idx">{{ $i + 1 }}</span>
                        <button type="button" class="rod-link-btn rod-branch-name" data-bs-toggle="modal" data-bs-target="#rodTodayModal{{ $branch->id }}">
                          {{ $branch->name }}
                        </button>
                      </td>
                      <td class="center col-sales">
                        @if($branchStats[$branch->id]['sys_today'] > 0)
                          <button type="button" class="rod-link-btn rod-sales-amount" data-bs-toggle="modal" data-bs-target="#rodTodayModal{{ $branch->id }}">
                            {{ number_format($branchStats[$branch->id]['sys_today'], 0) }}
                          </button>
                        @else
                          <span class="rod-sales-amount zero">0</span>
                        @endif
                      </td>
                    </tr>
                  @empty
                    <tr><td colspan="2" class="recon-empty-row">No active Retail branches found.</td></tr>
                  @endforelse
                </tbody>
              </table>
              <div class="rod-table-footer">
                <span class="rtf-label">Grand Total</span>
                <span class="rtf-value">{{ number_format($todaysSales, 0) }}</span>
              </div>
            </div>

            {{-- ── YESTERDAY ── --}}
            <div class="tab-pane fade" id="rodYesterday" role="tabpanel">
              <table class="rod-table">
                <thead><tr><th>Branch</th><th class="center col-sales">Sales (MWK)</th></tr></thead>
                <tbody>
                  @forelse($branches as $i => $branch)
                    <?php $sd = $branchStats[$branch->id]['diff_yesterday']; ?>
                    <tr>
                      <td>
                        <span class="rod-branch-idx">{{ $i + 1 }}</span>
                        <button type="button" class="rod-link-btn rod-branch-name" data-bs-toggle="modal" data-bs-target="#rodYesterdayModal{{ $branch->id }}">
                          {{ $branch->name }}
                        </button>
                        <span class="rod-diff-tag {{ $sd > 0 ? 'pos' : ($sd < 0 ? 'neg' : 'zero') }}">
                          [{{ $sd > 0 ? '+' : '' }}{{ number_format($sd, 0) }}]
                        </span>
                      </td>
                      <td class="center col-sales">
                        @if($branchStats[$branch->id]['sys_yesterday'] > 0)
                          <button type="button" class="rod-link-btn rod-sales-amount" data-bs-toggle="modal" data-bs-target="#rodYesterdayModal{{ $branch->id }}">
                            {{ number_format($branchStats[$branch->id]['sys_yesterday'], 0) }}
                          </button>
                        @else
                          <span class="rod-sales-amount zero">—</span>
                        @endif
                      </td>
                    </tr>
                  @empty
                    <tr><td colspan="2" class="recon-empty-row">No active Retail branches found.</td></tr>
                  @endforelse
                </tbody>
              </table>
              <div class="rod-table-footer">
                <span class="rtf-label">Grand Total</span>
                <span class="rtf-value">{{ number_format($yesterdaysSales, 0) }}</span>
              </div>
            </div>

          </div>

          {{-- Previous 3 months --}}
          <div class="rod-month-strip">
            <div class="rod-chip">
              <span class="rms-label">{{ $thisMonthName }}</span>
              <span class="rms-value">MWK {{ number_format($thisMonthSales, 0) }}</span>
            </div>
            <div class="rod-chip">
              <span class="rms-label">{{ $lastMonthName }}</span>
              <span class="rms-value">MWK {{ number_format($lastMonthSales, 0) }}</span>
            </div>
            <div class="rod-chip">
              <span class="rms-label">{{ $lastMonthMinusOneName }}</span>
              <span class="rms-value">MWK {{ number_format($lastMonthMinusOneSales, 0) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-5">
      <div class="card rod-card accent-green">
        <div class="rod-tab-strip has-title">
          <div class="rod-plain-title" style="flex-direction:column;align-items:flex-start;">
            <h4>Deliverynotes</h4>
            <span class="rod-card-subtitle">[Submitted]</span>
          </div>
        </div>
        <div class="card-body rod-card-body">

          <div class="rod-hero">
            <span class="rh-currency">MWK</span>
            <div class="rh-amount">{{ number_format($thisDayDnotes, 2) }}</div>
            <p class="rh-desc">Today</p>
          </div>

          <div class="rod-chip-grid is-added">
            <div class="rod-chip">
              <span class="rms-label">{{ $thisMonthName }}</span>
              <span class="rms-value">MWK {{ number_format($thisMonthDnotes, 2) }}</span>
            </div>
            <div class="rod-chip">
              <span class="rms-label">{{ $lastMonthName }}</span>
              <span class="rms-value">MWK {{ number_format($lastMonthDnotes, 2) }}</span>
            </div>
            <div class="rod-chip">
              <span class="rms-label">{{ $lastMonthMinusOneName }}</span>
              <span class="rms-value">MWK {{ number_format($lastMonthMinusOneDnotes, 2) }}</span>
            </div>
          </div>

        </div>
      </div>
    </div>

  </div>

  {{-- ════════════════ ROW 2 — Inventory Movement (col-7) + Expenditure tabs (col-5) ════════════════ --}}
  <div class="row g-3 mt-1">

    <div class="col-12 col-xl-7">
      <div class="card rod-card accent-red">
        <div class="rod-tab-strip has-title">
          <div class="rod-plain-title">
            <h4>Inventory Movement</h4>
          </div>
          <ul class="nav nav-pills mb-0" role="tablist">
            <li class="nav-item" role="presentation">
              <a class="nav-link active" data-bs-toggle="pill" href="#rodValueAddedInv" role="tab">Added</a>
            </li>
            <li class="nav-item" role="presentation">
              <a class="nav-link" data-bs-toggle="pill" href="#rodValueSubtracted" role="tab">Deducted</a>
            </li>
          </ul>
        </div>
        <div class="card-body rod-card-body">
          <div class="tab-content">

            {{-- ── ADDED (direct) — stock_change > 0 — blue, distinct from Deliverynotes green ── --}}
            <div class="tab-pane fade show active" id="rodValueAddedInv" role="tabpanel">
              <div class="rod-hero is-neutral">
                <span class="rh-currency">MWK</span>
                <div class="rh-amount">{{ number_format($gainToday, 2) }}</div>
                <p class="rh-desc">Today</p>
              </div>
              <div class="rod-chip-grid is-added-alt">
                <div class="rod-chip">
                  <span class="rms-label">{{ $thisMonthName }}</span>
                  <span class="rms-value">MWK {{ number_format($thisMonthGain, 2) }}</span>
                </div>
                <div class="rod-chip">
                  <span class="rms-label">{{ $lastMonthName }}</span>
                  <span class="rms-value">MWK {{ number_format($lastMonthGain, 2) }}</span>
                </div>
                <div class="rod-chip">
                  <span class="rms-label">{{ $lastMonthMinusOneName }}</span>
                  <span class="rms-value">MWK {{ number_format($lastMonthMinusOneGain, 2) }}</span>
                </div>
              </div>
            </div>

            {{-- ── DEDUCTED (direct) — stock_change < 0 ── --}}
            <div class="tab-pane fade" id="rodValueSubtracted" role="tabpanel">
              <div class="rod-hero is-negative">
                <span class="rh-currency">MWK</span>
                <div class="rh-amount">{{ number_format($lossToday, 2) }}</div>
                <p class="rh-desc">Today</p>
              </div>
              <div class="rod-chip-grid is-deducted">
                <div class="rod-chip">
                  <span class="rms-label">{{ $thisMonthName }}</span>
                  <span class="rms-value">MWK {{ number_format($thisMonthLoss, 2) }}</span>
                </div>
                <div class="rod-chip">
                  <span class="rms-label">{{ $lastMonthName }}</span>
                  <span class="rms-value">MWK {{ number_format($lastMonthLoss, 2) }}</span>
                </div>
                <div class="rod-chip">
                  <span class="rms-label">{{ $lastMonthMinusOneName }}</span>
                  <span class="rms-value">MWK {{ number_format($lastMonthMinusOneLoss, 2) }}</span>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-5">
      <div class="card rod-card accent-amber">
        <div class="rod-tab-strip has-title">
          <div class="rod-plain-title">
            <h4>Expenditure</h4>
          </div>
          <ul class="nav nav-pills mb-0" role="tablist">
            <li class="nav-item" role="presentation">
              <a class="nav-link active" data-bs-toggle="pill" href="#rodExpToday" role="tab">Today</a>
            </li>
            <li class="nav-item" role="presentation">
              <a class="nav-link" data-bs-toggle="pill" href="#rodExpThis" role="tab">This Month</a>
            </li>
            <li class="nav-item" role="presentation">
              <a class="nav-link" data-bs-toggle="pill" href="#rodExpLast" role="tab">Last Month</a>
            </li>
          </ul>
        </div>
        <div class="card-body rod-card-body">
          <div class="tab-content">

            {{-- ── TODAY ── --}}
            <div class="tab-pane fade show active" id="rodExpToday" role="tabpanel">
              <div class="exp-hero">
                <span class="exp-currency">MWK</span>
                <div class="exp-amount">{{ number_format($expenditureToday, 2) }}</div>
                <p class="exp-label">Expenditure today</p>
              </div>
              <ul class="exp-cat-list">
                @foreach($expCategoriesToday as $label => $amount)
                  <li>
                    <span class="ecl-left"><span class="ecl-dot"></span><span class="ecl-label">{{ $label }}</span></span>
                    <span class="ecl-value">MWK {{ number_format($amount, 0) }}</span>
                  </li>
                @endforeach
              </ul>
            </div>

            {{-- ── THIS MONTH ── --}}
            <div class="tab-pane fade" id="rodExpThis" role="tabpanel">
              <div class="exp-hero">
                <span class="exp-currency">MWK</span>
                <div class="exp-amount">{{ number_format($expenditureThisMonth, 2) }}</div>
                <p class="exp-label">Expenditure this month</p>
              </div>
              <ul class="exp-cat-list">
                @foreach($expCategoriesThisMonth as $label => $amount)
                  <li>
                    <span class="ecl-left"><span class="ecl-dot"></span><span class="ecl-label">{{ $label }}</span></span>
                    <span class="ecl-value">MWK {{ number_format($amount, 0) }}</span>
                  </li>
                @endforeach
              </ul>
            </div>

            {{-- ── LAST MONTH ── --}}
            <div class="tab-pane fade" id="rodExpLast" role="tabpanel">
              <div class="exp-hero">
                <span class="exp-currency">MWK</span>
                <div class="exp-amount">{{ number_format($expenditureLastMonth, 2) }}</div>
                <p class="exp-label">Expenditure last month</p>
              </div>
              <ul class="exp-cat-list">
                @foreach($expCategoriesLastMonth as $label => $amount)
                  <li>
                    <span class="ecl-left"><span class="ecl-dot"></span><span class="ecl-label">{{ $label }}</span></span>
                    <span class="ecl-value">MWK {{ number_format($amount, 0) }}</span>
                  </li>
                @endforeach
              </ul>
            </div>

          </div>
        </div>
      </div>
    </div>

  </div>

  {{-- ════════════════ ROW 3 — Performance Overview (col-12) ════════════════
       (Was "Sales vs Expenditure" — renamed since the graph also carries
       the Value Added series, so "vs Expenditure" undersold what's on it.) --}}
  <div class="row g-3 mt-1">

    <div class="col-12">
      <div class="card rod-card accent-teal">
        <div class="rod-tab-strip has-title">
          <div class="rod-plain-title">
            <h4>Performance Overview</h4>
          </div>
          <div class="rod-graph-legend">
            <span><i class="dot" style="background:#4B5EBD;"></i>Sales</span>
            <span><i class="dot" style="background:#059669;"></i>Value Added</span>
            <span><i class="dot" style="background:#9CA3AF;"></i>Expenditure</span>
            <select id="rodValueAddedSource" class="rod-va-source" title="Choose what feeds the Value Added bar">
              <option value="dnotes" selected>Value Added: Deliverynotes</option>
              <option value="gain">Value Added: Direct (Inventory)</option>
            </select>
          </div>
        </div>
        <div class="card-body rod-card-body">
          <div id="rodSalesExpChart"></div>
          <div id="rodSalesExpEmpty" class="rod-chart-empty" style="display:none;">No data to show for this period yet.</div>
        </div>
      </div>
    </div>

  </div>

</div></div></div>

{{-- ══════════════════════════════════════════════════════════════════
     MODALS — kept as siblings of the page content, mh-blue headers to
     match Base Products / Delivery Note Details.
══════════════════════════════════════════════════════════════════ --}}

@foreach($branches as $branch)
  <?php $stats = $branchStats[$branch->id]; ?>

  {{-- Today modal --}}
  <div class="modal fade" id="rodTodayModal{{ $branch->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header mh-blue">
          <div class="mh-title-block">
            <h5 class="mh-branch">{{ $branch->name }}</h5>
            <p class="mh-date">{{ $todayLabel }}</p>
          </div>
          <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" style="padding:18px 20px !important;">
          <table class="rod-iv-table">
            <thead><tr><th>Interval</th><th>User</th><th>System</th><th>Cash</th><th>Diff</th></tr></thead>
            <tbody>
              @forelse($stats['intervals_today'] as $iv)
                <tr>
                  <td>{{ $iv['slot'] }}</td>
                  <td>{{ $iv['user'] }}</td>
                  <td>{{ number_format($iv['sys'], 0) }}</td>
                  <td>{{ number_format($iv['cash'], 0) }}</td>
                  <td class="{{ $iv['diff'] > 0 ? 'recon-diff-pos' : ($iv['diff'] < 0 ? 'recon-diff-neg' : '') }}">{{ number_format($iv['diff'], 0) }}</td>
                </tr>
              @empty
                <tr><td colspan="5" class="recon-empty-row">No intervals logged yet today.</td></tr>
              @endforelse
            </tbody>
            <tfoot>
              <tr>
                <td colspan="2">Total</td>
                <td>{{ number_format($stats['sys_today'], 0) }}</td>
                <td>{{ number_format($stats['cash_today'], 0) }}</td>
                <td class="{{ $stats['diff_today'] > 0 ? 'recon-diff-pos' : ($stats['diff_today'] < 0 ? 'recon-diff-neg' : '') }}">{{ number_format($stats['diff_today'], 0) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>

  {{-- Yesterday modal --}}
  <div class="modal fade" id="rodYesterdayModal{{ $branch->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header mh-blue">
          <div class="mh-title-block">
            <h5 class="mh-branch">{{ $branch->name }}</h5>
            <p class="mh-date">{{ $yesterdayLabel }}</p>
          </div>
          <button type="button" class="btn-close mh-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body" style="padding:18px 20px !important;">
          <table class="rod-iv-table">
            <thead><tr><th>Interval</th><th>User</th><th>System</th><th>Cash</th><th>Diff</th></tr></thead>
            <tbody>
              @forelse($stats['intervals_yesterday'] as $iv)
                <tr>
                  <td>{{ $iv['slot'] }}</td>
                  <td>{{ $iv['user'] }}</td>
                  <td>{{ number_format($iv['sys'], 0) }}</td>
                  <td>{{ number_format($iv['cash'], 0) }}</td>
                  <td class="{{ $iv['diff'] > 0 ? 'recon-diff-pos' : ($iv['diff'] < 0 ? 'recon-diff-neg' : '') }}">{{ number_format($iv['diff'], 0) }}</td>
                </tr>
              @empty
                <tr><td colspan="5" class="recon-empty-row">No intervals logged for this day.</td></tr>
              @endforelse
            </tbody>
            <tfoot>
              <tr>
                <td colspan="2">Total</td>
                <td>{{ number_format($stats['sys_yesterday'], 0) }}</td>
                <td>{{ number_format($stats['cash_yesterday'], 0) }}</td>
                <td class="{{ $stats['diff_yesterday'] > 0 ? 'recon-diff-pos' : ($stats['diff_yesterday'] < 0 ? 'recon-diff-neg' : '') }}">{{ number_format($stats['diff_yesterday'], 0) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
@endforeach

{{-- ══════════════════════════════════════════════════════════════════
     Performance Overview — ApexCharts bar/line combo graph
     (apexcharts.min.js is already loaded globally by the dashboard
     layout). The "Value Added" bar defaults to Submitted Delivery
     Notes but can be switched to the direct inventory-log figure via
     the #rodValueAddedSource dropdown — no reload, just updateOptions.

     NO-DATA HANDLING: any series (Sales / Value Added / Expenditure)
     that is entirely zero across the 4-month window renders as a flat
     line in that series' own color instead of invisible zero-height
     bars, so the legend/color stays meaningful even when a module has
     nothing to show yet.
══════════════════════════════════════════════════════════════════ --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
  var rodValueAddedSeries = {
    dnotes: @json($threeMonthDnotesSeries),
    gain:   @json($threeMonthGainSeries),
  };
  var rodSalesSeries = @json($threeMonthSalesSeries);
  var rodExpSeries   = @json($threeMonthExpSeries);

  var rodColors = { sales: '#4B5EBD', valueAdded: '#059669', expenditure: '#9CA3AF' };

  // A zero value for a given month is just a zero-height bar — i.e. a
  // gap at that spot — no special rendering needed. The only thing we
  // guard against is EVERYTHING being empty (no series has any data at
  // all across all 4 months), in which case we show a plain empty
  // state instead of a blank axis.
  function rodAnyDataAtAll(seriesList) {
    return seriesList.some(function (arr) {
      return (arr || []).some(function (v) { return Math.abs(Number(v)) > 0.001; });
    });
  }

  var rodChartEl = document.querySelector('#rodSalesExpChart');
  var rodEmptyEl = document.querySelector('#rodSalesExpEmpty');
  var rodChart   = null;

  function rodSeriesFor(vaData) {
    return [
      { name: 'Sales',       data: rodSalesSeries },
      { name: 'Value Added', data: vaData },
      { name: 'Expenditure', data: rodExpSeries },
    ];
  }

  function rodBuildOptions(vaData) {
    return {
      chart: {
        type: 'bar',
        height: 340,
        toolbar: { show: false },
        fontFamily: 'inherit',
      },
      series: rodSeriesFor(vaData),
      xaxis: {
        categories: @json($threeMonthLabels),
        labels: { style: { fontSize: '12px', colors: '#8a8d98' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
      },
      yaxis: {
        labels: {
          style: { fontSize: '11px', colors: '#8a8d98' },
          formatter: function (val) { return (val / 1000).toFixed(0) + 'k'; }
        }
      },
      colors: [rodColors.sales, rodColors.valueAdded, rodColors.expenditure],
      plotOptions: {
        bar: { borderRadius: 6, columnWidth: '58%', borderRadiusApplication: 'end' }
      },
      dataLabels: { enabled: false },
      grid: { borderColor: '#f0f0f2', strokeDashArray: 4 },
      legend: { show: false },
      tooltip: {
        y: { formatter: function (val) { return 'MWK ' + Number(val).toLocaleString(); } }
      },
      // Smaller/tighter layout on phones so bars and axis labels stay
      // readable instead of squashing into the card width.
      responsive: [
        {
          breakpoint: 768,
          options: {
            chart: { height: 260 },
            plotOptions: { bar: { columnWidth: '68%' } },
            xaxis: { labels: { style: { fontSize: '10.5px' } } },
            yaxis: { labels: { style: { fontSize: '10px' } } },
          }
        }
      ]
    };
  }

  function rodRender(vaSource) {
    var vaData = rodValueAddedSeries[vaSource] || [];

    if (!rodAnyDataAtAll([rodSalesSeries, vaData, rodExpSeries])) {
      if (rodChart) { rodChart.destroy(); rodChart = null; }
      if (rodChartEl) rodChartEl.style.display = 'none';
      if (rodEmptyEl) rodEmptyEl.style.display = 'flex';
      return;
    }

    if (rodEmptyEl) rodEmptyEl.style.display = 'none';
    if (rodChartEl) rodChartEl.style.display = 'block';

    if (!rodChart) {
      if (rodChartEl) {
        rodChart = new ApexCharts(rodChartEl, rodBuildOptions(vaData));
        rodChart.render();
      }
    } else {
      rodChart.updateSeries(rodSeriesFor(vaData));
    }
  }

  rodRender('dnotes');

  var rodVASelect = document.querySelector('#rodValueAddedSource');
  if (rodVASelect) {
    rodVASelect.addEventListener('change', function () {
      rodRender(this.value);
    });
  }
});
</script>