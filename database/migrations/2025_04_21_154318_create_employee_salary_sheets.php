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
        Schema::create('employee_salary_sheets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('EID');
            $table->date('Month');
            $table->decimal('Gross_Salary', 10, 2)->default(0.00);
            $table->decimal('Basic', 10, 2)->default(0.00);
            $table->decimal('House_Rent', 10, 2)->default(0.00);
            $table->decimal('Conveyance', 10, 2)->default(0.00);
            $table->decimal('Medical_Allowance', 10, 2)->default(0.00);
            $table->decimal('Consolidated', 10, 2)->default(0.00);
            $table->decimal('Festival_Bonus', 10, 2)->default(0.00);
            $table->decimal('Travel_Allowance', 10, 2)->default(0.00);
            $table->decimal('Overtime', 10, 2)->default(0.00);
            $table->decimal('Daily_Allowance', 10, 2)->default(0.00);
            $table->decimal('Mobile_Bill', 10, 2)->default(0.00);
            $table->decimal('Other_Allowance', 10, 2)->default(0.00);

            $table->decimal('Total_Payment', 10, 2)->default(0.00);
            $table->decimal('Provident_Fund', 10, 2)->default(0.00);
            $table->decimal('Income_Tax', 10, 2)->default(0.00);
            $table->decimal('Revenue_Stamp', 10, 2)->default(0.00);
            $table->decimal('Leave_Without_Pay', 10, 2)->default(0.00);
            $table->decimal('Penalty_Other_Deduction', 10, 2)->default(0.00);
            $table->decimal('Advance', 10, 2)->default(0.00);
            $table->decimal('Loan_Payable', 10, 2)->default(0.00);
            $table->decimal('Other_Deduction', 10, 2)->default(0.00);

            $table->decimal('Total_Deduction', 10, 2)->default(0.00);
            $table->decimal('Net_Pay', 10, 2)->default(0.00);
            $table->string('Salary_Type', 255)->nullable();
            $table->string('Bank_Account_No', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('EID')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_salary_sheet');
    }
};
