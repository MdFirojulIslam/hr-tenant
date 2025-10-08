<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class salary_adjustment extends Model
{
    use HasFactory;
    protected $fillable = [
        'EID',
        'Month',
        'Type',
        'Amount',
        'Remarks',
        'created_by',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class, 'EID');
    }
}
