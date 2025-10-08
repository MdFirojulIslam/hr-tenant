<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class employee_type extends Model
{
    use HasFactory;
    protected $fillable = [
        'Name',
    ];

    public function employeeType()
    {
        return $this->hasOne(official::class, 'Employee_type_Id');
    }
}
