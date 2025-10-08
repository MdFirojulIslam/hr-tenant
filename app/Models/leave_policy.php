<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class leave_policy extends Model
{
    use HasFactory;
    protected $fillable = [
        'Leave_Type_Id',
        'Condition_Description',
    ];

    public function leave_type()
    {
        return $this->belongsTo(leave_type::class);
    }
}
