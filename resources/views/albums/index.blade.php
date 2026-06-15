@extends('layouts.app')
@section('title', 'Yuki Official - ' . $artist->name . ' Albums')
@section('content')
    <div class="database-hero database-year-hero">
        <div class="container">
            <h1 class="database-title">Albums</h1>
            <p class="database-subtitle">{{ $artist->name }} — すべてのアルバムコレクション</p>

            <div class="year-navigation">
                <select class="year-select" name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Discography</option>
                    <option value="{{ route('database.songs', $artist->id) }}">Songs</option>
                    <option value="{{ route('database.singles', $artist->id) }}">Singles</option>
                </select>
                <select class="year-select" name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Live</option>
                    <option value="{{ route('database.live', $artist->id) }}">All</option>
                    <option value="{{ route('database.live', $artist->id) }}?type=1">Tours</option>
                    <option value="{{ route('database.live', $artist->id) }}?type=2">Events</option>
                    @if($artist->name === 'Mr.Children')
                    <option value="{{ route('database.live', $artist->id) }}?type=3">ap bank fes</option>
                    <option value="{{ route('database.live', $artist->id) }}?type=4">Solo</option>
                    @endif
                </select>
                <select class="year-select" name="select" onChange="location.href=value;">
                    <option value="" disabled selected>Years</option>
                    @foreach ($bios as $bio)
                        <option value="{{ route('database.biography.year', [$artist->id, $bio->year]) }}">{{ $bio->year }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="container-lg database-year-content" style="margin-bottom: 2rem;">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="mobile">#</th>
                    <th class="mobile">タイトル</th>
                    <th class="mobile">リリース日</th>
                </tr>
            </thead>
            <tbody id="albums-container">
                @include('albums._list', ['albums' => $albums])
            </tbody>
        </table>
        <div class="pagination" id="pagination-links" style="display: none;">
            {!! $albums->onEachSide(5)->links() !!}
        </div>
    </div>
@endsection

@section('page-script')
    <script src="{{ asset('/js/infinite-scroll.js?v=20251101') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if($albums->hasMorePages())
                let nextUrl = '{!! $albums->nextPageUrl() !!}';
                if (window.location.protocol === 'https:') {
                    nextUrl = nextUrl.replace('http://', 'https://');
                }
                new InfiniteScroll({
                    container: '#albums-container',
                    nextPageUrl: nextUrl
                });
            @else
                const pagination = document.getElementById('pagination-links');
                if (pagination) {
                    pagination.style.display = 'block';
                }
            @endif
        });
    </script>
@endsection
