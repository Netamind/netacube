{{--
  ╔══════════════════════════════════════════════════════════════════╗
  ║  Branch Delivery Notes PDF                                       ║
  ║  Path  : resources/views/operations/retail/deliverynotespdf.blade.php ║
  ║  Style : Invoice-style template, Playfair Display + DM Sans +     ║
  ║          DM Mono. Header, Deliver To / Prepared By, line-items    ║
  ║          table, Notes/Signature footer, and the page footer are   ║
  ║          all <table>-based (no flexbox) so DomPDF renders every   ║
  ║          column reliably side-by-side, every time.                ║
  ║                                                                    ║
  ║  Page  : Fixed A4 canvas via @page. The footer (Notes/Signature +  ║
  ║          page-footer bar) is a position:fixed block anchored into  ║
  ║          a reserved bottom margin on every page — DomPDF repeats   ║
  ║          position:fixed content on each page it renders, which is  ║
  ║          the only way to get the same footer on page 1, 2, 3...    ║
  ║          (the old "sticky last table row" trick only ever drew     ║
  ║          once, at the true end of the document).                  ║
  ║                                                                    ║
  ║  IMPORTANT: @page margin uses longhand margin-top/right/bottom/    ║
  ║          left properties, NOT the shorthand "margin: 0 0 150px 0". ║
  ║          DomPDF's CSS parser can silently fail to apply multi-     ║
  ║          value @page margin shorthand, which — if it happens —     ║
  ║          leaves no reserved space and pushes the fixed footer's    ║
  ║          negative bottom offset completely off the page (footer    ║
  ║          appears to vanish). Longhand sidesteps that parser bug.   ║
  ║                                                                    ║
  ║  FOOTER POSITIONING: .footer-fixed uses bottom: 0 (NOT a negative   ║
  ║          value) and NO explicit height. DomPDF positions           ║
  ║          `position:fixed` elements relative to the full physical   ║
  ║          page box, not the content box. bottom: 0 glues the         ║
  ║          footer's own bottom edge flush with the page's true        ║
  ║          bottom edge on every page; leaving height unset lets the   ║
  ║          box size itself to its actual content (Notes/Signature     ║
  ║          row + page-footer bar) instead of stretching to fill the   ║
  ║          full 150px @page margin-bottom — which is what caused a    ║
  ║          strip of empty white space below the visible footer        ║
  ║          content. A negative bottom value (e.g. bottom: -150px)     ║
  ║          would push the footer past the bottom edge entirely and    ║
  ║          off the canvas — never use that.                           ║
  ║                                                                    ║
  ║  PAGE NUMBERS: {PAGE_NUM} / {PAGE_COUNT} below are plain literal    ║
  ║          text, not Blade syntax. Blade only compiles double-brace   ║
  ║          {{ }} expressions; single braces pass straight through      ║
  ║          untouched into the rendered HTML, where DomPDF's own PDF    ║
  ║          renderer scans for and substitutes them per page, after     ║
  ║          Blade has already finished. Do not wrap these in {{ }} —    ║
  ║          Blade would try to evaluate PAGE_NUM as a PHP variable      ║
  ║          and throw an error since it isn't passed from the           ║
  ║          controller. This is the simplest, working approach — no     ║
  ║          inline-PHP callback or isPhpEnabled config needed.          ║
  ╚══════════════════════════════════════════════════════════════════╝

  Variables passed from controller:
    $branch, $companyProfile, $preparedByUser,
    $deliveryNotes (Collection), $deliveryDate, $displayDate,
    $formattedDate, $grandTotalCost, $grandTotalValue,
    $submittedCount, $pendingCount, $totalQty,
    $generatedAt, $generatedBy

  Columns shown: # · Product Name (code shown inline, e.g. "Paracetamol Tabs [PARA-001]")
                 · Unit · Quantity · Price · Total
  Grand Total is the table's own final row (a tfoot row, attached
  directly under the last line item): the first 4 columns collapse
  into one empty merged cell, and the label + value sit shaded in the
  Price/Total columns only — gray top border, blue bottom border, and
  no divider between the label and the value so they read as one
  continuous block.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delivery Note - {{ $branch->name ?? '' }} - {{ $displayDate }}</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }

  /* ── PAGE: fixed A4. Bottom margin is reserved on every single page
     DomPDF renders so the fixed-position footer has a slot to repeat
     into on each one without overlapping the table content. Longhand
     margin-* properties used deliberately — see header note above. ── */
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
    max-width: 1010px; /* slightly wider sheet to give the table more room */
    margin: 0 auto;
    background: #fff;
  }

  /* ── REPEATING FOOTER ──
     position:fixed, positioned relative to the full page box (not the
     content/margin box) in DomPDF. bottom: 0 glues the footer's own
     bottom edge flush with the page's true bottom edge on every page.
     No explicit height: the box sizes itself to its actual content
     (Notes/Signature row + page-footer bar) instead of stretching to
     fill the full 150px @page margin-bottom — that mismatch was what
     left a strip of empty white space below the visible footer.
     DomPDF draws this block once per rendered page automatically —
     no PHP/controller changes needed. Do NOT use a negative bottom
     value here — that pushes the block past the page's bottom edge
     and off the canvas entirely (the footer silently stops rendering). */
  .footer-fixed {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    /* No explicit height here on purpose: the box sizes itself to its
       actual content (Notes/Signature row + page-footer bar). bottom: 0
       then glues that content's own bottom edge flush to the page's
       true bottom edge on every page DomPDF renders — no leftover
       empty strip below it, regardless of exact content height. */
  }
  .footer-fixed .footer-inner {
    max-width: 1010px;
    margin: 0 auto;
    background: #fff;
  }

  /* ── HEADER — grey band; brand accent lives in the divider bar below ── */
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

  /* ── DIVIDER BAR — plain gray line between header and Deliver To / Prepared By ── */
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
    color: #4B5EBD; /* brand accent */
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

  /* ── DELIVER TO + PREPARED BY (side by side, table-based for DomPDF) ── */
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
  .tv-amount {
    color: #4B5EBD;
    font-weight: 700;
    text-decoration: underline;
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

  /* ── TABLE ── */
  .table-wrap {
    padding: 0 24px; /* tighter side padding so the table itself is wider */
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
    padding: 8px 8px; /* slightly more breathing room */
    font-size: 9px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #111;
    text-align: center;
    border-top: 1.5px solid #d0d0d0;
    border-bottom: 1.5px solid #d0d0d0;
  }

  /* 6 visible columns now: # · Product Name (incl. code) · Unit · Quantity · Price · Total */
  table.t thead th:nth-child(1) { text-align: left; width: 5%;  padding-left: 5px; }
  table.t thead th:nth-child(2) { text-align: left; width: 41%; }
  table.t thead th:nth-child(3) { width: 13%; }
  table.t thead th:nth-child(4) { width: 14%; }
  table.t thead th:nth-child(5) { width: 13%; }
  table.t thead th:nth-child(6) { width: 14%; }

  table.t tbody tr { border-bottom: 1px solid #e4e4e4; }
  table.t tbody tr:nth-child(even) { background: #fafafa; }

  table.t tbody td {
    padding: 7px 8px; /* slightly increased row padding */
    font-size: 10.5px;
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
    font-size: 10.5px;
  }
  table.t tbody td:nth-child(2) .prod-code {
    font-family: 'DM Mono', monospace;
    font-size: 9px;
    color: #555;
    font-weight: 600;
    margin-left: 4px;
  }
  table.t tbody td.amt {
    font-weight: 400;
    color: #111;
  }
  table.t tbody td.total {
    font-weight: 600;
    color: #111;
  }

  /* ── GRAND TOTAL ROW — the table's own final row, attached directly
     under the last line item. The first 4 columns collapse into one
     empty, unstyled cell (.gt-empty); the shaded background sits only
     under the Price/Total columns (.gt-label / .gt-value), with a
     gray top border and blue bottom border closing the row — label
     and value flow together with no divider between them. ── */
  table.t tfoot td {
    padding: 9px 8px;
    font-size: 11px;
  }
  table.t tfoot td.gt-empty {
    background: transparent;
  }
  table.t tfoot td.gt-label {
    text-align: right;
    font-weight: 700;
    color: #111;
    letter-spacing: 0.3px;
    background: #f5f5f5;
    border-top: 1.5px solid #9a9a9a;
  }
  table.t tfoot td.gt-value {
    text-align: center;
    font-weight: 800;
    color: #dc2626;
    font-size: 13px;
    background: #f5f5f5;
    border-top: 1.5px solid #9a9a9a;
  }
  /* The blue bottom border is its own single bar cell (same trick as
     .hdr-divider) rather than separate border-bottom rules on the two
     cells above — a per-cell border can render as two abutting
     segments in DomPDF; one continuous background bar guarantees a
     single unbroken line under both the label and the value. */
  table.t tfoot td.gt-bottom-bar {
    height: 2px;
    padding: 0;
    line-height: 0;
    font-size: 0;
    background: #4B5EBD;
  }
  table.t tfoot td.gt-bottom-empty {
    height: 2px;
    padding: 0;
    line-height: 0;
    font-size: 0;
    background: transparent;
  }

  /* ── FOOTER ── */
  /* No top border here — this is the stray horizontal line that used to
     sit directly under the table; removed so the table's own bottom edge
     (the Grand Total row) is the last line before the Notes/Signature area. */
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
  .terms-chip {
    display: inline-block;
    border: 1.5px solid #4B5EBD;
    color: #2c3a8c;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 10px;
    border-radius: 2px;
    margin-bottom: 6px;
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

  /* ── PAGE FOOTER ── */
  table.pg-foot {
    width: 100%;
    border-collapse: collapse;
    border-top: 2px solid #4B5EBD;
    background: #e3e5ea; /* deepened from the original near-white #fafafa so
                             the footer band reads clearly instead of nearly
                             disappearing into the white page */
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
        <div class="co-name">{{ $companyProfile->business_name ?? $companyProfile->name ?? '—' }}</div>
        <div class="co-meta">
          @if(!empty($companyProfile->physical_address)){{ $companyProfile->physical_address }}<br>@endif
          @if(!empty($companyProfile->email_address)){{ $companyProfile->email_address }} &nbsp;·&nbsp; @endif
          @if(!empty($companyProfile->primary_number)){{ $companyProfile->primary_number }}@endif
        </div>
      </td>
      <td class="hdr-right">
        <div class="inv-word">DELIVERYNOTE</div>
        <div class="inv-dates">
          <div class="d-item">
            <label>Delivery Date</label>
            <span>{{ $displayDate }}</span>
          </div>
          <div class="d-item">
            <label>Currency</label>
            <span>MWK</span>
          </div>
        </div>
      </td>
    </tr>
  </table>

  <!-- Slim gray divider -->
  <table class="hdr-divider"><tr><td></td></tr></table>

  <!-- DELIVER TO + PREPARED BY (side by side) -->
  <table class="info-row">
    <tr>
      <td class="info-cell">
        <h5>Deliver To</h5>
        <div class="cli-name">{{ $branch->name ?? '—' }}</div>
        <p>
          @if(!empty($branch->address)){{ $branch->address }}<br>@endif
          @if(!empty($branch->phone))Tel: {{ $branch->phone }}<br>@endif
          Total Value: <span class="tv-amount">{{ number_format($grandTotalValue, 2) }}</span>
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
          <th>Quantity</th>
          <th>Price</th>
          <th>Total</th>
        </tr>
      </thead>
      <tbody>
        @forelse($deliveryNotes as $i => $note)
          @php
            $qty     = (float) $note->quantity;
            $sellP   = (float) ($note->selling_price ?? 0);
            $sellVal = $qty * $sellP;
          @endphp
          <tr>
            <td>{{ $i + 1 }}</td>
            <td>
              {{ $note->product_name ?? '—' }}
              @if(!empty($note->product_code))
                <span class="prod-code">[{{ $note->product_code }}]</span>
              @endif
            </td>
            <td>{{ $note->product_unit ?? '—' }}</td>
            <td>{{ number_format($qty, 2) }}</td>
            <td class="amt">{{ number_format($sellP, 2) }}</td>
            <td class="total">{{ number_format($sellVal, 2) }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="6" style="text-align:center;padding:22px;color:#94a3b8;font-style:italic;">
              No delivery notes found for this branch on {{ $displayDate }}.
            </td>
          </tr>
        @endforelse
      </tbody>
      <tfoot>
        <tr>
          <td colspan="4" class="gt-empty"></td>
          <td class="gt-label">Grand Total</td>
          <td class="gt-value">{{ number_format($grandTotalValue, 2) }}</td>
        </tr>
        <tr>
          <td colspan="4" class="gt-bottom-empty"></td>
          <td colspan="2" class="gt-bottom-bar"></td>
        </tr>
      </tfoot>
    </table>
  </div>

</div>

<!-- REPEATING FOOTER: position:fixed, drawn by DomPDF on every page into
     the 150px bottom margin reserved by @page above. bottom: 0 keeps it
     flush with the page's true bottom edge — see CSS comment above. -->
<div class="footer-fixed">
  <div class="footer-inner">

    <!-- FOOTER: Notes LEFT, Signature RIGHT (closer together) -->
    <table class="foot-row">
      <tr>
        <td class="foot-left">
          <h5>Notes</h5>
          <p>This document is computer-generated and serves as proof of delivery for the items listed above.</p>
        </td>
        <td class="foot-right">
          <div class="sig-block">Received By (Branch)</div>
        </td>
      </tr>
    </table>

    <!-- PAGE FOOTER -->
    <table class="pg-foot">
      <tr>
        <td>{{ $companyProfile->business_name ?? $companyProfile->name ?? '' }} &nbsp;·&nbsp; Delivery Note</td>
        <td class="pg-right">
          <span>Generated At: {{ $generatedAt }}</span>
          <!--<span class="pg-num">Page {PAGE_NUM} of {PAGE_COUNT}</span>-->
        </td>
      </tr>
    </table>

  </div>
</div>

</body>
</html>