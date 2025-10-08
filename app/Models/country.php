<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class country extends Model
{
    use HasFactory;
    protected $fillable = [
        'Name',
        'Phone_Code',
    ];

    public function country()
    {
        return $this->hasOne(country::class, 'Job_Location_Id');
    }
}
