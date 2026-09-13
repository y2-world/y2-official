<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ComputesDbSongStamps;
use App\Models\Artist;
use App\Models\DbSetlist;
use App\Models\DbSong;
use Illuminate\Support\Facades\Auth;

class MyPageStatsController extends Controller
{
    use ComputesDbSongStamps;

    // My Stamp Books一覧（アーティストごとのスタンプ帳への入り口）。
    // 対象は自分が出席記録を残しているアーティストのみ。
    public function index()
    {
        $attendedArtistIds = Auth::guard('external')->user()
            ->attendances()
            ->with('dbSetlist.tour')
            ->get()
            ->pluck('dbSetlist.tour.artist_id')
            ->filter()
            ->unique();

        $artists = Artist::whereIn('id', $attendedArtistIds)->orderBy('name')->get();

        return view('mypage.stats.stamps_index', compact('artists'));
    }

    // アーティスト別の自分専用統計（/stats/artist/{id} のMy Page版）。
    // 集計対象は自分が記録したExternalUserAttendance経由のdb_setlistsのみ。
    public function artist($artistId)
    {
        $artist = Artist::find($artistId);
        if (!$artist) {
            abort(404);
        }

        // type=4（ソロ）は本人単独のプロジェクトであり、アーティスト本体の統計には含めない
        // （イベント・ap bank fesはそのアーティスト自身としての出演なので含める）
        $attendances = Auth::guard('external')->user()
            ->attendances()
            ->with('dbSetlist.tour')
            ->whereHas('dbSetlist.tour', fn($q) => $q->where('artist_id', $artistId)->where('type', '!=', 4))
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
                    $songId = (int)$s['song'];
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
                        'song_id' => $songId,
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

        return view('mypage.stats.artist', compact(
            'artist',
            'totalShows',
            'totalSongs',
            'allSongs',
            'allSongsUnique',
            'yearStats',
            'venueStats'
        ));
    }

    // アーティストのdatabase楽曲カタログを台紙にした、自分専用のスタンプ帳。
    // 判定基準はStatsController::getStampBookと同じ考え方だが、「演奏済み」の元データが
    // SlSetlist（Yuki本人の記録）ではなく、自分が記録したExternalUserAttendanceになる。
    // db_setlistsはSlSetlistと違いフェス形式（block/interleaved）を持たず、songキーが
    // 直接db_song_idを指すため、SlSong経由の変換やフェス展開は不要でシンプルになる。
    public function stamps($artistId)
    {
        $artist = Artist::find($artistId);
        if (!$artist) {
            abort(404);
        }

        $attendedSetlistIds = Auth::guard('external')->user()
            ->attendances()
            ->whereHas('dbSetlist.tour', fn($q) => $q->where('artist_id', $artistId))
            ->pluck('db_setlist_id');

        $setlists = DbSetlist::whereIn('id', $attendedSetlistIds)->with('tour')->get();

        // type=0（ツアー）・1（単発ライブ）以外（イベント・ap bank fes・ソロ）はFES扱いとし、
        // そちらでしか演奏されていない曲は台紙上で区別できるようにする
        $playedDbSongIdsNormal = [];
        $playedDbSongIdsFes = [];
        foreach ($setlists as $setlist) {
            $isFes = !in_array((int)($setlist->tour->type ?? 0), [0, 1], true);
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                if (isset($s['song']) && is_numeric($s['song'])) {
                    if ($isFes) {
                        $playedDbSongIdsFes[(int)$s['song']] = true;
                    } else {
                        $playedDbSongIdsNormal[(int)$s['song']] = true;
                    }
                }
            }
        }

        $playedDbSongIds = $playedDbSongIdsNormal + $playedDbSongIdsFes;
        $fesOnlyDbSongIds = array_diff_key($playedDbSongIdsFes, $playedDbSongIdsNormal);

        $everPerformedDbSongIds = $this->everPerformedDbSongIds((int)$artistId);

        $dbSongs = DbSong::where('artist_id', $artistId)->orderBy('id')->get();
        $stamps = $dbSongs->map(function (DbSong $song) use ($playedDbSongIds, $everPerformedDbSongIds, $fesOnlyDbSongIds) {
            return [
                'song_id' => $song->id,
                'title' => $song->title,
                'done' => isset($playedDbSongIds[$song->id]),
                'never_performed' => !isset($everPerformedDbSongIds[$song->id]),
                'fes_only' => isset($fesOnlyDbSongIds[$song->id]),
            ];
        });

        $totalCount = $stamps->count();
        $doneCount = $stamps->where('done', true)->count();
        $percentage = $totalCount > 0 ? round(($doneCount / $totalCount) * 100, 1) : 0;

        $performedCount = $stamps->where('never_performed', false)->count();
        $performedPercentage = $performedCount > 0 ? round(($doneCount / $performedCount) * 100, 1) : 0;

        return view('mypage.stats.stamps', compact(
            'artist',
            'stamps',
            'totalCount',
            'doneCount',
            'percentage',
            'performedCount',
            'performedPercentage'
        ));
    }
}
