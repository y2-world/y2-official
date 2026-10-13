<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// スタンプ（曲）ごとに選んだ色（StampColor）。artist_ref は "official-{id}" / "user-{id}"
class StampMark extends Model
{
    protected $fillable = ['external_user_id', 'artist_ref', 'song_id', 'stamp_color_id'];
}
