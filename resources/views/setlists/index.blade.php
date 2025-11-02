@extends('layouts.app')
@section('title', 'Yuki Official - Setlists')
@section('content')
    <div class="database-hero database-year-hero">
        <div class="container">
            <h1 class="database-title">Setlists</h1>
            <p class="database-subtitle">すべてのセットリスト</p>

            <div class="year-navigation">
                <select class="year-select" name="select" onchange="if (this.value) window.location.href=this.value;">
                    <option value="" disabled selected>Live Type</option>
                    <option value="{{ url('/setlists') }}" {{ request('type') ? '' : 'selected' }}>All</option>
                    <option value="{{ url('/setlists?type=1') }}" {{ request('type') == '1' ? 'selected' : '' }}>Live</option>
                    <option value="{{ url('/setlists?type=2') }}" {{ request('type') == '2' ? 'selected' : '' }}>Fes</option>
                </select>
                <select class="year-select" name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Artists</option>
                    @foreach ($artists as $artist)
                        <option value="{{ url('/setlists/artists', $artist->id) }}">{{ $artist->name }}</option>
                    @endforeach
                </select>
                <select class="year-select" name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Years</option>
                    @foreach ($years as $year)
                        <option value="{{ url('/setlists/years', $year->year) }}">{{ $year->year }}</option>
                    @endforeach
                </select>
            </div>

            {{-- 検索フォーム（PC表示のみ） --}}
            <div class="database-search pc" style="margin-top: 30px;">
                <form action="{{ url('/search') }}" method="GET" id="setlist-search-form">
                    <input type="hidden" name="match_type" value="partial">
                    <div class="search-wrapper">
                        <input type="text" name="keyword" id="keyword-setlist" class="database-search-input" placeholder="楽曲を検索..." value="{{ request('keyword') }}" list="song-suggestions-setlist">
                        <datalist id="song-suggestions-setlist">
                            @foreach($suggestions as $suggestion)
                                <option value="{{ $suggestion['title'] }}"></option>
                            @endforeach
                        </datalist>
                        <button type="submit" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                            <i class="fa-solid fa-magnifying-glass search-icon" style="position: static; transform: none;"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="container-lg database-year-content">

        {{-- これからのライブ --}}
        @if($upcomingSetlists->count() > 0)
            <h3 style="margin-top: 0; margin-bottom: 15px;">Upcoming Shows</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th class="mobile">#</th>
                        <th class="mobile">開催日</th>
                        @if (request('type') != 2)
                            <th class="sp">アーティスト / タイトル</th>
                            <th class="pc">アーティスト</th>
                        @endif
                        @if (request('type') == 2)
                            <th class="pc"></th>
                            <th class="sp">タイトル</th>
                            <th class="pc">タイトル</th>
                        @else
                            <th class="pc">タイトル</th>
                        @endif
                        <th class="pc">会場</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($upcomingSetlists as $index => $setlist)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ date('Y.m.d', strtotime($setlist->date)) }}</td>
                            @if (request('type') != 2)
                                @if (isset($setlist->artist_id))
                                    <td class="sp">
                                        <a href="{{ url('/setlists/artists', $setlist->artist_id) }}">{{ $setlist->artist->name }}</a>
                                        /
                                        <a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a>
                                    </td>
                                    <td class="pc">
                                        <a href="{{ url('/setlists/artists', $setlist->artist_id) }}">{{ $setlist->artist->name }}</a>
                                    </td>
                                @else
                                    <td class="sp">
                                        <a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a>
                                    </td>
                                    <td class="pc"></td>
                                @endif
                            @endif
                            @if (request('type') == 2)
                                <td class="pc"></td>
                                <td class="sp">
                                    <a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a>
                                </td>
                                <td class="pc">
                                    <a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a>
                                </td>
                            @else
                                <td class="pc">
                                    <a href="{{ route('setlists.show', $setlist->id) }}">{{ $setlist->title }}</a>
                                </td>
                            @endif
                            <td class="pc">
                                <a href="{{ url('/venue?keyword=' . urlencode($setlist->venue)) }}">{{ $setlist->venue }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- 今までのライブ --}}
        <h3 style="margin-top: {{ $upcomingSetlists->count() > 0 ? '30px' : '0' }}; margin-bottom: 15px;">Past Shows</h3>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="mobile">#</th>
                    <th class="mobile">開催日</th>
                    @if (request('type') != 2)
                        <th class="sp">アーティスト / タイトル</th>
                        <th class="pc">アーティスト</th>
                    @endif
                    @if (request('type') == 2)
                        <th class="pc"></th>
                        <th class="sp">タイトル</th>
                        <th class="pc">タイトル</th>
                    @else
                        <th class="pc">タイトル</th>
                    @endif
                    <th class="pc">会場</th>
                </tr>
            </thead>
            <tbody id="setlists-container">
                @include('setlists._list', ['setlists' => $pastSetlists, 'totalCount' => $pastTotalCount, 'type' => $type])
            </tbody>
        </table>
        <div class="pagination" id="pagination-links" style="display: none;">
            {!! $pastSetlists->appends(['type' => $type])->links() !!}
        </div>
        <br>
    </div>
@endsection

@section('page-script')
    <script src="{{ asset('/js/search.js?v=20251101') }}"></script>
    <script src="{{ asset('/js/infinite-scroll.js?v=20251101') }}"></script>
    <script>
        // 検索候補のデータマップを作成
        const songMapSetlist = {
            @foreach($suggestions as $suggestion)
                "{{ $suggestion['title'] }}": {{ $suggestion['id'] }},
            @endforeach
        };

        // 検索フォームの入力フィールド
        const keywordInputSetlist = document.getElementById('keyword-setlist');
        if (keywordInputSetlist) {
            keywordInputSetlist.addEventListener('change', function(e) {
                const selectedTitle = e.target.value;
                if (songMapSetlist[selectedTitle]) {
                    // 候補から選択された場合は詳細ページへ
                    window.location.href = '/setlist-songs/' + songMapSetlist[selectedTitle];
                }
            });
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded');
            console.log('Has more pages: {{ $pastSetlists->hasMorePages() ? "true" : "false" }}');
            console.log('InfiniteScroll class available:', typeof InfiniteScroll);

            @if($pastSetlists->hasMorePages())
                console.log('Initializing InfiniteScroll...');
                let nextUrl = '{!! $pastSetlists->appends(['type' => $type])->nextPageUrl() !!}';
                // 本番環境では強制的にHTTPSにする
                if (window.location.protocol === 'https:') {
                    nextUrl = nextUrl.replace('http://', 'https://');
                }
                const infiniteScroll = new InfiniteScroll({
                    container: '#setlists-container',
                    nextPageUrl: nextUrl
                });
                console.log('InfiniteScroll instance:', infiniteScroll);
            @else
                console.log('No more pages, skipping InfiniteScroll initialization');
                // ページがない場合はページネーションを表示
                const pagination = document.getElementById('pagination-links');
                if (pagination) {
                    pagination.style.display = 'block';
                }
            @endif
        });
    </script>
@endsection
