<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Profile – {{ $user->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #2d2d2d;
            background: #fff;
        }

        .header {
            background: #6b86d4;
            padding: 28px 32px 24px;
        }
        .header-rule {
            height: 3px;
            background: #f0b429;
        }

        .company-name {
            font-size: 26px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        .header-divider {
            height: 1px;
            background: rgba(255,255,255,0.3);
            margin-bottom: 10px;
        }
        .header-label {
            font-size: 9px;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #d6e0f7;
            margin-bottom: 3px;
        }
        .emp-name {
            font-size: 16px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1.15;
        }
        .emp-meta {
            font-size: 9.5px;
            color: #b8c8f0;
            margin-top: 4px;
        }
        .header-date {
            text-align: right;
            font-size: 9px;
            color: #c0cfee;
            vertical-align: bottom;
            width: 120px;
        }

        .body { padding: 22px 32px; }

        .section { margin-bottom: 18px; }
        .section-title {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #2d3a8c;
            border-bottom: 1.5px solid #2d3a8c;
            padding-bottom: 4px;
            margin-bottom: 9px;
        }

        .fields { width: 100%; border-collapse: collapse; }
        .fields tr td {
            padding: 5px 0;
            border-bottom: 1px solid #f2f2f2;
            vertical-align: top;
        }
        .fields tr:last-child td { border-bottom: none; }
        .fl {
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #999;
            width: 28%;
        }
        .fv { font-size: 11px; color: #1a1a1a; }

        .kin-card {
            background: #f8f9ff;
            border: 1px solid #dde2f5;
            border-radius: 5px;
            padding: 12px 14px;
        }

        .footer {
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 9px;
            width: 100%;
        }
        .footer td { font-size: 8.5px; color: #777; }
        .footer-brand { color: #2d3a8c; font-weight: 700; }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <table width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td style="vertical-align: top;">
                <div class="company-name">
                    
              <?php
              $companyName = DB::table('company_info')->where('id',1)->value('business_name'); 
              ?>
                      {{$companyName}}
                </div>
                <div class="header-divider"></div>
                <div class="header-label">Employee Profile</div>
                <div class="emp-name">{{ $user->name }}</div>
                <div class="emp-meta">
                    ID #{{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }}
                    @if($user->started_on)
                        &nbsp;·&nbsp; Started {{ \Carbon\Carbon::parse($user->started_on)->format('d M Y') }}
                    @endif
                </div>
            </td>
            <td class="header-date">
                Generated<br>{{ now()->format('d M Y') }}
            </td>
        </tr>
    </table>
</div>
<div class="header-rule"></div>

<div class="body">

    <!-- PERSONAL -->
    <div class="section">
        <div class="section-title">Personal</div>
        <table class="fields" cellspacing="0" cellpadding="0">
            <tr><td class="fl">Full name</td><td class="fv">{{ $user->name }}</td></tr>
            <tr>
                <td class="fl">Date of birth</td>
                <td class="fv">{{ $user->dob ? \Carbon\Carbon::parse($user->dob)->format('d M Y') : '—' }}</td>
            </tr>
            <tr><td class="fl">ID type</td><td class="fv">{{ $user->idtype ?: '—' }}</td></tr>
            <tr><td class="fl">ID number</td><td class="fv">{{ $user->idnumber ?: '—' }}</td></tr>
        </table>
    </div>

    <!-- CONTACT -->
    <div class="section">
        <div class="section-title">Contact</div>
        <table class="fields" cellspacing="0" cellpadding="0">
            <tr><td class="fl">Phone</td><td class="fv">{{ $user->phone ?: '—' }}</td></tr>
            <tr><td class="fl">Email</td><td class="fv">{{ $user->email ?: '—' }}</td></tr>
            <tr><td class="fl">Home address</td><td class="fv">{{ $user->home_address ?: '—' }}</td></tr>
            <tr><td class="fl">Current residence</td><td class="fv">{{ $user->current_residence ?: '—' }}</td></tr>
        </table>
    </div>

    <!-- EMPLOYMENT -->
    <div class="section">
        <div class="section-title">Employment</div>
        <table class="fields" cellspacing="0" cellpadding="0">
            <tr><td class="fl">Position</td><td class="fv">{{ $user->position ?: '—' }}</td></tr>
            <tr><td class="fl">Department</td><td class="fv">{{ $user->department ?: '—' }}</td></tr>
            <tr><td class="fl">Branch</td><td class="fv">{{ $user->branch ?: '—' }}</td></tr>
            <tr><td class="fl">Role / Access</td><td class="fv">{{ $user->role ?: '—' }}</td></tr>
            <tr>
                <td class="fl">Started on</td>
                <td class="fv">{{ $user->started_on ? \Carbon\Carbon::parse($user->started_on)->format('d M Y') : '—' }}</td>
            </tr>
            <tr>
                <td class="fl">Added on</td>
                <td class="fv">{{ $user->entered_on ? \Carbon\Carbon::parse($user->entered_on)->format('d M Y') : '—' }}</td>
            </tr>
            <tr>
                <td class="fl">Is active</td>
                <td class="fv">{{ (strtolower($user->active ?? 'yes') === 'yes') ? 'Yes' : 'No' }}</td>
            </tr>
        </table>
    </div>

    <!-- COMPENSATION -->
    <div class="section">
        <div class="section-title">Compensation</div>
        <table class="fields" cellspacing="0" cellpadding="0">
            <tr>
                <td class="fl">Gross salary</td>
                <td class="fv">{{ $user->gross_salary ? 'MWK ' . number_format($user->gross_salary) : '—' }}</td>
            </tr>
        </table>
    </div>

    <!-- NEXT OF KIN -->
    <div class="section">
        <div class="section-title">Next of kin</div>
        <div class="kin-card">
            <table class="fields" cellspacing="0" cellpadding="0">
                <tr><td class="fl">Name</td><td class="fv">{{ $user->nextofkin_name ?: '—' }}</td></tr>
                <tr><td class="fl">Relationship</td><td class="fv">{{ $user->nextofkin_relationship ?: '—' }}</td></tr>
                <tr><td class="fl">Contact</td><td class="fv">{{ $user->nextofkin_contact ?: '—' }}</td></tr>
                <tr><td class="fl">Address</td><td class="fv">{{ $user->nextofkin_physical_address ?: '—' }}</td></tr>
            </table>
        </div>
    </div>

    <!-- FOOTER -->
    <table class="footer" cellspacing="0" cellpadding="0">
        <tr>
            <td>Confidential &nbsp;·&nbsp; For authorised HR use only</td>
            <td style="text-align:right;">
                <span class="footer-brand">Netacube</span> &nbsp;·&nbsp; {{ now()->format('d M Y, H:i') }}
            </td>
        </tr>
    </table>

</div>
</body>
</html>