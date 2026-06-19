<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SlSong;

class SongSearch extends Component
{
    public $search = '';
    public $songs = [];
    public $showDropdown = false;
    public $selectedIndex = -1;
    public $artistId = null; // アーティストIDをフィルタリング用に追加

    public function mount($artistId = null)
    {
        $this->artistId = $artistId;
        $this->loadInitialSongs();
    }

    public function loadInitialSongs()
    {
        $query = SlSong::query();

        // アーティストIDが指定されている場合はフィルタリング（アーティスト名は表示しない）
        if ($this->artistId) {
            $query->where('sl_songs.artist_id', $this->artistId);

            $this->songs = $query
                ->orderBy('sl_songs.title')
                ->limit(10)
                ->get([
                    'sl_songs.id as id',
                    'sl_songs.title as title',
                ])
                ->toArray();
        } else {
            // アーティストIDが指定されていない場合は全楽曲をアーティスト名付きで表示
            $query->leftJoin('artists', 'artists.id', '=', 'sl_songs.artist_id');

            $this->songs = $query
                ->orderBy('sl_songs.title')
                ->limit(10)
                ->get([
                    'sl_songs.id as id',
                    'sl_songs.title as title',
                    'artists.name as artist',
                ])
                ->toArray();
        }
    }

    public function updatedSearch()
    {
        if ($this->search === '') {
            $this->loadInitialSongs();
            return;
        }

        $escapedQuery = str_replace(['%', '_'], ['\%', '\_'], $this->search);

        $query = SlSong::query()
            ->whereRaw('LOWER(setlist_songs.title) LIKE LOWER(?)', [$escapedQuery . '%']);

        // アーティストIDが指定されている場合はフィルタリング（アーティスト名は表示しない）
        if ($this->artistId) {
            $query->where('sl_songs.artist_id', $this->artistId);

            $this->songs = $query
                ->orderBy('sl_songs.title')
                ->limit(10)
                ->get([
                    'sl_songs.id as id',
                    'sl_songs.title as title',
                ])
                ->toArray();
        } else {
            // アーティストIDが指定されていない場合は全楽曲をアーティスト名付きで表示
            $query->leftJoin('artists', 'artists.id', '=', 'sl_songs.artist_id');

            $this->songs = $query
                ->orderBy('sl_songs.title')
                ->limit(10)
                ->get([
                    'sl_songs.id as id',
                    'sl_songs.title as title',
                    'artists.name as artist',
                ])
                ->toArray();
        }

        $this->selectedIndex = -1;
    }

    public function selectSong($songId)
    {
        return redirect('/setlists/songs/' . $songId);
    }

    public function render()
    {
        return view('livewire.song-search');
    }
}
