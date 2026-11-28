{{-- 参加した公演の stats（セトリの下）。マイページの参加記録のページと、Yuki's Setlists のセットリストのページで使う。
     見た目は stats のトピックス（stats._topics_listener）と同じ（PC は .topic-pc の表、スマホは .topic-sp の段に分けた表、最初は3曲ずつ出し、4曲目からは Show More）。
     $liveStats：ArtistTopics::attendanceSummary の結果（firstHeard・welcomeBack・returns・debuts。releaseYears は Timeline のタブ＝_live_timeline）。
     $showMostListened（任意）：Most Listened Songs を出す（Yuki's Setlists のページ）。
     $liveStatsSongUrl（任意）：曲の id => 曲のページの URL（渡さなければ Database の曲のページ）。$liveStatsTourUrl（任意）：ツアーの id => URL
     曲の無い区切りは出さない --}}
@php
    // 最初に出す曲数（4曲目からは Show More で開く。2026-11-29 ユーザー。stats のページは10曲）
    $liveStatsShown = 3;
    $liveStatsSongUrl ??= fn ($id) => url('/database/songs/' . $id);
    $liveStatsTourUrl ??= fn ($id) => route('live.show', $id);
    // 他のランキングと同じ見た目の順位（同じ値が続くときは順位を出さない）
    $topicRank = function (array $list, int $index, string $key) {
        if ($index > 0 && $list[$index - 1][$key] === $list[$index][$key]) {
            return '';
        }
        return [0 => '<span class="rank-badge gold">🏆</span>', 1 => '<span class="rank-badge silver">🥈</span>', 2 => '<span class="rank-badge bronze">🥉</span>'][$index]
            ?? '<span class="rank-number">' . ($index + 1) . '</span>';
    };
    // 公演・ツアーを「2015 - タイトル」の形で（タイトルだけをリンクに）
    $topicShowLink = fn ($date, $title, $url) => e(substr($date, 0, 4)) . ' - ' . ($url ? '<a href="' . e($url) . '" class="stats-link">' . e($title) . '</a>' : e($title));
    // 曲名はその公演の表記。別表記のときは、曲のページもその表記で絞り込んで開く（stats のトピックスと同じ）
    $topicSongLink = function ($row) use ($liveStatsSongUrl) {
        $href = $liveStatsSongUrl($row['song_id']);
        if (!empty($row['version'])) {
            $href .= (str_contains($href, '?') ? '&' : '?') . http_build_query(['title' => $row['version']]);
        }
        return '<a href="' . e($href) . '" class="stats-link">' . e($row['title']) . '</a>';
    };
    // 曲名だけの一覧（Live Debuts）
    $debutsSection = ['rows' => $liveStats['debuts'], 'icon' => 'fa-star', 'title' => 'Live Debuts', 'desc' => 'このツアーで初めて演奏された曲'];
@endphp
{{-- Analyze のタブの中に出すので、タブとの間はあけない（タブの下の余白だけ） --}}
<div class="live-stats">



@php
    // First Listens：stats の Recent First Listens と同じく「新曲を除く」（最初は除いた状態）。新曲以外（リリースから時間がたってから初めて聴けた曲）は太字。
    // 新曲を除くと0曲になる・除いても変わらないときは、チェックを出さずに全部を出す
    $firstAll = $liveStats['firstHeard'];
    $firstExclude = array_values(array_filter($firstAll, fn ($row) => empty($row['new'])));
    $firstShowToggle = count($firstExclude) && count($firstExclude) !== count($firstAll);
    $firstDefault = count($firstExclude) ? 'exclude' : 'all';
    $topicSongLinkMarked = fn ($row) => empty($row['new']) ? '<span style="font-weight: 700;">' . $topicSongLink($row) . '</span>' : $topicSongLink($row);
@endphp
@if (count($firstAll))
<div class="stats-section visible">
    <div class="section-title-wrapper" style="flex-direction: column; align-items: center; gap: 10px;">
        <h2 class="section-title" style="text-align: center;"><i class="fas fa-seedling"></i> First Listens
        <span class="section-title-desc">この公演で初めて生で聴いた曲</span></h2>
        @if ($firstShowToggle)
        <div class="unique-tour-toggle" style="margin-left: 0;">
            <label class="unique-tour-label">
                <input type="checkbox" class="unique-tour-checkbox live-first-exclude-new" checked>
                <span class="unique-tour-text">新曲を除く</span>
            </label>
        </div>
        @endif
    </div>
    <div class="stats-table-container">
        {{-- 曲名の1列だけなので、スマホも PC と同じ表（スマホ用の段の表は2段の見出しの前提で、1段だと見出しが細くなる） --}}
        <table class="stats-table">
            <thead><tr><th>Song Title</th></tr></thead>
            @foreach (['exclude' => $firstExclude, 'all' => $firstAll] as $kind => $rows)
            <tbody data-recent-kind="{{ $kind }}" @if ($kind !== $firstDefault) style="display: none;" data-kind-hidden="1" @endif>
                @foreach ($rows as $index => $row)
                <tr @if ($index >= $liveStatsShown) data-more="1" style="display: none;" @endif>
                    <td class="song-title">{!! $topicSongLinkMarked($row) !!}</td>
                </tr>
                @endforeach
            </tbody>
            @endforeach
        </table>
        {{-- Show More は、今出している一覧（新曲を除く／全部）の曲数が多いときだけ出す（切り替えたら出し直す） --}}
        <div class="live-first-more" data-counts='@json(['exclude' => count($firstExclude), 'all' => count($firstAll)])' data-limit="{{ $liveStatsShown }}"
            @if (count($firstDefault === 'exclude' ? $firstExclude : $firstAll) <= $liveStatsShown) style="display: none;" @endif>
            @include('stats._show_more', ['count' => max(count($firstExclude), count($firstAll)), 'limit' => $liveStatsShown])
        </div>
    </div>
</div>
@endif
@once
<script>
// 「新曲を除く」の切り替え（stats の Recent First Listens と同じ動き。フェスではアーティストごとにあるので、そのセクションの中だけ）
document.addEventListener('change', function (e) {
    if (!e.target.classList.contains('live-first-exclude-new')) return;
    var shown = e.target.checked ? 'exclude' : 'all';
    var section = e.target.closest('.stats-section');
    var expanded = !!section.querySelector('.show-more-btn.expanded');
    section.querySelectorAll('tbody[data-recent-kind]').forEach(function (t) {
        var on = t.dataset.recentKind === shown;
        t.style.display = on ? '' : 'none';
        on ? t.removeAttribute('data-kind-hidden') : t.setAttribute('data-kind-hidden', '1');
        t.querySelectorAll('tr[data-more]').forEach(function (tr) { tr.style.display = on && expanded ? '' : 'none'; });
    });
    section.querySelectorAll('tbody[data-kind^="recent-"]').forEach(function (t) {
        var on = t.dataset.kind === 'recent-' + shown;
        on ? t.removeAttribute('data-kind-hidden') : t.setAttribute('data-kind-hidden', '1');
        t.style.display = on && (expanded || !t.dataset.more) ? '' : 'none';
    });
    // 切り替えた一覧が最初に出す曲数以下なら、Show More を隠す
    var more = section.querySelector('.live-first-more');
    if (more) {
        more.style.display = JSON.parse(more.dataset.counts)[shown] > Number(more.dataset.limit) ? '' : 'none';
    }
});
</script>
@endonce

@php
    // Welcome Back のあいた期間：1年以上は「x.x年」、1年未満は「Nヶ月」、1ヶ月未満は「N日」
    $welcomeBackBadge = fn ($row) => $row['years'] >= 1 ? number_format($row['years'], 1) . '年' : ($row['months'] >= 1 ? $row['months'] . 'ヶ月' : $row['days'] . '日');
@endphp
@if (count($liveStats['welcomeBack']))
<div class="stats-section visible">
    <h2 class="section-title"><i class="fas fa-hand-sparkles"></i> Welcome Back
    <span class="section-title-desc">前に聴いてから何年ぶりに、この公演で聴いたか</span></h2>
    <div class="stats-table-container">
        <table class="stats-table topic-pc">
            <thead><tr><th class="rank-col">Rank</th><th>Song Title</th><th>Tour Title</th><th class="count-col">Years</th></tr></thead>
            <tbody>
                @foreach ($liveStats['welcomeBack'] as $index => $row)
                <tr @if ($index >= $liveStatsShown) data-more="1" style="display: none;" @endif>
                    <td class="rank-col">{!! $topicRank($liveStats['welcomeBack'], $index, 'days') !!}</td>
                    <td class="song-title">{!! $topicSongLink($row) !!}</td>
                    {{-- 前に聴いた公演だけ（「→ この公演」はこのページそのものなので出さない） --}}
                    <td style="font-size: 0.85em;">{!! $topicShowLink($row['from']['date'], $row['from']['title'], $row['from']['url'] ?? null) !!}</td>
                    <td class="count-col"><span class="count-badge">{{ $welcomeBackBadge($row) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <table class="stats-table stats-table-stacked topic-sp">
            @include('stats._stacked_head', ['hasRank' => true, 'badgeLabel' => 'Years', 'showArtist' => false])
            @foreach ($liveStats['welcomeBack'] as $index => $row)
                @include('stats._stacked_row', ['rank' => $topicRank($liveStats['welcomeBack'], $index, 'days'), 'song' => $topicSongLink($row), 'artist' => null, 'showArtist' => false,
                    'lines' => [['tour' => $topicShowLink($row['from']['date'], $row['from']['title'], $row['from']['url'] ?? null)]], 'badge' => $welcomeBackBadge($row), 'more' => $index >= $liveStatsShown])
            @endforeach
        </table>
        @include('stats._show_more', ['count' => count($liveStats['welcomeBack']), 'limit' => $liveStatsShown])
    </div>
</div>
@endif

@if (count($liveStats['returns']))
<div class="stats-section visible">
    <h2 class="section-title"><i class="fas fa-rotate-left"></i> Long-Awaited Returns
    <span class="section-title-desc">この公演で演奏された曲が、前のツアーから何年ぶりの演奏だったか（1年以上）</span></h2>
    <div class="stats-table-container">
        <table class="stats-table topic-pc">
            <thead><tr><th class="rank-col">Rank</th><th>Song Title</th><th>Tour Title</th><th class="count-col">Years</th></tr></thead>
            <tbody>
                @foreach ($liveStats['returns'] as $index => $row)
                <tr @if ($index >= $liveStatsShown) data-more="1" style="display: none;" @endif>
                    <td class="rank-col">{!! $topicRank($liveStats['returns'], $index, 'years') !!}</td>
                    <td class="song-title">{!! $topicSongLink($row) !!}</td>
                    {{-- 前回のツアーだけ（「→ この公演・ライブ」はこのページそのものなので出さない） --}}
                    <td style="font-size: 0.85em;">{{ substr($row['previous']['date'], 0, 4) }} / <a href="{{ $liveStatsTourUrl($row['previous']['id']) }}" class="stats-link">{{ $row['previous']['title'] }}</a></td>
                    <td class="count-col"><span class="count-badge">{{ number_format($row['years'], 1) }}年</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <table class="stats-table stats-table-stacked topic-sp">
            @include('stats._stacked_head', ['hasRank' => true, 'badgeLabel' => 'Years', 'showArtist' => false])
            @foreach ($liveStats['returns'] as $index => $row)
                @include('stats._stacked_row', ['rank' => $topicRank($liveStats['returns'], $index, 'years'), 'song' => $topicSongLink($row), 'artist' => null, 'showArtist' => false,
                    'lines' => [['tour' => $topicShowLink($row['previous']['date'], $row['previous']['title'], $liveStatsTourUrl($row['previous']['id']))]], 'badge' => number_format($row['years'], 1) . '年', 'more' => $index >= $liveStatsShown])
            @endforeach
        </table>
        @include('stats._show_more', ['count' => count($liveStats['returns']), 'limit' => $liveStatsShown])
    </div>
</div>
@endif

{{-- Not Played Since（Database のライブのページだけ。stats の Long Time No Play の位置） --}}
@include('mypage.attendances._live_stats_badges', ['shown' => $liveStatsShown, 'section' => ['rows' => $liveStats['notPlayedSince'] ?? [], 'icon' => 'fa-hourglass-half', 'title' => 'Not Played Since',
    'desc' => 'このライブのあと演奏されていない曲', 'label' => 'Years', 'badge' => fn ($row) => number_format($row['years'], 1) . '年', 'rankKey' => null]])

@include('mypage.attendances._live_stats_songs', ['section' => $debutsSection, 'topicSongLink' => $topicSongLink, 'shown' => $liveStatsShown])

{{-- Late Live Debuts・Performed Only Once（Database のライブのページだけ。並びは stats と同じ） --}}
@include('mypage.attendances._live_stats_badges', ['shown' => $liveStatsShown, 'section' => ['rows' => $liveStats['lateDebuts'] ?? [], 'icon' => 'fa-hourglass-start', 'title' => 'Late Live Debuts',
    'desc' => 'このライブが初演で、発売から1年以上たっていた曲', 'label' => 'Years', 'badge' => fn ($row) => number_format($row['years'], 1) . '年', 'rankKey' => 'years',
    'detail' => fn ($row) => date('Y.m.d', strtotime($row['released']))]])
@include('mypage.attendances._live_stats_songs', ['section' => ['rows' => $liveStats['onlyHere'] ?? [], 'icon' => 'fa-1', 'title' => 'Performed Only Once', 'desc' => 'このライブでしか演奏されていない曲'], 'topicSongLink' => $topicSongLink, 'shown' => $liveStatsShown])



{{-- Most Listened Songs（Yuki's Setlists のページだけ。$showMostListened）：この公演で聴いた曲を、この公演までに聴いた回数の多い順。いちばん下 --}}
@if (!empty($showMostListened))
    @include('mypage.attendances._live_stats_badges', ['shown' => $liveStatsShown, 'section' => ['rows' => $liveStats['mostListened'] ?? [], 'icon' => 'fa-fire', 'title' => 'Most Listened Songs',
        'desc' => 'この公演までに聴いた回数（この公演を含む）', 'label' => 'Times', 'badge' => fn ($row) => $row['count'], 'rankKey' => 'count']])
@endif

</div>
