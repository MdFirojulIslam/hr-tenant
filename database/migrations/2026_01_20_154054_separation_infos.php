<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('separation_infos', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('employee_id');
            $table->unsignedInteger('separation_request_id');

            $table->boolean('employee_rehire')->default(false)
                ->comment('1 = Yes, 0 = No');
            $table->boolean('employee_replacement')->default(false)
                ->comment('1 = Yes, 0 = No');

            $table->unsignedInteger('separation_approval_id')->nullable();
            $table->unsignedInteger('department_approval_id')->nullable();
            $table->unsignedInteger('hr_approval_id')->nullable();
            $table->unsignedInteger('it_approval_id')->nullable();
            $table->unsignedInteger('accounts_approval_id')->nullable();

            $table->decimal('deduct_ammount_by_hr', 12, 2)->default(0)->nullable();
            $table->text('deduction_cause')->nullable();
            $table->decimal('benefits', 12, 2)->default(0)->nullable();
            $table->decimal('final_payment_by_acc', 12, 2)->default(0)->nullable();
            $table->date('payment_date')->nullable();
            $table->enum('payment_method', ['CASH', 'BANK DEPOSIT', 'BANK TRANSFER', 'BANK CHECK'])->default('CASH')->nullable();

            $table->boolean('remove_user_email')->default(false)->nullable();
            $table->boolean('mail_backup')->default(false)->nullable();
            $table->boolean('remove_dms_account')->default(false)->nullable();
            $table->boolean('remove_hris_account')->default(false)->nullable();

            $table->boolean('assets_laptop')->default(false)->nullable();
            $table->boolean('others_accessories')->default(false)->nullable();

            $table->enum('status', ['PENDING',  'APPROVED', 'APPROVED(HR)', 'APPROVED(DEPT)', 'APPROVED(ACC)', 'APPROVED(IT)', 'COMPLETED', 'REJECTED'])
                ->default('PENDING')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('separation_infos');
    }
};
