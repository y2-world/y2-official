<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lyric extends Model
{
    protected $fillable = [
        'title',
        'album_id',
        'single_id',
        'lyrics',
    ];

    public function disco()
    {
        return $this->belongsTo(Disco::class, 'album_id');
    }

    public function album()
    {
        return $this->belongsTo(Disco::class, 'album_id')->where('type', 'album');
    }

    public function single()
    {
        return $this->belongsTo(Disco::class, 'single_id')->where('type', 'single');
    }
}
