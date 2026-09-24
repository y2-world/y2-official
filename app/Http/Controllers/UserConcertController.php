<?php

namespace App\Http\Controllers;

use App\Models\UserArtist;
use App\Models\UserConcert;
use App\Models\UserSetlist;
use App\Models\UserSong;

/**
 * ユーザー登録アーティストの「ツアー・ライブ情報」（誰でも閲覧できる）。
 * 公式アーティストのDbConcertController相当だが、schedule/venue/text等の
 * 列を持たないユーザー登録データ向けに、必要な項目だけのシンプルな構成にしている。
 */
class UserConcertController extends Controller
{
    public function index($artistId)
    {
        $artist = UserArtist::findOrFail($artistId);

        $tours = UserConcert::where('user_artist_id', $artistId)
            ->orderByDesc('date1')
            ->orderByDesc('id')
            ->get();

        return view('mypage.user_concerts.index', compact('artist', 'tours'));
    }

    // セットリスト内容だけを表示する閲覧専用ページ。参加登録の導線は一切持たない
    // （mypage.attendances.setlistsは登録フローの一部で「選択」「新規パターン追加」ボタンを持つため別画面にしている）。
    public function show($id)
    {
        $tour = UserConcert::findOrFail($id);
        $artist = $tour->artist;
        $songs = UserSong::where('user_artist_id', $tour->user_artist_id)->orderBy('sort_order', 'asc')->get();
        $tourSetlists = UserSetlist::where('user_concert_id', $id)->orderBy('row', 'asc')->orderBy('order_no', 'asc')->get();

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

        $previous = UserConcert::where('user_artist_id', $tour->user_artist_id)
            ->where(function ($q) use ($tour) {
                $q->where('date1', '<', $tour->date1)
                  ->orWhere(function ($q2) use ($tour) {
                      $q2->where('date1', $tour->date1)->where('id', '<', $tour->id);
                  });
            })->orderBy('date1', 'desc')->orderBy('id', 'desc')->first();
        $next = UserConcert::where('user_artist_id', $tour->user_artist_id)
            ->where(function ($q) use ($tour) {
                $q->where('date1', '>', $tour->date1)
                  ->orWhere(function ($q2) use ($tour) {
                      $q2->where('date1', $tour->date1)->where('id', '>', $tour->id);
                  });
            })->orderBy('date1')->orderBy('id')->first();

        return view('mypage.user_concerts.show', compact('songs', 'previous', 'next', 'tour', 'tourSetlists', 'artist', 'setlistSummaries'));
    }
}
