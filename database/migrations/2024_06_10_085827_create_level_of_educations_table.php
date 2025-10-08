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
        Schema::create('level_of_educations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('education_id')->nullable()->default(null);
            $table->string('Name',150);
            $table->unsignedBigInteger('created_by')->nullable();

            $table->foreign('education_id')->references('id')->on('level_of_educations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('level_of_educations');
    }
};
