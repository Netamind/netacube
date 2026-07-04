<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Full Stocktaking Found Quantities – {{ $branchName }} – {{ $displayDate }}</title>
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
table.hdr-t { width: 100%; border-collapse: collapse; background: #f5f5f6; border-bottom: 1px solid #e4e4e4; }
table.hdr-t td { padding: 16px 32px 12px 32px; vertical-align: top; }
table.hdr-t td.hdr-right { text-align: right; }

table.hdr-divider { width: 100%; border-collapse: collapse; }
table.hdr-divider td { height: 1px; padding: 0; line-height: 0; font-size: 0; background: #4B5EBD; }

.co-name { font-size: 18px; font-weight: 700; color: #111; margin-bottom: 3px; line-height: 1; }
.co-meta { font-size: 9px; color: #666; line-height: 1.6; }

.doc-word {
    font-size: 14px; font-weight: 700; color: #4B5EBD;
    letter-spacing: 1.6px; text-transform: uppercase; margin-bottom: 8px;
}
.d-item { margin-bottom: 3px; }
.d-item label {
    font-size: 7.5px; text-transform: uppercase; letter-spacing: 1.1px;
    color: #999; font-weight: 700; margin-right: 6px;
}
.d-item span { font-size: 10px; font-weight: 700; color: #111; }

/* ── DATA TABLE ── */
.section-wrap { padding: 18px 12px 0; }
table.data { width: 100%; border-collapse: collapse; margin-bottom: 6px; table-layout: fixed; }
table.data thead { display: table-header-group; }
table.data thead th {
    color: #2d2d3a; font-size: 9px; text-transform: uppercase; letter-spacing: .5px;
    font-weight: 700; padding: 6px 7px; text-align: center;
    background: #d4d4d8; border-bottom: 1.5px solid #b0b0b8;
}
table.data thead th.l { text-align: left; }
table.data tbody tr { border-bottom: 1px solid #eef0f7; page-break-inside: avoid; }
table.data tbody tr:nth-child(even) { background: #fafafa; }
table.data tbody td { padding: 5px 7px; font-size: 13px; color: #1e293b; overflow: hidden; text-align: center; }
table.data tbody td.l { text-align: left; }

table.data tfoot td { padding: 6px 7px; font-size: 10.5px; }
table.data tfoot td.gt-label { text-align: center; font-weight: 700; color: #111; background: #d4d4d8; border-top: 1.5px solid #b0b0b8; border-bottom: 2px solid #4B5EBD; }
table.data tfoot td.gt-value { text-align: center; font-weight: 800; color: #4B5EBD; font-size: 11.5px; background: #d4d4d8; border-top: 1.5px solid #b0b0b8; border-bottom: 2px solid #4B5EBD; }

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
    $totalValue     = $countedRows->sum(fn($r) => $r->found * $r->price);
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
            <div class="doc-word">Full Stocktaking Found Quantities</div>
            <div class="d-item"><label>Branch</label><span>{{ $branchName }}</span></div>
            <div class="d-item"><label>Date</label><span>{{ $displayDate }}</span></div>
        </td>
    </tr>
</table>
<table class="hdr-divider"><tr><td></td></tr></table>

<!-- DATA TABLE -->
<div class="section-wrap">
    <table class="data">
        <thead>
            <tr>
                <th class="l" style="width:34%">Product</th>
                <th style="width:14%">Unit</th>
                <th style="width:17%">Price</th>
                <th style="width:15%">Found</th>
                <th style="width:20%">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($countedRows->sortBy('product_name') as $row)
                <tr>
                    <td class="l" style="font-weight:600;">{{ $row->product_name }}</td>
                    <td style="color:#64748b;">{{ $row->unit }}</td>
                    <td style="color:#64748b;">{{ number_format($row->price, 2) }}</td>
                    <td>{{ number_format($row->found, 2) }}</td>
                    <td style="font-weight:700;">{{ number_format($row->found * $row->price, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;padding:16px;color:#94a3b8;font-style:italic;">No products counted for this date.</td></tr>
            @endforelse
        </tbody>
        @if($countedRows->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="3" style="border:none;background:transparent;"></td>
                <td class="gt-label">Total value</td>
                <td class="gt-value">{{ number_format($totalValue, 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
</div>

<!-- FOOTER — always pinned to bottom -->
<div class="footer-fixed">
    <table class="pg-foot">
        <tr>
            <td>{{ $branchName }} &nbsp;&middot;&nbsp; Full Stocktaking Found Quantities &nbsp;&middot;&nbsp; {{ $displayDate }}</td>
            <td class="pg-right">Generated {{ now()->format('d M Y, H:i') }}</td>
        </tr>
    </table>
</div>

</body>
</html>