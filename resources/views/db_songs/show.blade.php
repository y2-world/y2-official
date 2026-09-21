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
            <p class="database-subtitle" style=""># {{ $songNumber }}</p>
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

    <div class="container-lg database-year-content">

        @if (!$tours->isEmpty() || $secondTab)
            @if ($secondTab)
                {{-- secondTabがある場合のみ切り替えタブを表示する。
                     'yuki': 認証不要（/stats経由）、セットリストサイト全体＝運営者本人の参加履歴
                     'mine': ログイン中の外部ユーザー本人の参戦記録（マイページと同じデータ） --}}
                <div class="song-performance-tabs" style="display: flex; gap: 8px; margin-bottom: 15px;">
                    <button type="button" class="song-performance-tab-btn is-active" data-tab-target="live-performances-panel"
                        style="padding: 8px 16px; border: none; border-radius: 20px; background: #667eea; color: white; font-weight: 500; cursor: pointer;">
                        Live Performances
                    </button>
                    <button type="button" class="song-performance-tab-btn" data-tab-target="second-tab-panel"
                        style="padding: 8px 16px; border: 1px solid #667eea; border-radius: 20px; background: white; color: #667eea; font-weight: 500; cursor: pointer;">
                        {{ $secondTab === 'yuki' ? 'Yukiの参加履歴' : 'My Live Attendances' }}
                    </button>
                </div>
            @else
                <h3 style="margin-top: 0; margin-bottom: 15px;">Live Performances</h3>
            @endif

            <div id="live-performances-panel" @if($secondTab) style="display: block;" @endif>
                @if ($tours->isEmpty())
                    <p style="color: #718096;">演奏記録がありません。</p>
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
                <div id="second-tab-panel" style="display: none;">
                    @if ($secondTabSetlists->isEmpty())
                        <p style="color: #718096;">参加記録がありません。</p>
                    @elseif ($secondTab === 'yuki')
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
                    @else
                        {{-- DbConcert（tour）: date1/date2/titleを持つ。Live Performancesと同じ列構成 --}}
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
        @endif

        {{-- 前後リンク --}}
        <div style="display: flex; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
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
    </div>
@endsection

@section('page-script')
@if ($secondTab)
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var buttons = document.querySelectorAll('.song-performance-tab-btn');
            buttons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    buttons.forEach(function (b) {
                        b.classList.remove('is-active');
                        b.style.background = 'white';
                        b.style.color = '#667eea';
                    });
                    btn.classList.add('is-active');
                    btn.style.background = '#667eea';
                    btn.style.color = 'white';

                    document.getElementById('live-performances-panel').style.display = 'none';
                    document.getElementById('second-tab-panel').style.display = 'none';
                    document.getElementById(btn.dataset.tabTarget).style.display = 'block';
                });
            });
        });
    </script>
@endif
@endsection
