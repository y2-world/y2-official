<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Artist;
use App\Setlist;

class ArtistController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $artists = Artist::orderBy('id', 'asc')
            ->paginate(10);

        // Setlistから年のリストを取得
        $years = Setlist::select('year')
            ->whereNotNull('year')
            ->distinct()
            ->orderBy('year', 'asc')
            ->pluck('year')
            ->map(function ($year) {
                return (object)['year' => $year];
            });

        return view('artists.index', compact('artists', 'years'));
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
    public function show($artistId)
    {
        // 指定されたアーティストのレコードを取得
        $artist = Artist::find($artistId);

        // 指定されたアーティストのレコードが存在しない場合は、404 エラーを返す
        if (!$artist) {
            abort(404);
        }

        // アーティスト一覧と年のデータを取得
        $artists = Artist::orderBy('id', 'asc')->where('visible', 1)->get();

        // Setlistから年のリストを取得
        $years = Setlist::select('year')
            ->whereNotNull('year')
            ->distinct()
            ->orderBy('year', 'asc')
            ->pluck('year')
            ->map(function ($year) {
                return (object)['year' => $year];
            });

        // 指定されたアーティストのセットリストを取得
        $setlists = Setlist::where('artist_id', $artist->id)
        ->orWhere(function ($query) use ($artistId) {
            $query->whereRaw("
                JSON_CONTAINS(fes_setlist, JSON_OBJECT('artist', ?))
            ", [$artistId])
            ->orWhereRaw("
                JSON_CONTAINS(fes_encore, JSON_OBJECT('artist', ?))
            ", [$artistId]);
        })
        ->orderBy('date', 'asc')
        ->paginate(100);

        // 検索候補（曲名のみ）- 表示中のアーティストの楽曲のみ
        $suggestions = \App\Models\SetlistSong::query()
            ->where('artist_id', $artistId)
            ->orderBy('title', 'asc')
            ->get(['id', 'title'])
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'title' => $row->title,
                    'artist_name' => '', // アーティスト名は表示しない
                ];
            })
            ->toArray();

        return view('artists.show', [
            'setlists' => $setlists,
            'artist' => $artist,
            'artists' => $artists,
            'years' => $years,
            'suggestions' => $suggestions
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
