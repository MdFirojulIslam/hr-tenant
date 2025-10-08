<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class zkt_machine extends Model
{
    use HasFactory;

    protected $fillable = [
        'IP',
        'port',
        'Model_Name',
        'Serial_Number',
        'Status',
    ];
}
