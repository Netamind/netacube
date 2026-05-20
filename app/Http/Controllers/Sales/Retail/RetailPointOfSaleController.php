<?php

namespace App\Http\Controllers\Sales\Retail;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DB;
use Auth;

class RetailPointOfSaleController extends Controller
{

    // ══════════════════════════════════════════════════════════════════════
    // VIEWS
    // ══════════════════════════════════════════════════════════════════════

    public function showMobilePosView()
    {
        return view('sales.retail.pos.mobile');
    }

    public function showDesktopPosView()
    {
        return view('sales.retail.pos.desktop');
    }

    // ══════════════════════════════════════════════════════════════════════
    // UPLOAD SALES
    // Accepts the JSON blob from localStorage, inserts into
    // retail_system_sales, decrements retail_branch_products.stock_quantity,
    // and returns whatever rows could NOT be inserted so the client can
    // keep them pending.
    // ══════════════════════════════════════════════════════════════════════

    public function uploadSales(Request $request)
    {
        $request->validate(['data' => 'required|string']);

        $rows = json_decode($request->data, true);

        if (!is_array($rows) || empty($rows)) {
            return response()->json([]);
        }

        $failed = [];

        foreach ($rows as $row) {
            try {
                $branchProductId = $row['branch_product_id'] ?? null;

                if (!$branchProductId) {
                    $failed[] = $row;
                    continue;
                }

                $branchProduct = DB::connection('tenant')
                    ->table('retail_branch_products')
                    ->where('id', $branchProductId)
                    ->first();

                if (!$branchProduct) {
                    $failed[] = $row;
                    continue;
                }

                $qtySold   = (float) ($row['quantity']   ?? 0);
                $qtyBefore = (float) $branchProduct->stock_quantity;
                $qtyAfter  = max(0, $qtyBefore - $qtySold);

                $inserted = DB::connection('tenant')
                    ->table('retail_system_sales')
                    ->insertOrIgnore([
                        'transid'           => $row['transid']        ?? '',
                        'date'              => $row['date']           ?? now()->toDateString(),
                        'time'              => $row['time']           ?? now()->toTimeString(),
                        'branch_product_id' => $branchProductId,
                        'product'           => $row['product']        ?? '',
                        'unit'              => $row['unit']           ?? '',
                        'price'             => $row['price']          ?? 0,
                        'user'              => $row['user']           ?? '',
                        'branch'            => $row['branch']         ?? '',
                        'quantity'          => $qtySold,
                        'rquantity'         => 0,
                        'qty_before'        => $qtyBefore,
                        'qty_sold'          => $qtySold,
                        'qty_after'         => $qtyAfter,
                        'payment_method'    => $row['payment_method'] ?? 'cash',
                        'amount_paid'       => $row['amount_paid']    ?? null,
                        'slot'              => $row['slot']           ?? '0',
                        'device_name'       => $row['device_name']    ?? null,
                        'ip_address'        => $request->ip(),
                        'user_agent'        => $row['user_agent']     ?? null,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);

                if ($inserted) {
                    DB::connection('tenant')
                        ->table('retail_branch_products')
                        ->where('id', $branchProductId)
                        ->update([
                            'stock_quantity' => $qtyAfter,
                            'updated_at'     => now(),
                        ]);
                }
                // If insertOrIgnore returns 0 the row was a duplicate —
                // we still treat it as "done" and don't push to $failed,
                // so the client clears it.

            } catch (\Throwable $e) {
                \Log::error('uploadSales row failed: ' . $e->getMessage());
                $failed[] = $row;
            }
        }

        // Return the failed rows so the JS can keep them in localStorage.
        // An empty array means "all done — clear your pending queue".
        return response()->json($failed);
    }

    // ══════════════════════════════════════════════════════════════════════
    // INSERT INTERVAL SALE
    //
    // Wrapped in a DB transaction so the interval insert, the
    // retail_system_sales slot-stamping, and the retail_physical_cash
    // upsert either all succeed together or all roll back together.
    //
    // Responses use standard HTTP status codes + a JSON body:
    //   201 success
    //   404 interval / record not found
    //   409 duplicate (already logged today)
    //   422 validation error (handled automatically by ->validate())
    //   500 unexpected server error
    // ══════════════════════════════════════════════════════════════════════

    public function insertIntervalSale(Request $request)
    {
        $request->validate([
            'interval_id' => 'required|integer|exists:tenant.retail_intervals,id',
            'branch'      => 'required|integer',
            'date'        => 'required|date',
            'sales'       => 'required|numeric|min:0',
        ]);

        $sales      = (float) $request->sales;
        $branchId   = (int)   $request->branch;
        $date       = $request->date;
        $intervalId = (int)   $request->interval_id;
        $userId     = Auth::id();

        try {
            return DB::connection('tenant')->transaction(function () use ($sales, $branchId, $date, $intervalId, $userId) {

                $interval = DB::connection('tenant')
                    ->table('retail_intervals')
                    ->where('id', $intervalId)
                    ->first();

                if (!$interval) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Interval not found.',
                    ], 404);
                }

                $slotLabel = $interval->slot;

                $inserted = DB::connection('tenant')
                    ->table('retail_interval_sales')
                    ->insertOrIgnore([
                        'date'        => $date,
                        'branch_id'   => $branchId,
                        'interval_id' => $intervalId,
                        'user_id'     => $userId,
                        'sales'       => $sales,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);

                if (!$inserted) {
                    return response()->json([
                        'status'  => 'duplicate',
                        'message' => 'This interval has already been recorded for today.',
                    ], 409);
                }

                $newId = DB::connection('tenant')
                    ->table('retail_interval_sales')
                    ->where('branch_id', $branchId)
                    ->where('date', $date)
                    ->where('interval_id', $intervalId)
                    ->value('id');

                // ── Stamp unslotted retail_system_sales rows ──────────────
                // Any sale recorded offline (slot = '0') for this branch/date
                // that hasn't been assigned an interval yet gets stamped
                // with this slot.
                //
                // ALL three variable values (slot, date, branch) must be
                // embedded as quoted literals directly in the SQL string —
                // NOT passed as PDO ? bindings. MySQL coerces unquoted PDO
                // bindings to DECIMAL when the statement touches the slot
                // column (which defaults to '0'), causing SQLSTATE[22007]
                // on any non-numeric string elsewhere in the table.
                // branch and date are both stored as VARCHAR in retail_system_sales
                // (see migration: string('branch',165), string('date',165)).
                // Comparing a VARCHAR column to an unquoted integer causes MySQL
                // to promote the entire scan to DECIMAL context, which then blows
                // up on any non-numeric string it finds in those columns.
                // Every literal in this statement must be a quoted string.
                $safeSlot   = addslashes($slotLabel);
                $safeDate   = addslashes($date);
                $safeBranch = addslashes((string) $branchId);
                DB::connection('tenant')
                    ->statement(
                        "UPDATE `retail_system_sales`
                         SET   `slot`   = '{$safeSlot}'
                         WHERE `branch` = '{$safeBranch}'
                           AND `date`   = '{$safeDate}'
                           AND `slot`   = '0'"
                    );

                // ── Upsert retail_physical_cash ───────────────────────────
                $this->upsertPhysicalCash($branchId, $date, $sales);

                return response()->json([
                    'status' => 'success',
                    'data'   => [
                        'id'    => $newId,
                        'slot'  => $slotLabel,
                        'sales' => $sales,
                    ],
                ], 201);
            });
        } catch (\Throwable $e) {
            \Log::error('insertIntervalSale failed: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not save the interval. Please try again.',
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // EDIT INTERVAL SALE
    //
    // Responses:
    //   200 success
    //   404 record not found
    //   422 validation error
    //   500 unexpected server error
    // Adjusts retail_physical_cash by the delta (new - old), inside the
    // same transaction as the update so a failure rolls everything back.
    // ══════════════════════════════════════════════════════════════════════

    public function editIntervalSale(Request $request)
    {
        $request->validate([
            'id'       => 'required|integer|exists:tenant.retail_interval_sales,id',
            'sales'    => 'required|numeric|min:0',
            'branch'   => 'required|integer',
            'date'     => 'required|date',
            'oldsales' => 'required|numeric',
        ]);

        $newSales = (float) $request->sales;
        $oldSales = (float) $request->oldsales;
        $branchId = (int)   $request->branch;
        $date     = $request->date;

        try {
            return DB::connection('tenant')->transaction(function () use ($request, $newSales, $oldSales, $branchId, $date) {

                $updated = DB::connection('tenant')
                    ->table('retail_interval_sales')
                    ->where('id', $request->id)
                    ->update([
                        'sales'      => $newSales,
                        'updated_at' => now(),
                    ]);

                if (!$updated) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Record not found or no change made.',
                    ], 404);
                }

                $this->adjustPhysicalCash($branchId, $date, $newSales - $oldSales);

                return response()->json([
                    'status' => 'success',
                    'data'   => [
                        'id'    => (int) $request->id,
                        'sales' => $newSales,
                    ],
                ], 200);
            });
        } catch (\Throwable $e) {
            \Log::error('editIntervalSale failed: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not update the interval. Please try again.',
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // DELETE INTERVAL SALE
    //
    // Responses:
    //   200 success
    //   404 record not found
    //   422 validation error
    //   500 unexpected server error
    // Subtracts the deleted sale's value from retail_physical_cash inside
    // the same transaction as the delete.
    // ══════════════════════════════════════════════════════════════════════

    public function deleteIntervalSale(Request $request)
    {
        $request->validate([
            'id'     => 'required|integer|exists:tenant.retail_interval_sales,id',
            'branch' => 'required|integer',
            'date'   => 'required|date',
        ]);

        $branchId = (int) $request->branch;
        $date     = $request->date;

        try {
            return DB::connection('tenant')->transaction(function () use ($request, $branchId, $date) {

                $row = DB::connection('tenant')
                    ->table('retail_interval_sales')
                    ->where('id', $request->id)
                    ->first();

                if (!$row) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Record not found.',
                    ], 404);
                }

                $deleted = DB::connection('tenant')
                    ->table('retail_interval_sales')
                    ->where('id', $request->id)
                    ->delete();

                if (!$deleted) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Record not found.',
                    ], 404);
                }

                $this->adjustPhysicalCash($branchId, $date, -((float) $row->sales));

                return response()->json([
                    'status' => 'success',
                    'data'   => ['id' => (int) $request->id],
                ], 200);
            });
        } catch (\Throwable $e) {
            \Log::error('deleteIntervalSale failed: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not delete the interval. Please try again.',
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // GET INTERVAL ROW
    // Kept for backward compatibility / manual reconciliation use, although
    // insertIntervalSale now returns the new row's id directly so the
    // client no longer needs to call this after a successful insert.
    // ══════════════════════════════════════════════════════════════════════

    public function getIntervalRow(Request $request)
    {
        $request->validate([
            'branch'      => 'required|integer',
            'date'        => 'required|date',
            'interval_id' => 'required|integer',
        ]);

        $row = DB::connection('tenant')
            ->table('retail_interval_sales')
            ->where('branch_id', $request->branch)
            ->where('date', $request->date)
            ->where('interval_id', $request->interval_id)
            ->first();

        if (!$row) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Record not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $row,
        ], 200);
    }

    // ══════════════════════════════════════════════════════════════════════
    // GET PAYMENT SUMMARY
    // Read-only lookup of today's per-payment-method totals for a branch.
    // Used by the POS view to silently refresh the "view interval sales"
    // payment-breakdown pane after an interval insert/edit/delete, without
    // a full page reload. Mirrors the same query the Blade view runs on
    // initial page load ($todaysPaymentSummary), so the two stay identical.
    // ══════════════════════════════════════════════════════════════════════

    public function getPaymentSummary(Request $request)
    {
        $request->validate([
            'branch' => 'required|integer',
            'date'   => 'required|date',
        ]);

        // retail_system_sales.branch and .date are VARCHAR columns
        // (see migration note in insertIntervalSale above) — must compare
        // as strings, same as everywhere else in this controller.
        $branchId = (string) (int) $request->branch;
        $date     = $request->date;

        $summary = DB::connection('tenant')
            ->table('retail_system_sales')
            ->where('branch', $branchId)
            ->where('date', $date)
            ->select('payment_method', DB::raw('SUM(quantity * price) as total'))
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        return response()->json([
            'status' => 'success',
            'data'   => [
                'by_method' => $summary,
                'total'     => (float) $summary->sum(),
            ],
        ], 200);
    }

    // ══════════════════════════════════════════════════════════════════════
    // PHYSICAL CASH HELPERS
    // updated_at / created_at are intentionally NOT touched here —
    // these columns are managed by the application insert that first
    // creates the record and should not be overwritten on every sync.
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Insert or increment the daily physical cash record for a branch.
     * Called when a new interval is added.
     */
    private function upsertPhysicalCash(int $branchId, string $date, float $amount): void
    {
        $existing = DB::connection('tenant')
            ->table('retail_physical_cash')
            ->where('branch_id', $branchId)
            ->where('date', $date)
            ->first();

        if ($existing) {
            DB::connection('tenant')
                ->table('retail_physical_cash')
                ->where('branch_id', $branchId)
                ->where('date', $date)
                ->update([
                    'amount' => $existing->amount + $amount,
                ]);
        } else {
            DB::connection('tenant')
                ->table('retail_physical_cash')
                ->insert([
                    'branch_id'   => $branchId,
                    'date'        => $date,
                    'amount'      => $amount,
                    'recorded_by' => Auth::id(),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
        }
    }

    /**
     * Apply a signed delta (positive or negative) to the physical cash record.
     * Used by edit and delete operations.
     */
    private function adjustPhysicalCash(int $branchId, string $date, float $delta): void
    {
        if (abs($delta) < 0.001) return;

        $existing = DB::connection('tenant')
            ->table('retail_physical_cash')
            ->where('branch_id', $branchId)
            ->where('date', $date)
            ->first();

        if ($existing) {
            $newAmount = max(0, (float) $existing->amount + $delta);
            DB::connection('tenant')
                ->table('retail_physical_cash')
                ->where('branch_id', $branchId)
                ->where('date', $date)
                ->update([
                    'amount' => $newAmount,
                ]);
        } else if ($delta > 0) {
            // No record yet but we have a positive delta — create it.
            DB::connection('tenant')
                ->table('retail_physical_cash')
                ->insert([
                    'branch_id'   => $branchId,
                    'date'        => $date,
                    'amount'      => $delta,
                    'recorded_by' => Auth::id(),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
        }
        // Negative delta with no existing record — nothing to do.
    }
}