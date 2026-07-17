<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MasterCurrencyReportController extends Controller
{
    /**
     * Renders the filter shell (year + currency selects + results table).
     * Actual row data is loaded via AJAX from getTenantsByCurrency().
     */
    public function showTenantsByCurrencyView()
    {
        $currencies = DB::table('currency')->orderBy('name')->get();

        // Build a sensible list of years to pick from, based on tenants'
        // next_payment_date, always including the current year even if no
        // tenant has a due date in it yet.
        $years = DB::table('tenants')
            ->selectRaw('YEAR(next_payment_date) as yr')
            ->whereNotNull('next_payment_date')
            ->distinct()
            ->pluck('yr')
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->values();

        return view('master.tenants.tenants-by-currency', compact('currencies', 'years'));
    }

    /**
     * AJAX: returns every tenant whose next payment (due date) falls within
     * the given year and whose billing currency matches the given currency -
     * custom_currency for custom-pricing tenants, otherwise their
     * subscription plan's currency.
     *
     * Also attaches the most recent matching tenant_invoices row (if any) so
     * we can show a real Paid / Not Paid status and the invoice id, without
     * requiring a fresh invoice to already exist for every tenant.
     */
    public function getTenantsByCurrency(Request $request)
    {
        $request->validate([
            'year'     => 'required|integer|digits:4',
            'currency' => 'required|string|exists:currency,code',
        ]);

        $year     = (int) $request->year;
        $currency = strtoupper($request->currency);

        $rows = DB::table('tenants as t')
            ->leftJoin('subscription_plans as p', 'p.id', '=', 't.subscription_plan')
            ->whereYear('t.next_payment_date', $year)
            ->where(function ($q) use ($currency) {
                $q->where(function ($q2) use ($currency) {
                    $q2->where('t.custom_pricing_enabled', true)
                       ->where('t.custom_currency', $currency);
                })->orWhere(function ($q2) use ($currency) {
                    $q2->where('t.custom_pricing_enabled', false)
                       ->whereRaw('UPPER(p.plan_currency) = ?', [$currency]);
                });
            })
            ->select([
                't.id', 't.full_name', 't.business_name', 't.next_payment_date',
                't.custom_pricing_enabled', 't.custom_amount', 't.custom_currency',
                'p.plan_name', 'p.plan_period', 'p.plan_amount', 'p.plan_currency',
            ])
            ->orderBy('t.next_payment_date')
            ->get();

        $tenantIds = $rows->pluck('id');

        // Most recent invoice per tenant that matches this currency/year -
        // used purely to decide the Paid / Not Paid badge and to let the
        // "Generate Invoice" button know an invoice already exists.
        $latestInvoices = DB::table('tenant_invoices')
            ->whereIn('tenant_id', $tenantIds)
            ->where('currency', $currency)
            ->whereYear('due_date', $year)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('tenant_id')
            ->map(fn ($group) => $group->first());

        $totalAmount = 0.0;

        $tenants = $rows->map(function ($row) use ($latestInvoices, &$totalAmount) {
            $amount = $row->custom_pricing_enabled
                ? (float) $row->custom_amount
                : (float) $row->plan_amount;

            $invoice = $latestInvoices->get($row->id);
            $status  = ($invoice && $invoice->status === 'Paid') ? 'Paid' : 'Not Paid';

            $totalAmount += $amount;

            return [
                'tenant_id'   => $row->id,
                'tenant_name' => $row->full_name . ($row->business_name ? ' (' . $row->business_name . ')' : ''),
                'plan_name'   => $row->plan_name,
                'amount'      => number_format($amount, 2),
                'due_date'    => $row->next_payment_date ? Carbon::parse($row->next_payment_date)->format('d M Y') : 'N/A',
                'status'      => $status,
                'invoice_id'  => $invoice->id ?? null,
            ];
        })->values();

        return response()->json([
            'status'       => 200,
            'currency'     => $currency,
            'year'         => $year,
            'count'        => $tenants->count(),
            'total_amount' => number_format($totalAmount, 2),
            'tenants'      => $tenants,
        ]);
    }
}