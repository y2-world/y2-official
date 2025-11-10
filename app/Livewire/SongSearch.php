<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SetlistSong;

class SongSearch extends Component
{
    public $search = '';
    public $songs = [];
    public $showDropdown = false;
    public $selectedIndex = -1;

    public function mount()
    {
        $this->loadInitialSongs();
    }

    public function loadInitialSongs()
    {
        $this->songs = SetlistSong::query()
            ->leftJoin('artists', 'artists.id', '=', 'setlist_songs.artist_id')
            ->orderBy('setlist_songs.title')
            ->limit(10)
            ->get([
                'setlist_songs.id as id',
                'setlist_songs.title as title',
                'artists.name as artist',
            ])
            ->toArray();
    }

    public function updatedSearch()
    {
        if ($this->search === '') {
            $this->loadInitialSongs();
            return;
        }

        $escapedQuery = str_replace(['%', '_'], ['\%', '\_'], $this->search);

        $this->songs = SetlistSong::query()
            ->leftJoin('artists', 'artists.id', '=', 'setlist_songs.artist_id')
            ->whereRaw('LOWER(setlist_songs.title) LIKE LOWER(?)', [$escapedQuery . '%'])
            ->orderBy('setlist_songs.title')
            ->limit(10)
            ->get([
                'setlist_songs.id as id',
                'setlist_songs.title as title',
                'artists.name as artist',
            ])
            ->toArray();

        $this->selectedIndex = -1;
    }

    public function selectSong($songId)
    {
        return redirect('/setlist-songs/' . $songId);
    }

    public function render()
    {
        return view('livewire.song-search');
    }
}
