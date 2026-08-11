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
        Schema::create('outlet_visit_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('outlet_week_schedules_id');
            $table->unsignedInteger('employee_id');
            $table->unsignedInteger('distributor_id');
            $table->string('outlet_code');
            $table->string('outlet_name');
            $table->date('visit_date')->nullable()->default(null);
            $table->boolean('is_active')->default(true);
            $table->dateTime('inactivated_on')->nullable()->default(null);
            $table->unsignedInteger('inactivated_by')->nullable();
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outlet_visit_assignments');
    }
};
