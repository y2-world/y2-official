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
        // 作成・更新のたびに、同一アーティストでタイトルが一致する未紐付けのSlSongが1件だけ
        // 見つかれば自動で紐付ける（SlSong::booted()の逆方向。どちらを先に登録・編集しても紐付く）。
        // savedを使うのは、createdの時点ではまだ$song->idが確定していない場合があるため、
        // 保存完了後（更新時のタイトル変更でも再チェックされる）に実行する。
        static::saved(function (DbSong $song) {
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

            \Log::info('DEBUG DbSong auto-match (saved event)', [
                'song_id' => $song->id,
                'song_title' => $song->title,
                'candidate_count' => $candidates->count(),
                'match_count' => $matches->count(),
                'match_ids' => $matches->pluck('id')->all(),
            ]);

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

    // この曲（db_songs.id）が実際に演奏されたDbSetlist（setlist/encore列内に自分のidまたは
    // タイトル一致の項目を含むもの）を、演奏日（tour.date1）降順で返す。
    // DbSetController@show の抽出ロジックと同じ考え方（Eloquentリレーションではなく
    // JSON列の総当たりスキャンになるのは、db_setlists側が曲IDを外部キーとして持たないため）。
    public function performedTourSetlists()
    {
        $id = $this->id;
        $title = $this->title;

        return DbSetlist::with('tour')->get()
            ->filter(function ($setlistModel) use ($id, $title) {
                $lists = array_merge($setlistModel->setlist ?? [], $setlistModel->encore ?? []);

                foreach ($lists as $entry) {
                    if (!isset($entry['song'])) {
                        continue;
                    }
                    if (is_numeric($entry['song']) && (int) $entry['song'] === (int) $id) {
                        return true;
                    }
                    if (!is_numeric($entry['song'])) {
                        $entryTitle = preg_replace('/\s*\[[^\]]+\]/u', '', $entry['song']);
                        if (trim($entryTitle) === $title) {
                            return true;
                        }
                    }
                }
                return false;
            })
            ->sortByDesc(fn ($setlistModel) => optional($setlistModel->tour)->date1)
            ->values();
    }
}
