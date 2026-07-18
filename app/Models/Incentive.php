<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Incentive extends Model
{
    use HasFactory;

    protected $table = 'incentives';

    protected $fillable = [
        'incentive_periods',
        'incentive_amount',
        'status',
        'created_by',
        'updated_by',
        'approved_by',
        'disbursed_by',
    ];

    public function details()
    {
        return $this->hasMany(IncentiveDetails::class, 'incentive_id');
    }
}
