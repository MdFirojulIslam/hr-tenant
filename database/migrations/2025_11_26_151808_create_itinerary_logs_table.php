<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('itinerary_logs', function (Blueprint $table) {
            $table->id();
            $table->date('created_date');
            $table->enum('status', ['save', 'pending', 'approved', 'rejected'])
                ->default('pending');
            $table->text('supervisor_remarks')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('common_updated_date')->nullable();
            $table->timestamp('creator_update_date')->nullable();
            $table->timestamp('sup_update_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itinerary_logs');
    }
};
