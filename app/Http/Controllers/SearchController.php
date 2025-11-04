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

        // キーワードから該当するSetlistSongのIDを取得
        $songIds = [];
        if (!empty($keyword)) {
            if ($matchType === 'exact') {
                $songIds = \App\Models\SetlistSong::where('title', $keyword)->pluck('id')->toArray();
            } else {
                $songIds = \App\Models\SetlistSong::where('title', 'like', "%$keyword%")->pluck('id')->toArray();
            }
        }

        $query = Setlist::query();

        // キーワード未入力でもアーティストが選択されていればアーティストで絞り込み
        if (empty($keyword) && !empty($artist_id)) {
            $artistIdInt = (int) $artist_id;
            $artistJsonInt = json_encode(['artist' => $artistIdInt], JSON_UNESCAPED_UNICODE);
            $artistJsonStr = json_encode(['artist' => (string) $artist_id], JSON_UNESCAPED_UNICODE);
            $artistLikeInt = '%"artist":' . $artistIdInt . '%';
            $artistLikeStr = '%"artist":"' . $artist_id . '"%';
            $query->where(function ($query) use ($artist_id, $artistJsonInt, $artistJsonStr, $artistLikeInt, $artistLikeStr) {
                // 単独ライブ
                $query->where('artist_id', $artist_id)
                    // フェス（JSON一致 or LIKE フォールバック）
                    ->orWhereRaw('JSON_CONTAINS(fes_setlist, ?, "$")', [$artistJsonInt])
                    ->orWhereRaw('JSON_CONTAINS(fes_encore, ?, "$")', [$artistJsonInt])
                    ->orWhereRaw('JSON_CONTAINS(fes_setlist, ?, "$")', [$artistJsonStr])
                    ->orWhereRaw('JSON_CONTAINS(fes_encore, ?, "$")', [$artistJsonStr])
                    ->orWhere('fes_setlist', 'like', $artistLikeInt)
                    ->orWhere('fes_encore', 'like', $artistLikeInt)
                    ->orWhere('fes_setlist', 'like', $artistLikeStr)
                    ->orWhere('fes_encore', 'like', $artistLikeStr);
            });
        }

        if (!empty($keyword)) {
            $query->where(function ($query) use ($artist_id, $keyword, $matchType, $songIds) {
                if (!empty($artist_id)) {
                    $query->where('artist_id', $artist_id)
                        ->where(function ($query) use ($keyword, $matchType, $songIds) {
                            // songフィールドがID（数値）の場合
                            foreach ($songIds as $songId) {
                                $query->orWhereRaw("JSON_CONTAINS(setlist, JSON_OBJECT('song', ?))", [$songId])
                                    ->orWhereRaw("JSON_CONTAINS(encore, JSON_OBJECT('song', ?))", [$songId]);
                            }
                            // songフィールドが文字列（例外）の場合
                            if ($matchType === 'exact') {
                                $query->orWhereRaw("JSON_CONTAINS(setlist, JSON_OBJECT('song', ?))", [$keyword])
                                    ->orWhereRaw("JSON_CONTAINS(encore, JSON_OBJECT('song', ?))", [$keyword]);
                            } else {
                                $query->orWhere('setlist', 'like', "%$keyword%")
                                    ->orWhere('encore', 'like', "%$keyword%");
                            }
                        })
                        ->orWhere(function ($query) use ($artist_id, $keyword, $matchType, $songIds) {
                            // フェスの場合
                        foreach ($songIds as $songId) {
                            $artistIdInt = (int) $artist_id;
                            $comboJsonInt = json_encode(['song' => (int) $songId, 'artist' => $artistIdInt], JSON_UNESCAPED_UNICODE);
                            $comboJsonStr = json_encode(['song' => (int) $songId, 'artist' => (string) $artist_id], JSON_UNESCAPED_UNICODE);
                            $query->orWhereRaw('JSON_CONTAINS(fes_setlist, ?, "$")', [$comboJsonInt])
                                ->orWhereRaw('JSON_CONTAINS(fes_encore, ?, "$")', [$comboJsonInt])
                                ->orWhereRaw('JSON_CONTAINS(fes_setlist, ?, "$")', [$comboJsonStr])
                                ->orWhereRaw('JSON_CONTAINS(fes_encore, ?, "$")', [$comboJsonStr]);
                        }
                            // songフィールドが文字列（例外）の場合
                            if ($matchType === 'exact') {
                                $artistIdInt = (int) $artist_id;
                                $comboKeywordJsonInt = json_encode(['song' => (string) $keyword, 'artist' => $artistIdInt], JSON_UNESCAPED_UNICODE);
                                $comboKeywordJsonStr = json_encode(['song' => (string) $keyword, 'artist' => (string) $artist_id], JSON_UNESCAPED_UNICODE);
                                $query->orWhereRaw('JSON_CONTAINS(fes_setlist, ?, "$")', [$comboKeywordJsonInt])
                                    ->orWhereRaw('JSON_CONTAINS(fes_encore, ?, "$")', [$comboKeywordJsonInt])
                                    ->orWhereRaw('JSON_CONTAINS(fes_setlist, ?, "$")', [$comboKeywordJsonStr])
                                    ->orWhereRaw('JSON_CONTAINS(fes_encore, ?, "$")', [$comboKeywordJsonStr]);
                            } else {
                                $query->orWhere(function ($q) use ($keyword, $artist_id) {
                                    $q->where('fes_setlist', 'like', "%$keyword%")
                                        ->where('fes_setlist', 'like', "%$artist_id%");
                                })->orWhere(function ($q) use ($keyword, $artist_id) {
                                    $q->where('fes_encore', 'like', "%$keyword%")
                                        ->where('fes_encore', 'like', "%$artist_id%");
                                });
                            }
                        });
                } else {
                    $query->where(function ($query) use ($keyword, $matchType, $songIds) {
                        // songフィールドがID（数値）の場合
                        foreach ($songIds as $songId) {
                            $query->orWhereRaw("JSON_CONTAINS(setlist, JSON_OBJECT('song', ?))", [$songId])
                                ->orWhereRaw("JSON_CONTAINS(encore, JSON_OBJECT('song', ?))", [$songId])
                                ->orWhereRaw("JSON_CONTAINS(fes_setlist, JSON_OBJECT('song', ?))", [$songId])
                                ->orWhereRaw("JSON_CONTAINS(fes_encore, JSON_OBJECT('song', ?))", [$songId]);
                        }
                        // songフィールドが文字列（例外）の場合
                        if ($matchType === 'exact') {
                            $query->orWhereRaw("JSON_CONTAINS(setlist, JSON_OBJECT('song', ?))", [$keyword])
                                ->orWhereRaw("JSON_CONTAINS(encore, JSON_OBJECT('song', ?))", [$keyword])
                                ->orWhereRaw("JSON_CONTAINS(fes_setlist, JSON_OBJECT('song', ?))", [$keyword])
                                ->orWhereRaw("JSON_CONTAINS(fes_encore, JSON_OBJECT('song', ?))", [$keyword]);
                        } else {
                            $query->orWhere('setlist', 'like', "%$keyword%")
                                ->orWhere('encore', 'like', "%$keyword%")
                                ->orWhere('fes_setlist', 'like', "%$keyword%")
                                ->orWhere('fes_encore', 'like', "%$keyword%");
                        }
                    });
                }
            });
        }

        $data = $query->orderBy('date', 'desc')->get();
        $artists = Artist::orderBy('id', 'asc')->get();

        // 検索候補（曲名 + アーティスト名）
        $suggestions = \App\Models\SetlistSong::query()
            ->leftJoin('artists', 'artists.id', '=', 'setlist_songs.artist_id')
            ->orderBy('setlist_songs.title', 'asc')
            ->get([
                'setlist_songs.id as id',
                'setlist_songs.title as title',
                'artists.name as artist_name',
            ])
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'title' => $row->title,
                    'artist_name' => $row->artist_name,
                ];
            })
            ->toArray();

        return view('search', compact('data', 'keyword', 'artist_id', 'artists', 'matchType', 'suggestions'));
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
