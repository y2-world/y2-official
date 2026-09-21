@extends('layouts.app')
@section('title', 'Yuki Official - ' . $songs->title)

@section('og_title', $songs->title . ' - Yuki Official')
@section('og_description', 'Song ID: ' . $songs->id . ' - ' . $songs->title)
@section('og_type', 'music.song')

@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
            ['label' => 'Database', 'url' => '/database'],
            ['label' => $songs->artist->name, 'url' => route('database.artist', $songs->artist_id)],
            ['label' => 'Songs', 'url' => route('database.songs', $songs->artist_id)],
            ['label' => $songs->title],
        ]])
            <p class="database-subtitle" style="">
                <a href="{{ route('database.artist', $songs->artist_id) }}">{{ $songs->artist->name }}</a>
            </p>
            {{-- #（曲番）は選んでいるタブによって意味が変わる：Live Performances中はアーティスト内の
                 sort_order順位（常に存在、初期表示）。2つ目のタブ（未ログインなら存在しない）中は、
                 'yuki'なら対応するSlSongのid順位（まだSlSongに紐付いていなければ非表示）、
                 'mine'なら自分の参加記録内での初登場順（まだ聴いた記録がなければ非表示）。 --}}
            <p class="database-subtitle song-number-performances" style="{{ $initialTab !== 'performances' ? 'display: none;' : '' }}"># {{ $songNumber }}</p>
            @if ($secondTabSongNumber)
                <p class="database-subtitle song-number-second" style="{{ $initialTab !== 'performances' ? '' : 'display: none;' }}"># {{ $secondTabSongNumber }}</p>
            @endif
            <h1 class="database-title sp" style="margin-bottom: 4px; cursor: pointer;"
                onclick="document.getElementById('spSearchFormSongs').style.display='block'; document.querySelector('.database-title.sp').style.display='none';">
                {{ $songs->title }}
            </h1>
            <h1 class="database-title pc" style="">{{ $songs->title }}</h1>

            @php
                $single = $songs->singleFromTracklist;
                $album = $songs->albumFromTracklist;
            @endphp
            @if ($single)
                <p class="database-subtitle" style="font-weight: 400;"><strong>Single:</strong> <a href="{{ route('singles.show', $single->id) }}" style="color: white; text-decoration: underline;">{{ $single->title }}</a></p>
                @if ($single->date)
                    <p class="database-subtitle">Release: {{ date('Y.m.d', strtotime($single->date)) }}</p>
                @endif
            @endif
            @if ($album)
                <p class="database-subtitle" style="font-weight: 400;"><strong>Album:</strong> <a href="{{ route('albums.show', $album->id) }}" style="color: white; text-decoration: underline;">{{ $songs->albumDisplayTitleFromTracklist }}</a></p>
                @if ($album->date)
                    <p class="database-subtitle">Release: {{ date('Y.m.d', strtotime($album->date)) }}</p>
                @endif
            @else
                <p class="database-subtitle">アルバム未収録</p>
            @endif
            @if ($songs->text)
                <p class="database-subtitle" style="margin-top: 8px;">{{ $songs->text }}</p>
            @endif

            {{-- 検索フォーム（SP表示） --}}
             <div class="sp" id="spSearchFormSongs" style="margin-top: 10px; display: none;">
                <div>
                    @livewire('database-song-search', ['artistId' => $songs->artist_id])
                    {{-- 閉じるボタン --}}
                    <div style="text-align: center; margin-top: 15px;">
                        <button type="button" onclick="document.getElementById('spSearchFormSongs').style.display='none'; document.querySelector('.database-title.sp').style.display='block';" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-xmark" style="font-size: 16px;"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- 検索フォーム（PC表示のみ） --}}
            <div class="database-search pc song-search-top-right" style="width: 320px; max-width: 320px;">
                <div>
                    @livewire('database-song-search', ['artistId' => $songs->artist_id])
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
        @if ($secondTab)
            <div class="song-performance-tabs" style="display: flex; gap: 8px; margin-bottom: 15px;">
                <button type="button" class="song-performance-tab-btn @if($initialTab === 'performances') is-active @endif" data-tab-target="live-performances-panel"
                    style="padding: 8px 16px; border-radius: 20px; font-weight: 500; cursor: pointer; {{ $initialTab === 'performances' ? 'border: none; background: #667eea; color: white;' : 'border: 1px solid #667eea; background: white; color: #667eea;' }}">
                    Live Performances
                </button>
                <button type="button" class="song-performance-tab-btn @if($initialTab !== 'performances') is-active @endif" data-tab-target="second-tab-panel"
                    style="padding: 8px 16px; border-radius: 20px; font-weight: 500; cursor: pointer; {{ $initialTab !== 'performances' ? 'border: none; background: #667eea; color: white;' : 'border: 1px solid #667eea; background: white; color: #667eea;' }}">
                    {{ $secondTab === 'yuki' ? "Yuki's Live Attendances" : 'My Live Attendances' }}
                </button>
            </div>
        @else
            <h3 style="margin-top: 0; margin-bottom: 15px;">Live Performances</h3>
        @endif

        <div id="live-performances-panel" style="display: {{ $initialTab !== 'performances' ? 'none' : 'block' }};">
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
                                @if (isset($tour->date1) && isset($tour->date2))
                                    <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }} -
                                        {{ date('Y.m.d', strtotime($tour->date2)) }}</td>
                                @elseif(isset($tour->date1) && !isset($tour->date2))
                                    <td class="td_date">{{ date('Y.m.d', strtotime($tour->date1)) }}</td>
                                @endif
                                <td class="td_title"><a href="{{ route('live.show', $tour->id) }}">{{ $tour->title }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        @if ($secondTab)
        <div id="second-tab-panel" style="display: {{ $initialTab !== 'performances' ? 'block' : 'none' }};">
            @if ($secondTabSetlists->isEmpty())
                <p style="color: #718096; text-align: center;">参加記録がありません。</p>
            @else
                {{-- SlSetlist: date/title/venueを直接持つ --}}
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
                        @foreach ($secondTabSetlists as $setlist)
                            <tr>
                                <td></td>
                                <td class="td_date">{{ date('Y.m.d', strtotime($setlist->date)) }}</td>
                                <td class="td_title"><a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a></td>
                                <td class="pc"><a href="{{ url('/venue?keyword=' . urlencode($setlist->venue)) }}">{{ $setlist->venue }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        @endif

        {{-- 前後リンクも#と同じくタブに応じて意味が変わる。'yuki'側は対応するDbSongをsort_order順
             にたどり、db_song_idで逆引きしたSlSongへリンクする。'mine'側は自分の参加記録内での
             初めて聴いた順の前後のDbSongへ直接リンクする（タブを切り替えたままページ間を
             移動できるようにするため）。未ログイン（$secondTabがnull）の場合はLive Performances
             のみなので、常にこちら（song-nav-performances）を表示する。 --}}
        <div class="song-nav-performances" style="display: {{ $initialTab !== 'performances' ? 'none' : 'flex' }}; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
            @if (isset($previous))
                <a href="{{ route('songs.show', $previous->id) }}" rel="prev"
                    style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                    <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                    Previous
                </a>
            @else
                <div></div>
            @endif
            @if (isset($next))
                <a href="{{ route('songs.show', $next->id) }}" rel="next"
                    style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                    Next
                    <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
                </a>
            @endif
        </div>
        @if ($secondTabSongNumber)
            <div class="song-nav-second" style="display: {{ $initialTab !== 'performances' ? 'flex' : 'none' }}; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
                @if ($secondTabPrevious)
                    <a href="{{ $secondTab === 'mine' ? route('songs.show', ['id' => $secondTabPrevious->id, 'tab' => 'mine']) : url('/setlists/songs', $secondTabPrevious->id) }}" rel="prev"
                        style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                        <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                        Previous
                    </a>
                @else
                    <div></div>
                @endif
                @if ($secondTabNext)
                    <a href="{{ $secondTab === 'mine' ? route('songs.show', ['id' => $secondTabNext->id, 'tab' => 'mine']) : url('/setlists/songs', $secondTabNext->id) }}" rel="next"
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
                    // 同じタイミングで表示を切り替える。2つ目のタブの種類（mine/yuki）は
                    // ログイン有無だけで決まり、ページ内では切り替わらない。
                    var isSecond = btn.dataset.tabTarget === 'second-tab-panel';
                    var songNumberPerformances = document.querySelector('.song-number-performances');
                    var songNumberSecond = document.querySelector('.song-number-second');
                    if (songNumberPerformances) songNumberPerformances.style.display = isSecond ? 'none' : '';
                    if (songNumberSecond) songNumberSecond.style.display = isSecond ? '' : 'none';

                    var navPerformances = document.querySelector('.song-nav-performances');
                    var navSecond = document.querySelector('.song-nav-second');
                    if (navPerformances) navPerformances.style.display = isSecond ? 'none' : 'flex';
                    if (navSecond) navSecond.style.display = isSecond ? 'flex' : 'none';

                    // 2つ目のタブを見ながらPrevious/Nextで移動した先でも同じタブをキープできる
                    // よう、?tab=mine だけURLに反映する（デフォルトはLive Performancesなので
                    // そちらに戻す場合はクエリを外す）
                    var url = new URL(window.location.href);
                    if (isSecond) {
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
