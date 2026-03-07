<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class bonus_sheet extends Model
{
    use HasFactory;
    protected $fillable = [
        'employee_id',
        'bonus_id',
        'bonus_date',
        'gross_salary',
        'basic_salary',
        'bonus_percentages',
        'bonus_amounts',
        'payment_methods',
        'bank_account_no',
        'created_by',
        'bonus_disbursements'
    ];
}
