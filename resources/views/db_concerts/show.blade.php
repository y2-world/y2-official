@extends('layouts.app')
@section('title', 'Yuki Official - ' . $tours->title)

@section('og_title', $tours->title . ' - Yuki Official')
@section('og_description', 'Tour: ' . $tours->title . ($tours->date1 ? ' (' . date('Y', strtotime($tours->date1)) . ')' : ''))
@section('og_type', 'article')

@section('content')
    @php
        // Previous/Nextで移動してもtype絞り込み一覧の範囲・Summary専用表示を
        // 維持できるよう、現在のfrom/tabをクエリとして引き継ぐ
        $prevNextQuery = (($tab ?? null) === 'summary') ? '?tab=summary' : (!empty($from) ? '?from=' . $from : '');

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
                                <div class="live-column-wrap" style="max-width: min(450px, 80vw);">
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
                                                ->map(fn ($line) => '<span style="white-space: nowrap;">' . $line . '</span>')
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
                                                    {!! $rowPatternLabels->implode(' ') !!}
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
                                                            @if (!$loop->first)
                                                                <span>{{ !empty($variant['medley']) ? '〜' : ' / ' }}</span>
                                                            @endif
                                                            @php
                                                                $featuring = $variant['featuring'] ?? '';
                                                                $featuringType = $variant['featuring_type'] ?? 'guest';
                                                                $featuringDisplay = $featuring !== '' && $featuringType === 'artist'
                                                                    ? '/ ' . $featuring
                                                                    : $featuring;
                                                            @endphp
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
                                                                <span style="color:#999;font-size:0.75em;">{{ $featuringDisplay }}</span>
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
                    @else
                        <p>このライブにはSummaryがありません。</p>
                    @endif
                </div>
            </div>
        </div>
    @else
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
                    @php $summaryNumber = 0; @endphp
                    <div class="setlist setlist-summary-popup" data-summary-row="{{ $rowNum }}" style="display: none; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08); padding: 30px; max-width: 560px; width: calc(100% - 32px); max-height: 80vh; overflow-y: auto; position: relative;">
                        <button type="button" class="setlist-summary-close" style="position: absolute; top: 16px; right: 16px; border: none; background: #f0f1f6; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; color: #718096; font-size: 16px; line-height: 1; flex-shrink: 0;">&times;</button>
                        <h3 style="margin: 0 44px 20px 0; font-size: 18px;">{{ $tours->title }}</h3>
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
                                                    <span>{{ !empty($variant['medley']) ? '〜' : ' / ' }}</span>
                                                @endif
                                                @php
                                                    $featuring = $variant['featuring'] ?? '';
                                                    $featuringType = $variant['featuring_type'] ?? 'guest';
                                                    $featuringDisplay = $featuring !== '' && $featuringType === 'artist'
                                                        ? '/ ' . $featuring
                                                        : $featuring;
                                                @endphp
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
                                                    <span style="color:#999;font-size:0.75em;">{{ $featuringDisplay }}</span>
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
                        <a href="{{ route('live.show', $previous->id) }}{{ $prevNextQuery }}" rel="prev"
                           style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                            <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                            Previous
                        </a>
                    @else
                        <div></div>
                    @endif
                    @if (isset($next))
                        <a href="{{ route('live.show', $next->id) }}{{ $prevNextQuery }}" rel="next"
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
    // Summaryページのrowパターンラベル一覧（setlist-subtitle-wrap）は、
    // パターン数や幅次第で1行に収まることもある。その場合は省略記号や
    // クリック展開の見た目自体が不要（展開しても何も変わらないため）
    // なので、実際にline-clampで2行目以降が切り詰められているかどうかを
    // 判定し、切り詰められていなければsetlist-subtitle-collapsibleを
    // 外す。判定はscrollHeightとclientHeightの比較で行う（一時的に
    // is-expandedを付けて実際の全文の高さを測り、line-clamp適用時の
    // 高さと比べる）。
    document.querySelectorAll('.setlist-subtitle-wrap.setlist-subtitle-collapsible').forEach(function (el) {
        var clampedHeight = el.getBoundingClientRect().height;
        el.classList.add('is-expanded');
        var fullHeight = el.getBoundingClientRect().height;
        el.classList.remove('is-expanded');
        if (fullHeight <= clampedHeight + 1) {
            el.classList.remove('setlist-subtitle-collapsible');
            el.removeAttribute('onclick');
        }
    });

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
            if (summaryRowNums.length) {
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
                // 通常のセットリスト表示ページに、選んだパターンのIDをアンカーとして
                // 遷移する（Summaryページからのクイックアクセスという位置付け）。
                if (wrap && wrap.id) {
                    window.location.href = '{{ route('live.show', $tours->id) }}{{ !empty($from) ? '?from=' . $from : '' }}#' + wrap.id;
                }
                return;
            @endif
            if (wrap) {
                scrollToPatternWrap(wrap);
                patternSelect.value = '';
            }
        });
    }

    setupPatternSelect('spPatternListSelect');
    setupPatternSelect('pcPatternListSelect');

    // Summaryテキスト表示ページのパターン一覧から個別パターンを選ぶと、
    // このページ（通常のセットリスト表示）に#setlist-pattern-IDのハッシュ付きで
    // 遷移してくる。ブラウザ自身の標準アンカージャンプ（scrollToPatternWrapとは
    // 別に、ページ読み込み時点で先に発生する）が先に効いてしまい、その後
    // scrollToPatternWrapが「既にジャンプ済みの位置」を基準に計算してしまうと、
    // 二重にオフセットがかかって着地位置がずれる（例: 1曲目を選んだのに、
    // ブラウザの標準ジャンプ分だけ余計に下へスクロールされ、タイトルや
    // 1曲目自体が隠れてしまう）。scrollTo自体は同期的に位置を更新するが、
    // 直後のgetBoundingClientRect()がレイアウト再計算前の古い値を返すことが
    // あるため、requestAnimationFrameで1フレーム待ってから計算し直す。
    if (window.location.hash.indexOf('#setlist-pattern-') === 0) {
        var hashTarget = document.getElementById(window.location.hash.slice(1));
        if (hashTarget) {
            window.scrollTo(0, 0);
            requestAnimationFrame(function () {
                scrollToPatternWrap(hashTarget);
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

function scrollToPatternWrap(wrap) {
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
