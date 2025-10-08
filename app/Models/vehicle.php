<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'Vehicle_Id',
        'Name',
        'Model',
        'Reg_No',
        'Engine_No',
        'Chassis_No',
        'Tracking_No',
        'Driver_Id',
        'Supervisor_Id',
        'Tax_Expire_Date',
        'Fitness_Expire_Date',
        'image',
    ];

    public function driver()
    {
        return $this->belongsTo(employee::class, 'Driver_Id');
    }

    public function supervisor()
    {
        return $this->belongsTo(employee::class, 'Supervisor_Id');
    }

    public function vehicle_requsition()
    {
        return $this->hasOne(vehicle_requisition::class, 'Vehicle_Id');
    }
}
