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
        Schema::create('market_intelligence', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('brand_id');

            $table->string('product_name');
            $table->string('size')->nullable();

            $table->string('outlet_name');
            $table->string('outlet_location', 500)->nullable();

            $table->string('location');
            $table->string('location_coordinate')->nullable();

            $table->string('division');
            $table->string('district');

            $table->string('country_of_origin', 120)->nullable();

            $table->decimal('tp', 14, 2)->default(0.00);
            $table->decimal('mrp', 14, 2)->default(0.00);

            $table->string('product_image')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['employee_id', 'created_at']);
            $table->index('brand_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_intelligence');
    }
};
