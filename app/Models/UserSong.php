<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UserSong extends Model
{
    protected $fillable = [
        'user_artist_id',
        'title',
        'sort_order',
    ];

    public function artist()
    {
        return $this->belongsTo(UserArtist::class, 'user_artist_id');
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
