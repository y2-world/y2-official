{{-- $setlistModel（DbSetlist 1件）と $songs（全DbSongのコレクション）を受け取り、
     曲ごとにカード化して表示する。My Pageの参加記録詳細（1パターンのみ表示）専用。 --}}
@php
    $setlist = is_array($setlistModel->setlist) ? $setlistModel->setlist : [];
    $encore = is_array($setlistModel->encore) ? $setlistModel->encore : [];
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
                    $link = $songModel ? route('mypage.attendances.index', ['song_id' => $songModel->id]) : null;
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
