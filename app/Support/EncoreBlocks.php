<?php

namespace App\Support;

// アンコールを「ENCORE / DOUBLE ENCORE / TRIPLE ENCORE」のブロックに分ける。
// アンコールの曲に encore_block_start（ここから次のアンコール）が付いていれば、そこで区切る。
// 付いていない既存データは、今までどおり1つのENCOREとして扱われる。
class EncoreBlocks
{
    public const START_FLAG = 'encore_block_start';

    public const DOUBLE_ENCORE = 1;

    // 福山雅治はDOUBLE ENCOREで弾き語りをするため、そのブロックの曲に弾き語りアイコンを出し、
    // DOUBLE ENCOREの統計を出す
    public const HIKIGATARI_ARTIST_ID = 5;

    public static function label(int $block): string
    {
        return ['ENCORE', 'DOUBLE ENCORE', 'TRIPLE ENCORE'][$block] ?? ($block + 1) . 'TH ENCORE';
    }

    // アンコールの各曲が何番目のブロックか（0 = ENCORE, 1 = DOUBLE ENCORE, ...）を、曲の並びと同じ順で返す
    public static function blockIndexes(array $encore): array
    {
        $block = 0;
        $indexes = [];
        foreach (array_values($encore) as $i => $item) {
            if ($i > 0 && !empty($item[self::START_FLAG])) {
                $block++;
            }
            $indexes[] = $block;
        }

        return $indexes;
    }

    // [['label' => null, 'block' => null, 'items' => 本編], ['label' => 'ENCORE', 'block' => 0, 'items' => [...]], ...]
    public static function sections(array $setlist, array $encore): array
    {
        $sections = [['label' => null, 'block' => null, 'items' => $setlist]];
        $indexes = self::blockIndexes($encore);
        foreach (array_values($encore) as $i => $item) {
            $block = $indexes[$i];
            if (!isset($sections[$block + 1])) {
                $sections[$block + 1] = ['label' => self::label($block), 'block' => $block, 'items' => []];
            }
            $sections[$block + 1]['items'][] = $item;
        }

        return array_values($sections);
    }

    // DOUBLE ENCORE以降の曲を除いたアンコール（統計の「DOUBLE ENCOREを除く」用）
    public static function withoutDoubleEncore(array $encore): array
    {
        $indexes = self::blockIndexes($encore);

        return array_values(array_filter(array_values($encore), fn ($item, $i) => $indexes[$i] < self::DOUBLE_ENCORE, ARRAY_FILTER_USE_BOTH));
    }

    // DOUBLE ENCORE以降の曲だけ
    public static function doubleEncore(array $encore): array
    {
        $indexes = self::blockIndexes($encore);

        return array_values(array_filter(array_values($encore), fn ($item, $i) => $indexes[$i] >= self::DOUBLE_ENCORE, ARRAY_FILTER_USE_BOTH));
    }
}
