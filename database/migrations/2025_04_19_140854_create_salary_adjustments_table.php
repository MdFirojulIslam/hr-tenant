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
        Schema::create('salary_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('EID');
            $table->date('Month');
            $table->enum('Type', ['advance', 'deduction']);
            $table->decimal('Amount', 10, 2);
            $table->text('Remarks')->nullable();
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
        Schema::dropIfExists('salary_adjustments');
    }
};
