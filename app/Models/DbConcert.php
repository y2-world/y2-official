<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DbConcert extends Model
{
    protected $table = 'db_concerts';

    // SCHEDULE は「10月8日 (水) 会場」の形にそろえて保存する（管理画面・マイページ・取り込みのどこから保存しても同じ形になるように）
    protected static function booted(): void
    {
        static::saving(function (self $concert) {
            if (filled($concert->schedule) && ($concert->isDirty('schedule') || $concert->isDirty('date1') || $concert->isDirty('date2'))) {
                $concert->schedule = \App\Support\ScheduleFormatter::format($concert->schedule, $concert->date1, $concert->date2)['text'];
            }
        });

        // ツアーを消したら、パターン・Summary の段も消す（パターンを消すと、付いている参加記録も DB の cascade で消える）。
        // db_setlists・db_setlist_rows にはツアーへの外部キーが無く、消さないとパターンと参加記録だけが残って一覧や詳細がエラーになる。
        // Yuki's Setlists（sl_setlists）は Yuki の記録なので消さず、ツアーとの結び付けだけ外す
        static::deleting(function (self $concert) {
            DbSetlist::where('tour_id', $concert->id)->delete();
            DbSetlistRow::where('tour_id', $concert->id)->delete();
            SlSetlist::where('db_concert_id', $concert->id)->update(['db_concert_id' => null]);
        });
    }

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

    // 管理画面（AdminData）で、マイページの UserConcert と同じ書き方で絞り込むためのスコープ
    public function scopeOfArtist($query, $artistId)
    {
        return $query->where('artist_id', $artistId);
    }

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

    // Database の会場（Venues）用。アーティストのツアー・ライブのスケジュールから、1公演ずつ [date, venue, concert] を集める。
    // スケジュールが無いライブは、会場の欄と日付から（parseScheduleEntries と同じ）。「※公演中止」のような注記は会場名から外す
    // $scope でライブを絞れる（Stats の種類の絞り込みなど）。Solo（type 4、メンバーのソロ活動）は数えない
    public static function scheduleVenueEntries(int $artistId, ?\Closure $scope = null): \Illuminate\Support\Collection
    {
        return static::where('artist_id', $artistId)->where(fn ($q) => $q->whereNull('type')->orWhere('type', '!=', '4'))->when($scope, $scope)->get()
            ->flatMap(fn (self $concert) => collect($concert->parseScheduleEntries())
                ->map(fn ($entry) => [
                    'date' => $entry['date'],
                    'venue' => static::venueKeyword($entry['venue']),
                    'concert' => $concert,
                ]))
            ->filter(fn ($entry) => $entry['venue'] !== '')
            ->values();
    }

    // 会場のページ・会場検索・stats の Top Venues で数える公演。ツアーと単発ライブ（type 0・1）だけで、イベント・フェス・ap bank fes・ソロは入れない。
    // venue は今の会場名（改称する前の名前・書き方の違いは VenueAlias でまとめる）、name はスケジュールに書いてある会場名
    public static function venuePageEntries(int $artistId, ?\Closure $scope = null): \Illuminate\Support\Collection
    {
        return static::scheduleVenueEntries($artistId, function ($query) use ($scope) {
            $query->whereIn('type', [0, 1]);
            if ($scope) {
                $scope($query);
            }
        })->map(fn ($entry) => ['name' => $entry['venue'], 'venue' => VenueAlias::resolve($entry['venue'])] + $entry);
    }

    // 会場のページに公演が1つでも出る会場名（ツアーと単発ライブの会場だけ）。会場名にリンクを付けるかどうかに使う。1回のページ表示の間は覚えておく
    public static function linkableVenue(int $artistId, ?string $venue): ?string
    {
        static $names = [];
        $names[$artistId] ??= static::venuePageEntries($artistId)->pluck('venue')->flip()->all();
        $keyword = VenueAlias::resolve(static::venueKeyword($venue));

        return $keyword !== '' && isset($names[$artistId][$keyword]) ? $keyword : null;
    }

    // 会場のページ（?keyword=）で使う会場名。うしろのかっこ書き（「(公演中止)」など）と「※…」の注記を外す。
    // スケジュールの表・ライブの会場の欄からリンクするときも、これでそろえる
    public static function venueKeyword(?string $venue): string
    {
        $venue = trim(preg_replace('/\s*※.*$/u', '', (string) $venue));

        return trim(preg_replace('/\s*(?:[（(][^）)]*[）)]|【[^】]*】)\s*$/u', '', $venue));
    }

    // scheduleカラム（フリーテキストの日程表）から日付・会場の候補を抽出する。[date, venue]、同じ日に何回かあるときは part（「1部」）も。
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

            // "4月24日(土)〜4月25日(日) 愛知・日本ガイシホール" / "4月24日(土) - 4月25日(日)" 形式
            if (preg_match('/^(\d{1,2})月(\d{1,2})日\s*(?:\([^)]*\))?\s*(?:〜|～|-)\s*(\d{1,2})日\s*(?:\([^)]*\))?\s*(.+)$/u', $line, $m)) {
                $month = (int)$m[1];
                $advanceYearIfMonthWrapped($month);
                [$venue, $part] = $this->splitVenueNote($m[4]);
                $startDay = (int)$m[2];
                $endDay = (int)$m[3];
                foreach (range($startDay, $endDay) as $day) {
                    $date = $this->buildDate($currentYear, $month, (int)$day);
                    if ($date && $venue !== '' && !static::cancelledOn($m[4], $date)) {
                        $entries[] = ['date' => $date, 'venue' => $venue] + ($part !== null ? ['part' => $part] : []);
                    }
                }
                continue;
            }

            // "4月24日(土) 愛知・日本ガイシホール (公演中止)" 形式
            if (preg_match('/^(\d{1,2})月(\d{1,2})日\s*(?:\([^)]*\))?\s*(.+)$/u', $line, $m)) {
                $advanceYearIfMonthWrapped((int)$m[1]);
                [$venue, $part] = $this->splitVenueNote($m[3]);
                $date = $this->buildDate($currentYear, (int)$m[1], (int)$m[2]);
                if ($date && $venue !== '' && !static::cancelledOn($m[3], $date)) {
                    $entries[] = ['date' => $date, 'venue' => $venue] + ($part !== null ? ['part' => $part] : []);
                }
                continue;
            }

            // "2022/10/22(土) 三重県営サンアリーナ" / "2022.11.22(火) ..." 形式（年/月/日、年.月.日）
            if (preg_match('/^(\d{4})[.\/](\d{1,2})[.\/](\d{1,2})\s*(?:\([^)]*\))?\s+(.+)$/u', $line, $m)) {
                $currentYear = (int)$m[1];
                $lastMonth = (int)$m[2];
                [$venue, $part] = $this->splitVenueNote($m[4]);
                $date = $this->buildDate($currentYear, (int)$m[2], (int)$m[3]);
                if ($date && $venue !== '' && !static::cancelledOn($m[4], $date)) {
                    $entries[] = ['date' => $date, 'venue' => $venue] + ($part !== null ? ['part' => $part] : []);
                }
                continue;
            }

            // "06.18(月) Zepp Sapporo" / "05.14(土) マリンメッセ福岡A館" / "06/18(月) Zepp Sapporo" 形式
            if (preg_match('/^(\d{1,2})[.\/](\d{1,2})\s*(?:\([^)]*\))?\s+(.+)$/u', $line, $m)) {
                $advanceYearIfMonthWrapped((int)$m[1]);
                [$venue, $part] = $this->splitVenueNote($m[3]);
                $date = $this->buildDate($currentYear, (int)$m[1], (int)$m[2]);
                if ($date && $venue !== '' && !static::cancelledOn($m[3], $date)) {
                    $entries[] = ['date' => $date, 'venue' => $venue] + ($part !== null ? ['part' => $part] : []);
                }
                continue;
            }

            // "9.28,29 大阪ミューズホール" / "9/28,29 大阪ミューズホール"（複数日で同一会場）形式
            if (preg_match('/^(\d{1,2})[.\/](\d{1,2})(?:,(\d{1,2}))+\s+(.+)$/u', $line, $m)) {
                preg_match('/^(\d{1,2})[.\/]((?:\d{1,2},?)+)\s+(.+)$/u', $line, $m2);
                $month = (int)$m2[1];
                $advanceYearIfMonthWrapped($month);
                [$venue, $part] = $this->splitVenueNote($m2[3]);
                $days = explode(',', $m2[2]);
                foreach ($days as $day) {
                    $date = $this->buildDate($currentYear, $month, (int)$day);
                    if ($date && $venue !== '' && !static::cancelledOn($m2[3], $date)) {
                        $entries[] = ['date' => $date, 'venue' => $venue] + ($part !== null ? ['part' => $part] : []);
                    }
                }
                continue;
            }
        }

        return $entries;
    }

    // 中止・延期の公演か（その日にやっていない公演）。スケジュールの表にはそのまま出すが、公演日の選択肢・会場の集計などには入れない。
    // 「(中止)」「※公演中止」は書いてある行の公演が中止。「(26日中止)」「(※7/14は公演中止)」のように日が書いてあれば、その日だけ。
    // 「(公演延期)」「(9.4,5に延期)」も入れない。「(3/7から延期)」は延期してきた公演（その日にやった）なので入れる
    public static function cancelledOn(string $text, string $date): bool
    {
        if (str_contains($text, '延期') && !str_contains($text, 'から延期')) {
            return true;
        }
        if (!str_contains($text, '中止')) {
            return false;
        }
        if (!preg_match_all('/(?:(\d{1,2})[\/.月])?(\d{1,2})日?\s*(?:は|が)?\s*(?:公演)?中止/u', $text, $matches, PREG_SET_ORDER)) {
            return true;
        }
        foreach ($matches as $m) {
            if ((int) $m[2] === (int) substr($date, 8, 2) && ($m[1] === '' || (int) $m[1] === (int) substr($date, 5, 2))) {
                return true;
            }
        }

        return false;
    }

    // スケジュールの会場の欄から、うしろのかっこ書き（延期・振替・県名などの注記）を外す。
    // 「(1部)」「(2部)」「(昼公演)」「(夜公演)」のように同じ日の何回目かを書いたものだけは、捨てずに [会場名, '1部'] として返す（パターンで回まで選べるように）
    private function splitVenueNote(string $text): array
    {
        $part = null;
        if (preg_match('/[（(]\s*(\d+\s*部|昼公演|夜公演)\s*[）)]\s*$/u', $text, $m)) {
            $part = preg_replace('/\s+/u', '', $m[1]);
        }

        return [trim(preg_replace('/(?:[（(][^）)]*[）)]|【[^】]*】)\s*$/u', '', $text)), $part];
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
