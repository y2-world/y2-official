<?php

namespace App\Http\Controllers;

use App\Models\UserAlbum;
use App\Models\UserArtist;
use App\Models\UserSingle;
use App\Models\UserSong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Manage My Artists & Setlists：自分が作ったアーティストのシングル・アルバムの登録・編集・削除。
// 収録曲はセットリストの曲目と同じく曲名で入力し、保存のときに UserSong に置き換える（無い曲名は曲として登録する）
class ManageDiscController extends Controller
{
    public function index($artistId)
    {
        $artist = $this->ownArtist($artistId);
        $singles = UserSingle::where('user_artist_id', $artist->id)->orderBy('date')->orderBy('id')->get();
        $albums = UserAlbum::where('user_artist_id', $artist->id)->orderBy('date')->orderBy('id')->get();

        return view('mypage.manage.discs', compact('artist', 'singles', 'albums'));
    }

    public function create(Request $request, $artistId)
    {
        $artist = $this->ownArtist($artistId);
        $kind = $request->query('kind') === 'album' ? 'album' : 'single';
        $disc = null;
        $songOptions = $this->songOptions($artist);
        $discTracks = [[]];

        return view('mypage.manage.disc_form', compact('artist', 'kind', 'disc', 'songOptions', 'discTracks'));
    }

    public function store(Request $request, $artistId)
    {
        $artist = $this->ownArtist($artistId);
        $kind = $request->input('kind') === 'album' ? 'album' : 'single';
        $data = $this->validated($request);

        DB::transaction(function () use ($artist, $kind, $data, $request) {
            $attributes = $this->attributes($artist, $kind, $data, $request);
            $kind === 'album' ? UserAlbum::create($attributes) : UserSingle::create($attributes);
        });

        return redirect()->route('mypage.manage.discs', $artist->id)
            ->with('success', ($kind === 'album' ? 'アルバム' : 'シングル') . 'を追加しました。');
    }

    public function edit($artistId, $kind, $discId)
    {
        $artist = $this->ownArtist($artistId);
        $disc = $this->ownDisc($artist, $kind, $discId);
        $songOptions = $this->songOptions($artist);

        // 収録曲をディスクごとの曲名の並びにする（シングルは1枚）
        $titles = UserSong::whereIn('id', collect($disc->tracklist ?? [])->pluck('id'))->pluck('title', 'id');
        $discTracks = [];
        foreach ($disc->tracklist ?? [] as $track) {
            $discTracks[max(0, (int) ($track['disc'] ?? 1) - 1)][] = $titles[$track['id']] ?? '';
        }
        ksort($discTracks);
        $discTracks = array_values($discTracks) ?: [[]];

        return view('mypage.manage.disc_form', compact('artist', 'kind', 'disc', 'songOptions', 'discTracks'));
    }

    public function update(Request $request, $artistId, $kind, $discId)
    {
        $artist = $this->ownArtist($artistId);
        $disc = $this->ownDisc($artist, $kind, $discId);
        $data = $this->validated($request);

        DB::transaction(function () use ($artist, $kind, $data, $request, $disc) {
            $disc->update($this->attributes($artist, $kind, $data, $request));
        });

        return redirect()->route('mypage.manage.discs', $artist->id)
            ->with('success', ($kind === 'album' ? 'アルバム' : 'シングル') . 'を更新しました。');
    }

    public function destroy($artistId, $kind, $discId)
    {
        $artist = $this->ownArtist($artistId);
        $this->ownDisc($artist, $kind, $discId)->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => '削除しました。']);
        }

        return redirect()->route('mypage.manage.discs', $artist->id)->with('success', '削除しました。');
    }

    // 作った本人のアーティストだけ
    private function ownArtist($artistId): UserArtist
    {
        $artist = UserArtist::findOrFail($artistId);
        abort_unless($artist->external_user_id === Auth::guard('external')->id(), 403);

        return $artist;
    }

    private function ownDisc(UserArtist $artist, string $kind, $discId)
    {
        abort_unless(in_array($kind, ['single', 'album'], true), 404);
        $model = $kind === 'album' ? UserAlbum::class : UserSingle::class;

        return $model::where('user_artist_id', $artist->id)->findOrFail($discId);
    }

    private function songOptions(UserArtist $artist)
    {
        return UserSong::where('user_artist_id', $artist->id)->orderBy('sort_order')->pluck('title');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'tracks' => ['array'],
            'tracks.*' => ['array'],
            'tracks.*.*' => ['nullable', 'string', 'max:255'],
        ], [
            'title.required' => 'タイトルを入力してください。',
        ]);
    }

    // 入力から保存する値を作る。曲名は UserSong に置き換え、空の行は捨てる。アルバムはディスク番号を付ける
    private function attributes(UserArtist $artist, string $kind, array $data, Request $request): array
    {
        $tracklist = [];
        foreach (array_values($data['tracks'] ?? []) as $discIndex => $titles) {
            foreach ($titles as $title) {
                $title = trim((string) $title);
                if ($title === '') {
                    continue;
                }
                $song = UserSong::firstOrCreateByTitle($artist->id, $title);
                $tracklist[] = $kind === 'album' ? ['id' => $song->id, 'disc' => $discIndex + 1] : ['id' => $song->id];
            }
        }
        // ディスクが1枚だけのアルバムは、ディスク番号を持たない（公式と同じ）
        if ($kind === 'album' && collect($tracklist)->pluck('disc')->unique()->count() <= 1) {
            $tracklist = array_map(fn ($track) => ['id' => $track['id']], $tracklist);
        }

        $attributes = [
            'user_artist_id' => $artist->id,
            'external_user_id' => Auth::guard('external')->id(),
            'title' => $data['title'],
            'date' => $data['date'] ?? null,
            'tracklist' => $tracklist,
        ];

        return $kind === 'album'
            ? $attributes + ['mini' => $request->boolean('mini'), 'best' => $request->boolean('best')]
            : $attributes + ['ep' => $request->boolean('ep')];
    }
}
