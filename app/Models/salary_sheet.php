<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class salary_sheet extends Model
{
    use HasFactory;

    // Table name (optional if follows Laravel convention)
    protected $table = 'salary_sheets';

    // Mass assignable fields
    protected $fillable = [
        'monthly_salary_id',
        'employee_id',
        'salary_date',
        'gross_salary',
        'basic_salary',
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
        'earning_adjustment',
        'total_payment',
        'provident_fund',
        'income_tax',
        'revenue_stamp',
        'leave_without_pay',
        'deduction_adjustment',
        'total_deduction',
        'monthly_loan_repayments',
        'present_days',
        'absent_days',
        'late_days',
        'net_pay',
        'salary_status',
        'payment_method',
        'bank_account_no',
        'created_by',
    ];

    // Optional: Relationships
    public function employee()
    {
        return $this->belongsTo(employee::class, 'employee_id', 'id');
    }

    // You can add any custom methods or scopes here
}
