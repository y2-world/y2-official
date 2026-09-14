<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ComputesDbSongStamps;
use App\Http\Controllers\Concerns\SplitsKindRef;
use App\Models\Artist;
use App\Models\DbSetlist;
use App\Models\DbSong;
use App\Models\ExternalUser;
use App\Models\UserArtist;
use App\Models\UserSetlist;
use App\Models\UserSong;
use App\Support\JapaneseNameSorter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyPageStatsController extends Controller
{
    use ComputesDbSongStamps;
    use SplitsKindRef;

    // My Stamp Books一覧（アーティストごとのスタンプ帳への入り口）。
    // 対象は自分が出席記録を残しているアーティスト（公式・ユーザー登録の両方）のみ。
    public function index()
    {
        $attendances = Auth::guard('external')->user()
            ->attendances()
            ->with(['dbSetlist.tour', 'userSetlist.concert'])
            ->get();

        $attendedOfficialArtistIds = $attendances
            ->pluck('dbSetlist.tour.artist_id')
            ->filter()
            ->unique();
        $attendedUserArtistIds = $attendances
            ->pluck('userSetlist.concert.user_artist_id')
            ->filter()
            ->unique();

        $officialArtists = JapaneseNameSorter::sortBy(Artist::whereIn('id', $attendedOfficialArtistIds)->get());
        $userArtists = JapaneseNameSorter::sortBy(UserArtist::whereIn('id', $attendedUserArtistIds)->get());

        return view('mypage.stats.stamps_index', compact('officialArtists', 'userArtists'));
    }

    // アーティスト別の自分専用統計（/stats/artist/{id} のMy Page版）。
    // $artistId は "official-{id}" / "user-{id}" 形式。
    public function artist($artistId)
    {
        [$kind, $id] = $this->splitRef($artistId);

        if ($kind === 'official') {
            return $this->officialArtistStats($id);
        }

        return $this->userArtistStats($id);
    }

    private function officialArtistStats($id)
    {
        $artist = Artist::find($id);
        if (!$artist) {
            abort(404);
        }

        // type=4（ソロ）は本人単独のプロジェクトであり、アーティスト本体の統計には含めない
        // （イベント・ap bank fesはそのアーティスト自身としての出演なので含める）
        $attendances = Auth::guard('external')->user()
            ->attendances()
            ->with('dbSetlist.tour')
            ->whereHas('dbSetlist.tour', fn ($q) => $q->where('artist_id', $id)->where('type', '!=', 4))
            ->get();

        $totalShows = $attendances->count();

        $songPlayCounts = [];
        // 同名ツアーを1回だけカウントする版：[songId => [tourTitle1, tourTitle2, ...]]
        $songTourTitles = [];
        foreach ($attendances as $attendance) {
            $setlist = $attendance->dbSetlist;
            if (!$setlist) {
                continue;
            }
            // 1つのセットリスト（＝1回のライブ参加）内で同じ曲が複数回演奏されても1回とカウントする
            $songsInThisSetlist = [];
            $tourTitle = $setlist->tour->title ?? 'Unknown';
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                if (isset($s['song']) && is_numeric($s['song'])) {
                    $songId = (int) $s['song'];
                    if (!in_array($songId, $songsInThisSetlist, true)) {
                        $songsInThisSetlist[] = $songId;
                        $songPlayCounts[$songId] = ($songPlayCounts[$songId] ?? 0) + 1;

                        if (!isset($songTourTitles[$songId])) {
                            $songTourTitles[$songId] = [];
                        }
                        if (!in_array($tourTitle, $songTourTitles[$songId], true)) {
                            $songTourTitles[$songId][] = $tourTitle;
                        }
                    }
                }
            }
        }
        arsort($songPlayCounts);

        $songPlayCountsUnique = [];
        foreach ($songTourTitles as $songId => $tourTitles) {
            $songPlayCountsUnique[$songId] = count($tourTitles);
        }
        arsort($songPlayCountsUnique);

        $songs = DbSong::whereIn('id', array_keys($songPlayCounts))->get()->keyBy('id');

        $buildAllSongs = function (array $counts) use ($songs) {
            $result = [];
            foreach ($counts as $songId => $count) {
                $song = $songs->get($songId);
                if ($song) {
                    $result[] = [
                        'song_id' => 'official-' . $songId,
                        'title' => $song->title,
                        'count' => $count,
                    ];
                }
            }
            return $result;
        };

        $allSongs = $buildAllSongs($songPlayCounts);
        $allSongsUnique = $buildAllSongs($songPlayCountsUnique);

        $totalSongs = count($allSongs);

        $yearStats = $attendances
            ->filter(fn ($a) => $a->attended_date)
            ->groupBy(fn ($a) => $a->attended_date->format('Y'))
            ->map(fn ($group, $year) => (object) ['year' => $year, 'count' => $group->count()])
            ->sortKeysDesc()
            ->values();

        $venueStats = $attendances
            ->filter(fn ($a) => $a->venue)
            ->groupBy('venue')
            ->map(fn ($group, $venue) => (object) ['venue' => $venue, 'count' => $group->count()])
            ->sortByDesc('count')
            ->values();

        $artistRef = 'official-' . $artist->id;
        $stampsRoute = route('mypage.stats.stamps', $artistRef);

        return view('mypage.stats.artist', compact(
            'artist',
            'artistRef',
            'stampsRoute',
            'totalShows',
            'totalSongs',
            'allSongs',
            'allSongsUnique',
            'yearStats',
            'venueStats'
        ));
    }

    private function userArtistStats($id)
    {
        $artist = UserArtist::find($id);
        if (!$artist) {
            abort(404);
        }

        $attendances = Auth::guard('external')->user()
            ->attendances()
            ->with('userSetlist.concert')
            ->whereHas('userSetlist.concert', fn ($q) => $q->where('user_artist_id', $id))
            ->get();

        $totalShows = $attendances->count();

        $songPlayCounts = [];
        $songTourTitles = [];
        foreach ($attendances as $attendance) {
            $setlist = $attendance->userSetlist;
            if (!$setlist) {
                continue;
            }
            $songsInThisSetlist = [];
            $tourTitle = $setlist->concert->title ?? 'Unknown';
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                if (isset($s['song']) && is_numeric($s['song'])) {
                    $songId = (int) $s['song'];
                    if (!in_array($songId, $songsInThisSetlist, true)) {
                        $songsInThisSetlist[] = $songId;
                        $songPlayCounts[$songId] = ($songPlayCounts[$songId] ?? 0) + 1;

                        if (!isset($songTourTitles[$songId])) {
                            $songTourTitles[$songId] = [];
                        }
                        if (!in_array($tourTitle, $songTourTitles[$songId], true)) {
                            $songTourTitles[$songId][] = $tourTitle;
                        }
                    }
                }
            }
        }
        arsort($songPlayCounts);

        $songPlayCountsUnique = [];
        foreach ($songTourTitles as $songId => $tourTitles) {
            $songPlayCountsUnique[$songId] = count($tourTitles);
        }
        arsort($songPlayCountsUnique);

        $songs = UserSong::whereIn('id', array_keys($songPlayCounts))->get()->keyBy('id');

        $buildAllSongs = function (array $counts) use ($songs) {
            $result = [];
            foreach ($counts as $songId => $count) {
                $song = $songs->get($songId);
                if ($song) {
                    $result[] = [
                        'song_id' => 'user-' . $songId,
                        'title' => $song->title,
                        'count' => $count,
                    ];
                }
            }
            return $result;
        };

        $allSongs = $buildAllSongs($songPlayCounts);
        $allSongsUnique = $buildAllSongs($songPlayCountsUnique);

        $totalSongs = count($allSongs);

        $yearStats = $attendances
            ->filter(fn ($a) => $a->attended_date)
            ->groupBy(fn ($a) => $a->attended_date->format('Y'))
            ->map(fn ($group, $year) => (object) ['year' => $year, 'count' => $group->count()])
            ->sortKeysDesc()
            ->values();

        $venueStats = $attendances
            ->filter(fn ($a) => $a->venue)
            ->groupBy('venue')
            ->map(fn ($group, $venue) => (object) ['venue' => $venue, 'count' => $group->count()])
            ->sortByDesc('count')
            ->values();

        $artistRef = 'user-' . $artist->id;
        $stampsRoute = route('mypage.stats.stamps', $artistRef);

        return view('mypage.stats.artist', compact(
            'artist',
            'artistRef',
            'stampsRoute',
            'totalShows',
            'totalSongs',
            'allSongs',
            'allSongsUnique',
            'yearStats',
            'venueStats'
        ));
    }

    // アーティストのdatabase楽曲カタログを台紙にしたスタンプ帳。
    // $artistId は "official-{id}" / "user-{id}" 形式。
    // ?user={id} クエリで他ユーザーのスタンプ帳も閲覧できる（プロフィールページのリンク先）。
    // 省略時はログイン中の自分。
    public function stamps(Request $request, $artistId)
    {
        [$kind, $id] = $this->splitRef($artistId);

        $viewedUserId = $request->query('user');
        $externalUser = $viewedUserId
            ? ExternalUser::findOrFail($viewedUserId)
            : Auth::guard('external')->user();

        if ($kind === 'official') {
            return $this->officialStamps($id, $externalUser);
        }

        return $this->userStamps($id, $externalUser);
    }

    // 判定基準はStatsController::getStampBookと同じ考え方だが、「演奏済み」の元データが
    // SlSetlist（Yuki本人の記録）ではなく、自分が記録したExternalUserAttendanceになる。
    // db_setlistsはSlSetlistと違いフェス形式（block/interleaved）を持たず、songキーが
    // 直接db_song_idを指すため、SlSong経由の変換やフェス展開は不要でシンプルになる。
    private function officialStamps($artistId, $externalUser)
    {
        $artist = Artist::find($artistId);
        if (!$artist) {
            abort(404);
        }

        $attendedSetlistIds = $externalUser
            ->attendances()
            ->whereHas('dbSetlist.tour', fn ($q) => $q->where('artist_id', $artistId))
            ->pluck('db_setlist_id');

        $setlists = DbSetlist::whereIn('id', $attendedSetlistIds)->with('tour')->get();

        // type=0（ツアー）・1（単発ライブ）以外（イベント・ap bank fes・ソロ）はFES扱いとし、
        // そちらでしか演奏されていない曲は台紙上で区別できるようにする
        $playedDbSongIdsNormal = [];
        $playedDbSongIdsFes = [];
        foreach ($setlists as $setlist) {
            $isFes = !in_array((int) ($setlist->tour->type ?? 0), [0, 1], true);
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                if (isset($s['song']) && is_numeric($s['song'])) {
                    if ($isFes) {
                        $playedDbSongIdsFes[(int) $s['song']] = true;
                    } else {
                        $playedDbSongIdsNormal[(int) $s['song']] = true;
                    }
                }
            }
        }

        $playedDbSongIds = $playedDbSongIdsNormal + $playedDbSongIdsFes;
        $fesOnlyDbSongIds = array_diff_key($playedDbSongIdsFes, $playedDbSongIdsNormal);

        $everPerformedDbSongIds = $this->everPerformedDbSongIds((int) $artistId);

        $dbSongs = DbSong::where('artist_id', $artistId)->orderBy('sort_order')->get();
        $stamps = $dbSongs->map(function (DbSong $song) use ($playedDbSongIds, $everPerformedDbSongIds, $fesOnlyDbSongIds) {
            return [
                'song_id' => $song->id,
                'song_url' => route('songs.show', $song->id),
                'title' => $song->title,
                'done' => isset($playedDbSongIds[$song->id]),
                'never_performed' => !isset($everPerformedDbSongIds[$song->id]),
                'fes_only' => isset($fesOnlyDbSongIds[$song->id]),
            ];
        });

        return $this->renderStampsView($artist, $stamps, $externalUser);
    }

    // ユーザー登録アーティストのスタンプ帳。演奏記録の元がYuki本人の公式記録ではなく
    // ユーザー自身の申告（UserSong/UserSetlist）のため、「未演奏」区分は設けず
    // 「自分が参加したセットリストで演奏された曲か」だけのシンプルな2値判定にする。
    // ただしtype（0=ツアー・1=単発ライブ以外はフェス扱い）は公式側と同様に区別し、
    // フェスでしか演奏されていない曲はfes_onlyとして台紙上で区別できるようにする。
    private function userStamps($artistId, $externalUser)
    {
        $artist = UserArtist::find($artistId);
        if (!$artist) {
            abort(404);
        }

        $attendedSetlistIds = $externalUser
            ->attendances()
            ->whereHas('userSetlist.concert', fn ($q) => $q->where('user_artist_id', $artistId))
            ->pluck('user_setlist_id');

        $setlists = UserSetlist::whereIn('id', $attendedSetlistIds)->with('concert')->get();

        $playedUserSongIdsNormal = [];
        $playedUserSongIdsFes = [];
        foreach ($setlists as $setlist) {
            $isFes = !in_array((int) ($setlist->concert->type ?? 0), [0, 1], true);
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                if (isset($s['song']) && is_numeric($s['song'])) {
                    if ($isFes) {
                        $playedUserSongIdsFes[(int) $s['song']] = true;
                    } else {
                        $playedUserSongIdsNormal[(int) $s['song']] = true;
                    }
                }
            }
        }

        $playedUserSongIds = $playedUserSongIdsNormal + $playedUserSongIdsFes;
        $fesOnlyUserSongIds = array_diff_key($playedUserSongIdsFes, $playedUserSongIdsNormal);

        $userSongs = UserSong::where('user_artist_id', $artistId)->orderBy('sort_order')->get();
        $stamps = $userSongs->map(function (UserSong $song) use ($playedUserSongIds, $fesOnlyUserSongIds) {
            return [
                'song_id' => $song->id,
                'song_url' => route('mypage.attendances.index', ['song_id' => 'user-' . $song->id]),
                'title' => $song->title,
                'done' => isset($playedUserSongIds[$song->id]),
                'never_performed' => false,
                'fes_only' => isset($fesOnlyUserSongIds[$song->id]),
            ];
        });

        return $this->renderStampsView($artist, $stamps, $externalUser);
    }

    private function renderStampsView($artist, $stamps, $externalUser)
    {
        $totalCount = $stamps->count();
        $doneCount = $stamps->where('done', true)->count();
        $percentage = $totalCount > 0 ? round(($doneCount / $totalCount) * 100, 1) : 0;

        $performedCount = $stamps->where('never_performed', false)->count();
        $performedPercentage = $performedCount > 0 ? round(($doneCount / $performedCount) * 100, 1) : 0;

        $isOwner = $externalUser->id === Auth::guard('external')->id();

        return view('mypage.stats.stamps', compact(
            'artist',
            'stamps',
            'totalCount',
            'doneCount',
            'percentage',
            'performedCount',
            'performedPercentage',
            'externalUser',
            'isOwner'
        ));
    }
}
