<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class salaries extends Model
{
    use HasFactory;
    protected $fillable = [
        'employee_id',
        'gross_salary',
        'basic',
        'house_rent',
        'conveyance',
        'medical_allowance',
        'consolidated',
        'festival_bonus',
        'travel_allowance',
        'daily_allowance',
        'mobile_bill',
        'other_allowance',
        'total_payment',
        'provident_fund',
        'income_tax',
        'revenue_stamp',
        'net_pay',
        'salary_type',
        'bank_account_no',
        'created_by',
        'updated_by',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class, 'employee_id');
    }
}
