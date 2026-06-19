<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DbSingle extends Model
{
    protected $table = \'db_singles\';

    protected $fillable = [
        'artist_id',
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

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function songs()
    {
        return $this->hasMany('App\Models\DbSong');
    }
}
