<?php

namespace App\Support;

use App\Models\DbConcert;
use App\Models\DbSetlist;
use App\Models\DbSong;

// アーティストのstatsの「トピックス」。公式の演奏記録（db_setlists）から、
// 久しぶりに演奏された曲・ご無沙汰の曲・本編ラスト曲を出す（Databaseのstats）。
// 参加記録と組み合わせて、最近初めて聴いた曲・久しぶりに聴いた曲・自分が聴いた「久しぶり」・初めて聴いた曲の年表を出す（Yuki／マイページのstats）。
// 回数はどれも他のランキングと同じく「演奏されたツアーの数」で数える
class ArtistTopics
{
    private const LIST_SIZE = 10;

    private array $songs;
    private array $tours;
    private array $setlistsByTour = [];
    // [曲ID => [ツアーID => true]]
    private array $toursBySong = [];

    public function __construct(private int $artistId)
    {
        $this->songs = DbSong::where('artist_id', $artistId)->pluck('title', 'id')->all();
        $today = now()->toDateString();
        $this->tours = DbConcert::where('artist_id', $artistId)
            ->whereNotNull('date1')
            ->whereDate('date1', '<=', $today)
            ->orderBy('date1')
            ->get(['id', 'title', 'date1', 'date2'])
            ->keyBy('id')
            ->all();
        foreach (DbSetlist::whereIn('tour_id', array_keys($this->tours))->get() as $setlist) {
            $this->setlistsByTour[$setlist->tour_id][] = $setlist;
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $item) {
                if ($this->isSong($item)) {
                    $this->toursBySong[(int) $item['song']][$setlist->tour_id] = true;
                }
            }
        }
    }

    private function isSong($item): bool
    {
        return is_numeric($item['song'] ?? null) && isset($this->songs[(int) $item['song']]);
    }

    private function tourInfo(int $tourId): array
    {
        $tour = $this->tours[$tourId];

        return ['id' => $tourId, 'title' => $tour->title, 'date' => substr($tour->date1, 0, 10)];
    }

    // 曲ごとの演奏ツアー（開始日の古い順）
    private function sortedTours(int $songId): array
    {
        $ids = array_keys($this->toursBySong[$songId] ?? []);
        usort($ids, fn ($a, $b) => strcmp($this->tours[$a]->date1, $this->tours[$b]->date1));

        return $ids;
    }

    private static function years(string $from, string $to): float
    {
        return round((strtotime($to) - strtotime($from)) / 86400 / 365.25, 1);
    }

    // ===== Databaseのstats =====

    // 久しぶりに演奏された曲：前の演奏ツアーから次の演奏ツアーまでがいちばん空いた曲（間の年数が長い順）
    public function revivals(): array
    {
        $rows = [];
        foreach (array_keys($this->toursBySong) as $songId) {
            $ids = $this->sortedTours($songId);
            $best = null;
            for ($i = 1; $i < count($ids); $i++) {
                $prev = $this->tours[$ids[$i - 1]];
                $gap = self::years(substr($prev->date2 ?? $prev->date1, 0, 10), substr($this->tours[$ids[$i]]->date1, 0, 10));
                if (!$best || $gap > $best['years']) {
                    $best = ['years' => $gap, 'from' => $this->tourInfo($ids[$i - 1]), 'to' => $this->tourInfo($ids[$i])];
                }
            }
            if ($best && $best['years'] >= 1) {
                $rows[] = ['song_id' => $songId, 'title' => $this->songs[$songId]] + $best;
            }
        }
        usort($rows, fn ($a, $b) => $b['years'] <=> $a['years']);

        return array_slice($rows, 0, self::LIST_SIZE);
    }

    // ご無沙汰の曲：5ツアー以上で演奏されてきたのに、最後の演奏からいちばん時間がたっている曲
    public function dormant(): array
    {
        $today = now()->toDateString();
        $rows = [];
        foreach (array_keys($this->toursBySong) as $songId) {
            $ids = $this->sortedTours($songId);
            if (count($ids) < 5) {
                continue;
            }
            $last = $this->tourInfo(end($ids));
            $rows[] = ['song_id' => $songId, 'title' => $this->songs[$songId], 'tours' => count($ids), 'last' => $last, 'years' => self::years($last['date'], $today)];
        }
        usort($rows, fn ($a, $b) => $b['years'] <=> $a['years']);

        return array_slice($rows, 0, self::LIST_SIZE);
    }

    // 本編ラスト曲：各パターンの本編（アンコール前）最後の曲を、演奏されたツアーの数で数える
    public function closingSongs(): array
    {
        $counts = [];
        foreach ($this->setlistsByTour as $tourId => $setlists) {
            foreach ($setlists as $setlist) {
                $main = array_values(array_filter($setlist->setlist ?? [], fn ($item) => $this->isSong($item) && empty($item['medley'])));
                if ($main) {
                    $counts[(int) end($main)['song']][$tourId] = true;
                }
            }
        }

        return $this->ranking(array_map('count', $counts));
    }

    // ===== 参加記録と組み合わせるもの（Yuki／マイページのstats） =====
    // $heard: 参加した公演の一覧 [['date' => 'Y-m-d', 'song_ids' => [DbSongのID, ...]], ...]

    // 自分が聴いた「久しぶり」：参加した公演で聴いた曲が、その前のツアーから何年ぶりの演奏だったか（長い順）
    public function heardRevivals(array $heard): array
    {
        $rows = [];
        foreach ($heard as $show) {
            foreach (array_unique($show['song_ids']) as $songId) {
                if (!isset($this->songs[$songId])) {
                    continue;
                }
                // 参加した公演より前に終わったツアーのうち、いちばん最近のもの
                $prev = null;
                foreach ($this->sortedTours($songId) as $tourId) {
                    $tour = $this->tours[$tourId];
                    if (substr($tour->date2 ?? $tour->date1, 0, 10) < $show['date']) {
                        $prev = $tourId;
                    }
                }
                if ($prev === null) {
                    continue;
                }
                $prevTour = $this->tours[$prev];
                $years = self::years(substr($prevTour->date2 ?? $prevTour->date1, 0, 10), $show['date']);
                if ($years >= 1 && (!isset($rows[$songId]) || $years > $rows[$songId]['years'])) {
                    $rows[$songId] = ['song_id' => $songId, 'title' => $this->songs[$songId], 'years' => $years, 'date' => $show['date'], 'show' => $show['title'] ?? '', 'show_url' => $show['url'] ?? null, 'previous' => $this->tourInfo($prev)];
                }
            }
        }
        usort($rows, fn ($a, $b) => $b['years'] <=> $a['years']);

        return array_slice(array_values($rows), 0, self::LIST_SIZE);
    }

    // 自分が聴いた中で久しぶりに聴いた曲：自分の参加記録の中で、前に聴いてから次に聴くまでがいちばん空いた曲（長い順）
    public function welcomeBack(array $heard): array
    {
        usort($heard, fn ($a, $b) => strcmp($a['date'], $b['date']));
        $last = [];
        $rows = [];
        foreach ($heard as $show) {
            foreach (array_unique($show['song_ids']) as $songId) {
                if (!isset($this->songs[$songId])) {
                    continue;
                }
                if (isset($last[$songId]) && $last[$songId]['date'] < $show['date']) {
                    $years = self::years($last[$songId]['date'], $show['date']);
                    if ($years >= 1 && (!isset($rows[$songId]) || $years > $rows[$songId]['years'])) {
                        $rows[$songId] = ['song_id' => $songId, 'title' => $this->songs[$songId], 'years' => $years,
                            'from' => $last[$songId],
                            'to' => ['date' => $show['date'], 'title' => $show['title'] ?? '', 'url' => $show['url'] ?? null]];
                    }
                }
                $last[$songId] = ['date' => $show['date'], 'title' => $show['title'] ?? '', 'url' => $show['url'] ?? null];
            }
        }
        usort($rows, fn ($a, $b) => $b['years'] <=> $a['years']);

        return array_slice(array_values($rows), 0, self::LIST_SIZE);
    }

    // 最近初めて聴いた曲：初めて生で聴いた曲を、新しい順に。新曲（そのライブの1年前以降に発売された曲と、発売前だった曲）は、
    // 聴けて当たり前なので除く
    public function recentFirstListens(array $heard): array
    {
        usort($heard, fn ($a, $b) => strcmp($a['date'], $b['date']));
        $first = [];
        foreach ($heard as $show) {
            foreach ($show['song_ids'] as $songId) {
                if (isset($this->songs[$songId]) && !isset($first[$songId])) {
                    $first[$songId] = ['song_id' => $songId, 'title' => $this->songs[$songId], 'date' => $show['date'], 'show' => $show['title'] ?? '', 'show_url' => $show['url'] ?? null];
                }
            }
        }
        $first = array_filter($first, fn ($row) => !$this->isNewSong($row['song_id'], $row['date']));
        $rows = array_values($first);
        usort($rows, fn ($a, $b) => strcmp($b['date'], $a['date']));

        return array_slice($rows, 0, self::LIST_SIZE);
    }

    // そのライブの時点で新曲かどうか：ライブの1年前以降に発売された曲と、まだ発売されていなかった曲。
    // 発売日（シングル・アルバムで最初に出た日）で判定し、発売されていない曲は初めて演奏された日で判定する
    private function isNewSong(int $songId, string $date): bool
    {
        $this->releaseDates ??= $this->releaseDates();
        $tours = $this->sortedTours($songId);
        $debut = $this->releaseDates[$songId] ?? ($tours ? substr($this->tours[$tours[0]]->date1, 0, 10) : $date);

        return $debut >= date('Y-m-d', strtotime($date . ' -1 year'));
    }

    private ?array $releaseDates = null;

    // 曲ごとの発売日：シングル・アルバム（ベスト盤を含む）の収録曲のうち、いちばん早く出た日
    private function releaseDates(): array
    {
        $dates = [];
        foreach ([\App\Models\DbSingle::class, \App\Models\DbAlbum::class] as $model) {
            foreach ($model::where('artist_id', $this->artistId)->whereNotNull('date')->get(['date', 'tracklist']) as $disc) {
                $date = substr((string) $disc->date, 0, 10);
                foreach ($disc->tracklist ?? [] as $track) {
                    if (is_numeric($track['id'] ?? null)) {
                        $id = (int) $track['id'];
                        $dates[$id] = isset($dates[$id]) ? min($dates[$id], $date) : $date;
                    }
                }
            }
        }

        return $dates;
    }

    // そのライブの頃のオリジナルアルバム（ベスト盤以外）の収録曲。ライブの3年前〜1年後に出たアルバムを対象にする
    // （発売前のツアーで新しいアルバムの曲を先に演奏することがあるため、発売後だけでなく少し先に出たアルバムも含める）。
    // ツアーでそのアルバムの曲を聴くのは当たり前なので、初めて聴いた曲の年表では、それ以外の曲（昔の曲・シングルのみ・未発表曲など）を目立たせる
    private function tourAlbumSongIds(string $date): array
    {
        static $albumsByArtist = [];
        $albumsByArtist[$this->artistId] ??= \App\Models\DbAlbum::where('artist_id', $this->artistId)->where('best', 0)
            ->whereNotNull('date')->get(['date', 'tracklist'])->all();
        $from = date('Y-m-d', strtotime($date . ' -3 years'));
        $to = date('Y-m-d', strtotime($date . ' +1 year'));
        $ids = [];
        foreach ($albumsByArtist[$this->artistId] as $album) {
            $released = substr((string) $album->date, 0, 10);
            if ($released >= $from && $released <= $to) {
                foreach ($album->tracklist ?? [] as $track) {
                    if (is_numeric($track['id'] ?? null)) {
                        $ids[(int) $track['id']] = true;
                    }
                }
            }
        }

        return $ids;
    }

    // 初めて聴いた曲の年表：年ごとに、その年に初めて生で聴いた曲（新しい年から）。
    // tour_album: そのツアーの頃のオリジナルアルバムの曲かどうか / new: そのライブの時点で新曲かどうか（「新曲を除く」用）
    public function firstHeardTimeline(array $heard): array
    {
        usort($heard, fn ($a, $b) => strcmp($a['date'], $b['date']));
        $seen = [];
        $timeline = [];
        foreach ($heard as $show) {
            $albumSongs = $this->tourAlbumSongIds($show['date']);
            foreach ($show['song_ids'] as $songId) {
                if (isset($this->songs[$songId]) && !isset($seen[$songId])) {
                    $seen[$songId] = true;
                    $timeline[substr($show['date'], 0, 4)][] = ['song_id' => $songId, 'title' => $this->songs[$songId], 'tour_album' => isset($albumSongs[$songId]),
                        'new' => $this->isNewSong($songId, $show['date'])];
                }
            }
        }
        krsort($timeline);

        return $timeline;
    }

    // 全アーティストをまとめた版（全体のstats用）。$heardByArtist: [アーティストID => 参加した公演の一覧（heardRevivals等と同じ形）]。
    // 各行にアーティスト名（artist）を付けて、アーティストをまたいで1つのランキングに並べ直す（アーティストごとの見方はアーティストのstatsで）
    public static function combined(array $heardByArtist): array
    {
        $names = \App\Models\Artist::whereIn('id', array_keys($heardByArtist))->pluck('name', 'id');
        $heardRevivals = $firstHeard = $welcomeBack = $recentFirst = [];
        foreach ($heardByArtist as $artistId => $heard) {
            $topics = new self((int) $artistId);
            $withArtist = fn ($row) => $row + ['artist' => $names[$artistId] ?? '', 'artist_id' => (int) $artistId];
            $heardRevivals = array_merge($heardRevivals, array_map($withArtist, $topics->heardRevivals($heard)));
            $welcomeBack = array_merge($welcomeBack, array_map($withArtist, $topics->welcomeBack($heard)));
            $recentFirst = array_merge($recentFirst, array_map($withArtist, $topics->recentFirstListens($heard)));
            foreach ($topics->firstHeardTimeline($heard) as $year => $songs) {
                $firstHeard[$year] = array_merge($firstHeard[$year] ?? [], array_map($withArtist, $songs));
            }
        }
        usort($heardRevivals, fn ($a, $b) => $b['years'] <=> $a['years']);
        usort($welcomeBack, fn ($a, $b) => $b['years'] <=> $a['years']);
        usort($recentFirst, fn ($a, $b) => strcmp($b['date'], $a['date']));
        krsort($firstHeard);

        return [
            'topicHeardRevivals' => array_slice($heardRevivals, 0, self::LIST_SIZE),
            'topicFirstHeard' => $firstHeard,
            'topicWelcomeBack' => array_slice($welcomeBack, 0, self::LIST_SIZE),
            'topicRecentFirst' => array_slice($recentFirst, 0, self::LIST_SIZE),
        ];
    }

    private function ranking(array $counts): array
    {
        uksort($counts, fn ($a, $b) => $counts[$b] !== $counts[$a] ? $counts[$b] - $counts[$a] : $a - $b);
        $rows = [];
        foreach ($counts as $songId => $count) {
            $rows[] = ['song_id' => $songId, 'title' => $this->songs[$songId], 'count' => $count];
        }

        return $rows;
    }
}
