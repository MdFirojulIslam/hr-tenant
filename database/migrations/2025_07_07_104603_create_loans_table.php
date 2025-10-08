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
            $table->unsignedBigInteger('EID');
            $table->decimal('Principal', 14, 2);
            $table->unsignedTinyInteger('Tenure_Months'); 
            $table->decimal('Monthly_Installment', 14, 2);
            $table->date('First_Due_On');
            $table->enum('Status', ['active', 'closed', 'cancelled'])->default('active');
            $table->string('Remarks', 255)->nullable();
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
        Schema::dropIfExists('loans');
    }
};
