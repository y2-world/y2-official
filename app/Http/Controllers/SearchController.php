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
        $matchType = $request->input('match_type', 'exact'); // デフォルトは完全一致

        $query = Setlist::query();

        $query->where(function ($query) use ($artist_id, $keyword, $matchType) {
            if (!empty($artist_id)) {
                $query->where('artist_id', $artist_id)
                    ->where(function ($query) use ($keyword, $matchType) {
                        if ($matchType === 'exact') {
                            $query->whereRaw("JSON_CONTAINS(setlist, JSON_OBJECT('song', ?))", [$keyword])
                                ->orWhereRaw("JSON_CONTAINS(encore, JSON_OBJECT('song', ?))", [$keyword]);
                        } else {
                            $query->where('setlist', 'like', "%$keyword%")
                                ->orWhere('encore', 'like', "%$keyword%");
                        }
                    })
                    ->orWhere(function ($query) use ($artist_id, $keyword, $matchType) {
                        if ($matchType === 'exact') {
                            $query->whereRaw("JSON_CONTAINS(fes_setlist, JSON_OBJECT('song', ?, 'artist', ?))", [$keyword, $artist_id])
                                ->orWhereRaw("JSON_CONTAINS(fes_encore, JSON_OBJECT('song', ?, 'artist', ?))", [$keyword, $artist_id]);
                        } else {
                            // ここを修正。artist_id もlike条件に含めるためには、JSON内のartistはlikeで部分検索しかないので、 fes_setlist, fes_encore共にartist_idが入っているかどうかを文字列likeでチェックする条件を追加
                            $query->where(function ($q) use ($keyword, $artist_id) {
                                $q->where('fes_setlist', 'like', "%$keyword%")
                                    ->where('fes_setlist', 'like', "%$artist_id%");
                            })->orWhere(function ($q) use ($keyword, $artist_id) {
                                $q->where('fes_encore', 'like', "%$keyword%")
                                    ->where('fes_encore', 'like', "%$artist_id%");
                            });
                        }
                    });
            } else {
                $query->where(function ($query) use ($keyword, $matchType) {
                    if ($matchType === 'exact') {
                        $query->whereRaw("JSON_CONTAINS(setlist, JSON_OBJECT('song', ?))", [$keyword])
                            ->orWhereRaw("JSON_CONTAINS(encore, JSON_OBJECT('song', ?))", [$keyword])
                            ->orWhereRaw("JSON_CONTAINS(fes_setlist, JSON_OBJECT('song', ?))", [$keyword])
                            ->orWhereRaw("JSON_CONTAINS(fes_encore, JSON_OBJECT('song', ?))", [$keyword]);
                    } else {
                        $query->where('setlist', 'like', "%$keyword%")
                            ->orWhere('encore', 'like', "%$keyword%")
                            ->orWhere('fes_setlist', 'like', "%$keyword%")
                            ->orWhere('fes_encore', 'like', "%$keyword%");
                    }
                });
            }
        });

        $data = $query->orderBy('date', 'desc')->get();
        $artists = Artist::orderBy('id', 'asc')->get();

        return view('search', compact('data', 'keyword', 'artist_id', 'artists', 'matchType'));
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
