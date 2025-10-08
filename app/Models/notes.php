<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class notes extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'Title',
        'Content',
        'Pin',
        'created_by',
        'updated_by',
    ];

    public function shares(){
        return $this->hasMany(note_share::class, 'Note_Id');
    }

    public function creator()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(Employee::class, 'updated_by');
    }
}
