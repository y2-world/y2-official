<?php

namespace App\Http\Controllers;

use App\Models\DbAlbum;
use App\Models\Artist;
use App\Models\DbSong;
use Illuminate\Http\Request;

class AlbumController extends Controller
{
    public function index($artistId)
    {
        $artist = Artist::findOrFail($artistId);
        $albums = DbAlbum::where('artist_id', $artistId)
            ->orderBy('date', 'asc')
            ->paginate(10);
        $totalCount = $albums->total();
        $bios = $artist->years;

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
        $albums = DbAlbum::findOrFail($id);
        $artist = $albums->artist;
        $songs = DbSong::where('artist_id', $albums->artist_id)->get()->keyBy('id');
        $previous = DbAlbum::where('artist_id', $albums->artist_id)->where('id', '<', $albums->id)->orderBy('id', 'desc')->first();
        $next = DbAlbum::where('artist_id', $albums->artist_id)->where('id', '>', $albums->id)->orderBy('id')->first();

        return view('albums.show', compact('songs', 'albums', 'previous', 'next', 'artist'));
    }
}
