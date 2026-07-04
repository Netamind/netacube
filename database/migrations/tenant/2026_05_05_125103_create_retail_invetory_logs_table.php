<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retail_inventory_logs', function (Blueprint $table) {
            $table->id();

            // ── Core References ───────────────────────────────────────────
            $table->foreignId('product_id')->constrained('retail_base_products')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();

            // ── Stock Movement ────────────────────────────────────────────
            $table->decimal('stock_before', 15, 4);
            $table->decimal('stock_after', 15, 4);
            $table->decimal('stock_change', 15, 4); // positive = in, negative = out

            // ── Price Snapshot ────────────────────────────────────────────
            // Captured at log time — immune to future price changes.
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('cost_price', 15, 2)->default(0); // for margin/loss reporting

        
            $table->enum('operation_type', [
                // ── Inbound ──────────────────────────────────────────────
                'StockDelivery',      // goods received from supplier / warehouse
                'TransferIn',         // stock moved in from another branch
                'FoundStock',         // discovered surplus during stocktake
                'ReturnFromCustomer', // customer returned item back to shelf
                'ProductionIn',       // finished goods added from production
                'OpeningStock',       // initial stock load when branch goes live

                // ── Outbound ─────────────────────────────────────────────
                'Sale',               // sold to customer (POS or manual)
                'TransferOut',        // stock moved out to another branch
                'Damage',             // items broken / physically damaged
                'Expired',            // past expiry date, removed from shelf
                'Usage',              // consumed internally (staff use, demos)
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
            // Links this log entry back to the originating record so you can
            // trace a log row back to its sale, transfer, delivery, etc.
            // source_type  : the originating module  e.g. 'Sale', 'Transfer', 'Manual'
            // source_id    : the primary key in that module's table (nullable for manual)
            $table->string('source_type', 50)->default('Manual');
            $table->unsignedBigInteger('source_id')->nullable();

            // ── Reason / Notes ────────────────────────────────────────────
            $table->text('action_reason')->nullable();

            // ── User Identity Snapshot ────────────────────────────────────
            // Foreign key for joins; snapshot columns for historical accuracy
            // even if the user record is later edited or soft-deleted.
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('user_full_name')->nullable();  // name at time of action
            $table->string('user_email')->nullable();      // email at time of action
            $table->string('user_role')->nullable();       // role at time of action

            // ── Device & Session Fingerprint ──────────────────────────────
            $table->string('user_device_details')->nullable();  // raw User-Agent string
            $table->string('ip_address', 45)->nullable();       // IPv4 or IPv6
            $table->string('device_type', 20)->nullable();      // desktop / mobile / tablet
            $table->string('browser', 50)->nullable();          // Chrome, Firefox, Safari…
            $table->string('operating_system', 50)->nullable(); // Windows, Android, iOS…
            $table->string('session_id', 100)->nullable();      // Laravel session ID snapshot

            // ── Date & Time ───────────────────────────────────────────────
            // log_date / log_time : the business date/time of the movement
            //                       (may be manually set by the user)
            // created_at          : server-side timestamp; tamper-evident anchor
            $table->date('log_date');
            $table->time('log_time');
            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────────────
            // Chosen to cover every query pattern used in the views provided:
            //
            //  auditlogspdf / auditlogs blade
            //    → WHERE branch_id = ? AND log_date = ?
            //    → WHERE branch_id = ? AND log_date BETWEEN ? AND ?
            $table->index(['branch_id', 'log_date'],              'ril_branch_date');

            //  shopvalues movement page
            //    → WHERE branch_id = ? AND log_date BETWEEN ? AND ?
            //      (same index above covers this)

            //  product history / per-product audit
            //    → WHERE branch_id = ? AND product_id = ?
            $table->index(['branch_id', 'product_id'],            'ril_branch_product');

            //  filter by operation type over a date range
            //    → WHERE operation_type = ? AND log_date BETWEEN ? AND ?
            $table->index(['operation_type', 'log_date'],         'ril_optype_date');

            //  audit by user  (who did what across all branches)
            //    → WHERE user_id = ?
            $table->index('user_id',                              'ril_user');

            //  source tracing  (find all logs from a specific sale / transfer)
            //    → WHERE source_type = ? AND source_id = ?
            $table->index(['source_type', 'source_id'],           'ril_source');

            //  IP / session forensics
            //    → WHERE ip_address = ?  or  WHERE session_id = ?
            $table->index('ip_address',                           'ril_ip');
            $table->index('session_id',                           'ril_session');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_inventory_logs');
    }
};