{{-- Databaseのstats「Songs」タブのトピックス（久しぶりに演奏された曲・ご無沙汰の曲）（App\Support\ArtistTopics）。空いた期間は年数を小数で出す（1年半なら1.5） --}}
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
    // ツアーは「1992 / ツアー名」の形で、ツアー名だけをリンクにする
    $topicTourLink = fn ($tour) => '<span style="font-size: 0.85em;">' . e(substr($tour['date'], 0, 4)) . ' / <a href="' . e(route('live.show', $tour['id'])) . '" class="stats-link">' . e($tour['title']) . '</a></span>';
@endphp

@if (count($topicRevivals))
<div class="stats-section visible">
    <h2 class="section-title"><i class="fas fa-rotate-left"></i> Long-Awaited Returns
    <span class="section-title-desc">前の演奏から長い間をおいて、久しぶりに演奏された曲</span></h2>
    <div class="stats-table-container">
        <table class="stats-table">
            <thead><tr><th class="rank-col">Rank</th><th>Song Title</th><th>Tour Title</th><th class="count-col">Years</th></tr></thead>
            <tbody>
                @foreach ($topicRevivals as $index => $row)
                <tr>
                    <td class="rank-col">{!! $topicRank($topicRevivals, $index, 'years') !!}</td>
                    <td class="song-title">{!! $topicSongLink($row) !!}</td>
                    <td>{!! $topicTourLink($row['from']) !!}<br>→ {!! $topicTourLink($row['to']) !!}</td>
                    <td class="count-col"><span class="count-badge">{{ number_format($row['years'], 1) }}年</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if (count($topicDormant))
<div class="stats-section visible">
    <h2 class="section-title"><i class="fas fa-hourglass-half"></i> Long Time No Play
    <span class="section-title-desc">5ツアー以上で演奏されてきたのに、最後の演奏からいちばん時間がたっている曲</span></h2>
    <div class="stats-table-container">
        <table class="stats-table">
            <thead><tr><th class="rank-col">Rank</th><th>Song Title</th><th>Tour Title</th><th class="count-col">Years</th></tr></thead>
            <tbody>
                @foreach ($topicDormant as $index => $row)
                <tr>
                    <td class="rank-col">{!! $topicRank($topicDormant, $index, 'years') !!}</td>
                    <td class="song-title">{!! $topicSongLink($row) !!}</td>
                    <td>{!! $topicTourLink($row['last']) !!}</td>
                    <td class="count-col"><span class="count-badge">{{ number_format($row['years'], 1) }}年</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

