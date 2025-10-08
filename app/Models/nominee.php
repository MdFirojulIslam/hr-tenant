<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class nominee extends Model
{
    use HasFactory;
    protected $fillable = [
        'EID',
        'Nominee_Name',
        'DOB',
        'Contact_No',
        'Email',
        'NID',
        'Share',
        'Personal_Address',
        'created_by',
        'updated_by',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class, 'EID');
    }
}
