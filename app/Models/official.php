<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class official extends Model
{
    use HasFactory;
    protected $fillable = [
        'EID',
        'Department_Id',
        'Designation_Id',
        'Employee_Grade',
        'Employee_type_Id',
        'Area_Id',
        'Territory_Id',
        'Supervisor_Id',
        'DOJ',
        'DOL',
        'Provation_period',
        'DOC',
        'Job_Location_Id',
        'Shift',
        'Status',
        'created_by',
        'updated_by',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class, 'EID');
    }
    public function area()
    {
        return $this->belongsTo(area::class, 'Area_Id');
    }

    public function department()
    {
        return $this->belongsTo(department::class, 'Department_Id');
    }

    public function designation()
    {
        return $this->belongsTo(designation::class, 'Designation_Id');
    }

    public function employeeType()
    {
        return $this->belongsTo(employee_type::class, 'Employee_type_Id');
    }

    public function territory()
    {
        return $this->belongsTo(territory::class, 'Territory_Id');
    }

    public function supervisor()
    {
        return $this->belongsTo(employee::class, 'Supervisor_Id');
    }

    public function branch()
    {
        return $this->belongsTo(branch::class, 'Job_Location_Id');
    }
}
