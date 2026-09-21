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
            <p class="database-subtitle" style="">
                <a href="{{ route('database.artist', $artist->id) }}">{{ $artist->name }}</a>
            </p>
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
                        <a href="{{ route('live.show', $previous->id) }}" rel="prev"
                           style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                            <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                            Previous
                        </a>
                    @else
                        <div></div>
                    @endif
                    @if (isset($next))
                        <a href="{{ route('live.show', $next->id) }}" rel="next"
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
        var stickyTitle = row.previousElementSibling;
        if (stickyTitle && stickyTitle.classList.contains('setlist-group-title-sticky')) {
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
        var currentOptgroup = null;
        var currentGroupTitle = null;
        wraps.forEach(function (wrap, index) {
            var groupWrap = wrap.closest('.setlist-group-wrap');
            var groupTitle = groupWrap ? groupWrap.getAttribute('data-group-title') : null;

            var option = document.createElement('option');
            option.value = String(index);
            option.textContent = wrap.getAttribute('data-pattern-label');

            if (groupTitle) {
                if (groupTitle !== currentGroupTitle) {
                    currentOptgroup = document.createElement('optgroup');
                    currentOptgroup.label = groupTitle;
                    patternSelect.appendChild(currentOptgroup);
                    currentGroupTitle = groupTitle;
                }
                currentOptgroup.appendChild(option);
            } else {
                currentGroupTitle = null;
                patternSelect.appendChild(option);
            }
        });
        patternSelect.addEventListener('change', function () {
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

    // PCでは、セットリストが横に並びきらずスクロールが発生している場合だけ
    // パターン一覧アイコンを表示する（ウィンドウ幅やパターン数によって変わるため実測する）。
    var pcIconWrap = document.getElementById('pcPatternListIconWrap');
    if (pcIconWrap) {
        var updatePcIconVisibility = function () {
            // .pcクラス自体がmax-width:991pxで非表示になる想定のため、
            // モバイル幅ではインラインstyleで上書きしないよう判定自体をスキップする
            if (window.innerWidth < 992) {
                pcIconWrap.style.display = 'none';
                return;
            }
            var hasScrollingRow = Array.prototype.some.call(
                document.querySelectorAll('.setlist-row'),
                function (row) {
                    return row.scrollWidth > row.clientWidth + 1;
                }
            );
            pcIconWrap.style.display = hasScrollingRow ? 'flex' : 'none';
        };
        updatePcIconVisibility();
        window.addEventListener('resize', updatePcIconVisibility);
    }
});
</script>
@endsection

@endsection
