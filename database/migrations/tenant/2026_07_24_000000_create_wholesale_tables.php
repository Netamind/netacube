<?php
// wholesale module — full schema (base products, branch stock, customers,
// delivery notes, price change log, inventory movement log)
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ════════════════════════════════════════════════════════════════
        // 1. wholesale_base_products — catalogue (one row per product,
        //    independent of branch/warehouse)
        // ════════════════════════════════════════════════════════════════
        Schema::create('wholesale_base_products', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique();
            $table->string('code')->unique()->nullable(); // SKU
            $table->text('description')->nullable();

            // category & sector live on the supplier, not the product
            $table->foreignId('supplier_id') // default / preferred supplier
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete();

            // ── Unit / packaging ─────────────────────────────────────────
            $table->string('unit')->default('Each');       // base selling unit, e.g. "Piece"
            $table->string('pack_unit')->nullable();        // e.g. "Carton", "Pallet", "Box"
            $table->decimal('units_per_pack', 12, 2)->nullable(); // pieces per pack_unit

            // ── Pricing (default / walk-in price) ─────────────────────────
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->decimal('selling_price', 15, 2)->nullable();

            // ── Wholesale-specific ────────────────────────────────────────
            $table->decimal('min_order_quantity', 15, 2)->default(1);

            $table->boolean('is_product')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('code');
            $table->index('supplier_id');
            $table->index('is_product');
        });

        // ════════════════════════════════════════════════════════════════
        // 2. wholesale_branch_products — per-warehouse/branch stock record
        // ════════════════════════════════════════════════════════════════
        Schema::create('wholesale_branch_products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->cascadeOnDelete();

            $table->foreignId('base_product_id')
                ->constrained('wholesale_base_products')
                ->cascadeOnDelete();

            $table->foreignId('supplier_id') // stock-level override of default supplier
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete();

            // ── Barcode ───────────────────────────────────────────────────
            $table->string('primary_barcode')->nullable();

            // ── Batch / Expiry ────────────────────────────────────────────
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();

            // ── Pricing ───────────────────────────────────────────────────
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->decimal('selling_price', 15, 2)->nullable();

            // ── Stock ─────────────────────────────────────────────────────
            $table->decimal('stock_quantity', 15, 2)->default(0);
            $table->decimal('reorder_point', 15, 2)->default(0);
            $table->decimal('reorder_quantity', 15, 2)->nullable();
            $table->decimal('max_stock', 15, 2)->nullable();
            $table->boolean('track_stock')->default(true);

            // ── Status ────────────────────────────────────────────────────
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_negative_stock')->default(false);

            $table->timestamps();

            $table->unique(['branch_id', 'base_product_id'], 'wbp_branch_product_unique');
            $table->index('primary_barcode');
            $table->index('supplier_id');
            $table->index(['branch_id', 'is_active']);
            $table->index('expiry_date');
        });

        // ════════════════════════════════════════════════════════════════
        // 3. wholesale_customers — B2B customers with credit terms
        // ════════════════════════════════════════════════════════════════
        Schema::create('wholesale_customers', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('business_name')->nullable();
            $table->string('registration_number')->nullable();

            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_alt')->nullable();
            $table->string('email')->nullable();

            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable()->default('Malawi');

            // ── Credit / terms ────────────────────────────────────────────
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->decimal('credit_balance', 15, 2)->default(0); // current outstanding
            $table->string('payment_terms')->nullable();          // e.g. "Net 30"

            $table->enum('status', ['active', 'inactive', 'blacklisted'])->default('active');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('name');
            $table->index('phone');
            $table->index('status');
        });

        // ════════════════════════════════════════════════════════════════
        // 4. wholesale_deliverynotes — delivery note header (inbound from
        //    supplier or outbound to customer)
        // ════════════════════════════════════════════════════════════════
        Schema::create('wholesale_deliverynotes', function (Blueprint $table) {
            $table->id();

            $table->string('delivery_note_number')->unique();

            $table->enum('type', ['inbound', 'outbound'])->default('outbound');

            $table->foreignId('branch_id')
                ->constrained('branches')
                ->restrictOnDelete();

            // outbound → who it's going to
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('wholesale_customers')
                ->nullOnDelete();

            // inbound → who it's coming from
            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete();

            $table->foreignId('user_id') // prepared by
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('driver_name')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->text('delivery_address')->nullable();

            $table->date('delivery_date');
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->enum('status', [
                'pending', 'dispatched', 'delivered', 'cancelled',
            ])->default('pending');

            $table->string('received_by')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['branch_id', 'delivery_date'], 'wdn_branch_date');
            $table->index('customer_id');
            $table->index('supplier_id');
            $table->index('status');
        });

        // ════════════════════════════════════════════════════════════════
        // 5. wholesale_deliverynote_items — line items per delivery note
        // ════════════════════════════════════════════════════════════════
        Schema::create('wholesale_deliverynote_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('delivery_note_id')
                ->constrained('wholesale_deliverynotes')
                ->cascadeOnDelete();

            $table->foreignId('branch_product_id')
                ->constrained('wholesale_branch_products')
                ->restrictOnDelete();

            // ── Snapshot — line reads back correctly even if the product
            //    record is later renamed / re-priced / deleted ────────────
            $table->string('product_name');
            $table->string('unit')->default('Each');
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();

            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('line_total', 15, 2)->nullable();

            $table->timestamps();

            $table->index('delivery_note_id');
            $table->index('branch_product_id');
        });

        // ════════════════════════════════════════════════════════════════
        // 6. wholesale_price_changes — price change audit trail
        // ════════════════════════════════════════════════════════════════
        Schema::create('wholesale_price_changes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('base_product_id')
                ->nullable()
                ->constrained('wholesale_base_products')
                ->nullOnDelete();

            // null = base catalogue price change, set = branch-specific override
            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();

            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // ── Snapshot — record stays meaningful if product is deleted ──
            $table->string('product_name');
            $table->string('product_code')->nullable();
            $table->string('product_unit')->default('Each');
            $table->string('branch_name')->nullable();

            $table->decimal('old_price', 15, 2);
            $table->decimal('new_price', 15, 2);

            $table->string('reason')->nullable();
            $table->date('change_date'); // effective immediately, not a schedule date

            $table->timestamps();

            $table->index(['base_product_id', 'branch_id'], 'wpc_product_branch');
            $table->index('change_date');
            $table->index('changed_by');
            $table->index('product_name');
        });

        // ════════════════════════════════════════════════════════════════
        // 7. wholesale_inventory_logs — full stock movement audit trail
        // ════════════════════════════════════════════════════════════════
        Schema::create('wholesale_inventory_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained('wholesale_base_products')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();

            // Batch/expiry snapshot at time of movement
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();

            // ── Stock Movement ────────────────────────────────────────────
            $table->decimal('stock_before', 15, 4);
            $table->decimal('stock_after', 15, 4);
            $table->decimal('stock_change', 15, 4); // positive = in, negative = out

            // ── Price Snapshot — immune to future price changes ──────────
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('cost_price', 15, 2)->default(0);

            $table->enum('operation_type', [
                // ── Inbound ──────────────────────────────────────────────
                'StockDelivery',      // goods received from supplier
                'TransferIn',         // stock moved in from another branch/warehouse
                'FoundStock',         // discovered surplus during stocktake
                'ReturnFromCustomer', // customer returned goods
                'ProductionIn',       // finished goods added from production
                'OpeningStock',       // initial stock load when branch goes live

                // ── Outbound ─────────────────────────────────────────────
                'BulkSale',           // sold to a wholesale customer
                'TransferOut',        // stock moved out to another branch/warehouse
                'Damage',             // items broken / physically damaged
                'Expired',            // past expiry date, removed from stock
                'Usage',              // consumed internally
                'Theft',              // confirmed or suspected pilferage
                'Wastage',            // spoilage, spillage, shrinkage
                'Donation',           // given away (charity, promotion, etc.)
                'ReturnToSupplier',   // sent back to supplier / vendor
                'Recall',             // manufacturer / regulatory product recall
                'Sample',             // given as a free sample
                'WriteOff',           // management decision to write off stock
                'Loss',               // unaccounted loss after stocktake

                // ── Neutral / Corrective ──────────────────────────────────
                'Adjustment',         // general correction after stocktake
                'Recount',            // quantity corrected after recount
                'Reversal',           // system-generated undo of a previous entry
                'Others',             // catch-all; selected when nothing else fits
            ])->default('Others');

            // ── Source Reference ──────────────────────────────────────────
            // source_type : originating module, e.g. 'DeliveryNote', 'Transfer', 'Manual'
            // source_id   : primary key in that module's table (nullable for manual)
            $table->string('source_type', 50)->default('Manual');
            $table->unsignedBigInteger('source_id')->nullable();

            $table->text('action_reason')->nullable();

            // ── User Identity Snapshot ────────────────────────────────────
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('user_full_name')->nullable();
            $table->string('user_email')->nullable();
            $table->string('user_role')->nullable();

            // ── Device & Session Fingerprint ──────────────────────────────
            $table->string('user_device_details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('device_type', 20)->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('operating_system', 50)->nullable();
            $table->string('session_id', 100)->nullable();

            // ── Date & Time ───────────────────────────────────────────────
            $table->date('log_date');
            $table->time('log_time');
            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────────────
            $table->index(['branch_id', 'log_date'],      'wil_branch_date');
            $table->index(['branch_id', 'product_id'],    'wil_branch_product');
            $table->index(['operation_type', 'log_date'], 'wil_optype_date');
            $table->index('user_id',                      'wil_user');
            $table->index(['source_type', 'source_id'],   'wil_source');
            $table->index('ip_address',                   'wil_ip');
            $table->index('session_id',                   'wil_session');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wholesale_inventory_logs');
        Schema::dropIfExists('wholesale_price_changes');
        Schema::dropIfExists('wholesale_deliverynote_items');
        Schema::dropIfExists('wholesale_deliverynotes');
        Schema::dropIfExists('wholesale_customers');
        Schema::dropIfExists('wholesale_branch_products');
        Schema::dropIfExists('wholesale_base_products');
    }
};