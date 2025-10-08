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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('Device_Name');
            $table->unsignedBigInteger('Device_Code')->unique();
            $table->text('Description')->nullable();
            $table->unsignedBigInteger('Device_Type_Id');
            $table->date('Purchase_Date')->nullable();
            $table->decimal('Price')->nullable();
            $table->string('Serial_Number',100)->unique()->nullable();
            $table->date('Warranty_Expiration')->nullable();
            $table->enum('Status', ['Available', 'Assigned', 'Maintenance', 'Disposed'])->default('Available');
            $table->timestamps();

            $table->foreign('Device_Type_Id')->references('id')->on('device_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
