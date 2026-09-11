@extends('layouts.app')

@section('title', 'My Page')
@section('og_title', 'My Page - Yuki Official')

@section('content')
<div class="stats-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="element js-fadein">
                    <h1 class="stats-title">My Page</h1>
                    <p class="stats-subtitle">参加したライブの記録</p>

                    <!-- Overall Stats Cards -->
                    <div class="row stats-cards">
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                                <div class="stat-value">{{ $overallStats['total_shows'] }}</div>
                                <div class="stat-label">Total Shows</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-value">{{ $overallStats['total_artists'] }}</div>
                                <div class="stat-label">Total Artists</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-music"></i>
                                </div>
                                <div class="stat-value">{{ $overallStats['total_songs'] }}</div>
                                <div class="stat-label">Total Songs</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                                <div class="stat-value">{{ $overallStats['total_venues'] }}</div>
                                <div class="stat-label">Total Venues</div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendances Section -->
                    <div class="stats-section visible">
                        <div class="section-title-wrapper" style="flex-direction: row !important; flex-wrap: nowrap; align-items: center; min-width: 0;">
                            <h2 class="section-title" style="text-align: left !important; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; flex: 1 1 auto; margin-bottom: 0;">
                                <i class="fas fa-calendar-check"></i> My Live Attendances
                            </h2>
                            <a href="{{ route('mypage.attendances.create') }}" class="mypage-add-button" title="ライブの参加記録を追加" style="flex: 0 0 auto;">
                                <i class="fas fa-plus"></i>
                            </a>
                        </div>
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
                                    @php $attendanceStart = count($attendances); @endphp
                                    @forelse ($attendances as $index => $attendance)
                                        <tr class="{{ $index >= 10 ? 'hidden-row-attendances' : '' }}">
                                            <td>{{ $attendanceStart - $index }}</td>
                                            <td>{{ $attendance->attended_date?->format('Y.m.d') ?? '-' }}</td>
                                            <td class="sp">
                                                @if ($attendance->dbSetlist?->tour?->artist)
                                                    <a href="{{ route('mypage.attendances.index', ['artist_id' => $attendance->dbSetlist->tour->artist_id]) }}" class="stats-link">{{ $attendance->dbSetlist->tour->artist->name }}</a>
                                                @endif
                                                /
                                                <a href="{{ route('mypage.attendances.show', $attendance) }}" class="stats-link">{{ $attendance->dbSetlist->tour->title ?? '-' }}</a>
                                            </td>
                                            <td class="pc td_artist">
                                                @if ($attendance->dbSetlist?->tour?->artist)
                                                    <a href="{{ route('mypage.attendances.index', ['artist_id' => $attendance->dbSetlist->tour->artist_id]) }}" class="stats-link">{{ $attendance->dbSetlist->tour->artist->name }}</a>
                                                @endif
                                            </td>
                                            <td class="pc">
                                                <a href="{{ route('mypage.attendances.show', $attendance) }}" class="stats-link">{{ $attendance->dbSetlist->tour->title ?? '-' }}</a>
                                            </td>
                                            <td class="pc">{{ $attendance->venue }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6">まだ参加したライブが記録されていません。右上の「＋」から追加してください。</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            @if (count($attendances) > 10)
                                <div class="show-more-container">
                                    <button class="show-more-btn" onclick="toggleAttendanceRows(this)">
                                        Show More <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Most Listened Songs Section -->
                    <div class="stats-section visible">
                        <div class="section-title-wrapper" style="justify-content: center;">
                            <h2 class="section-title" style="text-align: center;">
                                <i class="fas fa-fire"></i> Most Listened Songs ({{ count($topSongs) }})
                            </h2>
                        </div>
                        <div class="stats-table-container">
                            <table class="stats-table">
                                <thead>
                                    <tr>
                                        <th class="rank-col">Rank</th>
                                        <th class="title-col">Song Title</th>
                                        <th>Artist</th>
                                        <th class="count-col">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($topSongs as $index => $song)
                                        @php
                                            $showRank = $index === 0 || $topSongs[$index - 1]['count'] !== $song['count'];
                                            $actualRank = $index + 1;
                                        @endphp
                                        <tr class="{{ $index >= 10 ? 'hidden-row-songs' : '' }}">
                                            <td class="rank-col">
                                                @if ($showRank)
                                                    @if ($index === 0)
                                                        <span class="rank-badge gold">🏆</span>
                                                    @elseif ($index === 1)
                                                        <span class="rank-badge silver">🥈</span>
                                                    @elseif ($index === 2)
                                                        <span class="rank-badge bronze">🥉</span>
                                                    @else
                                                        <span class="rank-number">{{ $actualRank }}</span>
                                                    @endif
                                                @endif
                                            </td>
                                            <td class="song-title">
                                                <a href="{{ route('mypage.attendances.index', ['song_id' => $song['song_id']]) }}" class="stats-link">{{ $song['title'] }}</a>
                                            </td>
                                            <td class="artist-name">
                                                @if ($song['artist_id'])
                                                    <a href="{{ route('mypage.stats.artist', $song['artist_id']) }}" class="stats-link">{{ $song['artist_name'] }}</a>
                                                @else
                                                    {{ $song['artist_name'] }}
                                                @endif
                                            </td>
                                            <td class="count-col">
                                                <span class="count-badge">{{ $song['count'] }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4">まだ参加したライブが記録されていません。</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            @if (count($topSongs) > 10)
                                <div class="show-more-container">
                                    <button class="show-more-btn" onclick="toggleSongRows(this)">
                                        Show More <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Artist Statistics Section -->
                    <div class="stats-section visible">
                        <div class="section-title-wrapper">
                            <h2 class="section-title">
                                <i class="fas fa-microphone"></i> Artist Statistics
                            </h2>
                        </div>
                        <div class="stats-table-container">
                            <table class="stats-table">
                                <thead>
                                    <tr>
                                        <th class="rank-col">Rank</th>
                                        <th>Artist Name</th>
                                        <th class="count-col">Shows Attended</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($artistStats as $index => $artist)
                                        @php
                                            $showRank = $index === 0 || $artistStats[$index - 1]['show_count'] !== $artist['show_count'];
                                            $actualRank = $index + 1;
                                        @endphp
                                        <tr class="{{ $index >= 10 ? 'hidden-row-artist' : '' }}">
                                            <td class="rank-col">
                                                @if ($showRank)
                                                    @if ($index === 0)
                                                        <span class="rank-badge gold">🏆</span>
                                                    @elseif ($index === 1)
                                                        <span class="rank-badge silver">🥈</span>
                                                    @elseif ($index === 2)
                                                        <span class="rank-badge bronze">🥉</span>
                                                    @else
                                                        <span class="rank-number">{{ $actualRank }}</span>
                                                    @endif
                                                @endif
                                            </td>
                                            <td class="artist-name">
                                                <a href="{{ route('mypage.stats.artist', $artist['id']) }}" class="stats-link">{{ $artist['name'] }}</a>
                                            </td>
                                            <td class="count-col">
                                                <span class="count-badge">{{ $artist['show_count'] }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3">まだ参加したライブが記録されていません。</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            @if (count($artistStats) > 10)
                                <div class="show-more-container">
                                    <button class="show-more-btn" onclick="toggleArtistStatsRows(this)">
                                        Show More <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Top Venues Section -->
                    <div class="stats-section visible">
                        <h2 class="section-title">
                            <i class="fas fa-map-marker-alt"></i> Top Venues
                        </h2>
                        <div class="stats-table-container">
                            <table class="stats-table">
                                <thead>
                                    <tr>
                                        <th class="rank-col">Rank</th>
                                        <th>Venue</th>
                                        <th class="count-col">Visits</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($venueStats as $index => $venue)
                                        @php
                                            $showRank = $index === 0 || $venueStats[$index - 1]->count !== $venue->count;
                                            $actualRank = $index + 1;
                                        @endphp
                                        <tr>
                                            <td class="rank-col">
                                                @if ($showRank)
                                                    @if ($index === 0)
                                                        <span class="rank-badge gold">🏆</span>
                                                    @elseif ($index === 1)
                                                        <span class="rank-badge silver">🥈</span>
                                                    @elseif ($index === 2)
                                                        <span class="rank-badge bronze">🥉</span>
                                                    @else
                                                        <span class="rank-number">{{ $actualRank }}</span>
                                                    @endif
                                                @endif
                                            </td>
                                            <td class="venue-name">{{ $venue->venue }}</td>
                                            <td class="count-col">
                                                <span class="count-badge">{{ $venue->count }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3">まだ参加したライブが記録されていません。</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Shows by Year Section -->
                    <div class="stats-section visible">
                        <h2 class="section-title">
                            <i class="fas fa-calendar-alt"></i> Shows by Year
                        </h2>
                        <div class="stats-table-container">
                            <table class="stats-table has-bar-indicator">
                                <thead>
                                    <tr>
                                        <th>Year</th>
                                        <th class="count-col">Shows Attended</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($yearStats as $yearStat)
                                        <tr>
                                            <td class="year-col"><a href="{{ route('mypage.attendances.index', ['year' => $yearStat->year]) }}" class="stats-link">{{ $yearStat->year }}</a></td>
                                            <td class="count-col">
                                                <div class="year-bar-container">
                                                    <div class="year-bar-wrapper">
                                                        <div class="year-bar" style="width: {{ ($yearStat->count / $yearStats->max('count')) * 100 }}%"></div>
                                                    </div>
                                                    <span class="year-count">{{ $yearStat->count }}</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2">まだ参加したライブが記録されていません。</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($artists->isNotEmpty())
                        <div class="stats-section visible">
                            <div class="section-title-wrapper">
                                <h2 class="section-title">
                                    <i class="fas fa-stamp"></i> Live Stamp Book
                                </h2>
                            </div>
                            <div class="stamp-book-link-wrapper" style="display: flex; gap: 12px; flex-wrap: wrap; justify-content: flex-start;">
                                @foreach ($artists as $artist)
                                    <a href="{{ route('mypage.stats.stamps', $artist->id) }}" class="stamp-book-link">
                                        <i class="fas fa-stamp"></i> {{ $artist->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div style="text-align: center; margin-top: 40px;">
                        <form method="POST" action="{{ route('mypage.logout') }}">
                            @csrf
                            <button type="submit" class="stamp-book-link" style="border: none; cursor: pointer;">ログアウト</button>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAttendanceRows(button) {
    const hiddenRows = document.querySelectorAll('.hidden-row-attendances');
    const isExpanded = button.classList.contains('expanded');

    hiddenRows.forEach(row => {
        row.style.display = isExpanded ? 'none' : 'table-row';
    });

    button.classList.toggle('expanded');
    button.innerHTML = isExpanded
        ? 'Show More <i class="fas fa-chevron-down"></i>'
        : 'Show Less <i class="fas fa-chevron-up"></i>';
}

function toggleSongRows(button) {
    const hiddenRows = document.querySelectorAll('.hidden-row-songs');
    const isExpanded = button.classList.contains('expanded');

    hiddenRows.forEach(row => {
        row.style.display = isExpanded ? 'none' : 'table-row';
    });

    button.classList.toggle('expanded');
    button.innerHTML = isExpanded
        ? 'Show More <i class="fas fa-chevron-down"></i>'
        : 'Show Less <i class="fas fa-chevron-up"></i>';
}

function toggleArtistStatsRows(button) {
    const hiddenRows = document.querySelectorAll('.hidden-row-artist');
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
