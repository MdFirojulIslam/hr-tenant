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
        Schema::create('degrees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('Level_of_Education_Id');
            $table->string('Name',255);
            $table->unsignedBigInteger('created_by')->nullable();

            $table->foreign('Level_of_Education_Id')->references('id')->on('level_of_educations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('degrees');
    }
};
