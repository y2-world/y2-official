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

                @if ($officialArtists->isNotEmpty())
                    <div class="pick-card-grid" data-hide-while-adding @if ($errors->any()) hidden @endif>
                        @foreach ($officialArtists as $artist)
                            <a href="{{ route('mypage.attendances.tours', 'official-' . $artist->id) }}" class="pick-card">
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

                @if ($officialArtists->isNotEmpty() && $myArtists->isNotEmpty())
                    <hr style="margin: 32px 0;" data-hide-while-adding @if ($errors->any()) hidden @endif>
                @endif

                <div style="text-align: center;">
                    <div class="mypage-add-row" style="margin-bottom: 24px;">
                        <span class="mypage-add-row-label">アーティストを追加</span>
                        <a href="#" id="newArtistToggle" class="mypage-add-button" title="その他のアーティストを追加" @if(!$errors->any()) onclick="event.preventDefault(); setNewArtistFormOpen(true);" @else hidden @endif>
                            <i class="fas fa-plus"></i>
                        </a>
                    </div>
                    <form id="newArtistForm" method="POST" action="{{ route('mypage.attendances.artists.new') }}" @if(!$errors->any()) hidden @endif class="new-item-form @unless($errors->any()) new-item-form--via-toggle @endunless">
                        @csrf
                        <div class="mb-3">
                            <label for="new_artist_name" class="form-label">アーティスト名</label>
                            <input type="text" class="form-control" id="new_artist_name" name="name" value="{{ old('name') }}" required>
                        </div>
                        <button type="submit" class="btn btn-outline-dark w-100">追加</button>
                        <div style="text-align: center; margin-top: 8px;">
                            <button type="button" onclick="setNewArtistFormOpen(false);" style="background: none; border: none; color: #999; cursor: pointer; padding: 20px;" title="閉じる">
                                <span class="close-x-thin" style="font-size: 30px;"></span>
                            </button>
                        </div>
                    </form>
                </div>

                @if ($myArtists->isNotEmpty())
                    <div class="pick-card-grid" data-hide-while-adding @if ($errors->any()) hidden @endif>
                        @foreach ($myArtists as $artist)
                            <a href="{{ route('mypage.attendances.tours', 'user-' . $artist->id) }}" class="pick-card">
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
    <script>
    // 追加のフォームを開いている間は、一覧（data-hide-while-adding を付けたもの）を隠す
    function setNewArtistFormOpen(open) {
        document.getElementById('newArtistForm').hidden = !open;
        document.getElementById('newArtistToggle').hidden = open;
        document.querySelectorAll('[data-hide-while-adding]').forEach((el) => { el.hidden = open; });
    }
    </script>
@endsection
