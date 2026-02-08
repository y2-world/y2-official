@extends('layouts.app')

@section('title', $artist->name . ' - Statistics')
@section('og_title', $artist->name . ' Statistics - Yuki Official')

@section('content')
<div class="stats-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="element js-fadein">
                    <div class="breadcrumb-nav">
                        <a href="{{ route('stats.index') }}">← Back to Dashboard</a>
                    </div>

                    <h1 class="stats-title">{{ $artist->name }}</h1>
                    <p class="stats-subtitle">Artist Statistics</p>

                    <!-- Overall Stats Cards -->
                    <div class="row stats-cards">
                        <div class="col-md-6 col-sm-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                                <div class="stat-value">{{ $totalShows }}</div>
                                <div class="stat-label">Total Shows</div>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-music"></i>
                                </div>
                                <div class="stat-value">{{ $totalSongs }}</div>
                                <div class="stat-label">Unique Songs</div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Songs for Artist -->
                    <div class="stats-section visible">
                        <div class="section-title-wrapper">
                            <h2 class="section-title">
                                <i class="fas fa-fire"></i> Most Listened Songs (<span id="songCountLabel">{{ count($allSongs) }}</span>)
                            </h2>
                            <div class="unique-tour-toggle">
                                <label class="unique-tour-label">
                                    <input type="checkbox" id="uniqueTourCheckboxArtist" class="unique-tour-checkbox">
                                    <span class="unique-tour-text">Count same-named tours only once</span>
                                </label>
                            </div>
                        </div>
                        <div class="stats-table-container">
                            <table class="stats-table" id="artistSongStatsTable">
                                <thead>
                                    <tr>
                                        <th class="rank-col">Rank</th>
                                        <th>Song Title</th>
                                        <th class="count-col">Times Listened</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($allSongs as $index => $song)
                                    <tr class="{{ $index >= 10 ? 'hidden-row-top-songs' : '' }}">
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
                                        <td class="count-col">
                                            <span class="count-badge">{{ $song['count'] }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <div class="show-more-container" id="showMoreContainer" style="{{ count($allSongs) > 10 ? '' : 'display: none;' }}">
                                <button class="show-more-btn" onclick="toggleTopSongRows(this)">
                                    Show More <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
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
                                        <th class="count-col">Show Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($yearStats as $yearData)
                                    <tr>
                                        <td class="year-col">{{ $yearData->year }}</td>
                                        <td class="count-col">
                                            <div class="year-bar-container">
                                                <div class="year-bar" style="width: {{ ($yearData->count / $yearStats->max('count')) * 100 }}%"></div>
                                                <span class="year-count">{{ $yearData->count }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
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
                                        <td class="song-title">{{ $venue->venue }}</td>
                                        <td class="count-col">
                                            <span class="count-badge">{{ $venue->count }}</span>
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
function toggleTopSongRows(button) {
    const hiddenRows = document.querySelectorAll('.hidden-row-top-songs');
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
const allSongsData = @json($allSongs);
const allSongsUnique = @json($allSongsUnique);

document.getElementById('uniqueTourCheckboxArtist').addEventListener('change', function(e) {
    const useUnique = e.target.checked;
    const data = useUnique ? allSongsUnique : allSongsData;

    // Update song count label
    document.getElementById('songCountLabel').textContent = data.length;

    // Reset show more button state
    const showMoreBtn = document.querySelector('.show-more-btn');
    if (showMoreBtn) {
        showMoreBtn.classList.remove('expanded');
        showMoreBtn.innerHTML = 'Show More <i class="fas fa-chevron-down"></i>';
    }

    // Update show more container visibility
    const showMoreContainer = document.getElementById('showMoreContainer');
    if (data.length > 10) {
        showMoreContainer.style.display = '';
    } else {
        showMoreContainer.style.display = 'none';
    }

    const tbody = document.querySelector('#artistSongStatsTable tbody');
    tbody.innerHTML = '';

    data.forEach((song, index) => {
        const tr = document.createElement('tr');

        // Hide rows after 10
        if (index >= 10) {
            tr.classList.add('hidden-row-top-songs');
        }

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
            <td class="count-col">
                <span class="count-badge">${song.count}</span>
            </td>
        `;

        tbody.appendChild(tr);
    });
});
</script>
@endsection
