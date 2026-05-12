{{--
  ╔══════════════════════════════════════════════════════════════════╗
  ║  Branch Delivery Notes PDF                                       ║
  ║  Path  : resources/views/operations/retail/deliverynotespdf.blade.php ║
  ║  Engine: DomPDF · A4 Landscape                                   ║
  ║  Brand : #4B5EBD indigo · DejaVu Sans                            ║
  ╚══════════════════════════════════════════════════════════════════╝

  Variables passed from controller:
    $branch, $companyProfile, $preparedByUser,
    $deliveryNotes (Collection), $deliveryDate, $displayDate,
    $formattedDate, $grandTotalCost, $grandTotalValue,
    $submittedCount, $pendingCount, $totalQty,
    $generatedAt, $generatedBy
--}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Delivery Notes — {{ $branch->name ?? '' }} — {{ $displayDate }}</title>
<style>
/* ════════════════════════════════════════════════════════════════════
   DomPDF-safe CSS
   No flexbox · no grid · no CSS variables · no gradients
   All multi-column layouts use <table>
════════════════════════════════════════════════════════════════════ */
* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 11px;
    color: #1e293b;
    background: #fff;
    padding: 30px 38px 0 38px;
}

/* ── HEADER ────────────────────────────────────────────────────────── */
.hdr { width: 100%; border-collapse: collapse; }
.hdr td { vertical-align: top; padding: 0; }

.co-name { font-size: 20px; font-weight: 700; color: #4B5EBD;
           line-height: 1.1; display: block; margin-bottom: 3px; }
.co-meta { font-size: 9px; color: #64748b; line-height: 1.85; }

.doc-title { font-size: 20px; font-weight: 800; color: #4B5EBD;
             letter-spacing: 2px; text-transform: uppercase;
             text-align: right; display: block; margin-bottom: 3px; }
.br-name   { font-size: 13px; font-weight: 700; color: #1e293b;
             text-align: right; display: block; margin-bottom: 2px; }
.br-meta   { font-size: 9px; color: #64748b; line-height: 1.85;
             text-align: right; display: block; margin-bottom: 5px; }
.doc-badge { display: inline-block; padding: 3px 12px; border-radius: 20px;
             font-size: 8.5px; font-weight: 700; text-transform: uppercase;
             letter-spacing: 0.5px; background: #4B5EBD; color: #fff; }

/* ── DIVIDER ───────────────────────────────────────────────────────── */
.div-blue { width: 100%; border: none; border-top: 2.5px solid #4B5EBD;
            margin: 11px 0 13px 0; }

/* ── META STRIP ────────────────────────────────────────────────────── */
.meta { width: 100%; border-collapse: collapse; margin-bottom: 13px; }
.meta td { padding: 0 24px 0 0; vertical-align: top; }
.meta td:last-child { padding-right: 0; }
.ml { font-size: 8px; text-transform: uppercase; letter-spacing: 0.9px;
      color: #94a3b8; display: block; margin-bottom: 2px; font-weight: 700; }
.mv { font-size: 11px; font-weight: 700; color: #1e293b; display: block; }
.ms { font-size: 9px; color: #64748b; display: block; margin-top: 1px; }

/* ── KPI BAR ───────────────────────────────────────────────────────── */
.kpi { width: 100%; border-collapse: collapse; margin-bottom: 13px;
       border: 1px solid #dde3f5; }
.kpi td { width: 25%; padding: 9px 13px; vertical-align: middle;
          border-right: 1px solid #dde3f5; background: #f4f6ff; }
.kpi td:last-child { border-right: none; }
.kl { font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.7px;
      color: #94a3b8; display: block; margin-bottom: 3px; font-weight: 700; }
.kv        { font-size: 14px; font-weight: 800; display: block; line-height: 1; }
.kv-accent { color: #4B5EBD; }
.kv-green  { color: #059669; }
.kv-slate  { color: #475569; }

/* ── BADGE ROW (mixed status) ──────────────────────────────────────── */
.status-row { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
.status-row td { font-size: 9.5px; color: #64748b; padding: 0; }
.bdg { display: inline-block; padding: 2px 9px; border-radius: 5px;
       font-size: 8.5px; font-weight: 700; letter-spacing: 0.3px; border: 1px solid; }
.bdg-sub  { background: #dcfce7; color: #15803d; border-color: #86efac; }
.bdg-pend { background: #fef9c3; color: #854d0e; border-color: #fde68a; }

/* ── MAIN TABLE ────────────────────────────────────────────────────── */
table.dn { width: 100%; border-collapse: collapse; table-layout: fixed; }

table.dn thead tr { background: #4B5EBD; }
table.dn thead th {
    padding: 7px 6px; font-size: 7.5px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.5px;
    color: #fff; vertical-align: middle;
}
table.dn thead th.thl { text-align: left;  padding-left: 8px; }
table.dn thead th.thc { text-align: center; }
table.dn thead th.thr { text-align: right; padding-right: 9px; }

table.dn tbody tr.odd  { background: #ffffff; }
table.dn tbody tr.even { background: #f7f8fd; }
table.dn tbody tr      { border-bottom: 1px solid #eaecf5; }
table.dn tbody tr:last-child { border-bottom: none; }

table.dn tbody td {
    padding: 5px 6px; font-size: 10px; color: #334155;
    vertical-align: middle; overflow: hidden;
    text-overflow: ellipsis; white-space: nowrap; line-height: 1.4;
}
table.dn tbody td.tnum   { text-align: left;   color: #94a3b8; font-size: 9px; padding-left: 8px; }
table.dn tbody td.tname  { text-align: left;   font-weight: 600; color: #1e293b; }
table.dn tbody td.tcode  { text-align: center; font-size: 9px; color: #64748b; letter-spacing: 0.3px; }
table.dn tbody td.tc     { text-align: center; }
table.dn tbody td.tr     { text-align: right;  padding-right: 9px; font-variant-numeric: tabular-nums; }
table.dn tbody td.tmoney { text-align: right;  padding-right: 9px; font-weight: 600;
                           font-variant-numeric: tabular-nums; }
table.dn tbody td.tvalue { text-align: right;  padding-right: 9px; font-weight: 700;
                           color: #059669; font-variant-numeric: tabular-nums; }

/* ── TOTALS TRAY ───────────────────────────────────────────────────── */
.tray-wrap { width: 100%; border-collapse: collapse;
             margin-top: 0; margin-bottom: 18px; }
.tray-wrap td.sp   { vertical-align: top; }
.tray-wrap td.tcol { width: 298px; vertical-align: top; padding: 0; }

.tray {
    width: 100%; border-collapse: collapse;
    border-top:    3px solid #4B5EBD;
    border-left:   1px solid #d1d9f0;
    border-right:  1px solid #d1d9f0;
    border-bottom: 1px solid #d1d9f0;
}
.tray tr.sr td {
    padding: 6px 12px; font-size: 10.5px; color: #475569;
    background: #f8f9ff; border-bottom: 1px solid #e4e7f5;
}
.tray tr.sr td.sl  { text-align: left;  font-weight: 500; }
.tray tr.sr td.sv  { text-align: right; font-weight: 700; font-variant-numeric: tabular-nums; }
.tray tr.sr td.svg { color: #059669; }
.tray tr.sr td.sva { color: #d97706; }

.tray tr.gr td {
    background: #4B5EBD; color: #fff; font-size: 11.5px; font-weight: 800;
    padding: 10px 12px; font-variant-numeric: tabular-nums;
}
.tray tr.gr td.gl { text-align: left; color: #c7d0f7;
                    font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
.tray tr.gr td.gv { text-align: right; }

/* ── SIGNATURE STRIP ───────────────────────────────────────────────── */
.sig { width: 100%; border-collapse: collapse;
       margin-top: 16px; margin-bottom: 18px; }
.sig td { padding: 0 18px 0 0; vertical-align: bottom; width: 33%; }
.sig td:last-child { padding-right: 0; }
.sig-line { border-top: 1px solid #94a3b8; margin-top: 26px;
            padding-top: 5px; font-size: 9px; color: #475569; }
.sig-role { font-size: 7.5px; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.5px; margin-top: 1px; }

/* ── PAGE FOOTER (fixed) ───────────────────────────────────────────── */
.pgfoot {
    position: fixed; bottom: 0; left: 38px; right: 38px;
    border-top: 1px solid #c8cef2;
    padding-top: 6px; padding-bottom: 7px; background: #fff;
}
.pgfoot table { width: 100%; border-collapse: collapse; }
.pgfoot td { font-size: 7.5px; color: #94a3b8;
             vertical-align: middle; white-space: nowrap; }
.pgfoot td.pr { text-align: right; }
.pgfoot .italic { font-style: italic; color: #b0b9cc; font-size: 7px; }
.pgfoot .pnum  { font-weight: 700; color: #64748b; font-size: 7.5px; }

.foot-space { height: 38px; }
</style>
</head>
<body>

{{-- ══ HEADER ══════════════════════════════════════════════════════════ --}}
<table class="hdr">
  <tr>
    <td style="width:50%;">
      <span class="co-name">
        {{ $companyProfile->business_name ?? $companyProfile->name ?? '—' }}
      </span>
      <span class="co-meta">
        @if(!empty($companyProfile->physical_address)){{ $companyProfile->physical_address }}<br>@endif
        @if(!empty($companyProfile->email_address))Email: {{ $companyProfile->email_address }}<br>@endif
        @if(!empty($companyProfile->primary_number))Tel: {{ $companyProfile->primary_number }}@endif
      </span>
    </td>
    <td style="width:50%; text-align:right;">
      <span class="doc-title">Delivery Note</span>
      <span class="br-name">{{ $branch->name ?? '—' }}</span>
      <span class="br-meta">
        @if(!empty($branch->address)){{ $branch->address }}<br>@endif
        @if(!empty($branch->phone))Tel: {{ $branch->phone }}@endif
      </span>
      <span class="doc-badge">Retail Distribution</span>
    </td>
  </tr>
</table>

<hr class="div-blue">

{{-- ══ META STRIP ════════════════════════════════════════════════════════ --}}
<table class="meta">
  <tr>
    <td>
      <span class="ml">Delivery Date</span>
      <span class="mv">{{ $displayDate }}</span>
      <span class="ms">{{ \Carbon\Carbon::parse($deliveryDate)->format('l') }}</span>
    </td>
    <td>
      <span class="ml">Generated At</span>
      <span class="mv">{{ $generatedAt }}</span>
    </td>
    <td>
      <span class="ml">Prepared By</span>
      <span class="mv">{{ $generatedBy }}</span>
      @if(!empty($preparedByUser->position))
        <span class="ms">
          {{ $preparedByUser->position }}
          @if(!empty($preparedByUser->department)) · {{ $preparedByUser->department }}@endif
        </span>
      @endif
    </td>
    <td>
      <span class="ml">Contact</span>
      <span class="mv">{{ !empty($preparedByUser->phone) ? $preparedByUser->phone : '—' }}</span>
      @if(!empty($preparedByUser->email))
        <span class="ms">{{ $preparedByUser->email }}</span>
      @endif
    </td>
  </tr>
</table>

{{-- ══ KPI BAR ═══════════════════════════════════════════════════════════ --}}
<table class="kpi">
  <tr>
    <td>
      <span class="kl">Product Lines</span>
      <span class="kv kv-accent">{{ $deliveryNotes->count() }}</span>
    </td>
    <td>
      <span class="kl">Total Quantity</span>
      <span class="kv kv-slate">{{ number_format($totalQty, 2) }}</span>
    </td>
    <td>
      <span class="kl">Total Cost (MWK)</span>
      <span class="kv kv-slate">{{ number_format($grandTotalCost, 2) }}</span>
    </td>
    <td>
      <span class="kl">Total Value (MWK)</span>
      <span class="kv kv-green">{{ number_format($grandTotalValue, 2) }}</span>
    </td>
  </tr>
</table>

{{-- ══ STATUS BADGE ROW (only when both submitted and pending coexist) ═══ --}}
@if($submittedCount > 0 && $pendingCount > 0)
<table class="status-row">
  <tr>
    <td>
      Note status:&nbsp;
      <span class="bdg bdg-sub">&#10003; {{ $submittedCount }} Submitted</span>
      &nbsp;
      <span class="bdg bdg-pend">&#9679; {{ $pendingCount }} Pending</span>
    </td>
  </tr>
</table>
@endif

{{-- ══ MAIN TABLE ═════════════════════════════════════════════════════════ --}}
<table class="dn">
  <colgroup>
    <col style="width:24px;">   {{-- # --}}
    <col style="width:24%;">    {{-- Product Name --}}
    <col style="width:8%;">     {{-- Code --}}
    <col style="width:6%;">     {{-- Unit --}}
    <col style="width:8.5%;">   {{-- Cost Price --}}
    <col style="width:8.5%;">   {{-- Sell Price --}}
    <col style="width:7%;">     {{-- Qty --}}
    <col style="width:10%;">    {{-- Cost Value --}}
    <col style="width:10%;">    {{-- Sell Value --}}
    <col style="width:9%;">     {{-- Status --}}
  </colgroup>
  <thead>
    <tr>
      <th class="thl">#</th>
      <th class="thl">Product Name</th>
      <th class="thc">Code</th>
      <th class="thc">Unit</th>
      <th class="thr">Cost Price</th>
      <th class="thr">Sell Price</th>
      <th class="thr">Qty</th>
      <th class="thr">Cost Value</th>
      <th class="thr">Sell Value</th>
      <th class="thc">Status</th>
    </tr>
  </thead>
  <tbody>
    @forelse($deliveryNotes as $i => $note)
      @php
        $qty       = (float) $note->quantity;
        $costP     = (float) ($note->cost_price    ?? 0);
        $sellP     = (float) ($note->selling_price ?? 0);
        $costVal   = $qty * $costP;
        $sellVal   = $qty * $sellP;
        $rc        = ($i % 2 === 0) ? 'odd' : 'even';
        $submitted = (bool) $note->submitted;
      @endphp
      <tr class="{{ $rc }}">
        <td class="tnum">{{ $i + 1 }}</td>
        <td class="tname">{{ $note->product_name ?? '—' }}</td>
        <td class="tcode">{{ $note->product_code ?? '—' }}</td>
        <td class="tc">{{ $note->product_unit ?? '—' }}</td>
        <td class="tmoney">{{ number_format($costP,   2) }}</td>
        <td class="tmoney">{{ number_format($sellP,   2) }}</td>
        <td class="tr">{{ number_format($qty,    2) }}</td>
        <td class="tmoney">{{ number_format($costVal, 2) }}</td>
        <td class="tvalue">{{ number_format($sellVal, 2) }}</td>
        <td class="tc">
          @if($submitted)
            <span class="bdg bdg-sub">Submitted</span>
          @else
            <span class="bdg bdg-pend">Pending</span>
          @endif
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="10"
            style="text-align:center;padding:22px;color:#94a3b8;font-style:italic;">
          No delivery notes found for this branch on {{ $displayDate }}.
        </td>
      </tr>
    @endforelse
  </tbody>
</table>

{{-- ══ TOTALS TRAY ════════════════════════════════════════════════════════ --}}
<table class="tray-wrap">
  <tr>
    <td class="sp">&nbsp;</td>
    <td class="tcol">
      <table class="tray">
        <tr class="sr">
          <td class="sl">Total Cost (MWK)</td>
          <td class="sv">{{ number_format($grandTotalCost, 2) }}</td>
        </tr>
        <tr class="sr">
          <td class="sl">Total Quantity</td>
          <td class="sv">{{ number_format($totalQty, 2) }}</td>
        </tr>
        @if($submittedCount > 0)
        <tr class="sr">
          <td class="sl">Submitted Lines</td>
          <td class="sv svg">{{ $submittedCount }}</td>
        </tr>
        @endif
        @if($pendingCount > 0)
        <tr class="sr">
          <td class="sl" style="color:#d97706;">Pending Lines</td>
          <td class="sv sva">{{ $pendingCount }}</td>
        </tr>
        @endif
        <tr class="gr">
          <td class="gl">Total Selling Value</td>
          <td class="gv">MWK {{ number_format($grandTotalValue, 2) }}</td>
        </tr>
      </table>
    </td>
  </tr>
</table>

{{-- ══ SIGNATURE STRIP ════════════════════════════════════════════════════ --}}
<table class="sig">
  <tr>
    <td>
      <div class="sig-line">{{ $generatedBy }}</div>
      <div class="sig-role">Prepared By</div>
    </td>
    <td>
      <div class="sig-line">&nbsp;</div>
      <div class="sig-role">Authorised By</div>
    </td>
    <td>
      <div class="sig-line">&nbsp;</div>
      <div class="sig-role">Received By (Branch)</div>
    </td>
  </tr>
</table>

<div class="foot-space"></div>

{{-- ══ PAGE FOOTER (fixed — appears on every page) ═══════════════════════ --}}
<div class="pgfoot">
  <table>
    <tr>
      <td>{{ $branch->name ?? '' }} &nbsp;·&nbsp; Delivery Note &nbsp;·&nbsp; {{ $displayDate }}</td>
      <td class="pr">
        <span class="italic">
          This document is computer-generated and does not require a physical signature.
        </span>
        &nbsp;&nbsp;<span class="pnum">Page 1 of 1</span>
      </td>
    </tr>
  </table>
</div>

</body>
</html>