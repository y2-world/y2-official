<?php

namespace App\Http\Controllers;

use App\Models\Artist;

class DatabaseController extends Controller
{
    public function index()
    {
        // type=0のみが「ツアー」（1=単発ライブ、2=イベント、3=ap bank fes、4=ソロは別カウント対象）
        // 公開していて曲が登録されているアーティストを、名前順（日本語の読み順）に
        $artists = \App\Support\JapaneseNameSorter::sortBy(Artist::where('visible', 1)->whereHas('songs')
            ->withCount(['songs', 'tours as tours_count' => fn ($q) => $q->where('type', 0)])
            ->get(), 'name');
        return view('database.index', compact('artists'));
    }

    public function show($artistId)
    {
        $artist = Artist::findOrFail($artistId);
        $bios = $artist->years;
        return view('database.artist', compact('artist', 'bios'));
    }
}
