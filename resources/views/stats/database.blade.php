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
                    <h1 class="stats-title">{{ isset($artist) ? $artist->name : 'Statistics' }}</h1>
                    <p class="stats-subtitle">{{ isset($artist) ? 'セットリスト統計' : 'ライブ演奏履歴とデータ分析' }}</p>

                    @isset($artist)
                    {{-- 参加したライブの stats へのリンク（Yuki 本人は Yuki's Stats、ほかのログイン中のユーザーは My Statistics、未ログインは無し） --}}
                    @if (!empty($personalStatsLink))
                    <div class="stamp-book-link-wrapper" style="margin: -18px 0 45px; display: flex; justify-content: center;">
                        <a href="{{ $personalStatsLink['url'] }}" class="stamp-book-link">
                            <i class="fa-solid fa-chart-simple"></i> {{ $personalStatsLink['label'] }}
                        </a>
                    </div>
                    @endif
                    <!-- Overall Stats Cards -->
                    <div class="row stats-cards">
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-guitar"></i>
                                </div>
                                {{-- ツアーとイベントなど（1日だけのライブ）が混ざっているので「Tours / Shows」。公演数統計では公演の数 --}}
                                <div class="stat-value" id="statTotalValue">{{ $overallStats['total_tours'] }}</div>
                                <div class="stat-label" id="statTotalLabel">{{ $byShows ? 'Total Shows' : 'Total Tours/Shows' }}</div>
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
                                <div class="stat-label">Songs Performed</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-4">
                            <div class="stat-card">
                                <div class="stat-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="stat-value" id="statAvgValue">{{ $overallStats['avg_setlist_length'] }}</div>
                                <div class="stat-label">Avg Songs/Show</div>
                            </div>
                        </div>
                    </div>

                    {{-- 集計をタブで分ける：Songs（曲ごとのランキング・トピックス）／Setlists（セトリの中の位置、セトリの曲数）／Data（会場、公演の多いツアー、年ごとの数） --}}
                    <div class="stats-tab-bar">
                        <button type="button" class="stats-tab-btn is-active" data-stats-tab="songs">Songs</button>
                        <button type="button" class="stats-tab-btn" data-stats-tab="setlist">Setlists</button>
                        <button type="button" class="stats-tab-btn" data-stats-tab="data">Data</button>
                        {{-- Artists：ほかのアーティストのDatabaseのstatsへ切り替える --}}
                        @if (count($tabArtists) > 1)
                        <select class="stats-tab-btn stats-tab-select" onchange="statsGoToArtist(this)" aria-label="Artists">
                            @foreach ($tabArtists as $tabArtist)
                                <option value="{{ $tabArtist['url'] }}" {{ $tabArtist['current'] ? 'selected' : '' }}>{{ $tabArtist['name'] }}</option>
                            @endforeach
                        </select>
                        @endif
                    </div>

                    {{-- Songs・Data のボタンの下。パネルと曲のランキング（Most Performed・Encore・Opening・DOUBLE ENCORE）を、ツアー・ライブの数ではなく公演の数で出す（読み込み直さずに切り替える。?count=shows で開けば最初から公演数） --}}
                    <div class="unique-tour-toggle" style="margin: -10px 0 24px; display: flex; justify-content: center;">
                        {{-- 紫の背景の上なので白い文字 --}}
                        <label class="unique-tour-label" style="color: #fff;">
                            <input type="checkbox" id="countByShows" class="unique-tour-checkbox" {{ $byShows ? 'checked' : '' }}>
                            <span class="unique-tour-text">公演数統計に切替</span>
                        </label>
                    </div>
                    <div class="stats-tab-panel" data-stats-panel="songs">
                    <!-- Most Performed Songs Section -->
                    <div class="stats-section visible">
                        <div class="section-title-wrapper" style="flex-direction: column; align-items: center; gap: 10px;">
                            <h2 class="section-title" style="text-align: center;">
                                <i class="fas fa-fire"></i> Most Performed Songs (<span id="dbSongCountLabel">{{ count(array_filter($songStats, fn($s) => $s['count'] > 0)) }}</span>)
    <span class="section-title-desc" data-count-desc-tours="演奏されたツアー・ライブの数が多い曲" data-count-desc-shows="演奏された公演の数が多い曲">{{ $byShows ? '演奏された公演の数が多い曲' : '演奏されたツアー・ライブの数が多い曲' }}</span></h2>
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
                                        <th class="count-col">Times</th>
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
                                            @include('stats._other_rank', ['song' => $song])
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
                    @php $doubleEncoreSongStatsTop = array_slice($doubleEncoreSongStats, 0, 25); @endphp
                    <!-- DOUBLE ENCORE Songs Section（福山雅治のみ。DOUBLE ENCOREは弾き語り） -->
                    <div class="stats-section visible">
                        <h2 class="section-title">
                            <i class="fas fa-guitar"></i> Most Performed DOUBLE ENCORE Songs
    <span class="section-title-desc" data-count-desc-tours="DOUBLE ENCOREで演奏されたツアー・ライブの数が多い曲" data-count-desc-shows="DOUBLE ENCOREで演奏された公演の数が多い曲">DOUBLE ENCOREで{{ $byShows ? '演奏された公演の数が多い曲' : '演奏されたツアー・ライブの数が多い曲' }}</span></h2>
                        <div class="stats-table-container">
                            <table class="stats-table" id="dbDoubleEncoreStatsTable">
                                <thead>
                                    <tr>
                                        <th class="rank-col">Rank</th>
                                        <th>Song Title</th>
                                        <th class="count-col">Times</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($doubleEncoreSongStatsTop as $index => $song)
                                    @php
                                        $showRank = $index === 0 || $doubleEncoreSongStatsTop[$index - 1]['count'] !== $song['count'];
                                        $actualRank = $index + 1;
                                    @endphp
                                    <tr @if ($index >= 10) data-more="1" style="display: none;" @endif>
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
                                            @include('stats._other_rank', ['song' => $song])
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
                            @include('stats._show_more', ['count' => count($doubleEncoreSongStatsTop)])
                        </div>
                    </div>

                    @endif

                    @include('stats._topics_songs')

                    </div>

                    {{-- Setlists：セットリストの中の場所（1曲目・本編の最後・アンコール）で曲を並べたランキングと、曲数の多いセットリスト。
                         ボタンの文字は複数形、URL は ?stats_tab=setlist（?stats_tab=setlists は My Statistics の古いリンクで Data になる） --}}
                    <div class="stats-tab-panel" data-stats-panel="setlist" style="display: none;">
                    <!-- Most Opening Songs Section -->
                    <div class="stats-section visible">
                        <h2 class="section-title">
                            <i class="fas fa-play"></i> Most Used Opening Songs
    <span class="section-title-desc" data-count-desc-tours="1曲目に演奏されたツアー・ライブの数が多い曲" data-count-desc-shows="1曲目に演奏された公演の数が多い曲">1曲目に{{ $byShows ? '演奏された公演の数が多い曲' : '演奏されたツアー・ライブの数が多い曲' }}</span></h2>
                        <div class="stats-table-container">
                            <table class="stats-table" id="dbOpeningStatsTable">
                                <thead>
                                    <tr>
                                        <th class="rank-col">Rank</th>
                                        <th>Song Title</th>
                                        <th class="count-col">Times</th>
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
                                            @include('stats._other_rank', ['song' => $song])
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
    <span class="section-title-desc" data-count-desc-tours="アンコールで演奏されたツアー・ライブの数が多い曲" data-count-desc-shows="アンコールで演奏された公演の数が多い曲">アンコールで{{ $byShows ? '演奏された公演の数が多い曲' : '演奏されたツアー・ライブの数が多い曲' }}</span></h2>
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
                                        <th class="count-col">Times</th>
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
                                            @include('stats._other_rank', ['song' => $song])
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
                                                    <br><small style="color: #666;">{!! $setlist['subtitle'] !!}</small>
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

                    </div>

                    {{-- Data：会場・公演の多いツアー・年ごとの数 --}}
                    <div class="stats-tab-panel" data-stats-panel="data" style="display: none;">
                    <!-- Top Venues Section：スケジュールに出てくる会場の公演数。会場を押すと Database の会場のページ -->
                    @if ($venueStats->isNotEmpty())
                    <div class="stats-section visible">
                        <h2 class="section-title">
                            <i class="fas fa-map-marker-alt"></i> Top Venues
    <span class="section-title-desc">ツアー・単発ライブで公演の多い会場</span></h2>
                        <div class="stats-table-container">
                            <table class="stats-table">
                                <thead>
                                    <tr>
                                        <th class="rank-col">Rank</th>
                                        <th>Venue</th>
                                        <th class="count-col">Shows</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($venueStats as $index => $venue)
                                    @php
                                        $showRank = $index === 0 || $venueStats[$index - 1]->count !== $venue->count;
                                        $actualRank = $index + 1;
                                    @endphp
                                    <tr @if($index >= 10) data-more style="display: none;" @endif>
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
                                        <td class="venue-name">
                                            <a href="{{ route('database.venues', [$artist->id, 'keyword' => $venue->venue]) }}" class="stats-link">{{ $venue->venue }}</a>
                                        </td>
                                        <td class="count-col">
                                            <span class="count-badge">{{ $venue->count }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @include('stats._show_more', ['count' => count($venueStats)])
                        </div>
                    </div>
                    @endif

                    <!-- Most Shows Tours Section：公演の多いツアー・ライブ（Total Shows と同じ数え方）。ツアーを押すとそのツアーのページ -->
                    @if ($tourShowStats->isNotEmpty())
                    <div class="stats-section visible">
                        <h2 class="section-title">
                            <i class="fas fa-route"></i> Most Shows Tours
    <span class="section-title-desc">公演の多いツアー・ライブ</span></h2>
                        <div class="stats-table-container">
                            <table class="stats-table">
                                <thead>
                                    <tr>
                                        <th class="rank-col">Rank</th>
                                        <th>Tour / Show</th>
                                        <th class="count-col">Shows</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tourShowStats as $index => $tourShow)
                                    @php
                                        $showRank = $index === 0 || $tourShowStats[$index - 1]->count !== $tourShow->count;
                                        $actualRank = $index + 1;
                                    @endphp
                                    <tr @if($index >= 10) data-more style="display: none;" @endif>
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
                                            <a href="{{ url('/database/live/' . $tourShow->id) }}" class="stats-link">{{ $tourShow->title }}</a>
                                        </td>
                                        <td class="count-col">
                                            <span class="count-badge">{{ $tourShow->count }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @include('stats._show_more', ['count' => count($tourShowStats)])
                        </div>
                    </div>
                    @endif

                    <!-- Tours / Shows by Year Section（ツアーとイベントなど1日だけのライブが混ざっているので Tours / Shows） -->
                    <div class="stats-section visible">
                        <h2 class="section-title">
                            <i class="fas fa-calendar-alt"></i> Tours / Shows by Year
    <span class="section-title-desc">ツアー・ライブが多かった年</span></h2>
                        <div class="stats-table-container">
                            <table class="stats-table has-bar-indicator">
                                <thead>
                                    <tr>
                                        <th>Year</th>
                                        <th class="count-col">Tour / Show Count</th>
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

// 「公演数統計に切替」「DOUBLE ENCOREを除く」（福山雅治のみ）で、ページを読み込み直さずにパネルと表を描き直す（同ツアーを除くと同じ動き）
const countModes = @json($countModes);

// もう一方の数え方での順位（stats/_other_rank と同じ形）。上がった ↑・下がった ↓・同じなら矢印なし、もう一方で演奏されていなければ（-）
function otherRankHtml(song) {
    if (!song.rank) return '';
    if (!song.other_rank) return '<div class="rank-other">(-)</div>';
    const arrow = song.other_rank > song.rank ? '<span class="rank-other-up">↑</span>' : song.other_rank < song.rank ? '<span class="rank-other-down">↓</span>' : '';
    return '<div class="rank-other">(' + arrow + song.other_rank + ')</div>';
}

// 表を描き直す。collapse: 'hidden-row'（Show More で開く行）か 'data-more'（_show_more の行）。10曲目まではいつも出す
function renderRanking(tableId, data, collapse) {
    const table = document.getElementById(tableId);
    if (!table) return;
    const tbody = table.querySelector('tbody');
    const expanded = table.closest('.stats-section')?.querySelector('.show-more-btn')?.classList.contains('expanded');
    tbody.innerHTML = '';
    data.forEach((song, index) => {
        const tr = document.createElement('tr');
        if (collapse && index >= 10) {
            if (collapse === 'data-more') {
                tr.setAttribute('data-more', '1');
                if (!expanded) tr.style.display = 'none';
            } else {
                tr.classList.add('hidden-row');
                if (expanded) tr.style.display = 'table-row';
            }
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
        tr.innerHTML = `<td class="rank-col">${rankBadge}${otherRankHtml(song)}</td><td class="song-title"></td><td class="count-col"><span class="count-badge">${song.count}</span></td>`;
        tr.querySelector('.song-title').appendChild(link);
        tbody.appendChild(tr);
    });
}

function updateCountMode() {
    const mode = document.getElementById('countByShows')?.checked ? 'shows' : 'tours';
    const stats = countModes[mode];
    const noDoubleEncoreSongs = document.getElementById('excludeDoubleEncoreSongs')?.checked;
    const noDoubleEncoreEncore = document.getElementById('excludeDoubleEncoreEncore')?.checked;
    const songs = noDoubleEncoreSongs ? stats.songsNoDoubleEncore : stats.songs;
    renderRanking('dbSongStatsTable', songs, 'hidden-row');
    document.getElementById('dbSongCountLabel').textContent = songs.filter(s => s.count > 0).length;
    renderRanking('dbEncoreStatsTable', noDoubleEncoreEncore ? stats.encoreNoDoubleEncore : stats.encore, null);
    renderRanking('dbDoubleEncoreStatsTable', stats.doubleEncore, 'data-more');
    renderRanking('dbOpeningStatsTable', stats.opening, null);
    renderRanking('dbClosingStatsTable', stats.closing, 'data-more');
    document.getElementById('statTotalValue').textContent = stats.overall.total_tours;
    document.getElementById('statTotalLabel').textContent = mode === 'shows' ? 'Total Shows' : 'Total Tours/Shows';
    document.getElementById('statAvgValue').textContent = stats.overall.avg_setlist_length;
    document.querySelectorAll('[data-count-desc-tours]').forEach(el => {
        el.textContent = el.getAttribute(mode === 'shows' ? 'data-count-desc-shows' : 'data-count-desc-tours');
    });
    // 読み込み直したり共有したりしても同じ数え方になるよう、URL の ?count=shows も変える（ページは読み込み直さない）
    const url = new URL(window.location.href);
    if (mode === 'shows') url.searchParams.set('count', 'shows'); else url.searchParams.delete('count');
    history.replaceState(history.state, '', url.toString());
}
document.getElementById('countByShows')?.addEventListener('change', updateCountMode);
document.getElementById('excludeDoubleEncoreSongs')?.addEventListener('change', updateCountMode);
document.getElementById('excludeDoubleEncoreEncore')?.addEventListener('change', updateCountMode);
</script>
@endsection
