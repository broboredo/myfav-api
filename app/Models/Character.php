<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    protected $fillable = [
        'name',
        'img'
    ];

    protected $hidden = [
        'sitcom_id'
    ];

    public function sitcom()
    {
        return $this->belongsTo(Sitcom::class);
    }
}
