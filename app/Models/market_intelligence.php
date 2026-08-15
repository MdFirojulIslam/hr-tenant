<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class market_intelligence extends Model
{
    use HasFactory;

    protected $table = 'market_intelligence';

    protected $fillable = [
        'employee_id',
        'brand_id',
        'product_name',
        'size',
        'outlet_name',
        'outlet_location',
        'location',
        'location_coordinate',
        'division',
        'district',
        'country_of_origin',
        'tp',
        'mrp',
        'product_image',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'tp' => 'decimal:2',
        'mrp' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class, 'employee_id');
    }
}
