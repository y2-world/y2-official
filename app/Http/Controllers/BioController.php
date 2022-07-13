<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Song;
use App\Models\Single;
use App\Models\Album;
use App\Models\Bio;

class BioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $bios = Bio::orderBy('id', 'asc')
        ->paginate(10);
        return view('bios.index', compact('bios'));
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
    public function show(Bio $bio)
    {
        $bio = Bio::find($bio->id); //idが、リクエストされた$userのidと一致するuserを取得
        $singles = Single::orderBy('id', 'asc')
        ->get();
        $albums = Album::orderBy('id', 'asc')
        ->get();
        $bios = Bio::orderBy('year', 'asc')
        ->get();
        $songs = Song::where('year', $bio->year)
            ->orderBy('id', 'asc') //$userによる投稿を取得
            ->get(); // 投稿作成日が新しい順に並べる
        return view('bios.show', [
            'bio' => $bio,
            'singles' => $singles, // $userの書いた記事をviewへ渡す
            'albums' => $albums,
            'bios' => $bios,
            'songs' => $songs,
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
