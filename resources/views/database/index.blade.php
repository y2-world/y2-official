@extends('layouts.app')
@section('title', 'Yuki Official - Database')
@section('content')
    <div class="database-hero">
        <div class="container">
            <h1 class="database-title" style="margin-bottom: 20px; text-align: center;">Database</h1>
            <p class="database-subtitle" style="text-align: center;">アーティストを選択してください</p>
        </div>
    </div>

    <div class="container database-content">
        <div class="row justify-content-center">
            @foreach ($artists as $artist)
                <div class="col-md-4 mb-4">
                    <a href="{{ route('database.artist', $artist->id) }}" class="text-decoration-none">
                        <div class="database-card" style="cursor: pointer;">
                            <div class="card-icon">
                                <i class="fa-solid fa-music"></i>
                            </div>
                            <h3 class="card-title">{{ $artist->name }}</h3>
                            @if ($artist->kana)
                                <p class="card-description">{{ $artist->kana }}</p>
                            @endif
                            <div class="card-links" style="margin-top: 12px;">
                                <span class="database-link">
                                    <span>Database を見る</span>
                                    <i class="fa-solid fa-arrow-right"></i>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endsection
