{{--
  Audit Log PDF Template
  View  : operations/retail/auditlogspdf.blade.php
  Engine: DomPDF (A4 landscape)
--}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Audit Report — {{ $branch->name ?? '' }} — {{ $formattedDate }}</title>
<style>
/* ═══════════════════════════════════════════════════════════════════
   DomPDF-safe — no flexbox, no grid, no CSS variables, no gradients.
   All multi-column layouts via <table>.
═══════════════════════════════════════════════════════════════════ */
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 12px;
    color: #333;
    background: #fff;
    padding: 36px 40px 0 40px;
}

/* ── Header ─────────────────────────────────────────────────────── */
.hdr-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
.hdr-table td { vertical-align: middle; padding: 0; }
.hdr-table td.right-col { text-align: right; }

.company-name {
    font-size: 22px;
    font-weight: 700;
    color: #4B5EBD;
    line-height: 1;
    margin-bottom: 0;
}
.company-meta {
    font-size: 10px;
    color: #555;
    line-height: 1.7;
    margin-top: 0;
}

.report-title {
    font-size: 22px;
    font-weight: 800;
    color: #4B5EBD;
    letter-spacing: 2px;
    text-transform: uppercase;
    line-height: 1;
    display: block;
    margin-bottom: 4px;
}
.branch-name-hdr {
    font-size: 13px;
    font-weight: 700;
    color: #222;
    display: block;
    margin-bottom: 2px;
}
.branch-meta-hdr {
    font-size: 10.5px;
    color: #666;
    line-height: 1.75;
    display: block;
    margin-bottom: 5px;
}
.dir-badge {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #fff;
}

/* ── Blue divider ───────────────────────────────────────────────── */
.divider {
    width: 100%;
    border: none;
    border-top: 2px solid #4B5EBD;
    margin: 14px 0 16px 0;
}

/* ── Dates strip ────────────────────────────────────────────────── */
.dates-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
.dates-table td { padding: 0 32px 0 0; vertical-align: top; }
.dates-table td:last-child { padding-right: 0; }
.date-label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #999;
    display: block;
    margin-bottom: 2px;
}
.date-val {
    font-size: 12px;
    font-weight: 600;
    color: #333;
    display: block;
}
.date-sub {
    font-size: 10px;
    color: #888;
    display: block;
    margin-top: 1px;
}

/* ── Main data table ────────────────────────────────────────────── */
table.inv-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0;
    table-layout: fixed;
}

table.inv-table thead tr { background: #4B5EBD; }
table.inv-table thead th {
    padding: 8px 8px;
    font-size: 8.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #fff;
    vertical-align: middle;
}
table.inv-table thead th.th-num    { text-align: left; padding-left: 7px; width: 26px; }
table.inv-table thead th.th-name   { text-align: left; width: 28%; }
table.inv-table thead th.th-center { text-align: center; }
table.inv-table thead th.th-right  { text-align: center; }

table.inv-table tbody tr { border-bottom: 1px solid #e9ecef; }
table.inv-table tbody tr:nth-child(even) { background: #f7f8fd; }
table.inv-table tbody tr:nth-child(odd)  { background: #fff; }

table.inv-table tbody td {
    padding: 6px 8px;
    font-size: 10.5px;
    color: #444;
    vertical-align: middle;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    line-height: 1.4;
}
table.inv-table tbody td.td-num  { text-align: left; padding-left: 7px; color: #999; font-size: 9.5px; }
table.inv-table tbody td.td-name { text-align: left; font-weight: 500; color: #222; }
table.inv-table tbody td.td-c    { text-align: center; }
table.inv-table tbody td.td-r    { text-align: center; }
table.inv-table tbody td.td-mono { text-align: center; font-size: 9px; color: #555; letter-spacing: 0.3px; }

table.inv-table tbody td.change-in   { text-align: center; color: #1a7a3c; font-weight: 700; }
table.inv-table tbody td.change-out  { text-align: center; color: #c0392b; font-weight: 700; }
table.inv-table tbody td.change-zero { text-align: center; color: #888;    font-weight: 600; }

/* ── Totals tray ────────────────────────────────────────────────── */
.totals-wrap {
    width: 100%;
    border-collapse: collapse;
    margin-top: 0;
    margin-bottom: 22px;
}
.totals-wrap td.spacer     { vertical-align: top; }
.totals-wrap td.totals-col { width: 260px; vertical-align: top; padding: 0; }

/* Card: thick top grey line, thin sides and bottom */
.tot-inner {
    width: 100%;
    border-collapse: collapse;
    border-top:    3px solid #9aa0b0;   /* prominent grey top rule */
    border-left:   1px solid #d6d9e0;
    border-right:  1px solid #d6d9e0;
    border-bottom: 1px solid #d6d9e0;  /* subtle closing line */
}

/* Grand-total row — blue accent */
.tot-inner tr.total-row td {
    background: #4B5EBD;
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    padding: 9px 12px;
    border-top: 2px solid #9aa0b0;
    border-bottom: 3px double #9aa0b0;  /* double grey bottom border */
}
.tot-inner tr.total-row td.lbl { text-align: left;  color: #dde3ff; }
.tot-inner tr.total-row td.val { text-align: right; color: #fff;    }

/* Sub-rows for "all" direction */
.tot-inner tr.sub-row td {
    padding: 6px 12px;
    font-size: 11px;
    color: #555;
    background: #fafbfc;
    border-bottom: 1px solid #e4e7ec;
}
.tot-inner tr.sub-row:first-child td { padding-top: 8px; }
.tot-inner tr.sub-row td.sub-lbl     { text-align: left; }
.tot-inner tr.sub-row td.sub-val-in  { text-align: right; font-weight: 600; color: #1a7a3c; }
.tot-inner tr.sub-row td.sub-val-out { text-align: right; font-weight: 600; color: #c0392b; }

/* ── Page footer ─────────────────────────────────────────────────── */
.pg-foot {
    position: fixed;
    bottom: 0;
    left: 40px;
    right: 40px;
    border-top: 1px solid #c2c7d0;     /* grey separator line */
    padding-top: 7px;
    padding-bottom: 10px;
    background: #fff;
}
.pg-foot table { width: 100%; border-collapse: collapse; }
.pg-foot td {
    font-size: 8.5px;
    color: #999;
    vertical-align: middle;
    white-space: nowrap;
}
.pg-foot td.right { text-align: right; }
.pg-generated { font-style: italic; color: #bbb; font-size: 8px; }
.pg-num        { font-weight: 700;   color: #777; font-size: 8.5px; }

/* Prevent body content from hiding behind the fixed footer */
.footer-spacer { height: 36px; }
</style>
</head>
<body>

  {{-- ══════════════════════════════════════════════════════════════
       HEADER
       Left  : Company name + address + contact
       Right : AUDIT REPORT + Branch name + address/phone + badge
  ══════════════════════════════════════════════════════════════════ --}}
  <table class="hdr-table">
    <tr>
      <td style="vertical-align:top;">
        <div class="company-name">{{ $company->business_name ?? '—' }}</div>
        <div class="company-meta">
          <span>Address: {{ !empty($company->physical_address) ? $company->physical_address : '—' }}</span><br>
          <span>Email: {{ !empty($company->email_address) ? $company->email_address : '—' }}</span><br>
          <span>Phone: {{ !empty($company->primary_number) ? $company->primary_number : '—' }}</span>
        </div>
      </td>
      <td class="right-col" style="vertical-align:top;">
        <span class="report-title">Audit Report</span>
        <span class="branch-name-hdr">{{ $branch->name ?? '—' }}</span>
        <span class="branch-meta-hdr">
          @if(!empty($branch->address)){{ $branch->address }}<br>@endif
          @if(!empty($branch->phone)){{ $branch->phone }}@endif
        </span>
        <span class="dir-badge" style="background:{{ $accentHdr }};">{{ $dirLabel }}</span>
      </td>
    </tr>
  </table>

  <hr class="divider">

  {{-- ══════════════════════════════════════════════════════════════
       DATES STRIP — Report Date | Generated At | Prepared By
       Prepared By includes phone number in brackets if available.
  ══════════════════════════════════════════════════════════════════ --}}
  <table class="dates-table">
    <tr>
      <td>
        <span class="date-label">Report Date</span>
        <span class="date-val">{{ $formattedDate }}</span>
      </td>
      <td>
        <span class="date-label">Generated At</span>
        <span class="date-val">{{ $generatedAt }}</span>
      </td>
      <td>
        <span class="date-label">Prepared By</span>
        <span class="date-val">
          {{ $generatedBy }}@if(!empty($preparedByUser->phone)) ({{ $preparedByUser->phone }})@endif
        </span>
      </td>
    </tr>
  </table>

  {{-- ══════════════════════════════════════════════════════════════
       MAIN LOG TABLE
       Columns: # | Product Name | Code | Unit | Before | Change | After | Price | Value
       NOTE: Batch No. and Expiry Date have been removed.
  ══════════════════════════════════════════════════════════════════ --}}
  <table class="inv-table">
    <colgroup>
      <col style="width:26px;">  {{-- # --}}
      <col style="width:27%;">   {{-- Product Name --}}
      <col style="width:9%;">    {{-- Code --}}
      <col style="width:7%;">    {{-- Unit --}}
      <col style="width:10%;">   {{-- Price --}}
      <col style="width:10%;">   {{-- Before --}}
      <col style="width:11%;">   {{-- Change --}}
      <col style="width:10%;">   {{-- After --}}
      <col style="width:11%;">   {{-- Value --}}
    </colgroup>
    <thead>
      <tr>
        <th class="th-num">#</th>
        <th class="th-name">Product Name</th>
        <th class="th-center">Code</th>
        <th class="th-center">Unit</th>
        <th class="th-right">Price</th>
        <th class="th-right">Before</th>
        <th class="th-right">Change</th>
        <th class="th-right">After</th>
        <th class="th-right">Value</th>
      </tr>
    </thead>
    <tbody>
      @forelse($logs as $i => $log)
        @php
          $change    = (float) $log->stock_change;
          $absChange = abs($change);
          $price     = (float) ($log->product_sell_price ?? 0);
          $rowValue  = $absChange * $price;

          if ($change > 0)     { $changeStr = '+' . number_format($absChange, 2); $changeTd = 'change-in'; }
          elseif ($change < 0) { $changeStr = '−' . number_format($absChange, 2); $changeTd = 'change-out'; }
          else                 { $changeStr = number_format($absChange, 2);        $changeTd = 'change-zero'; }
        @endphp
        <tr>
          <td class="td-num">{{ $i + 1 }}</td>
          <td class="td-name">{{ $log->product_name ?? '—' }}</td>
          <td class="td-mono">{{ $log->product_code ?? '—' }}</td>
          <td class="td-c">{{ $log->product_unit ?? '—' }}</td>
          <td class="td-r">{{ number_format($price, 2) }}</td>
          <td class="td-r">{{ number_format((float)$log->stock_before, 2) }}</td>
          <td class="{{ $changeTd }}">{{ $changeStr }}</td>
          <td class="td-r">{{ number_format((float)$log->stock_after, 2) }}</td>
          <td class="td-r">{{ number_format($rowValue, 2) }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="9" style="padding:20px;text-align:center;color:#aaa;font-style:italic;">
            No records found for this date and filter.
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>

  {{-- ══════════════════════════════════════════════════════════════
       TOTALS TRAY
       • Thick top border  (3px #4B5EBD) — prominent accent line
       • Thin bottom border (1px #c8cef2) — subtle closing line
       • "all" direction: shows Value Added / Value Removed sub-rows
         then a Net Value grand-total row
       • Single direction : shows only the Total Value grand-total row
  ══════════════════════════════════════════════════════════════════ --}}
  <table class="totals-wrap">
    <tr>
      <td class="spacer">&nbsp;</td>
      <td class="totals-col">
        <table class="tot-inner">

          @if($direction === 'all')

            {{-- Sub-rows for split view --}}
            <tr class="sub-row">
              <td class="sub-lbl">Value Added</td>
              <td class="sub-val-in">{{ number_format($summaryIn, 2) }}</td>
            </tr>
            <tr class="sub-row">
              <td class="sub-lbl">Value Removed</td>
              <td class="sub-val-out">{{ number_format($summaryOut, 2) }}</td>
            </tr>

            {{-- Grand-total row --}}
            @php $net = $summaryIn - $summaryOut; @endphp
            <tr class="total-row">
              <td class="lbl">Net Value</td>
              <td class="val">{{ $net >= 0 ? '+' : '−' }}&nbsp;{{ number_format(abs($net), 2) }}</td>
            </tr>

          @else

            {{-- Single-direction: one grand-total row only --}}
            <tr class="total-row">
              <td class="lbl">Total Value</td>
              <td class="val">{{ number_format($totalValue, 2) }}</td>
            </tr>

          @endif

        </table>
      </td>
    </tr>
  </table>

  {{-- Spacer prevents content from hiding behind the fixed footer --}}
  <div class="footer-spacer"></div>

  {{-- ══════════════════════════════════════════════════════════════
       PAGE FOOTER — position:fixed → always at page bottom
       Left : branch · direction · date
       Right: italic computer-generated note · bold page number
  ══════════════════════════════════════════════════════════════════ --}}
  <div class="pg-foot">
    <table>
      <tr>
        <td>{{ $branch->name ?? '' }} &nbsp;·&nbsp; {{ $dirLabel }} &nbsp;·&nbsp; {{ $formattedDate }}</td>
        <td class="right">
          <span class="pg-generated">This document is computer-generated and does not require a physical signature.</span>
          &nbsp;&nbsp;<span class="pg-num">Page 1 of 1</span>
        </td>
      </tr>
    </table>
  </div>

</body>
</html>