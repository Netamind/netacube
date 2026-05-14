<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $letter->letter_type }} Letter</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #1a1a2e;
            background: #fff;
        }

        .page {
            margin: 0;
            padding: 28px 46px 70px 46px;
        }

        /* ══════════════════════════════════════════
           LETTERHEAD
        ══════════════════════════════════════════ */

        /* Coloured band behind the company header */
        .header-band {
            background: #4B5EBD;
            margin: -28px -46px 0 -46px;
            padding: 16px 46px 14px 46px;
        }
        .header-band-inner {
            width: 100%;
        }
        .company-name-white {
            font-size: 19px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.4px;
        }
        .company-meta-white {
            font-size: 8px;
            color: rgba(255,255,255,0.80);
            margin-top: 3px;
            line-height: 1.6;
        }

        /* Letter-type badge on the right side of the band */
        .header-badge-cell {
            text-align: right;
            vertical-align: middle;
        }
        .letter-type-pill {
            display: inline-block;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            border: 2px solid rgba(255,255,255,0.7);
            color: #fff;
        }

        /* Thin accent under the band */
        .header-accent {
            height: 3px;
            background: #f0a500;
            margin: 0 -46px;
            margin-bottom: 14px;
        }

        /* ══════════════════════════════════════════
           REF / META BAR
        ══════════════════════════════════════════ */
        .ref-bar {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 8.5px;
        }
        .ref-bar td { padding: 5px 10px; }
        .ref-bar td:first-child {
            background: #f0f2fb;
            border-left: 3px solid #4B5EBD;
            color: #334155;
            width: 70%;
        }
        .ref-bar td:first-child strong { color: #1a1a2e; }
        .ref-bar td:last-child {
            background: #f8fafc;
            color: #64748b;
            text-align: right;
        }
        .ref-bar td:last-child strong { color: #1a1a2e; }

        /* ══════════════════════════════════════════
           RECIPIENT BLOCK
        ══════════════════════════════════════════ */
        .recipient {
            margin-bottom: 12px;
            font-size: 9.5px;
            line-height: 1.65;
        }
        .recipient .to-label {
            font-size: 7.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: #94a3b8;
            margin-bottom: 3px;
        }
        .recipient .emp-name {
            font-size: 13px;
            font-weight: 700;
            color: #1a1a2e;
        }
        .recipient .emp-sub {
            font-size: 9px;
            color: #475569;
        }

        /* ══════════════════════════════════════════
           SUBJECT LINE
        ══════════════════════════════════════════ */
        .subject-wrap {
            background: #f0f2fb;
            border-left: 4px solid #4B5EBD;
            padding: 7px 12px;
            margin-bottom: 12px;
        }
        .subject-label {
            font-size: 7.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            margin-bottom: 2px;
        }
        .subject-text {
            font-size: 11.5px;
            font-weight: 700;
            color: #4B5EBD;
        }

        /* ══════════════════════════════════════════
           BODY TEXT
        ══════════════════════════════════════════ */
        .salutation {
            font-size: 10px;
            font-weight: 600;
            margin-bottom: 7px;
            color: #1a1a2e;
        }
        .body-text {
            font-size: 9.5px;
            line-height: 1.8;
            color: #334155;
            margin-bottom: 10px;
            text-align: justify;
        }

        /* ══════════════════════════════════════════
           SECTION DIVIDERS
        ══════════════════════════════════════════ */
        .section-divider {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0 8px 0;
        }
        .section-divider td {
            vertical-align: middle;
            white-space: nowrap;
        }
        .section-divider .sd-label {
            font-size: 7.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            color: #4B5EBD;
            padding-right: 8px;
        }
        .section-divider .sd-line {
            width: 100%;
            border-bottom: 1px solid #c8d0ed;
        }

        /* ══════════════════════════════════════════
           DETAILS TABLE — two-column key/value grid
        ══════════════════════════════════════════ */
        .details-outer {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            margin-bottom: 10px;
            font-size: 9px;
        }
        .details-outer tr td {
            padding: 5px 10px;
            vertical-align: middle;
        }
        .details-outer tr:nth-child(odd)  td { background: #f8fafc; }
        .details-outer tr:nth-child(even) td { background: #ffffff; }
        .details-outer tr td:first-child {
            width: 34%;
            color: #64748b;
            font-weight: 600;
            border-right: 1px solid #e2e8f0;
        }
        .details-outer tr td:last-child {
            color: #1a1a2e;
            font-weight: 700;
        }
        /* Salary highlight row */
        .salary-row td { background: #eef0fb !important; }
        .salary-row td:first-child { color: #4B5EBD !important; }
        .salary-row td:last-child  { color: #4B5EBD !important; font-size: 10.5px; }
        /* Top border accent on first row */
        .details-outer tr:first-child td { border-top: 2px solid #4B5EBD; }

        /* ══════════════════════════════════════════
           CUSTOM MESSAGE BOX
        ══════════════════════════════════════════ */
        .custom-message-box {
            background: #f0f2fb;
            border: 1px solid #c8d0ed;
            border-left: 4px solid #4B5EBD;
            padding: 8px 12px;
            font-size: 9.5px;
            color: #1a1a2e;
            margin: 10px 0;
            line-height: 1.75;
        }
        .custom-message-label {
            font-size: 7.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #4B5EBD;
            margin-bottom: 3px;
        }

        /* ══════════════════════════════════════════
           CLOSING
        ══════════════════════════════════════════ */
        .closing-text {
            font-size: 9.5px;
            line-height: 1.8;
            color: #334155;
            margin-bottom: 10px;
            text-align: justify;
        }

        .closing-salutation {
            font-size: 9.5px;
            color: #334155;
            margin-bottom: 20px;
        }

        /* ══════════════════════════════════════════
           SIGNATURE BLOCK
        ══════════════════════════════════════════ */
        .sig-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .sig-table td { vertical-align: bottom; padding: 0; }
        .sig-table td.sig-left  { width: 50%; padding-right: 20px; }
        .sig-table td.sig-right { width: 50%; }

        .sig-line-wrap { border-bottom: 1.5px solid #1a1a2e; padding-bottom: 2px; height: 26px; }
        .sig-name  { font-size: 9.5px; font-weight: 700; color: #1a1a2e; margin-top: 3px; }
        .sig-title { font-size: 8.5px; color: #64748b; margin-top: 1px; }
        .sig-stamp {
            display: inline-block;
            width: 50px; height: 50px;
            text-align: center;
            line-height: 50px;
            font-size: 7px;
            font-weight: 700;
            color: #4B5EBD;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            opacity: 0.35;
        }

        /* ══════════════════════════════════════════
           ACCEPTANCE BLOCK
        ══════════════════════════════════════════ */
        .acceptance-outer {
            margin-top: 22px;
            border: 1px dashed #94a3b8;
            padding: 16px 18px;
            background: #fafafa;
        }
        .acceptance-title {
            font-size: 8.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            margin-bottom: 14px;
        }
        .accept-table { width: 100%; border-collapse: collapse; }
        .accept-table td { padding: 0; vertical-align: bottom; }
        .accept-table td.ac-col { width: 30%; padding-right: 20px; }
        .accept-table td.ac-wide { width: 38%; padding-right: 20px; }
        .accept-line { border-bottom: 1px solid #94a3b8; height: 30px; display: block; width: 100%; }
        .accept-label { font-size: 8px; color: #94a3b8; margin-top: 3px; }

        /* ══════════════════════════════════════════
           NOTES BOX
        ══════════════════════════════════════════ */
        .notes-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-left: 3px solid #f59e0b;
            padding: 6px 10px;
            font-size: 9px;
            color: #78350f;
            margin: 10px 0;
            line-height: 1.65;
        }
        .notes-title {
            font-weight: 700;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 2px;
            color: #92400e;
        }

        /* ══════════════════════════════════════════
           CONFIDENTIAL WATERMARK STRIP
        ══════════════════════════════════════════ */
        .confidential-strip {
            text-align: right;
            font-size: 7px;
            font-weight: 700;
            color: #dc2626;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        /* ══════════════════════════════════════════
           FOOTER
        ══════════════════════════════════════════ */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: #4B5EBD;
            padding: 6px 46px;
            font-size: 7px;
            color: rgba(255,255,255,0.80);
            text-align: center;
        }
        .footer strong { color: #fff; }
    </style>
</head>
<body>
<div class="page">

    {{-- ── HEADER BAND ── --}}
    <div class="header-band">
        <table class="header-band-inner" style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="vertical-align:middle;">
                    <div class="company-name-white">{{ $company->business_name ?? 'Company Name' }}</div>
                    <div class="company-meta-white">
                        @php
                            $meta = [];
                            if (!empty($company->physical_address)) $meta[] = $company->physical_address;
                            if (!empty($company->primary_number))   $meta[] = 'Tel: ' . $company->primary_number;
                            if (!empty($company->email_address))    $meta[] = $company->email_address;
                            echo implode(' &nbsp;|&nbsp; ', $meta);
                        @endphp
                    </div>
                </td>
                <td class="header-badge-cell" style="text-align:right; vertical-align:middle; white-space:nowrap;">
                    <span class="letter-type-pill">{{ $letter->letter_type }} Letter</span>
                </td>
            </tr>
        </table>
    </div>

    {{-- ── GOLD ACCENT LINE ── --}}
    <div class="header-accent"></div>

    {{-- ── CONFIDENTIAL STRIP ── --}}
    <div class="confidential-strip">Strictly Confidential</div>

    {{-- ── REF BAR ── --}}
    <table class="ref-bar">
        <tr>
            <td>
                <strong>Date:</strong> {{ \Carbon\Carbon::parse($letter->issue_date)->format('d F Y') }}
                &nbsp;&nbsp;&nbsp;
                <strong>Ref No.:</strong>&nbsp;
                {{ strtoupper(substr($letter->letter_type, 0, 3)) }}-{{ str_pad($letter->id, 5, '0', STR_PAD_LEFT) }}/{{ \Carbon\Carbon::parse($letter->issue_date)->format('Y') }}
            </td>
            <td>
                @if($letter->generated_by)
                    <strong>Prepared by:</strong> {{ $letter->generated_by }}
                @endif
            </td>
        </tr>
    </table>

    {{-- ── RECIPIENT ── --}}
    <div class="recipient">
        <div class="to-label">Addressed to</div>
        <div class="emp-name">{{ $letter->employee_name }}</div>
        @php $subParts = array_filter([$letter->current_position ?? null, $letter->department ?? null]); @endphp
        @if(count($subParts))
            <div class="emp-sub">{{ implode(' — ', $subParts) }}</div>
        @endif
        <div class="emp-sub">{{ $company->business_name ?? '' }}</div>
    </div>

    {{-- ── SUBJECT ── --}}
    @php
        $subjects = [
            'Offer'        => 'Offer of Employment',
            'Confirmation' => 'Confirmation of Employment',
            'Promotion'    => 'Promotion / Change of Role',
            'Termination'  => 'Termination of Employment',
        ];
        $subjectText = $subjects[$letter->letter_type] ?? ($letter->letter_type . ' Letter');
    @endphp
    <div class="subject-wrap">
        <div class="subject-label">Subject</div>
        <div class="subject-text">{{ $subjectText }}</div>
    </div>

    {{-- ── OPENING PARAGRAPH ── --}}
    @php
        $companyName    = $company->business_name ?? 'the Company';
        $offeredPos     = $letter->offered_position   ?? 'the position discussed';
        $offeredDept    = $letter->offered_department ?? '';
        $deptClause     = $offeredDept ? ', within the ' . $offeredDept . ' department'  : '';
        $deptClause2    = $offeredDept ? ', in the '     . $offeredDept . ' department'  : '';
        $startFormatted = $letter->start_date
                            ? \Carbon\Carbon::parse($letter->start_date)->format('d F Y')
                            : 'the date indicated below';
    @endphp

    <div class="salutation">Dear <strong>{{ $letter->employee_name }}</strong>,</div>

    <div class="body-text">
        @if($letter->letter_type === 'Offer')
            We are pleased to extend this formal offer of employment with
            <strong>{{ $companyName }}</strong> in the position of
            <strong>{{ $offeredPos }}</strong>{{ $deptClause }}.
            This offer is contingent upon the terms and conditions detailed herein and
            supersedes any prior verbal or informal arrangements.

        @elseif($letter->letter_type === 'Confirmation')
            Following the successful completion of your probationary period, we are pleased
            to confirm your continued employment with <strong>{{ $companyName }}</strong>
            on a permanent basis, effective <strong>{{ $startFormatted }}</strong>.
            This confirmation reflects your consistent performance and commitment to the
            organisation during the assessment period.

        @elseif($letter->letter_type === 'Promotion')
            It is with great pleasure that <strong>{{ $companyName }}</strong> formally
            notifies you of your promotion to the position of
            <strong>{{ $offeredPos }}</strong>{{ $deptClause2 }},
            effective <strong>{{ $startFormatted }}</strong>.
            This decision recognises your outstanding performance, dedication, and the
            significant contribution you have made to the organisation.

        @elseif($letter->letter_type === 'Termination')
            This letter serves as formal notice that your employment with
            <strong>{{ $companyName }}</strong> will be terminated effective
            <strong>{{ $startFormatted }}</strong>, in accordance with the terms of
            your employment contract and applicable labour laws.
            Please treat this communication as strictly confidential.
        @endif
    </div>

    {{-- ── DETAILS TABLE ── --}}
    @php
        $detailsTitle = $letter->letter_type === 'Termination' ? 'Termination Details' : 'Employment Details';
        $posLabel     = $letter->letter_type === 'Promotion'   ? 'New Position'        : 'Position';
        $salaryLabel  = $letter->letter_type === 'Promotion'   ? 'New Gross Salary'    : 'Gross Salary';
        $dateLabel    = 'Commencement Date';
        if ($letter->letter_type === 'Termination')  $dateLabel = 'Effective Date';
        if ($letter->letter_type === 'Confirmation') $dateLabel = 'Confirmation Date';

        $hasDetails = !empty($letter->offered_position)
                   || !empty($letter->offered_department)
                   || !empty($letter->offered_salary)
                   || !empty($letter->start_date);
    @endphp

    @if($hasDetails)
        <table class="section-divider">
            <tr>
                <td class="sd-label">{{ $detailsTitle }}</td>
                <td class="sd-line"></td>
            </tr>
        </table>

        <table class="details-outer">
            @if(!empty($letter->offered_position))
                <tr>
                    <td>{{ $posLabel }}</td>
                    <td>{{ $letter->offered_position }}</td>
                </tr>
            @endif
            @if(!empty($letter->offered_department))
                <tr>
                    <td>Department</td>
                    <td>{{ $letter->offered_department }}</td>
                </tr>
            @endif
            <tr class="salary-row">
                <td>{{ $salaryLabel }}</td>
                <td>{{ number_format($letter->offered_salary, 2) }} per month</td>
            </tr>
            @if(!empty($letter->start_date))
                <tr>
                    <td>{{ $dateLabel }}</td>
                    <td>{{ \Carbon\Carbon::parse($letter->start_date)->format('d F Y') }}</td>
                </tr>
            @endif
            <tr>
                <td>Issue Date</td>
                <td>{{ \Carbon\Carbon::parse($letter->issue_date)->format('d F Y') }}</td>
            </tr>
        </table>
    @endif

    {{-- ── CUSTOM MESSAGE ── --}}
    @if(!empty($letter->custom_message))
        <div class="custom-message-box">
            <div class="custom-message-label">Additional Message</div>
            {{ $letter->custom_message }}
        </div>
    @endif

    {{-- ── CLOSING PARAGRAPH ── --}}
    <div class="closing-text">
        @if($letter->letter_type === 'Offer')
            Kindly indicate your acceptance of this offer by signing and returning a copy of
            this letter no later than <strong>five (5) working days</strong> from the date
            above. Failure to respond within this period may result in the offer being
            withdrawn. Should you require any clarification, please contact the Human
            Resources department at your earliest convenience.

        @elseif($letter->letter_type === 'Confirmation')
            Your existing terms and conditions of employment remain unchanged unless otherwise
            stated herein. We look forward to your continued growth and valued contribution
            to the organisation.

        @elseif($letter->letter_type === 'Promotion')
            Please acknowledge your acceptance of this promotion by signing and returning a
            copy of this letter. Your revised terms and conditions take effect on the date
            stated above, unless an alternative arrangement has been agreed in writing.

        @elseif($letter->letter_type === 'Termination')
            You are required to return all company property — including access devices,
            equipment, and documentation — and complete a formal handover by the effective
            date. Your final remuneration and any accrued benefits will be processed in
            accordance with company policy and applicable legislation.
        @endif
    </div>

    {{-- ── NOTES (internal — printed here clearly labelled) ── --}}
    @if(!empty($letter->notes))
        <div class="notes-box">
            <div class="notes-title">Notes</div>
            {{ $letter->notes }}
        </div>
    @endif

    {{-- ── SIGNATURE ── --}}
    <div class="closing-salutation">Yours sincerely,</div>

    <table class="sig-table">
        <tr>
            <td class="sig-left">
                <div class="sig-line-wrap"></div>
                <div class="sig-name">{{ $letter->generated_by ?? 'Human Resources Manager' }}</div>
                <div class="sig-title">Human Resources &nbsp;|&nbsp; {{ $company->business_name ?? '' }}</div>
            </td>
            <td class="sig-right" style="text-align:right; vertical-align:bottom;">
                <div class="sig-stamp">Official<br>Seal</div>
            </td>
        </tr>
    </table>

    {{-- ── ACCEPTANCE BLOCK (Offer & Promotion only) ── --}}
    @if(in_array($letter->letter_type, ['Offer', 'Promotion']))
        <div class="acceptance-outer">
            <div class="acceptance-title">
                &mdash; Employee Acknowledgement &amp; Acceptance &mdash;
                Please sign and return a copy of this letter
            </div>
            <table class="accept-table">
                <tr>
                    <td class="ac-wide">
                        <span class="accept-line">&nbsp;</span>
                        <div class="accept-label">Employee Full Name (Print)</div>
                    </td>
                    <td class="ac-col">
                        <span class="accept-line">&nbsp;</span>
                        <div class="accept-label">Signature</div>
                    </td>
                    <td class="ac-col">
                        <span class="accept-line">&nbsp;</span>
                        <div class="accept-label">Date</div>
                    </td>
                </tr>
            </table>
        </div>
    @endif

</div>

{{-- ── FOOTER ── --}}
<div class="footer">
    @php
        $footerParts = [];
        if (!empty($company->business_name))    $footerParts[] = '<strong>' . $company->business_name . '</strong>';
        if (!empty($company->physical_address)) $footerParts[] = $company->physical_address;
        if (!empty($company->primary_number))   $footerParts[] = $company->primary_number;
        if (!empty($company->email_address))    $footerParts[] = $company->email_address;
        echo implode(' &nbsp;&bull;&nbsp; ', $footerParts);
    @endphp
    &nbsp;&nbsp;&mdash;&nbsp;&nbsp; This document is strictly confidential and intended solely for the named recipient.
</div>

</body>
</html>