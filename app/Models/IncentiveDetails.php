<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncentiveDetails extends Model
{
    use HasFactory;

    protected $table = 'incentive_details';

    protected $fillable = [
        'incentive_id',
        'employee_id',
        'department_id',
        'amounts',
        'created_by',
        'updated_by',
        'remarks',
    ];

    public function incentive()
    {
        return $this->belongsTo(Incentive::class, 'incentive_id');
    }
}
