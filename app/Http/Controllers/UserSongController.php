<?php

namespace App\Http\Controllers;

use App\Models\UserArtist;
use App\Models\UserSong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ユーザー登録曲の一覧・詳細ページ（認証必須。mypage配下）。DbSongController相当。
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

    public function show(Request $request, $id)
    {
        $song = UserSong::findOrFail($id);
        $tourSetlists = $song->performedTourSetlists();
        $tours = $tourSetlists->pluck('concert')->filter()->unique('id')->values();

        // このページは認証必須（mypage配下）なので常にログイン中。「Live Performances」
        // （この曲が演奏された全ライブ）と「My Live Attendances」（自分の参加記録）を
        // 常に2タブ構成で出す。
        $secondTabSetlists = $song->myAttendedTours($tourSetlists);

        // Previous/Nextで選んだタブをキープしたまま移動できるよう、?tab=mine をURLで引き継ぐ
        $secondTab = $request->query('tab') === 'mine' ? 'mine' : 'performances';

        // Live Performancesタブ用：アーティスト内のsort_order順位・前後の曲
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

        // My Live Attendancesタブ用：自分の参加記録内での初めて聴いた順・前後の曲
        $firstSeenOrder = UserSong::firstSeenOrderFor(Auth::guard('external')->user(), $song->user_artist_id);
        $songNumberMine = $firstSeenOrder[$song->id] ?? null;
        $orderedSongIds = array_keys($firstSeenOrder);
        $currentIndex = array_search($song->id, $orderedSongIds, true);
        $previousSongIdMine = $currentIndex !== false && $currentIndex > 0 ? $orderedSongIds[$currentIndex - 1] : null;
        $nextSongIdMine = $currentIndex !== false && $currentIndex < count($orderedSongIds) - 1 ? $orderedSongIds[$currentIndex + 1] : null;
        $previousMine = $previousSongIdMine ? UserSong::find($previousSongIdMine) : null;
        $nextMine = $nextSongIdMine ? UserSong::find($nextSongIdMine) : null;

        return view('mypage.user_songs.show', compact(
            'song', 'tours', 'previous', 'next', 'songNumber', 'secondTab', 'secondTabSetlists',
            'songNumberMine', 'previousMine', 'nextMine'
        ));
    }
}
