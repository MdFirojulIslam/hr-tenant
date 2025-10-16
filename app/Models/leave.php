<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class leave extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'from_date',
        'to_date',
        'purpose',
        'status',
        'attachment_url',
        'created_by',
        'updated_by',
        'notification',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class, 'employee_id');
    }
    public function leave_type()
    {
        return $this->belongsTo(leave_type::class, 'leave_type_id');
    }
}
