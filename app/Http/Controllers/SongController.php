<?php

namespace App\Http\Controllers;

use App\Models\Song;
use App\Models\Album;
use App\Models\Single;
use App\Models\Bio;
use App\Models\Tour;
use Illuminate\Http\Request;
use App\Models\TourSetlist;

class SongController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $songs = Song::orderBy('id', 'asc')
            ->paginate(10);
        $albums = Album::orderBy('id', 'asc')
            ->get();
        $bios = Bio::orderBy('id', 'asc')
            ->get();
        return view('songs.index', compact('albums', 'songs', 'bios'));
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
        $songs = Song::findOrFail($id);
        $title = $songs->title;

        // 関連する TourSetlist を tour 付きで取得してフィルター
        $tourSetlists = TourSetlist::with('tour')->get()
            ->filter(function ($setlistModel) use ($id, $title) {
                $setlistArr = is_array($setlistModel->setlist)
                    ? $setlistModel->setlist
                    : json_decode($setlistModel->setlist ?? '[]', true);

                $encoreArr = is_array($setlistModel->encore)
                    ? $setlistModel->encore
                    : json_decode($setlistModel->encore ?? '[]', true);

                $lists = array_merge($setlistArr, $encoreArr);

                foreach ($lists as $entry) {
                    if (is_numeric($entry['song']) && (int)$entry['song'] === (int)$id) {
                        return true;
                    }

                    if (!is_numeric($entry['song'])) {
                        $entryTitle = preg_replace('/\s*\[[^\]]+\]/u', '', $entry['song']);
                        if (trim($entryTitle) === $title) {
                            return true;
                        }
                    }
                }
                return false;
            })
            ->sortByDesc(function ($setlistModel) {
                // tour->start_date があるものを基準に降順ソート
                return optional($setlistModel->tour)->start_date;
            })
            ->values(); // 並べ替え後にキーを振り直す

        // 関連ツアー一覧（重複除去）
        $tours = $tourSetlists->pluck('tour')->filter()->unique('id')->values();

        // その他データ
        $allSongs = Song::orderBy('id')->get();
        $albums = Album::orderBy('id')->get();
        $singles = Single::orderBy('id')->get();
        $previous = Song::where('id', '<', $songs->id)->orderBy('id', 'desc')->first();
        $next = Song::where('id', '>', $songs->id)->orderBy('id')->first();

        return view('songs.show', compact(
            'songs',
            'allSongs',
            'albums',
            'singles',
            'previous',
            'next',
            'tourSetlists',
            'tours'
        ));
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

    public function search(Request $request)
    {
        $query = $request->input('q');
        $songs = Song::where('title', 'LIKE', "%{$query}%")->get(['id', 'title']);

        return response()->json($songs);
    }
}
