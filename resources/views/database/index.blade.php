@extends('layouts.app')
@section('title', 'Yuki Official - Database')
@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container">
            <h1 class="database-title" style="text-align: center;">Database</h1>
            <p class="database-subtitle" style="text-align: center; margin-top: 8px; margin-bottom: 0;">アーティストのライブ・楽曲データベース</p>
        </div>
    </div>

    <div class="container database-content sp-pt-24">
        <div class="timeline-filter-row sp">
            <select class="timeline-user-select" onchange="if (this.value) window.location.href=this.value;">
                <option value="{{ url('/database') }}" selected>すべて</option>
                @foreach ($artists as $artist)
                    <option value="{{ route('database.artist', $artist->id) }}">{{ $artist->name }}</option>
                @endforeach
            </select>
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
                        <div class="card-links">
                            <a href="{{ route('database.live', $artist->id) }}" class="database-link">
                                <span>Live</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                            <a href="{{ route('database.songs', $artist->id) }}" class="database-link">
                                <span>Songs</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                            <a href="{{ route('database.artist', $artist->id) }}" class="database-link">
                                <span>View All</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection
