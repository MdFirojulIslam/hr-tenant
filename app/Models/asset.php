<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class asset extends Model
{
    use HasFactory;
    protected $fillable = [
        'Device_Name',
        'Device_Code',
        'Device_Type_Id',
        'Serial_Number',
        'Description',
        'Purchase_Date',
        'Warranty_Expiration',
        'Price',
        'Status',
    ];

    public function deviceType()
    {
        return $this->belongsTo(device_type::class, 'Device_Type_Id');
    }
}
