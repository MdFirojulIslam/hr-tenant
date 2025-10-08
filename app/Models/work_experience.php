<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class work_experience extends Model
{
    use HasFactory;
    protected $fillable = [
        'EID',
        'Company_Name',
        'Company_Business',
        'Company_Address',
        'Designation',
        'Department',
        'From_Date',
        'To_Date',
        'Duration',
        'Responsibilities',
        'Last_Salary',
        'Continuing',
        'created_by',
        'updated_by',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class, 'EID');
    }
}
