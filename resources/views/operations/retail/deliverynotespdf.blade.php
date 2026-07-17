<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .invoice-details{width:45%;text-align:right;}
        .invoice-details strong{font-size:12pt;font-weight:700;color:#4B5EBD;}
        .invoice-details p{margin:1px 0;}

        /* ---------- CLIENT + BANK DETAILS ---------- */
        .address-table{width:100%;border-collapse:collapse;margin:10px 0 12px;font-size:8.8pt;}
        .address-table td{width:50%;padding:8px 10px;background:#f8f9fa;
            border:1px solid #e0e0e0;border-radius:5px;vertical-align:top;}
        .address-table h3{
            font-size:11pt;font-weight:700;color:#4B5EBD;
            margin-bottom:6px;text-transform:uppercase;letter-spacing:0.5px;
        }
        .address-table p{margin:1.5px 0;line-height:1.3;}
        .address-table strong{color:#2c3e50;}

        /* ---------- PRODUCTS TABLE (NO <thead> → NO REPEAT) ---------- */
        table.items{width:100%;border-collapse:collapse;margin:8px 0 0;font-size:8.8pt;}
        .items td{padding:6px 5px;border:1px solid #ddd;vertical-align:middle;
            font-size:8.5pt;text-align:center;line-height:1;}
        
        /* Fake Header Row (first row only) */
        .items tr.header-row td{
            background:#4B5EBD;color:#fff;font-weight:600;font-size:8.5pt;
            text-align:center;border:1px solid #999;
            page-break-inside:avoid; /* DOMPDF respects this */
        }
        .items tr.header-row td:first-child,
        .items tr.header-row td:nth-child(2){text-align:left;}

        /* Regular rows — light zebra striping. The original used
           `:nth-child(even of :not(.header-row))`, a CSS4 selector that
           DomPDF (and most browsers) don't support, so no stripe was
           ever showing. Since the header row and data rows sit in
           separate <tbody> elements, targeting the second <tbody>'s
           even rows directly works everywhere. */
        .items tbody:nth-of-type(2) tr:nth-child(even){background:#f9f9fb;}
        .items td:first-child, .items td:nth-child(2){text-align:left;}

        /* ---------- TOTALS (last 2 columns: Price + Total) ---------- */
        .totals-wrapper{margin-top:-1px;}
        .totals-table{
            width:100%;border-collapse:collapse;font-size:9pt;
            border:1px solid #ddd;border-top:2px solid #4B5EBD;
            border-radius:0 0 5px 5px;overflow:hidden;background:#f8f9fa;
            table-layout:fixed; /* so the two column widths below are exact,
                and the divider between them lines up with the real
                Price/Total column boundary above */
        }
        /* Price is 16% and Total is 20% of the items table (36% combined).
           Split this nested table the same way: 16/36 and 20/36. */
        .totals-table td:first-child{width:44.44%;}
        .totals-table td:last-child{width:55.56%;}
        .totals-table td{padding:6px 10px;background:#f8f9fa;border-bottom:1px solid #eee;}
        .totals-table td:first-child{font-weight:600;color:#2c3e50;}
        .totals-table td:last-child{text-align:right;font-weight:500;}
        .totals-table tr:nth-child(3) td{font-weight:700;color:#1a1a1a;}
        .totals-table tr:last-child td{
            background:#4B5EBD;color:#fff;font-weight:700;font-size:10.5pt;
        }

        /* ---------- NOTES ---------- */
        .notes{clear:both;margin-top:25px;padding-top:12px;
            border-top:1px dashed #ccc;font-size:8.5pt;}
        .notes h4{color:#4B5EBD;margin-bottom:4px;font-size:9.5pt;}

        /* ---------- FOOTER ---------- */
        .footer{margin-top:30px;text-align:center;font-size:7.5pt;
            color:#777;border-top:1px solid #eee;padding-top:10px;}

        @page{margin:0.6cm;size:A4;}
    </style>
</head>
<body>

<div class="container">
    @php
        // ── NEW SYSTEM DATA: controller-passed variables, no inline
        //    DB::table() queries — $branch, $preparedByUser,
        //    $deliveryNotes, $deliveryDate, $displayDate,
        //    $grandTotalValue, $generatedBy all come from the controller.
        //    Company info is a single-row settings table (id=1), queried
        //    directly here via the tenant connection.
        $companyProfile = DB::connection('tenant')->table('company_info')->first();

        $branchName    = $branch->name    ?? '';
        $branchAddress = $branch->address ?? '';
        $branchContact = $branch->phone   ?? '';

        // Delivery-note number: first 3 letters of the branch name
        // (uppercased, padded with X if the name is short) followed by
        // the delivery date — e.g. "EXP20260710".
        $branchPrefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $branchName ?: 'BR'), 0, 3));
        $branchPrefix = str_pad($branchPrefix, 3, 'X');
        $dnoteNumber  = $branchPrefix . \Carbon\Carbon::parse($deliveryDate)->format('Ymd');

        $data       = $deliveryNotes;
        $grandTotal = $grandTotalValue;

        $num = 1;
    @endphp

    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td class="company-details">
                <h1>{{ $companyProfile->business_name ?? '' }}</h1>
                <p>{{ $companyProfile->physical_address ?? '' }}</p>
                <p>Phone: {{ $companyProfile->primary_number ?? '' }}</p>
                <p>Email: {{ $companyProfile->email_address ?? '' }}</p>
            </td>
            <td class="invoice-details">
                <p><strong>DELIVERYNOTE</strong></p>
                <p><strong>{{ $dnoteNumber }}<span></span></strong></p>
                <p>Date: {{ $displayDate }}</p>
            </td>
        </tr>
    </table>

    <!-- CLIENT + BANK DETAILS -->
    <table class="address-table">
        <tr>
            <td>
                <h3>RECEPIENT BRANCH</h3>
                <p><strong>{{ $branchName }}</strong></p>
                <p>Contact : <strong>{{ $branchContact }}</strong></p>
                <p>Value : <u> <span>MWK</span><strong><span style="color:red;font-weight:bold">{{ number_format($grandTotal, 2) }}</span> </u> </strong></p>
            </td>
            <td>
                <h3>GENERATED BY</h3>
                <p>Account Name: <strong>{{ $generatedBy }}</strong></p>
                <p>Contact : <strong>{{ $preparedByUser->phone ?? '' }}</strong></p>
                <p>Email: <strong>{{ $preparedByUser->email ?? '' }}</strong></p>
            </td>
        </tr>
    </table>

    <!-- PRODUCTS TABLE (NO <thead> → NO REPEAT) -->
    <table class="items">
        <tbody>
            <!-- FAKE HEADER ROW (appears only once) -->
            <tr class="header-row">
                <td style="width:5%;">#</td>
                <td style="width:39%;">Product</td>
                <td style="width:10%;">Units</td>
                <td style="width:10%;">Qty</td>
                <td style="width:16%;">Price</td>
                <td style="width:20%;">Total</td>
            </tr>
            <tbody>
            @foreach($data as $d)
            <tr>
                <td>{{ $num++ }}</td>
                <td>{{ $d->product_name }}</td>
                <td>{{ $d->product_unit }}</td>
                <td>{{ $d->quantity }}</td>
                <td>{{ number_format($d->selling_price, 2) }}</td>
                <td>{{ number_format($d->selling_price * $d->quantity, 2) }}</td>
            </tr>
            @endforeach
            </tbody>
            <!-- TOTALS ROW -->
            <tr class="totals-wrapper">
                <td colspan="4"></td>
                <td colspan="2">
                    <table class="totals-table">
                        <tr><td><strong>Grand Total</strong></td><td><strong>{{ number_format($grandTotal, 2) }}</strong></td></tr>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- NOTES -->
    <div class="notes">
        <h4>Important Notice</h4>
        <p>Please cross-check the quantities stated on the delivery note against the actual physical quantities received, and immediately report any discrepancies.</p>
        <h4 style="margin-top:8px;">Thank You for Your Attention</h4>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        @php
            $footerParts = collect([
                $companyProfile->business_name    ?? null,
                $companyProfile->physical_address ?? null,
                $companyProfile->primary_number   ?? null,
                $companyProfile->email_address    ?? null,
            ])->filter(fn($v) => !empty($v));
        @endphp
        <p>{{ $footerParts->implode(' | ') }}</p>
        <p>This is a computer-generated deliverynote. No signature required.</p>
    </div>

</div>
</body>
</html>