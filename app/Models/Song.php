<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Song extends Model
{
    protected $fillable = [
        'title',
        'artist_id',
        'text',
    ];

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function getAlbumFromTracklistAttribute()
    {
        $songId = $this->id;
        return Album::where('artist_id', $this->artist_id)
            ->where('best', false)
            ->get()
            ->first(function ($album) use ($songId) {
                $tracklist = $album->tracklist ?? [];
                return collect($tracklist)->pluck('id')->contains((string) $songId);
            });
    }

    public function getSingleFromTracklistAttribute()
    {
        $songId = $this->id;
        return Single::where('artist_id', $this->artist_id)
            ->get()
            ->first(function ($single) use ($songId) {
                $tracklist = $single->tracklist ?? [];
                return collect($tracklist)->pluck('id')->contains((string) $songId);
            });
    }
}
