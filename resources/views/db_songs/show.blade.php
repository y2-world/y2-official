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
            <p class="database-subtitle" style=""># {{ $songNumber }}</p>
            <h1 class="database-title sp" style="margin-bottom: 20px; cursor: pointer;"
                onclick="document.getElementById('spSearchFormSongs').style.display='block'; document.querySelector('.database-title.sp').style.display='none';">
                {{ $songs->title }}
            </h1>
            <h1 class="database-title pc" style="">{{ $songs->title }}</h1>

            @php
                $single = $songs->singleFromTracklist;
                $album = $songs->albumFromTracklist;
            @endphp
            @if ($single)
                <p class="database-subtitle" style="font-weight: bold;"><strong>Single:</strong> <a href="{{ route('singles.show', $single->id) }}" style="color: white; text-decoration: underline;">{{ $single->title }}</a></p>
                @if ($single->date)
                    <p class="database-subtitle">Release: {{ date('Y.m.d', strtotime($single->date)) }}</p>
                @endif
            @endif
            @if ($album)
                <p class="database-subtitle" style="font-weight: bold;"><strong>Album:</strong> <a href="{{ route('albums.show', $album->id) }}" style="color: white; text-decoration: underline;">{{ $album->title }}</a></p>
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

        @if (!$tours->isEmpty())
            <h3 style="margin-top: 0; margin-bottom: 15px;">Live Performances</h3>
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
                            <td class="td_title"><a href="{{ route('live.show', [$tour->artist_id, $tour->id]) }}">{{ $tour->title }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
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
@endsection
