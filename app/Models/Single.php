<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Single extends Model
{
    protected $fillable = [
        'single_id',
        'date',
        'title',
        'download',
        'text',
        'tracklist',
    ];

    protected $casts = [
        'tracklist' =>'json',
    ];

    public function getAlbumAttribute($value)
    {
        return array_values(json_decode($value, true) ?: []);
    }

    public function setTracklistAttribute($value)
    {
        $this->attributes['tracklist'] = json_encode(array_values($value));
    }

    public function songs(){
        return $this->hasMany('App\Models\Song');
    }
}
