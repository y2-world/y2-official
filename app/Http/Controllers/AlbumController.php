<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Song;
use App\Models\Single;
use App\Models\Bio;
use Illuminate\Http\Request;

class AlbumController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $albums = Album::orderBy('id', 'asc')
            ->paginate(10);
        $totalCount = $albums->total();

        $bios = Bio::orderBy('id', 'asc')
            ->get();

        // AJAXリクエストの場合はJSON形式で返す
        if (request()->wantsJson() || request()->ajax()) {
            $html = view('albums._list', compact('albums', 'totalCount'))->render();
            return response()->json([
                'html' => $html,
                'next_page_url' => $albums->nextPageUrl(),
                'current_page' => $albums->currentPage(),
                'last_page' => $albums->lastPage(),
            ]);
        }

        return view('albums.index', compact('albums', 'bios', 'totalCount'));
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
        $albums = Album::find($id);
        $songs = Song::orderBy('id', 'asc')
        ->get();
        $previous = Album::where('id', '<', $albums->id)->orderBy('id', 'desc')->first();
        $next = Album::where('id', '>', $albums->id)->orderBy('id')->first();
        
        return view('albums.show', compact('songs', 'albums', 'previous', 'next'));
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
}
