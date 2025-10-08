<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class loan extends Model
{
    use HasFactory;
    protected $fillable = [
        'employee_id',
        'principal',
        'tenure_months',
        'monthly_installment',
        'first_due_on',
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
        return $this->hasMany(loan_repayment::class, 'Loan_Id', 'id');
    }
}
