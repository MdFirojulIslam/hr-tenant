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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('Company_Id');
            $table->unsignedBigInteger('Employee_Id')->unique();
            $table->string('Card_No', 50)->nullable()->unique();
            $table->string('Full_Name', 100);
            $table->string('Father_Name', 100)->nullable();
            $table->string('Mother_Name', 100)->nullable();
            $table->string('Spouse_Name', 100)->nullable();
            $table->string('Marital_Status', 20)->nullable();
            $table->date('DOB', 20);
            $table->string('Place_of_Birth', 100)->nullable();
            $table->text('Present_Address')->nullable();
            $table->text('Permanent_Address')->nullable();
            $table->string('Contact_No', 30)->nullable();
            $table->string('Emergency_Contact', 30)->nullable();
            $table->string('Gender', 30)->nullable();
            $table->string('Personal_Email', 255)->nullable();
            $table->string('Official_Email', 255)->nullable();
            $table->unsignedBigInteger('Blood_Group_Id')->nullable();
            $table->unsignedBigInteger('Religion_Id')->nullable();
            $table->string('Nationality', 50)->nullable();
            $table->string('NID', 30)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('Company_Id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('Blood_Group_Id')->references('id')->on('blood_groups')->onDelete('cascade');
            $table->foreign('Religion_Id')->references('id')->on('religions')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
