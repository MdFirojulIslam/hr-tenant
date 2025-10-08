<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class vehicle_requisition extends Model
{
    use HasFactory;
    protected $fillable = [
        'EID',
        'Vehicle_Id',
        'From_Date',
        'To_Date',
        'Pickup_Time',
        'Drop_Time',
        'Pickup_Location',
        'Drop_Location',
        'Purpose',
        'Description',
        'Status',
    ];

    public function vehicle()
    {
        return $this->belongsTo(vehicle::class, 'Vehicle_Id');
    }

    public function employee()
    {
        return $this->belongsTo(employee::class, 'EID');
    }
}
