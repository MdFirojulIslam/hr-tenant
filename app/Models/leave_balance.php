<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class leave_balance extends Model
{
    use HasFactory;

    protected $fillable = [
        'EID',
        'Leave_Type_Id',
        'Total_Leave',
        'Used_Leave',
        'Balance_Leave',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class);
    }
    public function leave_type()
    {
        return $this->belongsTo(leave_type::class);
    }
}
