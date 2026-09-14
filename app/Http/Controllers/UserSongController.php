<?php

namespace App\Http\Controllers;

use App\Models\UserSong;

/**
 * ユーザー登録曲の詳細ページ（誰でも閲覧できる）。DbSongController::show相当。
 * アルバム・シングルの概念が無いため、演奏履歴（Live Performances）と前後リンクのみ。
 */
class UserSongController extends Controller
{
    public function show($id)
    {
        $song = UserSong::findOrFail($id);
        $tourSetlists = $song->performedTourSetlists();
        $tours = $tourSetlists->pluck('concert')->filter()->unique('id')->values();

        $previous = UserSong::where('user_artist_id', $song->user_artist_id)
            ->where('sort_order', '<', $song->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();
        $next = UserSong::where('user_artist_id', $song->user_artist_id)
            ->where('sort_order', '>', $song->sort_order)
            ->orderBy('sort_order')
            ->first();
        $songNumber = UserSong::where('user_artist_id', $song->user_artist_id)
            ->where('sort_order', '<=', $song->sort_order)
            ->count();

        return view('mypage.user_songs.show', compact('song', 'tours', 'previous', 'next', 'songNumber'));
    }
}
