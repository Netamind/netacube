<?php

namespace App\Http\Controllers\Operations\Retail;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use DB;
use Auth;

class OperationSalesController extends Controller
{
    // ── View ─────────────────────────────────────────────────────────────

    public function showTodaysSalesView()
    {
        return view('operations.retail.sales.today');
    }

    
    public function showSalesHistoryView()
    {
        return view('operations.retail.sales.history');
    }

    // ── Update single sale ────────────────────────────────────────────────
    // Quantity may only be REDUCED (or set to 0 for full reversal).
    // The difference between the currently active qty (quantity - rquantity)
    // and the new qty is credited back to stock, rounded to 2 decimal places.
    // We update rquantity rather than quantity so the original sold amount
    // is preserved as an audit record.
    // POST /operations/retail/sales/update

    public function updateSale(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'       => 'required|integer',
            'price'    => 'required|numeric|min:0',
            'quantity' => 'required|numeric|min:0',   // 0 = full reversal allowed
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error' => implode(', ', $validator->errors()->all())]);
        }

        $pref = DB::connection('tenant')->table('user_filters')->where('user_id', Auth::id())->first();
        if (!$pref || !$pref->branch_id) {
            return response()->json(['status' => 0, 'error' => 'No branch selected.']);
        }

        try {
            $sale = DB::connection('tenant')
                ->table('retail_system_sales as s')
                ->join('retail_branch_products as rbp', 'rbp.id', '=', 's.branch_product_id')
                ->where('s.id', $request->id)
                ->where('rbp.branch_id', $pref->branch_id)
                ->select('s.*', 'rbp.id as rbp_id')
                ->first();

            if (!$sale) {
                return response()->json(['status' => 0, 'error' => 'Sale not found or not in your branch.']);
            }

            // activeQty = what is currently deducted from stock.
            // If this sale was previously partially reversed, rquantity holds
            // the already-restored portion — only (quantity - rquantity) is
            // still deducted from stock and is the ceiling for the new qty.
            $activeQty = round((float)$sale->quantity - (float)$sale->rquantity, 2);
            $newQty    = round((float)$request->quantity, 2);
            $newPrice  = round((float)$request->price, 2);

            // Stock to restore = reduction in active qty
            $stockRestored = round($activeQty - $newQty, 2);

            if ($newQty > $activeQty) {
                return response()->json([
                    'status' => 0,
                    'error'  => 'Quantity cannot exceed the currently active quantity of ' . number_format($activeQty, 2) . '.',
                ]);
            }

            DB::connection('tenant')->beginTransaction();

            if ($stockRestored > 0) {
                // DB::raw avoids PHP float drift accumulating in MySQL
                DB::connection('tenant')
                    ->table('retail_branch_products')
                    ->where('id', $sale->rbp_id)
                    ->update([
                        'stock_quantity' => DB::raw('ROUND(stock_quantity + ' . $stockRestored . ', 2)'),
                    ]);
            }

            if ($newQty <= 0) {
                // Reduced to zero = full reversal via the edit modal.
                // Same outcome as the bulk Reverse action: delete outright
                // rather than leaving a zeroed-out audit row.
                DB::connection('tenant')->table('retail_system_sales')->where('id', $sale->id)->delete();

                DB::connection('tenant')->commit();

                return response()->json(['status' => 201, 'deleted' => true, 'id' => $sale->id]);
            }

            // Keep quantity (original sold) as the audit record — only touch
            // rquantity and price. New rquantity = everything not being kept.
            $newRqty = round((float)$sale->quantity - $newQty, 2);

            DB::connection('tenant')->table('retail_system_sales')->where('id', $sale->id)->update([
                'price'      => $newPrice,
                'rquantity'  => $newRqty,
                'qty_after'  => round((float)$sale->qty_after + $stockRestored, 2),
                'updated_at' => now(),
            ]);

            DB::connection('tenant')->commit();

            $updated = DB::connection('tenant')
                ->table('retail_system_sales as s')
                ->leftJoin('users as u', 'u.id', '=', DB::raw("CAST(s.user AS UNSIGNED)"))
                ->where('s.id', $sale->id)
                ->select('s.*', 'u.name as user_name')
                ->first();

            return response()->json(['status' => 201, 'row' => $updated]);

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::error('updateSale: ' . $e->getMessage());
            return response()->json(['status' => 0, 'error' => 'Server error. Please try again.']);
        }
    }

    // ── Reverse selected sales ────────────────────────────────────────────
    // POST /operations/retail/sales/reverse

    public function reverseSales(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids'      => 'required|array|min:1|max:50',
            'ids.*'    => 'integer',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error' => implode(', ', $validator->errors()->all())]);
        }

        $user = DB::connection('tenant')->table('users')->where('id', Auth::id())->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['status' => 0, 'error' => 'Incorrect password.']);
        }

        $pref = DB::connection('tenant')->table('user_filters')->where('user_id', Auth::id())->first();
        if (!$pref || !$pref->branch_id) {
            return response()->json(['status' => 0, 'error' => 'No branch selected.']);
        }

        try {
            $sales = DB::connection('tenant')
                ->table('retail_system_sales as s')
                ->join('retail_branch_products as rbp', 'rbp.id', '=', 's.branch_product_id')
                ->whereIn('s.id', $request->ids)
                ->where('rbp.branch_id', $pref->branch_id)
                ->select('s.id', 's.quantity', 's.rquantity', 'rbp.id as rbp_id')
                ->get();

            if ($sales->isEmpty()) {
                return response()->json(['status' => 0, 'error' => 'No matching sales found.']);
            }

            DB::connection('tenant')->beginTransaction();

            foreach ($sales as $sale) {
                // Only restore what is still active (not yet reversed)
                $reverseQty = round((float)$sale->quantity - (float)$sale->rquantity, 2);

                if ($reverseQty > 0) {
                    DB::connection('tenant')->table('retail_branch_products')
                        ->where('id', $sale->rbp_id)
                        ->update([
                            'stock_quantity' => DB::raw('ROUND(stock_quantity + ' . $reverseQty . ', 2)'),
                        ]);
                }

                // Reversal is final — remove the sale outright rather than
                // keeping a zeroed-out audit row via rquantity.
                DB::connection('tenant')->table('retail_system_sales')->where('id', $sale->id)->delete();
            }

            DB::connection('tenant')->commit();

            return response()->json(['status' => 201, 'reversed' => $sales->pluck('id')]);

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::error('reverseSales: ' . $e->getMessage());
            return response()->json(['status' => 0, 'error' => 'Server error. Please try again.']);
        }
    }

    // ── Change date on selected sales ─────────────────────────────────────
    // POST /operations/retail/sales/change-date

    public function changeSalesDate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids'      => 'required|array|min:1|max:50',
            'ids.*'    => 'integer',
            'date'     => 'required|date_format:Y-m-d',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error' => implode(', ', $validator->errors()->all())]);
        }

        $user = DB::connection('tenant')->table('users')->where('id', Auth::id())->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['status' => 0, 'error' => 'Incorrect password.']);
        }

        $pref = DB::connection('tenant')->table('user_filters')->where('user_id', Auth::id())->first();
        if (!$pref || !$pref->branch_id) {
            return response()->json(['status' => 0, 'error' => 'No branch selected.']);
        }

        try {
            $affected = DB::connection('tenant')
                ->table('retail_system_sales as s')
                ->join('retail_branch_products as rbp', 'rbp.id', '=', 's.branch_product_id')
                ->whereIn('s.id', $request->ids)
                ->where('rbp.branch_id', $pref->branch_id)
                ->pluck('s.id');

            if ($affected->isEmpty()) {
                return response()->json(['status' => 0, 'error' => 'No matching sales found.']);
            }

            DB::connection('tenant')->table('retail_system_sales')
                ->whereIn('id', $affected)
                ->update(['date' => $request->date, 'updated_at' => now()]);

            return response()->json(['status' => 201, 'changed' => $affected]);

        } catch (\Exception $e) {
            Log::error('changeSalesDate: ' . $e->getMessage());
            return response()->json(['status' => 0, 'error' => 'Server error. Please try again.']);
        }
    }

    // ── Update interval sale ──────────────────────────────────────────────
    // POST /operations/retail/sales/interval/update

    public function updateIntervalSale(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'    => 'required|integer',
            'sales' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error' => implode(', ', $validator->errors()->all())]);
        }

        $pref = DB::connection('tenant')->table('user_filters')->where('user_id', Auth::id())->first();
        if (!$pref || !$pref->branch_id) {
            return response()->json(['status' => 0, 'error' => 'No branch selected.']);
        }

        $rows = DB::connection('tenant')->table('retail_interval_sales')
            ->where('id', $request->id)
            ->where('branch_id', $pref->branch_id)
            ->update(['sales' => $request->sales, 'updated_at' => now()]);

        if (!$rows) {
            return response()->json(['status' => 0, 'error' => 'Record not found or no change.']);
        }

        return response()->json(['status' => 201]);
    }

    // ── Delete interval sale ──────────────────────────────────────────────
    // POST /operations/retail/sales/interval/delete

    public function deleteIntervalSale(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 0, 'error' => implode(', ', $validator->errors()->all())]);
        }

        $pref = DB::connection('tenant')->table('user_filters')->where('user_id', Auth::id())->first();
        if (!$pref || !$pref->branch_id) {
            return response()->json(['status' => 0, 'error' => 'No branch selected.']);
        }

        DB::connection('tenant')->table('retail_interval_sales')
            ->where('id', $request->id)
            ->where('branch_id', $pref->branch_id)
            ->delete();

        return response()->json(['status' => 201]);
    }
}