<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bio extends Model
{
    protected $fillable = [
        'artist_id',
        'year',
        'text',
    ];

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function songs()
    {
        return $this->hasMany('App\Models\Song');
    }

    public function tours()
    {
        return $this->hasMany('App\Models\Tour');
    }
}
