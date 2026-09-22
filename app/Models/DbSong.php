<?php

namespace App\Models;

use App\Support\SongTitleNormalizer;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DbSong extends Model
{
    protected $table = 'db_songs';

    protected $fillable = [
        'title',
        'artist_id',
        'text',
        'sort_order',
        'album_id',
        'single_id',
    ];

    protected static function booted()
    {
        // sort_orderを指定せずに作成された場合、NULLのままだと曲詳細ページのprevious/next取得
        // （where('sort_order', '<', ...)）がIlluminateのIllegal operator and value combination
        // 例外を起こすため、同一アーティスト内の最大値+1を自動的に割り当てる。
        static::creating(function (DbSong $song) {
            if ($song->sort_order === null && $song->artist_id) {
                $song->sort_order = (static::where('artist_id', $song->artist_id)->max('sort_order') ?? -1) + 1;
            }
        });

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
        // 管理画面で収録アルバムが手動指定されていれば最優先する（同じ曲がリミックス盤等の
        // 別アルバムにも収録されていて、日付順の自動判定だけでは正しい初出を選べない場合の
        // 上書き手段。手動指定が無ければ日付が最も早いものにフォールバックする）
        if ($this->album_id) {
            $manual = DbAlbum::find($this->album_id);
            if ($manual) {
                return $manual;
            }
        }

        $songId = $this->id;
        $contains = fn($album) => collect($album->tracklist ?? [])->pluck('id')->contains((string) $songId);

        return DbAlbum::where('artist_id', $this->artist_id)
            ->orderBy('date', 'asc')
            ->get()
            ->first($contains);
    }

    // 「Disc 1」「DISC-2」「Reel.3」「CD」「ボーナスCD」「Bonus Disc」のような、単なる収録媒体の分割を
    // 示すだけの定型discラベルにマッチする。これらはアルバム名の代わりにはならない
    private const GENERIC_DISC_LABEL_PATTERN = '/^(disc|reel)[\s\-.]*\d*$|^cd$|^ボーナスcd$|disc/ui';

    // 曲一覧・曲詳細でアルバム名として表示する文字列。通常はアルバム自体のtitleだが、
    // 収録トラックのdiscラベルが「Disc 1」等の定型分割ラベルではなく、「Slow Collection」
    // のようなアルバム本編とは別の固有の呼称を持つボーナスディスクの場合はそちらを優先表示する
    // （例: AKIRA初回限定盤ボーナスCD収録の新録曲は「AKIRA」ではなく「Slow Collection」として見せたい）
    public function getAlbumDisplayTitleFromTracklistAttribute()
    {
        $album = $this->albumFromTracklist;
        if (!$album) {
            return null;
        }

        $songId = (string) $this->id;
        $track = collect($album->tracklist ?? [])->first(fn($t) => isset($t['id']) && (string) $t['id'] === $songId);
        $disc = $track['disc'] ?? null;

        if ($disc && !preg_match(self::GENERIC_DISC_LABEL_PATTERN, $disc)) {
            return $disc;
        }

        return $album->title;
    }

    public function getSingleFromTracklistAttribute()
    {
        // 収録アルバムと同様、手動指定があれば最優先する
        if ($this->single_id) {
            $manual = DbSingle::find($this->single_id);
            if ($manual) {
                return $manual;
            }
        }

        $songId = $this->id;
        $contains = fn($single) => collect($single->tracklist ?? [])->pluck('id')->contains((string) $songId);

        return DbSingle::where('artist_id', $this->artist_id)
            ->orderBy('date', 'asc')
            ->get()
            ->first($contains);
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

    // この曲が演奏されたDbSetlist（performedTourSetlists()と同じ抽出結果）のうち、
    // ログイン中の外部ユーザー本人が「参加した」と記録しているものに対応するツアーを、
    // 開催日（tour.date1）降順で返す（未ログイン時は空）。
    // $tourSetlists を渡せば同じ抽出結果を再利用でき、渡さなければ自前で計算する。
    public function myAttendedTours($tourSetlists = null)
    {
        if (!Auth::guard('external')->check()) {
            return collect();
        }

        $matchingSetlistIds = ($tourSetlists ?? $this->performedTourSetlists())->pluck('id');

        return Auth::guard('external')->user()
            ->attendances()
            ->whereIn('db_setlist_id', $matchingSetlistIds)
            ->with('dbSetlist.tour')
            ->get()
            ->pluck('dbSetlist.tour')
            ->filter()
            ->unique('id')
            ->sortByDesc(fn ($tour) => $tour->date1)
            ->values();
    }

    // 指定ユーザーの参加記録を古い順に見ていったとき、このアーティストの曲の中で各曲が
    // 何番目に初めて登場したかのマップ（[db_song_id => 番号, ...]）を返す。
    // AttendanceController::index の#（曲番）・Previous/Next（初めて聴いた順）と同じ考え方。
    public static function firstSeenOrderFor(\App\Models\ExternalUser $user, int $artistId): array
    {
        $orderedAttendances = $user
            ->attendances()
            ->whereHas('dbSetlist.tour', fn ($q) => $q->where('artist_id', $artistId))
            ->with('dbSetlist')
            ->orderBy('attended_date')
            ->get();

        $firstSeenOrder = [];
        foreach ($orderedAttendances as $attendance) {
            $setlist = $attendance->dbSetlist;
            if (!$setlist) {
                continue;
            }
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                if (isset($s['song']) && is_numeric($s['song'])) {
                    $sid = (int) $s['song'];
                    if (!isset($firstSeenOrder[$sid])) {
                        $firstSeenOrder[$sid] = count($firstSeenOrder) + 1;
                    }
                }
            }
        }

        return $firstSeenOrder;
    }

    // この曲（db_songs.id）に紐づくSlSong（複数の可能性あり）を演奏している
    // SlSetlist（セットリストサイト側の全記録＝運営者本人のライブ参加履歴）を、
    // 日付降順で返す。SlSongController@showの抽出ロジックと同じ考え方。
    public function performedSlSetlists()
    {
        $slSongIds = $this->slSongs()->pluck('id')->map(fn ($id) => (string) $id)->all();
        $title = $this->title;

        if (empty($slSongIds)) {
            return collect();
        }

        $expandFes = function ($items) {
            $result = [];
            foreach ($items as $item) {
                if (($item['type'] ?? 'song') === 'block') {
                    foreach ($item['songs'] ?? [] as $s) {
                        $result[] = $s;
                    }
                } else {
                    $result[] = $item;
                }
            }
            return $result;
        };

        return SlSetlist::all()
            ->filter(function ($setlist) use ($slSongIds, $title, $expandFes) {
                $allLists = array_merge(
                    $setlist->setlist ?? [],
                    $setlist->encore ?? [],
                    $expandFes($setlist->fes_setlist ?? []),
                    $expandFes($setlist->fes_encore ?? [])
                );

                foreach ($allLists as $entry) {
                    $song = $entry['song'] ?? null;
                    if ($song === null) {
                        continue;
                    }
                    if (is_numeric($song) && in_array((string) (int) $song, $slSongIds, true)) {
                        return true;
                    }
                    if (!is_numeric($song)) {
                        $entryTitle = preg_replace('/\s*\[[^\]]+\]/u', '', $song);
                        if (trim($entryTitle) === $title) {
                            return true;
                        }
                    }
                }
                return false;
            })
            ->sortByDesc('date')
            ->values();
    }
}
