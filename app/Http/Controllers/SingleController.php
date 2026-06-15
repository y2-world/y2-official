<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Artist;
use App\Models\Single;
use App\Models\Song;

class SingleController extends Controller
{
    public function index($artistId)
    {
        $artist = Artist::findOrFail($artistId);
        $singles = Single::where('artist_id', $artistId)
            ->orderBy('date', 'asc')
            ->paginate(10);
        $totalCount = $singles->total();
        $bios = $artist->bios()->get();

        if (request()->wantsJson() || request()->ajax()) {
            $html = view('singles._list', compact('singles', 'totalCount'))->render();
            return response()->json([
                'html' => $html,
                'next_page_url' => $singles->nextPageUrl(),
                'current_page' => $singles->currentPage(),
                'last_page' => $singles->lastPage(),
            ]);
        }

        return view('singles.index', compact('singles', 'bios', 'totalCount', 'artist'));
    }

    public function show($id)
    {
        $singles = Single::findOrFail($id);
        $artist = $singles->artist;
        $songs = Song::where('artist_id', $singles->artist_id)->get()->keyBy('id');
        $previous = Single::where('artist_id', $singles->artist_id)->where('id', '<', $singles->id)->orderBy('id', 'desc')->first();
        $next = Single::where('artist_id', $singles->artist_id)->where('id', '>', $singles->id)->orderBy('id')->first();

        return view('singles.show', compact('songs', 'singles', 'previous', 'next', 'artist'));
    }
}
