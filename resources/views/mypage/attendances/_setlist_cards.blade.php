{{-- $setlistModel（DbSetlistまたはUserSetlist 1件）と $songs（該当する曲のコレクション）、
     $kind（'official' または 'user'）を受け取り、曲ごとにカード化して表示する。
     My Pageのセットリスト詳細（1パターンのみ表示）専用。
     $isFromTimeline: Timeline経由（データベース的な曲詳細へリンク）か、それ以外
     （My Stats等、自分の参加記録一覧へリンク）かで曲名の遷移先を出し分ける。 --}}
@php
    $setlist = is_array($setlistModel->setlist) ? $setlistModel->setlist : [];
    $encore = is_array($setlistModel->encore) ? $setlistModel->encore : [];
    $isFromTimeline = $isFromTimeline ?? false;
    $selectedDailySongs = $selectedDailySongs ?? [];

    // 日替わり候補グループ（直前の通常曲1つ + is_dailyが連続する曲群）に属するitemの
    // _uuidを集めておき、selected_daily_songsで選ばれなかった候補を後で除外する。
    $dailyClusterUuids = [];
    if (!empty($selectedDailySongs)) {
        foreach ([$setlist, $encore] as $sectionItems) {
            foreach (groupDailySongClusters($sectionItems) as $cluster) {
                foreach ($cluster['items'] as $clusterItem) {
                    if (isset($clusterItem['_uuid'])) {
                        $dailyClusterUuids[] = $clusterItem['_uuid'];
                    }
                }
            }
        }
    }
@endphp

@foreach ([['label' => null, 'items' => $setlist], ['label' => 'ENCORE', 'items' => $encore]] as $section)
    @continue(empty($section['items']))
    @if ($section['label'])
        <div class="setlist-card-section-label">{{ $section['label'] }}</div>
    @endif
    <div class="setlist-card-grid">
        @php $number = 0; @endphp
        @foreach ($section['items'] as $data)
            @php
                $itemUuid = $data['_uuid'] ?? null;
                $isInDailyCluster = in_array($itemUuid, $dailyClusterUuids, true);

                // 日替わり候補グループに属するitemは、選ばれた1曲だけ通常曲として表示し、
                // 選ばれなかった候補（1曲目の通常曲を含む）はカード自体を出さない。
                if ($isInDailyCluster) {
                    if (!in_array($itemUuid, $selectedDailySongs, true)) {
                        continue;
                    }
                }

                // selected_daily_songsが空（未選択のまま保存された既存データ等）の場合は
                // 選択肢を絞り込めないため、従来通りis_dailyの曲を「番号なし」で表示する。
                $isDaily = empty($selectedDailySongs) && !empty($data['is_daily']);
                $isMedley = !empty($data['medley']);
                $isSkipped = $isDaily || $isMedley;
                if (!$isSkipped) {
                    $number++;
                }

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
                <span class="setlist-card-number" @if ($isSkipped) style="visibility: hidden;" @endif>{{ $isSkipped ? '' : $number }}</span>
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
