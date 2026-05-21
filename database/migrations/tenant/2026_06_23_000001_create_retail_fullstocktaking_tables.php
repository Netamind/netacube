<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* ════════════════════════════════════════════════════════════════
           1. SESSION SNAPSHOT — the frozen baseline.
           ────────────────────────────────────────────────────────────────
           Seeded ONCE per branch+date, the first time the Stocktaking tab
           is opened for that branch+date — i.e. before anyone goes offline
           to count. expected_at_session_start and sales_id_at_session_start
           are read from here for every product's FIRST count line, instead
           of from live retail_branch_products / retail_system_sales at
           whatever moment a device finally regains signal and syncs. This
           is what makes "true expected" survive arbitrarily long offline
           gaps and any number of POS sales happening in between.
           ════════════════════════════════════════════════════════════════ */
        Schema::create('retail_fullstocktaking_session_snapshot', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('base_product_id');
            $table->decimal('expected_at_session_start', 14, 3);
            $table->unsignedBigInteger('sales_id_at_session_start')->nullable();
            $table->timestamps();

            $table->unique(['date', 'branch_id', 'base_product_id'], 'fst_snapshot_unique');
            $table->index(['date', 'branch_id'], 'fst_snapshot_branch_date_idx');
        });

        /* ════════════════════════════════════════════════════════════════
           2. AGGREGATE COUNT TABLE — one row per branch+date+product.
           ────────────────────────────────────────────────────────────────
           `found` is a CACHED total, kept equal to
           SUM(retail_fullstocktaking_count_lines.quantity) for this row —
           recomputed after every ledger insert/sync, never written to
           directly by a device. expected_at_count / sales_id_at_count are
           copied from the session snapshot the first time this row is
           created, then frozen for the life of the row.
           ════════════════════════════════════════════════════════════════ */
        Schema::create('retail_fullstocktaking', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('base_product_id');

            $table->string('product_name');
            $table->string('unit', 50);
            $table->decimal('price', 14, 2)->default(0);
            $table->decimal('rate', 8, 2)->default(1.00);

            $table->decimal('expected_at_count', 14, 3);                  // frozen from session snapshot
            $table->unsignedBigInteger('sales_id_at_count')->nullable();  // frozen from session snapshot
            $table->decimal('found', 14, 3)->default(0);                  // cached SUM of ledger lines
            $table->decimal('expected_final', 14, 3)->nullable();         // set at rectification

            $table->unsignedInteger('merge_count')->default(0);
            $table->json('source_device_ids')->nullable();

            $table->enum('status', ['counted', 'rectified'])->default('counted');
            $table->unsignedBigInteger('counted_by_user_id')->nullable();
            $table->unsignedBigInteger('rectified_by_user_id')->nullable();
            $table->timestamp('rectified_at')->nullable();
            $table->string('last_synced_client_uuid', 60)->nullable();

            $table->timestamps();

            $table->unique(['date', 'branch_id', 'base_product_id'], 'fst_product_unique');
            $table->index(['branch_id', 'date', 'status'], 'fst_branch_date_status_idx');
        });

        /* ════════════════════════════════════════════════════════════════
           3. COUNT-LINE LEDGER — append-only, one row per physical count
              submission from any device, at any time.
           ────────────────────────────────────────────────────────────────
           found = SUM(quantity) over all lines for that product. Two
           devices counting the same product just both insert lines and
           the totals add naturally — no different from today's behavior,
           but now every contribution is individually traceable. A wrong
           submission is corrected with a NEW line (quantity can be
           negative), referencing the line it corrects via
           replaces_line_id — nothing is ever overwritten or deleted.
           client_uuid is unique so a flaky connection retrying the same
           offline submission can never double-count it.
           ════════════════════════════════════════════════════════════════ */
        Schema::create('retail_fullstocktaking_count_lines', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('base_product_id');

            $table->string('device_id', 120);
            $table->string('device_label', 120)->nullable();
            $table->unsignedBigInteger('submitted_by_user_id')->nullable();

            $table->decimal('quantity', 14, 3); // may be negative — see replaces_line_id
            $table->unsignedBigInteger('replaces_line_id')->nullable();
            $table->string('client_uuid', 60);

            $table->timestamps();

            $table->unique('client_uuid', 'fst_count_lines_client_uuid_unique');
            $table->index(['date', 'branch_id', 'base_product_id'], 'fst_count_lines_lookup_idx');
        });

        /* ════════════════════════════════════════════════════════════════
           4. MISSING PRODUCTS — same shape you already have, with the
              offline-sync columns the controller already reads/writes.
           ════════════════════════════════════════════════════════════════ */
        Schema::create('retail_fullstocktaking_missing_products', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('base_product_id');

            $table->string('product_name');
            $table->string('unit', 50);
            $table->decimal('price', 14, 2)->default(0);
            $table->decimal('quantity', 14, 3)->default(0);
            $table->decimal('rate', 8, 2)->default(1.00);

            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('product_status', 30)->default('Active');

            $table->string('client_uuid', 60)->nullable();
            $table->unsignedBigInteger('last_edited_by_user_id')->nullable();
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->unique(['date', 'branch_id', 'base_product_id'], 'fst_missing_unique');
        });

        /* ════════════════════════════════════════════════════════════════
           5. SUMMARY — your existing shape, PLUS status/started_at, which
              is the atomic lock-claim mechanism that replaces
              DB::transaction() in the controller. A single INSERT here
              IS the lock: whoever's INSERT lands first owns the
              rectification run. status flips pending → completed once
              every row write below has finished, so a crash mid-run
              leaves a visibly resumable 'pending' row instead of a
              half-applied, untracked mess.
           ════════════════════════════════════════════════════════════════ */
        Schema::create('retail_fullstocktaking_summary', function (Blueprint $table) {
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

            $table->unsignedInteger('missing_count')->default(0);
            $table->decimal('missing_value', 16, 2)->default(0);
            $table->decimal('full_difference_value', 16, 2)->default(0);

            $table->unsignedBigInteger('rectified_by_user_id')->nullable();
            $table->text('device_details')->nullable();

            $table->timestamps();

            $table->unique(['date', 'branch_id'], 'fst_summary_unique');
        });

        /* ════════════════════════════════════════════════════════════════
           6. SYNC DEVICE HEARTBEAT — covers BOTH offline stocktaking
              devices AND offline POS/sales devices for a branch+date.
           ────────────────────────────────────────────────────────────────
           Every device — whether counting stock or selling at the
           till — reports its local unsynced-operation count here on
           every successful sync call. pending_ops_count = 0 means that
           device has nothing left queued offline. Rectification reads
           this table and refuses to run while ANY device (of either
           type) for this branch+date still shows pending_ops_count > 0
           or has never reported in — this is what guarantees "all sales
           synced before rectify" without blocking selling at any point;
           selling itself is never paused, only the rectify BUTTON waits.
           ════════════════════════════════════════════════════════════════ */
        Schema::create('retail_fullstocktaking_sync_devices', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('branch_id');
            $table->string('device_id', 120);
            $table->string('device_label', 120)->nullable();
            $table->enum('device_type', ['stocktaking', 'pos']);

            $table->unsignedInteger('pending_ops_count')->default(0);
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();

            $table->unique(['date', 'branch_id', 'device_id', 'device_type'], 'fst_sync_device_unique');
            $table->index(['date', 'branch_id', 'device_type'], 'fst_sync_device_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_fullstocktaking_sync_devices');
        Schema::dropIfExists('retail_fullstocktaking_summary');
        Schema::dropIfExists('retail_fullstocktaking_missing_products');
        Schema::dropIfExists('retail_fullstocktaking_count_lines');
        Schema::dropIfExists('retail_fullstocktaking');
        Schema::dropIfExists('retail_fullstocktaking_session_snapshot');
    }
};