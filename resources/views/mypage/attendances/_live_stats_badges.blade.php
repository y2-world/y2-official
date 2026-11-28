{{-- 参加した公演・ライブの stats のうち、曲名と右端のバッジの2列の表（スマホも同じ表）。Database のライブのページの Late Live Debuts・Not Played Since で使う。
     必要な変数：$section（rows・icon・title・desc・label（バッジの列の見出し）・badge（行 => バッジの文字）・rankKey（順位を付ける値。null なら順位なし）・detail（任意。行 => 曲名の下の小さい文字））、
     $topicSongLink・$topicRank（_live_stats の）、$shown（最初に出す曲数）。曲が無ければ出さない --}}
@if (count($section['rows']))
<div class="stats-section visible">
    <h2 class="section-title"><i class="fas {{ $section['icon'] }}"></i> {{ $section['title'] }}
    <span class="section-title-desc">{{ $section['desc'] }}</span></h2>
    <div class="stats-table-container">
        <table class="stats-table">
            <thead><tr>@if ($section['rankKey'])<th class="rank-col">Rank</th>@endif<th>Song Title</th><th class="count-col">{{ $section['label'] }}</th></tr></thead>
            <tbody>
                @foreach ($section['rows'] as $index => $row)
                <tr @if ($index >= $shown) data-more="1" style="display: none;" @endif>
                    @if ($section['rankKey'])<td class="rank-col">{!! $topicRank($section['rows'], $index, $section['rankKey']) !!}</td>@endif
                    <td class="song-title">{!! $topicSongLink($row) !!}@if (!empty($section['detail']))<div style="font-size: 0.8em; color: #888;">{{ ($section['detail'])($row) }}</div>@endif</td>
                    <td class="count-col"><span class="count-badge">{{ ($section['badge'])($row) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @include('stats._show_more', ['count' => count($section['rows']), 'limit' => $shown])
    </div>
</div>
@endif
