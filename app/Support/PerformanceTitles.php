<?php

namespace App\Support;

// 楽曲ページの表記ごとの絞り込み（All / 表記1 / 表記2）用。
// Databaseの楽曲ページ（DbSongController）とセットリストサイトの楽曲ページ（SlSongController）で共通に使う
class PerformanceTitles
{
    // 全角チルダ（～）と波ダッシュ（〜）、アポストロフィー（’ と '）の違いは入力の揺れなので、同じ表記として扱う
    public static function normalize(string $title): string
    {
        return str_replace(['～', '’', '‘'], ['〜', "'", "'"], trim($title));
    }

    // セットリストの項目のうち $matches に当たる曲が、どの表記で載っているか（別表記があればそれ、無ければ曲名）
    public static function in(array $entries, callable $matches, string $songTitle): array
    {
        $titles = [];
        foreach ($entries as $entry) {
            if (($entry['song'] ?? null) === null || !$matches($entry['song'])) {
                continue;
            }
            $alternative = self::normalize((string) ($entry['alternative_title'] ?? ''));
            $titles[] = $alternative !== '' ? $alternative : self::normalize($songTitle);
        }

        return array_values(array_unique($titles));
    }

    // 行ごとの表記の一覧から、ボタンに出す表記（古い順）を作る。表記が曲名の1つだけなら絞り込みは出さない
    // （元の曲名と違う表記でしか演奏していないときは、その表記で開けるように出す）
    public static function options(string $songTitle, array ...$titlesByRow): array
    {
        $titles = collect($titlesByRow)->flatten()->unique()->values()->all();
        if (count($titles) === 1 && $titles[0] === self::normalize($songTitle)) {
            return [];
        }

        return $titles;
    }

    // ?title= で指定された表記を選ぶ。空白の違いは無視し、大文字・小文字まで一致するものを優先する
    // （「I'll be」と「I'LL BE」のように大文字・小文字で書き分けている表記もあるため）
    public static function pick(array $options, ?string $wanted): ?string
    {
        if ($wanted === null || trim($wanted) === '') {
            return null;
        }
        $key = fn ($t) => preg_replace('/\s+/u', '', self::normalize((string) $t));
        $wantedKey = $key($wanted);

        return collect($options)->first(fn ($t) => $key($t) === $wantedKey)
            ?? collect($options)->first(fn ($t) => mb_strtolower($key($t)) === mb_strtolower($wantedKey));
    }
}
