@extends('layouts.app')
@section('title', 'Yuki Official - Live')
@section('content')
    <div class="database-hero database-year-hero">
        <div class="container">
            @if (request('type') == 1)
                <h1 class="database-title">Tours</h1>
                <p class="database-subtitle">すべてのツアー情報</p>
            @elseif(request('type') == 2)
                <h1 class="database-title">Events</h1>
                <p class="database-subtitle">すべてのイベント情報</p>
            @elseif(request('type') == 3)
                <h1 class="database-title">ap bank fes</h1>
                <p class="database-subtitle">ap bank fes出演履歴</p>
            @elseif(request('type') == 4)
                <h1 class="database-title">Solo</h1>
                <p class="database-subtitle">すべてのソロ活動</p>
            @else
                <h1 class="database-title">Live</h1>
                <p class="database-subtitle">すべてのライブ情報</p>
            @endif

            <div class="year-navigation">
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

    <div class="container database-year-content">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="mobile">#</th>
                    <th class="mobile">開催日</th>
                    <th class="mobile">タイトル</th>
                    <th class="pc">会場</th>
                </tr>
            </thead>
            <tbody id="tours-container">
                @include('live._list', ['tours' => $tours])
            </tbody>
        </table>
        <div class="pagination" id="pagination-links" style="display: none;">
            {!! $tours->appends(['type' => $type])->links() !!}
        </div>
        <br>
    </div>
@endsection

@section('page-script')
    <script src="{{ asset('/js/infinite-scroll.js?v=20251101') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if($tours->hasMorePages())
                let nextUrl = '{!! $tours->appends(['type' => $type])->nextPageUrl() !!}';
                if (window.location.protocol === 'https:') {
                    nextUrl = nextUrl.replace('http://', 'https://');
                }
                new InfiniteScroll({
                    container: '#tours-container',
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
@endsection
