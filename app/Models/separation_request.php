<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class separation_request extends Model
{
    use HasFactory;
    protected $fillable = [
        'employee_id',
        'separation_date',
        'separation_type',
        'reason_for_separation',
    ];
    public function separationRequest()
    {
        return $this->hasOne(separation_info::class, 'employee_id', 'employee_id');
    }
}
