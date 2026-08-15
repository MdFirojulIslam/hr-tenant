<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutletVisitedVisit extends Model
{
    protected $table = 'outlet_visited_visits';

    protected $fillable = [
        'assignment_id',
        'scheduled_date',
        'employee_id',
        'distributor_id',
        'outlet_code',
        'outlet_name',
        'image_path',
        'latitude',
        'longitude',
        'location_name',
        'remarks',
        'visited_at',
        'check_in_at',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_location_name',
        'check_out_at',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_location_name',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'visited_at' => 'datetime',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'check_in_latitude' => 'float',
        'check_in_longitude' => 'float',
        'check_out_latitude' => 'float',
        'check_out_longitude' => 'float',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(OutletVisitAssignment::class, 'assignment_id');
    }
}
