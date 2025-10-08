<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class level_of_education extends Model
{
    use HasFactory;
    protected $fillable = [
        'Name',
        'education_id'
    ];

    public function degree()
    {
        return $this->belongsTo(level_of_education::class, 'education_id');
    }
    public function academic()
    {
        return $this->hasOne(academic_info::class, 'Level_of_Education_Id');
    }
}
