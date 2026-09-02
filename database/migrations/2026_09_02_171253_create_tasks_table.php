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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->text('task_name');
            $table->date('start_date');
            $table->date('due_date');
            $table->date('new_due_date')->nullable();;
            $table->enum('status', ['Not started', 'In progress', 'Completed']);
            $table->enum('repeat', ['Do not repeat', 'Daily', 'Custom']);
            $table->enum('bucket', ['No bucket', 'Backlog', 'Up next', 'In progress', 'Blocked', 'Completed']);
            $table->enum('priority', ['Urgent', 'Important', 'Medium', 'Low']);
            $table->enum('privacy', ['Public', 'Private', 'Shared', 'Others']);
            $table->text('notes')->nullable();
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
        Schema::dropIfExists('tasks');
    }
};
