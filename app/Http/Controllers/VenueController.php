<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\ExternalUserAttendance;
use Illuminate\Http\Request;
use App\Models\SlSetlist;

class VenueController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = SlSetlist::query();
        $keyword = $request->input('keyword');

        // キーワードが空でない場合のみ、検索条件を追加
        if (!empty($keyword)) {
            $query->where('venue', 'like', "%{$keyword}%");
        }

        $artists = Artist::orderBy('id', 'asc')->get();

        $data = $query->orderBy('date', 'desc')->get()
            ->map(fn ($setlist) => (object) [
                'kind' => 'official',
                'date' => $setlist->date,
                'artist_name' => $setlist->artist->name ?? null,
                'artist_url' => $setlist->artist_id ? url('/setlists/artists', $setlist->artist_id) : null,
                'title' => $setlist->title,
                'url' => route('setlists.show', $setlist->id),
            ]);

        // ユーザー登録セトリの参加記録も、公式演奏記録と同じ形式に整形して混ぜる。
        // 会場はユーザーが自由入力した文字列のため、公式側と表記が完全一致するとは限らない。
        if (!empty($keyword)) {
            $userAttendances = ExternalUserAttendance::where('venue', 'like', "%{$keyword}%")
                ->with(['dbSetlist.tour.artist', 'userSetlist.concert.artist'])
                ->get()
                ->map(function (ExternalUserAttendance $attendance) {
                    $isOfficial = (bool) $attendance->db_setlist_id;
                    $tour = $isOfficial ? $attendance->dbSetlist?->tour : $attendance->userSetlist?->concert;
                    $artistId = $isOfficial ? $tour?->artist_id : $tour?->user_artist_id;
                    $artistRef = $artistId ? ($isOfficial ? 'official-' : 'user-') . $artistId : null;

                    return (object) [
                        'kind' => 'attendance',
                        'date' => $attendance->attended_date,
                        'artist_name' => $tour?->artist?->name,
                        'artist_url' => $artistRef ? route('mypage.attendances.index', ['artist_id' => $artistRef]) : null,
                        'title' => $tour?->title,
                        'url' => route('mypage.attendances.show', $attendance),
                    ];
                });

            $data = $data->concat($userAttendances);
        }

        $data = $data->sortByDesc('date')->values();

        return view('venue', compact('artists', 'data', 'keyword'));
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
