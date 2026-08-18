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
        Schema::create('emp_info_documentations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('employee_id');
            $table->enum('job_circular', ['Not Applicable', 'Yes', 'No'])->nullable();
            $table->enum('interview_evalation', ['Not Applicable', 'Yes', 'No'])->nullable();
            $table->enum('appointment_letter', ['Yes', 'No'])->nullable();
            $table->enum('updated_resume', ['Yes', 'No'])->nullable();
            $table->enum('nid_passport', ['Yes', 'No'])->nullable();
            $table->enum('academic_cert', ['Yes', 'No'])->nullable();
            $table->enum('post_graduate_cert', ['Yes', 'No'])->nullable();
            $table->enum('prev_cmp_exp_letter', ['Yes', 'No'])->nullable();
            $table->enum('prev_cmp_release_letter', ['Yes', 'No'])->nullable();
            $table->enum('prev_cmp_salary_cert', ['Yes', 'No'])->nullable();
            $table->enum('recent_passport_pic', ['Yes', 'No'])->nullable();
            $table->enum('two_emegency_info', ['Yes', 'No'])->nullable();
            $table->enum('nid_verified', ['Yes', 'No'])->nullable();
            $table->enum('job_description', ['Yes', 'No'])->nullable();
            $table->enum('joining_letter', ['Yes', 'No'])->nullable();
            $table->enum('kye_form', ['Yes', 'No'])->nullable();
            $table->enum('emp_declation_letter', ['Yes', 'No'])->nullable();
            $table->enum('pa_pp_address', ['Yes', 'No'])->nullable();
            $table->string('completed_percent')->nullable();
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
        Schema::dropIfExists('emp_info_documentations');
    }
};
