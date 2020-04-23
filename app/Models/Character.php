<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    protected $fillable = [
        'name',
        'img',
        'is_first'
    ];

    protected $hidden = [
        'sitcom_id'
    ];

    protected $appends = [
        'is_first'
    ];

    public function sitcom()
    {
        return $this->belongsTo(Sitcom::class);
    }

    public function votes()
    {
        return $this->hasMany(Vote::class);
    }

    public static function ranking()
    {
        return Character::withCount('votes')->orderBy('votes_count', 'desc');
    }

    public function getIsFirstAttribute(): bool
    {
        return self::ranking()->first()->id === $this->id ? true : false;
    }
}
