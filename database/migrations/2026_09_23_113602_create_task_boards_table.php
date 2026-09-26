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
        Schema::create('task_boards', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('assigned_to');
            $table->unsignedInteger('assigned_by');
            $table->string('task_name');
            $table->text('description');
            $table->enum('status', ['Assigned', 'Complete', 'Incomplete']);
            $table->dateTime('task_assigned_at');
            $table->dateTime('task_finished_at');
            $table->text('remarks')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_boards');
    }
};
