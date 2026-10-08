@extends('layouts.app')
@section('title', 'Yuki Official - ' . $tours->title)

@section('og_title', $tours->title . ' - Yuki Official')
@section('og_description', 'Tour: ' . $tours->title . ($tours->date1 ? ' (' . date('Y', strtotime($tours->date1)) . ')' : ''))
@section('og_type', 'article')

@section('content')
    @php
        // Previous/Nextで移動してもtype絞り込み一覧の範囲・Summary専用表示を
        // 維持できるよう、現在のfrom/tabをクエリとして引き継ぐ
        // Summaryで見ている間は、Summaryの無いライブを通っても ?tab=summary を引き継ぐ
        // （Summaryの無いライブは通常の表示で開き、その先にSummaryがあればまたSummaryで開く）
        $normalPrevNextQuery = !empty($from) ? '?from=' . $from : '';
        $previousQuery = $nextQuery = !empty($summaryMode)
            ? '?tab=summary' . (!empty($from) ? '&from=' . urlencode($from) : '')
            : $normalPrevNextQuery;
        $standardViewQuery = '?view=standard' . (!empty($from) ? '&from=' . urlencode($from) : '');

        // 関数の重複定義を防ぐためにチェック
        if (!function_exists('getTotalOlCount')) {
            function getTotalOlCount($tourSetlists)
            {
                $totalOlCount = 0;

                foreach ($tourSetlists as $setlist) {
                    if (!empty($setlist)) {
                        foreach ($setlist as $data) {
                            $totalOlCount++;
                            $hasDate = true;
                        }
                    }
                }

                return $totalOlCount;
            }
        }

        // 各セットリストの <ol> 数を集計
        $totalOlCount = $tourSetlists
            ->filter(function ($model) {
                return is_array($model->setlist) && count($model->setlist) > 0;
            })
            ->count();

        // レイアウト用クラスを調整
        $colClass = $totalOlCount <= 2 ? 'col-xl-9' : 'col-xl-12';
    @endphp

    <div class="database-hero database-hero--detail">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
            ['label' => 'Database', 'url' => '/database'],
            ['label' => $artist->name, 'url' => route('database.artist', $artist->id)],
            ['label' => 'Live', 'url' => route('database.live', $artist->id)],
            ['label' => $tours->title],
        ]])
            @if ((int) $tours->type !== 4)
                <p class="database-subtitle" style="">
                    <a href="{{ route('database.artist', $artist->id) }}">{{ $artist->name }}</a>
                </p>
            @endif
            <h1 class="database-title" style="">{{ $tours->title }}</h1>
            <p class="database-subtitle" style="">
                @if (isset($tours->date1) && isset($tours->date2))
                    {{ date('Y.m.d', strtotime($tours->date1)) }} - {{ date('Y.m.d', strtotime($tours->date2)) }}
                @elseif(isset($tours->date1) && !isset($tours->date2))
                    {{ date('Y.m.d', strtotime($tours->date1)) }}
                @endif
                <br>
                {{ $tours->venue }}
            </p>

            @if ($totalOlCount >= 2)
                {{-- パターン一覧アイコン（SP表示のみ、見出しブロックの右下）：ネイティブのselectを重ねて、タップするとOS標準の選択メニューが開く --}}
                <div class="sp" style="position: absolute; bottom: -14px; right: 8px; width: 36px; height: 36px;">
                    <div style="pointer-events: none; background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-bars" style="font-size: 14px;"></i>
                    </div>
                    <select id="spPatternListSelect" style="position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; border: none; cursor: pointer;">
                        <option value="" selected disabled>パターンを選択</option>
                    </select>
                </div>
                {{-- パターン一覧アイコン（PC表示）：横スクロールが発生している時だけJSで表示する --}}
                <div id="pcPatternListIconWrap" class="pc" style="display: none; position: absolute; bottom: -14px; right: 8px; width: 36px; height: 36px;">
                    <div style="pointer-events: none; background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; border-radius: 50%; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-bars" style="font-size: 14px;"></i>
                    </div>
                    <select id="pcPatternListSelect" style="position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; border: none; cursor: pointer;">
                        <option value="" selected disabled>パターンを選択</option>
                    </select>
                </div>
            @endif
        </div>
    </div>

    @if (($tab ?? null) === 'summary')
        {{-- Live一覧の「Summary」タブから遷移してきた場合、ページ本文には
             Summarizeの内容をそのままテキスト表示する。ただし、タイトル右の
             パターン一覧アイコン（fa-bars、見出し内で先に描画済み）はDOM上の
             .live-column-wrap[data-pattern-label]を探して選択肢を作るため、
             通常のセットリスト自体も非表示のまま埋め込んでおき、アイコンから
             引き続きパターンジャンプ・Summarize呼び出しを使えるようにする。 --}}
        <div class="setlist" style="display: none;">
            @include('db_concerts._setlist_rows', ['tourSetlists' => $tourSetlists, 'songs' => $songs])
        </div>
        @include('db_concerts._summary_page')
    @else
        <div class="setlist-standard-page{{ $totalOlCount === 1 ? ' is-single-pattern' : '' }}{{ isset($setlistSummaries) && $setlistSummaries->count() ? ' has-responsive-summary' : '' }}{{ request()->query('view') === 'standard' ? ' is-mobile-selected' : '' }}">
        <div class="{{ $totalOlCount >= 3 ? 'container-fluid' : 'container' }} database-year-content">
            <div class="row justify-content-center">
                <div class="{{ $colClass }}">
                    <div class="setlist" style="width: 100%;">
                        @include('db_concerts._setlist_rows', ['tourSetlists' => $tourSetlists, 'songs' => $songs])
                    </div>
                </div>
            </div>
        </div>
        </div>

        @if (isset($setlistSummaries) && $setlistSummaries->count())
            <div class="setlist-responsive-summary-page{{ request()->query('view') === 'standard' ? ' is-mobile-hidden' : '' }}">
                @include('db_concerts._summary_page')
            </div>
        @endif

        @if (isset($setlistSummaries) && $setlistSummaries->count())
            <div id="setlistSummaryOverlay" style="display: none; position: fixed; inset: 0; background: rgba(20,22,30,0.5); z-index: 1050; align-items: center; justify-content: center;">
                @foreach ($setlistSummaries as $rowNum => $summary)
                    @php $summaryNumber = 0; @endphp
                    <div class="setlist setlist-summary-popup" data-summary-row="{{ $rowNum }}" style="display: none; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); padding: 30px; max-width: 560px; width: calc(100% - 32px); max-height: 80vh; overflow-y: auto; position: relative;">
                        <button type="button" class="setlist-summary-close" style="position: absolute; top: 16px; right: 16px; border: none; background: #f0f1f6; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; color: #718096; font-size: 16px; line-height: 1; flex-shrink: 0;">&times;</button>
                        <h3 style="margin: 0 44px 20px 0; font-size: 18px;">{{ $tours->title }}</h3>
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
                                                {{-- メドレーの曲は改行せず「~」でつなぐ（セットリストの表示とは違い、Summary は改行しない） --}}
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
        @endif
    @endif
    <div class="container database-year-content" style="padding-top: 0;">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                <div class="schedule-text">
                    <!-- Additional content -->
                    @if (!is_null($tours->schedule))
                        @if ($tourSetlists->count())
                            <hr>
                        @endif
                        <h5>SCHEDULE</h5>
                        {!! nl2br(e($tours->schedule)) !!}
                    @endif
                    @if (!is_null($tours->text))
                        <hr>
                        {!! nl2br(e($tours->text)) !!}
                    @endif
                </div>
                {{-- 前後リンク --}}
                <div style="display: flex; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
                    @if (isset($previous))
                        <a href="{{ route('live.show', $previous->id) }}{{ $previousQuery }}" rel="prev"
                           style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                            <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                            Previous
                        </a>
                    @else
                        <div></div>
                    @endif
                    @if (isset($next))
                        <a href="{{ route('live.show', $next->id) }}{{ $nextQuery }}" rel="next"
                           style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                            Next
                            <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>



@section('page-script')
<script>
// Summaryページからのハッシュ付き遷移（#setlist-pattern-ID）で、ブラウザ標準の
// スクロール位置復元・アンカージャンプがJSでの正確な位置計算より後から効いて
// しまい、タイトルや1曲目が隠れる位置までずれてしまう。スクリプト読み込み直後
// （DOMContentLoadedより前）にscrollRestorationを明示的にmanualへ切り替え、
// ブラウザ自身による自動スクロール調整を止める。
if ('scrollRestoration' in history) {
    history.scrollRestoration = 'manual';
}
document.addEventListener('DOMContentLoaded', function () {
    function updateSongFeaturingWrap() {
        var isSinglePatternOnDesktop = window.innerWidth > 767
            && {{ $totalOlCount === 1 ? 'true' : 'false' }};

        document.querySelectorAll('.setlist-song-featuring').forEach(function (item) {
            item.classList.remove('is-feature-wrap');
            if (isSinglePatternOnDesktop) {
                return;
            }
            if (!item.querySelector('.setlist-featuring')) {
                return;
            }

            var measure = item.cloneNode(true);
            measure.style.cssText = 'position:fixed;left:-10000px;top:0;display:inline-block;width:max-content;max-width:none;white-space:nowrap;visibility:hidden;';
            measure.querySelectorAll('.setlist-featuring').forEach(function (featuring) {
                featuring.style.cssText += ';display:inline;max-width:none;white-space:nowrap;';
            });
            document.body.appendChild(measure);
            var width = measure.getBoundingClientRect().width;
            measure.remove();

            if (width > 350) {
                item.classList.add('is-feature-wrap');
            }
        });
    }

    function updatePatternSubtitleAccordions() {
        document.querySelectorAll('.setlist-pattern-wrap .setlist-subtitle-auto').forEach(function (heading) {
            heading.classList.remove('setlist-subtitle-collapsible', 'is-expanded');

            // 改行位置を固定せず出力した見出し自身の実高さを見る。
            // max-heightを外した状態で1行分より十分高ければ、実際に折り返している。
            var style = window.getComputedStyle(heading);
            var lineHeight = parseFloat(style.lineHeight);
            if (!Number.isFinite(lineHeight)) {
                lineHeight = parseFloat(style.fontSize) * 1.2;
            }
            var wrapsNaturally = heading.scrollHeight > lineHeight * 1.5;
            if (wrapsNaturally) {
                heading.classList.add('setlist-subtitle-collapsible');
                heading.onclick = function () {
                    heading.classList.toggle('is-expanded');
                };
            } else {
                heading.onclick = null;
            }
        });
    }

    updateSongFeaturingWrap();
    updatePatternSubtitleAccordions();
    window.addEventListener('resize', updateSongFeaturingWrap);
    window.addEventListener('resize', updatePatternSubtitleAccordions);
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(function () {
            updateSongFeaturingWrap();
            updatePatternSubtitleAccordions();
        });
    }

    // Summaryタイトルはフォント読込後・画面幅変更後にも実際の高さを比較し、
    // 複数行が切り詰められる場合だけアコーディオンを有効にする。
    function updateSummarySubtitleAccordions() {
        document.querySelectorAll('.setlist-subtitle-wrap').forEach(function (el) {
            el.classList.add('setlist-subtitle-collapsible');
            el.classList.remove('is-expanded');
            var clampedHeight = el.getBoundingClientRect().height;
            el.classList.add('is-expanded');
            var fullHeight = el.getBoundingClientRect().height;
            el.classList.remove('is-expanded');

            if (fullHeight > clampedHeight + 1) {
                el.onclick = function () {
                    el.classList.toggle('is-expanded');
                };
            } else {
                el.classList.remove('setlist-subtitle-collapsible');
                el.onclick = null;
            }
        });
    }

    updateSummarySubtitleAccordions();
    window.addEventListener('resize', updateSummarySubtitleAccordions);
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(updateSummarySubtitleAccordions);
    }
    document.querySelectorAll('.setlist-row').forEach(function (row) {
        if (row.scrollWidth > row.clientWidth) {
            row.style.justifyContent = 'flex-start';
        }
        var areas = row.querySelectorAll('.setlist-subtitle-area');
        var recalcSubtitleHeights = function () {
            var maxH = 0;
            areas.forEach(function (a) { a.style.height = 'auto'; maxH = Math.max(maxH, a.scrollHeight); });
            areas.forEach(function (a) { a.style.height = maxH + 'px'; });
        };
        recalcSubtitleHeights();

        // 日付・地名を1行目のみ表示している見出しを展開/折りたたみしたら、
        // カード間で揃えている高さも再計算する
        row.querySelectorAll('.setlist-subtitle-collapsible').forEach(function (heading) {
            heading.addEventListener('click', function () {
                recalcSubtitleHeights();
            });
        });

        // 横スクロール中、画面中央に見えているグループのタイトルを追従表示する
        // （PC幅では各グループの見出し(h4)を固定表示するので不要。モバイル幅のみ使う）
        var stickyTitle = row.previousElementSibling;
        if (window.innerWidth <= 767 && stickyTitle && stickyTitle.classList.contains('setlist-group-title-sticky')) {
            var groupWraps = row.querySelectorAll('.setlist-group-wrap[data-group-title]');
            var updateStickyTitle = function () {
                var rowRect = row.getBoundingClientRect();
                var centerX = rowRect.left + rowRect.width / 2;
                var currentTitle = '';
                groupWraps.forEach(function (wrap) {
                    var rect = wrap.getBoundingClientRect();
                    if (rect.left <= centerX && rect.right >= centerX) {
                        currentTitle = wrap.getAttribute('data-group-title') || '';
                    }
                });
                if (!currentTitle && groupWraps.length) {
                    currentTitle = groupWraps[0].getAttribute('data-group-title') || '';
                }
                stickyTitle.textContent = currentTitle;
            };
            updateStickyTitle();
            row.addEventListener('scroll', updateStickyTitle, { passive: true });
        }
    });

    function setupPatternSelect(selectId) {
        var patternSelect = document.getElementById(selectId);
        if (!patternSelect) {
            return;
        }
        var wraps = document.querySelectorAll('.live-column-wrap[data-pattern-label]');
        var summaryRowNums = Array.prototype.map.call(
            document.querySelectorAll('.setlist-summary-popup[data-summary-row]'),
            function (popup) { return popup.getAttribute('data-summary-row'); }
        );

        var rowNums = [];
        var groupTitlesByRow = {};
        wraps.forEach(function (wrap) {
            var rowEl = wrap.closest('.setlist-row');
            var rowNum = rowEl ? rowEl.getAttribute('data-row-num') : null;
            if (rowNum !== null && rowNums.indexOf(rowNum) === -1) {
                rowNums.push(rowNum);
            }
            var groupWrap = wrap.closest('.setlist-group-wrap');
            var groupTitle = groupWrap ? (groupWrap.getAttribute('data-group-title') || '') : '';
            if (rowNum !== null && groupTitle) {
                groupTitlesByRow[rowNum] = groupTitlesByRow[rowNum] || [];
                if (groupTitlesByRow[rowNum].indexOf(groupTitle) === -1) {
                    groupTitlesByRow[rowNum].push(groupTitle);
                }
            }
        });
        var hasMultipleRows = rowNums.length > 1;

        var currentContainer = patternSelect;
        var currentRowNum = null;
        var currentGroupTitle = null;
        var summaryShownForRow = {};
        wraps.forEach(function (wrap, index) {
            var rowEl = wrap.closest('.setlist-row');
            var rowNum = rowEl ? rowEl.getAttribute('data-row-num') : null;
            var groupWrap = wrap.closest('.setlist-group-wrap');
            var groupTitle = groupWrap ? groupWrap.getAttribute('data-group-title') : null;
            var rowChanged = rowNum !== null && rowNum !== currentRowNum;
            var groupChanged = groupTitle !== currentGroupTitle;

            // row内のグループ名が複数種類ある場合、Summarizeはどのグループにも属さない
            // row全体の先頭に入れる（特定のサブグループのoptgroupの中に入れるのは意味的に誤り）。
            // row内のグループ名が1種類だけ（またはグループ名が無い）場合は、そのグループの
            // optgroupの中に入れてよい（実質row全体を覆う唯一のグループのため）。
            var groupTitleCountInRow = (groupTitlesByRow[rowNum] || []).length;
            var summarizeGoesOutsideGroup = groupTitleCountInRow > 1;

            if (rowChanged) {
                currentRowNum = rowNum;
                if (summarizeGoesOutsideGroup && !summaryShownForRow[rowNum] && summaryRowNums.indexOf(rowNum) !== -1) {
                    summaryShownForRow[rowNum] = true;
                    var summaryOption = document.createElement('option');
                    summaryOption.value = '__summary_' + rowNum + '__';
                    summaryOption.textContent = 'Summarize';
                    patternSelect.appendChild(summaryOption);
                }
            }

            // rowまたはグループ名（同じrow内の曲順グループ見出し）が切り替わるたびに
            // 新しいoptgroupを作る。
            if (rowChanged || groupChanged) {
                currentGroupTitle = groupTitle;

                var label = groupTitle ? groupTitle : (hasMultipleRows ? 'Row ' + rowNum : '');

                if (label) {
                    var rowOptgroup = document.createElement('optgroup');
                    rowOptgroup.label = label;
                    patternSelect.appendChild(rowOptgroup);
                    currentContainer = rowOptgroup;
                } else {
                    currentContainer = patternSelect;
                }

                if (!summarizeGoesOutsideGroup && !summaryShownForRow[rowNum] && summaryRowNums.indexOf(rowNum) !== -1) {
                    summaryShownForRow[rowNum] = true;
                    var summaryOptionInGroup = document.createElement('option');
                    summaryOptionInGroup.value = '__summary_' + rowNum + '__';
                    summaryOptionInGroup.textContent = 'Summarize';
                    currentContainer.appendChild(summaryOptionInGroup);
                }
            }

            var option = document.createElement('option');
            option.value = String(index);
            option.textContent = wrap.getAttribute('data-pattern-label');
            currentContainer.appendChild(option);
        });
        @if (($tab ?? null) !== 'summary')
            var isMobileSummaryView = window.matchMedia('(max-width: 767px)').matches
                && document.querySelector('.setlist-responsive-summary-page:not(.is-mobile-hidden)');
            if (summaryRowNums.length && !isMobileSummaryView) {
                var summarySeparator = document.createElement('hr');
                patternSelect.appendChild(summarySeparator);

                var summaryPageOption = document.createElement('option');
                summaryPageOption.value = '__summary_page__';
                summaryPageOption.textContent = 'Summaryに移動';
                patternSelect.appendChild(summaryPageOption);
            }
        @endif
        patternSelect.addEventListener('change', function () {
            if (patternSelect.value === '__summary_page__') {
                window.location.href = '{{ route('live.show', $tours->id) }}?tab=summary{{ !empty($from) ? '&from=' . urlencode($from) : '' }}';
                return;
            }
            if (patternSelect.value.indexOf('__summary_') === 0) {
                var rowNum = patternSelect.value.replace('__summary_', '').replace('__', '');
                openSetlistSummary(rowNum);
                patternSelect.value = '';
                return;
            }
            var wrap = wraps[Number(patternSelect.value)];
            @if (($tab ?? null) === 'summary')
                // Summaryテキスト表示ページでは通常のセットリスト自体を非表示で
                // 埋め込んでいるだけなので、その場でスクロールしても意味が無い。
                // 通常表示に切り替え、選択したパターンの位置へ移動する。
                if (wrap && wrap.id) {
                    var standardViewUrl = '{{ route('live.show', $tours->id) }}{{ $standardViewQuery }}';
                    window.location.href = standardViewUrl + '&pattern=' + encodeURIComponent(wrap.id);
                }
                return;
            @else
                // モバイルではSummaryを初期表示し、通常セットリストはCSSで隠しているため、
                // 選択したパターンの位置へスクロールする通常表示に遷移する。
                if (isMobileSummaryView) {
                    if (wrap && wrap.id) {
                        window.location.href = '{{ route('live.show', $tours->id) }}{{ $standardViewQuery }}&pattern=' + encodeURIComponent(wrap.id);
                    }
                    return;
                }
            @endif
            if (wrap) {
                scrollToPatternWrap(wrap);
                patternSelect.value = '';
            }
        });
    }

    setupPatternSelect('spPatternListSelect');
    setupPatternSelect('pcPatternListSelect');

    // Summaryから通常表示へ移るとき、URLハッシュを使うとブラウザ標準の
    // アンカージャンプと独自スクロールが競合する。patternクエリを使い、
    // 通常表示のレイアウトが確定してから独自スクロールだけを実行する。
    var patternTargetId = new URLSearchParams(window.location.search).get('pattern');
    if (!patternTargetId && window.location.hash.indexOf('#setlist-pattern-') === 0) {
        patternTargetId = window.location.hash.slice(1);
    }
    if (patternTargetId) {
        var patternTarget = document.getElementById(patternTargetId);
        if (patternTarget) {
            window.scrollTo(0, 0);
            var pageAssetsReady = new Promise(function (resolve) {
                if (document.readyState === 'complete') {
                    resolve();
                } else {
                    window.addEventListener('load', resolve, { once: true });
                }
            });
            var fontsReady = document.fonts && document.fonts.ready
                ? document.fonts.ready
                : Promise.resolve();
            Promise.all([pageAssetsReady, fontsReady]).then(function () {
                // load/font完了後に2フレーム待ち、画像・フォント反映後の位置で計算する。
                requestAnimationFrame(function () {
                    requestAnimationFrame(function () {
                        scrollToPatternWrap(patternTarget, 'auto');
                    });
                });
            });
        }
    }

    // PCでもウィンドウ幅やスクロール有無に関わらず常にパターン一覧アイコンを表示する
    var pcIconWrap = document.getElementById('pcPatternListIconWrap');
    if (pcIconWrap) {
        pcIconWrap.style.display = 'flex';
    }

    var summaryOverlay = document.getElementById('setlistSummaryOverlay');
    if (summaryOverlay) {
        summaryOverlay.addEventListener('click', function (e) {
            if (e.target === summaryOverlay) {
                closeSetlistSummary();
            }
        });
        summaryOverlay.querySelectorAll('.setlist-summary-close').forEach(function (btn) {
            btn.addEventListener('click', closeSetlistSummary);
        });
    }
});

function scrollToPatternWrap(wrap, behavior) {
    var header = document.querySelector('nav.fixed-top');
    var headerBottom = header ? header.getBoundingClientRect().bottom : 0;
    var wrapRect = wrap.getBoundingClientRect();

    // グループ見出しを含むグループ全体（.setlist-group-wrap）の先頭を
    // 基準にスクロールする（PCは見出しが子要素として表示され、モバイルは
    // 見出し自体は隠れていてもラッパーの位置は変わらない）。
    // 見出しが無いグループは、その分オフセットを少なくする。
    var groupWrap = wrap.closest('.setlist-group-wrap');
    var hasGroupTitle = groupWrap && !!groupWrap.querySelector('.setlist-group-title');
    var scrollAnchorRect = groupWrap ? groupWrap.getBoundingClientRect() : wrapRect;
    var row = wrap.closest('.setlist-row');
    var stickyTitle = row ? row.previousElementSibling : null;
    var hasMobileStickyTitle = window.matchMedia('(max-width: 767px)').matches
        && stickyTitle
        && stickyTitle.classList.contains('setlist-group-title-sticky');
    var stickyTitleMarginTop = hasMobileStickyTitle
        ? (parseFloat(window.getComputedStyle(stickyTitle).marginTop) || 0)
        : 0;
    var extraOffset = hasMobileStickyTitle
        ? stickyTitle.offsetHeight + stickyTitleMarginTop + 4
        : (hasGroupTitle ? 30 : 12);

    var targetTop = window.scrollY + scrollAnchorRect.top - (headerBottom + extraOffset);
    window.scrollTo({ top: targetTop, behavior: behavior || 'smooth' });

    var scrollParent = row;
    if (scrollParent) {
        var parentRect = scrollParent.getBoundingClientRect();
        var targetLeft = scrollParent.scrollLeft + wrapRect.left - parentRect.left
            - (parentRect.width - wrapRect.width) / 2;
        scrollParent.scrollTo({ left: targetLeft, behavior: behavior || 'smooth' });
    }
}

function openSetlistSummary(rowNum) {
    var overlay = document.getElementById('setlistSummaryOverlay');
    if (!overlay) {
        return;
    }
    overlay.querySelectorAll('.setlist-summary-popup').forEach(function (popup) {
        popup.style.display = popup.getAttribute('data-summary-row') === String(rowNum) ? 'block' : 'none';
    });
    overlay.style.display = 'flex';
}

function closeSetlistSummary() {
    var overlay = document.getElementById('setlistSummaryOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}
</script>
@endsection

@endsection
