<?php

namespace App\Http\Controllers;

use App\Models\Song;
use App\Models\Bio;
use App\Models\Apbank;
use Illuminate\Http\Request;

class ApbankController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $apbanks = Apbank::orderBy('id', 'asc')
        ->paginate(10);
        $bios = Bio::orderBy('id', 'asc')
        ->get();
        $songs = Song::orderBy('id', 'asc')
        ->get();
        return view('apbanks.index', compact('apbanks', 'bios', 'songs'));
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
        $apbanks = Apbank::find($id);
        $songs = Song::orderBy('id', 'asc')
        ->get();
        $previous = Apbank::where('id', '<', $apbanks->id)->orderBy('id', 'desc')->first();
        $next = Apbank::where('id', '>', $apbanks->id)->orderBy('id')->first();
        
        return view('apbanks.show', compact('songs', 'previous', 'next', 'apbanks'));
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
