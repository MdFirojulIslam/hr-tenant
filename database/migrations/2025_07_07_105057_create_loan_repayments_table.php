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
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('Loan_Id');
            $table->date('Due_On');
            $table->decimal('Amount', 14, 2);
            $table->boolean('Is_Paid')->default(false);
            $table->date('Paid_On')->nullable();
            $table->timestamps();

            $table->foreign('Loan_Id')->references('id')->on('loans')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
    }
};
