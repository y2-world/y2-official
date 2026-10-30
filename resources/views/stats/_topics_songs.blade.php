{{-- Databaseのstats「Songs」タブのトピックス（App\Support\ArtistTopics）。空いた期間は年数を小数で出す（1年半なら1.5）。
     PC（.topic-pc）はツアー名を「1992 - ツアー名」の1行で、スマホ（.topic-sp）は崩れないよう1曲を2〜3段に分けて出す --}}
@php
    // 他のランキングと同じ見た目の順位（同じ値が続くときは順位を出さない）
    $topicRank = function (array $list, int $index, string $key) {
        if ($index > 0 && $list[$index - 1][$key] === $list[$index][$key]) {
            return '';
        }
        return [0 => '<span class="rank-badge gold">🏆</span>', 1 => '<span class="rank-badge silver">🥈</span>', 2 => '<span class="rank-badge bronze">🥉</span>'][$index]
            ?? '<span class="rank-number">' . ($index + 1) . '</span>';
    };
    // 曲・ツアーのリンク先は $topicSongUrl / $topicTourUrl（ID => URL）で変えられる（マイページで作ったアーティストではマイページのページへ）。渡さなければ Database
    $topicSongUrl ??= fn ($id) => url('/database/songs/' . $id);
    $topicTourUrl ??= fn ($id) => route('live.show', $id);
    $topicSongLink = fn ($row) => '<a href="' . e($topicSongUrl($row['song_id'])) . '" class="stats-link">' . e($row['title']) . '</a>';
    $topicTourLink = fn ($tour) => '<a href="' . e($topicTourUrl($tour['id'])) . '" class="stats-link">' . e($tour['title']) . '</a>';
    $topicYear = fn ($date) => e(substr($date, 0, 4));
    // PC：「1992 - ツアー名」の形で、ツアー名だけをリンクにする
    $topicTourLine = fn ($tour) => '<span style="font-size: 0.85em;">' . $topicYear($tour['date']) . ' - ' . $topicTourLink($tour) . '</span>';
    $topicYears = fn ($row) => number_format($row['years'], 1) . '年';
    // スマホ：「年 - ツアー名」（パソコンと同じ）
    $topicTourWithYear = fn ($tour) => $topicYear($tour['date']) . ' - ' . $topicTourLink($tour);
@endphp

@if (count($topicRevivals))
{{-- 50曲まで出し、最初は10曲だけ見せる（残りは Show More で開く） --}}
<div class="stats-section visible" id="topicRevivalsSection">
    <h2 class="section-title"><i class="fas fa-rotate-left"></i> Long-Awaited Returns
    <span class="section-title-desc">前の演奏から長い間をおいて、久しぶりに演奏された曲</span></h2>
    <div class="stats-table-container">
        <table class="stats-table topic-pc">
            <thead><tr><th class="rank-col">Rank</th><th>Song Title</th><th>Tour Title</th><th class="count-col">Years</th></tr></thead>
            <tbody>
                @foreach ($topicRevivals as $index => $row)
                <tr @if ($index >= 10) data-more="1" style="display: none;" @endif>
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
                    'lines' => [['tour' => $topicTourWithYear($row['from'])], ['tour' => '→ ' . $topicTourWithYear($row['to'])]], 'badge' => $topicYears($row), 'more' => $index >= 10])
            @endforeach
        </table>
        @if (count($topicRevivals) > 10)
        <div class="show-more-container">
            <button type="button" class="show-more-btn" id="revivalsShowMore">
                Show More <i class="fas fa-chevron-down"></i>
            </button>
        </div>
        @endif
    </div>
</div>
<script>
(function () {
    var section = document.getElementById('topicRevivalsSection');
    var button = document.getElementById('revivalsShowMore');
    if (!button) return;
    var expanded = false;
    // PCの表の行と、スマホの表の1曲ぶん（tbody）の両方を開け閉めする
    button.addEventListener('click', function () {
        expanded = !expanded;
        section.querySelectorAll('[data-more]').forEach(function (el) { el.style.display = expanded ? '' : 'none'; });
        button.classList.toggle('expanded', expanded);
        button.innerHTML = expanded ? 'Show Less <i class="fas fa-chevron-up"></i>' : 'Show More <i class="fas fa-chevron-down"></i>';
    });
})();
</script>
@endif

@if (count($topicDormant))
{{-- 50曲まで出し、最初は10曲だけ見せる（残りは Show More で開く）。シングルのみの切り替えと一緒に、その場で表示を変える --}}
@php $dormantShown = 10; @endphp
<div class="stats-section visible" id="topicDormantSection">
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
                <tr @if ($index >= $dormantShown) data-more="1" style="display: none;" @endif>
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
                        'lines' => [['tour' => $topicTourWithYear($row['last'])]], 'badge' => $topicYears($row), 'kind' => $kind, 'hidden' => $kind === 'singles', 'more' => $index >= $dormantShown])
                @endforeach
            @endforeach
        </table>
        @if (max(count($topicDormant), count($topicDormantSingles)) > $dormantShown)
        <div class="show-more-container" id="dormantShowMoreContainer">
            <button type="button" class="show-more-btn" id="dormantShowMore">
                Show More <i class="fas fa-chevron-down"></i>
            </button>
        </div>
        @endif
    </div>
</div>
<script>
(function () {
    var section = document.getElementById('topicDormantSection');
    var singles = document.getElementById('dormantSinglesOnly');
    var button = document.getElementById('dormantShowMore');
    var expanded = false;
    var counts = { all: {{ count($topicDormant) }}, singles: {{ count($topicDormantSingles) }} };
    // 選んでいる一覧（全曲 / シングルのみ）と、開いているかどうかに合わせて、PCの表とスマホの表の両方を出し分ける
    function render() {
        var kind = singles && singles.checked ? 'singles' : 'all';
        section.querySelectorAll('.topic-pc tbody[data-kind]').forEach(function (tbody) {
            tbody.style.display = tbody.dataset.kind === kind ? '' : 'none';
            tbody.querySelectorAll('tr[data-more]').forEach(function (tr) { tr.style.display = expanded ? '' : 'none'; });
        });
        section.querySelectorAll('.topic-sp tbody.stacked-item[data-kind]').forEach(function (tbody) {
            tbody.style.display = tbody.dataset.kind === kind && (expanded || !tbody.dataset.more) ? '' : 'none';
        });
        if (button) {
            document.getElementById('dormantShowMoreContainer').style.display = counts[kind] > {{ $dormantShown }} ? '' : 'none';
            button.classList.toggle('expanded', expanded);
            button.innerHTML = expanded ? 'Show Less <i class="fas fa-chevron-up"></i>' : 'Show More <i class="fas fa-chevron-down"></i>';
        }
    }
    if (singles) singles.addEventListener('change', render);
    if (button) button.addEventListener('click', function () { expanded = !expanded; render(); });
    render();
})();
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
                <tr @if ($index >= 10) data-more="1" style="display: none;" @endif>
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
                    'lines' => [['tour' => e(date('Y.m.d', strtotime($row['released'])))], ['tour' => '→ ' . $topicTourWithYear($row['first'])]], 'badge' => $topicYears($row), 'more' => $index >= 10])
            @endforeach
        </table>
        @include('stats._show_more', ['count' => count($topicLateDebuts)])
    </div>
</div>
@endif

@php
    $topicOnlyOnce ??= [];
    $topicOnlyOnceTours ??= $topicOnlyOnce;
    // ツアーごとにまとめる（新しいツアーから。曲はセットリストの順）。all：全部のライブで数えたもの / tours：種類が「ツアー」のライブだけで数えたもの
    $onlyOnceGroups = fn ($rows) => collect($rows)->groupBy(fn ($row) => $row['tour']['id'])
        ->map(fn ($rows) => ['tour' => $rows->first()['tour'], 'songs' => $rows->values()->all()])->values()->all();
    $onlyOnceLists = ['all' => $onlyOnceGroups($topicOnlyOnce), 'tours' => $onlyOnceGroups($topicOnlyOnceTours)];
    // ツアーだけで数えても変わらない・0曲になるときは、チェックを出さない
    $onlyOnceShowToggle = count($onlyOnceLists['tours']) && $onlyOnceLists['tours'] != $onlyOnceLists['all'];
@endphp
@if (count($onlyOnceLists['all']))
{{-- ツアーごとに、そのツアーでしか演奏されていない曲を出す。最初は10ツアーだけ見せる（残りは Show More で開く）。
     data-count-*：一覧ごとのツアー数（今見えている一覧が10ツアー以下なら Show More を隠す） --}}
<div class="stats-section visible" id="topicOnlyOnceSection" data-count-all="{{ count($onlyOnceLists['all']) }}" data-count-tours="{{ count($onlyOnceLists['tours']) }}">
    <div class="section-title-wrapper" style="flex-direction: column; align-items: center; gap: 10px;">
        <h2 class="section-title" style="text-align: center;"><i class="fas fa-1"></i> Performed Only Once
        <span class="section-title-desc">1つのツアーでしか演奏されていない曲（ツアー別）</span></h2>
        {{-- 種類が「ツアー」のライブだけで数える（単発ライブ・イベントなどで演奏したことは数えない）。最初はオフ --}}
        @if ($onlyOnceShowToggle)
        <div class="unique-tour-toggle" style="margin-left: 0;">
            <label class="unique-tour-label">
                <input type="checkbox" class="unique-tour-checkbox" id="onlyOnceToursOnly">
                <span class="unique-tour-text">ツアーのみ</span>
            </label>
        </div>
        @endif
    </div>
    <div class="stats-table-container">
        <table class="stats-table topic-pc">
            <thead><tr><th>Tour Title</th><th>Song Title</th><th class="count-col">Songs</th></tr></thead>
            @foreach ($onlyOnceLists as $kind => $groups)
            <tbody data-only-once-kind="{{ $kind }}" @if ($kind !== 'all') style="display: none;" data-kind-hidden="1" @endif>
                @foreach ($groups as $index => $group)
                <tr @if ($index >= 10) data-more="1" style="display: none;" @endif>
                    {{-- ツアーが1列目の主役なので、ほかの表（$topicTourLine は 0.85em）より小さくせず、ふつうの大きさで出す --}}
                    <td>{!! $topicTourWithYear($group['tour']) !!}</td>
                    <td class="song-title">{!! implode('<br>', array_map($topicSongLink, $group['songs'])) !!}</td>
                    <td class="count-col"><span class="count-badge">{{ count($group['songs']) }}</span></td>
                </tr>
                @endforeach
            </tbody>
            @endforeach
        </table>
        <table class="stats-table stats-table-stacked topic-sp">
            {{-- スマホ：1段目にツアー（ほかの表のツアー名と同じ小さい文字）、2段目に曲（曲名の文字で1行ずつ） --}}
            <thead><tr><th>Tour Title</th><th class="count-col" rowspan="2">Songs</th></tr><tr><th>Song Title</th></tr></thead>
            @foreach ($onlyOnceLists as $kind => $groups)
                @foreach ($groups as $index => $group)
                <tbody class="stacked-item" data-kind="only-once-{{ $kind }}" @if ($index >= 10) data-more="1" @endif @if ($kind !== 'all') data-kind-hidden="1" @endif @if ($index >= 10 || $kind !== 'all') style="display: none;" @endif>
                    <tr>
                        <td class="topic-tour">{!! $topicTourWithYear($group['tour']) !!}</td>
                        <td class="count-col" rowspan="2"><span class="count-badge">{{ count($group['songs']) }}</span></td>
                    </tr>
                    <tr class="stacked-sub">
                        <td class="song-title only-once-songs">{!! implode('<br>', array_map($topicSongLink, $group['songs'])) !!}</td>
                    </tr>
                </tbody>
                @endforeach
            @endforeach
        </table>
        @include('stats._show_more', ['count' => max(count($onlyOnceLists['all']), count($onlyOnceLists['tours']))])
    </div>
</div>
<script>
(function () {
    var section = document.getElementById('topicOnlyOnceSection');
    // Show More は、今見えている一覧が10ツアーより多いときだけ出す
    var showMoreFor = function (shown) {
        var more = section.querySelector('.show-more-container');
        if (more) more.style.display = Number(section.dataset[shown === 'tours' ? 'countTours' : 'countAll']) > 10 ? '' : 'none';
    };
    showMoreFor('all');
    document.getElementById('onlyOnceToursOnly')?.addEventListener('change', function (e) {
        var shown = e.target.checked ? 'tours' : 'all';
        showMoreFor(shown);
        var expanded = !!section.querySelector('.show-more-btn.expanded');
        // 切り替えた一覧だけを出す。Show More を開いていないときは、11ツアー目からは隠したまま
        section.querySelectorAll('tbody[data-only-once-kind]').forEach(function (t) {
            var on = t.dataset.onlyOnceKind === shown;
            t.style.display = on ? '' : 'none';
            on ? t.removeAttribute('data-kind-hidden') : t.setAttribute('data-kind-hidden', '1');
            t.querySelectorAll('tr[data-more]').forEach(function (tr) { tr.style.display = on && expanded ? '' : 'none'; });
        });
        section.querySelectorAll('tbody[data-kind^="only-once-"]').forEach(function (t) {
            var on = t.dataset.kind === 'only-once-' + shown;
            on ? t.removeAttribute('data-kind-hidden') : t.setAttribute('data-kind-hidden', '1');
            t.style.display = on && (expanded || !t.dataset.more) ? '' : 'none';
        });
    });
})();
</script>
@endif
