<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class branch extends Model
{
    use HasFactory;
    protected $fillable = [
        'Name',
    ];

    public function branch()
    {
        return $this->hasOne(official::class, 'Job_Location_Id');
    }
}
