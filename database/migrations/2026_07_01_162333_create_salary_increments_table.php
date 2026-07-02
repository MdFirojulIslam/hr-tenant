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
        Schema::create('salary_increments', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id');
            $table->decimal('previous_gross_salary', 10, 2)->default(0);
            $table->decimal('increment_amount', 10, 2)->default(0);
            $table->decimal('previous_net_pay', 10, 2)->default(0);
            $table->decimal('current_net_pay', 10, 2)->default(0);
            $table->date('increment_dates')->nullable();
            $table->string('remarks')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_increments');
    }
};
