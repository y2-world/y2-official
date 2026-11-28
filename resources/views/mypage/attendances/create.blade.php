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
            {{-- Yuki 本人のアカウントは Yuki's Setlists の公演から選ぶので「セットリストを選択」 --}}
            <h1 class="database-title" style="text-align: center;">{{ !empty($yukiCandidates['matched']) ? 'セットリストを選択' : 'アーティストを選択' }}</h1>
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

                {{-- Yuki 本人のアカウントだけ：Yuki's Setlists の公演（まだ登録していない日）から選ぶ。選ぶと、パターン・参加日・日替わりの曲を入れた状態で
                     参加日・会場の入力画面へ（パターンが2つ以上ある公演は、パターンごとに出す） --}}
                @if (!empty($yukiCandidates['matched']))
                    {{-- 一覧はツアーを選ぶ画面と同じ形（1行ずつ縦に並べる）。1行目はアーティスト、2行目はツアータイトル、3行目は会場と日付 --}}
                    <div data-hide-while-adding style="margin-bottom: 40px;">
                        <div class="select-card-list">
                            @foreach ($yukiCandidates['matched'] as $candidate)
                                @foreach ($candidate['patterns'] as $choice)
                                    <a href="{{ route('mypage.attendances.form', ['setlistId' => 'official-' . $choice['pattern']->id, 'date' => $candidate['date'], 'selected_daily_songs' => $choice['daily']]) }}" class="select-card">
                                        <span class="select-card-body">
                                            <span class="select-card-meta">{{ $candidate['artist'] }}</span>
                                            <span class="select-card-title">{{ $candidate['tour']->title }}@if (count($candidate['patterns']) > 1) ／ {{ \App\Support\PatternTitle::oneLine($choice['pattern']) ?: 'パターン ' . $loop->iteration }}@endif</span>
                                            <span class="select-card-meta">{{ $candidate['venue'] }}・{{ \Illuminate\Support\Carbon::parse($candidate['date'])->format('Y.m.d') }}</span>
                                        </span>
                                        <i class="fa-solid fa-chevron-right select-card-arrow"></i>
                                    </a>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                @else
                {{-- アーティストから選ぶ一覧（Yuki 本人のアカウントは Yuki's Setlists の公演から選ぶので出さない） --}}
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
                @endif
            </div>
        </div>
    </div>
@endsection
