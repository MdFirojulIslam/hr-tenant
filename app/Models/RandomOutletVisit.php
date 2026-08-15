<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RandomOutletVisit extends Model
{
    protected $table = 'random_outlet_visits';

    protected $fillable = [
        'eid',
        'town',
        'outlet_name',
        'image_path',
        'latitude',
        'longitude',
        'location_name',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];
}
