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

    // 1回聴いたことを足す。$stats[曲] = [first => 初めて聴いた印, last => 最後に聴いた印, shows => [公演 => true],
    // first_title・last_title => そのとき聴いた表記（セトリの alternative_title。'' は曲名のまま）, title_shows => [表記 => [公演 => true]]]。
    // 回数は公演の数（同じ公演で2回聴いても1回）
    public static function add(array &$stats, int $songId, $date, $showId, int $position, ?string $title = null): void
    {
        $key = static::key($date, $showId, $position);
        $title = trim((string) $title);
        static::merge($stats, $songId, [
            'first' => $key, 'last' => $key, 'shows' => [(string) $showId => true],
            'first_title' => $title, 'last_title' => $title, 'title_shows' => [$title => [(string) $showId => true]],
        ]);
    }

    // 別の曲（同じデータベースの曲につながる Yuki's Setlists の曲など）の記録を合わせる
    public static function merge(array &$stats, int $songId, array $stat): void
    {
        if (!isset($stats[$songId])) {
            $stats[$songId] = $stat;

            return;
        }
        if ($stat['first'] < $stats[$songId]['first']) {
            $stats[$songId]['first'] = $stat['first'];
            $stats[$songId]['first_title'] = $stat['first_title'];
        }
        if ($stat['last'] > $stats[$songId]['last']) {
            $stats[$songId]['last'] = $stat['last'];
            $stats[$songId]['last_title'] = $stat['last_title'];
        }
        $stats[$songId]['shows'] += $stat['shows'];
        foreach ($stat['title_shows'] as $title => $shows) {
            $stats[$songId]['title_shows'][$title] = ($stats[$songId]['title_shows'][$title] ?? []) + $shows;
        }
    }

    // いちばん多い表記（[表記 => [公演・ツアー => true]] の数で比べる）。同じ数なら $preferred（最後に聴いた・演奏した表記）
    public static function mostTitle(array $titleShows, string $preferred): string
    {
        $most = $preferred;
        foreach ($titleShows as $title => $shows) {
            if (count($shows) > count($titleShows[$most] ?? [])) {
                $most = (string) $title;
            }
        }

        return $most;
    }

    // スタンプに持たせる値（heard_order：First heard、last_heard：Last heard、heard_count：Most heard）。
    // First heard・Last heard・Most heard で並べたときは、そのとき聴いた表記（heard_first_title・heard_last_title・heard_most_title）で出す
    public static function attributes(?array $stat): array
    {
        return [
            'heard_order' => $stat['first'] ?? null,
            'last_heard' => $stat['last'] ?? null,
            'heard_count' => $stat ? count($stat['shows']) : 0,
            'heard_first_title' => $stat['first_title'] ?? '',
            'heard_last_title' => $stat['last_title'] ?? '',
            'heard_most_title' => $stat ? static::mostTitle($stat['title_shows'], $stat['last_title']) : '',
        ];
    }
}
