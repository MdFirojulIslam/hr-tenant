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
        Schema::create('employee_expense_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('EID');
            $table->boolean('Date_Lock')->default(1);
            $table->boolean('Attendance_Lock')->default(1);
            $table->timestamps();

            $table->foreign('EID')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_expense_permissions');
    }
};
