<?php

namespace App\Support;

use App\Models\Artist;
use App\Models\SlSetlist;

// Yuki's Setlists のアーティストの選択欄の Live / Fes。
// Live：単独ライブのセットリストがある。Fes：フェスのセットリスト（各ステージの「アーティスト」）にだけ出てくる。
// どちらにも無いアーティスト（セットリストが無い）と、公開していないアーティスト（「公開」＝選択欄に出すか）は出さない
class SetlistArtistGroups
{
    public static function groups(): array
    {
        $setlists = SlSetlist::get(['artist_id', 'fes', 'fes_setlist', 'fes_encore']);
        $liveIds = $setlists->where('fes', 0)->pluck('artist_id')->filter()->map(fn ($id) => (int) $id)->flip()->all();
        $fesIds = [];
        foreach ($setlists->whereIn('fes', [1, 2]) as $setlist) {
            foreach (array_merge((array) $setlist->fes_setlist, (array) $setlist->fes_encore) as $item) {
                if (is_numeric($item['artist'] ?? null) && !isset($liveIds[(int) $item['artist']])) $fesIds[(int) $item['artist']] = true;
            }
        }
        $artists = Artist::orderBy('id')->where('visible', 1)->whereIn('id', array_merge(array_keys($liveIds), array_keys($fesIds)))->get();

        return [
            'Live' => $artists->filter(fn ($a) => isset($liveIds[$a->id]))->values(),
            'Fes' => $artists->filter(fn ($a) => isset($fesIds[$a->id]))->values(),
        ];
    }
}
