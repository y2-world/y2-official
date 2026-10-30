<?php

namespace App\Support;

use App\Models\Artist;
use App\Models\SlSetlist;

// Yuki's Setlists のアーティストの Live / Fes。
// Live：単独ライブのセットリストがある。Fes：フェスのセットリスト（各ステージの「アーティスト」）にだけ出てくる。
// どちらにも無いアーティスト（セットリストが無い）はどちらにも入らない
class SetlistArtistGroups
{
    // アーティストの選択欄（Yuki's Setlists のページ）。公開していないアーティスト（「公開」＝選択欄に出すか）は出さない。
    // 初めてライブに行った順（同じ日なら登録した順）。登録した順だけだと、あとから作ったアーティスト
    // （松本孝弘など、別名義の記録を移したもの）が最後になってしまう
    public static function groups(): array
    {
        $ids = static::ids();
        $first = static::firstDates();
        $artists = Artist::orderBy('id')->where('visible', 1)->whereIn('id', array_merge($ids['Live'], $ids['Fes']))->get()
            ->sortBy(fn ($artist) => [$first[$artist->id] ?? '9999-12-31', $artist->id]);

        return [
            'Live' => $artists->whereIn('id', $ids['Live'])->values(),
            'Fes' => $artists->whereIn('id', $ids['Fes'])->values(),
        ];
    }

    // アーティストごとに初めてライブに行った日（単独ライブ・フェスのどちらでも）
    private static function firstDates(): array
    {
        $dates = [];
        $mark = function ($artistId, $date) use (&$dates) {
            $date = substr((string) $date, 0, 10);
            if ($date !== '' && (!isset($dates[$artistId]) || $date < $dates[$artistId])) $dates[$artistId] = $date;
        };
        foreach (SlSetlist::get(['artist_id', 'date', 'fes', 'fes_setlist', 'fes_encore']) as $setlist) {
            if ($setlist->artist_id) $mark((int) $setlist->artist_id, $setlist->date);
            foreach (array_merge((array) $setlist->fes_setlist, (array) $setlist->fes_encore) as $item) {
                if (is_numeric($item['artist'] ?? null)) $mark((int) $item['artist'], $setlist->date);
            }
        }

        return $dates;
    }

    // Live / Fes それぞれのアーティストの id（公開かどうかは見ない。管理画面のアーティストの絞り込みにも使う）
    public static function ids(): array
    {
        $setlists = SlSetlist::get(['artist_id', 'fes', 'fes_setlist', 'fes_encore']);
        $liveIds = $setlists->where('fes', 0)->pluck('artist_id')->filter()->map(fn ($id) => (int) $id)->flip()->all();
        $fesIds = [];
        foreach ($setlists->whereIn('fes', [1, 2]) as $setlist) {
            foreach (array_merge((array) $setlist->fes_setlist, (array) $setlist->fes_encore) as $item) {
                if (is_numeric($item['artist'] ?? null) && !isset($liveIds[(int) $item['artist']])) $fesIds[(int) $item['artist']] = true;
            }
        }

        return ['Live' => array_keys($liveIds), 'Fes' => array_keys($fesIds)];
    }
}
