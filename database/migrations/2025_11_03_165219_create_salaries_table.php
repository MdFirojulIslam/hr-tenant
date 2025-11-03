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
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->unique();
            $table->decimal('gross_salary', 10, 2)->default(0.00);
            $table->decimal('basic', 10, 2)->default(0.00);
            $table->decimal('house_rent', 10, 2)->default(0.00);
            $table->decimal('conveyance', 10, 2)->default(0.00);
            $table->decimal('medical_allowance', 10, 2)->default(0.00);
            $table->decimal('consolidated', 10, 2)->default(0.00);
            $table->decimal('festival_bonus', 10, 2)->default(0.00);
            $table->decimal('travel_allowance', 10, 2)->default(0.00);
            $table->decimal('daily_allowance', 10, 2)->default(0.00);
            $table->decimal('mobile_bill', 10, 2)->default(0.00);
            $table->decimal('other_allowance', 10, 2)->default(0.00);

            $table->decimal('total_payment', 10, 2)->default(0.00);
            $table->decimal('provident_fund', 10, 2)->default(0.00);
            $table->decimal('income_tax', 10, 2)->default(0.00);
            $table->decimal('revenue_stamp', 10, 2)->default(0.00);
            $table->decimal('net_pay', 10, 2)->default(0.00);
            $table->string('salary_type', 255)->nullable();
            $table->string('bank_account_no', 255)->unique()->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
