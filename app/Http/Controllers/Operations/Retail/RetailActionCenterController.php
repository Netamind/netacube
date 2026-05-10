<?php

namespace App\Http\Controllers\Operations\Retail;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;

class RetailActionCenterController extends Controller
{

    // ══════════════════════════════════════════════════════════════════════
    //  PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════════════════

    private function parseDeviceType(string $ua): string
    {
        $ua = strtolower($ua);
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad'))   return 'tablet';
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android')
            || str_contains($ua, 'iphone'))                              return 'mobile';
        return 'desktop';
    }

    private function parseBrowser(string $ua): string
    {
        if (str_contains($ua, 'Edg'))                                    return 'Edge';
        if (str_contains($ua, 'OPR') || str_contains($ua, 'Opera'))     return 'Opera';
        if (str_contains($ua, 'Chrome'))                                 return 'Chrome';
        if (str_contains($ua, 'Firefox'))                                return 'Firefox';
        if (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome'))return 'Safari';
        if (str_contains($ua, 'MSIE')   || str_contains($ua, 'Trident'))return 'IE';
        return 'Other';
    }

    private function parseOS(string $ua): string
    {
        if (str_contains($ua, 'Windows NT'))                             return 'Windows';
        if (str_contains($ua, 'Mac OS X'))                               return 'macOS';
        if (str_contains($ua, 'Android'))                                return 'Android';
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad'))   return 'iOS';
        if (str_contains($ua, 'Linux'))                                  return 'Linux';
        return 'Other';
    }

    private function findBaseProduct(int $baseProductId): ?object
    {
        return DB::connection('tenant')
            ->table('retail_base_products')
            ->where('id', $baseProductId)
            ->first(['id', 'name', 'code', 'unit', 'selling_price', 'cost_price']);
    }

    private function findFormattedDeliveryNote(int $id): ?array
    {
        $note = DB::connection('tenant')
            ->table('retail_deliverynotes as rdn')
            ->join('retail_base_products as rbp', 'rbp.id', '=', 'rdn.base_product_id')
            ->join('branches as b',               'b.id',   '=', 'rdn.branch_id')
            ->join('users as u',                  'u.id',   '=', 'rdn.added_by')
            ->leftJoin('users as su', 'su.id', '=', 'rdn.submitted_by')
            ->where('rdn.id', $id)
            ->select(
                'rdn.*',
                'rbp.name  as product_name',
                'rbp.code  as product_code',
                'rbp.unit  as product_unit',
                'b.name    as branch_name',
                'u.name    as added_by_name',
                'su.name   as submitted_by_name'
            )
            ->first();

        return $note ? $this->formatDeliveryNote($note) : null;
    }

    private function formatDeliveryNote($note): array
    {
        return [
            'id'                => $note->id,
            'row'               => 'row' . $note->id,
            'branch_id'         => $note->branch_id,
            'base_product_id'   => $note->base_product_id,
            'branch_name'       => $note->branch_name       ?? null,
            'product_name'      => $note->product_name      ?? null,
            'product_code'      => $note->product_code      ?? null,
            'product_unit'      => $note->product_unit      ?? null,
            'selling_price'     => $note->selling_price     ?? 0,
            'cost_price'        => $note->cost_price        ?? 0,
            'delivery_date'     => $note->delivery_date,
            'quantity'          => $note->quantity,
            'submitted'         => (bool) $note->submitted,
            'submitted_by'      => $note->submitted_by      ?? null,
            'submitted_by_name' => $note->submitted_by_name ?? null,
            'submitted_at'      => $note->submitted_at      ?? null,
            'added_by'          => $note->added_by,
            'added_by_name'     => $note->added_by_name     ?? null,
            'error_quantity'    => $note->error_quantity     ?? null,
            'error_notes'       => $note->error_notes        ?? null,
            'error_status'      => $note->error_status       ?? null,
            'notes'             => $note->notes              ?? null,
            'created_at'        => $note->created_at         ?? null,
            'updated_at'        => $note->updated_at         ?? null,
            'row_value'         => round((float) $note->quantity * (float) ($note->selling_price ?? 0), 2),
        ];
    }

    private function writeInventoryLog(
        int     $baseProductId,
        int     $branchId,
        float   $stockBefore,
        float   $stockAfter,
        string  $operationType,
        string  $reason,
        ?float  $sellingPrice = null,
        ?float  $costPrice    = null,
        string  $sourceType   = 'DeliveryNote',
        ?int    $sourceId     = null
    ): void {
        $change = $stockAfter - $stockBefore;
        if (abs($change) < 0.0001) return;

        $request = request();
        $user    = Auth::user();
        $agent   = $request->userAgent() ?? '';

        DB::connection('tenant')
            ->table('retail_inventory_logs')
            ->insert([
                'product_id'          => $baseProductId,
                'branch_id'           => $branchId,
                'stock_before'        => $stockBefore,
                'stock_after'         => $stockAfter,
                'stock_change'        => $change,
                'selling_price'       => $sellingPrice ?? 0,
                'cost_price'          => $costPrice    ?? 0,
                'operation_type'      => $operationType,
                'source_type'         => $sourceType,
                'source_id'           => $sourceId,
                'action_reason'       => $reason,
                'user_id'             => $user->id,
                'user_full_name'      => $user->name  ?? null,
                'user_email'          => $user->email ?? null,
                'user_role'           => $user->role  ?? null,
                'user_device_details' => $agent,
                'ip_address'          => $request->ip(),
                'device_type'         => $this->parseDeviceType($agent),
                'browser'             => $this->parseBrowser($agent),
                'operating_system'    => $this->parseOS($agent),
                'session_id'          => session()->getId(),
                'log_date'            => now()->toDateString(),
                'log_time'            => now()->toTimeString(),
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  VIEWS
    // ══════════════════════════════════════════════════════════════════════

    public function showActioncenterView()
    {
        return view('operations.retail.actioncenter');
    }

    public function showDeliverynotesView()
    {
        return view('operations.retail.deliverynotes');
    }

    public function showPricechangesView()
    {
        return view('operations.retail.pricechanges');
    }

    // ══════════════════════════════════════════════════════════════════════
    //  BRANCH GRID  (AJAX partial — returns rendered HTML)
    // ══════════════════════════════════════════════════════════════════════

    public function getBranchGrid(Request $request)
    {
        $request->validate([
            'base_product_id' => 'required|integer|exists:tenant.retail_base_products,id',
            'delivery_date'   => 'required|date',
        ]);

        $productId    = (int) $request->base_product_id;
        $deliveryDate = $request->delivery_date;

        $pref = DB::connection('tenant')
            ->table('user_filters')
            ->where('user_id', Auth::id())
            ->first();

        $categoryId = $pref->category_id ?? null;

        if (!$categoryId) {
            return response()->json(['html' => '<div class="no-product-placeholder" style="grid-column:1/-1;padding:40px 16px;text-align:center;color:#94a3b8;"><i class="ri-store-2-line" style="font-size:40px;color:#dde1f0;display:block;margin-bottom:10px;"></i><p style="font-size:13px;margin:0;">No category selected.</p></div>']);
        }

        $branches = DB::connection('tenant')
            ->table('branches')
            ->where('sector',   'Retail')
            ->where('category', (string) $categoryId)
            ->where('status',   'active')
            ->orderBy('name')
            ->get();

        $product = $this->findBaseProduct($productId);

        if (!$product) {
            return response()->json(['html' => '']);
        }

        $html = '<div class="branch-grid" id="branchGrid">';

        foreach ($branches as $branch) {
            $stock = DB::connection('tenant')
                ->table('retail_branch_products')
                ->where('branch_id',       $branch->id)
                ->where('base_product_id', $productId)
                ->value('stock_quantity') ?? 0;

            $branchPrice = DB::connection('tenant')
                ->table('retail_branch_products')
                ->where('branch_id',       $branch->id)
                ->where('base_product_id', $productId)
                ->value('selling_price');

            $effectivePrice = $branchPrice ?? $product->selling_price;
            $isOverride     = $branchPrice !== null;

            $sdnote = DB::connection('tenant')
                ->table('retail_deliverynotes')
                ->where('delivery_date',   $deliveryDate)
                ->where('branch_id',       $branch->id)
                ->where('base_product_id', $productId)
                ->where('submitted',       true)
                ->value('quantity') ?? 0;

            $pending = DB::connection('tenant')
                ->table('retail_deliverynotes')
                ->where('delivery_date',   $deliveryDate)
                ->where('branch_id',       $branch->id)
                ->where('base_product_id', $productId)
                ->where('submitted',       false)
                ->value('quantity');

            $priceClass = $isOverride ? 'override' : 'base';
            $priceIcon  = $isOverride ? 'ri-pencil-line' : 'ri-checkbox-circle-line';
            $priceLabel = $isOverride ? '(branch)' : '(catalogue)';
            $pendingVal = $pending !== null ? $pending : '';
            $inputClass = $pending !== null ? 'bc-input saved' : 'bc-input';
            $stockFmt   = number_format((float) $stock,          0);
            $sdnoteFmt  = number_format((float) $sdnote,         0);
            $priceFmt   = number_format((float) $effectivePrice, 2);

            $html .= <<<HTML
            <div class="branch-card" data-branch-id="{$branch->id}" data-product-id="{$productId}">
                <div class="bc-name">{$branch->name}</div>
                <div class="bc-meta">
                    <span>stock: <span class="bc-stock">{$stockFmt}</span></span>
                    <span>sdnote: <span class="bc-sdnote">{$sdnoteFmt}</span></span>
                </div>
                <input type="number"
                       class="{$inputClass}"
                       placeholder="0"
                       value="{$pendingVal}"
                       data-branch-id="{$branch->id}"
                       data-product-id="{$productId}"
                       data-branch-name="{$branch->name}">
                <div class="bc-price-hint {$priceClass}">
                    <i class="{$priceIcon}"></i>
                    MWK {$priceFmt} {$priceLabel}
                </div>
            </div>
HTML;
        }

        $html .= '</div>';

        return response($html);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  BRANCH PRICE OVERRIDE
    // ══════════════════════════════════════════════════════════════════════

    public function saveBranchPrice(Request $request)
    {
        $request->validate([
            'branch_id'       => 'required|integer|exists:tenant.branches,id',
            'base_product_id' => 'required|integer|exists:tenant.retail_base_products,id',
            'selling_price'   => 'nullable|numeric|min:0',
            'cost_price'      => 'nullable|numeric|min:0',
        ]);

        $branchId  = (int) $request->branch_id;
        $productId = (int) $request->base_product_id;

        $sell = ($request->selling_price !== null && $request->selling_price !== '')
            ? (float) $request->selling_price
            : null;

        $cost = ($request->cost_price !== null && $request->cost_price !== '')
            ? (float) $request->cost_price
            : null;

        $existing = DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('branch_id',       $branchId)
            ->where('base_product_id', $productId)
            ->first();

        if ($existing) {
            DB::connection('tenant')
                ->table('retail_branch_products')
                ->where('branch_id',       $branchId)
                ->where('base_product_id', $productId)
                ->update(['selling_price' => $sell, 'cost_price' => $cost, 'updated_at' => now()]);
        } else {
            DB::connection('tenant')
                ->table('retail_branch_products')
                ->insert([
                    'branch_id'       => $branchId,
                    'base_product_id' => $productId,
                    'selling_price'   => $sell,
                    'cost_price'      => $cost,
                    'stock_quantity'  => 0,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
        }

        return response()->json([
            'success' => $sell !== null
                ? 'Branch price override saved.'
                : 'Branch price override removed. Branch will use catalogue price.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SAVE DELIVERY NOTE  (auto-save on input change)
    // ══════════════════════════════════════════════════════════════════════

    public function saveDeliveryNote(Request $request)
    {
        $request->validate([
            'branch_id'       => 'required|integer|exists:tenant.branches,id',
            'base_product_id' => 'required|integer|exists:tenant.retail_base_products,id',
            'quantity'        => 'required|numeric|min:0',
            'delivery_date'   => 'required|date',
        ]);

        $branchId      = (int)   $request->branch_id;
        $baseProductId = (int)   $request->base_product_id;
        $quantity      = (float) $request->quantity;
        $date          = $request->delivery_date;

        // Clear the slot when quantity is zero.
        if ($quantity <= 0) {
            DB::connection('tenant')
                ->table('retail_deliverynotes')
                ->where('branch_id',       $branchId)
                ->where('base_product_id', $baseProductId)
                ->where('delivery_date',   $date)
                ->where('submitted',       false)
                ->delete();

            return response()->json(['status' => 200, 'info' => 'Delivery note cleared.']);
        }

        $base = $this->findBaseProduct($baseProductId);

        if (!$base) {
            return response()->json(['error' => 'Product not found.', 'status' => 404]);
        }

        $bp = DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('branch_id',       $branchId)
            ->where('base_product_id', $baseProductId)
            ->first(['selling_price', 'cost_price']);

        $effectiveSell = ($bp && $bp->selling_price !== null)
            ? (float) $bp->selling_price
            : (float) ($base->selling_price ?? 0);

        $effectiveCost = ($bp && $bp->cost_price !== null)
            ? (float) $bp->cost_price
            : (float) ($base->cost_price ?? 0);

        $existing = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('branch_id',       $branchId)
            ->where('base_product_id', $baseProductId)
            ->where('delivery_date',   $date)
            ->where('submitted',       false)
            ->first();

        $now = now();

        if ($existing) {
            DB::connection('tenant')
                ->table('retail_deliverynotes')
                ->where('id', $existing->id)
                ->update([
                    'quantity'      => $quantity,
                    'selling_price' => $effectiveSell,
                    'cost_price'    => $effectiveCost,
                    'updated_at'    => $now,
                ]);
            $noteId = $existing->id;
        } else {
            $noteId = DB::connection('tenant')
                ->table('retail_deliverynotes')
                ->insertGetId([
                    'branch_id'       => $branchId,
                    'base_product_id' => $baseProductId,
                    'product_name'    => $base->name ?? null,
                    'product_code'    => $base->code ?? null,
                    'product_unit'    => $base->unit ?? 'Each',
                    'selling_price'   => $effectiveSell,
                    'cost_price'      => $effectiveCost,
                    'delivery_date'   => $date,
                    'quantity'        => $quantity,
                    'submitted'       => false,
                    'submitted_by'    => null,
                    'submitted_at'    => null,
                    'added_by'        => Auth::id(),
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
        }

        return response()->json([
            'status'  => $existing ? 200 : 201,
            'success' => 'Delivery note saved.',
            'note'    => $this->findFormattedDeliveryNote($noteId),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SUBMIT — single product
    // ══════════════════════════════════════════════════════════════════════

    public function submitDeliveryNotes(Request $request)
    {
        $request->validate([
            'base_product_id' => 'required|integer|exists:tenant.retail_base_products,id',
            'delivery_date'   => 'required|date',
        ]);

        $baseProductId = (int) $request->base_product_id;
        $date          = $request->delivery_date;
        $now           = now();
        $userId        = Auth::id();

        $pending = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('base_product_id', $baseProductId)
            ->where('delivery_date',   $date)
            ->where('submitted',       false)
            ->get();

        if ($pending->isEmpty()) {
            return response()->json(['status' => 200, 'info' => 'No pending delivery notes to submit for this product.']);
        }

        $submitted = 0;

        DB::connection('tenant')->transaction(function () use ($pending, $baseProductId, $userId, $now, &$submitted) {
            foreach ($pending as $note) {
                $branchId = (int)   $note->branch_id;
                $quantity = (float) $note->quantity;

                if ($quantity <= 0) continue;

                DB::connection('tenant')
                    ->table('retail_deliverynotes')
                    ->where('id', $note->id)
                    ->update([
                        'submitted'    => true,
                        'submitted_by' => $userId,
                        'submitted_at' => $now,
                        'updated_at'   => $now,
                    ]);

                $branchProduct = DB::connection('tenant')
                    ->table('retail_branch_products')
                    ->where('branch_id',       $branchId)
                    ->where('base_product_id', $baseProductId)
                    ->first();

                if ($branchProduct) {
                    $stockBefore = (float) $branchProduct->stock_quantity;
                    $stockAfter  = $stockBefore + $quantity;
                    DB::connection('tenant')
                        ->table('retail_branch_products')
                        ->where('branch_id',       $branchId)
                        ->where('base_product_id', $baseProductId)
                        ->update(['stock_quantity' => $stockAfter, 'updated_at' => $now]);
                } else {
                    $stockBefore = 0.0;
                    $stockAfter  = $quantity;
                    DB::connection('tenant')
                        ->table('retail_branch_products')
                        ->insert([
                            'branch_id'       => $branchId,
                            'base_product_id' => $baseProductId,
                            'selling_price'   => $note->selling_price,
                            'cost_price'      => $note->cost_price,
                            'stock_quantity'  => $stockAfter,
                            'created_at'      => $now,
                            'updated_at'      => $now,
                        ]);
                }

                $this->writeInventoryLog(
                    baseProductId: $baseProductId,
                    branchId:      $branchId,
                    stockBefore:   $stockBefore,
                    stockAfter:    $stockAfter,
                    operationType: 'StockDelivery',
                    reason:        'Delivery note #' . $note->id . ' submitted — ' . Carbon::parse($note->delivery_date)->format('d M Y'),
                    sellingPrice:  (float) ($note->selling_price ?? 0),
                    costPrice:     (float) ($note->cost_price    ?? 0),
                    sourceType:    'DeliveryNote',
                    sourceId:      $note->id,
                );

                $submitted++;
            }
        });

        if ($submitted === 0) {
            return response()->json(['status' => 200, 'info' => 'All notes had zero quantity and were skipped.']);
        }

        return response()->json([
            'status'  => 200,
            'success' => $submitted . ' delivery note' . ($submitted > 1 ? 's' : '') . ' submitted and stock updated.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  SUBMIT ALL — all products for the date
    // ══════════════════════════════════════════════════════════════════════

    public function submitAllDeliveryNotes(Request $request)
    {
        $request->validate([
            'delivery_date' => 'required|date',
        ]);

        $date   = $request->delivery_date;
        $now    = now();
        $userId = Auth::id();

        $pending = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('delivery_date', $date)
            ->where('submitted',     false)
            ->get();

        if ($pending->isEmpty()) {
            return response()->json([
                'status' => 200,
                'info'   => 'No pending delivery notes for ' . Carbon::parse($date)->format('d M Y') . '.',
            ]);
        }

        $submitted = 0;
        $skipped   = 0;

        DB::connection('tenant')->transaction(function () use ($pending, $userId, $now, &$submitted, &$skipped) {
            foreach ($pending as $note) {
                $branchId      = (int)   $note->branch_id;
                $baseProductId = (int)   $note->base_product_id;
                $quantity      = (float) $note->quantity;

                if ($quantity <= 0) { $skipped++; continue; }

                $branchProduct = DB::connection('tenant')
                    ->table('retail_branch_products')
                    ->where('branch_id',       $branchId)
                    ->where('base_product_id', $baseProductId)
                    ->first();

                if ($branchProduct) {
                    $stockBefore = (float) $branchProduct->stock_quantity;
                    $stockAfter  = $stockBefore + $quantity;
                    DB::connection('tenant')
                        ->table('retail_branch_products')
                        ->where('id', $branchProduct->id)
                        ->update(['stock_quantity' => $stockAfter, 'updated_at' => $now]);
                } else {
                    $stockBefore = 0.0;
                    $stockAfter  = $quantity;
                    DB::connection('tenant')
                        ->table('retail_branch_products')
                        ->insert([
                            'branch_id'       => $branchId,
                            'base_product_id' => $baseProductId,
                            'selling_price'   => $note->selling_price,
                            'cost_price'      => $note->cost_price,
                            'stock_quantity'  => $stockAfter,
                            'created_at'      => $now,
                            'updated_at'      => $now,
                        ]);
                }

                DB::connection('tenant')
                    ->table('retail_deliverynotes')
                    ->where('id', $note->id)
                    ->update([
                        'submitted'    => true,
                        'submitted_by' => $userId,
                        'submitted_at' => $now,
                        'updated_at'   => $now,
                    ]);

                $this->writeInventoryLog(
                    baseProductId: $baseProductId,
                    branchId:      $branchId,
                    stockBefore:   $stockBefore,
                    stockAfter:    $stockAfter,
                    operationType: 'StockDelivery',
                    reason:        'Submit-all — delivery note #' . $note->id . ' — ' . Carbon::parse($note->delivery_date)->format('d M Y'),
                    sellingPrice:  (float) ($note->selling_price ?? 0),
                    costPrice:     (float) ($note->cost_price    ?? 0),
                    sourceType:    'DeliveryNote',
                    sourceId:      $note->id,
                );

                $submitted++;
            }
        });

        if ($submitted === 0) {
            return response()->json(['status' => 200, 'info' => 'All pending notes had zero quantity and were skipped.']);
        }

        $message = $submitted . ' delivery note' . ($submitted > 1 ? 's' : '') . ' submitted successfully.';
        if ($skipped > 0) {
            $message .= ' ' . $skipped . ' note' . ($skipped > 1 ? 's' : '') . ' skipped (zero quantity).';
        }

        return response()->json(['status' => 200, 'success' => $message]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  CANCEL PENDING — single product
    // ══════════════════════════════════════════════════════════════════════

    public function cancelDeliveryNotes(Request $request)
    {
        $request->validate([
            'base_product_id' => 'required|integer|exists:tenant.retail_base_products,id',
            'delivery_date'   => 'required|date',
        ]);

        $deleted = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('base_product_id', (int) $request->base_product_id)
            ->where('delivery_date',   $request->delivery_date)
            ->where('submitted',       false)
            ->delete();

        if ($deleted === 0) {
            return response()->json(['status' => 200, 'info' => 'No pending delivery notes found to cancel.']);
        }

        return response()->json([
            'status'  => 200,
            'success' => $deleted . ' pending delivery note' . ($deleted > 1 ? 's' : '') . ' cancelled.',
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  GET DELIVERY NOTES
    // ══════════════════════════════════════════════════════════════════════

    public function getDeliveryNotes(Request $request)
    {
        $request->validate([
            'delivery_date'   => 'required|date',
            'branch_id'       => 'nullable|integer|exists:tenant.branches,id',
            'base_product_id' => 'nullable|integer|exists:tenant.retail_base_products,id',
            'submitted'       => 'nullable|boolean',
        ]);

        $query = DB::connection('tenant')
            ->table('retail_deliverynotes as rdn')
            ->join('retail_base_products as rbp', 'rbp.id', '=', 'rdn.base_product_id')
            ->join('branches as b',               'b.id',   '=', 'rdn.branch_id')
            ->join('users as u',                  'u.id',   '=', 'rdn.added_by')
            ->leftJoin('users as su', 'su.id', '=', 'rdn.submitted_by')
            ->where('rdn.delivery_date', $request->delivery_date)
            ->select(
                'rdn.*',
                'rbp.name  as product_name',
                'rbp.code  as product_code',
                'rbp.unit  as product_unit',
                'b.name    as branch_name',
                'u.name    as added_by_name',
                'su.name   as submitted_by_name'
            )
            ->orderBy('b.name')
            ->orderBy('rbp.name');

        if ($request->filled('branch_id'))       $query->where('rdn.branch_id',       $request->branch_id);
        if ($request->filled('base_product_id')) $query->where('rdn.base_product_id', $request->base_product_id);
        if ($request->filled('submitted'))       $query->where('rdn.submitted',        (bool) $request->submitted);

        $notes      = $query->get();
        $formatted  = $notes->map(fn($n) => $this->formatDeliveryNote($n))->values();
        $totalQty   = $notes->sum(fn($n) => (float) $n->quantity);
        $totalValue = $notes->sum(fn($n) => (float) $n->quantity * (float) ($n->selling_price ?? 0));

        return response()->json([
            'status'      => 200,
            'notes'       => $formatted,
            'total_qty'   => round($totalQty,   3),
            'total_value' => round($totalValue, 2),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  UPDATE DELIVERY NOTE
    // ══════════════════════════════════════════════════════════════════════

    public function updateDeliveryNote(Request $request)
    {
        $request->validate([
            'id'       => 'required|integer|exists:tenant.retail_deliverynotes,id',
            'quantity' => 'required|numeric|min:0',
            'notes'    => 'nullable|string|max:500',
        ]);

        $note = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('id', $request->id)
            ->first();

        if (!$note) {
            return response()->json(['error' => 'Delivery note not found.', 'status' => 404]);
        }

        if ($note->submitted) {
            return response()->json([
                'error'  => 'This delivery note has already been submitted and cannot be edited.',
                'status' => 422,
            ]);
        }

        $base = $this->findBaseProduct((int) $note->base_product_id);

        DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('id', $request->id)
            ->update([
                'quantity'      => (float) $request->quantity,
                'selling_price' => $base->selling_price ?? $note->selling_price,
                'cost_price'    => $base->cost_price    ?? $note->cost_price,
                'notes'         => $request->notes ?? $note->notes,
                'updated_at'    => now(),
            ]);

        return response()->json([
            'status'  => 201,
            'success' => 'Delivery note updated successfully.',
            'note'    => $this->findFormattedDeliveryNote((int) $request->id),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  DELETE DELIVERY NOTE
    // ══════════════════════════════════════════════════════════════════════

    public function deleteDeliveryNote(Request $request)
    {
        $request->validate([
            'id' => 'required|integer|exists:tenant.retail_deliverynotes,id',
        ]);

        $deleted = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('id', $request->id)
            ->delete();

        if ($deleted) {
            return response()->json(['status' => 201, 'success' => 'Delivery note deleted successfully.']);
        }

        return response()->json(['error' => 'Delivery note not found.', 'status' => 404]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  BULK DELETE DELIVERY NOTES
    // ══════════════════════════════════════════════════════════════════════

    public function bulkDeleteDeliveryNotes(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:tenant.retail_deliverynotes,id',
        ]);

        $deleted = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->whereIn('id', $request->ids)
            ->delete();

        if ($deleted > 0) {
            return response()->json([
                'status'  => 201,
                'success' => $deleted . ' delivery note' . ($deleted > 1 ? 's' : '') . ' deleted successfully.',
            ]);
        }

        return response()->json(['error' => 'No delivery notes found.', 'status' => 404]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  DATES WITH DELIVERY NOTES
    // ══════════════════════════════════════════════════════════════════════

    public function getDatesWithNotes(Request $request)
    {
        $request->validate([
            'branch_id' => 'nullable|integer|exists:tenant.branches,id',
        ]);

        $query = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('delivery_date', '>=', now()->subMonths(3)->toDateString())
            ->distinct()
            ->orderByDesc('delivery_date');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        return response()->json(['status' => 200, 'dates' => $query->pluck('delivery_date')]);
    }

    // ══════════════════════════════════════════════════════════════════════
    //  DELETE BASE PRODUCT
    // ══════════════════════════════════════════════════════════════════════

    public function deleteBaseProduct(Request $request)
    {
        $request->validate([
            'base_product_id' => 'required|integer|exists:tenant.retail_base_products,id',
        ]);

        $productId = (int) $request->base_product_id;
        $userId    = Auth::id();
        $user      = Auth::user();
        $now       = now();
        $req       = request();
        $agent     = $req->userAgent() ?? '';

        $product = $this->findBaseProduct($productId);

        if (!$product) {
            return response()->json(['error' => 'Product not found.', 'status' => 404]);
        }

        DB::connection('tenant')->transaction(function () use ($productId, $product, $userId, $user, $now, $req, $agent) {

            $branchRows = DB::connection('tenant')
                ->table('retail_branch_products')
                ->where('base_product_id', $productId)
                ->get(['branch_id', 'stock_quantity', 'selling_price', 'cost_price']);

            foreach ($branchRows as $row) {
                $stockBefore = (float) $row->stock_quantity;
                if (abs($stockBefore) < 0.0001) continue;

                DB::connection('tenant')
                    ->table('retail_inventory_logs')
                    ->insert([
                        'product_id'          => $productId,
                        'branch_id'           => $row->branch_id,
                        'stock_before'        => $stockBefore,
                        'stock_after'         => 0,
                        'stock_change'        => -$stockBefore,
                        'selling_price'       => (float) ($row->selling_price ?? 0),
                        'cost_price'          => (float) ($row->cost_price    ?? 0),
                        'operation_type'      => 'WriteOff',
                        'source_type'         => 'ProductDeletion',
                        'source_id'           => $productId,
                        'action_reason'       => 'Base product "' . $product->name . '" permanently deleted by user',
                        'user_id'             => $userId,
                        'user_full_name'      => $user->name  ?? null,
                        'user_email'          => $user->email ?? null,
                        'user_role'           => $user->role  ?? null,
                        'user_device_details' => $agent,
                        'ip_address'          => $req->ip(),
                        'device_type'         => $this->parseDeviceType($agent),
                        'browser'             => $this->parseBrowser($agent),
                        'operating_system'    => $this->parseOS($agent),
                        'session_id'          => session()->getId(),
                        'log_date'            => $now->toDateString(),
                        'log_time'            => $now->toTimeString(),
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ]);
            }

            DB::connection('tenant')->table('retail_branch_products')->where('base_product_id', $productId)->delete();
            DB::connection('tenant')->table('retail_deliverynotes')->where('base_product_id', $productId)->delete();
            DB::connection('tenant')->table('retail_base_products')->where('id', $productId)->delete();
        });

        return response()->json([
            'success' => '"' . $product->name . '" has been permanently deleted.',
            'status'  => 200,
        ]);
    }
}