{{-- $setlistModel（DbSetlistまたはUserSetlist 1件）と $songs（該当する曲のコレクション）、
     $kind（'official' または 'user'）を受け取り、曲ごとにカード化して表示する。
     My Pageのセットリスト詳細（1パターンのみ表示）専用。
     $isFromTimeline: Timeline経由（データベース的な曲詳細へリンク）か、それ以外
     （My Stats等、自分の参加記録一覧へリンク）かで曲名の遷移先を出し分ける。 --}}
@php
    $setlist = is_array($setlistModel->setlist) ? $setlistModel->setlist : [];
    $encore = is_array($setlistModel->encore) ? $setlistModel->encore : [];
    $isFromTimeline = $isFromTimeline ?? false;
@endphp

@foreach ([['label' => null, 'items' => $setlist], ['label' => 'ENCORE', 'items' => $encore]] as $section)
    @continue(empty($section['items']))
    @if ($section['label'])
        <div class="setlist-card-section-label">{{ $section['label'] }}</div>
    @endif
    <div class="setlist-card-grid">
        @foreach ($section['items'] as $index => $data)
            @php
                $featuringType = $data['featuring_type'] ?? 'guest';
                $featuring = $data['featuring'] ?? '';
                $featuringDisplay = $featuring !== '' && $featuringType === 'artist' ? '/ ' . $featuring : $featuring;
                $alternativeTitle = $data['alternative_title'] ?? '';
                $isNumericSong = is_numeric($data['song'] ?? '');

                if ($isNumericSong) {
                    $songModel = $songs->find($data['song']);
                    $title = $alternativeTitle ?: ($songModel->title ?? 'Unknown Song');
                    if ($songModel && $isFromTimeline) {
                        $link = $kind === 'official'
                            ? route('songs.show', $songModel->id)
                            : route('mypage.user_songs.show', $songModel->id);
                    } elseif ($songModel) {
                        $link = route('mypage.attendances.index', ['song_id' => $kind . '-' . $songModel->id]);
                    } else {
                        $link = null;
                    }
                } else {
                    $title = $alternativeTitle ?: $data['song'];
                    $link = null;
                }
            @endphp
            @if ($link)
                <a href="{{ $link }}" class="setlist-card">
            @else
                <div class="setlist-card">
            @endif
                <span class="setlist-card-number">{{ $index + 1 }}</span>
                <span class="setlist-card-title">{{ $title }}</span>
                @if (!empty($featuring))
                    <span class="setlist-card-meta">{{ $featuringDisplay }}</span>
                @endif
                @if (!empty($data['daily_note']))
                    <span class="setlist-card-meta">{{ $data['daily_note'] }}</span>
                @endif
            @if ($link)
                </a>
            @else
                </div>
            @endif
        @endforeach
    </div>
@endforeach
