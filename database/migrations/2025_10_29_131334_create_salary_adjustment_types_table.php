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
        Schema::create('salary_adjustment_types', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_name');
            $table->tinyInteger('adjustment_type')->comment('1=Addition, 0=Deduction');
            $table->tinyInteger('status')->comment('1=Active, 0=Inactive')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_adjustment_types');
    }
};
