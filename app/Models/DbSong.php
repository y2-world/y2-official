<?php

namespace App\Models;

use App\Support\SongTitleNormalizer;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class DbSong extends Model
{
    protected $table = 'db_songs';

    protected $fillable = [
        'title',
        'artist_id',
        'text',
        'sort_order',
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

            if ($matches->count() === 1) {
                $matches->first()->update(['db_song_id' => $song->id]);
                return;
            }

            if (app()->runningInConsole()) {
                return;
            }

            // 候補が2件以上で自動では決められない場合、サイレントにスキップすると
            // 気づかれないまま未紐付けが放置されるため、管理画面上に通知する。
            // モデルイベントはArtisanコマンド等（通知先のUIが無い文脈）からも発火するため、
            // Web/Livewireリクエスト内でのみ送信する。
            if ($matches->count() > 1) {
                Notification::make()
                    ->warning()
                    ->title('セットリスト楽曲の自動紐付けが曖昧です')
                    ->body("「{$song->title}」に一致する未紐付けのセットリスト楽曲が複数見つかったため、自動紐付けをスキップしました。手動で紐付けてください。")
                    ->persistent()
                    ->send();
                return;
            }

            // 候補が0件でも、「タイトルは一致するが既に別のDbSongに奪われている」SlSongが
            // 存在する場合は、新曲でSlSongがまだ存在しないだけの通常ケースとは違い、
            // データの取り合いが起きている異常な状態なので気づけるようにする。
            $stolenByOther = SlSong::where('artist_id', $song->artist_id)
                ->whereNotNull('db_song_id')
                ->where('db_song_id', '!=', $song->id)
                ->get(['id', 'title', 'db_song_id'])
                ->first(fn(SlSong $candidate) => SongTitleNormalizer::normalize($candidate->title) === $normalizedTitle);

            if ($stolenByOther) {
                $owner = DbSong::find($stolenByOther->db_song_id);
                Notification::make()
                    ->warning()
                    ->title('セットリスト楽曲が既に別の楽曲に紐付いています')
                    ->body("「{$song->title}」に一致するセットリスト楽曲「{$stolenByOther->title}」は既に別の楽曲「"
                        . ($owner?->title ?? '(id: ' . $stolenByOther->db_song_id . ')')
                        . '」に紐付いているため、自動紐付けできませんでした。')
                    ->persistent()
                    ->send();
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

        // ベストアルバムでフォールバック
        $best = $albums->where('best', true)->first($contains);
        if ($best) return $best;

        // best/mini/album_idのいずれにも当たらない企画盤（クラシック・アレンジ集等）
        return $albums->where('best', false)->whereNull('album_id')->first($contains);
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
        $artistId = $this->artist_id;

        return DbSetlist::with('tour')->get()
            ->filter(function ($setlistModel) use ($id, $title, $artistId) {
                $lists = array_merge($setlistModel->setlist ?? [], $setlistModel->encore ?? []);

                foreach ($lists as $entry) {
                    if (!isset($entry['song'])) {
                        continue;
                    }
                    if (is_numeric($entry['song']) && (int) $entry['song'] === (int) $id) {
                        return true;
                    }
                    if (!is_numeric($entry['song'])) {
                        // 曲名の文字列一致は同名異アーティスト曲を拾ってしまうため、
                        // このセットリストのツアーが自分と同じアーティストのものである場合に限定する
                        if (optional($setlistModel->tour)->artist_id !== $artistId) {
                            continue;
                        }
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
