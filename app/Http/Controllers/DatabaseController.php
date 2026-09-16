<?php

namespace App\Http\Controllers;

use App\Models\Artist;

class DatabaseController extends Controller
{
    public function index()
    {
        // type=0のみが「ツアー」（1=単発ライブ、2=イベント、3=ap bank fes、4=ソロは別カウント対象）
        $artists = Artist::where('visible', 1)->whereHas('songs')
            ->withCount(['songs', 'tours as tours_count' => fn ($q) => $q->where('type', 0)])
            ->orderBy('id', 'asc')
            ->get();
        return view('database.index', compact('artists'));
    }

    public function show($artistId)
    {
        $artist = Artist::findOrFail($artistId);
        $bios = $artist->years;
        return view('database.artist', compact('artist', 'bios'));
    }
}
