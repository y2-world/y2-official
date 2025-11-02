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
        $type = request()->input('type');
        $today = now()->toDateString();

        // これからのライブ（今日以降）
        $upcomingQuery = Setlist::where('date', '>=', $today)->orderBy('date', 'asc');

        if ($type === '1') {
            $upcomingQuery->where('fes', 0);
        } elseif ($type === '2') {
            $upcomingQuery->whereIn('fes', [1, 2]);
        }

        $upcomingSetlists = $upcomingQuery->get();
        $upcomingTotalCount = $upcomingSetlists->count();

        // 今までのライブ（今日より前）
        $pastQuery = Setlist::where('date', '<', $today)->orderBy('date', 'desc');

        if ($type === '1') {
            $pastQuery->where('fes', 0);
        } elseif ($type === '2') {
            $pastQuery->whereIn('fes', [1, 2]);
        }

        $pastSetlists = $pastQuery->paginate(10);
        $pastTotalCount = $pastSetlists->total();

        // アーティスト、全てのアーティスト、年のデータを取得する
        $artists = Artist::where('visible', 1)->orderBy('id', 'asc')->get();
        $allArtists = Artist::orderBy('id', 'asc')->get();

        // Setlistから年のリストを取得
        $years = Setlist::select('year')
            ->whereNotNull('year')
            ->distinct()
            ->orderBy('year', 'asc')
            ->pluck('year')
            ->map(function ($year) {
                return (object)['year' => $year];
            });

        // AJAXリクエストの場合はJSON形式で返す（過去のライブのみページネーション対応）
        if (request()->wantsJson() || request()->ajax()) {
            $html = view('setlists._list', [
                'setlists' => $pastSetlists,
                'totalCount' => $pastTotalCount,
                'type' => $type
            ])->render();
            return response()->json([
                'html' => $html,
                'next_page_url' => $pastSetlists->appends(['type' => $type])->nextPageUrl(),
                'current_page' => $pastSetlists->currentPage(),
                'last_page' => $pastSetlists->lastPage(),
            ]);
        }

        // 検索候補を取得（SetlistSongテーブルから）
        $suggestions = \App\Models\SetlistSong::select('id', 'title')
            ->orderBy('title', 'asc')
            ->get()
            ->toArray();

        // ビューにデータを渡して表示する
        return view('setlists.index', compact('artists', 'allArtists', 'upcomingSetlists', 'pastSetlists', 'upcomingTotalCount', 'pastTotalCount', 'years', 'type', 'suggestions'));
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
        $artists = Artist::orderBy('id', 'asc')
        ->get();
        $previous = Setlist::where('date', '<', $setlists->date)->orderBy('date', 'desc')->first();
        $next = Setlist::where('date', '>', $setlists->date)->orderBy('date')->first();
        
        return view('setlists.show', compact('artists', 'setlists', 'previous', 'next'));
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
