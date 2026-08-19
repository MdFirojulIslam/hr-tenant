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
        Schema::create('outlet_visited_visits', function (Blueprint $table) {
            $table->id();


            $table->unsignedBigInteger('assignment_id');
            $table->date('scheduled_date');

            $table->unsignedInteger('employee_id');
            $table->unsignedInteger('distributor_id');

            $table->string('outlet_code')->nullable();
            $table->string('outlet_name')->nullable();

            $table->string('image_path');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            $table->string('location_name', 500)->nullable();
            $table->text('remarks')->nullable();

            $table->timestamp('visited_at')->useCurrent();

            $table->timestamp('check_in_at')->nullable();
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->string('check_in_location_name', 500)->nullable();

            $table->timestamp('check_out_at')->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->string('check_out_location_name', 500)->nullable();
            $table->decimal('check_out_distance_meters', 8, 1)->nullable();

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
        Schema::dropIfExists('outlet_visited_visits');
    }
};
