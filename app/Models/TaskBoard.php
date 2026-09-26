<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskBoard extends Model
{
    use HasFactory;

    public const STATUS_ASSIGNED = 'Assigned';
    public const STATUS_COMPLETE = 'Complete';
    public const STATUS_INCOMPLETE = 'Incomplete';

    public const STATUSES = [
        self::STATUS_ASSIGNED,
        self::STATUS_COMPLETE,
        self::STATUS_INCOMPLETE,
    ];

    protected $table = 'task_boards';

    protected $fillable = [
        'assigned_to',
        'assigned_by',
        'task_name',
        'description',
        'status',
        'task_assigned_at',
        'task_finished_at',
        'remarks',
        'updated_by',
    ];

    protected $casts = [
        'task_assigned_at' => 'datetime',
        'task_finished_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class, 'assigned_to');
    }

    public function assigner()
    {
        return $this->belongsTo(employee::class, 'assigned_by');
    }

    public function updater()
    {
        return $this->belongsTo(employee::class, 'updated_by');
    }

    public function assignees()
    {
        return $this->hasMany(TaskBoardAssignee::class, 'task_board_id');
    }

    public function isAssigned(): bool
    {
        return $this->status === self::STATUS_ASSIGNED;
    }

    /**
     * Employee may only move Assigned → Complete or Incomplete.
     */
    public static function canEmployeeTransition(?string $from, ?string $to): bool
    {
        if ($from !== self::STATUS_ASSIGNED) {
            return false;
        }

        return in_array($to, [self::STATUS_COMPLETE, self::STATUS_INCOMPLETE], true);
    }
}
