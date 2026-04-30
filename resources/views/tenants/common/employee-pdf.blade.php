<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Profile – {{ $user->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page {
            margin-top: 0;
            margin-bottom: 58px;
            margin-left: 0;
            margin-right: 0;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1a1a1a;
            background: #fff;
        }

        /* ── FIXED FOOTER ── */
        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 48px;
            background: #f4f6fa;
            border-top: 1px solid #d0d7e6;
            padding: 0 32px;
        }
        .page-footer table {
            width: 100%;
            height: 48px;
            border-collapse: collapse;
        }
        .page-footer td { vertical-align: middle; }
        .footer-brand {
            font-size: 10.5px;
            font-weight: 700;
            color: #2a5caa;
        }
        .footer-sub {
            font-size: 7.5px;
            color: #b0b8cc;
            margin-top: 2px;
            letter-spacing: 0.2px;
        }
        .footer-right {
            text-align: right;
            font-size: 8.5px;
            color: #a0a8bc;
            line-height: 1.8;
        }

        /* ── TOP ACCENT ── */
        .top-bar { height: 3px; background: #2a5caa; }

        /* ── HEADER ── */
        .header {
            padding: 26px 32px 22px;
            border-bottom: 1px solid #dde3ef;
        }
        .company-name {
            font-size: 20px;
            font-weight: 700;
            color: #2a5caa;
            margin-bottom: 14px;
            letter-spacing: 0.2px;
        }
        .header-label {
            font-size: 7px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #b0b8cc;
            margin-bottom: 5px;
        }
        .emp-name {
            font-size: 17px;
            font-weight: 700;
            color: #111;
            margin-bottom: 4px;
        }
        .emp-meta {
            font-size: 10px;
            color: #666;
            line-height: 1.7;
        }
        .header-date {
            text-align: right;
            font-size: 8.5px;
            color: #aaa;
            vertical-align: top;
            width: 115px;
            line-height: 2;
        }
        .header-date strong { color: #333; font-size: 10px; display: block; }

        /* ── BODY ── */
        .body { padding: 0 32px 24px; }

        /* ── SECTION BLOCK ── */
        .section {
            margin-top: 22px;
        }
        .section-head {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        .section-title {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2.5px;
            color: #2a5caa;
            background: #f0f5ff;
            padding: 5px 10px;
            display: inline-block;
        }
        .section-line {
            display: table;
            width: 100%;
            border-bottom: 1px solid #dde3ef;
            margin-bottom: 10px;
        }

        /* ── FIELDS ── */
        .fields { width: 100%; border-collapse: collapse; }
        .fields tr td {
            padding: 6px 0;
            border-bottom: 1px solid #f0f2f7;
            vertical-align: top;
        }
        .fields tr:last-child td { border-bottom: none; }
        .fl {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #a0a8bc;
            width: 36%;
            padding-right: 8px;
        }
        .fv {
            font-size: 12px;
            color: #1a1a1a;
            font-weight: 400;
        }

        /* ── TWO COLUMN ── */
        .two-col { width: 100%; border-collapse: collapse; }
        .two-col > tbody > tr > td { vertical-align: top; width: 50%; }
        .two-col > tbody > tr > td:first-child {
            padding-right: 20px;
            border-right: 1px solid #eef0f6;
        }
        .two-col > tbody > tr > td:last-child { padding-left: 20px; }

        /* ── SALARY ── */
        .salary-val {
            font-size: 14px;
            font-weight: 700;
            color: #2a5caa;
        }
        .salary-unit {
            font-size: 9px;
            color: #bbb;
        }

        /* ── STATUS ── */
        .status-active {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #1a6e3a;
        }
        .status-inactive {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #999;
        }
    </style>
</head>
<body>

<!-- FIXED FOOTER -->
<div class="page-footer">
    <table cellspacing="0" cellpadding="0">
        <tr>
            <td>
                <div class="footer-brand">Netacube &nbsp;&middot;&nbsp; HR &amp; Workforce Management</div>
                <div class="footer-sub">Confidential &nbsp;&middot;&nbsp; For authorised HR use only</div>
            </td>
            <td>
                <div class="footer-right">
                    {{ now()->format('d M Y, H:i') }}<br>
                    Employee ID #{{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }}
                </div>
            </td>
        </tr>
    </table>
</div>

<!-- TOP BAR -->
<div class="top-bar"></div>

<!-- HEADER -->
<div class="header">
    <table width="100%" cellspacing="0" cellpadding="0">
        <tr>

        <?php
        $companyName = DB::connection('tenant')->table('company_info')->where('id',1)->value('business_name');
        ?>
            <td style="vertical-align: top;">
                <div class="company-name">{{ $companyName  }}</div>
                <div class="header-label">Employee Profile</div>
                <div class="emp-name">{{ $user->name }}</div>
                <div class="emp-meta">
                    ID #{{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }}
                    @if($user->position) &nbsp;&middot;&nbsp; {{ $user->position }} @endif
                    @if($user->department) &nbsp;&middot;&nbsp; {{ $user->department }} @endif
                    @if($user->branch) &nbsp;&middot;&nbsp; {{ $user->branch }} @endif
                </div>
            </td>
            <td class="header-date">
                Generated on
                <strong>{{ now()->format('d M Y') }}</strong>
                {{ now()->format('H:i') }}
            </td>
        </tr>
    </table>
</div>

<!-- BODY -->
<div class="body">

    <!-- PERSONAL & CONTACT -->
    <div class="section">
        <table class="two-col" cellspacing="0" cellpadding="0">
            <tr>
                <td>
                    <div class="section-title">Personal Information</div>
                    <div class="section-line"></div>
                    <table class="fields" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="fl">Full Name</td>
                            <td class="fv">{{ $user->name ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fl">Date of Birth</td>
                            <td class="fv">{{ $user->dob ? \Carbon\Carbon::parse($user->dob)->format('d M Y') : '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fl">ID Type</td>
                            <td class="fv">{{ $user->idtype ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fl">ID Number</td>
                            <td class="fv">{{ $user->idnumber ?: '—' }}</td>
                        </tr>
                    </table>
                </td>
                <td>
                    <div class="section-title">Contact Details</div>
                    <div class="section-line"></div>
                    <table class="fields" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="fl">Phone</td>
                            <td class="fv">{{ $user->phone ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fl">Email</td>
                            <td class="fv">{{ $user->email ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fl">Home Address</td>
                            <td class="fv">{{ $user->home_address ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fl">Residence</td>
                            <td class="fv">{{ $user->current_residence ?: '—' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- EMPLOYMENT & DATES/COMPENSATION -->
    <div class="section">
        <table class="two-col" cellspacing="0" cellpadding="0">
            <tr>
                <td>
                    <div class="section-title">Employment</div>
                    <div class="section-line"></div>
                    <table class="fields" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="fl">Position</td>
                            <td class="fv">{{ $user->position ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fl">Department</td>
                            <td class="fv">{{ $user->department ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fl">Branch</td>
                            <td class="fv">{{ $user->branch ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fl">Role</td>
                            <td class="fv">{{ $user->role ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fl">Status</td>
                            <td class="fv">
                                @if(strtolower($user->active ?? 'yes') === 'yes')
                                    <span class="status-active">Active</span>
                                @else
                                    <span class="status-inactive">Inactive</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
                <td>
                    <div class="section-title">Dates &amp; Compensation</div>
                    <div class="section-line"></div>
                    <table class="fields" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="fl">Started On</td>
                            <td class="fv">{{ $user->started_on ? \Carbon\Carbon::parse($user->started_on)->format('d M Y') : '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fl">Added On</td>
                            <td class="fv">{{ $user->entered_on ? \Carbon\Carbon::parse($user->entered_on)->format('d M Y') : '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fl">Gross Salary</td>
                            <td class="fv">
                                @if($user->gross_salary)
                                    <span class="salary-val">MWK {{ number_format($user->gross_salary) }}</span>
                                    <span class="salary-unit"> / month</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- NEXT OF KIN -->
    <div class="section">
        <table class="two-col" cellspacing="0" cellpadding="0">
            <tr>
                <td>
                    <div class="section-title">Next of Kin</div>
                    <div class="section-line"></div>
                    <table class="fields" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="fl">Full Name</td>
                            <td class="fv">{{ $user->nextofkin_name ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fl">Relationship</td>
                            <td class="fv">{{ $user->nextofkin_relationship ?: '—' }}</td>
                        </tr>
                    </table>
                </td>
                <td>
                    <div class="section-title">Kin Contact</div>
                    <div class="section-line"></div>
                    <table class="fields" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="fl">Phone</td>
                            <td class="fv">{{ $user->nextofkin_contact ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td class="fl">Address</td>
                            <td class="fv">{{ $user->nextofkin_physical_address ?: '—' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

</div>

</body>
</html>