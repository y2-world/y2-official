<?php

namespace App\Support;

// メドレーのタイトル（セットリストの曲の medley_title）。
// 管理画面ではメドレーにチェックした曲（メドレーの2曲目以降）で入力するので、表示の前にメドレーの1曲目
// （メドレーのチェックが無い、直前の曲）へ移す。表示は1曲目のところで、タイトルの行に曲番を付けて出す
class Medley
{
    public static function liftTitles(array $items): array
    {
        $items = array_values($items);
        $head = null;
        foreach ($items as $i => $item) {
            if (empty($item['medley'])) {
                $head = $i;
                continue;
            }
            $title = trim((string) ($item['medley_title'] ?? ''));
            unset($items[$i]['medley_title']);
            if ($title !== '' && $head !== null && trim((string) ($items[$head]['medley_title'] ?? '')) === '') {
                $items[$head]['medley_title'] = $title;
            }
        }

        return $items;
    }
}
