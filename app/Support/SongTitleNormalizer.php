<?php

namespace App\Support;

class SongTitleNormalizer
{
    // SlSong/DbSong間のタイトル完全一致照合で表記揺れ（全角/半角、アポストロフィの種類など）
    // による false negative を防ぐための正規化。曲の同一性判定以外の用途（表示等）では使わない。
    public static function normalize(string $title): string
    {
        $title = mb_convert_kana($title, 'as'); // 全角英数・記号 → 半角
        $title = str_replace(['’', '‘'], "'", $title);
        $title = str_replace(['＝'], '=', $title);
        $title = str_replace(['〜', '～'], '~', $title); // 波ダッシュ・全角チルダを統一
        $title = preg_replace('/\s+/u', ' ', $title);
        return mb_strtolower(trim($title));
    }
}
