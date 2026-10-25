<?php

namespace App\Support;

use Illuminate\Support\Str;

class EncoreFormGroups
{
    public static function fromItems(array $items): array
    {
        $groups = [];

        if (!$items) {
            return [[
                '_uuid' => (string) Str::uuid(),
                'songs' => [],
            ]];
        }

        foreach (array_values($items) as $index => $item) {
            if ($index === 0 || !empty($item['encore_block_start'])) {
                $groups[] = [
                    '_uuid' => (string) Str::uuid(),
                    'songs' => [],
                ];
            }

            $groups[array_key_last($groups)]['songs'][] = $item;
        }

        return $groups;
    }

    public static function toItems(array $groups): array
    {
        $items = [];
        $hasSongs = false;

        foreach (array_values($groups) as $group) {
            $songs = array_values((array) ($group['songs'] ?? []));
            if (!$songs) continue;

            if ($hasSongs) {
                $songs[0]['encore_block_start'] = true;
            } else {
                unset($songs[0]['encore_block_start']);
            }

            $items = array_merge($items, $songs);
            $hasSongs = true;
        }

        return $items;
    }

}
