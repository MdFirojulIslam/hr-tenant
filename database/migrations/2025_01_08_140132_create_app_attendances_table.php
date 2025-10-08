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
        Schema::create('app_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('EID');
            $table->unsignedBigInteger('town_id');
            $table->string('latitude');
            $table->string('longitude');
            $table->enum('status', ['in', 'out']);
            $table->string('location')->nullable();
            $table->string('postcode')->nullable();
            $table->string('village')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();
    
            $table->foreign('EID')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('town_id')->references('id')->on('towns')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_attendances');
    }
};
