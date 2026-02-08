@extends('layouts.app')

@section('title', 'Statistics')
@section('og_title', 'Statistics - Yuki Official')
@section('og_description', 'Live attendance statistics and analytics')

@section('content')
<div class="stats-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="element js-fadein">
                    <h1 class="stats-title">Statistics</h1>
                    <p class="stats-subtitle">ライブ参加履歴とデータ分析</p>

                    <!-- Tab Navigation -->
                    <div class="stats-tabs">
                        <a href="{{ route('stats.index', ['tab' => 'personal']) }}"
                           class="stats-tab {{ $tab === 'personal' ? 'active' : '' }}">
                            <i class="fas fa-user"></i> Personal
                        </a>
                        <a href="{{ route('stats.index', ['tab' => 'mrchildren']) }}"
                           class="stats-tab {{ $tab === 'mrchildren' ? 'active' : '' }}">
                            <i class="fas fa-database"></i> Mr.Children
                        </a>
                    </div>

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

                    <!-- Most Listened Songs Section -->
                    <div class="stats-section visible">
                        <div class="section-title-wrapper">
                            <h2 class="section-title">
                                <i class="fas fa-fire"></i> Most Listened Songs
                            </h2>
                            <div class="unique-tour-toggle">
                                <label class="unique-tour-label">
                                    <input type="checkbox" id="uniqueTourCheckbox" class="unique-tour-checkbox">
                                    <span class="unique-tour-text">Count same-named tours only once</span>
                                </label>
                            </div>
                        </div>
                        <div class="stats-table-container">
                            <table class="stats-table" id="songStatsTable">
                                <thead>
                                    <tr>
                                        <th class="rank-col">Rank</th>
                                        <th>Song Title</th>
                                        <th>Artist</th>
                                        <th class="count-col">Times Heard</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($songStats as $index => $song)
                                    <tr>
                                        <td class="rank-col">
                                            @if($index === 0)
                                                <span class="rank-badge gold">🏆</span>
                                            @elseif($index === 1)
                                                <span class="rank-badge silver">🥈</span>
                                            @elseif($index === 2)
                                                <span class="rank-badge bronze">🥉</span>
                                            @else
                                                <span class="rank-number">{{ $index + 1 }}</span>
                                            @endif
                                        </td>
                                        <td class="song-title">{{ $song['title'] }}</td>
                                        <td class="artist-name">{{ $song['artist_name'] }}</td>
                                        <td class="count-col">
                                            <span class="count-badge">{{ $song['count'] }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Artist Statistics Section -->
                    <div class="stats-section visible">
                        <h2 class="section-title">
                            <i class="fas fa-microphone"></i> Artist Statistics
                        </h2>
                        <div class="stats-table-container">
                            <table class="stats-table">
                                <thead>
                                    <tr>
                                        <th class="rank-col">Rank</th>
                                        <th>Artist Name</th>
                                        <th class="count-col">Shows Attended</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($artistStats as $index => $artist)
                                    <tr class="{{ $index >= 10 ? 'hidden-row' : '' }}">
                                        <td class="rank-col">
                                            @if($index === 0)
                                                <span class="rank-badge gold">🏆</span>
                                            @elseif($index === 1)
                                                <span class="rank-badge silver">🥈</span>
                                            @elseif($index === 2)
                                                <span class="rank-badge bronze">🥉</span>
                                            @else
                                                <span class="rank-number">{{ $index + 1 }}</span>
                                            @endif
                                        </td>
                                        <td class="artist-name">{{ $artist['name'] }}</td>
                                        <td class="count-col">
                                            <span class="count-badge">{{ $artist['show_count'] }}</span>
                                        </td>
                                        <td class="detail-link-col">
                                            <a href="{{ route('stats.artist', $artist['id']) }}" class="detail-link">
                                                View Details →
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if(count($artistStats) > 10)
                            <div class="show-more-container">
                                <button class="show-more-btn" onclick="toggleArtistRows(this)">
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
                                    @foreach($venueStats as $index => $venue)
                                    <tr>
                                        <td class="rank-col">
                                            @if($index === 0)
                                                <span class="rank-badge gold">🏆</span>
                                            @elseif($index === 1)
                                                <span class="rank-badge silver">🥈</span>
                                            @elseif($index === 2)
                                                <span class="rank-badge bronze">🥉</span>
                                            @else
                                                <span class="rank-number">{{ $index + 1 }}</span>
                                            @endif
                                        </td>
                                        <td class="venue-name">{{ $venue->venue }}</td>
                                        <td class="count-col">
                                            <span class="count-badge">{{ $venue->count }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
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
                                    @foreach($yearStats as $yearStat)
                                    <tr>
                                        <td class="year-col">{{ $yearStat->year }}</td>
                                        <td class="count-col">
                                            <div class="year-bar-container">
                                                <div class="year-bar" style="width: {{ ($yearStat->count / $yearStats->max('count')) * 100 }}%"></div>
                                                <span class="year-count">{{ $yearStat->count }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Shows by Month Section -->
                    <div class="stats-section visible">
                        <h2 class="section-title">
                            <i class="fas fa-chart-bar"></i> Shows by Month
                        </h2>
                        <div class="stats-table-container">
                            <table class="stats-table has-bar-indicator">
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th class="count-col">Shows Attended</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($monthStats as $monthStat)
                                    <tr>
                                        <td class="year-col">{{ $monthStat->month }}</td>
                                        <td class="count-col">
                                            <div class="year-bar-container">
                                                <div class="year-bar" style="width: {{ $monthStats->max('count') > 0 ? ($monthStat->count / $monthStats->max('count')) * 100 : 0 }}%"></div>
                                                <span class="year-count">{{ $monthStat->count }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<div class="footer-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                <div class="footer">
                    <div class="footer-title">
                        Yuki Yoshida Official Website
                    </div>
                    <a href="{{ url('/#news') }}">News</a>・
                    <a href="{{ url('/#music') }}">Music</a>・
                    <a href="{{ url('/#profile') }}">Profile</a>・
                    <a href="{{ url('/#radio') }}">Radio</a>・
                    <a href="{{ url('/stats') }}">Stats</a>・
                    <a href="https://ameblo.jp/y2-world" target="_blank">Blog</a>・
                    <a href="{{ url('/admin') }}" target="_blank">Admin</a>
                    <br>
                    <div class="footer-copyright">©2024 y2 records inc.</div>
                </div>
            </div>
            <br>
        </div>
    </div>
</div>

<script>
function toggleArtistRows(button) {
    const hiddenRows = document.querySelectorAll('.hidden-row');
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
const songStatsData = @json($songStats);
const songStatsUnique = @json($songStatsUnique);

document.getElementById('uniqueTourCheckbox').addEventListener('change', function(e) {
    const useUnique = e.target.checked;
    const data = useUnique ? songStatsUnique : songStatsData;

    const tbody = document.querySelector('#songStatsTable tbody');
    tbody.innerHTML = '';

    data.forEach((song, index) => {
        const tr = document.createElement('tr');

        let rankBadge = '';
        if (index === 0) {
            rankBadge = '<span class="rank-badge gold">🏆</span>';
        } else if (index === 1) {
            rankBadge = '<span class="rank-badge silver">🥈</span>';
        } else if (index === 2) {
            rankBadge = '<span class="rank-badge bronze">🥉</span>';
        } else {
            rankBadge = '<span class="rank-number">' + (index + 1) + '</span>';
        }

        tr.innerHTML = `
            <td class="rank-col">${rankBadge}</td>
            <td class="song-title">${song.title}</td>
            <td class="artist-name">${song.artist_name}</td>
            <td class="count-col">
                <span class="count-badge">${song.count}</span>
            </td>
        `;

        tbody.appendChild(tr);
    });
});
</script>
@endsection
