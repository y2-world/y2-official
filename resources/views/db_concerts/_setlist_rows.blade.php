{{-- $tourSetlists（DbSetlistのコレクション）と $songs（全DbSongのコレクション）を受け取り、
     row でグルーピングした複数パターンのセットリストを横並びで描画する。
     db_concerts/show.blade.php と mypage/attendances/show.blade.php から共通で利用する。 --}}
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
                    // 行ごとに処理: 「数字.数字」を含む日付らしいパターン（範囲・カンマ区切り可）を
                    // 行の中から繰り返し検出し、日付と日付の間に挟まる部分を会場名としてグレー表示にする。
                    // 例:「7.17 福井1」→ 日付「7.17」+ 会場名「福井1」
                    // 「7.17 東京1 7.18東京2」→ 日付「7.17」+ 会場名「東京1」+ 日付「7.18」+ 会場名「東京2」
                    // 日付らしいパターンが1つも無い行（「A」「ホール公演」など）はそのまま。
                    $subtitleLines = preg_split('/\r\n|\r|\n/', trim($setlistModel->subtitle ?? ''));
                    $subtitleLines = array_values(array_filter($subtitleLines, fn($line) => trim($line) !== ''));
                    // 末尾の「-」だけで終わる開催期間未定表記（例:「6.13-」）も日付として扱う
                    $subtitleDatePattern = '/(\d{1,2}\.\d{1,2}(?:[-]\s*\d{1,2}(?:\.\d{1,2})?)*-?)/u';
                    $subtitleLineResults = array_map(function ($line) use ($subtitleDatePattern) {
                        $line = trim($line);
                        $segments = preg_split($subtitleDatePattern, $line, -1, PREG_SPLIT_DELIM_CAPTURE);

                        if (count($segments) <= 1) {
                            return ['html' => e($line), 'hasVenue' => false];
                        }

                        $html = '';
                        $hasVenue = false;
                        foreach ($segments as $i => $segment) {
                            if ($i % 2 === 1) {
                                // 奇数インデックス = 日付本体
                                $html .= e($segment);
                                continue;
                            }

                            $venue = trim($segment);
                            if ($venue === '') {
                                continue;
                            }

                            if ($i === 0) {
                                // 最初の日付より前の文字列はラベル扱いでそのまま
                                $html .= e($venue);
                            } else {
                                // 会場名は見出しサイズに対する相対値(em)。見出し自体が行数で縮小されると連動して縮む
                                $html .= '<span style="font-size: 0.5em; color: #999; font-weight: normal; margin-left: 3px;">' . e($venue) . '</span>';
                                $hasVenue = true;
                                if (isset($segments[$i + 1])) {
                                    $html .= ' ';
                                }
                            }
                        }

                        return ['html' => $html, 'hasVenue' => $hasVenue];
                    }, $subtitleLines);
                    $subtitleRenderedLines = array_column($subtitleLineResults, 'html');

                    // 「日付+会場」がある行の数が増えるほど見出し全体（日付・会場名とも）を段階的に縮小する
                    $subtitleLineCount = count(array_filter($subtitleLineResults, fn($r) => $r['hasVenue']));
                    $subtitleFontSize = match (true) {
                        $subtitleLineCount >= 4 => '0.75em',
                        $subtitleLineCount >= 3 => '0.95em',
                        default => null,
                    };
                @endphp
                @if (count($setlist) || count($encore))
                    <div class="live-column-wrap">
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
                                    $dailyNote = isset($data['daily_note']) ? $data['daily_note'] : '';
                                    $featuringType = $data['featuring_type'] ?? 'guest';
                                    $featuring = isset($data['featuring']) ? $data['featuring'] : '';
                                    $featuringDisplay = $featuring !== '' && $featuringType === 'artist' ? '/ ' . $featuring : $featuring;
                                    $alternativeTitle = isset($data['alternative_title']) ? $data['alternative_title'] : '';
                                    $isNumericSong = is_numeric($data['song'] ?? '');
                                    $title = '';
                                    $link = null;
                                    $isUnique = $commonSongs !== null && !in_array($data['song'] ?? '', $commonSongs);

                                    if ($isNumericSong) {
                                        $songModel = $songs->find($data['song']);
                                        $title = !empty($alternativeTitle) ? $alternativeTitle : ($songModel->title ?? 'Unknown Song');
                                        $link = $songModel
                                            ? url('/database/songs', $data['song'])
                                            : null;
                                    } else {
                                        $title = !empty($alternativeTitle) ? $alternativeTitle : $data['song'];
                                        $link = null;
                                    }
                                @endphp

                                @if ($isDaily)
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
