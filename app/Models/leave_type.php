<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class leave_type extends Model
{
    use HasFactory;

    protected $fillable = [
        'Name',
        'Max_Days',
        'Max_Consecutive_Days'
    ];
    public function leave()
    {
        return $this->hasMany(leave::class);
    }
    public function leaveBalance()
    {
        return $this->hasMany(leave_balance::class);
    }
    public function leavePolicy()
    {
        return $this->hasMany(leave_policy::class);
    }
}
