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
        Schema::create('emplooyee_expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('EID');
            $table->unsignedBigInteger('Town_Id');
            $table->date('Date');
            $table->string('Tr_Type',255);
            $table->string('Description', 255)->nullable();
            $table->string('From', 255)->nullable();
            $table->string('To', 255)->nullable();
            $table->string('Vehicle', 255)->nullable();
            $table->string('File_Path', 255)->nullable();
            $table->decimal('Amount');
            $table->integer('Status')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('EID')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('Town_Id')->references('id')->on('towns')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emplooyee_expenses');
    }
};
