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

    // 会場名を今の会場名にする（別名でなければそのまま）
    public static function resolve(string $venue): string
    {
        return static::map()[$venue] ?? $venue;
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
