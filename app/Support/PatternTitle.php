<?php

namespace App\Support;

use App\Models\DbSetlist;
use Illuminate\Support\Carbon;

// パターンの見出し。スケジュールから選んだ公演日（schedule_dates）を「5.30 大阪城ホール」「4.6 大阪城ホール 1部」と1日1行で出し、
// タイトル（subtitle）があればその下に出す。公演日を選んでいないパターン（スケジュールの無いツアーなど）は、今までどおりタイトルだけ。
// 公式（DbSetlist）もマイページ（UserSetlist）も同じ
class PatternTitle
{
    // 公演日1つ（「2026-05-30」「2000-04-06 1部」）を、選ぶ欄・保存に使う形にする
    public static function key(array $entry): string
    {
        return $entry['date'] . (filled($entry['part'] ?? null) ? ' ' . $entry['part'] : '');
    }

    // 公演日1つの見出し（「5.30 大阪城ホール 1部」）
    public static function label(array $entry): string
    {
        $venue = \App\Models\DbConcert::venueKeyword($entry['venue'] ?? '');

        return trim(Carbon::parse($entry['date'])->format('n.j') . ' ' . $venue . ' ' . ($entry['part'] ?? ''));
    }

    // 保存した公演日（キーの並び）を、スケジュールの日に当てる。スケジュールに無い回（「1部」がスケジュールに書かれていない日など）は、
    // 同じ日の会場を借りて出す。並びは日付順
    public static function entries(array $keys, array $schedule): array
    {
        $byKey = [];
        foreach ($schedule as $entry) {
            $byKey[static::key($entry)] ??= $entry;
        }
        $entries = [];
        foreach ($keys as $key) {
            if (isset($byKey[$key])) {
                $entries[] = $byKey[$key];
                continue;
            }
            [$date, $part] = array_pad(explode(' ', (string) $key, 2), 2, null);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $date)) {
                continue;
            }
            $sameDay = collect($schedule)->firstWhere('date', $date);
            $entries[] = ['date' => $date, 'venue' => $sameDay['venue'] ?? '', 'part' => $part];
        }
        usort($entries, fn ($a, $b) => [$a['date'], static::partOrder($a['part'] ?? null)] <=> [$b['date'], static::partOrder($b['part'] ?? null)]);

        return $entries;
    }

    // 同じ日の回を並べるための文字。昼公演・夜公演は 1部・2部 と同じ順（文字のままだと「夜」が「昼」より前になる）
    public static function partOrder(?string $part): string
    {
        return strtr((string) $part, ['昼公演' => '1部', '夜公演' => '2部']);
    }

    // パターンの公演日の見出しの並び（「5.30 大阪城ホール」…）。公演日を選んでいなければ空
    // 会場が1つだけのライブ（単発のライブや、同じ会場で何日もやるライブ）は、会場名を出さずに日付（と回）だけ
    public static function dateLabels($pattern): array
    {
        $keys = (array) ($pattern?->schedule_dates ?? []);
        if ($keys === []) {
            return [];
        }
        $schedule = static::schedule($pattern);
        $entries = static::entries($keys, $schedule);
        if (static::singleVenue($schedule)) {
            return array_map(fn ($entry) => trim(Carbon::parse($entry['date'])->format('n.j') . ' ' . ($entry['part'] ?? '')), $entries);
        }

        return array_map([static::class, 'label'], $entries);
    }

    // スケジュールの会場が1つだけか
    public static function singleVenue(array $schedule): bool
    {
        return count(array_unique(array_map(fn ($entry) => \App\Models\DbConcert::venueKeyword($entry['venue'] ?? ''), $schedule))) === 1;
    }

    // 1公演1行。公演日1つずつと、タイトル（あれば）の行の並び。画面ではこの行を改行でつないで出す
    public static function textLines($pattern): array
    {
        $title = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $pattern?->subtitle)));

        return array_values(array_filter([...static::dateLabels($pattern), $title], fn ($line) => $line !== ''));
    }

    // 縦に長くしたくないところ（管理画面の一覧）用。最初の公演の1行と「他 N 公演」、タイトルがあればその下
    public static function compactHtml($pattern): string
    {
        $dates = static::dateLabels($pattern);
        $title = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $pattern?->subtitle)));
        $lines = [];
        if ($dates) {
            $lines[] = e($dates[0]) . (count($dates) > 1 ? ' <span style="color: #999;">他' . (count($dates) - 1) . '公演</span>' : '');
        }
        if ($title !== '') {
            $lines[] = e($title);
        }

        return implode('<br>', $lines);
    }

    // 改行でつないだ HTML（1公演1行）
    public static function textHtml($pattern): string
    {
        return implode('<br>', array_map('e', static::textLines($pattern)));
    }

    // 短い形（「6.17 佐賀 6.24 広島」）。公演日は日付と地名（会場名から決める。DbSetlistResource::venuePlace）、回があれば「1部」も。
    // 同じ地名で続けて何日かあるときは、地名のうしろにその何日目か（「12.16 札幌2」）。
    // 改行できない、Database のツアーのページのパターンを選ぶプルダウンだけで使う
    public static function shortText($pattern): string
    {
        $keys = (array) ($pattern?->schedule_dates ?? []);
        $schedule = $keys === [] ? [] : static::schedule($pattern);
        // 会場が1つだけのライブは、地名を付けずに日付（と回）だけ
        $withPlace = !static::singleVenue($schedule);
        $place = fn ($entry) => \App\Filament\Resources\DbSetlistResource::venuePlace(\App\Models\DbConcert::venueKeyword($entry['venue'] ?? ''));
        // 何日目か：スケジュールで同じ地名が続くところ（1回の訪問）ごとに、日付を1から数える（「札幌1 札幌2 札幌3」）
        $ordinal = [];
        $runs = [];
        $prevPlace = null;
        foreach ($schedule as $entry) {
            $name = $place($entry);
            if ($name !== $prevPlace) {
                $runs[] = [];
            }
            $prevPlace = $name;
            $runs[count($runs) - 1][$entry['date']] = true;
            $ordinal[$entry['date']] = [count($runs) - 1, count($runs[count($runs) - 1])];
        }
        $dates = array_map(function ($entry) use ($withPlace, $place, $ordinal, $runs) {
            $name = '';
            if ($withPlace) {
                $name = $place($entry);
                [$run, $nth] = $ordinal[$entry['date']] ?? [null, null];
                if ($run !== null && count($runs[$run]) > 1) {
                    $name .= $nth;
                }
            }
            return preg_replace('/\s+/u', ' ', trim(Carbon::parse($entry['date'])->format('n.j') . ' ' . $name . ' ' . ($entry['part'] ?? '')));
        }, $keys === [] ? [] : static::entries($keys, $schedule));
        $title = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $pattern?->subtitle)));

        return trim(implode(' ', $dates) . ' ' . $title);
    }

    // プルダウン（改行できない）用の1行。最初の公演（会場名はフル）と「他 N 公演」、タイトルがあればそのうしろ
    public static function oneLine($pattern): string
    {
        $dates = static::dateLabels($pattern);
        $title = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $pattern?->subtitle)));
        $first = $dates ? $dates[0] . (count($dates) > 1 ? ' 他' . (count($dates) - 1) . '公演' : '') : '';

        return trim($first . ' ' . $title);
    }

    // 空かどうかを見る・検索に使う文字（行を空白でつないだもの）。画面に出すときは textLines / textHtml
    public static function text($pattern): string
    {
        return implode(' ', static::textLines($pattern));
    }

    // 見出しの HTML の行。公演日は1日1行で、日付も会場名も小さいグレー（見出しの半分の大きさ）
    // 公演日もタイトル（「en-Zepp 1」「Day1」）も、同じ文字で1行ずつ出す（公演日の無いパターンのタイトルも同じ）
    public static function htmlLines($pattern): array
    {
        return static::htmlLinesFor([$pattern]);
    }

    // たたんだ見出しに出す行の数。これより多いときだけたたむ（db_concerts/show の SUBTITLE_COLLAPSE_LINES と合わせる）
    public const VISIBLE_LINES = 1;

    // いくつかのパターンの見出しの行をつないだもの（Summary の、段のパターンを全部並べる見出し）。
    // 見出しをたたんだときは1行目だけ見せる。2行目からは --rest（たたむと隠す）。
    // 1行目のうしろの「他 N 公演」（隠れた公演の数）はたたんでいるときだけ出す（見えている公演だけと思わないように）。
    // そのときは1行に収め、長い会場名は「他 N 公演」の前を「…」で切る
    public static function htmlLinesFor(iterable $patterns): array
    {
        $texts = [];
        foreach ($patterns as $pattern) {
            foreach (static::dateLabels($pattern) as $date) {
                $texts[] = [$date, true];
            }
            foreach (array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $pattern?->subtitle)), fn ($t) => $t !== '') as $title) {
                $texts[] = [$title, false];
            }
        }
        $lines = [];
        $hiddenDates = 0;
        foreach ($texts as $i => [$text, $isDate]) {
            $rest = $i >= static::VISIBLE_LINES;
            $hiddenDates += $rest && $isDate ? 1 : 0;
            $lines[] = '<span class="setlist-schedule-date-line' . ($rest ? ' setlist-schedule-date-line--rest' : '') . '" style="display: block; font-size: 0.5em; line-height: 1.5; color: #999; font-weight: normal;">' . e($text) . '</span>';
        }
        if ($hiddenDates >= 1) {
            $last = static::VISIBLE_LINES - 1;
            // 長い会場名でも2行にせず、「他 N 公演」の前を「…」で切る（文字を囲んで、CSS で1行にする）
            $lines[$last] = preg_replace('/>([^<]*)<\/span>$/', '><span class="setlist-schedule-date-text">$1</span><span class="setlist-subtitle-more">他' . $hiddenDates . '公演</span></span>', $lines[$last]);
        }

        return $lines;
    }

    // パターンのツアーのスケジュール（ツアーごとに1回だけ読む）
    public static function schedule($pattern): array
    {
        static $cache = [];
        $tour = $pattern instanceof DbSetlist ? ($pattern->relationLoaded('tour') ? $pattern->tour : \App\Models\DbConcert::find($pattern->tour_id))
            : ($pattern?->relationLoaded('concert') ? $pattern->concert : \App\Models\UserConcert::find($pattern?->user_concert_id));
        if (!$tour) {
            return [];
        }
        $cacheKey = get_class($tour) . ':' . $tour->id;

        return $cache[$cacheKey] ??= $tour->parseScheduleEntries();
    }
}
