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
        $keyword = $request->input('keyword');

        // Setlistクエリの初期化
        $query = Setlist::query();

        // キーワードが空でない場合のみ、検索条件を追加
        $query->where(function ($query) use ($artist_id, $keyword) {
            if (!empty($artist_id)) {
                // artist_idが指定されている場合
                $query->where('artist_id', $artist_id)
                    ->where(function ($query) use ($keyword) {
                        $query->where('setlist', 'like', "%{$keyword}%")
                            ->orWhere('encore', 'like', "%{$keyword}%");
                    })
                    ->orWhere(function ($query) use ($artist_id, $keyword) {
                        $query->whereRaw("
                            JSON_UNQUOTE(JSON_EXTRACT(fes_setlist, '$[*].song')) LIKE ?
                        ", ["%{$keyword}%"])
                        ->whereRaw("
                            JSON_UNQUOTE(JSON_EXTRACT(fes_setlist, '$[*].artist')) LIKE ?
                        ", ["%{$artist_id}%"])
                        ->orWhereRaw("
                            JSON_UNQUOTE(JSON_EXTRACT(fes_encore, '$[*].song')) LIKE ?
                        ", ["%{$keyword}%"])
                        ->whereRaw("
                            JSON_UNQUOTE(JSON_EXTRACT(fes_encore, '$[*].artist')) LIKE ?
                        ", ["%{$artist_id}%"]);
                    });
            } else {
                // artist_idが指定されていない場合（songのみ部分一致）
                $query->where('setlist', 'like', "%{$keyword}%")
                    ->orWhere('encore', 'like', "%{$keyword}%")
                    ->orWhereRaw("
                        JSON_UNQUOTE(JSON_EXTRACT(fes_setlist, '$[*].song')) LIKE ?
                    ", ["%{$keyword}%"])
                    ->orWhereRaw("
                        JSON_UNQUOTE(JSON_EXTRACT(fes_encore, '$[*].song')) LIKE ?
                    ", ["%{$keyword}%"]);
            }
        });

        // 結果を日付順に並べる
        $data = $query->orderBy('date', 'desc')->get();

        // アーティスト情報を取得
        $artists = Artist::orderBy('id', 'asc')->get();

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
