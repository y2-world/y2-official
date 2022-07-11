<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Song extends Model
{
    public function album()
    {
        return $this->belongsTo(Album::class, 'album_id');
    }

    public function single()
    {
        return $this->belongsTo(Single::class, 'single_id');
    }
}
