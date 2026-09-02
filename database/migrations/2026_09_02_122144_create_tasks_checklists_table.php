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
        Schema::create('tasks_checklists', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('task_id');
            $table->string('checklist_name')->nullable();
            $table->boolean('completed_status')->default(false);
            $table->unsignedInteger('completed_by')->nullable();
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
        Schema::dropIfExists('tasks_checklists');
    }
};
