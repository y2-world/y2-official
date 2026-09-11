@extends('layouts.app')
@section('title', 'Yuki Official - ライブの参加記録を追加')

@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => 'ライブの参加記録を追加'],
            ]])
            <h1 class="database-title" style="text-align: center;">アーティストを選択</h1>
        </div>
    </div>

    <div class="container database-content">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                @if ($artists->isEmpty())
                    <p>選択できるアーティストがまだありません。</p>
                @else
                    <div class="select-card-grid">
                        @foreach ($artists as $artist)
                            <a href="{{ route('mypage.attendances.tours', $artist->id) }}" class="select-card">
                                <span class="select-card-icon"><i class="fa-solid fa-music"></i></span>
                                <span class="select-card-body">
                                    <span class="select-card-title">{{ $artist->name }}</span>
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
