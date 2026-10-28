{{-- Map のタブ：都道府県ランキング（行ったライブの回数）。$prefectureStats は VenueMap::build の prefectures。都道府県を押すと地図のページでその県を開く --}}
<div class="stats-section visible">
    <h2 class="section-title">
        <i class="fas fa-map"></i> Prefectures
        <span class="section-title-desc">都道府県ごとのライブの回数</span></h2>
    <div class="stats-table-container">
        <table class="stats-table">
            <thead>
                <tr>
                    <th class="rank-col">Rank</th>
                    <th>Prefecture</th>
                    <th class="count-col">Visits</th>
                </tr>
            </thead>
            <tbody>
                @php $prefectureRows = array_values($prefectureStats); @endphp
                @forelse ($prefectureRows as $index => $prefecture)
                    @php $showRank = $index === 0 || $prefectureRows[$index - 1]['count'] !== $prefecture['count']; @endphp
                    <tr>
                        <td class="rank-col">
                            @if ($showRank)
                                @if ($index === 0)
                                    <span class="rank-badge gold">🏆</span>
                                @elseif ($index === 1)
                                    <span class="rank-badge silver">🥈</span>
                                @elseif ($index === 2)
                                    <span class="rank-badge bronze">🥉</span>
                                @else
                                    <span class="rank-number">{{ $index + 1 }}</span>
                                @endif
                            @endif
                        </td>
                        <td class="venue-name">
                            <a href="{{ $mapUrl }}{{ str_contains($mapUrl, '?') ? '&' : '?' }}pref={{ urlencode($prefecture['code']) }}" class="stats-link">{{ $prefecture['name'] }}</a>
                        </td>
                        <td class="count-col">
                            <span class="count-badge">{{ $prefecture['count'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">まだ参加したライブが記録されていません。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
