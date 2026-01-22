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
        Schema::create('component_permissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('menu_id')->nullable();
            $table->unsignedInteger('sub_menu_id')->nullable();
            $table->string('component_name', 150);
            $table->unsignedInteger('component_id')->nullable();
            $table->string('permission_key', 150)->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('component_permissions');
    }
};
