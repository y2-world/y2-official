<?php

namespace App\Support;

use Collator;
use Illuminate\Support\Collection;

/**
 * Postgresのデフォルト照合順序はバイト列比較のため、ひらがな・カタカナが
 * 漢字より不自然に上位に来てしまう（MySQLのutf8mb4_unicode_ciでは起きなかった）。
 * ICUのCollatorで日本語として自然な順序（英数字→カナ→漢字）に並べ直す。
 */
class JapaneseNameSorter
{
    private static ?Collator $collator = null;

    private static function collator(): Collator
    {
        return self::$collator ??= new Collator('ja_JP');
    }

    /**
     * @param Collection<int, mixed> $items
     * @param string $key モデル/オブジェクトのソート対象プロパティ名
     * @return Collection<int, mixed>
     */
    public static function sortBy(Collection $items, string $key = 'name'): Collection
    {
        $collator = self::collator();

        return $items->sort(
            fn ($a, $b) => $collator->compare($a->{$key} ?? '', $b->{$key} ?? '')
        )->values();
    }

    /**
     * id => name の連想配列（Filamentのoptions()等）を、値（name）で日本語順に並べ替える。
     *
     * @param array<int|string, string> $options
     * @return array<int|string, string>
     */
    public static function sortOptions(array $options): array
    {
        $collator = self::collator();
        uasort($options, fn ($a, $b) => $collator->compare($a, $b));

        return $options;
    }
}
