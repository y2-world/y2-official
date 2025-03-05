<?php

namespace App\Http\Controllers;

use App\Models\Song;
use App\Models\Album;
use App\Models\Single;
use App\Models\Bio;
use App\Models\Tour;
use Illuminate\Http\Request;

class SongController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $songs = Song::orderBy('id', 'asc')
            ->paginate(10);
        $albums = Album::orderBy('id', 'asc')
            ->get();
        $bios = Bio::orderBy('id', 'asc')
            ->get();
        return view('songs.index', compact('albums', 'songs', 'bios'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $songs = Song::find($id);
        $allSongs = Song::orderBy('id', 'asc')
            ->get();
        $albums = Album::orderBy('id', 'asc')
            ->get();
        $singles = Single::orderBy('id', 'asc')
            ->get();
        $tours = Tour::whereRaw("
            JSON_EXTRACT(setlist1, '$[*].id') REGEXP ?
        ", ['"' . $id . '"'])
            ->orWhereRaw("
            JSON_EXTRACT(setlist2, '$[*].id') REGEXP ?
        ", ['"' . $id . '"'])
            ->orWhereRaw("
            JSON_EXTRACT(setlist3, '$[*].id') REGEXP ?
        ", ['"' . $id . '"'])
            ->orderBy('date1', 'desc')
            ->get();

        $previous = Song::where('id', '<', $songs->id)->orderBy('id', 'desc')->first();
        $next = Song::where('id', '>', $songs->id)->orderBy('id')->first();

        return view('songs.show', compact('songs', 'allSongs', 'previous', 'next', 'albums', 'singles', 'tours'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function search(Request $request)
    {
        $query = $request->input('q');
        $songs = Song::where('title', 'LIKE', "%{$query}%")->get(['id', 'title']);

        return response()->json($songs);
    }
}
