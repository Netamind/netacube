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
    font-size: 7.5pt;
    color: #1a1a1a;
    background: #ffffff;
    padding: 16pt 24pt 16pt 24pt;
}

/* ── HEADER ── */
.hdr-tbl { width: 100%; border-collapse: collapse; }
.hdr-tbl td { vertical-align: middle; padding: 0; }

.accent-bar {
    display: inline-block;
    width: 3pt; height: 24pt;
    background: #4B5EBD;
    vertical-align: middle;
    margin-right: 7pt;
}
.co-block  { display: inline-block; vertical-align: middle; }
.co-name   { font-size: 11pt; font-weight: 900; color: #1a1a1a; display: block; letter-spacing: -0.2pt; }
.co-meta   { font-size: 6pt; color: #666; display: block; margin-top: 2pt; line-height: 1.7; }
.co-tin    { font-size: 6pt; color: #4B5EBD; font-weight: 700; display: block; margin-top: 1pt; }

.slip-right { text-align: right; }
.slip-eyebrow {
    font-size: 5.8pt; font-weight: 800; text-transform: uppercase;
    letter-spacing: 2pt; color: #4B5EBD;
    display: inline-block;
    border-bottom: 1pt solid #4B5EBD;
    padding-bottom: 2pt; margin-bottom: 3pt;
}
.slip-period  { font-size: 10pt; font-weight: 900; color: #1a1a1a; display: block; }
.slip-dates   { font-size: 6pt; color: #555; display: block; margin-top: 1pt; }
.slip-paydate { font-size: 6pt; font-weight: 700; color: #1a1a1a; display: block; }
.slip-ref     { font-size: 5.5pt; color: #999; display: block; margin-top: 2pt; font-style: italic; }

.hdr-rule { border: none; border-top: 0.5pt solid #1a1a1a; margin: 9pt 0 0 0; }

/* ── EMPLOYEE INFO — 2 columns ── */
.info-outer { width: 100%; border-collapse: collapse; border: 0.5pt solid #ccc; margin-top: 9pt; }
.info-sect-hd td {
    background: #f4f5fc;
    border-bottom: 1pt solid #4B5EBD;
    padding: 3.5pt 8pt;
    font-size: 5.8pt; font-weight: 800;
    text-transform: uppercase; letter-spacing: 1pt; color: #4B5EBD;
}
.info-col      { width: 50%; vertical-align: top; padding: 0; }
.info-col.left { border-right: 0.5pt solid #ccc; }

.kv-tbl { width: 100%; border-collapse: collapse; }
.kv-tbl tr td { padding: 3pt 8pt; border-bottom: 0.5pt solid #f0f0f0; }
.kv-tbl tr:last-child td { border-bottom: none; }
.kv-l {
    font-size: 5.8pt; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.4pt; color: #999; width: 38%; white-space: nowrap;
}
.kv-v      { font-size: 7.5pt; font-weight: 700; color: #1a1a1a; }
.kv-v-mono { font-size: 7pt; font-weight: 700; color: #1a1a1a;
              font-family: 'Courier New', monospace; letter-spacing: 0.3pt; }

.pill-y { border: 0.5pt solid #16a34a; color: #14532d; padding: 0.5pt 4pt;
           border-radius: 2pt; font-size: 5.5pt; font-weight: 800; background: #f0fdf4; }
.pill-n { border: 0.5pt solid #ccc; color: #999; padding: 0.5pt 4pt;
           border-radius: 2pt; font-size: 5.5pt; }

/* ── EARNINGS & DEDUCTIONS ── */
.ed-wrap { margin-top: 9pt; }
.ed-tbl  { width: 100%; border-collapse: collapse; border: 0.5pt solid #ccc; }

.ed-col-hd {
    background: #4B5EBD; color: #fff;
    font-size: 6.5pt; font-weight: 800;
    text-transform: uppercase; letter-spacing: 1pt;
    padding: 5pt 9pt; width: 50%;
}
.ed-col-hd.right { border-left: 0.5pt solid #6070c8; }

.ed-tbl tr.ed-row td {
    padding: 3.5pt 9pt;
    border-bottom: 0.5pt solid #f0f0f0;
    font-size: 7.5pt; vertical-align: middle;
}
.ed-ll { color: #444; width: 27%; }
.ed-la { text-align: right; font-weight: 600; color: #1a1a1a; width: 23%; white-space: nowrap; }
.ed-rl { color: #444; width: 27%; border-left: 0.5pt solid #ccc; padding-left: 9pt; }
.ed-ra { text-align: right; font-weight: 600; color: #1a1a1a; width: 23%; white-space: nowrap; }

.amt-zero { color: #ccc; }

/* Subtotal row */
.ed-tbl tr.ed-subtotal td {
    padding: 4pt 9pt;
    font-weight: 800; font-size: 7.5pt;
    border-top: 0.8pt solid #bbb;
    background: #fafafa;
}
.ed-subtotal .ed-ll { color: #4B5EBD; }
.ed-subtotal .ed-la { color: #4B5EBD; }
.ed-subtotal .ed-rl { color: #c0392b; border-left: 0.5pt solid #ccc; padding-left: 9pt; }
.ed-subtotal .ed-ra { color: #c0392b; }

/* ── SUMMARY TABLE — vertical layout ── */
.summary-wrap { margin-top: 6pt; }

.summary-tbl { width: 100%; border-collapse: collapse; border: 0.5pt solid #ccc; }

.summary-title td {
    background: #f4f5fc;
    border-bottom: 1pt solid #4B5EBD;
    padding: 3.5pt 8pt;
    font-size: 5.8pt; font-weight: 800;
    text-transform: uppercase; letter-spacing: 1pt; color: #4B5EBD;
    colspan: 3;
}

/* Each summary row: label | spacer | value */
.summary-tbl tr.s-row td {
    padding: 3pt 8pt;
    border-bottom: 0.5pt solid #f0f0f0;
    font-size: 7.5pt;
    vertical-align: middle;
}
.summary-tbl tr.s-row:last-child td { border-bottom: none; }
.summary-tbl tr.s-row.s-net-row td { background: #f0f2fc; border-top: 1pt solid #4B5EBD; }

.s-lbl {
    font-size: 5.8pt; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.4pt; color: #999; width: 38%; white-space: nowrap;
}
.s-val {
    text-align: right; font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: #1a1a1a; white-space: nowrap; width: 62%;
}
.s-val.s-ded { color: #c0392b; }
.s-val.s-net { color: #4B5EBD; font-weight: 800; font-size: 8.5pt; }

/* Currency note once, in title */
.s-currency-note {
    float: right; font-size: 5.5pt; font-weight: 600;
    color: #aaa; letter-spacing: 0.3pt; font-style: italic;
    text-transform: none; letter-spacing: 0;
}

/* ── SIGNATURES ── */
.sig-wrap { margin-top: 10pt; }
.sig-tbl  { width: 100%; border-collapse: collapse; }
.sig-tbl td { vertical-align: bottom; width: 33.33%; padding: 0 18pt 0 0; }
.sig-tbl td:last-child { padding-right: 0; }
.sig-line-top { border-top: 0.5pt solid #888; padding-top: 4pt; margin-top: 22pt; }
.sig-name { font-size: 6.5pt; font-weight: 700; color: #1a1a1a; display: block; }
.sig-role { font-size: 5.5pt; color: #888; text-transform: uppercase;
             letter-spacing: 0.4pt; display: block; margin-top: 1pt; }

/* ── NOTES ── */
.notes-wrap {
    margin-top: 8pt;
    border-left: 2pt solid #f59e0b;
    background: #fffbeb;
    padding: 3.5pt 7pt;
    font-size: 6.5pt; color: #78350f;
}

/* ── FOOTER ── */
.pgfoot {
    position: fixed; bottom: 10pt; left: 24pt; right: 24pt;
    border-top: 0.5pt solid #ccc; padding-top: 4pt;
}
.pgfoot-tbl { width: 100%; border-collapse: collapse; }
.pgfoot-tbl td { font-size: 5.5pt; color: #999; vertical-align: middle; }
.pgfoot-tbl td.pr { text-align: right; }
.foot-space { height: 18pt; }
</style>
</head>
<body>

<?php
    $refHash = strtoupper(substr(
        md5($entry->id . $entry->payroll_period_id . $entry->net_pay . $entry->employee_id), 0, 8
    ));
    $slipRef = 'PSL-' . $entry->payroll_period_id . '-' . $entry->id . '-' . $refHash;

    /* Earnings */
    $basicSalary        = (float)($entry->basic_salary               ?? 0);
    $housingAllowance   = (float)($entry->housing_allowance          ?? 0);
    $transportAllowance = (float)($entry->transport_allowance        ?? 0);
    $medicalAllowance   = (float)($entry->medical_allowance          ?? 0);
    $mealAllowance      = (float)($entry->meal_allowance             ?? 0);
    $otherRecurring     = (float)($entry->other_recurring_allowance  ?? 0);
    $actingAllowance    = (float)($entry->acting_allowance           ?? 0);
    $commissions        = (float)($entry->commissions                ?? 0);
    $otherVariable      = (float)($entry->other_variable_allowance   ?? 0);
    $overtimeAmount     = (float)($entry->overtime_amount            ?? 0);
    $grossPay           = (float)($entry->gross_pay                  ?? 0);

    $otherAllowances = $mealAllowance + $otherRecurring + $actingAllowance
                     + $commissions   + $otherVariable;
    $totalAllowances = $housingAllowance + $transportAllowance + $medicalAllowance
                     + $otherAllowances  + $overtimeAmount;

    /* Deductions */
    $paye            = (float)($entry->paye              ?? 0);
    $pensionEe       = (float)($entry->pension_employee  ?? 0);
    $pensionEr       = (float)($entry->pension_employer  ?? 0);
    $loanDed         = (float)($entry->loan_deduction    ?? 0);
    $advanceDed      = (float)($entry->advance_deduction ?? 0);
    $otherDed        = (float)($entry->other_deductions  ?? 0);
    $totalDeductions = (float)($entry->total_deductions  ?? 0);
    $netPay          = (float)($entry->net_pay           ?? 0);
    $onPension       = (bool) ($entry->on_pension        ?? false);

    $otherDedTotal = $advanceDed + $otherDed;

    /* Bank — from users table fields */
    $bankName     = $entry->bank_name           ?? '';
    $bankAcctName = $entry->bank_account_name   ?? '';
    $bankAcctNo   = $entry->bank_account_number ?? '';
    $bankBranch   = $entry->bank_branch         ?? '';
    $bankAcctType = $entry->bank_account_type   ?? '';

    function fmtAmt($v) { return number_format((float)$v, 2); }
    function amtClass($v) { return (float)$v == 0 ? 'amt-zero' : ''; }
?>

{{-- HEADER --}}
<table class="hdr-tbl">
  <tr>
    <td style="width:58%;">
      <span class="accent-bar"></span>
      <span class="co-block">
        <span class="co-name">{{ $company->business_name ?? 'Company Name' }}</span>
        <span class="co-meta">
          @if(!empty($company->physical_address)){{ $company->physical_address }}<br>@endif
          @if(!empty($company->primary_number))Tel: {{ $company->primary_number }}@endif
          @if(!empty($company->email_address)) &bull; {{ $company->email_address }}@endif
        </span>
        @if(!empty($company->tin_number))<span class="co-tin">TIN: {{ $company->tin_number }}</span>@endif
      </span>
    </td>
    <td class="slip-right" style="width:42%;">
      <span class="slip-eyebrow">Employee Pay Slip</span>
      <span class="slip-period">{{ $period->name }}</span>
      <span class="slip-dates">
        {{ \Carbon\Carbon::parse($period->period_start)->format('d M Y') }}
        &ndash; {{ \Carbon\Carbon::parse($period->period_end)->format('d M Y') }}
      </span>
      <span class="slip-paydate">Pay Date: {{ \Carbon\Carbon::parse($period->pay_date)->format('d M Y') }}</span>
      <span class="slip-ref">Ref: {{ $slipRef }}</span>
    </td>
  </tr>
</table>
<hr class="hdr-rule">

{{-- EMPLOYEE INFO — 2 COLUMNS --}}
<table class="info-outer">
  <tr class="info-sect-hd"><td colspan="2">Employee &amp; Payment Details</td></tr>
  <tr>
    {{-- LEFT: personal & employment --}}
    <td class="info-col left">
      <table class="kv-tbl">
        <tr><td class="kv-l">Full Name</td><td class="kv-v">{{ $entry->employee_name ?? '—' }}</td></tr>
        <tr><td class="kv-l">Employee No.</td><td class="kv-v">{{ $entry->employee_number ?? '—' }}</td></tr>
        <tr><td class="kv-l">Position</td><td class="kv-v">{{ $entry->position ?? '—' }}</td></tr>
        <tr><td class="kv-l">Department</td><td class="kv-v">{{ $entry->department ?? '—' }}</td></tr>
      </table>
    </td>
    {{-- RIGHT: bank + pension/paye --}}
    <td class="info-col">
      <table class="kv-tbl">
        <tr><td class="kv-l">Bank</td><td class="kv-v">{{ $bankName ?: '—' }}</td></tr>
        <tr><td class="kv-l">Account Name</td><td class="kv-v">{{ $bankAcctName ?: '—' }}</td></tr>
        <tr><td class="kv-l">Account No.</td><td class="kv-v-mono">{{ $bankAcctNo ?: '—' }}</td></tr>
        @if($bankBranch || $bankAcctType)
        <tr><td class="kv-l">Branch / Type</td><td class="kv-v">{{ implode(' / ', array_filter([$bankBranch, $bankAcctType])) }}</td></tr>
        @endif
        <tr>
          <td class="kv-l">Pension</td>
          <td class="kv-v">@if($onPension)<span class="pill-y">Enrolled</span>@else<span class="pill-n">Not Enrolled</span>@endif</td>
        </tr>
        <tr>
          <td class="kv-l">PAYE</td>
          <td class="kv-v">@if($paye > 0)<span class="pill-y">On PAYE</span>@else<span class="pill-n">Exempt</span>@endif</td>
        </tr>
      </table>
    </td>
  </tr>
</table>

{{-- EARNINGS & DEDUCTIONS --}}
<div class="ed-wrap">
  <table class="ed-tbl">
    <tr>
      <td class="ed-col-hd" colspan="2">Earnings</td>
      <td class="ed-col-hd right" colspan="2">Deductions</td>
    </tr>
    <tr class="ed-row">
      <td class="ed-ll">Basic Salary</td>
      <td class="ed-la">{{ fmtAmt($basicSalary) }}</td>
      <td class="ed-rl">PAYE (Income Tax)</td>
      <td class="ed-ra {{ amtClass($paye) }}">{{ fmtAmt($paye) }}</td>
    </tr>
    <tr class="ed-row">
      <td class="ed-ll">Housing Allowance</td>
      <td class="ed-la {{ amtClass($housingAllowance) }}">{{ fmtAmt($housingAllowance) }}</td>
      <td class="ed-rl">Pension — Employee</td>
      <td class="ed-ra {{ amtClass($pensionEe) }}">{{ fmtAmt($pensionEe) }}</td>
    </tr>
    <tr class="ed-row">
      <td class="ed-ll">Transport Allowance</td>
      <td class="ed-la {{ amtClass($transportAllowance) }}">{{ fmtAmt($transportAllowance) }}</td>
      <td class="ed-rl">Pension — Employer</td>
      <td class="ed-ra {{ amtClass($pensionEr) }}">{{ fmtAmt($pensionEr) }}</td>
    </tr>
    <tr class="ed-row">
      <td class="ed-ll">Medical Allowance</td>
      <td class="ed-la {{ amtClass($medicalAllowance) }}">{{ fmtAmt($medicalAllowance) }}</td>
      <td class="ed-rl">Loan Deduction</td>
      <td class="ed-ra {{ amtClass($loanDed) }}">{{ fmtAmt($loanDed) }}</td>
    </tr>
    <tr class="ed-row">
      <td class="ed-ll">Overtime</td>
      <td class="ed-la {{ amtClass($overtimeAmount) }}">{{ fmtAmt($overtimeAmount) }}</td>
      <td class="ed-rl">Other Deductions</td>
      <td class="ed-ra {{ amtClass($otherDedTotal) }}">{{ fmtAmt($otherDedTotal) }}</td>
    </tr>
    <tr class="ed-row">
      <td class="ed-ll">Other Allowances</td>
      <td class="ed-la {{ amtClass($otherAllowances) }}">{{ fmtAmt($otherAllowances) }}</td>
      <td class="ed-rl"></td>
      <td class="ed-ra"></td>
    </tr>
    <tr class="ed-subtotal">
      <td class="ed-ll">Total Gross Pay</td>
      <td class="ed-la">{{ fmtAmt($grossPay) }}</td>
      <td class="ed-rl">Total Deductions</td>
      <td class="ed-ra">{{ fmtAmt($totalDeductions) }}</td>
    </tr>
  </table>
</div>

{{-- SUMMARY TABLE — vertical --}}
<div class="summary-wrap">
  <table class="summary-tbl">
    <tr class="summary-title">
      <td colspan="2">Summary <span class="s-currency-note">All figures in MWK</span></td>
    </tr>
    <tr class="s-row">
      <td class="s-lbl">Basic Pay</td>
      <td class="s-val">{{ fmtAmt($basicSalary) }}</td>
    </tr>
    <tr class="s-row">
      <td class="s-lbl">Total Allowances</td>
      <td class="s-val">{{ fmtAmt($totalAllowances) }}</td>
    </tr>
    <tr class="s-row">
      <td class="s-lbl">Gross Pay</td>
      <td class="s-val">{{ fmtAmt($grossPay) }}</td>
    </tr>
    <tr class="s-row">
      <td class="s-lbl">Total Deductions</td>
      <td class="s-val s-ded">{{ fmtAmt($totalDeductions) }}</td>
    </tr>
    <tr class="s-row s-net-row">
      <td class="s-lbl" style="color:#4B5EBD; font-weight:800;">Net Pay</td>
      <td class="s-val s-net">{{ fmtAmt($netPay) }}</td>
    </tr>
  </table>
</div>

{{-- OPTIONAL NOTES --}}
@if(!empty($entry->notes))
<div class="notes-wrap"><strong>Note:</strong> {{ $entry->notes }}</div>
@endif

{{-- SIGNATURES --}}
<div class="sig-wrap">
  <table class="sig-tbl">
    <tr>
      <td>
        <div class="sig-line-top">
          <span class="sig-name">{{ $entry->employee_name ?? '' }}</span>
          <span class="sig-role">Employee Signature</span>
        </div>
      </td>
      <td>
        <div class="sig-line-top">
          <span class="sig-name">{{ $company->business_name ?? '' }}</span>
          <span class="sig-role">Authorised By</span>
        </div>
      </td>
      <td>
        <div class="sig-line-top">
          <span class="sig-name">&nbsp;</span>
          <span class="sig-role">Date</span>
        </div>
      </td>
    </tr>
  </table>
</div>

<div class="foot-space"></div>

{{-- FIXED FOOTER --}}
<div class="pgfoot">
  <table class="pgfoot-tbl">
    <tr>
      <td>Generated: {{ \Carbon\Carbon::now()->format('d M Y, H:i') }} &nbsp;&bull;&nbsp; Ref: {{ $slipRef }}</td>
      <td class="pr">This payslip is confidential — intended solely for the named employee.</td>
    </tr>
  </table>
</div>

</body>
</html>