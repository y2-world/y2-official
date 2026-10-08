        <div class="{{ $totalOlCount >= 3 ? 'container-fluid' : 'container' }} database-year-content">
            <div class="row justify-content-center">
                <div class="{{ $colClass }} setlist">
                    @if ($setlistSummaries->count())
                        <div class="setlist-row" style="justify-content: safe center;">
                            @foreach ($summaryRows as $rowNum => $summary)
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
                                    @if ($rowTitleList->count() === 1 && $summaryRows->count() > 1)
                                        {{-- 表示するrowが複数ある場合は、rowごとのグループ名を見出しにする。 --}}
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
                                            // subtitleに改行が残っていても全行を拾えるよう、行配列をflattenする
                                            $rowPatternLabels = $tourSetlists
                                                ->filter(fn ($m) => ($m->row ?? 1) == $rowNum)
                                                ->sortBy('order_no')
                                                ->flatMap(fn ($m) => renderSubtitleWithGreyedVenues($m->subtitle ?? '')['lines'])
                                                ->filter(fn ($line) => trim(strip_tags($line)) !== '')
                                                ->map(fn ($line) => '<span class="setlist-summary-pattern-label">' . $line . '</span>')
                                                ->values();
                                        @endphp
                                        {{-- 表示するrowが複数ある場合に、グループ名が無いrowは
                                             パターンの日付・会場ラベルを見出しとして表示する。 --}}
                                        @if ($summaryRows->count() > 1 && $rowPatternLabels->count())
                                            <div class="setlist-subtitle-area">
                                                {{-- 未展開時はmax-heightで1行分だけに切り詰め、クリックで
                                                     全パターン分を折り返し表示する。1パターン目だけを別枠に
                                                     切り出さず全パターンを同じマークアップで出すことで、
                                                     未展開時から幅に収まる分だけ複数パターンが自然に見える。 --}}
                                                <h5 class="setlist-subtitle-heading setlist-subtitle-wrap setlist-subtitle-collapsible{{ $rowPatternLabels->count() === 1 ? ' setlist-summary-single-pattern-label' : '' }}"
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
                                                @php $currentEncoreBlock = null; $encoreBlockCount = $section === 'encore' ? collect($rows)->flatMap(fn ($r) => $r['variants'])->max(fn ($v) => (int) ($v['encore_block'] ?? 0)) + 1 : 1; @endphp
                                                @foreach ($rows as $row)
                                                    {{-- アンコールは行ごとのブロック（ENCORE / DOUBLE ENCORE …）が変わるところに見出しを挟む --}}
                                                    @if ($section === 'encore')
                                                        @php $rowEncoreBlock = min(array_map(fn ($v) => (int) ($v['encore_block'] ?? 0), $row['variants'] ?: [[]])); @endphp
                                                        @if ($rowEncoreBlock !== $currentEncoreBlock)
                                                            @php $currentEncoreBlock = $rowEncoreBlock; @endphp
                                                            <div style="margin: 20px 0 10px;">
                                                                <span style="color: #999; font-weight: 600; font-size: 0.9rem; letter-spacing: 2px;">{{ \App\Support\EncoreBlocks::label($rowEncoreBlock, $encoreBlockCount, $tours->artist_id ?? null) }}</span>
                                                            </div>
                                                        @endif
                                                    @endif
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
                                                        @foreach ($row['variants'] as $variant)
                                                            @php
                                                                $featuring = $variant['featuring'] ?? '';
                                                                $featuringType = $variant['featuring_type'] ?? 'guest';
                                                                $featuringDisplay = $featuring !== '' && $featuringType === 'artist'
                                                                    ? '/ ' . $featuring
                                                                    : $featuring;
                                                            @endphp
                                                            {{-- メドレーの曲は、セットリストの表示と同じく改行して「~ 曲名」。改行は曲名の箱（inline-block）の外に入れる --}}
                                                            @if (!$loop->first && !empty($variant['medley']))<br>@endif
                                                            <span class="setlist-song-featuring">
                                                                @if ($isExtraRow && $loop->first)
                                                                    <span class="setlist-extra-prefix">-&nbsp;</span>
                                                                @endif
                                                                @if (!$loop->first)
                                                                    <span class="setlist-summary-variant-separator">@if (!empty($variant['medley']))~@else/@endif</span>
                                                                @endif
                                                                @if (!($variant['is_common'] ?? true))
                                                                    <strong>
                                                                @endif
                                                                @if ($variant['song_id'])
                                                                    <a href="{{ \App\Models\DbSong::showUrl($variant['song_id'], $variant['title']) }}">{{ $variant['title'] }}</a>
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
