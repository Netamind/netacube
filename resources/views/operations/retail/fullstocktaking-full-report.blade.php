<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1e293b; }
    .header { text-align: center; margin-bottom: 14px; }
    .header h1 { font-size: 15px; margin: 0 0 2px; color: #4B5EBD; }
    .header .sub { font-size: 10px; color: #64748b; }
    .meta-row { display: table; width: 100%; margin-bottom: 12px; font-size: 9.5px; }
    .meta-row .cell { display: table-cell; width: 33.3%; }
    .meta-row .lbl { color: #94a3b8; text-transform: uppercase; font-size: 8px; letter-spacing: .4px; }
    .meta-row .val { font-weight: 700; color: #1e293b; }

    .section-title { font-size: 11px; font-weight: 700; color: #4B5EBD; margin: 14px 0 6px; border-bottom: 1.5px solid #c5caec; padding-bottom: 3px; }

    table.data { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    table.data th { background: #4B5EBD; color: #fff; font-size: 8.5px; text-transform: uppercase; padding: 5px 6px; text-align: left; }
    table.data th.c { text-align: center; }
    table.data td { padding: 4px 6px; border-bottom: 1px solid #eef0f7; font-size: 9px; }
    table.data td.c { text-align: center; }
    table.data tr:nth-child(even) { background: #f8f9fc; }

    .pos { color: #059669; font-weight: 700; }
    .neg { color: #dc2626; font-weight: 700; }
    .zero { color: #64748b; }

    .summary-box { background: #f4f6ff; border: 1px solid #c5caec; border-radius: 4px; padding: 10px 12px; margin-top: 10px; }
    .summary-row { display: table; width: 100%; font-size: 9.5px; padding: 2px 0; }
    .summary-row .lbl { display: table-cell; width: 70%; color: #475569; }
    .summary-row .val { display: table-cell; width: 30%; text-align: right; font-weight: 700; color: #1e293b; }

    .footer-note { margin-top: 14px; font-size: 8px; color: #94a3b8; text-align: center; }
</style>
</head>
<body>

    <div class="header">
        <h1>Full Stocktaking Report</h1>
        <div class="sub">{{ $branchName }} &middot; {{ $displayDate }}</div>
    </div>

    <div class="meta-row">
        <div class="cell"><span class="lbl">Branch</span><br><span class="val">{{ $branchName }}</span></div>
        <div class="cell"><span class="lbl">Date</span><br><span class="val">{{ $displayDate }}</span></div>
        <div class="cell"><span class="lbl">Status</span><br><span class="val">{{ $summary ? 'Rectified' : 'Not yet rectified (live preview)' }}</span></div>
    </div>

    <div class="section-title">Counted Products ({{ $countedRows->count() }})</div>
    <table class="data">
        <thead>
            <tr>
                <th>Product</th>
                <th>Unit</th>
                <th class="c">Price</th>
                <th class="c">Expected</th>
                <th class="c">Found</th>
                <th class="c">Difference</th>
                <th class="c">Diff. Value</th>
            </tr>
        </thead>
        <tbody>
            @forelse($countedRows as $row)
                @php
                    $expectedDisplay = $row->expected_final ?? $row->expected_at_count;
                    $diff = $row->found - $expectedDisplay;
                    $diffValue = $diff * $row->price;
                    $diffClass = $diff > 0 ? 'pos' : ($diff < 0 ? 'neg' : 'zero');
                @endphp
                <tr>
                    <td>{{ $row->product_name }}</td>
                    <td>{{ $row->unit }}</td>
                    <td class="c">{{ number_format($row->price, 2) }}</td>
                    <td class="c">{{ number_format($expectedDisplay, 2) }}</td>
                    <td class="c">{{ number_format($row->found, 2) }}</td>
                    <td class="c {{ $diffClass }}">{{ number_format($diff, 2) }}</td>
                    <td class="c {{ $diffClass }}">{{ number_format($diffValue, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="c">No products counted for this date.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Missing Products ({{ $missingRows->count() }})</div>
    <table class="data">
        <thead>
            <tr>
                <th>Product</th>
                <th>Unit</th>
                <th class="c">Price</th>
                <th class="c">Quantity</th>
                <th class="c">Value</th>
            </tr>
        </thead>
        <tbody>
            @forelse($missingRows as $row)
                <tr>
                    <td>{{ $row->product_name }}</td>
                    <td>{{ $row->unit }}</td>
                    <td class="c">{{ number_format($row->price, 2) }}</td>
                    <td class="c">{{ number_format($row->quantity, 2) }}</td>
                    <td class="c">{{ number_format($row->quantity * $row->price, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="c">No missing products for this date.</td></tr>
            @endforelse
        </tbody>
    </table>

    @php
        $expectedTotal = $countedRows->sum(fn($r) => ($r->expected_final ?? $r->expected_at_count) * $r->price);
        $foundTotal    = $countedRows->sum(fn($r) => $r->found * $r->price);
        $missingTotal  = $missingRows->sum(fn($r) => $r->quantity * $r->price);
        $difference    = $foundTotal - $expectedTotal;
        $fullDifference = $difference - $missingTotal;
    @endphp

    <div class="summary-box">
        <div class="summary-row"><span class="lbl">Expected Value (EV)</span><span class="val">{{ number_format($expectedTotal, 2) }}</span></div>
        <div class="summary-row"><span class="lbl">Found Value (FV)</span><span class="val">{{ number_format($foundTotal, 2) }}</span></div>
        <div class="summary-row"><span class="lbl">Difference (FV - EV)</span><span class="val">{{ number_format($difference, 2) }}</span></div>
        <div class="summary-row"><span class="lbl">Missing Value (MV)</span><span class="val">{{ number_format($missingTotal, 2) }}</span></div>
        <div class="summary-row"><span class="lbl"><strong>Full Difference (FV - (EV + MV))</strong></span><span class="val"><strong>{{ number_format($fullDifference, 2) }}</strong></span></div>
    </div>

    <div class="footer-note">
        Sales made after each product was counted have been automatically netted out using sale-sequence tracking.
        Generated {{ now()->format('d M Y, H:i') }}.
    </div>

</body>
</html>