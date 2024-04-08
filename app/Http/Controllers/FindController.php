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
        return view('find.index');
    }

    public function getSuggestions(Request $request)
    {
        $query = $request->input('query');

        // Songsテーブルから曲を検索
        $songs = Song::where('title', 'like', '%' . $query . '%')->pluck('title');

        return response()->json($songs);
    }
}
