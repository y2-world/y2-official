<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\DbSong;
use App\Models\DbConcert;
use App\Models\DbSetlist;
use Illuminate\Http\Request;

class DbConcertController extends Controller
{
    public function index($artistId)
    {
        $artist = Artist::findOrFail($artistId);
        $type = request()->input('type');

        $liveQuery = DbConcert::where('artist_id', $artistId)->orderBy('date1', 'desc');

        if ($type === '1') {
            $liveQuery->whereIn('type', [0, 1]);
        } elseif ($type === '6') {
            $liveQuery->where('type', 0);
        } elseif ($type === '5') {
            $liveQuery->where('type', 1);
        } elseif ($type === '2') {
            $liveQuery->where('type', 2);
        } elseif ($type === '3') {
            $liveQuery->where('type', 3);
        } elseif ($type === '4') {
            $liveQuery->where('type', 4);
        }

        $bios = $artist->years;
        $tours = $liveQuery->paginate(10);
        $totalCount = $tours->total();

        if (request()->wantsJson() || request()->ajax()) {
            $html = view('db_concerts._list', compact('tours', 'totalCount', 'type'))->render();
            return response()->json([
                'html' => $html,
                'next_page_url' => $tours->appends(['type' => $type])->nextPageUrl(),
                'current_page' => $tours->currentPage(),
                'last_page' => $tours->lastPage(),
            ]);
        }

        return view('db_concerts.index', compact('tours', 'bios', 'type', 'totalCount', 'artist'));
    }

    public function show(Request $request, $id)
    {
        $tours = DbConcert::findOrFail($id);
        $artist = $tours->artist;

        // 一覧ページ（type別に絞り込まれたタブ）から来た場合、Previous/Nextの移動範囲を
        // その一覧と同じtype（ツアー/イベント/ap bank fes/ソロ）内に絞り込む。
        // 該当しない・指定が無い場合は従来通りアーティスト内の全ライブから前後に移動する。
        $from = $request->query('from');
        $scopeQuery = function ($query) use ($from, $tours) {
            if ($from === 'type') {
                $query->where('type', $tours->type);
            }
            return $query;
        };
        $songs = DbSong::orderBy('sort_order', 'asc')->get();
        $tourSetlists = DbSetlist::where('tour_id', $id)->orderBy('order_no', 'asc')->get();

        // row（同時に見比べる列同士）ごとに、パターンが2つ以上ある場合だけ
        // Summarizeポップアップ用の位置ベース差分マージ結果を作る
        $setlistSummaries = $tourSetlists
            ->groupBy(fn ($m) => $m->row ?? 1)
            ->map(function ($rowSetlists) use ($songs) {
                if ($rowSetlists->count() < 2) {
                    return null;
                }
                $summary = buildSetlistPatternSummary($rowSetlists, $songs);
                $hasDifference = collect(array_merge($summary['setlist'], $summary['encore']))
                    ->contains(fn ($row) => count($row['variants']) > 1);
                // 全パターンが完全に同じ曲順・曲目のrowは、Summarizeで見せる差異が
                // 無いので対象から除外する
                return $hasDifference ? $summary : null;
            })
            ->filter();
        $previous = $scopeQuery(DbConcert::where('artist_id', $tours->artist_id)
            ->where(function ($q) use ($tours) {
                $q->where('date1', '<', $tours->date1)
                  ->orWhere(function ($q2) use ($tours) {
                      $q2->where('date1', $tours->date1)->where('id', '<', $tours->id);
                  });
            }))->orderBy('date1', 'desc')->orderBy('id', 'desc')->first();
        $next = $scopeQuery(DbConcert::where('artist_id', $tours->artist_id)
            ->where(function ($q) use ($tours) {
                $q->where('date1', '>', $tours->date1)
                  ->orWhere(function ($q2) use ($tours) {
                      $q2->where('date1', $tours->date1)->where('id', '>', $tours->id);
                  });
            }))->orderBy('date1')->orderBy('id')->first();

        return view('db_concerts.show', compact('songs', 'previous', 'next', 'tours', 'tourSetlists', 'artist', 'setlistSummaries', 'from'));
    }
}
