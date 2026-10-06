<?php

namespace App\Http\Controllers;

use App\Models\UserAlbum;
use App\Models\UserArtist;
use App\Models\UserSingle;
use App\Models\UserSong;

// マイページで作ったアーティストのシングル・アルバムの一覧と詳細（公式の Database の Singles / Albums と同じ役割）。
// ほかの Database のページと同じく、ログインしていれば誰でも見られる
class UserDiscController extends Controller
{
    public function index($artistId, string $kind)
    {
        $artist = UserArtist::findOrFail($artistId);
        $model = $kind === 'albums' ? UserAlbum::class : UserSingle::class;
        $discs = $model::where('user_artist_id', $artist->id)->orderBy('date')->orderBy('id')->get();
        $isOwner = $artist->external_user_id === auth('external')->id();
        $numbers = $model::ordinalNumbers($artist->id);

        return view('mypage.user_discs.index', compact('artist', 'kind', 'discs', 'isOwner', 'numbers'));
    }

    public function show($id, string $kind)
    {
        $disc = ($kind === 'albums' ? UserAlbum::class : UserSingle::class)::with('artist')->findOrFail($id);
        $artist = $disc->artist;
        $songs = UserSong::whereIn('id', collect($disc->tracklist ?? [])->pluck('id'))->get()->keyBy('id');
        // ディスクごとに分ける（ディスク番号が無ければ1枚）
        $tracksByDisc = collect($disc->tracklist ?? [])->groupBy(fn ($track) => (int) ($track['disc'] ?? 1))->sortKeys();
        $isOwner = $artist->external_user_id === auth('external')->id();
        $number = $disc::ordinalNumbers($artist->id)[$disc->id] ?? null;

        return view('mypage.user_discs.show', compact('disc', 'kind', 'artist', 'songs', 'tracksByDisc', 'isOwner', 'number'));
    }
}
