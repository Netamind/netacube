<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ str_replace(' ', '-', trim($filters->branch_name)) }}-{{ $filters->category }}-Order-{{ \Carbon\Carbon::parse($filters->date)->format('d-M-Y') }}</title>
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
table.items tbody td .prod-note{font-size:7.5pt;color:#7c3aed;font-weight:700;margin-left:4px;}

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

        $displayDate = \Carbon\Carbon::parse($filters->date)->format('d M Y');

        $categoryAbbr = match($filters->category) {
            'Regular'   => 'RO',
            'Emergency' => 'EO',
            'Rare'      => 'RAO',
            default     => strtoupper(substr($filters->category, 0, 2)),
        };

        // Document number: first 3 letters of the branch name (uppercased,
        // padded with X if short) + category abbreviation + today's date —
        // there's no fixed "order date" anymore since this is a live,
        // ever-refreshing list rather than a one-time batch.
        $branchPrefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $filters->branch_name ?: 'BR'), 0, 3));
        $branchPrefix = str_pad($branchPrefix, 3, 'X');
        $docNumber    = $branchPrefix . $categoryAbbr . \Carbon\Carbon::parse($filters->date)->format('Ymd');

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
                <p><strong>{{ $filters->category }} Order</strong></p>
                <p><strong>{{ ucwords(strtolower($filters->branch_name)) }}</strong></p>
                {{-- Always shown — "download file should indicate supplier /
                     All Suppliers" applies to every download, not just
                     supplier-scoped ones. --}}
                <p>Supplier: {{ $filters->supplier_label }}</p>
                <p>Doc No: {{ $docNumber }}</p>
                <p>Date: {{ $displayDate }}</p>
            </td>
        </tr>
    </table>

    <!-- PRODUCTS TABLE -->
    <table class="items">
        <thead>
            <tr>
                <th style="width:5%;">#</th>
                <th class="l" style="width:33%;">Product</th>
                <th style="width:11%;">Unit</th>
                <th style="width:13%;">Qty@Order</th>
                <th style="width:13%;">OrderQty</th>
                <th style="width:12%;">Date</th>
                <th style="width:13%;">Current Qty</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lines as $i => $l)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="l">
                        {{ $l->product_name }}
                        @if($l->is_custom)
                            <span class="prod-note">(custom)</span>
                        @endif
                    </td>
                    <td>{{ $l->units ?? '—' }}</td>
                    <td>{{ $l->stock_at_order !== null ? number_format($l->stock_at_order, 0) : '—' }}</td>
                    <td style="font-weight:700;">{{ $l->quantity }}</td>
                    <td>{{ \Carbon\Carbon::parse($l->date)->format('d M Y') }}</td>
                    <td>{{ isset($l->current_qty) && $l->current_qty !== null ? number_format($l->current_qty, 0) : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;padding:16px;color:#94a3b8;font-style:italic;">No items on this order.</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- FOOTER -->
    <div class="footer">
        <p>{{ $footerParts->implode(' | ') }}</p>
        <p>{{ $filters->branch_name }} &middot; {{ $filters->category }} Order &middot; Supplier: {{ $filters->supplier_label }} &middot; Generated {{ now()->format('d M Y, H:i') }}</p>
    </div>

</div>
</body>
</html>