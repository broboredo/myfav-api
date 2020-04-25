<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    protected $fillable = [
        'name', //max 18 chars
        'img',
        'is_first',
        'sitcom_name'
    ];

    protected $hidden = [
        'sitcom_id'
    ];

    protected $appends = [
        'is_first',
        'total_appearances'
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

    public function appearances()
    {
        return Appearance::where('character_one', $this->id)
            ->orWhere('character_two', $this->id);
    }

    public function getTotalAppearancesAttribute()
    {
        return $this->appearances()->count();
    }
}
