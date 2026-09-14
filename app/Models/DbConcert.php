<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DbConcert extends Model
{
    protected $table = 'db_concerts';

    protected $fillable = [
        'artist_id',
        'title',
        'type',
        'date1',
        'date2',
        'venue',
        'schedule',
        'text',
    ];

    protected $casts = [
        // setlist1~6 は削除済み。tour_setlistsテーブルで管理
    ];

    public function artist()
    {
        return $this->belongsTo(Artist::class);
    }

    public function songs()
    {
        return $this->hasMany('App\Models\DbSong');
    }

    public function getTourAttribute($value)
    {
        return array_values(json_decode($value, true) ?: []);
    }

    public function tourSetlists()
    {
        return $this->hasMany(DbSetlist::class, 'tour_id', 'id');
    }

    // scheduleカラム（フリーテキストの日程表）から日付・会場の候補を抽出する。
    // 表記ゆれが大きい（"M月D日(曜)" / "M.D" / "MM.DD(曜)"、複数日をまとめた"M.D,D2"等）ため、
    // パースできない行は単に候補から外れるだけで、フォームの手入力へフォールバックできる。
    public function parseScheduleEntries(): array
    {
        if (!$this->schedule) {
            // schedule列自体が無くても、date1〜date2が数日程度の短い範囲かつ
            // 会場が単一（venue列がある）なら、日毎の候補をそのまま作れる
            return $this->buildDateRangeFallbackEntries();
        }

        $entries = [];
        $currentYear = (int)date('Y', strtotime($this->date1));
        $lastMonth = null;
        $lines = preg_split('/\r\n|\r|\n/', $this->schedule);

        // scheduleに明示的な年の区切りが無い年跨ぎツアー（例: 12月開始〜翌年3月）向けに、
        // 月が前の行より小さくなった時点で年が繰り上がったとみなす。
        $advanceYearIfMonthWrapped = function (int $month) use (&$lastMonth, &$currentYear) {
            if ($lastMonth !== null && $month < $lastMonth) {
                $currentYear++;
            }
            $lastMonth = $month;
        };

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // 年の区切り行（"1992" 単独 or "＜2023年＞"）
            if (preg_match('/^(\d{4})年?$/u', $line, $m) || preg_match('/^[＜<](\d{4})年?[＞>]$/u', $line, $m)) {
                $currentYear = (int)$m[1];
                $lastMonth = null;
                continue;
            }

            // "4月24日(土) 愛知・日本ガイシホール (公演中止)" 形式
            if (preg_match('/^(\d{1,2})月(\d{1,2})日\s*(?:\([^)]*\))?\s*(.+)$/u', $line, $m)) {
                $advanceYearIfMonthWrapped((int)$m[1]);
                $venue = trim(preg_replace('/[（(][^）)]*[）)]\s*$/u', '', $m[3]));
                $date = $this->buildDate($currentYear, (int)$m[1], (int)$m[2]);
                if ($date && $venue !== '') {
                    $entries[] = ['date' => $date, 'venue' => $venue];
                }
                continue;
            }

            // "2022/10/22(土) 三重県営サンアリーナ" / "2022.11.22(火) ..." 形式（年/月/日、年.月.日）
            if (preg_match('/^(\d{4})[.\/](\d{1,2})[.\/](\d{1,2})\s*(?:\([^)]*\))?\s+(.+)$/u', $line, $m)) {
                $currentYear = (int)$m[1];
                $lastMonth = (int)$m[2];
                $venue = trim(preg_replace('/[（(][^）)]*[）)]\s*$/u', '', $m[4]));
                $date = $this->buildDate($currentYear, (int)$m[2], (int)$m[3]);
                if ($date && $venue !== '') {
                    $entries[] = ['date' => $date, 'venue' => $venue];
                }
                continue;
            }

            // "06.18(月) Zepp Sapporo" / "05.14(土) マリンメッセ福岡A館" / "06/18(月) Zepp Sapporo" 形式
            if (preg_match('/^(\d{1,2})[.\/](\d{1,2})\s*(?:\([^)]*\))?\s+(.+)$/u', $line, $m)) {
                $advanceYearIfMonthWrapped((int)$m[1]);
                $venue = trim(preg_replace('/[（(][^）)]*[）)]\s*$/u', '', $m[3]));
                $date = $this->buildDate($currentYear, (int)$m[1], (int)$m[2]);
                if ($date && $venue !== '') {
                    $entries[] = ['date' => $date, 'venue' => $venue];
                }
                continue;
            }

            // "9.28,29 大阪ミューズホール" / "9/28,29 大阪ミューズホール"（複数日で同一会場）形式
            if (preg_match('/^(\d{1,2})[.\/](\d{1,2})(?:,(\d{1,2}))+\s+(.+)$/u', $line, $m)) {
                preg_match('/^(\d{1,2})[.\/]((?:\d{1,2},?)+)\s+(.+)$/u', $line, $m2);
                $month = (int)$m2[1];
                $advanceYearIfMonthWrapped($month);
                $venue = trim($m2[3]);
                $days = explode(',', $m2[2]);
                foreach ($days as $day) {
                    $date = $this->buildDate($currentYear, $month, (int)$day);
                    if ($date && $venue !== '') {
                        $entries[] = ['date' => $date, 'venue' => $venue];
                    }
                }
                continue;
            }
        }

        return $entries;
    }

    private function buildDate(int $year, int $month, int $day): ?string
    {
        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return null;
        }
        if (!checkdate($month, $day, $year)) {
            return null;
        }
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    // schedule列にデータが無い単発〜数日開催のツアー向けフォールバック。
    // date1〜date2の日数が短く（1週間以内）、venueが単一なら、日毎に同じ会場の候補を作る。
    // 長期ツアーで単に日程表がまだ入力されていないだけのケースを、
    // 大量の候補生成で誤魔化さないよう上限を設ける。
    private function buildDateRangeFallbackEntries(): array
    {
        if (!$this->date1 || !$this->venue) {
            return [];
        }

        $start = strtotime($this->date1);
        $end = $this->date2 ? strtotime($this->date2) : $start;
        if ($start === false || $end === false || $end < $start) {
            return [];
        }

        $dayCount = (int)round(($end - $start) / 86400) + 1;
        if ($dayCount > 7) {
            return [];
        }

        $entries = [];
        for ($i = 0; $i < $dayCount; $i++) {
            $entries[] = [
                'date' => date('Y-m-d', $start + $i * 86400),
                'venue' => $this->venue,
            ];
        }

        return $entries;
    }
}
