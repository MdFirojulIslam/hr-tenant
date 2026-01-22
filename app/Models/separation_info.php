<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class separation_info extends Model
{
    use HasFactory;
    protected $fillable = [
        'employee_id',
        'separation_request_id',
        'employee_rehire',
        'employee_replacement',
        'separation_approval_id',
        'department_approval_id',
        'hr_approval_id',
        'it_approval_id',
        'accounts_approval_id',
        'deduct_ammount_by_hr',
        'deduction_cause',
        'benefits',
        'final_payment_by_acc',
        'payment_date',
        'payment_method',
        'remove_user_email',
        'mail_backup',
        'remove_dms_account',
        'remove_hris_account',
        'assets_laptop',
        'others_accessories',
        'status',
    ];

    public function separationRequest()
    {
        return $this->belongsTo(separation_request::class, 'employee_id', 'employee_id');
    }
}
