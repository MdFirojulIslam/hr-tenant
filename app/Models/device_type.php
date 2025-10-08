<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class device_type extends Model
{
    use HasFactory;
    protected $fillable = [
        'Name',
    ];
    public function assets()
    {
        return $this->hasMany(Asset::class,  'device_type_id');
    }
}
