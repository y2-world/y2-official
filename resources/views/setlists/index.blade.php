@extends('layouts.app')
@section('title', 'Yuki Official - Setlists')
@section('content')
    <div class="database-hero database-year-hero">
        <div class="container">
            <div style="display: flex; justify-content: center; align-items: center; flex-wrap: wrap; gap: 15px;">
                <h1 class="database-title" style="margin-bottom: 0; text-align: center;">Setlists</h1>
                {{-- 虫眼鏡アイコン（SP表示のみ） --}}
                <button type="button" id="spSearchButtonSetlists" class="sp sp-search-button" onclick="var form = document.getElementById('spSearchFormSetlists'); var icon = this.querySelector('i'); if (form.style.display === 'none' || form.style.display === '') { form.style.display='block'; icon.className='fa-solid fa-xmark'; } else { form.style.display='none'; icon.className='fa-solid fa-magnifying-glass'; }" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; align-items: center; justify-content: center; margin-bottom: 0;">
                    <i class="fa-solid fa-magnifying-glass" style="font-size: 14px;"></i>
                </button>
            </div>

            {{-- 検索フォーム（SP表示） --}}
            <div class="sp" id="spSearchFormSetlists" style="margin-top: 20px; display: none;">
                <div>
                    <div class="search-wrapper">
                        <input type="text" name="keyword" id="keyword-sp-setlists" class="database-search-input typeahead" placeholder="楽曲を検索..." style="font-size: 14px; padding: 12px 16px;" required>
                    </div>
                </div>
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

            {{-- 検索フォーム（PC表示のみ） --}}
            <div class="database-search pc" style="margin-top: 30px;">
                <div>
                    <div class="search-wrapper">
                        <input type="text" name="keyword" id="keyword-pc" class="database-search-input typeahead" placeholder="楽曲を検索..." value="{{ request('keyword') }}" required>
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
        // Typeaheadの初期化関数
        function initTypeaheadSetlist(inputId) {
            const $input = $(inputId);
            if ($input.length && !$input.data('typeahead')) {
                var songs = new Bloodhound({
                    datumTokenizer: Bloodhound.tokenizers.obj.whitespace('title'),
                    queryTokenizer: Bloodhound.tokenizers.whitespace,
                    remote: {
                        url: '/find-setlist-song?q=%QUERY',
                        wildcard: '%QUERY'
                    },
                    limit: 20
                });

                $input.typeahead({
                    minLength: 1,
                    highlight: true,
                    hint: true,
                    classNames: {
                        menu: 'tt-menu-modern',
                        suggestion: 'tt-suggestion-modern',
                        cursor: 'tt-cursor-modern'
                    }
                },
                {
                    name: 'songs',
                    display: 'title',
                    source: songs,
                    limit: 20,
                    templates: {
                        empty: '<div class="tt-empty">該当する曲が見つかりません</div>',
                        suggestion: function(data) {
                            var artistText = data.artist ? ' - ' + data.artist : '';
                            return '<div class="tt-suggestion-content"><span>' + data.title + artistText + '</span></div>';
                        }
                    }
                }).on('typeahead:selected', function(event, data) {
                    // 選択された曲の詳細ページにリダイレクト
                    window.location.href = '/setlist-songs/' + data.id;
                });
            }
        }

        // PC表示の検索フォームを初期化
        $(document).ready(function() {
            setTimeout(function() {
                initTypeaheadSetlist('#keyword-pc');
            }, 100);

            // SP表示の検索フォームが表示された時にTypeaheadを初期化
            const spSearchForm = document.getElementById('spSearchFormSetlists');
            if (spSearchForm) {
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                            if (spSearchForm.style.display === 'block') {
                                setTimeout(function() {
                                    initTypeaheadSetlist('#keyword-sp-setlists');
                                }, 100);
                            }
                        }
                    });
                });
                observer.observe(spSearchForm, {
                    attributes: true,
                    attributeFilter: ['style']
                });
            }
        });
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
