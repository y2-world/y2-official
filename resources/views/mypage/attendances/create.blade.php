@extends('layouts.app')
@section('title', 'Yuki Official - セットリスト登録')

@section('content')
    <div class="database-hero database-hero--detail manage-page">
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
            <div class="col-lg-8">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul style="margin-bottom: 0; padding-left: 20px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($officialArtists->isEmpty() && $myArtists->isEmpty())
                    <p data-hide-while-adding @if ($errors->any()) hidden @endif>選択できるアーティストがまだありません。</p>
                @endif

                {{-- 公式のアーティストと自分で追加したアーティストを分けずに、1つの一覧に名前順で並べる --}}
                @php
                    $allArtists = \App\Support\JapaneseNameSorter::sortBy(
                        $officialArtists->map(fn ($artist) => (object) ['name' => $artist->name, 'ref' => 'official-' . $artist->id])
                            ->concat($myArtists->map(fn ($artist) => (object) ['name' => $artist->name, 'ref' => 'user-' . $artist->id]))
                            ->values(),
                        'name'
                    );
                @endphp
                @if (count($allArtists))
                    <div class="pick-card-grid" data-hide-while-adding @if ($errors->any()) hidden @endif>
                        @foreach ($allArtists as $artist)
                            <a href="{{ route('mypage.attendances.tours', $artist->ref) }}" class="pick-card">
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
