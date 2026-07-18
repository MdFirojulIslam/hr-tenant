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
        Schema::create('incentive_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incentive_id')
                ->constrained('incentives')
                ->cascadeOnDelete();
            $table->unsignedInteger('employee_id');
            $table->unsignedInteger('department_id');
            $table->unsignedInteger('amounts');
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('updated_by');
            $table->string('remarks');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incentive_details');
    }
};
