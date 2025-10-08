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
        Schema::create('work_experiences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('EID');
            $table->string('Company_Name',255);
            $table->string('Company_Business',255)->nullable();
            $table->text('Company_Address')->nullable();
            $table->string('Designation',100);
            $table->string('Department',100);
            $table->date('From_Date');
            $table->date('To_Date');
            $table->string('Duration',50);
            $table->text('Responsibilities')->nullable();
            $table->string('Last_Salary')->nullable();
            $table->text('Continuing')->nullable();
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
        Schema::dropIfExists('work_experiences');
    }
};
