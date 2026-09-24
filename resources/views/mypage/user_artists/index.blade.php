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
        @if ($artists->isEmpty())
            <p style="text-align: center; color: #999;">まだ登録されているアーティストがありません。</p>
        @else
            <div class="row justify-content-center">
                @foreach ($artists as $artist)
                    <div class="col-lg-4 mb-4">
                        <div class="database-card">
                            <div class="card-icon">
                                <i class="fa-solid fa-music"></i>
                            </div>
                            <h3 class="card-title">{{ $artist->name }}</h3>
                            <p class="card-description">{{ $artist->songs_count }}曲 / {{ $artist->concerts_count }}ツアー</p>
                            <div class="card-links">
                                <a href="{{ route('mypage.user_artists.live', $artist->id) }}" class="database-link">
                                    <span>Live</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </a>
                                <a href="{{ route('mypage.user_artists.songs', $artist->id) }}" class="database-link">
                                    <span>Songs</span>
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
