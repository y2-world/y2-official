<?php

namespace App\Support;

use Carbon\Carbon;

// ツアーの SCHEDULE を「10月8日 (水) 会場」の形にそろえる。
// 「05.03(金・祝)」「2012/3/31(土)」「03/28」「8.10」「4.28,29,5.1,2」などの書き方を読み、1日ずつの行にする。
// 年は、行の中の年 → 年の見出し → ツアーの期間（date1〜date2）に入る年 → 月が戻ったら次の年、の順で決める。
// 曜日は日付から計算する（元の「祝」は残す）。会場のあとの「(福岡県)」のような県名は外す。
// 年をまたぐツアーだけ、年ごとに「2026年」の見出しを付ける。読めない行はそのまま残し、warnings に入れる
class ScheduleFormatter
{
    private const WEEKDAYS = ['日', '月', '火', '水', '木', '金', '土'];

    private const PREFECTURE = '(?:北海道|東京都|京都府|大阪府|[^\s()（）]{2,3}県)';

    /** @return array{text: string, warnings: string[]} */
    public static function format(?string $schedule, $startDate = null, $endDate = null): array
    {
        $schedule = (string) $schedule;
        if (trim($schedule) === '') {
            return ['text' => $schedule, 'warnings' => []];
        }

        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
        $end = $endDate ? Carbon::parse($endDate)->startOfDay() : null;
        $contextYear = $start?->year;
        $headerYear = null;
        $prevMonth = null;
        $rows = [];
        $warnings = [];

        foreach (preg_split('/\r\n|\r|\n/', $schedule) as $line) {
            $line = trim(str_replace("\u{3000}", ' ', $line));
            if ($line === '') {
                continue;
            }

            // 年の見出し（「2026年」「［2025年］」「＜2023年＞」「2013」）
            if (preg_match('/^[［\[（(＜<]?\s*(\d{4})\s*年?\s*[］\]）)＞>]?$/u', $line, $m)) {
                $headerYear = $contextYear = (int) $m[1];
                $prevMonth = null;
                continue;
            }

            $parsed = self::parseLine($line);
            if (!$parsed) {
                $rows[] = ['raw' => $line];
                $warnings[] = "読めない行: {$line}";
                continue;
            }

            [$lineYear, $dates, $givenWeekdays, $holiday, $venue] = $parsed;
            $lineRows = [];
            foreach ($dates as $i => [$month, $day]) {
                if ($lineYear) {
                    $year = $lineYear;
                } elseif ($headerYear) {
                    $year = $headerYear;
                } else {
                    $year = self::yearInPeriod($month, $day, $start, $end)
                        ?? ($contextYear !== null && $prevMonth !== null && $month < $prevMonth ? $contextYear + 1 : $contextYear);
                }
                if (!$year || !checkdate($month, $day, $year)) {
                    $warnings[] = ($year ? 'ない日付: ' : '年が分からない行: ') . $line;
                    $lineRows = null;
                    break;
                }
                $contextYear = $year;
                $prevMonth = $month;

                $weekday = self::WEEKDAYS[Carbon::create($year, $month, $day)->dayOfWeek];
                $given = count($givenWeekdays) === count($dates) ? $givenWeekdays[$i] : null;
                if ($given !== null && $given !== $weekday) {
                    $warnings[] = "曜日が違う: {$line}（{$year}年{$month}月{$day}日は{$weekday}曜日）";
                }
                $holidayMark = $holiday && $i === count($dates) - 1 ? '・祝' : '';
                $lineRows[] = ['year' => $year, 'text' => "{$month}月{$day}日 ({$weekday}{$holidayMark})" . ($venue !== '' ? " {$venue}" : '')];
            }
            if ($lineRows === null) {
                $rows[] = ['raw' => $line];
                continue;
            }
            array_push($rows, ...$lineRows);
        }

        $years = array_values(array_unique(array_filter(array_column($rows, 'year'))));
        $multiYear = count($years) > 1;
        $out = [];
        $currentYear = null;
        foreach ($rows as $row) {
            if (isset($row['raw'])) {
                $out[] = $row['raw'];
                continue;
            }
            if ($multiYear && $row['year'] !== $currentYear) {
                // 2つ目からの年の見出しの上は1行空ける
                if ($currentYear !== null) {
                    $out[] = '';
                }
                $out[] = "{$row['year']}年";
                $currentYear = $row['year'];
            }
            $out[] = $row['text'];
        }

        return ['text' => implode("\n", $out), 'warnings' => $warnings];
    }

    // ツアーの期間（前後1週間まで）に入る年が1つだけなら、その年
    private static function yearInPeriod(int $month, int $day, ?Carbon $start, ?Carbon $end): ?int
    {
        if (!$start || !$end) {
            return null;
        }
        $from = $start->copy()->subDays(7);
        $to = $end->copy()->addDays(7);
        $hits = [];
        for ($y = $from->year; $y <= $to->year; $y++) {
            if (checkdate($month, $day, $y) && Carbon::create($y, $month, $day)->between($from, $to)) {
                $hits[] = $y;
            }
        }

        return count($hits) === 1 ? $hits[0] : null;
    }

    // [年|null, [[月, 日]...], [元の曜日...], 祝があるか, 会場]
    private static function parseLine(string $line): ?array
    {
        $rest = $line;
        $year = null;
        if (preg_match('/^(\d{4})\s*[\/.\-年]\s*(?=\d)/u', $rest, $m)) {
            $year = (int) $m[1];
            $rest = substr($rest, strlen($m[0]));
        }

        // 最初の「月.日」（「4月18日」「4/18」「04.18」）
        $date = self::takeDate($rest, true);
        if (!$date) {
            return null;
        }
        [$month, $day] = $date;
        if ($month < 1 || $month > 12) {
            return null;
        }
        $dates = [[$month, $day]];

        // 続きの日（「,19」「,5.1」。「2.15.16」のような打ち間違いの「.」も区切りとして読む）
        while (preg_match('/^\s*(?:[,，、]|\.(?=\d{1,2}(?:\s|$)))\s*/u', $rest, $sep)) {
            $after = substr($rest, strlen($sep[0]));
            $next = self::takeDate($after, false, $month);
            if (!$next) {
                break;
            }
            $rest = $after;
            [$month, $day] = $next;
            $dates[] = [$month, $day];
        }

        // 曜日（「(土)」「(金・祝)」「(祝・月)」「(土・日)」）
        $weekdays = [];
        $holiday = false;
        if (preg_match('/^\s*[（(]\s*(?:(祝|休|祭)\s*[・･\/]\s*)?([月火水木金土日](?:\s*[・･,、\/]\s*[月火水木金土日])*)\s*(?:[・･\/]\s*(祝|休|祭))?\s*[)）]/u', $rest, $m)) {
            $weekdays = preg_split('/\s*[・･,、\/]\s*/u', $m[2]);
            $holiday = ($m[1] ?? '') !== '' || ($m[3] ?? '') !== '';
            $rest = substr($rest, strlen($m[0]));
        }

        $venue = trim($rest);
        // 日付のすぐあとの注記（「12月3日(中止) SEOUL-AX」）は会場名のうしろへ
        if (preg_match('/^([（(][^)）]*[)）])\s*(.+)$/u', $venue, $m)) {
            $venue = trim($m[2]) . ' ' . $m[1];
        }
        $venue = trim(preg_replace('/\s*[（(]\s*' . self::PREFECTURE . '\s*[）)]$/u', '', $venue));

        return [$year, $dates, $weekdays, $holiday, $venue];
    }

    // 文字列の先頭から「月.日」（$needMonth が false なら「日」だけでも可）を読み、読んだぶんを $text から取る
    private static function takeDate(string &$text, bool $needMonth, ?int $month = null): ?array
    {
        if (preg_match('/^(\d{1,2})\s*(月|[\/.])\s*(\d{1,2})/u', $text, $m)) {
            $text = substr($text, strlen($m[0]));
            self::takeDayMark($text, $m[2] === '月');

            return [(int) $m[1], (int) $m[3]];
        }
        if (!$needMonth && preg_match('/^(\d{1,2})(?!\d)/u', $text, $m)) {
            $text = substr($text, strlen($m[0]));
            self::takeDayMark($text, false);

            return [$month, (int) $m[1]];
        }

        return null;
    }

    // 「日」を読む。「月」で書いた日付なら必ず、そうでなければすぐあとが空白・かっこ・読点・行の終わりのときだけ
    // （「2.19 日本ガイシホール」の「日」を取らない）
    private static function takeDayMark(string &$text, bool $always): void
    {
        $re = $always ? '/^\s*日/u' : '/^日(?=[\s(（,，、]|$)/u';
        if (preg_match($re, $text, $m)) {
            $text = substr($text, strlen($m[0]));
        }
    }
}
