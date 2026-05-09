<?php

namespace App\Http\Controllers\Operations\Retail;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;

class RetailDeliverynotesController extends Controller
{

    // ── Views ─────────────────────────────────────────────────────────────

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

    // ─────────────────────────────────────────────────────────────────────
    //  BRANCH GRID  (AJAX partial — returns rendered HTML for the grid)
    //
    //  Route: GET retail.operations.actioncenter.branchgrid
    //  Params: base_product_id, delivery_date
    // ─────────────────────────────────────────────────────────────────────

    public function getBranchGrid(Request $request)
    {
        $request->validate([
            'base_product_id' => 'required|integer|exists:tenant.retail_base_products,id',
            'delivery_date'   => 'required|date',
        ]);

        $productId    = (int) $request->base_product_id;
        $deliveryDate = $request->delivery_date;

        // Resolve the authenticated user's category preference
        $pref = DB::connection('tenant')
            ->table('user_filters')
            ->where('user_id', Auth::id())
            ->first();

        $categoryId = $pref->category_id ?? null;

        if (!$categoryId) {
            return response()->json(['html' => '<div class="no-product-placeholder" style="grid-column:1/-1;padding:40px 16px;text-align:center;color:#94a3b8;"><i class="ri-store-2-line" style="font-size:40px;color:#dde1f0;display:block;margin-bottom:10px;"></i><p style="font-size:13px;margin:0;">No category selected.</p></div>']);
        }

        // Fetch branches in the category
        $branches = DB::connection('tenant')
            ->table('branches')
            ->where('sector',   'Retail')
            ->where('category', (string) $categoryId)
            ->where('status',   'active')
            ->orderBy('name')
            ->get();

        // Fetch the base product
        $product = DB::connection('tenant')
            ->table('retail_base_products')
            ->where('id', $productId)
            ->first(['id', 'name', 'unit', 'selling_price', 'cost_price', 'code']);

        if (!$product) {
            return response()->json(['html' => '']);
        }

        // Build branch cards HTML
        $html = '<div class="branch-grid" id="branchGrid">';

        foreach ($branches as $branch) {
            // Stock quantity
            $stock = DB::connection('tenant')
                ->table('retail_branch_products')
                ->where('branch_id',       $branch->id)
                ->where('base_product_id', $productId)
                ->value('stock_quantity') ?? 0;

            // Branch-specific selling price (null = use base catalogue price)
            $branchPrice = DB::connection('tenant')
                ->table('retail_branch_products')
                ->where('branch_id',       $branch->id)
                ->where('base_product_id', $productId)
                ->value('selling_price');

            $effectivePrice = $branchPrice ?? $product->selling_price;
            $isOverride     = $branchPrice !== null;

            // Submitted delivery note quantity for this date
            $sdnote = DB::connection('tenant')
                ->table('retail_deliverynotes')
                ->where('delivery_date',   $deliveryDate)
                ->where('branch_id',       $branch->id)
                ->where('base_product_id', $productId)
                ->where('submitted',       true)
                ->value('quantity') ?? 0;

            // Pending (unsubmitted) quantity — pre-fills the input
            $pending = DB::connection('tenant')
                ->table('retail_deliverynotes')
                ->where('delivery_date',   $deliveryDate)
                ->where('branch_id',       $branch->id)
                ->where('base_product_id', $productId)
                ->where('submitted',       false)
                ->value('quantity');

            $priceClass  = $isOverride ? 'override' : 'base';
            $priceIcon   = $isOverride ? 'ri-pencil-line' : 'ri-checkbox-circle-line';
            $priceLabel  = $isOverride ? '(branch)' : '(catalogue)';
            $pendingVal  = $pending !== null ? $pending : '';
            $inputClass  = $pending !== null ? 'bc-input saved' : 'bc-input';

            $stockFmt   = number_format((float) $stock,   0);
            $sdnoteFmt  = number_format((float) $sdnote,  0);
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

        // Also render the price legend
        $html .= <<<HTML
        <div class="price-legend">
            <div class="pl-item"><div class="pl-dot" style="background:#059669;"></div> Catalogue default price</div>
            <div class="pl-item"><div class="pl-dot" style="background:#1d4ed8;"></div> Branch-specific override</div>
        </div>
HTML;

        return response($html);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  BRANCH PRICE OVERRIDES  (AJAX JSON — populates the overrides panel)
    //
    //  Route: GET retail.operations.actioncenter.overrides
    //  Params: base_product_id
    // ─────────────────────────────────────────────────────────────────────

    public function getOverrides(Request $request)
    {
        $request->validate([
            'base_product_id' => 'required|integer|exists:tenant.retail_base_products,id',
        ]);

        $productId = (int) $request->base_product_id;

        // Base product for catalogue price reference
        $product = DB::connection('tenant')
            ->table('retail_base_products')
            ->where('id', $productId)
            ->first(['id', 'selling_price', 'cost_price']);

        // Category from user preference
        $pref = DB::connection('tenant')
            ->table('user_filters')
            ->where('user_id', Auth::id())
            ->first();

        $categoryId = $pref->category_id ?? null;

        // All branches in this category
        $branches = DB::connection('tenant')
            ->table('branches')
            ->where('sector',   'Retail')
            ->where('category', (string) $categoryId)
            ->where('status',   'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $result = [];

        foreach ($branches as $branch) {
            $bp = DB::connection('tenant')
                ->table('retail_branch_products')
                ->where('branch_id',       $branch->id)
                ->where('base_product_id', $productId)
                ->first(['selling_price', 'cost_price']);

            $result[] = [
                'id'           => $branch->id,
                'name'         => $branch->name,
                'base_price'   => (float) ($product->selling_price ?? 0),
                'base_cost'    => (float) ($product->cost_price    ?? 0),
                'branch_price' => ($bp && $bp->selling_price !== null) ? (float) $bp->selling_price : null,
                'branch_cost'  => ($bp && $bp->cost_price    !== null) ? (float) $bp->cost_price    : null,
            ];
        }

        return response()->json(['branches' => $result]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  BRANCH PRICE OVERRIDE  (POST — save or clear a branch price)
    //
    //  Route: POST retail.operations.actioncenter.branch.price
    //  Params: branch_id, base_product_id, selling_price (null = clear), cost_price
    // ─────────────────────────────────────────────────────────────────────

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

        // Determine effective price values (null means "clear override → use base")
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
                ->update([
                    'selling_price' => $sell,
                    'cost_price'    => $cost,
                    'updated_at'    => now(),
                ]);
        } else {
            // Create a branch product record with null stock (price management only)
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

        $message = $sell !== null
            ? 'Branch price override saved.'
            : 'Branch price override removed. Branch will use catalogue price.';

        return response()->json(['success' => $message]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  SAVE DELIVERY NOTE  (auto-save on input change)
    //
    //  Route: POST retail.operations.actioncenter.save.dnote
    //  Params: branch_id, base_product_id, quantity, delivery_date
    //
    //  Logic:
    //   • If a pending note exists for this branch+product+date → update qty
    //   • If no pending note → insert a new one (submitted = false)
    //   • Price snapshot: branch override if set, else base catalogue price
    // ─────────────────────────────────────────────────────────────────────

    public function saveDnote(Request $request)
    {
        $request->validate([
            'branch_id'       => 'required|integer|exists:tenant.branches,id',
            'base_product_id' => 'required|integer|exists:tenant.retail_base_products,id',
            'quantity'        => 'required|numeric|min:0',
            'delivery_date'   => 'required|date',
        ]);

        $branchId     = (int) $request->branch_id;
        $productId    = (int) $request->base_product_id;
        $quantity     = (float) $request->quantity;
        $deliveryDate = $request->delivery_date;
        $userId       = Auth::id();

        // Resolve base product for snapshot
        $product = DB::connection('tenant')
            ->table('retail_base_products')
            ->where('id', $productId)
            ->first(['id', 'name', 'code', 'unit', 'selling_price', 'cost_price']);

        if (!$product) {
            return response()->json(['error' => 'Product not found.', 'status' => 404]);
        }

        // Branch-specific price override (null = use base)
        $bp = DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('branch_id',       $branchId)
            ->where('base_product_id', $productId)
            ->first(['selling_price', 'cost_price']);

        $effectiveSell = ($bp && $bp->selling_price !== null)
            ? (float) $bp->selling_price
            : (float) ($product->selling_price ?? 0);

        $effectiveCost = ($bp && $bp->cost_price !== null)
            ? (float) $bp->cost_price
            : (float) ($product->cost_price ?? 0);

        // Check for existing pending note
        $existing = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('delivery_date',   $deliveryDate)
            ->where('branch_id',       $branchId)
            ->where('base_product_id', $productId)
            ->where('submitted',       false)
            ->first();

        if ($existing) {
            DB::connection('tenant')
                ->table('retail_deliverynotes')
                ->where('id', $existing->id)
                ->update([
                    'quantity'       => $quantity,
                    'selling_price'  => $effectiveSell,
                    'cost_price'     => $effectiveCost,
                    'updated_at'     => now(),
                ]);

            return response()->json(['status' => 200, 'message' => 'Delivery note updated.']);
        }

        // Insert new pending note
        DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->insert([
                'branch_id'       => $branchId,
                'base_product_id' => $productId,
                'product_name'    => $product->name,
                'product_code'    => $product->code,
                'product_unit'    => $product->unit ?? 'Each',
                'selling_price'   => $effectiveSell,
                'cost_price'      => $effectiveCost,
                'delivery_date'   => $deliveryDate,
                'quantity'        => $quantity,
                'submitted'       => false,
                'submitted_by'    => null,
                'submitted_at'    => null,
                'added_by'        => $userId,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

        return response()->json(['status' => 201, 'message' => 'Delivery note created.']);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  SUBMIT  (marks pending notes as submitted, adds to branch stock)
    //
    //  Route: POST retail.operations.actioncenter.submit
    //  Params: base_product_id, delivery_date
    //
    //  For each pending note of this product on this date:
    //   1. Mark submitted = true, submitted_by, submitted_at
    //   2. Add quantity to retail_branch_products.stock_quantity
    //   3. Write an inventory log entry (StockDelivery)
    // ─────────────────────────────────────────────────────────────────────

    public function submitDnotes(Request $request)
    {
        $request->validate([
            'base_product_id' => 'required|integer|exists:tenant.retail_base_products,id',
            'delivery_date'   => 'required|date',
        ]);

        $productId    = (int) $request->base_product_id;
        $deliveryDate = $request->delivery_date;
        $userId       = Auth::id();
        $now          = now();

        // Fetch all pending notes for this product+date
        $pending = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('base_product_id', $productId)
            ->where('delivery_date',   $deliveryDate)
            ->where('submitted',       false)
            ->get();

        if ($pending->isEmpty()) {
            return response()->json(['info' => 'No pending delivery notes to submit for this product.']);
        }

        $submitted = 0;

        DB::connection('tenant')->transaction(function () use ($pending, $productId, $userId, $now, &$submitted) {
            foreach ($pending as $note) {
                $branchId = (int) $note->branch_id;
                $quantity = (float) $note->quantity;

                // Skip zero quantities
                if ($quantity <= 0) continue;

                // 1. Mark as submitted
                DB::connection('tenant')
                    ->table('retail_deliverynotes')
                    ->where('id', $note->id)
                    ->update([
                        'submitted'    => true,
                        'submitted_by' => $userId,
                        'submitted_at' => $now,
                        'updated_at'   => $now,
                    ]);

                // 2. Upsert branch product stock
                $branchProduct = DB::connection('tenant')
                    ->table('retail_branch_products')
                    ->where('branch_id',       $branchId)
                    ->where('base_product_id', $productId)
                    ->first();

                if ($branchProduct) {
                    $oldQty = (float) $branchProduct->stock_quantity;
                    $newQty = $oldQty + $quantity;

                    DB::connection('tenant')
                        ->table('retail_branch_products')
                        ->where('branch_id',       $branchId)
                        ->where('base_product_id', $productId)
                        ->update([
                            'stock_quantity' => $newQty,
                            'updated_at'     => $now,
                        ]);

                    // 3. Inventory log
                    $this->logStockDelivery(
                        productId:    $productId,
                        branchId:     $branchId,
                        stockBefore:  $oldQty,
                        stockAfter:   $newQty,
                        sellPrice:    (float) ($note->selling_price ?? 0),
                        costPrice:    (float) ($note->cost_price    ?? 0),
                        sourceId:     (int) $note->id,
                        reason:       'Delivery note submitted — ' . Carbon::parse($note->delivery_date)->format('d M Y'),
                    );
                } else {
                    // Branch product row doesn't exist yet — create it
                    DB::connection('tenant')
                        ->table('retail_branch_products')
                        ->insert([
                            'branch_id'       => $branchId,
                            'base_product_id' => $productId,
                            'selling_price'   => $note->selling_price,
                            'cost_price'      => $note->cost_price,
                            'stock_quantity'  => $quantity,
                            'created_at'      => $now,
                            'updated_at'      => $now,
                        ]);

                    $this->logStockDelivery(
                        productId:    $productId,
                        branchId:     $branchId,
                        stockBefore:  0,
                        stockAfter:   $quantity,
                        sellPrice:    (float) ($note->selling_price ?? 0),
                        costPrice:    (float) ($note->cost_price    ?? 0),
                        sourceId:     (int) $note->id,
                        reason:       'First delivery for this branch — ' . Carbon::parse($note->delivery_date)->format('d M Y'),
                    );
                }

                $submitted++;
            }
        });

        if ($submitted === 0) {
            return response()->json(['info' => 'All notes had zero quantity and were skipped.']);
        }

        return response()->json([
            'success' => $submitted . ' delivery note' . ($submitted > 1 ? 's' : '') . ' submitted and stock updated.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  SUBMIT ALL  (submits ALL pending notes across all products for a date)
    //
    //  Route: POST retail.operations.actioncenter.submitall
    //  Params: delivery_date
    // ─────────────────────────────────────────────────────────────────────

    public function submitAllDnotes(Request $request)
    {
        $request->validate([
            'delivery_date' => 'required|date',
        ]);

        $deliveryDate = $request->delivery_date;
        $userId       = Auth::id();
        $now          = now();

        $pending = DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('delivery_date', $deliveryDate)
            ->where('submitted',     false)
            ->get();

        if ($pending->isEmpty()) {
            return response()->json(['info' => 'No pending delivery notes for ' . Carbon::parse($deliveryDate)->format('d M Y') . '.']);
        }

        $submitted = 0;

        DB::connection('tenant')->transaction(function () use ($pending, $userId, $now, &$submitted) {
            foreach ($pending as $note) {
                $branchId  = (int) $note->branch_id;
                $productId = (int) $note->base_product_id;
                $quantity  = (float) $note->quantity;

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
                    ->where('base_product_id', $productId)
                    ->first();

                if ($branchProduct) {
                    $oldQty = (float) $branchProduct->stock_quantity;
                    $newQty = $oldQty + $quantity;

                    DB::connection('tenant')
                        ->table('retail_branch_products')
                        ->where('branch_id',       $branchId)
                        ->where('base_product_id', $productId)
                        ->update([
                            'stock_quantity' => $newQty,
                            'updated_at'     => $now,
                        ]);

                    $this->logStockDelivery(
                        productId:   $productId,
                        branchId:    $branchId,
                        stockBefore: $oldQty,
                        stockAfter:  $newQty,
                        sellPrice:   (float) ($note->selling_price ?? 0),
                        costPrice:   (float) ($note->cost_price    ?? 0),
                        sourceId:    (int) $note->id,
                        reason:      'Submit-all — delivery date ' . Carbon::parse($note->delivery_date)->format('d M Y'),
                    );
                } else {
                    DB::connection('tenant')
                        ->table('retail_branch_products')
                        ->insert([
                            'branch_id'       => $branchId,
                            'base_product_id' => $productId,
                            'selling_price'   => $note->selling_price,
                            'cost_price'      => $note->cost_price,
                            'stock_quantity'  => $quantity,
                            'created_at'      => $now,
                            'updated_at'      => $now,
                        ]);

                    $this->logStockDelivery(
                        productId:   $productId,
                        branchId:    $branchId,
                        stockBefore: 0,
                        stockAfter:  $quantity,
                        sellPrice:   (float) ($note->selling_price ?? 0),
                        costPrice:   (float) ($note->cost_price    ?? 0),
                        sourceId:    (int) $note->id,
                        reason:      'Submit-all first delivery — ' . Carbon::parse($note->delivery_date)->format('d M Y'),
                    );
                }

                $submitted++;
            }
        });

        if ($submitted === 0) {
            return response()->json(['info' => 'All pending notes had zero quantity and were skipped.']);
        }

        return response()->json([
            'success' => $submitted . ' delivery note' . ($submitted > 1 ? 's' : '') . ' submitted successfully.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  CANCEL PENDING  (deletes unsubmitted notes for a product+date)
    //
    //  Route: POST retail.operations.actioncenter.cancel
    //  Params: base_product_id, delivery_date
    // ─────────────────────────────────────────────────────────────────────

    public function cancelDnotes(Request $request)
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
            return response()->json(['info' => 'No pending delivery notes found to cancel.']);
        }

        return response()->json([
            'success' => $deleted . ' pending note' . ($deleted > 1 ? 's' : '') . ' cancelled.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    //  Private: Write one inventory log row for a stock delivery
    // ─────────────────────────────────────────────────────────────────────

    private function logStockDelivery(
        int    $productId,
        int    $branchId,
        float  $stockBefore,
        float  $stockAfter,
        float  $sellPrice,
        float  $costPrice,
        int    $sourceId,
        string $reason
    ): void {
        $change = $stockAfter - $stockBefore;
        if (abs($change) < 0.0001) return;

        $request = request();
        $user    = Auth::user();
        $agent   = $request->userAgent() ?? '';

        DB::connection('tenant')
            ->table('retail_inventory_logs')
            ->insert([
                'product_id'          => $productId,
                'branch_id'           => $branchId,
                'stock_before'        => $stockBefore,
                'stock_after'         => $stockAfter,
                'stock_change'        => $change,
                'selling_price'       => $sellPrice,
                'cost_price'          => $costPrice,
                'operation_type'      => 'StockDelivery',
                'source_type'         => 'DeliveryNote',
                'source_id'           => $sourceId,
                'action_reason'       => $reason,
                'user_id'             => $user->id,
                'user_full_name'      => $user->name   ?? null,
                'user_email'          => $user->email  ?? null,
                'user_role'           => $user->role   ?? null,
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

    // ─────────────────────────────────────────────────────────────────────
    //  UA parsers
    // ─────────────────────────────────────────────────────────────────────

    private function parseDeviceType(string $ua): string
    {
        $ua = strtolower($ua);
        if (str_contains($ua, 'tablet') || str_contains($ua, 'ipad'))  return 'tablet';
        if (str_contains($ua, 'mobile') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) return 'mobile';
        return 'desktop';
    }

    private function parseBrowser(string $ua): string
    {
        if (str_contains($ua, 'Edg'))                                    return 'Edge';
        if (str_contains($ua, 'OPR') || str_contains($ua, 'Opera'))     return 'Opera';
        if (str_contains($ua, 'Chrome'))                                 return 'Chrome';
        if (str_contains($ua, 'Firefox'))                                return 'Firefox';
        if (str_contains($ua, 'Safari') && !str_contains($ua, 'Chrome')) return 'Safari';
        if (str_contains($ua, 'MSIE')   || str_contains($ua, 'Trident')) return 'IE';
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
 
    // Resolve the product name before deletion (for log readability)
    $product = DB::connection('tenant')
        ->table('retail_base_products')
        ->where('id', $productId)
        ->first(['id', 'name', 'code']);
 
    if (!$product) {
        return response()->json(['error' => 'Product not found.', 'status' => 404]);
    }
 
    DB::connection('tenant')->transaction(function () use (
        $productId, $product, $userId, $user, $now, $req, $agent
    ) {
        // ── 1. Write a WriteOff inventory log for every branch that held stock ──
        $branchRows = DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('base_product_id', $productId)
            ->get(['branch_id', 'stock_quantity', 'selling_price', 'cost_price']);
 
        foreach ($branchRows as $row) {
            $stockBefore = (float) $row->stock_quantity;
            if (abs($stockBefore) < 0.0001) continue; // skip zero-stock rows
 
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
                    'user_full_name'      => $user->name   ?? null,
                    'user_email'          => $user->email  ?? null,
                    'user_role'           => $user->role   ?? null,
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
 
        // ── 2. Delete branch product rows ────────────────────────────────
        DB::connection('tenant')
            ->table('retail_branch_products')
            ->where('base_product_id', $productId)
            ->delete();
 
        // ── 3. Delete delivery notes (both pending and submitted) ────────
        DB::connection('tenant')
            ->table('retail_deliverynotes')
            ->where('base_product_id', $productId)
            ->delete();
 
        // ── 4. Delete the base product itself ────────────────────────────
        DB::connection('tenant')
            ->table('retail_base_products')
            ->where('id', $productId)
            ->delete();
    });
 
    return response()->json([
        'success' => '"' . $product->name . '" has been permanently deleted.',
        'status'  => 200,
    ]);
}


}