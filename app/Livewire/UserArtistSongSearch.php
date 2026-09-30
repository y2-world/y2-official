<?php

namespace App\Livewire;

use App\Models\UserSong;
use Livewire\Component;

// ユーザーが登録したアーティストのトップ（mypage.user_artists.show）の曲検索。
// DatabaseSongSearch（公式の曲、/database/songs/{id}へ遷移）と同じ動きで、対象がそのアーティストの UserSong になる
class UserArtistSongSearch extends Component
{
    public $search = '';
    public $songs = [];
    public $artistId = null;

    public function mount($artistId)
    {
        $this->artistId = $artistId;
        $this->loadInitialSongs();
    }

    private function toList($songs): array
    {
        return \App\Support\JapaneseNameSorter::sortBy($songs, 'title')
            ->take(10)
            ->map(fn ($song) => ['id' => $song->id, 'title' => $song->title, 'url' => route('mypage.user_songs.show', $song->id)])
            ->values()
            ->toArray();
    }

    public function loadInitialSongs()
    {
        $this->songs = $this->toList(UserSong::where('user_artist_id', $this->artistId)->get());
    }

    public function updatedSearch()
    {
        if ($this->search === '') {
            $this->loadInitialSongs();
            return;
        }

        $escaped = str_replace(['%', '_'], ['\%', '\_'], $this->search);
        $this->songs = $this->toList(UserSong::where('user_artist_id', $this->artistId)
            ->whereRaw('LOWER(title) LIKE LOWER(?)', [$escaped . '%'])
            ->get());
    }

    public function render()
    {
        return view('livewire.user-artist-song-search');
    }
}
