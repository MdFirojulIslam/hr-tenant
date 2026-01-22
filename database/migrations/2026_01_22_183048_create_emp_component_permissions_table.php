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
        Schema::create('emp_component_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('employee_id')->nullable();
            $table->unsignedInteger('component_permissions_id')->nullable();
            $table->unsignedInteger('component_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emp_component_permissions');
    }
};
