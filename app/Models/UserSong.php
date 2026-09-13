<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSong extends Model
{
    protected $fillable = [
        'user_artist_id',
        'title',
        'sort_order',
    ];

    public function artist()
    {
        return $this->belongsTo(UserArtist::class, 'user_artist_id');
    }
}
