@extends('layouts.app')

@section('title', 'My Statistics')
@section('og_title', 'My Statistics - Yuki Official')

@section('content')
@php
    // プロフィール画面として、本人以外がこの統計を見ることがある（My Statistics を公開しているユーザー）。
    // 参加記録の一覧・詳細などの本人だけが開けるページへのリンクは、本人以外にはDatabaseのページ（公式の曲・アーティスト・ライブ）にし、
    // それが無いもの（自分で登録したアーティストなど）はリンクなしの文字にする。アーティスト別のstats・スタンプ帳は ?user= でその人の分を開く
    $isOwner = $isOwner ?? true;
    $asProfile = $asProfile ?? false;
    $refId = fn ($ref) => (int) substr((string) $ref, strpos((string) $ref, '-') + 1);
    $isOfficialRef = fn ($ref) => str_starts_with((string) $ref, 'official-');
    $songUrl = fn ($ref) => $isOwner ? route('mypage.attendances.index', ['song_id' => $ref]) : ($isOfficialRef($ref) ? url('/database/songs/' . $refId($ref)) : null);
    // アーティスト名は、公式ならDatabaseのアーティストのトップ、自分で登録したアーティストならそのアーティストのページへ
    //（アーティスト別のstatsへは、タブの Artists から移動する）
    $artistUrl = fn ($ref) => $isOfficialRef($ref) ? route('database.artist', $refId($ref)) : route('mypage.user_artists.show', $refId($ref));
    $artistStatsUrl = fn ($ref) => $isOwner ? route('mypage.stats.artist', $ref) : route('mypage.stats.artist', ['artistId' => $ref, 'user' => $statsUser->id]);
    $stampsUrl = fn ($ref) => $isOwner ? route('mypage.stats.stamps', $ref) : route('mypage.stats.stamps', ['artistId' => $ref, 'user' => $statsUser->id]);
    $link = fn ($url, $text) => $url ? '<a href="' . e($url) . '" class="stats-link">' . e($text) . '</a>' : e($text);
@endphp
<div class="stats-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="element js-fadein">
                    @if ($asProfile)
                        <div class="profile-header">
                            @if ($statsUser->avatar_url)
                                <img src="{{ $statsUser->avatar_url }}" alt="" class="profile-avatar">
                            @else
                                <div class="profile-avatar profile-avatar--placeholder"><i class="fa-solid fa-user"></i></div>
                            @endif
                            <h1 class="stats-title" style="margin: 12px 0 0; font-size: 1.8rem;">{{ $statsUser->name ?: 'ゲスト' }}</h1>
                            @if ($statsUser->bio)
                                <p class="profile-bio">{{ $statsUser->bio }}</p>
                            @endif
                        </div>
                        <p class="stats-subtitle" style="margin-top: 20px;">参加したライブの記録</p>
                    @else
                    <div style="text-align: center;">
                        <h1 class="stats-title" style="margin-bottom: 0;">My Statistics</h1>
                    </div>
                    <p class="stats-subtitle" style="margin-top: 10px;">参加したライブの記録</p>
                    @endif

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

                    {{-- 全体のstatsと同じく、タブで分ける：Songs（曲）／Setlists（参加記録・アーティスト・会場・時期）／Stamps（スタンプ帳）／Artists（アーティスト別のstatsへ移動） --}}
                    <div class="stats-tab-bar">
                        <button type="button" class="stats-tab-btn is-active" data-stats-tab="songs">Songs</button>
                        <button type="button" class="stats-tab-btn" data-stats-tab="data">Data</button>
                        <button type="button" class="stats-tab-btn" data-stats-tab="stamps">Stamps</button>
                        {{-- Artists：選んだアーティストのstatsに移動する --}}
                        @if (count($artistStats))
                        <select class="stats-tab-btn stats-tab-select" onchange="if (this.value) location.href = this.value;">
                            <option value="" selected>All</option>
                            {{-- 初めてライブに行った順 --}}
                            @foreach ($tabArtistStats as $tabArtist)
                                <option value="{{ $artistStatsUrl($tabArtist['id']) }}">{{ $tabArtist['name'] }}</option>
                            @endforeach
                        </select>
                        @endif
                    </div>

                    <div class="stats-tab-panel" data-stats-panel="songs">
                    <!-- Most Listened Songs Section -->
                    <div class="stats-section visible">
                        <div class="section-title-wrapper" style="flex-direction: column; align-items: center; gap: 10px;">
                            <h2 class="section-title" style="text-align: center;">
                                <i class="fas fa-fire"></i> Most Listened Songs (<span id="mypageSongCountLabel">{{ count($topSongs) }}</span>)
    <span class="section-title-desc">参加したライブで聴いた回数が多い曲</span></h2>
                            {{-- 同じツアーに2回以上行っていなければ、数え方が変わらないのでチェックを出さない --}}
                            @if ($topSongs != $topSongsUnique)
                            <div class="unique-tour-toggle" style="margin-left: 0;">
                                <label class="unique-tour-label">
                                    <input type="checkbox" id="mypageUniqueTourCheckbox" class="unique-tour-checkbox">
                                    <span class="unique-tour-text">同ツアーを除く</span>
                                </label>
                            </div>
                            @endif
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
                                                {!! $link($songUrl($song['song_id']), $song['title']) !!}
                                            </td>
                                            <td class="artist-name">
                                                @if ($song['artist_id'])
                                                    {!! $link($artistUrl($song['artist_id']), $song['artist_name']) !!}
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

                    @includeWhen(isset($topicHeardRevivals), 'stats._topics_listener', ['showFirstListens' => false, 'topicArtistUrl' => fn ($id) => $artistUrl('official-' . $id)])
                    </div>

                    <div class="stats-tab-panel" data-stats-panel="data" style="display: none;">
                    <!-- Artist Statistics Section -->
                    <div class="stats-section visible">
                        <div class="section-title-wrapper">
                            <h2 class="section-title">
                                <i class="fas fa-microphone"></i> Artist Statistics
    <span class="section-title-desc">参加したライブが多いアーティスト</span></h2>
                        </div>
                        <div class="stats-table-container">
                            <table class="stats-table">
                                <thead>
                                    <tr>
                                        <th class="rank-col">Rank</th>
                                        <th>Artist Name</th>
                                        <th class="count-col">Shows</th>
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
                                                {!! $link($artistUrl($artist['id']), $artist['name']) !!}
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
                                            <td class="venue-name">
                                                {!! $link($isOwner ? route('mypage.attendances.index', ['venue' => $venue->venue]) : null, $venue->venue) !!}
                                            </td>
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
    <span class="section-title-desc">参加したライブが多かった年</span></h2>
                        <div class="stats-table-container">
                            <table class="stats-table has-bar-indicator">
                                <thead>
                                    <tr>
                                        <th>Year</th>
                                        <th class="count-col">Shows</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($yearStats as $yearStat)
                                        <tr>
                                            <td class="year-col">{!! $link($isOwner ? route('mypage.attendances.index', ['year' => $yearStat->year]) : null, $yearStat->year) !!}</td>
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

                    <div class="stats-tab-panel" data-stats-panel="stamps" style="display: none;">
                    @if ($artistSongStats->isNotEmpty())
                        <!-- Live Stamp Book Section -->
                        <div class="stats-section visible">
                            <div class="section-title-wrapper">
                                <h2 class="section-title">
                                    <i class="fas fa-stamp"></i> Live Stamp Book
    <span class="section-title-desc">アーティストごとの、聴いた曲のスタンプ帳</span></h2>
                            </div>
                            <style>
                                @media (max-width: 768px) {
                                    .stamp-book-link-wrapper {
                                        justify-content: center !important;
                                    }
                                }
                            </style>
                            <div class="stamp-book-link-wrapper" style="display: flex; gap: 12px; flex-wrap: wrap; justify-content: flex-start;">
                                @foreach ($stampBookArtists as $artistStat)
                                    <a href="{{ $stampsUrl($artistStat['id']) }}" class="stamp-book-link">
                                        <i class="fas fa-stamp"></i> {{ $artistStat['name'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($artistSongStats->isNotEmpty())
                        <!-- Unique Songs by Artist Section -->
                        <div class="stats-section visible">
                            <h2 class="section-title">
                                <i class="fas fa-music"></i> Unique Songs by Artist
    <span class="section-title-desc">アーティストごとの、聴いた曲の数と全曲に対する割合</span></h2>
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
                                                {!! $link($artistUrl($artistStat['id']), $artistStat['name']) !!}
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
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

@include('stats._tabs_script')
<script>
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

document.getElementById('mypageUniqueTourCheckbox')?.addEventListener('change', function(e) {
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

        // 本人以外が見るときは、公式の曲・アーティストだけDatabaseのページへリンクする（上の表と同じ）
        const isOwner = @json($isOwner);
        const officialId = ref => String(ref).startsWith('official-') ? String(ref).slice(9) : null;
        const songHref = isOwner ? '/mypage/attendances?song_id=' + song.song_id : (officialId(song.song_id) ? '/database/songs/' + officialId(song.song_id) : null);
        const artistHref = !song.artist_id ? null : (officialId(song.artist_id) ? '/database/artists/' + officialId(song.artist_id) : '/mypage/artists/' + String(song.artist_id).replace('user-', ''));
        const artistCell = artistHref ? '<a href="' + artistHref + '" class="stats-link">' + song.artist_name + '</a>' : song.artist_name;
        const songCell = songHref ? '<a href="' + songHref + '" class="stats-link">' + song.title + '</a>' : song.title;

        tr.innerHTML = `
            <td class="rank-col">${rankBadge}</td>
            <td class="song-title">
                ${songCell}
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
