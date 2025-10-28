<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class employee_salary_sheet extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'salary_date',
        'gross_salary',
        'basic',
        'house_rent',
        'conveyance',
        'medical_allowance',
        'consolidated',
        'festival_bonus',
        'travel_allowance',
        'overtime',
        'daily_allowance',
        'mobile_bill',
        'other_allowance',
        'total_payment',
        'provident_fund',
        'income_tax',
        'revenue_stamp',
        'leave_without_pay',
        'penalty_other_deduction',
        'advance',
        'loan_payable',
        'total_deduction',
        'net_pay',
        'bank_account_no',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class, 'employee_id');
    }
}
