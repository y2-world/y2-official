<?php

namespace App\Models;

use App\Support\SongTitleNormalizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlSong extends Model
{
    protected $table = 'sl_songs';

    use HasFactory;

    protected $fillable = [
        'title',
        'artist_id',
        'db_song_id',
    ];

    protected static function booted()
    {
        // 保存時、同一アーティストでタイトルが一致するDbSongが1件だけ見つかれば自動で紐付ける。
        // 既に手動で紐付け済み（db_song_id指定あり）の場合は上書きしない。
        static::saving(function (SlSong $song) {
            if ($song->db_song_id || !$song->artist_id || !$song->title) {
                return;
            }

            $normalizedTitle = SongTitleNormalizer::normalize($song->title);
            $candidates = DbSong::where('artist_id', $song->artist_id)->get(['id', 'title']);
            $matches = $candidates->filter(
                fn(DbSong $candidate) => SongTitleNormalizer::normalize($candidate->title) === $normalizedTitle
            );

            if ($matches->count() === 1) {
                $song->db_song_id = $matches->first()->id;
            }
        });
    }

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function dbSong()
    {
        return $this->belongsTo(DbSong::class);
    }
}
