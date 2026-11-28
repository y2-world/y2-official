{{-- 参加した公演の stats のうち、曲名だけの一覧（Live Debuts）。stats のトピックスと同じ見た目（曲名の1列だけなので、スマホも PC と同じ表。最初は $shown 曲、そのあとは Show More）。
     必要な変数：$section（rows・icon・title・desc）、$topicSongLink（行 => 曲名のリンクの HTML）、$shown（最初に出す曲数）。曲が無ければ出さない --}}
@if (count($section['rows']))
<div class="stats-section visible">
    <h2 class="section-title"><i class="fas {{ $section['icon'] }}"></i> {{ $section['title'] }}
    <span class="section-title-desc">{{ $section['desc'] }}</span></h2>
    <div class="stats-table-container">
        <table class="stats-table">
            <thead><tr><th>Song Title</th></tr></thead>
            <tbody>
                @foreach ($section['rows'] as $index => $row)
                <tr @if ($index >= $shown) data-more="1" style="display: none;" @endif>
                    <td class="song-title">{!! $topicSongLink($row) !!}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @include('stats._show_more', ['count' => count($section['rows']), 'limit' => $shown])
    </div>
</div>
@endif
