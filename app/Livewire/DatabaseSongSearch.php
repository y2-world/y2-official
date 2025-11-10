<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Song;

class DatabaseSongSearch extends Component
{
    public $search = '';
    public $songs = [];

    public function mount()
    {
        $this->loadInitialSongs();
    }

    public function loadInitialSongs()
    {
        $this->songs = Song::orderBy('title')
            ->limit(10)
            ->get()
            ->map(function ($song) {
                return [
                    'id' => $song->id,
                    'title' => $song->title,
                    'artist' => null,
                ];
            })
            ->toArray();
    }

    public function updatedSearch()
    {
        if ($this->search === '') {
            $this->loadInitialSongs();
            return;
        }

        $escapedQuery = str_replace(['%', '_'], ['\%', '\_'], $this->search);

        $this->songs = Song::whereRaw('LOWER(title) LIKE LOWER(?)', [$escapedQuery . '%'])
            ->orderBy('title')
            ->limit(10)
            ->get()
            ->map(function ($song) {
                return [
                    'id' => $song->id,
                    'title' => $song->title,
                    'artist' => null,
                ];
            })
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
