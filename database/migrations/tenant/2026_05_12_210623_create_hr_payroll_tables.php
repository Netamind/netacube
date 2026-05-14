<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |------------------------------------------------------------
        | 1. EMPLOYEE ALLOWANCES
        |    One row per employee. Source of truth for all standing
        |    recurring and variable allowance amounts.
        |
        |    RECURRING TIER — included in every payroll run
        |    automatically. Set once, fires every month.
        |
        |    VARIABLE TIER — set before a run for one-off or
        |    irregular payments. Auto-reset to 0 after generate
        |    when variable_reset_on_generate = true.
        |    Set variable_reset_on_generate = false to carry a
        |    variable amount across runs (e.g. a long-term
        |    acting appointment that spans several months).
        |
        |    gross_pay computation in payroll entries:
        |    basic_salary
        |    + housing_allowance + transport_allowance
        |    + medical_allowance + meal_allowance
        |    + other_recurring_allowance
        |    + acting_allowance + commissions
        |    + other_variable_allowance
        |    + overtime_amount
        |------------------------------------------------------------
        */
        Schema::create('employee_allowances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->unique();

            // ── RECURRING TIER ────────────────────────────────────
            $table->decimal('housing_allowance',               12, 2)->default(0.00);
            $table->decimal('transport_allowance',             12, 2)->default(0.00);
            $table->decimal('medical_allowance',               12, 2)->default(0.00);
            $table->decimal('meal_allowance',                  12, 2)->default(0.00);
            $table->decimal('other_recurring_allowance',       12, 2)->default(0.00);
            $table->string('other_recurring_allowance_label', 100)->nullable();   // e.g. "Hardship Allowance"

            // ── VARIABLE TIER ─────────────────────────────────────
            $table->decimal('acting_allowance',                12, 2)->default(0.00);
            $table->decimal('commissions',                     12, 2)->default(0.00);
            $table->decimal('other_variable_allowance',        12, 2)->default(0.00);
            $table->string('other_variable_allowance_label',  100)->nullable();   // e.g. "Project Bonus"

            // ── CONTROL COLUMNS ───────────────────────────────────
            $table->boolean('variable_reset_on_generate')->default(true);
            $table->date('effective_from');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });

        /*
        |------------------------------------------------------------
        | 2. EMPLOYEE ALLOWANCE HISTORY
        |    Immutable snapshot written every time the allowance
        |    record is updated. Allows you to see exactly what an
        |    employee was earning in any past period.
        |------------------------------------------------------------
        */
        Schema::create('employee_allowance_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');

            // ── Snapshot of recurring tier ────────────────────────
            $table->decimal('housing_allowance',               12, 2)->default(0.00);
            $table->decimal('transport_allowance',             12, 2)->default(0.00);
            $table->decimal('medical_allowance',               12, 2)->default(0.00);
            $table->decimal('meal_allowance',                  12, 2)->default(0.00);
            $table->decimal('other_recurring_allowance',       12, 2)->default(0.00);
            $table->string('other_recurring_allowance_label', 100)->nullable();

            // ── Snapshot of variable tier ─────────────────────────
            $table->decimal('acting_allowance',                12, 2)->default(0.00);
            $table->decimal('commissions',                     12, 2)->default(0.00);
            $table->decimal('other_variable_allowance',        12, 2)->default(0.00);
            $table->string('other_variable_allowance_label',  100)->nullable();

            // ── Audit metadata ────────────────────────────────────
            $table->string('change_reason')->nullable();   // e.g. "Annual review", "Promotion"
            $table->string('changed_by')->nullable();      // Auth user name at time of change
            $table->date('effective_from');                // Copied from the allowance row

            // History rows are immutable — no updated_at
            $table->timestamp('created_at')->nullable();

            $table->index('employee_id');
            $table->index('effective_from');

            $table->foreign('employee_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });

        /*
        |------------------------------------------------------------
        | 3. PENSION ENROLLMENT
        |    One row per employee. Stores fund, rates, status.
        |    Read when generating payroll entries to decide
        |    whether to compute pension deductions.
        |------------------------------------------------------------
        */
        Schema::create('employee_pension', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->unique();
            $table->string('pension_fund_name')->nullable();
            $table->string('pension_member_number')->nullable();
            $table->decimal('employee_rate', 5, 2)->default(5.00);
            $table->decimal('employer_rate', 5, 2)->default(10.00);
            $table->date('enrolled_on');
            $table->enum('status', ['active', 'suspended', 'exited'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });

        /*
        |------------------------------------------------------------
        | 4. LOANS
        |    An employee can have multiple loans but only one
        |    active at a time (enforced in controller).
        |    monthly_deduction is pulled into payroll entries.
        |    balance_remaining is reduced each pay cycle.
        |------------------------------------------------------------
        */
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->decimal('loan_amount',        15, 2);
            $table->decimal('balance_remaining',  15, 2);
            $table->decimal('monthly_deduction',  15, 2);
            $table->date('start_date');
            $table->date('expected_end_date')->nullable();
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->string('purpose')->nullable();
            $table->string('approved_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });

        /*
        |------------------------------------------------------------
        | 5. ADVANCES
        |    Salary advance taken before payday.
        |    monthly_deduction recovered over agreed months.
        |    balance_remaining reduced each pay cycle.
        |------------------------------------------------------------
        */
        Schema::create('employee_advances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->decimal('advance_amount',    15, 2);
            $table->decimal('balance_remaining', 15, 2);
            $table->decimal('monthly_deduction', 15, 2);
            $table->date('advance_date');
            $table->enum('status', ['active', 'recovered', 'cancelled'])->default('active');
            $table->string('approved_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });

        /*
        |------------------------------------------------------------
        | 6. PAYROLL PERIODS
        |    One row per monthly run. Admin creates this first.
        |    Status flow: draft → processing → approved → paid
        |------------------------------------------------------------
        */
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // e.g. "June 2026"
            $table->date('period_start');
            $table->date('period_end');
            $table->date('pay_date');
            $table->enum('status', ['draft', 'processing', 'approved', 'paid'])->default('draft');
            $table->string('created_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        /*
        |------------------------------------------------------------
        | 7. PAYROLL ENTRIES
        |    Auto-generated. One row per employee per period.
        |    Every allowance column mirrors employee_allowances so
        |    the payslip is a pure read — no recomputing.
        |
        |    gross_pay = basic_salary
        |              + housing_allowance + transport_allowance
        |              + medical_allowance + meal_allowance
        |              + other_recurring_allowance
        |              + acting_allowance + commissions
        |              + other_variable_allowance
        |              + overtime_amount
        |
        |    total_deductions = paye + pension_employee
        |                     + loan_deduction + advance_deduction
        |                     + other_deductions
        |
        |    net_pay = gross_pay - total_deductions
        |------------------------------------------------------------
        */
        Schema::create('payroll_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedBigInteger('employee_id');

            // ── Earnings — base ───────────────────────────────────
            $table->decimal('basic_salary',                    15, 2)->default(0);

            // ── Earnings — recurring allowances ──────────────────
            // (mirrors employee_allowances recurring tier)
            $table->decimal('housing_allowance',               15, 2)->default(0);
            $table->decimal('transport_allowance',             15, 2)->default(0);
            $table->decimal('medical_allowance',               15, 2)->default(0);
            $table->decimal('meal_allowance',                  15, 2)->default(0);
            $table->decimal('other_recurring_allowance',       15, 2)->default(0);
            $table->string('other_recurring_allowance_label', 100)->nullable();

            // ── Earnings — variable allowances ───────────────────
            // (mirrors employee_allowances variable tier)
            $table->decimal('acting_allowance',                15, 2)->default(0);
            $table->decimal('commissions',                     15, 2)->default(0);
            $table->decimal('other_variable_allowance',        15, 2)->default(0);
            $table->string('other_variable_allowance_label',  100)->nullable();

            // ── Earnings — overtime & gross ───────────────────────
            $table->decimal('overtime_amount',                 15, 2)->default(0);
            $table->decimal('gross_pay',                       15, 2)->default(0);

            // ── Statutory deductions ──────────────────────────────
            $table->boolean('on_pension')->default(false);
            $table->decimal('pension_employee',                15, 2)->default(0);
            $table->decimal('pension_employer',                15, 2)->default(0);  // recorded for employer reporting
            $table->decimal('paye',                            15, 2)->default(0);

            // ── Other deductions ──────────────────────────────────
            $table->decimal('loan_deduction',                  15, 2)->default(0);
            $table->decimal('advance_deduction',               15, 2)->default(0);
            $table->decimal('other_deductions',                15, 2)->default(0);

            // ── Totals (computed on generate, stored flat) ────────
            $table->decimal('total_deductions',                15, 2)->default(0);
            $table->decimal('net_pay',                         15, 2)->default(0);

            $table->string('payslip_path')->nullable();
            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id']);

            $table->foreign('payroll_period_id')
                  ->references('id')
                  ->on('payroll_periods')
                  ->onDelete('cascade');

            $table->foreign('employee_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });

        /*
        |------------------------------------------------------------
        | 8. OFFER LETTERS
        |    Track every generated letter per employee.
        |    file_path stores the PDF so it can be re-downloaded.
        |------------------------------------------------------------
        */
        Schema::create('offer_letters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->enum('letter_type', [
                'Offer', 'Confirmation', 'Promotion', 'Termination',
            ])->default('Offer');
            $table->date('issue_date');
            $table->date('start_date')->nullable();
            $table->decimal('offered_salary',    15, 2)->nullable();
            $table->string('offered_position')->nullable();
            $table->string('offered_department')->nullable();
            $table->string('file_path')->nullable();
            $table->string('generated_by')->nullable();
            $table->string('custom_message')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });

        /*
        |------------------------------------------------------------
        | 9. PAYE BRACKETS
        |    Tax bands used to compute PAYE during payroll generate.
        |    effective_to = null means the band is currently active.
        |    Multiple band sets can coexist; the generator selects
        |    rows WHERE effective_from <= pay_date
        |              AND (effective_to IS NULL OR effective_to >= pay_date)
        |    Seeded below with current MRA Malawi monthly bands.
        |------------------------------------------------------------
        */
        Schema::create('paye_brackets', function (Blueprint $table) {
            $table->id();
            $table->decimal('income_from', 15, 2);
            $table->decimal('income_to',   15, 2)->nullable();  // null = no ceiling (top band)
            $table->decimal('rate',         5, 2);              // percentage e.g. 25.00
            $table->date('effective_from');
            $table->date('effective_to')->nullable();            // null = currently active
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // ── Seed current MRA Malawi monthly brackets (FY 2024/25) ────────
        $now = now();
        DB::table('paye_brackets')->insert([
            [
                'income_from'    => 0,
                'income_to'      => 100000,
                'rate'           => 0.00,
                'effective_from' => '2024-07-01',
                'effective_to'   => null,
                'description'    => 'Tax-free personal allowance',
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'income_from'    => 100000,
                'income_to'      => 1000000,
                'rate'           => 25.00,
                'effective_from' => '2024-07-01',
                'effective_to'   => null,
                'description'    => '25% band',
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'income_from'    => 1000000,
                'income_to'      => 3000000,
                'rate'           => 30.00,
                'effective_from' => '2024-07-01',
                'effective_to'   => null,
                'description'    => '30% band',
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
            [
                'income_from'    => 3000000,
                'income_to'      => null,
                'rate'           => 35.00,
                'effective_from' => '2024-07-01',
                'effective_to'   => null,
                'description'    => '35% band (no ceiling)',
                'created_at'     => $now,
                'updated_at'     => $now,
            ],
        ]);

        /*
        |------------------------------------------------------------
        | 10. PAYSLIP EMAIL LOGS
        |     One row per send attempt (single or bulk dispatch).
        |     Records recipient, outcome, and who triggered the send.
        |     Depends on payroll_entries, payroll_periods, and users
        |     — created last so all FK targets already exist.
        |------------------------------------------------------------
        */
        Schema::create('payslip_email_logs', function (Blueprint $table) {
            $table->id();

            // ── References ────────────────────────────────────────
            $table->unsignedBigInteger('payroll_entry_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('payroll_period_id');

            // ── Delivery info ─────────────────────────────────────
            $table->string('recipient_email', 255);
            $table->enum('send_type', ['single', 'bulk'])->default('single');
            $table->enum('status', ['sent', 'failed', 'skipped'])->default('sent');

            // ── Optional content ──────────────────────────────────
            $table->text('note')->nullable();
            $table->string('sent_by', 255)->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────
            $table->index('payroll_entry_id');
            $table->index('employee_id');
            $table->index('payroll_period_id');
            $table->index('status');
            $table->index('send_type');

            $table->foreign('payroll_entry_id')
                  ->references('id')
                  ->on('payroll_entries')
                  ->onDelete('cascade');

            $table->foreign('employee_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->foreign('payroll_period_id')
                  ->references('id')
                  ->on('payroll_periods')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_email_logs');
        Schema::dropIfExists('paye_brackets');
        Schema::dropIfExists('offer_letters');
        Schema::dropIfExists('payroll_entries');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('employee_advances');
        Schema::dropIfExists('employee_loans');
        Schema::dropIfExists('employee_pension');
        Schema::dropIfExists('employee_allowance_history');
        Schema::dropIfExists('employee_allowances');
    }
};