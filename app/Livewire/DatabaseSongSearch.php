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

    // 候補：登録した曲と、セットリストに名前だけで入っている曲（会場と同じように、登録していない曲も探せる）。
    // 名前だけの曲は、その曲を演奏したライブの一覧（database.text_song）へ
    private function toSuggestions($songs, string $prefix = ''): array
    {
        $rows = $songs->map(fn($song) => ['id' => $song->id, 'title' => $song->title, 'artist' => $this->artistId ? null : $song->artist?->name,
            'url' => '/database/songs/' . $song->id]);
        $registered = $songs->map(fn($song) => $song->artist_id . "\0" . mb_strtolower($song->title))->flip();
        $artistNames = \App\Models\Artist::pluck('name', 'id');
        foreach (\App\Support\TextSongs::all($this->artistId) as $text) {
            if ($prefix !== '' && !str_starts_with(mb_strtolower($text['title']), mb_strtolower($prefix))) {
                continue;
            }
            if (isset($registered[$text['artist_id'] . "\0" . mb_strtolower($text['title'])])) {
                continue;
            }
            $rows->push(['id' => 'text:' . $text['artist_id'] . ':' . $text['title'], 'title' => $text['title'],
                'artist' => $this->artistId ? null : ($artistNames[$text['artist_id']] ?? null),
                'url' => route('database.text_song', ['artistId' => $text['artist_id'], 'title' => $text['title']])]);
        }

        return \App\Support\JapaneseNameSorter::sortBy($rows->map(fn ($row) => (object) $row), 'title')
            ->take(10)
            ->map(fn ($row) => (array) $row)
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

        $this->songs = $this->toSuggestions($songs, $this->search);
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
