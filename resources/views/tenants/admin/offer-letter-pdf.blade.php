<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $letter->letter_type }} Letter — {{ $letter->employee_name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            background: #fff;
            padding: 0;
        }

        /* ── Page wrapper ───────────────────────────────────────────── */
        .page {
            width: 100%;
            min-height: 100%;
            padding: 40px 50px;
        }

        /* ── Header ─────────────────────────────────────────────────── */
        .header {
            width: 100%;
            border-bottom: 3px solid #4B5EBD;
            padding-bottom: 14px;
            margin-bottom: 20px;
        }

        .header-inner {
            width: 100%;
        }

        .company-name {
            font-size: 20px;
            font-weight: 700;
            color: #4B5EBD;
            letter-spacing: 0.5px;
        }

        .company-meta {
            font-size: 9.5px;
            color: #64748b;
            margin-top: 3px;
            line-height: 1.6;
        }

        .letter-type-badge {
            display: inline-block;
            background: #4B5EBD;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 3px;
            margin-top: 6px;
        }

        /* ── Reference / date bar ───────────────────────────────────── */
        .ref-bar {
            background: #f1f5f9;
            border-left: 4px solid #4B5EBD;
            padding: 8px 14px;
            margin-bottom: 22px;
            font-size: 10px;
            color: #475569;
        }

        .ref-bar strong {
            color: #1e293b;
        }

        /* ── Recipient block ────────────────────────────────────────── */
        .recipient {
            margin-bottom: 20px;
            font-size: 11px;
            line-height: 1.8;
        }

        .recipient .label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #94a3b8;
            margin-bottom: 4px;
        }

        .recipient .name {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
        }

        /* ── Subject line ───────────────────────────────────────────── */
        .subject-line {
            font-size: 12px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 18px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
        }

        .subject-line span {
            color: #4B5EBD;
        }

        /* ── Body text ──────────────────────────────────────────────── */
        .body-text {
            font-size: 11px;
            line-height: 1.85;
            color: #334155;
            margin-bottom: 16px;
            text-align: justify;
        }

        /* ── Details table ──────────────────────────────────────────── */
        .details-section {
            margin: 20px 0;
        }

        .details-section-title {
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #4B5EBD;
            border-bottom: 1px solid #c8d0ed;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table tr td {
            padding: 6px 10px;
            font-size: 10.5px;
            vertical-align: top;
        }

        .details-table tr:nth-child(odd) td {
            background: #f8fafc;
        }

        .details-table tr:nth-child(even) td {
            background: #ffffff;
        }

        .details-table td.field-label {
            width: 38%;
            color: #64748b;
            font-weight: 600;
        }

        .details-table td.field-value {
            color: #1e293b;
            font-weight: 700;
        }

        /* ── Notes box ──────────────────────────────────────────────── */
        .notes-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 4px;
            padding: 10px 14px;
            font-size: 10.5px;
            color: #78350f;
            margin: 18px 0;
            line-height: 1.7;
        }

        .notes-box .notes-title {
            font-weight: 700;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 4px;
            color: #92400e;
        }

        /* ── Closing / signature ────────────────────────────────────── */
        .closing {
            margin-top: 28px;
            font-size: 11px;
            line-height: 1.85;
            color: #334155;
        }

        .signature-block {
            margin-top: 40px;
        }

        .signature-line {
            border-top: 1px solid #1e293b;
            width: 200px;
            margin-bottom: 4px;
        }

        .signature-name {
            font-size: 11px;
            font-weight: 700;
            color: #1e293b;
        }

        .signature-title {
            font-size: 10px;
            color: #64748b;
        }

        /* ── Acceptance block (Offer / Promotion only) ──────────────── */
        .acceptance-block {
            margin-top: 36px;
            border-top: 2px dashed #cbd5e1;
            padding-top: 18px;
        }

        .acceptance-title {
            font-size: 9.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.07em;
            color: #64748b;
            margin-bottom: 14px;
        }

        .acceptance-table {
            width: 100%;
            border-collapse: collapse;
        }

        .acceptance-table td {
            padding: 4px 0;
            font-size: 10.5px;
            vertical-align: bottom;
        }

        .acceptance-table .sign-line {
            border-bottom: 1px solid #94a3b8;
            height: 28px;
            width: 85%;
            display: inline-block;
        }

        .acceptance-table .sign-label {
            font-size: 9px;
            color: #94a3b8;
            margin-top: 3px;
        }

        /* ── Footer ─────────────────────────────────────────────────── */
        .footer {
            position: fixed;
            bottom: 20px;
            left: 50px;
            right: 50px;
            border-top: 1px solid #e2e8f0;
            padding-top: 7px;
            font-size: 8.5px;
            color: #94a3b8;
            text-align: center;
        }

        /* ── Confidential stamp ─────────────────────────────────────── */
        .confidential {
            text-align: right;
            font-size: 8.5px;
            font-weight: 700;
            color: #dc2626;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
    </style>
</head>
<body>
<div class="page">

    {{-- ── CONFIDENTIAL stamp ──────────────────────────────────────────── --}}
    <div class="confidential">Confidential</div>

    {{-- ── HEADER ──────────────────────────────────────────────────────── --}}
    <div class="header">
        <div class="header-inner">
            <div class="company-name">
                {{ $company->business_name ?? 'Company Name' }}
            </div>
            <div class="company-meta">
                @if(!empty($company->physical_address)){{ $company->physical_address }}@endif
                @if(!empty($company->primary_number)) &nbsp;|&nbsp; Tel: {{ $company->primary_number }}@endif
                @if(!empty($company->email_address)) &nbsp;|&nbsp; {{ $company->email_address }}@endif
            </div>
            <div>
                <span class="letter-type-badge">{{ $letter->letter_type }} Letter</span>
            </div>
        </div>
    </div>

    {{-- ── REFERENCE / DATE BAR ────────────────────────────────────────── --}}
    <div class="ref-bar">
        <strong>Date:</strong>
        {{ \Carbon\Carbon::parse($letter->issue_date)->format('d F Y') }}
        &nbsp;&nbsp;&nbsp;
        <strong>Ref:</strong>
        {{ strtoupper(substr($letter->letter_type, 0, 3)) }}-{{ str_pad($letter->id, 4, '0', STR_PAD_LEFT) }}/{{ \Carbon\Carbon::parse($letter->issue_date)->format('Y') }}
        @if($letter->generated_by)
            &nbsp;&nbsp;&nbsp;
            <strong>Prepared by:</strong> {{ $letter->generated_by }}
        @endif
    </div>

    {{-- ── RECIPIENT ───────────────────────────────────────────────────── --}}
    <div class="recipient">
        <div class="label">To</div>
        <div class="name">{{ $letter->employee_name }}</div>
        @if(!empty($letter->current_position))
            <div>{{ $letter->current_position }}</div>
        @endif
        @if(!empty($letter->department))
            <div>{{ $letter->department }}</div>
        @endif
        <div>{{ $company->business_name ?? '' }}</div>
    </div>

    {{-- ── SUBJECT LINE ────────────────────────────────────────────────── --}}
    <div class="subject-line">
        RE: <span>
            @if($letter->letter_type === 'Offer')
                Offer of Employment
            @elseif($letter->letter_type === 'Confirmation')
                Confirmation of Employment
            @elseif($letter->letter_type === 'Promotion')
                Promotion / Change of Role
            @elseif($letter->letter_type === 'Termination')
                Termination of Employment
            @else
                {{ $letter->letter_type }} Letter
            @endif
        </span>
    </div>

    {{-- ── OPENING PARAGRAPH ───────────────────────────────────────────── --}}
    <div class="body-text">
        @if($letter->letter_type === 'Offer')
            Dear <strong>{{ $letter->employee_name }}</strong>,
            <br><br>
            We are pleased to offer you employment with
            <strong>{{ $company->business_name ?? 'the Company' }}</strong>
            in the position of
            <strong>{{ $letter->offered_position ?? 'the role discussed' }}</strong>
            @if(!empty($letter->offered_department))
                within the <strong>{{ $letter->offered_department }}</strong> department
            @endif.
            This offer is subject to the terms and conditions outlined below.

        @elseif($letter->letter_type === 'Confirmation')
            Dear <strong>{{ $letter->employee_name }}</strong>,
            <br><br>
            We are delighted to inform you that, following the successful completion of your
            probationary period, your employment with
            <strong>{{ $company->business_name ?? 'the Company' }}</strong>
            has been confirmed on a permanent basis with effect from
            <strong>{{ $letter->start_date ? \Carbon\Carbon::parse($letter->start_date)->format('d F Y') : 'the date indicated below' }}</strong>.

        @elseif($letter->letter_type === 'Promotion')
            Dear <strong>{{ $letter->employee_name }}</strong>,
            <br><br>
            It is with great pleasure that
            <strong>{{ $company->business_name ?? 'the Company' }}</strong>
            informs you of your promotion to the position of
            <strong>{{ $letter->offered_position ?? 'the new role' }}</strong>
            @if(!empty($letter->offered_department))
                in the <strong>{{ $letter->offered_department }}</strong> department
            @endif,
            effective <strong>{{ $letter->start_date ? \Carbon\Carbon::parse($letter->start_date)->format('d F Y') : 'the date indicated below' }}</strong>.
            This recognition reflects your dedication, performance and contribution to the organisation.

        @elseif($letter->letter_type === 'Termination')
            Dear <strong>{{ $letter->employee_name }}</strong>,
            <br><br>
            This letter serves as formal notice that your employment with
            <strong>{{ $company->business_name ?? 'the Company' }}</strong>
            will be terminated effective
            <strong>{{ $letter->start_date ? \Carbon\Carbon::parse($letter->start_date)->format('d F Y') : 'the date indicated below' }}</strong>.
            Please treat this communication as strictly confidential.
        @endif
    </div>

    {{-- ── DETAILS TABLE ───────────────────────────────────────────────── --}}
    @php
        $hasDetails =
            !empty($letter->offered_position)   ||
            !empty($letter->offered_department) ||
            !empty($letter->offered_salary)     ||
            !empty($letter->start_date);
    @endphp

    @if($hasDetails)
    <div class="details-section">
        <div class="details-section-title">
            @if($letter->letter_type === 'Termination')
                Termination Details
            @else
                Employment Details
            @endif
        </div>
        <table class="details-table">
            @if(!empty($letter->offered_position))
            <tr>
                <td class="field-label">
                    @if($letter->letter_type === 'Promotion') New Position @else Position @endif
                </td>
                <td class="field-value">{{ $letter->offered_position }}</td>
            </tr>
            @endif

            @if(!empty($letter->offered_department))
            <tr>
                <td class="field-label">Department</td>
                <td class="field-value">{{ $letter->offered_department }}</td>
            </tr>
            @endif

            @if(!empty($letter->offered_salary))
            <tr>
                <td class="field-label">
                    @if($letter->letter_type === 'Promotion') New Gross Salary @else Gross Salary @endif
                </td>
                <td class="field-value">
                    {{ number_format($letter->offered_salary, 2) }}
                    per month
                </td>
            </tr>
            @endif

            @if(!empty($letter->start_date))
            <tr>
                <td class="field-label">
                    @if($letter->letter_type === 'Termination')
                        Effective Date
                    @elseif($letter->letter_type === 'Confirmation')
                        Confirmation Date
                    @else
                        Commencement Date
                    @endif
                </td>
                <td class="field-value">
                    {{ \Carbon\Carbon::parse($letter->start_date)->format('d F Y') }}
                </td>
            </tr>
            @endif

            <tr>
                <td class="field-label">Issue Date</td>
                <td class="field-value">
                    {{ \Carbon\Carbon::parse($letter->issue_date)->format('d F Y') }}
                </td>
            </tr>
        </table>
    </div>
    @endif

    {{-- ── CLOSING PARAGRAPHS ──────────────────────────────────────────── --}}
    <div class="body-text">
        @if($letter->letter_type === 'Offer')
            Kindly indicate your acceptance of this offer by signing and returning a copy of this
            letter no later than <strong>five (5) working days</strong> from the date of issue.
            Should you have any questions or require clarification on any of the terms, please do
            not hesitate to contact the Human Resources department.

        @elseif($letter->letter_type === 'Confirmation')
            Your terms and conditions of employment remain as previously communicated unless
            otherwise stated in this letter. We look forward to your continued contribution and
            growth within the organisation.

        @elseif($letter->letter_type === 'Promotion')
            Your revised terms and conditions will take effect on the commencement date stated
            above. Please sign and return a copy of this letter to acknowledge your acceptance of
            the new role and its associated terms.

        @elseif($letter->letter_type === 'Termination')
            Please ensure that all company property, access credentials, and outstanding work are
            handed over to your line manager or the Human Resources department by the effective
            date. Your final pay, including any accrued leave, will be processed in accordance
            with the company's standard payroll procedures.
        @endif
    </div>

    {{-- ── NOTES ───────────────────────────────────────────────────────── --}}
    @if(!empty($letter->notes))
    <div class="notes-box">
        <div class="notes-title">Additional Notes</div>
        {{ $letter->notes }}
    </div>
    @endif

    {{-- ── CLOSING ──────────────────────────────────────────────────────── --}}
    <div class="closing">
        Yours sincerely,
    </div>

    {{-- ── SIGNATURE BLOCK ─────────────────────────────────────────────── --}}
    <div class="signature-block">
        <div class="signature-line"></div>
        <div class="signature-name">
            {{ $letter->generated_by ?? 'Human Resources Manager' }}
        </div>
        <div class="signature-title">
            Human Resources &nbsp;|&nbsp; {{ $company->business_name ?? '' }}
        </div>
    </div>

    {{-- ── ACCEPTANCE BLOCK (Offer & Promotion only) ───────────────────── --}}
    @if(in_array($letter->letter_type, ['Offer', 'Promotion']))
    <div class="acceptance-block">
        <div class="acceptance-title">Employee Acceptance — Please sign and return</div>
        <table class="acceptance-table">
            <tr>
                <td style="width:50%; padding-right:20px;">
                    <span class="sign-line">&nbsp;</span>
                    <div class="sign-label">Employee Signature</div>
                </td>
                <td style="width:50%;">
                    <span class="sign-line">&nbsp;</span>
                    <div class="sign-label">Date</div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding-top:16px;">
                    <span class="sign-line" style="width:92%;">&nbsp;</span>
                    <div class="sign-label">Full Name (Print)</div>
                </td>
            </tr>
        </table>
    </div>
    @endif

    {{-- ── FOOTER ──────────────────────────────────────────────────────── --}}
    <div class="footer">
        {{ $company->business_name ?? '' }}
        @if(!empty($company->physical_address))
            &nbsp;|&nbsp; {{ $company->physical_address }}
        @endif
        @if(!empty($company->primary_number))
            &nbsp;|&nbsp; {{ $company->primary_number }}
        @endif
        @if(!empty($company->email_address))
            &nbsp;|&nbsp; {{ $company->email_address }}
        @endif
        &nbsp;&nbsp;—&nbsp;&nbsp;
        This document is confidential and intended solely for the named recipient.
    </div>

</div>
</body>
</html>