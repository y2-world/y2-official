<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Disco;
use App\Models\Lyric;

class DiscoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $type = request()->input('type');

        $discos = Disco::orderBy('date', 'desc')->get();

        if ($type === '1') {
            // live_typeが1の場合はfesカラムが0のセットリストを取得する
            $discos = Disco::orderBy('date', 'desc')
                ->where('type', '=', "0")
                ->get();
        } elseif ($type === '2') {
            // live_typeが2の場合はfesカラムが1か2のセットリストを取得する
            $discos = Disco::orderBy('date', 'desc')
                ->where('type', '=', "1")
                ->get();
        }

        return view('music.index', compact('discos'));
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
        $discos = Disco::find($id);
        $lyrics = Lyric::orderBy('id', 'asc')
            ->get();
        $previous = Disco::where('id', '<', $discos->id)->orderBy('id', 'desc')->first();
        $next = Disco::where('id', '>', $discos->id)->orderBy('id')->first();

        return view('music.show', compact('discos', 'lyrics', 'previous', 'next'));
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
