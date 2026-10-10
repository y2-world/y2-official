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

    // アーティストを決めずに使うとき（Database のトップのクイック検索）は、公開中のアーティストの曲から探し、候補にアーティスト名も出す
    private function songQuery()
    {
        return DbSong::query()
            ->when($this->artistId, fn($q) => $q->where('artist_id', $this->artistId))
            ->when(!$this->artistId, fn($q) => $q->whereHas('artist', fn($a) => $a->where('visible', 1))->with('artist'));
    }

    private function toSuggestions($songs): array
    {
        return \App\Support\JapaneseNameSorter::sortBy($songs, 'title')
            ->take(10)
            ->map(fn($song) => ['id' => $song->id, 'title' => $song->title, 'artist' => $this->artistId ? null : $song->artist?->name])
            ->values()
            ->toArray();
    }

    public function loadInitialSongs()
    {
        $this->songs = $this->toSuggestions($this->songQuery()->get());
    }

    public function updatedSearch()
    {
        if ($this->search === '') {
            $this->loadInitialSongs();
            return;
        }

        $escaped = str_replace(['%', '_'], ['\%', '\_'], $this->search);

        $songs = $this->songQuery()
            ->whereRaw('LOWER(title) LIKE LOWER(?)', [$escaped . '%'])
            ->get();

        $this->songs = $this->toSuggestions($songs);
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
