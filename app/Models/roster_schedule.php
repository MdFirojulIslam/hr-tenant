<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class roster_schedule extends Model
{
    use HasFactory;
    protected $fillable = [
        'employee_id',
        'effective_from',
        'time_in',
        'time_out',
        'work_from',
        'created_by'
    ];
}
