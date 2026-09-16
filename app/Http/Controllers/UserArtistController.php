<?php

namespace App\Http\Controllers;

use App\Models\UserArtist;
use App\Support\JapaneseNameSorter;

/**
 * ユーザー登録アーティストの「Database」的な入り口（誰でも閲覧できる）。
 * Timelineの「Users' Database」から遷移し、ここからアーティストごとの
 * ツアー一覧（UserConcertController::index）→セットリスト詳細（::show）へ辿る。
 */
class UserArtistController extends Controller
{
    public function index()
    {
        $artists = JapaneseNameSorter::sortBy(
            UserArtist::withCount(['concerts', 'songs'])->get()
        );

        return view('mypage.user_artists.index', compact('artists'));
    }
}
