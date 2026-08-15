<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Outlet Visit assignment (live schema).
 *
 * Week comes from outlet_week_schedules; outlet identity is outlet_code + distributor_id.
 */
class OutletVisitAssignment extends Model
{
    protected $table = 'outlet_visit_assignments';

    protected $fillable = [
        'outlet_week_schedules_id',
        'employee_id',
        'distributor_id',
        'outlet_code',
        'outlet_name',
        'visit_date',
        'is_active',
        'inactivated_on',
        'inactivated_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'outlet_week_schedules_id' => 'integer',
        'employee_id' => 'integer',
        'distributor_id' => 'integer',
        'visit_date' => 'date',
        'is_active' => 'boolean',
        'inactivated_on' => 'datetime',
    ];

    public function weekSchedule(): BelongsTo
    {
        return $this->belongsTo(OutletWeekSchedule::class, 'outlet_week_schedules_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(OutletVisitedVisit::class, 'assignment_id');
    }

    public function visit(): HasOne
    {
        return $this->hasOne(OutletVisitedVisit::class, 'assignment_id')->latestOfMany();
    }
}
