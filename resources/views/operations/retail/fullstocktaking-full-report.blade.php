<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Full Stocktaking Report – {{ $branchName }} – {{ $displayDate }}</title>
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

.status-chip{
    display:inline-block;margin-top:6px;padding:3px 9px;border-radius:10px;
    font-size:8pt;font-weight:700;text-transform:uppercase;letter-spacing:.6px;
}
.status-rectified{background:#d1fae5;color:#059669;}
.status-open{background:#fef3c7;color:#b45309;}

/* ---------- BRANCH + SNAPSHOT / BREAKDOWN BOXES ---------- */
.address-table{width:100%;border-collapse:collapse;margin:10px 0 12px;font-size:8.8pt;}
.address-table td{width:50%;padding:8px 10px;background:#f8f9fa;
    border:1px solid #e0e0e0;border-radius:5px;vertical-align:top;}
.address-table h3{
    font-size:11pt;font-weight:700;color:#4B5EBD;
    margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;
}
.address-table p{margin:1.5px 0;line-height:1.3;padding-bottom:3px;border-bottom:1px dotted #e2e5ea;}
.address-table p:last-child{border-bottom:none;padding-bottom:0;}
.address-table strong{color:#2c3e50;}
.pos-val{color:#059669;}
.neg-val{color:#dc2626;}

/* ---------- REMARKS ---------- */
.remarks-box{background:#fffbea;border:1px solid #fde68a;border-radius:6px;
    padding:10px 12px;font-size:9pt;color:#334155;line-height:1.5;
    white-space:pre-wrap;margin:0 0 12px;}

/* ---------- SECTION TITLES ---------- */
.section-title{
    font-size:9.5pt;font-weight:800;text-transform:uppercase;letter-spacing:1px;
    color:#7a7a85;margin:14px 0 6px;padding-bottom:3px;
    border-bottom:1.5px solid #c4c4c8;display:inline-block;
}

/* ---------- ITEMS TABLES ---------- */
table.items{width:100%;border-collapse:collapse;margin:0 0 4px;font-size:8.8pt;table-layout:fixed;}
table.items thead{display:table-header-group;} /* repeat header across pages — lists can run long */
table.items thead th{
    background:#4B5EBD;color:#fff;font-weight:800;font-size:9.5pt;
    text-align:center;padding:6px 5px;border:1px solid #999;
}
table.items thead th.l{text-align:left;}
table.items tbody td{padding:6px 5px;border:1px solid #ddd;vertical-align:middle;
    font-size:8.5pt;text-align:center;line-height:1;}
table.items tbody td.l{text-align:left;}
table.items tbody tr:nth-child(even){background:#f9f9fb;}

.diff-pos{font-weight:700;}
.diff-neg{font-weight:700;}
.diff-zero{font-weight:400;color:#94a3b8;}
.missing-val{color:#dc2626;font-weight:700;}

/* ---------- NOTES ---------- */
.notes{clear:both;margin-top:14px;padding-top:12px;
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
        $missingRows    = $missingRows ?? collect();
        $isRectified    = isset($summary) && $summary && $summary->status === 'completed';

        // Live figures are always recomputed from the current rows so the PDF
        // matches what's on screen, whether or not this date has been
        // rectified yet.
        $expectedTotal  = $countedRows->sum(fn($r) => ($r->expected_final ?? $r->expected_at_count) * $r->price);
        $foundTotal     = $countedRows->sum(fn($r) => $r->found * $r->price);
        $missingTotal   = $missingRows->sum(fn($r) => $r->quantity * $r->price);
        $difference     = $foundTotal - $expectedTotal;
        // Final difference brings missing products into the picture: what
        // should have been on the shelf (expected + what's now missing)
        // versus what was actually found.
        $finalDifference = $foundTotal - ($expectedTotal + $missingTotal);

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

        $zeroPct = round(($noAnomaly / $safeCounted) * 100, 2);
        $posPct  = round(($overageCount / $safeCounted) * 100, 2);
        $negPct  = round(($shortageCount / $safeCounted) * 100, 2);

        // Document number: first 3 letters of the branch name (uppercased,
        // padded with X if short) + FSR (Full Stocktaking Report) + date.
        $branchPrefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $branchName ?: 'BR'), 0, 3));
        $branchPrefix = str_pad($branchPrefix, 3, 'X');
        $docNumber    = $branchPrefix . 'FSR' . \Carbon\Carbon::parse($date ?? $displayDate)->format('Ymd');

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
                <p><strong>Full Stocktaking Report</strong></p>
                <p><strong>{{ ucwords(strtolower($branchName)) }}</strong></p>
                <p>Date: {{ $displayDate }}</p>
                <div class="status-chip {{ $isRectified ? 'status-rectified' : 'status-open' }}">
                    {{ $isRectified ? 'Rectified' : 'Not Yet Rectified' }}
                </div>
            </td>
        </tr>
    </table>

    <!-- SUMMARY: COUNT + VALUE BREAKDOWN -->
    <table class="address-table">
        <tr>
            <td>
                <h3>Count Breakdown</h3>
                <p>Products counted: <strong>{{ $totalCounted }}</strong> (100.00%)</p>
                <p>No anomalies: <strong>{{ $noAnomaly }}</strong> ({{ $zeroPct }}%)</p>
                <p>Overages: <strong class="pos-val">{{ $overageCount }}</strong> ({{ $posPct }}%) — MWK {{ number_format($overageVal, 2) }}</p>
                <p>Shortages: <strong class="neg-val">{{ $shortageCount }}</strong> ({{ $negPct }}%) — MWK {{ number_format($shortageVal, 2) }}</p>
            </td>
            <td>
                <h3>Value Breakdown</h3>
                <p>Expected value (EV): <strong>MWK {{ number_format($expectedTotal, 2) }}</strong></p>
                <p>Found value (FV): <strong>MWK {{ number_format($foundTotal, 2) }}</strong></p>
                <p>Difference (FV − EV): <strong class="{{ $difference >= 0 ? 'pos-val' : 'neg-val' }}">MWK {{ number_format($difference, 2) }}</strong></p>
                <p>Missing items: <strong>{{ $missingCount }}</strong> — Missing value (MV): <strong class="neg-val">MWK {{ number_format($missingTotal, 2) }}</strong></p>
                <p>Final Difference (FV − (EV + MV)): <strong style="color:#dc2626;">MWK {{ number_format($finalDifference, 2) }}</strong></p>
            </td>
        </tr>
    </table>

    <!-- COUNTED PRODUCTS -->
    <div class="section-title">Counted Products ({{ $totalCounted }})</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width:5%;">#</th>
                <th class="l" style="width:27%;">Product</th>
                <th style="width:10%;">Unit</th>
                <th style="width:13%;">Price</th>
                <th style="width:14%;">Expected</th>
                <th style="width:13%;">Found</th>
                <th style="width:18%;">Diff</th>
            </tr>
        </thead>
        <tbody>
            @php $num = 1; @endphp
            @forelse($countedRows->sortBy(fn($r) => $r->found - ($r->expected_final ?? $r->expected_at_count)) as $row)
                @php
                    $exp  = $row->expected_final ?? $row->expected_at_count;
                    $diff = $row->found - $exp;
                    $cls  = abs($diff) < 0.0001 ? 'diff-zero' : ($diff > 0 ? 'diff-pos' : 'diff-neg');
                @endphp
                <tr>
                    <td>{{ $num++ }}</td>
                    <td class="l">{{ $row->product_name }}</td>
                    <td>{{ $row->unit }}</td>
                    <td>{{ number_format($row->price, 2) }}</td>
                    <td>{{ number_format($exp, 2) }}</td>
                    <td>{{ number_format($row->found, 2) }}</td>
                    <td class="{{ $cls }}">{{ number_format($diff, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;padding:16px;color:#94a3b8;font-style:italic;">No products counted for this date.</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- MISSING PRODUCTS -->
    <div class="section-title">Missing Products ({{ $missingCount }})</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width:5%;">#</th>
                <th class="l" style="width:35%;">Product</th>
                <th style="width:12%;">Unit</th>
                <th style="width:16%;">Price</th>
                <th style="width:14%;">Quantity</th>
                <th style="width:18%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $mnum = 1; @endphp
            @forelse($missingRows->sortByDesc(fn($r) => $r->quantity * $r->price) as $m)
                <tr>
                    <td>{{ $mnum++ }}</td>
                    <td class="l">{{ $m->product_name }}</td>
                    <td>{{ $m->unit }}</td>
                    <td>{{ number_format($m->price, 2) }}</td>
                    <td>{{ number_format($m->quantity, 2) }}</td>
                    <td class="missing-val">{{ number_format($m->quantity * $m->price, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="text-align:center;padding:16px;color:#94a3b8;font-style:italic;">No missing products for this date.</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- NOTES -->
    <div class="notes">
        <h4>Important Notice</h4>
        <p>Figures shown reflect live stock at the time this report was generated. Any sales made after a product was counted have already been netted off automatically.</p>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        <p>{{ $footerParts->implode(' | ') }}</p>
        <p>{{ $branchName }} &middot; Full Stocktaking Report &middot; {{ $displayDate }} &middot; Generated {{ now()->format('d M Y, H:i') }}</p>
    </div>

</div>
</body>
</html>