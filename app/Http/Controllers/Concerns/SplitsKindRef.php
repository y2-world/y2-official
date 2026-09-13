<?php

namespace App\Http\Controllers\Concerns;

trait SplitsKindRef
{
    // "official-{id}" / "user-{id}" 形式の文字列を ['official'|'user', $id] に分解する。
    // 公式データ（artists/db_concerts/db_setlists）とユーザー登録データ（user_artists/...）を
    // 同じURL・同じ画面上で区別するための共通ヘルパー。
    private function splitRef(string $ref): array
    {
        $parts = explode('-', $ref, 2);
        abort_if(count($parts) !== 2 || !in_array($parts[0], ['official', 'user'], true), 404);
        return $parts;
    }
}
