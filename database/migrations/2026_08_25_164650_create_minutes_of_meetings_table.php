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
        Schema::create('minutes_of_meetings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('eid')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->time('str_time');
            $table->time('end_time');

            $table->foreignId('town')
                ->constrained('towns');

            $table->date('date');
            $table->string('meeting_with');
            $table->text('mom');
            $table->string('file')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['eid', 'date']);
            $table->index(['eid', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('minutes_of_meetings');
    }
};
