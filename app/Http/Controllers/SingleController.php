<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Single;
use App\Models\Song;
use App\Models\Bio;

class SingleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $singles = Single::orderBy('id', 'asc')
        ->paginate(10);
        $bios = Bio::orderBy('id', 'asc')
        ->get();
        return view('singles.index', compact('singles', 'bios'));
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
        $singles = Single::find($id);
        $songs = Song::orderBy('single_trk', 'asc')
        ->get();
        $previous = Single::where('id', '<', $singles->id)->orderBy('id', 'desc')->first();
        $next = Single::where('id', '>', $singles->id)->orderBy('id')->first();
        
        return view('singles.show', compact('songs', 'singles', 'previous', 'next'));
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
