<?php

namespace App\Support;

// アンコールを「ENCORE 1 / ENCORE 2 / …」のブロックに分ける（2つ目以降のアンコールがDOUBLE ENCORE）。
// アンコールの曲に encore_block_start（ここから次のアンコール）が付いていれば、そこで区切る。
// 付いていない既存データは、今までどおり1つのENCOREとして扱われる。
class EncoreBlocks
{
    public const START_FLAG = 'encore_block_start';

    public const DOUBLE_ENCORE = 1;

    // 福山雅治はDOUBLE ENCOREで弾き語りをするため、そのブロックの曲に弾き語りアイコンを出し、
    // DOUBLE ENCOREの統計を出す
    public const HIKIGATARI_ARTIST_ID = 5;

    // アンコールが1つだけなら「ENCORE」、2つ以上あれば「ENCORE 1」「ENCORE 2」…
    public static function label(int $block, int $blockCount = 1): string
    {
        return $blockCount > 1 ? 'ENCORE ' . ($block + 1) : 'ENCORE';
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

    // [['label' => null, 'block' => null, 'items' => 本編], ['label' => 'ENCORE 1', 'block' => 0, 'items' => [...]], ...]
    public static function sections(array $setlist, array $encore): array
    {
        $sections = [['label' => null, 'block' => null, 'items' => $setlist]];
        $indexes = self::blockIndexes($encore);
        $blockCount = $indexes ? max($indexes) + 1 : 0;
        foreach (array_values($encore) as $i => $item) {
            $block = $indexes[$i];
            if (!isset($sections[$block + 1])) {
                $sections[$block + 1] = ['label' => self::label($block, $blockCount), 'block' => $block, 'items' => []];
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
