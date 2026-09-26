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
        Schema::create('event_setups', function (Blueprint $table) {
            $table->id();
            $table->string('event_header');
            $table->text('event_description');
            $table->enum('event_type', ['Holiday', 'Meeting', 'Meet-Up', 'Others']);
            $table->string('picture');
            $table->dateTime('event_date_from');
            $table->dateTime('event_date_to');
            $table->unsignedInteger('department_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_setups');
    }
};
