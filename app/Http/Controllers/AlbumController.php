<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use Illuminate\Http\Request;

class AlbumController extends Controller
{
    public function index($artistId)
    {
        $artist = Artist::findOrFail($artistId);
        $albums = Album::where('artist_id', $artistId)
            ->orderBy('date', 'asc')
            ->paginate(10);
        $totalCount = $albums->total();
        $bios = $artist->bios()->get();

        if (request()->wantsJson() || request()->ajax()) {
            $html = view('albums._list', compact('albums', 'totalCount'))->render();
            return response()->json([
                'html' => $html,
                'next_page_url' => $albums->nextPageUrl(),
                'current_page' => $albums->currentPage(),
                'last_page' => $albums->lastPage(),
            ]);
        }

        return view('albums.index', compact('albums', 'bios', 'totalCount', 'artist'));
    }

    public function show($id)
    {
        $albums = Album::findOrFail($id);
        $artist = $albums->artist;
        $songs = Song::where('artist_id', $albums->artist_id)->get()->keyBy('id');
        $previous = Album::where('artist_id', $albums->artist_id)->where('id', '<', $albums->id)->orderBy('id', 'desc')->first();
        $next = Album::where('artist_id', $albums->artist_id)->where('id', '>', $albums->id)->orderBy('id')->first();

        return view('albums.show', compact('songs', 'albums', 'previous', 'next', 'artist'));
    }
}
