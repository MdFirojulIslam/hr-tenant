<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class training_info extends Model
{
    use HasFactory;
    protected $fillable = [
        'EID',
        'Training_Title',
        'Organized_By',
        'Topic_Covered',
        'Duration',
        'Year',
        'Country_Id',
        'Remarks',
        'created_by',
        'updated_by',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class, 'EID');
    }

    public function country()
    {
        return $this->belongsTo(country::class, 'Country_Id');
    }
}
