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

    public function department()
    {
        return $this->hasOne(official::class, 'Department_Id');
    }

    public function designation()
    {
        return $this->hasOne(designation::class, 'Designation_Id');
    }

    public function official()
    {
        return $this->hasOne(official::class, 'Department_Id');
    }
}
