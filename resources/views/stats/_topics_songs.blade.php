{{-- Databaseのstats「Songs」タブのトピックス（App\Support\ArtistTopics）。空いた期間は年数を小数で出す（1年半なら1.5）。
     PC（.topic-pc）はツアー名を「1992 / ツアー名」の1行で、スマホ（.topic-sp）は崩れないよう1曲を2〜3段に分けて出す --}}
@php
    // 他のランキングと同じ見た目の順位（同じ値が続くときは順位を出さない）
    $topicRank = function (array $list, int $index, string $key) {
        if ($index > 0 && $list[$index - 1][$key] === $list[$index][$key]) {
            return '';
        }
        return [0 => '<span class="rank-badge gold">🏆</span>', 1 => '<span class="rank-badge silver">🥈</span>', 2 => '<span class="rank-badge bronze">🥉</span>'][$index]
            ?? '<span class="rank-number">' . ($index + 1) . '</span>';
    };
    $topicSongLink = fn ($row) => '<a href="' . e(url('/database/songs/' . $row['song_id'])) . '" class="stats-link">' . e($row['title']) . '</a>';
    $topicTourLink = fn ($tour) => '<a href="' . e(route('live.show', $tour['id'])) . '" class="stats-link">' . e($tour['title']) . '</a>';
    $topicYear = fn ($date) => e(substr($date, 0, 4));
    // PC：「1992 / ツアー名」の形で、ツアー名だけをリンクにする
    $topicTourLine = fn ($tour) => '<span style="font-size: 0.85em;">' . $topicYear($tour['date']) . ' / ' . $topicTourLink($tour) . '</span>';
    $topicYears = fn ($row) => number_format($row['years'], 1) . '年';
@endphp

@if (count($topicRevivals))
<div class="stats-section visible">
    <h2 class="section-title"><i class="fas fa-rotate-left"></i> Long-Awaited Returns
    <span class="section-title-desc">前の演奏から長い間をおいて、久しぶりに演奏された曲</span></h2>
    <div class="stats-table-container">
        <table class="stats-table topic-pc">
            <thead><tr><th class="rank-col">Rank</th><th>Song Title</th><th>Tour Title</th><th class="count-col">Years</th></tr></thead>
            <tbody>
                @foreach ($topicRevivals as $index => $row)
                <tr>
                    <td class="rank-col">{!! $topicRank($topicRevivals, $index, 'years') !!}</td>
                    <td class="song-title">{!! $topicSongLink($row) !!}</td>
                    <td>{!! $topicTourLine($row['from']) !!}<br>→ {!! $topicTourLine($row['to']) !!}</td>
                    <td class="count-col"><span class="count-badge">{{ $topicYears($row) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <table class="stats-table stats-table-stacked topic-sp">
            @include('stats._stacked_head', ['hasRank' => true, 'badgeLabel' => 'Years', 'showArtist' => false])
            @foreach ($topicRevivals as $index => $row)
                @include('stats._stacked_row', ['rank' => $topicRank($topicRevivals, $index, 'years'), 'song' => $topicSongLink($row), 'artist' => null, 'showArtist' => false,
                    'lines' => [['tour' => $topicTourLink($row['from'])], ['tour' => '→ ' . $topicTourLink($row['to'])]], 'badge' => $topicYears($row)])
            @endforeach
        </table>
    </div>
</div>
@endif

@if (count($topicDormant))
<div class="stats-section visible">
    <div class="section-title-wrapper" style="flex-direction: column; align-items: center; gap: 10px;">
        <h2 class="section-title" style="text-align: center;"><i class="fas fa-hourglass-half"></i> Long Time No Play
        <span class="section-title-desc">いちばん長い間演奏されていない曲</span></h2>
        {{-- シングルの表題曲だけにする（ページを読み込み直さずにその場で切り替える） --}}
        @if (count($topicDormantSingles))
        <div class="unique-tour-toggle" style="margin-left: 0;">
            <label class="unique-tour-label">
                <input type="checkbox" class="unique-tour-checkbox" id="dormantSinglesOnly">
                <span class="unique-tour-text">シングルのみ</span>
            </label>
        </div>
        @endif
    </div>
    <div class="stats-table-container">
        <table class="stats-table topic-pc">
            <thead><tr><th class="rank-col">Rank</th><th>Song Title</th><th>Tour Title</th><th class="count-col">Years</th></tr></thead>
            @foreach (['all' => $topicDormant, 'singles' => $topicDormantSingles] as $kind => $rows)
            <tbody data-kind="{{ $kind }}" @if ($kind === 'singles') style="display: none;" @endif>
                @foreach ($rows as $index => $row)
                <tr>
                    <td class="rank-col">{!! $topicRank($rows, $index, 'years') !!}</td>
                    <td class="song-title">{!! $topicSongLink($row) !!}</td>
                    <td>{!! $topicTourLine($row['last']) !!}</td>
                    <td class="count-col"><span class="count-badge">{{ $topicYears($row) }}</span></td>
                </tr>
                @endforeach
            </tbody>
            @endforeach
        </table>
        <table class="stats-table stats-table-stacked topic-sp">
            @include('stats._stacked_head', ['hasRank' => true, 'badgeLabel' => 'Years', 'showArtist' => false])
            @foreach (['all' => $topicDormant, 'singles' => $topicDormantSingles] as $kind => $rows)
                @foreach ($rows as $index => $row)
                    @include('stats._stacked_row', ['rank' => $topicRank($rows, $index, 'years'), 'song' => $topicSongLink($row), 'artist' => null, 'showArtist' => false,
                        'lines' => [['tour' => $topicTourLink($row['last'])]], 'badge' => $topicYears($row), 'kind' => $kind, 'hidden' => $kind === 'singles'])
                @endforeach
            @endforeach
        </table>
    </div>
</div>
<script>
document.getElementById('dormantSinglesOnly')?.addEventListener('change', function (e) {
    document.querySelectorAll('tbody[data-kind]').forEach(function (tbody) {
        tbody.style.display = (tbody.dataset.kind === 'singles') === e.target.checked ? '' : 'none';
    });
});
</script>
@endif

@if (count($topicLateDebuts))
<div class="stats-section visible">
    <h2 class="section-title"><i class="fas fa-hourglass-start"></i> Late Live Debuts
    <span class="section-title-desc">発売から、初めて演奏されるまでに時間がかかった曲</span></h2>
    <div class="stats-table-container">
        <table class="stats-table topic-pc">
            <thead><tr><th class="rank-col">Rank</th><th>Song Title</th><th>Tour Title</th><th class="count-col">Years</th></tr></thead>
            <tbody>
                @foreach ($topicLateDebuts as $index => $row)
                <tr>
                    <td class="rank-col">{!! $topicRank($topicLateDebuts, $index, 'years') !!}</td>
                    <td class="song-title">{!! $topicSongLink($row) !!}</td>
                    <td><span style="font-size: 0.85em;">{{ date('Y.m.d', strtotime($row['released'])) }}</span><br>→ {!! $topicTourLine($row['first']) !!}</td>
                    <td class="count-col"><span class="count-badge">{{ $topicYears($row) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <table class="stats-table stats-table-stacked topic-sp">
            @include('stats._stacked_head', ['hasRank' => true, 'badgeLabel' => 'Years', 'showArtist' => false])
            @foreach ($topicLateDebuts as $index => $row)
                @include('stats._stacked_row', ['rank' => $topicRank($topicLateDebuts, $index, 'years'), 'song' => $topicSongLink($row), 'artist' => null, 'showArtist' => false,
                    'lines' => [['tour' => e(date('Y.m.d', strtotime($row['released'])))], ['tour' => '→ ' . $topicTourLink($row['first'])]], 'badge' => $topicYears($row)])
            @endforeach
        </table>
    </div>
</div>
@endif

@if (count($topicFormerStaples))
<div class="stats-section visible">
    <h2 class="section-title"><i class="fas fa-box-archive"></i> Former Staples
    <span class="section-title-desc">定番曲だったのに、演奏されなくなった曲</span></h2>
    <div class="stats-table-container">
        <table class="stats-table topic-pc">
            <thead><tr><th class="rank-col">Rank</th><th>Song Title</th><th>Tour Title</th><th class="count-col">Years</th></tr></thead>
            <tbody>
                @foreach ($topicFormerStaples as $index => $row)
                <tr>
                    <td class="rank-col">{!! $topicRank($topicFormerStaples, $index, 'years') !!}</td>
                    <td class="song-title">{!! $topicSongLink($row) !!} <span style="color: #a0aec0; font-size: 0.8em;">{{ $row['tours'] }} tours</span></td>
                    <td>{!! $topicTourLine($row['last']) !!}</td>
                    <td class="count-col"><span class="count-badge">{{ $topicYears($row) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <table class="stats-table stats-table-stacked topic-sp">
            @include('stats._stacked_head', ['hasRank' => true, 'badgeLabel' => 'Years', 'showArtist' => false])
            @foreach ($topicFormerStaples as $index => $row)
                @include('stats._stacked_row', ['rank' => $topicRank($topicFormerStaples, $index, 'years'), 'song' => $topicSongLink($row) . '<span class="stats-sub">' . $row['tours'] . ' tours</span>', 'artist' => null, 'showArtist' => false,
                    'lines' => [['tour' => $topicTourLink($row['last'])]], 'badge' => $topicYears($row)])
            @endforeach
        </table>
    </div>
</div>
@endif
