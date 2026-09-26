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
    public const TYPE_MEETUP = 'Meet-Up';
    public const TYPE_OTHER = 'Others';

    public const TYPES = [
        self::TYPE_HOLIDAY,
        self::TYPE_MEETING,
        self::TYPE_MEETUP,
        self::TYPE_OTHER,
    ];

    /**
     * Sentinel stored in for_departments when an event targets all departments.
     */
    public const ALL_DEPARTMENTS = ['all'];

    protected $table = 'event_setups';

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
    ];

    protected $casts = [
        'event_date_from' => 'datetime',
        'event_date_to' => 'datetime',
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

    /**
     * Store multiple department ids as a comma-separated string, e.g. "1,2,3",
     * while the controller always works with an array of department ids.
     */
    public function setForDepartmentsAttribute($value): void
    {
        if ($value === null || $value === '' || $value === []) {
            $this->attributes['for_departments'] = null;

            return;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                if (is_string($item) && strtolower($item) === 'all') {
                    $this->attributes['for_departments'] = 'all';

                    return;
                }
            }

            $ids = array_values(array_unique(array_filter(array_map('intval', $value), fn($id) => $id > 0)));
            $this->attributes['for_departments'] = $ids === [] ? null : implode(',', $ids);

            return;
        }

        $this->attributes['for_departments'] = (string) $value;
    }

    /**
     * Read the comma-separated "1,2,3" string back as an array of dept ids.
     */
    public function getForDepartmentsAttribute($value): array
    {
        if ($value === null || trim((string) $value) === '') {
            return [];
        }

        $value = (string) $value;

        if (strtolower($value) === 'all') {
            return self::ALL_DEPARTMENTS;
        }

        return array_values(array_unique(array_filter(array_map('intval', explode(',', $value)), fn($id) => $id > 0)));
    }
}