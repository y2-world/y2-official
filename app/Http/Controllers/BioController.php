<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Song;
use App\Models\Single;
use App\Models\Album;
use App\Models\Bio;
use App\Models\Tour;

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
            ->get();
        $songs = Song::orderBy('id', 'asc')
            ->get();
        return view('database.index', compact('bios', 'songs'));
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
    public function show($year)
    {
        // 指定された年の Bio を取得
        $bio = Bio::where('year', $year)->first();

        // 指定された年の Bio が存在しない場合は、404 エラーを返す
        if (!$bio) {
            abort(404);
        }

        // その年の他のデータを取得
        $singles = Single::orderBy('id', 'asc')->get();
        $albums = Album::orderBy('id', 'asc')->get();
        $bios = Bio::orderBy('year', 'asc')->get();
        $songs = Song::where('year', $bio->year)->orderBy('id', 'asc')->get();
        $tours = Tour::whereRaw('YEAR(date1) = ? OR YEAR(date2) = ?', [$year->year, $year->year])
            ->orderBy('date1', 'asc')  // または、日付順で並べる場合は date1 または date2 に基づいて並べ替え
            ->get();

        return view('database.show', [
            'bio' => $bio,
            'singles' => $singles,
            'albums' => $albums,
            'bios' => $bios,
            'songs' => $songs,
            'tours' => $tours,
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
