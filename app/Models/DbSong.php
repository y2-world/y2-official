<?php

namespace App\Models;

use App\Support\SongTitleNormalizer;
use Illuminate\Database\Eloquent\Model;

class DbSong extends Model
{
    protected $table = 'db_songs';

    protected $fillable = [
        'title',
        'artist_id',
        'text',
    ];

    protected static function booted()
    {
        // 新規作成時、同一アーティストでタイトルが一致する未紐付けのSlSongが1件だけ見つかれば
        // 自動で紐付ける（SlSong::booted()の逆方向。どちらを先に登録しても紐付く）。
        static::created(function (DbSong $song) {
            if (!$song->artist_id || !$song->title) {
                return;
            }

            $normalizedTitle = SongTitleNormalizer::normalize($song->title);
            $candidates = SlSong::where('artist_id', $song->artist_id)
                ->whereNull('db_song_id')
                ->get(['id', 'title']);
            $matches = $candidates->filter(
                fn(SlSong $candidate) => SongTitleNormalizer::normalize($candidate->title) === $normalizedTitle
            );

            if ($matches->count() === 1) {
                $matches->first()->update(['db_song_id' => $song->id]);
            }
        });
    }

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function slSongs()
    {
        return $this->hasMany(SlSong::class);
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
