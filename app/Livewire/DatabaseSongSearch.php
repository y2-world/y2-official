<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\DbSong;

class DatabaseSongSearch extends Component
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
        $songs = DbSong::when($this->artistId, fn($q) => $q->where('artist_id', $this->artistId))->get();

        $this->songs = \App\Support\JapaneseNameSorter::sortBy($songs, 'title')
            ->take(10)
            ->map(fn($song) => ['id' => $song->id, 'title' => $song->title])
            ->values()
            ->toArray();
    }

    public function updatedSearch()
    {
        if ($this->search === '') {
            $this->loadInitialSongs();
            return;
        }

        $escaped = str_replace(['%', '_'], ['\%', '\_'], $this->search);

        $songs = DbSong::when($this->artistId, fn($q) => $q->where('artist_id', $this->artistId))
            ->whereRaw('LOWER(title) LIKE LOWER(?)', [$escaped . '%'])
            ->get();

        $this->songs = \App\Support\JapaneseNameSorter::sortBy($songs, 'title')
            ->take(10)
            ->map(fn($song) => ['id' => $song->id, 'title' => $song->title])
            ->values()
            ->toArray();
    }

    public function selectSong($songId)
    {
        return redirect('/database/songs/' . $songId);
    }

    public function render()
    {
        return view('livewire.database-song-search');
    }
}
