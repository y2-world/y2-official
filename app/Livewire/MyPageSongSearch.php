<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\DbSetlist;
use App\Models\DbSong;
use Illuminate\Support\Facades\Auth;

// My Page専用の曲検索。DatabaseSongSearch（database側、/database/songs/{id}へ遷移）と
// SongSearch（setlists側、SlSongが対象）とは別物。My Pageでは自分の参加履歴（mypage.attendances.index）
// をsong_idで絞り込む先へ遷移させる。検索候補も「自分が実際に参加したライブで演奏された曲」だけに絞る。
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

    private function heardSongIds()
    {
        $attendedSetlistIds = Auth::guard('external')->user()
            ->attendances()
            ->whereHas('dbSetlist.tour', fn($q) => $q->when($this->artistId, fn($q2) => $q2->where('artist_id', $this->artistId)))
            ->pluck('db_setlist_id')
            ->unique();

        $setlists = DbSetlist::whereIn('id', $attendedSetlistIds)->get();

        $songIds = [];
        foreach ($setlists as $setlist) {
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                if (isset($s['song']) && is_numeric($s['song'])) {
                    $songIds[] = (int)$s['song'];
                }
            }
        }

        return array_unique($songIds);
    }

    public function loadInitialSongs()
    {
        $this->songs = DbSong::with('artist')
            ->whereIn('id', $this->heardSongIds())
            ->orderBy('title')
            ->limit(10)
            ->get()
            ->map(fn($song) => ['id' => $song->id, 'title' => $song->title, 'artist' => $song->artist?->name])
            ->toArray();
    }

    public function updatedSearch()
    {
        if ($this->search === '') {
            $this->loadInitialSongs();
            return;
        }

        $escaped = str_replace(['%', '_'], ['\%', '\_'], $this->search);

        $this->songs = DbSong::with('artist')
            ->whereIn('id', $this->heardSongIds())
            ->whereRaw('LOWER(title) LIKE LOWER(?)', [$escaped . '%'])
            ->orderBy('title')
            ->limit(10)
            ->get()
            ->map(fn($song) => ['id' => $song->id, 'title' => $song->title, 'artist' => $song->artist?->name])
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
