<?php

namespace App\Support;

use Illuminate\Support\Str;

// Filament の繰り返しの欄（Repeater）は、行を UUID のキーで見分ける。
// プログラムで行を入れ直すとき（一括入力の反映など）に 0, 1, 2… のキーにすると、ブラウザ側で行の見分けがずれ、
// メドレーのチェックでタイトルの欄が出ない・曲番が変わらない、といったことが起きる。行の _uuid をキーにする
class RepeaterKeys
{
    // $nested：行の中の繰り返しの欄（フェスのステージの songs など）も同じようにする
    public static function byUuid(array $items, ?string $nested = null): array
    {
        $keyed = [];
        foreach ($items as $item) {
            if (!is_array($item)) continue;
            $item['_uuid'] = filled($item['_uuid'] ?? null) ? (string) $item['_uuid'] : (string) Str::uuid();
            if ($nested !== null && is_array($item[$nested] ?? null)) {
                $item[$nested] = static::byUuid($item[$nested]);
            }
            $keyed[$item['_uuid']] = $item;
        }

        return $keyed;
    }
}
