<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sitcom extends Model
{
    CONST BASE_IMG_URL = 'https://image.tmdb.org/t/p/w500';

    protected $fillable = [
        'name',
        'logo',
        'name_pt',
        'start_date',
        'end_date'
    ];

    public function characters()
    {
        return $this->hasMany(Character::class);
    }

    public function getLogoAttribute($logo)
    {
        return self::BASE_IMG_URL . $logo;
    }
}
