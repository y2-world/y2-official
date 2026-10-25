<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DbSingle extends Model
{
    // 管理画面（AdminData）で、マイページのモデルと同じ書き方で絞り込むためのスコープ
    public function scopeOfArtist($query, $artistId)
    {
        return $query->where('artist_id', $artistId);
    }

    protected $table = 'db_singles';

    protected $fillable = [
        'artist_id',
        'single_id',
        'date',
        'title',
        'download',
        'ep',
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
