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

    // Database の会場：公演の多い順に、打った文字を含む会場（今の会場名。改称する前の名前で探しても出る）
    private function scheduleVenueNames(string $search = '')
    {
        $aliases = \App\Models\VenueAlias::aliasesByVenue();
        $entries = \App\Models\DbConcert::venuePageEntries((int) $this->dbArtistId);
        // 会場ごとに、探せる名前（会場名・スケジュールの名前・同じ会場の前の名前）
        $searchNames = $entries->groupBy('venue')->map(fn ($rows) => $rows->pluck('name')
            ->merge($rows->pluck('group'))->merge($rows->flatMap(fn ($row) => $aliases[$row['group']] ?? []))->unique());

        return $entries
            ->countBy('venue')
            ->filter(fn ($count, $venue) => $search === '' || collect([$venue, ...$searchNames[$venue]])->contains(fn ($name) => mb_stripos($name, $search) !== false))
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

    // Yuki's Setlists・My Page の会場：同じ会場（前の名前・書き方の違い）は1つにまとめ、会場名は最後に行ったときの名前（VenueAlias::countByVenue）。
    // 何も打っていないときは名前の順に10件、打ったときは、その会場のどれかの名前が打った文字で始まる会場
    private function attendedVenueNames(string $search = '')
    {
        $dateColumn = $this->myPageUserId ? 'attended_date' : 'date';
        $rows = $this->venueQuery()->get(['venue', $dateColumn])->map(fn ($row) => [$row->venue, (string) $row->{$dateColumn}]);
        $namesByVenue = $rows->groupBy(fn ($row) => \App\Models\VenueAlias::resolve(trim($row[0])))->map(fn ($group) => $group->pluck(0)->map(fn ($n) => trim($n))->unique());

        return \App\Models\VenueAlias::countByVenue($rows)
            ->filter(fn ($venue) => $search === '' || ($namesByVenue[\App\Models\VenueAlias::resolve($venue->venue)] ?? collect([$venue->venue]))
                ->contains(fn ($name) => mb_stripos($name, $search) === 0))
            ->pluck('venue')
            ->sort()
            ->values()
            ->take(10);
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
        $this->venues = $this->toVenues($this->attendedVenueNames());
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

        $this->venues = $this->toVenues($this->attendedVenueNames(trim($this->search)));

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

