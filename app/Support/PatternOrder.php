<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

// 段の中のパターンを公演日の順に並べ、パターン番号（order_no）を 1 から振り直す。
// 公演日はスケジュールから選んだ公演日（schedule_dates の最初）、無ければタイトルの「7.17 大阪」から読む。
// 日付が読めないパターン（A・B など）は、いま保存したものなら入力した番号の位置（空欄なら段の最後）に置く。
// ほかの日付が読めないパターンは、番号ではなく「すぐ前にあったパターンの後ろ」に置いたままにする
// （番号のままだと、日付のあるパターンを足したときに段の最後にあった B が途中にずれる）。
// 段のグループタイトルはパターン番号に結び付いているので、並び直しても同じパターンに付いたままにする
class PatternOrder
{
    // $pinned: [いま保存したパターンの id => 入力した番号（null なら段の最後）]
    public static function reorder($tourId, $row, array $pinned = []): void
    {
        $tour = AdminData::query('concert')->find($tourId);
        $patterns = AdminData::query('setlist')->ofTour($tourId)->where('row', $row)->orderBy('order_no')->get();
        if (!$tour || $patterns->isEmpty()) {
            return;
        }

        $dated = [];
        $undated = [];
        $pinnedUndated = [];
        $previousId = null;
        foreach ($patterns as $pattern) {
            $date = self::dateKey($pattern, $tour->date1);
            if ($date !== null) {
                $dated[] = [$date, (int) $pattern->order_no, $pattern];
            } elseif (array_key_exists($pattern->id, $pinned)) {
                $slot = $pinned[$pattern->id];
                $pinnedUndated[] = [$slot === null || $slot === '' ? PHP_INT_MAX : (int) $slot, $pattern];
                continue;
            } else {
                $undated[] = [$previousId, $pattern];
            }
            $previousId = $pattern->id;
        }
        usort($dated, fn ($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);
        $ordered = array_column($dated, 2);
        foreach ($undated as [$afterId, $pattern]) {
            $at = $afterId === null ? 0 : collect($ordered)->search(fn ($p) => $p->id === $afterId) + 1;
            array_splice($ordered, $at, 0, [$pattern]);
        }
        usort($pinnedUndated, fn ($a, $b) => $a[0] <=> $b[0]);
        foreach ($pinnedUndated as [$slot, $pattern]) {
            array_splice($ordered, max(0, min($slot - 1, count($ordered))), 0, [$pattern]);
        }

        $newNumbers = [];
        foreach ($ordered as $i => $pattern) {
            $newNumbers[$pattern->id] = $i + 1;
        }
        if ($patterns->every(fn ($p) => (int) $p->order_no === $newNumbers[$p->id])) {
            return;
        }

        $tourColumn = AdminData::column('setlistRow', 'tour_id');
        $titles = AdminData::query('setlistRow')->where($tourColumn, $tourId)->where('row', $row)->get()->keyBy('order_no');

        // 番号の重なりが許されない（ツアー・段・番号で1つ）ので、いったんマイナスの番号に逃がしてから振る
        DB::transaction(function () use ($patterns, $newNumbers, $titles) {
            foreach ($patterns as $pattern) {
                $title = $titles->get($pattern->order_no);
                $pattern->newQuery()->whereKey($pattern->id)->update(['order_no' => -$newNumbers[$pattern->id]]);
                $title?->newQuery()->whereKey($title->id)->update(['order_no' => -$newNumbers[$pattern->id]]);
            }
            // パターンの無い番号に残っていたグループタイトルは消す
            $titles->filter(fn ($title) => (int) $title->order_no > 0 && !$patterns->contains('order_no', $title->order_no))
                ->each(fn ($title) => $title->newQuery()->whereKey($title->id)->delete());
            foreach ($patterns as $pattern) {
                $pattern->newQuery()->whereKey($pattern->id)->update(['order_no' => $newNumbers[$pattern->id]]);
                $title = $titles->get($pattern->order_no);
                $title?->newQuery()->whereKey($title->id)->update(['order_no' => $newNumbers[$pattern->id]]);
            }
        });
    }

    // 保存する前の仮の番号（段の最後の次）。保存したあと reorder で振り直す
    public static function temporaryNumber($tourId, $row): int
    {
        return (int) AdminData::query('setlist')->ofTour($tourId)->where('row', $row ?: 1)->max('order_no') + 1;
    }

    // 並べるための公演日（「2003-08-07 1部」のような文字。読めなければ null）
    public static function dateKey($pattern, $tourDate): ?string
    {
        $scheduled = array_values(array_filter((array) ($pattern->schedule_dates ?? [])));
        if ($scheduled !== []) {
            return PatternTitle::partOrder((string) $scheduled[0]);
        }

        if (!preg_match('/(?:(\d{4})\/)?(?<!\d)(\d{1,2})\.(\d{1,2})(?!\d)/', (string) ($pattern->subtitle ?? ''), $m)) {
            return null;
        }
        $month = (int) $m[2];
        $day = (int) $m[3];
        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return null;
        }
        if ($m[1] !== '') {
            $year = (int) $m[1];
        } elseif ($tourDate) {
            // 年の書いていない日付は、ツアーの初日の年。初日の月より前の月なら翌年（年をまたぐツアー）
            $start = \Carbon\Carbon::parse($tourDate);
            $year = $start->year + ($month < $start->month ? 1 : 0);
        } else {
            $year = 0;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }
}
