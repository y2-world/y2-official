@extends('layouts.app')

@section('title', ($user->name ?: 'ゲスト') . ' - Profile')
@section('og_title', ($user->name ?: 'ゲスト') . ' - Yuki Official')

@section('content')
<div class="stats-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-8">
                <div class="element js-fadein">
                    <div style="text-align: left; margin-bottom: 16px;">
                        <a href="{{ url()->previous() }}" class="back-link" style="color: rgba(255, 255, 255, 0.9); font-size: 0.9rem;">
                            <i class="fa-solid fa-arrow-left"></i> Back
                        </a>
                    </div>

                    <div class="profile-header">
                        @if ($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="" class="profile-avatar">
                        @else
                            <div class="profile-avatar profile-avatar--placeholder">
                                <i class="fa-solid fa-user"></i>
                            </div>
                        @endif
                        <h1 class="stats-title" style="margin: 12px 0 0; font-size: 1.8rem;">{{ $user->name ?: 'ゲスト' }}</h1>
                        @if ($user->bio)
                            <p class="profile-bio">{{ $user->bio }}</p>
                        @endif
                    </div>

                    <div class="row stats-cards" style="margin-top: 30px;">
                        <div class="col-4">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                                <div class="stat-value">{{ $overallStats['total_shows'] }}</div>
                                <div class="stat-label">Shows</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-value">{{ $overallStats['total_artists'] }}</div>
                                <div class="stat-label">Artists</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="stat-value">{{ $overallStats['total_venues'] }}</div>
                                <div class="stat-label">Venues</div>
                            </div>
                        </div>
                    </div>

                    @if ($recentShows->isNotEmpty())
                        <div class="stats-section visible" style="margin-top: 20px;">
                            <h2 class="section-title" style="font-size: 1.1rem;">
                                <i class="fas fa-calendar-check"></i> My Live Attendances
                            </h2>
                            <div class="database-year-content" style="padding: 0;">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th class="mobile">#</th>
                                            <th class="mobile">開催日</th>
                                            <th class="sp">アーティスト / タイトル</th>
                                            <th class="pc td_artist">アーティスト</th>
                                            <th class="pc">タイトル</th>
                                            <th class="pc">会場</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $recentShowsStart = $recentShows->count(); @endphp
                                        @foreach ($recentShows as $index => $attendance)
                                            @php
                                                $tour = $attendance->attendedTour;
                                                $isFes = in_array((int) ($tour?->type ?? 0), [2, 3, 4], true);
                                                $artistRef = $attendance->db_setlist_id
                                                    ? 'official-' . $tour?->artist_id
                                                    : 'user-' . $tour?->user_artist_id;
                                            @endphp
                                            <tr class="{{ $index >= 3 ? 'hidden-row-recent-shows' : '' }}">
                                                <td>{{ $recentShowsStart - $index }}</td>
                                                <td>{{ $attendance->attended_date?->format('Y.m.d') ?? '-' }}</td>
                                                <td class="sp">
                                                    @if ($tour?->artist && !$isFes)
                                                        <a href="{{ route('mypage.attendances.index', ['user_id' => $user->id, 'artist_id' => $artistRef]) }}" class="stats-link">{{ $tour->artist->name }}</a>
                                                        /
                                                    @endif
                                                    <a href="{{ route('mypage.attendances.show', ['attendance' => $attendance, 'from' => 'stats']) }}" class="stats-link">{{ $tour->title ?? '-' }}</a>
                                                </td>
                                                <td class="pc td_artist">
                                                    @if ($tour?->artist && !$isFes)
                                                        <a href="{{ route('mypage.attendances.index', ['user_id' => $user->id, 'artist_id' => $artistRef]) }}" class="stats-link">{{ $tour->artist->name }}</a>
                                                    @endif
                                                </td>
                                                <td class="pc">
                                                    <a href="{{ route('mypage.attendances.show', ['attendance' => $attendance, 'from' => 'stats']) }}" class="stats-link">{{ $tour->title ?? '-' }}</a>
                                                </td>
                                                <td class="pc">{{ $attendance->venue }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if ($recentShows->count() > 3)
                                    <div class="show-more-container">
                                        <button class="show-more-btn" onclick="toggleRecentShowRows(this)">
                                            Show More <i class="fas fa-chevron-down"></i>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if ($topArtists->isNotEmpty())
                        <div class="stats-section visible" style="margin-top: 20px;">
                            <h2 class="section-title" style="font-size: 1.1rem;">
                                <i class="fas fa-microphone"></i> Top Artists
                            </h2>
                            <div class="stats-table-container">
                                <table class="stats-table">
                                    <tbody>
                                        @foreach ($topArtists as $artist)
                                            <tr>
                                                <td class="rank-col">
                                                    @if ($loop->iteration === 1)
                                                        <span class="rank-badge gold">🏆</span>
                                                    @elseif ($loop->iteration === 2)
                                                        <span class="rank-badge silver">🥈</span>
                                                    @else
                                                        <span class="rank-badge bronze">🥉</span>
                                                    @endif
                                                </td>
                                                <td class="artist-name">{{ $artist['name'] }}</td>
                                                <td class="count-col"><span class="count-badge">{{ $artist['show_count'] }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if ($topSongs->isNotEmpty())
                        <div class="stats-section visible" style="margin-top: 20px;">
                            <h2 class="section-title" style="font-size: 1.1rem;">
                                <i class="fas fa-fire"></i> Top Songs
                            </h2>
                            <div class="stats-table-container">
                                <table class="stats-table">
                                    <tbody>
                                        @foreach ($topSongs as $song)
                                            <tr>
                                                <td class="rank-col">
                                                    @if ($loop->iteration === 1)
                                                        <span class="rank-badge gold">🏆</span>
                                                    @elseif ($loop->iteration === 2)
                                                        <span class="rank-badge silver">🥈</span>
                                                    @else
                                                        <span class="rank-badge bronze">🥉</span>
                                                    @endif
                                                </td>
                                                <td class="song-title">{{ $song['title'] }}</td>
                                                <td class="count-col"><span class="count-badge">{{ $song['count'] }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if ($topVenues->isNotEmpty())
                        <div class="stats-section visible" style="margin-top: 20px;">
                            <h2 class="section-title" style="font-size: 1.1rem;">
                                <i class="fas fa-map-marker-alt"></i> Top Venues
                            </h2>
                            <div class="stats-table-container">
                                <table class="stats-table">
                                    <tbody>
                                        @foreach ($topVenues as $venue)
                                            <tr>
                                                <td class="rank-col">
                                                    @if ($loop->iteration === 1)
                                                        <span class="rank-badge gold">🏆</span>
                                                    @elseif ($loop->iteration === 2)
                                                        <span class="rank-badge silver">🥈</span>
                                                    @else
                                                        <span class="rank-badge bronze">🥉</span>
                                                    @endif
                                                </td>
                                                <td class="song-title">{{ $venue['venue'] }}</td>
                                                <td class="count-col"><span class="count-badge">{{ $venue['count'] }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if ($stampBooks->isNotEmpty())
                        <div class="stats-section visible" style="margin-top: 20px;">
                            <h2 class="section-title" style="font-size: 1.1rem;">
                                <i class="fas fa-stamp"></i> My Stamps
                            </h2>
                            <div class="stats-table-container">
                                <table class="stats-table">
                                    <tbody>
                                        @foreach ($stampBooks as $stampBook)
                                            <tr>
                                                <td class="rank-col">
                                                    @if ($loop->iteration === 1)
                                                        <span class="rank-badge gold">🏆</span>
                                                    @elseif ($loop->iteration === 2)
                                                        <span class="rank-badge silver">🥈</span>
                                                    @else
                                                        <span class="rank-badge bronze">🥉</span>
                                                    @endif
                                                </td>
                                                <td class="artist-name">
                                                    <a href="{{ route('mypage.stats.stamps', [$stampBook['artist_ref'], 'user' => $user->id]) }}" class="stats-link">{{ $stampBook['name'] }}</a>
                                                </td>
                                                <td class="count-col"><span class="count-badge">{{ $stampBook['percentage'] }}%</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-header {
    text-align: center;
}
.profile-avatar {
    display: block;
    width: 96px;
    height: 96px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid rgba(255, 255, 255, 0.4);
    margin: 0 auto;
}
.profile-avatar--placeholder {
    width: 96px;
    height: 96px;
    border-radius: 50%;
    border: 3px solid rgba(255, 255, 255, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    background: rgba(255, 255, 255, 0.15);
    color: rgba(255, 255, 255, 0.7);
    font-size: 2.2rem;
}
.profile-bio {
    color: rgba(255, 255, 255, 0.85);
    font-size: 0.9rem;
    margin-top: 10px;
    max-width: 480px;
    margin-left: auto;
    margin-right: auto;
    white-space: pre-wrap;
}
</style>

<script>
function toggleRecentShowRows(button) {
    const hiddenRows = document.querySelectorAll('.hidden-row-recent-shows');
    const isExpanded = button.classList.contains('expanded');

    hiddenRows.forEach(row => {
        row.style.display = isExpanded ? 'none' : 'table-row';
    });

    button.classList.toggle('expanded');
    button.innerHTML = isExpanded
        ? 'Show More <i class="fas fa-chevron-down"></i>'
        : 'Show Less <i class="fas fa-chevron-up"></i>';
}
</script>
@endsection
