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
            'send_email'     => 'nullable|boolean',
        ]);

        $sendEmail = $request->boolean('send_email');

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

        // Custom-pricing tenants are invoiced using their own amount/currency
        // instead of the plan's. The plan name/description shown on the
        // invoice always still comes from the subscription plan - we only
        // ever override the money, never the plan identity, so the tenant
        // details view keeps showing "subscription plan only, no amounts".
        if ($tenant->custom_pricing_enabled) {
            if (!$tenant->custom_amount || !$tenant->custom_currency) {
                return response()->json([
                    'error' => 'Custom pricing is enabled for this tenant but no custom amount/currency has been set. Set it first from Tenant Actions.'
                ], 422);
            }
            $amount   = (float) $tenant->custom_amount;
            $currency = strtoupper($tenant->custom_currency);
        } else {
            $amount   = (float) $plan->plan_amount;
            $currency = strtoupper($plan->plan_currency);
        }

        $planJson = [
            'plan_name'          => $plan->plan_name,
            'plan_period'        => ($tenant->custom_pricing_enabled && $tenant->custom_period_days)
                ? $tenant->custom_period_days . ' days'
                : $plan->plan_period,
            'plan_period_name'   => ($tenant->custom_pricing_enabled && $tenant->custom_period_name)
                ? $tenant->custom_period_name
                : $plan->plan_period_name,
            'plan_amount'        => (string) $amount,
            'plan_currency'      => $currency,
            'plan_currency_name' => $plan->plan_currency_name ?? $currency,
            'plan_description'   => $plan->plan_description ?? 'Netacube Subscription',
        ];

        $paymentMethod = DB::table('payment_methods')->find($request->payment_method);
        if (!$paymentMethod) {
            return response()->json(['error' => 'Payment method not found.'], 400);
        }

        $paymentMethodJson = ['method_type' => $paymentMethod->method_type];

        if ($paymentMethod->method_type === 'Bank') {
            $paymentMethodJson += [
                'bank_name'      => $paymentMethod->bank_name,
                'account_name'   => $paymentMethod->account_name,
                'account_number' => $paymentMethod->account_number,
                'account_type'   => $paymentMethod->account_type,
                'account_branch' => $paymentMethod->account_branch,
            ];
        } elseif ($paymentMethod->method_type === 'Mobile') {
            $paymentMethodJson += [
                'mobile_operator'    => $paymentMethod->mobile_operator,
                'mobile_number'      => $paymentMethod->mobile_number,
                'mobile_number_name' => $paymentMethod->mobile_number_name,
            ];
        } elseif ($paymentMethod->method_type === 'Paypal') {
            $paymentMethodJson += [
                'paypal_name'    => $paymentMethod->paypal_name,
                'paypal_email'   => $paymentMethod->paypal_email,
                'paypal_me_link' => $paymentMethod->paypal_me_link,
            ];
        }

        try {
            $year  = now()->format('Y');
            $count = DB::table('tenant_invoices')->whereYear('created_at', $year)->count() + 1;
            $invoiceNumber = "INV-{$year}-" . str_pad($count, 6, '0', STR_PAD_LEFT);

            // FIX: Only use next_payment_date if it is strictly in the future.
            // If the stored date is today or already past, it would immediately
            // land in the Overdue bucket. Fall back to a billing-cycle length:
            // the tenant's own custom_period_days if custom pricing set one,
            // otherwise the subscription plan's plan_days, otherwise 14 days.
            $parsedNextPayment = $tenant->next_payment_date
                ? Carbon::parse($tenant->next_payment_date)
                : null;

            $fallbackDays = ($tenant->custom_pricing_enabled && $tenant->custom_period_days)
                ? (int) $tenant->custom_period_days
                : ((int) ($plan->plan_days ?? 0) ?: 14);

            $dueDate = ($parsedNextPayment && $parsedNextPayment->isFuture())
                ? $parsedNextPayment
                : now()->addDays($fallbackDays);

            $invoiceData = [
                'tenant_id'      => $tenant->id,
                'invoice_number' => $invoiceNumber,
                'amount'         => $amount,
                'currency'       => $currency,
                'description'    => null,
                'plan'           => json_encode($planJson),
                'payment_method' => json_encode($paymentMethodJson),
                'status'         => 'Pending',
                'due_date'       => $dueDate->toDateString(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ];

            DB::table('tenant_invoices')->insert($invoiceData);

            $invoice = (object) array_merge($invoiceData, [
                'id' => DB::getPdo()->lastInsertId(),
            ]);

            // Invoice is now generated and recorded regardless of whether it gets emailed.
            if (!$sendEmail) {
                return response()->json([
                    'success'        => 'Invoice generated successfully. It has not been emailed to the tenant.',
                    'invoice_number' => $invoiceNumber
                ], 201);
            }

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
                    'success'        => 'Invoice generated but email failed to send.',
                    'invoice_number' => $invoiceNumber
                ], 201);
            }

            return response()->json([
                'success'        => 'Invoice generated and sent successfully!',
                'invoice_number' => $invoiceNumber
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Invoice creation/send failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate invoice.'], 500);
        }
    }


    /**
     * Preview Invoice PDF - Loads correct view (Custom or System)
     */
    public function tenantInvoicePdfPreview($id)
    {
        $invoice = DB::table('tenant_invoices')->find($id);
        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found.'], 404);
        }

        $tenant = DB::table('tenants')->find($invoice->tenant_id);
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Tenant not found.'], 404);
        }

        $planData = is_string($invoice->plan) ? json_decode($invoice->plan, true) : [];
        $planName = $planData['plan_name'] ?? '';

        \Log::info('Invoice PDF Preview - ID: ' . $id, [
            'plan_json' => $invoice->plan,
            'plan_name' => $planName,
            'isCustom'  => ($planName === 'Custom') ? 'YES' : 'NO'
        ]);

        if ($planName === 'Custom') {
            $pdf = Pdf::loadView('master.tenants.invoices.custom-tenant-invoice-pdf', compact('invoice', 'tenant'))
                      ->setPaper('a4')
                      ->setOptions(['defaultFont' => 'DejaVu Sans']);
        } else {
            $pdf = Pdf::loadView('master.tenants.invoices.tenant-invoice-pdf', compact('invoice', 'tenant'))
                      ->setPaper('a4')
                      ->setOptions(['defaultFont' => 'DejaVu Sans']);
        }

        return $pdf->stream('invoice_' . $invoice->invoice_number . '.pdf');
    }

    /**
     * Download Invoice PDF - Loads correct view (Custom or System)
     */
    public function tenantInvoiceDownloadPdf($id)
    {
        $invoice = DB::table('tenant_invoices')->find($id);
        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found.'], 404);
        }

        $tenant = DB::table('tenants')->find($invoice->tenant_id);
        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Tenant not found.'], 404);
        }

        $planData = is_string($invoice->plan) ? json_decode($invoice->plan, true) : [];
        $planName = $planData['plan_name'] ?? '';

        \Log::info('Invoice PDF Download - ID: ' . $id, [
            'plan_json' => $invoice->plan,
            'plan_name' => $planName,
            'isCustom'  => ($planName === 'Custom') ? 'YES' : 'NO'
        ]);

        if ($planName === 'Custom') {
            $pdf = Pdf::loadView('master.tenants.invoices.custom-tenant-invoice-pdf', compact('invoice', 'tenant'))
                      ->setPaper('a4')
                      ->setOptions(['defaultFont' => 'DejaVu Sans']);
        } else {
            $pdf = Pdf::loadView('master.tenants.invoices.tenant-invoice-pdf', compact('invoice', 'tenant'))
                      ->setPaper('a4')
                      ->setOptions(['defaultFont' => 'DejaVu Sans']);
        }

        return $pdf->download('invoice_' . $invoice->invoice_number . '.pdf');
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
            $planData = is_string($invoice->plan) ? json_decode($invoice->plan, true) : null;
            $planName = $planData['plan_name'] ?? '';

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
                'plan'           => $planData,
                'payment_method' => $invoice->payment_method ? json_decode($invoice->payment_method, true) : null,
            ];

            // Route this generated-but-unsent invoice through the correct template,
            // matching the same Custom/System check used for preview & download.
            $view = ($planName === 'Custom')
                ? 'master.tenants.invoices.custom-tenant-invoice-pdf'
                : 'master.tenants.invoices.tenant-invoice-pdf';

            $emailView = ($planName === 'Custom')
                ? 'master.tenants.invoices.custom-tenant-invoice-email'
                : 'master.tenants.invoices.tenant-invoice-email';

            $pdf = Pdf::loadView($view, $pdfData)
                      ->setPaper('a4')
                      ->setOptions(['defaultFont' => 'DejaVu Sans']);

            Mail::send($emailView, $pdfData, function ($message) use ($tenant, $pdf, $invoice) {
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


    /**
     * Generate CUSTOM Invoice, optionally emailing it (Separate method)
     */
    public function masterSendCustomInvoice(Request $request)
    {
        $request->validate([
            'tenant_id'      => 'required|exists:tenants,id',
            'payment_method' => 'required|exists:payment_methods,id',
            'description'    => 'required|string|max:1000',
            'amount'         => 'required|numeric|min:0.01',
            'currency'       => 'required|exists:currency,code',
            'due_date'       => 'nullable|date|after_or_equal:today',
            'send_email'     => 'nullable|boolean',
        ]);

        $sendEmail = $request->boolean('send_email');

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
                         . 'Please resolve it first.'
            ], 422);
        }

        $paymentMethod = DB::table('payment_methods')->find($request->payment_method);
        if (!$paymentMethod) {
            return response()->json(['error' => 'Payment method not found.'], 400);
        }

        // Build Payment Method JSON
        $paymentMethodJson = ['method_type' => $paymentMethod->method_type];

        if ($paymentMethod->method_type === 'Bank') {
            $paymentMethodJson += [
                'bank_name'      => $paymentMethod->bank_name,
                'account_name'   => $paymentMethod->account_name,
                'account_number' => $paymentMethod->account_number,
                'account_type'   => $paymentMethod->account_type,
                'account_branch' => $paymentMethod->account_branch,
            ];
        } elseif ($paymentMethod->method_type === 'Mobile') {
            $paymentMethodJson += [
                'mobile_operator'    => $paymentMethod->mobile_operator,
                'mobile_number'      => $paymentMethod->mobile_number,
                'mobile_number_name' => $paymentMethod->mobile_number_name,
            ];
        } elseif ($paymentMethod->method_type === 'Paypal') {
            $paymentMethodJson += [
                'paypal_name'    => $paymentMethod->paypal_name,
                'paypal_email'   => $paymentMethod->paypal_email,
                'paypal_me_link' => $paymentMethod->paypal_me_link,
            ];
        }

        $amount      = (float) $request->amount;
        $currency    = strtoupper($request->currency);
        $description = trim($request->description);

        // FIX: If the user supplied a due_date from the form, use it (validation already
        // ensures it is after_or_equal:today). If they left it blank, default to +14 days.
        // Either way we never store a date that is already in the past.
        $dueDate = $request->filled('due_date')
            ? Carbon::parse($request->due_date)
            : now()->addDays(14);

        try {
            $year  = now()->format('Y');
            $count = DB::table('tenant_invoices')->whereYear('created_at', $year)->count() + 1;
            $invoiceNumber = "INV-{$year}-" . str_pad($count, 6, '0', STR_PAD_LEFT);

            $customPlanJson = json_encode([
                'plan_name'        => 'Custom',
                'plan_period'      => 'One-time',
                'plan_period_name' => 'Custom',
                'plan_amount'      => (string) $amount,
                'plan_currency'    => $currency,
                'plan_description' => $description,
            ]);

            $invoiceData = [
                'tenant_id'      => $tenant->id,
                'invoice_number' => $invoiceNumber,
                'amount'         => $amount,
                'currency'       => $currency,
                'description'    => $description,
                'plan'           => $customPlanJson,
                'payment_method' => json_encode($paymentMethodJson),
                'status'         => 'Pending',
                'due_date'       => $dueDate->toDateString(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ];

            DB::table('tenant_invoices')->insert($invoiceData);

            $invoice = (object) array_merge($invoiceData, [
                'id' => DB::getPdo()->lastInsertId(),
            ]);

            // Invoice is now generated and recorded regardless of whether it gets emailed.
            if (!$sendEmail) {
                return response()->json([
                    'success'        => 'Custom invoice generated successfully. It has not been emailed to the tenant.',
                    'invoice_number' => $invoiceNumber
                ], 201);
            }

            $data = [
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
                'description'    => $description,
                'payment_method' => $paymentMethodJson,
            ];

            $pdf = Pdf::loadView('master.tenants.invoices.custom-tenant-invoice-pdf', $data)
                      ->setPaper('a4')
                      ->setOptions(['defaultFont' => 'DejaVu Sans']);

            try {
                Mail::send('master.tenants.invoices.custom-tenant-invoice-email', $data, function ($message) use ($tenant, $pdf, $invoiceNumber) {
                    $message->to($tenant->email)
                            ->subject('Invoice ' . $invoiceNumber . ' - Netamind Technology')
                            ->attachData($pdf->output(), "Invoice-{$invoiceNumber}.pdf", [
                                'mime' => 'application/pdf',
                            ]);
                });
            } catch (\Exception $mailException) {
                \Log::error('Custom invoice email failed: ' . $mailException->getMessage());
                return response()->json([
                    'success'        => 'Custom invoice generated but email failed to send.',
                    'invoice_number' => $invoiceNumber
                ], 201);
            }

            return response()->json([
                'success'        => 'Custom invoice generated and sent successfully!',
                'invoice_number' => $invoiceNumber
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Custom Invoice failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate custom invoice.'], 500);
        }
    }
}