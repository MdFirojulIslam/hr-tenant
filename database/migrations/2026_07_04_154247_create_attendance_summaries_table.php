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
        Schema::create('attendance_summaries', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id');
            $table->unsignedInteger('department_id');
            $table->date('date');
            $table->time('in_time')->nullable();
            $table->time('out_time')->nullable();
            $table->enum('status', ['Present', 'Absent', 'Late', 'On Leave', 'Holiday', 'Manual'])->default('Present')->nullable();
            $table->time('rostered_in_time')->nullable();
            $table->time('rostered_out_time')->nullable();
            $table->enum('rostered_status', ['Home', 'Weekend', 'Office'])->nullable();
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_summaries');
    }
};
