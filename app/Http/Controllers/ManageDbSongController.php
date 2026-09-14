<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\DbSong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Manage画面から公式データベース（db_songs）を直接編集する機能。
 * Yuki本人（ExternalUser::isDatabaseManager()）専用。
 * 通常のFilament管理画面と機能は重複するが、Manage画面の使い慣れたUIから
 * 即座に曲の追加・修正ができるようにするためのショートカット。
 */
class ManageDbSongController extends Controller
{
    private function authorizeManager(): void
    {
        $user = Auth::guard('external')->user();
        abort_unless($user && $user->isDatabaseManager(), 403);
    }

    public function songs($artistId)
    {
        $this->authorizeManager();

        $artist = Artist::findOrFail($artistId);
        $songs = DbSong::where('artist_id', $artistId)->orderBy('sort_order')->get();

        return view('mypage.manage.database_songs', compact('artist', 'songs'));
    }

    public function storeSong(Request $request, $artistId)
    {
        $this->authorizeManager();

        $artist = Artist::findOrFail($artistId);

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $nextSortOrder = (DbSong::where('artist_id', $artist->id)->max('sort_order') ?? -1) + 1;

        $song = DbSong::firstOrCreate(
            ['artist_id' => $artist->id, 'title' => $request->input('title')],
            ['sort_order' => $nextSortOrder]
        );

        if (!$song->wasRecentlyCreated) {
            return back()->withErrors(['title' => 'この曲名は既に登録されています。'])->withInput();
        }

        return redirect()->route('mypage.manage.database_songs', $artistId)->with('success', '曲を追加しました。');
    }

    // 楽曲の並べ替え（Ajax）。songIdsは新しい表示順に並んだDbSong.idの配列。
    public function reorderSongs(Request $request, $artistId)
    {
        $this->authorizeManager();

        $validator = Validator::make($request->all(), [
            'song_ids' => ['required', 'array'],
            'song_ids.*' => ['integer'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'invalid request'], 422);
        }

        $songIds = $request->input('song_ids');
        $ownedIds = DbSong::where('artist_id', $artistId)->pluck('id')->all();
        abort_unless(count(array_diff($songIds, $ownedIds)) === 0, 403);

        foreach ($songIds as $index => $songId) {
            DbSong::where('id', $songId)->update(['sort_order' => $index]);
        }

        return response()->json(['status' => 'ok']);
    }

    public function updateSong(Request $request, $artistId, $songId)
    {
        $this->authorizeManager();

        $song = DbSong::where('artist_id', $artistId)->findOrFail($songId);

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $validator->errors()->first()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $song->update(['title' => $request->input('title')]);

        if ($request->wantsJson()) {
            return response()->json(['message' => '曲名を変更しました。', 'title' => $song->title]);
        }

        return redirect()->route('mypage.manage.database_songs', $artistId)->with('success', '曲名を変更しました。');
    }

    public function destroySong(Request $request, $artistId, $songId)
    {
        $this->authorizeManager();

        $song = DbSong::where('artist_id', $artistId)->findOrFail($songId);
        $song->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => '曲を削除しました。']);
        }

        return redirect()->route('mypage.manage.database_songs', $artistId)->with('success', '曲を削除しました。');
    }
}
