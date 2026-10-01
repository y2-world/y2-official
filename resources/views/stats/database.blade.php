@extends('layouts.app')

@section('title', isset($artist) ? $artist->name . ' Statistics' : 'Database Statistics')
@section('og_title', isset($artist) ? $artist->name . ' Statistics - Yuki Official' : 'Database Statistics - Yuki Official')
@section('og_description', isset($artist) ? $artist->name . ' setlist statistics and analytics' : 'Database statistics and analytics')

@section('content')
<div class="stats-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="element js-fadein">
                    <h1 class="stats-title">{{ isset($artist) ? $artist->name . ' Statistics' : 'Statistics' }}</h1>
                    <p class="stats-subtitle">{{ isset($artist) ? 'セットリスト統計' : 'ライブ演奏履歴とデータ分析' }}</p>

                    @isset($artist)
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

                    {{-- 集計をタブで分ける：Songs（曲ごとのランキング・トピックス）／Data（セトリの中の位置や曲数、年ごとのツアー数） --}}
                    <div class="stats-tab-bar">
                        <button type="button" class="stats-tab-btn is-active" data-stats-tab="songs">Songs</button>
                        <button type="button" class="stats-tab-btn" data-stats-tab="data">Data</button>
                        {{-- Artists：ほかのアーティストのDatabaseのstatsへ切り替える --}}
                        @if (count($tabArtists) > 1)
                        <select class="stats-tab-btn stats-tab-select" onchange="if (this.value) location.href = this.value;" aria-label="Artists">
                            @foreach ($tabArtists as $tabArtist)
                                <option value="{{ $tabArtist['url'] }}" {{ $tabArtist['current'] ? 'selected' : '' }}>{{ $tabArtist['name'] }}</option>
                            @endforeach
                        </select>
                        @endif
                    </div>

                    <div class="stats-tab-panel" data-stats-panel="songs">
                    <!-- Most Performed Songs Section -->
                    <div class="stats-section visible">
                        <div class="section-title-wrapper" style="flex-direction: column; align-items: center; gap: 10px;">
                            <h2 class="section-title" style="text-align: center;">
                                <i class="fas fa-fire"></i> Most Performed Songs in Tours
    <span class="section-title-desc">演奏されたツアーの数が多い曲</span></h2>
                            @if (!empty($isHikigatariArtist) && !empty($doubleEncoreSongStats))
                            {{-- 福山雅治のみ：演奏回数を、DOUBLE ENCORE（弾き語り）を除いて数える（その場で表を切り替える） --}}
                            <div class="unique-tour-toggle" style="margin-left: 0;">
                                <label class="unique-tour-label">
                                    <input type="checkbox" id="excludeDoubleEncoreSongs" class="unique-tour-checkbox">
                                    <span class="unique-tour-text">DOUBLE ENCOREを除く</span>
                                </label>
                            </div>
                            @endif
                        </div>
                        <div class="stats-table-container">
                            <table class="stats-table" id="dbSongStatsTable">
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

                    @if (!empty($isHikigatariArtist) && !empty($doubleEncoreSongStats))
                    @php $doubleEncoreSongStatsTop = array_slice($doubleEncoreSongStats, 0, 10); @endphp
                    <!-- DOUBLE ENCORE Songs Section（福山雅治のみ。DOUBLE ENCOREは弾き語り） -->
                    <div class="stats-section visible">
                        <h2 class="section-title">
                            <i class="fas fa-guitar"></i> Most Performed DOUBLE ENCORE Songs
    <span class="section-title-desc">DOUBLE ENCOREで演奏されたツアーの数が多い曲</span></h2>
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
                                    @foreach($doubleEncoreSongStatsTop as $index => $song)
                                    @php
                                        $showRank = $index === 0 || $doubleEncoreSongStatsTop[$index - 1]['count'] !== $song['count'];
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

                    @endif

                    @include('stats._topics_songs')

                    </div>

                    <div class="stats-tab-panel" data-stats-panel="data" style="display: none;">
                    <!-- Most Opening Songs Section -->
                    <div class="stats-section visible">
                        <h2 class="section-title">
                            <i class="fas fa-play"></i> Most Used Opening Songs
    <span class="section-title-desc">1曲目に演奏されたツアーの数が多い曲</span></h2>
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

                    @include('stats._topics_setlist')

                    <!-- Most Encore Songs Section -->
                    <div class="stats-section visible">
                        <div class="section-title-wrapper" style="flex-direction: column; align-items: center; gap: 10px;">
                            <h2 class="section-title" style="text-align: center;">
                                <i class="fas fa-star"></i> Most Performed Encore Songs
    <span class="section-title-desc">アンコールで演奏されたツアーの数が多い曲</span></h2>
                            @if (!empty($isHikigatariArtist) && !empty($doubleEncoreSongStats))
                            {{-- 福山雅治のみ：アンコールの回数を、DOUBLE ENCORE（弾き語り）を除いて数える（その場で表を切り替える） --}}
                            <div class="unique-tour-toggle" style="margin-left: 0;">
                                <label class="unique-tour-label">
                                    <input type="checkbox" id="excludeDoubleEncoreEncore" class="unique-tour-checkbox">
                                    <span class="unique-tour-text">DOUBLE ENCOREを除く</span>
                                </label>
                            </div>
                            @endif
                        </div>
                        <div class="stats-table-container">
                            <table class="stats-table" id="dbEncoreStatsTable">
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

                    <!-- Longest Setlists Section -->
                    <div class="stats-section visible">
                        <h2 class="section-title">
                            <i class="fas fa-list-ol"></i> Longest Setlists
    <span class="section-title-desc">1公演の曲数が多いセットリスト</span></h2>
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
                                            <a href="{{ route('live.show', $setlist['tour_id']) }}" class="stats-link">
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
    <span class="section-title-desc">ツアー・ライブが多かった年</span></h2>
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
                                        <td class="year-col"><a href="{{ url('/database/years/' . $yearData->year) }}" class="stats-link">{{ $yearData->year }}</a></td>
                                        <td class="count-col">
                                            <div class="year-bar-container">
                                                <div class="year-bar-wrapper">
                                                    <div class="year-bar" style="width: {{ ($yearData->count / $yearStats->max('count')) * 100 }}%"></div>
                                                </div>
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
                    <a href="https://ameblo.jp/y2-world" target="_blank">Blog</a>・
                    <a href="{{ url('/admin') }}" target="_blank">Admin</a>
                    <br>
                    <div class="footer-copyright">©2024 y2 records inc.</div>
                </div>
            </div>
            <br>
        </div>
    </div>
    @endisset

</div>

@include('stats._tabs_script')
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

@if (!empty($isHikigatariArtist) && !empty($doubleEncoreSongStats))
// 福山雅治のみ：「DOUBLE ENCOREを除く」で、ページを読み込み直さずに表を描き直す（同ツアーを除くと同じ動き）
const dbSongStats = { normal: @json($songStats), noDoubleEncore: @json($songStatsNoDoubleEncore) };
const dbEncoreStats = { normal: @json($encoreSongStats), noDoubleEncore: @json($encoreSongStatsNoDoubleEncore) };

function renderRanking(tableId, data, collapseAfter) {
    const tbody = document.querySelector('#' + tableId + ' tbody');
    const expanded = document.querySelector('#' + tableId + ' ~ .show-more-container .show-more-btn')?.classList.contains('expanded');
    tbody.innerHTML = '';
    data.forEach((song, index) => {
        const tr = document.createElement('tr');
        if (collapseAfter && index >= collapseAfter) {
            tr.classList.add('hidden-row');
            if (expanded) tr.style.display = 'table-row';
        }
        const showRank = index === 0 || data[index - 1].count !== song.count;
        let rankBadge = '';
        if (showRank) {
            rankBadge = index === 0 ? '<span class="rank-badge gold">🏆</span>'
                : index === 1 ? '<span class="rank-badge silver">🥈</span>'
                : index === 2 ? '<span class="rank-badge bronze">🥉</span>'
                : '<span class="rank-number">' + (index + 1) + '</span>';
        }
        const link = document.createElement('a');
        link.href = '/database/songs/' + song.song_id;
        link.className = 'stats-link';
        link.textContent = song.title;
        tr.innerHTML = `<td class="rank-col">${rankBadge}</td><td class="song-title"></td><td class="count-col"><span class="count-badge">${song.count}</span></td>`;
        tr.querySelector('.song-title').appendChild(link);
        tbody.appendChild(tr);
    });
}

document.getElementById('excludeDoubleEncoreSongs')?.addEventListener('change', function (e) {
    const data = e.target.checked ? dbSongStats.noDoubleEncore : dbSongStats.normal;
    renderRanking('dbSongStatsTable', data, 10);
});
document.getElementById('excludeDoubleEncoreEncore')?.addEventListener('change', function (e) {
    renderRanking('dbEncoreStatsTable', e.target.checked ? dbEncoreStats.noDoubleEncore : dbEncoreStats.normal, 0);
});
@endif
</script>
@endsection
