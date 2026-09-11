<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\DbSetlist;
use App\Models\DbSong;
use Illuminate\Support\Facades\Auth;

class MyPageController extends Controller
{
    public function index()
    {
        $user = Auth::guard('external')->user();
        $attendances = $user->attendances()
            ->with('dbSetlist.tour.artist')
            ->orderByDesc('attended_date')
            ->get();

        $totalShows = $attendances->count();
        $totalArtists = $attendances->pluck('dbSetlist.tour.artist_id')->filter()->unique()->count();
        $totalVenues = $attendances->pluck('venue')->filter()->unique()->count();

        $attendedSetlistIds = $attendances->pluck('db_setlist_id')->unique();
        $setlists = DbSetlist::whereIn('id', $attendedSetlistIds)->get();

        $songPlayCounts = [];
        foreach ($setlists as $setlist) {
            foreach (array_merge($setlist->setlist ?? [], $setlist->encore ?? []) as $s) {
                if (isset($s['song']) && is_numeric($s['song'])) {
                    $songId = (int)$s['song'];
                    $songPlayCounts[$songId] = ($songPlayCounts[$songId] ?? 0) + 1;
                }
            }
        }
        arsort($songPlayCounts);

        $songs = DbSong::whereIn('id', array_keys($songPlayCounts))->get()->keyBy('id');
        $topSongs = [];
        foreach ($songPlayCounts as $songId => $count) {
            $song = $songs->get($songId);
            if ($song) {
                $artist = $song->artist;
                $topSongs[] = [
                    'song_id' => $songId,
                    'title' => $song->title,
                    'artist_id' => $artist?->id,
                    'artist_name' => $artist ? $artist->name : '不明',
                    'count' => $count,
                ];
            }
        }

        $overallStats = [
            'total_shows' => $totalShows,
            'total_artists' => $totalArtists,
            'total_songs' => count($songPlayCounts),
            'total_venues' => $totalVenues,
        ];

        $artists = Artist::whereIn('id', $setlists->pluck('tour.artist_id')->filter()->unique())->get();

        $artistStats = $attendances
            ->filter(fn ($a) => $a->dbSetlist?->tour?->artist)
            ->groupBy(fn ($a) => $a->dbSetlist->tour->artist_id)
            ->map(function ($group) {
                $artist = $group->first()->dbSetlist->tour->artist;
                return [
                    'id' => $artist->id,
                    'name' => $artist->name,
                    'show_count' => $group->count(),
                ];
            })
            ->sortByDesc('show_count')
            ->values();

        $venueStats = $attendances
            ->filter(fn ($a) => $a->venue)
            ->groupBy('venue')
            ->map(fn ($group, $venue) => (object) ['venue' => $venue, 'count' => $group->count()])
            ->sortByDesc('count')
            ->take(10)
            ->values();

        $yearStats = $attendances
            ->filter(fn ($a) => $a->attended_date)
            ->groupBy(fn ($a) => $a->attended_date->format('Y'))
            ->map(fn ($group, $year) => (object) ['year' => $year, 'count' => $group->count()])
            ->sortByDesc('count')
            ->take(10)
            ->values();

        return view('mypage.index', compact('attendances', 'overallStats', 'topSongs', 'artists', 'artistStats', 'venueStats', 'yearStats'));
    }
}
