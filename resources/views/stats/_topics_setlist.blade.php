{{-- Databaseのstats「Setlist」タブのトピックス（本編ラスト曲）（App\Support\ArtistTopics） --}}
@php
    $topicSongLink = fn ($row) => '<a href="' . e(url('/database/songs/' . $row['song_id'])) . '" class="stats-link">' . e($row['title']) . '</a>';
@endphp
@if (count($topicClosingSongs))
<div class="stats-section visible">
    <h2 class="section-title"><i class="fas fa-flag-checkered"></i> Most Used Closing Songs
    <span class="section-title-desc">本編の最後に演奏されたツアーの数が多い曲</span></h2>
    <div class="stats-table-container">
        <table class="stats-table">
            <thead><tr><th class="rank-col">Rank</th><th>Song Title</th><th class="count-col">Times Performed</th></tr></thead>
            <tbody>
                @foreach ($topicClosingSongs as $index => $row)
                @php $showRank = $index === 0 || $topicClosingSongs[$index - 1]['count'] !== $row['count']; @endphp
                <tr>
                    <td class="rank-col">
                        @if ($showRank)
                            @if ($index === 0) <span class="rank-badge gold">🏆</span>
                            @elseif ($index === 1) <span class="rank-badge silver">🥈</span>
                            @elseif ($index === 2) <span class="rank-badge bronze">🥉</span>
                            @else <span class="rank-number">{{ $index + 1 }}</span>
                            @endif
                        @endif
                    </td>
                    <td class="song-title">{!! $topicSongLink($row) !!}</td>
                    <td class="count-col"><span class="count-badge">{{ $row['count'] }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
