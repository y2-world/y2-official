@extends('layouts.app')
@section('title', 'Yuki Official - Database')
@section('content')
    {{-- アーティストの Database のページと同じく、PC はタイトルの右にクイック楽曲検索（全アーティストの曲から） --}}
    <div class="database-hero database-hero--nav">
        <div class="container" style="position: relative;">
            <div class="setlists-header-row">
                <div style="flex-shrink: 0;">
                    <h1 class="database-title" style="white-space: nowrap;">Database</h1>
                    <p class="database-subtitle" style="margin: 4px 0 0;">アーティストのライブ・楽曲データベース</p>
                </div>
                <div class="setlists-search-pc" style="min-width: 320px; position: relative; overflow: visible; flex-shrink: 0;">
                    @livewire('database-song-search')
                </div>
            </div>
        </div>
    </div>

    <div class="container database-content sp-pt-24">
        <div class="timeline-filter-row">
            <select class="timeline-user-select" onchange="if (this.value) window.location.href=this.value;">
                <option value="{{ url('/database') }}" selected>すべて</option>
                @foreach ($artists as $artist)
                    <option value="{{ route('database.artist', $artist->id) }}">{{ $artist->name }}</option>
                @endforeach
            </select>
        </div>
        {{-- スマホはアーティストの選択の下にクイック楽曲検索 --}}
        <div class="sp" style="margin-bottom: 20px;">
            @livewire('database-song-search')
        </div>
        <div class="row justify-content-center">
            @foreach ($artists as $artist)
                <div class="col-lg-4 mb-4">
                    <div class="database-card">
                        <div class="card-icon">
                            <i class="fa-solid fa-music"></i>
                        </div>
                        <h3 class="card-title">{{ $artist->name }}</h3>
                        @if ($artist->kana)
                            <p class="card-description">{{ $artist->kana }}</p>
                        @endif
                        <p class="card-description">{{ $artist->songs_count }}曲 / {{ $artist->tours_count }}ツアー</p>
                        {{-- リンクの名前の前にアイコン（Stats は setlists の Stats ボタンと同じ棒グラフ、Live はアーティストの Database のページの Live と同じギター） --}}
                        <div class="card-links">
                            <a href="{{ route('stats.index', ['tab' => 'database', 'artist_id' => $artist->id]) }}" class="database-link">
                                <span><i class="fa-solid fa-chart-simple" style="margin-right: 10px;"></i>Stats</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                            <a href="{{ route('database.live', $artist->id) }}" class="database-link">
                                <span><i class="fa-solid fa-guitar" style="margin-right: 10px;"></i>Live</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                            <a href="{{ route('database.songs', $artist->id) }}" class="database-link">
                                <span><i class="fa-solid fa-music" style="margin-right: 10px;"></i>Songs</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                            <a href="{{ route('database.artist', $artist->id) }}" class="database-link">
                                <span><i class="fa-solid fa-database" style="margin-right: 10px;"></i>View All</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
