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
        Schema::create('itineraries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('itinarary_logs_id');  // FK to itinerary_logs
            $table->date('visit_date');
            $table->unsignedInteger('town_id');
            $table->unsignedInteger('visit_with_id')->nullable();
            $table->string('location')->nullable();
            $table->string('visit_objective')->nullable();
            $table->string('meeting_with')->nullable();
            $table->time('visit_start_time')->nullable();
            $table->time('visit_end_time')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('itineraries');
    }
};
