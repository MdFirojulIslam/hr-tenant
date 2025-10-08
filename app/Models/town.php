<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class town extends Model
{
    use HasFactory;
    protected $fillable = [
        'Name',
    ];

    public function official()
    {
        return $this->hasOne(official::class, 'Town_Id');
    }

    public function town_belong()
    {
        return $this->hasOne(town_belong::class, 'Town_Id');
    }

}
