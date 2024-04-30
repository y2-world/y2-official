<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Artist;
use App\Setlist;
use App\Models\Year;

class ArtistController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $artists = Artist::orderBy('id', 'asc')
        ->paginate(10);
        $years = Year::orderBy('id', 'asc')
        ->get();
        return view('artists.index', compact('artists', 'years'));
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
    public function show(Artist $artist)
    {
        $artist = Artist::find($artist->id); //idが、リクエストされた$userのidと一致するuserを取得
        $artists = Artist::orderBy('id', 'asc')->where('visible', 0)
        ->get();
        $years = Year::orderBy('year', 'asc')
        ->get();
        $setlists = Setlist::where('artist_id', $artist->id)
            ->orderBy('date', 'asc') //$userによる投稿を取得
            ->paginate(100); // 投稿作成日が新しい順に並べる
        return view('artists.show', [
            'setlists' => $setlists,
            'artist' => $artist, // $userの書いた記事をviewへ渡す
            'artists' => $artists,
            'years' => $years
        ]);
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
