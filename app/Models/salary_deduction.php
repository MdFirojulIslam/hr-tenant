<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class salary_deduction extends Model
{
    use HasFactory;
    protected $fillable = [
        'employee_id',
        'adjustment_types_id',
        'date',
        'amount',
        'remarks',
        'created_by'
    ];
    public function employee()
    {
        return $this->belongsTo(employee::class, 'employee_id');
    }
}
