@extends('layouts.app')
@section('title', 'Yuki Official - Setlists')
@section('content')
    <div class="database-hero database-year-hero">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <h1 class="database-title" style="margin-bottom: 0;">Setlists</h1>
                {{-- 虫眼鏡アイコン（SP表示のみ） --}}
                <button type="button" id="spSearchButtonSetlists" class="sp" onclick="document.getElementById('spSearchFormSetlists').style.display='block'; this.style.display='none';" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 12px; border-radius: 50%; cursor: pointer; width: 48px; height: 48px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 0;">
                    <i class="fa-solid fa-magnifying-glass" style="font-size: 18px;"></i>
                </button>
            </div>

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

            {{-- 検索フォーム（SP表示） --}}
            <div class="sp" id="spSearchFormSetlists" style="margin-top: 30px; display: none;">
                <div>
                    <div class="search-wrapper">
                        <input type="text" name="keyword" id="keyword-sp-setlists" class="database-search-input" placeholder="楽曲を検索..." style="font-size: 14px; padding: 12px 45px 12px 16px;" list="song-suggestions-sp-setlists">
                        <datalist id="song-suggestions-sp-setlists">
                            @foreach($suggestions as $suggestion)
                                <option value="{{ $suggestion['title'] }}" label="{{ $suggestion['title'] }}{{ $suggestion['artist_name'] ? ' — ' . $suggestion['artist_name'] : '' }}"></option>
                            @endforeach
                        </datalist>
                        <button type="button" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;">
                            <i class="fa-solid fa-magnifying-glass search-icon" style="position: static; transform: none; font-size: 16px;"></i>
                        </button>
                    </div>
                    {{-- 閉じるボタン --}}
                    <div style="text-align: center; margin-top: 15px;">
                        <button type="button" onclick="document.getElementById('spSearchFormSetlists').style.display='none'; document.getElementById('spSearchButtonSetlists').style.display='block';" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center;">
                            <i class="fa-solid fa-xmark" style="font-size: 16px;"></i>
                        </button>
                    </div>
                </div>
            </div>

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
                                @if (isset($setlist->artist_id) && $setlist->artist)
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

        // 検索フォームの入力フィールド（PC）
        const keywordInputSetlist = document.getElementById('keyword-pc');
        if (keywordInputSetlist) {
            // フォーム送信を防ぐ
            keywordInputSetlist.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const selectedTitle = e.target.value;
                    if (songMapSetlist[selectedTitle]) {
                        window.location.href = '/setlist-songs/' + songMapSetlist[selectedTitle];
                    }
                }
            });
            
            keywordInputSetlist.addEventListener('change', function(e) {
                const selectedTitle = e.target.value;
                if (songMapSetlist[selectedTitle]) {
                    // 候補から選択された場合は詳細ページへ
                    window.location.href = '/setlist-songs/' + songMapSetlist[selectedTitle];
                }
            });
        }

        // 検索フォームの入力フィールド（SP）
        const keywordInputSpSetlists = document.getElementById('keyword-sp-setlists');
        if (keywordInputSpSetlists) {
            // フォーム送信を防ぐ
            keywordInputSpSetlists.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const selectedTitle = e.target.value;
                    if (songMapSetlist[selectedTitle]) {
                        window.location.href = '/setlist-songs/' + songMapSetlist[selectedTitle];
                    }
                }
            });
            
            keywordInputSpSetlists.addEventListener('change', function(e) {
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
