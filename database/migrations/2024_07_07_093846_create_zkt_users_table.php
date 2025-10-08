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
        Schema::create('zkt_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('User_Id')->unique();
            $table->unsignedBigInteger('Machine_Id');
            $table->string('Name');
            $table->string('Card_No')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            // $table->foreign('Machine_Id')->references('id')->on('zkt_machines')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zkt_users');
    }
};
