<?php

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
        Schema::create('salary_sheets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('employee_id');
            $table->date('salary_date');
            $table->decimal('gross_salary', 10, 2);
            $table->decimal('basic_salary', 10, 2);
            $table->decimal('house_rent', 10, 2);
            $table->decimal('conveyance', 10, 2);
            $table->decimal('medical_allowance', 10, 2);
            $table->decimal('consolidated', 10, 2);
            $table->decimal('festival_bonus', 10, 2);
            $table->decimal('travel_allowance', 10, 2);
            $table->decimal('overtime', 10, 2);
            $table->decimal('daily_allowance', 10, 2)->default(0);
            $table->decimal('mobile_bill', 10, 2);
            $table->decimal('other_allowance', 10, 2);
            $table->decimal('earning_adjustment', 10, 2)->default(0);
            $table->decimal('total_payment', 10, 2);
            $table->decimal('provident_fund', 10, 2);
            $table->decimal('income_tax', 10, 2);
            $table->decimal('revenue_stamp', 10, 2);
            $table->decimal('leave_without_pay', 10, 2)->default(0);
            $table->decimal('deduction_adjustment', 10, 2)->default(0);
            $table->decimal('total_deduction', 10, 2);
            $table->decimal('monthly_loan_repayments', 10, 2)->default(0);
            $table->unsignedInteger('present_days');
            $table->unsignedInteger('absent_days');
            $table->unsignedInteger('late_days');
            $table->decimal('net_pay', 10, 2);
            $table->enum('salary_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->enum('payment_method', ['bank_transfer', 'cash'])->default('bank_transfer');
            $table->string('bank_account_no', 100)->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_sheets');
    }
};
