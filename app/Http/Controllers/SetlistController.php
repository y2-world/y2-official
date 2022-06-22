<?php

namespace App\Http\Controllers;

use App\Artist;
use App\Setlist;
use Illuminate\Http\Request;

class SetlistController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {   
        $setlists = Setlist::orderBy('date', 'asc')
        ->paginate(10);
        $artists = Artist::orderBy('created_at', 'asc')
        ->paginate(100);

        return view('setlists.index', compact('artists', 'setlists'));
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
        $setlists = Setlist::find($id);
        $previous = Setlist::where('date', '<', $setlists->date)->orderBy('date', 'desc')->first();
        $next = Setlist::where('date', '>', $setlists->date)->orderBy('date')->first();
        
        return view('setlists.show', compact('setlists', 'previous', 'next'));
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
