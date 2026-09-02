<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TasksAssignee extends Model
{
    use HasFactory;

    protected $table = 'tasks_assignees';

    protected $fillable = [
        'task_id',
        'assigned_to',
        'assigned_by',
        'updated_by',
        'read_status',
    ];

    protected $casts = [
        'read_status' => 'boolean',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

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
}
