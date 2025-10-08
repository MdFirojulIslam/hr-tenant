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
        Schema::create('zkt_attendences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('User_id');
            $table->unsignedBigInteger('Machine_Id');
            $table->date('date');
            $table->time('time');
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unique(['date', 'time']);

            // $table->foreign('Machine_Id')->references('id')->on('zkt_machines')->onDelete('cascade');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zkt_attendences');
    }
};
