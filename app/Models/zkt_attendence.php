<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class zkt_attendence extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'time',
        'Machine_Id',
    ];

    public function attendence()
    {
        return $this->belongsTo(zkt_user::class, 'user_id');
    }
}
