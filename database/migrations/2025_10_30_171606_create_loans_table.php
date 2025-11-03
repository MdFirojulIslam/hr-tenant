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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->decimal('principal_amounts', 14, 2);
            $table->unsignedTinyInteger('tenure_months');
            $table->decimal('monthly_installments', 14, 2);
            $table->date('installment_dates');
            $table->date('loan_created_at')->nullable();
            $table->enum('status', ['active', 'closed', 'cancelled'])->default('active');
            $table->string('remarks', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
