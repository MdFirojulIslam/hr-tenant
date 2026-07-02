<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryIncrement extends Model
{
    use HasFactory;
    protected $fillable = [
        'employee_id',
        'previous_gross_salary',
        'increment_amount',
        'previous_net_pay',
        'current_net_pay',
        'increment_dates',
        'created_by',
    ];
}
