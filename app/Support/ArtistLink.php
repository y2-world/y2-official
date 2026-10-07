<?php

namespace App\Support;

use App\Models\Artist;
use App\Models\UserArtist;
use Normalizer;

// 公式アーティスト（artists）と、マイページで作られた同じ名前のアーティスト（user_artists）を自動で結び付ける。
// 公式の Database に曲などが無いアーティストでも、マイページで登録された曲・シングル・アルバム・ライブを使って見せるため。
// 名前は大文字・小文字、空白、全角・半角の違いを無視して比べ、同じ名前が複数あれば曲の多い方を使う
class ArtistLink
{
    private static ?array $userArtistsByName = null;

    public static function normalize(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/u', '', Normalizer::normalize($name, Normalizer::FORM_KC)));
    }

    public static function userArtistFor(Artist $artist): ?UserArtist
    {
        return self::userArtistsByName()[self::normalize($artist->name)] ?? null;
    }

    private static function userArtistsByName(): array
    {
        if (self::$userArtistsByName === null) {
            self::$userArtistsByName = [];
            foreach (UserArtist::withCount('songs')->orderByDesc('songs_count')->orderBy('id')->get() as $userArtist) {
                self::$userArtistsByName[self::normalize($userArtist->name)] ??= $userArtist;
            }
        }

        return self::$userArtistsByName;
    }
}
