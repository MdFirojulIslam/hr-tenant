<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class loan_repayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'installment_dates',
        'installment_amounts',
        'paid_status',
        'paid_on_dates',
        'created_by',
    ];

    public function loan()
    {
        return $this->belongsTo(loan::class, 'loan_id', 'id');
    }
}
