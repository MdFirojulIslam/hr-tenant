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
        Schema::create('employee_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('EID');
            $table->unsignedBigInteger('Device_Id');
            $table->date('Assigned_Date');
            $table->date('Return_Date')->nullable();
            $table->text('Remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('EID')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('Device_Id')->references('id')->on('assets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_assets');
    }
};
