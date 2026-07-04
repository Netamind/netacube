<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Audit Report - {{ $branch->name ?? '' }} - {{ $formattedDate }}</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }

  @page {
    size: A4;
    margin-top: 0;
    margin-right: 0;
    margin-bottom: 150px;
    margin-left: 0;
  }

  html, body {
    font-family: 'DM Sans', sans-serif;
    font-size: 11px;
    color: #111;
  }

  body {
    background: #fff;
    padding: 0;
  }

  .print-btn {
    display: block;
    margin: 0 auto 10px auto;
    max-width: 1010px;
    text-align: right;
  }
  .print-btn button {
    background: #111;
    color: #fff;
    border: none;
    padding: 8px 22px;
    font-family: 'DM Sans', sans-serif;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 1px;
    text-transform: uppercase;
    cursor: pointer;
    border-radius: 2px;
  }
  .print-btn button:hover { background: #444; }

  .wrap {
    max-width: 1010px;
    margin: 0 auto;
    background: #fff;
  }

  .footer-fixed {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
  }
  .footer-fixed .footer-inner {
    max-width: 1010px;
    margin: 0 auto;
    background: #fff;
  }

  table.hdr-t {
    width: 100%;
    border-collapse: collapse;
    background: #f5f5f6;
    border-bottom: 1px solid #e4e4e4;
  }
  table.hdr-t td {
    padding: 22px 36px 18px 36px;
    vertical-align: top;
  }
  table.hdr-t td.hdr-right {
    text-align: right;
    vertical-align: top;
  }

  table.hdr-divider {
    width: 100%;
    border-collapse: collapse;
  }
  table.hdr-divider td {
    height: 1px;
    padding: 0;
    line-height: 0;
    font-size: 0;
    background: #d8d8d8;
  }

  .co-name {
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    font-weight: 700;
    color: #111;
    letter-spacing: 0.3px;
    margin-top: 0;
    margin-bottom: 5px;
    line-height: 1;
  }
  .co-meta {
    font-size: 10.5px;
    color: #666;
    line-height: 1.7;
    font-weight: 400;
  }

  .inv-word {
    font-family: 'Playfair Display', serif;
    font-size: 18px;
    font-weight: 700;
    color: #4B5EBD;
    letter-spacing: 2px;
    text-transform: uppercase;
    line-height: 1;
    margin-top: 0;
    margin-bottom: 12px;
  }
  .inv-ref {
    font-size: 11px;
    color: #555;
    font-weight: 400;
  }
  .inv-ref strong { color: #111; font-weight: 700; }

  .inv-dates {
    margin-top: 2px;
  }
  .d-item {
    margin-bottom: 5px;
  }
  .d-item label {
    font-size: 8px;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: #999;
    font-weight: 700;
    margin-right: 10px;
  }
  .d-item span {
    font-size: 11.5px;
    font-weight: 700;
    color: #111;
  }

  .dir-badge {
    display: inline-block;
    margin-top: 4px;
    padding: 2px 10px;
    border-radius: 20px;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #fff;
  }

  table.info-row {
    width: 100%;
    border-collapse: collapse;
    border-bottom: 1.5px solid #d0d0d0;
  }
  table.info-row td.info-cell {
    width: 50%;
    padding: 18px 36px 16px 36px;
    vertical-align: top;
  }
  table.info-row td.info-cell + td.info-cell {
    border-left: 1.5px solid #e6e6e6;
  }
  .info-cell h5 {
    font-size: 8.5px;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #4B5EBD;
    font-weight: 700;
    margin-bottom: 10px;
    padding-bottom: 5px;
    border-bottom: 1.5px solid #4B5EBD;
    display: inline-block;
  }
  .cli-name {
    font-size: 14px;
    font-weight: 700;
    color: #111;
    margin-bottom: 4px;
    margin-top: 2px;
  }
  .info-cell p {
    font-size: 11px;
    color: #333;
    line-height: 1.7;
    font-weight: 400;
  }
  .bank-t {
    width: 100%;
    font-size: 11px;
    border-collapse: collapse;
    margin-top: 2px;
  }
  .bank-t tr { border-bottom: 1px solid #f1f1f1; }
  .bank-t tr:last-child { border-bottom: none; }
  .bank-t td { padding: 6px 0; vertical-align: top; }
  .bank-t td:first-child {
    color: #9a9a9a;
    width: 95px;
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    font-weight: 700;
  }
  .bank-t td:last-child {
    font-weight: 600;
    color: #111;
    font-size: 11.5px;
  }

  .table-wrap {
    padding: 0 24px;
    margin-top: 28px;
  }

  table.t {
    width: 100%;
    border-collapse: collapse;
    margin: 10px 0 0 0;
    table-layout: fixed;
  }

  table.t thead tr { background: #f0f0f0; }
  table.t thead th {
    padding: 8px 6px;
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #111;
    text-align: center;
    border-top: 1.5px solid #d0d0d0;
    border-bottom: 1.5px solid #d0d0d0;
  }

  /* 8 columns: #, Product, Unit, Price, Qty Before, Qty After, Diff, Value */
  table.t thead th:nth-child(1) { text-align: left; width: 4%;  padding-left: 5px; }
  table.t thead th:nth-child(2) { text-align: left; width: 25%; }
  table.t thead th:nth-child(3) { width: 9%;  }
  table.t thead th:nth-child(4) { width: 13%; }
  table.t thead th:nth-child(5) { width: 13%; }
  table.t thead th:nth-child(6) { width: 13%; }
  table.t thead th:nth-child(7) { width: 11%; }
  table.t thead th:nth-child(8) { width: 12%; }

  table.t tbody tr { border-bottom: 1px solid #e4e4e4; }
  table.t tbody tr:nth-child(even) { background: #fafafa; }
  table.t tbody tr:last-child { border-bottom: 2px solid #9a9a9a; }

  table.t tbody td {
    padding: 6px 6px;
    font-size: 12px;
    color: #111;
    text-align: center;
    vertical-align: middle;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-weight: 400;
  }

  table.t tbody td:nth-child(1) {
    text-align: left;
    padding-left: 5px;
    color: #555;
  }
  table.t tbody td:nth-child(2) {
    text-align: left;
    font-weight: 400;
    color: #111;
    font-size: 12px;
  }
  table.t tbody td.amt {
    font-weight: 400;
    color: #111;
  }
  table.t tbody td.total {
    font-weight: 600;
    color: #111;
  }

  /* Allow long numeric values in body cells to break instead of being
     clipped/ellipsised when figures get very large. */
  table.t tbody td.amt,
  table.t tbody td.total {
    white-space: normal;
    word-break: break-word;
    overflow-wrap: anywhere;
  }

  table.t tbody td.change-in   { color: #1a7a3c; font-weight: 700; }
  table.t tbody td.change-out  { color: #c0392b; font-weight: 700; }
  table.t tbody td.change-zero { color: #888;    font-weight: 600; }

  table.t tfoot td {
    padding: 8px 8px;
    font-size: 10.5px;
  }
  table.t tfoot td.gt-empty {
    background: transparent;
  }
  table.t tfoot tr.sub-row td.gt-label {
    text-align: right;
    font-weight: 600;
    color: #444;
    background: #f5f5f5;
    border-top: 1px solid #e4e4e4;
  }
  table.t tfoot tr.sub-row td.gt-value {
    text-align: center;
    font-weight: 700;
    font-size: 11px;
    background: #f5f5f5;
    border-top: 1px solid #e4e4e4;
  }
  table.t tfoot tr.sub-row td.gt-value.sub-val-in  { color: #1a7a3c; }
  table.t tfoot tr.sub-row td.gt-value.sub-val-out { color: #c0392b; }
  table.t tfoot tr.total-row td.gt-label {
    text-align: right;
    font-weight: 700;
    color: #111;
    letter-spacing: 0.3px;
    background: #f5f5f5;
    border-top: 1.5px solid #9a9a9a;
    border-bottom: 2px solid #4B5EBD;
  }
  table.t tfoot tr.total-row td.gt-value {
    text-align: center;
    font-weight: 800;
    color: #dc2626;
    font-size: 12px; /* slightly reduced so large figures fit comfortably */
    background: #f5f5f5;
    border-top: 1.5px solid #9a9a9a;
    border-bottom: 2px solid #4B5EBD;
  }

  /* ── Totals overflow fix ──────────────────────────────────────────────
     The value cell now spans the last TWO table columns (≈20% width)
     instead of just one (≈12% width), giving large figures (e.g.
     50,000,000.00) enough room. word-break/overflow-wrap act as a
     safety net for any figure that still doesn't fit. */
  table.t tfoot td.gt-label,
  table.t tfoot td.gt-value {
    white-space: normal;
    word-break: break-word;
    overflow-wrap: anywhere;
    line-height: 1.3;
  }

  table.foot-row {
    width: 100%;
    border-collapse: collapse;
  }
  table.foot-row td {
    padding: 16px 36px 18px 36px;
    vertical-align: bottom;
  }
  table.foot-row td.foot-left {
    width: auto;
  }
  table.foot-row td.foot-right {
    width: 220px;
    padding-left: 40px;
    text-align: center;
  }

  .foot-left h5 {
    font-size: 8.5px;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #4B5EBD;
    font-weight: 700;
    margin-bottom: 6px;
    padding-bottom: 4px;
    border-bottom: 1.5px solid #ccc;
    display: inline-block;
  }
  .foot-left p {
    font-size: 10px;
    color: #555;
    line-height: 1.7;
    max-width: 280px;
    font-weight: 400;
  }

  .sig-block {
    margin-top: 30px;
    border-top: 1.5px solid #aaa;
    width: 200px;
    padding-top: 6px;
    font-size: 9.5px;
    color: #999;
    letter-spacing: 0.5px;
    text-align: center;
    display: inline-block;
  }

  table.pg-foot {
    width: 100%;
    border-collapse: collapse;
    border-top: 2px solid #4B5EBD;
    background: #e3e5ea;
  }
  table.pg-foot td {
    padding: 8px 36px;
    font-size: 9px;
    color: #777;
    vertical-align: middle;
  }
  table.pg-foot td.pg-right {
    text-align: right;
  }
  table.pg-foot td.pg-right span.pg-num {
    margin-left: 24px;
  }

  @media print {
    .print-btn { display: none; }
  }
</style>
</head>
<body>

<div class="wrap">

  <!-- HEADER -->
  <table class="hdr-t">
    <tr>
      <td>
        <div class="co-name">{{ $company->business_name ?? '—' }}</div>
        <div class="co-meta">
          @if(!empty($company->physical_address)){{ $company->physical_address }}<br>@endif
          @if(!empty($company->email_address)){{ $company->email_address }} &nbsp;·&nbsp; @endif
          @if(!empty($company->primary_number)){{ $company->primary_number }}@endif
        </div>
      </td>
      <td class="hdr-right">
        <div class="inv-word">Audit Report</div>
        <div class="inv-dates">
          <div class="d-item">
            <label>Report Date</label>
            <span>{{ $formattedDate }}</span>
          </div>
          <div class="d-item">
            <label>Currency</label>
            <span>MWK</span>
          </div>
        </div>
        <span class="dir-badge" style="background:{{ $accentHdr }};">{{ $dirLabel }}</span>
      </td>
    </tr>
  </table>

  <!-- Slim gray divider -->
  <table class="hdr-divider"><tr><td></td></tr></table>

  <!-- BRANCH INFO + PREPARED BY (side by side) -->
  <table class="info-row">
    <tr>
      <td class="info-cell">
        <h5>Branch Info</h5>
        <div class="cli-name">{{ $branch->name ?? '—' }}</div>
        <p>
          @if(!empty($branch->address)){{ $branch->address }}<br>@endif
          @if(!empty($branch->phone))Tel: {{ $branch->phone }}@endif
        </p>
      </td>
      <td class="info-cell">
        <h5>Prepared By</h5>
        <table class="bank-t">
          <tr><td>Name</td><td>{{ $generatedBy }}</td></tr>
          @if(!empty($preparedByUser->position))
          <tr><td>Position</td><td>{{ $preparedByUser->position }}{{ !empty($preparedByUser->department) ? ' · '.$preparedByUser->department : '' }}</td></tr>
          @endif
          <tr><td>Contact</td><td>{{ !empty($preparedByUser->phone) ? $preparedByUser->phone : '—' }}</td></tr>
          @if(!empty($preparedByUser->email))
          <tr><td>Email</td><td>{{ $preparedByUser->email }}</td></tr>
          @endif
        </table>
      </td>
    </tr>
  </table>

  <!-- TABLE -->
  <div class="table-wrap">
    <table class="t">
      <thead>
        <tr>
          <th>#</th>
          <th>Product Name</th>
          <th>Unit</th>
          <th>Price</th>
          <th>Qty Before</th>
          <th>Qty After</th>
          <th>Diff</th>
          <th>Value</th>
        </tr>
      </thead>
      <tbody>
        @forelse($logs as $i => $log)
          @php
            $change    = (float) $log->stock_change;
            $absChange = abs($change);
            // FIX: this table's price column is `selling_price`, not
            // `product_sell_price` — the old key never existed on the row,
            // so it silently fell back to 0 every time.
            $price     = (float) ($log->selling_price ?? 0);
            $rowValue  = $absChange * $price;

            if ($change > 0)     { $changeStr = '+' . number_format($absChange, 2); $changeTd = 'change-in'; }
            elseif ($change < 0) { $changeStr = '-' . number_format($absChange, 2); $changeTd = 'change-out'; }
            else                 { $changeStr = number_format($absChange, 2);        $changeTd = 'change-zero'; }
          @endphp
          <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $log->product_name ?? '—' }}</td>
            <td>{{ $log->product_unit ?? '—' }}</td>
            <td class="amt">{{ number_format($price, 2) }}</td>
            <td class="amt">{{ number_format((float)$log->stock_before, 2) }}</td>
            <td class="amt">{{ number_format((float)$log->stock_after, 2) }}</td>
            <td class="{{ $changeTd }}">{{ $changeStr }}</td>
            <td class="total">{{ number_format($rowValue, 2) }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="8" style="text-align:center;padding:22px;color:#94a3b8;font-style:italic;">
              No records found for this date and filter.
            </td>
          </tr>
        @endforelse
      </tbody>
      <tfoot>
        @if($direction === 'all')

          {{-- Sub-rows for split view.
               Value cell spans the last TWO columns (Diff + Value, ≈23%
               width) instead of one, so large figures don't overflow. --}}
          <tr class="sub-row">
            <td colspan="5" class="gt-empty"></td>
            <td colspan="1" class="gt-label">Value Added</td>
            <td colspan="2" class="gt-value sub-val-in">{{ number_format($summaryIn, 2) }}</td>
          </tr>
          <tr class="sub-row">
            <td colspan="5" class="gt-empty"></td>
            <td colspan="1" class="gt-label">Value Removed</td>
            <td colspan="2" class="gt-value sub-val-out">{{ number_format($summaryOut, 2) }}</td>
          </tr>

          {{-- Grand-total row --}}
          @php $net = $summaryIn - $summaryOut; @endphp
          <tr class="total-row">
            <td colspan="5" class="gt-empty"></td>
            <td colspan="1" class="gt-label">Net Value</td>
            <td colspan="2" class="gt-value">{{ $net >= 0 ? '+' : '-' }}&nbsp;{{ number_format(abs($net), 2) }}</td>
          </tr>

        @else

          {{-- Single-direction: one grand-total row only --}}
          <tr class="total-row">
            <td colspan="5" class="gt-empty"></td>
            <td colspan="1" class="gt-label">Total Value</td>
            <td colspan="2" class="gt-value">{{ number_format($totalValue, 2) }}</td>
          </tr>

        @endif
      </tfoot>
    </table>
  </div>

</div>

<!-- REPEATING FOOTER -->
<div class="footer-fixed">
  <div class="footer-inner">

    <!-- FOOTER -->
    <table class="foot-row">
      <tr>
        <td class="foot-left">
          <h5>Notes</h5>
          <p>This document is computer-generated and does not require a physical signature.</p>
        </td>
        <td class="foot-right">
          <div class="sig-block">Reviewed By</div>
        </td>
      </tr>
    </table>

    <!-- PAGE FOOTER -->
    <table class="pg-foot">
      <tr>
        <td>{{ $branch->name ?? '' }} &nbsp;·&nbsp; {{ $dirLabel }} &nbsp;·&nbsp; Audit Report</td>
        <td class="pg-right">
          <span>Generated At: {{ $generatedAt }}</span>
        </td>
      </tr>
    </table>

  </div>
</div>

</body>
</html>