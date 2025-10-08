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
        Schema::create('attendences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('EID');
            $table->date('Date');
            $table->string('Time_In',20)->nullable();
            $table->string('Time_Out',20)->nullable();
            $table->string('Status', 20)->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // $table->foreign('EID')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendences');
    }
};
