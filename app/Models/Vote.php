<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    protected $fillable = [
        'character_id'
    ];

    public function character()
    {
        return $this->hasOne(Character::class);
    }
}
