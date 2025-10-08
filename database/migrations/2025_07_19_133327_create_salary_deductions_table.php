<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('salary_deductions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('employee_id');
            $table->enum('adjustment_type', ['advance', 'other_deductions']);
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->text('remarks')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_deductions');
    }
};
