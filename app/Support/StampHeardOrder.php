<?php

namespace App\Support;

// スタンプ帳の並べ替え（First heard・Most heard・Last heard）に使う、曲ごとの聴いた記録。
// 印は「公演日|セットリスト（参加記録）|曲順」の文字列で、小さいほど先。同じ日の公演（1部・2部など）は登録した順、同じ公演の中は曲順
class StampHeardOrder
{
    public static function key($date, $id, int $position): string
    {
        $date = $date ? \Illuminate\Support\Carbon::parse($date)->toDateString() : '9999-12-31';

        return $date . '|' . sprintf('%010d', (int) $id) . '|' . sprintf('%04d', $position);
    }

    // 1回聴いたことを足す。$stats[曲] = [first => 初めて聴いた印, last => 最後に聴いた印, shows => [公演 => true]]。
    // 回数は公演の数（同じ公演で2回聴いても1回）
    public static function add(array &$stats, int $songId, $date, $showId, int $position): void
    {
        $key = static::key($date, $showId, $position);
        $stat = $stats[$songId] ?? ['first' => $key, 'last' => $key, 'shows' => []];
        $stat['first'] = min($stat['first'], $key);
        $stat['last'] = max($stat['last'], $key);
        $stat['shows'][(string) $showId] = true;
        $stats[$songId] = $stat;
    }

    // 別の曲（同じデータベースの曲につながる Yuki's Setlists の曲など）の記録を合わせる
    public static function merge(array &$stats, int $songId, array $stat): void
    {
        if (!isset($stats[$songId])) {
            $stats[$songId] = $stat;

            return;
        }
        $stats[$songId]['first'] = min($stats[$songId]['first'], $stat['first']);
        $stats[$songId]['last'] = max($stats[$songId]['last'], $stat['last']);
        $stats[$songId]['shows'] += $stat['shows'];
    }

    // スタンプに持たせる値（heard_order：First heard、last_heard：Last heard、heard_count：Most heard）
    public static function attributes(?array $stat): array
    {
        return [
            'heard_order' => $stat['first'] ?? null,
            'last_heard' => $stat['last'] ?? null,
            'heard_count' => $stat ? count($stat['shows']) : 0,
        ];
    }
}
