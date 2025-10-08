<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class academic_info extends Model
{
    use HasFactory;
    protected $fillable = [
        'EID',
        'Level_of_Education_Id',
        'Board_Id',
        'Group',
        'Institute_Name',
        'Scale_Id',
        'Result',
        'Year_of_Passing',
        'Acheivement',
        'Remarks',
        'created_by',
        'updated_by',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class, 'EID');
    }

    public function education()
    {
        return $this->belongsTo(level_of_education::class, 'Level_of_Education_Id');
    }

    public function scale()
    {
        return $this->belongsTo(scale::class, 'Scale_Id');
    }

    public function board()
    {
        return $this->belongsTo(board::class, 'Board_Id');
    }
}
