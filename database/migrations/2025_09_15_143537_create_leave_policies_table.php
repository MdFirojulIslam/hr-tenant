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
        Schema::create('leave_policies', function (Blueprint $table) { 
            $table->id();
            $table->unsignedInteger('leave_type_id');
            $table->text('condition_description');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('cascade');
        }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_policies');
    }
};
