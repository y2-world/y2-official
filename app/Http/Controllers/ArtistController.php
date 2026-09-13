<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Artist;
use App\Models\SlSetlist;
use Illuminate\Support\Facades\DB;

class ArtistController extends Controller
{
    // fes_setlist/fes_encore（JSON配列、各要素が {"artist": "...", ...}）の中に
    // 指定したartistIdを含む要素が1つでもあるかを判定するwhereRaw用のSQL文字列を返す。
    // MySQLはJSON_SEARCHで一発検索できるが、PostgreSQL（json型）には相当関数が無いため
    // json_array_elements で展開してEXISTSするサブクエリに書き換える。
    private function jsonArrayContainsArtistSql(string $column): string
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            return "EXISTS (SELECT 1 FROM json_array_elements({$column}) AS elem WHERE elem->>'artist' = ?)";
        }

        return "JSON_SEARCH({$column}, 'one', ?, NULL, '\$[*].artist') IS NOT NULL";
    }
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
        $years = SlSetlist::select('year')
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
        $years = SlSetlist::select('year')
            ->whereNotNull('year')
            ->distinct()
            ->orderBy('year', 'asc')
            ->pluck('year')
            ->map(function ($year) {
                return (object)['year' => $year];
            });

        // 指定されたアーティストのセットリストを取得
        $setlists = SlSetlist::where('artist_id', $artist->id)
        ->orWhere(function ($query) use ($artistId) {
            $query->whereRaw($this->jsonArrayContainsArtistSql('fes_setlist'), [$artistId])
            ->orWhereRaw($this->jsonArrayContainsArtistSql('fes_encore'), [$artistId]);
        })
        ->orderBy('date', 'asc')
        ->paginate(100);

        // 検索候補（曲名のみ）- 表示中のアーティストの楽曲のみ
        $suggestions = \App\Models\SlSong::query()
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
