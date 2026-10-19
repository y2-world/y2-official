<?php

namespace App\Support;

use App\Models\DbSetlist;
use App\Models\DbSetlistRow;
use App\Models\UserSetlist;
use Illuminate\Support\Collection;

// 曲ごとのツアー一覧で、ツアーの全パターンではやっていない曲のとき、ツアー名の下に出す「やったパターン」の名前。
// 段（グループ）のタイトルがあって、その段のパターンを全部やっていれば、パターン名の代わりに段のタイトル
//（段の区切りはセトリのページと同じ：DbSetlistRow の (row, order_no) からそのグループが始まる。マイページのアーティストのセトリには段のタイトルがない）
class PartialPatterns
{
    // $played：その曲が入っているパターン（DbSetlist か UserSetlist）。返り値：ツアー id（UserSetlist は user_concert_id） => [名前, ...]
    public static function of(Collection $played): array
    {
        if ($played->isEmpty()) {
            return [];
        }
        $isUser = $played->first() instanceof UserSetlist;
        $key = $isUser ? 'user_concert_id' : 'tour_id';
        $tourIds = $played->pluck($key)->unique();
        $patternsByTour = ($isUser ? UserSetlist::query() : DbSetlist::query())->whereIn($key, $tourIds)
            ->get(['id', $key, 'order_no', 'row', 'subtitle'])->groupBy($key);
        $groupTitlesByTour = $isUser ? collect() : DbSetlistRow::whereIn('tour_id', $tourIds)->get()->groupBy('tour_id');

        $result = [];
        foreach ($played->groupBy($key) as $tourId => $playedInTour) {
            $all = ($patternsByTour[$tourId] ?? collect())->sortBy(fn ($p) => [$p->row ?? 1, $p->order_no])->values();
            if ($playedInTour->count() >= $all->count()) {
                continue;
            }
            $playedIds = $playedInTour->pluck('id')->all();
            $label = fn ($p) => trim(preg_replace('/\s+/u', ' ', strip_tags((string) $p->subtitle))) ?: 'パターン' . ($all->search(fn ($x) => $x->id === $p->id) + 1);
            $titles = ($groupTitlesByTour[$tourId] ?? collect())->groupBy('row')->map(fn ($rows) => $rows->pluck('title', 'order_no'));
            $names = [];
            foreach ($all->groupBy(fn ($p) => $p->row ?? 1) as $row => $rowPatterns) {
                $rowTitles = $titles[$row] ?? collect();
                $groups = [];
                foreach ($rowPatterns->sortBy('order_no') as $p) {
                    if ($rowTitles->has($p->order_no) || empty($groups)) {
                        $groups[] = ['title' => $rowTitles[$p->order_no] ?? null, 'items' => []];
                    }
                    $groups[count($groups) - 1]['items'][] = $p;
                }
                foreach ($groups as $g) {
                    $playedInGroup = array_values(array_filter($g['items'], fn ($p) => in_array($p->id, $playedIds, true)));
                    if (!$playedInGroup) {
                        continue;
                    }
                    if ($g['title'] && count($playedInGroup) === count($g['items'])) {
                        $names[] = $g['title'];
                    } else {
                        foreach ($playedInGroup as $p) {
                            $names[] = $label($p);
                        }
                    }
                }
            }
            $result[$tourId] = $names;
        }

        return $result;
    }
}
