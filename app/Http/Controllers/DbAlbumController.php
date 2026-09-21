<?php

namespace App\Http\Controllers;

use App\Models\DbAlbum;
use App\Models\Artist;
use App\Models\DbSong;
use Illuminate\Http\Request;

class DbAlbumController extends Controller
{
    public function index($artistId)
    {
        $artist = Artist::findOrFail($artistId);

        $perPage = 10;
        // wantsJson()（Acceptヘッダー依存）だけで判定すると、ブラウザの「戻る」操作が
        // 過去のfetchリクエストのヘッダーをそのまま再現してしまうケースで、通常の
        // ページ遷移をAJAXと誤判定してJSONを返してしまう事故が起きる。
        // フロント側が明示的に付与するクエリパラメータを正とする
        $isAjax = request()->query('ajax') === '1';
        $page = max(1, (int) request('page', 1));

        $query = DbAlbum::where('artist_id', $artistId)->orderBy('date', 'asc');
        $accumulated = !$isAjax;

        if ($isAjax) {
            // スクロールでの追加読み込み: 従来通りそのページ分のみ返す
            $albums = $query->paginate($perPage);
        } else {
            // 通常のページロード（直接アクセス・リロード・ブラウザの戻るボタン含む）:
            // 1ページ目からこのページまでをまとめて返す。無限スクロールで読み進めた際に
            // history.replaceStateでURLのpageを更新しておくことで、戻るボタンで
            // 該当ページのURLに戻ったときにも読み込み済みだった分がまとめて表示され、
            // 先頭に戻ってしまう問題を避けられる
            $total = (clone $query)->count();
            $items = $query->take($page * $perPage)->get();
            $albums = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        $totalCount = $albums->total();
        $bios = $artist->years;

        if ($isAjax) {
            $html = view('db_albums._list', compact('albums', 'totalCount', 'accumulated'))->render();
            return response()->json([
                'html' => $html,
                'next_page_url' => $albums->nextPageUrl(),
                'current_page' => $albums->currentPage(),
                'last_page' => $albums->lastPage(),
            ]);
        }

        return view('db_albums.index', compact('albums', 'bios', 'totalCount', 'artist', 'accumulated'));
    }

    public function show($id)
    {
        $albums = DbAlbum::findOrFail($id);
        $artist = $albums->artist;
        $songs = DbSong::where('artist_id', $albums->artist_id)->get()->keyBy('id');
        // idは作成順であり発売順とは限らない（データ修正等でずれるとオリジナル/ベスト/ミニの並びが崩れる）ため、
        // 発売日(date)を基準に前後を判定する。同日発売の場合はidで副次的に順序を安定させる。
        $previous = DbAlbum::where('artist_id', $albums->artist_id)
            ->where(function ($q) use ($albums) {
                $q->where('date', '<', $albums->date)
                    ->orWhere(function ($q2) use ($albums) {
                        $q2->where('date', $albums->date)->where('id', '<', $albums->id);
                    });
            })
            ->orderByDesc('date')->orderByDesc('id')->first();
        $next = DbAlbum::where('artist_id', $albums->artist_id)
            ->where(function ($q) use ($albums) {
                $q->where('date', '>', $albums->date)
                    ->orWhere(function ($q2) use ($albums) {
                        $q2->where('date', $albums->date)->where('id', '>', $albums->id);
                    });
            })
            ->orderBy('date')->orderBy('id')->first();

        return view('db_albums.show', compact('songs', 'albums', 'previous', 'next', 'artist'));
    }
}
