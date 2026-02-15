<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class salary_disbursement extends Model
{
    use HasFactory;

    protected $table = 'salary_disbursements';

    // Mass assignable fields
    protected $fillable = [
        'salary_id',
        'salary_month',
        'status',
        'created_by'
    ];
}
