@extends('layouts.app')

@section('title', 'My Stamp Books')
@section('og_title', 'My Stamp Books - Yuki Official')

@section('content')
<div class="stats-wrapper">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="element js-fadein compact-top">
                    <div class="breadcrumb-nav">
                        <a href="{{ route('mypage.index') }}" class="back-link">← Back to My Page</a>
                    </div>

                    <h1 class="stats-title">My Stamp Books</h1>
                    <p class="stats-subtitle">参加したライブのアーティストごとに、演奏された曲を集めよう</p>

                    @if ($officialArtists->isEmpty() && $userArtists->isEmpty())
                        <p style="text-align: center; color: #999; margin-top: 40px;">まだスタンプ帳を作れるアーティストがありません。</p>
                    @else
                        <div class="stamp-book-link-wrapper" style="display: flex; gap: 12px; flex-wrap: wrap; justify-content: flex-start; margin-top: 24px;">
                            @foreach ($officialArtists as $artist)
                                <a href="{{ route('mypage.stats.stamps', 'official-' . $artist->id) }}" class="stamp-book-link">
                                    <i class="fas fa-stamp"></i> {{ $artist->name }}
                                </a>
                            @endforeach
                            @foreach ($userArtists as $artist)
                                <a href="{{ route('mypage.stats.stamps', 'user-' . $artist->id) }}" class="stamp-book-link">
                                    <i class="fas fa-stamp"></i> {{ $artist->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
