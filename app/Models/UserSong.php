<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UserSong extends Model
{
    protected $fillable = [
        'user_artist_id',
        // 管理画面（公式の DbSong と同じ画面）で使う別名。中身は user_artist_id
        'artist_id',
        'title',
        'sort_order',
        // 公式のテーブルと同じ項目（管理画面で同じ画面を使うため）
        'album_id',
        'single_id',
        'year',
        'text',
        'credit',
    ];

    // 管理画面で公式の DbSong と同じ名前で扱えるようにする別名（artist_id = user_artist_id）
    // 管理画面の編集画面は、モデルの中身（toArray）をフォームに入れるので、別名も入るようにする
    protected $appends = ['artist_id'];

    public const ADMIN_COLUMNS = ['artist_id' => 'user_artist_id'];

    public function getArtistIdAttribute()
    {
        return $this->attributes['user_artist_id'] ?? null;
    }

    public function setArtistIdAttribute($value): void
    {
        $this->attributes['user_artist_id'] = $value;
    }

    public function scopeOfArtists($query, array $artistIds)
    {
        return $query->whereIn('user_artist_id', $artistIds);
    }

    public function artist()
    {
        return $this->belongsTo(UserArtist::class, 'user_artist_id');
    }

    // セットリストで「アーティスト表記あり」の行に出す表記（公式の DbSong::setlistCredit と同じ）
    public function setlistCredit(): ?string
    {
        $credit = trim((string) $this->credit);

        return $credit === '' ? null : $credit;
    }

    // この曲が入っているシングル・アルバムのうち、発売日がいちばん早いもの（公式の DbSong の singleFromTracklist / albumFromTracklist と同じ）。
    // 管理画面で手動指定されていれば、それを優先する
    public function getSingleFromTracklistAttribute()
    {
        if ($this->single_id && ($manual = UserSingle::where('user_artist_id', $this->user_artist_id)->find($this->single_id))) {
            return $manual;
        }

        return $this->firstDiscContaining(UserSingle::class);
    }

    // アルバムは初出のオリジナルアルバム（ベスト盤・企画盤ではないもの）を基準にする。オリジナルアルバムに入っていない曲だけ、ベスト盤・企画盤のいちばん早いもの
    public function getAlbumFromTracklistAttribute()
    {
        if ($this->album_id && ($manual = UserAlbum::where('user_artist_id', $this->user_artist_id)->find($this->album_id))) {
            return $manual;
        }

        return $this->firstDiscContaining(UserAlbum::class, fn ($q) => $q->where('best', false)->where('special', false))
            ?? $this->firstDiscContaining(UserAlbum::class);
    }

    private function firstDiscContaining(string $model, ?\Closure $scope = null)
    {
        $songId = (int) $this->id;

        return $model::where('user_artist_id', $this->user_artist_id)
            ->when($scope, $scope)
            ->orderByRaw('date IS NULL')->orderBy('date')->orderBy('id')
            ->get()
            ->first(fn ($disc) => collect($disc->tracklist ?? [])->contains(fn ($track) => (int) ($track['id'] ?? 0) === $songId));
    }

    // 大文字小文字を無視して既存曲を検索し、無ければ作成する。
    // MySQL時代はutf8mb4_unicode_ci照合順序により firstOrCreate(['title' => $title]) が
    // 自動的に大文字小文字を無視していたが、Postgresのデフォルト照合順序は区別するため、
    // 単純な firstOrCreate のままだと "Abc" と "abc" が別々の曲として複製されてしまう。
    public static function firstOrCreateByTitle(int $userArtistId, string $title): self
    {
        $existing = static::where('user_artist_id', $userArtistId)
            ->whereRaw('LOWER(title) = LOWER(?)', [$title])
            ->first();

        if ($existing) {
            return $existing;
        }

        return static::create([
            'user_artist_id' => $userArtistId,
            'title' => $title,
            'sort_order' => (static::where('user_artist_id', $userArtistId)->max('sort_order') ?? -1) + 1,
        ]);
    }

    // この曲（user_songs.id）が実際に演奏されたUserSetlist（setlist/encore列に自分のidを含むもの）を、
    // 演奏日（concert.date1）降順で返す。DbSong::performedTourSetlists()と同じ考え方。
    public function performedTourSetlists()
    {
        $id = $this->id;

        return UserSetlist::whereHas('concert', fn ($q) => $q->where('user_artist_id', $this->user_artist_id))
            ->with('concert')
            ->get()
            ->filter(function (UserSetlist $setlistModel) use ($id) {
                $lists = array_merge($setlistModel->setlist ?? [], $setlistModel->encore ?? []);

                foreach ($lists as $entry) {
                    if (isset($entry['song']) && is_numeric($entry['song']) && (int) $entry['song'] === (int) $id) {
                        return true;
                    }
                }

                return false;
            })
            ->sortByDesc(fn (UserSetlist $setlistModel) => $setlistModel->concert->date1 ?? '')
            ->values();
    }

    // この曲が演奏されたUserSetlist（performedTourSetlists()と同じ抽出結果）のうち、
    // ログイン中の外部ユーザー本人が「参加した」と記録しているものに対応するツアーを、
    // 開催日（concert.date1）降順で返す（未ログイン時は空）。DbSong::myAttendedTours()と同じ考え方。
    // $tourSetlists を渡せば同じ抽出結果を再利用でき、渡さなければ自前で計算する。
    public function myAttendedTours($tourSetlists = null)
    {
        if (!Auth::guard('external')->check()) {
            return collect();
        }

        $matchingSetlistIds = ($tourSetlists ?? $this->performedTourSetlists())->pluck('id');

        return Auth::guard('external')->user()
            ->attendances()
            ->whereIn('user_setlist_id', $matchingSetlistIds)
            ->with('userSetlist.concert')
            ->get()
            ->pluck('userSetlist.concert')
            ->filter()
            ->unique('id')
            ->sortByDesc(fn ($concert) => $concert->date1)
            ->values();
    }

    // 指定ユーザーの参加記録を古い順に見ていったとき、このアーティストの曲の中で各曲が
    // 何番目に初めて登場したかのマップ（[user_song_id => 番号, ...]）を返す。
    // AttendanceController::index の#（曲番）・Previous/Next（初めて聴いた順）と同じ考え方。
    public static function firstSeenOrderFor(ExternalUser $user, int $userArtistId): array
    {
        $orderedAttendances = $user
            ->attendances()
            ->whereHas('userSetlist.concert', fn ($q) => $q->where('user_artist_id', $userArtistId))
            ->with('userSetlist')
            ->orderBy('attended_date')
            ->get();

        $firstSeenOrder = [];
        foreach ($orderedAttendances as $attendance) {
            $setlist = $attendance->userSetlist;
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
}
