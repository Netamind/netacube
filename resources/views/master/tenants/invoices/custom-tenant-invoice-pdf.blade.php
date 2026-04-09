<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{
            font-family:DejaVu Sans,sans-serif;      
            font-size:9.5pt;color:#333;line-height:1.4;
            background:#fff;padding:18px;
        }
        .container{width:100%;max-width:820px;margin:0 auto;background:#fff;}

        .header-table{
            padding-bottom:12px;
            margin-bottom:16px;
            border-bottom:3px solid #4B5EBD;
        }
        .header-table{width:100%;border-collapse:collapse;}
        .header-table td{vertical-align:top;padding:0;}

        .company-details h1,
        .invoice-title{
            font-size:17pt;font-weight:800;color:#4B5EBD;
            margin:0 0 4px 0;
            letter-spacing:0.5px;line-height:1;
        }
        .invoice-title{text-align:right;}

        .company-details p{
            margin:2px 0;font-size:9.2pt;color:#444;line-height:1.2;
        }

        .invoice-details{
            text-align:right;vertical-align:top;
        }
        .invoice-details p{
            margin:2px 0 2px auto;
            font-size:9.2pt;color:#444;
            line-height:1.2;
            padding-right:0;
        }
        .invoice-details p strong{font-weight:800;}

        .address-table{width:100%;margin:18px 0 20px;font-size:9pt;}
        .address-table td{width:50%;padding:12px 14px;background:#f8f9fc;
                          border:1px solid #e0e6f5;vertical-align:top;}
        .address-table h3{font-size:11.5pt;font-weight:800;color:#4B5EBD;
                          margin-bottom:8px;text-transform:uppercase;letter-spacing:0.8px;}

        table.items{width:100%;border-collapse:collapse;margin:22px 0 28px;
                   font-size:9.5pt;border:1px solid #c8d0e0;}
        .items th,.items td{border:1px solid #c8d0e0;padding:10px 8px;}
        .items th{background:#f8f9fc;font-weight:700;text-transform:uppercase;
                  letter-spacing:0.6px;color:#333;text-align:left;}
        .items th:first-child, .items td:first-child{width:8%;text-align:center;}
        .items th:nth-child(2), .items td:nth-child(2){width:62%;padding-left:14px;}
        .items th:last-child, .items td:last-child{width:30%;text-align:right;padding-right:14px;}
        .items tbody td{background:#fff;}
        .items tbody tr:nth-child(even) td{background:#fdfdff;}
        .items tbody td:last-child{font-weight:600;color:#4B5EBD;}
        .items tfoot td{background:#fff;padding:12px 8px;font-size:12.5pt;font-weight:800;}
        .items tfoot td:first-child + td{text-align:right;color:#2c3e50;padding-right:20px;}
        .items tfoot td:last-child{text-align:right;color:#4B5EBD;padding-right:14px;}

        .notes{margin-top:30px;padding-top:8px;font-size:9.3pt;}
        .notes h4{color:#4B5EBD;margin-bottom:8px;font-size:10.8pt;font-weight:700;}

        .footer{
            margin-top:50px;padding-top:20px;text-align:center;
            font-size:8.5pt;color:#777;border-top:1px solid #d0d7e5;line-height:1.6;
        }
    </style>
</head>
<body>
<div class="container">

    <div class="header">
        <table class="header-table" cellspacing="0">
            <tr>
                <td class="company-details">
                    <h1>Netamind Technology</h1>
                    <p>PO Box 20257 Mzuzu, Malawi</p>
                    <p>Phone: +265 992522601</p>
                    <p>Email: info@netamind.com</p>
                    <p>Website: www.netamind.com</p>
                </td>
                <td class="invoice-details">
                    <div class="invoice-title">INVOICE</div>
                    <p>#{{ $invoice->invoice_number }}</p>
                    <p>Date: {{ \Carbon\Carbon::parse($invoice->created_at)->format('d M Y') }}</p>
                    <p>Due: <strong>{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') : 'Upon Receipt' }}</strong></p>
                </td>
            </tr>
        </table>
    </div>

    <table class="address-table" cellspacing="0">
        <tr>
            <td>
                <h3>Bill To</h3>
                <p><strong>{{ $tenant->full_name ?? 'Tenant' }}</strong></p>
                @if(!empty($tenant->business_name))
                    <p><strong>{{ $tenant->business_name }}</strong></p>
                @endif
                @if(!empty($tenant->postal_address))
                    <p>{{ $tenant->postal_address }}</p>
                @endif
                @if(!empty($tenant->email))
                    <p>{{ $tenant->email }}</p>
                @endif
                @if(!empty($tenant->phone_number))
                    <p>{{ $tenant->phone_number }}</p>
                @endif
            </td>
            <td>
                <h3>Payment Details</h3>
                <?php
                    $payment = is_string($invoice->payment_method) 
                        ? json_decode($invoice->payment_method, true) 
                        : (is_array($invoice->payment_method) ? $invoice->payment_method : []);
                    $methodType = strtoupper($payment['method_type'] ?? '');
                ?>

                @if($methodType)
                    <p><strong>{{ ucwords(strtolower($methodType)) }} Payment</strong></p>

                    @if($methodType === 'BANK')
                        <p>Bank Name: <strong>{{ $payment['bank_name'] ?? '' }}</strong></p>
                        <p>Account Name: <strong>{{ $payment['account_name'] ?? '' }}</strong></p>
                        <p>Account Number: <strong>{{ $payment['account_number'] ?? '' }}</strong></p>
                        <p>Account Type: <strong>{{ $payment['account_type'] ?? '' }}</strong></p>
                        <p>Branch: <strong>{{ $payment['account_branch'] ?? '' }}</strong></p>

                    @elseif($methodType === 'MOBILE')
                        <p>Operator: <strong>{{ $payment['mobile_operator'] ?? '—' }}</strong></p>
                        <p>Mobile Number: <strong>{{ $payment['mobile_number'] ?? '—' }}</strong></p>
                        <p>Registered Name: <strong>{{ $payment['mobile_number_name'] ?? '—' }}</strong></p>

                    @elseif($methodType === 'PAYPAL')
                        <p>PayPal Name: <strong>{{ $payment['paypal_name'] ?? '—' }}</strong></p>
                        <p>PayPal Email: <strong>{{ $payment['paypal_email'] ?? '—' }}</strong></p>
                        <p>PayPal.Me Link: <strong>{{ $payment['paypal_me_link'] ?? '—' }}</strong></p>
                    @endif
                @else
                    <p><em>No payment method configured.</em></p>
                @endif
            </td>
        </tr>
    </table>

    <table class="items" cellspacing="0">
        <thead>
            <tr>
                <th>#</th>
                <th>Description</th>
                <th style="text-align:center">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align:center;">1</td>
                <td style="padding-left:14px;">
                    @php
                        $planData = [];
                        if (is_string($invoice->plan) && trim($invoice->plan) !== '') {
                            $decoded = json_decode($invoice->plan, true);
                            $planData = is_array($decoded) ? $decoded : [];
                        }

                        // Simple check: Is this a custom invoice?
                        $isCustom = isset($planData['plan_name']) && $planData['plan_name'] === 'Custom';
                    @endphp

                    @if($isCustom)
                        <!-- Show the exact description entered by the user -->
                        <strong>{{ $description ?? $invoice->description ?? 'Custom Invoice' }}</strong>
                    @else
                        <strong>Netacube Subscription</strong>
                        <br><small>
                            {{ $planData['plan_name'] ?? 'Subscription' }} — 
                            {{ $planData['plan_period_name'] ?? $planData['plan_period'] ?? '' }}
                        </small>
                    @endif
                </td>
                <td style="text-align:center;padding-right:14px;">
                    {{ number_format($invoice->amount ?? 0, 2) }} {{ $invoice->currency }}
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:right;padding-right:20px;">Grand Total</td>
                <td style="text-align:center;padding-right:14px;">
                    {{ number_format($invoice->amount ?? 0, 2) }} {{ $invoice->currency }}
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="notes">
        <h4>Payment Terms</h4>
        <p>Payment is due on or before <strong>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</strong>.</p>
        <p>Please make all payments using the details provided above.</p>
        <p>Thank you for your business!</p>
    </div>

    <div class="footer">
        <p><strong>Netamind Technology</strong> | PO Box 20257, Mzuzu, Malawi</p>
        <p>Phone: +265992522601 | Email: info@netamind.com | Web: www.netamind.com</p>
        <p>This is a computer-generated invoice • Generated on {{ \Carbon\Carbon::parse($invoice->created_at)->format('d M Y') }}</p>
    </div>
</div>
</body>
</html>