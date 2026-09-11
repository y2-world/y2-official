<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\DbSong;

// My Page専用の曲検索。DatabaseSongSearch（database側、/database/songs/{id}へ遷移）と
// SongSearch（setlists側、SlSongが対象）とは別物。My Pageでは自分の参加履歴（mypage.attendances.index）
// をsong_idで絞り込む先へ遷移させる。
class MyPageSongSearch extends Component
{
    public $search = '';
    public $songs = [];
    public $artistId = null;

    public function mount($artistId = null)
    {
        $this->artistId = $artistId;
        $this->loadInitialSongs();
    }

    public function loadInitialSongs()
    {
        $this->songs = DbSong::when($this->artistId, fn($q) => $q->where('artist_id', $this->artistId))
            ->orderBy('title')
            ->limit(10)
            ->get()
            ->map(fn($song) => ['id' => $song->id, 'title' => $song->title])
            ->toArray();
    }

    public function updatedSearch()
    {
        if ($this->search === '') {
            $this->loadInitialSongs();
            return;
        }

        $escaped = str_replace(['%', '_'], ['\%', '\_'], $this->search);

        $this->songs = DbSong::when($this->artistId, fn($q) => $q->where('artist_id', $this->artistId))
            ->whereRaw('LOWER(title) LIKE LOWER(?)', [$escaped . '%'])
            ->orderBy('title')
            ->limit(10)
            ->get()
            ->map(fn($song) => ['id' => $song->id, 'title' => $song->title])
            ->toArray();
    }

    public function selectSong($songId)
    {
        return redirect(route('mypage.attendances.index', ['song_id' => $songId]));
    }

    public function render()
    {
        return view('livewire.my-page-song-search');
    }
}
