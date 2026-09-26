<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskBoardAssignee extends Model
{
    use HasFactory;

    protected $table = 'task_board_assignees';

    protected $fillable = [
        'task_board_id',
        'employee_id',
    ];

    public function taskBoard()
    {
        return $this->belongsTo(TaskBoard::class, 'task_board_id');
    }

    public function employee()
    {
        return $this->belongsTo(employee::class, 'employee_id');
    }
}