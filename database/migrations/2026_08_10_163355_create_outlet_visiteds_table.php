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
        Schema::create('outlet_visiteds', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('outlet_visits_assignments_id'); // outlet_visited_assignments.id
            $table->date('visited_date')->nullable();
            $table->string('lattitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->boolean('is_visited')->default(true);
            $table->boolean('accepted')->default(true);
            $table->string('image_path')->nullable();
            $table->unsignedInteger('created_by')->nullable(); // tenant outlets.id (logical only)
            $table->unsignedInteger('updated_by')->nullable();
            $table->datetime('in_time')->nullable();
            $table->datetime('out_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outlet_visiteds');
    }
};
