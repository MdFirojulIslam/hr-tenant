<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class bonus_disbursement extends Model
{
    use HasFactory;

    protected $table = 'bonus_disbursements';

    protected $fillable = [
        'bonus_id',
        'bonus_date',
        'status',
        'created_by'
    ];
}
