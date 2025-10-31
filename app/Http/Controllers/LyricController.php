<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Disco;
use App\Models\Lyric;

class LyricController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    // public function index()
    // {
    //     $lyrics = Lyric::orderBy('id', 'asc')
    //         ->paginate(10);
    //     $totalCount = $lyrics->total();

    //     $discos = Disco::orderBy('id', 'asc')
    //         ->get();

    //     // AJAXリクエストの場合はJSON形式で返す
    //     if (request()->wantsJson() || request()->ajax()) {
    //         $html = view('lyrics._list', compact('lyrics', 'totalCount'))->render();
    //         return response()->json([
    //             'html' => $html,
    //             'next_page_url' => $lyrics->nextPageUrl(),
    //             'current_page' => $lyrics->currentPage(),
    //             'last_page' => $lyrics->lastPage(),
    //         ]);
    //     }

    //     return view('lyrics.index', compact('lyrics', 'discos', 'totalCount'));
    // }

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
        // $lyrics = Lyric::find($id);
        // $previous = Lyric::where('id', '<', $lyrics->id)->orderBy('id', 'desc')->first();
        // $next = Lyric::where('id', '>', $lyrics->id)->orderBy('id')->first();
        
        // return view('lyrics.show', compact('lyrics', 'previous', 'next'));

        $lyrics = Lyric::findOrFail($id);
        return response()->json([
            'title' => $lyrics->title,
            'lyrics' => nl2br(e($lyrics->lyrics)), // 改行を <br> に変換して HTML エスケープ
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
