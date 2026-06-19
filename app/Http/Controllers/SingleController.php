<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Artist;
use App\Models\DbSingle;
use App\Models\DbSong;

class SingleController extends Controller
{
    public function index($artistId)
    {
        $artist = Artist::findOrFail($artistId);
        $singles = DbSingle::where('artist_id', $artistId)
            ->orderBy('date', 'asc')
            ->paginate(10);
        $totalCount = $singles->total();
        $bios = $artist->years;

        if (request()->wantsJson() || request()->ajax()) {
            $html = view('db_singles._list', compact('singles', 'totalCount'))->render();
            return response()->json([
                'html' => $html,
                'next_page_url' => $singles->nextPageUrl(),
                'current_page' => $singles->currentPage(),
                'last_page' => $singles->lastPage(),
            ]);
        }

        return view('db_singles.index', compact('singles', 'bios', 'totalCount', 'artist'));
    }

    public function show($id)
    {
        $singles = DbSingle::findOrFail($id);
        $artist = $singles->artist;
        $songs = DbSong::where('artist_id', $singles->artist_id)->get()->keyBy('id');
        $previous = DbSingle::where('artist_id', $singles->artist_id)->where('id', '<', $singles->id)->orderBy('id', 'desc')->first();
        $next = DbSingle::where('artist_id', $singles->artist_id)->where('id', '>', $singles->id)->orderBy('id')->first();

        return view('db_singles.show', compact('songs', 'singles', 'previous', 'next', 'artist'));
    }
}
