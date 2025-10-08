<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('salary_adjustments', function (Blueprint $table) {
             DB::statement("ALTER TABLE salary_adjustments MODIFY COLUMN Type ENUM('earning', 'deduction') NOT NULL");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_adjustments', function (Blueprint $table) {
            DB::statement("ALTER TABLE salary_adjustments MODIFY COLUMN Type ENUM('advance', 'deduction') NOT NULL");
        });
    }
};
