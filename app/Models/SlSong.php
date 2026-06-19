<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlSong extends Model
{
    protected $table = \'sl_songs\';

    use HasFactory;

    protected $fillable = [
        'title',
        'artist_id',
    ];

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }
}
