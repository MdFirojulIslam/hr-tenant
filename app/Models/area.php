<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class area extends Model
{
    use HasFactory;
    protected $fillable = [
        'Name',
    ];

    public function area()
    {
        return $this->hasOne(official::class, 'Area_Id');
    }
}
