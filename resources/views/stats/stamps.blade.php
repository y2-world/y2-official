@extends('layouts.app')

@section('title', $artist->name . ' Stamp Book - Statistics')
@section('og_title', $artist->name . ' Stamp Book - Yuki Official')

@section('content')
<div class="stats-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="element js-fadein">
                    <h1 class="stats-title">{{ $artist->name }}</h1>
                    <p class="stats-subtitle">Live Stamp Book</p>

                    <div class="stamp-summary"
                        data-total="{{ $totalCount }}"
                        data-total-percentage="{{ $percentage }}"
                        data-performed="{{ $performedCount }}"
                        data-performed-percentage="{{ $performedPercentage }}">
                        <div class="stamp-summary-count">
                            <span class="stamp-summary-done" id="stampSummaryDone">{{ $doneCount }}</span>
                            <span class="stamp-summary-slash">/</span>
                            <span class="stamp-summary-total" id="stampSummaryTotal">{{ $totalCount }}</span>
                            <span class="stamp-summary-unit">songs</span>
                        </div>
                        <div class="stamp-summary-bar">
                            <div class="stamp-summary-bar-fill" id="stampSummaryBarFill" style="width: {{ $percentage }}%;"></div>
                        </div>
                        <div class="stamp-summary-percentage" id="stampSummaryPercentage">{{ $percentage }}% complete</div>

                        @include('stats._stamp_filter')

                        <label class="unique-tour-label stamp-summary-toggle">
                            <input type="checkbox" id="stampPerformedOnlyCheckbox" class="unique-tour-checkbox">
                            <span class="unique-tour-text">演奏曲のみ</span>
                        </label>
                    </div>

                    @if ($totalCount === 0)
                        <p class="stamp-empty">No songs registered in the database yet.</p>
                    @else
                        <div class="stamp-book {{ $canEditDeco ? 'is-owner' : '' }}">
                            {{-- 絞り込んだときに、絞り込みの名前を出す --}}
                            <p class="stamp-book-filter-title" id="stampBookFilterTitle" hidden></p>
                            @if ($canEditDeco)
                                {{-- 枠の色とタイトルの編集（マイページのYukiのアカウントでログインしているときだけ） --}}
                                <button type="button" class="stamp-deco-edit-button" id="stampDecoEditButton" title="スタンプの枠の色とタイトルを編集" aria-label="スタンプの枠の色とタイトルを編集">
                                    <i class="fa-solid fa-palette"></i>
                                </button>
                            @endif
                            @foreach ($stamps as $stamp)
                                <div class="stamp-slot {{ $stamp['done'] ? 'is-stamped' : '' }} {{ $stamp['never_performed'] ? 'is-never-performed' : '' }} {{ $stamp['fes_only'] ? 'is-fes-only' : '' }} {{ !empty($stamp['hikigatari_only']) ? 'is-hikigatari-only' : '' }}"
                                    data-filter-keys="{{ implode(' ', $stamp['filter_keys'] ?? []) }}"
                                    data-done="{{ $stamp['done'] ? 1 : 0 }}"
                                    data-never-performed="{{ $stamp['never_performed'] ? 1 : 0 }}"
                                    data-song-id="{{ $stamp['song_id'] }}"
                                    data-color-id="{{ $stamp['color_id'] ?? '' }}"
                                    @if (!empty($stamp['color_id']) && ($deco = $stampColors->firstWhere('id', $stamp['color_id']))) style="--stamp-deco: {{ $deco->color }};" @endif
                                    @if (!empty($stamp['track_titles'])) data-track-titles="{{ json_encode($stamp['track_titles'], JSON_UNESCAPED_UNICODE) }}" @endif
                                    @if (!empty($stamp['track_orders'])) data-track-orders="{{ json_encode($stamp['track_orders']) }}" @endif>
                                    <div class="stamp-slot-frame js-stamp-tap" @if ($stamp['never_performed']) title="ライブで演奏されたことがない曲です" @endif>
                                        {{-- 選んだ色のタイトル（枠の飾り） --}}
                                        <span class="stamp-slot-deco-label">{{ !empty($stamp['color_id']) ? optional($stampColors->firstWhere('id', $stamp['color_id']))->label : '' }}</span>
                                        @if ($stamp['done'])
                                            <div class="stamp-mark">
                                                @if (!empty($stamp['hikigatari_only']))
                                                    {{-- 福山雅治のDOUBLE ENCORE（弾き語り）でしか聴いていない曲 --}}
                                                    <span class="stamp-mark-text" title="弾き語り（DOUBLE ENCORE）でのみ"><i class="fa-solid fa-guitar"></i></span>
                                                @else
                                                    <span class="stamp-mark-text">{{ $stamp['fes_only'] ? 'FES' : 'LIVE' }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    <a href="{{ $stamp['song_url'] ?? route('songs.show', ['id' => $stamp['song_id'], 'tab' => 'mine']) }}" class="stamp-slot-title">{{ $stamp['title'] }}</a>@if (!empty($stamp['credit']))<span class="stamp-slot-credit">{{ $stamp['credit'] }}</span>@endif
                                </div>
                            @endforeach
                            {{-- 絞り込みで1曲も無いときのメッセージ（台紙の中に出す） --}}
                            <p class="stamp-empty stamp-empty-in-book" id="stampFilterNoMatch" hidden>No songs match this filter.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@include('stats._stamp_deco_modals')
@endsection

@section('page-script')
<script src="{{ asset('/js/stamp-book.js?v=20261013e') }}"></script>
@include('stats._stamp_book_script')
@endsection
