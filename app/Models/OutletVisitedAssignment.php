<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Outlet Visited assignment.
 *
 * Outlet identity is composite: distributor_id + outlets_id
 * (distributor_id = towns.DMS_Town_Id = artisan_admin.distributors.id).
 */
class OutletVisitedAssignment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'outlet_visited_assignments';

    protected $fillable = [
        'employee_id',
        'town_id',
        'distributor_id',
        'outlets_id',
        'outlet_code',
        'outlet_name',
        'cycle_year',
        'cycle_month',
        'activity_type',
        'week_no',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'employee_id' => 'integer',
        'town_id' => 'integer',
        'distributor_id' => 'integer',
        'outlets_id' => 'integer',
        'cycle_year' => 'integer',
        'cycle_month' => 'integer',
        'week_no' => 'integer',
        'is_active' => 'boolean',
    ];

    public function visit(): HasOne
    {
        return $this->hasOne(OutletVisitedVisit::class, 'assignment_id');
    }
}
