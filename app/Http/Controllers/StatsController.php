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

    // MySQL/PostgreSQL両対応：日付から年・月を取り出すSQL関数式を接続ドライバに応じて返す。
    // MySQLはYEAR()/MONTH()、PostgreSQLはEXTRACT(... FROM ...)と構文が異なるため。
    private function dateExtractRaw(string $column, string $part): string
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            return "EXTRACT({$part} FROM {$column})";
        }

        $fn = strtoupper($part) === 'YEAR' ? 'YEAR' : 'MONTH';
        return "{$fn}({$column})";
    }

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
        $tab = $request->get('tab', 'database');

        if ($tab === 'database') {
            $artistId = $request->get('artist_id');
            if (!$artistId) {
                return redirect('/database');
            }
            $type = in_array($request->get('type'), ['tours', 'events'], true)
                ? $request->get('type')
                : 'all';
            return $this->getDatabaseStats((int)$artistId, $type);
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
            // 公式の Database に曲が無くても、マイページで作られた同じ名前のアーティストと結び付いていれば、その曲で数えて入れる
            ->concat($this->linkedStampBookSongStats())
            ->sortByDesc('percentage')
            ->values();
        foreach ($stampBookSongStats as $stat) {
            $artistIdsWithDbSongs[$stat['id']] ??= true;
        }

        // トピックス（最近初めて聴いた曲・久しぶりに聴いた曲・自分が聴いた「久しぶり」・初めて聴いた曲の年表）を全アーティスト分まとめる。
        // 参加記録の曲（SlSong）を紐付いたDatabaseの曲（DbSong）に置き換え、その曲のアーティストごとに分ける
        $slToDbSong = SlSong::whereNotNull('db_song_id')->pluck('db_song_id', 'id');
        $dbSongArtist = DbSong::pluck('artist_id', 'id');
        $heardByArtist = [];
        foreach (SlSetlist::where('date', '<=', now()->toDateString())->get() as $setlist) {
            $byArtist = [];
            $items = array_merge($setlist->setlist ?? [], $setlist->encore ?? [],
                $this->flattenFesSongs($setlist->fes_setlist ?? []), $this->flattenFesSongs($setlist->fes_encore ?? []));
            foreach ($items as $songData) {
                $dbSongId = is_numeric($songData['song'] ?? null) ? ($slToDbSong[(int) $songData['song']] ?? null) : null;
                if ($dbSongId && isset($dbSongArtist[$dbSongId])) {
                    $byArtist[$dbSongArtist[$dbSongId]][] = (int) $dbSongId;
                }
            }
            foreach ($byArtist as $artistId => $songIds) {
                $heardByArtist[$artistId][] = ['date' => substr((string) $setlist->date, 0, 10), 'title' => $setlist->title, 'url' => route('setlists.show', $setlist->id), 'song_ids' => $songIds];
            }
        }
        extract(\App\Support\ArtistTopics::combined($heardByArtist));

        $tab = 'personal';

        return view('stats.index', compact(
            'topicHeardRevivals',
            'topicFirstHeard',
            'topicWelcomeBack',
            'topicRecentFirst',
            'topicRecentFirstAll',
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
        // アーティストごとに初めてライブに行った日（ドロップダウンの並びに使う）
        $firstDates = [];
        $markFirst = function ($artistId, $date) use (&$firstDates) {
            $date = substr((string) $date, 0, 10);
            if (!isset($firstDates[$artistId]) || $date < $firstDates[$artistId]) {
                $firstDates[$artistId] = $date;
            }
        };

        foreach ($setlists as $setlist) {
            // 単独ライブ
            if ($setlist->artist_id) {
                $artistId = $setlist->artist_id;
                if (!isset($artistShowCounts[$artistId])) {
                    $artistShowCounts[$artistId] = 0;
                }
                $artistShowCounts[$artistId]++;
                $markFirst($artistId, $setlist->date);
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
                    $markFirst($fesArtistId, $setlist->date);
                }
            }
        }

        arsort($artistShowCounts);

        $artistStats = [];
        foreach ($artistShowCounts as $artistId => $count) {
            $artist = Artist::find($artistId);
            // 公開しているアーティストだけ（ランキング・タブの切り替えの両方）
            // マイページで作られた同じ名前のアーティストと結び付いていれば、非公開でも出す（そのデータで stats・スタンプ帳を見せる）
            if ($artist && ((int) $artist->visible === 1 || \App\Support\ArtistLink::userArtistFor($artist))) {
                $artistStats[] = [
                    'id' => $artist->id,
                    'name' => $artist->name,
                    'show_count' => $count,
                    'first_date' => $firstDates[$artistId] ?? null,
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
            ->select(DB::raw($this->dateExtractRaw('date', 'MONTH') . ' as month'), DB::raw('count(*) as count'))
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

    private function getDatabaseStats(int $artistId, string $type = 'all')
    {
        $artist = Artist::findOrFail($artistId);
        $overallStats = $this->getDatabaseOverallStats($artistId, $type);
        $songStats = $this->getDatabaseSongStats($artistId, $type);
        $encoreSongStats = $this->getDatabaseEncoreSongStats($artistId, $type);
        // 福山雅治のみ：DOUBLE ENCORE（弾き語り）を除いた版も用意して、画面上のチェックでその場で切り替える
        $isHikigatariArtist = $artistId === \App\Support\EncoreBlocks::HIKIGATARI_ARTIST_ID;
        $songStatsNoDoubleEncore = $isHikigatariArtist ? $this->getDatabaseSongStats($artistId, $type, true) : [];
        // トピックス（久しぶりに演奏された曲など）
        $topics = new \App\Support\ArtistTopics($artistId);
        // Long-Awaited Returns も50曲まで（最初の10曲だけ見せて、残りは折りたたむ）
        $topicRevivals = $topics->revivals(50);
        // Long Time No Play は50曲まで（最初の10曲だけ見せて、残りは折りたたむ）
        $topicDormant = $topics->dormant(1, false, 50);
        $topicDormantSingles = $topics->dormant(1, true, 50);
        $topicLateDebuts = $topics->lateDebuts();
        $topicClosingSongs = array_slice($topics->closingSongs(), 0, 10);
        $encoreSongStatsNoDoubleEncore = $isHikigatariArtist ? $this->getDatabaseEncoreSongStats($artistId, $type, true) : [];
        $doubleEncoreSongStats = $isHikigatariArtist ? $this->getDatabaseDoubleEncoreSongStats($artistId, $type) : [];
        $openingSongStats = $this->getDatabaseOpeningSongStats($artistId, $type);
        $longestSetlists = $this->getDatabaseLongestSetlists($artistId, $type);
        $yearStats = $this->getDatabaseYearStats($artistId, $type);

        // タブの「Artists」：セットリストが登録されているアーティストのDatabaseのstatsに切り替える（今のアーティストを選んだ状態）
        $tourArtistIds = DbConcert::whereIn('id', DbSetlist::distinct()->pluck('tour_id'))->distinct()->pluck('artist_id');
        $tabArtists = \App\Support\JapaneseNameSorter::sortBy(Artist::whereIn('id', $tourArtistIds)->where('visible', 1)->get(), 'name')
            ->map(fn ($a) => ['name' => $a->name, 'url' => route('stats.index', ['tab' => 'database', 'artist_id' => $a->id]), 'current' => (int) $a->id === $artistId])
            ->values()->all();

        // Yuki が参加したライブの stats（Setlists）があるか（単独ライブかフェスで参加していれば）
        $hasPersonalStats = $this->getPersonalArtistStats()->contains(fn ($a) => (int) data_get($a, 'id') === $artistId);

        // 参加記録の stats へのリンク（未ログインなら出さない）。Yuki 本人なら Yuki の stats（Yuki's Stats）、
        // それ以外のログイン中のユーザーなら、そのアーティストの参加記録があるときだけ自分の stats（My Statistics）
        $externalUser = \Illuminate\Support\Facades\Auth::guard('external')->user();
        $personalStatsLink = null;
        if (!$externalUser) {
            $personalStatsLink = null;
        } elseif (!$externalUser->is_yuki) {
            $attended = $externalUser->attendances()->whereHas('dbSetlist.tour', fn ($q) => $q->where('artist_id', $artistId))->exists();
            if ($attended) {
                $personalStatsLink = ['url' => route('mypage.stats.artist', 'official-' . $artistId), 'label' => 'My Statistics'];
            }
        } elseif ($hasPersonalStats) {
            $personalStatsLink = ['url' => route('stats.artist', $artistId), 'label' => "Yuki's Stats"];
        }

        return view('stats.database', compact(
            'personalStatsLink',
            'tabArtists',
            'artist',
            'overallStats',
            'songStats',
            'encoreSongStats',
            'doubleEncoreSongStats',
            'isHikigatariArtist',
            'songStatsNoDoubleEncore',
            'encoreSongStatsNoDoubleEncore',
            'topicRevivals',
            'topicDormant',
            'topicDormantSingles',
            'topicLateDebuts',
            'topicClosingSongs',
            'openingSongStats',
            'longestSetlists',
            'yearStats'
        ));
    }

    private function getDatabaseOverallStats(int $artistId, string $type = 'all')
    {
        $tourIds = $this->databaseConcertIds([$artistId], $type);
        $totalTours = $tourIds->count();
        $totalSetlistPatterns = DbSetlist::whereIn('tour_id', $tourIds)->count();
        $totalSongs = DbSong::where('artist_id', $artistId)->count();

        $songArtistIds = DbSong::pluck('artist_id', 'id');
        $crossoverTourIds = $this->databaseConcertIds($this->crossoverTourArtistIds($artistId), $type);
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
            // メドレーは1曲、日替わりの候補は1か所につき1曲として数える
            $totalSongCount += countActualSongs($setlist->setlist ?? []) + countActualSongs($setlist->encore ?? []);
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

    // DOUBLE ENCOREを除くときは、アンコールのうちENCORE 1だけを数える
    private function statsEncore($setlist, bool $excludeDoubleEncore): array
    {
        $encore = array_values((array) ($setlist->encore ?? []));

        return $excludeDoubleEncore ? \App\Support\EncoreBlocks::withoutDoubleEncore($encore) : $encore;
    }

    private function getDatabaseSongStats(int $artistId, string $type = 'all', bool $excludeDoubleEncore = false)
    {
        $songArtistIds = DbSong::pluck('artist_id', 'id');
        $tourIds = $this->databaseConcertIds($this->crossoverTourArtistIds($artistId), $type);
        $tourSetlists = DbSetlist::whereIn('tour_id', $tourIds)->get();
        $songTourCounts = [];

        foreach ($tourSetlists as $setlist) {
            foreach (array_merge($setlist->setlist ?? [], $this->statsEncore($setlist, $excludeDoubleEncore)) as $s) {
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

    private function getDatabaseYearStats(int $artistId, string $type = 'all')
    {
        $query = DbConcert::where('artist_id', $artistId);
        $this->applyDatabaseTypeFilter($query, $type);

        return $query
            ->select(DB::raw($this->dateExtractRaw('date1', 'YEAR') . ' as year'), DB::raw('count(*) as count'))
            ->whereNotNull('date1')
            ->groupBy('year')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
    }

    // 福山雅治のDOUBLE ENCORE（弾き語り）で演奏された曲。数え方は他のランキングと同じく、演奏されたツアーの数
    private function getDatabaseDoubleEncoreSongStats(int $artistId, string $type = 'all')
    {
        $songArtistIds = DbSong::pluck('artist_id', 'id');
        $tourIds = $this->databaseConcertIds([$artistId], $type);
        $counts = [];
        foreach (DbSetlist::whereIn('tour_id', $tourIds)->get() as $setlist) {
            foreach (\App\Support\EncoreBlocks::doubleEncore((array) ($setlist->encore ?? [])) as $s) {
                if (isset($s['song']) && is_numeric($s['song']) && ($songArtistIds[(int)$s['song']] ?? null) === $artistId) {
                    $counts[(int)$s['song']][$setlist->tour_id] = true;
                }
            }
        }
        $counts = array_map('count', $counts);
        uksort($counts, fn($a, $b) => $counts[$b] !== $counts[$a] ? $counts[$b] - $counts[$a] : $a - $b);

        $titles = DbSong::whereIn('id', array_keys($counts))->pluck('title', 'id');
        $stats = [];
        foreach ($counts as $songId => $count) {
            if (isset($titles[$songId])) $stats[] = ['song_id' => $songId, 'title' => $titles[$songId], 'count' => $count];
        }
        return $stats;
    }

    private function getDatabaseEncoreSongStats(int $artistId, string $type = 'all', bool $excludeDoubleEncore = false)
    {
        $songArtistIds = DbSong::pluck('artist_id', 'id');
        $tourIds = $this->databaseConcertIds($this->crossoverTourArtistIds($artistId), $type);
        $tourSetlists = DbSetlist::whereIn('tour_id', $tourIds)->get();
        $counts = [];

        foreach ($tourSetlists as $setlist) {
            foreach ($this->statsEncore($setlist, $excludeDoubleEncore) as $s) {
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

    private function getDatabaseOpeningSongStats(int $artistId, string $type = 'all')
    {
        $songArtistIds = DbSong::pluck('artist_id', 'id');
        $tourIds = $this->databaseConcertIds($this->crossoverTourArtistIds($artistId), $type);
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

    private function getDatabaseLongestSetlists(int $artistId, string $type = 'all')
    {
        // type=2（イベント）・3（ap bank fes）・4（ソロ）は他アーティストとの合同編成や
        // 単独プロジェクトのため、そのアーティスト単独のセットリスト長の比較には含めない
        $query = DbConcert::where('artist_id', $artistId)->whereNotIn('type', [2, 3, 4]);
        $this->applyDatabaseTypeFilter($query, $type);
        $tourIds = $query->pluck('id');
        $tourSetlists = DbSetlist::whereIn('tour_id', $tourIds)->get();
        $lengths = [];

        foreach ($tourSetlists as $setlist) {
            // メドレーは1曲、日替わりの候補は1か所につき1曲として数える
            $count = countActualSongs($setlist->setlist ?? []) + countActualSongs($setlist->encore ?? []);
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

    private function databaseConcertIds(array $artistIds, string $type): \Illuminate\Support\Collection
    {
        $query = DbConcert::whereIn('artist_id', $artistIds);
        $this->applyDatabaseTypeFilter($query, $type);
        return $query->pluck('id');
    }

    private function applyDatabaseTypeFilter($query, string $type): void
    {
        if ($type === 'tours') {
            $query->whereIn('type', [0, 1]);
        } elseif ($type === 'events') {
            $query->where('type', 2);
        }
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
            // 回数の多い順（同じ回数なら新しい年から）
            ->sortKeysDesc()
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

        // 福山雅治のDOUBLE ENCORE（弾き語り）：除いて数えた版と、DOUBLE ENCOREで聴いた曲のランキング
        $isHikigatariArtist = (int) $artistId === \App\Support\EncoreBlocks::HIKIGATARI_ARTIST_ID;
        $allSongsNoDoubleEncore = $isHikigatariArtist ? $this->listenedSongStats($setlists, (int) $artistId, false, 'exclude') : [];
        $allSongsUniqueNoDoubleEncore = $isHikigatariArtist ? $this->listenedSongStats($setlists, (int) $artistId, true, 'exclude') : [];
        $doubleEncoreSongs = $isHikigatariArtist ? $this->listenedSongStats($setlists, (int) $artistId, false, 'only') : [];

        // トピックス（最近初めて聴いた曲・久しぶりに聴いた曲・自分が聴いた「久しぶり」・初めて聴いた曲の年表）。
        // 参加記録の曲（SlSong）を、紐付いたDatabaseの曲（DbSong）に置き換えて使う。
        // 公式の Database に曲が無く、マイページで作られた同じ名前のアーティストと結び付いていれば、曲名でそのアーティストの曲に置き換える
        $topicUserArtist = DbSong::where('artist_id', (int) $artistId)->exists() ? null : \App\Support\ArtistLink::userArtistFor($artist);
        if ($topicUserArtist) {
            $userSongIdsByTitle = \App\Models\UserSong::where('user_artist_id', $topicUserArtist->id)->get()
                ->mapWithKeys(fn ($song) => [\App\Support\ArtistLink::normalize($song->title) => $song->id]);
            $slToDbSong = SlSong::where('artist_id', (int) $artistId)->get()
                ->mapWithKeys(fn ($slSong) => [$slSong->id => $userSongIdsByTitle[\App\Support\ArtistLink::normalize($slSong->title)] ?? null])
                ->filter();
        } else {
            $slToDbSong = SlSong::whereNotNull('db_song_id')->pluck('db_song_id', 'id');
        }
        $heard = [];
        foreach ($setlists as $setlist) {
            $items = [];
            if ($setlist->artist_id == $artistId) {
                $items = array_merge($setlist->setlist ?? [], $setlist->encore ?? []);
            } elseif ($setlist->fes == 1) {
                $items = array_filter($this->flattenFesSongs(array_merge($setlist->fes_setlist ?? [], $setlist->fes_encore ?? [])),
                    fn ($songData) => isset($songData['artist']) && (string) $songData['artist'] === (string) $artistId);
            }
            $songIds = [];
            foreach ($items as $songData) {
                if (is_numeric($songData['song'] ?? null) && isset($slToDbSong[(int) $songData['song']])) {
                    $songIds[] = (int) $slToDbSong[(int) $songData['song']];
                }
            }
            if ($songIds) {
                $heard[] = ['date' => substr((string) $setlist->date, 0, 10), 'title' => $setlist->title, 'url' => route('setlists.show', $setlist->id), 'song_ids' => $songIds];
            }
        }
        $topics = $topicUserArtist ? new \App\Support\ArtistTopics($topicUserArtist->id, true) : new \App\Support\ArtistTopics((int) $artistId);
        // 曲のリンクは、2つ目のタブ（ログインしている人で決まる。Yuki なら Yuki's Live Attendances）で開く（?tab=mine）。
        // 結び付いたアーティストの曲・ライブは、マイページのページへ
        $topicSongUrl = $topicUserArtist
            ? fn ($songId) => route('mypage.user_songs.show', ['id' => $songId, 'tab' => 'mine'])
            : fn ($songId) => route('songs.show', ['id' => $songId, 'tab' => 'mine']);
        $topicTourUrl = $topicUserArtist ? fn ($tourId) => route('mypage.user_concerts.show', $tourId) : null;
        $topicHeardRevivals = $topics->heardRevivals($heard);
        $topicFirstHeard = $topics->firstHeardTimeline($heard);
        $topicWelcomeBack = $topics->welcomeBack($heard);
        $topicRecentFirst = $topics->recentFirstListens($heard);
        $topicRecentFirstAll = $topics->recentFirstListens($heard, false);

        // タブの「Artists」：参加したアーティストに切り替える（今のアーティストを選んだ状態）
        // 初めてライブに行った順
        $tabArtists = collect($this->getPersonalArtistStats())
            ->sortBy('first_date')
            ->map(fn ($a) => ['name' => $a['name'], 'url' => route('stats.artist', $a['id']), 'current' => (int) $a['id'] === (int) $artistId])
            ->values()->all();

        // 曲が登録されていないアーティストはスタンプ帳が作れないので、ボタンを出さない。
        // 公式の Database に曲が無くても、マイページで作られた同じ名前のアーティストに曲があれば、その曲でスタンプ帳・stats を出す
        $linkedUserArtist = DbSong::where('artist_id', (int) $artistId)->exists() ? null : \App\Support\ArtistLink::userArtistFor($artist);
        $hasStampBook = DbSong::where('artist_id', (int) $artistId)->exists() || ($linkedUserArtist && $linkedUserArtist->songs()->exists());
        // このアーティストの Database（公式の演奏記録）の stats があるか（曲が1件でもあれば。セットリストは曲が無いと登録できない）
        $hasDatabaseStats = $hasStampBook;
        $databaseStatsUrl = $linkedUserArtist
            ? route('mypage.user_artists.stats', $linkedUserArtist->id)
            : route('stats.index', ['tab' => 'database', 'artist_id' => $artist->id]);

        return view('stats.artist', compact(
            'topicSongUrl',
            'topicTourUrl',
            'hasStampBook',
            'hasDatabaseStats',
            'databaseStatsUrl',
            'tabArtists',
            'artist',
            'allSongs',
            'allSongsUnique',
            'isHikigatariArtist',
            'topicHeardRevivals',
            'topicFirstHeard',
            'topicWelcomeBack',
            'topicRecentFirst',
            'topicRecentFirstAll',
            'allSongsNoDoubleEncore',
            'allSongsUniqueNoDoubleEncore',
            'doubleEncoreSongs',
            'yearStats',
            'venueStats',
            'totalShows',
            'totalSongs'
        ));
    }

    // Most Listened Songs の集計（getArtistTopSongsの通常／同名ツアー1回カウントと同じ数え方）に、
    // DOUBLE ENCOREの扱いを加えたもの。$doubleEncore: 'exclude' = DOUBLE ENCOREを除く / 'only' = DOUBLE ENCOREだけ。
    // フェスの出演にはDOUBLE ENCOREが無いので、'exclude' ではそのまま数え、'only' では数えない
    private function listenedSongStats($setlists, int $artistId, bool $uniqueTour, string $doubleEncore): array
    {
        $counts = [];
        foreach ($setlists as $setlist) {
            $songs = [];
            if ($setlist->artist_id == $artistId) {
                $encore = array_values((array) ($setlist->encore ?? []));
                $songs = $doubleEncore === 'only'
                    ? \App\Support\EncoreBlocks::doubleEncore($encore)
                    : array_merge($setlist->setlist ?? [], \App\Support\EncoreBlocks::withoutDoubleEncore($encore));
            } elseif ($setlist->fes == 1 && $doubleEncore !== 'only') {
                foreach ($this->flattenFesSongs(array_merge($setlist->fes_setlist ?? [], $setlist->fes_encore ?? [])) as $songData) {
                    if (isset($songData['artist']) && (string) $songData['artist'] === (string) $artistId) {
                        $songs[] = $songData;
                    }
                }
            }
            $key = $uniqueTour ? ($setlist->title ?? 'Unknown') : 'setlist-' . $setlist->id;
            foreach ($songs as $songData) {
                if (isset($songData['song']) && is_numeric($songData['song'])) {
                    $counts[(int) $songData['song']][$key] = true;
                }
            }
        }
        $counts = array_map('count', $counts);
        arsort($counts);

        $titles = SlSong::whereIn('id', array_keys($counts))->pluck('title', 'id');
        $stats = [];
        foreach ($counts as $songId => $count) {
            if (isset($titles[$songId])) {
                $stats[] = ['song_id' => $songId, 'title' => $titles[$songId], 'count' => $count];
            }
        }

        return $stats;
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

        // このアーティストのdatabase楽曲IDのうち、実際にライブ演奏された記録があるものを集める。
        // 通常セットリスト（setlist/encore）とフェス（fes_setlist/fes_encore）を別集計にし、
        // フェスでしか演奏されていない曲は台紙上で区別できるようにする
        $playedSlSongIdsNormal = [];
        $playedSlSongIdsFes = [];
        $setlists = SlSetlist::where('date', '<=', $today)->get();

        // 福山雅治のDOUBLE ENCORE（2つ目以降のアンコール）は弾き語りなので、そこでしか聴いていない曲は
        // 台紙上でギター柄のスタンプにする。DOUBLE ENCORE以外（本編・1つ目のアンコール・フェス）で聴いたかも別に集める
        $playedSlSongIdsDoubleEncore = [];
        $playedSlSongIdsOutsideDoubleEncore = [];
        foreach ($setlists as $setlist) {
            $encore = array_values((array) ($setlist->encore ?? []));
            $encoreBlocks = \App\Support\EncoreBlocks::blockIndexes($encore);
            foreach (array_merge($setlist->setlist ?? [], $encore) as $position => $songData) {
                if (isset($songData['song']) && is_numeric($songData['song'])) {
                    $playedSlSongIdsNormal[(int)$songData['song']] = true;
                    $encoreIndex = $position - count($setlist->setlist ?? []);
                    if ($encoreIndex >= 0 && ($encoreBlocks[$encoreIndex] ?? 0) >= \App\Support\EncoreBlocks::DOUBLE_ENCORE) {
                        $playedSlSongIdsDoubleEncore[(int)$songData['song']] = true;
                    } else {
                        $playedSlSongIdsOutsideDoubleEncore[(int)$songData['song']] = true;
                    }
                }
            }

            $fesSongs = $this->flattenFesSongs(array_merge($setlist->fes_setlist ?? [], $setlist->fes_encore ?? []));
            foreach ($fesSongs as $songData) {
                if (isset($songData['song']) && is_numeric($songData['song'])) {
                    $playedSlSongIdsFes[(int)$songData['song']] = true;
                    $playedSlSongIdsOutsideDoubleEncore[(int)$songData['song']] = true;
                }
            }
        }

        $slSongToDbSongId = SlSong::where('artist_id', $artistId)
            ->whereNotNull('db_song_id')
            ->pluck('db_song_id', 'id');

        $playedDbSongIds = [];
        $fesOnlyDbSongIds = [];
        $hikigatariDbSongIds = [];
        foreach ($slSongToDbSongId as $slSongId => $dbSongId) {
            $playedNormal = isset($playedSlSongIdsNormal[$slSongId]);
            $playedFes = isset($playedSlSongIdsFes[$slSongId]);
            if ($playedNormal || $playedFes) {
                $playedDbSongIds[(int)$dbSongId] = true;
            }
            if ($playedFes && !$playedNormal) {
                $fesOnlyDbSongIds[(int)$dbSongId] = true;
            }
            if ((int) $artistId === \App\Support\EncoreBlocks::HIKIGATARI_ARTIST_ID && isset($playedSlSongIdsDoubleEncore[$slSongId])) {
                $hikigatariDbSongIds[(int)$dbSongId] = ($hikigatariDbSongIds[(int)$dbSongId] ?? true) && !isset($playedSlSongIdsOutsideDoubleEncore[$slSongId]);
            } elseif ($playedNormal || $playedFes) {
                // 同じ曲に紐づく別のSlSongでDOUBLE ENCORE以外で聴いていれば、弾き語りのみではない
                $hikigatariDbSongIds[(int)$dbSongId] = false;
            }
        }

        // 公式ツアー記録（db_setlists）上、一度でも演奏された曲IDを集める。
        // ここに含まれない曲は「ライブでそもそも未演奏」として台紙自体をグレー表示する。
        $everPerformedDbSongIds = $this->everPerformedDbSongIds((int)$artistId);

        if (!DbSong::where('artist_id', $artistId)->exists() && ($linkedUserArtist = \App\Support\ArtistLink::userArtistFor($artist))) {
            return $this->linkedUserArtistStampBook($artist, $linkedUserArtist, $playedSlSongIdsNormal, $playedSlSongIdsFes);
        }

        $stampFilters = $this->stampDiscographyFilters((int)$artistId);

        $dbSongs = DbSong::where('artist_id', $artistId)->orderBy('sort_order')->get();
        $stamps = $dbSongs->map(function (DbSong $song) use ($playedDbSongIds, $everPerformedDbSongIds, $fesOnlyDbSongIds, $hikigatariDbSongIds, $stampFilters) {
            return [
                'song_id' => $song->id,
                'title' => $song->title,
                // ほかの人との曲のアーティスト表記（スタンプでは曲名のあとに灰色で出す）
                'credit' => $song->credit,
                'done' => isset($playedDbSongIds[$song->id]),
                'never_performed' => !isset($everPerformedDbSongIds[$song->id]),
                'fes_only' => isset($fesOnlyDbSongIds[$song->id]),
                'hikigatari_only' => !empty($hikigatariDbSongIds[$song->id]),
                'filter_keys' => $this->stampFilterKeys($song->id, $stampFilters),
                'track_titles' => $this->stampTrackTitles($song->id, $stampFilters),
                'track_orders' => $this->stampTrackOrders($song->id, $stampFilters),
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
            'performedPercentage',
            'stampFilters'
        ));
    }

    // トップの Stamps タブ用：公式の Database に曲が無く、マイページで作られた同じ名前のアーティストと結び付いた参加アーティストの、
    // 聴いた曲の数（setlists で聴いた曲を曲名でユーザーの曲に結び付ける）と全曲数
    private function linkedStampBookSongStats(): array
    {
        $artistIdsWithDbSongs = DbSong::select('artist_id')->distinct()->pluck('artist_id')->flip();
        $playedSlSongIds = [];
        foreach (SlSetlist::where('date', '<=', now()->toDateString())->get() as $setlist) {
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? [], $this->flattenFesSongs($setlist->fes_setlist ?? []), $this->flattenFesSongs($setlist->fes_encore ?? [])) as $songData) {
                if (is_numeric($songData['song'] ?? null)) {
                    $playedSlSongIds[(int) $songData['song']] = true;
                }
            }
        }
        $stats = [];
        foreach ($this->getPersonalArtistStats() as $artistStat) {
            $artist = Artist::find($artistStat['id']);
            if (!$artist || isset($artistIdsWithDbSongs[$artist->id]) || !($userArtist = \App\Support\ArtistLink::userArtistFor($artist))) {
                continue;
            }
            $userTitles = \App\Models\UserSong::where('user_artist_id', $userArtist->id)->pluck('title')->map(fn ($t) => \App\Support\ArtistLink::normalize($t))->flip();
            if ($userTitles->isEmpty()) {
                continue;
            }
            $heard = SlSong::where('artist_id', $artist->id)->get()
                ->filter(fn ($slSong) => isset($playedSlSongIds[$slSong->id]) && isset($userTitles[\App\Support\ArtistLink::normalize($slSong->title)]))
                ->map(fn ($slSong) => \App\Support\ArtistLink::normalize($slSong->title))->unique()->count();
            $total = $userTitles->count();
            $stats[] = ['id' => $artist->id, 'name' => $artist->name, 'done_count' => $heard, 'total_count' => $total, 'percentage' => $total > 0 ? round($heard / $total * 100, 1) : 0];
        }

        return $stats;
    }

    // 公式の Database に曲が無いアーティストのスタンプ帳を、マイページで作られた同じ名前のアーティストの曲で作る。
    // setlists で聴いた曲（SlSong）は曲名でユーザーの曲に結び付ける。演奏されたことがあるかは、そのアーティストの登録ライブで見る
    private function linkedUserArtistStampBook(Artist $artist, \App\Models\UserArtist $userArtist, array $playedNormal, array $playedFes)
    {
        $norm = fn ($title) => \App\Support\ArtistLink::normalize((string) $title);
        $slTitles = SlSong::where('artist_id', $artist->id)->pluck('title', 'id');
        $heardNormal = [];
        $heardFes = [];
        foreach ($slTitles as $slSongId => $title) {
            if (isset($playedNormal[$slSongId])) {
                $heardNormal[$norm($title)] = true;
            }
            if (isset($playedFes[$slSongId])) {
                $heardFes[$norm($title)] = true;
            }
        }

        $everPerformed = $this->everPerformedUserSongIds($userArtist->id);
        $stampFilters = $this->stampDiscographyFilters($userArtist->id, true);
        $stamps = \App\Models\UserSong::where('user_artist_id', $userArtist->id)->orderBy('sort_order')->get()
            ->map(function ($song) use ($norm, $heardNormal, $heardFes, $everPerformed, $stampFilters) {
                $key = $norm($song->title);
                $done = isset($heardNormal[$key]) || isset($heardFes[$key]);
                return [
                    'song_id' => $song->id,
                    // マイページの曲のページを、2つ目のタブ（自分の参加記録）で開く
                    'song_url' => route('mypage.user_songs.show', ['id' => $song->id, 'tab' => 'mine']),
                    'title' => $song->title,
                    'done' => $done,
                    // 聴いた曲は、ライブで演奏されたことがある
                    'never_performed' => !$done && !isset($everPerformed[$song->id]),
                    'fes_only' => isset($heardFes[$key]) && !isset($heardNormal[$key]),
                    'hikigatari_only' => false,
                    'filter_keys' => $this->stampFilterKeys($song->id, $stampFilters),
                    'track_titles' => $this->stampTrackTitles($song->id, $stampFilters),
                    'track_orders' => $this->stampTrackOrders($song->id, $stampFilters),
                ];
            });

        $totalCount = $stamps->count();
        $doneCount = $stamps->where('done', true)->count();
        $percentage = $totalCount > 0 ? round(($doneCount / $totalCount) * 100, 1) : 0;
        $performedCount = $stamps->where('never_performed', false)->count();
        $performedPercentage = $performedCount > 0 ? round(($doneCount / $performedCount) * 100, 1) : 0;

        return view('stats.stamps', compact('artist', 'stamps', 'totalCount', 'doneCount', 'percentage', 'performedCount', 'performedPercentage', 'stampFilters'));
    }
}
