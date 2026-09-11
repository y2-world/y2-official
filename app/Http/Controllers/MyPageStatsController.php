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

    // アーティスト別の自分専用統計（/stats/artist/{id} のMy Page版）。
    // 集計対象は自分が記録したExternalUserAttendance経由のdb_setlistsのみ。
    public function artist($artistId)
    {
        $artist = Artist::find($artistId);
        if (!$artist) {
            abort(404);
        }

        $attendances = Auth::guard('external')->user()
            ->attendances()
            ->with('dbSetlist.tour')
            ->whereHas('dbSetlist.tour', fn($q) => $q->where('artist_id', $artistId))
            ->get();

        $totalShows = $attendances->count();

        $songPlayCounts = [];
        foreach ($attendances as $attendance) {
            $setlist = $attendance->dbSetlist;
            if (!$setlist) {
                continue;
            }
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                if (isset($s['song']) && is_numeric($s['song'])) {
                    $songId = (int)$s['song'];
                    $songPlayCounts[$songId] = ($songPlayCounts[$songId] ?? 0) + 1;
                }
            }
        }
        arsort($songPlayCounts);

        $songs = DbSong::whereIn('id', array_keys($songPlayCounts))->get()->keyBy('id');
        $allSongs = [];
        foreach ($songPlayCounts as $songId => $count) {
            $song = $songs->get($songId);
            if ($song) {
                $allSongs[] = [
                    'song_id' => $songId,
                    'title' => $song->title,
                    'count' => $count,
                ];
            }
        }

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

        $setlists = DbSetlist::whereIn('id', $attendedSetlistIds)->get();

        $playedDbSongIds = [];
        foreach ($setlists as $setlist) {
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                if (isset($s['song']) && is_numeric($s['song'])) {
                    $playedDbSongIds[(int)$s['song']] = true;
                }
            }
        }

        $everPerformedDbSongIds = $this->everPerformedDbSongIds((int)$artistId);

        $dbSongs = DbSong::where('artist_id', $artistId)->orderBy('id')->get();
        $stamps = $dbSongs->map(function (DbSong $song) use ($playedDbSongIds, $everPerformedDbSongIds) {
            return [
                'song_id' => $song->id,
                'title' => $song->title,
                'done' => isset($playedDbSongIds[$song->id]),
                'never_performed' => !isset($everPerformedDbSongIds[$song->id]),
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
