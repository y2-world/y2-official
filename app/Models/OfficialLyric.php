<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficialLyric extends Model
{
    protected $table = 'official_lyrics';

    protected $fillable = [
        'title',
        'album_id',
        'single_id',
        'lyrics',
    ];

    public function disco()
    {
        return $this->belongsTo(OfficialRelease::class, 'album_id');
    }

    public function album()
    {
        return $this->belongsTo(OfficialRelease::class, 'album_id');
    }

    public function single()
    {
        return $this->belongsTo(OfficialRelease::class, 'single_id');
    }
}
