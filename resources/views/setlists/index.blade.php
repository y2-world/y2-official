@php
    // 現在のページに基づく基点番号を計算
    $startNumber = ($setlists->currentPage() - 1) * $setlists->perPage() + 1;
@endphp
@extends('layouts.app')
@section('title', 'Yuki Official - Setlists')
@section('content')
    <br>
    <div class="container-lg">
        <h2>Setlists</h2>
        <div class="parts-wrapper">
            <div class="dropdown-wrapper">
                <select name="select" onchange="if (this.value) window.location.href=this.value;">
                    <option value="" disabled selected>Live Type</option>
                    <option value="{{ url('/setlists') }}" {{ request('type') ? '' : 'selected' }}>All</option>
                    <option value="{{ url('/setlists?type=1') }}" {{ request('type') == '1' ? 'selected' : '' }}>Live
                    </option>
                    <option value="{{ url('/setlists?type=2') }}" {{ request('type') == '2' ? 'selected' : '' }}>Fes</option>
                </select>
                <select name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Artists</option>
                    @foreach ($artists as $artist)
                        <option value="{{ url('/setlists/artists', $artist->id) }}">{{ $artist->name }}</option>
                    @endforeach
                </select>
                <select name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Years</option>
                    @foreach ($years as $year)
                        <option value="{{ url('/setlists/years', $year->year) }}">{{ $year->year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="pc">
                <div class="search">
                    <form action="{{ url('/search') }}" method="GET">
                        <div class="mb_dropdown">
                            <select name="artist_id" required data-toggle="select">
                                <option value="" disabled selected>Artists</option>
                                <option value="">(No Artist Selected)</option>
                                @foreach ($allArtists as $artist)
                                    <option value="{{ $artist->id }}"
                                        {{ request('artist_id') == $artist->id ? 'selected' : '' }}>
                                        {{ $artist->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 一致タイプ選択 --}}
                        <div class="mb_dropdown">
                            <label class="small-label">
                                <input type="radio" name="match_type" value="exact"
                                    {{ request('match_type', 'exact') === 'exact' ? 'checked' : '' }}>
                                完全一致
                            </label>
                            <label class="small-label" style="margin-left: 20px;">
                                <input type="radio" name="match_type" value="partial"
                                    {{ request('match_type') === 'partial' ? 'checked' : '' }}>
                                部分一致
                            </label>
                        </div>
                        <div class="input-group mb-3" style="width: 400px;">
                            <input type="search" class="form-control" aria-label="Search" value="{{ request('keyword') }}"
                                name="keyword" required>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="submit" id="button-addon2">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
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
                @include('setlists._list', ['setlists' => $setlists, 'totalCount' => $totalCount, 'type' => $type])
            </tbody>
        </table>
        <div class="pagination" id="pagination-links" style="display: none;">
            {!! $setlists->appends(['type' => $type])->links() !!}
        </div>
        <br>
    </div>
@endsection

@section('page-script')
    <script src="{{ asset('/js/search.js?v=20251101') }}"></script>
    <script src="{{ asset('/js/infinite-scroll.js?v=20251101') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded');
            console.log('Has more pages: {{ $setlists->hasMorePages() ? "true" : "false" }}');
            console.log('InfiniteScroll class available:', typeof InfiniteScroll);

            @if($setlists->hasMorePages())
                console.log('Initializing InfiniteScroll...');
                let nextUrl = '{!! $setlists->appends(['type' => $type])->nextPageUrl() !!}';
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
