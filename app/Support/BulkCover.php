<?php

namespace App\Support;

// 一括入力の「曲名 / アーティスト名」（カバー・別名義で歌った曲）。前後に空白のある「 / 」（全角の「／」も）の、いちばん後ろで分ける。
// 「AC/DC」のように空白の無いスラッシュは分けない。曲名に「 / 」がある曲（両A面など）は、呼ぶ側で先に行ごと照らし合わせる
class BulkCover
{
    /** @return array{0: string, 1: string}|null [曲名, アーティスト名] */
    public static function split(string $line): ?array
    {
        $parts = preg_split('/\s+[\/／]\s+/u', trim($line));
        if (count($parts) < 2) {
            return null;
        }
        $artist = trim((string) array_pop($parts));
        $title = trim(implode(' / ', $parts));

        return $title !== '' && $artist !== '' ? [$title, $artist] : null;
    }
}
