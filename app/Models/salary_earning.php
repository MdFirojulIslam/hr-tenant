<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class salary_earning extends Model
{
    use HasFactory;
    protected $fillable = [
                'employee_id',
                'adjustment_type',
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
