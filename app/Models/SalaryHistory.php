<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'previous_net_pay',
        'increment_amount',
        'current_net_pay',
        'increment_dates',
        'created_by'
    ];
}
