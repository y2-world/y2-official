@extends('layouts.app')

@section('title', 'Manage My Artists & Setlists')
@section('og_title', 'Manage My Artists & Setlists - Yuki Official')

@section('content')
    <div class="database-hero database-hero--detail manage-page">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => 'Manage My Artists & Setlists'],
            ]])
            <h1 class="database-title" style="text-align: center;">Manage My Artists & Setlists</h1>
            <p class="database-subtitle" style="text-align: center;">自分で登録したアーティストの曲やツアーを管理</p>
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

                @if ($artists->isEmpty())
                    <p style="text-align: center; color: #999;">まだアーティストを登録していません。</p>
                @else
                    <div class="select-card-list">
                        @foreach ($artists as $artist)
                            <div class="manage-artist-row">
                                <a href="{{ route('mypage.manage.artist', $artist->id) }}" class="select-card">
                                    <span class="select-card-body">
                                        <span class="select-card-title">{{ $artist->name }}</span>
                                        <span class="select-card-meta">{{ $artist->songs_count }}曲 / {{ $artist->concerts_count }}ツアー</span>
                                    </span>
                                    <button type="button" class="manage-artist-delete" data-delete-url="{{ route('mypage.manage.artists.destroy', $artist->id) }}" data-confirm="このアーティストを削除すると、含まれるツアー・セットリスト・曲もすべて削除されます。よろしいですか？" title="削除">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                    <i class="fa-solid fa-chevron-right select-card-arrow"></i>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div style="margin-top: 24px; text-align: center;">
                    <a href="#" id="newArtistToggle" class="mypage-add-button" title="アーティストを追加" style="display: inline-flex;" @if(!$errors->any()) onclick="event.preventDefault(); document.getElementById('newArtistForm').hidden = false; this.hidden = true;" @else hidden @endif>
                        <i class="fas fa-plus"></i>
                    </a>
                    <form id="newArtistForm" method="POST" action="{{ route('mypage.manage.artists.store') }}" @if(!$errors->any()) hidden @endif class="new-item-form @unless($errors->any()) new-item-form--via-toggle @endunless">
                        @csrf
                        <div class="mb-3">
                            <label for="new_artist_name" class="form-label">アーティスト名</label>
                            <input type="text" class="form-control" id="new_artist_name" name="name" value="{{ old('name') }}" required>
                        </div>
                        <button type="submit" class="btn btn-outline-dark w-100">追加</button>
                        <div style="text-align: center; margin-top: 8px;">
                            <button type="button" onclick="document.getElementById('newArtistForm').hidden = true; document.getElementById('newArtistToggle').hidden = false;" style="background: none; border: none; color: #999; cursor: pointer; padding: 20px;" title="閉じる">
                                <span class="close-x-thin" style="font-size: 30px;"></span>
                            </button>
                        </div>
                    </form>
                </div>

                @if ($isDatabaseManager)
                    <hr style="margin: 40px 0;">
                    <div class="select-card-list">
                        @foreach ($databaseArtists as $dbArtist)
                            <a href="{{ route('mypage.manage.database_songs', $dbArtist->id) }}" class="select-card">
                                <span class="select-card-body">
                                    <span class="select-card-title">{{ $dbArtist->name }}</span>
                                    <span class="select-card-meta">{{ $dbArtist->songs_count }}曲</span>
                                </span>
                                <i class="fa-solid fa-chevron-right select-card-arrow"></i>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
    document.querySelectorAll('.manage-artist-delete').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (!confirm(btn.dataset.confirm || '削除しますか？')) return;
            const row = btn.closest('.manage-artist-row');
            fetch(btn.dataset.deleteUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            })
                .then((res) => res.json())
                .then((data) => {
                    row.remove();
                    showAppToast(data.message);
                });
        });
    });
    </script>
@endsection
