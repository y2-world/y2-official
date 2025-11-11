<?php

namespace App\Livewire;

use Livewire\Component;
use App\Setlist;

class VenueSearch extends Component
{
    public $search = '';
    public $venues = [];
    public $showDropdown = false;
    public $selectedIndex = -1;

    public function mount()
    {
        $this->loadInitialVenues();
    }

    public function loadInitialVenues()
    {
        $this->venues = Setlist::query()
            ->whereNotNull('venue')
            ->where('venue', '!=', '')
            ->distinct()
            ->orderBy('venue')
            ->limit(10)
            ->pluck('venue')
            ->map(function ($venue) {
                return [
                    'name' => $venue,
                ];
            })
            ->toArray();
    }

    public function updatedSearch()
    {
        if ($this->search === '') {
            $this->loadInitialVenues();
            return;
        }

        $escapedQuery = str_replace(['%', '_'], ['\%', '\_'], $this->search);

        $this->venues = Setlist::query()
            ->whereNotNull('venue')
            ->where('venue', '!=', '')
            ->whereRaw('LOWER(venue) LIKE LOWER(?)', [$escapedQuery . '%'])
            ->distinct()
            ->orderBy('venue')
            ->limit(10)
            ->pluck('venue')
            ->map(function ($venue) {
                return [
                    'name' => $venue,
                ];
            })
            ->toArray();

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

