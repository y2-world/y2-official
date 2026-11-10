{{-- Databaseのstats「Setlist」タブのトピックス（本編ラスト曲）（App\Support\ArtistTopics） --}}
@php
    // 曲のリンク先は $topicSongUrl（ID => URL）で変えられる（マイページで作ったアーティストではマイページのページへ）。渡さなければ Database
    $topicSongUrl ??= fn ($id) => url('/database/songs/' . $id);
    $topicSongLink = fn ($row) => '<a href="' . e($topicSongUrl($row['song_id'])) . '" class="stats-link">' . e($row['title']) . '</a>';
@endphp
@if (count($topicClosingSongs))
<div class="stats-section visible">
    <h2 class="section-title"><i class="fas fa-flag-checkered"></i> Most Used Closing Songs
    <span class="section-title-desc" data-count-desc-tours="本編の最後に演奏されたツアー・ライブの数が多い曲" data-count-desc-shows="本編の最後に演奏された公演の数が多い曲">本編の最後に{{ !empty($byShows) ? '演奏された公演の数が多い曲' : '演奏されたツアー・ライブの数が多い曲' }}</span></h2>
    <div class="stats-table-container">
        {{-- Database の stats では、公演数統計の切り替えで表を描き直す（id）。順位の下にもう一方の数え方での順位（rank・other_rank があるときだけ） --}}
        <table class="stats-table" id="dbClosingStatsTable">
            <thead><tr><th class="rank-col">Rank</th><th>Song Title</th><th class="count-col">Times</th></tr></thead>
            <tbody>
                @foreach ($topicClosingSongs as $index => $row)
                @php $showRank = $index === 0 || $topicClosingSongs[$index - 1]['count'] !== $row['count']; @endphp
                <tr @if ($index >= 10) data-more="1" style="display: none;" @endif>
                    <td class="rank-col">
                        @if ($showRank)
                            @if ($index === 0) <span class="rank-badge gold">🏆</span>
                            @elseif ($index === 1) <span class="rank-badge silver">🥈</span>
                            @elseif ($index === 2) <span class="rank-badge bronze">🥉</span>
                            @else <span class="rank-number">{{ $index + 1 }}</span>
                            @endif
                        @endif
                        @include('stats._other_rank', ['song' => $row])
                    </td>
                    <td class="song-title">{!! $topicSongLink($row) !!}</td>
                    <td class="count-col"><span class="count-badge">{{ $row['count'] }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @include('stats._show_more', ['count' => count($topicClosingSongs)])
    </div>
</div>
@endif
