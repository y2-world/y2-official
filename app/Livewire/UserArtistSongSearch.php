<?php

namespace App\Livewire;

use App\Models\UserSong;
use Livewire\Component;

// ユーザーが登録したアーティストのトップ（mypage.user_artists.show）の曲検索。
// DatabaseSongSearch（公式の曲、/database/songs/{id}へ遷移）と同じ動きで、対象がそのアーティストの UserSong になる。
// アーティストを決めずに使うとき（My Page の Database のクイック検索）は、カードと同じく
// 公式の Database のアーティスト（公開中）の曲と、マイページで作られたアーティストの曲の両方から探し、候補にアーティスト名も出す
class UserArtistSongSearch extends Component
{
    public $search = '';
    public $songs = [];
    public $artistId = null;

    public function mount($artistId = null)
    {
        $this->artistId = $artistId;
        $this->loadInitialSongs();
    }

    private function toList($songs): array
    {
        return \App\Support\JapaneseNameSorter::sortBy($songs, 'title')
            ->take(10)
            ->map(fn ($song) => $song instanceof \App\Models\DbSong
                ? ['id' => 'official-' . $song->id, 'title' => $song->title, 'url' => url('/database/songs/' . $song->id), 'artist' => $song->artist?->name]
                : ['id' => 'user-' . $song->id, 'title' => $song->title, 'url' => route('mypage.user_songs.show', $song->id), 'artist' => $this->artistId ? null : $song->artist?->name])
            ->values()
            ->toArray();
    }

    // 探す曲（$titleLike があれば、その文字から始まる曲）
    private function findSongs(?string $titleLike = null)
    {
        $filter = fn ($q) => $titleLike === null ? $q : $q->whereRaw('LOWER(title) LIKE LOWER(?)', [$titleLike . '%']);
        if ($this->artistId) {
            return $filter(UserSong::where('user_artist_id', $this->artistId))->get();
        }

        return $filter(\App\Models\DbSong::whereHas('artist', fn ($a) => $a->where('visible', 1))->with('artist'))->get()
            ->concat($filter(UserSong::query()->with('artist'))->get());
    }

    public function loadInitialSongs()
    {
        $this->songs = $this->toList($this->findSongs());
    }

    public function updatedSearch()
    {
        if ($this->search === '') {
            $this->loadInitialSongs();
            return;
        }

        $escaped = str_replace(['%', '_'], ['\%', '\_'], $this->search);
        $this->songs = $this->toList($this->findSongs($escaped));
    }

    public function render()
    {
        return view('livewire.user-artist-song-search');
    }
}
