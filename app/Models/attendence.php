<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class attendence extends Model
{
    use HasFactory;
    protected $fillable = [
        'EID',
        'Date',
        'Time_In',
        'Time_Out',
        'Status',
        'created_by',
        'updated_by',

    ];

    public function employee()
    {
        return $this->belongsTo(employee::class, 'EID');
    }
}
