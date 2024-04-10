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
        // $query = $request->input('keyword');

        // Songsテーブルから曲を検索
        // $songId = Song::where('title', $query)->value('id');

        // $tours = Tour::where(function ($query) use ($songId) {
        //     $query->whereJsonContains('setlist1', ['id' => $songId])
        //         ->orWhereJsonContains('setlist2', ['id' => $songId]);
        // })->get();

        // $tours = Tour::orderBy('id', 'asc')
        // ->get();

        $query = Tour::query();
        $keyword = $request->input('keyword');

        if (!empty($keyword)) {
            $tours =  $query->where('setlist1', 'like', "%{$keyword}%")
                ->orWhere('setlist2', 'like', "%{$keyword}%");
        };

        $data = $query->orderBy('date1','desc')->get();

        $bios = Bio::orderBy('id', 'asc')
            ->get();

        return view('find', compact('bios', 'data', 'query', 'keyword'));
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
