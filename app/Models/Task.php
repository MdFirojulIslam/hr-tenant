<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $table = 'tasks';

    protected $fillable = [
        'task_name',
        'start_date',
        'due_date',
        'new_due_date',
        'status',
        'repeat',
        'bucket',
        'priority',
        'privacy',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'due_date' => 'date:Y-m-d',
        'new_due_date' => 'date:Y-m-d',
    ];

    public const STATUS_NOT_STARTED = 'Not started';
    public const STATUS_IN_PROGRESS = 'In progress';
    public const STATUS_COMPLETED = 'Completed';

    public const STATUSES = [
        self::STATUS_NOT_STARTED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
    ];

    public const REPEATS = ['Do not repeat', 'Daily', 'Custom'];

    public const BUCKETS = ['No bucket', 'Backlog', 'Up next', 'In progress', 'Blocked', 'Completed'];

    public const PRIORITIES = ['Urgent', 'Important', 'Medium', 'Low'];

    public const PRIVACIES = ['Public', 'Private', 'Shared', 'Others'];

    public function assignees()
    {
        return $this->hasMany(TasksAssignee::class, 'task_id');
    }

    public function checklists()
    {
        return $this->hasMany(TasksChecklists::class, 'task_id');
    }

    public function results()
    {
        return $this->hasMany(TasksResult::class, 'task_id');
    }

    public function creator()
    {
        return $this->belongsTo(employee::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(employee::class, 'updated_by');
    }
}
