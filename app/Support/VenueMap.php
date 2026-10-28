<?php

namespace App\Support;

use App\Models\VenuePrefecture;
use Illuminate\Support\Collection;

// Statistics の Map：会場ごとの回数（[会場 => 回数]）を、都道府県ごとにまとめる
class VenueMap
{
    // 濃さの段階（この回数以上で次の段階）
    public const LEVELS = [1, 2, 5, 20];

    // 戻り値：prefectures（[コード => [code, name, overseas, count, level, venues => [[venue, count], …]]]、回数の多い順）、
    // unassigned（都道府県が決まっていない会場）。海外は国ごとに「country:国の名前」のコードでまとめる
    public static function build(Collection $venueCounts): array
    {
        $rows = VenuePrefecture::whereIn('venue', $venueCounts->keys()->all())->get(['venue', 'prefecture', 'country'])->keyBy('venue');
        $prefectures = [];
        $unassigned = [];
        foreach ($venueCounts->sortDesc() as $venue => $count) {
            $row = $rows[$venue] ?? null;
            $code = $row?->prefecture;
            $country = trim((string) $row?->country);
            if ($code === VenuePrefecture::OVERSEAS && $country !== '') {
                [$code, $name, $overseas] = ['country:' . $country, $country, true];
            } elseif ($code && $code !== VenuePrefecture::OVERSEAS && isset(VenuePrefecture::NAMES[$code])) {
                [$name, $overseas] = [VenuePrefecture::NAMES[$code], false];
            } else {
                // 都道府県が空、または海外で国が決まっていない
                $unassigned[] = ['venue' => $venue, 'count' => $count];
                continue;
            }
            $prefectures[$code] ??= ['code' => $code, 'name' => $name, 'overseas' => $overseas, 'count' => 0, 'venues' => []];
            $prefectures[$code]['count'] += $count;
            $prefectures[$code]['venues'][] = ['venue' => $venue, 'count' => $count];
        }
        foreach ($prefectures as &$p) {
            $p['level'] = static::level($p['count']);
        }
        unset($p);
        uasort($prefectures, fn ($a, $b) => $b['count'] <=> $a['count'] ?: strcmp($a['code'], $b['code']));

        return ['prefectures' => $prefectures, 'unassigned' => $unassigned];
    }

    public static function level(int $count): int
    {
        $level = 0;
        foreach (static::LEVELS as $i => $min) {
            if ($count >= $min) $level = $i + 1;
        }

        return $level;
    }

    // 地図の形（resources/data/japan_prefectures.json）
    public static function shapes(): array
    {
        return json_decode(file_get_contents(resource_path('data/japan_prefectures.json')), true);
    }
}
