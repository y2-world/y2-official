<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Song;
use App\Models\Bio;
use App\Models\Tour;

class FindController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request)
    {
        $query = $request->input('keyword');
        
        // Songsテーブルから曲を検索
        $songIds = Song::where('title', $query)->pluck('id');
        
        // モデルから検索
        $tours = Tour::where(function ($query) use ($songIds) {
            $query->whereJsonContains('setlist1', ['id' => $songIds])
                ->orWhereJsonContains('setlist2', ['id' => $songIds]);
        })->get();

        $bios = Bio::orderBy('id', 'asc')
            ->get();

        return view('find', compact('bios', 'tours', 'query'));
    }

    public function autocomplete(Request $request)
    {
        $query = $request->input('query');

        // データベースから検索候補を取得
        $songs = Song::where('title', 'like', "%{$query}%")->limit(10)->get();

        // タイトルの配列を返す
        return response()->json($songs->pluck('title'));
    }
}
