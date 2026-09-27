<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrActionEvent extends Model
{
    use HasFactory;

    protected $table = 'hr_action_events';

    protected $fillable = [
        'employee_id',
        'event_name',
        'event_details',
        'event_date',
        'new_event_date',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'employee_id' => 'integer',
        'event_date' => 'date',
        'new_event_date' => 'date',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    /**
     * The employee the HR action / event was raised against.
     */
    public function employee()
    {
        return $this->belongsTo(employee::class, 'employee_id');
    }

    /**
     * Audit: the employee who created the record.
     */
    public function creator()
    {
        return $this->belongsTo(employee::class, 'created_by');
    }

    /**
     * Audit: the employee who last updated the record.
     */
    public function updater()
    {
        return $this->belongsTo(employee::class, 'updated_by');
    }

    /**
     * Department of the employee, resolved through the existing
     * employees -> officials -> departments chain.
     */
    public function department()
    {
        return $this->hasOneThrough(
            department::class,
            official::class,
            'EID',
            'id',
            'employee_id',
            'Department_Id'
        );
    }
}
