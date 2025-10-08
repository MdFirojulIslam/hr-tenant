<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class emplooyee_expense extends Model
{
    use HasFactory, SoftDeletes;


    protected $fillable = [
        'EID',
        'Date',
        'Tr_Type',
        'Town_Id',
        'Description',
        'From',
        'To',
        'Vehicle',
        'File_Path',
        'Amount',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class, 'EID');
    }

    public function town()
    {
        return $this->belongsTo(town::class, 'Town_Id');
    }
}
