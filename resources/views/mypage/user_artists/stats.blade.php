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
                    <div class="mb-3">
                        <a href="{{ route('mypage.user_artists.index') }}" class="stats-link stats-back-link">← Database</a>
                    </div>
                    <h1 class="stats-title">{{ $artist->name }} Statistics</h1>
                    <p class="stats-subtitle">セットリスト統計</p>

                    <div class="row stats-cards">
                        @foreach([
                            ['value' => $overallStats['total_concerts'], 'label' => 'Total Tours', 'icon' => 'fa-guitar'],
                            ['value' => $overallStats['total_songs'], 'label' => 'Total Songs', 'icon' => 'fa-music'],
                            ['value' => $overallStats['unique_songs_played'], 'label' => 'Songs in Setlists', 'icon' => 'fa-star'],
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

                    @foreach([
                        ['title' => 'Most Performed Songs in Tours', 'icon' => 'fa-fire', 'items' => $songStats, 'count_label' => 'Times Performed', 'accordion' => true],
                        ['title' => 'Most Performed Encore Songs', 'icon' => 'fa-star', 'items' => $encoreSongStats, 'count_label' => 'Times Performed', 'accordion' => false],
                        ['title' => 'Most Used Opening Songs', 'icon' => 'fa-play', 'items' => $openingSongStats, 'count_label' => 'Times Used', 'accordion' => false],
                    ] as $section)
                        <section class="stats-section visible">
                            <h2 class="section-title"><i class="fas {{ $section['icon'] }}"></i> {{ $section['title'] }} ({{ $section['items']->filter(fn ($song) => $song['count'] > 0)->count() }})</h2>
                            @if($section['accordion'] && $section['items']->count() > 10)
                                <div class="stats-accordion-toggle-row">
                                    <button class="stats-accordion-toggle" type="button" aria-label="一覧を展開" aria-expanded="false" onclick="toggleStatsRows(this)">
                                        <i class="fas fa-chevron-down" aria-hidden="true"></i>
                                    </button>
                                </div>
                            @endif
                            @if($section['items']->isEmpty())
                                <p>該当するデータはありません。</p>
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
                                </div>
                            @endif
                        </section>
                    @endforeach

                    <section class="stats-section visible">
                        <h2 class="section-title"><i class="fas fa-list-ol"></i> Longest Setlists</h2>
                        @if(empty($longestSetlists))
                            <p>該当するデータはありません。</p>
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
                            <p>該当するデータはありません。</p>
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
<script>
function toggleStatsRows(button) {
    const container = button.closest('.stats-table-container');
    const hiddenRows = container.querySelectorAll('.hidden-row');
    const isExpanded = button.classList.contains('expanded');

    hiddenRows.forEach(row => {
        row.style.display = isExpanded ? 'none' : 'table-row';
    });
    button.classList.toggle('expanded');
    button.setAttribute('aria-expanded', String(!isExpanded));
    button.setAttribute('aria-label', isExpanded ? '一覧を展開' : '一覧を折りたたむ');
    button.innerHTML = '<i class="fas fa-chevron-down" aria-hidden="true"></i>';
}
</script>
@endsection
