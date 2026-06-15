<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Bio;
use App\Models\Single;
use App\Models\Album;
use App\Models\Song;
use App\Models\Tour;

class BioController extends Controller
{
    public function index($artistId)
    {
        $artist = Artist::findOrFail($artistId);
        $bios = $artist->bios()->get();
        return view('database.artist', compact('artist', 'bios'));
    }

    public function show($artistId, $year)
    {
        $artist = Artist::findOrFail($artistId);
        $bio = Bio::where('artist_id', $artistId)->where('year', $year)->first();

        if (!$bio) {
            abort(404);
        }

        $singles = Single::where('artist_id', $artistId)->orderBy('id', 'asc')->get();
        $albums = Album::where('artist_id', $artistId)->orderBy('id', 'asc')->get();
        $bios = Bio::where('artist_id', $artistId)->orderBy('year', 'asc')->get();
        $songs = Song::where('artist_id', $artistId)->where('year', $bio->year)->orderBy('id', 'asc')->get();

        $tours = Tour::where('artist_id', $artistId)
            ->whereRaw('YEAR(date1) = ? OR YEAR(date2) = ?', [$year, $year])
            ->orderBy('date1', 'asc')
            ->get();

        return view('database.show', [
            'artist' => $artist,
            'bio' => $bio,
            'singles' => $singles,
            'albums' => $albums,
            'bios' => $bios,
            'songs' => $songs,
            'tours' => $tours,
        ]);
    }
}
