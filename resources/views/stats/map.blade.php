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
        'venues' => array_map(fn ($v) => ['html' => $venueLink($v['venue']), 'count' => $v['count']], $p['venues']),
    ])->all();
    // 海外：国ごとに「国の名前」の見出しを付けて、会場を並べる
    $overseasList = array_filter($prefectures, fn ($p) => $p['overseas']);
    if ($overseasList) {
        $panelData['overseas'] = [
            'name' => '海外',
            'count' => array_sum(array_column($overseasList, 'count')),
            'venues' => collect($overseasList)->flatMap(fn ($c) => array_map(
                fn ($v) => ['html' => e($c['name']) . '：' . $venueLink($v['venue']), 'count' => $v['count']], $c['venues']
            ))->values()->all(),
        ];
    }
    $levels = \App\Support\VenueMap::LEVELS;
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
                            <p class="stats-subtitle venue-map-subtitle"><strong class="venue-map-total">{{ count($prefectures) - count($overseas) }}<span> / 47</span></strong> 都道府県@if ($overseas)<small class="venue-map-count">海外 {{ count($overseas) }}か国</small>@endif</p>
                        </div>
                        <div class="venue-map-figure">
                            <svg viewBox="{{ implode(' ', $shapes['viewBox']) }}" role="img" aria-label="日本地図" class="venue-map-svg">
                                <path d="{{ $shapes['divider'] }}" fill="none" stroke="#c8c8d0" stroke-width="0.8"/>
                                @foreach ($shapes['paths'] as $code => $d)
                                    @php $p = $prefectures[$code] ?? null; @endphp
                                    <path d="{{ $d }}" data-pref="{{ $code }}"
                                        class="venue-map-pref{{ $p ? ' is-visited' : '' }}"
                                        fill="{{ $levelColors[$p['level'] ?? 0] }}">
                                        <title>{{ \App\Models\VenuePrefecture::NAMES[$code] }}{{ $p ? ' ' . $p['count'] . '回' : '' }}</title>
                                    </path>
                                @endforeach
                            </svg>
                            {{-- 海外は地図に無いので、押したときだけ右の一覧に国と会場を出す --}}
                            @if ($overseas)
                                <button type="button" class="venue-map-overseas" data-pref-link="overseas">
                                    <i class="fa-solid fa-earth-americas"></i> 海外<small class="venue-map-count">{{ count($overseas) }}か国</small>
                                </button>
                            @endif
                            <div class="venue-map-legend">
                                <span><i style="background: {{ $levelColors[0] }}"></i>0</span>
                                @foreach ($levels as $i => $min)
                                    <span><i style="background: {{ $levelColors[$i + 1] }}"></i>{{ $min }}{{ isset($levels[$i + 1]) ? ($levels[$i + 1] - 1 > $min ? '〜' . ($levels[$i + 1] - 1) : '') : '〜' }}回</span>
                                @endforeach
                            </div>
                            <p class="venue-map-credit">地図：国土地理院「地球地図日本」をもとにした jpn-atlas（BSD-3-Clause, © 2018 Dan Henri）を加工して作成</p>
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
    .venue-map-wrapper { padding-bottom: 16px; min-height: calc(100vh - 56px); }
    .venue-map-layout { display: grid; grid-template-columns: minmax(0, 1fr); grid-template-areas: "head" "map" "panel"; gap: 16px; margin-top: 8px; }
    .venue-map-head { grid-area: head; }
    .venue-map-figure { grid-area: map; position: relative; min-width: 0; }
    .venue-map-panel { grid-area: panel; min-width: 0; margin: 0 !important; padding: 16px !important; }
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
            grid-template-rows: 1fr auto auto 1fr;
            grid-template-areas: "map ." "map head" "map panel" "map .";
            row-gap: 0;
            column-gap: 32px;
            /* 画面の高さ − ヘッダー(56) − 上の余白(16) − 下の余白(16) − ゆとり(8) */
            height: calc(100vh - 56px - 16px - 16px - 8px);
        }
        .venue-map-head { text-align: left; }
        .venue-map-title { font-size: 2.2rem; }
        .venue-map-title, .venue-map-subtitle { text-align: left; }
        .venue-map-figure { display: flex; flex-direction: column; justify-content: center; height: 100%; }
        .venue-map-svg { height: auto; max-height: calc(100% - 64px); width: auto; max-width: 100%; margin: 0 auto; }
        /* カードの大きさは、どの都道府県を選んでも同じ（会場5件ぐらいの高さ。多いときは中でスクロール） */
        .venue-map-panel { overflow-y: auto; padding: 24px !important; margin-top: 16px !important; height: 324px; }
        /* PC は地図の左下（海の上）に重ねる */
        .venue-map-overseas { position: absolute; left: 0; bottom: 64px; margin: 0; }
    }
    .venue-map-pref { stroke: #fff; stroke-width: 0.6; stroke-linejoin: round; }
    .venue-map-pref.is-visited { cursor: pointer; }
    .venue-map-pref.is-visited:hover { filter: brightness(0.92); }
    .venue-map-pref.is-selected { stroke: #ffb703; stroke-width: 1; }
    .venue-map-legend { display: flex; flex-wrap: wrap; gap: 6px 14px; justify-content: center; margin-top: 8px; font-size: 0.8rem; color: #fff; }
    .venue-map-legend i { display: inline-block; width: 14px; height: 14px; border-radius: 3px; margin-right: 4px; vertical-align: -2px; }
    .venue-map-overseas { display: block; margin: 8px auto 0; background: #fff; border: 1px solid #d5d8ef; border-radius: 999px; padding: 4px 12px; font-size: 0.8rem; color: #555; cursor: pointer; }
    .venue-map-overseas:hover, .venue-map-overseas.is-selected { border-color: #667eea; color: #667eea; }
    .venue-map-count { font-size: 0.6em; margin-left: 0.5em; opacity: 0.85; }
    /* 一覧は詰めて出す（Statistics の表より行を細く） */
    .venue-map-panel .section-title { font-size: 1.3rem; margin-bottom: 12px; padding-bottom: 8px; }
    .venue-map-panel .stats-table th { padding: 8px 12px; font-size: 0.7rem; }
    .venue-map-panel .stats-table td { padding: 6px 12px; font-size: 0.75rem; }
    .venue-map-panel .count-badge { min-width: 0; padding: 1px 9px; font-size: 0.72rem; }
    .venue-map-credit { margin: 8px 0 0; font-size: 0.65rem; color: rgba(255, 255, 255, 0.7); text-align: center; }
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
        document.getElementById('venueMapPanel').style.display = '';
        title.textContent = pref.name;
        var count = document.createElement('small');
        count.className = 'venue-map-count';
        count.textContent = pref.count + '回';
        title.appendChild(count);
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

    // ランキングから来たとき（?pref=13）は、その都道府県を開いておく
    var initial = @json($selected);
    if (initial && data[initial]) show(initial);
})();
</script>
@endsection
