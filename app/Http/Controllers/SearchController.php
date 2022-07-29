<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Setlist;
use App\Artist;

class SearchController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {   
        $artist_id = $request->input('artist_id');
        $query = Setlist::query();
        $keyword = $request->input('keyword');
 
        if (!empty($keyword)) {
            $setlists = $query->where('artist_id', "$artist_id") 
            ->where(function($query) use ($keyword) {
                $query->where('setlist', 'like', "%{$keyword}%")
                ->orWhere('encore', 'like', "%{$keyword}%");
            })->orWhere(function($query) use ($keyword) {
                $query->where('fes_setlist', 'like', "%{$keyword}%")
                ->orWhere('fes_encore', 'like', "%{$keyword}%");
            });
        };

        $data = $query->orderBy('date','desc')->paginate(10);

        $artists = Artist::orderBy('id', 'asc')
        ->get();
 
        return view('search', compact('data', 'keyword', 'artist_id', 'artists'));
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
        //
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
