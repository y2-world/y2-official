@extends('layouts.app')
@section('title', 'Yuki Official - セットリストを入力')

@section('content')
    <div class="database-hero database-hero--detail">
        <div class="container">
            @include('database._breadcrumb', ['breadcrumbs' => [
                ['label' => 'My Page', 'url' => route('mypage.index')],
                ['label' => 'セットリスト登録', 'url' => route('mypage.attendances.create')],
                ['label' => $artistName, 'url' => route('mypage.attendances.tours', $artistId === 'new' ? ['artistId' => 'new', 'name' => $artistName] : $artistId)],
                ['label' => $tourTitle],
            ]])
            <p class="database-subtitle" style="text-align: center; margin-bottom: 0;">セットリスト登録</p>
            <p class="database-subtitle" style="text-align: center; margin-bottom: 0;">{{ $artistName }}</p>
            <h1 class="database-title" style="text-align: center;">{{ $tourTitle }}</h1>
            <p class="database-subtitle" style="text-align: center;">セットリストを入力</p>
        </div>
    </div>

    <div class="container database-content">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul style="margin-bottom: 0; padding-left: 20px;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('mypage.attendances.setlist_create.confirm', ['artistId' => $artistId, 'tourId' => $tourId]) }}">
                    @csrf
                    @if ($artistId === 'new')
                        <input type="hidden" name="artist_name" value="{{ $artistName }}">
                    @endif
                    @if ($tourId === 'new')
                        <input type="hidden" name="tour_title" value="{{ $tourTitle }}">
                        <input type="hidden" name="tour_date1" value="{{ $tourDate1 }}">
                        <input type="hidden" name="tour_date2" value="{{ $tourDate2 }}">
                    @endif

                    <div id="setlistRows" class="setlist-song-rows"></div>
                    <div style="text-align: center; margin-bottom: 24px;">
                        <button type="button" class="mypage-add-button" title="曲を追加" onclick="addSongRow('setlistRows', 'setlist')" style="border: none;">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>

                    <h5 style="font-size: 0.9rem; color: #999; letter-spacing: 1px;">ENCORE</h5>
                    <div id="encoreRows" class="setlist-song-rows"></div>
                    <div style="text-align: center; margin-bottom: 24px;">
                        <button type="button" class="mypage-add-button" title="曲を追加" onclick="addSongRow('encoreRows', 'encore')" style="border: none;">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>

                    <div class="d-flex gap-2 mt-3">
                        <a href="{{ $tourId === 'new' ? route('mypage.attendances.tours', $artistId === 'new' ? ['artistId' => 'new', 'name' => $artistName] : $artistId) : route('mypage.attendances.setlists', $tourId) }}" class="btn btn-outline-secondary w-100">戻る</a>
                        <button type="submit" class="btn btn-outline-dark w-100">次へ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
    .setlist-song-row {
        position: relative;
        border-radius: 10px;
        margin-bottom: 10px;
        background: white;
        border: 1px solid #eee;
        padding: 10px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .setlist-song-row .song-row-reorder-buttons {
        display: flex;
        flex-direction: column;
        flex-shrink: 0;
    }
    .setlist-song-row .song-row-reorder-up,
    .setlist-song-row .song-row-reorder-down {
        background: none;
        border: none;
        color: #999;
        cursor: pointer;
        padding: 2px 6px;
        line-height: 1;
    }
    .setlist-song-row .song-row-reorder-up:hover,
    .setlist-song-row .song-row-reorder-down:hover {
        color: #667eea;
    }
    .setlist-song-row .song-row-number {
        flex: 0 0 auto;
        width: 1.6em;
        text-align: right;
        color: #999;
        font-size: 0.9em;
    }
    .setlist-song-row input[type="text"] {
        flex: 1;
        border: none;
        padding: 4px 0;
    }
    .setlist-song-row input[type="text"]:focus {
        outline: none;
        box-shadow: none;
    }
    .setlist-song-row .remove-row-btn {
        background: none;
        border: none;
        color: #dc3545;
        cursor: pointer;
        padding: 4px 8px;
        flex-shrink: 0;
    }
    </style>
    <script>
    const songTitleOptions = @json($songOptions->values());

    function addSongRow(containerId, fieldName) {
        const container = document.getElementById(containerId);
        const row = document.createElement('div');
        row.className = 'setlist-song-row';
        row.innerHTML = `
            <div class="song-row-reorder-buttons">
                <button type="button" class="song-row-reorder-up" title="上へ"><i class="fa-solid fa-chevron-up"></i></button>
                <button type="button" class="song-row-reorder-down" title="下へ"><i class="fa-solid fa-chevron-down"></i></button>
            </div>
            <span class="song-row-number"></span>
            <input type="text" class="form-control" name="${fieldName}[]" placeholder="曲名">
            <button type="button" class="remove-row-btn" title="削除" onclick="this.closest('.setlist-song-row').remove(); renumberRows('${containerId}');">
                <i class="fa-solid fa-trash"></i>
            </button>
        `;
        container.appendChild(row);
        setupSongRowDrag(row, container);
        renumberRows(containerId);
        const input = row.querySelector('input');
        input.dataset.autocompleteOptions = JSON.stringify(songTitleOptions);
        initAutocomplete(input);
        input.focus();
    }

    // --- 上下ボタンで1つずつ順位を入れ替える（ドラッグ操作は誤操作が多いため） ---
    function setupSongRowDrag(row, container) {
        row.querySelector('.song-row-reorder-up').addEventListener('click', () => {
            const prev = row.previousElementSibling;
            if (!prev) return;
            container.insertBefore(row, prev);
            renumberRows(container.id);
        });
        row.querySelector('.song-row-reorder-down').addEventListener('click', () => {
            const next = row.nextElementSibling;
            if (!next) return;
            container.insertBefore(next, row);
            renumberRows(container.id);
        });
    }

    function renumberRows(containerId) {
        const container = document.getElementById(containerId);
        container.querySelectorAll('.setlist-song-row').forEach((row, index) => {
            row.querySelector('.song-row-number').textContent = (index + 1) + '.';
        });
    }

    // initAutocompleteはlayouts/app.blade.php側の<script>で定義されるが、
    // そちらは@yield('content')より後にレンダリングされるため、ここでの即時実行では
    // 未定義エラーになる。DOMContentLoadedまで遅らせて確実に定義済みの状態で呼び出す。
    document.addEventListener('DOMContentLoaded', function () {
        // 初期表示時に本編・アンコールそれぞれ1行ずつ用意しておく
        addSongRow('setlistRows', 'setlist');
        addSongRow('encoreRows', 'encore');
    });
    </script>
@endsection
