@extends('layouts.app')

@section('title', $artist->name . ' ツアーを管理 - Manage My Artists & Setlists')
@section('og_title', $artist->name . ' ツアーを管理 - Yuki Official')

@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => 'Manage My Artists & Setlists', 'url' => route('mypage.manage.index')],
                ['label' => $artist->name, 'url' => route('mypage.manage.artist', $artist->id)],
                ['label' => 'ツアーを管理'],
            ]])
            <p class="database-subtitle" style="text-align: center; margin-bottom: 0;">{{ $artist->name }}</p>
            <h1 class="database-title" style="text-align: center;">ツアーを管理</h1>
        </div>
    </div>

    <div class="container database-content">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div id="concertList">
                    @foreach ($concerts as $concert)
                        <div class="manage-artist-row">
                            <a href="{{ route('mypage.manage.setlists', [$artist->id, $concert->id]) }}" class="select-card">
                                <span class="select-card-body">
                                    <span class="select-card-title">{{ $concert->title }}</span>
                                    <span class="select-card-meta">
                                        @if ($concert->date1)
                                            <span style="display: block;">
                                                {{ \Carbon\Carbon::parse($concert->date1)->format('Y.m.d') }}
                                                @if ($concert->date2 && $concert->date2 !== $concert->date1)
                                                    - {{ \Carbon\Carbon::parse($concert->date2)->format('Y.m.d') }}
                                                @endif
                                            </span>
                                        @endif
                                        <span style="display: block;">{{ $concert->setlists_count }}パターン</span>
                                    </span>
                                </span>
                                <button type="button" class="manage-artist-delete" data-delete-url="{{ route('mypage.manage.concerts.destroy', [$artist->id, $concert->id]) }}" data-confirm="このツアーを削除すると、含まれるセットリストパターンもすべて削除されます。よろしいですか？" title="削除">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                                <i class="fa-solid fa-chevron-right select-card-arrow"></i>
                            </a>
                        </div>
                    @endforeach
                </div>
                <p id="noConcertsMessage" style="text-align: center; color: #999;" @if ($concerts->isNotEmpty()) hidden @endif>まだツアーがありません。</p>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const list = document.getElementById('concertList');
        if (!list) return;

        list.querySelectorAll('.manage-artist-delete').forEach((btn) => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (!confirm(btn.dataset.confirm || 'このツアーを削除しますか？')) return;
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
                        if (!list.querySelector('.manage-artist-row')) {
                            document.getElementById('noConcertsMessage').hidden = false;
                        }
                    });
            });
        });
    })();
    </script>
@endsection
