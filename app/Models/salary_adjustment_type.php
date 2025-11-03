<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class salary_adjustment_type extends Model
{
    use HasFactory;
    protected $fillable = [
        'adjustment_name',
        'adjustment_type', // 'addition' or 'deduction'
        'status'
    ];
    public function earnings()
    {
        return $this->hasMany(salary_earning::class, 'adjustment_types_id');
    }
    public function deductions()
    {
        return $this->hasMany(salary_deduction::class, 'adjustment_types_id');
    }
}
