<?php

namespace App\Support;

use Illuminate\Support\Carbon;

// パターンのタイトルに書いた日付（「5.30 大阪1」の 5.30、「4.4-5.17」「6.13-」「-5.17」「4.4-17」のような範囲）を、
// ツアーのスケジュール（parseScheduleEntries() の [date, venue] の並び）の日に当てはめる。
// マイページで参加日の候補を絞るところ（AttendanceController）と、Database のツアーのページでタイトルを会場名にして出すところ（subtitleWithScheduleVenues）で使う
class SubtitleScheduleDates
{
    // 範囲の書き方。前か後ろの日付のどちらかは要る（「Zepp-TOKYO」のような「-」は範囲にしない）
    public const RANGE_PATTERN = '/(?<![\d.])(?:(\d{1,2})\.(\d{1,2}))?\s*[-‐−–〜～~]\s*(?:(\d{1,2})(?:\.(\d{1,2}))?)?(?![\d.])/u';

    // 1日だけの日付
    public const DATE_PATTERN = '/(?<![\d.])(\d{1,2})\.(\d{1,2})(?![\d])/u';

    // タイトルに当てはまるスケジュールの日（スケジュールの並びのまま）。タイトルに日付が無い・どの日にも当たらないときは空
    public static function entries(string $subtitle, array $entries): array
    {
        if ($entries === [] || trim($subtitle) === '') {
            return [];
        }
        $ranges = [];
        $rest = preg_replace_callback(static::RANGE_PATTERN, function ($m) use (&$ranges, $entries) {
            $range = static::range($m, $entries);
            if ($range === null) {
                return $m[0];
            }
            $ranges[] = $range;
            return ' ';
        }, $subtitle);
        preg_match_all(static::DATE_PATTERN, $rest, $singles, PREG_SET_ORDER);
        foreach ($singles as $m) {
            if ($date = static::toDate((int) $m[1], (int) $m[2], $entries)) {
                $ranges[] = [$date, $date];
            }
        }

        return static::inRanges($entries, $ranges);
    }

    // 範囲の書き方1つ（RANGE_PATTERN に当たったもの）を [始まり, 終わり] にする。範囲でなければ null
    public static function range(array $m, array $entries): ?array
    {
        $hasStart = ($m[1] ?? '') !== '';
        $hasEnd = ($m[3] ?? '') !== '';
        if (!$hasStart && !$hasEnd) {
            return null;
        }
        $start = $hasStart ? static::toDate((int) $m[1], (int) $m[2], $entries) : $entries[0]['date'];
        $end = !$hasEnd
            ? end($entries)['date']
            // 「4.4-17」のように終わりが日だけなら、始まりと同じ月
            : (($m[4] ?? '') !== '' ? static::toDate((int) $m[3], (int) $m[4], $entries) : ($hasStart ? static::toDate((int) $m[1], (int) $m[3], $entries) : null));

        return $start && $end ? [$start, $end] : null;
    }

    // [始まり, 終わり] の組のどれかに入るスケジュールの日
    public static function inRanges(array $entries, array $ranges): array
    {
        return array_values(array_filter($entries, function ($entry) use ($ranges) {
            foreach ($ranges as [$start, $end]) {
                if ($entry['date'] >= $start && $entry['date'] <= $end) {
                    return true;
                }
            }
            return false;
        }));
    }

    // 「月.日」を年つきの日付にする。年はスケジュールの最初の日から数え、月が戻ったら（年をまたぐツアー）次の年
    public static function toDate(int $month, int $day, array $entries): ?string
    {
        $first = Carbon::parse($entries[0]['date']);
        $year = $first->year + ($month < $first->month ? 1 : 0);

        return checkdate($month, $day, $year) ? sprintf('%04d-%02d-%02d', $year, $month, $day) : null;
    }
}
