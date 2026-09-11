@extends('layouts.app')
@section('title', 'Yuki Official - セットリスト登録')

@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => 'セットリスト登録'],
            ]])
            <p class="database-subtitle" style="text-align: center; margin-bottom: 0;">セットリスト登録</p>
            <h1 class="database-title" style="text-align: center;">アーティストを選択</h1>
        </div>
    </div>

    <div class="container database-content">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                @if ($artists->isEmpty())
                    <p>選択できるアーティストがまだありません。</p>
                @else
                    <div class="pick-card-grid">
                        @foreach ($artists as $artist)
                            <a href="{{ route('mypage.attendances.tours', $artist->id) }}" class="pick-card">
                                <div class="pick-card-header">
                                    <span class="select-card-icon"><i class="fa-solid fa-music"></i></span>
                                    <span class="select-card-body">
                                        <span class="select-card-title">{{ $artist->name }}</span>
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
