<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class minutes_of_meeting extends Model
{
    use HasFactory;

    protected $table = 'minutes_of_meetings';

    protected $fillable = [
        'eid',
        'str_time',
        'end_time',
        'town',
        'date',
        'meeting_with',
        'mom',
        'file',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class, 'eid');
    }

    public function townRelation()
    {
        return $this->belongsTo(town::class, 'town');
    }
}
