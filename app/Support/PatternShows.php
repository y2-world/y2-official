<?php

namespace App\Support;

use App\Models\DbConcert;

// パターンごとの公演（演奏回数を公演数で数えるため）。[パターンの id => [「ツアーid|公演日」…]]
// 公演日（schedule_dates）があればその公演。無ければタイトルの日付（「7.17 大阪」「4.4-5.17」）に当たる公演。
// どちらも無いパターンは、同じ段のほかのパターンに入っていない公演を全部（どれをやったか分からないので、どれもやったことにする）。
// スケジュールが無い・残りの公演も無いパターンは1公演。
// 日替わりの候補も、パターンの公演の全部で演奏したことにする（どの日にどちらをやったか分からないため）
class PatternShows
{
    public static function of(iterable $setlists): array
    {
        $byTourRow = [];
        foreach ($setlists as $setlist) {
            $byTourRow[$setlist->tour_id][(int) ($setlist->row ?? 1)][] = $setlist;
        }
        $tours = DbConcert::whereIn('id', array_keys($byTourRow))->get()->keyBy('id');

        $shows = [];
        foreach ($byTourRow as $tourId => $rows) {
            $entries = $tours[$tourId]?->parseScheduleEntries() ?? [];
            $allKeys = array_values(array_unique(array_map([PatternTitle::class, 'key'], $entries)));
            foreach ($rows as $patterns) {
                $covered = [];
                $undated = [];
                foreach ($patterns as $pattern) {
                    $keys = array_values(array_filter(array_map('strval', (array) ($pattern->schedule_dates ?? []))));
                    if ($keys === []) {
                        $keys = array_map([PatternTitle::class, 'key'], SubtitleScheduleDates::entries((string) $pattern->subtitle, $entries));
                    }
                    if ($keys === []) {
                        $undated[] = $pattern;
                        continue;
                    }
                    $shows[$pattern->id] = $keys;
                    $covered += array_flip($keys);
                }
                $rest = array_values(array_filter($allKeys, fn ($key) => !isset($covered[$key])));
                foreach ($undated as $pattern) {
                    $shows[$pattern->id] = $rest !== [] ? $rest : ['pattern-' . $pattern->id];
                }
            }
        }

        // ツアーをまたいで同じ公演日があっても別の公演なので、ツアーを付けて数える
        foreach ($setlists as $setlist) {
            if (isset($shows[$setlist->id])) {
                $shows[$setlist->id] = array_map(fn ($key) => $setlist->tour_id . '|' . $key, $shows[$setlist->id]);
            }
        }

        return $shows;
    }
}
