<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class itineraries_update extends Model
{
    use HasFactory;
    protected $table = 'itineraries_update';
    protected $fillable = [
        'itineraries_id',
        'employee_id',
        'visit_date',
        'status',
        'town_id',
        'visit_with',
        'location',
        'visit_objective',
        'meeting_with',
        'visit_start_time',
        'visit_end_time',
        'remarks',
        'created_by'
    ];

    // Each itinerary belongs to one itinerary log
    public function itinerariesToUpdate()
    {
        return $this->belongsTo(itineraries::class, 'itineraries_id');
    }
}
