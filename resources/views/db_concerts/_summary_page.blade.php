        <div class="{{ $totalOlCount >= 3 ? 'container-fluid' : 'container' }} database-year-content">
            <div class="row justify-content-center">
                <div class="{{ $colClass }} setlist">
                    @if ($setlistSummaries->count())
                        <div class="setlist-row" style="justify-content: safe center;">
                            @foreach ($setlistSummaries as $rowNum => $summary)
                                @php
                                    // rowが変わったら（=横並びの別グループに移ったら）曲番を1から
                                    // 数え直す。$summaryNumberはこの<ol>のスコープ内だけで完結する
                                    // 想定だが、Bladeの@phpはループをまたいで変数が残ってしまうため
                                    // 明示的にリセットする（例: tour121のRow2「スタジアム公演」が、
                                    // Row1「ドーム公演」からの続き番号になってしまっていた）。
                                    $summaryNumber = 0;
                                @endphp
                                <div class="live-column-wrap setlist-summary-wrap" style="max-width: min(450px, 80vw);">
                                    @php
                                        $rowTitleList = $summaryRowTitles[$rowNum] ?? collect();
                                    @endphp
                                    @if ($rowTitleList->count() === 1 && $setlistSummaries->count() > 1)
                                        {{-- rowが1つしかタイトルを持たず、かつこのツアーでSummary対象の
                                             rowも1つしかない場合、row同士を見比べる意味がある見出し
                                             自体が不要なため出さない。 --}}
                                        <div class="setlist-subtitle-area">
                                            <h5 class="setlist-subtitle-heading">{{ $rowTitleList->first() }}</h5>
                                        </div>
                                    @elseif ($rowTitleList->count() >= 2)
                                        {{-- rowの途中でグループタイトルが切り替わる場合（例:
                                             「アリーナ公演」→「ドーム・スタジアム公演」）、代表の
                                             1つだけを見出しにすると残りのグループの存在が分からなく
                                             なるため、全タイトルを列挙する。 --}}
                                        <div class="setlist-subtitle-area">
                                            <h5 class="setlist-subtitle-heading">{!! $rowTitleList->map(fn ($title) => e($title))->implode(' / ') !!}</h5>
                                        </div>
                                    @else
                                        {{-- rowにDbSetlistRowのグループ名が設定されていない場合、代わりに
                                             このrowに属する全パターンの日付・会場ラベル（通常表示の
                                             live-column-wrapと同じsubtitle）を一覧表示する。row見出しが
                                             無いと、Summaryだけ見てもどの公演を元にした比較表なのか
                                             分からないため。 --}}
                                        @php
                                            // 通常表示（_setlist_rows.blade.php）と同じrenderSubtitleWithGreyedVenues
                                            // で日付を太字・地名をグレー小文字にした上で、各パターンのsubtitleを
                                            // 1行ずつ連結する（1公演のsubtitle自体が複数行のことがあるため、
                                            // まずrenderSubtitleWithGreyedVenuesが返す行配列をflattenする）。
                                            // 1パターン分の日付+会場名が折り返しの途中で分断されないよう、
                                            // それぞれnowrapなspanで囲む（パターン同士の間はスペースのみ
                                            // なので、そこで折り返される）。
                                            $rowPatternLabels = $tourSetlists
                                                ->filter(fn ($m) => ($m->row ?? 1) == $rowNum)
                                                ->sortBy('order_no')
                                                ->flatMap(fn ($m) => renderSubtitleWithGreyedVenues($m->subtitle ?? '')['lines'])
                                                ->filter(fn ($line) => trim(strip_tags($line)) !== '')
                                                ->map(fn ($line) => '<span class="setlist-summary-pattern-label" style="white-space: nowrap;">' . $line . '</span>')
                                                ->values();
                                        @endphp
                                        {{-- このツアーでSummary対象のrowが1つしかない場合、row同士を
                                             見比べる意味がある見出し（rowタイトル）自体が不要なため
                                             出さない（通常表示のパターン一覧アイコンから個別公演には
                                             いつでも移動できる）。 --}}
                                        @if ($setlistSummaries->count() > 1 && $rowPatternLabels->count())
                                            <div class="setlist-subtitle-area">
                                                {{-- 未展開時はmax-heightで1行分だけに切り詰め、クリックで
                                                     全パターン分を折り返し表示する。1パターン目だけを別枠に
                                                     切り出さず全パターンを同じマークアップで出すことで、
                                                     未展開時から幅に収まる分だけ複数パターンが自然に見える。 --}}
                                                <h5 class="setlist-subtitle-heading setlist-subtitle-wrap setlist-subtitle-collapsible"
                                                    onclick="this.classList.toggle('is-expanded')">
                                                    {!! $rowPatternLabels->implode(' ') !!}<span class="setlist-subtitle-toggle" aria-hidden="true"><i class="fa-solid fa-angle-down"></i><i class="fa-solid fa-angle-up"></i></span>
                                                </h5>
                                            </div>
                                        @endif
                                    @endif
                                    {{-- 通常のセットリスト表示（_setlist_rows.blade.php）と同じく、
                                         SETLIST/ENCOREで<ol>を分けず1つに統一し、間に見出しだけを
                                         挟むことで、ENCORE側もSETLISTからの続き番号にする。 --}}
                                    <ol class="live-column">
                                        @foreach (['setlist' => $summary['setlist'], 'encore' => $summary['encore']] as $section => $rows)
                                            @if (count($rows))
                                                @if ($section === 'encore')
                                                    <div style="margin: 20px 0 10px;">
                                                        <span style="color: #999; font-weight: 600; font-size: 0.9rem; letter-spacing: 2px;">ENCORE</span>
                                                    </div>
                                                @endif
                                                @foreach ($rows as $row)
                                                    @php
                                                        // is_extra: 基準パターン（最後、または曲数最多のパターン）に
                                                        // 存在しない曲。行内の全variantsがextraの場合だけ（＝この行
                                                        // 自体が「一部の公演限定で挟まれた追加曲」）、通常の曲番を
                                                        // 振らない「-」行として表示する。行内の一部だけがextraの
                                                        // 場合（同じ日替わり位置の他の候補は基準パターンにある）は
                                                        // 通常通り曲番付きの行として扱う。
                                                        // <ol>はvalue属性を持つ<li>もカウント対象にしてしまうため、
                                                        // 「-」行にも直前の通常行と同じvalueを指定し、次の通常行の
                                                        // 番号がずれないようにする。
                                                        $isExtraRow = collect($row['variants'])->every(fn ($v) => $v['is_extra'] ?? false);
                                                        if (!$isExtraRow) {
                                                            $summaryNumber = ($summaryNumber ?? 0) + 1;
                                                        }
                                                    @endphp
                                                    @if ($isExtraRow)
                                                        <li class="live-column-extra" value="{{ $summaryNumber ?? 0 }}" style="list-style: none;">
                                                    @else
                                                        <li value="{{ $summaryNumber }}">
                                                    @endif
                                                        @if ($isExtraRow)
                                                            -
                                                        @endif
                                                        @foreach ($row['variants'] as $variant)
                                                            @php
                                                                $featuring = $variant['featuring'] ?? '';
                                                                $featuringType = $variant['featuring_type'] ?? 'guest';
                                                                $featuringDisplay = $featuring !== '' && $featuringType === 'artist'
                                                                    ? '/ ' . $featuring
                                                                    : $featuring;
                                                            @endphp
                                                            <span class="setlist-song-featuring">
                                                                @if (!$loop->first)
                                                                    <span class="setlist-summary-variant-separator">@if (!empty($variant['medley']))〜@else/@endif</span>
                                                                @endif
                                                                @if (!($variant['is_common'] ?? true))
                                                                    <strong>
                                                                @endif
                                                                @if ($variant['song_id'])
                                                                    <a href="{{ url('/database/songs', $variant['song_id']) }}">{{ $variant['title'] }}</a>
                                                                @else
                                                                    {{ $variant['title'] }}
                                                                @endif
                                                                @if (!($variant['is_common'] ?? true))
                                                                    </strong>
                                                                @endif
                                                                @if ($featuringDisplay !== '')
                                                                    <span class="setlist-featuring setlist-featuring-summary">{{ $featuringDisplay }}</span>
                                                                @endif
                                                            </span>
                                                        @endforeach
                                                    </li>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    </ol>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p>このライブにはSummaryがありません。</p>
                    @endif
                </div>
            </div>
        </div>
