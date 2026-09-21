@extends('layouts.app')
@section('title', 'Yuki Official - ' . $song->title)

@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => $song->artist->name, 'url' => route('mypage.user_artists.live', $song->user_artist_id)],
                ['label' => $song->title],
            ]])
            <p class="database-subtitle">
                <a href="{{ route('mypage.user_artists.live', $song->user_artist_id) }}">{{ $song->artist->name }}</a>
            </p>
            {{-- #（曲番）は選んでいるタブによって意味が変わる：Live Performances中はアーティスト内の
                 sort_order順位（常に存在）、My Live Attendances中は自分の参加記録での初登場順
                 （自分がまだ聴いた記録がなければ非表示）。両方をあらかじめ用意し、タブ切り替えJSで出し分ける。 --}}
            <p class="database-subtitle song-number-performances" style="{{ $secondTab === 'mine' ? 'display: none;' : '' }}"># {{ $songNumber }}</p>
            @if ($songNumberMine)
                <p class="database-subtitle song-number-mine" style="{{ $secondTab === 'mine' ? '' : 'display: none;' }}"># {{ $songNumberMine }}</p>
            @endif
            <h1 class="database-title">{{ $song->title }}</h1>
        </div>
    </div>

    <div class="container-lg database-year-content">
        <style>
            @media (max-width: 768px) {
                .song-performance-tabs {
                    justify-content: center;
                }
            }
        </style>
        <div class="song-performance-tabs" style="display: flex; gap: 8px; margin-bottom: 15px;">
            <button type="button" class="song-performance-tab-btn @if($secondTab === 'performances') is-active @endif" data-tab-target="live-performances-panel"
                style="padding: 8px 16px; border-radius: 20px; font-weight: 500; cursor: pointer; {{ $secondTab === 'performances' ? 'border: none; background: #667eea; color: white;' : 'border: 1px solid #667eea; background: white; color: #667eea;' }}">
                Live Performances
            </button>
            <button type="button" class="song-performance-tab-btn @if($secondTab === 'mine') is-active @endif" data-tab-target="second-tab-panel"
                style="padding: 8px 16px; border-radius: 20px; font-weight: 500; cursor: pointer; {{ $secondTab === 'mine' ? 'border: none; background: #667eea; color: white;' : 'border: 1px solid #667eea; background: white; color: #667eea;' }}">
                My Live Attendances
            </button>
        </div>

        <div id="live-performances-panel" style="display: {{ $secondTab === 'performances' ? 'block' : 'none' }};">
            @if ($tours->isEmpty())
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
                        @foreach ($tours as $tour)
                            <tr>
                                <td></td>
                                @if ($tour->date1 && $tour->date2)
                                    <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }} - {{ date('Y.m.d', strtotime($tour->date2)) }}</td>
                                @elseif ($tour->date1)
                                    <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }}</td>
                                @else
                                    <td class="td_date"></td>
                                @endif
                                <td class="td_title"><a href="{{ route('mypage.user_concerts.show', $tour->id) }}">{{ $tour->title }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div id="second-tab-panel" style="display: {{ $secondTab === 'mine' ? 'block' : 'none' }};">
            @if ($secondTabSetlists->isEmpty())
                <p style="color: #718096; text-align: center;">参加記録がありません。</p>
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
                        @foreach ($secondTabSetlists as $tour)
                            <tr>
                                <td></td>
                                @if ($tour->date1 && $tour->date2)
                                    <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }} - {{ date('Y.m.d', strtotime($tour->date2)) }}</td>
                                @elseif ($tour->date1)
                                    <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }}</td>
                                @else
                                    <td class="td_date"></td>
                                @endif
                                <td class="td_title"><a href="{{ route('mypage.user_concerts.show', $tour->id) }}">{{ $tour->title }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- 前後リンクも#と同じくタブに応じて意味が変わる（Live Performances: sort_order順、
             My Live Attendances: 初めて聴いた順）。両パターンを用意しJSで出し分ける。 --}}
        <div class="song-nav-performances" style="display: {{ $secondTab === 'mine' ? 'none' : 'flex' }}; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
            @if ($previous)
                <a href="{{ route('mypage.user_songs.show', ['id' => $previous->id, 'tab' => 'performances']) }}" rel="prev"
                   style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                    <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                    Previous
                </a>
            @else
                <div></div>
            @endif
            @if ($next)
                <a href="{{ route('mypage.user_songs.show', ['id' => $next->id, 'tab' => 'performances']) }}" rel="next"
                   style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                    Next
                    <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                </a>
            @endif
        </div>
        <div class="song-nav-mine" style="display: {{ $secondTab === 'mine' ? 'flex' : 'none' }}; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
            @if ($previousMine)
                <a href="{{ route('mypage.user_songs.show', ['id' => $previousMine->id, 'tab' => 'mine']) }}" rel="prev"
                   style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                    <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                    Previous
                </a>
            @else
                <div></div>
            @endif
            @if ($nextMine)
                <a href="{{ route('mypage.user_songs.show', ['id' => $nextMine->id, 'tab' => 'mine']) }}" rel="next"
                   style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                    Next
                    <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                </a>
            @endif
        </div>
    </div>
@endsection

@section('page-script')
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
                    var isMine = btn.dataset.tabTarget === 'second-tab-panel';
                    var songNumberPerformances = document.querySelector('.song-number-performances');
                    var songNumberMine = document.querySelector('.song-number-mine');
                    if (songNumberPerformances) songNumberPerformances.style.display = isMine ? 'none' : '';
                    if (songNumberMine) songNumberMine.style.display = isMine ? '' : 'none';

                    var navPerformances = document.querySelector('.song-nav-performances');
                    var navMine = document.querySelector('.song-nav-mine');
                    if (navPerformances) navPerformances.style.display = isMine ? 'none' : 'flex';
                    if (navMine) navMine.style.display = isMine ? 'flex' : 'none';

                    // Previous/Nextで移動した先でも選んだタブをキープできるよう、URLに反映する
                    var url = new URL(window.location.href);
                    if (isMine) {
                        url.searchParams.set('tab', 'mine');
                    } else {
                        url.searchParams.delete('tab');
                    }
                    history.replaceState(null, '', url);
                });
            });
        });
    </script>
@endsection
