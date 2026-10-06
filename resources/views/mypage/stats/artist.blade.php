@extends('layouts.app')

@section('title', $artist->name . ' - My Statistics')
@section('og_title', $artist->name . ' Statistics - Yuki Official')

@section('content')
@php
    // 本人以外（My Statistics を公開しているユーザーを見に来た人）が見るときは、本人だけが開ける参加記録へのリンクを外し、
    // 公式の曲はDatabaseの曲ページにする
    $isOwner = $isOwner ?? true;
    $officialSongId = fn ($ref) => str_starts_with((string) $ref, 'official-') ? (int) substr((string) $ref, 9) : null;
    $songUrl = fn ($ref) => $isOwner ? route('mypage.attendances.index', ['song_id' => $ref]) : ($officialSongId($ref) ? url('/database/songs/' . $officialSongId($ref)) : null);
@endphp
<div class="stats-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="element js-fadein">
                    <h1 class="stats-title">{{ $artist->name }}</h1>
                    <p class="stats-subtitle">{{ $isOwner ? 'My Artist Statistics' : (($statsUser->name ?: 'ゲスト') . ' のArtist Statistics') }}</p>

                    @if ($stampsRoute)
                    <div class="stamp-book-link-wrapper" style="margin: -18px 0 45px;">
                        <a href="{{ $stampsRoute }}" class="stamp-book-link">
                            <i class="fas fa-stamp"></i> View Live Stamp Book
                        </a>
                    </div>
                    @endif

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

                    {{-- 全体のstatsと同じタブ：Songs（曲）／Data（公演の年・会場）／Artists（ほかのアーティストへ切り替え）。スタンプ帳のボタンは見出しの下 --}}
                    <div class="stats-tab-bar">
                        <button type="button" class="stats-tab-btn is-active" data-stats-tab="songs">Songs</button>
                        <button type="button" class="stats-tab-btn" data-stats-tab="data">Data</button>
                        @if (count($tabArtists))
                        <select class="stats-tab-btn stats-tab-select" onchange="if (this.value) location.href = this.value;" aria-label="Artists">
                            <option value="{{ $isOwner ? route('mypage.stats') : route('mypage.users.stats', $statsUser) }}">All</option>
                            @foreach ($tabArtists as $tabArtist)
                                <option value="{{ $tabArtist['url'] }}" {{ $tabArtist['current'] ? 'selected' : '' }}>{{ $tabArtist['name'] }}</option>
                            @endforeach
                        </select>
                        @endif
                    </div>

                    <div class="stats-tab-panel" data-stats-panel="songs">
                    <!-- Top Songs for Artist -->
                    <div class="stats-section visible">
                        <div class="section-title-wrapper" style="flex-direction: column; align-items: center; gap: 10px;">
                            <h2 class="section-title" style="text-align: center;">
                                <i class="fas fa-fire"></i> Most Listened Songs (<span id="mypageArtistSongCountLabel">{{ count($allSongs) }}</span>)
    <span class="section-title-desc">参加したライブで聴いた回数が多い曲</span></h2>
                            {{-- 同じツアーに2回以上行っていなければ、数え方が変わらないのでチェックを出さない --}}
                            @if ($allSongs != $allSongsUnique)
                            <div class="unique-tour-toggle" style="margin-left: 0;">
                                <label class="unique-tour-label">
                                    <input type="checkbox" id="uniqueTourCheckboxMypageArtist" class="unique-tour-checkbox">
                                    <span class="unique-tour-text">同ツアーを除く</span>
                                </label>
                            </div>
                            @endif
                        </div>
                        <div class="stats-table-container">
                            <table class="stats-table" id="mypageArtistSongStatsTable">
                                <thead>
                                    <tr>
                                        <th class="rank-col">Rank</th>
                                        <th>Song Title</th>
                                        <th class="count-col">Times</th>
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
                                            @if ($songUrl($song['song_id']))<a href="{{ $songUrl($song['song_id']) }}" class="stats-link">{{ $song['title'] }}</a>@else{{ $song['title'] }}@endif
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

                    @includeWhen(isset($topicHeardRevivals), 'stats._topics_listener')
                    </div>

                    <div class="stats-tab-panel" data-stats-panel="data" style="display: none;">
                    <!-- Shows by Year Section -->
                    <div class="stats-section visible">
                        <h2 class="section-title">
                            <i class="fas fa-calendar-alt"></i> Shows by Year
    <span class="section-title-desc">参加したライブが多かった年</span></h2>
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
                                        <td class="year-col">@if ($isOwner)<a href="{{ route('mypage.attendances.index', ['artist_id' => $artistRef, 'year' => $yearData->year]) }}" class="stats-link">{{ $yearData->year }}</a>@else{{ $yearData->year }}@endif</td>
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
    <span class="section-title-desc">よく行った会場</span></h2>
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
                                        <td class="venue-name">@if ($isOwner)<a href="{{ route('mypage.attendances.index', ['artist_id' => $artistRef, 'venue' => $venue->venue]) }}" class="stats-link">{{ $venue->venue }}</a>@else{{ $venue->venue }}@endif</td>
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
</div>

@include('stats._tabs_script')
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

document.getElementById('uniqueTourCheckboxMypageArtist')?.addEventListener('change', function(e) {
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

        const isOwner = @json($isOwner);
        const songCell = song => {
            const official = String(song.song_id).startsWith('official-') ? String(song.song_id).slice(9) : null;
            const href = isOwner ? '{{ url('/mypage/attendances') }}?song_id=' + song.song_id : (official ? '/database/songs/' + official : null);
            return href ? '<a href="' + href + '" class="stats-link">' + song.title + '</a>' : song.title;
        };
        tr.innerHTML = `
            <td class="rank-col">${rankBadge}</td>
            <td class="song-title">
                ${songCell(song)}
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
