<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setlist;
use App\Models\TourSetlist;
use App\Models\Tour;
use App\Models\Artist;
use App\Models\Song;
use App\Models\Single;
use App\Models\Album;
use App\Models\SetlistSong;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'personal');

        if ($tab === 'mrchildren') {
            return $this->getMrChildrenStats();
        }

        // Personal stats (参加したライブの履歴)
        $overallStats = $this->getPersonalOverallStats();
        $songStats = $this->getPersonalSongStats();
        $songStatsUnique = $this->getPersonalSongStats(true); // 同名ツアーを1回カウント
        $artistStats = $this->getPersonalArtistStats();
        $venueStats = $this->getPersonalVenueStats();
        $yearStats = $this->getPersonalYearStats();
        $monthStats = $this->getPersonalMonthStats();

        $tab = 'personal';

        return view('stats.index', compact(
            'overallStats',
            'songStats',
            'songStatsUnique',
            'artistStats',
            'venueStats',
            'yearStats',
            'monthStats',
            'tab'
        ));
    }

    // =====================================
    // Personal統計（参加したライブ履歴）
    // =====================================

    private function getPersonalOverallStats()
    {
        $totalShows = Setlist::count();

        // ユニークなアーティスト数
        $uniqueArtists = Setlist::whereNotNull('artist_id')
            ->distinct('artist_id')
            ->count('artist_id');

        // 聴いた曲数（setlistsから全曲のユニークID）
        $allSongIds = [];
        $setlists = Setlist::all();
        foreach ($setlists as $setlist) {
            $songs = array_merge(
                $setlist->setlist ?? [],
                $setlist->encore ?? [],
                $setlist->fes_setlist ?? [],
                $setlist->fes_encore ?? []
            );
            foreach ($songs as $songData) {
                if (isset($songData['song']) && is_numeric($songData['song'])) {
                    $allSongIds[(int)$songData['song']] = true;
                }
            }
        }
        $uniqueSongs = count($allSongIds);

        // 訪れた会場数
        $uniqueVenues = Setlist::whereNotNull('venue')
            ->where('venue', '!=', '')
            ->distinct('venue')
            ->count('venue');

        return [
            'total_shows' => $totalShows,
            'total_artists' => $uniqueArtists,
            'total_songs' => $uniqueSongs,
            'total_venues' => $uniqueVenues,
        ];
    }

    private function getPersonalSongStats($uniqueTourOnly = false)
    {
        // 最も聴いた曲トップ10（IDのみ使用）
        $setlists = Setlist::all();
        $songPlayCounts = [];

        if ($uniqueTourOnly) {
            // 同名ツアーを1回だけカウント
            $songTourCounts = []; // [songId => [tourName1, tourName2, ...]]

            foreach ($setlists as $setlist) {
                $allSongs = array_merge(
                    $setlist->setlist ?? [],
                    $setlist->encore ?? [],
                    $setlist->fes_setlist ?? [],
                    $setlist->fes_encore ?? []
                );

                $tourName = $setlist->title ?? 'Unknown';

                foreach ($allSongs as $songData) {
                    if (isset($songData['song']) && is_numeric($songData['song'])) {
                        $songId = (int)$songData['song'];

                        if (!isset($songTourCounts[$songId])) {
                            $songTourCounts[$songId] = [];
                        }

                        // 同じツアー名で複数回出ても1カウント
                        if (!in_array($tourName, $songTourCounts[$songId])) {
                            $songTourCounts[$songId][] = $tourName;
                        }
                    }
                }
            }

            // ツアー数をカウント
            foreach ($songTourCounts as $songId => $tours) {
                $songPlayCounts[$songId] = count($tours);
            }
        } else {
            // 通常のカウント（全セットリスト）
            foreach ($setlists as $setlist) {
                $allSongs = array_merge(
                    $setlist->setlist ?? [],
                    $setlist->encore ?? [],
                    $setlist->fes_setlist ?? [],
                    $setlist->fes_encore ?? []
                );

                foreach ($allSongs as $songData) {
                    if (isset($songData['song']) && is_numeric($songData['song'])) {
                        $songId = (int)$songData['song'];
                        if (!isset($songPlayCounts[$songId])) {
                            $songPlayCounts[$songId] = 0;
                        }
                        $songPlayCounts[$songId]++;
                    }
                }
            }
        }

        arsort($songPlayCounts);
        $topSongIds = array_slice(array_keys($songPlayCounts), 0, 10, true);

        $topSongs = [];
        foreach ($topSongIds as $songId) {
            $setlistSong = SetlistSong::find($songId);
            if ($setlistSong) {
                $artist = Artist::find($setlistSong->artist_id);
                $topSongs[] = [
                    'song_id' => $songId,
                    'title' => $setlistSong->title,
                    'artist_id' => $setlistSong->artist_id,
                    'artist_name' => $artist ? $artist->name : '不明',
                    'count' => $songPlayCounts[$songId],
                ];
            }
        }

        return $topSongs;
    }

    private function getPersonalArtistStats()
    {
        // アーティスト別の参加公演数（全アーティスト）
        $artistShowCounts = Setlist::whereNotNull('artist_id')
            ->select('artist_id', DB::raw('count(*) as count'))
            ->groupBy('artist_id')
            ->orderBy('count', 'desc')
            ->get();

        $artistStats = [];
        foreach ($artistShowCounts as $item) {
            $artist = Artist::find($item->artist_id);
            if ($artist) {
                $artistStats[] = [
                    'id' => $artist->id,
                    'name' => $artist->name,
                    'show_count' => $item->count,
                ];
            }
        }

        return collect($artistStats);
    }

    private function getPersonalVenueStats()
    {
        // 最も訪れた会場トップ10
        $venues = Setlist::select('venue', DB::raw('count(*) as count'))
            ->whereNotNull('venue')
            ->where('venue', '!=', '')
            ->groupBy('venue')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return $venues;
    }

    private function getPersonalYearStats()
    {
        // 年別の参加公演数（多い順）
        $yearStats = Setlist::select('year', DB::raw('count(*) as count'))
            ->whereNotNull('year')
            ->groupBy('year')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return $yearStats;
    }

    private function getPersonalMonthStats()
    {
        // 月別の参加公演数分布
        $monthCounts = Setlist::select(DB::raw('MONTH(date) as month'), DB::raw('count(*) as count'))
            ->whereNotNull('date')
            ->groupBy('month')
            ->get()
            ->pluck('count', 'month')
            ->toArray();

        $monthStats = [];
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        for ($i = 1; $i <= 12; $i++) {
            $monthStats[] = (object)[
                'month' => $monthNames[$i - 1],
                'count' => $monthCounts[$i] ?? 0,
            ];
        }

        return collect($monthStats);
    }

    // =====================================
    // Mr.Children統計（ツアー情報のみ）
    // =====================================

    private function getMrChildrenStats()
    {
        $overallStats = $this->getMrChildrenOverallStats();
        $songStats = $this->getMrChildrenSongStats();
        $encoreSongStats = $this->getMrChildrenEncoreSongStats();
        $openingSongStats = $this->getMrChildrenOpeningSongStats();
        $longestSetlists = $this->getMrChildrenLongestSetlists();
        $yearStats = $this->getMrChildrenYearStats();

        $tab = 'mrchildren';

        return view('stats.mrchildren', compact(
            'overallStats',
            'songStats',
            'encoreSongStats',
            'openingSongStats',
            'longestSetlists',
            'yearStats',
            'tab'
        ));
    }

    private function getMrChildrenOverallStats()
    {
        $totalTours = Tour::count();
        $totalSetlistPatterns = TourSetlist::count();
        $totalSongs = Song::count();

        // ツアーで演奏された曲数（ユニーク）
        $uniqueSongIds = [];
        $tourSetlists = TourSetlist::all();
        foreach ($tourSetlists as $setlist) {
            $songs = array_merge(
                $setlist->setlist ?? [],
                $setlist->encore ?? []
            );
            foreach ($songs as $songData) {
                if (isset($songData['song']) && is_numeric($songData['song'])) {
                    $uniqueSongIds[(int)$songData['song']] = true;
                }
            }
        }
        $uniqueSongsInTours = count($uniqueSongIds);

        // 平均セットリスト曲数
        $totalSongCount = 0;
        $setlistCount = 0;
        foreach ($tourSetlists as $setlist) {
            $songs = array_merge(
                $setlist->setlist ?? [],
                $setlist->encore ?? []
            );
            $totalSongCount += count($songs);
            $setlistCount++;
        }
        $avgSetlistLength = $setlistCount > 0 ? round($totalSongCount / $setlistCount, 1) : 0;

        return [
            'total_tours' => $totalTours,
            'total_setlist_patterns' => $totalSetlistPatterns,
            'total_songs' => $totalSongs,
            'unique_songs_in_tours' => $uniqueSongsInTours,
            'avg_setlist_length' => $avgSetlistLength,
        ];
    }

    private function getMrChildrenSongStats()
    {
        // ツアーで演奏された全曲（ツアーごとに1カウント）
        $tourSetlists = TourSetlist::all();
        $songTourCounts = []; // [songId => [tourId1, tourId2, ...]]

        foreach ($tourSetlists as $setlist) {
            $allSongs = array_merge(
                $setlist->setlist ?? [],
                $setlist->encore ?? []
            );

            foreach ($allSongs as $songData) {
                if (isset($songData['song']) && is_numeric($songData['song'])) {
                    $songId = (int)$songData['song'];
                    $tourId = $setlist->tour_id;

                    if (!isset($songTourCounts[$songId])) {
                        $songTourCounts[$songId] = [];
                    }

                    // 同じツアーで複数回出ても1カウント
                    if (!in_array($tourId, $songTourCounts[$songId])) {
                        $songTourCounts[$songId][] = $tourId;
                    }
                }
            }
        }

        // ツアー数をカウント
        $songPlayCounts = [];
        foreach ($songTourCounts as $songId => $tourIds) {
            $songPlayCounts[$songId] = count($tourIds);
        }

        arsort($songPlayCounts);

        $songStats = [];
        foreach ($songPlayCounts as $songId => $count) {
            $song = Song::find($songId);
            if ($song) {
                $songStats[] = [
                    'song_id' => $songId,
                    'title' => $song->title,
                    'count' => $count,
                ];
            }
        }

        return $songStats;
    }

    private function getMrChildrenTourStats()
    {
        // ツアーごとのセットリストパターン数
        $tourPatternCounts = TourSetlist::select('tour_id', DB::raw('count(*) as pattern_count'))
            ->groupBy('tour_id')
            ->orderBy('pattern_count', 'desc')
            ->limit(5)
            ->get();

        $tourStats = [];
        foreach ($tourPatternCounts as $item) {
            $tour = Tour::find($item->tour_id);
            if ($tour) {
                $tourStats[] = [
                    'title' => $tour->title,
                    'pattern_count' => $item->pattern_count,
                ];
            }
        }

        return $tourStats;
    }

    private function getMrChildrenTourPatternStats()
    {
        // 全ツアーのパターン数
        $tourPatterns = TourSetlist::select('tour_id', DB::raw('count(*) as pattern_count'))
            ->groupBy('tour_id')
            ->orderBy('tour_id', 'asc')
            ->get();

        $tourPatternStats = [];
        foreach ($tourPatterns as $item) {
            $tour = Tour::find($item->tour_id);
            if ($tour) {
                $tourPatternStats[] = [
                    'tour_title' => $tour->title,
                    'pattern_count' => $item->pattern_count,
                ];
            }
        }

        return collect($tourPatternStats);
    }

    private function getMrChildrenYearStats()
    {
        // 年別のツアー数（多い順）
        $yearStats = Tour::select(DB::raw('YEAR(date1) as year'), DB::raw('count(*) as count'))
            ->whereNotNull('date1')
            ->groupBy('year')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return $yearStats;
    }

    private function getMrChildrenEncoreSongStats()
    {
        // アンコールで最も演奏された曲（ツアーごとに1カウント）
        $tourSetlists = TourSetlist::all();
        $encoreSongTourCounts = []; // [songId => [tourId1, tourId2, ...]]

        foreach ($tourSetlists as $setlist) {
            $encoreSongs = $setlist->encore ?? [];

            foreach ($encoreSongs as $songData) {
                if (isset($songData['song']) && is_numeric($songData['song'])) {
                    $songId = (int)$songData['song'];
                    $tourId = $setlist->tour_id;

                    if (!isset($encoreSongTourCounts[$songId])) {
                        $encoreSongTourCounts[$songId] = [];
                    }

                    // 同じツアーで複数回出ても1カウント
                    if (!in_array($tourId, $encoreSongTourCounts[$songId])) {
                        $encoreSongTourCounts[$songId][] = $tourId;
                    }
                }
            }
        }

        // ツアー数をカウント
        $encoreSongCounts = [];
        foreach ($encoreSongTourCounts as $songId => $tourIds) {
            $encoreSongCounts[$songId] = count($tourIds);
        }

        arsort($encoreSongCounts);

        $encoreStats = [];
        foreach (array_slice($encoreSongCounts, 0, 10, true) as $songId => $count) {
            $song = Song::find($songId);
            if ($song) {
                $encoreStats[] = [
                    'song_id' => $songId,
                    'title' => $song->title,
                    'count' => $count,
                ];
            }
        }

        return $encoreStats;
    }

    private function getMrChildrenOpeningSongStats()
    {
        // オープニング曲の統計（ツアーごとに1カウント）
        $tourSetlists = TourSetlist::all();
        $openingSongTourCounts = []; // [songId => [tourId1, tourId2, ...]]

        foreach ($tourSetlists as $setlist) {
            $setlistSongs = $setlist->setlist ?? [];
            if (!empty($setlistSongs)) {
                $firstSong = $setlistSongs[0];
                if (isset($firstSong['song']) && is_numeric($firstSong['song'])) {
                    $songId = (int)$firstSong['song'];
                    $tourId = $setlist->tour_id;

                    if (!isset($openingSongTourCounts[$songId])) {
                        $openingSongTourCounts[$songId] = [];
                    }

                    // 同じツアーで複数回出ても1カウント
                    if (!in_array($tourId, $openingSongTourCounts[$songId])) {
                        $openingSongTourCounts[$songId][] = $tourId;
                    }
                }
            }
        }

        // ツアー数をカウント
        $openingSongCounts = [];
        foreach ($openingSongTourCounts as $songId => $tourIds) {
            $openingSongCounts[$songId] = count($tourIds);
        }

        arsort($openingSongCounts);

        $openingStats = [];
        foreach (array_slice($openingSongCounts, 0, 10, true) as $songId => $count) {
            $song = Song::find($songId);
            if ($song) {
                $openingStats[] = [
                    'song_id' => $songId,
                    'title' => $song->title,
                    'count' => $count,
                ];
            }
        }

        return $openingStats;
    }

    private function getMrChildrenLongestSetlists()
    {
        // 最も曲数の多いセットリスト
        $tourSetlists = TourSetlist::all();
        $setlistLengths = [];

        foreach ($tourSetlists as $setlist) {
            $songs = array_merge(
                $setlist->setlist ?? [],
                $setlist->encore ?? []
            );
            $songCount = 0;
            foreach ($songs as $songData) {
                if (isset($songData['song']) && is_numeric($songData['song'])) {
                    // is_dailyが1の曲は除外
                    if (!isset($songData['is_daily']) || $songData['is_daily'] != 1) {
                        $songCount++;
                    }
                }
            }

            if ($songCount > 0) {
                $tour = Tour::find($setlist->tour_id);
                $setlistLengths[] = [
                    'tour_id' => $setlist->tour_id,
                    'tour_title' => $tour ? $tour->title : '不明',
                    'subtitle' => $setlist->subtitle ?? '',
                    'song_count' => $songCount,
                ];
            }
        }

        usort($setlistLengths, function($a, $b) {
            return $b['song_count'] - $a['song_count'];
        });

        return array_slice($setlistLengths, 0, 5);
    }

    // =====================================
    // アーティスト詳細ページ
    // =====================================

    public function getArtistTopSongs($artistId)
    {
        $artist = Artist::find($artistId);
        if (!$artist) {
            abort(404);
        }

        // そのアーティストのライブのみ取得
        $setlists = Setlist::where('artist_id', $artistId)->get();

        // 通常のカウント（全セットリスト）
        $songPlayCounts = [];
        foreach ($setlists as $setlist) {
            $allSongs = array_merge(
                $setlist->setlist ?? [],
                $setlist->encore ?? [],
                $setlist->fes_setlist ?? [],
                $setlist->fes_encore ?? []
            );

            foreach ($allSongs as $songData) {
                if (isset($songData['song']) && is_numeric($songData['song'])) {
                    $songId = (int)$songData['song'];
                    if (!isset($songPlayCounts[$songId])) {
                        $songPlayCounts[$songId] = 0;
                    }
                    $songPlayCounts[$songId]++;
                }
            }
        }

        arsort($songPlayCounts);

        // 同名ツアーを1回だけカウント
        $songTourCounts = []; // [songId => [tourName1, tourName2, ...]]
        foreach ($setlists as $setlist) {
            $allSongs = array_merge(
                $setlist->setlist ?? [],
                $setlist->encore ?? [],
                $setlist->fes_setlist ?? [],
                $setlist->fes_encore ?? []
            );

            $tourName = $setlist->title ?? 'Unknown';

            foreach ($allSongs as $songData) {
                if (isset($songData['song']) && is_numeric($songData['song'])) {
                    $songId = (int)$songData['song'];

                    if (!isset($songTourCounts[$songId])) {
                        $songTourCounts[$songId] = [];
                    }

                    // 同じツアー名で複数回出ても1カウント
                    if (!in_array($tourName, $songTourCounts[$songId])) {
                        $songTourCounts[$songId][] = $tourName;
                    }
                }
            }
        }

        // ツアー数をカウント
        $songPlayCountsUnique = [];
        foreach ($songTourCounts as $songId => $tours) {
            $songPlayCountsUnique[$songId] = count($tours);
        }

        arsort($songPlayCountsUnique);

        // 全楽曲リスト（聴いた回数順）- 通常
        $allSongs = [];
        foreach ($songPlayCounts as $songId => $count) {
            $setlistSong = SetlistSong::find($songId);
            if ($setlistSong) {
                $allSongs[] = [
                    'song_id' => $songId,
                    'title' => $setlistSong->title,
                    'count' => $count,
                ];
            }
        }

        // 全楽曲リスト（同名ツアー1回カウント）
        $allSongsUnique = [];
        foreach ($songPlayCountsUnique as $songId => $count) {
            $setlistSong = SetlistSong::find($songId);
            if ($setlistSong) {
                $allSongsUnique[] = [
                    'song_id' => $songId,
                    'title' => $setlistSong->title,
                    'count' => $count,
                ];
            }
        }

        // 年別の参加公演数（多い順）
        $yearStats = Setlist::where('artist_id', $artistId)
            ->select('year', DB::raw('count(*) as count'))
            ->whereNotNull('year')
            ->groupBy('year')
            ->orderBy('count', 'desc')
            ->get();

        // 会場別トップ5
        $venueStats = Setlist::where('artist_id', $artistId)
            ->select('venue', DB::raw('count(*) as count'))
            ->whereNotNull('venue')
            ->where('venue', '!=', '')
            ->groupBy('venue')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        // 総参加公演数
        $totalShows = $setlists->count();

        // 総曲数（ユニーク）
        $totalSongs = count($songPlayCounts);

        return view('stats.artist', compact(
            'artist',
            'allSongs',
            'allSongsUnique',
            'yearStats',
            'venueStats',
            'totalShows',
            'totalSongs'
        ));
    }
}
