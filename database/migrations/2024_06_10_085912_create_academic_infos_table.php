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
        Schema::create('academic_infos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('EID');
            $table->unsignedBigInteger('Level_of_Education_Id');
            $table->unsignedBigInteger('Board_Id')->nullable();
            $table->string('Group',255)->nullable();
            $table->string('Institute_Name',255)->nullable();
            $table->unsignedBigInteger('Scale_Id')->nullable();
            $table->string('Result',100)->nullable();
            $table->string('Year_of_Passing')->nullable();
            $table->text('Acheivement')->nullable();
            $table->text('Remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('EID')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('Level_of_Education_Id')->references('id')->on('level_of_educations')->onDelete('cascade');
            $table->foreign('Scale_Id')->references('id')->on('scales')->onDelete('cascade');
            $table->foreign('Board_Id')->references('id')->on('boards')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_infos');
    }
};
