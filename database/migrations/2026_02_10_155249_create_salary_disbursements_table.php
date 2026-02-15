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
        Schema::create('salary_disbursements', function (Blueprint $table) {
            $table->id();
            $table->date('salary_month');
            $table->enum('status', ['PENDING', 'COMPLETED'])->default('PENDING');
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('appro_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_disbursements');
    }
};
