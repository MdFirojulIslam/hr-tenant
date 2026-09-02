<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TasksResult extends Model
{
    use HasFactory;

    protected $table = 'tasks_results';

    protected $fillable = [
        'task_id',
        'current_status',
        'executed_date',
        'executed_by',
        'updated_by',
    ];

    protected $casts = [
        'executed_date' => 'date:Y-m-d',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function executor()
    {
        return $this->belongsTo(employee::class, 'executed_by');
    }

    public function updater()
    {
        return $this->belongsTo(employee::class, 'updated_by');
    }
}
