<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\DbSingle;
use App\Models\DbAlbum;
use App\Models\DbSong;
use App\Models\DbConcert;


class BioController extends Controller
{
    public function index($artistId)
    {
        $artist = Artist::findOrFail($artistId);
        $bios = $artist->years;
        return view('database.artist', compact('artist', 'bios'));
    }

    public function show($artistId, $year)
    {
        $artist = Artist::findOrFail($artistId);
        $bio = (object)['year' => $year, 'text' => null];
        $bios = $artist->years;
        // 前年以前のシングル・アルバム両方で既出の曲IDを収集（初出年判定用）
        $previousIds = collect();
        DbSingle::where('artist_id', $artistId)->whereYear('date', '<', $year)->get()
            ->each(function ($single) use (&$previousIds) {
                $previousIds = $previousIds->merge(
                    collect($single->tracklist ?? [])->pluck('id')->map(fn($id) => (string)$id)
                );
            });
        DbAlbum::where('artist_id', $artistId)->where('best', false)
            ->whereYear('date', '<', $year)->get()
            ->each(function ($album) use (&$previousIds) {
                $previousIds = $previousIds->merge(
                    collect($album->tracklist ?? [])->pluck('id')->filter()->map(fn($id) => (string)$id)
                );
            });
        $previousIds = $previousIds->unique();
        // シングルの曲：初出年のみ表示
        $singleSongIds = collect();
        DbSingle::where('artist_id', $artistId)->whereYear('date', $year)->get()
            ->each(function ($single) use (&$singleSongIds, $previousIds) {
                $singleSongIds = $singleSongIds->merge(
                    collect($single->tracklist ?? [])->pluck('id')->map(fn($id) => (string)$id)->diff($previousIds)
                );
            });
        // アルバムの曲：初出年のみ表示（ベスト含む）
        $albumSongIds = collect();
        DbAlbum::where('artist_id', $artistId)
            ->whereYear('date', $year)->get()
            ->each(function ($album) use (&$albumSongIds, $previousIds) {
                $ids = collect($album->tracklist ?? [])->pluck('id')->filter()->map(fn($id) => (string)$id);
                $albumSongIds = $albumSongIds->merge($ids->diff($previousIds));
            });
        $songIds = $singleSongIds->merge($albumSongIds)->unique()->filter()->values();
        $songs = DbSong::where('artist_id', $artistId)->whereIn('id', $songIds)->orderBy('id', 'asc')->get();

        $tours = DbConcert::where('artist_id', $artistId)
            ->where(function ($q) use ($year) {
                $q->whereRaw('YEAR(date1) = ?', [$year])
                  ->orWhereRaw('YEAR(date2) = ?', [$year]);
            })
            ->orderBy('date1', 'asc')
            ->get();

        return view('database.show', [
            'artist' => $artist,
            'bio' => $bio,
            'bios' => $bios,
            'songs' => $songs,
            'tours' => $tours,
        ]);
    }
}
