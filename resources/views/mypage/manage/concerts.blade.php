@extends('layouts.app')

@section('title', $artist->name . ' ツアーを管理 - Manage My Artists & Setlists')
@section('og_title', $artist->name . ' ツアーを管理 - Yuki Official')

@section('content')
    <div class="database-hero database-hero--detail manage-page">
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
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul style="margin-bottom: 0; padding-left: 20px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

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
                <p id="noConcertsMessage" style="text-align: center; color: #999; margin-top: 0;" @if ($concerts->isNotEmpty() || $errors->any()) hidden @endif>まだツアーがありません。</p>

                <div style="margin-top: 24px; text-align: center;">
                    <a href="#" id="newConcertToggle" class="mypage-add-button" title="ツアーを追加" style="display: inline-flex;" @if(!$errors->any()) onclick="event.preventDefault(); document.getElementById('newConcertForm').hidden = false; document.getElementById('noConcertsMessage').hidden = true; this.hidden = true;" @else hidden @endif>
                        <i class="fas fa-plus"></i>
                    </a>
                    <form id="newConcertForm" method="POST" action="{{ route('mypage.manage.concerts.store', $artist->id) }}" @if(!$errors->any()) hidden @endif style="max-width: 360px; margin: 16px auto 0; text-align: left;">
                        @csrf
                        <div class="mb-3">
                            <label for="new_concert_title" class="form-label">ツアー名</label>
                            <input type="text" class="form-control" id="new_concert_title" name="title" value="{{ old('title') }}" required>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="new_concert_is_fes" name="is_fes" value="1" @if(old('is_fes')) checked @endif>
                            <label class="form-check-label" for="new_concert_is_fes">
                                フェス・複数アーティスト出演イベント
                            </label>
                        </div>
                        <div class="mb-3">
                            <label for="new_concert_date1" class="form-label">開始日</label>
                            <input type="date" class="form-control" id="new_concert_date1" name="date1" value="{{ old('date1') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_concert_date2" class="form-label">終了日（任意・単発の場合は空欄）</label>
                            <input type="date" class="form-control" id="new_concert_date2" name="date2" value="{{ old('date2') }}">
                        </div>
                        <button type="submit" class="btn btn-outline-dark w-100">追加</button>
                        <div style="text-align: center; margin-top: 8px;">
                            <button type="button" onclick="document.getElementById('newConcertForm').hidden = true; document.getElementById('newConcertToggle').hidden = false; if (!document.querySelector('#concertList .manage-artist-row')) { document.getElementById('noConcertsMessage').hidden = false; }" style="background: none; border: none; color: #999; cursor: pointer; padding: 8px;" title="閉じる">
                                <i class="fa-solid fa-xmark" style="font-size: 26px;"></i>
                            </button>
                        </div>
                    </form>
                </div>
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
