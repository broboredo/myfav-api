<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    protected $fillable = [
        'name',
        'img',
        'is_first',
        'sitcom_name'
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

    public function getIsFirstAttribute(): bool
    {
        return self::ranking()->first()->id === $this->id ? true : false;
    }

    public function getSitcomName(): string
    {
        return $this->sitcom->name;
    }

    public static function ranking()
    {
        return Character::with('sitcom')
            ->withCount('votes')
            ->orderBy('votes_count', 'desc');
    }
}
