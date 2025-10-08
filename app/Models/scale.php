<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class scale extends Model
{
    use HasFactory;
    protected $fillable = [
        'Name',
    ];

    public function academic()
    {
        return $this->hasOne(academic_info::class, 'Sacle_Id');
    }
}
