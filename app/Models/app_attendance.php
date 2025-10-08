<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class app_attendance extends Model
{
    use HasFactory;
    protected $fillable = [
        'EID',
        'town_id',
        'latitude',
        'longitude',
        'status',
        'location',
        'postcode',
        'village',
        'city',
        'district',
        'image_path',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class, 'EID');
    }
    public function town()
    {
        return $this->belongsTo(town::class, 'town_id');
    }
}
