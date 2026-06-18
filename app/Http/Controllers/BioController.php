<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Single;
use App\Models\Album;
use App\Models\Song;
use App\Models\Tour;


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
        // 過去のアルバム（同年より前）に収録済みの曲IDを収集
        $previousAlbumSongIds = collect();
        Album::where('artist_id', $artistId)->where('best', false)->whereNotNull('album_id')
            ->whereYear('date', '<', $year)->get()
            ->each(function ($album) use (&$previousAlbumSongIds) {
                $previousAlbumSongIds = $previousAlbumSongIds->merge(
                    collect($album->tracklist ?? [])->pluck('id')->filter()->map(fn($id) => (string)$id)
                );
            });
        // アルバムにのみ収録されている曲はアルバムのリリース年で判定（初出年のみ表示）
        $albumSongIds = collect();
        Album::where('artist_id', $artistId)->where('best', false)->whereNotNull('album_id')->whereYear('date', $year)->get()->each(function ($album) use (&$albumSongIds, $allSingleSongIds, $previousAlbumSongIds) {
            $ids = collect($album->tracklist ?? [])->pluck('id')->filter()->map(fn($id) => (string)$id);
            $albumSongIds = $albumSongIds->merge(
                $ids->diff($allSingleSongIds->map(fn($id) => (string)$id))
                    ->diff($previousAlbumSongIds)
            );
        });
        $songIds = $singleSongIds->merge($albumSongIds)->unique()->filter()->values();
        $songs = Song::where('artist_id', $artistId)->whereIn('id', $songIds)->orderBy('id', 'asc')->get();

        $tours = Tour::where('artist_id', $artistId)
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
