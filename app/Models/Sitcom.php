<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sitcom extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'name_pt',
        'start_date',
        'end_date'
    ];

    public function characters()
    {
        $this->hasMany(Character::class);
    }
}
