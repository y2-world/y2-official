@extends('layouts.app')
@section('title', "Database - My Page")
@section('og_title', "Database - Yuki Official")

@section('content')
    {{-- 公式の Database のトップと同じく、PC はタイトルの右にクイック楽曲検索（公式とマイページのアーティストの曲から） --}}
    <div class="database-hero database-hero--nav">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => "Database"],
            ]])
            <div class="setlists-header-row">
                <div style="flex-shrink: 0;">
                    <h1 class="database-title" style="white-space: nowrap;">Database</h1>
                    <p class="database-subtitle" style="margin: 4px 0 0;">アーティストのライブ・楽曲データベース</p>
                </div>
                <div class="setlists-search-pc" style="min-width: 320px; position: relative; overflow: visible; flex-shrink: 0;">
                    @livewire('user-artist-song-search', [], key('mypage-database-search-pc'))
                </div>
            </div>
        </div>
    </div>

    <div class="container database-content">
        @if ($cards->isEmpty())
            <p style="text-align: center; color: #999;">まだ登録されているアーティストがありません。</p>
        @else
            <div class="timeline-filter-row">
                <select class="timeline-user-select" onchange="if (this.value) window.location.href=this.value;">
                    <option value="{{ route('mypage.user_artists.index') }}" selected>すべて</option>
                    {{-- 公式の Database のアーティストも混ぜて名前順に並べる（公式は公式のアーティストのページへ） --}}
                    @foreach ($cards as $card)
                        <option value="{{ $card->show_url }}">{{ $card->name }}</option>
                    @endforeach
                </select>
            </div>
            {{-- スマホはアーティストの選択の下にクイック楽曲検索 --}}
            <div class="sp" style="margin-bottom: 20px;">
                @livewire('user-artist-song-search', [], key('mypage-database-search-sp'))
            </div>
            <div class="row justify-content-center">
                {{-- 公式の Database のアーティストとマイページで作られたアーティストを、名前順に --}}
                @foreach ($cards as $card)
                    <div class="col-lg-4 mb-4">
                        <div class="database-card">
                            <div class="card-icon">
                                <i class="fa-solid fa-music"></i>
                            </div>
                            <h3 class="card-title">{{ $card->name }}</h3>
                            <p class="card-description">{{ $card->songs_count }}曲 / {{ $card->concerts_count }}ツアー</p>
                            <div class="card-links">
                                <a href="{{ $card->stats_url }}" class="database-link">
                                    <span><i class="fa-solid fa-chart-simple" style="margin-right: 10px;"></i>Stats</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                                <a href="{{ $card->live_url }}" class="database-link">
                                    <span><i class="fa-solid fa-guitar" style="margin-right: 10px;"></i>Live</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                                <a href="{{ $card->songs_url }}" class="database-link">
                                    <span><i class="fa-solid fa-music" style="margin-right: 10px;"></i>Songs</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                                <a href="{{ $card->show_url }}" class="database-link">
                                    <span><i class="fa-solid fa-database" style="margin-right: 10px;"></i>View All</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
