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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('Vehicle_Id')->nullable();
            $table->string('Name');
            $table->string('Model');
            $table->string('Reg_No');
            $table->string('Engine_No');
            $table->string('Chassis_No');
            $table->string('Tracking_No')->nullable();
            $table->unsignedBigInteger('Driver_Id')->nullable();
            $table->unsignedBigInteger('Supervisor_Id')->nullable();
            $table->string('Tax_Expire_Date')->nullable();
            $table->string('Fitness_Expire_Date')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();

            $table->foreign('Driver_Id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('Supervisor_Id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
