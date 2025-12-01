<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class itinerary_logs extends Model
{
    use HasFactory;


    protected $fillable = [
        'created_date',
        'status',
        'supervisor_remarks',
        'created_by',
        'updated_by',
        'common_updated_date',
        'creator_update_date',
        'sup_update_date'
    ];

    // One itinerary log has many itineraries
    public function itineraries()
    {
        return $this->hasMany(itineraries::class, 'itinerary_logs_id');
    }
}
