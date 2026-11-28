<?php

namespace App\Support;

use App\Models\DbConcert;
use App\Models\DbSetlist;
use App\Models\DbSong;

// アーティストのstatsの「トピックス」。公式の演奏記録（db_setlists）から、
// 久しぶりに演奏された曲・いちばん長い間演奏されていない曲・定番だったのに演奏されなくなった曲・発売から初演まで時間がかかった曲・本編ラスト曲を出す（Databaseのstats）。
// 参加記録と組み合わせて、最近初めて聴いた曲・久しぶりに聴いた曲・自分が聴いた「久しぶり」・初めて聴いた曲の年表を出す（Yuki／マイページのstats）。
// 回数はどれも他のランキングと同じく「演奏されたツアーの数」で数える
class ArtistTopics
{
    // 一覧はこの曲数まで（statsでは最初の10曲だけ出し、残りは Show More）
    private const LIST_SIZE = 25;

    private array $songs;
    private array $tours;
    // そのアーティスト自身のライブ（組になっているアーティストのライブを除く）
    private array $ownTourIds;
    private array $setlistsByTour = [];
    // [曲ID => [ツアーID => true]]
    private array $toursBySong = [];

    // $userArtist = true のときは、マイページで作ったアーティスト（user_songs / user_concerts / user_setlists / user_singles / user_albums）で計算する
    public function __construct(private int $artistId, private bool $userArtist = false)
    {
        $songModel = $userArtist ? \App\Models\UserSong::class : DbSong::class;
        $tourModel = $userArtist ? \App\Models\UserConcert::class : DbConcert::class;
        $this->songs = $songModel::where($this->artistColumn(), $artistId)->pluck('title', 'id')->all();
        $today = now()->toDateString();
        // Database のアーティストは、組になっているアーティスト（ソロプロジェクトと、そのグループ）のライブで演奏した自分の曲も数える。
        // ランキング（ComputesDbSongStamps::crossoverTourArtistIds）と同じ。曲は自分の曲（$this->songs）だけに絞る
        $tourArtistIds = $userArtist ? [$artistId] : \App\Models\Artist::songArtistIds($artistId);
        $this->tours = $tourModel::whereIn($this->artistColumn(), $tourArtistIds)
            ->whereNotNull('date1')
            ->whereDate('date1', '<=', $today)
            ->orderBy('date1')
            ->get(['id', 'title', 'date1', 'date2', 'type', $this->artistColumn()])
            ->keyBy('id')
            ->all();
        $this->ownTourIds = array_keys(array_filter($this->tours, fn ($tour) => (int) $tour->{$this->artistColumn()} === $artistId));
        $setlists = $userArtist
            ? \App\Models\UserSetlist::whereIn('user_concert_id', array_keys($this->tours))->get()
            : DbSetlist::whereIn('tour_id', array_keys($this->tours))->get();
        foreach ($setlists as $setlist) {
            $tourId = $userArtist ? $setlist->user_concert_id : $setlist->tour_id;
            $this->setlistsByTour[$tourId][] = $setlist;
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $item) {
                if ($this->isSong($item)) {
                    $this->toursBySong[(int) $item['song']][$tourId] = true;
                }
            }
        }
    }

    private function artistColumn(): string
    {
        return $this->userArtist ? 'user_artist_id' : 'artist_id';
    }

    private function discModels(): array
    {
        return $this->userArtist
            ? [\App\Models\UserSingle::class, \App\Models\UserAlbum::class]
            : [\App\Models\DbSingle::class, \App\Models\DbAlbum::class];
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
    public function revivals(int $limit = self::LIST_SIZE): array
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

        return array_slice($rows, 0, $limit);
    }

    // 同じライブシリーズを1つにまとめるためのキー（B'z の -Preview- / -Intermission- / -Extra- など、同じツアーの延長として
    // 別に登録された公演を1ツアーとして数える）。タイトル末尾のそれらを外し、大文字・小文字の違いも同じとみなす
    private function seriesKey(int $tourId): string
    {
        $title = trim($this->tours[$tourId]->title);
        $title = preg_replace('/\s*(?:-\s*(?:Preview|Intermission|Extra)\s*-|EXTRA|In Tokyo)\s*$/iu', '', $title);

        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($title)));
    }

    // 曲が演奏されたライブシリーズの数
    private function seriesCount(array $tourIds): int
    {
        return count(array_unique(array_map(fn ($id) => $this->seriesKey($id), $tourIds)));
    }

    // いちばん長い間演奏されていない曲：最後の演奏から時間がたっている順。$minTours 以上のツアー（同じライブシリーズは1つ）で演奏された曲だけ、
    // $singlesOnly ならシングルの表題曲だけ。$limit 曲まで
    public function dormant(int $minTours = 1, bool $singlesOnly = false, int $limit = self::LIST_SIZE): array
    {
        $today = now()->toDateString();
        $singles = $singlesOnly ? $this->singleSongIds() : null;
        $rows = [];
        foreach (array_keys($this->toursBySong) as $songId) {
            $ids = $this->sortedTours($songId);
            $series = $this->seriesCount($ids);
            if ($series < $minTours || ($singles !== null && !isset($singles[$songId]))) {
                continue;
            }
            $last = $this->tourInfo(end($ids));
            $rows[] = ['song_id' => $songId, 'title' => $this->songs[$songId], 'tours' => $series, 'last' => $last, 'years' => self::years($last['date'], $today)];
        }
        usort($rows, fn ($a, $b) => $b['years'] <=> $a['years']);

        return array_slice($rows, 0, $limit);
    }

    // 発売から初めて演奏されるまでに時間がかかった曲（発売日と、初めて演奏されたツアーの開始日の差が大きい順）
    public function lateDebuts(): array
    {
        $this->releaseDates ??= $this->releaseDates();
        $rows = [];
        foreach ($this->releaseDates as $songId => $released) {
            if (!isset($this->songs[$songId]) || empty($this->toursBySong[$songId])) {
                continue;
            }
            $first = $this->tourInfo($this->sortedTours($songId)[0]);
            $years = self::years($released, $first['date']);
            if ($years >= 1) {
                $rows[] = ['song_id' => $songId, 'title' => $this->songs[$songId], 'released' => $released, 'first' => $first, 'years' => $years];
            }
        }
        usort($rows, fn ($a, $b) => $b['years'] <=> $a['years']);

        return array_slice($rows, 0, self::LIST_SIZE);
    }

    // そのツアーのセットリストで、曲が最初に出てくる位置（段・パターン番号の順にパターンを見て、本編・アンコールの通し番号）
    private function setlistPositions(int $tourId): array
    {
        $setlists = $this->setlistsByTour[$tourId] ?? [];
        usort($setlists, fn ($a, $b) => [(int) ($a->row ?? 1), (int) ($a->order_no ?? 0)] <=> [(int) ($b->row ?? 1), (int) ($b->order_no ?? 0)]);
        $positions = [];
        $n = 0;
        foreach ($setlists as $setlist) {
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $item) {
                if ($this->isSong($item)) {
                    $positions[(int) $item['song']] ??= $n;
                }
                $n++;
            }
        }

        return $positions;
    }

    // 1つのツアーでしか演奏されていない曲。演奏したツアーの新しい順（同じツアーならセットリストの順）。
    // 同じライブシリーズをタイトルでまとめる決まり（seriesKey）は使わない。タイトルが違う同じ時期のライブ（SHOWCASE・PREMIUM LIVE など）は
    // どのみちまとめられないので、ツアー1件ずつで数える（ユーザー、2026-10-30）
    // $toursOnly：種類が「ツアー」（type 0）のライブだけで数える（単発ライブ・イベントなどで演奏したことは数えない）
    public function performedOnlyOnce(bool $toursOnly = false): array
    {
        $rows = [];
        foreach (array_keys($this->toursBySong) as $songId) {
            $ids = $this->sortedTours($songId);
            if ($toursOnly) {
                $ids = array_values(array_filter($ids, fn ($id) => (int) $this->tours[$id]->type === 0));
            }
            if (count($ids) === 1) {
                $rows[] = ['song_id' => $songId, 'title' => $this->songs[$songId], 'tour' => $this->tourInfo($ids[0])];
            }
        }
        $positions = [];
        foreach ($rows as &$row) {
            $positions[$row['tour']['id']] ??= $this->setlistPositions($row['tour']['id']);
            $row['position'] = $positions[$row['tour']['id']][$row['song_id']] ?? PHP_INT_MAX;
        }
        unset($row);
        usort($rows, fn ($a, $b) => [$b['tour']['date'], $b['tour']['id'], $a['position']] <=> [$a['tour']['date'], $a['tour']['id'], $b['position']]);

        return $rows;
    }

    // シングルの表題曲（両A面は「A / B」の曲数ぶん先頭から、EPは全曲）。スタンプ帳のシングルの絞り込みと同じ考え方
    private function singleSongIds(): array
    {
        $ids = [];
        foreach ($this->discModels()[0]::where($this->artistColumn(), $this->artistId)->get(['title', 'tracklist', 'ep']) as $single) {
            $tracks = $single->tracklist ?? [];
            if (!$single->ep) {
                $tracks = array_slice($tracks, 0, count(preg_split('/[\/／]/u', $single->title ?? '')));
            }
            foreach ($tracks as $track) {
                if (is_numeric($track['id'] ?? null)) {
                    $ids[(int) $track['id']] = true;
                }
            }
        }

        return $ids;
    }

    // 本編ラスト曲：各パターンの本編（アンコール前）最後の曲を、演奏されたツアーの数で数える。
    // 自分のライブだけ（組になっているアーティストのライブでは、本編の途中の曲が「自分の曲の中で最後」になってしまう）
    // $patternShows: 公演の数で数えるとき、パターンごとの公演（PatternShows::of）。渡さなければツアー・ライブの数
    public function closingSongs(?array $patternShows = null): array
    {
        $counts = [];
        foreach (array_intersect_key($this->setlistsByTour, array_flip($this->ownTourIds)) as $tourId => $setlists) {
            foreach ($setlists as $setlist) {
                $main = array_values(array_filter($setlist->setlist ?? [], fn ($item) => $this->isSong($item) && empty($item['medley'])));
                if ($main) {
                    foreach ($patternShows === null ? [$tourId] : ($patternShows[$setlist->id] ?? []) as $unit) {
                        $counts[(int) end($main)['song']][$unit] = true;
                    }
                }
            }
        }

        return $this->ranking(array_map('count', $counts));
    }

    // ===== 参加記録と組み合わせるもの（Yuki／マイページのstats） =====
    // $heard: 参加した公演の一覧 [['date' => 'Y-m-d', 'song_ids' => [DbSongのID, ...], 'titles' => [DbSongのID => その公演の別表記]], ...]
    // 曲名は、その公演で聴いた表記（セトリの別表記）で出す（スタンプ帳の並べ替えと同じ考え方）。version はその表記（曲名のままなら null）

    // セトリの曲（$items）から、曲ごとのその公演の別表記 [曲の id => 別表記]。$songId で曲の id に置き換える（Yuki's Setlists の曲 → Database の曲）
    public static function versionTitles(iterable $items, ?callable $songId = null): array
    {
        $titles = [];
        foreach ($items as $s) {
            $id = is_numeric($s['song'] ?? null) ? ($songId ? $songId((int) $s['song']) : (int) $s['song']) : null;
            $version = trim((string) ($s['alternative_title'] ?? ''));
            if ($id !== null && $version !== '') {
                $titles[(int) $id] = $version;
            }
        }

        return $titles;
    }

    // マイページの参加記録を、$heard の形にする。$userArtist = false なら Database のパターンの記録（dbSetlist.tour を読み込んでおく）、
    // true ならマイページで作ったアーティストの記録（userSetlist.concert を読み込んでおく）。
    // マイページのアーティストの stats と、参加記録のページの「その公演の stats」で使う
    public static function heardFromAttendances(iterable $attendances, bool $userArtist = false): array
    {
        $setlistOf = fn ($a) => $userArtist ? $a->userSetlist : $a->dbSetlist;
        $tourOf = fn ($a) => $userArtist ? $a->userSetlist->concert : $a->dbSetlist->tour;

        return collect($attendances)->filter(fn ($a) => $setlistOf($a))->map(fn ($a) => [
            'date' => $a->attended_date ? $a->attended_date->format('Y-m-d') : substr((string) optional($tourOf($a))->date1, 0, 10),
            'title' => optional($tourOf($a))->title,
            'url' => route('mypage.attendances.show', ['attendance' => $a, 'from' => 'stats']),
            'song_ids' => collect(array_merge($setlistOf($a)->setlist ?? [], $setlistOf($a)->encore ?? []))
                ->filter(fn ($s) => is_numeric($s['song'] ?? null))->map(fn ($s) => (int) $s['song'])->values()->all(),
            'titles' => static::versionTitles(array_merge($setlistOf($a)->setlist ?? [], $setlistOf($a)->encore ?? [])),
            // その公演のツアー（参加記録のページの「その公演の stats」で、このツアーより前のツアーを見るため）
            'tour_id' => $userArtist ? $setlistOf($a)->user_concert_id : $setlistOf($a)->tour_id,
            'attendance_id' => $a->id,
        ])->values()->all();
    }

    private function heardTitle(array $show, int $songId): array
    {
        $version = $show['titles'][$songId] ?? null;

        return ['title' => $version ?: $this->songs[$songId], 'version' => $version ?: null];
    }

    // Database のライブのページの Analyze（アーティストの stats と同じ決まりで、そのライブについて）。
    // returns：前に演奏したツアー（このライブより前に終わったもの）から1年以上あいて演奏された曲、debuts：このライブで初めて演奏された曲、
    // lateDebuts：このライブが初演で、発売から1年以上たっていた曲（lateDebuts と同じ決まり。あいた年数の長い順）、
    // onlyHere：このライブでしか演奏されていない曲（performedOnlyOnce と同じ。同じライブシリーズはまとめない）、
    // notPlayedSince：このライブのあと演奏されていない曲（このライブより後のライブがあるときだけ。今日までの年数）、
    // releaseYears：このライブで演奏した曲の発売年の年表（Timeline のタブ）。
    // ライブの曲はそのライブのパターン全部（段・パターン番号の順に、初めて出てきた順）。まだ開催していないライブは空
    public function tourStats(int $tourId): array
    {
        $empty = ['returns' => [], 'debuts' => [], 'lateDebuts' => [], 'onlyHere' => [], 'notPlayedSince' => [], 'releaseYears' => []];
        if (!isset($this->tours[$tourId])) {
            return $empty;
        }
        $patterns = $this->setlistsByTour[$tourId] ?? [];
        usort($patterns, fn ($a, $b) => [(int) ($a->row ?? 1), (int) $a->order_no] <=> [(int) ($b->row ?? 1), (int) $b->order_no]);
        $songIds = [];
        foreach ($patterns as $pattern) {
            foreach (array_merge($pattern->setlist ?? [], $pattern->encore ?? []) as $item) {
                if ($this->isSong($item)) {
                    $songIds[(int) $item['song']] = true;
                }
            }
        }
        $tour = $this->tours[$tourId];
        $tourDate = substr($tour->date1, 0, 10);
        // 曲名はこのライブのセトリの表記（別表記があればそれ。パターンで違えば最初に出てきたもの）。参加記録のページと同じ
        $titles = [];
        foreach ($patterns as $pattern) {
            $titles += static::versionTitles(array_merge($pattern->setlist ?? [], $pattern->encore ?? []));
        }
        $show = ['date' => $tourDate, 'title' => $tour->title, 'url' => null, 'song_ids' => array_keys($songIds), 'titles' => $titles, 'tour_id' => $tourId];
        $summary = $this->attendanceSummary($show, []);

        $this->releaseDates ??= $this->releaseDates();
        $hasLater = (bool) array_filter($this->tours, fn ($other) => $other->date1 > $tour->date1);
        $today = now()->toDateString();
        $lateDebuts = $onlyHere = $notPlayedSince = [];
        foreach (array_keys($songIds) as $songId) {
            $ids = $this->sortedTours($songId);
            $row = ['song_id' => $songId] + $this->heardTitle($show, $songId);
            if (($ids[0] ?? null) === $tourId && isset($this->releaseDates[$songId]) && ($years = self::years($this->releaseDates[$songId], $tourDate)) >= 1) {
                $lateDebuts[] = $row + ['released' => $this->releaseDates[$songId], 'years' => $years];
            }
            if ($ids === [$tourId]) {
                $onlyHere[] = $row;
            }
            if ($hasLater && end($ids) === $tourId) {
                $notPlayedSince[] = $row + ['years' => self::years(substr($tour->date2 ?? $tour->date1, 0, 10), $today)];
            }
        }
        usort($lateDebuts, fn ($a, $b) => $b['years'] <=> $a['years']);

        return ['returns' => $summary['returns'], 'debuts' => $summary['debuts'], 'lateDebuts' => $lateDebuts, 'onlyHere' => $onlyHere,
            'notPlayedSince' => $notPlayedSince, 'releaseYears' => $summary['releaseYears']];
    }

    // 参加記録のページの、その公演の stats のうち、その人の参加記録だけで決まるもの（Database に曲が無いアーティストでも出せる）。
    // $show はその公演（$heard の1件と同じ形）、$heard はその人のそのアーティストの参加記録の全部、$songTitles は [曲の id => 曲名]（ここに無い曲は数えない）。
    // $isNew（任意）：[曲の id, 公演日] => 新曲か（「新曲を除く」用。渡さなければ新曲の印を付けない）。
    // firstHeard：その人がこの公演で初めて生で聴いた曲（前の参加記録に無い曲）。セトリの順
    // welcomeBack：その人が前に聴いたことのある曲（あいた期間は問わない。1年未満でも出す）。あいた年数の長い順
    public static function personalListens(array $show, array $heard, array $songTitles, ?callable $isNew = null): array
    {
        $before = array_filter($heard, fn ($other) => $other['date'] < $show['date']);
        $firstHeard = $welcomeBack = [];
        foreach (array_unique($show['song_ids']) as $songId) {
            if (!isset($songTitles[$songId])) {
                continue;
            }
            $version = $show['titles'][$songId] ?? null;
            $title = ['title' => $version ?: $songTitles[$songId], 'version' => $version ?: null];
            $lastHeard = null;
            foreach ($before as $other) {
                if (in_array($songId, $other['song_ids'], true) && ($lastHeard === null || $other['date'] > $lastHeard['date'])) {
                    $lastHeard = $other;
                }
            }
            if ($lastHeard === null) {
                // 新曲（そのライブの1年前以降に発売された曲と、発売前だった曲）かどうか。「新曲を除く」で使う（Recent First Listens と同じ）
                $firstHeard[] = ['song_id' => $songId] + $title + ['new' => $isNew ? $isNew($songId, $show['date']) : false];
            } else {
                // 1年未満でも出す（参加記録のページの Welcome Back。2026-11-29 ユーザー。stats のページの Welcome Back は1年以上のまま）
                $years = self::years($lastHeard['date'], $show['date']);
                // 1年未満は月数（暦の月）、1ヶ月未満は日数で出す（「0.0年」にならないよう）。並びは日数で
                $days = (int) round((strtotime($show['date']) - strtotime($lastHeard['date'])) / 86400);
                $months = (int) \Illuminate\Support\Carbon::parse($lastHeard['date'])->diffInMonths(\Illuminate\Support\Carbon::parse($show['date']));
                $welcomeBack[] = ['song_id' => $songId] + $title + ['years' => $years, 'months' => $months, 'days' => $days, 'from' => ['date' => $lastHeard['date'], 'title' => $lastHeard['title'] ?? '', 'url' => $lastHeard['url'] ?? null],
                    'to' => ['date' => $show['date'], 'title' => $show['title'] ?? '', 'url' => $show['url'] ?? null]];
            }
        }
        usort($welcomeBack, fn ($a, $b) => $b['days'] <=> $a['days']);

        return compact('firstHeard', 'welcomeBack');
    }

    // 参加記録のページの、その公演の stats（セトリの下）。$show はその公演（$heard の1件と同じ形。tour_id も付ける）、$heard はその人のそのアーティストの参加記録の全部。
    // firstHeard・welcomeBack：personalListens
    // returns：アーティストが前に演奏したツアー（この公演より前に終わったもの）から1年以上あいて演奏した曲（revivals と同じ決まり）。あいた年数の長い順
    // debuts：このツアーで初めて演奏された曲（このツアーより前のツアーで演奏していない曲）。セトリの順
    public function attendanceSummary(array $show, array $heard): array
    {
        ['firstHeard' => $firstHeard, 'welcomeBack' => $welcomeBack] = static::personalListens($show, $heard, $this->songs, fn ($songId, $date) => $this->isNewSong($songId, $date));
        $returns = $debuts = [];
        foreach (array_unique($show['song_ids']) as $songId) {
            if (!isset($this->songs[$songId])) {
                continue;
            }
            $title = $this->heardTitle($show, $songId);

            $ids = $this->sortedTours($songId);
            $prev = null;
            foreach ($ids as $tourId) {
                $tour = $this->tours[$tourId];
                if ($tourId !== ($show['tour_id'] ?? null) && substr($tour->date2 ?? $tour->date1, 0, 10) < $show['date']) {
                    $prev = $tourId;
                }
            }
            if ($prev !== null) {
                $prevTour = $this->tours[$prev];
                $years = self::years(substr($prevTour->date2 ?? $prevTour->date1, 0, 10), $show['date']);
                if ($years >= 1) {
                    $returns[] = ['song_id' => $songId] + $title + ['years' => $years, 'previous' => $this->tourInfo($prev), 'date' => $show['date'], 'show' => $show['title'] ?? '', 'show_url' => $show['url'] ?? null];
                }
            }
            if (($ids[0] ?? null) === ($show['tour_id'] ?? null)) {
                $debuts[] = ['song_id' => $songId] + $title;
            }
        }
        usort($returns, fn ($a, $b) => $b['years'] <=> $a['years']);
        $releaseYears = $this->releaseYears($show);

        return compact('firstHeard', 'welcomeBack', 'returns', 'debuts', 'releaseYears');
    }

    // その公演で演奏した曲を、発売年（シングル・アルバムでいちばん早く出た日の年）ごとに、新しい年から（参加記録のページの Analyze の下の年表）。
    // まだ発売されていない曲（シングル・アルバムに入っていない曲）は 'Unreleased' としていちばん上に。年の中はセトリの順
    private function releaseYears(array $show): array
    {
        $this->releaseDates ??= $this->releaseDates();
        $years = [];
        foreach (array_unique($show['song_ids']) as $songId) {
            if (!isset($this->songs[$songId])) {
                continue;
            }
            $date = $this->releaseDates[$songId] ?? null;
            $years[$date ? substr($date, 0, 4) : 'Unreleased'][] = ['song_id' => $songId] + $this->heardTitle($show, $songId);
        }
        uksort($years, fn ($a, $b) => $a === 'Unreleased' ? -1 : ($b === 'Unreleased' ? 1 : strcmp($b, $a)));

        return $years;
    }

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
                // 1年未満でも、前の演奏から間が空いていれば出す（空いた年数の長い順に上位だけ出すので、長いものが先に並ぶ）
                if ($years > 0 && (!isset($rows[$songId]) || $years > $rows[$songId]['years'])) {
                    $rows[$songId] = ['song_id' => $songId] + $this->heardTitle($show, $songId) + ['years' => $years, 'date' => $show['date'], 'show' => $show['title'] ?? '', 'show_url' => $show['url'] ?? null, 'previous' => $this->tourInfo($prev)];
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
                        $rows[$songId] = ['song_id' => $songId] + $this->heardTitle($show, $songId) + ['years' => $years,
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

    // 自分が聴いた曲で、最後に聴いてからいちばん長い間聴いていない曲（Long Time No Play の自分の版）：最後に聴いた日から今日までが長い順
    public function personalDormant(array $heard, int $limit = 50): array
    {
        $today = now()->toDateString();
        $last = [];
        foreach ($heard as $show) {
            if ($show['date'] > $today) {
                continue;
            }
            foreach (array_unique($show['song_ids']) as $songId) {
                if (isset($this->songs[$songId]) && (!isset($last[$songId]) || $last[$songId]['date'] < $show['date'])) {
                    $last[$songId] = ['date' => $show['date'], 'title' => $show['title'] ?? '', 'url' => $show['url'] ?? null, 'song' => $this->heardTitle($show, $songId)];
                }
            }
        }
        $rows = [];
        foreach ($last as $songId => $show) {
            $rows[] = ['song_id' => $songId] + $show['song'] + ['last' => $show, 'years' => self::years($show['date'], $today)];
        }
        usort($rows, fn ($a, $b) => $b['years'] <=> $a['years']);

        return array_slice($rows, 0, $limit);
    }

    // 最近初めて聴いた曲：初めて生で聴いた曲を、新しい順に。新曲（そのライブの1年前以降に発売された曲と、発売前だった曲）は、
    // 聴けて当たり前なので除く
    public function recentFirstListens(array $heard, bool $excludeNew = true): array
    {
        usort($heard, fn ($a, $b) => strcmp($a['date'], $b['date']));
        $first = [];
        foreach ($heard as $show) {
            foreach ($show['song_ids'] as $songId) {
                if (isset($this->songs[$songId]) && !isset($first[$songId])) {
                    $first[$songId] = ['song_id' => $songId] + $this->heardTitle($show, $songId) + ['date' => $show['date'], 'show' => $show['title'] ?? '', 'show_url' => $show['url'] ?? null,
                        'new' => $this->isNewSong($songId, $show['date'])];
                }
            }
        }
        if ($excludeNew) {
            $first = array_filter($first, fn ($row) => !$row['new']);
        }
        $rows = array_values($first);
        usort($rows, fn ($a, $b) => strcmp($b['date'], $a['date']));

        return array_slice($rows, 0, self::LIST_SIZE);
    }

    // そのライブの時点で新曲かどうか：ライブの1年前以降に発売された曲と、まだ発売されていなかった曲。
    // 発売日（シングル・アルバムで最初に出た日）で判定し、発売されていない曲は初めて演奏された日で判定する
    private function isNewSong(int $songId, string $date): bool
    {
        $this->releaseDates ??= $this->releaseDates();
        // マイページで作ったアーティストは登録されたツアーが少なく、「初めて演奏された日」が当てにならないので、
        // 発売日（登録したシングル・アルバム）が分からない曲は新曲として扱わない
        if ($this->userArtist && !isset($this->releaseDates[$songId])) {
            return false;
        }
        $tours = $this->sortedTours($songId);
        $debut = $this->releaseDates[$songId] ?? ($tours ? substr($this->tours[$tours[0]]->date1, 0, 10) : $date);

        return $debut >= date('Y-m-d', strtotime($date . ' -1 year'));
    }

    private ?array $releaseDates = null;

    // 曲ごとの発売日：シングル・アルバム（ベスト盤を含む）の収録曲のうち、いちばん早く出た日
    private function releaseDates(): array
    {
        $dates = [];
        foreach ($this->discModels() as $model) {
            foreach ($model::where($this->artistColumn(), $this->artistId)->whereNotNull('date')->get(['date', 'tracklist']) as $disc) {
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

    // 初めて聴いた曲の年表：年ごとに、その年に初めて生で聴いた曲（新しい年から）。
    // new: そのライブの時点で新曲かどうか（「新曲を除く」用）
    public function firstHeardTimeline(array $heard): array
    {
        usort($heard, fn ($a, $b) => strcmp($a['date'], $b['date']));
        $seen = [];
        $timeline = [];
        foreach ($heard as $show) {
            foreach ($show['song_ids'] as $songId) {
                if (isset($this->songs[$songId]) && !isset($seen[$songId])) {
                    $seen[$songId] = true;
                    $timeline[substr($show['date'], 0, 4)][] = ['song_id' => $songId] + $this->heardTitle($show, $songId) + ['new' => $this->isNewSong($songId, $show['date'])];
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
        $heardRevivals = $firstHeard = $welcomeBack = $recentFirst = $recentFirstAll = [];
        foreach ($heardByArtist as $artistId => $heard) {
            $topics = new self((int) $artistId);
            $withArtist = fn ($row) => $row + ['artist' => $names[$artistId] ?? '', 'artist_id' => (int) $artistId];
            $heardRevivals = array_merge($heardRevivals, array_map($withArtist, $topics->heardRevivals($heard)));
            $welcomeBack = array_merge($welcomeBack, array_map($withArtist, $topics->welcomeBack($heard)));
            $recentFirst = array_merge($recentFirst, array_map($withArtist, $topics->recentFirstListens($heard)));
            $recentFirstAll = array_merge($recentFirstAll, array_map($withArtist, $topics->recentFirstListens($heard, false)));
            foreach ($topics->firstHeardTimeline($heard) as $year => $songs) {
                $firstHeard[$year] = array_merge($firstHeard[$year] ?? [], array_map($withArtist, $songs));
            }
        }
        usort($heardRevivals, fn ($a, $b) => $b['years'] <=> $a['years']);
        usort($welcomeBack, fn ($a, $b) => $b['years'] <=> $a['years']);
        usort($recentFirst, fn ($a, $b) => strcmp($b['date'], $a['date']));
        usort($recentFirstAll, fn ($a, $b) => strcmp($b['date'], $a['date']));
        krsort($firstHeard);

        return [
            'topicHeardRevivals' => array_slice($heardRevivals, 0, self::LIST_SIZE),
            'topicFirstHeard' => $firstHeard,
            'topicWelcomeBack' => array_slice($welcomeBack, 0, self::LIST_SIZE),
            'topicRecentFirst' => array_slice($recentFirst, 0, self::LIST_SIZE),
            'topicRecentFirstAll' => array_slice($recentFirstAll, 0, self::LIST_SIZE),
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
