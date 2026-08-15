<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'principal_amounts',
        'tenure_months',
        'monthly_installments',
        'installment_dates',
        'updated_by',
        'status',
        'remarks',
        'created_by',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class, 'employee_id', 'id');
    }

    public function repayments()
    {
        return $this->hasMany(loan_repayment::class, 'loan_id', 'id');
    }
}
