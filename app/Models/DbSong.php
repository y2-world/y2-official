<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DbSong extends Model
{
    protected $table = 'db_songs';

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
        $albums = DbAlbum::where('artist_id', $this->artist_id)
            ->orderBy('date', 'asc')
            ->get();

        $contains = fn($album) => collect($album->tracklist ?? [])->pluck('id')->contains((string) $songId);

        // album_idあり（オリジナル・ミニ）を優先
        $original = $albums->where('best', false)->whereNotNull('album_id')->first($contains);
        if ($original) return $original;

        // ベストアルバムのみフォールバック
        return $albums->where('best', true)->first($contains);
    }

    public function getSingleFromTracklistAttribute()
    {
        $songId = $this->id;
        return DbSingle::where('artist_id', $this->artist_id)
            ->get()
            ->first(function ($single) use ($songId) {
                $tracklist = $single->tracklist ?? [];
                return collect($tracklist)->pluck('id')->contains((string) $songId);
            });
    }
}
