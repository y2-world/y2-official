@extends('layouts.app')
@section('title', 'Yuki Official - ' . $song->title)
@section('content')
    <div class="database-hero database-year-hero">
        <div class="container">
            <h1 class="database-title sp" style="margin-bottom: 20px; cursor: pointer;"
                onclick="document.getElementById('spSearchFormSetlistSong').style.display='block'; document.querySelector('.database-title.sp').style.display='none';">
                {{ $song->title }}
            </h1>
            <h1 class="database-title pc" style="margin-bottom: 20px;">{{ $song->title }}</h1>

            <div style="font-size: 1rem; color: rgba(255, 255, 255, 0.9); line-height: 1.8;">
                @if ($song->artist)
                    <div style="margin-bottom: 8px;">
                        <strong>Artist:</strong>
                        <a href="{{ url('/setlists/artists', $song->artist_id) }}"
                            style="color: white; text-decoration: underline;">
                            {{ $song->artist->name }}
                        </a>
                    </div>
                @endif
            </div>

            {{-- 検索フォーム（SP表示） --}}
            <div class="sp" id="spSearchFormSetlistSong" style="margin-top: 10px; display: none;">
                <div>
                    @livewire('song-search')
                    {{-- 閉じるボタン --}}
                    <div style="text-align: center; margin-top: 15px;">
                        <button type="button" onclick="document.getElementById('spSearchFormSetlistSong').style.display='none'; document.querySelector('.database-title.sp').style.display='block';" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-xmark" style="font-size: 16px;"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- 検索フォーム（PC表示のみ） --}}
            <div class="database-search pc" style="margin-top: 30px;">
                <div>
                    @livewire('song-search')
                </div>
            </div>
        </div>
    </div>

    <div class="container-lg database-year-content">

        @if (!$setlists->isEmpty())
            <h3 style="margin-top: 0; margin-bottom: 15px;">Live Performances</h3>
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

        {{-- 前後リンク --}}
        <div style="display: flex; justify-content: space-between; margin-top: 40px; padding-bottom: 40px;">
            @if (isset($previous))
                <a href="{{ url('/setlist-songs', $previous->id) }}" rel="prev"
                    style="display: inline-flex; align-items: center; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 25px; text-decoration: none; font-weight: 500; transition: all 0.3s ease;">
                    <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i>
                    Previous
                </a>
            @else
                <div></div>
            @endif
            @if (isset($next))
                <a href="{{ url('/setlist-songs', $next->id) }}" rel="next"
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
