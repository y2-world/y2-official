<?php

namespace App\Http\Controllers;

use App\Models\UserArtist;
use App\Models\UserSong;

/**
 * ユーザー登録曲の一覧・詳細ページ（誰でも閲覧できる）。DbSongController相当。
 * アルバム・シングルの概念が無いため、演奏履歴（Live Performances）と前後リンクのみ。
 */
class UserSongController extends Controller
{
    // そのアーティストの全曲一覧（database.songs相当）。ページネーション・検索・
    // アルバム分けは無く、曲名一覧から個別の詳細ページへ遷移できるだけのシンプルな構成。
    public function index($artistId)
    {
        $artist = UserArtist::findOrFail($artistId);
        $songs = UserSong::where('user_artist_id', $artistId)->orderBy('sort_order')->get();

        return view('mypage.user_songs.index', compact('artist', 'songs'));
    }

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
