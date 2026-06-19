<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\DbSong;
use App\Models\DbConcert;
use App\Models\DbSetlist;

class LiveController extends Controller
{
    public function index($artistId)
    {
        $artist = Artist::findOrFail($artistId);
        $type = request()->input('type');

        $liveQuery = DbConcert::where('artist_id', $artistId)->orderBy('date1', 'desc');

        if ($type === '1') {
            $liveQuery->whereIn('type', [0, 1]);
        } elseif ($type === '6') {
            $liveQuery->where('type', 0);
        } elseif ($type === '5') {
            $liveQuery->where('type', 1);
        } elseif ($type === '2') {
            $liveQuery->where('type', 2);
        } elseif ($type === '3') {
            $liveQuery->where('type', 3);
        } elseif ($type === '4') {
            $liveQuery->where('type', 4);
        }

        $bios = $artist->years;
        $tours = $liveQuery->paginate(10);
        $totalCount = $tours->total();

        if (request()->wantsJson() || request()->ajax()) {
            $html = view('db_concerts._list', compact('tours', 'totalCount', 'type'))->render();
            return response()->json([
                'html' => $html,
                'next_page_url' => $tours->appends(['type' => $type])->nextPageUrl(),
                'current_page' => $tours->currentPage(),
                'last_page' => $tours->lastPage(),
            ]);
        }

        return view('db_concerts.index', compact('tours', 'bios', 'type', 'totalCount', 'artist'));
    }

    public function show($id)
    {
        $tours = DbConcert::findOrFail($id);
        $artist = $tours->artist;
        $songs = DbSong::orderBy('id', 'asc')->get();
        $tourSetlists = DbSetlist::where('tour_id', $id)->orderBy('order_no', 'asc')->get();
        $previous = DbConcert::where('artist_id', $tours->artist_id)->where('date1', '<', $tours->date1)->orderBy('date1', 'desc')->first();
        $next = DbConcert::where('artist_id', $tours->artist_id)->where('date1', '>', $tours->date1)->orderBy('date1')->first();

        return view('db_concerts.show', compact('songs', 'previous', 'next', 'tours', 'tourSetlists', 'artist'));
    }
}
