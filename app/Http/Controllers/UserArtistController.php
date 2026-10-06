<?php

namespace App\Http\Controllers;

use App\Models\UserArtist;
use App\Support\JapaneseNameSorter;

/**
 * ユーザー登録アーティストの「Database」的な入り口（誰でも閲覧できる）。
 * Timelineの「Users' Database」から遷移し、ここからアーティストごとの
 * ツアー一覧（UserConcertController::index）→セットリスト詳細（::show）へ辿る。
 */
class UserArtistController extends Controller
{
    public function index()
    {
        $artists = JapaneseNameSorter::sortBy(
            UserArtist::withCount(['concerts', 'songs'])->get()
        );

        return view('mypage.user_artists.index', compact('artists'));
    }

    // アーティストのトップ（Databaseのアーティストのトップ database/artist と同じ形）。Live・Discography への入口
    public function show(int $artistId)
    {
        $artist = UserArtist::withCount(['concerts', 'songs'])->findOrFail($artistId);

        return view('mypage.user_artists.show', compact('artist'));
    }

    public function stats(int $artistId)
    {
        $artist = UserArtist::with(['songs', 'concerts.setlists'])->findOrFail($artistId);
        $songTitles = $artist->songs->keyBy('id')->map(fn ($song) => $song->title);
        $songCounts = [];
        $encoreCounts = [];
        $openingCounts = [];
        $longestSetlists = [];
        $performedSongIds = [];
        $yearCounts = [];
        $setlistCount = 0;
        $songEntryCount = 0;

        foreach ($artist->concerts as $concert) {
            $year = $concert->date1 ? substr((string) $concert->date1, 0, 4) : null;
            if ($year && ctype_digit($year)) {
                $yearCounts[$year] = ($yearCounts[$year] ?? 0) + 1;
            }

            foreach ($concert->setlists as $setlist) {
                $setlistCount++;
                $mainSongs = $this->songIds($setlist->setlist ?? []);
                $encoreSongs = $this->songIds($setlist->encore ?? []);
                $allSongs = array_merge($mainSongs, $encoreSongs);
                // メドレーは1曲、日替わりの候補は1か所につき1曲として数える
                $songEntryCount += countActualSongs($setlist->setlist ?? []) + countActualSongs($setlist->encore ?? []);

                foreach (array_unique($allSongs) as $songId) {
                    $performedSongIds[$songId] = true;
                    $songCounts[$songId][$concert->id] = true;
                }
                foreach (array_unique($encoreSongs) as $songId) {
                    $encoreCounts[$songId][$concert->id] = true;
                }
                if (isset($mainSongs[0])) {
                    $openingId = $mainSongs[0];
                    $openingCounts[$openingId][$concert->id] = true;
                }

                if (!in_array((int) $concert->type, [2, 3, 4], true)) {
                    $longestSetlists[] = [
                        'concert' => $concert,
                        'setlist' => $setlist,
                        'song_count' => countActualSongs($setlist->setlist ?? []) + countActualSongs($setlist->encore ?? []),
                    ];
                }
            }
        }

        $makeSongStats = function (array $counts) use ($songTitles) {
            foreach ($counts as $id => $concertIds) {
                $counts[$id] = count($concertIds);
            }
            return collect($counts)->map(fn ($count, $id) => [
                'id' => (int) $id,
                'title' => $songTitles[$id] ?? '不明な曲',
                'count' => $count,
            ])->sort(function ($a, $b) {
                return $b['count'] <=> $a['count'] ?: $a['id'] <=> $b['id'];
            })->values();
        };

        usort($longestSetlists, fn ($a, $b) => $b['song_count'] <=> $a['song_count']);

        $overallStats = [
            'total_concerts' => $artist->concerts->count(),
            'total_songs' => $artist->songs->count(),
            'unique_songs_played' => count($performedSongIds),
            'avg_setlist_length' => $setlistCount ? round($songEntryCount / $setlistCount, 1) : 0,
        ];
        foreach ($artist->songs as $song) {
            $songCounts[$song->id] ??= [];
        }
        $songStats = $makeSongStats($songCounts);
        $encoreSongStats = $makeSongStats($encoreCounts)->take(10)->values();
        $openingSongStats = $makeSongStats($openingCounts)->take(10)->values();
        $longestSetlists = array_slice(array_values(array_filter($longestSetlists, fn ($entry) => $entry['song_count'] > 0)), 0, 5);
        arsort($yearCounts);
        $yearStats = collect($yearCounts)->take(10)->map(fn ($count, $year) => (object) ['year' => $year, 'count' => $count])->values();

        // 自分のスタンプ帳・stats へのリンクは、このアーティストの参加記録があるときだけ出す
        $hasAttended = (bool) \Illuminate\Support\Facades\Auth::guard('external')->user()?->attendances()
            ->whereHas('userSetlist.concert', fn ($q) => $q->where('user_artist_id', $artist->id))
            ->exists();

        return view('mypage.user_artists.stats', compact(
            'hasAttended',
            'artist', 'overallStats', 'songStats', 'encoreSongStats', 'openingSongStats', 'longestSetlists', 'yearStats'
        ));
    }

    private function songIds(array $items): array
    {
        $ids = [];
        foreach ($items as $item) {
            if (isset($item['song']) && is_numeric($item['song'])) {
                $ids[] = (int) $item['song'];
            }
        }
        return $ids;
    }
}
