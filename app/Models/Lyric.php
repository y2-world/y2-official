<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lyric extends Model
{
    public function disco()
    {
        return $this->belongsTo(Disco::class, 'album_id');
    }

    public function album()
    {
        return $this->belongsTo(Disco::class, 'album_id');
    }

    public function single()
    {
        return $this->belongsTo(Disco::class, 'single_id');
    }
}
