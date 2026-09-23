@extends('layouts.app')
@section('title', 'Yuki Official - ' . $tours->title)

@section('og_title', $tours->title . ' - Yuki Official')
@section('og_description', 'Tour: ' . $tours->title . ($tours->date1 ? ' (' . date('Y', strtotime($tours->date1)) . ')' : ''))
@section('og_type', 'article')

@section('content')
    @php
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

    <div class="{{ $totalOlCount >= 3 ? 'container-fluid' : 'container' }} database-year-content">
        <div class="row justify-content-center">
            <div class="{{ $colClass }}">
                <div class="setlist" style="width: 100%;">
                    @include('db_concerts._setlist_rows', ['tourSetlists' => $tourSetlists, 'songs' => $songs])
                </div>
            </div>
        </div>
    </div>

    @if (isset($setlistSummaries) && $setlistSummaries->count())
        <div id="setlistSummaryOverlay" style="display: none; position: fixed; inset: 0; background: rgba(20,22,30,0.5); z-index: 1050; align-items: center; justify-content: center;">
            @foreach ($setlistSummaries as $rowNum => $summary)
                <div class="setlist setlist-summary-popup" data-summary-row="{{ $rowNum }}" style="display: none; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); padding: 30px; max-width: 560px; width: calc(100% - 32px); max-height: 80vh; overflow-y: auto; position: relative;">
                    <button type="button" class="setlist-summary-close" style="position: absolute; top: 16px; right: 16px; border: none; background: #f0f1f6; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; color: #718096; font-size: 16px; line-height: 1; flex-shrink: 0;">&times;</button>
                    <h3 style="margin: 0 44px 20px 0; font-size: 18px;">{{ $tours->title }}</h3>
                    @foreach (['setlist' => $summary['setlist'], 'encore' => $summary['encore']] as $section => $rows)
                        @if (count($rows))
                            @if ($section === 'encore')
                                <div style="margin: 20px 0 10px;">
                                    <span style="color: #999; font-weight: 600; font-size: 0.9rem; letter-spacing: 2px;">ENCORE</span>
                                </div>
                            @endif
                            <ol class="live-column">
                                @foreach ($rows as $row)
                                    <li>
                                        @foreach ($row['variants'] as $variant)
                                            @if (!$loop->first)
                                                <span> / </span>
                                            @endif
                                            @if ($variant['song_id'])
                                                <a href="{{ url('/database/songs', $variant['song_id']) }}">{{ $variant['title'] }}</a>
                                            @else
                                                {{ $variant['title'] }}
                                            @endif
                                        @endforeach
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </div>
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
                        <a href="{{ route('live.show', $previous->id) }}{{ !empty($from) ? '?from=' . $from : '' }}" rel="prev"
                           style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                            <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                            Previous
                        </a>
                    @else
                        <div></div>
                    @endif
                    @if (isset($next))
                        <a href="{{ route('live.show', $next->id) }}{{ !empty($from) ? '?from=' . $from : '' }}" rel="next"
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
document.addEventListener('DOMContentLoaded', function () {
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
        patternSelect.addEventListener('change', function () {
            if (patternSelect.value.indexOf('__summary_') === 0) {
                var rowNum = patternSelect.value.replace('__summary_', '').replace('__', '');
                openSetlistSummary(rowNum);
                patternSelect.value = '';
                return;
            }
            var wrap = wraps[Number(patternSelect.value)];
            if (wrap) {
                var header = document.querySelector('nav.fixed-top');
                var headerHeight = header ? header.getBoundingClientRect().height : 0;
                var wrapRect = wrap.getBoundingClientRect();

                // グループ見出しを含むグループ全体（.setlist-group-wrap）の先頭を
                // 基準にスクロールする（PCは見出しが子要素として表示され、モバイルは
                // 見出し自体は隠れていてもラッパーの位置は変わらない）。
                // 見出しが無いグループは、その分オフセットを少なくする。
                var groupWrap = wrap.closest('.setlist-group-wrap');
                var hasGroupTitle = groupWrap && !!groupWrap.querySelector('.setlist-group-title');
                var scrollAnchorRect = groupWrap ? groupWrap.getBoundingClientRect() : wrapRect;
                var extraOffset = hasGroupTitle ? 30 : 12;

                var targetTop = window.scrollY + scrollAnchorRect.top - (headerHeight + extraOffset);
                window.scrollTo({ top: targetTop, behavior: 'smooth' });

                var scrollParent = wrap.closest('.setlist-row');
                if (scrollParent) {
                    var parentRect = scrollParent.getBoundingClientRect();
                    var targetLeft = scrollParent.scrollLeft + wrapRect.left - parentRect.left
                        - (parentRect.width - wrapRect.width) / 2;
                    scrollParent.scrollTo({ left: targetLeft, behavior: 'smooth' });
                }
                patternSelect.value = '';
            }
        });
    }

    setupPatternSelect('spPatternListSelect');
    setupPatternSelect('pcPatternListSelect');

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
