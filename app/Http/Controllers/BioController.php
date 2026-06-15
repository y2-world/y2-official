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
        // シングルに収録されている曲はシングルのリリース年で判定
        $singleSongIds = collect();
        Single::where('artist_id', $artistId)->whereYear('date', $year)->get()->each(function ($single) use (&$singleSongIds) {
            $singleSongIds = $singleSongIds->merge(collect($single->tracklist ?? [])->pluck('id'));
        });
        // 全シングルに収録されている曲IDを収集（アルバムの絞り込みに使用）
        $allSingleSongIds = collect();
        Single::where('artist_id', $artistId)->get()->each(function ($single) use (&$allSingleSongIds) {
            $allSingleSongIds = $allSingleSongIds->merge(collect($single->tracklist ?? [])->pluck('id'));
        });
        // アルバムにのみ収録されている曲はアルバムのリリース年で判定
        $albumSongIds = collect();
        Album::where('artist_id', $artistId)->where('best', false)->whereYear('date', $year)->get()->each(function ($album) use (&$albumSongIds, $allSingleSongIds) {
            $ids = collect($album->tracklist ?? [])->pluck('id')->filter()->map(fn($id) => (string)$id);
            $albumSongIds = $albumSongIds->merge($ids->diff($allSingleSongIds->map(fn($id) => (string)$id)));
        });
        $songIds = $singleSongIds->merge($albumSongIds)->unique()->filter()->values();
        $songs = Song::where('artist_id', $artistId)->whereIn('id', $songIds)->orderBy('id', 'asc')->get();

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
