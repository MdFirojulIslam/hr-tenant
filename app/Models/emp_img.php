<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class emp_img extends Model
{
    use HasFactory;
    protected $fillable = [
        'EID',
        'img_url',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class, 'EID');
    }
}
