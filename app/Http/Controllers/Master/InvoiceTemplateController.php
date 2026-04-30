<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceTemplateController extends Controller
{
    /**
     * Directory (relative to resources/views) where invoice template blades live.
     */
    private string $viewFolder = 'templates/invoice';


    
  public function showInvoiceTemplatesView()
    {
        $templates = DB::table('invoice_templates')->orderBy('name')->get();
        return view('master.invoice-templates', compact('templates'));
    }


    // ── Insert ────────────────────────────────────────────────────────────────

    public function insertInvoiceTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255|unique:invoice_templates,name',
            'view_name'   => 'required|string|max:255|unique:invoice_templates,view_name|regex:/^[a-z0-9_]+$/',
            'description' => 'nullable|string',
        ], [
            'name.unique'      => 'A template with this name already exists.',
            'view_name.unique' => 'A template with this blade view name already exists.',
            'view_name.regex'  => 'Blade view name may only contain lowercase letters, numbers, and underscores.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'error'  => $validator->errors()->first(),
            ]);
        }

        // Auto-create the blade file if it doesn't exist yet
        $createResult = $this->ensureBladeExists($request->view_name, $request->name);
        if ($createResult !== true) {
            return response()->json(['status' => 422, 'error' => $createResult]);
        }

        $id = DB::table('invoice_templates')->insertGetId([
            'name'        => $request->name,
            'view_name'   => $request->view_name,
            'description' => $request->description ?? null,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $template = DB::table('invoice_templates')->find($id);

        return response()->json([
            'status'   => 201,
            'success'  => "Template <strong>{$template->name}</strong> created successfully.",
            'template' => $template,
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function updateInvoiceTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'          => 'required|integer|exists:invoice_templates,id',
            'name'        => "required|string|max:255|unique:invoice_templates,name,{$request->id}",
            'view_name'   => "required|string|max:255|unique:invoice_templates,view_name,{$request->id}|regex:/^[a-z0-9_]+$/",
            'description' => 'nullable|string',
        ], [
            'id.exists'        => 'Template not found.',
            'name.unique'      => 'Another template with this name already exists.',
            'view_name.unique' => 'Another template with this blade view name already exists.',
            'view_name.regex'  => 'Blade view name may only contain lowercase letters, numbers, and underscores.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'error'  => $validator->errors()->first(),
            ]);
        }

        $old = DB::table('invoice_templates')->find($request->id);

        // If view_name changed, auto-create the new blade file
        // (keep old file — another record may still reference it)
        if ($old->view_name !== $request->view_name) {
            $createResult = $this->ensureBladeExists($request->view_name, $request->name);
            if ($createResult !== true) {
                return response()->json(['status' => 422, 'error' => $createResult]);
            }
        }

        DB::table('invoice_templates')
            ->where('id', $request->id)
            ->update([
                'name'        => $request->name,
                'view_name'   => $request->view_name,
                'description' => $request->description ?? null,
                'updated_at'  => now(),
            ]);

        $template = DB::table('invoice_templates')->find($request->id);

        return response()->json([
            'status'   => 201,
            'success'  => "Template <strong>{$template->name}</strong> updated successfully.",
            'template' => $template,
        ]);
    }

    // ── Delete ────────────────────────────────────────────────────────────────

   public function deleteInvoiceTemplate(Request $request)
{
    $validator = Validator::make($request->all(), [
        'id' => 'required|integer|exists:invoice_templates,id',
    ], [
        'id.exists' => 'Template not found.',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 422,
            'error'  => $validator->errors()->first(),
        ]);
    }

    $template = DB::table('invoice_templates')->find($request->id);

    // Delete the blade file from disk
    $filePath = resource_path('views/' . $this->viewFolder . '/' . $template->view_name . '.blade.php');
    if (File::exists($filePath)) {
        File::delete($filePath);
    }

    DB::table('invoice_templates')->where('id', $request->id)->delete();

    return response()->json([
        'status'  => 201,
        'success' => "Template <strong>{$template->name}</strong> deleted successfully.",
    ]);
}

    // ── Preview ───────────────────────────────────────────────────────────────

    public function previewInvoiceTemplate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:invoice_templates,id',
        ]);

        if ($validator->fails()) {
            abort(404, 'Template not found.');
        }

        $template = DB::table('invoice_templates')->find($request->id);
        $viewPath  = 'templates.invoice.' . $template->view_name;

        if (!View::exists($viewPath)) {
            abort(404, "Blade view [{$template->view_name}] does not exist.");
        }

        return view($viewPath, [
            'invoice'   => $this->dummyInvoiceData(),
            'isPreview' => true,
            'template'  => $template,
        ]);
    }

    // ── Generate PDF ──────────────────────────────────────────────────────────

    public function generateInvoiceTemplatePdf(Request $request, $id)
    {
        $template = DB::table('invoice_templates')->find($id);

        if (!$template) {
            abort(404, 'Template not found.');
        }

        $viewPath = 'templates.invoice.' . $template->view_name;

        if (!View::exists($viewPath)) {
            abort(404, "Blade view [{$template->view_name}] does not exist.");
        }

        $pdf = Pdf::loadView($viewPath, [
            'invoice'   => $this->dummyInvoiceData(),
            'isPreview' => true,
            'template'  => $template,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('invoice_preview_' . $template->view_name . '.pdf');
    }

    // ── Private: ensure blade file exists, create if not ─────────────────────

    /**
     * Checks whether the blade file exists on disk.
     * If not, creates a clean starter template at the correct path.
     *
     * @return true|string  Returns true on success, or an error message string on failure.
     */
    private function ensureBladeExists(string $viewName, string $templateName): true|string
    {
        $directory = resource_path('views/' . $this->viewFolder);
        $filePath  = $directory . '/' . $viewName . '.blade.php';

        // Already exists — nothing to do
        if (File::exists($filePath)) {
            return true;
        }

        // Make sure the directory exists
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        try {
            File::put($filePath, $this->bladeStub($viewName, $templateName));
        } catch (\Exception $e) {
            return 'Could not create blade file: ' . $e->getMessage();
        }

        return true;
    }

    /**
     * Returns the starter blade content for a brand-new invoice template.
     * Customise freely — this stub is only written once when the template is first created.
     */
    private function bladeStub(string $viewName, string $templateName): string
    {
        $now = now()->format('Y-m-d H:i');

        return <<<BLADE
{{--
  Invoice Template : {$templateName}
  View             : templates/invoice/{$viewName}.blade.php
  Generated        : {$now}

  Available variables:
    \$invoice       → invoice object (see InvoiceTemplateController::dummyInvoiceData)
    \$isPreview     → bool — true when rendered from the preview modal
    \$template      → the invoice_templates DB record
--}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ \$invoice->invoice_number ?? 'Invoice' }}</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      font-size: 13px;
      color: #333;
      background: #fff;
      padding: 40px;
    }

    .preview-banner {
      background: #fff3cd;
      border: 1px solid #ffc107;
      color: #856404;
      padding: 8px 16px;
      border-radius: 6px;
      margin-bottom: 20px;
      font-size: 12px;
      text-align: center;
    }

    .inv-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 32px;
    }

    .inv-header .company-name {
      font-size: 24px;
      font-weight: 700;
      color: #4B5EBD;
    }

    .inv-header .company-meta {
      font-size: 12px;
      color: #666;
      margin-top: 4px;
      line-height: 1.6;
    }

    .inv-header .inv-title-block { text-align: right; }

    .inv-header .inv-title-block h1 {
      font-size: 32px;
      font-weight: 800;
      color: #4B5EBD;
      letter-spacing: 2px;
      text-transform: uppercase;
    }

    .inv-header .inv-title-block .inv-number {
      font-size: 13px;
      color: #555;
      margin-top: 4px;
    }

    .inv-header .inv-title-block .inv-status {
      display: inline-block;
      margin-top: 6px;
      padding: 2px 10px;
      border-radius: 20px;
      background: #e8f4fd;
      color: #17a2b8;
      font-size: 11px;
      font-weight: 600;
      text-transform: uppercase;
    }

    .inv-divider {
      border: none;
      border-top: 2px solid #4B5EBD;
      margin: 0 0 24px;
    }

    .inv-addresses {
      display: flex;
      justify-content: space-between;
      margin-bottom: 28px;
    }

    .inv-addresses .address-block h4 {
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: #4B5EBD;
      margin-bottom: 6px;
    }

    .inv-addresses .address-block p {
      font-size: 12.5px;
      color: #444;
      line-height: 1.7;
    }

    .inv-dates {
      display: flex;
      gap: 32px;
      margin-bottom: 28px;
    }

    .inv-dates .date-item label {
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: #999;
      display: block;
      margin-bottom: 2px;
    }

    .inv-dates .date-item span {
      font-size: 13px;
      font-weight: 600;
      color: #333;
    }

    .inv-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 24px;
    }


    .inv-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 24px;
}

.inv-table thead tr { background: #4B5EBD; color: #fff; }

.inv-table thead th {
  padding: 10px 12px;
  text-align: left;
  font-size: 11.5px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.inv-table thead th:not(:first-child):not(:nth-child(2)) {
  text-align: center;
}

.inv-table tbody td {
  padding: 10px 12px;
  border-bottom: 1px solid #e9ecef;
  font-size: 12.5px;
  color: #444;
}

.inv-table tbody td:not(:first-child):not(:nth-child(2)) {
  text-align: center;
}

.inv-table tbody tr:nth-child(even) { background: #f7f8fd; }








    .inv-table tbody td {
      padding: 10px 12px;
      border-bottom: 1px solid #e9ecef;
      font-size: 12.5px;
      color: #444;
    }

    .inv-totals {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 28px;
    }

    .inv-totals table { width: 280px; }

    .inv-totals table td {
      padding: 5px 8px;
      font-size: 12.5px;
      color: #555;
    }

    .inv-totals table td:last-child {
      text-align: right;
      font-weight: 600;
      color: #333;
    }

    .inv-totals table tr.total-row td {
      border-top: 2px solid #4B5EBD;
      font-size: 14px;
      font-weight: 700;
      color: #4B5EBD;
      padding-top: 10px;
    }

    .inv-footer-grid {
      display: flex;
      justify-content: space-between;
      gap: 24px;
      margin-bottom: 24px;
    }

    .inv-footer-grid .notes-block,
    .inv-footer-grid .bank-block { flex: 1; }

    .inv-footer-grid h4 {
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: #4B5EBD;
      margin-bottom: 6px;
    }

    .inv-footer-grid p,
    .inv-footer-grid table td {
      font-size: 12px;
      color: #555;
      line-height: 1.7;
    }

    .inv-footer-grid .bank-block table td:first-child {
      color: #999;
      width: 120px;
    }

    .inv-thankyou {
      text-align: center;
      padding: 12px;
      background: #f0f2fa;
      border-radius: 6px;
      font-size: 12px;
      color: #4B5EBD;
      font-weight: 600;
      letter-spacing: 0.5px;
    }
  </style>
</head>
<body>

  @if(isset(\$isPreview) && \$isPreview)
    <div class="preview-banner">
      ⚠ This is a <strong>preview</strong> using sample data — not a real invoice.
    </div>
  @endif

  <div class="inv-header">
    <div>
      <div class="company-name">{{ \$invoice->company_name }}</div>
      <div class="company-meta">
        {{ \$invoice->company_address }}<br>
        {{ \$invoice->company_email }} &nbsp;|&nbsp; {{ \$invoice->company_phone }}
      </div>
    </div>
    <div class="inv-title-block">
      <h1>Invoice</h1>
      <div class="inv-number"># {{ \$invoice->invoice_number }}</div>
      <span class="inv-status">{{ \$invoice->status }}</span>
    </div>
  </div>

  <hr class="inv-divider">

  <div class="inv-dates">
    <div class="date-item">
      <label>Invoice Date</label>
      <span>{{ \$invoice->invoice_date }}</span>
    </div>
    <div class="date-item">
      <label>Due Date</label>
      <span>{{ \$invoice->due_date }}</span>
    </div>
    <div class="date-item">
      <label>Payment Terms</label>
      <span>{{ \$invoice->payment_terms }}</span>
    </div>
  </div>

  <div class="inv-addresses">
    <div class="address-block">
      <h4>From</h4>
      <p>
        <strong>{{ \$invoice->company_name }}</strong><br>
        {{ \$invoice->company_address }}<br>
        {{ \$invoice->company_email }}
      </p>
    </div>
    <div class="address-block" style="text-align:right">
      <h4>Bill To</h4>
      <p>
        <strong>{{ \$invoice->client_name }}</strong><br>
        {{ \$invoice->client_address }}<br>
        {{ \$invoice->client_email }}
      </p>
    </div>
  </div>

<table class="inv-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Product Name</th>
        <th>Batch Number</th>
        <th>Unit</th>
        <th>Price</th>
        <th>Qty</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      @foreach(\$invoice->items as \$i => \$item)
        <tr>
          <td>{{ \$i + 1 }}</td>
          <td>{{ \$item->description }}</td>
          <td>{{ \$item->batch_number }}</td>
          <td>{{ \$item->unit }}</td>
          <td>{{ \$invoice->currency }} {{ number_format(\$item->unit_price, 2) }}</td>
          <td>{{ \$item->quantity }}</td>
          <td>{{ \$invoice->currency }} {{ number_format(\$item->total, 2) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <div class="inv-totals">
    <table>
      <tr>
        <td>Subtotal</td>
        <td>{{ \$invoice->currency }} {{ number_format(\$invoice->subtotal, 2) }}</td>
      </tr>
      <tr>
        <td>Tax ({{ \$invoice->tax_rate }}%)</td>
        <td>{{ \$invoice->currency }} {{ number_format(\$invoice->tax_amount, 2) }}</td>
      </tr>
      @if(\$invoice->discount > 0)
        <tr>
          <td>Discount</td>
          <td>- {{ \$invoice->currency }} {{ number_format(\$invoice->discount, 2) }}</td>
        </tr>
      @endif
      <tr class="total-row">
        <td>Total Due</td>
        <td>{{ \$invoice->currency }} {{ number_format(\$invoice->total, 2) }}</td>
      </tr>
    </table>
  </div>

  <div class="inv-footer-grid">
    <div class="notes-block">
      <h4>Notes</h4>
      <p>{{ \$invoice->notes }}</p>
    </div>
    <div class="bank-block">
      <h4>Payment Details</h4>
      <table>
        <tr><td>Bank</td><td>{{ \$invoice->bank_name }}</td></tr>
        <tr><td>Account Name</td><td>{{ \$invoice->account_name }}</td></tr>
        <tr><td>Account No.</td><td>{{ \$invoice->account_number }}</td></tr>
      </table>
    </div>
  </div>

  <div class="inv-thankyou">Thank you for your business!</div>

</body>
</html>
BLADE;
    }

    // ── Dummy data ────────────────────────────────────────────────────────────

    private function dummyInvoiceData(): object
    {
        return (object) [
            'invoice_number'  => 'INV-00001',
            'invoice_date'    => now()->format('d M Y'),
            'due_date'        => now()->addDays(30)->format('d M Y'),
            'status'          => 'Draft',

            'company_name'    => 'Netacube ERP',
            'company_address' => '123 Business Park, Lilongwe, Malawi',
            'company_email'   => 'info@netacube.com',
            'company_phone'   => '+265 999 000 000',

            'client_name'     => 'Sample Client Ltd',
            'client_address'  => '456 Client Street, Blantyre, Malawi',
            'client_email'    => 'client@example.com',
            'client_phone'    => '+265 888 000 000',
        
            'items' => collect([
            (object) ['description' => 'Paracetamol 500mg', 'batch_number' => 'BCH-2024-001', 'unit' => 'Box',    'unit_price' => 5000.00, 'quantity' => 10, 'total' => 50000.00],
            (object) ['description' => 'Amoxicillin 250mg', 'batch_number' => 'BCH-2024-002', 'unit' => 'Strip',  'unit_price' => 3000.00, 'quantity' => 5,  'total' => 15000.00],
            (object) ['description' => 'IV Saline 0.9%',    'batch_number' => 'BCH-2024-003', 'unit' => 'Bottle', 'unit_price' => 8000.00, 'quantity' => 1,  'total' =>  8000.00],
        ]),

            'subtotal'       => 73000.00,
            'tax_rate'       => 16.5,
            'tax_amount'     => 12045.00,
            'discount'       => 0.00,
            'total'          => 85045.00,
            'currency'       => 'MWK',

            'notes'          => 'Thank you for your business. Payment is due within 30 days.',
            'payment_terms'  => 'Net 30',
            'bank_name'      => 'National Bank of Malawi',
            'account_number' => '0123456789',
            'account_name'   => 'Netacube ERP Ltd',
        ];
    }
}