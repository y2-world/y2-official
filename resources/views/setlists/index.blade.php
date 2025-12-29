@extends('layouts.app')
@section('title', 'Yuki Official - Setlists')

@section('og_title', 'Setlists - Yuki Official')
@section('og_description', 'Browse all setlists from Yuki Yoshida performances')
@section('og_type', 'website')

@section('content')
    <div class="database-hero database-year-hero">
        <div class="container">
            <h1 class="database-title" style="margin-bottom: 0; text-align: center;">Setlists</h1>

            <div class="year-navigation" style="display: flex; align-items: center; gap: 10px;">
                {{-- 虫眼鏡アイコン（SP表示のみ・ドロップダウンの左端） --}}
                <button type="button" id="spSearchButtonSetlists" class="sp" onclick="var form = document.getElementById('spSearchFormSetlists'); var icon = this.querySelector('i'); if (form.style.display === 'none' || form.style.display === '') { form.style.display='block'; icon.className='fa-solid fa-xmark'; } else { form.style.display='none'; icon.className='fa-solid fa-magnifying-glass'; }" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fa-solid fa-magnifying-glass" style="font-size: 14px;"></i>
                </button>
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
            <div class="sp" id="spSearchFormSetlists" style="margin-top: 20px; display: none;">
                <div>
                    @livewire('song-search')
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
    <script src="{{ asset('/js/infinite-scroll.js?v=20251110e') }}"></script>
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
