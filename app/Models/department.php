<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class department extends Model
{
    use HasFactory;
    protected $fillable = [
        'Name',
    ];

    public function designation()
    {
        return $this->hasOne(designation::class, 'Designation_Id');
    }

    public function official()
    {
        return $this->hasOne(official::class, 'Department_Id');
    }
}
