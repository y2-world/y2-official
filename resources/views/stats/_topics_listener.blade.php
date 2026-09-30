{{-- 参加記録と組み合わせたトピックス（App\Support\ArtistTopics）。Yukiのstatsとマイページのstatsで共通。
     全体のstatsでは $topicArtistUrl（アーティストID => リンク先）を渡すと、Artistの列をリンクにする。
     First Listens by Year は $showFirstListens = false で出さない（全体のstatsでは出さない） --}}
@php
    // 他のランキングと同じ見た目の順位（同じ値が続くときは順位を出さない）
    $topicRank = function (array $list, int $index, string $key) {
        if ($index > 0 && $list[$index - 1][$key] === $list[$index][$key]) {
            return '';
        }
        return [0 => '<span class="rank-badge gold">🏆</span>', 1 => '<span class="rank-badge silver">🥈</span>', 2 => '<span class="rank-badge bronze">🥉</span>'][$index]
            ?? '<span class="rank-number">' . ($index + 1) . '</span>';
    };
    // 参加した公演のリンク（「2015 / タイトル」の形で、タイトルだけをリンクに）
    $topicShowLink = fn ($date, $title, $url) => e(substr($date, 0, 4)) . ' / ' . ($url ? '<a href="' . e($url) . '" class="stats-link">' . e($title) . '</a>' : e($title));
    $topicSongLink = fn ($row) => '<a href="' . e(url('/database/songs/' . $row['song_id'])) . '" class="stats-link">' . e($row['title']) . '</a>';
    // 全体のstatsでは、アーティストをまたいで並ぶのでアーティスト名の列（Artist）を足す
    $topicWelcomeBack = $topicWelcomeBack ?? [];
    $topicRecentFirst = $topicRecentFirst ?? [];
    $topicShowArtist = collect($topicHeardRevivals)->merge($topicWelcomeBack)->merge($topicRecentFirst)->contains(fn ($row) => !empty($row['artist']));
    $topicArtistCell = fn ($row) => isset($topicArtistUrl, $row['artist_id']) && ($artistHref = $topicArtistUrl($row['artist_id']))
        ? '<a href="' . e($artistHref) . '" class="stats-link">' . e($row['artist']) . '</a>' : e($row['artist'] ?? '');
@endphp

@if (count($topicRecentFirst))
<div class="stats-section visible">
    <h2 class="section-title"><i class="fas fa-seedling"></i> Recent First Listens
    <span class="section-title-desc">最近、初めて生で聴いた曲</span></h2>
    <div class="stats-table-container">
        <table class="stats-table">
            <thead><tr><th>Song Title</th>@if ($topicShowArtist)<th>Artist</th>@endif<th>Tour Title</th><th class="count-col">Date</th></tr></thead>
            <tbody>
                @foreach ($topicRecentFirst as $row)
                <tr>
                    <td class="song-title">{!! $topicSongLink($row) !!}</td>
                    @if ($topicShowArtist)<td>{!! $topicArtistCell($row) !!}</td>@endif
                    <td style="font-size: 0.85em;">@if (!empty($row['show_url']))<a href="{{ $row['show_url'] }}" class="stats-link">{{ $row['show'] }}</a>@else{{ $row['show'] }}@endif</td>
                    <td class="count-col"><span class="count-badge">{{ date('Y.m.d', strtotime($row['date'])) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if (count($topicWelcomeBack))
<div class="stats-section visible">
    <h2 class="section-title"><i class="fas fa-hand-sparkles"></i> Welcome Back
    <span class="section-title-desc">自分が聴いた中で、前に聴いてから久しぶりに聴いた曲</span></h2>
    <div class="stats-table-container">
        <table class="stats-table">
            <thead><tr><th class="rank-col">Rank</th><th>Song Title</th>@if ($topicShowArtist)<th>Artist</th>@endif<th>Tour Title</th><th class="count-col">Years</th></tr></thead>
            <tbody>
                @foreach ($topicWelcomeBack as $index => $row)
                <tr>
                    <td class="rank-col">{!! $topicRank($topicWelcomeBack, $index, 'years') !!}</td>
                    <td class="song-title">{!! $topicSongLink($row) !!}</td>
                    @if ($topicShowArtist)<td>{!! $topicArtistCell($row) !!}</td>@endif
                    <td style="font-size: 0.85em;">{!! $topicShowLink($row['from']['date'], $row['from']['title'], $row['from']['url'] ?? null) !!}<br>→ {!! $topicShowLink($row['to']['date'], $row['to']['title'], $row['to']['url'] ?? null) !!}</td>
                    <td class="count-col"><span class="count-badge">{{ number_format($row['years'], 1) }}年</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if (count($topicHeardRevivals))
<div class="stats-section visible">
    <h2 class="section-title"><i class="fas fa-rotate-left"></i> Long-Awaited Returns I Heard
    <span class="section-title-desc">参加したライブで聴いた曲が、前のツアーから何年ぶりの演奏だったか</span></h2>
    <div class="stats-table-container">
        <table class="stats-table">
            <thead><tr><th class="rank-col">Rank</th><th>Song Title</th>@if ($topicShowArtist)<th>Artist</th>@endif<th>Tour Title</th><th class="count-col">Years</th></tr></thead>
            <tbody>
                @foreach ($topicHeardRevivals as $index => $row)
                <tr>
                    <td class="rank-col">{!! $topicRank($topicHeardRevivals, $index, 'years') !!}</td>
                    <td class="song-title">{!! $topicSongLink($row) !!}</td>
                    @if ($topicShowArtist)<td>@if (isset($topicArtistUrl, $row['artist_id']))<a href="{{ $topicArtistUrl($row['artist_id']) }}" class="stats-link">{{ $row['artist'] }}</a>@else{{ $row['artist'] ?? '' }}@endif</td>@endif
                    <td style="font-size: 0.85em;">{{ substr($row['previous']['date'], 0, 4) }} / <a href="{{ route('live.show', $row['previous']['id']) }}" class="stats-link">{{ $row['previous']['title'] }}</a><br>
                        → {!! $topicShowLink($row['date'], $row['show'], $row['show_url'] ?? null) !!}</td>
                    <td class="count-col"><span class="count-badge">{{ number_format($row['years'], 1) }}年</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if (($showFirstListens ?? true) && count($topicFirstHeard))
<div class="stats-section visible">
    <div class="section-title-wrapper" style="flex-direction: column; align-items: center; gap: 10px;">
        <h2 class="section-title" style="text-align: center;"><i class="fas fa-seedling"></i> First Listens by Year
        <span class="section-title-desc">その年に初めて生で聴いた曲。そのツアーのアルバム以外の曲は太字</span></h2>
        {{-- 新曲（そのライブの1年前以降に発売された曲と、発売前だった曲）を除いて見る。ページを読み込み直さずにその場で切り替える --}}
        <div class="unique-tour-toggle" style="margin-left: 0;">
            <label class="unique-tour-label">
                <input type="checkbox" class="unique-tour-checkbox" id="firstListensExcludeNew">
                <span class="unique-tour-text">新曲を除く</span>
            </label>
        </div>
    </div>
    <div class="stats-table-container">
        <table class="stats-table" id="firstListensTable">
            <thead><tr><th style="width: 80px; white-space: nowrap;">Year</th><th>Songs</th><th class="count-col">Count</th></tr></thead>
            <tbody>
                @foreach ($topicFirstHeard as $year => $songsOfYear)
                <tr class="first-listens-row">
                    <td style="white-space: nowrap;">{{ $year }}</td>
                    <td style="font-size: 0.85em; line-height: 1.8;">@foreach ($songsOfYear as $song)<span class="first-listen" style="display: inline;" @if ($song['new']) data-new="1" @endif><span class="first-listen-sep" style="display: inline;">{{ $loop->first ? '' : ' / ' }}</span>{!! $song['tour_album'] ? $topicSongLink($song) : '<strong>' . $topicSongLink($song) . '</strong>' !!}</span>@endforeach</td>
                    <td class="count-col"><span class="count-badge first-listens-count">{{ count($songsOfYear) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<script>
document.getElementById('firstListensExcludeNew')?.addEventListener('change', function (e) {
    var excludeNew = e.target.checked;
    document.querySelectorAll('#firstListensTable .first-listens-row').forEach(function (row) {
        var visible = 0;
        row.querySelectorAll('.first-listen').forEach(function (song) {
            var show = !(excludeNew && song.dataset.new === '1');
            song.style.display = show ? 'inline' : 'none';
            // 区切りの「 / 」は、見えている曲の2曲目以降にだけ付ける
            song.querySelector('.first-listen-sep').style.display = show && visible > 0 ? 'inline' : 'none';
            if (show) visible++;
        });
        row.querySelector('.first-listens-count').textContent = visible;
        row.style.display = visible ? '' : 'none';
    });
});
</script>
@endif
