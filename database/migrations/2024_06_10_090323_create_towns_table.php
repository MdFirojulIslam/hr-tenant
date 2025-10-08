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
        Schema::create('towns', function (Blueprint $table) {
            $table->id();
            $table->string('Name',255);
            $table->unsignedBigInteger('DMS_Town_Id');
            $table->string('Domain_Name',255);
            $table->string('DB_Name',255);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('towns');
    }
};
