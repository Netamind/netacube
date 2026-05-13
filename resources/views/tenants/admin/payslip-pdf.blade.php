<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Payslip</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 8.5px;
    color: #1e293b;
    background: #fff;
    padding: 20px 24px 0 24px;
}

/* ════════════════════════════════════════════════
   HEADER
════════════════════════════════════════════════ */
.hdr { width: 100%; border-collapse: collapse; }
.hdr-left {
    background: #4B5EBD;
    padding: 11px 16px;
    width: 58%;
    vertical-align: middle;
}
.hdr-right {
    background: #3a4d9f;
    padding: 11px 14px;
    width: 42%;
    text-align: right;
    vertical-align: middle;
}
.co-name { font-size: 15px; font-weight: 800; color: #fff; display: block; }
.co-sub  { font-size: 7px; color: rgba(255,255,255,0.72); line-height: 1.8;
           display: block; margin-top: 3px; }
.sl-title { font-size: 10px; font-weight: 800; color: #fff;
            text-transform: uppercase; letter-spacing: 1.8px; display: block; }
.sl-per  { font-size: 7.5px; color: rgba(255,255,255,0.75); line-height: 1.8;
           display: block; margin-top: 3px; }
.sl-date { font-size: 8px; font-weight: 700; color: #c7d2fe;
           display: block; margin-top: 3px; }

/* ════════════════════════════════════════════════
   EMPLOYEE + META BAND  (two rows, same table)
   Row 1: Name / Position / Department / Pay Period / Pension / Ref
   Row 2: Bank Name / Account No / Pay Date / Status / (spans)
════════════════════════════════════════════════ */
.info { width: 100%; border-collapse: collapse; margin-top: 7px;
        border: 1px solid #dde3f5; }
.info td {
    padding: 5px 9px;
    vertical-align: top;
    border-right: 1px solid #dde3f5;
    border-bottom: 1px solid #dde3f5;
}
.info td:last-child { border-right: none; }
.info tr:last-child td { border-bottom: none; }
/* accent stripe on left edge */
.info tr:first-child td:first-child { border-left: 3px solid #4B5EBD; }
.info tr:last-child  td:first-child { border-left: 3px solid #4B5EBD; }
/* row 1 background */
.info tr:first-child td { background: #f4f6fb; }
/* row 2 background */
.info tr:last-child  td { background: #fafbfe; }

.il { font-size: 6px; font-weight: 700; text-transform: uppercase;
      letter-spacing: 0.7px; color: #94a3b8; display: block; margin-bottom: 2px; }
.iv { font-size: 8.5px; font-weight: 700; color: #1e293b; display: block; }
.iv-sm { font-size: 7.5px; font-weight: 600; color: #1e293b; display: block; }
.iv-ref { font-size: 7px; font-weight: 700; color: #4B5EBD; display: block; }

/* pension badges */
.bon  { background: #dcfce7; color: #15803d; border: 1px solid #86efac;
        padding: 1px 5px; border-radius: 5px; font-size: 6.5px; font-weight: 700; }
.boff { background: #f1f5f9; color: #94a3b8; border: 1px solid #e2e8f0;
        padding: 1px 5px; border-radius: 5px; font-size: 6.5px; }

/* ════════════════════════════════════════════════
   THREE-COLUMN PAYMENTS / DEDUCTIONS / SUMMARY
   Equal height: outer <td> set to height:170px;
   inner table fills it top-down, subtotal pinned
   by being last row.
════════════════════════════════════════════════ */
.body { width: 100%; border-collapse: collapse; margin-top: 8px;
        border: 1px solid #dde3f5; }

/* column header cells */
.ch {
    background: #4B5EBD; color: #fff;
    font-size: 7px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.8px;
    padding: 5px 9px;
    border-right: 1px solid #5a6ec9;
    vertical-align: middle;
}
.ch:last-child { border-right: none; }

/* outer data cells — fixed height forces equal columns */
.dc {
    width: 33.33%;
    vertical-align: top;
    border-right: 1px solid #dde3f5;
    padding: 0;
    height: 162px;        /* fixed — enough for 6 rows + subtotal */
}
.dc:last-child { border-right: none; }

/* inner line-item table */
.lt { width: 100%; border-collapse: collapse; }
.lt tr { border-bottom: 1px solid #eef0f7; }
.lt tr:last-child { border-bottom: none; }
.lt td { padding: 3.5px 9px; font-size: 8px; vertical-align: middle; }
.lt td.lb { color: #475569; }
.lt td.av { text-align: right; font-weight: 600; color: #1e293b;
            white-space: nowrap; font-variant-numeric: tabular-nums; }
.lt td.az { text-align: right; color: #c8d0e8;
            font-variant-numeric: tabular-nums; }
.lt tr.st td {
    background: #eef0fb; font-weight: 700;
    border-top: 1.5px solid #c8d0ed;
}
.lt tr.st td.av  { color: #4B5EBD; }
.lt tr.st td.ard { color: #c0392b; font-weight: 700; }

/* YTD / This Month summary table — same structure */
.yt { width: 100%; border-collapse: collapse; }
.yt tr { border-bottom: 1px solid #eef0f7; }
.yt tr:last-child { border-bottom: none; }
.yt td { padding: 3.5px 9px; font-size: 8px; vertical-align: middle; }
.yt td.lb { color: #475569; }
.yt td.av { text-align: right; font-weight: 600; color: #1e293b;
            font-variant-numeric: tabular-nums; }
.yt-hd td {
    background: #eef0fb; font-size: 6.5px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.6px;
    color: #4B5EBD; padding: 3px 9px;
    border-bottom: 1px solid #dde3f5;
}

/* ════════════════════════════════════════════════
   NET PAY SUMMARY — branded indigo strip
   Sits directly below body table (no gap).
   Three cells matching the three columns above.
════════════════════════════════════════════════ */
.net { width: 100%; border-collapse: collapse; }
.net td {
    width: 33.33%;
    background: #4B5EBD;
    color: #fff;
    padding: 8px 11px;
    border-right: 1px solid #5a6ec9;
    vertical-align: middle;
}
.net td:last-child { border-right: none; }
.nl { font-size: 6px; text-transform: uppercase; letter-spacing: 0.9px;
      color: rgba(255,255,255,0.6); display: block; margin-bottom: 2px; }
.nv { font-size: 12px; font-weight: 800; display: block;
      font-variant-numeric: tabular-nums; color: #fff; }
.nv-hi { color: #fff; }          /* gross  — plain white  */
.nv-ded { color: #fca5a5; }      /* deduct — soft red     */
.nv-net { color: #bbf7d0; }      /* net pay — soft green  */
.ns { font-size: 6.5px; color: rgba(255,255,255,0.5);
      display: block; margin-top: 2px; }

/* ════════════════════════════════════════════════
   SIGNATURE ROW
════════════════════════════════════════════════ */
.sig { width: 100%; border-collapse: collapse; margin-top: 9px; }
.sig td { vertical-align: bottom; padding: 0 16px 0 0; width: 33.33%; }
.sig td:last-child { padding-right: 0; }
.sig-line { border-top: 1px solid #94a3b8; padding-top: 4px;
            margin-top: 18px; font-size: 7px; color: #64748b; }
.sig-role { font-size: 6px; text-transform: uppercase; letter-spacing: 0.5px;
            color: #94a3b8; margin-top: 1px; }

/* ════════════════════════════════════════════════
   NOTES
════════════════════════════════════════════════ */
.nb { background: #fffbeb; border: 1px solid #fde68a; border-radius: 3px;
      padding: 5px 9px; margin-top: 8px; font-size: 7.5px; color: #78350f; }

/* ════════════════════════════════════════════════
   FIXED FOOTER
════════════════════════════════════════════════ */
.pgfoot {
    position: fixed; bottom: 0; left: 24px; right: 24px;
    border-top: 1px solid #dde3f5;
    padding: 5px 0;
    background: #fff;
}
.pgfoot table { width: 100%; border-collapse: collapse; }
.pgfoot td { font-size: 6.5px; color: #94a3b8; vertical-align: middle; }
.pgfoot td.pr { text-align: right; }

.foot-space { height: 28px; }
</style>
</head>
<body>

<?php
    $refHash = strtoupper(substr(
        md5($entry->id . $entry->payroll_period_id . $entry->net_pay . $entry->employee_id), 0, 6
    ));
    $slipRef = 'PSL-' . $entry->payroll_period_id . '-' . $entry->id . '-' . $refHash;

    $base = \DB::connection('tenant')
        ->table('payroll_entries')
        ->join('payroll_periods', 'payroll_periods.id', '=', 'payroll_entries.payroll_period_id')
        ->where('payroll_entries.employee_id', $entry->employee_id)
        ->where('payroll_periods.status', 'paid')
        ->where('payroll_periods.period_end', '<=', $period->period_end);

    $ytdGross   = (clone $base)->sum('payroll_entries.gross_pay');
    $ytdTax     = (clone $base)->sum('payroll_entries.paye');
    $ytdPension = (clone $base)->sum('payroll_entries.pension_employee');
    $ytdNet     = (clone $base)->sum('payroll_entries.net_pay');
?>

{{-- ══ HEADER ═══════════════════════════════════════════════════════════ --}}
<table class="hdr">
  <tr>
    <td class="hdr-left">
      <span class="co-name">{{ $company->business_name ?? 'Company Name' }}</span>
      <span class="co-sub">
        @if(!empty($company->physical_address)){{ $company->physical_address }}<br>@endif
        @if(!empty($company->primary_number))Tel: {{ $company->primary_number }}@endif
        @if(!empty($company->primary_number) && !empty($company->email_address)) &nbsp;&bull;&nbsp; @endif
        @if(!empty($company->email_address)){{ $company->email_address }}@endif
        @if(!empty($company->tin_number))<br>TIN: {{ $company->tin_number }}@endif
      </span>
    </td>
    <td class="hdr-right">
      <span class="sl-title">Employee Pay Slip</span>
      <span class="sl-per">
        {{ $period->name }}<br>
        {{ \Carbon\Carbon::parse($period->period_start)->format('d M Y') }}
        &ndash;
        {{ \Carbon\Carbon::parse($period->period_end)->format('d M Y') }}
      </span>
      <span class="sl-date">Pay Date: {{ \Carbon\Carbon::parse($period->pay_date)->format('d M Y') }}</span>
    </td>
  </tr>
</table>

{{-- ══ EMPLOYEE + META INFO (two rows in one table) ══════════════════════ --}}
<table class="info">

  {{-- Row 1: employee details --}}
  <tr>
    <td style="width:24%;">
      <span class="il">Employee Name</span>
      <span class="iv">{{ $entry->employee_name }}</span>
    </td>
    <td style="width:16%;">
      <span class="il">Position</span>
      <span class="iv">{{ $entry->position ?? '&mdash;' }}</span>
    </td>
    <td style="width:16%;">
      <span class="il">Department</span>
      <span class="iv">{{ $entry->department ?? '&mdash;' }}</span>
    </td>
    <td style="width:15%;">
      <span class="il">Pay Period</span>
      <span class="iv">{{ $period->name }}</span>
    </td>
    <td style="width:11%;">
      <span class="il">Pay Date</span>
      <span class="iv">{{ \Carbon\Carbon::parse($period->pay_date)->format('d M Y') }}</span>
    </td>
    <td style="width:9%;">
      <span class="il">Pension</span>
      <span class="iv" style="margin-top:2px;">
        @if($entry->on_pension)
          <span class="bon">Yes</span>
        @else
          <span class="boff">No</span>
        @endif
      </span>
    </td>
    <td style="width:9%; border-right:none;">
      <span class="il">Status</span>
      <span class="iv">{{ ucfirst($period->status) }}</span>
    </td>
  </tr>

  {{-- Row 2: banking + reference --}}
  <tr>
    <td style="width:24%;">
      <span class="il">Bank Name</span>
      <span class="iv-sm">{{ !empty($entry->bank_name) ? $entry->bank_name : '&mdash;' }}</span>
    </td>
    <td style="width:16%;">
      <span class="il">Account Number</span>
      <span class="iv-sm">{{ !empty($entry->bank_account_number) ? $entry->bank_account_number : '&mdash;' }}</span>
    </td>
    <td style="width:16%;">
      <span class="il">Employee No.</span>
      <span class="iv-sm">{{ !empty($entry->employee_number) ? $entry->employee_number : '&mdash;' }}</span>
    </td>
    <td style="width:15%;" colspan="2">
      <span class="il">Document Reference</span>
      <span class="iv-ref">{{ $slipRef }}</span>
    </td>
    <td style="width:9%;" colspan="2" style="border-right:none;">
      <span class="il">Generated</span>
      <span class="iv-sm">{{ \Carbon\Carbon::now()->format('d M Y') }}</span>
    </td>
  </tr>

</table>

{{-- ══ THREE-COLUMN BODY ══════════════════════════════════════════════════ --}}
<table class="body">
  <tr>
    <td class="ch" style="width:33.33%;">Payments</td>
    <td class="ch" style="width:33.33%;">Deductions</td>
    <td class="ch" style="width:33.34%;">Summary</td>
  </tr>
  <tr>

    {{-- PAYMENTS --}}
    <td class="dc">
      <table class="lt">
        <tr><td class="lb">Basic Salary</td>
            <td class="av">{{ number_format($entry->basic_salary,2) }}</td></tr>
        <tr><td class="lb">Housing Allowance</td>
            <td class="{{ $entry->housing_allowance==0 ? 'az' : 'av' }}">{{ number_format($entry->housing_allowance,2) }}</td></tr>
        <tr><td class="lb">Transport Allowance</td>
            <td class="{{ $entry->transport_allowance==0 ? 'az' : 'av' }}">{{ number_format($entry->transport_allowance,2) }}</td></tr>
        <tr><td class="lb">Other Allowances</td>
            <td class="{{ $entry->other_allowances==0 ? 'az' : 'av' }}">{{ number_format($entry->other_allowances,2) }}</td></tr>
        <tr><td class="lb">Overtime</td>
            <td class="{{ $entry->overtime_amount==0 ? 'az' : 'av' }}">{{ number_format($entry->overtime_amount,2) }}</td></tr>
        <tr class="st">
          <td class="lb">Total Gross Pay</td>
          <td class="av">{{ number_format($entry->gross_pay,2) }}</td>
        </tr>
      </table>
    </td>

    {{-- DEDUCTIONS --}}
    <td class="dc">
      <table class="lt">
        <tr><td class="lb">PAYE (Income Tax)</td>
            <td class="{{ $entry->paye==0 ? 'az' : 'av' }}">{{ number_format($entry->paye,2) }}</td></tr>
        <tr><td class="lb">Pension (Employee)</td>
            <td class="{{ $entry->pension_employee==0 ? 'az' : 'av' }}">{{ number_format($entry->pension_employee,2) }}</td></tr>
        <tr><td class="lb">Pension (Employer)</td>
            <td class="{{ $entry->pension_employer==0 ? 'az' : 'av' }}">{{ number_format($entry->pension_employer,2) }}</td></tr>
        <tr><td class="lb">Loan Deduction</td>
            <td class="{{ $entry->loan_deduction==0 ? 'az' : 'av' }}">{{ number_format($entry->loan_deduction,2) }}</td></tr>
        <tr><td class="lb">Advance Recovery</td>
            <td class="{{ $entry->advance_deduction==0 ? 'az' : 'av' }}">{{ number_format($entry->advance_deduction,2) }}</td></tr>
        <tr><td class="lb">Other Deductions</td>
            <td class="{{ $entry->other_deductions==0 ? 'az' : 'av' }}">{{ number_format($entry->other_deductions,2) }}</td></tr>
        <tr class="st">
          <td class="lb">Total Deductions</td>
          <td class="ard">{{ number_format($entry->total_deductions,2) }}</td>
        </tr>
      </table>
    </td>

    {{-- SUMMARY --}}
    <td class="dc">
      <table class="yt">
        <tr class="yt-hd"><td colspan="2">Year to Date</td></tr>
        <tr><td class="lb">Gross Pay</td>
            <td class="av">{{ number_format($ytdGross,2) }}</td></tr>
        <tr><td class="lb">Tax Paid</td>
            <td class="av">{{ number_format($ytdTax,2) }}</td></tr>
        <tr><td class="lb">Pension Paid</td>
            <td class="av">{{ number_format($ytdPension,2) }}</td></tr>
        <tr><td class="lb">Net Pay</td>
            <td class="av">{{ number_format($ytdNet,2) }}</td></tr>
        <tr class="yt-hd"><td colspan="2">This Month</td></tr>
        <tr><td class="lb">Gross Pay</td>
            <td class="av">{{ number_format($entry->gross_pay,2) }}</td></tr>
        <tr><td class="lb">Income Tax</td>
            <td class="av">{{ number_format($entry->paye,2) }}</td></tr>
        <tr><td class="lb">Pension (Ee)</td>
            <td class="av">{{ number_format($entry->pension_employee,2) }}</td></tr>
        <tr><td class="lb">Total Deductions</td>
            <td class="av" style="color:#c0392b;">{{ number_format($entry->total_deductions,2) }}</td></tr>
      </table>
    </td>

  </tr>
</table>

{{-- ══ NET PAY BAR (indigo, sits flush below body) ═══════════════════════ --}}
<table class="net">
  <tr>
    <td>
      <span class="nl">Total Gross Payments</span>
      <span class="nv nv-hi">MWK {{ number_format($entry->gross_pay,2) }}</span>
    </td>
    <td>
      <span class="nl">Total Deductions</span>
      <span class="nv nv-ded">MWK {{ number_format($entry->total_deductions,2) }}</span>
    </td>
    <td>
      <span class="nl">Net Pay</span>
      <span class="nv nv-net">MWK {{ number_format($entry->net_pay,2) }}</span>
      <span class="ns">{{ $period->name }} &bull; {{ \Carbon\Carbon::parse($period->pay_date)->format('d M Y') }}</span>
    </td>
  </tr>
</table>

{{-- ══ SIGNATURE ROW ══════════════════════════════════════════════════════ --}}
<table class="sig">
  <tr>
    <td>
      <div class="sig-line">{{ $entry->employee_name }}</div>
      <div class="sig-role">Employee Acknowledgement</div>
    </td>
    <td>
      <div class="sig-line">&nbsp;</div>
      <div class="sig-role">Authorised By</div>
    </td>
    <td>
      <div class="sig-line">{{ $company->business_name ?? '' }}</div>
      <div class="sig-role">Employer Stamp</div>
    </td>
  </tr>
</table>

@if(!empty($entry->notes))
<div class="nb"><strong>Note:</strong> {{ $entry->notes }}</div>
@endif

<div class="foot-space"></div>

{{-- ══ FIXED FOOTER ═══════════════════════════════════════════════════════ --}}
<div class="pgfoot">
  <table>
    <tr>
      <td>Generated: {{ \Carbon\Carbon::now()->format('d M Y, H:i') }} &nbsp;&bull;&nbsp; Ref: {{ $slipRef }}</td>
      <td class="pr">This payslip is confidential &mdash; intended solely for the named employee.</td>
    </tr>
  </table>
</div>

</body>
</html>