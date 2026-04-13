<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceTemplateController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    //  Page
    // ─────────────────────────────────────────────────────────────

    public function showInvoiceTemplatesView()
    {
        $templates = DB::table('invoice_templates')->orderBy('name')->get();
        return view('master.invoice-templates', compact('templates'));
    }

    // ─────────────────────────────────────────────────────────────
    //  CREATE
    // ─────────────────────────────────────────────────────────────

    public function insertInvoiceTemplate(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255|unique:invoice_templates,name',
            'is_default' => 'nullable|in:1',
        ]);

        $isDefault = $request->is_default == '1';
        $now       = now();

        if ($isDefault) {
            DB::table('invoice_templates')->update(['is_default' => false]);
        }

        $id = DB::table('invoice_templates')->insertGetId([
            'name'       => trim($request->name),
            'is_default' => $isDefault,
            'content'    => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $template = DB::table('invoice_templates')->where('id', $id)->first();

        return response()->json([
            'status'   => 201,
            'success'  => "Template '{$template->name}' created successfully.",
            'template' => $template,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  UPDATE (name + is_default only — content handled separately)
    // ─────────────────────────────────────────────────────────────

    public function updateInvoiceTemplate(Request $request)
    {
        $request->validate([
            'id'         => 'required|exists:invoice_templates,id',
            'name'       => "required|string|max:255|unique:invoice_templates,name,{$request->id}",
            'is_default' => 'nullable|in:1',
        ]);

        $isDefault = $request->is_default == '1';

        if ($isDefault) {
            DB::table('invoice_templates')
              ->where('id', '!=', $request->id)
              ->update(['is_default' => false]);
        }

        DB::table('invoice_templates')->where('id', $request->id)->update([
            'name'       => trim($request->name),
            'is_default' => $isDefault,
            'updated_at' => now(),
        ]);

        $template = DB::table('invoice_templates')->where('id', $request->id)->first();

        return response()->json([
            'status'   => 201,
            'success'  => "Template '{$template->name}' updated successfully.",
            'template' => $template,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  DELETE
    // ─────────────────────────────────────────────────────────────

    public function deleteInvoiceTemplate(Request $request)
    {
        $request->validate(['id' => 'required|exists:invoice_templates,id']);

        $template = DB::table('invoice_templates')->where('id', $request->id)->first();
        DB::table('invoice_templates')->where('id', $request->id)->delete();

        return response()->json([
            'status'  => 201,
            'success' => "Template '{$template->name}' deleted successfully.",
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  GET CONTENT  (called by code editor on open)
    // ─────────────────────────────────────────────────────────────

    public function getInvoiceTemplateContent(Request $request)
    {
        $request->validate(['id' => 'required|exists:invoice_templates,id']);

        $template = DB::table('invoice_templates')->where('id', $request->id)->first();

        return response()->json([
            'status'  => 200,
            'content' => $template->content ?? '',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  SAVE CONTENT  (called by code editor on save)
    // ─────────────────────────────────────────────────────────────

    public function saveInvoiceTemplateContent(Request $request)
    {
        $request->validate([
            'id'      => 'required|exists:invoice_templates,id',
            'content' => 'nullable|string',
        ]);

        DB::table('invoice_templates')->where('id', $request->id)->update([
            'content'    => $request->content,
            'updated_at' => now(),
        ]);

        return response()->json([
            'status'  => 201,
            'success' => 'Template content saved successfully.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    //  PREVIEW  (returns a full HTML page for the preview iframe)
    // ─────────────────────────────────────────────────────────────

    public function previewInvoiceTemplate(Request $request)
    {
        $request->validate(['id' => 'required|exists:invoice_templates,id']);

        $template = DB::table('invoice_templates')->where('id', $request->id)->first();
        $content  = $template->content ?? '';

        // Wrap in a full document shell if the user wrote a bare snippet
        if (!str_contains(strtolower($content), '<html')) {
            $name    = e($template->name);
            $content = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$name}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; font-family: 'Segoe UI', sans-serif; font-size: 14px; color: #333; }
    </style>
</head>
<body>
{$content}
</body>
</html>
HTML;
        }

        return response($content, 200)->header('Content-Type', 'text/html');
    }

    // ─────────────────────────────────────────────────────────────
    //  GENERATE PDF
    //  Renders the stored HTML through DomPDF.
    //  Pass $data if your template uses Blade {{ $variable }} syntax.
    // ─────────────────────────────────────────────────────────────

    public function generateInvoiceTemplatePdf(Request $request, int $id)
    {
        $template = DB::table('invoice_templates')->where('id', $id)->first();

        abort_if(!$template, 404, 'Template not found.');

        $html = $this->buildInvoiceHtmlForPdf($template->content ?? '', [
            // 'invoice' => $invoice,  // pass your invoice data here
        ]);

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');

        return $pdf->stream($template->name . '.pdf');
    }

    // ─────────────────────────────────────────────────────────────
    //  HELPER — build final HTML ready for DomPDF
    //
    //  Call this from any other controller when generating invoices:
    //
    //  $ctrl = app(InvoiceTemplateController::class);
    //  $html = $ctrl->buildInvoiceHtmlForPdf($template->content, ['invoice' => $invoice]);
    //  return Pdf::loadHTML($html)->download('invoice.pdf');
    // ─────────────────────────────────────────────────────────────

    public function buildInvoiceHtmlForPdf(string $rawContent, array $data = []): string
    {
        // Process Blade {{ $variable }} / @if ... syntax if used in the template
        $rendered = Blade::render($rawContent, $data);

        if (!str_contains(strtolower($rendered), '<html')) {
            $rendered = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
    </style>
</head>
<body>
{$rendered}
</body>
</html>
HTML;
        }

        return $rendered;
    }
}