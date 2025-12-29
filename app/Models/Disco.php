<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disco extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'date',
        'tracklist',
        'text',
        'url',
        'image',
        'type',
        'visible',
    ];

    protected $casts = [
        'tracklist' =>'json',
    ];

    public function getTourAttribute($value)
    {
        return array_values(json_decode($value, true) ?: []);
    }

    public function setSetlistAttribute($value)
    {
        $this->attributes['tracklist'] = json_encode(array_values($value));
    }

    // スコープ: アルバムのみ取得
    public function scopeAlbums($query)
    {
        return $query->where('type', 'album');
    }

    // スコープ: シングルのみ取得
    public function scopeSingles($query)
    {
        return $query->where('type', 'single');
    }
}
