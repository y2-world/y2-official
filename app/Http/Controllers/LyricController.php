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
    public function index()
    {
        $lyrics = Lyric::orderBy('id', 'asc')
        ->paginate(10);
        $discos = Disco::orderBy('id', 'asc')
        ->get();
        return view('lyrics.index', compact('lyrics','discos'));
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
        $lyrics = Lyric::find($id);
        $previous = Lyric::where('id', '<', $lyrics->id)->orderBy('id', 'desc')->first();
        $next = Lyric::where('id', '>', $lyrics->id)->orderBy('id')->first();
        
        return view('lyrics.show', compact('lyrics', 'previous', 'next'));
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
