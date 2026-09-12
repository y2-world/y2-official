<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SlSetlist;
use App\Models\DbSetlist;
use App\Models\DbConcert;
use App\Models\Artist;
use App\Models\DbSong;
use App\Models\DbSingle;
use App\Models\DbAlbum;
use App\Models\SlSong;
use App\Http\Controllers\Concerns\ComputesDbSongStamps;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    use ComputesDbSongStamps;

    // フェスのセットリスト（fes_setlist/fes_encore）は、通常の曲を直接並べた形式（interleaved）と、
    // アーティストごとに曲をまとめたブロック形式（type: 'block', songs: [...]）が混在し得る。
    // ブロック形式の要素はそれ自体に song キーを持たず、ネストされた songs 配列の中に曲がある。
    // 集計処理はすべてこのヘルパーを通し、ブロックを展開した「曲エントリのフラットな配列」を得る。
    private function flattenFesSongs(array $fesItems): array
    {
        $flattened = [];
        foreach ($fesItems as $item) {
            if (($item['type'] ?? 'song') === 'block') {
                foreach ($item['songs'] ?? [] as $song) {
                    $flattened[] = $song + ['artist' => $item['artist'] ?? null];
                }
            } else {
                $flattened[] = $item;
            }
        }
        return $flattened;
    }

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'personal');

        if ($tab === 'database') {
            $artistId = $request->get('artist_id');
            if (!$artistId) {
                $dbArtists = Artist::whereHas('tours')->orderBy('name')->get();
                $tab = 'database';
                return view('stats.database', compact('dbArtists', 'tab'));
            }
            return $this->getDatabaseStats((int)$artistId);
        }

        // Personal stats (参加したライブの履歴)
        $overallStats = $this->getPersonalOverallStats();
        $songStats = $this->getPersonalSongStats();
        $songStatsUnique = $this->getPersonalSongStats(true); // 同名ツアーを1回カウント
        $artistStats = $this->getPersonalArtistStats();
        $venueStats = $this->getPersonalVenueStats();
        $yearStats = $this->getPersonalYearStats();
        $monthStats = $this->getPersonalMonthStats();

        // Stamp BookはDbSong（database側の楽曲マスタ）を台紙にするため、
        // 楽曲が1件も登録されていないアーティストは表示対象から除く
        $artistIdsWithDbSongs = DbSong::select('artist_id')->distinct()->pluck('artist_id')->flip();

        // Live Stamp Bookの下に表示する、アーティストごとのUnique Songs（演奏済み曲数）統計
        $dbSongTotalsByArtist = DbSong::select('artist_id', DB::raw('count(*) as total'))
            ->groupBy('artist_id')
            ->pluck('total', 'artist_id');
        $playedDbSongIdsByArtist = $this->playedDbSongIdsByArtist();
        $stampBookSongStats = collect($artistIdsWithDbSongs->keys())
            ->map(function ($artistId) use ($dbSongTotalsByArtist, $playedDbSongIdsByArtist) {
                $artist = Artist::find($artistId);
                if (!$artist) {
                    return null;
                }
                $doneCount = count($playedDbSongIdsByArtist[$artistId] ?? []);
                $totalCount = $dbSongTotalsByArtist[$artistId] ?? 0;
                return [
                    'id' => $artist->id,
                    'name' => $artist->name,
                    'done_count' => $doneCount,
                    'total_count' => $totalCount,
                    'percentage' => $totalCount > 0 ? round($doneCount / $totalCount * 100, 1) : 0,
                ];
            })
            ->filter()
            ->sortByDesc('percentage')
            ->values();

        $tab = 'personal';

        return view('stats.index', compact(
            'overallStats',
            'songStats',
            'songStatsUnique',
            'artistStats',
            'venueStats',
            'yearStats',
            'monthStats',
            'artistIdsWithDbSongs',
            'stampBookSongStats',
            'tab'
        ));
    }

    // =====================================
    // Personal統計（参加したライブ履歴）
    // =====================================

    private function getPersonalOverallStats()
    {
        $today = now()->toDateString();

        $totalShows = SlSetlist::where('date', '<=', $today)->count();

        // ユニークなアーティスト数（単独ライブ + フェス出演アーティスト）
        $allArtistIds = [];
        $allSetlists = SlSetlist::where('date', '<=', $today)->get();
        foreach ($allSetlists as $setlist) {
            if ($setlist->artist_id) {
                $allArtistIds[$setlist->artist_id] = true;
            }
            if ($setlist->fes == 1) {
                foreach (array_merge($setlist->fes_setlist ?? [], $setlist->fes_encore ?? []) as $songData) {
                    if (isset($songData['artist']) && is_numeric($songData['artist'])) {
                        $allArtistIds[(int)$songData['artist']] = true;
                    }
                }
            }
        }
        $uniqueArtists = count($allArtistIds);

        // 聴いた曲数（setlistsから全曲のユニークID）
        $allSongIds = [];
        foreach ($allSetlists as $setlist) {
            $songs = array_merge(
                $setlist->setlist ?? [],
                $setlist->encore ?? [],
                $this->flattenFesSongs($setlist->fes_setlist ?? []),
                $this->flattenFesSongs($setlist->fes_encore ?? [])
            );
            foreach ($songs as $songData) {
                if (isset($songData['song']) && is_numeric($songData['song'])) {
                    $allSongIds[(int)$songData['song']] = true;
                }
            }
        }
        $uniqueSongs = count($allSongIds);

        // 訪れた会場数
        $uniqueVenues = SlSetlist::where('date', '<=', $today)
            ->whereNotNull('venue')
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
        $today = now()->toDateString();
        $setlists = SlSetlist::where('date', '<=', $today)->get();
        $songPlayCounts = [];

        if ($uniqueTourOnly) {
            // 同名ツアーを1回だけカウント
            $songTourCounts = []; // [songId => [tourName1, tourName2, ...]]

            foreach ($setlists as $setlist) {
                $allSongs = array_merge(
                    $setlist->setlist ?? [],
                    $setlist->encore ?? [],
                    $this->flattenFesSongs($setlist->fes_setlist ?? []),
                    $this->flattenFesSongs($setlist->fes_encore ?? [])
                );

                $tourName = $setlist->title ?? 'Unknown';

                // このセットリスト内で既に登場した曲を記録
                $songsInThisSetlist = [];
                foreach ($allSongs as $songData) {
                    if (isset($songData['song']) && is_numeric($songData['song'])) {
                        $songId = (int)$songData['song'];

                        // このセットリスト内で初めて登場する場合のみ処理
                        if (!in_array($songId, $songsInThisSetlist)) {
                            $songsInThisSetlist[] = $songId;

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
            }

            // ツアー数をカウント
            foreach ($songTourCounts as $songId => $tours) {
                $songPlayCounts[$songId] = count($tours);
            }
        } else {
            // 通常のカウント（1ライブ内で同じ曲が複数回演奏されても1回カウント）
            foreach ($setlists as $setlist) {
                $allSongs = array_merge(
                    $setlist->setlist ?? [],
                    $setlist->encore ?? [],
                    $this->flattenFesSongs($setlist->fes_setlist ?? []),
                    $this->flattenFesSongs($setlist->fes_encore ?? [])
                );

                // このセットリスト内で既に登場した曲を記録
                $songsInThisSetlist = [];
                foreach ($allSongs as $songData) {
                    if (isset($songData['song']) && is_numeric($songData['song'])) {
                        $songId = (int)$songData['song'];

                        // このセットリスト内で初めて登場する場合のみカウント
                        if (!in_array($songId, $songsInThisSetlist)) {
                            $songsInThisSetlist[] = $songId;

                            if (!isset($songPlayCounts[$songId])) {
                                $songPlayCounts[$songId] = 0;
                            }
                            $songPlayCounts[$songId]++;
                        }
                    }
                }
            }
        }

        arsort($songPlayCounts);
        $topSongIds = array_slice(array_keys($songPlayCounts), 0, 10, true);

        $topSongs = [];
        foreach ($topSongIds as $songId) {
            $setlistSong = SlSong::find($songId);
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
        // アーティスト別の参加公演数（単独ライブ + フェス出演）
        $today = now()->toDateString();
        $setlists = SlSetlist::where('date', '<=', $today)->get();
        $artistShowCounts = [];

        foreach ($setlists as $setlist) {
            // 単独ライブ
            if ($setlist->artist_id) {
                $artistId = $setlist->artist_id;
                if (!isset($artistShowCounts[$artistId])) {
                    $artistShowCounts[$artistId] = 0;
                }
                $artistShowCounts[$artistId]++;
            }

            // フェスの場合、出演アーティストごとにカウント
            if ($setlist->fes == 1) {
                $fesArtistIds = [];
                foreach (array_merge($setlist->fes_setlist ?? [], $setlist->fes_encore ?? []) as $songData) {
                    if (isset($songData['artist']) && is_numeric($songData['artist'])) {
                        $fesArtistIds[(int)$songData['artist']] = true;
                    }
                }
                foreach (array_keys($fesArtistIds) as $fesArtistId) {
                    if (!isset($artistShowCounts[$fesArtistId])) {
                        $artistShowCounts[$fesArtistId] = 0;
                    }
                    $artistShowCounts[$fesArtistId]++;
                }
            }
        }

        arsort($artistShowCounts);

        $artistStats = [];
        foreach ($artistShowCounts as $artistId => $count) {
            $artist = Artist::find($artistId);
            if ($artist) {
                $artistStats[] = [
                    'id' => $artist->id,
                    'name' => $artist->name,
                    'show_count' => $count,
                ];
            }
        }

        return collect($artistStats);
    }

    private function getPersonalVenueStats()
    {
        // 最も訪れた会場トップ10
        $today = now()->toDateString();
        $venues = SlSetlist::where('date', '<=', $today)
            ->select('venue', DB::raw('count(*) as count'))
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
        $today = now()->toDateString();
        $yearStats = SlSetlist::where('date', '<=', $today)
            ->select('year', DB::raw('count(*) as count'))
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
        $today = now()->toDateString();
        $monthCounts = SlSetlist::where('date', '<=', $today)
            ->select(DB::raw('MONTH(date) as month'), DB::raw('count(*) as count'))
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
    // Database統計（ツアー情報）
    // =====================================

    private function getDatabaseStats(int $artistId)
    {
        $artist = Artist::findOrFail($artistId);
        $dbArtists = Artist::whereHas('tours')->orderBy('name')->get();

        $overallStats = $this->getDatabaseOverallStats($artistId);
        $songStats = $this->getDatabaseSongStats($artistId);
        $encoreSongStats = $this->getDatabaseEncoreSongStats($artistId);
        $openingSongStats = $this->getDatabaseOpeningSongStats($artistId);
        $longestSetlists = $this->getDatabaseLongestSetlists($artistId);
        $yearStats = $this->getDatabaseYearStats($artistId);

        $tab = 'database';

        return view('stats.database', compact(
            'artist',
            'dbArtists',
            'overallStats',
            'songStats',
            'encoreSongStats',
            'openingSongStats',
            'longestSetlists',
            'yearStats',
            'tab'
        ));
    }

    private function getDatabaseOverallStats(int $artistId)
    {
        $tourIds = DbConcert::where('artist_id', $artistId)->pluck('id');
        $totalTours = $tourIds->count();
        $totalSetlistPatterns = DbSetlist::whereIn('tour_id', $tourIds)->count();
        $totalSongs = DbSong::where('artist_id', $artistId)->count();

        $songArtistIds = DbSong::pluck('artist_id', 'id');
        $crossoverTourIds = DbConcert::whereIn('artist_id', $this->crossoverTourArtistIds($artistId))->pluck('id');
        $uniqueSongIds = [];
        $tourSetlists = DbSetlist::whereIn('tour_id', $crossoverTourIds)->get();
        foreach ($tourSetlists as $setlist) {
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                if (isset($s['song']) && is_numeric($s['song']) && ($songArtistIds[(int)$s['song']] ?? null) === $artistId) {
                    $uniqueSongIds[(int)$s['song']] = true;
                }
            }
        }
        $uniqueSongsInTours = count($uniqueSongIds);

        $totalSongCount = 0;
        $setlistCount = $tourSetlists->count();
        foreach ($tourSetlists as $setlist) {
            $totalSongCount += count(array_merge($setlist->setlist ?? [], $setlist->encore ?? []));
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

    private function getDatabaseSongStats(int $artistId)
    {
        $songArtistIds = DbSong::pluck('artist_id', 'id');
        $tourIds = DbConcert::whereIn('artist_id', $this->crossoverTourArtistIds($artistId))->pluck('id');
        $tourSetlists = DbSetlist::whereIn('tour_id', $tourIds)->get();
        $songTourCounts = [];

        foreach ($tourSetlists as $setlist) {
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                if (isset($s['song']) && is_numeric($s['song']) && ($songArtistIds[(int)$s['song']] ?? null) === $artistId) {
                    $songId = (int)$s['song'];
                    $tourId = $setlist->tour_id;
                    if (!isset($songTourCounts[$songId])) $songTourCounts[$songId] = [];
                    if (!in_array($tourId, $songTourCounts[$songId])) {
                        $songTourCounts[$songId][] = $tourId;
                    }
                }
            }
        }

        $counts = array_map('count', $songTourCounts);
        arsort($counts);
        uksort($counts, fn($a, $b) => $counts[$b] !== $counts[$a] ? $counts[$b] - $counts[$a] : $a - $b);

        $stats = [];
        foreach ($counts as $songId => $count) {
            $song = DbSong::find($songId);
            if ($song) $stats[] = ['song_id' => $songId, 'title' => $song->title, 'count' => $count];
        }

        // ツアーで一度も演奏されていない曲をリストの最後に追加（演奏回数0）
        $unplayedSongs = DbSong::where('artist_id', $artistId)
            ->whereNotIn('id', array_keys($songTourCounts))
            ->orderBy('id')
            ->get();
        foreach ($unplayedSongs as $song) {
            $stats[] = ['song_id' => $song->id, 'title' => $song->title, 'count' => 0];
        }

        return $stats;
    }

    private function getDatabaseYearStats(int $artistId)
    {
        return DbConcert::where('artist_id', $artistId)
            ->select(DB::raw('YEAR(date1) as year'), DB::raw('count(*) as count'))
            ->whereNotNull('date1')
            ->groupBy('year')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
    }

    private function getDatabaseEncoreSongStats(int $artistId)
    {
        $songArtistIds = DbSong::pluck('artist_id', 'id');
        $tourIds = DbConcert::whereIn('artist_id', $this->crossoverTourArtistIds($artistId))->pluck('id');
        $tourSetlists = DbSetlist::whereIn('tour_id', $tourIds)->get();
        $counts = [];

        foreach ($tourSetlists as $setlist) {
            foreach ($setlist->encore ?? [] as $s) {
                if (isset($s['song']) && is_numeric($s['song']) && ($songArtistIds[(int)$s['song']] ?? null) === $artistId) {
                    $songId = (int)$s['song'];
                    $tourId = $setlist->tour_id;
                    if (!isset($counts[$songId])) $counts[$songId] = [];
                    if (!in_array($tourId, $counts[$songId])) $counts[$songId][] = $tourId;
                }
            }
        }

        $counts = array_map('count', $counts);
        arsort($counts);
        uksort($counts, fn($a, $b) => $counts[$b] !== $counts[$a] ? $counts[$b] - $counts[$a] : $a - $b);

        $stats = [];
        foreach (array_slice($counts, 0, 10, true) as $songId => $count) {
            $song = DbSong::find($songId);
            if ($song) $stats[] = ['song_id' => $songId, 'title' => $song->title, 'count' => $count];
        }
        return $stats;
    }

    private function getDatabaseOpeningSongStats(int $artistId)
    {
        $songArtistIds = DbSong::pluck('artist_id', 'id');
        $tourIds = DbConcert::whereIn('artist_id', $this->crossoverTourArtistIds($artistId))->pluck('id');
        $tourSetlists = DbSetlist::whereIn('tour_id', $tourIds)->get();
        $counts = [];

        foreach ($tourSetlists as $setlist) {
            $songs = $setlist->setlist ?? [];
            if (!empty($songs) && isset($songs[0]['song']) && is_numeric($songs[0]['song']) && ($songArtistIds[(int)$songs[0]['song']] ?? null) === $artistId) {
                $songId = (int)$songs[0]['song'];
                $tourId = $setlist->tour_id;
                if (!isset($counts[$songId])) $counts[$songId] = [];
                if (!in_array($tourId, $counts[$songId])) $counts[$songId][] = $tourId;
            }
        }

        $counts = array_map('count', $counts);
        arsort($counts);
        uksort($counts, fn($a, $b) => $counts[$b] !== $counts[$a] ? $counts[$b] - $counts[$a] : $a - $b);

        $stats = [];
        foreach (array_slice($counts, 0, 10, true) as $songId => $count) {
            $song = DbSong::find($songId);
            if ($song) $stats[] = ['song_id' => $songId, 'title' => $song->title, 'count' => $count];
        }
        return $stats;
    }

    private function getDatabaseLongestSetlists(int $artistId)
    {
        // type=2（イベント）・3（ap bank fes）・4（ソロ）は他アーティストとの合同編成や
        // 単独プロジェクトのため、そのアーティスト単独のセットリスト長の比較には含めない
        $tourIds = DbConcert::where('artist_id', $artistId)->whereNotIn('type', [2, 3, 4])->pluck('id');
        $tourSetlists = DbSetlist::whereIn('tour_id', $tourIds)->get();
        $lengths = [];

        foreach ($tourSetlists as $setlist) {
            $count = 0;
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                if (isset($s['song']) && !empty($s['song']) && empty($s['is_daily'])) {
                    $count++;
                }
            }
            if ($count > 0) {
                $tour = DbConcert::find($setlist->tour_id);
                $lengths[] = [
                    'tour_id' => $setlist->tour_id,
                    'tour_title' => $tour ? $tour->title : '不明',
                    'subtitle' => $setlist->subtitle ?? '',
                    'song_count' => $count,
                ];
            }
        }

        usort($lengths, fn($a, $b) => $b['song_count'] - $a['song_count']);
        return array_slice($lengths, 0, 5);
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

        // そのアーティストのライブ + フェスでの出演を取得（未来の公演を除外）
        $today = now()->toDateString();
        $setlists = SlSetlist::where('date', '<=', $today)->get();

        // 通常のカウント（1ライブ内で同じ曲が複数回演奏されても1回カウント）
        $songPlayCounts = [];
        foreach ($setlists as $setlist) {
            $allSongs = [];

            // アーティスト単独ライブの場合
            if ($setlist->artist_id == $artistId) {
                $allSongs = array_merge(
                    $setlist->setlist ?? [],
                    $setlist->encore ?? []
                );
            }
            // フェスの場合は、そのアーティストの曲のみ抽出
            elseif ($setlist->fes == 1) {
                $fesSongs = $this->flattenFesSongs(array_merge($setlist->fes_setlist ?? [], $setlist->fes_encore ?? []));
                foreach ($fesSongs as $songData) {
                    if (isset($songData['artist']) && $songData['artist'] == $artistId) {
                        $allSongs[] = $songData;
                    }
                }
            }

            // このセットリスト内で既に登場した曲を記録
            $songsInThisSetlist = [];
            foreach ($allSongs as $songData) {
                if (isset($songData['song']) && is_numeric($songData['song'])) {

                    $songId = (int)$songData['song'];

                    // このセットリスト内で初めて登場する場合のみカウント
                    if (!in_array($songId, $songsInThisSetlist)) {
                        $songsInThisSetlist[] = $songId;

                        if (!isset($songPlayCounts[$songId])) {
                            $songPlayCounts[$songId] = 0;
                        }
                        $songPlayCounts[$songId]++;
                    }
                }
            }
        }

        arsort($songPlayCounts);

        // 同名ツアーを1回だけカウント
        $songTourCounts = []; // [songId => [tourName1, tourName2, ...]]
        foreach ($setlists as $setlist) {
            $allSongs = [];

            // アーティスト単独ライブの場合
            if ($setlist->artist_id == $artistId) {
                $allSongs = array_merge(
                    $setlist->setlist ?? [],
                    $setlist->encore ?? []
                );
            }

            // フェスの場合は、そのアーティストの曲のみ抽出
            if ($setlist->fes == 1) {
                $fesSongs = $this->flattenFesSongs(array_merge($setlist->fes_setlist ?? [], $setlist->fes_encore ?? []));
                foreach ($fesSongs as $songData) {
                    if (isset($songData['artist']) && (string)$songData['artist'] === (string)$artistId) {
                        $allSongs[] = $songData;
                    }
                }
            }

            $tourName = $setlist->title ?? 'Unknown';

            // このセットリスト内で既に登場した曲を記録
            $songsInThisSetlist = [];
            foreach ($allSongs as $songData) {
                if (isset($songData['song']) && is_numeric($songData['song'])) {
                    // フェスの場合はアーティストチェック
                    if ($setlist->fes == 1 && isset($songData['artist']) && $songData['artist'] != $artistId) {
                        continue;
                    }

                    $songId = (int)$songData['song'];

                    // このセットリスト内で初めて登場する場合のみ処理
                    if (!in_array($songId, $songsInThisSetlist)) {
                        $songsInThisSetlist[] = $songId;

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
            $setlistSong = SlSong::find($songId);
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
            $setlistSong = SlSong::find($songId);
            if ($setlistSong) {
                $allSongsUnique[] = [
                    'song_id' => $songId,
                    'title' => $setlistSong->title,
                    'count' => $count,
                ];
            }
        }

        // そのアーティストに関連するセットリスト（単独ライブ + フェス出演）
        $artistSetlists = $setlists->filter(function ($setlist) use ($artistId) {
            if ($setlist->artist_id == $artistId) {
                return true;
            }
            if ($setlist->fes == 1) {
                foreach (array_merge($setlist->fes_setlist ?? [], $setlist->fes_encore ?? []) as $songData) {
                    if (isset($songData['artist']) && $songData['artist'] == $artistId) {
                        return true;
                    }
                }
            }
            return false;
        });

        // 総参加公演数
        $totalShows = $artistSetlists->count();

        // 年別の参加公演数（多い順）
        $yearStats = $artistSetlists->whereNotNull('year')
            ->groupBy('year')
            ->map(function ($items, $year) {
                return (object)['year' => $year, 'count' => $items->count()];
            })
            ->sortByDesc('count')
            ->values();

        // 会場別トップ5
        $venueStats = $artistSetlists->filter(function ($setlist) {
                return !empty($setlist->venue);
            })
            ->groupBy('venue')
            ->map(function ($items, $venue) {
                return (object)['venue' => $venue, 'count' => $items->count()];
            })
            ->sortByDesc('count')
            ->values()
            ->take(5);

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

    // アーティストのdatabase楽曲カタログを台紙にした「スタンプ帳」。
    // 各曲は、紐付いたsl_songが（演奏したアーティスト名義を問わず）実際にどこかのライブで
    // 演奏された記録（SlSetlist）があれば「済」。ソロ名義でのバンド曲演奏なども実績に含む
    // （曲の詳細ページ SlSongController@show と同じ考え方）。
    public function getStampBook($artistId)
    {
        $artist = Artist::find($artistId);
        if (!$artist) {
            abort(404);
        }

        $today = now()->toDateString();

        // このアーティストのdatabase楽曲IDのうち、実際にライブ演奏された記録があるものを集める
        $playedSlSongIds = [];
        $setlists = SlSetlist::where('date', '<=', $today)->get();

        foreach ($setlists as $setlist) {
            $allSongs = array_merge(
                $setlist->setlist ?? [],
                $setlist->encore ?? [],
                $this->flattenFesSongs($setlist->fes_setlist ?? []),
                $this->flattenFesSongs($setlist->fes_encore ?? [])
            );

            foreach ($allSongs as $songData) {
                if (isset($songData['song']) && is_numeric($songData['song'])) {
                    $playedSlSongIds[(int)$songData['song']] = true;
                }
            }
        }

        $slSongToDbSongId = SlSong::where('artist_id', $artistId)
            ->whereNotNull('db_song_id')
            ->pluck('db_song_id', 'id');

        $playedDbSongIds = [];
        foreach ($slSongToDbSongId as $slSongId => $dbSongId) {
            if (isset($playedSlSongIds[$slSongId])) {
                $playedDbSongIds[(int)$dbSongId] = true;
            }
        }

        // 公式ツアー記録（db_setlists）上、一度でも演奏された曲IDを集める。
        // ここに含まれない曲は「ライブでそもそも未演奏」として台紙自体をグレー表示する。
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

        // ライブでそもそも演奏されたことがある曲だけを分母にした場合の達成率（表示切替用）
        $performedCount = $stamps->where('never_performed', false)->count();
        $performedPercentage = $performedCount > 0 ? round(($doneCount / $performedCount) * 100, 1) : 0;

        return view('stats.stamps', compact(
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
