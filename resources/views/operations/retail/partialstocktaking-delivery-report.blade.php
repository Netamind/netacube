<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Partial Stocktaking Found Quantities – {{ $branchName }} – {{ $displayDate }}</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{
    font-family:DejaVu Sans,sans-serif;
    font-size:9.5pt;color:#333;line-height:1.35;
    background:#fff;padding:16px;
}
.container{width:100%;max-width:800px;margin:0 auto;}

/* ---------- HEADER ---------- */
.header-table{width:100%;border-collapse:collapse;margin-bottom:8px;
    border-bottom:1.5px solid #4B5EBD;padding-bottom:6px;}
.header-table td{vertical-align:top;padding:0;font-size:9pt;line-height:1.3;}
.company-details h1{font-size:14pt;font-weight:700;color:#4B5EBD;margin:0 0 2px;}
.company-details p{margin:1px 0;font-size:8.5pt;}
.doc-details{width:45%;text-align:right;}
.doc-details strong{font-size:12pt;font-weight:700;color:#4B5EBD;}
.doc-details p{margin:1px 0;}

/* ---------- BRANCH + GENERATED-BY DETAILS ---------- */
.address-table{width:100%;border-collapse:collapse;margin:10px 0 12px;font-size:8.8pt;}
.address-table td{width:50%;padding:8px 10px;background:#f8f9fa;
    border:1px solid #e0e0e0;border-radius:5px;vertical-align:top;}
.address-table h3{
    font-size:11pt;font-weight:700;color:#4B5EBD;
    margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;
}
.address-table p{margin:1.5px 0;line-height:1.3;}
.address-table strong{color:#2c3e50;}

/* ---------- PRODUCTS TABLE ---------- */
table.items{width:100%;border-collapse:collapse;margin:8px 0 0;font-size:8.8pt;table-layout:fixed;}
table.items thead{display:table-header-group;} /* repeat header across pages — this list can run long */
table.items thead th{
    background:#4B5EBD;color:#fff;font-weight:800;font-size:9.5pt;
    text-align:center;padding:6px 5px;border:1px solid #999;
}
table.items thead th.l{text-align:left;}
table.items tbody td{padding:6px 5px;border:1px solid #ddd;vertical-align:middle;
    font-size:8.5pt;text-align:center;line-height:1;}
table.items tbody td.l{text-align:left;}
table.items tbody tr:nth-child(even){background:#f9f9fb;}

/* ---------- NOTES ---------- */
.notes{clear:both;margin-top:25px;padding-top:12px;
    border-top:1px dashed #ccc;font-size:8.5pt;}
.notes h4{color:#4B5EBD;margin-bottom:4px;font-size:9.5pt;}

/* ---------- FOOTER ---------- */
.footer{position:fixed;bottom:0;left:0;right:0;text-align:center;font-size:7.5pt;
    color:#777;border-top:1px solid #eee;padding-top:10px;padding-bottom:12px;background:#fff;}

@page{margin-top:0.6cm;margin-right:0.6cm;margin-bottom:2cm;margin-left:0.6cm;size:A4;}
</style>
</head>
<body>

<div class="container">
    @php
        $companyProfile = DB::connection('tenant')->table('company_info')->first();
        $companyName    = $companyProfile->business_name    ?? 'Netamind Technology';
        $companyAddress = $companyProfile->physical_address ?? null;
        $companyPhone   = $companyProfile->primary_number   ?? null;
        $companyEmail   = $companyProfile->email_address    ?? null;
        $totalValue     = $totalValue ?? $countedRows->sum(fn($r) => $r->found * $r->price);


        // Document number: first 3 letters of the branch name (uppercased,
        // padded with X if short) + PSQ (Partial Stocktaking found Quantities) + date.
        $branchPrefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $branchName ?: 'BR'), 0, 3));
        $branchPrefix = str_pad($branchPrefix, 3, 'X');
        $docNumber    = $branchPrefix . 'PSQ' . \Carbon\Carbon::parse($deliveryDate ?? $date)->format('Ymd');

        $footerParts = collect([$companyName, $companyAddress, $companyPhone, $companyEmail])
            ->filter(fn ($v) => ! empty($v));
    @endphp

    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td class="company-details">
                <h1>{{ $companyName }}</h1>
                @if($companyAddress)<p>{{ $companyAddress }}</p>@endif
                @if($companyPhone)<p>Phone: {{ $companyPhone }}</p>@endif
                @if($companyEmail)<p>Email: {{ $companyEmail }}</p>@endif
            </td>
            <td class="doc-details">
                <p><strong>Found Quantities</strong></p>
                <p><strong>{{ ucwords(strtolower($branchName)) }}</strong></p>
                <p>Date: {{ $displayDate }}</p>
            </td>
        </tr>
    </table>

    <!-- PRODUCTS TABLE -->
    <table class="items">
        <thead>
            <tr>
                <th style="width:5%;">#</th>
                <th class="l" style="width:34%;">Product</th>
                <th style="width:14%;">Unit</th>
                <th style="width:16%;">Price</th>
                <th style="width:11%;">Found</th>
                <th style="width:20%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $num = 1; @endphp
            @forelse($countedRows->sortBy('product_name') as $row)
                <tr>
                    <td>{{ $num++ }}</td>
                    <td class="l">{{ $row->product_name }}</td>
                    <td>{{ $row->unit }}</td>
                    <td>{{ number_format($row->price, 2) }}</td>
                    <td>{{ number_format($row->found, 2) }}</td>
                    <td style="font-weight:700;">{{ number_format($row->found * $row->price, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;padding:16px;color:#94a3b8;font-style:italic;">No products counted for this date.</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- NOTES -->
    <div class="notes">
        <h4>Important Notice</h4>
        <p>Please cross-check the found quantities on this sheet against the actual physical stock, and immediately report any discrepancies.</p>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <p>{{ $footerParts->implode(' | ') }}</p>
        <p>{{ $branchName }} &middot; Partial Stocktaking Found Quantities &middot; {{ $displayDate }} &middot; Generated {{ now()->format('d M Y, H:i') }}</p>
    </div>

</div>
</body>
</html>