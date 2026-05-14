<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrPayrollTables extends Migration
{
    public function up()
    {
        /*
        |------------------------------------------------------------
        | 1. PENSION ENROLLMENT
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
            $table->enum('status', ['active','suspended','exited'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        /*
        |------------------------------------------------------------
        | 2. LOANS
        |    An employee can have multiple loans but only one
        |    active at a time (enforced in controller).
        |    monthly_deduction is pulled into payroll entries.
        |    balance_remaining is reduced each pay cycle.
        |------------------------------------------------------------
        */
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->decimal('loan_amount',       15, 2);
            $table->decimal('balance_remaining', 15, 2);
            $table->decimal('monthly_deduction', 15, 2);
            $table->date('start_date');
            $table->date('expected_end_date')->nullable();
            $table->enum('status', ['active','completed','cancelled'])->default('active');
            $table->string('purpose')->nullable();
            $table->string('approved_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        /*
        |------------------------------------------------------------
        | 3. ADVANCES
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
            $table->enum('status', ['active','recovered','cancelled'])->default('active');
            $table->string('approved_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        /*
        |------------------------------------------------------------
        | 4. PAYROLL PERIODS
        |    One row per monthly run. Admin creates this first.
        |    Status flow: draft → processing → approved → paid
        |------------------------------------------------------------
        */
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');                  // "June 2026"
            $table->date('period_start');
            $table->date('period_end');
            $table->date('pay_date');
            $table->enum('status', ['draft','processing','approved','paid'])
                  ->default('draft');
            $table->string('created_by')->nullable();
            $table->string('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        /*
        |------------------------------------------------------------
        | 5. PAYROLL ENTRIES
        |    Auto-generated. One row per employee per period.
        |    Stores every earning and deduction line so the
        |    payslip is a pure read of this row — no recomputing.
        |
        |    gross_pay     = basic + housing + transport +
        |                    other_allowances + overtime
        |    total_deductions = paye + pension_employee +
        |                       loan_deduction + advance_deduction +
        |                       other_deductions
        |    net_pay       = gross_pay - total_deductions
        |------------------------------------------------------------
        */
        Schema::create('payroll_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_period_id');
            $table->unsignedBigInteger('employee_id');

            // ── Earnings ──────────────────────────────────────────
            $table->decimal('basic_salary',         15, 2)->default(0);
            $table->decimal('housing_allowance',    15, 2)->default(0);
            $table->decimal('transport_allowance',  15, 2)->default(0);
            $table->decimal('other_allowances',     15, 2)->default(0);
            $table->decimal('overtime_amount',      15, 2)->default(0);
            $table->decimal('gross_pay',            15, 2)->default(0);

            // ── Statutory deductions ──────────────────────────────
            $table->boolean('on_pension')->default(false);
            $table->decimal('pension_employee',     15, 2)->default(0);
            $table->decimal('pension_employer',     15, 2)->default(0);
            $table->decimal('paye',                 15, 2)->default(0);

            // ── Other deductions ──────────────────────────────────
            $table->decimal('loan_deduction',       15, 2)->default(0);
            $table->decimal('advance_deduction',    15, 2)->default(0);
            $table->decimal('other_deductions',     15, 2)->default(0);

            // ── Totals (computed on save) ─────────────────────────
            $table->decimal('total_deductions',     15, 2)->default(0);
            $table->decimal('net_pay',              15, 2)->default(0);

            $table->string('payslip_path')->nullable();
            $table->enum('status', ['draft','approved','paid'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id']);
        });

        /*
        |------------------------------------------------------------
        | 6. OFFER LETTERS
        |    Track every generated letter per employee.
        |    file_path stores the PDF so it can be re-downloaded.
        |------------------------------------------------------------
        */
        Schema::create('offer_letters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->enum('letter_type', [
                'Offer','Confirmation','Promotion','Termination'
            ])->default('Offer');
            $table->date('issue_date');
            $table->date('start_date')->nullable();
            $table->decimal('offered_salary',    15, 2)->nullable();
            $table->string('offered_position')->nullable();
            $table->string('offered_department')->nullable();
            $table->string('file_path')->nullable();
            $table->string('generated_by')->nullable();
            $table->text('notes')->nullable();
            $table->string('custom_message')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('offer_letters');
        Schema::dropIfExists('payroll_entries');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('employee_advances');
        Schema::dropIfExists('employee_loans');
        Schema::dropIfExists('employee_pension');
    }
}