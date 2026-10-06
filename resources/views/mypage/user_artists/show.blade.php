@extends('layouts.app')
@section('title', 'Yuki Official - ' . $artist->name . ' Database')
@section('og_title', $artist->name . ' Database - Yuki Official')

{{-- ユーザーが登録したアーティストのトップ。Databaseのアーティストのトップ（database/artist）と同じ形で、
     シングル・アルバム・年ごとのページは無いので、Live と Discography（Songs）だけ --}}
@section('content')
    <div class="database-hero database-hero--nav">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => 'Database', 'url' => route('mypage.user_artists.index')],
                ['label' => $artist->name],
            ]])
            <div class="setlists-header-row">
                <h1 class="database-title" style="white-space: nowrap; flex-shrink: 0;">{{ $artist->name }} Database</h1>
                <div class="setlists-search-pc" style="min-width: 320px; position: relative; overflow: visible; flex-shrink: 0;">
                    @livewire('user-artist-song-search', ['artistId' => $artist->id])
                </div>
            </div>

            {{-- 検索フォーム（SP表示） --}}
            <div class="sp" id="spSearchFormDatabase" style="margin-top: 15px;">
                @livewire('user-artist-song-search', ['artistId' => $artist->id])
            </div>
        </div>
    </div>

    <div class="container database-content">
        <div class="row justify-content-center">
            <!-- Live Card -->
            <div class="col-lg-4 mb-4">
                <div class="database-card">
                    <div class="card-icon">
                        <i class="fa-solid fa-guitar"></i>
                    </div>
                    <h3 class="card-title">Live</h3>
                    <p class="card-description">セットリスト統計、すべてのツアー（{{ $artist->concerts_count }}ツアー）</p>
                    <div class="card-links">
                        <a href="{{ route('mypage.user_artists.stats', $artist->id) }}" class="database-link">
                            <span>Stats</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <a href="{{ route('mypage.user_artists.live', $artist->id) }}" class="database-link">
                            <span>All</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Discography Card -->
            <div class="col-lg-4 mb-4">
                <div class="database-card">
                    <div class="card-icon">
                        <i class="fa-solid fa-music"></i>
                    </div>
                    <h3 class="card-title">Discography</h3>
                    <p class="card-description">すべての楽曲（{{ $artist->songs_count }}曲）・シングル・アルバム</p>
                    <div class="card-links">
                        <a href="{{ route('mypage.user_artists.songs', $artist->id) }}" class="database-link">
                            <span>Songs</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <a href="{{ route('mypage.user_artists.singles', $artist->id) }}" class="database-link">
                            <span>Singles</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                        <a href="{{ route('mypage.user_artists.albums', $artist->id) }}" class="database-link">
                            <span>Albums</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 1rem;">
            <a href="{{ route('mypage.user_artists.index') }}" style="color: #888; font-size: 0.9rem;">
                <i class="fa-solid fa-arrow-left"></i> アーティスト一覧に戻る
            </a>
        </div>
    </div>
@endsection
