<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\SlSetlist;

class VenueSearch extends Component
{
    public $search = '';
    public $venues = [];
    public $showDropdown = false;
    public $selectedIndex = -1;
    // My Page の会場のページで使うとき：その人（external_users.id）の参加記録の会場から探し、My Page の会場のページへ飛ぶ
    public $myPageUserId = null;
    // My Page で、他の人の一覧を見ているとき（?user_id=）。自分のときは null
    public $userIdParam = null;
    // Database のアーティストの会場（Venues）のページで使うとき：そのアーティストのツアー・ライブのスケジュールの会場から探す（部分一致）
    public $dbArtistId = null;

    public function mount($myPageUserId = null, $userIdParam = null, $dbArtistId = null)
    {
        $this->myPageUserId = $myPageUserId;
        $this->userIdParam = $userIdParam;
        $this->dbArtistId = $dbArtistId;
        $this->loadInitialVenues();
    }

    // Database の会場：公演の多い順に、打った文字を含む会場
    private function scheduleVenueNames(string $search = '')
    {
        return \App\Models\DbConcert::venuePageEntries((int) $this->dbArtistId)
            ->countBy('venue')
            ->filter(fn ($count, $venue) => $search === '' || mb_stripos($venue, $search) !== false)
            ->sortDesc()
            ->keys()
            ->take(10);
    }

    private function venueQuery()
    {
        $query = $this->myPageUserId
            ? \App\Models\ExternalUserAttendance::query()->where('external_user_id', $this->myPageUserId)
            : SlSetlist::query();

        return $query->whereNotNull('venue')->where('venue', '!=', '');
    }

    private function toVenues($names): array
    {
        return collect($names)
            ->map(fn ($venue) => [
                'name' => $venue,
                'url' => match (true) {
                    (bool) $this->dbArtistId => route('database.venues', [$this->dbArtistId, 'keyword' => $venue]),
                    (bool) $this->myPageUserId => route('mypage.attendances.index', array_filter(['user_id' => $this->userIdParam, 'venue' => $venue])),
                    default => '/venue?keyword=' . urlencode($venue),
                },
            ])
            ->toArray();
    }

    public function loadInitialVenues()
    {
        if ($this->dbArtistId) {
            $this->venues = $this->toVenues($this->scheduleVenueNames());
            return;
        }
        $this->venues = $this->toVenues($this->venueQuery()->distinct()->orderBy('venue')->limit(10)->pluck('venue'));
    }

    public function updatedSearch()
    {
        if ($this->search === '') {
            $this->loadInitialVenues();
            return;
        }

        if ($this->dbArtistId) {
            $this->venues = $this->toVenues($this->scheduleVenueNames(trim($this->search)));
            $this->selectedIndex = -1;
            return;
        }

        $escapedQuery = str_replace(['%', '_'], ['\%', '\_'], $this->search);

        $this->venues = $this->toVenues($this->venueQuery()
            ->whereRaw('LOWER(venue) LIKE LOWER(?)', [$escapedQuery . '%'])
            ->distinct()
            ->orderBy('venue')
            ->limit(10)
            ->pluck('venue'));

        $this->selectedIndex = -1;
    }

    public function selectVenue($venueName)
    {
        return redirect('/venue?keyword=' . urlencode($venueName));
    }

    public function render()
    {
        return view('livewire.venue-search');
    }
}

