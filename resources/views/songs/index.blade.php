@extends('layouts.app')
@section('title', 'Yuki Official - Songs')
@section('content')
    <div class="database-hero database-year-hero">
        <div class="container">
            <h1 class="database-title">Songs</h1>
            <p class="database-subtitle">すべての楽曲コレクション</p>

            {{-- 検索フォーム（PC表示のみ） --}}
            <div class="database-search pc" style="margin-top: 30px;">
                <div>
                    <div class="search-wrapper">
                        <input type="text" name="keyword" id="keyword-pc" class="database-search-input" placeholder="楽曲を検索..." value="{{ request('keyword') }}" list="song-suggestions-pc">
                        <datalist id="song-suggestions-pc">
                            @foreach($suggestions as $suggestion)
                                <option value="{{ $suggestion['title'] }}" label="{{ $suggestion['title'] }}{{ $suggestion['artist_name'] ? ' — ' . $suggestion['artist_name'] : '' }}"></option>
                            @endforeach
                        </datalist>
                        <button type="button" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                            <i class="fa-solid fa-magnifying-glass search-icon" style="position: static; transform: none;"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="year-navigation">
                <select class="year-select" name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Discography</option>
                    <option value="{{ url('/database/singles') }}">Singles</option>
                    <option value="{{ url('/database/albums') }}">Albums</option>
                </select>
                <select class="year-select" name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Live</option>
                    <option value="{{ url('/database/live') }}">All</option>
                    <option value="{{ url('/database/live?type=1') }}">Tours</option>
                    <option value="{{ url('/database/live?type=2') }}">Events</option>
                    <option value="{{ url('/database/live?type=3') }}">ap bank fes</option>
                    <option value="{{ url('/database/live?type=4') }}">Solo</option>
                </select>
                <select class="year-select" name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Years</option>
                    @foreach ($bios as $bio)
                        <option value="{{ url('/database/years', $bio->year) }}">{{ $bio->year }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="container-lg database-year-content">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="mobile">#</th>
                    <th class="mobile">タイトル</th>
                    <th class="mobile">シングル / アルバム</th>
                    <th class="pc">リリース日</th>
                </tr>
            </thead>
            <tbody id="songs-container">
                @include('songs._list', ['songs' => $songs])
            </tbody>
        </table>
        <div class="pagination" id="pagination-links" style="display: none;">
            {!! $songs->links() !!}
        </div>
        <br>
    </div>
@endsection

@section('page-script')
    <script src="{{ asset('/js/infinite-scroll.js?v=20251101') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if($songs->hasMorePages())
                let nextUrl = '{!! $songs->nextPageUrl() !!}';
                if (window.location.protocol === 'https:') {
                    nextUrl = nextUrl.replace('http://', 'https://');
                }
                new InfiniteScroll({
                    container: '#songs-container',
                    nextPageUrl: nextUrl
                });
            @else
                // ページがない場合はページネーションを表示
                const pagination = document.getElementById('pagination-links');
                if (pagination) {
                    pagination.style.display = 'block';
                }
            @endif
        });
    </script>
    <script>
        // 検索候補のデータマップを作成
        const songMapSongs = {
            @foreach($suggestions as $suggestion)
                "{{ $suggestion['title'] }}": {{ $suggestion['id'] }},
            @endforeach
        };

        // 入力フィールドで候補選択時に曲ページへ
        const keywordInputSongs = document.getElementById('keyword-pc');
        if (keywordInputSongs) {
            // フォーム送信を防ぐ
            keywordInputSongs.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const selectedTitle = e.target.value;
                    if (songMapSongs[selectedTitle]) {
                        window.location.href = '/setlist-songs/' + songMapSongs[selectedTitle];
                    }
                }
            });
            
            keywordInputSongs.addEventListener('change', function(e) {
                const selectedTitle = e.target.value;
                if (songMapSongs[selectedTitle]) {
                    window.location.href = '/setlist-songs/' + songMapSongs[selectedTitle];
                }
            });
        }
    </script>
@endsection
