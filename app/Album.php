<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DbAlbum extends Model
{

    public function songs()
    {
        return $this->hasMany('App\DbSong'); 
    }

    public function getAlbumAttribute($value)
    {
        return array_values(json_decode($value, true) ?: []);
    }

    public function setTracklistAttribute($value)
    {
        $this->attributes['tracklist'] = json_encode(array_values($value));
    }
}
