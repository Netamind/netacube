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
 |   Now backed by retail_expenditure_types + retail_expenditures. Each
 |   expenditure row references an expenditure_type_id; the dashboard
 |   groups amounts by type name (`retail_expenditure_types.name`) for
 |   Today / This Month / Last Month, and the 4-month Performance
 |   Overview graph uses real monthly totals as well (no more dummy
 |   ratio series).
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
 |   of a blank axis.
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

// ── Branches ranked highest → lowest sales, one order per tab ────────────
// (Grand Total table rows are now a leaderboard rather than an
// alphabetical list — Today and Yesterday each get their own ranking
// since a branch's rank can differ day to day.)
$branchesByToday = $branches
    ->sortByDesc(fn ($b) => $branchStats[$b->id]['sys_today'])
    ->values();

$branchesByYesterday = $branches
    ->sortByDesc(fn ($b) => $branchStats[$b->id]['sys_yesterday'])
    ->values();

// ── Active expenditure types ──────────────────────────────────────────
// Expenditures are only meaningful for reporting while their type is
// still 'active' in retail_expenditure_types. Every direct query below
// against retail_expenditures (grand totals, the 4-month graph series,
// and the Income & Expenditure scope figures) filters down to this id
// list so a deactivated type's historical spend silently drops out of
// all dashboard totals — not just the per-type breakdown, which already
// gets this for free via its join to retail_expenditure_types.
$activeExpenditureTypeIds = DB::connection('tenant')
    ->table('retail_expenditure_types')
    ->where('status', 'active')
    ->pluck('id');

// ── Sales / Value Added / Direct Gain / Expenditure — last 4 months ──────
// (single loop so we don't re-derive month cursors separately for each)
$threeMonthLabels       = [];
$threeMonthSalesSeries  = [];
$threeMonthDnotesSeries = []; // Value Added — Submitted Delivery Notes (default graph source)
$threeMonthGainSeries   = []; // Value Added — Direct (Inventory Movement / stock_change > 0)
$threeMonthExpSeries    = []; // Expenditure — real retail_expenditures totals

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

    $monthExp = (float) DB::connection('tenant')
        ->table('retail_expenditures')
        ->whereIn('expenditure_type_id', $activeExpenditureTypeIds)
        ->whereMonth('expenditure_date', $cursor->month)
        ->whereYear('expenditure_date', $cursor->year)
        ->sum('amount');

    $threeMonthLabels[]       = $cursor->format('M Y');
    $threeMonthSalesSeries[]  = round($monthSales, 2);
    $threeMonthDnotesSeries[] = round($monthDnotes, 2);
    $threeMonthGainSeries[]   = round((float) $monthGain, 2);
    $threeMonthExpSeries[]    = round($monthExp, 2);
}

// ═══════════════════ EXPENDITURE — retail_expenditures / retail_expenditure_types ═══════
// Fully dynamic: the category list is driven by whatever expenditure
// types are configured (active) in retail_expenditure_types — not a
// fixed PHP array, and not limited to types that happen to have a
// spend row for the period. Each active type is left-joined to its
// spend for the period (COALESCE to 0 when nothing was logged), so
// admins always see the complete, current set of categories with
// real amounts (or 0), highest amount first. Adding/renaming/retiring
// a type in retail_expenditure_types automatically reflects here with
// no code change.
$expTodayRows = DB::connection('tenant')
    ->table('retail_expenditure_types as ret')
    ->leftJoin('retail_expenditures as re', function ($join) use ($today) {
        $join->on('re.expenditure_type_id', '=', 'ret.id')
             ->where('re.expenditure_date', '=', $today);
    })
    ->where('ret.status', 'active')
    ->groupBy('ret.id', 'ret.name')
    ->orderByDesc('total')
    ->selectRaw('ret.name as name, COALESCE(SUM(re.amount), 0) as total')
    ->get();

$expThisMonthRows = DB::connection('tenant')
    ->table('retail_expenditure_types as ret')
    ->leftJoin('retail_expenditures as re', function ($join) {
        $join->on('re.expenditure_type_id', '=', 'ret.id')
             ->whereMonth('re.expenditure_date', Carbon::today()->month)
             ->whereYear('re.expenditure_date', Carbon::today()->year);
    })
    ->where('ret.status', 'active')
    ->groupBy('ret.id', 'ret.name')
    ->orderByDesc('total')
    ->selectRaw('ret.name as name, COALESCE(SUM(re.amount), 0) as total')
    ->get();

$expLastMonthRows = DB::connection('tenant')
    ->table('retail_expenditure_types as ret')
    ->leftJoin('retail_expenditures as re', function ($join) use ($lastMonthCursor) {
        $join->on('re.expenditure_type_id', '=', 'ret.id')
             ->whereMonth('re.expenditure_date', $lastMonthCursor->month)
             ->whereYear('re.expenditure_date', $lastMonthCursor->year);
    })
    ->where('ret.status', 'active')
    ->groupBy('ret.id', 'ret.name')
    ->orderByDesc('total')
    ->selectRaw('ret.name as name, COALESCE(SUM(re.amount), 0) as total')
    ->get();

// Grand totals come straight from retail_expenditures (not the per-type
// rows), but still restricted to active types via $activeExpenditureTypeIds
// so the headline figure always matches what the category list below
// shows — a deactivated type's spend drops out of both consistently.
$expenditureToday = (float) DB::connection('tenant')
    ->table('retail_expenditures')
    ->whereIn('expenditure_type_id', $activeExpenditureTypeIds)
    ->where('expenditure_date', $today)->sum('amount');

$expenditureThisMonth = (float) DB::connection('tenant')
    ->table('retail_expenditures')
    ->whereIn('expenditure_type_id', $activeExpenditureTypeIds)
    ->whereMonth('expenditure_date', Carbon::today()->month)
    ->whereYear('expenditure_date', Carbon::today()->year)
    ->sum('amount');

$expenditureLastMonth = (float) DB::connection('tenant')
    ->table('retail_expenditures')
    ->whereIn('expenditure_type_id', $activeExpenditureTypeIds)
    ->whereMonth('expenditure_date', $lastMonthCursor->month)
    ->whereYear('expenditure_date', $lastMonthCursor->year)
    ->sum('amount');

$expCategoriesToday     = $expTodayRows->mapWithKeys(fn ($r) => [$r->name => (float) $r->total]);
$expCategoriesThisMonth = $expThisMonthRows->mapWithKeys(fn ($r) => [$r->name => (float) $r->total]);
$expCategoriesLastMonth = $expLastMonthRows->mapWithKeys(fn ($r) => [$r->name => (float) $r->total]);

// ═══════════════════ INCOME & EXPENDITURE — scope-aware (All Branches / per-branch) ═══════
// "Income" = Sales revenue from retail_system_sales (same figure as the
// Sales card above), for This Month / Last Month / the month before that.
//
// Expenditure honours retail_expenditures.scope_type:
//   - All Branches view    -> every expenditure row regardless of scope
//     (matches the existing Expenditure card totals above).
//   - Specific Branch view -> rows scoped directly to that branch
//     (scope_type = 'branch' + matching branch_id) PLUS rows scoped
//     'all' (sector-wide overhead, attributed to every branch). Rows
//     scoped 'category' aren't tied to a single branch, so they only
//     surface in the All Branches view — same as they do today.
//
// Every scope × period combination is computed once, server-side, and
// shipped down as JSON. Flipping the scope dropdown just swaps the
// numbers on screen client-side — no page reload, no extra requests.
$ieIncomeForPeriod = function (Carbon $cursor, $branchId = null) {
    $q = DB::connection('tenant')
        ->table('retail_system_sales')
        ->whereRaw('MONTH(STR_TO_DATE(date, "%Y-%m-%d")) = ?', [$cursor->month])
        ->whereRaw('YEAR(STR_TO_DATE(date, "%Y-%m-%d")) = ?',  [$cursor->year]);
    if ($branchId !== null) {
        $q->where('branch', (string) (int) $branchId);
    }
    return (float) $q->sum(DB::raw('quantity * price'));
};

$ieExpenditureForPeriod = function (Carbon $cursor, $branchId = null) use ($activeExpenditureTypeIds) {
    $q = DB::connection('tenant')
        ->table('retail_expenditures')
        ->whereIn('expenditure_type_id', $activeExpenditureTypeIds)
        ->whereMonth('expenditure_date', $cursor->month)
        ->whereYear('expenditure_date', $cursor->year);
    if ($branchId !== null) {
        $q->where(function ($w) use ($branchId) {
            $w->where('scope_type', 'all')
              ->orWhere(function ($w2) use ($branchId) {
                  $w2->where('scope_type', 'branch')->where('branch_id', $branchId);
              });
        });
    }
    return (float) $q->sum('amount');
};

$iePeriods = [
    'this_month'     => ['cursor' => Carbon::today(),    'name' => $thisMonthName],
    'last_month'     => ['cursor' => $lastMonthCursor,   'name' => $lastMonthName],
    'two_months_ago' => ['cursor' => $twoMonthsAgoCursor,'name' => $lastMonthMinusOneName],
];

$ieScopeList = collect([(object) ['id' => 'all', 'name' => 'All Branches']])
    ->concat($branches->map(fn ($b) => (object) ['id' => (string) $b->id, 'name' => $b->name]));

$incomeExpenditure = [];
foreach ($ieScopeList as $scope) {
    $branchId = $scope->id === 'all' ? null : $scope->id;
    $periods  = [];

    foreach ($iePeriods as $key => $p) {
        $income  = $ieIncomeForPeriod($p['cursor'], $branchId);
        $expense = $ieExpenditureForPeriod($p['cursor'], $branchId);

        $periods[$key] = [
            'label'       => $p['name'],
            'income'      => round($income, 2),
            'expenditure' => round($expense, 2),
            'diff'        => round($income - $expense, 2),
        ];
    }

    $incomeExpenditure[$scope->id] = [
        'label'   => $scope->name,
        'periods' => $periods,
    ];
}
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
  --rod-accent: #4B5EBD;
  border: none; border-top: 4px solid var(--rod-accent);
  box-shadow: 0 2px 12px rgba(0,0,0,0.08); border-radius: 12px;
  background: #fff; height: 100%; display: flex; flex-direction: column;
  overflow: hidden; max-width: 100%; transition: box-shadow .2s ease;
}
.rod-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.10); }
.rod-card.accent-green { --rod-accent: #059669; }
.rod-card.accent-red   { --rod-accent: #dc2626; }
.rod-card.accent-amber { --rod-accent: #d97706; }
.rod-card.accent-teal  { --rod-accent: #0ea5e9; }

/* ── Tab strip — light title bar shared by every card, tabs optional ──
   Background intensity toned down ~30% (was #eef1f6). Bottom border now
   tracks each card's own accent color (via --rod-accent) at ~70%
   intensity — i.e. mixed 30% toward white — instead of one flat grey
   for every card. */
.rod-tab-strip {
  background: #f4f6fa;
  border-bottom: 1px solid color-mix(in srgb, var(--rod-accent, #4B5EBD) 45%, white 55%);
  overflow-x: auto;
}
.rod-tab-strip .nav-pills { flex-wrap: nowrap; }
.rod-tab-strip .nav-link {
  border-radius: 0 !important; padding: .5rem 1rem; font-weight: 500; font-size: 12px; color: #6c757d;
  border-bottom: 3px solid transparent; transition: all .2s; white-space: nowrap; cursor: pointer;
}
.rod-tab-strip .nav-link:hover  { background: #e4e8f1; color: #4B5EBD; }
.rod-tab-strip .nav-link.active { background: transparent !important; color: #4B5EBD !important; border-bottom-color: #4B5EBD; font-weight: 600; }

/* Every card header is the same fixed height, title left / tabs or
   legend right, vertically centered regardless of content. On mobile
   this switches to a wrapping, auto-height layout — see media query
   below — so title/tabs/legend/dropdown never overlap on narrow
   screens. ────────── */
.rod-tab-strip.has-title {
  display: flex !important; align-items: center !important; justify-content: flex-start !important;
  height: 48px; padding: 0 18px; gap: 12px; flex-wrap: nowrap; overflow-x: visible;
}
.rod-tab-strip.has-title .rod-plain-title { display: flex; align-items: center; flex-shrink: 0; margin-right: 0 !important; }
.rod-tab-strip.has-title .rod-plain-title h4 { margin: 0; font-size: 14.5px; font-weight: 700; color: #1e293b; letter-spacing: .1px; }
/* margin-left: auto pushes tabs to the far right whether they're sharing
   the row with the title or, on narrow screens, sitting alone on their
   own wrapped line — either way they hug the right edge, never drift
   toward the middle/left. !important guards against any global
   .nav / .nav-pills centering rule defined elsewhere in the shared
   layout (e.g. a card-header-tabs style reset). */
.rod-tab-strip.has-title .nav-pills {
  flex: 0 0 auto !important; flex-wrap: nowrap; overflow-x: auto; height: 100%; align-items: center;
  margin: 0 0 0 auto !important; justify-content: flex-end !important; width: auto !important;
}
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

/* Footer value column is intentionally the same width (100px / 80px on
   mobile) and same right-edge offset as table.rod-table .col-sales, so
   the Grand Total figure sits on the exact same vertical line as the
   per-branch sales figures above it. */
.rod-table-footer {
  display: flex; align-items: center; border-top: 1px solid #e6e6e9;
  padding: 12px 0 2px; margin-top: 4px;
}
.rod-table-footer .rtf-label { flex: 1; font-size: 12.5px; color: #8a8d98; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; text-align: left; padding-left: 8px; }
.rod-table-footer .rtf-value {
  flex: 0 0 100px; min-width: 100px; text-align: center; font-size: 16px; font-weight: 800; color: #4B5EBD;
  display: flex; align-items: center; justify-content: center;
}

.rod-link-btn { background: none; border: none; padding: 0; margin: 0; cursor: pointer; font: inherit; text-decoration: none; }
.rod-branch-name { font-size: 13px; font-weight: 400; color: #1e293b; }
.rod-branch-idx { font-size: 11px; color: #b3b5bd; font-weight: 600; margin-right: 8px; }

.rod-sales-amount { font-size: 13px; font-weight: 700; color: #4B5EBD; text-align: center; display: inline-block; width: 100%; }
.rod-sales-amount:hover { color: #2d3a8c; text-decoration: underline; }
/* Zero-value sales are still clickable (the modal can show interval/cash
   detail even when system sales are 0), so they get a light-blue tint
   instead of the greyed-out "disabled" look they had before. */
.rod-sales-amount.zero { color: #93c5fd; }
.rod-sales-amount.zero:hover { color: #3b82f6; text-decoration: underline; }

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
.exp-hero {
  text-align: center; padding: 18px 10px 20px; margin-bottom: 16px; border-radius: 12px;
  background: linear-gradient(135deg, #fff7ec, #fef3e2);
  border: 1px solid #fde8c8;
}
.exp-hero .exp-currency { display: block; font-size: 12px; font-weight: 700; color: #b8792f; letter-spacing: .5px; margin-bottom: 4px; text-transform: uppercase; }
.exp-hero .exp-amount { margin: 0; font-weight: 800; color: #92400e; font-size: 28px; letter-spacing: -.4px; }
.exp-hero .exp-label { margin: 8px 0 0; color: #b8792f; font-size: 11.5px; font-weight: 600; opacity: .85; }

.exp-cat-list { list-style: none; margin: 0; padding: 0; flex: 1; }
.exp-cat-list li { display: flex; flex-direction: column; gap: 7px; padding: 12px 4px; border-top: 1px solid #f5f5f6; font-size: 13px; border-radius: 8px; transition: background .15s ease; }
.exp-cat-list li:first-child { border-top: none; }
.exp-cat-list li:hover { background: #fafbff; }
.exp-cat-list .ecl-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.exp-cat-list .ecl-left { display: flex; align-items: center; gap: 10px; min-width: 0; }
.exp-cat-list .ecl-avatar {
  width: 26px; height: 26px; border-radius: 8px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 800; color: #fff; letter-spacing: .2px;
  box-shadow: 0 2px 5px rgba(0,0,0,0.12);
}
.exp-cat-list .ecl-label { color: #333; font-weight: 700; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.exp-cat-list .ecl-value { font-weight: 800; color: #111; flex-shrink: 0; font-size: 13.5px; }
.exp-cat-list .ecl-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.exp-cat-list .ecl-pct-badge {
  font-size: 10.5px; font-weight: 800; letter-spacing: .2px;
  color: #4B5EBD; background: #eff3ff; border: 1px solid #c5caec;
  padding: 2px 7px; border-radius: 20px; flex-shrink: 0; min-width: 38px; text-align: center;
}
.exp-cat-list .ecl-bar-track { height: 5px; border-radius: 3px; background: #f1f2f5; overflow: hidden; margin-left: 36px; }
.exp-cat-list .ecl-bar-fill { height: 100%; border-radius: 3px; transition: width .6s ease; }

/* ── Income & Expenditure card — scope-aware, 3 stat panels per tab ── */
.rod-card.accent-purple { --rod-accent: #7c3aed; }

.ie-scope-row { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
.ie-scope-label { font-size: 11px; font-weight: 700; color: #8a8d98; text-transform: uppercase; letter-spacing: .4px; }
.ie-scope-select { min-width: 200px; }

.ie-stat-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
.ie-stat {
  border-radius: 12px; padding: 18px 16px; text-align: center; border: 1px solid;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 4px; min-height: 96px; transition: background .2s ease, border-color .2s ease;
}
.ie-stat-label { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }
.ie-stat-amount { font-size: 21px; font-weight: 800; letter-spacing: -.3px; word-break: break-word; }
.ie-stat-tag { font-size: 10.5px; font-weight: 700; margin-top: 2px; }

.ie-stat-income { background: #eff6ff; border-color: #bfdbfe; }
.ie-stat-income .ie-stat-label { color: #2563eb; }
.ie-stat-income .ie-stat-amount { color: #1d4ed8; }

.ie-stat-expenditure { background: #fff7ec; border-color: #fde8c8; }
.ie-stat-expenditure .ie-stat-label { color: #b8792f; }
.ie-stat-expenditure .ie-stat-amount { color: #92400e; }

.ie-stat-diff { background: #f8fafc; border-color: #e2e8f0; }
.ie-stat-diff .ie-stat-label { color: #64748b; }
.ie-stat-diff .ie-stat-amount { color: #334155; }
.ie-stat-diff .ie-stat-amount.is-positive { color: #059669; }
.ie-stat-diff .ie-stat-amount.is-negative { color: #dc2626; }
.ie-stat-diff .ie-stat-tag.is-positive { color: #059669; }
.ie-stat-diff .ie-stat-tag.is-negative { color: #dc2626; }
.ie-stat-diff .ie-stat-tag.is-zero { color: #94a3b8; }
.ie-stat-diff.has-positive { background: #ecfdf5; border-color: #bbf7d0; }
.ie-stat-diff.has-negative { background: #fef2f2; border-color: #fecaca; }

/* ── Graph card ──────────────────────────────────────────────────── */
.rod-graph-legend { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
.rod-graph-legend span { display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #5b5f6b; white-space: nowrap; }
.rod-graph-legend i.dot { width: 9px; height: 9px; border-radius: 2px; display: inline-block; flex-shrink: 0; }
.rod-va-source {
  font-size: 12px; font-weight: 600; color: #4B5EBD; border: 1px solid #d8dbe3;
  border-radius: 6px; padding: 3px 8px; background: transparent; cursor: pointer; flex-shrink: 0;
}
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
  .rod-table-footer .rtf-value { flex-basis: 80px; min-width: 80px; }
  .rod-month-strip .rod-chip { min-width: 100%; }
  .rod-chip-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 6px; }
  .rod-chip-grid .rod-chip { padding: 8px 8px; }
  .rod-chip-grid .rms-value { font-size: 11.5px; }
  .rod-chip-grid .rms-label { font-size: 9.5px; }

  /* Hero figures (Deliverynotes / Inventory Movement) — on mobile show
     currency + amount as a single inline line ("MWK700,000.00") instead
     of stacking currency above the number, even though there's enough
     width to do so. */
  .rod-hero { flex-direction: row; flex-wrap: wrap; align-items: baseline; justify-content: center; padding: 12px 10px; }
  .rod-hero .rh-currency { display: inline; margin-bottom: 0; font-size: 15px; }
  .rod-hero .rh-amount { display: inline; font-size: 22px; }
  .rod-hero .rh-desc { width: 100%; margin-top: 6px; }

  /* Expenditure hero — same inline currency+amount treatment */
  .exp-hero { display: flex; flex-direction: row; flex-wrap: wrap; align-items: baseline; justify-content: center; padding: 12px 10px 14px; }
  .exp-hero .exp-currency { display: inline; margin-bottom: 0; font-size: 14px; }
  .exp-hero .exp-amount { display: inline; font-size: 20px; }
  .exp-hero .exp-label { width: 100%; margin-top: 6px; }

  /* Card headers (Sales, Deliverynotes, Inventory Movement, Expenditure,
     graph): title and tabs only wrap onto their own line when they
     genuinely don't fit — the title keeps its natural width instead of
     being forced to 100%, and the tabs flex into the remaining space
     first (scrolling horizontally if needed) before ever wrapping. */
  .rod-tab-strip.has-title {
    height: auto;
    flex-wrap: wrap !important;
    align-items: center;
    padding: 10px 14px;
    gap: 6px 10px;
  }
  .rod-tab-strip.has-title .rod-plain-title { flex-shrink: 0; }
  .rod-tab-strip.has-title .nav-pills {
    flex: 0 0 auto !important; max-width: 100%; margin: 0 0 0 auto !important; height: auto; overflow-x: auto; -webkit-overflow-scrolling: touch;
  }
  .rod-tab-strip.has-title .nav-link { padding: .4rem .7rem; font-size: 11.5px; }

  /* Graph header specifically: legend wraps onto its own line(s) and the
     Value Added source dropdown drops to full width below it, instead of
     all three competing for one 48px-tall row. */
  .rod-graph-legend { width: 100%; gap: 10px 14px; }
  .rod-graph-legend span { font-size: 11px; }
  .rod-va-source { width: 100%; margin-top: 2px; }

  /* Interval reconciliation modals — let the modal grow with its
     content instead of scrolling internally; the page itself scrolls
     if the modal is taller than the viewport. */
  .modal-dialog { margin: 1rem auto !important; max-width: calc(100% - 24px) !important; }
  .modal-content { border-radius: 10px !important; }
  table.rod-iv-table { font-size: 11px; }
  table.rod-iv-table thead th, table.rod-iv-table tbody td { padding: 6px 4px; }

  .rod-chart-empty { height: 260px; font-size: 12px; text-align: center; padding: 0 16px; }
  .exp-cat-list li { font-size: 12px; padding: 8px 2px; }

  /* Income & Expenditure — stack the 3 stat panels, scope select goes full width */
  .ie-scope-row { width: 100%; }
  .ie-scope-select { flex: 1; min-width: 0; }
  .ie-stat-grid { grid-template-columns: 1fr; gap: 10px; }
  .ie-stat { padding: 14px; min-height: auto; flex-direction: row; flex-wrap: wrap; justify-content: space-between; text-align: left; }
  .ie-stat-amount { font-size: 18px; }
}
@media (max-width: 420px) {
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

            {{-- ── TODAY — ranked highest → lowest sales ── --}}
            <div class="tab-pane fade show active" id="rodToday" role="tabpanel">
              <table class="rod-table">
                <thead><tr><th>Branch</th><th class="center col-sales">Sales</th></tr></thead>
                <tbody>
                  @forelse($branchesByToday as $i => $branch)
                    <tr>
                      <td>
                        <span class="rod-branch-idx">{{ $i + 1 }}</span>
                        <span class="rod-branch-name">{{ $branch->name }}</span>
                      </td>
                      <td class="center col-sales">
                        <button type="button" class="rod-link-btn rod-sales-amount {{ $branchStats[$branch->id]['sys_today'] > 0 ? '' : 'zero' }}" data-bs-toggle="modal" data-bs-target="#rodTodayModal{{ $branch->id }}">
                          {{ number_format($branchStats[$branch->id]['sys_today'], 0) }}
                        </button>
                      </td>
                    </tr>
                  @empty
                    <tr><td colspan="2" class="recon-empty-row">No active Retail branches found.</td></tr>
                  @endforelse
                </tbody>
              </table>
              <div class="rod-table-footer">
                <span class="rtf-label">Grand Total</span>
                <span class="rtf-value">MWK {{ number_format($todaysSales, 0) }}</span>
              </div>
            </div>

            {{-- ── YESTERDAY — ranked highest → lowest sales ── --}}
            <div class="tab-pane fade" id="rodYesterday" role="tabpanel">
              <table class="rod-table">
                <thead><tr><th>Branch</th><th class="center col-sales">Sales</th></tr></thead>
                <tbody>
                  @forelse($branchesByYesterday as $i => $branch)
                    <?php $sd = $branchStats[$branch->id]['diff_yesterday']; ?>
                    <tr>
                      <td>
                        <span class="rod-branch-idx">{{ $i + 1 }}</span>
                        <span class="rod-branch-name">{{ $branch->name }}</span>
                        <span class="rod-diff-tag {{ $sd > 0 ? 'pos' : ($sd < 0 ? 'neg' : 'zero') }}">
                          [{{ $sd > 0 ? '+' : '' }}{{ number_format($sd, 0) }}]
                        </span>
                      </td>
                      <td class="center col-sales">
                        <button type="button" class="rod-link-btn rod-sales-amount {{ $branchStats[$branch->id]['sys_yesterday'] > 0 ? '' : 'zero' }}" data-bs-toggle="modal" data-bs-target="#rodYesterdayModal{{ $branch->id }}">
                          {{ number_format($branchStats[$branch->id]['sys_yesterday'], 0) }}
                        </button>
                      </td>
                    </tr>
                  @empty
                    <tr><td colspan="2" class="recon-empty-row">No active Retail branches found.</td></tr>
                  @endforelse
                </tbody>
              </table>
              <div class="rod-table-footer">
                <span class="rtf-label">Grand Total</span>
                <span class="rtf-value">MWK {{ number_format($yesterdaysSales, 0) }}</span>
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

            {{-- ── TODAY — real spend, grouped by expenditure type name ── --}}
            <div class="tab-pane fade show active" id="rodExpToday" role="tabpanel">
              <div class="exp-hero">
                <span class="exp-currency">MWK</span>
                <div class="exp-amount">{{ number_format($expenditureToday, 2) }}</div>
                <p class="exp-label">Expenditure today</p>
              </div>
              <ul class="exp-cat-list">
                @php($rodExpPalette = ['#4B5EBD','#059669','#d97706','#dc2626','#0ea5e9','#7c3aed','#db2777','#0d9488'])
                @forelse($expCategoriesToday as $label => $amount)
                  <?php $rodPct = $expenditureToday > 0 ? round($amount / $expenditureToday * 100, 1) : 0; ?>
                  <li>
                    <div class="ecl-row">
                      <span class="ecl-left">
                        <span class="ecl-avatar" style="background:{{ $rodExpPalette[$loop->index % count($rodExpPalette)] }}">{{ strtoupper(substr($label, 0, 1)) }}</span>
                        <span class="ecl-label">{{ $label }}</span>
                      </span>
                      <span class="ecl-right">
                        <span class="ecl-value">MWK{{ number_format($amount, 0) }}</span>
                        <span class="ecl-pct-badge">{{ $rodPct }}%</span>
                      </span>
                    </div>
                    <div class="ecl-bar-track">
                      <div class="ecl-bar-fill" style="width:{{ $rodPct }}%; background:{{ $rodExpPalette[$loop->index % count($rodExpPalette)] }};"></div>
                    </div>
                  </li>
                @empty
                  <li class="recon-empty-row" style="width:100%;">No expenditure types configured yet.</li>
                @endforelse
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
                @forelse($expCategoriesThisMonth as $label => $amount)
                  <?php $rodPct = $expenditureThisMonth > 0 ? round($amount / $expenditureThisMonth * 100, 1) : 0; ?>
                  <li>
                    <div class="ecl-row">
                      <span class="ecl-left">
                        <span class="ecl-avatar" style="background:{{ $rodExpPalette[$loop->index % count($rodExpPalette)] }}">{{ strtoupper(substr($label, 0, 1)) }}</span>
                        <span class="ecl-label">{{ $label }}</span>
                      </span>
                      <span class="ecl-right">
                        <span class="ecl-value">MWK{{ number_format($amount, 0) }}</span>
                        <span class="ecl-pct-badge">{{ $rodPct }}%</span>
                      </span>
                    </div>
                    <div class="ecl-bar-track">
                      <div class="ecl-bar-fill" style="width:{{ $rodPct }}%; background:{{ $rodExpPalette[$loop->index % count($rodExpPalette)] }};"></div>
                    </div>
                  </li>
                @empty
                  <li class="recon-empty-row" style="width:100%;">No expenditure types configured yet.</li>
                @endforelse
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
                @forelse($expCategoriesLastMonth as $label => $amount)
                  <?php $rodPct = $expenditureLastMonth > 0 ? round($amount / $expenditureLastMonth * 100, 1) : 0; ?>
                  <li>
                    <div class="ecl-row">
                      <span class="ecl-left">
                        <span class="ecl-avatar" style="background:{{ $rodExpPalette[$loop->index % count($rodExpPalette)] }}">{{ strtoupper(substr($label, 0, 1)) }}</span>
                        <span class="ecl-label">{{ $label }}</span>
                      </span>
                      <span class="ecl-right">
                        <span class="ecl-value">MWK{{ number_format($amount, 0) }}</span>
                        <span class="ecl-pct-badge">{{ $rodPct }}%</span>
                      </span>
                    </div>
                    <div class="ecl-bar-track">
                      <div class="ecl-bar-fill" style="width:{{ $rodPct }}%; background:{{ $rodExpPalette[$loop->index % count($rodExpPalette)] }};"></div>
                    </div>
                  </li>
                @empty
                  <li class="recon-empty-row" style="width:100%;">No expenditure types configured yet.</li>
                @endforelse
              </ul>
            </div>

          </div>
        </div>
      </div>
    </div>

  </div>

  {{-- ════════════════ ROW 2.5 — Income & Expenditure (col-12, scope-aware) ════════════════
       Scope selector (All Branches / a specific branch) swaps the numbers
       shown in all three tabs client-side — every scope × period figure
       was already computed server-side into $incomeExpenditure, so there's
       no reload and no extra request when the scope changes. --}}
  <div class="row g-3 mt-1">

    <div class="col-12">
      <div class="card rod-card accent-purple" id="ieCard">
        <div class="rod-tab-strip has-title">
          <div class="rod-plain-title">
            <h4>Income &amp; Expenditure</h4>
          </div>
          <ul class="nav nav-pills mb-0" role="tablist">
            <li class="nav-item" role="presentation">
              <a class="nav-link active" data-bs-toggle="pill" href="#ieThisMonth" role="tab">This Month</a>
            </li>
            <li class="nav-item" role="presentation">
              <a class="nav-link" data-bs-toggle="pill" href="#ieLastMonth" role="tab">Last Month</a>
            </li>
            <li class="nav-item" role="presentation">
              <a class="nav-link" data-bs-toggle="pill" href="#ieTwoMonthsAgo" role="tab">{{ $lastMonthMinusOneName }}</a>
            </li>
          </ul>
        </div>
        <div class="card-body rod-card-body">

          <div class="ie-scope-row">
            <label for="ieScopeSelect" class="ie-scope-label">Scope</label>
            <select id="ieScopeSelect" class="rod-va-source ie-scope-select">
              @foreach($incomeExpenditure as $scopeId => $scopeData)
                <option value="{{ $scopeId }}">{{ $scopeData['label'] }}</option>
              @endforeach
            </select>
          </div>

          <div class="tab-content">
            @foreach([
              'this_month'     => 'ieThisMonth',
              'last_month'     => 'ieLastMonth',
              'two_months_ago' => 'ieTwoMonthsAgo',
            ] as $periodKey => $paneId)
              <div class="tab-pane fade {{ $periodKey === 'this_month' ? 'show active' : '' }}" id="{{ $paneId }}" role="tabpanel">
                <div class="ie-stat-grid">
                  <div class="ie-stat ie-stat-income">
                    <span class="ie-stat-label">Income</span>
                    <div class="ie-stat-amount" data-ie-field="income" data-ie-period="{{ $periodKey }}">MWK 0.00</div>
                  </div>
                  <div class="ie-stat ie-stat-expenditure">
                    <span class="ie-stat-label">Expenditure</span>
                    <div class="ie-stat-amount" data-ie-field="expenditure" data-ie-period="{{ $periodKey }}">MWK 0.00</div>
                  </div>
                  <div class="ie-stat ie-stat-diff">
                    <span class="ie-stat-label">Net Difference</span>
                    <div class="ie-stat-amount" data-ie-field="diff" data-ie-period="{{ $periodKey }}">MWK 0.00</div>
                    <span class="ie-stat-tag" data-ie-field="diff-tag" data-ie-period="{{ $periodKey }}"></span>
                  </div>
                </div>
              </div>
            @endforeach
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

{{-- ══════════════════════════════════════════════════════════════════
     Income & Expenditure — scope switcher (client-side, no reload)
     All scope × period figures are already computed server-side into
     $incomeExpenditure; the dropdown just swaps which scope's numbers
     are shown across all three (This Month / Last Month / N-2) tabs.
══════════════════════════════════════════════════════════════════ --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
  var ieData   = @json($incomeExpenditure);
  var ieCard   = document.querySelector('#ieCard');
  var ieSelect = document.querySelector('#ieScopeSelect');
  var iePeriodKeys = ['this_month', 'last_month', 'two_months_ago'];

  function ieFormatMoney(v) {
    var n = Number(v) || 0;
    var sign = n < 0 ? '-' : '';
    return sign + 'MWK ' + Math.abs(n).toLocaleString(undefined, {
      minimumFractionDigits: 2, maximumFractionDigits: 2
    });
  }

  function ieRender(scopeId) {
    if (!ieCard) return;
    var scope = ieData[scopeId];
    if (!scope) return;

    iePeriodKeys.forEach(function (periodKey) {
      var p = scope.periods[periodKey] || { income: 0, expenditure: 0, diff: 0 };

      var incomeEl = ieCard.querySelector('[data-ie-field="income"][data-ie-period="' + periodKey + '"]');
      var expEl    = ieCard.querySelector('[data-ie-field="expenditure"][data-ie-period="' + periodKey + '"]');
      var diffEl   = ieCard.querySelector('[data-ie-field="diff"][data-ie-period="' + periodKey + '"]');
      var tagEl    = ieCard.querySelector('[data-ie-field="diff-tag"][data-ie-period="' + periodKey + '"]');

      if (incomeEl) incomeEl.textContent = ieFormatMoney(p.income);
      if (expEl)    expEl.textContent = ieFormatMoney(p.expenditure);

      if (diffEl) {
        diffEl.textContent = (p.diff > 0 ? '+' : '') + ieFormatMoney(p.diff);
        diffEl.classList.remove('is-positive', 'is-negative', 'is-zero');

        var diffBox = diffEl.closest('.ie-stat-diff');
        if (diffBox) diffBox.classList.remove('has-positive', 'has-negative');

        if (p.diff > 0) {
          diffEl.classList.add('is-positive');
          if (diffBox) diffBox.classList.add('has-positive');
          if (tagEl) { tagEl.textContent = '▲ Surplus'; tagEl.className = 'ie-stat-tag is-positive'; }
        } else if (p.diff < 0) {
          diffEl.classList.add('is-negative');
          if (diffBox) diffBox.classList.add('has-negative');
          if (tagEl) { tagEl.textContent = '▼ Deficit'; tagEl.className = 'ie-stat-tag is-negative'; }
        } else {
          diffEl.classList.add('is-zero');
          if (tagEl) { tagEl.textContent = 'Break-even'; tagEl.className = 'ie-stat-tag is-zero'; }
        }
      }
    });
  }

  if (ieSelect) {
    ieRender(ieSelect.value);
    ieSelect.addEventListener('change', function () {
      ieRender(this.value);
    });
  }
});
</script>