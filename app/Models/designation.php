<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class designation extends Model
{
    use HasFactory;
    protected $fillable = [
        'Department_Id ',
        'Name',
    ];

    public function designation()
    {
        return $this->hasOne(official::class, 'Designation_Id');
    }

    public function department()
    {
        return $this->belongsTo(official::class, 'Department_Id');
    }
}
