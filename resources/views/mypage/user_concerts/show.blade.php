@extends('layouts.app')
@section('title', 'Yuki Official - ' . $tour->title)

@section('content')
    @php
        $totalOlCount = $tourSetlists
            ->filter(fn ($model) => is_array($model->setlist) && count($model->setlist) > 0)
            ->count();
        $colClass = $totalOlCount <= 2 ? 'col-xl-9' : 'col-xl-12';
    @endphp

    <div class="database-hero database-hero--detail">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => $artist->name, 'url' => route('mypage.user_artists.live', $artist->id)],
                ['label' => $tour->title],
            ]])
            <p class="database-subtitle">
                <a href="{{ route('mypage.user_artists.live', $artist->id) }}">{{ $artist->name }}</a>
            </p>
            <h1 class="database-title">{{ $tour->title }}</h1>
            <p class="database-subtitle">
                @if ($tour->date1 && $tour->date2)
                    {{ date('Y.m.d', strtotime($tour->date1)) }} - {{ date('Y.m.d', strtotime($tour->date2)) }}
                @elseif ($tour->date1)
                    {{ date('Y.m.d', strtotime($tour->date1)) }}
                @endif
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
                    @include('db_concerts._setlist_rows', ['tourSetlists' => $tourSetlists, 'songs' => $songs, 'songLinkResolver' => fn ($song) => route('mypage.user_songs.show', $song->id)])
                </div>
            </div>
        </div>
    </div>

    @if (isset($setlistSummaries) && $setlistSummaries->count())
        <div id="setlistSummaryOverlay" style="display: none; position: fixed; inset: 0; background: rgba(20,22,30,0.5); z-index: 1050; align-items: center; justify-content: center;">
            @foreach ($setlistSummaries as $rowNum => $summary)
                @php $summaryNumber = 0; @endphp
                <div class="setlist setlist-summary-popup" data-summary-row="{{ $rowNum }}" style="display: none; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); padding: 30px; max-width: 560px; width: calc(100% - 32px); max-height: 80vh; overflow-y: auto; position: relative;">
                    <button type="button" class="setlist-summary-close" style="position: absolute; top: 16px; right: 16px; border: none; background: #f0f1f6; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; color: #718096; font-size: 16px; line-height: 1; flex-shrink: 0;">&times;</button>
                    <h3 style="margin: 0 44px 20px 0; font-size: 18px;">{{ $tour->title }}</h3>
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
                                            @if (!$loop->first)
                                                <span> / </span>
                                            @endif
                                            @if (!($variant['is_common'] ?? true))
                                                <strong>
                                            @endif
                                            @if ($variant['song_id'])
                                                <a href="{{ route('mypage.user_songs.show', $variant['song_id']) }}">{{ $variant['title'] }}</a>
                                            @else
                                                {{ $variant['title'] }}
                                            @endif
                                            @if (!($variant['is_common'] ?? true))
                                                </strong>
                                            @endif
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

    <div class="container database-year-content" style="padding-top: 0;">
        <div class="row justify-content-center">
            <div class="col-xl-9">
                <div style="display: flex; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
                    @if ($previous)
                        <a href="{{ route('mypage.user_concerts.show', $previous->id) }}" rel="prev"
                           style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                            <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                            Previous
                        </a>
                    @else
                        <div></div>
                    @endif
                    @if ($next)
                        <a href="{{ route('mypage.user_concerts.show', $next->id) }}" rel="next"
                           style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                            Next
                            <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.setlist-row').forEach(function (row) {
        if (row.scrollWidth > row.clientWidth) {
            row.style.justifyContent = 'flex-start';
        }
        var areas = row.querySelectorAll('.setlist-subtitle-area');
        var maxH = 0;
        areas.forEach(function (a) { a.style.height = 'auto'; maxH = Math.max(maxH, a.scrollHeight); });
        areas.forEach(function (a) { a.style.height = maxH + 'px'; });
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
