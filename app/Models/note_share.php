<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class note_share extends Model
{
    use HasFactory;
    protected $fillable = [
        'EID',
        'Note_Id',
    ];

    public function employee()
    {
        return $this->belongsTo(employee::class, 'EID');
    }
    
    public function notes()
    {
        return $this->belongsTo(notes::class, 'Note_Id');
    }
}
