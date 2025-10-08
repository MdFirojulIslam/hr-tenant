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
        Schema::create('vehicle_requisitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('EID');
            $table->unsignedBigInteger('Vehicle_Id');
            $table->date('From_Date');
            $table->date('To_Date');
            $table->time('Pickup_Time')->nullable();
            $table->time('Drop_Time')->nullable();
            $table->string('Pickup_Location', 255)->nullable();
            $table->string('Drop_Location', 255)->nullable();
            $table->string('Purpose', 255);
            $table->text('Description')->nullable();
            $table->string('Status')->default('pending');
            $table->timestamps();

            $table->foreign('EID')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('Vehicle_Id')->references('id')->on('vehicles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_requisitions');
    }
};
