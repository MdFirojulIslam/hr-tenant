<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TasksChecklists extends Model
{
    use HasFactory;

    protected $table = 'tasks_checklists';

    protected $fillable = [
        'task_id',
        'checklist_name',
        'completed_status',
        'completed_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'completed_status' => 'boolean',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function creator()
    {
        return $this->belongsTo(employee::class, 'created_by');
    }

    public function completer()
    {
        return $this->belongsTo(employee::class, 'completed_by');
    }

    public function updater()
    {
        return $this->belongsTo(employee::class, 'updated_by');
    }
}
