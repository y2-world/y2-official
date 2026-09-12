<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\DbSetlist;
use App\Models\DbSong;
use Illuminate\Support\Facades\Auth;

class MyPageController extends Controller
{
    public function index()
    {
        $user = Auth::guard('external')->user();
        $attendances = $user->attendances()
            ->with('dbSetlist.tour.artist')
            ->orderByDesc('attended_date')
            ->get();

        $totalShows = $attendances->count();
        $totalArtists = $attendances->pluck('dbSetlist.tour.artist_id')->filter()->unique()->count();
        $totalVenues = $attendances->pluck('venue')->filter()->unique()->count();

        $attendedSetlistIds = $attendances->pluck('db_setlist_id')->unique();
        $setlists = DbSetlist::whereIn('id', $attendedSetlistIds)->with('tour')->get();

        $songPlayCounts = [];
        // 同名ツアーを1回だけカウントする版：[songId => [tourTitle1, tourTitle2, ...]]
        $songTourTitles = [];
        foreach ($setlists as $setlist) {
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

        $buildTopSongs = function (array $counts) use ($songs) {
            $result = [];
            foreach ($counts as $songId => $count) {
                $song = $songs->get($songId);
                if ($song) {
                    $artist = $song->artist;
                    $result[] = [
                        'song_id' => $songId,
                        'title' => $song->title,
                        'artist_id' => $artist?->id,
                        'artist_name' => $artist ? $artist->name : '不明',
                        'count' => $count,
                    ];
                }
            }
            return $result;
        };

        $topSongs = $buildTopSongs($songPlayCounts);
        $topSongsUnique = $buildTopSongs($songPlayCountsUnique);

        $overallStats = [
            'total_shows' => $totalShows,
            'total_artists' => $totalArtists,
            'total_songs' => count($songPlayCounts),
            'total_venues' => $totalVenues,
        ];

        $artists = Artist::whereIn('id', $setlists->pluck('tour.artist_id')->filter()->unique())->get();

        // Live Stamp Bookの下に表示する、アーティストごとのUnique Songs（自分が聴いた曲数）統計
        // type=4（ソロ）は単独アーティストとしての集計に含めない（AttendanceControllerの絞り込みと同じ基準）
        $songIdsByArtist = [];
        foreach ($attendances as $attendance) {
            $tour = $attendance->dbSetlist?->tour;
            if (!$tour || !$tour->artist_id || (int)$tour->type === 4) {
                continue;
            }
            $setlist = $attendance->dbSetlist;
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                if (isset($s['song']) && is_numeric($s['song'])) {
                    $songIdsByArtist[$tour->artist_id][(int)$s['song']] = true;
                }
            }
        }
        $artistSongStats = $artists
            ->filter(fn ($artist) => isset($songIdsByArtist[$artist->id]))
            ->map(function ($artist) use ($songIdsByArtist) {
                return [
                    'id' => $artist->id,
                    'name' => $artist->name,
                    'unique_songs' => count($songIdsByArtist[$artist->id]),
                    'total_songs' => DbSong::where('artist_id', $artist->id)->count(),
                ];
            })
            ->sortByDesc('unique_songs')
            ->values();

        $artistStats = $attendances
            // type=0（ツアー）・1（単発ライブ）以外は複数アーティスト出演のフェス等のため、単独アーティストの参加数には含めない
            ->filter(fn ($a) => $a->dbSetlist?->tour?->artist && !in_array((int)$a->dbSetlist->tour->type, [2, 3, 4], true))
            ->groupBy(fn ($a) => $a->dbSetlist->tour->artist_id)
            ->map(function ($group) {
                $artist = $group->first()->dbSetlist->tour->artist;
                return [
                    'id' => $artist->id,
                    'name' => $artist->name,
                    'show_count' => $group->count(),
                ];
            })
            ->sortByDesc('show_count')
            ->values();

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

        return view('mypage.index', compact('attendances', 'overallStats', 'topSongs', 'topSongsUnique', 'artists', 'artistStats', 'artistSongStats', 'venueStats', 'yearStats'));
    }
}
