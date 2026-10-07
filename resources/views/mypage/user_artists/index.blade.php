@extends('layouts.app')
@section('title', "Database - My Page")
@section('og_title', "Database - Yuki Official")

@section('content')
    <div class="database-hero database-hero--detail manage-page">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => "Database"],
            ]])
            <h1 class="database-title" style="text-align: center;">Database</h1>
            <p class="database-subtitle" style="text-align: center;">アーティストのライブ・楽曲データベース</p>
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
                                    <span>Stats</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                                <a href="{{ $card->live_url }}" class="database-link">
                                    <span>Live</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                                <a href="{{ $card->songs_url }}" class="database-link">
                                    <span>Songs</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                                <a href="{{ $card->show_url }}" class="database-link">
                                    <span>View All</span>
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
