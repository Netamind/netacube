<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Full Stocktaking Report – {{ $branchName }} – {{ $displayDate }}</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

@page {
    size: A4;
    margin-top: 0;
    margin-right: 0;
    margin-bottom: 60px;
    margin-left: 0;
}

html, body {
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 11px;
    color: #111;
    background: #fff;
}

/* ── HEADER ── */
table.hdr-t {
    width: 100%;
    border-collapse: collapse;
    background: #f5f5f6;
    border-bottom: 1px solid #e4e4e4;
}
table.hdr-t td { padding: 16px 32px 12px 32px; vertical-align: top; }
table.hdr-t td.hdr-right { text-align: right; }

table.hdr-divider { width: 100%; border-collapse: collapse; }
table.hdr-divider td { height: 1px; padding: 0; line-height: 0; font-size: 0; background: #4B5EBD; }

.co-name { font-size: 19px; font-weight: 700; color: #111; margin-bottom: 3px; line-height: 1; }
.co-meta { font-size: 10px; color: #666; line-height: 1.6; }

.doc-word {
    font-size: 15px; font-weight: 700; color: #4B5EBD;
    letter-spacing: 1.6px; text-transform: uppercase; margin-bottom: 8px;
}
.d-item { margin-bottom: 3px; }
.d-item label {
    font-size: 8px; text-transform: uppercase; letter-spacing: 1.1px;
    color: #999; font-weight: 700; margin-right: 6px;
}
.d-item span { font-size: 11px; font-weight: 700; color: #111; }

/* ── SECTION TITLES ── */
.section-wrap { padding: 0 24px; margin-top: 10px; }
.section-title {
    font-size: 9.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px;
    color: #7a7a85; margin-top: 16px; margin-bottom: 7px; padding-bottom: 3px;
    border-bottom: 1.5px solid #c4c4c8; display: inline-block;
}
.section-title:first-child { margin-top: 0; }

/* ── SUMMARY CARDS ── */
table.summary-grid {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 18px;
}
table.summary-grid td.sum-card-l {
    vertical-align: top;
    width: 50%;
    padding-right: 8px;
}
table.summary-grid td.sum-card-r {
    vertical-align: top;
    width: 50%;
    padding-left: 8px;
}

.sum-card {
    background: #f1f1f3;
    border: 1px solid #e0e0e2;
    border-radius: 6px;
    padding: 10px 10px 6px;
}

/* Single sum-t style used in both cards */
table.sum-t { width: 100%; border-collapse: collapse; }
table.sum-t thead th {
    color: #4B5EBD; font-size: 8.5px; text-transform: uppercase; letter-spacing: .5px;
    font-weight: 700; padding: 5px 6px; text-align: left; border-bottom: 1.5px solid #4B5EBD;
}
table.sum-t thead th.c { text-align: center; }
table.sum-t tbody tr { border-bottom: 1px solid #e2e2e6; }
table.sum-t tbody tr:last-child { border-bottom: none; }
table.sum-t tbody td { padding: 4.5px 6px; font-size: 10.5px; color: #1e293b; text-align: left; }
table.sum-t tbody td.c { text-align: center; }
table.sum-t tbody td.sum-val { font-weight: 700; white-space: nowrap; }
table.sum-t tbody td.sum-pct { color: #94a3b8; }

/* Full difference row — visually separated at the bottom of the right card */
table.sum-t tbody tr.sum-total-sep td {
    font-weight: 800;
    background: #e6e9f7;
    border-top: 2px solid #b8bfe8;
    padding-top: 6px;
    padding-bottom: 6px;
}

.pos-val { color: #059669; }
.neg-val { color: #dc2626; }

/* ── DATA TABLES ── */
table.data { width: 100%; border-collapse: collapse; margin-bottom: 18px; table-layout: fixed; }
table.data thead { display: table-header-group; }
table.data thead th {
    color: #2d2d3a; font-size: 9px; text-transform: uppercase; letter-spacing: .5px;
    font-weight: 700; padding: 6px 7px; text-align: center;
    background: #d4d4d8; border-bottom: 1.5px solid #b0b0b8;
}
table.data thead th.l { text-align: left; }
table.data tbody tr { border-bottom: 1px solid #eef0f7; page-break-inside: avoid; }
table.data tbody tr:nth-child(even) { background: #fafafa; }
table.data tbody td { padding: 4.5px 7px; font-size: 13px; color: #1e293b; overflow: hidden; text-align: center; }
table.data tbody td.l { text-align: left; }

table.data tfoot td { padding: 5px 7px; font-size: 10.5px; }
table.data tfoot td.gt-label { text-align: center; font-weight: 700; color: #111; background: #d7d7da; border-top: 1.5px solid #b0b0b8; border-bottom: 2px solid #4B5EBD; }
table.data tfoot td.gt-value { text-align: center; font-weight: 800; color: #4B5EBD; font-size: 11.5px; background: #d7d7da; border-top: 1.5px solid #b0b0b8; border-bottom: 2px solid #4B5EBD; }

.diff-pos { color: #059669; font-weight: 700; }
.diff-neg { color: #dc2626; font-weight: 700; }
.diff-zero { color: #94a3b8; }

/* ── FOOTER ── */
.footer-fixed { position: fixed; bottom: 0; left: 0; right: 0; }
table.pg-foot { width: 100%; border-collapse: collapse; border-top: 2px solid #4B5EBD; background: #f5f5f6; }
table.pg-foot td { padding: 6px 32px; font-size: 9.5px; color: #555; vertical-align: middle; }
table.pg-foot td.pg-right { text-align: right; }
</style>
</head>
<body>

@php
    $companyName    = $companyName    ?? 'Netamind Technology';
    $companyAddress = $companyAddress ?? null;

    $expectedTotal  = $countedRows->sum(fn($r) => ($r->expected_final ?? $r->expected_at_count) * $r->price);
    $foundTotal     = $countedRows->sum(fn($r) => $r->found * $r->price);
    $missingTotal   = $missingRows->sum(fn($r) => $r->quantity * $r->price);
    $difference     = $foundTotal - $expectedTotal;
    $fullDifference = $difference - $missingTotal;

    $totalCounted  = $countedRows->count();
    $noAnomaly     = $countedRows->filter(fn($r) => abs($r->found - ($r->expected_final ?? $r->expected_at_count)) < 0.0001)->count();
    $overageCount  = $countedRows->filter(fn($r) => $r->found > ($r->expected_final ?? $r->expected_at_count) + 0.0001)->count();
    $shortageCount = $countedRows->filter(fn($r) => $r->found < ($r->expected_final ?? $r->expected_at_count) - 0.0001)->count();
    $overageVal    = $countedRows->filter(fn($r) => $r->found > ($r->expected_final ?? $r->expected_at_count) + 0.0001)
                        ->sum(fn($r) => ($r->found - ($r->expected_final ?? $r->expected_at_count)) * $r->price);
    $shortageVal   = $countedRows->filter(fn($r) => $r->found < ($r->expected_final ?? $r->expected_at_count) - 0.0001)
                        ->sum(fn($r) => (($r->expected_final ?? $r->expected_at_count) - $r->found) * $r->price);
    $missingCount  = $missingRows->count();
    $safeCounted   = max($totalCounted, 1);
    $safeAll       = max($totalCounted + $missingCount, 1);

    $zeroPct    = round(($noAnomaly    / $safeCounted) * 100, 2);
    $posPct     = round(($overageCount / $safeCounted) * 100, 2);
    $negPct     = round(($shortageCount/ $safeCounted) * 100, 2);
    $missingPct = round(($missingCount / $safeAll)     * 100, 2);
@endphp

<!-- HEADER -->
<table class="hdr-t">
    <tr>
        <td>
            <div class="co-name">{{ $companyName }}</div>
            @if($companyAddress)
                <div class="co-meta">{{ $companyAddress }}</div>
            @endif
        </td>
        <td class="hdr-right">
            <div class="doc-word">Full Stocktaking Report</div>
            <div class="d-item"><label>Branch</label><span>{{ $branchName }}</span></div>
            <div class="d-item"><label>Date</label><span>{{ $displayDate }}</span></div>
        </td>
    </tr>
</table>
<table class="hdr-divider"><tr><td></td></tr></table>

<!-- SUMMARY -->
<div class="section-wrap">
    <div class="section-title">Full Stocktaking Summary</div>

    <table class="summary-grid">
        <tr>
            {{-- LEFT CARD --}}
            <td class="sum-card-l">
                <div class="sum-card">
                    <table class="sum-t">
                        <thead>
                            <tr><th>Description</th><th class="c">Value</th><th class="c">%</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Products counted</td><td class="c sum-val">{{ $totalCounted }}</td><td class="c sum-pct">100.00</td></tr>
                            <tr><td>No anomalies</td><td class="c sum-val">{{ $noAnomaly }}</td><td class="c sum-pct">{{ $zeroPct }}</td></tr>
                            <tr><td>Products with overages</td><td class="c sum-val pos-val">{{ $overageCount }}</td><td class="c sum-pct">{{ $posPct }}</td></tr>
                            <tr><td>Overage value</td><td class="c sum-val pos-val">{{ number_format($overageVal, 2) }}</td><td class="c sum-pct">—</td></tr>
                            <tr><td>Products with shortages</td><td class="c sum-val neg-val">{{ $shortageCount }}</td><td class="c sum-pct">{{ $negPct }}</td></tr>
                            <tr><td>Shortage value</td><td class="c sum-val neg-val">{{ number_format($shortageVal, 2) }}</td><td class="c sum-pct">—</td></tr>
                        </tbody>
                    </table>
                </div>
            </td>

            {{-- RIGHT CARD — Full difference separated by a top border at the bottom --}}
            <td class="sum-card-r">
                <div class="sum-card">
                    <table class="sum-t">
                        <thead>
                            <tr><th>Description</th><th class="c">Value</th><th class="c">%</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Expected value (EV)</td><td class="c sum-val">{{ number_format($expectedTotal, 2) }}</td><td class="c sum-pct">—</td></tr>
                            <tr><td>Found value (FV)</td><td class="c sum-val">{{ number_format($foundTotal, 2) }}</td><td class="c sum-pct">—</td></tr>
                            <tr><td>Difference (FV - EV)</td><td class="c sum-val {{ $difference >= 0 ? 'pos-val' : 'neg-val' }}">{{ number_format($difference, 2) }}</td><td class="c sum-pct">—</td></tr>
                            <tr><td>Missing items</td><td class="c sum-val">{{ $missingCount }}</td><td class="c sum-pct">{{ $missingPct }}</td></tr>
                            <tr><td>Missing value (MV)</td><td class="c sum-val neg-val">{{ number_format($missingTotal, 2) }}</td><td class="c sum-pct">—</td></tr>
                            <tr class="sum-total-sep">
                                <td>Full difference (FV - (EV + MV))</td>
                                <td class="c sum-val {{ $fullDifference >= 0 ? 'pos-val' : 'neg-val' }}">{{ number_format($fullDifference, 2) }}</td>
                                <td class="c sum-pct">—</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- COUNTED PRODUCTS -->
    <div class="section-title">Counted Products ({{ $countedRows->count() }})</div>
    <table class="data">
        <thead>
            <tr>
                <th class="l" style="width:30%">Product</th>
                <th style="width:12%">Unit</th>
                <th style="width:15%">Price</th>
                <th style="width:15%">Expected</th>
                <th style="width:14%">Found</th>
                <th style="width:14%">Diff</th>
            </tr>
        </thead>
        <tbody>
            @forelse($countedRows->sortBy('product_name') as $row)
                @php
                    $exp  = $row->expected_final ?? $row->expected_at_count;
                    $diff = $row->found - $exp;
                    $cls  = abs($diff) < 0.0001 ? 'diff-zero' : ($diff > 0 ? 'diff-pos' : 'diff-neg');
                @endphp
                <tr>
                    <td class="l" style="font-weight:600;">{{ $row->product_name }}</td>
                    <td style="color:#64748b;">{{ $row->unit }}</td>
                    <td style="color:#64748b;">{{ number_format($row->price, 2) }}</td>
                    <td>{{ number_format($exp, 2) }}</td>
                    <td>{{ number_format($row->found, 2) }}</td>
                    <td class="{{ $cls }}">{{ abs($diff) < 0.0001 ? '—' : (($diff > 0 ? '+' : '') . number_format($diff, 2)) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;padding:16px;color:#94a3b8;font-style:italic;">No products counted for this date.</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- MISSING PRODUCTS -->
    <div class="section-title">Missing Products ({{ $missingRows->count() }})</div>
    <table class="data">
        <thead>
            <tr>
                <th class="l" style="width:32%">Product</th>
                <th style="width:13%">Unit</th>
                <th style="width:17%">Price</th>
                <th style="width:17%">Quantity</th>
                <th style="width:21%">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($missingRows->sortByDesc(fn($r) => $r->quantity * $r->price) as $m)
                <tr>
                    <td class="l" style="font-weight:600;">{{ $m->product_name }}</td>
                    <td style="color:#64748b;">{{ $m->unit }}</td>
                    <td style="color:#64748b;">{{ number_format($m->price, 2) }}</td>
                    <td>{{ number_format($m->quantity, 2) }}</td>
                    <td style="font-weight:700;color:#dc2626;">{{ number_format($m->quantity * $m->price, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;padding:16px;color:#94a3b8;font-style:italic;">No missing products for this date.</td></tr>
            @endforelse
        </tbody>
        @if($missingRows->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="3" style="border:none;"></td>
                <td class="gt-label">Total missing value</td>
                <td class="gt-value">{{ number_format($missingTotal, 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

<!-- FOOTER -->
<div class="footer-fixed">
    <table class="pg-foot">
        <tr>
            <td>{{ $branchName }} &nbsp;&middot;&nbsp; Full Stocktaking Report &nbsp;&middot;&nbsp; {{ $displayDate }}</td>
            <td class="pg-right">Generated {{ now()->format('d M Y, H:i') }}</td>
        </tr>
    </table>
</div>

</body>
</html>