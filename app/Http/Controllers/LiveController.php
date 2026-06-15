<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Song;
use App\Models\Tour;
use App\Models\TourSetlist;

class LiveController extends Controller
{
    public function index($artistId)
    {
        $artist = Artist::findOrFail($artistId);
        $type = request()->input('type');

        $liveQuery = Tour::where('artist_id', $artistId)->orderBy('date1', 'desc');

        if ($type === '1') {
            $liveQuery->whereIn('type', [0, 1]);
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
            $html = view('live._list', compact('tours', 'totalCount', 'type'))->render();
            return response()->json([
                'html' => $html,
                'next_page_url' => $tours->appends(['type' => $type])->nextPageUrl(),
                'current_page' => $tours->currentPage(),
                'last_page' => $tours->lastPage(),
            ]);
        }

        return view('live.index', compact('tours', 'bios', 'type', 'totalCount', 'artist'));
    }

    public function show($id)
    {
        $tours = Tour::findOrFail($id);
        $artist = $tours->artist;
        $songs = Song::orderBy('id', 'asc')->get();
        $tourSetlists = TourSetlist::where('tour_id', $id)->orderBy('order_no', 'asc')->get();
        $previous = Tour::where('artist_id', $tours->artist_id)->where('id', '<', $tours->id)->orderBy('id', 'desc')->first();
        $next = Tour::where('artist_id', $tours->artist_id)->where('id', '>', $tours->id)->orderBy('id')->first();

        return view('live.show', compact('songs', 'previous', 'next', 'tours', 'tourSetlists', 'artist'));
    }
}
