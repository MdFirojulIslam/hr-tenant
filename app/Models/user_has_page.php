<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class user_has_page extends Model
{
    use HasFactory;

    protected $fillable = [
        'Page_Id',
        'EID',
        'created_by',
        'updated_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'User_Id');
    }
}
