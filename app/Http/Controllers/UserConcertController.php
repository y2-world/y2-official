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
            ->withCount('setlists')
            ->with('externalUser')
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
        $songs = UserSong::where('user_artist_id', $tour->user_artist_id)->orderBy('id', 'asc')->get();
        $tourSetlists = UserSetlist::where('user_concert_id', $id)->orderBy('row', 'asc')->orderBy('order_no', 'asc')->get();

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

        return view('mypage.user_concerts.show', compact('songs', 'previous', 'next', 'tour', 'tourSetlists', 'artist'));
    }
}
