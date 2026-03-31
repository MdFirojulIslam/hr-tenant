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
        Schema::create('roster_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('employee_id');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->time('time_in');
            $table->time('time_out');
            $table->enum('work_from', ['home', 'office'])->default('office');
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roster_schedules');
    }
};
