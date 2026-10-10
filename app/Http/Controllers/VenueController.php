<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use Illuminate\Http\Request;
use App\Models\SlSetlist;

class VenueController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = SlSetlist::query();
        $keyword = $request->input('keyword');

        // 検索結果ではなく、その会場のすべてのセットリスト（My Page の会場のページと同じく、会場名が一致するもの）
        if (!empty($keyword)) {
            $query->where('venue', $keyword);
        }

        $artists = Artist::orderBy('id', 'asc')->get();

        $data = $query->orderBy('date', 'desc')->get()
            ->map(fn ($setlist) => (object) [
                'kind' => 'official',
                'date' => $setlist->date,
                'artist_name' => $setlist->artist->name ?? null,
                'artist_url' => $setlist->artist_id ? url('/setlists/artists', $setlist->artist_id) : null,
                'title' => $setlist->title,
                'url' => route('setlists.show', $setlist->id) . '?from=venue',
            ]);

        return view('venue', compact('artists', 'data', 'keyword'));
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
        //
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
