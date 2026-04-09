<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice_number }} – Netamind Technology</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 30px 10px;
            color: #333;
        }
        .container {
            max-width: 640px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #4B5EBD, #576CC0);
            color: #fff;
            padding: 45px 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 36px;
            font-weight: 800;
            margin: 0;
            letter-spacing: 1px;
        }
        .content {
            padding: 40px 45px;
            line-height: 1.8;
            font-size: 16px;
            color: #444;
        }
        .content p {margin: 16px 0;}
        .highlight {color: #4B5EBD;font-weight:600;}
        .invoice-box {
            background: #f8f9fc;
            border-left: 5px solid #4B5EBD;
            padding: 20px 25px;
            border-radius: 8px;
            margin: 25px 0;
            font-size: 15.5px;
        }
        .signature {
            margin-top: 35px;
            font-size: 16px;
            color: #333;
        }
        .signature strong {
            color: #4B5EBD;
            font-size: 18px;
        }
        .footer {
            background: #f8f9fa;
            color: #666;
            padding: 30px 40px;
            text-align: center;
            font-size: 13px;
            line-height: 1.6;
            border-top: 1px solid #eee;
        }
        .footer a {
            color: #4B5EBD;
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="content">
        <p>Dear <strong>{{ $full_name }}</strong>,</p>

        <p>Please find attached invoice.</p>

        <div class="invoice-box">
            <strong>Invoice Details</strong><br>
            Invoice #: {{ $invoice_number }}<br>
            Issue Date: {{ $current_date }}<br>
            Due Date: {{ $due_date }}<br>
            <strong>Amount Due: <span class="highlight">{{ number_format($amount, 2) }} {{ $currency }}</span></strong><br>
            Description: {{ $description }}
        </div>

        <p>We kindly request payment at your earliest convenience using the payment details on the attached invoice.</p>

        <p>Should you have any questions, feel free to contact us.</p>

        <div class="signature">
            <p>Warm regards,<br>
            Netamind Technology<br>
            <a href="mailto:info@netamind.com">info@netamind.com</a><br>
            <a href="tel:+265992522601">+265992522601</a><br>
            <a href="https://www.netamind.com">www.netamind.com</a>
            </p>
        </div>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} <strong>Netamind Technology</strong>. All rights reserved.</p>
        <p>PO Box 20257, Mzuzu, Malawi</p>
        <p>This is an automated message from our billing system.</p>
    </div>
</div>
</body>
</html>