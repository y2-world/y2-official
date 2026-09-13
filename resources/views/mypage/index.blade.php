@extends('layouts.app')

@section('title', 'My Statistics')
@section('og_title', 'My Statistics - Yuki Official')

@section('content')
<div class="stats-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="element js-fadein">
                    <div style="text-align: left; margin-bottom: 16px;">
                        <a href="{{ route('mypage.index') }}" style="color: rgba(255, 255, 255, 0.9); font-size: 0.9rem;">
                            <i class="fa-solid fa-arrow-left"></i> Back to My Page
                        </a>
                    </div>
                    <div style="text-align: center;">
                        <h1 class="stats-title" style="margin-bottom: 0;">My Statistics</h1>
                    </div>
                    <p class="stats-subtitle" style="margin-top: 10px;">参加したライブの記録</p>

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
                            <a href="{{ route('mypage.attendances.create') }}" class="mypage-add-button" title="セットリストを追加" style="flex: 0 0 auto;">
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
                                        @php
                                            $tour = $attendance->attendedTour;
                                            $isFes = in_array((int)($tour?->type ?? 0), [2, 3, 4], true);
                                            $artistRef = $attendance->db_setlist_id
                                                ? 'official-' . $tour?->artist_id
                                                : 'user-' . $tour?->user_artist_id;
                                        @endphp
                                        <tr class="{{ $index >= 10 ? 'hidden-row-attendances' : '' }}">
                                            <td>{{ $attendanceStart - $index }}</td>
                                            <td>{{ $attendance->attended_date?->format('Y.m.d') ?? '-' }}</td>
                                            <td class="sp">
                                                @if ($tour?->artist && !$isFes)
                                                    <a href="{{ route('mypage.attendances.index', ['artist_id' => $artistRef]) }}" class="stats-link">{{ $tour->artist->name }}</a>
                                                    /
                                                @endif
                                                <a href="{{ route('mypage.attendances.show', $attendance) }}" class="stats-link">{{ $tour->title ?? '-' }}</a>
                                            </td>
                                            <td class="pc td_artist">
                                                @if ($tour?->artist && !$isFes)
                                                    <a href="{{ route('mypage.attendances.index', ['artist_id' => $artistRef]) }}" class="stats-link">{{ $tour->artist->name }}</a>
                                                @endif
                                            </td>
                                            <td class="pc">
                                                <a href="{{ route('mypage.attendances.show', $attendance) }}" class="stats-link">{{ $tour->title ?? '-' }}</a>
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
                        <div class="section-title-wrapper" style="flex-direction: column; align-items: center; gap: 10px;">
                            <h2 class="section-title" style="text-align: center;">
                                <i class="fas fa-fire"></i> Most Listened Songs (<span id="mypageSongCountLabel">{{ count($topSongs) }}</span>)
                            </h2>
                            <div class="unique-tour-toggle" style="margin-left: 0;">
                                <label class="unique-tour-label">
                                    <input type="checkbox" id="mypageUniqueTourCheckbox" class="unique-tour-checkbox">
                                    <span class="unique-tour-text">Count same-named tours only once</span>
                                </label>
                            </div>
                        </div>
                        <div class="stats-table-container">
                            <table class="stats-table" id="mypageSongStatsTable">
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
                                                    <a href="{{ route('mypage.attendances.index', ['artist_id' => $song['artist_id']]) }}" class="stats-link">{{ $song['artist_name'] }}</a>
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
                            <div class="show-more-container" id="mypageSongShowMoreContainer" style="{{ count($topSongs) > 10 ? '' : 'display: none;' }}">
                                <button class="show-more-btn" onclick="toggleSongRows(this)">
                                    Show More <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
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

                    @if ($artistSongStats->isNotEmpty())
                        <!-- Unique Songs by Artist Section -->
                        <div class="stats-section visible">
                            <h2 class="section-title">
                                <i class="fas fa-music"></i> Unique Songs by Artist
                            </h2>
                            <div class="stats-table-container">
                                <table class="stats-table has-ratio-count">
                                    <thead>
                                        <tr>
                                            <th class="rank-col">Rank</th>
                                            <th>Artist Name</th>
                                            <th class="count-col">Unique Songs</th>
                                            <th class="percentage-col">%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($artistSongStats as $index => $artistStat)
                                        @php
                                            $showRank = $index === 0 || $artistSongStats[$index - 1]['percentage'] !== $artistStat['percentage'];
                                            $actualRank = $index + 1;
                                        @endphp
                                        <tr class="{{ $index >= 10 ? 'hidden-row-artist-song-stats' : '' }}">
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
                                                <a href="{{ route('mypage.stats.artist', $artistStat['id']) }}" class="stats-link">{{ $artistStat['name'] }}</a>
                                            </td>
                                            <td class="count-col">{{ $artistStat['unique_songs'] }} / {{ $artistStat['total_songs'] }}</td>
                                            <td class="percentage-col">
                                                <span class="count-badge">{{ $artistStat['percentage'] }}%</span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                @if (count($artistSongStats) > 10)
                                <div class="show-more-container">
                                    <button class="show-more-btn" onclick="toggleArtistSongStatsRows(this)">
                                        Show More <i class="fas fa-chevron-down"></i>
                                    </button>
                                </div>
                                @endif
                            </div>
                        </div>
                    @endif

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

function toggleArtistSongStatsRows(button) {
    const hiddenRows = document.querySelectorAll('.hidden-row-artist-song-stats');
    const isExpanded = button.classList.contains('expanded');

    hiddenRows.forEach(row => {
        row.style.display = isExpanded ? 'none' : 'table-row';
    });

    button.classList.toggle('expanded');
    button.innerHTML = isExpanded
        ? 'Show More <i class="fas fa-chevron-down"></i>'
        : 'Show Less <i class="fas fa-chevron-up"></i>';
}

// Most Listened Songs - Unique Tour Toggle
const mypageSongStatsData = @json($topSongs);
const mypageSongStatsUnique = @json($topSongsUnique);

document.getElementById('mypageUniqueTourCheckbox').addEventListener('change', function(e) {
    const useUnique = e.target.checked;
    const data = useUnique ? mypageSongStatsUnique : mypageSongStatsData;

    document.getElementById('mypageSongCountLabel').textContent = data.length;

    const showMoreBtn = document.querySelector('#mypageSongShowMoreContainer .show-more-btn');
    if (showMoreBtn) {
        showMoreBtn.classList.remove('expanded');
        showMoreBtn.innerHTML = 'Show More <i class="fas fa-chevron-down"></i>';
    }

    const showMoreContainer = document.getElementById('mypageSongShowMoreContainer');
    showMoreContainer.style.display = data.length > 10 ? '' : 'none';

    const tbody = document.querySelector('#mypageSongStatsTable tbody');
    tbody.innerHTML = '';

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4">まだ参加したライブが記録されていません。</td></tr>';
        return;
    }

    data.forEach((song, index) => {
        const tr = document.createElement('tr');

        if (index >= 10) {
            tr.classList.add('hidden-row-songs');
        }

        const showRank = index === 0 || data[index - 1].count !== song.count;
        const actualRank = index + 1;

        let rankBadge = '';
        if (showRank) {
            if (index === 0) {
                rankBadge = '<span class="rank-badge gold">🏆</span>';
            } else if (index === 1) {
                rankBadge = '<span class="rank-badge silver">🥈</span>';
            } else if (index === 2) {
                rankBadge = '<span class="rank-badge bronze">🥉</span>';
            } else {
                rankBadge = '<span class="rank-number">' + actualRank + '</span>';
            }
        }

        const artistCell = song.artist_id
            ? '<a href="/mypage/attendances?artist_id=' + song.artist_id + '" class="stats-link">' + song.artist_name + '</a>'
            : song.artist_name;

        tr.innerHTML = `
            <td class="rank-col">${rankBadge}</td>
            <td class="song-title">
                <a href="/mypage/attendances?song_id=${song.song_id}" class="stats-link">${song.title}</a>
            </td>
            <td class="artist-name">${artistCell}</td>
            <td class="count-col">
                <span class="count-badge">${song.count}</span>
            </td>
        `;

        tbody.appendChild(tr);
    });
});
</script>
@endsection
