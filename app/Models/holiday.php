<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'Name',
        'From_Date',
        'To_Date',
        'Duration',
        'created_by',
        'updated_by',
    ];
}
