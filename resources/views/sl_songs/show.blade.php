@extends('layouts.app')
@section('title', 'Yuki Official - ' . $song->title)
@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'Setlists', 'url' => '/setlists'],
                ['label' => $song->title],
            ]])
            {{-- #（曲番）は選んでいるタブによって意味が変わる：Yuki's Live Attendances中はsl_songs.id順
                 （常に存在、デフォルト表示）、Live Performances中はDbSong詳細と同じsort_order順位
                 （db_song_id未紐付けならLive Performancesタブ自体が無いので表示しない）。 --}}
            <p class="database-subtitle song-number-mine" style="{{ $initialTab === 'performances' ? 'display: none;' : '' }}">#{{ $songNumber }}</p>
            @if ($performanceSongNumber)
                <p class="database-subtitle song-number-performances" style="{{ $initialTab === 'performances' ? '' : 'display: none;' }}">#{{ $performanceSongNumber }}</p>
            @endif
            <h1 class="database-title" style="">{{ $song->title }}</h1>

            <div style="font-size: 1rem; color: rgba(255, 255, 255, 0.9); line-height: 1.8;">
                @if ($song->artist)
                    <div style="">
                        <a href="{{ url('/setlists/artists', $song->artist_id) }}"
                            style="color: white; text-decoration: underline;">
                            {{ $song->artist->name }}
                        </a>
                    </div>
                @endif
            </div>

            {{-- 虫眼鏡アイコン（SP表示のみ、見出しブロックの右下）：押すとフォームが開き、ボタン自体は隠れる --}}
            <button type="button" id="spSearchButtonSetlistSong" class="sp" onclick="document.getElementById('spSearchButtonSetlistSong').style.display='none'; document.getElementById('spSearchFormSetlistSong').style.display='block';" style="position: absolute; bottom: -14px; right: 8px; background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-magnifying-glass" style="font-size: 14px;"></i>
            </button>

            {{-- 検索フォーム（SP表示） --}}
            <div class="sp" id="spSearchFormSetlistSong" style="margin-top: 10px; display: none;">
                <div>
                    @livewire('song-search')
                    {{-- 閉じるボタン --}}
                    <div style="text-align: center; margin-top: 15px;">
                        <button type="button" onclick="document.getElementById('spSearchFormSetlistSong').style.display='none'; document.getElementById('spSearchButtonSetlistSong').style.display='inline-flex';" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-xmark" style="font-size: 16px;"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- 検索フォーム（PC表示のみ） --}}
            <div class="database-search pc song-search-top-right">
                <div>
                    @livewire('song-search')
                </div>
            </div>
        </div>
    </div>

    <div class="container database-year-content">
        <div class="row justify-content-center">
            <div class="col-xl-9">

                <style>
                    @media (max-width: 768px) {
                        .song-performance-tabs {
                            justify-content: center;
                        }
                    }
                </style>
                <div class="song-performance-tabs" style="display: flex; gap: 8px; margin-bottom: 15px;">
                    @if ($hasLivePerformancesTab)
                        <button type="button" class="song-performance-tab-btn @if($initialTab === 'performances') is-active @endif" data-tab-target="live-performances-panel"
                            style="padding: 8px 16px; border-radius: 20px; font-weight: 500; cursor: pointer; {{ $initialTab === 'performances' ? 'border: none; background: #667eea; color: white;' : 'border: 1px solid #667eea; background: white; color: #667eea;' }}">
                            Live Performances
                        </button>
                    @endif
                    <button type="button" class="song-performance-tab-btn @if($initialTab === 'mine') is-active @endif" data-tab-target="second-tab-panel"
                        style="padding: 8px 16px; border-radius: 20px; font-weight: 500; cursor: pointer; {{ $initialTab === 'mine' ? 'border: none; background: #667eea; color: white;' : 'border: 1px solid #667eea; background: white; color: #667eea;' }}">
                        Yuki's Live Attendances
                    </button>
                </div>

                @if ($hasLivePerformancesTab)
                    <div id="live-performances-panel" style="display: {{ $initialTab === 'performances' ? 'block' : 'none' }};">
                        @if ($performanceTours->isEmpty())
                            <p style="color: #718096; text-align: center;">演奏記録がありません。</p>
                        @else
                            <table class="table table-striped count">
                                <thead>
                                    <tr>
                                        <th class="mobile">#</th>
                                        <th class="mobile">開催日</th>
                                        <th class="mobile">タイトル</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($performanceTours as $tour)
                                        <tr>
                                            <td></td>
                                            @if (isset($tour->date1) && isset($tour->date2))
                                                <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }} -
                                                    {{ date('Y.m.d', strtotime($tour->date2)) }}</td>
                                            @elseif(isset($tour->date1) && !isset($tour->date2))
                                                <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }}</td>
                                            @endif
                                            <td class="td_title"><a href="{{ route('live.show', $tour->id) }}">{{ $tour->title }}</a></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                @endif

                <div id="second-tab-panel" style="display: {{ $initialTab === 'mine' ? 'block' : 'none' }};">
                    @if ($setlists->isEmpty())
                        <p style="color: #718096; text-align: center;">演奏記録がありません。</p>
                    @else
                        <table class="table table-striped count">
                            <thead>
                                <tr>
                                    <th class="mobile">#</th>
                                    <th class="mobile">開催日</th>
                                    <th class="mobile">タイトル</th>
                                    <th class="pc">会場</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($setlists as $setlist)
                                    <tr>
                                        <td></td>
                                        <td class="td_date">{{ date('Y.m.d', strtotime($setlist->date)) }}</td>
                                        <td class="td_title"><a
                                                href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a></td>
                                        <td class="pc"><a
                                                href="{{ url('/venue?keyword=' . urlencode($setlist->venue)) }}">{{ $setlist->venue }}</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                {{-- 前後リンクも#と同じくタブに応じて意味が変わる。Live Performances側は
                     対応するDbSongをsort_order順にたどり、db_song_idで逆引きしたSlSongへリンクする
                     （タブを切り替えたままページ間を移動できるようにするため）。 --}}
                <div class="song-nav-mine" style="display: {{ $initialTab === 'performances' ? 'none' : 'flex' }}; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
                    @if (isset($previous))
                        <a href="{{ url('/setlists/songs', $previous->id) }}" rel="prev"
                            style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                            <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                            Previous
                        </a>
                    @else
                        <div></div>
                    @endif
                    @if (isset($next))
                        <a href="{{ url('/setlists/songs', $next->id) }}" rel="next"
                            style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                            Next
                            <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                        </a>
                    @endif
                </div>
                @if ($performanceSongNumber)
                    {{-- Previous/Nextの出現判定は常にDbSong基準（本来の並び順）で行う。リンク先は
                         対応するSlSongがあればそちらへ（タブの中に留まれる）、無ければDbSong詳細
                         ページへフォールバックする（まだ「聴いた記録」がない曲でも先には進める）。 --}}
                    <div class="song-nav-performances" style="display: {{ $initialTab === 'performances' ? 'flex' : 'none' }}; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
                        @if ($performancePreviousDbSong)
                            <a href="{{ $performancePreviousSlSong ? url('/setlists/songs', $performancePreviousSlSong->id) . '?tab=performances' : route('songs.show', ['id' => $performancePreviousDbSong->id, 'tab' => 'performances']) }}" rel="prev"
                                style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                                <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                                Previous
                            </a>
                        @else
                            <div></div>
                        @endif
                        @if ($performanceNextDbSong)
                            <a href="{{ $performanceNextSlSong ? url('/setlists/songs', $performanceNextSlSong->id) . '?tab=performances' : route('songs.show', ['id' => $performanceNextDbSong->id, 'tab' => 'performances']) }}" rel="next"
                                style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                                Next
                                <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                            </a>
                        @endif
                    </div>
                @endif

            </div>
        </div>
    </div>
@endsection

@section('page-script')
@if ($hasLivePerformancesTab)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var buttons = document.querySelectorAll('.song-performance-tab-btn');
            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    buttons.forEach(function (b) {
                        b.classList.remove('is-active');
                        b.style.background = 'white';
                        b.style.color = '#667eea';
                        b.style.border = '1px solid #667eea';
                    });
                    btn.classList.add('is-active');
                    btn.style.background = '#667eea';
                    btn.style.color = 'white';
                    btn.style.border = 'none';

                    document.getElementById('live-performances-panel').style.display = 'none';
                    document.getElementById('second-tab-panel').style.display = 'none';
                    document.getElementById(btn.dataset.tabTarget).style.display = 'block';

                    // #（曲番）とPrevious/Nextも選んだタブに応じて意味が変わるため、
                    // 同じタイミングで表示を切り替える
                    var isPerformances = btn.dataset.tabTarget === 'live-performances-panel';
                    var songNumberMine = document.querySelector('.song-number-mine');
                    var songNumberPerformances = document.querySelector('.song-number-performances');
                    if (songNumberMine) songNumberMine.style.display = isPerformances ? 'none' : '';
                    if (songNumberPerformances) songNumberPerformances.style.display = isPerformances ? '' : 'none';

                    var navMine = document.querySelector('.song-nav-mine');
                    var navPerformances = document.querySelector('.song-nav-performances');
                    if (navMine) navMine.style.display = isPerformances ? 'none' : 'flex';
                    if (navPerformances) navPerformances.style.display = isPerformances ? 'flex' : 'none';

                    // Previous/Nextで移動した先でも選んだタブをキープできるよう、URLに反映する
                    var url = new URL(window.location.href);
                    if (isPerformances) {
                        url.searchParams.set('tab', 'performances');
                    } else {
                        url.searchParams.delete('tab');
                    }
                    history.replaceState(null, '', url);
                });
            });
        });
    </script>
@endif
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== Setlist Songs Page Script Loaded ===');
            console.log('Livewire available:', typeof Livewire !== 'undefined');
            console.log('Alpine available:', typeof Alpine !== 'undefined');

            // Livewireコンポーネントが読み込まれたか確認
            setTimeout(function() {
                const searchInputs = document.querySelectorAll('.database-search-input');
                console.log('Search inputs found:', searchInputs.length);
                searchInputs.forEach(function(input, index) {
                    console.log('Input ' + index + ':', input);
                    console.log('Input has x-data:', input.closest('[x-data]') !== null);
                });
            }, 1000);
        });
    </script>
@endsection
