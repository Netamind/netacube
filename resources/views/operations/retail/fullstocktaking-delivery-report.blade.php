<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1e293b; }
    .header { text-align: center; margin-bottom: 14px; }
    .header h1 { font-size: 15px; margin: 0 0 2px; color: #4B5EBD; }
    .header .sub { font-size: 10px; color: #64748b; }
    .meta-row { display: table; width: 100%; margin-bottom: 14px; font-size: 9.5px; }
    .meta-row .cell { display: table-cell; width: 33.3%; }
    .meta-row .lbl { color: #94a3b8; text-transform: uppercase; font-size: 8px; letter-spacing: .4px; }
    .meta-row .val { font-weight: 700; color: #1e293b; }

    table.data { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    table.data th { background: #4B5EBD; color: #fff; font-size: 8.5px; text-transform: uppercase; padding: 5px 6px; text-align: left; }
    table.data th.c { text-align: center; }
    table.data td { padding: 4px 6px; border-bottom: 1px solid #eef0f7; font-size: 9px; }
    table.data td.c { text-align: center; }
    table.data tr:nth-child(even) { background: #f8f9fc; }

    .total-row td { font-weight: 700; background: #f4f6ff; border-top: 1.5px solid #c5caec; }

    .sign-row { display: table; width: 100%; margin-top: 40px; }
    .sign-cell { display: table-cell; width: 50%; }
    .sign-line { border-top: 1px solid #94a3b8; margin-top: 30px; padding-top: 4px; font-size: 9px; color: #64748b; }

    .footer-note { margin-top: 14px; font-size: 8px; color: #94a3b8; text-align: center; }
</style>
</head>
<body>

    <div class="header">
        <h1>Stock Delivery Note</h1>
        <div class="sub">{{ $branchName }} &middot; {{ $displayDate }}</div>
    </div>

    <div class="meta-row">
        <div class="cell"><span class="lbl">Branch</span><br><span class="val">{{ $branchName }}</span></div>
        <div class="cell"><span class="lbl">Date</span><br><span class="val">{{ $displayDate }}</span></div>
        <div class="cell"><span class="lbl">Line Items</span><br><span class="val">{{ $countedRows->count() }}</span></div>
    </div>

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
            @forelse($countedRows as $row)
                <tr>
                    <td>{{ $row->product_name }}</td>
                    <td>{{ $row->unit }}</td>
                    <td class="c">{{ number_format($row->price, 2) }}</td>
                    <td class="c">{{ number_format($row->found, 2) }}</td>
                    <td class="c">{{ number_format($row->found * $row->price, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="c">No products counted for this date.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" style="text-align:right;">Total Value</td>
                <td class="c">{{ number_format($totalValue, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="sign-row">
        <div class="sign-cell"><div class="sign-line">Delivered by</div></div>
        <div class="sign-cell"><div class="sign-line">Received by</div></div>
    </div>

    <div class="footer-note">Generated {{ now()->format('d M Y, H:i') }}.</div>

</body>
</html>