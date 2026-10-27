<?php

namespace App\Support;

use App\Models\Artist;
use App\Models\SlSetlist;

// Yuki's Setlists のアーティストの Live / Fes。
// Live：単独ライブのセットリストがある。Fes：フェスのセットリスト（各ステージの「アーティスト」）にだけ出てくる。
// どちらにも無いアーティスト（セットリストが無い）はどちらにも入らない
class SetlistArtistGroups
{
    // アーティストの選択欄（Yuki's Setlists のページ）。公開していないアーティスト（「公開」＝選択欄に出すか）は出さない
    public static function groups(): array
    {
        $ids = static::ids();
        $artists = Artist::orderBy('id')->where('visible', 1)->whereIn('id', array_merge($ids['Live'], $ids['Fes']))->get();

        return [
            'Live' => $artists->whereIn('id', $ids['Live'])->values(),
            'Fes' => $artists->whereIn('id', $ids['Fes'])->values(),
        ];
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
