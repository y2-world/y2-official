<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Support\Biography;

// 公式のアーティストの Biography。中身は Biography（マイページと共通）で作る
class BioController extends Controller
{
    public function index($artistId)
    {
        $artist = Artist::findOrFail($artistId);
        $bios = Biography::years('official', $artist->id);
        return view('database.artist', compact('artist', 'bios'));
    }

    public function show($artistId, $year)
    {
        $artist = Artist::findOrFail($artistId);
        $bio = (object)['year' => $year, 'text' => null];
        $bios = Biography::years('official', $artist->id);
        ['songs' => $songs, 'tours' => $tours] = Biography::year('official', $artist->id, (int) $year);

        return view('database.show', [
            'artist' => $artist,
            'bio' => $bio,
            'bios' => $bios,
            'songs' => $songs,
            'tours' => $tours,
        ]);
    }
}
