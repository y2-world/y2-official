<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Setlist;
use App\Artist;
use App\Models\Year;

class YearController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $years = Year::orderBy('id', 'asc')
        ->paginate(10);
        $artists = Artist::orderBy('id', 'asc')->where('visible', 0)
        ->get();
        return view('years.index', compact('artists', 'years'));
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
    public function show($year)
    {
        // 指定された年の Year レコードを取得
        $year = Year::where('year', $year)->first();
    
        // 指定された年の Year レコードが存在しない場合は、404 エラーを返す
        if (!$year) {
            abort(404);
        }
    
        // アーティストと年のデータを取得
        $artists = Artist::orderBy('id', 'asc')->where('visible', 0)->get();
        $years = Year::orderBy('year', 'asc')->get();
    
        // 指定された年の Setlist を取得
        $setlists = Setlist::where('year', $year->year)->orderBy('date', 'asc')->get();
    
        return view('years.show', [
            'setlists' => $setlists,
            'year' => $year,
            'artists' => $artists,
            'years' => $years
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
