<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileNotification extends Model
{
    public const TYPE_CHECK_IN = 'attendance_check_in_reminder';
    public const TYPE_CHECK_OUT = 'attendance_check_out_reminder';
    public const TYPE_EVENT = 'event_created';
    public const TYPE_LEAVE = 'leave_approved';
    public const TYPE_EXPENSE = 'expense_submission_reminder';
    public const TYPE_OUTLET = 'scheduled_outlet_visit_pending';

    protected $table = 'mobile_notifications';

    protected $fillable = [
        'eid',
        'type',
        'title',
        'body',
        'business_key',
        'reference_type',
        'reference_id',
        'route',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];
}
