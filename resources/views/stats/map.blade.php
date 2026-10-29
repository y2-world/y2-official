@extends('layouts.app')

{{-- Map：行った会場の都道府県を、行ったライブの回数で染めた日本地図。都道府県を押すと、その県の会場の一覧が出る。
     $map は VenueMap::build（prefectures・unassigned）、$shapes は地図の形（resources/data/japan_prefectures.json）。
     $venueUrl は会場のリンク先（null ならリンクにしない） --}}
@section('title', $title)
@section('og_title', $title . ' - Yuki Official')

@section('content')
@php
    $prefectures = $map['prefectures'];
    $levelColors = ['#eceef4', '#c3c8f6', '#8e97ee', '#6a5bd4', '#47298c'];
    $venueLink = fn ($venue) => $venueUrl ? '<a href="' . e($venueUrl($venue)) . '" class="stats-link">' . e($venue) . '</a>' : e($venue);
    $selected = request('pref');
    $panelData = collect($prefectures)->map(fn ($p) => [
        'name' => $p['name'],
        'count' => $p['count'],
        'venueCount' => count($p['venues']),
        'venues' => array_map(fn ($v) => ['html' => $venueLink($v['venue']), 'count' => $v['count']], $p['venues']),
    ])->all();
    // 海外：国ごとに「国の名前」の見出しを付けて、会場を並べる
    $overseasList = array_filter($prefectures, fn ($p) => $p['overseas']);
    if ($overseasList) {
        $panelData['overseas'] = [
            'name' => '海外',
            'count' => array_sum(array_column($overseasList, 'count')),
            'venueCount' => array_sum(array_map(fn ($c) => count($c['venues']), $overseasList)),
            'venues' => collect($overseasList)->flatMap(fn ($c) => array_map(
                fn ($v) => ['html' => e($c['name']) . '：' . $venueLink($v['venue']), 'count' => $v['count']], $c['venues']
            ))->values()->all(),
        ];
    }
    $levels = \App\Support\VenueMap::LEVELS;
    // 行った会場の数（都道府県が未設定の会場も入れる）
    $totalVenues = array_sum(array_map(fn ($p) => count($p['venues']), $prefectures)) + count($map['unassigned']);
@endphp
<div class="stats-wrapper venue-map-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="element js-fadein venue-map-page">
                    @php $overseas = array_filter($prefectures, fn ($p) => $p['overseas']); @endphp
                    {{-- PC：左に地図、右上にタイトル、その下に会場の一覧（スクロールせずに見えるように）。スマホ：タイトル・地図・一覧の順 --}}
                    <div class="venue-map-layout">
                        <div class="venue-map-head">
                            <h1 class="stats-title venue-map-title">{{ $title }}</h1>
                            <p class="stats-subtitle venue-map-subtitle"><strong class="venue-map-total">{{ count($prefectures) - count($overseas) }}<span> / 47</span></strong> 都道府県<span class="venue-map-extra">@if ($overseas)<small class="venue-map-count">海外 {{ count($overseas) }}か国</small>@endif<small class="venue-map-count">{{ $totalVenues }}会場</small></span></p>
                        </div>
                        <div class="venue-map-figure">
                            <div class="venue-map-canvas">
                            <svg viewBox="{{ implode(' ', $shapes['viewBox']) }}" role="img" aria-label="日本地図" class="venue-map-svg">
                                <g id="venueMapShapes">
                                <path d="{{ $shapes['divider'] }}" fill="none" stroke="#c8c8d0" stroke-width="0.8"/>
                                @foreach ($shapes['paths'] as $code => $d)
                                    @php $p = $prefectures[$code] ?? null; @endphp
                                    <path d="{{ $d }}" data-pref="{{ $code }}"
                                        class="venue-map-pref{{ $p ? ' is-visited' : '' }}"
                                        fill="{{ $levelColors[$p['level'] ?? 0] }}">
                                        <title>{{ \App\Models\VenuePrefecture::NAMES[$code] }}{{ $p ? ' ' . $p['count'] . '回' : '' }}</title>
                                    </path>
                                @endforeach
                                </g>
                            </svg>
                            {{-- スマホ：地図の拡大・縮小（拡大中は指でなぞって動かせる。2本指でも拡大・縮小できる） --}}
                            <div class="venue-map-zoom">
                                <button type="button" data-zoom="in" aria-label="拡大"><i class="fa-solid fa-magnifying-glass-plus"></i></button>
                                <button type="button" data-zoom="out" aria-label="縮小"><i class="fa-solid fa-magnifying-glass-minus"></i></button>
                                <button type="button" data-zoom="reset" aria-label="元に戻す" hidden><i class="fa-solid fa-expand"></i></button>
                            </div>
                                {{-- 海外は地図に無いので、押したときだけ一覧に国と会場を出す --}}
                                @if ($overseas)
                                    <button type="button" class="venue-map-overseas" data-pref-link="overseas">
                                        <i class="fa-solid fa-earth-americas"></i> 海外<small class="venue-map-count">{{ count($overseas) }}か国</small>
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- 会場の一覧のカード：都道府県を押したときに出す --}}
                        <div class="venue-map-panel stats-section visible" id="venueMapPanel" style="display: none;">
                            <h2 class="section-title" id="venueMapPanelTitle"></h2>
                            <div class="stats-table-container">
                                <table class="stats-table">
                                    <thead>
                                        <tr>
                                            <th>Venue</th>
                                            <th class="count-col">Visits</th>
                                        </tr>
                                    </thead>
                                    <tbody id="venueMapPanelRows">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        {{-- 色の見本と地図の出典（カードの下。カードが出ていなくても見せる） --}}
                        <div class="venue-map-foot">
                            <div class="venue-map-legend">
                                <span><i style="background: {{ $levelColors[0] }}"></i>0</span>
                                @foreach ($levels as $i => $min)
                                    <span><i style="background: {{ $levelColors[$i + 1] }}"></i>{{ $min }}{{ isset($levels[$i + 1]) ? ($levels[$i + 1] - 1 > $min ? '〜' . ($levels[$i + 1] - 1) : '') : '〜' }}回</span>
                                @endforeach
                            </div>
                            <p class="venue-map-credit">地図：国土地理院「地球地図日本」をもとにした jpn-atlas（BSD-3-Clause, © 2018 Dan Henri）を加工して作成</p>
                        </div>
                    </div>

                    @if ($map['unassigned'])
                        <div class="stats-section visible">
                            <h2 class="section-title">都道府県が未設定の会場
                                <span class="section-title-desc">地図には入っていません</span></h2>
                            <div class="stats-table-container">
                                <table class="stats-table">
                                    <tbody>
                                        @foreach ($map['unassigned'] as $v)
                                            <tr>
                                                <td class="venue-name">{!! $venueLink($v['venue']) !!}</td>
                                                <td class="count-col"><span class="count-badge">{{ $v['count'] }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .venue-map-page { padding-top: 16px !important; }
    /* ふつうの Statistics は「画面の高さ以上」なので、ヘッダーの分だけ長くなる。地図のページはヘッダーを除いた高さにする */
    /* 紫の背景は、いちばん広いときの画面の高さまで（iPhone でアドレスバーが引っ込んでも下まで紫） */
    .venue-map-wrapper { padding-bottom: 16px; min-height: calc(100vh - 56px); }
    /* 紫のグラデーションは、画面に貼りつく1枚の背景にする。ページの長さやスクロールに関係なく、見えている画面全体がいつも同じグラデーション
       （高さは画面のいちばん広いとき。iPhone でアドレスバーが引っ込んでも下まで）。下に引っぱったときだけ見える所は、グラデーションの下の端の色 */
    html { background: #764ba2; }
    body, .venue-map-wrapper { background: transparent !important; }
    body::before { content: ""; position: fixed; left: 0; right: 0; top: 0; height: 100vh; height: 100lvh; z-index: -1;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); pointer-events: none; }
    /* スマホで地図・ボタンを続けてタップしたときに、ページが拡大されないようにする（スクロールはそのまま） */
    .venue-map-wrapper, .venue-map-wrapper button { touch-action: manipulation; }
    .venue-map-layout { display: grid; grid-template-columns: minmax(0, 1fr); grid-template-areas: "head" "map" "panel" "foot"; gap: 16px; margin-top: 8px; }
    .venue-map-foot { grid-area: foot; }
    .venue-map-head { grid-area: head; }
    .venue-map-figure { grid-area: map; position: relative; min-width: 0; }
    /* 会場の一覧のカード：高さは決まった大きさにして、会場が多いときはカードの中でスクロール（スマホも PC も） */
    .venue-map-panel { grid-area: panel; min-width: 0; margin: 0 !important; padding: 16px !important; height: 324px; }
    /* カードの中でスクロールするのは表だけ。見出しはスクロールの外、表の見出しはスクロールの中で上に止める */
    #venueMapPanel:not([style*="none"]) { display: flex; flex-direction: column; }
    /* カードの見出しは下の紫の線なし */
    .venue-map-panel .section-title { flex: none; margin-bottom: 8px !important; border-bottom: none; padding-bottom: 0; }
    .venue-map-panel .stats-table-container { flex: 1; min-height: 0; overflow-y: auto; -webkit-overflow-scrolling: touch; }
    /* 表の overflow: hidden（角を丸めるため）があると止まらないので外し、角は見出しのセルで丸める */
    .venue-map-panel .stats-table { overflow: visible; }
    .venue-map-panel .stats-table thead th { position: sticky; top: 0; z-index: 1; background: #6f72dc; }
    .venue-map-panel .stats-table thead th:first-child { border-top-left-radius: 15px; }
    .venue-map-panel .stats-table thead th:last-child { border-top-right-radius: 15px; }
    /* スマホ：見出しは地図の左上（日本海の上）に重ね、地図は画面のぎりぎり上まで上げる。地図とカードが一緒に見えるように */
    @media (max-width: 991.98px) {
        .venue-map-page { padding-top: 0 !important; }
        .venue-map-layout { position: relative; grid-template-areas: "map" "panel" "foot"; margin-top: 0; gap: 12px; }
        /* 見出しは地図と同じ場所に重ねる（地図と一緒に動く） */
        .venue-map-head { grid-area: map; align-self: start; justify-self: start; position: relative; z-index: 4; margin-top: 4px; pointer-events: none; text-align: left; }
        /* 都道府県を選ぶ前は、地図と色の見本を画面の上下の真ん中に置く */
        /* iPhone の Safari の 100vh はアドレスバーなどを含む高さなので、真ん中の計算は見えている高さ（dvh）で（使えないブラウザは vh） */
        .venue-map-layout:not(.has-selection) { min-height: calc(100vh - 56px - 36px); min-height: calc(100dvh - 56px - 36px); align-content: center; }
        .venue-map-head .venue-map-title, .venue-map-head .venue-map-subtitle { text-align: left; }
        .venue-map-title { font-size: 1.15rem; margin-bottom: 2px; }
        .venue-map-subtitle { font-size: 0.8rem; }
        .venue-map-layout.is-zoomed .venue-map-head { display: none; }
        /* 「海外 1か国　78会場」は2行目に */
        .venue-map-extra { display: block; margin-top: 2px; }
        .venue-map-extra .venue-map-count { font-size: 0.75rem; }
        .venue-map-extra .venue-map-count:first-child { margin-left: 0; }
    }
    .venue-map-title { font-size: 1.6rem; margin-bottom: 4px; }
    /* 行った都道府県の数を目立たせる */
    .venue-map-total { font-size: 2em; font-weight: 700; color: #fff; line-height: 1; margin-right: 4px; }
    .venue-map-total span { font-size: 0.55em; font-weight: 500; opacity: 0.85; }
    /* タイトルの下の文字と一覧のカードの間を詰める */
    .venue-map-subtitle { margin-bottom: 0 !important; }
    .venue-map-svg { width: 100%; height: auto; display: block; }
    /* PC：スクロールせずに見えるように、地図と一覧を画面の高さに収める（一覧が長いときは一覧の中だけスクロール） */
    @media (min-width: 992px) {
        .venue-map-layout {
            grid-template-columns: minmax(0, 1.5fr) minmax(0, 1fr);
            /* 右の列：見出しとカードをひとまとまりにして、上下の真ん中に置く */
            grid-template-rows: 1fr auto auto auto 1fr;
            grid-template-areas: "map ." "map head" "map panel" "map foot" "map .";
            row-gap: 0;
            column-gap: 32px;
            /* 画面の高さ − ヘッダー(56) − 上の余白(16) − 下の余白(16) − ゆとり(8) */
            height: calc(100vh - 56px - 16px - 16px - 8px);
            height: calc(100dvh - 56px - 16px - 16px - 8px);
        }
        .venue-map-head { text-align: left; }
        .venue-map-title { font-size: 2.2rem; }
        .venue-map-title, .venue-map-subtitle { text-align: left; }
        .venue-map-figure { display: flex; flex-direction: column; justify-content: center; height: 100%; }
        .venue-map-svg { height: auto; max-height: calc(100% - 40px); width: auto; max-width: 100%; margin: 0 auto; }
        .venue-map-foot { margin-top: 12px; }
        .venue-map-foot .venue-map-legend, .venue-map-foot .venue-map-credit { justify-content: flex-start; text-align: left; }
        /* カードの大きさは、どの都道府県を選んでも同じ（会場5件ぐらいの高さ。多いときは中でスクロール） */
        .venue-map-panel { padding: 24px !important; margin-top: 16px !important; height: 324px; }
    }
    .venue-map-pref { stroke: #fff; stroke-width: 0.6; stroke-linejoin: round; }
    .venue-map-pref.is-visited { cursor: pointer; }
    .venue-map-pref.is-visited:hover { filter: brightness(0.92); }
    .venue-map-pref.is-selected { stroke: #ffb703; stroke-width: 1; }
    .venue-map-legend { display: flex; flex-wrap: wrap; gap: 6px 14px; justify-content: center; margin-top: 8px; font-size: 0.8rem; color: #fff; }
    .venue-map-legend i { display: inline-block; width: 14px; height: 14px; border-radius: 3px; margin-right: 4px; vertical-align: -2px; }
    /* 海外のボタンは、地図の左下（九州の下の海の上）に重ねる */
    .venue-map-overseas { position: absolute; left: 0; bottom: 2%; z-index: 5; background: #fff; border: 1px solid #d5d8ef; border-radius: 999px; padding: 4px 12px; font-size: 0.8rem; color: #555; cursor: pointer; }
    .venue-map-overseas:hover, .venue-map-overseas.is-selected { border-color: #667eea; color: #667eea; }
    .venue-map-svg { -webkit-touch-callout: none; -webkit-user-select: none; user-select: none; }
    .venue-map-canvas { position: relative; }
    /* 拡大のボタンは、北海道の下（東北の右の海の上）に縦に並べる */
    .venue-map-zoom { position: absolute; right: 3%; top: 38%; display: flex; flex-direction: column; gap: 8px; z-index: 5; }
    .venue-map-zoom button { width: 34px; height: 34px; border-radius: 50%; border: 1px solid rgba(255, 255, 255, 0.5); background: rgba(255, 255, 255, 0.2); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 14px; }
    .venue-map-zoom button[hidden] { display: none; }
    /* PC は地図が大きいので、拡大のボタンは出さない */
    @media (min-width: 992px) { .venue-map-zoom { display: none; } }
    .venue-map-count { font-size: 0.6em; margin-left: 0.5em; opacity: 0.85; }
    /* 一覧は詰めて出す（Statistics の表より行を細く） */
    .venue-map-panel .section-title { font-size: 1.3rem; margin-bottom: 12px; padding-bottom: 8px; }
    .venue-map-panel .stats-table th { padding: 8px 12px; font-size: 0.7rem; }
    .venue-map-panel .stats-table td { padding: 6px 12px; font-size: 0.75rem; }
    .venue-map-panel .count-badge { min-width: 0; padding: 1px 9px; font-size: 0.72rem; }
    .venue-map-credit { margin: 6px 0 0; font-size: 0.55rem; color: rgba(255, 255, 255, 0.7); text-align: center; }
</style>

<script>
(function () {
    var data = @json($panelData);
    var title = document.getElementById('venueMapPanelTitle');
    var rows = document.getElementById('venueMapPanelRows');

    function show(code) {
        var pref = data[code];
        if (!pref) return;
        document.querySelectorAll('.venue-map-pref, .venue-map-overseas').forEach(function (el) { el.classList.toggle('is-selected', (el.dataset.pref || el.dataset.prefLink) === code); });
        var layout = document.querySelector('.venue-map-layout');
        var first = !layout.classList.contains('has-selection');
        var figure = document.querySelector('.venue-map-figure');
        var before = figure.getBoundingClientRect().top;
        document.getElementById('venueMapPanel').style.display = '';
        layout.classList.add('has-selection');
        // スマホ：はじめて選んだときは、真ん中にあった地図をゆっくり上へ動かし、カードはふわっと出す
        if (first && window.innerWidth < 992) {
            var dy = before - figure.getBoundingClientRect().top;
            var parts = layout.querySelectorAll('.venue-map-head, .venue-map-figure, .venue-map-panel, .venue-map-foot');
            parts.forEach(function (el) { el.style.transition = 'none'; el.style.transform = 'translateY(' + dy + 'px)'; });
            var panel = document.getElementById('venueMapPanel');
            panel.style.opacity = '0';
            layout.getBoundingClientRect();
            parts.forEach(function (el) {
                el.style.transition = 'transform 0.6s ease, opacity 0.6s ease';
                el.style.transform = '';
            });
            panel.style.opacity = '';
            setTimeout(function () { parts.forEach(function (el) { el.style.transition = ''; }); }, 650);
        }
        title.textContent = pref.name;
        var count = document.createElement('small');
        count.className = 'venue-map-count';
        count.textContent = pref.count + '回 ' + pref.venueCount + '会場';
        title.appendChild(count);
        document.querySelector('#venueMapPanel .stats-table-container').scrollTop = 0;
        rows.innerHTML = pref.venues.map(function (v) {
            return '<tr><td class="venue-name">' + v.html + '</td><td class="count-col"><span class="count-badge">' + v.count + '</span></td></tr>';
        }).join('');
        var url = new URL(window.location.href);
        url.searchParams.set('pref', code);
        history.replaceState(null, '', url);
    }

    document.querySelectorAll('.venue-map-pref.is-visited').forEach(function (el) {
        el.addEventListener('click', function () { show(el.dataset.pref); });
    });
    document.querySelectorAll('[data-pref-link]').forEach(function (el) {
        el.addEventListener('click', function (e) { e.preventDefault(); show(el.dataset.prefLink); });
    });

    // スマホ：地図の拡大・縮小。拡大中は指でなぞって動かし、2本指で広げる・つまむでも拡大・縮小する。
    // 地図を動かしただけのときは、都道府県を押したことにしない
    var svg = document.querySelector('.venue-map-svg');
    var base = svg.viewBox.baseVal;
    var B = { x: base.x, y: base.y, w: base.width, h: base.height };
    var view = { s: 1, cx: B.x + B.w / 2, cy: B.y + B.h / 2 };
    var MAX = 6, STEP = 1.6;
    var resetBtn = document.querySelector('[data-zoom="reset"]');

    function apply() {
        view.s = Math.min(MAX, Math.max(1, view.s));
        var w = B.w / view.s, h = B.h / view.s;
        view.cx = Math.min(B.x + B.w - w / 2, Math.max(B.x + w / 2, view.cx));
        view.cy = Math.min(B.y + B.h - h / 2, Math.max(B.y + h / 2, view.cy));
        svg.setAttribute('viewBox', (view.cx - w / 2) + ' ' + (view.cy - h / 2) + ' ' + w + ' ' + h);
        // 拡大していないときは、地図の上でもページをスクロールできるようにする
        svg.style.touchAction = view.s > 1 ? 'none' : 'pan-y';
        resetBtn.hidden = view.s <= 1;
        // 拡大中は、地図に重ねた見出し（スマホ）を隠す
        document.querySelector('.venue-map-layout').classList.toggle('is-zoomed', view.s > 1);
    }
    function toSvg(x, y) {
        var pt = svg.createSVGPoint(); pt.x = x; pt.y = y;
        return pt.matrixTransform(svg.getScreenCTM().inverse());
    }
    // 画面の (x, y) の地点を動かさずに、倍率を factor 倍にする
    function zoomAt(factor, x, y) {
        var before = toSvg(x, y);
        var s = Math.min(MAX, Math.max(1, view.s * factor));
        var k = view.s / s;
        view.cx = before.x + (view.cx - before.x) * k;
        view.cy = before.y + (view.cy - before.y) * k;
        view.s = s;
        apply();
    }
    document.querySelectorAll('[data-zoom]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var r = svg.getBoundingClientRect(), x = r.left + r.width / 2, y = r.top + r.height / 2;
            if (btn.dataset.zoom === 'in') zoomAt(STEP, x, y);
            else if (btn.dataset.zoom === 'out') zoomAt(1 / STEP, x, y);
            else { view.s = 1; apply(); }
        });
    });

    var drag = null, pinch = null, moved = false;
    svg.addEventListener('touchstart', function (e) {
        moved = false;
        if (e.touches.length === 2) {
            var a = e.touches[0], b = e.touches[1];
            pinch = { d: Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY) };
            drag = null;
        } else if (e.touches.length === 1 && view.s > 1) {
            drag = { x: e.touches[0].clientX, y: e.touches[0].clientY };
        }
    }, { passive: true });
    svg.addEventListener('touchmove', function (e) {
        if (pinch && e.touches.length === 2) {
            e.preventDefault();
            var a = e.touches[0], b = e.touches[1];
            var d = Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
            zoomAt(d / pinch.d, (a.clientX + b.clientX) / 2, (a.clientY + b.clientY) / 2);
            pinch.d = d; moved = true;
        } else if (drag && e.touches.length === 1) {
            e.preventDefault();
            var t = e.touches[0];
            var scale = (B.w / view.s) / svg.getBoundingClientRect().width;
            view.cx -= (t.clientX - drag.x) * scale;
            view.cy -= (t.clientY - drag.y) * scale;
            if (Math.hypot(t.clientX - drag.x, t.clientY - drag.y) > 2) moved = true;
            drag = { x: t.clientX, y: t.clientY };
            apply();
        }
    }, { passive: false });
    svg.addEventListener('touchend', function (e) {
        if (e.touches.length < 2) pinch = null;
        if (e.touches.length === 0) drag = null;
    });
    // 動かしたあとのタップは、都道府県を選ばない
    svg.addEventListener('click', function (e) {
        if (moved) { e.stopPropagation(); e.preventDefault(); moved = false; }
    }, true);
    apply();

    // ランキングから来たとき（?pref=13）は、その都道府県を開いておく
    var initial = @json($selected);
    if (initial && data[initial]) show(initial);
})();
</script>
@endsection
