<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class town_belong extends Model
{
    use HasFactory;

    protected $fillable = [
        'EID',
        'Town_Id',
    ];

    public function town()
    {
        return $this->belongsTo(town::class, 'Town_Id');
    }

    public function employee()
    {
        return $this->belongsTo(employee::class, 'EID');
    }

}
