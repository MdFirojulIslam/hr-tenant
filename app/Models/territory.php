<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class territory extends Model
{
    use HasFactory;

    protected $fillable = [
        'Name',
    ];

    public function official()
    {
        return $this->hasOne(official::class, 'Territory_Id');
    }
}
