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
        // (row, order_no) => title のマップ。同じrow内で複数のグループタイトルを
        // order_no単位で持てるようにし、そのorder_no以降を新しいグループとして扱う。
        // グループタイトルはDbSetlist（公式データ）のみ対応。UserSetlistにはtour_id列自体がない。
        $groupTitlesByRowAndOrderNo = $tourSetlists->first() instanceof \App\Models\DbSetlist
            ? \App\Models\DbSetlistRow::where('tour_id', $tourSetlists->first()->tour_id)
                ->get()
                ->groupBy('row')
                ->map(fn($rows) => $rows->pluck('title', 'order_no'))
            : collect();
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

            // order_noにグループタイトルが設定されているパターンから、次にタイトルが
            // 設定されているパターンの手前までを1つのグループとしてまとめる。
            // .setlist-row自体は分割せず、その内側に小さな横並びラッパーを並べることで
            // 段（row）を変えずにグループの見出しだけをグループの中央に出す。
            $rowTitles = $groupTitlesByRowAndOrderNo[$rowNum] ?? collect();
            $groups = [];
            foreach ($rowSetlists->sortBy('order_no') as $setlistModel) {
                if ($rowTitles->has($setlistModel->order_no) || empty($groups)) {
                    $groups[] = ['title' => $rowTitles[$setlistModel->order_no] ?? null, 'items' => collect()];
                }
                $groups[array_key_last($groups)]['items']->push($setlistModel);
            }
            $rowHasGroupTitle = collect($groups)->contains(fn($g) => !empty($g['title']));
        @endphp
        @if ($rowHasGroupTitle)
            <h4 class="setlist-group-title-sticky"></h4>
        @endif
        <div class="setlist-row" style="justify-content: safe center;">
            @foreach ($groups as $group)
                <div class="setlist-group-wrap" data-group-title="{{ $group['title'] }}">
                    @if ($group['title'])
                        <h4 class="setlist-group-title">{{ $group['title'] }}</h4>
                    @endif
                    <div class="setlist-group-columns" style="display: flex; justify-content: safe center;">
                        @foreach ($group['items'] as $setlistModel)
                            @php
                                $setlist = is_array($setlistModel->setlist) ? $setlistModel->setlist : [];
                                $encore = is_array($setlistModel->encore) ? $setlistModel->encore : [];
                                $totalItems = count($setlist) + count($encore);
                            @endphp

                            @php
                                $subtitleRendered = renderSubtitleWithGreyedVenues($setlistModel->subtitle ?? '');
                                $subtitleRenderedLines = $subtitleRendered['lines'];
                                $patternLabel = trim(strip_tags($setlistModel->subtitle ?? '')) !== ''
                                    ? trim(strip_tags($setlistModel->subtitle))
                                    : 'パターン' . $loop->iteration;
                            @endphp
                            @if (count($setlist) || count($encore))
                                <div class="live-column-wrap" id="setlist-pattern-{{ $setlistModel->id }}" data-pattern-label="{{ $patternLabel }}">
                                    <div class="setlist-subtitle-area">
                                        @if (count($subtitleRenderedLines))
                                            @php
                                                $firstLine = $subtitleRenderedLines[0];
                                                $restLines = array_slice($subtitleRenderedLines, 1);
                                            @endphp
                                            <h5 class="setlist-subtitle-heading {{ count($restLines) ? 'setlist-subtitle-collapsible' : '' }}"
                                                @if (count($restLines)) onclick="this.classList.toggle('is-expanded')" @endif>
                                                {!! $firstLine !!}@if (count($restLines))<span class="setlist-subtitle-rest"><br>{!! implode('<br>', $restLines) !!}</span>@endif
                                            </h5>
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
                                                    <a href="{{ $link }}" @if($isUnique) style="font-weight:900;" @endif>{{ $title }}</a>
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
                                                <li @if($isUnique) style="font-weight:900;" @endif>
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
                </div>
            @endforeach
        </div>
    @endforeach
@endif
