@extends('layouts.app')
@section('title', "Users' Database - My Page")
@section('og_title', "Users' Database - Yuki Official")

@section('content')
    <div class="container database-content" style="padding-top: 24px;">
        @php
            $myId = \Illuminate\Support\Facades\Auth::guard('external')->id();
        @endphp
        <div class="timeline-filter-row">
            <select class="timeline-user-select" onchange="if (this.value) window.location.href=this.value;">
                <option value="{{ route('mypage.timeline.index', ['user_id' => 'all']) }}">すべての投稿</option>
                <option value="{{ route('mypage.timeline.index', ['user_id' => $myId]) }}">自分の投稿</option>
                <option value="{{ route('mypage.user_artists.index') }}" selected>Users' Database</option>
            </select>
        </div>

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
