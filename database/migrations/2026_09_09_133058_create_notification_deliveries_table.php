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
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('notification_recipient_id');
            $table->enum('channel', ['mail', 'sms']);
            $table->enum('status', ['success', 'failed', 'pending'])->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('failed_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};
