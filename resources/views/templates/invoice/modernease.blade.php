<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice - Netacube ERP Ltd</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'DM Sans', sans-serif;
    font-size: 12px;
    color: #111;
    background: #c4c8cf;
    padding: 32px 20px;
  }

  .print-btn {
    display: block;
    margin: 0 auto 14px auto;
    max-width: 980px;
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
    max-width: 980px;
    margin: 0 auto;
    background: #fff;
    border: 1px solid #d0d0d0;
    box-shadow: 0 6px 40px rgba(0,0,0,0.13);
  }

  /* ── HEADER (no bg) ── */
  .hdr {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    padding: 30px 32px 22px 32px;
    border-bottom: 1.5px solid #d0d0d0;
  }

  .co-name {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    font-weight: 700;
    color: #111;
    letter-spacing: 0.3px;
    margin-bottom: 10px;
    line-height: 1;
  }
  .co-meta {
    font-size: 11.5px;
    color: #555;
    line-height: 1.95;
    font-weight: 400;
  }

  .inv-right {
    text-align: right;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 6px;
  }
  .inv-word {
    font-family: 'Playfair Display', serif;
    font-size: 28px;
    font-weight: 700;
    color: #111;
    letter-spacing: 5px;
    text-transform: uppercase;
    line-height: 1;
  }
  .inv-ref {
    font-size: 12px;
    color: #555;
    font-weight: 400;
  }
  .inv-ref strong { color: #111; font-weight: 700; }

  .inv-dates {
    display: flex;
    flex-direction: column;
    gap: 4px;
    align-items: flex-end;
    margin-top: 2px;
  }
  .d-item { display: flex; align-items: center; gap: 14px; }
  .d-item label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 1.6px;
    color: #999;
    font-weight: 700;
  }
  .d-item span {
    font-size: 12px;
    font-weight: 600;
    color: #111;
    min-width: 96px;
    text-align: right;
  }

  /* ── BILL TO + PAYMENT DETAILS ── */
  .info-row {
    display: flex;
    border-bottom: 1.5px solid #d0d0d0;
  }
  .info-cell {
    flex: 1;
    padding: 16px 32px;
  }
  .info-cell + .info-cell {
    border-left: 1.5px solid #d0d0d0;
  }
  /* Section labels — gray underline, text width only */
  .info-cell h5 {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 2.2px;
    color: #999;
    font-weight: 600;
    margin-bottom: 8px;
    padding-bottom: 4px;
    border-bottom: 1.5px solid #ccc;
    display: inline-block;
  }
  .cli-name {
    font-size: 13.5px;
    font-weight: 700;
    color: #111;
    margin-bottom: 4px;
    margin-top: 4px;
  }
  .info-cell p {
    font-size: 12px;
    color: #333;
    line-height: 1.9;
    font-weight: 400;
  }
  .bank-t {
    width: 100%;
    font-size: 12px;
    border-collapse: collapse;
    margin-top: 4px;
  }
  .bank-t td { padding: 3.5px 0; vertical-align: top; }
  .bank-t td:first-child {
    color: #888;
    width: 118px;
    font-size: 10.5px;
    font-weight: 500;
  }
  .bank-t td:last-child {
    font-weight: 600;
    color: #111;
  }

  /* ── TABLE ── */
  .table-wrap { padding: 0 32px; }

  table.t {
    width: 100%;
    border-collapse: collapse;
    margin: 22px 0 0 0;
    table-layout: fixed;
  }

  /* thead bold only */
  table.t thead tr { background: #f0f0f0; }
  table.t thead th {
    padding: 10px 7px;
    font-size: 9.5px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.9px;
    color: #111;
    text-align: center;
    border-top: 1.5px solid #d0d0d0;
    border-bottom: 1.5px solid #d0d0d0;
  }

  table.t thead th:nth-child(1)  { text-align: left; width: 28px; padding-left: 4px; }
  table.t thead th:nth-child(2)  { text-align: left; }
  table.t thead th:nth-child(3)  { width: 7%; }
  table.t thead th:nth-child(4)  { width: 11%; }
  table.t thead th:nth-child(5)  { width: 10%; }
  table.t thead th:nth-child(6)  { width: 5%; }
  table.t thead th:nth-child(7)  { width: 11%; }
  table.t thead th:nth-child(8)  { width: 11%; }
  table.t thead th:nth-child(9)  { width: 8%; }

  table.t tbody tr { border-bottom: 1px solid #e4e4e4; }
  table.t tbody tr:nth-child(even) { background: #fafafa; }

  /* tbody: plain black, no bold */
  table.t tbody td {
    padding: 6px 7px;
    font-size: 11.5px;
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
    padding-left: 4px;
    color: #555;
  }
  table.t tbody td:nth-child(2) {
    text-align: left;
    font-weight: 400;
    color: #111;
    font-size: 11.5px;
  }
  table.t tbody td:nth-child(4) {
    font-family: 'DM Mono', monospace;
    font-size: 9.5px;
    color: #444;
  }
  /* Taxable column */
  table.t tbody td.taxable-yes {
    color: #1a7a3c;
    font-weight: 500;
  }
  table.t tbody td.taxable-no {
    color: #888;
  }
  table.t tbody td.amt {
    font-weight: 400;
    color: #111;
  }

  /* ── TOTALS ── */
  .totals-row {
    display: flex;
    justify-content: flex-end;
    margin-top: 0;
  }
  .totals-inner {
    width: 26%;
    border-left: 1.5px solid #d0d0d0;
    border-right: 1.5px solid #d0d0d0;
    border-bottom: 1.5px solid #d0d0d0;
  }

  .tot-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 7px 13px;
    font-size: 11.5px;
    border-bottom: 1px solid #e2e2e2;
    color: #111;
  }
  .tot-row:last-child { border-bottom: none; }
  .tot-row .lbl { color: #555; font-weight: 400; }
  .tot-row .val { font-weight: 600; color: #111; }

  .tot-row.grand {
    background: #e8e8e8;
    padding: 10px 13px;
    border-top: 1.5px solid #d0d0d0;
  }
  .tot-row.grand .lbl { color: #111; font-weight: 700; font-size: 12px; }
  .tot-row.grand .val { color: #111; font-size: 13px; font-weight: 700; }

  /* ── FOOTER ── */
  .foot-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    padding: 22px 32px 26px 32px;
    border-top: 1.5px solid #d0d0d0;
    margin-top: 24px;
    gap: 40px;
  }

  /* Payment Terms – LEFT */
  .foot-left h5 {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 2.2px;
    color: #999;
    font-weight: 600;
    margin-bottom: 8px;
    padding-bottom: 4px;
    border-bottom: 1.5px solid #ccc;
    display: inline-block;
  }
  .terms-chip {
    display: inline-block;
    border: 1.5px solid #bbb;
    color: #111;
    font-size: 11px;
    font-weight: 600;
    padding: 2px 10px;
    border-radius: 2px;
    margin-bottom: 6px;
  }
  .foot-left p {
    font-size: 11px;
    color: #555;
    line-height: 1.85;
    max-width: 280px;
    font-weight: 400;
  }

  /* Authorised Signature – RIGHT */
  .foot-right {
    text-align: right;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
  }
  .sig-block {
    margin-top: 44px;
    border-top: 1.5px solid #aaa;
    width: 200px;
    padding-top: 6px;
    font-size: 10px;
    color: #999;
    letter-spacing: 0.5px;
    text-align: center;
  }

  /* ── PAGE FOOTER ── */
  .pg-foot {
    border-top: 1.5px solid #d0d0d0;
    padding: 9px 32px;
    display: flex;
    justify-content: space-between;
    font-size: 9.5px;
    color: #999;
    background: #f8f8f8;
  }

  @media print {
    body { background: #fff; padding: 0; }
    .print-btn { display: none; }
    .wrap { box-shadow: none; border: none; }
  }
</style>
</head>
<body>


<div class="wrap">

  <!-- HEADER (no background) -->
  <div class="hdr">
    <div>
      <div class="co-name">Netacube ERP Ltd</div>
      <div class="co-meta">
        123 Business Park, Lilongwe, Malawi<br>
        info@netacube.com &nbsp;·&nbsp; +265 999 000 000<br>
        TIN: 1234567890
      </div>
    </div>
    <div class="inv-right">
      <div class="inv-word">Invoice</div>
      <div class="inv-ref">INV-Number: <strong>00001</strong></div>
      <div class="inv-dates">
        <div class="d-item">
          <label>Invoice Date</label>
          <span>01 May 2025</span>
        </div>
        <div class="d-item">
          <label>Due Date</label>
          <span>31 May 2025</span>
        </div>
      </div>
    </div>
  </div>

  <!-- BILL TO + PAYMENT DETAILS -->
  <div class="info-row">
    <div class="info-cell">
      <h5>Bill To</h5>
      <div class="cli-name">Sample Client Ltd</div>
      <p>
        456 Client Street, Blantyre, Malawi<br>
        client@example.com &nbsp;·&nbsp; +265 888 000 000
      </p>
    </div>
    <div class="info-cell">
      <h5>Payment Details</h5>
      <table class="bank-t">
        <tr><td>Bank</td><td>National Bank of Malawi</td></tr>
        <tr><td>Account Name</td><td>Netacube ERP Ltd</td></tr>
        <tr><td>Account No.</td><td>0123456789</td></tr>
        <tr><td>Branch</td><td>Lilongwe Main</td></tr>
      </table>
    </div>
  </div>

  <!-- TABLE + TOTALS -->
  <div class="table-wrap">
    <table class="t">
      <thead>
        <tr>
          <th>#</th>
          <th>Product Name</th>
          <th>Unit</th>
          <th>Batch No.</th>
          <th>Expiry Date</th>
          <th>Qty</th>
          <th>Price (MWK)</th>
          <th>Total (MWK)</th>
          <th>Taxable</th>
        </tr>
      </thead>
      <tbody>
        <tr><td>1</td><td>Paracetamol 500mg</td><td>Box</td><td>BCH-2024-001</td><td>Dec 2026</td><td>10</td><td>5,000.00</td><td class="amt">50,000.00</td><td class="taxable-yes">Yes</td></tr>
        <tr><td>2</td><td>Amoxicillin 250mg</td><td>Strip</td><td>BCH-2024-002</td><td>Jun 2026</td><td>5</td><td>3,000.00</td><td class="amt">15,000.00</td><td class="taxable-yes">Yes</td></tr>
        <tr><td>3</td><td>IV Saline 0.9%</td><td>Bottle</td><td>BCH-2024-003</td><td>Mar 2027</td><td>1</td><td>8,000.00</td><td class="amt">8,000.00</td><td class="taxable-no">No</td></tr>
        <tr><td>4</td><td>Metformin 500mg</td><td>Box</td><td>BCH-2024-004</td><td>Sep 2026</td><td>8</td><td>4,500.00</td><td class="amt">36,000.00</td><td class="taxable-yes">Yes</td></tr>
        <tr><td>5</td><td>Ibuprofen 400mg</td><td>Strip</td><td>BCH-2024-005</td><td>Aug 2026</td><td>12</td><td>2,800.00</td><td class="amt">33,600.00</td><td class="taxable-yes">Yes</td></tr>
        <tr><td>6</td><td>Omeprazole 20mg</td><td>Box</td><td>BCH-2024-006</td><td>Nov 2026</td><td>4</td><td>6,200.00</td><td class="amt">24,800.00</td><td class="taxable-yes">Yes</td></tr>
        <tr><td>7</td><td>Cetirizine 10mg</td><td>Strip</td><td>BCH-2024-007</td><td>Apr 2027</td><td>20</td><td>1,500.00</td><td class="amt">30,000.00</td><td class="taxable-no">No</td></tr>
        <tr><td>8</td><td>Azithromycin 500mg</td><td>Box</td><td>BCH-2024-008</td><td>Jan 2027</td><td>3</td><td>9,000.00</td><td class="amt">27,000.00</td><td class="taxable-yes">Yes</td></tr>
        <tr><td>9</td><td>Vitamin C 1000mg</td><td>Bottle</td><td>BCH-2024-009</td><td>Jul 2026</td><td>6</td><td>7,500.00</td><td class="amt">45,000.00</td><td class="taxable-no">No</td></tr>
        <tr><td>10</td><td>Diclofenac 50mg</td><td>Strip</td><td>BCH-2024-010</td><td>Feb 2027</td><td>15</td><td>2,200.00</td><td class="amt">33,000.00</td><td class="taxable-yes">Yes</td></tr>
        <tr><td>11</td><td>Atenolol 50mg</td><td>Box</td><td>BCH-2024-011</td><td>Oct 2026</td><td>4</td><td>5,500.00</td><td class="amt">22,000.00</td><td class="taxable-yes">Yes</td></tr>
        <tr><td>12</td><td>Amlodipine 5mg</td><td>Strip</td><td>BCH-2024-012</td><td>May 2027</td><td>7</td><td>3,800.00</td><td class="amt">26,600.00</td><td class="taxable-yes">Yes</td></tr>
        <tr><td>13</td><td>Ciprofloxacin 500mg</td><td>Box</td><td>BCH-2024-013</td><td>Dec 2025</td><td>2</td><td>8,500.00</td><td class="amt">17,000.00</td><td class="taxable-yes">Yes</td></tr>
        <tr><td>14</td><td>Loratadine 10mg</td><td>Strip</td><td>BCH-2024-014</td><td>Jun 2027</td><td>10</td><td>1,800.00</td><td class="amt">18,000.00</td><td class="taxable-no">No</td></tr>
        <tr><td>15</td><td>Metronidazole 400mg</td><td>Box</td><td>BCH-2024-015</td><td>Aug 2027</td><td>5</td><td>4,000.00</td><td class="amt">20,000.00</td><td class="taxable-yes">Yes</td></tr>
        <tr><td>16</td><td>Salbutamol Inhaler</td><td>Piece</td><td>BCH-2024-016</td><td>Mar 2026</td><td>3</td><td>12,000.00</td><td class="amt">36,000.00</td><td class="taxable-no">No</td></tr>
        <tr><td>17</td><td>Doxycycline 100mg</td><td>Strip</td><td>BCH-2024-017</td><td>Sep 2027</td><td>6</td><td>3,200.00</td><td class="amt">19,200.00</td><td class="taxable-yes">Yes</td></tr>
        <tr><td>18</td><td>Lisinopril 10mg</td><td>Box</td><td>BCH-2024-018</td><td>Nov 2027</td><td>4</td><td>5,800.00</td><td class="amt">23,200.00</td><td class="taxable-yes">Yes</td></tr>
        <tr><td>19</td><td>Pantoprazole 40mg</td><td>Strip</td><td>BCH-2024-019</td><td>Jan 2026</td><td>8</td><td>4,100.00</td><td class="amt">32,800.00</td><td class="taxable-yes">Yes</td></tr>
        <tr><td>20</td><td>Simvastatin 20mg</td><td>Box</td><td>BCH-2024-020</td><td>Apr 2026</td><td>3</td><td>6,700.00</td><td class="amt">20,100.00</td><td class="taxable-yes">Yes</td></tr>
        <tr><td>21</td><td>Hydrocortisone Cream</td><td>Tube</td><td>BCH-2024-021</td><td>Feb 2028</td><td>5</td><td>3,500.00</td><td class="amt">17,500.00</td><td class="taxable-no">No</td></tr>
        <tr><td>22</td><td>Zinc Sulphate 20mg</td><td>Bottle</td><td>BCH-2024-022</td><td>Jul 2027</td><td>10</td><td>2,900.00</td><td class="amt">29,000.00</td><td class="taxable-yes">Yes</td></tr>
        <tr><td>23</td><td>Tramadol 50mg</td><td>Box</td><td>BCH-2024-023</td><td>May 2026</td><td>2</td><td>7,200.00</td><td class="amt">14,400.00</td><td class="taxable-yes">Yes</td></tr>
        <tr><td>24</td><td>Folic Acid 5mg</td><td>Strip</td><td>BCH-2024-024</td><td>Oct 2027</td><td>25</td><td>1,200.00</td><td class="amt">30,000.00</td><td class="taxable-no">No</td></tr>
        <tr><td>25</td><td>Erythromycin 250mg</td><td>Box</td><td>BCH-2024-025</td><td>Aug 2026</td><td>4</td><td>5,300.00</td><td class="amt">21,200.00</td><td class="taxable-yes">Yes</td></tr>
      </tbody>
    </table>

    <div class="totals-row">
      <div class="totals-inner">
        <div class="tot-row">
          <span class="lbl">Subtotal</span>
          <span class="val">MWK 719,400.00</span>
        </div>
        <div class="tot-row">
          <span class="lbl">Discount</span>
          <span class="val">MWK 0.00</span>
        </div>
        <div class="tot-row">
          <span class="lbl">VAT (16.5%)</span>
          <span class="val">MWK 118,701.00</span>
        </div>
        <div class="tot-row grand">
          <span class="lbl">Grand Total</span>
          <span class="val">MWK 838,101.00</span>
        </div>
      </div>
    </div>
  </div>

  <!-- FOOTER: Payment Terms LEFT, Signature RIGHT -->
  <div class="foot-row">
    <div class="foot-left">
      <h5>Payment Terms</h5>
      <p>Payment is due within 30 days of invoice date.<br>
      Please reference the invoice number when making payment.</p>
    </div>
    <div class="foot-right">
      <div class="sig-block">Authorised Signature</div>
    </div>
  </div>

  <!-- PAGE FOOTER -->
  <div class="pg-foot">
    <span>Netacube ERP Ltd &nbsp;·&nbsp; TIN: 1234567890 &nbsp;·&nbsp; www.netacube.com</span>
    <span>Page 1 of 1</span>
  </div>

</div>
</body>
</html>