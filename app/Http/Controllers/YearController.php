<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setlist;
use App\Models\Artist;

class YearController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Setlistから年を取得（重複なし、降順）
        $years = Setlist::select('year')
            ->whereNotNull('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->map(function ($year) {
                return (object)['year' => $year];
            });

        $artists = Artist::orderBy('id', 'asc')->where('visible', 1)
            ->get();
        return view('years.index', compact('artists', 'years'));
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
    public function show($yearParam)
    {
        // 指定された年の Setlist を取得
        $setlists = Setlist::where('year', $yearParam)
            ->orderBy('date', 'asc')
            ->get();

        // Setlist が存在しない場合は、404 エラーを返す
        if ($setlists->isEmpty()) {
            abort(404);
        }

        // アーティストと年のデータを取得
        $artists = Artist::orderBy('id', 'asc')->where('visible', 1)->get();

        // Setlistから年のリストを取得
        $years = Setlist::select('year')
            ->whereNotNull('year')
            ->distinct()
            ->orderBy('year', 'asc')
            ->pluck('year')
            ->map(function ($year) {
                return (object)['year' => $year];
            });

        // 年のオブジェクトを作成（ビューで使用）
        $year = (object)['year' => $yearParam];

        // 検索候補を取得（SetlistSongテーブルから）
        $suggestions = \App\Models\SetlistSong::select('id', 'title')
            ->orderBy('title', 'asc')
            ->get()
            ->toArray();

        // ビューにデータを渡す
        return view('years.show', [
            'setlists' => $setlists,
            'year' => $year,
            'artists' => $artists,
            'years' => $years,
            'suggestions' => $suggestions
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
