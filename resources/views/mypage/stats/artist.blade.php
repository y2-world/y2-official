@extends('layouts.app')

@section('title', $artist->name . ' - My Statistics')
@section('og_title', $artist->name . ' Statistics - Yuki Official')

@section('content')
<div class="stats-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="element js-fadein">
                    <div class="breadcrumb-nav">
                        <a href="{{ route('mypage.stats') }}">← Back to My Page</a>
                    </div>

                    <h1 class="stats-title">{{ $artist->name }}</h1>
                    <p class="stats-subtitle">My Artist Statistics</p>

                    <div class="stamp-book-link-wrapper">
                        <a href="{{ $stampsRoute }}" class="stamp-book-link">
                            <i class="fas fa-stamp"></i> View Live Stamp Book
                        </a>
                    </div>

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
                        <div class="section-title-wrapper" style="flex-direction: column; align-items: center; gap: 10px;">
                            <h2 class="section-title" style="text-align: center;">
                                <i class="fas fa-fire"></i> Most Listened Songs (<span id="mypageArtistSongCountLabel">{{ count($allSongs) }}</span>)
                            </h2>
                            <div class="unique-tour-toggle" style="margin-left: 0;">
                                <label class="unique-tour-label">
                                    <input type="checkbox" id="uniqueTourCheckboxMypageArtist" class="unique-tour-checkbox">
                                    <span class="unique-tour-text">Count same-named tours only once</span>
                                </label>
                            </div>
                        </div>
                        <div class="stats-table-container">
                            <table class="stats-table" id="mypageArtistSongStatsTable">
                                <thead>
                                    <tr>
                                        <th class="rank-col">Rank</th>
                                        <th>Song Title</th>
                                        <th class="count-col">Times Listened</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($allSongs as $index => $song)
                                    @php
                                        $showRank = $index === 0 || $allSongs[$index - 1]['count'] !== $song['count'];
                                        $actualRank = $index + 1;
                                    @endphp
                                    <tr class="{{ $index >= 10 ? 'hidden-row-top-songs' : '' }}">
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
                                        <td class="count-col">
                                            <span class="count-badge">{{ $song['count'] }}</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3">まだ参加したライブが記録されていません。</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="show-more-container" id="mypageArtistShowMoreContainer" style="{{ count($allSongs) > 10 ? '' : 'display: none;' }}">
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
                                    @forelse ($yearStats as $yearData)
                                    <tr>
                                        <td class="year-col"><a href="{{ route('mypage.attendances.index', ['artist_id' => $artistRef, 'year' => $yearData->year]) }}" class="stats-link">{{ $yearData->year }}</a></td>
                                        <td class="count-col">
                                            <div class="year-bar-container">
                                                <div class="year-bar-wrapper">
                                                    <div class="year-bar" style="width: {{ ($yearData->count / $yearStats->max('count')) * 100 }}%"></div>
                                                </div>
                                                <span class="year-count">{{ $yearData->count }}</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="2">データがありません。</td>
                                    </tr>
                                    @endforelse
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
                                        <td class="song-title">{{ $venue->venue }}</td>
                                        <td class="count-col">
                                            <span class="count-badge">{{ $venue->count }}</span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3">データがありません。</td>
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
const mypageArtistAllSongsData = @json($allSongs);
const mypageArtistAllSongsUnique = @json($allSongsUnique);

document.getElementById('uniqueTourCheckboxMypageArtist').addEventListener('change', function(e) {
    const useUnique = e.target.checked;
    const data = useUnique ? mypageArtistAllSongsUnique : mypageArtistAllSongsData;

    document.getElementById('mypageArtistSongCountLabel').textContent = data.length;

    const showMoreBtn = document.querySelector('#mypageArtistShowMoreContainer .show-more-btn');
    if (showMoreBtn) {
        showMoreBtn.classList.remove('expanded');
        showMoreBtn.innerHTML = 'Show More <i class="fas fa-chevron-down"></i>';
    }

    const showMoreContainer = document.getElementById('mypageArtistShowMoreContainer');
    showMoreContainer.style.display = data.length > 10 ? '' : 'none';

    const tbody = document.querySelector('#mypageArtistSongStatsTable tbody');
    tbody.innerHTML = '';

    if (data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3">まだ参加したライブが記録されていません。</td></tr>';
        return;
    }

    data.forEach((song, index) => {
        const tr = document.createElement('tr');

        if (index >= 10) {
            tr.classList.add('hidden-row-top-songs');
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

        tr.innerHTML = `
            <td class="rank-col">${rankBadge}</td>
            <td class="song-title">
                <a href="{{ url('/mypage/attendances') }}?song_id=${song.song_id}" class="stats-link">${song.title}</a>
            </td>
            <td class="count-col">
                <span class="count-badge">${song.count}</span>
            </td>
        `;

        tbody.appendChild(tr);
    });
});
</script>
@endsection
