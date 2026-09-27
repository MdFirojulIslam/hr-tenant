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
        Schema::create('hr_action_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('employee_id');
            $table->string('event_name');
            $table->string('event_details');
            $table->date('event_date');
            $table->date('new_event_date')->nullable();
            $table->string('remarks')->nullable();
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
        Schema::dropIfExists('hr_action_events');
    }
};
