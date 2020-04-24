<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    protected $fillable = [
        'character_id',
        'appearance_id'
    ];

    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    public function appearance()
    {
        return $this->belongsTo(Appearance::class);
    }
}
