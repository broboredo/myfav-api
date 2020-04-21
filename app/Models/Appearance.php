<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appearance extends Model
{
    public function characterOne()
    {
        return $this->belongsTo(Character::class, 'character_one');
    }

    public function characterTwo()
    {
        return $this->belongsTo(Character::class, 'character_two');
    }
}
