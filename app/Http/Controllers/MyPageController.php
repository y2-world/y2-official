<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\DbSetlist;
use App\Models\DbSong;
use App\Models\UserArtist;
use App\Models\UserSetlist;
use App\Models\UserSong;
use Illuminate\Support\Facades\Auth;

class MyPageController extends Controller
{
    public function index()
    {
        $user = Auth::guard('external')->user();
        $attendances = $user->attendances()
            ->with(['dbSetlist.tour.artist', 'userSetlist.concert.artist'])
            ->orderByDesc('attended_date')
            ->get();

        $totalShows = $attendances->count();
        $totalArtists = $attendances
            ->map(fn ($a) => $a->db_setlist_id
                ? 'official-' . $a->dbSetlist?->tour?->artist_id
                : 'user-' . $a->userSetlist?->concert?->user_artist_id)
            ->filter(fn ($ref) => $ref !== 'official-' && $ref !== 'user-')
            ->unique()
            ->count();
        $totalVenues = $attendances->pluck('venue')->filter()->unique()->count();

        // 公式（db_setlists）とユーザー登録（user_setlists）、それぞれの出席記録からセットリストを引く
        $dbAttendances = $attendances->filter(fn ($a) => $a->db_setlist_id);
        $userAttendances = $attendances->filter(fn ($a) => $a->user_setlist_id);

        $dbSetlists = DbSetlist::whereIn('id', $dbAttendances->pluck('db_setlist_id')->unique())->with('tour')->get();
        $userSetlists = UserSetlist::whereIn('id', $userAttendances->pluck('user_setlist_id')->unique())->with('concert')->get();

        $songPlayCounts = [];
        $songTourTitles = [];
        $countSetlistSongs = function ($setlist, $tourTitle) use (&$songPlayCounts, &$songTourTitles) {
            $songsInThisSetlist = [];
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
        };

        // 公式楽曲IDとユーザー登録楽曲IDは同じテーブルではないため、
        // 曲の集計キーを "official-{id}" / "user-{id}" の複合参照にして混在させない
        $officialSongPlayCounts = [];
        $officialSongTourTitles = [];
        foreach ($dbSetlists as $setlist) {
            $tourTitle = $setlist->tour->title ?? 'Unknown';
            $countSetlistSongs($setlist, $tourTitle);
        }
        $officialSongPlayCounts = $songPlayCounts;
        $officialSongTourTitles = $songTourTitles;

        $songPlayCounts = [];
        $songTourTitles = [];
        foreach ($userSetlists as $setlist) {
            $tourTitle = $setlist->concert->title ?? 'Unknown';
            $countSetlistSongs($setlist, $tourTitle);
        }
        $userSongPlayCounts = $songPlayCounts;
        $userSongTourTitles = $songTourTitles;

        arsort($officialSongPlayCounts);
        arsort($userSongPlayCounts);

        $officialSongPlayCountsUnique = [];
        foreach ($officialSongTourTitles as $songId => $tourTitles) {
            $officialSongPlayCountsUnique[$songId] = count($tourTitles);
        }
        arsort($officialSongPlayCountsUnique);

        $userSongPlayCountsUnique = [];
        foreach ($userSongTourTitles as $songId => $tourTitles) {
            $userSongPlayCountsUnique[$songId] = count($tourTitles);
        }
        arsort($userSongPlayCountsUnique);

        $officialSongs = DbSong::whereIn('id', array_keys($officialSongPlayCounts))->get()->keyBy('id');
        $userSongs = UserSong::whereIn('id', array_keys($userSongPlayCounts))->get()->keyBy('id');

        $buildTopSongs = function (array $officialCounts, array $userCounts) use ($officialSongs, $userSongs) {
            $result = [];
            foreach ($officialCounts as $songId => $count) {
                $song = $officialSongs->get($songId);
                if ($song) {
                    $artist = $song->artist;
                    $result[] = [
                        'song_id' => 'official-' . $songId,
                        'title' => $song->title,
                        'artist_id' => $artist ? 'official-' . $artist->id : null,
                        'artist_name' => $artist ? $artist->name : '不明',
                        'count' => $count,
                    ];
                }
            }
            foreach ($userCounts as $songId => $count) {
                $song = $userSongs->get($songId);
                if ($song) {
                    $artist = $song->artist;
                    $result[] = [
                        'song_id' => 'user-' . $songId,
                        'title' => $song->title,
                        'artist_id' => $artist ? 'user-' . $artist->id : null,
                        'artist_name' => $artist ? $artist->name : '不明',
                        'count' => $count,
                    ];
                }
            }
            usort($result, fn ($a, $b) => $b['count'] <=> $a['count']);
            return $result;
        };

        $topSongs = $buildTopSongs($officialSongPlayCounts, $userSongPlayCounts);
        $topSongsUnique = $buildTopSongs($officialSongPlayCountsUnique, $userSongPlayCountsUnique);

        $overallStats = [
            'total_shows' => $totalShows,
            'total_artists' => $totalArtists,
            'total_songs' => count($officialSongPlayCounts) + count($userSongPlayCounts),
            'total_venues' => $totalVenues,
        ];

        $officialArtists = Artist::whereIn('id', $dbSetlists->pluck('tour.artist_id')->filter()->unique())->get();
        $userArtists = UserArtist::whereIn('id', $userSetlists->pluck('concert.user_artist_id')->filter()->unique())->get();

        // Live Stamp Bookの下に表示する、アーティストごとのUnique Songs（自分が聴いた曲数）統計
        // type=4（ソロ）は単独アーティストとしての集計に含めない（AttendanceControllerの絞り込みと同じ基準）
        $songIdsByArtist = [];
        foreach ($dbAttendances as $attendance) {
            $tour = $attendance->dbSetlist?->tour;
            if (!$tour || !$tour->artist_id || (int) $tour->type === 4) {
                continue;
            }
            $setlist = $attendance->dbSetlist;
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                if (isset($s['song']) && is_numeric($s['song'])) {
                    $songIdsByArtist['official-' . $tour->artist_id][(int) $s['song']] = true;
                }
            }
        }
        foreach ($userAttendances as $attendance) {
            $concert = $attendance->userSetlist?->concert;
            if (!$concert || !$concert->user_artist_id) {
                continue;
            }
            $setlist = $attendance->userSetlist;
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                if (isset($s['song']) && is_numeric($s['song'])) {
                    $songIdsByArtist['user-' . $concert->user_artist_id][(int) $s['song']] = true;
                }
            }
        }

        $artistSongStats = collect();
        foreach ($officialArtists as $artist) {
            $ref = 'official-' . $artist->id;
            if (!isset($songIdsByArtist[$ref])) {
                continue;
            }
            $uniqueSongs = count($songIdsByArtist[$ref]);
            $totalSongs = DbSong::where('artist_id', $artist->id)->count();
            $artistSongStats->push([
                'id' => $ref,
                'name' => $artist->name,
                'unique_songs' => $uniqueSongs,
                'total_songs' => $totalSongs,
                'percentage' => $totalSongs > 0 ? round($uniqueSongs / $totalSongs * 100, 1) : 0,
            ]);
        }
        foreach ($userArtists as $artist) {
            $ref = 'user-' . $artist->id;
            if (!isset($songIdsByArtist[$ref])) {
                continue;
            }
            $uniqueSongs = count($songIdsByArtist[$ref]);
            $totalSongs = UserSong::where('user_artist_id', $artist->id)->count();
            $artistSongStats->push([
                'id' => $ref,
                'name' => $artist->name,
                'unique_songs' => $uniqueSongs,
                'total_songs' => $totalSongs,
                'percentage' => $totalSongs > 0 ? round($uniqueSongs / $totalSongs * 100, 1) : 0,
            ]);
        }
        $artistSongStats = $artistSongStats->sortByDesc('percentage')->values();

        $officialArtistStats = $dbAttendances
            // type=4（ソロ）は本人単独のプロジェクトであり、アーティスト本体の参加数には含めない
            // （イベント・ap bank fesはそのアーティスト自身としての出演なので含める）
            ->filter(fn ($a) => $a->dbSetlist?->tour?->artist && (int) $a->dbSetlist->tour->type !== 4)
            ->groupBy(fn ($a) => $a->dbSetlist->tour->artist_id)
            ->map(function ($group) {
                $artist = $group->first()->dbSetlist->tour->artist;
                return [
                    'id' => 'official-' . $artist->id,
                    'name' => $artist->name,
                    'show_count' => $group->count(),
                ];
            });

        $userArtistStats = $userAttendances
            ->filter(fn ($a) => $a->userSetlist?->concert?->artist)
            ->groupBy(fn ($a) => $a->userSetlist->concert->user_artist_id)
            ->map(function ($group) {
                $artist = $group->first()->userSetlist->concert->artist;
                return [
                    'id' => 'user-' . $artist->id,
                    'name' => $artist->name,
                    'show_count' => $group->count(),
                ];
            });

        $artistStats = $officialArtistStats->merge($userArtistStats)->sortByDesc('show_count')->values();

        $venueStats = $attendances
            ->filter(fn ($a) => $a->venue)
            ->groupBy('venue')
            ->map(fn ($group, $venue) => (object) ['venue' => $venue, 'count' => $group->count()])
            ->sortByDesc('count')
            ->take(10)
            ->values();

        $yearStats = $attendances
            ->filter(fn ($a) => $a->attended_date)
            ->groupBy(fn ($a) => $a->attended_date->format('Y'))
            ->map(fn ($group, $year) => (object) ['year' => $year, 'count' => $group->count()])
            ->sortByDesc('count')
            ->take(10)
            ->values();

        return view('mypage.index', compact('attendances', 'overallStats', 'topSongs', 'topSongsUnique', 'artistStats', 'artistSongStats', 'venueStats', 'yearStats'));
    }
}
