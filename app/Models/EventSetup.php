<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventSetup extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_HOLIDAY = 'Holiday';
    public const TYPE_MEETING = 'Meeting';
    public const TYPE_MEETUP = 'Meet-up';
    public const TYPE_OTHER = 'Other';

    public const TYPES = [
        self::TYPE_HOLIDAY,
        self::TYPE_MEETING,
        self::TYPE_MEETUP,
        self::TYPE_OTHER,
    ];

    /**
     * JSON sentinel stored in for_departments when "Select All Departments" is used.
     */
    public const ALL_DEPARTMENTS = ['all'];

    protected $table = 'event_setup';

    protected $fillable = [
        'event_header',
        'event_description',
        'event_type',
        'picture',
        'event_date_from',
        'event_date_to',
        'for_departments',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'event_date_from' => 'datetime',
        'event_date_to' => 'datetime',
        'for_departments' => 'array',
        'deleted_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(employee::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(employee::class, 'updated_by');
    }

    public function isForAllDepartments(): bool
    {
        $ids = $this->for_departments;

        if (!is_array($ids) || $ids === []) {
            return false;
        }

        foreach ($ids as $id) {
            if (is_string($id) && strtolower($id) === 'all') {
                return true;
            }
        }

        return false;
    }

    public function matchesDepartment(?int $departmentId): bool
    {
        if ($this->isForAllDepartments()) {
            return true;
        }

        if ($departmentId === null) {
            return false;
        }

        $ids = $this->for_departments ?? [];

        return in_array($departmentId, array_map('intval', $ids), true);
    }

    public function isActiveAt($when): bool
    {
        if (!$this->event_date_from || !$this->event_date_to) {
            return false;
        }

        return $this->event_date_from->lte($when) && $this->event_date_to->gte($when);
    }
}
