{{-- 参加した公演のページの Timeline のタブ。その公演で演奏した曲を、発売年（シングル・アルバムでいちばん早く出た日の年）ごとに新しい年から並べた年表。
     まだ発売されていない曲は 'Unreleased' でいちばん上。見た目は stats の First Listens by Year と同じ（曲は1曲ずつ改行）。
     $releaseYears：ArtistTopics::attendanceSummary の releaseYears。$liveStatsSongUrl（任意）：曲の id => 曲のページの URL（渡さなければ Database の曲のページ） --}}
@php
    $liveStatsSongUrl ??= fn ($id) => url('/database/songs/' . $id);
    // 曲名はその公演の表記。別表記のときは、曲のページもその表記で絞り込んで開く（stats のトピックスと同じ）
    $timelineSongLink = function ($row) use ($liveStatsSongUrl) {
        $href = $liveStatsSongUrl($row['song_id']);
        if (!empty($row['version'])) {
            $href .= (str_contains($href, '?') ? '&' : '?') . http_build_query(['title' => $row['version']]);
        }
        return '<a href="' . e($href) . '" class="stats-link">' . e($row['title']) . '</a>';
    };
@endphp
@if (!empty($releaseYears))
<div class="live-stats">
<div class="stats-section visible">
    <h2 class="section-title"><i class="fas fa-compact-disc"></i> Release Timeline
    <span class="section-title-desc">この公演で演奏した曲の発売年</span></h2>
    <div class="stats-table-container">
        <table class="stats-table">
            <thead><tr><th style="width: 80px; white-space: nowrap;">Year</th><th>Songs</th><th class="count-col">Count</th></tr></thead>
            <tbody>
                @foreach ($releaseYears as $year => $songsOfYear)
                <tr>
                    <td style="white-space: nowrap;">{{ $year }}</td>
                    <td class="song-title" style="line-height: 1.8;">@foreach ($songsOfYear as $song)<div>{!! $timelineSongLink($song) !!}</div>@endforeach</td>
                    <td class="count-col"><span class="count-badge">{{ count($songsOfYear) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
</div>
@endif
