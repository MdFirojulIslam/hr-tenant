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
        Schema::create('itineraries_updates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('itineraries_id');  // FK to itineraries
            $table->unsignedInteger('employee_id'); // FK to itineraries_logs
            $table->date('visit_date');
            $table->enum('status', ['PLAN', 'ACTUAL'])->default('PLAN');
            $table->unsignedInteger('town_id');
            $table->string('visit_with')->nullable();
            $table->string('location')->nullable();
            $table->string('visit_objective')->nullable();
            $table->string('meeting_with')->nullable();
            $table->time('visit_start_time')->nullable();
            $table->time('visit_end_time')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('supervisor_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itineraries_updates');
    }
};
