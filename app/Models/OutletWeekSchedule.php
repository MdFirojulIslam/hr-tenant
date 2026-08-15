<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OutletWeekSchedule extends Model
{
    protected $table = 'outlet_week_schedules';

    protected $fillable = [
        'week_no',
        'day_name',
        'is_complete',
    ];

    protected $casts = [
        'week_no' => 'integer',
        'is_complete' => 'boolean',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(OutletVisitAssignment::class, 'outlet_week_schedules_id');
    }
}
