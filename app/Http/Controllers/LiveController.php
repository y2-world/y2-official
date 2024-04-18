<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Song;
use App\Models\Bio;
use App\Models\Tour;

class LiveController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $liveType = request()->input('type');

        // クエリビルダーを生成し、セットリストを取得する
        $liveQuery = Tour::orderBy('date1', 'desc');
    
        if ($liveType === '1') {
            // live_typeが1の場合はfesカラムが0のセットリストを取得する
            $liveQuery->whereIn('type', [0, 1]);
        } elseif ($liveType === '2') {
            // live_typeが2の場合はfesカラムが1か2のセットリストを取得する
            $liveQuery->where('type', 2);
        } elseif ($liveType === '3') {
            // live_typeが2の場合はfesカラムが1か2のセットリストを取得する
            $liveQuery->where('type', 3);
        } elseif ($liveType === '4') {
            // live_typeが2の場合はfesカラムが1か2のセットリストを取得する
            $liveQuery->where('type', 4);
        }

        $bios = Bio::orderBy('id', 'asc')
        ->get();
        $songs = Song::orderBy('id', 'asc')
        ->get();
    
        // ページネーションを適用してセットリストを取得する
        $tours = $liveQuery->paginate(10);
    
        $bios = Bio::orderBy('id', 'asc')
        ->get();
        $songs = Song::orderBy('id', 'asc')
        ->get();
        return view('live.index', compact('tours', 'bios', 'songs'));
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
        $tours = Tour::find($id);
        $songs = Song::orderBy('id', 'asc')
        ->get();
        $previous = Tour::where('id', '<', $tours->id)->orderBy('id', 'desc')->first();
        $next = Tour::where('id', '>', $tours->id)->orderBy('id')->first();
        
        return view('tours.show', compact('songs', 'previous', 'next', 'tours'));
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
