<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class employee_asset extends Model
{
    use HasFactory;
    protected $fillable = [
        'EID',
        'Device_Id',
        'Assigned_Date',
        'Return_Date',
        'Remarks',
        'created_by',
        'updated_by',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class, 'EID');
    }
    public function asset()
    {
        return $this->belongsTo(asset::class, 'Device_Id');
    }
}
