<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DbAlbum extends Model
{
    protected $table = 'db_albums';

    protected $fillable = [
        'artist_id',
        'album_id',
        'date',
        'title',
        'best',
        'mini',
        'text',
        'tracklist',
    ];

    protected $casts = [
        'tracklist' =>'json',
    ];

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function songs()
    {
        return $this->hasMany('App\Models\DbSong');
    }

    public function getSongs()
    {
        return $this->belongsToMany(DbSong::class);
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
