@extends('layouts.app')
@section('title', 'Yuki Official - ' . $artist->name . ' Live')
@section('content')
    <div class="database-hero database-hero--nav">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
            ['label' => 'Database', 'url' => '/database'],
            ['label' => $artist->name, 'url' => route('database.artist', $artist->id)],
            ['label' => 'Live'],
        ]])
            @php
                $liveTitle = match(request('type')) {
                    '1' => 'Live', '6' => 'Tours', '5' => '単発ライブ',
                    '2' => 'Events', '3' => 'ap bank fes', '4' => 'Solo',
                    default => 'Live'
                };
                $liveSubtitle = match(request('type')) {
                    '1' => 'すべてのツアー・単発ライブ情報', '6' => 'すべてのツアー情報',
                    '5' => 'すべての単発ライブ情報', '2' => 'すべてのイベント情報',
                    '3' => 'ap bank fes出演履歴', '4' => 'すべてのソロ活動',
                    default => 'すべてのライブ情報'
                };
            @endphp
            <div class="setlists-header-row">
                <div style="flex-shrink: 0;">
                    <h1 class="database-title" style="white-space: nowrap;">{{ $liveTitle }}</h1>
                    <p class="database-subtitle" style="margin: 4px 0 0;">{{ $artist->name }} — {{ $liveSubtitle }}</p>
                </div>
                <div class="header-selects" style="display: flex; align-items: center; gap: 10px; flex-wrap: nowrap; overflow-x: auto; max-width: 100%;">
                    <button type="button" id="spSearchButtonConcerts" class="sp" onclick="var form = document.getElementById('spSearchFormConcerts'); var icon = this.querySelector('i'); if (form.style.display === 'none' || form.style.display === '') { form.style.display='block'; icon.className='fa-solid fa-xmark'; } else { form.style.display='none'; icon.className='fa-solid fa-magnifying-glass'; }" style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 8px; border-radius: 50%; cursor: pointer; width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i class="fa-solid fa-magnifying-glass" style="font-size: 14px;"></i>
                    </button>
                    <select class="year-select" name="select" onChange="location.href=value;">
                        <option value="" disabled selected>Discography</option>
                        <option value="{{ route('database.songs', $artist->id) }}">Songs</option>
                        <option value="{{ route('database.singles', $artist->id) }}">Singles</option>
                        <option value="{{ route('database.albums', $artist->id) }}">Albums</option>
                    </select>
                    <select class="year-select" name="select" onChange="location.href=value;">
                        <option value="" disabled>Live</option>
                        <option value="{{ route('database.live', $artist->id) }}" {{ !request('type') ? 'selected' : '' }}>All</option>
                        <option value="{{ route('database.live', $artist->id) }}?type=1" {{ request('type') == '1' ? 'selected' : '' }}>Live</option>
                        <option value="{{ route('database.live', $artist->id) }}?type=6" {{ request('type') == '6' ? 'selected' : '' }}>Tours</option>
                        <option value="{{ route('database.live', $artist->id) }}?type=5" {{ request('type') == '5' ? 'selected' : '' }}>単発ライブ</option>
                        <option value="{{ route('database.live', $artist->id) }}?type=2" {{ request('type') == '2' ? 'selected' : '' }}>Events</option>
                        @if($artist->name === 'Mr.Children')
                        <option value="{{ route('database.live', $artist->id) }}?type=3" {{ request('type') == '3' ? 'selected' : '' }}>ap bank fes</option>
                        <option value="{{ route('database.live', $artist->id) }}?type=4" {{ request('type') == '4' ? 'selected' : '' }}>Solo</option>
                        @endif
                    </select>
                    <select class="year-select" name="select" onChange="location.href=value;">
                        <option value="" disabled selected>Years</option>
                        @foreach ($bios as $bio)
                            <option value="{{ route('database.biography.year', [$artist->id, $bio->year]) }}">{{ $bio->year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="setlists-search-pc" style="min-width: 320px; position: relative; overflow: visible; flex-shrink: 0;">
                    @livewire('database-song-search', ['artistId' => $artist->id])
                </div>
            </div>

            {{-- 検索フォーム（SP表示） --}}
            <div class="sp" id="spSearchFormConcerts" style="margin-top: 15px; display: none;">
                @livewire('database-song-search', ['artistId' => $artist->id])
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
                @include('db_concerts._list', ['tours' => $tours])
            </tbody>
        </table>
        </div>
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
                const pagination = document.getElementById('pagination-links');
                if (pagination) {
                    pagination.style.display = 'block';
                }
            @endif
        });
    </script>
@endsection
