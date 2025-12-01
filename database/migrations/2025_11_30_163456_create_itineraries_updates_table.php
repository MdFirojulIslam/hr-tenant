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
            $table->date('visit_date');
            $table->unsignedInteger('town_id');
            $table->unsignedInteger('visit_with_id')->nullable();
            $table->string('location')->nullable();
            $table->string('visit_objective')->nullable();
            $table->string('meeting_with')->nullable();
            $table->time('visit_start_time')->nullable();
            $table->time('visit_end_time')->nullable();
            $table->unsignedBigInteger('updated_by');
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
