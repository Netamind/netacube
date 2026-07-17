<?php
// FILE: database/migrations/2026_07_11_000001_create_retail_partialstocktaking_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* ════════════════════════════════════════════════════════════════
           1. AGGREGATE ROW — one row per branch+date+product.
           ────────────────────────────────────────────────────────────────
           Unlike Full Stocktaking there is no separate session-snapshot
           table: expected_at_count and sales_id_at_count are frozen
           directly on THIS row the first time the product is counted that
           day (see controller). Every subsequent write on this product
           (live count, or an edit from the Stocktaking Data tab) updates
           `found` and immediately auto-resolves `expected_final` /
           pushes to retail_branch_products.stock_quantity — that's what
           makes Partial Stocktaking "live".

           `last_activity_line_id` is NOT a timestamp — it is copied from
           the auto-increment id of whichever retail_partialstocktaking_
           count_lines row most recently touched this product. Because
           that id is assigned centrally by MySQL regardless of which
           device wrote it, ordering the Stocktaking Data list by this
           column DESC always puts the most recently affected product on
           top, with no risk of clock-skew between devices.
           ════════════════════════════════════════════════════════════════ */
        Schema::create('retail_partialstocktaking', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('base_product_id');

            $table->string('product_name');
            $table->string('unit', 50);
            $table->decimal('price', 14, 2)->default(0);
            $table->decimal('rate', 8, 2)->default(1.00);

            $table->decimal('expected_at_count', 14, 3);                 // frozen the first time this product is counted today
            $table->unsignedBigInteger('sales_id_at_count')->nullable(); // sales checkpoint frozen at the same moment
            $table->decimal('found', 14, 3)->default(0);                 // cached SUM of ledger lines for this product
            $table->decimal('expected_final', 14, 3)->nullable();        // live-recomputed: expected_at_count minus sales since count

            $table->unsignedInteger('merge_count')->default(0);
            $table->json('source_device_ids')->nullable();

            $table->unsignedBigInteger('last_activity_line_id')->nullable(); // drives "latest affected on top" ordering — see note above

            $table->enum('status', ['counted', 'rectified'])->default('counted');
            $table->unsignedBigInteger('counted_by_user_id')->nullable();
            $table->unsignedBigInteger('rectified_by_user_id')->nullable();
            $table->timestamp('rectified_at')->nullable();
            $table->string('last_synced_client_uuid', 60)->nullable();

            $table->timestamps();

            $table->unique(['date', 'branch_id', 'base_product_id'], 'pst_product_unique');
            $table->index(['branch_id', 'date', 'status'], 'pst_branch_date_status_idx');
            $table->index(['branch_id', 'date', 'last_activity_line_id'], 'pst_branch_date_activity_idx');
        });

        /* ════════════════════════════════════════════════════════════════
           2. COUNT-LINE LEDGER — append-only, one row per physical count
              submission or correction, from any device, at any time.
           ────────────────────────────────────────────────────────────────
           found = SUM(quantity) over all lines for that product — same
           additive ledger model as Full Stocktaking, so simultaneous
           counting from multiple devices on the same product never loses
           data. A correction (editing Found on the Stocktaking Data tab,
           or recounting the same product live) is recorded as a NEW line
           carrying only the delta (quantity can be negative) — nothing is
           ever overwritten or deleted. client_uuid is unique so a flaky
           connection retrying the same offline edit can never double-apply.
           ════════════════════════════════════════════════════════════════ */
        Schema::create('retail_partialstocktaking_count_lines', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('base_product_id');

            $table->string('device_id', 120);
            $table->string('device_label', 120)->nullable();
            $table->unsignedBigInteger('submitted_by_user_id')->nullable();

            $table->decimal('quantity', 14, 3); // may be negative — corrections/edits
            $table->unsignedBigInteger('replaces_line_id')->nullable();
            $table->string('client_uuid', 60);

            $table->timestamps();

            $table->unique('client_uuid', 'pst_count_lines_client_uuid_unique');
            $table->index(['date', 'branch_id', 'base_product_id'], 'pst_count_lines_lookup_idx');
        });

        /* ════════════════════════════════════════════════════════════════
           3. SUMMARY — one row per branch+date, created only when the
              stocktake is closed off from Actions & Info. Exactly the
              same atomic lock pattern as Full Stocktaking's summary
              table (a single INSERT here IS the lock — status flips
              pending -> completed once every row write has finished),
              PLUS a `remarks` field so the closing auditor can leave a
              free-text note that prints on the PDF report.
           ════════════════════════════════════════════════════════════════ */
        Schema::create('retail_partialstocktaking_summary', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('branch_id');

            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->timestamp('started_at')->nullable();

            $table->unsignedInteger('products_counted')->default(0);
            $table->unsignedInteger('products_no_anomaly')->default(0);
            $table->unsignedInteger('products_overage')->default(0);
            $table->unsignedInteger('products_shortage')->default(0);

            $table->decimal('expected_value', 16, 2)->default(0);
            $table->decimal('found_value', 16, 2)->default(0);
            $table->decimal('overage_value', 16, 2)->default(0);
            $table->decimal('shortage_value', 16, 2)->default(0);
            $table->decimal('difference_value', 16, 2)->default(0);

            $table->text('remarks')->nullable(); // auditor's free-text note — printed on the PDF report

            $table->unsignedBigInteger('rectified_by_user_id')->nullable();
            $table->text('device_details')->nullable();

            $table->timestamps();

            $table->unique(['date', 'branch_id'], 'pst_summary_unique');
        });

        /* ════════════════════════════════════════════════════════════════
           4. SYNC DEVICE HEARTBEAT — covers offline Stocktaking Data edits
              AND POS/sales devices for a branch+date, same idea as Full
              Stocktaking. The live Counting tab talks to the server on
              every submission and never queues offline, so it does not
              need to report here — only the Stocktaking Data tab's
              offline-edit queue, and POS, do. Rectification refuses to
              run while any device still shows pending_ops_count > 0 or
              has never reported in, unless force=true is passed.
           ════════════════════════════════════════════════════════════════ */
        Schema::create('retail_partialstocktaking_sync_devices', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('branch_id');
            $table->string('device_id', 120);
            $table->string('device_label', 120)->nullable();
            $table->enum('device_type', ['partial', 'pos']);

            $table->unsignedInteger('pending_ops_count')->default(0);
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->unique(['date', 'branch_id', 'device_id', 'device_type'], 'pst_sync_device_unique');
            $table->index(['date', 'branch_id', 'device_type'], 'pst_sync_device_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_partialstocktaking_sync_devices');
        Schema::dropIfExists('retail_partialstocktaking_summary');
        Schema::dropIfExists('retail_partialstocktaking_count_lines');
        Schema::dropIfExists('retail_partialstocktaking');
    }
};