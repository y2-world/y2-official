@extends('layouts.app')
@section('title', "Users' Database - My Page")
@section('og_title', "Users' Database - Yuki Official")

@section('content')
    <div class="database-hero database-hero--nav">
        <div class="container" style="position: relative;">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => "Users' Database"],
            ]])
            <h1 class="database-title" style="text-align: center;">Users' Database</h1>
            <p class="database-subtitle" style="text-align: center;">ユーザーが登録したアーティスト・ツアー・セットリスト</p>
        </div>
    </div>

    <div class="container database-content">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                @if ($artists->isEmpty())
                    <p style="text-align: center; color: #999;">まだ登録されているアーティストがありません。</p>
                @else
                    <div class="select-card-list">
                        @foreach ($artists as $artist)
                            <a href="{{ route('mypage.user_artists.live', $artist->id) }}" class="select-card">
                                <span class="select-card-body">
                                    <span class="select-card-title">{{ $artist->name }}</span>
                                    <span class="select-card-meta">{{ $artist->songs_count }}曲 / {{ $artist->concerts_count }}ツアー</span>
                                </span>
                                <i class="fa-solid fa-chevron-right select-card-arrow"></i>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
