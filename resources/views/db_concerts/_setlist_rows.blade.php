{{-- $tourSetlists（DbSetlistのコレクション）と $songs（全DbSongのコレクション）を受け取り、
     row でグルーピングした複数パターンのセットリストを横並びで描画する。
     db_concerts/show.blade.php と mypage/attendances/show.blade.php から共通で利用する。
     曲名のリンク先は既定でdatabase側の曲詳細だが、$songLinkResolver（fn($songModel) => string|null）を
     渡すと呼び出し元でリンク先を差し替えられる（mypageでは自分の参加履歴一覧へ誘導するため）。 --}}
@php
    $songLinkResolver = $songLinkResolver ?? null;
@endphp
@if ($tourSetlists->count())
    @php
        $setlistsByRow = $tourSetlists->groupBy(fn($m) => $m->row ?? 1)->sortKeys();
    @endphp
    @foreach ($setlistsByRow as $rowNum => $rowSetlists)
        @php
            // この行（同時に見比べる列同士）に共通する曲IDを計算
            $commonSongs = null;
            if ($rowSetlists->count() >= 2) {
                foreach ($rowSetlists as $setlistModel) {
                    $allSongs = array_merge(
                        is_array($setlistModel->setlist) ? $setlistModel->setlist : [],
                        is_array($setlistModel->encore) ? $setlistModel->encore : []
                    );
                    $songIds = array_map(fn($s) => $s['song'] ?? '', $allSongs);
                    $songIds = array_filter($songIds, fn($s) => $s !== '');
                    if ($commonSongs === null) {
                        $commonSongs = array_flip($songIds);
                    } else {
                        $commonSongs = array_intersect_key($commonSongs, array_flip($songIds));
                    }
                }
                $commonSongs = array_keys($commonSongs ?? []);
            }
        @endphp
        <div class="setlist-row" style="justify-content: safe center;">
            @foreach ($rowSetlists as $setlistModel)
                @php
                    $setlist = is_array($setlistModel->setlist) ? $setlistModel->setlist : [];
                    $encore = is_array($setlistModel->encore) ? $setlistModel->encore : [];
                    $totalItems = count($setlist) + count($encore);
                @endphp

                @php
                    $subtitleRendered = renderSubtitleWithGreyedVenues($setlistModel->subtitle ?? '');
                    $subtitleRenderedLines = $subtitleRendered['lines'];
                    $subtitleFontSize = $subtitleRendered['font_size'];
                    $patternLabel = trim(strip_tags($setlistModel->subtitle ?? '')) !== ''
                        ? trim(strip_tags($setlistModel->subtitle))
                        : 'パターン' . $loop->iteration;
                @endphp
                @if (count($setlist) || count($encore))
                    <div class="live-column-wrap" id="setlist-pattern-{{ $setlistModel->id }}" data-pattern-label="{{ $patternLabel }}">
                        <div class="setlist-subtitle-area">
                            @if (count($subtitleRenderedLines))
                                <h5 style="margin: 0.25rem;">@if ($subtitleFontSize)<span style="font-size: {{ $subtitleFontSize }};">{!! implode('<br>', $subtitleRenderedLines) !!}</span>@else{!! implode('<br>', $subtitleRenderedLines) !!}@endif</h5>
                            @endif
                        </div>
                    <ol class="live-column {{ $totalItems >= 20 ? 'live-column-two-col' : '' }}">
                        @foreach ([$setlist, $encore] as $section)
                            @if ($loop->index === 1 && count($encore))
                                <div style="margin: 20px 0 0 0;">
                                    <span style="color: #999; font-weight: 600; font-size: 0.9rem; letter-spacing: 2px;">ENCORE</span>
                                </div>
                            @endif

                            @foreach ($section as $data)
                                @php
                                    $isDaily = isset($data['is_daily']) && $data['is_daily'];
                                    $isMedley = isset($data['medley']) && $data['medley'];
                                    $isInline = $isDaily || $isMedley;
                                    $dailyNote = isset($data['daily_note']) ? $data['daily_note'] : '';
                                    $featuringType = $data['featuring_type'] ?? 'guest';
                                    $featuring = isset($data['featuring']) ? $data['featuring'] : '';
                                    $featuringDisplay = $featuring !== '' && $featuringType === 'artist' ? '/ ' . $featuring : $featuring;
                                    $alternativeTitle = isset($data['alternative_title']) ? $data['alternative_title'] : '';
                                    // is_numericだけでは、"20180908"のような数字だけの曲名（DbSongとして
                                    // 登録せず生文字列のまま保存された曲）を誤ってDbSong.idの参照と
                                    // 解釈してしまい、該当id不在でUnknown Songになる。数字かどうかではなく、
                                    // 実際にそのidのDbSongが存在するかどうかで判定する。
                                    $songModel = is_numeric($data['song'] ?? '') ? $songs->find($data['song']) : null;
                                    $title = '';
                                    $link = null;
                                    $isUnique = $commonSongs !== null && !in_array($data['song'] ?? '', $commonSongs);

                                    if ($songModel) {
                                        $title = !empty($alternativeTitle) ? $alternativeTitle : $songModel->title;
                                        $link = $songLinkResolver ? $songLinkResolver($songModel) : url('/database/songs', $data['song']);
                                    } else {
                                        $title = !empty($alternativeTitle) ? $alternativeTitle : $data['song'];
                                        $link = null;
                                    }
                                @endphp

                                @if ($isInline)
                                    -
                                    @if ($link)
                                        <a href="{{ $link }}" @if($isUnique) style="font-weight:bold;" @endif>{{ $title }}</a>
                                    @else
                                        @if($isUnique)<strong>{{ $title }}</strong>@else{{ $title }}@endif
                                    @endif
                                    @if(!empty($featuring))
                                        <span style="color:#999;font-size:0.75em;">{{ $featuringDisplay }}</span>
                                    @endif
                                    @if(!empty($dailyNote))
                                        <span style="color:#999;font-size:0.75em;">{{ $dailyNote }}</span>
                                    @endif
                                    <br>
                                @else
                                    <li @if($isUnique) style="font-weight:bold;" @endif>
                                        @if ($link)
                                            <a href="{{ $link }}">{{ $title }}</a>
                                        @else
                                            {{ $title }}
                                        @endif
                                        @if(!empty($featuring))
                                            <span style="color:#999;font-size:0.75em;">{{ $featuringDisplay }}</span>
                                        @endif
                                        @if(!empty($dailyNote))
                                            <span style="color:#999;font-size:0.75em;">{{ $dailyNote }}</span>
                                        @endif
                                    </li>
                                @endif
                            @endforeach
                        @endforeach
                    </ol>
                    </div>
                @endif
            @endforeach
        </div>
    @endforeach
@endif
