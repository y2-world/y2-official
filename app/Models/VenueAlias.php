<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// 会場の別名（改称する前の名前・書き方の違い）=> 今の会場名。名前は DbConcert::venueKeyword の形（うしろのかっこ書きを外したもの）で引く
class VenueAlias extends Model
{
    protected $fillable = ['alias', 'venue'];

    // [別名 => 今の会場名]。1回のページ表示の間は覚えておく
    public static function map(): array
    {
        static $map = null;

        return $map ??= static::pluck('venue', 'alias')->map(fn ($venue) => trim($venue))->all();
    }

    // 会場名を、まとめた会場の名前にする（別名でなければそのまま）。表に無い名前も、空白・「・」・全角半角・注記の違いだけなら同じ会場にする
    // （Yuki's Setlists・参加記録にしか無い書き方。「ザ・ヒロサワシティ会館」→「ザ・ヒロサワ・シティ会館」の会場）
    public static function resolve(string $venue): string
    {
        static $byKey = null;
        if (isset(static::map()[$venue])) {
            return static::map()[$venue];
        }
        if ($byKey === null) {
            $byKey = [];
            foreach (static::map() as $alias => $current) {
                $byKey[static::key($alias)] ??= $current;
                $byKey[static::key($current)] ??= $current;
            }
        }

        return $byKey[static::key($venue)] ?? $venue;
    }

    // 意味の変わらない違い（全角半角・空白・「・」・注記の《出演者》＜ゲスト＞・うしろの「(都道府県)」）を除いた形
    public static function key(string $venue): string
    {
        $key = \Normalizer::normalize($venue, \Normalizer::FORM_KC) ?: $venue;
        $key = preg_replace('/《[^》]*》|<[^>]*>|＜[^＞]*＞/u', '', $key);
        $key = preg_replace('/\((?:北海道|東京都|大阪府|京都府|\S{2,3}県)\)/u', '', $key);
        $key = preg_replace('/[\s・･]/u', '', $key);

        return mb_strtolower(str_replace('＆', '&', $key));
    }

    // 参加した公演の会場（[[会場名, 日付], …]）を会場ごとに数える。会場名は、その中で最後に使われた名前。多い順、同じ数なら最後の公演が新しい順
    public static function countByVenue(iterable $rows): \Illuminate\Support\Collection
    {
        return collect($rows)
            ->map(fn ($row) => ['name' => trim((string) $row[0]), 'date' => (string) $row[1]])
            ->filter(fn ($row) => $row['name'] !== '')
            ->groupBy(fn ($row) => static::resolve($row['name']))
            ->map(fn ($group) => (object) [
                'venue' => $group->sortBy('date')->last()['name'],
                'count' => $group->count(),
                'last' => $group->max('date'),
            ])
            ->sort(fn ($a, $b) => [$b->count, $b->last] <=> [$a->count, $a->last])
            ->values();
    }

    // 今の会場名 => [別名, …]
    public static function aliasesByVenue(): array
    {
        $byVenue = [];
        foreach (static::map() as $alias => $venue) {
            $byVenue[$venue][] = $alias;
        }

        return $byVenue;
    }
}
