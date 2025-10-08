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
        Schema::create('nominees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('EID')->unique();
            $table->string('Nominee_Name',50);
            $table->date('DOB');
            $table->string('Contact_No',20);
            $table->string('Email',50)->nullable();
            $table->string('NID',25)->nullable();
            $table->string('Share',25)->nullable();
            $table->text('Personal_Address')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('EID')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nominees');
    }
};
