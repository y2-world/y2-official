<?php

namespace App\Http\Controllers;

use App\Models\Song;
use App\Models\Bio;
use App\Models\Tour;
use Illuminate\Http\Request;

class TourController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tours = Tour::query()
        ->where('type', '=', "0")
        ->orWhere('type', '=', "1")
        ->orderBy('id', 'desc')
        ->paginate(10);
        $bios = Bio::orderBy('id', 'asc')
        ->get();
        $songs = Song::orderBy('id', 'asc')
        ->get();
        return view('tours.index', compact('tours', 'bios', 'songs'));
    }

    public function fes()
    {
        $tours = Tour::query()
        ->orWhere('type', '=', "1")
        ->orderBy('id', 'desc')
        ->paginate(10);
        $bios = Bio::orderBy('id', 'asc')
        ->get();
        $songs = Song::orderBy('id', 'asc')
        ->get();
        return view('tours.index', compact('tours', 'bios', 'songs'));
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
