<?php

namespace App\Support;

// スタンプ帳の「聴いた順」の並び替えに使う、初めて聴いたときの印。「公演日|セットリスト（参加記録）|曲順」の文字列で、小さいほど先。
// 同じ日の公演（1部・2部など）は登録した順、同じ公演の中は曲順
class StampHeardOrder
{
    public static function key($date, $id, int $position): string
    {
        $date = $date ? \Illuminate\Support\Carbon::parse($date)->toDateString() : '9999-12-31';

        return $date . '|' . sprintf('%010d', (int) $id) . '|' . sprintf('%04d', $position);
    }
}
