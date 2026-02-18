<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class MasterTenantInvoicesController extends Controller
{
    public function showTenantInvoicesView()
    {
        return view('master.tenants.invoices.tenant-invoices-view');
    }


public function masterSendInvoiceFromTenantDetails(Request $request)
{
    $request->validate([
        'tenant_id'      => 'required|exists:tenants,id',
        'payment_method' => 'required|exists:payment_methods,id',
    ]);

    $tenant = DB::table('tenants')->where('id', $request->tenant_id)->first();
    if (!$tenant) {
        return response()->json(['error' => 'Tenant not found.'], 404);
    }

    // Prevent multiple pending invoices
    $existingPending = DB::table('tenant_invoices')
        ->where('tenant_id', $tenant->id)
        ->where('status', 'Pending')
        ->first();

    if ($existingPending) {
        return response()->json([
            'error' => 'This tenant already has a pending invoice (#' . $existingPending->invoice_number . '). '
                     . 'You can resend it from the Invoices section.'
        ], 422);
    }

    // Must have a subscription plan
    if (!$tenant->subscription_plan) {
        return response()->json(['error' => 'Tenant has no subscription plan assigned.'], 422);
    }

    $plan = DB::table('subscription_plans')
        ->where('id', $tenant->subscription_plan)
        ->first();

    if (!$plan) {
        return response()->json(['error' => 'Subscription plan not found.'], 404);
    }

    $amount   = (float) $plan->plan_amount;
    $currency = strtoupper($plan->plan_currency);

    $planJson = [
        'plan_name'          => $plan->plan_name,
        'plan_period'        => $plan->plan_period,
        'plan_period_name'   => $plan->plan_period_name,
        'plan_amount'        => $plan->plan_amount,
        'plan_currency'      => $plan->plan_currency,
        'plan_currency_name' => $plan->plan_currency_name ?? $plan->plan_currency,
        'plan_description'   => $plan->plan_description ?? 'Netacube Subscription',
    ];

    $paymentMethod = DB::table('payment_methods')->find($request->payment_method);
    if (!$paymentMethod) {
        return response()->json(['error' => 'Payment method not found.'], 400);
    }

    $paymentMethodJson = ['method_type' => $paymentMethod->method_type];

    if ($paymentMethod->method_type === 'Bank') {
        $paymentMethodJson += [
            'bank_name'          => $paymentMethod->bank_name,
            'account_name'       => $paymentMethod->account_name,
            'account_number'     => $paymentMethod->account_number,
            'account_type'       => $paymentMethod->account_type,
            'account_branch'     => $paymentMethod->account_branch,
            //'account_swift_code' => $paymentMethod->account_swift_code,
        ];
    } elseif ($paymentMethod->method_type === 'Mobile') {
        $paymentMethodJson += [
            'mobile_operator'    => $paymentMethod->mobile_operator,
            'mobile_number'      => $paymentMethod->mobile_number,
            'mobile_number_name' => $paymentMethod->mobile_number_name,
        ];
    } elseif ($paymentMethod->method_type === 'Paypal') {
        $paymentMethodJson += [
            'paypal_name'     => $paymentMethod->paypal_name,
            'paypal_email'    => $paymentMethod->paypal_email,
            'paypal_me_link'  => $paymentMethod->paypal_me_link,
        ];
    }

    try {
        $year = now()->format('Y');

        $count = DB::table('tenant_invoices')->whereYear('created_at', $year)->count() + 1;

        $invoiceNumber = "INV-{$year}-" . str_pad($count, 6, '0', STR_PAD_LEFT);

        $dueDate = $tenant->next_payment_date 
            ? Carbon::parse($tenant->next_payment_date) 
            : now()->addDays(14);

        $invoiceData = [
            'tenant_id'      => $tenant->id,
            'invoice_number' => $invoiceNumber,
            'amount'         => $amount,
            'currency'       => $currency,
            'description'    => null,
            'plan'           => json_encode($planJson),
            'payment_method' => json_encode($paymentMethodJson),
            'status'         => 'Pending',
            'due_date'       => $dueDate,
            'created_at'     => now(),
            'updated_at'     => now(),
        ];

        DB::table('tenant_invoices')->insert($invoiceData);

        $invoice = (object) array_merge($invoiceData, [
            'id' => DB::getPdo()->lastInsertId(),
        ]);

        $pdf = Pdf::loadView('master.tenants.invoices.tenant-invoice-pdf', [
            'tenant'  => $tenant,
            'invoice' => $invoice,
        ])->setPaper('a4')->setOptions(['defaultFont' => 'DejaVu Sans']);

        $emailData = [
            'tenant'         => $tenant,
            'invoice'        => $invoice,
            'full_name'      => $tenant->full_name,
            'business_name'  => $tenant->business_name ?? 'Personal',
            'email'          => $tenant->email,
            'phone_number'   => $tenant->phone_number ?? '',
            'amount'         => $amount,
            'currency'       => $currency,
            'invoice_number' => $invoiceNumber,
            'current_date'   => now()->format('d M Y'),
            'due_date'       => $dueDate->format('d M Y'),
            'plan'           => $planJson,
            'payment_method' => $paymentMethodJson,
        ];

        try {
            Mail::send('master.tenants.invoices.tenant-invoice-email', $emailData, function ($message) use ($tenant, $pdf, $invoiceNumber) {
                $message->to($tenant->email)
                        ->subject('Invoice ' . $invoiceNumber . ' - ' . config('app.name'))
                        ->attachData($pdf->output(), "Invoice-{$invoiceNumber}.pdf", [
                            'mime' => 'application/pdf',
                        ]);
            });
        } catch (\Exception $mailException) {
            \Log::error('Invoice email failed: ' . $mailException->getMessage());
            return response()->json([
                'success' => 'Invoice created but email failed to send.',
                'invoice_number' => $invoiceNumber
            ], 201);
        }

        return response()->json([
            'success'        => 'Invoice created and sent successfully!',
            'invoice_number' => $invoiceNumber
        ], 201);

    } catch (\Exception $e) {
        \Log::error('Invoice creation/send failed: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to create and send invoice.'], 500);
    }
}

public function tenantInvoicePdfPreview($id)
{
    $invoice = DB::table('tenant_invoices')->find($id);

    if (!$invoice) {
        return response()->json([
            'success' => false,
            'message' => 'Invoice not found.'
        ], 404);
    }

    $tenant = DB::table('tenants')->find($invoice->tenant_id);

    if (!$tenant) {
        return response()->json([
            'success' => false,
            'message' => 'Tenant not found.'
        ], 404);
    }

    $pdf = Pdf::loadView('master.tenants.invoices.tenant-invoice-pdf', compact('invoice', 'tenant'))
              ->setPaper('a4')
              ->setOptions(['defaultFont' => 'DejaVu Sans']);

    return $pdf->stream('invoice_' . $invoice->invoice_number . '.pdf');
}

public function tenantInvoiceDownloadPdf($id)
{
    $invoice = DB::table('tenant_invoices')->find($id);

    if (!$invoice) {
        return response()->json([
            'success' => false,
            'message' => 'Invoice not found.'
        ], 404);
    }

    $tenant = DB::table('tenants')->find($invoice->tenant_id);

    if (!$tenant) {
        return response()->json([
            'success' => false,
            'message' => 'Tenant not found.'
        ], 404);
    }

    $pdf = Pdf::loadView('master.tenants.invoices.tenant-invoice-pdf', compact('invoice', 'tenant'))
              ->setPaper('a4')
              ->setOptions(['defaultFont' => 'DejaVu Sans']);

    return $pdf->download('invoice_' . $invoice->invoice_number . '.pdf');
}

public function tenantInvoiceMarkAsPaid($id, Request $request)
{
    $request->validate(['days' => 'required|integer|min:1']);

    $invoice = DB::table('tenant_invoices')->find($id);

    if (!$invoice) {
        return response()->json(['error' => 'Invoice not found.'], 404);
    }

    // Parse the invoice's original due date
    $currentDueDate = Carbon::parse($invoice->due_date);

    // Calculate the new next payment date
    $newNextPaymentDate = $currentDueDate->copy()->addDays($request->days);

    // Use transaction to ensure all updates succeed or none do
    DB::transaction(function () use ($invoice, $currentDueDate, $newNextPaymentDate) {
        // 1. Mark the invoice as Paid (do NOT change its due_date)
        DB::table('tenant_invoices')
            ->where('id', $invoice->id)
            ->update([
                'status'     => 'Paid',
                'updated_at' => now(),
            ]);

        // 2. Update the tenant record
        DB::table('tenants')
            ->where('id', $invoice->tenant_id)
            ->update([
                'put_on_hold'       => 'No',                         
                'next_payment_date' => $newNextPaymentDate,
                'last_payment_date' => $currentDueDate,               
                'updated_at'        => now(),
            ]);
    });

    return response()->json([
        'success'      => 'Invoice marked as paid successfully.',
        'new_due_date' => $newNextPaymentDate->format('d M Y'),
        'message'      => "Tenant status set to Approved. Next payment due on {$newNextPaymentDate->format('d M Y')}",
    ]);
}


    public function tenantInvoiceCancel($id)
    {
        $invoice = DB::table('tenant_invoices')->find($id);
        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found.'], 404);
        }

        DB::table('tenant_invoices')->where('id', $id)->update([
            'status'     => 'Cancelled',
            'updated_at' => now()
        ]);

        return response()->json(['success' => 'Invoice cancelled successfully.']);
    }

    public function tenantSendInvoiceFromInvoicesTable($id)
    {
        $invoice = DB::table('tenant_invoices')->find($id);
        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found.'], 404);
        }

        $tenant = DB::table('tenants')->find($invoice->tenant_id);
        if (!$tenant || empty($tenant->email)) {
            return response()->json(['error' => 'Tenant email not found.'], 400);
        }

        try {
            $pdfData = [
                'tenant'         => $tenant,
                'invoice'        => $invoice,
                'full_name'      => $tenant->full_name,
                'business_name'  => $tenant->business_name ?? 'Personal',
                'email'          => $tenant->email,
                'phone_number'   => $tenant->phone_number ?? '',
                'amount'         => $invoice->amount,
                'currency'       => $invoice->currency ?? 'MWK',
                'invoice_number' => $invoice->invoice_number,
                'current_date'   => Carbon::parse($invoice->created_at)->format('d M Y'),
                'due_date'       => $invoice->due_date ? Carbon::parse($invoice->due_date)->format('d M Y') : 'N/A',
                'status'         => $invoice->status,
                'description'    => $invoice->description ?? '',
                'plan'           => $invoice->plan ? json_decode($invoice->plan, true) : null,
                'payment_method' => $invoice->payment_method ? json_decode($invoice->payment_method, true) : null,
            ];

            $pdf = Pdf::loadView('master.tenants.invoices.tenant-invoice-pdf', $pdfData)
                      ->setPaper('a4')
                      ->setOptions(['defaultFont' => 'DejaVu Sans']);

            Mail::send('master.tenants.invoices.tenant-invoice-email', $pdfData, function ($message) use ($tenant, $pdf, $invoice) {
                $message->to($tenant->email)
                        ->subject('Invoice #' . $invoice->invoice_number . ' - ' . config('app.name'))
                        ->attachData($pdf->output(), "Invoice-{$invoice->invoice_number}.pdf", [
                            'mime' => 'application/pdf'
                        ]);
            });

            return response()->json(['success' => 'Invoice sent successfully to ' . $tenant->email]);

        } catch (\Exception $e) {
            \Log::error('Failed to resend invoice email: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['error' => 'Failed to send email. Please try again.'], 500);
        }
    }
}