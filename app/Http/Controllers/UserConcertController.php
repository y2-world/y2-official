<?php

namespace App\Http\Controllers;

use App\Models\UserArtist;
use App\Models\UserConcert;

/**
 * ユーザー登録アーティストの「ツアー・ライブ情報」一覧（誰でも閲覧できる）。
 * 公式アーティストのDbConcertController::index相当だが、schedule/venue/text等の
 * 列を持たないユーザー登録データ向けに、日付とタイトルだけのシンプルな一覧にしている。
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
}
