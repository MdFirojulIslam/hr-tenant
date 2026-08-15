<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class daily_order_target extends Model
{
    use HasFactory;

    protected $table = 'daily_order_target';

    protected $fillable = [
        'employee_id',
        'town_id',
        'amount',
        'eco',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'eco' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class, 'employee_id');
    }

    public function town()
    {
        return $this->belongsTo(town::class, 'town_id');
    }
}
