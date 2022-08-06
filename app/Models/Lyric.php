<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lyric extends Model
{
    public function disco()
    {
        return $this->belongsTo(Disco::class, 'album_id');
    }
}
