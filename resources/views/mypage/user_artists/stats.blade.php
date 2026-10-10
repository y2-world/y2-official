@extends('layouts.app')

@section('title', $artist->name . ' Statistics')
@section('og_title', $artist->name . ' Statistics - Yuki Official')
@section('og_description', $artist->name . ' の登録セットリスト統計')

@section('content')
<div class="stats-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="element js-fadein">
                    <h1 class="stats-title">{{ $artist->name }}</h1>
                    <p class="stats-subtitle">セットリスト統計</p>

                    {{-- 自分の参加記録の stats へのリンク（参加記録があるときだけ） --}}
                    @if ($hasAttended)
                    <div class="stamp-book-link-wrapper" style="margin: -18px 0 45px; display: flex; gap: 12px; flex-wrap: wrap; justify-content: center;">
                        <a href="{{ route('mypage.stats.artist', 'user-' . $artist->id) }}" class="stamp-book-link">
                            <i class="fas fa-ticket"></i> My Statistics
                        </a>
                    </div>
                    @endif

                    <div class="row stats-cards">
                        @foreach([
                            ['value' => $overallStats['total_concerts'], 'label' => 'Total Lives', 'icon' => 'fa-guitar'],
                            ['value' => $overallStats['total_songs'], 'label' => 'Total Songs', 'icon' => 'fa-music'],
                            ['value' => $overallStats['unique_songs_played'], 'label' => 'Songs Performed', 'icon' => 'fa-star'],
                            ['value' => $overallStats['avg_setlist_length'], 'label' => 'Avg Songs/Show', 'icon' => 'fa-chart-line'],
                        ] as $card)
                            <div class="col-md-3 col-sm-6 mb-4">
                                <div class="stat-card">
                                    <div class="stat-icon"><i class="fas {{ $card['icon'] }}"></i></div>
                                    <div class="stat-value">{{ $card['label'] === 'Avg Songs/Show' ? number_format($card['value'], 1) : number_format($card['value']) }}</div>
                                    <div class="stat-label">{{ $card['label'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- 本家の Database Stats と同じく、Songs（曲ごとのランキング・トピックス）／Data（セトリの中の位置や曲数、年ごとのツアー数）のタブで分ける --}}
                    <div class="stats-tab-bar">
                        <button type="button" class="stats-tab-btn is-active" data-stats-tab="songs">Songs</button>
                        <button type="button" class="stats-tab-btn" data-stats-tab="data">Data</button>
                        {{-- Artists：ほかのアーティストの stats へ切り替える --}}
                        @if (count($tabArtists) > 1)
                        <select class="stats-tab-btn stats-tab-select" onchange="if (this.value) location.href = this.value;" aria-label="Artists">
                            @foreach ($tabArtists as $tabArtist)
                                <option value="{{ $tabArtist['url'] }}" {{ $tabArtist['current'] ? 'selected' : '' }}>{{ $tabArtist['name'] }}</option>
                            @endforeach
                        </select>
                        @endif
                    </div>

                    <div class="stats-tab-panel" data-stats-panel="songs">
                    @foreach([
                        ['title' => 'Most Performed Songs', 'icon' => 'fa-fire', 'items' => $songStats, 'count_label' => 'Times', 'accordion' => true],
                    ] as $section)
                        <section class="stats-section visible">
                            <h2 class="section-title"><i class="fas {{ $section['icon'] }}"></i> {{ $section['title'] }} ({{ $section['items']->filter(fn ($song) => $song['count'] > 0)->count() }})</h2>
                            @if($section['items']->isEmpty())
                                <p style="color: #718096; text-align: center; font-size: 0.9rem; margin: 10px 0;">該当するデータはありません。</p>
                            @else
                                <div class="stats-table-container">
                                    <table class="stats-table">
                                        <thead><tr><th class="rank-col">Rank</th><th>Song Title</th><th class="count-col">{{ $section['count_label'] }}</th></tr></thead>
                                        <tbody>
                                            @foreach($section['items'] as $index => $song)
                                                @php
                                                    $showRank = $index === 0 || $section['items'][$index - 1]['count'] !== $song['count'];
                                                @endphp
                                                <tr class="{{ $section['accordion'] && $index >= 10 ? 'hidden-row' : '' }}">
                                                    <td class="rank-col">
                                                        @if($showRank)
                                                            @if($index === 0)<span class="rank-badge gold">🏆</span>
                                                            @elseif($index === 1)<span class="rank-badge silver">🥈</span>
                                                            @elseif($index === 2)<span class="rank-badge bronze">🥉</span>
                                                            @else<span class="rank-number">{{ $index + 1 }}</span>@endif
                                                        @endif
                                                    </td>
                                                    <td class="song-title"><a href="{{ route('mypage.user_songs.show', $song['id']) }}" class="stats-link">{{ $song['title'] }}</a></td>
                                                    <td class="count-col"><span class="count-badge">{{ $song['count'] }}</span></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @if($section['accordion'] && $section['items']->count() > 10)
                                        <div class="show-more-container">
                                            <button class="show-more-btn" type="button" onclick="toggleStatsRows(this)">Show More <i class="fas fa-chevron-down"></i></button>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </section>
                    @endforeach

                    {{-- 公式の Database Stats と同じトピックス。曲・ツアーのリンク先はマイページのページ --}}
                    @include('stats._topics_songs', ['topicSongUrl' => fn ($id) => route('mypage.user_songs.show', $id), 'topicTourUrl' => fn ($id) => route('mypage.user_concerts.show', $id)])
                    </div>

                    <div class="stats-tab-panel" data-stats-panel="data" style="display: none;">
                    @foreach([
                        ['title' => 'Most Used Opening Songs', 'icon' => 'fa-play', 'items' => $openingSongStats, 'count_label' => 'Times', 'accordion' => false],
                    ] as $section)
                        <section class="stats-section visible">
                            <h2 class="section-title"><i class="fas {{ $section['icon'] }}"></i> {{ $section['title'] }} ({{ $section['items']->filter(fn ($song) => $song['count'] > 0)->count() }})</h2>
                            @if($section['items']->isEmpty())
                                <p style="color: #718096; text-align: center; font-size: 0.9rem; margin: 10px 0;">該当するデータはありません。</p>
                            @else
                                <div class="stats-table-container">
                                    <table class="stats-table">
                                        <thead><tr><th class="rank-col">Rank</th><th>Song Title</th><th class="count-col">{{ $section['count_label'] }}</th></tr></thead>
                                        <tbody>
                                            @foreach($section['items'] as $index => $song)
                                                @php
                                                    $showRank = $index === 0 || $section['items'][$index - 1]['count'] !== $song['count'];
                                                @endphp
                                                <tr class="{{ $section['accordion'] && $index >= 10 ? 'hidden-row' : '' }}">
                                                    <td class="rank-col">
                                                        @if($showRank)
                                                            @if($index === 0)<span class="rank-badge gold">🏆</span>
                                                            @elseif($index === 1)<span class="rank-badge silver">🥈</span>
                                                            @elseif($index === 2)<span class="rank-badge bronze">🥉</span>
                                                            @else<span class="rank-number">{{ $index + 1 }}</span>@endif
                                                        @endif
                                                    </td>
                                                    <td class="song-title"><a href="{{ route('mypage.user_songs.show', $song['id']) }}" class="stats-link">{{ $song['title'] }}</a></td>
                                                    <td class="count-col"><span class="count-badge">{{ $song['count'] }}</span></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @if($section['accordion'] && $section['items']->count() > 10)
                                        <div class="show-more-container">
                                            <button class="show-more-btn" type="button" onclick="toggleStatsRows(this)">Show More <i class="fas fa-chevron-down"></i></button>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </section>
                    @endforeach

                    {{-- 本編ラスト曲（本家の Data タブと同じ位置）。曲のリンク先はマイページのページ --}}
                    @include('stats._topics_setlist', ['topicSongUrl' => fn ($id) => route('mypage.user_songs.show', $id)])

                    @foreach([
                        ['title' => 'Most Performed Encore Songs', 'icon' => 'fa-star', 'items' => $encoreSongStats, 'count_label' => 'Times', 'accordion' => false],
                    ] as $section)
                        <section class="stats-section visible">
                            <h2 class="section-title"><i class="fas {{ $section['icon'] }}"></i> {{ $section['title'] }} ({{ $section['items']->filter(fn ($song) => $song['count'] > 0)->count() }})</h2>
                            @if($section['items']->isEmpty())
                                <p style="color: #718096; text-align: center; font-size: 0.9rem; margin: 10px 0;">該当するデータはありません。</p>
                            @else
                                <div class="stats-table-container">
                                    <table class="stats-table">
                                        <thead><tr><th class="rank-col">Rank</th><th>Song Title</th><th class="count-col">{{ $section['count_label'] }}</th></tr></thead>
                                        <tbody>
                                            @foreach($section['items'] as $index => $song)
                                                @php
                                                    $showRank = $index === 0 || $section['items'][$index - 1]['count'] !== $song['count'];
                                                @endphp
                                                <tr class="{{ $section['accordion'] && $index >= 10 ? 'hidden-row' : '' }}">
                                                    <td class="rank-col">
                                                        @if($showRank)
                                                            @if($index === 0)<span class="rank-badge gold">🏆</span>
                                                            @elseif($index === 1)<span class="rank-badge silver">🥈</span>
                                                            @elseif($index === 2)<span class="rank-badge bronze">🥉</span>
                                                            @else<span class="rank-number">{{ $index + 1 }}</span>@endif
                                                        @endif
                                                    </td>
                                                    <td class="song-title"><a href="{{ route('mypage.user_songs.show', $song['id']) }}" class="stats-link">{{ $song['title'] }}</a></td>
                                                    <td class="count-col"><span class="count-badge">{{ $song['count'] }}</span></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    @if($section['accordion'] && $section['items']->count() > 10)
                                        <div class="show-more-container">
                                            <button class="show-more-btn" type="button" onclick="toggleStatsRows(this)">Show More <i class="fas fa-chevron-down"></i></button>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </section>
                    @endforeach

                    <section class="stats-section visible">
                        <h2 class="section-title"><i class="fas fa-list-ol"></i> Longest Setlists</h2>
                        @if(empty($longestSetlists))
                            <p style="color: #718096; text-align: center; font-size: 0.9rem; margin: 10px 0;">該当するデータはありません。</p>
                        @else
                            <div class="stats-table-container">
                                <table class="stats-table">
                                    <thead><tr><th class="rank-col">Rank</th><th>Live / Pattern</th><th class="count-col">Songs</th></tr></thead>
                                    <tbody>
                                        @foreach($longestSetlists as $index => $entry)
                                            @php $showRank = $index === 0 || $longestSetlists[$index - 1]['song_count'] !== $entry['song_count']; @endphp
                                            <tr>
                                                <td class="rank-col">
                                                    @if($showRank)
                                                        @if($index === 0)<span class="rank-badge gold">🏆</span>
                                                        @elseif($index === 1)<span class="rank-badge silver">🥈</span>
                                                        @elseif($index === 2)<span class="rank-badge bronze">🥉</span>
                                                        @else<span class="rank-number">{{ $index + 1 }}</span>@endif
                                                    @endif
                                                </td>
                                                <td><a href="{{ route('mypage.user_concerts.show', $entry['concert']->id) }}" class="stats-link">{{ $entry['concert']->title }}</a>@if($entry['setlist']->subtitle) <span class="text-muted">— {{ $entry['setlist']->subtitle }}</span>@endif</td>
                                                <td class="count-col"><span class="count-badge">{{ $entry['song_count'] }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>

                    <section class="stats-section visible">
                        <h2 class="section-title"><i class="fas fa-calendar-alt"></i> Tours by Year</h2>
                        @if($yearStats->isEmpty())
                            <p style="color: #718096; text-align: center; font-size: 0.9rem; margin: 10px 0;">該当するデータはありません。</p>
                        @else
                            <div class="stats-table-container">
                                <table class="stats-table has-bar-indicator">
                                    <thead><tr><th>Year</th><th class="count-col">Tour Count</th></tr></thead>
                                    <tbody>
                                        @foreach($yearStats as $yearData)
                                            <tr>
                                                <td class="year-col">{{ $yearData->year }}</td>
                                                <td class="count-col">
                                                    <div class="year-bar-container">
                                                        <div class="year-bar-wrapper"><div class="year-bar" style="width: {{ $yearStats->max('count') ? ($yearData->count / $yearStats->max('count')) * 100 : 0 }}%"></div></div>
                                                        <span class="year-count">{{ $yearData->count }}</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@include('stats._tabs_script')
<script>
function toggleStatsRows(button) {
    const container = button.closest('.stats-table-container');
    const hiddenRows = container.querySelectorAll('.hidden-row');
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
