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
