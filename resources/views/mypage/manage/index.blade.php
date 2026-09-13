@extends('layouts.app')

@section('title', 'Manage My Artists & Setlists')
@section('og_title', 'Manage My Artists & Setlists - Yuki Official')

@section('content')
    <div class="database-hero database-hero--detail">
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
            <div class="col-lg-10">
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
