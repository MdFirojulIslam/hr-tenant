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
        Schema::create('training_infos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('EID');
            $table->string('Training_Title',255);
            $table->string('Organized_By',255)->nullable();
            $table->text('Topic_Covered')->nullable();
            $table->string('Duration')->nullable();
            $table->string('Year')->nullable();
            $table->text('Remarks')->nullable();
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
        Schema::dropIfExists('training_infos');
    }
};
