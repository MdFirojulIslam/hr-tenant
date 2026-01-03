<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class itineraries extends Model
{
    use HasFactory;

    protected $fillable = [
        'itinerary_logs_id',
        'visit_date',
        'town_id',
        'visit_with',
        'location',
        'visit_objective',
        'meeting_with',
        'visit_start_time',
        'visit_end_time',
        'created_by'
    ];

    // Each itinerary belongs to one itinerary log
    public function log()
    {
        return $this->belongsTo(itinerary_logs::class, 'itinerary_logs_id');
    }
    public function itinerariesUpdates()
    {
        return $this->belongsTo(itineraries_updates::class, 'itineraries_id');
    }
}
