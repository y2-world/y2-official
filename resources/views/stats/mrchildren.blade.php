@extends('layouts.app')

@section('title', 'Mr.Children Statistics')
@section('og_title', 'Mr.Children Statistics - Yuki Official')
@section('og_description', 'Mr.Children setlist statistics and analytics')

@section('content')
<div class="stats-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="element js-fadein">
                    <h1 class="stats-title">Statistics</h1>
                    <p class="stats-subtitle">Mr.Children セットリスト統計</p>

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
                                    <i class="fas fa-guitar"></i>
                                </div>
                                <div class="stat-value">{{ $overallStats['total_tours'] }}</div>
                                <div class="stat-label">Total Tours</div>
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
                                    <i class="fas fa-star"></i>
                                </div>
                                <div class="stat-value">{{ $overallStats['unique_songs_in_tours'] }}</div>
                                <div class="stat-label">Songs in Tours</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="stat-value">{{ $overallStats['avg_setlist_length'] }}</div>
                                <div class="stat-label">Avg Songs/Show</div>
                            </div>
                        </div>
                    </div>

                    <!-- Most Performed Songs Section -->
                    <div class="stats-section visible">
                        <h2 class="section-title">
                            <i class="fas fa-fire"></i> Most Performed Songs in Tours ({{ count($songStats) }})
                        </h2>
                        <div class="stats-table-container">
                            <table class="stats-table">
                                <thead>
                                    <tr>
                                        <th class="rank-col">Rank</th>
                                        <th>Song Title</th>
                                        <th class="count-col">Times Performed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($songStats as $index => $song)
                                    @php
                                        $showRank = $index === 0 || $songStats[$index - 1]['count'] !== $song['count'];
                                        $actualRank = $index + 1;
                                    @endphp
                                    <tr class="{{ $index >= 10 ? 'hidden-row' : '' }}">
                                        <td class="rank-col">
                                            @if($showRank)
                                                @if($index === 0)
                                                    <span class="rank-badge gold">🏆</span>
                                                @elseif($index === 1)
                                                    <span class="rank-badge silver">🥈</span>
                                                @elseif($index === 2)
                                                    <span class="rank-badge bronze">🥉</span>
                                                @else
                                                    <span class="rank-number">{{ $actualRank }}</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="song-title">
                                            <a href="{{ url('/database/songs/' . $song['song_id']) }}" class="stats-link">{{ $song['title'] }}</a>
                                        </td>
                                        <td class="count-col">
                                            <span class="count-badge">{{ $song['count'] }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @if(count($songStats) > 10)
                            <div class="show-more-container">
                                <button class="show-more-btn" onclick="toggleSongRows(this)">
                                    Show More <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Most Encore Songs Section -->
                    <div class="stats-section visible">
                        <h2 class="section-title">
                            <i class="fas fa-star"></i> Most Performed Encore Songs
                        </h2>
                        <div class="stats-table-container">
                            <table class="stats-table">
                                <thead>
                                    <tr>
                                        <th class="rank-col">Rank</th>
                                        <th>Song Title</th>
                                        <th class="count-col">Times Performed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($encoreSongStats as $index => $song)
                                    @php
                                        $showRank = $index === 0 || $encoreSongStats[$index - 1]['count'] !== $song['count'];
                                        $actualRank = $index + 1;
                                    @endphp
                                    <tr>
                                        <td class="rank-col">
                                            @if($showRank)
                                                @if($index === 0)
                                                    <span class="rank-badge gold">🏆</span>
                                                @elseif($index === 1)
                                                    <span class="rank-badge silver">🥈</span>
                                                @elseif($index === 2)
                                                    <span class="rank-badge bronze">🥉</span>
                                                @else
                                                    <span class="rank-number">{{ $actualRank }}</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="song-title">
                                            <a href="{{ url('/database/songs/' . $song['song_id']) }}" class="stats-link">{{ $song['title'] }}</a>
                                        </td>
                                        <td class="count-col">
                                            <span class="count-badge">{{ $song['count'] }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Most Opening Songs Section -->
                    <div class="stats-section visible">
                        <h2 class="section-title">
                            <i class="fas fa-play"></i> Most Used Opening Songs
                        </h2>
                        <div class="stats-table-container">
                            <table class="stats-table">
                                <thead>
                                    <tr>
                                        <th class="rank-col">Rank</th>
                                        <th>Song Title</th>
                                        <th class="count-col">Times Used</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($openingSongStats as $index => $song)
                                    @php
                                        $showRank = $index === 0 || $openingSongStats[$index - 1]['count'] !== $song['count'];
                                        $actualRank = $index + 1;
                                    @endphp
                                    <tr>
                                        <td class="rank-col">
                                            @if($showRank)
                                                @if($index === 0)
                                                    <span class="rank-badge gold">🏆</span>
                                                @elseif($index === 1)
                                                    <span class="rank-badge silver">🥈</span>
                                                @elseif($index === 2)
                                                    <span class="rank-badge bronze">🥉</span>
                                                @else
                                                    <span class="rank-number">{{ $actualRank }}</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="song-title">
                                            <a href="{{ url('/database/songs/' . $song['song_id']) }}" class="stats-link">{{ $song['title'] }}</a>
                                        </td>
                                        <td class="count-col">
                                            <span class="count-badge">{{ $song['count'] }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Longest Setlists Section -->
                    <div class="stats-section visible">
                        <h2 class="section-title">
                            <i class="fas fa-list-ol"></i> Longest Setlists
                        </h2>
                        <div class="stats-table-container">
                            <table class="stats-table">
                                <thead>
                                    <tr>
                                        <th class="rank-col">Rank</th>
                                        <th>Tour</th>
                                        <th class="count-col">Songs</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($longestSetlists as $index => $setlist)
                                    @php
                                        $showRank = $index === 0 || $longestSetlists[$index - 1]['song_count'] !== $setlist['song_count'];
                                        $actualRank = $index + 1;
                                    @endphp
                                    <tr>
                                        <td class="rank-col">
                                            @if($showRank)
                                                @if($index === 0)
                                                    <span class="rank-badge gold">🏆</span>
                                                @elseif($index === 1)
                                                    <span class="rank-badge silver">🥈</span>
                                                @elseif($index === 2)
                                                    <span class="rank-badge bronze">🥉</span>
                                                @else
                                                    <span class="rank-number">{{ $actualRank }}</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="song-title">
                                            <a href="{{ url('/database/live/' . $setlist['tour_id']) }}" class="stats-link">
                                                {{ $setlist['tour_title'] }}
                                                @if($setlist['subtitle'])
                                                    <br><small style="color: #666;">{{ $setlist['subtitle'] }}</small>
                                                @endif
                                            </a>
                                        </td>
                                        <td class="count-col">
                                            <span class="count-badge">{{ $setlist['song_count'] }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tours by Year Section -->
                    <div class="stats-section visible">
                        <h2 class="section-title">
                            <i class="fas fa-calendar-alt"></i> Tours by Year
                        </h2>
                        <div class="stats-table-container">
                            <table class="stats-table has-bar-indicator">
                                <thead>
                                    <tr>
                                        <th>Year</th>
                                        <th class="count-col">Tour Count</th>
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
function toggleSongRows(button) {
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
</script>
@endsection
