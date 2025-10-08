<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class loan_repayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'due_on',
        'amount',
        'is_paid',
        'paid_on',
    ];

    public function loan()
    {
        return $this->belongsTo(loan::class, 'loan_id');
    }
}
