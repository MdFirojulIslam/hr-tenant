<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceSummary extends Model
{
    use HasFactory;
    protected $fillable = [
        'employee_id',
        'department_id',
        'date',
        'status',
        'rostered_in_time',
        'rostered_out_time',
        'rostered_status',
        'in_time',
        'out_time'
    ];
}
