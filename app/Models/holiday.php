<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'holiday_name',
        'from_date',
        'to_date',
        'duration',
        'created_by',
        'updated_by',
    ];
}
