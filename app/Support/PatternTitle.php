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
        usort($entries, fn ($a, $b) => [$a['date'], (string) ($a['part'] ?? '')] <=> [$b['date'], (string) ($b['part'] ?? '')]);

        return $entries;
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

    // 改行でつないだ HTML（1公演1行）
    public static function textHtml($pattern): string
    {
        return implode('<br>', array_map('e', static::textLines($pattern)));
    }

    // 短い形（「6.17 佐賀 6.24 広島」）。公演日は日付と地名（会場名から決める。DbSetlistResource::venuePlace）、回があれば「1部」も。
    // 改行できない、Database のツアーのページのパターンを選ぶプルダウンだけで使う
    public static function shortText($pattern): string
    {
        $keys = (array) ($pattern?->schedule_dates ?? []);
        $schedule = $keys === [] ? [] : static::schedule($pattern);
        // 会場が1つだけのライブは、地名を付けずに日付（と回）だけ
        $withPlace = !static::singleVenue($schedule);
        $dates = array_map(function ($entry) use ($withPlace) {
            $place = $withPlace ? \App\Filament\Resources\DbSetlistResource::venuePlace(\App\Models\DbConcert::venueKeyword($entry['venue'] ?? '')) : '';
            return preg_replace('/\s+/u', ' ', trim(Carbon::parse($entry['date'])->format('n.j') . ' ' . $place . ' ' . ($entry['part'] ?? '')));
        }, $keys === [] ? [] : static::entries($keys, $schedule));
        $title = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $pattern?->subtitle)));

        return trim(implode(' ', $dates) . ' ' . $title);
    }

    // 空かどうかを見る・検索に使う文字（行を空白でつないだもの）。画面に出すときは textLines / textHtml
    public static function text($pattern): string
    {
        return implode(' ', static::textLines($pattern));
    }

    // 見出しの HTML の行。公演日は1日1行で、日付も会場名も小さいグレー（見出しの半分の大きさ）。タイトルは今までの出し方（renderSubtitleWithGreyedVenues）
    // 公演日もタイトル（「en-Zepp 1」「Day1」）も、同じ文字で1行ずつ出す（公演日の無いパターンのタイトルも同じ）
    public static function htmlLines($pattern): array
    {
        $line = fn ($text) => '<span class="setlist-schedule-date-line" style="display: block; font-size: 0.5em; line-height: 1.5; color: #999; font-weight: normal;">' . e($text) . '</span>';
        $titles = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $pattern?->subtitle)), fn ($t) => $t !== '');
        $dates = static::dateLabels($pattern);
        $lines = array_map($line, [...$dates, ...$titles]);
        // 公演が2つ以上あるときは、1行目のうしろに「他 N 公演」。見出しがたたまれているときだけ出す（1行目だけでその公演だけと思わないように）
        if (count($dates) >= 2) {
            $lines[0] = preg_replace('/<\/span>$/', '<span class="setlist-subtitle-more">他' . (count($dates) - 1) . '公演</span></span>', $lines[0]);
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
