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
        'EID',
        'Leave_Type_Id',
        'From_Date',
        'To_Date',
        'Purpose',
        'Status',
        'Attachment_Url',
        'created_by',
        'updated_by',
        'Notification',
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
