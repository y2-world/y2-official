<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Artist;
use App\Models\DbSingle;
use App\Models\DbSong;

class DbSingleController extends Controller
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

        $query = DbSingle::where('artist_id', $artistId)->orderBy('date', 'asc');
        $accumulated = !$isAjax;

        if ($isAjax) {
            // スクロールでの追加読み込み: 従来通りそのページ分のみ返す
            $singles = $query->paginate($perPage);
        } else {
            // 通常のページロード（直接アクセス・リロード・ブラウザの戻るボタン含む）:
            // 1ページ目からこのページまでをまとめて返す。無限スクロールで読み進めた際に
            // history.replaceStateでURLのpageを更新しておくことで、戻るボタンで
            // 該当ページのURLに戻ったときにも読み込み済みだった分がまとめて表示され、
            // 先頭に戻ってしまう問題を避けられる
            $total = (clone $query)->count();
            $items = $query->take($page * $perPage)->get();
            $singles = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        $totalCount = $singles->total();
        $bios = $artist->years;

        if ($isAjax) {
            $html = view('db_singles._list', compact('singles', 'totalCount', 'accumulated'))->render();
            return response()->json([
                'html' => $html,
                'next_page_url' => $singles->nextPageUrl(),
                'current_page' => $singles->currentPage(),
                'last_page' => $singles->lastPage(),
            ]);
        }

        return view('db_singles.index', compact('singles', 'bios', 'totalCount', 'artist', 'accumulated'));
    }

    public function show($id)
    {
        $singles = DbSingle::findOrFail($id);
        $artist = $singles->artist;
        $songs = DbSong::where('artist_id', $singles->artist_id)->get()->keyBy('id');
        // idは作成順であり発売順とは限らない（データ修正等でずれるとCD/配信シングルの並びが崩れる）ため、
        // 発売日(date)を基準に前後を判定する。同日発売の場合はidで副次的に順序を安定させる。
        $previous = DbSingle::where('artist_id', $singles->artist_id)
            ->where(function ($q) use ($singles) {
                $q->where('date', '<', $singles->date)
                    ->orWhere(function ($q2) use ($singles) {
                        $q2->where('date', $singles->date)->where('id', '<', $singles->id);
                    });
            })
            ->orderByDesc('date')->orderByDesc('id')->first();
        $next = DbSingle::where('artist_id', $singles->artist_id)
            ->where(function ($q) use ($singles) {
                $q->where('date', '>', $singles->date)
                    ->orWhere(function ($q2) use ($singles) {
                        $q2->where('date', $singles->date)->where('id', '>', $singles->id);
                    });
            })
            ->orderBy('date')->orderBy('id')->first();

        return view('db_singles.show', compact('songs', 'singles', 'previous', 'next', 'artist'));
    }
}
