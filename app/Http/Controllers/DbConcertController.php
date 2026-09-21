<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\DbSong;
use App\Models\DbConcert;
use App\Models\DbSetlist;

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

        $perPage = 10;
        // wantsJson()（Acceptヘッダー依存）だけで判定すると、ブラウザの「戻る」操作が
        // 過去のfetchリクエストのヘッダーをそのまま再現してしまうケースで、通常の
        // ページ遷移をAJAXと誤判定してJSONを返してしまう事故が起きる。
        // フロント側が明示的に付与するクエリパラメータを正とする
        $isAjax = request()->query('ajax') === '1';
        $page = max(1, (int) request('page', 1));
        $accumulated = !$isAjax;

        if ($isAjax) {
            // スクロールでの追加読み込み: 従来通りそのページ分のみ返す
            $tours = $liveQuery->paginate($perPage);
        } else {
            // 通常のページロード（直接アクセス・リロード・ブラウザの戻るボタン含む）:
            // 1ページ目からこのページまでをまとめて返す。無限スクロールで読み進めた際に
            // history.replaceStateでURLのpageを更新しておくことで、戻るボタンで
            // 該当ページのURLに戻ったときにも読み込み済みだった分がまとめて表示され、
            // 先頭に戻ってしまう問題を避けられる
            $total = (clone $liveQuery)->count();
            $items = $liveQuery->take($page * $perPage)->get();
            $tours = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        $totalCount = $tours->total();

        if ($isAjax) {
            $html = view('db_concerts._list', compact('tours', 'totalCount', 'type', 'accumulated'))->render();
            return response()->json([
                'html' => $html,
                'next_page_url' => $tours->appends(['type' => $type])->nextPageUrl(),
                'current_page' => $tours->currentPage(),
                'last_page' => $tours->lastPage(),
            ]);
        }

        return view('db_concerts.index', compact('tours', 'bios', 'type', 'totalCount', 'artist', 'accumulated'));
    }

    public function show($id)
    {
        $tours = DbConcert::findOrFail($id);
        $artist = $tours->artist;
        $songs = DbSong::orderBy('sort_order', 'asc')->get();
        $tourSetlists = DbSetlist::where('tour_id', $id)->orderBy('order_no', 'asc')->get();
        $previous = DbConcert::where('artist_id', $tours->artist_id)
            ->where(function ($q) use ($tours) {
                $q->where('date1', '<', $tours->date1)
                  ->orWhere(function ($q2) use ($tours) {
                      $q2->where('date1', $tours->date1)->where('id', '<', $tours->id);
                  });
            })->orderBy('date1', 'desc')->orderBy('id', 'desc')->first();
        $next = DbConcert::where('artist_id', $tours->artist_id)
            ->where(function ($q) use ($tours) {
                $q->where('date1', '>', $tours->date1)
                  ->orWhere(function ($q2) use ($tours) {
                      $q2->where('date1', $tours->date1)->where('id', '>', $tours->id);
                  });
            })->orderBy('date1')->orderBy('id')->first();

        return view('db_concerts.show', compact('songs', 'previous', 'next', 'tours', 'tourSetlists', 'artist'));
    }
}
