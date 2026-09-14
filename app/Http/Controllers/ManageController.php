<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\UserArtist;
use App\Models\UserConcert;
use App\Models\UserSetlist;
use App\Models\UserSong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * 「Manage My Artists & Setlists」画面。
 * ユーザー登録データ（user_artists/user_concerts/user_setlists/user_songs）は
 * 誰でも閲覧・追加できるが、削除と楽曲の並べ替えは作成した本人だけができる
 * （間違って登録した場合の取り消し手段という位置づけ）。
 */
class ManageController extends Controller
{
    // 自分が作成したアーティスト一覧
    public function index()
    {
        $user = Auth::guard('external')->user();

        $artists = UserArtist::where('external_user_id', $user->id)
            ->withCount(['concerts', 'songs'])
            ->orderBy('name')
            ->get();

        // Yuki本人だけ、公式データベース（db_songs）を直接編集する導線も表示する。
        $isDatabaseManager = $user->isDatabaseManager();
        $databaseArtists = $isDatabaseManager
            ? Artist::where('visible', 1)->withCount('songs')->orderBy('id')->get()
            : collect();

        return view('mypage.manage.index', compact('artists', 'isDatabaseManager', 'databaseArtists'));
    }

    // アーティスト詳細（入り口）：「曲を管理」「ツアーを管理」への案内のみ
    public function artist($artistId)
    {
        $userId = Auth::guard('external')->id();

        $artist = UserArtist::findOrFail($artistId);
        abort_unless($artist->external_user_id === $userId, 403);

        $songsCount = UserSong::where('user_artist_id', $artistId)->count();
        $concertsCount = UserConcert::where('user_artist_id', $artistId)->count();

        return view('mypage.manage.artist', compact('artist', 'songsCount', 'concertsCount'));
    }

    // 曲一覧画面（並べ替え・削除・追加）
    public function songs($artistId)
    {
        $userId = Auth::guard('external')->id();

        $artist = UserArtist::findOrFail($artistId);
        abort_unless($artist->external_user_id === $userId, 403);

        $songs = UserSong::where('user_artist_id', $artistId)->orderBy('sort_order')->get();

        return view('mypage.manage.songs', compact('artist', 'songs'));
    }

    // ツアー一覧画面（削除）
    public function concerts($artistId)
    {
        $userId = Auth::guard('external')->id();

        $artist = UserArtist::findOrFail($artistId);
        abort_unless($artist->external_user_id === $userId, 403);

        $concerts = UserConcert::where('user_artist_id', $artistId)
            ->withCount('setlists')
            ->orderByDesc('date1')
            ->orderByDesc('id')
            ->get();

        return view('mypage.manage.concerts', compact('artist', 'concerts'));
    }

    // ツアー配下のセットリストパターン一覧画面（削除）
    public function setlists($artistId, $concertId)
    {
        $userId = Auth::guard('external')->id();

        $artist = UserArtist::findOrFail($artistId);
        abort_unless($artist->external_user_id === $userId, 403);

        $concert = UserConcert::where('user_artist_id', $artistId)->findOrFail($concertId);

        $setlists = UserSetlist::where('user_concert_id', $concertId)
            ->orderBy('order_no')
            ->get();

        $songTitles = UserSong::where('user_artist_id', $artistId)->pluck('title', 'id');

        return view('mypage.manage.setlists', compact('artist', 'concert', 'setlists', 'songTitles'));
    }

    // セットリストパターンの曲目を編集（setlist_create画面と同じ形式のtitle配列を受け取る）
    public function updateSetlist(Request $request, $artistId, $concertId, $setlistId)
    {
        $userId = Auth::guard('external')->id();

        $artist = UserArtist::findOrFail($artistId);
        abort_unless($artist->external_user_id === $userId, 403);

        $concert = UserConcert::where('user_artist_id', $artistId)->findOrFail($concertId);
        $setlist = UserSetlist::where('user_concert_id', $concertId)->findOrFail($setlistId);
        abort_unless($setlist->external_user_id === $userId, 403);

        $validator = Validator::make($request->all(), [
            'subtitle' => ['nullable', 'string', 'max:255'],
            'setlist' => ['array'],
            'setlist.*' => ['nullable', 'string', 'max:255'],
            'encore' => ['array'],
            'encore.*' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $toSongIds = function (array $titles) use ($artistId) {
            $items = [];
            foreach ($titles as $title) {
                $title = trim((string) $title);
                if ($title === '') {
                    continue;
                }
                $song = UserSong::firstOrCreate(
                    ['user_artist_id' => $artistId, 'title' => $title],
                    ['sort_order' => (UserSong::where('user_artist_id', $artistId)->max('sort_order') ?? -1) + 1]
                );
                $items[] = ['song' => (string) $song->id];
            }
            return $items;
        };

        $setlist->update([
            'subtitle' => $request->input('subtitle') ?: null,
            'setlist' => $toSongIds($request->input('setlist', [])),
            'encore' => $toSongIds($request->input('encore', [])),
        ]);

        return redirect()->route('mypage.manage.setlists', [$artistId, $concertId])->with('success', 'セットリストを更新しました。');
    }

    // ツアー情報（タイトル・開催期間）の編集
    public function updateConcert(Request $request, $artistId, $concertId)
    {
        $userId = Auth::guard('external')->id();

        $artist = UserArtist::findOrFail($artistId);
        abort_unless($artist->external_user_id === $userId, 403);

        $concert = UserConcert::where('user_artist_id', $artistId)->findOrFail($concertId);
        abort_unless($concert->external_user_id === $userId, 403);

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'date1' => ['nullable', 'date'],
            'date2' => ['nullable', 'date'],
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $validator->errors()->first()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $concert->update([
            'title' => $request->input('title'),
            'date1' => $request->input('date1') ?: null,
            'date2' => $request->input('date2') ?: null,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'ツアー情報を更新しました。']);
        }

        return redirect()->route('mypage.manage.setlists', [$artistId, $concertId])->with('success', 'ツアー情報を更新しました。');
    }

    // 楽曲を追加（並べ替えとは独立して、セトリを介さずに直接曲だけ登録したい場合用）
    public function storeSong(Request $request, $artistId)
    {
        $userId = Auth::guard('external')->id();

        $artist = UserArtist::findOrFail($artistId);
        abort_unless($artist->external_user_id === $userId, 403);

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $nextSortOrder = (UserSong::where('user_artist_id', $artistId)->max('sort_order') ?? -1) + 1;

        $song = UserSong::firstOrCreate(
            ['user_artist_id' => $artistId, 'title' => $request->input('title')],
            ['sort_order' => $nextSortOrder]
        );

        if (!$song->wasRecentlyCreated) {
            return back()->withErrors(['title' => 'この曲名は既に登録されています。'])->withInput();
        }

        return redirect()->route('mypage.manage.songs', $artistId)->with('success', '曲を追加しました。');
    }

    // 楽曲名の編集（インライン編集からのAjaxで呼ばれる想定。JSONで結果を返す）
    public function updateSong(Request $request, $artistId, $songId)
    {
        $userId = Auth::guard('external')->id();

        $artist = UserArtist::findOrFail($artistId);
        abort_unless($artist->external_user_id === $userId, 403);

        $song = UserSong::where('user_artist_id', $artistId)->findOrFail($songId);

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

        return redirect()->route('mypage.manage.songs', $artistId)->with('success', '曲名を変更しました。');
    }

    // 楽曲の並べ替え（Ajax）。songIdsは新しい表示順に並んだUserSong.idの配列。
    public function reorderSongs(Request $request, $artistId)
    {
        $userId = Auth::guard('external')->id();

        $artist = UserArtist::findOrFail($artistId);
        abort_unless($artist->external_user_id === $userId, 403);

        $validator = Validator::make($request->all(), [
            'song_ids' => ['required', 'array'],
            'song_ids.*' => ['integer'],
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'invalid request'], 422);
        }

        $songIds = $request->input('song_ids');
        // このアーティストに属さないidが紛れ込んでいないか確認してから並べ替える
        $ownedIds = UserSong::where('user_artist_id', $artistId)->pluck('id')->all();
        abort_unless(count(array_diff($songIds, $ownedIds)) === 0, 403);

        foreach ($songIds as $index => $songId) {
            UserSong::where('id', $songId)->update(['sort_order' => $index]);
        }

        return response()->json(['status' => 'ok']);
    }

    // 楽曲の削除（作成者本人のみ。曲自体には作成者の記録が無いので、
    // アーティストの所有者で判定する＝アーティストを作った人だけが曲も削除できる）
    public function destroySong(Request $request, $artistId, $songId)
    {
        $userId = Auth::guard('external')->id();

        $artist = UserArtist::findOrFail($artistId);
        abort_unless($artist->external_user_id === $userId, 403);

        $song = UserSong::where('user_artist_id', $artistId)->findOrFail($songId);
        $song->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => '曲を削除しました。']);
        }

        return redirect()->route('mypage.manage.songs', $artistId)->with('success', '曲を削除しました。');
    }

    // アーティストの削除（配下のツアー・セットリストパターンも連鎖削除される）
    public function destroyArtist(Request $request, $artistId)
    {
        $userId = Auth::guard('external')->id();

        $artist = UserArtist::findOrFail($artistId);
        abort_unless($artist->external_user_id === $userId, 403);

        $artist->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'アーティストを削除しました。']);
        }

        return redirect()->route('mypage.manage.index')->with('success', 'アーティストを削除しました。');
    }

    // ツアーの削除（配下のセットリストパターンも連鎖削除される）
    public function destroyConcert(Request $request, $artistId, $concertId)
    {
        $userId = Auth::guard('external')->id();

        $artist = UserArtist::findOrFail($artistId);
        abort_unless($artist->external_user_id === $userId, 403);

        $concert = UserConcert::where('user_artist_id', $artistId)->findOrFail($concertId);
        abort_unless($concert->external_user_id === $userId, 403);
        $concert->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'ツアーを削除しました。']);
        }

        return redirect()->route('mypage.manage.concerts', $artistId)->with('success', 'ツアーを削除しました。');
    }

    // セットリストパターン単体の削除（そのパターンに紐づく出席記録も一緒に消える）
    public function destroySetlist(Request $request, $artistId, $concertId, $setlistId)
    {
        $userId = Auth::guard('external')->id();

        $artist = UserArtist::findOrFail($artistId);
        abort_unless($artist->external_user_id === $userId, 403);

        $setlist = UserSetlist::where('user_concert_id', $concertId)->findOrFail($setlistId);
        abort_unless($setlist->external_user_id === $userId, 403);
        $setlist->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'セットリストを削除しました。']);
        }

        return redirect()->route('mypage.manage.concerts', $artistId)->with('success', 'セットリストを削除しました。');
    }
}
