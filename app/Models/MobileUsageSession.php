<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileUsageSession extends Model
{
    protected $table = 'mobile_usage_sessions';

    protected $fillable = [
        'employee_id',
        'user_id',
        'usage_date',
        'session_started_at',
        'session_ended_at',
        'duration_seconds',
        'client_session_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'usage_date' => 'date',
        'session_started_at' => 'datetime',
        'session_ended_at' => 'datetime',
    ];
}
