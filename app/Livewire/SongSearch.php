<?php

namespace App\Livewire;

use Illuminate\Support\Facades\DB;
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

            // アーティスト表記（グループのメンバーのソロ曲など）がある曲だけ、その表記を出す（Database の曲の検索と同じ）
            $songs = $query->get([
                'sl_songs.id as id',
                'sl_songs.title as title',
                DB::raw("NULLIF(TRIM(sl_songs.credit), '') as artist"),
            ]);
        } else {
            // アーティストIDが指定されていない場合は全楽曲をアーティスト名付きで表示
            $query->leftJoin('artists', 'artists.id', '=', 'sl_songs.artist_id');

            // アーティスト表記があれば、アーティスト名の代わりにそれを出す（Database の曲の検索と同じ）
            $songs = $query->get([
                'sl_songs.id as id',
                'sl_songs.title as title',
                DB::raw("COALESCE(NULLIF(TRIM(sl_songs.credit), ''), artists.name) as artist"),
            ]);
        }

        $this->songs = \App\Support\JapaneseNameSorter::sortBy($songs, 'title')
            ->take(10)
            ->values()
            ->toArray();
    }

    public function updatedSearch()
    {
        if ($this->search === '') {
            $this->loadInitialSongs();
            return;
        }

        $escapedQuery = str_replace(['%', '_'], ['\%', '\_'], $this->search);

        $query = SlSong::query()
            ->whereRaw('LOWER(sl_songs.title) LIKE LOWER(?)', [$escapedQuery . '%']);

        // アーティストIDが指定されている場合はフィルタリング（アーティスト名は表示しない）
        if ($this->artistId) {
            $query->where('sl_songs.artist_id', $this->artistId);

            // アーティスト表記（グループのメンバーのソロ曲など）がある曲だけ、その表記を出す（Database の曲の検索と同じ）
            $songs = $query->get([
                'sl_songs.id as id',
                'sl_songs.title as title',
                DB::raw("NULLIF(TRIM(sl_songs.credit), '') as artist"),
            ]);
        } else {
            // アーティストIDが指定されていない場合は全楽曲をアーティスト名付きで表示
            $query->leftJoin('artists', 'artists.id', '=', 'sl_songs.artist_id');

            // アーティスト表記があれば、アーティスト名の代わりにそれを出す（Database の曲の検索と同じ）
            $songs = $query->get([
                'sl_songs.id as id',
                'sl_songs.title as title',
                DB::raw("COALESCE(NULLIF(TRIM(sl_songs.credit), ''), artists.name) as artist"),
            ]);
        }

        $this->songs = \App\Support\JapaneseNameSorter::sortBy($songs, 'title')
            ->take(10)
            ->values()
            ->toArray();

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
